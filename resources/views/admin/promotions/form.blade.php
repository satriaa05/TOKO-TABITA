@extends('layouts.app')

@section('title', ($mode === 'create' ? 'Tambah' : 'Ubah').' Promo')
@section('page_title', ($mode === 'create' ? 'Tambah Promo' : 'Ubah Promo'))

@section('header_actions')
    <a href="{{ route('admin.promotions.index') }}" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
@endsection

@section('content')
    <form method="POST"
          action="{{ $mode === 'create' ? route('admin.promotions.store') : route('admin.promotions.update', $promotion) }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-12 col-xl-8">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-percent"></i> Detail Promo</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="name" class="form-label">Nama Promo <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name"
                                       value="{{ old('name', $promotion->name) }}"
                                       maxlength="150" required autofocus>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="type" class="form-label">Jenis Diskon <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                    @foreach ($types as $type)
                                        <option value="{{ $type->value }}"
                                            @selected(old('type', $promotion->type) === $type->value)>
                                            {{ $type->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="value" class="form-label">Nilai Diskon <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control font-money @error('value') is-invalid @enderror"
                                           id="value" name="value"
                                           value="{{ old('value', $promotion->value) }}"
                                           min="0" step="1" required>
                                    <span class="input-group-text" id="satuanNilai">%</span>
                                    @error('value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="min_purchase" class="form-label">Minimum Belanja <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number"
                                           class="form-control font-money @error('min_purchase') is-invalid @enderror"
                                           id="min_purchase" name="min_purchase"
                                           value="{{ old('min_purchase', $promotion->min_purchase) }}"
                                           min="0" step="1" required>
                                    @error('min_purchase')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="start_date" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date"
                                       class="form-control @error('start_date') is-invalid @enderror"
                                       id="start_date" name="start_date"
                                       value="{{ old('start_date', $promotion->start_date?->format('Y-m-d')) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="end_date" class="form-label">Tanggal Berakhir <span class="text-danger">*</span></label>
                                <input type="date"
                                       class="form-control @error('end_date') is-invalid @enderror"
                                       id="end_date" name="end_date"
                                       value="{{ old('end_date', $promotion->end_date?->format('Y-m-d')) }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="description" name="description" rows="2" maxlength="500">{{ old('description', $promotion->description) }}</textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="is_active" name="is_active" value="1"
                                           @checked(old('is_active', $promotion->is_active))>
                                    <label class="form-check-label" for="is_active">Promo Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> Produk yang Memenuhi Syarat</span>
                        <div>
                            <button type="button" class="btn btn-sm btn-light" id="btnPilihSemua">Pilih Semua</button>
                            <button type="button" class="btn btn-sm btn-light" id="btnKosongkan">Kosongkan</button>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 420px; overflow-y: auto;">
                        @error('products')
                            <div class="alert alert-danger py-2">{{ $message }}</div>
                        @enderror

                        @forelse ($products as $product)
                            <div class="form-check mb-1">
                                <input class="form-check-input produk-promo" type="checkbox"
                                       name="products[]" value="{{ $product->id }}" id="promo_produk_{{ $product->id }}"
                                       @checked(in_array($product->id, old('products', $selectedProducts)))>
                                <label class="form-check-label" for="promo_produk_{{ $product->id }}">
                                    {{ $product->name }}
                                    <span class="text-muted-2 small">
                                        &mdash; {{ rupiah($product->sale_price) }} (stok {{ $product->stock }})
                                    </span>
                                </label>
                            </div>
                        @empty
                            <x-empty-state icon="bi-box" title="Belum ada produk"
                                          message="Tambahkan produk terlebih dahulu di menu Produk." />
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-info-circle"></i> Cara Kerja Promo</div>
                    <div class="card-body">
                        <ul class="small text-muted-2 ps-3 mb-0">
                            <li class="mb-2">Promo hanya berlaku pada periode yang ditentukan.</li>
                            <li class="mb-2">Diskon hanya berlaku bila total belanja (sebelum diskon) sudah mencapai
                                <strong>Minimum Belanja</strong>.</li>
                            <li class="mb-2">Diskon diberikan hanya pada produk yang dicentang.</li>
                            <li class="mb-2">Satu produk hanya bisa mendapat <strong>satu</strong> promo. Jika
                                beberapa promo berlaku, yang dipakai adalah yang diskonnya paling besar.</li>
                            <li class="mb-2">Harga akhir tidak boleh menjadi negatif.</li>
                            <li>Promo otomatis diterapkan sesuai aturan diskon yang diatur.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i>
                {{ $mode === 'create' ? 'Simpan Promo' : 'Simpan Perubahan' }}
            </button>
            <a href="{{ route('admin.promotions.index') }}" class="btn btn-light">Batal</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const typeSelect = document.getElementById('type');
            const satuan = document.getElementById('satuanNilai');
            const checkboxes = Array.from(document.querySelectorAll('.produk-promo'));

            function updateSatuan() {
                satuan.textContent = typeSelect.value === 'percent' ? '%' : 'Rp';
            }

            typeSelect.addEventListener('change', updateSatuan);
            updateSatuan();

            document.getElementById('btnPilihSemua')?.addEventListener('click', function () {
                checkboxes.forEach(c => { c.checked = true; });
            });

            document.getElementById('btnKosongkan')?.addEventListener('click', function () {
                checkboxes.forEach(c => { c.checked = false; });
            });
        })();
    </script>
@endpush
