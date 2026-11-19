@extends('layouts.app')

@section('title', $user->exists ? 'Edit Akun' : 'Tambah Admin')
@section('page_title', $user->exists ? 'Edit Akun' : 'Tambah Admin')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}">
        @csrf
        @if ($method !== 'POST') @method($method) @endif

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header">Data Akun</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="name" class="form-label small">Nama lengkap</label>
                                <input type="text" id="name" name="name" class="form-control" required maxlength="150"
                                       value="{{ old('name', $user->name) }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label small">Email</label>
                                <input type="email" id="email" name="email" class="form-control" required maxlength="150"
                                       value="{{ old('email', $user->email) }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="phone" class="form-label small">WhatsApp</label>
                                <input type="tel" id="phone" name="phone" class="form-control" maxlength="30"
                                       value="{{ old('phone', $user->phone) }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="role" class="form-label small">Role</label>
                                <select id="role" name="role" class="form-select" required>
                                    <option value="admin" @selected(old('role', $user->role?->value ?? 'admin') === 'admin')>Admin</option>
                                    <option value="customer" @selected(old('role', $user->role?->value) === 'customer')>Pelanggan</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label small">Password {{ $user->exists ? '(kosongkan jika tidak diubah)' : '' }}</label>
                                <input type="password" id="password" name="password" class="form-control" minlength="8"
                                       @if (! $user->exists) required @endif autocomplete="new-password">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="password_confirmation" class="form-label small">Konfirmasi password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" minlength="8"
                                       @if (! $user->exists) required @endif autocomplete="new-password">
                            </div>
                        </div>

                        @if ($user->exists)
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                       @checked(old('is_active', $user->is_active))>
                                <label class="form-check-label" for="is_active">Akun aktif</label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-header">Permission Role Admin</div>
                    <div class="card-body">
                        <p class="small text-muted-2">
                            Berlaku untuk semua akun berrole Admin. Owner selalu memiliki seluruh akses.
                        </p>
                        @foreach ($permissions as $permission)
                            <div class="form-check small mb-2">
                                <input class="form-check-input" type="checkbox" name="permissions[]"
                                       id="perm-{{ $permission->key }}" value="{{ $permission->key }}"
                                       @checked(in_array($permission->key, old('permissions', $selected->all()), true))>
                                <label class="form-check-label" for="perm-{{ $permission->key }}">
                                    {{ $permission->name }} <span class="text-muted-2">({{ $permission->key }})</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('owner.users.index') }}" class="btn btn-light">Batal</a>
        </div>
    </form>
@endsection
