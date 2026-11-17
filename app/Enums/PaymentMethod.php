<?php

namespace App\Enums;

/**
 * Metode pembayaran yang didukung.
 *
 * MVP hanya mengaktifkan `Cash`. Nilai lain sudah disiapkan agar mudah
 * ditambahkan, namun TIDAK Connected ke payment gateway apa pun —
 * QRIS/Debit/Kredit/E-Wallet belum benar-benar terintegrasi.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Qris = 'qris';
    case Debit = 'debit';
    case Credit = 'credit';
    case Ewallet = 'ewallet';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Qris => 'QRIS',
            self::Debit => 'Debit',
            self::Credit => 'Kredit',
            self::Ewallet => 'E-Wallet',
            self::Transfer => 'Transfer Bank',
        };
    }

    /**
     * Metode yang benar-benar aktif pada MVP.
     *
     * @return array<int, self>
     */
    public static function enabled(): array
    {
        return [self::Cash];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $m): string => $m->value, self::cases());
    }
}
