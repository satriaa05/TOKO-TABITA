@extends('layouts.app')

@section('title', 'Pengaturan Bisnis')
@section('page_title', 'Pengaturan Bisnis')

@section('header_actions')
    <a href="{{ route('home') }}" class="btn btn-light btn-sm" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-box-arrow-up-right"></i> Lihat Toko
    </a>
@endsection

@section('content')
    <p class="text-muted-2">
        Pengaturan ini mengatur identitas resmi toko pada header, footer, dan tautan WhatsApp publik.
        Perubahan langsung tampil di halaman toko setelah disimpan. Hanya Owner yang dapat mengubah
        nomor WhatsApp, Instagram, lokasi, dan metode pembayaran.
    </p>
    <hr class="stitch mt-0 mb-3">

    <form method="POST" action="{{ route('owner.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            {{-- Identitas toko --}}
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><i class="bi bi-shop"></i> Identitas Toko</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="store_name" class="form-label">Nama Toko <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('store_name') is-invalid @enderror"
                                   id="store_name"
                                   name="store_name"
                                   maxlength="150"
                                   value="{{ old('store_name', $settings['store_name'] ?? $profile->name()) }}"
                                   required>
                            @error('store_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="store_tagline" class="form-label">Tagline</label>
                            <input type="text"
                                   class="form-control @error('store_tagline') is-invalid @enderror"
                                   id="store_tagline"
                                   name="store_tagline"
                                   maxlength="100"
                                   value="{{ old('store_tagline', $settings['store_tagline'] ?? $profile->tagline()) }}">
                            @error('store_tagline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Tampil di bawah logo header dan pada kolom identitas footer.</div>
                        </div>

                        <div class="mb-0">
                            <label for="store_description" class="form-label">Deskripsi Singkat</label>
                            <textarea class="form-control @error('store_description') is-invalid @enderror"
                                      id="store_description"
                                      name="store_description"
                                      rows="3"
                                      maxlength="255">{{ old('store_description', $settings['store_description'] ?? $profile->description()) }}</textarea>
                            @error('store_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kontak & lokasi --}}
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><i class="bi bi-geo-alt"></i> Kontak &amp; Lokasi</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="store_whatsapp" class="form-label">Nomor WhatsApp Resmi <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('store_whatsapp') is-invalid @enderror"
                                   id="store_whatsapp"
                                   name="store_whatsapp"
                                   maxlength="30"
                                   value="{{ old('store_whatsapp', $settings['store_whatsapp'] ?? config('pos.store.whatsapp')) }}"
                                   required>
                            @error('store_whatsapp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Format bebas: <code>+62 831-2881-4776</code>, <code>0831-2881-4776</code>, atau
                                <code>6283128814776</code>. Tautan wa.me dibuat otomatis tanpa tanda +/spasi.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="store_instagram_url" class="form-label">URL Instagram</label>
                            <input type="url"
                                   class="form-control @error('store_instagram_url') is-invalid @enderror"
                                   id="store_instagram_url"
                                   name="store_instagram_url"
                                   maxlength="255"
                                   placeholder="https://www.instagram.com/namatoko"
                                   value="{{ old('store_instagram_url', $settings['store_instagram_url'] ?? '') }}">
                            @error('store_instagram_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Kosongkan bila akun belum ada — footer menampilkan ikon non-aktif, bukan tautan palsu.
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="store_location" class="form-label">Kota / Lokasi <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('store_location') is-invalid @enderror"
                                       id="store_location"
                                       name="store_location"
                                       maxlength="150"
                                       value="{{ old('store_location', $settings['store_location'] ?? $profile->location()) }}"
                                       required>
                                @error('store_location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="store_operating_hours" class="form-label">Jam Operasional</label>
                                <input type="text"
                                       class="form-control @error('store_operating_hours') is-invalid @enderror"
                                       id="store_operating_hours"
                                       name="store_operating_hours"
                                       maxlength="150"
                                       placeholder="Senin – Sabtu, 09.00 – 17.00 WIB"
                                       value="{{ old('store_operating_hours', $settings['store_operating_hours'] ?? '') }}">
                                @error('store_operating_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-3 mb-0">
                            <label for="store_address" class="form-label">Alamat Lengkap</label>
                            <textarea class="form-control @error('store_address') is-invalid @enderror"
                                      id="store_address"
                                      name="store_address"
                                      rows="2"
                                      maxlength="255"
                                      placeholder="Nama jalan, nomor, kelurahan, kecamatan">{{ old('store_address', $settings['store_address'] ?? '') }}</textarea>
                            @error('store_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Kosongkan bila alamat lengkap belum tersedia. Jangan mengisi alamat fiktif.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Metode pembayaran --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-credit-card"></i> Metode Pembayaran di Footer</span>
                        <span class="badge text-bg-secondary">{{ count($activePayments) }} aktif</span>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted-2 mb-3">
                            Centang hanya metode yang <strong>benar-benar didukung toko</strong>. Logo yang tampil di
                            footer mengikuti pilihan ini, sehingga pelanggan tidak melihat metode yang belum tersedia.
                        </p>

                        <div class="row g-3">
                            @foreach ($catalog as $key => $method)
                                @php($logoUrl = $currentLogos[$key] ?? '')
                                <div class="col-12 col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="payment_methods[]"
                                                   id="payment_method_{{ $key }}"
                                                   value="{{ $key }}"
                                                   @checked(in_array($key, old('payment_methods', $activePayments), true))>
                                            <label class="form-check-label fw-semibold" for="payment_method_{{ $key }}">
                                                {{ $method['label'] }}
                                            </label>
                                        </div>

                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            <span class="border rounded bg-white d-inline-flex align-items-center justify-content-center p-2"
                                                  style="height: 40px; min-width: 72px;">
                                                @if ($logoUrl !== '')
                                                    <img src="{{ $logoUrl }}" alt="{{ $method['label'] }}"
                                                         style="max-height: 22px; max-width: 84px; width: auto; height: auto; object-fit: contain;">
                                                @endif
                                            </span>
                                            <span class="small text-muted-2">{{ $method['hint'] }}</span>
                                        </div>

                                        <div class="mt-3">
                                            <label for="payment_logo_{{ $key }}" class="form-label small mb-1">
                                                Ganti logo (PNG/JPG/WebP, maks. 1 MB)
                                            </label>
                                            <input type="file"
                                                   class="form-control form-control-sm @error('payment_logos.'.$key) is-invalid @enderror"
                                                   id="payment_logo_{{ $key }}"
                                                   name="payment_logos[{{ $key }}]"
                                                   accept="image/png,image/jpeg,image/webp">
                                            @error('payment_logos.'.$key)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror

                                            @if (\App\Models\Setting::get('payment_logo_'.$key))
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="remove_payment_logos[]"
                                                           id="remove_payment_logo_{{ $key }}"
                                                           value="{{ $key }}">
                                                    <label class="form-check-label small" for="remove_payment_logo_{{ $key }}">
                                                        Kembalikan ke logo bawaan
                                                    </label>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('payment_methods')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Konten halaman Tentang Kami --}}
        <div class="card mt-3">
            <div class="card-header fw-semibold">
                <i class="bi bi-file-text" aria-hidden="true"></i> Konten Halaman Tentang Kami
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="about_eyebrow" class="form-label small">Teks kecil atas</label>
                        <input type="text" class="form-control form-control-sm" id="about_eyebrow"
                               name="about_eyebrow" maxlength="80"
                               value="{{ old('about_eyebrow', $settings['about_eyebrow'] ?? 'Sejak 2010') }}">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="about_title" class="form-label small">Judul halaman</label>
                        <input type="text" class="form-control form-control-sm" id="about_title"
                               name="about_title" maxlength="150"
                               value="{{ old('about_title', $settings['about_title'] ?? 'Tentang Tabita Modiste') }}">
                    </div>
                </div>

                <div class="mt-3">
                    <label for="about_intro" class="form-label small">Paragraf pembuka</label>
                    <textarea class="form-control form-control-sm" id="about_intro" name="about_intro" rows="3" maxlength="1000">{{ old('about_intro', $settings['about_intro'] ?? '') }}</textarea>
                </div>
                <div class="mt-3">
                    <label for="about_story" class="form-label small">Paragraf cerita toko</label>
                    <textarea class="form-control form-control-sm" id="about_story" name="about_story" rows="3" maxlength="1000">{{ old('about_story', $settings['about_story'] ?? '') }}</textarea>
                </div>
                <div class="mt-3">
                    <label for="about_closure" class="form-label small">Paragraf penutup</label>
                    <textarea class="form-control form-control-sm" id="about_closure" name="about_closure" rows="3" maxlength="1000">{{ old('about_closure', $settings['about_closure'] ?? '') }}</textarea>
                </div>

                <div class="mt-3">
                    <label for="about_features_title" class="form-label small">Judul daftar keunggulan</label>
                    <input type="text" class="form-control form-control-sm" id="about_features_title"
                           name="about_features_title" maxlength="150"
                           value="{{ old('about_features_title', $settings['about_features_title'] ?? 'Mengapa Tabita Modiste?') }}">
                </div>
                <div class="mt-3">
                    <label for="about_features" class="form-label small">Daftar keunggulan</label>
                    <textarea class="form-control form-control-sm" id="about_features" name="about_features" rows="6" maxlength="3000">{{ old('about_features', $settings['about_features'] ?? '') }}</textarea>
                    <div class="form-text">
                        Satu baris per poin dengan format: <code>Judul|Deskripsi</code>. Contoh:
                        <code>Garment premium|Bahan pilihan dengan jahitan rapi.</code>
                        Kosongkan untuk memakai teks bawaan.
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Pengaturan
            </button>
            <a href="{{ route('owner.dashboard') }}" class="btn btn-light">Batal</a>
        </div>
    </form>
@endsection
