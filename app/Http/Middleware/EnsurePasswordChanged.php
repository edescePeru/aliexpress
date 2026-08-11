<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsurePasswordChanged
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        /*
         * Si debe cambiar contraseña,
         * bloqueamos el resto del sistema.
         */
        if ($user->must_change_password) {
            return redirect()
                ->route(
                    'password.required.edit'
                );
        }

        return $next($request);
    }
}
