@component('mail::message')
# {{ $statusLabel }}

Halo {{ $sale->buyer_name }},

{{ $description }}

**Nomor pesanan:** {{ $sale->invoice_number }}  
**Total pembayaran:** {{ rupiah($sale->total) }}  
**Status pembayaran:** {{ $statusLabel }}

@component('mail::button', ['url' => route('store.payment.show', $sale)])
Lihat Detail Pembayaran
@endcomponent

Terima kasih telah berbelanja di {{ config('pos.store.name') }}.

{{ config('pos.store.name') }}
@endcomponent
