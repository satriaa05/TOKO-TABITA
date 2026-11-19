<?php

namespace App\Enums;

/**
 * Jenis notifikasi pelanggan. Satu event = satu notifikasi (event_key unik).
 */
enum NotificationType: string
{
    case OrderCreated = 'order_created';
    case PaymentSuccess = 'payment_success';
    case PaymentFailed = 'payment_failed';
    case PaymentExpired = 'payment_expired';
    case OrderProcessing = 'order_processing';
    case OrderPacked = 'order_packed';
    case OrderHandedToCourier = 'order_handed_to_courier';
    case OrderShipped = 'order_shipped';
    case OrderInTransit = 'order_in_transit';
    case OrderOutForDelivery = 'order_out_for_delivery';
    case OrderDelivered = 'order_delivered';

    public function label(): string
    {
        return match ($this) {
            self::OrderCreated => 'Pesanan Dibuat',
            self::PaymentSuccess => 'Pembayaran Berhasil',
            self::PaymentFailed => 'Pembayaran Gagal',
            self::PaymentExpired => 'Pembayaran Kedaluwarsa',
            self::OrderProcessing => 'Pesanan Diproses',
            self::OrderPacked => 'Pesanan Dikemas',
            self::OrderHandedToCourier => 'Paket Diserahkan ke Kurir',
            self::OrderShipped => 'Paket Dikirim',
            self::OrderInTransit => 'Paket Dalam Perjalanan',
            self::OrderOutForDelivery => 'Paket Sedang Diantar',
            self::OrderDelivered => 'Paket Diterima',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::OrderCreated => 'bi-cart-check',
            self::PaymentSuccess => 'bi-check2-circle',
            self::PaymentFailed => 'bi-x-circle',
            self::PaymentExpired => 'bi-clock-history',
            self::OrderProcessing => 'bi-gear',
            self::OrderPacked => 'bi-box',
            self::OrderHandedToCourier => 'bi-truck',
            self::OrderShipped => 'bi-truck',
            self::OrderInTransit => 'bi-signpost-split',
            self::OrderOutForDelivery => 'bi-bicycle',
            self::OrderDelivered => 'bi-house-check',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $s): string => $s->value, self::cases());
    }
}
