<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\IdentityDocumentType;
use App\Services\NubefactGreService;
use App\Services\PlatformAuditService;
use App\SunatShippingIndicator;
use App\TransferReason;
use App\TransportType;
use App\WeightUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class ShippingCatalogController extends Controller
{
    private const CATALOGS = [
        'transporte' => [TransportType::class, 'Tipos de transporte', 120],
        'motivos' => [TransferReason::class, 'Motivos de traslado', 120],
        'documentos' => [IdentityDocumentType::class, 'Tipos de documento', 160],
        'peso' => [WeightUnit::class, 'Unidades de peso', 120],
        'indicadores' => [SunatShippingIndicator::class, 'Indicadores SUNAT', 160],
    ];

    private const REMITTER_REASONS = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '13', '14', '17', '18'];

    private function supportedCodes($catalog): array
    {
        return [
            'motivos' => self::REMITTER_REASONS,
            'indicadores' => array_keys(NubefactGreService::REMITTER_INDICATORS),
        ][$catalog] ?? [];
    }

    public function index($catalog = 'transporte')
    {
        [$model, $title, $maxName] = $this->catalog($catalog);
        $records = $model::query()->orderBy('sort_order')->orderBy('code')->get();
        $catalogs = self::CATALOGS;

        $supportedCodes = $this->supportedCodes($catalog);
        $availableCodes = array_diff($supportedCodes, $records->pluck('code')->all());

        return view('platform.shippingCatalogs.index', compact(
            'catalog', 'catalogs', 'title', 'maxName', 'records', 'supportedCodes', 'availableCodes'
        ));
    }

    public function store(Request $request, $catalog, PlatformAuditService $audit)
    {
        [$model, , $maxName] = $this->catalog($catalog);
        $codes = $this->supportedCodes($catalog);
        abort_if($codes === [], 404);

        $table = (new $model())->getTable();
        $data = $request->validate([
            'code' => ['required', 'string', Rule::in($codes), Rule::unique($table, 'code')],
            'name' => ['required', 'string', 'max:'.$maxName],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
            'is_active' => ['required', 'boolean'],
        ], [
            'code.in' => 'Seleccione un código admitido para la guía remitente.',
            'code.unique' => 'Ese código ya está registrado.',
        ]);

        try {
            DB::transaction(function () use ($model, $data, $audit, $catalog) {
                $record = $model::create($data);
                $audit->log('shipping_catalog_created', $record, [
                    'catalog' => $catalog,
                    'code' => $record->code,
                    'after' => $record->only(['name', 'sort_order', 'is_active']),
                ]);
            });
        } catch (QueryException $e) {
            if ($model::query()->where('code', $data['code'])->exists()) {
                throw ValidationException::withMessages(['code' => 'Ese código ya está registrado.']);
            }
            throw $e;
        }

        return redirect()->route('platformShippingCatalogs.index', $catalog)
            ->with('success', 'Registro creado correctamente.');
    }

    public function update(Request $request, $catalog, $id, PlatformAuditService $audit)
    {
        [$model, , $maxName] = $this->catalog($catalog);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:'.$maxName],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
            'is_active' => ['required', 'boolean'],
        ]);
        $fields = ['name', 'sort_order', 'is_active'];

        DB::transaction(function () use ($model, $id, $data, $fields, $audit, $catalog) {
            $record = $model::query()->lockForUpdate()->findOrFail($id);
            $before = $record->only($fields);
            $record->name = $data['name'];
            $record->sort_order = (int) $data['sort_order'];
            $record->is_active = (bool) $data['is_active'];
            if ($record->isDirty($fields)) {
                $record->save();
                $audit->log('shipping_catalog_updated', $record, [
                    'catalog' => $catalog,
                    'code' => $record->code,
                    'before' => $before,
                    'after' => $record->only($fields),
                ]);
            }
        });

        return redirect()->route('platformShippingCatalogs.index', $catalog)
            ->with('success', 'Configuración guardada.');
    }

    private function catalog($catalog): array
    {
        abort_unless(isset(self::CATALOGS[$catalog]), 404);

        return self::CATALOGS[$catalog];
    }
}