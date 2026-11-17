<?php

namespace App\Enums;

/**
 * Peran pengguna dalam aplikasi Toko Tabita.
 *
 * Nilai string di sini disimpan pada kolom `users.role` dan dipakai
 * oleh middleware `role` untuk pembatasan akses di sisi server.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Owner = 'owner';
    case Customer = 'customer';

    /**
     * Label ramah untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Owner => 'Owner',
            self::Customer => 'Pelanggan',
        };
    }

    /**
     * Route dashboard sesuai peran.
     * Pengguna yang bukan admin/owner (pembeli online) diarahkan ke
     * halaman toko publik.
     */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Owner => 'owner.dashboard',
            self::Customer => 'store.catalog',
            default => 'home',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}
