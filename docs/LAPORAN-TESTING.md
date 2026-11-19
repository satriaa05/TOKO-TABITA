# Laporan Testing — Tabita Modiste

> Tanggal: 18 November 2026  
> Perintah (dengan GD): `& 'C:\Users\LENOVO\php-8.5-gd\php.exe' artisan test` di **PowerShell** — PHP **8.5.12 + GD aktif**.  
> Hasil terakhir (19 November 2026): **177 test · 177 passed · 0 skipped · 851 assertions**  
> (mencakup state machine pembayaran, default-deny permission, dan audit log)

---

## 1. Ringkasan

| Status | Jumlah | Keterangan |
|---|---|---|
| **PASS** | 177 | Semua test otomatis yang dijalankan lolos (dengan GD aktif) |
| **FAIL** | 0 | Tidak ada test yang gagal |
| **SKIPPED** | 0 | Tidak ada test yang diskip setelah GD diaktifkan |
| **BLOCKED** | 6 skenario live | Butuh kredensial Midtrans/Biteship/Resend; peta gratis sudah aktif |

> **Catatan kejujuran:** fitur gateway & tracking otomatis **belum diuji dengan penyedia sungguhan**
> karena kredensial belum tersedia. Yang terbukti adalah logika validasi, idempotensi,
> fallback manual, dan seluruh test otomatis dengan HTTP/sandbox palsu.

---

## 2. Pemetaan 20 skenario yang diminta

| No | Skenario | Status | Bukti / Catatan |
|---|---|---|---|
| 1 | Checkout berhasil | **PASS** | `CheckoutModernTest`, `BuyerJourneyTest` |
| 2 | Checkout dengan stok habis | **PASS** | `CheckoutModernTest::test_checkout_stok_habis_ditolak` |
| 3 | Pembayaran QRIS berhasil | **PASS (simulasi)** / **BLOCKED (live)** | `MidtransChargeTest`, webhook settlement di `PaymentVerificationTest` |
| 4 | Pembayaran QRIS gagal | **PASS (simulasi)** / **BLOCKED (live)** | Webhook `deny` → `PaymentStatus::Failed` |
| 5 | Pembayaran kedaluwarsa | **PASS (simulasi)** / **BLOCKED (live)** | Webhook `expire` → `PaymentStatus::Expired` |
| 6 | Webhook valid | **PASS** | Signature_SHA512 cocok → PAID, 1 `Payment` row |
| 7 | Webhook signature tidak valid | **PASS** | Ditolak, dicatat `invalid_signature` |
| 8 | Webhook duplikat | **PASS** | `event_id` unik → tidak memproses ulang |
| 9 | Nominal pembayaran tidak cocok | **PASS** | Webhook ditolak, status tetap UNPAID |
| 10 | Pembayaran manual | **PASS** | Upload bukti → Admin setujui → PAIN; Admin tidak bisa setujui transaksi otomatis |
| 11 | Input nomor resi | **PASS** | `TrackingTest::test_admin_membuat_pengiriman_dan_memperbarui_status` |
| 12 | Tracking berhasil | **PASS (manual)** / **BLOCKED (API live)** | Event tracking tersimpan & tampil di timeline |
| 13 | Tracking gagal | **PASS** | Webhook Biteship signature invalid ditolak; error sync ditangani |
| 14 | Data lokasi tidak tersedia | **PASS** | Timeline tetap tampil; koordinat bisa diisi manual Admin |
| 15 | Estimasi pengiriman tersedia | **PASS** | `Shipment::etaLabel()` dipakai di halaman tracking |
| 16 | Estimasi tidak tersedia | **PASS** | Tampil "Belum tersedia" bila ETA null |
| 17 | Pelanggan melihat pesanan sendiri | **PASS** | `BuyerJourneyTest`, `CheckoutModernTest` |
| 18 | Pelanggan tidak melihat pesanan orang lain | **PASS** | 403 di pembayaran, tracking, notifikasi, alamat |
| 19 | Admin mengelola pengiriman | **PASS** | `TrackingTest` — buat shipment, update status |
| 20 | Owner memantau transaksi | **PASS** | `MonitoringTest` — metrik & gangguan integrasi |

---

## 3. Daftar file test

| File | Fokus |
|---|---|
| `tests/Feature/Store/StorefrontChromeTest.php` | Header/footer, Instagram, Semarang, pengaturan Owner |
| `tests/Feature/Store/BuyerJourneyTest.php` | Jalur pembeli katalog → checkout → bukti |
| `tests/Feature/Store/CheckoutModernTest.php` | Alamat, voucher, metode aktif, stok habis, otorisasi |
| `tests/Feature/Store/NotificationTest.php` | Notifikasi in-app & otorisasi |
| `tests/Feature/Payment/PaymentFoundationTest.php` | Migration/status/relasi/idempotensi/voucher |
| `tests/Feature/Payment/PaymentVerificationTest.php` | Webhook valid/invalid/duplikat/nominal/expired, verifikasi Admin |
| `tests/Feature/Payment/MidtransChargeTest.php` | Charge QRIS dengan `Http::fake` |
| `tests/Feature/Shipping/TrackingTest.php` | Shipment, status timeline, peta tanpa key, webhook Biteship |
| `tests/Feature/Owner/MonitoringTest.php` | Dashboard monitoring & akses role |
| `tests/Feature/Smoke/RouteHealthMatrixTest.php` | Semua route GET tidak balas 500 & tercakup daftar uji |

---

## 4. Hasil verifikasi manual (non-otomatis)

| Item | Hasil |
|---|---|
| Build Vite (`npm run build`) | **PASS** — `public/build/manifest.json` ada |
| PHP syntax seluruh file baru | **PASS** |
| Pint pada file baru | **PASS** (catatan: `StoreController.php` & `RouteHealthMatrixTest.php` punya format warisan) |
| Migration MySQL lokal Laragon | **PASS** — 12 migration tambahan berhasil |
| Seeder `PaymentChannelSeeder` | **PASS** — 7 metode, hanya `transfer` aktif |
| Error PHP 8.1 di Cmd.exe | **Diatasi** — pakai PowerShell/PHP 8.5 |

---

## 5. Skenario BLOCKED (butuh kredensial/akses)

| Skenario | Yang dibutuhkan |
|---|---|
| QRIS dinamis live | `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, notification URL HTTPS |
| Webhook Midtrans sungguhan | Sandbox Midtrans + tunnel HTTPS |
| Fallback GET status live | Kredensial Midtrans |
| Biteship ongkir & tracking live | `BITESHIP_API_KEY`, webhook token |
| Peta Leaflet/OpenStreetMap live | ? Sudah aktif, tanpa API key |
| Email Resend live | `RESEND_API_KEY` + domain terverifikasi |

> Setelah kredensial tersedia, jalankan ulang test + uji sandbox, lalu ubah status
> BLOCKED → PASS di tabel atas. Jangan mengklaim otomatis sebelum itu.

---

## 6. Cara mengulang test

```powershell
cd "C:\Users\LENOVO\Documents\TOKO TABITA"
php -v                                              # Herd Lite 8.5.0 (tanpa GD)
& 'C:\Users\LENOVO\php-8.5-gd\php.exe' -v           # 8.5.12 + GD aktif
npm run build          # hanya jika asset berubah
php artisan view:clear
& 'C:\Users\LENOVO\php-8.5-gd\php.exe' artisan test
```

Ringkasan akhir akan muncul seperti:

```
Tests: 177 passed (851 assertions)
```

---

## 7. Catatan untuk sesi berikutnya

- Jangan pakai **Cmd.exe** untuk perintah PHP — memakai PHP 8.1.10 dan error Composer.
- Jangan ulang `npm install` hanya karena ganti terminal; `node_modules` dan `public/build` sudah ada.
- Saat pindah ke provider pembayaran lain (Tripay), `PaymentService` **tidak diubah** — cukup ganti service + webhook + config.
- Hosting yang dipilih: **Rumahweb Unlimited M**, PHP 8.4 dikonfirmasi, SSH ada, cron 15 menit.
- Email sudah dibuat **sinkron**, jadi tidak bergantung queue worker.

---

## 8. Revisi lanjutan 19 November 2026 — PASS

| Perubahan | Status | Bukti |
|---|---|---|
| Teks notifikasi "Paket diserahkan ke kurir" | **PASS** | Enum baru `OrderHandedToCourier`, `ShipmentNotificationService`, simulasi webhook `picked` |
| Konten halaman Tentang bisa diedit Owner | **PASS** | `tests/Feature/Store/AboutAndInvoiceTest.php`; screenshot `revisi-owner-settings-about-bawah.png`, `revisi-tentang-editable.png` |
| Rincian harga di pesanan pembeli (subtotal − diskon + ongkir) | **PASS** | Screenshot `revisi-order-detail-breakdown.png` |
| Invoice/cetak struk Admin & Owner | **PASS** | `tests/Feature/Store/AboutAndInvoiceTest.php`; screenshot `revisi-invoice.png` |
| Akses invoice ditolak untuk pembeli | **PASS** | `test_pembeli_tidak_bisa_mencetak_invoice` → 403 |

Audit browser: halaman pengaturan Owner, Tentang, invoice, dan detail pesanan dibuka dengan **0 console error**.
Data uji visual (settings `about_*`, item simulasi, akun `visual.owner@tokotabita.test`) sudah dihapus.

---

## 9. Multi-user & galeri varian (19 November 2026) — PASS

| Skenario | Status | Bukti |
|---|---|---|
| 5 akun belanja bersamaan (keranjang, ukuran, qty, ekspedisi, checkout) | **PASS** | `MultiUserCheckoutTest::test_lima_pembeli_mendapatkan_keranjang_dan_pesanan_terpisah` |
| Keranjang tidak tertukar saat akun berganti di satu browser | **PASS** | `test_keranjang_tidak_tertukar_saat_akun_berganti_di_satu_browser` |
| Double-post idempotency key → 1 invoice, stok dipotong sekali | **PASS** | `test_double_post_dengan_idempotency_key_sama_hanya_memotong_stok_satu_kali` |
| Stok terakhir tidak bisa dibeli dua pembeli | **PASS** | `test_stok_terakhir_tidak_bisa_dibeli_oleh_dua_pembeli` |
| Expired mengembalikan stok varian + produk tepat sekali | **PASS** | `test_pembayaran_expired_mengembalikan_stok_varian_hanya_satu_kali` |
| Bukti pembayaran tidak tertukar | **PASS** | `test_bukti_pembayaran_melekat_pada_pesanan_pemiliknya` |
| IDOR halaman sukses ditutup | **PASS** | `test_pembeli_tidak_bisa_membuka_halaman_sukses_pesanan_orang_lain` |
| Admin & Owner melihat semua pesanan tanpa pencampuran | **PASS** | `test_admin_dan_owner_melihat_semua_pesanan_tanpa_mencampurkan_data` |
| Fallback status Midtrans tanpa `signature_key` | **PASS** | `PaymentVerificationTest::test_fallback_status_gateway_tanpa_signature_key_tetap_memproses_pembayaran` |
| 4 warna × 2 foto, swatch, swipe, indikator | **PASS** | `ProductVariantGalleryTest` (7 test) |
| Admin & Owner CRUD warna/varian/galeri | **PASS** | `ProductVariantGalleryTest` |
| Warna tersimpan sampai cart → checkout → invoice | **PASS** | `test_warna_terpilih_tersimpan_di_keranjang_checkout_sale_item_dan_invoice` |
| Hapus 1 baris keranjang, tambah qty, legacy key | **PASS** | `CartOperationsTest` (4 test) |

Audit browser: 0 console error di halaman produk desktop/mobile, admin edit produk, dashboard Owner,
dan keranjang setelah hapus/tambah (`docs/screenshots/cart-hapus-tambah-fixed.png`).
Screenshot: `docs/screenshots/multiuser-produk-galeri-*.png`, `multiuser-admin-varian-galeri.png`, `multiuser-owner-produk.png`.

### Konkurensi HTTP sungguhan — PASS

Dijalankan dengan **5 proses server** (`scripts/concurrency-checkout.ps1`) pada port 8101–8105,
SQLite WAL + `DB_BUSY_TIMEOUT=5000`, lima akun, satu varian stok 1, kelima checkout dilepas
bersamaan via `curl_multi`:

```text
sales_created : 1
variant_stock : 0
product_stock : 0
oversell      : false
http_statuses : [302, 302, 302, 302, 302]
result        : PASS
```

Satu pembeli diarahkan ke `/pembayaran/{id}`, empat pembeli kembali ke `/checkout` dengan pesan
stok tidak cukup. Tidak ada HTTP 500, invoice tidak duplikat, stok tidak minus. Skrip:
`scripts/concurrency-checkout.php` + `scripts/concurrency-checkout.ps1`.

> QRIS live tetap **BLOCKED** sampai kredensial Midtrans diisi.

---

## 10. Keamanan & state machine pembayaran (19 November 2026) — PASS

| Perubahan | Status | Bukti |
|---|---|---|
| Token webhook Biteship dirotasi & tidak lagi literal di script/docs | **PASS** | `.env`, `scripts/tunnel-lokal.ps1`, `docs/PANDUAN-TUNNEL-LOKAL.md` |
| Trusted proxy default deny (tidak mempercayai `X-Forwarded-*` sembarangan) | **PASS** | `bootstrap/app.php`, env `TRUSTED_PROXIES` |
| Cookie session produksi `Secure` + terenkripsi | **PASS** | `docs/env.production.example` |
| `SEED_DEMO=false` di Render & Docker | **PASS** | `render.yaml`, `docker/entrypoint.sh` |
| Webhook Midtrans gagal tercatat di `PaymentWebhookLog` | **PASS** | `MidtransWebhookController`, `test_webhook_yang_melempar_exception_dicatat_di_log_admin` |
| Webhook Biteship gagal/invalid juga tercatat | **PASS** | `BiteshipWebhookController` |
| `expire/deny` tidak menimpa transaksi LUNAS | **PASS** | `PaymentStateMachineTest` |
| `settlement` setelah `expired` memotong stok ulang sekali | **PASS** | `test_settlement_setelah_expired_memotong_stok_ulang` |
| Charge Midtrans anti-double (cache lock) | **PASS** | `MidtransService::charge()` |
| QR kedaluwarsa bisa dibuat ulang via tombol | **PASS** | `PaymentStateMachineTest` (2 test) |
| Scheduler `payments:sync-pending` tiap 10 menit | **PASS** | `routes/console.php`, `app/Console/Commands/SyncPendingPayments.php` |
| Permission default-deny saat `role_permissions` kosong | **PASS** | `PermissionDefaultDenyTest` (4 test) |
| Halaman Jejak Audit bisa dibuka Admin & Owner | **PASS** | `PermissionDefaultDenyTest`, `RouteHealthMatrixTest` |

> **Tindakan user:** token webhook Biteship sudah berubah. Buka `.env`, ambil nilai
> `BITESHIP_WEBHOOK_TOKEN`, lalu update header secret di dashboard Biteship.
> Jangan menempelkan token tersebut ke chat atau commit.

