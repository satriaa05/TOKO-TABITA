@extends('layouts.store')

@section('title', 'Beranda')

@push('styles')
<style>
    .st-hero { background: linear-gradient(100deg, #fbf1ee 0%, #f2ddd8 52%, #e3c3bf 100%); }
    .st-script { font-family: "Segoe Script", "Snell Roundhand", "Brush Script MT", cursive; font-style: italic; }
    .st-eyebrow { letter-spacing: .3em; font-size: .68rem; font-weight: 700; text-transform: uppercase; color: var(--rose-dark); }
    .st-headline { font-family: Georgia, "Times New Roman", serif; font-size: clamp(2.6rem, 6.4vw, 4.4rem); line-height: 1.02; color: var(--ink); }
    .st-tile { background: #fffdfb; border: 1px solid var(--line); border-radius: .6rem; overflow: hidden; text-decoration: none; display: flex; flex-direction: column; align-items: center; transition: transform .18s ease, box-shadow .18s ease; }
    .st-tile:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(46, 38, 34, .14); }
    .st-tile-img { width: 100%; height: 120px; object-fit: contain; object-position: center bottom; background: linear-gradient(180deg, #f7ece6, #efe0da); }
    .st-tile-body { padding: .55rem .3rem .65rem; text-align: center; }
    .st-chip-demo { position: absolute; bottom: .35rem; left: .35rem; background: rgba(46,38,34,.55); color: #fff; font-size: .58rem; border-radius: .15rem; padding: .1rem .35rem; letter-spacing: .08em; text-transform: uppercase; }
    .st-promo { background: linear-gradient(100deg, #e9c7b8 0%, #d9ada2 55%, #c99a92 100%); border-radius: .75rem; overflow: hidden; }
    .st-promo-off { font-family: Georgia, "Times New Roman", serif; font-size: clamp(2.4rem, 5.4vw, 3.9rem); line-height: 1.05; color: #2e1a18; }
    .st-stars { color: #d4a24e; font-size: .75rem; letter-spacing: .1em; }
    .st-price-old { text-decoration: line-through; color: var(--muted); font-size: .72rem; margin-right: .3rem; }
    .st-insta-wrap { background: var(--cream); border: 1px solid var(--line); border-radius: .75rem; padding: 1.25rem; }
    .st-newsletter { background: #241c18; color: #f6ebe2; border-radius: .75rem; }
    .st-newsletter input { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); color: #fdf6f0; }
    .st-newsletter input::placeholder { color: #b9a38f; }
    .st-newsletter .btn { background: var(--rose); color: #fff; border: 0; }
    .st-newsletter .btn:hover { background: var(--rose-dark); }
    .st-feature-icon { color: var(--rose); font-size: 1.5rem; }
    @media (max-width: 575.98px) {
        .st-hero { background: linear-gradient(180deg, #f6e7de 0%, #ecd5ce 100%); }
        .st-tile-img { height: 96px; }
    }
    @media (prefers-reduced-motion: reduce) { .st-tile { transition: none; } }
</style>
@endpush

@section('content')
    {{-- ================= HERO ================= --}}
    @php($hero = $banners->first())
    <section class="st-hero">
        <div class="container py-4 py-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-6">
                    <p class="st-eyebrow mb-2">New Season, New You</p>
                    <h1 class="st-headline">Elevate Your <span class="st-script" style="color: var(--rose);">Style!</span></h1>
                    <p class="mt-3 mb-4" style="max-width: 420px; color: var(--muted); font-size: .95rem;">
                        Garment premium dengan harga terjangkau — dibuat untuk remaja hingga wanita dewasa yang ingin tampil percaya diri setiap hari.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('store.catalog') }}" class="st-btn"><i class="bi bi-bag" aria-hidden="true"></i> Shop Now</a>
                        <a href="#kategori" class="st-btn st-btn-outline">Explore Collection</a>
                    </div>
                </div>
                <div class="col-12 col-lg-6 text-center">
                    <div style="max-width: 480px; margin: 0 auto;">
                        @if ($hero)
                            <img src="{{ $hero->url() }}" alt="Banner koleksi {{ config('pos.store.name') }}"
                                 class="img-fluid rounded-4 shadow-sm" style="max-height: 440px; object-fit: cover; width: 100%;">
                        @else
                            <img src="{{ asset('assets/images/banners/hero-elegant-collection.jpg') }}"
                                 alt="Koleksi busana wanita {{ config('pos.store.name') }}"
                                 class="img-fluid rounded-4 shadow-sm" style="max-height: 440px; object-fit: cover; width: 100%;">
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= TRUST BADGES ================= --}}
    <div class="container my-3">
        <div class="st-trust rounded-4 px-3 py-3">
            <div class="row g-2 text-center small">
                <div class="col-6 col-md-3"><i class="bi bi-truck" style="color: var(--rose);" aria-hidden="true"></i> <strong>Free Shipping</strong><div class="text-muted-2">di atas Rp 500 rb</div></div>
                <div class="col-6 col-md-3"><i class="bi bi-shield-check" style="color: var(--rose);" aria-hidden="true"></i> <strong>Secure Payment</strong><div class="text-muted-2">100% aman &amp; mudah</div></div>
                <div class="col-6 col-md-3"><i class="bi bi-arrow-repeat" style="color: var(--rose);" aria-hidden="true"></i> <strong>Easy Returns</strong><div class="text-muted-2">30 hari · klaim mudah</div></div>
                <div class="col-6 col-md-3"><i class="bi bi-headset" style="color: var(--rose);" aria-hidden="true"></i> <strong>24/7 Support</strong><div class="text-muted-2">via WhatsApp</div></div>
            </div>
        </div>
    </div>

    {{-- ================= SHOP BY CATEGORY ================= --}}
    <section class="container py-3" id="kategori">
        <div class="d-flex align-items-center gap-3 mb-3">
            <span class="st-rule"></span>
            <h2 class="st-section mb-0">Shop by Category</h2>
            <span class="st-rule"></span>
        </div>

        <div class="row g-2 g-md-3">
            @php($cats = $categories->take(6))
            @php($demoCategories = [
                ['name' => 'Casual Wear', 'sub' => 'Everyday Comfort', 'img' => 'casual-wear.jpg'],
                ['name' => 'Dresses', 'sub' => 'Grace in Every Stitch', 'img' => 'dresses.jpg'],
                ['name' => 'Party Dresses', 'sub' => 'Stand Out in Style', 'img' => 'party-dresses.jpg'],
                ['name' => 'Workwear', 'sub' => 'Power Dressing', 'img' => 'workwear.jpg'],
                ['name' => 'Blouses & Tops', 'sub' => 'Everyday Essentials', 'img' => 'blouses.jpg'],
                ['name' => 'Outerwear', 'sub' => 'Chic & Warmth', 'img' => 'outerwear.jpg'],
            ])
            @if ($cats->isNotEmpty())
                @foreach ($cats as $idx => $category)
                    @php($fallbackCategoryImg = 'assets/images/categories/' . ($demoCategories[$idx % count($demoCategories)]['img'] ?? 'dresses.jpg'))
                    <div class="col-4 col-sm-4 col-md-2">
                        <a href="{{ route('store.catalog', ['kategori' => $category->id]) }}" class="st-tile position-relative">
                            <img src="{{ $category->photo?->url() ?? asset($fallbackCategoryImg) }}"
                                 class="st-tile-img" alt="Foto kategori {{ $category->name }}"
                                 style="object-fit: cover;">
                            <span class="st-tile-body">
                                <span class="st-tile-name d-block text-truncate">{{ $category->name }}</span>
                            </span>
                        </a>
                    </div>
                @endforeach
            @else
                @foreach ($demoCategories as $demo)
                    <div class="col-4 col-sm-4 col-md-2">
                        <a href="{{ route('store.catalog') }}" class="st-tile position-relative" title="{{ $demo['name'] }}">
                            <img src="{{ asset('assets/images/categories/' . $demo['img']) }}" class="st-tile-img"
                                 alt="Kategori {{ $demo['name'] }}"
                                 style="object-fit: cover;">
                            <span class="st-tile-body">
                                <span class="st-tile-name d-block text-truncate">{{ $demo['name'] }}</span>
                                <span class="st-tile-sub d-block text-truncate">{{ $demo['sub'] }}</span>
                            </span>
                        </a>
                    </div>
                @endforeach
            @endif
        </div>
    </section>

    {{-- ================= SALE BANNER ================= --}}
    @php($promo = $promos->first())
    <section class="container my-4" id="promo">
        <div class="st-promo p-4 p-lg-5 position-relative">
            <div class="row align-items-center">
                <div class="col-12 col-md-7">
                    <p class="st-eyebrow mb-1" style="color:#4a2428;">Spring Collection</p>
                    <div class="st-promo-off">30% OFF</div>
                    <p class="mb-3" style="color:#3a2024;">On selected items — waktu terbatas.</p>
                    <a href="{{ route('store.catalog') }}" class="st-btn"><i class="bi bi-tag" aria-hidden="true"></i> Shop the Sale</a>
                </div>
                <div class="col-12 col-md-5 text-center mt-3 mt-md-0">
                    <img src="{{ $promo ? $promo->url() : asset('assets/images/banners/hero-special-sale.jpg') }}"
                         alt="Banner promosi {{ config('pos.store.name') }}"
                         class="img-fluid shadow-sm" style="max-height: 250px; width: 100%; object-fit: cover; border-radius: .75rem;">
                </div>
            </div>
        </div>
    </section>

    {{-- ================= BEST SELLERS ================= --}}
    <section class="container py-3">
        <div class="d-flex align-items-center gap-3 mb-3">
            <span class="st-rule"></span>
            <h2 class="st-section mb-0">Best Sellers</h2>
            <span class="st-rule"></span>
        </div>

        <div class="row g-2 g-md-3">
            @php($best = $products->take(6))
            @php($demoProducts = [
                ['name' => 'Linen Blazer Dress', 'price' => 1190000, 'hue' => 0, 'rating' => 5, 'reviews' => 128, 'isNew' => true],
                ['name' => 'Floral Midi Dress', 'price' => 899000, 'hue' => 25, 'rating' => 5, 'reviews' => 94, 'isNew' => true],
                ['name' => 'Satin Cami Top', 'price' => 459000, 'hue' => 300, 'rating' => 4, 'reviews' => 119, 'isNew' => false],
                ['name' => 'High Waist Trousers', 'price' => 689000, 'hue' => 205, 'rating' => 5, 'reviews' => 86, 'isNew' => false],
                ['name' => 'Knit Sweater', 'price' => 629000, 'hue' => 30, 'rating' => 4, 'reviews' => 101, 'isNew' => false],
                ['name' => 'Pleated Midi Skirt', 'price' => 549000, 'hue' => 262, 'rating' => 5, 'reviews' => 92, 'isNew' => false],
            ])
            @if ($best->isNotEmpty())
                @foreach ($best as $product)
                    <div class="col-6 col-md-4 col-lg-2">
                        <article class="st-card h-100 overflow-hidden position-relative">
                            @if ($product->created_at && $product->created_at->gt(now()->subDays(30)))
                                <span class="st-new">NEW</span>
                            @endif
                            @auth
                                <form method="POST" action="{{ route('store.wishlist.toggle', $product) }}" class="position-absolute" style="top:.4rem; right:.4rem; z-index:2;">
                                    @csrf
                                    <button type="submit" class="st-wishlist" style="position:static;" aria-label="Simpan {{ $product->name }} ke wishlist"><i class="bi bi-heart" aria-hidden="true"></i></button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="st-wishlist" aria-label="Masuk untuk menyimpan {{ $product->name }} ke wishlist"><i class="bi bi-heart" aria-hidden="true"></i></a>
                            @endauth
                            @if ($product->displayPhoto())
                                <img src="{{ $product->displayPhoto()->url() }}" class="st-product-img" alt="Foto produk {{ $product->name }}" style="object-fit: cover;">
                            @else
                                <img src="{{ asset('assets/images/products/dresses/linen-blazer-dress.jpg') }}" class="st-product-img" alt="Foto produk {{ $product->name }}" style="object-fit: cover;">
                            @endif
                            <div class="p-2">
                                <div class="st-tile-name"><a href="{{ route('store.product', $product) }}" class="text-decoration-none" style="color: var(--ink);">{{ $product->name }}</a></div>
                                <div class="st-price mt-1">{{ rupiah($product->sale_price) }}</div>
                                <div class="small {{ $product->stock > 0 ? 'text-muted-2' : 'text-danger' }}">
                                    @if ($product->stock > 0) Stok: {{ $product->stock }} @else Stok habis @endif
                                </div>
                                @if ($product->variants->isNotEmpty())
                                    <a href="{{ route('store.product', $product) }}" class="st-btn st-btn-soft w-100 justify-content-center mt-2">Pilih Varian</a>
                                @else
                                    <form method="POST" action="{{ route('store.cart.add') }}" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <input type="hidden" name="qty" value="1">
                                        <button type="submit" class="st-btn st-btn-soft w-100 justify-content-center">Add to Cart</button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    </div>
                @endforeach
            @else
                @php($demoImgList = ['linen-blazer-dress.jpg', 'floral-midi-dress.jpg', 'satin-cami-top.jpg', 'high-waist-trousers.jpg', 'knit-sweater.jpg', 'pleated-midi-skirt.jpg'])
                @foreach ($demoProducts as $i => $demo)
                    @php($imgSrc = $demoImgList[$i % count($demoImgList)])
                    <div class="col-6 col-md-4 col-lg-2">
                        <article class="st-card h-100 overflow-hidden position-relative" title="Busana koleksi Tabita Modiste">
                            <span class="st-new">{{ $demo['isNew'] ? 'NEW' : 'HITS' }}</span>
                            <img src="{{ asset('assets/images/products/' . (str_contains($imgSrc, 'dress') ? 'dresses/' : (str_contains($imgSrc, 'top') ? 'blouses/' : (str_contains($imgSrc, 'trousers') ? 'pants/' : (str_contains($imgSrc, 'sweater') ? 'outerwear/' : 'skirts/')))) . $imgSrc) }}"
                                 class="st-product-img" alt="Katalog {{ $demo['name'] }}"
                                 style="object-fit: cover;">
                            <div class="p-2">
                                <div class="st-tile-name">{{ $demo['name'] }}</div>
                                <div class="st-stars">{{ str_repeat('★', $demo['rating']) }}{{ str_repeat('☆', 5 - $demo['rating']) }} <span class="text-muted-2">({{ $demo['reviews'] }})</span></div>
                                <div class="st-price mt-1">{{ rupiah($demo['price']) }}</div>
                                <a href="{{ route('store.catalog') }}" class="st-btn st-btn-soft w-100 justify-content-center mt-2">Add to Cart</a>
                            </div>
                        </article>
                    </div>
                @endforeach
            @endif
        </div>
    </section>

    {{-- ================= FEATURES ================= --}}
    <section class="container my-4">
        <div class="row g-3 text-center small">
            <div class="col-6 col-md-3"><i class="bi bi-shield-check st-feature-icon" aria-hidden="true"></i><div class="fw-semibold">Premium Quality</div><div class="text-muted-2">Handpicked with care</div></div>
            <div class="col-6 col-md-3"><i class="bi bi-stars st-feature-icon" aria-hidden="true"></i><div class="fw-semibold">Trendy Styles</div><div class="text-muted-2">Ahead of fashion</div></div>
            <div class="col-6 col-md-3"><i class="bi bi-heart st-feature-icon" aria-hidden="true"></i><div class="fw-semibold">Loved by Thousands</div><div class="text-muted-2">4.8 · rata-rata rating</div></div>
            <div class="col-6 col-md-3"><i class="bi bi-globe2 st-feature-icon" aria-hidden="true"></i><div class="fw-semibold">Lokal &amp; Berkelanjutan</div><div class="text-muted-2">Lebih baik buat bumi</div></div>
        </div>
    </section>

    {{-- ================= INSTAGRAM ================= --}}
    <section class="container mb-4" id="koleksi">
        <div class="st-insta-wrap">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                <h2 class="st-section mb-0" style="font-size: 1.1rem;">Follow Us <span class="st-script" style="color: var(--rose); font-size: 1.3rem;">@tabitamodiste</span></h2>
                <a href="#" class="small text-decoration-none" style="color: var(--rose);">Lihat semua foto <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="row g-2 g-md-3">
                @php($insta = $collections->take(6))
                @if ($insta->isNotEmpty())
                    @foreach ($insta as $item)
                        <div class="col-4 col-md-2">
                            <img src="{{ $item->url() }}" class="img-fluid w-100" alt="Foto koleksi {{ config('pos.store.name') }}"
                                 style="aspect-ratio: 1; object-fit: cover; border-radius: .5rem;">
                        </div>
                    @endforeach
                @else
                    @foreach ([0, 40, 120, 205, 262, 300] as $hue)
                        <div class="col-4 col-md-2">
                            <img src="{{ asset('images/store/garment.svg') }}" class="img-fluid w-100" alt="Contoh feed Instagram {{ $loop->iteration }}"
                                 style="aspect-ratio: 1; object-fit: cover; border-radius: .5rem; mix-blend-mode: multiply; background: linear-gradient(160deg, #f8ece6, #ecd8cf); filter: hue-rotate({{ $hue }}deg) saturate(.85);">
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    {{-- ================= NEWSLETTER ================= --}}
    <section class="container mb-5">
        <div class="st-newsletter p-4 p-lg-5 text-center">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <h2 class="st-section mb-1" style="color: #fdf3ea;">Be the first to know</h2>
                    <p class="small mb-3" style="color: #c8b4a6;">Subscribe untuk penawaran eksklusif, produk baru, dan update toko. Kami hormati privasi Anda.</p>
                    @if (session('newsletter_success'))
                        <div class="alert alert-success py-2 small">{{ session('newsletter_success') }}</div>
                    @endif
                    @if (session('newsletter_error'))
                        <div class="alert alert-danger py-2 small">{{ session('newsletter_error') }}</div>
                    @endif
                    <form method="POST" action="{{ route('store.subscribe') }}" class="d-flex flex-column flex-sm-row gap-2">
                        @csrf
                        <label class="visually-hidden" for="newsletter-email">Email</label>
                        <input type="email" id="newsletter-email" class="form-control" name="email"
                               value="{{ old('email') }}" placeholder="Enter your email" required maxlength="150"
                               aria-label="Alamat email untuk newsletter">
                        <button type="submit" class="btn btn-sm px-4">Subscribe</button>
                    </form>
                    <p class="small mt-2 mb-0" style="color: #a89483;">
                        Dengan berlangganan, Anda menyetujui penerimaan email promo dari Tabita Modiste.
                        Anda dapat berhenti berlangganan kapan pun.
                    </p>
                    @error('email')
                        <p class="small mt-2 mb-0" style="color: #ffb4b4;">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </section>
@endsection
