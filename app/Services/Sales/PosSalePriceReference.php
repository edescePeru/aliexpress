<?php
namespace App\Services\Sales;

use App\PriceList;
use App\StockItem;
use App\Services\BranchPriceListService;
use App\Services\PriceResolverService;
use App\Services\Materials\MaterialInventoryScope;
use Illuminate\Validation\ValidationException;

class PosSalePriceReference
{
    // Caller owns the sale transaction. No financial or inventory writes here.
    public function begin($request): PriceList
    {
        app(PosCatalogReadService::class)->authorize();
        app(MaterialInventoryScope::class)->snapshot($request);
        $list = app(BranchPriceListService::class)->current(true);
        $id = $request->input('pos_price_list_id');
        if (!$list || !is_scalar($id) || (string) $id !== (string) $list->id) {
            throw ValidationException::withMessages(['price_list_id' => 'Cambió la lista de precios o no hay una lista activa. Recargue la pantalla antes de vender.']);
        }
        return $list;
    }

    public function snapshot(StockItem $item, PriceList $list): array
    {
        abort_unless(app(PosCatalogReadService::class)->catalog()->where('stock_items.id', $item->id)->exists(), 422, 'El producto ya no está activo o habilitado para esta empresa.');
        $resolved = app(PriceResolverService::class)->resolveUsingPriceList($item, $list);
        if ($resolved['price'] === null) throw ValidationException::withMessages(['price' => 'El producto no tiene precio configurado en la lista de la sucursal.']);
        return ['price_list_id' => $list->id, 'price_list_name' => $list->name,
            'catalog_unit_price' => $resolved['price'], 'catalog_price_source' => $resolved['source']];
    }
}
