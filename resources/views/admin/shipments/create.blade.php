@extends('layouts.app')

@section('title', 'Input Pengiriman '.$sale->invoice_number)
@section('page_title', 'Input Pengiriman')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    Pesanan <strong>{{ $sale->invoice_number }}</strong> — {{ $sale->buyer_name }}
                </div>
                <div class="card-body">
                    <p class="small text-muted-2">
                        Alamat: {{ $sale->buyer_address }}<br>
                        Ongkir tercatat: <strong>{{ rupiah($sale->shipping_cost) }}</strong>
                    </p>

                    <form method="POST" action="{{ route('admin.shipments.store', $sale) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="courier" class="form-label">Kurir <span class="text-danger">*</span></label>
                                <select class="form-select @error('courier') is-invalid @enderror" id="courier" name="courier" required>
                                    <option value="">Pilih kurir</option>
                                    @foreach ($couriers as $code => $info)
                                        <option value="{{ $code }}" @selected(old('courier') === $code)>{{ $info['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('courier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="service" class="form-label">Layanan</label>
                                <select class="form-select" id="service" name="service">
                                    <option value="">Pilih layanan</option>
                                    @foreach ($couriers as $code => $info)
                                        @foreach ($info['services'] as $serviceCode => $serviceLabel)
                                            <option value="{{ $serviceCode }}" data-courier="{{ $code }}" class="d-none">
                                                {{ $serviceLabel }}
                                            </option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="tracking_number" class="form-label">Nomor Resi</label>
                                <input type="text" class="form-control" id="tracking_number" name="tracking_number"
                                       maxlength="100" value="{{ old('tracking_number') }}"
                                       placeholder="Isi jika sudah ada; bisa menyusul">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="shipping_cost" class="form-label">Biaya Pengiriman</label>
                                <input type="text" class="form-control" id="shipping_cost_display"
                                       value="{{ rupiah($sale->shipping_cost) }}" readonly>
                                <input type="hidden" name="shipping_cost" value="{{ $sale->shipping_cost }}">
                                <div class="form-text">Ongkir diambil otomatis dari checkout & tidak bisa diubah Admin.</div>
                            </div>
                        </div>

                        <div class="alert alert-light border small mt-3 mb-0">
                            Jika API Biteship sudah dikonfigurasi, sistem akan otomatis membuat order pengiriman.
                            Jika belum, data tetap tersimpan dan bisa diisi manual oleh Admin.
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle" aria-hidden="true"></i> Simpan Pengiriman
                            </button>
                            <a href="{{ route('admin.shipments.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const courier = document.getElementById('courier');
            const service = document.getElementById('service');
            function filterService() {
                if (!courier || !service) return;
                service.querySelectorAll('option[data-courier]').forEach(function (option) {
                    const show = option.dataset.courier === courier.value;
                    option.classList.toggle('d-none', !show);
                    option.disabled = !show;
                });
                service.value = '';
            }
            if (courier) courier.addEventListener('change', filterService);
            filterService();
        })();
    </script>
@endpush
