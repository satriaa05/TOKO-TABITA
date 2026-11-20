<?php

namespace Tests\Feature\Shipping;

use App\Models\Product;
use App\Models\User;
use App\Services\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config()->set('shipping.biteship.api_key', 'biteship_test_key');
        config()->set('pos.shipping.origin_postal_code', '50100');

        Http::fake([
            '*api.biteship.com/v1/rates/couriers' => Http::response([
                'success' => true,
                'pricing' => [
                    [
                        'courier_code' => 'jne',
                        'courier_name' => 'JNE',
                        'courier_service_code' => 'REG',
                        'courier_service_name' => 'Reguler',
                        'price' => 17000,
                        'shipment_duration_range' => '1 - 2',
                        'shipment_duration_unit' => 'days',
                    ],
                ],
            ]),
        ]);
    }

    public function test_cek_ongkir_mengembalikan_tarif_biteship(): void
    {
        $buyer = User::query()->where('role', 'customer')->first()
            ?? User::query()->create([
                'name' => 'Pembeli Ongkir',
                'email' => 'pembeli.ongkir@tokotabita.test',
                'password' => bcrypt('password123'),
                'role' => 'customer',
                'is_active' => true,
            ]);

        $product = Product::query()->where('is_active', true)->where('stock', '>', 0)->firstOrFail();
        $this->actingAs($buyer)->post('/keranjang/tambah', ['product_id' => $product->id, 'qty' => 1]);

        $this->actingAs($buyer)->postJson('/ongkir/cek', ['postal_code' => '50111'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('rates.0.price', 17000)
            ->assertJsonPath('rates.0.etd', '1-2 hari');

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/v1/rates/couriers')
                && $request['destination_postal_code'] === '50111'
                && $request['origin_postal_code'] === '50100';
        });
    }

    public function test_shipping_service_memakai_harga_biteship_untuk_kurir_terpilih(): void
    {
        $quote = app(ShippingService::class)->quote('jawa_tengah', 200000, '50111', 'jne', 'REG');

        $this->assertSame('biteship', $quote['source']);
        $this->assertEquals(17000, $quote['cost']);
        $this->assertSame('1-2 hari', $quote['eta']);
    }

    public function test_tanpa_api_key_memakai_tarif_manual(): void
    {
        config()->set('shipping.biteship.api_key', null);

        $quote = app(ShippingService::class)->quote('jawa_tengah', 200000, '50111', 'jne', 'REG');

        $this->assertSame('manual', $quote['source']);
        $this->assertSame('3-5 hari', $quote['eta']);
    }
}
