<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'old_payment_status',
        'new_payment_status',
        'old_fulfillment_status',
        'new_fulfillment_status',
        'changed_by',
        'source',
        'note',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
