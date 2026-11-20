@extends('layouts.store')

@section('title', 'Wishlist')

@section('content')
    <section class="container py-4">
        <h1 class="store-section-title mb-3">Wishlist Saya</h1>

        @if ($items->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-heart fs-1 text-muted-2" aria-hidden="true"></i>
                <p class="text-muted-2 mt-2">Wishlist masih kosong.</p>
                <a href="{{ route('store.catalog') }}" class="btn btn-primary">Jelajah Produk</a>
            </div>
        @else
            <div class="row g-3">
                @foreach ($items as $wish)
                    @php($product = $wish->product)
                    @if (! $product)
                        @continue
                    @endif
                    <div class="col-6 col-md-4 col-lg-3">
                        <article class="store-card h-100 position-relative">
                            <form method="POST" action="{{ route('store.wishlist.toggle', $product) }}"
                                  class="position-absolute top-0 end-0 m-2" style="z-index: 2;">
                                @csrf
                                <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm"
                                        style="width: 34px; height: 34px;" aria-label="Hapus dari wishlist">
                                    <i class="bi bi-heart-fill" style="color: var(--rose);" aria-hidden="true"></i>
                                </button>
                            </form>

                            @if ($product->displayPhoto())
                                <a href="{{ route('store.product', $product) }}">
                                    <img src="{{ $product->displayPhoto()->url() }}" class="st-product-img" alt="Foto produk {{ $product->name }}" style="object-fit: cover;">
                                </a>
                            @else
                                <a href="{{ route('store.product', $product) }}" aria-label="Lihat detail {{ $product->name }}">
                                    <img src="{{ asset('assets/images/products/dresses/linen-blazer-dress.jpg') }}" class="st-product-img" alt="Foto produk {{ $product->name }}" style="object-fit: cover;">
                                </a>
                            @endif

                            <div class="p-3">
                                <div class="fw-semibold text-truncate">
                                    <a href="{{ route('store.product', $product) }}" class="text-decoration-none" style="color: var(--ink);">{{ $product->name }}</a>
                                </div>
                                <div>@include('store.partials.price', ['product' => $product, 'promoPrice' => $priceMap[$product->id] ?? null])</div>
                                <div class="small text-muted-2">Stok: {{ $product->stock }}</div>
                                <form method="POST" action="{{ route('store.cart.add') }}" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="qty" value="1">
                                    <button type="submit" class="btn btn-primary btn-sm w-100" {{ $product->stock < 1 ? 'disabled' : '' }}>
                                        {{ $product->stock < 1 ? 'Stok habis' : '+ Keranjang' }}
                                    </button>
                                </form>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
            @if ($items->hasPages())
                <div class="mt-4">{{ $items->links() }}</div>
            @endif
        @endif
    </section>
@endsection
