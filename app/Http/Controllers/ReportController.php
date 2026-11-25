<?php

namespace App\Http\Controllers;

use App\DetailEntry;
use App\Entry;
use App\Exports\AmountReport;
use App\Exports\DatabaseMaterialsExport;
use App\Exports\QuotesReportExcelExport;
use App\Exports\QuoteSummaryExport;
use App\Item;
use App\Location;
use App\Material;
use App\Output;
use App\OutputDetail;
use App\Quote;
use App\Warehouse;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade as PDF;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(\App\Http\Middleware\OperationalModuleGate::class.':industrial-quotes')->only(['quotesReport', 'quoteSummaryReport', 'quoteIndividualReport', 'exportQuotesExcel', 'chartQuotesDollarsSoles', 'chartQuotesDollarsSolesView']);
    }

    public function amountInWarehouse()
    {
        $materials = Material::where('stock_current', '>', 0)
            ->where('description', 'not like', '%EDESCE%')
            ->where('enable_status', 1)
            ->whereNotIn('category_id', [8,6])
            ->get();
        $amount_dollars = 0;
        $amount_soles = 0;
        $quantity_items = 0;
        foreach ( $materials as $material )
        {
            $items = Item::where('material_id', $material->id)
                ->whereNotIn('state_item', ['exited'])->get();
            //dd($items);
            foreach ( $items as $item )
            {
                $detail_entry = DetailEntry::with('entry')->find($item->detail_entry_id);
                //dump($detail_entry);

                if ( isset($detail_entry) )
                {
                    $entry = Entry::find($detail_entry->entry_id);
                    $currency = $entry->currency_invoice;

                    if ( $currency === 'USD' )
                    {
                        $amount_dollars = $amount_dollars + (float)$item->price;
                    } else {
                        $amount_soles = $amount_soles + (float)$item->price;
                    }
                    $quantity_items = $quantity_items + (float)$item->percentage;
                }

            }
        }
        /*$items = Item::whereNotIn('state_item', ['exited'])
            ->whereNotIn('material_id', [1040,1041])->get();
        $amount_dollars = 0;
        $amount_soles = 0;
        $quantity_items = 0;
        //dd($items);
        foreach ( $items as $item )
        {
            $detail_entry = DetailEntry::with('entry')->find($item->detail_entry_id);
            //dump($detail_entry);
            $currency = $detail_entry->entry->currency_invoice;

            if ( $currency === 'USD' )
            {
                $amount_dollars = $amount_dollars + (float)$item->price;
            } else {
                $amount_soles = $amount_soles + (float)$item->price;
            }
            $quantity_items = $quantity_items + (float)$item->percentage;
        }*/

        return response()->json(['amount_dollars' => $amount_dollars, 'amount_soles' => $amount_soles, 'quantity_items' => $quantity_items]);

    }

    public function excelAmountStock()
    {
        $materials = Material::where('stock_current', '>', 0)
            ->where('description', 'not like', '%EDESCE%')
            ->where('enable_status', 1)
            ->whereNotIn('category_id', [8,6])
            ->get();
        $materials_array = [];
        $amount_dollars = 0;
        $amount_soles = 0;
        $quantity_dollars = 0;
        $quantity_soles = 0;
        foreach ( $materials as $material )
        {
            $items = Item::where('material_id', $material->id)
                ->whereNotIn('state_item', ['exited'])->get();
            foreach ( $items as $item )
            {
                $detail_entry = DetailEntry::with('entry')->find($item->detail_entry_id);
                //dump($detail_entry);
                if ( isset($detail_entry) )
                {
                    $entry = Entry::find($detail_entry->entry_id);
                    $currency = $entry->currency_invoice;

                    if ( $currency === 'USD' )
                    {
                        $amount_dollars = $amount_dollars + (float)$item->price;
                        $quantity_dollars = $quantity_dollars + (float)$item->percentage;
                    } else {
                        $amount_soles = $amount_soles + (float)$item->price;
                        $quantity_soles = $quantity_soles + (float)$item->percentage;
                    }
                }

            }

            array_push($materials_array, ['material'=>$material->full_description, 'stock_dollars'=>$quantity_dollars, 'stock_soles'=>$quantity_soles, 'amount_dollars'=>$amount_dollars, 'amount_soles'=>$amount_soles]);

            // Reset values
            $amount_dollars = 0;
            $amount_soles = 0;
            $quantity_dollars = 0;
            $quantity_soles = 0;
        }

        $total_amount_dollars = 0;
        $total_amount_soles = 0;
        $total_quantity_dollars = 0;
        $total_quantity_soles = 0;

        for ( $i=0; $i<count($materials_array); $i++ )
        {
            $total_quantity_dollars += (float) $materials_array[$i]['stock_dollars'];
            $total_quantity_soles += (float) $materials_array[$i]['stock_soles'];
            $total_amount_dollars += (float) $materials_array[$i]['amount_dollars'];
            $total_amount_soles += (float) $materials_array[$i]['amount_soles'];
        }
        //dump($materials_array);

        return Excel::download(new AmountReport($materials_array, $total_amount_dollars,$total_amount_soles,$total_quantity_dollars, $total_quantity_soles), 'reporte_Stock_Monto_En_Almacen.xlsx');
    }

    public function excelBDMaterials()
    {
        $materials = Material::with('category', 'materialType','unitMeasure','subcategory','subType','exampler','brand','warrant','quality','typeScrap')
            ->where('description', 'not like', '%EDESCE%')
            ->where('enable_status', 1)
            ->whereNotIn('category_id', [8])
            ->get();

        $materials_array = [];

        foreach ( $materials as $material )
        {
            $priority = '';
            if ( $material->stock_current > $material->stock_max ){
                $priority = 'Completo';
            } else if ( $material->stock_current == $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current > $material->stock_min && $material->stock_current < $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current == $material->stock_min ){
                $priority = 'Por agotarse';
            } else if ( $material->stock_current < $material->stock_min || $material->stock_current == 0 ){
                $priority = 'Agotado';
            }

            $localizacion = $this->getLocationsGeneralMaterial($material->id);

            array_push($materials_array, [
                'code' => $material->code,
                'material' => $material->full_description,
                'measure' => $material->measure,
                'unit' => ($material->unitMeasure == null) ? '':$material->unitMeasure->name,
                'stock_max' => $material->stock_max,
                'stock_min' => $material->stock_min,
                'stock_current' => $material->stock_current,
                'priority'=> $priority,
                'price'=> $material->unit_price,
                'category'=> ($material->category == null) ? '': $material->category->name,
                'subcategory'=> ($material->subcategory == null) ? '': $material->subcategory->name,
                'type'=> ($material->materialType == null) ? '': $material->materialType->name,
                'subtype'=> ($material->subType == null) ? '': $material->subType->name,
                'brand'=> ($material->brand == null) ? '': $material->brand->name,
                'exampler'=> ($material->exampler == null) ? '': $material->exampler->name,
                'quality'=> ($material->quality == null) ? '': $material->quality->name,
                'warrant'=> ($material->warrant == null) ? '':$material->warrant->name,
                'scrap'=> ($material->typeScrap == null) ? '':$material->typeScrap->name,
                'location' => $localizacion
            ]);
        }
        //dump($materials_array);

        $title = 'BASE DE MATERIALES COMPLETA';

        return Excel::download(new DatabaseMaterialsExport($materials_array, $title), 'reporte_base_materiales.xlsx');
    }

    public function getLocationsGeneralMaterial($material)
    {
        $textLocations = "";
        $items = Item::where('material_id', $material)
            ->where('state_item', '<>', 'exited')
            ->get();

        $locations = $items->pluck('location_id')->unique()->toArray();

        if (!empty($locations)) {
            // No se encontraron items para el material específico
            foreach ($locations as $location) {
                $ubicacion = Location::with(['shelf', 'level'])->find($location);

                $textLocations = $textLocations . $ubicacion->shelf->name ." - ". $ubicacion->level->name ." | ";

            }

        }

        return $textLocations;
    }

    public function excelBDMaterialsByLocation($location_id)
    {
        $materials = Material::with('category', 'materialType','unitMeasure','subcategory','subType','exampler','brand','warrant','quality','typeScrap')
            ->where('description', 'not like', '%EDESCE%')
            ->where('enable_status', 1)
            ->get();

        $materials_array = [];

        foreach ( $materials as $material )
        {
            $priority = '';
            if ( $material->stock_current > $material->stock_max ){
                $priority = 'Completo';
            } else if ( $material->stock_current == $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current > $material->stock_min && $material->stock_current < $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current == $material->stock_min ){
                $priority = 'Por agotarse';
            } else if ( $material->stock_current < $material->stock_min || $material->stock_current == 0 ){
                $priority = 'Agotado';
            }

            $itemsCount = Item::where('material_id', $material->id)
                ->where('location_id', $location_id)
                ->where('state_item', '<>', 'exited')
                ->sum('percentage');

            if ( $itemsCount > 0 )
            {
                array_push($materials_array, [
                    'code' => $material->code,
                    'material' => $material->full_description,
                    'measure' => $material->measure,
                    'unit' => ($material->unitMeasure == null) ? '':$material->unitMeasure->name,
                    'stock_max' => $material->stock_max,
                    'stock_min' => $material->stock_min,
                    'stock_current' => $itemsCount,
                    'priority'=> $priority,
                    'price'=> $material->unit_price,
                    'category'=> ($material->category == null) ? '': $material->category->name,
                    'subcategory'=> ($material->subcategory == null) ? '': $material->subcategory->name,
                    'type'=> ($material->materialType == null) ? '': $material->materialType->name,
                    'subtype'=> ($material->subType == null) ? '': $material->subType->name,
                    'brand'=> ($material->brand == null) ? '': $material->brand->name,
                    'exampler'=> ($material->exampler == null) ? '': $material->exampler->name,
                    'quality'=> ($material->quality == null) ? '': $material->quality->name,
                    'warrant'=> ($material->warrant == null) ? '':$material->warrant->name,
                    'scrap'=> ($material->typeScrap == null) ? '':$material->typeScrap->name,
                ]);

            }

        }
        //dump($materials_array);
        $location = Location::find($location_id);
        $title = 'BASE DE MATERIALES EN AREA';
        if ( !is_null($location) )
        {
            $title = 'BASE DE MATERIALES EN AREA: ' . $location->area->name . ' - ALMACEN: ' . $location->warehouse->name;
        }

        return Excel::download(new DatabaseMaterialsExport($materials_array, $title), 'reporte_base_materiales.xlsx');
    }

    public function excelBDMaterialsByWarehouse($warehouse_id)
    {
        $warehouse = Warehouse::find($warehouse_id);
        $locations = Location::where('warehouse_id', $warehouse_id)->pluck('id')->toArray();
        $materials = Material::with('category', 'materialType','unitMeasure','subcategory','subType','exampler','brand','warrant','quality','typeScrap')
            ->where('description', 'not like', '%EDESCE%')
            ->where('enable_status', 1)
            ->get();

        $materials_array = [];

        foreach ( $materials as $material )
        {
            $priority = '';
            if ( $material->stock_current > $material->stock_max ){
                $priority = 'Completo';
            } else if ( $material->stock_current == $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current > $material->stock_min && $material->stock_current < $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current == $material->stock_min ){
                $priority = 'Por agotarse';
            } else if ( $material->stock_current < $material->stock_min || $material->stock_current == 0 ){
                $priority = 'Agotado';
            }

            $itemsCount = Item::where('material_id', $material->id)
                ->whereIn('location_id', $locations)
                ->where('state_item', '<>', 'exited')
                ->sum('percentage');

            if ( $itemsCount > 0 )
            {
                array_push($materials_array, [
                    'code' => $material->code,
                    'material' => $material->full_description,
                    'measure' => $material->measure,
                    'unit' => ($material->unitMeasure == null) ? '':$material->unitMeasure->name,
                    'stock_max' => $material->stock_max,
                    'stock_min' => $material->stock_min,
                    'stock_current' => $itemsCount,
                    'priority'=> $priority,
                    'price'=> $material->unit_price,
                    'category'=> ($material->category == null) ? '': $material->category->name,
                    'subcategory'=> ($material->subcategory == null) ? '': $material->subcategory->name,
                    'type'=> ($material->materialType == null) ? '': $material->materialType->name,
                    'subtype'=> ($material->subType == null) ? '': $material->subType->name,
                    'brand'=> ($material->brand == null) ? '': $material->brand->name,
                    'exampler'=> ($material->exampler == null) ? '': $material->exampler->name,
                    'quality'=> ($material->quality == null) ? '': $material->quality->name,
                    'warrant'=> ($material->warrant == null) ? '':$material->warrant->name,
                    'scrap'=> ($material->typeScrap == null) ? '':$material->typeScrap->name,
                ]);

            }

        }
        //dump($materials_array);
        $title = '';
        if ( !is_null($warehouse) )
        {
            $title = 'BASE DE MATERIALES EN EL ALMACÉN: ' . $warehouse->name;
        }

        return Excel::download(new DatabaseMaterialsExport($materials_array, $title), 'reporte_base_materiales.xlsx');
    }

    public function chartQuotesDollarsSoles()
    {
        $meses = array("ENE","FEB","MAR","ABR","MAY","JUN","JUL","AGO","SEP","OCT","NOV","DIC");

        $current_date = CarbonImmutable::now('America/Lima');
        $current_month = $current_date->format('m');
        $current_year = $current_date->format('Y');
        //dump($current_date);
        //dump($current_month);
        //dump($current_year);
        $arrayMonths = [];
        $arrayYears = [];
        $arrayMonthsNames = [];
        for ( $i = 0; $i<=6; $i++ )
        {
            if ( (int)$current_month - $i <= 0 )
            {
                $mes = (int)$current_month - $i + 12;
                array_push($arrayMonths, (int)$mes);
                array_push($arrayYears, $current_year - 1);
                //array_push($arrayMonthsNames, $meses[((int)$mes) - 1] . ' ' . $current_year - 1);

            } else {
                array_push($arrayYears, $current_year);
                array_push($arrayMonths, (int)$current_month - $i);
                //array_push($arrayMonthsNames, $meses[((int)$current_month - $i) - 1] . ' ' . $current_year);
            }
        }

        for ( $j = 0; $j < count($arrayMonths); $j++ )
        {
            array_push($arrayMonthsNames, $meses[(int)$arrayMonths[$j] - 1].' '.(int)$arrayYears[$j]);
        }
        //dump($arrayMonths);
        //dump($arrayMonthsNames);
        $total_dollars = 0;
        $total_soles = 0;

        $total_quantity = 0;
        $dollars_quantity = 0;
        $soles_quantity = 0;

        $amounts_dollars = [];
        $amounts_soles = [];

        for ( $i=0; $i<count($arrayMonths); $i++ )
        {
            $quotes = Quote::whereNotIn('state', ['expired', 'canceled'])
                ->where('raise_status', 1)
                ->whereMonth('date_quote', $arrayMonths[$i])
                ->whereYear('date_quote', $arrayYears[$i])
                ->get();

            $total_quantity += $quotes->count();

            foreach ( $quotes as $quote )
            {
                if ($quote->currency_invoice === 'PEN')
                {
                    //dump((float) $quote->subtotal_rent);
                    $total_soles += (float) $quote->subtotal_rent;
                    $soles_quantity += 1;
                } else {
                    //dump((float) $quote->subtotal_rent);
                    $total_dollars += (float) $quote->subtotal_rent;
                    $dollars_quantity += 1;
                }
            }

            array_push($amounts_dollars, $total_dollars);
            array_push($amounts_soles, $total_soles);

            $total_dollars = 0;
            $total_soles = 0;
        }
        //dump($amounts_dollars);
        //dump($amounts_soles);
        $months = array_reverse($arrayMonths);
        $monthsNames = array_reverse($arrayMonthsNames);
        $dollars = array_reverse($amounts_dollars);
        $soles = array_reverse($amounts_soles);
        //dump($months);
        //dump($monthsNames);
        //dump($dollars);
        //dump($soles);

        $percentage_dollars = round((($dollars_quantity/$total_quantity)*100), 0);
        $percentage_soles = round((($soles_quantity/$total_quantity)*100), 0);

        $sum_dollars = array_sum($dollars);
        $sum_soles = array_sum($soles);

        return response()->json([
            'months' => $months,
            'monthsNames' => $monthsNames,
            'dollars' => $dollars,
            'soles' => $soles,
            'percentage_dollars' => $percentage_dollars,
            'percentage_soles' => $percentage_soles,
            'sum_dollars' => $sum_dollars,
            'sum_soles' => $sum_soles
        ]);

    }

    public function chartQuotesDollarsSolesView( $dateStart, $dateEnd )
    {
        $date_start = Carbon::parse($dateStart);
        $date_end = Carbon::parse($dateEnd);
        //dump($dateStart);
        //dump($dateEnd);
        //dump($date_start);
        //dump($date_end);
        $meses = array("ENE","FEB","MAR","ABR","MAY","JUN","JUL","AGO","SEP","OCT","NOV","DIC");

        $year_start = (int)$date_start->format('Y');
        $month_start = (int)$date_start->format('m');
        $day_start = 1;
        $tz = 'America/Lima';

        $start_date = Carbon::createFromDate($year_start, $month_start, $day_start, $tz);

        $year_end = (int)$date_end->format('Y');
        $month_end = (int)$date_end->format('m');
        $day_end = 1;

        $endDate = Carbon::createFromDate($year_end, $month_end, $day_end, $tz);
        $lastDayofMonth = $endDate->endOfMonth()->toDateString();
        $end_date = Carbon::createFromFormat('Y-m-d', $lastDayofMonth);
        //dump($start_date);
        //dump($end_date);

        $arrayMonths = [];
        $arrayYears = [];
        $arrayMonthsNames = [];
        while ($start_date < $end_date) {
            array_push($arrayMonths, (int)$start_date->format('m'));
            array_push($arrayYears, (int)$start_date->format('Y'));
            $start_date->addMonth();
        }

        for ( $j = 0; $j < count($arrayMonths); $j++ )
        {
            array_push($arrayMonthsNames, $meses[(int)$arrayMonths[$j] - 1].' '.(int)$arrayYears[$j]);
        }

        //dump($arrayMonths);
        //dump($arrayYears);
        //dump($arrayMonthsNames);

        $total_quantity = 0;

        $total_soles = 0;
        $soles_quantity = 0;
        $total_dollars = 0;
        $dollars_quantity = 0;

        $amounts_dollars = [];
        $amounts_soles = [];

        for ( $i=0; $i<count($arrayMonths); $i++ )
        {
            $quotes = Quote::whereNotIn('state', ['expired', 'canceled'])
                ->where('raise_status', 1)
                ->whereMonth('date_quote', $arrayMonths[$i])
                ->whereYear('date_quote', $arrayYears[$i])
                ->get();

            $total_quantity += $quotes->count();

            foreach ( $quotes as $quote )
            {
                if ($quote->currency_invoice === 'PEN')
                {
                    //dump((float) $quote->subtotal_rent);
                    $total_soles += (float) $quote->subtotal_rent;
                    $soles_quantity += 1;
                } else {
                    //dump((float) $quote->subtotal_rent);
                    $total_dollars += (float) $quote->subtotal_rent;
                    $dollars_quantity += 1;
                }
            }

            array_push($amounts_dollars, $total_dollars);
            array_push($amounts_soles, $total_soles);

            $total_dollars = 0;
            $total_soles = 0;
        }
        //dump($amounts_dollars);
        //dump($amounts_soles);
        $months = array_reverse($arrayMonths);
        $monthsNames = array_reverse($arrayMonthsNames);
        $dollars = array_reverse($amounts_dollars);
        $soles = array_reverse($amounts_soles);


        $percentage_dollars = round((($dollars_quantity/$total_quantity)*100), 0);
        $percentage_soles = round((($soles_quantity/$total_quantity)*100), 0);

        $sum_dollars = array_sum($dollars);
        $sum_soles = array_sum($soles);

        //dump($arrayMonths);
        //dump($arrayYears);
        //dump($monthsNames);
        //dump($dollars);
        //dump($soles);
        //dump($percentage_dollars);
        //dump($percentage_soles);
        //dump($sum_dollars);
        //dump($sum_soles);

        return response()->json([
            'months' => $arrayMonths,
            'monthsNames' => $arrayMonthsNames,
            'dollars' => $amounts_dollars,
            'soles' => $amounts_soles,
            'percentage_dollars' => $percentage_dollars,
            'percentage_soles' => $percentage_soles,
            'sum_dollars' => $sum_dollars,
            'sum_soles' => $sum_soles
        ]);

    }

    public function chartExpensesIncomeDollarsSoles()
    {
        $meses = array("ENE","FEB","MAR","ABR","MAY","JUN","JUL","AGO","SEP","OCT","NOV","DIC");

        $current_date = CarbonImmutable::now('America/Lima');
        $current_month = $current_date->format('m');
        $current_year = $current_date->format('Y');
        //dump($current_date);
        //dump($current_month);
        //dump($current_year);
        $arrayMonths = [];
        $arrayYears = [];
        $arrayMonthsNames = [];
        for ( $i = 0; $i<=6; $i++ )
        {
            if ( (int)$current_month - $i <= 0 )
            {
                $mes = (int)$current_month - $i + 12;
                array_push($arrayMonths, (int)$mes);
                array_push($arrayYears, $current_year - 1);
                //array_push($arrayMonthsNames, $meses[((int)$mes) - 1] . ' ' . $current_year - 1);

            } else {
                array_push($arrayYears, $current_year);
                array_push($arrayMonths, (int)$current_month - $i);
                //array_push($arrayMonthsNames, $meses[((int)$current_month - $i) - 1] . ' ' . $current_year);
            }
        }
        for ( $j = 0; $j < count($arrayMonths); $j++ )
        {
            array_push($arrayMonthsNames, $meses[(int)$arrayMonths[$j] - 1].' '.(int)$arrayYears[$j]);
        }
        //dump($arrayMonths);
        //dump($arrayMonthsNames);
        $income_dollars = 0;
        $income_soles = 0;
        $income_mix = 0;

        $income_array_dollars = [];
        $income_array_soles = [];
        $income_array_mix = [];

        // Ingresos
        for ( $i=0; $i<count($arrayMonths); $i++ )
        {
            $quotes = Quote::whereNotIn('state', ['expired', 'canceled'])
                ->where('raise_status', 1)
                ->whereMonth('date_quote', $arrayMonths[$i])
                ->whereYear('date_quote', $arrayYears[$i])
                ->get();

            foreach ( $quotes as $quote )
            {
                if ($quote->currency_invoice === 'PEN')
                {
                    //dump((float) $quote->subtotal_rent);
                    $income_soles += (float) $quote->subtotal_rent;
                    $subtotal1 = $quote->total * (($quote->utility/100)+1);
                    $subtotal2 = $subtotal1 * (($quote->letter/100)+1);
                    $subtotal3 = $subtotal2 * (($quote->rent/100)+1);
                    $income_mix += (float) $subtotal3;
                } else {
                    //dump((float) $quote->subtotal_rent);
                    $income_dollars += (float) $quote->subtotal_rent;
                    $income_mix += (float) $quote->subtotal_rent;
                }
            }

            array_push($income_array_dollars, $income_dollars);
            array_push($income_array_soles, $income_soles);
            array_push($income_array_mix, $income_mix);
            $income_dollars = 0;
            $income_soles = 0;
            $income_mix = 0;
        }

        // Egresos
        $expense_soles = 0;
        $expense_dollars = 0;
        $expense_mix = 0;

        $expense_array_dollars = [];
        $expense_array_soles = [];
        $expense_array_mix = [];

        for ( $i=0; $i<count($arrayMonths); $i++ )
        {
            $entries = Entry::with('details')
                ->whereMonth('date_entry', $arrayMonths[$i])
                ->whereYear('date_entry', $arrayYears[$i])
                ->get();

            foreach ( $entries as $entry )
            {
                if ($entry->currency_invoice === 'PEN')
                {
                    foreach ( $entry->details as $detail )
                    {
                        $expense_soles = $expense_soles + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                        $expense_mix = $expense_mix + (((float)$detail->entered_quantity * (float)$detail->unit_price)/(float)$entry->currency_compra);
                    }

                } else {
                    foreach ( $entry->details as $detail )
                    {
                        $expense_dollars = $expense_dollars + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                        $expense_mix = $expense_mix + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                    }
                }
            }

            array_push($expense_array_dollars, round($expense_dollars,2));
            array_push($expense_array_soles, round($expense_soles,2));
            array_push($expense_array_mix, round($expense_mix,2));

            $expense_soles = 0;
            $expense_dollars = 0;
            $expense_mix = 0;
        }
        //dump($amounts_dollars);
        //dump($amounts_soles);
        $months = array_reverse($arrayMonths);
        $monthsNames = array_reverse($arrayMonthsNames);
        $dollars_income = array_reverse($income_array_dollars);
        $dollars_expense = array_reverse($expense_array_dollars);
        $soles_income = array_reverse($income_array_soles);
        $soles_expense = array_reverse($expense_array_soles);
        $mix_income = array_reverse($income_array_mix);
        $mix_expense = array_reverse($expense_array_mix);

        $sum_dollars_income = array_sum($dollars_income);
        $sum_dollars_expense = array_sum($dollars_expense);

        $sum_soles_income = array_sum($soles_income);
        $sum_soles_expense = array_sum($soles_expense);

        $sum_mix_income = array_sum($mix_income);
        $sum_mix_expense = array_sum($mix_expense);

        $percentage_dollars = round($sum_dollars_income - $sum_dollars_expense, 2);
        $percentage_soles = round($sum_soles_income - $sum_soles_expense, 2);
        $percentage_mix = round($sum_mix_income - $sum_mix_expense, 2);

        /*dump($monthsNames);
        dump($dollars_income);
        dump($dollars_expense);
        dump($soles_income);
        dump($soles_expense);
        dump($percentage_dollars);
        dump($percentage_soles);
        dump($mix_income);
        dump($mix_expense);
        dump($percentage_mix);*/

        return response()->json([
            'monthsNames' => $monthsNames,
            'income_dollars' => $dollars_income,
            'income_soles' => $soles_income,
            'expense_dollars' => $dollars_expense,
            'expense_soles' => $soles_expense,
            'percentage_dollars' => $percentage_dollars,
            'percentage_soles' => $percentage_soles,
            'sum_dollars_income' => $sum_dollars_income,
            'sum_soles_income' => $sum_soles_income,
            'sum_dollars_expense' => $sum_dollars_expense,
            'sum_soles_expense' => $sum_soles_expense,
            'mix_income' => $mix_income,
            'mix_expense' => $mix_expense,
            'percentage_mix' => $percentage_mix,
        ]);

    }

    public function chartExpensesIncomeDollarsSolesView( $dateStart, $dateEnd )
    {
        $date_start = Carbon::parse($dateStart);
        $date_end = Carbon::parse($dateEnd);

        $meses = array("ENE","FEB","MAR","ABR","MAY","JUN","JUL","AGO","SEP","OCT","NOV","DIC");

        $year_start = (int)$date_start->format('Y');
        $month_start = (int)$date_start->format('m');
        $day_start = 1;
        $tz = 'America/Lima';

        $start_date = Carbon::createFromDate($year_start, $month_start, $day_start, $tz);

        $year_end = (int)$date_end->format('Y');
        $month_end = (int)$date_end->format('m');
        $day_end = 1;

        $endDate = Carbon::createFromDate($year_end, $month_end, $day_end, $tz);
        $lastDayofMonth = $endDate->endOfMonth()->toDateString();
        $end_date = Carbon::createFromFormat('Y-m-d', $lastDayofMonth);
        //dump($start_date);
        //dump($end_date);

        $arrayMonths = [];
        $arrayYears = [];
        $arrayMonthsNames = [];
        while ($start_date < $end_date) {
            array_push($arrayMonths, (int)$start_date->format('m'));
            array_push($arrayYears, (int)$start_date->format('Y'));
            $start_date->addMonth();
        }

        for ( $j = 0; $j < count($arrayMonths); $j++ )
        {
            array_push($arrayMonthsNames, $meses[(int)$arrayMonths[$j] - 1].' '.(int)$arrayYears[$j]);
        }

        //dump($arrayMonths);
        //dump($arrayYears);
        //dump($arrayMonthsNames);

        $income_dollars = 0;
        $income_soles = 0;
        $income_mix = 0;

        $income_array_dollars = [];
        $income_array_soles = [];
        $income_array_mix = [];

        // Ingresos
        for ( $i=0; $i<count($arrayMonths); $i++ )
        {
            $quotes = Quote::whereNotIn('state', ['expired', 'canceled'])
                ->where('raise_status', 1)
                ->whereMonth('date_quote', $arrayMonths[$i])
                ->whereYear('date_quote', $arrayYears[$i])
                ->get();

            foreach ( $quotes as $quote )
            {
                if ($quote->currency_invoice === 'PEN')
                {
                    //dump((float) $quote->subtotal_rent);
                    $income_soles += (float) $quote->subtotal_rent;
                    $subtotal1 = $quote->total * (($quote->utility/100)+1);
                    $subtotal2 = $subtotal1 * (($quote->letter/100)+1);
                    $subtotal3 = $subtotal2 * (($quote->rent/100)+1);
                    $income_mix += (float) $subtotal3;
                } else {
                    //dump((float) $quote->subtotal_rent);
                    $income_dollars += (float) $quote->subtotal_rent;
                    $income_mix += (float) $quote->subtotal_rent;
                }
            }

            array_push($income_array_dollars, $income_dollars);
            array_push($income_array_soles, $income_soles);
            array_push($income_array_mix, $income_mix);
            $income_dollars = 0;
            $income_soles = 0;
            $income_mix = 0;
        }

        // Egresos
        $expense_soles = 0;
        $expense_dollars = 0;
        $expense_mix = 0;

        $expense_array_dollars = [];
        $expense_array_soles = [];
        $expense_array_mix = [];

        for ( $i=0; $i<count($arrayMonths); $i++ )
        {
            $entries = Entry::with('details')
                ->whereMonth('date_entry', $arrayMonths[$i])
                ->whereYear('date_entry', $arrayYears[$i])
                ->get();

            foreach ( $entries as $entry )
            {
                if ($entry->currency_invoice === 'PEN')
                {
                    foreach ( $entry->details as $detail )
                    {
                        $expense_soles = $expense_soles + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                        $expense_mix = $expense_mix + (((float)$detail->entered_quantity * (float)$detail->unit_price)/(float)$entry->currency_compra);
                    }

                } else {
                    foreach ( $entry->details as $detail )
                    {
                        $expense_dollars = $expense_dollars + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                        $expense_mix = $expense_mix + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                    }
                }
            }

            array_push($expense_array_dollars, round($expense_dollars,2));
            array_push($expense_array_soles, round($expense_soles,2));
            array_push($expense_array_mix, round($expense_mix,2));

            $expense_soles = 0;
            $expense_dollars = 0;
            $expense_mix = 0;
        }
        //dump($amounts_dollars);
        //dump($amounts_soles);
        $months = array_reverse($arrayMonths);
        $monthsNames = array_reverse($arrayMonthsNames);
        $dollars_income = array_reverse($income_array_dollars);
        $dollars_expense = array_reverse($expense_array_dollars);
        $soles_income = array_reverse($income_array_soles);
        $soles_expense = array_reverse($expense_array_soles);
        $mix_income = array_reverse($income_array_mix);
        $mix_expense = array_reverse($expense_array_mix);

        $sum_dollars_income = array_sum($dollars_income);
        $sum_dollars_expense = array_sum($dollars_expense);

        $sum_soles_income = array_sum($soles_income);
        $sum_soles_expense = array_sum($soles_expense);

        $sum_mix_income = array_sum($mix_income);
        $sum_mix_expense = array_sum($mix_expense);

        $percentage_dollars = round($sum_dollars_income - $sum_dollars_expense, 2);
        $percentage_soles = round($sum_soles_income - $sum_soles_expense, 2);
        $percentage_mix = round($sum_mix_income - $sum_mix_expense, 2);

        /*dump($monthsNames);
        dump($dollars_income);
        dump($dollars_expense);
        dump($soles_income);
        dump($soles_expense);
        dump($percentage_dollars);
        dump($percentage_soles);
        dump($mix_income);
        dump($mix_expense);
        dump($percentage_mix);*/

        return response()->json([
            'monthsNames' => $monthsNames,
            'income_dollars' => $dollars_income,
            'income_soles' => $soles_income,
            'expense_dollars' => $dollars_expense,
            'expense_soles' => $soles_expense,
            'percentage_dollars' => $percentage_dollars,
            'percentage_soles' => $percentage_soles,
            'sum_dollars_income' => $sum_dollars_income,
            'sum_soles_income' => $sum_soles_income,
            'sum_dollars_expense' => $sum_dollars_expense,
            'sum_soles_expense' => $sum_soles_expense,
            'mix_income' => $mix_income,
            'mix_expense' => $mix_expense,
            'percentage_mix' => $percentage_mix,
        ]);

    }

    public function chartUtilitiesDollarsSoles()
    {
        $meses = array("ENE","FEB","MAR","ABR","MAY","JUN","JUL","AGO","SEP","OCT","NOV","DIC");

        $current_date = CarbonImmutable::now('America/Lima');
        $current_month = $current_date->format('m');
        $current_year = $current_date->format('Y');
        //dump($current_date);
        //dump($current_month);
        //dump($current_year);
        $arrayMonths = [];
        $arrayYears = [];
        $arrayMonthsNames = [];
        for ( $i = 0; $i<=6; $i++ )
        {
            if ( (int)$current_month - $i <= 0 )
            {
                $mes = (int)$current_month - $i + 12;
                array_push($arrayMonths, (int)$mes);
                array_push($arrayYears, $current_year - 1);
                //array_push($arrayMonthsNames, $meses[((int)$mes) - 1] . ' ' . $current_year - 1);

            } else {
                array_push($arrayYears, $current_year);
                array_push($arrayMonths, (int)$current_month - $i);
                //array_push($arrayMonthsNames, $meses[((int)$current_month - $i) - 1] . ' ' . $current_year);
            }
        }
        for ( $j = 0; $j < count($arrayMonths); $j++ )
        {
            array_push($arrayMonthsNames, $meses[(int)$arrayMonths[$j] - 1].' '.(int)$arrayYears[$j]);
        }
        //dump($arrayMonths);
        //dump($arrayMonthsNames);
        $income_dollars = 0;
        $income_soles = 0;
        $income_mix = 0;

        $income_array_dollars = [];
        $income_array_soles = [];
        $income_array_mix = [];

        // Ingresos
        for ( $i=0; $i<count($arrayMonths); $i++ )
        {
            $quotes = Quote::whereNotIn('state', ['expired', 'canceled'])
                ->where('raise_status', 1)
                ->whereMonth('date_quote', $arrayMonths[$i])
                ->whereYear('date_quote', $arrayYears[$i])
                ->get();

            foreach ( $quotes as $quote )
            {
                if ($quote->currency_invoice === 'PEN')
                {
                    //dump((float) $quote->subtotal_rent);
                    $income_soles += (float) $quote->subtotal_rent;
                    $subtotal1 = $quote->total * (($quote->utility/100)+1);
                    $subtotal2 = $subtotal1 * (($quote->letter/100)+1);
                    $subtotal3 = $subtotal2 * (($quote->rent/100)+1);
                    $income_mix += (float) $subtotal3;
                } else {
                    //dump((float) $quote->subtotal_rent);
                    $income_dollars += (float) $quote->subtotal_rent;
                    $income_mix += (float) $quote->subtotal_rent;
                }
            }

            array_push($income_array_dollars, $income_dollars);
            array_push($income_array_soles, $income_soles);
            array_push($income_array_mix, $income_mix);
            $income_dollars = 0;
            $income_soles = 0;
            $income_mix = 0;
        }

        // Egresos
        $expense_soles = 0;
        $expense_dollars = 0;
        $expense_mix = 0;

        $expense_array_dollars = [];
        $expense_array_soles = [];
        $expense_array_mix = [];

        for ( $i=0; $i<count($arrayMonths); $i++ )
        {
            $entries = Entry::with('details')
                ->whereMonth('date_entry', $arrayMonths[$i])
                ->whereYear('date_entry', $arrayYears[$i])
                ->get();

            foreach ( $entries as $entry )
            {
                if ($entry->currency_invoice === 'PEN')
                {
                    foreach ( $entry->details as $detail )
                    {
                        $expense_soles = $expense_soles + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                        $expense_mix = $expense_mix + (((float)$detail->entered_quantity * (float)$detail->unit_price)/(float)$entry->currency_compra);
                    }

                } else {
                    foreach ( $entry->details as $detail )
                    {
                        $expense_dollars = $expense_dollars + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                        $expense_mix = $expense_mix + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                    }
                }
            }

            array_push($expense_array_dollars, round($expense_dollars,2));
            array_push($expense_array_soles, round($expense_soles,2));
            array_push($expense_array_mix, round($expense_mix,2));

            $expense_soles = 0;
            $expense_dollars = 0;
            $expense_mix = 0;
        }
        //dump($amounts_dollars);
        //dump($amounts_soles);
        $months = array_reverse($arrayMonths);
        $monthsNames = array_reverse($arrayMonthsNames);
        $dollars_income = array_reverse($income_array_dollars);
        $dollars_expense = array_reverse($expense_array_dollars);
        $soles_income = array_reverse($income_array_soles);
        $soles_expense = array_reverse($expense_array_soles);
        $mix_income = array_reverse($income_array_mix);
        $mix_expense = array_reverse($expense_array_mix);

        $utilities_dollars = [];
        if ( count($dollars_income) == count($dollars_expense) )
        {
            for ($i=0; $i < count($dollars_income); $i++)
                array_push($utilities_dollars, ($dollars_income[$i] - $dollars_expense[$i]) );
        }

        $utilities_soles = [];
        if ( count($soles_income) == count($soles_expense) )
        {
            for ($i=0; $i < count($soles_income); $i++)
                array_push($utilities_soles, ($soles_income[$i] - $soles_expense[$i]) );
        }

        $utilities_mix = [];
        if ( count($mix_income) == count($mix_expense) )
        {
            for ($i=0; $i < count($mix_income); $i++)
                array_push($utilities_mix, ($mix_income[$i] - $mix_expense[$i]) );
        }

        $sum_dollars_income = array_sum($dollars_income);
        $sum_dollars_expense = array_sum($dollars_expense);

        $sum_soles_income = array_sum($soles_income);
        $sum_soles_expense = array_sum($soles_expense);

        $sum_mix_income = array_sum($mix_income);
        $sum_mix_expense = array_sum($mix_expense);

        $percentage_dollars = round($sum_dollars_income - $sum_dollars_expense, 2);
        $percentage_soles = round($sum_soles_income - $sum_soles_expense, 2);
        $percentage_mix = round($sum_mix_income - $sum_mix_expense, 2);

        $sum_utilities_dollars = array_sum($utilities_dollars);
        $sum_utilities_soles = array_sum($utilities_soles);
        $sum_utilities_mix = array_sum($utilities_mix);
        /*dump($monthsNames);
        dump($dollars_income);
        dump($dollars_expense);
        dump($soles_income);
        dump($soles_expense);
        dump($percentage_dollars);
        dump($percentage_soles);
        dump($mix_income);
        dump($mix_expense);
        dump($percentage_mix);*/

        return response()->json([
            'monthsNames' => $monthsNames,
            'utilities_dollars' => $utilities_dollars,
            'utilities_soles' => $utilities_soles,
            'utilities_mix' => $utilities_mix,
            'sum_utilities_dollars' => $sum_utilities_dollars,
            'sum_utilities_soles' => $sum_utilities_soles,
            'sum_utilities_mix' => $sum_utilities_mix,
        ]);

    }

    public function chartUtilitiesDollarsSolesView( $dateStart, $dateEnd )
    {
        $date_start = Carbon::parse($dateStart);
        $date_end = Carbon::parse($dateEnd);

        $meses = array("ENE","FEB","MAR","ABR","MAY","JUN","JUL","AGO","SEP","OCT","NOV","DIC");

        $year_start = (int)$date_start->format('Y');
        $month_start = (int)$date_start->format('m');
        $day_start = 1;
        $tz = 'America/Lima';

        $start_date = Carbon::createFromDate($year_start, $month_start, $day_start, $tz);

        $year_end = (int)$date_end->format('Y');
        $month_end = (int)$date_end->format('m');
        $day_end = 1;

        $endDate = Carbon::createFromDate($year_end, $month_end, $day_end, $tz);
        $lastDayofMonth = $endDate->endOfMonth()->toDateString();
        $end_date = Carbon::createFromFormat('Y-m-d', $lastDayofMonth);
        //dump($start_date);
        //dump($end_date);

        $arrayMonths = [];
        $arrayYears = [];
        $arrayMonthsNames = [];
        while ($start_date < $end_date) {
            array_push($arrayMonths, (int)$start_date->format('m'));
            array_push($arrayYears, (int)$start_date->format('Y'));
            $start_date->addMonth();
        }

        for ( $j = 0; $j < count($arrayMonths); $j++ )
        {
            array_push($arrayMonthsNames, $meses[(int)$arrayMonths[$j] - 1].' '.(int)$arrayYears[$j]);
        }

        //dump($arrayMonths);
        //dump($arrayYears);
        //dump($arrayMonthsNames);

        $income_dollars = 0;
        $income_soles = 0;
        $income_mix = 0;

        $income_array_dollars = [];
        $income_array_soles = [];
        $income_array_mix = [];

        // Ingresos
        for ( $i=0; $i<count($arrayMonths); $i++ )
        {
            $quotes = Quote::whereNotIn('state', ['expired', 'canceled'])
                ->where('raise_status', 1)
                ->whereMonth('date_quote', $arrayMonths[$i])
                ->whereYear('date_quote', $arrayYears[$i])
                ->get();

            foreach ( $quotes as $quote )
            {
                if ($quote->currency_invoice === 'PEN')
                {
                    //dump((float) $quote->subtotal_rent);
                    $income_soles += (float) $quote->subtotal_rent;
                    $subtotal1 = $quote->total * (($quote->utility/100)+1);
                    $subtotal2 = $subtotal1 * (($quote->letter/100)+1);
                    $subtotal3 = $subtotal2 * (($quote->rent/100)+1);
                    $income_mix += (float) $subtotal3;
                } else {
                    //dump((float) $quote->subtotal_rent);
                    $income_dollars += (float) $quote->subtotal_rent;
                    $income_mix += (float) $quote->subtotal_rent;
                }
            }

            array_push($income_array_dollars, $income_dollars);
            array_push($income_array_soles, $income_soles);
            array_push($income_array_mix, $income_mix);
            $income_dollars = 0;
            $income_soles = 0;
            $income_mix = 0;
        }

        // Egresos
        $expense_soles = 0;
        $expense_dollars = 0;
        $expense_mix = 0;

        $expense_array_dollars = [];
        $expense_array_soles = [];
        $expense_array_mix = [];

        for ( $i=0; $i<count($arrayMonths); $i++ )
        {
            $entries = Entry::with('details')
                ->whereMonth('date_entry', $arrayMonths[$i])
                ->whereYear('date_entry', $arrayYears[$i])
                ->get();

            foreach ( $entries as $entry )
            {
                if ($entry->currency_invoice === 'PEN')
                {
                    foreach ( $entry->details as $detail )
                    {
                        $expense_soles = $expense_soles + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                        $expense_mix = $expense_mix + (((float)$detail->entered_quantity * (float)$detail->unit_price)/(float)$entry->currency_compra);
                    }

                } else {
                    foreach ( $entry->details as $detail )
                    {
                        $expense_dollars = $expense_dollars + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                        $expense_mix = $expense_mix + ((float)$detail->entered_quantity * (float)$detail->unit_price);
                    }
                }
            }

            array_push($expense_array_dollars, round($expense_dollars,2));
            array_push($expense_array_soles, round($expense_soles,2));
            array_push($expense_array_mix, round($expense_mix,2));

            $expense_soles = 0;
            $expense_dollars = 0;
            $expense_mix = 0;
        }
        //dump($amounts_dollars);
        //dump($amounts_soles);


        $months = array_reverse($arrayMonths);
        $monthsNames = array_reverse($arrayMonthsNames);
        $dollars_income = array_reverse($income_array_dollars);
        $dollars_expense = array_reverse($expense_array_dollars);
        $soles_income = array_reverse($income_array_soles);
        $soles_expense = array_reverse($expense_array_soles);
        $mix_income = array_reverse($income_array_mix);
        $mix_expense = array_reverse($expense_array_mix);

        $sum_dollars_income = array_sum($dollars_income);
        $sum_dollars_expense = array_sum($dollars_expense);

        $sum_soles_income = array_sum($soles_income);
        $sum_soles_expense = array_sum($soles_expense);

        $sum_mix_income = array_sum($mix_income);
        $sum_mix_expense = array_sum($mix_expense);

        $utilities_dollars = [];
        if ( count($dollars_income) == count($dollars_expense) )
        {
            for ($i=0; $i < count($dollars_income); $i++)
                array_push($utilities_dollars, ($dollars_income[$i] - $dollars_expense[$i]) );
        }

        $utilities_soles = [];
        if ( count($soles_income) == count($soles_expense) )
        {
            for ($i=0; $i < count($soles_income); $i++)
                array_push($utilities_soles, ($soles_income[$i] - $soles_expense[$i]) );
        }

        $utilities_mix = [];
        if ( count($mix_income) == count($mix_expense) )
        {
            for ($i=0; $i < count($mix_income); $i++)
                array_push($utilities_mix, ($mix_income[$i] - $mix_expense[$i]) );
        }

        $sum_utilities_dollars = array_sum($utilities_dollars);
        $sum_utilities_soles = array_sum($utilities_soles);
        $sum_utilities_mix = array_sum($utilities_mix);

        $percentage_dollars = round($sum_dollars_income - $sum_dollars_expense, 2);
        $percentage_soles = round($sum_soles_income - $sum_soles_expense, 2);
        $percentage_mix = round($sum_mix_income - $sum_mix_expense, 2);

        /*dump($monthsNames);
        dump($dollars_income);
        dump($dollars_expense);
        dump($soles_income);
        dump($soles_expense);
        dump($percentage_dollars);
        dump($percentage_soles);
        dump($mix_income);
        dump($mix_expense);
        dump($percentage_mix);*/

        return response()->json([
            'monthsNames' => $monthsNames,
            'utilities_dollars' => $utilities_dollars,
            'utilities_soles' => $utilities_soles,
            'utilities_mix' => $utilities_mix,
            'sum_utilities_dollars' => $sum_utilities_dollars,
            'sum_utilities_soles' => $sum_utilities_soles,
            'sum_utilities_mix' => $sum_utilities_mix,
        ]);

    }

    public function quotesReport()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('report.reportQuote', compact( 'permissions'));
    }

    public function quoteSummaryReport()
    {
        $quotes = Quote::with(['customer'])
            ->where('state_active','open')
            ->where('state','confirmed')
            ->where('raise_status',1)
            ->get();

        $array = [];

        foreach ( $quotes as $quote )
        {
            $codigo = $quote->code;
            $descripcion = $quote->description_quote;
            $monto_materiales = 0;
            $monto_consumibles = 0;
            $monto_servicios_varios = 0;
            $monto_servicios_adicionales = 0;
            $monto_dias_trabajo = 0;
            $subtotal = round((float)$quote->total, 2);
            $utilidad = $quote->utility;
            $renta = $quote->rent;
            $letra = $quote->letter;
            $pago_cliente = round((float)$quote->subtotal_rent_pdf, 2);

            foreach( $quote->equipments as $equipment )
            {
                foreach ( $equipment->materials as $material  )
                {
                    $monto_materiales += ($material->price * $material->quantity);
                }

                foreach ( $equipment->consumables as $consumable  )
                {
                    $monto_consumibles += ($consumable->price * $consumable->quantity);
                }

                foreach ( $equipment->workforces as $workforce  )
                {
                    $monto_servicios_varios += ($workforce->price * $workforce->quantity);
                }

                foreach ( $equipment->turnstiles as $turnstile  )
                {
                    $monto_servicios_adicionales += ($turnstile->price * $turnstile->quantity);
                }

                foreach ( $equipment->workdays as $workday  )
                {
                    $monto_dias_trabajo += ($workday->total);
                }
            }

            $adicionales = 0;
            $outputs = Output::with('details')
                ->where('execution_order', $quote->order_execution)
                ->where('indicator', 'ore')
                ->get();
            foreach ( $outputs as $output )
            {
                foreach ( $output->details as $detail )
                {
                    $item = Item::where('id',$detail->item_id)->first();
                    $adicionales += ($item->price);
                }
            }

            $costo_real = round($subtotal + $adicionales, 2);
            $diferencia_neta = round($pago_cliente - $costo_real, 2);

            array_push(
                $array,
                [
                    'codigo' => $codigo,
                    'descripcion' => $descripcion,
                    'monto_materiales' => round($monto_materiales,2),
                    'monto_consumibles' => round($monto_consumibles,2,2),
                    'monto_servicios_varios' => round($monto_servicios_varios,2),
                    'monto_servicios_adicionales' => round($monto_servicios_adicionales,2),
                    'monto_dias_trabajo' => round($monto_dias_trabajo,2),
                    'subtotal' => $subtotal,
                    'utilidad' => $utilidad,
                    'renta' => $renta,
                    'letra' => $letra,
                    'pago_cliente' => $pago_cliente,
                    'adicionales' => $adicionales,
                    'costo_real' => $costo_real,
                    'diferencia_neta' => $diferencia_neta
                ]
            );

        }

        return Excel::download(new QuoteSummaryExport($array), 'reporte_cotizaciones_resumido.xlsx');

    }

    public function quoteIndividualReport( $id )
    {
        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with(['equipments' => function ($query) {
                $query->with(['materials', 'consumables', 'workforces', 'turnstiles']);
            }])->first();

        $array = [];

        $codigo = $quote->code;
        $descripcion = $quote->description_quote;
        $materiales = [];
        $consumables = [];
        $servicios_varios = [];
        $servicios_adicionales = [];
        $dias_trabajo = [];
        $monto_materiales = 0;
        $monto_consumibles = 0;
        $monto_servicios_varios = 0;
        $monto_servicios_adicionales = 0;
        $monto_dias_trabajo = 0;
        $utilidad = $quote->utility;
        $renta = $quote->rent;
        $letra = $quote->letter;
        foreach( $quote->equipments as $equipment )
        {
            foreach ( $equipment->materials as $material  )
            {
                array_push($materiales,[
                    'codigo' => $material->material->code,
                    'nombre_total' => $material->material->full_description,
                    'cantidad' => $material->quantity,
                    'monto' => round(($material->price * $material->quantity), 2)
                ]);
                $monto_materiales += ($material->price * $material->quantity);

            }

            foreach ( $equipment->consumables as $consumable  )
            {
                array_push($consumables,[
                    'nombre_total' => $consumable->material->full_description,
                    'cantidad' => $consumable->quantity,
                    'monto' => round(($consumable->price * $consumable->quantity), 2)
                ]);
                $monto_consumibles += ($consumable->price * $consumable->quantity);

            }

            foreach ( $equipment->workforces as $workforce  )
            {
                array_push($servicios_varios,[
                    'nombre_total' => $workforce->description,
                    'cantidad' => $workforce->quantity,
                    'monto' => round(($workforce->price * $workforce->quantity), 2)
                ]);
                $monto_servicios_varios += ($workforce->price * $workforce->quantity);

            }

            foreach ( $equipment->turnstiles as $turnstile  )
            {
                array_push($servicios_adicionales,[
                    'nombre_total' => $turnstile->description,
                    'cantidad' => $turnstile->quantity,
                    'monto' => round(($turnstile->price * $turnstile->quantity), 2)
                ]);
                $monto_servicios_adicionales += ($turnstile->price * $turnstile->quantity);

            }

            foreach ( $equipment->workdays as $workday  )
            {
                array_push($dias_trabajo,[
                    'nombre_total' => $workday->description,
                    'cantidad' => $workday->quantityPerson,
                    'monto' => round(($workday->total), 2)
                ]);
                $monto_dias_trabajo += ($workday->total);
            }
        }

        $adicionales = 0;
        $materiales_adicionales = [];
        $outputs = Output::with('details')
            ->where('execution_order', $quote->order_execution)
            ->where('indicator', 'ore')
            ->get();
        foreach ($outputs as $output) {
            foreach ($output->details as $detail) {
                $item = Item::where('id', $detail->item_id)->first();
                array_push($materiales_adicionales,[
                    'nombre_total' => $item->material->full_description,
                    'cantidad' => $item->percentage,
                    'monto' => $item->price
                ]);
                $adicionales += $item->price;
            }
        }
        $subtotal = round((float)$quote->total, 2);
        $costo_real = round($subtotal + $adicionales, 2);
        $pago_cliente = round((float)$quote->subtotal_rent_pdf, 2);
        $diferencia_neta = round($pago_cliente - $costo_real, 2);

        array_push(
            $array,
            [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'materiales' => $materiales,
                'consumables' => $consumables,
                'servicios_varios' => $servicios_varios,
                'servicios_adicionales' => $servicios_adicionales,
                'dias_trabajo' => $dias_trabajo,
                'monto_adicional' => $adicionales,
                'adicionales' => $materiales_adicionales,
                'monto_materiales' => round($monto_materiales,2),
                'monto_consumibles' => round($monto_consumibles,2,2),
                'monto_servicios_varios' => round($monto_servicios_varios,2),
                'monto_servicios_adicionales' => round($monto_servicios_adicionales,2),
                'monto_dias_trabajo' => round($monto_dias_trabajo,2),
                'subtotal' => $subtotal,
                'utilidad' => $utilidad,
                'renta' => $renta,
                'letra' => $letra,
                'pago_cliente' => $pago_cliente,
                'costo_real' => $costo_real,
                'diferencia_neta' => $diferencia_neta
            ]
        );
        //dd($array[0]['materiales']);
        $view = view('exports.quoteReport', compact('array', 'quote'));

        $pdf = PDF::loadHTML($view);

        $name = 'Reporte_cotizacion_' . $quote->code . '.pdf';

        return $pdf->stream($name);
    }


    public function exportQuotesExcel()
    {
        //dd($request);
        $start = $_GET['start'];
        $end = $_GET['end'];
        $type = $_GET['type'];
        //dump($start);
        //dump($end);
        $quotes_array = [];
        $dates = '';

        if ( $start == '' || $end == '' )
        {
            //dump('Descargar todos');
            $dates = 'TOTALES';
            $quotes = [];
            switch ($type) {
                case 'all':
                    $quotes = Quote::with(['customer'])
                        //->where('state_active','open')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 'raised':
                    $quotes = Quote::with(['customer'])
                        ->where('state_active','open')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 'finished':
                    $quotes = Quote::with(['customer'])
                        ->where('state_active','close')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
            }

            foreach ( $quotes as $quote )
            {
                $date_quote = Carbon::createFromFormat('Y-m-d H:i:s', $quote->date_quote)->format('d-m-Y');

                $monto_materiales = 0;
                $monto_consumibles = 0;
                $monto_servicios_varios = 0;
                $monto_servicios_adicionales = 0;
                $monto_dias_trabajo = 0;

                foreach( $quote->equipments as $equipment )
                {
                    foreach ( $equipment->materials as $material  )
                    {
                        if ( $material->original == 1 && $material->replacement == 0 )
                        {
                            $monto_materiales += (($material->price * $material->quantity)*$equipment->quantity);
                        }

                    }

                    foreach ( $equipment->consumables as $consumable  )
                    {
                        $monto_consumibles += (($consumable->price * $consumable->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->workforces as $workforce  )
                    {
                        $monto_servicios_varios += (($workforce->price * $workforce->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->turnstiles as $turnstile  )
                    {
                        $monto_servicios_adicionales += (($turnstile->price * $turnstile->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->workdays as $workday  )
                    {
                        $monto_dias_trabajo += (($workday->total)*$equipment->quantity);
                    }
                }

                $output_details = OutputDetail::where('quote_id', $quote->id)
                    ->get();

                $monto_materiales_real = 0;
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id != null )
                    {
                        $material = Material::find($output_detail->material_id);
                        if ( $material->category_id != 2 )
                        {
                            $monto_materiales_real += ($output_detail->price);
                        }

                    }
                }

                $monto_consumibles_real = 0;
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id != null )
                    {
                        $material = Material::find($output_detail->material_id);
                        if ( $material->category_id == 2 )
                        {
                            $monto_consumibles_real += ($output_detail->price);
                        }

                    }
                }


                array_push($quotes_array, [
                    'date' => $date_quote,
                    'code' => $quote->code,
                    'description' => $quote->description_quote,
                    'materials_quote' => $monto_materiales,
                    'materials_real' => $monto_materiales_real,
                    'consumables_quote' => $monto_consumibles,
                    'consumables_real' => $monto_consumibles_real,
                    'monto_servicios_varios' => $monto_servicios_varios,
                    'monto_servicios_adicionales' => $monto_servicios_adicionales,
                    'monto_dias_trabajo' => $monto_dias_trabajo,
                    'total' => $quote->total_quote,
                    'currency_invoice' => $quote->currency_invoice,
                    'state_raise' => $quote->raise_status,
                    'state_active' => $quote->state_active,
                ]);
            }


        } else {
            $date_start = Carbon::createFromFormat('d/m/Y', $start);
            $end_start = Carbon::createFromFormat('d/m/Y', $end);

            $dates = 'DEL '. $start .' AL '. $end;
            $quotes = [];
            switch ($type) {
                case 'all':
                    $quotes = Quote::with(['customer'])
                        //->where('state_active','open')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->whereDate('date_quote', '>=',$date_start)
                        ->whereDate('date_quote', '<=',$end_start)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 'raised':
                    $quotes = Quote::with(['customer'])
                        ->where('state_active','open')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->whereDate('date_quote', '>=',$date_start)
                        ->whereDate('date_quote', '<=',$end_start)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
                case 'finished':
                    $quotes = Quote::with(['customer'])
                        ->where('state_active','close')
                        ->where('state','confirmed')
                        ->where('raise_status',1)
                        ->whereDate('date_quote', '>=',$date_start)
                        ->whereDate('date_quote', '<=',$end_start)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    break;
            }

            foreach ( $quotes as $quote )
            {
                $date_quote = Carbon::createFromFormat('Y-m-d H:i:s', $quote->date_quote)->format('d-m-Y');

                $monto_materiales = 0;
                $monto_consumibles = 0;
                $monto_servicios_varios = 0;
                $monto_servicios_adicionales = 0;
                $monto_dias_trabajo = 0;

                foreach( $quote->equipments as $equipment )
                {
                    foreach ( $equipment->materials as $material  )
                    {
                        if ( $material->original == 1 && $material->replacement == 0 )
                        {
                            $monto_materiales += (($material->price * $material->quantity)*$equipment->quantity);
                        }

                    }

                    foreach ( $equipment->consumables as $consumable  )
                    {
                        $monto_consumibles += (($consumable->price * $consumable->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->workforces as $workforce  )
                    {
                        $monto_servicios_varios += (($workforce->price * $workforce->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->turnstiles as $turnstile  )
                    {
                        $monto_servicios_adicionales += (($turnstile->price * $turnstile->quantity)*$equipment->quantity);
                    }

                    foreach ( $equipment->workdays as $workday  )
                    {
                        $monto_dias_trabajo += (($workday->total)*$equipment->quantity);
                    }
                }

                $output_details = OutputDetail::where('quote_id', $quote->id)
                    ->get();

                $monto_materiales_real = 0;
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id != null )
                    {
                        $material = Material::find($output_detail->material_id);
                        if ( $material->category_id != 2 )
                        {
                            $monto_materiales_real += ($output_detail->price);
                        }

                    }
                }

                $monto_consumibles_real = 0;
                foreach ( $output_details as $output_detail )
                {
                    if ( $output_detail->material_id != null )
                    {
                        $material = Material::find($output_detail->material_id);
                        if ( $material->category_id == 2 )
                        {
                            $monto_consumibles_real += ($output_detail->price);
                        }

                    }
                }


                array_push($quotes_array, [
                    'date' => $date_quote,
                    'code' => $quote->code,
                    'description' => $quote->description_quote,
                    'materials_quote' => $monto_materiales,
                    'materials_real' => $monto_materiales_real,
                    'consumables_quote' => $monto_consumibles,
                    'consumables_real' => $monto_consumibles_real,
                    'monto_servicios_varios' => $monto_servicios_varios,
                    'monto_servicios_adicionales' => $monto_servicios_adicionales,
                    'monto_dias_trabajo' => $monto_dias_trabajo,
                    'total' => $quote->total_quote,
                    'currency_invoice' => $quote->currency_invoice,
                    'state_raise' => $quote->raise_status,
                    'state_active' => $quote->state_active,
                ]);
            }

            //dump($date_start);
            //dump($end_start);
        }
        //dump($invoices_array);
        //dd('Fechas');
        //return response()->json(['message' => 'Reporte descargado correctamente.'], 200);
        //(new UsersExport)->download('users.xlsx');
        return (new QuotesReportExcelExport($quotes_array, $dates))->download('reporteCotizaciones.xlsx');

    }
}
