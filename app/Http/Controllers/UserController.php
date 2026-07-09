<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\RestoreSupplierRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserSettingsRequest;
use App\User;
use App\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        $roles = Role::all();

        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('access.users', compact('users', 'roles', 'permissions'));
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => bcrypt('venti3602025'),
        ]);

        $worker = Worker::create([
            'first_name' => $user->name,
            'email' => $user->email,
            'image' => $user->image
        ]);

        // Sincronizar con roles
        $roles = $request->get('roles');
        //var_dump($roles);
        $user->syncRoles($roles);

        // TODO: Tratamiento de un archivo de forma tradicional
        if (!$request->file('image')) {
            $user->image = 'no_image.png';
            $user->save();
        } else {
            $path = public_path().'/images/users/';
            $extension = $request->file('image')->getClientOriginalExtension();
            $filename = $user->id . '.' . $extension;
            $request->file('image')->move($path, $filename);
            $user->image = $filename;
            $user->save();
        }

        return response()->json(['message' => 'Usuario guardado con éxito.'], 200);

    }

    public function update(UpdateUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::find($request->get('user_id'));

        $user->name = $request->get('name');
        $user->email = $request->get('email');
        $user->save();

        // Sincronizar con roles
        $roles = $request->get('roles');
        $user->syncRoles($roles);

        // TODO: Tratamiento de un archivo de forma tradicional
        if (!$request->file('image')) {
            if ($user->image == 'no_image.png' || $user->image == null) {
                $user->image = 'no_image.png';
                $user->save();
            }

        } else {
            $path = public_path().'/images/user/';
            $extension = $request->file('image')->getClientOriginalExtension();
            $filename = $user->id . '.' . $extension;
            $request->file('image')->move($path, $filename);
            $user->image = $filename;
            $user->save();
        }

        return response()->json(['message' => 'Usuario modificado con éxito.'], 200);

    }

    public function destroy(DeleteUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::find($request->get('user_id'));

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado con éxito.'], 200);

    }

    public function getRoles( $id )
    {
        $user = User::find($id);
        //var_dump($role);
        // No usar permissions() sino solo permissions
        $rolesAll = Role::all();
        $rolesSelected = [];
        $roles = $user->roles;
        foreach ( $roles as $role )
        {
            //var_dump($permission->name);
            array_push($rolesSelected, $role->name);
        }
        //var_dump($permissions);
        return array(
            'rolesAll' => $rolesAll,
            'rolesSelected' => $rolesSelected
        );
    }

    public function getUsers()
    {
        $users = User::select('id', 'name', 'email', 'image', 'enable')
            ->where('enable', true)->get();
        return datatables($users)->toJson();
    }

    public function getUsers2()
    {
        $users = User::select('id', 'name')
            ->where('id', '!=' , Auth::user()->id)
            ->where('enable', true)
            ->get();
        return json_encode($users);
    }

    public function profile()
    {
        $user = User::with('roles')->find(Auth::user()->id);
        return view('user.profile', compact('user'));
    }

    public function changeImage(Request $request, User $user)
    {
        //dump($user);
        //dd($request);
        if ($request->file('image')) {
            $path = public_path() . '/images/users/';
            $extension = $request->file('image')->getClientOriginalExtension();
            $filename = $user->id . '.' . $extension;
            $request->file('image')->move($path, $filename);
            $user->image = $filename;
            $user->save();

            return response()->json(['message' => 'Imagen cambiada con éxito.'], 200);

        }

        return response()->json(['message' => 'No se pudo guardar la imagen.'], 422);

    }

    public function changeSettings(UpdateUserSettingsRequest $request, User $user)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $user->name = $request->get('name');
            //$user->email = $request->get('email');
            $user->save();

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Datos actualizados con éxito.'], 200);

    }

    public function changePassword(UpdateUserPasswordRequest $request, User $user)
    {
        //dd();
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $passwordCurrent = $request->get('current_password');
            if ( ! Hash::check($request->get('current_password'), $user->password) )
            {
                return response()->json(['message' => 'Debe conocer su contraseña actual.'], 422);
            }
            $passwordNew = $request->get('new_password');
            $user->password = bcrypt($passwordNew) ;
            $user->save();

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Contraseña actualizada con éxito.'], 200);

    }

    public function disable(DeleteUserRequest $request)
    {
        //dd($request);
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $user = User::find($request->get('user_id'));

            $worker = Worker::where('user_id', $user->id)->first();

            if ( !is_null($worker) )
            {
                $worker->enable = false;
                $worker->save();
            }

            $user->enable = false;
            $user->save();
            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Usuario inhabilitado con éxito.'], 200);

    }

    public function enable(DeleteUserRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $user = User::find($request->get('user_id'));

            $worker = Worker::where('user_id', $user->id)->first();

            if ( !is_null($worker) )
            {
                $worker->enable = true;
                $worker->save();
            }

            $user->enable = true;
            $user->save();
            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Usuario habilitado con éxito.'], 200);

    }

    public function indexEnable()
    {
        $users = User::all();
        $roles = Role::all();

        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('user.indexEnable', compact('users', 'roles', 'permissions'));
    }

    public function getUsersDelete()
    {
        $users = User::select('id', 'name', 'email', 'image', 'enable')
            ->where('enable', false)->get();
        return datatables($users)->toJson();
    }

    public function convertUsersToWorkers()
    {
        DB::beginTransaction();
        try {

            $user_actives = User::where('enable', true)
                ->get();

            if ( count($user_actives) > 0 )
            {
                foreach ( $user_actives as $user_active )
                {
                    $worker = Worker::where('user_id', $user_active->id)->first();
                    if ( !isset($worker) )
                    {
                        $worker = Worker::create([
                            'first_name' => $user_active->name,
                            'email' => $user_active->email,
                            'image' => $user_active->image,
                            'user_id' => $user_active->id
                        ]);
                    }
                }
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Trabajadores creados.'], 200);

    }
}
