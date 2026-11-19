@extends('layouts.store')

@section('title', 'Keranjang')

@section('content')
    <section class="container py-4">
        <h1 class="store-section-title mb-3">Keranjang</h1>

        @if ($items->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-bag fs-1 text-muted-2" aria-hidden="true"></i>
                <p class="text-muted-2 mt-2">Keranjang masih kosong.</p>
                <a href="{{ route('store.catalog') }}" class="btn btn-primary">Lihat Katalog</a>
            </div>
        @else
            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-center">Harga</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-center">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        @php
                                            $product = $item['product'];
                                            $cartPhoto = $product->displayPhoto($item['color'] ?? null);
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if ($cartPhoto)
                                                        <img src="{{ $cartPhoto->url() }}"
                                                             alt="Foto produk {{ $product->name }}"
                                                             style="width: 56px; height: 56px; object-fit: cover; border-radius: .4rem;">
                                                    @else
                                                        <img src="{{ asset('assets/images/products/dresses/linen-blazer-dress.jpg') }}"
                                                             alt="Foto produk {{ $product->name }}"
                                                             style="width: 56px; height: 56px; object-fit: cover; border-radius: .4rem;">
                                                    @endif
                                                    <span class="fw-semibold">
                                                        {{ $product->name }}
                                                        @if ($item['variant'])
                                                            <span class="d-block small text-muted-2 fw-normal">{{ $item['variant']->label() }}</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-center font-money">{{ rupiah($item['price']) }}</td>
                                            <td class="text-center">
                                                <form method="POST" action="{{ route('store.cart.update', $item['key']) }}"
                                                      class="d-flex align-items-center justify-content-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="number" name="qty" value="{{ $item['qty'] }}"
                                                           min="0" max="99" class="form-control form-control-sm"
                                                           style="width: 70px;" aria-label="Jumlah">
                                                    <button type="submit" class="btn btn-light btn-sm" aria-label="Ubah jumlah">
                                                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-center font-money">{{ rupiah($item['subtotal']) }}</td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('store.cart.remove', $item['key']) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-light btn-sm text-danger" aria-label="Hapus dari keranjang">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('store.cart.clear') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-link text-danger p-0">Kosongkan keranjang</button>
                    </form>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-header">Ringkasan</div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted-2">Subtotal</span>
                                <span class="font-money fw-semibold">{{ rupiah($subtotal) }}</span>
                            </div>
                            @if ($discount > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted-2">Diskon</span>
                                    <span class="font-money fw-semibold text-success">-{{ rupiah($discount) }}</span>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted-2">Ongkir ({{ $shipping['is_free'] ? 'Gratis' : $shipping['zone'] }})</span>
                                <span class="font-money fw-semibold {{ $shipping['is_free'] ? 'text-success' : '' }}">{{ rupiah($shipping['cost']) }}</span>
                            </div>
                            @if (!$shipping['is_free'] && $shipping['threshold'] > 0)
                                <div class="text-center mb-2">
                                    <span class="badge bg-info text-dark small">
                                        Gratis ongkir belanja minimal {{ rupiah($shipping['threshold']) }}
                                        <span class="text-muted-2">(belum {{ rupiah($shipping['shortfall']) }} lagi)</span>
                                    </span>
                                </div>
                            @endif
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Total</span>
                                <span class="font-money fw-bold fs-5">{{ rupiah($total) }}</span>
                            </div>
                            <a href="{{ route('store.checkout') }}" class="btn btn-primary w-100 mt-3">
                                Lanjut Checkout
                            </a>
                            <a href="{{ route('store.catalog') }}" class="btn btn-light w-100 mt-2">Tambah produk lain</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection
