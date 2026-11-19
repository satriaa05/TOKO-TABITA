# Ringkasan Percakapan & Handover — Tabita Modiste

> **CARA PAKAI DI CHAT BARU**
> Copy seluruh isi file ini, lalu tempel di pesan pertama chat baru, lalu tambahkan:
> "Lanjutkan dari kondisi ini."
>
> Dokumen ini dibuat 18 November 2026. Baca juga `AGENTS.md` di root project.

---

## 1. Tujuan awal

User (Indonesia, non-teknis) ingin:

1. Revisi header/footer toko: hapus announcement bar, footer Instagram-only, logo pembayaran asli, lokasi Semarang, pengaturan dinamis Owner.
2. Sistem checkout modern ala marketplace (tanpa menyalin desain platform lain).
3. Pembayaran: QRIS dinamis, transfer bank, e-wallet, webhook terverifikasi, idempotent, fallback manual.
4. Status pembayaran terpisah dari status pengiriman.
5. Tracking pengiriman + timeline + peta gratis Leaflet + OpenStreetMap + estimasi tiba.
6. Notifikasi pelanggan (in-app + email).
7. Dashboard Admin (pembayaran & pengiriman) dan Owner (monitoring + gangguan integrasi).
8. ERD, workflow Mermaid, dokumentasi provider, laporan testing.
9. Deploy ke hosting (bukan Netlify).

**Pattern kerja yang diminta user:** audit dulu, rancangan dulu, tunggu persetujuan, baru coding. User sering memberi instruksi pendek: "lanjut", "siapkan...", "pilih mana".

---

## 2. Keputusan arsitektur yang sudah disetujui

- **Tidak membuat ulang** sistem transaksi. `sales` tetap menjadi order master (bukan tabel `orders` baru).
- **Tidak ada database duplikat.** Tabel baru bersifat tambahan/migrasi aman.
- Semua perhitungan (harga, diskon, ongkir, voucher) di **backend**.
- `sales.status` lama dipertahankan karena laporan Owner memakai `completed` sebagai pendapatan. Ditambahkan:
  - `sales.payment_status` → UNPAID/PENDING/PAID/FAILED/EXPIRED/REFUNDED
  - `sales.fulfillment_status` → PENDING/PROCESSING/PACKED/SHIPPED/DELIVERED/CANCELLED
- **Satu-satunya tempat yang boleh menandai PAID** = `App\Services\PaymentService`.
  Sumber: webhook terverifikasi, fallback GET status API, atau verifikasi manual Admin.
  Redirect browser **tidak pernah** jadi bukti pembayaran.
- Gateway awal: **Midtrans**. Karena pendaftaran Midtrans mentok (nomor HP "taken" di ekosistem GoTo), direncanakan pindah ke **Tripay** (server masih maintenance saat dokumen dibuat).
- Pengiriman: **Biteship**. GPS live **hanya** untuk kurir instant (GoSend/GrabExpress) via `courier.link` resmi. Reguler (JNE/SiCepat) memakai **lokasi scan** — tidak ada marker bergerak palsu.
- Peta: **Leaflet + OpenStreetMap** — geocoding Nominatim + routing OSRM, tanpa API key/kartu kredit. Hasil di-cache; atribusi OSM wajib tampil.
- Email notifikasi awalnya queue, lalu **diubah jadi sinkron** karena hosting yang dipilih membatasi cron minimal 15 menit.
- Deploy: **shared hosting Rumahweb paket Unlimited M**, bukan Netlify, bukan cPanel WHM trial.

---

## 3. Status implementasi (SELESAI)

### 3.1 Header/footer & Owner settings (tahap awal)
- Announcement bar dihapus, navbar sticky tanpa gap atas.
- Footer gelap: identitas Women's,链接 Shop/Customer Care/About Us/Contact Us, Semarang, satu ikon Instagram (nonaktif jika URL kosong), logo pembayaran SVG.
- `app/Services/StoreProfile.php` + helper `store_profile()` — sumber tunggal identitas toko, WA, IG, metode bayar.
- Owner `/owner/pengaturan` untuk nama toko, tagline, deskripsi, WA, IG, lokasi, alamat, jam buka, metode aktif, upload logo.

### 3.2 Fondasi data & checkout modern
- 12 migration baru (2026_11_17_000001 s/d 000012): status sales, verifikasi proof, payment_methods, payment_transactions, payment_webhook_logs, customer_addresses, vouchers + voucher_usages, shipments, shipment_tracking_events, shipment_locations, order_status_histories, customer_notifications.
- Enum baru: `PaymentStatus`, `FulfillmentStatus`, `ShipmentStatus` (mapping `inTransit`, `droppingOff`, `pickingUp`, dll), `PaymentProofStatus`, `VoucherType`, `NotificationType`.
- Model baru: `PaymentChannel` (tabel `payment_methods`, sengaja bukan `PaymentMethod` agar tidak bentrok dengan enum POS), `PaymentTransaction`, `PaymentWebhookLog`, `CustomerAddress`, `Voucher`, `VoucherUsage`, `Shipment`, `ShipmentTrackingEvent`, `ShipmentLocation`, `OrderStatusHistory`, `CustomerNotification`.
- `OnlineOrderService::place()` menerima opsi `payment_method`, `voucher_code`, `address_id`, `courier`, `service`; membuat transaksi + voucher usage + history + notifikasi dalam satu DB transaction.
- Checkout modern: alamat tersimpan/baru, kurir Grab/JNE/SiCepat, metode aktif dengan logo, voucher AJAX, ringkasan sticky.
- Halaman pembayaran: QR/instruksi, nominal, no. pesanan, expired, upload bukti, cek status.
- Alamat CRUD: `/alamat`.

### 3.3 Pembayaran & verifikasi
- `app/Services/MidtransService.php` — charge Core API (QRIS/e-wallet/VA), signature `SHA512(order_id+status_code+gross_amount+ServerKey)`, fallback `GET /v2/{id}/status`.
- `app/Services/PaymentService.php` — `handleWebhook()`, `markPaid()`, `markFailed()`, `markExpired()`, idempotensi via event_id + status PAID, sanitasi payload, audit log.
- Webhook: `POST /payments/midtrans/webhook` (CSRF dikecualikan di `bootstrap/app.php`).
- Admin Pembayaran: `/admin/pembayaran`, setujui/tolak bukti manual, sinkron gateway, riwayat webhook. **Admin dilarang PAID-kan transaksi otomatis secara manual.**
- Email: `PaymentStatusMail` **sinkron** (tidak ShouldQueue), try/catch agar email gagal tidak menggagalkan pembayaran.

### 3.4 Pengiriman, tracking, peta
- `app/Services/BiteshipService.php` — create order, sync status, mapping status, dedupe tracking event, koordinat valid saja, webhook `order.status`/`order.waybill_id`/`order.price` + verifikasi header `Tabita-Modiste-Signature`.
- `app/Services/GoogleMapsService.php` — geocode + Routes API `computeRoutes` dengan field mask.
- Admin Pengiriman: `/admin/pengiriman`, input kurir/service/resi, update status, sinkron Biteship, timeline.
- Customer: `/pesanan-saya/{sale}/lacak` — timeline, ETA, nomor resi, status, peta Leaflet/OpenStreetMap dengan titik asal/tujuan/scan + perkiraan rute. Tanpa API key.
- **Real-time tracking**: halaman melakukan polling tiap **15 detik** ke `/pesanan-saya/{sale}/lacak/partial`, sehingga status, timeline, lokasi terakhir, dan riwayat lokasi diperbarui otomatis dari database server. Admin bisa menginput **lokasi, koordinat, dan waktu kejadian** tiap scan manual. Peta menampilkan semua titik scan yang memiliki koordinat valid. Untuk kurir instant, `courier.link` Biteship ditampilkan sebagai iframe GPS live resmi.

### 3.5 Monitoring & notifikasi (tambahan terakhir)
- Owner: `/owner/monitoring` — metrik transaksi, pembayaran, pengiriman, ongkir, estimasi keuntungan, performa metode bayar, gangguan integrasi, webhook terakhir, filter periode.
- Notifikasi in-app: lonceng + badge di header toko, halaman `/notifikasi`, tandai dibaca / semua dibaca.

### 3.6 Dokumen deploy
- `docs/DEPLOY-SHARED-HOSTING.md` — panduan lengkap cPanel.
- `docs/env.production.example` — template `.env` produksi.
- Update terakhir: email sinkron, cron opsional `*/15 * * * *`, PHP **8.4.1+**, ekstensi `pdo_mysql, fileinfo, gd, mbstring, openssl, tokenizer, xml, exif`.
- `docs/screenshots/` — screenshot footer desktop & mobile.

---

## 4. Test & kualitas

- **Terakhir dijalankan (PHP 8.5.12 + GD):** 143 test, **143 passed, 0 skipped**, 648 assertion.
- Test baru: `PaymentFoundationTest`, `CheckoutModernTest`, `PaymentVerificationTest`, `MidtransChargeTest`, `TrackingTest`, `MonitoringTest`, `NotificationTest`, `StorefrontChromeTest`.
- Pint lolos untuk file baru. **Catatan:** `StoreController.php` dan `RouteHealthMatrixTest.php` masih punya masalah format Pint **warisan** (sengaja tidak di-reformat menyeluruh agar diff tidak membengkak).
- `vendor/composer/platform_check.php` mensyaratkan **PHP >= 8.4.1**. PHP lokal yang dipakai: **8.5.0** dari Herd Lite (`C:\Users\LENOVO\.config\herd-lite\bin\php.exe`). Cmd.exe memakai PHP 8.1.10 → error Composer. **Selalu pakai PowerShell.**

---

## 5. Masalah yang sudah dipecahkan

| Masalah | Kesimpulan |
|---|---|
| Netlify deploy gagal (`dist` vs `public/build`) | Netlify **tidak bisa** menjalankan Laravel (tanpa PHP/MySQL). Error Vite hanya gejala. |
| cPanel & WHM Free Trial | Bukan hosting. Lisensi panel untuk VPS. Jangan dibeli. |
| Harga cPanel $27,46/bulan | Itu lisensi VPS-only, bukan shared hosting. |
| Midtrans "Business phone number has been taken" | Nomor sudah terdaftar di ekosistem GoTo (GoPay/GoBiz/GoFood). Gmail sebenarnya boleh; masalahnya nomor. |
| Tripay | Server sedang maintenance → adapter belum dibuat. Rencana: `TripayService` menggantikan `MidtransService`, `PaymentService` tidak diubah. |
| GD lokal mati | **SUDAH DIAKTIFKAN** (18 Nov 2026) via PHP 8.5.12 NTS resmi di `C:\Users\LENOVO\php-8.5-gd\php.exe` (Herd Lite tidak diubah). Ekstensi: gd, pdo_mysql, pdo_sqlite, mbstring, openssl, curl, fileinfo, exif, intl. Test yang sebelumnya diskip kini ikut jalan. |
| PHP 8.1 error di Cmd.exe | Pakai PowerShell (PHP 8.5). Tidak perlu ulang `npm install` / `npm run build` — `public/build/manifest.json` sudah ada. |

---

## 6. Kondisi PEMBELIAN HOSTING (saat dokumen dibuat)

User sedang di halaman **Cart Rumahweb** (step 5/5), belum membayar:

| Item | Total |
|---|---|
| Domain `tabitamodiste.com` (1 tahun, diskon) | Rp147.000 |
| `.site` bonus | Rp0 |
| `.space` bonus | Rp0 |
| Hosting **Unlimited M — 1 Bulan** | Rp110.000 |
| Fee | Rp2.570 |
| PPN 11% | Rp28.553 |
| **TOTAL** | **Rp288.123** |

Keputusan:
- Paket **Unlimited M** dipilih karena ada **SSH**; Entry & Unlimited S tidak ada SSH.
- Periode **1 Bulan** dipilih agar murah di depan (Rp110.000); promo tahunan Rp29.900/bulan tidak diambil.
- CS Rumahweb mengonfirmasi **PHP 8.4 tersedia** (screenshot "8.4 (current)").
- Cron minimal paket Medium = **15 menit**; sudah diatasi dengan email sinkron. Tidak perlu paket Large.
- **ID Protection (+Rp10.000) bisa dimatikan** dengan kembali ke step 1 Domain → matikan toggle → total jadi ~Rp278.000.
- Addons (Titan Email, Sitepro, SSL Premium, RankingCoach) **semua dilewati**.
- Domain `.site` dan `.space` adalah bonus gratis, tidak apa-apa.
- Setelah bayar → lanjut deploy mengikuti `docs/DEPLOY-SHARED-HOSTING.md`.

---

## 7. Yang masih tertunda / langkah berikutnya

1. **Selesaikan pembayaran hosting** (atau matikan ID Protection dulu).
2. **Deploy** setelah cPanel aktif:
   ```
   MySQL Databases → Select PHP Version (8.4) → Domains (Document Root=/public)
   → File Manager (upload + .env) → Terminal/SSH (composer, migrate, storage:link, cache)
   → SSL/TLS Status → Cron opsional
   ```
3. Isi `.env` produksi dari `docs/env.production.example` (Midtrans/Biteship/Maps/Resend/rekening).
4. Daftarkan webhook:
   - Midtrans: `https://DOMAIN/payments/midtrans/webhook`
   - Biteship: `https://DOMAIN/shipping/biteship/webhook`, header `Tabita-Modiste-Signature`
5. **Tripay** — tunggu server pulih, lalu buat `TripayService` + webhook adapter.
6. **GD** — aktifkan di `Select PHP Version` kalau mau (opsional).
7. Integrasi live Midtrans/Biteship/Resend **belum teruji** karena kredensial belum ada. Peta Leaflet/OpenStreetMap sudah aktif & teruji tanpa kredensial.

---

## 8. File penting (path relatif project)

```
app/Services/StoreProfile.php
app/Services/OnlineOrderService.php
app/Services/MidtransService.php        (rencana → TripayService)
app/Services/PaymentService.php
app/Services/BiteshipService.php
app/Services/GoogleMapsService.php
app/Mail/PaymentStatusMail.php
app/Http/Controllers/StoreController.php
app/Http/Controllers/Store/CustomerAddressController.php
app/Http/Controllers/Store/NotificationController.php
app/Http/Controllers/Payment/MidtransWebhookController.php
app/Http/Controllers/Shipping/BiteshipWebhookController.php
app/Http/Controllers/Admin/PaymentController.php
app/Http/Controllers/Admin/ShipmentController.php
app/Http/Controllers/Owner/MonitoringController.php
resources/views/layouts/store.blade.php
resources/views/store/{checkout,payment,tracking,order-detail}.blade.php
resources/views/store/addresses/*
resources/views/store/notifications/index.blade.php
resources/views/admin/payments/*
resources/views/admin/shipments/*
resources/views/owner/monitoring/index.blade.php
config/pos.php, config/payments.php, config/shipping.php, config/navigation.php
routes/web.php
docs/DEPLOY-SHARED-HOSTING.md
docs/env.production.example
docs/screenshots/
tests/Feature/... (lihat daftar di bagian 4)
```

---

## 9. Perintah penting

```powershell
# Selalu di PowerShell, jangan Cmd.exe
cd "C:\Users\LENOVO\Documents\TOKO TABITA"
php -v                       # harus 8.5.x
npm run build                # sudah pernah dijalankan; public/build ada
php artisan test             # 167 passed, 815 assertions
php artisan view:clear
php artisan serve
npx cloudflared tunnel --url http://localhost:8000   # HTTPS gratis untuk webhook
```

---

## 10. Pesan gaya bicara user

- Bahasa Indonesia, santai, kadang singkat: "lanjut", "ini apa", "pilih mana", "wkwk salah liat".
- Butuh penjelasan praktis langkah demi langkah, bukan teori panjang.
- Mudah bingung dengan angka/format harga — selalu jelaskan satuan (ribu vs juta) dan format titik/koma Indonesia.
- Lebih nyaman kalau asisten yang mengambil keputusan teknis dan memberi instruksi "klik ini, isi itu".

---

## 11. Revisi terakhir (19 Nov 2026, sesi lanjutan)

- Teks notifikasi pengambilan paket diganti persis: **"Paket diserahkan ke kurir"**.
- Halaman **Tentang Kami** kini diedit dari **Owner → Pengaturan Bisnis** (7 field `about_*`).
- Detail pesanan pembeli menampilkan rincian: **Subtotal − Diskon + Ongkir = Total**.
- Invoice/cetak struk tersedia untuk **Admin & Owner** di `/invoice/{sale}`.
- Test: **147 passed / 662 assertions**, audit browser 0 console error.
- Screenshot baru: `docs/screenshots/revisi-*.png`.
- Panduan singkat: `docs/PANDUAN-OWNER-TENTANG-INVOICE.md`.

---

## 12. Multi-user & galeri varian (19 Nov 2026, lanjutan)

- Keranjang terikat `user_id`; akun berbeda di browser sama tidak melihat keranjang lama.
- Invoice unik + idempotensi checkout diperkuat; double-post tidak membuat pesanan ganda.
- Stok produk & varian dipotong sekali dalam transaksi + `lockForUpdate`; gagal/expired/refund
  mengembalikan stok tepat sekali lewat `StockReleaseService` + `stock_released_at`.
- Webhook Midtrans idempoten dan fallback GET status diperbaiki (QRIS tetap tanpa perubahan fungsi).
- IDOR halaman sukses pelanggan ditutup.
- Galeri foto per warna: swatch, swipe, prev/next, indikator `1 / n`, thumbnail warna.
- Admin & Owner bisa CRUD warna, varian, stok per `warna × ukuran`, dan foto; Owner kini
  memiliki akses produk penuh.
- Warna yang dibeli tersimpan di `sale_items` dan tampil di cart/checkout/invoice/riwayat.
- Test: **167 passed / 815 assertions**; audit browser 0 console error.
- Konkurensi HTTP nyata: 5 server + 5 akun checkout bersamaan → 1 invoice, stok 0, tanpa oversell
  (`scripts/concurrency-checkout.ps1`).
- Perbaikan keranjang: hapus satu baris & tambah qty berfungsi, termasuk key lama.
- Owner default direset: `owner@tokotabita.test` / `Tabita-Owner-123`.
- Panduan: `docs/PANDUAN-MULTIUSER-GALERI.md`.

