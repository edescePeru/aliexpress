<?php

namespace App\Http\Controllers;

use App\Bank;
use App\Supplier;
use App\SupplierAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierAccountController extends Controller
{
    public function index($worker_id)
    {
        /*
         * La ruta todavía usa worker_id por legado,
         * pero realmente corresponde a supplier_id.
         *
         * Supplier ya tiene TenantScope.
         */
        $supplier = Supplier::findOrFail($worker_id);

        $banks = Bank::all();

        $user = Auth::user();

        /*
         * SupplierAccount también tiene TenantScope.
         */
        $accounts = SupplierAccount::query()
            ->where('supplier_id', $supplier->id)
            ->with('bank')
            ->get();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        return view(
            'supplierAccount.index',
            compact(
                'permissions',
                'supplier',
                'banks',
                'accounts'
            )
        );
    }


    public function store(Request $request, $supplier_id)
    {
        /*
         * Validar primero que el Supplier pertenezca
         * al Tenant actual.
         */
        $supplier = Supplier::findOrFail($supplier_id);

        $validated = $request->validate([
            'number_account' => [
                'nullable',
                'string',
                'max:191',
            ],

            'currency' => [
                'nullable',
                Rule::in([
                    'PEN',
                    'USD',
                ]),
            ],

            'bank_id' => [
                'nullable',
                'exists:banks,id',
            ],
        ]);


        DB::beginTransaction();

        try {

            /*
             * tenant_id será asignado automáticamente
             * mediante BelongsToTenant.
             */
            $supplierAccount = SupplierAccount::create([
                'supplier_id' =>
                    $supplier->id,

                'number_account' =>
                    $request->input(
                        'number_account'
                    ),

                'currency' =>
                    $request->input(
                        'currency',
                        'PEN'
                    ),

                'bank_id' =>
                    $request->input(
                        'bank_id'
                    ),
            ]);

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
                'Cuenta Bancaria generada con éxito.',

            'account' =>
                $supplierAccount,
        ], 200);
    }


    public function update(
        Request $request,
        $account_id
    ) {
        $validated = $request->validate([
            'number_account' => [
                'nullable',
                'string',
                'max:191',
            ],

            'bank_id' => [
                'nullable',
                'exists:banks,id',
            ],

            'currency' => [
                'required',
                Rule::in([
                    'PEN',
                    'USD',
                ]),
            ],
        ]);


        /*
         * SupplierAccount ya tiene TenantScope.
         *
         * Si intentan modificar una cuenta perteneciente
         * a otro Tenant, devuelve 404.
         */
        $account = SupplierAccount::findOrFail(
            $account_id
        );


        DB::beginTransaction();

        try {

            $account->number_account =
                $request->input(
                    'number_account'
                );

            $account->bank_id =
                $request->input(
                    'bank_id'
                );

            $account->currency =
                $request->input(
                    'currency'
                );

            $account->save();


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
                'Cuenta Bancaria modificada con éxito.',
        ], 200);
    }


    public function destroy(
        Request $request,
        $account_id
    ) {
        /*
         * TenantScope protege contra eliminación
         * cross-tenant.
         */
        $account = SupplierAccount::findOrFail(
            $account_id
        );


        DB::beginTransaction();

        try {

            $account->delete();

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
                'Cuenta Bancaria eliminada con éxito.',
        ], 200);
    }
}