<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemeriksaan permission berbasis database, sisi server.
 * Owner selalu diizinkan. Admin hanya jika permission diberikan.
 */
class EnsurePermission
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->is_active) {
            abort(403, 'Akun Anda tidak aktif.');
        }

        if ($user->role === UserRole::Owner) {
            return $next($request);
        }

        $query = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role', $user->role?->value);

        // Jika permission belum pernah dikonfigurasi untuk role ini,
        // jangan mengunci seluruh fitur (kompatibilitas data lama).
        if (! (clone $query)->exists()) {
            return $next($request);
        }

        $allowed = (clone $query)->where('permissions.key', $permission)->exists();

        abort_unless($allowed, 403, 'Anda tidak memiliki izin untuk melakukan aksi ini.');

        return $next($request);
    }
}
