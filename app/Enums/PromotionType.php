<?php

namespace App\Enums;

/**
 * Jenis diskon pada promo.
 */
enum PromotionType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Diskon Persentase',
            self::Fixed => 'Diskon Nominal',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $t): string => $t->value, self::cases());
    }
}
