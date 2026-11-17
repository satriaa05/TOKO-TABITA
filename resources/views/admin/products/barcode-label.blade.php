@extends('layouts.app')

@section('title', 'Cetak Label Barcode')
@section('page_title', 'Cetak Label Barcode')

@section('content')
    <section class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="store-section-title">Cetak Label Barcode</h1>
                <p class="text-muted-2">Cetak label sticker barcode untuk produk. Gunakan kertas label dan printer sticker untuk hasil terbaik.</p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Kembali ke Produk
            </a>
        </div>

        <div class="row g-3">
            @if ($product)
                <div class="col-12 col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-barcode"></i> {{ $product->name }}</span>
                            <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-light">Lihat Produk</a>
                        </div>
                        <div class="card-body text-center py-4">
                            <div class="barcode-target" style="margin: 0 auto; max-width: 300px;">
                                <svg id="barcode-svg"></svg>
                            </div>
                            <div class="mt-3">
                                <span class="fw-bold d-block">{{ $product->name }}</span>
                                <span class="text-muted-2 small">{{ $product->barcode }}</span>
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('admin.products.label', $product) }}" class="btn btn-primary">
                                    <i class="bi bi-printer"></i> Cetak Label Ini
                                </a>
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-secondary ms-2">Edit Produk</a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <span>Cetak Label Batch ({{ $products->count() }} produk)</span>
                        </div>
                        <div class="card-body">
                            @if ($products->isEmpty())
                                <div class="alert alert-warning">
                                    Tidak ada produk yang dipilih.
                                </div>
                            @else
                                <p class="text-muted-2 mb-3">Pilih produk yang akan dicetak labelnya:</p>
                                <div class="row g-3">
                                    @foreach ($products as $prod)
                                        <div class="col-6 col-md-4 col-lg-3">
                                            <div class="card h-100">
                                                <div class="card-body text-center py-3">
                                                    <div class="barcode-target" style="margin: 0 auto; max-width: 200px;">
                                                        <svg class="barcode-svg" data-barcode="{{ $prod->barcode }}"></svg>
                                                    </div>
                                                    <div class="mt-2">
                                                        <span class="fw-bold d-block small">{{ $prod->name }}</span>
                                                        <span class="text-muted-2 smaller">{{ $prod->barcode }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-3 text-end">
                                    <button onclick="window.print()" class="btn btn-primary">
                                        <i class="bi bi-printer"></i> Cetak Semua Label
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        (function () {
            var targets = document.querySelectorAll('.barcode-svg');
            targets.forEach(function (el) {
                var code = el.getAttribute('data-barcode') || document.getElementById('barcode-svg')?.getAttribute('data-code') || '';
                if (!code) {
                    // Single product page: read from meta or input
                    var input = document.querySelector('input[name="barcode"]');
                    code = input ? input.value : '';
                }
                if (code) {
                    try {
                        JsBarcode(el, code, {
                            format: 'CODE128',
                            width: 2,
                            height: 60,
                            displayValue: true,
                            fontSize: 14,
                            margin: 10,
                            marginTop: 10,
                            marginBottom: 10,
                            background: '#ffffff',
                            lineColor: '#000000'
                        });
                    } catch (e) {
                        console.error('JsBarcode error:', e);
                        el.innerHTML = '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-size="12">Error</text>';
                    }
                }
            });
        })();
    </script>
@endpush
