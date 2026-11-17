<?php

namespace App\Models;

use App\Enums\PromotionStatus;
use App\Enums\PromotionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'value',
        'start_date',
        'end_date',
        'min_purchase',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => PromotionType::class,
            'value' => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Produk yang memenuhi syarat promo ini.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_products');
    }

    /**
     * Promo yang sedang berlaku pada tanggal tertentu.
     */
    public function scopeCurrentlyActive(Builder $query, ?Carbon $date = null): Builder
    {
        $date = $date ?? now();

        return $query->where('is_active', true)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString());
    }

    /**
     * Promo yang masa berlakunya sudah lewat.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereDate('end_date', '<', now()->toDateString());
    }

    /**
     * Promo yang belum mulai.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('start_date', '>', now()->toDateString());
    }

    /**
     * Status dinamis promo (aktif / belum mulai / berakhir / nonaktif).
     */
    public function currentStatus(): PromotionStatus
    {
        if (! $this->is_active) {
            return PromotionStatus::Inactive;
        }

        $today = now()->toDateString();

        if ($this->start_date->toDateString() > $today) {
            return PromotionStatus::Upcoming;
        }

        if ($this->end_date->toDateString() < $today) {
            return PromotionStatus::Expired;
        }

        return PromotionStatus::Active;
    }

    /**
     * Hitung nilai diskon untuk sebuah subtotal.
     *
     * @param  float  $subtotal  Subtotal sebelum diskon.
     * @param  float  $max  Batas atas nilai diskon (tidak boleh melebihi subtotal).
     */
    public function calculateDiscount(float $subtotal, float $max = PHP_FLOAT_MAX): float
    {
        $value = (float) $this->value;

        $discount = $this->type === PromotionType::Percent
            ? $subtotal * $value / 100
            : $value;

        $discount = min($discount, $subtotal, $max);

        return round(max(0, $discount), 2);
    }
}
