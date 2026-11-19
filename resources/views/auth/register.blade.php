@extends('layouts.guest')

@section('title', 'Create Account')

@php
    $loginPhoto = \App\Models\Media::siteAsset('banner_login')?->url()
        ?? \App\Models\Media::siteAsset('banner_home')?->url()
        ?? (file_exists(public_path('assets/images/login/register-fashion-hero.jpg'))
            ? asset('assets/images/login/register-fashion-hero.jpg')
            : asset('images/login-reference-model.jpg'));
    $googleReady = filled(\Illuminate\Support\Facades\Config::get('services.google.client_id'))
        && filled(\Illuminate\Support\Facades\Config::get('services.google.client_secret'));
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
            padding: 2.5rem 1rem;
        }

        .login-viewport::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, rgba(18, 14, 14, 0.45) 0%, rgba(14, 10, 10, 0.78) 100%);
            z-index: 1;
        }

        .login-split-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1100px;
            min-height: 620px;
            display: flex;
            align-items: stretch;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .login-left-brand {
            flex: 1 1 48%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3.5rem 3rem;
            color: #ffffff;
            position: relative;
        }

        .login-vertical-divider {
            width: 3px;
            background: rgba(255, 255, 255, 0.65);
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.5);
            align-self: stretch;
            flex-shrink: 0;
        }

        .login-right-panel {
            flex: 1 1 52%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 3.5rem;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-left: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
        }

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
            font-size: clamp(2.2rem, 3.8vw, 3.2rem);
            font-weight: 700;
            line-height: 1.15;
            color: #ffffff;
            margin: 1.5rem 0 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
        }

        .brand-slogan {
            font-size: 1rem;
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

        .login-heading {
            font-family: var(--tm-font-body);
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1.5rem;
            text-align: center;
            letter-spacing: -0.01em;
        }

        .form-group-custom {
            margin-bottom: 1rem;
        }

        .form-label-custom {
            display: block;
            font-size: 0.88rem;
            font-weight: 500;
            color: #ffffff;
            margin-bottom: 0.35rem;
        }

        .input-pill-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: #ffffff;
            border-radius: 12px;
            padding: 0.15rem 0.5rem;
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
            width: 38px;
            color: #757575;
            font-size: 1.1rem;
        }

        .input-pill-field {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            padding: 0.55rem 0.5rem;
            font-size: 0.92rem;
            color: #212121;
            font-family: var(--tm-font-body);
        }

        .input-pill-field::placeholder {
            color: #9e9e9e;
            font-size: 0.88rem;
        }

        .btn-pwd-toggle {
            background: none;
            border: none;
            color: #757575;
            font-size: 1.1rem;
            padding: 0.3rem 0.6rem;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .btn-pwd-toggle:hover {
            color: #212121;
        }

        .auth-footer-prompt {
            text-align: center;
            font-size: 0.88rem;
            color: rgba(255, 255, 255, 0.85);
            margin: 1.25rem 0 1.5rem;
        }

        .auth-footer-prompt a {
            color: #ffffff;
            font-weight: 600;
            text-decoration: underline;
            margin-left: 0.25rem;
        }

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

        @media (max-width: 900px) {
            .login-split-container {
                flex-direction: column;
                max-width: 480px;
            }

            .login-left-brand {
                display: none;
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
                <h1 class="brand-title">Join Our<br>Community.</h1>
                <p class="brand-slogan">
                    Daftar akun sekarang dan nikmati pengalaman berbelanja busana wanita dengan pelayanan istimewa, panduan ukuran akurat, dan koleksi eksklusif.
                </p>
            </div>

            <div>
                <ul class="brand-feature-list">
                    <li><i class="bi bi-check2-circle"></i> Riwayat pesanan & pelacakan status belanja real-time</li>
                    <li><i class="bi bi-check2-circle"></i> Wishlist busana impian tersimpan aman</li>
                    <li><i class="bi bi-check2-circle"></i> Notifikasi promo eksklusif & koleksi new arrivals</li>
                </ul>
                <div class="mt-4 pt-3 border-top border-white border-opacity-10 small text-white text-opacity-50">
                    &copy; {{ date('Y') }} {{ store_profile()->name() }}. All rights reserved.
                </div>
            </div>
        </div>

        <!-- Garis Vertikal Transparan -->
        <div class="login-vertical-divider"></div>

        <!-- Sisi Kanan: Panel Register Glassmorphism -->
        <div class="login-right-panel">
            <h2 class="login-heading">Create Account</h2>

            @if (session('auth_error'))
                <div class="alert alert-danger py-2 px-3 small rounded-3 border-0 d-flex align-items-start gap-2 mb-3" role="alert">
                    <i class="bi bi-exclamation-circle mt-1"></i>
                    <span>{{ session('auth_error') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" novalidate>
                @csrf

                <!-- Nama Lengkap -->
                <div class="form-group-custom">
                    <label for="name" class="form-label-custom">Nama Lengkap</label>
                    <div class="input-pill-wrapper">
                        <span class="input-icon-lead"><i class="bi bi-person"></i></span>
                        <input type="text"
                               class="input-pill-field"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="Nama lengkap Anda"
                               required autofocus autocomplete="name">
                    </div>
                    @error('name')
                        <div class="text-danger small mt-1 ps-1 fw-medium">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Email -->
                <div class="form-group-custom">
                    <label for="email" class="form-label-custom">Email</label>
                    <div class="input-pill-wrapper">
                        <span class="input-icon-lead"><i class="bi bi-envelope"></i></span>
                        <input type="email"
                               class="input-pill-field"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="contoh@domain.com"
                               required autocomplete="username">
                    </div>
                    @error('email')
                        <div class="text-danger small mt-1 ps-1 fw-medium">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Nomor WhatsApp -->
                <div class="form-group-custom">
                    <label for="phone" class="form-label-custom">Nomor WhatsApp</label>
                    <div class="input-pill-wrapper">
                        <span class="input-icon-lead"><i class="bi bi-whatsapp"></i></span>
                        <input type="tel"
                               class="input-pill-field"
                               id="phone"
                               name="phone"
                               value="{{ old('phone') }}"
                               placeholder="08123456789"
                               autocomplete="tel">
                    </div>
                    @error('phone')
                        <div class="text-danger small mt-1 ps-1 fw-medium">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group-custom">
                    <label for="password" class="form-label-custom">Password</label>
                    <div class="input-pill-wrapper">
                        <span class="input-icon-lead"><i class="bi bi-shield-lock"></i></span>
                        <input type="password"
                               class="input-pill-field"
                               id="password"
                               name="password"
                               placeholder="Minimal 8 karakter"
                               required autocomplete="new-password">
                        <button type="button" class="btn-pwd-toggle" id="toggle-reg-password" aria-label="Show password">
                            <i class="bi bi-eye" id="toggle-reg-password-icon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-danger small mt-1 ps-1 fw-medium">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Konfirmasi Password -->
                <div class="form-group-custom">
                    <label for="password_confirmation" class="form-label-custom">Konfirmasi Password</label>
                    <div class="input-pill-wrapper">
                        <span class="input-icon-lead"><i class="bi bi-shield-check"></i></span>
                        <input type="password"
                               class="input-pill-field"
                               id="password_confirmation"
                               name="password_confirmation"
                               placeholder="Ulangi password"
                               required autocomplete="new-password">
                    </div>
                </div>

                <!-- Link Login -->
                <div class="auth-footer-prompt">
                    Already have an account ?
                    <a href="{{ route('login') }}">Login here</a>
                </div>

                <!-- Tombol Submit Register -->
                <button type="submit" class="btn-action-primary">
                    Register <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.getElementById('toggle-reg-password')?.addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggle-reg-password-icon');
            if (input && icon) {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    </script>
@endpush
