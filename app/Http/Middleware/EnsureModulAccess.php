<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses ke sebuah modul aplikasi (lihat config/modul.php).
 *
 * Dipakai sebagai `modul:pm` pada grup rute.
 */
class EnsureModulAccess
{
    public function handle(Request $request, Closure $next, string $modul): Response
    {
        $konfig = config("modul.daftar.{$modul}");

        if (! $konfig) {
            abort(404);
        }

        $user = $request->user();
        $permission = $konfig['permission'] ?? null;

        if ($permission && (! $user || ! $user->can($permission))) {
            abort(403, "Anda tidak memiliki akses ke modul {$konfig['nama']}.");
        }

        // Dipakai layout untuk menandai modul yang sedang aktif.
        $request->attributes->set('modul', $modul);

        return $next($request);
    }
}
