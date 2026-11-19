<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Fallback otomatis bila webhook Midtrans tidak sampai.
 * Aman dijalankan berulang: PaymentService sudah idempoten.
 */
class SyncPendingPayments extends Command
{
    protected $signature = 'payments:sync-pending {--limit=100 : Maksimal transaksi per siklus}';

    protected $description = 'Sinkronkan status pembayaran gateway yang belum dikonfirmasi webhook';

    public function handle(PaymentService $payments, MidtransService $midtrans): int
    {
        if (! $midtrans->isConfigured()) {
            $this->warn('Midtrans belum dikonfigurasi; tidak ada yang disinkronkan.');

            return self::SUCCESS;
        }

        $transactions = \App\Models\PaymentTransaction::query()
            ->with('channel')
            ->whereIn('status', [
                PaymentStatus::Unpaid->value,
                PaymentStatus::Failed->value,
                PaymentStatus::Expired->value,
            ])
            ->whereNotNull('provider_reference')
            ->whereHas('channel', fn ($q) => $q->where('mode', 'automatic')->where('is_active', true))
            ->orderBy('updated_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $synced = 0;
        $failed = 0;

        foreach ($transactions as $transaction) {
            try {
                $payments->syncFromGateway($transaction);
                $synced++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Gagal sinkron pembayaran '.$transaction->transaction_reference.': '.$e->getMessage());
            }
        }

        $this->info("Sinkron selesai: {$synced} berhasil, {$failed} gagal.");

        return self::SUCCESS;
    }
}
