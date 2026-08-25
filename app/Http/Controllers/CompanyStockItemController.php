<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Category;
use App\Company;
use App\CompanyStockItem;
use App\StockItem;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyStockItemController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $tenantId =
            TenantContext::tenantId();

        /*
         * Owner:
         * puede ver todas las Companies del Tenant.
         *
         * Usuario normal:
         * únicamente sus Companies activas.
         */
        if ($user->isTenantOwner()) {

            $companies =
                Company::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->orderBy(
                        'business_name'
                    )
                    ->get();

        } else {

            $companies =
                $user->companies()
                    ->where(
                        'companies.tenant_id',
                        $tenantId
                    )
                    ->where(
                        'companies.is_active',
                        true
                    )
                    ->wherePivot(
                        'is_active',
                        true
                    )
                    ->orderBy(
                        'companies.business_name'
                    )
                    ->get();
        }


        $brands =
            Brand::query()
                ->orderBy('name')
                ->get();

        $categories =
            Category::query()
                ->orderBy('name')
                ->get();


        return view(
            'companyStockItem.index',
            compact(
                'companies',
                'brands',
                'categories'
            )
        );
    }

    private function resolveAllowedCompany($companyId){
        $user =
            auth()->user();

        $tenantId =
            TenantContext::tenantId();


        if ($user->isTenantOwner()) {

            return Company::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(
                    'is_active',
                    true
                )
                ->findOrFail(
                    $companyId
                );
        }


        $company =
            $user->companies()
                ->where(
                    'companies.id',
                    $companyId
                )
                ->where(
                    'companies.tenant_id',
                    $tenantId
                )
                ->where(
                    'companies.is_active',
                    true
                )
                ->wherePivot(
                    'is_active',
                    true
                )
                ->first();


        if (!$company) {
            abort(
                403,
                'No tiene acceso a la empresa seleccionada.'
            );
        }


        return $company;
    }

    public function getData(Request $request)
    {
        $company =
            $this->resolveAllowedCompany(
                $request->input(
                    'company_id'
                )
            );


        $search =
            trim(
                $request->input(
                    'search',
                    ''
                )
            );

        $brandId =
            $request->input(
                'brand_id'
            );

        $categoryId =
            $request->input(
                'category_id'
            );

        $status =
            $request->input(
                'status',
                'all'
            );


        $query =
            StockItem::query()
                ->with([
                    'material.brand',
                    'material.category',
                    'variant.talla',
                    'variant.color',
                ])
                ->where(
                    'stock_items.is_active',
                    true
                );


        if ($search !== '') {

            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'stock_items.sku',
                        'like',
                        '%' . $search . '%'
                    )
                        ->orWhere(
                            'stock_items.barcode',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'stock_items.display_name',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhereHas(
                            'material',
                            function ($materialQuery) use ($search) {

                                $materialQuery
                                    ->where(
                                        'full_name',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'description',
                                        'like',
                                        '%' . $search . '%'
                                    );
                            }
                        );
                }
            );
        }


        if ($brandId) {

            $query->whereHas(
                'material',
                function ($materialQuery) use ($brandId) {

                    $materialQuery->where(
                        'brand_id',
                        $brandId
                    );
                }
            );
        }


        if ($categoryId) {

            $query->whereHas(
                'material',
                function ($materialQuery) use ($categoryId) {

                    $materialQuery->where(
                        'category_id',
                        $categoryId
                    );
                }
            );
        }


        /*
         * Filtrado de estado comercial.
         */

        if ($status === 'enabled') {

            $query->enabledForCompany(
                $company->id
            );

        } elseif ($status === 'disabled') {

            $query->whereDoesntHave(
                'companyStockItems',
                function ($q) use ($company) {

                    $q->where(
                        'company_id',
                        $company->id
                    )
                        ->where(
                            'is_active',
                            true
                        );
                }
            );
        }


        $stockItems =
            $query
                ->orderBy(
                    'stock_items.display_name'
                )
                ->paginate(20);


        /*
         * Marcamos si cada SKU está habilitado
         * para la Company seleccionada.
         */

        $enabledIds =
            CompanyStockItem::query()
                ->where(
                    'company_id',
                    $company->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereIn(
                    'stock_item_id',
                    $stockItems
                        ->pluck('id')
                )
                ->pluck(
                    'stock_item_id'
                )
                ->flip();


        $rows =
            $stockItems
                ->getCollection()
                ->map(
                    function ($stockItem) use ($enabledIds) {

                        $variantText =
                            'Simple';

                        if ($stockItem->variant) {

                            $parts = [];

                            if (
                            $stockItem->variant->talla
                            ) {
                                $parts[] =
                                    $stockItem
                                        ->variant
                                        ->talla
                                        ->short_name
                                        ?:
                                        $stockItem
                                            ->variant
                                            ->talla
                                            ->name;
                            }

                            if (
                            $stockItem->variant->color
                            ) {
                                $parts[] =
                                    $stockItem
                                        ->variant
                                        ->color
                                        ->name;
                            }

                            if (!empty($parts)) {
                                $variantText =
                                    implode(
                                        ' / ',
                                        $parts
                                    );
                            }
                        }


                        return [
                            'id' =>
                                $stockItem->id,

                            'material' =>
                                optional(
                                    $stockItem->material
                                )->full_name,

                            'brand' =>
                                optional(
                                    optional(
                                        $stockItem->material
                                    )->brand
                                )->name,

                            'category' =>
                                optional(
                                    optional(
                                        $stockItem->material
                                    )->category
                                )->name,

                            'variant' =>
                                $variantText,

                            'sku' =>
                                $stockItem->sku,

                            'barcode' =>
                                $stockItem->barcode,

                            'enabled' =>
                                isset(
                                    $enabledIds[$stockItem->id]
                                ),
                        ];
                    }
                );


        $stockItems->setCollection(
            $rows
        );


        return response()->json(
            $stockItems
        );
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'company_id' => [
                'required',
                'integer',
            ],

            'stock_item_id' => [
                'required',
                'integer',
            ],

            'enabled' => [
                'required',
                'boolean',
            ],
        ]);


        $company =
            $this->resolveAllowedCompany(
                $request->input(
                    'company_id'
                )
            );


        /*
         * StockItem queda protegido por TenantScope.
         */
        $stockItem =
            StockItem::findOrFail(
                $request->input(
                    'stock_item_id'
                )
            );


        /*
         * Seguridad adicional:
         * Company y StockItem deben pertenecer
         * al mismo Tenant.
         */

        if (
            (int)$company->tenant_id !==
            (int)$stockItem->tenant_id
        ) {
            abort(
                422,
                'El producto no pertenece al mismo grupo empresarial.'
            );
        }


        $companyStockItem =
            CompanyStockItem::firstOrNew([
                'company_id' =>
                    $company->id,

                'stock_item_id' =>
                    $stockItem->id,
            ]);


        $companyStockItem->is_active =
            (bool)$request->input(
                'enabled'
            );

        $companyStockItem->save();


        return response()->json([
            'message' =>
                $companyStockItem->is_active
                    ? 'Producto habilitado para la empresa.'
                    : 'Producto deshabilitado para la empresa.',

            'enabled' =>
                $companyStockItem->is_active,
        ]);
    }

}