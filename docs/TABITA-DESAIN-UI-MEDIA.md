# TABITA — Rancangan UI/UX Fitur Manajemen Gambar & Media

Tanggal: 11 November 2026
Project: TOKO TABITA (`C:\Users\LENOVO\Documents\TOKO TABITA`)
Peran: UI/UX Designer (design kece)
Status: Desain terpasang pada view media + halaman toko; menunggu review.

---

## 1. Arah Desain: "Atelier"

Fitur media dipakai admin/owner toko pakaian wanita. Arah visualnya **hangat, rapi, dan editorial** — seperti meja kerja perancang busana, bukan dashboard gudang.

- **Basis**: kain dan jahitan. Warna krem, blush, beige, dan cokelat moka; teks hamparan hangat, bukan hitam pekat.
- **Elemen khas (signature)**: garis *stitch* (putus-putus) warna mawar sebagai pembatas, dan bingkai mawar pada **foto utama**. Hanya dua ini; sisanya disiplin.
- **Keberanian visual ditumpuk di satu tempat**: kartu foto utama (galeri) dan strip banner toko. Sisanya tenang.
- **Gerak hanya untuk menjelaskan perubahan**: kartu terangkat saat hover, dan cincin fokus mawar saat navigasi keyboard. Hormati `prefers-reduced-motion`.
- **Kata sebagai materi antarmuka**: tombol memakai kata kerja jelas (`Unggah`, `Simpan`, `Ganti berkas`, `Jadikan utama`), pesan error/empty state menjelaskan langkah berikutnya.

## 2. Token Desain

| Token | Nilai | Pemakaian |
|---|---|---|
| `--tabita-ink` | `#2E2622` | Teks utama |
| `--tabita-muted` | `#7A6A62` | Teks pendukung |
| `--tabita-surface` | `#FBF7F2` | Latar halaman |
| `--tabita-card` | `#FFFEFC` | Kartu, topbar, header tabel |
| `--tabita-border` | `#EADFD6` | Garis batas |
| `--tabita-blush` | `#EBC9CE` | Latar badge, aksen lembut |
| `--tabita-primary` | `#8A4B5E` | Aksi utama (tomol, link, fokus) |
| `--tabita-primary-dark` | `#6F3A4B` | Hover aksi utama |
| `--tabita-beige` | `#E5D5C3` | Pembatas sekunder |
| `--tabita-sidebar` | `#33261F` | Sidebar |
| `--tabita-sidebar-text` | `#F3E8DE` | Teks sidebar |
| `--tabita-success` | `#4E7A5A` | Status aktif/tersimpan |
| `--tabita-danger` | `#A8434B` | Hapus / bahaya |

**Tipografi**
- Judul: `Georgia, "Times New Roman", serif` (editorial, hangat).
- Isi: `"Segoe UI", system-ui, sans-serif`.
- Label kecil: huruf kapital, `letter-spacing: .06em`, `0.7rem`, warna `--tabita-muted`.

**Bentuk**: radius kartu `0.6rem`, radius thumbnail `0.5rem`, bayangan sangat lembut (`0 1px 2px rgba(46,38,34,.04)`).

## 3. Arsitektur Informasi & Alur

```
Tampilan Toko (admin/owner)         Galeri Produk (admin, di form produk)
 ├─ Logo Toko                        ├─ Daftar foto (utama di depan)
 ├─ Banner Homepage                  ├─ Jadikan utama
 ├─ Banner Promosi                   ├─ Urutan (atas/bawah)
 └─ Foto Koleksi Terbaru             ├─ Label warna
                                     ├─ Ganti berkas
Foto Kategori (admin, modal di        └─ Hapus
  halaman kategori)
                                     Halaman Toko (publik, tamu)
                                       ├─ Header + logo
                                       ├─ Banner homepage
                                       ├─ Kategori (dengan foto)
                                       ├─ Produk terbaru (foto utama)
                                       ├─ Banner promosi
                                       └─ Koleksi terbaru
```

Alur inti: **Unggah → Preview → Simpan → Tercermin di halaman toko**. Semua perubahan dibaca dari tabel `media`, tidak ada gambar yang di-hardcode di view.

## 4. Spesifikasi Layar

### 4.1 Tampilan Toko (`/admin/tampilan`)
- Satu baris pembuka: aturan format & ukuran (JPG/JPEG/PNG/WebP, maks 2 MB).
- Empat kartu aset: Logo (1 berkas), Banner Homepage (maks 5), Banner Promosi (maks 5), Koleksi Terbaru (maks 5).
- Setiap kartu: judul + jumlah, hint singkat, daftar thumbnail (dengan tombol hapus; tombol geser untuk aset multi), lalu form unggah + preview.
- Foto utama aset multi ditandai bingkai mawar; tombol hapus memakai ikon merah dengan konfirmasi.

### 4.2 Galeri Produk (panel di `/admin/produk/{id}/edit`)
- Badge `Utama` di sudut kiri atas foto utama; badge warna di sudut kiri bawah.
- Aksi per foto: jadikan utama, hapus, geser atas/bawah, isi label warna, ganti berkas.
- Form unggah banyak foto: input `photos[]` (maks 10), label warna opsional, preview thumbnail, tombol `Unggah Foto`.
- Kartu foto lebar 150px; thumbnail rasio terjaga (`object-fit: cover`).

### 4.3 Foto Kategori (modal di `/admin/kategori`)
- Preview foto saat ini (120×120), input `photo`, preview thumbnail, tombol `Unggah`.
- Teks bantu: "Foto baru akan menggantikan foto lama secara otomatis."

### 4.4 Halaman Toko (publik, `/`)
- Header: logo toko, navigasi, tombol Masuk.
- Hero: banner homepage (jika ada).
- Strip kategori dengan foto kategori.
- Grid produk terbaru dengan foto utama.
- Strip banner promosi.
- Grid koleksi terbaru.
- Footer: nama toko, hak cipta.
- Bila aset belum diunggah: tampil placeholder yang rapi (bukan gambar pecah).

## 5. States

| State | Perlakuan |
|---|---|
| Kosong | Ikon + kalimat ajakan (mis. "Belum ada foto. Unggah foto pertama; foto pertama otomatis menjadi foto utama.") |
| Preview | Thumbnail 64×64 dari berkas terpilih, muncul sebelum tombol simpan |
| Berhasil | Toast/pesan flash di atas konten |
| Gagal validasi | Pesan merah di bawah field; field diberi `is-invalid` |
| Tanpa izin | Menu disembunyikan **dan** route ditolak controller (403) |
| Fokus keyboard | Cincin 2px `--tabita-primary` di semua kontrol |

## 6. Aksesibilitas & Responsivitas

- Kontras teks ≥ 4.5:1 (uji untuk `--tabita-muted` di atas `--tabita-surface`).
- Semua tombol ikon punya `aria-label` (mis. "Hapus foto", "Geser ke depan").
- Gambar punya `alt` yang bermakna (nama produk/kategori/aset).
- Form punya `label` yang terhubung ke `input` (terlihat atau `aria-label`).
- Layout: sidebar 250px di desktop, berubah jadi drawer di ≤991px; galeri wrap; tabel bisa discroll horizontal.
- `prefers-reduced-motion: reduce` mematikan animasi hover/transisi.

## 7. Komponen

| Komponen | Spesifikasi |
|---|---|
| Tombol utama | Latar `--tabita-primary`, teks putih, radius `.375rem`, hover `--tabita-primary-dark` |
| Tombol lembut | Putih, border `--tabita-border`, teks `--tabita-ink` |
| Kartu | Putih, border `--tabita-border`, radius `.6rem`, header tebal |
| Badge | Kecil, kapital; `Utama` = mawar, `Aktif` = hijau, `Segera` = abu |
| Thumbnail | Radius `.5rem`, `object-fit: cover`, border `--tabita-border` |
| Stitch divider | `border-top: 1px dashed var(--tabita-primary)` untuk pemisah antar bagian |

## 8. Daftar Periksa Penerimaan

**8 fitur admin**
1. Upload foto produk — `products.photos.store`
2. Ganti foto produk — `media.replace`
3. Hapus foto produk — `media.destroy`
4. Banyak foto — `photos[]`, maks 10
5. Pilih foto utama — `media.primary`, `is_primary`
6. Urutan galeri — `media.reorder`, `sort_order`
7. Foto per variasi warna — kolom `color`
8. Preview sebelum simpan — `partials/media-scripts.blade.php`

**5 aset toko**: logo, banner homepage, banner promosi, foto koleksi terbaru, foto kategori.

**Ketentuan teknis**: Storage `public`; lokasi file di DB; validasi JPG/JPEG/PNG/WebP; maks 2 MB; preview; nama unik (UUID); hapus file lama bila tak dipakai; optimasi ukuran; `storage:link`; perubahan tercermin tanpa ubah kode.

## 9. Yang Sudah Terpasang vs. Rekomendasi

| Item | Status |
|---|---|
| View Tampilan Toko, galeri produk, modal foto kategori | Terpasang |
| Preview + browser shrink + reorder | Terpasang (`partials/media-scripts`) |
| Halaman toko publik menampilkan aset | Terpasang (`store/home`) |
| Tema "Atelier" (krem/blush/mocha) | Terpasang (`resources/css/app.css`) |
| Storefront katalog penuh + keranjang + checkout | Di luar cakupan fitur media |
| Size chart, variasi ukuran, WhatsApp | Di luar cakupan fitur media |
