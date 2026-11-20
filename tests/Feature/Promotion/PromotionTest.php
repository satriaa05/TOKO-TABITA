<?php

namespace Tests\Feature\Promotion;

use App\Enums\PromotionType;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use App\Services\PromotionService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    public function test_admin_dan_owner_bisa_membuka_halaman_promo(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.promotions.index'))->assertOk();

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.promotions.index'))->assertOk();
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.promotions.create'))->assertOk();
    }

    public function test_owner_dapat_membuat_promo_dan_harga_tercoret_di_katalog(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create([
            'name' => 'Produk Promo Owner',
            'sale_price' => 200000,
            'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('admin.promotions.store'), [
            'name' => 'Promo Spektakuler',
            'type' => PromotionType::Percent->value,
            'value' => 25,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'min_purchase' => 0,
            'is_active' => 1,
            'products' => [$product->id],
        ])->assertRedirect();

        $promo = Promotion::query()->where('name', 'Promo Spektakuler')->firstOrFail();
        $this->assertTrue($promo->products->contains('id', $product->id));

        $price = app(PromotionService::class)->priceMap(collect([$product]))[$product->id];
        $this->assertSame(200000.0, round($price['original'], 2));
        $this->assertSame(150000.0, round($price['price'], 2));
        $this->assertSame(50000.0, round($price['discount'], 2));

        $this->get(route('store.catalog'))
            ->assertOk()
            ->assertSee('Produk Promo Owner')
            ->assertSee('200.000', false)
            ->assertSee('150.000', false);
    }

    public function test_promo_otomatis_masuk_ke_keranjang_dan_checkout(): void
    {
        $product = Product::factory()->create([
            'name' => 'Produk Diskon Otomatis',
            'sale_price' => 100000,
            'is_active' => true,
        ]);

        $promo = Promotion::create([
            'name' => 'Diskon Otomatis',
            'type' => PromotionType::Fixed,
            'value' => 30000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'min_purchase' => 0,
            'is_active' => true,
        ]);
        $promo->products()->attach($product);

        $buyer = User::query()->firstOrCreate(
            ['email' => 'promo.buyer@tokotabita.test'],
            ['name' => 'Pembeli Promo', 'password' => bcrypt('password123'), 'role' => 'customer', 'is_active' => true]
        );

        $this->actingAs($buyer)->post('/keranjang/tambah', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $this->actingAs($buyer)->get('/keranjang')
            ->assertOk()
            ->assertSee('100.000', false)
            ->assertSee('70.000', false);

        $this->actingAs($buyer)->get('/checkout')
            ->assertOk()
            ->assertSee('Diskon promo')
            ->assertSee('30.000', false);
    }
}
