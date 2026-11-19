@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page_title', 'Dashboard Admin')

@section('content')
    {{-- Banner homepage dari tabel media; tampil otomatis bila admin mengunggah. --}}
    @if ($homeBanners->isNotEmpty())
        <div class="card mb-4 overflow-hidden">
            @if ($homeBanners->count() === 1)
                <img src="{{ $homeBanners->first()->url() }}" class="d-block w-100"
                     style="max-height: 240px; object-fit: cover;" alt="Banner homepage">
            @else
                <div id="bannerHomeCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        @foreach ($homeBanners as $banner)
                            <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                <img src="{{ $banner->url() }}" class="d-block w-100"
                                     style="max-height: 240px; object-fit: cover;" alt="Banner homepage">
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="carousel-control-prev" data-bs-target="#bannerHomeCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon bg-dark rounded-circle p-2"></span>
                        <span class="visually-hidden">Sebelumnya</span>
                    </button>
                    <button type="button" class="carousel-control-next" data-bs-target="#bannerHomeCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon bg-dark rounded-circle p-2"></span>
                        <span class="visually-hidden">Berikutnya</span>
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- Strip banner promosi --}}
    @if ($promoBanners->isNotEmpty())
        <div class="row g-3 mb-4">
            @foreach ($promoBanners as $banner)
                <div class="col-12 col-md-6 col-xl-4">
                    <img src="{{ $banner->url() }}" class="rounded border d-block w-100"
                         style="max-height: 130px; object-fit: cover;" alt="Banner promosi">
                </div>
            @endforeach
        </div>
    @endif

    {{-- Ringkasan kartu --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4 col-xl">
            <x-stat-card label="Total Produk" :value="$totalProducts" icon="bi-box-seam" color="primary"
                         :sub="'Aktif: '.$totalProducts" />
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <x-stat-card label="Total Kategori" :value="$totalCategories" icon="bi-tags" color="info" />
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <x-stat-card label="Transaksi Hari Ini" :value="$todaySummary['transaction_count']"
                         icon="bi-receipt" color="success" />
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <x-stat-card label="Penjualan Hari Ini" :value="rupiah($todaySummary['total_sales'])"
                         icon="bi-cash-stack" color="success" />
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <x-stat-card label="Stok Menipis" :value="$lowStockCount" icon="bi-exclamation-triangle"
                         color="warning" sub="Produk di bawah stok minimum" />
        </div>
    </div>

    <div class="row g-3">
        {{-- Grafik penjualan --}}
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-graph-up-arrow"></i> Grafik Penjualan (14 Hari Terakhir)</span>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="sales-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Produk stok menipis --}}
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-exclamation-triangle text-warning"></i> Produk Stok Menipis
                </div>
                <div class="card-body p-0">
                    @if ($lowStockProducts->isEmpty())
                        <x-empty-state icon="bi-check-circle" title="Semua stok aman"
                                      message="Tidak ada produk di bawah stok minimum." />
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($lowStockProducts as $product)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $product->name }}</div>
                                        <div class="small text-muted-2">{{ $product->barcode }}</div>
                                    </div>
                                    <div class="text-end flex-shrink-0 ms-2">
                                        <span class="badge text-bg-warning">Sisa {{ $product->stock }}</span>
                                        <div class="small text-muted-2">Min. {{ $product->min_stock }}</div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Koleksi terbaru: foto koleksi admin + produk terbaru dengan fotonya --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-collection"></i> Koleksi Terbaru
                </div>
                <div class="card-body">
                    @if ($collectionPhotos->isEmpty() && $latestProducts->isEmpty())
                        <x-empty-state icon="bi-collection" title="Belum ada koleksi"
                                      message="Unggah foto koleksi di menu Tampilan Toko, atau produk terbaru dengan foto akan tampil di sini." />
                    @else
                        <div class="row g-3">
                            @foreach ($collectionPhotos as $photo)
                                <div class="col-6 col-md-3 col-xl-2">
                                    <img src="{{ $photo->url() }}"
                                         class="rounded border d-block w-100"
                                         style="height: 120px; object-fit: cover;"
                                         alt="Foto koleksi terbaru">
                                </div>
                            @endforeach
                            @foreach ($latestProducts as $product)
                                <div class="col-6 col-md-3 col-xl-2">
                                    @if ($product->displayPhoto())
                                        <img src="{{ $product->displayPhoto()->url() }}"
                                             class="rounded border d-block w-100"
                                             style="height: 120px; object-fit: cover;"
                                             alt="Foto {{ $product->name }}">
                                    @else
                                        <div class="rounded border d-flex align-items-center justify-content-center text-muted-2 d-flex"
                                             style="height: 120px;">
                                            <i class="bi bi-box"></i>
                                        </div>
                                    @endif
                                    <div class="small fw-semibold text-truncate mt-1">{{ $product->name }}</div>
                                    <div class="small font-money text-muted-2">{{ rupiah($product->sale_price) }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Transaksi terbaru --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Transaksi Terbaru
                </div>
                <div class="card-body p-0">
                    @if ($recentSales->isEmpty())
                        <x-empty-state icon="bi-receipt" title="Belum ada transaksi"
                                      message="Transaksi penjualan akan muncul di sini." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>No. Invoice</th>
                                        <th>Waktu</th>
                                        <th>Status</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentSales as $sale)
                                        <tr>
                                            <td class="fw-semibold">{{ $sale->invoice_number }}</td>
                                            <td class="text-muted-2">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <span class="badge {{ $sale->status->badgeClass() }}">
                                                    {{ $sale->status->label() }}
                                                </span>
                                            </td>
                                            <td class="text-end fw-semibold font-money">{{ rupiah($sale->total) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/chart.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('sales-chart');
            if (canvas && window.renderSalesChart) {
                window.renderSalesChart(
                    canvas,
                    @json($chart['labels']),
                    @json($chart['values'])
                );
            }
        });
    </script>
@endpush
