@extends('layouts.store')

@section('title', 'Notifikasi')

@section('content')
    <section class="container py-4">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
            <h1 class="store-section-title mb-0" style="font-size: 1.4rem;">Notifikasi</h1>
            @if ($notifications->whereNull('read_at')->isNotEmpty())
                <form method="POST" action="{{ route('store.notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-check2-all" aria-hidden="true"></i> Tandai semua dibaca
                    </button>
                </form>
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @forelse ($notifications as $notification)
            <div class="card st-notif-card mb-2 {{ $notification->read_at ? '' : 'st-notif-unread' }}">
                <div class="card-body d-flex gap-3 align-items-start">
                    <span class="st-notif-icon">
                        <i class="bi {{ $notification->icon ?: 'bi-bell' }}" aria-hidden="true"></i>
                    </span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">{{ $notification->title }}</div>
                        <div class="small text-muted-2">{{ $notification->message }}</div>
                        <div class="small text-muted-2 mt-1">
                            {{ $notification->created_at->translatedFormat('d M Y H:i') }}
                            @if ($notification->sale)
                                · <a href="{{ route('store.orders.show', $notification->sale) }}" class="text-decoration-none">Lihat pesanan</a>
                            @endif
                        </div>
                    </div>
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('store.notifications.read', $notification) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light" title="Tandai dibaca">
                                <i class="bi bi-check2" aria-hidden="true"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="st-empty">
                <i class="bi bi-bell fs-1 text-muted-2" aria-hidden="true"></i>
                <p class="mt-2 mb-0">Belum ada notifikasi.</p>
            </div>
        @endforelse

        @if ($notifications->hasPages())
            <div class="mt-3">{{ $notifications->links() }}</div>
        @endif
    </section>
@endsection

@push('styles')
    <style>
        .st-notif-card { border: 1px solid var(--line); border-radius: .7rem; }
        .st-notif-unread { border-left: 3px solid var(--rose); background: #fffaf8; }
        .st-notif-icon { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 999px; background: #fff0ec; color: var(--rose-dark); flex-shrink: 0; }
    </style>
@endpush
