<?php

namespace App\Services;

use App\SettingDefinition;
use App\SettingValue;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SettingService
{
    /**
     * Obtiene el valor efectivo de un setting.
     *
     * Si no existe un override para el scope actual,
     * devuelve default_value.
     *
     * Ejemplo:
     *
     * $settings->get('pagos_parciales');
     */
    public function get(string $key)
    {
        $definition = $this->getDefinition($key);

        $scope = $this->resolveScope(
            $definition
        );

        $value = SettingValue::query()
            ->where(
                'setting_definition_id',
                $definition->id
            )
            ->where(
                'tenant_id',
                $scope['tenant_id']
            )
            ->when(
                $scope['company_id'] === null,
                function ($query) {
                    $query->whereNull(
                        'company_id'
                    );
                },
                function ($query) use ($scope) {
                    $query->where(
                        'company_id',
                        $scope['company_id']
                    );
                }
            )
            ->when(
                $scope['branch_id'] === null,
                function ($query) {
                    $query->whereNull(
                        'branch_id'
                    );
                },
                function ($query) use ($scope) {
                    $query->where(
                        'branch_id',
                        $scope['branch_id']
                    );
                }
            )
            ->first();


        if (!$value) {
            return $this->castValue(
                $definition,
                $definition->default_value,
                null,
                null
            );
        }


        return $this->castValue(
            $definition,
            $value->value_text,
            $value->value_number,
            $value->value_json
        );
    }


    /**
     * Guarda un override del setting para el contexto actual.
     *
     * Solo debería utilizarse desde flujos administrativos
     * autorizados.
     */
    public function set(
        string $key,
        $value
    ) {
        $definition = $this->getDefinition(
            $key
        );


        $scope = $this->resolveScope(
            $definition
        );


        $normalized =
            $this->normalizeForStorage(
                $definition,
                $value
            );


        return DB::transaction(
            function () use (
                $definition,
                $scope,
                $normalized
            ) {

                /*
                 * No usamos updateOrCreate directamente porque
                 * company_id / branch_id pueden ser NULL y MySQL
                 * permite múltiples NULL en UNIQUE indexes.
                 *
                 * Buscamos explícitamente el registro efectivo.
                 */

                $query = SettingValue::query()
                    ->where(
                        'setting_definition_id',
                        $definition->id
                    )
                    ->where(
                        'tenant_id',
                        $scope['tenant_id']
                    );


                if ($scope['company_id'] === null) {
                    $query->whereNull(
                        'company_id'
                    );
                } else {
                    $query->where(
                        'company_id',
                        $scope['company_id']
                    );
                }


                if ($scope['branch_id'] === null) {
                    $query->whereNull(
                        'branch_id'
                    );
                } else {
                    $query->where(
                        'branch_id',
                        $scope['branch_id']
                    );
                }


                $settingValue =
                    $query
                        ->lockForUpdate()
                        ->first();


                if (!$settingValue) {

                    $settingValue =
                        new SettingValue();

                    /*
                     * tenant_id lo indicamos explícitamente.
                     *
                     * Aunque SettingValue usa BelongsToTenant,
                     * esto mantiene clara la relación con el
                     * scope que acabamos de resolver.
                     */
                    $settingValue->tenant_id =
                        $scope['tenant_id'];

                    $settingValue
                        ->setting_definition_id =
                        $definition->id;

                    $settingValue->company_id =
                        $scope['company_id'];

                    $settingValue->branch_id =
                        $scope['branch_id'];
                }


                $settingValue->value_text =
                    $normalized['value_text'];

                $settingValue->value_number =
                    $normalized['value_number'];

                $settingValue->value_json =
                    $normalized['value_json'];

                $settingValue->save();


                return $settingValue;
            }
        );
    }


    /**
     * Elimina el override actual.
     *
     * Al eliminarlo, get() volverá automáticamente
     * al default_value definido por Venti360.
     */
    public function reset(string $key): bool
    {
        $definition = $this->getDefinition(
            $key
        );

        $scope = $this->resolveScope(
            $definition
        );


        $query = SettingValue::query()
            ->where(
                'setting_definition_id',
                $definition->id
            )
            ->where(
                'tenant_id',
                $scope['tenant_id']
            );


        if ($scope['company_id'] === null) {
            $query->whereNull(
                'company_id'
            );
        } else {
            $query->where(
                'company_id',
                $scope['company_id']
            );
        }


        if ($scope['branch_id'] === null) {
            $query->whereNull(
                'branch_id'
            );
        } else {
            $query->where(
                'branch_id',
                $scope['branch_id']
            );
        }


        return (bool) $query->delete();
    }


    /**
     * Obtiene y valida una definición activa.
     */
    protected function getDefinition(
        string $key
    ): SettingDefinition {
        $definition = SettingDefinition::query()
            ->where(
                'key',
                $key
            )
            ->where(
                'is_active',
                true
            )
            ->first();


        if (!$definition) {
            throw new RuntimeException(
                'No existe una configuración activa con la clave "' .
                $key .
                '".'
            );
        }


        return $definition;
    }


    /**
     * Resuelve qué Tenant / Company / Branch
     * corresponden según el scope definido.
     */
    protected function resolveScope(
        SettingDefinition $definition
    ): array {
        $tenantId =
            TenantContext::tenantId();


        if ($definition->scope === 'tenant') {

            return [
                'tenant_id' =>
                    $tenantId,

                'company_id' =>
                    null,

                'branch_id' =>
                    null,
            ];
        }


        if ($definition->scope === 'company') {

            return [
                'tenant_id' =>
                    $tenantId,

                'company_id' =>
                    TenantContext::companyId(),

                'branch_id' =>
                    null,
            ];
        }


        if ($definition->scope === 'branch') {

            return [
                'tenant_id' =>
                    $tenantId,

                'company_id' =>
                    TenantContext::companyId(),

                'branch_id' =>
                    TenantContext::branchId(),
            ];
        }


        throw new RuntimeException(
            'El setting "' .
            $definition->key .
            '" tiene un scope no soportado: ' .
            $definition->scope
        );
    }


    /**
     * Convierte el valor almacenado al tipo declarado
     * en setting_definitions.value_type.
     */
    protected function castValue(
        SettingDefinition $definition,
        $valueText,
        $valueNumber,
        $valueJson
    ) {
        switch ($definition->value_type) {

            case 'boolean':

                $raw =
                    $valueNumber !== null
                        ? $valueNumber
                        : $valueText;

                if ($raw === null) {
                    return false;
                }

                /*
                 * Los overrides boolean se almacenan en value_number,
                 * que es DECIMAL y MySQL puede devolver "1.000000"
                 * o "0.000000".
                 */
                if (is_numeric($raw)) {
                    return (float) $raw != 0.0;
                }

                return $this->normalizeBoolean($raw);


            case 'integer':

                $raw =
                    $valueNumber !== null
                        ? $valueNumber
                        : $valueText;

                return $raw === null
                    ? null
                    : (int) $raw;


            case 'decimal':

                $raw =
                    $valueNumber !== null
                        ? $valueNumber
                        : $valueText;

                return $raw === null
                    ? null
                    : (float) $raw;


            case 'json':

                if ($valueJson !== null) {
                    return $valueJson;
                }

                if ($valueText === null) {
                    return null;
                }

                $decoded =
                    json_decode(
                        $valueText,
                        true
                    );

                return json_last_error() === JSON_ERROR_NONE
                    ? $decoded
                    : null;


            case 'enum':
            case 'string':

                return $valueText !== null
                    ? (string) $valueText
                    : null;
        }


        throw new RuntimeException(
            'El setting "' .
            $definition->key .
            '" tiene un value_type no soportado.'
        );
    }


    /**
     * Convierte un valor PHP a las columnas físicas
     * correspondientes de setting_values.
     */
    protected function normalizeForStorage(
        SettingDefinition $definition,
        $value
    ): array {
        $result = [
            'value_text' =>
                null,

            'value_number' =>
                null,

            'value_json' =>
                null,
        ];


        switch ($definition->value_type) {

            case 'boolean':

                $result['value_number'] =
                    $this->normalizeBoolean(
                        $value
                    )
                        ? 1
                        : 0;

                break;


            case 'integer':

                if (
                    $value === null ||
                    $value === ''
                ) {
                    $result['value_number'] =
                        null;
                } else {

                    if (
                        filter_var(
                            $value,
                            FILTER_VALIDATE_INT
                        ) === false
                    ) {
                        throw new RuntimeException(
                            'El valor de "' .
                            $definition->key .
                            '" debe ser un número entero.'
                        );
                    }

                    $result['value_number'] =
                        (int) $value;
                }

                break;


            case 'decimal':

                if (
                    $value === null ||
                    $value === ''
                ) {
                    $result['value_number'] =
                        null;
                } else {

                    if (!is_numeric($value)) {
                        throw new RuntimeException(
                            'El valor de "' .
                            $definition->key .
                            '" debe ser numérico.'
                        );
                    }

                    $result['value_number'] =
                        (float) $value;
                }

                break;


            case 'json':

                if ($value !== null) {

                    if (
                        !is_array($value) &&
                        !is_object($value)
                    ) {
                        throw new RuntimeException(
                            'El valor de "' .
                            $definition->key .
                            '" debe ser una estructura JSON válida.'
                        );
                    }

                    $result['value_json'] =
                        $value;
                }

                break;


            case 'enum':

                if ($value === null || $value === '') {
                    $result['value_text'] = null;

                    break;
                }

                $value =
                    (string) $value;

                $options =
                    $definition->options_json
                    ?? [];


                if (
                    empty($options) ||
                    !is_array($options)
                ) {
                    throw new RuntimeException(
                        'El setting "' .
                        $definition->key .
                        '" no tiene opciones configuradas.'
                    );
                }


                if (
                !in_array(
                    $value,
                    $options,
                    true
                )
                ) {
                    throw new RuntimeException(
                        'El valor "' .
                        $value .
                        '" no es válido para la configuración "' .
                        $definition->key .
                        '".'
                    );
                }


                $result['value_text'] =
                    $value;

                break;

            case 'string':

                $result['value_text'] =
                    $value === null
                        ? null
                        : (string) $value;

                break;


            default:

                throw new RuntimeException(
                    'El setting "' .
                    $definition->key .
                    '" tiene un value_type no soportado.'
                );
        }


        return $result;
    }


    /**
     * Convierte las distintas representaciones
     * comunes de boolean.
     */
    protected function normalizeBoolean(
        $value
    ): bool {
        if (is_bool($value)) {
            return $value;
        }


        if (
            $value === 1 ||
            $value === '1'
        ) {
            return true;
        }


        if (
            $value === 0 ||
            $value === '0'
        ) {
            return false;
        }


        $value =
            strtolower(
                trim(
                    (string) $value
                )
            );


        if (
        in_array(
            $value,
            [
                'true',
                'yes',
                'si',
                'sí',
                'on',
            ],
            true
        )
        ) {
            return true;
        }


        if (
        in_array(
            $value,
            [
                'false',
                'no',
                'off',
                '',
            ],
            true
        )
        ) {
            return false;
        }


        throw new RuntimeException(
            'El valor recibido no representa un boolean válido.'
        );
    }


    public function getForCompany(
        string $key,
        int $companyId
    ) {
        $definition = $this->getDefinition($key);

        if ($definition->scope !== 'company') {
            throw new RuntimeException(
                'El setting "' .
                $key .
                '" no tiene scope company.'
            );
        }

        $tenantId = TenantContext::tenantId();

        $company = \App\Company::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $companyId)
            ->first();

        if (!$company) {
            throw new RuntimeException(
                'La empresa indicada no pertenece al grupo empresarial actual.'
            );
        }

        $value = SettingValue::query()
            ->where(
                'setting_definition_id',
                $definition->id
            )
            ->where(
                'tenant_id',
                $tenantId
            )
            ->where(
                'company_id',
                $companyId
            )
            ->whereNull(
                'branch_id'
            )
            ->first();

        if (!$value) {
            return $this->castValue(
                $definition,
                $definition->default_value,
                null,
                null
            );
        }

        return $this->castValue(
            $definition,
            $value->value_text,
            $value->value_number,
            $value->value_json
        );
    }
}