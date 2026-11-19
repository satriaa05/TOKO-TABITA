@extends('layouts.app')

@section('title', 'Pembayaran '.$sale->invoice_number)
@section('page_title', 'Detail Transaksi Pembayaran')

@section('content')
    @php
        $transaction = $sale->paymentTransactions->first();
        $proof = $sale->paymentProofs->where('status', 'pending')->first();
    @endphp

    <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
        <div>
            <a href="{{ route('admin.payments.index') }}" class="small text-decoration-none">Kembali ke Daftar</a>
            <div class="fw-bold fs-5">{{ $sale->invoice_number }}</div>
        </div>
        <div class="d-flex gap-2">
            <span class="badge {{ $sale->payment_status->badgeClass() }} fs-6">{{ $sale->payment_status->label() }}</span>
            <span class="badge {{ $sale->fulfillment_status->badgeClass() }} fs-6">{{ $sale->fulfillment_status->label() }}</span>
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header">Verifikasi Bukti Pembayaran (Manual)</div>
                <div class="card-body">
                    @forelse ($sale->paymentProofs as $item)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                <div>
                                    <a href="{{ $item->url() }}" target="_blank" rel="noopener noreferrer" class="fw-semibold">
                                        {{ $item->original_name }}
                                    </a>
                                    <span class="badge {{ $item->status->badgeClass() }} ms-1">{{ $item->status->label() }}</span>
                                    <div class="small text-muted-2">
                                        Diunggah {{ $item->created_at->translatedFormat('d M Y H:i') }}
                                        @if ($item->verified_at) · Diverifikasi {{ $item->verified_at->translatedFormat('d M Y H:i') }} @endif
                                    </div>
                                    @if ($item->rejection_reason)
                                        <div class="small text-danger">Alasan: {{ $item->rejection_reason }}</div>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ $item->url() }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ $item->url() }}" alt="Bukti pembayaran"
                                     style="height:140px;width:auto;object-fit:cover;border-radius:.4rem;border:1px solid var(--tabita-border);">
                            </a>
                        </div>
                    @empty
                        <p class="small text-muted-2 mb-0">Belum ada bukti pembayaran diunggah pembeli.</p>
                    @endforelse

                    @if ($proof && $sale->payment_status->value !== 'paid')
                        <div class="alert alert-light border small">
                            Nominal yang harus dicocokkan: <strong class="font-money">{{ rupiah($sale->total) }}</strong>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="bi bi-check-circle" aria-hidden="true"></i> Setujui & Tandai Lunas
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle" aria-hidden="true"></i> Tolak Bukti
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Riwayat Webhook Gateway</div>
                <div class="card-body p-0">
                    @if (! $gatewayConfigured)
                        <div class="alert alert-warning small m-3 mb-0">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            Payment gateway belum terkonfigurasi (kredensial belum diisi). Tidak ada webhook yang diproses.
                            Pembayaran saat ini memakai verifikasi manual oleh Admin.
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Sumber</th>
                                    <th>ID Transaksi</th>
                                    <th>Event</th>
                                    <th>Status Proses</th>
                                    <th>Respons</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($webhooks as $log)
                                    <tr>
                                        <td>{{ $log->created_at->translatedFormat('d M Y H:i:s') }}</td>
                                        <td>{{ $log->provider }}</td>
                                        <td class="small">{{ $log->transaction_reference ?: '-' }}</td>
                                        <td class="small">{{ $log->sanitized_payload['transaction_status'] ?? ($log->sanitized_payload['event'] ?? '-') }}</td>
                                        <td>{{ $log->processing_status }}</td>
                                        <td>
                                            @if ($log->signature_valid)
                                                <span class="badge text-bg-success">Signature valid</span>
                                            @else
                                                <span class="badge text-bg-danger">Ditolak</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $log->error_message ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted-2 py-3">
                                            {{ $gatewayConfigured ? 'Belum ada webhook diterima.' : 'Belum terhubung — konfigurasi gateway kosong.' }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer small text-muted-2">
                        Webhook dengan ID event yang sama tidak diproses ulang (idempoten). Payload disimpan tanpa secret/token.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">Detail Transaksi (Pembayaran)</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">ID Transaksi</dt><dd class="col-7">{{ $transaction?->id ?: '-' }}</dd>
                        <dt class="col-5">No. Invoice</dt><dd class="col-7">{{ $sale->invoice_number }}</dd>
                        <dt class="col-5">Referensi Internal</dt><dd class="col-7">{{ $transaction?->transaction_reference ?: '-' }}</dd>
                        <dt class="col-5">Referensi Provider</dt><dd class="col-7">{{ $transaction?->provider_reference ?: '-' }}</dd>
                        <dt class="col-5">Metode</dt><dd class="col-7">{{ $transaction?->channel?->name ?: '-' }}</dd>
                        <dt class="col-5">Provider</dt><dd class="col-7">{{ $transaction?->channel?->provider ?: 'manual' }}</dd>
                        <dt class="col-5">Nominal</dt><dd class="col-7 font-money">{{ rupiah($transaction?->amount ?? $sale->total) }}</dd>
                        <dt class="col-5">Status</dt><dd class="col-7">{{ $transaction?->status?->label() ?: '-' }}</dd>
                        <dt class="col-5">Dibuat</dt><dd class="col-7">{{ $transaction?->created_at?->translatedFormat('d M Y H:i') ?: '-' }}</dd>
                        <dt class="col-5">Dibayar</dt><dd class="col-7">{{ $transaction?->paid_at?->translatedFormat('d M Y H:i') ?: '-' }}</dd>
                        <dt class="col-5">Kedaluwarsa</dt><dd class="col-7">{{ $transaction?->expired_at?->translatedFormat('d M Y H:i') ?: '-' }}</dd>
                        <dt class="col-5">Info Gagal</dt><dd class="col-7">{{ $transaction?->failure_detail ? json_encode($transaction->failure_detail) : '-' }}</dd>
                    </dl>

                    @if ($transaction?->channel->isAutomatic())
                        <form method="POST" action="{{ route('admin.payments.sync', $sale) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Sinkronkan dari Gateway
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('admin.orders.show', $sale) }}" class="btn btn-light btn-sm w-100 mt-2">
                        Lihat Detail Pesanan (barang & pengiriman)
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if ($proof)
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Setujui Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form method="POST" action="{{ route('admin.payments.proof.approve', $proof) }}">
                    @csrf
                    <div class="modal-body small">
                        Cocokkan bukti dengan nominal <strong class="font-money">{{ rupiah($sale->total) }}</strong> sebelum menyetujui.
                        Status pesanan akan berubah menjadi <strong>Lunas</strong>.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Ya, Setujui</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Tolak Bukti Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form method="POST" action="{{ route('admin.payments.proof.reject', $proof) }}">
                    @csrf
                    <div class="modal-body">
                        <label for="reason" class="form-label small">Alasan penolakan</label>
                        <input type="text" name="reason" id="reason" class="form-control" required minlength="5" maxlength="255"
                               placeholder="mis. Nominal tidak sesuai">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Tolak Bukti</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection
