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
 * Pengujian fitur manajemen gambar (media).
 *
 * Cakupan sesuai permintaan fitur:
 *  - unggah foto produk (banyak sekaligus + label warna variasi)
 *  - ganti dan hapus foto (berkas ikut dihapus bila tak dipakai)
 *  - foto utama & urutan galeri
 *  - aset tampilan toko: logo, banner homepage, banner promosi, koleksi
 *  - foto kategori (foto baru menggantikan yang lama)
 *  - validasi format (jpg/jpeg/png/webp) dan batas ukuran
 *  - lokasi berkas disimpan di database, nama berkas unik
 *  - izin: admin semua, owner hanya aset toko, kasir tidak sama sekali
 */
class MediaGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * Berkas PNG 1x1 yang valid, disusun manual (signature + IHDR + IDAT +
     * IEND) supaya pengujian tidak bergantung pada ekstensi PHP "gd"
     * yang tidak tersedia di mesin pengujian.
     */
    private function pngBytes(): string
    {
        $chunk = static fn (string $type, string $data): string
            => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        // Satu piksel RGBA transparan: filter baris + 4 byte piksel.
        $raw = "\x00\x00\x00\x00\x00";

        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', 1, 1, 8, 6, 0, 0, 0))
            .$chunk('IDAT', gzcompress($raw, 9))
            .$chunk('IEND', '');
    }

    private function png(string $name = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->pngBytes());
    }

    /**
     * Unggah foto produk lewat route admin.
     *
     * @param  array<int, UploadedFile>  $photos
     */
    private function uploadProductPhotos(Product $product, array $photos, ?string $color = null)
    {
        return $this->actingAs($this->admin())->post('/admin/produk/'.$product->id.'/foto', [
            'photos' => $photos,
            'color' => $color,
        ]);
    }

    // -----------------------------------------------------------------
    // Foto produk
    // -----------------------------------------------------------------

    public function test_admin_dapat_mengunggah_banyak_foto_produk_sekaligus(): void
    {
        $product = Product::factory()->create();

        $response = $this->uploadProductPhotos($product, [$this->png('satu.png'), $this->png('dua.png')], 'Hitam');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $photos = Media::query()->where('mediable_type', 'product')->where('mediable_id', $product->id)->get();

        $this->assertCount(2, $photos);
        $this->assertSame(['Hitam', 'Hitam'], $photos->pluck('color')->all());
        $this->assertSame('gallery', $photos->first()->role);

        // Lokasi berkas tersimpan di database, bukan di source code.
        $photos->each(function (Media $photo) {
            $this->assertNotEmpty($photo->path);
            $this->assertStringNotContainsString('public/storage', $photo->path);
            Storage::disk('public')->assertExists($photo->path);
            // Nama berkas unik (UUID), bukan nama asli dari pengguna.
            // Dicek pada nama berkasnya saja; path memuat direktori
            // media/product/{id}/gallery di depannya.
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.[a-z]+$/',
                basename($photo->path)
            );
            $this->assertStringContainsString('/storage/media/', $photo->url());
        });

        // Foto pertama otomatis menjadi foto utama; hanya boleh satu.
        $this->assertSame(1, $photos->where('is_primary', true)->count());
    }

    public function test_format_bukan_gambar_ditolak(): void
    {
        $product = Product::factory()->create();

        $this->uploadProductPhotos($product, [
            UploadedFile::fake()->createWithContent('catatan.txt', 'ini teks biasa, bukan gambar'),
        ])->assertSessionHasErrors('photos.0');

        $this->assertSame(0, Media::count());
    }

    public function test_ukuran_melewati_batas_ditolak(): void
    {
        $product = Product::factory()->create();

        //3000 KB melebihi batas 2048 KB dari config/media.php.
        $this->uploadProductPhotos($product, [
            UploadedFile::fake()->create('terlalu-besar.png', 3000),
        ])->assertSessionHasErrors('photos.0');

        $this->assertSame(0, Media::count());
    }

    public function test_ganti_foto_menghapus_berkas_lama(): void
    {
        $product = Product::factory()->create();
        $this->uploadProductPhotos($product, [$this->png('lama.png')])->assertSessionHas('success');

        $media = Media::query()->firstOrFail();
        $oldPath = $media->path;
        Storage::disk('public')->assertExists($oldPath);

        $response = $this->actingAs($this->admin())
            ->patch('/admin/foto/'.$media->id, ['photo' => $this->png('baru.png')]);

        $response->assertSessionHas('success');

        $media->refresh();
        $this->assertNotSame($oldPath, $media->path);
        Storage::disk('public')->assertExists($media->path);
        // Berkas lama dihapus karena sudah tidak dipakai baris mana pun.
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertSame(1, Media::count());
    }

    public function test_hapus_foto_menghapus_berkas_dan_mempromosikan_pengganti(): void
    {
        $product = Product::factory()->create();
        $this->uploadProductPhotos($product, [$this->png('utama.png'), $this->png('cadangan.png')]);

        $primary = Media::query()->where('is_primary', true)->firstOrFail();
        $primaryPath = $primary->path;

        $this->actingAs($this->admin())
            ->delete('/admin/foto/'.$primary->id)
            ->assertSessionHas('success');

        $this->assertSame(1, Media::count());
        Storage::disk('public')->assertMissing($primaryPath);

        // Foto tersisa otomatis naik menjadi foto utama.
        $this->assertTrue(Media::query()->firstOrFail()->is_primary);
    }

    public function test_memilih_foto_utama_hanya_meninggalkan_satu(): void
    {
        $product = Product::factory()->create();
        $this->uploadProductPhotos($product, [$this->png('a.png'), $this->png('b.png')]);

        $second = Media::query()->orderBy('id')->skip(1)->first();

        $this->actingAs($this->admin())
            ->patch('/admin/foto/'.$second->id.'/utama')
            ->assertSessionHas('success');

        $this->assertTrue($second->refresh()->is_primary);
        $this->assertSame(1, Media::query()->where('is_primary', true)->count());
    }

    public function test_urutan_galeri_dapat_diatur(): void
    {
        $product = Product::factory()->create();
        $this->uploadProductPhotos($product, [$this->png('a.png'), $this->png('b.png'), $this->png('c.png')]);

        $ids = Media::query()->orderBy('id')->pluck('id')->values()->all();

        // Balik urutan: C, B, A.
        $this->actingAs($this->admin())
            ->post('/admin/foto/urutkan', ['ids' => [$ids[2], $ids[1], $ids[0]]])
            ->assertSessionHas('success');

        $this->assertSame(0, (int) Media::query()->findOrFail($ids[2])->sort_order);
        $this->assertSame(1, (int) Media::query()->findOrFail($ids[1])->sort_order);
        $this->assertSame(2, (int) Media::query()->findOrFail($ids[0])->sort_order);
    }

    public function test_urutan_dengan_id_produk_lain_diabaikan(): void
    {
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        $this->uploadProductPhotos($productA, [$this->png('a1.png'), $this->png('a2.png')]);
        $this->uploadProductPhotos($productB, [$this->png('b1.png')]);

        $a1 = Media::query()->where('mediable_id', $productA->id)->orderBy('id')->firstOrFail();
        $b1 = Media::query()->where('mediable_id', $productB->id)->firstOrFail();

        $before = [$a1->sort_order, $b1->sort_order];

        // id milik dua produk berbeda dicampur -> urutan tidak boleh berubah.
        $this->actingAs($this->admin())
            ->post('/admin/foto/urutkan', ['ids' => [$b1->id, $a1->id]])
            ->assertRedirect();

        $this->assertSame($before, [
            (int) Media::query()->findOrFail($a1->id)->sort_order,
            (int) Media::query()->findOrFail($b1->id)->sort_order,
        ]);
    }

    public function test_label_variasi_warna_dapat_diubah(): void
    {
        $product = Product::factory()->create();
        $this->uploadProductPhotos($product, [$this->png('warna.png')]);

        $media = Media::query()->firstOrFail();

        $this->actingAs($this->admin())
            ->patch('/admin/foto/'.$media->id.'/warna', ['color' => 'Merah Marun'])
            ->assertSessionHas('success');

        $this->assertSame('Merah Marun', $media->refresh()->color);
    }

    public function test_unggahan_bernama_ekstensi_berbahaya_tetap_disimpan_aman(): void
    {
        $product = Product::factory()->create();

        // Isi file PNG valid tetapi nama file memakai ekstensi .php.
        // Laravel menolak unggahan ber-ekstensi PHP langsung di aturan
        // mimes/image (shouldBlockPhpUpload), apa pun isi berkasnya, jadi
        // tidak ada berkas .php yang pernah sampai ke disk. Sebagai lapis
        // kedua, MediaService juga menurunkan ekstensi berkas tersimpan
        // dari isi (MIME), bukan dari nama unggahan.
        $this->uploadProductPhotos($product, [$this->png('skrip.php')])
            ->assertSessionHasErrors('photos.0');

        $this->assertSame(0, Media::count());
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_ekstensi_berkas_diambil_dari_isi_bukan_nama_unggahan(): void
    {
        $product = Product::factory()->create();

        // File tiruan Laravel melaporkan MIME dari NAMA berkas, bukan dari
        // isi, sehingga MIME-nya dipaku ke image/png untuk meniru unggahan
        // asli yang isinya gambar. Nama ".pht" tidak diblokir aturan
        // shouldBlockPhpUpload, jadi berkas ini sah-sah saja lolos validasi
        // namun berbahaya bila nama unggahan dipakai apa adanya.
        $spoofed = UploadedFile::fake()
            ->createWithContent('banner.pht', $this->pngBytes())
            ->mimeType('image/png');

        $this->uploadProductPhotos($product, [$spoofed])
            ->assertSessionHas('success');

        $media = Media::query()->firstOrFail();

        // Ekstensi berkas tersimpan diturunkan dari isi (png), bukan dari
        // nama unggahan (pht).
        $this->assertStringEndsWith('.png', $media->path);
        $this->assertStringNotContainsString('.pht', $media->path);
        Storage::disk('public')->assertExists($media->path);

        // Nama asli tetap tercatat untuk audit, tapi tidak dipakai
        // sebagai nama berkas tersimpan.
        $this->assertSame('banner.pht', $media->original_name);
        $this->assertSame('image/png', $media->mime);
    }

    public function test_audit_log_dicatat_untuk_unggahan_foto_produk(): void
    {
        $product = Product::factory()->create();
        $this->uploadProductPhotos($product, [$this->png('audit.png')]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'media.upload',
            'model_type' => Product::class,
            'model_id' => $product->id,
        ]);
    }

    // -----------------------------------------------------------------
    // Foto kategori
    // -----------------------------------------------------------------

    public function test_foto_kategori_baru_menggantikan_yang_lama(): void
    {
        $category = Category::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/kategori/'.$category->id.'/foto', [
            'photo' => $this->png('kategori-1.png'),
        ])->assertSessionHas('success');

        $first = Media::query()->where('mediable_type', 'category')->firstOrFail();
        $firstPath = $first->path;

        $this->actingAs($admin)->post('/admin/kategori/'.$category->id.'/foto', [
            'photo' => $this->png('kategori-2.png'),
        ])->assertSessionHas('success');

        // Satu kategori satu foto: foto baru menggantikan yang lama.
        $this->assertSame(1, Media::query()->where('mediable_type', 'category')->count());
        Storage::disk('public')->assertMissing($firstPath);
        $this->assertTrue($category->refresh()->photo !== null);
    }

    // -----------------------------------------------------------------
    // Aset tampilan toko (logo, banner, promosi, koleksi)
    // -----------------------------------------------------------------

    public function test_admin_dapat_mengunggah_aset_toko(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/tampilan/banner_home', [
            'photos' => [$this->png('banner-1.png'), $this->png('banner-2.png')],
        ])->assertSessionHas('success');

        $banners = Media::query()->where('role', 'banner_home')->get();

        $this->assertCount(2, $banners);
        // Aset toko tidak terikat pada produk/kategori (mediable_id NULL).
        $this->assertTrue($banners->every(fn (Media $banner) => $banner->mediable_id === null && $banner->mediable_type === 'site'));
    }

    public function test_logo_baru_menggantikan_logo_lama(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/tampilan/logo', [
            'photos' => [$this->png('logo-1.png')],
        ])->assertSessionHas('success');

        $oldPath = Media::query()->where('role', 'logo')->firstOrFail()->path;

        $this->actingAs($admin)->post('/admin/tampilan/logo', [
            'photos' => [$this->png('logo-2.png')],
        ])->assertSessionHas('success');

        $this->assertSame(1, Media::query()->where('role', 'logo')->count());
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_urutan_aset_site_dapat_diatur(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/tampilan/collection', [
            'photos' => [$this->png('koleksi-1.png'), $this->png('koleksi-2.png')],
        ]);

        $ids = Media::query()->where('role', 'collection')->orderBy('id')->pluck('id')->values()->all();

        $this->actingAs($admin)->post('/admin/foto/urutkan', ['ids' => [$ids[1], $ids[0]]])
            ->assertSessionHas('success');

        $this->assertSame(0, (int) Media::query()->findOrFail($ids[1])->sort_order);
        $this->assertSame(1, (int) Media::query()->findOrFail($ids[0])->sort_order);
    }

    public function test_role_aset_toko_tidak_dikenal_ditolak(): void
    {
        $this->actingAs($this->admin())->post('/admin/tampilan/tidak-ada', [
            'photos' => [$this->png('aneh.png')],
        ])->assertNotFound();

        $this->assertSame(0, Media::count());
    }

    // -----------------------------------------------------------------
    // Izin akses
    // -----------------------------------------------------------------

    public function test_owner_dapat_membuka_dan_mengelola_aset_toko(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/admin/tampilan')->assertOk();

        $this->actingAs($owner)->post('/admin/tampilan/banner_promo', [
            'photos' => [$this->png('promo.png')],
        ])->assertSessionHas('success');

        $this->assertSame(1, Media::query()->where('role', 'banner_promo')->count());
    }

    public function test_owner_dapat_mengunggah_foto_produk(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create();

        $this->actingAs($owner)->post('/admin/produk/'.$product->id.'/foto', [
            'photos' => [$this->png('foto.png')],
        ])->assertSessionHas('success');

        $this->assertSame(1, Media::count());
    }

    public function test_owner_dapat_menghapus_foto_produk(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create();
        $this->uploadProductPhotos($product, [$this->png('foto.png')]);

        $media = Media::query()->firstOrFail();

        $this->actingAs($owner)->delete('/admin/foto/'.$media->id)->assertRedirect();

        $this->assertSame(0, Media::count());
    }


    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/admin/tampilan')->assertRedirect(route('login'));
    }
}
