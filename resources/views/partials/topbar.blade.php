@php
    $hour = (int) now()->format('H');
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp

<header class="pos-topbar">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <button type="button" class="btn btn-sm btn-light pos-sidebar-toggle border" data-sidebar-toggle>
            <i class="bi bi-list"></i>
        </button>

        <div class="min-w-0">
            <div class="fw-semibold text-truncate">@yield('page_title', 'Dashboard')</div>
            <div class="small text-muted-2 text-truncate">
                {{ $greeting }}, {{ auth()->user()->name }}
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <div class="text-end d-none d-md-block">
            <div class="small fw-semibold">{{ now()->translatedFormat('l, d F Y') }}</div>
            <div class="small text-muted-2" id="pos-clock">{{ now()->format('H:i:s') }}</div>
        </div>

        @hasSection('header_actions')
            <div class="d-flex align-items-center gap-2">
                @yield('header_actions')
            </div>
        @endif
    </div>
</header>
