<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'qty',
        'stock_before',
        'stock_after',
        'ref_type',
        'ref_id',
        'user_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'qty' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    /**
     * Produk yang bermutasi.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Pengguna yang melakukan perubahan stok.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mutasi stok masuk.
     */
    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('qty', '>', 0);
    }

    /**
     * Mutasi stok keluar.
     */
    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('qty', '<', 0);
    }
}
