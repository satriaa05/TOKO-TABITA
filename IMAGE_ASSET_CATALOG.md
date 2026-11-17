# IMAGE ASSET CATALOG — TABITA MODISTE
**Boutique Identity**: Luxury Feminine Fashion Editorial — Elegant Modern Boutique  
**Primary Color Palette**: Cream (`#FFF9F7`), Beige (`#EADBD2`), Soft Pink (`#F4ECE4`), Dusty Rose (`#BD777C`), Dark Brown (`#33261F`), Warm White (`#FFFFFF`).

---

## 1. Direktori & Struktur Penyimpanan
Semua aset visual tersimpan terpusat di `public/assets/images/`:
```text
public/assets/images/
├── branding/          # Identitas visual & logo butik
├── login/             # Banner visual split-screen login & registrasi
├── banners/           # Hero slider & banner seasonal sale homepage
├── categories/        # Foto model kategori pakaian wanita (portrait 3:4)
├── products/          # Foto katalog produk dengan model
├── about/             # Suasana butik atelier
├── gallery/           # Editorial lifestyle showcase
└── placeholders/      # Vektor ilustrasi empty state & error pages
```

---

## 2. Katalog Aset Lengkap

### A. Branding & Identitas Toko
| Nama Berkas | Lokasi | Kegunaan | Halaman Terkait | Status | Asal / Lisensi |
|---|---|---|---|---|---|
| `logo-tabita.svg` | `public/assets/images/branding/` | Logo resmi Tabita Modiste (Monogram jarum & benang modiste) | Navbar, Footer, Sidebar Admin | Tersedia | Official Vector Brand |
| `logo-tabita.png` | `public/assets/images/branding/` | Raster branding logo untuk email & metadata | Storefront, Admin | Tersedia | Official Brand |

### B. Banner Autentikasi (Glassmorphism Split-Screen)
| Nama Berkas | Lokasi | Kegunaan | Halaman Terkait | Status | Asal / Lisensi |
|---|---|---|---|---|---|
| `login-fashion-hero.jpg` | `public/assets/images/login/` | Foto model wanita tailored beige blazer dress (komposisi kiri lapang) | `/login` | Tersedia | High-End Editorial Generative (Tabita Modiste Exclusive) |
| `register-fashion-hero.jpg` | `public/assets/images/login/` | Foto model wanita dusty rose silk blouse & rok lipit krem | `/register` | Tersedia | High-End Editorial Generative (Tabita Modiste Exclusive) |

### C. Hero Banner Homepage (Storefront)
| Nama Berkas | Lokasi | Kegunaan | Halaman Terkait | Status | Asal / Lisensi |
|---|---|---|---|---|---|
| `hero-elegant-collection.jpg` | `public/assets/images/banners/` | Hero Banner Utama: Beige tailored luxury long coat & silk dress | `/` (Homepage) | Tersedia | High-End Editorial Generative (Tabita Modiste Exclusive) |
| `hero-new-arrival.jpg` | `public/assets/images/banners/` | Hero Banner 2: Romantic dusty rose chiffon tiered midi dress | `/` (Homepage & New Arrivals) | Tersedia | High-End Editorial Generative (Tabita Modiste Exclusive) |
| `hero-special-sale.jpg` | `public/assets/images/banners/` | Hero Banner 3: Neutral trench coat over cream slip dress | `/` (Homepage & Promo Sale) | Tersedia | High-End Editorial Generative (Tabita Modiste Exclusive) |

### D. Kategori Pakaian Wanita (Portrait 3:4 dengan Model Berbusana)
| Nama Berkas | Lokasi | Kategori | Kegunaan | Status |
|---|---|---|---|---|
| `dresses.jpg` | `public/assets/images/categories/` | Dresses | Model memakai beige flowing wrap dress | Tersedia |
| `blouses.jpg` | `public/assets/images/categories/` | Blouses | Model memakai silky dusty rose blouse | Tersedia |
| `shirts.jpg` | `public/assets/images/categories/` | Women's Shirts | Model memakai crisp white tailored shirt | Tersedia |
| `outerwear.jpg` | `public/assets/images/categories/` | Outerwear | Model memakai camel wool blazer coat | Tersedia |
| `hijab.jpg` | `public/assets/images/categories/` | Hijab | Model modern hijab & cream tunic dress | Tersedia |
| `skirts.jpg` | `public/assets/images/categories/` | Skirts | Model pleated A-line midi skirt | Tersedia |
| `pants.jpg` | `public/assets/images/categories/` | Pants | Model high-waist wide leg trousers | Tersedia |
| `casual-wear.jpg` | `public/assets/images/categories/` | Casual Wear | Model chic everyday casual ensemble | Tersedia |
| `workwear.jpg` | `public/assets/images/categories/` | Workwear | Model feminine executive power suit | Tersedia |
| `party-dresses.jpg` | `public/assets/images/categories/` | Party Dresses | Model glamorous evening slip gown | Tersedia |
| `t-shirts.jpg` | `public/assets/images/categories/` | T-Shirts | Model minimal relaxed premium cotton tee | Tersedia |
| `new-collection.jpg`| `public/assets/images/categories/` | New Collection | Model signature seasonal ensemble | Tersedia |

### E. Ilustrasi Empty States & Error Pages (Soft Luxury Vector)
| Nama Berkas | Lokasi | Kegunaan | Halaman Terkait | Status |
|---|---|---|---|---|
| `empty-cart.svg` | `public/assets/images/placeholders/` | Ilustrasi tas belanja butik dusty rose | `/keranjang` (saat kosong) | Tersedia |
| `empty-wishlist.svg` | `public/assets/images/placeholders/` | Ilustrasi hati dengan aksen jahitan modiste | `/wishlist` (saat kosong) | Tersedia |
| `empty-search.svg` | `public/assets/images/placeholders/` | Ilustrasi lensa kaca pembesar & jarum modiste | `/katalog` (saat pencarian nihil) | Tersedia |
| `error-404.svg` | `public/assets/images/placeholders/` | Ilustrasi 404 gaun manekin atelier modiste | `errors/404.blade.php` | Tersedia |

---

## 3. Tata Kelola & Pergantian Gambar oleh Admin / Owner
1. **Tidak Ada Hardcoded Image di Database Core**:
   Setiap gambar banner, kategori, dan produk terikat ke tabel `media` dan dipanggil secara dinamis:
   ```blade
   <img src="{{ $category->photo?->url() ?? asset('assets/images/categories/' . $slug . '.jpg') }}" ...>
   ```
2. **Dashboard Pengelolaan Media**:
   - Menu **Tampilan Toko** (`/admin/media`): Mengganti logo, background login/register, hero banner toko, dan promo banner.
   - Menu **Kategori** (`/admin/categories`): Mengunggah foto model untuk tiap kategori pakaian.
   - Menu **Produk** (`/admin/products`): Mengunggah multi-angle foto, memilih foto utama (*primary*), dan mengurutkan galeri.
