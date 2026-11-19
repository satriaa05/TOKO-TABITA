<?php

namespace Tests\Feature\Store;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uji header & footer toko serta pengaturan bisnis Owner.
 *
 * Fokus:
 *  - announcement bar benar-benar hilang dan tidak menyisakan markup;
 *  - footer hanya memuat Instagram, lokasi Semarang, dan logo pembayaran aktif;
 *  - nomor WhatsApp diambil dari satu sumber (settings/config) lalu dinormalisasi;
 *  - hanya Owner yang dapat mengubah pengaturan bisnis (admin ditolak 403);
 *  - input berbahaya (XSS) tetap di-escape dan URL Instagram dibatasi.
 */
class StorefrontChromeTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::query()->create([
            'name' => 'Owner Uji',
            'email' => 'owner.uji@tokotabita.test',
            'password' => 'password123',
            'role' => UserRole::Owner->value,
            'is_active' => true,
        ]);
    }

    private function admin(): User
    {
        return User::query()->create([
            'name' => 'Admin Uji',
            'email' => 'admin.uji@tokotabita.test',
            'password' => 'password123',
            'role' => UserRole::Admin->value,
            'is_active' => true,
        ]);
    }

    private function customer(): User
    {
        return User::query()->create([
            'name' => 'Pembeli Uji',
            'email' => 'pembeli.chrome@tokotabita.test',
            'password' => 'password123',
            'role' => UserRole::Customer->value,
            'is_active' => true,
        ]);
    }

    public function test_announcement_bar_hilang_dan_header_tetap_tampil(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('st-topbar', false);
        $response->assertDontSee('30-Day easy returns');
        $response->assertDontSee('CS 24/7');
        $response->assertSee('<header', false);
        $response->assertSee('Navigasi utama');
    }

    public function test_footer_hanya_menampilkan_instagram_dan_lokasi_semarang(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('bi-instagram', false);
        $response->assertDontSee('bi-facebook', false);
        $response->assertDontSee('bi-pinterest', false);
        $response->assertDontSee('bi-tiktok', false);
        $response->assertSee('Semarang, Jawa Tengah, Indonesia');
        $response->assertDontSee('Bandung, Jawa Barat');
    }

    public function test_default_hanya_metode_pembayaran_yang_didukung_yang_tampil(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Transfer Bank');
        $response->assertSee('bank-transfer.svg');
        $response->assertDontSee('qris.svg');
        $response->assertDontSee('visa.svg');
        $response->assertDontSee('mastercard.svg');
        $response->assertDontSee('st-pay">VISA', false);
    }

    public function test_whatsapp_memakai_nomor_default_dan_terencode_dengan_benar(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('wa.me/6283128814776', false);
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
        // Pesan di-encode (%20), bukan spasi mentah di dalam URL.
        $response->assertSee('?text=Halo%20Tabita%20Modiste', false);
    }

    public function test_pengaturan_owner_langsung_tampil_di_footer(): void
    {
        Setting::set('store_name', 'Tabita Modiste Semarang');
        Setting::set('store_tagline', 'Elegant Everyday');
        Setting::set('store_description', 'Busana wanita premium dari Semarang.');
        Setting::set('store_whatsapp', '0812-3456-7890');
        Setting::set('store_location', 'Semarang, Jawa Tengah, Indonesia');
        Setting::set('store_address', 'Jl. Pandanaran No. 10, Semarang');
        Setting::set('store_instagram_url', 'https://www.instagram.com/tabitamodiste');
        Setting::set('payment_methods', json_encode(['transfer', 'qris']));

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Tabita Modiste Semarang');
        $response->assertSee('Elegant Everyday');
        $response->assertSee('Busana wanita premium dari Semarang.');
        $response->assertSee('wa.me/6281234567890', false);
        $response->assertSee('https://www.instagram.com/tabitamodiste');
        $response->assertSee('bi-instagram', false);
        $response->assertSee('qris.svg');
        $response->assertSee('bank-transfer.svg');
        $response->assertDontSee('visa.svg');
    }

    public function test_instagram_tidak_valid_tidak_dirender_sebagai_tautan(): void
    {
        Setting::set('store_instagram_url', 'javascript:alert(1)');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('javascript:alert(1)', false);
        $response->assertSee('Instagram belum dikonfigurasi');
    }

    public function test_isi_pengaturan_di_escape_untuk_mencegah_xss(): void
    {
        Setting::set('store_name', '<script>alert(1)</script>');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_pengaturan_owner(): void
    {
        $this->get('/owner/pengaturan')->assertRedirect('/login');
    }

    public function test_admin_dan_pembeli_tidak_boleh_membuka_pengaturan_owner(): void
    {
        $this->actingAs($this->admin())
            ->get('/owner/pengaturan')
            ->assertForbidden();

        $this->actingAs($this->customer())
            ->get('/owner/pengaturan')
            ->assertForbidden();

        $this->actingAs($this->owner())
            ->get('/owner/pengaturan')
            ->assertOk()
            ->assertSee('Pengaturan Bisnis');
    }

    public function test_admin_tidak_boleh_menyimpan_pengaturan_bisnis(): void
    {
        $this->actingAs($this->admin())
            ->put('/owner/pengaturan', $this->payload())
            ->assertForbidden();
    }

    public function test_owner_dapat_menyimpan_pengaturan_dan_tercatat_di_audit_log(): void
    {
        $owner = $this->owner();

        $response = $this->actingAs($owner)
            ->put('/owner/pengaturan', $this->payload());

        $response->assertRedirect(route('owner.settings.index'));
        $response->assertSessionHas('success');

        $this->assertSame('Tabita Modiste', Setting::get('store_name'));
        $this->assertSame('Semarang, Jawa Tengah, Indonesia', Setting::get('store_location'));
        $this->assertSame('+62 831-2881-4776', Setting::get('store_whatsapp'));
        $this->assertSame('https://www.instagram.com/tabitamodiste', Setting::get('store_instagram_url'));
        $this->assertSame(['transfer', 'qris'], json_decode((string) Setting::get('payment_methods'), true));

        $log = AuditLog::query()->where('action', 'setting.business_update')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame($owner->id, $log->user_id);
    }

    public function test_owner_tidak_dapat_menyimpan_whatsapp_atau_instagram_tidak_valid(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->from('/owner/pengaturan')
            ->put('/owner/pengaturan', $this->payload(['store_whatsapp' => '12345']))
            ->assertRedirect('/owner/pengaturan')
            ->assertSessionHasErrors('store_whatsapp');

        $this->actingAs($owner)
            ->from('/owner/pengaturan')
            ->put('/owner/pengaturan', $this->payload([
                'store_instagram_url' => 'https://contoh-palsu.test/tabita',
            ]))
            ->assertRedirect('/owner/pengaturan')
            ->assertSessionHasErrors('store_instagram_url');
    }

    public function test_owner_wajib_memilih_minimal_satu_metode_pembayaran(): void
    {
        $this->actingAs($this->owner())
            ->from('/owner/pengaturan')
            ->put('/owner/pengaturan', $this->payload(['payment_methods' => []]))
            ->assertRedirect('/owner/pengaturan')
            ->assertSessionHasErrors('payment_methods');
    }

    public function test_owner_dapat_mengunggah_logo_pembayaran_kustom(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner())
            ->put('/owner/pengaturan', $this->payload([
                'payment_logos' => [
                    'qris' => new UploadedFile(
                        base_path('tests/fixtures/product.png'),
                        'qris-toko.png',
                        'image/png',
                        null,
                        true
                    ),
                ],
            ]))
            ->assertRedirect(route('owner.settings.index'));

        $path = (string) Setting::get('payment_logo_qris');

        $this->assertNotSame('', $path);
        Storage::disk('public')->assertExists($path);
    }

    /**
     * Payload valid untuk pengaturan bisnis.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'store_name' => 'Tabita Modiste',
            'store_tagline' => 'Fashion for Every You',
            'store_description' => 'Garment premium, harga terjangkau sejak 2010.',
            'store_whatsapp' => '+62 831-2881-4776',
            'store_instagram_url' => 'https://www.instagram.com/tabitamodiste',
            'store_location' => 'Semarang, Jawa Tengah, Indonesia',
            'store_address' => 'Jl. Pandanaran No. 10, Semarang',
            'store_operating_hours' => 'Senin – Sabtu, 09.00 – 17.00 WIB',
            'payment_methods' => ['transfer', 'qris'],
        ], $overrides);
    }
}
