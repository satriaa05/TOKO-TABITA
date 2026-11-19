@extends('layouts.store')

@section('title', 'Lacak '.$sale->invoice_number)

@section('content')
    <section class="container py-4">
        <p class="small text-muted-2 mb-1"><a href="{{ route('store.orders.index') }}" class="text-decoration-none">Riwayat Pesanan</a></p>
        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
            <h1 class="store-section-title mb-0" style="font-size: 1.4rem;">Lacak Pengiriman</h1>
            <span class="badge {{ $sale->fulfillment_status->badgeClass() }} fs-6">{{ $sale->fulfillment_status->label() }}</span>
        </div>

        <div class="row g-3 align-items-start">
            <div class="col-12 col-lg-7">
                <div class="card st-track-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-signpost-split" aria-hidden="true"></i> Perjalanan Paket</span>
                        <span class="small text-muted-2">
                            <span class="st-live-dot"></span> Diperbarui otomatis
                            <span id="tracking-updated">{{ now()->translatedFormat('H:i:s') }}</span>
                        </span>
                    </div>
                    <div class="card-body" id="tracking-live">
                        @include('store.partials.tracking-live', ['sale' => $sale, 'shipment' => $shipment])
                    </div>
                </div>
            </div>

                @if ($shipment)
                <div class="col-12 col-lg-5">
                    <div class="card st-track-card">
                        <div class="card-header"><i class="bi bi-map" aria-hidden="true"></i> Peta Lokasi</div>
                        <div class="card-body">
                            @if ($map['configured'])
                                <div id="tracking-map" style="height: 320px; border-radius: .6rem; background: #eee;"></div>
                                <div class="small text-muted-2 mt-2">
                                    <i class="bi bi-geo-alt-fill text-danger" aria-hidden="true"></i> Asal ·
                                    <i class="bi bi-geo-alt-fill text-primary" aria-hidden="true"></i> Tujuan ·
                                    <i class="bi bi-geo-alt-fill text-success" aria-hidden="true"></i> Scan terakhir ·
                                    <i class="bi bi-signpost-split" aria-hidden="true"></i> Perkiraan rute
                                </div>
                                <div class="small text-muted-2 mt-1">Peta oleh OpenStreetMap contributors · Leaflet</div>
                                @if ($map['note'])
                                    <div class="alert alert-light border small mt-2 mb-0">{{ $map['note'] }}</div>
                                @endif
                            @else
                                <div class="alert alert-warning small mb-0">
                                    Peta akan tampil setelah data pengiriman dibuat. Jika titik lokasi belum tersedia dari ekspedisi, Admin dapat mengisinya manual di menu Pengiriman.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card st-track-card mt-3">
                        <div class="card-header">Waktu Penting</div>
                        <div class="card-body small">
                            <div class="d-flex justify-content-between py-1"><span>Dibeli</span><span>{{ $sale->created_at->translatedFormat('d M Y H:i') }}</span></div>
                            <div class="d-flex justify-content-between py-1"><span>Dibayar</span><span>{{ $sale->paid_at?->translatedFormat('d M Y H:i') ?? '-' }}</span></div>
                            <div class="d-flex justify-content-between py-1"><span>Dikemas</span><span>{{ $shipment->created_at->translatedFormat('d M Y H:i') }}</span></div>
                            <div class="d-flex justify-content-between py-1"><span>Diserahkan ke kurir</span><span>{{ $shipment->last_synced_at?->translatedFormat('d M Y H:i') ?? '-' }}</span></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('styles')
    <style>
        .st-track-card { border: 1px solid var(--line); border-radius: .9rem; }
        .st-track-card .card-header { background: #fffdfc; border-bottom: 1px solid var(--line); font-weight: 600; font-size: .9rem; }
        .st-timeline { border-left: 2px solid var(--line); margin-left: .4rem; }
        .st-timeline-item { position: relative; padding: 0 0 1.1rem 1.2rem; }
        .st-timeline-dot { position: absolute; left: -7px; top: 3px; width: 12px; height: 12px; border-radius: 999px; background: var(--rose); border: 2px solid #fff; }
        .st-live-dot { display: inline-block; width: 7px; height: 7px; border-radius: 999px; background: #198754; margin-right: .25rem; box-shadow: 0 0 0 3px rgba(25,135,84,.15); }
        .st-live-frame { width: 100%; height: 360px; border: 1px solid var(--line); border-radius: .6rem; background: #fff; }
    </style>
@endpush

@if ($map['configured'] && $shipment)
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            (function () {
                const el = document.getElementById('tracking-map');
                if (!el || typeof L === 'undefined') return;

                const data = @json($map);
                const map = L.map(el).setView([-2.5, 118], 5);

                L.tileLayer(data.tile_url, {
                    attribution: data.attribution,
                    maxZoom: 19,
                    crossOrigin: true,
                }).addTo(map);

                const markers = L.featureGroup();

                function addMarker(position, title, color) {
                    if (!position || !position.lat || !position.lng) return;

                    const marker = L.circleMarker([position.lat, position.lng], {
                        radius: 8,
                        color: '#ffffff',
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 1,
                    })
                        .addTo(map)
                        .bindPopup(title);

                    markers.addLayer(marker);
                }

                addMarker(data.origin, 'Lokasi asal pengiriman', '#dc3545');
                addMarker(data.destination, 'Lokasi tujuan pengiriman', '#0d6efd');

                (data.scans || []).forEach(function (scan, index) {
                    addMarker(
                        { lat: scan.lat, lng: scan.lng },
                        'Scan ' + (index + 1) + ': ' + (scan.label || ''),
                        '#6f42c1'
                    );
                });

                addMarker(data.last_scan, 'Scan terakhir diketahui', '#198754');

                if (Array.isArray(data.polyline) && data.polyline.length > 1) {
                    const path = data.polyline.map(function (point) {
                        return [point.lat, point.lng];
                    });
                    L.polyline(path, {
                        color: '#8d6e63',
                        weight: 3,
                        opacity: 0.8,
                    }).addTo(map).bindPopup('Perkiraan jalur perjalanan');
                }

                if (markers.getLayers().length) {
                    map.fitBounds(markers.getBounds().pad(0.25));
                }

                setTimeout(function () { map.invalidateSize(); }, 300);
            })();
        </script>
    @endpush
@endif

@push('scripts')
    <script>
        (function () {
            const container = document.getElementById('tracking-live');
            const stamp = document.getElementById('tracking-updated');
            if (!container) return;

            const url = '{{ route('store.orders.tracking.partial', $sale) }}';

            function refresh() {
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
                    .then(function (r) { return r.ok ? r.text() : null; })
                    .then(function (html) {
                        if (!html) return;
                        container.innerHTML = html;
                        if (stamp) stamp.textContent = new Date().toLocaleTimeString('id-ID');
                    })
                    .catch(function () {});
            }

            setInterval(refresh, 15000);
        })();
    </script>
@endpush
