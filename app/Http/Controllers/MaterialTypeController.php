<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteMaterialTypeRequest;
use App\Http\Requests\DeleteMultipleMaterialTypesRequest;
use App\Http\Requests\StoreMaterialTypeRequest;
use App\Http\Requests\UpdateMaterialTypeRequest;
use App\Material;
use App\MaterialType;
use App\Services\Materials\DeleteMaterialTypes;
use App\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialTypeController extends Controller
{
    public function index()
    {
        $materialtypes =
            MaterialType::all();

        $user =
            Auth::user();

        $permissions =
            $user
                ->getPermissionsViaRoles()
                ->pluck('name')
                ->toArray();

        return view(
            'materialtype.index',
            compact(
                'materialtypes',
                'permissions'
            )
        );
    }


    public function store(
        StoreMaterialTypeRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         * Subcategory también debe pertenecer
         * al Tenant actual.
         */
        $subcategory =
            Subcategory::findOrFail(
                $validated[
                'subcategory_id'
                ]
            );

        DB::beginTransaction();

        try {

            $materialType =
                MaterialType::create([
                    'name' =>
                        $validated['name'],

                    'description' =>
                        $validated['description']
                        ?? null,

                    'subcategory_id' =>
                        $subcategory->id,
                ]);

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo registrar el tipo de material.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Tipo de material guardado con éxito.',

            'data' => [
                'id' => $materialType->id,
                'name' => $materialType->name,
                'description' => $materialType->description,
                'subcategory_id' => $materialType->subcategory_id,
            ],
        ], 200);
    }


    public function update(
        UpdateMaterialTypeRequest $request
    ) {
        $validated =
            $request->validated();

        $materialType =
            MaterialType::findOrFail(
                $validated[
                'materialtype_id'
                ]
            );

        $subcategory =
            Subcategory::findOrFail(
                $validated[
                'subcategory_id'
                ]
            );

        DB::beginTransaction();

        try {

            $materialType->name =
                $validated['name'];

            $materialType->description =
                $validated['description']
                ?? null;

            $materialType->subcategory_id =
                $subcategory->id;

            $materialType->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo modificar el tipo de material.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Tipo de material modificado con éxito.',

            'url' =>
                route(
                    'materialtype.index'
                ),
        ], 200);
    }


    public function destroy(DeleteMaterialTypeRequest $request)
    {
        $validated = $request->validated();

        try {
            app(DeleteMaterialTypes::class)->delete(
                [$validated['materialtype_id']],
                'materialtype_id'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudo eliminar el tipo de material.',
            ], 422);
        }

        return response()->json([
            'message' => 'Tipo de material eliminado con éxito.',
        ], 200);
    }


    public function create()
    {
        $subcategories =
            Subcategory::query()
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        return view(
            'materialtype.create',
            compact(
                'subcategories'
            )
        );
    }


    public function edit($id)
    {
        $subcategories =
            Subcategory::query()
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        $materialtype =
            MaterialType::findOrFail(
                $id
            );

        return view(
            'materialtype.edit',
            compact(
                'materialtype',
                'subcategories'
            )
        );
    }


    public function getMaterialTypes()
    {
        $materialtypes =
            MaterialType::with(
                'subcategory'
            )
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        return datatables(
            $materialtypes
        )->toJson();
    }


    public function getTypesBySubCategory(
        $id
    ) {
        /*
         * Primero validar Subcategory
         * dentro del Tenant actual.
         */
        $subcategory =
            Subcategory::findOrFail(
                $id
            );

        $types =
            MaterialType::query()
                ->where(
                    'subcategory_id',
                    $subcategory->id
                )
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        $array = [];

        foreach (
            $types as $type
        ) {

            $array[] = [
                'id' =>
                    $type->id,

                'type' =>
                    $type->name,
            ];
        }

        return $array;
    }


    public function deleteMultiple(DeleteMultipleMaterialTypesRequest $request)
    {
        $validated = $request->validated();

        try {
            $count = app(DeleteMaterialTypes::class)
                ->delete($validated['ids']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudieron eliminar los tipos de material.',
            ], 422);
        }

        return response()->json([
            'message' => 'Tipos de material eliminados correctamente.',
            'deleted_count' => $count,
        ], 200);
    }

}