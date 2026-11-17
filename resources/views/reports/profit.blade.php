@extends('layouts.app')

@section('title', 'Laporan Laba Kotor')
@section('page_title', 'Laporan Laba Kotor')

@section('content')
    @include('reports.partials.period-filter')

    @include('reports.partials.comparison')

    <div class="alert alert-info d-flex gap-2" role="alert">
        <i class="bi bi-info-circle-fill"></i>
        <div class="small">
            Laba kotor dihitung dari <strong>harga pokok historis</strong> yang tersimpan di
            detail transaksi, bukan dari harga pokok produk saat ini. Jadi mengubah harga
            produk sekarang <strong>tidak mengubah</strong> hasil laporan ini.
            <br>
            Angka ini <strong>bukan laba bersih</strong> — biaya operasional, pajak, biaya
            pembayaran, dan retur belum dikurangkan.
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Pendapatan (setelah diskon)" :value="rupiah($comparison['current']['total_sales'])"
                         icon="bi-cash-stack" color="success" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Harga Pokok Terjual" :value="rupiah($comparison['current']['total_cogs'])"
                         icon="bi-box-seam" color="secondary" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Laba Kotor" :value="rupiah($comparison['current']['gross_profit'])"
                         icon="bi-piggy-bank" color="success" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Margin Kotor"
                         :value="($comparison['current']['total_sales'] > 0
                            ? round($comparison['current']['gross_profit'] / $comparison['current']['total_sales'] * 100, 1)
                            : 0).'%'"
                         icon="bi-percent" color="info" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-list-ol"></i> Kontribusi Laba per Produk</div>
                <div class="card-body p-0">
                    @if ($topProducts->isEmpty())
                        <x-empty-state icon="bi-piggy-bank" title="Belum ada data"
                                      message="Tidak ada penjualan pada periode ini." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-end">Omzet</th>
                                        <th class="text-end">HPP</th>
                                        <th class="text-end">Laba</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topProducts as $row)
                                        <tr>
                                            <td class="fw-semibold">{{ $row->product_name }}</td>
                                            <td class="text-end font-money">{{ rupiah($row->omzet) }}</td>
                                            <td class="text-end font-money text-muted-2">{{ rupiah($row->hpp) }}</td>
                                            <td class="text-end font-money fw-semibold text-success">{{ rupiah($row->laba) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-calendar3"></i> Laba Kotor Harian</div>
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
                                        <th class="text-end">Omzet</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($daily as $row)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($row->tanggal)->translatedFormat('d M Y') }}</td>
                                            <td class="text-center">{{ $row->jumlah_transaksi }}</td>
                                            <td class="text-end font-money fw-semibold">{{ rupiah($row->omzet) }}</td>
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
