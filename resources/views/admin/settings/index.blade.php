@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page_title', 'Pengaturan Dasar')

@section('content')
    <div class="row g-3">
        {{-- Konfigurasi toko --}}
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-shop"></i> Konfigurasi Toko</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.store') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="store_name" class="form-label">Nama Toko <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('store_name') is-invalid @enderror"
                                   id="store_name" name="store_name"
                                   value="{{ old('store_name', $settings['store_name'] ?? config('pos.store.name')) }}" required>
                            @error('store_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="store_address" class="form-label">Alamat Toko</label>
                            <input type="text" class="form-control" id="store_address" name="store_address"
                                   value="{{ old('store_address', $settings['store_address'] ?? '') }}">
                        </div>

                        <div class="mb-3">
                            <label for="store_phone" class="form-label">Telepon Toko</label>
                            <input type="text" class="form-control" id="store_phone" name="store_phone"
                                   value="{{ old('store_phone', $settings['store_phone'] ?? '') }}">
                        </div>

                        <div class="mb-3">
                            <label for="receipt_footer" class="form-label">Catatan Kaki Struk</label>
                            <textarea class="form-control" id="receipt_footer" name="receipt_footer" rows="2">{{ old('receipt_footer', $settings['receipt_footer'] ?? '') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="max_discount_percent" class="form-label">Batas Maksimum Diskon (%)</label>
                            <input type="number" class="form-control font-money" id="max_discount_percent"
                                   name="max_discount_percent"
                                   value="{{ old('max_discount_percent', $settings['max_discount_percent'] ?? 100) }}"
                                   min="0" max="100" step="1">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Konfigurasi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Pengguna --}}
        <div class="col-12 col-xl-6">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-people"></i> Manajemen Pengguna
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Role</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                            <div class="small text-muted-2">{{ $user->email }}</div>
                                        </td>
                                        <td>
                                            <span class="badge text-bg-light border">{{ $user->role->label() }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-light" title="Reset kata sandi"
                                                        data-bs-toggle="modal" data-bs-target="#modalResetPassword"
                                                        data-id="{{ $user->id }}"
                                                        data-name="{{ $user->name }}"
                                                        data-action="{{ route('admin.settings.users.password', $user) }}">
                                                    <i class="bi bi-key"></i>
                                                </button>
                                                <form method="POST" class="d-inline"
                                                      action="{{ route('admin.settings.users.toggle', $user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-light"
                                                            title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                                            data-confirm="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun &quot;{{ $user->name }}&quot;?">
                                                        <i class="bi bi-{{ $user->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="bi bi-person-plus"></i> Tambah Pengguna</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.users.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="user_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="user_name" name="name" value="{{ old('name') }}" maxlength="150" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="user_email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="user_email" name="email" value="{{ old('email') }}" maxlength="150" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="user_role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select @error('role') is-invalid @enderror" id="user_role" name="role" required>
                                    <option value="owner" @selected(old('role') === 'owner')>Owner</option>
                                    <option value="admin" @selected(old('role') === 'admin')>Administrator</option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="user_phone" class="form-label">Telepon</label>
                                <input type="text" class="form-control" id="user_phone" name="phone"
                                       value="{{ old('phone') }}" maxlength="30">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="user_password" class="form-label">Kata Sandi <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       id="user_password" name="password" required minlength="8">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="user_password_confirmation" class="form-label">Ulangi Kata Sandi <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="user_password_confirmation"
                                       name="password_confirmation" required minlength="8">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">
                            <i class="bi bi-person-plus"></i> Tambah Pengguna
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal reset kata sandi --}}
    <div class="modal fade" id="modalResetPassword" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formResetPassword" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Reset Kata Sandi <span id="resetName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Kata Sandi Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="new_password" name="password"
                               required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label for="new_password_confirmation" class="form-label">Ulangi Kata Sandi <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="new_password_confirmation"
                               name="password_confirmation" required minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kata Sandi</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('modalResetPassword');
            const form = document.getElementById('formResetPassword');

            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                form.action = button.dataset.action;
                document.getElementById('resetName').textContent = button.dataset.name;
                form.reset();
            });
        })();
    </script>
@endpush
