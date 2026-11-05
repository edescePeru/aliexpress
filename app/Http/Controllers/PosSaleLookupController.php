<?php
namespace App\Http\Controllers;
use App\{Item,MaterialPresentation};
use App\Services\Sales\{PosCatalogReadService, PosInventoryService};
use Illuminate\Http\Request;
class PosSaleLookupController extends Controller
{
    public function items(Request $request,$stockItemId)
    {
        $catalog=app(PosCatalogReadService::class);$catalog->authorize();
        app(\App\Services\Materials\MaterialInventoryScope::class)->snapshot($request);
        $stock=$catalog->catalog()->where('stock_items.id',$stockItemId)->firstOrFail();
        $rows=app(PosInventoryService::class)->items(Item::query(),(int)$stock->id)
            ->with(['warehouse:id,name','stockLot:id,lot_code'])->orderBy('items.id')->get();
        return response()->json(['success'=>true,'items'=>$rows->map(function($item){
            $lot=optional($item->stockLot)->lot_code?:'Sin lote';$warehouse=optional($item->warehouse)->name?:'';
            return ['id'=>$item->id,'code'=>$item->code?:'Ítem #'.$item->id,'stock_item_id'=>$item->stock_item_id,
                'material_id'=>$item->material_id,'stock_lot_id'=>$item->stock_lot_id,'stock_lot_code'=>$lot,'lot_code'=>$lot,
                'warehouse_id'=>$item->warehouse_id,'warehouse_name'=>$warehouse,'location_id'=>$item->location_id];
        })->values()]);
    }
    public function presentations(Request $request,$materialId)
    {
        $catalog=app(PosCatalogReadService::class);$catalog->authorize();
        app(\App\Services\Materials\MaterialInventoryScope::class)->snapshot($request);
        abort_unless($catalog->catalog()->where('stock_items.material_id',$materialId)->exists(),404);
        return response()->json(['presentations'=>app(\App\Services\Sales\BranchPresentationService::class)->available($materialId)]);
    }
}
