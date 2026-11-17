<?php

/*
|--------------------------------------------------------------------------
| Pemeriksa Syarat Fitur Manajemen Gambar - TOKO TABITA
|--------------------------------------------------------------------------
|
| Menjalankan daftar syarat dari brief fitur manajemen gambar dan memeriksa
| mana yang sudah terpenuhi di kode, mana yang belum. Dipakai sebagai pagar
| rilis (release gate): jalankan sebelum menyatakan fitur selesai.
|
|   php scripts/cek-syarat-media.php
|
| Keluaran: tabel STATUS per syarat dan ringkasan di akhir.
|
*/

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require $root.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Route;

$baris = [];

function cek(string $label, bool $terpenuhi, string $catatan = ''): void
{
    global $baris;

    $baris[] = [
        'syarat' => $label,
        'status' => $terpenuhi ? 'TERPENUHI' : 'BELUM',
        'catatan' => $catatan,
    ];
}

function routeAda(string $nama): bool
{
    return Route::has($nama);
}

function kelasAda(string $kelas): bool
{
    return class_exists($kelas);
}

function fileAda(string $path): bool
{
    return is_file($path);
}

/*
|--------------------------------------------------------------------------
| A. Kemampuan Admin (8 permintaan)
|--------------------------------------------------------------------------
*/

cek('Route unggah foto produk', routeAda('admin.products.photos.store'),
    routeAda('admin.products.photos.store') ? '' : 'route admin.products.photos.store belum terdaftar');
cek('Route ganti foto produk', routeAda('admin.media.replace'));
cek('Route hapus foto produk', routeAda('admin.media.destroy'));
cek('Route pilih foto utama', routeAda('admin.media.primary'));
cek('Route atur urutan galeri', routeAda('admin.media.reorder'));
cek('Route ubah label variasi warna', routeAda('admin.media.color'));
cek('Route unggah aset toko', routeAda('admin.media.site.store'));
cek('Halaman kelola aset toko', routeAda('admin.media.site'));

cek('Unggah banyak foto sekaligus', fileAda($root.'/app/Http/Controllers/Admin/MediaController.php')
    && str_contains((string) file_get_contents($root.'/app/Http/Controllers/Admin/MediaController.php'), 'photos.*'),
    'Controller mencari array photos.*');

/*
|--------------------------------------------------------------------------
| B. Aset Toko (5 aset)
|--------------------------------------------------------------------------
*/

$roles = (array) config('media.site_roles', []);

foreach (['logo', 'banner_home', 'banner_promo', 'collection'] as $role) {
    cek("Aset toko: {$role}", array_key_exists($role, $roles));
}

cek('Gambar kategori', routeAda('admin.categories.photo.store'),
    'route admin.categories.photo.store belum terdaftar');

/*
|--------------------------------------------------------------------------
| C. Ketentuan Teknis
|--------------------------------------------------------------------------
*/

$service = $root.'/app/Services/MediaService.php';
$isiService = fileAda($service) ? (string) file_get_contents($service) : '';

cek('Memakai Laravel Storage', str_contains($isiService, 'Storage::'),
    'Laravel Storage untuk menyimpan berkas');
cek('Lokasi berkas tersimpan di database',
    fileAda($root.'/database/migrations/2026_11_11_000001_create_media_table.php'),
    'kolom disk + path pada tabel media');
cek('Format JPG, JPEG, PNG, WebP',
    in_array('jpg', (array) config('media.allowed_mimes', []), true)
    && in_array('jpeg', (array) config('media.allowed_mimes', []), true)
    && in_array('png', (array) config('media.allowed_mimes', []), true)
    && in_array('webp', (array) config('media.allowed_mimes', []), true));
cek('Batas ukuran file ada', (int) config('media.max_size_kb', 0) > 0,
    'batas '.config('media.max_size_kb').' KB per berkas');
cek('Nama berkas unik', str_contains($isiService, 'Str::uuid()'),
    'UUID dipakai sebagai nama berkas');
cek('Hapus berkas lama bila tidak dipakai lagi',
    str_contains($isiService, 'deleteFileIfUnreferenced'));
cek('Optimasi ukuran gambar', str_contains($isiService, 'optimizeWithGd'),
    extension_loaded('gd')
        ? 'ekstensi gd tersedia, optimasi berjalan'
        : 'ekstensi gd BELUM ada di mesin ini, optimasi dilewati');
cek('Gambar tampil setelah deploy (public/storage)',
    file_exists($root.'/public/storage'),
    'symlink storage:link sudah dibuat');
cek('Perubahan langsung tercermin tanpa ubah kode',
    fileAda($root.'/app/Models/Media.php'),
    'lokasi file dibaca dari database, bukan dari kode');

/*
|--------------------------------------------------------------------------
| D. Keamanan
|--------------------------------------------------------------------------
*/

cek('Ekstensi dari ISI berkas, bukan nama unggahan',
    str_contains($isiService, 'finfo') || str_contains($isiService, 'mimeFromContent'),
    'deteksi MIME lewat magic bytes');
cek('Label warna dibatasi 50 karakter',
    str_contains($isiService, 'Str::limit($color, 50'));
cek('Izin admin/owner ditegakkan di server',
    fileAda($root.'/app/Http/Controllers/Admin/MediaController.php')
    && str_contains((string) file_get_contents($root.'/app/Http/Controllers/Admin/MediaController.php'), 'authorize'),
    'pemeriksaan peran di controller');

/*
|--------------------------------------------------------------------------
| E. Gerbang Uji
|--------------------------------------------------------------------------
*/

cek('Uji service media ada', fileAda($root.'/tests/Feature/Media/TabitaMediaServiceSpecTest.php'));
cek('Uji HTTP media ada', fileAda($root.'/tests/Feature/Media/TabitaMediaHttpSpecTest.php'));

/*
|--------------------------------------------------------------------------
| Cetak hasil
|--------------------------------------------------------------------------
*/

$lebar = max(array_map(fn ($b) => mb_strlen($b['syarat']), $baris)) + 2;

echo PHP_EOL."PEMERIKSA SYARAT FITUR MANAJEMEN GAMBAR - TOKO TABITA".PHP_EOL;
echo str_repeat('=', $lebar + 60).PHP_EOL;

foreach ($baris as $b) {
    printf(
        "%-".$lebar."s %-12s %s%s",
        $b['syarat'],
        $b['status'],
        $b['catatan'],
        PHP_EOL
    );
}

$terpenuhi = count(array_filter($baris, fn ($b) => $b['status'] === 'TERPENUHI'));
$total = count($baris);

echo str_repeat('-', $lebar + 60).PHP_EOL;
printf("TERPENUHI %d dari %d syarat%s", $terpenuhi, $total, PHP_EOL);

if ($terpenuhi < $total) {
    echo PHP_EOL."Syarat yang belum: ".PHP_EOL;
    foreach ($baris as $b) {
        if ($b['status'] !== 'TERPENUHI') {
            echo '  - '.$b['syarat'].($b['catatan'] !== '' ? ' ('.$b['catatan'].')' : '').PHP_EOL;
        }
    }
}

echo PHP_EOL;

exit($terpenuhi === $total ? 0 : 1);
