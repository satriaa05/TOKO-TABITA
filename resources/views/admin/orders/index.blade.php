@extends('layouts.app')

@section('title', 'Pesanan Online')
@section('page_title', 'Pesanan Online')

@section('content')
    <form method="GET" class="mb-3">
        <button type="submit" class="btn btn-light btn-sm">Muat ulang</button>
    </form>

    @if ($sales->isEmpty())
        <x-empty-state icon="bi-bag" title="Belum ada pesanan online"
                        message="Pesanan tamu dari halaman toko akan muncul di sini." />
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Pembeli</th>
                            <th class="text-center">Item</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr>
                                <td class="fw-semibold">{{ $sale->invoice_number }}</td>
                                <td>
                                    <div>{{ $sale->buyer_name }}</div>
                                    <div class="small text-muted-2">{{ $sale->buyer_phone }}</div>
                                </td>
                                <td class="text-center">{{ $sale->items_count }}</td>
                                <td class="text-center font-money">{{ rupiah($sale->total) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $sale->status->badgeClass() }}">{{ $sale->status->label() }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $sale) }}" class="btn btn-light btn-sm">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($sales->hasPages())
                <div class="card-footer">{{ $sales->links() }}</div>
            @endif
        </div>
    @endif
@endsection
