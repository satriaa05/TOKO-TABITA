@if (! $shipment)
    <div class="st-empty">
        <i class="bi bi-truck fs-1 text-muted-2" aria-hidden="true"></i>
        <p class="mt-2 mb-1">Data pengiriman belum dibuat.</p>
        <p class="small text-muted-2 mb-0">Admin akan menginput kurir dan nomor resi setelah pembayaran dikonfirmasi.</p>
    </div>
@else
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <span class="badge {{ $sale->payment_status->badgeClass() }}">{{ $sale->payment_status->label() }}</span>
        <span class="badge {{ $sale->fulfillment_status->badgeClass() }}">{{ $sale->fulfillment_status->label() }}</span>
        <span class="badge {{ $shipment->shipment_status->badgeClass() }}">{{ $shipment->shipment_status->label() }}</span>
    </div>

    <div class="row g-2 small mb-3">
        <div class="col-6"><span class="text-muted-2">Kurir</span><br><strong>{{ strtoupper($shipment->courier) }} {{ $shipment->service ? '· '.$shipment->service : '' }}</strong></div>
        <div class="col-6"><span class="text-muted-2">No. resi</span><br><strong>{{ $shipment->tracking_number ?: 'Belum diinput' }}</strong></div>
        <div class="col-6"><span class="text-muted-2">Estimasi tiba</span><br><strong>{{ $shipment->shipping_eta ?: ($shipment->etaLabel() ?? 'Belum tersedia') }}</strong></div>
        <div class="col-6"><span class="text-muted-2">Update terakhir</span><br><strong>{{ $shipment->last_scan_at?->translatedFormat('d M Y H:i') ?? $shipment->last_synced_at?->translatedFormat('d M Y H:i') ?? '-' }}</strong></div>
        <div class="col-12">
            <span class="text-muted-2">Lokasi terakhir:</span>
            <strong>{{ $shipment->last_scan_location ?: 'Belum ada data lokasi' }}</strong>
        </div>
    </div>

    <div class="alert alert-light border small">
        Estimasi bukan jaminan waktu sampai. Data berasal dari layanan pengiriman bila tersedia.
        Halaman ini memperbarui status otomatis setiap 15 detik dari server.
    </div>

    @if ($shipment->isLive() && $shipment->live_tracking_url)
        <div class="card mt-3">
            <div class="card-header small fw-bold">GPS Live — disediakan Biteship</div>
            <div class="card-body">
                <iframe src="{{ $shipment->live_tracking_url }}"
                        title="Live tracking kurir" class="st-live-frame" loading="lazy"
                        referrerpolicy="no-referrer"></iframe>
                <p class="small text-muted-2 mt-2 mb-0">
                    Posisi kurir berasal langsung dari penyedia kurir instant. Tidak dipalsukan oleh website ini.
                </p>
            </div>
        </div>
    @endif

    <h2 class="h6 mt-4 mb-2">Perjalanan Paket</h2>
    <div class="st-timeline">
        @forelse ($shipment->events as $event)
            <div class="st-timeline-item">
                <span class="st-timeline-dot"></span>
                <div>
                    <div class="fw-semibold small">{{ $event->normalized_status->label() }}</div>
                    <div class="small text-muted-2">
                        {{ $event->event_time->translatedFormat('d M Y H:i') }} · {{ ucfirst($event->source) }}
                    </div>
                    @if ($event->description)<div class="small mt-1">{{ $event->description }}</div>@endif
                    @if ($event->location)
                        <div class="small text-muted-2"><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $event->location }}</div>
                    @endif
                    @if ($event->hasCoordinate())
                        <div class="small text-muted-2">
                            Koordinat: {{ $event->latitude }}, {{ $event->longitude }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="small text-muted-2 mb-0">Belum ada pembaruan perjalanan paket.</p>
        @endforelse
    </div>

    @if ($shipment->locations->isNotEmpty())
        <h2 class="h6 mt-4 mb-2">Riwayat Lokasi</h2>
        <div class="table-responsive">
            <table class="table table-sm small mb-0">
                <thead><tr><th>Waktu</th><th>Lokasi</th><th>Koordinat</th><th>Sumber</th></tr></thead>
                <tbody>
                    @foreach ($shipment->locations as $location)
                        <tr>
                            <td>{{ $location->recorded_at->translatedFormat('d M Y H:i') }}</td>
                            <td>{{ $location->location_description ?: '-' }}</td>
                            <td>{{ $location->latitude }}, {{ $location->longitude }}</td>
                            <td>{{ $location->source }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endif
