<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

/**
 * Masuk memakai akun Google (OAuth).
 *
 * Aturan penting:
 *  - Akun dibuat otomatis sebagai "customer" (pembeli), bukan admin/owner.
 *  - Kolom `password` di database tidak boleh NULL, jadi untuk akun Google
 *    dibuat kata sandi acak yang tidak diketahui siapa pun. Dengan begitu
 *    akun Google tetap tidak bisa dimasuki lewat form biasa.
 *  - Login kedua dengan Google yang sama memakai akun yang sudah ada,
 *    tidak membuat akun ganda.
 *  - Pesan error Google ditampilkan sebagai pengumuman terpisah di atas
 *    form, bukan sebagai galat di bawah kolom email (yang membingungkan).
 */
class GoogleAuthController extends Controller
{
    /**
     * Arahkan pengunjung ke halaman persetujuan Google.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (blank($clientId) || blank($clientSecret)) {
            report(new \RuntimeException('Google OAuth belum dikonfigurasi: GOOGLE_CLIENT_ID atau GOOGLE_CLIENT_SECRET kosong di .env.'));

            return redirect()->route('login')
                ->with('auth_error', 'Login dengan Google belum siap. Silakan hubungi admin toko untuk mengonfigurasi kredensial Google.');
        }

        try {
            $provider = Socialite::driver('google');

            // Redirect URI mengikuti host yang sedang dipakai (localhost atau
            // 127.0.0.1). Kalau berbeda, sesi browser tidak ikut dan
            // Google membalas "invalid state".
            $this->setRedirectUri($provider, $this->redirectUri($request));

            return $provider->redirect();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')
                ->with('auth_error', 'Tidak bisa menghubungi Google. Silakan coba lagi sebentar lagi.');
        }
    }

    /**
     * Balikan dari Google: cari atau buat akun, lalu loginkan.
     */
    public function callback(Request $request): RedirectResponse
    {
        // Google mengirim parameter "error" kalau pengguna menolak izin atau
        // URI tidak terdaftar. Pesannya lebih jelas daripada error teknis.
        if ($request->filled('error')) {
            $messages = [
                'access_denied' => 'Anda membatalkan login dengan Google. Silakan coba lagi bila mau masuk.',
                'redirect_uri_mismatch' => 'Redirect URI Google belum terdaftar. Admin perlu mendaftarkan URI ini di Google Cloud Console: '.$this->redirectUri($request),
                'invalid_scope' => 'Izin yang diminta Google tidak sesuai. Silakan hubungi admin toko.',
                'unauthorized_client' => 'Kredensial Google belum diizinkan untuk aplikasi ini. Silakan hubungi admin toko.',
            ];

            $kode = (string) $request->query('error');

            return redirect()->route('login')
                ->with('auth_error', $messages[$kode] ?? 'Login dengan Google gagal. Silakan coba lagi.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            // Sesi/state tidak cocok: biasanya tab sudah lama dibuka, atau
            // alamat situs diganti antara klik tombol dan balikan Google.
            report($e);

            return redirect()->route('login')
                ->with('auth_error', 'Sesi login Google sudah kedaluwarsa. Silakan klik lagi tombol Continue with Google.');
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')
                ->with('auth_error', 'Login dengan Google gagal atau dibatalkan. Silakan coba lagi.');
        }

        $googleId = (string) $googleUser->getId();
        $email = $googleUser->getEmail();

        if ($email === null || $email === '') {
            return redirect()->route('login')
                ->with('auth_error', 'Akun Google ini tidak punya email. Pilih akun Google lain yang punya email.');
        }

        $user = User::query()->where('google_id', $googleId)->first()
            ?? User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'avatar' => $googleUser->getAvatar(),
                // Kata sandi acak: kolomnya tidak boleh NULL, dan akun ini
                // tidak boleh bisa dipakai masuk lewat form biasa.
                'password' => Hash::make(Str::random(40)),
                'role' => UserRole::Customer,
                'is_active' => true,
            ]);
        } else {
            // Akun lama (misalnya yang sudah daftar lewat form) disambung
            // ke akun Google-nya, lalu peran dipastikan pembeli.
            $user->update([
                'google_id' => $googleId,
                'avatar' => $googleUser->getAvatar() ?: $user->avatar,
                'role' => $user->role ?? UserRole::Customer,
            ]);
        }

        if (! $user->is_active) {
            // Pesan ditampilkan sebagai pengumuman terpisah, bukan sebagai
            // galat di bawah kolom email (tampilan rusak yang dikeluhkan user).
            return redirect()->route('login')
                ->with('auth_error', 'Akun ini dinonaktifkan. Silakan hubungi admin toko.');
        }

        $user->update(['last_login_at' => now()]);

        Auth::login($user, true);

        return redirect()->intended(route('store.catalog'))
            ->with('success', 'Selamat datang, '.$user->name.'!');
    }

    /**
     * URI balikan Google mengikuti host yang sedang dipakai.
     *
     * Kalau URI berbeda dengan yang didaftarkan di Google Cloud Console,
     * sesi browser tidak ikut dan Google membalas "invalid state".
     */
    private function redirectUri(Request $request): string
    {
        return url('/login/google/callback');
    }

    /**
     * Set URI balikan pada provider Socialite.
     *
     * Nama method-nya berbeda antar versi Socialite:
     *  - Socialite 5.x: redirectUrl()
     *  - Socialite 4.x: setRedirectUrl()
     *
     * Pakai yang tersedia, supaya tidak muncul error "call to undefined
     * method" yang membuat login Google selalu gagal.
     */
    private function setRedirectUri(object $provider, string $uri): void
    {
        if (method_exists($provider, 'setRedirectUrl')) {
            $provider->setRedirectUrl($uri);

            return;
        }

        if (method_exists($provider, 'redirectUrl')) {
            $provider->redirectUrl($uri);
        }
    }
}
