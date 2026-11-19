<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Penyimpanan dan pengelolaan berkas gambar (media).
 *
 * Aturan penting yang dipakai seluruh fitur gambar:
 *  - Berkas disimpan lewat Laravel Storage pada disk "public".
 *  - Lokasi berkas disimpan di tabel `media`, TIDAK ditulis di source code.
 *  - Nama berkas selalu unik (UUID) supaya tidak pernah menimpa gambar lain.
 *  - Berkas lama dihapus hanya jika tidak dipakai baris `media` lain.
 *  - Optimasi ukuran gambar dijalankan bila ekstensi PHP "gd" tersedia di
 *    server; bila tidak, berkas asli tetap tersimpan tanpa error.
 */
class MediaService
{
    private int $maxWidth;

    private int $quality;

    private string $disk;

    public function __construct()
    {
        $this->maxWidth = (int) config('media.max_width', 1600);
        $this->quality = (int) config('media.quality', 82);
        $this->disk = 'public';
    }

    /**
     * Simpan berkas unggahan sebagai gambar baru untuk satu target.
     *
     * @param  string  $type  alias pemilik: "product", "category", atau "site"
     * @param  int|null  $targetId  id pemilik (NULL untuk aset site)
     * @param  string  $role  fungsi gambar ("gallery", "photo", "logo", dst.)
     * @param  string|null  $color  label variasi warna (opsional)
     * @param  int|null  $colorId  entitas warna produk (opsional, data lama boleh null)
     */
    public function store(UploadedFile $file, string $type, ?int $targetId, string $role, ?string $color = null, ?int $colorId = null): Media
    {
        $directory = $this->directory($type, $targetId, $role);
        $extension = $this->extensionFor($file);
        $filename = Str::uuid()->toString().'.'.$extension;

        $path = $file->storeAs($directory, $filename, $this->disk);

        [$width, $height] = $this->readDimensions($file);

        // Optimasi ukuran gambar (hanya bila gd tersedia di server).
        $optimized = null;
        if (extension_loaded('gd')) {
            try {
                $optimized = $this->optimizeWithGd($file, $path);
            } catch (\Throwable) {
                // Optimasi gagal -> biarkan berkas asli yang sudah tersimpan.
            }
        }

        return Media::create([
            'mediable_type' => $type,
            'mediable_id' => $targetId,
            'role' => $role,
            'color' => $this->cleanColor($color),
            'color_id' => $colorId,
            'is_color_primary' => $colorId !== null
                && ! Media::query()->where('color_id', $colorId)->where('is_color_primary', true)->exists(),
            'disk' => $this->disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $this->mimeFromContent($file),
            'size' => $optimized['size'] ?? Storage::disk($this->disk)->size($path),
            'width' => $optimized['width'] ?? $width,
            'height' => $optimized['height'] ?? $height,
            'is_primary' => false,
            'sort_order' => $this->nextSortOrder($type, $targetId, $role),
        ]);
    }

    /**
     * Ganti isi berkas pada satu baris media dengan berkas baru.
     * Berkas lama dihapus jika tidak dipakai baris media lain.
     */
    public function replace(Media $media, UploadedFile $file): Media
    {
        $directory = $this->directory($media->mediable_type, $media->mediable_id, $media->role);
        $extension = $this->extensionFor($file);
        $filename = Str::uuid()->toString().'.'.$extension;

        $path = $file->storeAs($directory, $filename, $this->disk);
        [$width, $height] = $this->readDimensions($file);

        $optimized = null;
        if (extension_loaded('gd')) {
            try {
                $optimized = $this->optimizeWithGd($file, $path);
            } catch (\Throwable) {
                // Abaikan: berkas asli tetap valid.
            }
        }

        $oldPath = $media->path;

        $media->update([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $this->mimeFromContent($file),
            'size' => $optimized['size'] ?? Storage::disk($this->disk)->size($path),
            'width' => $optimized['width'] ?? $width,
            'height' => $optimized['height'] ?? $height,
        ]);

        $this->deleteFileIfUnreferenced($oldPath);

        return $media->refresh();
    }

    /**
     * Hapus satu baris media beserta berkasnya (jika tidak dipakai lain).
     * Bila yang dihapus foto utama, foto berikutnya diurutan naik menjadi utama.
     */
    public function delete(Media $media): void
    {
        $wasPrimary = $media->is_primary;

        $media->delete();

        $this->deleteFileIfUnreferenced($media->path);

        if ($wasPrimary) {
            $successor = Media::query()
                ->forTarget($media->mediable_type, $media->mediable_id, $media->role)
                ->first();

            if ($successor !== null) {
                $successor->update(['is_primary' => true]);
            }
        }
    }

    /**
     * Jadikan satu gambar sebagai foto utama pada targetnya.
     * Hanya boleh ada satu foto utama per target+role.
     */
    public function setPrimary(Media $media): void
    {
        Media::query()
            ->where('mediable_type', $media->mediable_type)
            ->where('mediable_id', $media->mediable_id)
            ->where('role', $media->role)
            ->where('is_primary', true)
            ->where('id', '!=', $media->id)
            ->update(['is_primary' => false]);

        $media->update(['is_primary' => true]);
    }

    /**
     * Jadikan satu foto sebagai thumbnail warnanya (berbeda dengan foto utama
     * global yang dipakai katalog). Aman untuk data lama yang belum punya
     * color_id: fallback ke label warna string.
     */
    public function setColorPrimary(Media $media): void
    {
        $query = Media::query()
            ->where('mediable_type', $media->mediable_type)
            ->where('mediable_id', $media->mediable_id)
            ->where('role', $media->role)
            ->where('id', '!=', $media->id);

        if ($media->color_id !== null) {
            $query->where('color_id', $media->color_id);
        } else {
            $query->where('color', $media->color);
        }

        $query->update(['is_color_primary' => false]);

        $media->update(['is_color_primary' => true]);

        if ($media->color_id !== null) {
            \App\Models\ProductColor::query()
                ->where('id', $media->color_id)
                ->update(['thumbnail_media_id' => $media->id]);
        }
    }

    /**
     * Simpan urutan galeri berdasarkan daftar id yang dikirim admin.
     * Semua id wajib berada pada target+role yang sama. Gambar yang
     * berada paling depan otomatis menjadi foto utama, sehingga urutan
     * tampil di halaman persis seperti yang diatur admin.
     *
     * @param  array<int, int|string>  $orderedIds
     */
    public function reorder(string $type, ?int $targetId, string $role, array $orderedIds): void
    {
        $ids = array_values(array_map('intval', $orderedIds));

        $owned = Media::query()
            ->where('mediable_type', $type)
            ->where('mediable_id', $targetId)
            ->where('role', $role)
            ->whereIn('id', $ids)
            ->pluck('id');

        if ($owned->count() !== count($ids)) {
            return; // Ada id yang bukan milik target ini -> urutan diabaikan.
        }

        foreach ($ids as $index => $id) {
            Media::query()->where('id', $id)->update(['sort_order' => $index]);
        }

        // Item paling depan menjadi foto utama.
        $front = Media::query()->find($ids[0]);

        if ($front !== null && ! $front->is_primary) {
            $this->setPrimary($front);
        }
    }

    /**
     * Hapus berkas dari disk hanya bila tidak ada baris media lain
     * yang masih memakai lokasi yang sama.
     */
    public function deleteFileIfUnreferenced(string $path, ?string $disk = null): void
    {
        $disk ??= $this->disk;

        $referenced = Media::query()
            ->where('disk', $disk)
            ->where('path', $path)
            ->exists();

        if ($referenced) {
            return;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (\Throwable) {
            // Berkas mungkin sudah tidak ada; penghapusan row tetap berjalan.
        }
    }

    /**
     * Direktori berkas pada disk sesuai pemiliknya.
     */
    private function directory(string $type, ?int $targetId, string $role): string
    {
        return 'media/'.$type.'/'.($targetId !== null ? $targetId : 'site').'/'.$role;
    }

    /**
     * Nomor urut berikutnya untuk target+role tertentu.
     */
    private function nextSortOrder(string $type, ?int $targetId, string $role): int
    {
        $max = Media::query()
            ->where('mediable_type', $type)
            ->where('mediable_id', $targetId)
            ->where('role', $role)
            ->max('sort_order');

        return ((int) $max) + 1;
    }

    /**
     * Ekstensi berkas diturunkan dari ISI file (MIME), BUKAN dari nama file
     * hasil unggahan. Nama file bisa membawa ekstensi berbahaya (mis. .php);
     * menyimpannya apa adanya pada disk publik berisiko dieksekusi server.
     *
     * Deteksi MIME dilakukan dengan membaca magic bytes berkas (finfo),
     * sehingga nama berkas sama sekali tidak dipakai. Bila tipe tidak
     * dikenali, hasilnya "bin" - bukan ekstensi dari nama berkas. Dengan
     * begitu tidak ada jalur yang bisa menyimpan berkas .php ke disk publik.
     */
    private function extensionFor(UploadedFile $file): string
    {
        return match ($this->mimeFromContent($file)) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    /**
     * MIME asli berkas, dibaca dari magic bytes (bukan dari nama berkas
     * maupun header yang dikirim browser).
     */
    private function mimeFromContent(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if ($path !== false && is_file($path) && function_exists('finfo_open')) {
            try {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                if ($finfo !== false) {
                    $mime = finfo_file($finfo, $path);
                    finfo_close($finfo);

                    if (is_string($mime) && $mime !== '') {
                        return $mime;
                    }
                }
            } catch (\Throwable) {
                // Lanjut ke cara di bawah.
            }
        }

        return (string) $file->getMimeType();
    }

    /**
     * Bersihkan label warna agar aman ditampilkan.
     */
    private function cleanColor(?string $color): ?string
    {
        $color = trim((string) $color);

        return $color !== '' ? Str::limit($color, 50, '') : null;
    }

    /**
     * Baca dimensi gambar dengan getimagesize (tidak butuh gd).
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function readDimensions(UploadedFile $file): array
    {
        try {
            $info = @getimagesize($file->getRealPath());

            return $info !== false ? [(int) $info[0], (int) $info[1]] : [null, null];
        } catch (\Throwable) {
            return [null, null];
        }
    }

    /**
     * Perkecil dan kompres ulang gambar memakai gd bila melebihi
     * lebar maksimum. Mengembalikan ukuran + dimensi baru, atau NULL
     * bila tidak perlu dioptimasi.
     *
     * @return array{size: int, width: int, height: int}|null
     */
    private function optimizeWithGd(UploadedFile $file, string $path): ?array
    {
        $source = $file->getRealPath();
        $mime = $file->getMimeType();

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png' => @imagecreatefrompng($source),
            'image/webp' => @imagecreatefromwebp($source),
            default => null,
        };

        if ($image === false || $image === null) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $this->maxWidth) {
            imagedestroy($image);

            return null;
        }

        $newWidth = $this->maxWidth;
        $newHeight = (int) round($height * ($newWidth / $width));

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Pertahankan transparansi untuk PNG/WebP.
        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $target = Storage::disk($this->disk)->path($path);

        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($resized, $target, $this->quality),
            'image/png' => imagepng($resized, $target, 6),
            'image/webp' => imagewebp($resized, $target, $this->quality),
            default => false,
        };

        imagedestroy($image);
        imagedestroy($resized);

        if (! $saved) {
            return null;
        }

        clearstatcache(true, $target);

        return [
            'size' => (int) filesize($target),
            'width' => $newWidth,
            'height' => $newHeight,
        ];
    }
}
