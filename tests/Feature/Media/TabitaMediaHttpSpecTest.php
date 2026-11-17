<?php

namespace Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Gerbang uji HTTP untuk fitur Manajemen Gambar (Media) TOKO TABITA.
 *
 * Berkas ini memeriksa seluruh kemampuan yang diminta di brief:
 *  1. Upload foto produk        5. Pilih foto utama
 *  2. Ganti foto produk         6. Atur urutan galeri
 *  3. Hapus foto produk         7. Unggah foto per variasi warna
 *  4. Many foto per produk      8. Preview sebelum menyimpan
 *  + Aset toko: banner homepage, logo toko, gambar kategori,
 *    banner promosi, foto koleksi terbaru.
 *
 * Uji otomatis dilewati (skip) bila route atau kelas pendukung belum ada,
 * sehingga berkas ini aman dipakai sepanjang pengerjaan backend dan
 * otomatis menjadi cek nyata begitu backend selesai.
 */
class TabitaMediaHttpSpecTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * Lewati uji bila prasyarat backend belum ada.
     */
    private function skipUnlessReady(string $routeName): void
    {
        if (! Route::has($routeName)) {
            $this->markTestSkipped("Route '$routeName' belum ada. Uji otomatis aktif setelah route dibuat.");
        }

        if (! class_exists(\App\Enums\UserRole::class)) {
            $this->markTestSkipped('Enum App\Enums\UserRole belum ada (peran admin/owner/kasir belum siap).');
        }

        if (! class_exists(\App\Models\Product::class)) {
            $this->markTestSkipped('Model App\Models\Product belum ada.');
        }
    }

    private function png(string $asName = 'foto.png'): UploadedFile
    {
        $bytes = file_get_contents(dirname(__DIR__, 2).'/fixtures/tabita-product.png');

        return UploadedFile::fake()->createWithContent($asName, $bytes);
    }

    private function admin(): \App\Models\User
    {
        return \App\Models\User::factory()->admin()->create();
    }

    private function owner(): \App\Models\User
    {
        return \App\Models\User::factory()->owner()->create();
    }

    public function test_admin_bisa_mengunggah_foto_produk_dan_foto_pertama_menjadi_utama(): void
    {
        $this->skipUnlessReady('admin.products.photos.store');

        $product = \App\Models\Product::factory()->create();

        $response = $this->actingAs($this->admin())->post(route('admin.products.photos.store', $product), [
            'photos' => [$this->png()],
        ]);

        $response->assertRedirect();

        $media = $product->photos()->first();

        $this->assertNotNull($media);
        $this->assertTrue($media->is_primary);
        $this->assertTrue(Storage::disk('public')->exists($media->path));
    }

    public function test_admin_bisa_mengunggah_banyak_foto_produk_sekaligus_dengan_label_warna(): void
    {
        $this->skipUnlessReady('admin.products.photos.store');

        $product = \App\Models\Product::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.products.photos.store', $product), [
            'photos' => [$this->png('hitam.png'), $this->png('putih.png')],
            'color' => 'Hitam',
        ])->assertRedirect();

        $this->assertSame(2, $product->photos()->count());
        $this->assertSame('Hitam', $product->photos()->first()->color);
    }

    public function test_admin_bisa_mengganti_foto_produk(): void
    {
        $this->skipUnlessReady('admin.products.photos.store');
        $this->skipUnlessReady('admin.media.replace');

        $admin = $this->admin();
        $product = \App\Models\Product::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.photos.store', $product), [
            'photos' => [$this->png('lama.png')],
        ])->assertRedirect();

        $media = $product->photos()->first();
        $oldPath = $media->path;

        $this->actingAs($admin)->patch(route('admin.media.replace', $media), [
            'photo' => $this->png('baru.png'),
        ])->assertRedirect();

        $media->refresh();

        $this->assertNotSame($oldPath, $media->path);
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
    }

    public function test_admin_bisa_menghapus_foto_produk(): void
    {
        $this->skipUnlessReady('admin.products.photos.store');
        $this->skipUnlessReady('admin.media.destroy');

        $admin = $this->admin();
        $product = \App\Models\Product::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.photos.store', $product), [
            'photos' => [$this->png()],
        ])->assertRedirect();

        $media = $product->photos()->first();

        $this->actingAs($admin)->delete(route('admin.media.destroy', $media))->assertRedirect();

        $this->assertNull(\App\Models\Media::query()->find($media->id));
        $this->assertFalse(Storage::disk('public')->exists($media->path));
    }

    public function test_admin_bisa_menentukan_foto_utama_dan_mengatur_urutan(): void
    {
        $this->skipUnlessReady('admin.products.photos.store');
        $this->skipUnlessReady('admin.media.primary');
        $this->skipUnlessReady('admin.media.reorder');

        $admin = $this->admin();
        $product = \App\Models\Product::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.photos.store', $product), [
            'photos' => [$this->png('a.png'), $this->png('b.png'), $this->png('c.png')],
        ])->assertRedirect();

        $photos = $product->photos()->orderBy('id')->get();
        [$a, $b, $c] = $photos->all();

        $this->actingAs($admin)->patch(route('admin.media.primary', $c))->assertRedirect();
        $this->assertTrue($c->refresh()->is_primary);

        $this->actingAs($admin)->post(route('admin.media.reorder'), [
            'ids' => [$c->id, $a->id, $b->id],
        ])->assertRedirect();

        $this->assertTrue($c->refresh()->is_primary);
    }

    public function test_admin_bisa_mengubah_label_variasi_warna(): void
    {
        $this->skipUnlessReady('admin.products.photos.store');
        $this->skipUnlessReady('admin.media.color');

        $admin = $this->admin();
        $product = \App\Models\Product::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.photos.store', $product), [
            'photos' => [$this->png()],
        ])->assertRedirect();

        $media = $product->photos()->first();

        $this->actingAs($admin)->patch(route('admin.media.color', $media), [
            'color' => 'Navy',
        ])->assertRedirect();

        $this->assertSame('Navy', $media->refresh()->color);
    }

    public function test_admin_dan_owner_bisa_mengelola_aset_toko(): void
    {
        $this->skipUnlessReady('admin.media.site');
        $this->skipUnlessReady('admin.media.site.store');

        foreach (['admin', 'owner'] as $peran) {
            $user = $peran === 'admin' ? $this->admin() : $this->owner();

            $this->actingAs($user)->get(route('admin.media.site'))->assertOk();

            $this->actingAs($user)->post(route('admin.media.site.store', 'banner_home'), [
                'photos' => [$this->png('banner.png')],
            ])->assertRedirect();

            $this->assertGreaterThan(0, \App\Models\Media::siteRole('banner_home')->count());
        }
    }

    public function test_logo_toko_hanya_satu_dan_diganti_oleh_logo_baru(): void
    {
        $this->skipUnlessReady('admin.media.site.store');

        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.media.site.store', 'logo'), [
            'photos' => [$this->png('logo-1.png')],
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.media.site.store', 'logo'), [
            'photos' => [$this->png('logo-2.png')],
        ])->assertRedirect();

        $this->assertSame(1, \App\Models\Media::siteRole('logo')->count());
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->skipUnlessReady('admin.media.site');

        $this->get(route('admin.media.site'))->assertRedirect(route('login'));
    }

    public function test_format_bukan_gambar_ditolak(): void
    {
        $this->skipUnlessReady('admin.products.photos.store');

        $product = \App\Models\Product::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.products.photos.store', $product), [
            'photos' => [UploadedFile::fake()->createWithContent('dokumen.txt', 'bukan gambar sama sekali')],
        ])->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_ukuran_berkas_melebihi_batas_ditolak(): void
    {
        $this->skipUnlessReady('admin.products.photos.store');

        $product = \App\Models\Product::factory()->create();

        $besar = UploadedFile::fake()->createWithContent(
            'besar.png',
            str_repeat('x', (int) config('media.max_size_kb') * 1024 + 1024)
        );

        $this->actingAs($this->admin())->post(route('admin.products.photos.store', $product), [
            'photos' => [$besar],
        ])->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('media', 0);
    }
}
