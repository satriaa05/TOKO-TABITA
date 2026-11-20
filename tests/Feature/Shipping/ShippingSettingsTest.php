<?php

namespace Tests\Feature\Shipping;

use App\Models\Product;
use App\Models\User;
use App\Services\ShippingService;
use App\Services\StoreProfile;
use Database\Seeders\PaymentChannelSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentChannelSeeder::class);
        $this->seed(PermissionSeeder::class);
        config()->set('shipping.biteship.api_key', null);
    }

    public function test_owner_bisa_mengatur_ongkir_dan_estimasi_dipakai_saat_quote(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->put(route('owner.settings.update'), $this->settingsPayload([
            'shipping_default_cost' => 10000,
            'shipping_max_cost' => 50000,
            'shipping_free_threshold' => 500000,
            'shipping_zones' => json_encode(['jawa_tengah' => 12000]),
            'shipping_eta_zones' => json_encode(['jawa_tengah' => '2-4 hari', 'default' => '5-7 hari']),
            'shipping_eta_instant' => '45-90 menit',
        ]))->assertRedirect(route('owner.settings.index'));

        app()->forgetInstance(StoreProfile::class);

        $quote = app(ShippingService::class)->quote('jawa_tengah', 100000);
        $this->assertSame(12000.0, round($quote['cost'], 2));
        $this->assertSame('2-4 hari', $quote['eta']);

        $instant = app(ShippingService::class)->quote('jawa_tengah', 100000, null, 'grab', 'instant');
        $this->assertSame('45-90 menit', $instant['eta']);
    }

    public function test_checkout_tidak_menampilkan_cek_ongkir_tapi_menampilkan_estimasi(): void
    {
        $product = Product::factory()->create([
            'name' => 'Produk Ongkir Otomatis',
            'sale_price' => 100000,
            'stock' => 10,
            'is_active' => true,
        ]);
        $buyer = User::query()->firstOrCreate(
            ['email' => 'ongkir.buyer@tokotabita.test'],
            ['name' => 'Pembeli Ongkir', 'password' => bcrypt('password123'), 'role' => 'customer', 'is_active' => true]
        );

        $this->actingAs($buyer)->post('/keranjang/tambah', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $this->actingAs($buyer)->get('/checkout')
            ->assertOk()
            ->assertDontSee('Cek ongkir real-time')
            ->assertDontSee('Cek Ongkir')
            ->assertSee('Estimasi tiba')
            ->assertSee('Ongkir dihitung otomatis');
    }

    /** @return array<string, mixed> */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'store_name' => 'Tabita Modiste',
            'store_tagline' => 'Fashion for Every You',
            'store_description' => 'Garment premium.',
            'store_whatsapp' => '+62 831-2881-4776',
            'store_instagram_url' => 'https://www.instagram.com/tabitamodiste',
            'store_location' => 'Semarang, Jawa Tengah, Indonesia',
            'store_address' => 'Jl. Pandanaran No. 10, Semarang',
            'store_operating_hours' => 'Senin - Sabtu, 09.00 - 17.00 WIB',
            'payment_methods' => ['transfer'],
        ], $overrides);
    }
}
