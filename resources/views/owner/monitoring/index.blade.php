@extends('layouts.app')

@section('title', 'Monitoring Transaksi')
@section('page_title', 'Monitoring Transaksi')

@section('content')
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('owner.monitoring.index') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label for="periode" class="form-label small mb-1">Periode</label>
                    <select class="form-select form-select-sm" id="periode" name="periode" onchange="this.form.submit()">
                        @foreach ($periodOptions as $value => $label)
                            <option value="{{ $value }}" @selected($period === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="dari" class="form-label small mb-1">Dari</label>
                    <input type="date" class="form-control form-control-sm" id="dari" name="dari"
                           value="{{ $from->toDateString() }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="sampai" class="form-label small mb-1">Sampai</label>
                    <input type="date" class="form-control form-control-sm" id="sampai" name="sampai"
                           value="{{ $to->toDateString() }}">
                </div>
                <div class="col-6 col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    @php
        $cards = [
            ['label' => 'Total Transaksi', 'value' => $sales->count(), 'icon' => 'bi-receipt', 'tone' => 'primary'],
            ['label' => 'Pembayaran Berhasil', 'value' => rupiah($revenue), 'icon' => 'bi-check-circle', 'tone' => 'success'],
            ['label' => 'Pembayaran Pending', 'value' => $paymentCounts['pending'], 'icon' => 'bi-hourglass-split', 'tone' => 'warning'],
            ['label' => 'Pembayaran Gagal', 'value' => $paymentCounts['failed'] + $paymentCounts['expired'], 'icon' => 'bi-x-circle', 'tone' => 'danger'],
            ['label' => 'Pesanan Dikirim', 'value' => $fulfillmentCounts['shipped'], 'icon' => 'bi-truck', 'tone' => 'info'],
            ['label' => 'Pesanan Selesai', 'value' => $fulfillmentCounts['delivered'], 'icon' => 'bi-house-check', 'tone' => 'success'],
            ['label' => 'Total Ongkir', 'value' => rupiah($shippingTotal), 'icon' => 'bi-signpost', 'tone' => 'secondary'],
            ['label' => 'Estimasi Keuntungan', 'value' => rupiah($profit), 'icon' => 'bi-graph-up', 'tone' => 'primary'],
        ];
    @endphp

    <div class="row g-3 mb-3">
        @foreach ($cards as $card)
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small text-muted-2">{{ $card['label'] }}</div>
                                <div class="fw-bold fs-6 mt-1">{{ $card['value'] }}</div>
                            </div>
                            <i class="bi {{ $card['icon'] }} text-{{ $card['tone'] }} fs-5" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Status Pembayaran</div>
                <div class="card-body">
                    @foreach ($paymentCounts as $key => $count)
                        <div class="d-flex justify-content-between small py-1 border-bottom">
                            <span class="text-capitalize">{{ str_replace('_', ' ', $key) }}</span>
                            <strong>{{ $count }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Status Pengiriman</div>
                <div class="card-body">
                    @foreach ($fulfillmentCounts as $key => $count)
                        <div class="d-flex justify-content-between small py-1 border-bottom">
                            <span class="text-capitalize">{{ $key }}</span>
                            <strong>{{ $count }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Performa Metode Pembayaran</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Metode</th><th class="text-end">Transaksi</th><th class="text-end">Nominal</th></tr></thead>
                            <tbody>
                                @forelse ($byMethod as $row)
                                    <tr>
                                        <td>{{ $row->method }}</td>
                                        <td class="text-end">{{ $row->total }}</td>
                                        <td class="text-end font-money">{{ rupiah($row->nominal) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted-2 py-3">Belum ada transaksi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Gangguan Integrasi</div>
                <div class="card-body">
                    @foreach ($issues as $key => $count)
                        <div class="d-flex justify-content-between small py-1 border-bottom">
                            <span>{{ str_replace('_', ' ', $key) }}</span>
                            <span class="badge {{ $count > 0 ? 'text-bg-danger' : 'text-bg-success' }}">{{ $count }}</span>
                        </div>
                    @endforeach
                    <p class="small text-muted-2 mt-3 mb-0">
                        Angka di atas berasal dari log webhook dan data shipment. Periksa menu Pembayaran/Pengiriman Admin untuk detailnya.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">Webhook Terakhir</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Waktu</th><th>Provider</th><th>Status</th><th>Signature</th><th>Keterangan</th></tr></thead>
                            <tbody>
                                @forelse ($webhookLogs as $log)
                                    <tr>
                                        <td>{{ $log->created_at->translatedFormat('d M Y H:i') }}</td>
                                        <td>{{ $log->provider }}</td>
                                        <td>{{ $log->processing_status }}</td>
                                        <td>{!! $log->signature_valid ? '<span class="badge text-bg-success">Valid</span>' : '<span class="badge text-bg-danger">Invalid</span>' !!}</td>
                                        <td class="small">{{ $log->error_message ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted-2 py-3">Belum ada webhook masuk.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
