<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Shipment;
use App\Models\ShipmentLocation;
use App\Models\ShipmentTrackingEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Integrasi Biteship untuk ongkir, pembuatan order, dan tracking.
 *
 * Jika API belum dikonfigurasi, method akan melempar exception yang jelas.
 * Tidak ada status/lokasi yang dikarang agar tampilan tetap "terlihat jalan".
 */
class BiteshipService
{
    public function __construct(
        private readonly ShipmentNotificationService $notifications,
        private readonly MapService $maps,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('shipping.biteship.api_key'));
    }

    /**
     * Ambil tarif ongkir real-time dari Biteship Rates API.
     *
     * @return array{ok: bool, rates: array<int, array<string, mixed>>, message: string}
     */
    public function rates(string $destinationPostal, float $orderTotal = 0, int $weight = 1000): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'rates' => [], 'message' => 'Biteship belum dikonfigurasi. Ongkir memakai tarif manual toko.'];
        }

        $destination = preg_replace('/\D+/', '', $destinationPostal);

        if (strlen($destination) < 5) {
            return ['ok' => false, 'rates' => [], 'message' => 'Kode pos tujuan belum diisi dengan benar.'];
        }

        $couriers = implode(',', array_keys((array) config('shipping.couriers', [])));

        try {
            $response = $this->request()->post('/v1/rates/couriers', [
                'origin_postal_code' => (string) config('pos.shipping.origin_postal_code', '50100'),
                'destination_postal_code' => $destination,
                'couriers' => $couriers,
                'items' => [[
                    'name' => 'Paket Tabita Modiste',
                    'value' => (int) round($orderTotal),
                    'weight' => max(1, $weight),
                    'quantity' => 1,
                ]],
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'rates' => [], 'message' => 'Gagal menghubungi Biteship: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            $code = $response->json('error_code') ?? $response->status();

            return ['ok' => false, 'rates' => [], 'message' => 'Biteship menolak permintaan ongkir ('.$code.').'];
        }

        $json = (array) $response->json();
        $rates = [];
        $pricing = (array) ($json['pricing'] ?? []);
        $mappedPostal = preg_replace('/\D+/', '', (string) ($json['destination']['postal_code'] ?? ''));

        $maxCost = (float) config('pos.shipping.max_cost', 17000);
        $freeThreshold = (float) config('pos.shipping.free_threshold', 300000);
        $catalog = (array) config('shipping.couriers', []);
        $nearby = null; // dihitung hanya jika ada kurir instant

        foreach ($pricing as $row) {
            if (! is_array($row)) {
                continue;
            }

            $courier = strtolower((string) ($row['courier_code'] ?? ''));
            $service = strtolower((string) ($row['courier_service_code'] ?? ''));

            // Hanya tampilkan layanan yang dikonfigurasi (Reguler / Instan).
            if (! isset($catalog[$courier]['services'][$service])) {
                continue;
            }

            // Grab Instan hanya untuk tujuan dekat lokasi produksi.
            if (! empty($catalog[$courier]['instant'])) {
                if ($nearby === null) {
                    $nearby = $this->isDestinationNearby($destination);
                }
                if (! $nearby) {
                    continue;
                }
            }

            $rates[] = [
                'courier' => $courier,
                'courier_name' => $catalog[$courier]['name'] ?? strtoupper($courier),
                'service' => $service,
                'service_name' => $catalog[$courier]['services'][$service],
                'price' => $orderTotal >= $freeThreshold ? 0.0 : min((float) ($row['price'] ?? 0), $maxCost),
                'original_price' => (float) ($row['price'] ?? 0),
                'etd' => $this->etdLabel($row),
            ];
        }

        $message = 'Tarif real-time dari Biteship (maks '.rupiah($maxCost).', gratis minimal '.rupiah($freeThreshold).').';
        if ($mappedPostal !== '' && $mappedPostal !== $destination) {
            $message .= ' Perhatian: Biteship membaca kode pos tujuan sebagai '.$mappedPostal.'; periksa kembali kode pos Anda.';
        }
        if ($rates === []) {
            $message = 'Tidak ada layanan yang tersedia untuk kode pos ini.';
        } elseif ($nearby === false) {
            $message .= ' Grab Instan tidak ditampilkan karena lokasi tujuan di luar radius '.config('shipping.instant_max_km', 30).' km.';
        }

        return [
            'ok' => $rates !== [],
            'rates' => $rates,
            'message' => $message,
        ];
    }

    private function isDestinationNearby(string $destinationPostal): bool
    {
        $origin = (string) config('pos.shipping.origin_postal_code', '50131');
        $maxKm = (float) config('shipping.instant_max_km', 30);

        if ($this->maps->isConfigured()) {
            $originText = trim(store_profile()->location().' '.$origin.' Indonesia');
            $destinationText = trim($destinationPostal.', Indonesia');

            $from = $this->maps->geocode($originText);
            $to = $this->maps->geocode($destinationText);

            if ($from && $to) {
                return $this->distanceKm($from, $to) <= $maxKm;
            }
        }

        // Fallback bila geocoding gagal: prefix kode pos berbeda = dianggap jauh.
        return substr(preg_replace('/\D+/', '', $origin), 0, 2)
            === substr(preg_replace('/\D+/', '', $destinationPostal), 0, 2);
    }

    /** @param array{lat: float, lng: float} $a @param array{lat: float, lng: float} $b */
    private function distanceKm(array $a, array $b): float
    {
        $lat1 = deg2rad($a['lat']);
        $lat2 = deg2rad($b['lat']);
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLng = deg2rad($b['lng'] - $a['lng']);

        $h = sin($dLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($h), sqrt(1 - $h));
    }

    /** @param  array<string, mixed>  $row */
    private function etdLabel(array $row): string
    {
        if (! empty($row['etd'])) {
            return (string) $row['etd'];
        }

        $range = $row['shipment_duration_range'] ?? $row['duration'] ?? null;
        $unit = (string) ($row['shipment_duration_unit'] ?? 'hari');
        $unit = str_replace(['days', 'day'], ['hari', 'hari'], $unit);

        if (is_array($range)) {
            $min = $range[0] ?? null;
            $max = $range[1] ?? ($range[0] ?? null);

            if ($min !== null) {
                return $min === $max ? $min.' '.$unit : $min.'-'.$max.' '.$unit;
            }
        }

        if (is_string($range) && $range !== '') {
            // Biteship mengirim misalnya "1 - 2" dengan unit "days".
            if (str_contains($range, strtolower($unit)) || str_contains($range, 'hari')) {
                return $range;
            }

            return str_replace(' - ', '-', trim($range)).' '.$unit;
        }

        return 'Estimasi tidak tersedia';
    }

    /**
     * Buat order pengiriman di Biteship setelah Admin menginput kurir & resi.
     *
     * @return array{order_id: string, waybill_id: ?string}
     */
    public function createOrder(Shipment $shipment): array
    {
        $this->assertConfigured();

        $sale = $shipment->sale()->with('user')->firstOrFail();
        $store = store_profile();
        $catalog = (array) config('shipping.couriers', []);

        $originPostal = preg_replace('/\D+/', '', (string) config('pos.shipping.origin_postal_code', '50131'));
        $destinationPostal = preg_replace('/\D+/', '', (string) ($sale->buyer_postal_code ?: $sale->buyer_address));
        $destinationPostal = strlen($destinationPostal) >= 5 ? $destinationPostal : $originPostal;

        $payload = [
            'origin_contact_name' => $store->name(),
            'origin_contact_phone' => $store->whatsappNumber() ?? '6280000000000',
            'origin_address' => $store->address() ?: $store->location(),
            'origin_postal_code' => (int) $originPostal,
            'origin_note' => 'Pengirim Tabita Modiste',
            'destination_contact_name' => $sale->buyer_name,
            'destination_contact_phone' => $sale->buyer_phone ?: '6280000000000',
            'destination_address' => $sale->buyer_address,
            'destination_postal_code' => (int) $destinationPostal,
            'courier_company' => strtolower((string) $shipment->courier),
            'courier_type' => strtolower((string) $shipment->service),
            'delivery_type' => 'now',
            'reference_id' => 'TABITA-'.$sale->id.'-'.$shipment->id,
            'metadata' => ['invoice' => $sale->invoice_number],
            'items' => $sale->items->map(fn ($item) => [
                'name' => $item->product_name,
                'description' => trim(($item->color ? 'Warna '.$item->color.' ' : '').($item->size ? 'Ukuran '.$item->size : '')),
                'category' => 'fashion',
                'value' => (int) round((float) $item->price),
                'weight' => 500,
                'quantity' => (int) $item->qty,
            ])->values()->all(),
        ];

        // Grab Instan membutuhkan koordinat asli (Biteship tidak menerima kode pos untuk instant).
        if (! empty($catalog[$shipment->courier]['instant'])) {
            $from = $this->maps->geocode(trim(store_profile()->location().' '.$originPostal.' Indonesia'));
            $to = $this->maps->geocode(trim($sale->buyer_address.' '.$destinationPostal));

            if (! $from || ! $to) {
                throw new PaymentGatewayException('Lokasi asal/tujuan belum bisa ditentukan oleh layanan peta gratis. Periksa kembali alamat pembeli.');
            }

            $payload['origin_coordinate'] = $from;
            $payload['destination_coordinate'] = $to;
            unset($payload['origin_postal_code'], $payload['destination_postal_code']);
        }

        $response = $this->request()->post('/v1/orders', $payload);

        if (! $response->successful()) {
            $error = $response->json('error') ?? ('HTTP '.$response->status());

            throw new PaymentGatewayException('Biteship menolak pembuatan order: '.$error);
        }

        $json = (array) $response->json();
        $courierData = (array) ($json['courier'] ?? []);

        $shipment->update([
            'biteship_order_id' => $json['id'] ?? null,
            'biteship_tracking_id' => $courierData['tracking_id'] ?? null,
            'tracking_number' => $shipment->tracking_number ?: ($courierData['waybill_id'] ?? null),
            'tracking_mode' => ! empty($courierData['link']) ? 'live' : 'scan',
            'live_tracking_url' => $courierData['link'] ?? null,
        ]);

        $this->saveCoordinates($shipment, $json);

        return [
            'order_id' => (string) ($json['id'] ?? ''),
            'waybill_id' => isset($courierData['waybill_id']) ? (string) $courierData['waybill_id'] : null,
        ];
    }

    /**
     * Tarik status terbaru dari Biteship lalu simpan sebagai event tracking.
     *
     * @return array{ok: bool, status: ?ShipmentStatus, message: string}
     */
    public function sync(Shipment $shipment): array
    {
        $this->assertConfigured();

        if (! $shipment->biteship_order_id) {
            $this->createOrder($shipment);
            $shipment->refresh();
        }

        $response = $this->request()->get('/v1/orders/'.$shipment->biteship_order_id);

        if (! $response->successful()) {
            return ['ok' => false, 'status' => null, 'message' => 'Gagal mengambil tracking: '.($response->json('message') ?? 'HTTP '.$response->status())];
        }

        $json = (array) $response->json();
        $rawStatus = (string) ($json['status'] ?? '');
        $status = ShipmentStatus::fromBiteship($rawStatus);
        $courier = (array) ($json['courier'] ?? []);
        $oldStatus = $shipment->shipment_status;

        $shipment->update([
            'shipment_status' => $status->value,
            'tracking_number' => $courier['waybill_id'] ?? $shipment->tracking_number,
            'tracking_mode' => $this->isInstant($shipment->courier) && ! empty($courier['link']) ? 'live' : 'scan',
            'live_tracking_url' => $courier['link'] ?? null,
            'driver_name' => $courier['name'] ?? null,
            'driver_plate' => $courier['driver_plate_number'] ?? null,
            'estimated_delivery_start' => $this->parseDate($json['delivery']['datetime'] ?? null),
            'estimated_delivery_end' => $this->parseDate($json['delivery']['datetime'] ?? null),
            'last_scan_location' => $this->lastNote((array) ($courier['history'] ?? [])),
            'last_scan_at' => $this->lastTime((array) ($courier['history'] ?? [])),
            'last_synced_at' => now(),
        ]);

        foreach ((array) ($courier['history'] ?? []) as $event) {
            if (! is_array($event) || empty($event['status'])) {
                continue;
            }

            $eventTime = $this->parseDate($event['updated_at'] ?? null) ?? now();
            $location = isset($event['location']) ? trim((string) $event['location']) : '';
            $latitude = $this->validLat($event['latitude'] ?? null);
            $longitude = $this->validLng($event['longitude'] ?? null);
            $locationSource = $latitude !== null && $longitude !== null ? 'provider_scan' : null;

            // Biteship reguler sering mengirim nama kota tanpa koordinat.
            // Geocode nama scan tersebut dengan Nominatim agar peta menunjukkan
            // titik scan nyata. Ini BUKAN GPS kurir live.
            if (($latitude === null || $longitude === null) && $location !== '') {
                $geo = $this->maps->geocode($location.', Indonesia');

                if ($geo !== null) {
                    $latitude = $geo['lat'];
                    $longitude = $geo['lng'];
                    $locationSource = 'scan_geocoded';
                }
            }

            ShipmentTrackingEvent::updateOrCreate(
                [
                    'shipment_id' => $shipment->id,
                    'raw_status' => (string) $event['status'],
                    'event_time' => $eventTime,
                    'source' => 'provider',
                ],
                [
                    'normalized_status' => ShipmentStatus::fromBiteship((string) $event['status'])->value,
                    'description' => $event['note'] ?? null,
                    'location' => $location !== '' ? $location : null,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]
            );

            if ($latitude !== null && $longitude !== null && $locationSource !== null) {
                ShipmentLocation::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'recorded_at' => $eventTime,
                    ],
                    [
                        'location_description' => $location !== '' ? $location : 'Titik scan pengiriman',
                        'source' => $locationSource,
                    ]
                );
            }
        }

        // Posisi "scan terakhir" mengikuti event terakhir yang punya koordinat.
        $lastLocated = ShipmentTrackingEvent::query()
            ->where('shipment_id', $shipment->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest('event_time')
            ->latest('id')
            ->first();

        if ($lastLocated !== null) {
            $shipment->update([
                'last_scan_latitude' => $lastLocated->latitude,
                'last_scan_longitude' => $lastLocated->longitude,
                'last_scan_location' => $lastLocated->location ?: ($lastLocated->description ?: $shipment->last_scan_location),
            ]);
        }

        $this->saveCoordinates($shipment, $json);

        if ($oldStatus !== $status) {
            $this->notifications->notify($shipment, $status, 'biteship');
        }

        return ['ok' => true, 'status' => $status, 'message' => 'Tracking berhasil disinkronisasi.'];
    }

    public function verifyWebhook(Request $request): bool
    {
        $header = (string) config('shipping.biteship.webhook_header');
        $token = (string) config('shipping.biteship.webhook_token');

        if ($token === '') {
            return false;
        }

        return hash_equals($token, (string) $request->header($header, ''));
    }

    /**
     * Proses webhook Biteship. Event utama: order.status, order.waybill_id,
     * order.price. Semua perubahan tetap tercatat sebagai tracking event.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): array
    {
        $reference = (string) ($payload['courier_tracking_id'] ?? $payload['order_id'] ?? '');

        $shipment = Shipment::query()
            ->where('biteship_tracking_id', $reference)
            ->orWhere('biteship_order_id', $reference)
            ->first();

        if (! $shipment) {
            return ['ok' => false, 'message' => 'Shipment tidak ditemukan.'];
        }

        $event = (string) ($payload['event'] ?? '');

        if ($event === 'order.waybill_id' && ! empty($payload['courier_waybill_id'])) {
            $shipment->update(['tracking_number' => (string) $payload['courier_waybill_id']]);
        }

        if ($event === 'order.price' && isset($payload['order_price'])) {
            $shipment->update(['shipping_cost' => (float) $payload['order_price']]);
        }

        if ($event === 'order.status' || $event === 'order.waybill_id') {
            try {
                $result = $this->sync($shipment);

                return ['ok' => $result['ok'], 'message' => $result['message']];
            } catch (\Throwable $e) {
                return ['ok' => false, 'message' => $e->getMessage()];
            }
        }

        return ['ok' => true, 'message' => 'Event diterima.'];
    }

    private function isInstant(string $courier): bool
    {
        return in_array(strtolower($courier), array_map('strtolower', (array) config('shipping.instant_couriers', [])), true);
    }

    private function request()
    {
        return Http::withToken((string) config('shipping.biteship.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->baseUrl(rtrim((string) config('shipping.biteship.api_url'), '/'));
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $json */
    private function saveCoordinates(Shipment $shipment, array $json): void
    {
        $points = [
            'origin' => (array) ($json['origin']['coordinate'] ?? []),
            'destination' => (array) ($json['destination']['coordinate'] ?? []),
        ];

        foreach ($points as $label => $coordinate) {
            $lat = $this->validLat($coordinate['latitude'] ?? null);
            $lng = $this->validLng($coordinate['longitude'] ?? null);

            if ($lat === null || $lng === null) {
                continue;
            }

            ShipmentLocation::firstOrCreate(
                [
                    'shipment_id' => $shipment->id,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'recorded_at' => $shipment->created_at ?? now(),
                ],
                [
                    'location_description' => $label === 'origin' ? 'Lokasi asal pengiriman' : 'Lokasi tujuan pengiriman',
                    'source' => 'biteship',
                ]
            );
        }
    }

    private function validLat(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value >= -90 && (float) $value <= 90 ? (float) $value : null;
    }

    private function validLng(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value >= -180 && (float) $value <= 180 ? (float) $value : null;
    }

    /** @param  array<int, mixed>  $history */
    private function lastNote(array $history): ?string
    {
        $last = end($history);

        return is_array($last) && isset($last['note']) ? (string) $last['note'] : null;
    }

    /** @param  array<int, mixed>  $history */
    private function lastTime(array $history): ?Carbon
    {
        $last = end($history);

        return is_array($last) ? $this->parseDate($last['updated_at'] ?? null) : null;
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayException(
                'API Biteship belum dikonfigurasi. Isi BITESHIP_API_KEY di .env terlebih dahulu.'
            );
        }
    }
}
