<?php

namespace App\Enums;

/**
 * Status promo: aktif, belum mulai, sudah berakhir, atau dinonaktifkan manual.
 */
enum PromotionStatus: string
{
    case Active = 'active';
    case Upcoming = 'upcoming';
    case Expired = 'expired';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Upcoming => 'Belum Mulai',
            self::Expired => 'Sudah Berakhir',
            self::Inactive => 'Nonaktif',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'text-bg-success',
            self::Upcoming => 'text-bg-info',
            self::Expired => 'text-bg-secondary',
            self::Inactive => 'text-bg-danger',
        };
    }
}
