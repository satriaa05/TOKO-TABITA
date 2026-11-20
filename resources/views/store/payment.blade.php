@extends('layouts.store')

@section('title', 'Pembayaran '.$sale->invoice_number)

@section('content')
    <section class="container py-4">
        <p class="small text-muted-2 mb-1"><a href="{{ route('store.orders.index') }}" class="text-decoration-none">Riwayat Pesanan</a></p>
        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
            <h1 class="store-section-title mb-0" style="font-size: 1.4rem;">Pembayaran {{ $sale->invoice_number }}</h1>
            <span class="badge {{ $sale->payment_status->badgeClass() }} fs-6">{{ $sale->payment_status->label() }}</span>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3 align-items-start">
            <div class="col-12 col-lg-7">
                <div class="card st-pay-card">
                    <div class="card-header">
                        <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                        Instruksi Pembayaran — {{ $transaction?->channel?->name ?? 'Transfer Bank' }}
                    </div>
                    <div class="card-body">
                        @if ($sale->payment_status->value === 'paid')
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                                Pembayaran Anda sudah dikonfirmasi. Pesanan sedang menunggu diproses Admin.
                            </div>
                        @elseif ($gatewayError)
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                {{ $gatewayError }}
                            </div>
                            <p class="small text-muted-2 mb-0">
                                Transaksi Anda tetap tersimpan. Hubungi Admin jika pembayaran sudah dilakukan.
                            </p>
                        @elseif ($transaction && $transaction->channel->isAutomatic())
                            <div class="text-center mb-3">
                                @if ($transaction->qr_code_url)
                                    <img src="{{ $transaction->qr_code_url }}" alt="QRIS {{ $sale->invoice_number }}"
                                         class="st-qr-image" loading="lazy">
                                @elseif ($transaction->payment_url)
                                    <a href="{{ $transaction->payment_url }}" target="_blank" rel="noopener noreferrer"
                                       class="btn btn-primary">Lanjutkan ke Pembayaran</a>
                                @endif
                            </div>
                            <div class="st-pay-amount text-center mb-3">
                                <span class="d-block small text-muted-2">Nominal pembayaran</span>
                                <span class="font-money fw-bold">{{ rupiah($sale->total) }}</span>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-12 col-md-6"><span class="text-muted-2">Nomor pesanan</span><br><strong>{{ $sale->invoice_number }}</strong></div>
                                <div class="col-12 col-md-6"><span class="text-muted-2">Referensi provider</span><br><strong>{{ $transaction->provider_reference ?: '-' }}</strong></div>
                                <div class="col-12"><span class="text-muted-2">Batas waktu pembayaran</span><br>
                                    <strong>{{ $transaction->expired_at?->translatedFormat('d M Y H:i') ?? 'Menunggu data provider' }}</strong>
                                </div>
                            </div>
                            @if ($transaction->isExpired())
                                <form method="POST" action="{{ route('store.payment.refresh', $sale) }}" class="mt-3">
                                    @csrf
                                    <button type="submit" class="st-btn st-btn-outline w-100 justify-content-center">
                                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Buat QR Pembayaran Baru
                                    </button>
                                </form>
                            @endif
                            <div class="alert alert-light border small mt-3 mb-0">
                                Buka aplikasi pembayaran Anda, scan QR di atas, lalu konfirmasi nominal yang tertera.
                               Status akan berubah otomatis setelah notifikasi dari payment gateway diterima dan diverifikasi.
                            </div>
                        @else
                            @php
                                $bank = config('payments.bank_transfer', []);
                                $channel = $transaction?->channel;
                            @endphp
                            <div class="st-pay-amount text-center mb-4">
                                <span class="d-block small text-muted-2">Total yang harus dibayar</span>
                                <span class="font-money fw-bold">{{ rupiah($sale->total) }}</span>
                            </div>

                            <div class="row g-2 small">
                                <div class="col-12 col-md-6"><span class="text-muted-2">Nomor pesanan</span><br><strong>{{ $sale->invoice_number }}</strong></div>
                                <div class="col-12 col-md-6"><span class="text-muted-2">Metode</span><br><strong>{{ $transaction?->channel?->name ?? 'Transfer Bank' }}</strong></div>
                                <div class="col-12 col-md-4"><span class="text-muted-2">Bank</span><br><strong>{{ $channel?->bank_name ?: ($bank['bank_name'] ?? '-') }}</strong></div>
                                <div class="col-12 col-md-4"><span class="text-muted-2">Pemilik rekening</span><br><strong>{{ $channel?->account_holder ?: ($bank['account_holder'] ?? '-') }}</strong></div>
                                <div class="col-12 col-md-4"><span class="text-muted-2">Nomor rekening</span><br><strong class="font-money">{{ $channel?->account_number ?: ($bank['account_number'] ?? '-') }}</strong></div>
                            </div>

                            <div class="alert alert-light border small mt-3 mb-0">
                                <i class="bi bi-info-circle" aria-hidden="true"></i>
                                Transfer sesuai nominal sampai angka terakhir agar verifikasi lebih cepat.
                                Unggah bukti transfer di bawah ini. Status pesanan tidak berubah otomatis
                                hanya karena Anda kembali ke halaman ini — Admin akan memverifikasi bukti Anda.
                                @if ($transaction && $transaction->expired_at)
                                    <br>Batas waktu pembayaran: <strong>{{ $transaction->expired_at->translatedFormat('d M Y H:i') }}</strong>.
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                @if ($sale->payment_status->value !== 'paid' && (! $transaction || ! $transaction->channel->isAutomatic()))
                    <div class="card st-pay-card mt-3">
                        <div class="card-header"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Kirim Bukti Pembayaran</div>
                        <div class="card-body">
                            @if ($sale->paymentProofs->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    @foreach ($sale->paymentProofs as $proof)
                                        <a href="{{ $proof->url() }}" target="_blank" rel="noopener noreferrer" title="{{ $proof->original_name }}">
                                            <img src="{{ $proof->url() }}" alt="Bukti pembayaran {{ $loop->iteration }}"
                                                 class="st-proof-thumb">
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            <form method="POST" action="{{ route('store.orders.proof', $sale) }}" enctype="multipart/form-data">
                                @csrf
                                <label for="proof" class="form-label small fw-bold">File bukti transfer</label>
                                <input type="file" class="form-control @error('proof') is-invalid @enderror"
                                       id="proof" name="proof" accept=".jpg,.jpeg,.png,.webp" required>
                                @error('proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">JPG, JPEG, PNG, atau WebP, maksimal 2 MB.</div>
                                <button type="submit" class="st-btn mt-2">
                                    <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Kirim Bukti
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-12 col-lg-5">
                <div class="card st-pay-card">
                    <div class="card-header"><i class="bi bi-list-check" aria-hidden="true"></i> Status Pesanan</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between small mb-2">
                            <span class="text-muted-2">Pembayaran</span>
                            <span class="badge {{ $sale->payment_status->badgeClass() }}">{{ $sale->payment_status->label() }}</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-3">
                            <span class="text-muted-2">Pengiriman</span>
                            <span class="badge {{ $sale->fulfillment_status->badgeClass() }}">{{ $sale->fulfillment_status->label() }}</span>
                        </div>

                        <form method="POST" action="{{ route('store.payment.status', $sale) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Cek Status Pembayaran
                            </button>
                        </form>
                        <a href="{{ route('store.orders.show', $sale) }}" class="btn btn-light w-100 mt-2">
                            Lihat Detail Pesanan
                        </a>
                    </div>
                </div>

                <div class="card st-pay-card mt-3">
                    <div class="card-header"><i class="bi bi-receipt" aria-hidden="true"></i> Ringkasan</div>
                    <div class="card-body">
                        @foreach ($sale->items as $item)
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span>{{ $item->product_name }} x{{ $item->qty }}</span>
                                <span class="font-money">{{ rupiah($item->total) }}</span>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-between mt-3">
                            <span class="fw-bold">Total</span>
                            <span class="font-money fw-bold">{{ rupiah($sale->total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        .st-pay-card { border: 1px solid var(--line); border-radius: .9rem; }
        .st-pay-card .card-header { background: #fffdfc; border-bottom: 1px solid var(--line); font-weight: 600; font-size: .9rem; }
        .st-pay-amount span.font-money { font-size: 1.9rem; color: var(--rose-dark); }
        .st-qr-image { max-width: 220px; width: 100%; height: auto; border: 1px solid var(--line); border-radius: .6rem; background: #fff; padding: .5rem; }
        .st-proof-thumb { height: 84px; width: auto; object-fit: cover; border-radius: .4rem; border: 1px solid var(--line); }
    </style>
@endpush
