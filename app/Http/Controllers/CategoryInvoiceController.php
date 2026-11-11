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
        $categories = CategoryInvoice::forCurrentCompany()->orderBy('name')->get();
        //$permissions = Permission::all();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('categoryInvoice.index', compact('categories', 'permissions'));
    }


    public function store(StoreCategoryInvoiceRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {

            $category = new CategoryInvoice($validated);
            $category->forceFill(CategoryInvoice::currentContext())->save();

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

            $category = CategoryInvoice::forCurrentCompany()->lockForUpdate()->findOrFail($validated['category_id']);

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

            $category = CategoryInvoice::forCurrentCompany()->lockForUpdate()->findOrFail($validated['category_id']);

            if (DB::table('entries')->where('category_invoice_id', $category->id)->exists()) {
                DB::rollBack();
                return response()->json(['message' => 'Esta categoría ya tiene facturas asociadas.'], 422);
            }
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
        $category = CategoryInvoice::forCurrentCompany()->findOrFail($id);
        return view('categoryInvoice.edit', compact('category'));
    }


    public function getCategories()
    {
        $categories = CategoryInvoice::forCurrentCompany()->select('id', 'name', 'description')->orderBy('name')->get();
        return datatables($categories)->toJson();
        //dd(datatables($customers)->toJson());
    }
}
