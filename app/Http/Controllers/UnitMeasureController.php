<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteUnitMeasureRequest;
use App\Http\Requests\StoreUnitMeasureRequest;
use App\Http\Requests\UpdateUnitMeasureRequest;
use App\UnitMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnitMeasureController extends Controller
{
    public function index()
    {
        /*
         * TenantScope se aplica automáticamente.
         */
        $unitMeasures = UnitMeasure::query()
            ->orderBy('name', 'asc')
            ->get();

        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        return view(
            'unitMeasure.index',
            compact(
                'unitMeasures',
                'permissions'
            )
        );
    }


    public function store(
        StoreUnitMeasureRequest $request
    ) {
        $validated =
            $request->validated();

        DB::beginTransaction();

        try {

            /*
             * No mandamos tenant_id.
             * BelongsToTenant lo coloca automáticamente.
             */
            $unitMeasure = UnitMeasure::create([
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
                    'No se pudo registrar la unidad de medida.',
            ], 422);
        }

        return response()->json([
            'success' =>
                true,

            'message' =>
                'Unidad de medida guardada con éxito.',

            'data' => [
                'id' =>
                    $unitMeasure->id,

                'description' =>
                    $unitMeasure->name,
            ],
        ], 200);
    }


    public function update(
        UpdateUnitMeasureRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         * TenantScope se aplica.
         *
         * Si mandan un ID de otro Tenant,
         * este recurso no existe para el usuario.
         */
        $unitMeasure =
            UnitMeasure::findOrFail(
                $validated[
                'unitMeasure_id'
                ]
            );

        DB::beginTransaction();

        try {

            $unitMeasure->name =
                $validated['name'];

            $unitMeasure->description =
                $validated['description']
                ?? null;

            $unitMeasure->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo modificar la unidad de medida.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Unidad de medida modificada con éxito.',

            'url' =>
                route(
                    'unitmeasure.index'
                ),
        ], 200);
    }


    public function destroy(
        DeleteUnitMeasureRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         * Fuera del try/catch para conservar
         * correctamente el 404 de TenantScope.
         */
        $unitMeasure =
            UnitMeasure::findOrFail(
                $validated[
                'unitMeasure_id'
                ]
            );

        DB::beginTransaction();

        try {

            $unitMeasure->delete();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo eliminar la unidad de medida.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Unidad de medida eliminada con éxito.',
        ], 200);
    }


    public function create()
    {
        return view(
            'unitMeasure.create'
        );
    }


    public function edit($id)
    {
        /*
         * Antes:
         * UnitMeasure::find($id)
         *
         * Ahora:
         * TenantScope + 404 cross-tenant.
         */
        $unitMeasure =
            UnitMeasure::findOrFail(
                $id
            );

        return view(
            'unitMeasure.edit',
            compact(
                'unitMeasure'
            )
        );
    }


    public function getUnitMeasure()
    {
        $unitMeasures =
            UnitMeasure::query()
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
            $unitMeasures
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
         * IDs de otros tenants simplemente
         * no son devueltos por TenantScope.
         */
        $unitMeasures =
            UnitMeasure::query()
                ->whereIn(
                    'id',
                    $ids
                )
                ->get();

        DB::beginTransaction();

        try {

            foreach (
                $unitMeasures
                as $unitMeasure
            ) {

                $unitMeasure->delete();
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudieron eliminar las unidades de medida.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Unidades eliminadas correctamente.',
        ]);
    }
}