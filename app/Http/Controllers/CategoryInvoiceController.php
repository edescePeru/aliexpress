<?php

namespace App\Http\Controllers;

use App\CategoryInvoice;
use App\Http\Requests\DeleteCategoryInvoiceRequest;
use App\Http\Requests\StoreCategoryInvoiceRequest;
use App\Http\Requests\UpdateCategoryInvoiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CategoryInvoiceController extends Controller
{
    public function index()
    {
        $categories = CategoryInvoice::all();
        //$permissions = Permission::all();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('categoryInvoice.index', compact('categories', 'permissions'));
    }


    public function storeO(StoreCategoryInvoiceRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $category = CategoryInvoice::create([
                'name' => $request->get('name'),
                'description' => $request->get('description'),
            ]);

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Categoría de factura guardado con éxito.'], 200);
    }

    public function store(StoreCategoryInvoiceRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {

            $category = CategoryInvoice::create([
                'name' => $request->get('name'),
                'description' => $request->get('description'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Categoría de factura guardada con éxito.',
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                ]
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function update(UpdateCategoryInvoiceRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $category = CategoryInvoice::find($request->get('category_id'));

            $category->name = $request->get('name');
            $category->description = $request->get('description');
            $category->save();

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Categoría de factura modificado con éxito.','url'=>route('categoryInvoice.index')], 200);
    }


    public function destroy(DeleteCategoryInvoiceRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $category = CategoryInvoice::find($request->get('category_id'));

            $category->delete();

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Categoría de factura eliminado con éxito.'], 200);
    }


    public function create()
    {
        return view('categoryInvoice.create');
    }

    public function edit($id)
    {
        $category = CategoryInvoice::find($id);
        return view('categoryInvoice.edit', compact('category'));
    }


    public function getCategories()
    {
        $categories = CategoryInvoice::select('id', 'name', 'description')->get();
        return datatables($categories)->toJson();
        //dd(datatables($customers)->toJson());
    }
}
