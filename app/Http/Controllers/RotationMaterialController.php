<?php

namespace App\Http\Controllers;

use App\DataGeneral;
use App\DetailEntry;
use App\Entry;
use App\Item;
use App\Material;
use App\OutputDetail;
use App\RotationMaterial;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\SettingService;

class RotationMaterialController extends Controller
{
    public function getRotationMaterial()
    {
        DB::beginTransaction();
        try {

            $date = Carbon::now("America/Lima");

            $settings = app(SettingService::class);

            $start_rotation_baja = $settings->get('inventory.rotation.low_start');
            $end_rotation_baja = $settings->get('inventory.rotation.low_end');
            $start_rotation_media = $settings->get('inventory.rotation.medium_start');
            $end_rotation_media = $settings->get('inventory.rotation.medium_end');
            $start_rotation_alta = $settings->get('inventory.rotation.high_start');

            // TODO: Actualizacion de rotacion de materiales
            $lastRotation = RotationMaterial::latest()->first();
            $totalOutputs = 0;
            $materialsQuantity = [];
            $quantityMaterials = [];

            if ( !isset($lastRotation) )
            {
                // TODO: Significa que no hay ultima rotacion tomamos todas las salidas desde el 2023
                //$output_details = OutputDetail::whereYear('created_at', '>=', 2023)->get();
                $output_details = OutputDetail::whereDate('created_at', '>=', '2023-07-01')->get();
                //$output_details = OutputDetail::all();
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id == null )
                    {
                        // TODO: Entrar al item y tomar su porcentaje
                        $item_original = Item::find($output_detail->item_id);
                        if ($item_original) {
                            $material = $item_original->material;

                            // Verifica si el material existe y está activo
                            if ($material && ($material->enable_status == 1) && ($material->category_id != 8)  && $material->stock_current > 0 ) {
                                // El material está activo
                                $totalOutputs += $item_original->percentage;
                                // Guardamos el material en un array y su porcentaje
                                array_push($materialsQuantity, [
                                    "material_id" => $material->id,
                                    "percentage" => $item_original->percentage
                                ]);
                            }
                        }

                    } else {
                        $item_original = Item::find($output_detail->item_id);
                        if ( isset($item_original) )
                        {
                            $material = $output_detail->material;
                            if ($material && ($material->enable_status == 1) && ($material->category_id != 8)  && $material->stock_current > 0 ) {
                                // El material está activo
                                $totalOutputs += $item_original->percentage;
                                // Guardamos el material en un array y su porcentaje
                                array_push($materialsQuantity, [
                                    "material_id" => $material->id,
                                    "percentage" => $item_original->percentage
                                ]);
                            }
                        }

                    }
                }


            } else {
                // TODO: Significa que si hay ultima rotacion tomamos todas las salidas entre ambas fechas
                $output_details = OutputDetail::where('created_at', '>=', $lastRotation->date_rotation);
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id == null )
                    {
                        // TODO: Entrar al item y tomar su porcentaje
                        $item_original = Item::find($output_detail->item_id);
                        if ($item_original) {
                            $material = $item_original->material;

                            // Verifica si el material existe y está activo
                            if ($material && ($material->enable_status == 1) && ($material->category_id != 8)  && $material->stock_current > 0 ) {
                                // El material está activo
                                $totalOutputs += $item_original->percentage;
                                // Guardamos el material en un array y su porcentaje
                                array_push($materialsQuantity, [
                                    "material_id" => $material->id,
                                    "percentage" => $item_original->percentage
                                ]);
                            }
                        }

                    } else {
                        $material = $output_detail->material;
                        $item_original = Item::find($output_detail->item_id);
                        if (isset($item_original))
                        {
                            if ($material && ($material->enable_status == 1) && ($material->category_id != 8)  && $material->stock_current > 0 ) {
                                // El material está activo
                                $totalOutputs += $item_original->percentage;
                                // Guardamos el material en un array y su porcentaje
                                array_push($materialsQuantity, [
                                    "material_id" => $material->id,
                                    "percentage" => $item_original->percentage
                                ]);
                            }
                        }


                    }
                }
            }

            $new_arr2 = array();
            foreach($materialsQuantity as $item) {
                if(isset($new_arr2[$item['material_id']])) {
                    $new_arr2[ $item['material_id']]['percentage'] += (float)$item['percentage'];
                    continue;
                }

                $new_arr2[$item['material_id']] = $item;
            }

            $quantityMaterials = array_values($new_arr2);

            $finalMaterialsQuantity = [];

            for ( $i=0; $i<count($quantityMaterials); $i++ )
            {
                $material = Material::find($quantityMaterials[$i]['material_id']);

                $totalEntries = 0;

                if ( !isset($lastRotation) )
                {
                    // TODO: Significa que no hay ultima rotacion tomamos todas las entradas desde el 2023
                    //$output_details = OutputDetail::whereYear('created_at', '>=', 2023)->get();
                    $entryDetails = DetailEntry::where('material_id', $material->id)
                        //->whereYear('created_at', '>=', 2023)
                        ->whereDate('created_at', '>=', '2023-07-01')
                        ->get();

                    foreach ( $entryDetails as $detail )
                    {
                        $entry = Entry::with(['supplier'])->find($detail->entry_id);
                        if ( $entry->entry_type != 'Retacería' )
                        {
                            $totalEntries += (float)$detail->entered_quantity;
                        }
                    }


                } else {
                    // TODO: Significa que si hay ultima rotacion tomamos todas las entradas entre ambas fechas
                    //$output_details = OutputDetail::where('created_at', '>=', $lastRotation->date_rotation);
                    $entryDetails = DetailEntry::where('material_id', '=', $material->id)
                        ->where('created_at', '>=', $lastRotation->date_rotation)
                        ->get();

                    foreach ( $entryDetails as $detail )
                    {
                        $entry = Entry::with(['supplier'])->find($detail->entry_id);
                        if ( $entry->entry_type != 'Retacería' )
                        {
                            $totalEntries += (float)$detail->entered_quantity;
                        }
                    }
                }

                $percentage = $quantityMaterials[$i]['percentage'];

                if ( $totalEntries>0 )
                {
                    $rotation_value = round(($percentage/$totalEntries)*100, 2);
                } else {
                    $rotation_value = 0;
                }

                $rotation_state = "";

                if ( $rotation_value >= $start_rotation_alta )
                {
                    $rotation_state = "a";
                } elseif ( $rotation_value >= $start_rotation_media && $rotation_value < $end_rotation_media )
                {
                    $rotation_state = "m";
                } elseif ( $rotation_value >= $start_rotation_baja && $rotation_value < $end_rotation_baja )
                {
                    $rotation_state = "b";
                }

                array_push($finalMaterialsQuantity, [
                    'material_id' => $quantityMaterials[$i]['material_id'],
                    'percentage' => $quantityMaterials[$i]['percentage'],
                    'rotation_value' => $rotation_value,
                    'rotation_state' => $rotation_state,
                    'total_entries' => $totalEntries
                ]);
            }

            usort($finalMaterialsQuantity, function($a, $b) {
                return $b['rotation_value'] <=> $a['rotation_value'];
            });

            dump($finalMaterialsQuantity);

            //dump($quantityMaterials);

            //dump($finalMaterialsQuantity);
            dd();

            /*$rotation = RotationMaterial::create([
                'date_rotation' => Carbon::now("America/Lima"),
                'user_id' => Auth::id(),
            ]);*/

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Corte de Rotación'. $date->format("d/m/Y") .' guardado con éxito.'], 200);
    }

    public function storeRotationMaterial()
    {
        DB::beginTransaction();
        try {

            $date = Carbon::now("America/Lima");

            $settings = app(SettingService::class);

            $start_rotation_baja = $settings->get('inventory.rotation.low_start');
            $end_rotation_baja = $settings->get('inventory.rotation.low_end');
            $start_rotation_media = $settings->get('inventory.rotation.medium_start');
            $end_rotation_media = $settings->get('inventory.rotation.medium_end');
            $start_rotation_alta = $settings->get('inventory.rotation.high_start');

            // TODO: Actualizacion de rotacion de materiales
            $lastRotation = RotationMaterial::latest()->first();
            $totalOutputs = 0;
            $materialsQuantity = [];
            $quantityMaterials = [];

            if ( !isset($lastRotation) )
            {
                // TODO: Significa que no hay ultima rotacion tomamos todas las salidas desde el 2023
                $output_details = OutputDetail::whereDate('created_at', '>=', '2023-07-01')->get();
                //$output_details = OutputDetail::all();
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id == null )
                    {
                        // TODO: Entrar al item y tomar su porcentaje
                        $item_original = Item::find($output_detail->item_id);
                        if ($item_original) {
                            $material = $item_original->material;

                            // Verifica si el material existe y está activo
                            if ($material && ($material->enable_status == 1) && ($material->category_id != 8)  && $material->stock_current > 0 ) {
                                // El material está activo
                                $totalOutputs += $item_original->percentage;
                                // Guardamos el material en un array y su porcentaje
                                array_push($materialsQuantity, [
                                    "material_id" => $material->id,
                                    "percentage" => $item_original->percentage
                                ]);
                            }
                        }

                    } else {
                        $item_original = Item::find($output_detail->item_id);
                        if ( isset($item_original) )
                        {
                            $material = $output_detail->material;
                            if ($material && ($material->enable_status == 1) && ($material->category_id != 8)  && $material->stock_current > 0 ) {
                                // El material está activo
                                $totalOutputs += $item_original->percentage;
                                // Guardamos el material en un array y su porcentaje
                                array_push($materialsQuantity, [
                                    "material_id" => $material->id,
                                    "percentage" => $item_original->percentage
                                ]);
                            }
                        }

                    }
                }


            } else {
                // TODO: Significa que si hay ultima rotacion tomamos todas las salidas entre ambas fechas
                $output_details = OutputDetail::where('created_at', '>=', $lastRotation->date_rotation);
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id == null )
                    {
                        // TODO: Entrar al item y tomar su porcentaje
                        $item_original = Item::find($output_detail->item_id);
                        if ($item_original) {
                            $material = $item_original->material;

                            // Verifica si el material existe y está activo
                            if ($material && ($material->enable_status == 1) && ($material->category_id != 8)  && $material->stock_current > 0 ) {
                                // El material está activo
                                $totalOutputs += $item_original->percentage;
                                // Guardamos el material en un array y su porcentaje
                                array_push($materialsQuantity, [
                                    "material_id" => $material->id,
                                    "percentage" => $item_original->percentage
                                ]);
                            }
                        }

                    } else {
                        $material = $output_detail->material;
                        $item_original = Item::find($output_detail->item_id);
                        if (isset($item_original))
                        {
                            if ($material && ($material->enable_status == 1) && ($material->category_id != 8)  && $material->stock_current > 0 ) {
                                // El material está activo
                                $totalOutputs += $item_original->percentage;
                                // Guardamos el material en un array y su porcentaje
                                array_push($materialsQuantity, [
                                    "material_id" => $material->id,
                                    "percentage" => $item_original->percentage
                                ]);
                            }
                        }


                    }
                }
            }

            $new_arr2 = array();
            foreach($materialsQuantity as $item) {
                if(isset($new_arr2[$item['material_id']])) {
                    $new_arr2[ $item['material_id']]['percentage'] += (float)$item['percentage'];
                    continue;
                }

                $new_arr2[$item['material_id']] = $item;
            }

            $quantityMaterials = array_values($new_arr2);

            $finalMaterialsQuantity = [];

            for ( $i=0; $i<count($quantityMaterials); $i++ )
            {
                $material = Material::find($quantityMaterials[$i]['material_id']);

                $totalEntries = 0;

                if ( !isset($lastRotation) )
                {
                    // TODO: Significa que no hay ultima rotacion tomamos todas las entradas desde el 2023
                    //$output_details = OutputDetail::whereYear('created_at', '>=', 2023)->get();
                    $entryDetails = DetailEntry::where('material_id', $material->id)
                        //->whereYear('created_at', '>=', 2023)
                        ->whereDate('created_at', '>=', '2023-07-01')
                        ->get();

                    foreach ( $entryDetails as $detail )
                    {
                        $entry = Entry::with(['supplier'])->find($detail->entry_id);
                        if ( $entry->entry_type != 'Retacería' )
                        {
                            $totalEntries += (float)$detail->entered_quantity;
                        }
                    }


                } else {
                    // TODO: Significa que si hay ultima rotacion tomamos todas las entradas entre ambas fechas
                    //$output_details = OutputDetail::where('created_at', '>=', $lastRotation->date_rotation);
                    $entryDetails = DetailEntry::where('material_id', '=', $material->id)
                        ->where('created_at', '>=', $lastRotation->date_rotation)
                        ->get();

                    foreach ( $entryDetails as $detail )
                    {
                        $entry = Entry::with(['supplier'])->find($detail->entry_id);
                        if ( $entry->entry_type != 'Retacería' )
                        {
                            $totalEntries += (float)$detail->entered_quantity;
                        }
                    }
                }

                $percentage = $quantityMaterials[$i]['percentage'];

                if ( $totalEntries>0 )
                {
                    $rotation_value = round(($percentage/$totalEntries)*100, 2);
                } else {
                    $rotation_value = 0;
                }

                $rotation_state = "";

                if ( $rotation_value >= $start_rotation_alta )
                {
                    $rotation_state = "a";
                } elseif ( $rotation_value >= $start_rotation_media && $rotation_value < $end_rotation_media )
                {
                    $rotation_state = "m";
                } elseif ( $rotation_value >= $start_rotation_baja && $rotation_value < $end_rotation_baja )
                {
                    $rotation_state = "b";
                }

                array_push($finalMaterialsQuantity, [
                    'material_id' => $quantityMaterials[$i]['material_id'],
                    'percentage' => $quantityMaterials[$i]['percentage'],
                    'rotation_value' => $rotation_value,
                    'rotation_state' => $rotation_state,
                    'total_entries' => $totalEntries
                ]);
            }

            for ( $i=0; $i<count($finalMaterialsQuantity); $i++ )
            {
                $material_id = $finalMaterialsQuantity[$i]['material_id'];
                $material = Material::find($material_id);
                $material->rotation = $finalMaterialsQuantity[$i]['rotation_state'];
                $material->rotation_value = $finalMaterialsQuantity[$i]['rotation_value'];
                $material->save();
            }

            //dump($totalOutputs);

            //dump($quantityMaterials);

            //dump($finalMaterialsQuantity);

            //dd();

            $rotation = RotationMaterial::create([
                'date_rotation' => Carbon::now("America/Lima"),
                'user_id' => Auth::id(),
            ]);

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Corte de Rotación '. $date->format("d/m/Y") .' guardado con éxito.'], 200);
    }

    public function getDataRotations(Request $request, $pageNumber = 1)
    {
        $perPage = 5;

        $query = RotationMaterial::with('user')
            ->orderBy('id', 'desc');

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $rotations = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $array = [];

        foreach ($rotations as $index => $rotation)
        {
            array_push($array, [
                "id" => $index+1,
                "fecha" => $rotation->date_rotation->format('d/m/Y'),
                "user" => ($rotation->user_id != null) ? $rotation->user->name:"Sin información"
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

    public function destroy(RotationMaterial $rotationMaterial)
    {
        //
    }
}
