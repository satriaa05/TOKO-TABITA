@extends('layouts.app')

@section('title', 'Pembayaran')
@section('page_title', 'Pembayaran')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-credit-card" aria-hidden="true"></i> Daftar Transaksi</span>
            <span class="small text-muted-2">Total: {{ $sales->total() }} pesanan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Metode</th>
                            <th class="text-end">Nominal</th>
                            <th>Status Pembayaran</th>
                            <th>Status Pengiriman</th>
                            <th>Bukti</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td>
                                    <strong>{{ $sale->invoice_number }}</strong>
                                    <span class="d-block small text-muted-2">{{ $sale->created_at->translatedFormat('d M Y H:i') }}</span>
                                </td>
                                <td>{{ $sale->buyer_name ?? $sale->user?->name ?? '-' }}</td>
                                <td>{{ $sale->paymentTransactions->first()?->channel?->name ?? 'Transfer Bank' }}</td>
                                <td class="text-end font-money">{{ rupiah($sale->total) }}</td>
                                <td><span class="badge {{ $sale->payment_status->badgeClass() }}">{{ $sale->payment_status->label() }}</span></td>
                                <td><span class="badge {{ $sale->fulfillment_status->badgeClass() }}">{{ $sale->fulfillment_status->label() }}</span></td>
                                <td>{{ $sale->paymentProofs->count() }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.payments.show', $sale) }}" class="btn btn-sm btn-light">
                                        <i class="bi bi-eye" aria-hidden="true"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted-2">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($sales->hasPages())
            <div class="card-footer">{{ $sales->links() }}</div>
        @endif
    </div>
@endsection
