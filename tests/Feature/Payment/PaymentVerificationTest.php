<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentProofStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\PaymentChannel;
use App\Models\PaymentProof;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookLog;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config()->set('payments.midtrans.server_key', 'server-key-rahasia');
        Queue::fake();
    }

    public function test_webhook_valid_menandai_lunas_secara_idempotent(): void
    {
        [$sale, $transaction] = $this->makeSaleWithTransaction('transfer');

        $payload = $this->payload($transaction, 'settlement');

        $this->postJson('/payments/midtrans/webhook', $payload)->assertOk()->assertSee('OK');

        $this->assertSame(PaymentStatus::Paid, $sale->fresh()->payment_status);
        $this->assertSame('completed', $sale->fresh()->status->value);
        $this->assertSame(1, Payment::query()->where('sale_id', $sale->id)->count());

        // Webhook duplikat tidak boleh memproses ulang.
        $this->postJson('/payments/midtrans/webhook', $payload)->assertOk();

        $this->assertSame(1, Payment::query()->where('sale_id', $sale->id)->count());
        $this->assertSame(1, PaymentWebhookLog::query()
            ->where('transaction_reference', $transaction->transaction_reference)
            ->where('processing_status', 'processed')
            ->count());
    }

    public function test_webhook_signature_invalid_ditolak(): void
    {
        [$sale, $transaction] = $this->makeSaleWithTransaction('transfer');

        $payload = $this->payload($transaction, 'settlement');
        $payload['signature_key'] = 'palsu';

        $this->postJson('/payments/midtrans/webhook', $payload)->assertOk();

        $this->assertSame(PaymentStatus::Unpaid, $sale->fresh()->payment_status);
        $this->assertSame(1, PaymentWebhookLog::query()
            ->where('signature_valid', false)
            ->where('processing_status', 'invalid_signature')
            ->count());
    }

    public function test_webhook_nominal_tidak_cocok_ditolak(): void
    {
        [$sale, $transaction] = $this->makeSaleWithTransaction('transfer');

        $payload = $this->payload($transaction, 'settlement');
        $payload['gross_amount'] = '99999.00';
        $payload['signature_key'] = $this->sign($payload);

        $this->postJson('/payments/midtrans/webhook', $payload)->assertOk();

        $this->assertSame(PaymentStatus::Unpaid, $sale->fresh()->payment_status);
        $this->assertSame(0, Payment::query()->where('sale_id', $sale->id)->count());
    }

    public function test_webhook_expired_menandai_kedaluwarsa(): void
    {
        [$sale, $transaction] = $this->makeSaleWithTransaction('transfer');

        $this->postJson('/payments/midtrans/webhook', $this->payload($transaction, 'expire'))->assertOk();

        $this->assertSame(PaymentStatus::Expired, $sale->fresh()->payment_status);
    }

    public function test_fallback_status_gateway_tanpa_signature_key_tetap_memproses_pembayaran(): void
    {
        [$sale, $transaction] = $this->makeSaleWithTransaction('qris');
        $transaction->update(['provider_reference' => 'MID-GATEWAY-1']);

        $gatewayPayload = [
            'order_id' => $transaction->transaction_reference,
            'gross_amount' => '100000.00',
            'status_code' => '200',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'transaction_id' => 'MID-GATEWAY-1',
            'payment_type' => 'qris',
            // Respons GET status Midtrans tidak menyertakan signature_key webhook.
        ];

        Http::fake([
            '*' => Http::response($gatewayPayload, 200),
        ]);

        $result = app(\App\Services\PaymentService::class)->syncFromGateway($transaction);

        $this->assertTrue($result['ok']);
        $this->assertSame(PaymentStatus::Paid, $sale->fresh()->payment_status);
        $this->assertSame(1, Payment::query()->where('sale_id', $sale->id)->count());
    }

    public function test_admin_menyetujui_bukti_transfer_manual(): void
    {
        [$sale, $transaction] = $this->makeSaleWithTransaction('transfer');
        $proof = $this->makeProof($sale);
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.payments.proof.approve', $proof))
            ->assertRedirect();

        $this->assertSame(PaymentStatus::Paid, $sale->fresh()->payment_status);
        $this->assertSame(PaymentProofStatus::Approved, $proof->fresh()->status);
        $this->assertSame(1, Payment::query()->where('sale_id', $sale->id)->count());
    }

    public function test_admin_tidak_bisa_menyetujui_transaksi_otomatis_secara_manual(): void
    {
        [$sale, $transaction] = $this->makeSaleWithTransaction('qris');
        $proof = $this->makeProof($sale);
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.payments.proof.approve', $proof))
            ->assertStatus(400);

        $this->assertSame(PaymentStatus::Unpaid, $sale->fresh()->payment_status);
    }

    public function test_halaman_admin_pembayaran_dapat_diakses(): void
    {
        [$sale] = $this->makeSaleWithTransaction('transfer');
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.payments.show', $sale))->assertOk();
    }

    /** @return array{Sale, PaymentTransaction} */
    private function makeSaleWithTransaction(string $channelCode): array
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'pembeli.bayar@tokotabita.test'],
            ['name' => 'Pembeli Bayar', 'password' => bcrypt('password123'), 'role' => 'customer', 'is_active' => true]
        );

        $sale = Sale::create([
            'invoice_number' => 'INV-BAYAR-'.Str::upper(Str::random(8)),
            'user_id' => $user->id,
            'buyer_name' => $user->name,
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Bayar No. 1',
            'subtotal' => 100000,
            'total' => 100000,
            'status' => 'pending_payment',
        ]);

        $channel = PaymentChannel::query()->where('code', $channelCode)->firstOrFail();

        $transaction = PaymentTransaction::create([
            'sale_id' => $sale->id,
            'payment_method_id' => $channel->id,
            'transaction_reference' => 'TRX-'.$sale->invoice_number,
            'idempotency_key' => Str::random(40),
            'amount' => 100000,
            'status' => PaymentStatus::Unpaid->value,
        ]);

        return [$sale, $transaction];
    }

    /** @return array<string, mixed> */
    private function payload(PaymentTransaction $transaction, string $status): array
    {
        $payload = [
            'order_id' => $transaction->transaction_reference,
            'gross_amount' => '100000.00',
            'status_code' => '200',
            'transaction_status' => $status,
            'fraud_status' => 'accept',
            'transaction_id' => 'MID-'.Str::random(8),
            'payment_type' => 'qris',
        ];

        $payload['signature_key'] = $this->sign($payload);

        return $payload;
    }

    /** @param  array<string, mixed>  $payload */
    private function sign(array $payload): string
    {
        return hash('sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'server-key-rahasia'
        );
    }

    private function makeProof(Sale $sale): PaymentProof
    {
        return PaymentProof::create([
            'sale_id' => $sale->id,
            'disk' => 'public',
            'path' => 'payment-proofs/dummy.png',
            'original_name' => 'bukti.png',
            'mime' => 'image/png',
            'size' => 1024,
        ]);
    }
}
