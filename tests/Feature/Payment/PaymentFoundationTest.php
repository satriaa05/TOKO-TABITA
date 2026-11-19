<?php

namespace Tests\Feature\Payment;

use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Enums\VoucherType;
use App\Models\CustomerNotification;
use App\Models\PaymentChannel;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookLog;
use App\Models\Sale;
use App\Models\Shipment;
use App\Models\ShipmentTrackingEvent;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fondasi data untuk checkout modern: status terpisah, katalog metode
 * pembayaran, shipment, webhook log, notifikasi, dan voucher.
 */
class PaymentFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_status_pembayaran_dan_pengiriman_terpisah(): void
    {
        $sale = $this->makeSale();

        $this->assertSame(PaymentStatus::Unpaid, $sale->payment_status);
        $this->assertSame(FulfillmentStatus::Pending, $sale->fulfillment_status);
        $this->assertNull($sale->paid_at);
        $this->assertNull($sale->completed_at);
    }

    public function test_katalog_metode_pembayaran_terseedi_dan_hanya_transfer_aktif(): void
    {
        $this->assertSame(7, PaymentChannel::count());
        $this->assertSame(['transfer'], PaymentChannel::activeChannels()->pluck('code')->all());

        $qris = PaymentChannel::query()->where('code', 'qris')->firstOrFail();
        $this->assertSame('midtrans', $qris->provider);
        $this->assertTrue($qris->isAutomatic());
        $this->assertFalse($qris->is_active);
    }

    public function test_relasi_transaksi_pembayaran_tersimpan(): void
    {
        $sale = $this->makeSale();
        $channel = PaymentChannel::query()->where('code', 'transfer')->firstOrFail();

        $transaction = PaymentTransaction::create([
            'sale_id' => $sale->id,
            'payment_method_id' => $channel->id,
            'transaction_reference' => 'PAY-'.Str::upper(Str::random(8)),
            'idempotency_key' => Str::random(40),
            'amount' => 100000,
            'status' => PaymentStatus::Pending->value,
        ]);

        $this->assertTrue($sale->paymentTransactions->contains($transaction));
        $this->assertSame(PaymentStatus::Pending, $transaction->fresh()->status);
        $this->assertSame($sale->id, $transaction->sale->id);
    }

    public function test_webhook_log_menolak_event_id_duplikat(): void
    {
        $payload = [
            'event_id' => 'evt-112233',
            'provider' => 'midtrans',
            'transaction_reference' => 'PAY-1',
            'sanitized_payload' => ['transaction_status' => 'settlement', 'gross_amount' => '100000.00'],
            'signature_valid' => true,
        ];

        PaymentWebhookLog::create($payload);

        $this->expectException(QueryException::class);

        PaymentWebhookLog::create($payload);
    }

    public function test_pemetaan_status_biteship(): void
    {
        $this->assertSame(ShipmentStatus::InTransit, ShipmentStatus::fromBiteship('inTransit'));
        $this->assertSame(ShipmentStatus::DroppingOff, ShipmentStatus::fromBiteship('dropping_off'));
        $this->assertSame(ShipmentStatus::PickingUp, ShipmentStatus::fromBiteship('pickingUp'));
        $this->assertSame(ShipmentStatus::Pending, ShipmentStatus::fromBiteship('status_tidak_dikenal'));
    }

    public function test_tracking_event_duplikat_ditolak(): void
    {
        $sale = $this->makeSale();

        $shipment = Shipment::create([
            'sale_id' => $sale->id,
            'courier' => 'jne',
            'service' => 'REG',
            'tracking_number' => 'JNE123456',
        ]);

        $data = [
            'shipment_id' => $shipment->id,
            'raw_status' => 'inTransit',
            'normalized_status' => ShipmentStatus::InTransit->value,
            'description' => 'Paket dalam perjalanan',
            'event_time' => now(),
            'source' => 'webhook',
        ];

        ShipmentTrackingEvent::create($data);

        $this->assertCount(1, $shipment->events);
        $this->expectException(QueryException::class);

        ShipmentTrackingEvent::create($data);
    }

    public function test_voucher_menghitung_diskon_di_backend(): void
    {
        $voucher = Voucher::create([
            'code' => 'HEMAT10',
            'name' => 'Diskon 10%',
            'type' => VoucherType::Percent->value,
            'value' => 10,
            'min_order' => 50000,
            'max_discount' => 20000,
        ]);

        $this->assertTrue($voucher->isValidNow());
        $this->assertEquals(10000, $voucher->discountFor(100000));
        $this->assertEquals(20000, $voucher->discountFor(500000));
        $this->assertEquals(0, $voucher->discountFor(10000));
    }

    public function test_notifikasi_pelanggan_tersimpan(): void
    {
        $sale = $this->makeSale();

        CustomerNotification::create([
            'user_id' => $sale->user_id,
            'sale_id' => $sale->id,
            'event_key' => 'order_created-'.$sale->id,
            'type' => NotificationType::OrderCreated->value,
            'title' => 'Pesanan dibuat',
            'message' => 'Pesanan '.$sale->invoice_number.' berhasil dibuat.',
        ]);

        $this->assertSame(1, $sale->user->notifications()->count());
        $this->assertSame(1, $sale->user->unreadNotifications()->count());
    }

    private function makeSale(): Sale
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'pembeli.fondasi@tokotabita.test'],
            [
                'name' => 'Pembeli Fondasi',
                'password' => bcrypt('password123'),
                'role' => 'customer',
                'is_active' => true,
            ]
        );

        return Sale::create([
            'invoice_number' => 'INV-'.Str::upper(Str::random(10)),
            'user_id' => $user->id,
            'buyer_name' => $user->name,
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Uji No. 1, Semarang',
            'subtotal' => 100000,
            'total' => 100000,
            'status' => 'pending_payment',
        ]);
    }
}
