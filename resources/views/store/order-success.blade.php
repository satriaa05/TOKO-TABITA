@extends('layouts.store')

@section('title', 'Pesanan Diterima')

@section('content')
    <section class="container py-5 text-center">
        <i class="bi bi-check-circle-fill fs-1" style="color: var(--tabita-success);" aria-hidden="true"></i>
        <h1 class="store-section-title mt-3">Pesanan Diterima</h1>
        <p class="text-muted-2">No. Pesanan: <strong>{{ $sale->invoice_number }}</strong></p>
        <p class="text-muted-2">Total: <span class="font-money fw-semibold">{{ rupiah($sale->total) }}</span></p>

        <ul class="list-group my-4 mx-auto" style="max-width: 420px; text-align: left;">
            @foreach ($sale->items as $item)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>{{ $item->product_name }}@if ($item->size || $item->color) · @if ($item->color){{ $item->color }}@endif @if ($item->size)· {{ $item->size }}@endif @endif x{{ $item->qty }}</span>
                    <span class="font-money small">{{ rupiah($item->total) }}</span>
                </li>
            @endforeach
            @if ($shippingInfo['cost'] > 0)
                <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                    <span class="text-muted-2 small">Ongkir ({{ $shippingInfo['zone'] }})</span>
                    <span class="font-money small">{{ rupiah($shippingInfo['cost']) }}</span>
                </li>
            @endif
        </ul>

        @if ($waLink)
            <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                <i class="bi bi-whatsapp" aria-hidden="true"></i>
                Konfirmasi &amp; Kirim Bukti via WhatsApp
            </a>
            <p class="small text-muted-2 mt-3">
                Admin akan memvalidasi pembayaran Anda sebelum pesanan diproses.
            </p>
        @else
            <div class="alert alert-warning d-inline-block">
                Nomor WhatsApp toko belum dikonfigurasi. Admin akan menghubungi Anda.
            </div>
        @endif

        <div class="mt-4">
            <a href="{{ route('home') }}" class="btn btn-light">Kembali ke Beranda</a>
        </div>
    </section>
@endsection
