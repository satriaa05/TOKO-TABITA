<?php

namespace Tests\Feature\Owner;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\PaymentChannel;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookLog;
use App\Models\Sale;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_owner_melihat_monitoring_dengan_data_nyata(): void
    {
        $owner = User::query()->where('role', UserRole::Owner->value)->firstOrFail();

        $sale = Sale::create([
            'invoice_number' => 'INV-MON-'.Str::upper(Str::random(8)),
            'buyer_name' => 'Pembeli Monitoring',
            'subtotal' => 200000,
            'shipping_cost' => 15000,
            'total' => 215000,
            'status' => 'completed',
        ]);
        $sale->forceFill([
            'payment_status' => PaymentStatus::Paid->value,
            'fulfillment_status' => FulfillmentStatus::Shipped->value,
            'paid_at' => now(),
        ])->save();

        PaymentTransaction::create([
            'sale_id' => $sale->id,
            'payment_method_id' => PaymentChannel::query()->where('code', 'transfer')->firstOrFail()->id,
            'transaction_reference' => 'TRX-MON-1',
            'idempotency_key' => Str::random(40),
            'amount' => 215000,
            'status' => PaymentStatus::Paid->value,
            'paid_at' => now(),
        ]);

        Shipment::create([
            'sale_id' => $sale->id,
            'courier' => 'jne',
            'service' => 'REG',
            'tracking_number' => null,
            'shipment_status' => 'in_transit',
        ]);

        PaymentWebhookLog::create([
            'provider' => 'midtrans',
            'event_id' => 'evt-mon-1',
            'sanitized_payload' => ['transaction_status' => 'settlement'],
            'signature_valid' => false,
            'processing_status' => 'invalid_signature',
        ]);

        $this->actingAs($owner)->get(route('owner.monitoring.index'))
            ->assertOk()
            ->assertSee('Monitoring Transaksi')
            ->assertSee('Pembayaran Berhasil')
            ->assertSee('Gangguan Integrasi')
            ->assertSee('invalid_signature');
    }

    public function test_admin_dan_pembeli_tidak_bisa_membuka_monitoring(): void
    {
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();
        $buyer = User::query()->create([
            'name' => 'Pembeli Monitoring',
            'email' => 'pembeli.mon@tokotabita.test',
            'password' => bcrypt('password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('owner.monitoring.index'))->assertForbidden();
        $this->actingAs($buyer)->get(route('owner.monitoring.index'))->assertForbidden();
    }
}
