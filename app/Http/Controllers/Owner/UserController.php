<?php

namespace App\Http\Controllers\Owner;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->whereIn('role', [UserRole::Admin, UserRole::Customer])
            ->latest('id')
            ->paginate(15);

        return view('owner.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('owner.users.form', [
            'user' => new User,
            'action' => route('owner.users.store'),
            'method' => 'POST',
            'permissions' => DB::table('permissions')->orderBy('key')->get(),
            'selected' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'is_active' => true,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $this->log('user.created', $user, 'Akun '.$user->email.' dibuat Owner.');

        return redirect()->route('owner.users.index')->with('success', 'Akun berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        abort_if($user->role === UserRole::Owner, 403, 'Akun Owner tidak bisa diubah dari halaman ini.');

        return view('owner.users.form', [
            'user' => $user,
            'action' => route('owner.users.update', $user),
            'method' => 'PUT',
            'permissions' => DB::table('permissions')->orderBy('key')->get(),
            'selected' => DB::table('role_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('role_permissions.role', $user->role?->value)
                ->pluck('permissions.key'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role === UserRole::Owner, 403, 'Akun Owner tidak bisa diubah dari halaman ini.');

        $validated = $this->validateUser($request, $user->id);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        if ($validated['role'] === UserRole::Admin->value && array_key_exists('permissions', $validated)) {
            $ids = DB::table('permissions')->whereIn('key', $validated['permissions'] ?? [])->pluck('id');
            DB::table('role_permissions')->where('role', UserRole::Admin->value)->delete();
            foreach ($ids as $permissionId) {
                DB::table('role_permissions')->insert([
                    'role' => UserRole::Admin->value,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->log('user.updated', $user, 'Akun '.$user->email.' diperbarui Owner.');

        return redirect()->route('owner.users.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->role === UserRole::Owner, 403, 'Akun Owner tidak bisa dihapus.');
        abort_if($user->id === auth()->id(), 403, 'Tidak bisa menghapus akun sendiri.');

        $email = $user->email;
        $user->delete();
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.deleted',
            'description' => 'Akun '.$email.' dihapus Owner.',
        ]);

        return back()->with('success', 'Akun dihapus.');
    }

    public function toggle(User $user): RedirectResponse
    {
        abort_if($user->role === UserRole::Owner, 403, 'Akun Owner tidak bisa dinonaktifkan.');
        abort_if($user->id === auth()->id(), 403, 'Tidak bisa menonaktifkan akun sendiri.');

        $user->update(['is_active' => ! $user->is_active]);
        $this->log('user.toggled', $user, 'Status akun '.$user->email.' diubah menjadi '.($user->is_active ? 'aktif' : 'nonaktif').'.');

        return back()->with('success', 'Status akun diperbarui.');
    }

    /** @return array<string, mixed> */
    private function validateUser(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$id],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in([UserRole::Admin->value, UserRole::Customer->value])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,key'],
        ]);
    }

    private function log(string $action, User $user, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
