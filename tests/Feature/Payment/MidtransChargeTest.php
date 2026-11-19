<?php

namespace Tests\Feature\Payment;

use App\Exceptions\PaymentGatewayException;
use App\Models\PaymentChannel;
use App\Models\PaymentTransaction;
use App\Models\Sale;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MidtransChargeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config()->set('payments.midtrans.server_key', 'server-key-rahasia');
        config()->set('payments.midtrans.api_url', 'https://api.sandbox.midtrans.com');
    }

    public function test_charge_qris_menyimpan_referensi_dan_qr(): void
    {
        Http::fake([
            '*api.sandbox.midtrans.com/v2/charge' => Http::response([
                'transaction_id' => 'MID-QR-123',
                'status_code' => '201',
                'transaction_status' => 'pending',
                'acquirer' => 'gopay',
                'expiry_time' => now()->addMinutes(30)->toIso8601String(),
                'actions' => [
                    ['name' => 'generate-qr-code-v2', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/MID-QR-123/qr-code'],
                ],
            ], 201),
        ]);

        $transaction = $this->makeTransaction('qris');

        $result = app(MidtransService::class)->charge($transaction);

        $this->assertSame('MID-QR-123', $result['provider_reference']);
        $this->assertNotNull($result['qr_code_url']);
        $this->assertSame('MID-QR-123', $transaction->fresh()->provider_reference);
        $this->assertNotNull($transaction->fresh()->qr_code_url);

        Http::assertSent(function (Request $request) use ($transaction) {
            return str_contains($request->url(), '/v2/charge')
                && $request['payment_type'] === 'qris'
                && $request['transaction_details']['order_id'] === $transaction->transaction_reference
                && $request['transaction_details']['gross_amount'] === 150000;
        });
    }

    public function test_gateway_belum_dikonfigurasi_tidak_mengubah_transaksi(): void
    {
        config()->set('payments.midtrans.server_key', null);
        $transaction = $this->makeTransaction('qris');

        $this->expectException(PaymentGatewayException::class);

        app(MidtransService::class)->charge($transaction);
    }

    private function makeTransaction(string $code): PaymentTransaction
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'pembeli.qris@tokotabita.test'],
            ['name' => 'Pembeli QRIS', 'password' => bcrypt('password123'), 'role' => 'customer', 'is_active' => true]
        );

        $sale = Sale::create([
            'invoice_number' => 'INV-QRIS-'.Str::upper(Str::random(8)),
            'user_id' => $user->id,
            'buyer_name' => $user->name,
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. QRIS No. 1',
            'subtotal' => 150000,
            'total' => 150000,
            'status' => 'pending_payment',
        ]);

        return PaymentTransaction::create([
            'sale_id' => $sale->id,
            'payment_method_id' => PaymentChannel::query()->where('code', $code)->firstOrFail()->id,
            'transaction_reference' => 'TRX-'.$sale->invoice_number,
            'idempotency_key' => Str::random(40),
            'amount' => 150000,
            'status' => 'unpaid',
        ]);
    }
}
