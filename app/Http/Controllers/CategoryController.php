<?php

namespace App\Http\Controllers;

use App\Category;
use App\Http\Requests\DeleteCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Material;
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

    public function destroy(
        DeleteCategoryRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         * TenantScope protege el Tenant actual.
         */
        $category =
            Category::with(
                'subcategories'
            )
                ->findOrFail(
                    $validated[
                    'category_id'
                    ]
                );

        /*
         * =========================================================
         * VALIDAR USO DIRECTO DE CATEGORY
         * =========================================================
         */

        $hasMaterialsWithCategory =
            Material::query()
                ->where(
                    'category_id',
                    $category->id
                )
                ->exists();

        if ($hasMaterialsWithCategory) {

            return response()->json([
                'message' =>
                    'No se puede eliminar la categoría porque está siendo utilizada por uno o más materiales.',
            ], 422);
        }

        /*
         * =========================================================
         * VALIDAR USO DE SUBCATEGORIES
         * =========================================================
         */

        $subcategoryIds =
            $category
                ->subcategories
                ->pluck('id');

        if ($subcategoryIds->isNotEmpty()) {

            $hasMaterialsWithSubcategory =
                Material::query()
                    ->whereIn(
                        'subcategory_id',
                        $subcategoryIds
                    )
                    ->exists();

            if ($hasMaterialsWithSubcategory) {

                return response()->json([
                    'message' =>
                        'No se puede eliminar la categoría porque una o más de sus subcategorías están siendo utilizadas por materiales.',
                ], 422);
            }
        }

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
         * TenantScope filtra automáticamente.
         */
        $categories =
            Category::with(
                'subcategories'
            )
                ->whereIn(
                    'id',
                    $ids
                )
                ->get();

        /*
         * =========================================================
         * VALIDAR USO DIRECTO DE CATEGORIES
         * =========================================================
         */

        $categoryIds =
            $categories
                ->pluck('id');

        $usedCategoryIds =
            Material::query()
                ->whereIn(
                    'category_id',
                    $categoryIds
                )
                ->pluck(
                    'category_id'
                )
                ->filter()
                ->unique();

        if (
        $usedCategoryIds
            ->isNotEmpty()
        ) {

            $usedNames =
                $categories
                    ->whereIn(
                        'id',
                        $usedCategoryIds
                    )
                    ->pluck('name')
                    ->implode(', ');

            return response()->json([
                'message' =>
                    'No se pueden eliminar las categorías porque están siendo utilizadas por materiales: ' .
                    $usedNames .
                    '.',
            ], 422);
        }

        /*
         * =========================================================
         * VALIDAR USO DE SUBCATEGORIES
         * =========================================================
         */

        $subcategoryIds =
            $categories
                ->flatMap(
                    function ($category) {

                        return $category
                            ->subcategories
                            ->pluck('id');
                    }
                )
                ->filter()
                ->unique();

        if (
        $subcategoryIds
            ->isNotEmpty()
        ) {

            $usedSubcategoryIds =
                Material::query()
                    ->whereIn(
                        'subcategory_id',
                        $subcategoryIds
                    )
                    ->pluck(
                        'subcategory_id'
                    )
                    ->filter()
                    ->unique();

            if (
            $usedSubcategoryIds
                ->isNotEmpty()
            ) {

                return response()->json([
                    'message' =>
                        'No se pueden eliminar las categorías porque una o más de sus subcategorías están siendo utilizadas por materiales.',
                ], 422);
            }
        }

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