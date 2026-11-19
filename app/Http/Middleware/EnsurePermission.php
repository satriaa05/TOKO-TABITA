<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        if ($user->isOwner()) {
            return $next($request);
        }

        // Default deny: bila permission belum ada di role_permissions, akses
        // ditolak. Owner tetap penuh; Admin hanya izin yang eksplisit.
        abort_unless(
            $user->hasPermission($permission),
            403,
            'Anda tidak memiliki izin untuk melakukan aksi ini.'
        );

        return $next($request);
    }
}
