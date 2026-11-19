<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu variasi warna pada sebuah produk.
 *
 * Warna dibuat sebagai entitas agar galeri foto, swatch, dan stok per ukuran
 * terikat pada ID yang sama. Kolom `color` berupa string di tabel lama tetap
 * dipakai sebagai fallback untuk transaksi & media sebelum migrasi ini.
 */
class ProductColor extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'hex',
        'is_active',
        'sort_order',
        'thumbnail_media_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'color_id')->orderBy('sort_order')->orderBy('size');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'color_id')->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Warna untuk swatch CSS: pakai hex bila diisi, jika kosong hash nama stabil.
     */
    public function swatch(): string
    {
        if ($this->hex) {
            return $this->hex;
        }

        $palette = ['#2f2a28', '#f7f4f0', '#e9d9c3', '#c9a7a0', '#8d9c8a', '#b9c2cf'];

        return $palette[abs(crc32($this->name)) % count($palette)];
    }
}
