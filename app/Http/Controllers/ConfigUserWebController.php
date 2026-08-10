<?php

namespace App\Http\Controllers;

use App\Services\TenantPlanService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ConfigUserWebController extends Controller
{
    private function tenantUsersQuery()
    {
        $authUser = Auth::user();

        if (
            !$authUser ||
            !$authUser->tenant_id ||
            $authUser->is_platform_admin
        ) {
            abort(403, 'No existe un contexto de tenant válido.');
        }

        return User::query()
            ->where(
                'tenant_id',
                $authUser->tenant_id
            )
            ->where(
                'is_platform_admin',
                false
            );
    }

    private function findTenantUserOrFail($id)
    {
        return $this->tenantUsersQuery()
            ->where('id', $id)
            ->firstOrFail();
    }

    private function ensureTenantOwner()
    {
        $user = Auth::user();

        if (
            !$user ||
            !$user->is_tenant_owner ||
            $user->is_platform_admin
        ) {
            abort(
                403,
                'Solo el propietario del tenant puede administrar usuarios.'
            );
        }
    }

    public function listar()
    {
        $this->ensureTenantOwner();

        return view('configUserWeb.index');
    }

    public function getUsers(Request $request)
    {
        $this->ensureTenantOwner();

        $search = trim(
            (string) $request->get('search')
        );

        $status = $request->get(
            'status',
            'active'
        );

        $perPage = (int) $request->get(
            'per_page',
            10
        );

        if (!in_array(
            $perPage,
            [10, 25, 50]
        )) {
            $perPage = 10;
        }

        $query = $this->tenantUsersQuery()
            ->with('roles')
            ->select(
                'id',
                'tenant_id',
                'name',
                'email',
                'image',
                'enable',
                'is_tenant_owner',
                'updated_at'
            );

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where(
                    'name',
                    'LIKE',
                    '%' . $search . '%'
                )
                    ->orWhere(
                        'email',
                        'LIKE',
                        '%' . $search . '%'
                    );
            });
        }

        if ($status === 'active') {
            $query->where('enable', true);
        } elseif ($status === 'inactive') {
            $query->where('enable', false);
        }

        $users = $query
            ->orderByDesc('is_tenant_owner')
            ->orderBy('name')
            ->paginate($perPage);

        $users->getCollection()->transform(
            function ($user) {

                $role = $user->roles->first();

                return [
                    'id' =>
                        $user->id,

                    'name' =>
                        $user->name,

                    'email' =>
                        $user->email,

                    'image' =>
                        $this->getUserImage(
                            $user
                        ),

                    'enable' =>
                        (bool) $user->enable,

                    'is_tenant_owner' =>
                        (bool) $user->is_tenant_owner,

                    'updated_at' =>
                        optional(
                            $user->updated_at
                        )->format(
                            'd/m/Y H:i'
                        ),

                    'role' =>
                        $role
                            ? (
                        $role->description
                            ?: $role->name
                        )
                            : null,

                    'role_id' =>
                        $role
                            ? $role->id
                            : null,

                    'is_current_user' =>
                        $user->id === Auth::id(),
                ];
            }
        );

        return response()->json(
            $users
        );
    }

    private function getUserImage($user)
    {
        if ($user->image) {
            /*
            Ajusta esta ruta según dónde guardes tus imágenes.
            Ejemplos:
            return asset('storage/' . $user->image);
            */

            return asset('images/users/' . $user->image);
        }

        return asset('images/default-user.png');
    }

    public function edit($id)
    {
        $this->ensureTenantOwner();

        $user = $this
            ->findTenantUserOrFail($id);

        $user->load('roles');

        $role = $user->roles->first();

        return response()->json([
            'id' =>
                $user->id,

            'name' =>
                $user->name,

            'email' =>
                $user->email,

            'image' =>
                $this->getUserImage(
                    $user
                ),

            'role' =>
                $role
                    ? (
                $role->description
                    ?: $role->name
                )
                    : null,

            'is_tenant_owner' =>
                (bool) $user->is_tenant_owner,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->ensureTenantOwner();

        $user = $this->findTenantUserOrFail($id);

        $user = User::with('roles')->findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'La imagen debe ser JPG, JPEG, PNG o WEBP.',
            'image.max' => 'La imagen no debe superar los 2MB.',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if (!$request->file('image')) {

            if ($user->image == null || $user->image == '') {
                $user->image = 'no_image.png';
            }

        } else {

            $path = public_path('images/users/');

            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            $extension = $request->file('image')->getClientOriginalExtension();
            $filename = $user->id . '.' . $extension;

            /*
             * Opcional: eliminar imagen anterior si no es la imagen por defecto.
             * Esto ayuda si antes tenía user 5.jpg y ahora sube 5.png.
             */
            if ($user->image && $user->image != 'no_image.png') {
                $oldImagePath = $path . $user->image;

                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $request->file('image')->move($path, $filename);

            $user->image = $filename;
        }

        $user->save();

        return response()->json([
            'message' => 'Usuario actualizado correctamente.'
        ]);
    }

    public function resetPassword($id)
    {
        $this->ensureTenantOwner();

        $user = $this->findTenantUserOrFail($id);

        /*
         * Generamos una contraseña temporal.
         *
         * Evitamos caracteres ambiguos como:
         * 0 O l I
         */
        $temporaryPassword =$this->generateTemporaryPassword();

        $user->password =
            Hash::make(
                $temporaryPassword
            );

        $user->must_change_password =
            true;

        $user->remember_token =
            null;

        $user->save();

        return response()->json([
            'message' =>
                'La contraseña fue reseteada correctamente.',

            /*
             * Se devuelve únicamente en esta respuesta
             * para que el Owner pueda entregársela
             * al usuario.
             */
            'temporary_password' =>
                $temporaryPassword,
        ]);
    }

    private function generateTemporaryPassword()
    {
        /*
         * 10 caracteres:
         *
         * mayúscula
         * minúscula
         * número
         * símbolo
         * + 6 aleatorios
         */

        $upper =
            'ABCDEFGHJKLMNPQRSTUVWXYZ';

        $lower =
            'abcdefghijkmnopqrstuvwxyz';

        $numbers =
            '23456789';

        $symbols =
            '!@#$%';

        $all =
            $upper .
            $lower .
            $numbers .
            $symbols;

        $password =
            $upper[
            random_int(
                0,
                strlen($upper) - 1
            )
            ];

        $password .=
            $lower[
            random_int(
                0,
                strlen($lower) - 1
            )
            ];

        $password .=
            $numbers[
            random_int(
                0,
                strlen($numbers) - 1
            )
            ];

        $password .=
            $symbols[
            random_int(
                0,
                strlen($symbols) - 1
            )
            ];

        for ($i = 0; $i < 6; $i++) {

            $password .=
                $all[
                random_int(
                    0,
                    strlen($all) - 1
                )
                ];
        }

        /*
         * Mezclamos usando random_int en lugar
         * de depender de str_shuffle.
         */
        $characters =
            str_split(
                $password
            );

        for (
            $i = count($characters) - 1;
            $i > 0;
            $i--
        ) {
            $j =
                random_int(
                    0,
                    $i
                );

            $tmp =
                $characters[$i];

            $characters[$i] =
                $characters[$j];

            $characters[$j] =
                $tmp;
        }

        return implode(
            '',
            $characters
        );
    }

    public function changeStatus( Request $request, $id, TenantPlanService $planService ) {
        $this->ensureTenantOwner();

        $user = $this
            ->findTenantUserOrFail($id);

        $authUser = Auth::user();

        if ($user->id === $authUser->id) {
            return response()->json([
                'message' =>
                    'No puedes cambiar el estado de tu propia cuenta.'
            ], 422);
        }

        if ($user->is_tenant_owner) {
            return response()->json([
                'message' =>
                    'El propietario del tenant no puede ser inhabilitado desde este módulo.'
            ], 422);
        }

        $request->validate([
            'status' => [
                'required',
                'in:0,1'
            ],
        ]);

        $newStatus =
            (int) $request->status;

        /*
         * Si ya está en el mismo estado,
         * devolvemos sin hacer nada.
         */
        if (
            (int) $user->enable
            ===
            $newStatus
        ) {
            return response()->json([
                'message' =>
                    'El usuario ya se encuentra en ese estado.',
                'enable' =>
                    (bool) $user->enable,
            ]);
        }

        /*
         * Para HABILITAR hay que validar
         * capacidad del plan.
         */
        if ($newStatus === 1) {

            $tenant =
                $authUser->tenant;

            try {

                $planService
                    ->ensureCanActivateUser(
                        $tenant
                    );

            } catch (\RuntimeException $e) {

                return response()->json([
                    'message' =>
                        $e->getMessage(),
                ], 422);
            }
        }

        $user->enable =
            $newStatus === 1;

        /*
         * Al inhabilitar invalidamos
         * remember_token.
         */
        if (!$user->enable) {
            $user->remember_token = null;
        }

        $user->save();

        return response()->json([
            'message' =>
                $user->enable
                    ? 'Usuario activado correctamente.'
                    : 'Usuario inhabilitado correctamente.',

            'enable' =>
                (bool) $user->enable,
        ]);
    }
}
