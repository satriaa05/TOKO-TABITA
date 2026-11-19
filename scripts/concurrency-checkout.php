<?php

/**
 * Uji konkurensi nyata: 5 pembeli menekan Checkout bersamaan pada varian
 * dengan stok = 1. Jalankan lewat scripts/concurrency-checkout.ps1 yang
 * menyalakan 5 proses server dengan DB_BUSY_TIMEOUT + WAL.
 *
 * Output: ringkasan JSON jumlah sukses/gagal dan stok akhir.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$ports = array_map('intval', explode(',', (string) (getenv('CONCURRENCY_PORTS') ?: '8101,8102,8103,8104,8105')));
$tempDir = sys_get_temp_dir().'/tabita-concurrency-'.getmypid();
@mkdir($tempDir, 0777, true);

function request(int $port, string $method, string $path, string $cookie, ?array $fields = null): array
{
    $ch = curl_init('http://127.0.0.1:'.$port.$path);
    $headers = [];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields ?? []));
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $raw = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return [
        'status' => $status,
        'headers' => substr($raw, 0, $headerSize),
        'body' => substr($raw, $headerSize),
    ];
}

function token(string $html): string
{
    return preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m) ? $m[1] : '';
}

function field(string $html, string $name): string
{
    return preg_match('/name="'.$name.'"[^>]*value="([^"]+)"/', $html, $m) ? $m[1] : '';
}

$category = Category::query()->firstOrFail();
$barcode = 'TB-CONC-'.strtoupper(Str::random(6));
$product = Product::create([
    'barcode' => $barcode,
    'name' => 'Produk Uji Konkurensi '.$barcode,
    'category_id' => $category->id,
    'unit' => 'pcs',
    'cost_price' => 50000,
    'sale_price' => 100000,
    'stock' => 1,
    'min_stock' => 1,
    'is_active' => true,
]);
$variant = ProductVariant::create([
    'product_id' => $product->id,
    'size' => 'S',
    'color' => 'Hitam',
    'stock' => 1,
    'is_active' => true,
]);

$users = [];
$handles = [];
$multi = curl_multi_init();
$payloads = [];

try {
    foreach ($ports as $i => $port) {
        $email = 'conc'.($i + 1).'-'.strtolower($barcode).'@tokotabita.test';
        $user = User::create([
            'name' => 'Pembeli Conc '.($i + 1),
            'email' => $email,
            'password' => Hash::make('Concurrency-123'),
            'role' => UserRole::Customer,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $users[] = $user;
        $cookie = $tempDir.'/cookie-'.$i.'.txt';

        $loginPage = request($port, 'GET', '/login', $cookie);
        $loginToken = token($loginPage['body']);
        $login = request($port, 'POST', '/login', $cookie, [
            '_token' => $loginToken,
            'email' => $email,
            'password' => 'Concurrency-123',
        ]);

        $productPage = request($port, 'GET', '/produk/'.$product->id, $cookie);
        $addToken = token($productPage['body']);
        $add = request($port, 'POST', '/keranjang/tambah', $cookie, [
            '_token' => $addToken,
            'product_id' => $product->id,
            'size' => 'S',
            'color' => 'Hitam',
            'qty' => 1,
        ]);

        $checkoutPage = request($port, 'GET', '/checkout', $cookie);
        $checkoutToken = token($checkoutPage['body']);
        $idempotency = field($checkoutPage['body'], 'idempotency_key') ?: 'conc-'.($i + 1).'-'.$barcode;

        $payloads[] = [
            'user' => $user,
            'port' => $port,
            'cookie' => $cookie,
            'token' => $checkoutToken,
            'fields' => [
                '_token' => $checkoutToken,
                'buyer_name' => 'Pembeli Conc '.($i + 1),
                'buyer_phone' => '0812345000'.($i + 1),
                'buyer_address' => 'Jl. Concurrency No. '.($i + 1).', Semarang',
                'shipping_zone' => 'jawa_tengah',
                'courier' => 'jne',
                'service' => 'REG',
                'payment_method' => 'transfer',
                'idempotency_key' => $idempotency,
            ],
        ];

        if ($login['status'] === 0 || $add['status'] === 0 || $checkoutPage['status'] === 0) {
            throw new RuntimeException('Server port '.$port.' tidak merespons. Pastikan 5 server sudah berjalan.');
        }
    }

    foreach ($payloads as $index => $payload) {
        $ch = curl_init('http://127.0.0.1:'.$payload['port'].'/checkout');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload['fields']),
            CURLOPT_COOKIEFILE => $payload['cookie'],
            CURLOPT_COOKIEJAR => $payload['cookie'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        curl_multi_add_handle($multi, $ch);
        $handles[$index] = $ch;
    }

    do {
        $running = null;
        curl_multi_exec($multi, $running);
        curl_multi_select($multi, 0.2);
    } while ($running > 0);

    $results = [];

    foreach ($handles as $index => $ch) {
        $raw = (string) curl_multi_getcontent($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($raw, 0, $headerSize);
        $location = preg_match('/^Location:\s*(\S+)/mi', $headers, $m) ? trim($m[1]) : '';

        $results[] = [
            'user' => $payloads[$index]['user']->email,
            'status' => $status,
            'location' => $location,
            'success' => str_contains($location, '/pembayaran/'),
        ];
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
    }

    $saleIds = SaleItem::query()
        ->where('product_id', $product->id)
        ->pluck('sale_id')
        ->unique()
        ->values()
        ->all();

    $summary = [
        'product_id' => $product->id,
        'requests' => count($results),
        'http_statuses' => array_column($results, 'status'),
        'sales_created' => count($saleIds),
        'variant_stock' => (int) $variant->fresh()->stock,
        'product_stock' => (int) $product->fresh()->stock,
        'oversell' => (int) $variant->fresh()->stock < 0 || (int) $product->fresh()->stock < 0,
        'result' => (count($saleIds) === 1
            && (int) $variant->fresh()->stock === 0
            && (int) $product->fresh()->stock === 0)
            ? 'PASS'
            : 'FAIL',
        'details' => $results,
    ];

    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;

    // Bersihkan data uji agar bisa dijalankan ulang.
    if ($saleIds !== []) {
        DB::table('customer_notifications')->whereIn('sale_id', $saleIds)->delete();
        DB::table('order_status_histories')->whereIn('sale_id', $saleIds)->delete();
        DB::table('payment_proofs')->whereIn('sale_id', $saleIds)->delete();
        DB::table('payments')->whereIn('sale_id', $saleIds)->delete();
        DB::table('payment_transactions')->whereIn('sale_id', $saleIds)->delete();
        DB::table('sale_items')->whereIn('sale_id', $saleIds)->delete();
        DB::table('sales')->whereIn('id', $saleIds)->delete();
    }

    Sale::query()->whereIn('id', $saleIds)->delete();
    ProductVariant::query()->where('product_id', $product->id)->delete();
    Product::query()->where('id', $product->id)->delete();
    User::query()->whereIn('id', array_map(fn ($u) => $u->id, $users))->delete();

    @array_map('unlink', glob($tempDir.'/cookie-*.txt') ?: []);
    @rmdir($tempDir);
} finally {
    curl_multi_close($multi);
}

exit(($summary['result'] ?? 'FAIL') === 'PASS' ? 0 : 1);
