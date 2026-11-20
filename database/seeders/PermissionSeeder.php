<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'orders.view' => ['Lihat Pesanan', 'Melihat daftar dan detail pesanan'],
        'orders.manage' => ['Kelola Pesanan', 'Menyetujui pembayaran dan membatalkan pesanan'],
        'payments.verify' => ['Verifikasi Pembayaran', 'Memeriksa bukti dan status pembayaran'],
        'shipments.manage' => ['Kelola Pengiriman', 'Input resi dan memperbarui status pengiriman'],
        'products.manage' => ['Kelola Produk', 'Menambah, mengubah, dan menghapus produk'],
        'categories.manage' => ['Kelola Kategori', 'Menambah, mengubah, dan menghapus kategori'],
        'media.manage' => ['Kelola Media Toko', 'Mengatur banner, logo, dan foto toko'],
        'promotions.manage' => ['Kelola Promo', 'Membuat dan mengubah promo otomatis produk'],
        'reports.view' => ['Lihat Laporan', 'Melihat laporan penjualan dan monitoring'],
        'users.manage' => ['Kelola Akun', 'Menambah, mengubah, dan menonaktifkan akun Admin'],
        'settings.manage' => ['Pengaturan Sistem', 'Mengatur identitas dan konfigurasi toko'],
    ];

    private const ADMIN = [
        'orders.view', 'orders.manage', 'payments.verify', 'shipments.manage',
        'products.manage', 'categories.manage', 'media.manage', 'reports.view',
        'promotions.manage',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $key => [$name, $description]) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $key],
                ['name' => $name, 'description' => $description, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $ids = DB::table('permissions')->pluck('id', 'key');

        // Owner selalu memiliki semua permission (diabaikan saat pengecekan).
        // Admin hanya mendapat permission operasional.
        DB::table('role_permissions')->where('role', 'admin')->delete();

        foreach (self::ADMIN as $key) {
            DB::table('role_permissions')->insertOrIgnore([
                'role' => 'admin',
                'permission_id' => $ids[$key],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
