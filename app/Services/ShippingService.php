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
    public function __construct(private readonly BiteshipService $biteship) {}

    /**
     * Hitung ongkir: memakai tarif real-time Biteship bila tersedia,
     * otherwise memakai tarif manual dari config.
     *
     * @return array{
     *   cost: float, zone: string, is_free: bool, threshold: float,
     *   shortfall: float, eta: ?string, source: string,
     *   courier: ?string, service: ?string, message: ?string
     * }
     */
    public function quote(
        ?string $zone,
        float $orderTotal,
        ?string $postalCode = null,
        ?string $courier = null,
        ?string $service = null
    ): array {
        $base = $this->calculate($zone, $orderTotal);

        $result = [
            'cost' => $base['cost'],
            'zone' => $base['zone'],
            'is_free' => $base['is_free'],
            'threshold' => $base['threshold'],
            'shortfall' => $base['shortfall'],
            'eta' => null,
            'source' => 'manual',
            'courier' => $courier,
            'service' => $service,
            'message' => null,
        ];

        if ($postalCode && $courier && $service && $this->biteship->isConfigured()) {
            $rates = $this->biteship->rates($postalCode, $orderTotal);
            $result['message'] = $rates['message'];

            foreach ($rates['rates'] as $rate) {
                if (strtolower((string) $rate['courier']) === strtolower((string) $courier)
                    && strtolower((string) $rate['service']) === strtolower((string) $service)) {
                    $result['cost'] = (float) $rate['price'];
                    $result['eta'] = $rate['etd'];
                    $result['source'] = 'biteship';
                    break;
                }
            }
        }

        $maxCost = (float) config('pos.shipping.max_cost', 17000);

        if ($result['cost'] > $maxCost) {
            $result['cost'] = $maxCost;
            if ($result['source'] === 'biteship') {
                $result['message'] .= ' Tarif provider dibatasi maksimal '.rupiah($maxCost).' (selisih ditanggung toko).';
            }
        }

        if ($result['is_free']) {
            $result['cost'] = 0.0;
        }

        return $result;
    }

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
