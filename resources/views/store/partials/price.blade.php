@php
    $promo = $promoPrice ?? null;
    $onPromo = is_array($promo) && (float) ($promo['discount'] ?? 0) > 0;
@endphp

@if ($onPromo)
    <span class="st-price-old">{{ rupiah($promo['original']) }}</span>
    <span class="st-price">{{ rupiah($promo['price']) }}</span>
    @if (! empty($promo['promotion']))
        <span class="st-promo-badge" title="{{ $promo['promotion'] }}">Promo</span>
    @endif
@else
    <span class="st-price">{{ rupiah($product->sale_price) }}</span>
@endif
