@extends('layouts.app')

@section('title', 'Pengiriman')
@section('page_title', 'Pengiriman')

@section('content')
    <div class="card">
        <div class="card-header"><i class="bi bi-truck" aria-hidden="true"></i> Pesanan Siap Dikirim</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Pesanan</th>
                            <th>Penerima</th>
                            <th>Alamat</th>
                            <th>Kurir</th>
                            <th>Status Pengiriman</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            @php
                                $shipment = $sale->shipments->first();
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $sale->invoice_number }}</strong>
                                    <span class="d-block small text-muted-2">{{ $sale->created_at->translatedFormat('d M Y') }}</span>
                                </td>
                                <td>{{ $sale->buyer_name }}<span class="d-block small text-muted-2">{{ $sale->buyer_phone }}</span></td>
                                <td class="small" style="max-width:260px;">{{ \Illuminate\Support\Str::limit($sale->buyer_address, 90) }}</td>
                                <td>{{ $shipment?->courier ?: '-' }} {{ $shipment?->tracking_number ? '· '.$shipment->tracking_number : '' }}</td>
                                <td>
                                    @if ($shipment)
                                        <span class="badge {{ $shipment->shipment_status?->badgeClass() ?? 'text-bg-secondary' }}">
                                            {{ $shipment->shipment_status?->label() ?? '-' }}
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary">Belum dibuat</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($shipment)
                                        <a href="{{ route('admin.shipments.show', $shipment) }}" class="btn btn-sm btn-light">Detail</a>
                                    @else
                                        <a href="{{ route('admin.shipments.create', $sale) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Input Resi
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted-2">Belum ada pesanan lunas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($sales->hasPages())<div class="card-footer">{{ $sales->links() }}</div>@endif
    </div>
@endsection
