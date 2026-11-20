# Panduan Multi-User, Stok, dan Galeri Varian

## 1. Keranjang per pelanggan

- Keranjang disimpan di **session server**, bukan di localStorage browser.
- Session juga menyimpan `user_id`. Bila di satu browser akun A logout lalu akun B login,
  keranjang A **tidak** ikut ke akun B.
- Guest yang menambah barang lalu login masih membawa keranjangnya ke akun tersebut.
- Tombol ubah/hapus keranjang memakai kunci `warna~ukuran~product_id`; kunci sudah disanitasi
  sehingga warna dengan spasi tetap bisa dihapus.

## 2. Checkout & invoice

- Setiap checkout membuat **satu Sale baru** dengan `invoice_number` unik.
- Tombol checkout mengirim `idempotency_key`. Double-click / retry dengan kunci yang sama
  mengembalikan pesanan yang sudah ada, bukan membuat duplikat dan bukan memotong stok dua kali.
- Data pembeli (nama, WhatsApp, alamat, kode pos), produk, ukuran, warna, qty, dan ekspedisi
  disimpan di baris Sale/SaleItem milik akun yang checkout.
- Halaman pembayaran, tracking, riwayat, upload bukti, dan halaman sukses menolak akses
  jika invoice bukan milik akun yang login.

## 3. Stok warna × ukuran

Contoh stok yang didukung:

| Warna | Ukuran | Stok |
|---|---|---|
| Hitam | S | 5 |
| Hitam | M | 8 |
| Putih | S | 3 |
| Putih | M | 6 |
| Cream | L | 4 |

- Saat pembeli memilih **Hitam / M**, yang dipotong adalah stok **Hitam / M**, bukan total produk.
- `products.stock` tetap dipakai sebagai total (jumlah varian aktif) untuk laporan & stok menipis.
- Pemotongan dan pengembalian stok lewat `StockService::apply()` dengan `lockForUpdate`
  pada baris produk **dan** varian, di dalam satu `DB::transaction`.
- `addToCart` menolak jumlah melebihi sisa stok varian.
- Pembayaran `failed`/`expired` atau refund mengembalikan stok sekali, dijaga kolom
  `sales.stock_released_at`. Webhook retry tidak mengembalikan stok dua kali.

## 4. Pembayaran

- `PaymentTransaction` selalu terhubung ke satu `Sale`/invoice.
- Bukti pembayaran di_upload ke invoice pemiliknya; pembeli lain tidak bisa melihat/mengubahnya.
- Perubahan status PAID hanya lewat `PaymentService` (webhook terverifikasi, fallback status API,
  atau verifikasi manual Admin). Redirect browser tidak pernah dipercaya.
- Dedup webhook memakai `event_id` unik + `firstOrCreate`, aman bila webhook identik datang bersamaan.
- Fallback GET status Midtrans diverifikasi via kredensial API dan pencocokan `order_id` + nominal.
  QRIS/e-wallet/VA tetap memerlukan `MIDTRANS_SERVER_KEY` untuk aktif.

## 5. Admin & Owner

- Admin: melihat semua pesanan, detail per invoice, verifikasi pembayaran, pengiriman,
  dan mengelola produk sesuai `permission:products.manage`.
- Owner: akses penuh produk—tambah/hapus produk, warna, varian, stok per kombinasi,
  foto, urutan galeri, thumbnail warna, dan mengedit info produk.
- Perubahan status satu pesanan tidak menyentuh pesanan lain (semua query difilter by `sale_id`).

## 6. Galeri foto per warna

Di halaman detail produk:

- Lingkaran warna (swatch) dari hex yang diatur Admin/Owner.
- Memilih warna mengganti foto galeri, daftar ukuran, dan stok yang tersedia.
- Tombol prev/next + geser (swipe) di HP + indikator `1 / n`.
- Foto kartu di katalog, beranda, wishlist, cart, checkout, detail pesanan, dan invoice
  mengikuti warna yang dipilih.

Di dashboard produk:

- Tambah/ubah/nonaktifkan/hapus warna.
- Tambah/edit/nonaktifkan varian ukuran + stok per warna.
- Upload banyak foto per warna, ganti/hapus foto, atur urutan, foto utama global,
  dan thumbnail khusus warna.

> Warna yang sudah dipakai transaksi tidak bisa dihapus—cukup dinonaktifkan.
> Foto/warna lama yang belum punya `color_id` tetap tampil via fallback label string.

## 7. Pengujian

```powershell
cd "C:\Users\LENOVO\Documents\TOKO TABITA"
& 'C:\Users\LENOVO\php-8.5-gd\php.exe' artisan test
```

Hasil terakhir: **189 test, 189 passed, 917 assertions**.

Uji konkurensi HTTP sungguhan:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\concurrency-checkout.ps1
```

Hasil: 5 akun checkout bersamaan pada stok 1 → **1 invoice**, stok varian 0, tanpa oversell.

Fokus test:
- `tests/Feature/Store/MultiUserCheckoutTest.php`
- `tests/Feature/Store/ProductVariantGalleryTest.php`
- `tests/Feature/Payment/PaymentVerificationTest.php`

> Uji konkurensi lintas proses sudah dijalankan lewat `scripts/concurrency-checkout.ps1` dengan
> 5 server dan SQLite WAL. Di MySQL produksi, `lockForUpdate` bekerja per baris.

## 8. Catatan keamanan tambahan

- Token webhook Biteship sudah dirotasi; ambil nilainya dari `.env` (`BITESHIP_WEBHOOK_TOKEN`)
  dan update di dashboard Biteship. Jangan bagikan/catam di dokumen.
- Akses Admin sekarang **default deny**: tanpa baris `role_permissions`, semua halaman Admin ditolak.
- Owner tetap memiliki akses penuh, termasuk halaman Jejak Audit di `/admin/audit-log`.
- QR pembayaran yang kedaluwarsa bisa dibuat ulang dari halaman pembayaran (tombol
  **Buat QR Pembayaran Baru**). Reference lama tidak dipakai ulang.
- Status pembayaran bersifat terminal: webhook `expire` terlambat tidak akan membatalkan
  transaksi yang sudah lunas; pembayaran yang masuk setelah expired memotong stok ulang.
