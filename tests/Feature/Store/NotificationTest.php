<?php

namespace Tests\Feature\Store;

use App\Enums\NotificationType;
use App\Models\CustomerNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_pembeli_melihat_notifikasi_dan_menandainya_dibaca(): void
    {
        $buyer = User::query()->create([
            'name' => 'Pembeli Notif',
            'email' => 'pembeli.notif@tokotabita.test',
            'password' => bcrypt('password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $notif = CustomerNotification::create([
            'user_id' => $buyer->id,
            'event_key' => 'order_created-test',
            'type' => NotificationType::OrderCreated->value,
            'title' => 'Pesanan dibuat',
            'message' => 'Pesanan Anda berhasil dibuat.',
        ]);

        $this->actingAs($buyer)->get(route('store.notifications.index'))
            ->assertOk()
            ->assertSee('Pesanan dibuat')
            ->assertSee('Pesanan Anda berhasil dibuat.');

        $this->actingAs($buyer)->post(route('store.notifications.read', $notif))->assertRedirect();

        $this->assertNotNull($notif->fresh()->read_at);
        $this->assertSame(0, $buyer->unreadNotifications()->count());
    }

    public function test_pembeli_tidak_bisa_menandai_notifikasi_orang_lain(): void
    {
        $buyer = User::query()->create([
            'name' => 'Pembeli Notif',
            'email' => 'pembeli.notif2@tokotabita.test',
            'password' => bcrypt('password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);
        $other = User::query()->create([
            'name' => 'Pembeli Lain',
            'email' => 'pembeli.notif3@tokotabita.test',
            'password' => bcrypt('password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $notif = CustomerNotification::create([
            'user_id' => $other->id,
            'event_key' => 'order_created-other',
            'type' => NotificationType::OrderCreated->value,
            'title' => 'Notifikasi orang lain',
            'message' => 'Tidak boleh diakses.',
        ]);

        $this->actingAs($buyer)->post(route('store.notifications.read', $notif))->assertForbidden();
    }
}
