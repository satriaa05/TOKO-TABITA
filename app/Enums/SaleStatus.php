<?php

namespace App\Enums;

/**
 * Status transaksi penjualan.
 */
enum SaleStatus: string
{
    case Completed = 'completed';
    case PendingPayment = 'pending_payment';
    case Refunded = 'refunded';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Selesai',
            self::PendingPayment => 'Menunggu Konfirmasi',
            self::Refunded => 'Dikembalikan',
            self::Voided => 'Dibatalkan',
        };
    }

    /**
     * Status badge Bootstrap.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Completed => 'text-bg-success',
            self::PendingPayment => 'text-bg-warning',
            self::Refunded => 'text-bg-warning',
            self::Voided => 'text-bg-secondary',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $s): string => $s->value, self::cases());
    }
}
