@extends('layouts.app')

@section('title', 'Tampilan Toko')
@section('page_title', 'Tampilan Toko')

@section('header_actions')
    <a href="{{ route(auth()->user()->role->dashboardRoute()) }}" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
@endsection

@section('content')
    <p class="text-muted-2">
        Kelola gambar toko dari sini - perubahan langsung tampil di aplikasi tanpa
        perlu mengubah kode program. Format JPG, JPEG, PNG, atau WebP;
        maksimal {{ number_format(config('media.max_size_kb') / 1024, 0) }} MB per berkas.
    </p>
    <hr class="stitch mt-0 mb-3">

    <div class="row g-3">
        @foreach ($roles as $role => $config)
            @php
                $items = $groups[$role];
                $isMultiple = $config['multiple'];
            @endphp

            <div class="col-12 {{ $isMultiple ? 'col-xl-6' : 'col-xl-4' }}">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-image"></i> {{ $config['label'] }}</span>
                        <span class="badge text-bg-secondary">{{ $items->count() }}</span>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted-2 mb-2">{{ $config['hint'] }}</p>

                        @if ($items->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2 mb-3 {{ $isMultiple ? 'border rounded p-2' : '' }}"
                                 @if ($isMultiple) data-order-group="reorder-form-{{ $role }}" @endif>
                                @foreach ($items as $item)
                                    <div class="media-item position-relative" data-media-id="{{ $item->id }}">
                                        <img src="{{ $item->url() }}"
                                             class="rounded border d-block"
                                             style="height: 96px; width: auto; max-width: 190px; object-fit: cover;"
                                             alt="{{ $config['label'] }}">

                                        <div class="btn-group btn-group-sm position-absolute top-0 end-0 m-1 shadow-sm">
                                            @if ($isMultiple)
                                                <button type="button" class="btn btn-light" data-move="up"
                                                        title="Geser ke depan" aria-label="Geser ke depan">
                                                    <i class="bi bi-arrow-up"></i>
                                                </button>
                                                <button type="button" class="btn btn-light" data-move="down"
                                                        title="Geser ke belakang" aria-label="Geser ke belakang">
                                                    <i class="bi bi-arrow-down"></i>
                                                </button>
                                            @endif
                                            <form method="POST" action="{{ route('admin.media.destroy', $item) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-light text-danger"
                                                        title="Hapus gambar"
                                                        aria-label="Hapus {{ strtolower($config['label']) }}"
                                                        data-confirm="Hapus {{ strtolower($config['label']) }} ini? Berkas lama ikut dihapus dari penyimpanan.">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted-2 small mb-3"><i class="bi bi-image"></i> Belum ada gambar.</div>
                        @endif

                        <hr class="my-2">

                        <form method="POST"
                              action="{{ route('admin.media.site.store', $role) }}"
                              enctype="multipart/form-data"
                              data-media-upload>
                            @csrf
                            <input type="file"
                                   name="photos[]"
                                   accept=".jpg,.jpeg,.png,.webp"
                                   class="form-control form-control-sm"
                                   {{ $isMultiple ? 'multiple' : '' }}
                                   data-preview="preview-{{ $role }}"
                                   aria-label="Berkas {{ $config['label'] }}"
                                   required>
                            <div id="preview-{{ $role }}" class="d-flex flex-wrap gap-2 mt-2"></div>
                            <button type="submit" class="btn btn-sm btn-primary mt-2">
                                <i class="bi bi-cloud-arrow-up"></i> Unggah
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Form urutan tersembunyi untuk aset multi-gambar --}}
    @foreach ($roles as $role => $config)
        @if ($config['multiple'])
            <form id="reorder-form-{{ $role }}" method="POST" action="{{ route('admin.media.reorder') }}" class="d-none">
                @csrf
            </form>
        @endif
    @endforeach
@endsection

@push('scripts')
    @include('partials.media-scripts')
@endpush
