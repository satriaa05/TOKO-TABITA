@extends('layouts.app')

@section('title', 'Laporan Produk')
@section('page_title', 'Laporan Produk')

@section('header_actions')
    <a href="{{ route($base.'.reports.products', ['dari' => $from, 'sampai' => $to, 'export' => 'csv']) }}"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-filetype-csv"></i> Export CSV
    </a>
@endsection

@section('content')
    @include('reports.partials.period-filter')

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Nilai Persediaan" :value="rupiah($inventoryValue)"
                         icon="bi-boxes" color="warning" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Produk Terlaris" :value="$topProducts->count()"
                         icon="bi-trophy" color="success" sub="Termasuk produk terjual" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Produk Laris Jarang" :value="$slowProducts->count()"
                         icon="bi-arrow-down-circle" color="info" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Stok Menipis" :value="$lowStock->count()"
                         icon="bi-exclamation-triangle" color="danger" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-trophy"></i> Produk Terlaris</div>
                <div class="card-body p-0">
                    @if ($topProducts->isEmpty())
                        <x-empty-state icon="bi-trophy" title="Belum ada data"
                                      message="Tidak ada penjualan pada periode ini." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Produk</th>
                                        <th class="text-center">Terjual</th>
                                        <th class="text-end">Omzet</th>
                                        <th class="text-end">Laba</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topProducts as $i => $row)
                                        <tr>
                                            <td class="text-muted-2">{{ $i + 1 }}</td>
                                            <td class="fw-semibold">{{ $row->product_name }}</td>
                                            <td class="text-center">{{ $row->qty_terjual }}</td>
                                            <td class="text-end font-money">{{ rupiah($row->omzet) }}</td>
                                            <td class="text-end font-money text-success">{{ rupiah($row->laba) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-arrow-down-circle"></i> Produk Paling Laris Jarang</div>
                <div class="card-body p-0">
                    @if ($slowProducts->isEmpty())
                        <x-empty-state icon="bi-arrow-down-circle" title="Belum ada data"
                                      message="Tidak ada penjualan pada periode ini." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Kategori</th>
                                        <th class="text-center">Terjual</th>
                                        <th class="text-end">Stok Sekarang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($slowProducts as $row)
                                        <tr>
                                            <td class="fw-semibold">{{ $row['product']?->name ?? $row['product_name'] }}</td>
                                            <td class="text-muted-2">{{ $row['product']?->category?->name ?? '-' }}</td>
                                            <td class="text-center">{{ $row['qty'] }}</td>
                                            <td class="text-end font-money">{{ $row['product']?->stock ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-exclamation-triangle text-warning"></i> Produk Stok Menipis</div>
                <div class="card-body p-0">
                    @if ($lowStock->isEmpty())
                        <x-empty-state icon="bi-check-circle" title="Semua stok aman"
                                      message="Tidak ada produk di bawah stok minimum." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Kategori</th>
                                        <th class="text-end">Stok</th>
                                        <th class="text-end">Minimum</th>
                                        <th class="text-end">Nilai Persediaan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lowStock as $product)
                                        <tr>
                                            <td class="fw-semibold">{{ $product->name }}</td>
                                            <td class="text-muted-2">{{ $product->category?->name ?? '-' }}</td>
                                            <td class="text-end"><span class="badge text-bg-warning">{{ $product->stock }}</span></td>
                                            <td class="text-end text-muted-2">{{ $product->min_stock }}</td>
                                            <td class="text-end font-money">{{ rupiah($product->inventory_value) }}</td>
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
