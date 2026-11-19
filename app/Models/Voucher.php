<?php

namespace App\Models;

use App\Enums\VoucherType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'min_order',
        'max_discount',
        'quota',
        'used_count',
        'per_user_limit',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $attributes = [
        'min_order' => 0,
        'used_count' => 0,
        'per_user_limit' => 1,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'type' => VoucherType::class,
            'value' => 'decimal:2',
            'min_order' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function isValidNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return $this->quota === null || $this->used_count < $this->quota;
    }

    /**
     * Hitung diskon. Seluruh perhitungan dilakukan di backend.
     */
    public function discountFor(float $subtotal): float
    {
        if ($subtotal < (float) $this->min_order) {
            return 0.0;
        }

        $discount = $this->type === VoucherType::Percent
            ? round($subtotal * (float) $this->value / 100, 2)
            : round((float) $this->value, 2);

        if ($this->max_discount !== null) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }

    public function usageByUser(int $userId): int
    {
        return $this->usages()->where('user_id', $userId)->count();
    }
}
