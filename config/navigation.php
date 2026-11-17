<?php

/*
|--------------------------------------------------------------------------
| Struktur Menu Navigasi
|--------------------------------------------------------------------------
|
| Menu ditampilkan sesuai peran pengguna. Route diisi dengan nama route
| Laravel yang terdaftar di routes/web.php; item tanpa route ditandai
| `soon => true` dan dirender sebagai teks non-klik dengan badge "Segera".
|
*/

return [

    'admin' => [
        [
            'type' => 'item',
            'label' => 'Dashboard',
            'icon' => 'bi-speedometer2',
            'route' => 'admin.dashboard',
        ],

        ['type' => 'section', 'label' => 'Master Data'],

        [
            'type' => 'item', 'label' => 'Pesanan', 'icon' => 'bi-bag',
            'route' => 'admin.orders.index',
        ],
        [
            'type' => 'item', 'label' => 'Produk', 'icon' => 'bi-box-seam',
            'route' => 'admin.products.index',
        ],
        [
            'type' => 'item', 'label' => 'Kategori', 'icon' => 'bi-tags',
            'route' => 'admin.categories.index',
        ],

        ['type' => 'section', 'label' => 'Tampilan'],

        [
            'type' => 'item', 'label' => 'Tampilan Toko', 'icon' => 'bi-images',
            'route' => 'admin.media.site',
        ],
    ],

    'owner' => [
        [
            'type' => 'item',
            'label' => 'Dashboard Owner',
            'icon' => 'bi-speedometer2',
            'route' => 'owner.dashboard',
        ],

        [
            'type' => 'item', 'label' => 'Tampilan Toko', 'icon' => 'bi-images',
            'route' => 'admin.media.site',
        ],

        ['type' => 'section', 'label' => 'Laporan'],

        [
            'type' => 'item', 'label' => 'Laporan Penjualan', 'icon' => 'bi-graph-up',
            'route' => 'owner.reports.sales',
        ],
        [
            'type' => 'item', 'label' => 'Laporan Laba Kotor', 'icon' => 'bi-cash-stack',
            'route' => 'owner.reports.profit',
        ],
        [
            'type' => 'item', 'label' => 'Laporan Produk', 'icon' => 'bi-box-seam',
            'route' => 'owner.reports.products',
        ],
        [
            'type' => 'item', 'label' => 'Laporan Inventori', 'icon' => 'bi-boxes',
            'route' => 'owner.reports.inventory',
        ],

        ['type' => 'section', 'label' => 'Katalog (hanya baca)'],

        [
            'type' => 'item', 'label' => 'Lihat Katalog Toko', 'icon' => 'bi-shop',
            'route' => 'store.catalog',
        ],
        [
            'type' => 'item', 'label' => 'Kategori', 'icon' => 'bi-tags',
            'route' => null,
            'soon' => true,
        ],
    ],

];
