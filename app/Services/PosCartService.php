<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Keranjang belanja.
 *
 * KERANJANG DISIMPAN DI SERVER (pada session), bukan di browser.
 * Baris diidentifikasi dengan kunci "product_id~color~size" (tilde sebagai
 * pemisah, aman di URL). Harga SELALU dihitung ulang di server; nilai dari
 * browser tidak pernah dipercaya.
 */
class PosCartService
{
    public const SESSION_KEY = 'pos_cart';

    /**
     * Tambah produk ke keranjang (opsional dengan varian).
     */
    public function add(Product $product, int $qty, ?string $size = null, ?string $color = null): void
    {
        if ($qty < 1) {
            return;
        }

        $items = $this->rawItems();
        $key = $this->keyOf($product->id, $size, $color);

        if (isset($items[$key])) {
            $items[$key]['qty'] = (int) $items[$key]['qty'] + $qty;
        } else {
            $items[$key] = [
                'key' => $key,
                'product_id' => $product->id,
                'size' => $size,
                'color' => $color,
                'qty' => $qty,
            ];
        }

        session()->put(self::SESSION_KEY, ['items' => $items]);
    }

    /**
     * Ubah jumlah pada baris keranjang.
     */
    public function updateQty(string $key, int $qty): void
    {
        $items = $this->rawItems();

        if (! isset($items[$key])) {
            return;
        }

        if ($qty < 1) {
            unset($items[$key]);
            session()->put(self::SESSION_KEY, ['items' => $items]);

            return;
        }

        $items[$key]['qty'] = $qty;
        session()->put(self::SESSION_KEY, ['items' => $items]);
    }

    /**
     * Hapus satu baris.
     */
    public function remove(string $key): void
    {
        $items = $this->rawItems();
        unset($items[$key]);
        session()->put(self::SESSION_KEY, ['items' => $items]);
    }

    /**
     * Kosongkan keranjang.
     */
    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function isEmpty(): bool
    {
        return $this->rawItems() === [];
    }

    /**
     * Kunci unik baris keranjang.
     */
    private function keyOf(int|string $productId, ?string $size, ?string $color): string
    {
        return ($color ? trim($color) : 'null').'~' . ($size ? trim($size) : 'null').'~' . $productId;
    }

    /**
     * Isi mentah keranjang dari session.
     */
    public function rawItems(): array
    {
        $stored = session(self::SESSION_KEY, ['items' => []]);
        $items = $stored['items'] ?? [];

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, function ($item) {
            return is_array($item) && isset($item['product_id'], $item['qty']) && (int) $item['qty'] > 0;
        }));
    }

    /**
     * Bangun keranjang lengkap dengan harga yang dihitung di server.
     *
     * Bila produk memakai varian, baris divalidasi terhadap varian itu
     * (stok & harga). Produk nonaktif / varian hilang dilewati.
     */
    public function buildItems(): Collection
    {
        $raw = $this->rawItems();

        if ($raw === []) {
            return collect();
        }

        $productIds = array_unique(array_column($raw, 'product_id'));
        $products = Product::query()
            ->with('category', 'variants')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $items = collect();

        foreach ($raw as $row) {
            $product = $products->get((int) $row['product_id']);

            if (! $product || ! $product->is_active) {
                continue;
            }

            $size = $row['size'] ?? null;
            $color = $row['color'] ?? null;

            $variant = null;

            if ($product->hasVariants()) {
                $variant = $product->variants->first(fn ($v) => $v->size == $size && $v->color == $color);

                if (! $variant) {
                    continue; // Varian tidak lagi tersedia.
                }
            }

            $stock = $variant ? (int) $variant->stock : (int) $product->stock;
            $qty = (int) $row['qty'];
            $price = (float) $product->sale_price;

            $items->push([
                'index' => $row['key'] ?? $this->keyOf($product->id, $size, $color),
                'key' => $row['key'] ?? $this->keyOf($product->id, $size, $color),
                'product' => $product,
                'variant' => $variant,
                'size' => $size,
                'color' => $color,
                'qty' => $qty,
                'price' => $price,
                'cost_price' => (float) $product->cost_price,
                'subtotal' => round($price * $qty, 2),
                'stock' => $stock,
            ]);
        }

        return $items;
    }
}
