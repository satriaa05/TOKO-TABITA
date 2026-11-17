<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ShippingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Order online (guest checkout) untuk pembeli toko baju.
 *
 * ATURAN POKOK:
 *  1. Keranjang & harga SELALU dihitung ulang di server; nilai dari browser
 *     tidak pernah dipercaya.
 *  2. Transaksi disimpan dengan status "Menunggu Konfirmasi" — pembayaran
 *     dilakukan di luar sistem (transfer ke admin via WhatsApp). Pembayaran
 *     dicatat lalu order ditandai selesai oleh admin.
 *  3. Stok dikurangi saat order dibuat (transaksi atomik + lockForUpdate),
 *     agar tidak oversell.
 *  4. Anti klik ganda lewat idempotency key dari form.
 */
class OnlineOrderService
{
    public function __construct(
        private readonly PosCartService $cart,
        private readonly PromotionService $promotions,
        private readonly StockService $stock,
        private readonly ShippingService $shipping,
        private readonly DocumentNumberService $numbers,
    ) {}

    /**
     * Simpan order online dari keranjang session.
     *
     * @param  array{name: string, phone: string, address: string, note?: string, shipping_zone?: string, shipping_provider?: string}  $buyer
     */
    public function place(array $buyer, string $idempotencyKey): Sale
    {
        if ($idempotencyKey !== '') {
            $existing = Sale::query()
                ->where('idempotency_key', $idempotencyKey)
                ->with('items')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $items = $this->cart->buildItems();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang belanja masih kosong.',
            ]);
        }

        $promo = $this->promotions->apply($items);
        $subtotal = round($items->sum('subtotal'), 2);
        $discountTotal = (float) $promo['total_discount'];
        $orderTotal = round($subtotal - $discountTotal, 2);

        $shipping = $this->shipping->calculate(
            $buyer['shipping_zone'] ?? null,
            $orderTotal,
        );
        $total = round($orderTotal + $shipping['cost'], 2);

        $sale = DB::transaction(function () use (
            $buyer, $idempotencyKey, $items, $promo, $subtotal, $discountTotal,
            $orderTotal, $shipping, $total
        ) {
            $sale = Sale::create([
                'invoice_number' => $this->numbers->nextInvoiceNumber(),
                'user_id' => auth()->id(),
                'buyer_name' => $buyer['name'],
                'buyer_phone' => $buyer['phone'],
                'buyer_address' => $buyer['address'],
                'buyer_note' => $buyer['note'] ?? null,
                'shipping_zone' => $buyer['shipping_zone'] ?? null,
                'shipping_provider' => $buyer['shipping_provider'] ?? null,
                'shipping_cost' => $shipping['cost'],
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'total' => $total,
                'paid_amount' => 0,
                'change_amount' => 0,
                'status' => SaleStatus::PendingPayment->value,
                'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                $qty = (int) $item['qty'];
                $variant = $item['variant'] ?? null;

                // Kunci stok varian dahulu (bila ada), lalu kunci produk.
                if ($variant) {
                    $lockedVariant = ProductVariant::query()
                        ->lockForUpdate()
                        ->findOrFail($variant->id);

                    if ($lockedVariant->stock < $qty) {
                        throw ValidationException::withMessages([
                            'cart' => sprintf(
                                'Stok %s untuk varian %s tersisa %d; diminta %d.',
                                $lockedVariant->product?->name ?? 'produk',
                                $lockedVariant->label(),
                                $lockedVariant->stock,
                                $qty
                            ),
                        ]);
                    }

                    $lockedVariant->decrement('stock', $qty);
                }

                // Kunci stok produk; stok dikurangi segera agar tidak oversell.
                $locked = Product::query()->lockForUpdate()->findOrFail($product->id);

                if ($locked->stock < $qty) {
                    throw ValidationException::withMessages([
                        'cart' => sprintf(
                            'Stok %s tidak mencukupi. Tersedia %d, diminta %d.',
                            $locked->name,
                            $locked->stock,
                            $qty
                        ),
                    ]);
                }

                $itemDiscount = $promo['items'][$item['index']]['discount'] ?? 0.0;
                $itemTotal = $promo['items'][$item['index']]['total'] ?? $item['subtotal'];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $locked->id,
                    'product_name' => $locked->name,
                    'barcode' => $locked->barcode,
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                    'qty' => $qty,
                    'price' => $item['price'],
                    'cost_price' => $item['cost_price'],
                    'discount' => $itemDiscount,
                    'subtotal' => $item['subtotal'],
                    'total' => $itemTotal,
                ]);

                $this->stock->apply(
                    $locked,
                    -$qty,
                    StockMovementType::Sale,
                    null,
                    'Pesanan online '.$sale->invoice_number,
                    'sale',
                    $sale->id,
                );
            }

            return $sale;
        });

        $this->cart->clear();

        return $sale->load('items');
    }
}
