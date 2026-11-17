<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Registrasi pembeli online.
 *
 * Akun baru otomatis berperan "customer" (Pelanggan) — tidak bisa
 * masuk ke dashboard admin/owner. Setelah register, pengguna langsung
 * login dan diarahkan ke halaman toko.
 */
class RegisteredUserController extends Controller
{
    /**
     * Halaman registrasi.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->role->dashboardRoute());
        }

        return view('auth.register');
    }

    /**
     * Proses registrasi.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'phone.regex' => 'Nomor WhatsApp harus berisi angka (opsional +, -, spasi).',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => \App\Enums\UserRole::Customer,
            'is_active' => true,
        ]);

        Auth::login($user);

        return redirect()->intended(route('store.catalog'))
            ->with('success', 'Selamat datang, '.$user->name.'! Akun Anda berhasil dibuat.');
    }
}
