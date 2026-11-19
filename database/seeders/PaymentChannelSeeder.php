<?php

namespace Database\Seeders;

use App\Models\PaymentChannel;
use Illuminate\Database\Seeder;

/**
 * Katalog metode pembayaran checkout.
 *
 * Default yang AKTIF hanya transfer manual karena gateway Midtrans belum
 * dikonfigurasi. Setelah kredensial sandbox tersedia, Owner/Admin dapat
 * mengaktifkan qris/gopay/ovo/dana/shopeepay/va lewat tabel ini.
 */
class PaymentChannelSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            [
                'code' => 'transfer',
                'name' => 'Transfer Bank',
                'provider' => null,
                'mode' => 'manual',
                'logo_path' => 'assets/images/payments/bank-transfer.svg',
                'description' => 'Transfer ke rekening toko, bukti pembayaran diverifikasi Admin.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'qris',
                'name' => 'QRIS',
                'provider' => 'midtrans',
                'mode' => 'automatic',
                'logo_path' => 'assets/images/payments/qris.svg',
                'description' => 'QRIS dinamis per transaksi melalui Midtrans.',
                'is_active' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'gopay',
                'name' => 'GoPay',
                'provider' => 'midtrans',
                'mode' => 'automatic',
                'logo_path' => null,
                'description' => 'Pembayaran otomatis via GoPay / QRIS GoPay.',
                'is_active' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'ovo',
                'name' => 'OVO',
                'provider' => 'midtrans',
                'mode' => 'automatic',
                'logo_path' => null,
                'description' => 'Pembayaran otomatis via OVO.',
                'is_active' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'dana',
                'name' => 'DANA',
                'provider' => 'midtrans',
                'mode' => 'automatic',
                'logo_path' => null,
                'description' => 'Pembayaran otomatis via DANA.',
                'is_active' => false,
                'sort_order' => 5,
            ],
            [
                'code' => 'shopeepay',
                'name' => 'ShopeePay',
                'provider' => 'midtrans',
                'mode' => 'automatic',
                'logo_path' => null,
                'description' => 'Pembayaran otomatis via ShopeePay.',
                'is_active' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'va',
                'name' => 'Virtual Account Bank',
                'provider' => 'midtrans',
                'mode' => 'automatic',
                'logo_path' => 'assets/images/payments/bank-transfer.svg',
                'description' => 'Virtual account bank melalui Midtrans.',
                'is_active' => false,
                'sort_order' => 7,
            ],
        ];

        $gatewayReady = filled(config('payments.midtrans.server_key'));

        foreach ($channels as $index => $channel) {
            // Metode otomatis hanya aktif setelah kredensial Midtrans diisi.
            if ($channel['mode'] === 'automatic') {
                $channels[$index]['is_active'] = $gatewayReady;
            }
        }

        foreach ($channels as $channel) {
            PaymentChannel::query()->updateOrCreate(
                ['code' => $channel['code']],
                $channel
            );
        }
    }
}
