<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\StoreProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pengaturan bisnis yang tampil di halaman publik (header & footer toko).
 *
 * Hanya Owner yang boleh mengubah identitas resmi toko: nama, WhatsApp,
 * Instagram, lokasi, dan metode pembayaran. Admin tidak memiliki akses ke
 * route ini (dijaga middleware `role:owner` di routes/web.php), sehingga
 * nomor WhatsApp / kanal resmi tidak bisa diubah dari akun operasional.
 */
class StoreSettingController extends Controller
{
    /**
     * Daftar setting yang dikelola dari halaman ini.
     *
     * @var array<int, string>
     */
    private const EDITABLE_KEYS = [
        'store_name',
        'store_tagline',
        'store_description',
        'store_whatsapp',
        'store_instagram_url',
        'store_location',
        'store_address',
        'store_operating_hours',
        'about_eyebrow',
        'about_title',
        'about_intro',
        'about_story',
        'about_closure',
        'about_features_title',
        'about_features',
        'shipping_default_cost',
        'shipping_max_cost',
        'shipping_free_threshold',
        'shipping_zones',
        'shipping_eta_zones',
        'shipping_eta_instant',
        'payment_methods',
    ];

    public function index(StoreProfile $profile): View
    {
        $settings = Setting::query()
            ->whereIn('key', self::EDITABLE_KEYS)
            ->pluck('value', 'key');

        $catalog = $profile->paymentCatalog();
        $currentLogos = [];

        foreach (array_keys($catalog) as $key) {
            $currentLogos[$key] = $profile->paymentLogoUrl($key);
        }

        return view('owner.settings.index', [
            'settings' => $settings,
            'catalog' => $catalog,
            'activePayments' => $profile->paymentMethodKeys(),
            'currentLogos' => $currentLogos,
            'profile' => $profile,
        ]);
    }

    public function update(Request $request, StoreProfile $profile): RedirectResponse
    {
        $catalogKeys = array_keys($profile->paymentCatalog());

        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:150'],
            'store_tagline' => ['nullable', 'string', 'max:100'],
            'store_description' => ['nullable', 'string', 'max:255'],
            'store_whatsapp' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{8,30}$/'],
            'store_instagram_url' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'store_location' => ['required', 'string', 'max:150'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'store_operating_hours' => ['nullable', 'string', 'max:150'],
            'about_eyebrow' => ['nullable', 'string', 'max:80'],
            'about_title' => ['nullable', 'string', 'max:150'],
            'about_intro' => ['nullable', 'string', 'max:1000'],
            'about_story' => ['nullable', 'string', 'max:1000'],
            'about_closure' => ['nullable', 'string', 'max:1000'],
            'about_features_title' => ['nullable', 'string', 'max:150'],
            'about_features' => ['nullable', 'string', 'max:3000'],
            'shipping_default_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'shipping_max_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'shipping_free_threshold' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'shipping_zones' => ['nullable', 'string', 'max:3000'],
            'shipping_eta_zones' => ['nullable', 'string', 'max:3000'],
            'shipping_eta_instant' => ['nullable', 'string', 'max:30'],
            'payment_methods' => ['required', 'array', 'min:1'],
            'payment_methods.*' => ['string', Rule::in($catalogKeys)],
            'payment_logos' => ['nullable', 'array'],
            'payment_logos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'remove_payment_logos' => ['nullable', 'array'],
            'remove_payment_logos.*' => ['string', Rule::in($catalogKeys)],
        ], [
            'store_whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda +, tanda hubung, atau tanda kurung.',
            'payment_methods.required' => 'Pilih minimal satu metode pembayaran yang benar-benar didukung toko.',
            'payment_methods.min' => 'Pilih minimal satu metode pembayaran yang benar-benar didukung toko.',
        ]);

        foreach (['shipping_zones', 'shipping_eta_zones'] as $jsonKey) {
            if (! empty($data[$jsonKey]) && ! is_array(json_decode($data[$jsonKey], true))) {
                throw ValidationException::withMessages([
                    $jsonKey => 'Format JSON tidak valid.',
                ]);
            }
        }

        // Nomor harus bisa dinormalisasi ke format wa.me (62 + 8..13 digit).
        if (StoreProfile::normalizeWhatsApp($data['store_whatsapp']) === null) {
            throw ValidationException::withMessages([
                'store_whatsapp' => 'Nomor WhatsApp tidak valid. Contoh format: +62 831-2881-4776.',
            ]);
        }

        // Instagram hanya boleh mengarah ke domain Instagram resmi.
        if (! empty($data['store_instagram_url'])) {
            $host = strtolower((string) parse_url($data['store_instagram_url'], PHP_URL_HOST));

            if (! in_array($host, ['instagram.com', 'www.instagram.com'], true)) {
                throw ValidationException::withMessages([
                    'store_instagram_url' => 'URL Instagram harus memakai domain instagram.com, contoh: https://www.instagram.com/namatoko.',
                ]);
            }
        }

        $oldValues = Setting::query()
            ->whereIn('key', self::EDITABLE_KEYS)
            ->pluck('value', 'key')
            ->all();

        foreach (self::EDITABLE_KEYS as $key) {
            if ($key === 'payment_methods') {
                continue;
            }

            Setting::set($key, $data[$key] ?? null);
        }

        Setting::set('payment_methods', json_encode(array_values(array_unique($data['payment_methods']))));

        // Hapus logo kustom yang dicentang Owner.
        foreach ($data['remove_payment_logos'] ?? [] as $key) {
            $this->deleteCustomLogo($key);
        }

        // Unggah logo pengganti (opsional) untuk metode tertentu.
        foreach ($data['payment_logos'] ?? [] as $key => $file) {
            if (! $file || ! in_array($key, $catalogKeys, true)) {
                continue;
            }

            $this->deleteCustomLogo($key);

            $path = $file->store('payment-logos', 'public');
            Setting::set('payment_logo_'.$key, $path);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'setting.business_update',
            'model_type' => Setting::class,
            'model_id' => null,
            'description' => 'Pengaturan bisnis (identitas toko, WhatsApp, Instagram, pembayaran) diperbarui Owner.',
            'old_values' => $oldValues,
            'new_values' => collect($data)->except(['payment_logos', 'remove_payment_logos'])->all(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return redirect()
            ->route('owner.settings.index')
            ->with('success', 'Pengaturan bisnis berhasil disimpan dan langsung tampil di footer toko.');
    }

    /**
     * Hapus berkas logo kustom (bukan aset bawaan) lalu kosongkan setting.
     */
    private function deleteCustomLogo(string $key): void
    {
        $oldPath = (string) Setting::get('payment_logo_'.$key, '');

        if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        Setting::set('payment_logo_'.$key, null);
    }
}
