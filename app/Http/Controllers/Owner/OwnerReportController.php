<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Laporan bisnis untuk Owner.
 *
 * Menghubungkan SalesReportService ke view reports/ yang sudah
 * tersedia. Owner dapat melihat laporan penjualan, laba kotor,
 * produk, dan inventori — semua read-only.
 */
class OwnerReportController extends Controller
{
    public function __construct(private readonly SalesReportService $reportService) {}

    /**
     * Laporan penjualan: omzet, grafik, rincian harian, per metode bayar.
     */
    public function sales(Request $request): View
    {
        [$from, $to, $period] = $this->resolvePeriod($request);

        $comparison = $this->reportService->compare($from, $to);
        $daily = $this->reportService->dailyBreakdown($from, $to);
        $byPayment = $this->reportService->salesByPaymentMethod($from, $to);

        // Grafik penjualan harian.
        $chart = $this->buildChartSeries($from, $to);

        return view('reports.sales', [
            'base' => 'owner',
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'comparison' => $comparison,
            'daily' => $daily,
            'byPayment' => $byPayment,
            'chart' => $chart,
        ]);
    }

    /**
     * Laporan laba kotor: pendapatan, HPP, margin, kontribusi laba per produk.
     */
    public function profit(Request $request): View
    {
        [$from, $to, $period] = $this->resolvePeriod($request);

        $comparison = $this->reportService->compare($from, $to);
        $topProducts = $this->reportService->topProducts($from, $to);
        $daily = $this->reportService->dailyBreakdown($from, $to);

        return view('reports.profit', [
            'base' => 'owner',
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'comparison' => $comparison,
            'topProducts' => $topProducts,
            'daily' => $daily,
        ]);
    }

    /**
     * Laporan produk: terlaris, stok menipis, nilai persediaan.
     */
    public function products(Request $request): View
    {
        [$from, $to, $period] = $this->resolvePeriod($request);

        $topProducts = $this->reportService->topProducts($from, $to);
        $slowProducts = $this->reportService->slowProducts($from, $to);
        $lowStockProducts = $this->reportService->lowStockProducts();
        $inventoryValue = $this->reportService->inventoryValue();
        $lowStock = $lowStockProducts;

        return view('reports.products', [
            'base' => 'owner',
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'topProducts' => $topProducts,
            'slowProducts' => $slowProducts,
            'lowStockProducts' => $lowStockProducts,
            'lowStock' => $lowStock,
            'inventoryValue' => $inventoryValue,
        ]);
    }

    /**
     * Laporan inventori: nilai persediaan, mutasi stok masuk/keluar.
     */
    public function inventory(Request $request): View
    {
        [$from, $to, $period] = $this->resolvePeriod($request);

        $inventoryValue = $this->reportService->inventoryValue();
        $movements = $this->reportService->stockMovementSummary($from, $to);
        $lowStockProducts = $this->reportService->lowStockProducts();

        $products = $this->reportService->inventoryProducts();
        $byCategory = $this->reportService->inventoryByCategory();

        return view('reports.inventory', [
            'base' => 'owner',
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'inventoryValue' => $inventoryValue,
            'movements' => $movements,
            'lowStockProducts' => $lowStockProducts,
            'lowStock' => $lowStockProducts,
            'products' => $products,
            'byCategory' => $byCategory,
        ]);
    }

    /**
     * Resolve rentang tanggal dari query string periode/dari/sampai.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->query('periode', '30');
        $now = now();

        return match ($period) {
            'hari_ini' => [$now->toDateString(), $now->toDateString(), $period],
            '7' => [$now->copy()->subDays(6)->toDateString(), $now->toDateString(), $period],
            '30' => [$now->copy()->subDays(29)->toDateString(), $now->toDateString(), $period],
            '90' => [$now->copy()->subDays(89)->toDateString(), $now->toDateString(), $period],
            '365' => [$now->copy()->subDays(364)->toDateString(), $now->toDateString(), $period],
            'custom' => [
                $request->query('dari', $now->copy()->subDays(29)->toDateString()),
                $request->query('sampai', $now->toDateString()),
                $period,
            ],
            default => [$now->copy()->subDays(29)->toDateString(), $now->toDateString(), '30'],
        };
    }

    /**
     * Bangun deret grafik penjualan harian (zero-filled).
     *
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function buildChartSeries(string $from, string $to): array
    {
        $daily = $this->reportService->dailyBreakdown($from, $to);

        $byDate = $daily->keyBy(fn ($row) => \Carbon\Carbon::parse($row->tanggal)->format('Y-m-d'));

        $labels = [];
        $values = [];

        $start = \Carbon\CarbonImmutable::parse($from)->startOfDay();
        $end = \Carbon\CarbonImmutable::parse($to)->endOfDay();

        for ($day = $start; $day <= $end; $day = $day->addDay()) {
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('d/m');
            $values[] = (float) ($byDate->has($key) ? $byDate[$key]->omzet : 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
