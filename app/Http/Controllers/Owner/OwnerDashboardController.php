<?php

namespace App\Http\Controllers\Owner;

use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Dashboard owner: pantau kinerja bisnis toko baju online.
 *
 * Berisi laporan asli dari tabel sales / sale_items: omzet, jumlah
 * transaksi, rata-rata transaksi, laba kotor, nilai persediaan,
 * grafik penjualan harian, produk terlaris, stok menipis dan
 * transaksi terakhir.
 */
class OwnerDashboardController extends Controller
{
    /**
     * Tampilkan dashboard owner.
     */
    public function index(): View
    {
        [$from, $to, $period, $periodOptions] = $this->periodRange();

        $sales = Sale::query()
            ->with('items')
            ->where('status', SaleStatus::Completed->value)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $totalSales = (float) $sales->sum(fn (Sale $sale) => (float) $sale->total);
        $transactionCount = $sales->count();
        $averageTransaction = $transactionCount > 0 ? $totalSales / $transactionCount : 0.0;

        $grossProfit = (float) $sales->sum(fn (Sale $sale) => (float) $sale->grossProfit());

        $inventoryValue = (float) Product::query()
            ->where('is_active', true)
            ->get()
            ->sum(fn (Product $product) => $product->stock * (float) $product->cost_price);

        // Produk terlaris pada periode.
        $topProducts = $this->topProducts($from, $to);

        // Grafik penjualan per hari.
        $chart = $this->salesChart($from, $to);

        $lowStockProducts = Product::query()
            ->with('category')
            ->active()
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->limit(10)
            ->get();

        $recentSales = Sale::query()
            ->with('items')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('owner.dashboard', [
            'periodOptions' => $periodOptions,
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'summary' => [
                'total_sales' => $totalSales,
                'transaction_count' => $transactionCount,
                'average_transaction' => $averageTransaction,
                'gross_profit' => $grossProfit,
            ],
            'inventoryValue' => $inventoryValue,
            'chart' => $chart,
            'topProducts' => $topProducts,
            'lowStockProducts' => $lowStockProducts,
            'recentSales' => $recentSales,
        ]);
    }

    /**
     * Resolve periode laporan dari query string.
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon, 2: string, 3: array<string, string>}
     */
    private function periodRange(): array
    {
        $periodOptions = [
            '7' => '7 hari terakhir',
            '30' => '30 hari terakhir',
            'month' => 'Bulan ini',
            'year' => 'Tahun ini',
        ];

        $period = request()->query('period', '30');

        if (! array_key_exists($period, $periodOptions)) {
            $period = '30';
        }

        $now = now();
        [$from, $to] = match ($period) {
            '7' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };

        return [$from, $to, (string) $period, $periodOptions];
    }

    /**
     * Produk terlaris (berdasarkan qty terjual) pada rentang tanggal.
     */
    private function topProducts(\Carbon\Carbon $from, \Carbon\Carbon $to): Collection
    {
        $items = SaleItem::query()
            ->whereHas('sale', fn ($query) => $query
                ->where('status', SaleStatus::Completed->value)
                ->whereBetween('created_at', [$from, $to]))
            ->get();

        return $items
            ->groupBy('product_name')
            ->map(function (Collection $rows, string $name) {
                $qty = $rows->sum('qty');
                $omzet = (float) $rows->sum(fn ($item) => (float) $item->total);
                $laba = (float) $rows->sum(fn ($item) => (float) $item->total - ((float) $item->cost_price * $item->qty));

                return (object) [
                    'product_name' => $name,
                    'qty_terjual' => (int) $qty,
                    'omzet' => $omzet,
                    'laba' => $laba,
                ];
            })
            ->sortByDesc('qty_terjual')
            ->take(5)
            ->values();
    }

    /**
     * Siapkan data grafik penjualan per hari (zero-filled).
     */
    private function salesChart(\Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $sales = Sale::query()
            ->where('status', SaleStatus::Completed->value)
            ->whereBetween('created_at', [$from, $to])
            ->get(['created_at', 'total']);

        $totalsByDate = $sales
            ->groupBy(fn (Sale $sale) => $sale->created_at->format('Y-m-d'))
            ->map(fn (Collection $group) => (float) $group->sum(fn (Sale $sale) => (float) $sale->total));

        $labels = [];
        $values = [];

        for ($day = $from->copy(); $day <= $to; $day = $day->addDay()) {
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('d/m');
            $values[] = (float) ($totalsByDate[$key] ?? 0.0);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
