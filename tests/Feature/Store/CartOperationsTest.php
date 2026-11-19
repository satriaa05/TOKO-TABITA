<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_hapus_satu_baris_tidak_menghapus_baris_lain(): void
    {
        $buyer = $this->buyer();
        [$a, $b] = $this->products();

        $this->actingAs($buyer)->post('/keranjang/tambah', [
            'product_id' => $a->id, 'qty' => 1,
        ])->assertSessionHasNoErrors();

        $this->actingAs($buyer)->post('/keranjang/tambah', [
            'product_id' => $b->id, 'qty' => 2,
        ])->assertSessionHasNoErrors();

        $items = app(\App\Services\PosCartService::class)->rawItems();
        $this->assertCount(2, $items);

        $keyA = collect($items)->first(fn ($item) => (int) $item['product_id'] === $a->id)['key'];

        $this->actingAs($buyer)->delete(route('store.cart.remove', $keyA))
            ->assertRedirect(route('store.cart'));

        $remaining = app(\App\Services\PosCartService::class)->rawItems();
        $this->assertCount(1, $remaining);
        $this->assertSame($b->id, (int) collect($remaining)->first()['product_id']);
        $this->assertSame(2, (int) collect($remaining)->first()['qty']);
    }

    public function test_tambah_produk_yang_sama_menambah_qty_bukan_duplikat_baris(): void
    {
        $buyer = $this->buyer();
        [$a] = $this->products();

        foreach ([1, 2, 3] as $qty) {
            $this->actingAs($buyer)->post('/keranjang/tambah', [
                'product_id' => $a->id, 'qty' => $qty,
            ])->assertSessionHasNoErrors();
        }

        $items = app(\App\Services\PosCartService::class)->rawItems();
        $this->assertCount(1, $items);
        $this->assertSame(6, (int) collect($items)->first()['qty']);
    }

    public function test_ubah_qty_satu_baris_berhasil(): void
    {
        $buyer = $this->buyer();
        [$a, $b] = $this->products();

        $this->actingAs($buyer)->post('/keranjang/tambah', [
            'product_id' => $a->id, 'qty' => 1,
        ]);
        $this->actingAs($buyer)->post('/keranjang/tambah', [
            'product_id' => $b->id, 'qty' => 1,
        ]);

        $items = app(\App\Services\PosCartService::class)->rawItems();
        $keyB = collect($items)->first(fn ($item) => (int) $item['product_id'] === $b->id)['key'];

        $this->actingAs($buyer)->patch(route('store.cart.update', $keyB), ['qty' => 4])
            ->assertRedirect(route('store.cart'));

        $remaining = app(\App\Services\PosCartService::class)->rawItems();
        $this->assertSame(4, (int) collect($remaining)->first(fn ($i) => (int) $i['product_id'] === $b->id)['qty']);
        $this->assertSame(1, (int) collect($remaining)->first(fn ($i) => (int) $i['product_id'] === $a->id)['qty']);
    }

    public function test_baris_session_lama_dengan_kunci_numerik_tetap_bisa_dihapus(): void
    {
        $buyer = $this->buyer();
        [$a] = $this->products();
        $key = 'null~null~'.$a->id;

        $this->actingAs($buyer)->withSession([
            'pos_cart' => [
                'user_id' => $buyer->id,
                'items' => [
                    0 => ['key' => $key, 'product_id' => $a->id, 'size' => null, 'color' => null, 'qty' => 1],
                ],
            ],
        ])->delete(route('store.cart.remove', $key))->assertRedirect(route('store.cart'));

        $this->assertSame([], app(\App\Services\PosCartService::class)->rawItems());
    }

    /** @return array{Product, Product} */
    private function products(): array
    {
        $category = Category::query()->firstOrFail();

        $a = Product::create([
            'barcode' => 'TB-CART-A', 'name' => 'Produk Keranjang A',
            'category_id' => $category->id, 'unit' => 'pcs',
            'cost_price' => 10000, 'sale_price' => 20000,
            'stock' => 50, 'min_stock' => 1, 'is_active' => true,
        ]);
        $b = Product::create([
            'barcode' => 'TB-CART-B', 'name' => 'Produk Keranjang B',
            'category_id' => $category->id, 'unit' => 'pcs',
            'cost_price' => 15000, 'sale_price' => 30000,
            'stock' => 50, 'min_stock' => 1, 'is_active' => true,
        ]);

        return [$a, $b];
    }

    private function buyer(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'cart.ops@tokotabita.test'],
            [
                'name' => 'Pembeli Cart', 'password' => bcrypt('password123'),
                'role' => 'customer', 'is_active' => true, 'email_verified_at' => now(),
            ]
        );
    }
}
