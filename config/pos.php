<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi dasar aplikasi Toko Tabita
|--------------------------------------------------------------------------
|
| Nilai di sini dapat diubah tanpa menyentuh kode fitur. Nama toko tampil
| pada header aplikasi, halaman login, dan halaman publik.
|
*/

return [

    /*
    | Identitas toko yang tampil pada header aplikasi dan halaman login.
    */
    'store' => [
        'name' => env('TABITA_STORE_NAME', 'Tabita Modiste'),
        'address' => env('TABITA_STORE_ADDRESS', ''),
        'phone' => env('TABITA_STORE_PHONE', ''),

        /*
        | Identitas yang tampil di header/footer toko. Nilai default di sini
        | adalah cadangan; Owner dapat menimpanya lewat menu
        | "Pengaturan Bisnis" tanpa mengubah kode.
        */
        'tagline' => env('TABITA_STORE_TAGLINE', 'Fashion for Every You'),
        'description' => env('TABITA_STORE_DESCRIPTION', 'Garment premium, harga terjangkau sejak 2010.'),
        'location' => env('TABITA_STORE_LOCATION', 'Semarang, Jawa Tengah, Indonesia'),
        'operating_hours' => env('TABITA_STORE_HOURS', ''),

        /*
        | Nomor WhatsApp resmi toko. Format internasional tanpa "+" untuk URL.
        | Nomor ini HANYA dapat diubah oleh Owner (lihat Owner\StoreSettingController).
        */
        'whatsapp' => env('TABITA_STORE_WHATSAPP', '+62 831-2881-4776'),

        /*
        | URL Instagram resmi. Biarkan kosong bila akun belum tersedia;
        | footer akan menampilkan ikon non-aktif, bukan tautan palsu.
        */
        'instagram_url' => env('TABITA_INSTAGRAM_URL', ''),
    ],

    /*
    | Metode pembayaran yang dikenal aplikasi.
    |
    | `active` adalah metode yang ditampilkan di footer selama Owner belum
    | memilih sendiri dari dashboard. Hanya tampilkan metode yang benar-benar
    | didukung toko agar pelanggan tidak diberi janji yang salah.
    */
    'payments' => [
        'methods' => [
            'transfer' => [
                'label' => 'Transfer Bank',
                'logo' => 'assets/images/payments/bank-transfer.svg',
                'hint' => 'Transfer bank / virtual account yang diinformasikan admin.',
            ],
            'qris' => [
                'label' => 'QRIS',
                'logo' => 'assets/images/payments/qris.svg',
                'hint' => 'Pembayaran QRIS (bila sudah aktif di toko).',
            ],
            'visa' => [
                'label' => 'Visa',
                'logo' => 'assets/images/payments/visa.svg',
                'hint' => 'Kartu kredit/debit Visa (bila sudah aktif di toko).',
            ],
            'mastercard' => [
                'label' => 'Mastercard',
                'logo' => 'assets/images/payments/mastercard.svg',
                'hint' => 'Kartu kredit/debit Mastercard (bila sudah aktif di toko).',
            ],
        ],
        'active' => ['transfer'],
    ],

    /*
    | Format mata uang.
    */
    'currency' => [
        'symbol' => 'Rp',
        'decimals' => 0,
    ],

    /*
    | Jumlah baris per halaman pada tabel daftar.
    */
    'per_page' => 15,

    /*
    | Biaya pengiriman (ongkir).
    | Daftar rata-rata ongkir per wilayah (dalam rupiah).
    | Nilai ini bisa diubah admin lewat pengaturan toko / config tanpa ubah kode.
    */
    'shipping' => [
        'origin_postal_code' => env('TABITA_STORE_POSTAL_CODE', '50131'),
        'default' => env('TABITA_SHIPPING_DEFAULT', 17000),
        'max_cost' => env('TABITA_SHIPPING_MAX_COST', 17000),
        'zones' => [
            'jakarta' => env('TABITA_SHIPPING_JAKARTA', 15000),
            'jawa_barat' => env('TABITA_SHIPPING_JAWA_BARAT', 20000),
            'jawa_tengah' => env('TABITA_SHIPPING_JAWA_TENGAH', 25000),
            'jawa_timur' => env('TABITA_SHIPPING_JAWA_TIMUR', 25000),
            'luar_jawa' => env('TABITA_SHIPPING_LUAR_JAWA', 35000),
        ],
        'free_threshold' => env('TABITA_SHIPPING_FREE_THRESHOLD', 300000),
    ],

];
