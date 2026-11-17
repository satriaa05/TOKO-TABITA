<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Uji alur "Masuk dengan Google".
 *
 * Yang diuji:
 *  - Akun Google pertama membuat akun pembeli (role customer), bukan admin.
 *  - google_id tersimpan, dan akun kedua dengan Google yang sama memakai
 *    akun yang sudah ada (tidak membuat akun ganda).
 *  - Akun yang dinonaktifkan tidak boleh masuk.
 *  - Kegagalan Google (pengguna menolak izin, URI tidak terdaftar, state
 *    kedaluwarsa) menghasilkan pesan yang jelas, ditampilkan sebagai
 *    pengumuman terpisah, bukan sebagai galat di bawah kolom email.
 *  - Tombol Google mengirim redirect URI sesuai host yang sedang dipakai.
 */
class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Kredensial Google palsu: cukup untuk menguji alur, tidak pernah
        // menghubungi Google sungguhan. Tanpa ini, controller memakai jalur
        // "kredensial belum diisi" dan test lain ikut gagal.
        config([
            'services.google.client_id' => 'klien-id-uji.apps.googleusercontent.com',
            'services.google.client_secret' => 'rahasia-uji',
        ]);
    }

    /**
     * Palsukan balasan Google.
     *
     * Socialite memakai Guzzle sendiri (bukan facade Http), jadi provider-nya
     * dimock langsung agar tidak ada panggilan ke internet.
     */
    private function fakeGoogle(string $sub, string $email, string $name = 'Budi Santoso'): void
    {
        $socialiteUser = (new SocialiteUser())->map([
            'id' => $sub,
            'nickname' => strtolower(str_replace(' ', '.', $name)),
            'name' => $name,
            'email' => $email,
            'avatar' => 'https://example.test/foto.jpg',
        ]);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    /**
     * Panggil callback Google dengan parameter code & state yang sesuai.
     */
    private function callCallback(string $state = 'state-uji', string $code = 'kode-uji')
    {
        session(['state' => $state]);

        return $this->get(route('login.google.callback', [
            'state' => $state,
            'code' => $code,
        ]));
    }

    public function test_login_google_membuat_akun_pembeli_dan_tersimpan_google_id(): void
    {
        $this->fakeGoogle('google-123', 'budi@contoh.test');

        $response = $this->callCallback();

        $response->assertRedirect(route('store.catalog'));

        $user = User::query()->where('email', 'budi@contoh.test')->first();

        $this->assertNotNull($user, 'Akun pembeli harus dibuat.');
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertNotNull($user->password, 'Kolom password tidak boleh kosong.');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_google_kedua_tidak_membuat_akun_ganda(): void
    {
        $this->fakeGoogle('google-123', 'budi@contoh.test');
        $this->callCallback()->assertRedirect(route('store.catalog'));

        $this->post('/logout');
        $this->flushSession();

        $this->fakeGoogle('google-123', 'budi@contoh.test');
        $this->callCallback('state-kedua')->assertRedirect(route('store.catalog'));

        $this->assertSame(1, User::query()->where('email', 'budi@contoh.test')->count());
    }

    public function test_akun_yang_sudah_ada_dihubungkan_dengan_google(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@contoh.test',
            'password' => bcrypt('rahasia123'),
            'role' => UserRole::Customer,
            'is_active' => true,
        ]);

        $this->fakeGoogle('google-456', 'budi@contoh.test');
        $this->callCallback()->assertRedirect(route('store.catalog'));

        $this->assertSame('google-456', $user->refresh()->google_id);
        $this->assertSame(1, User::query()->where('email', 'budi@contoh.test')->count());
    }

    public function test_akun_nonaktif_tidak_bisa_masuk_lewat_google(): void
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => 'budi@contoh.test',
            'password' => bcrypt('rahasia123'),
            'role' => UserRole::Customer,
            'is_active' => false,
        ]);

        $this->fakeGoogle('google-789', 'budi@contoh.test');

        $response = $this->callCallback();

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');

        // Pesan harus jadi pengumuman terpisah, bukan galat di bawah kolom
        // email (tampilan rusak yang sempat dikeluhkan user).
        $this->assertFalse(
            $response->getSession()->has('errors')
                && $response->getSession()->get('errors')->has('email'),
            'Pesan akun nonaktif tidak boleh muncul sebagai galat kolom email.'
        );

        $this->assertGuest();
    }

    public function test_halaman_login_menampilkan_tombol_google_dengan_logo(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Continue with Google', false);
        // Logo digambar langsung sebagai SVG, bukan diambil dari internet.
        $response->assertSee('fill="#EA4335"', false);
        $response->assertDontSee('upload.wikimedia.org', false);
    }

    /**
     * Pengguna menolak izin di layar Google: pesan jelas, bukan galat fields.
     */
    public function test_google_menolak_izin_menampilkan_pesan_yang_jelas(): void
    {
        $response = $this->get(route('login.google.callback', [
            'error' => 'access_denied',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');

        // Pesan harus terpisah, bukan ditempel di bawah kolom email.
        $this->assertFalse(
            $response->getSession()->has('errors')
                && $response->getSession()->get('errors')->has('email'),
            'Pesan Google tidak boleh muncul sebagai galat kolom email.'
        );

        $this->assertGuest();
    }

    /**
     * URI balikan tidak terdaftar di Google Cloud Console: pesan menyebut URI.
     */
    public function test_uri_tidak_terdaftar_menyebut_uri_yang_harus_didaftarkan(): void
    {
        $response = $this->get(route('login.google.callback', [
            'error' => 'redirect_uri_mismatch',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');

        $pesan = (string) $response->getSession()->get('auth_error');
        $this->assertStringContainsString('/login/google/callback', $pesan);
    }

    /**
     * State/sesi tidak cocok (tab lama atau alamat situs berubah).
     */
    public function test_state_kedaluwarsa_menampilkan_pesan_khusus(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andThrow(new InvalidStateException('state tidak cocok'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->callCallback();

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_error');

        $pesan = (string) $response->getSession()->get('auth_error');
        $this->assertStringContainsString('kedaluwarsa', $pesan);
        $this->assertGuest();
    }

    /**
     * Tombol Google harus mengirim redirect URI sesuai host yang dipakai,
     * supaya sesi browser tidak putus di tengah perjalanan.
     */
    public function test_tombol_google_mengirim_uri_sesuai_host_yang_dipakai(): void
    {
        $response = $this->get(route('login.google'));

        $response->assertRedirect();
        $tujuan = (string) $response->headers->get('Location');

        $this->assertStringContainsString('accounts.google.com', $tujuan);
        $this->assertStringContainsString(
            'redirect_uri='.urlencode(url('/login/google/callback')),
            $tujuan
        );
    }

    /**
     * Halaman login menampilkan pengumuman error Google sebagai blok terpisah.
     */
    public function test_halaman_login_menampilkan_pesan_error_google_sebagai_pengumuman(): void
    {
        $response = $this->withSession(['auth_error' => 'Sesi login Google sudah kedaluwarsa.'])
            ->get(route('login'));

        $response->assertOk();
        $response->assertSee('Sesi login Google sudah kedaluwarsa.');
    }
}
