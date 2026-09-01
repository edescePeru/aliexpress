<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Category;
use App\CategoryInvoice;
use App\Color;
use App\DataGeneral;
use App\DiscountQuantity;
use App\Exampler;
use App\Genero;
use App\Http\Requests\DeleteMaterialRequest;
use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\InventoryLevel;
use App\Location;
use App\Material;
use App\MaterialDetailSetting;
use App\MaterialDiscountQuantity;
use App\MaterialType;
use App\MaterialUnpack;
use App\MaterialVencimiento;
use App\PriceList;
use App\PriceListItem;
use App\Quality;
use App\Services\InventoryCostService;
use App\Shelf;
use App\Specification;
use App\Item;
use APP\DetailEntry;
use App\StockItem;
use App\StoreMaterial;
use App\StoreMaterialLocation;
use App\StoreMaterialVencimiento;
use App\Subcategory;
use App\Subtype;
use App\Talla;
use App\TipoVenta;
use App\Typescrap;
use App\UnitMeasure;
use App\Variant;
use App\Warehouse;
use App\Warrant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use App\Support\TenantContext;
use App\CompanyStockItem;
use App\Services\Inventory\DefaultInventoryLocationResolver;

class MaterialController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('material.index', compact('permissions'));
    }

    public function listarActivosFijos()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('material.listarActivosIndex', compact('permissions'));
    }

    public function create()
    {
        /*
         * ============================================================
         * CONFIGURACIÓN DEL FORMULARIO PARA LA COMPANY ACTUAL
         * ============================================================
         */

        $setting = MaterialDetailSetting::query()
            ->forCompany(
                TenantContext::companyId()
            )
            ->first();

        $enabled = [];

        if (
            $setting &&
            is_array($setting->enabled_sections)
        ) {
            $enabled = $setting->enabled_sections;
        }


        /*
         * ============================================================
         * CATÁLOGOS SIEMPRE UTILIZADOS
         * ============================================================
         */

        $tipoVentas = TipoVenta::orderBy(
            'description',
            'asc'
        )->get();

        $discountQuantities =
            DiscountQuantity::all();


        /*
         * ============================================================
         * CATÁLOGOS CONDICIONADOS POR MaterialDetailSetting
         * ============================================================
         */

        $categories =
            in_array(
                'category',
                $enabled,
                true
            )
                ? Category::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $brands =
            in_array(
                'brand',
                $enabled,
                true
            )
                ? Brand::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $unitMeasures =
            in_array(
                'unit_measure',
                $enabled,
                true
            )
                ? UnitMeasure::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $generos =
            in_array(
                'genero',
                $enabled,
                true
            )
                ? Genero::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $tallas =
            in_array(
                'talla',
                $enabled,
                true
            )
                ? Talla::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $colors =
            in_array(
                'color',
                $enabled,
                true
            )
                ? Color::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $typescraps =
            in_array(
                'typescrap',
                $enabled,
                true
            )
                ? Typescrap::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        /*
         * No cargamos Subcategories, MaterialTypes ni Subtypes
         * inicialmente.
         *
         * Se cargarán por AJAX según:
         *
         * Category
         * → Subcategory
         * → MaterialType
         * → Subtype
         */


        return view(
            'material.create',
            compact(
                'enabled',
                'discountQuantities',
                'tipoVentas',
                'tallas',
                'generos',
                'categories',
                'brands',
                'typescraps',
                'unitMeasures',
                'colors'
            )
        );
    }

    public function store(StoreMaterialRequest $request)
    {
        DB::beginTransaction();

        try {

            /*
             * ============================================================
             * CONTEXTO OPERATIVO
             * ============================================================
             */

            $tenantId = TenantContext::tenantId();
            $companyId = TenantContext::companyId();

            /** @var DefaultInventoryLocationResolver $inventoryLocationResolver */
            $inventoryLocationResolver =
                app(DefaultInventoryLocationResolver::class);

            $defaultInventoryLocation = $inventoryLocationResolver->resolveCurrent();

            $defaultWarehouse = $defaultInventoryLocation['warehouse'];

            $defaultLocation = $defaultInventoryLocation['location'];

            /*
             * ============================================================
             * CONFIGURACIÓN GENERAL
             * ============================================================
             */

            // 0 = sin variantes
            // 1 = con variantes
            $tipoVariantes = (int) $request->input(
                'tipo_variantes',
                0
            );

            $variantes = json_decode(
                $request->input(
                    'variantes_json',
                    '[]'
                ),
                true
            );

            if (!is_array($variantes)) {
                throw new \Exception(
                    'El formato de variantes_json es inválido.'
                );
            }

            if (count($variantes) === 0) {
                throw new \Exception(
                    'Debe enviar al menos un registro de variante.'
                );
            }


            /*
             * ============================================================
             * MATERIAL
             * ============================================================
             *
             * tenant_id será asignado automáticamente por BelongsToTenant.
             */

            $material = Material::create([
                'description' => $request->input('description'),

                'unit_measure_id' => $request->input('unit_measure'),

                'stock_max' => 0,
                'stock_min' => 0,
                'stock_current' => 0,
                'stock_reserved' => 0,

                'priority' => 'Aceptable',

                'unit_price' => $request->input('unit_price', 0),

                'category_id' => $request->input('category'),

                'subcategory_id' => $request->input('subcategory'),

                'material_type_id' =>$request->input('material_type'),

                'subtype_id' => $request->input('subtype'),

                'brand_id' => $request->input('brand'),

                'exampler_id' => $request->input('exampler'),

                'typescrap_id' => $request->input('typescrap'),

                /*
                 * Género correcto.
                 *
                 * Antes se guardaba erróneamente
                 * en warrant_id.
                 */
                'genero_id' => $request->input('genero'),

                'enable_status' => 1,

                'full_name' => $request->input('name'),

                'tipo_venta_id' => $request->input('tipo_venta'),

                'perecible' => $request->input('perecible'),

                'type_tax_id' => $request->input('type_tax_id'),

                'list_price' => (float) $request->input('unit_price',0),

                'inventory' => 0,

                'image' => 'no_image.png',

                /*
                 * Campos legacy.
                 * Las variantes nuevas ya trabajan
                 * con variants.talla_id.
                 */
                'quality_id' => null,

                'codigo' => null,

                'isPack' => 0,

                'quantityPack' => 0,

                'stock_unPack' => 0,
            ]);


            /*
             * ============================================================
             * CÓDIGO INTERNO MATERIAL
             * ============================================================
             */

            $material->code =
                'P-' .
                str_pad(
                    $material->id,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

            $material->save();


            /*
             * ============================================================
             * IMAGEN GENERAL
             * ============================================================
             */

            if ($request->hasFile('image')) {

                $image =
                    $request->file('image');

                $filename =
                    $material->id .
                    '.' .
                    $image->getClientOriginalExtension();

                $path =
                    public_path(
                        'images/material/' .
                        $filename
                    );

                Image::make($image)
                    ->save($path);

                $material->image =
                    $filename;

                $material->save();
            }


            /*
             * ============================================================
             * VARIANTS + STOCK ITEMS
             * ============================================================
             */

            foreach ($variantes as $index => $item) {

                $tallaId = $item['talla_id'] ?? null;

                $colorId = $item['color_id'] ?? null;

                $sku = trim($item['sku'] ?? '');

                $barcode = trim($item['codigo_barras'] ?? '' );

                $stockMinimo = ($item['stock_minimo'] ?? '') !== '' ? (float) $item['stock_minimo'] : 0;

                $stockMaximo = ($item['stock_maximo'] ?? '') !== '' ? (float) $item['stock_maximo'] : 0;

                $isActive = isset($item['is_active']) ? (int) $item['is_active'] : 1;

                $tracksInventory = isset($item['afecto_inventario']) ? (int) $item['afecto_inventario'] : 1;

                $isPack = isset($item['pack']) ? (int) $item['pack'] : 0;

                $cantidadPack = isset($item['cantidad_pack']) ? (float) $item['cantidad_pack'] : 1;

                $imageKey = $item['image_key'] ?? null;

                if ($sku === '') {
                    throw new \Exception('Uno de los registros no tiene SKU.');
                }


                $variantId = null;

                $displayName = $material->full_name;

                /*
                 * ========================================================
                 * PRODUCTO CON VARIANTES
                 * ========================================================
                 */

                if ($tipoVariantes === 1) {

                    /*
                     * Talla y Color ya están TenantScope.
                     */
                    $talla = $tallaId ? Talla::find($tallaId) : null;

                    $color = $colorId ? Color::find($colorId) : null;

                    /*
                     * Si se envió un ID pero no pertenece
                     * al Tenant actual, no continuamos.
                     */

                    if ($tallaId && !$talla) {
                        throw new \Exception(
                            'Una de las tallas seleccionadas no pertenece al grupo empresarial actual.'
                        );
                    }

                    if ($colorId && !$color) {
                        throw new \Exception(
                            'Uno de los colores seleccionados no pertenece al grupo empresarial actual.'
                        );
                    }


                    $tallaTexto =
                        $talla
                            ? (
                        $talla->short_name
                            ?: $talla->name
                        )
                            : '';

                    $colorTexto =
                        $color
                            ? $color->name
                            : '';


                    $attributeSummary =
                        collect([
                            $tallaTexto,
                            $colorTexto,
                        ])
                            ->filter()
                            ->implode(' / ');


                    $displayName =
                        trim(
                            $material->full_name .
                            ' - ' .
                            collect([
                                $tallaTexto,
                                $colorTexto,
                            ])
                                ->filter()
                                ->implode(' - ')
                        );


                    /*
                     * Imagen propia de variante.
                     */

                    $variantImageName = null;

                    if (
                        $imageKey &&
                        $request->hasFile($imageKey)
                    ) {

                        $variantImage =
                            $request->file(
                                $imageKey
                            );

                        $variantImageName =
                            'variant_' .
                            $material->id .
                            '_' .
                            uniqid() .
                            '.' .
                            $variantImage
                                ->getClientOriginalExtension();

                        $variantPath =
                            public_path(
                                'images/material/variants/' .
                                $variantImageName
                            );

                        Image::make(
                            $variantImage
                        )->save(
                            $variantPath
                        );
                    }


                    /*
                     * Variant es Tenant-level.
                     *
                     * tenant_id se asigna mediante
                     * BelongsToTenant.
                     */

                    $variant =
                        Variant::create([
                            'material_id' => $material->id,

                            /*
                             * Ya NO usamos quality_id.
                             */
                            'talla_id' => $tallaId,

                            'color_id' => $colorId,

                            'attribute_summary' => $attributeSummary,

                            'image' => $variantImageName,

                            'is_active' => $isActive,
                        ]);

                    $variantId =
                        $variant->id;
                }


                /*
                 * ========================================================
                 * PRODUCTO SIN VARIANTES
                 * ========================================================
                 */

                else {

                    /*
                     * Material no necesita almacenar
                     * talla/color para producto simple.
                     *
                     * El StockItem representa directamente
                     * la unidad vendible.
                     */

                    $material->update([
                        'codigo' =>
                            $barcode !== ''
                                ? $barcode
                                : null,

                        'stock_min' =>
                            $stockMinimo,

                        'stock_max' =>
                            $stockMaximo,

                        'isPack' =>
                            $isPack,

                        'quantityPack' =>
                            $isPack
                                ? $cantidadPack
                                : 0,
                    ]);
                }


                /*
                 * ========================================================
                 * STOCK ITEM
                 * ========================================================
                 *
                 * StockItem también es Tenant-level.
                 * BelongsToTenant asigna tenant_id.
                 */

                $stockItem =
                    StockItem::create([
                        'material_id' =>
                            $material->id,

                        'variant_id' =>
                            $variantId,

                        'sku' =>
                            $sku,

                        'barcode' =>
                            $barcode !== ''
                                ? $barcode
                                : null,

                        'display_name' =>
                            $displayName,

                        'unit_measure_id' =>
                            $material->unit_measure_id,

                        'tracks_inventory' =>
                            $tracksInventory,

                        'is_active' =>
                            $isActive,
                    ]);


                /*
                 * ========================================================
                 * HABILITACIÓN COMERCIAL PARA COMPANY ACTUAL
                 * ========================================================
                 *
                 * Esta es la parte nueva de 5E-4B.
                 */

                CompanyStockItem::create([
                    'company_id' =>
                        $companyId,

                    'stock_item_id' =>
                        $stockItem->id,

                    'is_active' =>
                        true,
                ]);


                /*
                 * ========================================================
                 * INVENTORY LEVEL
                 * ========================================================
                 *
                 */

                InventoryLevel::create([
                    'company_id' => $companyId,

                    'stock_item_id' => $stockItem->id,

                    'location_id' => $defaultLocation->id,

                    'warehouse_id' => $defaultWarehouse->id,

                    'qty_on_hand' => 0,

                    'qty_reserved' => 0,

                    'min_alert' => $stockMinimo,

                    'max_alert' => $stockMaximo,

                    'average_cost' => 0,

                    'last_cost' => 0,
                ]);
            }


            /*
             * ============================================================
             * DESCUENTOS POR CANTIDAD
             * ============================================================
             */

            $discounts =
                $request->input(
                    'discount',
                    []
                );

            $percentages =
                $request->input(
                    'percentage',
                    []
                );

            foreach ( $discounts as $discountId => $value ) {

                if (isset($value)) {

                    $percentage =
                        $percentages[
                        $discountId
                        ] ?? null;

                    MaterialDiscountQuantity::create([
                        'material_id' =>
                            $material->id,

                        'discount_quantity_id' =>
                            $discountId,

                        'percentage' =>
                            $percentage,
                    ]);
                }
            }


            DB::commit();


            return response()->json([
                'message' =>
                    'Material guardado con éxito.',
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

    public function show(Material $material)
    {
        //
    }

    public function edit($id)
    {
        /*
         * ============================================================
         * CONFIGURACIÓN MATERIAL DE LA COMPANY ACTUAL
         * ============================================================
         */

        $setting =
            MaterialDetailSetting::query()
                ->forCompany(
                    TenantContext::companyId()
                )
                ->first();

        $enabled = [];

        if (
            $setting &&
            is_array(
                $setting->enabled_sections
            )
        ) {
            $enabled =
                $setting->enabled_sections;
        }


        /*
         * ============================================================
         * MATERIAL
         * ============================================================
         */

        $material =
            Material::with([
                'category',
                'subcategory',
                'materialType',
                'subType',
                'brand',
                'exampler',
                'genero',

                'variants.talla',
                'variants.color',
                'variants.stockItem',

                'stockItems',
            ])
                ->findOrFail(
                    $id
                );


        /*
         * ============================================================
         * LISTAS GENERALES
         * ============================================================
         */

        $tipoVentas =
            TipoVenta::orderBy(
                'description',
                'asc'
            )->get();

        $discountQuantities =
            DiscountQuantity::all();


        /*
         * ============================================================
         * CATÁLOGOS SEGÚN MaterialDetailSetting
         * ============================================================
         */

        $brands =
            in_array(
                'brand',
                $enabled,
                true
            )
                ? Brand::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $categories =
            in_array(
                'category',
                $enabled,
                true
            )
                ? Category::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $unitMeasures =
            in_array(
                'unit_measure',
                $enabled,
                true
            )
                ? UnitMeasure::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $generos =
            in_array(
                'genero',
                $enabled,
                true
            )
                ? Genero::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $tallas =
            in_array(
                'talla',
                $enabled,
                true
            )
                ? Talla::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $colors =
            in_array(
                'color',
                $enabled,
                true
            )
                ? Color::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        $typescraps =
            in_array(
                'typescrap',
                $enabled,
                true
            )
                ? Typescrap::orderBy(
                'name',
                'asc'
            )->get()
                : collect();


        /*
         * ============================================================
         * BRAND → EXAMPLER
         * ============================================================
         */

        $examplers =
            collect();

        if (
            in_array(
                'exampler',
                $enabled,
                true
            ) &&
            $material->brand_id
        ) {

            $examplers =
                Exampler::query()
                    ->where(
                        'brand_id',
                        $material->brand_id
                    )
                    ->orderBy(
                        'name',
                        'asc'
                    )
                    ->get();
        }


        /*
         * ============================================================
         * CATEGORY → SUBCATEGORY
         * ============================================================
         */

        $subcategories =
            collect();

        if (
            in_array(
                'subcategory',
                $enabled,
                true
            ) &&
            $material->category_id
        ) {

            $subcategories =
                Subcategory::query()
                    ->where(
                        'category_id',
                        $material->category_id
                    )
                    ->orderBy(
                        'name',
                        'asc'
                    )
                    ->get();
        }


        /*
         * ============================================================
         * SUBCATEGORY → MATERIAL TYPE
         * ============================================================
         */

        $materialTypes =
            collect();

        if (
            in_array(
                'material_type',
                $enabled,
                true
            ) &&
            $material->subcategory_id
        ) {

            $materialTypes =
                MaterialType::query()
                    ->where(
                        'subcategory_id',
                        $material->subcategory_id
                    )
                    ->orderBy(
                        'name',
                        'asc'
                    )
                    ->get();
        }


        /*
         * ============================================================
         * MATERIAL TYPE → SUBTYPE
         * ============================================================
         */

        $subtypes =
            collect();

        if (
            in_array(
                'subtype',
                $enabled,
                true
            ) &&
            $material->material_type_id
        ) {

            $subtypes =
                Subtype::query()
                    ->where(
                        'material_type_id',
                        $material->material_type_id
                    )
                    ->orderBy(
                        'name',
                        'asc'
                    )
                    ->get();
        }


        /*
         * ============================================================
         * DESCUENTOS
         * ============================================================
         */

        $materialsDiscounts =
            MaterialDiscountQuantity::where(
                'material_id',
                $material->id
            )
                ->get()
                ->keyBy(
                    'discount_quantity_id'
                )
                ->map(function ($item) {

                    return $item->percentage;
                })
                ->toArray();


        /*
         * ============================================================
         * VARIANTES
         * ============================================================
         */

        $tieneVariantes =
            $material
                ->variants
                ->isNotEmpty();


        $variantesEdit = [];


        if ($tieneVariantes) {

            $variantesEdit =
                $material->variants
                    ->map(function ($variant) {

                        $stockItem =
                            $variant->stockItem;

                        return [
                            'variant_id' =>
                                $variant->id,

                            /*
                             * Talla nueva/correcta.
                             */
                            'talla_id' =>
                                $variant->talla_id,

                            'talla_text' =>
                                optional(
                                    $variant->talla
                                )->name,

                            'talla_short_name' =>
                                optional(
                                    $variant->talla
                                )->short_name,

                            'color_id' =>
                                $variant->color_id,

                            'color_text' =>
                                optional(
                                    $variant->color
                                )->name,

                            'color_short_name' =>
                                optional(
                                    $variant->color
                                )->short_name,

                            'attribute_summary' =>
                                $variant->attribute_summary,

                            'image' =>
                                $variant->image,

                            'is_active' =>
                                (int) $variant->is_active,

                            'tracks_inventory' =>
                                optional(
                                    $stockItem
                                )->tracks_inventory,

                            'sku' =>
                                optional(
                                    $stockItem
                                )->sku,

                            'barcode' =>
                                optional(
                                    $stockItem
                                )->barcode,

                            'display_name' =>
                                optional(
                                    $stockItem
                                )->display_name,

                            /*
                             * SOLO Company actual.
                             */
                            'inventory_levels' =>
                                $this->mapInventoryLevels(
                                    $stockItem
                                ),
                        ];
                    })
                    ->values()
                    ->toArray();

        } else {

            /*
             * Producto simple:
             * un único StockItem sin Variant.
             */

            $stockItem =
                $material->stockItems
                    ->whereNull(
                        'variant_id'
                    )
                    ->first();

            $variantesEdit = [[
                'variant_id' =>
                    null,

                'stock_item_id' =>
                    optional(
                        $stockItem
                    )->id,

                /*
                 * Producto simple no usa talla/color.
                 */
                'talla_id' =>
                    null,

                'talla_text' =>
                    null,

                'talla_short_name' =>
                    null,

                'color_id' =>
                    null,

                'color_text' =>
                    null,

                'color_short_name' =>
                    null,

                'attribute_summary' =>
                    null,

                'image' =>
                    $material->image,

                'is_active' =>
                    optional(
                        $stockItem
                    )->is_active,

                'tracks_inventory' =>
                    optional(
                        $stockItem
                    )->tracks_inventory,

                'sku' =>
                    optional(
                        $stockItem
                    )->sku,

                'barcode' =>
                    optional(
                        $stockItem
                    )->barcode,

                'display_name' =>
                    optional(
                        $stockItem
                    )->display_name,

                'inventory_levels' =>
                    $this->mapInventoryLevels(
                        $stockItem
                    ),
            ]];
        }


        return view(
            'material.edit',
            compact(
                'enabled',
                'materialsDiscounts',
                'discountQuantities',
                'generos',
                'tallas',
                'tipoVentas',
                'unitMeasures',
                'typescraps',
                'brands',
                'categories',
                'materialTypes',
                'subtypes',
                'material',
                'examplers',
                'subcategories',
                'colors',
                'tieneVariantes',
                'variantesEdit'
            )
        );
    }

    private function mapInventoryLevels($stockItem)
    {
        if (!$stockItem) {
            return [];
        }

        $companyId =
            TenantContext::companyId();

        $levels =
            $stockItem->inventoryLevels()
                ->where(
                    'company_id',
                    $companyId
                )
                ->with([
                    'warehouse',
                    'location',
                ])
                ->orderBy(
                    'warehouse_id'
                )
                ->get();

        return $levels
            ->map(function ($level) {

                return [
                    'inventory_level_id' =>
                        $level->id,

                    'warehouse_id' =>
                        $level->warehouse_id,

                    'warehouse_name' =>
                        optional(
                            $level->warehouse
                        )->name,

                    'location_id' =>
                        $level->location_id,

                    'location_name' =>
                        optional(
                            $level->location
                        )->full_location,

                    'qty_on_hand' =>
                        (float) $level->qty_on_hand,

                    'qty_reserved' =>
                        (float) $level->qty_reserved,

                    'min_alert' =>
                        (float) $level->min_alert,

                    'max_alert' =>
                        (float) $level->max_alert,

                    'average_cost' =>
                        (float) $level->average_cost,

                    'last_cost' =>
                        (float) $level->last_cost,
                ];
            })
            ->values()
            ->toArray();
    }

    public function update(UpdateMaterialRequest $request)
    {
        DB::beginTransaction();

        try {

            /*
             * ============================================================
             * CONTEXTO OPERATIVO
             * ============================================================
             */

            $companyId =
                TenantContext::companyId();


            /*
             * ============================================================
             * MATERIAL
             * ============================================================
             *
             * UpdateMaterialRequest ya validó que material_id
             * pertenezca al Tenant actual.
             *
             * lockForUpdate evita que dos actualizaciones simultáneas
             * modifiquen el mismo Material.
             */

            $material =
                Material::with([
                    'variants.stockItem',
                    'stockItems',
                ])
                    ->where(
                        'id',
                        $request->input('material_id')
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


            /*
             * ============================================================
             * TIPO DE PRODUCTO
             * ============================================================
             *
             * 0 = producto simple
             * 1 = producto con variantes
             *
             * UpdateMaterialRequest ya impide:
             *
             * simple -> variantes
             * variantes -> simple
             */

            $tipoVariantes =
                (int) $request->input(
                    'tipo_variantes',
                    0
                );


            /*
             * ============================================================
             * VARIANTES JSON
             * ============================================================
             */

            $variantes =
                json_decode(
                    $request->input(
                        'variantes_json',
                        '[]'
                    ),
                    true
                );


            if (!is_array($variantes)) {

                throw new \RuntimeException(
                    'El formato de variantes_json es inválido.'
                );
            }


            if (count($variantes) === 0) {

                throw new \RuntimeException(
                    'Debe enviar al menos un registro.'
                );
            }


            /*
             * ============================================================
             * VALORES ANTERIORES
             * ============================================================
             */

            $oldTypescrapId =
                $material->typescrap_id;

            $oldUnitPrice =
                $material->unit_price;


            /*
             * ============================================================
             * ACTUALIZAR MATERIAL PADRE
             * ============================================================
             */

            $material->full_name =
                $request->input('name');

            $material->description =
                $request->input('description');

            $material->unit_measure_id =
                $request->input('unit_measure');

            $material->unit_price =
                $request->input(
                    'unit_price',
                    0
                );

            $material->category_id =
                $request->input('category');

            $material->subcategory_id =
                $request->input('subcategory');

            /*
             * Jerarquía nueva:
             *
             * Category
             * -> Subcategory
             * -> MaterialType
             * -> Subtype
             */
            $material->material_type_id =
                $request->input(
                    'material_type'
                );

            $material->subtype_id =
                $request->input(
                    'subtype'
                );


            $material->brand_id =
                $request->input('brand');

            $material->exampler_id =
                $request->input('exampler');

            $material->typescrap_id =
                $request->input('typescrap');


            /*
             * Género correcto.
             *
             * YA NO usamos warrant_id.
             */
            $material->genero_id =
                $request->input('genero');


            $material->tipo_venta_id =
                $request->input(
                    'tipo_venta'
                );

            $material->perecible =
                $request->input(
                    'perecible'
                );

            $material->type_tax_id =
                $request->input(
                    'type_tax_id'
                );


            /*
             * Legacy temporal.
             *
             * Pricing se rediseñará posteriormente.
             */
            $material->list_price =
                (float) $request->input(
                    'unit_price',
                    0
                );


            /*
             * Talla ya no pertenece al Material.
             *
             * Producto simple:
             * Material -> StockItem
             *
             * Producto con variantes:
             * Material -> Variant -> StockItem
             */
            $material->quality_id =
                null;


            /*
             * Para productos con variantes estos campos
             * legacy no representan al Material padre.
             */
            if ($tipoVariantes === 1) {

                $material->codigo =
                    null;

                $material->stock_min =
                    0;

                $material->stock_max =
                    0;

                $material->isPack =
                    0;

                $material->quantityPack =
                    0;
            }


            $material->save();


            /*
             * ============================================================
             * IMAGEN GENERAL DEL MATERIAL
             * ============================================================
             */

            if ($request->hasFile('image')) {

                $image =
                    $request->file('image');

                $filename =
                    $material->id .
                    '.' .
                    $image->getClientOriginalExtension();

                $path =
                    public_path(
                        'images/material/' .
                        $filename
                    );


                Image::make(
                    $image
                )->save(
                    $path
                );


                $material->image =
                    $filename;

                $material->save();

            } elseif (!$material->image) {

                $material->image =
                    'no_image.png';

                $material->save();
            }


            /*
             * ============================================================
             * REGLA LEGACY DE RETACERÍA
             * ============================================================
             */

            if (
                $oldTypescrapId !=
                $material->typescrap_id &&
                $material->typescrap_id
            ) {

                $typeScrap =
                    Typescrap::findOrFail(
                        $material->typescrap_id
                    );


                $items =
                    Item::where(
                        'material_id',
                        $material->id
                    )
                        ->whereIn(
                            'state_item',
                            [
                                'entered',
                                'exited',
                            ]
                        )
                        ->get();


                foreach ($items as $item) {

                    $item->length =
                        (float) $typeScrap->length;

                    $item->width =
                        (float) $typeScrap->width;

                    $item->typescrap_id =
                        $typeScrap->id;

                    $item->save();
                }
            }


            /*
             * ============================================================
             * CONTROL LEGACY DE CAMBIO DE PRECIO
             * ============================================================
             */

            if (
                (float) $oldUnitPrice !==
                (float) $material->unit_price
            ) {

                $material->date_update_price =
                    Carbon::now(
                        'America/Lima'
                    );

                $material->state_update_price =
                    1;

                $material->save();
            }


            /*
             * ============================================================
             * PRODUCTO CON VARIANTES
             * ============================================================
             */

            if ($tipoVariantes === 1) {

                /*
                 * Variantes actuales del Material.
                 *
                 * Las que no lleguen en el request NO son eliminadas.
                 */
                $existingVariants =
                    $material
                        ->variants
                        ->keyBy('id');


                foreach (
                    $variantes as $index => $item
                ) {

                    $variantId =
                        $item[
                        'variant_id'
                        ] ?? null;

                    $tallaId =
                        $item[
                        'talla_id'
                        ] ?? null;

                    $colorId =
                        $item[
                        'color_id'
                        ] ?? null;


                    $sku =
                        trim(
                            $item[
                            'sku'
                            ] ?? ''
                        );

                    $barcode =
                        trim(
                            $item[
                            'codigo_barras'
                            ] ?? ''
                        );


                    $isActive =
                        isset(
                            $item[
                            'is_active'
                            ]
                        )
                            ? (int) $item[
                        'is_active'
                        ]
                            : 1;


                    $tracksInventory =
                        isset(
                            $item[
                            'afecto_inventario'
                            ]
                        )
                            ? (int) $item[
                        'afecto_inventario'
                        ]
                            : 1;


                    $imageKey =
                        $item[
                        'image_key'
                        ] ?? null;


                    $inventoryLevels =
                        $item[
                        'inventory_levels'
                        ] ?? [];


                    /*
                     * Segunda barrera.
                     *
                     * El Request ya lo validó.
                     */
                    if ($sku === '') {

                        throw new \RuntimeException(
                            'Una de las variantes no tiene SKU.'
                        );
                    }


                    /*
                     * ====================================================
                     * TALLA + COLOR
                     * ====================================================
                     */

                    $talla =
                        $tallaId
                            ? Talla::find(
                            $tallaId
                        )
                            : null;


                    $color =
                        $colorId
                            ? Color::find(
                            $colorId
                        )
                            : null;


                    if (
                        $tallaId &&
                        !$talla
                    ) {

                        throw new \RuntimeException(
                            'Una de las tallas no pertenece al grupo empresarial actual.'
                        );
                    }


                    if (
                        $colorId &&
                        !$color
                    ) {

                        throw new \RuntimeException(
                            'Uno de los colores no pertenece al grupo empresarial actual.'
                        );
                    }


                    $tallaTexto =
                        $talla
                            ? (
                        $talla->short_name
                            ?: $talla->name
                        )
                            : '';


                    $colorTexto =
                        $color
                            ? $color->name
                            : '';


                    $attributeSummary =
                        collect([
                            $tallaTexto,
                            $colorTexto,
                        ])
                            ->filter()
                            ->implode(' / ');


                    $displayName =
                        trim(
                            $material->full_name .
                            ' - ' .
                            collect([
                                $tallaTexto,
                                $colorTexto,
                            ])
                                ->filter()
                                ->implode(' - ')
                        );


                    /*
                     * ====================================================
                     * VARIANT EXISTENTE O NUEVA
                     * ====================================================
                     */

                    if (
                        $variantId &&
                        $existingVariants->has(
                            $variantId
                        )
                    ) {

                        /*
                         * Variante existente.
                         */
                        $variant =
                            $existingVariants
                                ->get(
                                    $variantId
                                );

                    } else {

                        /*
                         * Variante nueva.
                         */
                        $variant =
                            new Variant();

                        $variant->material_id =
                            $material->id;
                    }


                    /*
                     * Talla correcta.
                     *
                     * YA NO quality_id.
                     */
                    $variant->talla_id =
                        $tallaId;

                    $variant->color_id =
                        $colorId;

                    $variant->attribute_summary =
                        $attributeSummary;

                    $variant->is_active =
                        $isActive;


                    /*
                     * Imagen específica de variante.
                     */
                    if (
                        $imageKey &&
                        $request->hasFile(
                            $imageKey
                        )
                    ) {

                        $variantImage =
                            $request->file(
                                $imageKey
                            );


                        $variantImageName =
                            'variant_' .
                            $material->id .
                            '_' .
                            uniqid() .
                            '.' .
                            $variantImage
                                ->getClientOriginalExtension();


                        $variantPath =
                            public_path(
                                'images/material/variants/' .
                                $variantImageName
                            );


                        Image::make(
                            $variantImage
                        )->save(
                            $variantPath
                        );


                        $variant->image =
                            $variantImageName;
                    }


                    $variant->save();


                    /*
                     * ====================================================
                     * STOCK ITEM
                     * ====================================================
                     */

                    $stockItem =
                        StockItem::firstOrNew([
                            'material_id' =>
                                $material->id,

                            'variant_id' =>
                                $variant->id,
                        ]);


                    /*
                     * Importante conocer si acaba de nacer.
                     */
                    $isNewStockItem =
                        !$stockItem->exists;


                    $stockItem->sku =
                        $sku;

                    $stockItem->barcode =
                        $barcode !== ''
                            ? $barcode
                            : null;

                    $stockItem->display_name =
                        $displayName;

                    $stockItem->unit_measure_id =
                        $material->unit_measure_id;

                    $stockItem->tracks_inventory =
                        $tracksInventory;

                    $stockItem->is_active =
                        $isActive;


                    $stockItem->save();


                    /*
                     * ====================================================
                     * STOCK ITEM NUEVO
                     * ====================================================
                     *
                     * El catálogo maestro pertenece al Tenant,
                     * pero un StockItem creado desde esta Company
                     * comienza habilitado solamente para esta Company.
                     */

                    if ($isNewStockItem) {

                        $this
                            ->enableStockItemForCurrentCompany(
                                $stockItem
                            );


                        /*
                         * Crear inventario inicial:
                         *
                         * Company actual
                         * -> Warehouse default
                         * -> Location default
                         */
                        $this
                            ->createInitialInventoryLevel(
                                $stockItem
                            );
                    }


                    /*
                     * ====================================================
                     * INVENTORY LEVELS EXISTENTES
                     * ====================================================
                     *
                     * syncInventoryLevels solamente permite actualizar
                     * min_alert / max_alert de registros pertenecientes:
                     *
                     * - al StockItem
                     * - a la Company actual
                     *
                     * No altera stock, costo, warehouse ni location.
                     */

                    $this->syncInventoryLevels(
                        $stockItem,
                        $inventoryLevels
                    );
                }


                /*
                 * Las variantes que no llegaron en el request
                 * se conservan.
                 *
                 * NO hacemos delete.
                 *
                 * Para desactivar una variante existente,
                 * debe enviarse is_active = 0.
                 */
            }


            /*
             * ============================================================
             * PRODUCTO SIMPLE
             * ============================================================
             */

            else {

                $item =
                    $variantes[0];


                $stockItemId =
                    $item[
                    'stock_item_id'
                    ] ?? null;


                $sku =
                    trim(
                        $item[
                        'sku'
                        ] ?? ''
                    );


                $barcode =
                    trim(
                        $item[
                        'codigo_barras'
                        ] ?? ''
                    );


                $isActive =
                    isset(
                        $item[
                        'is_active'
                        ]
                    )
                        ? (int) $item[
                    'is_active'
                    ]
                        : 1;


                $tracksInventory =
                    isset(
                        $item[
                        'afecto_inventario'
                        ]
                    )
                        ? (int) $item[
                    'afecto_inventario'
                    ]
                        : 1;


                $isPack =
                    isset(
                        $item[
                        'pack'
                        ]
                    )
                        ? (int) $item[
                    'pack'
                    ]
                        : 0;


                $cantidadPack =
                    isset(
                        $item[
                        'cantidad_pack'
                        ]
                    )
                        ? (float) $item[
                    'cantidad_pack'
                    ]
                        : 1;


                $inventoryLevels =
                    $item[
                    'inventory_levels'
                    ] ?? [];


                if ($sku === '') {

                    throw new \RuntimeException(
                        'El producto no tiene SKU.'
                    );
                }


                /*
                 * Producto simple:
                 *
                 * NO talla.
                 * NO color.
                 * NO Variant.
                 */

                $displayName =
                    $material->full_name;


                /*
                 * Campos legacy del Material que todavía
                 * pueden ser consumidos en partes antiguas.
                 */

                $material->quality_id =
                    null;

                $material->codigo =
                    $barcode !== ''
                        ? $barcode
                        : null;


                /*
                 * min/max ahora viven realmente en InventoryLevel.
                 *
                 * Estos campos quedan solamente por compatibilidad.
                 */
                $material->stock_min =
                    0;

                $material->stock_max =
                    0;


                $material->isPack =
                    $isPack;

                $material->quantityPack =
                    $isPack
                        ? $cantidadPack
                        : 0;


                $material->save();


                /*
                 * ========================================================
                 * BUSCAR STOCK ITEM SIMPLE
                 * ========================================================
                 */

                $stockItem =
                    null;


                if ($stockItemId) {

                    $stockItem =
                        StockItem::query()
                            ->where(
                                'material_id',
                                $material->id
                            )
                            ->whereNull(
                                'variant_id'
                            )
                            ->where(
                                'id',
                                $stockItemId
                            )
                            ->first();
                }


                /*
                 * Compatibilidad con productos históricos
                 * que eventualmente no tengan StockItem.
                 */
                if (!$stockItem) {

                    $stockItem =
                        StockItem::firstOrNew([
                            'material_id' =>
                                $material->id,

                            'variant_id' =>
                                null,
                        ]);
                }


                $isNewStockItem =
                    !$stockItem->exists;


                $stockItem->sku =
                    $sku;

                $stockItem->barcode =
                    $barcode !== ''
                        ? $barcode
                        : null;

                $stockItem->display_name =
                    $displayName;

                $stockItem->unit_measure_id =
                    $material->unit_measure_id;

                $stockItem->tracks_inventory =
                    $tracksInventory;

                $stockItem->is_active =
                    $isActive;


                $stockItem->save();


                /*
                 * ========================================================
                 * STOCK ITEM SIMPLE NUEVO
                 * ========================================================
                 */

                if ($isNewStockItem) {

                    $this
                        ->enableStockItemForCurrentCompany(
                            $stockItem
                        );


                    $this
                        ->createInitialInventoryLevel(
                            $stockItem
                        );
                }


                /*
                 * Actualizar min/max únicamente de niveles
                 * de inventario ya existentes de esta Company.
                 */

                $this->syncInventoryLevels(
                    $stockItem,
                    $inventoryLevels
                );
            }


            /*
             * ============================================================
             * DESCUENTOS POR CANTIDAD
             * ============================================================
             */

            MaterialDiscountQuantity::where(
                'material_id',
                $material->id
            )->delete();


            $discounts =
                $request->input(
                    'discount',
                    []
                );


            $percentages =
                $request->input(
                    'percentage',
                    []
                );


            foreach (
                $discounts as $discountId => $value
            ) {

                if (isset($value)) {

                    $percentage =
                        $percentages[
                        $discountId
                        ] ?? null;


                    MaterialDiscountQuantity::create([
                        'material_id' =>
                            $material->id,

                        'discount_quantity_id' =>
                            $discountId,

                        'percentage' =>
                            $percentage,
                    ]);
                }
            }


            /*
             * ============================================================
             * COMMIT
             * ============================================================
             */

            DB::commit();


            return response()->json([
                'message' =>
                    'Cambios guardados con éxito.',
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);


            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

    private function syncInventoryLevels(StockItem $stockItem, array $inventoryLevelsData = []) {
        $companyId =
            TenantContext::companyId();

        foreach (
            $inventoryLevelsData as $levelData
        ) {

            $inventoryLevelId =
                $levelData['id'] ?? null;

            $warehouseId =
                $levelData['warehouse_id'] ?? null;

            $minAlert =
                ($levelData['min_alert'] ?? '') !== ''
                    ? (float) $levelData['min_alert']
                    : 0;

            $maxAlert =
                ($levelData['max_alert'] ?? '') !== ''
                    ? (float) $levelData['max_alert']
                    : 0;


            /*
             * Desde Edit solo actualizamos InventoryLevels
             * que ya existen.
             *
             * Los niveles nuevos se crean mediante
             * DefaultInventoryLocationResolver.
             */
            if (!$inventoryLevelId) {
                continue;
            }


            $inventoryLevel =
                InventoryLevel::query()
                    ->where(
                        'id',
                        $inventoryLevelId
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'stock_item_id',
                        $stockItem->id
                    )
                    ->first();


            if (!$inventoryLevel) {

                throw new \RuntimeException(
                    'Uno de los niveles de inventario no pertenece al producto o empresa actual.'
                );
            }


            /*
             * Segunda barrera:
             * el Warehouse recibido debe coincidir
             * con el InventoryLevel existente.
             */
            if (
                $warehouseId &&
                (int) $inventoryLevel->warehouse_id !==
                (int) $warehouseId
            ) {

                throw new \RuntimeException(
                    'El almacén enviado no corresponde al nivel de inventario.'
                );
            }


            if ($minAlert < 0 || $maxAlert < 0) {

                throw new \RuntimeException(
                    'Los niveles mínimo y máximo de inventario no pueden ser negativos.'
                );
            }


            if ($minAlert > $maxAlert) {

                throw new \RuntimeException(
                    'El stock mínimo no puede ser mayor al stock máximo.'
                );
            }


            /*
             * No permitimos modificar desde aquí:
             *
             * qty_on_hand
             * qty_reserved
             * average_cost
             * last_cost
             * warehouse_id
             * location_id
             * company_id
             *
             * Esos datos pertenecen a procesos
             * operativos de inventario.
             */

            $inventoryLevel->min_alert =
                $minAlert;

            $inventoryLevel->max_alert =
                $maxAlert;

            $inventoryLevel->save();
        }
    }

    private function createInitialInventoryLevel(StockItem $stockItem) {
        $companyId =
            TenantContext::companyId();

        /** @var DefaultInventoryLocationResolver $resolver */
        $resolver =
            app(
                DefaultInventoryLocationResolver::class
            );

        $resolved =
            $resolver->resolveCurrent();

        $warehouse =
            $resolved['warehouse'];

        $location =
            $resolved['location'];


        /*
         * Evitamos duplicados.
         *
         * Tenemos UNIQUE:
         * stock_item_id + location_id
         */
        $inventoryLevel =
            InventoryLevel::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'stock_item_id',
                    $stockItem->id
                )
                ->where(
                    'location_id',
                    $location->id
                )
                ->first();


        if ($inventoryLevel) {
            return $inventoryLevel;
        }


        return InventoryLevel::create([
            'company_id' =>
                $companyId,

            'stock_item_id' =>
                $stockItem->id,

            'warehouse_id' =>
                $warehouse->id,

            'location_id' =>
                $location->id,

            'qty_on_hand' =>
                0,

            'qty_reserved' =>
                0,

            'min_alert' =>
                0,

            'max_alert' =>
                0,

            'average_cost' =>
                0,

            'last_cost' =>
                0,
        ]);
    }

    private function enableStockItemForCurrentCompany(StockItem $stockItem) {
        return CompanyStockItem::updateOrCreate(
            [
                'company_id' =>
                    TenantContext::companyId(),

                'stock_item_id' =>
                    $stockItem->id,
            ],
            [
                'is_active' =>
                    true,
            ]
        );
    }

    public function destroy(DeleteMaterialRequest $request)
    {
        $validated = $request->validated();

        $material = Material::find($request->get('material_id'));
        Specification::where('material_id', $request->get('material_id'))->delete();

        $material->delete();

        return response()->json(['message' => 'Material eliminado con éxito.'], 200);

    }

    public function getDataMaterials(Request $request, $pageNumber = 1) {
        $perPage = 10;

        $description =
            $request->input('description');

        $code =
            $request->input('code');

        $category =
            $request->input('category');

        $subcategory =
            $request->input('subcategory');

        $materialType =
            $request->input('material_type');

        $subtype =
            $request->input('sub_type');

        $marca =
            $request->input('marca');

        $retaceria =
            $request->input('retaceria');

        $rotation =
            $request->input('rotation');

        $isPack =
            $request->input('isPack');


        /*
         * ============================================================
         * QUERY BASE
         * ============================================================
         *
         * Material ya tiene TenantScope.
         */

        $query =
            Material::query()
                ->with([
                    'category:id,name',
                    'subcategory:id,name',

                    'materialType:id,name',
                    'subType:id,name',

                    'unitMeasure:id,name',

                    'brand:id,name',
                    'exampler:id,name',

                    'typeScrap:id,name',
                ])
                ->withCount('variants')
                ->where(
                    'enable_status',
                    1
                )
                ->orderBy('id');


        /*
         * ============================================================
         * BÚSQUEDA POR DESCRIPCIÓN
         * ============================================================
         */

        if ($description != '') {

            $keywords =
                array_filter(
                    explode(
                        ' ',
                        trim($description)
                    )
                );


            $query->where(
                function ($query) use ($keywords) {

                    foreach (
                        $keywords as $keyword
                    ) {

                        $query->where(
                            'full_name',
                            'LIKE',
                            '%' .
                            $keyword .
                            '%'
                        );
                    }
                }
            );
        }


        /*
         * ============================================================
         * FILTROS
         * ============================================================
         */

        if ($code != '') {

            $query->where(
                'code',
                'LIKE',
                '%' .
                $code .
                '%'
            );
        }


        if ($category != '') {

            $query->where(
                'category_id',
                $category
            );
        }


        if ($subcategory != '') {

            $query->where(
                'subcategory_id',
                $subcategory
            );
        }


        if ($materialType != '') {

            $query->where(
                'material_type_id',
                $materialType
            );
        }


        if ($subtype != '') {

            $query->where(
                'subtype_id',
                $subtype
            );
        }


        if ($marca != '') {

            $query->where(
                'brand_id',
                $marca
            );
        }


        if ($retaceria != '') {

            $query->where(
                'typescrap_id',
                $retaceria
            );
        }


        if ($rotation != '') {

            $query->where(
                'rotation',
                $rotation
            );
        }


        if ($isPack != '') {

            $query->where(
                'isPack',
                $isPack
            );
        }


        /*
         * ============================================================
         * PAGINACIÓN
         * ============================================================
         */

        $totalFilteredRecords =
            $query->count();

        $totalPages =
            (int) ceil(
                $totalFilteredRecords /
                $perPage
            );


        $startRecord =
            $totalFilteredRecords > 0
                ? (
                    ($pageNumber - 1) *
                    $perPage
                ) + 1
                : 0;


        $endRecord =
            min(
                $totalFilteredRecords,
                $pageNumber *
                $perPage
            );


        $materials =
            $query
                ->skip(
                    ($pageNumber - 1) *
                    $perPage
                )
                ->take(
                    $perPage
                )
                ->get();


        /*
         * ============================================================
         * RESPUESTA
         * ============================================================
         */

        $array = [];


        foreach (
            $materials as $material
        ) {

            /*
             * Prioridad legacy.
             *
             * Después revisaremos estos valores para
             * asegurarnos de que sean Company-aware.
             */

            $priority = '';


            if (
                $material->stock_current >
                $material->stock_max
            ) {

                $priority =
                    'Completo';

            } elseif (
                $material->stock_current ==
                $material->stock_max
            ) {

                $priority =
                    'Aceptable';

            } elseif (
                $material->stock_current >
                $material->stock_min &&
                $material->stock_current <
                $material->stock_max
            ) {

                $priority =
                    'Aceptable';

            } elseif (
                $material->stock_current ==
                $material->stock_min
            ) {

                $priority =
                    'Por agotarse';

            } elseif (
                $material->stock_current <
                $material->stock_min ||
                $material->stock_current == 0
            ) {

                $priority =
                    'Agotado';
            }


            /*
             * Rotación.
             */

            if (
                $material->rotation == 'a'
            ) {

                $rotacion =
                    '<span class="badge bg-success text-md">ALTA</span>';

            } elseif (
                $material->rotation == 'm'
            ) {

                $rotacion =
                    '<span class="badge bg-warning text-md">MEDIA</span>';

            } else {

                $rotacion =
                    '<span class="badge bg-danger text-md">BAJA</span>';
            }


            $array[] = [

                'id' =>
                    $material->id,

                'codigo' =>
                    $material->code,

                'descripcion' =>
                    $material->full_name,

                'medida' =>
                    $material->measure,


                /*
                 * ========================================================
                 * DATOS CONFIGURABLES
                 * ========================================================
                 */

                'unidad_medida' =>
                    optional(
                        $material->unitMeasure
                    )->name ?: '',


                'categoria' =>
                    optional(
                        $material->category
                    )->name ?: '',


                'sub_categoria' =>
                    optional(
                        $material->subcategory
                    )->name ?: '',


                /*
                 * NUEVAS COLUMNAS DEL LISTADO.
                 */

                'tipo_material' =>
                    optional(
                        $material->materialType
                    )->name ?: '',


                'subtipo' =>
                    optional(
                        $material->subType
                    )->name ?: '',


                'marca' =>
                    optional(
                        $material->brand
                    )->name ?: '',


                'modelo' =>
                    optional(
                        $material->exampler
                    )->name ?: '',


                'retaceria' =>
                    optional(
                        $material->typeScrap
                    )->name ?: '',


                /*
                 * ========================================================
                 * STOCK
                 * ========================================================
                 */

                'stock_max' =>
                    $material->stock_max_total,

                'stock_min' =>
                    $material->stock_min_total,

                'stock_actual' =>
                    $material->stock_current,

                'stock_current' =>
                    $material->stock_current_total,


                /*
                 * ========================================================
                 * OTROS
                 * ========================================================
                 */

                'prioridad' =>
                    $priority,

                'precio_unitario' =>
                    $material->unit_price,

                'precio_lista' =>
                    $material->list_price,


                'image' =>
                    empty($material->image)
                        ? 'no_image.png'
                        : $material->image,


                'rotation' =>
                    $rotacion,


                'update_price' =>
                    $material->state_update_price,


                'isPack' =>
                    $material->isPack,


                'has_variants' =>
                    $material->variants_count > 0
                        ? 1
                        : 0,


                /*
                 * Compatibilidad temporal.
                 *
                 * Puedes quitarlos cuando confirmemos que
                 * ningún otro código consume estos nombres.
                 */
                'tipo' =>
                    optional(
                        $material->materialType
                    )->name ?: '',

                'sub_tipo' =>
                    optional(
                        $material->subType
                    )->name ?: '',
            ];
        }


        /*
         * ============================================================
         * PAGINACIÓN
         * ============================================================
         */

        $pagination = [

            'currentPage' =>
                (int) $pageNumber,

            'totalPages' =>
                $totalPages,

            'startRecord' =>
                $startRecord,

            'endRecord' =>
                $endRecord,

            'totalRecords' =>
                $totalFilteredRecords,

            'totalFilteredRecords' =>
                $totalFilteredRecords,
        ];


        return [
            'data' =>
                $array,

            'pagination' =>
                $pagination,
        ];
    }

    public function indexV2()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        /*
         * ============================================================
         * CONFIGURACIÓN DE COLUMNAS DE MATERIAL POR COMPANY
         * ============================================================
        */

        $setting =
            MaterialDetailSetting::query()
                ->forCompany(
                    TenantContext::companyId()
                )
                ->first();

        $enabled = [];

        if (
            $setting &&
            is_array($setting->enabled_sections)
        ) {
            $enabled =
                $setting->enabled_sections;
        }

        $arrayCategories = Category::where('id', '<>', 8)->select('id', 'name')->get()->toArray();

        $arrayMarcas = Brand::select('id', 'name')->get()->toArray();

        $arrayRetacerias = Typescrap::select('id', 'name')->get()->toArray();

        $arrayRotations = [
            ["value" => "a", "display" => "ALTA"],
            ["value" => "m", "display" => "MEDIA"],
            ["value" => "b", "display" => "BAJA"]
        ];

        $materials = Material::where('isPack', 0)
            ->where('enable_status', 1)->get();

        $arrayMaterials = [];
        foreach ( $materials as $material )
        {
            array_push($arrayMaterials, [
                'id'=> $material->id,
                'full_name' => $material->full_name,
            ]);
        }

        $rows = Material::where('enable_status', 1)->resumenPorMaterial()->get();

        // Calculamos el estado de stock
        foreach ($rows as $row) {
            if ($row->stock_total <= 0) {
                $row->estado = 'desabastecido';
            } elseif ($row->stock_total <= $row->stock_min) {
                $row->estado = 'por_desabastecer';
            } else {
                $row->estado = 'ok';
            }
        }

        $flagAlertas = DataGeneral::where('name', 'show_alert_stock_minimos')->first();
        $alerta = $flagAlertas->valueText;
        $hayAlertas = [];
        if ($alerta == 's')
        {
            $hayAlertas = $rows->contains(function ($r) {
                return $r->estado === 'desabastecido' || $r->estado === 'por_desabastecer';
            });
        }

        return view(
            'material.indexv2',
            compact(
                'enabled',
                'permissions',
                'arrayCategories',
                'arrayMarcas',
                'arrayRetacerias',
                'arrayRotations',
                'arrayMaterials',
                'rows',
                'hayAlertas'
            )
        );

    }

    public function getAllMaterials()
    {
        $materials = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->where('enable_status', 1)
            ->where('category_id', '<>', 8)
            ->get();
            //->get(['id', 'code', 'measure', 'stock_max', 'stock_min', 'stock_current', 'priority', 'unit_price', 'image', 'description'])->toArray();


        //dd($materials);
        //dd(datatables($materials)->toJson());
        return datatables($materials)->toJson();
    }

    public function indexMaterialsActivos()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('material.indexActivosFijos', compact('permissions'));
    }

    public function getAllMaterialsActivosFijos()
    {
        $materials = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->where('enable_status', 1)
            ->where('category_id', '=', 8)
            ->get();
        //->get(['id', 'code', 'measure', 'stock_max', 'stock_min', 'stock_current', 'priority', 'unit_price', 'image', 'description'])->toArray();

        $items = Item::with('material')->where('type', true)->get();
        $items_quantity = [];
        $materials_quantity = [];
        foreach ( $items as $item )
        {
            if ( $item->material->category_id != 8 )
            {
                array_push($items_quantity, array('material_id'=>$item->material_id,'quantity'=> (float)$item->percentage));
            }
        }

        $new_arr = array();
        foreach($items_quantity as $item) {
            if(isset($new_arr[$item['material_id']])) {
                $new_arr[ $item['material_id']]['quantity'] += (float)$item['quantity'];
                continue;
            }

            $new_arr[$item['material_id']] = $item;
        }

        $materials_quantity = array_values($new_arr);

        $arrayMaterials = [];

        foreach($materials_quantity as $mat) {
            $material = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
                ->where('enable_status', 1)
                ->find($mat['material_id']);
            array_push($arrayMaterials, [
                'id'=> $material->id,
                'description' => $material->full_description,
                'code' => $material->code,
                'priority' => $material->priority,
                'measure' => $material->measure,
                'unit_measure' => ($material->unit_measure_id == null) ? '': $material->unitMeasure->name,
                'stock_max' => $material->stock_max,
                'stock_min'=>$material->stock_min,
                'quantity_items'=>$material->quantity_items,
                'stock_current'=>$mat['quantity'],
                'unit_price'=>$material->price_final,
                'image'=>$material->image,
                'category' => ($material->category_id == null) ? '': $material->category->name,
                'subcategory' => ($material->subcategory_id == null) ? '': $material->subcategory->name,
                'material_type' => ($material->material_type_id == null) ? '': $material->materialType->name,
                'sub_type' => ($material->sub_type_id == null) ? '': $material->sub_type->name,
                'warrant' => ($material->warrant_id == null) ? '': $material->warrant->name,
                'quality' => ($material->quality_id == null) ? '': $material->quality->name,
                'brand' => ($material->brand_id == null) ? '': $material->brand->name,
                'exampler' => ($material->exampler_id == null) ? '': $material->exampler->name,
                'type_scrap' => ($material->type_scrap_id == null) ? '': $material->typeScrap->name,
            ]);
        }

        foreach($materials as $material) {
            array_push($arrayMaterials, [
                'id'=> $material->id,
                'description' => $material->full_description,
                'code' => $material->code,
                'priority' => $material->priority,
                'measure' => $material->measure,
                'unit_measure' => ($material->unit_measure_id == null) ? '': $material->unitMeasure->name,
                'stock_max' => $material->stock_max,
                'stock_min'=>$material->stock_min,
                'quantity_items'=>$material->quantity_items,
                'stock_current'=>$material->quantity_items,
                'unit_price'=>$material->price_final,
                'image'=>$material->image,
                'category' => ($material->category_id == null) ? '': $material->category->name,
                'subcategory' => ($material->subcategory_id == null) ? '': $material->subcategory->name,
                'material_type' => ($material->material_type_id == null) ? '': $material->materialType->name,
                'sub_type' => ($material->sub_type_id == null) ? '': $material->sub_type->name,
                'warrant' => ($material->warrant_id == null) ? '': $material->warrant->name,
                'quality' => ($material->quality_id == null) ? '': $material->quality->name,
                'brand' => ($material->brand_id == null) ? '': $material->brand->name,
                'exampler' => ($material->exampler_id == null) ? '': $material->exampler->name,
                'type_scrap' => ($material->type_scrap_id == null) ? '': $material->typeScrap->name,
            ]);
        }

        //dd($arrayMaterials);
        //dd(datatables($materials)->toJson());
        return datatables($arrayMaterials)->toJson();
    }

    public function getAllMaterialsSinOp()
    {
        $begin = microtime(true);
        $materials = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->where('enable_status', 1)
            ->get();
        //->get(['id', 'code', 'measure', 'stock_max', 'stock_min', 'stock_current', 'priority', 'unit_price', 'image', 'description'])->toArray();

        $end = microtime(true) - $begin;

        dump($end. ' segundos');
        dd();
        //dd(datatables($materials)->toJson());
        //return datatables($materials)->toJson();
    }

    public function getAllMaterialsOp()
    {
        $begin = microtime(true);
        $materials = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->where('enable_status', 1)
            ->get();

        $array = [];

        foreach ($materials as $material) {
            array_push($array, [
                'id'=> $material->id,
                'description' => $material->full_description,
                'code' => $material->code,
                'priority' => $material->priority,
                'measure' => $material->measure,
                'unit_measure' => ($material->unit_measure_id == null) ? '': $material->unitMeasure->name,
                'stock_max' => $material->stock_max,
                'stock_min'=>$material->stock_min,
                'stock_current'=>$material->stock_current,
                'unit_price'=>$material->price_final,
                'image'=>$material->image,
                'category' => ($material->category_id == null) ? '': $material->category->name,
                'subcategory' => ($material->subcategory_id == null) ? '': $material->subcategory->name,
                'material_type' => ($material->material_type_id == null) ? '': $material->materialType->name,
                'sub_type' => ($material->sub_type_id == null) ? '': $material->sub_type->name,
                'warrant' => ($material->warrant_id == null) ? '': $material->warrant->name,
                'quality' => ($material->quality_id == null) ? '': $material->quality->name,
                'brand' => ($material->brand_id == null) ? '': $material->brand->name,
                'exampler' => ($material->exampler_id == null) ? '': $material->exampler->name,
                'type_scrap' => ($material->type_scrap_id == null) ? '': $material->typeScrap->name,
            ]);

        }
        //->get(['id', 'code', 'measure', 'stock_max', 'stock_min', 'stock_current', 'priority', 'unit_price', 'image', 'description'])->toArray();

        $end = microtime(true) - $begin;

        dump($end. ' segundos');
        dd();
        //dd(datatables($materials)->toJson());
        //return datatables($materials)->toJson();
    }

    public function getJsonMaterialsTransfer()
    {
        $materials = Material::where('enable_status', 1)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, ['id'=> $material->id, 'material' => $material->full_description, 'code' => $material->code, ]);
        }

        //dd($materials);
        return $array;
    }

    public function getJsonMaterialsEntry()
    {
        $materials = Material::with('category', 'materialType','unitMeasure','subcategory','subType','exampler','brand','warrant','quality','typeScrap')
            ->where('enable_status', 1)
            ->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, [
                'id'=> $material->id,
                'material' => $material->full_name,
                'unit' => ($material->unitMeasure == null) ? "":$material->unitMeasure->name,
                'code' => $material->code,
                'price'=>$material->price_final,
                'typescrap'=>$material->typescrap_id,
                'full_typescrap'=>$material->typeScrap,
                'stock_current'=>$material->stock_current,
                'category'=>$material->category_id,
                'enable_status'=>$material->enable_status,
                'tipo_venta_id'=>$material->tipo_venta_id,
                'perecible' => ($material->perecible == null) ? 'n':$material->perecible
            ]);
        }

        //dd($materials);
        return $array;
    }

    public function getJsonMaterials()
    {
        $materials = Material::with('category', 'materialType','unitMeasure','subcategory','subType','exampler','brand','warrant','quality','typeScrap')
            ->where('enable_status', 1)
            ->where('category_id', '<>', 8)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, ['id'=> $material->id, 'material' => $material->full_description, 'unit' => ($material->unitMeasure == null) ? '':$material->unitMeasure->name, 'code' => $material->code, 'price'=>$material->price_final, 'typescrap'=>$material->typescrap_id, 'full_typescrap'=>$material->typeScrap, 'stock_current'=>$material->stock_current]);
        }

        //dd($materials);
        return $array;
    }

    public function getJsonMaterialsQuote()
    {
        $materials = Material::with('category', 'materialType','unitMeasure','subcategory','subType','exampler','brand','warrant','quality','typeScrap')
            ->whereNotIn('category_id', [2])
            ->where('category_id', '<>', 8)
            ->where('enable_status', 1)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, ['id'=> $material->id, 'material' => $material->full_description, 'unit' => $material->unitMeasure->name, 'code' => $material->code]);
        }

        //dd($materials);
        return $array;
    }

    public function getJsonMaterialsCombo()
    {
        $materials = Material::with('unitMeasure')
            ->where('enable_status', 1)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, ['id'=> $material->id, 'material' => $material->full_name, 'unit' => $material->unitMeasure->name, 'code' => $material->code, 'price' => $material->price_final]);
        }

        //dd($materials);
        return $array;
    }

    public function getJsonMaterialsScrap()
    {
        $materials = Material::with('subcategory', 'materialType', 'subtype', 'warrant', 'quality')
            ->whereNotNull('typescrap_id')
            ->where('enable_status', 1)
            ->get();
        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, ['id'=> $material->id, 'material' => $material->full_description, 'code' => $material->code , 'unit' => $material->unitMeasure->name, 'typescrap'=>$material->typescrap_id]);
        }

        //dd($materials);
        return $array;
    }

    public function getItems($id)
    {
        /*
        $items = Item::where('material_id', $id)->get();
        $brands = Brand::all();
        $categories = Category::all();
        $materialTypes = MaterialType::all();
        $material = Material::with(['category', 'materialType'])->find($id);
        return view('material.edit', compact('items', 'brands', 'categories', 'materialTypes', 'material'));
        */

        $material = Material::find($id);
        //$items = Item::where('material_id', $id)->get();
        //return view('material.items', compact('items', 'material'));
        return view('material.items', compact('material'));

    }

    public function getItemsStockItems($id)
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $stockMaterial = StockItem::find($id);
        return view('material.stockItems_items', compact('stockMaterial', 'permissions'));
    }

    public function getItemsMaterialActivo($id)
    {

        $material = Material::find($id);
        //$items = Item::where('material_id', $id)->get();
        //return view('material.items', compact('items', 'material'));
        return view('material.itemsActivos', compact('material'));

    }

    public function getItemsMaterialAllActivos($id)
    {
        $material = Material::find($id);

        $arrayItems = [];

        if ( $material->category_id == 8 )
        {
            $items = Item::where('material_id', $id)
                ->with(['location' => function ($query) {
                    $query->with(['area', 'warehouse', 'shelf', 'level', 'container', 'position']);
                }])
                ->with('material')
                ->with('typescrap')
                ->with('detailEntry')->get();
        } else {
            $items = Item::where('material_id', $id)
                ->where('type', 1)
                ->with(['location' => function ($query) {
                    $query->with(['area', 'warehouse', 'shelf', 'level', 'container', 'position']);
                }])
                ->with('material')
                ->with('typescrap')
                ->with('detailEntry')->get();
        }


        //dd(datatables($items)->toJson());
        return datatables($items)->toJson();

    }

    public function getItemsMaterial($id)
    {

        $items = Item::where('material_id', $id)
            ->whereIn('state_item', ['entered', 'scraped'])
            ->with(['location' => function ($query) {
                $query->with(['area', 'warehouse', 'shelf', 'level', 'container', 'position']);
            }])
            ->with('material')
            ->with('typescrap')
            ->with('DetailEntry')->get();

        //dd(datatables($items)->toJson());
        return datatables($items)->toJson();

    }

    /*public function getItemsStockItemsMaterial($id)
    {

        $items = Item::where('stock_item_id', $id)
            ->whereIn('state_item', ['entered', 'scraped'])
            ->with(['location' => function ($query) {
                $query->with(['area', 'warehouse', 'shelf', 'level', 'container', 'position']);
            }])
            ->with('material')
            ->with('typescrap')
            ->with('stockItem')
            ->with('DetailEntry')->get();

        //dd(datatables($items)->toJson());
        return datatables($items)->toJson();

    }*/

    public function getItemsStockItemsMaterial($id)
    {
        $items = Item::query()
            ->where('stock_item_id', $id)
            ->whereIn('state_item', ['entered', 'scraped', 'reserved'])
            ->with([
                'material:id,full_name',
                'typescrap:id,name',
                'location.area',
                'location.warehouse',
                'location.shelf',
                'location.level',
                'location.container',
                'location.position',
            ]);

        return datatables($items)
            ->addColumn('material_description', function ($item) {
                return optional($item->material)->full_description ?? '';
            })
            ->toJson();
    }

    public function updateCode(Request $request, Item $item)
    {
        $request->validate([
            'code' => 'required|string|max:255'
        ]);

        $code = trim($request->code);

        $exists = Item::where('code', $code)
            ->where('id', '!=', $item->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Este código ya está registrado en otro ítem.'
            ], 422);
        }

        $item->code = $code;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Código actualizado correctamente.'
        ]);
    }

    public function disableMaterial(Request $request)
    {

        DB::beginTransaction();
        try {
            $material = Material::find($request->get('material_id'));
            $material->enable_status = 0;
            $material->save();

            $variants = Variant::where('material_id', $material->id)->get();
            if (isset( $variants ))
            {
                foreach ( $variants as $variant )
                {
                    $variant->is_active = false;
                    $variant->save();
                }
            }

            $stockItems = StockItem::where('material_id', $material->id)->get();
            if (isset( $stockItems ))
            {
                foreach ( $stockItems as $stockItem )
                {
                    $stockItem->is_active = false;
                    $stockItem->save();
                }
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Material inhabilitado con éxito.'], 200);

    }

    public function enableMaterial(Request $request)
    {
        DB::beginTransaction();
        try {
            $material = Material::find($request->get('material_id'));
            $material->enable_status = 1;
            $material->save();
            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Material habilitado con éxito.'], 200);

    }

    public function getAllMaterialsDisable()
    {
        $materials = Material::with(
            'category:id,name',
            'materialType:id,name',
            'unitMeasure:id,name',
            'subcategory:id,name',
            'subType:id,name',
            'exampler:id,name',
            'brand:id,name',
            'warrant:id,name',
            'quality:id,name',
            'typeScrap:id,name'
        )
            ->where('enable_status', 0)
            ->where(function ($q) {
                $q->where('category_id', '<>', 8)
                    ->orWhereNull('category_id');
            })
            ->get();
        //->get(['id', 'code', 'measure', 'stock_max', 'stock_min', 'stock_current', 'priority', 'unit_price', 'image', 'description'])->toArray();


        //dd($materials);
        //dd(datatables($materials)->toJson());
        return datatables($materials)->toJson();
    }

    public function indexEnable()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        return view('material.enable', compact('permissions'));
    }

    public function updateDescriptionLargeMaterials()
    {
        $begin = microtime(true);
        dump("Iniciano proceso");
        $materials = Material::all();

        foreach ($materials as $material) {
            $nombreCompleto = $material->full_description;
            $material->full_name = $nombreCompleto;
            $material->save();
        }
        $end = microtime(true) - $begin;
        dump($end);
        dd();
    }

    public function getDataMaterialsPack(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $description = $request->input('description');
        $code = $request->input('code');
        $category = $request->input('category');
        $subcategory = $request->input('subcategory');
        $material_type = $request->input('material_type');
        $sub_type = $request->input('sub_type');
        $cedula = $request->input('cedula');
        $calidad = $request->input('calidad');
        $marca = $request->input('marca');
        $retaceria = $request->input('retaceria');
        $rotation = $request->input('rotation');

        $query = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->where('enable_status', 1)
            ->where('isPack',1)
            /*->orderBy('rotation', "desc")*/
            ->orderBy('id');

        // Aplicar filtros si se proporcionan
        if ($description != "") {
            // Convertir la cadena de búsqueda en un array de palabras clave
            $keywords = explode(' ', $description);

            // Construir la consulta para buscar todas las palabras clave en el campo full_name
            $query->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->where('full_name', 'LIKE', '%' . $keyword . '%');
                }
            });

            // Asegurarse de que todas las palabras clave estén presentes en la descripción
            foreach ($keywords as $keyword) {
                $query->where('full_name', 'LIKE', '%' . $keyword . '%');
            }
        }

        if ($code != "") {
            $query->where('code', 'LIKE', '%'.$code.'%');
        }

        if ($category != "") {
            $query->where('category_id', $category);
        }

        if ($subcategory != "") {
            $query->where('subcategory_id', $subcategory);
        }

        if ($material_type != "") {
            $query->where('material_type_id', $material_type);
        }

        if ($sub_type != "") {
            $query->where('subtype_id', $sub_type);
        }

        if ($cedula != "") {
            $query->where('warrant_id', $cedula);
        }

        if ($calidad != "") {
            $query->where('quality_id', $calidad);
        }

        if ($marca != "") {
            $query->where('brand_id', $marca);
        }

        if ($retaceria != "") {
            $query->where('typescrap_id', $retaceria);
        }

        if ( $rotation != "" ) {
            $query->where('rotation', $rotation);
        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $materials = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $array = [];

        foreach ( $materials as $material )
        {
            $priority = '';
            if ( $material->stock_current > $material->stock_max ){
                $priority = 'Completo';
            } else if ( $material->stock_current == $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current > $material->stock_min && $material->stock_current < $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current == $material->stock_min ){
                $priority = 'Por agotarse';
            } else if ( $material->stock_current < $material->stock_min || $material->stock_current == 0 ){
                $priority = 'Agotado';
            }

            $rotacion = "";
            if ( $material->rotation == "a" )
            {
                $rotacion = '<span class="badge bg-success text-md">ALTA</span>';
            } elseif ( $material->rotation == "m" ) {
                $rotacion = '<span class="badge bg-warning text-md">MEDIA</span>';
            } else {
                $rotacion = '<span class="badge bg-danger text-md">BAJA</span>';
            }

            array_push($array, [
                "id" => $material->id,
                "codigo" => $material->code,
                "descripcion" => $material->full_name,
                "medida" => $material->measure,
                "unidad_medida" => ($material->unitMeasure == null) ? '':$material->unitMeasure->name,
                "stock_max" => $material->stock_max,
                "stock_min" => $material->stock_min,
                "stock_actual" => $material->stock_current,
                "prioridad" => $priority,
                "precio_unitario" => $material->price_final,
                "categoria" => ($material->category == null) ? '': $material->category->name,
                "sub_categoria" => ($material->subcategory == null) ? '': $material->subcategory->name,
                "tipo" => ($material->materialType == null) ? '': $material->materialType->name,
                "sub_tipo" => ($material->subType == null) ? '': $material->subType->name,
                "cedula" => ($material->warrant == null) ? '':$material->warrant->name,
                "calidad" => ($material->quality == null) ? '': $material->quality->name,
                "marca" => ($material->brand == null) ? '': $material->brand->name,
                "modelo" => ($material->exampler == null) ? '': $material->exampler->name,
                "retaceria" => ($material->typeScrap == null) ? '':$material->typeScrap->name,
                "image" => ($material->image == null || $material->image == "" ) ? 'no_image.png':$material->image,
                "rotation" => $rotacion,
                "update_price" => $material->state_update_price
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

    public function materialSeparatePack()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $arrayCategories = Category::where('id', '<>', 8)->select('id', 'name')->get()->toArray();

        $arrayCedulas = Warrant::select('id', 'name')->get()->toArray();

        $arrayCalidades = Quality::select('id', 'name')->get()->toArray();

        $arrayMarcas = Brand::select('id', 'name')->get()->toArray();

        $arrayRetacerias = Typescrap::select('id', 'name')->get()->toArray();

        $arrayRotations = [
            ["value" => "a", "display" => "ALTA"],
            ["value" => "m", "display" => "MEDIA"],
            ["value" => "b", "display" => "BAJA"]
        ];

        $materials = Material::where('isPack', 0)
            ->where('enable_status', 1)->get();

        //dd($array);

        $arrayMaterials = [];
        foreach ( $materials as $material )
        {
            array_push($arrayMaterials, [
                'id'=> $material->id,
                'full_name' => $material->full_name,
            ]);
        }

        return view('material.separatePack', compact( 'permissions', 'arrayCategories', 'arrayCedulas', 'arrayCalidades', 'arrayMarcas', 'arrayRetacerias', 'arrayRotations', 'arrayMaterials'));

    }

    public function storeSeparatePack(Request $request)
    {
        DB::beginTransaction();
        try {
            $material_id = $request->get('material_id');
            $quantityUnpack = $request->get('packs_separate');
            $materialUnpack = $request->get('material');

            $material = Material::find($material_id);

            if ( isset($material) )
            {
                // TODO: VERIFICAR EL TEMA DE LOS ITEMS
                $materialToUnpack = Material::find($materialUnpack);
                //$materialToUnpack->stock_unPack = $material->stock_unPack + ($quantityUnpack*$material->quantityPack);
                $materialToUnpack->stock_current = $materialToUnpack->stock_current + ($quantityUnpack*$material->quantityPack);
                $materialToUnpack->save();

                $material->stock_current = $material->stock_current - $quantityUnpack;
                $material->save();
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Separación de paquetes con éxito.'], 200);

    }

    public function getPriceListMaterialO($material)
    {
        $material = Material::find($material);

        if ( !isset($material) )
        {
            return response()->json(['message' => "No existe el material"], 422);
        } else {
            return response()->json([
                'priceList' => ($material->list_price == null) ? 0 : $material->list_price,
                'priceBase' => ($material->unit_price == null) ? 0 : $material->unit_price,
            ], 200);
        }
    }

    public function getPriceListMaterial($materialId, InventoryCostService $inventoryCostService)
    {
        $material = Material::with([
            'stockItems' => function ($query) {
                $query->where('is_active', true)
                    ->with([
                        'variant:id,material_id,attribute_summary,talla_id,color_id',
                        'variant.talla:id,name,short_name',
                        'variant.color:id,name,short_name',
                    ]);
            }
        ])->find($materialId);

        if (!$material) {
            return response()->json([
                'message' => 'No existe el material'
            ], 422);
        }

        // Aquí define cómo obtienes la lista de precios actual
        $priceListId = 1;

        $stockItems = $material->stockItems;

        if ($stockItems->isEmpty()) {
            return response()->json([
                'mode' => 'legacy',
                'material' => [
                    'id' => $material->id,
                    'full_name' => $material->full_name,
                ],
                'price_base' => $material->unit_price === null ? 0 : (float) $material->unit_price,
                'price_list' => $material->list_price === null ? 0 : (float) $material->list_price,
                'stock_items' => [],
            ], 200);
        }

        $stockItemIds = $stockItems->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->toArray();

        $avgCosts = $inventoryCostService->getAverageCostsByStockItem($stockItemIds);

        $priceListItems = PriceListItem::where('price_list_id', $priceListId)
            ->whereIn('stock_item_id', $stockItemIds)
            ->get()
            ->keyBy('stock_item_id');

        $rows = $stockItems->map(function ($stockItem) use ($priceListItems, $avgCosts) {
            $variantText = '';

            if ($stockItem->variant) {
                if (!empty($stockItem->variant->attribute_summary)) {
                    $variantText = $stockItem->variant->attribute_summary;
                } else {
                    $talla = optional($stockItem->variant->talla)->short_name
                        ?: optional($stockItem->variant->talla)->name;

                    $color = optional($stockItem->variant->color)->name;

                    $variantText = collect([$talla, $color])->filter()->implode(' / ');
                }
            }

            $priceListItem = $priceListItems->get($stockItem->id);

            return [
                'stock_item_id' => $stockItem->id,
                'display_name' => $stockItem->display_name,
                'sku' => $stockItem->sku,
                'barcode' => $stockItem->barcode,
                'variant_text' => $variantText,
                'price_base' => (float) ($avgCosts[$stockItem->id] ?? 0),
                'price_list_item_id' => optional($priceListItem)->id,
                'price_list' => $priceListItem ? (float) $priceListItem->price : 0,
            ];
        })->values()->toArray();

        $generalPriceBase = count($rows) === 1
            ? (float) ($rows[0]['price_base'] ?? 0)
            : null;

        return response()->json([
            'mode' => 'stock_items',
            'material' => [
                'id' => $material->id,
                'full_name' => $material->full_name,
            ],
            'price_base' => $generalPriceBase,
            'stock_items' => $rows,
        ], 200);
    }

    public function managePrice(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'material_id' => 'required|integer|exists:materials,id',
            'price_mode' => 'required|in:legacy,stock_items',
            'material_priceList' => 'nullable|numeric|min:0',
            'stock_items' => 'nullable|array',
            'stock_items.*.stock_item_id' => 'required_with:stock_items|integer|exists:stock_items,id',
            'stock_items.*.price_list' => 'required_with:stock_items|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $material = Material::find($request->material_id);

            if (!$material) {
                return response()->json([
                    'message' => 'No existe el material.'
                ], 422);
            }

            if ($request->price_mode === 'legacy') {
                $material->list_price = $request->material_priceList ?? 0;
                $material->save();

                DB::commit();

                return response()->json([
                    'message' => 'Precio de tienda actualizado correctamente.'
                ], 200);
            }

            $defaultPriceList = PriceList::where('is_default', true)
                ->where('is_active', true)
                ->first();

            if (!$defaultPriceList) {
                return response()->json([
                    'message' => 'No existe una lista de precios por defecto activa.'
                ], 422);
            }

            $stockItems = $request->stock_items ?? [];

            if (empty($stockItems)) {
                return response()->json([
                    'message' => 'No se enviaron stock items para actualizar.'
                ], 422);
            }

            foreach ($stockItems as $row) {
                $stockItem = StockItem::where('id', $row['stock_item_id'])
                    ->where('material_id', $material->id)
                    ->first();

                if (!$stockItem) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'Uno de los stock items no pertenece al material seleccionado.'
                    ], 422);
                }

                PriceListItem::updateOrCreate(
                    [
                        'price_list_id' => $defaultPriceList->id,
                        'stock_item_id' => $stockItem->id,
                    ],
                    [
                        'price' => (float) ($row['price_list'] ?? 0),
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Precios de tienda actualizados correctamente.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocurrió un error al guardar los precios.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPricePercentageMaterial($material)
    {
        $material = Material::find($material);

        if ( !isset($material) )
        {
            return response()->json(['message' => "No existe el material"], 422);
        } else {
            return response()->json(['pricePercentage' => ($material->percentage_price == null) ? 0 : 1-$material->percentage_price], 200);
        }
    }

    public function setPriceDirectoMaterial(Request $request)
    {
        DB::beginTransaction();
        try {
            $material_id = $request->get('material_id');
            $price = $request->get('material_priceList');

            $material = Material::find($material_id);

            if ( isset($material) )
            {
                $material->percentage_price = null;
                $material->list_price = $price;
                $material->save();
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Cambio de precio de lista con éxito.'], 200);
    }

    public function managePriceMaterial(Request $request)
    {
        DB::beginTransaction();
        try {
            $material_id = $request->get('material_id');
            $price_list = $request->get('material_priceList');
            /*$price_min = $request->get('material_priceMin');
            $price_max = $request->get('material_priceMax');*/
            $price_base = $request->get('material_priceBase');

            $material = Material::find($material_id);

            if ( isset($material) )
            {
                $material->percentage_price = null;
                $material->list_price = $price_list;
                $material->unit_price = $price_base;
                /*$material->min_price = $price_min;
                $material->max_price = $price_max;*/
                $material->save();
            }

            // Cambiar el precio en tienda
            $storeMaterials = StoreMaterial::where('material_id', $material_id)->get();
            foreach ( $storeMaterials as $storeMaterial ) {
                $storeMaterial->unit_price = $price_list;
                $storeMaterial->save();
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Cambio de precio de lista con éxito.'], 200);
    }

    public function setPricePorcentajeMaterial(Request $request)
    {
        DB::beginTransaction();
        try {
            $material_id = $request->get('material_id');
            $price = (float)($request->get('material_pricePercentage'));

            $material = Material::find($material_id);

            if ( isset($material) )
            {
                $material->percentage_price = (float)(1+($price/100));
                $material->list_price = ($material->unit_price*(1+($price/100)));;
                $material->save();
            }

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Cambio de precio de porcentaje con éxito.'], 200);
    }

    public function sendMaterialToStore($material_id)
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $material = Material::find($material_id);

        $data = DataGeneral::where('name', 'idWarehouseTienda')->first();

        $warehouse_id = $data->valueNumber; // TODO: HACERLO DINAMICO

        $shelves = Shelf::with('levels.containers.positions')
            ->where('warehouse_id', $warehouse_id)->get();

        $storeMaterials = StoreMaterial::where('material_id', $material_id)
            ->where('enable_status', 1)
            ->pluck('id');

        $locationIds = StoreMaterialLocation::whereIn('store_material_id', $storeMaterials)
            ->pluck('location_id')
            ->toArray();

        // TODO: Posiciones de material
        $positionIds = Location::whereIn('id', $locationIds)
            ->pluck('position_id')
            ->toArray();

        $storeMaterialsNotMaterial = StoreMaterial::where('material_id', '!=',$material_id)
            ->where('enable_status', 1)
            ->pluck('id');

        $locationIdsNotMaterial = StoreMaterialLocation::whereIn('store_material_id', $storeMaterialsNotMaterial)
            ->pluck('location_id')
            ->toArray();

        // TODO: Posiciones donde no hay material
        $positionIdsNotMaterial = Location::whereIn('id', $locationIdsNotMaterial)
            ->pluck('position_id')
            ->toArray();

        return view('material.sendToStore', compact( 'permissions', 'material', 'shelves', 'positionIds', 'positionIdsNotMaterial'));

    }

    public function guardarTraslado( Request $request )
    {
        $request->validate([
            'material_id'   => 'required|integer|exists:materials,id',
            'position_id'   => 'required|integer|exists:positions,id',
            'quantity'      => 'required|numeric|min:1',
            'unit_price'    => 'required|numeric|min:0.01',
            'fechas'        => 'nullable|array',
            'fechas.*'      => 'date|after_or_equal:today',
        ]);

        DB::beginTransaction();

        try {
            // 1. Buscar el material
            $material = Material::findOrFail($request->material_id);

            // 2. Crear StoreMaterial
            $storeMaterial = StoreMaterial::create([
                'material_id'    => $material->id,
                'full_name'      => $material->full_name,
                'stock_max'      => $material->stock_max,
                'stock_min'      => $material->stock_min,
                'stock_current'  => $request->quantity,
                'unit_price'     => $request->unit_price,
                'enable_status'  => 1,
                'codigo'         => $material->codigo,
                'isPack'         => $material->isPack,
                'quantityPack'   => $material->quantityPack,
            ]);

            // 3. Obtener location_id a partir del position_id
            $location = Location::where('position_id', $request->position_id)->first();
            if (!$location) {
                throw new \Exception("No se encontró una ubicación válida para esta posición.");
            }

            // 4. Crear StoreMaterialLocation
            StoreMaterialLocation::create([
                'store_material_id' => $storeMaterial->id,
                'location_id'       => $location->id,
            ]);

            // 5. Crear fechas de vencimiento
            foreach ($request->input('fechas', []) as $fecha) {
                StoreMaterialVencimiento::create([
                    'store_material_id'  => $storeMaterial->id,
                    'fecha_vencimiento'  => $fecha,
                ]);
            }

            // TODO: Falta disminuir del almacen general
            $material->stock_current = $material->stock_current - $request->quantity;
            $material->save();

            DB::commit();

            return response()->json(['message' => 'Traslado guardado correctamente.'], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error al guardar el traslado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function indexMaterialStore()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $arrayCategories = Category::where('id', '<>', 8)->select('id', 'name')->get()->toArray();

        $arrayCedulas = Warrant::select('id', 'name')->get()->toArray();

        $arrayCalidades = Quality::select('id', 'name')->get()->toArray();

        $arrayMarcas = Brand::select('id', 'name')->get()->toArray();

        $arrayRetacerias = Typescrap::select('id', 'name')->get()->toArray();

        $arrayRotations = [
            ["value" => "a", "display" => "ALTA"],
            ["value" => "m", "display" => "MEDIA"],
            ["value" => "b", "display" => "BAJA"]
        ];

        $materials = Material::where('isPack', 0)
            ->where('enable_status', 1)->get();

        //dd($array);

        $arrayMaterials = [];
        foreach ( $materials as $material )
        {
            array_push($arrayMaterials, [
                'id'=> $material->id,
                'full_name' => $material->full_name,
            ]);
        }

        $data = DataGeneral::where('name', 'idWarehouseTienda')->first();

        $warehouse_id = $data->valueNumber; // TODO: HACERLO DINAMICO

        $shelves = Shelf::with('levels.containers.positions')
            ->where('warehouse_id', $warehouse_id)->get();

        $rows = StoreMaterial::resumenPorMaterial()->get();

        // Calculamos el estado de stock
        foreach ($rows as $row) {
            if ($row->stock_total <= 0) {
                $row->estado = 'desabastecido';
            } elseif ($row->stock_total <= $row->stock_min) {
                $row->estado = 'por_desabastecer';
            } else {
                $row->estado = 'ok';
            }
        }

        $hayAlertas = $rows->contains(function ($r) {
            return $r->estado === 'desabastecido' || $r->estado === 'por_desabastecer';
        });

        return view('material.indexStore', compact( 'permissions', 'arrayCategories', 'arrayCedulas', 'arrayCalidades', 'arrayMarcas', 'arrayRetacerias', 'arrayRotations', 'arrayMaterials', 'shelves', 'rows', 'hayAlertas'));

    }

    public function getDataMaterialStore(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $description = $request->input('description');
        $code = $request->input('code');
        $category = $request->input('category');
        $subcategory = $request->input('subcategory');
        $material_type = $request->input('material_type');
        $sub_type = $request->input('sub_type');
        $cedula = $request->input('cedula');
        $calidad = $request->input('calidad');
        $marca = $request->input('marca');
        $retaceria = $request->input('retaceria');
        $rotation = $request->input('rotation');
        $isPack = $request->input('isPack');

        $materialIds = StoreMaterial::where('enable_status', 1)
            ->pluck('material_id')
            ->unique()
            ->toArray();

        $query = Material::with('category:id,name', 'materialType:id,name','unitMeasure:id,name','subcategory:id,name','subType:id,name','exampler:id,name','brand:id,name','warrant:id,name','quality:id,name','typeScrap:id,name')
            ->whereIn('id', $materialIds)
            ->where('enable_status', 1)
            ->orderBy('id');

        // Aplicar filtros si se proporcionan
        if ($description != "") {
            // Convertir la cadena de búsqueda en un array de palabras clave
            $keywords = explode(' ', $description);

            // Construir la consulta para buscar todas las palabras clave en el campo full_name
            $query->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->where('full_name', 'LIKE', '%' . $keyword . '%');
                }
            });

            // Asegurarse de que todas las palabras clave estén presentes en la descripción
            foreach ($keywords as $keyword) {
                $query->where('full_name', 'LIKE', '%' . $keyword . '%');
            }
        }

        if ($code != "") {
            $query->where('code', 'LIKE', '%'.$code.'%');
        }

        if ($category != "") {
            $query->where('category_id', $category);
        }

        if ($subcategory != "") {
            $query->where('subcategory_id', $subcategory);
        }

        if ($material_type != "") {
            $query->where('material_type_id', $material_type);
        }

        if ($sub_type != "") {
            $query->where('subtype_id', $sub_type);
        }

        if ($cedula != "") {
            $query->where('warrant_id', $cedula);
        }

        if ($calidad != "") {
            $query->where('quality_id', $calidad);
        }

        if ($marca != "") {
            $query->where('brand_id', $marca);
        }

        if ($retaceria != "") {
            $query->where('typescrap_id', $retaceria);
        }

        if ( $rotation != "" ) {
            $query->where('rotation', $rotation);
        }

        if ( $isPack != "" ) {
            $query->where('isPack', $isPack);
        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $materials = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        $array = [];

        foreach ( $materials as $material )
        {
            $priority = '';
            if ( $material->stock_current > $material->stock_max ){
                $priority = 'Completo';
            } else if ( $material->stock_current == $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current > $material->stock_min && $material->stock_current < $material->stock_max ){
                $priority = 'Aceptable';
            } else if ( $material->stock_current == $material->stock_min ){
                $priority = 'Por agotarse';
            } else if ( $material->stock_current < $material->stock_min || $material->stock_current == 0 ){
                $priority = 'Agotado';
            }

            $rotacion = "";
            if ( $material->rotation == "a" )
            {
                $rotacion = '<span class="badge bg-success text-md">ALTA</span>';
            } elseif ( $material->rotation == "m" ) {
                $rotacion = '<span class="badge bg-warning text-md">MEDIA</span>';
            } else {
                $rotacion = '<span class="badge bg-danger text-md">BAJA</span>';
            }

            array_push($array, [
                "id" => $material->id,
                "codigo" => $material->code,
                "descripcion" => $material->full_name,
                "medida" => $material->measure,
                "unidad_medida" => ($material->unitMeasure == null) ? '':$material->unitMeasure->name,
                "stock_max" => $material->stock_max,
                "stock_min" => $material->stock_min,
                "stock_actual" => $material->stock_store,
                "prioridad" => $priority,
                "precio_unitario" => $material->unit_price,
                "precio_lista" => $material->list_price,
                "categoria" => ($material->category == null) ? '': $material->category->name,
                "sub_categoria" => ($material->subcategory == null) ? '': $material->subcategory->name,
                "tipo" => ($material->materialType == null) ? '': $material->materialType->name,
                "sub_tipo" => ($material->subType == null) ? '': $material->subType->name,
                "cedula" => ($material->warrant == null) ? '':$material->warrant->name,
                "calidad" => ($material->quality == null) ? '': $material->quality->name,
                "marca" => ($material->brand == null) ? '': $material->brand->name,
                "modelo" => ($material->exampler == null) ? '': $material->exampler->name,
                "retaceria" => ($material->typeScrap == null) ? '':$material->typeScrap->name,
                "image" => ($material->image == null || $material->image == "" ) ? 'no_image.png':$material->image,
                "rotation" => $rotacion,
                "update_price" => $material->state_update_price,
                "isPack" => $material->isPack
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

    public function getFechasVencimiento($material_id)
    {
        $vencimientos = MaterialVencimiento::where('material_id', $material_id)
            ->orderBy('fecha_vencimiento', 'asc')
            ->get(['id', 'fecha_vencimiento']);

        return response()->json($vencimientos);
    }

    public function deleteFechasVencimiento($id)
    {
        $vencimiento = MaterialVencimiento::findOrFail($id);
        $vencimiento->delete();

        return response()->json(['message' => 'Fecha eliminada exitosamente']);
    }

    public function ocupadas($materialId)
    {
        $storeMaterials = StoreMaterial::where('material_id', $materialId)
            ->where('enable_status', 1)
            ->pluck('id');

        $locationIds = StoreMaterialLocation::whereIn('store_material_id', $storeMaterials)
            ->pluck('location_id')
            ->toArray();

        $positionIds = Location::whereIn('id', $locationIds)
            ->pluck('position_id')
            ->toArray();

        return response()->json([
            'locations' => $positionIds
        ]);
    }

    public function obtenerDetalleUbicacion(Request $request)
    {
        $position_id = $request->position_id;

        $location = Location::where('position_id', $position_id)->first();
        if (!$location) {
            return response()->json(['error' => 'Ubicación no encontrada'], 404);
        }

        $storeLocation = StoreMaterialLocation::where('location_id', $location->id)->first();
        if (!$storeLocation) {
            return response()->json(['error' => 'No hay material en esta ubicación'], 404);
        }

        $storeMaterial = StoreMaterial::find($storeLocation->store_material_id);
        if (!$storeMaterial) {
            return response()->json(['error' => 'Material no encontrado'], 404);
        }

        return response()->json([
            'store_material_id' => $storeMaterial->id,
            'store_material_location_id' => $storeLocation->id,
            'stock_current' => $storeMaterial->stock_current,
            'material_name' => $storeMaterial->full_name
        ]);
    }

    public function eliminarUbicacionOcupada(Request $request)
    {
        $storeMaterialLocation = StoreMaterialLocation::find($request->store_material_location_id);

        if (!$storeMaterialLocation) {
            return response()->json(['error' => 'Ubicación no encontrada'], 404);
        }

        $storeMaterial = StoreMaterial::find($storeMaterialLocation->store_material_id);
        if (!$storeMaterial) {
            return response()->json(['error' => 'Material no encontrado'], 404);
        }

        $material = Material::find($storeMaterial->material_id);
        if ($material) {
            // Devolver stock al material original
            $material->stock_current += $storeMaterial->stock_current;
            $material->save();
        }

        // Eliminar vencimientos y ubicaciones
        StoreMaterialVencimiento::where('store_material_id', $storeMaterial->id)->delete();
        StoreMaterialLocation::where('store_material_id', $storeMaterial->id)->delete();
        $storeMaterial->delete();

        return response()->json(['success' => true]);
    }

    public function selectAjax(Request $request)
    {
        $term = $request->get('q');

        $query = Material::query()
            ->select('id', 'code', 'full_name');
            /*->where('inventory', 1); */// solo los que manejan stock (ajusta si quieres)

        if ($term) {
            $term = trim($term);
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('full_name', 'like', "%{$term}%");
            });
        }

        $materials = $query
            ->orderBy('full_name')
            ->limit(20) // importante para no matar la BD
            ->get();

        // Formato compatible con select2
        $results = $materials->map(function ($m) {
            return [
                'id'   => $m->id,
                'text' => "{$m->code} - {$m->full_description}",
            ];
        });

        return response()->json($results);
    }

    public function selectStockItemsAjax(Request $request)
    {
        $term = trim($request->get('q', ''));

        $query = StockItem::query()
            ->with('material:id,full_name,code')
            ->select('id', 'material_id', 'sku', 'barcode', 'display_name')
            ->where('is_active', 1);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%")
                    ->orWhere('display_name', 'like', "%{$term}%")
                    ->orWhereHas('material', function ($mq) use ($term) {
                        $mq->where('code', 'like', "%{$term}%")
                            ->orWhere('full_name', 'like', "%{$term}%");
                    });
            });
        }

        $stockItems = $query
            ->orderBy('display_name')
            ->limit(20)
            ->get();

        $results = $stockItems->map(function ($item) {
            $code = $item->sku ?: optional($item->material)->code;
            $name = $item->display_name ?: optional($item->material)->full_name;

            return [
                'id'   => $item->id,
                'text' => trim(($code ? $code . ' - ' : '') . $name),
            ];
        });

        return response()->json($results);
    }

    public function selectWarehousesAjax(Request $request)
    {
        $term = trim($request->get('q', ''));

        $query = Warehouse::query()
            ->select('id', 'name')
            ->orderBy('name');

        if ($term !== '') {
            $query->where('name', 'like', "%{$term}%");
        }

        $warehouses = $query
            ->limit(20)
            ->get();

        $results = $warehouses->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'text' => $warehouse->name,
            ];
        });

        return response()->json($results);
    }

    public function select2Or(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 10;

        $query = Material::where('enable_status', 1);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('full_name', 'like', "%{$q}%")
                    ->orWhere('id', 'like', "%{$q}%");
            });
        }

        $total = (clone $query)->count();

        $items = $query
            ->orderBy('full_name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['id', 'full_name']);

        $results = $items->map(function ($m) {
            return [
                'id' => $m->id,
                'text' => $m->full_name,

                // extra para el JS
                'material_id' => $m->id,
                'codigo' => (string)$m->id,
                'descripcion' => $m->full_name,
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    public function select2(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 10;

        $query = StockItem::with([
            'material:id,full_name,enable_status',
            'variant:id,attribute_summary'
        ])
            ->where('is_active', 1)
            ->whereHas('material', function ($mq) {
                $mq->where('enable_status', 1);
            });

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('sku', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%")
                    ->orWhere('display_name', 'like', "%{$q}%")
                    ->orWhere('id', 'like', "%{$q}%")
                    ->orWhereHas('material', function ($mq) use ($q) {
                        $mq->where('full_name', 'like', "%{$q}%")
                            ->orWhere('id', 'like', "%{$q}%");
                    });
            });
        }

        $total = (clone $query)->count();

        $items = $query
            ->orderBy('display_name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = $items->map(function ($stockItem) {
            $material = $stockItem->material;

            $text = $stockItem->display_name
                ?: optional($material)->full_name
                    ?: 'Sin nombre';

            return [
                'id' => $stockItem->id,
                'text' => $text,

                // extra para el JS
                'stock_item_id' => $stockItem->id,
                'material_id' => $stockItem->material_id,

                'codigo' => $stockItem->sku ?: (string) $stockItem->id,
                'sku' => $stockItem->sku ?? '',
                'barcode' => $stockItem->barcode ?? '',

                'descripcion' => $text,
                'material_name' => optional($material)->full_name ?? '',
                'variant_text' => optional($stockItem->variant)->attribute_summary ?? '',
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ],
        ]);
    }

    public function getJsonMaterialStockItemsEntry()
    {
        $stockItems = StockItem::with([
            'material.category',
            'material.materialType',
            'material.unitMeasure',
            'material.subcategory',
            'material.subType',
            'material.exampler',
            'material.brand',
            'material.warrant',
            'material.quality',
            'material.typeScrap',
            'inventoryLevels',
        ])
            ->where('is_active', 1)
            ->get();

        $array = [];

        foreach ($stockItems as $stockItem) {
            $material = $stockItem->material;

            if (!$material) {
                continue;
            }

            $stockCurrent = $stockItem->inventoryLevels->sum('qty_on_hand');

            $array[] = [
                // importante: ahora el id principal es stock_item_id
                'id' => $stockItem->id,
                'stock_item_id' => $stockItem->id,
                'material_id' => $material->id,

                // display operativo
                'material' => $stockItem->display_name ?: $material->full_name,

                // compatibilidad
                'unit' => optional($stockItem->unitMeasure ?? $material->unitMeasure)->name ?? '',
                'code' => $stockItem->sku, // antes era material->code
                'barcode' => $stockItem->barcode,
                'price' => $material->price_final, // o desde price list si luego migras eso
                'typescrap' => $material->typescrap_id,
                'full_typescrap' => $material->typeScrap,
                'stock_current' => $stockCurrent,
                'category' => $material->category_id,
                'enable_status' => $material->enable_status,
                'tipo_venta_id' => $material->tipo_venta_id,
                'perecible' => $material->perecible ?? 'n',

                // nuevos flags útiles para ingreso
                'tracks_inventory' => (int) $stockItem->tracks_inventory,
            ];
        }

        return $array;
    }

    public function getJsonMaterialsForEntry(Request $request)
    {
        $search = trim($request->get('search', ''));

        $materials = Material::with([
            'unitMeasure',
            'typeScrap',
            'stockItems' => function ($q) {
                $q->where('is_active', 1)
                    ->with('inventoryLevels');
            }
        ])
            ->where('enable_status', 1)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('full_name', 'LIKE', "%{$search}%")
                        ->orWhereHas('stockItems', function ($stockQuery) use ($search) {
                            $stockQuery->where('is_active', 1)
                                ->where(function ($sq) use ($search) {
                                    $sq->where('sku', 'LIKE', "%{$search}%")
                                        ->orWhere('barcode', 'LIKE', "%{$search}%")
                                        ->orWhere('display_name', 'LIKE', "%{$search}%");
                                });
                        });
                });
            })
            ->limit(30)
            ->get();

        $array = [];

        foreach ($materials as $material) {
            $stockItems = $material->stockItems;

            $activeStockItemsCount = $stockItems->count();

            $stockCurrent = $stockItems->sum(function ($stockItem) {
                return $stockItem->inventoryLevels->sum('qty_on_hand');
            });

            $simpleStockItem = null;

            if ($activeStockItemsCount === 1) {
                $simpleStockItem = $stockItems->first();
            }

            $hasVariants = $stockItems->whereNotNull('variant_id')->count() > 0;

            $array[] = [
                'id' => $material->id,
                'material_id' => $material->id,

                'material' => $material->full_name,
                'unit' => optional($material->unitMeasure)->name ?? '',

                'price' => 0,
                'typescrap' => $material->typescrap_id,
                'full_typescrap' => $material->typeScrap,
                'stock_current' => $stockCurrent,
                'category' => $material->category_id,
                'enable_status' => $material->enable_status,
                'tipo_venta_id' => $material->tipo_venta_id,
                'perecible' => $material->perecible ?? 'n',

                'has_variants' => $hasVariants,
                'stock_items_count' => $activeStockItemsCount,

                'stock_item_id' => optional($simpleStockItem)->id,
                'stock_item_sku' => optional($simpleStockItem)->sku,
                'stock_item_barcode' => optional($simpleStockItem)->barcode,
                'stock_item_display_name' => optional($simpleStockItem)->display_name,
            ];
        }

        return response()->json($array);
    }

    public function getJsonMaterialsForOrderPurchase(Request $request)
    {
        $search = trim($request->get('search', ''));

        $materials = Material::with([
            'unitMeasure',
            'typeScrap',
            'stockItems' => function ($q) {
                $q->where('is_active', 1)
                    ->with([
                        'inventoryLevels',
                        'priceListItems.priceList'
                    ]);
            }
        ])
            ->where('enable_status', 1)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('full_name', 'LIKE', "%{$search}%")
                        ->orWhereHas('stockItems', function ($stockQuery) use ($search) {
                            $stockQuery->where('is_active', 1)
                                ->where(function ($sq) use ($search) {
                                    $sq->where('sku', 'LIKE', "%{$search}%")
                                        ->orWhere('barcode', 'LIKE', "%{$search}%")
                                        ->orWhere('display_name', 'LIKE', "%{$search}%");
                                });
                        });
                });
            })
            ->limit(30)
            ->get();

        $array = [];

        foreach ($materials as $material) {
            $stockItems = $material->stockItems;

            $activeStockItemsCount = $stockItems->count();

            $stockCurrent = $stockItems->sum(function ($stockItem) {
                return $stockItem->inventoryLevels->sum('qty_on_hand');
            });

            $simpleStockItem = null;

            if ($activeStockItemsCount === 1) {
                $simpleStockItem = $stockItems->first();
            }

            $hasVariants = $stockItems->whereNotNull('variant_id')->count() > 0;

            $array[] = [
                'id' => $material->id,
                'material_id' => $material->id,

                'material' => $material->full_name,
                'unit' => optional($material->unitMeasure)->name ?? '',

                'price' => $simpleStockItem ? $simpleStockItem->list_price : 0,
                'typescrap' => $material->typescrap_id,
                'full_typescrap' => $material->typeScrap,
                'stock_current' => $stockCurrent,
                'category' => $material->category_id,
                'enable_status' => $material->enable_status,
                'tipo_venta_id' => $material->tipo_venta_id,
                'perecible' => $material->perecible ?? 'n',

                'has_variants' => $hasVariants,
                'stock_items_count' => $activeStockItemsCount,

                'stock_item_id' => optional($simpleStockItem)->id,
                'stock_item_sku' => optional($simpleStockItem)->sku,
                'stock_item_barcode' => optional($simpleStockItem)->barcode,
                'stock_item_display_name' => optional($simpleStockItem)->display_name,
            ];
        }

        return response()->json($array);
    }

    public function getStockItemsForEntry($materialId)
    {
        $stockItems = StockItem::with([
            'variant',
            'priceListItems.priceList'
        ])
            ->where('material_id', $materialId)
            ->where('is_active', 1)
            ->whereNotNull('variant_id')
            ->orderBy('display_name', 'asc')
            ->get();

        $array = [];

        foreach ($stockItems as $stockItem) {
            $variant = $stockItem->variant;

            $array[] = [
                'stock_item_id' => $stockItem->id,
                'material_id' => $stockItem->material_id,
                'variant_id' => $stockItem->variant_id,
                'price' => $stockItem->list_price,
                'attribute_summary' => optional($variant)->attribute_summary ?? $stockItem->display_name,
                'sku' => $stockItem->sku,
                'barcode' => $stockItem->barcode,
                'display_name' => $stockItem->display_name,
            ];
        }

        return response()->json($array);
    }
}
