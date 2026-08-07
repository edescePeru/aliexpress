<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsurePlatformAdmin
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !$user->isPlatformAdmin()) {
            abort(
                403,
                'No tiene permisos para acceder a la administración de Venti360.'
            );
        }

        return $next($request);
    }
}