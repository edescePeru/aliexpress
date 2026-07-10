<?php

namespace App\Http\Controllers;

use App\DataGeneral;
use App\User;
use App\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ConfigUserWebController extends Controller
{
    public function listar()
    {
        return view('configUserWeb.index');
    }

    public function getUsers(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'active');
        $perPage = $request->get('per_page', 10);

        $query = User::query()
            ->with('roles')
            ->select('id', 'name', 'email', 'image', 'enable', 'updated_at');

        /*
        |--------------------------------------------------------------------------
        | Si el usuario logueado NO es admin, no puede ver usuarios admin
        |--------------------------------------------------------------------------
        */
        if (!Auth::user()->hasRole('admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filtro por búsqueda
        |--------------------------------------------------------------------------
        */
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('email', 'LIKE', '%' . $search . '%');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filtro por estado
        |--------------------------------------------------------------------------
        */
        if ($status === 'active') {
            $query->where('enable', 1);
        }

        if ($status === 'inactive') {
            $query->where('enable', 0);
        }

        $users = $query
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $this->getUserImage($user),
                'enable' => $user->enable,
                'updated_at' => optional($user->updated_at)->format('d/m/Y H:i'),
                'roles' => $user->roles->pluck('name')->implode(', '),
            ];
        });

        return response()->json($users);
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
        $user = User::with('roles')->findOrFail($id);

        if (!Auth::user()->hasRole('admin') && $user->hasRole('admin')) {
            return response()->json([
                'message' => 'No tienes permisos para editar este usuario.'
            ], 403);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'image' => $this->getUserImage($user),
            'roles' => $user->roles->pluck('name')->implode(', '),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::with('roles')->findOrFail($id);

        if (!Auth::user()->hasRole('admin') && $user->hasRole('admin')) {
            return response()->json([
                'message' => 'No tienes permisos para modificar este usuario.'
            ], 403);
        }

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
        $user = User::with('roles')->findOrFail($id);

        if (!Auth::user()->hasRole('admin') && $user->hasRole('admin')) {
            return response()->json([
                'message' => 'No tienes permisos para resetear la contraseña de este usuario.'
            ], 403);
        }

        $dataGeneralContraseaReset = DataGeneral::where('name', 'password_reset')->first();

        if (!$dataGeneralContraseaReset || !$dataGeneralContraseaReset->valueText) {
            return response()->json([
                'message' => 'No se encontró una contraseña de reseteo configurada.'
            ], 422);
        }

        $passwordReset = $dataGeneralContraseaReset->valueText;

        $user->password = Hash::make($passwordReset);
        $user->save();

        return response()->json([
            'message' => 'La contraseña fue reseteada correctamente.'
        ]);
    }

    public function changeStatus(Request $request, $id)
    {
        $user = User::with('roles')->findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'No puedes cambiar el estado de tu propio usuario.'
            ], 422);
        }

        if (!Auth::user()->hasRole('admin') && $user->hasRole('admin')) {
            return response()->json([
                'message' => 'No tienes permisos para cambiar el estado de este usuario.'
            ], 403);
        }

        $request->validate([
            'status' => ['required', 'in:0,1'],
        ]);

        $user->enable = (int) $request->status;

        if ($user->enable === 0) {
            $user->remember_token = null;

            $worker = Worker::where('user_id', $user->id)->first();

            if ( !is_null($worker) )
            {
                $worker->enable = false;
                $worker->save();
            }
        }

        $user->save();

        return response()->json([
            'message' => $user->enable == 1
                ? 'Usuario activado correctamente.'
                : 'Usuario inhabilitado correctamente.',
            'enable' => $user->enable
        ]);
    }
}
