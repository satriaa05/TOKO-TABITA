<?php

namespace App\Enums;

/**
 * Jenis mutasi stok. Semua perubahan stok wajib tercatat lewat tabel
 * `stock_movements` dengan salah satu jenis di bawah ini.
 */
enum StockMovementType: string
{
    case Purchase = 'purchase';           // Stok masuk dari penerimaan barang
    case Sale = 'sale';                   // Stok keluar karena penjualan
    case AdjustmentIn = 'adjustment_in';  // Penyesuaian stok positif (koreksi/rusak dibuang)
    case AdjustmentOut = 'adjustment_out'; // Penyesuaian stok negatif (hilang/rusak)
    case Refund = 'refund';               // Stok kembali karena refund
    case Void = 'void';                   // Stok kembali karena pembatalan transaksi

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Penerimaan Barang',
            self::Sale => 'Penjualan',
            self::AdjustmentIn => 'Penyesuaian Masuk',
            self::AdjustmentOut => 'Penyesuaian Keluar',
            self::Refund => 'Refund',
            self::Void => 'Pembatalan',
        };
    }

    /**
     * Tanda pengaruhnya terhadap stok (+1 / -1).
     */
    public function sign(): int
    {
        return match ($this) {
            self::Purchase, self::AdjustmentIn, self::Refund, self::Void => 1,
            self::Sale, self::AdjustmentOut => -1,
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
