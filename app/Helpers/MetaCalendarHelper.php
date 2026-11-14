<?php

namespace App\Helpers;

use App\DateDimension;
use Carbon\Carbon;

class MetaCalendarHelper
{
    /**
     * Devuelve estructura:
     *  [
     *    2025 => [
     *      'months' => [ 1 => 'Enero', 2 => 'Febrero', ... ],
     *      'weeks'  => [
     *          1 => [ ['number' => 44, 'start' => '01/11/2025', 'end' => '07/11/2025'], ... ],
     *          2 => [ ... ], // mes 2
     *      ]
     *    ],
     *    ...
     *  ]
     */
    public static function buildCalendarData()
    {
        // Una sola consulta, bien ordenada
        $rows = DateDimension::orderBy('year')
            ->orderBy('month')
            ->orderBy('date')
            ->get(['date', 'year', 'month', 'month_name', 'week_of_year']);

        $calendar = [];

        foreach ($rows as $row) {
            $y          = $row->year;
            $m          = $row->month;
            $monthName  = $row->month_name;
            $weekNumber = $row->week_of_year;
            $date       = $row->date; // es un Carbon (por el cast)

            if (!isset($calendar[$y])) {
                $calendar[$y] = [
                    'months' => [],
                    'weeks'  => [],
                ];
            }

            // Registrar mes
            $calendar[$y]['months'][$m] = $monthName;

            // Inicializar semanas del mes si no existe
            if (!isset($calendar[$y]['weeks'][$m])) {
                $calendar[$y]['weeks'][$m] = [];
            }

            // Usamos el número de semana como key interno
            if (!isset($calendar[$y]['weeks'][$m][$weekNumber])) {
                // Primera vez que vemos esa semana: start = end = este día
                $calendar[$y]['weeks'][$m][$weekNumber] = [
                    'number' => $weekNumber,
                    'start'  => $date->format('d/m/Y'),
                    'end'    => $date->format('d/m/Y'),
                ];
            } else {
                // Ya existe, solo actualizamos el end (último día que vamos viendo)
                $calendar[$y]['weeks'][$m][$weekNumber]['end'] = $date->format('d/m/Y');
            }
        }

        // Opcional: convertir las semanas de cada mes a arrays indexados 0..n
        foreach ($calendar as $year => $data) {
            foreach ($data['weeks'] as $month => $weeksAssoc) {
                $calendar[$year]['weeks'][$month] = array_values($weeksAssoc);
            }
        }

        return $calendar;
    }

    // getRangeForPeriod se queda igual que lo tienes
    public static function getRangeForPeriod($tipoMeta, $data)
    {
        $y = $data['year'];
        $m = $data['month'];

        if ($tipoMeta === 'mensual') {

            $start = Carbon::createFromDate($y, $m, 1);
            $end   = $start->copy()->endOfMonth();

            return ['start' => $start, 'end' => $end];
        }

        if ($tipoMeta === 'quincenal') {

            if ($data['quincena'] == 1) {
                $start = Carbon::createFromDate($y, $m, 1);
                $end   = Carbon::createFromDate($y, $m, 15);
            } else {
                $start = Carbon::createFromDate($y, $m, 16);
                $end   = Carbon::createFromDate($y, $m, 1)->endOfMonth();
            }

            return ['start' => $start, 'end' => $end];
        }

        if ($tipoMeta === 'semanal') {

            $weekNumber = $data['week'];

            $start = DateDimension::where('year', $y)
                ->where('month', $m)
                ->where('week_of_year', $weekNumber)
                ->orderBy('date')
                ->first();

            $end = DateDimension::where('year', $y)
                ->where('month', $m)
                ->where('week_of_year', $weekNumber)
                ->orderByDesc('date')
                ->first();

            if (!$start || !$end) {
                throw new \Exception("Semana no encontrada");
            }

            return ['start' => \Carbon\Carbon::parse($start->date), 'end' => \Carbon\Carbon::parse($end->date)];
        }

        throw new \Exception("Tipo de meta no soportado");
    }
}
