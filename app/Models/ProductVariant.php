<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'size',
        'color',
        'color_id',
        'stock',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function colorOption(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class, 'color_id');
    }

    public function label(): string
    {
        $parts = array_filter([
            $this->color ? 'Warna '.$this->color : null,
            $this->size ? 'Ukuran '.$this->size : null,
        ]);

        return $parts ? implode(' · ', $parts) : 'Standar';
    }
}
