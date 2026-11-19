<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentTrackingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'raw_status',
        'normalized_status',
        'description',
        'location',
        'latitude',
        'longitude',
        'event_time',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'normalized_status' => ShipmentStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'event_time' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function hasCoordinate(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
