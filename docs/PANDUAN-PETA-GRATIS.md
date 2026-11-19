# Peta Gratis Tabita Modiste — Leaflet + OpenStreetMap

Dokumen ini menjelaskan peta tracking yang **tidak membutuhkan API key dan kartu kredit**.

---

## 1. Teknologi yang dipakai

| Kebutuhan | Layanan | Biaya |
|---|---|---|
| Peta & tile | **Leaflet + OpenStreetMap** | Gratis (dengan atribusi) |
| Ubah alamat → koordinat | **Nominatim (OSM)** | Gratis, ada batas wajar |
| Rute asal → tujuan | **OSRM demo server** | Gratis, ada batas wajar |
| CSS/JS Leaflet | CDN unpkg | Gratis |

Tidak ada `GOOGLE_MAPS_*` yang dipakai lagi. File `GoogleMapsService.php` sudah dihapus.

---

## 2. File yang terlibat

| File | Peran |
|---|---|
| `app/Services/MapService.php` | Geocoding Nominatim + routing OSRM + cache |
| `config/shipping.php` → `maps` | URL, atribusi, User-Agent |
| `app/Http/Controllers/StoreController.php` | Menyiapkan data peta di halaman tracking |
| `app/Services/BiteshipService.php` | Memakai koordinat untuk cek jarak Grab Instan & order instant |
| `resources/views/store/tracking.blade.php` | Render peta Leaflet, marker, polyline, legenda |
| `resources/views/store/partials/tracking-live.blade.php` | Timeline & info lokasi (di-refresh 15 detik) |

---

## 3. Konfigurasi `.env`

```dotenv
TABITA_MAPS_USER_AGENT="TabitaModiste/1.0 (local development)"
TABITA_GEOCODE_URL=https://nominatim.openstreetmap.org/search
TABITA_ROUTE_URL=https://router.project-osrm.org/route/v1/driving
```

Sebelum production, ganti User-Agent:

```dotenv
TABITA_MAPS_USER_AGENT="TabitaModiste/1.0 (https://tokotabita.id)"
```

Lalu:

```powershell
php artisan config:clear
```

---

## 4. Cara kerja

### Geocoding
`MapService::geocode($alamat)` mengirim permintaan ke Nominatim:

```
GET https://nominatim.openstreetmap.org/search
    ?format=jsonv2
    &limit=1
    &countrycodes=id
    &q=Semarang, Jawa Tengah, Indonesia 50131
```

Hasil:

```json
{ "lat": -6.9656495, "lng": 110.4271369 }
```

Hasil **di-cache 24 jam** supaya tidak mengulang request.

### Routing
`MapService::route($origin, $destination)` memanggil OSRM:

```
GET https://router.project-osrm.org/route/v1/driving/110.427,-6.965;110.4,-6.9
    ?overview=full&geometries=geojson
```

Hasil dikonversi dari `[lng, lat]` menjadi `[lat, lng]`, lalu di-cache **6 jam**.

Kalau OSRM gagal, sistem otomatis memakai **garis lurus** antara dua titik. Jadi peta tetap tampil.

---

## 5. Yang tampil di halaman tracking

- 🔴 titik **asal** (lokasi toko)
- 🔵 titik **tujuan** (alamat pembeli)
- 🟣 titik **scan** dari event Biteship yang punyi koordinat
- 🟢 titik **scan terakhir**
- garis **perkiraan rute**
- atribusi: `Leaflet | © OpenStreetMap contributors`

Semua marker otomatis `fitBounds`, jadi peta langsung fokus ke area pengiriman.

---

## 6. Cara melihat peta

1. Pastikan server jalan:
   ```powershell
   php artisan serve
   ```
2. Login sebagai pembeli.
3. Buka **Pesanan Saya → Lacak Pengiriman**, atau langsung:
   ```
   http://127.0.0.1:8000/pesanan-saya/{id}/lacak
   ```

Peta langsung tampil tanpa pengaturan tambahan.

---

## 7. Keterbatasan yang jujur

| Hal | Penjelasan |
|---|---|
| GPS kurir live | ❌ Tidak ada untuk JNE/SiCepat. Yang tampil adalah **titik scan**, bukan posisi kurir bergerak. |
| Grab Instan | Membutuhkan koordinat asli; sistem akan menolak kalau geocoding gagal. |
| Nominatim | Gratis, tapi punya aturan pemakaian wajar. Karena itu hasil di-cache 24 jam. |
| OSRM demo | Cocok untuk volume rendah/sedang. Untuk volume tinggi sebaiknya pakai layanan routing berbayar atau self-host. |
| Kode pos salah | Biteship/Nominatim bisa salah membaca kode pos. Sistem menampilkan peringatan di checkout. |
| Titik scan kosong | Kalau ekspedisi tidak mengirim koordinat, Admin bisa mengisi manual di **Pengiriman → Perbarui Status Manual**. |

---

## 8. Perbandingan dengan Google Maps

| | Leaflet + OSM (sekarang) | Google Maps |
|---|---|---|
| API key | ❌ Tidak perlu | ✅ Perlu |
| Kartu kredit | ❌ Tidak perlu | ✅ Perlu |
| Biaya | Gratis | Ada kuota gratis, lalu bayar |
| Akurasi alamat | Cukup | Sangat baik |
| Rute | Cukup (OSRM) | Sangat baik |
| Cocok untuk uji coba | ✅✅ | ⚠️ |
| Cocok production premium | ⚠️ | ✅✅ |

Keputusan: **pakai Leaflet dulu**. Kalau nanti client mau akurasi premium & sudah ada budget, baru bisa kembali ke Google Maps.

---

## 9. Pengujian

- `tests/Feature/Shipping/MapServiceTest.php` — 4 test:
  - geocoding Nominatim,
  - cache geocoding,
  - rute OSRM,
  - fallback garis lurus.
- Audit browser: peta tampil, **9 tile termuat**, **3 marker**, atribusi benar, **tanpa console error**.
- Screenshot: `docs/screenshots/leaflet-tracking.png`.

---

## 10. Catatan penting

Jangan menghapus atribusi **OpenStreetMap contributors**. Itu kewajiban lisensi data OSM.

Kalau ingin nanti ganti ke Google Maps, cukup ganti `MapService` + view tracking. Database & fitur lain tidak berubah.
