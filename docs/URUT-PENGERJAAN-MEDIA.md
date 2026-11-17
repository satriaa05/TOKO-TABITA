# URUT PENGERJAAN & PETA FITUR MANAJEMEN GAMBAR (MEDIA)

Dokumen ini adalah peta jalan pengerjaan fitur "Manajemen Gambar dan Media Website"
untuk proyek **TOKO TABITA** (`C:\Users\LENOVO\Documents\TOKO TABITA`), supaya
urutan kerja, letak tiap bagian, dan syarat rilisnya jelas — tanpa perlu
mengedit source code ketika admin ingin mengganti gambar.

Tanggal verifikasi: 11 November 2026.

---

## 1. Status saat ini (sudah diverifikasi ulang)

| Yang diperiksa | Hasil |
|---|---|
| Test suite proyek | 68 test, 67 lulus, 1 dilewati (gd belum ada), 268 asersi |
| Pemeriksa syarat brief | 28 dari 28 syarat TERPENUHI |
| Unggah nyata lewat HTTP | Berhasil: login → unggah PNG → berkas muncul di disk → tampil di dashboard |
| Penyimpanan | `public/storage` tertaut ke `storage/app/public` |
| Baris `media` (DB dev) | 2 baris (1 logo, 1 banner promosi) |

Perintah cek cepat:

```bash
php scripts/cek-syarat-media.php   # daftar syarat brief + status
php artisan media:prune            # cari berkas gambar yatim (tidak terpakai)
php artisan test                   # 68 test
```

---

## 2. Urutan pengerjaan (dipakai, bukan teori)

| # | Langkah | Isi yang dihasilkan |
|---|---|---|
| 1 | Skema data | `database/migrations/2026_11_11_000001_create_media_table.php` — tabel `media` (polimorfik: `product` / `category` / `site`) |
| 2 | Model & relasi | `app/Models/Media.php`, relasi `photos()` / `primaryPhoto()` pada `Product`, `photo()` pada `Category`, `Relation::morphMap` di `AppServiceProvider` |
| 3 | Service penyimpanan | `app/Services/MediaService.php` — simpan, ganti, hapus, foto utama, urutan, bersihkan berkas tak terpakai, optimasi GD |
| 4 | Aturan main | `config/media.php` — format boleh, batas ukuran, jumlah berkas, lebar/ualitas optimasi, daftar aset toko |
| 5 | Controller + route | `app/Http/Controllers/Admin/MediaController.php`, route di `routes/web.php` (grup `role:admin,owner` untuk aset toko) |
| 6 | Tampilan | `resources/views/admin/media/index.blade.php` (Tampilan Toko), panel galeri di `admin/products/form.blade.php`, modal foto kategori di `admin/categories/index.blade.php`, logo di `partials/sidebar.blade.php`, banner/koleksi di `admin/dashboard.blade.php` |
| 7 | Perilaku di browser | `resources/views/partials/media-scripts.blade.php` — preview sebelum simpan, pengecilan berkas di browser, geser urutan |
| 8 | Pengujian & pagar rilis | `tests/Feature/Admin/MediaTest.php`, `tests/Feature/Admin/MediaGalleryTest.php`, `tests/Feature/Media/TabitaMediaHttpSpecTest.php`, `tests/Feature/Media/TabitaMediaServiceSpecTest.php`, `scripts/cek-syarat-media.php` |
| 9 | Kebersihan penyimpanan | `app/Console/Commands/PruneOrphanMedia.php` → `php artisan media:prune` |

---

## 3. Peta syarat brief → implementasi → cara pakai

| Syarat brief | Letak implementasi | Cara dipakai |
|---|---|---|
| Upload foto produk | `MediaController::storeProductPhotos`, route `admin.products.photos.store` | Produk → Ubah → panel "Foto Produk" → pilih berkas → Unggah Foto |
| Ganti foto produk | `MediaController::replace` | Panel galeri → tombol "Ganti berkas" → pilih berkas baru |
| Hapus foto produk | `MediaController::destroy` + `deleteFileIfUnreferenced` | Panel galeri → tombol hapus (ada konfirmasi) |
| Banyak foto sekaligus | Field `photos[]` (maks 10 berkas) | Pilih beberapa berkas dalam satu kali unggah |
| Pilih foto utama | `MediaController::setPrimary` (kolom `is_primary`) | Tombol bintang pada satu foto |
| Urutan galeri | `MediaController::reorder` (kolom `sort_order`) | Tombol panah atas/bawah; foto paling depan otomatis jadi utama |
| Variasi warna | Kolom `color` | Kolom label pada tiap foto; tampil sebagai lencana |
| Preview sebelum simpan | `partials/media-scripts.blade.php` (`renderPreview`) | Thumbnail muncul begitu berkas dipilih, sebelum tombol unggah ditekan |
| Banner homepage | role `banner_home` (maks 5) | Tampilan Toko → Banner Homepage ( jadi carousel bila >1) |
| Logo toko | role `logo` (1 berkas, baru menggantikan yang lama) | Tampilan Toko → Logo Toko; tampil di sidebar |
| Gambar kategori | `MediaController::storeCategoryPhoto` (role `photo`) | Kategori → tombol foto → unggah |
| Banner promosi | role `banner_promo` (maks 5) | Tampilan Toko → Banner Promosi; tampil di dashboard |
| Foto koleksi terbaru | role `collection` (maks 5) | Tampilan Toko → Foto Koleksi Terbaru; tampil di dashboard |
| Laravel Storage | `Storage::disk('public')` | Folder `storage/app/public/media/...` |
| Lokasi berkas di DB | Tabel `media` (kolom `disk` + `path`) | Semua tampilan membaca lokasi dari DB |
| Validasi JPG/JPEG/PNG/WebP | `config/media.php` + aturan `mimes` | Pesan error berbahasa Indonesia |
| Batas ukuran | `max_size_kb` = 2048 (2 MB) per berkas | Ditolak dengan pesan jelas |
| Nama berkas unik | `Str::uuid()` | Contoh: `76c432c6-efdb-4f0d-ac05-7b46bb4f0493.png` |
| Hapus berkas lama | `deleteFileIfUnreferenced()` + `php artisan media:prune` | Otomatis saat ganti/hapus; sisanya bisa dibersihkan manual |
| Optimasi ukuran | GD bila tersedia (lebar maks 1600 px, kualitas 82) + pengecilan di browser (>400 KB) | Otomatis; tanpa gd,-browser tetap mengecilkan berkas |
| Tampil setelah deploy | `Media::url()` membaca DB + `php artisan storage:link` | Wajib jalankan `storage:link` setelah deploy |
| Perubahan tanpa ubah kode | Semua view membaca dari tabel `media` | Terapkan langsung setelah admin mengunggah |

---

## 4. Izin akses (diperiksa di server, bukan hanya disembunyikan di tombol)

| Peran | Boleh |
|---|---|
| Admin | Semua: foto produk, foto kategori, aset toko, urutan, warna, hapus |
| Owner | Aset toko saja (logo, banner homepage, banner promosi, koleksi). Foto produk & kategori ditolak |
| Kasir | Ditolak total (403) |
| Tamu | Diarahkan ke halaman login |

---

## 5. Syarat saat deploy ke hosting

1. `php artisan storage:link` — tanpa ini semua gambar 404. Pada Docker, entrypoint sudah menjalankannya otomatis.
2. Ekstensi **gd** terpasang di server — tanpa gd, optimasi ukuran di sisi server dilewati (tidak error). Tambahkan di image Docker:
   `docker-php-ext-install -j$(nproc) gd` (+ `docker-php-ext-configure gd --with-jpeg --with-webp`).
3. **Penyimpanan persisten** — kalau hosting memakai disk sementara (mis. Render free), unggahan hilang setiap redeploy. Pakai persistent disk atau object storage (S3 kompatibel) untuk produksi.
4. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` memakai domain HTTPS.
5. Cadangan berkala untuk folder `storage/app/public/media` (bukan hanya database).

---

## 6. Hasil verifikasi yang dilakukan langsung

- **Ujung-ke-ujung lewat HTTP** (bukan simulasi): login `admin@tokotabita.test` → buka Tampilan Toko → unggah PNG → berkas muncul di
  `storage/app/public/media/site/site/banner_promo/76c432c6-….png` → **dashboard memuat gambar itu** dan logo tampil di sidebar, tanpa ada edit kode.
- Baris database dibaca balik: `mediable_type=site`, `role=banner_promo`, `mime=image/png`, `size=209`.
- `php scripts/cek-syarat-media.php` → 28 dari 28 syarat terpenuhi.
- `php artisan test` → 68 test, 67 lulus, 1 dilewati (test optimasi GD dilewati karena gd belum ada).
- `php artisan media:prune` → menemukan 2 berkas yatim (sisa reset DB), mode laporan saja sampai dijalankan dengan `--delete`.

Yang **belum** bisa dicek: klik nyata pada tombol "pilih berkas" di browser (mesin uji tidak bisa mengakses dialog pilih berkas), dan optimasi GD di server
karena gd belum terpasang di mesin ini.

---

## 7. Catatan untuk anggota tim

- `scripts/cek-syarat-media.php` milik anggota lain; logikanya benar. Dua baris catatannya terdengar seperti kegagalan padahal statusnya TERPENUHI
  ("Gambar kategori … belum terdaftar" dan "Optimasi … gd BELUM ada") — bacaan itu hanya teks penjelasan, bukan status.
- Jangan menjalankan `php artisan test` dua proses bersamaan di satu database uji. Di proyek ini test memakai SQLite in-memory sehingga tidak bentrok.
- Dokumen `AGENTS.md` di proyek ini meminta memasang Laravel Boost; belum dipasang karena mengubah `composer.json`/`AGENTS.md` saat beberapa anggota
  masih bekerja di proyek yang sama berisiko saling menimpa.
