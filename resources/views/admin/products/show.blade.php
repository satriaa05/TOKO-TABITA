@extends('layouts.app')

@section('title', 'Detail Produk')
@section('page_title', $product->name)

@section('header_actions')
    <a href="{{ route('admin.products.index') }}" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil"></i> Ubah
    </a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-box-seam"></i> Detail Produk
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted-2" style="width: 38%;">Barcode</td>
                                <td><code>{{ $product->barcode }}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted-2">Nama Produk</td>
                                <td class="fw-semibold">{{ $product->name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted-2">Kategori</td>
                                <td>{{ $product->category?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted-2">Satuan</td>
                                <td>{{ $product->unit }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted-2">Harga Pokok</td>
                                <td class="font-money">{{ rupiah($product->cost_price) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted-2">Harga Jual</td>
                                <td class="font-money fw-semibold">{{ rupiah($product->sale_price) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted-2">Margin</td>
                                <td class="font-money text-success fw-semibold">{{ rupiah($product->margin()) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted-2">Stok Tersedia</td>
                                <td>
                                    @if ($product->isLowStock())
                                        <span class="badge text-bg-warning">{{ $product->stock }}</span>
                                        <span class="small text-muted-2">(minimum {{ $product->min_stock }})</span>
                                    @else
                                        <span class="font-money fw-semibold">{{ $product->stock }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted-2">Stok Minimum</td>
                                <td>{{ $product->min_stock }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted-2">Status</td>
                                <td>
                                    <span class="badge {{ $product->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted-2">Dibuat</td>
                                <td>{{ $product->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> 20 Mutasi Stok Terakhir
                </div>
                <div class="card-body p-0">
                    @if ($movements->isEmpty())
                        <x-empty-state icon="bi-arrow-down-up" title="Belum ada mutasi"
                                      message="Riwayat perubahan stok produk ini akan tampil di sini." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Jenis</th>
                                        <th class="text-end">Perubahan</th>
                                        <th class="text-end">Stok</th>
                                        <th>Oleh</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($movements as $movement)
                                        <tr>
                                            <td class="text-muted-2 small">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <span class="badge {{ $movement->qty > 0 ? 'text-bg-success' : 'text-bg-danger' }}">
                                                    {{ $movement->type->label() }}
                                                </span>
                                            </td>
                                            <td class="text-end font-money fw-semibold">
                                                {{ $movement->qty > 0 ? '+' : '' }}{{ $movement->qty }}
                                            </td>
                                            <td class="text-end font-money small">
                                                {{ $movement->stock_before }} &rarr; {{ $movement->stock_after }}
                                            </td>
                                            <td class="small">{{ $movement->user?->name ?? '-' }}</td>
                                            <td class="small text-muted-2">{{ $movement->note ?? '-' }}</td>
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
