@extends('layouts.store')

@section('title', 'Daftar Alamat')

@section('content')
    <section class="container py-4">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
            <div>
                <p class="small text-muted-2 mb-1"><a href="{{ route('store.checkout') }}" class="text-decoration-none">Checkout</a></p>
                <h1 class="store-section-title mb-0" style="font-size: 1.4rem;">Daftar Alamat</h1>
            </div>
            <a href="{{ route('store.addresses.create') }}" class="st-btn">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah Alamat
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($addresses->isEmpty())
            <div class="st-empty">
                <i class="bi bi-geo-alt fs-1 text-muted-2" aria-hidden="true"></i>
                <p class="mt-2 mb-3">Anda belum menyimpan alamat pengiriman.</p>
                <a href="{{ route('store.addresses.create') }}" class="st-btn">Tambah Alamat Pertama</a>
            </div>
        @else
            <div class="row g-3">
                @foreach ($addresses as $address)
                    <div class="col-12 col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <h2 class="h6 mb-0">
                                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                        {{ $address->label }}
                                    </h2>
                                    @if ($address->is_default)
                                        <span class="badge text-bg-success">Alamat Utama</span>
                                    @endif
                                </div>
                                <p class="fw-semibold mb-1">{{ $address->recipient_name }}</p>
                                <p class="small text-muted-2 mb-1">{{ $address->phone }}</p>
                                <p class="small mb-3">{{ $address->fullAddress() }}</p>

                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('store.addresses.edit', $address) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil" aria-hidden="true"></i> Ubah
                                    </a>
                                    @if (! $address->is_default)
                                        <form method="POST" action="{{ route('store.addresses.default', $address) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-check2-circle" aria-hidden="true"></i> Jadikan Utama
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('store.addresses.destroy', $address) }}"
                                          onsubmit="return confirm('Hapus alamat ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash" aria-hidden="true"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">{{ $addresses->links() }}</div>
        @endif
    </section>
@endsection
