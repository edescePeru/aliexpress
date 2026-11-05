<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteMultipleSubtypesRequest;
use App\Http\Requests\DeleteSubtypeRequest;
use App\Http\Requests\StoreSubtypeRequest;
use App\Http\Requests\UpdateSubtypeRequest;
use App\Material;
use App\MaterialType;
use App\Services\Materials\DeleteSubtypes;
use App\Subtype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubtypeController extends Controller
{
    public function index()
    {
        $subtypes =
            Subtype::all();

        $user =
            Auth::user();

        $permissions =
            $user
                ->getPermissionsViaRoles()
                ->pluck('name')
                ->toArray();

        return view(
            'subtype.index',
            compact(
                'subtypes',
                'permissions'
            )
        );
    }


    public function store(
        StoreSubtypeRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         * Segunda barrera.
         */
        $materialType =
            MaterialType::findOrFail(
                $validated[
                'material_type_id'
                ]
            );

        DB::beginTransaction();

        try {

            $subtype =
                Subtype::create([
                    'name' =>
                        $validated['name'],

                    'description' =>
                        $validated['description']
                        ?? null,

                    'material_type_id' =>
                        $materialType->id,
                ]);

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo registrar el subtipo.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'SubTipo guardado con éxito.',

            'data' => [
                'id' => $subtype->id,
                'name' => $subtype->name,
                'description' => $subtype->description,
                'material_type_id' => $subtype->material_type_id,
            ],
        ], 200);
    }


    public function update(
        UpdateSubtypeRequest $request
    ) {
        $validated =
            $request->validated();

        $subtype =
            Subtype::findOrFail(
                $validated[
                'subtype_id'
                ]
            );

        $materialType =
            MaterialType::findOrFail(
                $validated[
                'material_type_id'
                ]
            );

        DB::beginTransaction();

        try {

            $subtype->name =
                $validated['name'];

            $subtype->description =
                $validated['description']
                ?? null;

            $subtype->material_type_id =
                $materialType->id;

            $subtype->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo modificar el subtipo.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Sub Tipo modificado con éxito.',

            'url' =>
                route(
                    'subtype.index'
                ),
        ], 200);
    }


    public function destroy(DeleteSubtypeRequest $request)
    {
        $validated = $request->validated();

        try {
            app(DeleteSubtypes::class)->delete(
                [$validated['subtype_id']],
                'subtype_id'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudo eliminar el subtipo.',
            ], 422);
        }

        return response()->json([
            'message' => 'Subtipo eliminado con éxito.',
        ], 200);
    }


    public function create()
    {
        $materialTypes =
            MaterialType::query()
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        return view(
            'subtype.create',
            compact(
                'materialTypes'
            )
        );
    }


    public function edit($id)
    {
        $materialTypes =
            MaterialType::query()
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        $subtype =
            Subtype::findOrFail(
                $id
            );

        return view(
            'subtype.edit',
            compact(
                'subtype',
                'materialTypes'
            )
        );
    }


    public function getSubTypes()
    {
        /*
         * TenantScope de Subtype se aplica
         * porque la query nace desde Eloquent.
         *
         * El Scope de MaterialType NO se aplica
         * automáticamente dentro del join,
         * por eso verificamos ambos tenant_id.
         */
        $subtypes =
            Subtype::query()
                ->select(
                    'subtypes.*'
                )
                ->join(
                    'material_types',
                    'subtypes.material_type_id',
                    '=',
                    'material_types.id'
                )
                ->whereColumn(
                    'subtypes.tenant_id',
                    'material_types.tenant_id'
                )
                ->orderBy(
                    'material_types.name',
                    'asc'
                )
                ->orderBy(
                    'subtypes.name',
                    'asc'
                )
                ->with(
                    'materialType'
                )
                ->get();

        return datatables(
            $subtypes
        )->toJson();
    }


    public function getSubTypesByType(
        $id
    ) {
        /*
         * Primero validar el MaterialType padre.
         */
        $materialType =
            MaterialType::findOrFail(
                $id
            );

        $subtypes =
            Subtype::query()
                ->where(
                    'material_type_id',
                    $materialType->id
                )
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        $array = [];

        foreach (
            $subtypes as $subtype
        ) {

            $array[] = [
                'id' =>
                    $subtype->id,

                'subtype' =>
                    $subtype->name,
            ];
        }

        return $array;
    }


    public function deleteMultiple(DeleteMultipleSubtypesRequest $request)
    {
        $validated = $request->validated();

        try {
            $count = app(DeleteSubtypes::class)
                ->delete($validated['ids']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudieron eliminar los subtipos.',
            ], 422);
        }

        return response()->json([
            'message' => 'Subtipos eliminados correctamente.',
            'deleted_count' => $count,
        ], 200);
    }

}