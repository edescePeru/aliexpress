<?php

namespace App\Http\Controllers;

use App\Genero;
use App\Http\Requests\DeleteGeneroRequest;
use App\Http\Requests\StoreGeneroRequest;
use App\Http\Requests\UpdateGeneroRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GeneroController extends Controller
{
    public function index()
    {
        $user =
            Auth::user();

        $permissions =
            $user
                ->getPermissionsViaRoles()
                ->pluck('name')
                ->toArray();

        return view(
            'genero.index',
            compact(
                'permissions'
            )
        );
    }


    public function create()
    {
        return view(
            'genero.create'
        );
    }


    public function store(
        StoreGeneroRequest $request
    ) {
        $validated =
            $request->validated();

        DB::beginTransaction();

        try {

            /*
             * tenant_id lo asigna automáticamente
             * BelongsToTenant.
             */
            $genero =
                Genero::create([
                    'name' =>
                        $validated['name'],

                    'description' =>
                        $validated['description']
                        ?? null,
                ]);

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'success' =>
                    false,

                'message' =>
                    'No se pudo registrar el género.',
            ], 422);
        }

        return response()->json([
            'success' =>
                true,

            'message' =>
                'Género guardado con éxito.',

            'data' => [
                'id' =>
                    $genero->id,

                'description' =>
                    $genero->name,
            ],
        ], 200);
    }


    public function edit($id)
    {
        /*
         * TenantScope garantiza que un ID
         * de otro Tenant devuelva 404.
         */
        $genero =
            Genero::findOrFail(
                $id
            );

        return view(
            'genero.edit',
            compact(
                'genero'
            )
        );
    }


    public function update(
        UpdateGeneroRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         * Fuera del try/catch para preservar 404.
         */
        $genero =
            Genero::findOrFail(
                $validated['genero_id']
            );

        DB::beginTransaction();

        try {

            $genero->name =
                $validated['name'];

            $genero->description =
                $validated['description']
                ?? null;

            $genero->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo modificar el género.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Género modificado con éxito.',

            'url' =>
                route(
                    'genero.index'
                ),
        ], 200);
    }


    public function destroy(
        DeleteGeneroRequest $request
    ) {
        $validated =
            $request->validated();

        $genero =
            Genero::findOrFail(
                $validated['genero_id']
            );

        DB::beginTransaction();

        try {

            $genero->delete();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo eliminar el género.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Género eliminado con éxito.',
        ], 200);
    }


    public function getGeneros()
    {
        /*
         * TenantScope filtra automáticamente.
         */
        $generos =
            Genero::query()
                ->select(
                    'id',
                    'name',
                    'description'
                )
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        return datatables(
            $generos
        )->toJson();
    }


    public function deleteMultiple(
        Request $request
    ) {
        $ids =
            $request->input(
                'ids'
            );

        if (
            !$ids ||
            !is_array($ids)
        ) {
            return response()->json([
                'message' =>
                    'Datos inválidos.',
            ], 400);
        }

        /*
         * IDs de otros tenants son filtrados
         * automáticamente por TenantScope.
         */
        $generos =
            Genero::query()
                ->whereIn(
                    'id',
                    $ids
                )
                ->get();

        DB::beginTransaction();

        try {

            foreach (
                $generos as $genero
            ) {
                $genero->delete();
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudieron eliminar los géneros.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Géneros eliminados correctamente.',
        ]);
    }
}