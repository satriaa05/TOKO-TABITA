@php
    /** @var \App\Models\User $user */
    $user = auth()->user();

    // Struktur menu diambil dari config/navigation.php sesuai peran pengguna.
    $menu = config('navigation.'.$user->role->value, []);

    // Nama toko mengikuti pengaturan Owner (fallback ke config).
    $storeName = store_profile()->name();
@endphp

<aside class="pos-sidebar">
    @php
        /** Logo toko dari tabel media; bila belum diunggah, tampil ikon bawaan. */
        $siteLogo = \App\Models\Media::siteAsset('logo');
    @endphp
    <div class="sidebar-brand">
        @if ($siteLogo)
            <img src="{{ $siteLogo->url() }}"
                 alt="Logo {{ $storeName }}"
                 style="height: 24px; width: auto; max-width: 150px; object-fit: contain;">
        @else
            <i class="bi bi-shop"></i>
        @endif
        <span>{{ $storeName }}</span>
    </div>

    <nav class="py-2 flex-grow-1 overflow-y-auto">
        <ul class="nav flex-column">
            @foreach ($menu as $entry)
                @if (($entry['type'] ?? 'item') === 'section')
                    <li class="nav-section">{{ $entry['label'] }}</li>
                @else
                    @php
                        $isSoon = ($entry['soon'] ?? false) === true;
                        $routeName = $entry['route'] ?? null;
                        $isActive = ! $isSoon && $routeName && request()->routeIs($routeName);
                    @endphp

                    <li class="nav-item">
                        @if ($isSoon)
                            {{-- Menu belum diimplementasikan: non-klik, ada badge "Segera". --}}
                            <span class="nav-link pos-menu-soon"
                                  title="Modul ini sedang dikerjakan">
                                <i class="bi {{ $entry['icon'] }}"></i>
                                <span class="flex-grow-1">{{ $entry['label'] }}</span>
                                <span class="badge text-bg-secondary fw-normal">Segera</span>
                            </span>
                        @else
                            <a class="nav-link {{ $isActive ? 'active' : '' }}"
                               href="{{ route($routeName) }}">
                                <i class="bi {{ $entry['icon'] }}"></i>
                                <span>{{ $entry['label'] }}</span>
                            </a>
                        @endif
                    </li>
                @endif
            @endforeach
        </ul>
    </nav>

    <div class="mt-auto p-3 border-top border-secondary-subtle">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width: 38px; height: 38px; font-weight: 600;">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="overflow-hidden">
                <div class="text-white fw-semibold text-truncate" style="font-size: 0.85rem;">{{ $user->name }}</div>
                <div class="text-truncate" style="font-size: 0.75rem; color: var(--pos-sidebar-text);">
                    {{ $user->role->label() }}
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-light w-100">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </button>
        </form>
    </div>
</aside>
