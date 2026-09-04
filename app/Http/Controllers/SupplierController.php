<?php

namespace App\Http\Controllers;

use App\Exports\SuppliersExport;
use App\Http\Requests\DeleteSupplierRequest;
use App\Http\Requests\RestoreSupplierRequest;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Supplier;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        return view(
            'supplier.index',
            compact('permissions')
        );
    }


    public function create()
    {
        return view('supplier.create');
    }


    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();

        /*
         * Validación antes de abrir la transacción.
         */
        if (
            $request->get('special') !== 'true'
            &&
            strlen((string) $request->get('ruc')) > 11
        ) {
            return response()->json([
                'message' =>
                    'El RUC es demasiado largo, porque no es extranjero.'
            ], 422);
        }

        DB::beginTransaction();

        try {

            /*
             * tenant_id será asignado automáticamente
             * por BelongsToTenant.
             */
            $supplier = Supplier::create([
                'business_name' =>
                    $request->get('business_name'),

                'RUC' =>
                    $request->get('ruc'),

                'address' =>
                    $request->get('address'),

                'phone' =>
                    $request->get('phone'),

                'email' =>
                    $request->get('email'),

                'special' =>
                    $request->get('special') === 'true',
            ]);


            /*
             * El id es global, por lo que este código continuará
             * siendo único aunque ahora Supplier sea Tenant-level.
             */
            $length = 5;

            $supplier->code =
                'PROV-' .
                str_pad(
                    $supplier->id,
                    $length,
                    '0',
                    STR_PAD_LEFT
                );

            $supplier->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' =>
                'Proveedor guardado con éxito.',
        ], 200);
    }


    public function edit($id)
    {
        /*
         * Supplier tiene TenantScope.
         *
         * Si intentan abrir un Supplier de otro Tenant:
         * 404.
         */
        $supplier = Supplier::findOrFail($id);

        return view(
            'supplier.edit',
            compact('supplier')
        );
    }


    public function update(UpdateSupplierRequest $request)
    {
        $validated = $request->validated();

        if (
            $request->get('special') !== 'true'
            &&
            strlen((string) $request->get('ruc')) > 11
        ) {
            return response()->json([
                'message' =>
                    'El RUC es demasiado largo, porque no es extranjero.'
            ], 422);
        }

        /*
         * Dejamos el findOrFail fuera del try.
         *
         * Supplier tiene TenantScope, por lo que un ID de otro
         * Tenant devuelve 404.
         */
        $supplier = Supplier::findOrFail(
            $request->get('supplier_id')
        );

        DB::beginTransaction();

        try {

            $supplier->business_name =
                $request->get('business_name');

            $supplier->RUC =
                $request->get('ruc');

            $supplier->address =
                $request->get('address');

            $supplier->phone =
                $request->get('phone');

            $supplier->email =
                $request->get('email');

            $supplier->special =
                $request->get('special') === 'true';

            $supplier->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' =>
                'Proveedor modificado con éxito.',

            'url' =>
                route('supplier.index'),
        ], 200);
    }


    public function destroy(DeleteSupplierRequest $request)
    {
        $validated = $request->validated();

        /*
         * Segundo nivel de defensa.
         */
        $supplier = Supplier::findOrFail(
            $request->get('supplier_id')
        );

        DB::beginTransaction();

        try {

            $supplier->delete();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' =>
                'Proveedor eliminado con éxito.',
        ], 200);
    }


    public function getSuppliers()
    {
        /*
         * TenantScope se aplica automáticamente.
         */
        $suppliers = Supplier::query()
            ->select(
                'id',
                'code',
                'business_name',
                'RUC',
                'address',
                'phone',
                'email'
            )
            ->with('accounts')
            ->get();

        return datatables($suppliers)->toJson();
    }


    public function indexrestore()
    {
        return view('supplier.restore');
    }


    public function getSuppliersDestroy()
    {
        /*
         * onlyTrashed conserva TenantScope.
         */
        $suppliers = Supplier::onlyTrashed()
            ->get();

        return datatables($suppliers)->toJson();
    }


    public function restore(RestoreSupplierRequest $request)
    {
        $validated = $request->validated();

        /*
         * Supplier sigue protegido por TenantScope incluso
         * utilizando onlyTrashed().
         */
        $supplier = Supplier::onlyTrashed()
            ->where(
                'id',
                $request->get('supplier_id')
            )
            ->firstOrFail();

        DB::beginTransaction();

        try {

            $supplier->restore();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' =>
                'Proveedor restaurado con éxito.',
        ], 200);
    }


    public function generateReport()
    {
        /*
         * IMPORTANTE:
         *
         * DB::table() NO recibe TenantScope.
         */
        $tenantId =
            TenantContext::tenantId();


        $suppliers = DB::table('suppliers')
            ->where(
                'tenant_id',
                $tenantId
            )
            ->whereNull('deleted_at')
            ->get();


        $deletedSuppliers = DB::table('suppliers')
            ->where(
                'tenant_id',
                $tenantId
            )
            ->whereNotNull('deleted_at')
            ->get();


        $data = [];
        $deletedData = [];


        foreach ($suppliers as $supplier) {

            /*
             * supplier_accounts todavía no necesita conocerse
             * como Tenant aquí porque supplier_id ya salió de
             * un Supplier perteneciente al Tenant actual.
             */
            $accounts = DB::table('supplier_accounts')
                ->where(
                    'supplier_id',
                    $supplier->id
                )
                ->pluck('number_account')
                ->toArray();


            $labeledAccounts = [];

            foreach ($accounts as $index => $account) {

                $label =
                    'Cuenta ' .
                    ($index + 1);

                $labeledAccounts[] =
                    $label .
                    ': ' .
                    $account;
            }


            $data[] = [
                'id' =>
                    $supplier->id,

                'code' =>
                    $supplier->code,

                'business_name' =>
                    $supplier->business_name,

                'RUC' =>
                    $supplier->RUC,

                'address' =>
                    $supplier->address,

                'phone' =>
                    $supplier->phone,

                'email' =>
                    $supplier->email,

                'accounts' =>
                    $labeledAccounts,
            ];
        }


        foreach ($deletedSuppliers as $deletedSupplier) {

            $deletedData[] = [
                'id' =>
                    $deletedSupplier->id,

                'code' =>
                    $deletedSupplier->code,

                'business_name' =>
                    $deletedSupplier->business_name,

                'RUC' =>
                    $deletedSupplier->RUC,

                'address' =>
                    $deletedSupplier->address,

                'phone' =>
                    $deletedSupplier->phone,

                'email' =>
                    $deletedSupplier->email,
            ];
        }


        return Excel::download(
            new SuppliersExport(
                $data,
                $deletedData
            ),
            'report.xlsx'
        );
    }
}