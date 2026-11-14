<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use App\Services\Sales\MetaProgress;
use App\Helpers\MetaCalendarHelper;
use Illuminate\Support\Facades\Validator;
use App\DateDimension;
use App\Meta;
use App\User;
use App\Support\TenantContext;
use App\Services\Materials\MaterialParameters;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\CalendarioHelper;

class MetaController extends Controller
{
    public function index(Request $request)
    {
        app(MaterialParameters::class)->context();
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

        $metas = Meta::forCurrentCompany()->withCount('users')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('metas.index', compact(
            'metas',
            'tipoMeta',
            'canCreateMetas'
        ));
    }

    public function create()
    {
        app(MaterialParameters::class)->context();
        $tipoMeta = app(SettingService::class)
            ->get('sales.goals.frequency');

        $tipoValido = [
            'semanal',
            'quincenal',
            'mensual',
        ];

        if (!in_array($tipoMeta, $tipoValido, true)) {
            return redirect()
                ->route(auth()->user()->can('list_metas') ? 'metas.index' : 'home')
                ->with(
                    'error',
                    'Primero configure el tipo de meta en Configuración de Empresa.'
                );
        }

        $calendarData = CalendarioHelper::buildCalendarData();

        $users = $this->eligibleUsers()->orderBy('users.name')->get();

        return view('metas.create', compact(
            'tipoMeta',
            'calendarData',
            'users'
        ));
    }

    public function store(Request $request)
    {
        app(MaterialParameters::class)->context();
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
            'monto'  => 'required|numeric|gt:0|max:99999999.99',
            'year'   => 'required|integer|between:1900,9998',
            'month'  => 'required|integer|between:1,12',
            'users' => 'required|array|min:1',
            'users.*' => 'integer|distinct',
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

        DB::transaction(function () use ($tipoMeta, $data, $fecha_inicio, $fecha_fin) {
            $this->lockCompany();
            $this->validateAssignments($data['users'], $fecha_inicio, $fecha_fin);
            $meta = Meta::create([
                'tenant_id' => TenantContext::tenantId(),
                'company_id' => TenantContext::companyId(),
                'tipo' => $tipoMeta,
                'nombre' => $data['nombre'],
                'monto' => $data['monto'],
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
            ]);
            $meta->users()->sync($data['users']);
        });

        return redirect()->route(auth()->user()->can('list_metas') ? 'metas.index' : 'metas.create')->with('success', 'Meta creada correctamente.');
    }

    public function edit($meta)
    {
        app(MaterialParameters::class)->context();
        $meta = Meta::forCurrentCompany()->findOrFail($meta);
        $tipoMeta = $meta->tipo;
        $users = $this->eligibleUsers()->orderBy('users.name')->get();
        $selectedUserIds = $meta->users()->pluck('users.id')->all();
        $assignedUsers = $meta->users()->get(['users.id', 'users.name']);
        $users = $users->toBase()->merge($assignedUsers)->unique('id')->sortBy('name');

        return view('metas.edit', compact('meta', 'tipoMeta', 'users', 'selectedUserIds'));
    }

    public function update(Request $request, $meta)
    {
        app(MaterialParameters::class)->context();
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'monto' => 'required|numeric|gt:0|max:99999999.99',
            'users' => 'required|array|min:1',
            'users.*' => 'integer|distinct',
        ]);

        DB::transaction(function () use ($meta, $data) {
            $this->lockCompany();
            $record = Meta::forCurrentCompany()->lockForUpdate()->findOrFail($meta);
            $this->validateAssignments($data['users'], $record->fecha_inicio, $record->fecha_fin, $record);
            $record->update(['nombre' => $data['nombre'], 'monto' => $data['monto']]);
            $record->users()->sync($data['users']);
        });

        return redirect()->route(auth()->user()->can('list_metas') ? 'metas.index' : 'metas.edit', auth()->user()->can('list_metas') ? [] : [$meta])->with('success', 'Meta actualizada correctamente.');
    }

    public function destroy($meta)
    {
        app(MaterialParameters::class)->context();
        DB::transaction(function () use ($meta) {
            $this->lockCompany();
            $record = Meta::forCurrentCompany()->findOrFail($meta);
            $record->workers()->detach();
            $record->delete();
        });

        return response()->json(['ok' => true]);
    }

    private function eligibleUsers()
    {
        return User::query()->where('users.tenant_id', TenantContext::tenantId())
            ->where('users.enable', true)->where('users.is_platform_admin', false)
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('company_user')
                    ->whereColumn('company_user.user_id', 'users.id')
                    ->where('company_user.company_id', TenantContext::companyId())
                    ->where('company_user.is_active', true);
            });
    }

    private function lockCompany(): void
    {
        DB::table('companies')->where('tenant_id', TenantContext::tenantId())
            ->where('id', TenantContext::companyId())->lockForUpdate()->first();
    }

    private function validateAssignments(array $ids, $start, $end, Meta $meta = null): void
    {
        $existing = $meta ? $meta->users()->pluck('users.id')->all() : [];
        $newIds = array_diff($ids, $existing);
        if ($this->eligibleUsers()->whereIn('users.id', $newIds)->count() !== count($newIds)) {
            throw ValidationException::withMessages(['users' => 'Seleccione usuarios habilitados en la empresa actual.']);
        }

        $conflicts = Meta::forCurrentCompany()->where('fecha_inicio', '<=', $end)->where('fecha_fin', '>=', $start)
            ->whereHas('users', function ($query) use ($ids) {
                $query->whereIn('users.id', $ids);
            });
        if ($meta) {
            $conflicts->where('metas.id', '<>', $meta->id);
        }
        if ($conflicts->exists()) {
            throw ValidationException::withMessages(['users' => 'Algún usuario ya tiene una meta en un período que se cruza con este.']);
        }
    }

    public function getWeeksByYearMonth(Request $request)
    {
        app(MaterialParameters::class)->context();
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
        app(MaterialParameters::class)->context();
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
        app(MaterialParameters::class)->context();
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
            'year'  => 'required|integer|between:1900,9998',
            'month' => 'required|integer|between:1,12',
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
            ], 422);
        }

        $fechaInicio = $range['start']; // Carbon
        $fechaFin    = $range['end'];   // Carbon

        return response()->json([
            'ok' => true,
            'data' => app(MetaProgress::class)->rows($tipoMeta, $fechaInicio, $fechaFin),
            'period' => ['start' => $fechaInicio->format('d/m/Y'), 'end' => $fechaFin->format('d/m/Y')],
        ]);
    }
}
