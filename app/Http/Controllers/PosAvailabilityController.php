<?php
namespace App\Http\Controllers;

use App\Services\Sales\PosCatalogReadService;
use Illuminate\Http\Request;

class PosAvailabilityController extends Controller
{
    public function search(Request $request, PosCatalogReadService $service)
    {
        return response()->json($service->search($request));
    }

    public function show(Request $request, $stockItem, PosCatalogReadService $service)
    {
        return response()->json($service->availability($request, $stockItem));
    }
}
