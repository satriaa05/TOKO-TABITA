<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\ShipmentStatus;
use App\Mail\ShipmentStatusMail;
use App\Models\CustomerNotification;
use App\Models\Shipment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifikasi perubahan status pengiriman untuk pelanggan.
 *
 * Dipakai baik oleh aksi Admin maupun oleh sinkronisasi webhook Biteship,
 * supaya aturan anti-duplikat dan pengiriman email sama di semua jalur.
 */
class ShipmentNotificationService
{
    public function notify(Shipment $shipment, ShipmentStatus $status, string $source = 'admin'): void
    {
        $type = $this->typeFor($status);

        if ($type === null) {
            return;
        }

        $sale = $shipment->sale()->with('user')->firstOrFail();

        $notification = CustomerNotification::firstOrCreate(
            ['event_key' => $type->value.'-shipment-'.$shipment->id],
            [
                'user_id' => (int) $sale->user_id,
                'sale_id' => $sale->id,
                'type' => $type->value,
                'title' => $type->label(),
                'message' => $this->messageFor($status, $sale->invoice_number),
                'icon' => $type->icon(),
                'data' => [
                    'invoice_number' => $sale->invoice_number,
                    'courier' => $shipment->courier,
                    'tracking_number' => $shipment->tracking_number,
                    'source' => $source,
                ],
            ]
        );

        if (! $notification->wasRecentlyCreated) {
            return;
        }

        if ($sale->user?->email) {
            try {
                Mail::to($sale->user)->send(new ShipmentStatusMail(
                    $sale,
                    $type->label(),
                    $this->messageFor($status, $sale->invoice_number)
                ));
            } catch (\Throwable $e) {
                Log::error('Gagal email notifikasi pengiriman '.$sale->invoice_number.': '.$e->getMessage());
            }
        }
    }

    private function typeFor(ShipmentStatus $status): ?NotificationType
    {
        return match ($status) {
            ShipmentStatus::Confirmed, ShipmentStatus::Allocated => NotificationType::OrderProcessing,
            ShipmentStatus::PickingUp, ShipmentStatus::Picked => NotificationType::OrderHandedToCourier,
            ShipmentStatus::InTransit => NotificationType::OrderInTransit,
            ShipmentStatus::DroppingOff => NotificationType::OrderOutForDelivery,
            ShipmentStatus::Delivered => NotificationType::OrderDelivered,
            ShipmentStatus::OnHold, ShipmentStatus::Returned, ShipmentStatus::Cancelled, ShipmentStatus::Failed => null,
            default => null,
        };
    }

    private function messageFor(ShipmentStatus $status, string $invoice): string
    {
        return match ($status) {
            ShipmentStatus::Confirmed, ShipmentStatus::Allocated => 'Pesanan '.$invoice.' sedang disiapkan untuk pengiriman.',
            ShipmentStatus::PickingUp, ShipmentStatus::Picked => 'Paket pesanan '.$invoice.' diserahkan ke kurir.',
            ShipmentStatus::InTransit => 'Paket pesanan '.$invoice.' sedang dalam perjalanan.',
            ShipmentStatus::DroppingOff => 'Paket pesanan '.$invoice.' sedang diantar ke alamat Anda.',
            ShipmentStatus::Delivered => 'Paket pesanan '.$invoice.' telah diterima. Terima kasih!',
            default => 'Status pengiriman pesanan '.$invoice.' diperbarui.',
        };
    }
}
