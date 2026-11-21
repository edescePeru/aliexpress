<?php
namespace App\Services\Inventory;

use App\Services\Materials\MaterialInventoryScope;
use App\Services\Materials\StockItemScreenAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KardexLookup
{
    private function authorize(): void
    {
        app(MaterialInventoryScope::class)->context();
        abort_unless(Auth::user()->can('list_kardex'), 403, 'No tiene permiso para consultar el Kardex.');
    }

    public function stockItems(Request $request)
    {
        $this->authorize();
        $data = $request->validate(['q'=>['nullable', 'string', 'max:100']]);
        $term = trim($data['q'] ?? '');
        $query = app(StockItemScreenAccess::class)->stockItems()
            ->with('material:id,tenant_id,full_name,code')
            ->select('stock_items.id', 'stock_items.material_id', 'stock_items.sku', 'stock_items.barcode', 'stock_items.display_name')
            ->where('stock_items.is_active', true);
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('stock_items.sku', 'like', '%'.$term.'%')->orWhere('stock_items.barcode', 'like', '%'.$term.'%')
                    ->orWhere('stock_items.display_name', 'like', '%'.$term.'%')
                    ->orWhereHas('material', function ($q) use ($term) {
                        $q->where(function ($q) use ($term) {
                            $q->where('code', 'like', '%'.$term.'%')->orWhere('full_name', 'like', '%'.$term.'%');
                        });
                    });
            });
        }
        return $query->orderBy('stock_items.display_name')->orderBy('stock_items.id')->limit(20)->get()->map(function ($item) {
            $code = $item->sku ?: optional($item->material)->code;
            $name = $item->display_name ?: optional($item->material)->full_name;
            return ['id'=>$item->id, 'text'=>trim(($code ? $code.' - ' : '').$name)];
        });
    }

    public function warehouses(Request $request)
    {
        $this->authorize();
        $data = $request->validate(['q'=>['nullable', 'string', 'max:100']]);
        $term = trim($data['q'] ?? '');
        $query = app(MaterialInventoryScope::class)->warehouses(DB::table('warehouses'))
            ->select('warehouses.id', 'warehouses.name', 'warehouses.branch_id');
        if ($term !== '') { $query->where('warehouses.name', 'like', '%'.$term.'%'); }
        return $query->orderByRaw('warehouses.branch_id IS NOT NULL')->orderBy('warehouses.name')->orderBy('warehouses.id')
            ->limit(20)->get()->map(function ($warehouse) {
                return ['id'=>$warehouse->id, 'text'=>$warehouse->name.($warehouse->branch_id === null ? ' (General)' : ' (Local actual)')];
            });
    }
}
