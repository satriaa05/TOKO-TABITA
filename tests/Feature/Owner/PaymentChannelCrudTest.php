<?php

namespace Tests\Feature\Owner;

use App\Models\PaymentChannel;
use App\Models\PaymentTransaction;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\PaymentChannelSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentChannelCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentChannelSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_owner_bisa_mengelola_semua_metode_pembayaran(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('owner.payment-channels.index'))->assertOk();
        $this->actingAs($owner)->get(route('owner.payment-channels.create'))->assertOk();

        $this->actingAs($owner)->post(route('owner.payment-channels.store'), [
            'code' => 'permata_va',
            'name' => 'Virtual Account Permata',
            'provider' => 'midtrans',
            'mode' => 'automatic',
            'status' => 'coming_soon',
            'is_active' => 0,
            'is_visible' => 1,
            'sort_order' => 8,
        ])->assertRedirect(route('owner.payment-channels.index'));

        $channel = PaymentChannel::query()->where('code', 'permata_va')->firstOrFail();
        $this->assertFalse($channel->is_active);
        $this->assertTrue($channel->is_visible);
        $this->assertTrue($channel->isComingSoon());

        $this->actingAs($owner)->put(
            route('owner.payment-channels.update', ['pembayaran_metode' => $channel]),
            [
                'code' => 'permata_va',
                'name' => 'VA Permata',
                'mode' => 'automatic',
                'status' => 'available',
                'is_active' => 1,
                'is_visible' => 1,
            ]
        )->assertRedirect(route('owner.payment-channels.index'));

        $this->assertSame('VA Permata', $channel->fresh()->name);
        $this->assertTrue($channel->fresh()->is_active);

        $this->actingAs($owner)->delete(
            route('owner.payment-channels.destroy', ['pembayaran_metode' => $channel])
        )->assertRedirect(route('owner.payment-channels.index'));

        $this->assertSame(0, PaymentChannel::query()->where('code', 'permata_va')->count());
    }

    public function test_kode_duplikat_ditolak(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post(route('owner.payment-channels.store'), [
            'code' => 'transfer',
            'name' => 'Duplikat',
            'mode' => 'manual',
            'status' => 'available',
            'is_visible' => 1,
        ])->assertSessionHasErrors('code');
    }

    public function test_metode_yang_sudah_dipakai_transaksi_tidak_bisa_dihapus(): void
    {
        $owner = User::factory()->owner()->create();
        $channel = PaymentChannel::query()->where('code', 'transfer')->firstOrFail();
        $buyer = User::factory()->create(['role' => \App\Enums\UserRole::Customer]);
        $sale = Sale::query()->create([
            'invoice_number' => 'INV-PC-'.rand(1000, 9999),
            'user_id' => $buyer->id,
            'buyer_name' => $buyer->name,
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Test',
            'subtotal' => 100000,
            'total' => 100000,
            'status' => 'pending_payment',
        ]);
        PaymentTransaction::query()->create([
            'sale_id' => $sale->id,
            'payment_method_id' => $channel->id,
            'transaction_reference' => 'TRX-PC-'.$sale->id,
            'idempotency_key' => 'pc-'.$sale->id,
            'amount' => 100000,
            'status' => 'unpaid',
        ]);

        $this->actingAs($owner)->delete(
            route('owner.payment-channels.destroy', ['pembayaran_metode' => $channel])
        )->assertSessionHas('error');

        $this->assertNotNull($channel->fresh());
    }

    public function test_rekening_bca_tampil_di_halaman_pembayaran(): void
    {
        $buyer = User::factory()->create(['role' => \App\Enums\UserRole::Customer]);
        $channel = PaymentChannel::query()->where('code', 'transfer')->firstOrFail();
        $sale = Sale::query()->create([
            'invoice_number' => 'INV-BCA-'.rand(1000, 9999),
            'user_id' => $buyer->id,
            'buyer_name' => $buyer->name,
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Test',
            'subtotal' => 100000,
            'total' => 100000,
            'status' => 'pending_payment',
        ]);
        PaymentTransaction::query()->create([
            'sale_id' => $sale->id,
            'payment_method_id' => $channel->id,
            'transaction_reference' => 'TRX-BCA-'.$sale->id,
            'idempotency_key' => 'bca-'.$sale->id,
            'amount' => 100000,
            'status' => 'unpaid',
        ]);

        $this->actingAs($buyer)->get(route('store.payment.show', $sale))
            ->assertOk()
            ->assertSee('BCA')
            ->assertSee('7963069288')
            ->assertSee('RETNO ADWIANTI');
    }
}
