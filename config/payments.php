<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Payment Gateway
|--------------------------------------------------------------------------
|
| Semua kredensial dibaca dari .env. Jangan pernah menaruh server key atau
| secret lain di config ini, di database, apalagi di JavaScript/Blade.
|
*/

return [

    'default' => env('PAYMENT_PROVIDER', 'midtrans'),

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        'api_url' => env(
            'MIDTRANS_API_URL',
            env('MIDTRANS_IS_PRODUCTION', false)
                ? 'https://api.midtrans.com'
                : 'https://api.sandbox.midtrans.com'
        ),
        'expiry_minutes' => (int) env('MIDTRANS_EXPIRY_MINUTES', 30),
        'notification_url' => env('MIDTRANS_NOTIFICATION_URL'),
    ],

    /*
     * Rekening toko untuk transfer manual (fallback bila gateway bermasalah).
     * Isi dari lingkungan/server, bukan dari browser.
     */
    'bank_transfer' => [
        'bank_name' => env('TABITA_BANK_NAME', ''),
        'account_holder' => env('TABITA_BANK_HOLDER', ''),
        'account_number' => env('TABITA_BANK_ACCOUNT', ''),
    ],

];
