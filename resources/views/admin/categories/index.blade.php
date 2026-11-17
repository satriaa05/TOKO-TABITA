@extends('layouts.app')

@section('title', 'Manajemen Kategori')
@section('page_title', 'Manajemen Kategori')

@section('header_actions')
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
        <i class="bi bi-plus-lg"></i> Tambah Kategori
    </button>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.categories.index') }}" class="d-flex gap-2">
                <div class="input-group input-group-sm" style="max-width: 380px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" name="q" value="{{ $keyword }}"
                           placeholder="Cari kategori">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                @if ($keyword)
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-sm btn-light">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-tags"></i> Daftar Kategori</span>
            <span class="badge text-bg-secondary">{{ $categories->total() }} kategori</span>
        </div>
        <div class="card-body p-0">
            @if ($categories->isEmpty())
                <x-empty-state icon="bi-tags" title="Kategori tidak ditemukan"
                              message="Tambahkan kategori agar produk dapat dikelompokkan." />
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="text-center">Foto</th>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Jumlah Produk</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td class="text-center">
                                        @if ($category->photo)
                                            <img src="{{ $category->photo->url() }}"
                                                 class="rounded border"
                                                 style="height: 40px; width: 40px; object-fit: cover;"
                                                 alt="Foto kategori {{ $category->name }}">
                                        @else
                                            <span class="text-muted-2"><i class="bi bi-image"></i></span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $category->name }}</td>
                                    <td class="text-muted-2 small">{{ $category->description ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge text-bg-light border">{{ $category->products_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $category->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-light" title="Foto kategori"
                                                    data-bs-toggle="modal" data-bs-target="#modalFotoKategori"
                                                    data-id="{{ $category->id }}"
                                                    data-name="{{ $category->name }}"
                                                    data-photo-action="{{ route('admin.categories.photo.store', $category) }}"
                                                    data-photo-url="{{ $category->photo?->url() }}">
                                                <i class="bi bi-image"></i>
                                            </button>
                                            <button type="button" class="btn btn-light" title="Ubah kategori"
                                                    data-bs-toggle="modal" data-bs-target="#modalUbahKategori"
                                                    data-id="{{ $category->id }}"
                                                    data-name="{{ $category->name }}"
                                                    data-description="{{ $category->description }}"
                                                    data-active="{{ $category->is_active ? '1' : '0' }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-light" title="Hapus kategori"
                                                        data-confirm="Hapus kategori &quot;{{ $category->name }}&quot;? Kategori yang masih memiliki produk tidak bisa dihapus.">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($categories->hasPages())
                    <div class="card-footer">{{ $categories->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    {{-- Modal tambah --}}
    <div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.categories.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_name" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="new_name"
                               name="name" value="{{ old('name') }}" maxlength="100" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="new_description" class="form-label">Deskripsi</label>
                        <input type="text" class="form-control" id="new_description" name="description"
                               value="{{ old('description') }}" maxlength="255">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="new_active"
                               name="is_active" value="1" checked>
                        <label class="form-check-label" for="new_active">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal ubah --}}
    <div class="modal fade" id="modalUbahKategori" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formUbahKategori" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Deskripsi</label>
                        <input type="text" class="form-control" id="edit_description" name="description" maxlength="255">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="edit_active"
                               name="is_active" value="1">
                        <label class="form-check-label" for="edit_active">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    {{-- Modal foto kategori --}}
    <div class="modal fade" id="modalFotoKategori" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formFotoKategori" enctype="multipart/form-data" data-media-upload class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Foto Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="fotoKategoriSekarang"
                             class="rounded border"
                             style="height: 120px; width: 120px; object-fit: cover;"
                             alt="Foto kategori saat ini">
                        <div class="form-text">Foto baru akan menggantikan foto lama secara otomatis.</div>
                    </div>
                    <div class="mb-2">
                        <label for="foto_kategori" class="form-label">Foto Baru <span class="text-danger">*</span></label>
                        <input type="file" id="foto_kategori" name="photo" accept=".jpg,.jpeg,.png,.webp"
                               class="form-control @error('photo') is-invalid @enderror" data-preview="preview-foto-kategori" required>
                        <div id="preview-foto-kategori" class="d-flex flex-wrap gap-2 mt-2"></div>
                        @error('photo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-arrow-up"></i> Unggah</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('modalUbahKategori');
            const form = document.getElementById('formUbahKategori');
            const photoModal = document.getElementById('modalFotoKategori');
            const photoForm = document.getElementById('formFotoKategori');
            const baseUrl = @json(route('admin.categories.index'));

            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                form.action = baseUrl + '/' + button.dataset.id;
                document.getElementById('edit_name').value = button.dataset.name;
                document.getElementById('edit_description').value = button.dataset.description || '';
                document.getElementById('edit_active').checked = button.dataset.active === '1';
            });

            photoModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const img = document.getElementById('fotoKategoriSekarang');

                photoForm.action = button.dataset.photoAction;
                img.src = button.dataset.photoUrl;
                img.alt = 'Foto kategori ' + button.dataset.name;
            });
        })();
    </script>

    @include('partials.media-scripts')
@endpush
