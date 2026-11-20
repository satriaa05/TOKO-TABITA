<?php

namespace Tests\Feature\Store;

use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Models\CustomerAddress;
use App\Models\CustomerNotification;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutModernTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_checkout_menampilkan_alamat_kurir_dan_metode_aktif(): void
    {
        $buyer = $this->makeBuyer();
        $this->addProductToCart($buyer);

        $address = $this->makeAddress($buyer);

        $response = $this->actingAs($buyer)->get('/checkout');

        $response->assertOk()
            ->assertSee('Alamat Pengiriman')
            ->assertSee($address->recipient_name)
            ->assertSee('JNE')
            ->assertSee('Transfer Bank')
            ->assertSee('Total Pembayaran');

        $response->assertDontSee('name="payment_method" value="qris"', false)
            ->assertSee('Segera Hadir');
    }

    public function test_checkout_dengan_alamat_tersimpan_membuat_relasi_lengkap(): void
    {
        $buyer = $this->makeBuyer();
        $this->addProductToCart($buyer);
        $address = $this->makeAddress($buyer);

        $response = $this->actingAs($buyer)->post('/checkout', $this->payload([
            'address_id' => $address->id,
        ]));

        $response->assertSessionHasNoErrors();

        $sale = Sale::query()->where('user_id', $buyer->id)->latest('id')->firstOrFail();

        $this->assertSame(PaymentStatus::Unpaid, $sale->payment_status);
        $this->assertSame(FulfillmentStatus::Pending, $sale->fulfillment_status);
        $this->assertSame('pending_payment', $sale->status->value);
        $this->assertStringContainsString('Semarang', $sale->buyer_address);
        $this->assertStringContainsString('JNE', (string) $sale->shipping_provider);

        $transaction = PaymentTransaction::query()->where('sale_id', $sale->id)->firstOrFail();
        $this->assertSame('transfer', $transaction->channel->code);
        $this->assertEquals($sale->total, $transaction->amount);
        $this->assertSame(PaymentStatus::Unpaid, $transaction->status);

        $this->assertSame(1, OrderStatusHistory::query()->where('sale_id', $sale->id)->count());
        $this->assertSame(1, CustomerNotification::query()->where('sale_id', $sale->id)->count());
        $this->assertSame(
            NotificationType::OrderCreated,
            CustomerNotification::query()->where('sale_id', $sale->id)->firstOrFail()->type
        );

        $response->assertRedirect(route('store.payment.show', $sale));
        $this->actingAs($buyer)->get(route('store.payment.show', $sale))->assertOk();
    }

    public function test_voucher_divalidasi_dan_dipakai_di_server(): void
    {
        $buyer = $this->makeBuyer();
        $this->addProductToCart($buyer);

        Voucher::create([
            'code' => 'HEMAT25',
            'name' => 'Diskon 25 Ribu',
            'type' => 'fixed',
            'value' => 25000,
            'min_order' => 0,
        ]);

        $this->actingAs($buyer)
            ->postJson('/voucher/validasi', ['code' => 'hemat25'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('code', 'HEMAT25');

        $this->actingAs($buyer)->post('/checkout', $this->payload([
            'voucher_code' => 'HEMAT25',
        ]))->assertSessionHasNoErrors();

        $sale = Sale::query()->where('user_id', $buyer->id)->latest('id')->firstOrFail();

        $this->assertEquals(25000, $sale->discount_total);
        $this->assertSame(1, VoucherUsage::query()->where('sale_id', $sale->id)->count());
        $this->assertSame(1, Voucher::query()->where('code', 'HEMAT25')->value('used_count'));
    }

    public function test_metode_pembayaran_nonaktif_ditolak(): void
    {
        $buyer = $this->makeBuyer();
        $this->addProductToCart($buyer);

        $this->actingAs($buyer)->post('/checkout', $this->payload([
            'payment_method' => 'qris',
        ]))->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Sale::query()->where('user_id', $buyer->id)->count());
    }

    public function test_pembeli_tidak_bisa_memakai_alamat_orang_lain(): void
    {
        $buyer = $this->makeBuyer();
        $other = $this->makeBuyer('other.checkout@tokotabita.test');
        $address = $this->makeAddress($other);
        $this->addProductToCart($buyer);

        $this->actingAs($buyer)->post('/checkout', $this->payload([
            'address_id' => $address->id,
        ]))->assertForbidden();
    }

    public function test_pembeli_tidak_bisa_melihat_pembayaran_orang_lain(): void
    {
        $buyer = $this->makeBuyer();
        $other = $this->makeBuyer('other.payment@tokotabita.test');
        $this->addProductToCart($buyer);
        $this->actingAs($buyer)->post('/checkout', $this->payload());

        $sale = Sale::query()->where('user_id', $buyer->id)->firstOrFail();

        $this->actingAs($other)->get(route('store.payment.show', $sale))->assertForbidden();
        $this->actingAs($other)->post(route('store.payment.status', $sale))->assertForbidden();
    }

    public function test_checkout_stok_habis_ditolak(): void
    {
        $buyer = $this->makeBuyer();
        $product = $this->addProductToCart($buyer);

        Product::query()->where('id', $product->id)->update(['stock' => 0]);

        $this->actingAs($buyer)->post('/checkout', $this->payload())
            ->assertSessionHasErrors('cart');

        $this->assertSame(0, Sale::query()->where('user_id', $buyer->id)->count());
    }

    /** @return array<string, mixed> */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'buyer_name' => 'Pembeli Modern',
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Modern No. 5, Semarang',
            'shipping_zone' => 'jawa_tengah',
            'courier' => 'jne',
            'service' => 'REG',
            'payment_method' => 'transfer',
            'idempotency_key' => Str::random(40),
        ], $extra);
    }

    private function makeBuyer(string $email = 'pembeli.modern@tokotabita.test'): User
    {
        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Pembeli Modern',
                'password' => bcrypt('password123'),
                'role' => 'customer',
                'is_active' => true,
            ]
        );
    }

    private function makeAddress(User $user): CustomerAddress
    {
        return CustomerAddress::create([
            'user_id' => $user->id,
            'label' => 'Rumah',
            'recipient_name' => 'Penerima Tersimpan',
            'phone' => '081999888777',
            'address_line' => 'Jl. Tersimpan No. 9',
            'city' => 'Semarang',
            'province' => 'Jawa Tengah',
            'postal_code' => '50111',
            'is_default' => true,
        ]);
    }

    private function addProductToCart(User $buyer): Product
    {
        $product = Product::query()->where('is_active', true)->where('stock', '>', 0)->firstOrFail();

        $this->actingAs($buyer)->post('/keranjang/tambah', [
            'product_id' => $product->id,
            'qty' => 1,
        ])->assertSessionHasNoErrors();

        return $product;
    }
}
