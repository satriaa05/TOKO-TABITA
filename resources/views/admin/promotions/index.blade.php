@extends('layouts.app')

@section('title', 'Manajemen Promo')
@section('page_title', 'Promo & Diskon')

@section('header_actions')
    <a href="{{ route('admin.promotions.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Tambah Promo
    </a>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.promotions.index') }}" class="d-flex gap-2">
                <div class="input-group input-group-sm" style="max-width: 380px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" name="q" value="{{ $keyword }}"
                           placeholder="Cari nama promo">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                @if ($keyword)
                    <a href="{{ route('admin.promotions.index') }}" class="btn btn-sm btn-light">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-percent"></i> Daftar Promo</span>
            <span class="badge text-bg-secondary">{{ $promotions->total() }} promo</span>
        </div>
        <div class="card-body p-0">
            @if ($promotions->isEmpty())
                <x-empty-state icon="bi-percent" title="Belum ada promo"
                              message="Buat promo untuk memberi diskon pada produk tertentu.">
                    <x-slot:action>
                        <a href="{{ route('admin.promotions.create') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> Tambah Promo
                        </a>
                    </x-slot:action>
                </x-empty-state>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Nama Promo</th>
                                <th>Jenis</th>
                                <th class="text-end">Nilai</th>
                                <th>Periode</th>
                                <th class="text-center">Min. Belanja</th>
                                <th class="text-center">Produk</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($promotions as $promotion)
                                @php $status = $promotion->currentStatus(); @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $promotion->name }}</div>
                                        @if ($promotion->description)
                                            <div class="small text-muted-2">{{ $promotion->description }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $promotion->type->label() }}</td>
                                    <td class="text-end font-money fw-semibold">
                                        {{ $promotion->type->value === 'percent' ? $promotion->value.'%' : rupiah($promotion->value) }}
                                    </td>
                                    <td class="text-muted-2 small">
                                        {{ $promotion->start_date->format('d/m/Y') }} &ndash;
                                        {{ $promotion->end_date->format('d/m/Y') }}
                                    </td>
                                    <td class="text-center font-money">{{ rupiah($promotion->min_purchase) }}</td>
                                    <td class="text-center">
                                        <span class="badge text-bg-light border">{{ $promotion->products_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $status->badgeClass() }}">{{ $status->label() }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.promotions.edit', $promotion) }}"
                                               class="btn btn-light" title="Ubah promo">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="{{ route('admin.promotions.toggle', $promotion) }}"
                                                  class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-light"
                                                        title="{{ $promotion->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                                        data-confirm="{{ $promotion->is_active ? 'Nonaktifkan' : 'Aktifkan' }} promo &quot;{{ $promotion->name }}&quot;?">
                                                    <i class="bi bi-{{ $promotion->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($promotions->hasPages())
                    <div class="card-footer">{{ $promotions->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
