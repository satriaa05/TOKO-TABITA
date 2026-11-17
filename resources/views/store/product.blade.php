@extends('layouts.store')

@section('title', $product->name)

@push('styles')
<style>
    .st-detail-img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: .6rem; background: linear-gradient(165deg, #f9efe9, #eddcd3); }
    .st-detail-img.is-illustration { object-fit: contain; padding: 2.5rem; }
    .st-thumb { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: .4rem; border: 1px solid var(--line); cursor: pointer; }
    .st-thumb.active { border: 2px solid var(--rose); }
    .st-price-lg { color: var(--rose); font-weight: 700; font-size: 1.6rem; }
    .st-size-table th, .st-size-table td { font-size: .78rem; }
</style>
@endpush

@section('content')
    <section class="container py-4">
        <nav class="small text-muted-2 mb-3" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="text-decoration-none">Beranda</a>
            <span>/</span>
            <a href="{{ route('store.catalog') }}" class="text-decoration-none">Katalog</a>
            <span>/</span>
            <span>{{ $product->name }}</span>
        </nav>

        <div class="row g-4">
            {{-- Kiri: galeri --}}
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        @php($main = $product->photos->first())
                        <img id="gallery-main"
                             src="{{ $main ? $main->url() : asset('assets/images/products/dresses/linen-blazer-dress.jpg') }}"
                             class="st-detail-img rounded-3 shadow-sm"
                             style="object-fit: cover;"
                             alt="Foto model {{ $product->name }}">
                        @if ($product->photos->count() > 1)
                            <div id="gallery-thumbs" class="d-flex flex-wrap gap-2 mt-3">
                                @foreach ($product->photos as $photo)
                                    <img src="{{ $photo->url() }}"
                                         class="st-thumb {{ $loop->first ? 'active' : '' }}"
                                         style="width: 78px;"
                                         data-src="{{ $photo->url() }}"
                                         alt="Foto {{ $product->name }} {{ $loop->iteration }}"
                                         tabindex="0" role="button" aria-label="Lihat foto {{ $loop->iteration }}">
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Kanan: info produk --}}
            <div class="col-12 col-lg-6">
                <h1 class="st-section" style="font-size: 1.6rem;">{{ $product->name }}</h1>
                <p class="small text-muted-2">Kode: {{ $product->barcode }} · Kategori: {{ $product->category?->name ?? '-' }}</p>

                <div class="st-price-lg mb-2">{{ rupiah($product->sale_price) }}</div>
                <p class="small mb-3">
                    @if ($product->stock > 0)
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

                <form method="POST" action="{{ route('store.cart.add') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if ($product->variants->isNotEmpty())
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="size">Ukuran *</label>
                                <select id="size" name="size" class="form-select" required>
                                    <option value="">Pilih</option>
                                    @foreach ($product->variants->pluck('size')->unique()->filter() as $size)
                                        <option value="{{ $size }}">{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="color">Warna *</label>
                                <select id="color" name="color" class="form-select" required>
                                    <option value="">Pilih</option>
                                    @foreach ($product->variants->pluck('color')->unique()->filter() as $color)
                                        <option value="{{ $color }}">{{ $color }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex gap-2 align-items-center mb-2">
                        <div class="input-group" style="width: 130px;">
                            <span class="input-group-text">Qty</span>
                            <input type="number" name="qty" value="1" min="1" max="99" class="form-control" aria-label="Jumlah beli" {{ $product->stock < 1 ? 'disabled' : '' }}>
                        </div>
                        <button type="submit" class="st-btn" {{ $product->stock < 1 ? 'disabled' : '' }}>
                            <i class="bi bi-bag-plus" aria-hidden="true"></i> Add to Cart
                        </button>
                        <button type="submit" name="buy_now" value="1" class="st-btn st-btn-outline" {{ $product->stock < 1 ? 'disabled' : '' }}>
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
                    <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($msg) }}" target="_blank" rel="noopener" class="st-btn w-100 mt-2 justify-content-center" style="background:#128C7E; color:#fff;">
                        <i class="bi bi-whatsapp" aria-hidden="true"></i> Tanya / Pesan via WhatsApp
                    </a>
                @else
                    <p class="small text-warning-emphasis mt-2 mb-0"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> WhatsApp toko belum dikonfigurasi admin.</p>
                @endif
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(function () {
    var main = document.getElementById('gallery-main');
    document.querySelectorAll('#gallery-thumbs .st-thumb').forEach(function (thumb) {
        var activate = function () {
            main.src = thumb.dataset.src;
            document.querySelectorAll('#gallery-thumbs .st-thumb').forEach(function (t) {
                t.classList.toggle('active', t === thumb);
            });
        };
        thumb.addEventListener('click', activate);
        thumb.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                activate();
            }
        });
    });
})();
</script>
@endpush
