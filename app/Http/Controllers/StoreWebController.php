<?php

namespace App\Http\Controllers;

use App\Category;
use App\Color;
use App\DataGeneral;
use App\Material;
use App\PriceList;
use App\PriceListItem;
use App\Talla;
use Illuminate\Http\Request;

class StoreWebController extends Controller
{
    public function home()
    {
        $dataLogotipoEmpresa = DataGeneral::where('name', 'logotipo')->first();
        $logotipoEmpresa = $dataLogotipoEmpresa->valueText;
        return view('shop.home', compact('logotipoEmpresa'));
    }

    public function tienda()
    {
        $dataLogotipoEmpresa = DataGeneral::where('name', 'logotipo')->first();
        $logotipoEmpresa = $dataLogotipoEmpresa->valueText;

        $defaultPriceList = PriceList::where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        $maxPrice = 0;

        if ($defaultPriceList) {
            $maxPrice = PriceListItem::where('price_list_id', $defaultPriceList->id)
                ->max('price');
        }

        $maxPrice = ceil($maxPrice ?? 0);

        $dataWhatsappEmpresa = DataGeneral::where('name', 'whatsapp')->first();
        $whatsappEmpresa = $dataWhatsappEmpresa->valueText;
        $whatsappEmpresa = preg_replace('/\D/', '', $whatsappEmpresa);
        //return view('shop.catalogNoPrice', compact('logotipoEmpresa', 'maxPrice'));
        return view('shop.catalogNoPrice', compact('logotipoEmpresa', 'maxPrice', 'whatsappEmpresa'));
    }

    public function catalog()
    {
        $dataLogotipoEmpresa = DataGeneral::where('name', 'logotipo')->first();
        $logotipoEmpresa = $dataLogotipoEmpresa->valueText;

        $defaultPriceList = PriceList::where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        $maxPrice = 0;

        if ($defaultPriceList) {
            $maxPrice = PriceListItem::where('price_list_id', $defaultPriceList->id)
                ->max('price');
        }

        $maxPrice = ceil($maxPrice ?? 0);

        $dataWhatsappEmpresa = DataGeneral::where('name', 'whatsapp')->first();
        $whatsappEmpresa = $dataWhatsappEmpresa->valueText;
        $whatsappEmpresa = preg_replace('/\D/', '', $whatsappEmpresa);

        $dataShowPricesCatalogEmpresa = DataGeneral::where('name', 'show_prices_catalog')->first();
        $showPricesCatalogEmpresa = $dataShowPricesCatalogEmpresa->valueText;
        $dataShowPresentations = DataGeneral::where('name', 'show_presentations')->first();
        $showPresentationsEmpresa = $dataShowPresentations->valueText;

        //return view('shop.catalogNoPrice', compact('logotipoEmpresa', 'maxPrice'));
        return view('shop.catalog', compact('logotipoEmpresa', 'maxPrice', 'whatsappEmpresa', 'showPricesCatalogEmpresa', 'showPresentationsEmpresa'));
    }

    public function getDataProductsV2(Request $request, $pageNumber = 1)
    {
        $perPage = 9;
        $categoryId = trim((string) $request->input('category_id', ''));
        $subcategoryId = trim((string) $request->input('subcategory_id', ''));
        $productSearch = trim((string) $request->input('product_search', ''));
        $sizeIds = $request->input('size_ids', []);
        if (!is_array($sizeIds)) {
            $sizeIds = explode(',', $sizeIds);
        }

        $sizeIds = collect($sizeIds)
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->toArray();

        $colorIds = $request->input('color_ids', []);
        if (!is_array($colorIds)) {
            $colorIds = explode(',', $colorIds);
        }

        $colorIds = collect($colorIds)
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->toArray();

        $minPrice = trim((string) $request->input('min_price', ''));
        $maxPrice = trim((string) $request->input('max_price', ''));

        $defaultPriceList = PriceList::where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        /**
         * =========================================================
         * 1) MATERIALS PADRES
         *    La búsqueda sigue usando stock_items:
         *    sku, barcode, display_name + material.full_name
         * =========================================================
         */
        $materialsQuery = Material::with([
            'category:id,description',
            'unitMeasure:id,description',
            'typeTax:id,tax',
            'tipoVenta:id',
            'stockItems' => function ($q) use ($defaultPriceList) {
                $q->where('is_active', 1)
                    ->with([
                        'variant',
                        'inventoryLevels:id,stock_item_id,qty_on_hand,qty_reserved',
                        'priceListItems' => function ($pq) use ($defaultPriceList) {
                            if ($defaultPriceList) {
                                $pq->where('price_list_id', $defaultPriceList->id);
                            } else {
                                $pq->whereRaw('1 = 0');
                            }
                        }
                    ]);
            }
        ])
            ->where('enable_status', 1);

        if ($categoryId !== '') {
            $materialsQuery->where('category_id', $categoryId);
        }

        if ($subcategoryId !== '') {
            $materialsQuery->where('subcategory_id', $subcategoryId);
        }

        if ($productSearch !== '') {
            $search = $productSearch;

            $materialsQuery->where(function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('codigo', 'like', '%' . $search . '%')
                    ->orWhereHas('stockItems', function ($sq) use ($search) {
                        $sq->where('is_active', 1)
                            ->where(function ($ssq) use ($search) {
                                $ssq->where('sku', 'like', '%' . $search . '%')
                                    ->orWhere('barcode', 'like', '%' . $search . '%')
                                    ->orWhere('display_name', 'like', '%' . $search . '%');
                            });
                    });
            });

            $materialsQuery->orderByRaw("
            CASE
                WHEN full_name = ? THEN 0
                WHEN code = ? THEN 0
                WHEN codigo = ? THEN 0
                ELSE 1
            END
        ", [$search, $search, $search]);
        }

        if (!empty($sizeIds)) {
            $materialsQuery->whereHas('stockItems.variant', function ($q) use ($sizeIds) {
                $q->whereIn('quality_id', $sizeIds)
                    ->where('is_active', 1);
            });
        }

        if (!empty($colorIds)) {
            $materialsQuery->whereHas('stockItems.variant', function ($q) use ($colorIds) {
                $q->whereIn('color_id', $colorIds)
                    ->where('is_active', 1);
            });
        }

        $materials = $materialsQuery->get()
            ->map(function ($material) {
                $stockItems = $material->stockItems ?? collect();

                $stockCurrent = $stockItems->sum(function ($stockItem) {
                    return $stockItem->inventoryLevels->sum(function ($level) {
                        return (float) ($level->qty_on_hand ?? 0);
                    });
                });

                $stockReserved = $stockItems->sum(function ($stockItem) {
                    return $stockItem->inventoryLevels->sum(function ($level) {
                        return (float) ($level->qty_reserved ?? 0);
                    });
                });

                $stockAvailable = max(0, $stockCurrent - $stockReserved);

                if ($stockAvailable <= 0) {
                    return null;
                }

                $firstStockItem = $stockItems->first();

                $prices = $stockItems
                    ->flatMap(function ($stockItem) {
                        return $stockItem->priceListItems;
                    })
                    ->pluck('price')
                    ->filter(function ($price) {
                        return $price !== null && (float) $price > 0;
                    })
                    ->map(function ($price) {
                        return (float) $price;
                    })
                    ->sort()
                    ->values();

                $minPrice = $prices->first() ?? 0;
                $maxPrice = $prices->last() ?? 0;

                $hasVariants = $stockItems->whereNotNull('variant_id')->count() > 0;

                $priceText = $hasVariants && $prices->count() > 1
                    ? 'Desde S/. ' . number_format($minPrice, 2)
                    : 'S/. ' . number_format($minPrice, 2);

                return [
                    'source' => 'material',
                    'id' => $material->id,
                    'material_id' => $material->id,
                    'full_name' => $material->full_name ?? '',
                    'category' => optional($material->category)->description ?? '',
                    'price' => (float) $minPrice,
                    'price_text' => $priceText,
                    'has_variants' => $hasVariants,
                    'stock' => (float) $stockAvailable,
                    'image' => $material->image ?? optional($firstStockItem)->display_image,
                    'image_url' => $material->image_url ?? optional($firstStockItem)->display_image_url,
                    'unit' => optional($material->unitMeasure)->description ?? '',
                    'tax' => optional($material->typeTax)->tax ?? 18,
                    'rating' => 4,
                    'type' => optional($material->tipoVenta)->id ?? 0,

                    // Datos referenciales del primer stock item
                    'sku' => optional($firstStockItem)->sku ?? '',
                    'barcode' => optional($firstStockItem)->barcode ?? '',

                    // Útil para saber si debe abrir modal de variantes
                    'stock_items_count' => $stockItems->count(),

                    'detail_url' => route('shop.product.show', $material->id),
                ];
            })
            ->filter()
            ->values();

        if ($minPrice !== '') {
            $materials = $materials->filter(function ($product) use ($minPrice) {
                return (float) $product['price'] >= (float) $minPrice;
            })->values();
        }

        if ($maxPrice !== '') {
            $materials = $materials->filter(function ($product) use ($maxPrice) {
                return (float) $product['price'] <= (float) $maxPrice;
            })->values();
        }

        /**
         * =========================================================
         * 2) Paginar
         * =========================================================
         */
        $totalFilteredRecords = $materials->count();

        $totalPages = $totalFilteredRecords > 0
            ? (int) ceil($totalFilteredRecords / $perPage)
            : 1;

        $pageNumber = max(1, (int) $pageNumber);

        $pagedProducts = $materials
            ->slice(($pageNumber - 1) * $perPage, $perPage)
            ->values();

        $startRecord = $totalFilteredRecords > 0
            ? (($pageNumber - 1) * $perPage + 1)
            : 0;

        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $pagination = [
            'currentPage' => (int) $pageNumber,
            'totalPages' => (int) $totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords,
        ];

        return [
            'data' => $pagedProducts->toArray(),
            'pagination' => $pagination,
        ];
    }

    public function getCategoriesData()
    {
        $categories = Category::with([
            'subcategories' => function ($q) {
                $q->select('id', 'category_id', 'name', 'description')
                    ->orderBy('name', 'asc');
            }
        ])
            ->select('id', 'name', 'description')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => strtoupper($category->name ?: $category->description),
                    'description' => $category->description,
                    'subcategories' => $category->subcategories->map(function ($subcategory) {
                        return [
                            'id' => $subcategory->id,
                            'name' => strtoupper($subcategory->name ?: $subcategory->description),
                            'description' => $subcategory->description,
                        ];
                    })->values(),
                ];
            });

        return response()->json([
            'data' => $categories
        ]);
    }

    public function getSizesData()
    {
        $sizes = Talla::select('id', 'name', 'description', 'short_name')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($size) {
                return [
                    'id' => $size->id,
                    'name' => strtoupper($size->short_name ?: $size->name),
                    'description' => $size->description,
                ];
            });

        return response()->json([
            'data' => $sizes
        ]);
    }

    public function getColorsData()
    {
        $colors = Color::select('id', 'name', 'code', 'short_name')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($color) {
                return [
                    'id' => $color->id,
                    'name' => strtoupper($color->name ?: $color->short_name),
                    'code' => $color->code,
                ];
            });

        return response()->json([
            'data' => $colors
        ]);
    }

    public function showProduct(Material $material)
    {
        $dataLogotipoEmpresa = DataGeneral::where('name', 'logotipo')->first();
        $logotipoEmpresa = $dataLogotipoEmpresa->valueText;

        $dataWhatsappEmpresa = DataGeneral::where('name', 'whatsapp')->first();
        $whatsappEmpresa = $dataWhatsappEmpresa->valueText;
        $whatsappEmpresa = preg_replace('/\D/', '', $whatsappEmpresa);

        $dataShowPricesCatalogEmpresa = DataGeneral::where('name', 'show_prices_catalog')->first();
        $showPricesCatalogEmpresa = $dataShowPricesCatalogEmpresa->valueText;
        $dataShowPresentations = DataGeneral::where('name', 'show_presentations')->first();
        $showPresentationsEmpresa = $dataShowPresentations->valueText;

        $defaultPriceList = PriceList::where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        $material->load([
            'category:id,name,description',
            'subcategory:id,name,description',
            'brand:id,name',
            'presentations' => function ($q) {
                $q->where('active', 1)
                    ->orderBy('quantity', 'asc');
            },
            'variants' => function ($q) {
                $q->where('is_active', 1)
                    ->with([
                        'talla:id,name,short_name',
                        'color:id,name,short_name,code'
                    ]);
            },
            'stockItems' => function ($q) use ($defaultPriceList) {
                $q->where('is_active', 1)
                    ->with([
                        'variant.talla:id,name,short_name',
                        'variant.color:id,name,short_name,code',
                        'inventoryLevels:id,stock_item_id,qty_on_hand,qty_reserved',
                        'priceListItems' => function ($pq) use ($defaultPriceList) {
                            if ($defaultPriceList) {
                                $pq->where('price_list_id', $defaultPriceList->id);
                            } else {
                                $pq->whereRaw('1 = 0');
                            }
                        }
                    ]);
            }
        ]);

        $stockItems = $material->stockItems ?? collect();

        $stockAvailable = $stockItems->sum(function ($stockItem) {
            $onHand = $stockItem->inventoryLevels->sum('qty_on_hand');
            $reserved = $stockItem->inventoryLevels->sum('qty_reserved');

            return max(0, (float) $onHand - (float) $reserved);
        });

        $prices = $stockItems
            ->flatMap(function ($stockItem) {
                return $stockItem->priceListItems;
            })
            ->pluck('price')
            ->filter(function ($price) {
                return $price !== null && (float) $price > 0;
            })
            ->map(function ($price) {
                return (float) $price;
            })
            ->sort()
            ->values();

        $minPrice = $prices->first() ?? 0;
        $maxPrice = $prices->last() ?? 0;

        $hasVariants = $material->variants->count() > 0;

        $priceText = $hasVariants && $prices->count() > 1
            ? 'Desde S/. ' . number_format($minPrice, 2)
            : 'S/. ' . number_format($minPrice, 2);

        $colors = $material->variants
            ->pluck('color')
            ->filter()
            ->unique('id')
            ->values();

        $sizes = $material->variants
            ->pluck('talla')
            ->filter()
            ->unique('id')
            ->values();

        if (!$hasVariants) {
            $colors = collect();
            $sizes = collect();
        }

        $images = collect();

        if ($hasVariants) {
            $images = $material->variants
                ->filter(function ($variant) {
                    return $variant->image;
                })
                ->map(function ($variant) {
                    return [
                        'image' => asset('images/variant/' . $variant->image),
                        'thumb' => asset('images/variant/' . $variant->image),
                        'label' => $variant->attribute_summary,
                    ];
                })
                ->values();
        }

        if ($images->isEmpty()) {
            $image = $material->image
                ? asset('images/material/' . $material->image)
                : asset('shop/img/no-image.png');

            $images->push([
                'image' => $image,
                'thumb' => $image,
                'label' => $material->full_name,
            ]);
        }

        return view('shop.detailCatalog', compact(
            'material',
            'stockAvailable',
            'priceText',
            'colors',
            'sizes',
            'images',
            'logotipoEmpresa',
            'whatsappEmpresa',
            'showPricesCatalogEmpresa', 'showPresentationsEmpresa'
        ));
    }
}
