<?php
namespace App\Http\Controllers;

use App\Http\Requests\WarehouseAvailabilityRequest;
use App\Services\Inventory\WarehouseAvailabilityService;
use App\Services\Transfers\WarehouseTransferAccess;

class WarehouseAvailabilityController extends Controller
{
    public function index(WarehouseTransferAccess $access)
    {
        $access->context(); return view('warehouseAvailability.index');
    }
    public function products(WarehouseAvailabilityRequest $request, WarehouseAvailabilityService $service)
    {
        $query = $service->catalog(); $text = trim((string) $request->input('q', ''));
        if ($text !== '') { $query->where(function ($q) use ($text) { $q->where('s.display_name', 'like', '%'.$text.'%')->orWhere('s.sku', 'like', '%'.$text.'%')->orWhere('s.barcode', 'like', '%'.$text.'%'); }); }
        $rows = $query->select('s.id', 's.sku', 's.display_name')->orderBy('s.display_name')->orderBy('s.id')->offset(((int) $request->input('page', 1) - 1) * 25)->limit(26)->get();
        return response()->json(['results' => $rows->take(25)->map(function ($row) { return ['id' => $row->id, 'text' => $row->display_name.' · '.$row->sku]; })->values(), 'pagination' => ['more' => $rows->count() > 25]]);
    }
    public function data(WarehouseAvailabilityRequest $request, WarehouseAvailabilityService $service)
    {
        return response()->json($service->snapshot((int) $request->input('stock_item_id')));
    }
}
