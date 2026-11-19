@extends('layouts.app')

@section('title', 'Dashboard Owner')
@section('page_title', 'Dashboard Owner')

@section('content')
    {{-- Filter periode --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('owner.dashboard') }}" class="d-flex flex-wrap align-items-center gap-2">
                <label for="period" class="small text-muted-2 mb-0">Periode:</label>
                <select name="period" id="period" class="form-select form-select-sm" style="width: auto;">
                    @foreach ($periodOptions as $value => $label)
                        <option value="{{ $value }}" @selected($period === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel"></i> Terapkan
                </button>
                <div class="small text-muted-2 ms-auto">
                    {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}
                    &mdash;
                    {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
                </div>
            </form>
        </div>
    </div>

    {{-- Ringkasan utama --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4 col-xl">
            <x-stat-card label="Total Omzet" :value="rupiah($summary['total_sales'])"
                         icon="bi-graph-up-arrow" color="success" />
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <x-stat-card label="Jumlah Transaksi" :value="$summary['transaction_count']"
                         icon="bi-receipt" color="primary" />
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <x-stat-card label="Rata-rata Transaksi" :value="rupiah($summary['average_transaction'])"
                         icon="bi-calculator" color="info" />
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <x-stat-card label="Estimasi Laba Kotor" :value="rupiah($summary['gross_profit'])"
                         icon="bi-piggy-bank" color="success"
                         sub="Belum termasuk biaya operasional" />
        </div>
        <div class="col-6 col-lg-4 col-xl">
            <x-stat-card label="Nilai Persediaan" :value="rupiah($inventoryValue)"
                         icon="bi-boxes" color="warning" />
        </div>
    </div>

    <div class="row g-3">
        {{-- Grafik penjualan --}}
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Grafik Penjualan
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="sales-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Produk terlaris --}}
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-trophy"></i> Produk Terlaris
                </div>
                <div class="card-body p-0">
                    @if ($topProducts->isEmpty())
                        <x-empty-state icon="bi-trophy" title="Belum ada data"
                                      message="Produk terlaris muncul setelah ada penjualan." />
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($topProducts as $product)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $product->product_name }}</div>
                                        <div class="small text-muted-2">{{ $product->qty_terjual }} item terjual</div>
                                    </div>
                                    <div class="text-end flex-shrink-0 ms-2">
                                        <div class="fw-semibold font-money">{{ rupiah($product->omzet) }}</div>
                                        <div class="small text-success">Laba {{ rupiah($product->laba) }}</div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stok menipis --}}
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-exclamation-triangle text-warning"></i> Ringkasan Stok Menipis
                </div>
                <div class="card-body p-0">
                    @if ($lowStockProducts->isEmpty())
                        <x-empty-state icon="bi-check-circle" title="Semua stok aman"
                                      message="Tidak ada produk di bawah stok minimum." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Kategori</th>
                                        <th class="text-end">Sisa Stok</th>
                                        <th class="text-end">Minimum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lowStockProducts as $product)
                                        <tr>
                                            <td class="fw-semibold">{{ $product->name }}</td>
                                            <td class="text-muted-2">{{ $product->category?->name ?? '-' }}</td>
                                            <td class="text-end">
                                                <span class="badge text-bg-warning">{{ $product->stock }}</span>
                                            </td>
                                            <td class="text-end text-muted-2">{{ $product->min_stock }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Transaksi terakhir --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Ringkasan Transaksi Terakhir
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
                                            <td class="fw-semibold">
                                                <a href="{{ route('invoice.show', $sale) }}" target="_blank" rel="noopener noreferrer"
                                                   class="text-decoration-none" title="Cetak invoice">
                                                    {{ $sale->invoice_number }} <i class="bi bi-printer ms-1" aria-hidden="true"></i>
                                                </a>
                                            </td>
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
