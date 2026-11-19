@extends('layouts.store')

@section('title', $product->name)

@push('styles')
<style>
    .st-gallery-stage { position: relative; }
    .st-detail-img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: .6rem; background: linear-gradient(165deg, #f9efe9, #eddcd3); }
    .st-detail-img.is-illustration { object-fit: contain; padding: 2.5rem; }
    .st-thumb { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: .4rem; border: 1px solid var(--line); cursor: pointer; }
    .st-thumb.active { border: 2px solid var(--rose); }
    .st-gallery-nav {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 36px; height: 36px; border-radius: 50%; border: 0;
        background: rgba(255,255,255,.92); color: #2e2826;
        display: grid; place-items: center; box-shadow: 0 2px 10px rgba(0,0,0,.12);
    }
    .st-gallery-nav.prev { left: 10px; }
    .st-gallery-nav.next { right: 10px; }
    .st-gallery-counter {
        position: absolute; bottom: 12px; right: 12px;
        background: rgba(46,40,38,.78); color: #fff;
        border-radius: 999px; padding: 3px 11px; font-size: .75rem;
    }
    .st-swatch {
        width: 30px; height: 30px; border-radius: 50%;
        border: 2px solid #fff; box-shadow: 0 0 0 1px var(--line);
        cursor: pointer; padding: 0;
    }
    .st-swatch.active { box-shadow: 0 0 0 2px var(--rose); }
    .st-swatch:disabled { opacity: .35; cursor: not-allowed; }
    .st-size-btn.active { background: var(--rose); color: #fff; border-color: var(--rose); }
    .st-price-lg { color: var(--rose); font-weight: 700; font-size: 1.6rem; }
    .st-size-table th, .st-size-table td { font-size: .78rem; }
</style>
@endpush

@section('content')
    @php
        $colors = $product->activeColors();
        $variantData = [];

        foreach ($product->variants as $variant) {
            if (! ($variant->is_active ?? true)) {
                continue;
            }

            $colorName = $variant->color ?: '';
            $variantData[$colorName][] = [
                'size' => (string) $variant->size,
                'stock' => (int) $variant->stock,
            ];
        }

        $initialColor = '';

        foreach ($colors as $color) {
            if (! empty($variantData[$color->name])) {
                $initialColor = $color->name;
                break;
            }
        }

        if ($initialColor === '' && $colors->isNotEmpty()) {
            $initialColor = $colors->first()->name;
        }

        $initialPhotos = $gallery[$initialColor] ?? $gallery[''] ?? [];
        if ($initialPhotos === []) {
            $initialPhotos = $product->photos->map(fn ($photo) => ['src' => $photo->url(), 'alt' => 'Foto '.$product->name])->values()->all();
        }
    @endphp

    <section class="container py-4">
        <nav class="small text-muted-2 mb-3" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="text-decoration-none">Beranda</a>
            <span>/</span>
            <a href="{{ route('store.catalog') }}" class="text-decoration-none">Katalog</a>
            <span>/</span>
            <span>{{ $product->name }}</span>
        </nav>

        <div class="row g-4">
            {{-- Kiri: galeri per warna --}}
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="st-gallery-stage" id="gallery-stage">
                            <img id="gallery-main"
                                 src="{{ $initialPhotos[0]['src'] ?? asset('assets/images/products/dresses/linen-blazer-dress.jpg') }}"
                                 class="st-detail-img rounded-3 shadow-sm"
                                 alt="{{ $initialPhotos[0]['alt'] ?? 'Foto '.$product->name }}">
                            @if (count($initialPhotos) > 1)
                                <button type="button" class="st-gallery-nav prev" id="gallery-prev" aria-label="Foto sebelumnya">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <button type="button" class="st-gallery-nav next" id="gallery-next" aria-label="Foto berikutnya">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            @endif
                            <span class="st-gallery-counter" id="gallery-counter">1 / {{ max(1, count($initialPhotos)) }}</span>
                        </div>

                        <div id="gallery-thumbs" class="d-flex flex-wrap gap-2 mt-3">
                            @foreach ($initialPhotos as $index => $photo)
                                <img src="{{ $photo['src'] }}"
                                     class="st-thumb {{ $index === 0 ? 'active' : '' }}"
                                     style="width: 78px;"
                                     data-src="{{ $photo['src'] }}"
                                     alt="{{ $photo['alt'] }}"
                                     tabindex="0" role="button" aria-label="Lihat foto {{ $index + 1 }}">
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kanan: info produk --}}
            <div class="col-12 col-lg-6">
                <h1 class="st-section" style="font-size: 1.6rem;">{{ $product->name }}</h1>
                <p class="small text-muted-2">Kode: {{ $product->barcode }} · Kategori: {{ $product->category?->name ?? '-' }}</p>

                <div class="st-price-lg mb-2">{{ rupiah($product->sale_price) }}</div>
                <p class="small mb-2" id="variant-stock">
                    @if ($variantData !== [])
                        <span class="badge text-bg-success">Pilih warna &amp; ukuran untuk melihat stok</span>
                    @elseif ($product->stock > 0)
                        <span class="badge text-bg-success">Stok tersedia: {{ $product->stock }}</span>
                    @else
                        <span class="badge text-bg-secondary">Stok habis</span>
                    @endif
                </p>

                <p style="color: var(--muted);">Garment premium Tabita Modiste — dikerjakan dari bahan pilihan dengan jahitan rapi dan detail yang elegan.</p>

                <hr class="stitch my-3">

                {{-- Size chart --}}
                <h2 class="small fw-bold text-uppercase mb-2" style="letter-spacing: .08em; color: var(--rose);">Size Chart (cm)</h2>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered st-size-table mb-0">
                        <thead>
                            <tr>
                                @foreach ($chart['headers'] as $header)
                                    <th>{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($chart['rows'] as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted-2">Ukuran dihitung manual oleh tim Tabita Modiste — jika ragu, konsultasikan via WhatsApp sebelum memesan.</p>

                <form method="POST" action="{{ route('store.cart.add') }}" id="add-to-cart-form">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if ($colors->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-2">Warna *</label>
                            <div class="d-flex flex-wrap gap-2" id="color-swatches">
                                @foreach ($colors as $color)
                                    <button type="button"
                                            class="st-swatch {{ $color->name === $initialColor ? 'active' : '' }}"
                                            style="background: {{ $color->swatch() }}"
                                            data-color="{{ $color->name }}"
                                            title="{{ $color->name }}{{ $color->hex ? ' ('.$color->hex.')' : '' }}"
                                            aria-label="Pilih warna {{ $color->name }}"></button>
                                @endforeach
                            </div>
                            <div class="small mt-1 fw-semibold" id="color-label">{{ $initialColor ?: 'Pilih warna' }}</div>
                            {{-- Input warna yang dikirim ke server --}}
                            <input type="hidden" name="color" id="color-input" value="{{ $initialColor }}">
                        </div>
                    @else
                        <input type="hidden" name="color" id="color-input" value="">
                    @endif

                    @if ($product->variants->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-2" for="size">Ukuran *</label>
                            <div class="d-flex flex-wrap gap-2" id="size-options">
                                @foreach (($variantData[$initialColor] ?? []) as $option)
                                    <button type="button" class="btn btn-sm btn-outline-secondary st-size-btn"
                                            data-size="{{ $option['size'] }}" data-stock="{{ $option['stock'] }}">
                                        {{ $option['size'] }}
                                    </button>
                                @endforeach
                            </div>
                            <select name="size" id="size" class="visually-hidden" required>
                                @foreach (($variantData[$initialColor] ?? []) as $option)
                                    <option value="{{ $option['size'] }}">{{ $option['size'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="size" value="">
                    @endif

                    <div class="d-flex gap-2 align-items-center mb-2">
                        <div class="input-group" style="width: 130px;">
                            <span class="input-group-text">Qty</span>
                            <input type="number" name="qty" id="qty-input" value="1" min="1" max="99" class="form-control" aria-label="Jumlah beli">
                        </div>
                        <button type="submit" class="st-btn" id="add-to-cart-btn">
                            <i class="bi bi-bag-plus" aria-hidden="true"></i> Add to Cart
                        </button>
                        <button type="submit" name="buy_now" value="1" class="st-btn st-btn-outline" id="buy-now-btn">
                            <i class="bi bi-lightning-charge" aria-hidden="true"></i> Buy Now
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('store.wishlist.toggle', $product) }}" class="d-inline-flex">
                    @csrf
                    <button type="submit" class="st-btn st-btn-outline" aria-label="Tambah/hapus dari wishlist">
                        <i class="bi bi-heart" aria-hidden="true"></i> Wishlist
                    </button>
                </form>

                @if ($waNumber)
                    @php($msg = "Halo, saya ingin memesan: ".$product->name." (Kode: ".$product->barcode.") Harga: ".rupiah($product->sale_price)."\nTerima kasih.")
                    <a href="https://wa.me/{{ $waNumber }}?text={{ rawurlencode($msg) }}" target="_blank" rel="noopener noreferrer" class="st-btn w-100 mt-2 justify-content-center" style="background:#128C7E; color:#fff;">
                        <i class="bi bi-whatsapp" aria-hidden="true"></i> Tanya / Pesan via WhatsApp
                    </a>
                @else
                    <p class="small text-warning-emphasis mt-2 mb-0"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> WhatsApp toko belum dikonfigurasi Owner.</p>
                @endif
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(function () {
    const gallery = @json($gallery);
    const variants = @json($variantData);
    const initialColor = @json($initialColor);

    const main = document.getElementById('gallery-main');
    const thumbs = document.getElementById('gallery-thumbs');
    const counter = document.getElementById('gallery-counter');
    const prev = document.getElementById('gallery-prev');
    const next = document.getElementById('gallery-next');
    const stage = document.getElementById('gallery-stage');
    const colorInput = document.getElementById('color-input');
    const colorLabel = document.getElementById('color-label');
    const sizeSelect = document.getElementById('size');
    const sizeOptions = document.getElementById('size-options');
    const qty = document.getElementById('qty-input');
    const addBtn = document.getElementById('add-to-cart-btn');
    const buyBtn = document.getElementById('buy-now-btn');
    const stockInfo = document.getElementById('variant-stock');

    let photos = (gallery[initialColor] || gallery[''] || []);
    let index = 0;

    function render() {
        if (!photos.length) return;
        if (index >= photos.length) index = 0;
        if (index < 0) index = photos.length - 1;
        main.src = photos[index].src;
        main.alt = photos[index].alt;
        counter.textContent = (index + 1) + ' / ' + photos.length;
        thumbs.innerHTML = '';
        photos.forEach(function (photo, i) {
            const img = document.createElement('img');
            img.src = photo.src;
            img.alt = photo.alt;
            img.className = 'st-thumb' + (i === index ? ' active' : '');
            img.style.width = '78px';
            img.dataset.src = photo.src;
            img.setAttribute('tabindex', '0');
            img.setAttribute('role', 'button');
            img.addEventListener('click', function () { index = i; render(); });
            img.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); index = i; render(); }
            });
            thumbs.appendChild(img);
        });
    }

    function selectedStock() {
        if (!sizeSelect || !variants[colorInput.value]) return null;
        const found = variants[colorInput.value].find(function (v) { return v.size === sizeSelect.value; });
        return found ? found.stock : null;
    }

    function updateStockUi() {
        const stock = selectedStock();
        if (stock === null) {
            stockInfo.innerHTML = '<span class="badge text-bg-secondary">Pilih varian</span>';
            qty.max = 99;
            addBtn.disabled = false;
            buyBtn.disabled = false;
            return;
        }
        if (stock <= 0) {
            stockInfo.innerHTML = '<span class="badge text-bg-secondary">Stok varian habis</span>';
        } else {
            stockInfo.innerHTML = '<span class="badge text-bg-success">Stok varian: ' + stock + '</span>';
        }
        qty.max = Math.max(1, stock);
        if (Number(qty.value) > stock) qty.value = stock;
        addBtn.disabled = stock <= 0;
        buyBtn.disabled = stock <= 0;
    }

    function chooseColor(name) {
        colorInput.value = name;
        if (colorLabel) colorLabel.textContent = name || 'Pilih warna';
        document.querySelectorAll('#color-swatches .st-swatch').forEach(function (b) {
            b.classList.toggle('active', b.dataset.color === name);
        });

        photos = (gallery[name] || gallery[''] || []);
        index = 0;
        render();

        if (sizeOptions && sizeSelect) {
            const options = variants[name] || [];
            sizeOptions.innerHTML = '';
            sizeSelect.innerHTML = '';
            options.forEach(function (option) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-outline-secondary st-size-btn';
                btn.dataset.size = option.size;
                btn.dataset.stock = option.stock;
                btn.textContent = option.size;
                btn.addEventListener('click', function () {
                    sizeSelect.value = option.size;
                    document.querySelectorAll('#size-options .st-size-btn').forEach(function (b) {
                        b.classList.toggle('active', b === btn);
                    });
                    updateStockUi();
                });
                sizeOptions.appendChild(btn);

                const opt = document.createElement('option');
                opt.value = option.size;
                opt.textContent = option.size;
                sizeSelect.appendChild(opt);
            });
            const first = sizeSelect.querySelector('option');
            if (first) {
                sizeSelect.value = first.value;
                const firstBtn = sizeOptions.querySelector('.st-size-btn');
                if (firstBtn) firstBtn.classList.add('active');
            }
        }
        updateStockUi();
    }

    document.querySelectorAll('#color-swatches .st-swatch').forEach(function (button) {
        button.addEventListener('click', function () { chooseColor(button.dataset.color); });
    });

    if (prev) prev.addEventListener('click', function () { index--; render(); });
    if (next) next.addEventListener('click', function () { index++; render(); });

    let touchX = null;
    stage.addEventListener('touchstart', function (e) { touchX = e.changedTouches[0].screenX; }, {passive: true});
    stage.addEventListener('touchend', function (e) {
        if (touchX === null) return;
        const delta = e.changedTouches[0].screenX - touchX;
        if (Math.abs(delta) > 40) { index += delta < 0 ? 1 : -1; render(); }
        touchX = null;
    }, {passive: true});

    chooseColor(initialColor);
})();
</script>
@endpush
