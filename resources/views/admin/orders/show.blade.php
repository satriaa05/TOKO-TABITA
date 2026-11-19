@extends('layouts.app')

@section('title', 'Detail Pesanan '.$sale->invoice_number)
@section('page_title', 'Detail Pesanan')

@section('content')
    @php
        $shipment = $sale->shipments->first();
        $transaction = $sale->paymentTransactions->first();
        $courierName = $shipment
            ? strtoupper($shipment->courier)
            : ($sale->shipping_provider ?: 'Belum dipilih pembeli');
        $serviceName = $shipment?->service;
        $waNumber = $sale->buyer_phone
            ? preg_replace('/^0/', '62', preg_replace('/\D+/', '', $sale->buyer_phone))
            : null;
        $computedTotal = round((float) $sale->subtotal - (float) $sale->discount_total + (float) $sale->shipping_cost, 2);
    @endphp

    <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
        <div>
            <div class="fw-bold fs-5">{{ $sale->invoice_number }}</div>
            <div class="d-flex gap-1 flex-wrap mt-1">
                <span class="badge {{ $sale->status->badgeClass() }}">{{ $sale->status->label() }}</span>
                <span class="badge {{ $sale->payment_status->badgeClass() }}">Bayar: {{ $sale->payment_status->label() }}</span>
                <span class="badge {{ $sale->fulfillment_status->badgeClass() }}">Kirim: {{ $sale->fulfillment_status->label() }}</span>
            </div>
        </div>
        <a href="{{ route('invoice.show', $sale) }}" target="_blank" rel="noopener noreferrer"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer" aria-hidden="true"></i> Cetak Invoice
        </a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali
        </a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header">Produk dalam Pesanan Ini</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Produk</th>
                                    <th class="text-center">Harga</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sale->items as $item)
                                    <tr>
                                        <td style="width:64px;">
                                            @if ($item->photoUrl())
                                                <img src="{{ $item->photoUrl() }}"
                                                     alt="{{ $item->product_name }}"
                                                     style="height:48px;width:48px;object-fit:cover;border-radius:.4rem;border:1px solid var(--tabita-border);">
                                            @else
                                                <span class="d-inline-flex align-items-center justify-content-center bg-light rounded"
                                                      style="height:48px;width:48px;">
                                                    <i class="bi bi-image text-muted-2" aria-hidden="true"></i>
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $item->product_name }}</div>
                                            @if ($item->size || $item->color)
                                                <div class="small text-muted-2">
                                                    @if ($item->color)Warna: {{ $item->color }}@endif
                                                    @if ($item->size) · Ukuran: {{ $item->size }}@endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center font-money">{{ rupiah($item->price) }}</td>
                                        <td class="text-center">{{ $item->qty }}</td>
                                        <td class="text-end font-money">{{ rupiah($item->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted-2 py-3">Tidak ada item pada invoice ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between small"><span>Subtotal produk</span><span class="font-money">{{ rupiah($sale->subtotal) }}</span></div>
                        @if ($sale->discount_total > 0)
                            <div class="d-flex justify-content-between small"><span>Diskon / voucher</span><span class="font-money text-success">-{{ rupiah($sale->discount_total) }}</span></div>
                        @endif
                        <div class="d-flex justify-content-between small"><span>Ongkos kirim</span><span class="font-money">{{ rupiah($sale->shipping_cost) }}</span></div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total pembayaran</span>
                            <span class="font-money">{{ rupiah($computedTotal) }}</span>
                        </div>
                        <p class="small text-muted-2 mt-2 mb-0">
                            Total = subtotal − diskon + ongkir. Ongkir hanya dihitung sekali dan sudah termasuk dalam total pesanan.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Informasi Pengiriman</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4 col-md-3">Metode</dt>
                        <dd class="col-8 col-md-9">Pengiriman ke alamat pembeli</dd>

                        <dt class="col-4 col-md-3">Ekspedisi</dt>
                        <dd class="col-8 col-md-9 fw-semibold">{{ $courierName }}</dd>

                        <dt class="col-4 col-md-3">Layanan</dt>
                        <dd class="col-8 col-md-9">{{ $serviceName ?: 'Layanan tidak ditentukan pembeli' }}</dd>

                        <dt class="col-4 col-md-3">Ongkir</dt>
                        <dd class="col-8 col-md-9 font-money">{{ rupiah($sale->shipping_cost) }}</dd>

                        <dt class="col-4 col-md-3">Nomor Resi</dt>
                        <dd class="col-8 col-md-9">{{ $shipment?->tracking_number ?: 'Belum tersedia' }}</dd>

                        <dt class="col-4 col-md-3">Status Kirim</dt>
                        <dd class="col-8 col-md-9">
                            <span class="badge {{ $sale->fulfillment_status->badgeClass() }}">{{ $sale->fulfillment_status->label() }}</span>
                            @if ($shipment?->shipment_status)
                                <span class="badge {{ $shipment->shipment_status->badgeClass() }}">{{ $shipment->shipment_status->label() }}</span>
                            @endif
                        </dd>

                        <dt class="col-4 col-md-3">Lokasi Terakhir</dt>
                        <dd class="col-8 col-md-9">{{ $shipment?->last_scan_location ?: 'Belum ada data lokasi' }}</dd>

                        <dt class="col-4 col-md-3">Catatan Pembeli</dt>
                        <dd class="col-8 col-md-9">{{ $sale->buyer_note ?: 'Tidak ada catatan khusus' }}</dd>
                    </dl>
                    @if ($shipment)
                        <a href="{{ route('admin.shipments.show', $shipment) }}" class="btn btn-sm btn-outline-primary mt-3">
                            <i class="bi bi-truck" aria-hidden="true"></i> Kelola Pengiriman
                        </a>
                    @elseif ($sale->payment_status->value === 'paid')
                        <a href="{{ route('admin.shipments.create', $sale) }}" class="btn btn-sm btn-primary mt-3">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Input Pengiriman
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">Data Pembeli</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4">Nama</dt><dd class="col-8">{{ $sale->buyer_name ?: '-' }}</dd>
                        <dt class="col-4">WhatsApp</dt><dd class="col-8">{{ $sale->buyer_phone ?: '-' }}</dd>
                        <dt class="col-4">Alamat</dt><dd class="col-8">{{ $sale->buyer_address ?: '-' }}</dd>
                        <dt class="col-4">Dibuat</dt><dd class="col-8">{{ $sale->created_at->translatedFormat('d F Y H:i') }}</dd>
                    </dl>

                    @if ($waNumber)
                        <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener noreferrer"
                           class="btn btn-light btn-sm w-100 mt-3">
                            <i class="bi bi-whatsapp" aria-hidden="true"></i> Buka WhatsApp Pembeli
                        </a>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Pembayaran</div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5">Metode</dt>
                        <dd class="col-7">{{ $transaction?->channel?->name ?: 'Transfer Bank (manual)' }}</dd>
                        <dt class="col-5">Status</dt>
                        <dd class="col-7"><span class="badge {{ $sale->payment_status->badgeClass() }}">{{ $sale->payment_status->label() }}</span></dd>
                        <dt class="col-5">Referensi</dt>
                        <dd class="col-7">{{ $transaction?->provider_reference ?: ($transaction?->transaction_reference ?: '-') }}</dd>
                        <dt class="col-5">Nominal</dt>
                        <dd class="col-7 font-money">{{ rupiah($transaction?->amount ?? $sale->total) }}</dd>
                    </dl>

                    @if ($sale->paymentProofs->isNotEmpty())
                        <h2 class="small fw-bold text-uppercase mt-3 mb-2">Bukti Pembayaran</h2>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($sale->paymentProofs as $proof)
                                <a href="{{ $proof->url() }}" target="_blank" rel="noopener noreferrer" title="{{ $proof->original_name }}">
                                    <img src="{{ $proof->url() }}" alt="Bukti pembayaran {{ $loop->iteration }}"
                                         style="height:90px;width:auto;object-fit:cover;border-radius:.4rem;border:1px solid var(--tabita-border);">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="small text-muted-2 mt-3 mb-0">Belum ada bukti pembayaran diunggah pembeli.</p>
                    @endif

                    @if ($sale->status->value === 'pending_payment')
                        <button type="button" class="btn btn-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="bi bi-check-circle" aria-hidden="true"></i> Periksa & Setujui Pembayaran
                        </button>
                    @elseif ($sale->status->value === 'completed')
                        <div class="alert alert-success mt-3 mb-0">Pembayaran sudah dikonfirmasi.</div>
                        <button type="button" class="btn btn-outline-danger btn-sm w-100 mt-2"
                                data-bs-toggle="modal" data-bs-target="#refundModal">
                            <i class="bi bi-x-circle" aria-hidden="true"></i> Batalkan / Refund
                        </button>
                    @elseif ($sale->status->value === 'refunded')
                        <div class="alert alert-danger mt-3 mb-0">
                            Transaksi dibatalkan.
                            @if ($sale->refund_reason)<br><span class="small">Alasan: {{ $sale->refund_reason }}</span>@endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal persetujuan: tidak memakai confirm() bawaan browser --}}
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Periksa Pembayaran {{ $sale->invoice_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form method="POST" action="{{ route('admin.orders.complete', $sale) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-light border small mb-3">
                            Sebelum menyetujui, cocokkan bukti transfer dengan nominal di bawah ini.
                        </div>
                        <dl class="row mb-0 small">
                            <dt class="col-5">Nominal seharusnya</dt>
                            <dd class="col-7 font-money fw-bold">{{ rupiah($sale->total) }}</dd>
                            <dt class="col-5">Metode</dt>
                            <dd class="col-7">{{ $transaction?->channel?->name ?: 'Transfer manual' }}</dd>
                            <dt class="col-5">Bukti diunggah</dt>
                            <dd class="col-7">{{ $sale->paymentProofs->count() }} file</dd>
                        </dl>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Ya, Pembayaran Valid</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal refund --}}
    <div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.orders.refund', $sale) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title text-danger" id="refundModalLabel">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Batalkan Transaksi
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small">Invoice {{ $sale->invoice_number }} akan dibatalkan dan stok dikembalikan otomatis.</p>
                        <label for="reason" class="form-label small">Alasan pembatalan</label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" required minlength="5" maxlength="255"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Batalkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
