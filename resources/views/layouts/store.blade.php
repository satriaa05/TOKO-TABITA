@php
    $store = store_profile();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Beranda') &middot; {{ $store->name() }}</title>

    {{-- Google Fonts: Playfair Display (heading) + Poppins (body) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root { --rose:#bd777c; --rose-dark:#a65e63; --blush:#e8c3c1; --blush2:#f9e6e6; --cream:#fff9f7; --ink:#242020; --muted:#77716f; --card:#ffffff; --line:#ead7d1; }

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
        .st-btn { background: var(--rose); color: #fff; border: 0; font-size: .7rem; letter-spacing: .08em; text-transform: uppercase; font-weight: 700; border-radius: .3rem; padding: .45rem .8rem; display: inline-flex; align-items: center; gap: .35rem; text-decoration: none; transition: background .15s ease; }
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

        /* ===== Footer toko ===== */
        .st-footer { background: #241c18; color: #e8dcd0; padding-bottom: 4.5rem; }
        .st-footer a { color: #b9a795; font-size: .78rem; text-decoration: none; transition: color .15s ease; }
        .st-footer a:hover { color: #fff; }
        .st-footer-title { font-family: Georgia, "Times New Roman", serif; color: #fff; font-size: .82rem; letter-spacing: .12em; text-transform: uppercase; }
        .st-footer-brand-name { font-family: Georgia, "Times New Roman", serif; font-weight: 700; color: #fff; font-size: 1.35rem; line-height: 1.1; }
        .st-footer-brand-tag { color: #d9c9bb; font-size: .8rem; letter-spacing: .03em; }
        .st-footer-muted { color: #a08f85; font-size: .78rem; line-height: 1.7; }
        .st-footer-links { display: flex; flex-direction: column; gap: .45rem; }
        .st-footer-links a { display: inline-flex; align-items: center; }
        .st-footer-links a:hover { padding-left: 2px; }
        .st-footer-contact { color: #bfaea0; }
        .st-footer-contact li { display: flex; align-items: flex-start; gap: .5rem; font-size: .78rem; line-height: 1.5; }
        .st-footer-contact li > i { color: var(--blush); margin-top: .12rem; flex-shrink: 0; }
        .st-footer-social { width: 40px; height: 40px; border-radius: 999px; border: 1px solid rgba(232,220,208,.28); display: inline-flex; align-items: center; justify-content: center; color: #e8dcd0; font-size: 1.05rem; transition: background .18s ease, color .18s ease, border-color .18s ease, transform .18s ease; }
        .st-footer-social:hover, .st-footer-social:focus-visible { background: var(--rose); border-color: var(--rose); color: #fff; transform: translateY(-2px); }
        .st-footer-social.is-disabled { opacity: .45; cursor: not-allowed; }
        .st-footer-social.is-disabled:hover { background: transparent; border-color: rgba(232,220,208,.28); color: #e8dcd0; transform: none; }
        .st-footer-bottom { border-top: 1px solid rgba(255,255,255,.08); }
        .st-footer-copy { color: #8f8178; font-size: .75rem; }
        .st-pay-list { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem; }
        .st-pay-item { background: #fff; border-radius: .35rem; height: 32px; min-width: 50px; padding: 0 .55rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,.18); }
        .st-pay-item img { display: block; height: 20px; width: auto; max-width: 62px; object-fit: contain; }

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
        .st-wpp { position: fixed; right: 1.1rem; bottom: 1.1rem; z-index: 1030; width: 52px; height: 52px; border-radius: 999px; background: #25d366; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; box-shadow: 0 6px 18px rgba(0,0,0,.28); text-decoration: none; transition: transform .18s ease, filter .18s ease; }
        .st-wpp::after { content: "Chat dengan Admin"; position: absolute; right: calc(100% + .6rem); top: 50%; transform: translateY(-50%) translateX(4px); background: #241c18; color: #fff9f7; font-size: .72rem; font-weight: 600; white-space: nowrap; padding: .35rem .6rem; border-radius: .35rem; opacity: 0; pointer-events: none; transition: opacity .16s ease, transform .16s ease; }
        .st-wpp:hover, .st-wpp:focus-visible { color: #fff; filter: brightness(.96); transform: translateY(-2px); }
        .st-wpp:hover::after, .st-wpp:focus-visible::after { opacity: 1; transform: translateY(-50%) translateX(0); }
        @media (max-width: 575.98px) { .store-section-title { font-size: 1.3rem; } .st-wpp::after { display: none; } }
        @media (prefers-reduced-motion: reduce) { .store-card, .st-wpp, .st-footer-social { transition: none; } .store-card:hover, .st-wpp:hover { transform: none; } }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>

    @stack('styles')
</head>
<body style="background: var(--cream); color: var(--ink); font-family: 'Segoe UI', system-ui, sans-serif;">

    @php
        $unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    @endphp

    {{-- Header / navigation --}}
    <header style="background: var(--card); border-bottom: 1px solid var(--line);" class="sticky-top">
        <div class="container py-2 d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                @php
                    $logo = \App\Models\Media::siteAsset('logo');
                @endphp
                @if ($logo)
                    <img src="{{ $logo->url() }}" alt="Logo {{ $store->name() }}" style="height: 44px; width: auto; object-fit: contain;">
                @else
                    <span>
                        <span class="st-logo-main">Women's
                            <i class="bi bi-flower1" style="font-size: .9rem; color: var(--rose); vertical-align: 2px;" aria-hidden="true"></i>
                        </span>
                        <span class="st-logo-tag d-block">{{ $store->tagline() }}</span>
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
                    <a href="{{ route('store.notifications.index') }}" class="st-icon-btn" aria-label="Notifikasi">
                        <i class="bi bi-bell fs-5"></i>
                        @if ($unreadNotifications > 0)<span class="st-badge">{{ $unreadNotifications }}</span>@endif
                    </a>
                    <a href="{{ route('store.orders.index') }}" class="st-icon-btn" aria-label="Riwayat pesanan"><i class="bi bi-bag-check fs-5"></i></a>
                    <a href="{{ route('store.wishlist') }}" class="st-icon-btn" aria-label="Wishlist"><i class="bi bi-heart fs-5"></i></a>
                @else
                    <a href="{{ route('login') }}" class="st-icon-btn" aria-label="Akun"><i class="bi bi-person fs-5"></i></a>
                    <a href="{{ route('login') }}" class="st-icon-btn" aria-label="Wishlist"><i class="bi bi-heart fs-5"></i></a>
                @endif
                @php
                    $cartCount = collect(session('pos_cart')['items'] ?? [])->sum('qty');
                @endphp
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
    @php
        $instagramUrl = $store->instagramUrl();
        $waContactUrl = $store->whatsappUrl('Halo '.$store->name().', saya ingin bertanya.');
        $payments = $store->paymentMethods();
        $storeAddress = $store->address();
        $operatingHours = $store->operatingHours();
    @endphp

    <footer class="st-footer">
        <div class="container py-5">
            <div class="row g-4 g-lg-5">
                {{-- Identitas toko --}}
                <div class="col-12 col-lg-4">
                    <div class="st-footer-brand-name mb-1">
                        Women's
                        <i class="bi bi-flower1" style="font-size: .8rem; color: var(--blush); vertical-align: 2px;" aria-hidden="true"></i>
                    </div>
                    <div class="st-footer-brand-tag mb-2">{{ $store->tagline() }}</div>
                    <p class="st-footer-muted mb-3">{{ $store->description() }}</p>

                    @if ($instagramUrl)
                        <a href="{{ $instagramUrl }}"
                           class="st-footer-social"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="Buka Instagram resmi {{ $store->name() }}"
                           title="Instagram {{ $store->name() }}">
                            <i class="bi bi-instagram" aria-hidden="true"></i>
                        </a>
                    @else
                        <span class="st-footer-social is-disabled"
                              role="img"
                              aria-label="Instagram belum dikonfigurasi"
                              title="Instagram belum dikonfigurasi oleh Owner">
                            <i class="bi bi-instagram" aria-hidden="true"></i>
                        </span>
                    @endif
                </div>

                {{-- Shop --}}
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="st-footer-title mb-3">Shop</div>
                    <div class="st-footer-links">
                        <a href="{{ route('store.catalog') }}">All Products</a>
                        <a href="{{ route('home') }}#kategori">Collections</a>
                        <a href="{{ route('home') }}#promo">Sale</a>
                    </div>
                </div>

                {{-- Customer Care --}}
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="st-footer-title mb-3">Customer Care</div>
                    <div class="st-footer-links">
                        <a href="{{ route('page.shipping') }}">Pengiriman &amp; Pengembalian</a>
                        <a href="{{ route('page.size-guide') }}">Size Guide</a>
                    </div>
                </div>

                {{-- About Us --}}
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="st-footer-title mb-3">About Us</div>
                    <div class="st-footer-links">
                        <a href="{{ route('page.about') }}">Our Story</a>
                        <a href="{{ route('page.about') }}">Tentang Kami</a>
                    </div>
                </div>

                {{-- Contact Us --}}
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="st-footer-title mb-3">Contact Us</div>
                    <ul class="list-unstyled st-footer-contact d-flex flex-column gap-2 mb-0">
                        <li>
                            <i class="bi bi-whatsapp" aria-hidden="true"></i>
                            @if ($waContactUrl)
                                <a href="{{ $waContactUrl }}" target="_blank" rel="noopener noreferrer">
                                    {{ $store->whatsappDisplay() }}
                                </a>
                            @else
                                <span>WhatsApp segera diaktifkan</span>
                            @endif
                        </li>
                        <li>
                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                            <span>
                                {{ $store->location() }}
                                @if ($storeAddress)
                                    <br>{{ $storeAddress }}
                                @endif
                            </span>
                        </li>
                        @if ($operatingHours)
                            <li>
                                <i class="bi bi-clock" aria-hidden="true"></i>
                                <span>{{ $operatingHours }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mt-4 pt-4 st-footer-bottom">
                <div class="st-footer-copy">
                    &copy; {{ date('Y') }} {{ $store->name() }} &middot; All rights reserved.
                </div>

                @if ($payments !== [])
                    <div class="st-pay-list" aria-label="Metode pembayaran yang tersedia">
                        @foreach ($payments as $payment)
                            <span class="st-pay-item"
                                  title="{{ $payment['label'] }}{{ $payment['hint'] !== '' ? ' — '.$payment['hint'] : '' }}">
                                <img src="{{ $payment['logo'] }}" alt="{{ $payment['label'] }}">
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </footer>

    {{-- Tombol WhatsApp mengambang --}}
    @php
        $waFloatUrl = $store->whatsappUrl('Halo '.$store->name().', saya ingin bertanya.');
    @endphp
    @if ($waFloatUrl)
        <a class="st-wpp"
           href="{{ $waFloatUrl }}"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="Chat dengan Admin via WhatsApp"
           title="Chat dengan Admin">
            <i class="bi bi-whatsapp" aria-hidden="true"></i>
        </a>
    @endif

    @stack('scripts')
</body>
</html>
