@extends('layouts.store')

@section('title', 'Checkout')

@section('content')
    <section class="container py-4">
        <h1 class="store-section-title mb-3">Checkout</h1>

        @if ($errors->has('cart'))
            <div class="alert alert-warning">{{ $errors->first('cart') }}</div>
        @endif

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header">Data Pembeli &amp; Alamat</div>
                    <div class="card-body">
                        <p class="small text-muted-2">
                            Pesanan disimpan dengan status <strong>Menunggu Konfirmasi</strong>.
                            Setelah checkout, kirim konfirmasi ke admin lewat WhatsApp —
                            pembayaran dilakukan lewat transfer.
                        </p>

                        <form method="POST" action="{{ route('store.placeOrder') }}">
                            @csrf
                            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

                            <div class="mb-3">
                                <label for="buyer_name" class="form-label">Nama Penerima <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('buyer_name') is-invalid @enderror"
                                       id="buyer_name" name="buyer_name" value="{{ old('buyer_name', auth()->user()->name) }}"
                                       maxlength="150" required autofocus>
                                @error('buyer_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="buyer_phone" class="form-label">No WhatsApp <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control @error('buyer_phone') is-invalid @enderror"
                                       id="buyer_phone" name="buyer_phone" value="{{ old('buyer_phone') }}"
                                       maxlength="30" inputmode="numeric" placeholder="08xxxxxxxxxx" required>
                                @error('buyer_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Nomor yang aktif dipakai untuk konfirmasi pesanan.</div>
                            </div>

                            <div class="mb-3">
                                <label for="buyer_address" class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('buyer_address') is-invalid @enderror"
                                          id="buyer_address" name="buyer_address" rows="3" maxlength="1000"
                                          required>{{ old('buyer_address') }}</textarea>
                                @error('buyer_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="buyer_note" class="form-label">Catatan (opsional)</label>
                                <textarea class="form-control" id="buyer_note" name="buyer_note"
                                          rows="2" maxlength="255">{{ old('buyer_note') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label for="shipping_zone" class="form-label">Wilayah Pengiriman</label>
                                <select class="form-select" id="shipping_zone" name="shipping_zone">
                                    @foreach ($shippingZones as $zone => $label)
                                        <option value="{{ $zone }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Ongkir dihitung otomatis berdasarkan wilayah.</div>
                            </div>

                            <div class="mb-3">
                                <label for="shipping_provider" class="form-label">Jasa Pengiriman (opsional)</label>
                                <input type="text" class="form-control @error('shipping_provider') is-invalid @enderror"
                                       id="shipping_provider" name="shipping_provider"
                                       value="{{ old('shipping_provider') }}"
                                       maxlength="64" placeholder="mis. JNE, J&T, SiCepat, POS Indonesia">
                                @error('shipping_provider')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Nama jasa pengiriman yang dipakai (hanya untuk catatan).</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                                Buat Pesanan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-header">Ringkasan Pesanan</div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush mb-3">
                            @foreach ($items as $item)
                                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                    <span class="text-truncate">
                                        {{ $item['product']->name }}@if ($item['variant']), {{ $item['variant']->label() }}@endif x{{ $item['qty'] }}
                                    </span>
                                    <span class="font-money small">{{ rupiah($item['subtotal']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted-2">Subtotal</span>
                            <span class="font-money">{{ rupiah($subtotal) }}</span>
                        </div>
                        @if ($discount > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted-2">Diskon</span>
                                <span class="font-money text-success">-{{ rupiah($discount) }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted-2">Ongkir ({{ $shipping['is_free'] ? 'Gratis' : $shipping['zone'] }})</span>
                            <span class="font-money {{ $shipping['is_free'] ? 'text-success' : '' }}">{{ rupiah($shipping['cost']) }}</span>
                        </div>
                        @if (!$shipping['is_free'] && $shipping['threshold'] > 0)
                            <div class="text-center mb-2">
                                <span class="badge bg-info text-dark small">
                                    Gratis ongkir belanja minimal {{ rupiah($shipping['threshold']) }}
                                    <span class="text-muted-2">(belum {{ rupiah($shipping['shortfall']) }} lagi)</span>
                                </span>
                            </div>
                        @endif
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total</span>
                            <span class="font-money fw-bold fs-5">{{ rupiah($total) }}</span>
                        </div>
                        <p class="small text-muted-2 mt-3 mb-0">
                            Pembayaran: <strong>Transfer Bank</strong> (konfirmasi via WhatsApp).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
