@extends('layouts.guest')

@section('title', 'Login')

@php
    $loginPhoto = \App\Models\Media::siteAsset('banner_login')?->url()
        ?? \App\Models\Media::siteAsset('banner_home')?->url()
        ?? (file_exists(public_path('assets/images/login/login-fashion-hero.jpg'))
            ? asset('assets/images/login/login-fashion-hero.jpg')
            : asset('images/login-reference-model.jpg'));
    $googleReady = filled(\Illuminate\Support\Facades\Config::get('services.google.client_id'))
        && filled(\Illuminate\Support\Facades\Config::get('services.google.client_secret'));
    $hasForgot = \Illuminate\Support\Facades\Route::has('password.request');
@endphp

@push('styles')
    <style>
        :root {
            --tm-primary: #d32f2f;
            --tm-primary-hover: #b71c1c;
            --tm-rose: #bd777c;
            --tm-font-heading: 'Playfair Display', Georgia, serif;
            --tm-font-body: 'Poppins', system-ui, -apple-system, sans-serif;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-family: var(--tm-font-body);
        }

        /* Fullscreen Viewport Container */
        .login-viewport {
            position: relative;
            min-height: 100vh;
            width: 100%;
            background-color: #1a1615;
            background-image: url('{{ $loginPhoto }}');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* Vignette & Soft Gradient Overlay */
        .login-viewport::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, rgba(18, 14, 14, 0.45) 0%, rgba(14, 10, 10, 0.78) 100%);
            z-index: 1;
        }

        /* Main Container for Split Screen */
        .login-split-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1100px;
            min-height: 580px;
            display: flex;
            align-items: stretch;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        /* Bagian Kiri - Visual Mood & Branding */
        .login-left-brand {
            flex: 1 1 50%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3.5rem 3rem;
            color: #ffffff;
            position: relative;
        }

        /* Garis Vertikal Pembatas Persis Gambar Referensi */
        .login-vertical-divider {
            width: 3px;
            background: rgba(255, 255, 255, 0.65);
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.5);
            align-self: stretch;
            flex-shrink: 0;
        }

        /* Bagian Kanan - Glassmorphism Form */
        .login-right-panel {
            flex: 1 1 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3.5rem 3.5rem;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-left: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
        }

        /* Tipografi & Elemen Visual Kiri */
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem 1.1rem;
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 50px;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            font-weight: 500;
            width: fit-content;
        }

        .brand-title {
            font-family: var(--tm-font-heading);
            font-size: clamp(2.4rem, 4vw, 3.4rem);
            font-weight: 700;
            line-height: 1.15;
            color: #ffffff;
            margin: 1.5rem 0 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
        }

        .brand-slogan {
            font-size: 1.05rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.88);
            max-width: 380px;
            margin-bottom: 2rem;
            font-weight: 300;
        }

        .brand-feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
        }

        .brand-feature-list li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-feature-list i {
            color: #ffd1d7;
            font-size: 1.1rem;
        }

        /* Form Controls Sisi Kanan (Sesuai Referensi) */
        .login-heading {
            font-family: var(--tm-font-body);
            font-size: 2.2rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1.8rem;
            text-align: center;
            letter-spacing: -0.01em;
        }

        .form-group-custom {
            margin-bottom: 1.25rem;
        }

        .form-label-custom {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: #ffffff;
            margin-bottom: 0.4rem;
        }

        .input-pill-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: #ffffff;
            border-radius: 12px;
            padding: 0.2rem 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.25s ease;
        }

        .input-pill-wrapper:focus-within {
            box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.35);
        }

        .input-icon-lead {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            color: #757575;
            font-size: 1.15rem;
        }

        .input-pill-field {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            padding: 0.65rem 0.5rem;
            font-size: 0.95rem;
            color: #212121;
            font-family: var(--tm-font-body);
        }

        .input-pill-field::placeholder {
            color: #9e9e9e;
            font-size: 0.9rem;
        }

        .btn-pwd-toggle {
            background: none;
            border: none;
            color: #757575;
            font-size: 1.15rem;
            padding: 0.4rem 0.75rem;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .btn-pwd-toggle:hover {
            color: #212121;
        }

        /* Forgot Password & Options */
        .auth-extra-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.4rem;
            font-size: 0.85rem;
        }

        .auth-link {
            color: rgba(255, 255, 255, 0.88);
            text-decoration: none;
            font-style: italic;
            transition: color 0.2s ease;
        }

        .auth-link:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        .custom-remember {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.85rem;
            cursor: pointer;
        }

        .custom-remember input {
            cursor: pointer;
            accent-color: var(--tm-primary);
        }

        /* Divider "or continue with" */
        .auth-divider-line {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0 1.25rem;
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.85rem;
            letter-spacing: 0.02em;
        }

        .auth-divider-line::before,
        .auth-divider-line::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(255, 255, 255, 0.35);
        }

        .auth-divider-line span {
            padding: 0 1rem;
        }

        /* Tombol Google */
        .google-btn-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 1.4rem;
        }

        .btn-google-round {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
        }

        .btn-google-round:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
        }

        .btn-google-round.disabled {
            opacity: 0.55;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Footer Register Link */
        .auth-footer-prompt {
            text-align: center;
            font-size: 0.88rem;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 1.5rem;
        }

        .auth-footer-prompt a {
            color: #ffffff;
            font-weight: 600;
            text-decoration: underline;
            margin-left: 0.25rem;
            transition: opacity 0.2s ease;
        }

        .auth-footer-prompt a:hover {
            opacity: 0.85;
        }

        /* Tombol Utama Login (Sesuai Tombol Merah/Dusty Rose Referensi) */
        .btn-action-primary {
            width: 100%;
            padding: 0.85rem 1.5rem;
            background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
            color: #ffffff;
            font-weight: 600;
            font-size: 1.05rem;
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(211, 47, 47, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
        }

        .btn-action-primary:hover {
            background: linear-gradient(135deg, #e53935 0%, #c62828 100%);
            box-shadow: 0 8px 25px rgba(211, 47, 47, 0.55);
            transform: translateY(-1px);
            color: #ffffff;
        }

        .btn-action-primary:active {
            transform: translateY(1px);
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.4);
        }

        /* Demo credentials card (local mode) */
        .demo-badge-card {
            margin-top: 1.25rem;
            padding: 0.75rem 1rem;
            background: rgba(0, 0, 0, 0.35);
            border: 1px dashed rgba(255, 255, 255, 0.25);
            border-radius: 10px;
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .demo-badge-card code {
            color: #ffcdd2;
        }

        /* Responsive Breakpoint */
        @media (max-width: 900px) {
            .login-split-container {
                flex-direction: column;
                max-width: 480px;
            }

            .login-left-brand {
                display: none; /* Pada smartphone fokuskan panel login di tengah sesuai instruksi */
            }

            .login-vertical-divider {
                display: none;
            }

            .login-right-panel {
                padding: 2.5rem 1.8rem;
                border-left: none;
            }
        }
    </style>
@endpush

@section('content')
<div class="login-viewport">
    <div class="login-split-container">
        
        <!-- Sisi Kiri: Branding & Mood Visual -->
        <div class="login-left-brand">
            <div>
                <div class="brand-badge">
                    <i class="bi bi-gem"></i> Tabita Modiste
                </div>
                <h1 class="brand-title">Timeless<br>Elegance.</h1>
                <p class="brand-slogan">
                    Temukan busana wanita eksklusif dengan sentuhan keanggunan sejati dan kualitas modiste premium.
                </p>
            </div>

            <div>
                <ul class="brand-feature-list">
                    <li><i class="bi bi-check2-circle"></i> Bahan premium dan jahitan standar modiste</li>
                    <li><i class="bi bi-check2-circle"></i> Koleksi dress, blouse, hijab & busana wanita modern</li>
                    <li><i class="bi bi-check2-circle"></i> Transaksi belanja aman & verifikasi pesanan terpercaya</li>
                </ul>
                <div class="mt-4 pt-3 border-top border-white border-opacity-10 small text-white text-opacity-50">
                    &copy; {{ date('Y') }} {{ config('pos.store.name', 'Tabita Modiste') }}. All rights reserved.
                </div>
            </div>
        </div>

        <!-- Garis Vertikal Transparan (Persis Gambar Referensi) -->
        <div class="login-vertical-divider"></div>

        <!-- Sisi Kanan: Panel Login Glassmorphism -->
        <div class="login-right-panel">
            <h2 class="login-heading">Login</h2>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 py-2 px-3 small" role="alert">
                    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('auth_error'))
                <div class="alert alert-danger py-2 px-3 small rounded-3 border-0 d-flex align-items-start gap-2 mb-3" role="alert">
                    <i class="bi bi-exclamation-circle mt-1"></i>
                    <span>{{ session('auth_error') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf

                <!-- Input Email -->
                <div class="form-group-custom">
                    <label for="email" class="form-label-custom">Email</label>
                    <div class="input-pill-wrapper">
                        <span class="input-icon-lead"><i class="bi bi-envelope"></i></span>
                        <input type="email"
                               class="input-pill-field"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="Enter your Email"
                               required autofocus autocomplete="username">
                    </div>
                    @error('email')
                        <div class="text-danger small mt-1 ps-1 fw-medium">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Input Password -->
                <div class="form-group-custom">
                    <label for="password" class="form-label-custom">Password</label>
                    <div class="input-pill-wrapper">
                        <span class="input-icon-lead"><i class="bi bi-shield-lock"></i></span>
                        <input type="password"
                               class="input-pill-field"
                               id="password"
                               name="password"
                               placeholder="Enter your password"
                               required autocomplete="current-password">
                        <button type="button" class="btn-pwd-toggle" id="toggle-password" aria-label="Show password">
                            <i class="bi bi-eye" id="toggle-password-icon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-danger small mt-1 ps-1 fw-medium">{{ $message }}</div>
                    @enderror

                    <div class="auth-extra-links">
                        <label class="custom-remember">
                            <input type="checkbox" name="remember" id="remember" value="1">
                            <span>Remember me</span>
                        </label>
                        @if ($hasForgot)
                            <a href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
                        @endif
                    </div>
                </div>

                <!-- Pembatas Continue With -->
                <div class="auth-divider-line">
                    <span>— or continue with —</span>
                </div>

                <!-- Tombol Google -->
                <div class="google-btn-wrapper">
                    @if ($googleReady)
                        <a href="{{ route('login.google') }}" class="btn-google-round" title="Continue with Google">
                            <svg width="22" height="22" viewBox="0 0 48 48">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.17 7.09-10.32 7.09-17.65z"/>
                                <path fill="#FBBC05" d="M10.53 28.59A14.41 14.41 0 0 1 9.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.19A23.94 23.94 0 0 0 0 24c0 3.87.93 7.54 2.56 10.78l7.97-6.19z"/>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            </svg>
                        </a>
                    @else
                        <button type="button" class="btn-google-round disabled" title="Google login belum dikonfigurasi">
                            <svg width="22" height="22" viewBox="0 0 48 48">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.17 7.09-10.32 7.09-17.65z"/>
                                <path fill="#FBBC05" d="M10.53 28.59A14.41 14.41 0 0 1 9.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.19A23.94 23.94 0 0 0 0 24c0 3.87.93 7.54 2.56 10.78l7.97-6.19z"/>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            </svg>
                        </button>
                    @endif
                </div>

                <!-- Link Daftar -->
                <div class="auth-footer-prompt">
                    Don't have an account ?
                    <a href="{{ route('register') }}">Register here</a>
                </div>

                <!-- Tombol Submit Login Merah Elegan (Persis Referensi) -->
                <button type="submit" class="btn-action-primary">
                    Login <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            @if (app()->environment('local'))
                <div class="demo-badge-card">
                    <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i> Akun Uji Coba:</div>
                    <div>Admin: <code>admin@tokotabita.test</code> (Tabita-Admin-123)</div>
                    <div>Owner: <code>owner@tokotabita.test</code> (Tabita-Owner-123)</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.getElementById('toggle-password')?.addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggle-password-icon');
            if (input && icon) {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    </script>
@endpush
