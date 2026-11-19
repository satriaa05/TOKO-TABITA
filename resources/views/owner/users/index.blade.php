@extends('layouts.app')

@section('title', 'Kelola Akun')
@section('page_title', 'Kelola Akun')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="small text-muted-2 mb-0">Owner dapat mengelola akun Admin dan permission yang diberikan.</p>
        <a href="{{ route('owner.users.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah Admin
        </a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nama</th><th>Email</th><th>WhatsApp</th><th>Role</th><th>Status</th><th>Dibuat</th><th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->name }}</td>
                            <td>{{ $item->email }}</td>
                            <td>{{ $item->phone ?: '-' }}</td>
                            <td><span class="badge text-bg-secondary">{{ $item->role?->label() }}</span></td>
                            <td>
                                <span class="badge {{ $item->is_active ? 'text-bg-success' : 'text-bg-danger' }}">
                                    {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="small">{{ $item->created_at->translatedFormat('d M Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('owner.users.edit', $item) }}" class="btn btn-light btn-sm">Edit</a>
                                <form method="POST" action="{{ route('owner.users.toggle', $item) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                                        {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-delete-url="{{ route('owner.users.destroy', $item) }}"
                                            data-delete-name="{{ $item->name }}">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted-2 py-3">Belum ada akun.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())<div class="card-footer">{{ $users->links() }}</div>@endif
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="deleteModalLabel">Hapus Akun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body small">
                    Akun <strong id="delete-name"></strong> akan dihapus. Tindakan ini tidak dapat dibatalkan.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" id="delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('deleteModal');
            if (!modal) return;
            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('delete-name').textContent = button.dataset.deleteName || '';
                document.getElementById('delete-form').action = button.dataset.deleteUrl;
            });
        })();
    </script>
@endpush
