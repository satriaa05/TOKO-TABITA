# Panduan Deploy Tabita Modiste ke Shared Hosting

Panduan ini untuk hosting cPanel dengan **PHP 8.4.1+ (disarankan 8.4/8.5), MySQL, SSH, dan Cron Job**.
Contoh: Niagahoster, Hostinger, Idwebhost (paket yang menyediakan SSH).

> **Penting:** Netlify **tidak bisa** menjalankan aplikasi ini. Netlify hanya untuk
> file statis/JS. Aplikasi ini butuh PHP + MySQL + queue worker.

---

## 0. Daftar pengecekan sebelum mulai

- [ ] Hosting PHP **8.4.1** ke atas (8.4 / 8.5) — **PHP 8.3 tidak cukup**
- [ ] Ekstensi aktif: **pdo_mysql, fileinfo, gd, mbstring, openssl, tokenizer, xml, exif**
- [ ] MySQL / MariaDB tersedia
- [ ] Ada **SSH access**
- [ ] Ada **Cron Job** di cPanel (opsional — hanya untuk scheduler)
- [ ] SSL aktif (Let's Encrypt gratis)
- [ ] Bisa mengubah **Document Root** ke folder `public`
- [ ] Domain sudah mengarah ke hosting

---

## 1. Persiapan di komputer Anda (Laragon)

### 1.1 Build asset frontend

```powershell
cd "C:\Users\LENOVO\Documents\TOKO TABITA"
npm install
npm run build
```

Hasilnya harus ada:

```
public/build/manifest.json
public/build/assets/...
```

> `public/build` dan `vendor` **tidak ikut ke git** (ada di `.gitignore`).
> Jadi harus di-upload manual atau di-build di server.

### 1.2 Pastikan test masih lulus

```powershell
php artisan test
```

Jangan deploy kalau masih ada test yang gagal.

### 1.3 Buat APP_KEY (kalau tidak bisa generate di server)

```powershell
php -r "echo 'base64:'.base64_encode(random_bytes(32));"
```

Salin hasilnya untuk `APP_KEY` di `.env` produksi.

---

## 2. Buat database di cPanel

1. cPanel → **MySQL Databases**
2. Buat database, misalnya: `u123_tabita`
3. Buat user, misalnya: `u123_tabita_owner`
4. Tambahkan user ke database dengan **ALL PRIVILEGES**
5. Catat: nama database, username, password

---

## 3. Upload project

### Opsi A — Ada SSH (direkomendasikan)

Upload semua file **kecuali** `node_modules`, `.git`, dan `.env` lokal:

```powershell
# Contoh dengan scp (sesuaikan user & host)
scp -r . user@server:/home/user/tabita/
```

Lalu di server:

```sh
cd ~/tabita
composer install --no-dev --optimize-autoloader --no-interaction
npm install
npm run build
```

> Kalau server tidak punya Node.js, lakukan `npm run build` di lokal lalu upload
> folder `public/build` ke server.

### Opsi B — Tanpa SSH (FTP)

1. Upload **semua folder/file** project ke `/home/user/tabita/`, termasuk `vendor` dan `public/build` dari lokal.
2. Export database lokal lewat phpMyAdmin → **Export → Quick → Go** (`.sql`).
3. Import lewat cPanel → **phpMyAdmin** → pilih database → **Import**.

---

## 4. Atur Document Root

Laravel harus masuk lewat folder `public/`, bukan root project.

**Cara terbaik:** cPanel → **Domains / Addon Domains → Change Document Root**
→ arahkan ke `/home/user/tabita/public`

**Kalau tidak bisa mengubah document root:**
- Salin **isi** folder `public/` ke `public_html/`
- Edit `public_html/index.php`:

```php
require __DIR__.'/../tabita/vendor/autoload.php';
$app = require_once __DIR__.'/../tabita/bootstrap/app.php';
```

Sesuaikan `../tabita/` dengan nama folder tempat Anda upload project.

> Jangan pernah membiarkan file `.env`, `vendor`, atau `app/` bisa diakses publik.

---

## 5. Buat `.env` di server

Salin isi `docs/env.production.example` menjadi file `.env` di root project, lalu isi semua `<<ISI>>`.

Contoh nilai penting:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tokotabita.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=u123_tabita
DB_USERNAME=u123_tabita_owner
DB_PASSWORD=password-dari-cpanel

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

MAIL_MAILER=resend
RESEND_API_KEY=re_xxxx

MIDTRANS_NOTIFICATION_URL=https://tokotabita.id/payments/midtrans/webhook
BITESHIP_WEBHOOK_URL=https://tokotabita.id/shipping/biteship/webhook
```

Jika lewat SSH, generate kunci aplikasi:

```sh
php artisan key:generate
```

---

## 6. Perintah wajib di server (lewat SSH)

```sh
cd ~/tabita

# Pastikan folder bisa ditulis
chmod -R 775 storage bootstrap/cache

# Migrasi (hanya menambah tabel/kolom, aman)
php artisan migrate --force

# HANYA seeder metode pembayaran
php artisan db:seed --class=PaymentChannelSeeder --force

# Symlink storage agar gambar produk/bukti bisa diakses
php artisan storage:link

# Bersihkan & cache-kan konfigurasi
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### ⚠️ Jangan jalankan `DatabaseSeeder` lengkap di produksi

Seeder itu membuat akun demo dengan password tetap
(`admin@tokotabita.test` / `Tabita-Admin-123`). Kalau terlanjur, **segera ganti passwordnya**.

Buat akun Owner/Admin baru lewat tinker:

```sh
php artisan tinker
```

```php
App\Models\User::create([
  'name' => 'Owner Tabita',
  'email' => 'owner@domain-anda.com',
  'password' => bcrypt('PASSWORD_KUAT_ANDA'),
  'role' => 'owner',
  'is_active' => true,
  'email_verified_at' => now(),
]);
```

Ketik `exit` untuk keluar.

---

## 7. Cron Job (disarankan)

Email notifikasi **dikirim langsung (sinkron)** oleh aplikasi, jadi **tidak perlu queue worker**.
Namun scheduler kini menjalankan `payments:sync-pending` tiap 10 menit (fallback bila webhook
Midtrans tidak sampai) dan `media:prune` harian. Tanpa cron, sinkronisasi hanya terjadi saat
halaman pembayaran dibuka.

Tambahkan cron berikut di cPanel:

```cron
*/5 * * * * cd /home/user/tabita && /usr/bin/php artisan schedule:run >/dev/null 2>&1
```

Interval 5 menit disarankan agar fallback pembayaran maksimal 10 menit. Paket **Medium**
Rumahweb biasanya mengizinkan cron 15 menit; itu masih lebih baik daripada tidak sama sekali.

Cek path PHP Anda dengan:

```sh
which php
```

Kalau bukan `/usr/bin/php`, ganti sesuai hasil tersebut.

---

## 8. Daftarkan webhook ke provider

### Midtrans
Dashboard Sandbox → **Settings → Configuration → Payment Notification URL**:

```
https://tokotabita.id/payments/midtrans/webhook
```

### Biteship
Dashboard → **Integrations → Webhook → Add Webhook**:

- URL: `https://tokotabita.id/shipping/biteship/webhook`
- Events: `order.status`, `order.waybill_id`, `order.price`
- Header Signature Key: `Tabita-Modiste-Signature`
- Header Signature Secret: **sama dengan** `BITESHIP_WEBHOOK_TOKEN` di `.env`

### Peta gratis (Leaflet + OpenStreetMap)
- Tidak perlu API key / kartu kredit
- Isi `TABITA_MAPS_USER_AGENT` dengan domain produksi
- Detail: `docs/PANDUAN-PETA-GRATIS.md`

---

## 9. Cek setelah deploy

| Cek | Harapan |
|---|---|
| `https://domain.com/` | 200, tampil beranda |
| CSS/JS | Tidak ada error *Vite manifest not found* |
| `/katalog`, `/checkout` | Tidak 500 |
| Login admin/owner | Berhasil |
| Checkout transfer + upload bukti | Berhasil |
| Admin → Pembayaran → Setujui | Status jadi **Lunas** |
| Midtrans dashboard | Webhook masuk, signature valid |
| Biteship dashboard | Event log webhook 200 |
| Email notifikasi | Masuk langsung (sinkron, tidak butuh queue worker) |

---

## 10. Masalah umum

| Gejala | Penyebab | Solusi |
|---|---|---|
| `Composer detected issues... require PHP >= 8.4.1, running 8.1.10` | Terminal memakai PHP lama (mis. Cmd.exe/system PHP) | Pakai PHP Herd/Laragon 8.4+ atau panggil eksplisit: `C:\Users\LENOVO\.config\herd-lite\bin\php.exe artisan test` |
| Gambar produk terlalu besar / lambat | Ekstensi **GD** tidak aktif | cPanel → Select PHP Version → centang `gd` → Apply. Cek dengan `php -m` |
| `Vite manifest not found` | `public/build` tidak ada | Jalankan `npm run build`, upload folder `public/build` |
| 500 semua halaman | `.env` salah / APP_KEY kosong | Cek `.env`, jalankan `php artisan key:generate` |
| Error database | Koneksi MySQL salah | Cek DB_HOST/USER/PASS di `.env` |
| Gambar tidak muncul | Symlink storage belum ada | `php artisan storage:link` |
| Webhook tidak masuk | URL bukan HTTPS / maintenance mode | Cek SSL & matikan maintenance |
| Email tidak terkirim | `MAIL_MAILER` masih `log` / API key kosong | Cek `MAIL_MAILER=resend`, `RESEND_API_KEY`, dan domain terverifikasi |
| 403 di webhook | Signature token beda | Samakan `BITESHIP_WEBHOOK_TOKEN` dengan dashboard |

---

## Ringkasan urutan

```
build asset → upload → database → document root → .env
→ composer → migrate → storage:link → cache → cron → webhook → test
```

Kalau ada langkah yang error, tempel pesan errornya di sini — saya bantu baca log-nya.
