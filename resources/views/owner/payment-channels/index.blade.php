@extends('layouts.app')

@section('title', 'Metode Pembayaran')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
        <div>
            <h1 class="h4 mb-1">Metode Pembayaran</h1>
            <p class="text-muted-2 small mb-0">Kelola semua metode pembayaran checkout. Perubahan langsung dipakai halaman checkout.</p>
        </div>
        <a href="{{ route('owner.payment-channels.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah Metode
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success small">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger small">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Rekening</th>
                        <th class="text-center">Aktif</th>
                        <th class="text-center">Tampil</th>
                        <th class="text-center">Urutan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($channels as $channel)
                        <tr>
                            <td><code>{{ $channel->code }}</code></td>
                            <td class="fw-semibold">{{ $channel->name }}</td>
                            <td>{{ $channel->isAutomatic() ? 'Otomatis' : 'Manual' }}</td>
                            <td>
                                <span class="badge {{ $channel->isComingSoon() ? 'text-bg-secondary' : 'text-bg-success' }}">
                                    {{ $channel->isComingSoon() ? 'Segera Hadir' : 'Tersedia' }}
                                </span>
                            </td>
                            <td class="small">{{ $channel->bankDetails() }}</td>
                            <td class="text-center">{{ $channel->is_active ? 'Ya' : 'Tidak' }}</td>
                            <td class="text-center">{{ $channel->is_visible ? 'Ya' : 'Tidak' }}</td>
                            <td class="text-center">{{ $channel->sort_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('owner.payment-channels.edit', $channel) }}" class="btn btn-sm btn-light">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                                <form method="POST" action="{{ route('owner.payment-channels.destroy', ['pembayaran_metode' => $channel]) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light text-danger"
                                            data-confirm="Hapus metode {{ $channel->name }}?" aria-label="Hapus metode">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted-2 py-4">Belum ada metode pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
