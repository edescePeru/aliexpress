<?php

namespace App\Http\Controllers;

use App\Company;
use App\Material;
use App\PriceList;
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
        $tenantId = TenantContext::tenantId();

        $companies = Company::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('business_name')
            ->get([
                'id',
                'business_name',
                'ruc',
            ]);

        $currentCompanyId =
            TenantContext::companyId();

        return view(
            'priceList.index',
            compact(
                'companies',
                'currentCompanyId'
            )
        );
    }

    public function getPriceLists($companyId)
    {
        $company = $this->getCompanyForCurrentTenant(
            $companyId
        );

        $priceLists = PriceList::query()
            ->where(
                'company_id',
                $company->id
            )
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get([
                'id',
                'company_id',
                'name',
                'currency',
                'is_default',
                'is_active',
            ]);

        return response()->json([
            'data' => $priceLists,
        ]);
    }

    private function getCompanyForCurrentTenant(int $companyId): Company
    {
        return Company::query()
            ->where(
                'tenant_id',
                TenantContext::tenantId()
            )
            ->where(
                'id',
                $companyId
            )
            ->firstOrFail();
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => [
                'required',
                'integer',
            ],

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
        ]);

        $company = $this->getCompanyForCurrentTenant(
            (int) $request->company_id
        );

        $exists = PriceList::query()
            ->where(
                'company_id',
                $company->id
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

        DB::transaction(function () use ($request, $company, &$priceList) {
            $isDefault =
                (bool) $request->is_default;

            /*
             * Si es la primera lista,
             * automáticamente será default.
             */
            $hasLists = PriceList::query()
                ->where(
                    'company_id',
                    $company->id
                )
                ->exists();

            if (!$hasLists) {
                $isDefault = true;
            }

            if ($isDefault) {
                PriceList::query()
                    ->where(
                        'company_id',
                        $company->id
                    )
                    ->update([
                        'is_default' => false,
                    ]);
            }

            $priceList = PriceList::create([
                'tenant_id' =>
                    TenantContext::tenantId(),

                'company_id' =>
                    $company->id,

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

                'is_active' =>
                    true,
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

            ->paginate(15);

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
}
