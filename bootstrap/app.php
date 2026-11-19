<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Perintah artisan di app/Console/Commands (mis. media:prune).
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware) {
        // Aplikasi dapat diakses lewat reverse proxy (mis. saat deploy).
        // Percayai header X-Forwarded-* agar URL aset memakai HTTPS.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            // Pembatasan akses berbasis peran: ->middleware('role:admin')
            'role' => EnsureUserHasRole::class,

            // Memastikan akun yang login masih aktif.
            'active' => EnsureAccountIsActive::class,

            // Permission berbasis database (Owner selalu diizinkan).
            'permission' => EnsurePermission::class,
        ]);

        // Webhook dari provider tidak punya session/CSRF token.
        $middleware->validateCsrfTokens(except: [
            'payments/midtrans/webhook',
            'shipping/biteship/webhook',
        ]);

        // Pengguna yang belum login diarahkan ke halaman login dengan flash message.
        $middleware->redirectGuestsTo(function (Request $request) {
            session()->flash('auth_error', 'Silakan login terlebih dahulu untuk melanjutkan pembelian di Tabita Modiste.');

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Halaman "akses ditolak" yang informatif.
        //
        // CATATAN PENTING (Laravel 11+):
        //  - Signature closure WAJIB (Throwable $e, Request $request).
        //    Parameter exception harus YANG PERTAMA, karena Laravel hanya
        //    memeriksa tipe parameter pertama untuk mencocokkan exception.
        //  - Helper abort(403, ...) melempar HttpException generik
        //    (bukan AccessDeniedHttpException), jadi status code-nya
        //    ikut diperiksa.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 403) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }

            return response()->view('errors.403', [
                'message' => $e->getMessage() ?: 'Anda tidak memiliki hak akses ke halaman ini.',
            ], 403);
        });
    })->create();
