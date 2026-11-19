<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Mengembalikan stok dari satu pesanan secara idempoten.
 *
 * Stok dipotong tepat satu kali di OnlineOrderService saat checkout.
 * Service ini dipanggil hanya bila pesanan batal/gagal/expired/refund.
 * Kolom `sales.stock_released_at` menjadi guard: dua webhook gagal atau
 * webhook + admin membatalkan tidak akan mengembalikan stok dua kali.
 */
class StockReleaseService
{
    public function __construct(private readonly StockService $stock) {}

    public function release(
        Sale $sale,
        ?User $user,
        StockMovementType $type = StockMovementType::Void,
        ?string $note = null,
    ): bool {
        return DB::transaction(function () use ($sale, $user, $type, $note) {
            /** @var Sale $fresh */
            $fresh = Sale::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($fresh->stock_released_at !== null) {
                return false;
            }

            foreach ($fresh->items as $item) {
                $qty = (int) $item->qty;

                if ($qty <= 0) {
                    continue;
                }

                $variant = $this->resolveVariant($item);

                if ($item->product_id) {
                    $this->stock->apply(
                        product: $item->product,
                        qty: $qty,
                        type: $type,
                        user: $user,
                        note: $note ?? 'Pengembalian stok pesanan '.$fresh->invoice_number.'.',
                        refType: 'sale',
                        refId: $fresh->id,
                        variant: $variant,
                        color: $item->color,
                        size: $item->size,
                    );
                }
            }

            $fresh->update(['stock_released_at' => now()]);

            return true;
        });
    }

    /**
     * Varian diambil dari product_variant_id bila tersedia; untuk data lama
     * fallback ke pasangan warna+ukuran yang tersimpan di sale_items.
     */
    private function resolveVariant($item): ?ProductVariant
    {
        if (! empty($item->product_variant_id)) {
            $variant = ProductVariant::query()->find($item->product_variant_id);

            if ($variant !== null) {
                return $variant;
            }
        }

        if (! $item->product_id || (! $item->color && ! $item->size)) {
            return null;
        }

        return ProductVariant::query()
            ->where('product_id', $item->product_id)
            ->where('color', $item->color)
            ->where('size', $item->size)
            ->first();
    }
}
