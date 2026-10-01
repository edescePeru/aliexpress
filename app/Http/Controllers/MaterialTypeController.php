<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteMaterialTypeRequest;
use App\Http\Requests\StoreMaterialTypeRequest;
use App\Http\Requests\UpdateMaterialTypeRequest;
use App\Material;
use App\MaterialType;
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


    public function destroy(
        DeleteMaterialTypeRequest $request
    ) {
        $validated =
            $request->validated();

        $materialType =
            MaterialType::findOrFail(
                $validated[
                'materialtype_id'
                ]
            );

        /*
         * No permitir eliminar si algún Material
         * utiliza este MaterialType.
         */
        $hasMaterials =
            Material::query()
                ->where(
                    'material_type_id',
                    $materialType->id
                )
                ->exists();

        if ($hasMaterials) {

            return response()->json([
                'message' =>
                    'No se puede eliminar el tipo de material porque está siendo utilizado por uno o más materiales.',
            ], 422);
        }

        /*
         * Tampoco eliminar si alguno de sus Subtypes
         * está siendo utilizado por un Material.
         */
        $subtypeIds =
            $materialType
                ->subtypes()
                ->pluck('id');

        $hasSubtypeMaterials =
            Material::query()
                ->whereIn(
                    'subtype_id',
                    $subtypeIds
                )
                ->exists();

        if ($hasSubtypeMaterials) {

            return response()->json([
                'message' =>
                    'No se puede eliminar el tipo de material porque uno o más de sus subtipos están siendo utilizados por materiales.',
            ], 422);
        }

        DB::beginTransaction();

        try {

            $materialType->delete();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo eliminar el tipo de material.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Tipo de material eliminado con éxito.',
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

        $materialTypes =
            MaterialType::with(
                'subtypes'
            )
                ->whereIn(
                    'id',
                    $ids
                )
                ->get();

        /*
         * =========================================================
         * VALIDAR USO EN MATERIAL
         * =========================================================
         */

        $usedMaterialTypeIds =
            Material::query()
                ->whereIn(
                    'material_type_id',
                    $materialTypes->pluck('id')
                )
                ->pluck('material_type_id')
                ->filter()
                ->unique();

        if ($usedMaterialTypeIds->isNotEmpty()) {

            $usedNames =
                $materialTypes
                    ->whereIn(
                        'id',
                        $usedMaterialTypeIds
                    )
                    ->pluck('name')
                    ->implode(', ');

            return response()->json([
                'message' =>
                    'No se pueden eliminar los tipos de material porque están siendo utilizados por materiales: ' .
                    $usedNames .
                    '.',
            ], 422);
        }

        /*
         * =========================================================
         * VALIDAR USO DE SUBTYPES
         * =========================================================
         */

        $subtypeIds =
            $materialTypes
                ->flatMap(
                    function ($materialType) {

                        return $materialType
                            ->subtypes
                            ->pluck('id');
                    }
                )
                ->filter()
                ->unique();

        if ($subtypeIds->isNotEmpty()) {

            $usedSubtypeIds =
                Material::query()
                    ->whereIn(
                        'subtype_id',
                        $subtypeIds
                    )
                    ->pluck('subtype_id')
                    ->filter()
                    ->unique();

            if ($usedSubtypeIds->isNotEmpty()) {

                return response()->json([
                    'message' =>
                        'No se pueden eliminar los tipos de material porque uno o más de sus subtipos están siendo utilizados por materiales.',
                ], 422);
            }
        }

        DB::beginTransaction();

        try {

            foreach (
                $materialTypes
                as $materialType
            ) {

                foreach (
                    $materialType->subtypes
                    as $subtype
                ) {
                    $subtype->delete();
                }

                $materialType->delete();
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudieron eliminar los tipos de material.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Tipos de material eliminados correctamente.',
        ]);
    }
}