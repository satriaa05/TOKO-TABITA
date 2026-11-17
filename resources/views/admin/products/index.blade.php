@extends('layouts.app')

@section('title', 'Manajemen Produk')
@section('page_title', 'Manajemen Produk')

@section('header_actions')
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Tambah Produk
    </a>
@endsection

@section('content')
    {{-- Filter & pencarian --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.products.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="q" class="form-label small mb-1">Cari Produk</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" id="q" name="q"
                               value="{{ $keyword }}" placeholder="Nama atau barcode produk">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <label for="kategori" class="form-label small mb-1">Kategori</label>
                    <select class="form-select form-select-sm" id="kategori" name="kategori">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label for="status" class="form-label small mb-1">Status</label>
                    <select class="form-select form-select-sm" id="status" name="status">
                        <option value="">Semua</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                        <option value="low" @selected($status === 'low')>Stok Menipis</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                        <i class="bi bi-funnel"></i> Terapkan
                    </button>
                    @if ($keyword || $categoryId || $status)
                        <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-light">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel produk --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-box-seam"></i> Daftar Produk</span>
            <span class="badge text-bg-secondary">{{ $products->total() }} produk</span>
        </div>
        <div class="card-body p-0">
            @if ($products->isEmpty())
                <x-empty-state icon="bi-box" title="Produk tidak ditemukan"
                              message="Coba ubah kata kunci atau filter pencarian.">
                    <x-slot:action>
                        <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> Tambah Produk
                        </a>
                    </x-slot:action>
                </x-empty-state>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                @php
                                    $sortLink = function (string $key, string $label) use ($sort, $direction) {
                                        $nextDir = ($sort === $key && $direction === 'asc') ? 'desc' : 'asc';
                                        $icon = $sort === $key
                                            ? ($direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down')
                                            : 'bi-arrow-down-up';

                                        return '<a href="'.request()->fullUrlWithQuery(['sort' => $key, 'dir' => $nextDir]).'" class="text-decoration-none text-reset">'
                                            .$label.' <i class="bi '.$icon.' small"></i></a>';
                                    };
                                @endphp

                                <th>{!! $sortLink('nama', 'Produk') !!}</th>
                                <th>{!! $sortLink('barcode', 'Barcode') !!}</th>
                                <th>Kategori</th>
                                <th class="text-end">{!! $sortLink('harga_pokok', 'H. Pokok') !!}</th>
                                <th class="text-end">{!! $sortLink('harga_jual', 'H. Jual') !!}</th>
                                <th class="text-end">{!! $sortLink('stok', 'Stok') !!}</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $product->name }}</div>
                                        <div class="small text-muted-2">per {{ $product->unit }}</div>
                                    </td>
                                    <td><code class="small">{{ $product->barcode }}</code></td>
                                    <td class="text-muted-2">{{ $product->category?->name ?? '-' }}</td>
                                    <td class="text-end font-money text-nowrap">{{ rupiah($product->cost_price) }}</td>
                                    <td class="text-end font-money fw-semibold text-nowrap">{{ rupiah($product->sale_price) }}</td>
                                    <td class="text-end text-nowrap">
                                        @if ($product->isLowStock())
                                            <span class="badge text-bg-warning">{{ $product->stock }}</span>
                                            <div class="small text-muted-2">min. {{ $product->min_stock }}</div>
                                        @else
                                            <span class="font-money">{{ $product->stock }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $product->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.products.show', $product) }}"
                                               class="btn btn-light" title="Detail produk">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.products.edit', $product) }}"
                                               class="btn btn-light" title="Ubah produk">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="{{ route('admin.products.toggle', $product) }}"
                                                  class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="btn btn-light"
                                                        title="{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                                        data-confirm="{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }} produk &quot;{{ $product->name }}&quot;?">
                                                    <i class="bi bi-{{ $product->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($products->hasPages())
                    <div class="card-footer">
                        {{ $products->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
