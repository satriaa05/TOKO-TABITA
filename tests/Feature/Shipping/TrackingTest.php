<?php

namespace Tests\Feature\Shipping;

use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Models\CustomerNotification;
use App\Models\Sale;
use App\Models\Shipment;
use App\Models\ShipmentLocation;
use App\Models\ShipmentTrackingEvent;
use App\Models\User;
use App\Services\BiteshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config()->set('shipping.maps.js_api_key', null);
        config()->set('shipping.maps.server_api_key', null);
        config()->set('shipping.biteship.api_key', null);
        config()->set('shipping.biteship.webhook_token', 'rahasia-webhook');
    }

    public function test_admin_membuat_pengiriman_dan_memperbarui_status(): void
    {
        $sale = $this->makePaidSale();
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.shipments.store', $sale), [
            'courier' => 'jne',
            'service' => 'REG',
            'tracking_number' => 'JNE123456',
        ])->assertRedirect();

        $shipment = Shipment::query()->where('sale_id', $sale->id)->firstOrFail();
        $this->assertSame('jne', $shipment->courier);
        $this->assertSame(ShipmentStatus::Pending, $shipment->fresh()->shipment_status);
        $this->assertSame(FulfillmentStatus::Processing, $sale->fresh()->fulfillment_status);

        $this->actingAs($admin)->post(route('admin.shipments.status', $shipment), [
            'status' => ShipmentStatus::InTransit->value,
            'note' => 'Paket berangkat dari gudang',
            'location' => 'Gudang JNE Semarang',
            'latitude' => -6.9,
            'longitude' => 110.4,
        ])->assertRedirect();

        $this->assertSame(ShipmentStatus::InTransit, $shipment->fresh()->shipment_status);
        $this->assertSame(FulfillmentStatus::Shipped, $sale->fresh()->fulfillment_status);
        $this->assertSame(1, ShipmentTrackingEvent::query()->where('shipment_id', $shipment->id)->count());

        $event = ShipmentTrackingEvent::query()->where('shipment_id', $shipment->id)->firstOrFail();
        $this->assertSame('Gudang JNE Semarang', $event->location);
        $this->assertTrue($event->hasCoordinate());
        $this->assertSame('Gudang JNE Semarang', $shipment->fresh()->last_scan_location);

        $this->assertSame(1, CustomerNotification::query()->where('sale_id', $sale->id)->count());
        $this->assertSame(
            NotificationType::OrderInTransit,
            CustomerNotification::query()->where('sale_id', $sale->id)->firstOrFail()->type
        );
    }

    public function test_pelanggan_melihat_halaman_lacak_dan_tidak_bisa_melihat_punya_orang_lain(): void
    {
        $sale = $this->makePaidSale();
        $shipment = Shipment::create([
            'sale_id' => $sale->id,
            'courier' => 'jnt',
            'service' => 'EZ',
            'tracking_number' => 'JNT999',
            'shipment_status' => ShipmentStatus::InTransit->value,
        ]);

        ShipmentTrackingEvent::create([
            'shipment_id' => $shipment->id,
            'raw_status' => 'inTransit',
            'normalized_status' => ShipmentStatus::InTransit->value,
            'description' => 'Paket dalam perjalanan',
            'event_time' => now(),
            'source' => 'provider',
        ]);

        $owner = $sale->user;

        $this->actingAs($owner)->get(route('store.orders.tracking', $sale))
            ->assertOk()
            ->assertSee('Lacak Pengiriman')
            ->assertSee('JNT999')
            ->assertSee('Paket Dalam Perjalanan')
            ->assertSee('Peta oleh OpenStreetMap contributors');

        $other = User::query()->create([
            'name' => 'Orang Lain',
            'email' => 'orang.lain@tokotabita.test',
            'password' => bcrypt('password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->actingAs($other)->get(route('store.orders.tracking', $sale))->assertForbidden();
    }

    public function test_partial_tracking_bisa_di_refresh_dan_terbatas_pemilik(): void
    {
        $sale = $this->makePaidSale();
        $shipment = Shipment::create([
            'sale_id' => $sale->id,
            'courier' => 'jne',
            'tracking_number' => 'JNE-REFRESH',
            'shipment_status' => ShipmentStatus::InTransit->value,
            'last_scan_location' => 'Gudang Semarang',
        ]);
        ShipmentTrackingEvent::create([
            'shipment_id' => $shipment->id,
            'raw_status' => 'inTransit',
            'normalized_status' => ShipmentStatus::InTransit->value,
            'location' => 'Gudang Semarang',
            'event_time' => now(),
            'source' => 'provider',
        ]);

        $this->actingAs($sale->user)->get(route('store.orders.tracking.partial', $sale))
            ->assertOk()
            ->assertSee('JNE-REFRESH')
            ->assertSee('Gudang Semarang')
            ->assertSee('Perjalanan Paket');

        $other = User::query()->create([
            'name' => 'Pembeli Lain',
            'email' => 'pembeli.partial@tokotabita.test',
            'password' => bcrypt('password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->actingAs($other)->get(route('store.orders.tracking.partial', $sale))->assertForbidden();
    }

    public function test_scan_nama_kota_tanpa_koordinat_digeocode_ke_peta(): void
    {
        config()->set('shipping.biteship.api_key', 'test-key');

        Http::fake([
            'https://api.biteship.com/v1/orders/*' => Http::response([
                'id' => 'BS-1',
                'status' => 'in_transit',
                'courier' => [
                    'waybill_id' => 'WYB-GEO',
                    'history' => [
                        [
                            'status' => 'in_transit',
                            'note' => 'Item is on the way to destination',
                            'location' => 'Pekalongan',
                            'updated_at' => '2026-11-19 21:46:00',
                        ],
                    ],
                ],
            ], 200),
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                ['lat' => '-6.8900', 'lon' => '109.6700', 'display_name' => 'Pekalongan, Jawa Tengah, Indonesia'],
            ], 200),
        ]);

        $sale = $this->makePaidSale();
        $shipment = Shipment::create([
            'sale_id' => $sale->id,
            'courier' => 'jne',
            'service' => 'REG',
            'tracking_number' => 'WYB-GEO',
            'biteship_order_id' => 'BS-1',
            'biteship_tracking_id' => 'BS-1',
            'shipment_status' => ShipmentStatus::Pending->value,
        ]);

        $result = app(BiteshipService::class)->sync($shipment);

        $this->assertTrue($result['ok']);
        $event = ShipmentTrackingEvent::query()
            ->where('shipment_id', $shipment->id)
            ->where('raw_status', 'in_transit')
            ->firstOrFail();
        $this->assertSame(-6.89, round((float) $event->latitude, 4));
        $this->assertSame(109.67, round((float) $event->longitude, 4));

        $this->assertSame(1, ShipmentLocation::query()
            ->where('shipment_id', $shipment->id)
            ->where('source', 'scan_geocoded')
            ->where('location_description', 'Pekalongan')
            ->count());

        $this->assertSame('Pekalongan', $shipment->fresh()->last_scan_location);
        $this->assertNotNull($shipment->fresh()->last_scan_latitude);
    }

    public function test_webhook_biteship_signature_invalid_ditolak(): void
    {
        $this->postJson('/shipping/biteship/webhook', ['event' => 'order.status'], [
            'X-Tabita-Signature' => 'salah',
        ])->assertOk()->assertSee('OK');

        $this->postJson('/shipping/biteship/webhook', ['event' => 'order.status'], [
            'X-Tabita-Signature' => 'rahasia-webhook',
        ])->assertOk()->assertSee('OK');
    }

    private function makePaidSale(): Sale
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'pembeli.kirim@tokotabita.test'],
            ['name' => 'Pembeli Kirim', 'password' => bcrypt('password123'), 'role' => 'customer', 'is_active' => true]
        );

        $sale = Sale::create([
            'invoice_number' => 'INV-KIRIM-'.Str::upper(Str::random(8)),
            'user_id' => $user->id,
            'buyer_name' => $user->name,
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Kirim No. 2, Semarang',
            'subtotal' => 150000,
            'total' => 150000,
            'status' => 'completed',
        ]);

        $sale->forceFill([
            'payment_status' => PaymentStatus::Paid->value,
            'fulfillment_status' => FulfillmentStatus::Pending->value,
            'paid_at' => now(),
        ])->save();

        return $sale;
    }
}
