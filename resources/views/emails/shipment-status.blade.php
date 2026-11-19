@component('mail::message')
# {{ $statusLabel }}

Halo {{ $sale->buyer_name }},

{{ $description }}

**Nomor pesanan:** {{ $sale->invoice_number }}  
**Status:** {{ $statusLabel }}

@component('mail::button', ['url' => route('store.orders.tracking', $sale)])
Lacak Pengiriman
@endcomponent

Terima kasih telah berbelanja di {{ config('pos.store.name') }}.

{{ config('pos.store.name') }}
@endcomponent
