<?php

namespace Tests\Feature\Store;

use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\PaymentProof;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pengujian multi-user: isolasi keranjang, invoice, pesanan, stok, dan bukti
 * pembayaran. Dijalankan berurutan di SQLite in-memory, tetapi setiap skenario
 * memakai akun & session berbeda dan transaksi DB + lock yang sama dengan
 * kondisi produksi.
 */
class MultiUserCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_lima_pembeli_mendapatkan_keranjang_dan_pesanan_terpisah(): void
    {
        $invoices = [];
        $buyers = [];

        for ($i = 1; $i <= 5; $i++) {
            $buyer = $this->makeBuyer("multi{$i}@tokotabita.test", "Pembeli {$i}");
            [$product, $variant] = $this->makeProduct('Produk Pembeli '.$i, 10, 'M', 'Hitam '.$i);

            $this->actingAs($buyer)->post('/keranjang/tambah', [
                'product_id' => $product->id,
                'size' => 'M',
                'color' => 'Hitam '.$i,
                'qty' => $i,
            ])->assertSessionHasNoErrors();

            $this->actingAs($buyer)->post('/checkout', $this->payload([
                'buyer_name' => "Pembeli {$i}",
                'buyer_phone' => '0812345000'.$i,
                'buyer_address' => "Jl. Pembeli {$i}, Semarang",
                'idempotency_key' => 'idem-multi-'.$i,
            ]))->assertSessionHasNoErrors();

            $sale = Sale::query()->where('user_id', $buyer->id)->firstOrFail();
            $invoices[] = $sale->invoice_number;
            $buyers[$buyer->id] = $sale->id;

            $this->assertSame('Hitam '.$i, $sale->items->first()->color);
            $this->assertSame($i, (int) $sale->items->first()->qty);
            $this->assertEquals(10 - $i, $variant->fresh()->stock);
        }

        $this->assertCount(5, array_unique($invoices));
        $this->assertSame(5, Sale::query()->count());
        $this->assertSame(5, Sale::query()->distinct('invoice_number')->count('invoice_number'));

        foreach ($buyers as $userId => $saleId) {
            $this->assertSame($saleId, (int) Sale::query()->where('user_id', $userId)->value('id'));
        }
    }

    public function test_keranjang_tidak_tertukar_saat_akun_berganti_di_satu_browser(): void
    {
        $a = $this->makeBuyer('cart.a@tokotabita.test', 'Keranjang A');
        $b = $this->makeBuyer('cart.b@tokotabita.test', 'Keranjang B');
        [$product] = $this->makeProduct('Produk Keranjang A', 10, 'M', 'Hitam');

        $this->actingAs($a)->post('/keranjang/tambah', [
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Hitam',
            'qty' => 2,
        ])->assertSessionHasNoErrors();

        $this->actingAs($a)->get('/keranjang')->assertSee('Produk Keranjang A');

        $this->actingAs($b)->get('/keranjang')
            ->assertDontSee('Produk Keranjang A');

        $this->actingAs($a)->get('/keranjang')
            ->assertDontSee('Produk Keranjang A');
    }

    public function test_double_post_dengan_idempotency_key_sama_hanya_memotong_stok_satu_kali(): void
    {
        $buyer = $this->makeBuyer('idem@tokotabita.test', 'Idempoten');
        [$product, $variant] = $this->makeProduct('Produk Idempoten', 5, 'L', 'Putih');

        $this->actingAs($buyer)->post('/keranjang/tambah', [
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'Putih',
            'qty' => 2,
        ]);

        $payload = $this->payload(['idempotency_key' => 'idem-sama-123']);

        $this->actingAs($buyer)->post('/checkout', $payload)->assertSessionHasNoErrors();
        $this->actingAs($buyer)->post('/checkout', $payload)->assertSessionHasNoErrors();

        $this->assertSame(1, Sale::query()->where('user_id', $buyer->id)->count());
        $this->assertSame(3, (int) $variant->fresh()->stock);
        $this->assertSame(3, (int) $product->fresh()->stock);
    }

    public function test_stok_terakhir_tidak_bisa_dibeli_oleh_dua_pembeli(): void
    {
        $a = $this->makeBuyer('race.a@tokotabita.test', 'Race A');
        $b = $this->makeBuyer('race.b@tokotabita.test', 'Race B');
        [$product, $variant] = $this->makeProduct('Produk Stok Terakhir', 1, 'S', 'Cream');

        // Setiap pembeli memakai sesi terpisah: tambah + checkout A dulu,
        // lalu tambah + checkout B (tidak bergantian di satu sesi).
        $this->actingAs($a)->post('/keranjang/tambah', [
            'product_id' => $product->id,
            'size' => 'S',
            'color' => 'Cream',
            'qty' => 1,
        ]);

        $this->actingAs($a)->post('/checkout', $this->payload([
            'buyer_name' => 'Race A',
            'idempotency_key' => 'race-a',
        ]))->assertSessionHasNoErrors();

        $this->actingAs($b)->post('/keranjang/tambah', [
            'product_id' => $product->id,
            'size' => 'S',
            'color' => 'Cream',
            'qty' => 1,
        ]);

        $this->actingAs($b)->post('/checkout', $this->payload([
            'buyer_name' => 'Race B',
            'idempotency_key' => 'race-b',
        ]))->assertSessionHasErrors('cart');

        $this->assertSame(1, Sale::query()->count());
        $this->assertSame(0, (int) $variant->fresh()->stock);
        $this->assertSame(0, (int) $product->fresh()->stock);
    }

    public function test_pembayaran_expired_mengembalikan_stok_varian_hanya_satu_kali(): void
    {
        $buyer = $this->makeBuyer('expired@tokotabita.test', 'Expired');
        [$product, $variant] = $this->makeProduct('Produk Expired', 5, 'M', 'Navy');

        $this->actingAs($buyer)->post('/keranjang/tambah', [
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Navy',
            'qty' => 2,
        ]);
        $this->actingAs($buyer)->post('/checkout', $this->payload([
            'idempotency_key' => 'expired-1',
        ]))->assertSessionHasNoErrors();

        $sale = Sale::query()->where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame(3, (int) $variant->fresh()->stock);

        $transaction = PaymentTransaction::query()->where('sale_id', $sale->id)->firstOrFail();
        $payments = app(PaymentService::class);

        $this->assertTrue($payments->markExpired($transaction));
        $this->assertFalse($payments->markExpired($transaction));

        $this->assertSame(5, (int) $variant->fresh()->stock);
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertNotNull($sale->fresh()->stock_released_at);
    }

    public function test_bukti_pembayaran_melekat_pada_pesanan_pemiliknya(): void
    {
        $a = $this->makeBuyer('proof.a@tokotabita.test', 'Proof A');
        $b = $this->makeBuyer('proof.b@tokotabita.test', 'Proof B');

        foreach ([$a, $b] as $index => $buyer) {
            [$product] = $this->makeProduct('Produk Proof '.$index, 10, 'M', 'Hitam');
            $this->actingAs($buyer)->post('/keranjang/tambah', [
                'product_id' => $product->id,
                'size' => 'M',
                'color' => 'Hitam',
                'qty' => 1,
            ]);
            $this->actingAs($buyer)->post('/checkout', $this->payload([
                'buyer_name' => $buyer->name,
                'idempotency_key' => 'proof-'.$index,
            ]))->assertSessionHasNoErrors();
        }

        $saleA = Sale::query()->where('user_id', $a->id)->firstOrFail();
        $saleB = Sale::query()->where('user_id', $b->id)->firstOrFail();

        $this->actingAs($a)->post(route('store.orders.proof', $saleA), [
            'proof' => UploadedFile::fake()->image('bukti-a.jpg', 40, 40),
        ])->assertSessionHasNoErrors();

        $proof = PaymentProof::query()->firstOrFail();
        $this->assertSame($saleA->id, (int) $proof->sale_id);
        $this->assertSame($a->id, (int) $proof->uploaded_by);

        $this->actingAs($b)->get(route('store.orders.show', $saleA))->assertForbidden();
        $this->actingAs($b)->get(route('store.orders.show', $saleB))->assertOk();
    }

    public function test_pembeli_tidak_bisa_membuka_halaman_sukses_pesanan_orang_lain(): void
    {
        $owner = $this->makeBuyer('success.owner@tokotabita.test', 'Success Owner');
        [$product] = $this->makeProduct('Produk Success', 10, 'M', 'Hitam');

        $this->actingAs($owner)->post('/keranjang/tambah', [
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Hitam',
            'qty' => 1,
        ]);
        $this->actingAs($owner)->post('/checkout', $this->payload([
            'idempotency_key' => 'success-1',
        ]))->assertSessionHasNoErrors();

        $sale = Sale::query()->where('user_id', $owner->id)->firstOrFail();
        $stranger = $this->makeBuyer('success.stranger@tokotabita.test', 'Stranger');

        $this->actingAs($owner)->get(route('store.orderSuccess', $sale))->assertOk();
        $this->actingAs($stranger)->get(route('store.orderSuccess', $sale))->assertForbidden();
    }

    public function test_admin_dan_owner_melihat_semua_pesanan_tanpa_mencampurkan_data(): void
    {
        foreach ([1, 2] as $index) {
            $buyer = $this->makeBuyer("lihat{$index}@tokotabita.test", "Lihat {$index}");
            [$product] = $this->makeProduct('Produk Lihat '.$index, 10, 'M', 'Hitam');
            $this->actingAs($buyer)->post('/keranjang/tambah', [
                'product_id' => $product->id,
                'size' => 'M',
                'color' => 'Hitam',
                'qty' => 1,
            ]);
            $this->actingAs($buyer)->post('/checkout', $this->payload([
                'buyer_name' => "Lihat {$index}",
                'idempotency_key' => 'lihat-'.$index,
            ]))->assertSessionHasNoErrors();
        }

        $saleA = Sale::query()->where('buyer_name', 'Lihat 1')->firstOrFail();
        $saleB = Sale::query()->where('buyer_name', 'Lihat 2')->firstOrFail();

        $admin = $this->makeUser('admin@tokotabita.test', 'admin');
        $owner = $this->makeUser('owner@tokotabita.test', 'owner');

        $this->actingAs($admin)->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($saleA->invoice_number)
            ->assertSee($saleB->invoice_number);

        $this->actingAs($admin)->get(route('admin.orders.show', $saleA))
            ->assertOk()
            ->assertSee('Lihat 1')
            ->assertDontSee('Jl. Lihat 2, Semarang');

        $this->actingAs($owner)->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee($saleA->invoice_number)
            ->assertSee($saleB->invoice_number);
    }

    /** @return array{Product, ProductVariant} */
    private function makeProduct(string $name, int $stock, string $size, string $color): array
    {
        $category = Category::query()->firstOrFail();
        $suffix = Str::lower(Str::random(6));

        $product = Product::create([
            'barcode' => 'TB-MU-'.strtoupper($suffix),
            'name' => $name,
            'category_id' => $category->id,
            'unit' => 'pcs',
            'cost_price' => 50000,
            'sale_price' => 100000,
            'stock' => $stock,
            'min_stock' => 2,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => $size,
            'color' => $color,
            'stock' => $stock,
        ]);

        return [$product, $variant];
    }

    /** @return array<string, mixed> */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'buyer_name' => 'Pembeli Multi',
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Multi, Semarang',
            'shipping_zone' => 'jawa_tengah',
            'courier' => 'jne',
            'service' => 'REG',
            'payment_method' => 'transfer',
            'idempotency_key' => Str::random(40),
        ], $extra);
    }

    private function makeBuyer(string $email, string $name): \App\Models\User
    {
        return $this->makeUser($email, 'customer', $name);
    }

    private function makeUser(string $email, string $role, ?string $name = null): \App\Models\User
    {
        $existing = \App\Models\User::query()->where('email', $email)->first();

        if ($existing) {
            return $existing;
        }

        return \App\Models\User::query()->create([
            'name' => $name ?? ucfirst($role),
            'email' => $email,
            'password' => bcrypt('password123'),
            'role' => $role,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
