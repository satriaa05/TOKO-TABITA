<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_id',
        'transaction_reference',
        'sanitized_payload',
        'signature_valid',
        'processing_status',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'sanitized_payload' => 'array',
            'signature_valid' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}
