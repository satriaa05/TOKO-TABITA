<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengujian sistem manajemen gambar (media).
 *
 * Fixture gambar asli (JPEG/PNG/WebP) tersimpan di tests/fixtures karena
 * pengujian memerlukan berkas gambar sungguhan untuk validasi MIME.
 */
class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    /**
     * Isi berkas fixture gambar.
     */
    private function fixture(string $name): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/fixtures/'.$name);
    }

    /**
     * UploadedFile dari fixture gambar.
     */
    private function image(string $fixtureName, string $asName): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($asName, $this->fixture($fixtureName));
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function owner(): User
    {
        return User::factory()->owner()->create();
    }

    // -----------------------------------------------------------------
    // Foto produk
    // -----------------------------------------------------------------

    public function test_admin_dapat_mengunggah_foto_produk_dan_foto_pertama_menjadi_utama(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin())->post(
            route('admin.products.photos.store', $product),
            ['photos' => [$this->image('product.jpg', 'foto-depan.jpg')]]
        );

        $response->assertRedirect()->assertSessionHas('success');

        $photos = $product->photos()->get();
        $this->assertCount(1, $photos);
        $this->assertTrue($photos[0]->is_primary);
        $this->assertSame('image/jpeg', $photos[0]->mime);

        Storage::disk('public')->assertExists($photos[0]->path);
    }

    public function test_admin_dapat_mengunggah_beberapa_foto_sekaligus_dengan_label_warna(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin())->post(
            route('admin.products.photos.store', $product),
            [
                'photos' => [
                    $this->image('product.jpg', 'hitam.jpg'),
                    $this->image('product.png', 'biru.png'),
                ],
                'color' => 'Hitam',
            ]
        );

        $response->assertRedirect()->assertSessionHas('success');

        $photos = $product->photos()->get();
        $this->assertCount(2, $photos);
        $this->assertSame('Hitam', $photos[0]->color);
        $this->assertSame('Hitam', $photos[1]->color);
        $this->assertSame(1, $photos[0]->sort_order);
        $this->assertSame(2, $photos[1]->sort_order);
    }

    public function test_format_gambar_tidak_didukung_ditolak(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        $bukanGambar = UploadedFile::fake()->createWithContent('dokumen.txt', 'teks biasa, bukan gambar.');

        $response = $this->actingAs($this->admin())->post(
            route('admin.products.photos.store', $product),
            ['photos' => [$bukanGambar]]
        );

        $response->assertSessionHasErrors('photos.0');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_ukuran_berkas_melebihi_batas_ditolak(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        // JPEG asli + isi tambahan agar melebihi batas 2048 KB,
        // konten tetap dikenali sebagai gambar.
        $gede = UploadedFile::fake()->createWithContent(
            'gede.jpg',
            $this->fixture('product.jpg').str_repeat(' ', 3 * 1024 * 1024)
        );

        $response = $this->actingAs($this->admin())->post(
            route('admin.products.photos.store', $product),
            ['photos' => [$gede]]
        );

        $response->assertSessionHasErrors('photos.0');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_ekstensi_berkas_tersimpan_mengikuti_isi_bukan_nama_asli(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        // UploadedFile nyata (bukan fake testing) agar MIME dideteksi dari
        // ISI berkas - objek fake justru menebak dari nama berkasnya.
        // Isinya JPEG asli, nama aslinya "resep.txt": ekstensi .txt aman
        // (tidak kena blok PHP bawaan Laravel), jadi validasi menilai lewat
        // isi, dan berkas tersimpan wajib berakhiran .jpg sesuai isinya,
        // bukan .txt sesuai nama aslinya.
        $tmpPath = tempnam(sys_get_temp_dir(), 'media_test_');
        copy(dirname(__DIR__, 2).'/fixtures/product.jpg', $tmpPath);

        $file = new UploadedFile($tmpPath, 'resep.txt', null, null, true);

        $response = $this->actingAs($this->admin())->post(
            route('admin.products.photos.store', $product),
            ['photos' => [$file]]
        );

        $response->assertRedirect()->assertSessionHas('success');

        $media = $product->photos()->firstOrFail();

        $this->assertStringEndsWith('.jpg', $media->path);
        $this->assertStringNotContainsString('.txt', $media->path);
        $this->assertSame('image/jpeg', $media->mime);
        Storage::disk('public')->assertExists($media->path);

        @unlink($tmpPath);
    }

    public function test_foto_utama_hanya_satu_dan_bisa_dipindah(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        $this->actingAs($this->admin())->post(
            route('admin.products.photos.store', $product),
            ['photos' => [$this->image('product.jpg', 'a.jpg'), $this->image('product.png', 'b.png')]]
        );

        $photos = $product->photos()->orderBy('sort_order')->get();
        $this->assertTrue($photos[0]->is_primary);
        $this->assertFalse($photos[1]->is_primary);

        $this->actingAs($this->admin())
            ->patch(route('admin.media.primary', $photos[1]))
            ->assertRedirect();

        $this->assertTrue($photos[1]->fresh()->is_primary);
        $this->assertFalse($photos[0]->fresh()->is_primary);
    }

    public function test_mengganti_foto_memperbarui_row_dan_menghapus_berkas_lama(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        $this->actingAs($this->admin())->post(
            route('admin.products.photos.store', $product),
            ['photos' => [$this->image('product.jpg', 'lama.jpg')]]
        );

        $media = $product->photos()->firstOrFail();
        $oldPath = $media->path;

        $this->actingAs($this->admin())
            ->patch(route('admin.media.replace', $media), [
                'photo' => $this->image('product.png', 'baru.png'),
            ])
            ->assertRedirect()->assertSessionHas('success');

        $media->refresh();
        $this->assertNotSame($oldPath, $media->path);
        $this->assertSame('image/png', $media->mime);
        $this->assertDatabaseCount('media', 1);

        Storage::disk('public')->assertExists($media->path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_menghapus_foto_menghapus_berkas_dan_foto_utama_pindah_ke_galeri_berikutnya(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        $this->actingAs($this->admin())->post(
            route('admin.products.photos.store', $product),
            ['photos' => [$this->image('product.jpg', 'a.jpg'), $this->image('product.png', 'b.png')]]
        );

        $photos = $product->photos()->orderBy('sort_order')->get();
        $primary = $photos[0];
        $oldPath = $primary->path;

        $this->actingAs($this->admin())
            ->delete(route('admin.media.destroy', $primary))
            ->assertRedirect();

        $this->assertDatabaseCount('media', 1);
        Storage::disk('public')->assertMissing($oldPath);

        $successor = $product->photos()->firstOrFail();
        $this->assertTrue($successor->is_primary);
        $this->assertSame($photos[1]->id, $successor->id);
    }

    public function test_urutan_galeri_disimpan_sesuai_kirim_admin(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        $this->actingAs($this->admin())->post(
            route('admin.products.photos.store', $product),
            ['photos' => [
                $this->image('product.jpg', 'satu.jpg'),
                $this->image('product.png', 'dua.png'),
                $this->image('product.webp', 'tiga.webp'),
            ]]
        );

        $ids = $product->photos()->orderBy('sort_order')->pluck('id')->toArray();
        // Urutan baru: tiga, satu, dua.
        $newOrder = [$ids[2], $ids[0], $ids[1]];

        $this->actingAs($this->admin())
            ->post(route('admin.media.reorder'), ['ids' => $newOrder])
            ->assertRedirect();

        $ordered = $product->photos()->orderBy('sort_order')->pluck('id')->toArray();
        $this->assertSame($newOrder, $ordered);
    }

    public function test_label_warna_foto_dapat_diubah(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        $this->actingAs($this->admin())->post(
            route('admin.products.photos.store', $product),
            ['photos' => [$this->image('product.jpg', 'satu.jpg')]]
        );

        $media = $product->photos()->firstOrFail();

        $this->actingAs($this->admin())
            ->patch(route('admin.media.color', $media), ['color' => 'Merah'])
            ->assertRedirect();

        $this->assertSame('Merah', $media->fresh()->color);
    }

    // -----------------------------------------------------------------
    // Aset tampilan toko (logo, banner, koleksi)
    // -----------------------------------------------------------------

    public function test_admin_dapat_mengunggah_logo_dan_logo_baru_menggantikan_yang_lama(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.media.site.store', 'logo'), [
                'photos' => [$this->image('product.png', 'logo-lama.png')],
            ])
            ->assertRedirect()->assertSessionHas('success');

        $first = Media::siteAsset('logo');
        $this->assertNotNull($first);
        $oldPath = $first->path;

        $this->actingAs($this->admin())
            ->post(route('admin.media.site.store', 'logo'), [
                'photos' => [$this->image('product.jpg', 'logo-baru.jpg')],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('media', 1);
        $current = Media::siteAsset('logo');

        $this->assertNotSame($oldPath, $current->path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($current->path);
    }

    public function test_admin_dapat_mengunggah_banner_home_lebih_dari_satu(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.media.site.store', 'banner_home'), [
                'photos' => [
                    $this->image('product.jpg', 'banner-1.jpg'),
                    $this->image('product.png', 'banner-2.png'),
                ],
            ])
            ->assertRedirect();

        $this->assertSame(2, Media::siteRole('banner_home')->count());
    }

    public function test_unggah_banner_melebihi_batas_ditolak(): void
    {
        Storage::fake('public');

        $files = [];
        for ($i = 1; $i <= 6; $i++) {
            $files[] = $this->image('product.jpg', 'banner-'.$i.'.jpg');
        }

        $this->actingAs($this->admin())
            ->post(route('admin.media.site.store', 'banner_home'), ['photos' => $files])
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_owner_dapat_mengakses_halaman_tampilan_dan_mengunggah_aset_toko(): void
    {
        Storage::fake('public');
        $owner = $this->owner();

        $this->actingAs($owner)->get(route('admin.media.site'))->assertOk();

        $this->actingAs($owner)
            ->post(route('admin.media.site.store', 'collection'), [
                'photos' => [$this->image('product.jpg', 'koleksi.jpg')],
            ])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, Media::siteRole('collection')->count());
    }

    public function test_owner_dapat_menghapus_foto_produk(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        $this->actingAs($this->admin())->post(
            route('admin.products.photos.store', $product),
            ['photos' => [$this->image('product.jpg', 'satu.jpg')]]
        );

        $media = $product->photos()->firstOrFail();

        $this->actingAs($this->owner())
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect();

        $this->assertDatabaseCount('media', 0);
    }

    // -----------------------------------------------------------------
    // Foto kategori
    // -----------------------------------------------------------------

    public function test_foto_kategori_baru_menggantikan_foto_lama(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.categories.photo.store', $category), [
                'photo' => $this->image('product.png', 'kategori-lama.png'),
            ])
            ->assertRedirect()->assertSessionHas('success');

        $first = $category->photo;
        $this->assertNotNull($first);
        $this->assertTrue($first->is_primary);
        $oldPath = $first->path;

        $this->actingAs($this->admin())
            ->post(route('admin.categories.photo.store', $category), [
                'photo' => $this->image('product.jpg', 'kategori-baru.jpg'),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('media', 1);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($category->fresh()->photo->path);
    }

    // -----------------------------------------------------------------
    // Tampilan memakai gambar dari database
    // -----------------------------------------------------------------

    public function test_logo_tampil_di_sidebar_tanpa_mengubah_kode(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.media.site.store', 'logo'), [
                'photos' => [$this->image('product.png', 'logo.png')],
            ])
            ->assertRedirect();

        $logo = Media::siteAsset('logo');

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($logo->url(), false);
    }
}
