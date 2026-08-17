<?php

namespace App\Http\Controllers;

use App\Category;
use App\Http\Requests\DeleteCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index()
    {
        /*
         * TenantScope se aplica automáticamente.
         */
        $categories = Category::all();

        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        return view(
            'category.index',
            compact(
                'categories',
                'permissions'
            )
        );
    }

    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {

            /*
             * No enviamos tenant_id.
             * BelongsToTenant lo asigna automáticamente.
             */
            $category = Category::create([
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
                    'No se pudo registrar la categoría.',
            ], 422);
        }

        return response()->json([
            'success' =>
                true,

            'message' =>
                'Categoría de material guardada con éxito.',

            'data' => [
                'id' =>
                    $category->id,

                'description' =>
                    $category->name,
            ],
        ], 200);
    }

    public function update(UpdateCategoryRequest $request)
    {
        $validated = $request->validated();

        /*
         * Protegido por TenantScope.
         *
         * Lo dejamos fuera del try/catch
         * para conservar correctamente el 404.
         */
        $category = Category::findOrFail(
            $validated['category_id']
        );

        DB::beginTransaction();

        try {

            $category->name =
                $validated['name'];

            $category->description =
                $validated['description']
                ?? null;

            $category->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo modificar la categoría.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Categoría de material modificada con éxito.',

            'url' =>
                route('category.index'),
        ], 200);
    }

    public function destroy(DeleteCategoryRequest $request)
    {
        $validated = $request->validated();

        /*
         * Esta Category ya queda aislada
         * por TenantScope.
         */
        $category = Category::with(
            'subcategories'
        )->findOrFail(
            $validated['category_id']
        );

        DB::beginTransaction();

        try {

            foreach (
                $category->subcategories
                as $subcategory
            ) {
                $subcategory->delete();
            }

            $category->delete();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo eliminar la categoría.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Categoría de material eliminada con éxito.',
        ], 200);
    }

    public function create()
    {
        return view(
            'category.create'
        );
    }

    public function show(Category $category)
    {
        //
    }

    public function edit($id)
    {
        /*
         * Antes:
         * Category::find($id)
         *
         * Ahora:
         * TenantScope + 404 cross-tenant.
         */
        $category = Category::findOrFail(
            $id
        );

        return view(
            'category.edit',
            compact('category')
        );
    }

    public function getCategories()
    {
        $categories = Category::query()
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
            $categories
        )->toJson();
    }

    public function getSubcategoryByCategory($id)
    {
        /*
         * IMPORTANTE:
         *
         * Subcategory todavía no tiene TenantScope.
         *
         * Primero validamos la Category padre
         * mediante TenantScope.
         */
        $category = Category::findOrFail(
            $id
        );

        $subcategories =
            $category
                ->subcategories()
                ->get();

        $array = [];

        foreach (
            $subcategories
            as $subcategory
        ) {
            $array[] = [
                'id' =>
                    $subcategory->id,

                'subcategory' =>
                    $subcategory->name,
            ];
        }

        return $array;
    }

    public function deleteMultiple(Request $request)
    {
        $ids = $request->input(
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
         * TenantScope filtra automáticamente.
         *
         * IDs de otros tenants no serán encontrados.
         */
        $categories = Category::with(
            'subcategories'
        )
            ->whereIn(
                'id',
                $ids
            )
            ->get();

        DB::beginTransaction();

        try {

            foreach (
                $categories
                as $category
            ) {

                foreach (
                    $category->subcategories
                    as $subcategory
                ) {
                    $subcategory->delete();
                }

                $category->delete();
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudieron eliminar las categorías.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Categorías eliminadas correctamente.',
        ]);
    }
}