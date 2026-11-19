@extends('layouts.app')

@section('title', 'Profil Saya')
@section('page_title', 'Profil Saya')

@section('content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header">Foto Profil</div>
                <div class="card-body text-center">
                    <img id="avatar-preview" src="{{ $user->avatarUrl() }}" alt="Foto {{ $user->name }}"
                         class="st-avatar-preview" style="width:120px;height:120px;object-fit:cover;border-radius:999px;border:3px solid var(--tabita-rose-soft);">
                    <p class="small text-muted-2 mt-2 mb-3">JPG, JPEG, PNG, WebP · maksimal 2 MB</p>

                    <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" id="photo-form">
                        @csrf
                        <input type="file" name="avatar" id="avatar" class="form-control form-control-sm"
                               accept=".jpg,.jpeg,.png,.webp" required>
                        <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">Unggah Foto</button>
                    </form>

                    @if ($user->avatar)
                        <form method="POST" action="{{ route('profile.photo.remove') }}" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">Hapus Foto</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Informasi Akun</div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5">Nama</dt><dd class="col-7">{{ $user->name }}</dd>
                        <dt class="col-5">Email</dt><dd class="col-7">{{ $user->email }}</dd>
                        <dt class="col-5">WhatsApp</dt><dd class="col-7">{{ $user->phone ?: '-' }}</dd>
                        <dt class="col-5">Jabatan</dt><dd class="col-7">{{ $user->role?->label() }}</dd>
                        <dt class="col-5">Akun dibuat</dt><dd class="col-7">{{ $user->created_at->translatedFormat('d F Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">Edit Profil</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')
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
                                <label for="phone" class="form-label small">Nomor WhatsApp</label>
                                <input type="tel" id="phone" name="phone" class="form-control" maxlength="30"
                                       value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Simpan Profil</button>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Ganti Password</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="current_password" class="form-label small">Password lama</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="password" class="form-label small">Password baru</label>
                                <input type="password" id="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="password_confirmation" class="form-label small">Konfirmasi password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary mt-3">Ganti Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('avatar');
            const preview = document.getElementById('avatar-preview');
            if (!input || !preview) return;
            input.addEventListener('change', function () {
                if (!input.files || !input.files[0]) return;
                const reader = new FileReader();
                reader.onload = function (e) { preview.src = e.target.result; };
                reader.readAsDataURL(input.files[0]);
            });
        })();
    </script>
@endpush
