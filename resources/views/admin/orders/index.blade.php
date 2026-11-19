@extends('layouts.app')

@section('title', 'Pesanan Online')
@section('page_title', 'Pesanan Online')

@section('content')
    @php
        $cards = [
            ['label' => 'Total Pesanan', 'value' => $stats['total'], 'icon' => 'bi-receipt', 'tone' => 'primary'],
            ['label' => 'Menunggu Konfirmasi', 'value' => $stats['pending'], 'icon' => 'bi-hourglass-split', 'tone' => 'warning'],
            ['label' => 'Diproses', 'value' => $stats['processing'], 'icon' => 'bi-gear', 'tone' => 'info'],
            ['label' => 'Dikirim', 'value' => $stats['shipped'], 'icon' => 'bi-truck', 'tone' => 'secondary'],
            ['label' => 'Selesai', 'value' => $stats['completed'], 'icon' => 'bi-check-circle', 'tone' => 'success'],
            ['label' => 'Dibatalkan', 'value' => $stats['cancelled'], 'icon' => 'bi-x-circle', 'tone' => 'danger'],
        ];
    @endphp

    <div class="row g-2 mb-3">
        @foreach ($cards as $card)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card h-100">
                    <div class="card-body py-2">
                        <div class="small text-muted-2">{{ $card['label'] }}</div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <strong class="fs-5">{{ $card['value'] }}</strong>
                            <i class="bi {{ $card['icon'] }} text-{{ $card['tone'] }} fs-5" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <form method="GET" class="mb-3">
        <button type="submit" class="btn btn-light btn-sm">Muat ulang</button>
    </form>

    @if ($sales->isEmpty())
        <x-empty-state icon="bi-bag" title="Belum ada pesanan online"
                        message="Pesanan dari halaman toko akan muncul di sini." />
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Pembeli</th>
                            <th class="text-center">Jenis<br>Produk</th>
                            <th class="text-center">Total<br>Qty</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Ongkir</th>
                            <th class="text-end">Total</th>
                            <th>Ekspedisi</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            @php
                                $shipment = $sale->shipments->first();
                                $courier = $shipment
                                    ? strtoupper($shipment->courier).($shipment->service ? ' · '.$shipment->service : '')
                                    : ($sale->shipping_provider ?: 'Belum dipilih');
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $sale->invoice_number }}</td>
                                <td>
                                    <div>{{ $sale->buyer_name }}</div>
                                    <div class="small text-muted-2">{{ $sale->buyer_phone }}</div>
                                </td>
                                <td class="text-center">{{ $sale->items_count }}</td>
                                <td class="text-center">{{ (int) $sale->items_sum_qty }}</td>
                                <td class="text-end font-money">{{ rupiah($sale->items_sum_subtotal ?? $sale->subtotal) }}</td>
                                <td class="text-end font-money">{{ rupiah($sale->shipping_cost) }}</td>
                                <td class="text-end font-money fw-bold">{{ rupiah($sale->total) }}</td>
                                <td class="small">{{ $courier }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $sale->status->badgeClass() }}">{{ $sale->status->label() }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $sale) }}" class="btn btn-light btn-sm">
                                        Detail
                                    </a>
                                    <a href="{{ route('invoice.show', $sale) }}" target="_blank" rel="noopener noreferrer"
                                       class="btn btn-outline-primary btn-sm" title="Cetak invoice">
                                        <i class="bi bi-printer" aria-hidden="true"></i>
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
