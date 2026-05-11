<?php

namespace App\Http\Controllers;

use App\ContactName;
use App\Customer;
use App\Http\Requests\DeleteContactNameRequest;
use App\Http\Requests\DeleteCustomerRequest;
use App\Http\Requests\RestoreContactNameRequest;
use App\Http\Requests\StoreContactNameRequest;
use App\Http\Requests\UpdateContactNameRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactNameController extends Controller
{
    public function index()
    {
        //$customers = Customer::all();
        //$permissions = Permission::all();
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('contactName.index',  compact('permissions'));
    }

    public function store(StoreContactNameRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $contactName = ContactName::create([
                'name' => $request->get('name'),
                'customer_id' => $request->get('customer_id'),
                'phone' => $request->get('phone'),
                'email' => $request->get('email'),
                'area' => $request->get('area'),
            ]);

            $length = 5;
            $string = $contactName->id;
            $codecustomer = 'CN-'.str_pad($string,$length,"0", STR_PAD_LEFT);
            //output: 0012345

            $contactName->code = $codecustomer;
            $contactName->save();

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Contacto guardado con éxito.'], 200);
    }

    public function update(UpdateContactNameRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $contactName = ContactName::find($request->get('contactName_id'));

            $contactName->name = $request->get('name');
            $contactName->customer_id = $request->get('customer_id');
            $contactName->phone = $request->get('phone');
            $contactName->email = $request->get('email');
            $contactName->area = $request->get('area');
            $contactName->save();

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Contacto modificado con éxito.','url'=>route('contactName.index')], 200);
    }

    public function destroy(DeleteContactNameRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $contact = ContactName::find($request->get('contactName_id'));

            $contact->delete();

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Contacto eliminado con éxito.'], 200);
    }

    public function create()
    {
        $customers = Customer::all();
        return view('contactName.create', compact('customers'));

    }

    public function show(Customer $customer)
    {
        // Visualizar todos los datos de un cliente
    }

    public function edit($id)
    {
        $contactName = ContactName::with('customer')->find($id);
        $customers = Customer::all();
        return view('contactName.edit', compact('contactName', 'customers'));
    }


    public function getContacts()
    {
        $contacts = ContactName::with('customer')->get();
        return datatables($contacts)->toJson();
        //dd(datatables($customers)->toJson());
    }

    public function indexrestore()
    {
        //$customers = Customer::all();
        //$permissions = Permission::all();

        return view('contactName.restore');
    }

    public function getContactsDestroy()
    {
        $contactNames = ContactName::onlyTrashed()->with('customer')->get();

        return datatables($contactNames)->toJson();
        //dd(datatables($customers)->toJson());
    }

    public function restore(RestoreContactNameRequest $request)
    {
        
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            //$customer = Customer::find($request->get('customer_id'));
            /*
            $contact = ContactName::onlyTrashed()->where('id', $request->get('contactName_id'))->first();

            $contact->restore();

            DB::commit();
            */

            $customer = Customer::withTrashed()->find($request->get('customer_id'));
            $customer_delete = $customer->deleted_at;

            if(is_null($customer_delete)) {
                $contact = ContactName::onlyTrashed()->where('id', $request->get('contactName_id'))->first();

                $contact->restore();
            } else {
                return response()->json(['message' => 'La empresa se encuentra eliminada.'], 422);   
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Contacto restaurado con éxito.'], 200);
        

        
    }
}
