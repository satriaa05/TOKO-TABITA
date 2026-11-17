<?php

namespace App\Services;

use App\Enums\PromotionType;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Collection;

/**
 * Penerapan promo & diskon pada keranjang.
 *
 * ATURAN MVP:
 *  - Hanya promo AKTIF (is_active = true dan berada dalam periode) yang dipakai.
 *  - Setiap produk hanya boleh mendapatkan SATU promo (tidak boleh bertumpuk).
 *  - Jika beberapa promo berlaku untuk produk yang sama, yang dipakai adalah
 *    promo dengan NILAI DISKON TERBESAR. Jika seri, yang lebih dulu dibuat
 *    (id terkecil) yang dipakai - hasilnya selalu konsisten.
 *  - Promo dengan `min_purchase` hanya berlaku bila total belanja (sebelum
 *    diskon) sudah mencapai batas minimum tersebut.
 *  - Nilai diskon tidak boleh melebihi harga barang (harga akhir tidak negatif).
 */
class PromotionService
{
    /**
     * Hitung diskon untuk sekumpulan item keranjang.
     *
     * @param  Collection<int, array{product_id?: int, product?: Product, subtotal: float}>  $items
     * @return array{
     *     items: array<int, array{discount: float, total: float, promotion: ?string}>,
     *     total_discount: float,
     *     applied: array<int, array{name: string, amount: float}>
     * }
     */
    public function apply(Collection $items): array
    {
        $empty = [
            'items' => [],
            'total_discount' => 0.0,
            'applied' => [],
        ];

        if ($items->isEmpty()) {
            return $empty;
        }

        $transactionSubtotal = round($items->sum('subtotal'), 2);

        // Kumpulkan seluruh promo aktif beserta produk yang memenuhi syarat.
        $promotions = Promotion::query()
            ->currentlyActive()
            ->with('products:id')
            ->get();

        if ($promotions->isEmpty()) {
            return $empty;
        }

        $result = [];
        $totalDiscount = 0.0;
        $applied = [];

        foreach ($items as $index => $item) {
            $productId = $item['product']->id ?? ($item['product_id'] ?? null);
            $subtotal = (float) $item['subtotal'];

            $discount = 0.0;
            $promotionName = null;
            $promotionId = null;

            foreach ($promotions as $promotion) {
                // Produk harus termasuk daftar produk promo.
                if (! $promotion->products->contains('id', $productId)) {
                    continue;
                }

                // Minimal belanja harus tercapai.
                if ($transactionSubtotal < (float) $promotion->min_purchase) {
                    continue;
                }

                $candidate = $this->discountFor($promotion, $subtotal);

                // Ambil diskon terbesar; seri -> id promo terkecil.
                if ($candidate > $discount || ($candidate === $discount && $candidate > 0 && $promotionId !== null && $promotion->id < $promotionId)) {
                    $discount = $candidate;
                    $promotionName = $promotion->name;
                    $promotionId = $promotion->id;
                }
            }

            $discount = round(min($discount, $subtotal), 2);
            $total = round($subtotal - $discount, 2);

            $result[$index] = [
                'discount' => $discount,
                'total' => $total,
                'promotion' => $promotionName,
            ];

            $totalDiscount += $discount;

            if ($discount > 0 && $promotionName !== null) {
                $applied[$promotionName] = ($applied[$promotionName] ?? 0) + $discount;
            }
        }

        return [
            'items' => $result,
            'total_discount' => round($totalDiscount, 2),
            'applied' => $applied,
        ];
    }

    /**
     * Nilai diskon dari sebuah promo untuk sebuah subtotal.
     */
    private function discountFor(Promotion $promotion, float $subtotal): float
    {
        $value = (float) $promotion->value;

        if ($promotion->type === PromotionType::Percent) {
            // Batasi sesuai config maksimum diskon.
            $maxPercent = (float) config('pos.max_discount_percent', 100);
            $percent = min($value, $maxPercent);

            return round($subtotal * $percent / 100, 2);
        }

        // Diskon nominal tidak boleh melebihi subtotal.
        return round(min($value, $subtotal), 2);
    }
}
