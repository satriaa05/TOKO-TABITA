<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Manajemen Gambar (Media)
|--------------------------------------------------------------------------
|
| Aturan upload dan optimasi gambar. Nilai di sini boleh diubah tanpa
| menyentuh kode fitur; admin tidak perlu tahu file ini sama sekali
| karena pengelolaan gambar dilakukan dari halaman admin.
|
*/

return [

    /*
    | Format berkas yang diizinkan.
    */
    'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp'],

    /*
    | Batas ukuran berkas per unggahan, dalam kilobyte (KB).
    */
    'max_size_kb' => 2048,

    /*
    | Batas jumlah berkas dalam satu kali unggah (multi-upload).
    */
    'max_files_per_upload' => 10,

    /*
    | Optimasi gambar: lebar maksimum (px) dan kualitas kompresi ulang.
    | Optimasi server-side butuh ekstensi PHP "gd" di server; bila ekstensi
    | tidak tersedia, berkas asli tetap tersimpan tanpa error.
    */
    'max_width' => 1600,
    'quality' => 82,

    /*
    | Aset tampilan toko yang dikelola Admin/Owner dari halaman
    | "Tampilan Toko". `multiple` menentukan boleh lebih dari satu berkas.
    */
    'site_roles' => [
        'logo' => [
            'label' => 'Logo Toko',
            'hint' => 'Tampil di navbar dan sidebar aplikasi. Satu logo saja; logo baru menggantikan yang lama.',
            'multiple' => false,
            'max_files' => 1,
        ],
        'banner_login' => [
            'label' => 'Background Login & Register (Fashion Model)',
            'hint' => 'Foto model fashion wanita editorial beresolusi tinggi untuk background halaman login & registrasi Glassmorphism.',
            'multiple' => false,
            'max_files' => 1,
        ],
        'banner_home' => [
            'label' => 'Hero Banner Storefront',
            'hint' => 'Foto model fashion utama untuk banner toko depan (storefront) dan dashboard.',
            'multiple' => true,
            'max_files' => 5,
        ],
        'banner_promo' => [
            'label' => 'Banner Promosi',
            'hint' => 'Ditampilkan di dashboard sebagai strip promosi.',
            'multiple' => true,
            'max_files' => 5,
        ],
        'collection' => [
            'label' => 'Foto Koleksi Terbaru',
            'hint' => 'Ditampilkan di bagian "Koleksi Terbaru" dashboard.',
            'multiple' => true,
            'max_files' => 5,
        ],
    ],
];
