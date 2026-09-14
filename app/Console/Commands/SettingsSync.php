<?php

namespace App\Console\Commands;

use App\SettingDefinition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SettingsSync extends Command
{
    protected $signature =
        'settings:sync';

    protected $description =
        'Sincroniza las definiciones globales de configuración desde config/settings_catalog.php';


    public function handle()
    {
        $catalog =
            config('settings_catalog');


        if (
            !is_array($catalog) ||
            empty($catalog)
        ) {
            $this->error(
                'El catálogo settings_catalog está vacío o no existe.'
            );

            return 1;
        }


        $allowedScopes = [
            'tenant',
            'company',
            'branch',
        ];


        $allowedValueTypes = [
            'string',
            'integer',
            'decimal',
            'boolean',
            'enum',
            'json',
        ];


        $created = 0;
        $updated = 0;


        DB::beginTransaction();

        try {

            foreach ($catalog as $index => $item) {

                /*
                 * =====================================================
                 * VALIDACIÓN DEL CATÁLOGO
                 * =====================================================
                 */

                if (
                    empty($item['key']) ||
                    !is_string($item['key'])
                ) {
                    throw new \RuntimeException(
                        'El setting en la posición ' .
                        $index .
                        ' no tiene una key válida.'
                    );
                }


                if (
                    empty($item['label']) ||
                    !is_string($item['label'])
                ) {
                    throw new \RuntimeException(
                        'El setting "' .
                        $item['key'] .
                        '" no tiene un label válido.'
                    );
                }


                if (
                    empty($item['scope']) ||
                    !in_array(
                        $item['scope'],
                        $allowedScopes,
                        true
                    )
                ) {
                    throw new \RuntimeException(
                        'El setting "' .
                        $item['key'] .
                        '" tiene un scope inválido.'
                    );
                }


                if (
                    empty($item['value_type']) ||
                    !in_array(
                        $item['value_type'],
                        $allowedValueTypes,
                        true
                    )
                ) {
                    throw new \RuntimeException(
                        'El setting "' .
                        $item['key'] .
                        '" tiene un value_type inválido.'
                    );
                }


                /*
                 * =====================================================
                 * BUSCAR DEFINICIÓN EXISTENTE
                 * =====================================================
                 */

                $definition =
                    SettingDefinition::query()
                        ->where(
                            'key',
                            $item['key']
                        )
                        ->first();


                $data = [
                    'label' =>
                        $item['label'],

                    'module' =>
                        $item['module']
                        ?? null,

                    'scope' =>
                        $item['scope'],

                    'value_type' =>
                        $item['value_type'],

                    'default_value' =>
                        array_key_exists(
                            'default_value',
                            $item
                        )
                            ? $item['default_value']
                            : null,

                    'options_json' =>
                        $item['options']
                        ?? null,

                    'description' =>
                        $item['description']
                        ?? null,

                    'editable_by_owner' =>
                        array_key_exists(
                            'editable_by_owner',
                            $item
                        )
                            ? (bool) $item['editable_by_owner']
                            : true,

                    'is_active' =>
                        array_key_exists(
                            'is_active',
                            $item
                        )
                            ? (bool) $item['is_active']
                            : true,
                ];


                /*
                 * =====================================================
                 * CREAR O ACTUALIZAR
                 * =====================================================
                 */

                if (!$definition) {

                    SettingDefinition::create(
                        array_merge(
                            [
                                'key' =>
                                    $item['key'],
                            ],
                            $data
                        )
                    );

                    $created++;

                    $this->info(
                        'Creado: ' .
                        $item['key']
                    );

                } else {

                    $definition->fill(
                        $data
                    );


                    if ($definition->isDirty()) {

                        $definition->save();

                        $updated++;

                        $this->info(
                            'Actualizado: ' .
                            $item['key']
                        );

                    } else {

                        $this->line(
                            'Sin cambios: ' .
                            $item['key']
                        );
                    }
                }
            }


            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            $this->error(
                $e->getMessage()
            );

            return 1;
        }


        $this->info(
            'Sincronización completada. ' .
            'Creados: ' .
            $created .
            '. Actualizados: ' .
            $updated .
            '.'
        );


        return 0;
    }
}