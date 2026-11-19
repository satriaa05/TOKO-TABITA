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
        $index = $this->findIndex($items, $key);

        if ($index !== null) {
            $items[$index]['qty'] = (int) $items[$index]['qty'] + $qty;
            $items[$index]['size'] = $size;
            $items[$index]['color'] = $color;
        } else {
            $items[$key] = [
                'key' => $key,
                'product_id' => $product->id,
                'size' => $size,
                'color' => $color,
                'qty' => $qty,
            ];
        }

        $this->persist($items);
    }

    /**
     * Ubah jumlah pada baris keranjang.
     */
    public function updateQty(string $key, int $qty): void
    {
        $items = $this->rawItems();
        $index = $this->findIndex($items, $key);

        if ($index === null) {
            return;
        }

        if ($qty < 1) {
            unset($items[$index]);
            $this->persist($items);

            return;
        }

        $items[$index]['qty'] = $qty;
        $this->persist($items);
    }

    /**
     * Hapus satu baris.
     */
    public function remove(string $key): void
    {
        $items = $this->rawItems();
        $index = $this->findIndex($items, $key);

        if ($index !== null) {
            unset($items[$index]);
        }

        $this->persist($items);
    }

    /**
     * Cari posisi baris berdasarkan kunci.
     *
     * Kompatibel dengan session lama: kunci bisa berupa key array associatif
     * ATAU field `key` pada tiap baris.
     *
     * @param  array<int|string, array<string, mixed>>  $items
     */
    private function findIndex(array $items, string $key): int|string|null
    {
        if (array_key_exists($key, $items)) {
            return $key;
        }

        foreach ($items as $index => $item) {
            if (($item['key'] ?? null) === $key) {
                return $index;
            }
        }

        return null;
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
        $clean = static fn (?string $value): string => $value === null
            ? 'null'
            : (preg_replace('/[^A-Za-z0-9_-]/', '-', trim($value)) ?: 'null');

        return $clean($color).'~'.$clean($size).'~'.$productId;
    }

    /**
     * Jumlah satu baris varian yang sekarang ada di keranjang.
     */
    public function qtyFor(int|string $productId, ?string $size, ?string $color): int
    {
        $key = $this->keyOf($productId, $size, $color);

        foreach ($this->rawItems() as $item) {
            if (($item['key'] ?? null) === $key) {
                return (int) $item['qty'];
            }
        }

        return 0;
    }

    private function guardOwner(): void
    {
        if (! session()->has(self::SESSION_KEY)) {
            return;
        }

        $storedUserId = session(self::SESSION_KEY.'.user_id');
        $currentUserId = auth()->id();

        // Keranjang tamu yang kemudian login di sesi yang sama boleh dipakai akun itu.
        if ($storedUserId === null && $currentUserId !== null) {
            session()->put(self::SESSION_KEY.'.user_id', $currentUserId);

            return;
        }

        if ((int) $storedUserId !== (int) $currentUserId) {
            session()->forget(self::SESSION_KEY);
        }
    }

    /**
     * Simpan keranjang beserta pemiliknya.
     *
     * Session sudah terisolasi per browser, tetapi menyimpan user_id membuat
     * keranjang juga terikat akun. Bila akun di session berubah (logout/login
     * di browser yang sama), isi keranjang lama dibuang, bukan diberikan ke
     * akun lain.
     */
    private function persist(array $items): void
    {
        session()->put(self::SESSION_KEY, [
            'user_id' => auth()->id(),
            'items' => $items,
        ]);
    }

    /**
     * Isi mentah keranjang dari session.
     */
    public function rawItems(): array
    {
        $this->guardOwner();

        $stored = session(self::SESSION_KEY, ['items' => []]);
        $items = $stored['items'] ?? [];

        if (! is_array($items)) {
            return [];
        }

        // Jangan pakai array_values(): kunci array dipakai untuk update/hapus
        // baris. Field `key` tetap disimpan sebagai fallback data lama.
        return array_filter($items, function ($item) {
            return is_array($item) && isset($item['product_id'], $item['qty']) && (int) $item['qty'] > 0;
        });
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
                $variant = $product->variants->first(fn ($v) => $v->size == $size
                    && $v->color == $color
                    && ($v->is_active ?? true));

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
