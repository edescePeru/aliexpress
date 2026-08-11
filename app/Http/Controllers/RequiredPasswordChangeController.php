<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RequiredPasswordChangeController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()
                ->route('login');
        }

        /*
         * Si ya cambió su contraseña,
         * no tiene sentido volver a esta pantalla.
         */
        if (!$user->must_change_password) {
            return redirect()
                ->route(
                    'dashboard.principal'
                );
        }

        return view(
            'auth.required-password-change'
        );
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' =>
                    'La sesión ha expirado.'
            ], 401);
        }

        if (!$user->must_change_password) {
            return response()->json([
                'message' =>
                    'La contraseña ya fue actualizada.'
            ], 422);
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'password.required' =>
                'Ingrese una nueva contraseña.',

            'password.min' =>
                'La contraseña debe tener al menos 8 caracteres.',

            'password.confirmed' =>
                'Las contraseñas no coinciden.',
        ]);

        /*
         * Evitamos que vuelva a utilizar
         * exactamente la contraseña temporal actual.
         */
        if (
        Hash::check(
            $validated['password'],
            $user->password
        )
        ) {
            return response()->json([
                'message' =>
                    'La nueva contraseña debe ser diferente a la contraseña temporal.'
            ], 422);
        }

        $user->password =
            Hash::make(
                $validated['password']
            );

        $user->must_change_password =
            false;

        /*
         * Invalidamos remember-me anterior.
         */
        $user->remember_token =
            null;

        $user->save();

        /*
         * Renovamos sesión después de
         * un cambio sensible de credenciales.
         */
        $request
            ->session()
            ->regenerate();

        return response()->json([
            'message' =>
                'Contraseña actualizada correctamente.',

            'redirect' =>
                route(
                    'dashboard.principal'
                ),
        ]);
    }
}