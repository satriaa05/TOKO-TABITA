@extends('layouts.app')

@section('title', ($mode === 'create' ? 'Tambah' : 'Ubah').' Produk')
@section('page_title', ($mode === 'create' ? 'Tambah Produk' : 'Ubah Produk'))

@section('header_actions')
    <a href="{{ route('admin.products.index') }}" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Informasi Produk
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="{{ $mode === 'create' ? route('admin.products.store') : route('admin.products.update', $product) }}">
                        @csrf
                        @if ($mode === 'edit')
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="barcode" class="form-label">Barcode <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('barcode') is-invalid @enderror"
                                       id="barcode"
                                       name="barcode"
                                       value="{{ old('barcode', $product->barcode) }}"
                                       maxlength="50"
                                       required
                                       autofocus>
                                @error('barcode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Pindai barcode dengan scanner, ketik manual, atau klik tombol di bawah untuk generate otomatis.</div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn-generate-barcode"
                                        data-url="{{ route('admin.products.barcode.generate') }}"
                                        aria-label="Generate barcode otomatis">
                                    <i class="bi bi-barcode" aria-hidden="true"></i>
                                    Generate Barcode Otomatis
                                </button>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="name" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $product->name) }}"
                                       maxlength="150"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror"
                                        id="category_id"
                                        name="category_id"
                                        required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            @selected((string) old('category_id', $product->category_id) === (string) $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="unit" class="form-label">Satuan <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('unit') is-invalid @enderror"
                                       id="unit"
                                       name="unit"
                                       value="{{ old('unit', $product->unit) }}"
                                       maxlength="20"
                                       required>
                                @error('unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Contoh: pcs, bungkus, botol, karton.</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="cost_price" class="form-label">Harga Pokok <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number"
                                           class="form-control font-money @error('cost_price') is-invalid @enderror"
                                           id="cost_price"
                                           name="cost_price"
                                           value="{{ old('cost_price', $product->cost_price) }}"
                                           min="0"
                                           step="1"
                                           required>
                                    @error('cost_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="sale_price" class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number"
                                           class="form-control font-money @error('sale_price') is-invalid @enderror"
                                           id="sale_price"
                                           name="sale_price"
                                           value="{{ old('sale_price', $product->sale_price) }}"
                                           min="0"
                                           step="1"
                                           required>
                                    @error('sale_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="min_stock" class="form-label">Stok Minimum <span class="text-danger">*</span></label>
                                <input type="number"
                                       class="form-control font-money @error('min_stock') is-invalid @enderror"
                                       id="min_stock"
                                       name="min_stock"
                                       value="{{ old('min_stock', $product->min_stock) }}"
                                       min="0"
                                       step="1"
                                       required>
                                @error('min_stock')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Batas bawah sebelum ditandai "stok menipis".</div>
                            </div>

                            @if ($mode === 'create')
                                <div class="col-12 col-md-4">
                                    <label for="stock" class="form-label">Stok Awal <span class="text-danger">*</span></label>
                                    <input type="number"
                                           class="form-control font-money @error('stock') is-invalid @enderror"
                                           id="stock"
                                           name="stock"
                                           value="{{ old('stock', 0) }}"
                                           min="0"
                                           step="1"
                                           required>
                                    @error('stock')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Dicatat sebagai mutasi stok awal.</div>
                                </div>
                            @else
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Stok Saat Ini</label>
                                    <input type="text" class="form-control font-money" value="{{ $product->stock }}" disabled>
                                    <div class="form-text">
                                        Stok tidak diubah dari form ini. Kelola lewat
                                        <a href="#" onclick="document.getElementById('v-color')?.scrollIntoView({behavior:'smooth',block:'center'})">Varian (Ukuran &amp; Warna)</a>
                                        atau tombol penyesuaian stok di bawah (jika tersedia).
                                    </div>
                                </div>
                            @endif

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           role="switch"
                                           id="is_active"
                                           name="is_active"
                                           value="1"
                                           @checked(old('is_active', $product->is_active))>
                                    <label class="form-check-label" for="is_active">Produk Aktif (bisa dijual)</label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i>
                                {{ $mode === 'create' ? 'Simpan Produk' : 'Simpan Perubahan' }}
                            </button>
                            <a href="{{ route('admin.products.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-calculator"></i> Ringkasan Margin
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted-2">Harga Pokok</span>
                        <span class="fw-semibold font-money" id="preview-cost">{{ rupiah(old('cost_price', $product->cost_price)) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted-2">Harga Jual</span>
                        <span class="fw-semibold font-money" id="preview-price">{{ rupiah(old('sale_price', $product->sale_price)) }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold">Margin per Unit</span>
                        <span class="fw-bold font-money text-success" id="preview-margin">Rp 0</span>
                    </div>
                    <p class="small text-muted-2 mt-3 mb-0">
                        Harga jual tidak boleh lebih kecil dari harga pokok. Sistem menolak
                        jika violate aturan ini.
                    </p>
                </div>
            </div>
        </div>
    </div>

        @if ($mode === 'edit')
            @php
                /** Galeri foto produk dari tabel media (foto utama paling depan). */
                $photos = $product->photos()->get();
                $colorOptions = $product->colors;
            @endphp
            <div class="row g-3 mt-0">
                {{-- Warna produk --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-palette"></i> Variasi Warna</span>
                            <span class="badge text-bg-secondary">{{ $colorOptions->count() }} warna</span>
                        </div>
                        <div class="card-body">
                            @if ($colorOptions->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    @foreach ($colorOptions as $color)
                                        <span class="badge rounded-pill border d-inline-flex align-items-center gap-1"
                                              style="background: {{ $color->swatch() }}; color: #fff; {{ $color->is_active ? '' : 'opacity:.45;' }}">
                                            {{ $color->name }}
                                        </span>
                                        <form method="POST" action="{{ route('admin.colors.update', $color) }}" class="d-flex gap-1 align-items-center">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" value="{{ $color->name }}" class="form-control form-control-sm" style="width:110px" maxlength="50" required>
                                            <input type="color" name="hex" value="{{ $color->hex ?: '#000000' }}" class="form-control form-control-sm" style="width:45px;padding:2px" title="Warna swatch">
                                            <button type="submit" class="btn btn-sm btn-light" title="Simpan warna"><i class="bi bi-check"></i></button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.colors.toggle', $color) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-light" title="{{ $color->is_active ? 'Nonaktifkan' : 'Aktifkan' }} warna">
                                                <i class="bi bi-{{ $color->is_active ? 'eye-slash' : 'eye' }}"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.colors.destroy', $color) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger" title="Hapus warna"
                                                    data-confirm="Hapus warna {{ $color->name }}?">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.colors.store', $product) }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1" for="c-name">Nama Warna</label>
                                    <input type="text" id="c-name" name="name" class="form-control form-control-sm" placeholder="mis. Cream" maxlength="50" required>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small mb-1" for="c-hex">Swatch</label>
                                    <input type="color" id="c-hex" name="hex" value="#111111" class="form-control form-control-sm" style="padding:2px">
                                </div>
                                <div class="col-12 col-md-7">
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Tambah Warna</button>
                                </div>
                            </form>
                            <p class="small text-muted-2 mt-2 mb-0">
                                Warna yang sudah dipakai transaksi tidak bisa dihapus — cukup dinonaktifkan agar riwayat tetap utuh.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Varian (Ukuran & Warna) --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-grid-3x3-gap"></i> Varian (Ukuran &amp; Warna)</span>
                            <span class="badge text-bg-secondary">{{ $product->variants->count() }} varian</span>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted-2 mb-3">
                                Isi varian jika produk ini tersedia dalam kombinasi ukuran &amp; warna dengan stok berbeda.
                                Total stok produk diisi otomatis dari penjumlahan stok varian.
                            </p>

                            @if ($product->variants->isNotEmpty())
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Warna</th>
                                                <th>Ukuran</th>
                                                <th class="text-end">Stok</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($product->variants->sortBy([['size', 'asc'], ['color', 'asc']]) as $variant)
                                                <tr>
                                                    <td>
                                                        {{ $variant->color ?? '-' }}
                                                        @if ($variant->color_id)
                                                            <span class="badge text-bg-light">terhubung</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $variant->size ?? '-' }}</td>
                                                    <td class="text-end font-money">{{ $variant->stock }}</td>
                                                    <td>
                                                        <span class="badge {{ $variant->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                            {{ $variant->is_active ? 'Aktif' : 'Nonaktif' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr class="table-light small">
                                                    <td colspan="4">
                                                        <form method="POST" action="{{ route('admin.variants.update', $variant) }}"
                                                              class="row g-1 align-items-end">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="col-6 col-md-3">
                                                                <label class="form-label small mb-0">Warna</label>
                                                                <select name="color_id" class="form-select form-select-sm">
                                                                    <option value="">—</option>
                                                                    @foreach ($colorOptions as $color)
                                                                        <option value="{{ $color->id }}"
                                                                            {{ (int) $variant->color_id === $color->id || (! $variant->color_id && $variant->color === $color->name) ? 'selected' : '' }}>
                                                                            {{ $color->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-6 col-md-2">
                                                                <label class="form-label small mb-0">Ukuran</label>
                                                                <input type="text" name="size" value="{{ $variant->size }}"
                                                                       class="form-control form-control-sm" maxlength="20">
                                                            </div>
                                                            <div class="col-6 col-md-2">
                                                                <label class="form-label small mb-0">Stok</label>
                                                                <input type="number" name="stock" value="{{ $variant->stock }}"
                                                                       class="form-control form-control-sm" min="0" required>
                                                            </div>
                                                            <div class="col-6 col-md-2">
                                                                <label class="form-label small mb-0">Urutan</label>
                                                                <input type="number" name="sort_order" value="{{ $variant->sort_order }}"
                                                                       class="form-control form-control-sm" min="0">
                                                            </div>
                                                            <div class="col-12 col-md-3 d-flex gap-1">
                                                                <button type="submit" class="btn btn-sm btn-primary" title="Simpan varian">
                                                                    <i class="bi bi-check"></i> Simpan
                                                                </button>
                                                            </div>
                                                        </form>
                                                        <div class="mt-1 d-flex gap-1">
                                                            <form method="POST" action="{{ route('admin.variants.toggle', $variant) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="btn btn-sm btn-light" title="Aktif/nonaktif varian">
                                                                    <i class="bi bi-{{ $variant->is_active ? 'eye-slash' : 'eye' }}"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" action="{{ route('admin.variants.destroy', $variant) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-light text-danger" title="Hapus varian"
                                                                        data-confirm="Hapus varian {{ $variant->label() }}?">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-end">Total Stok Varian Aktif</th>
                                                <th class="text-end font-money">{{ $product->variants->where('is_active', true)->sum('stock') }}</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <p class="small text-muted-2 mb-3">Belum ada varian. Produk dianggap single-size; stok memakai kolom "Stok Saat Ini".</p>
                            @endif

                            <form method="POST" action="{{ route('admin.variants.store', $product) }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1" for="v-color">Warna</label>
                                    <select id="v-color" name="color_id" class="form-select form-select-sm">
                                        <option value="">— tanpa warna —</option>
                                        @foreach ($colorOptions as $color)
                                            <option value="{{ $color->id }}">{{ $color->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="color" value="">
                                </div>
                                <div class="col-4 col-md-3">
                                    <label class="form-label small mb-1" for="v-size">Ukuran</label>
                                    <input type="text" id="v-size" name="size" class="form-control form-control-sm"
                                           placeholder="mis. M" maxlength="20">
                                </div>
                                <div class="col-4 col-md-2">
                                    <label class="form-label small mb-1" for="v-stock">Stok <span class="text-danger">*</span></label>
                                    <input type="number" id="v-stock" name="stock" class="form-control form-control-sm"
                                           min="0" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        <i class="bi bi-plus-lg"></i> Tambah Varian
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-images"></i> Foto Produk (Galeri)</span>
                            <span class="badge text-bg-secondary">{{ $photos->count() }} foto</span>
                        </div>
                        <div class="card-body">
                            @if ($photos->isEmpty())
                                <p class="small text-muted-2 mb-3">
                                    Belum ada foto. Unggah foto pertama; foto pertama otomatis
                                    menjadi <strong>foto utama</strong>.
                                </p>
                            @else
                                <div class="d-flex flex-wrap gap-3 mb-2" data-order-group="reorder-form-product">
                                    @foreach ($photos as $photo)
                                        <div class="position-relative" data-media-id="{{ $photo->id }}" style="width: 150px;">
                                            <img src="{{ $photo->url() }}"
                                                 class="rounded border d-block w-100 @if ($photo->is_primary) media-primary-ring @endif"
                                                 style="height: 110px; object-fit: cover;"
                                                 alt="Foto produk {{ $product->name }}">

                                            @if ($photo->is_primary)
                                                <span class="badge text-bg-primary position-absolute top-0 start-0 m-1">
                                                    <i class="bi bi-star-fill"></i> Utama
                                                </span>
                                            @endif
                                            @if ($photo->color)
                                                <span class="badge text-bg-dark position-absolute bottom-0 start-0 m-1">
                                                    {{ $photo->color }}
                                                </span>
                                            @endif
                                            @if ($photo->is_color_primary)
                                                <span class="badge text-bg-info position-absolute top-0 end-0 m-1">
                                                    Thumbnail warna
                                                </span>
                                            @endif

                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @unless ($photo->is_color_primary)
                                                    <form method="POST" action="{{ route('admin.media.color.primary', $photo) }}" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-light" title="Jadikan thumbnail warna">
                                                            <i class="bi bi-image"></i>
                                                        </button>
                                                    </form>
                                                @endunless
                                                @unless ($photo->is_primary)
                                                    <form method="POST" action="{{ route('admin.media.primary', $photo) }}" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-light"
                                                                title="Jadikan foto utama" aria-label="Jadikan foto utama">
                                                            <i class="bi bi-star"></i>
                                                        </button>
                                                    </form>
                                                @endunless
                                                <form method="POST" action="{{ route('admin.media.destroy', $photo) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light text-danger" title="Hapus foto"
                                                            aria-label="Hapus foto"
                                                            data-confirm="Hapus foto ini? Berkas lama ikut dihapus dari penyimpanan.">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-light" data-move="up"
                                                        title="Geser ke depan" aria-label="Geser ke depan">
                                                    <i class="bi bi-arrow-up"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light" data-move="down"
                                                        title="Geser ke belakang" aria-label="Geser ke belakang">
                                                    <i class="bi bi-arrow-down"></i>
                                                </button>
                                            </div>

                                            {{-- Label / entitas warna per foto --}}
                                            <form method="POST" action="{{ route('admin.media.color', $photo) }}" class="mt-1">
                                                @csrf
                                                @method('PATCH')
                                                <div class="input-group input-group-sm">
                                                    <select name="color_id" class="form-select form-select-sm" style="max-width:130px">
                                                        <option value="">— label bebas —</option>
                                                        @foreach ($colorOptions as $color)
                                                            <option value="{{ $color->id }}"
                                                                {{ (int) $photo->color_id === $color->id ? 'selected' : '' }}>
                                                                {{ $color->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <input type="text" name="color" value="{{ $photo->color }}"
                                                           class="form-control" placeholder="Warna" maxlength="50"
                                                           aria-label="Label warna foto">
                                                    <button type="submit" class="btn btn-light" title="Simpan warna foto">
                                                        <i class="bi bi-tag"></i>
                                                    </button>
                                                </div>
                                            </form>

                                            {{-- Ganti berkas foto ini --}}
                                            <form method="POST" action="{{ route('admin.media.replace', $photo) }}"
                                                  enctype="multipart/form-data" data-media-upload class="mt-1">
                                                @csrf
                                                @method('PATCH')
                                                <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"
                                                       class="form-control form-control-sm"
                                                       aria-label="Ganti berkas foto" required>
                                                <button type="submit" class="btn btn-sm btn-light mt-1 w-100" title="Ganti berkas foto ini">
                                                    <i class="bi bi-arrow-repeat"></i> Ganti berkas
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <hr>

                            {{-- Unggah beberapa foto sekaligus, boleh ditandai satu warna --}}
                            <form method="POST" action="{{ route('admin.products.photos.store', $product) }}"
                                  enctype="multipart/form-data" data-media-upload>
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-12 col-lg-5">
                                        <label class="form-label small mb-1" for="product-photos">Unggah Foto Baru</label>
                                        <input type="file" id="product-photos" name="photos[]" multiple
                                               accept=".jpg,.jpeg,.png,.webp"
                                               class="form-control form-control-sm"
                                               data-preview="preview-product-photos" required>
                                        <div id="preview-product-photos" class="d-flex flex-wrap gap-2 mt-2"></div>
                                    </div>
                                    <div class="col-6 col-lg-3">
                                        <label class="form-label small mb-1" for="product-photo-color">Warna Foto</label>
                                        <select id="product-photo-color" name="color_id" class="form-select form-select-sm">
                                            <option value="">— tanpa warna —</option>
                                            @foreach ($colorOptions as $color)
                                                <option value="{{ $color->id }}">{{ $color->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="color" value="">
                                    </div>
                                    <div class="col-6 col-lg-4">
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="bi bi-cloud-arrow-up"></i> Unggah Foto
                                        </button>
                                    </div>
                                </div>
                                <div class="form-text">
                                    Format JPG, JPEG, PNG, atau WebP; maksimal
                                    {{ number_format(config('media.max_size_kb') / 1024, 0) }} MB per berkas,
                                    maksimal {{ config('media.max_files_per_upload') }} berkas per unggahan.
                                    Gambar besar otomatis diperkecil agar halaman tetap cepat.
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <form id="reorder-form-product" method="POST" action="{{ route('admin.media.reorder') }}" class="d-none">
                @csrf
            </form>
        @endif
    @endsection

@push('scripts')
    <script>
        (function () {
            const cost = document.getElementById('cost_price');
            const price = document.getElementById('sale_price');
            const pCost = document.getElementById('preview-cost');
            const pPrice = document.getElementById('preview-price');
            const pMargin = document.getElementById('preview-margin');

            function rupiah(value) {
                return 'Rp ' + Math.round(value || 0).toLocaleString('id-ID');
            }

            function update() {
                const c = parseFloat(cost.value || '0');
                const p = parseFloat(price.value || '0');
                pCost.textContent = rupiah(c);
                pPrice.textContent = rupiah(p);
                pMargin.textContent = rupiah(p - c);
                pMargin.classList.toggle('text-danger', p > 0 && p < c);
                pMargin.classList.toggle('text-success', !(p > 0 && p < c));
            }

            cost.addEventListener('input', update);
            price.addEventListener('input', update);
            update();
        })();
    </script>

    @include('partials.media-scripts')

    <script>
        (function () {
            var btn = document.getElementById('btn-generate-barcode');
            var input = document.getElementById('barcode');

            if (!btn || !input) {
                return;
            }

            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-url');

                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-barcode bi-spin" aria-hidden="true"></i> Memproses...';

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Gagal generate barcode.');
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (data && data.barcode) {
                        input.value = data.barcode;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                })
                .catch(function (error) {
                    console.error(error);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-barcode" aria-hidden="true"></i> Generate Barcode Otomatis';
                });
            });
        })();
    </script>
@endpush
