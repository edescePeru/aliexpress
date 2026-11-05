<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Http\Requests\DeleteBrandRequest;
use App\Http\Requests\DeleteMultipleBrandsRequest;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Material;
use App\Services\Materials\DeleteBrands;
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

    public function destroy(DeleteBrandRequest $request)
    {
        $validated = $request->validated();

        try {
            app(\App\Services\Materials\DeleteBrands::class)->delete(
                [$validated['brand_id']],
                'brand_id'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudo eliminar la marca.',
            ], 422);
        }

        return response()->json([
            'message' => 'Marca de material eliminada con éxito.',
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

    public function deleteMultiple(DeleteMultipleBrandsRequest $request)
    {
        $validated = $request->validated();

        try {
            $count = app(DeleteBrands::class)
                ->delete($validated['ids']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudieron eliminar las marcas.',
            ], 422);
        }

        return response()->json([
            'message' => 'Marcas eliminadas correctamente.',
            'deleted_count' => $count,
        ], 200);
    }

}