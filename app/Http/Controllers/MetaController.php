<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use App\DateDimension;
use App\Helpers\MetaCalendarHelper;
use App\Meta;
use App\MetaWorker;
use App\Sale;
use App\Worker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\CalendarioHelper;
use Illuminate\Support\Facades\Validator;

class MetaController extends Controller
{
    public function index(Request $request)
    {
        $tipoMeta = app(SettingService::class)
            ->get('sales.goals.frequency');

        $tipoValido = [
            'semanal',
            'quincenal',
            'mensual',
        ];

        $canCreateMetas = in_array(
            $tipoMeta,
            $tipoValido,
            true
        );

        if (!$canCreateMetas) {
            $tipoMeta = null;
        }

        $metas = Meta::withCount('workers')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('metas.index', compact(
            'metas',
            'tipoMeta',
            'canCreateMetas'
        ));
    }

    /**
     * Guardar / configurar el tipo de meta
     */
    public function configTipo(Request $request)
    {
        $request->validate([
            'tipo_meta' => 'required|in:semanal,quincenal,mensual',
        ]);

        app(SettingService::class)->set(
            'sales.goals.frequency',
            $request->tipo_meta
        );

        return redirect()
            ->route('metas.index')
            ->with(
                'success',
                'Tipo de meta configurado correctamente.'
            );
    }

    public function create()
    {
        $tipoMeta = app(SettingService::class)
            ->get('sales.goals.frequency');

        $tipoValido = [
            'semanal',
            'quincenal',
            'mensual',
        ];

        if (!in_array($tipoMeta, $tipoValido, true)) {
            return redirect()
                ->route('metas.index')
                ->with(
                    'error',
                    'Primero configure el tipo de meta en el listado de metas.'
                );
        }

        $calendarData = CalendarioHelper::buildCalendarData();

        $workers = Worker::where('enable', true)
            ->orderBy('first_name')
            ->get();

        return view('metas.create', compact(
            'tipoMeta',
            'calendarData',
            'workers'
        ));
    }

    public function store(Request $request)
    {
        // Tipo de meta desde configuración
        $tipoMeta = app(SettingService::class)->get('sales.goals.frequency');

        if (!in_array(
            $tipoMeta,
            ['semanal', 'quincenal', 'mensual'],
            true
        )) {
            return back()->with(
                'error',
                'Tipo de meta no configurado correctamente.'
            );
        }

        $rules = [
            'nombre' => 'required|string|max:255',
            'monto'  => 'required|numeric|min:0',
            'year'   => 'required|integer',
            'month'  => 'required|integer',
            'workers' => 'array',
            'workers.*' => 'integer|exists:workers,id',
        ];

        if ($tipoMeta === 'semanal') {
            $rules['week'] = 'required|integer';
        } elseif ($tipoMeta === 'quincenal') {
            $rules['quincena'] = 'required|in:1,2';
        }

        $data = $request->validate($rules);

        $year  = (int) $data['year'];
        $month = (int) $data['month'];

        // Calcular fecha_inicio y fecha_fin según tipo
        if ($tipoMeta === 'semanal') {
            $weekOfMonth = (int) $data['week'];

            $dates = DateDimension::where('year', $year)
                ->where('month', $month)
                ->where('week_of_year', $weekOfMonth)
                ->orderBy('date')
                ->get();

            if ($dates->isEmpty()) {
                return back()->with('error', 'No se encontraron fechas para la semana seleccionada.')
                    ->withInput();
            }

            $fecha_inicio = $dates->first()->date->format('Y-m-d');
            $fecha_fin    = $dates->last()->date->format('Y-m-d');

        } elseif ($tipoMeta === 'mensual') {
            $start = Carbon::create($year, $month, 1);
            $fecha_inicio = $start->toDateString();
            $fecha_fin    = $start->copy()->endOfMonth()->toDateString();

        } else { // quincenal
            $quincena = (int) $data['quincena'];
            $startMonth = Carbon::create($year, $month, 1);
            $endMonth   = $startMonth->copy()->endOfMonth();

            if ($quincena === 1) {
                $fecha_inicio = $startMonth->toDateString();
                // hasta el 15 o el fin de mes si tiene menos de 15 días
                $fecha_fin = $startMonth->copy()->day(min(15, $endMonth->day))->toDateString();
            } else {
                // segunda quincena
                $fecha_inicio = $startMonth->copy()->day(16)->toDateString();
                $fecha_fin    = $endMonth->toDateString();
            }
        }

        // Crear meta
        $meta = Meta::create([
            'tipo'         => $tipoMeta,
            'nombre'       => $data['nombre'],
            'monto'        => $data['monto'],
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin'    => $fecha_fin,
        ]);

        // Asignar workers
        $workerIds = $data['workers'] ?? [];
        if (!empty($workerIds)) {
            // si tienes belongsToMany en Meta:
            // $meta->workers()->sync($workerIds);

            // usando modelo MetaWorker explícito:
            foreach ($workerIds as $wid) {
                MetaWorker::create([
                    'meta_id'   => $meta->id,
                    'worker_id' => $wid,
                ]);
            }
        }

        return redirect()->route('metas.index')
            ->with('success', 'Meta creada correctamente.');
    }

    public function edit(Meta $meta)
    {
        $tipoMeta = app(SettingService::class)
            ->get('sales.goals.frequency');

        $tipoMeta = $tipoMeta ?: $meta->tipo;

        // Trabajadores actualmente asignados a esta meta
        $meta->load('workers');
        $metaWorkers = $meta->workers->map(function ($w) {
            return [
                'id'   => $w->id,
                'name' => trim($w->first_name . ' ' . $w->last_name),
            ];
        })->values()->all();

        // Trabajadores disponibles para agregar:
        // todos los habilitados que NO estén en otra meta distinta a esta
        $workers = Worker::where('enable', true)
            ->whereNotIn('id', function ($q) use ($meta) {
                $q->select('worker_id')
                    ->from('meta_workers')
                    ->where('meta_id', '!=', $meta->id);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('metas.edit', compact(
            'meta',
            'tipoMeta',
            'workers',
            'metaWorkers'
        ));
    }

    public function update(Request $request, Meta $meta)
    {
        // Solo permitimos editar nombre, monto y trabajadores
        $rules = [
            'nombre'   => 'required|string|max:255',
            'monto'    => 'required|numeric|min:0',
            'workers'  => 'array',
            'workers.*'=> 'integer|exists:workers,id',
        ];

        $data = $request->validate($rules);

        // 1) Actualizar nombre y monto
        $meta->update([
            'nombre' => $data['nombre'],
            'monto'  => $data['monto'],
        ]);

        // 2) Actualizar trabajadores
        $workerIds = $data['workers'] ?? [];

        // Seguridad extra: ningún worker puede pertenecer a otra meta
        if (!empty($workerIds)) {
            $invalidWorkers = MetaWorker::whereIn('worker_id', $workerIds)
                ->where('meta_id', '!=', $meta->id)
                ->pluck('worker_id')
                ->unique();

            if ($invalidWorkers->isNotEmpty()) {
                $names = Worker::whereIn('id', $invalidWorkers)->get()
                    ->map(function ($w) {
                        return trim($w->first_name . ' ' . $w->last_name);
                    })->implode(', ');

                return back()
                    ->withInput()
                    ->with('error', 'Los siguientes trabajadores ya están asignados a otra meta: ' . $names);
            }
        }

        // Borrar relaciones que ya no están
        MetaWorker::where('meta_id', $meta->id)
            ->whereNotIn('worker_id', $workerIds)
            ->delete();

        // Crear relaciones nuevas (si no existen)
        foreach ($workerIds as $wid) {
            MetaWorker::firstOrCreate([
                'meta_id'   => $meta->id,
                'worker_id' => $wid,
            ]);
        }

        return redirect()->route('metas.index')
            ->with('success', 'Meta actualizada correctamente.');
    }

    public function progreso()
    {
        // Esta también luego
        return view('metas.progreso');
    }

    public function destroy(Meta $meta)
    {
        // aquí podrías borrar primero relaciones pivote, si no tienes cascade
        $meta->workers()->detach();
        $meta->delete();

        return response()->json(['ok' => true]);
    }

    public function getWeeksByYearMonth(Request $request)
    {
        $year  = (int) $request->get('year');
        $month = (int) $request->get('month');

        if (!$year || !$month) {
            return response()->json([], 400);
        }

        // Usamos week_of_year para tener 44, 45, etc.
        $weeks = DateDimension::selectRaw('week_of_year as week, MIN(date) as start_date, MAX(date) as end_date')
            ->where('year', $year)
            ->where('month', $month)
            ->groupBy('week_of_year')
            ->orderBy('week_of_year')
            ->get();

        // Armamos el arreglo para el select
        $data = $weeks->map(function ($row) {
            return [
                'value' => (int) $row->week, // ej. 44
                'label' => sprintf(
                    'Semana %d (%s - %s)',
                    $row->week,
                    Carbon::parse($row->start_date)->format('d/m/Y'),
                    Carbon::parse($row->end_date)->format('d/m/Y')
                ),
            ];
        })->values();

        return response()->json($data);
    }

    public function ranking()
    {
        $tipoValido = [
            'semanal',
            'quincenal',
            'mensual',
        ];

        $tipoMeta = app(SettingService::class)
            ->get('sales.goals.frequency');

        $tipoMetaValida = in_array(
            $tipoMeta,
            $tipoValido,
            true
        );

        if (!$tipoMetaValida) {
            $tipoMeta = null;
        }

        if (!$tipoMetaValida) {
            // si no está configurado, no tiene sentido el ranking
            return view('metas.ranking', [
                'tipoMeta'       => null,
                'tipoMetaValida' => false,
                'calendarData'   => [],
            ]);
        }

        // 2) Calendar data para armar selects (igual que en create)
        $calendarData = MetaCalendarHelper::buildCalendarData();
        //$calendarData = [];
        return view('metas.ranking', [
            'tipoMeta'       => $tipoMeta,
            'tipoMetaValida' => true,
            'calendarData'   => $calendarData,
        ]);
    }

    public function getRankingData(Request $request)
    {
        $tipoMeta = app(SettingService::class)
            ->get('sales.goals.frequency');

        if (!in_array($tipoMeta, ['semanal', 'quincenal', 'mensual'])) {
            return response()->json([
                'ok'    => false,
                'error' => 'Tipo de meta no configurado correctamente.',
            ], 400);
        }

        $year  = $request->input('year');
        $month = $request->input('month');
        $week  = $request->input('week');
        $quin  = $request->input('quincena');

        // Validaciones básicas según tipo de meta
        $rules = [
            'year'  => 'required|integer',
            'month' => 'required|integer',
        ];

        if ($tipoMeta === 'semanal') {
            $rules['week'] = 'required|integer';
        } elseif ($tipoMeta === 'quincenal') {
            $rules['quincena'] = 'required|in:1,2';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'ok'    => false,
                'error' => 'Parámetros inválidos',
                'msgs'  => $validator->errors(),
            ], 422);
        }

        // Obtener rango de fechas usando el helper
        try {
            $range = MetaCalendarHelper::getRangeForPeriod($tipoMeta, [
                'year'     => (int)$year,
                'month'    => (int)$month,
                'week'     => $tipoMeta === 'semanal'   ? (int)$week : null,
                'quincena' => $tipoMeta === 'quincenal' ? (int)$quin : null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'    => false,
                'error' => 'No se pudo calcular el rango de fechas.',
            ], 500);
        }

        $fechaInicio = $range['start']; // Carbon
        $fechaFin    = $range['end'];   // Carbon

        // 1) Buscar metas que tengan exactamente ese periodo
        $metas = Meta::with('workers')
            ->whereDate('fecha_inicio', $fechaInicio->format('Y-m-d'))
            ->whereDate('fecha_fin', $fechaFin->format('Y-m-d'))
            ->get();

        if ($metas->isEmpty()) {
            return response()->json([
                'ok'     => true,
                'data'   => [],
                'period' => [
                    'start' => $fechaInicio->format('d/m/Y'),
                    'end'   => $fechaFin->format('d/m/Y'),
                ],
            ]);
        }

        // IMPORTANTE: asumimos que cada worker solo pertenece a una meta
        $ranking = [];

        $inicio = $fechaInicio->copy()->startOfDay();  // 2025-11-24 00:00:00
        $fin    = $fechaFin->copy()->endOfDay();      // 2025-11-30 23:59:59

        foreach ($metas as $meta) {
            foreach ($meta->workers as $worker) {

                // 2) Ventas del trabajador en ese rango
                /*$ventasTotal = Sale::where('worker_id', $worker->id)
                    ->whereBetween('date_sale', [
                        $fechaInicio->format('Y-m-d'),
                        $fechaFin->format('Y-m-d'),
                    ])
                    ->where('state_annulled', false)
                    ->sum('importe_total');*/
                $ventasTotal = Sale::where('worker_id', $worker->id)
                    ->whereBetween('date_sale', [$inicio, $fin])
                    ->where('state_annulled', false)
                    ->sum('importe_total');

                // Cálculo de % de cumplimiento
                $metaMonto = (float) $meta->monto;
                $porcentaje = $metaMonto > 0
                    ? round(($ventasTotal / $metaMonto) * 100, 2)
                    : 0;

                $ranking[] = [
                    'worker_id'       => $worker->id,
                    'worker_name'     => trim($worker->first_name . ' ' . $worker->last_name),
                    'meta_id'         => $meta->id,
                    'meta_nombre'     => $meta->nombre,
                    'meta_monto'      => round($metaMonto, 2),
                    'ventas_total'    => round($ventasTotal, 2),
                    'porcentaje'      => $porcentaje,
                ];
            }
        }

        // Ordenar de mayor a menor por ventas_total
        usort($ranking, function ($a, $b) {
            return $b['ventas_total'] <=> $a['ventas_total'];
        });

        // Agregar campo "puesto"
        foreach ($ranking as $idx => &$row) {
            $row['puesto'] = $idx + 1;
        }

        return response()->json([
            'ok'   => true,
            'data' => $ranking,
            'period' => [
                'start' => $fechaInicio->format('d/m/Y'),
                'end'   => $fechaFin->format('d/m/Y'),
            ],
        ]);
    }
}
