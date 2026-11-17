@extends('layouts.app')

@section('title', 'Laporan Penjualan')
@section('page_title', 'Laporan Penjualan')

@section('header_actions')
    <a href="{{ route($base.'.reports.sales', ['periode' => $period, 'dari' => $from, 'sampai' => $to, 'export' => 'csv']) }}"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-filetype-csv"></i> Export CSV
    </a>
@endsection

@section('content')
    @include('reports.partials.period-filter')

    @include('reports.partials.comparison')

    {{-- Ringkasan periode berjalan --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Total Penjualan" :value="rupiah($comparison['current']['total_sales'])"
                         icon="bi-cash-stack" color="success" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Jumlah Transaksi" :value="$comparison['current']['transaction_count']"
                         icon="bi-receipt" color="primary" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Total Diskon" :value="rupiah($comparison['current']['total_discount'])"
                         icon="bi-tag" color="warning" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Transaksi Dibatalkan" :value="$comparison['current']['refunded_count']"
                         icon="bi-arrow-counterclockwise" color="danger" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-graph-up"></i> Grafik Penjualan</div>
                <div class="card-body">
                    <div style="height: 280px;">
                        <canvas id="sales-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-credit-card"></i> Omzet per Metode Pembayaran</div>
                <div class="card-body p-0">
                    @if ($byPayment->isEmpty())
                        <x-empty-state icon="bi-credit-card" title="Belum ada data"
                                      message="Belum ada pembayaran pada periode ini." />
                    @else
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Metode</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($byPayment as $row)
                                    <tr>
                                        <td>{{ $row->method?->label() ?? $row->method }}</td>
                                        <td class="text-center">{{ $row->jumlah }}</td>
                                        <td class="text-end font-money fw-semibold">{{ rupiah($row->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-calendar3"></i> Rincian Harian</div>
                <div class="card-body p-0">
                    @if ($daily->isEmpty())
                        <x-empty-state icon="bi-calendar3" title="Belum ada data"
                                      message="Tidak ada penjualan pada periode ini." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th class="text-center">Transaksi</th>
                                        <th class="text-end">Diskon</th>
                                        <th class="text-end">Omzet</th>
                                        <th class="text-end">Rata-rata</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($daily as $row)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($row->tanggal)->translatedFormat('l, d M Y') }}</td>
                                            <td class="text-center">{{ $row->jumlah_transaksi }}</td>
                                            <td class="text-end font-money">{{ rupiah($row->diskon) }}</td>
                                            <td class="text-end font-money fw-semibold">{{ rupiah($row->omzet) }}</td>
                                            <td class="text-end font-money">
                                                {{ rupiah($row->jumlah_transaksi > 0 ? $row->omzet / $row->jumlah_transaksi : 0) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-center">{{ $comparison['current']['transaction_count'] }}</th>
                                        <th class="text-end font-money">{{ rupiah($comparison['current']['total_discount']) }}</th>
                                        <th class="text-end font-money">{{ rupiah($comparison['current']['total_sales']) }}</th>
                                        <th class="text-end font-money">{{ rupiah($comparison['current']['average_transaction']) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/chart.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('sales-chart');
            if (canvas && window.renderSalesChart) {
                window.renderSalesChart(canvas, @json($chart['labels']), @json($chart['values']));
            }
        });
    </script>
@endpush
