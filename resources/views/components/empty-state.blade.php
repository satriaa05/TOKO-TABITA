@props([
    'icon' => null,
    'image' => null,
    'title' => 'Belum ada data',
    'message' => 'Data akan tampil di sini setelah tersedia.',
])

<div {{ $attributes->merge(['class' => 'text-center py-5']) }}>
    @if ($image)
        <img src="{{ $image }}" alt="{{ $title }}" style="max-width: 240px; height: auto;" class="mb-3 d-inline-block">
    @elseif ($icon)
        <i class="bi {{ $icon }} text-muted-2" style="font-size: 2.5rem;"></i>
    @else
        <i class="bi bi-inbox text-muted-2" style="font-size: 2.5rem;"></i>
    @endif
    <h6 class="mt-2 mb-1 fw-semibold">{{ $title }}</h6>
    <p class="text-muted-2 small mb-0">{{ $message }}</p>
    @isset($action)
        <div class="mt-3">{{ $action }}</div>
    @endisset
</div>
