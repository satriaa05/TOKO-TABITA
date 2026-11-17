@props([
    'label' => '',
    'value' => '',
    'icon' => 'bi-info-circle',
    'color' => 'primary',
    'sub' => null,
])

@php
    $bg = [
        'primary' => 'bg-primary bg-opacity-10 text-primary',
        'success' => 'bg-success bg-opacity-10 text-success',
        'danger' => 'bg-danger bg-opacity-10 text-danger',
        'warning' => 'bg-warning bg-opacity-10 text-warning',
        'info' => 'bg-info bg-opacity-10 text-info',
        'secondary' => 'bg-secondary bg-opacity-10 text-secondary',
    ][$color] ?? 'bg-primary bg-opacity-10 text-primary';
@endphp

<div {{ $attributes->merge(['class' => 'stat-card']) }}>
    <div class="stat-icon {{ $bg }}">
        <i class="bi {{ $icon }}"></i>
    </div>
    <div class="min-w-0">
        <div class="stat-label">{{ $label }}</div>
        <div class="stat-value font-money">{{ $value }}</div>
        @if ($sub)
            <div class="stat-sub">{{ $sub }}</div>
        @endif
    </div>
</div>
