<?php

namespace App\Http\Controllers;

use App\DataGeneral;
use App\Material;
use App\PromotionLimit;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromotionLimitController extends Controller
{
    public function index()
    {
        //$limits = PromotionLimit::with(['material'])->paginate(20);
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('promotionLimit.index',  compact( 'permissions'));
    }

    public function getMaterials(Request $request)
    {
        $materials = [];

        if ($request->has('q')) {
            $search = $request->get('q');

            $materials = Material::where('enable_status', 1) // 👈 aquí filtras en la BD
            ->get()
                ->filter(function ($item) use ($search) {
                    return stripos($item->full_description, $search) !== false;
                });
        }

        return json_encode($materials);
    }

    public function getMaterialTotals()
    {
        $materials = Material::with('unitMeasure','typeScrap')
            ->where('enable_status', 1)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, [
                'id'=> $material->id,
                'full_description' => $material->full_description,
                'unit' => $material->unitMeasure->name,
                'code' => $material->code,
                'type_scrap' => $material->typeScrap,
                'unit_measure' => $material->unitMeasure,
                'list_price' => $material->list_price,
                'enable_status' => $material->enable_status,
                'stock_current' => $material->stock_current,
                'state_update_price' => $material->state_update_price
            ]);
        }

        return $array;
    }

    public function getDataPromotions(Request $request, $pageNumber = 1)
    {
        $perPage = 10;

        $dateCurrent = Carbon::now('America/Lima');
        $date4MonthAgo = $dateCurrent->subMonths(6);
        $query = PromotionLimit::with('material')->orderBy('created_at', 'DESC');

        // Aplicar filtros si se proporcionan

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $promotions = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();


        $array = [];

        foreach ( $promotions as $promotion )
        {

            array_push($array, [
                "id" => $promotion->id,
                "material" => $promotion->material->full_name,
                "limit_quantity" => $promotion->limit_quantity,
                "applies_to" => ($promotion->applies_to == "worker") ? 'Por Trabajador': "Global",
                "original_price" => ($promotion->original_price == null || $promotion->original_price == "") ? '0': $promotion->original_price,
                "promo_price" => ($promotion->promo_price == null || $promotion->promo_price == "") ? "0": $promotion->promo_price,
                "start_date" => ($promotion->start_date == null || $promotion->start_date == "") ? '': $promotion->start_date->format('d/m/Y'),
                "end_date" => ($promotion->end_date == null || $promotion->end_date == "") ? "":$promotion->end_date->format('d/m/Y'),
            ]);
        }

        $pagination = [
            'currentPage' => (int)$pageNumber,
            'totalPages' => (int)$totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords
        ];

        return ['data' => $array, 'pagination' => $pagination];
    }

    public function create()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $currency = app(SettingService::class)->get('finance.base_currency');

        return view('promotionLimit.create', compact('currency', 'permissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'limit_quantity' => 'required|integer|min:1',
            'applies_to' => 'required|in:worker,global',
            'price_type' => 'required|in:fixed,percentage',
            'percentage' => 'nullable|numeric|min:0',
            'promo_price' => 'nullable|numeric|min:0',
            'original_price' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $limit = PromotionLimit::create($data);

        return response()->json(['message' => 'Promoción creada correctamente', 'data' => $limit]);
    }

    public function edit($id)
    {
        $promotion = PromotionLimit::with('material')->findOrFail($id);

        $currency = app(SettingService::class)->get('finance.base_currency');

        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('promotionLimit.edit', compact('currency', 'permissions', 'promotion'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'limit_quantity' => 'required|integer|min:1',
            'applies_to' => 'required|in:worker,global',
            'price_type' => 'required|in:fixed,percentage',
            'percentage' => 'nullable|numeric|min:0',
            'promo_price' => 'nullable|numeric|min:0',
            'original_price' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $promotion = PromotionLimit::findOrFail($request->promotion_id);

            $promotion->update([
                'limit_quantity' => $request->limit_quantity,
                'applies_to' => $request->applies_to,
                'price_type' => $request->price_type,
                'percentage' => $request->percentage,
                'promo_price' => $request->promo_price,
                'original_price' => $request->original_price,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            return response()->json(['message' => 'Promoción modificada correctamente']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al actualizar la promoción.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($promotion_id)
    {
        try {
            $promotion = PromotionLimit::findOrFail($promotion_id);
            $promotion->delete();

            return response()->json(['message' => 'Promoción anulada correctamente']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al anular la promoción',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
