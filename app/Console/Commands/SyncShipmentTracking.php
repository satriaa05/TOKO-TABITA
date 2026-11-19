<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Services\BiteshipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Polling scan nyata dari Biteship.
 *
 * Tidak mengarang posisi kurir. Command ini hanya mengambil data yang benar-benar
 * dikirim provider, lalu BiteshipService menyimpan event/koordinat yang ada.
 */
class SyncShipmentTracking extends Command
{
    protected $signature = 'shipping:sync-tracking {--minutes=5 : Minimal selisih sinkron ulang} {--limit=100 : Maksimal pengiriman per siklus}';

    protected $description = 'Sinkronkan scan pengiriman Biteship yang masih aktif';

    public function handle(BiteshipService $biteship): int
    {
        if (! $biteship->isConfigured()) {
            $this->warn('Biteship belum dikonfigurasi; tidak ada yang disinkronkan.');

            return self::SUCCESS;
        }

        $threshold = now()->subMinutes(max(1, (int) $this->option('minutes')));

        $shipments = Shipment::query()
            ->whereNotNull('biteship_order_id')
            ->where(function ($query) use ($threshold) {
                $query->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<', $threshold);
            })
            ->orderBy('last_synced_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $synced = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($shipments as $shipment) {
            if ($shipment->shipment_status?->isFinal()) {
                $skipped++;

                continue;
            }

            try {
                $result = $biteship->sync($shipment);
                $result['ok'] ? $synced++ : $failed++;

                if (! $result['ok']) {
                    Log::warning('Gagal sinkron tracking '.$shipment->id.': '.$result['message']);
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Exception sinkron tracking '.$shipment->id.': '.$e->getMessage());
            }
        }

        $this->info("Sinkron tracking: {$synced} berhasil, {$failed} gagal, {$skipped} dilewati (final).");

        return self::SUCCESS;
    }
}
