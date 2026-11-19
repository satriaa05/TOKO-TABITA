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
        [
            'type' => 'item',
            'label' => 'Profil Saya',
            'icon' => 'bi-person-circle',
            'route' => 'profile.index',
        ],

        ['type' => 'section', 'label' => 'Master Data'],

        [
            'type' => 'item', 'label' => 'Pesanan', 'icon' => 'bi-bag',
            'route' => 'admin.orders.index',
        ],
        [
            'type' => 'item', 'label' => 'Pembayaran', 'icon' => 'bi-credit-card',
            'route' => 'admin.payments.index',
        ],
        [
            'type' => 'item', 'label' => 'Pengiriman', 'icon' => 'bi-truck',
            'route' => 'admin.shipments.index',
        ],
        [
            'type' => 'item', 'label' => 'Produk', 'icon' => 'bi-box-seam',
            'route' => 'admin.products.index',
        ],
        [
            'type' => 'item', 'label' => 'Kategori', 'icon' => 'bi-tags',
            'route' => 'admin.categories.index',
        ],
        [
            'type' => 'item', 'label' => 'Jejak Audit', 'icon' => 'bi-clock-history',
            'route' => 'admin.audit-logs.index',
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
            'type' => 'item',
            'label' => 'Monitoring Transaksi',
            'icon' => 'bi-activity',
            'route' => 'owner.monitoring.index',
        ],
        [
            'type' => 'item',
            'label' => 'Profil Saya',
            'icon' => 'bi-person-circle',
            'route' => 'profile.index',
        ],

        [
            'type' => 'section', 'label' => 'Pengguna'],

        [
            'type' => 'item',
            'label' => 'Kelola Akun',
            'icon' => 'bi-people',
            'route' => 'owner.users.index',
        ],

        [
            'type' => 'item',
            'label' => 'Jejak Audit',
            'icon' => 'bi-clock-history',
            'route' => 'admin.audit-logs.index',
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

        ['type' => 'section', 'label' => 'Katalog'],

        [
            'type' => 'item', 'label' => 'Kelola Produk', 'icon' => 'bi-box-seam',
            'route' => 'admin.products.index',
        ],
        [
            'type' => 'item', 'label' => 'Lihat Katalog Toko', 'icon' => 'bi-shop',
            'route' => 'store.catalog',
        ],
        [
            'type' => 'item', 'label' => 'Kategori', 'icon' => 'bi-tags',
            'route' => null,
            'soon' => true,
        ],

        ['type' => 'section', 'label' => 'Pengaturan'],

        [
            'type' => 'item', 'label' => 'Pengaturan Bisnis', 'icon' => 'bi-gear',
            'route' => 'owner.settings.index',
        ],
    ],

];
