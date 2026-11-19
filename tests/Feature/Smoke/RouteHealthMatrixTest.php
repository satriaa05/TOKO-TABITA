<?php

namespace Tests\Feature\Smoke;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Uji kesehatan seluruh halaman untuk setiap peran.
 *
 * Tujuannya sederhana: tidak boleh ada halaman yang balas HTTP 500.
 * Halaman yang memang tidak boleh diakses peran tertentu boleh balas
 * 403 (dilarang) atau 302 (diarahkan ke login), tetapi TIDAK boleh 500.
 *
 * Uji ini adalah jaring pengaman: begitu ada controller atau view yang
 * lupa mengirim variabel, atau query yang tidak cocok dengan database,
 * halaman itu langsung terdeteksi.
 */
class RouteHealthMatrixTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private User $customer;

    private Product $product;

    private Product $productDenganVarian;

    private Category $category;

    private Promotion $promotion;

    private Sale $pesanan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();
        $this->owner = User::query()->where('role', UserRole::Owner->value)->firstOrFail();

        // Seeder hanya membuat akun admin & owner, jadi pembeli dibuat di sini.
        $this->customer = User::query()->create([
            'name' => 'Pembeli Uji',
            'email' => 'pembeli.uji@tokotabita.test',
            'password' => 'password123',
            'role' => UserRole::Customer->value,
            'is_active' => true,
        ]);

        $this->category = Category::query()->where('is_active', true)->firstOrFail();
        $this->product = Product::query()->where('is_active', true)->firstOrFail();
        $this->productDenganVarian = Product::query()
            ->has('variants')
            ->where('is_active', true)
            ->first() ?? $this->product;

        // Seeder tidak membuat promo & pesanan, jadi keduanya dibuat di sini
        // supaya halaman admin untuk keduanya ikut teruji.
        $this->promotion = Promotion::query()->create([
            'name' => 'Promo Uji 10%',
            'type' => 'percent',
            'value' => 10,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'min_purchase' => 100000,
            'is_active' => true,
        ]);

        $this->pesanan = Sale::query()->create([
            'invoice_number' => 'INV-UJI-0001',
            'user_id' => $this->customer->id,
            'buyer_name' => 'Pembeli Uji',
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Uji No. 1, Jakarta',
            'subtotal' => 200000,
            'total' => 200000,
            'paid_amount' => 200000,
            'status' => 'pending_payment',
        ]);

        // Transaksi SELESAI beserta itemnya. Tanpa ini laporan owner
        // tetap kosong sehingga error pada tabel laporan tidak terlihat.
        $selesai = Sale::query()->create([
            'invoice_number' => 'INV-UJI-0002',
            'user_id' => $this->customer->id,
            'buyer_name' => 'Pembeli Uji',
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Uji No. 1, Jakarta',
            'subtotal' => 200000,
            'total' => 200000,
            'paid_amount' => 200000,
            'status' => 'completed',
        ]);

        SaleItem::query()->create([
            'sale_id' => $selesai->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'barcode' => $this->product->barcode,
            'qty' => 2,
            'price' => 100000,
            'cost_price' => 60000,
            'subtotal' => 200000,
            'total' => 200000,
        ]);

        // Kasus yang dulu bikin ErrorException: produk yang sudah dihapus
        // (soft delete) tapi masih ada di baris transaksi. Relasi produk
        // jadi null, dan laporan harus tetap bisa dibuka.
        $produkTerhapus = Product::query()->create([
            'barcode' => 'TB-HAPUS-001',
            'name' => 'Produk Sudah Dihapus',
            'category_id' => $this->category->id,
            'unit' => 'pcs',
            'cost_price' => 50000,
            'sale_price' => 80000,
            'stock' => 0,
            'min_stock' => 2,
            'is_active' => true,
        ]);

        $terhapus = Sale::query()->create([
            'invoice_number' => 'INV-UJI-0003',
            'user_id' => $this->customer->id,
            'buyer_name' => 'Pembeli Uji',
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Uji No. 1, Jakarta',
            'subtotal' => 80000,
            'total' => 80000,
            'paid_amount' => 80000,
            'status' => 'completed',
        ]);

        SaleItem::query()->create([
            'sale_id' => $terhapus->id,
            'product_id' => $produkTerhapus->id,
            'product_name' => $produkTerhapus->name,
            'barcode' => $produkTerhapus->barcode,
            'qty' => 1,
            'price' => 80000,
            'cost_price' => 50000,
            'subtotal' => 80000,
            'total' => 80000,
        ]);

        $produkTerhapus->delete();
    }

    /**
     * Alamat setiap halaman GET, dengan parameter diganti data nyata.
     *
     * @return array<string, string>
     */
    private function daftarHalaman(): array
    {
        $promosi = Promotion::query()->first();
        $pesanan = Sale::query()->first();

        return [
            'beranda' => '/',
            'katalog' => '/katalog',
            'detail produk' => '/produk/'.$this->product->id,
            'panduan ukuran' => '/panduan-ukuran',
            'pengiriman' => '/pengiriman-pengembalian',
            'tentang' => '/tentang',
            'login' => '/login',
            'login google' => '/login/google',
            'login google callback' => '/login/google/callback',
            'register' => '/register',

            'keranjang' => '/keranjang',
            'checkout' => '/checkout',
            'dashboard umum' => '/dashboard',
            'profil' => '/profil',
            'invoice' => '/invoice/1',
            'pesanan saya' => '/pesanan-saya',
            'detail pesanan saya' => '/pesanan-saya/'.$this->pesanan->id,
            'lacak pesanan' => '/pesanan-saya/'.$this->pesanan->id.'/lacak',
            'lacak pesanan partial' => '/pesanan-saya/'.$this->pesanan->id.'/lacak/partial',
            'halaman pesanan selesai' => '/pesanan/selesai/'.$this->pesanan->id,
            'wishlist' => '/wishlist',
            'notifikasi' => '/notifikasi',
            'alamat' => '/alamat',
            'tambah alamat' => '/alamat/tambah',
            'edit alamat' => '/alamat/1/edit',
            'pembayaran' => '/pembayaran/'.$this->pesanan->id,
            'hapus voucher' => '/voucher/hapus',

            'dashboard admin' => '/admin/dashboard',
            'daftar produk' => '/admin/produk',
            'tambah produk' => '/admin/produk/tambah',
            'detail produk admin' => '/admin/produk/'.$this->product->id,
            'edit produk' => '/admin/produk/'.$this->product->id.'/edit',
            'label barcode' => '/admin/produk/'.$this->product->id.'/label',
            'cetak label' => '/admin/produk/label/cetak',
            'generate barcode' => '/admin/produk/barcode/generate',
            'daftar kategori' => '/admin/kategori',
            'daftar pesanan' => '/admin/pesanan',
            'detail pesanan' => '/admin/pesanan/'.$this->pesanan->id,
            'pembayaran admin' => '/admin/pembayaran',
            'detail pembayaran admin' => '/admin/pembayaran/'.$this->pesanan->id,
            'pengiriman admin' => '/admin/pengiriman',
            'tambah pengiriman' => '/admin/pengiriman/'.$this->pesanan->id.'/tambah',
            'detail pengiriman' => '/admin/pengiriman/detail/1',
            'daftar promo' => '/admin/promo',
            'edit promo' => '/admin/promo/'.$this->promotion->id.'/edit',
            'tambah promo' => '/admin/promo/tambah',
            'tampilan toko' => '/admin/tampilan',

            'dashboard owner' => '/owner/dashboard',
            'monitoring owner' => '/owner/monitoring',
            'kelola akun owner' => '/owner/pengguna',
            'tambah akun owner' => '/owner/pengguna/tambah',
            'edit akun owner' => '/owner/pengguna/1/edit',
            'laporan penjualan' => '/owner/laporan/penjualan',
            'laporan produk' => '/owner/laporan/produk',
            'laporan inventory' => '/owner/laporan/inventori',
            'laporan laba kotor' => '/owner/laporan/laba-kotor',
            'pengaturan bisnis' => '/owner/pengaturan',
        ];
    }

    /**
     * Setiap peran membuka setiap halaman; tidak boleh ada yang balas 500.
     */
    public function test_tidak_ada_halaman_yang_balas_500(): void
    {
        $peran = [
            'admin' => $this->admin,
            'owner' => $this->owner,
            'pembeli' => $this->customer,
            'tamu' => null,
        ];

        $gagal = [];

        foreach ($peran as $namaPeran => $user) {
            foreach ($this->daftarHalaman() as $namaHalaman => $url) {
                $response = $user !== null
                    ? $this->actingAs($user)->get($url)
                    : $this->get($url);

                if ($response->getStatusCode() >= 500) {
                    $pesan = $response->exception?->getMessage() ?? '(tanpa pesan)';
                    $gagal[] = $namaPeran.' -> '.$namaHalaman.' ('.$url.') balas '
                        .$response->getStatusCode().': '.substr(preg_replace('/\s+/', ' ', $pesan), 0, 200);
                }
            }
        }

        $this->assertSame([], $gagal, "Halaman yang balas 500:\n".implode("\n", $gagal));
    }

    /**
     * Route GET yang ada tapi belum masuk daftar uji di atas.
     */
    public function test_semua_route_get_tercakup_daftar_uji(): void
    {
        $terdaftar = array_values($this->daftarHalaman());

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            // Route teknis Laravel saja yang tidak diuji di sini.
            if (str_starts_with($uri, 'storage/') || str_starts_with($uri, 'up')) {
                continue;
            }

            $sudah = false;
            foreach ($terdaftar as $alamat) {
                if (str_contains($alamat, '{') || $alamat === '/'.$uri || $alamat === $uri) {
                    $sudah = true;
                    break;
                }
            }

            // Cocokkan dengan pola (mis. /produk/{id} vs /produk/1)
            $pola = '#^'.preg_replace('#\{[^}]+\}#', '[0-9]+', '/'.trim($uri, '/')).'$#';

            foreach ($terdaftar as $alamat) {
                if (preg_match($pola, $alamat)) {
                    $sudah = true;
                    break;
                }
            }

            $this->assertTrue($sudah, "Route GET belum diuji: /".$uri);
        }
    }
}
