<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $sale->invoice_number }} · {{ store_profile()->name() }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 32px;
            background: #f6f1ee;
            color: #242020;
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 13px;
        }
        .invoice {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            padding: 40px 44px;
            border: 1px solid #ead7d1;
            box-shadow: 0 8px 28px rgba(46,38,34,.08);
        }
        .top { display: flex; justify-content: space-between; gap: 24px; align-items: flex-start; }
        .brand { font-family: Georgia, serif; font-size: 24px; font-weight: 700; }
        .brand-tag { font-size: 10px; letter-spacing: .18em; text-transform: uppercase; color: #77716f; }
        .brand img { max-height: 56px; max-width: 180px; object-fit: contain; display: block; margin-bottom: 6px; }
        .invoice-title { font-family: Georgia, serif; font-size: 26px; margin: 0; text-align: right; }
        .invoice-no { color: #bd777c; font-weight: 700; text-align: right; }
        .meta { margin-top: 28px; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .meta h3 { font-size: 11px; text-transform: uppercase; letter-spacing: .1em; color: #77716f; margin: 0 0 8px; }
        .meta p { margin: 2px 0; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-top: 28px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #ead7d1; text-align: left; vertical-align: top; }
        th { background: #fbf1ec; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }
        .num { text-align: right; white-space: nowrap; }
        .totals { margin-top: 18px; margin-left: auto; width: 300px; }
        .totals div { display: flex; justify-content: space-between; padding: 6px 0; }
        .totals .grand { border-top: 2px solid #242020; font-weight: 700; font-size: 16px; margin-top: 6px; padding-top: 12px; }
        .badge { display: inline-block; padding: 3px 9px; border-radius: 999px; background: #e8f3ea; color: #2f6b43; font-size: 11px; font-weight: 700; }
        .badge.wait { background: #fff3d6; color: #8a6100; }
        .note { margin-top: 24px; font-size: 11px; color: #77716f; line-height: 1.6; }
        .actions { max-width: 820px; margin: 0 auto 18px; display: flex; gap: 10px; }
        .btn { border: 0; border-radius: 6px; padding: 10px 16px; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #bd777c; color: #fff; }
        .btn-light { background: #fff; color: #242020; border: 1px solid #ead7d1; }
        @media print {
            body { background: #fff; padding: 0; }
            .invoice { border: 0; box-shadow: none; padding: 0; max-width: none; }
            .actions { display: none !important; }
            @page { size: A4; margin: 14mm; }
        }
    </style>
</head>
<body>
    @php
        $store = store_profile();
        $logo = \App\Models\Media::siteAsset('logo');
        $transaction = $sale->paymentTransactions->first();
        $shipment = $sale->shipments->first();
        $backRoute = auth()->user()?->isOwner()
            ? route('owner.dashboard')
            : route('admin.orders.show', $sale);
    @endphp

    <div class="actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            🖨 Cetak / Simpan PDF
        </button>
        <a href="{{ $backRoute }}" class="btn btn-light">Kembali</a>
    </div>

    <div class="invoice">
        <div class="top">
            <div class="brand-wrap">
                @if ($logo)
                    <img src="{{ $logo->url() }}" alt="Logo {{ $store->name() }}">
                @endif
                <div class="brand">{{ $store->name() }}</div>
                <div class="brand-tag">{{ $store->tagline() }}</div>
                <div style="margin-top:10px; color:#77716f; line-height:1.6;">
                    {{ $store->location() }}<br>
                    @if ($store->address()){{ $store->address() }}<br>@endif
                    @if ($store->whatsappDisplay()){{ $store->whatsappDisplay() }}<br>@endif
                    @if ($store->operatingHours()){{ $store->operatingHours() }}@endif
                </div>
            </div>
            <div>
                <h1 class="invoice-title">INVOICE</h1>
                <div class="invoice-no">{{ $sale->invoice_number }}</div>
                <div style="margin-top:10px;">
                    @if ($sale->payment_status?->value === 'paid')
                        <span class="badge">LUNAS</span>
                    @else
                        <span class="badge wait">{{ strtoupper($sale->payment_status?->label() ?? 'MENUNGGU') }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="meta">
            <div>
                <h3>Ditagihkan Kepada</h3>
                <p><strong>{{ $sale->buyer_name ?: '-' }}</strong></p>
                <p>{{ $sale->buyer_phone ?: '-' }}</p>
                <p>{{ $sale->buyer_address ?: '-' }}</p>
                @if ($sale->buyer_postal_code)<p>Kode pos {{ $sale->buyer_postal_code }}</p>@endif
            </div>
            <div>
                <h3>Informasi Pesanan</h3>
                <p>Tanggal pesanan: {{ $sale->created_at->translatedFormat('d F Y H:i') }}</p>
                @if ($sale->paid_at)<p>Tanggal bayar: {{ $sale->paid_at->translatedFormat('d F Y H:i') }}</p>@endif
                <p>Metode bayar: <strong>{{ $transaction?->channel?->name ?: 'Transfer Bank' }}</strong></p>
                @if ($transaction?->provider_reference)<p>Referensi: {{ $transaction->provider_reference }}</p>@endif
                <p>Status: {{ $sale->status->label() }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="num">Harga</th>
                    <th class="num">Qty</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sale->items as $item)
                    <tr>
                        <td>
                            <div style="display:flex;gap:10px;align-items:center;">
                                @if ($item->photoUrl())
                                    <img src="{{ $item->photoUrl() }}" alt=""
                                         style="width:44px;height:44px;object-fit:cover;border-radius:4px;">
                                @endif
                                <div>
                                    <strong>{{ $item->product_name }}</strong>
                                    @if ($item->size || $item->color)
                                        <div style="color:#77716f;">
                                            @if ($item->color)Warna {{ $item->color }}@endif
                                            @if ($item->size) · Ukuran {{ $item->size }}@endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="num">{{ rupiah($item->price) }}</td>
                        <td class="num">{{ $item->qty }}</td>
                        <td class="num">{{ rupiah($item->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="num">Tidak ada item.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div><span>Subtotal produk</span><span>{{ rupiah($sale->subtotal) }}</span></div>
            @if ($sale->discount_total > 0)
                <div><span>Diskon</span><span>-{{ rupiah($sale->discount_total) }}</span></div>
            @endif
            <div><span>Ongkos kirim</span><span>{{ rupiah($sale->shipping_cost) }}</span></div>
            <div class="grand"><span>Total Pembayaran</span><span>{{ rupiah($sale->total) }}</span></div>
        </div>

        @if ($shipment)
            <div class="meta" style="margin-top:28px;">
                <div>
                    <h3>Pengiriman</h3>
                    <p>Kurir: <strong>{{ strtoupper($shipment->courier) }}</strong></p>
                    <p>Layanan: {{ $shipment->service ?: '-' }}</p>
                    <p>No. resi: {{ $shipment->tracking_number ?: 'Belum tersedia' }}</p>
                </div>
                <div>
                    <h3>Status Pengiriman</h3>
                    <p>{{ $shipment->shipment_status?->label() ?? '-' }}</p>
                    @if ($shipment->last_scan_location)<p>Lokasi Scan Terakhir: {{ $shipment->last_scan_location }}</p>@endif
                    @if ($shipment->shipping_eta)<p>Estimasi: {{ $shipment->shipping_eta }}</p>@endif
                </div>
            </div>
        @endif

        <div class="note">
            Total dihitung sebagai <strong>Subtotal − Diskon + Ongkir</strong>. Ongkir hanya dikenakan sekali.
            Invoice ini dicetak otomatis dari sistem Tabita Modiste pada {{ now()->translatedFormat('d F Y H:i') }} WIB.
        </div>

    </div>
</body>
</html>
