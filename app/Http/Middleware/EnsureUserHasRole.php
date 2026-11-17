<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses route berdasarkan peran pengguna.
 *
 * PEMERIKSAAN DILAKUKAN DI SISI SERVER. Menyembunyikan tombol di frontend
 * BUKAN mekanisme keamanan yang cukup.
 *
 * Pemakaian pada route:
 *   ->middleware('role:admin')
 *   ->middleware('role:admin,owner')
 */
class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            // Belum login -> arahkan ke halaman login.
            return redirect()->guest(route('login'));
        }

        if (! $user->role instanceof UserRole) {
            abort(403, 'Peran pengguna tidak dikenali.');
        }

        if (! in_array($user->role->value, $roles, true)) {
            abort(403, sprintf(
                'Halaman ini hanya dapat diakses oleh: %s.',
                strtoupper(implode(', ', $roles))
            ));
        }

        return $next($request);
    }
}
