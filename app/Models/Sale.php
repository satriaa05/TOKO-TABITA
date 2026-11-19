<?php

namespace App\Models;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
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
        'buyer_postal_code',
        'buyer_note',
        'shipping_zone',
        'shipping_provider',
        'shipping_cost',
        'shipping_eta',
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
        'stock_released_at',
    ];

    /**
     * Nilai awal saat model dibuat, sejajar dengan default di migration.
     * Tanpa ini, enum akan bernilai null sebelum baris di-refresh.
     */
    protected $attributes = [
        'status' => 'pending_payment',
        'payment_status' => 'unpaid',
        'fulfillment_status' => 'pending',
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
            'payment_status' => PaymentStatus::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'stock_released_at' => 'datetime',
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
     * Percobaan pembayaran gateway/manual untuk pesanan ini.
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->latest();
    }

    /**
     * Data pengiriman (bisa lebih dari satu jika ada pengiriman ulang).
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class)->latest();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('created_at');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(CustomerNotification::class);
    }

    public function voucherUsages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function scopePaymentStatus(Builder $query, PaymentStatus $status): Builder
    {
        return $query->where('payment_status', $status->value);
    }

    public function scopeFulfillmentStatus(Builder $query, FulfillmentStatus $status): Builder
    {
        return $query->where('fulfillment_status', $status->value);
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
