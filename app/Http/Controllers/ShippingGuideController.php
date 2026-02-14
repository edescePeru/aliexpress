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
use App\WeightUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShippingGuideController extends Controller
{
    /** @var NubefactGreService */
    private $nubefact;

    public function __construct(NubefactGreService $nubefact)
    {
        $this->nubefact = $nubefact;
    }

    public function index(Request $request)
    {

        $query = ShippingGuide::query()
            ->where('tipo_de_comprobante', 7) // SOLO REMITENTE
            ->orderByDesc('id');

        // TODO filtros luego: fecha_emision rango, serie-numero
        if ($request->date_from) {
            $query->whereDate('fecha_emision', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('fecha_emision', '<=', $request->date_to);
        }

        if ($request->buscar_doc) {
            $buscar = trim($request->buscar_doc);

            if (strpos($buscar, '-') !== false) {
                [$serie, $numero] = explode('-', $buscar);
                $query->where('serie', $serie)
                    ->where('numero', $numero);
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
        $transferReasons = TransferReason::orderBy('code')->get();
        $weightUnits = WeightUnit::orderBy('code')->get();
        $indicators = SunatShippingIndicator::orderBy('code')->get();
        $identityDocTypes = IdentityDocumentType::orderBy('code')->get();

        // Serie por defecto: una por empresa (lo puedes leer de DataGeneral si ya lo tienes)
        $defaultSerie = 'TPD1';

        $customers = Customer::all();

        return view('shipping_guides.create', compact(
            'transferReasons',
            'weightUnits',
            'indicators',
            'identityDocTypes',
            'defaultSerie',
            'customers'
        ));
    }

    public function show($id)
    {
        $guide = ShippingGuide::with(['items', 'vehicles', 'drivers'])
            ->where('tipo_de_comprobante', 7)
            ->findOrFail($id);

        return response()->json(['data' => $guide]);
    }

    public function store(Request $request)
    {
        // Remitente ONLY
        $data = $request->validate([
            'serie' => ['required', 'string', 'max:4'],
            'numero' => ['nullable', 'string', 'max:20'], // editable "por si acaso"

            'fecha_emision' => ['required', 'date'],
            'fecha_inicio_traslado' => ['required', 'date'],

            'motivo_traslado_code' => ['required', 'string', 'size:2'],
            'tipo_transporte' => ['required', 'string', Rule::in(['01', '02'])], // 01 publico, 02 privado

            'peso_bruto_total' => ['required', 'numeric', 'min:0'],
            'peso_bruto_um_code' => ['required', 'string', 'max:3'], // KGM/TNE (de tu seeder)
            'numero_bultos' => ['required', 'integer', 'min:1'],

            'sunat_shipping_indicator_code' => ['nullable', 'string', 'max:60'],

            // Partida/llegada
            'partida_ubigeo' => ['required', 'string', 'size:6'],
            'partida_direccion' => ['required', 'string', 'max:255'],
            'partida_cod_establecimiento' => ['nullable', 'string', 'size:4'],

            'llegada_ubigeo' => ['required', 'string', 'size:6'],
            'llegada_direccion' => ['required', 'string', 'max:255'],
            'llegada_cod_establecimiento' => ['nullable', 'string', 'size:4'],

            // Destinatario snapshot
            'customer_doc_type' => ['required', 'string', 'max:2'],
            'customer_doc_number' => ['required', 'string', 'max:20'],
            'customer_name' => ['required', 'string', 'max:200'],
            'customer_address' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'customer_email_1' => ['nullable', 'email', 'max:120'],
            'customer_email_2' => ['nullable', 'email', 'max:120'],

            'observaciones' => ['nullable', 'string', 'max:2000'],

            // Items mode
            'items_mode' => ['required', Rule::in(['SALE', 'MANUAL'])],
            'sale_ref' => ['required_if:items_mode,SALE', 'nullable', 'string', 'max:40'],

            'items' => ['required_if:items_mode,MANUAL', 'array'],
            'items.*.descripcion' => ['required_if:items_mode,MANUAL', 'string', 'max:255'],
            'items.*.cantidad' => ['required_if:items_mode,MANUAL', 'numeric', 'min:0.001'],
            'items.*.codigo' => ['nullable', 'string', 'max:60'],
            'items.*.detalle_adicional' => ['nullable', 'string', 'max:255'],
        ]);

        // Validaciones condicionales por transporte (Remitente)
        if ($data['tipo_transporte'] === '01') {
            // Público: agencia
            $request->validate([
                'transportista_doc_type' => ['required', 'string', 'max:2'], // normalmente 6
                'transportista_doc_number' => ['required', 'string', 'max:20'],
                'transportista_name' => ['required', 'string', 'max:200'],
                'vehicle_primary_plate' => ['required', 'string', 'max:20'],
            ]);
        } else {
            // Privado: conductor + placa
            $request->validate([
                'vehicle_primary_plate' => ['required', 'string', 'max:20'],

                'driver_primary.document_type_code' => ['required', 'string', 'max:2'],
                'driver_primary.document_number' => ['required', 'string', 'max:20'],
                'driver_primary.first_name' => ['required', 'string', 'max:120'],
                'driver_primary.last_name' => ['required', 'string', 'max:120'],
                'driver_primary.license_number' => ['required', 'string', 'max:30'],

                'vehicles_secondary' => ['nullable', 'array', 'max:2'],
                'vehicles_secondary.*.plate_number' => ['required', 'string', 'max:20'],

                'drivers_secondary' => ['nullable', 'array', 'max:2'],
                'drivers_secondary.*.document_type_code' => ['required', 'string', 'max:2'],
                'drivers_secondary.*.document_number' => ['required', 'string', 'max:20'],
                'drivers_secondary.*.first_name' => ['required', 'string', 'max:120'],
                'drivers_secondary.*.last_name' => ['required', 'string', 'max:120'],
                'drivers_secondary.*.license_number' => ['required', 'string', 'max:30'],
            ]);
        }

        DB::beginTransaction();
        try {
            $guide = ShippingGuide::create([
                'guide_type' => 'REMITENTE',
                'tipo_de_comprobante' => 7,

                'serie' => $data['serie'],
                'numero' => $data['numero'] ?? null,

                'fecha_emision' => $data['fecha_emision'],
                'fecha_inicio_traslado' => $data['fecha_inicio_traslado'],

                'motivo_traslado_code' => $data['motivo_traslado_code'],
                'tipo_transporte' => $data['tipo_transporte'],

                'peso_bruto_total' => $data['peso_bruto_total'],
                'peso_bruto_um_code' => $data['peso_bruto_um_code'],
                'numero_bultos' => $data['numero_bultos'],

                'sunat_shipping_indicator_code' => $data['sunat_shipping_indicator_code'] ?? null,

                'partida_ubigeo' => $data['partida_ubigeo'],
                'partida_direccion' => $data['partida_direccion'],
                'partida_cod_establecimiento' => $data['partida_cod_establecimiento'] ?? '0000',

                'llegada_ubigeo' => $data['llegada_ubigeo'],
                'llegada_direccion' => $data['llegada_direccion'],
                'llegada_cod_establecimiento' => $data['llegada_cod_establecimiento'] ?? '0000',

                'customer_doc_type' => $data['customer_doc_type'],
                'customer_doc_number' => $data['customer_doc_number'],
                'customer_name' => $data['customer_name'],
                'customer_address' => $data['customer_address'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_email_1' => $data['customer_email_1'] ?? null,
                'customer_email_2' => $data['customer_email_2'] ?? null,

                'observaciones' => $data['observaciones'] ?? null,

                'items_mode' => $data['items_mode'],
                'status' => 'DRAFT',
            ]);

            // Público: agencia
            if ($data['tipo_transporte'] === '01') {
                $guide->update([
                    'transportista_doc_type' => $request->input('transportista_doc_type'),
                    'transportista_doc_number' => $request->input('transportista_doc_number'),
                    'transportista_name' => $request->input('transportista_name'),
                ]);
            }

            // Vehículo principal (siempre)
            ShippingGuideVehicle::create([
                'shipping_guide_id' => $guide->id,
                'is_primary' => true,
                'plate_number' => $request->input('vehicle_primary_plate'),
            ]);

            // Privado: conductores y secundarios
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
                    'last_name' => $dp['last_name'],
                    'license_number' => $dp['license_number'],
                ]);

                foreach ((array)$request->input('drivers_secondary', []) as $d) {
                    ShippingGuideDriver::create([
                        'shipping_guide_id' => $guide->id,
                        'is_primary' => false,
                        'document_type_code' => $d['document_type_code'],
                        'document_number' => $d['document_number'],
                        'first_name' => $d['first_name'],
                        'last_name' => $d['last_name'],
                        'license_number' => $d['license_number'],
                    ]);
                }
            }

            // Items
            if ($data['items_mode'] === 'SALE') {
                [$serieSunat, $numeroSunat] = $this->parseSaleRef($data['sale_ref']);

                /** @var Sale $sale */
                $sale = Sale::with(['details.material', 'details.materialPresentation'])
                    ->where('serie_sunat', $serieSunat)
                    ->where('numero', $numeroSunat)
                    ->firstOrFail();

                $guide->update([
                    'source_sale_id' => $sale->id,
                    'source_sale_ref' => $sale->serie_sunat . '-' . $sale->numero,
                    'customer_doc_type' => (string)($sale->tipo_documento_cliente ?? $guide->customer_doc_type),
                    'customer_doc_number' => (string)($sale->numero_documento_cliente ?? $guide->customer_doc_number),
                    'customer_name' => (string)($sale->nombre_cliente ?? $guide->customer_name),
                    'customer_address' => $sale->direccion_cliente ?? $guide->customer_address,
                    'customer_email' => $sale->email_cliente ?? $guide->customer_email,
                ]);

                $line = 1;
                foreach ($sale->details as $detail) {
                    $descripcion = $this->buildDetailDescription($detail);

                    ShippingGuideItem::create([
                        'shipping_guide_id' => $guide->id,
                        'line' => $line++,
                        'product_id' => $detail->material_id,
                        'codigo' => (string)$detail->material_id,
                        'descripcion' => $descripcion,
                        'detalle_adicional' => null,
                        'cantidad' => $detail->quantity,
                        'unidad_medida' => 'NIU',
                    ]);
                }
            } else {
                $line = 1;
                foreach ($data['items'] as $it) {
                    ShippingGuideItem::create([
                        'shipping_guide_id' => $guide->id,
                        'line' => $line++,
                        'product_id' => null,
                        'codigo' => $it['codigo'] ?? null,
                        'descripcion' => $it['descripcion'],
                        'detalle_adicional' => $it['detalle_adicional'] ?? null,
                        'cantidad' => $it['cantidad'],
                        'unidad_medida' => 'NIU',
                    ]);
                }
            }

            // Envío Nubefact
            $guide->load(['items', 'vehicles', 'drivers']);

            $payload = $this->nubefact->buildPayload($guide);
            $guide->update(['last_nubefact_payload' => $payload]);

            $resp = $this->nubefact->send($guide);

            $guide->update([
                'last_nubefact_response' => $resp,
                'nubefact_enlace' => $resp['enlace'] ?? null,
                'nubefact_accepted' => (bool)($resp['aceptada_por_sunat'] ?? false),
                'sunat_description' => $resp['sunat_description'] ?? null,
                'sunat_note' => $resp['sunat_note'] ?? null,
                'sunat_responsecode' => $resp['sunat_responsecode'] ?? null,
                'sunat_soap_error' => $resp['sunat_soap_error'] ?? null,
                'pdf_link' => $resp['enlace_del_pdf'] ?? null,
                'xml_link' => $resp['enlace_del_xml'] ?? null,
                'cdr_link' => $resp['enlace_del_cdr'] ?? null,
                'status' => (($resp['aceptada_por_sunat'] ?? false) ? 'ACCEPTED' : 'PENDING_SUNAT'),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Guía creada y enviada a Nubefact.',
                'data' => $guide->fresh(['items', 'vehicles', 'drivers']),
                'nubefact' => $resp,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear/enviar la guía.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        return response()->json(['message' => 'Pendiente de implementación'], 501);
    }

    public function consultNubefact($id)
    {
        return response()->json(['message' => 'Pendiente de implementación'], 501);
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

    private function buildDetailDescription($saleDetail): string
    {
        $name = optional($saleDetail->material)->name ?? ('MATERIAL #' . $saleDetail->material_id);

        $pres = optional($saleDetail->materialPresentation)->name
            ?? optional($saleDetail->materialPresentation)->descripcion
            ?? null;

        return $pres ? ($name . ' - ' . $pres) : $name;
    }
}
