<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed akun admin dan owner beserta contoh produk.
     *
     * Akun demo memakai kata sandi tetap (bukan acak) supaya bisa dipakai
     * saat demo tanpa harus mencari catatan di terminal. Email-nya berakhiran
     * .test sehingga tidak pernah bisa kirim email sungguhan.
     * Before production: ganti kata sandi ini dan sembunyikan panel akun
     * demo di resources/views/auth/login.blade.php.
     *
     *   admin@tokotabita.test  (Admin)  / Tabita-Admin-123
     *   owner@tokotabita.test  (Owner)  / Tabita-Owner-123
     */
    public function run(): void
    {
        $passwords = [
            'admin@tokotabita.test' => 'Tabita-Admin-123',
            'owner@tokotabita.test' => 'Tabita-Owner-123',
        ];

        $akun = [
            ['name' => 'Admin Toko Tabita', 'email' => 'admin@tokotabita.test', 'role' => UserRole::Admin],
            ['name' => 'Owner Toko Tabita', 'email' => 'owner@tokotabita.test', 'role' => UserRole::Owner],
        ];

        foreach ($akun as $data) {
            User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($passwords[$data['email']]),
                    'role' => $data['role'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        // Tampilkan password ke console agar bisa dicatat.
        // HANYA tampil saat menjalankan `php artisan db:seed` dari CLI.
        if (app()->runningInConsole()) {
            foreach ($passwords as $email => $pass) {
                $this->command->info("Akun demo: {$email} / {$pass}");
            }
            $this->command->warn('Akun demo memakai kata sandi tetap. Ganti sebelum dipakai di server sungguhan.');
        }

        $kategori = [
            'Kemeja' => 'Kemeja Pria dan Wanita',
            'Gamis' => 'Gamis dan Kebaya',
            'Celana' => 'Celana formalized',
            'Aksesoris' => 'Aksesoris dan pelengkap',
        ];

        foreach ($kategori as $nama => $deskripsi) {
            Category::query()->updateOrCreate(
                ['name' => $nama],
                ['description' => $deskripsi, 'is_active' => true]
            );
        }

        $contoh = [
            ['barcode' => '899100010001', 'name' => 'Kemeja Flanel Lengan Panjang', 'category' => 'Kemeja', 'cost_price' => 145000, 'sale_price' => 195000, 'stock' => 24, 'min_stock' => 5],
            ['barcode' => '899100010002', 'name' => 'Kemeja Linen Lengan Pendek', 'category' => 'Kemeja', 'cost_price' => 135000, 'sale_price' => 185000, 'stock' => 18, 'min_stock' => 5],
            ['barcode' => '899100010003', 'name' => 'Gamis Katun Motif Bunga', 'category' => 'Gamis', 'cost_price' => 210000, 'sale_price' => 275000, 'stock' => 12, 'min_stock' => 4],
            ['barcode' => '899100010004', 'name' => 'Gamis Katun Polos Elegan', 'category' => 'Gamis', 'cost_price' => 195000, 'sale_price' => 255000, 'stock' => 9, 'min_stock' => 4],
            ['barcode' => '899100010005', 'name' => 'Celana Formal Slim Fit', 'category' => 'Celana', 'cost_price' => 165000, 'sale_price' => 215000, 'stock' => 15, 'min_stock' => 5],
            ['barcode' => '899100010006', 'name' => 'Sash Ikat Kain Motif', 'category' => 'Aksesoris', 'cost_price' => 45000, 'sale_price' => 75000, 'stock' => 30, 'min_stock' => 8],
        ];

        foreach ($contoh as $baris) {
            Product::query()->updateOrCreate(
                ['barcode' => $baris['barcode']],
                [
                    'name' => $baris['name'],
                    'category_id' => Category::query()->where('name', $baris['category'])->value('id'),
                    'unit' => 'pcs',
                    'cost_price' => $baris['cost_price'],
                    'sale_price' => $baris['sale_price'],
                    'stock' => $baris['stock'],
                    'min_stock' => $baris['min_stock'],
                    'is_active' => true,
                ]
            );
        }

        // Katalog metode pembayaran checkout (transfer aktif, gateway menyusul).
        $this->call(PaymentChannelSeeder::class);

        // Hak akses Admin/Owner.
        $this->call(PermissionSeeder::class);
    }
}
