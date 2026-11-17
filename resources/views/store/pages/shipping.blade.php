@extends('layouts.store')

@section('title', 'Pengiriman & Pengembalian')

@section('content')
    <section class="container py-4 py-lg-5" style="max-width: 860px;">
        <nav class="st-breadcrumb mb-3" aria-label="Jejak navigasi">
            <a href="{{ route('home') }}">Home</a>
            <span aria-hidden="true">&nbsp;/&nbsp;</span>
            <span aria-current="page">Pengiriman &amp; Pengembalian</span>
        </nav>

        <h1 class="store-section-title mb-4">Pengiriman &amp; Pengembalian</h1>

        <div class="st-card p-4 p-lg-5 mb-3">
            <h2 class="h5"><i class="bi bi-truck" style="color: var(--rose);" aria-hidden="true"></i> Pengiriman</h2>
            <ul class="mt-2 mb-0" style="color: var(--muted);">
                <li><strong>Gratis ongkir</strong> untuk pembelian di atas Rp 500.000.</li>
                <li>Pengiriman dalam kota Bandung 1–2 hari kerja; luar kota 2–6 hari kerja (tergantung kurir &amp; tujuan).</li>
                <li>Pesanan diproses setelah bukti pembayaran (transfer) dikonfirmasi admin. Anda akan menerima informasi resi via WhatsApp.</li>
                <li>Biaya pengiriman untuk pesanan di bawah Rp 500.000 akan dikonfirmasikan admin via WhatsApp sebelum pengiriman.</li>
            </ul>
        </div>

        <div class="st-card p-4 p-lg-5 mb-3">
            <h2 class="h5"><i class="bi bi-arrow-repeat" style="color: var(--rose);" aria-hidden="true"></i> Pengembalian &amp; Tukar Ukuran (30 Hari)</h2>
            <ul class="mt-2 mb-0" style="color: var(--muted);">
                <li>Klaim pengembalian dapat diajukan maksimal <strong>30 hari</strong> setelah barang diterima.</li>
                <li>Produk harus belum dipakai/dicuci, tag masih terpasang, dan dikembalikan dalam kemasan asli.</li>
                <li>Ukuran tidak pas? Kami utamakan <strong>tukar ukuran</strong> dengan ongkir ditanggung pembeli (klaim cacat produksi: ongkir ditanggung toko).</li>
                <li>Refund dikembalikan ke rekening pengirim setelah produk diterima dan diperiksa (maks. 3 hari kerja).</li>
            </ul>
        </div>

        <div class="st-card p-4 p-lg-5">
            <h2 class="h5"><i class="bi bi-shield-check" style="color: var(--rose);" aria-hidden="true"></i> Pembayaran</h2>
            <ul class="mt-2 mb-0" style="color: var(--muted);">
                <li>Saat ini pembayaran melalui <strong>transfer bank</strong>; unggah bukti transfer di halaman detail pesanan Anda.</li>
                <li>Admin mengonfirmasi pembayaran Anda — status pesanan berubah menjadi <em>Selesai</em>.</li>
                <li>Simpan bukti pembayaran Anda sampai pesanan selesai.</li>
            </ul>
        </div>

        <p class="small text-muted-2 mt-4 mb-0">
            Ada pertanyaan lain? Hubungi kami melalui WhatsApp pada jam operasional 09.00–21.00 WIB.
        </p>
    </section>
@endsection
