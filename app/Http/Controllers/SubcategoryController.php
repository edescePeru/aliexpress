<?php

namespace App\Http\Controllers;

use App\Category;
use App\Http\Requests\DeleteSubcategoryRequest;
use App\Http\Requests\StoreSubcategoryRequest;
use App\Http\Requests\UpdateSubcategoryRequest;
use App\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubcategoryController extends Controller
{
    public function index()
    {
        /*
         * TenantScope se aplica automáticamente
         * tanto a Subcategory como a Category.
         */
        $subcategories = Subcategory::with(
            'category'
        )->get();

        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        return view(
            'subcategory.index',
            compact(
                'subcategories',
                'permissions'
            )
        );
    }


    public function store(
        StoreSubcategoryRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         * Segunda barrera.
         *
         * La validación ya comprueba que
         * category_id pertenece al Tenant actual,
         * pero volvemos a resolverla mediante
         * Category + TenantScope.
         */
        $category =
            Category::findOrFail(
                $validated['category_id']
            );

        DB::beginTransaction();

        try {

            $created = [];

            foreach (
                $validated['subcategories']
                as $sub
            ) {

                $subcategory =
                    Subcategory::create([
                        'name' =>
                            $sub['name'],

                        'description' =>
                            $sub['description']
                            ?? null,

                        'category_id' =>
                            $category->id,
                    ]);

                $created[] =
                    $subcategory;
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudieron registrar las subcategorías.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Subcategorías guardadas con éxito.',

            'data' =>
                $created,
        ], 200);
    }


    /*
     * Actualmente ambos flujos utilizan
     * la misma estructura de validación.
     */
    public function storeIndividual(
        StoreSubcategoryRequest $request
    ) {
        return $this->store(
            $request
        );
    }


    public function update(
        UpdateSubcategoryRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         * Subcategory protegida por TenantScope.
         *
         * Fuera del try/catch para conservar 404
         * si se manipula el ID de otro Tenant.
         */
        $subcategory =
            Subcategory::findOrFail(
                $validated['subcategory_id']
            );

        /*
         * La nueva Category también debe
         * pertenecer al Tenant actual.
         */
        $category =
            Category::findOrFail(
                $validated['category_id']
            );

        DB::beginTransaction();

        try {

            $subcategory->name =
                $validated['name'];

            $subcategory->description =
                $validated['description']
                ?? null;

            $subcategory->category_id =
                $category->id;

            $subcategory->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo modificar la subcategoría.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Subcategoría modificada con éxito.',

            'url' =>
                route('subcategory.index'),
        ], 200);
    }


    public function destroy(
        DeleteSubcategoryRequest $request
    ) {
        $validated =
            $request->validated();

        $subcategory =
            Subcategory::findOrFail(
                $validated['subcategory_id']
            );

        DB::beginTransaction();

        try {

            $subcategory->delete();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo eliminar la subcategoría.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Subcategoría eliminada con éxito.',
        ], 200);
    }


    public function create()
    {
        /*
         * Category ya tiene TenantScope.
         */
        $categories = Category::query()
            ->orderBy(
                'name',
                'asc'
            )
            ->get();

        return view(
            'subcategory.create',
            compact('categories')
        );
    }


    public function edit($id)
    {
        /*
         * Ambas consultas quedan filtradas
         * por TenantScope.
         */
        $categories = Category::query()
            ->orderBy(
                'name',
                'asc'
            )
            ->get();

        $subcategory =
            Subcategory::with(
                'category'
            )->findOrFail(
                $id
            );

        return view(
            'subcategory.edit',
            compact(
                'categories',
                'subcategory'
            )
        );
    }


    public function getSubcategories()
    {
        /*
         * IMPORTANTE:
         *
         * La consulta comienza desde Subcategory,
         * por lo tanto TenantScope se aplica
         * automáticamente.
         */
        $subcategories =
            Subcategory::query()
                ->select(
                    'subcategories.*'
                )
                ->join(
                    'categories',
                    'subcategories.category_id',
                    '=',
                    'categories.id'
                )
                /*
                 * Protección adicional.
                 *
                 * Category también tiene tenant_id,
                 * así que aseguramos consistencia
                 * entre padre e hijo.
                 */
                ->whereColumn(
                    'subcategories.tenant_id',
                    'categories.tenant_id'
                )
                ->orderBy(
                    'categories.name',
                    'asc'
                )
                ->orderBy(
                    'subcategories.name',
                    'asc'
                )
                ->with('category')
                ->get();

        return datatables(
            $subcategories
        )->toJson();
    }


    public function deleteMultiple(
        Request $request
    ) {
        $ids =
            $request->input('ids');

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
         * TenantScope elimina automáticamente
         * de la consulta IDs pertenecientes
         * a otros tenants.
         */
        $subcategories =
            Subcategory::query()
                ->whereIn(
                    'id',
                    $ids
                )
                ->get();

        DB::beginTransaction();

        try {

            foreach (
                $subcategories
                as $subcategory
            ) {
                $subcategory->delete();
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudieron eliminar las subcategorías.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Subcategorías eliminadas correctamente.',
        ]);
    }
}