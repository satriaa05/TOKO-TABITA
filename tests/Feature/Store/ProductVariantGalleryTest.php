<?php

namespace Tests\Feature\Store;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Galeri foto per warna + CRUD varian + sinkronisasi ke transaksi.
 */
class ProductVariantGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_produk_dengan_empat_warna_menampilkan_swatch_dan_galeri_per_warna(): void
    {
        [$product, $colors] = $this->makeProductWithFourColors();

        $response = $this->get(route('store.product', $product));

        $response->assertOk()
            ->assertSee('data-color="Hitam"', false)
            ->assertSee('data-color="Putih"', false)
            ->assertSee('data-color="Cream"', false)
            ->assertSee('data-color="Navy"', false)
            ->assertSee('#111111', false)
            ->assertSee('st-gallery-counter', false)
            ->assertSee('gallery-prev', false)
            ->assertSee('gallery-next', false);

        foreach ($colors as $color) {
            $photos = $product->photosForColor($color->name, (int) $color->id);
            $this->assertCount(2, $photos, 'Foto warna '.$color->name.' harus 2.');
            $this->assertTrue($photos->every(fn (Media $m) => $m->color === $color->name));
        }

        $this->assertCount(2, $product->photosForColor('Putih'));
        $this->assertNotSame(
            $product->photosForColor('Hitam')->first()->id,
            $product->photosForColor('Putih')->first()->id
        );
    }

    public function test_admin_dapat_menambah_mengubah_menonaktifkan_dan_menghapus_warna(): void
    {
        [$product] = $this->makeProductWithFourColors();
        $admin = $this->user('admin');

        $this->actingAs($admin)->post(route('admin.colors.store', $product), [
            'name' => 'Maroon',
            'hex' => '#7b2d3a',
        ])->assertSessionHasNoErrors();

        $color = ProductColor::query()->where('product_id', $product->id)->where('name', 'Maroon')->firstOrFail();
        $this->assertSame('#7b2d3a', $color->hex);

        $this->actingAs($admin)->put(route('admin.colors.update', $color), [
            'name' => 'Merah',
            'hex' => '#aa2233',
        ])->assertSessionHas('success');

        $this->assertSame('Merah', $color->fresh()->name);

        $this->actingAs($admin)->patch(route('admin.colors.toggle', $color))->assertSessionHas('success');
        $this->assertFalse($color->fresh()->is_active);

        $this->actingAs($admin)->delete(route('admin.colors.destroy', $color))->assertSessionHas('success');
        $this->assertSame(0, ProductColor::query()->where('id', $color->id)->count());
    }

    public function test_warna_yang_sudah_dipakai_transaksi_tidak_bisa_dihapus(): void
    {
        [$product, $colors] = $this->makeProductWithFourColors();
        $hitam = $colors->firstWhere('name', 'Hitam');

        ProductVariant::query()->where('color_id', $hitam->id)->update(['stock' => 5]);

        $this->actingAs($this->user('admin'))->delete(route('admin.colors.destroy', $hitam))
            ->assertSessionHas('error');

        $this->assertNotNull($hitam->fresh());
    }

    public function test_owner_memiliki_akses_penuh_produk_warna_dan_galeri(): void
    {
        [$product, $colors] = $this->makeProductWithFourColors();
        $owner = $this->user('owner');
        $hitam = $colors->firstWhere('name', 'Hitam');
        $media = $product->photosForColor('Hitam')->first();

        $this->actingAs($owner)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($owner)->get(route('admin.products.edit', $product))->assertOk();

        $variant = $hitam->variants()->first();

        $this->actingAs($owner)->put(route('admin.variants.update', $variant), [
            'size' => $variant->size,
            'color_id' => $hitam->id,
            'stock' => 12,
        ])->assertSessionHas('success');

        $this->actingAs($owner)->post(route('admin.products.photos.store', $product), [
            'color_id' => $hitam->id,
            'photos' => [UploadedFile::fake()->image('hitam-baru.jpg', 20, 20)],
        ])->assertSessionHas('success');

        $this->actingAs($owner)->patch(route('admin.media.color.primary', $media))->assertSessionHas('success');

        $this->assertSame(12, (int) $hitam->variants()->first()->fresh()->stock);
        $this->assertTrue((bool) $media->fresh()->is_color_primary);
        $this->assertSame($media->id, (int) $hitam->fresh()->thumbnail_media_id);
    }

    public function test_warna_terpilih_tersimpan_di_keranjang_checkout_sale_item_dan_invoice(): void
    {
        [$product, $colors] = $this->makeProductWithFourColors();
        $cream = $colors->firstWhere('name', 'Cream');
        $buyer = $this->user('customer', 'buyer.galeri@tokotabita.test', 'Pembeli Galeri');

        $this->actingAs($buyer)->post('/keranjang/tambah', [
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'Cream',
            'qty' => 2,
        ])->assertSessionHasNoErrors();

        $this->actingAs($buyer)->get('/keranjang')->assertSee('Cream');

        $this->actingAs($buyer)->post('/checkout', [
            'buyer_name' => 'Pembeli Galeri',
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Galeri No. 2, Semarang',
            'shipping_zone' => 'jawa_tengah',
            'courier' => 'jne',
            'service' => 'REG',
            'payment_method' => 'transfer',
            'idempotency_key' => 'galeri-cream-1',
        ])->assertSessionHasNoErrors();

        $sale = Sale::query()->where('user_id', $buyer->id)->firstOrFail();
        $item = $sale->items->first();

        $this->assertSame('Cream', $item->color);
        $this->assertSame('L', $item->size);
        $this->assertSame($cream->id, (int) $item->color_id);
        $this->assertSame($cream->thumbnail_media_id, (int) $item->media_id);

        $this->actingAs($this->user('admin'))->get(route('invoice.show', $sale))
            ->assertOk()
            ->assertSee('Warna Cream')
            ->assertSee(Media::query()->findOrFail($item->media_id)->url(), false);
    }

    public function test_perubahan_nama_warna_tidak_mengubah_riwayat_pesanan_lama(): void
    {
        [$product, $colors] = $this->makeProductWithFourColors();
        $hitam = $colors->firstWhere('name', 'Hitam');
        $buyer = $this->user('customer', 'buyer.riwayat@tokotabita.test', 'Pembeli Riwayat');

        $this->actingAs($buyer)->post('/keranjang/tambah', [
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Hitam',
            'qty' => 1,
        ]);
        $this->actingAs($buyer)->post('/checkout', [
            'buyer_name' => 'Pembeli Riwayat',
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Riwayat No. 3, Semarang',
            'shipping_zone' => 'jawa_tengah',
            'courier' => 'jne',
            'service' => 'REG',
            'payment_method' => 'transfer',
            'idempotency_key' => 'riwayat-hitam-1',
        ])->assertSessionHasNoErrors();

        $sale = Sale::query()->where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame('Hitam', $sale->items->first()->color);

        $this->actingAs($this->user('admin'))->put(route('admin.colors.update', $hitam), [
            'name' => 'Hitam Doff',
            'hex' => '#111111',
        ])->assertSessionHas('success');

        $this->assertSame('Hitam', $sale->fresh()->items->first()->color);
        $this->assertSame('Hitam Doff', $hitam->fresh()->name);
    }

    public function test_varian_nonaktif_tidak_bisa_dimasukkan_keranjang(): void
    {
        [$product, $colors] = $this->makeProductWithFourColors();
        $variant = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('color', 'Putih')
            ->where('size', 'S')
            ->firstOrFail();

        $variant->update(['is_active' => false]);

        $this->actingAs($this->user('customer', 'buyer.nonaktif@tokotabita.test', 'Pembeli Nonaktif'))
            ->post('/keranjang/tambah', [
                'product_id' => $product->id,
                'size' => 'S',
                'color' => 'Putih',
                'qty' => 1,
            ])->assertSessionHas('error');

        $this->assertSame(0, collect(session('pos_cart', ['items' => []])['items'] ?? [])->count());
    }

    /** @return array{Product, \Illuminate\Support\Collection} */
    private function makeProductWithFourColors(): array
    {
        $product = Product::create([
            'barcode' => 'TB-GAL-'.strtoupper(Str::random(6)),
            'name' => 'Dress Galeri Empat Warna',
            'category_id' => Category::query()->firstOrFail()->id,
            'unit' => 'pcs',
            'cost_price' => 70000,
            'sale_price' => 150000,
            'stock' => 0,
            'min_stock' => 2,
            'is_active' => true,
        ]);

        $matrix = [
            'Hitam' => ['#111111', ['S' => 5, 'M' => 8]],
            'Putih' => ['#ffffff', ['S' => 3, 'M' => 6]],
            'Cream' => ['#e9d9c3', ['L' => 4]],
            'Navy' => ['#1a2b4c', ['M' => 2]],
        ];

        $total = 0;
        $colors = collect();
        $sort = 0;

        foreach ($matrix as $name => [$hex, $sizes]) {
            $color = ProductColor::create([
                'product_id' => $product->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'hex' => $hex,
                'is_active' => true,
                'sort_order' => $sort++,
            ]);
            $colors->push($color);

            for ($i = 1; $i <= 2; $i++) {
                $path = 'media/product/'.$product->id.'/gallery/'.Str::slug($name).'-'.$i.'.jpg';
                Storage::disk('public')->put($path, 'foto-'.$name.'-'.$i);

                $media = Media::create([
                    'mediable_type' => 'product',
                    'mediable_id' => $product->id,
                    'role' => 'gallery',
                    'color' => $name,
                    'color_id' => $color->id,
                    'is_color_primary' => $i === 1,
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $name.'-'.$i.'.jpg',
                    'mime' => 'image/jpeg',
                    'size' => 100,
                    'sort_order' => $i,
                ]);

                if ($i === 1) {
                    $color->update(['thumbnail_media_id' => $media->id]);
                }
            }

            foreach ($sizes as $size => $stock) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'color' => $name,
                    'color_id' => $color->id,
                    'size' => $size,
                    'stock' => $stock,
                    'is_active' => true,
                    'sort_order' => $total,
                ]);
                $total += $stock;
            }
        }

        $product->update(['stock' => $total]);

        return [$product->refresh(), $colors];
    }

    private function user(string $role, ?string $email = null, ?string $name = null): User
    {
        $email ??= $role.'@galeri.test';

        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name ?? ucfirst($role),
                'password' => bcrypt('password123'),
                'role' => $role === 'customer' ? UserRole::Customer : ($role === 'owner' ? UserRole::Owner : UserRole::Admin),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
