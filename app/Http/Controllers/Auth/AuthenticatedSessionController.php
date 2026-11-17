<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Autentikasi pengguna: login dan logout.
 *
 * Satu mekanisme autentikasi untuk dua role (admin, owner).
 * Perbedaan akses ditentukan oleh middleware `role`, bukan sistem login terpisah.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Tampilkan halaman login.
     */
    public function create(): View
    {
        // Sudah login? langsung ke dashboard sesuai role.
        if (Auth::check()) {
            return redirect()->route(Auth::user()->role->dashboardRoute());
        }

        return view('auth.login');
    }

    /**
     * Proses login.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        DB::transaction(function () use ($user, $request) {
            $user->forceFill(['last_login_at' => now()])->save();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.login',
                'model_type' => User::class,
                'model_id' => $user->id,
                'description' => $user->name.' berhasil login sebagai '.$user->role->label(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        });

        $request->session()->flash('success', 'Selamat datang, '.$user->name.'!');

        return redirect()->intended(route($user->role->dashboardRoute()));
    }

    /**
     * Proses logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.logout',
                'model_type' => User::class,
                'model_id' => $user->id,
                'description' => $user->name.' logout',
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }
}
