<?php

namespace App\Http\Controllers;

use App\Customer;
use App\IdentityDocumentType;
use App\Sale;
use App\Services\NubefactGreService;
use App\ShippingGuide;
use App\ShippingGuideDriver;
use App\ShippingGuideItem;
use App\ShippingGuideVehicle;
use App\SunatShippingIndicator;
use App\TransferReason;
use App\TransportType;
use App\WeightUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ShippingGuidesExport;
use App\Support\TenantContext;

class ShippingGuideController extends Controller
{
    /** @var NubefactGreService */
    private $nubefact;

    public function __construct(NubefactGreService $nubefact)
    {
        $this->nubefact = $nubefact;
    }

    private function customers()
    {
        return Customer::query()->where('tenant_id', TenantContext::tenantId());
    }

    private function sales()
    {
        return Sale::query()->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())->where('branch_id', TenantContext::branchId())
            ->where('state_annulled', false)->where(function ($query) {
                $query->whereNull('internal_reversal_status')->orWhere('internal_reversal_status', '<>', 'reversed');
            });
    }

    private function nextGreNumber(array $numbering): int
    {
        $last = ShippingGuide::query()->where('tipo_de_comprobante', 7)
            ->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())
            ->where('serie', $numbering['series'])
            ->max(DB::raw('CAST(numero AS UNSIGNED)'));

        return max($numbering['first_number'], (int) $last + 1);
    }

    public function customerPayload($id)
    {
        return app(CustomerController::class)->payload($this->customers()->findOrFail($id));
    }

    public function products(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1']);
        $term = trim($filters['q'] ?? '');
        $query = app(\App\Services\Sales\PosCatalogReadService::class)->catalog()->with('material:id,full_name');
        if ($term !== '') {
            $query->where(function ($query) use ($term) {
                $query->where('display_name', 'like', '%'.$term.'%')->orWhere('sku', 'like', '%'.$term.'%')
                    ->orWhere('barcode', 'like', '%'.$term.'%');
            });
        }
        $page = $query->orderBy('display_name')->orderBy('id')->paginate(10);
        $results = $page->getCollection()->map(function ($item) {
            $name = $item->display_name ?: $item->material->full_name;
            return [
                'id' => $item->id, 'text' => $name, 'descripcion' => $name,
                'stock_item_id' => $item->id, 'material_id' => $item->material_id,
                'codigo' => $item->sku ?: (string) $item->id,
            ];
        });
        return response()->json(['results' => $results, 'pagination' => ['more' => $page->hasMorePages()]]);
    }

    public function index(Request $request)
    {
        $query = ShippingGuide::forCurrentLocal()
            ->where('shipping_guides.tipo_de_comprobante', 7) // SOLO REMITENTE
            ->leftJoin('transfer_reasons', 'transfer_reasons.code', '=', 'shipping_guides.motivo_traslado_code')
            ->orderByDesc('shipping_guides.id')
            ->select([
                'shipping_guides.*',
                DB::raw("COALESCE(transfer_reasons.name, shipping_guides.motivo_traslado_code) as motivo_traslado_name"),
            ]);

        if ($request->filled('date_from')) {
            $query->whereDate('shipping_guides.fecha_emision', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('shipping_guides.fecha_emision', '<=', $request->date_to);
        }

        if ($request->filled('buscar_doc')) {
            $buscar = trim($request->buscar_doc);

            if (strpos($buscar, '-') !== false) {
                [$serie, $numero] = array_map('trim', explode('-', $buscar, 2));

                $query->where('shipping_guides.serie', $serie)
                    ->where('shipping_guides.numero', $numero);
            }
        }

        return response()->json([
            'data' => $query->paginate(20),
        ]);
    }

    public function viewIndex()
    {
        return view('shipping_guides.index');
    }

    public function create(Request $request)
    {
        // MVP: solo remitente
        $type = $request->get('type', 'remitente');
        if ($type !== 'remitente') abort(404);

        // Cargar catálogos para los selects
        $transportTypes = TransportType::where('is_active', true)->whereIn('code', ['01', '02'])
            ->orderBy('sort_order')->orderBy('code')->get();
        $transferReasons = TransferReason::where('is_active', true)->orderBy('sort_order')->orderBy('code')->get();
        $weightUnits = WeightUnit::where('is_active', true)->whereIn('code', ['KGM', 'TNE'])
            ->orderBy('sort_order')->orderBy('code')->get();
        $indicators = SunatShippingIndicator::where('is_active', true)->orderBy('sort_order')->orderBy('code')->get();
        $identityDocTypes = IdentityDocumentType::where('is_active', true)->orderBy('sort_order')->orderBy('code')->get();
        $recipientDocTypes = $identityDocTypes->whereIn('code', ['6', '1', '4', '7']);
        $carrierDocTypes = $identityDocTypes->where('code', '6');

        try {
            $numbering = app(\App\Services\NubefactCompanyConfigService::class)
                ->forCompany(TenantContext::tenantId(), TenantContext::companyId())->greRemitterNumbering();
        } catch (\RuntimeException $e) {
            $numbering = null;
        }

        $defaultSerie = $numbering['series'] ?? null;
        $nextNum = $numbering ? $this->nextGreNumber($numbering) : null;
        if ($nextNum > 99999999) {
            $nextNum = null;
        }

        $customers = $this->customers()->orderBy('business_name')->get();

        return view('shipping_guides.create', compact(
            'transportTypes',
            'recipientDocTypes',
            'carrierDocTypes',
            'transferReasons',
            'weightUnits',
            'indicators',
            'identityDocTypes',
            'defaultSerie',
            'customers',
            'nextNum'
        ));
    }

    public function show(ShippingGuide $guide)
    {
        $guide = ShippingGuide::forCurrentLocal()->where('tipo_de_comprobante', 7)->findOrFail($guide->id);
        $guide->load(['items', 'vehicles', 'drivers']);

        // para mostrar nombre del motivo en show (tabla transfer_reasons)
        $transferReason = DB::table('transfer_reasons')
            ->where('code', $guide->motivo_traslado_code)
            ->value('name');

        return view('shipping_guides.show', [
            'guide' => $guide,
            'transferReasonName' => $transferReason,
        ]);
    }

    public function store(Request $request)
    {
        foreach (['tenant' => TenantContext::tenantId(), 'company' => TenantContext::companyId(),
            'branch' => TenantContext::branchId()] as $key => $expected) {
            if ((string) $request->input('gre_'.$key, '') !== (string) $expected) {
                throw \Illuminate\Validation\ValidationException::withMessages(['context' => 'Cambió el contexto. Vuelva a abrir el formulario.']);
            }
        }

        $data = $request->validate(
            [
                // ====== Encabezado ======

                'fecha_emision'         => ['required', 'date'],
                'fecha_inicio_traslado' => ['required', 'date'],

                'motivo_traslado_code' => ['required', 'string', 'size:2', Rule::exists('transfer_reasons', 'code')->where('is_active', true)],
                'tipo_transporte'      => ['required', 'string', Rule::in(['01', '02']), Rule::exists('shipping_transport_types', 'code')->where('is_active', true)],

                // ====== Traslado ======
                'peso_bruto_total'   => ['required', 'numeric', 'min:0'],
                'peso_bruto_um_code' => ['required', Rule::in(['KGM', 'TNE']), Rule::exists('weight_units', 'code')->where('is_active', true)],
                'numero_bultos'      => ['required', 'integer', 'min:1'],

                'sunat_shipping_indicator_code' => ['nullable', 'string', 'max:60', Rule::exists('sunat_shipping_indicators', 'code')->where('is_active', true)],

                // ====== Partida / Llegada ======
                'partida_ubigeo'    => ['required', 'string', 'size:6', 'exists:districts,id'],
                'partida_direccion' => ['required', 'string', 'max:255'],
                'partida_cod_establecimiento' => ['nullable', 'string', 'size:4'],

                'llegada_ubigeo'    => ['required', 'string', 'size:6', 'exists:districts,id'],
                'llegada_direccion' => ['required', 'string', 'max:255'],
                'llegada_cod_establecimiento' => ['nullable', 'string', 'size:4'],

                // ====== Destinatario (Customer del ERP) ======
                'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('tenant_id', TenantContext::tenantId())->whereNull('deleted_at')],

                // Snapshot
                'customer_doc_type'   => ['required', 'string', Rule::in(['6', '1', '4', '7']), Rule::exists('identity_document_types', 'code')->where('is_active', true)],
                'customer_doc_number' => ['required', 'string', 'max:20'],
                'customer_address'    => ['nullable', 'string', 'max:255'],
                'customer_email'      => ['nullable', 'email', 'max:120'],
                'customer_email_1'    => ['nullable', 'email', 'max:120'],
                'customer_email_2'    => ['nullable', 'email', 'max:120'],

                'observaciones' => ['nullable', 'string', 'max:2000'],

                // ====== Items ======
                'items_mode' => ['required', Rule::in(['SALE', 'MANUAL'])],
                'sale_id'    => ['required_if:items_mode,SALE', 'nullable', 'integer'],

                'items' => ['required_if:items_mode,MANUAL', 'array'],
                'items.*.product_id' => ['required_if:items_mode,MANUAL', 'integer'],
                'items.*.material_id' => ['required_if:items_mode,MANUAL', 'integer'],
                'items.*.descripcion' => ['required_if:items_mode,MANUAL', 'string', 'max:255'],
                'items.*.cantidad'    => ['required_if:items_mode,MANUAL', 'numeric', 'min:0.001'],
                'items.*.codigo'      => ['nullable', 'string', 'max:60'],
                'items.*.detalle_adicional' => ['nullable', 'string', 'max:255'],
            ],
            [
                // ====== Encabezado ======
                'serie.required' => 'La serie es obligatoria.',
                'serie.max'      => 'La serie no debe exceder 4 caracteres.',
                'numero.max'     => 'El número no debe exceder 20 caracteres.',

                'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
                'fecha_emision.date'     => 'La fecha de emisión no tiene un formato válido.',

                'fecha_inicio_traslado.required' => 'La fecha de inicio de traslado es obligatoria.',
                'fecha_inicio_traslado.date'     => 'La fecha de inicio de traslado no tiene un formato válido.',

                'motivo_traslado_code.required' => 'Debes seleccionar un motivo de traslado.',
                'motivo_traslado_code.size'     => 'El motivo de traslado no es válido.',

                'tipo_transporte.required' => 'Debes seleccionar el tipo de transporte.',
                'tipo_transporte.exists'   => 'El tipo de transporte seleccionado está inactivo.',
                'tipo_transporte.in'       => 'El tipo de transporte seleccionado no es válido.',

                // ====== Traslado ======
                'peso_bruto_total.required' => 'El peso bruto total es obligatorio.',
                'peso_bruto_total.numeric'  => 'El peso bruto total debe ser numérico.',
                'peso_bruto_total.min'      => 'El peso bruto total no puede ser negativo.',

                'peso_bruto_um_code.required' => 'Debes seleccionar la unidad de medida del peso.',
                'peso_bruto_um_code.max'      => 'La unidad de medida del peso no es válida.',

                'numero_bultos.required' => 'El número de bultos es obligatorio.',
                'numero_bultos.integer'  => 'El número de bultos debe ser un número entero.',
                'numero_bultos.min'      => 'El número de bultos debe ser al menos 1.',

                'sunat_shipping_indicator_code.max' => 'El indicador de envío SUNAT es demasiado largo.',

                // ====== Partida / Llegada ======
                'partida_ubigeo.required' => 'Debes seleccionar el ubigeo de partida.',
                'partida_ubigeo.size'     => 'El ubigeo de partida debe tener 6 dígitos.',
                'partida_direccion.required' => 'La dirección de partida es obligatoria.',
                'partida_direccion.max'      => 'La dirección de partida no debe exceder 255 caracteres.',
                'partida_cod_establecimiento.size' => 'El código de establecimiento de partida debe tener 4 dígitos.',

                'llegada_ubigeo.required' => 'Debes seleccionar el ubigeo de llegada.',
                'llegada_ubigeo.size'     => 'El ubigeo de llegada debe tener 6 dígitos.',
                'llegada_direccion.required' => 'La dirección de llegada es obligatoria.',
                'llegada_direccion.max'      => 'La dirección de llegada no debe exceder 255 caracteres.',
                'llegada_cod_establecimiento.size' => 'El código de establecimiento de llegada debe tener 4 dígitos.',

                // ====== Destinatario ======
                'customer_id.required' => 'Debes seleccionar un cliente (destinatario).',
                'customer_id.exists'   => 'El cliente seleccionado no existe.',

                'customer_doc_type.required' => 'El tipo de documento del destinatario es obligatorio.',
                'customer_doc_number.required' => 'El número de documento del destinatario es obligatorio.',
                'customer_doc_number.max'      => 'El número de documento del destinatario no debe exceder 20 caracteres.',

                'customer_email.email'   => 'El email del destinatario no es válido.',
                'customer_email_1.email' => 'El email 1 del destinatario no es válido.',
                'customer_email_2.email' => 'El email 2 del destinatario no es válido.',

                'observaciones.max' => 'Las observaciones no deben exceder 2000 caracteres.',

                // ====== Items ======
                'items_mode.required' => 'Debes seleccionar el modo de items (Desde venta o Manual).',
                'items_mode.in'       => 'El modo de items seleccionado no es válido.',

                'sale_id.required_if' => 'Debes seleccionar una venta para cargar los items.',
                'sale_id.exists'      => 'La venta seleccionada no existe.',

                'items.required_if' => 'Debes agregar al menos un item en modo manual.',
                'items.array'       => 'El formato de items manuales no es válido.',

                'items.*.product_id.required_if' => 'Debes seleccionar un producto en cada item manual.',
                'items.*.product_id.exists' => 'El producto seleccionado no existe.',

                'items.*.material_id.required_if' => 'Debes seleccionar un material en cada item manual.',
                'items.*.material_id.exists'      => 'El material seleccionado no existe.',

                'items.*.descripcion.required_if' => 'La descripción del item es obligatoria.',
                'items.*.descripcion.max'         => 'La descripción del item no debe exceder 255 caracteres.',
                'items.*.cantidad.required_if'    => 'La cantidad del item es obligatoria.',
                'items.*.cantidad.numeric'        => 'La cantidad del item debe ser numérica.',
                'items.*.cantidad.min'            => 'La cantidad del item debe ser mayor a 0.',
                'items.*.codigo.max'              => 'El código del item no debe exceder 60 caracteres.',
                'items.*.detalle_adicional.max'   => 'El detalle adicional del item no debe exceder 255 caracteres.',
            ]
        );

        // ====== Validaciones condicionales por transporte ======
        if ($data['tipo_transporte'] === '01') {

            $request->validate(
                [
                    'transportista_doc_type'   => ['required', 'string', Rule::in(['6']), Rule::exists('identity_document_types', 'code')->where('is_active', true)],
                    'transportista_doc_number' => ['required', 'string', 'max:20'],
                    'transportista_name'       => ['required', 'string', 'max:200'],
                    'vehicle_primary_plate'    => ['required', 'string', 'max:20'],
                ],
                [
                    'transportista_doc_type.required' => 'El tipo de documento del transportista es obligatorio.',
                    'transportista_doc_number.required' => 'El número de documento del transportista es obligatorio.',
                    'transportista_doc_number.max' => 'El número de documento del transportista no debe exceder 20 caracteres.',
                    'transportista_name.required' => 'La razón social del transportista es obligatoria.',
                    'transportista_name.max'      => 'La razón social del transportista no debe exceder 200 caracteres.',
                    'vehicle_primary_plate.required' => 'La placa del vehículo principal es obligatoria.',
                    'vehicle_primary_plate.max'      => 'La placa del vehículo principal no debe exceder 20 caracteres.',
                ]
            );

        } else {

            $request->validate(
                [
                    'vehicle_primary_plate' => ['required', 'string', 'max:20'],

                    'driver_primary.document_type_code' => ['required', 'string', 'max:2', Rule::exists('identity_document_types', 'code')->where('is_active', true)],
                    'driver_primary.document_number'    => ['required', 'string', 'max:20'],
                    'driver_primary.first_name'         => ['required', 'string', 'max:120'],
                    'driver_primary.last_name'          => ['required', 'string', 'max:120'],
                    'driver_primary.license_number'     => ['required', 'string', 'max:30'],

                    'vehicles_secondary' => ['nullable', 'array', 'max:2'],
                    'vehicles_secondary.*.plate_number' => ['required', 'string', 'max:20'],

                    'drivers_secondary' => ['nullable', 'array', 'max:2'],
                    'drivers_secondary.*.document_type_code' => ['required', 'string', 'max:2', Rule::exists('identity_document_types', 'code')->where('is_active', true)],
                    'drivers_secondary.*.document_number'    => ['required', 'string', 'max:20'],
                    'drivers_secondary.*.first_name'         => ['required', 'string', 'max:120'],
                    'drivers_secondary.*.last_name'          => ['required', 'string', 'max:120'],
                    'drivers_secondary.*.license_number'     => ['required', 'string', 'max:30'],
                ],
                [
                    'vehicle_primary_plate.required' => 'La placa del vehículo principal es obligatoria.',
                    'vehicle_primary_plate.max'      => 'La placa del vehículo principal no debe exceder 20 caracteres.',

                    'driver_primary.document_type_code.required' => 'Debes seleccionar el tipo de documento del conductor.',
                    'driver_primary.document_number.required'    => 'El número de documento del conductor es obligatorio.',
                    'driver_primary.first_name.required'         => 'Los nombres del conductor son obligatorios.',
                    'driver_primary.last_name.required'          => 'Los apellidos del conductor son obligatorios.',
                    'driver_primary.license_number.required'     => 'El número de licencia del conductor es obligatorio.',

                    'driver_primary.document_number.max' => 'El documento del conductor no debe exceder 20 caracteres.',
                    'driver_primary.first_name.max'      => 'Los nombres del conductor no deben exceder 120 caracteres.',
                    'driver_primary.last_name.max'       => 'Los apellidos del conductor no deben exceder 120 caracteres.',
                    'driver_primary.license_number.max'  => 'La licencia del conductor no debe exceder 30 caracteres.',

                    'vehicles_secondary.array' => 'El formato de vehículos secundarios no es válido.',
                    'vehicles_secondary.max'   => 'Solo se permiten hasta 2 vehículos secundarios.',
                    'vehicles_secondary.*.plate_number.required' => 'La placa del vehículo secundario es obligatoria.',
                    'vehicles_secondary.*.plate_number.max'      => 'La placa del vehículo secundario no debe exceder 20 caracteres.',

                    'drivers_secondary.array' => 'El formato de conductores secundarios no es válido.',
                    'drivers_secondary.max'   => 'Solo se permiten hasta 2 conductores secundarios.',
                    'drivers_secondary.*.document_type_code.required' => 'El tipo de documento del conductor secundario es obligatorio.',
                    'drivers_secondary.*.document_number.required'    => 'El documento del conductor secundario es obligatorio.',
                    'drivers_secondary.*.first_name.required'         => 'Los nombres del conductor secundario son obligatorios.',
                    'drivers_secondary.*.last_name.required'          => 'Los apellidos del conductor secundario son obligatorios.',
                    'drivers_secondary.*.license_number.required'     => 'La licencia del conductor secundario es obligatoria.',
                ]
            );
        }

        $customer = $this->customers()->findOrFail($data['customer_id']);
        $sale = null;
        if ($data['items_mode'] === 'SALE') {
            $sale = $this->sales()->with(['details.material', 'details.stockItem', 'details.materialPresentation'])
                ->findOrFail($data['sale_id']);
            if ($sale->details->isEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['sale_id' => 'La venta no tiene líneas para la guía.']);
            }
        } else {
            $items = collect($data['items']);
            $products = app(\App\Services\Sales\PosCatalogReadService::class)->catalog()
                ->whereIn('stock_items.id', $items->pluck('product_id'))->get()->keyBy('id');
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                if (!$product || (int) $product->material_id !== (int) $item['material_id']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Hay un producto que no corresponde al catálogo de la empresa.']);
                }
            }
        }

        $persisted = false;
        DB::beginTransaction();
        try {
            // ====== Customer (Opción A) ======
            $customerName = $customer->business_name;

            $docNumber = $data['customer_doc_number'];
            if (empty($docNumber) && !empty($customer->RUC)) {
                $docNumber = $customer->RUC;
            }

            DB::table('companies')->where('tenant_id', TenantContext::tenantId())
                ->where('id', TenantContext::companyId())->lockForUpdate()->first();
            try {
                $connection = app(\App\Services\NubefactCompanyConfigService::class)
                    ->forCompany(TenantContext::tenantId(), TenantContext::companyId());
            } catch (\RuntimeException $e) {
                DB::rollBack();
                return response()->json(['message' => 'Revise la conexión Nubefact de la empresa emisora.'], 422);
            }

            $numbering = $connection->greRemitterNumbering();
            if (!$numbering || $request->input('serie') !== $numbering['series']) {
                DB::rollBack();
                return response()->json(['message' => 'La serie GRE remitente falta o cambió. Revise la configuración y vuelva a abrir el formulario.'], 422);
            }

            $nextNum = $this->nextGreNumber($numbering);
            if ($nextNum > 99999999) {
                DB::rollBack();
                return response()->json(['message' => 'La serie GRE remitente agotó sus correlativos. Solicite una nueva serie a Platform Admin.'], 422);
            }

            $data['serie'] = $numbering['series'];
            $data['numero'] = (string) $nextNum;

            // ====== Crear guía ======
            $guide = ShippingGuide::create([
                'tenant_id' => TenantContext::tenantId(),
                'company_id' => TenantContext::companyId(),
                'branch_id' => TenantContext::branchId(),
                'guide_type' => 'REMITENTE',
                'tipo_de_comprobante' => 7,

                'serie'  => $data['serie'],
                'numero' => $data['numero'],

                'fecha_emision' => $data['fecha_emision'],
                'fecha_inicio_traslado' => $data['fecha_inicio_traslado'],

                'motivo_traslado_code' => $data['motivo_traslado_code'],
                'tipo_transporte'      => $data['tipo_transporte'],

                'peso_bruto_total'   => $data['peso_bruto_total'],
                'peso_bruto_um_code' => $data['peso_bruto_um_code'],
                'numero_bultos'      => $data['numero_bultos'],

                'sunat_shipping_indicator_code' => $data['sunat_shipping_indicator_code'] ?? null,

                'partida_ubigeo'    => $data['partida_ubigeo'],
                'partida_direccion' => $data['partida_direccion'],
                'partida_cod_establecimiento' => $data['partida_cod_establecimiento'] ?? '0000',

                'llegada_ubigeo'    => $data['llegada_ubigeo'],
                'llegada_direccion' => $data['llegada_direccion'],
                'llegada_cod_establecimiento' => $data['llegada_cod_establecimiento'] ?? '0000',

                // Snapshot destinatario
                'customer_id'         => $customer->id,
                'customer_name'       => $customerName,
                'customer_doc_type'   => $data['customer_doc_type'],
                'customer_doc_number' => $docNumber,
                'customer_address'    => $data['customer_address'] ?? $customer->address ?? null,
                'customer_email'      => $data['customer_email'] ?? null,
                'customer_email_1'    => $data['customer_email_1'] ?? null,
                'customer_email_2'    => $data['customer_email_2'] ?? null,

                'observaciones' => $data['observaciones'] ?? null,

                'items_mode' => $data['items_mode'],
                'status'     => 'DRAFT',
            ]);

            // ====== Público: agencia ======
            if ($data['tipo_transporte'] === '01') {
                $guide->update([
                    'transportista_doc_type'   => $request->input('transportista_doc_type'),
                    'transportista_doc_number' => $request->input('transportista_doc_number'),
                    'transportista_name'       => $request->input('transportista_name'),
                ]);
            }

            // ====== Vehículo principal (siempre) ======
            ShippingGuideVehicle::create([
                'shipping_guide_id' => $guide->id,
                'is_primary' => true,
                'plate_number' => $request->input('vehicle_primary_plate'),
            ]);

            // ====== Privado: conductor + secundarios ======
            if ($data['tipo_transporte'] === '02') {

                foreach ((array)$request->input('vehicles_secondary', []) as $v) {
                    ShippingGuideVehicle::create([
                        'shipping_guide_id' => $guide->id,
                        'is_primary' => false,
                        'plate_number' => $v['plate_number'],
                    ]);
                }

                $dp = $request->input('driver_primary');

                ShippingGuideDriver::create([
                    'shipping_guide_id' => $guide->id,
                    'is_primary' => true,
                    'document_type_code' => $dp['document_type_code'],
                    'document_number' => $dp['document_number'],
                    'first_name' => $dp['first_name'],
                    'last_name'  => $dp['last_name'],
                    'license_number' => $dp['license_number'],
                ]);

                foreach ((array)$request->input('drivers_secondary', []) as $d) {
                    ShippingGuideDriver::create([
                        'shipping_guide_id' => $guide->id,
                        'is_primary' => false,
                        'document_type_code' => $d['document_type_code'],
                        'document_number' => $d['document_number'],
                        'first_name' => $d['first_name'],
                        'last_name'  => $d['last_name'],
                        'license_number' => $d['license_number'],
                    ]);
                }
            }

            // ====== Items ======
            if ($data['items_mode'] === 'SALE') {


                $guide->update([
                    'source_sale_id' => $sale->id,
                    'source_sale_ref' => ($sale->serie_sunat ? ($sale->serie_sunat . '-' . $sale->numero) : ('SALE#' . $sale->id)),

                ]);

                $line = 1;
                foreach ($sale->details as $detail) {

                    $descripcion = $this->buildDetailDescription($detail);

                    $cant = (!empty($detail->material_presentation_id))
                        ? ((float)($detail->packs ?: 0))
                        : ((float)($detail->quantity ?: 0));

                    if ($cant <= 0) {
                        throw new \RuntimeException('La venta tiene un item con cantidad 0. Revisa packs/quantity.');
                    }

                    ShippingGuideItem::create([
                        'shipping_guide_id' => $guide->id,
                        'line' => $line++,
                        'product_id' => $detail->material_id,
                        'stock_item_id' => $detail->stock_item_id,
                        'codigo' => (string)$detail->stock_item_id,
                        'descripcion' => $descripcion,
                        'detalle_adicional' => null,
                        'cantidad' => $cant,
                        'unidad_medida' => 'NIU',
                    ]);
                }

            } else {

                $line = 1;
                foreach ($data['items'] as $it) {

                    $productId = isset($it['product_id']) && $it['product_id'] !== ''
                        ? (int)$it['product_id']
                        : null;

                    $materialId = isset($it['material_id']) && $it['material_id'] !== ''
                        ? (int)$it['material_id']
                        : null;

                    ShippingGuideItem::create([
                        'shipping_guide_id' => $guide->id,
                        'line' => $line++,

                        // ✅ referencia real a materials.id
                        'product_id' => $materialId,
                        'stock_item_id' => $productId,

                        // ✅ “codigo” repetido (id del material)
                        'codigo' => $it['codigo'] ?? ($productId ? (string)$productId : null),

                        'descripcion' => $it['descripcion'],
                        'detalle_adicional' => $it['detalle_adicional'] ?? null,
                        'cantidad' => $it['cantidad'],
                        'unidad_medida' => 'NIU',
                    ]);
                }
            }

            // ====== Envío Nubefact ======
            $guide->load(['items', 'vehicles', 'drivers']);

            // NO existe SENDING en tu enum -> usamos SENT como "enviado a Nubefact"
            $payload = $this->nubefact->buildPayload($guide);
            $guide->update([
                'last_nubefact_payload' => $payload,
                'status' => 'SENT', // <-- aquí
            ]);

            DB::commit();
            $persisted = true;

            $resp = $this->nubefact->send($guide, $connection);

            // Estado por SUNAT
            $accepted = (bool)($resp['aceptada_por_sunat'] ?? false);

            $soapError = trim((string)($resp['sunat_soap_error'] ?? ''));
            $description = trim((string)($resp['sunat_description'] ?? ''));
            $responseCode = trim((string)($resp['sunat_responsecode'] ?? ''));

            // Detectar error real de SUNAT
            $hasError = (!$accepted && ($soapError !== '' || $responseCode !== ''));

            if ($accepted) {
                $finalStatus = 'ACCEPTED';
            } elseif ($hasError) {
                $finalStatus = 'REJECTED';
            } else {
                $finalStatus = 'PENDING_SUNAT';
            }

            $guide->update([
                'last_nubefact_response' => $resp,

                'nubefact_enlace' => $resp['enlace'] ?? null,
                'nubefact_accepted' => $accepted,

                'sunat_description' => $resp['sunat_description'] ?? null,
                'sunat_note' => $resp['sunat_note'] ?? null,
                'sunat_responsecode' => $resp['sunat_responsecode'] ?? null,
                'sunat_soap_error' => $resp['sunat_soap_error'] ?? null,

                'pdf_link' => $resp['enlace_del_pdf'] ?? null,
                'xml_link' => $resp['enlace_del_xml'] ?? null,
                'cdr_link' => $resp['enlace_del_cdr'] ?? null,

                // Primero se considera "enviada" -> SENT, pero como guardamos final en una sola, dejamos final
                // Si quieres guardar el paso intermedio, podrías setear SENT antes y luego el final, pero no es necesario.
                'status' => $finalStatus,
            ]);

            $message = 'Guía creada y enviada correctamente.';

            if ($finalStatus === 'REJECTED') {

                $message = $soapError ?: $description ?: 'SUNAT rechazó la guía.';

            } elseif ($finalStatus === 'PENDING_SUNAT') {

                $message = 'Guía enviada. SUNAT aún no responde.';
            }

            return response()->json([
                'message' => $message,
                'status' => $finalStatus,
                'data' => $guide->fresh(),
                'nubefact' => $resp,
            ]);

        } catch (\Throwable $e) {
            if (!$persisted) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                return response()->json(['message' => 'No se pudo guardar la guía. Revise sus datos.'], 422);
            }

            return response()->json([
                'message' => 'La guía quedó guardada. Consulte su estado antes de intentar emitir otra.',
                'status' => 'SENT',
                'data' => $guide->fresh(),
            ], 502);
        }
    }

    public function exportExcel(Request $request)
    {
        $data = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to'   => ['required', 'date', 'after_or_equal:date_from'],
        ], [
            'date_from.required' => 'Selecciona la fecha "Desde".',
            'date_to.required'   => 'Selecciona la fecha "Hasta".',
            'date_to.after_or_equal' => 'La fecha "Hasta" no puede ser menor que "Desde".',
        ]);

        $from = $data['date_from'];
        $to   = $data['date_to'];

        $filename = "GRE-{$from}-{$to}.xlsx";

        return Excel::download(new ShippingGuidesExport($from, $to, TenantContext::company()), $filename);
    }

    public function consultNubefact($id)
    {
        $guide = ShippingGuide::forCurrentLocal()->where('tipo_de_comprobante', 7)->findOrFail($id);

        try {
            $connection = app(\App\Services\NubefactCompanyConfigService::class)->forCompany(
                (int) $guide->tenant_id, (int) $guide->company_id
            );
            $resp = $this->nubefact->consult($guide->serie, $guide->numero, (int) $guide->tipo_de_comprobante, $connection);

            // Estado según Nubefact/SUNAT
            $accepted = (bool)($resp['aceptada_por_sunat'] ?? false);

            $newStatus = $accepted ? 'ACCEPTED' : 'PENDING_SUNAT';

            // Si SUNAT rechaza, a veces viene sunat_soap_error o sunat_description/responsecode
            $soapError = trim((string)($resp['sunat_soap_error'] ?? ''));
            $responseCode = $resp['sunat_responsecode'] ?? null;

            if (!$accepted && $soapError !== '') {
                $newStatus = 'REJECTED';
            }

            $guide->update([
                'last_nubefact_response' => $resp,

                'nubefact_enlace' => $resp['enlace'] ?? $guide->nubefact_enlace,
                'nubefact_accepted' => $accepted,

                'sunat_description' => $resp['sunat_description'] ?? null,
                'sunat_note' => $resp['sunat_note'] ?? null,
                'sunat_responsecode' => $responseCode,
                'sunat_soap_error' => $soapError,

                'pdf_link' => $resp['enlace_del_pdf'] ?? null,
                'xml_link' => $resp['enlace_del_xml'] ?? null,
                'cdr_link' => $resp['enlace_del_cdr'] ?? null,

                'status' => $newStatus,
            ]);

            return response()->json([
                'message' => $accepted ? 'SUNAT aceptó la guía. Ya hay PDF/XML/CDR.' : 'Guía sigue pendiente en SUNAT.',
                'data' => $guide->fresh(),
                'nubefact' => $resp,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'No se pudo consultar la guía en Nubefact.'], 502);
        }
    }

    private function parseSaleRef(string $ref): array
    {
        $ref = trim($ref);
        if (strpos($ref, '-') === false) {
            throw new \InvalidArgumentException('Formato sale_ref inválido. Usa SERIE-NUMERO (ej: FFF1-6).');
        }
        [$serie, $numero] = explode('-', $ref, 2);
        $serie = trim($serie);
        $numero = trim($numero);

        if ($serie === '' || $numero === '') {
            throw new \InvalidArgumentException('Formato sale_ref inválido. Usa SERIE-NUMERO (ej: FFF1-6).');
        }

        return [$serie, $numero];
    }

    private function buildDetailDescriptionO($saleDetail)
    {
        $name = optional($saleDetail->material)->full_name ?: ('MATERIAL #' . $saleDetail->material_id);

        // Si hay presentación, mostrar (X UND)
        if (!empty($saleDetail->material_presentation_id) && $saleDetail->materialPresentation) {
            $und = $saleDetail->materialPresentation->quantity; // ej: 48
            if (!empty($und)) {
                return $name . ' (' . $und . ' UND)';
            }
            return $name; // por si viniera sin quantity
        }

        // Sin presentación: solo nombre
        return $name;
    }

    private function buildDetailDescription($saleDetail)
    {
        // 🔥 1) Prioridad: stockItem
        if ($saleDetail->stockItem) {
            $name = $saleDetail->stockItem->display_name;
        }
        // 🔁 2) Fallback legacy
        else {
            $name = optional($saleDetail->material)->full_name
                ?: ('MATERIAL #' . $saleDetail->material_id);
        }

        // 📦 3) Si hay presentación, agregar (X UND)
        if (!empty($saleDetail->material_presentation_id) && $saleDetail->materialPresentation) {
            $und = $saleDetail->materialPresentation->quantity;

            if (!empty($und)) {
                return $name . ' (' . $und . ' UND)';
            }

            return $name;
        }

        // 🟢 4) Sin presentación
        return $name;
    }

    public function saleItems(Request $request)
    {
        $request->validate([
            'sale_ref' => ['required', 'string', 'max:40'],
        ]);

        [$serieSunat, $numeroSunat] = $this->parseSaleRef($request->sale_ref);

        $sale = $this->sales()->with(['details.material', 'details.stockItem', 'details.materialPresentation'])
            ->where('serie_sunat', $serieSunat)
            ->where('numero', $numeroSunat)
            ->firstOrFail();

        $items = [];
        $line = 1;

        foreach ($sale->details as $detail) {
            $items[] = [
                'line' => $line++,
                'descripcion' => $this->buildDetailDescription($detail),
                'cantidad' => (float) ($detail->material_presentation_id ? $detail->packs : $detail->quantity),
                'unidad_medida' => 'NIU',
            ];
        }

        return response()->json([
            'sale' => [
                'id' => $sale->id,
                'ref' => $sale->serie_sunat . '-' . $sale->numero,
                'cliente' => $sale->nombre_cliente,
                'doc' => $sale->numero_documento_cliente,
                'fecha' => $sale->fecha_emision,
                'total' => $sale->importe_total,
            ],
            'items' => $items,
        ]);
    }

    public function select2(Request $request)
    {
        $term = trim((string)$request->get('q', ''));
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 10;

        $query = $this->sales()
            ->select(['id','serie_sunat','numero','type_document','nombre_cliente','numero_documento_cliente','fecha_emision','importe_total'])
            ->orderByDesc('id');

        if ($term !== '') {
            $query->where(function($q) use ($term){
                // Por ID
                if (ctype_digit($term)) {
                    $q->orWhere('id', (int)$term);
                }

                // Por "SERIE-NUM"
                if (strpos($term, '-') !== false) {
                    [$s,$n] = explode('-', $term, 2);
                    $s = trim($s); $n = trim($n);
                    if ($s !== '' && $n !== '') {
                        $q->orWhere(function($qq) use ($s,$n){
                            $qq->where('serie_sunat', $s)->where('numero', $n);
                        });
                    }
                }

                // Por cliente / doc
                $q->orWhere('nombre_cliente', 'like', "%{$term}%")
                    ->orWhere('numero_documento_cliente', 'like', "%{$term}%")
                    ->orWhere('serie_sunat', 'like', "%{$term}%");
            });
        }

        $p = $query->paginate($perPage, ['*'], 'page', $page);

        $results = [];
        foreach ($p->items() as $s) {
            $tipo = ($s->type_document === '01') ? 'FACT' : (($s->type_document === '03') ? 'BOL' : 'TICKET');
            $ref = $s->serie_sunat ? ($s->serie_sunat . '-' . $s->numero) : ('#' . $s->id);

            $text = $ref . ' | ' . $tipo . ' | ' . ($s->nombre_cliente ?? '-') .
                ' | ' . ($s->importe_total ?? '-') .
                ' | ' . ($s->fecha_emision ?? '');

            $results[] = [
                'id' => $s->id,
                'text' => $text,
            ];
        }

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $p->hasMorePages()],
        ]);
    }

    public function items(Sale $sale)
    {
        $sale = $this->sales()->findOrFail($sale->id);
        $sale->load(['details.material', 'details.stockItem', 'details.materialPresentation']);

        $items = [];
        $line = 1;
        foreach ($sale->details as $detail) {
            $cant = (!empty($detail->material_presentation_id)) ? ($detail->packs ?: 0) : ($detail->quantity ?: 0);

            $items[] = [
                'line' => $line++,
                'descripcion' => $this->buildDetailDescription($detail),
                'cantidad' => $cant,
                'unidad_medida' => 'NIU',
            ];
        }

        $tipo = ($sale->type_document === '01') ? 'FACTURA' : (($sale->type_document === '03') ? 'BOLETA' : 'TICKET');
        $ref = $sale->serie_sunat ? ($sale->serie_sunat . '-' . $sale->numero) : ('SALE#' . $sale->id);

        return response()->json([
            'sale' => [
                'id' => $sale->id,
                'ref' => $ref,
                'tipo' => $tipo,
                'cliente' => $sale->nombre_cliente,
                'doc' => $sale->numero_documento_cliente,
                'fecha' => $sale->fecha_emision,
                'total' => $sale->importe_total,
            ],
            'items' => $items,
        ]);
    }
}
