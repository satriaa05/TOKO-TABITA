<?php

// Jalankan test render dengan koneksi fallback jika MySQL Laragon belum di-"Start All"
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . __DIR__ . '/../database/database.sqlite');
putenv('SESSION_DRIVER=file');

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$routes = [
    '/' => 'Homepage',
    '/login' => 'Halaman Login Glassmorphism (Sesuai Referensi)',
    '/register' => 'Halaman Register Glassmorphism',
    '/katalog' => 'Katalog Produk',
    '/keranjang' => 'Keranjang (Wajib Login Test)',
];

echo "=== VALIDASI SINTAKS DAN RENDERING BLADE VIEW TABITA MODISTE ===\n\n";

foreach ($routes as $uri => $name) {
    $request = Illuminate\Http\Request::create($uri, 'GET');
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    
    if ($status === 200) {
        echo "[STATUS 200 - BERHASIL] {$name} ({$uri})\n";
        echo "   -> Konten HTML ter-render sempurna (" . strlen($response->getContent()) . " bytes)\n";
    } elseif ($status === 302) {
        $target = $response->headers->get('Location');
        echo "[STATUS 302 - REDIRECT GATE] {$name} ({$uri})\n";
        echo "   -> Pengunjung tamu dialihkan ke: {$target}\n";
    } else {
        echo "[STATUS {$status} - PERIKSA] {$name} ({$uri})\n";
    }
}
