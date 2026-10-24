<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ClassifyLegacySaleEmissionStatus extends Migration
{
    public function up()
    {
        DB::transaction(function () {
            // Solo el histórico previo a la integración: sin clave de operación.
            $legacy = function () {
                return DB::table('sales')->whereNull('emission_key')
                    ->where('emission_status', 'unknown');
            };

            // Sin intención de documento electrónico registrada.
            $legacy()->where(function ($query) {
                $query->whereNull('type_document')
                    ->orWhereNotIn('type_document', ['01', '03']);
            })->update(['emission_status' => 'not_requested']);

            // Evidencia histórica de identidad; NO implica aceptación SUNAT.
            // Error legacy se conserva unknown aun si hay serie y número.
            $legacy()->whereIn('type_document', ['01', '03'])
                ->whereNotNull('serie_sunat')->where('serie_sunat', '<>', '')
                ->whereNotNull('numero')->where('numero', '<>', '')
                ->where('numero', '<>', '0')
                ->where(function ($query) {
                    $query->whereNull('sunat_status')
                        ->orWhere('sunat_status', '<>', 'Error');
                })
                ->update(['emission_status' => 'confirmed']);

            // No modificar sunat_status ni fabricar intentos históricos.
            // Resto: unknown. Resolver con evidencia antes de reemitir.
        });
    }

    public function down()
    {
        // No borrar clasificaciones que pudieron avanzar operativamente.
        // La reversión de la estructura elimina la columna si se solicita.
    }
}
