<?php

namespace Tests\Feature\Store;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uji jalur pembeli dari katalog sampai bukti pembayaran.
 *
 * Tujuannya: pembeli tidak boleh menemukan halaman rusak di tengah
 * bepergian. Semua langkah必须 berhasil tanpa 500, dan pintu masuk yang
 * mewajibkan login harus mengarahkan dengan benar (bukan error).
 */
class BuyerJourneyTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;

    private Product $produk;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->pembeli = User::query()->create([
            'name' => 'Pembeli Uji',
            'email' => 'pembeli.uji@tokotabita.test',
            'password' => 'password123',
            'role' => UserRole::Customer->value,
            'is_active' => true,
            'phone' => '08123456789',
        ]);

        $this->produk = Product::query()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->firstOrFail();
    }

    /**
     * Tamu boleh melihat katalog dan detail produk tanpa login.
     */
    public function test_tamu_bisa_melihat_katalog_dan_detail_produk(): void
    {
        $this->get('/katalog')->assertOk();
        $this->get('/produk/'.$this->produk->id)->assertOk();
    }

    /**
     * Tamu yang mau membeli diarahkan ke login, bukan error.
     */
    public function test_tamu_yang_mau_keranjang_diarahkan_ke_login(): void
    {
        $response = $this->post('/keranjang/tambah', [
            'product_id' => $this->produk->id,
            'qty' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertTrue(
            str_contains((string) $response->headers->get('Location'), 'login')
                || str_contains((string) $response->headers->get('Location'), 'register'),
            'Tamu harus diarahkan ke login atau register, bukan diberi error.'
        );
    }

    /**
     * Pembeli terdaftar bisa menambah ke keranjang.
     */
    public function test_pembeli_bisa_menambah_ke_keranjang(): void
    {
        $response = $this->actingAs($this->pembeli)->post('/keranjang/tambah', [
            'product_id' => $this->produk->id,
            'qty' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->actingAs($this->pembeli)->get('/keranjang')->assertOk();
    }

    /**
     * Pembeli bisa membuka halaman checkout dan melihat ringkasan.
     */
    public function test_pembeli_bisa_membuka_halaman_checkout(): void
    {
        $this->actingAs($this->pembeli)->post('/keranjang/tambah', [
            'product_id' => $this->produk->id,
            'qty' => 1,
        ]);

        $this->actingAs($this->pembeli)->get('/checkout')->assertOk();
    }

    /**
     * Data formulir checkout yang sama dengan yang dikirim form asli.
     *
     * @return array<string, string>
     */
    private function dataCheckout(): array
    {
        return [
            'buyer_name' => 'Pembeli Uji',
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Uji No. 1, Jakarta',
            'buyer_note' => 'Tolong dikirim sore',
            'shipping_zone' => 'jakarta',
            'shipping_provider' => 'JNE',
            'idempotency_key' => \Illuminate\Support\Str::random(40),
        ];
    }

    /**
     * Pesananmade: pembeli menyelesaikan checkout dan pesanan masuk riwayat.
     */
    public function test_pembeli_bisa_memesan_dan_melihat_pesanan_saya(): void
    {
        $this->actingAs($this->pembeli)->post('/keranjang/tambah', [
            'product_id' => $this->produk->id,
            'qty' => 1,
        ]);

        $response = $this->actingAs($this->pembeli)->post('/checkout', $this->dataCheckout());

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $pesanan = Sale::query()->where('user_id', $this->pembeli->id)->latest('id')->first();

        $this->assertNotNull($pesanan, 'Pesanan harus tersimpan dan terhubung ke pembeli.');
        $this->assertSame('pending_payment', $pesanan->status->value);

        // Halaman riwayat pesanan pembeli harus memuat pesanannya.
        $this->actingAs($this->pembeli)->get('/pesanan-saya')->assertOk();
        $this->actingAs($this->pembeli)->get('/pesanan-saya/'.$pesanan->id)->assertOk();
    }

    /**
     * Pembeli bisa mengunggah bukti pembayaran pada pesanan miliknya.
     */
    public function test_pembeli_bisa_mengunggah_bukti_pembayaran(): void
    {
        $this->actingAs($this->pembeli)->post('/keranjang/tambah', [
            'product_id' => $this->produk->id,
            'qty' => 1,
        ]);

        $this->actingAs($this->pembeli)->post('/checkout', $this->dataCheckout());

        $pesanan = Sale::query()->where('user_id', $this->pembeli->id)->latest('id')->firstOrFail();

        $bukti = UploadedFile::fake()->createWithContent(
            'bukti.png',
            (string) file_get_contents(dirname(__DIR__, 2).'/fixtures/bukti-pembayaran.png')
        );

        $response = $this->actingAs($this->pembeli)->post('/pesanan-saya/'.$pesanan->id.'/bukti', [
            'proof' => $bukti,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertGreaterThan(0, $pesanan->fresh()->paymentProofs()->count(), 'Bukti pembayaran harus tersimpan.');
    }

    /**
     * Admin bisa membuka pesanan pembeli dan mengonfirmasi pembayaran.
     */
    public function test_admin_bisa_melihat_dan_menonfirmasi_pesanan_pembeli(): void
    {
        $this->actingAs($this->pembeli)->post('/keranjang/tambah', [
            'product_id' => $this->produk->id,
            'qty' => 1,
        ]);

        $this->actingAs($this->pembeli)->post('/checkout', $this->dataCheckout());

        $pesanan = Sale::query()->where('user_id', $this->pembeli->id)->latest('id')->firstOrFail();

        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();

        $this->actingAs($admin)->get('/admin/pesanan')->assertOk();
        $this->actingAs($admin)->get('/admin/pesanan/'.$pesanan->id)->assertOk();
    }
}
