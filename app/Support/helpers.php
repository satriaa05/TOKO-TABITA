<?php

use App\Services\StoreProfile;

/**
 * Fungsi bantu global untuk aplikasi POS.
 *
 * File ini di-autoload otomatis oleh Composer (lihat autoload.files
 * pada composer.json).
 */
if (! function_exists('store_profile')) {
    /**
     * Profil toko publik (nama, WhatsApp, Instagram, lokasi, pembayaran).
     *
     * Nomor WhatsApp dan identitas toko hanya didefinisikan di satu tempat
     * (tabel settings + config/pos.php), tidak diulang di file Blade.
     */
    function store_profile(): StoreProfile
    {
        return app(StoreProfile::class);
    }
}

if (! function_exists('rupiah')) {
    /**
     * Format nilai uang ke Rupiah, contoh: 15000 -> "Rp 15.000".
     */
    function rupiah(float|int|string|null $value, bool $withPrefix = true): string
    {
        $formatted = number_format((float) $value, 0, ',', '.');

        return $withPrefix ? 'Rp '.$formatted : $formatted;
    }
}

if (! function_exists('rupiahShort')) {
    /**
     * Format ringkas untuk label grafik, contoh: 1500000 -> "1,5jt".
     */
    function rupiahShort(float|int|string|null $value): string
    {
        $value = (float) $value;

        if ($value >= 1_000_000_000) {
            return number_format($value / 1_000_000_000, 1, ',', '.').'M';
        }

        if ($value >= 1_000_000) {
            return number_format($value / 1_000_000, 1, ',', '.').'jt';
        }

        if ($value >= 1_000) {
            return number_format($value / 1_000, 1, ',', '.').'rb';
        }

        return number_format($value, 0, ',', '.');
    }
}

if (! function_exists('rupiahShortWithPrefix')) {
    /**
     * Versi ringkas dengan awalan "Rp ".
     */
    function rupiahShortWithPrefix(float|int|string|null $value): string
    {
        return 'Rp '.rupiahShort($value);
    }
}
