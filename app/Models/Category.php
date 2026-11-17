<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Produk dalam kategori ini.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Foto kategori (tabel media). Satu kategori satu foto;
     * foto baru menggantikan yang lama.
     */
    public function photo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')
            ->where('role', 'photo');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Pencarian berdasarkan nama kategori.
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (filled($keyword)) {
            $query->where('name', 'like', '%'.$keyword.'%');
        }

        return $query;
    }
}
