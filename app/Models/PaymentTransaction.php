<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'payment_method_id',
        'transaction_reference',
        'provider_reference',
        'idempotency_key',
        'amount',
        'status',
        'payment_url',
        'qr_code_url',
        'deeplink_url',
        'acquirer',
        'expired_at',
        'paid_at',
        'failure_detail',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
            'failure_detail' => 'array',
            'metadata' => 'array',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(PaymentChannel::class, 'payment_method_id');
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(PaymentWebhookLog::class, 'transaction_reference', 'transaction_reference');
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }
}
