<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\PaymentChannel;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config()->set('payments.midtrans.server_key', 'server-key-rahasia');
        Http::preventStrayRequests();
    }

    public function test_webhook_expire_setelah_lunas_tidak_mengubah_status_atau_stok(): void
    {
        [$sale, $transaction, $variant] = $this->makePaidSale(5, 2);

        $this->assertFalse(app(PaymentService::class)->markExpired($transaction));

        $this->assertSame(PaymentStatus::Paid, $transaction->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $sale->fresh()->payment_status);
        $this->assertSame(3, (int) $variant->fresh()->stock);
        $this->assertNull($sale->fresh()->stock_released_at);
    }

    public function test_settlement_setelah_expired_memotong_stok_ulang(): void
    {
        [$sale, $transaction, $variant] = $this->makeSale(5, 2);

        $payments = app(PaymentService::class);
        $this->assertTrue($payments->markExpired($transaction));
        $this->assertSame(5, (int) $variant->fresh()->stock);
        $this->assertNotNull($sale->fresh()->stock_released_at);

        $this->assertTrue($payments->markPaid($transaction, ['transaction_status' => 'settlement'], 'webhook', 'MID-LATE-1'));

        $this->assertSame(PaymentStatus::Paid, $transaction->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $sale->fresh()->payment_status);
        $this->assertSame(3, (int) $variant->fresh()->stock);
        $productId = (int) $sale->items->first()->product_id;
        $this->assertSame(3, (int) Product::query()->findOrFail($productId)->stock);
        $this->assertNull($sale->fresh()->stock_released_at);
    }

    public function test_gagal_expired_tidak_bisa_mengubah_status_failed(): void
    {
        [$sale, $transaction, $variant] = $this->makeSale(5, 2);
        $payments = app(PaymentService::class);

        $this->assertTrue($payments->markFailed($transaction));
        $this->assertFalse($payments->markExpired($transaction));

        $this->assertSame(PaymentStatus::Failed, $transaction->fresh()->status);
        $this->assertSame(5, (int) $variant->fresh()->stock);
    }

    public function test_webhook_yang_melempar_exception_dicatat_di_log_admin(): void
    {
        $mock = \Mockery::mock(PaymentService::class)->makePartial();
        $mock->shouldReceive('handleWebhook')->once()->andThrow(new \RuntimeException('boom'));
        $this->app->instance(PaymentService::class, $mock);

        $this->postJson('/payments/midtrans/webhook', ['order_id' => 'TRX-GAGAL'])->assertOk();

        $this->assertSame(1, PaymentWebhookLog::query()
            ->where('processing_status', 'failed')
            ->where('error_message', 'like', '%boom%')
            ->count());
    }

    public function test_qr_kedaluwarsa_bisa_dibuat_ulang_oleh_pemiliknya(): void
    {
        [$sale, $transaction] = $this->makeSale(5, 2, 'qris');
        $transaction->update([
            'provider_reference' => 'MID-OLD',
            'qr_code_url' => 'https://example.test/qr-lama.png',
            'expired_at' => now()->subMinute(),
            'status' => PaymentStatus::Unpaid->value,
        ]);

        Http::fake([
            '*/v2/charge' => Http::response([
                'transaction_id' => 'MID-BARU',
                'status_code' => '201',
                'transaction_status' => 'pending',
                'actions' => [['name' => 'qr-code', 'url' => 'https://example.test/qr-baru.png']],
            ], 200),
        ]);

        $this->actingAs($sale->user)->post(route('store.payment.refresh', $sale))
            ->assertRedirect();

        $fresh = $transaction->fresh();
        $this->assertStringEndsWith('-R1', $fresh->transaction_reference);
        $this->assertSame('MID-BARU', $fresh->provider_reference);
        $this->assertSame('https://example.test/qr-baru.png', $fresh->qr_code_url);
    }

    public function test_qr_baru_menolak_pesanan_orang_lain(): void
    {
        [$sale] = $this->makeSale(5, 2, 'qris');
        $stranger = User::query()->create([
            'name' => 'Orang Lain', 'email' => 'orang.lain@tokotabita.test',
            'password' => bcrypt('password123'), 'role' => 'customer', 'is_active' => true,
        ]);

        $this->actingAs($stranger)->post(route('store.payment.refresh', $sale))->assertForbidden();
    }

    /** @return array{Sale, PaymentTransaction, ProductVariant} */
    private function makeSale(int $stock, int $qty, string $channelCode = 'transfer'): array
    {
        $buyer = User::query()->firstOrCreate(
            ['email' => 'state.machine@tokotabita.test'],
            ['name' => 'Pembeli State', 'password' => bcrypt('password123'), 'role' => 'customer', 'is_active' => true]
        );
        $product = Product::create([
            'barcode' => 'TB-SM-'.Str::upper(Str::random(6)),
            'name' => 'Produk State Machine',
            'category_id' => Category::query()->firstOrFail()->id,
            'unit' => 'pcs', 'cost_price' => 50000, 'sale_price' => 100000,
            'stock' => $stock, 'min_stock' => 1, 'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'size' => 'M', 'color' => 'Hitam',
            'stock' => $stock, 'is_active' => true,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-SM-'.Str::upper(Str::random(8)),
            'user_id' => $buyer->id,
            'buyer_name' => $buyer->name,
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. State No. 1',
            'subtotal' => 100000 * $qty,
            'total' => 100000 * $qty,
            'status' => 'pending_payment',
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_variant_id' => $variant->id,
            'size' => 'M', 'color' => 'Hitam',
            'qty' => $qty, 'price' => 100000, 'cost_price' => 50000, 'total' => 100000 * $qty,
            'subtotal' => 100000 * $qty,
        ]);

        $transaction = PaymentTransaction::create([
            'sale_id' => $sale->id,
            'payment_method_id' => PaymentChannel::query()->where('code', $channelCode)->firstOrFail()->id,
            'transaction_reference' => 'TRX-'.$sale->invoice_number,
            'idempotency_key' => Str::random(40),
            'amount' => $sale->total,
            'status' => PaymentStatus::Unpaid->value,
        ]);

        // Stok sudah dipotong seperti layaknya checkout.
        $variant->decrement('stock', $qty);
        $product->decrement('stock', $qty);

        return [$sale->fresh(), $transaction, $variant->fresh()];
    }

    /** @return array{Sale, PaymentTransaction, ProductVariant} */
    private function makePaidSale(int $stock, int $qty): array
    {
        [$sale, $transaction, $variant] = $this->makeSale($stock, $qty);
        app(PaymentService::class)->markPaid($transaction, [], 'webhook', 'MID-PAID');

        return [$sale->fresh(), $transaction->fresh(), $variant->fresh()];
    }
}
