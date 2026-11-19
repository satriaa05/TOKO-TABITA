<?php

namespace Tests\Feature\Store;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AboutAndInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_owner_settings_memiliki_field_konten_tentang(): void
    {
        $owner = User::query()->where('role', UserRole::Owner->value)->firstOrFail();

        $this->actingAs($owner)->get(route('owner.settings.index'))
            ->assertOk()
            ->assertSee('about_intro')
            ->assertSee('about_features')
            ->assertSee('Konten Halaman Tentang Kami');
    }

    public function test_halaman_tentang_memakai_konten_dari_settings(): void
    {
        Setting::set('about_title', 'Tentang Custom Tabita');
        Setting::set('about_intro', 'Intro yang diedit Owner.');

        $this->get(route('page.about'))
            ->assertOk()
            ->assertSee('Tentang Custom Tabita')
            ->assertSee('Intro yang diedit Owner.');
    }

    public function test_admin_dan_owner_bisa_mencetak_invoice(): void
    {
        $sale = $this->makeSale();
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();
        $owner = User::query()->where('role', UserRole::Owner->value)->firstOrFail();

        $this->actingAs($admin)->get(route('invoice.show', $sale))
            ->assertOk()
            ->assertSee($sale->invoice_number)
            ->assertSee('Total Pembayaran');

        $this->actingAs($owner)->get(route('invoice.show', $sale))
            ->assertOk()
            ->assertSee($sale->invoice_number);
    }

    public function test_pembeli_tidak_bisa_mencetak_invoice(): void
    {
        $sale = $this->makeSale();
        $buyer = User::query()->create([
            'name' => 'Pembeli Invoice',
            'email' => 'pembeli.invoice@tokotabita.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Customer,
            'is_active' => true,
        ]);

        $this->actingAs($buyer)->get(route('invoice.show', $sale))->assertForbidden();
    }

    private function makeSale(): Sale
    {
        $sale = Sale::create([
            'invoice_number' => 'INV-'.Str::upper(Str::random(9)),
            'buyer_name' => 'Pembeli Invoice',
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jl. Invoice No. 1',
            'subtotal' => 200000,
            'total' => 217000,
            'shipping_cost' => 17000,
            'status' => 'completed',
        ]);
        $sale->forceFill([
            'payment_status' => PaymentStatus::Paid->value,
            'paid_at' => now(),
        ])->save();

        return $sale;
    }
}
