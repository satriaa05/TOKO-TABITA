<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Mail\PaymentStatusMail;
use App\Models\AuditLog;
use App\Models\CustomerNotification;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookLog;
use App\Models\Sale;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Satu-satunya tempat yang boleh mengubah status pembayaran menjadi PAID.
 *
 * Sumber perubahan: webhook Midtrans terverifikasi, fallback GET status API,
 * atau verifikasi manual Admin atas bukti transfer. Redirect browser TIDAK
 * pernah dipakai sebagai bukti pembayaran.
 */
class PaymentService
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly StockReleaseService $releases,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): array
    {
        if (! $this->midtrans->verifySignature($payload)) {
            $this->log($payload, null, 'invalid_signature', false, 'Signature tidak cocok.');

            return ['ok' => false, 'reason' => 'invalid_signature'];
        }

        $reference = (string) ($payload['order_id'] ?? '');
        $transaction = PaymentTransaction::query()->where('transaction_reference', $reference)->first();

        if (! $transaction) {
            $this->log($payload, $reference, 'ignored', true, 'Referensi transaksi tidak ditemukan.');

            return ['ok' => false, 'reason' => 'unknown_reference'];
        }

        return $this->process($payload, $transaction, 'webhook');
    }

    /**
     * Fallback bila webhook tidak pernah sampai.
     *
     * Respons GET status dari server Midtrans tidak memuat signature_key
     * webhook, jadi diverifikasi lewat kredensial API + pencocokan order_id
     * dan gross_amount dengan transaksi di database.
     */
    public function syncFromGateway(PaymentTransaction $transaction): array
    {
        $payload = $this->midtrans->status($transaction);

        if (! $this->midtrans->verifyStatusResponse($payload, $transaction)) {
            $this->log($payload, $transaction->transaction_reference, 'invalid_signature', false, 'Respons status gateway tidak cocok dengan transaksi.');

            return ['ok' => false, 'reason' => 'invalid_gateway_response'];
        }

        return $this->process($payload, $transaction, 'gateway');
    }

    /**
     * Satu tempat untuk dedup + terjemahan status, dipakai webhook maupun
     * fallback GET status. Idempoten: event yang sama diproses sekali.
     *
     * @param  array<string, mixed>  $payload
     */
    private function process(array $payload, PaymentTransaction $transaction, string $prefix): array
    {
        $reference = (string) ($payload['order_id'] ?? '');
        $eventId = $prefix.':'.hash('sha256', (string) json_encode($payload));

        if (abs((float) ($payload['gross_amount'] ?? 0) - (float) $transaction->amount) > 0.01) {
            $this->log($payload, $reference, 'failed', true, 'Nominal tidak cocok dengan transaksi.', $eventId);

            return ['ok' => false, 'reason' => 'amount_mismatch'];
        }

        try {
            $log = PaymentWebhookLog::firstOrCreate(
                ['event_id' => $eventId],
                [
                    'provider' => 'midtrans',
                    'transaction_reference' => $reference,
                    'sanitized_payload' => $this->sanitize($payload),
                    'signature_valid' => true,
                    'processing_status' => 'received',
                    'processed_at' => null,
                ]
            );
        } catch (QueryException) {
            // Dua webhook identik bersamaan: yang lain sudah memenangkan insert.
            return ['ok' => true, 'duplicate' => true];
        }

        if ($log->processing_status === 'processed') {
            return ['ok' => true, 'duplicate' => true];
        }

        $status = (string) ($payload['transaction_status'] ?? '');
        $fraud = (string) ($payload['fraud_status'] ?? 'accept');
        $providerRef = (string) ($payload['transaction_id'] ?? '');
        $detail = [
            'transaction_status' => $status,
            'fraud_status' => $fraud,
            'payment_type' => $payload['payment_type'] ?? null,
            'source' => $prefix,
        ];

        if ($status === 'settlement' && $fraud === 'accept') {
            $this->markPaid($transaction, $detail, $prefix, $providerRef);
        } elseif (in_array($status, ['deny', 'cancel'], true)) {
            $this->markFailed($transaction, $detail, $prefix);
        } elseif ($status === 'expire') {
            $this->markExpired($transaction, $prefix);
        }

        $log->update(['processing_status' => 'processed', 'processed_at' => now()]);

        return ['ok' => true];
    }

    /**
     * Tandai transaksi LUNAS. Idempotent: memanggil dua kali tidak mengubah apa pun.
     *
     * @param  array<string, mixed>  $detail
     */
    public function markPaid(
        PaymentTransaction $transaction,
        array $detail = [],
        string $source = 'admin',
        string $providerReference = ''
    ): bool {
        return DB::transaction(function () use ($transaction, $detail, $source, $providerReference) {
            /** @var PaymentTransaction $locked */
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($locked->status === PaymentStatus::Paid) {
                return false;
            }

            $previousStatus = $locked->status;
            $latePayment = in_array($previousStatus, [PaymentStatus::Failed, PaymentStatus::Expired], true);

            /** @var Sale $sale */
            $sale = Sale::query()->lockForUpdate()->findOrFail($locked->sale_id);
            $oldStatus = $sale->payment_status;

            $locked->status = PaymentStatus::Paid->value;
            $locked->paid_at = now();

            if ($providerReference !== '') {
                $locked->provider_reference = $providerReference;
            }

            if ($detail !== []) {
                $locked->failure_detail = null;
                $locked->metadata = array_merge((array) $locked->metadata, $detail);
            }

            $locked->save();

            $sale->payment_status = PaymentStatus::Paid->value;
            $sale->status = SaleStatus::Completed->value;
            $sale->paid_amount = (float) $sale->total;
            $sale->paid_at = now();
            $sale->completed_at = now();
            $sale->save();

            $reReserved = false;
            $reReserveError = null;

            if ($latePayment && $sale->stock_released_at !== null) {
                try {
                    $reReserved = $this->releases->reReserve(
                        $sale,
                        'Stok dipotong ulang: pembayaran '.$sale->invoice_number.' diterima setelah status '.$previousStatus->value.'.'
                    );
                } catch (\Throwable $e) {
                    // Uang sudah diterima: jangan gagalkan status LUNAS hanya karena
                    // stok tidak cukup. Catat untuk ditindaklanjuti Admin.
                    $reReserveError = $e->getMessage();
                    Log::error('Gagal memotong ulang stok '.$sale->invoice_number.': '.$reReserveError);
                }
            }

            // Catatan pembayaran untuk laporan POS yang sudah ada.
            Payment::create([
                'sale_id' => $sale->id,
                'method' => $this->paymentMethodFor((string) $locked->channel->code),
                'amount' => (float) $locked->amount,
                'reference' => $locked->provider_reference ?: ('MANUAL-'.$sale->invoice_number),
                'paid_at' => now(),
            ]);

            OrderStatusHistory::create([
                'sale_id' => $sale->id,
                'old_payment_status' => $oldStatus?->value,
                'new_payment_status' => PaymentStatus::Paid->value,
                'source' => $source,
                'note' => 'Pembayaran terverifikasi ('.$source.').'
                    .($reReserved ? ' Stok dipotong ulang setelah pembayaran terlambat.' : '')
                    .($reReserveError !== null ? ' PERHATIAN: stok gagal dipotong ulang.' : ''),
            ]);

            $this->notify(
                $sale,
                NotificationType::PaymentSuccess,
                'Pembayaran '.$sale->invoice_number.' berhasil dikonfirmasi. Pesanan Anda akan segera diproses.'
                    .($reReserveError !== null ? ' Catatan: stok sedang diverifikasi tim kami.' : '')
            );

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'payment.mark_paid',
                'model_type' => PaymentTransaction::class,
                'model_id' => $locked->id,
                'description' => 'Pembayaran '.$sale->invoice_number.' ditandai LUNAS via '.$source.'.',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return true;
        });
    }

    /** @param  array<string, mixed>  $detail */
    public function markFailed(PaymentTransaction $transaction, array $detail = [], string $source = 'webhook'): bool
    {
        return DB::transaction(function () use ($transaction, $detail, $source) {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if (in_array($locked->status, [PaymentStatus::Paid, PaymentStatus::Failed, PaymentStatus::Expired], true)) {
                return false;
            }

            $sale = Sale::query()->lockForUpdate()->findOrFail($locked->sale_id);
            $old = $sale->payment_status;

            $locked->status = PaymentStatus::Failed->value;
            $locked->failure_detail = $detail;
            $locked->save();

            $sale->payment_status = PaymentStatus::Failed->value;
            $sale->save();

            $this->releases->release(
                $sale,
                null,
                StockMovementType::Void,
                'Stok dikembalikan karena pembayaran pesanan '.$sale->invoice_number.' gagal.'
            );

            OrderStatusHistory::create([
                'sale_id' => $sale->id,
                'old_payment_status' => $old?->value,
                'new_payment_status' => PaymentStatus::Failed->value,
                'source' => $source,
                'note' => 'Pembayaran gagal / ditolak provider.',
            ]);

            $this->notify(
                $sale,
                NotificationType::PaymentFailed,
                'Pembayaran pesanan '.$sale->invoice_number.' gagal. Silakan coba lagi atau hubungi Admin.'
            );

            return true;
        });
    }

    public function markExpired(PaymentTransaction $transaction, string $source = 'webhook'): bool
    {
        return DB::transaction(function () use ($transaction, $source) {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if (in_array($locked->status, [PaymentStatus::Paid, PaymentStatus::Failed, PaymentStatus::Expired], true)) {
                return false;
            }

            $sale = Sale::query()->lockForUpdate()->findOrFail($locked->sale_id);
            $old = $sale->payment_status;

            $locked->status = PaymentStatus::Expired->value;
            $locked->save();

            $sale->payment_status = PaymentStatus::Expired->value;
            $sale->save();

            $this->releases->release(
                $sale,
                null,
                StockMovementType::Void,
                'Stok dikembalikan karena pembayaran pesanan '.$sale->invoice_number.' kedaluwarsa.'
            );

            OrderStatusHistory::create([
                'sale_id' => $sale->id,
                'old_payment_status' => $old?->value,
                'new_payment_status' => PaymentStatus::Expired->value,
                'source' => $source,
                'note' => 'Batas waktu pembayaran terlampaui.',
            ]);

            $this->notify(
                $sale,
                NotificationType::PaymentExpired,
                'Pembayaran pesanan '.$sale->invoice_number.' kedaluwarsa. Buat pesanan baru jika masih ingin membeli.'
            );

            return true;
        });
    }

    /**
     * Catat kegagalan webhook yang belum sempat ditulis process().
     * Dipanggil controller supaya error tidak hilang hanya di laravel.log.
     */
    public function logFailure(array $payload, \Throwable $e): void
    {
        $this->log(
            $payload,
            (string) ($payload['order_id'] ?? ''),
            'failed',
            false,
            $e->getMessage(),
            'webhook-error:'.Str::random(16)
        );
    }

    /** @param  array<string, mixed>  $payload */
    private function log(
        array $payload,
        ?string $reference,
        string $status,
        bool $valid,
        ?string $error = null,
        ?string $eventIdOverride = null
    ): PaymentWebhookLog {
        $baseId = $eventIdOverride ?? hash('sha512', (string) json_encode($payload));
        // Webhook tidak valid boleh masuk berulang; beri suffix agar tidak
        // melanggar unique constraint saat penyerang mengirim ulang payload yang sama.
        $eventId = $status === 'invalid_signature'
            ? $baseId.'-'.Str::random(8)
            : $baseId;

        return PaymentWebhookLog::create([
            'provider' => 'midtrans',
            'event_id' => $eventId,
            'transaction_reference' => $reference,
            'sanitized_payload' => $this->sanitize($payload),
            'signature_valid' => $valid,
            'processing_status' => $status,
            'error_message' => $error,
            'processed_at' => $status === 'received' ? null : now(),
        ]);
    }

    /** @param  array<string, mixed>  $payload */
    private function sanitize(array $payload): array
    {
        return collect($payload)
            ->reject(fn ($value, $key) => is_string($key)
                && str_contains(strtolower($key), 'key')
                || (is_string($key) && str_contains(strtolower($key), 'secret')))
            ->map(fn ($value) => is_array($value) ? $this->sanitize($value) : $value)
            ->all();
    }

    private function notify(Sale $sale, NotificationType $type, string $message): void
    {
        CustomerNotification::firstOrCreate(
            ['event_key' => $type->value.'-'.$sale->id],
            [
                'user_id' => (int) $sale->user_id,
                'sale_id' => $sale->id,
                'type' => $type->value,
                'title' => $type->label(),
                'message' => $message,
                'icon' => $type->icon(),
            ]
        );

        if ($sale->user?->email) {
            try {
                // Sinkron: tidak bergantung queue worker/cron pada shared hosting.
                Mail::to($sale->user)->send(new PaymentStatusMail($sale, $type->label(), $message));
            } catch (\Throwable $e) {
                // Email gagal tidak boleh menggagalkan proses pembayaran.
                Log::error('Gagal mengirim email notifikasi '.$sale->invoice_number.': '.$e->getMessage());
            }
        }
    }

    private function paymentMethodFor(string $code): PaymentMethod
    {
        return match ($code) {
            'qris' => PaymentMethod::Qris,
            'gopay', 'ovo', 'dana', 'shopeepay' => PaymentMethod::Ewallet,
            default => PaymentMethod::Transfer,
        };
    }
}
