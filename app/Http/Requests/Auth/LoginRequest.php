<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validasi dan proses login.
 *
 * Menangani:
 *  - validasi input,
 *  - pembatasan jumlah percobaan login (rate limiting),
 *  - penolakan akun yang berstatus nonaktif,
 *  - pembedaan pesan error agar tidak membocorkan keberadaan sebuah email.
 */
class LoginRequest extends FormRequest
{
    /** Halaman login gagal maksimal dalam satu menit. */
    public const MAX_ATTEMPTS = 5;

    /** Lama penguncian dalam detik. */
    public const DECAY_SECONDS = 60;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Coba autentikasi kredensial pengguna.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'email' => $this->string('email')->lower()->trim()->toString(),
            'password' => $this->string('password')->toString(),
        ];

        // Akun nonaktif ditolak di sini, sebelum percobaan password.
        $inactive = User::query()
            ->where('email', $credentials['email'])
            ->where('is_active', false)
            ->exists();

        if ($inactive) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.',
            ]);
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi yang dimasukkan salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Pastikan percobaan login belum melebihi batas.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => sprintf(
                'Terlalu banyak percobaan login. Silakan coba lagi dalam %d detik.',
                $seconds
            ),
        ]);
    }

    /**
     * Kunci unik per kombinasi email + alamat IP.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')).'|'.$this->ip()
        );
    }
}
