<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terjadi Kesalahan &middot; {{ config('pos.store.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="text-center" style="max-width: 460px;">
            <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle mb-3"
                 style="width: 72px; height: 72px;">
                <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
            </div>

            <h1 class="h4 mb-2">500 &mdash; Terjadi Kesalahan</h1>
            <p class="text-muted-2 mb-4">
                Ada gangguan di sistem. Silakan coba beberapa saat lagi.
                <br>Jika berlanjut, hubungi administrator.
            </p>

            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="bi bi-house"></i> Kembali ke Halaman Utama
            </a>
        </div>
    </div>
</body>
</html>
