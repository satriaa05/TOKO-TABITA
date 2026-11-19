<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Katalog metode pembayaran checkout.
 *
 * Nama model sengaja PaymentChannel (bukan PaymentMethod) agar tidak
 * bentrok dengan App\Enums\PaymentMethod yang dipakai modul POS.
 */
class PaymentChannel extends Model
{
    use HasFactory;

    protected $table = 'payment_methods';

    protected $fillable = [
        'code',
        'name',
        'provider',
        'mode',
        'logo_path',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function isAutomatic(): bool
    {
        return $this->mode === 'automatic';
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset($this->logo_path) : null;
    }

    /** @return Collection<int, static> */
    public static function activeChannels()
    {
        return static::query()->where('is_active', true)->orderBy('sort_order')->get();
    }
}
