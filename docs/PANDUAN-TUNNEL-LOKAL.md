# Akses Publik Sementara + Uji Webhook dari Lokal

Panduan ini dipakai **sambil menunggu hosting + domain dibeli**. Website bisa diakses publik lewat Cloudflare Tunnel, dan webhook Biteship bisa diuji tanpa hosting.

---

## 1. Kondisi saat ini (sudah aktif)

| Item | Nilai |
|---|---|
| Server lokal | `http://127.0.0.1:8000` |
| URL publik sementara | `https://silence-furniture-gale-sculpture.trycloudflare.com` |
| Webhook Biteship | `https://silence-furniture-gale-sculpture.trycloudflare.com/shipping/biteship/webhook` |
| Header Signature Key | `Tabita-Modiste-Signature` |
| Header Signature Secret | `<token dari BITESHIP_WEBHOOK_TOKEN>` |
| Status verifikasi | ✅ Home `200`, ✅ Webhook `200 OK` |

> URL `trycloudflare.com` **berubah hanya kalau tunnel dibuat ulang**. Script `tunnel-lokal.ps1` sekarang otomatis memakai ulang URL yang masih hidup, jadi tidak perlu daftar webhook ulang setiap kali script dijalankan.

> **Rotasi token:** secret webhook sudah diganti dan tidak lagi ditulis di dokumentasi/script.
> Nilai aslinya hanya ada di `.env` (`BITESHIP_WEBHOOK_TOKEN`). Update header secret di dashboard
> Biteship dengan nilai terbaru dari `.env`; jangan bagikan nilainya di chat atau commit.

---

## 2. Menjalankan ulang (kalau tunnel mati / komputer restart)

### Cara cepat — pakai script
Buka PowerShell di folder project:

```powershell
cd "C:\Users\LENOVO\Documents\TOKO TABITA"
powershell -ExecutionPolicy Bypass -File scripts\tunnel-lokal.ps1
```

Script akan:
- mengecek dulu apakah URL lama masih aktif → kalau iya, langsung dipakai (tidak menyalakan tunnel baru),
- menjalankan `php artisan serve` kalau belum jalan,
- membuat tunnel baru dengan **nama log unik** kalau URL lama sudah mati,
- mencetak URL publik & URL webhook.

### Kenapa versi script sebelumnya bisa gagal
Versi lama memakai path log yang sama dengan tunnel yang masih berjalan, sehingga file log ditimpa proses baru dan URL lama hilang dari log. Script tidak menemukan URL dan mencetak "Gagal mendapat URL". Versi sekarang sudah memakai file state + log unik, jadi masalah itu tidak terjadi lagi.

### Cara manual — 2 terminal

**Terminal 1**
```powershell
cd "C:\Users\LENOVO\Documents\TOKO TABITA"
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2**
```powershell
npx cloudflared tunnel --url http://127.0.0.1:8000
```

Salin URL `https://xxxx.trycloudflare.com` yang muncul.

---

## 3. Daftarkan webhook di Biteship

Dashboard Biteship → **Integrasi → Webhook → Tambah Webhook**:

| Field | Isi |
|---|---|
| Nama | `Tabita Modiste Lokal` |
| URL | `https://silence-furniture-gale-sculpture.trycloudflare.com/shipping/biteship/webhook` |
| Events | `order.status`, `order.waybill_id`, `order.price` |
| Header Signature Key | `Tabita-Modiste-Signature` |
| Header Signature Secret | `<token dari BITESHIP_WEBHOOK_TOKEN>` |

Klik **Save**. Tunggu 1–2 menit.

---

## 4. Env yang dipakai

File `.env` lokal sudah diisi:

```dotenv
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta

BITESHIP_API_KEY=
BITESHIP_WEBHOOK_TOKEN=<token dari .env>
BITESHIP_WEBHOOK_HEADER=Tabita-Modiste-Signature
BITESHIP_WEBHOOK_URL=https://silence-furniture-gale-sculpture.trycloudflare.com/shipping/biteship/webhook
TABITA_STORE_POSTAL_CODE=50131
```

Setelah mengubah `.env`, jalankan:

```powershell
php artisan config:clear
```

> `BITESHIP_API_KEY` masih kosong. Cek ongkir real-time belum aktif sampai API key Starter dimasukkan. Webhook tetap bisa diuji sekarang.

---

## 5. Cara menguji webhook benar-benar masuk

Webhook Biteship **hanya kirim kalau ada order pengiriman**. Caranya:

1. Buat pesanan di website sampai status **Lunas**.
   - Bisa lewat checkout + upload bukti + Admin setujui.
2. Login Admin → **Pengiriman → Input Pengiriman**.
3. Isi kurir (mis. JNE) & layanan (REG), simpan.
4. Sistem membuat order di Biteship (kalau API key sudah ada) atau menyimpan data manual.
5. Cek:
   - Biteship → **Integrasi → Webhook → Event Log** → status `200`.
   - Halaman pelanggan → **Lacak Pengiriman** → timeline bertambah.
   - Log: `storage/logs/laravel.log`.

### Uji cepat tanpa order
Bisa kirim POST manual ke webhook (misal dari Postman):
```
POST https://silence-furniture-gale-sculpture.trycloudflare.com/shipping/biteship/webhook
Header: Tabita-Modiste-Signature: <token dari .env>
Body (JSON): {"event":"order.status","status":"inTransit"}
```
Harus balas:
```
OK
```
Ini hanya menguji keterhubungan URL & signature, **bukan mensimulasikan paket asli**.

---

## 6. Keterbatasan mode gratis ini

| Hal | Penjelasan |
|---|---|
| URL berubah | Hanya kalau tunnel dibuat ulang → update webhook di Biteship. Script otomatis memakai ulang URL aktif. |
| Harus menyala | Server + tunnel harus aktif saat uji |
| Bukan untuk transaksi nyata | Jangan dipakai client untuk pembayaran produksi |
| API key kosong | Ongkir masih manual sampai key diisi |
| Peta | Tetap jalan tanpa key (timeline tampil, peta pesan belum tersedia) |

---

## 7. Setelah hosting + domain aktif

Tinggal ganti di Biteship:

```
https://tokotabita.id/shipping/biteship/webhook
```

Dan di `.env` produksi:

```dotenv
APP_URL=https://tokotabita.id
BITESHIP_WEBHOOK_URL=https://tokotabita.id/shipping/biteship/webhook
BITESHIP_API_KEY=live_key
```

Tidak ada source code yang diubah. Panduan lengkap ada di `docs/DEPLOY-SHARED-HOSTING.md`.

---

## 8. Cara menghentikan sementara

Tutup terminal/jendela server & tunnel, atau:

```powershell
Get-NetTCPConnection -LocalPort 8000 -State Listen | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force }
Get-Process npx,cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force
```
