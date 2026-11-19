<?php

namespace App\Enums;

/**
 * Status pengiriman hasil normalisasi dari respons Biteship.
 *
 * Jangan menganggap semua ekspedisi punya tahapan yang sama. Status
 * mentah dari provider disimpan apa adanya di shipment_tracking_events,
 * lalu dipetakan ke enum ini untuk keperluan tampilan.
 */
enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Allocated = 'allocated';
    case PickingUp = 'picking_up';
    case Picked = 'picked';
    case InTransit = 'in_transit';
    case DroppingOff = 'dropping_off';
    case Delivered = 'delivered';
    case OnHold = 'on_hold';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Kurir',
            self::Confirmed => 'Pesanan Dikonfirmasi',
            self::Allocated => 'Kurir Ditugaskan',
            self::PickingUp => 'Kurir Menuju Lokasi Pengambilan',
            self::Picked => 'Paket Diambil Kurir',
            self::InTransit => 'Paket Dalam Perjalanan',
            self::DroppingOff => 'Paket Sedang Diantar',
            self::Delivered => 'Paket Diterima',
            self::OnHold => 'Paket Tertahan',
            self::Returned => 'Paket Dikembalikan',
            self::Cancelled => 'Pengiriman Dibatalkan',
            self::Failed => 'Pengiriman Gagal',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending, self::OnHold => 'text-bg-secondary',
            self::Confirmed, self::Allocated, self::PickingUp => 'text-bg-info',
            self::Picked => 'text-bg-primary',
            self::InTransit, self::DroppingOff => 'text-bg-warning',
            self::Delivered => 'text-bg-success',
            self::Returned, self::Cancelled, self::Failed => 'text-bg-danger',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Returned, self::Cancelled, self::Failed], true);
    }

    /**
     * Petakan status mentah Biteship ke status normalisasi.
     *
     * @see https://biteship.com/en/docs/api/trackings/status
     */
    public static function fromBiteship(string $raw): self
    {
        $key = strtolower(str_replace([' ', '-'], '_', trim($raw)));

        return match ($key) {
            'confirmed' => self::Confirmed,
            'allocated' => self::Allocated,
            'pickingup', 'picking_up' => self::PickingUp,
            'picked' => self::Picked,
            'intransit', 'in_transit' => self::InTransit,
            'droppingoff', 'dropping_off' => self::DroppingOff,
            'delivered' => self::Delivered,
            'onhold', 'on_hold' => self::OnHold,
            'returnintransit', 'return_in_transit', 'returned' => self::Returned,
            'rejected', 'couriernotfound', 'courier_not_found', 'disposed' => self::Failed,
            'cancelled', 'canceled' => self::Cancelled,
            default => self::Pending,
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $s): string => $s->value, self::cases());
    }
}
