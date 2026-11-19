<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'courier',
        'service',
        'tracking_number',
        'biteship_order_id',
        'biteship_tracking_id',
        'shipping_cost',
        'shipping_eta',
        'shipment_status',
        'tracking_mode',
        'live_tracking_url',
        'driver_name',
        'driver_plate',
        'estimated_delivery_start',
        'estimated_delivery_end',
        'last_scan_latitude',
        'last_scan_longitude',
        'last_scan_location',
        'last_scan_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_cost' => 'decimal:2',
            'shipment_status' => ShipmentStatus::class,
            'estimated_delivery_start' => 'datetime',
            'estimated_delivery_end' => 'datetime',
            'last_scan_latitude' => 'float',
            'last_scan_longitude' => 'float',
            'last_scan_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentTrackingEvent::class)->orderByDesc('event_time');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ShipmentLocation::class)->orderByDesc('recorded_at');
    }

    public function isLive(): bool
    {
        return $this->tracking_mode === 'live' && $this->live_tracking_url !== null;
    }

    public function hasScanCoordinate(): bool
    {
        return $this->last_scan_latitude !== null && $this->last_scan_longitude !== null;
    }

    /**
     * Estimasi tiba ramah tampilan. Null bila provider tidakmemberikan data.
     */
    public function etaLabel(): ?string
    {
        if (! $this->estimated_delivery_start && ! $this->estimated_delivery_end) {
            return null;
        }

        if ($this->estimated_delivery_start && $this->estimated_delivery_end) {
            if ($this->estimated_delivery_start->isSameDay($this->estimated_delivery_end)) {
                return $this->estimated_delivery_start->translatedFormat('d M Y');
            }

            return $this->estimated_delivery_start->translatedFormat('d M')
                .' – '.$this->estimated_delivery_end->translatedFormat('d M Y');
        }

        return ($this->estimated_delivery_start ?? $this->estimated_delivery_end)?->translatedFormat('d M Y');
    }
}
