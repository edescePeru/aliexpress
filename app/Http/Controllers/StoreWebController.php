<?php

namespace App\Http\Controllers;

use App\DataGeneral;
use Illuminate\Http\Request;

class StoreWebController extends Controller
{
    public function home()
    {
        $dataLogotipoEmpresa = DataGeneral::where('name', 'logotipo')->first();
        $logotipoEmpresa = $dataLogotipoEmpresa->valueText;
        return view('shop.home', compact('logotipoEmpresa'));
    }

    public function catalog()
    {
        $dataLogotipoEmpresa = DataGeneral::where('name', 'logotipo')->first();
        $logotipoEmpresa = $dataLogotipoEmpresa->valueText;
        return view('shop.catalog', compact('logotipoEmpresa'));
    }
}
