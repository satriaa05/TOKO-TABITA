@extends('layouts.store')

@section('title', 'Pesanan '.$sale->invoice_number)

@section('content')
    <section class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <p class="small text-muted-2 mb-1"><a href="{{ route('store.orders.index') }}" class="text-decoration-none">Riwayat Pesanan</a></p>
                <h1 class="store-section-title" style="font-size: 1.35rem;">{{ $sale->invoice_number }}</h1>
            </div>
            <span class="badge {{ $sale->status->badgeClass() }} fs-6">{{ $sale->status->label() }}</span>
            @if ($sale->payment_status?->value === 'paid')
                <a href="{{ route('store.orders.tracking', $sale) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-geo-alt" aria-hidden="true"></i> Lacak Pengiriman
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

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
                                                <div class="d-flex align-items-center gap-2">
                                                    @if ($item->photoUrl())
                                                        <img src="{{ $item->photoUrl() }}" alt=""
                                                             style="width:44px;height:44px;object-fit:cover;border-radius:.35rem;">
                                                    @endif
                                                    <span>
                                                        {{ $item->product_name }}
                                                        @if ($item->size || $item->color)
                                                            <span class="d-block small text-muted-2">
                                                                @if ($item->color)Warna {{ $item->color }}@endif
                                                                @if ($item->size)Ukuran {{ $item->size }}@endif
                                                            </span>
                                                        @endif
                                                    </span>
                                                </div>
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
                            <div class="d-flex justify-content-between"><span class="text-muted-2">Subtotal produk</span><span class="font-money">{{ rupiah($sale->subtotal) }}</span></div>
                            @if ($sale->discount_total > 0)
                                <div class="d-flex justify-content-between"><span class="text-muted-2">Diskon</span><span class="font-money text-success">-{{ rupiah($sale->discount_total) }}</span></div>
                            @endif
                            <div class="d-flex justify-content-between"><span class="text-muted-2">Ongkos kirim</span><span class="font-money">{{ rupiah($sale->shipping_cost) }}</span></div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between"><span class="fw-bold">Total pembayaran</span><span class="font-money fw-bold">{{ rupiah($sale->total) }}</span></div>
                            <p class="small text-muted-2 mt-2 mb-0">
                                Total = Subtotal − Diskon + Ongkir. Ongkir hanya dihitung sekali dan sudah termasuk dalam total pesanan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card h-100">
                    <div class="card-header">Pembayaran &amp; Bukti</div>
                    <div class="card-body">
                        @php $buyerTransaction = $sale->paymentTransactions->first(); @endphp
                        <p class="small text-muted-2">
                            Metode pembayaran: <strong>{{ $buyerTransaction?->channel?->name ?: 'Transfer Bank' }}</strong>.
                            @if ($buyerTransaction?->provider_reference)
                                <br>Referensi: {{ $buyerTransaction->provider_reference }}
                            @endif
                            @if ($sale->status->value === 'pending_payment')
                                Unggah bukti agar Admin dapat memverifikasi.
                            @endif
                        </p>

                        @if ($sale->paymentProofs->isNotEmpty())
                            <h2 class="small fw-bold text-uppercase mb-2" style="letter-spacing:.08em;">Bukti Terkirim</h2>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach ($sale->paymentProofs as $proof)
                                    <a href="{{ $proof->url() }}" target="_blank" rel="noopener">
                                        <img src="{{ $proof->url() }}" alt="Bukti pembayaran {{ $loop->iteration }}"
                                             style="height: 96px; width: auto; object-fit: cover; border-radius: .4rem; border: 1px solid var(--line);">
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if ($sale->status->value === 'pending_payment')
                            <form method="POST" action="{{ route('store.orders.proof', $sale) }}" enctype="multipart/form-data">
                                @csrf
                                <label for="proof" class="form-label">Unggah Bukti Pembayaran <span class="text-danger">*</span></label>
                                <input type="file" class="form-control @error('proof') is-invalid @enderror"
                                       id="proof" name="proof" accept=".jpg,.jpeg,.png,.webp,.png" required>
                                @error('proof')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">JPG, JPEG, PNG atau WebP · maks 2 MB.</div>
                                <button type="submit" class="st-btn mt-2">
                                    <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Kirim Bukti
                                </button>
                            </form>
                        @else
                            <div class="alert alert-success mb-0">Pembayaran sudah dikonfirmasi admin.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
