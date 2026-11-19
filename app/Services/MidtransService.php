<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\PaymentChannel;
use App\Models\PaymentTransaction;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Integrasi Midtrans Core API (QRIS dinamis, e-wallet, VA).
 *
 * Semua kredensial dibaca dari config yang bersumber dari .env.
 * Tidak ada server key yang dikirim ke browser/disimpan di database.
 */
class MidtransService
{
    public function isConfigured(): bool
    {
        return filled(config('payments.midtrans.server_key'));
    }

    /**
     * Buat transaksi ke Midtrans dan simpan referensi + QR ke database.
     *
     * @return array{provider_reference: string, qr_code_url: ?string, expired_at: ?Carbon}
     */
    public function charge(PaymentTransaction $transaction): array
    {
        $this->assertConfigured();

        $sale = $transaction->sale()->with('items', 'user')->firstOrFail();
        $channel = $transaction->channel;

        $response = Http::withBasicAuth((string) config('payments.midtrans.server_key'), '')
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($this->baseUrl().'/v2/charge', $this->payload($sale, $transaction, $channel));

        if (! $response->successful()) {
            throw new PaymentGatewayException($this->errorMessage($response));
        }

        $json = $response->json() ?? [];

        if (empty($json['transaction_id'])) {
            throw new PaymentGatewayException('Midtrans tidak mengembalikan transaction_id.');
        }

        $qrUrl = null;
        $paymentUrl = null;

        foreach ((array) ($json['actions'] ?? []) as $action) {
            if (! is_array($action) || empty($action['url'])) {
                continue;
            }

            $name = (string) ($action['name'] ?? '');

            if ($qrUrl === null && str_contains($name, 'qr-code')) {
                $qrUrl = (string) $action['url'];
            }

            if ($paymentUrl === null && str_contains($name, 'deeplink')) {
                $paymentUrl = (string) $action['url'];
            }
        }

        $expired = null;

        if (! empty($json['expiry_time'])) {
            try {
                $expired = Carbon::parse($json['expiry_time']);
            } catch (\Throwable) {
                $expired = null;
            }
        }

        $expired ??= now()->addMinutes((int) config('payments.midtrans.expiry_minutes', 30));

        $transaction->update([
            'provider_reference' => (string) $json['transaction_id'],
            'qr_code_url' => $qrUrl,
            'payment_url' => $paymentUrl,
            'acquirer' => isset($json['acquirer']) ? (string) $json['acquirer'] : null,
            'expired_at' => $expired,
            'metadata' => array_merge((array) $transaction->metadata, [
                'midtrans_status_code' => $json['status_code'] ?? null,
                'midtrans_transaction_status' => $json['transaction_status'] ?? null,
            ]),
        ]);

        return [
            'provider_reference' => (string) $json['transaction_id'],
            'qr_code_url' => $qrUrl,
            'expired_at' => $expired,
        ];
    }

    /**
     * Fallback bila webhook gagal diterima: tanyakan status langsung ke API.
     * Respons backend-to-backend ini dipercaya menurut dokumentasi Midtrans.
     *
     * @return array<string, mixed>
     */
    public function status(PaymentTransaction $transaction): array
    {
        $this->assertConfigured();

        if (! $transaction->provider_reference) {
            throw new PaymentGatewayException('Transaksi belum memiliki referensi provider.');
        }

        $response = Http::withBasicAuth((string) config('payments.midtrans.server_key'), '')
            ->acceptJson()
            ->timeout(20)
            ->get($this->baseUrl().'/v2/'.$transaction->provider_reference.'/status');

        if (! $response->successful()) {
            throw new PaymentGatewayException($this->errorMessage($response));
        }

        return (array) $response->json();
    }

    /**
     * SHA512(order_id + status_code + gross_amount + ServerKey)
     *
     * @see https://docs.midtrans.com/docs/https-notification-webhooks
     */
    public function verifySignature(array $payload): bool
    {
        $serverKey = (string) config('payments.midtrans.server_key');

        if ($serverKey === '') {
            return false;
        }

        $expected = hash('sha512',
            (string) ($payload['order_id'] ?? '')
            .(string) ($payload['status_code'] ?? '')
            .(string) ($payload['gross_amount'] ?? '')
            .$serverKey
        );

        return hash_equals($expected, (string) ($payload['signature_key'] ?? ''));
    }

    /**
     * Verifikasi respons GET /v2/{id}/status (fallback saat webhook tidak sampai).
     *
     * Respons status API tidak selalu memuat signature_key webhook, jadi
     * kepercayaan berasal dari Basic Auth server key + kecocokan order_id dan
     * gross_amount dengan transaksi yang ada di database kita.
     */
    public function verifyStatusResponse(array $payload, PaymentTransaction $transaction): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        if ((string) ($payload['order_id'] ?? '') !== $transaction->transaction_reference) {
            return false;
        }

        if (abs((float) ($payload['gross_amount'] ?? 0) - (float) $transaction->amount) > 0.01) {
            return false;
        }

        // Bila Midtrans menyertakan signature_key, tetap diverifikasi.
        if (! empty($payload['signature_key'])) {
            return $this->verifySignature($payload);
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function payload(Sale $sale, PaymentTransaction $transaction, PaymentChannel $channel): array
    {
        $items = $sale->items->map(fn ($item) => [
            'id' => (string) $item->product_id,
            'price' => (int) round((float) $item->total),
            'quantity' => (int) $item->qty,
            'name' => substr($item->product_name, 0, 50),
        ])->values()->all();

        $payload = [
            'payment_type' => $this->paymentType($channel->code),
            'transaction_details' => [
                'order_id' => $transaction->transaction_reference,
                'gross_amount' => (int) round((float) $transaction->amount),
            ],
            'item_details' => $items,
            'customer_details' => [
                'first_name' => $sale->buyer_name,
                'phone' => $sale->buyer_phone,
            ],
            'expiry' => [
                'duration' => (int) config('payments.midtrans.expiry_minutes', 30),
                'unit' => 'minutes',
            ],
        ];

        if ($channel->code === 'qris') {
            $payload['qris'] = ['acquirer' => 'gopay'];
        }

        if ($channel->code === 'gopay') {
            $payload['gopay'] = ['enable_callback' => true];
        }

        if ($channel->code === 'va') {
            $payload['bank_transfer'] = ['bank' => 'bca'];
        }

        return $payload;
    }

    private function paymentType(string $code): string
    {
        return match ($code) {
            'qris' => 'qris',
            'gopay', 'ovo', 'dana', 'shopeepay' => $code,
            'va' => 'bank_transfer',
            default => 'qris',
        };
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('payments.midtrans.api_url'), '/');
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayException(
                'Payment gateway belum dikonfigurasi. Isi MIDTRANS_SERVER_KEY di .env terlebih dahulu.'
            );
        }
    }

    private function errorMessage($response): string
    {
        $body = $response->json();
        $message = is_array($body) ? ($body['status_message'] ?? null) : null;

        return $message
            ? 'Midtrans menolak permintaan: '.$message
            : 'Midtrans tidak merespons (HTTP '.$response->status().').';
    }
}
