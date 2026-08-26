<?php

namespace App\Services\Inventory;

use App\Location;
use App\Support\TenantContext;
use App\Warehouse;
use RuntimeException;

class DefaultInventoryLocationResolver
{
    /**
     * Resolver el almacén y ubicación predeterminados
     * de la Company actualmente seleccionada.
     */
    public function resolveCurrent()
    {
        return $this->resolveForCompany(
            TenantContext::companyId()
        );
    }


    /**
     * Resolver almacén + ubicación default
     * para una Company específica.
     */
    public function resolveForCompany($companyId)
    {
        $tenantId =
            TenantContext::tenantId();


        /*
         * ============================================================
         * WAREHOUSE DEFAULT
         * ============================================================
         */

        $warehouses =
            Warehouse::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_default',
                    true
                )
                ->get();


        if ($warehouses->isEmpty()) {
            throw new RuntimeException(
                'La empresa seleccionada no tiene un almacén predeterminado configurado.'
            );
        }


        if ($warehouses->count() > 1) {
            throw new RuntimeException(
                'La empresa seleccionada tiene más de un almacén predeterminado configurado.'
            );
        }


        $warehouse =
            $warehouses->first();


        /*
         * ============================================================
         * LOCATION DEFAULT DEL WAREHOUSE
         * ============================================================
         */

        $locations =
            Location::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'warehouse_id',
                    $warehouse->id
                )
                ->where(
                    'default',
                    true
                )
                ->get();


        if ($locations->isEmpty()) {
            throw new RuntimeException(
                'El almacén predeterminado "' .
                $warehouse->name .
                '" no tiene una ubicación predeterminada configurada.'
            );
        }


        if ($locations->count() > 1) {
            throw new RuntimeException(
                'El almacén predeterminado "' .
                $warehouse->name .
                '" tiene más de una ubicación predeterminada configurada.'
            );
        }


        $location =
            $locations->first();


        /*
         * ============================================================
         * VALIDACIÓN FINAL
         * ============================================================
         */

        if (
            (int) $location->warehouse_id !==
            (int) $warehouse->id
        ) {
            throw new RuntimeException(
                'La ubicación predeterminada no pertenece al almacén seleccionado.'
            );
        }


        if (
            (int) $location->company_id !==
            (int) $warehouse->company_id
        ) {
            throw new RuntimeException(
                'La ubicación y el almacén predeterminados pertenecen a empresas diferentes.'
            );
        }


        if (
            (int) $location->tenant_id !==
            (int) $warehouse->tenant_id
        ) {
            throw new RuntimeException(
                'La ubicación y el almacén predeterminados pertenecen a grupos empresariales diferentes.'
            );
        }


        return [
            'warehouse' => $warehouse,
            'location' => $location,
        ];
    }
}