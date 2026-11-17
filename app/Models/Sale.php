<?php

namespace App\Models;

use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'buyer_name',
        'buyer_phone',
        'buyer_address',
        'buyer_note',
        'shipping_zone',
        'shipping_provider',
        'shipping_cost',
        'subtotal',
        'discount_total',
        'total',
        'paid_amount',
        'change_amount',
        'status',
        'refunded_at',
        'refunded_by',
        'refund_reason',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'status' => SaleStatus::class,
            'refunded_at' => 'datetime',
        ];
    }

    /**
     * Pengguna yang memproses transaksi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Detail barang yang terjual.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Record pembayaran.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Bukti pembayaran yang diunggah pembeli.
     */
    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class)->latest();
    }

    /**
     * Admin yang memproses refund (jika ada).
     */
    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    // -----------------------------------------------------------------
    // Scope
    // -----------------------------------------------------------------

    /**
     * Transaksi aktif: belum direfund dan belum dibatalkan.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::Completed->value);
    }

    /**
     * Transaksi dalam rentang tanggal (inklusif, berbasis tanggal lokal).
     */
    public function scopeBetweenDates(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [
            $from.' 00:00:00',
            $to.' 23:59:59',
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === SaleStatus::Completed;
    }

    /**
     * Laba kotor transaksi = (pendapatan setelah diskon) - (harga pokok terjual).
     *
     * Angka ini BUKAN laba bersih.
     */
    public function grossProfit(): float
    {
        $revenue = (float) $this->total;
        $cost = (float) $this->items->sum(
            fn (SaleItem $item): float => (float) $item->cost_price * $item->qty
        );

        return round($revenue - $cost, 2);
    }
}
