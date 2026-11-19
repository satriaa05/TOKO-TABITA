<?php

namespace Tests\Feature\Access;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAndRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_admin_dan_owner_bisa_membuka_halaman_profil(): void
    {
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();
        $owner = User::query()->where('role', UserRole::Owner->value)->firstOrFail();

        $this->actingAs($admin)->get(route('profile.index'))
            ->assertOk()->assertSee('Profil Saya')->assertSee('Ganti Password');
        $this->actingAs($owner)->get(route('profile.index'))->assertOk();
    }

    public function test_profil_bisa_diubah_dan_email_unik_divalidasi(): void
    {
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();
        $owner = User::query()->where('role', UserRole::Owner->value)->firstOrFail();

        $this->actingAs($admin)->put(route('profile.update'), [
            'name' => 'Admin Updated',
            'email' => $admin->email,
            'phone' => '08123456789',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Admin Updated', $admin->fresh()->name);

        $this->actingAs($admin)->put(route('profile.update'), [
            'name' => 'Admin Updated',
            'email' => $owner->email,
        ])->assertSessionHasErrors('email');
    }

    public function test_ganti_password_memeriksa_password_lama(): void
    {
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();

        $this->actingAs($admin)->put(route('profile.password'), [
            'current_password' => 'salah',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($admin)->put(route('profile.password'), [
            'current_password' => 'Tabita-Admin-123',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('password-baru-123', $admin->fresh()->password));
    }

    public function test_upload_foto_profil(): void
    {
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();

        $file = UploadedFile::fake()->createWithContent(
            'foto.png',
            (string) file_get_contents(dirname(__DIR__, 2).'/fixtures/product.png')
        );

        $this->actingAs($admin)->post(route('profile.photo'), ['avatar' => $file])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($admin->fresh()->avatar);
        Storage::disk('public')->assertExists($admin->fresh()->avatar);
    }

    public function test_admin_tidak_bisa_mengakses_management_akun_owner(): void
    {
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();

        $this->actingAs($admin)->get(route('owner.users.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('owner.settings.index'))->assertForbidden();
    }

    public function test_owner_bisa_membuat_admin_tapi_tidak_bisa_membuat_owner(): void
    {
        $owner = User::query()->where('role', UserRole::Owner->value)->firstOrFail();

        $this->actingAs($owner)->post(route('owner.users.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin.baru@tokotabita.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'owner',
        ])->assertSessionHasErrors('role');

        $this->actingAs($owner)->post(route('owner.users.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin.baru@tokotabita.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'admin.baru@tokotabita.test',
            'role' => 'admin',
        ]);
    }

    public function test_owner_bisa_mengubah_permission_admin(): void
    {
        $owner = User::query()->where('role', UserRole::Owner->value)->firstOrFail();
        $admin = User::query()->where('role', UserRole::Admin->value)->firstOrFail();

        $this->actingAs($owner)->put(route('owner.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'admin',
            'is_active' => 1,
            'permissions' => ['orders.view', 'orders.manage'],
        ])->assertSessionHasNoErrors();

        $this->assertFalse($admin->fresh()->hasPermission('products.manage'));
        $this->assertTrue($admin->fresh()->hasPermission('orders.view'));
    }
}
