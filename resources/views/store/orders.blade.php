@extends('layouts.store')

@section('title', 'Riwayat Pesanan')

@section('content')
    <section class="container py-4">
        <h1 class="store-section-title mb-3">Riwayat Pesanan</h1>

        @if ($sales->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-bag fs-1 text-muted-2" aria-hidden="true"></i>
                <p class="text-muted-2 mt-2">Belum ada pesanan.</p>
                <a href="{{ route('store.catalog') }}" class="btn btn-primary">Mulai Belanja</a>
            </div>
        @else
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Tanggal</th>
                                <th class="text-center">Item</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Bukti</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sales as $sale)
                                <tr>
                                    <td class="fw-semibold">{{ $sale->invoice_number }}</td>
                                    <td>{{ $sale->created_at->translatedFormat('d M Y H:i') }}</td>
                                    <td class="text-center">{{ $sale->items->count() }}</td>
                                    <td class="text-center font-money">{{ rupiah($sale->total) }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $sale->status->badgeClass() }}">{{ $sale->status->label() }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($sale->paymentProofs->isNotEmpty())
                                            <span class="badge text-bg-success"><i class="bi bi-check-lg" aria-hidden="true"></i> Terkirim</span>
                                        @else
                                            <span class="badge text-bg-light border text-muted-2">Belum</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('store.orders.show', $sale) }}" class="btn btn-light btn-sm">Detail</a>
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
    </section>
@endsection
