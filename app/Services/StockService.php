<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Logika perubahan stok.
 *
 * ATURAN POKOK:
 *  - Stok TIDAK PERNAH diubah langsung lewat query produk. Semua perubahan
 *    wajib lewat service ini supaya selalu ada mutasi stok yang tercatat.
 *  - Setiap mutasi menyimpan stok sebelum & sesudah, jenis mutasi, alasan,
 *    dan pengguna yang melakukan.
 *  - Stok tidak boleh menjadi negatif.
 */
class StockService
{
    /**
     * Terapkan perubahan stok pada sebuah produk.
     *
     * @param  int  $qty  Perubahan stok. Positif = masuk, negatif = keluar.
     * @param  string  $note  Alasan wajib (khusus penyesuaian).
     */
    public function apply(
        Product $product,
        int $qty,
        StockMovementType $type,
        ?User $user = null,
        ?string $note = null,
        ?string $refType = null,
        ?int $refId = null,
    ): StockMovement {
        if ($qty === 0) {
            throw ValidationException::withMessages([
                'qty' => 'Jumlah perubahan stok tidak boleh nol.',
            ]);
        }

        return DB::transaction(function () use ($product, $qty, $type, $user, $note, $refType, $refId) {
            // Kunci baris produk agar dua perubahan stok tidak saling menimpa.
            $fresh = Product::query()->lockForUpdate()->findOrFail($product->id);

            $stockBefore = $fresh->stock;
            $stockAfter = $stockBefore + $qty;

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'qty' => sprintf(
                        'Stok %s tidak mencukupi. Stok tersedia: %d.',
                        $fresh->name,
                        $stockBefore
                    ),
                ]);
            }

            $fresh->update(['stock' => $stockAfter]);

            return StockMovement::create([
                'product_id' => $fresh->id,
                'type' => $type,
                'qty' => $qty,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'user_id' => $user?->id,
                'note' => $note,
            ]);
        });
    }

    /**
     * Penyesuaian stok manual oleh admin (stok masuk / keluar / koreksi).
     *
     * Alasan WAJIB diisi agar riwayat dapat diaudit.
     */
    public function adjust(
        Product $product,
        int $qty,
        string $reason,
        User $user,
        bool $isIncrease = true,
    ): StockMovement {
        $type = $isIncrease
            ? StockMovementType::AdjustmentIn
            : StockMovementType::AdjustmentOut;

        $signedQty = $isIncrease ? abs($qty) : -abs($qty);

        return $this->apply(
            product: $product,
            qty: $signedQty,
            type: $type,
            user: $user,
            note: $reason,
            refType: 'adjustment',
        );
    }

    /**
     * Tambah stok karena penerimaan barang, sekaligus memperbarui
     * harga pokok produk ke harga pembelian terakhir (FIFO disederhanakan).
     */
    public function receiveFromPurchase(
        Product $product,
        int $qty,
        float $costPrice,
        User $user,
        int $purchaseId,
    ): StockMovement {
        $movement = $this->apply(
            product: $product,
            qty: abs($qty),
            type: StockMovementType::Purchase,
            user: $user,
            note: 'Penerimaan barang dari dokumen pembelian.',
            refType: 'purchase',
            refId: $purchaseId,
        );

        // Harga pokok produk mengikuti pembelian terakhir.
        Product::query()->whereKey($product->id)->update([
            'cost_price' => $costPrice,
        ]);

        return $movement;
    }

    /**
     * Kembalikan stok ke Gudang karena transaksi dibatalkan/refund.
     */
    public function returnFromSale(
        Product $product,
        int $qty,
        User $user,
        int $saleId,
        StockMovementType $type = StockMovementType::Refund,
    ): StockMovement {
        return $this->apply(
            product: $product,
            qty: abs($qty),
            type: $type,
            user: $user,
            note: 'Pengembalian stok karena transaksi dibatalkan/direfund.',
            refType: 'sale',
            refId: $saleId,
        );
    }
}
