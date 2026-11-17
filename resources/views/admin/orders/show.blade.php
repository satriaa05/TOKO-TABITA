@extends('layouts.app')

@section('title', 'Detail Pesanan '.$sale->invoice_number)
@section('page_title', 'Detail Pesanan')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
        <div>
            <div class="fw-bold fs-5">{{ $sale->invoice_number }}</div>
            <span class="badge {{ $sale->status->badgeClass() }} fs-6">{{ $sale->status->label() }}</span>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header">Item Pesanan</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center">Harga</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sale->items as $item)
                                    <tr>
                                        <td>
                                            {{ $item->product_name }}
                                            @if ($item->size || $item->color)
                                                <span class="d-block small text-muted-2">
                                                    @if ($item->color){{ $item->color }}@endif
                                                    @if ($item->size) · {{ $item->size }}@endif
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center font-money">{{ rupiah($item->price) }}</td>
                                        <td class="text-center">{{ $item->qty }}</td>
                                        <td class="text-center font-money">{{ rupiah($item->subtotal) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between"><span class="text-muted-2">Subtotal</span><span class="font-money">{{ rupiah($sale->subtotal) }}</span></div>
                        @if ($sale->discount_total > 0)
                            <div class="d-flex justify-content-between"><span class="text-muted-2">Diskon</span><span class="font-money text-success">-{{ rupiah($sale->discount_total) }}</span></div>
                        @endif
                        <div class="d-flex justify-content-between"><span class="fw-bold">Total</span><span class="font-money fw-bold">{{ rupiah($sale->total) }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header">Data Pembeli</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-4">Nama</dt>
                        <dd class="col-8">{{ $sale->buyer_name ?? '-' }}</dd>

                        <dt class="col-4">WhatsApp</dt>
                        <dd class="col-8">{{ $sale->buyer_phone ?? '-' }}</dd>

                        <dt class="col-4">Alamat</dt>
                        <dd class="col-8">{{ $sale->buyer_address ?? '-' }}</dd>

                        <dt class="col-4">Catatan</dt>
                        <dd class="col-8">{{ $sale->buyer_note ?? '-' }}</dd>

                        <dt class="col-4">Dibuat</dt>
                        <dd class="col-8">{{ $sale->created_at->translatedFormat('d F Y H:i') }}</dd>
                    </dl>

                    @if ($sale->paymentProofs->isNotEmpty())
                        <h2 class="small fw-bold text-uppercase mt-3 mb-2" style="letter-spacing:.08em;">Bukti Pembayaran</h2>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($sale->paymentProofs as $proof)
                                <a href="{{ $proof->url() }}" target="_blank" rel="noopener" title="{{ $proof->original_name }}">
                                    <img src="{{ $proof->url() }}" alt="Bukti pembayaran {{ $loop->iteration }}"
                                         style="height: 90px; width: auto; object-fit: cover; border-radius: .4rem; border: 1px solid var(--tabita-border);">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="small text-muted-2 mt-3">Belum ada bukti pembayaran diunggah pembeli.</p>
                    @endif

                    @if ($sale->status->value === 'pending_payment')
                        @if ($sale->buyer_phone)
                            @php($waNumber = preg_replace('/\D+/', '', $sale->buyer_phone))
                            @php($waNumber = preg_replace('/^0/', '62', $waNumber))
                            <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener"
                               class="btn btn-light btn-sm w-100 mt-2">
                                <i class="bi bi-whatsapp" aria-hidden="true"></i> Buka WhatsApp Pembeli
                            </a>
                        @endif
                        <form method="POST" action="{{ route('admin.orders.complete', $sale) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                Konfirmasi Pembayaran Diterima
                            </button>
                        </form>
                    @elseif ($sale->status->value === 'completed')
                        <div class="alert alert-success mt-2 mb-0">Pesanan sudah dikonfirmasi selesai.</div>
                        <button type="button" class="btn btn-outline-danger btn-sm w-100 mt-2"
                                data-bs-toggle="modal" data-bs-target="#refundModal">
                            <i class="bi bi-x-circle"></i> Batalkan / Refund Transaksi
                        </button>

                        {{-- Modal Refund --}}
                        <div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.orders.refund', $sale) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title text-danger" id="refundModalLabel">
                                                <i class="bi bi-exclamation-triangle"></i> Batalkan Transaksi
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-2">
                                                Transaksi <strong>{{ $sale->invoice_number }}</strong>
                                                ({{ rupiah($sale->total) }}) akan dibatalkan.
                                            </p>
                                            <div class="alert alert-warning py-2 small mb-3">
                                                <i class="bi bi-info-circle"></i>
                                                Stok produk yang terjual akan <strong>dikembalikan otomatis</strong>.
                                                Aksi ini tidak dapat dibatalkan.
                                            </div>
                                            <div class="mb-3">
                                                <label for="reason" class="form-label">Alasan Pembatalan <span class="text-danger">*</span></label>
                                                <textarea name="reason" id="reason" class="form-control" rows="3"
                                                          placeholder="Contoh: Pembeli meminta pembatalan, pembayaran tidak diterima, dst."
                                                          required minlength="5" maxlength="255"></textarea>
                                                <div class="form-text">Minimal 5 karakter.</div>
                                                @error('reason')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-danger">
                                                <i class="bi bi-x-circle"></i> Ya, Batalkan Transaksi
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @elseif ($sale->status->value === 'refunded')
                        <div class="alert alert-danger mt-2 mb-0">
                            <i class="bi bi-x-circle"></i> Transaksi telah dibatalkan.
                            @if ($sale->refund_reason)
                                <br><span class="small">Alasan: {{ $sale->refund_reason }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
