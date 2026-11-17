<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'barcode',
        'name',
        'category_id',
        'unit',
        'cost_price',
        'sale_price',
        'stock',
        'min_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Kategori produk.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Galeri foto produk (tabel media), urutan: foto utama lebih dulu
     * lalu urutan yang diatur admin.
     */
    /**
     * Relasi varian produk (ukuran & warna, dengan stok masing-masing).
     */
    public function variants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('size')->orderBy('color');
    }

    /**
     * Apakah produk ini memakai varian.
     */
    public function hasVariants(): bool
    {
        return $this->relationLoaded('variants')
            ? $this->variants->isNotEmpty()
            : $this->variants()->exists();
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')
            ->where('role', 'gallery')
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Foto utama produk (untuk thumbnail di daftar/katalog).
     */
    public function primaryPhoto(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')
            ->where('role', 'gallery')
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    // -----------------------------------------------------------------
    // Scope
    // -----------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Produk dengan stok <= stok minimum.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    /**
     * Pencarian berdasarkan nama atau barcode.
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (filled($keyword)) {
            $query->where(function (Builder $sub) use ($keyword) {
                $sub->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('barcode', 'like', '%'.$keyword.'%');
            });
        }

        return $query;
    }

    // -----------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------

    /**
     * Apakah stok produk sudah menipis.
     */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /**
     * Margin laba kotor per unit (tidak pernah negatif).
     */
    public function margin(): float
    {
        return max(0, (float) $this->sale_price - (float) $this->cost_price);
    }

    /**
     * Nilai persediaan produk ini = stok x harga pokok.
     * Dipakai di laporan inventori.
     */
    public function getInventoryValueAttribute(): float
    {
        return round((float) $this->stock * (float) $this->cost_price, 2);
    }

    /**
     * Potensi omzet jika seluruh stok terjual = stok x harga jual.
     */
    public function getPotentialRevenueAttribute(): float
    {
        return round((float) $this->stock * (float) $this->sale_price, 2);
    }
}
