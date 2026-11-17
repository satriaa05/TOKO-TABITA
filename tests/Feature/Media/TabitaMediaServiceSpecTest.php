<?php

namespace Tests\Feature\Media;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Gerbang uji lapisan service untuk fitur Manajemen Gambar (Media).
 *
 * Berkas ini menguji aturan inti media tanpa bergantung pada route HTTP,
 * sehingga bisa dipakai sebagai pagar keamanan (quality gate) begitu pun
 * backend sedang dikerjakan:
 *
 *  - nama berkas unik dan format diambil dari ISI berkas, bukan nama unggahan
 *  - lokasi berkas tersimpan di database pada disk publik
 *  - berkas lama dihapus saat diganti, tetapi TIDAK dihapus bila masih dipakai
 *  - hanya satu foto utama per target, dan urutan galeri bisa diatur
 *  - label variasi warna tersimpan rapi
 *  - gambar tetap bisa diakses lewat URL publik setelah storage:link
 */
class TabitaMediaServiceSpecTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function service(): MediaService
    {
        return app(MediaService::class);
    }

    /**
     * Isi fixture gambar asli (PNG/JPG/WebP) dari tests/fixtures.
     */
    private function fixture(string $name): string
    {
        $path = dirname(__DIR__, 2).'/fixtures/'.$name;

        $this->assertFileExists($path, 'Fixture gambar tidak ditemukan: '.$path);

        return (string) file_get_contents($path);
    }

    /**
     * Unggahan dengan isi PNG asli, nama berkas ditentukan pemanggil.
     */
    private function png(string $asName = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($asName, $this->fixture('tabita-product.png'));
    }

    public function test_berkas_disimpan_pada_disk_publik_dengan_lokasi_tercatat_di_database(): void
    {
        $media = $this->service()->store($this->png(), 'product', 7, 'gallery');

        $this->assertSame('public', $media->disk);
        $this->assertStringStartsWith('media/product/7/gallery/', $media->path);
        $this->assertTrue(Storage::disk('public')->exists($media->path));
        $this->assertSame('image/png', $media->mime);
        $this->assertNotNull($media->size);
    }

    public function test_nama_berkas_selalu_unik_walaupun_nama_asli_sama(): void
    {
        $service = $this->service();

        $first = $service->store($this->png('produk.png'), 'product', 1, 'gallery');
        $second = $service->store($this->png('produk.png'), 'product', 1, 'gallery');

        $this->assertNotSame($first->path, $second->path);
        $this->assertTrue(Storage::disk('public')->exists($first->path));
        $this->assertTrue(Storage::disk('public')->exists($second->path));

        // Nama berkas mengikuti pola UUID + ekstensi.
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.png$/',
            basename($first->path)
        );
    }

    /**
     * Nama unggahan yang berbahaya tidak boleh ikut tersimpan apa adanya.
     */
    public function test_ekstensi_diambil_dari_isi_berkas_bukan_dari_nama_unggahan(): void
    {
        $service = $this->service();

        $php = $service->store($this->png('shell.php'), 'product', 1, 'gallery');
        $txt = $service->store($this->png('catatan.txt'), 'product', 1, 'gallery');

        $this->assertStringEndsWith('.png', $php->path);
        $this->assertStringEndsWith('.png', $txt->path);
        $this->assertStringNotContainsString('.php', $php->path);
        $this->assertFalse(Storage::disk('public')->exists('media/product/1/gallery/shell.php'));
    }

    public function test_ganti_foto_memperbarui_baris_dan_menghapus_berkas_lama(): void
    {
        $service = $this->service();

        $media = $service->store($this->png('lama.png'), 'product', 3, 'gallery');
        $oldPath = $media->path;

        $service->replace($media, $this->png('baru.png'));

        $media->refresh();

        $this->assertNotSame($oldPath, $media->path);
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
        $this->assertTrue(Storage::disk('public')->exists($media->path));
        $this->assertSame('baru.png', $media->original_name);
    }

    /**
     * Bila berkas yang sama masih dipakai baris lain, berkas TIDAK boleh dihapus.
     */
    public function test_berkas_lama_tidak_dihapus_kalau_masih_dipakai_baris_lain(): void
    {
        $service = $this->service();

        $media = $service->store($this->png('dipakai.png'), 'product', 4, 'gallery');
        $sharedPath = $media->path;

        $twin = Media::create([
            'mediable_type' => 'category',
            'mediable_id' => 9,
            'role' => 'photo',
            'disk' => 'public',
            'path' => $sharedPath,
            'original_name' => 'dipakai.png',
            'mime' => 'image/png',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $service->replace($media, $this->png('pengganti.png'));

        $this->assertTrue(Storage::disk('public')->exists($sharedPath));
        $this->assertSame($sharedPath, $twin->refresh()->path);
    }

    public function test_hapus_foto_menghapus_berkas_dan_foto_berikutnya_menjadi_utama(): void
    {
        $service = $this->service();

        $first = $service->store($this->png('satu.png'), 'product', 5, 'gallery');
        $second = $service->store($this->png('dua.png'), 'product', 5, 'gallery');

        $service->setPrimary($first);

        $service->delete($first);

        $this->assertNull(Media::query()->find($first->id));
        $this->assertFalse(Storage::disk('public')->exists($first->path));
        $this->assertTrue($second->refresh()->is_primary);
    }

    public function test_foto_utama_hanya_satu_per_target(): void
    {
        $service = $this->service();

        $first = $service->store($this->png('a.png'), 'product', 6, 'gallery');
        $second = $service->store($this->png('b.png'), 'product', 6, 'gallery');

        $service->setPrimary($first);
        $service->setPrimary($second);

        $primaries = Media::query()
            ->where('mediable_type', 'product')
            ->where('mediable_id', 6)
            ->where('role', 'gallery')
            ->where('is_primary', true)
            ->count();

        $this->assertSame(1, $primaries);
        $this->assertTrue($second->refresh()->is_primary);
        $this->assertFalse($first->refresh()->is_primary);
    }

    public function test_urutan_galeri_dapat_diatur_dan_item_pertama_menjadi_utama(): void
    {
        $service = $this->service();

        $a = $service->store($this->png('a.png'), 'product', 8, 'gallery');
        $b = $service->store($this->png('b.png'), 'product', 8, 'gallery');
        $c = $service->store($this->png('c.png'), 'product', 8, 'gallery');

        $service->reorder('product', 8, 'gallery', [$c->id, $a->id, $b->id]);

        $this->assertSame(0, $c->refresh()->sort_order);
        $this->assertSame(1, $a->refresh()->sort_order);
        $this->assertSame(2, $b->refresh()->sort_order);
        $this->assertTrue($c->refresh()->is_primary);
    }

    /**
     * Id milik produk lain tidak boleh bisa mengubah urutan.
     */
    public function test_urutan_dengan_id_asing_diabaikan(): void
    {
        $service = $this->service();

        $mine = $service->store($this->png('milik-saya.png'), 'product', 10, 'gallery');
        $other = $service->store($this->png('milik-lain.png'), 'product', 11, 'gallery');

        $orderBefore = [$mine->refresh()->sort_order, $other->refresh()->sort_order];

        $service->reorder('product', 10, 'gallery', [$other->id, $mine->id]);

        $orderAfter = [$mine->refresh()->sort_order, $other->refresh()->sort_order];

        $this->assertSame($orderBefore, $orderAfter, 'Urutan milik target lain tidak boleh berubah.');
        $this->assertSame(1, $mine->refresh()->sort_order, 'Foto milik target ini tetap pada posisinya sendiri.');
    }

    public function test_label_variasi_warna_tersimpan_rapi(): void
    {
        $service = $this->service();

        $withColor = $service->store($this->png(), 'product', 12, 'gallery', '  Hitam  ');
        $withoutColor = $service->store($this->png(), 'product', 12, 'gallery', '   ');

        $this->assertSame('Hitam', $withColor->refresh()->color);
        $this->assertNull($withoutColor->refresh()->color);
    }

    /**
     * Aset toko (logo, banner, koleksi) memakai direktori terpisah.
     */
    public function test_aset_toko_tersimpan_di_direktori_site(): void
    {
        $media = $this->service()->store($this->png('logo.png'), 'site', null, 'logo');

        $this->assertSame('site', $media->mediable_type);
        $this->assertNull($media->mediable_id);
        $this->assertStringStartsWith('media/site/site/logo/', $media->path);
    }

    /**
     * Gambar harus bisa diakses lewat URL publik (syarat agar tampil setelah deploy).
     */
    public function test_gambar_tetap_tampil_setelah_deploy_melalui_url_publik(): void
    {
        $media = $this->service()->store($this->png(), 'product', 13, 'gallery');

        $url = $media->url();

        $this->assertStringContainsString(basename($media->path), $url);
        $this->assertStringContainsString('/storage', (string) config('filesystems.disks.public.url'));

        // Symlink public/storage wajib ada di server, kalau tidak gambar 404.
        $this->assertTrue(
            file_exists(public_path('storage')),
            'Symlink public/storage belum dibuat. Jalankan: php artisan storage:link'
        );
    }

    /**
     * Optimasi ukuran gambar hanya jalan bila ekstensi gd tersedia.
     */
    public function test_gambar_lebar_diperkecil_bila_gd_tersedia(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('Ekstensi PHP "gd" belum ada di mesin ini, optimasi gambar dilewati.');
        }

        $media = $this->service()->store(
            UploadedFile::fake()->createWithContent('lebar.png', $this->fixture('tabita-lebar.png')),
            'product',
            14,
            'gallery'
        );

        $media->refresh();

        $this->assertSame(1600, $media->width);
        $this->assertLessThanOrEqual(1600, $media->width);
    }
}
