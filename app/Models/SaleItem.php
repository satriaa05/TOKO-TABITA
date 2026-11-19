<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'barcode',
        'size',
        'color',
        'product_variant_id',
        'color_id',
        'media_id',
        'qty',
        'price',
        'cost_price',
        'discount',
        'subtotal',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * Transaksi induk.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Produk asal (boleh null bila produk dihapus).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function selectedVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function selectedMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /**
     * Foto warna yang benar-benar dibeli pelanggan.
     * Data lama fallback ke foto produk berdasarkan label warna.
     */
    public function photo(): ?Media
    {
        if ($this->media_id) {
            $media = Media::query()->find($this->media_id);

            if ($media !== null) {
                return $media;
            }
        }

        return $this->product?->primaryPhotoForColor($this->color);
    }

    public function photoUrl(): ?string
    {
        return $this->photo()?->url();
    }

    /**
     * Total harga pokok barang terjual pada baris ini.
     */
    public function costTotal(): float
    {
        return round((float) $this->cost_price * $this->qty, 2);
    }
}
