@extends('layouts.store')

@section('title', $address->exists ? 'Ubah Alamat' : 'Tambah Alamat')

@section('content')
    <section class="container py-4">
        <p class="small text-muted-2 mb-1"><a href="{{ route('store.addresses.index') }}" class="text-decoration-none">Daftar Alamat</a></p>
        <h1 class="store-section-title mb-3" style="font-size: 1.4rem;">
            {{ $address->exists ? 'Ubah Alamat' : 'Tambah Alamat' }}
        </h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ $action }}">
                            @csrf
                            @if ($method !== 'POST')
                                @method($method)
                            @endif

                            <div class="mb-3">
                                <label for="label" class="form-label">Label Alamat <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('label') is-invalid @enderror"
                                       id="label" name="label" maxlength="40" required
                                       value="{{ old('label', $address->label ?: 'Rumah') }}"
                                       placeholder="mis. Rumah, Kantor">
                                @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="recipient_name" class="form-label">Nama Penerima <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('recipient_name') is-invalid @enderror"
                                           id="recipient_name" name="recipient_name" maxlength="150" required
                                           value="{{ old('recipient_name', $address->recipient_name) }}">
                                    @error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="phone" class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                           id="phone" name="phone" maxlength="30" required inputmode="numeric"
                                           value="{{ old('phone', $address->phone) }}" placeholder="08xxxxxxxxxx">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label for="address_line" class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('address_line') is-invalid @enderror"
                                          id="address_line" name="address_line" rows="3" maxlength="1000" required>{{ old('address_line', $address->address_line) }}</textarea>
                                @error('address_line')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label for="city" class="form-label">Kota</label>
                                    <input type="text" class="form-control" id="city" name="city" maxlength="100"
                                           value="{{ old('city', $address->city) }}" placeholder="Semarang">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="province" class="form-label">Provinsi</label>
                                    <input type="text" class="form-control" id="province" name="province" maxlength="100"
                                           value="{{ old('province', $address->province) }}" placeholder="Jawa Tengah">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="postal_code" class="form-label">Kode Pos</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" maxlength="15"
                                           value="{{ old('postal_code', $address->postal_code) }}" placeholder="501xx">
                                </div>
                            </div>

                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1"
                                       @checked(old('is_default', $address->is_default))>
                                <label class="form-check-label" for="is_default">Jadikan alamat utama</label>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="st-btn">
                                    <i class="bi bi-check-circle" aria-hidden="true"></i> Simpan Alamat
                                </button>
                                <a href="{{ route('store.addresses.index') }}" class="btn btn-light">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
