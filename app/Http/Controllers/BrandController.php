<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Http\Requests\DeleteBrandRequest;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    public function index()
    {
        /*
         * TenantScope se aplica automáticamente.
         */
        $brands = Brand::all();

        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        return view(
            'brand.index',
            compact(
                'brands',
                'permissions'
            )
        );
    }

    public function store(StoreBrandRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {

            /*
             * NO enviamos tenant_id.
             *
             * BelongsToTenant lo asignará
             * automáticamente desde TenantContext.
             */
            $brand = Brand::create([
                'name' =>
                    $validated['name'],

                'comment' =>
                    $validated['comment']
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
                    'No se pudo registrar la marca.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Marca de material guardada con éxito.',

            'success' =>
                true,

            'data' => [
                'id' =>
                    $brand->id,

                'name' =>
                    $brand->name,

                'comment' =>
                    $brand->comment,
            ],
        ], 200);
    }

    public function update(UpdateBrandRequest $request)
    {
        $validated = $request->validated();

        /*
         * IMPORTANTE:
         *
         * findOrFail pasa por TenantScope.
         *
         * Si Tenant 1 intenta modificar una
         * Brand de Tenant 2, devuelve 404.
         *
         * Lo hacemos FUERA del try/catch para
         * conservar correctamente el 404.
         */
        $brand = Brand::findOrFail(
            $validated['brand_id']
        );

        DB::beginTransaction();

        try {

            $brand->name =
                $validated['name'];

            $brand->comment =
                $validated['comment']
                ?? null;

            $brand->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo modificar la marca.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Marca de material modificada con éxito.',

            'url' =>
                route('brand.index'),
        ], 200);
    }

    public function destroy(
        DeleteBrandRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         * Brand está protegida por TenantScope.
         */
        $brand =
            Brand::with(
                'examplers'
            )
                ->findOrFail(
                    $validated[
                    'brand_id'
                    ]
                );

        /*
         * =========================================================
         * VALIDAR USO DIRECTO DE BRAND EN MATERIAL
         * =========================================================
         */

        $hasMaterialsWithBrand =
            Material::query()
                ->where(
                    'brand_id',
                    $brand->id
                )
                ->exists();

        if ($hasMaterialsWithBrand) {

            return response()->json([
                'message' =>
                    'No se puede eliminar la marca porque está siendo utilizada por uno o más materiales.',
            ], 422);
        }

        /*
         * =========================================================
         * VALIDAR USO DE EXAMPLERS EN MATERIAL
         * =========================================================
         */

        $examplerIds =
            $brand
                ->examplers
                ->pluck('id');

        if ($examplerIds->isNotEmpty()) {

            $hasMaterialsWithExampler =
                Material::query()
                    ->whereIn(
                        'exampler_id',
                        $examplerIds
                    )
                    ->exists();

            if ($hasMaterialsWithExampler) {

                return response()->json([
                    'message' =>
                        'No se puede eliminar la marca porque uno o más de sus modelos están siendo utilizados por materiales.',
                ], 422);
            }
        }

        DB::beginTransaction();

        try {

            /*
             * Nadie utiliza la Brand ni sus Examplers.
             *
             * Podemos hacer soft delete de los hijos
             * y posteriormente de la Brand.
             */
            foreach (
                $brand->examplers
                as $exampler
            ) {
                $exampler->delete();
            }

            $brand->delete();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo eliminar la marca.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Marca de material eliminada con éxito.',
        ], 200);
    }

    public function create()
    {
        return view(
            'brand.create'
        );
    }

    public function edit($id)
    {
        /*
         * Antes:
         * Brand::find($id)
         *
         * Ahora:
         * TenantScope + 404 cross-tenant.
         */
        $brand = Brand::findOrFail(
            $id
        );

        return view(
            'brand.edit',
            compact('brand')
        );
    }

    public function getBrands()
    {
        /*
         * TenantScope se aplica automáticamente.
         */
        $brands = Brand::query()
            ->select(
                'id',
                'name',
                'comment'
            )
            ->orderBy(
                'name',
                'asc'
            )
            ->get();

        return datatables(
            $brands
        )->toJson();
    }

    public function getJsonBrands($id)
    {
        /*
         * Antes se hacía:
         *
         * Exampler::where('brand_id', $id)
         *
         * Eso permitía enviar directamente el ID
         * de una Brand perteneciente a otro Tenant.
         *
         * Primero validamos la Brand usando
         * TenantScope.
         */
        $brand = Brand::findOrFail(
            $id
        );

        /*
         * Ahora obtenemos los Examplers
         * mediante la relación de la Brand
         * ya autorizada.
         */
        $examplers =
            $brand->examplers()
                ->get();

        $array = [];

        foreach (
            $examplers as $exampler
        ) {

            $array[] = [
                'id' =>
                    $exampler->id,

                'exampler' =>
                    $exampler->name,
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

        /*
         * TenantScope actúa automáticamente.
         */
        $brands =
            Brand::with(
                'examplers'
            )
                ->whereIn(
                    'id',
                    $ids
                )
                ->get();

        /*
         * =========================================================
         * VALIDAR USO DIRECTO DE BRANDS
         * =========================================================
         */

        $brandIds =
            $brands
                ->pluck('id');

        $usedBrandIds =
            Material::query()
                ->whereIn(
                    'brand_id',
                    $brandIds
                )
                ->pluck(
                    'brand_id'
                )
                ->filter()
                ->unique();

        if ($usedBrandIds->isNotEmpty()) {

            $usedNames =
                $brands
                    ->whereIn(
                        'id',
                        $usedBrandIds
                    )
                    ->pluck('name')
                    ->implode(', ');

            return response()->json([
                'message' =>
                    'No se pueden eliminar las marcas porque están siendo utilizadas por materiales: ' .
                    $usedNames .
                    '.',
            ], 422);
        }

        /*
         * =========================================================
         * VALIDAR USO DE EXAMPLERS
         * =========================================================
         */

        $examplerIds =
            $brands
                ->flatMap(
                    function ($brand) {

                        return $brand
                            ->examplers
                            ->pluck('id');
                    }
                )
                ->filter()
                ->unique();

        if ($examplerIds->isNotEmpty()) {

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

                return response()->json([
                    'message' =>
                        'No se pueden eliminar las marcas porque uno o más de sus modelos están siendo utilizados por materiales.',
                ], 422);
            }
        }

        DB::beginTransaction();

        try {

            foreach (
                $brands
                as $brand
            ) {

                foreach (
                    $brand->examplers
                    as $exampler
                ) {
                    $exampler->delete();
                }

                $brand->delete();
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudieron eliminar las marcas.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Marcas eliminadas correctamente.',
        ]);
    }
}