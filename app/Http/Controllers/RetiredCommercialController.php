<?php
namespace App\Http\Controllers;

class RetiredCommercialController extends Controller
{
    protected function unavailable()
    {
        $message = 'Las proformas y órdenes de servicio legacy fueron retiradas. Sus datos históricos se conservan; esta sección ya no permite consultar ni modificar documentos.';
        if (request()->expectsJson()) { return response()->json(['message' => $message], 410); }
        return response()->view('commercialLegacy.retired', ['message' => $message], 410);
    }
}
