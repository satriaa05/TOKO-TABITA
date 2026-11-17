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
        'default' => env('TABITA_SHIPPING_DEFAULT', 15000),
        'zones' => [
            'jakarta' => env('TABITA_SHIPPING_JAKARTA', 15000),
            'jawa_barat' => env('TABITA_SHIPPING_JAWA_BARAT', 20000),
            'jawa_tengah' => env('TABITA_SHIPPING_JAWA_TENGAH', 25000),
            'jawa_timur' => env('TABITA_SHIPPING_JAWA_TIMUR', 25000),
            'luar_jawa' => env('TABITA_SHIPPING_LUAR_JAWA', 35000),
        ],
        'free_threshold' => env('TABITA_SHIPPING_FREE_THRESHOLD', 500000),
    ],

];
