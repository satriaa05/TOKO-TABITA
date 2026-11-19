<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Profil toko publik (nama, kontak, lokasi, sosial media, metode pembayaran).
 *
 * Sumber data, berurutan dari yang paling diutamakan:
 *   1. tabel `settings` (diubah Owner lewat menu Pengaturan Bisnis),
 *   2. config/pos.php (nilai bawaan / environment).
 *
 * Seluruh nilai dibaca sekali per request (satu query), lalu di-memo-kan di
 * instance ini. Kelas ini di-bind sebagai `scoped` di AppServiceProvider
 * sehingga Blade, controller, dan helper memakai data yang sama.
 */
class StoreProfile
{
    /** @var array<string, string|null>|null */
    private ?array $values = null;

    /**
     * Ambil seluruh nilai settings sekali jalan.
     *
     * Dibungkus try/catch agar halaman error tetap bisa dirender ketika
     * database belum siap (mis. saat instalasi atau migrasi).
     *
     * @return array<string, string|null>
     */
    private function values(): array
    {
        if ($this->values === null) {
            try {
                $this->values = Setting::query()->pluck('value', 'key')->all();
            } catch (\Throwable) {
                $this->values = [];
            }
        }

        return $this->values;
    }

    /**
     * Nilai setting dengan fallback; string kosong dianggap "belum diatur".
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->values()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    public function name(): string
    {
        return (string) $this->get('store_name', config('pos.store.name', 'Tabita Modiste'));
    }

    public function tagline(): string
    {
        return (string) $this->get('store_tagline', config('pos.store.tagline', 'Fashion for Every You'));
    }

    public function description(): string
    {
        return (string) $this->get(
            'store_description',
            config('pos.store.description', 'Garment premium, harga terjangkau sejak 2010.')
        );
    }

    /**
     * Nomor WhatsApp apa adanya (untuk ditampilkan ke pengunjung).
     */
    public function whatsappDisplay(): ?string
    {
        $raw = (string) $this->get('store_whatsapp', config('pos.store.whatsapp', ''));

        if ($raw === '') {
            $raw = (string) config('pos.store.whatsapp', '');
        }

        $number = self::normalizeWhatsApp($raw);

        return $number !== null ? self::formatWhatsApp($number) : ($raw !== '' ? $raw : null);
    }

    /**
     * Nomor WhatsApp siap pakai untuk wa.me (digit, awalan 62, tanpa simbol).
     */
    public function whatsappNumber(): ?string
    {
        $raw = (string) $this->get('store_whatsapp', config('pos.store.whatsapp', ''));

        if ($raw === '') {
            $raw = (string) config('pos.store.whatsapp', '');
        }

        return self::normalizeWhatsApp($raw);
    }

    /**
     * Tautan WhatsApp dengan pesan terisi. Null bila nomor belum valid.
     */
    public function whatsappUrl(string $message = ''): ?string
    {
        $number = $this->whatsappNumber();

        if ($number === null) {
            return null;
        }

        $url = 'https://wa.me/'.$number;

        return $message !== '' ? $url.'?text='.rawurlencode($message) : $url;
    }

    /**
     * Normalisasi nomor Indonesia ke format internasional tanpa "+".
     * Contoh: "+62 831-2881-4776", "0831 2881 4776", "83128814776"
     * semuanya menjadi "6283128814776".
     */
    public static function normalizeWhatsApp(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        // 62 + 8..13 digit nomor pelanggan = panjang total 10..15 digit.
        if (! preg_match('/^62\d{8,13}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * Tampilan ramah: 6283128814776 -> "+62 831-2881-4776".
     */
    public static function formatWhatsApp(string $number): string
    {
        if (preg_match('/^62(\d{3})(\d{4})(\d+)$/', $number, $matches) === 1) {
            return '+62 '.$matches[1].'-'.$matches[2].'-'.$matches[3];
        }

        return '+'.$number;
    }

    public function location(): string
    {
        return (string) $this->get('store_location', config('pos.store.location', 'Semarang, Jawa Tengah, Indonesia'));
    }

    public function address(): ?string
    {
        $address = trim((string) $this->get('store_address', config('pos.store.address', '')));

        return $address !== '' ? $address : null;
    }

    public function operatingHours(): ?string
    {
        $hours = trim((string) $this->get('store_operating_hours', config('pos.store.operating_hours', '')));

        return $hours !== '' ? $hours : null;
    }

    /**
     * URL Instagram resmi. Hanya menerima http/https dan host instagram.com,
     * sehingga tidak bisa dipakai untuk menyisipkan skema berbahaya.
     */
    public function instagramUrl(): ?string
    {
        $url = trim((string) $this->get('store_instagram_url', config('pos.store.instagram_url', '')));

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        if (! in_array($host, ['instagram.com', 'www.instagram.com'], true)) {
            return null;
        }

        return $url;
    }

    /**
     * Katalog metode pembayaran yang dikenal aplikasi.
     *
     * @return array<string, array{label: string, logo: string, hint: string}>
     */
    public function paymentCatalog(): array
    {
        return (array) config('pos.payments.methods', []);
    }

    /**
     * Key metode pembayaran yang benar-benar aktif di toko.
     *
     * @return array<int, string>
     */
    public function paymentMethodKeys(): array
    {
        $raw = $this->get('payment_methods');

        $keys = is_string($raw) ? json_decode($raw, true) : null;

        if (! is_array($keys)) {
            $keys = (array) config('pos.payments.active', ['transfer']);
        }

        $catalog = array_keys($this->paymentCatalog());

        return array_values(array_unique(array_filter(
            array_intersect($keys, $catalog),
            static fn ($key): bool => is_string($key)
        )));
    }

    /**
     * Data metode pembayaran aktif siap render (label, logo, hint).
     *
     * @return array<int, array{key: string, label: string, logo: string, hint: string}>
     */
    public function paymentMethods(): array
    {
        $catalog = $this->paymentCatalog();

        $methods = [];

        foreach ($this->paymentMethodKeys() as $key) {
            if (! isset($catalog[$key])) {
                continue;
            }

            $methods[] = [
                'key' => $key,
                'label' => (string) ($catalog[$key]['label'] ?? ucfirst($key)),
                'logo' => $this->paymentLogoUrl($key),
                'hint' => (string) ($catalog[$key]['hint'] ?? ''),
            ];
        }

        return $methods;
    }

    /**
     * Label metode aktif, contoh: "Transfer Bank, QRIS".
     */
    public function paymentMethodLabels(): string
    {
        return implode(', ', array_column($this->paymentMethods(), 'label'));
    }

    /**
     * URL logo: pakai unggahan Owner bila ada, jika tidak pakai aset bawaan.
     */
    public function paymentLogoUrl(string $key): string
    {
        $override = trim((string) $this->get('payment_logo_'.$key, ''));

        if ($override !== '') {
            return Storage::disk('public')->url($override);
        }

        $path = (string) (config('pos.payments.methods.'.$key.'.logo') ?? '');

        return $path !== '' ? asset($path) : '';
    }
}
