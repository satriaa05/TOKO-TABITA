<?php

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\CustomerNotification;
use App\Models\OrderStatusHistory;
use App\Models\PaymentChannel;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Order online (guest checkout) untuk pembeli toko baju.
 *
 * ATURAN POKOK:
 *  1. Keranjang & harga SELALU dihitung ulang di server; nilai dari browser
 *     tidak pernah dipercaya.
 *  2. Voucher, metode pembayaran, dan alamat juga diverifikasi ulang di sini.
 *  3. Semua status (payment_status, fulfillment_status) dan record turunan
 *     dibuat dalam SATU transaksi database bersama potongan stok.
 *  4. Status pembayaran awal selalu UNPAID. Tidak ada status PAID yang
 *     ditentukan oleh redirect browser.
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
     * @param  array{name: string, phone: string, address: string, note?: string, shipping_zone?: string, shipping_provider?: string, postal_code?: string}  $buyer
     * @param  array{
     *     payment_method?: string,
     *     voucher_code?: string,
     *     address_id?: int|null,
     *     courier?: string|null,
     *     service?: string|null
     * }  $options
     */
    public function place(array $buyer, string $idempotencyKey, array $options = []): Sale
    {
        if ($idempotencyKey !== '') {
            $existing = Sale::query()
                ->where('idempotency_key', $idempotencyKey)
                ->where('user_id', auth()->id())
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

        $sale = $this->transactional(function () use ($buyer, $idempotencyKey, $items, $options) {
            $promo = $this->promotions->apply($items);
            $subtotal = round($items->sum('subtotal'), 2);
            $discountTotal = (float) $promo['total_discount'];

            // Voucher: kunci baris agar kuota tidak race condition.
            $voucher = null;
            $voucherDiscount = 0.0;
            $voucherCode = trim((string) ($options['voucher_code'] ?? ''));

            if ($voucherCode !== '') {
                /** @var Voucher|null $voucher */
                $voucher = Voucher::query()
                    ->where('code', $voucherCode)
                    ->lockForUpdate()
                    ->first();

                if (! $voucher || ! $voucher->isValidNow()) {
                    throw ValidationException::withMessages([
                        'voucher_code' => 'Kode voucher tidak valid atau sudah tidak berlaku.',
                    ]);
                }

                if ($voucher->per_user_limit > 0 && $voucher->usageByUser((int) auth()->id()) >= $voucher->per_user_limit) {
                    throw ValidationException::withMessages([
                        'voucher_code' => 'Voucher ini sudah pernah dipakai oleh akun Anda.',
                    ]);
                }

                $voucherDiscount = $voucher->discountFor(round($subtotal - $discountTotal, 2));

                if ($voucherDiscount <= 0) {
                    throw ValidationException::withMessages([
                        'voucher_code' => 'Minimum belanja untuk voucher ini belum terpenuhi.',
                    ]);
                }
            }

            $orderTotal = round(max(0, $subtotal - $discountTotal - $voucherDiscount), 2);

            $shipping = $this->shipping->quote(
                $buyer['shipping_zone'] ?? null,
                $orderTotal,
                $buyer['postal_code'] ?? null,
                $options['courier'] ?? null,
                $options['service'] ?? null,
            );
            $total = round($orderTotal + $shipping['cost'], 2);

            $shippingLabel = $buyer['shipping_provider'] ?? null;

            if ($shipping['source'] === 'biteship' && $shipping['courier']) {
                $label = strtoupper($shipping['courier']);
                if ($shipping['service']) {
                    $label .= ' · '.$shipping['service'];
                }
                if ($shipping['eta']) {
                    $label .= ' · Estimasi '.$shipping['eta'];
                }
                $shippingLabel = $label;
            }

            $channelCode = (string) ($options['payment_method'] ?? 'transfer');
            /** @var PaymentChannel|null $channel */
            $channel = PaymentChannel::query()->where('code', $channelCode)->first();

            if (! $channel || ! $channel->is_active) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Metode pembayaran ini belum tersedia.',
                ]);
            }

            [$sale, $created] = $this->createSale([
                'invoice_number' => $this->numbers->nextInvoiceNumber(),
                'user_id' => auth()->id(),
                'buyer_name' => $buyer['name'],
                'buyer_phone' => $buyer['phone'],
                'buyer_address' => $buyer['address'],
                'buyer_postal_code' => $buyer['postal_code'] ?? null,
                'buyer_note' => $buyer['note'] ?? null,
                'shipping_zone' => $buyer['shipping_zone'] ?? null,
                'shipping_provider' => $shippingLabel,
                'shipping_cost' => $shipping['cost'],
                'shipping_eta' => $shipping['eta'],
                'subtotal' => $subtotal,
                'discount_total' => round($discountTotal + $voucherDiscount, 2),
                'total' => $total,
                'paid_amount' => 0,
                'change_amount' => 0,
                'status' => SaleStatus::PendingPayment->value,
                'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
            ], $idempotencyKey);

            if (! $created) {
                // Request duplikat dengan kunci yang sama: jangan potong stok lagi.
                return $sale;
            }

            $sale->payment_status = PaymentStatus::Unpaid->value;
            $sale->fulfillment_status = FulfillmentStatus::Pending->value;
            $sale->save();

            foreach ($items as $item) {
                $product = $item['product'];
                $qty = (int) $item['qty'];
                $variant = $item['variant'] ?? null;

                // Kunci stok varian dahulu (bila ada), lalu kunci produk.
                if ($variant) {
                    $lockedVariant = ProductVariant::query()
                        ->lockForUpdate()
                        ->where('is_active', true)
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
                }

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

                // Simpan snapshot varian & foto warna yang benar-benar dipilih
                // pembeli, agar riwayat/invoice tidak berubah bila admin
                // mengganti foto atau stok varian kemudian.
                $colorOption = null;

                if (! empty($item['color'])) {
                    $colorOption = \App\Models\ProductColor::query()
                        ->where('product_id', $locked->id)
                        ->where('name', $item['color'])
                        ->first();
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $locked->id,
                    'product_name' => $locked->name,
                    'barcode' => $locked->barcode,
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                    'product_variant_id' => $variant?->id,
                    'color_id' => $colorOption?->id,
                    'media_id' => $colorOption?->thumbnail_media_id,
                    'qty' => $qty,
                    'price' => $item['price'],
                    'cost_price' => $item['cost_price'],
                    'discount' => $itemDiscount,
                    'subtotal' => $item['subtotal'],
                    'total' => $itemTotal,
                ]);

                $this->stock->apply(
                    product: $locked,
                    qty: -$qty,
                    type: StockMovementType::Sale,
                    user: null,
                    note: 'Pesanan online '.$sale->invoice_number,
                    refType: 'sale',
                    refId: $sale->id,
                    variant: $variant,
                    color: $item['color'] ?? null,
                    size: $item['size'] ?? null,
                );
            }

            // Satu transaksi pembayaran untuk pesanan ini (belum lunas).
            PaymentTransaction::create([
                'sale_id' => $sale->id,
                'payment_method_id' => $channel->id,
                'transaction_reference' => 'TRX-'.$sale->invoice_number,
                'idempotency_key' => Str::random(60),
                'amount' => $total,
                'status' => PaymentStatus::Unpaid->value,
                'metadata' => [
                    'mode' => $channel->mode,
                    'provider' => $channel->provider,
                    'courier' => $options['courier'] ?? null,
                    'service' => $options['service'] ?? null,
                    'address_id' => $options['address_id'] ?? null,
                    'voucher' => $voucher?->code,
                ],
            ]);

            if ($voucher) {
                VoucherUsage::create([
                    'voucher_id' => $voucher->id,
                    'user_id' => (int) auth()->id(),
                    'sale_id' => $sale->id,
                    'discount_amount' => $voucherDiscount,
                ]);

                $voucher->increment('used_count');
            }

            OrderStatusHistory::create([
                'sale_id' => $sale->id,
                'new_payment_status' => PaymentStatus::Unpaid->value,
                'new_fulfillment_status' => FulfillmentStatus::Pending->value,
                'changed_by' => auth()->id(),
                'source' => 'system',
                'note' => 'Pesanan dibuat dari checkout toko.',
            ]);

            CustomerNotification::create([
                'user_id' => (int) auth()->id(),
                'sale_id' => $sale->id,
                'event_key' => 'order_created-'.$sale->id,
                'type' => NotificationType::OrderCreated->value,
                'title' => NotificationType::OrderCreated->label(),
                'message' => 'Pesanan '.$sale->invoice_number.' berhasil dibuat. Selesaikan pembayaran Anda.',
                'icon' => NotificationType::OrderCreated->icon(),
                'data' => ['invoice_number' => $sale->invoice_number, 'total' => $total],
            ]);

            return $sale;
        });

        $this->cart->clear();

        return $sale->load('items');
    }

    /**
     * Jalankan transaksi checkout dengan retry saat database sedang terkunci.
     *
     * Di SQLite (server uji konkurensi) lock bersifat database-level; di MySQL
     * lockForUpdate bekerja per baris. Retry membuat pembeli yang kalah balapan
     * mendapat validasi stok, bukan HTTP 500.
     */
    private function transactional(callable $callback): Sale
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                return DB::transaction($callback);
            } catch (QueryException $e) {
                if ($attempt === 5 || ! $this->isLockContention($e)) {
                    throw $e;
                }

                usleep(50000 * $attempt);
            }
        }

        throw new \RuntimeException('Gagal menyimpan pesanan setelah beberapa percobaan.');
    }

    private function isLockContention(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'database is locked')
            || str_contains($message, 'database table is locked')
            || str_contains($message, 'deadlock')
            || str_contains($message, '40001')
            || str_contains($message, 'lock wait timeout');
    }

    /**
     * Buat baris Sale dengan aman terhadap bentrok UNIQUE.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{Sale, bool}  [penjualan, apakah baris baru dibuat]
     */
    private function createSale(array $attributes, string $idempotencyKey): array
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return [Sale::create($attributes), true];
            } catch (QueryException $e) {
                if ($idempotencyKey !== ''
                    && $this->isUniqueViolation($e, 'idempotency_key')) {
                    $existing = Sale::query()
                        ->where('idempotency_key', $idempotencyKey)
                        ->where('user_id', auth()->id())
                        ->with('items')
                        ->first();

                    if ($existing !== null) {
                        return [$existing, false];
                    }
                }

                if ($this->isUniqueViolation($e, 'invoice_number')) {
                    $attributes['invoice_number'] = $this->numbers->nextInvoiceNumber();

                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Gagal membuat nomor invoice unik setelah beberapa percobaan.');
    }

    private function isUniqueViolation(QueryException $e, string $column): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, $column)
            && (str_contains($message, 'unique') || str_contains($message, 'duplicate'));
    }
}
