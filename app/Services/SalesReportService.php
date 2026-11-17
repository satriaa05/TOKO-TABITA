<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Perhitungan laporan penjualan, laba kotor, dan performa admin.
 *
 * PENTING:
 *  - Semua angka berasal dari data nyata di database.
 *  - Transaksi berstatus `refunded` dan `voided` TIDAK dihitung sebagai
 *    pendapatan, karena uang/produk sudah dikembalikan.
 *  - Harga pokok diambil dari `sale_items.cost_price` (snapshot historis),
 *    bukan dari `products.cost_price` saat ini. Jadi mengubah harga produk
 *    sekarang tidak mengubah hasil laporan transaksi lama.
 */
class SalesReportService
{
    /**
     * Ringkasan penjualan untuk suatu rentang tanggal (inklusif).
     *
     * @return array{
     *     total_sales: float, total_discount: float, total_qty: int,
     *     transaction_count: int, average_transaction: float,
     *     total_cogs: float, gross_profit: float,
     *     refunded_count: int, voided_count: int
     * }
     */
    public function summary(string $from, string $to): array
    {
        $base = Sale::query()
            ->active()
            ->betweenDates($from, $to);

        $totals = (clone $base)
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('COALESCE(SUM(total), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(discount_total), 0) as total_discount')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid')
            ->first();

        $totalSales = (float) $totals->total_sales;
        $transactionCount = (int) $totals->transaction_count;

        // Harga pokok barang yang terjual (historis).
        $totalCogs = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->selectRaw('COALESCE(SUM(sale_items.qty * sale_items.cost_price), 0) as cogs')
            ->value('cogs');

        return [
            'total_sales' => round($totalSales, 2),
            'total_discount' => round((float) $totals->total_discount, 2),
            'total_paid' => round((float) $totals->total_paid, 2),
            'total_qty' => (int) SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.status', 'completed')
                ->whereBetween('sales.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
                ->sum('sale_items.qty'),
            'transaction_count' => $transactionCount,
            'average_transaction' => $transactionCount > 0
                ? round($totalSales / $transactionCount, 2)
                : 0.0,
            'total_cogs' => round($totalCogs, 2),
            'gross_profit' => round($totalSales - $totalCogs, 2),
            'refunded_count' => Sale::query()
                ->where('status', 'refunded')
                ->betweenDates($from, $to)
                ->count(),
            'voided_count' => Sale::query()
                ->where('status', 'voided')
                ->betweenDates($from, $to)
                ->count(),
        ];
    }

    /**
     * Deret penjualan harian untuk grafik.
     *
     * @param  int  $days  Jumlah hari ke belakang (termasuk hari ini).
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public function dailySeries(int $days = 14): array
    {
        $start = CarbonImmutable::today()->subDays($days - 1);

        $rows = Sale::query()
            ->active()
            ->where('created_at', '>=', $start->startOfDay())
            ->selectRaw('DATE(created_at) as tanggal, COALESCE(SUM(total), 0) as omzet')
            ->groupBy('tanggal')
            ->pluck('omzet', 'tanggal');

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i);
            $key = $date->toDateString();

            $labels[] = $date->translatedFormat('d M');
            $values[] = (float) ($rows[$key] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Produk terlaris dalam rentang tanggal tertentu.
     */
    public function topProducts(string $from, string $to, int $limit = 10): Collection
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->select('sale_items.product_id', 'sale_items.product_name')
            ->selectRaw('SUM(sale_items.qty) as qty_terjual')
            ->selectRaw('SUM(sale_items.total) as omzet')
            ->selectRaw('SUM(sale_items.qty * sale_items.cost_price) as hpp')
            ->orderByDesc('qty_terjual')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->omzet = (float) $row->omzet;
                $row->hpp = (float) $row->hpp;
                $row->laba = round($row->omzet - $row->hpp, 2);

                return $row;
            });
    }

    /**
     * Produk paling laris jarang (qty terjual paling rendah pada periode).
     */
    public function slowProducts(string $from, string $to, int $limit = 10): Collection
    {
        $rows = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->select('sale_items.product_id', 'sale_items.product_name')
            ->selectRaw('SUM(sale_items.qty) as qty_terjual')
            ->selectRaw('SUM(sale_items.total) as omzet')
            ->selectRaw('SUM(sale_items.qty * sale_items.cost_price) as hpp')
            ->orderBy('qty_terjual')
            ->limit($limit)
            ->get();

        // Data produk diambil terpisah supaya laporan bisa menampilkan
        // kategori dan stok terkini. Produk yang sudah dihapus tetap
        // ditampilkan lewat nama yang tersimpan di baris transaksi.
        $produk = Product::query()
            ->whereIn('id', $rows->pluck('product_id')->filter()->unique())
            ->with('category')
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($produk): array {
            $qty = (int) $row->qty_terjual;
            $omzet = (float) $row->omzet;
            $hpp = (float) $row->hpp;

            return [
                'product' => $produk->get($row->product_id),
                'product_name' => $row->product_name,
                'qty' => $qty,
                'qty_terjual' => $qty,
                'omzet' => $omzet,
                'hpp' => $hpp,
                'laba' => round($omzet - $hpp, 2),
            ];
        });
    }

    /**
     * Produk dengan stok menipis (stok <= stok minimum).
     */
    public function lowStockProducts(int $limit = 10): Collection
    {
        return Product::query()
            ->active()
            ->lowStock()
            ->with('category')
            ->orderBy('stock')
            ->limit($limit)
            ->get();
    }

    /**
     * Performa penjualan per pengguna admin.
     */
    public function adminPerformance(string $from, string $to): Collection
    {
        return User::query()
            ->where('role', 'admin')
            ->leftJoin('sales', function ($join) use ($from, $to) {
                $join->on('sales.user_id', '=', 'users.id')
                    ->where('sales.status', 'completed')
                    ->whereBetween('sales.created_at', [$from.' 00:00:00', $to.' 23:59:59']);
            })
            ->groupBy('users.id', 'users.name')
            ->select('users.id', 'users.name')
            ->selectRaw('COUNT(sales.id) as jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(sales.total), 0) as omzet')
            ->orderByDesc('omzet')
            ->get()
            ->map(function ($row) {
                $row->jumlah_transaksi = (int) $row->jumlah_transaksi;
                $row->omzet = (float) $row->omzet;
                $row->rata_rata = $row->jumlah_transaksi > 0
                    ? round($row->omzet / $row->jumlah_transaksi, 2)
                    : 0.0;

                return $row;
            });
    }

    /**
     * Omzet per metode pembayaran.
     */
    public function salesByPaymentMethod(string $from, string $to): Collection
    {
        return Payment::query()
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->groupBy('payments.method')
            ->select('payments.method')
            ->selectRaw('COUNT(payments.id) as jumlah')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as total')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $row->total = (float) $row->total;
                $row->jumlah = (int) $row->jumlah;

                return $row;
            });
    }

    /**
     * Nilai persediaan saat ini (metode FIFO disederhanakan: harga pokok terakhir).
     */
    public function inventoryValue(): float
    {
        return round((float) Product::query()
            ->active()
            ->selectRaw('COALESCE(SUM(stock * cost_price), 0) as nilai')
            ->value('nilai'), 2);
    }

    /**
     * Rincian persediaan per kategori: jumlah produk & nilai persediaannya.
     *
     * Dipakai halaman Laporan Inventori.
     *
     * @return array<int, array{name: string, count: int, value: float}>
     */
    public function inventoryByCategory(): array
    {
        return Product::query()
            ->active()
            ->with('category')
            ->get()
            ->groupBy(fn (Product $product): string => $product->category?->name ?? 'Tanpa Kategori')
            ->map(fn (Collection $produk, string $nama): array => [
                'name' => $nama,
                'count' => $produk->count(),
                'value' => round((float) $produk->sum(fn (Product $p): float => $p->inventory_value), 2),
            ])
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    /**
     * Rincian persediaan per produk, diurutkan dari nilai persediaan terbesar.
     *
     * Dipakai halaman Laporan Inventori.
     */
    public function inventoryProducts(int $limit = 100): Collection
    {
        return Product::query()
            ->active()
            ->with('category')
            ->orderByRaw('(stock * cost_price) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Total mutasi stok masuk & keluar dalam rentang tertentu.
     */
    public function stockMovementSummary(string $from, string $to): array
    {
        $base = StockMovement::query()
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);

        return [
            'masuk' => (int) (clone $base)->where('qty', '>', 0)->sum('qty'),
            'keluar' => abs((int) (clone $base)->where('qty', '<', 0)->sum('qty')),
        ];
    }

    /**
     * Query transaksi dengan filter rentang tanggal (dipakai halaman daftar).
     */
    public function salesQuery(?string $from = null, ?string $to = null): Builder
    {
        $query = Sale::query()->with(['user', 'items']);

        if ($from) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }

        if ($to) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        return $query;
    }

    /**
     * Bandingkan periode saat ini dengan periode sebelumnya (berjumlah hari sama).
     *
     * Contoh: 30 hari terakhir vs 30 hari sebelum itu.
     *
     * @return array{
     *     current: array, previous: array,
     *     prev_from: string, prev_to: string,
     *     days: int
     * }
     */
    public function compare(string $from, string $to): array
    {
        $fromDate = CarbonImmutable::parse($from)->startOfDay();
        $toDate = CarbonImmutable::parse($to)->endOfDay();

        $days = (int) $fromDate->diffInDays($toDate) + 1;

        $prevTo = $fromDate->subDay()->toDateString();
        $prevFrom = $fromDate->subDays($days)->toDateString();

        return [
            'current' => $this->summary($from, $to),
            'previous' => $this->summary($prevFrom, $prevTo),
            'prev_from' => $prevFrom,
            'prev_to' => $prevTo,
            'days' => $days,
        ];
    }

    /**
     * Persentase perubahan antara dua nilai.
     *
     * @return float|null Null bila periode sebelumnya nol (tidak bisa dihitung).
     */
    public function percentChange(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Rekap omzet per hari dalam rentang tanggal (untuk laporan tabel).
     */
    public function dailyBreakdown(string $from, string $to): Collection
    {
        return Sale::query()
            ->active()
            ->betweenDates($from, $to)
            ->selectRaw('DATE(created_at) as tanggal')
            ->selectRaw('COUNT(*) as jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(total), 0) as omzet')
            ->selectRaw('COALESCE(SUM(discount_total), 0) as diskon')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->map(function ($row) {
                $row->jumlah_transaksi = (int) $row->jumlah_transaksi;
                $row->omzet = (float) $row->omzet;
                $row->diskon = (float) $row->diskon;

                return $row;
            });
    }
}
