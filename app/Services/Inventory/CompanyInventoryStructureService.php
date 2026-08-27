<?php

namespace App\Services\Inventory;

use App\Area;
use App\Company;
use App\Container;
use App\Level;
use App\Location;
use App\Position;
use App\Shelf;
use App\Warehouse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompanyInventoryStructureService
{
    public function provision(Company $company)
    {
        if (!$company->tenant_id) {
            throw new RuntimeException(
                'La empresa no tiene un grupo empresarial asignado.'
            );
        }

        return DB::transaction(function () use ($company) {

            /*
             * ========================================================
             * 1. BUSCAR WAREHOUSE DEFAULT EXISTENTE
             * ========================================================
             *
             * Si la Company ya tiene infraestructura histórica válida,
             * debemos reutilizarla.
             *
             * Ejemplo:
             *
             * Area TRUJ
             * └── Warehouse GEN
             *     is_default = 1
             *
             * No debemos crear otra estructura GENERAL.
             */

            $defaultWarehouses =
                Warehouse::withoutGlobalScopes()
                    ->where(
                        'tenant_id',
                        $company->tenant_id
                    )
                    ->where(
                        'company_id',
                        $company->id
                    )
                    ->where(
                        'is_default',
                        true
                    )
                    ->get();

            if ($defaultWarehouses->count() > 1) {
                throw new RuntimeException(
                    'La empresa tiene más de un almacén predeterminado configurado.'
                );
            }

            $warehouse =
                $defaultWarehouses->first();


            /*
             * ========================================================
             * 2. SI EXISTE WAREHOUSE DEFAULT, REUTILIZAR SU AREA
             * ========================================================
             */

            if ($warehouse) {

                $area =
                    Area::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $company->tenant_id
                        )
                        ->where(
                            'company_id',
                            $company->id
                        )
                        ->findOrFail(
                            $warehouse->area_id
                        );


                /*
                 * ====================================================
                 * 3. BUSCAR LOCATION DEFAULT EXISTENTE
                 * ====================================================
                 *
                 * Si ya existe una Location default dentro del
                 * Warehouse default, toda la estructura física ya
                 * existe y debe reutilizarse.
                 */

                $defaultLocations =
                    Location::withoutGlobalScopes()
                        ->where(
                            'tenant_id',
                            $company->tenant_id
                        )
                        ->where(
                            'company_id',
                            $company->id
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

                if ($defaultLocations->count() > 1) {
                    throw new RuntimeException(
                        'El almacén predeterminado "' .
                        $warehouse->name .
                        '" tiene más de una ubicación predeterminada configurada.'
                    );
                }

                $existingDefaultLocation =
                    $defaultLocations->first();


                /*
                 * ====================================================
                 * 4. SI YA EXISTE LOCATION DEFAULT,
                 *    REUTILIZAR TODA LA CADENA
                 * ====================================================
                 */

                if ($existingDefaultLocation) {

                    /*
                     * Validamos que la Location realmente corresponda
                     * al Warehouse y Area que acabamos de resolver.
                     */

                    if (
                        (int) $existingDefaultLocation->warehouse_id !==
                        (int) $warehouse->id
                    ) {
                        throw new RuntimeException(
                            'La ubicación predeterminada no pertenece al almacén predeterminado.'
                        );
                    }

                    if (
                        (int) $existingDefaultLocation->company_id !==
                        (int) $company->id
                    ) {
                        throw new RuntimeException(
                            'La ubicación predeterminada pertenece a otra empresa.'
                        );
                    }

                    if (
                        (int) $existingDefaultLocation->tenant_id !==
                        (int) $company->tenant_id
                    ) {
                        throw new RuntimeException(
                            'La ubicación predeterminada pertenece a otro grupo empresarial.'
                        );
                    }


                    $shelf =
                        Shelf::withoutGlobalScopes()
                            ->where(
                                'tenant_id',
                                $company->tenant_id
                            )
                            ->findOrFail(
                                $existingDefaultLocation->shelf_id
                            );

                    $level =
                        Level::withoutGlobalScopes()
                            ->where(
                                'tenant_id',
                                $company->tenant_id
                            )
                            ->findOrFail(
                                $existingDefaultLocation->level_id
                            );

                    $container =
                        Container::withoutGlobalScopes()
                            ->where(
                                'tenant_id',
                                $company->tenant_id
                            )
                            ->findOrFail(
                                $existingDefaultLocation->container_id
                            );

                    $position =
                        Position::withoutGlobalScopes()
                            ->where(
                                'tenant_id',
                                $company->tenant_id
                            )
                            ->findOrFail(
                                $existingDefaultLocation->position_id
                            );


                    /*
                     * Validamos la jerarquía física.
                     */

                    if (
                        (int) $shelf->warehouse_id !==
                        (int) $warehouse->id
                    ) {
                        throw new RuntimeException(
                            'El anaquel de la ubicación predeterminada no pertenece al almacén predeterminado.'
                        );
                    }

                    if (
                        (int) $level->shelf_id !==
                        (int) $shelf->id
                    ) {
                        throw new RuntimeException(
                            'El nivel de la ubicación predeterminada no pertenece al anaquel correspondiente.'
                        );
                    }

                    if (
                        (int) $container->level_id !==
                        (int) $level->id
                    ) {
                        throw new RuntimeException(
                            'El contenedor de la ubicación predeterminada no pertenece al nivel correspondiente.'
                        );
                    }

                    if (
                        (int) $position->container_id !==
                        (int) $container->id
                    ) {
                        throw new RuntimeException(
                            'La posición de la ubicación predeterminada no pertenece al contenedor correspondiente.'
                        );
                    }


                    return [
                        'area' =>
                            $area,

                        'warehouse' =>
                            $warehouse,

                        'shelf' =>
                            $shelf,

                        'level' =>
                            $level,

                        'container' =>
                            $container,

                        'position' =>
                            $position,

                        'location' =>
                            $existingDefaultLocation,
                    ];
                }


                /*
                 * Si llegamos hasta aquí:
                 *
                 * - Existe Warehouse default.
                 * - Pero NO existe Location default.
                 *
                 * En ese caso reutilizamos Warehouse + Area
                 * y completamos una estructura GENERAL inferior.
                 */

            } else {

                /*
                 * ====================================================
                 * 5. NO EXISTE WAREHOUSE DEFAULT
                 *    CREAR AREA GENERAL
                 * ====================================================
                 */

                $area =
                    Area::withoutGlobalScopes()
                        ->firstOrCreate(
                            [
                                'tenant_id' =>
                                    $company->tenant_id,

                                'company_id' =>
                                    $company->id,

                                'name' =>
                                    'GENERAL',
                            ],
                            [
                                'comment' =>
                                    'Área general creada automáticamente.',
                            ]
                        );


                /*
                 * ====================================================
                 * 6. CREAR WAREHOUSE GENERAL
                 * ====================================================
                 */

                $warehouse =
                    Warehouse::withoutGlobalScopes()
                        ->firstOrCreate(
                            [
                                'tenant_id' =>
                                    $company->tenant_id,

                                'company_id' =>
                                    $company->id,

                                'area_id' =>
                                    $area->id,

                                'name' =>
                                    'GENERAL',
                            ],
                            [
                                'branch_id' =>
                                    null,

                                'comment' =>
                                    'Almacén general creado automáticamente.',

                                'is_default' =>
                                    true,
                            ]
                        );


                /*
                 * Si existía Warehouse GENERAL pero no estaba marcado
                 * como default, lo convertimos en el default porque
                 * ya comprobamos que la Company no tenía otro.
                 */

                if (!$warehouse->is_default) {

                    $warehouse->is_default =
                        true;

                    $warehouse->save();
                }
            }


            /*
             * ========================================================
             * 7. SHELF GENERAL
             * ========================================================
             *
             * Solo llegamos aquí cuando:
             *
             * A) acabamos de crear Warehouse GENERAL, o
             *
             * B) existía Warehouse default pero no tenía una
             *    Location default válida.
             */

            $shelf =
                Shelf::withoutGlobalScopes()
                    ->firstOrCreate(
                        [
                            'tenant_id' =>
                                $company->tenant_id,

                            'warehouse_id' =>
                                $warehouse->id,

                            'name' =>
                                'GENERAL',
                        ],
                        [
                            'comment' =>
                                'Anaquel general creado automáticamente.',
                        ]
                    );


            /*
             * ========================================================
             * 8. LEVEL GENERAL
             * ========================================================
             */

            $level =
                Level::withoutGlobalScopes()
                    ->firstOrCreate(
                        [
                            'tenant_id' =>
                                $company->tenant_id,

                            'shelf_id' =>
                                $shelf->id,

                            'name' =>
                                'GENERAL',
                        ],
                        [
                            'comment' =>
                                'Nivel general creado automáticamente.',
                        ]
                    );


            /*
             * ========================================================
             * 9. CONTAINER GENERAL
             * ========================================================
             */

            $container =
                Container::withoutGlobalScopes()
                    ->firstOrCreate(
                        [
                            'tenant_id' =>
                                $company->tenant_id,

                            'level_id' =>
                                $level->id,

                            'name' =>
                                'GENERAL',
                        ],
                        [
                            'comment' =>
                                'Contenedor general creado automáticamente.',
                        ]
                    );


            /*
             * ========================================================
             * 10. POSITION GENERAL
             * ========================================================
             */

            $position =
                Position::withoutGlobalScopes()
                    ->firstOrCreate(
                        [
                            'tenant_id' =>
                                $company->tenant_id,

                            'container_id' =>
                                $container->id,

                            'name' =>
                                'GENERAL',
                        ],
                        [
                            'comment' =>
                                'Posición general creada automáticamente.',

                            'status' =>
                                'active',
                        ]
                    );


            /*
             * ========================================================
             * 11. LOCATION GENERAL
             * ========================================================
             */

            $location =
                Location::withoutGlobalScopes()
                    ->firstOrCreate(
                        [
                            'tenant_id' =>
                                $company->tenant_id,

                            'company_id' =>
                                $company->id,

                            'area_id' =>
                                $area->id,

                            'warehouse_id' =>
                                $warehouse->id,

                            'shelf_id' =>
                                $shelf->id,

                            'level_id' =>
                                $level->id,

                            'container_id' =>
                                $container->id,

                            'position_id' =>
                                $position->id,
                        ],
                        [
                            'description' =>
                                'Ubicación general creada automáticamente.',

                            'default' =>
                                true,
                        ]
                    );


            /*
             * ========================================================
             * 12. GARANTIZAR LOCATION DEFAULT
             * ========================================================
             *
             * Llegamos aquí porque al inicio NO existía una Location
             * default dentro del Warehouse.
             *
             * Por tanto esta Location GENERAL puede convertirse
             * con seguridad en la predeterminada.
             */

            if (!$location->default) {

                $location->default =
                    true;

                $location->save();
            }


            /*
             * ========================================================
             * RESULTADO
             * ========================================================
             */

            return [
                'area' =>
                    $area,

                'warehouse' =>
                    $warehouse,

                'shelf' =>
                    $shelf,

                'level' =>
                    $level,

                'container' =>
                    $container,

                'position' =>
                    $position,

                'location' =>
                    $location,
            ];
        });
    }
}