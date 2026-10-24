<?php

namespace App\Http\Controllers;

use App\Services\Inventory\ManagedWarehouseService;
use Illuminate\Http\Request;

class ManagedWarehouseController extends Controller
{
    public function index(ManagedWarehouseService $service, $companyId, $branchId = null)
    {
        return response()->json($service->state($companyId, $branchId));
    }

    public function store(Request $request, ManagedWarehouseService $service, $companyId, $branchId = null)
    {
        $data = $request->validate([
            'action' => 'required|in:save,default,preference',
            'warehouse_id' => 'nullable|required_if:action,default|integer|min:1',
            'name' => 'required_if:action,save|string|max:255',
            'comment' => 'nullable|string|max:255',
        ]);
        $service->save($companyId, $branchId, $data);
        return response()->json(['message' => 'La configuración se guardó correctamente.']);
    }
}
