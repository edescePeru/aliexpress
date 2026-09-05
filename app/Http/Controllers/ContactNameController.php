<?php

namespace App\Http\Controllers;

use App\ContactName;
use App\Customer;
use App\Http\Requests\DeleteContactNameRequest;
use App\Http\Requests\RestoreContactNameRequest;
use App\Http\Requests\StoreContactNameRequest;
use App\Http\Requests\UpdateContactNameRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactNameController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        return view(
            'contactName.index',
            compact('permissions')
        );
    }


    public function store(StoreContactNameRequest $request)
    {
        $validated = $request->validated();

        /*
         * Customer ya es Tenant-aware.
         *
         * Aunque el Request también lo valida,
         * hacemos una segunda defensa antes de crear.
         */
        $customer = Customer::findOrFail(
            $request->get('customer_id')
        );

        DB::beginTransaction();

        try {

            /*
             * tenant_id se asigna automáticamente
             * mediante BelongsToTenant.
             */
            $contactName = ContactName::create([
                'name' =>
                    $request->get('name'),

                'customer_id' =>
                    $customer->id,

                'phone' =>
                    $request->get('phone'),

                'email' =>
                    $request->get('email'),

                'area' =>
                    $request->get('area'),
            ]);


            $length = 5;

            $contactName->code =
                'CN-' .
                str_pad(
                    $contactName->id,
                    $length,
                    '0',
                    STR_PAD_LEFT
                );

            $contactName->save();


            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }


        return response()->json([
            'message' =>
                'Contacto guardado con éxito.',
        ], 200);
    }


    public function update(UpdateContactNameRequest $request)
    {
        $validated = $request->validated();


        /*
         * ContactName tiene TenantScope.
         */
        $contactName = ContactName::findOrFail(
            $request->get('contactName_id')
        );


        /*
         * El contacto solamente puede cambiarse a
         * otro Customer del mismo Tenant.
         */
        $customer = Customer::findOrFail(
            $request->get('customer_id')
        );


        DB::beginTransaction();

        try {

            $contactName->name =
                $request->get('name');

            $contactName->customer_id =
                $customer->id;

            $contactName->phone =
                $request->get('phone');

            $contactName->email =
                $request->get('email');

            $contactName->area =
                $request->get('area');

            $contactName->save();


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
                'Contacto modificado con éxito.',

            'url' =>
                route('contactName.index'),
        ], 200);
    }


    public function destroy(DeleteContactNameRequest $request)
    {
        $validated = $request->validated();


        /*
         * TenantScope impide eliminar un contacto
         * perteneciente a otro Tenant.
         */
        $contact = ContactName::findOrFail(
            $request->get('contactName_id')
        );


        DB::beginTransaction();

        try {

            $contact->delete();

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
                'Contacto eliminado con éxito.',
        ], 200);
    }


    public function create()
    {
        /*
         * Customer tiene TenantScope.
         */
        $customers = Customer::query()
            ->orderBy('business_name')
            ->get();

        return view(
            'contactName.create',
            compact('customers')
        );
    }


    public function show(Customer $customer)
    {
        /*
         * Route Model Binding queda protegido
         * por TenantScope de Customer.
         */
    }


    public function edit($id)
    {
        /*
         * ContactName y Customer tienen TenantScope.
         */
        $contactName = ContactName::query()
            ->with('customer')
            ->findOrFail($id);


        $customers = Customer::query()
            ->orderBy('business_name')
            ->get();


        return view(
            'contactName.edit',
            compact(
                'contactName',
                'customers'
            )
        );
    }


    public function getContacts()
    {
        /*
         * TenantScope automático.
         */
        $contacts = ContactName::query()
            ->with('customer')
            ->get();

        return datatables($contacts)
            ->toJson();
    }


    public function indexrestore()
    {
        return view(
            'contactName.restore'
        );
    }


    public function getContactsDestroy()
    {
        /*
         * onlyTrashed mantiene TenantScope.
         */
        $contactNames = ContactName::onlyTrashed()
            ->with('customer')
            ->get();

        return datatables($contactNames)
            ->toJson();
    }


    public function restore(
        RestoreContactNameRequest $request
    ) {
        $validated = $request->validated();


        /*
         * Buscamos el contacto eliminado dentro
         * del Tenant actual.
         */
        $contact = ContactName::onlyTrashed()
            ->where(
                'id',
                $request->get('contactName_id')
            )
            ->firstOrFail();


        /*
         * NO confiamos en customer_id enviado
         * desde frontend.
         *
         * Utilizamos el customer_id real guardado
         * en el contacto.
         */
        $customer = Customer::withTrashed()
            ->where(
                'id',
                $contact->customer_id
            )
            ->first();


        if (!$customer) {
            return response()->json([
                'message' =>
                    'No se encontró el cliente asociado al contacto.',
            ], 422);
        }


        if ($customer->trashed()) {
            return response()->json([
                'message' =>
                    'El cliente se encuentra eliminado. Restáurelo antes de restaurar el contacto.',
            ], 422);
        }


        DB::beginTransaction();

        try {

            $contact->restore();

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
                'Contacto restaurado con éxito.',
        ], 200);
    }
}