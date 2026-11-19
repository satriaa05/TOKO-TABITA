<?php

namespace App\Enums;

/**
 * Status pemenuhan pesanan (barang), terpisah dari PaymentStatus.
 *
 * Dipakai Admin untuk mengelola alur: diproses -> dikemas -> dikirim ->
 * diterima. Tidak mengubah status finansial di Sales.status.
 */
enum FulfillmentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Diproses',
            self::Processing => 'Sedang Diproses',
            self::Packed => 'Sedang Dikemas',
            self::Shipped => 'Dalam Pengiriman',
            self::Delivered => 'Diterima Pelanggan',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'text-bg-secondary',
            self::Processing => 'text-bg-info',
            self::Packed => 'text-bg-primary',
            self::Shipped => 'text-bg-warning',
            self::Delivered => 'text-bg-success',
            self::Cancelled => 'text-bg-danger',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $s): string => $s->value, self::cases());
    }
}
