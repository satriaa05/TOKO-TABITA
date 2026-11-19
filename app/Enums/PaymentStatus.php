<?php

namespace App\Enums;

/**
 * Status pembayaran, terpisah dari status pengiriman.
 *
 * Status ini menggambarkan UANG, bukan barang. Pembayaran PAID tidak
 * berarti barang sudah dikirim — itu ada di FulfillmentStatus.
 */
enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::Pending => 'Menunggu Verifikasi',
            self::Paid => 'Lunas',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
            self::Refunded => 'Dikembalikan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Unpaid => 'text-bg-secondary',
            self::Pending => 'text-bg-warning',
            self::Paid => 'text-bg-success',
            self::Failed => 'text-bg-danger',
            self::Expired => 'text-bg-dark',
            self::Refunded => 'text-bg-info',
        };
    }

    /**
     * Status yang mengizinkan stok dipertahankan / dianggap terjual.
     */
    public function isSettled(): bool
    {
        return in_array($this, [self::Paid, self::Refunded], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $s): string => $s->value, self::cases());
    }
}
