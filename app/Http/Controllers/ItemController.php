<?php

namespace App\Http\Controllers;

use App\Entry;
use App\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Item $item)
    {
        //
    }

    public function edit(Item $item)
    {
        //
    }

    public function update(Request $request, Item $item)
    {
        //
    }

    public function destroy(Item $item)
    {
        //
    }

    private function legacyRows($action, $materialId, array $states, $length = 30)
    {
        app(\App\Services\Materials\LegacyItemReadAccess::class)->authorize($action);
        $read = app(\App\Services\Materials\LegacyItemReadQuery::class);
        $read->material($materialId);
        $query = $read->items()->where('i.material_id', $materialId);
        if ($action === 'getJsonItemsTransfer') { $query->where('i.state_item', '<>', 'exited'); }
        else { $query->whereIn('i.state_item', $states); }
        $result = [];
        foreach ($query->get() as $item) {
            $label = $read->location($item, $action === 'getJsonItemsTransfer');
            if ($action === 'getJsonItemsTransfer') {
                $result[] = ['id'=>$item->id, 'location'=>substr($label, 0, 30).'...', 'material_id'=>$item->material_id,
                    'code'=>$item->code, 'length'=>$item->length, 'width'=>$item->width,
                    'state_item'=>$item->state_item, 'percentage'=>$item->percentage];
                continue;
            }
            $row = ['id'=>$item->id, 'location'=>$length === null ? $label : substr($label, 0, $length).'...',
                'location_id'=>$item->location_id, 'typescrap'=>$item->safe_typescrap_id ?? '',
                'material'=>$item->material_name, 'material_id'=>$item->material_id, 'price'=>$item->material_price,
                'state'=>$item->state, 'code'=>$item->code, 'length'=>$item->length, 'width'=>$item->width,
                'weight'=>$item->weight, 'detailEntry'=>$item->safe_detail_id];
            if ($action !== 'getJsonItems') { $row['percentage'] = $item->percentage; }
            $result[] = $row;
        }
        return $result;
    }

    public function getJsonItems($id_material)
    {
        return $this->legacyRows(__FUNCTION__, $id_material, ['exited'], null);
    }

    public function getJsonItemsTransfer($id_material)
    {
        return json_encode($this->legacyRows(__FUNCTION__, $id_material, []));
    }

    public function getJsonItemsOutput($id_material)
    {
        return json_encode($this->legacyRows(__FUNCTION__, $id_material, ['entered', 'scraped']));
    }

    public function getJsonItemsOutputComplete($id_material)
    {
        return json_encode($this->legacyRows(__FUNCTION__, $id_material, ['entered'], 20));
    }

    public function getJsonItemsOutputScraped($id_material)
    {
        return json_encode($this->legacyRows(__FUNCTION__, $id_material, ['scraped'], 20));
    }

    public function getJsonItemsEntry($entry)
    {
        $read = app(\App\Services\Materials\LegacyItemReadQuery::class);
        $details = $read->details($entry);
        $items = $read->items(true)->whereIn('i.detail_entry_id', $details->pluck('id'))->get()->groupBy('detail_entry_id');
        $arrayDetails = []; $arrayItems = [];
        foreach ($details as $detail) {
            $arrayDetails[] = ['id'=>$detail->id, 'material'=>$detail->safe_stock_name ?? $detail->safe_material_name ?? $detail->material_name,
                'code'=>$detail->safe_sku ?? $detail->safe_material_code, 'ordered_quantity'=>$detail->entered_quantity, 'unit_price'=>$detail->unit_price];
            foreach ($items->get($detail->id, collect()) as $key=>$item) {
                $arrayItems[] = ['id'=>$key+1, 'material'=>$item->stock_name ?? $item->material_name, 'code'=>$item->code,
                    'length'=>$item->length, 'width'=>$item->width, 'weight'=>$item->weight,
                    'price'=>$item->unit_cost ?? $item->material_price, 'location'=>substr($read->location($item), 0, 30).'...', 'state'=>$item->state];
            }
        }
        return response()->json(['details'=>$arrayDetails, 'items'=>$arrayItems]);
    }
}
