<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Satu berkas gambar yang dikelola dari admin.
 *
 * Gambar terikat pada pemiliknya lewat kolom polymorphic
 * (mediable_type + mediable_id) dengan alias:
 *  - "product"  -> galeri foto produk
 *  - "category" -> foto kategori
 *  - "site"     -> aset tampilan toko (logo, banner, koleksi)
 *
 * Lokasi berkas disimpan pada kolom `disk` + `path`, sehingga tampilan
 * selalu membaca dari database - admin bisa mengganti gambar tanpa
 * mengubah source code.
 */
class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'role',
        'color',
        'color_id',
        'is_color_primary',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'width',
        'height',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'mediable_id' => 'integer',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'is_primary' => 'boolean',
            'is_color_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Pemilik gambar (Product, Category, atau NULL untuk aset site).
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * URL publik berkas. Dipakai di semua view agar gambar tetap tampil
     * setelah deploy ke hosting (hanya butuh `php artisan storage:link`).
     */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Apakah gambar ini milik aset site (bukan produk/kategori).
     */
    public function isSiteAsset(): bool
    {
        return $this->mediable_type === 'site';
    }

    /**
     * Ambil daftar gambar milik satu target dengan urutan tampil yang benar:
     * foto utama lebih dulu, lalu urutan yang diatur admin.
     */
    public function scopeForTarget(Builder $query, string $type, ?int $id, string $role): Builder
    {
        return $query->where('mediable_type', $type)
            ->where('mediable_id', $id)
            ->where('role', $role)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Galeri satu warna: pakai color_id bila ada, jika tidak fallback ke label string lama.
     */
    public function scopeForColor(Builder $query, ?int $colorId, ?string $color): Builder
    {
        if ($colorId !== null) {
            return $query->where('color_id', $colorId);
        }

        if ($color !== null && $color !== '') {
            return $query->where('color', $color);
        }

        return $query->whereNull('color_id')->whereNull('color');
    }

    /**
     * Ambil daftar aset site untuk satu peran (logo, banner, dll).
     */
    public function scopeSiteRole(Builder $query, string $role): Builder
    {
        return $query->where('mediable_type', 'site')
            ->whereNull('mediable_id')
            ->where('role', $role)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Aset site tunggal (mis. logo toko).
     */
    public static function siteAsset(string $role): ?self
    {
        return static::siteRole($role)->first();
    }
}
