# Laporan Audit & Perbaikan — Tabita Modiste

> Tanggal: 18 November 2026  
> Project: `C:\Users\LENOVO\Documents\TOKO TABITA`  
> Hasil test terakhir: **136 test · 136 passed · 0 skipped · 627 assertions**

---

## 1. Daftar file yang diubah

### Pesanan & pembayaran Admin
- `app/Http/Controllers/Admin/OrderController.php` — statistik daftar pesanan, relasi更长 (foto produk, transaksi, shipment), konfirmasi pembayaran lewat `PaymentService`.
- `resources/views/admin/orders/index.blade.php` — 6 kartu statistik + tabel lengkap (qty, subtotal, ongkir, total, ekspedisi).
- `resources/views/admin/orders/show.blade.php` — foto produk, varian, rincian ongkir, **Informasi Pengiriman terpisah dari Catatan Pembeli**, status pembayaran & pengiriman, modal konfirmasi (tanpa `confirm()`).
- `app/Http/Controllers/Admin/PaymentController.php` — kirim flag `gatewayConfigured` ke view.
- `resources/views/admin/payments/show.blade.php` — modal setujui/tolak bukti, riwayat webhook lengkap & jujur, Detail Transaksi teknis terpisah dari Detail Pesanan.

### Hak akses & profil
- `database/migrations/2026_11_18_000001_create_permissions_tables.php` — tabel `permissions` + `role_permissions`.
- `database/migrations/2026_11_18_000002_shift_legacy_timestamps_to_wib.php` — koreksi timestamp lama UTC → WIB (sekali jalan, MySQL).
- `database/seeders/PermissionSeeder.php`, `database/seeders/DatabaseSeeder.php` — 10 permission default.
- `app/Http/Middleware/EnsurePermission.php`, `bootstrap/app.php` — middleware `permission:` di backend.
- `app/Models/User.php` — `hasPermission()`, `avatarUrl()`.
- `app/Http/Controllers/ProfileController.php`, `resources/views/profile/index.blade.php` — profil, edit, ganti password, upload/hapus foto.
- `app/Http/Controllers/Owner/UserController.php`, `resources/views/owner/users/index.blade.php`, `resources/views/owner/users/form.blade.php` — manajemen akun & permission Owner.
- `routes/web.php` — route profil & manajemen akun + middleware permission pada route Admin/Owner.
- `config/navigation.php` — menu Profil Saya & Kelola Akun.

### Tampilan & waktu
- `resources/css/app.css` — aturan global tanpa underline pada link/tombol (kecuali harga coret).
- `resources/views/layouts/store.blade.php` — `.st-btn` tanpa underline.
- `config/app.php`, `.env`, `.env.example`, `docs/env.production.example` — `APP_TIMEZONE=Asia/Jakarta`.
- `resources/js/app.js` — jam topbar memakai `Asia/Jakarta`.
- `resources/views/store/checkout.blade.php` — jam checkout real-time (WIB, tick tiap detik).

### Test & dokumentasi
- `tests/Feature/Access/ProfileAndRoleTest.php` (7 test baru).
- `tests/Feature/Smoke/RouteHealthMatrixTest.php` — route baru tercakup.
- `docs/RINGKASAN-PERCAKAPAN.md`, `docs/LAPORAN-TESTING.md`, `docs/ERD.md`, `docs/WORKFLOW.md`.
- `docs/screenshots/audit-*.png` — 6 screenshot hasil perbaikan.

---

## 2. File baru

| File | Fungsi |
|---|---|
| `app/Http/Middleware/EnsurePermission.php` | Cek permission dari database (Owner bypass) |
| `app/Http/Controllers/ProfileController.php` | Profil Admin/Owner |
| `app/Http/Controllers/Owner/UserController.php` | Kelola akun & permission |
| `resources/views/profile/index.blade.php` | UI profil |
| `resources/views/owner/users/index.blade.php`, `form.blade.php` | UI manajemen akun |
| `database/migrations/2026_11_18_000001_create_permissions_tables.php` | Tabel permission |
| `database/migrations/2026_11_18_000002_shift_legacy_timestamps_to_wib.php` | Koreksi waktu lama |
| `database/seeders/PermissionSeeder.php` | 10 permission default |
| `public/assets/images/avatar-default.svg` | Foto profil default |
| `tests/Feature/Access/ProfileAndRoleTest.php` | Test profil & hak akses |

---

## 3. Penjelasan perbaikan

### A. Detail pesanan Admin
- Ekspedisi, layanan, ongkir, nomor resi, status kirim, dan lokasi terakhir kini tampil dari `shipments` + `sales.shipping_provider`.
- Catatan pembeli **dipisahkan** menjadi baris tersendiri di Informasi Pengiriman; jika kosong tampil "Tidak ada catatan khusus".
- Jika resi belum ada → "Belum tersedia", bukan sel kosong.
- Semua produk dalam **satu invoice** tampil dalam satu tabel (foto, ukuran/warna, harga, qty, subtotal).

### B. Daftar pesanan Admin
- Kartu statistik: total, menunggu konfirmasi, diproses, dikirim, selesai, dibatalkan — dihitung dari database.
- Tabel ditambah: WhatsApp, jenis produk, total qty, subtotal, ongkir, total, ekspedisi.

### C. Konfirmasi & webhook
- `confirm()` browser (yang memunculkan judul jendela "OpenCode") **dihapus**, diganti modal Bootstrap dengan nominal yang harus dicocokkan admin.
- Konfirmasi manual tetap memakai `PaymentService::markPaid()` (idempoten, audit, notifikasi). Transaksi otomatis tidak bisa di-PAID-kan manual.
- Riwayat webhook menampilkan waktu, sumber, ID transaksi, event, status proses, signature, keterangan.
- Jika gateway belum dikonfigurasi → tampil pesan **"Belum terhubung — konfigurasi gateway kosong"**, bukan tabel kosong menyesatkan.
- Detail Transaksi kini fokus ke data teknis pembayaran; Detail Pesanan fokus ke barang & pengiriman.

### D. Garis bawah
- CSS global: `a`, `a:hover`, `.btn`, `button`, `.nav-link`, `.st-btn`, sidebar — `text-decoration: none !important`.
- Pengecualian: `.st-price-old` tetap `line-through` (harga coret, memang disengaja).
- Border/garis pemisah antarkomponen tidak dihapus.

### E. Profil Admin/Owner
- Halaman `/profil`: foto, nama, email, WhatsApp, jabatan, tanggal dibuat.
- Edit nama/email/WhatsApp (email unik per akun).
- Ganti password dengan password lama + konfirmasi; hash memakai `Hash::make()` / `Hash::check()`.
- Upload foto JPG/JPEG/PNG/WebP maks 2 MB, preview sebelum simpan, file lama dihapus dari storage.

### F. Pemisahan Admin & Owner
- Middleware `role:` + `permission:` di **backend**.
- Owner selalu diizinkan; Admin hanya permission yang diberikan Owner.
- Owner bisa tambah/edit/nonaktifkan/hapus Admin, tapi **tidak bisa membuat atau mengubah akun Owner**.
- Admin tidak bisa membuka `/owner/*` (403), tidak bisa mengubah role sendiri, tidak punya menu manajemen akun.
- Jika permission belum pernah dikonfigurasi untuk suatu role, sistem fallback ke akses lama agar tidak mengunci seluruh fitur.

### G. Waktu checkout real-time (permintaan tambahan)
- Penyebab: `config/app.php` memakai **UTC**, sehingga jam 19.00 WIB tersimpan/tampil sebagai 12.00.
- Perbaikan: `APP_TIMEZONE=Asia/Jakarta` + migrasi sekali jalan menggeser timestamp lama +7 jam (MySQL, dilindungi flag `timezone_shifted_to_wib_v1`).
- Jam topbar dan jam di halaman checkout sekarang memakai WIB dan berjalan tiap detik.
- Verifikasi: sekarang `now()` → `18 Nov 2026 21:49:58`; detail pesanan menampil `19:08` (waktu transaksi sebenarnya).

---

## 4. Perubahan database & migration

| Migration | Perubahan |
|---|---|
| `2026_11_18_000001_create_permissions_tables.php` | Tabel `permissions` (key unik) + `role_permissions` (unique role+permission) |
| `2026_11_18_000002_shift_legacy_timestamps_to_wib.php` | Geser semua kolom datetime/timestamp +7 jam sekali jalan, hanya MySQL |

Tidak ada tabel lama yang dihapus. Foreign key & data transaksi tetap utuh.

---

## 5. Fitur yang berhasil diuji

| Pengujian | Hasil |
|---|---|
| Login Admin & Owner | ✅ |
| Dashboard & daftar pesanan Admin | ✅ |
| Detail pesanan: ekspedisi, ongkir, resi, status, catatan terpisah | ✅ |
| Perhitungan subtotal − diskon + ongkir = total | ✅ |
| Konfirmasi pembayaran manual (modal, bukan confirm browser) | ✅ |
| Riwayat webhook tanpa gateway → status belum terhubung | ✅ |
| Detail transaksi vs detail pesanan terpisah | ✅ |
| Halaman profil, edit profil, email unik | ✅ |
| Ganti password (password lama salah → ditolak; benar → berhasil) | ✅ |
| Upload foto profil | ✅ |
| Admin tidak bisa akses `/owner/*` | ✅ |
| Owner buat Admin, tolak role owner | ✅ |
| Owner ubah permission Admin | ✅ |
| Tanpa underline pada link/tombol | ✅ (screenshot) |
| Waktu checkout/topbar WIB real-time | ✅ |
| Full suite | ✅ 136/136 |

---

## 6. Fitur yang belum bisa diuji (BLOCKED)

| Fitur | Alasan |
|---|---|
| Webhook Midtrans sungguhan | Kredensial sandbox belum diisi |
| QRIS dinamis live | Server key Midtrans belum ada |
| Tracking Biteship live | API key Biteship belum ada |
| Peta Leaflet/OpenStreetMap | ? Sudah aktif tanpa API key |
| GPS live kurir instant | Bergantung `courier.link` Biteship |
| Email Resend produksi | API key & domain terverifikasi belum ada |

Semua logika, validasi, idempotensi, dan fallback manual sudah diuji dengan test otomatis/HTTP fake.

---

## 7. Error yang ditemukan & penanganan

| Error | Penyebab | Solusi |
|---|---|---|
| `Missing parameter [proof]` di detail pembayaran | Modal dirender saat bukti null | Modal dibungkus `@if ($proof)` |
| `Call to undefined method Controller::middleware()` | Laravel 13 menghapus middleware di controller | Dipindah ke middleware route |
| 403 pada test Media | Permission belum terkonfigurasi di test | Middleware fallback bila role belum punya aturan |
| Jam tampil 12:00 padahal malam | Timezone aplikasi UTC | `Asia/Jakarta` + migrasi timestamp lama |
| Screenshot Owner 403 | Logout GET tidak berfungsi; password Owner lokal berubah | Logout POST + akun Owner audit sementara (sudah dihapus) |

---

## 8. Cara menjalankan ulang via Laragon

1. Buka **Laragon** → start **Apache/Nginx** + **MySQL**.
2. Pastikan `.env` berisi `APP_TIMEZONE=Asia/Jakarta` dan koneksi DB Laragon.
3. Buka terminal di folder project:
   ```powershell
   cd "C:\Users\LENOVO\Documents\TOKO TABITA"
   php artisan migrate --force
   php artisan db:seed --class=PermissionSeeder --force
   npm install
   npm run build
   php artisan serve
   ```
4. Buka `http://127.0.0.1:8000`.
5. Untuktest dengan PHP + GD:
   ```powershell
   & 'C:\Users\LENOVO\php-8.5-gd\php.exe' artisan test
   ```

---

## 9. Akun pengujian

Dari `DatabaseSeeder` (password demo):

| Role | Email | Password |
|---|---|---|
| Admin | `admin@tokotabita.test` | `Tabita-Admin-123` |
| Owner | `owner@tokotabita.test` | `Tabita-Owner-123` |

> Catatan: password di database lokal Anda mungkin sudah diubah. Gunakan akun yang Anda simpan, atau reset lewat tinker:
> `php artisan tinker` → `App\Models\User::where('email','...')->update(['password'=>bcrypt('...')])`.

---

## 10. Screenshot hasil akhir

Tersimpan di `docs/screenshots/`:

- `audit-admin-orders.png` — statistik + tabel pesanan lengkap.
- `audit-admin-order-detail.png` — produk, ongkir, informasi pengiriman terpisah, pembayaran.
- `audit-admin-payment-detail.png` — modal verifikasi & riwayat webhook.
- `audit-profile-admin.png` — halaman profil Admin.
- `audit-owner-users.png` — kelola akun & permission Owner.
- `audit-home-buttons.png` — tombol tanpa garis bawah.

### H. Ongkir real-time Biteship (permintaan terakhir)
- `BiteshipService::rates()` memanggil `POST /v1/rates/couriers` dengan kode pos asal (`TABITA_STORE_POSTAL_CODE`, default 50100) dan kode pos tujuan.
- Endpoint `POST /ongkir/cek` dipakai tombol **Cek Ongkir** di checkout untuk menampilkan pilihan kurir/layanan, harga, dan estimasi dari Biteship.
- Harga **tetap dihitung ulang di server** oleh `ShippingService::quote()` saat checkout; nilai dari browser tidak dipercaya.
- Jika API key belum ada atau gagal → otomatis fallback ke tarif manual `config/pos.php`, tanpa error.
- Test: `RatesTest` (3 test) dengan `Http::fake`.

### I. Perbaikan kurir, ongkir, dan tracking (19 Nov 2026)
- Kurir diganti menjadi **Grab, JNE, SiCepat**; J&T dihapus.
- Layanan hanya **Reguler** (`reg`) dan **Instan** (`instant`). Grab Instan hanya muncul jika tujuan dalam radius 30 km (fallback prefix kode pos jika geocoding gagal).
- Ongkir dari Biteship dibatasi maksimal **Rp17.000** (selisih ditanggung toko) dan **gratis minimal Rp300.000**. Admin tidak bisa mengubah ongkir di form pengiriman.
- Kode pos pembeli disimpan di `sales.buyer_postal_code` dan dipakai saat membuat order Biteship.
- Payload order Biteship diperbaiki ke format datar (`courier_company`, `courier_type`, `delivery_type`, `reference_id`) — sebelumnya HTTP 400.
- Tracking event & status kini tersinkron otomatis dari webhook/API Biteship; waktu pembuatan order uji berhasil menghasilkan event "Pesanan Dikonfirmasi".
- Data historis lokal diperbaiki: ongkir lama >17rb disesuaikan ke 17rb beserta total transaksinya.
- Audit browser 12 halaman: tidak ada console error; satu 403 muncul hanya saat akun Admin membuka tracking pesanan pembeli (perilaku keamanan yang benar).

### J. Peta gratis Leaflet + OpenStreetMap (19 Nov 2026)
- `GoogleMapsService` dihapus, diganti `MapService` (Nominatim + OSRM) tanpa API key/kartu kredit.
- View tracking memakai Leaflet, tile OSM, marker asal/tujuan/scan, polyline rute, dan atribusi wajib.
- Geocoding di-cache 24 jam, routing 6 jam, fallback garis lurus saat OSRM gagal.
- Test baru `MapServiceTest` (4 test). Audit browser: peta tampil, 9 tile, 3 marker, tanpa console error.
- Screenshot: `docs/screenshots/leaflet-tracking.png`.
- Dokumen: `docs/PANDUAN-PETA-GRATIS.md`.

### K. Revisi konten Owner, rincian harga, dan invoice (19 Nov 2026)
- Notifikasi pengambilan paket kini memakai tipe baru `OrderHandedToCourier`
  dengan judul **"Paket Diserahkan ke Kurir"** dan teks **"Paket pesanan {invoice} diserahkan ke kurir."**
  (menggantikan "sudah diambil kurir").
- Halaman **Tentang Kami** tidak lagi hardcode. Owner bisa mengubah eyebrow, judul,
  3 paragraf, judul keunggulan, dan daftar keunggulan lewat
  **Owner → Pengaturan Bisnis → Konten Halaman Tentang Kami**.
  Format daftar keunggulan: `Judul|Deskripsi` per baris. Jika kosong, teks bawaan dipakai.
- Detail pesanan pembeli sekarang menampilkan **Subtotal produk**, **Diskon** (jika ada),
  **Ongkos kirim**, **Total pembayaran**, dan catatan rumus `Total = Subtotal − Diskon + Ongkir`.
  Metode pembayaran juga diambil dari transaksi nyata, bukan daftar statis.
- Invoice/cetak struk ditambahkan di rute `GET /invoice/{sale}` dengan middleware
  `role:admin,owner` + `permission:orders.view`. Tersedia tombol **Cetak Invoice** di
  daftar & detail pesanan Admin, serta tautan pada nomor invoice di Dashboard Owner.
  Tombol **Cetak / Simpan PDF** memakai `window.print()` dengan CSS `@media print` A4.
- Test baru: `AboutAndInvoiceTest` (4 test). Total menjadi **147 test / 662 assertions**.

### L. Multi-user aman + galeri warna/foto (19 Nov 2026)
- **Keranjang** tetap di session server, kini menyimpan `user_id`. Bila akun di satu browser berganti,
  keranjang lama dibuang (tidak diberikan ke akun lain). Guest yang login bisa membawa keranjangnya.
  Kunci barang disanitasi dan rute `keranjang/{key}` tidak lagi memakai regex sempit.
- **Checkout** memindahkan pengecekan idempotensi ke dalam transaksi; bentrok UNIQUE
  `sales.idempotency_key` mengembalikan Sale yang sudah ada (bukan 500 dan tidak memotong stok dua kali).
  Nomor invoice dihitung ulang saat bentrok UNIQUE; `DocumentNumberService` tidak lagi membuka
  transaksi bersarang yang membuat `lockForUpdate` tidak efektif.
- **Stok** kini satu sumber pemotongan di `StockService::apply()`: produk + varian dikunci
  (`lockForUpdate`) dan diubah dalam satu transaksi. `addToCart` juga menolak qty melebihi sisa stok.
- **Pengembalian stok** lewat `StockReleaseService` dengan guard `sales.stock_released_at`:
  webhook failed/expired, refund admin, dan retry webhook tidak pernah mengembalikan stok dua kali.
  Mutasi stok kini mencatat `variant_id`, `color`, dan `size`.
- **Pembayaran**: webhook dedup memakai `firstOrCreate` + unique constraint (aman saat dua webhook
  bersamaan). Fallback `syncFromGateway` diperbaiki: respons GET status Midtrans diverifikasi lewat
  kredensial API + `order_id`/`gross_amount` (signature_key tetap diverifikasi bila ada). QRIS
  tidak berubah secara fungsional.
- **Keamanan**: `orderSuccess` kini menolak pesanan milik orang lain (IDOR ditutup).
- **Produk & galeri**: tabel baru `product_colors`; `product_variants` dengan `color_id/is_active/sort_order`;
  `media` dengan `color_id/is_color_primary`; `sale_items` menyimpan `product_variant_id/color_id/media_id`
  sebagai snapshot. Semua kolom lama dipertahankan dan di-backfill.
- **Halaman produk** memakai swatch warna, galeri per warna, tombol prev/next, swipe, dan indikator `1 / n`.
  Katalog, beranda, wishlist, cart, checkout, detail pesanan, invoice, dan admin order menampilkan
  foto sesuai warna yang dipilih.
- **Akses**: Owner kini bisa CRUD produk, warna, varian, stok, dan seluruh foto (sebelumnya admin-only).
  Admin tetap tunduk pada `permission:products.manage`. `media.reorder` juga konsisten memakai izin.
- Test baru: `MultiUserCheckoutTest` (8), `ProductVariantGalleryTest` (7), serta test fallback Midtrans.
  Total **167 test / 815 assertions**.
- Perbaikan lanjutan: `rawItems()` tidak lagi membuang kunci array, sehingga **hapus satu baris**
  dan **update qty** di keranjang bekerja; menambah produk yang sama kini menggabung qty.
  Kompatibel dengan session lama yang kuncinya numerik lewat fallback field `key`.
- `OnlineOrderService` menambah retry transaksi saat database terkunci. Uji konkurensi HTTP
  sungguhan dengan 5 server & 5 akun menghasilkan 1 invoice, stok 0, 0 oversell, tanpa 500.

### M. Keamanan webhook & state machine pembayaran (19 Nov 2026)
- Token webhook Biteship dirotasi dan dihapus dari script/dokumentasi; script kini membaca
  `BITESHIP_WEBHOOK_TOKEN` dari `.env`. User wajib update secret di dashboard Biteship.
- `trustProxies(at: '*')` diganti daftar `TRUSTED_PROXIES` (default kosong) agar IP tidak bisa
  dipalsukan untuk bypass throttle login.
- Template produksi menambah `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`;
  `SEED_DEMO` diset false di Render/Docker; health check memakai `/up`.
- `PaymentService` sekarang memakai transisi terminal: `markFailed/markExpired` menolak
  mengubah transaksi `Paid/Failed/Expired`. `markPaid` setelah `Failed/Expired` memotong
  stok ulang via `StockReleaseService::reReserve()` dan mengosongkan `stock_released_at`.
- Webhook yang melempar exception tidak lagi hilang di `laravel.log`: Midtrans & Biteship
  mencatat baris `PaymentWebhookLog` berstatus `failed` sebelum membalas 200.
- `MidtransService::charge()` memakai cache lock per transaksi; ada `refresh()` untuk QR
  kedaluwarsa dengan reference baru `-R{n}` dan tombol di halaman pembayaran.
- Command `payments:sync-pending` dijadwalkan tiap 10 menit; `media:prune` harian.
- `EnsurePermission` berubah dari fail-open menjadi **default deny**.
- Route `admin.audit-logs.index` didaftarkan dengan `permission:reports.view` (Owner bypass),
  tersedia di navigasi Admin & Owner.
- Test baru: `PaymentStateMachineTest` (6) + `PermissionDefaultDenyTest` (4).
  Total **189 test / 917 assertions**.

### N. Ongkir otomatis & promo Owner/Admin (20 Nov 2026)
- UI **Cek Ongkir** dan teks penjelasannya di checkout dihapus. Kode pos tetap dikirim form;
  ongkir dihitung penuh di server sehingga pembeli tidak bisa "mengintip" tarif sebelum checkout.
- Ongkir default, batas maksimal, ambang gratis, tarif per wilayah, dan estimasi per wilayah
  sekarang bisa diatur Owner di **Pengaturan Bisnis** (tabel `settings`, fallback `config/pos.php`).
- Estimasi tampil untuk semua pengiriman di ringkasan checkout. Layanan **instant** memakai
  format jam seperti e-commerce (default `1-3 jam`, bisa diatur).
- Promo sekarang bisa dikelola **Admin & Owner** (`permission:promotions.manage`), menu Promo
  tersedia di navigasi keduanya. Promo tetap otomatis aktif berdasarkan tanggal.
- Harga promo tampil dengan coretan harga lama di home, katalog, detail produk, wishlist,
  keranjang, dan checkout. Diskon per baris dihitung server, bukan dari browser.
- Endpoint `/ongkir/cek` tidak dihapus agar test/internal tetap bekerja, tapi tidak ditampilkan.
- Test baru: `PromotionTest` (3), `ShippingSettingsTest` (2). Total **189 test / 917 assertions**.

### O. Metode pembayaran & geocoding instan (20 Nov 2026)
- Tanda tangan di invoice dihapus; label lokasi scan diperjelas.
- `payment_methods` ditambah `is_visible`, `status`, `status_note`, dan field rekening.
- QRIS/e-wallet/ShopeePay/VA tampil sebagai **Segera Hadir** tanpa bisa dipilih sebelum API ada.
- Rekening BCA `7963069288` a.n. `RETNO ADWIANTI` tersimpan di seeder/migrasi dan tampil di halaman bayar.
- Owner mendapat CRUD penuh metode pembayaran di `/owner/pembayaran-metode`.
- `PaymentChannelSeeder` kini `firstOrCreate` agar tidak menimpa edit Owner.
- Geocoding Grab Instan: string asal dinormalisasi, fallback koordinat toko Semarang,
  error dibedakan asal/tujuan. Test order instant memastikan `origin_coordinate` &
  `destination_coordinate` dikirim ke Biteship.
- Test baru: `PaymentChannelCrudTest` (4), `TrackingTest` (1). Total **189 test / 917 assertions**.

---

## Kesimpulan

Website **tidak dibuat ulang**. Seluruh perbaikan dilakukan langsung pada source code yang ada, database tetap satu, fitur checkout/pembayaran/stok/pengiriman yang sudah jalan tidak dirusak. Prioritas akurasi data (waktu WIB, ongkir sekali hitung), keamanan akses (backend middleware + permission), dan konsistensi tampilan sudah dipenuhi. Integrasi eksternal tetap dinyatakan **belum terhubung** sampai kredensial resmi tersedia.
