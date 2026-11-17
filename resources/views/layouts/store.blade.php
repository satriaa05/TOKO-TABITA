<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Beranda') &middot; {{ config('pos.store.name') }}</title>

    {{-- Google Fonts: Playfair Display (heading) + Poppins (body) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root { --rose:#bd777c; --rose-dark:#a65e63; --blush:#e8c3c1; --blush2:#f9e6e6; --cream:#fff9f7; --ink:#242020; --muted:#77716f; --card:#ffffff; --line:#ead7d1; }

        .st-topbar { background: var(--rose); color: #fdf6f0; font-size: .72rem; }
        .st-topbar a { color: #fdf6f0; opacity: .85; }
        .st-topbar a:hover { opacity: 1; }
        .st-logo-main { font-family: Georgia, "Times New Roman", serif; font-weight: 700; font-size: 1.55rem; line-height: 1; color: var(--ink); }
        .st-logo-tag { font-size: .6rem; letter-spacing: .22em; text-transform: uppercase; color: var(--muted); }
        .st-nav a { font-size: .75rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--ink); }
        .st-nav a:hover { color: var(--rose); }
        .st-icon-btn { position: relative; color: var(--ink); }
        .st-badge { position: absolute; top: -6px; right: -8px; background: var(--rose); color: #fff; font-size: .65rem; font-weight: 700; min-width: 18px; height: 18px; border-radius: 999px; display: flex; align-items: center; justify-content: center; padding: 0 5px; }
        .st-section { font-family: Georgia, "Times New Roman", serif; font-size: 1.35rem; color: var(--ink); }
        .st-rule { height: 1px; background: var(--ink); opacity: .25; flex: 1; }
        .st-card { background: var(--card); border: 1px solid var(--line); border-radius: .75rem; }
        .st-img-tile { display: block; width: 100%; aspect-ratio: .72; object-fit: contain; object-position: center bottom; }
        .st-tile-name { font-family: Georgia, "Times New Roman", serif; font-size: .78rem; color: var(--ink); }
        .st-tile-sub { font-size: .66rem; color: var(--muted); }
        .st-product-img { width: 100%; aspect-ratio: .78; object-fit: cover; }
        .st-price { color: var(--rose); font-weight: 700; font-size: .95rem; }
        .st-btn { background: var(--rose); color: #fff; border: 0; font-size: .7rem; letter-spacing: .08em; text-transform: uppercase; font-weight: 700; border-radius: .3rem; padding: .45rem .8rem; display: inline-flex; align-items: center; gap: .35rem; transition: background .15s ease; }
        .st-btn:hover { background: var(--rose-dark); color: #fff; }
        .st-btn-soft { background: var(--blush2); color: var(--rose-dark); }
        .st-btn-soft:hover { background: var(--blush); color: var(--rose-dark); }
        .st-btn-outline { background: transparent; color: var(--ink); border: 1px solid rgba(46,38,34,.3); }
        .st-btn-outline:hover { border-color: var(--ink); color: var(--ink); }
        .st-new { position: absolute; top: .4rem; left: .4rem; background: #c14b5a; color: #fff; font-size: .62rem; font-weight: 700; letter-spacing: .06em; padding: .18rem .4rem; border-radius: .2rem; z-index: 2; }
        .st-wishlist { position: absolute; top: .4rem; right: .4rem; background: #fff; border: 1px solid var(--line); width: 28px; height: 28px; border-radius: 999px; display: flex; align-items: center; justify-content: center; color: var(--ink); z-index: 2; }
        .st-wishlist:hover { color: var(--rose); border-color: var(--rose); }
        .st-trust { background: var(--cream); border: 1px solid var(--line); }
        .st-stats { border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
        .st-insta img { aspect-ratio: 1; object-fit: cover; border-radius: .5rem; }
        .st-footer { background: #241c18; color: #e8dcd0; }
        .st-footer-title { font-family: Georgia, "Times New Roman", serif; color: #fff; font-size: .9rem; }
        .st-footer a { color: #b9a795; font-size: .78rem; }
        .st-footer a:hover { color: #fff; }
        .st-pay { background: rgba(255,255,255,.14); color: #fdf6f0; font-size: .62rem; font-weight: 700; border-radius: .2rem; padding: .2rem .45rem; letter-spacing: .05em; }

        /* ===== Komponen toko (katalog / detail / keranjang / checkout) ===== */
        .store-section-title { font-family: Georgia, "Times New Roman", serif; font-size: 1.6rem; color: var(--ink); letter-spacing: -0.01em; }
        .st-breadcrumb { font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        .st-breadcrumb a { color: var(--muted); text-decoration: none; }
        .st-breadcrumb a:hover { color: var(--rose); }
        .store-card { background: var(--card); border: 1px solid var(--line); border-radius: .75rem; overflow: hidden; transition: transform .18s ease, box-shadow .18s ease; }
        .store-card:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(46, 38, 34, .14); }
        .store-placeholder { background: linear-gradient(165deg, #f9efe9, #eddcd3); }
        .store-price { color: var(--rose); font-weight: 700; font-size: 1rem; letter-spacing: .01em; }
        .st-price-lg { font-family: Georgia, "Times New Roman", serif; color: var(--rose); font-weight: 700; font-size: 1.5rem; }
        .st-size-table thead th { background: #fbf1ec; font-size: .78rem; letter-spacing: .03em; color: var(--muted); text-transform: none; }
        .st-cat-chips { display: flex; flex-wrap: wrap; gap: .45rem; }
        .st-chip { display: inline-flex; align-items: center; gap: .3rem; border: 1px solid var(--line); background: #fff; color: var(--ink); font-size: .75rem; font-weight: 600; border-radius: 999px; padding: .32rem .85rem; text-decoration: none; transition: all .15s ease; }
        .st-chip:hover { border-color: var(--rose); color: var(--rose-dark); }
        .st-chip.active { background: var(--rose); border-color: var(--rose); color: #fff; }
        .st-catalog-bar { background: #fff; border: 1px solid var(--line); border-radius: .75rem; padding: .6rem .75rem; }
        .st-count { font-size: .78rem; color: var(--muted); }
        .st-empty { border: 1px dashed var(--line); border-radius: .75rem; background: #fffdfc; padding: 2.5rem 1.5rem; text-align: center; }
        .st-wpp { position: fixed; right: 1.1rem; bottom: 1.1rem; z-index: 1030; width: 52px; height: 52px; border-radius: 999px; background: #25d366; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; box-shadow: 0 6px 18px rgba(0,0,0,.28); text-decoration: none; }
        .st-wpp:hover { color:#fff; filter: brightness(.95); }
        @media (max-width: 575.98px) { .store-section-title { font-size: 1.3rem; } }
        @media (prefers-reduced-motion: reduce) { .store-card { transition: none; } .store-card:hover { transform: none; } }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>

    @stack('styles')
</head>
<body style="background: var(--cream); color: var(--ink); font-family: 'Segoe UI', system-ui, sans-serif;">

    {{-- Top bar --}}
    <div class="st-topbar d-none d-md-block">
        <div class="container py-1 d-flex justify-content-between align-items-center">
            <a href="{{ route('home') }}" class="text-decoration-none"><i class="bi bi-truck" aria-hidden="true"></i> Gratis ongkir &gt; Rp 500 rb</a>
            <span><i class="bi bi-arrow-repeat" aria-hidden="true"></i> 30-Day easy returns &nbsp;·&nbsp; <i class="bi bi-headset" aria-hidden="true"></i> CS 24/7</span>
            <span class="d-flex gap-3" aria-label="Media sosial">
                <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                <a href="#" aria-label="Pinterest"><i class="bi bi-pinterest"></i></a>
                <a href="#" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
            </span>
        </div>
    </div>

    {{-- Header / navigation --}}
    <header style="background: var(--card); border-bottom: 1px solid var(--line);" class="sticky-top">
        <div class="container py-2 d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                @php($logo = \App\Models\Media::siteAsset('logo'))
                @if ($logo)
                    <img src="{{ $logo->url() }}" alt="Logo {{ config('pos.store.name') }}" style="height: 44px; width: auto; object-fit: contain;">
                @else
                    <span>
                        <span class="st-logo-main">Women's
                            <i class="bi bi-flower1" style="font-size: .9rem; color: var(--rose); vertical-align: 2px;" aria-hidden="true"></i>
                        </span>
                        <span class="st-logo-tag d-block">Fashion for Every You</span>
                    </span>
                @endif
            </a>

            <nav class="st-nav d-none d-lg-flex align-items-center gap-4" aria-label="Navigasi utama">
                <a href="{{ route('home') }}" class="text-decoration-none">Home</a>
                <a href="{{ route('store.catalog') }}" class="text-decoration-none">Shop</a>
                <a href="{{ route('home') }}#kategori" class="text-decoration-none">Collections</a>
                <a href="{{ route('home') }}#promo" class="text-decoration-none">Sale</a>
                <a href="{{ route('page.about') }}" class="text-decoration-none">Tentang</a>
            </nav>

            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('store.catalog') }}" class="st-icon-btn" aria-label="Cari produk"><i class="bi bi-search fs-5"></i></a>
                @if (auth()->check())
                    <a href="{{ route('store.orders.index') }}" class="st-icon-btn" aria-label="Riwayat pesanan"><i class="bi bi-bag-check fs-5"></i></a>
                    <a href="{{ route('store.wishlist') }}" class="st-icon-btn" aria-label="Wishlist"><i class="bi bi-heart fs-5"></i></a>
                @else
                    <a href="{{ route('login') }}" class="st-icon-btn" aria-label="Akun"><i class="bi bi-person fs-5"></i></a>
                    <a href="{{ route('login') }}" class="st-icon-btn" aria-label="Wishlist"><i class="bi bi-heart fs-5"></i></a>
                @endif
                @php($cartCount = collect(session('pos_cart')['items'] ?? [])->sum('qty'))
                <a href="{{ route('store.cart') }}" class="st-icon-btn" aria-label="Keranjang ({{ $cartCount }} item)">
                    <i class="bi bi-bag fs-5"></i>
                    @if ($cartCount > 0)<span class="st-badge">{{ $cartCount }}</span>@endif
                </a>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="st-footer">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-12 col-lg-3">
                    <div class="st-logo-main" style="color:#fff; font-size:1.3rem;">Women's</div>
                    <p class="small mb-2" style="color:#a08f85;">Fashion for Every You. Garment premium, harga terjangkau sejak 2010.</p>
                    <div class="d-flex gap-2">
                        <a href="#" class="text-decoration-none" aria-label="Facebook"><i class="bi bi-facebook" style="color:#bfaea0;"></i></a>
                        <a href="#" class="text-decoration-none" aria-label="Instagram"><i class="bi bi-instagram" style="color:#bfaea0;"></i></a>
                        <a href="#" class="text-decoration-none" aria-label="Pinterest"><i class="bi bi-pinterest" style="color:#bfaea0;"></i></a>
                        <a href="#" class="text-decoration-none" aria-label="TikTok"><i class="bi bi-tiktok" style="color:#bfaea0;"></i></a>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="st-footer-title mb-2">SHOP</div>
                    <ul class="list-unstyled small d-flex flex-column gap-1">
                        <li><a href="{{ route('store.catalog') }}" class="text-decoration-none">All Products</a></li>
                        <li><a href="{{ route('home') }}#kategori" class="text-decoration-none">Collections</a></li>
                        <li><a href="{{ route('home') }}#promo" class="text-decoration-none">Sale</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="st-footer-title mb-2">CUSTOMER CARE</div>
                    <ul class="list-unstyled small d-flex flex-column gap-1">
                        <li><a href="{{ route('page.shipping') }}" class="text-decoration-none">Pengiriman &amp; Pengembalian</a></li>
                        <li><a href="{{ route('page.size-guide') }}" class="text-decoration-none">Size Guide</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="st-footer-title mb-2">ABOUT US</div>
                    <ul class="list-unstyled small d-flex flex-column gap-1">
                        <li><a href="{{ route('page.about') }}" class="text-decoration-none">Our Story</a></li>
                        <li><a href="{{ route('page.about') }}" class="text-decoration-none">Tentang Kami</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="st-footer-title mb-2">CONTACT US</div>
                    <ul class="list-unstyled small d-flex flex-column gap-2" style="color:#bfaea0;">
                        @if (config('pos.store.phone'))
                            <li><i class="bi bi-whatsapp" aria-hidden="true"></i> {{ config('pos.store.phone') }}</li>
                        @else
                            <li><i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp: segera diaktifkan</li>
                        @endif
                        @if (config('pos.store.address'))
                            <li><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ config('pos.store.address') }}</li>
                        @else
                            <li><i class="bi bi-geo-alt" aria-hidden="true"></i> Bandung, Jawa Barat</li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,.08);">
                <div class="small" style="color:#8f8178;">&copy; {{ date('Y') }} {{ config('pos.store.name') }} · All rights reserved.</div>
                <div class="d-flex gap-2">
                    <span class="st-pay">VISA</span>
                    <span class="st-pay">MASTERCARD</span>
                    <span class="st-pay">QRIS</span>
                    <span class="st-pay">TRANSFER</span>
                </div>
            </div>
        </div>
    </footer>

    @php($waFloat = (string) config('pos.store.phone'))
    @if ($waFloat !== '')
        @php($waFloat = '62'.ltrim(preg_replace('/\D+/', '', $waFloat), '0'))
        <a class="st-wpp" href="https://wa.me/{{ $waFloat }}?text={{ rawurlencode('Halo Tabita Modiste, saya ingin bertanya.') }}"
           target="_blank" rel="noopener" aria-label="Chat WhatsApp Tabita Modiste">
            <i class="bi bi-whatsapp" aria-hidden="true"></i>
        </a>
    @endif

    @stack('scripts')
</body>
</html>
