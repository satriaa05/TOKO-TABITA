<?php

namespace App\Services;

/**
 * Perhitungan biaya pengiriman (ongkir).
 *
 * Mengambil tarif dari config/pos.php berdasarkan tujuan pengiriman.
 * Bila total belanja sudah mencapai batas gratis ongkir, biaya = 0.
 */
class ShippingService
{
    /**
     * Hitung ongkir untuk satu pesanan.
     *
     * @param  string|null  $zone  Kode wilayah tujuan (jakarta, jawa_barat, dst.)
     * @param  float  $orderTotal  Total belanja sebelum ongkir
     * @return array{
     *     cost: float,
     *     zone: string,
     *     is_free: bool,
     *     threshold: float,
     *     shortfall: float,
     * }
     */
    public function calculate(?string $zone, float $orderTotal): array
    {
        $config = config('pos.shipping', []);
        $zones = (array) ($config['zones'] ?? []);
        $default = (float) ($config['default'] ?? 0);
        $freeThreshold = (float) ($config['free_threshold'] ?? 0);

        $zoneKey = $zone !== null && array_key_exists($zone, $zones)
            ? $zone
            : 'default';

        $cost = (float) ($zones[$zoneKey] ?? $default);
        $isFree = $freeThreshold > 0 && $orderTotal >= $freeThreshold;

        if ($isFree) {
            $cost = 0.0;
        }

        return [
            'cost' => round($cost, 2),
            'zone' => $zoneKey,
            'is_free' => $isFree,
            'threshold' => $freeThreshold,
            'shortfall' => $isFree ? 0.0 : round(max(0, $freeThreshold - $orderTotal), 2),
        ];
    }

    /**
     * Daftar semua zona ongkir dengan label ramah.
     *
     * @return array<string, string>
     */
    public function zones(): array
    {
        return [
            'jakarta' => 'Jakarta',
            'jawa_barat' => 'Jawa Barat',
            'jawa_tengah' => 'Jawa Tengah',
            'jawa_timur' => 'Jawa Timur',
            'luar_jawa' => 'Luar Jawa',
            'default' => 'Lainnya',
        ];
    }
}
