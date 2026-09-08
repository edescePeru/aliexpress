<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureTenantOwner
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        /*
         * Debe existir una sesión autenticada.
         */
        if (!$user) {
            abort(401);
        }

        /*
         * Platform Admin tiene su propio módulo de administración
         * global de Tenants.
         *
         * No debe utilizar el autoservicio del Tenant.
         */
        if ($user->isPlatformAdmin()) {
            abort(403, 'Esta función corresponde a la administración del Tenant.');
        }

        /*
         * Un usuario normal debe pertenecer a un Tenant.
         */
        if (!$user->tenant_id) {
            abort(403, 'El usuario no pertenece a un grupo empresarial.');
        }

        /*
         * Solo el Tenant Owner puede administrar
         * Companies y Branches desde este módulo.
         */
        if (!$user->isTenantOwner()) {
            abort(403, 'Solo el propietario del grupo empresarial puede realizar esta acción.');
        }

        return $next($request);
    }
}