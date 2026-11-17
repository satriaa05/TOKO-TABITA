<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman Tidak Ditemukan &middot; {{ config('pos.store.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="text-center" style="max-width: 500px;">
            <img src="{{ asset('assets/images/placeholders/error-404.svg') }}"
                 alt="404 Halaman Tidak Ditemukan"
                 class="img-fluid mb-4" style="max-height: 280px;">

            <p class="text-muted mb-4">
                Busana atau halaman yang Anda tuju tidak tersedia atau telah dipindahkan ke koleksi lain.
            </p>

            <a href="{{ url('/') }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm" style="background: #bd777c; border-color: #bd777c;">
                <i class="bi bi-house me-1"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
