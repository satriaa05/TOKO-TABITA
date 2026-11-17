<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Pengaturan dasar aplikasi.
 *
 * - Konfigurasi toko (nama, alamat, telepon, catatan kaki struk).
 * - Manajemen pengguna: tambah akun owner/admin, aktif/nonaktif, reset password.
 */
class SettingController extends Controller
{
    /**
     * Halaman pengaturan.
     */
    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => Setting::query()->orderBy('key')->pluck('value', 'key'),
            'users' => User::query()->orderBy('role')->orderBy('name')->get(),
        ]);
    }

    /**
     * Simpan konfigurasi toko.
     */
    public function updateStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:150'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'store_phone' => ['nullable', 'string', 'max:30'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'max_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'setting.update',
            'model_type' => Setting::class,
            'model_id' => null,
            'description' => 'Konfigurasi toko diperbarui.',
            'new_values' => $data,
        ]);

        return back()->with('success', 'Pengaturan toko berhasil disimpan.');
    }

    /**
     * Tambah pengguna baru.
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:admin,owner'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.create',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => 'Pengguna "'.$user->name.'" ('.$user->role->label().') dibuat.',
        ]);

        return back()->with('success', 'Pengguna "'.$user->name.'" berhasil dibuat.');
    }

    /**
     * Aktifkan / nonaktifkan pengguna.
     */
    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        // Admin tidak boleh menonaktifkan akunnya sendiri.
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $newStatus = ! $user->is_active;

        $user->update(['is_active' => $newStatus]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.toggle_status',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => 'Pengguna "'.$user->name.'" '.($newStatus ? 'diaktifkan' : 'dinonaktifkan').'.',
        ]);

        return back()->with(
            'success',
            'Pengguna "'.$user->name.'" berhasil '.($newStatus ? 'diaktifkan' : 'dinonaktifkan').'.'
        );
    }

    /**
     * Reset kata sandi pengguna.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.reset_password',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => 'Kata sandi pengguna "'.$user->name.'" direset.',
        ]);

        return back()->with('success', 'Kata sandi "'.$user->name.'" berhasil direset.');
    }
}
