<?php

namespace App\Services;

use App\Material;
use App\TypeTax;
use RuntimeException;

class TypeTaxResolverService
{
    /**
     * Resolver un TypeTax.
     *
     * Si se proporciona un ID:
     * - devuelve exactamente ese TypeTax,
     * - incluso si actualmente está inactivo.
     *
     * Si no se proporciona:
     * - utiliza el TypeTax default activo global.
     */
    public function resolve(
        ?int $typeTaxId = null
    ): TypeTax {

        if ($typeTaxId) {

            $typeTax = TypeTax::query()
                ->where(
                    'id',
                    $typeTaxId
                )
                ->first();

            if (!$typeTax) {
                throw new RuntimeException(
                    'El tipo de impuesto indicado no existe.'
                );
            }

            return $typeTax;
        }

        return $this->resolveDefault();
    }


    /**
     * Resolver el impuesto de un Material.
     *
     * Material con type_tax_id:
     *     usa su impuesto específico.
     *
     * Material sin type_tax_id:
     *     usa el impuesto default.
     *
     * El fallback sirve principalmente para productos
     * históricos que todavía pudieran no tener type_tax_id.
     */
    public function resolveForMaterial(
        Material $material
    ): TypeTax {

        return $this->resolve(
            $material->type_tax_id
                ? (int) $material->type_tax_id
                : null
        );
    }


    /**
     * Resolver el TypeTax default global.
     *
     * Debe existir exactamente un registro:
     *
     * is_default = 1
     * is_active = 1
     */
    public function resolveDefault(): TypeTax
    {
        $defaults = TypeTax::query()
            ->where(
                'is_default',
                true
            )
            ->where(
                'is_active',
                true
            )
            ->get();

        if ($defaults->count() === 0) {
            throw new RuntimeException(
                'No existe un tipo de impuesto predeterminado activo.'
            );
        }

        if ($defaults->count() > 1) {
            throw new RuntimeException(
                'Existe más de un tipo de impuesto predeterminado activo.'
            );
        }

        return $defaults->first();
    }


    /**
     * Versión enriquecida para procesos como Quote/Sale.
     *
     * Esto nos permitirá posteriormente generar
     * snapshots tributarios sin repetir lógica.
     */
    public function resolveWithSource(
        ?int $typeTaxId = null
    ): array {

        $typeTax =
            $this->resolve(
                $typeTaxId
            );

        return [
            'type_tax' =>
                $typeTax,

            'type_tax_id' =>
                $typeTax->id,

            'code' =>
                $typeTax->code,

            'name' =>
                $typeTax->name,

            'tax_rate' =>
                (float) $typeTax->tax,

            'source' =>
                $typeTaxId
                    ? 'specific'
                    : 'default',
        ];
    }


    /**
     * Versión enriquecida para Material.
     */
    public function resolveMaterialWithSource(
        Material $material
    ): array {

        return $this->resolveWithSource(
            $material->type_tax_id
                ? (int) $material->type_tax_id
                : null
        );
    }
}