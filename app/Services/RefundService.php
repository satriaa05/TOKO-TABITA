<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\AuditLog;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pembatalan / refund transaksi penjualan.
 *
 * ATURAN:
 *  - Hanya Admin dan Owner yang boleh memproses (dijaga middleware role +
 *    pengecekan ulang di service ini).
 *  - WAJIB ada alasan - alasannya tersimpan di transaksi dan audit log.
 *  - Stok produk yang sudah terjual dikembalikan ke gudang.
 *  - Transaksi yang sudah dibatalkan tidak bisa dibatalkan lagi.
 */
class RefundService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Batalkan transaksi dan kembalikan stoknya.
     *
     * @throws ValidationException
     */
    public function refund(Sale $sale, User $user, string $reason): Sale
    {
        if (! $user->isAdmin() && ! $user->isOwner()) {
            throw ValidationException::withMessages([
                'sale' => 'Anda tidak berwenang membatalkan transaksi.',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Alasan pembatalan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($sale, $user, $reason) {
            $fresh = Sale::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($fresh->status !== SaleStatus::Completed) {
                throw ValidationException::withMessages([
                    'sale' => 'Transaksi ini sudah berstatus "'.$fresh->status->label().'" dan tidak bisa dibatalkan lagi.',
                ]);
            }

            // Kembalikan stok produk yang terjual.
            foreach ($fresh->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $this->stock->apply(
                    product: $item->product,
                    qty: (int) $item->qty,
                    type: StockMovementType::Refund,
                    user: $user,
                    note: 'Pengembalian stok dari pembatalan transaksi '.$fresh->invoice_number.'.',
                    refType: 'sale',
                    refId: $fresh->id,
                );
            }

            $fresh->update([
                'status' => SaleStatus::Refunded->value,
                'refunded_at' => now(),
                'refunded_by' => $user->id,
                'refund_reason' => $reason,
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'sale.refund',
                'model_type' => Sale::class,
                'model_id' => $fresh->id,
                'description' => 'Transaksi '.$fresh->invoice_number.' dibatalkan oleh '.$user->name
                    .'. Alasan: '.$reason,
                'old_values' => ['status' => SaleStatus::Completed->value],
                'new_values' => [
                    'status' => SaleStatus::Refunded->value,
                    'total' => (float) $fresh->total,
                ],
            ]);

            return $fresh->refresh();
        });
    }
}
