@extends('layouts.store')

@section('title', 'Checkout')

@section('content')
    <section class="container py-4">
        <div class="mb-4">
            <h1 class="store-section-title mb-2">Checkout</h1>
            <div class="st-checkout-steps" aria-label="Tahap checkout">
                <span class="st-step active"><span>1</span> Alamat</span>
                <span class="st-step-line"></span>
                <span class="st-step active"><span>2</span> Pengiriman</span>
                <span class="st-step-line"></span>
                <span class="st-step active"><span>3</span> Pembayaran</span>
            </div>
        </div>

        @if ($errors->has('cart'))
            <div class="alert alert-warning">{{ $errors->first('cart') }}</div>
        @endif

        @if ($errors->any() && ! $errors->has('cart'))
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('store.placeOrder') }}" id="checkout-form">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

            <div class="row g-3 align-items-start">
                <div class="col-12 col-lg-7">
                    {{-- Alamat --}}
                    <div class="card st-checkout-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-geo-alt" aria-hidden="true"></i> Alamat Pengiriman</span>
                            <a href="{{ route('store.addresses.create') }}" class="small text-decoration-none">+ Alamat baru</a>
                        </div>
                        <div class="card-body">
                            @forelse ($addresses as $address)
                                <label class="st-address-option">
                                    <input type="radio" name="address_id" value="{{ $address->id }}"
                                           data-postal="{{ $address->postal_code }}"
                                           class="form-check-input me-2" @checked(old('address_id') == $address->id || (! old('address_id') && $address->is_default))>
                                    <span>
                                        <span class="fw-semibold">{{ $address->label }} — {{ $address->recipient_name }}</span>
                                        <span class="d-block small text-muted-2">{{ $address->phone }}</span>
                                        <span class="d-block small">{{ $address->fullAddress() }}</span>
                                    </span>
                                    @if ($address->is_default)<span class="badge text-bg-success">Utama</span>@endif
                                </label>
                            @empty
                                <p class="small text-muted-2">Belum ada alamat tersimpan. Isi alamat di bawah.</p>
                            @endforelse

                            <label class="st-address-option">
                                <input type="radio" name="address_id" value="" id="address_new"
                                       class="form-check-input me-2"
                                       @checked(old('address_id') === '' || ($addresses->isEmpty() && ! old('address_id')))>
                                <span class="fw-semibold">Gunakan alamat baru</span>
                            </label>

                            <div id="new-address-fields" class="row g-3 mt-1">
                                <div class="col-12 col-md-6">
                                    <label for="buyer_name" class="form-label">Nama Penerima</label>
                                    <input type="text" class="form-control @error('buyer_name') is-invalid @enderror"
                                           id="buyer_name" name="buyer_name" maxlength="150"
                                           value="{{ old('buyer_name', auth()->user()->name) }}">
                                    @error('buyer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="buyer_phone" class="form-label">No. WhatsApp</label>
                                    <input type="tel" class="form-control @error('buyer_phone') is-invalid @enderror"
                                           id="buyer_phone" name="buyer_phone" maxlength="30" inputmode="numeric"
                                           value="{{ old('buyer_phone') }}" placeholder="08xxxxxxxxxx">
                                    @error('buyer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="buyer_address" class="form-label">Alamat Lengkap</label>
                                    <textarea class="form-control @error('buyer_address') is-invalid @enderror"
                                              id="buyer_address" name="buyer_address" rows="3" maxlength="1000">{{ old('buyer_address') }}</textarea>
                                    @error('buyer_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="city" class="form-label">Kota</label>
                                    <input type="text" class="form-control" id="city" name="city" maxlength="100"
                                           value="{{ old('city') }}" placeholder="Semarang">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="province" class="form-label">Provinsi</label>
                                    <input type="text" class="form-control" id="province" name="province" maxlength="100"
                                           value="{{ old('province') }}" placeholder="Jawa Tengah">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="postal_code" class="form-label">Kode Pos</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" maxlength="15"
                                           value="{{ old('postal_code') }}">
                                </div>
                                <div class="col-12">
                                    <label for="buyer_note" class="form-label">Catatan (opsional)</label>
                                    <textarea class="form-control" id="buyer_note" name="buyer_note" rows="2" maxlength="255">{{ old('buyer_note') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pengiriman --}}
                    <div class="card st-checkout-card">
                        <div class="card-header"><i class="bi bi-truck" aria-hidden="true"></i> Pengiriman</div>
                        <div class="card-body row g-3">
                            <div class="col-12 col-md-6">
                                <label for="courier" class="form-label">Jasa Pengiriman</label>
                                <select class="form-select" id="courier" name="courier">
                                    <option value="">Pilih kurir</option>
                                    @foreach ($couriers as $code => $info)
                                        <option value="{{ $code }}" @selected(old('courier') === $code)>{{ $info['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="service" class="form-label">Jenis Layanan</label>
                                <select class="form-select" id="service" name="service">
                                    <option value="">Pilih layanan</option>
                                    @foreach ($couriers as $code => $info)
                                        @foreach ($info['services'] as $serviceCode => $serviceLabel)
                                            <option value="{{ $serviceCode }}" data-courier="{{ $code }}"
                                                    @selected(old('service') === $serviceCode) class="d-none">
                                                {{ $serviceLabel }}
                                            </option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="shipping_zone" class="form-label">Wilayah Tujuan</label>
                                <select class="form-select" id="shipping_zone" name="shipping_zone">
                                    @foreach ($shippingZones as $zone => $label)
                                        <option value="{{ $zone }}" @selected(old('shipping_zone', 'jawa_tengah') === $zone)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-light border small mb-0" id="shipping-note">
                                    Ongkir dihitung otomatis di server, <strong>maksimal {{ rupiah(store_profile()->shippingMaxCost()) }}</strong>
                                    dan <strong>gratis</strong> untuk belanja minimal
                                    {{ rupiah(store_profile()->shippingFreeThreshold()) }}.
                                    Estimasi pengiriman mengikuti wilayah tujuan; layanan instan memakai estimasi jam.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pembayaran --}}
                    <div class="card st-checkout-card">
                        <div class="card-header"><i class="bi bi-credit-card" aria-hidden="true"></i> Metode Pembayaran</div>
                        <div class="card-body">
                            @forelse ($paymentChannels as $channel)
                                <label class="st-payment-option">
                                    <input type="radio" name="payment_method" value="{{ $channel->code }}"
                                           class="form-check-input me-2" @checked(old('payment_method', $paymentChannels->first()->code) === $channel->code)>
                                    @if ($channel->logo_path)
                                        <img src="{{ $channel->logoUrl() }}" alt="{{ $channel->name }}" class="st-pay-logo">
                                    @endif
                                    <span class="flex-grow-1">
                                        <span class="fw-semibold d-block">{{ $channel->name }}</span>
                                        <span class="small text-muted-2">{{ $channel->description }}</span>
                                    </span>
                                    <span class="badge {{ $channel->isAutomatic() ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $channel->isAutomatic() ? 'Otomatis' : 'Verifikasi Admin' }}
                                    </span>
                                </label>
                            @empty
                                <div class="alert alert-warning mb-0">
                                    Belum ada metode pembayaran aktif. Hubungi Admin toko.
                                </div>
                            @endforelse

                            @if ($upcomingChannels->isNotEmpty())
                                <div class="mt-3 pt-3 border-top">
                                    <div class="small fw-semibold text-muted-2 mb-2">
                                        <i class="bi bi-clock-history" aria-hidden="true"></i> Segera Hadir
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($upcomingChannels as $channel)
                                            <span class="border rounded px-2 py-1 small d-inline-flex align-items-center gap-2"
                                                  title="{{ $channel->status_note }}">
                                                @if ($channel->logo_path)
                                                    <img src="{{ $channel->logoUrl() }}" alt="" style="height:16px;width:auto;">
                                                @endif
                                                <span>{{ $channel->name }}</span>
                                                <span class="badge text-bg-secondary">Segera Hadir</span>
                                            </span>
                                        @endforeach
                                    </div>
                                    <div class="form-text mt-2">
                                        Metode di atas akan aktif otomatis setelah kredensial payment gateway diisi Admin.
                                    </div>
                                </div>
                            @endif

                            @error('payment_method')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Ringkasan --}}
                <div class="col-12 col-lg-5">
                    <div class="card st-checkout-summary">
                        <div class="card-header"><i class="bi bi-receipt" aria-hidden="true"></i> Ringkasan Pesanan</div>
                        <div class="card-body">
                            <div class="st-summary-items">
                                @foreach ($items as $item)
                                    @php
                                        $checkoutPhoto = $item['product']->displayPhoto($item['color'] ?? null);
                                        $rowPromo = $promo['items'][$loop->index] ?? null;
                                        $lineDiscount = (float) ($rowPromo['discount'] ?? 0);
                                    @endphp
                                    <div class="d-flex justify-content-between gap-2 py-2 border-bottom">
                                        <span class="small d-flex gap-2">
                                            @if ($checkoutPhoto)
                                                <img src="{{ $checkoutPhoto->url() }}" alt=""
                                                     style="width:42px;height:42px;object-fit:cover;border-radius:.35rem;">
                                            @endif
                                            <span>
                                                {{ $item['product']->name }}
                                            @if ($item['variant'])<span class="d-block text-muted-2">{{ $item['variant']->label() }}</span>@endif
                                            <span class="text-muted-2">
                                                {{ $item['qty'] }} x
                                                @if ($lineDiscount > 0)
                                                    <span class="st-price-old">{{ rupiah($item['price']) }}</span>
                                                    <span class="text-success">{{ rupiah($item['price'] - $lineDiscount / max(1, (int) $item['qty'])) }}</span>
                                                @else
                                                    {{ rupiah($item['price']) }}
                                                @endif
                                            </span>
                                            </span>
                                        </span>
                                        <span class="font-money small text-nowrap">
                                            @if ($lineDiscount > 0)
                                                <span class="st-price-old">{{ rupiah($item['subtotal']) }}</span>
                                                <span class="text-success">{{ rupiah($item['subtotal'] - $lineDiscount) }}</span>
                                            @else
                                                {{ rupiah($item['subtotal']) }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3 small">
                                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Subtotal</span><span class="font-money">{{ rupiah($subtotal) }}</span></div>
                                @if ($discount > 0)
                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Diskon promo</span><span class="font-money text-success">-{{ rupiah($discount) }}</span></div>
                                @endif
                                @if ($voucherDiscount > 0)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted-2">Voucher {{ $voucher->code }}</span>
                                        <span class="font-money text-success">-{{ rupiah($voucherDiscount) }}</span>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Ongkir</span><span class="font-money" id="summary-shipping">{{ rupiah($shipping['cost']) }}</span></div>
                                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Estimasi tiba</span><span id="shipping-eta-display">{{ $shipping['eta'] ?? store_profile()->shippingEta() }}</span></div>
                            </div>

                            <div class="card bg-light border-0 mt-3">
                                <div class="card-body py-2">
                                    <label for="voucher_code_input" class="form-label small fw-bold mb-1">Punya voucher?</label>
                                    @if ($voucher)
                                        <div class="d-flex justify-content-between align-items-center small mb-2">
                                            <span><i class="bi bi-ticket-perforated" aria-hidden="true"></i> {{ $voucher->code }} terpasang</span>
                                            <a href="{{ route('store.voucher.remove') }}" class="text-decoration-none small">Hapus</a>
                                        </div>
                                    @endif
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="voucher_code_input" class="form-control" placeholder="Kode voucher" autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary" id="voucher-apply">Gunakan</button>
                                    </div>
                                    <div id="voucher-feedback" class="small mt-2"></div>
                                </div>
                            </div>

                            <hr class="my-3">
                            <div class="d-flex justify-content-between align-items-end">
                                <span class="fw-bold">Total Pembayaran</span>
                                <span class="font-money fw-bold fs-4" id="summary-total">{{ rupiah($displayTotal) }}</span>
                            </div>
                            <span id="checkout-base" class="d-none">{{ round((float) $subtotal - (float) $discount - $voucherDiscount, 2) }}</span>
                            <p class="small text-muted-2 mt-2 mb-3">
                                Seluruh subtotal, diskon, dan ongkir dihitung ulang di server saat pesanan dibuat.
                            </p>
                            <button type="submit" class="st-btn w-100">
                                <i class="bi bi-lock" aria-hidden="true"></i> Buat Pesanan & Lanjut Bayar
                            </button>
                            <p class="small text-muted-2 text-center mt-2 mb-0">
                                Status awal pesanan: <strong>Menunggu Pembayaran</strong>
                            </p>
                            <p class="small text-muted-2 text-center mb-0">
                                Waktu saat ini: <strong id="checkout-clock" class="font-money">{{ now()->translatedFormat('d M Y H:i:s') }} WIB</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection

@push('styles')
    <style>
        .st-checkout-steps { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
        .st-step { display: inline-flex; align-items: center; gap: .4rem; font-size: .78rem; font-weight: 600; color: var(--muted); }
        .st-step span { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 999px; background: var(--rose); color: #fff; font-size: .72rem; }
        .st-step-line { width: 28px; height: 1px; background: var(--line); }
        .st-checkout-card, .st-checkout-summary { border: 1px solid var(--line); border-radius: .9rem; }
        .st-checkout-card .card-header, .st-checkout-summary .card-header { background: #fffdfc; border-bottom: 1px solid var(--line); font-weight: 600; font-size: .9rem; }
        .st-address-option, .st-payment-option { display: flex; align-items: flex-start; gap: .7rem; padding: .8rem; border: 1px solid var(--line); border-radius: .65rem; margin-bottom: .6rem; cursor: pointer; background: #fff; transition: border-color .15s ease, background .15s ease; }
        .st-address-option:hover, .st-payment-option:hover { border-color: var(--rose); background: #fffaf8; }
        .st-pay-logo { height: 22px; width: auto; max-width: 56px; object-fit: contain; }
        .st-summary-items { max-height: 260px; overflow-y: auto; }
        @media (max-width: 575.98px) { .st-step { font-size: .7rem; } .st-step-line { width: 14px; } .st-pay-logo { height: 18px; } }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const newAddress = document.getElementById('address_new');
            const fields = document.getElementById('new-address-fields');

            function toggleAddress() {
                if (!fields) return;
                const useNew = !newAddress || newAddress.checked || !document.querySelector('input[name="address_id"]:checked');
                fields.style.display = useNew ? 'flex' : 'none';
                fields.querySelectorAll('input, textarea').forEach(function (el) {
                    el.disabled = !useNew;
                });
            }

            document.querySelectorAll('input[name="address_id"]').forEach(function (radio) {
                radio.addEventListener('change', toggleAddress);
            });
            toggleAddress();

            const courier = document.getElementById('courier');
            const service = document.getElementById('service');
            const zone = document.getElementById('shipping_zone');
            const etaDisplay = document.getElementById('shipping-eta-display');
            const etaMap = @json(store_profile()->shippingEtaZones());
            const instantEta = @json(store_profile()->shippingInstantEta());

            function updateEta() {
                if (!etaDisplay) return;
                const selected = service && service.value ? service.value : '';
                if (selected.toLowerCase().indexOf('instant') !== -1) {
                    etaDisplay.textContent = instantEta;
                    return;
                }
                const key = zone && etaMap[zone.value] ? zone.value : 'default';
                etaDisplay.textContent = etaMap[key] || etaMap.default || '5-7 hari';
            }

            if (zone) zone.addEventListener('change', updateEta);
            if (service) service.addEventListener('change', updateEta);
            updateEta();

            function filterService() {
                if (!courier || !service) return;
                let hasVisible = false;
                service.querySelectorAll('option').forEach(function (option) {
                    if (!option.dataset.courier) return;
                    const show = option.dataset.courier === courier.value;
                    option.classList.toggle('d-none', !show);
                    option.disabled = !show;
                    if (show) hasVisible = true;
                });
                if (!hasVisible) service.value = '';
            }

            if (courier) courier.addEventListener('change', filterService);
            filterService();

            const apply = document.getElementById('voucher-apply');
            if (apply) {
                apply.addEventListener('click', function () {
                    const input = document.getElementById('voucher_code_input');
                    const feedback = document.getElementById('voucher-feedback');
                    feedback.textContent = 'Memeriksa voucher...';

                    fetch('{{ route('store.voucher.validate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ code: input.value })
                    })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                    .then(function (res) {
                        feedback.textContent = res.data.message || ('Voucher ' + res.data.code + ' dipakai: -' + res.data.discount_label);
                        feedback.className = 'small mt-2 ' + (res.ok ? 'text-success' : 'text-danger');
                        if (res.ok) window.location.reload();
                    })
                    .catch(function () {
                        feedback.textContent = 'Gagal memeriksa voucher. Coba lagi.';
                        feedback.className = 'small mt-2 text-danger';
                    });
                });
            }
        })();

        (function () {
            const el = document.getElementById('checkout-clock');
            if (!el) return;
            function tick() {
                const now = new Date();
                const pad = function (n) { return String(n).padStart(2, '0'); };
                el.textContent = pad(now.getDate())+' '+
                    ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][now.getMonth()]+' '+
                    now.getFullYear()+' '+pad(now.getHours())+':'+pad(now.getMinutes())+':'+pad(now.getSeconds())+' WIB';
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>
@endpush
