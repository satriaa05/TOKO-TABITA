@extends('layouts.app')

@section('title', $editing ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $editing ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran' }}</h1>
            <p class="text-muted-2 small mb-0">Metode otomatis akan aktif setelah kredensial Midtrans diisi.</p>
        </div>
        <a href="{{ route('owner.payment-channels.index') }}" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST"
                  action="{{ $editing ? route('owner.payment-channels.update', ['pembayaran_metode' => $channel]) : route('owner.payment-channels.store') }}">
                @csrf
                @if ($editing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label for="code" class="form-label small">Kode *</label>
                        <input type="text" class="form-control form-control-sm @error('code') is-invalid @enderror"
                               id="code" name="code" value="{{ old('code', $channel->code) }}" maxlength="30" required>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="name" class="form-label small">Nama *</label>
                        <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name', $channel->name) }}" maxlength="80" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="provider" class="form-label small">Provider</label>
                        <select class="form-select form-select-sm" id="provider" name="provider">
                            <option value="">Manual / Internal</option>
                            <option value="midtrans" @selected(old('provider', $channel->provider) === 'midtrans')>Midtrans</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="mode" class="form-label small">Mode *</label>
                        <select class="form-select form-select-sm" id="mode" name="mode" required>
                            <option value="manual" @selected(old('mode', $channel->mode) === 'manual')>Manual</option>
                            <option value="automatic" @selected(old('mode', $channel->mode) === 'automatic')>Otomatis</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="status" class="form-label small">Status *</label>
                        <select class="form-select form-select-sm" id="status" name="status" required>
                            <option value="available" @selected(old('status', $channel->status) === 'available')>Tersedia</option>
                            <option value="coming_soon" @selected(old('status', $channel->status) === 'coming_soon')>Segera Hadir</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="sort_order" class="form-label small">Urutan</label>
                        <input type="number" min="0" class="form-control form-control-sm" id="sort_order" name="sort_order"
                               value="{{ old('sort_order', $channel->sort_order) }}">
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label small">Deskripsi</label>
                        <textarea class="form-control form-control-sm" id="description" name="description" rows="2" maxlength="500">{{ old('description', $channel->description) }}</textarea>
                    </div>
                </div>

                <hr class="my-3">

                <h2 class="h6 mb-2">Rekening Bank (untuk transfer manual)</h2>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label for="bank_name" class="form-label small">Bank</label>
                        <input type="text" class="form-control form-control-sm" id="bank_name" name="bank_name"
                               value="{{ old('bank_name', $channel->bank_name) }}" maxlength="50" placeholder="BCA">
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="account_number" class="form-label small">Nomor Rekening</label>
                        <input type="text" class="form-control form-control-sm font-money" id="account_number" name="account_number"
                               value="{{ old('account_number', $channel->account_number) }}" maxlength="30">
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="account_holder" class="form-label small">Atas Nama</label>
                        <input type="text" class="form-control form-control-sm" id="account_holder" name="account_holder"
                               value="{{ old('account_holder', $channel->account_holder) }}" maxlength="100">
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                   @checked(old('is_active', $channel->is_active))>
                            <label class="form-check-label small" for="is_active">Aktif (bisa dipilih pembeli)</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible" value="1"
                                   @checked(old('is_visible', $channel->is_visible))>
                            <label class="form-check-label small" for="is_visible">Tampilkan di checkout (meski belum aktif)</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-save" aria-hidden="true"></i> Simpan
                    </button>
                    <a href="{{ route('owner.payment-channels.index') }}" class="btn btn-light btn-sm">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
