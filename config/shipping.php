<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Pengiriman & Peta
|--------------------------------------------------------------------------
|
| Biteship dipakai untuk ongkir dan tracking. Google Maps dipakai untuk
| peta. Dua API key Google berbeda disiapkan: key JS dibatasi HTTP referer,
| key server dibatasi IP address.
|
*/

return [

    'provider' => env('SHIPPING_PROVIDER', 'biteship'),

    'biteship' => [
        'api_key' => env('BITESHIP_API_KEY'),
        'webhook_token' => env('BITESHIP_WEBHOOK_TOKEN'),
        'webhook_header' => env('BITESHIP_WEBHOOK_HEADER', 'X-Tabita-Signature'),
        'api_url' => env('BITESHIP_API_URL', 'https://api.biteship.com'),
        'webhook_url' => env('BITESHIP_WEBHOOK_URL'),
        'poll_seconds' => (int) env('BITESHIP_POLL_SECONDS', 15),
    ],

    'maps' => [
        /*
         * Peta gratis tanpa API key: Leaflet + OpenStreetMap.
         * Geocoding memakai Nominatim, rute memakai OSRM demo server.
         * Semua hasil di-cache agar pemakaian tetap wajar.
         */
        'provider' => 'osm',
        'geocode_url' => env('TABITA_GEOCODE_URL', 'https://nominatim.openstreetmap.org/search'),
        'route_url' => env('TABITA_ROUTE_URL', 'https://router.project-osrm.org/route/v1/driving'),
        'tile_url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors',
        'user_agent' => env('TABITA_MAPS_USER_AGENT', 'TabitaModiste/1.0 (local development)'),
    ],

    /*
     * Katalog kurir yang ditampilkan di checkout. Tarif diambil dari Biteship
     * bila API tersedia, dengan fallback tarif manual config('pos.shipping').
     * Jangan menampilkan estimasi hari dari katalog ini.
     */
    'couriers' => [
        'grab' => [
            'name' => 'Grab',
            'services' => [
                'instant' => 'Instan',
            ],
            'instant' => true,
        ],
        'jne' => [
            'name' => 'JNE',
            'services' => [
                'reg' => 'Reguler',
            ],
        ],
        'sicepat' => [
            'name' => 'SiCepat',
            'services' => [
                'reg' => 'Reguler',
            ],
        ],
    ],

    /*
     * Jarak maksimal Grab Instan dari lokasi produksi (km).
     * Gunakan koordinat asli bila Google Maps tersedia; jika tidak,
     * kode pos dengan prefix beda dianggap terlalu jauh.
     */
    'instant_max_km' => (float) env('TABITA_INSTANT_MAX_KM', 30),

    /*
     * Kurir instant yang benar-benar menyediakan GPS live menurut Biteship.
     * Selain kode di sini memakai mode "scan" (lokasi scan terakhir).
     */
    'instant_couriers' => ['gosend', 'grabexpress', 'grab', 'gojek'],

];
