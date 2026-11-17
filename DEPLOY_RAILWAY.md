# Panduan Deploy TOKO TABITA ke Railway

## Prasyarat
- Akun [Railway.app](https://railway.app/)
- Domain sudah siap (untuk HTTPS)
- Google Cloud Console credentials sudah dikonfigurasi

## Langkah Deploy

### 1. Buat Project di Railway
Masuk ke [Railway Dashboard](https://railway.app/dashboard) dan buat project baru dari GitHub repo TOKO TABITA.

### 2. Tambahkan Service MySQL
Di Railway Dashboard, pilih project → "New" → "Database" → "MySQL". Railway akan otomatis menyediakan variabel environment untuk koneksi database:
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_DATABASE`
- `MYSQL_USER`
- `MYSQL_PASSWORD`

### 3. Tambahkan Service Redis (untuk cache & queue)
Pilih "New" → "Database" → "Redis". Railway akan menyediakan:
- `REDIS_HOST`
- `REDIS_PORT`
- `REDIS_PASSWORD`

### 4. Konfigurasi Environment Variables
Di Railway Dashboard → project → Settings → Variables, tambahkan:

| Variable | Nilai | Keterangan |
|----------|-------|------------|
| `APP_NAME` | Toko Tabita | |
| `APP_ENV` | production | Wajib production |
| `APP_DEBUG` | false | Wajib false di produksi |
| `APP_KEY` | base64:... | Dari `php artisan key:generate` |
| `APP_URL` | https://domainanda.com | Domain HTTPS Anda |
| `DB_CONNECTION` | mysql | |
| `DB_HOST` | dari MySQL service | |
| `DB_PORT` | dari MySQL service | |
| `DB_DATABASE` | dari MySQL service | |
| `DB_USERNAME` | dari MySQL service | |
| `DB_PASSWORD` | dari MySQL service | |
| `FILESYSTEM_DISK` | public | |
| `SESSION_DRIVER` | database | |
| `CACHE_STORE` | database | |
| `QUEUE_CONNECTION` | database | |
| `LOG_CHANNEL` | stack | |
| `LOG_LEVEL` | error | Kurangi log di produksi |
| `GOOGLE_CLIENT_ID` | dari Google Cloud Console | |
| `GOOGLE_CLIENT_SECRET` | dari Google Cloud Console | |
| `GOOGLE_REDIRECT_URI` | https://domainanda.com/login/google/callback | |

### 5. Konfigurasi Google Cloud Console
Masuk ke [Google Cloud Console → Credentials](https://console.cloud.google.com/), buka OAuth Client ID, lalu di **Authorized redirect URIs** tambahkan:
```
https://domainanda.com/login/google/callback
```
Jika consent screen masih "Testing", masukkan akun pembimbing sebagai **Test User**, atau ubah ke "Production".

### 6. Deploy dan Jalankan Migration
Setelah deploy berhasil, jalankan perintah berikut di Railway Dashboard → Deployments → Console (atau lewat CLI):

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan view:clear
php artisan route:cache
```

### 7. Verifikasi
- Buka `https://domainanda.com/login` — halaman login harus tampil
- Buka `https://domainanda.com/` — halaman beranda harus tampil
- Coba login dengan akun demo: `admin@tokotabita.test` / `Tabita-Admin-123`

## Masalah Umum

### 502 Bad Gateway
Pastikan `startCommand` di `railway.toml` sudah benar dan port 80 terbuka.

### Gambar produk tidak tampil
Pastikan `php artisan storage:link` telah dijalankan dan `FILESYSTEM_DISK=public`.

### Login Google error
Pastikan redirect URI di Google Cloud Console menggunakan HTTPS domain produksi (bukan localhost).

### Queue worker tidak jalan
Cek log service di Railway Dashboard → toko-tabita-web → Logs.

## Catatan
- Railway secara otomatis memberikan domain `*.railway.app`. Untuk production, gunakan domain custom Anda.
- Ekstensi PHP `gd` dan `intl` sudah diinstall dalam Dockerfile.
- File yang diupload (foto produk) disimpan di `storage/app/public` — untuk produksi sebaiknya gunakan S3 atau storage eksternal agar tidak hilang saat restart.
