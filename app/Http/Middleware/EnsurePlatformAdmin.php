<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsurePlatformAdmin
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        /*
         * La Superadministración solamente puede
         * ser utilizada por administradores de Venti360.
         */
        if (!$user->is_platform_admin) {
            abort(
                403,
                'No tienes autorización para acceder a la administración de plataforma.'
            );
        }

        /*
         * Un Platform Admin no debe pertenecer
         * operacionalmente a ningún tenant.
         *
         * No bloqueamos aquí por tenant_id para evitar
         * quedarnos sin acceso ante una inconsistencia
         * histórica. Esa integridad la validaremos
         * administrativamente.
         */

        return $next($request);
    }
}