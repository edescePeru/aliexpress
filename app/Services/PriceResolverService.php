<?php

namespace App\Services;

use App\PriceList;
use App\PriceListItem;
use App\PriceListMaterial;
use App\StockItem;
use App\Support\TenantContext;

class PriceResolverService
{
    /**
     * Resuelve solamente el precio efectivo de un StockItem
     * usando la PriceList default activa de la Company.
     *
     * Retorna:
     *
     * float -> existe precio configurado
     * null  -> no existe precio configurado
     */
    public function resolveForStockItem(
        StockItem $stockItem,
        ?int $companyId = null
    ): ?float {
        $result = $this->resolveWithSource(
            $stockItem,
            $companyId
        );

        return $result['price'];
    }

    /**
     * Resuelve el precio y además informa de dónde provino.
     *
     * source:
     *
     * stock_item -> PriceListItem
     * material   -> PriceListMaterial
     * none       -> no existe precio
     */
    public function resolveWithSource(
        StockItem $stockItem,
        ?int $companyId = null
    ): array {
        $companyId = $companyId
            ?: TenantContext::companyId();

        /*
         * ============================================================
         * 1. OBTENER PRICE LIST DEFAULT DE LA COMPANY
         * ============================================================
         */

        $priceList = $this->getDefaultPriceListForCompany(
            $companyId
        );

        if (!$priceList) {
            return [
                'price' => null,
                'source' => 'none',

                'price_list_id' => null,
                'price_list_name' => null,

                'material_id' => $stockItem->material_id,
                'stock_item_id' => $stockItem->id,
            ];
        }

        /*
         * ============================================================
         * 2. RESOLVER USANDO ESA PRICE LIST
         * ============================================================
         */

        return $this->resolveUsingPriceList(
            $stockItem,
            $priceList
        );
    }

    /**
     * Obtiene la PriceList default y activa de una Company.
     */
    public function getDefaultPriceListForCompany(
        int $companyId
    ): ?PriceList {
        $lists = PriceList::query()->where('tenant_id', TenantContext::tenantId())->where('company_id', $companyId)
            ->where('is_default', true)->where('is_active', true)->orderBy('id')->limit(2)->get();
        if ($lists->count() > 1) throw \Illuminate\Validation\ValidationException::withMessages(['price_list_id' => 'La empresa tiene más de una lista predeterminada activa.']);
        return $lists->first();
    }

    /**
     * Resuelve el precio para un StockItem usando
     * una PriceList específica.
     *
     * Este método nos servirá más adelante cuando:
     *
     * Branch → seleccione PriceList
     * Channel → seleccione PriceList
     *
     * sin tener que modificar la lógica de resolución.
     */
    public function resolveUsingPriceList(
        StockItem $stockItem,
        PriceList $priceList
    ): array {
        if ((int) $priceList->tenant_id !== TenantContext::tenantId() || !$priceList->is_active
            || (int) $stockItem->tenant_id !== TenantContext::tenantId()
            || !\Illuminate\Support\Facades\DB::table('companies')->where('id', $priceList->company_id)->where('tenant_id', TenantContext::tenantId())->exists()) {
            throw new \RuntimeException('La lista no está activa o no pertenece al grupo empresarial.');
        }
        /*
         * ============================================================
         * SEGURIDAD
         * ============================================================
         *
         * El StockItem y PriceList deben pertenecer
         * al mismo Tenant.
         */

        if (
            (int) $stockItem->tenant_id !==
            (int) $priceList->tenant_id
        ) {
            throw new \RuntimeException(
                'El producto y la lista de precios no pertenecen al mismo Tenant.'
            );
        }

        /*
         * ============================================================
         * 1. OVERRIDE DEL STOCK ITEM
         * ============================================================
         *
         * Es la regla de mayor prioridad.
         *
         * Ejemplo:
         *
         * Material 18 → S/ 30
         * StockItem 95 → S/ 25
         *
         * Resultado StockItem 95 = S/ 25.
         */

        $priceListItem = $stockItem->relationLoaded('priceListItems')
            ? $stockItem->priceListItems->first(function ($row) use ($priceList, $stockItem) { return (int) $row->price_list_id === (int) $priceList->id && (int) $row->stock_item_id === (int) $stockItem->id; })
            : PriceListItem::query()->where('price_list_id', $priceList->id)->where('stock_item_id', $stockItem->id)->first();

        if ($priceListItem) {
            return [
                'price' => $this->validPrice($priceListItem->price),

                'source' => 'stock_item',

                'price_list_id' => $priceList->id,
                'price_list_name' => $priceList->name,

                'material_id' => $stockItem->material_id,
                'stock_item_id' => $stockItem->id,
            ];
        }

        /*
         * ============================================================
         * 2. PRECIO BASE DEL MATERIAL
         * ============================================================
         */

        $material = $stockItem->material;
        if (!$material || (int) $material->tenant_id !== TenantContext::tenantId()) throw new \RuntimeException('El producto padre no pertenece al grupo empresarial.');
        $priceListMaterial = $material->relationLoaded('priceListMaterials')
            ? $material->priceListMaterials->first(function ($row) use ($priceList, $material) { return (int) $row->price_list_id === (int) $priceList->id && (int) $row->material_id === (int) $material->id; })
            : PriceListMaterial::query()->where('price_list_id', $priceList->id)->where('material_id', $material->id)->first();

        if ($priceListMaterial) {
            return [
                'price' => $this->validPrice($priceListMaterial->price),

                'source' => 'material',

                'price_list_id' => $priceList->id,
                'price_list_name' => $priceList->name,

                'material_id' => $stockItem->material_id,
                'stock_item_id' => $stockItem->id,
            ];
        }

        /*
         * ============================================================
         * 3. SIN PRECIO CONFIGURADO
         * ============================================================
         */

        return [
            'price' => null,

            'source' => 'none',

            'price_list_id' => $priceList->id,
            'price_list_name' => $priceList->name,

            'material_id' => $stockItem->material_id,
            'stock_item_id' => $stockItem->id,
        ];
    }
    private function validPrice($value): float
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0) throw new \RuntimeException('El precio configurado no es válido.');
        return (float) $value;
    }

}