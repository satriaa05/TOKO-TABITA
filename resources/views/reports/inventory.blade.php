@extends('layouts.app')

@section('title', 'Laporan Inventori')
@section('page_title', 'Laporan Inventori')

@section('content')
    @include('reports.partials.period-filter')

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Nilai Persediaan" :value="rupiah($inventoryValue)"
                         icon="bi-boxes" color="warning" sub="Harga pokok × stok" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Stok Masuk (Periode)" :value="number_format($movements['masuk'], 0, ',', '.')"
                         icon="bi-box-arrow-in-down" color="success" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Stok Keluar (Periode)" :value="number_format($movements['keluar'], 0, ',', '.')"
                         icon="bi-box-arrow-up" color="danger" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Produk Stok Menipis" :value="$lowStock->count()"
                         icon="bi-exclamation-triangle" color="danger" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-diagram-3"></i> Nilai Persediaan per Kategori</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-center">Produk</th>
                                <th class="text-end">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($byCategory as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="text-center">{{ $row['count'] }}</td>
                                    <td class="text-end font-money fw-semibold">{{ rupiah($row['value']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-box-seam"></i> Rincian Persediaan</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th class="text-end">Stok</th>
                                    <th class="text-end">H. Pokok</th>
                                    <th class="text-end">Nilai</th>
                                    <th class="text-end">Potensi Omzet</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $product->name }}
                                            @if ($product->isLowStock())
                                                <span class="badge text-bg-warning ms-1">Menipis</span>
                                            @endif
                                        </td>
                                        <td class="text-muted-2">{{ $product->category?->name ?? '-' }}</td>
                                        <td class="text-end font-money">{{ $product->stock }}</td>
                                        <td class="text-end font-money">{{ rupiah($product->cost_price) }}</td>
                                        <td class="text-end font-money fw-semibold">{{ rupiah($product->inventory_value) }}</td>
                                        <td class="text-end font-money text-muted-2">{{ rupiah($product->potential_revenue) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
