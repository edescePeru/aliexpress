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

    public function getJsonItems($id_material)
    {
        $array = [];
        $items = Item::with(['location', 'typescrap', 'material', 'detailEntry'])
            ->where('material_id', $id_material)->where('state_item','exited')
            ->get();
        foreach ( $items as $item )
        {
            $l = 'AR:'.$item->location->area->name.'|AL:'.$item->location->warehouse->name.'|AN:'.$item->location->shelf->name.'|NIV:'.$item->location->level->name.'|CON:'.$item->location->container->name;
            array_push($array,
                [
                    'id'=> $item->id,
                    'location' => $l,
                    'location_id' => $item->location->id,
                    'typescrap' => (isset($item->typescrap)) ? $item->typescrap->id : '',
                    'material' => $item->material->full_description,
                    'material_id' => $item->material->id,
                    'price' => $item->material->unit_price,
                    'state' => $item->state,
                    'code' => $item->code,
                    'length' => $item->length,
                    'width' => $item->width,
                    'weight' => $item->weight,
                    'detailEntry' => $item->detailEntry->id,
                ]);
        }
        //dd($array);
        return $array;
    }

    public function getJsonItemsEntry($entry)
    {
        $arrayItems = [];
        $arrayDetails = [];

        $entry = Entry::with([
            'details.material',
            'details.stockItem',
            'details.items.stockItem',
            'details.items.material',
            'details.items.location.area',
            'details.items.location.warehouse',
            'details.items.location.shelf',
            'details.items.location.level',
            'details.items.location.container',
        ])->find($entry);

        foreach ($entry->details as $detail) {

            array_push($arrayDetails,
                [
                    'id'=> $detail->id,
                    'material' => optional($detail->stockItem)->display_name
                        ?? optional($detail->material)->full_name,

                    'code' => optional($detail->stockItem)->sku
                        ?? optional($detail->material)->code,
                    'ordered_quantity' => $detail->entered_quantity,
                    'unit_price' => $detail->unit_price
                ]);

            $items = Item::with([
                'location.area',
                'location.warehouse',
                'location.shelf',
                'location.level',
                'location.container',
                'typescrap',
                'material',
                'stockItem'
            ])
                ->where('detail_entry_id', $detail->id)
                ->get();
            foreach ($items as $key => $item) {

                $l = 'AR:' . optional($item->location->area)->name .
                    '|AL:' . optional($item->location->warehouse)->name .
                    '|AN:' . optional($item->location->shelf)->name .
                    '|NIV:' . optional($item->location->level)->name .
                    '|CON:' . optional($item->location->container)->name;

                $arrayItems[] = [
                    'id' => $key + 1,
                    'material' => optional($item->stockItem)->display_name
                        ?? optional($item->material)->full_name,
                    'code' => $item->code,
                    'length' => $item->length,
                    'width' => $item->width,
                    'weight' => $item->weight,
                    'price' => $item->unit_cost
                        ?? optional($item->material)->unit_price,
                    'location' => substr($l, 0, 30) . '...',
                    'state' => $item->state,
                ];
            }
        }

        //dd($array);
        return response()->json(['details' => $arrayDetails, 'items' => $arrayItems], 200);

    }

    public function getJsonItemsTransfer($id_material)
    {
        $array = [];
        $items = Item::select([
            'id',
            'material_id',
            'code',
            'length',
            'width',
            'percentage',
            'state_item',
            'location_id'
        ])
            ->with('location')
            ->where('material_id', $id_material)
            ->where('state_item', '<>', 'exited')->get();

        foreach ( $items as $item )
        {
            $l = 'AR:'.$item->location->area->name.'|AL:'.$item->location->warehouse->name.'|AN:'.$item->location->shelf->name.'|NIV:'.$item->location->level->name.'|CON:'.$item->location->container->name.'|POS:'.$item->location->position->name;
            array_push($array,
                [
                    'id'=> $item->id,
                    'location' => substr($l,0,30).'...',
                    'material_id' => $id_material,
                    'code' => $item->code,
                    'length' => $item->length,
                    'width' => $item->width,
                    'state_item' => $item->state_item,
                    'percentage' => $item->percentage,
                ]);
        }

        //dump($array[0]);
        return json_encode($array);

    }

    public function getJsonItemsOutput($id_material)
    {
        $array = [];
        $items = Item::with(['location', 'typescrap', 'material', 'detailEntry'])
            ->where('material_id', $id_material)->whereIn('state_item',['entered', 'scraped'])
            ->get();
        foreach ( $items as $item )
        {
            $l = 'AR:'.$item->location->area->name.'|AL:'.$item->location->warehouse->name.'|AN:'.$item->location->shelf->name.'|NIV:'.$item->location->level->name.'|CON:'.$item->location->container->name;
            array_push($array,
                [
                    'id'=> $item->id,
                    'location' => substr($l,0,30).'...',
                    'location_id' => $item->location->id,
                    'typescrap' => (isset($item->typescrap)) ? $item->typescrap->id : '',
                    'material' => $item->material->full_description,
                    'material_id' => $item->material->id,
                    'price' => $item->material->unit_price,
                    'state' => $item->state,
                    'code' => $item->code,
                    'length' => $item->length,
                    'width' => $item->width,
                    'weight' => $item->weight,
                    'detailEntry' => $item->detailEntry->id,
                    'percentage' => $item->percentage,
                ]);
        }
        //dd($array);
        return json_encode($array);
    }

    public function getJsonItemsOutputComplete($id_material)
    {
        $array = [];
        $items = Item::with(['location', 'typescrap', 'material', 'detailEntry'])
            //->where('material_id', $id_material)->whereIn('state_item',['entered', 'scraped'])
            ->where('material_id', $id_material)->whereIn('state_item',['entered'])
            ->get();
        foreach ( $items as $item )
        {
            $l = 'AR:'.$item->location->area->name.'|AL:'.$item->location->warehouse->name.'|AN:'.$item->location->shelf->name.'|NIV:'.$item->location->level->name.'|CON:'.$item->location->container->name;
            array_push($array,
                [
                    'id'=> $item->id,
                    'location' => substr($l,0,20).'...',
                    'location_id' => $item->location->id,
                    'typescrap' => (isset($item->typescrap)) ? $item->typescrap->id : '',
                    'material' => $item->material->full_description,
                    'material_id' => $item->material->id,
                    'price' => $item->material->unit_price,
                    'state' => $item->state,
                    'code' => $item->code,
                    'length' => $item->length,
                    'width' => $item->width,
                    'weight' => $item->weight,
                    'detailEntry' => $item->detailEntry->id,
                    'percentage' => $item->percentage,
                ]);
        }
        //dd($array);
        return json_encode($array);
    }

    public function getJsonItemsOutputScraped($id_material)
    {
        $array = [];
        $items = Item::with(['location', 'typescrap', 'material', 'detailEntry'])
            //->where('material_id', $id_material)->whereIn('state_item',['entered', 'scraped'])
            ->where('material_id', $id_material)->whereIn('state_item',['scraped'])
            ->get();
        foreach ( $items as $item )
        {
            $l = 'AR:'.$item->location->area->name.'|AL:'.$item->location->warehouse->name.'|AN:'.$item->location->shelf->name.'|NIV:'.$item->location->level->name.'|CON:'.$item->location->container->name;
            array_push($array,
                [
                    'id'=> $item->id,
                    'location' => substr($l,0,20).'...',
                    'location_id' => $item->location->id,
                    'typescrap' => (isset($item->typescrap)) ? $item->typescrap->id : '',
                    'material' => $item->material->full_description,
                    'material_id' => $item->material->id,
                    'price' => $item->material->unit_price,
                    'state' => $item->state,
                    'code' => $item->code,
                    'length' => $item->length,
                    'width' => $item->width,
                    'weight' => $item->weight,
                    'detailEntry' => $item->detailEntry->id,
                    'percentage' => $item->percentage,
                ]);
        }
        //dd($array);
        return json_encode($array);
    }
}
