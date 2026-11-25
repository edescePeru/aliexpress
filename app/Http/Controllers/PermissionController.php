<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeletePermissionRequest;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(\App\Http\Middleware\OperationalModuleGate::class.':legacy-access');
    }

    public function index()
    {
        $permissions = Permission::all();
        $user = Auth::user();
        $permissionsAll = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('access.permissions', compact('permissions', 'permissionsAll'));
    }

    public function store(StorePermissionRequest $request)
    {
        $validated = $request->validated();

        $permission = Permission::create([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
        ]);

        return response()->json(['message' => 'Permiso guardado con éxito.'], 200);

    }

    public function update(UpdatePermissionRequest $request)
    {
        $validated = $request->validated();

        $permission = Permission::find($request->get('permission_id'));

        $permission->name = $request->get('name');
        $permission->description = $request->get('description');

        $permission->save();

        return response()->json(['message' => 'Permiso modificado con éxito.'], 200);

    }

    public function destroy(DeletePermissionRequest $request)
    {
        $validated = $request->validated();

        $permission = Permission::find($request->get('permission_id'));

        $permission->delete();

        return response()->json(['message' => 'Permiso eliminado con éxito.'], 200);

    }

    public function getPermissions()
    {
        $permissions = Permission::select('id', 'name', 'description')->get();
        return datatables($permissions)->toJson();
    }
}
