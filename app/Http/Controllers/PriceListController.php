<?php

namespace App\Http\Controllers;

use App\Company;
use App\Material;
use App\PriceList;
use App\PriceListItem;
use App\PriceListMaterial;
use App\StockItem;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PriceListController extends Controller
{
    public function index()
    {
        $companyId = TenantContext::companyId();

        $company = Company::query()
            ->where('tenant_id', TenantContext::tenantId())
            ->where('id', $companyId)
            ->firstOrFail();

        return view(
            'priceList.index',
            compact('company')
        );
    }

    public function getPriceLists()
    {
        $companyId = TenantContext::companyId();

        $priceLists = PriceList::query()
            ->where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $priceLists,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'currency' => [
                'required',
                'string',
                'max:3',
            ],

            'is_default' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        if (
            (bool) $request->is_default &&
            !(bool) $request->is_active
        ) {
            return response()->json([
                'message' =>
                    'Una lista predeterminada debe estar activa.',
            ], 422);
        }

        $companyId = TenantContext::companyId();
        $tenantId = TenantContext::tenantId();

        $exists = PriceList::query()
            ->where(
                'company_id',
                $companyId
            )
            ->whereRaw(
                'LOWER(name) = ?',
                [
                    strtolower(
                        trim($request->name)
                    )
                ]
            )
            ->exists();

        if ($exists) {
            return response()->json([
                'message' =>
                    'Ya existe una lista de precios con ese nombre para esta empresa.',
            ], 422);
        }

        DB::transaction(function () use ($request, $companyId, $tenantId, &$priceList) {
            $isDefault =
                (bool) $request->is_default;

            /*
             * Si es la primera lista,
             * automáticamente será default.
             */
            $hasLists = PriceList::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->exists();

            if (!$hasLists) {
                $isDefault = true;
            }

            if ($isDefault) {
                PriceList::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->update([
                        'is_default' => false,
                    ]);
            }

            $priceList = PriceList::create([
                'tenant_id' =>
                    $tenantId,

                'company_id' =>
                    $companyId,

                'name' =>
                    strtoupper(
                        trim($request->name)
                    ),

                'currency' =>
                    strtoupper(
                        $request->currency
                    ),

                'is_default' =>
                    $isDefault,

                'is_active' => (bool) $request->is_active,
            ]);
        });

        return response()->json([
            'message' =>
                'Lista de precios creada correctamente.',

            'data' =>
                $priceList,
        ]);
    }

    public function update(Request $request, PriceList $priceList) {
        $this->validatePriceListTenant(
            $priceList
        );

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'currency' => [
                'required',
                'string',
                'max:3',
            ],

            'is_default' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $duplicate = PriceList::query()
            ->where(
                'company_id',
                $priceList->company_id
            )
            ->where(
                'id',
                '<>',
                $priceList->id
            )
            ->whereRaw(
                'LOWER(name) = ?',
                [
                    strtolower(
                        trim($request->name)
                    )
                ]
            )
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' =>
                    'Ya existe una lista con ese nombre en esta empresa.',
            ], 422);
        }

        DB::transaction(function () use ($request, $priceList) {
            if ((bool) $request->is_default) {
                PriceList::query()
                    ->where(
                        'company_id',
                        $priceList->company_id
                    )
                    ->where(
                        'id',
                        '<>',
                        $priceList->id
                    )
                    ->update([
                        'is_default' => false,
                    ]);
            }

            if (
                $priceList->is_default &&
                !(bool) $request->is_active
            ) {
                return response()->json([
                    'message' => 'No puede desactivar la lista predeterminada. Asigne primero otra lista como predeterminada.',
                ], 422);
            }

            if (
                (bool) $request->is_default &&
                !(bool) $request->is_active
            ) {
                return response()->json([
                    'message' => 'Una lista predeterminada debe estar activa.',
                ], 422);
            }

            $priceList->update([
                'name' =>
                    strtoupper(
                        trim($request->name)
                    ),

                'currency' =>
                    strtoupper(
                        $request->currency
                    ),

                'is_default' =>
                    (bool) $request->is_default,

                'is_active' =>
                    (bool) $request->is_active,
            ]);
        });

        return response()->json([
            'message' =>
                'Lista actualizada correctamente.',
        ]);
    }

    private function validatePriceListTenant(PriceList $priceList): void {
        if (
            (int) $priceList->tenant_id !==
            (int) TenantContext::tenantId()
        ) {
            abort(404);
        }
    }

    public function getMaterials(Request $request, PriceList $priceList) {
        $this->validatePriceListTenant(
            $priceList
        );

        $search = trim(
            $request->get(
                'search',
                ''
            )
        );

        $companyId =
            $priceList->company_id;

        $materials = Material::query()

            ->where(
                'enable_status',
                1
            )

            ->whereHas(
                'stockItems',
                function ($query) use ($companyId) {
                    $query
                        ->where(
                            'is_active',
                            1
                        )
                        ->enabledForCompany(
                            $companyId
                        );
                }
            )

            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(
                        'full_name',
                        'LIKE',
                        "%{$search}%"
                    );
                }
            )

            ->with([
                'stockItems' => function ($query) use ($companyId) {
                    $query
                        ->where(
                            'is_active',
                            1
                        )
                        ->enabledForCompany(
                            $companyId
                        )
                        ->select([
                            'id',
                            'material_id',
                            'display_name',
                            'sku',
                            'variant_id',
                        ]);
                },

                'priceListMaterials' => function ($query) use ($priceList) {
                    $query->where(
                        'price_list_id',
                        $priceList->id
                    );
                }
            ])

            ->orderBy(
                'full_name'
            )

            ->paginate(5);

        $materials->getCollection()
            ->transform(function ($material) {

                $price =
                    $material
                        ->priceListMaterials
                        ->first();

                return [
                    'id' =>
                        $material->id,

                    'name' =>
                        $material->full_name,

                    'price' =>
                        $price
                            ? (float) $price->price
                            : null,

                    'has_price' =>
                        $price !== null,

                    'stock_items_count' =>
                        $material
                            ->stockItems
                            ->count(),
                ];
            });

        return response()->json(
            $materials
        );
    }

    public function saveMaterialPrices(Request $request, PriceList $priceList) {
        $this->validatePriceListTenant(
            $priceList
        );

        $request->validate([
            'prices' =>
                'required|array',

            'prices.*.material_id' =>
                'required|integer',

            'prices.*.price' =>
                'nullable|numeric|min:0',
        ]);

        if (!$priceList->is_active) {
            return response()->json([
                'message' => 'La lista de precios está inactiva. Actívela antes de modificar sus precios.',
            ], 422);
        }

        $companyId =
            $priceList->company_id;

        DB::transaction(function () use (
            $request,
            $priceList,
            $companyId
        ) {
            foreach (
                $request->prices
                as $row
            ) {
                $material = Material::query()
                    ->where(
                        'id',
                        $row['material_id']
                    )

                    ->whereHas(
                        'stockItems',
                        function ($query) use ($companyId) {
                            $query
                                ->where(
                                    'is_active',
                                    1
                                )
                                ->enabledForCompany(
                                    $companyId
                                );
                        }
                    )

                    ->firstOrFail();

                /*
                 * Campo vacío:
                 * eliminar precio base.
                 */
                if (
                    $row['price'] === null ||
                    $row['price'] === ''
                ) {
                    PriceListMaterial::query()
                        ->where(
                            'price_list_id',
                            $priceList->id
                        )
                        ->where(
                            'material_id',
                            $material->id
                        )
                        ->delete();

                    continue;
                }

                PriceListMaterial::updateOrCreate(
                    [
                        'price_list_id' =>
                            $priceList->id,

                        'material_id' =>
                            $material->id,
                    ],
                    [
                        'price' =>
                            $row['price'],
                    ]
                );
            }
        });

        return response()->json([
            'message' =>
                'Precios guardados correctamente.',
        ]);
    }

    public function getMaterialVariants(PriceList $priceList, Material $material) {
        $this->validatePriceListTenant(
            $priceList
        );

        $companyId =
            TenantContext::companyId();

        /*
         * La PriceList debe pertenecer a la Company actual.
         */
        if (
            (int) $priceList->company_id !==
            (int) $companyId
        ) {
            abort(404);
        }

        /*
         * El Material debe tener al menos un StockItem
         * habilitado para esta Company.
         */
        $materialExists = Material::query()
            ->where(
                'id',
                $material->id
            )
            ->whereHas(
                'stockItems',
                function ($query) use ($companyId) {
                    $query
                        ->where(
                            'is_active',
                            1
                        )
                        ->enabledForCompany(
                            $companyId
                        );
                }
            )
            ->exists();

        if (!$materialExists) {
            abort(404);
        }

        /*
         * Precio base del Material para esta PriceList.
         */
        $materialPrice =
            PriceListMaterial::query()
                ->where(
                    'price_list_id',
                    $priceList->id
                )
                ->where(
                    'material_id',
                    $material->id
                )
                ->first();

        /*
         * Solo StockItems habilitados para la Company actual.
         */
        $stockItems = StockItem::query()
            ->with([
                'variant'
            ])
            ->where(
                'material_id',
                $material->id
            )
            ->where(
                'is_active',
                1
            )
            ->enabledForCompany(
                $companyId
            )
            ->orderBy(
                'display_name'
            )
            ->get();

        /*
         * Overrides existentes.
         */
        $overrides =
            PriceListItem::query()
                ->where(
                    'price_list_id',
                    $priceList->id
                )
                ->whereIn(
                    'stock_item_id',
                    $stockItems->pluck('id')
                )
                ->get()
                ->keyBy(
                    'stock_item_id'
                );

        $basePrice =
            $materialPrice
                ? (float) $materialPrice->price
                : null;

        $data =
            $stockItems->map(
                function ($stockItem) use (
                    $overrides,
                    $basePrice
                ) {
                    $override =
                        $overrides->get(
                            $stockItem->id
                        );

                    return [
                        'stock_item_id' =>
                            $stockItem->id,

                        'variant_id' =>
                            $stockItem->variant_id,

                        'name' =>
                            optional(
                                $stockItem->variant
                            )->attribute_summary
                                ?: $stockItem->display_name,

                        'sku' =>
                            $stockItem->sku,

                        'barcode' =>
                            $stockItem->barcode,

                        'base_price' =>
                            $basePrice,

                        'override_price' =>
                            $override
                                ? (float) $override->price
                                : null,

                        'effective_price' =>
                            $override
                                ? (float) $override->price
                                : $basePrice,

                        'source' =>
                            $override
                                ? 'stock_item'
                                : (
                            $basePrice !== null
                                ? 'material'
                                : 'none'
                            ),
                    ];
                }
            );

        return response()->json([
            'material' => [
                'id' =>
                    $material->id,

                'name' =>
                    $material->full_name,

                'base_price' =>
                    $basePrice,
            ],

            'data' =>
                $data,
        ]);
    }

    public function saveMaterialVariants(Request $request, PriceList $priceList, Material $material) {
        $this->validatePriceListTenant(
            $priceList
        );

        $companyId =
            TenantContext::companyId();

        if (
            (int) $priceList->company_id !==
            (int) $companyId
        ) {
            abort(404);
        }

        $request->validate([
            'items' =>
                'required|array',

            'items.*.stock_item_id' =>
                'required|integer',

            'items.*.price' =>
                'nullable|numeric|min:0',
        ]);

        if (!$priceList->is_active) {
            return response()->json([
                'message' => 'La lista de precios está inactiva. Actívela antes de modificar sus precios.',
            ], 422);
        }

        DB::transaction(
            function () use (
                $request,
                $priceList,
                $material,
                $companyId
            ) {
                foreach (
                    $request->items
                    as $row
                ) {
                    /*
                     * Seguridad:
                     *
                     * - StockItem pertenece al Material
                     * - está activo
                     * - está habilitado para Company actual
                     */
                    $stockItem =
                        StockItem::query()
                            ->where(
                                'id',
                                $row['stock_item_id']
                            )
                            ->where(
                                'material_id',
                                $material->id
                            )
                            ->where(
                                'is_active',
                                1
                            )
                            ->enabledForCompany(
                                $companyId
                            )
                            ->firstOrFail();

                    /*
                     * Campo vacío:
                     *
                     * eliminar override
                     * y volver a precio del Material.
                     */
                    if (
                        $row['price'] === null ||
                        $row['price'] === ''
                    ) {
                        PriceListItem::query()
                            ->where(
                                'price_list_id',
                                $priceList->id
                            )
                            ->where(
                                'stock_item_id',
                                $stockItem->id
                            )
                            ->delete();

                        continue;
                    }

                    PriceListItem::updateOrCreate(
                        [
                            'price_list_id' =>
                                $priceList->id,

                            'stock_item_id' =>
                                $stockItem->id,
                        ],
                        [
                            'price' =>
                                $row['price'],
                        ]
                    );
                }
            }
        );

        return response()->json([
            'message' =>
                'Precios de variantes guardados correctamente.',
        ]);
    }
}
