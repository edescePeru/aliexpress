<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Exampler;
use App\Http\Requests\DeleteExamplerRequest;
use App\Http\Requests\StoreExamplerRequest;
use App\Http\Requests\UpdateExamplerRequest;
use App\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamplerController extends Controller
{
    public function index()
    {
        /*
         * TenantScope se aplica automáticamente.
         */
        $examplers = Exampler::with(
            'brand'
        )->get();

        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        return view(
            'exampler.index',
            compact(
                'examplers',
                'permissions'
            )
        );
    }


    public function store(
        StoreExamplerRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         * Segunda barrera.
         *
         * Brand debe pertenecer al Tenant actual.
         */
        $brand = Brand::findOrFail(
            $validated['brand_id']
        );

        DB::beginTransaction();

        try {

            $exampler = Exampler::create([
                'name' =>
                    $validated['name'],

                'comment' =>
                    $validated['comment']
                    ?? null,

                'brand_id' =>
                    $brand->id,
            ]);

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo registrar el modelo.',
            ], 422);
        }

        return response()->json([
            'id' =>
                $exampler->id,

            'exampler' =>
                $exampler->name,

            'message' =>
                'Modelo guardado con éxito.',
        ], 200);
    }


    public function update(
        UpdateExamplerRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         * Exampler protegido por TenantScope.
         */
        $exampler =
            Exampler::findOrFail(
                $validated['exampler_id']
            );

        /*
         * Nueva Brand también debe
         * pertenecer al mismo Tenant.
         */
        $brand =
            Brand::findOrFail(
                $validated['brand_id']
            );

        DB::beginTransaction();

        try {

            $exampler->name =
                $validated['name'];

            $exampler->comment =
                $validated['comment']
                ?? null;

            $exampler->brand_id =
                $brand->id;

            $exampler->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo modificar el modelo.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Modelo modificado con éxito.',

            'url' =>
                route('exampler.index'),
        ], 200);
    }


    public function destroy(
        DeleteExamplerRequest $request
    ) {
        $validated =
            $request->validated();

        $exampler =
            Exampler::findOrFail(
                $validated[
                'exampler_id'
                ]
            );

        /*
         * =========================================================
         * VALIDAR USO EN MATERIAL
         * =========================================================
         */

        $hasMaterials =
            Material::query()
                ->where(
                    'exampler_id',
                    $exampler->id
                )
                ->exists();

        if ($hasMaterials) {

            return response()->json([
                'message' =>
                    'No se puede eliminar el modelo porque está siendo utilizado por uno o más materiales.',
            ], 422);
        }

        DB::beginTransaction();

        try {

            $exampler->delete();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo eliminar el modelo.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Modelo eliminado con éxito.',
        ], 200);
    }


    public function create()
    {
        /*
         * Brand ya está aislado por TenantScope.
         */
        $brands = Brand::query()
            ->orderBy(
                'name',
                'asc'
            )
            ->get();

        return view(
            'exampler.create',
            compact('brands')
        );
    }


    public function edit($id)
    {
        $brands = Brand::query()
            ->orderBy(
                'name',
                'asc'
            )
            ->get();

        $exampler =
            Exampler::with(
                'brand'
            )->findOrFail(
                $id
            );

        return view(
            'exampler.edit',
            compact(
                'exampler',
                'brands'
            )
        );
    }


    public function getExamplers()
    {
        $examplers =
            Exampler::with(
                'brand'
            )
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        return datatables(
            $examplers
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
         * TenantScope filtra automáticamente
         * IDs pertenecientes a otros tenants.
         */
        $examplers =
            Exampler::query()
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

        $examplerIds =
            $examplers
                ->pluck('id');

        $usedExamplerIds =
            Material::query()
                ->whereIn(
                    'exampler_id',
                    $examplerIds
                )
                ->pluck(
                    'exampler_id'
                )
                ->filter()
                ->unique();

        if ($usedExamplerIds->isNotEmpty()) {

            $usedNames =
                $examplers
                    ->whereIn(
                        'id',
                        $usedExamplerIds
                    )
                    ->pluck('name')
                    ->implode(', ');

            return response()->json([
                'message' =>
                    'No se pueden eliminar los modelos porque están siendo utilizados por materiales: ' .
                    $usedNames .
                    '.',
            ], 422);
        }

        DB::beginTransaction();

        try {

            foreach (
                $examplers
                as $exampler
            ) {
                $exampler->delete();
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudieron eliminar los modelos.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Modelos eliminados correctamente.',
        ]);
    }
}