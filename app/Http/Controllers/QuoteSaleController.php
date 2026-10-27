<?php

namespace App\Http\Controllers;

use App\Audit;
use App\CashBox;
use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\CompanyBankAccount;
use App\CompanyStockItem;
use App\ContactName;
use App\Customer;
use App\DataGeneral;
use App\Equipment;
use App\EquipmentConsumable;
use App\EquipmentElectric;
use App\EquipmentMaterial;
use App\EquipmentTurnstile;
use App\EquipmentWorkday;
use App\EquipmentWorkforce;
use App\Http\Controllers\Traits\NubefactTrait;
use App\Http\Middleware\QuoteSaleBranchContext;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\ImagesQuote;
use App\InventoryLevel;
use App\Item;
use App\Location;
use App\Mail\StockLowNotificationMail;
use App\Material;
use App\MaterialPresentation;
use App\Notification;
use App\NotificationUser;
use App\Output;
use App\OutputDetail;
use App\PaymentDeadline;
use App\PorcentageQuote;
use App\PriceList;
use App\PriceListItem;
use App\PromotionLimit;
use App\PromotionUsage;
use App\Quote;
use App\QuoteMaterialReservation;
use App\QuoteStockLot;
use App\QuoteUser;
use App\ResumenEquipment;
use App\ResumenQuote;
use App\Sale;
use App\SaleDetail;
use App\Services\InventoryCostService;
use App\Services\Quotes\QuoteBranchCatalog;
use App\Services\Quotes\QuoteBranchReadScope;
use App\Services\Quotes\QuoteSaleActions;
use App\Services\QuoteTaxCalculatorService;
use App\Services\SettingService;
use App\StockItem;
use App\StockLot;
use App\Support\TenantContext;
use App\TipoPago;
use App\UnitMeasure;
use App\User;
use App\Warehouse;
use App\Worker;
use App\Workforce;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;
use Intervention\Image\Facades\Image;
use App\Services\QuoteStockReservationService;
use App\Services\PriceResolverService;
use App\Services\TypeTaxResolverService;
use App\Services\SaleEmissionService;
use App\Services\SaleEmissionRecoveryService;

class QuoteSaleController extends Controller
{
    use NubefactTrait;

    /** @var InventoryCostService */
    private $inventoryCostService;

    public function __construct(InventoryCostService $inventoryCostService)
    {
        $this->inventoryCostService = $inventoryCostService;
        $this->middleware(QuoteSaleBranchContext::class);
    }

    public function index()
    {
        $branchScope = app(QuoteBranchReadScope::class);
        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        $companyId = TenantContext::companyId();

        $registros = $branchScope->local(Quote::query())
            ->where('company_id', $companyId)
            ->get();

        $arrayYears = $registros
            ->pluck('date_quote')
            ->filter()
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y');
            })
            ->unique()
            ->values()
            ->toArray();

        /*
         * Customer es Tenant-level, por lo que puede ser compartido
         * por las Companies del mismo Tenant.
         */
        $arrayCustomers = Customer::select(
            'id',
            'business_name'
        )
            ->get()
            ->toArray();

        $arrayStates = [
            [ "value" => "created", "display" => "CREADAS"],
            [ "value" => "send", "display" => "ENVIADAS"],
            [ "value" => "confirm", "display" => "CONFIRMADAS"],
            [ "value" => "raised", "display" => "ELEVADAS"],
            [ "value" => "requote", "display" => "PARA RECOTIZAR"],
            [ "value" => "close", "display" => "FINALIZADOS"],
            [ "value" => "canceled", "display" => "ANULADAS"]
        ];

        $arrayUsers = User::select(
            'id',
            'name'
        )
            ->get()
            ->toArray();

        return view(
            'quoteSale.general',
            compact(
                'permissions',
                'arrayYears',
                'arrayCustomers',
                'arrayStates',
                'arrayUsers'
            )
        );
    }

    public function indexFacturadas()
    {
        $branchScope = app(QuoteBranchReadScope::class);
        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        $companyId = TenantContext::companyId();

        $registros = $branchScope->local(Quote::query())
            ->where('company_id', $companyId)
            ->get();

        $arrayYears = $registros
            ->pluck('date_quote')
            ->filter()
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y');
            })
            ->unique()
            ->values()
            ->toArray();

        $arrayCustomers = Customer::select(
            'id',
            'business_name'
        )
            ->get()
            ->toArray();

        $arrayStates = [
            [ "value" => "created", "display" => "CREADAS"],
            [ "value" => "send", "display" => "ENVIADAS"],
            [ "value" => "confirm", "display" => "CONFIRMADAS"],
            [ "value" => "raised", "display" => "ELEVADAS"],
            [ "value" => "close", "display" => "FINALIZADOS"],
            [ "value" => "canceled", "display" => "CANCELADAS"]
        ];

        $arrayUsers = User::select(
            'id',
            'name'
        )
            ->get()
            ->toArray();

        return view(
            'quoteSale.facturadas',
            compact(
                'permissions',
                'arrayYears',
                'arrayCustomers',
                'arrayStates',
                'arrayUsers'
            )
        );
    }

    public function getMaterials(Request $request)
    {
        $companyId = TenantContext::companyId();
        $catalog = app(QuoteBranchCatalog::class);

        $search = trim(
            (string) $request->get('q', '')
        );

        $query = StockItem::query()
            ->with([
                'material:id,full_name,code,enable_status,unit_measure_id',
                'material.unitMeasure:id,name',
                'variant:id,material_id,attribute_summary,talla_id,color_id',
                'variant.talla:id,name,short_name',
                'variant.color:id,name,short_name',
                'unitMeasure:id,name',
            ])
            ->where('is_active', true)
            ->enabledForCompany($companyId)
            ->whereHas('material', function ($materialQuery) {
                $materialQuery->where('enable_status', 1);
            });

        /*
         * Buscador.
         *
         * Permitimos buscar por:
         * - nombre del Material
         * - display_name del StockItem
         * - SKU
         * - barcode
         */
        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where(
                        'display_name',
                        'LIKE',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'sku',
                        'LIKE',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'barcode',
                        'LIKE',
                        '%' . $search . '%'
                    )
                    ->orWhereHas('material', function ($materialQuery) use ($search) {
                        $materialQuery->where(
                            'full_name',
                            'LIKE',
                            '%' . $search . '%'
                        );
                    });
            });
        }

        $catalog->catalog($query);

        $stockItems = $query
            ->orderBy('display_name', 'asc')
            ->limit(50)
            ->get();

        $results = $stockItems->map(function ($stockItem) {
            $material = $stockItem->material;

            $variantText = $this->getVariantText(
                $stockItem
            );

            $text = $stockItem->display_name
                ?: optional($material)->full_name
                    ?: ('Producto #' . $stockItem->id);

            if ($variantText) {
                $text .= ' - ' . $variantText;
            }

            if (!empty($stockItem->sku)) {
                $text .= ' | SKU: ' . $stockItem->sku;
            }

            return [
                'id' => $stockItem->id,

                'type' => 'stock_item',

                'material_id' => $stockItem->material_id,

                'stock_item_id' => $stockItem->id,

                'text' => $text,

                'full_name' => optional($material)->full_name,

                'display_name' => $stockItem->display_name,

                'variant_text' => $variantText,

                'unit' => optional($stockItem->unitMeasure)->name
                    ?: optional(optional($material)->unitMeasure)->name,

                'code' => optional($material)->code,

                'sku' => $stockItem->sku,

                'barcode' => $stockItem->barcode,
            ];
        });

        return response()->json(
            $results->values()
        );
    }

    public function getMaterialTotals()
    {
        $catalog = app(QuoteBranchCatalog::class);
        $catalog->validateContext();
        $companyId =
            TenantContext::companyId();

        /*
         * ============================================================
         * SERVICIOS
         * ============================================================
         */

        /** @var PriceResolverService $priceResolver */
        $priceResolver =
            app(
                PriceResolverService::class
            );

        /** @var TypeTaxResolverService $typeTaxResolver */
        $typeTaxResolver =
            app(
                TypeTaxResolverService::class
            );

        /*
         * ============================================================
         * STOCKITEMS DISPONIBLES PARA LA LOCAL ACTUAL
         * ============================================================
         */

        $stockItems =
            $catalog->catalog(StockItem::query())
                ->with([
                    'material' =>
                        function ($query) {

                            $query->with([
                                'unitMeasure',
                                'typeScrap',
                            ]);
                        },

                    'variant:id,material_id,attribute_summary,talla_id,color_id',

                    'variant.talla:id,name,short_name',

                    'variant.color:id,name,short_name',

                    'unitMeasure:id,name',

                    /*
                     * Inventario únicamente
                     * del local actual.
                     */
                    'inventoryLevels' =>
                        function ($query) use (
                            $companyId, $catalog
                        ) {

                            $catalog->levels($query);
                            $query
                                ->where(
                                    'company_id',
                                    $companyId
                                )
                                ->select([
                                    'id',
                                    'company_id',
                                    'stock_item_id',
                                    'qty_on_hand',
                                    'qty_reserved',
                                ]);
                        },
                ])
                ->where(
                    'is_active',
                    true
                )
                ->enabledForCompany(
                    $companyId
                )
                ->whereHas(
                    'material',
                    function ($query) {

                        $query->where(
                            'enable_status',
                            1
                        );
                    }
                )
                ->orderBy(
                    'display_name',
                    'asc'
                )
                ->get();

        $array = [];

        foreach (
            $stockItems
            as $stockItem
        ) {

            $material =
                $stockItem->material;

            if (!$material) {
                continue;
            }

            /*
             * ============================================================
             * VARIANTE
             * ============================================================
             */

            $variantText =
                $this->getVariantText(
                    $stockItem
                );

            $fullDescription =
                $stockItem
                    ->display_name
                    ?: $material
                    ->full_name;

            if ($variantText) {

                $fullDescription .=
                    ' - ' .
                    $variantText;
            }

            /*
             * ============================================================
             * INVENTARIO DE LA LOCAL ACTUAL
             * ============================================================
             */

            $stockCurrent =
                (float)
                $stockItem
                    ->inventoryLevels
                    ->sum(
                        function ($level) {

                            return (float) (
                                $level
                                    ->qty_on_hand
                                ?? 0
                            );
                        }
                    );

            $stockReserved =
                (float)
                $stockItem
                    ->inventoryLevels
                    ->sum(
                        function ($level) {

                            return (float) (
                                $level
                                    ->qty_reserved
                                ?? 0
                            );
                        }
                    );

            $stockAvailable =
                $stockCurrent
                -
                $stockReserved;

            /*
             * ============================================================
             * PRECIO
             * ============================================================
             *
             * Regla:
             *
             * 1. PriceListItem
             * 2. PriceListMaterial
             * 3. null = sin precio
             *
             * IMPORTANTE:
             *
             * El precio devuelto es precio final
             * con impuesto incluido cuando corresponda.
             */

            $priceData =
                $priceResolver
                    ->resolveWithSource(
                        $stockItem,
                        $companyId
                    );

            $listPrice =
                $priceData[
                'price'
                ];

            /*
             * Para compatibilidad con create.js
             * seguimos enviando list_price numérico.
             *
             * Pero has_price nos permite diferenciar:
             *
             * precio 0 válido
             *
             * vs
             *
             * producto sin precio configurado.
             */

            $hasPrice =
                $listPrice !== null;

            /*
             * ============================================================
             * METADATA TRIBUTARIA
             * ============================================================
             *
             * Cada producto obtiene su impuesto
             * directamente desde el Material.
             *
             * Esto permite que dentro de una misma
             * cotización existan:
             *
             * - IGV
             * - Exonerados
             * - Inafectos
             */

            $taxData =
                $typeTaxResolver
                    ->resolveMaterialWithSource(
                        $material
                    );

            /*
             * ============================================================
             * RESPONSE
             * ============================================================
             */

            $array[] = [

                /*
                 * Identidad.
                 */
                'id' =>
                    $stockItem->id,

                'type' =>
                    'stock_item',

                'material_id' =>
                    $material->id,

                'stock_item_id' =>
                    $stockItem->id,

                /*
                 * Descripción.
                 */
                'full_description' =>
                    $fullDescription,

                'display_name' =>
                    $stockItem
                        ->display_name,

                'variant_text' =>
                    $variantText,

                /*
                 * Unidad.
                 */
                'unit' =>
                    optional(
                        $stockItem
                            ->unitMeasure
                    )->name
                        ?:
                        optional(
                            $material
                                ->unitMeasure
                        )->name
                            ?: '',

                /*
                 * Códigos.
                 */
                'code' =>
                    $material->code,

                'sku' =>
                    $stockItem->sku,

                'barcode' =>
                    $stockItem->barcode,

                /*
                 * Metadata Material.
                 */
                'type_scrap' =>
                    $material
                        ->typeScrap,

                'tipo_venta_id' =>
                    $material
                        ->tipo_venta_id,

                'unit_measure' =>
                    $stockItem
                        ->unitMeasure
                        ?:
                        $material
                            ->unitMeasure,

                /*
                 * ========================================================
                 * PRECIO
                 * ========================================================
                 */

                'list_price' =>
                    $hasPrice
                        ? (float)
                    $listPrice
                        : 0,

                'has_price' =>
                    $hasPrice,

                'price_source' =>
                    $priceData[
                    'source'
                    ],

                'price_list_id' =>
                    $priceData[
                    'price_list_id'
                    ],

                'price_list_name' =>
                    $priceData[
                    'price_list_name'
                    ],

                /*
                 * ========================================================
                 * ESTADO
                 * ========================================================
                 */

                'enable_status' =>
                    $material
                        ->enable_status,

                /*
                 * ========================================================
                 * INVENTARIO
                 * ========================================================
                 */

                'stock_current' =>
                    $stockCurrent,

                'stock_reserved' =>
                    $stockReserved,

                'stock_available' =>
                    $stockAvailable,

                /*
                 * Legacy.
                 */
                'state_update_price' =>
                    $material
                        ->state_update_price,

                /*
                 * ========================================================
                 * TRIBUTACIÓN
                 * ========================================================
                 *
                 * El frontend utiliza estos datos únicamente
                 * para previsualizar los cálculos.
                 *
                 * El backend sigue siendo la autoridad.
                 */

                'type_tax_id' =>
                    (int)
                    $taxData[
                    'type_tax_id'
                    ],

                'tax_rate' =>
                    (float)
                    $taxData[
                    'tax_rate'
                    ],

                'tax_code' =>
                    $taxData[
                    'code'
                    ],
            ];
        }

        return $array;
    }

    private function getVariantText($stockItem)
    {
        $variantText = '';

        if ($stockItem->variant) {
            if (!empty($stockItem->variant->attribute_summary)) {
                $variantText = $stockItem->variant->attribute_summary;
            } else {
                $talla = optional($stockItem->variant->quality)->short_name
                    ?: optional($stockItem->variant->quality)->name;

                $color = optional($stockItem->variant->color)->name;

                $variantText = collect([$talla, $color])
                    ->filter()
                    ->implode(' / ');
            }
        }

        return $variantText;
    }

    public function getAvailableItemsByStockItem($stockItemId) {
        $companyId = TenantContext::companyId();
        $catalog = app(QuoteBranchCatalog::class);
        $catalog->validateContext();

        /*
         * ============================================================
         * VALIDAR STOCKITEM
         * ============================================================
         *
         * StockItem ya tiene aislamiento por Tenant gracias
         * a BelongsToTenant.
         *
         * Además exigimos que esté habilitado comercialmente
         * para la Company actual.
         */

        $stockItem = StockItem::query()
            ->where(
                'id',
                $stockItemId
            )
            ->where(
                'is_active',
                true
            )
            ->enabledForCompany(
                $companyId
            )
            ->whereHas('material', function ($q) { $q->where('enable_status', 1); })
            ->first();

        if (!$stockItem) {
            return response()->json([
                'success' => false,
                'message' =>
                    'El producto no existe o no está habilitado para la empresa actual.',
                'items' => [],
            ], 404);
        }

        /*
         * ============================================================
         * ITEMS DISPONIBLES
         * ============================================================
         */

        $items = $catalog->items(Item::query(), (int) $stockItem->id)
            ->with([
                'stockLot' => function ($query) use ($companyId) {
                    $query
                        ->where(
                            'company_id',
                            $companyId
                        )
                        ->select([
                            'id',
                            'company_id',
                            'lot_code',
                        ]);
                },

                'warehouse:id,name',
            ])
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'stock_item_id',
                $stockItem->id
            )
            ->where(
                'percentage',
                1
            )
            ->where(
                'state_item',
                'entered'
            )
            ->orderBy(
                'code',
                'asc'
            )
            ->get([
                'id',
                'tenant_id',
                'company_id',
                'stock_item_id',
                'material_id',
                'stock_lot_id',
                'code',
                'warehouse_id',
                'location_id',
                'state_item',
                'percentage',
            ]);

        return response()->json([
            'success' => true,

            'items' => $items
                ->map(function ($item) {
                    $lotCode = optional(
                        $item->stockLot
                    )->lot_code;

                    if (
                    empty(
                    trim(
                        (string) $lotCode
                    )
                    )
                    ) {
                        $lotCode = 'Sin Lote';
                    }

                    $warehouseName = optional(
                        $item->warehouse
                    )->name;

                    if (
                    empty(
                    trim(
                        (string) $warehouseName
                    )
                    )
                    ) {
                        $warehouseName =
                            'Sin almacén';
                    }

                    return [
                        'id' => $item->id,

                        'code' => $item->code
                            ?: (
                                'Ítem #' .
                                $item->id
                            ),

                        'stock_item_id' =>
                            $item->stock_item_id,

                        'material_id' =>
                            $item->material_id,

                        'stock_lot_id' =>
                            $item->stock_lot_id,

                        'stock_lot_code' =>
                            $lotCode,

                        'lot_code' =>
                            $lotCode,

                        'warehouse_id' =>
                            $item->warehouse_id,

                        'warehouse_name' =>
                            $warehouseName,

                        'location_id' =>
                            $item->location_id,

                        'state_item' =>
                            $item->state_item,
                    ];
                })
                ->values(),
        ]);
    }

    public function create()
    {
        $begin = microtime(true);

        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        /*
         * ============================================================
         * CATÁLOGOS NECESARIOS PARA LA VISTA
         * ============================================================
         */

        $unitMeasures =
            UnitMeasure::all();

        /*
         * Customer es Tenant-level.
         *
         * BelongsToTenant ya se encarga
         * del aislamiento por Tenant.
         */
        $customers =
            Customer::query()
                ->orderBy(
                    'business_name',
                    'asc'
                )
                ->get();

        /*
         * Servicios adicionales disponibles.
         */
        $workforces =
            Workforce::with(
                'unitMeasure'
            )
                ->get();

        /*
         * ============================================================
         * CÓDIGO REFERENCIAL
         * ============================================================
         *
         * Este código solo se muestra en la vista.
         *
         * El código definitivo se genera en store()
         * después de crear la Quote y conocer su ID real.
         */

        $maxId =
            (
                Quote::max('id')
                ?? 0
            )
            + 1;

        $codeQuote =
            'COT-' .
            str_pad(
                $maxId,
                5,
                '0',
                STR_PAD_LEFT
            );

        /*
         * ============================================================
         * PLAZOS DE PAGO
         * ============================================================
         */

        $paymentDeadlines =
            PaymentDeadline::query()
                ->where(
                    'type',
                    'quotes'
                )
                ->selectable()->get();

        /*
         * ============================================================
         * MONEDA BASE DE LA COMPANY ACTUAL
         * ============================================================
         */

        $currency =
            app(
                SettingService::class
            )->get(
                'finance.base_currency'
            );

        /*
         * ============================================================
         * IMPUESTO DEFAULT
         * ============================================================
         *
         * Se utiliza principalmente para servicios,
         * porque estos no pertenecen a un Material.
         *
         * Los productos resolverán su TypeTax
         * individualmente desde su Material.
         */

        /** @var TypeTaxResolverService $typeTaxResolver */
        $typeTaxResolver =
            app(
                TypeTaxResolverService::class
            );

        $defaultTax =
            $typeTaxResolver
                ->resolveWithSource();

        /*
         * ============================================================
         * AUDITORÍA
         * ============================================================
         */

        $end =
            microtime(true)
            -
            $begin;

        Audit::create([
            'user_id' =>
                $user->id,

            'action' =>
                'Crear cotizacion VISTA',

            'time' =>
                $end,
        ]);

        /*
         * ============================================================
         * VISTA
         * ============================================================
         */

        return view(
            'quoteSale.create',
            compact(
                'currency',
                'customers',
                'unitMeasures',
                'workforces',
                'codeQuote',
                'permissions',
                'paymentDeadlines',
                'defaultTax'
            )
        );
    }

    /**
     * Store Quote
     *
     * IMPORTANTE (según cambios recientes):
     * - Consumables pueden venir como:
     *   - Unitario: quantity = unidades, presentation_id = null, units_equivalent = quantity (opcional)
     *   - Presentación: quantity = packs, presentation_id != null, units_per_pack viene del front,
     *                   pero el backend recalcula units_per_pack desde BD y units_equivalent = packs * units_per_pack
     *
     * - Para mantener coherencia con reservas/stock:
     *   - Reservamos y descontamos stock_reserved en UNIDADES EQUIVALENTES (units_equivalent).
     *
     * - Workforces (servicios adicionales):
     *   - Puede venir billable (1/0). Si billable = 0, igual lo guardamos, pero en facturación se excluirá.
     */
    public function storeO(StoreQuoteRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        DB::beginTransaction();
        try {

            $maxCode = Quote::max('id');
            $maxId = $maxCode + 1;
            $length = 5;

            $quote = Quote::create([
                'code' => '',
                'description_quote' => $request->get('code_description'),
                'observations' => $request->get('observations'),

                'date_quote' => ($request->filled('date_quote'))
                    ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote'))
                    : Carbon::now(),

                'date_validate' => ($request->filled('date_validate'))
                    ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate'))
                    : Carbon::now()->addDays(5),

                'way_to_pay' => $request->get('way_to_pay', ''),
                'delivery_time' => $request->get('delivery_time', ''),

                'customer_id' => $request->get('customer_id'),
                'contact_id' => $request->get('contact_id'),
                'payment_deadline_id' => $request->get('payment_deadline'),

                'state' => 'created',
                'currency_invoice' => $currency,

                // Totales (front)
                //'descuento' => $request->get('descuento'),
                'descuento' => $request->get('descuentoReal'),
                'discount_type' => $request->get('discount_type'),                 // amount|percent|null
                'discount_input_mode' => $request->get('discount_input_mode'),     // with_igv|without_igv|null
                'discount_input_value' => $request->get('discount_input_value'),   // decimal|null

                /*'gravada' => $request->get('gravada'),
                'igv_total' => $request->get('igv_total'),
                'total_importe' => $request->get('total_importe'),*/
                'gravada' => $request->get('gravadaReal'),
                'igv_total' => $request->get('igvReal'),
                'total_importe' => $request->get('totalReal'),
            ]);

            // Código
            $codeQuote = ($maxId < $quote->id)
                ? 'COT-' . str_pad($quote->id, $length, "0", STR_PAD_LEFT)
                : 'COT-' . str_pad($maxId, $length, "0", STR_PAD_LEFT);

            $quote->code = $codeQuote;
            $quote->save();

            QuoteUser::create([
                'quote_id' => $quote->id,
                'user_id' => Auth::user()->id,
            ]);

            $equipments = json_decode($request->get('equipments')) ?? [];

            // IGV: si tienes una variable/config global, reemplaza
            $igvPct = 18.0;
            $igvFactor = 1 + ($igvPct / 100);

            for ($i = 0; $i < sizeof($equipments); $i++) {

                $equipment = Equipment::create([
                    'quote_id' => $quote->id,
                    'description' => ($equipments[$i]->description ?? '') ?: '',
                    'detail' => ($equipments[$i]->detail ?? '') ?: '',
                    'quantity' => $equipments[$i]->quantity ?? 1,
                    'utility' => $equipments[$i]->utility ?? 0,
                    'rent' => $equipments[$i]->rent ?? 0,
                    'letter' => $equipments[$i]->letter ?? 0,
                    'total' => $equipments[$i]->total ?? 0,
                ]);

                $totalMaterial = 0;
                $totalConsumable = 0;
                $totalElectric = 0;
                $totalWorkforces = 0;
                $totalTornos = 0;
                $totalDias = 0;

                $materials = $equipments[$i]->materials ?? [];
                $consumables = $equipments[$i]->consumables ?? [];
                $electrics = $equipments[$i]->electrics ?? [];
                $workforces = $equipments[$i]->workforces ?? [];
                $tornos = $equipments[$i]->tornos ?? [];
                $dias = $equipments[$i]->dias ?? [];

                // MATERIALS (sin cambios)
                for ($j = 0; $j < sizeof($materials); $j++) {
                    $equipmentMaterial = EquipmentMaterial::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $materials[$j]->material->id,
                        'quantity' => (float)$materials[$j]->quantity,
                        'price' => (float)$materials[$j]->material->unit_price,
                        'length' => (float)(($materials[$j]->length ?? '') == '' ? 0 : $materials[$j]->length),
                        'width' => (float)(($materials[$j]->width ?? '') == '' ? 0 : $materials[$j]->width),
                        'percentage' => (float)$materials[$j]->quantity,
                        'state' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Falta comprar' : 'En compra',
                        'availability' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Agotado' : 'Completo',
                        'total' => (float)$materials[$j]->quantity * (float)$materials[$j]->material->unit_price,
                    ]);

                    $totalMaterial += $equipmentMaterial->total;
                }

                // CONSUMABLES (con presentaciones)
                // Recomendado para precisión decimal (si tienes bcmath)
                @bcscale(10);

                $totalConsumable = '0';

                for ($k = 0; $k < sizeof($consumables); $k++) {

                    $c = $consumables[$k];

                    $materialId = (int)($c->id ?? 0);
                    $material = Material::find($materialId);

                    if (!$material) {
                        throw new \Exception("El material con ID {$materialId} no existe.");
                    }

                    // ============================
                    // 1) Inputs del front (reales)
                    // ============================
                    // quantity: packs si hay presentación, unidades si no hay presentación
                    $frontQty = (float)($c->quantity ?? 0);
                    if ($frontQty <= 0) {
                        throw new \Exception("Cantidad inválida para material {$material->full_name}.");
                    }

                    $presentationId = $c->presentation_id ?? null;

                    // ✅ Estos 3 son los que mandas y NO debemos recalcular:
                    $valorUnitarioReal = (string)($c->valorReal ?? $c->valor ?? '0'); // sin IGV (pack o unidad)
                    $priceReal         = (string)($c->priceReal ?? $c->price ?? '0'); // con IGV (pack o unidad)
                    $importeReal       = (string)($c->importe ?? '0');                // total con IGV (ya calculado)

                    if ((float)$valorUnitarioReal < 0 || (float)$priceReal < 0 || (float)$importeReal < 0) {
                        throw new \Exception("Valores inválidos (precio/valor/total) para {$material->full_name}.");
                    }

                    // ============================
                    // 2) Presentación SOLO para stock (unidades equivalentes)
                    // ============================
                    $packs = null;
                    $unitsPerPack = null;

                    if (!empty($presentationId)) {

                        $packs = (int)$frontQty;
                        if ($packs < 1) {
                            throw new \Exception("Cantidad de paquetes inválida para material {$material->full_name}.");
                        }

                        $presentation = MaterialPresentation::where('id', $presentationId)
                            ->where('material_id', $materialId)
                            ->where('active', 1)
                            ->first();

                        if (!$presentation) {
                            throw new \Exception("La presentación seleccionada no es válida o no pertenece al material {$material->full_name}.");
                        }

                        $unitsPerPack = (int)$presentation->quantity;
                        if ($unitsPerPack < 1) {
                            throw new \Exception("La presentación tiene cantidad inválida para {$material->full_name}.");
                        }

                        $requestedUnits = (float)($packs * $unitsPerPack);

                    } else {
                        // Unitario: units_equivalent debe ser unidades (si no viene, usa frontQty)
                        $requestedUnits = (float)($c->units_equivalent ?? $frontQty);

                        if ($requestedUnits <= 0) {
                            throw new \Exception("Cantidad inválida para material {$material->full_name}.");
                        }
                    }

                    // ============================
                    // 3) Stock disponible
                    // ============================
                    $available = (float)$material->stock_current - (float)$material->stock_reserved;

                    if ($requestedUnits > $available) {
                        throw new \Exception(
                            "El material {$material->full_name} no cuenta con stock suficiente. " .
                            "Requerido: {$requestedUnits}. Disponible: {$available}."
                        );
                    }

                    $availability = ($requestedUnits > (float)$material->stock_current) ? 'Agotado' : 'Completo';
                    $state        = ($requestedUnits > (float)$material->stock_current) ? 'Falta comprar' : 'En compra';

                    // ============================
                    // 4) Guardar consumable (decimales reales)
                    // ============================
                    $equipmentConsumable = EquipmentConsumable::create([
                        'availability' => $availability,
                        'state' => $state,
                        'equipment_id' => $equipment->id,
                        'material_id' => $materialId,

                        // ✅ SIEMPRE stock en unidades equivalentes
                        'quantity' => $requestedUnits,

                        // ✅ Guardar EXACTO lo real que viene del front (decimal 20,10)
                        'price' => $priceReal,                    // con IGV (pack o unidad)
                        'valor_unitario' => $valorUnitarioReal,   // sin IGV (pack o unidad)
                        'total' => $importeReal,                  // total con IGV (ya calculado)

                        'discount' => (string)($c->discount ?? '0'),
                        'type_promo' => $c->type_promo ?? null,

                        'material_presentation_id' => $presentationId,
                        'packs' => $packs,
                        'units_per_pack' => $unitsPerPack,
                    ]);

                    // ============================
                    // 5) Total consumables con precisión
                    // ============================
                    // Ideal: sumar el mismo string que guardas (importeReal)
                    $totalConsumable = bcadd($totalConsumable, $importeReal, 10);

                    // ============================
                    // 6) Reservas (unidades equivalentes)
                    // ============================
                    $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                        ->where('material_id', $materialId)
                        ->lockForUpdate()
                        ->first();

                    if ($reservation) {
                        $reservation->quantity += $requestedUnits;
                        $reservation->save();
                    } else {
                        QuoteMaterialReservation::create([
                            'quote_id' => $quote->id,
                            'material_id' => $materialId,
                            'quantity' => $requestedUnits,
                        ]);
                    }

                    $material->stock_reserved += $requestedUnits;
                    $material->save();

                    // ============================
                    // 7) Promo limit (valida y registra uso) - usa unidades equivalentes
                    // ============================
                    if (($c->type_promo ?? null) === "limit") {

                        $promotion = PromotionLimit::where('material_id', $materialId)
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->first();

                        if ($promotion) {
                            $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                            if ($promotion->applies_to === 'worker') {
                                $query->where('user_id', auth()->id());
                            }

                            $usage = $query->first();

                            // ✅ Validar antes de sumar
                            $alreadyUsed = $usage ? (float)$usage->used_quantity : 0.0;
                            $remaining = (float)$promotion->limit_quantity - $alreadyUsed;

                            if ($remaining < $requestedUnits) {
                                throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                            }

                            if (!$usage) {
                                PromotionUsage::create([
                                    'promotion_limit_id' => $promotion->id,
                                    'user_id' => $promotion->applies_to === 'worker' ? auth()->id() : null,
                                    'used_quantity' => $requestedUnits,
                                    'equipment_id' => $equipment->id,
                                    'equipment_consumable_id' => $equipmentConsumable->id,
                                    'quote_id' => $equipment->quote_id,
                                ]);
                            } else {
                                $usage->used_quantity += $requestedUnits;
                                $usage->equipment_id = $equipment->id;
                                $usage->equipment_consumable_id = $equipmentConsumable->id;
                                $usage->quote_id = $equipment->quote_id;
                                $usage->save();
                            }
                        }
                    }
                }

                // WORKFORCES (servicios adicionales)
                // - Guarda billable
                // - SIEMPRE suma al total del equipo/cotización
                for ($w = 0; $w < sizeof($workforces); $w++) {

                    $billable = isset($workforces[$w]->billable)
                        ? (int)$workforces[$w]->billable
                        : 1;

                    $equipmentWorkforce = EquipmentWorkforce::create([
                        'equipment_id' => $equipment->id,
                        'description' => $workforces[$w]->description ?? '',
                        'price' => (float)($workforces[$w]->price ?? 0),
                        'quantity' => (float)($workforces[$w]->quantity ?? 0),
                        'total' => (float)($workforces[$w]->importe ?? ($workforces[$w]->total ?? 0)),
                        'unit' => $workforces[$w]->unit ?? '',
                        'billable' => $billable,
                    ]);

                    $totalWorkforces += $equipmentWorkforce->total;
                }

                // Totales del equipo (sin cambios)
                $totalEquipo = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity);
                $totalEquipmentU = $totalEquipo * ((($equipment->utility / 100) + 1));
                $totalEquipmentL = $totalEquipmentU * ((($equipment->letter / 100) + 1));
                $totalEquipmentR = $totalEquipmentL * ((($equipment->rent / 100) + 1));

                //$totalQuote += $totalEquipmentR;

                $equipment->total = $totalEquipo;
                $equipment->save();
            }

            $quote->save();

            $notification = Notification::create([
                'content' => $quote->code . ' creada por ' . Auth::user()->name,
                'reason_for_creation' => 'create_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('quote.edit', $quote->id)
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $user) {
                if ($user->id != Auth::user()->id) {
                    foreach ($user->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false,
                            'date_read' => null,
                            'date_delete' => null
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;
            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar cotizacion POST',
                'time' => $end
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }

        return response()->json(['message' => 'Cotización ' . $codeQuote . ' guardada con éxito.'], 200);
    }

    public function store(StoreQuoteRequest $request)
    {
        $begin = microtime(true);

        $validated = $request->validated();

        $companyId = TenantContext::companyId();

        $currency = app(
            SettingService::class
        )->get('finance.base_currency');

        /*
         * ============================================================
         * VALIDAR CUSTOMER
         * ============================================================
         */

        $customer = Customer::query()
            ->where(
                'id',
                $request->get('customer_id')
            )
            ->firstOrFail();

        /*
         * ============================================================
         * VALIDAR CONTACTO
         * ============================================================
         */

        $contactId = $request->get('contact_id');

        $contact = null;

        if (!empty($contactId)) {
            $contact = ContactName::query()
                ->where('id', $contactId)
                ->where(
                    'customer_id',
                    $customer->id
                )
                ->firstOrFail();
        }

        /*
         * ============================================================
         * VALIDAR FORMA / PLAZO DE PAGO
         * ============================================================
         */

        $paymentDeadlineId =
            $request->get('payment_deadline');

        $paymentDeadline = null;

        if (!empty($paymentDeadlineId)) {
            $paymentDeadline =
                PaymentDeadline::query()
                    ->where(
                        'id',
                        $paymentDeadlineId
                    )
                    ->where(
                        'type',
                        'quotes'
                    )
                    ->selectable()->firstOrFail();
        }

        /*
         * ============================================================
         * EQUIPMENT
         * ============================================================
         */

        $equipments = json_decode(
                $request->get('equipments')
            ) ?? [];

        if (!is_array($equipments)) {
            throw new \RuntimeException(
                'El formato de equipos es inválido.'
            );
        }

        /*
         * Regla funcional actual:
         * una cotización contiene exactamente un Equipment.
         */
        if (count($equipments) !== 1) {
            throw new \RuntimeException(
                'La cotización debe contener exactamente un equipo.'
            );
        }

        DB::beginTransaction();

        try {

            /*
             * ========================================================
             * CREAR QUOTE
             * ========================================================
             *
             * El código temporal evita colisiones con el UNIQUE
             * mientras todavía no conocemos el ID real.
             */

            $quote = Quote::create([
                'branch_id' => TenantContext::branchId(),
                'company_id' => $companyId,

                'code' =>
                    'TMP-' .
                    Str::uuid()->toString(),

                'description_quote' =>
                    $request->get(
                        'code_description'
                    ),

                'observations' =>
                    $request->get(
                        'observations'
                    ),

                'date_quote' =>
                    $request->filled(
                        'date_quote'
                    )
                        ? Carbon::createFromFormat(
                        'd/m/Y',
                        $request->get(
                            'date_quote'
                        )
                    )
                        : Carbon::now(),

                'date_validate' =>
                    $request->filled(
                        'date_validate'
                    )
                        ? Carbon::createFromFormat(
                        'd/m/Y',
                        $request->get(
                            'date_validate'
                        )
                    )
                        : Carbon::now()
                        ->addDays(5),

                'way_to_pay' =>
                    $request->get(
                        'way_to_pay',
                        ''
                    ),

                'delivery_time' =>
                    $request->get(
                        'delivery_time',
                        ''
                    ),

                'customer_id' =>
                    $customer->id,

                'contact_id' =>
                    $contact
                        ? $contact->id
                        : null,

                'payment_deadline_id' =>
                    $paymentDeadline
                        ? $paymentDeadline->id
                        : null,

                'state' => 'created',

                'currency_invoice' =>
                    $currency,

                'discount_type' =>
                    $request->get(
                        'discount_type'
                    ),

                'discount_input_mode' =>
                    $request->get(
                        'discount_input_mode'
                    ),

                'discount_input_value' =>
                    $request->get(
                        'discount_input_value'
                    ),

                'descuento' => 0,

                'gravada' => 0,

                'exonerada' => 0,

                'inafecta' => 0,

                'igv_total' => 0,

                'total_importe' => 0,
            ]);

            /*
             * ========================================================
             * CÓDIGO DEFINITIVO
             * ========================================================
             */

            $codeQuote =
                'COT-' .
                str_pad(
                    $quote->id,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

            $quote->code = $codeQuote;
            $quote->save();

            /*
             * ========================================================
             * USUARIO CREADOR
             * ========================================================
             */

            QuoteUser::create([
                'quote_id' =>
                    $quote->id,

                'user_id' =>
                    Auth::user()->id,
            ]);

            /*
             * ========================================================
             * EQUIPMENT ÚNICO
             * ========================================================
             */

            $equipmentData =
                $equipments[0];

            $equipment = Equipment::create([
                'quote_id' =>
                    $quote->id,

                'description' =>
                    ($equipmentData->description ?? '')
                        ?: '',

                'detail' =>
                    ($equipmentData->detail ?? '')
                        ?: '',

                /*
                 * Regla funcional:
                 * una cotización siempre tiene un único Equipment
                 * con quantity = 1.
                 */
                'quantity' => 1,

                'utility' =>
                    $equipmentData->utility ?? 0,

                'rent' =>
                    $equipmentData->rent ?? 0,

                'letter' =>
                    $equipmentData->letter ?? 0,

                /*
                 * Se recalcula abajo.
                 */
                'total' => 0,
            ]);

            /*
             * ========================================================
             * ÚNICOS DETALLES ACTIVOS ACTUALMENTE
             * ========================================================
             */

            $consumables =
                $equipmentData->consumables ?? [];

            $workforces =
                $equipmentData->workforces ?? [];

            $totalConsumable = '0';
            $totalWorkforces = 0;

            $productTaxLines = [];
            $serviceTaxLines = [];

            /*
             * ========================================================
             * SERVICIOS
             * ========================================================
             */

            /** @var QuoteStockReservationService $reservationService */
            $reservationService = app(
                QuoteStockReservationService::class
            );

            /** @var TypeTaxResolverService $typeTaxResolver */
            $typeTaxResolver = app(
                TypeTaxResolverService::class
            );

            /** @var QuoteTaxCalculatorService $taxCalculator */
            $taxCalculator = app(
                QuoteTaxCalculatorService::class
            );

            /** @var PriceResolverService $priceResolver */
            $priceResolver = app(
                PriceResolverService::class
            );

            /*
             * ========================================================
             * CONSUMABLES / PRODUCTOS
             * ========================================================
             */

            @bcscale(10);

            for (
                $k = 0;
                $k < sizeof($consumables);
                $k++
            ) {

                $c = $consumables[$k];

                $stockItemId =
                    (int) ($c->id ?? 0);

                if ($stockItemId <= 0) {
                    throw new \RuntimeException(
                        'Uno de los productos no contiene un StockItem válido.'
                    );
                }

                /*
                 * StockItem ya está protegido por TenantScope.
                 */
                $stockItem =
                    StockItem::with([
                        'material'
                    ])
                        ->find(
                            $stockItemId
                        );

                if (!$stockItem) {
                    throw new \RuntimeException(
                        "El producto con ID {$stockItemId} no existe."
                    );
                }

                if (!$stockItem->material) {
                    throw new \RuntimeException(
                        "El producto con ID {$stockItemId} no tiene material asociado."
                    );
                }

                /*
                 * ====================================================
                 * VALIDAR QUE LA COMPANY COMERCIALICE ESTE STOCKITEM
                 * ====================================================
                 */

                $companyStockItem =
                    CompanyStockItem::query()
                        ->where(
                            'company_id',
                            $companyId
                        )
                        ->where(
                            'stock_item_id',
                            $stockItem->id
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->first();

                if (!$companyStockItem) {
                    throw new \RuntimeException(
                        "El producto {$stockItem->display_name} no está habilitado para la empresa actual."
                    );
                }

                /*
                 * ====================================================
                 * SNAPSHOT TRIBUTARIO
                 * ====================================================
                 *
                 * El impuesto se obtiene del Material al momento
                 * de crear la cotización.
                 *
                 * A partir de aquí la línea conservará:
                 *
                 * - type_tax_id
                 * - tax_rate
                 *
                 * aunque posteriormente cambie el Material.
                 */

                $taxData =
                    $typeTaxResolver
                        ->resolveMaterialWithSource(
                            $stockItem->material
                        );

                /*
                 * ====================================================
                 * TIPO DE PRODUCTO
                 * ====================================================
                 */

                $isItemeable =
                    (int) (
                        $stockItem
                            ->material
                            ->tipo_venta_id
                        ?? 0
                    ) === 3;

                /*
                 * ====================================================
                 * DATOS DEL FRONT
                 * ====================================================
                 */

                $frontQty =
                    (float) (
                        $c->quantity ?? 0
                    );

                if ($frontQty <= 0) {
                    throw new \RuntimeException(
                        "Cantidad inválida para {$stockItem->display_name}."
                    );
                }

                $presentationId =
                    $c->presentation_id
                    ?? null;

                /*
                 * ====================================================
                 * PRESENTACIÓN / UNIDADES REALES
                 * ====================================================
                 */

                $packs = null;
                $unitsPerPack = null;
                $priceReal = null;

                if (!empty($presentationId)) {

                    $packs =
                        (int) $frontQty;

                    if ($packs < 1) {
                        throw new \RuntimeException(
                            "Cantidad de paquetes inválida para {$stockItem->display_name}."
                        );
                    }

                    $presentation =
                        MaterialPresentation::query()
                            ->where(
                                'id',
                                $presentationId
                            )
                            ->where(
                                'material_id',
                                $stockItem
                                    ->material
                                    ->id
                            )
                            ->where(
                                'active',
                                1
                            )
                            ->first();

                    if (!$presentation) {
                        throw new \RuntimeException(
                            "La presentación seleccionada no es válida para {$stockItem->display_name}."
                        );
                    }

                    /*
                     * El precio de presentación ya es precio final
                     * con el impuesto correspondiente incluido.
                     */
                    $priceReal =
                        (float) $presentation->price;

                    $unitsPerPack =
                        (int) $presentation
                            ->quantity;

                    if ($unitsPerPack < 1) {
                        throw new \RuntimeException(
                            "La presentación tiene una cantidad inválida para {$stockItem->display_name}."
                        );
                    }

                    $requestedUnits =
                        (float) (
                            $packs *
                            $unitsPerPack
                        );

                } else {

                    $requestedUnits =
                        (float) (
                            $c->units_equivalent
                            ?? $frontQty
                        );

                    if ($requestedUnits <= 0) {
                        throw new \RuntimeException(
                            "Cantidad inválida para {$stockItem->display_name}."
                        );
                    }

                    /*
                     * El precio resuelto también es precio final.
                     */
                    $priceData =
                        $priceResolver
                            ->resolveWithSource(
                                $stockItem,
                                $companyId
                            );

                    if ($priceData['price'] === null) {
                        throw new \RuntimeException(
                            "El producto {$stockItem->display_name} no tiene un precio configurado en la lista predeterminada."
                        );
                    }

                    $priceReal =
                        (float) $priceData['price'];
                }

                /*
                 * ====================================================
                 * CÁLCULOS MONETARIOS
                 * ====================================================
                 *
                 * priceReal:
                 * precio final, impuesto incluido cuando corresponda.
                 *
                 * valorUnitarioReal:
                 * valor sin impuesto en líneas gravadas.
                 */

                $taxRate =
                    (float) $taxData['tax_rate'];

                $taxFactor =
                    1 + (
                        $taxRate / 100
                    );

                if ($taxFactor <= 0) {
                    throw new \RuntimeException(
                        "El factor tributario de {$stockItem->display_name} es inválido."
                    );
                }

                $valorUnitarioReal =
                    round(
                        (float) $priceReal /
                        $taxFactor,
                        10
                    );

                /*
                 * frontQty:
                 *
                 * presentación → packs
                 * unidad       → unidades
                 */
                $importeReal =
                    round(
                        (float) $frontQty *
                        (float) $priceReal,
                        10
                    );

                if (
                    (float) $priceReal < 0 ||
                    (float) $importeReal < 0
                ) {
                    throw new \RuntimeException(
                        "Valores inválidos para {$stockItem->display_name}."
                    );
                }

                /*
                 * ====================================================
                 * ITEMS FÍSICOS
                 * ====================================================
                 */

                $selectedItemIds = [];

                if ($isItemeable) {

                    $rawSelectedItemIds =
                        $c->selected_item_ids
                        ?? [];

                    if (
                    is_string(
                        $rawSelectedItemIds
                    )
                    ) {

                        $decodedItemIds =
                            json_decode(
                                $rawSelectedItemIds,
                                true
                            );

                        $rawSelectedItemIds =
                            is_array(
                                $decodedItemIds
                            )
                                ? $decodedItemIds
                                : [];
                    }

                    if (
                    !is_array(
                        $rawSelectedItemIds
                    )
                    ) {
                        $rawSelectedItemIds = [];
                    }

                    $selectedItemIds =
                        collect(
                            $rawSelectedItemIds
                        )
                            ->map(
                                function ($itemId) {
                                    return (int) $itemId;
                                }
                            )
                            ->filter(
                                function ($itemId) {
                                    return $itemId > 0;
                                }
                            )
                            ->values()
                            ->toArray();

                    if (
                    empty(
                    $selectedItemIds
                    )
                    ) {
                        throw new \RuntimeException(
                            "Debe seleccionar los ítems físicos para {$stockItem->display_name}."
                        );
                    }

                    if (
                        count(
                            $selectedItemIds
                        )
                        !==
                        count(
                            array_unique(
                                $selectedItemIds
                            )
                        )
                    ) {
                        throw new \RuntimeException(
                            "Se detectaron ítems repetidos en {$stockItem->display_name}."
                        );
                    }

                    if (
                        (int) $requestedUnits
                        !==
                        count(
                            $selectedItemIds
                        )
                    ) {
                        throw new \RuntimeException(
                            "La cantidad de ítems seleccionados no coincide con la cantidad requerida para {$stockItem->display_name}. " .
                            "Requerido: {$requestedUnits}. " .
                            "Seleccionados: " .
                            count(
                                $selectedItemIds
                            ) .
                            '.'
                        );
                    }
                }

                /*
                 * ====================================================
                 * STOCK DISPONIBLE
                 * ====================================================
                 */

                $available =
                    $reservationService
                        ->getAvailableStockByStockItem(
                            $stockItem->id
                        );

                if (
                    $requestedUnits >
                    $available
                ) {
                    throw new \RuntimeException(
                        "El producto {$stockItem->display_name} no cuenta con stock suficiente. " .
                        "Requerido: {$requestedUnits}. Disponible: {$available}."
                    );
                }

                $availability = 'Completo';
                $state = 'En compra';

                /*
                 * ====================================================
                 * PROMOCIÓN LEGACY
                 * ====================================================
                 *
                 * Se conserva por compatibilidad.
                 *
                 * IMPORTANTE:
                 * este valor NO participa en QuoteTaxCalculatorService.
                 * El descuento funcional de la cotización es únicamente
                 * el descuento global.
                 */

                $promotionDiscount =
                    (float) (
                        $c->discount
                        ?? 0
                    );

                if ($promotionDiscount < 0) {
                    throw new \RuntimeException(
                        "El descuento de {$stockItem->display_name} es inválido."
                    );
                }

                if ($promotionDiscount > $importeReal) {
                    throw new \RuntimeException(
                        "El descuento de {$stockItem->display_name} no puede superar el importe de la línea."
                    );
                }

                /*
                 * ====================================================
                 * EQUIPMENT CONSUMABLE
                 * ====================================================
                 */

                $equipmentConsumable =
                    EquipmentConsumable::create([
                        'availability' =>
                            $availability,

                        'state' =>
                            $state,

                        'equipment_id' =>
                            $equipment->id,

                        /*
                         * material_id queda por compatibilidad.
                         */
                        'material_id' =>
                            $stockItem
                                ->material
                                ->id,

                        /*
                         * Identidad moderna.
                         */
                        'stock_item_id' =>
                            $stockItem->id,

                        /*
                         * Snapshot tributario.
                         */
                        'type_tax_id' =>
                            $taxData['type_tax_id'],

                        'tax_rate' =>
                            $taxData['tax_rate'],

                        /*
                         * Cantidad física real que afecta inventario.
                         */
                        'quantity' =>
                            $requestedUnits,

                        /*
                         * Precio final de venta.
                         */
                        'price' =>
                            $priceReal,

                        /*
                         * Valor sin impuesto cuando corresponda.
                         */
                        'valor_unitario' =>
                            $valorUnitarioReal,

                        /*
                         * Importe final de la línea.
                         */
                        'total' =>
                            $importeReal,

                        /*
                         * Metadata legacy de promociones.
                         */
                        'discount' =>
                            $promotionDiscount,

                        'type_promo' =>
                            $c->type_promo
                            ?? null,

                        'material_presentation_id' =>
                            $presentationId,

                        'packs' =>
                            $packs,

                        'units_per_pack' =>
                            $unitsPerPack,
                    ]);

                /*
                 * ====================================================
                 * LÍNEA TRIBUTARIA
                 * ====================================================
                 *
                 * MUY IMPORTANTE:
                 *
                 * El QuoteTaxCalculatorService recibe el importe COMPLETO
                 * de la línea.
                 *
                 * NO descontamos promotionDiscount aquí.
                 *
                 * El único descuento funcional que afecta los totales
                 * tributarios es el descuento global de Quote.
                 */

                $productTaxLines[] = [
                    'type_tax_id' =>
                        (int) $equipmentConsumable
                            ->type_tax_id,

                    'tax_rate' =>
                        (float) $equipmentConsumable
                            ->tax_rate,

                    'total' =>
                        round(
                            (float) $importeReal,
                            10
                        ),
                ];

                /*
                 * ====================================================
                 * TOTAL PRODUCTOS
                 * ====================================================
                 */

                $totalConsumable =
                    bcadd(
                        $totalConsumable,
                        $importeReal,
                        10
                    );

                /*
                 * ====================================================
                 * RESERVAR STOCK
                 * ====================================================
                 */

                if ($isItemeable) {

                    $reservationService
                        ->reserveItemeableForQuoteDetail(
                            (int) $quote->id,
                            (int) $equipmentConsumable->id,
                            (int) $stockItem->id,
                            $selectedItemIds
                        );

                } else {

                    $reservationService
                        ->reserveForQuoteDetail(
                            (int) $quote->id,
                            (int) $equipmentConsumable->id,
                            (int) $stockItem->id,
                            (float) $requestedUnits
                        );
                }

                /*
                 * ====================================================
                 * PROMOCIÓN CON LÍMITE
                 * ====================================================
                 *
                 * Legacy.
                 * Continúa controlando consumo/límite de la promoción,
                 * pero no modifica la línea tributaria de TAX-01.
                 */

                if (
                    ($c->type_promo ?? null)
                    === 'limit'
                ) {

                    $promotion =
                        PromotionLimit::where(
                            'material_id',
                            $stockItem
                                ->material
                                ->id
                        )
                            ->whereDate(
                                'start_date',
                                '<=',
                                now()
                            )
                            ->whereDate(
                                'end_date',
                                '>=',
                                now()
                            )
                            ->first();

                    if ($promotion) {

                        $query =
                            PromotionUsage::where(
                                'promotion_limit_id',
                                $promotion->id
                            );

                        if (
                            $promotion
                                ->applies_to
                            === 'worker'
                        ) {
                            $query->where(
                                'user_id',
                                auth()->id()
                            );
                        }

                        $usage =
                            $query->first();

                        $alreadyUsed =
                            $usage
                                ? (float) $usage
                                ->used_quantity
                                : 0.0;

                        $remaining =
                            (float) $promotion
                                ->limit_quantity
                            -
                            $alreadyUsed;

                        if (
                            $remaining <
                            $requestedUnits
                        ) {
                            throw new \RuntimeException(
                                "La promoción para {$stockItem->display_name} ya no tiene suficiente cantidad disponible."
                            );
                        }

                        if (!$usage) {

                            PromotionUsage::create([
                                'promotion_limit_id' =>
                                    $promotion->id,

                                'user_id' =>
                                    $promotion
                                        ->applies_to
                                    === 'worker'
                                        ? auth()->id()
                                        : null,

                                'used_quantity' =>
                                    $requestedUnits,

                                'equipment_id' =>
                                    $equipment->id,

                                'equipment_consumable_id' =>
                                    $equipmentConsumable
                                        ->id,

                                'quote_id' =>
                                    $quote->id,
                            ]);

                        } else {

                            $usage
                                ->used_quantity +=
                                $requestedUnits;

                            $usage->equipment_id =
                                $equipment->id;

                            $usage
                                ->equipment_consumable_id =
                                $equipmentConsumable
                                    ->id;

                            $usage->quote_id =
                                $quote->id;

                            $usage->save();
                        }
                    }
                }
            }

            /*
             * ========================================================
             * WORKFORCES
             * ========================================================
             */

            $defaultTax =
                $typeTaxResolver
                    ->resolveWithSource();

            for (
                $w = 0;
                $w < sizeof($workforces);
                $w++
            ) {

                $billable =
                    isset(
                        $workforces[$w]
                            ->billable
                    )
                        ? (int)
                    $workforces[$w]
                        ->billable
                        : 1;

                if (
                !in_array(
                    $billable,
                    [0, 1],
                    true
                )
                ) {
                    throw new \RuntimeException(
                        'Uno de los servicios contiene un estado de facturación inválido.'
                    );
                }

                $workforceQuantity =
                    (float) (
                        $workforces[$w]->quantity
                        ?? 0
                    );

                $workforcePrice =
                    (float) (
                        $workforces[$w]->price
                        ?? 0
                    );

                if (
                    $workforceQuantity < 0 ||
                    $workforcePrice < 0
                ) {
                    throw new \RuntimeException(
                        'Uno de los servicios contiene valores inválidos.'
                    );
                }

                /*
                 * El precio del servicio también representa
                 * precio final de venta.
                 */
                $workforceTotal =
                    round(
                        $workforceQuantity *
                        $workforcePrice,
                        10
                    );

                $equipmentWorkforce =
                    EquipmentWorkforce::create([
                        'equipment_id' =>
                            $equipment->id,

                        'description' =>
                            $workforces[$w]
                                ->description
                            ?? '',

                        'price' =>
                            $workforcePrice,

                        'quantity' =>
                            $workforceQuantity,

                        'total' =>
                            $workforceTotal,

                        'unit' =>
                            $workforces[$w]
                                ->unit
                            ?? '',

                        'billable' =>
                            $billable,

                        /*
                         * Los servicios usan el TypeTax default
                         * vigente al crear la cotización.
                         */
                        'type_tax_id' =>
                            $defaultTax['type_tax_id'],

                        'tax_rate' =>
                            $defaultTax['tax_rate'],
                    ]);

                /*
                 * Solo servicios facturables afectan
                 * los totales de Quote.
                 */
                if ($billable === 1) {

                    $serviceTaxLines[] = [
                        'type_tax_id' =>
                            (int) $equipmentWorkforce
                                ->type_tax_id,

                        'tax_rate' =>
                            (float) $equipmentWorkforce
                                ->tax_rate,

                        'total' =>
                            round(
                                (float) $equipmentWorkforce
                                    ->total,
                                10
                            ),
                    ];
                }

                /*
                 * Equipment.total conserva la semántica legacy
                 * y suma todos los servicios.
                 */
                $totalWorkforces +=
                    (float)
                    $equipmentWorkforce
                        ->total;
            }

            /*
             * ========================================================
             * TOTAL DEL EQUIPMENT
             * ========================================================
             *
             * quantity siempre es 1.
             */

            $totalEquipment =
                (
                    (float) $totalConsumable +
                    (float) $totalWorkforces
                )
                *
                (float) $equipment->quantity;

            $equipment->total =
                $totalEquipment;

            $equipment->save();

            /*
             * ========================================================
             * TOTALES TRIBUTARIOS DE QUOTE
             * ========================================================
             *
             * QuoteTaxCalculatorService es la autoridad.
             *
             * Los precios de las líneas ya incluyen
             * su impuesto cuando corresponda.
             *
             * discount_input_mode:
             *
             * with_igv:
             *     el valor ingresado afecta el total final.
             *
             * without_igv:
             *     el valor ingresado afecta la base gravada.
             */

            $taxTotals =
                $taxCalculator->calculate(
                    $productTaxLines,
                    $serviceTaxLines,
                    [
                        'type' =>
                            $request->get(
                                'discount_type',
                                'amount'
                            ),

                        'mode' =>
                            $request->get(
                                'discount_input_mode',
                                'without_igv'
                            ),

                        'value' =>
                            (float) $request->get(
                                'discount_input_value',
                                0
                            ),
                    ]
                );

            /*
             * Quote.descuento:
             *
             * with_igv:
             *     equivalente de descuento en base.
             *
             * without_igv:
             *     descuento aplicado directamente a la base.
             */
            $quote->descuento =
                $taxTotals[
                'discount_total'
                ];

            $quote->gravada =
                $taxTotals[
                'gravada'
                ];

            $quote->exonerada =
                $taxTotals[
                'exonerada'
                ];

            $quote->inafecta =
                $taxTotals[
                'inafecta'
                ];

            $quote->igv_total =
                $taxTotals[
                'igv_total'
                ];

            $quote->total_importe =
                $taxTotals[
                'total_importe'
                ];

            $quote->save();

            /*
             * ========================================================
             * NOTIFICACIÓN
             * ========================================================
             *
             * QUOTE-NOTIF-01:
             * pendiente migrar roles hardcodeados y Company.
             */

            $notification =
                Notification::create([
                    'content' =>
                        $quote->code .
                        ' creada por ' .
                        Auth::user()->name,

                    'reason_for_creation' =>
                        'create_quote',

                    'user_id' =>
                        Auth::user()->id,

                    'url_go' =>
                        route(
                            'quote.edit',
                            $quote->id
                        )
                ]);

            $users =
                User::role([
                    'admin',
                    'principal',
                    'logistic'
                ])->get();

            foreach ($users as $user) {

                if (
                    $user->id ==
                    Auth::user()->id
                ) {
                    continue;
                }

                foreach (
                    $user->roles
                    as $role
                ) {

                    NotificationUser::create([
                        'notification_id' =>
                            $notification->id,

                        'role_id' =>
                            $role->id,

                        'user_id' =>
                            $user->id,

                        'read' =>
                            false,

                        'date_read' =>
                            null,

                        'date_delete' =>
                            null,
                    ]);
                }
            }

            /*
             * ========================================================
             * AUDITORÍA
             * ========================================================
             */

            $end =
                microtime(true)
                -
                $begin;

            Audit::create([
                'user_id' =>
                    Auth::user()->id,

                'action' =>
                    'Guardar cotizacion POST',

                'time' =>
                    $end
            ]);

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    $e->getMessage(),

                'file' =>
                    $e->getFile(),

                'line' =>
                    $e->getLine(),
            ], 422);
        }

        return response()->json([
            'message' =>
                'Cotización ' .
                $codeQuote .
                ' guardada con éxito.'
        ], 200);
    }

    public function renewQuote(Quote $quote)
    {
        try {

            $companyId = TenantContext::companyId();
            $tenantId = TenantContext::tenantId();

            $renewQuote = DB::transaction(function () use (
                $quote,
                $companyId,
                $tenantId
            ) {

                $begin = microtime(true);

                /*
                 * ==========================================================
                 * 1. BLOQUEAR Y OBTENER LA COTIZACIÓN ORIGINAL
                 * ==========================================================
                 *
                 * BelongsToTenant protege Tenant.
                 * company_id protege explícitamente entre Companies
                 * del mismo Tenant.
                 */

                $originalQuote = Quote::query()
                    ->where(
                        'id',
                        $quote->id
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->with([
                        'equipments.consumables.material',
                        'equipments.consumables.stockItem',
                        'equipments.workforces',
                    ])
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Solamente una cotización marcada para recotizar
                 * puede utilizar esta operación.
                 */
                if (
                    $originalQuote->state
                    !== 'requote'
                ) {
                    throw new \Exception(
                        'La cotización seleccionada no está disponible para recotización.'
                    );
                }

                /*
                 * Una cotización original solamente puede generar
                 * una nueva recotización dentro de la misma Company.
                 */
                $alreadyRenewed = Quote::query()
                    ->where(
                        'renewed_from_quote_id',
                        $originalQuote->id
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->exists();

                if ($alreadyRenewed) {
                    throw new \Exception(
                        'Esta cotización ya fue recotizada anteriormente.'
                    );
                }

                /*
                 * ==========================================================
                 * 2. VALIDAR QUE SOLAMENTE EXISTA UN EQUIPMENT
                 * ==========================================================
                 */

                if (
                $originalQuote
                    ->equipments
                    ->isEmpty()
                ) {
                    throw new \Exception(
                        'La cotización no contiene productos para recotizar.'
                    );
                }

                if (
                    $originalQuote
                        ->equipments
                        ->count() > 1
                ) {
                    throw new \Exception(
                        'La cotización contiene más de un grupo de productos y no puede recotizarse automáticamente.'
                    );
                }

                /*
                 * ==========================================================
                 * 3. VALIDAR PRODUCTOS ITEMEABLES
                 * ==========================================================
                 *
                 * Por ahora no recotizamos automáticamente productos
                 * con ítems físicos porque sería necesario volver
                 * a seleccionar las unidades específicas.
                 */

                foreach (
                    $originalQuote->equipments
                    as $equipment
                ) {

                    foreach (
                        $equipment->consumables
                        as $consumable
                    ) {

                        if (!$consumable->material) {
                            throw new \Exception(
                                'Uno de los productos no tiene un material asociado.'
                            );
                        }

                        if (
                            (int)
                            $consumable
                                ->material
                                ->tipo_venta_id
                            === 3
                        ) {
                            throw new \Exception(
                                'La cotización contiene productos con ítems físicos y no puede recotizarse automáticamente.'
                            );
                        }

                        if (
                        empty(
                        $consumable
                            ->stock_item_id
                        )
                        ) {
                            throw new \Exception(
                                'El producto "' .
                                $consumable
                                    ->material
                                    ->full_name .
                                '" no tiene un StockItem asociado y no puede recotizarse automáticamente.'
                            );
                        }
                    }
                }

                /*
                 * ==========================================================
                 * 4. REDONDEO INTERNO
                 * ==========================================================
                 */

                $round10 = function ($value) {
                    return round(
                        (float) $value,
                        10
                    );
                };

                /*
                 * ==========================================================
                 * 5. CREAR NUEVA COTIZACIÓN
                 * ==========================================================
                 */

                $renewQuote = Quote::create([
                    'branch_id' => TenantContext::branchId(),

                    /*
                     * Company explícita.
                     * tenant_id lo asigna BelongsToTenant.
                     */
                    'company_id' =>
                        $companyId,

                    /*
                     * Código temporal para evitar conflicto
                     * con índice unique.
                     */
                    'code' =>
                        'TMP-' .
                        Str::uuid(),

                    'renewed_from_quote_id' =>
                        $originalQuote->id,

                    'description_quote' =>
                        $originalQuote
                            ->description_quote,

                    'observations' =>
                        $originalQuote
                            ->observations,

                    'date_quote' =>
                        Carbon::now(),

                    'date_validate' =>
                        Carbon::now()
                            ->addDays(5),

                    'way_to_pay' =>
                        $originalQuote
                            ->way_to_pay,

                    'delivery_time' =>
                        $originalQuote
                            ->delivery_time,

                    'customer_id' =>
                        $originalQuote
                            ->customer_id,

                    'contact_id' =>
                        $originalQuote
                            ->contact_id,

                    'payment_deadline_id' =>
                        $originalQuote
                            ->payment_deadline_id,

                    'state' =>
                        'created',

                    'currency_invoice' =>
                        $originalQuote
                            ->currency_invoice,

                    /*
                     * Se conserva la configuración del descuento,
                     * pero los importes se recalculan.
                     */
                    'discount_type' =>
                        $originalQuote
                            ->discount_type,

                    'discount_input_mode' =>
                        $originalQuote
                            ->discount_input_mode,

                    'discount_input_value' =>
                        $originalQuote
                            ->discount_input_value,

                    'descuento' =>
                        0,

                    'gravada' =>
                        0,

                    'exonerada' =>
                        0,

                    'inafecta' =>
                        0,

                    'igv_total' =>
                        0,

                    'total_importe' =>
                        0,
                ]);

                /*
                 * Código definitivo.
                 */
                $renewQuote->code =
                    'COT-' .
                    str_pad(
                        $renewQuote->id,
                        5,
                        '0',
                        STR_PAD_LEFT
                    );

                $renewQuote->save();

                QuoteUser::create([
                    'quote_id' =>
                        $renewQuote->id,

                    'user_id' =>
                        Auth::id(),
                ]);

                /*
                 * ==========================================================
                 * SERVICIOS
                 * ==========================================================
                 */

                /** @var QuoteStockReservationService $reservationService */
                $reservationService = app(
                    QuoteStockReservationService::class
                );

                /** @var PriceResolverService $priceResolver */
                $priceResolver = app(
                    PriceResolverService::class
                );

                /** @var TypeTaxResolverService $typeTaxResolver */
                $typeTaxResolver = app(
                    TypeTaxResolverService::class
                );

                /** @var QuoteTaxCalculatorService $taxCalculator */
                $taxCalculator = app(
                    QuoteTaxCalculatorService::class
                );

                /*
                 * Líneas que utilizará QuoteTaxCalculatorService.
                 */
                $productTaxLines = [];
                $serviceTaxLines = [];

                /*
                 * ==========================================================
                 * 6. COPIAR EQUIPMENT
                 * ==========================================================
                 */

                foreach (
                    $originalQuote->equipments
                    as $originalEquipment
                ) {

                    $renewEquipment =
                        Equipment::create([
                            'quote_id' =>
                                $renewQuote->id,

                            'description' =>
                                $originalEquipment
                                    ->description
                                    ?: '',

                            'detail' =>
                                $originalEquipment
                                    ->detail
                                    ?: '',

                            /*
                             * Regla funcional actual:
                             * siempre existe un único Equipment
                             * y su quantity siempre es 1.
                             */
                            'quantity' =>
                                1,

                            /*
                             * Legacy.
                             */
                            'utility' =>
                                0,

                            'rent' =>
                                0,

                            'letter' =>
                                0,

                            'total' =>
                                0,
                        ]);

                    $totalConsumablesForEquipment =
                        0;

                    $totalWorkforcesForEquipment =
                        0;

                    /*
                     * ======================================================
                     * 6.1. PRODUCTOS
                     * ======================================================
                     */

                    foreach (
                        $originalEquipment
                            ->consumables
                        as $originalConsumable
                    ) {

                        /*
                         * StockItem:
                         *
                         * - Tenant actual vía BelongsToTenant
                         * - activo
                         * - habilitado comercialmente para Company
                         */

                        $stockItem =
                            StockItem::query()
                                ->with([
                                    'material',
                                ])
                                ->where(
                                    'id',
                                    $originalConsumable
                                        ->stock_item_id
                                )
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->enabledForCompany(
                                    $companyId
                                )
                                ->first();

                        if (!$stockItem) {
                            throw new \Exception(
                                'Uno de los productos ya no está activo o habilitado para la empresa actual.'
                            );
                        }

                        if (!$stockItem->material) {
                            throw new \Exception(
                                'El producto con ID ' .
                                $originalConsumable
                                    ->stock_item_id .
                                ' no tiene material asociado.'
                            );
                        }

                        /*
                         * ==================================================
                         * IMPUESTO ACTUAL PARA LA NUEVA RECOTIZACIÓN
                         * ==================================================
                         *
                         * Renew es una operación nueva.
                         *
                         * Por eso NO conserva el snapshot tributario
                         * anterior.
                         *
                         * Obtiene el TypeTax actual del Material y
                         * genera un nuevo snapshot.
                         */

                        $taxData =
                            $typeTaxResolver
                                ->resolveMaterialWithSource(
                                    $stockItem->material
                                );

                        /*
                         * Doble protección por si la configuración
                         * cambió desde la cotización original.
                         */
                        if (
                            (int)
                            $stockItem
                                ->material
                                ->tipo_venta_id
                            === 3
                        ) {
                            throw new \Exception(
                                'El producto "' .
                                $stockItem
                                    ->material
                                    ->full_name .
                                '" requiere selección de ítems físicos y no puede recotizarse automáticamente.'
                            );
                        }

                        /*
                         * No modificamos la cantidad física
                         * solicitada originalmente.
                         */

                        $requestedUnits =
                            (float)
                            $originalConsumable
                                ->quantity;

                        if ($requestedUnits <= 0) {
                            throw new \Exception(
                                'El producto "' .
                                $stockItem
                                    ->material
                                    ->full_name .
                                '" tiene una cantidad inválida.'
                            );
                        }

                        $presentationId =
                            $originalConsumable
                                ->material_presentation_id;

                        $packs =
                            $originalConsumable
                                ->packs;

                        $unitsPerPack =
                            $originalConsumable
                                ->units_per_pack;

                        $currentPrice =
                            null;

                        /*
                         * Cantidad comercial utilizada para
                         * calcular el importe:
                         *
                         * - unidad       → requestedUnits
                         * - presentación → packs
                         */
                        $lineQuantityForPrice =
                            $requestedUnits;

                        /*
                         * ==================================================
                         * PRODUCTO CON PRESENTACIÓN
                         * ==================================================
                         */

                        if (!empty($presentationId)) {

                            $presentation =
                                MaterialPresentation::query()
                                    ->where(
                                        'id',
                                        $presentationId
                                    )
                                    ->where(
                                        'material_id',
                                        $stockItem
                                            ->material_id
                                    )
                                    ->where(
                                        'active',
                                        1
                                    )
                                    ->first();

                            if (!$presentation) {
                                throw new \Exception(
                                    'La presentación del producto "' .
                                    $stockItem
                                        ->material
                                        ->full_name .
                                    '" ya no se encuentra activa.'
                                );
                            }

                            if (
                                empty($packs) ||
                                (int) $packs <= 0 ||
                                empty($unitsPerPack) ||
                                (int) $unitsPerPack <= 0
                            ) {
                                throw new \Exception(
                                    'La presentación del producto "' .
                                    $stockItem
                                        ->material
                                        ->full_name .
                                    '" contiene información inválida.'
                                );
                            }

                            /*
                             * Si cambió la cantidad que contiene
                             * la presentación, detenemos Renew.
                             *
                             * No reinterpretamos silenciosamente
                             * la cantidad física original.
                             */
                            if (
                                (int)
                                $presentation
                                    ->quantity
                                !==
                                (int)
                                $unitsPerPack
                            ) {
                                throw new \Exception(
                                    'La presentación del producto "' .
                                    $stockItem
                                        ->material
                                        ->full_name .
                                    '" cambió su cantidad de unidades. Revise el producto manualmente.'
                                );
                            }

                            /*
                             * Precio vigente de la presentación.
                             *
                             * Es precio final de venta y contiene
                             * impuesto cuando corresponda.
                             */
                            $currentPrice =
                                (float)
                                $presentation
                                    ->price;

                            $lineQuantityForPrice =
                                (float) $packs;

                        } else {

                            /*
                             * ==================================================
                             * PRODUCTO POR UNIDAD
                             * ==================================================
                             *
                             * PriceResolverService:
                             *
                             * 1. PriceListItem
                             * 2. PriceListMaterial
                             * 3. null = sin precio
                             */

                            $priceData =
                                $priceResolver
                                    ->resolveWithSource(
                                        $stockItem,
                                        $companyId
                                    );

                            if (
                                $priceData['price']
                                === null
                            ) {
                                throw new \Exception(
                                    'El producto "' .
                                    $stockItem
                                        ->display_name .
                                    '" no tiene un precio configurado en la lista predeterminada.'
                                );
                            }

                            /*
                             * Precio vigente final.
                             */
                            $currentPrice =
                                (float)
                                $priceData[
                                'price'
                                ];
                        }

                        /*
                         * En recotización automática no permitimos
                         * precio 0 porque no existe interacción
                         * para que el usuario lo autorice.
                         */
                        if (
                            $currentPrice === null ||
                            $currentPrice <= 0
                        ) {
                            throw new \Exception(
                                'El producto "' .
                                $stockItem
                                    ->display_name .
                                '" tiene un precio inválido o igual a cero.'
                            );
                        }

                        /*
                         * ==================================================
                         * CÁLCULOS MONETARIOS
                         * ==================================================
                         *
                         * El precio ya es precio FINAL.
                         */

                        $priceReal =
                            $round10(
                                $currentPrice
                            );

                        $taxRate =
                            (float)
                            $taxData[
                            'tax_rate'
                            ];

                        $taxFactor =
                            1 + (
                                $taxRate /
                                100
                            );

                        if ($taxFactor <= 0) {
                            throw new \RuntimeException(
                                'El factor tributario del producto "' .
                                $stockItem
                                    ->display_name .
                                '" es inválido.'
                            );
                        }

                        /*
                         * En gravados:
                         * extraemos la base del precio final.
                         *
                         * En exonerado/inafecto:
                         * tax_rate = 0
                         * factor = 1.
                         */
                        $valorUnitarioReal =
                            $round10(
                                $priceReal /
                                $taxFactor
                            );

                        $importeReal =
                            $round10(
                                $lineQuantityForPrice *
                                $priceReal
                            );

                        /*
                         * ==================================================
                         * VALIDAR STOCK ACTUAL
                         * ==================================================
                         */

                        $available =
                            $reservationService
                                ->getAvailableStockByStockItem(
                                    (int)
                                    $stockItem->id
                                );

                        if (
                            $requestedUnits >
                            $available
                        ) {
                            throw new \Exception(
                                'El producto "' .
                                $stockItem
                                    ->material
                                    ->full_name .
                                '" no cuenta con stock suficiente. ' .
                                'Requerido: ' .
                                $requestedUnits .
                                '. Disponible: ' .
                                $available .
                                '.'
                            );
                        }

                        /*
                         * ==================================================
                         * PROMOCIÓN LEGACY
                         * ==================================================
                         *
                         * Conservamos metadata histórica por
                         * compatibilidad.
                         *
                         * MUY IMPORTANTE:
                         *
                         * Este descuento NO participa en
                         * QuoteTaxCalculatorService.
                         *
                         * El descuento funcional de la Quote
                         * es exclusivamente el descuento global.
                         */

                        $promotionDiscount =
                            (float) (
                            $originalConsumable
                                ->discount
                                ?: 0
                            );

                        if ($promotionDiscount < 0) {
                            throw new \RuntimeException(
                                'El descuento promocional del producto "' .
                                $stockItem
                                    ->display_name .
                                '" es inválido.'
                            );
                        }

                        if (
                            $promotionDiscount >
                            $importeReal
                        ) {
                            throw new \RuntimeException(
                                'El descuento promocional del producto "' .
                                $stockItem
                                    ->display_name .
                                '" supera el nuevo importe de la línea.'
                            );
                        }

                        /*
                         * ==================================================
                         * CREAR NUEVO CONSUMABLE
                         * ==================================================
                         */

                        $renewConsumable =
                            EquipmentConsumable::create([
                                'equipment_id' =>
                                    $renewEquipment->id,

                                'material_id' =>
                                    $stockItem
                                        ->material_id,

                                'stock_item_id' =>
                                    $stockItem->id,

                                /*
                                 * Nuevo snapshot tributario.
                                 */
                                'type_tax_id' =>
                                    $taxData[
                                    'type_tax_id'
                                    ],

                                'tax_rate' =>
                                    $taxData[
                                    'tax_rate'
                                    ],

                                /*
                                 * Cantidad física real.
                                 */
                                'quantity' =>
                                    $requestedUnits,

                                /*
                                 * Precio final vigente.
                                 */
                                'price' =>
                                    $priceReal,

                                /*
                                 * Base unitaria cuando corresponda.
                                 */
                                'valor_unitario' =>
                                    $valorUnitarioReal,

                                /*
                                 * Importe final de la línea.
                                 */
                                'total' =>
                                    $importeReal,

                                /*
                                 * Metadata legacy.
                                 */
                                'discount' =>
                                    $promotionDiscount,

                                'type_promo' =>
                                    $originalConsumable
                                        ->type_promo,

                                'material_presentation_id' =>
                                    $presentationId,

                                'packs' =>
                                    $packs,

                                'units_per_pack' =>
                                    $unitsPerPack,

                                'state' =>
                                    'En compra',

                                'availability' =>
                                    'Completo',
                            ]);

                        /*
                         * ==================================================
                         * LÍNEA TRIBUTARIA
                         * ==================================================
                         *
                         * IMPORTANTE:
                         *
                         * El motor tributario recibe el importe
                         * COMPLETO de la línea.
                         *
                         * NO descontamos promotionDiscount.
                         */

                        $productTaxLines[] = [
                            'type_tax_id' =>
                                (int)
                                $renewConsumable
                                    ->type_tax_id,

                            'tax_rate' =>
                                (float)
                                $renewConsumable
                                    ->tax_rate,

                            'total' =>
                                $round10(
                                    $importeReal
                                ),
                        ];

                        /*
                         * ==================================================
                         * RESERVAR STOCK NUEVO
                         * ==================================================
                         */

                        $reservationService
                            ->reserveForQuoteDetail(
                                (int)
                                $renewQuote->id,

                                (int)
                                $renewConsumable->id,

                                (int)
                                $stockItem->id,

                                $requestedUnits
                            );

                        /*
                         * Equipment.total conserva
                         * el importe comercial de productos.
                         */
                        $totalConsumablesForEquipment =
                            $round10(
                                $totalConsumablesForEquipment +
                                $importeReal
                            );
                    }

                    /*
                     * ======================================================
                     * 6.2. SERVICIOS ADICIONALES
                     * ======================================================
                     *
                     * Renew es una operación nueva:
                     *
                     * los servicios reciben el TypeTax default
                     * vigente al momento de recotizar.
                     */

                    $defaultTax =
                        $typeTaxResolver
                            ->resolveWithSource();

                    foreach (
                        $originalEquipment
                            ->workforces
                        as $originalWorkforce
                    ) {

                        $billable =
                            isset(
                                $originalWorkforce
                                    ->billable
                            )
                                ? (int)
                            $originalWorkforce
                                ->billable
                                : 1;

                        if (
                        !in_array(
                            $billable,
                            [0, 1],
                            true
                        )
                        ) {
                            throw new \RuntimeException(
                                'Uno de los servicios contiene un estado de facturación inválido.'
                            );
                        }

                        $workforcePrice =
                            (float)
                            $originalWorkforce
                                ->price;

                        $workforceQuantity =
                            (float)
                            $originalWorkforce
                                ->quantity;

                        if (
                            $workforcePrice < 0 ||
                            $workforceQuantity < 0
                        ) {
                            throw new \RuntimeException(
                                'Uno de los servicios contiene valores inválidos.'
                            );
                        }

                        /*
                         * Recalculamos siempre.
                         *
                         * No confiamos en total histórico.
                         */
                        $workforceTotal =
                            $round10(
                                $workforcePrice *
                                $workforceQuantity
                            );

                        $renewWorkforce =
                            EquipmentWorkforce::create([
                                'equipment_id' =>
                                    $renewEquipment->id,

                                'description' =>
                                    $originalWorkforce
                                        ->description
                                        ?: '',

                                'price' =>
                                    $workforcePrice,

                                'quantity' =>
                                    $workforceQuantity,

                                'total' =>
                                    $workforceTotal,

                                'unit' =>
                                    $originalWorkforce
                                        ->unit
                                        ?: '',

                                'billable' =>
                                    $billable,

                                /*
                                 * Snapshot tributario nuevo.
                                 */
                                'type_tax_id' =>
                                    $defaultTax[
                                    'type_tax_id'
                                    ],

                                'tax_rate' =>
                                    $defaultTax[
                                    'tax_rate'
                                    ],
                            ]);

                        /*
                         * Equipment.total conserva la semántica
                         * legacy y suma todos los servicios.
                         */
                        $totalWorkforcesForEquipment =
                            $round10(
                                $totalWorkforcesForEquipment +
                                (float)
                                $renewWorkforce
                                    ->total
                            );

                        /*
                         * Solo servicios facturables afectan Quote.
                         */
                        if ($billable === 1) {

                            $serviceTaxLines[] = [
                                'type_tax_id' =>
                                    (int)
                                    $renewWorkforce
                                        ->type_tax_id,

                                'tax_rate' =>
                                    (float)
                                    $renewWorkforce
                                        ->tax_rate,

                                'total' =>
                                    $round10(
                                        (float)
                                        $renewWorkforce
                                            ->total
                                    ),
                            ];
                        }
                    }

                    /*
                     * ======================================================
                     * 6.3. TOTAL EQUIPMENT
                     * ======================================================
                     *
                     * quantity siempre es 1.
                     */

                    $totalEquipment =
                        $round10(
                            (
                                $totalConsumablesForEquipment +
                                $totalWorkforcesForEquipment
                            )
                            *
                            (float)
                            $renewEquipment
                                ->quantity
                        );

                    $renewEquipment->total =
                        $totalEquipment;

                    $renewEquipment->save();
                }

                /*
                 * ==========================================================
                 * 7. RECALCULAR TOTALES TRIBUTARIOS
                 * ==========================================================
                 *
                 * QuoteTaxCalculatorService es la autoridad.
                 *
                 * WITH_IGV:
                 * el descuento se aplica sobre el total final.
                 *
                 * WITHOUT_IGV:
                 * el descuento se aplica sobre la base gravada.
                 */

                $taxTotals =
                    $taxCalculator
                        ->calculate(
                            $productTaxLines,
                            $serviceTaxLines,
                            [
                                'type' =>
                                    $renewQuote
                                        ->discount_type
                                        ?: 'amount',

                                'mode' =>
                                    $renewQuote
                                        ->discount_input_mode
                                        ?: 'without_igv',

                                'value' =>
                                    (float) (
                                    $renewQuote
                                        ->discount_input_value
                                        ?: 0
                                    ),
                            ]
                        );

                /*
                 * Quote.descuento:
                 *
                 * with_igv:
                 * equivalente de descuento en base.
                 *
                 * without_igv:
                 * descuento aplicado a la base.
                 */

                $renewQuote->descuento =
                    $taxTotals[
                    'discount_total'
                    ];

                $renewQuote->gravada =
                    $taxTotals[
                    'gravada'
                    ];

                $renewQuote->exonerada =
                    $taxTotals[
                    'exonerada'
                    ];

                $renewQuote->inafecta =
                    $taxTotals[
                    'inafecta'
                    ];

                $renewQuote->igv_total =
                    $taxTotals[
                    'igv_total'
                    ];

                $renewQuote->total_importe =
                    $taxTotals[
                    'total_importe'
                    ];

                $renewQuote->save();

                /*
                 * ==========================================================
                 * 8. NOTIFICACIÓN
                 * ==========================================================
                 */

                $notification =
                    Notification::create([
                        'content' =>
                            $renewQuote->code .
                            ' recotizada desde ' .
                            $originalQuote->code .
                            ' por ' .
                            Auth::user()->name,

                        'reason_for_creation' =>
                            'renew_quote',

                        'user_id' =>
                            Auth::id(),

                        'url_go' =>
                            route(
                                'quoteSale.edit',
                                $renewQuote->id
                            ),
                    ]);

                /*
                 * Evitar notificar usuarios de otros Tenants.
                 *
                 * QUOTE-NOTIF-01:
                 * los nombres de roles siguen siendo legacy.
                 */

                $users =
                    User::query()
                        ->where(
                            'tenant_id',
                            $tenantId
                        )
                        ->role([
                            'admin',
                            'principal',
                            'logistic',
                        ])
                        ->get();

                foreach (
                    $users
                    as $user
                ) {

                    if (
                        $user->id ==
                        Auth::id()
                    ) {
                        continue;
                    }

                    foreach (
                        $user->roles
                        as $role
                    ) {

                        NotificationUser::create([
                            'notification_id' =>
                                $notification->id,

                            'role_id' =>
                                $role->id,

                            'user_id' =>
                                $user->id,

                            'read' =>
                                false,

                            'date_read' =>
                                null,

                            'date_delete' =>
                                null,
                        ]);
                    }
                }

                /*
                 * ==========================================================
                 * 9. AUDITORÍA
                 * ==========================================================
                 */

                $end =
                    microtime(true)
                    -
                    $begin;

                Audit::create([
                    'user_id' =>
                        Auth::id(),

                    'action' =>
                        'Recotizar cotización de venta ' .
                        $originalQuote->code .
                        ' a ' .
                        $renewQuote->code,

                    'time' =>
                        $end,
                ]);

                return $renewQuote;
            });

            return response()->json([
                'ok' =>
                    true,

                'message' =>
                    'La cotización ' .
                    $renewQuote->code .
                    ' fue creada correctamente.',

                'url' =>
                    route(
                        'quoteSale.index'
                    ),
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'ok' =>
                    false,

                'message' =>
                    $e->getMessage(),

                'file' =>
                    $e->getFile(),

                'line' =>
                    $e->getLine(),
            ], 422);
        }
    }

    public function getAvailableStockFromLots($stockItemId)
    {
        return app(QuoteStockReservationService::class)->getAvailableStockByStockItem((int) $stockItemId);
    }

    public function updateDatosGeneral(Request $request)
    {
        $quote = Quote::findOrFail($request->get('quote_id'));

        try {
            $dateQuote = $request->get('date_quote')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote'))
                : null;

            $dateValidate = $request->get('date_validate')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate'))
                : null;

            $quote->update([
                'description_quote'   => $request->get('descriptionQuote'),
                'code'                => $request->get('codeQuote'),
                'date_quote'          => $dateQuote,
                'date_validate'       => $dateValidate,
                'way_to_pay'          => $request->get('way_to_pay') ?? '',
                'delivery_time'       => $request->get('delivery_time') ?? '',
                'customer_id'         => $request->get('customer_id') ?? null,
                'contact_id'          => $request->get('contact_id') ?? null,
                'payment_deadline_id' => $request->get('payment_deadline') ?? null,
                'observations'        => $request->get('observations') ?? '',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Datos generales actualizados correctamente.'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function getDataQuotesIndex(Request $request, $pageNumber = 1)
    {
        $branchScope = app(QuoteBranchReadScope::class);
        $perPage = 10;

        $description_quote = $request->input('description_quote');
        $year = $request->input('year');
        $code = $request->input('code');
        $order = $request->input('order');
        $customer = $request->input('customer');
        $creator = $request->input('creator');
        $stateQuote = $request->input('stateQuote');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        /*
         * ============================================================
         * CONSULTA BASE
         * ============================================================
         */

        $companyId = TenantContext::companyId();

        $query = $branchScope->local(Quote::query())->with([
            'customer',
            'deadline',
            'users',
            'equipments.workforces',
        ])
            ->where(
                'company_id', $companyId
            )
            ->whereNotIn('state', [
                'expired',
            ])
            ->where('state_active', $stateQuote === 'close' ? 'close' : 'open')

            /*
             * No mostrar cotizaciones que todavía tengan
             * una venta activa asociada.
             */
            ->whereDoesntHave('sales', function ($q) {
                $q->where('state_annulled', 0);
            })

            /*
             * ========================================================
             * OCULTAR COTIZACIONES REQUOTE YA RECOTIZADAS
             * ========================================================
             *
             * Se muestran:
             * - Todas las cotizaciones cuyo estado no sea requote.
             * - Las cotizaciones requote que todavía no tengan
             *   una nueva cotización asociada.
             *
             * No se muestran:
             * - Las cotizaciones requote que ya fueron utilizadas
             *   como origen de otra cotización.
             */
            ->where(function ($q) {
                $q->where('state', '<>', 'requote')
                    ->orWhere(function ($requoteQuery) {
                        $requoteQuery
                            ->where('state', 'requote')
                            ->whereDoesntHave('renewedQuote');
                    });
            });

        /*
         * Sin filtro explícito:
         * mostrar solamente cotizaciones operativas.
         */
        if (!$stateQuote) {
            $query
                ->where(
                    'state',
                    '<>',
                    'canceled'
                )
                ->where(
                    'state_active',
                    'open'
                );
        }

        /*
         * ============================================================
         * FILTRO POR FECHAS
         * ============================================================
         */
        if ($startDate != "" && $endDate != "") {
            $fechaInicio = Carbon::createFromFormat(
                'd/m/Y',
                $startDate
            );

            $fechaFinal = Carbon::createFromFormat(
                'd/m/Y',
                $endDate
            );

            $query->whereDate(
                'date_quote',
                '>=',
                $fechaInicio
            );

            $query->whereDate(
                'date_quote',
                '<=',
                $fechaFinal
            );
        }

        /*
         * ============================================================
         * FILTROS GENERALES
         * ============================================================
         */
        if ($description_quote) {
            $query->where(
                'description_quote',
                'LIKE',
                '%' . $description_quote . '%'
            );
        }

        if ($year) {
            $query->whereYear(
                'date_quote',
                $year
            );
        }

        if ($code) {
            $query->where(
                'code',
                'LIKE',
                '%' . $code . '%'
            );
        }

        if ($order) {
            $query->where(
                'code_customer',
                'LIKE',
                '%' . $order . '%'
            );
        }

        if ($customer) {
            $query->whereHas(
                'customer',
                function ($query2) use ($customer) {
                    $query2->where(
                        'customer_id',
                        $customer
                    );
                }
            );
        }

        if ($creator != "") {
            $query->whereHas(
                'users',
                function ($query2) use ($creator) {
                    $query2->where(
                        'user_id',
                        $creator
                    );
                }
            );
        }

        /*
         * ============================================================
         * FILTRO POR ESTADO
         * ============================================================
         */
        if ($stateQuote) {
            $query->where(function ($subquery) use ($stateQuote) {
                switch ($stateQuote) {
                    case 'created':
                        $subquery
                            ->where('state', 'created')
                            ->where(function ($q2) {
                                $q2->where('send_state', 0)
                                    ->orWhere('send_state', false);
                            });
                        break;

                    case 'send':
                        $subquery
                            ->where('state', 'created')
                            ->where(function ($q2) {
                                $q2->where('send_state', 1)
                                    ->orWhere('send_state', true);
                            });
                        break;

                    case 'close':
                        $subquery->where(
                            'state_active',
                            'close'
                        );
                        break;

                    case 'raised':
                        $subquery
                            ->where('state', 'confirmed')
                            ->where('raise_status', 1)
                            ->where('state', '<>', 'canceled')
                            ->where('state_active', '<>', 'close');
                        break;

                    case 'confirm':
                        $subquery
                            ->where('state', 'confirmed')
                            ->where('raise_status', 0);
                        break;

                    case 'canceled':
                        $subquery->where(
                            'state',
                            'canceled'
                        );
                        break;

                    case 'requote':
                        /*
                         * Solamente devolverá las cotizaciones para
                         * recotizar que todavía no fueron recotizadas.
                         *
                         * La condición whereDoesntHave('renewedQuote')
                         * también está aplicada en la consulta base.
                         */
                        $subquery->where(
                            'state',
                            'requote'
                        );
                        break;

                    default:
                        break;
                }
            });
        }

        /*
         * ============================================================
         * ORDENAMIENTO Y PAGINACIÓN
         * ============================================================
         */
        $query->orderBy(
            'created_at',
            'DESC'
        );

        $totalFilteredRecords = $query->count();

        $totalPages = (int) ceil(
            $totalFilteredRecords / $perPage
        );

        $startRecord = $totalFilteredRecords > 0
            ? (($pageNumber - 1) * $perPage) + 1
            : 0;

        $endRecord = min(
            $totalFilteredRecords,
            $pageNumber * $perPage
        );

        $quotes = $query
            ->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        /*
         * ============================================================
         * FORMATEAR RESPUESTA
         * ============================================================
         */
        $array = [];

        foreach ($quotes as $quote) {
            $state = "";
            $stateText = "";

            if ($quote->state === 'created') {
                if (
                    $quote->send_state == 1 ||
                    $quote->send_state == true
                ) {
                    $state = 'send';

                    $stateText =
                        '<span class="badge bg-warning">' .
                        'Enviado' .
                        '</span>';
                } else {
                    $state = 'created';

                    $stateText =
                        '<span class="badge bg-primary">' .
                        'Creada' .
                        '</span>';
                }
            }

            if ($quote->state_active === 'close') {
                $state = 'close';

                $stateText =
                    '<span class="badge bg-danger">' .
                    'Finalizada' .
                    '</span>';
            } else {
                if (
                    $quote->state === 'confirmed' &&
                    $quote->raise_status === 1
                ) {
                    $state = 'raise';

                    $stateText =
                        '<span class="badge bg-success">' .
                        'Elevada' .
                        '</span>';
                }

                if (
                    $quote->state === 'confirmed' &&
                    $quote->raise_status === 0
                ) {
                    $state = 'confirm';

                    $stateText =
                        '<span class="badge bg-success">' .
                        'Confirmada' .
                        '</span>';
                }

                if ($quote->state === 'canceled') {
                    $state = 'canceled';

                    $stateText =
                        '<span class="badge bg-danger">' .
                        'Cancelada' .
                        '</span>';
                }

                if ($quote->state === 'requote') {
                    $state = 'requote';

                    $stateText =
                        '<span class="badge bg-secondary">' .
                        'Para Recotizar' .
                        '</span>';
                }
            }

            /*
             * Estado de decimales.
             */
            if ($quote->state_decimals == 1) {
                $stateDecimals =
                    '<span class="badge bg-success">' .
                    'Mostrar' .
                    '</span>';
            } else {
                $stateDecimals =
                    '<span class="badge bg-danger">' .
                    'Ocultar' .
                    '</span>';
            }

            /*
             * Servicios no facturables.
             */
            $total_workforce = 0;

            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->workforces as $workforce) {
                    if ($workforce->billable == false) {
                        $total_workforce +=
                            (float) $workforce->total;
                    }
                }
            }

            $array[] = [
                'id' => $quote->id,

                'year' => (
                    $quote->date_quote == null ||
                    $quote->date_quote == ""
                )
                    ? ''
                    : $quote->date_quote->year,

                'code' => (
                    $quote->code == null ||
                    $quote->code == ""
                )
                    ? ''
                    : $quote->code,

                'description' => (
                    $quote->description_quote == null ||
                    $quote->description_quote == ""
                )
                    ? ''
                    : $quote->description_quote,

                'date_quote' => (
                    $quote->date_quote == null ||
                    $quote->date_quote == ""
                )
                    ? ''
                    : $quote->date_quote->format('d/m/Y'),

                'order' => (
                    $quote->code_customer == null ||
                    $quote->code_customer == ""
                )
                    ? ''
                    : $quote->code_customer,

                'date_validate' => (
                    $quote->date_validate == null ||
                    $quote->date_validate == ""
                )
                    ? ''
                    : $quote->date_validate->format('d/m/Y'),

                'deadline' => (
                    $quote->payment_deadline_id == null ||
                    $quote->payment_deadline_id == ""
                )
                    ? ''
                    : optional($quote->deadline)->description,

                'time_delivery' =>
                    $quote->time_delivery . ' DÍAS',

                'customer' => empty($quote->customer_id)
                    ? ''
                    : optional($quote->customer)->business_name,

                'total_sunat' => number_format(
                    $quote->total_importe,
                    2
                ),

                'total_cliente' => number_format(
                    $quote->total_importe + $total_workforce,
                    2
                ),

                'total_services' => number_format(
                    $total_workforce,
                    2
                ),

                'currency' => (
                    $quote->currency_invoice == null ||
                    $quote->currency_invoice == ""
                )
                    ? ''
                    : $quote->currency_invoice,

                'state' => $state,
                'stateText' => $stateText,

                'created_at' => $quote->created_at
                    ->format('d/m/Y'),

                'creator' => (
                    !isset($quote->users[0]) ||
                    !$quote->users[0]->user
                )
                    ? ''
                    : $quote->users[0]->user->name,

                'decimals' => $stateDecimals,
                'send_state' => $quote->send_state,
                'has_active_sale' => false,
            ];
        }

        /*
         * ============================================================
         * INFORMACIÓN DE PAGINACIÓN
         * ============================================================
         */
        $pagination = [
            'currentPage' => (int) $pageNumber,
            'totalPages' => $totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords,
        ];

        return [
            'data' => $array,
            'pagination' => $pagination,
        ];
    }

    public function getDataQuotesSalesIndex(Request $request, $pageNumber = 1)
    {
        $branchScope = app(QuoteBranchReadScope::class);
        $perPage = 10;
        $description_quote = $request->input('description_quote');
        $year = $request->input('year');
        $code = $request->input('code');
        $order = $request->input('order');
        $customer = $request->input('customer');
        $creator = $request->input('creator');
        $stateQuote = $request->input('stateQuote');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $companyId = TenantContext::companyId();

        $query = $branchScope->local(Quote::query())->with([
            'customer',
            'deadline',
            'users',
        ])
            ->where(
                'company_id',
                $companyId
            )
            ->whereHas(
                'sales',
                function ($q) {
                    $q->where(
                        'state_annulled',
                        0
                    );
                }
            );

        if ($startDate != "" || $endDate != "")
        {
            $fechaInicio =
                Carbon::createFromFormat(
                    'd/m/Y',
                    $startDate
                );

            $fechaFinal =
                Carbon::createFromFormat(
                    'd/m/Y',
                    $endDate
                );

            $query
                ->whereDate(
                    'date_quote',
                    '>=',
                    $fechaInicio
                )
                ->whereDate(
                    'date_quote',
                    '<=',
                    $fechaFinal
                );
        }

        if (!$stateQuote) {
            $query
                ->where(
                    'state',
                    '<>',
                    'canceled'
                )
                ->where(
                    'state',
                    '<>',
                    'expired'
                )
                ->where(
                    'state_active',
                    'open'
                );
        }

        $query->orderBy(
            'created_at',
            'DESC'
        );

        // Aplicar filtros si se proporcionan
        if ($description_quote) {
            $query->where('description_quote', 'LIKE', '%'.$description_quote.'%');
        }

        if ($year) {
            $query->whereYear('date_quote', $year);
        }

        if ($code) {
            $query->where('code', 'LIKE', '%'.$code.'%');

        }

        if ($order) {
            $query->where('code_customer', 'LIKE', '%'.$order.'%');

        }

        if ($customer) {
            $query->whereHas('customer', function ($query2) use ($customer) {
                $query2->where('customer_id', $customer);
            });

        }

        if ($creator != "")
        {
            $query->whereHas('users', function ($query2) use ($creator) {
                $query2->where('user_id', $creator);
            });
        }

        if ($stateQuote) {
            // Creada, Enviada, confirmada, elevada, VB Finanzas, VB Operaciones, Finalizadas, Anuladas
            // created, send, confirm, raised, VB_finance, VB_operation, close, canceled
            $query->where(function ($subquery) use ($stateQuote) {
                $subquery->where(function ($q) use ($stateQuote) {
                    switch ($stateQuote) {
                        case 'created':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 0)
                                        ->orWhere('send_state', false);
                                });
                            break;
                        case 'send':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 1)
                                        ->orWhere('send_state', true);
                                });
                            break;
                        case 'close':
                            $q->where('state_active', 'close');
                            break;
                        case 'raised':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('state', '<>','canceled')
                                ->where('state_active', '<>','close');
                            break;
                        case 'confirm':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 0);
                            break;
                        case 'canceled':
                            $q->where('state', 'canceled');
                            break;
                        default:
                            // Lógica por defecto o manejo de errores si es necesario
                            break;
                    }
                });
            });
        }

        //$query = FinanceWork::with('quote', 'bank');

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $quotes = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        //dd($proformas);

        $array = [];

        foreach ( $quotes as $quote )
        {
            $state = "";
            $stateText = "";
            if ( $quote->state === 'created' ) {
                if ( $quote->send_state == 1 || $quote->send_state == true )
                {
                    $state = 'send';
                    $stateText = '<span class="badge bg-warning">Enviado</span>';
                } else {
                    $state = 'created';
                    $stateText = '<span class="badge bg-primary">Creada</span>';
                }
            }
            if ($quote->state_active === 'close'){
                $state = 'close';
                $stateText = '<span class="badge bg-danger">Finalizada</span>';
            } else {
                if ($quote->state === 'confirmed' && $quote->raise_status === 1){

                    $state = 'raise';
                    $stateText = '<span class="badge bg-success">Elevada</span>';
                }
                if ($quote->state === 'confirmed' && $quote->raise_status === 0){
                    $state = 'confirm';
                    $stateText =  '<span class="badge bg-success">Confirmada</span>';
                }
                if ($quote->state === 'canceled'){
                    $state = 'canceled';
                    $stateText = '<span class="badge bg-danger">Cancelada</span>';
                }
            }

            $stateDecimals = '';
            if ( $quote->state_decimals == 1 )
            {
                $stateDecimals = '<span class="badge bg-success">Mostrar</span>';
            } else {
                $stateDecimals = '<span class="badge bg-danger">Ocultar</span>';
            }

            $total_workforce  = 0;
            foreach($quote->equipments as $equipment)
            {
                foreach($equipment->workforces as $workforce)
                {
                    if ( $workforce->billable == false )
                    {
                        $total_workforce = $total_workforce + $workforce->total;
                    }

                }
            }

            array_push($array, [
                "id" => $quote->id,
                "year" => ( $quote->date_quote == null || $quote->date_quote == "") ? '':$quote->date_quote->year,
                "code" => ($quote->code == null || $quote->code == "") ? '': $quote->code,
                "description" => ($quote->description_quote == null || $quote->description_quote == "") ? '': $quote->description_quote,
                "date_quote" => ($quote->date_quote == null || $quote->date_quote == "") ? '': $quote->date_quote->format('d/m/Y'),
                "order" => ($quote->code_customer == null || $quote->code_customer == "") ? "": $quote->code_customer,
                "date_validate" => ($quote->date_validate == null || $quote->date_validate == "") ? '': $quote->date_validate->format('d/m/Y'),
                "deadline" => ($quote->payment_deadline_id == null || $quote->payment_deadline_id == "") ? "":$quote->deadline->description,
                "time_delivery" => $quote->time_delivery.' DÍAS',
                "customer" => ($quote->customer_id == "" || $quote->customer_id == null) ? "" : $quote->customer->business_name,
                "total_igv" => number_format($quote->total_importe/1.18, 2),
                "total" => number_format($quote->total_importe, 2),
                "total_sunat" => number_format($quote->total_importe, 2),
                "total_cliente" => number_format($quote->total_importe+$total_workforce, 2),
                "total_services" => number_format($total_workforce, 2),
                "currency" => ($quote->currency_invoice == null || $quote->currency_invoice == "") ? '': $quote->currency_invoice,
                "state" => $state,
                "stateText" => $stateText,
                "created_at" => $quote->created_at->format('d/m/Y'),
                "creator" => ($quote->users[0] == null) ? "": $quote->users[0]->user->name,
                "decimals" => $stateDecimals,
                "has_active_sale" => true,
                "send_state" => $quote->send_state
            ]);
        }

        $pagination = [
            'currentPage' => (int)$pageNumber,
            'totalPages' => (int)$totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords
        ];

        return ['data' => $array, 'pagination' => $pagination];
    }

    public function edit($id)
    {
        $begin = microtime(true);

        $companyId = TenantContext::companyId();

        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        $unitMeasures = UnitMeasure::all();

        /*
         * Customer es Tenant-level.
         */
        $customers = Customer::query()
            ->orderBy('business_name')
            ->get();

        $workforces = Workforce::with('unitMeasure')
            ->get();

        /*
         * CRÍTICO:
         * Quote debe pertenecer a la Company actual.
         */
        $quote = Quote::query()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->with([
                'customer',
                'deadline',

                'equipments' => function ($query) {
                    $query->with([

                        'consumables' => function ($consumableQuery) {
                            $consumableQuery->with([
                                'presentation',

                                'stockItem' => function ($stockItemQuery) {
                                    $stockItemQuery->with([
                                        'material.unitMeasure',
                                    ]);
                                },

                                'quoteStockLots',
                            ]);
                        },

                        'workforces',
                    ]);
                },
            ])
            ->firstOrFail();

        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')
            ->selectable($quote->payment_deadline_id)
            ->get();

        $images = [];

        $currency = app(
            SettingService::class
        )->get(
            'finance.base_currency'
        );

        /** @var TypeTaxResolverService $typeTaxResolver */
        $typeTaxResolver =
            app(
                TypeTaxResolverService::class
            );

        $defaultTax =
            $typeTaxResolver
                ->resolveWithSource();

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::id(),
            'action' => 'Editar cotizacion VISTA',
            'time' => $end,
        ]);

        return view(
            'quoteSale.edit',
            compact(
                'quote',
                'unitMeasures',
                'customers',
                'workforces',
                'permissions',
                'paymentDeadlines',
                'images',
                'currency',
                'defaultTax'
            )
        );
    }

    public function update(UpdateQuoteRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();
        $reservationService = app(QuoteStockReservationService::class);
        DB::beginTransaction();
        try {
            $quote = Quote::find($request->get('quote_id'));

            $quote->code = $request->get('code_quote');
            $quote->description_quote = $request->get('code_description');
            $quote->observations = $request->get('observations');
            $quote->date_quote = ($request->has('date_quote')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote')) : Carbon::now();
            $quote->date_validate = ($request->has('date_validate')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate')) : Carbon::now()->addDays(5);
            $quote->way_to_pay = ($request->has('way_to_pay')) ? $request->get('way_to_pay') : '';
            $quote->payment_deadline_id = ($request->has('payment_deadline')) ? $request->get('payment_deadline') : null;
            $quote->delivery_time = ($request->has('delivery_time')) ? $request->get('delivery_time') : '';
            $quote->customer_id = ($request->has('customer_id')) ? $request->get('customer_id') : null;
            $quote->contact_id = ($request->has('contact_id')) ? $request->get('contact_id') : null;
            //$quote->utility = ($request->has('utility')) ? $request->get('utility'): 0;
            //$quote->letter = ($request->has('letter')) ? $request->get('letter'): 0;
            //$quote->rent = ($request->has('taxes')) ? $request->get('taxes'): 0;
            $quote->currency_invoice = 'USD';
            $quote->currency_compra = null;
            $quote->currency_venta = null;
            $quote->total_soles = 0;
            $quote->save();

            $equipments = json_decode($request->get('equipments'));

            $totalQuote = 0;

            for ( $i=0; $i<sizeof($equipments); $i++ )
            {
                if ($equipments[$i]->quote === '' )
                {
                    $equipment = Equipment::create([
                        'quote_id' => $quote->id,
                        'description' => ($equipments[$i]->description == "" || $equipments[$i]->description == null) ? '':$equipments[$i]->description,
                        'detail' => ($equipments[$i]->detail == "" || $equipments[$i]->detail == null) ? '':$equipments[$i]->detail,
                        'quantity' => $equipments[$i]->quantity,
                        'utility' => $equipments[$i]->utility,
                        'rent' => $equipments[$i]->rent,
                        'letter' => $equipments[$i]->letter,
                        'total' => $equipments[$i]->total
                    ]);

                    $totalMaterial = 0;

                    $totalConsumable = 0;

                    $totalElectric = 0;

                    $totalWorkforces = 0;

                    $totalTornos = 0;

                    $totalDias = 0;

                    $materials = $equipments[$i]->materials;

                    $consumables = $equipments[$i]->consumables;

                    $electrics = $equipments[$i]->electrics;

                    $workforces = $equipments[$i]->workforces;

                    $tornos = $equipments[$i]->tornos;

                    $dias = $equipments[$i]->dias;

                    for ( $j=0; $j<sizeof($materials); $j++ )
                    {
                        $equipmentMaterial = EquipmentMaterial::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $materials[$j]->material->id,
                            'quantity' => (float) $materials[$j]->quantity,
                            'price' => (float) $materials[$j]->material->unit_price,
                            'length' => (float) ($materials[$j]->length == '') ? 0: $materials[$j]->length,
                            'width' => (float) ($materials[$j]->width == '') ? 0: $materials[$j]->width,
                            'percentage' => (float) $materials[$j]->quantity,
                            'state' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Falta comprar':'En compra',
                            'availability' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Agotado':'Completo',
                            'total' => (float) $materials[$j]->material->unit_price*(float) $materials[$j]->quantity,
                        ]);

                        $totalMaterial += $equipmentMaterial->total;
                    }

                    // CONSUMABLES
                    @bcscale(10);

                    for ($k = 0; $k < sizeof($consumables); $k++) {
                        $c = $consumables[$k];

                        $materialId = (int) ($c->id ?? 0);
                        $stockItemId = (int) ($c->stockItemId ?? 0);

                        $material = Material::find($materialId);
                        if (!$material) {
                            throw new \Exception("El material con ID {$materialId} no existe.");
                        }

                        $stockItem = StockItem::with(['material'])
                            ->where('id', $stockItemId)
                            ->where('material_id', $materialId)
                            ->first();

                        if (!$stockItem) {
                            throw new \Exception("El stock item con ID {$stockItemId} no existe o no pertenece al material {$material->full_name}.");
                        }

                        // 1) Inputs del front
                        $frontQty = (float) ($c->quantity ?? 0);
                        if ($frontQty <= 0) {
                            throw new \Exception("Cantidad inválida para material {$stockItem->display_name}.");
                        }

                        $presentationId = $c->presentation_id ?? null;

                        $valorUnitarioReal = (string) ($c->valorReal ?? $c->valor ?? '0');
                        $priceReal         = (string) ($c->priceReal ?? $c->price ?? '0');
                        $importeReal       = (string) ($c->importe ?? '0');

                        if ((float) $valorUnitarioReal < 0 || (float) $priceReal < 0 || (float) $importeReal < 0) {
                            throw new \Exception("Valores inválidos (precio/valor/total) para {$stockItem->display_name}.");
                        }

                        // 2) Presentación / unidades equivalentes
                        $packs = null;
                        $unitsPerPack = null;

                        if (!empty($presentationId)) {
                            $packs = (int) $frontQty;
                            if ($packs < 1) {
                                throw new \Exception("Cantidad de paquetes inválida para material {$stockItem->display_name}.");
                            }

                            $presentation = MaterialPresentation::where('id', $presentationId)
                                ->where('material_id', $materialId)
                                ->where('active', 1)
                                ->first();

                            if (!$presentation) {
                                throw new \Exception("La presentación seleccionada no es válida o no pertenece al material {$stockItem->display_name}.");
                            }

                            $unitsPerPack = (int) $presentation->quantity;
                            if ($unitsPerPack < 1) {
                                throw new \Exception("La presentación tiene cantidad inválida para {$stockItem->display_name}.");
                            }

                            $requestedUnits = (float) ($packs * $unitsPerPack);
                        } else {
                            $requestedUnits = (float) ($c->units_equivalent ?? $frontQty);

                            if ($requestedUnits <= 0) {
                                throw new \Exception("Cantidad inválida para material {$stockItem->display_name}.");
                            }
                        }

                        // 3) Validar stock disponible real por lotes
                        $available = $reservationService->getAvailableStockByStockItem($stockItem->id);

                        if ($requestedUnits > $available) {
                            throw new \Exception(
                                "El material {$stockItem->material->full_name} no cuenta con stock suficiente. " .
                                "Requerido: {$requestedUnits}. Disponible: {$available}."
                            );
                        }

                        $availability = ($requestedUnits > $available) ? 'Agotado' : 'Completo';
                        $state        = ($requestedUnits > $available) ? 'Falta comprar' : 'En compra';

                        // 4) Promo limit
                        if (($c->type_promo ?? null) === "limit") {
                            $promotion = PromotionLimit::where('material_id', $materialId)
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $remaining = (float) $promotion->limit_quantity - (float) $usage->used_quantity;

                                if ($remaining < $requestedUnits) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                $usage->increment('used_quantity', $requestedUnits);
                            }
                        }

                        // 5) Guardar consumable
                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => $availability,
                            'state' => $state,
                            'equipment_id' => $equipment->id,
                            'material_id' => $materialId,
                            'stock_item_id' => $stockItemId,
                            'quantity' => $requestedUnits,
                            'price' => $priceReal,
                            'valor_unitario' => $valorUnitarioReal,
                            'discount' => (string) ($c->discount ?? '0'),
                            'total' => $importeReal,
                            'type_promo' => $c->type_promo ?? null,
                            'material_presentation_id' => $presentationId,
                            'packs' => $packs,
                            'units_per_pack' => $unitsPerPack,
                        ]);

                        $totalConsumable = bcadd((string) $totalConsumable, (string) $importeReal, 10);

                        // 6) Reservar lotes para cotización
                        $reservationService->reserveForQuoteDetail(
                            (int) $quote->id,
                            (int) $equipmentConsumable->id,
                            (int) $stockItemId,
                            (float) $requestedUnits
                        );

                        // 7) Vincular promo con el detalle
                        if (($c->type_promo ?? null) === "limit") {
                            $promotion = PromotionLimit::where('material_id', $materialId)
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if ($usage) {
                                    $usage->equipment_id = $equipment->id;
                                    $usage->equipment_consumable_id = $equipmentConsumable->id;
                                    $usage->quote_id = $equipment->quote_id;
                                    $usage->save();
                                }
                            }
                        }
                    }

                    for ( $k=0; $k<sizeof($electrics); $k++ )
                    {
                        $equipmentElectric = EquipmentElectric::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $electrics[$k]->id,
                            'quantity' => (float) $electrics[$k]->quantity,
                            'price' => (float) $electrics[$k]->price,
                            'total' => (float) $electrics[$k]->quantity*(float) $electrics[$k]->price,
                        ]);

                        $totalElectric += $equipmentElectric->total;
                    }

                    for ( $w=0; $w<sizeof($workforces); $w++ )
                    {
                        $equipmentWorkforce = EquipmentWorkforce::create([
                            'equipment_id' => $equipment->id,
                            'description' => $workforces[$w]->description,
                            'price' => (float) $workforces[$w]->price,
                            'quantity' => (float) $workforces[$w]->quantity,
                            'total' => (float) $workforces[$w]->price*(float) $workforces[$w]->quantity,
                            'unit' => $workforces[$w]->unit,
                        ]);

                        $totalWorkforces += $equipmentWorkforce->total;
                    }

                    for ( $r=0; $r<sizeof($tornos); $r++ )
                    {
                        $equipmenttornos = EquipmentTurnstile::create([
                            'equipment_id' => $equipment->id,
                            'description' => $tornos[$r]->description,
                            'price' => (float) $tornos[$r]->price,
                            'quantity' => (float) $tornos[$r]->quantity,
                            'total' => (float) $tornos[$r]->price*(float) $tornos[$r]->quantity
                        ]);

                        $totalTornos += $equipmenttornos->total;
                    }

                    for ( $d=0; $d<sizeof($dias); $d++ )
                    {
                        $equipmentdias = EquipmentWorkday::create([
                            'equipment_id' => $equipment->id,
                            'description' => $dias[$d]->description,
                            'quantityPerson' => (float) $dias[$d]->quantity,
                            'hoursPerPerson' => (float) $dias[$d]->hours,
                            'pricePerHour' => (float) $dias[$d]->price,
                            'total' => (float) $dias[$d]->quantity*(float) $dias[$d]->hours*(float) $dias[$d]->price
                        ]);

                        $totalDias += $equipmentdias->total;
                    }

                    //$totalQuote += ($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity;

                    //$equipment->total = ($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos + $totalDias)* (float)$equipment->quantity;

                    // Cambio el 16/01/2024
                    //$totalEquipo = (($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos) * (float)$equipment->quantity) + $totalDias;
                    $totalEquipo = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity);
                    $totalEquipmentU = $totalEquipo*(($equipment->utility/100)+1);
                    $totalEquipmentL = $totalEquipmentU*(($equipment->letter/100)+1);
                    $totalEquipmentR = $totalEquipmentL*(($equipment->rent/100)+1);

                    $totalQuote += $totalEquipmentR;

                    $equipment->total = $totalEquipo;

                    $equipment->save();
                }

            }

            $quote->total += $totalQuote;

            $quote->save();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Editar cotizaciones POST',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Nuevos equipos guardados con éxito.'], 200);

    }

    public function destroy(Quote $quote)
    {
        $begin = microtime(true);

        try {

            $companyId =
                TenantContext::companyId();

            DB::transaction(
                function () use (
                    $quote,
                    $companyId,
                    $begin
                ) {

                    /*
                     * ==========================================================
                     * 1. OBTENER Y BLOQUEAR QUOTE DE LA COMPANY ACTUAL
                     * ==========================================================
                     *
                     * BelongsToTenant protege el Tenant.
                     *
                     * company_id protege explícitamente frente a otras
                     * Companies pertenecientes al mismo Tenant.
                     */

                    $quote =
                        Quote::query()
                            ->where(
                                'id',
                                $quote->id
                            )
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->where('tenant_id', TenantContext::tenantId())
                            ->where('branch_id', TenantContext::branchId())
                            ->lockForUpdate()
                            ->firstOrFail();

                    /*
                     * ==========================================================
                     * 2. VALIDAR ESTADO
                     * ==========================================================
                     */

                    if (
                        $quote->state ===
                        'canceled'
                    ) {

                        throw new \Exception(
                            'La cotización ya fue anulada.'
                        );
                    }

                    /*
                     * ==========================================================
                     * 3. VALIDAR VENTA ACTIVA
                     * ==========================================================
                     *
                     * Una Quote que tenga una venta activa asociada
                     * NO puede anularse.
                     *
                     * No dependemos de quote.state para aplicar esta regla.
                     *
                     * Sale se comprueba por tenant, empresa y quote_id.
                     */

                    $activeSale =
                        Sale::query()
                            ->where('tenant_id', TenantContext::tenantId())
                            ->where('company_id', $companyId)
                            ->where(
                                'quote_id',
                                $quote->id
                            )
                            ->where(
                                'state_annulled',
                                0
                            )
                            ->lockForUpdate()
                            ->first();

                    if ($activeSale) {

                        throw new \Exception(
                            'No se puede anular esta cotización porque tiene una venta activa asociada. Primero debe anular la venta.'
                        );
                    }

                    /*
                     * ==========================================================
                     * 4. LIBERAR TODAS LAS RESERVAS
                     * ==========================================================
                     *
                     * QuoteStockReservationService se encarga de:
                     *
                     * - liberar qty_reserved
                     * - liberar StockLot reservado
                     * - devolver Items reserved -> entered
                     * - eliminar QuoteStockLot
                     * - sincronizar InventoryLevel
                     *
                     * Funciona tanto para productos normales
                     * como para productos itemeables.
                     */

                    /** @var QuoteStockReservationService $reservationService */
                    $reservationService =
                        app(
                            QuoteStockReservationService::class
                        );

                    $reservationService
                        ->releaseReservationsByQuote(
                            (int) $quote->id
                        );

                    /*
                     * ==========================================================
                     * 5. PROMOCIONES LEGACY
                     * ==========================================================
                     *
                     * IMPORTANTE:
                     *
                     * NO eliminamos PromotionUsage aquí.
                     *
                     * La implementación legacy puede acumular varios usos
                     * dentro de un mismo registro mediante used_quantity.
                     *
                     * Eliminar el registro solamente porque actualmente
                     * referencia a un EquipmentConsumable de esta Quote
                     * podría eliminar consumo correspondiente a operaciones
                     * anteriores.
                     *
                     * Esto queda registrado como PROMO-01.
                     *
                     * Además, discount / type_promo ya no participan
                     * en los cálculos tributarios de TAX-01.
                     */

                    /*
                     * ==========================================================
                     * 6. MARCAR QUOTE COMO CANCELADA
                     * ==========================================================
                     *
                     * Esto es una anulación lógica.
                     *
                     * NO ejecutamos delete().
                     *
                     * Conservamos:
                     *
                     * - Quote
                     * - Equipment
                     * - EquipmentConsumable
                     * - EquipmentWorkforce
                     * - snapshots de precios
                     * - snapshots tributarios
                     *
                     * para histórico y auditoría.
                     */

                    $quote->state =
                        'canceled';

                    $quote->save();

                    /*
                     * ==========================================================
                     * 7. AUDITORÍA
                     * ==========================================================
                     */

                    Audit::create([
                        'user_id' =>
                            Auth::id(),

                        'action' =>
                            'Anular cotización de venta ' .
                            $quote->code,

                        'time' =>
                            microtime(true)
                            -
                            $begin,
                    ]);
                }
            );

            return response()->json([
                'ok' =>
                    true,

                'message' =>
                    'La cotización fue anulada correctamente.',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $messages = collect($errors)->flatten()->all();
            return response()->json([
                'ok' => false,
                'message' => $messages[0] ?? 'No se pudo liberar las reservas de la cotización.',
                'errors' => $errors,
            ], 422);

        } catch (\Throwable $e) {

            return response()->json([
                'ok' =>
                    false,

                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

    public function updateEquipmentOfQuoteO(Request $request, $id_equipment, $id_quote)
    {
        $begin = microtime(true);

        $quote = Quote::findOrFail($id_quote);

        DB::beginTransaction();

        try {
            /** @var QuoteStockReservationService $reservationService */
            $reservationService = app(QuoteStockReservationService::class);

            $dateQuote = $request->get('date_quote')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote'))
                : null;

            $dateValidate = $request->get('date_validate')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate'))
                : null;

            $quote->update([
                'description_quote' => $request->get('descriptionQuote'),
                'code' => $request->get('codeQuote'),
                'date_quote' => $dateQuote,
                'date_validate' => $dateValidate,
                'way_to_pay' => $request->get('way_to_pay') ?? '',
                'delivery_time' => $request->get('delivery_time') ?? '',
                'customer_id' => $request->get('customer_id') ?? null,
                'contact_id' => $request->get('contact_id') ?? null,
                'payment_deadline_id' => $request->get('payment_deadline') ?? null,
                'observations' => $request->get('observations') ?? '',
            ]);

            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id', $quote->id)
                ->firstOrFail();

            /*
             * =========================================================
             * 1) Totales generales de cotización
             * =========================================================
             */
            $quote->descuento = (string) $request->input('descuento', '0');
            $quote->gravada = (string) $request->input('gravada', '0');
            $quote->igv_total = (string) $request->input('igv_total', '0');
            $quote->total_importe = (string) $request->input('total_importe', '0');

            $quote->discount_type = $request->input('discount_type');
            $quote->discount_input_mode = $request->input('discount_input_mode');
            $quote->discount_input_value = $request->input('discount_input_value');
            $quote->save();

            /*
             * =========================================================
             * 2) Liberar reservas y eliminar consumables anteriores
             *
             * releaseReservationsByQuoteDetail:
             * - devuelve qty_reserved al lote
             * - si QuoteStockLot tiene item_ids, cambia Items:
             *   reserved -> entered
             * - elimina QuoteStockLot
             * =========================================================
             */
            foreach ($equipment_quote->consumables as $consumable) {
                $reservationService->releaseReservationsByQuoteDetail(
                    (int) $quote->id,
                    (int) $consumable->id
                );

                $consumable->delete();
            }

            /*
             * Limpiar relaciones restantes.
             */
            foreach ($equipment_quote->workforces as $workforce) {
                $workforce->delete();
            }

            foreach ($equipment_quote->materials as $material) {
                $material->delete();
            }

            foreach ($equipment_quote->electrics as $electric) {
                $electric->delete();
            }

            foreach ($equipment_quote->turnstiles as $turnstile) {
                $turnstile->delete();
            }

            foreach ($equipment_quote->workdays as $workday) {
                $workday->delete();
            }

            /*
             * =========================================================
             * 3) Obtener payload
             * =========================================================
             */
            $equipments = $request->input('equipment');

            if (!is_array($equipments) || count($equipments) === 0) {
                throw new \Exception('No se recibieron equipos.');
            }

            $equip = $equipments[0];

            /*
             * =========================================================
             * 4) Actualizar equipo principal
             * =========================================================
             */
            $equipment_quote->description = $equip['description'] ?? '';
            $equipment_quote->detail = $equip['detail'] ?? '';
            $equipment_quote->quantity = $equip['quantity'] ?? 1;
            $equipment_quote->utility = $equip['utility'] ?? 0;
            $equipment_quote->rent = $equip['rent'] ?? 0;
            $equipment_quote->letter = $equip['letter'] ?? 0;
            $equipment_quote->total = (string) ($equip['total'] ?? '0');
            $equipment_quote->save();

            /*
             * =========================================================
             * 5) Re-crear consumables y sus reservas
             * =========================================================
             */
            $consumables = $equip['consumables'] ?? [];

            @bcscale(10);

            foreach ($consumables as $c) {
                /*
                 * En la nueva lógica, id representa StockItem.
                 * Se deja fallback para payloads antiguos que manden
                 * stock_item_id por separado.
                 */
                $stockItemId = (int) (
                    $c['stock_item_id']
                    ?? $c['stockItemId']
                    ?? $c['id']
                    ?? 0
                );

                if ($stockItemId <= 0) {
                    throw new \Exception('No se pudo identificar el stock item del producto.');
                }

                $stockItem = StockItem::with(['material'])
                    ->where('id', $stockItemId)
                    ->first();

                if (!$stockItem) {
                    throw new \Exception(
                        "El stock item con ID {$stockItemId} no existe."
                    );
                }

                if (!$stockItem->material) {
                    throw new \Exception(
                        "El stock item con ID {$stockItemId} no tiene material asociado."
                    );
                }

                $material = $stockItem->material;
                $materialId = (int) $material->id;

                /*
                 * La validación itemeable se realiza en backend.
                 * No confiamos en is_itemeable enviado por JavaScript.
                 */
                $isItemeable = (int) ($material->tipo_venta_id ?? 0) === 3;

                /*
                 * ---------------------------------------------------------
                 * Presentación y unidades equivalentes
                 * ---------------------------------------------------------
                 */
                $presentationId = $c['presentation_id'] ?? null;
                $packs = null;
                $unitsPerPack = null;
                $requestedUnits = 0;

                if (!empty($presentationId)) {
                    $packs = (int) ($c['quantity'] ?? 0);

                    if ($packs < 1) {
                        throw new \Exception(
                            "Packs inválidos para {$stockItem->display_name}."
                        );
                    }

                    $presentation = MaterialPresentation::where('id', $presentationId)
                        ->where('material_id', $materialId)
                        ->where('active', 1)
                        ->first();

                    if (!$presentation) {
                        throw new \Exception(
                            "Presentación inválida para {$stockItem->display_name}."
                        );
                    }

                    $unitsPerPack = (int) $presentation->quantity;

                    if ($unitsPerPack < 1) {
                        throw new \Exception(
                            "Units per pack inválido para {$stockItem->display_name}."
                        );
                    }

                    $requestedUnits = (float) ($packs * $unitsPerPack);
                } else {
                    $requestedUnits = (float) (
                        $c['units_equivalent']
                        ?? $c['quantity']
                        ?? 0
                    );

                    if ($requestedUnits <= 0) {
                        throw new \Exception(
                            "Cantidad inválida para {$stockItem->display_name}."
                        );
                    }
                }

                /*
                 * ---------------------------------------------------------
                 * Valores monetarios enviados desde frontend
                 * ---------------------------------------------------------
                 */
                $valorUnitarioReal = (string) (
                    $c['valorReal']
                    ?? $c['valor']
                    ?? '0'
                );

                $priceReal = (string) (
                    $c['priceReal']
                    ?? $c['price']
                    ?? '0'
                );

                $importeReal = (string) (
                    $c['importe']
                    ?? '0'
                );

                if (
                    (float) $valorUnitarioReal < 0 ||
                    (float) $priceReal < 0 ||
                    (float) $importeReal < 0
                ) {
                    throw new \Exception(
                        "Valores inválidos (precio/valor/total) para {$stockItem->display_name}."
                    );
                }

                /*
                 * ---------------------------------------------------------
                 * Validar itemeables
                 * ---------------------------------------------------------
                 */
                $selectedItemIds = [];

                if ($isItemeable) {
                    $rawSelectedItemIds = $c['selected_item_ids'] ?? [];

                    /*
                     * Por seguridad, soportamos que llegue como JSON string
                     * o como array.
                     */
                    if (is_string($rawSelectedItemIds)) {
                        $decodedIds = json_decode($rawSelectedItemIds, true);

                        $rawSelectedItemIds = is_array($decodedIds)
                            ? $decodedIds
                            : [];
                    }

                    if (!is_array($rawSelectedItemIds)) {
                        $rawSelectedItemIds = [];
                    }

                    $selectedItemIds = collect($rawSelectedItemIds)
                        ->map(function ($itemId) {
                            return (int) $itemId;
                        })
                        ->filter(function ($itemId) {
                            return $itemId > 0;
                        })
                        ->values()
                        ->toArray();

                    if (empty($selectedItemIds)) {
                        throw new \Exception(
                            "Debe seleccionar los ítems físicos para {$material->full_name}."
                        );
                    }

                    if (count($selectedItemIds) !== count(array_unique($selectedItemIds))) {
                        throw new \Exception(
                            "Se detectaron ítems repetidos para {$material->full_name}."
                        );
                    }

                    /*
                     * Cada Item representa una unidad completa.
                     */
                    if ((int) $requestedUnits !== count($selectedItemIds)) {
                        throw new \Exception(
                            "La cantidad de ítems seleccionados no coincide con la cantidad requerida para {$material->full_name}. " .
                            "Requerido: {$requestedUnits}. Seleccionados: " . count($selectedItemIds) . '.'
                        );
                    }
                }

                /*
                 * ---------------------------------------------------------
                 * Stock disponible por lotes
                 * ---------------------------------------------------------
                 */
                $available = $reservationService->getAvailableStockByStockItem(
                    $stockItemId
                );

                if ($requestedUnits > $available) {
                    throw new \Exception(
                        "Stock insuficiente para {$material->full_name}. " .
                        "Requerido: {$requestedUnits}. Disponible: {$available}."
                    );
                }

                $availability = 'Completo';
                $state = 'En compra';

                /*
                 * ---------------------------------------------------------
                 * Crear nuevo detalle consumable
                 * ---------------------------------------------------------
                 */
                $equipmentConsumable = EquipmentConsumable::create([
                    'equipment_id' => $equipment_quote->id,
                    'material_id' => $materialId,
                    'stock_item_id' => $stockItemId,

                    'material_presentation_id' => $presentationId,
                    'packs' => $packs,
                    'units_per_pack' => $unitsPerPack,

                    'quantity' => (float) $requestedUnits,

                    'price' => $priceReal,
                    'valor_unitario' => $valorUnitarioReal,
                    'total' => $importeReal,

                    'discount' => (string) ($c['discount'] ?? '0'),
                    'type_promo' => $c['type_promo'] ?? null,

                    'availability' => $availability,
                    'state' => $state,
                ]);

                /*
                 * ---------------------------------------------------------
                 * Reservar stock
                 * ---------------------------------------------------------
                 */
                if ($isItemeable) {
                    $reservationService->reserveItemeableForQuoteDetail(
                        (int) $quote->id,
                        (int) $equipmentConsumable->id,
                        (int) $stockItemId,
                        $selectedItemIds
                    );
                } else {
                    $reservationService->reserveForQuoteDetail(
                        (int) $quote->id,
                        (int) $equipmentConsumable->id,
                        (int) $stockItemId,
                        (float) $requestedUnits
                    );
                }
            }

            /*
             * =========================================================
             * 6) Re-crear servicios adicionales
             * =========================================================
             */
            $workforces = $equip['workforces'] ?? [];

            foreach ($workforces as $workforce) {
                EquipmentWorkforce::create([
                    'equipment_id' => $equipment_quote->id,
                    'description' => $workforce['description'] ?? '',
                    'price' => (string) ($workforce['price'] ?? '0'),
                    'quantity' => (float) ($workforce['quantity'] ?? 0),
                    'total' => (string) ($workforce['importe'] ?? '0'),
                    'unit' => $workforce['unit'] ?? '',
                    'billable' => isset($workforce['billable'])
                        ? (int) $workforce['billable']
                        : 1,
                ]);
            }

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificar equipo de cotizacion (EDIT)',
                'time' => microtime(true) - $begin
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Equipo guardado con éxito.',
                'equipment' => $equipment_quote,
                'quote' => $quote
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }
    }

    public function updateEquipmentOfQuote(
        Request $request,
        $id_equipment,
        $id_quote
    ) {
        $begin = microtime(true);

        $companyId = TenantContext::companyId();

        DB::beginTransaction();

        try {

            /** @var QuoteStockReservationService $reservationService */
            $reservationService = app(
                QuoteStockReservationService::class
            );

            /** @var TypeTaxResolverService $typeTaxResolver */
            $typeTaxResolver = app(
                TypeTaxResolverService::class
            );

            /** @var QuoteTaxCalculatorService $taxCalculator */
            $taxCalculator = app(
                QuoteTaxCalculatorService::class
            );

            /** @var PriceResolverService $priceResolver */
            $priceResolver = app(
                PriceResolverService::class
            );

            /*
             * =========================================================
             * 1) VALIDAR QUOTE DE LA COMPANY ACTUAL
             * =========================================================
             */

            $quote = Quote::query()
                ->where(
                    'id',
                    $id_quote
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->firstOrFail();

            /*
             * =========================================================
             * 2) VALIDAR DATOS GENERALES
             * =========================================================
             */

            $customerId =
                $request->get(
                    'customer_id'
                );

            $contactId =
                $request->get(
                    'contact_id'
                );

            if ($customerId) {
                Customer::query()
                    ->where(
                        'id',
                        $customerId
                    )
                    ->firstOrFail();
            }

            if ($contactId) {
                ContactName::query()
                    ->where(
                        'id',
                        $contactId
                    )
                    ->where(
                        'customer_id',
                        $customerId
                    )
                    ->firstOrFail();
            }

            $paymentDeadlineId =
                $request->get(
                    'payment_deadline'
                );

            if ($paymentDeadlineId) {
                PaymentDeadline::query()
                    ->where(
                        'id',
                        $paymentDeadlineId
                    )
                    ->where(
                        'type',
                        'quotes'
                    )
                    ->selectable($quote->payment_deadline_id)->firstOrFail();

            }

            $dateQuote =
                $request->get(
                    'date_quote'
                )
                    ? Carbon::createFromFormat(
                    'd/m/Y',
                    $request->get(
                        'date_quote'
                    )
                )
                    : null;

            $dateValidate =
                $request->get(
                    'date_validate'
                )
                    ? Carbon::createFromFormat(
                    'd/m/Y',
                    $request->get(
                        'date_validate'
                    )
                )
                    : null;

            /*
             * =========================================================
             * 3) ACTUALIZAR DATOS GENERALES DE QUOTE
             * =========================================================
             */

            $quote->update([
                'description_quote' =>
                    $request->get(
                        'descriptionQuote'
                    ),

                'code' =>
                    $request->get(
                        'codeQuote'
                    ),

                'date_quote' =>
                    $dateQuote,

                'date_validate' =>
                    $dateValidate,

                'way_to_pay' =>
                    $request->get(
                        'way_to_pay'
                    )
                    ?? '',

                'delivery_time' =>
                    $request->get(
                        'delivery_time'
                    )
                    ?? '',

                'customer_id' =>
                    $customerId
                        ?: null,

                'contact_id' =>
                    $contactId
                        ?: null,

                'payment_deadline_id' =>
                    $paymentDeadlineId
                        ?: null,

                'observations' =>
                    $request->get(
                        'observations'
                    )
                    ?? '',
            ]);

            /*
             * =========================================================
             * 4) VALIDAR EQUIPMENT PERTENECE A LA QUOTE
             * =========================================================
             */

            $equipmentQuote =
                Equipment::query()
                    ->where(
                        'id',
                        $id_equipment
                    )
                    ->where(
                        'quote_id',
                        $quote->id
                    )
                    ->firstOrFail();

            /*
             * =========================================================
             * 5) CONFIGURACIÓN DEL DESCUENTO GLOBAL
             * =========================================================
             *
             * Los totales no se aceptan desde frontend.
             *
             * QuoteTaxCalculatorService los recalcula al final.
             */

            $quote->discount_type =
                $request->input(
                    'discount_type'
                );

            $quote->discount_input_mode =
                $request->input(
                    'discount_input_mode'
                );

            $quote->discount_input_value =
                $request->input(
                    'discount_input_value'
                );

            $quote->save();

            /*
             * =========================================================
             * 6) LIBERAR RESERVAS Y ELIMINAR DETALLES ANTERIORES
             * =========================================================
             */

            $equipmentQuote->load([
                'consumables',
                'workforces',
            ]);

            /*
             * =========================================================
             * SNAPSHOTS ANTERIORES DE PRODUCTOS
             * =========================================================
             *
             * Edit reconstruye físicamente EquipmentConsumable.
             *
             * Una línea existente debe conservar:
             *
             * - TypeTax histórico
             * - tax_rate histórico
             * - precio histórico
             * - metadata legacy de promoción
             */

            $consumableSnapshots = [];

            foreach (
                $equipmentQuote->consumables
                as $consumable
            ) {

                $consumableSnapshots[
                (int) $consumable->id
                ] = [
                    'type_tax_id' =>
                        $consumable
                            ->type_tax_id,

                    'tax_rate' =>
                        $consumable
                            ->tax_rate,

                    'price' =>
                        $consumable
                            ->price,

                    'discount' =>
                        $consumable
                            ->discount,

                    'type_promo' =>
                        $consumable
                            ->type_promo,
                ];
            }

            /*
             * Liberar reservas y eliminar líneas anteriores.
             */

            foreach (
                $equipmentQuote->consumables
                as $consumable
            ) {

                $reservationService
                    ->releaseReservationsByQuoteDetail(
                        (int) $quote->id,
                        (int) $consumable->id
                    );

                $consumable->delete();
            }

            /*
             * =========================================================
             * SNAPSHOTS ANTERIORES DE SERVICIOS
             * =========================================================
             */

            $workforceSnapshots = [];

            foreach (
                $equipmentQuote->workforces
                as $workforce
            ) {

                $workforceSnapshots[
                (int) $workforce->id
                ] = [
                    'type_tax_id' =>
                        $workforce
                            ->type_tax_id,

                    'tax_rate' =>
                        $workforce
                            ->tax_rate,
                ];
            }

            foreach (
                $equipmentQuote->workforces
                as $workforce
            ) {
                $workforce->delete();
            }

            /*
             * =========================================================
             * 7) OBTENER PAYLOAD
             * =========================================================
             */

            $equipments =
                $request->input(
                    'equipment'
                );

            if (
                !is_array(
                    $equipments
                ) ||
                count(
                    $equipments
                ) === 0
            ) {
                throw new \Exception(
                    'No se recibió la información de la cotización.'
                );
            }

            /*
             * Regla actual:
             * exactamente un Equipment.
             */
            $equip =
                $equipments[0];

            /*
             * =========================================================
             * 8) ACTUALIZAR EQUIPMENT PRINCIPAL
             * =========================================================
             */

            $equipmentQuote->description =
                $equip[
                'description'
                ]
                ?? '';

            $equipmentQuote->detail =
                $equip[
                'detail'
                ]
                ?? '';

            /*
             * Regla funcional:
             * Equipment.quantity siempre es 1.
             */
            $equipmentQuote->quantity = 1;

            /*
             * utility / rent / letter ya no forman parte
             * del flujo funcional moderno.
             *
             * Se mantienen en 0 por compatibilidad legacy.
             */
            $equipmentQuote->utility = 0;
            $equipmentQuote->rent = 0;
            $equipmentQuote->letter = 0;

            /*
             * Se recalculará al final.
             */
            $equipmentQuote->total = 0;

            $equipmentQuote->save();

            /*
             * =========================================================
             * 9) RECREAR CONSUMABLES Y RESERVAS
             * =========================================================
             */

            $consumables =
                $equip[
                'consumables'
                ]
                ?? [];

            @bcscale(10);

            $productTaxLines = [];
            $serviceTaxLines = [];

            $totalConsumable = 0;
            $totalWorkforces = 0;

            /*
             * IDs recreados que devolveremos al frontend.
             *
             * El índice coincide con el índice enviado
             * por consumables/workforces.
            */
            $consumableSync = [];
            $workforceSync = [];

            foreach (
                $consumables
                as $c
            ) {

                /*
                 * stock_item_id es la identidad real moderna.
                 */

                $stockItemId =
                    (int) (
                        $c[
                        'stock_item_id'
                        ]
                        ??
                        $c[
                        'stockItemId'
                        ]
                        ??
                        $c[
                        'id'
                        ]
                        ??
                        0
                    );

                if (
                    $stockItemId <= 0
                ) {
                    throw new \Exception(
                        'No se pudo identificar el producto seleccionado.'
                    );
                }

                /*
                 * =====================================================
                 * VALIDAR STOCKITEM
                 * =====================================================
                 *
                 * Debe:
                 *
                 * - pertenecer al Tenant actual
                 * - estar activo
                 * - estar habilitado para la Company actual
                 */

                $stockItem =
                    StockItem::query()
                        ->with([
                            'material',
                        ])
                        ->where(
                            'id',
                            $stockItemId
                        )
                        ->where(
                            'is_active',
                            1
                        )
                        ->enabledForCompany(
                            $companyId
                        )
                        ->first();

                if (!$stockItem) {
                    throw new \Exception(
                        'El producto seleccionado no está disponible para la empresa actual.'
                    );
                }

                if (
                !$stockItem->material
                ) {
                    throw new \Exception(
                        "El StockItem {$stockItemId} no tiene material asociado."
                    );
                }

                $material =
                    $stockItem->material;

                $materialId =
                    (int) $material->id;

                /*
                 * =====================================================
                 * ITEMEABLE
                 * =====================================================
                 */

                $isItemeable =
                    (int) (
                        $material
                            ->tipo_venta_id
                        ?? 0
                    ) === 3;

                /*
                 * =====================================================
                 * PRESENTACIÓN
                 * =====================================================
                 */

                $presentationId =
                    $c[
                    'presentation_id'
                    ]
                    ?? null;

                /*
                 * =====================================================
                 * IDENTIFICAR LÍNEA HISTÓRICA
                 * =====================================================
                 *
                 * equipment_consumable_id:
                 *
                 * > 0 = línea preexistente.
                 * 0   = línea agregada durante Edit.
                 */

                $equipmentConsumableId =
                    (int) (
                        $c[
                        'equipment_consumable_id'
                        ]
                        ?? 0
                    );

                $existingSnapshot =
                    $equipmentConsumableId > 0
                        ? (
                        $consumableSnapshots[
                        $equipmentConsumableId
                        ]
                        ?? null
                    )
                        : null;

                /*
                 * Si se declaró una línea histórica pero el ID
                 * no pertenece a esta Quote, no hacemos fallback.
                 */
                if (
                    $equipmentConsumableId > 0 &&
                    !$existingSnapshot
                ) {
                    throw new \RuntimeException(
                        'No se pudo identificar una de las líneas originales de la cotización. '. $equipmentConsumableId . "  " . !$existingSnapshot
                    );
                }

                /*
                 * =====================================================
                 * SNAPSHOT TRIBUTARIO
                 * =====================================================
                 *
                 * Línea existente:
                 * conservar TypeTax histórico.
                 *
                 * Línea nueva:
                 * tomar TypeTax actual del Material.
                 */

                if (
                    $existingSnapshot &&
                    $existingSnapshot[
                    'type_tax_id'
                    ] &&
                    $existingSnapshot[
                    'tax_rate'
                    ] !== null
                ) {

                    $typeTaxId =
                        (int)
                        $existingSnapshot[
                        'type_tax_id'
                        ];

                    $taxRate =
                        (float)
                        $existingSnapshot[
                        'tax_rate'
                        ];

                } else {

                    $taxData =
                        $typeTaxResolver
                            ->resolveMaterialWithSource(
                                $material
                            );

                    $typeTaxId =
                        (int)
                        $taxData[
                        'type_tax_id'
                        ];

                    $taxRate =
                        (float)
                        $taxData[
                        'tax_rate'
                        ];
                }

                /*
                 * =====================================================
                 * PRESENTACIÓN / UNIDADES FÍSICAS
                 * =====================================================
                 */

                $packs = null;
                $unitsPerPack = null;
                $requestedUnits = 0;

                if (
                !empty(
                $presentationId
                )
                ) {

                    $packs =
                        (int) (
                            $c[
                            'quantity'
                            ]
                            ?? 0
                        );

                    if (
                        $packs < 1
                    ) {
                        throw new \Exception(
                            "Cantidad de paquetes inválida para {$stockItem->display_name}."
                        );
                    }

                    $presentation =
                        MaterialPresentation::query()
                            ->where(
                                'id',
                                $presentationId
                            )
                            ->where(
                                'material_id',
                                $materialId
                            )
                            ->where(
                                'active',
                                1
                            )
                            ->first();

                    if (!$presentation) {
                        throw new \Exception(
                            "La presentación seleccionada no es válida para {$stockItem->display_name}."
                        );
                    }

                    $unitsPerPack =
                        (int)
                        $presentation
                            ->quantity;

                    if (
                        $unitsPerPack < 1
                    ) {
                        throw new \Exception(
                            "La presentación tiene una cantidad inválida para {$stockItem->display_name}."
                        );
                    }

                    $requestedUnits =
                        (float) (
                            $packs *
                            $unitsPerPack
                        );

                } else {

                    $requestedUnits =
                        (float) (
                            $c[
                            'units_equivalent'
                            ]
                            ??
                            $c[
                            'quantity'
                            ]
                            ??
                            0
                        );

                    if (
                        $requestedUnits <= 0
                    ) {
                        throw new \Exception(
                            "Cantidad inválida para {$stockItem->display_name}."
                        );
                    }
                }

                /*
                 * =====================================================
                 * PRECIO
                 * =====================================================
                 *
                 * Línea histórica:
                 * conserva precio histórico.
                 *
                 * Línea nueva:
                 * obtiene precio vigente.
                 */

                if ($existingSnapshot) {

                    $priceReal =
                        (float)
                        $existingSnapshot[
                        'price'
                        ];

                } else {

                    if (
                    !empty(
                    $presentationId
                    )
                    ) {

                        /*
                         * Precio final de la presentación.
                         */
                        $priceReal =
                            (float)
                            $presentation
                                ->price;

                    } else {

                        $priceData =
                            $priceResolver
                                ->resolveWithSource(
                                    $stockItem,
                                    $companyId
                                );

                        if (
                            $priceData[
                            'price'
                            ] === null
                        ) {
                            throw new \RuntimeException(
                                "El producto {$stockItem->display_name} no tiene un precio configurado en la lista predeterminada."
                            );
                        }

                        /*
                         * Precio final vigente.
                         */
                        $priceReal =
                            (float)
                            $priceData[
                            'price'
                            ];
                    }
                }

                if (
                    $priceReal < 0
                ) {
                    throw new \RuntimeException(
                        "El precio de {$stockItem->display_name} es inválido."
                    );
                }

                /*
                 * =====================================================
                 * CÁLCULOS MONETARIOS
                 * =====================================================
                 *
                 * priceReal representa precio final.
                 *
                 * En gravados:
                 * valorUnitarioReal extrae la base.
                 *
                 * En exonerados / inafectos:
                 * taxRate = 0, factor = 1.
                 */

                $taxFactor =
                    1 + (
                        $taxRate /
                        100
                    );

                if (
                    $taxFactor <= 0
                ) {
                    throw new \RuntimeException(
                        "El factor tributario de {$stockItem->display_name} es inválido."
                    );
                }

                $valorUnitarioReal =
                    round(
                        $priceReal /
                        $taxFactor,
                        10
                    );

                /*
                 * quantity del UI:
                 *
                 * presentación → packs
                 * unidad       → unidades
                 */
                $frontQty =
                    (float) (
                        $c[
                        'quantity'
                        ]
                        ?? 0
                    );

                if (
                    $frontQty <= 0
                ) {
                    throw new \RuntimeException(
                        "Cantidad inválida para {$stockItem->display_name}."
                    );
                }

                /*
                 * Importe comercial final de la línea.
                 */
                $importeReal =
                    round(
                        $frontQty *
                        $priceReal,
                        10
                    );

                /*
                 * =====================================================
                 * VALIDAR ITEMS FÍSICOS
                 * =====================================================
                 */

                $selectedItemIds = [];

                if ($isItemeable) {

                    $rawSelectedItemIds =
                        $c[
                        'selected_item_ids'
                        ]
                        ?? [];

                    if (
                    is_string(
                        $rawSelectedItemIds
                    )
                    ) {

                        $decodedIds =
                            json_decode(
                                $rawSelectedItemIds,
                                true
                            );

                        $rawSelectedItemIds =
                            is_array(
                                $decodedIds
                            )
                                ? $decodedIds
                                : [];
                    }

                    if (
                    !is_array(
                        $rawSelectedItemIds
                    )
                    ) {
                        $rawSelectedItemIds = [];
                    }

                    $selectedItemIds =
                        collect(
                            $rawSelectedItemIds
                        )
                            ->map(
                                function ($itemId) {
                                    return (int) $itemId;
                                }
                            )
                            ->filter(
                                function ($itemId) {
                                    return $itemId > 0;
                                }
                            )
                            ->values()
                            ->toArray();

                    if (
                    empty(
                    $selectedItemIds
                    )
                    ) {
                        throw new \Exception(
                            "Debe seleccionar los ítems físicos para {$material->full_name}."
                        );
                    }

                    if (
                        count(
                            $selectedItemIds
                        )
                        !==
                        count(
                            array_unique(
                                $selectedItemIds
                            )
                        )
                    ) {
                        throw new \Exception(
                            "Se detectaron ítems repetidos para {$material->full_name}."
                        );
                    }

                    if (
                        (int) $requestedUnits
                        !==
                        count(
                            $selectedItemIds
                        )
                    ) {
                        throw new \Exception(
                            "La cantidad de ítems seleccionados no coincide con la cantidad requerida para {$material->full_name}. " .
                            "Requerido: {$requestedUnits}. " .
                            "Seleccionados: " .
                            count(
                                $selectedItemIds
                            ) .
                            '.'
                        );
                    }
                }

                /*
                 * =====================================================
                 * VALIDAR STOCK
                 * =====================================================
                 */

                $available =
                    $reservationService
                        ->getAvailableStockByStockItem(
                            $stockItemId
                        );

                if (
                    $requestedUnits >
                    $available
                ) {
                    throw new \Exception(
                        "Stock insuficiente para {$material->full_name}. " .
                        "Requerido: {$requestedUnits}. " .
                        "Disponible: {$available}."
                    );
                }

                /*
                 * =====================================================
                 * PROMOCIÓN LEGACY
                 * =====================================================
                 *
                 * Línea histórica:
                 * conserva metadata histórica.
                 *
                 * Línea nueva:
                 * utiliza metadata del flujo actual.
                 *
                 * IMPORTANTE:
                 *
                 * promotionDiscount NO participa en
                 * QuoteTaxCalculatorService.
                 *
                 * El descuento funcional de la Quote
                 * es exclusivamente el descuento global.
                 */

                if ($existingSnapshot) {

                    $promotionDiscount =
                        (float) (
                            $existingSnapshot[
                            'discount'
                            ]
                            ?? 0
                        );

                    $typePromo =
                        $existingSnapshot[
                        'type_promo'
                        ]
                        ?? null;

                } else {

                    $promotionDiscount =
                        (float) (
                            $c[
                            'discount'
                            ]
                            ?? 0
                        );

                    $typePromo =
                        $c[
                        'type_promo'
                        ]
                        ?? null;
                }

                if (
                    $promotionDiscount < 0
                ) {
                    throw new \RuntimeException(
                        "El descuento de {$stockItem->display_name} es inválido."
                    );
                }

                if (
                    $promotionDiscount >
                    $importeReal
                ) {
                    throw new \RuntimeException(
                        "El descuento de {$stockItem->display_name} no puede superar el importe de la línea."
                    );
                }

                /*
                 * =====================================================
                 * CREAR EQUIPMENT CONSUMABLE
                 * =====================================================
                 */

                $equipmentConsumable =
                    EquipmentConsumable::create([
                        'equipment_id' =>
                            $equipmentQuote->id,

                        'material_id' =>
                            $materialId,

                        'stock_item_id' =>
                            $stockItemId,

                        /*
                         * Snapshot tributario.
                         */
                        'type_tax_id' =>
                            $typeTaxId,

                        'tax_rate' =>
                            $taxRate,

                        'material_presentation_id' =>
                            $presentationId,

                        'packs' =>
                            $packs,

                        'units_per_pack' =>
                            $unitsPerPack,

                        /*
                         * Cantidad física real.
                         */
                        'quantity' =>
                            (float)
                            $requestedUnits,

                        /*
                         * Precio final.
                         */
                        'price' =>
                            $priceReal,

                        /*
                         * Valor sin impuesto cuando corresponda.
                         */
                        'valor_unitario' =>
                            $valorUnitarioReal,

                        /*
                         * Importe final completo de la línea.
                         */
                        'total' =>
                            $importeReal,

                        /*
                         * Metadata legacy.
                         */
                        'discount' =>
                            $promotionDiscount,

                        'type_promo' =>
                            $typePromo,

                        'availability' =>
                            'Completo',

                        'state' =>
                            'En compra',
                    ]);

                $consumableSync[] = [
                    'id' =>
                        (int) $equipmentConsumable->id,

                    'stock_item_id' =>
                        (int) $equipmentConsumable->stock_item_id,

                    /*
                     * Sirve únicamente para debug/trazabilidad.
                     */
                    'previous_id' =>
                        $equipmentConsumableId > 0
                            ? $equipmentConsumableId
                            : null,
                ];

                /*
                 * =====================================================
                 * LÍNEA TRIBUTARIA
                 * =====================================================
                 *
                 * IMPORTANTE:
                 *
                 * Enviamos el importe COMPLETO.
                 *
                 * NO hacemos:
                 *
                 * importeReal - promotionDiscount
                 *
                 * El único descuento tributario funcional
                 * es el descuento global.
                 */

                $productTaxLines[] = [
                    'type_tax_id' =>
                        (int)
                        $equipmentConsumable
                            ->type_tax_id,

                    'tax_rate' =>
                        (float)
                        $equipmentConsumable
                            ->tax_rate,

                    'total' =>
                        round(
                            (float)
                            $importeReal,
                            10
                        ),
                ];

                $totalConsumable +=
                    (float)
                    $importeReal;

                /*
                 * =====================================================
                 * RESERVAR INVENTARIO
                 * =====================================================
                 */

                if ($isItemeable) {

                    $reservationService
                        ->reserveItemeableForQuoteDetail(
                            (int)
                            $quote->id,

                            (int)
                            $equipmentConsumable->id,

                            (int)
                            $stockItemId,

                            $selectedItemIds
                        );

                } else {

                    $reservationService
                        ->reserveForQuoteDetail(
                            (int)
                            $quote->id,

                            (int)
                            $equipmentConsumable->id,

                            (int)
                            $stockItemId,

                            (float)
                            $requestedUnits
                        );
                }
            }

            /*
             * =========================================================
             * 10) RECREAR SERVICIOS ADICIONALES
             * =========================================================
             */

            $defaultTax =
                $typeTaxResolver
                    ->resolveWithSource();

            $workforces =
                $equip[
                'workforces'
                ]
                ?? [];

            foreach (
                $workforces
                as $workforce
            ) {

                /*
                 * =====================================================
                 * SNAPSHOT DEL SERVICIO
                 * =====================================================
                 *
                 * Servicio existente:
                 * conserva TypeTax histórico.
                 *
                 * Servicio nuevo:
                 * usa TypeTax default vigente.
                 */

                $workforceId =
                    (int) (
                        $workforce[
                        'id'
                        ]
                        ?? 0
                    );

                $workforceSnapshot =
                    $workforceId > 0
                        ? (
                        $workforceSnapshots[
                        $workforceId
                        ]
                        ?? null
                    )
                        : null;

                if (
                    $workforceId > 0 &&
                    !$workforceSnapshot
                ) {
                    throw new \RuntimeException(
                        'No se pudo identificar uno de los servicios originales de la cotización.'
                    );
                }

                if (
                    $workforceSnapshot &&
                    $workforceSnapshot[
                    'type_tax_id'
                    ] &&
                    $workforceSnapshot[
                    'tax_rate'
                    ] !== null
                ) {

                    $workforceTypeTaxId =
                        (int)
                        $workforceSnapshot[
                        'type_tax_id'
                        ];

                    $workforceTaxRate =
                        (float)
                        $workforceSnapshot[
                        'tax_rate'
                        ];

                } else {

                    $workforceTypeTaxId =
                        (int)
                        $defaultTax[
                        'type_tax_id'
                        ];

                    $workforceTaxRate =
                        (float)
                        $defaultTax[
                        'tax_rate'
                        ];
                }

                /*
                 * =====================================================
                 * DATOS DEL SERVICIO
                 * =====================================================
                 */

                $billable =
                    isset(
                        $workforce[
                        'billable'
                        ]
                    )
                        ? (int)
                    $workforce[
                    'billable'
                    ]
                        : 1;

                if (
                !in_array(
                    $billable,
                    [0, 1],
                    true
                )
                ) {
                    throw new \RuntimeException(
                        'Uno de los servicios contiene un estado de facturación inválido.'
                    );
                }

                $workforceQuantity =
                    (float) (
                        $workforce[
                        'quantity'
                        ]
                        ?? 0
                    );

                $workforcePrice =
                    (float) (
                        $workforce[
                        'price'
                        ]
                        ?? 0
                    );

                if (
                    $workforceQuantity < 0 ||
                    $workforcePrice < 0
                ) {
                    throw new \RuntimeException(
                        'Uno de los servicios contiene valores inválidos.'
                    );
                }

                /*
                 * Recalcular importe.
                 */
                $workforceTotal =
                    round(
                        $workforceQuantity *
                        $workforcePrice,
                        10
                    );

                $equipmentWorkforce =
                    EquipmentWorkforce::create([
                        'equipment_id' =>
                            $equipmentQuote->id,

                        'description' =>
                            $workforce[
                            'description'
                            ]
                            ?? '',

                        'price' =>
                            $workforcePrice,

                        'quantity' =>
                            $workforceQuantity,

                        'total' =>
                            $workforceTotal,

                        'unit' =>
                            $workforce[
                            'unit'
                            ]
                            ?? '',

                        'billable' =>
                            $billable,

                        'type_tax_id' =>
                            $workforceTypeTaxId,

                        'tax_rate' =>
                            $workforceTaxRate,
                    ]);

                $workforceSync[] = [
                    'id' =>
                        (int) $equipmentWorkforce->id,

                    'previous_id' =>
                        $workforceId > 0
                            ? $workforceId
                            : null,
                ];

                /*
                 * Solo servicios facturables afectan Quote.
                 */
                if (
                    $billable === 1
                ) {

                    $serviceTaxLines[] = [
                        'type_tax_id' =>
                            (int)
                            $equipmentWorkforce
                                ->type_tax_id,

                        'tax_rate' =>
                            (float)
                            $equipmentWorkforce
                                ->tax_rate,

                        'total' =>
                            round(
                                (float)
                                $equipmentWorkforce
                                    ->total,
                                10
                            ),
                    ];
                }

                /*
                 * Equipment.total conserva la semántica legacy
                 * y suma todos los servicios.
                 */
                $totalWorkforces +=
                    (float)
                    $equipmentWorkforce
                        ->total;
            }

            /*
             * =========================================================
             * TOTAL DEL EQUIPMENT
             * =========================================================
             *
             * quantity siempre es 1.
             */

            $equipmentQuote->total =
                round(
                    (
                        $totalConsumable +
                        $totalWorkforces
                    )
                    *
                    (float)
                    $equipmentQuote
                        ->quantity,
                    10
                );

            $equipmentQuote->save();

            /*
             * =========================================================
             * TOTALES TRIBUTARIOS
             * =========================================================
             *
             * QuoteTaxCalculatorService es la autoridad.
             *
             * with_igv:
             * descuento sobre precio final.
             *
             * without_igv:
             * descuento sobre base gravada.
             */

            $taxTotals =
                $taxCalculator
                    ->calculate(
                        $productTaxLines,
                        $serviceTaxLines,
                        [
                            'type' =>
                                $quote
                                    ->discount_type
                                    ?: 'amount',

                            'mode' =>
                                $quote
                                    ->discount_input_mode
                                    ?: 'without_igv',

                            'value' =>
                                (float) (
                                $quote
                                    ->discount_input_value
                                    ?: 0
                                ),
                        ]
                    );

            /*
             * Quote.descuento conserva la semántica visual:
             *
             * with_igv:
             * equivalente del descuento en base.
             *
             * without_igv:
             * descuento aplicado a la base.
             */

            $quote->descuento =
                $taxTotals[
                'discount_total'
                ];

            $quote->gravada =
                $taxTotals[
                'gravada'
                ];

            $quote->exonerada =
                $taxTotals[
                'exonerada'
                ];

            $quote->inafecta =
                $taxTotals[
                'inafecta'
                ];

            $quote->igv_total =
                $taxTotals[
                'igv_total'
                ];

            $quote->total_importe =
                $taxTotals[
                'total_importe'
                ];

            $quote->save();

            /*
             * =========================================================
             * 11) AUDITORÍA
             * =========================================================
             */

            Audit::create([
                'user_id' =>
                    Auth::id(),

                'action' =>
                    'Modificar equipo de cotizacion (EDIT)',

                'time' =>
                    microtime(true)
                    -
                    $begin,
            ]);

            DB::commit();

            $equipmentSent =
                Equipment::with([
                    'consumables',
                    'workforces',
                ])
                    ->find(
                        $equipmentQuote->id
                    );

            return response()->json([
                'message' =>
                    'Cotización actualizada correctamente.',

                'equipment' =>
                    $equipmentSent,

                'quote' =>
                    $quote,

                /*
                 * IDs nuevos generados al reconstruir
                 * los detalles.
                 */
                'sync' => [
                    'consumables' =>
                        $consumableSync,

                    'workforces' =>
                        $workforceSync,
                ],
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    $e->getMessage(),

                'file' =>
                    $e->getFile(),

                'line' =>
                    $e->getLine(),
            ], 422);
        }
    }

    public function showRegistrarComprobante($typeComprobante)
    {
        abort_unless(
            in_array($typeComprobante, ['Boleta', 'Factura', 'Ticket'], true),
            404
        );

        $tenantId = TenantContext::tenantId();
        $companyId = TenantContext::companyId();
        $branchId = TenantContext::branchId();

        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        $settings = app(SettingService::class);

        $currency = $settings->get('finance.base_currency');

        $dataIgv = PorcentageQuote::where('name', 'igv')->firstOrFail();
        $igv = $dataIgv->value;

        $cashBoxes = CashBox::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            })
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(function ($box) {
                return [
                    'id' => (int) $box->id,
                    'name' => (string) $box->name,
                    'type' => (string) $box->type,
                    'uses_subtypes' => (bool) $box->uses_subtypes,
                ];
            })
            ->values();

        $cashBoxIds = $cashBoxes->pluck('id')->all();

        $subtypes = CashBoxSubtype::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->where(function ($query) use ($cashBoxIds) {
                $query->whereNull('cash_box_id')
                    ->orWhereIn('cash_box_id', $cashBoxIds);
            })
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(function ($subtype) {
                return [
                    'id' => (int) $subtype->id,
                    'name' => (string) $subtype->name,
                    'cash_box_id' => $subtype->cash_box_id === null
                        ? null
                        : (int) $subtype->cash_box_id,
                    'is_deferred' => (bool) $subtype->is_deferred,
                    'requires_commission' => (bool) $subtype->requires_commission,
                ];
            })
            ->values();

        $pagos_parciales = (bool) $settings->get(
            'sales.allow_partial_payments'
        ) ? 's' : 'n';

        return view('quoteSale.registrarComprobante', compact(
            'typeComprobante',
            'permissions',
            'currency',
            'igv',
            'cashBoxes',
            'subtypes',
            'pagos_parciales'
        ));
    }

    public function buscarO(Request $request)
    {
        $query = Quote::query();

        if ($request->has('code') && !empty($request->code)) {
            $query->where('code', 'LIKE', '%' . $request->code . '%');
        }

        if ($request->has('name') && !empty($request->name)) {
            $query->where('description_quote', 'LIKE', '%' . $request->name . '%');
        }

        $quotes = $query->where('state', 'confirmed')
            ->whereDoesntHave('sales', function ($q) {
                $q->where('state_annulled', 0);
            })
            ->limit(20)
            ->get()
            ->map(function ($quote) {
                $quote->customer_name = $quote->customer_id
                    ? optional($quote->customer)->business_name
                    : "";

                $quote->date_quote_format = $quote->date_quote
                    ? $quote->date_quote->format('d/m/Y')
                    : "";

                return $quote;
            });

        return response()->json($quotes);
    }

    public function buscarChat(Request $request)
    {
        if (!$request->filled('code') && !$request->filled('name')) {
            return response()->json([]);
        }

        $query = Quote::with(['customer:id,business_name'])
            ->where('state', 'confirmed')
            ->whereDoesntHave('sales', function ($q) {
                $q->where('state_annulled', 0);
            });

        if ($request->filled('code')) {
            $query->where('code', 'LIKE', '%' . trim($request->code) . '%');
        }

        if ($request->filled('name')) {
            $query->where('description_quote', 'LIKE', '%' . trim($request->name) . '%');
        }

        $quotes = $query->orderBy('id', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($quote) {
                $quote->customer_name = optional($quote->customer)->business_name ?? '';
                $quote->date_quote_format = $quote->date_quote
                    ? $quote->date_quote->format('d/m/Y')
                    : '';

                return $quote;
            });

        return response()->json($quotes);
    }

    public function buscar(Request $request)
    {
        $branchScope = app(QuoteBranchReadScope::class);
        if (!$request->filled('code') && !$request->filled('name')) {
            return response()->json([]);
        }

        $query = $branchScope->local(Quote::query())->with(['customer:id,business_name'])
            ->where('company_id', TenantContext::companyId())
            ->select('id', 'code', 'description_quote', 'customer_id', 'date_quote', 'state')
            ->where('state', 'confirmed')
            ->whereDoesntHave('sales', function ($q) {
                $q->where('state_annulled', 0);
            });

        if ($request->filled('code')) {
            $query->where('code', 'LIKE', '%' . trim($request->code) . '%');
        }

        if ($request->filled('name')) {
            $query->where('description_quote', 'LIKE', '%' . trim($request->name) . '%');
        }

        $quotes = $query->orderBy('id', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($quote) {
                return [
                    'id' => $quote->id,
                    'code' => $quote->code,
                    'description_quote' => $quote->description_quote,
                    'customer_name' => optional($quote->customer)->business_name ?? '',
                    'date_quote_format' => $quote->date_quote
                        ? $quote->date_quote->format('d/m/Y')
                        : '',
                ];
            })
            ->values();

        return response()->json($quotes);
    }

    public function getDataIndividualO($id)
    {
        $quote = Quote::with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with([
                    'consumables.material',
                    'workforces' // ✅ nuevo
                ]);
            }])->findOrFail($id);

        // Si quieres formatear la fecha:
        $quote->date_quote_format = $quote->date_quote ? $quote->date_quote->format('d/m/Y') : "";
        $quote->date_validate_format = $quote->date_validate ? $quote->date_validate->format('d/m/Y') : "";
        $quote->deadline_format = $quote->deadline ? $quote->deadline->description : "";
        $quote->customer_format = $quote->customer_id ? $quote->customer->business_name : "";
        $quote->contact_format = $quote->contact_id ? $quote->contact->name : "";

        return response()->json($quote);
    }

    public function getDataIndividual($id)
    {
        $quote = Quote::with([
            'customer:id,business_name,RUC,address',
            'contact:id,name',
            'deadline:id,description',
            'equipments' => function ($query) {
                $query->select('id', 'quote_id', 'detail')
                    ->with([
                        'consumables' => function ($q) {
                            $q->select(
                                'id',
                                'equipment_id',
                                'material_id',
                                'stock_item_id',
                                'material_presentation_id',
                                'discount',
                                'type_promo',
                                'units_per_pack',
                                'quantity',
                                'packs',
                                'valor_unitario',
                                'price',
                                'total'
                            )->with([
                                'material' => function ($q) {
                                    $q->select('id', 'full_name', 'unit_measure_id')
                                        ->with('unitMeasure:id,name');
                                },
                                'stockItem:id,display_name,sku',
                                'quoteStockLots:id,quote_detail_id,item_ids'
                            ]);
                        },
                        'workforces' => function ($q) {
                            $q->select(
                                'id',
                                'equipment_id',
                                'description',
                                'unit',
                                'quantity',
                                'price',
                                'total',
                                'billable'
                            );
                        }
                    ]);
            }
        ])->where('company_id', TenantContext::companyId())->findOrFail($id);

        $response = [
            'id' => $quote->id,
            'code' => $quote->code,
            'description_quote' => $quote->description_quote,
            'date_quote_format' => $quote->date_quote ? $quote->date_quote->format('d/m/Y') : '',
            'date_validate_format' => $quote->date_validate ? $quote->date_validate->format('d/m/Y') : '',
            'deadline_format' => optional($quote->deadline)->description ?? '',
            'customer_format' => optional($quote->customer)->business_name ?? '',
            'contact_format' => optional($quote->contact)->name ?? '',
            'delivery_time' => $quote->delivery_time,
            'observations' => $quote->observations,
            'descuento' => $quote->descuento ?? 0,
            'gravada' => $quote->gravada ?? 0,
            'igv_total' => $quote->igv_total ?? 0,
            'total_importe' => $quote->total_importe ?? 0,
            'discount_type' => $quote->discount_type,
            'discount_input_mode' => $quote->discount_input_mode,
            'discount_input_value' => $quote->discount_input_value,

            'customer' => [
                'RUC' => optional($quote->customer)->RUC ?? '',
                'address' => optional($quote->customer)->address ?? '',
            ],

            'equipments' => $quote->equipments->map(function ($equipment) {
                return [
                    'id' => $equipment->id,
                    'detail' => $equipment->detail,
                    'consumables' => $equipment->consumables->map(function ($consumable) {
                        $itemIds = [];

                        foreach ($consumable->quoteStockLots ?? [] as $quoteStockLot) {
                            $rawItemIds = $quoteStockLot->item_ids ?? [];

                            if (is_string($rawItemIds)) {
                                $decodedItemIds = json_decode($rawItemIds, true);
                                $rawItemIds = is_array($decodedItemIds) ? $decodedItemIds : [];
                            }

                            if (!is_array($rawItemIds)) {
                                $rawItemIds = [];
                            }

                            foreach ($rawItemIds as $itemId) {
                                $itemId = (int) $itemId;

                                if ($itemId > 0) {
                                    $itemIds[] = $itemId;
                                }
                            }
                        }

                        $itemIds = array_values(array_unique($itemIds));

                        $itemCodes = [];

                        if (!empty($itemIds)) {
                            $itemCodes = Item::whereIn('id', $itemIds)
                                ->whereNotNull('code')
                                ->where('code', '<>', '')
                                ->orderBy('code', 'asc')
                                ->pluck('code')
                                ->values()
                                ->toArray();
                        }
                        return [
                            'id' => $consumable->id,
                            'discount' => $consumable->discount,
                            'type_promo' => $consumable->type_promo,
                            'material_presentation_id' => $consumable->material_presentation_id,
                            'units_per_pack' => $consumable->units_per_pack,
                            'quantity' => $consumable->quantity,
                            'packs' => $consumable->packs,
                            'valor_unitario' => $consumable->valor_unitario,
                            'price' => $consumable->price,
                            'total' => $consumable->total,
                            'material' => [
                                'full_description' => optional($consumable->stockItem)->display_name
                                    ?? optional($consumable->material)->full_name
                                    ?? '',
                                'name_unit' => optional(optional($consumable->material)->unitMeasure)->name ?? '',
                                'sku' => optional($consumable->stockItem)->sku ?? '',
                            ],
                            'item_codes' => $itemCodes,
                        ];
                    })->values(),

                    'workforces' => $equipment->workforces->map(function ($wf) {
                        return [
                            'id' => $wf->id,
                            'description' => $wf->description,
                            'unit' => $wf->unit,
                            'quantity' => $wf->quantity,
                            'price' => $wf->price,
                            'total' => $wf->total,
                            'billable' => $wf->billable,
                        ];
                    })->values(),
                ];
            })->values(),
        ];

        return response()->json($response);
    }

    public function storeFromQuoteO(Request $request)
    {
        $begin = microtime(true);
        $worker = Worker::where('user_id', Auth::user()->id)->first();
        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        DB::beginTransaction();
        try {
            $quote = Quote::with(['equipments.consumables.material'])->findOrFail($request->quote_id);

            // Validaciones mínimas
            if (!$quote || $quote->state !== 'confirmed') {
                throw new \Exception("La cotización no es válida o no está confirmada.");
            }

            // Construcción de la venta
            $sale = Sale::create([
                // Si viene fechaDocumento la usamos, si no usamos la actual
                'date_sale' => $request->filled('fechaDocumento')
                    ? Carbon::createFromFormat('Y-m-d', $request->fechaDocumento)
                    : Carbon::now(),

                'serie' => $this->generateRandomString(), // método tuyo que genera serie
                'worker_id' => $worker->id,
                'caja' => $worker->id,
                'currency' => ($currency == 'usd') ? 'USD':'PEN',

                // Totales (tomados de la cotización)
                'op_exonerada' => 0,
                'op_inafecta' => 0,
                'op_gravada' => $quote->gravada,
                'igv' => $quote->igv_total,
                'total_descuentos' => $quote->descuento,
                'importe_total' => $quote->total_importe,
                'vuelto' => 0,

                'quote_id' => $quote->id,

                // Aquí usamos lo que envías
                'tipo_pago_id' => $request->tipoPago ?? 4,  // si no llega, por defecto efectivo

                // Datos de cliente
                'type_document' => $request->type_document,  // "01" factura, "03" boleta/ticket
                'numero_documento_cliente' => $request->numero_documento_cliente,
                'tipo_documento_cliente' => $request->tipo_documento_cliente, // "1" DNI, "6" RUC
                'nombre_cliente' => $request->nombre_cliente,
                'direccion_cliente' => $request->direccion_cliente,
                'email_cliente' => $request->email_cliente,

                // Campos SUNAT se llenarán luego
                'serie_sunat' => null,
                'numero' => null,
                'sunat_ticket' => null,
                'sunat_status' => null,
                'sunat_message' => null,
                'xml_path' => null,
                'cdr_path' => null,
                'fecha_emision' => null,
            ]);

            // ✅ 1) Crear Output (salida) asociada a la ejecución/orden de la cotización
            $output = Output::create([
                'execution_order'   => ($quote->order_execution == "") ? 'VENTA POR COTIZACION':$quote->order_execution, // o $quote->execution_order si tu campo fuera otro
                'request_date'      => $sale->date_sale,        // o Carbon::now()
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),              // si luego lo asignas a almacenero, lo puedes cambiar
                'state'             => 'confirmed',
                'indicator'         => 'or',
            ]);

            // Ahora los detalles
            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {
                    SaleDetail::create([
                        'sale_id' => $sale->id,
                        'material_id' => $consumable->material_id,
                        'price' => $consumable->price,
                        'quantity' => $consumable->quantity,
                        'percentage_tax' => 18, // Asumiendo IGV fijo, ajusta si es dinámico
                        'total' => $consumable->total,
                        'discount' => $consumable->discount,
                    ]);

                    // Descontar stock del material directamente
                    $material = $consumable->material;
                    if ($material) {
                        $material->stock_current = max(0, $material->stock_current - $consumable->quantity);
                        $material->stock_current = max(0, $material->stock_reserved - $consumable->quantity);
                        $material->save();
                    }

                    /*$this->manageNotifications($material);*/

                    $materialId = $consumable->material_id;
                    $qtyNeeded  = (float) $consumable->quantity;

                    // ⚠️ por ahora solo soportamos cantidades enteras (material normal sin retazos)
                    if (floor($qtyNeeded) != $qtyNeeded) {
                        throw new \Exception("La cotización requiere cantidad decimal para el material {$materialId}. Aún no está implementado para retazos.");
                    }

                    $qtyNeeded = (int) $qtyNeeded;

                    if ( $material->tipo_venta_id == 3 )
                    {
                        // ✅ 2.1) Obtener items disponibles con lock (evita carreras)
                        $items = Item::where('material_id', $materialId)
                            ->whereIn('state_item', ['entered', 'scrapped'])
                            // si usas usage para filtrar disponibles:
                            // ->where('usage', '<>', 'finished')
                            ->orderBy('id', 'asc') // FIFO simple
                            ->lockForUpdate()
                            ->take($qtyNeeded)
                            ->get();

                        // ✅ 2.2) Validar stock suficiente
                        if ($items->count() < $qtyNeeded) {
                            throw new \Exception("Stock insuficiente del material ID {$materialId}. Se requieren {$qtyNeeded} y solo hay {$items->count()} disponibles.");
                        }

                        // ✅ 2.3) Crear OutputDetails + marcar items como exited
                        foreach ($items as $item) {
                            OutputDetail::create([
                                'output_id'    => $output->id,
                                'item_id'      => $item->id,
                                'material_id'  => $materialId,
                                'quote_id'     => $quote->id,
                                'custom'       => 0,
                                'percentage'   => 1,           // material normal => 1
                                'price'        => $item->price,        // opcional: puedes guardar el costo o precio de salida si quieres
                                'length'       => $item->length,
                                'width'        => $item->width,
                                'equipment_id' => $equipment->id ?? null,
                                'activo'       => null,        // no usar para lógica de salida (según me dijiste)
                            ]);

                            $item->state_item = 'exited';
                            // opcional si manejas usage:
                            // $item->usage = 'finished';
                            $item->save();
                        }
                    }
                }
            }
            $paymentType = $request->tipoPago ?? 4;
            // Mapear tipo de pago a los nombres de las cajas
            $paymentTypeMap = [
                1 => 'yape',
                2 => 'plin',
                3 => 'bancario',
                4 => 'efectivo'
            ];

            // Obtener la caja del tipo de pago
            $cashRegister = CashRegister::where('type', $paymentTypeMap[$paymentType])
                ->where('user_id', Auth::user()->id)
                ->where('status', 1) // Caja abierta
                ->latest()
                ->first();

            if (!isset($cashRegister)) {
                return response()->json(['message' => 'No hay caja abierta para este tipo de pago.'], 422);
            }
            if ( $paymentType != 3 ) {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => (float)$request->get('total_importe')+(float)$request->get('total_vuelto'),
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'sale_id' => $sale->id
                ]);

                // Actualizar el saldo actual y el total de ventas en la caja
                $cashRegister->current_balance += (float)$request->get('total_importe')+(float)$request->get('total_vuelto');
                $cashRegister->total_sales += (float)$request->get('total_importe')+(float)$request->get('total_vuelto');
                $cashRegister->save();
            } else {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => (float)$request->get('total_importe')+(float)$request->get('total_vuelto'),
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'regularize' => 0,
                    'sale_id' => $sale->id
                ]);
            }


            // Registrar el vuelto como egreso si el tipo de pago es efectivo y hay vuelto

            $typeVuelto = $paymentTypeMap[$paymentType];
            $vuelto = 0;

            if ($paymentType == 4) {
                // Mapear el type_vuelto (la caja desde donde se dará el vuelto)
                $typeVueltoMap = [
                    'efectivo' => 'efectivo',
                    'yape' => 'yape',
                    'plin' => 'plin',
                    'bancario' => 'bancario'
                ];

                // Obtener la caja para el vuelto
                $vueltoCashRegister = CashRegister::where('type', $typeVueltoMap[$typeVuelto])
                    ->where('user_id', Auth::user()->id)
                    ->where('status', 1) // Caja abierta
                    ->latest()
                    ->first();

                if (!isset($vueltoCashRegister)) {
                    return response()->json(['message' => 'No hay caja abierta para dar el vuelto.'], 422);
                }

                // Crear el movimiento de egreso (vuelto)
                CashMovement::create([
                    'cash_register_id' => $vueltoCashRegister->id,
                    'type' => 'expense', // Tipo de movimiento: egreso
                    'amount' => $vuelto,
                    'description' => 'Vuelto entregado de la venta',
                    'sale_id' => $sale->id
                ]);

                // Actualizar el saldo de la caja del vuelto
                $vueltoCashRegister->current_balance -= $vuelto;
                $vueltoCashRegister->total_expenses += $vuelto;
                $vueltoCashRegister->save();
            }

            // Crear notificación
            $notification = Notification::create([
                'content' => 'Venta creada desde cotización por '.Auth::user()->name,
                'reason_for_creation' => 'create_sale_from_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('puntoVenta.index')
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $user) {
                if ($user->id != Auth::user()->id) {
                    foreach ($user->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar venta desde cotización',
                'time' => $end
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Venta creada con éxito desde cotización',
                'sale_id' => $sale->id,
                'url_print' => route('puntoVenta.print', $sale->id)
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function storeFromQuoteO2(Request $request)
    {
        $begin = microtime(true);

        $worker = Worker::where('user_id', Auth::user()->id)->first();
        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        DB::beginTransaction();
        try {
            $quote = Quote::with(['equipments.consumables.material'])->findOrFail($request->quote_id);

            if (!$quote || $quote->state !== 'confirmed') {
                throw new \Exception("La cotización no es válida o no está confirmada.");
            }

            // 1) Crear venta
            $sale = Sale::create([
                'date_sale' => $request->filled('fechaDocumento')
                    ? Carbon::createFromFormat('Y-m-d', $request->fechaDocumento)
                    : Carbon::now(),

                'serie' => $this->generateRandomString(),
                'worker_id' => $worker->id,
                'caja' => $worker->id,
                'currency' => ($currency == 'usd') ? 'USD' : 'PEN',

                'op_exonerada' => 0,
                'op_inafecta' => 0,
                'op_gravada' => $quote->gravada,
                'igv' => $quote->igv_total,
                'total_descuentos' => $quote->descuento,
                'importe_total' => $quote->total_importe,
                'vuelto' => 0,

                'quote_id' => $quote->id,

                'tipo_pago_id' => $request->tipoPago ?? 4,

                'type_document' => $request->type_document,
                'numero_documento_cliente' => $request->numero_documento_cliente,
                'tipo_documento_cliente' => $request->tipo_documento_cliente,
                'nombre_cliente' => $request->nombre_cliente,
                'direccion_cliente' => $request->direccion_cliente,
                'email_cliente' => $request->email_cliente,

                'serie_sunat' => null,
                'numero' => null,
                'sunat_ticket' => null,
                'sunat_status' => null,
                'sunat_message' => null,
                'xml_path' => null,
                'cdr_path' => null,
                'pdf_path' => null,
                'fecha_emision' => null,
            ]);

            $saleDate = $sale->date_sale instanceof Carbon
                ? $sale->date_sale
                : Carbon::parse($sale->date_sale);

            // recolectar materialIds
            $materialIds = [];
            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {
                    $materialIds[] = (int)$consumable->material_id;
                }
            }
            $materialIds = array_values(array_unique($materialIds));

            // costo promedio vigente por material hasta la fecha de emisión
            $avgCosts = $this->inventoryCostService->getAverageCostsUpToDate($materialIds, $saleDate);

            // 2) Crear Output
            $output = Output::create([
                'execution_order'   => ($quote->order_execution == "") ? 'VENTA POR COTIZACION' : $quote->order_execution,
                'request_date'      => $sale->date_sale,
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),
                'state'             => 'confirmed',
                'indicator'         => 'or',
            ]);

            // 3) Detalles + stock + output details
            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {

                    $materialId = (int)$consumable->material_id;
                    $qty = (float)$consumable->quantity; // OJO: confirmaste que quantity siempre es real a descontar

                    $unitCost = (float)($avgCosts[$materialId] ?? 0.0);
                    $totalCost = $qty * $unitCost;

                    $saleDetail = SaleDetail::create([
                        'sale_id'                   => $sale->id,
                        'material_id'               => $consumable->material_id,
                        'material_presentation_id'  => $consumable->material_presentation_id,
                        'valor_unitario'            => $consumable->valor_unitario,
                        'price'                     => $consumable->price,
                        'quantity'                  => $consumable->quantity,
                        'packs'                     => $consumable->packs,           // ✅ si hay presentación
                        'units_per_pack'            => $consumable->units_per_pack,    // ✅ si hay presentación
                        'percentage_tax'            => 18,
                        'total'                     => $consumable->total,
                        'discount'                  => $consumable->discount,

                        // ✅ snapshot de costos para utilidad histórica
                        'unit_cost'                 => $unitCost,
                        'total_cost'                => $totalCost,
                    ]);

                    $material = $consumable->material;

                    if ($material) {
                        $material->unit_price = $unitCost;
                        $material->stock_current  = max(0, (float)$material->stock_current  - (float)$consumable->quantity);
                        $material->stock_reserved = max(0, (float)$material->stock_reserved - (float)$consumable->quantity);
                        $material->save();
                    }

                    $materialId = $consumable->material_id;
                    $qtyNeeded  = (float) $consumable->quantity;

                    if (floor($qtyNeeded) != $qtyNeeded) {
                        throw new \Exception("La cotización requiere cantidad decimal para el material {$materialId}. Aún no está implementado para retazos.");
                    }

                    $qtyNeeded = (int) $qtyNeeded;

                    if ($material && (int)$material->tipo_venta_id === 3) {
                        $items = Item::where('material_id', $materialId)
                            ->whereIn('state_item', ['entered', 'scrapped'])
                            ->orderBy('id', 'asc')
                            ->lockForUpdate()
                            ->take($qtyNeeded)
                            ->get();

                        if ($items->count() < $qtyNeeded) {
                            throw new \Exception("Stock insuficiente del material ID {$materialId}. Se requieren {$qtyNeeded} y solo hay {$items->count()} disponibles.");
                        }

                        foreach ($items as $item) {
                            OutputDetail::create([
                                'output_id'      => $output->id,
                                'sale_detail_id' => $saleDetail->id, // ✅ enlace a la línea de venta
                                'item_id'        => $item->id,
                                'material_id'    => $materialId,
                                'quote_id'       => $quote->id,
                                'custom'         => 0,
                                'percentage'     => 1,
                                'price'          => $item->price,
                                'length'         => $item->length,
                                'width'          => $item->width,
                                'equipment_id'   => $equipment->id ?? null,
                                'activo'         => null,
                                // ✅ costo promedio aplicado (misma base)
                                'unit_cost'      => $unitCost,
                                'total_cost'     => $unitCost,
                            ]);

                            $item->state_item = 'exited';
                            $item->save();
                        }
                    }

                    if ($material && (int)$material->tipo_venta_id !== 3) {
                        // ✅ NO ITEMEABLE: 1 OutputDetail por línea, con item_id NULL
                        OutputDetail::create([
                            'output_id'      => $output->id,
                            'sale_detail_id' => $saleDetail->id,
                            'item_id'        => null,
                            'material_id'    => $materialId,
                            'quote_id'       => $quote->id,
                            'custom'         => 0,

                            // tu VIEW usa percentage como "cantidad real"
                            'percentage'     => $qty,

                            // opcional, si tu tabla los necesita
                            'price'          => $consumable->price,
                            'length'         => null,
                            'width'          => null,
                            'equipment_id'   => $equipment->id ?? null,
                            'activo'         => null,

                            // (Opcional pero recomendado para auditoría)
                            'unit_cost'      => $unitCost,
                            'total_cost'     => $totalCost,
                        ]);
                    }
                }
            }

            // 4) Caja + notificaciones + auditoría (tu misma lógica tal cual)
            // ... (aquí pega tu bloque tal como lo tienes)
            $paymentType = $request->tipoPago ?? 4;
            // Mapear tipo de pago a los nombres de las cajas
            $paymentTypeMap = [
                1 => 'yape',
                2 => 'plin',
                3 => 'bancario',
                4 => 'efectivo'
            ];

            // Obtener la caja del tipo de pago
            $cashRegister = CashRegister::where('type', $paymentTypeMap[$paymentType])
                ->where('user_id', Auth::user()->id)
                ->where('status', 1) // Caja abierta
                ->latest()
                ->first();

            if (!isset($cashRegister)) {
                return response()->json(['message' => 'No hay caja abierta para este tipo de pago.'], 422);
            }
            if ( $paymentType != 3 ) {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => $quote->total_importe,
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'sale_id' => $sale->id
                ]);

                // Actualizar el saldo actual y el total de ventas en la caja
                $cashRegister->current_balance += $quote->total_importe;
                $cashRegister->total_sales += $quote->total_importe;
                $cashRegister->save();
            } else {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => $quote->total_importe,
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'regularize' => 0,
                    'sale_id' => $sale->id
                ]);
            }


            // Registrar el vuelto como egreso si el tipo de pago es efectivo y hay vuelto

            $typeVuelto = $paymentTypeMap[$paymentType];
            $vuelto = 0;

            if ($paymentType == 4) {
                // Mapear el type_vuelto (la caja desde donde se dará el vuelto)
                $typeVueltoMap = [
                    'efectivo' => 'efectivo',
                    'yape' => 'yape',
                    'plin' => 'plin',
                    'bancario' => 'bancario'
                ];

                // Obtener la caja para el vuelto
                $vueltoCashRegister = CashRegister::where('type', $typeVueltoMap[$typeVuelto])
                    ->where('user_id', Auth::user()->id)
                    ->where('status', 1) // Caja abierta
                    ->latest()
                    ->first();

                if (!isset($vueltoCashRegister)) {
                    return response()->json(['message' => 'No hay caja abierta para dar el vuelto.'], 422);
                }

                // Crear el movimiento de egreso (vuelto)
                if ( $vuelto > 0)
                {
                    CashMovement::create([
                        'cash_register_id' => $vueltoCashRegister->id,
                        'type' => 'expense', // Tipo de movimiento: egreso
                        'amount' => $vuelto,
                        'description' => 'Vuelto entregado de la venta',
                        'sale_id' => $sale->id
                    ]);

                    // Actualizar el saldo de la caja del vuelto
                    $vueltoCashRegister->current_balance -= $vuelto;
                    $vueltoCashRegister->total_expenses += $vuelto;
                    $vueltoCashRegister->save();
                }

            }

            // Crear notificación
            $notification = Notification::create([
                'content' => 'Venta creada desde cotización por '.Auth::user()->name,
                'reason_for_creation' => 'create_sale_from_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('puntoVenta.index')
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $user) {
                if ($user->id != Auth::user()->id) {
                    foreach ($user->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action'  => 'Guardar venta desde cotización',
                'time'    => $end
            ]);

            DB::commit();

            // ✅ 5) Generar comprobante Nubefact (FUERA de la transacción)
            $nubefactResult = null;
            if (in_array($sale->type_document, ['01', '03'])) {
                try {
                    $sale->loadMissing(['details.material']);
                    $nubefactResult = $this->generarComprobanteNubefactParaVenta($sale);

                    // Guardar archivos y actualizar campos (reusamos lógica similar a tu NubefactController)
                    $filename = 'ORD' . $sale->id;
                    $pdfFilename = $filename . '.pdf';
                    $xmlFilename = $filename . '.xml';
                    $cdrFilename = $filename . '.zip';

                    foreach (['pdfs', 'xmls', 'cdrs'] as $folder) {
                        if (!file_exists(public_path("comprobantes/$folder"))) {
                            mkdir(public_path("comprobantes/$folder"), 0777, true);
                        }
                    }

                    if (!empty($nubefactResult['enlace_del_pdf'])) {
                        $pdfContent = Http::get($nubefactResult['enlace_del_pdf'])->body();
                        file_put_contents(public_path('comprobantes/pdfs/' . $pdfFilename), $pdfContent);
                    }
                    if (!empty($nubefactResult['enlace_del_xml'])) {
                        $xmlContent = Http::get($nubefactResult['enlace_del_xml'])->body();
                        file_put_contents(public_path('comprobantes/xmls/' . $xmlFilename), $xmlContent);
                    }
                    if (!empty($nubefactResult['enlace_del_cdr'])) {
                        $cdrContent = Http::get($nubefactResult['enlace_del_cdr'])->body();
                        file_put_contents(public_path('comprobantes/cdrs/' . $cdrFilename), $cdrContent);
                    }

                    $sale->update([
                        'serie_sunat' => $nubefactResult['serie'] ?? null,
                        'numero' => $nubefactResult['numero'] ?? null,
                        'sunat_ticket' => $nubefactResult['sunat_ticket'] ?? null,
                        'sunat_status' => $nubefactResult['sunat_description'] ?? 'Enviado',
                        'sunat_message' => $nubefactResult['sunat_note'] ?? '',
                        'xml_path' => file_exists(public_path('comprobantes/xmls/' . $xmlFilename)) ? $xmlFilename : null,
                        'cdr_path' => file_exists(public_path('comprobantes/cdrs/' . $cdrFilename)) ? $cdrFilename : null,
                        'pdf_path' => file_exists(public_path('comprobantes/pdfs/' . $pdfFilename)) ? $pdfFilename : null,
                        'fecha_emision' => now()->toDateString(),
                    ]);

                } catch (\Throwable $e) {
                    // Si Nubefact falla, NO tumbamos la venta: devolvemos warning
                    $sale->update([
                        'sunat_status' => 'Error',
                        'sunat_message' => $e->getMessage(),
                    ]);
                }
            }

            // Por defecto: ticket interno
            $urlPrint = route('puntoVenta.print', $sale->id);
            $printType = 'ticket';

            // Si es boleta/factura, preferimos PDF SUNAT (si existe)
            if (in_array($sale->type_document, ['01', '03'])) {

                // Caso A: tenemos link directo de nubefact en la respuesta
                if (!empty($nubefactResult['enlace_del_pdf'])) {
                    $urlPrint = $nubefactResult['enlace_del_pdf'];
                }

                // Caso B (recomendado): usar el PDF guardado localmente (si existe)
                if (!empty($sale->pdf_path)) {
                    // Prioridad 1: PDF guardado localmente
                    if (!empty($sale->pdf_path)) {
                        $localPath = public_path('comprobantes/pdfs/' . $sale->pdf_path);
                        if (file_exists($localPath)) {
                            $urlPrint  = asset('comprobantes/pdfs/' . $sale->pdf_path);
                            $printType = 'sunat_pdf';
                        }
                    }
                    // Prioridad 2: enlace directo de Nubefact
                    elseif (!empty($nubefactResult['enlace_del_pdf'])) {
                        $urlPrint  = $nubefactResult['enlace_del_pdf'];
                        $printType = 'sunat_pdf';
                    }
                }
            }
            return response()->json([
                'message' => 'Venta creada con éxito desde cotización' . ($nubefactResult ? ' y comprobante generado.' : ' (sin comprobante).'),
                'sale_id' => $sale->id,
                'nubefact' => $nubefactResult,
                'url_print' => $urlPrint,
                'print_type' => $printType,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function storeFromQuoteO3(Request $request)
    {
        $begin = microtime(true);

        $worker = Worker::where('user_id', Auth::id())->firstOrFail();

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency ? $dataCurrency->valueText : 'pen';

        // ===========================
        // ✅ Validación CashBox/Subtype
        // ===========================
        $cashBoxId        = $request->input('pv_cash_box_id');
        $cashBoxSubtypeId = $request->input('pv_cash_box_subtype_id');

        if (!$cashBoxId) {
            return response()->json(['message' => 'Seleccione una caja (CashBox).'], 422);
        }

        $cashBox = CashBox::find($cashBoxId);

        if (!$cashBox || !(bool)$cashBox->is_active) {
            return response()->json(['message' => 'La caja seleccionada no es válida o está inactiva.'], 422);
        }

        $needSubtype = ($cashBox->type === 'bank' && (bool)$cashBox->uses_subtypes);

        if ($needSubtype) {
            if (!$cashBoxSubtypeId) {
                return response()->json(['message' => 'Seleccione el canal/subtipo (Yape/Plin/POS/Transfer).'], 422);
            }

            // ✅ Subtypes globales (no dependen de cash_box_id)
            $validSubtype = CashBoxSubtype::whereNull('cash_box_id')
                ->where('is_active', 1)
                ->where('id', (int)$cashBoxSubtypeId)
                ->first();

            if (!$validSubtype) {
                return response()->json(['message' => 'El subtipo seleccionado no es válido o está inactivo.'], 422);
            }
        } else {
            $cashBoxSubtypeId = null;
        }

        DB::beginTransaction();
        try {
            $quote = Quote::with(['equipments.consumables.material'])->findOrFail($request->quote_id);

            if (!$quote || $quote->state !== 'confirmed') {
                throw new \Exception("La cotización no es válida o no está confirmada.");
            }

            // ===========================
            // 1) Crear venta (Sale)
            // ===========================
            $sale = Sale::create([
                'date_sale' => $request->filled('fechaDocumento')
                    ? Carbon::createFromFormat('Y-m-d', $request->fechaDocumento)
                    : Carbon::now(),

                'serie'     => $this->generateRandomString(),
                'worker_id' => $worker->id,
                'caja'      => $worker->id,
                'currency'  => ($currency === 'usd') ? 'USD' : 'PEN',

                'op_exonerada'     => 0,
                'op_inafecta'      => 0,
                'op_gravada'       => $quote->gravada,
                'igv'              => $quote->igv_total,
                'total_descuentos' => $quote->descuento,
                'importe_total'    => $quote->total_importe,
                'vuelto'           => 0,

                'quote_id' => $quote->id,

                // ❌ Ya NO usamos tipo_pago_id
                // 'tipo_pago_id' => ...

                'type_document'            => $request->type_document,
                'numero_documento_cliente' => $request->numero_documento_cliente,
                'tipo_documento_cliente'   => $request->tipo_documento_cliente,
                'nombre_cliente'           => $request->nombre_cliente,
                'direccion_cliente'        => $request->direccion_cliente,
                'email_cliente'            => $request->email_cliente,

                'serie_sunat'   => null,
                'numero'        => null,
                'sunat_ticket'  => null,
                'sunat_status'  => null,
                'sunat_message' => null,
                'xml_path'      => null,
                'cdr_path'      => null,
                'pdf_path'      => null,
                'fecha_emision' => null,
            ]);

            $saleDate = $sale->date_sale instanceof Carbon
                ? $sale->date_sale
                : Carbon::parse($sale->date_sale);

            // recolectar materialIds
            $materialIds = [];
            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {
                    $materialIds[] = (int)$consumable->material_id;
                }
            }
            $materialIds = array_values(array_unique($materialIds));

            // costo promedio vigente por material hasta la fecha de emisión
            $avgCosts = $this->inventoryCostService->getAverageCostsUpToDate($materialIds, $saleDate);

            // ===========================
            // 2) Crear Output
            // ===========================
            $output = Output::create([
                'execution_order'   => ($quote->order_execution == "") ? 'VENTA POR COTIZACION' : $quote->order_execution,
                'request_date'      => $sale->date_sale,
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),
                'state'             => 'confirmed',
                'indicator'         => 'or',
            ]);

            $igv = (float) PorcentageQuote::where('name', 'igv')->value('value'); // 18.00
            $factor = bcadd('1', bcdiv((string)$igv, '100', 10), 10); // 1.1800000000

            // ===========================
            // 3) Detalles + stock + output details
            // ===========================
            foreach ($quote->equipments as $equipment) {
                // 1) Consumables (tal cual lo tienes)
                foreach ($equipment->consumables as $consumable) {

                    $materialId = (int)$consumable->material_id;
                    $qty        = (float)$consumable->quantity;

                    $unitCost  = (float)($avgCosts[$materialId] ?? 0.0);
                    $totalCost = $qty * $unitCost;

                    $saleDetail = SaleDetail::create([
                        'sale_id'                  => $sale->id,
                        'material_id'              => $consumable->material_id,
                        'material_presentation_id' => $consumable->material_presentation_id,
                        'valor_unitario'           => $consumable->valor_unitario,
                        'price'                    => $consumable->price,
                        'quantity'                 => $consumable->quantity,
                        'packs'                    => $consumable->packs,
                        'units_per_pack'           => $consumable->units_per_pack,
                        'percentage_tax'           => 18,
                        'total'                    => $consumable->total,
                        'discount'                 => $consumable->discount,

                        // snapshot costos
                        'unit_cost'                => $unitCost,
                        'total_cost'               => $totalCost,
                    ]);

                    $material = $consumable->material;

                    if ($material) {
                        //$material->unit_price     = $unitCost;
                        $material->stock_current  = max(0, (float)$material->stock_current  - (float)$consumable->quantity);
                        $material->stock_reserved = max(0, (float)$material->stock_reserved - (float)$consumable->quantity);
                        $material->save();
                    }

                    $qtyNeeded = (float)$consumable->quantity;

                    if (floor($qtyNeeded) != $qtyNeeded) {
                        throw new \Exception("La cotización requiere cantidad decimal para el material {$materialId}. Aún no está implementado para retazos.");
                    }

                    $qtyNeeded = (int)$qtyNeeded;

                    if ($material && (int)$material->tipo_venta_id === 3) {

                        $items = Item::where('material_id', $materialId)
                            ->whereIn('state_item', ['entered', 'scrapped'])
                            ->orderBy('id', 'asc')
                            ->lockForUpdate()
                            ->take($qtyNeeded)
                            ->get();

                        if ($items->count() < $qtyNeeded) {
                            throw new \Exception("Stock insuficiente del material ID {$materialId}. Se requieren {$qtyNeeded} y solo hay {$items->count()} disponibles.");
                        }

                        foreach ($items as $item) {

                            OutputDetail::create([
                                'output_id'      => $output->id,
                                'sale_detail_id' => $saleDetail->id,
                                'item_id'        => $item->id,
                                'material_id'    => $materialId,
                                'quote_id'       => $quote->id,
                                'custom'         => 0,
                                'percentage'     => 1,
                                'price'          => $item->price,
                                'length'         => $item->length,
                                'width'          => $item->width,
                                'equipment_id'   => $equipment->id ?? null,
                                'activo'         => null,
                                'unit_cost'      => $unitCost,
                                'total_cost'     => $unitCost,
                            ]);

                            $item->state_item = 'exited';
                            $item->save();
                        }
                    }

                    if ($material && (int)$material->tipo_venta_id !== 3) {
                        OutputDetail::create([
                            'output_id'      => $output->id,
                            'sale_detail_id' => $saleDetail->id,
                            'item_id'        => null,
                            'material_id'    => $materialId,
                            'quote_id'       => $quote->id,
                            'custom'         => 0,
                            'percentage'     => $qty,
                            'price'          => $consumable->price,
                            'length'         => null,
                            'width'          => null,
                            'equipment_id'   => $equipment->id ?? null,
                            'activo'         => null,
                            'unit_cost'      => $unitCost,
                            'total_cost'     => $totalCost,
                        ]);
                    }
                }

                // 2) Workforces (solo billable = 1)
                foreach ($equipment->workforces->where('billable', 1) as $wf) {

                    $qty = (string) $wf->quantity;
                    $priceWithIgv = (string) $wf->price; // precio unitario con IGV

                    // 🔥 valor_unitario SIN IGV (20,10)
                    $valorUnitario = bcdiv($priceWithIgv, $factor, 10);

                    // 🔥 total sin IGV
                    $totalSinIgv = bcmul($valorUnitario, $qty, 10);

                    // 🔥 IGV del item
                    $igvItem = bcsub((string)$wf->total, $totalSinIgv, 10);

                    $saleDetail = SaleDetail::create([
                        'sale_id'                  => $sale->id,
                        'material_id'              => null,
                        'material_presentation_id' => null,
                        'description'              => $wf->description,

                        'valor_unitario'           => $valorUnitario, // 🔥 SIN IGV
                        'price'                    => $priceWithIgv,  // CON IGV
                        'quantity'                 => $qty,
                        'packs'                    => null,
                        'units_per_pack'           => null,
                        'percentage_tax'           => $igv,

                        'total'                    => $wf->total,     // total CON IGV
                        'discount'                 => 0,

                        'unit_cost'                => '0.0000000000',
                        'total_cost'               => '0.0000000000',
                    ]);
                }

            }

            // ===========================
            // 4) Caja: movimiento (SIN VUELTO)
            // ===========================
            $cashRegisterQuery = CashRegister::where('cash_box_id', $cashBox->id)
                ->where('user_id', Auth::id())
                ->where('status', 1)
                ->latest();

            // Si tu tabla cash_registers tiene cash_box_subtype_id, filtramos
            if ($needSubtype && $cashBoxSubtypeId && Schema::hasColumn('cash_registers', 'cash_box_subtype_id')) {
                $cashRegisterQuery->where('cash_box_subtype_id', (int)$cashBoxSubtypeId);
            }

            $cashRegister = $cashRegisterQuery->first();

            if (!$cashRegister) {
                return response()->json(['message' => 'No hay caja abierta para la caja/canal seleccionado.'], 422);
            }

            $amount = (float)$quote->total_importe;

            $description = 'Venta desde cotización - ' . ($cashBox->name ?? 'Caja');
            if ($needSubtype && $cashBoxSubtypeId) {
                $sub = CashBoxSubtype::find((int)$cashBoxSubtypeId);
                if ($sub) $description .= ' (' . $sub->name . ')';
            }

            // ===========================
            // Determinar regularize según subtipo (MISMA LÓGICA QUE POS)
            // ===========================
            $regularize = 1;

            if ($cashBox->type === 'bank' && (int) $cashBox->uses_subtypes === 1) {

                if (!$cashBoxSubtypeId) {
                    return response()->json([
                        'message' => 'Debe seleccionar el subtipo bancario (Yape / Plin / POS / Transfer).'
                    ], 422);
                }

                $subtype = CashBoxSubtype::findOrFail($cashBoxSubtypeId);

                // 👉 si es diferido, NO se regulariza aún
                $regularize = $subtype->is_deferred ? 0 : 1;
            } else {
                // por seguridad, si no aplica, lo anulamos
                $cashBoxSubtypeId = null;
            }

            // ===========================
            // Construir movimiento
            // ===========================
            $movement = [
                'cash_register_id'      => $cashRegister->id,
                'type'                  => 'sale',
                'amount'                => $amount,
                'description'           => $description,
                'sale_id'               => $sale->id,
                // ✅ correcto
                'regularize'            => $regularize,
            ];

            // Si hay subtipo, lo seteamos (tu modelo SÍ lo tiene)
            if (!is_null($cashBoxSubtypeId)) {
                $movement['cash_box_subtype_id'] = (int) $cashBoxSubtypeId;
            }

            CashMovement::create($movement);

            // Si NO es bank, actualizamos saldos (si aplica en tu modelo)
            if ($regularize == 1) {
                $cashRegister->current_balance += $amount;
                $cashRegister->total_sales     += $amount;
                $cashRegister->save();
            }

            // ===========================
            // Notificación + Auditoría
            // ===========================
            $notification = Notification::create([
                'content' => 'Venta creada desde cotización por ' . Auth::user()->name,
                'reason_for_creation' => 'create_sale_from_quote',
                'user_id' => Auth::id(),
                'url_go' => route('puntoVenta.index')
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $u) {
                if ($u->id != Auth::id()) {
                    foreach ($u->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $u->id,
                            'read' => false
                        ]);
                    }
                }
            }

            $elapsed = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::id(),
                'action'  => 'Guardar venta desde cotización',
                'time'    => $elapsed
            ]);

            DB::commit();

            // ===========================
            // 5) Nubefact (igual que tu bloque)
            // ===========================
            $nubefactResult = null;

            if (in_array($sale->type_document, ['01', '03'])) {
                try {
                    $sale->loadMissing(['details.material']);
                    $nubefactResult = $this->generarComprobanteNubefactParaVenta($sale);

                    $filename = 'ORD' . $sale->id;
                    $pdfFilename = $filename . '.pdf';
                    $xmlFilename = $filename . '.xml';
                    $cdrFilename = $filename . '.zip';

                    foreach (['pdfs', 'xmls', 'cdrs'] as $folder) {
                        if (!file_exists(public_path("comprobantes/$folder"))) {
                            mkdir(public_path("comprobantes/$folder"), 0777, true);
                        }
                    }

                    if (!empty($nubefactResult['enlace_del_pdf'])) {
                        $pdfContent = Http::get($nubefactResult['enlace_del_pdf'])->body();
                        file_put_contents(public_path('comprobantes/pdfs/' . $pdfFilename), $pdfContent);
                    }
                    if (!empty($nubefactResult['enlace_del_xml'])) {
                        $xmlContent = Http::get($nubefactResult['enlace_del_xml'])->body();
                        file_put_contents(public_path('comprobantes/xmls/' . $xmlFilename), $xmlContent);
                    }
                    if (!empty($nubefactResult['enlace_del_cdr'])) {
                        $cdrContent = Http::get($nubefactResult['enlace_del_cdr'])->body();
                        file_put_contents(public_path('comprobantes/cdrs/' . $cdrFilename), $cdrContent);
                    }

                    $sale->update([
                        'serie_sunat'   => $nubefactResult['serie'] ?? null,
                        'numero'        => $nubefactResult['numero'] ?? null,
                        'sunat_ticket'  => $nubefactResult['sunat_ticket'] ?? null,
                        'sunat_status'  => $nubefactResult['sunat_description'] ?? 'Enviado',
                        'sunat_message' => $nubefactResult['sunat_note'] ?? '',
                        'xml_path'      => file_exists(public_path('comprobantes/xmls/' . $xmlFilename)) ? $xmlFilename : null,
                        'cdr_path'      => file_exists(public_path('comprobantes/cdrs/' . $cdrFilename)) ? $cdrFilename : null,
                        'pdf_path'      => file_exists(public_path('comprobantes/pdfs/' . $pdfFilename)) ? $pdfFilename : null,
                        'fecha_emision' => now()->toDateString(),
                    ]);

                } catch (\Throwable $e) {
                    $sale->update([
                        'sunat_status'  => 'Error',
                        'sunat_message' => $e->getMessage(),
                    ]);
                }
            }

            // ===========================
            // URL impresión
            // ===========================
            $urlPrint  = route('puntoVenta.print', $sale->id);
            $printType = 'ticket';

            if (in_array($sale->type_document, ['01', '03'])) {
                if (!empty($nubefactResult['enlace_del_pdf'])) {
                    $urlPrint = $nubefactResult['enlace_del_pdf'];
                }
                if (!empty($sale->pdf_path)) {
                    $localPath = public_path('comprobantes/pdfs/' . $sale->pdf_path);
                    if (file_exists($localPath)) {
                        $urlPrint  = asset('comprobantes/pdfs/' . $sale->pdf_path);
                        $printType = 'sunat_pdf';
                    } elseif (!empty($nubefactResult['enlace_del_pdf'])) {
                        $urlPrint  = $nubefactResult['enlace_del_pdf'];
                        $printType = 'sunat_pdf';
                    }
                }
            }

            return response()->json([
                'message'    => 'Venta creada con éxito desde cotización' . ($nubefactResult ? ' y comprobante generado.' : ' (sin comprobante).'),
                'sale_id'    => $sale->id,
                'nubefact'   => $nubefactResult,
                'url_print'  => $urlPrint,
                'print_type' => $printType,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function storeFromQuote(Request $request)
    {
        $begin = microtime(true);

        $tenantId = TenantContext::tenantId();
        $companyId = TenantContext::companyId();
        $branchId = TenantContext::branchId();
        $userId = Auth::id();


        $settings = app(SettingService::class);

        $currency = $settings->get('finance.base_currency');

        $request->validate([
            'quote_id' => ['required', 'integer', 'min:1'],
            'pagos_parciales_venta' => ['nullable', 'in:s,n'],
            'fechaDocumento' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $pagosParcialesVenta =
            $request->input('pagos_parciales_venta') ?? 'n';

        $allowPartialPayments = (bool) $settings->get(
            'sales.allow_partial_payments'
        );

        if ($pagosParcialesVenta === 's' && !$allowPartialPayments) {
            return response()->json([
                'message' => 'Esta empresa no tiene habilitados los pagos parciales.',
            ], 422);
        }

        // ===========================
        // ✅ Validación CashBox/Subtype
        // Solo aplica si NO es pago parcial
        // ===========================

        $cashBox = null;
        $validSubtype = null;
        $cashRegister = null;

        $cashBoxId = null;
        $cashBoxSubtypeId = null;
        $needSubtype = false;

        if ($pagosParcialesVenta === 'n') {
            $request->validate([
                'type_document' => ['nullable', 'string', 'in:01,03'],

                'tipo_documento_cliente' => [
                    'nullable',
                    'required_with:type_document',
                    'string',
                    'in:1,6',
                ],

                'numero_documento_cliente' => [
                    'nullable',
                    'required_with:type_document',
                    'string',
                    'max:11',
                ],

                'nombre_cliente' => [
                    'nullable',
                    'required_with:type_document',
                    'string',
                    'max:255',
                ],

                'direccion_cliente' => [
                    'nullable',
                    'required_if:type_document,01',
                    'string',
                    'max:255',
                ],

                'email_cliente' => [
                    'nullable',
                    'email',
                    'max:254',
                ],
            ]);

            if (
                $request->type_document === '03' &&
                !auth()->user()->can('generarBoleta_quoteSale')
            ) {
                abort(403, 'No tiene permiso para generar boletas.');
            }

            if (
                $request->type_document === '01' &&
                !auth()->user()->can('generarFactura_quoteSale')
            ) {
                abort(403, 'No tiene permiso para generar facturas.');
            }
        }

        DB::beginTransaction();
        try {

            if ($pagosParcialesVenta === 'n') {
                $cashBoxId = $request->input('pv_cash_box_id');
                $cashBoxSubtypeId = $request->input('pv_cash_box_subtype_id');

                if (
                    !is_scalar($cashBoxId) ||
                    !ctype_digit((string) $cashBoxId) ||
                    (int) $cashBoxId < 1
                ) {
                    throw new \Exception('Seleccione una caja válida.');
                }

                $cashBox = CashBox::query()
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->where('id', (int) $cashBoxId)
                    ->where('is_active', 1)
                    ->where(function ($query) use ($branchId) {
                        $query->whereNull('branch_id')
                            ->orWhere('branch_id', $branchId);
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$cashBox) {
                    throw new \Exception(
                        'La caja no está disponible para la empresa y sucursal actuales.'
                    );
                }

                if ((string) $cashBox->currency !== (string) $currency) {
                    throw new \Exception(
                        'La moneda de la caja no coincide con la moneda de la venta.'
                    );
                }

                $needSubtype =
                    $cashBox->type === 'bank' &&
                    (bool) $cashBox->uses_subtypes;

                if ($needSubtype) {
                    if (
                        !is_scalar($cashBoxSubtypeId) ||
                        !ctype_digit((string) $cashBoxSubtypeId) ||
                        (int) $cashBoxSubtypeId < 1
                    ) {
                        throw new \Exception('Seleccione un canal/subtipo válido.');
                    }

                    $validSubtype = CashBoxSubtype::query()
                        ->where('tenant_id', $tenantId)
                        ->where('company_id', $companyId)
                        ->where('id', (int) $cashBoxSubtypeId)
                        ->where('is_active', 1)
                        ->availableForCashBox($cashBox->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$validSubtype) {
                        throw new \Exception(
                            'El subtipo está inactivo o no corresponde a la caja seleccionada.'
                        );
                    }

                    $cashBoxSubtypeId = (int) $validSubtype->id;
                } else {
                    $cashBoxSubtypeId = null;
                }

                $registers = CashRegister::query()
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->where('branch_id', $branchId)
                    ->where('cash_box_id', $cashBox->id)
                    ->where('user_id', $userId)
                    ->where('status', 1)
                    ->orderBy('id')
                    ->limit(2)
                    ->lockForUpdate()
                    ->get();

                if ($registers->isEmpty()) {
                    throw new \Exception(
                        'No tiene una sesión abierta para esta caja en la sucursal actual.'
                    );
                }

                if ($registers->count() > 1) {
                    throw new \Exception(
                        'Existen varias sesiones abiertas para esta caja y usuario. Revise las aperturas.'
                    );
                }

                $cashRegister = $registers->first();
            }

            $quote = Quote::with([
                'equipments.consumables.material',
                'equipments.consumables.stockItem',
                'equipments.workforces',
            ])
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->where('id', $request->quote_id)
                ->lockForUpdate()
                ->firstOrFail();

            app(QuoteStockReservationService::class)->validateReservationsForSale((int) $quote->id);

            if (!$quote || $quote->state !== 'confirmed') {
                throw new \Exception("La cotización no es válida o no está confirmada.");
            }

            $activeSale = Sale::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->where('quote_id', $quote->id)
                ->where('state_annulled', 0)
                ->lockForUpdate()
                ->first();

            if ($activeSale) {
                throw new \Exception(
                    'La cotización ya fue convertida en una venta activa.'
                );
            }

            /* $tipoComprobante = $request->tipo_documento_cliente; // 01 factura, 03 boleta
            $typeDocument = $request->type_document; // 1 DNI, 6 RUC
            $numeroDocumento = preg_replace('/\D/', '', $request->numero_documento_cliente);*/

            $tipoComprobante = $request->type_document; // 01 factura, 03 boleta
            $typeDocument = $request->tipo_documento_cliente; // 1 DNI, 6 RUC
            $numeroDocumento = preg_replace('/\D/', '', $request->numero_documento_cliente ?? '');

            if ($pagosParcialesVenta === 'n') {

                // Boleta = DNI
                if ($tipoComprobante === '03') {

                    if ((string)$typeDocument !== '1') {
                        throw new \Exception("Para emitir una boleta, el tipo de documento debe ser DNI.");
                    }

                    if (!preg_match('/^\d{8}$/', $numeroDocumento)) {
                        throw new \Exception("El DNI debe contener exactamente 8 dígitos.");
                    }
                }

                // Factura = RUC
                if ($tipoComprobante === '01') {

                    if ((string)$typeDocument !== '6') {
                        throw new \Exception("Para emitir una factura, el tipo de documento debe ser RUC.");
                    }

                    if (!preg_match('/^\d{11}$/', $numeroDocumento)) {
                        throw new \Exception("El RUC debe contener exactamente 11 dígitos.");
                    }
                }
            }

            // ===========================
            // 1) Crear venta (Sale)
            // ===========================
            $identity = app(\App\Services\Workers\WorkerUserLink::class)->saleIdentity();

            $sale = Sale::create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'branch_id' => $branchId,

                'date_sale' => $request->filled('fechaDocumento')
                    ? Carbon::createFromFormat('Y-m-d', $request->fechaDocumento)
                    : Carbon::now(),

                'serie'     => $this->generateRandomString(),
                'worker_id' => $identity['worker_id'],
                'created_by' => $identity['created_by'],
                'created_by_name' => $identity['created_by_name'],
                'caja'      => $identity['worker_id'], // Legacy field; not a CashBox ID.
                'currency'  => $currency,

                'op_exonerada'     => $quote->exonerada ?? 0,
                'op_inafecta'      => $quote->inafecta ?? 0,
                'op_gravada'       => $quote->gravada,
                'igv'              => $quote->igv_total,
                'total_descuentos' => $quote->descuento,
                'importe_total'    => $quote->total_importe,
                'vuelto'           => 0,

                'quote_id' => $quote->id,
                'customer_id' => $quote->customer_id,
                'dispatch_status' => ($pagosParcialesVenta === 's') ? 'pendiente': 'despachado',

                'type_document' => $pagosParcialesVenta === 's'
                    ? null
                    : $request->type_document,

                'numero_documento_cliente' => $pagosParcialesVenta === 's'
                    ? null
                    : $numeroDocumento,

                'tipo_documento_cliente' => $pagosParcialesVenta === 's'
                    ? null
                    : $request->tipo_documento_cliente,

                'nombre_cliente' => $pagosParcialesVenta === 's'
                    ? null
                    : $request->nombre_cliente,

                'direccion_cliente' => $pagosParcialesVenta === 's'
                    ? null
                    : $request->direccion_cliente,

                'email_cliente' => $pagosParcialesVenta === 's'
                    ? null
                    : $request->email_cliente,

                'pagos_parciales_venta' => $pagosParcialesVenta,

                'serie_sunat'   => null,
                'numero'        => null,
                'sunat_ticket'  => null,
                'sunat_status'  => null,
                'sunat_message' => null,
                'xml_path'      => null,
                'cdr_path'      => null,
                'pdf_path'      => null,
                'fecha_emision' => null,

                'emission_status' => 'not_requested',
            ]);

            // ===========================
            // 2) Crear Output
            // ===========================
            $output = Output::create([
                'tenant_id' => $sale->tenant_id,
                'company_id' => $sale->company_id,
                'branch_id' => $sale->branch_id,

                'execution_order'   => ($quote->order_execution == "") ? 'VENTA POR COTIZACION' : $quote->order_execution,
                'request_date'      => $sale->date_sale,
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),
                'state'             => 'confirmed',
                'indicator'         => 'or',
            ]);

            $igv = (float) PorcentageQuote::where('name', 'igv')->value('value');
            $factor = bcadd('1', bcdiv((string)$igv, '100', 10), 10);

            // ===========================
            // 3) Detalles + consumo real de reservas/lotes
            // ===========================
            foreach ($quote->equipments as $equipment) {

                foreach ($equipment->consumables as $consumable) {
                    $materialId  = (int) $consumable->material_id;
                    $stockItemId = (int) $consumable->stock_item_id;
                    $qty         = (float) $consumable->quantity;

                    if (!$stockItemId) {
                        throw new \Exception("El consumible {$consumable->id} no tiene stock_item_id.");
                    }

                    $material = $consumable->material;

                    $reservedLots = QuoteStockLot::where('quote_id', $quote->id)
                        ->where('quote_detail_id', $consumable->id)
                        ->where('stock_item_id', $stockItemId)
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    if ($reservedLots->isEmpty()) {
                        throw new \Exception("No se encontraron reservas para el consumible {$consumable->id}.");
                    }

                    $requiredQty = (string) $consumable->quantity;

                    if (bccomp($requiredQty, '0', 10) <= 0) {
                        throw new \Exception(
                            "La cantidad del consumible {$consumable->id} debe ser mayor que cero."
                        );
                    }

                    $reservedQty = '0';

                    foreach ($reservedLots as $reservedLot) {
                        $lotReservedQty = (string) $reservedLot->quantity;

                        if (bccomp($lotReservedQty, '0', 10) <= 0) {
                            throw new \Exception(
                                "La reserva {$reservedLot->id} tiene una cantidad inválida."
                            );
                        }

                        $reservedQty = bcadd(
                            $reservedQty,
                            $lotReservedQty,
                            10
                        );
                    }

                    if (bccomp($reservedQty, $requiredQty, 10) !== 0) {
                        throw new \Exception(
                            "Las reservas del consumible {$consumable->id} " .
                            "no coinciden con la cantidad cotizada. " .
                            "Reservado: {$reservedQty}. Requerido: {$requiredQty}."
                        );
                    }

                    $totalCost = (float) $reservedLots->sum(function ($row) {
                        return (float) $row->quantity * (float) $row->unit_cost;
                    });
                    $unitCost = $qty > 0 ? ($totalCost / $qty) : 0;

                    $saleDetailData = [
                        'tenant_id'                => $sale->tenant_id,
                        'company_id'               => $sale->company_id,

                        'sale_id'                  => $sale->id,
                        'material_id'              => $materialId,
                        'stock_item_id'            => $stockItemId,
                        'material_presentation_id' => $consumable->material_presentation_id,
                        'valor_unitario'           => $consumable->valor_unitario,
                        'price'                    => $consumable->price,
                        'quantity'                 => $consumable->quantity,
                        'packs'                    => $consumable->packs,
                        'units_per_pack'           => $consumable->units_per_pack,
                        'percentage_tax'           => 18,
                        'total'                    => $consumable->total,
                        'discount'                 => $consumable->discount,
                        'unit_cost'                => $unitCost,
                        'total_cost'               => $totalCost,
                        'item_snapshot'            => null,
                    ];

                    if (Schema::hasColumn('sale_details', 'stock_item_id')) {
                        $saleDetailData['stock_item_id'] = $stockItemId;
                    }

                    $saleDetail = SaleDetail::create($saleDetailData);

                    if ($material && (int) $material->tipo_venta_id === 3) {

                        $itemSnapshot = [];

                        if (floor($qty) != $qty) {
                            throw new \Exception(
                                "La cotización requiere cantidad decimal para el material {$materialId}. " .
                                "Aún no está implementado para retazos."
                            );
                        }

                        foreach ($reservedLots as $reservedLot) {
                            $consumeQty = (float) $reservedLot->quantity;

                            if ($consumeQty <= 0) {
                                continue;
                            }

                            if (floor($consumeQty) != $consumeQty) {
                                throw new \Exception(
                                    "La reserva del lote {$reservedLot->stock_lot_id} tiene cantidad decimal y no está implementado para items unitarios."
                                );
                            }

                            $consumeQtyInt = (int) $consumeQty;

                            /*
                             * Los IDs no se buscan nuevamente por FIFO.
                             * Deben ser exactamente los ítems físicos que se reservaron
                             * al guardar la cotización.
                             */
                            $reservedItemIds = $reservedLot->item_ids ?? [];

                            if (is_string($reservedItemIds)) {
                                $decodedItemIds = json_decode($reservedItemIds, true);
                                $reservedItemIds = is_array($decodedItemIds)
                                    ? $decodedItemIds
                                    : [];
                            }

                            if (!is_array($reservedItemIds)) {
                                $reservedItemIds = [];
                            }

                            $reservedItemIds = collect($reservedItemIds)
                                ->map(function ($itemId) {
                                    return (int) $itemId;
                                })
                                ->filter(function ($itemId) {
                                    return $itemId > 0;
                                })
                                ->unique()
                                ->values()
                                ->toArray();

                            if (count($reservedItemIds) !== $consumeQtyInt) {
                                throw new \Exception(
                                    "Los ítems reservados no coinciden con la cantidad del lote {$reservedLot->stock_lot_id}. " .
                                    "Esperados: {$consumeQtyInt}. Registrados: " . count($reservedItemIds) . '.'
                                );
                            }

                            /*
                             * Se bloquean únicamente los Items seleccionados en cotización.
                             * Deben seguir reservados y pertenecer al mismo StockItem/lote.
                             */
                            $reservedItems = Item::query()
                                ->where('tenant_id', $tenantId)
                                ->where('company_id', $companyId)
                                ->whereIn('id', $reservedItemIds)
                                ->where('stock_item_id', $stockItemId)
                                ->where('stock_lot_id', $reservedLot->stock_lot_id)
                                ->where('percentage', 1)
                                ->where('state_item', 'reserved')
                                ->lockForUpdate()
                                ->get();

                            if ($reservedItems->count() !== $consumeQtyInt) {
                                throw new \Exception(
                                    "Uno o más ítems reservados ya no están disponibles para completar la venta del lote {$reservedLot->stock_lot_id}."
                                );
                            }

                            $lot = StockLot::query()
                                ->where('tenant_id', $tenantId)
                                ->where('company_id', $companyId)
                                ->where('id', $reservedLot->stock_lot_id)
                                ->where('stock_item_id', $stockItemId)
                                ->lockForUpdate()
                                ->first();

                            if (!$lot) {
                                throw new \Exception(
                                    "No se encontró el lote reservado {$reservedLot->stock_lot_id}."
                                );
                            }

                            if (
                                (float) $lot->qty_reserved < $consumeQty ||
                                (float) $lot->qty_on_hand < $consumeQty
                            ) {
                                throw new \Exception(
                                    "El lote {$lot->id} no tiene stock/reserva suficiente para consumirse."
                                );
                            }

                            foreach ($reservedItems as $item) {
                                $itemSnapshot[] = [
                                    'id' => (int) $item->id,
                                    'code' => (string) $item->code,
                                ];

                                OutputDetail::create([
                                    'tenant_id' => $output->tenant_id,
                                    'company_id' => $output->company_id,

                                    'output_id' => $output->id,
                                    'sale_detail_id' => $saleDetail->id,

                                    'item_id' => $item->id,
                                    'material_id' => $materialId,
                                    'stock_item_id' => $item->stock_item_id,
                                    'stock_lot_id' => $item->stock_lot_id,
                                    'warehouse_id' => $item->warehouse_id,
                                    'location_id' => $item->location_id,

                                    'quote_id' => $quote->id,
                                    'custom' => 0,
                                    'percentage' => 1,

                                    'price' => $item->price,
                                    'length' => $item->length,
                                    'width' => $item->width,

                                    'equipment_id' => $equipment->id ?? null,
                                    'activo' => null,

                                    'unit_cost' => (float) (
                                        $item->unit_cost ?? $reservedLot->unit_cost
                                    ),

                                    'total_cost' => (float) (
                                        $item->unit_cost ?? $reservedLot->unit_cost
                                    ),
                                ]);

                                $item->state_item = 'exited';
                                $item->save();
                            }

                            /*
                             * La reserva ahora se convierte en salida real.
                             */
                            $lot->qty_on_hand = (float) $lot->qty_on_hand - $consumeQty;
                            $lot->qty_reserved = (float) $lot->qty_reserved - $consumeQty;
                            $lot->save();

                            $this->syncInventoryLevelFromLots(
                                $stockItemId,
                                $lot->warehouse_id,
                                $lot->location_id,
                                $consumeQty
                            );

                            /*
                             * Ya no existe reserva pendiente para este lote.
                             */
                            $reservedLot->delete();
                        }

                        $saleDetail->item_snapshot = $itemSnapshot;
                        $saleDetail->save();

                    } else {

                        foreach ($reservedLots as $reservedLot) {
                            $consumeQty = (float) $reservedLot->quantity;

                            if ($consumeQty <= 0) {
                                continue;
                            }

                            $lot = StockLot::query()
                                ->where('tenant_id', $tenantId)
                                ->where('company_id', $companyId)
                                ->where('id', $reservedLot->stock_lot_id)
                                ->where('stock_item_id', $stockItemId)
                                ->lockForUpdate()
                                ->first();

                            if (!$lot) {
                                throw new \Exception("No se encontró el lote reservado {$reservedLot->stock_lot_id}.");
                            }

                            if ((float) $lot->qty_reserved < $consumeQty) {
                                throw new \Exception(
                                    "El lote {$lot->id} no tiene reserva suficiente. " .
                                    "Reservado en lote: {$lot->qty_reserved}. Requerido: {$consumeQty}."
                                );
                            }

                            if ((float) $lot->qty_on_hand < $consumeQty) {
                                throw new \Exception(
                                    "El lote {$lot->id} no tiene stock suficiente para consumirse. " .
                                    "Stock: {$lot->qty_on_hand}. Requerido: {$consumeQty}."
                                );
                            }

                            $lot->qty_on_hand  = (float) $lot->qty_on_hand - $consumeQty;
                            $lot->qty_reserved = (float) $lot->qty_reserved - $consumeQty;
                            $lot->save();

                            $this->syncInventoryLevelFromLots(
                                $stockItemId,
                                $lot->warehouse_id,
                                $lot->location_id,
                                $consumeQty
                            );

                            OutputDetail::create([
                                'tenant_id' => $output->tenant_id,
                                'company_id' => $output->company_id,

                                'output_id'      => $output->id,
                                'sale_detail_id' => $saleDetail->id,
                                'item_id'        => null,
                                'material_id'    => $materialId,
                                'stock_item_id'  => $stockItemId,
                                'stock_lot_id'   => $lot->id,
                                'warehouse_id'   => $lot->warehouse_id,
                                'location_id'    => $lot->location_id,
                                'quote_id'       => $quote->id,
                                'custom'         => 0,
                                'percentage'     => $consumeQty,
                                'price'          => $consumable->price,
                                'length'         => null,
                                'width'          => null,
                                'equipment_id'   => $equipment->id ?? null,
                                'activo'         => null,
                                'unit_cost'      => (float) $reservedLot->unit_cost,
                                'total_cost'     => (float) $reservedLot->unit_cost * $consumeQty,
                            ]);

                            $reservedLot->delete();
                        }
                    }
                }

                foreach ($equipment->workforces->where('billable', 1) as $wf) {
                    $qty = (string) $wf->quantity;
                    $priceWithIgv = (string) $wf->price;

                    $valorUnitario = bcdiv($priceWithIgv, $factor, 10);

                    SaleDetail::create([
                        'tenant_id'                => $sale->tenant_id,
                        'company_id'               => $sale->company_id,

                        'sale_id'                  => $sale->id,
                        'material_id'              => null,
                        'stock_item_id'            => null,
                        'material_presentation_id' => null,
                        'description'              => $wf->description,
                        'valor_unitario'           => $valorUnitario,
                        'price'                    => $priceWithIgv,
                        'quantity'                 => $qty,
                        'packs'                    => null,
                        'units_per_pack'           => null,
                        'percentage_tax'           => $igv,
                        'total'                    => $wf->total,
                        'discount'                 => 0,
                        'unit_cost'                => '0.0000000000',
                        'total_cost'               => '0.0000000000',
                    ]);
                }
            }

            // ===========================
            // 4) Movimiento financiero
            // Pagos parciales: sin cobro inicial.
            // ===========================
            if ($pagosParcialesVenta === 'n') {
                $amount = bcadd(
                    (string) $quote->total_importe,
                    '0',
                    2
                );

                if (bccomp($amount, '0', 2) <= 0) {
                    throw new \Exception(
                        'El importe de la venta debe ser mayor que cero.'
                    );
                }

                $regularize = $validSubtype && $validSubtype->is_deferred
                    ? 0
                    : 1;

                $description = 'Venta desde cotización '
                    . $quote->code
                    . ' - '
                    . $cashBox->name;

                if ($validSubtype) {
                    $description .= ' (' . $validSubtype->name . ')';
                }

                CashMovement::create([
                    'tenant_id' => $sale->tenant_id,
                    'company_id' => $sale->company_id,
                    'branch_id' => $cashRegister->branch_id,

                    'cash_box_id' => $cashBox->id,
                    'cash_register_id' => $cashRegister->id,
                    'cash_box_subtype_id' => $cashBoxSubtypeId,
                    'user_id' => $userId,

                    'type' => 'sale',
                    'amount' => $amount,
                    'description' => $description,
                    'sale_id' => $sale->id,

                    'regularize' => $regularize,
                ]);

                if ($regularize === 1) {
                    $cashRegister->current_balance = bcadd(
                        (string) $cashRegister->current_balance,
                        $amount,
                        2
                    );

                    $cashRegister->total_sales = bcadd(
                        (string) $cashRegister->total_sales,
                        $amount,
                        2
                    );

                    $cashRegister->save();
                }
            }

            // ===========================
            // Notificación + Auditoría
            // ===========================
            $notification = Notification::create([
                'content' => 'Venta creada desde cotización por ' . Auth::user()->name,
                'reason_for_creation' => 'create_sale_from_quote',
                'user_id' => Auth::id(),
                'url_go' => route('puntoVenta.index')
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])
                ->where('users.tenant_id', $tenantId)
                ->where('users.id', '<>', $userId)
                ->whereHas('companies', function ($query) use ($companyId) {
                    $query->where('companies.id', $companyId)
                        ->where('company_user.is_active', 1);
                })
                ->get();
            foreach ($users as $u) {
                if ($u->id != Auth::id()) {
                    foreach ($u->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $u->id,
                            'read' => false
                        ]);
                    }
                }
            }

            $elapsed = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::id(),
                'action'  => 'Guardar venta desde cotización',
                'time'    => $elapsed
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Desde aquí la venta ya está registrada.
        // Los errores posteriores no deben intentar revertirla.
        $nubefactResult = null;
        $warnings = [];

        $urlPrint = null;
        $printType = 'ticket';
        $outcome = null;

        try {
            // ===========================
            // 5) Nubefact
            // ===========================
            if ($pagosParcialesVenta === 'n' && in_array($sale->type_document, ['01', '03'])) {
                $outcome = app(SaleEmissionService::class)->emit(
                    $sale,
                    function (
                        Sale $lockedSale,
                        \App\Services\NubefactCompanyConnection $connection
                    ) {
                        return $this->buildNubefactData(
                            $lockedSale,
                            $connection
                        );
                    }
                );

                $nubefactResult = $outcome['result'];

                $warnings = array_merge(
                    $warnings,
                    $outcome['warnings']
                );

                /*
                 * SEGUNDO PROCESO:
                 * Descargar PDF, XML y CDR.
                 *
                 * Solamente se ejecuta si Nubefact devolvió un resultado.
                 */
                if (is_array($nubefactResult)) {
                    $filename = 'ORD' . $sale->id;

                    $pdfFilename = $filename . '.pdf';
                    $xmlFilename = $filename . '.xml';
                    $cdrFilename = $filename . '.zip';

                    $pdfPath = public_path(
                        'comprobantes/pdfs/' . $pdfFilename
                    );

                    $xmlPath = public_path(
                        'comprobantes/xmls/' . $xmlFilename
                    );

                    $cdrPath = public_path(
                        'comprobantes/cdrs/' . $cdrFilename
                    );

                    /*
                     * Creamos las carpetas con permisos estándar.
                     * Ya no usamos 0777.
                     */
                    foreach (['pdfs', 'xmls', 'cdrs'] as $folder) {
                        $directory = public_path(
                            'comprobantes/' . $folder
                        );

                        if (!is_dir($directory)) {
                            if (
                                !mkdir($directory, 0755, true) &&
                                !is_dir($directory)
                            ) {
                                Log::error(
                                    'No se pudo crear directorio de comprobantes',
                                    [
                                        'sale_id' => $sale->id,
                                        'directory' => $directory,
                                    ]
                                );
                            }
                        }
                    }

                    /*
                     * Las tres descargas son independientes.
                     *
                     * Si falla el PDF, todavía intentamos descargar XML y CDR.
                     * Si falla el XML, todavía intentamos descargar el CDR.
                     */
                    $pdfDescargado = false;
                    $xmlDescargado = false;
                    $cdrDescargado = false;

                    $erroresDescarga = [];

                    if (!empty($nubefactResult['enlace_del_pdf'])) {
                        try {
                            $this->descargarArchivoNubefactSeguro(
                                $nubefactResult['enlace_del_pdf'],
                                $pdfPath,
                                'pdf'
                            );

                            $pdfDescargado = true;
                        } catch (\Throwable $e) {
                            $erroresDescarga[] = 'PDF: ' . $e->getMessage();

                            Log::error(
                                'El comprobante fue emitido, pero falló el PDF',
                                [
                                    'sale_id' => $sale->id,
                                    'error' => $e->getMessage(),
                                    'url' => $nubefactResult['enlace_del_pdf'],
                                ]
                            );
                        }
                    }

                    if (!empty($nubefactResult['enlace_del_xml'])) {
                        try {
                            $this->descargarArchivoNubefactSeguro(
                                $nubefactResult['enlace_del_xml'],
                                $xmlPath,
                                'xml'
                            );

                            $xmlDescargado = true;
                        } catch (\Throwable $e) {
                            $erroresDescarga[] = 'XML: ' . $e->getMessage();

                            Log::error(
                                'El comprobante fue emitido, pero falló el XML',
                                [
                                    'sale_id' => $sale->id,
                                    'error' => $e->getMessage(),
                                    'url' => $nubefactResult['enlace_del_xml'],
                                ]
                            );
                        }
                    }

                    if (!empty($nubefactResult['enlace_del_cdr'])) {
                        try {
                            $this->descargarArchivoNubefactSeguro(
                                $nubefactResult['enlace_del_cdr'],
                                $cdrPath,
                                'zip'
                            );

                            $cdrDescargado = true;
                        } catch (\Throwable $e) {
                            $erroresDescarga[] = 'CDR: ' . $e->getMessage();

                            Log::error(
                                'El comprobante fue emitido, pero falló el CDR',
                                [
                                    'sale_id' => $sale->id,
                                    'error' => $e->getMessage(),
                                    'url' => $nubefactResult['enlace_del_cdr'],
                                ]
                            );
                        }
                    }

                    /*
                     * Solo guardamos las rutas cuando la descarga y validación
                     * fueron correctas.
                     *
                     * Ya no usamos únicamente file_exists(), porque un HTML
                     * también podría existir con extensión .pdf.
                     */
                    $datosArchivos = [];

                    if ($pdfDescargado) {
                        $datosArchivos['pdf_path'] = $pdfFilename;
                    }

                    if ($xmlDescargado) {
                        $datosArchivos['xml_path'] = $xmlFilename;
                    }

                    if ($cdrDescargado) {
                        $datosArchivos['cdr_path'] = $cdrFilename;
                    }

                    if (!empty($datosArchivos)) {
                        $sale->update($datosArchivos);
                    }

                    /*
                     * No cambiamos sunat_status a Error, porque el comprobante
                     * sí fue emitido.
                     *
                     * El detalle técnico queda en laravel.log.
                     */
                    if (!empty($erroresDescarga)) {
                        $warnings[] =
                            'El comprobante fue emitido, pero quedaron archivos pendientes de descarga.';

                        Log::warning(
                            'Comprobante emitido con archivos pendientes',
                            [
                                'sale_id' => $sale->id,
                                'errores' => $erroresDescarga,
                            ]
                        );
                    }

                    /*
                     * Recargamos el modelo porque acabamos de actualizar
                     * pdf_path, xml_path y cdr_path.
                     */
                    $sale->refresh();
                }
            }

            $urlPrint  = route('puntoVenta.print', $sale->id);
            $printType = 'ticket';

            if ($pagosParcialesVenta === 'n' && in_array($sale->type_document, ['01', '03'])) {
                if (!empty($sale->pdf_path)) {
                    $localPath = public_path(
                        'comprobantes/pdfs/' . $sale->pdf_path
                    );

                    if (file_exists($localPath)) {
                        $urlPrint = asset(
                            'comprobantes/pdfs/' . $sale->pdf_path
                        );

                        $printType = 'sunat_pdf';
                    }
                }

                /*
                 * Si el archivo local no se pudo descargar,
                 * utilizamos directamente el enlace de Nubefact.
                 */
                if (
                    $printType !== 'sunat_pdf' &&
                    !empty($nubefactResult['enlace_del_pdf'])
                ) {
                    $urlPrint = $nubefactResult['enlace_del_pdf'];
                    $printType = 'sunat_pdf';
                }
            }
        } catch (\Throwable $e) {
            $warnings[] =
                'La venta quedó registrada, pero no se pudo completar ' .
                'el procesamiento del comprobante o la impresión. ' .
                'Revise la venta antes de repetir la operación.';

            Log::error('Error posterior al commit de venta desde cotización', [
                'sale_id' => $sale->id,
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'error' => $e->getMessage(),
            ]);
        }

        $warnings = array_values(array_unique($warnings));

        $message = 'Venta creada con éxito desde cotización.';

        if (!empty($warnings)) {
            $message = 'Venta registrada con advertencias.';
        } elseif (is_array($nubefactResult)) {
            $message = 'Venta creada y comprobante generado.';
        }

        return response()->json([
            'message' => $message,
            'sale_id' => $sale->id,
            'sale_created' => true,
            'warnings' => $warnings,
            'nubefact' => $nubefactResult,
            'url_print' => $urlPrint,
            'print_type' => $printType,
            'emission_status' => $outcome['emission_status'] ?? $sale->emission_status,
        ], 200);
    }

    public function recoverEmissionPersistence(Request $request, $saleId)
    {
        $data = $request->validate([
            'attempt_id' => ['required', 'integer', 'min:1'],
        ]);

        if (!ctype_digit((string) $saleId) || (int) $saleId < 1) {
            abort(404);
        }

        try {
            $result = app(SaleEmissionRecoveryService::class)->recover(
                (int) $saleId,
                (int) $data['attempt_id']
            );

            return response()->json($result, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();

            return response()->json([
                'message' => $errors['attempt_id'][0]
                    ?? 'No se pudo recuperar la persistencia del comprobante.',
                'errors' => $errors,
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Venta o intento no encontrado en la empresa actual.',
            ], 404);

        } catch (\Throwable $e) {
            Log::error('Fallo al recuperar persistencia de emisión', [
                'sale_id' => (int) $saleId,
                'attempt_id' => (int) $data['attempt_id'],
                'tenant_id' => TenantContext::tenantId(),
                'company_id' => TenantContext::companyId(),
                'exception_type' => get_class($e),
            ]);

            return response()->json([
                'message' => 'No se pudo completar la recuperación. '
                    . 'No se volvió a emitir el comprobante.',
            ], 500);
        }
    }

    /**
     * Sincroniza el resumen por inventory_level desde los stock_lots.
     */
    protected function syncInventoryLevelFromLots(
        int $stockItemId,
        int $warehouseId,
        int $locationId,
        float $consumedUnits
    ): void {
        app(QuoteStockReservationService::class)->consumeInventoryLevel(
            $stockItemId, $warehouseId, $locationId, $consumedUnits
        );
    }



    public function generateRandomString($length = 25) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function manageNotifications(Material $material)
    {
        $settings = app(SettingService::class);

        $notifyPopup = $settings->get(
            'inventory.stock_notifications.popup'
        );

        $notifyBell = $settings->get(
            'inventory.stock_notifications.bell'
        );

        $notifyEmail = $settings->get(
            'inventory.stock_notifications.email'
        );

        $notifyTelegram = $settings->get(
            'inventory.stock_notifications.telegram'
        );

        // Texto base
        $content = 'El producto '.$material->full_name.' está por agotarse.';

        $nameMaterial = $material->full_name;

        // Obtener usuarios con roles específicos (excepto el actual)
        $users = User::role(['admin', 'principal', 'logistic'])->where('id', '!=', Auth::id())->get();

        if ($notifyBell)
        {
            $notification = Notification::create([
                'content' => $content,
                'reason_for_creation' => 'check_stock',
                'user_id' => Auth::id(),
                'url_go' => route('material.index.store')
            ]);

            foreach ($users as $user) {
                foreach ($user->roles as $role) {
                    NotificationUser::create([
                        'notification_id' => $notification->id,
                        'role_id' => $role->id,
                        'user_id' => $user->id,
                        'read' => false,
                        'date_read' => null,
                        'date_delete' => null
                    ]);
                }
            }
        }

        if ($notifyPopup)
        {
            $notification = Notification::create([
                'content' => $content,
                'reason_for_creation' => 'check_stock_pop_up',
                'user_id' => Auth::id(),
                'url_go' => route('material.index.store')
            ]);

            foreach ($users as $user) {
                foreach ($user->roles as $role) {
                    NotificationUser::create([
                        'notification_id' => $notification->id,
                        'role_id' => $role->id,
                        'user_id' => $user->id,
                        'read' => false,
                        'date_read' => null,
                        'date_delete' => null
                    ]);
                }
            }
        }

        if ($notifyEmail)
        {
            foreach ($users as $user) {
                Mail::to($user->email)->queue(new StockLowNotificationMail($nameMaterial));
            }
        }

        // Si deseas dejar el código preparado para Telegram:
        if ($notifyTelegram)
        {
            $telegram = new TelegramController();

            // Enviar al canal de procesos
            $telegram->sendNotification('📦 El producto XYZ está por agotarse.', 'process');
        }


    }

    public function raiseQuote(Request $request, $quote_id)
    {
        $begin = microtime(true);
        $quote = Quote::find($quote_id);
        // Leer el código enviado desde el popup
        $code = trim($request->input('code_customer', ''));

        if ($code === '') {
            return response()->json(['message' => 'El código de cliente es obligatorio.'], 422);
        }

        DB::beginTransaction();
        try {
            if ( !isset( $quote->order_execution ) )
            {
                $all_quotes = Quote::whereNotNull('order_execution')->get();
                $quantity = count($all_quotes) + 1;
                $length = 5;
                $codeOrderExecution = 'OE-'.str_pad($quantity,$length,"0", STR_PAD_LEFT);
                $quote->order_execution = $codeOrderExecution;
                $quote->save();
            }

            $quote->code_customer = $code;
            $quote->raise_status = true;
            $quote->save();

            // TODO: Guardar el pdf interna en el sistema
            $quote = Quote::where('id', $quote->id)
                ->with('customer')
                ->with('deadline')
                ->with(['equipments' => function ($query) {
                    $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles']);
                }])->first();

            $images = ImagesQuote::where('quote_id', $quote->id)
                ->where('type', 'img')
                ->orderBy('order', 'ASC')->get();

            $view = view('exports.quoteInternal', compact('quote', 'images'));

            $pdf = PDF::loadHTML($view);

            $description = str_replace(array('"', "'", "/"),'',$quote->description_quote);

            $name = $quote->code . ' '. ltrim(rtrim($description)) . '.pdf';

            $image_path = public_path().'/pdfs/quotes/'.$name;
            if (file_exists($image_path)) {
                unlink($image_path);
            }

            $output = $pdf->output();
            file_put_contents(public_path().'/pdfs/quotes/'.$name, $output);

            $pdfPrincipal = public_path().'/pdfs/quotes/'.$name;

            $oMerger = PDFMerger::init();

            $oMerger->addPDF($pdfPrincipal, 'all');

            $pdfs = ImagesQuote::where('quote_id', $quote->id)
                ->where('type', 'pdf')->get();

            foreach ( $pdfs as $pdf )
            {
                $namePdf = public_path().'/images/planos/'.$pdf->image;
                $oMerger->addPDF($namePdf, 'all');
            }

            $oMerger->merge();
            $oMerger->setFileName($name);
            // Guarda el archivo fusionado en la misma carpeta
            $output_path = public_path('pdfs/quotes/' . $name);
            $oMerger->save($output_path);

            // TODO: Guardar los resumenes
            $resumen = ResumenQuote::create([
                'quote_id' => $quote->id,
                'code' => $quote->code,
                'description_quote' => $quote->description_quote,
                'date_quote' => $quote->date_quote,
                'customer_id' => ($quote->customer_id == null) ? null : $quote->customer_id,
                'customer' => ($quote->customer_id == null) ? "" : $quote->customer->business_name,
                'contact_id' => ($quote->contact_id == null) ? null : $quote->contact_id,
                'contact' => ($quote->contact_id == null) ? "" : $quote->contact->name,
                'total_sin_igv' => round(($quote->total_equipments)/1.18, 2),
                'total_con_igv' => round($quote->total_equipments, 2),
                'total_utilidad_sin_igv' => round(($quote->total_quote)/1.18, 2),
                'total_utilidad_con_igv' => round($quote->total_quote, 2),
                'path_pdf' => $name
            ]);

            foreach ( $quote->equipments as $equipment )
            {
                $resumenEquipment = ResumenEquipment::create([
                    'resumen_quote_id' => $resumen->id,
                    'equipment_id' => $equipment->id,
                    'description' => $equipment->description,
                    'total_materials' => $equipment->total_materials,
                    'total_consumables' => $equipment->total_consumables,
                    'total_electrics' => $equipment->total_electrics,
                    'total_workforces' => $equipment->total_workforces,
                    'total_turnstiles' => $equipment->total_turnstiles,
                    'total_workdays' => $equipment->total_workdays,
                    'quantity' => $equipment->quantity,
                    'total' => round($equipment->subtotal_percentage/1.18, 2),
                    'utility' => $equipment->utility,
                    'letter' => $equipment->letter,
                    'rent' => $equipment->rent
                ]);
            }

            // TODO: Actualizar los stocks
            $equipment_materials = $quote->equipments;
            foreach ( $equipment_materials as $equipment )
            {
                $quote_materials = $equipment->consumables;
                foreach ( $quote_materials as $consumable )
                {
                    $mat = Material::find($consumable->material_id);
                    $quantity = $consumable->quantity;
                    $mat->stock_current = $mat->stock_current - $quantity;
                    $mat->save();
                }
            }


            // Crear notificacion
            $notification = Notification::create([
                'content' => $quote->code.' elevada por '.Auth::user()->name,
                'reason_for_creation' => 'raise_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('quote.raise', $quote->id)
            ]);

            // Roles adecuados para recibir esta notificación admin, logistica
            $users = User::role(['admin', 'principal' , 'logistic' , 'finance'])->get();
            foreach ( $users as $user )
            {
                if ( $user->id != Auth::user()->id )
                {
                    foreach ( $user->roles as $role )
                    {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false,
                            'date_read' => null,
                            'date_delete' => null
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Elevar cotizacion',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Cotización elevada.'], 200);
    }

    public function printQuoteToCustomer($id)
    {
        // Eliminamos elos archivos
        $files = glob(public_path().'/pdfs/*');
        foreach($files as $file){
            if(is_file($file))
                unlink($file);
        }

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with('users')
            ->with(['equipments' => function ($query) {
                $query->with([
                    'materials',
                    'consumables.material',
                    'consumables.stockItem',
                    'consumables.quoteStockLots',
                    'workforces',
                    'turnstiles'
                ]);
            }])->first();

        $quoteItemIdsByConsumable = [];
        $allItemIds = [];

        foreach ($quote->equipments as $equipment) {
            foreach ($equipment->consumables as $consumable) {
                $itemIds = [];

                foreach ($consumable->quoteStockLots as $quoteStockLot) {
                    $rawItemIds = $quoteStockLot->item_ids ?? [];

                    if (is_string($rawItemIds)) {
                        $decodedItemIds = json_decode($rawItemIds, true);
                        $rawItemIds = is_array($decodedItemIds) ? $decodedItemIds : [];
                    }

                    if (!is_array($rawItemIds)) {
                        $rawItemIds = [];
                    }

                    foreach ($rawItemIds as $itemId) {
                        $itemId = (int) $itemId;

                        if ($itemId > 0) {
                            $itemIds[] = $itemId;
                            $allItemIds[] = $itemId;
                        }
                    }
                }

                $quoteItemIdsByConsumable[$consumable->id] = array_values(array_unique($itemIds));
            }
        }

        $itemsById = Item::whereIn('id', array_values(array_unique($allItemIds)))
            ->get(['id', 'code'])
            ->keyBy('id');

        $quoteItemCodesByConsumable = [];

        foreach ($quoteItemIdsByConsumable as $consumableId => $itemIds) {
            $codes = [];

            foreach ($itemIds as $itemId) {
                $item = $itemsById->get($itemId);

                if ($item) {
                    $codes[] = $item->code ?: ('Ítem #' . $item->id);
                }
            }

            $quoteItemCodesByConsumable[$consumableId] = $codes;
        }

        /*dump($quoteItemCodesByConsumable);
        dd();*/


        $images = ImagesQuote::where('quote_id', $quote->id)
            ->where('type', 'img')
            ->orderBy('order', 'ASC')->get();

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        $monedaTexto = $quote->currency_invoice === 'USD'
            ? 'DÓLARES'
            : 'SOLES';

        $total_workforce  = 0;
        foreach($quote->equipments as $equipment)
        {
            foreach($equipment->workforces as $workforce)
            {
                if ( $workforce->billable == false )
                {
                    $total_workforce = $total_workforce + $workforce->total;
                }

            }
        }

        $montoEnLetras = numeroALetras($quote->total_importe+$total_workforce, $monedaTexto);

        $company = TenantContext::company();

        $nombreEmpresa = $company->business_name;
        $direccionEmpresa = $company->address;
        $telefonoEmpresa = $company->phone;
        $emailEmpresa = $company->email;
        $rucEmpresa = $company->ruc;

        $settings = app(SettingService::class);

        $webEmpresa = $settings->get('company.profile.website');

        $logotipoEmpresa = $settings->get('company.branding.logo');

        $versiculoEmpresa = $settings->get('company.branding.quote_text');

        $citaBiblicaEmpresa = $settings->get('company.branding.quote_reference');

        $companyBankAccounts = CompanyBankAccount::query()
            ->with('bank')
            ->where(
                'company_id',
                TenantContext::companyId()
            )
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $tieneCuentas = $companyBankAccounts->isNotEmpty();

        $view = view('exports.quoteSaleCustomerV2', compact(
            'logotipoEmpresa',
            'versiculoEmpresa',
            'citaBiblicaEmpresa',
            'companyBankAccounts',
            'tieneCuentas',
            'rucEmpresa','webEmpresa','emailEmpresa','telefonoEmpresa','direccionEmpresa','nombreEmpresa', 'quote', 'images', 'igv', 'montoEnLetras', 'quoteItemCodesByConsumable'));

        $pdf = PDF::loadHTML($view);

        $description = str_replace(array('"', "'", "/"),'',$quote->description_quote);

        $name = $quote->code . ' '. ltrim(rtrim($description)) . '.pdf';

        $image_path = public_path().'/pdfs/'.$name;
        //$image_path = 'C:/wamp64/www/construction/public/pdfs/'.$name;
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        // = $pdf->output();

        //file_put_contents(public_path().'/pdfs/'.$name, $output);
        //file_put_contents('C:/wamp64/www/construction/public/pdfs/'.$name, $output);
        $pdfPrincipal = public_path().'/pdfs/'.$name;
        //$pdfPrincipal = 'C:/wamp64/www/construction/public/pdfs/'.$name;
        //$oMerger = PDFMerger::init();

        //$oMerger->addPDF($pdfPrincipal, 'all');

        /*$pdfs = ImagesQuote::where('quote_id', $quote->id)
            ->where('type', 'pdf')->get();

        foreach ( $pdfs as $pdf )
        {
            $namePdf = public_path().'/images/planos/'.$pdf->image;
            //$namePdf ='C:/wamp64/www/construction/public/images/planos/'.$pdf->image;
            $oMerger->addPDF($namePdf, 'all');
        }*/

        //$oMerger->merge();
        //$oMerger->setFileName($name);
        //$oMerger->stream();

        return $pdf->stream($name);
    }

    public function show($id)
    {
        $begin = microtime(true);
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();
        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->where('category_id', 2)->where('description','LIKE',"%".$defaultConsumable."%")->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();
        $workforces = Workforce::with('unitMeasure')->get();
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->get();
        $utility = PorcentageQuote::where('name', 'utility')->first();
        $rent = PorcentageQuote::where('name', 'rent')->first();
        $letter = PorcentageQuote::where('name', 'letter')->first();

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with([
                    'materials',
                    'consumables.material',
                    'consumables.stockItem',
                    'consumables.quoteStockLots',
                    'electrics',
                    'workforces',
                    'turnstiles',
                    'workdays'
                ]);
            }])->first();

        $quoteItemIdsByConsumable = [];
        $allItemIds = [];

        foreach ($quote->equipments as $equipment) {
            foreach ($equipment->consumables as $consumable) {
                $itemIds = [];

                foreach ($consumable->quoteStockLots as $quoteStockLot) {
                    $rawItemIds = $quoteStockLot->item_ids ?? [];

                    if (is_string($rawItemIds)) {
                        $decodedItemIds = json_decode($rawItemIds, true);
                        $rawItemIds = is_array($decodedItemIds) ? $decodedItemIds : [];
                    }

                    if (!is_array($rawItemIds)) {
                        $rawItemIds = [];
                    }

                    foreach ($rawItemIds as $itemId) {
                        $itemId = (int) $itemId;

                        if ($itemId > 0) {
                            $itemIds[] = $itemId;
                            $allItemIds[] = $itemId;
                        }
                    }
                }

                $quoteItemIdsByConsumable[$consumable->id] = array_values(array_unique($itemIds));
            }
        }

        $itemsById = Item::whereIn('id', array_values(array_unique($allItemIds)))
            ->get(['id', 'code'])
            ->keyBy('id');

        $quoteItemCodesByConsumable = [];

        foreach ($quoteItemIdsByConsumable as $consumableId => $itemIds) {
            $codes = [];

            foreach ($itemIds as $itemId) {
                $item = $itemsById->get($itemId);

                if ($item && !empty($item->code)) {
                    $codes[] = $item->code;
                }
            }

            $quoteItemCodesByConsumable[$consumableId] = $codes;
        }

        $images = [];

        $currency = app(SettingService::class)->get('finance.base_currency');

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Ver cotizacion venta VISTA',
            'time' => $end
        ]);

        return view('quoteSale.show', compact(
            'quote',
            'unitMeasures',
            'customers',
            'consumables',
            'electrics',
            'workforces',
            'permissions',
            'paymentDeadlines',
            'utility',
            'rent',
            'letter',
            'images',
            'currency',
            'igv',
            'quoteItemCodesByConsumable'
        ));

    }

    public function confirm(Request $request, $quote)
    {
        app(QuoteSaleActions::class)->change($request, (int) $quote, 'confirm');
        return response()->json(['message' => 'Cotización confirmada correctamente.']);
    }

    public function send(Request $request, $quote)
    {
        app(QuoteSaleActions::class)->change($request, (int) $quote, 'send');
        return response()->json(['message' => 'Cotización marcada como enviada.']);
    }

    public function closeQuote(Request $request, $quote)
    {
        app(QuoteSaleActions::class)->change($request, (int) $quote, 'close');
        return response()->json(['message' => 'Cotización cerrada correctamente.']);
    }

    public function activeQuote(Request $request, $quote)
    {
        app(QuoteSaleActions::class)->change($request, (int) $quote, 'reopen');
        return response()->json(['message' => 'Cotización reabierta correctamente.']);
    }

    public function getDecimalsQuote($quote)
    {
        return response()->json(app(QuoteSaleActions::class)->read((int) $quote, 'decimals'));
    }

    public function changeDecimalsQuote(Request $request)
    {
        $data = $request->validate(['quote_id' => 'required|integer|min:1', 'decimals' => 'required|in:0,1']);
        app(QuoteSaleActions::class)->change($request, (int) $data['quote_id'], 'decimals', $data['decimals']);
        return response()->json(['message' => 'Visualización de decimales guardada correctamente.']);
    }


}
