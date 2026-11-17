<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SalesReportService;
use Illuminate\View\View;

/**
 * Dashboard administrator: ringkasan konfigurasi & operasional toko.
 */
class AdminDashboardController extends Controller
{
    public function __construct(private readonly SalesReportService $report) {}

    /**
     * Tampilkan dashboard admin.
     */
    public function index(): View
    {
        $today = now()->format('Y-m-d');
        $summary = $this->report->summary($today, $today);

        $totalProducts = Product::query()->active()->count();
        $totalCategories = Category::query()->active()->count();
        $lowStockCount = Product::query()->active()->lowStock()->count();

        $recentSales = Sale::query()
            ->with('user')
            ->latest('created_at')
            ->limit(10)
            ->get();

        $chart = $this->report->dailySeries(14);

        // Aset gambar dari tabel media - langsung tercermin tanpa ubah kode.
        $homeBanners = Media::siteRole('banner_home')->get();
        $promoBanners = Media::siteRole('banner_promo')->get();
        $collectionPhotos = Media::siteRole('collection')->get();
        $latestProducts = Product::query()
            ->active()
            ->with('primaryPhoto')
            ->latest('created_at')
            ->limit(6)
            ->get();

        return view('admin.dashboard', [
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'lowStockCount' => $lowStockCount,
            'todaySummary' => $summary,
            'recentSales' => $recentSales,
            'lowStockProducts' => $this->report->lowStockProducts(5),
            'chart' => $chart,
            'homeBanners' => $homeBanners,
            'promoBanners' => $promoBanners,
            'collectionPhotos' => $collectionPhotos,
            'latestProducts' => $latestProducts,
        ]);
    }
}
