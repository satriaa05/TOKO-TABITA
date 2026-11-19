@extends('layouts.store')

@section('title', 'Tentang Kami')

@section('content')
    @php
        $store = store_profile();

        $eyebrow = $store->get('about_eyebrow', 'Sejak 2010');
        $title = $store->get('about_title', 'Tentang Tabita Modiste');
        $intro = $store->get(
            'about_intro',
            'Tabita Modiste adalah butik garment wanita yang lahir dari satu keyakinan sederhana: setiap wanita berhak tampil percaya diri dengan pakaian yang pas, rapi, dan elegan — tanpa harus mengorbankan kenyamanan.'
        );
        $story = $store->get(
            'about_story',
            'Kami mengerjakan setiap potongan dengan bahan pilihan dan jahitan yang teliti, mulai dari dress, atasan, bawahan, hingga hijab. Ukuran dibuat mengikuti standar lokal Indonesia, dan setiap produk tersedia dalam pilihan ukuran serta warna.'
        );
        $closure = $store->get(
            'about_closure',
            'Dari butik kecil di Semarang, kini kami melayani pelanggan dari berbagai kota — dengan layanan konsultasi ukuran via WhatsApp dan pengiriman ke seluruh Indonesia.'
        );
        $featuresTitle = $store->get('about_features_title', 'Mengapa Tabita Modiste?');

        $defaultFeatures = [
            ['Garment premium', 'Bahan pilihan dengan jahitan yang rapi dan detail yang elegan.'],
            ['Ukuran lokal', 'Size chart S–XL standar Indonesia, tersedia dalam beberapa warna.'],
            ['Layanan personal', 'Konsultasi ukuran & model langsung via WhatsApp.'],
            ['30-day returns', 'Pengembalian mudah bila ukuran tidak sesuai.'],
        ];

        $features = collect(explode("\n", (string) $store->get('about_features', '')))
            ->map(fn ($line) => array_map('trim', explode('|', $line, 2)))
            ->filter(fn ($parts) => count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '')
            ->values()
            ->all();

        if ($features === []) {
            $features = $defaultFeatures;
        }

        $aboutWa = $store->whatsappUrl('Halo '.$store->name().', saya ingin bertanya tentang produk Anda.');
    @endphp

    <section class="container py-4 py-lg-5">
        <nav class="st-breadcrumb mb-3" aria-label="Jejak navigasi">
            <a href="{{ route('home') }}">Home</a>
            <span aria-hidden="true">&nbsp;/&nbsp;</span>
            <span aria-current="page">Tentang</span>
        </nav>

        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-12 col-lg-6">
                <p class="st-eyebrow mb-1">{{ $eyebrow }}</p>
                <h1 class="store-section-title mb-3">{{ $title }}</h1>
                <p style="color: var(--muted); font-size: .98rem;">{{ $intro }}</p>
                <p style="color: var(--muted); font-size: .98rem;">{{ $story }}</p>
                <p style="color: var(--muted); font-size: .98rem;">{{ $closure }}</p>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="{{ route('store.catalog') }}" class="st-btn">
                        <i class="bi bi-bag" aria-hidden="true"></i> Lihat Koleksi
                    </a>
                    @if ($aboutWa)
                        <a href="{{ $aboutWa }}" target="_blank" rel="noopener noreferrer" class="st-btn st-btn-outline">
                            <i class="bi bi-whatsapp" aria-hidden="true"></i> Tanya via WhatsApp
                        </a>
                    @endif
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="st-card p-4 p-lg-5">
                    <h2 class="h5 mb-3">{{ $featuresTitle }}</h2>
                    <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                        @foreach ($features as $feature)
                            <li class="d-flex gap-2">
                                <i class="bi bi-gem st-feature-icon fs-5" aria-hidden="true"></i>
                                <span><strong>{{ $feature[0] }}</strong> — {{ $feature[1] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="st-card p-4 mt-3">
                    <h2 class="h6 mb-2">Kunjungi &amp; Hubungi</h2>
                    <ul class="list-unstyled small mb-0" style="color: var(--muted);">
                        <li>
                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                            {{ $store->location() }}
                            @if ($store->address())
                                <span class="d-block ms-4">{{ $store->address() }}</span>
                            @endif
                        </li>
                        @if ($store->whatsappDisplay())
                            <li>
                                <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                @if ($aboutWa)
                                    <a href="{{ $aboutWa }}" target="_blank" rel="noopener noreferrer">{{ $store->whatsappDisplay() }}</a>
                                @else
                                    {{ $store->whatsappDisplay() }}
                                @endif
                            </li>
                        @endif
                        @if ($store->operatingHours())
                            <li><i class="bi bi-clock" aria-hidden="true"></i> {{ $store->operatingHours() }}</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection
