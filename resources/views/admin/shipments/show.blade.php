@extends('layouts.app')

@section('title', 'Detail Pengiriman '.$shipment->sale->invoice_number)
@section('page_title', 'Detail Pengiriman')

@section('content')
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
        <div>
            <a href="{{ route('admin.shipments.index') }}" class="small text-decoration-none">Kembali</a>
            <div class="fw-bold fs-5">{{ $shipment->sale->invoice_number }}</div>
        </div>
        <span class="badge {{ $shipment->shipment_status->badgeClass() }} fs-6">
            {{ $shipment->shipment_status->label() }}
        </span>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header">Timeline Tracking</div>
                <div class="card-body">
                    @forelse ($shipment->events as $event)
                        <div class="border-start border-2 ps-3 pb-3">
                            <div class="fw-semibold small">{{ $event->normalized_status->label() }}</div>
                            <div class="small text-muted-2">
                                {{ $event->event_time->translatedFormat('d M Y H:i') }} · {{ ucfirst($event->source) }}
                            </div>
                            @if ($event->description)<div class="small">{{ $event->description }}</div>@endif
                            @if ($event->location)<div class="small text-muted-2"><i class="bi bi-geo-alt"></i> {{ $event->location }}</div>@endif
                        </div>
                    @empty
                        <p class="text-muted-2 mb-0 small">Belum ada event tracking.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">Data Pengiriman</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Kurir</dt><dd class="col-7">{{ strtoupper($shipment->courier) }} {{ $shipment->service ? '· '.$shipment->service : '' }}</dd>
                        <dt class="col-5">No. Resi</dt><dd class="col-7">{{ $shipment->tracking_number ?: '-' }}</dd>
                        <dt class="col-5">Ongkir</dt><dd class="col-7 font-money">{{ rupiah($shipment->shipping_cost) }}</dd>
                        <dt class="col-5">Mode</dt><dd class="col-7">{{ $shipment->isLive() ? 'GPS live (provider)' : 'Scan ekspedisi' }}</dd>
                        <dt class="col-5">Estimasi</dt><dd class="col-7">{{ $shipment->shipping_eta ?: ($shipment->etaLabel() ?? 'Belum tersedia') }}</dd>
                        <dt class="col-5">Sinkron terakhir</dt><dd class="col-7">{{ $shipment->last_synced_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>
                    </dl>

                    <form method="POST" action="{{ route('admin.shipments.sync', $shipment) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Sinkronkan dari Biteship
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Perbarui Status Manual</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.shipments.status', $shipment) }}">
                        @csrf
                        <div class="mb-2">
                            <label for="status" class="form-label small">Status</label>
                            <select class="form-select form-select-sm" id="status" name="status" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($shipment->shipment_status === $status)>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="note" class="form-label small">Catatan</label>
                            <input type="text" class="form-control form-control-sm" id="note" name="note" maxlength="255"
                                   placeholder="mis. Paket diserahkan ke kurir">
                        </div>
                        <div class="mb-2">
                            <label for="location" class="form-label small">Lokasi Scan / Penurunan</label>
                            <input type="text" class="form-control form-control-sm" id="location" name="location" maxlength="255"
                                   placeholder="mis. Gudang JNE Semarang">
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label for="latitude" class="form-label small">Latitude</label>
                                <input type="number" step="0.0000001" class="form-control form-control-sm" id="latitude" name="latitude"
                                       placeholder="-6.9xxxx">
                            </div>
                            <div class="col-6">
                                <label for="longitude" class="form-label small">Longitude</label>
                                <input type="number" step="0.0000001" class="form-control form-control-sm" id="longitude" name="longitude"
                                       placeholder="110.4xxxx">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label for="event_time" class="form-label small">Waktu Kejadian</label>
                            <input type="datetime-local" class="form-control form-control-sm" id="event_time" name="event_time">
                            <div class="form-text">Kosongkan untuk memakai waktu sekarang.</div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Simpan Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
