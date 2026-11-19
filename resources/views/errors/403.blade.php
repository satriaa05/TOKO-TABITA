<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak &middot; {{ store_profile()->name() }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="text-center" style="max-width: 460px;">
            <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle mb-3"
                 style="width: 72px; height: 72px;">
                <i class="bi bi-shield-lock" style="font-size: 2rem;"></i>
            </div>

            <h1 class="h4 mb-2">Akses Ditolak</h1>
            <p class="text-muted-2 mb-4">
                {{ $message ?? 'Anda tidak memiliki hak akses ke halaman ini.' }}
            </p>

            <div class="d-flex justify-content-center gap-2">
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                    <i class="bi bi-speedometer2"></i> Dashboard Saya
                </a>
            </div>
        </div>
    </div>
</body>
</html>
