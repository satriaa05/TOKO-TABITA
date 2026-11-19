<?php

namespace Tests\Feature\Access;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionDefaultDenyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_tanpa_role_permissions_ditolak(): void
    {
        $admin = \App\Models\User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/produk')->assertForbidden();
        $this->actingAs($admin)->get('/admin/pesanan')->assertForbidden();
    }

    public function test_admin_dengan_permission_seed_bisa_mengakses_modulnya(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = \App\Models\User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/produk')->assertOk();
        $this->actingAs($admin)->get('/admin/pesanan')->assertOk();
        $this->actingAs($admin)->get('/admin/audit-log')->assertOk();
    }

    public function test_owner_tetap_penuh_dan_pembeli_ditolak(): void
    {
        $this->seed(PermissionSeeder::class);
        $owner = \App\Models\User::factory()->owner()->create();
        $customer = \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::Customer]);

        $this->actingAs($owner)->get('/admin/produk')->assertOk();
        $this->actingAs($owner)->get('/admin/audit-log')->assertOk();
        $this->actingAs($customer)->get('/admin/audit-log')->assertForbidden();
    }

    public function test_permission_yang_dicabut_admin_langsung_berlaku(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = \App\Models\User::factory()->admin()->create();

        \Illuminate\Support\Facades\DB::table('role_permissions')
            ->where('role', 'admin')
            ->delete();

        $this->actingAs($admin)->get('/admin/produk')->assertForbidden();
    }
}
