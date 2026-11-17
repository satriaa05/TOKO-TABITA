@extends('layouts.store')

@section('title', 'Tentang Kami')

@section('content')
    <section class="container py-4 py-lg-5">
        <nav class="st-breadcrumb mb-3" aria-label="Jejak navigasi">
            <a href="{{ route('home') }}">Home</a>
            <span aria-hidden="true">&nbsp;/&nbsp;</span>
            <span aria-current="page">Tentang</span>
        </nav>

        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-12 col-lg-6">
                <p class="st-eyebrow mb-1">Sejak 2010</p>
                <h1 class="store-section-title mb-3">Tentang Tabita Modiste</h1>
                <p style="color: var(--muted); font-size: .98rem;">
                    Tabita Modiste adalah butik garment wanita yang lahir dari satu keyakinan sederhana:
                    setiap wanita berhak tampil percaya diri dengan pakaian yang pas, rapi, dan elegan —
                    tanpa harus mengorbankan kenyamanan.
                </p>
                <p style="color: var(--muted); font-size: .98rem;">
                    Kami mengerjakan setiap potongan dengan bahan pilihan dan jahitan yang teliti,
                    mulai dari dress, atasan, bawahan, hingga hijab. Ukuran dibuat mengikuti standar
                    lokal Indonesia, dan setiap produk tersedia dalam pilihan ukuran serta warna.
                </p>
                <p style="color: var(--muted); font-size: .98rem;">
                    Dari butik kecil di Bandung, kini kami melayani pelanggan dari berbagai kota —
                    dengan layanan konsultasi ukuran via WhatsApp dan pengiriman ke seluruh Indonesia.
                </p>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="{{ route('store.catalog') }}" class="st-btn"><i class="bi bi-bag" aria-hidden="true"></i> Lihat Koleksi</a>
                    @if (config('pos.store.phone'))
                        @php($wa = '62'.ltrim(preg_replace('/\D+/', '', (string) config('pos.store.phone')), '0'))
                        <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Halo Tabita Modiste, saya ingin bertanya tentang produk Anda.') }}"
                           target="_blank" rel="noopener" class="st-btn st-btn-outline">
                            <i class="bi bi-whatsapp" aria-hidden="true"></i> Tanya via WhatsApp
                        </a>
                    @endif
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="st-card p-4 p-lg-5">
                    <h2 class="h5 mb-3">Mengapa Tabita Modiste?</h2>
                    <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                        <li class="d-flex gap-2"><i class="bi bi-gem st-feature-icon fs-5" aria-hidden="true"></i><span><strong>Garment premium</strong> — bahan pilihan dengan jahitan yang rapi dan detail yang elegan.</span></li>
                        <li class="d-flex gap-2"><i class="bi bi-rulers st-feature-icon fs-5" aria-hidden="true"></i><span><strong>Ukuran lokal</strong> — size chart S–XL standar Indonesia, tersedia dalam beberapa warna.</span></li>
                        <li class="d-flex gap-2"><i class="bi bi-chat-heart st-feature-icon fs-5" aria-hidden="true"></i><span><strong>Layanan personal</strong> — konsultasi ukuran &amp; model langsung via WhatsApp.</span></li>
                        <li class="d-flex gap-2"><i class="bi bi-arrow-repeat st-feature-icon fs-5" aria-hidden="true"></i><span><strong>30-day returns</strong> — pengembalian mudah bila ukuran tidak sesuai.</span></li>
                    </ul>
                </div>

                <div class="st-card p-4 mt-3">
                    <h2 class="h6 mb-2">Kunjungi &amp; Hubungi</h2>
                    <ul class="list-unstyled small mb-0" style="color: var(--muted);">
                        <li><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ config('pos.store.address') ?: 'Bandung, Jawa Barat' }}</li>
                        @if (config('pos.store.phone'))
                            <li><i class="bi bi-whatsapp" aria-hidden="true"></i> {{ config('pos.store.phone') }}</li>
                        @endif
                        <li><i class="bi bi-clock" aria-hidden="true"></i> Setiap hari, 09.00–21.00 WIB</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection
