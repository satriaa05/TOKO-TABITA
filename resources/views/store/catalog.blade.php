@extends('layouts.store')

@section('title', 'Katalog')

@section('content')
    <section class="container py-4 py-lg-5">

        {{-- Breadcrumb --}}
        <nav class="st-breadcrumb mb-3" aria-label="Jejak navigasi">
            <a href="{{ route('home') }}">Home</a>
            <span aria-hidden="true">&nbsp;/&nbsp;</span>
            <span aria-current="page">Katalog</span>
        </nav>

        {{-- Header --}}
        <div class="text-center mb-4">
            <p class="st-eyebrow mb-1">Tabita Modiste Collection</p>
            <h1 class="store-section-title mb-2">Katalog Kami</h1>
            <p class="text-muted-2 mx-auto mb-0" style="max-width: 520px;">
                Koleksi garment premium pilihan — dress, atasan, bawahan, dan hijab.
                Setiap potong dikerjakan rapi dengan bahan pilihan.
            </p>
        </div>

        {{-- Search + sort --}}
        <form method="GET" action="{{ route('store.catalog') }}" class="st-catalog-bar d-flex flex-column flex-md-row gap-2 align-items-md-center mb-3" role="search">
            <div class="input-group" style="max-width: 420px;">
                <span class="input-group-text bg-white"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="search" class="form-control border-start-0" name="q" value="{{ $keyword }}"
                       placeholder="Cari nama produk..." aria-label="Cari nama produk" maxlength="100">
            </div>

            <select class="form-select" name="urut" style="max-width: 200px;" aria-label="Urutkan produk" onchange="this.form.submit()">
                @foreach (['terbaru' => 'Terbaru', 'nama' => 'Nama A–Z', 'harga_asc' => 'Harga: Termurah', 'harga_desc' => 'Harga: Termahal'] as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                @endforeach
            </select>

            @if ($keyword !== '')
                <input type="hidden" name="kategori" value="{{ $categoryId }}">
            @endif
            <button type="submit" class="st-btn"><i class="bi bi-search" aria-hidden="true"></i> Cari</button>
            @if ($keyword !== '' || $categoryId > 0 || $sort !== 'terbaru')
                <a href="{{ route('store.catalog') }}" class="small text-muted-2 text-decoration-none">Atur ulang</a>
            @endif
        </form>

        {{-- Filter kategori (chips) --}}
        <div class="st-cat-chips mb-3" role="group" aria-label="Filter kategori">
            <a href="{{ route('store.catalog', array_filter(['q' => $keyword, 'urut' => $sort !== 'terbaru' ? $sort : null])) }}"
               class="st-chip {{ $categoryId === 0 ? 'active' : '' }}">Semua</a>
            @foreach ($categories as $category)
                <a href="{{ route('store.catalog', array_filter(['q' => $keyword, 'kategori' => $category->id, 'urut' => $sort !== 'terbaru' ? $sort : null])) }}"
                   class="st-chip {{ $categoryId === $category->id ? 'active' : '' }}">{{ $category->name }}</a>
            @endforeach
        </div>

        {{-- Hasil --}}
        @if ($products->isEmpty())
            <div class="st-empty">
                <i class="bi bi-search fs-1" style="color: var(--blush);" aria-hidden="true"></i>
                <h2 class="h5 mt-3">Belum ada produk ditemukan</h2>
                <p class="text-muted-2 mb-3">
                    @if ($keyword !== '')
                        Tidak ada hasil untuk "{{ $keyword }}". Coba kata kunci lain atau lihat semua koleksi.
                    @else
                        Belum ada produk pada kategori ini. Silakan lihat kategori lainnya.
                    @endif
                </p>
                <a href="{{ route('store.catalog') }}" class="st-btn"><i class="bi bi-grid" aria-hidden="true"></i> Lihat Semua Produk</a>
            </div>
        @else
            <p class="st-count mb-2">Menampilkan {{ $products->firstItem() }}–{{ $products->lastItem() }} dari {{ $products->total() }} produk</p>

            <div class="row g-2 g-md-3">
                @foreach ($products as $product)
                    @php($hasVariants = $product->variants->isNotEmpty())
                    <div class="col-6 col-md-4 col-lg-3">
                        <article class="st-card h-100 position-relative d-flex flex-column">
                            @if ($product->created_at && $product->created_at->gt(now()->subDays(30)))
                                <span class="st-new">NEW</span>
                            @endif

                            @auth
                                <form method="POST" action="{{ route('store.wishlist.toggle', $product) }}" class="position-absolute" style="top:.4rem; right:.4rem; z-index:2;">
                                    @csrf
                                    <button type="submit" class="st-wishlist" style="position: static;" aria-label="Wishlist {{ $product->name }}">
                                        <i class="bi bi-heart" aria-hidden="true"></i>
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="st-wishlist" aria-label="Masuk untuk menyimpan {{ $product->name }} ke wishlist">
                                    <i class="bi bi-heart" aria-hidden="true"></i>
                                </a>
                            @endauth

                            <a href="{{ route('store.product', $product) }}" class="d-block text-decoration-none" aria-label="Lihat detail {{ $product->name }}">
                                @if ($product->primaryPhoto)
                                    <img src="{{ $product->primaryPhoto->url() }}" alt="Foto produk {{ $product->name }}"
                                         class="st-product-img" loading="lazy" style="object-fit: cover;">
                                @else
                                    <img src="{{ asset('assets/images/products/dresses/linen-blazer-dress.jpg') }}" alt="Foto model {{ $product->name }}"
                                         class="st-product-img" loading="lazy" style="object-fit: cover;">
                                @endif
                            </a>

                            <div class="p-2 p-md-3 d-flex flex-column flex-grow-1">
                                @if ($product->category)
                                    <span class="st-tile-sub text-uppercase" style="letter-spacing:.08em;">{{ $product->category->name }}</span>
                                @endif
                                <div class="fw-semibold mt-1" style="font-size:.88rem; line-height:1.3; min-height: 2.3em;">
                                    <a href="{{ route('store.product', $product) }}" class="text-decoration-none" style="color: var(--ink);">{{ $product->name }}</a>
                                </div>
                                <div class="st-price mt-1">{{ rupiah($product->sale_price) }}</div>
                                <div class="small {{ $product->stock > 0 ? 'text-muted-2' : 'text-danger' }}">
                                    @if ($product->stock > 0) Stok: {{ $product->stock }} @else Stok habis @endif
                                </div>

                                <div class="mt-auto pt-2">
                                    @if ($product->stock < 1)
                                        <button class="st-btn st-btn-outline w-100 justify-content-center" disabled>Stok Habis</button>
                                    @elseif ($hasVariants)
                                        <a href="{{ route('store.product', $product) }}" class="st-btn w-100 justify-content-center">
                                            <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i> Pilih Varian
                                        </a>
                                    @else
                                        <form method="POST" action="{{ route('store.cart.add') }}">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="qty" value="1">
                                            <button type="submit" class="st-btn w-100 justify-content-center">
                                                <i class="bi bi-bag-plus" aria-hidden="true"></i> + Keranjang
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            @if ($products->hasPages())
                <div class="mt-4 d-flex justify-content-center">{{ $products->links() }}</div>
            @endif
        @endif
    </section>
@endsection
