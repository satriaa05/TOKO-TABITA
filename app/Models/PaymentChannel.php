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
        'bank_name',
        'account_holder',
        'account_number',
        'logo_path',
        'description',
        'is_active',
        'is_visible',
        'status',
        'status_note',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'payment_method_id');
    }

    public function isAutomatic(): bool
    {
        return $this->mode === 'automatic';
    }

    public function isComingSoon(): bool
    {
        return $this->status === 'coming_soon';
    }

    public function bankDetails(): string
    {
        $parts = array_filter([
            $this->bank_name,
            $this->account_number ? 'No. '.$this->account_number : null,
            $this->account_holder ? 'a.n. '.$this->account_holder : null,
        ]);

        return $parts ? implode(' · ', $parts) : '-';
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, static> */
    public static function visibleChannels()
    {
        return static::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();
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
