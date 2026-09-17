<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class BackfillLegacyCompanyBankAccounts extends Migration
{
    public function up()
    {
        $company = DB::table('companies')
            ->where('id', 1)
            ->first();

        if (!$company) {
            return;
        }

        $legacyNames = [
            'title_cuenta_1',
            'nro_cuenta_1',
            'cci_cuenta_1',
            'img_cuenta_1',
            'owner_cuenta_1',

            'title_cuenta_2',
            'nro_cuenta_2',
            'cci_cuenta_2',
            'img_cuenta_2',
            'owner_cuenta_2',
        ];

        $legacyValues = DB::table('data_generals')
            ->whereIn('name', $legacyNames)
            ->pluck('valueText', 'name')
            ->toArray();

        $accounts = [
            [
                'position' => 1,
                'title' => $this->clean(
                    $legacyValues['title_cuenta_1'] ?? null
                ),
                'account_number' => $this->clean(
                    $legacyValues['nro_cuenta_1'] ?? null
                ),
                'cci' => $this->clean(
                    $legacyValues['cci_cuenta_1'] ?? null
                ),
                'image' => $this->clean(
                    $legacyValues['img_cuenta_1'] ?? null
                ),
                'account_holder' => $this->clean(
                    $legacyValues['owner_cuenta_1'] ?? null
                ),
            ],
            [
                'position' => 2,
                'title' => $this->clean(
                    $legacyValues['title_cuenta_2'] ?? null
                ),
                'account_number' => $this->clean(
                    $legacyValues['nro_cuenta_2'] ?? null
                ),
                'cci' => $this->clean(
                    $legacyValues['cci_cuenta_2'] ?? null
                ),
                'image' => $this->clean(
                    $legacyValues['img_cuenta_2'] ?? null
                ),
                'account_holder' => $this->clean(
                    $legacyValues['owner_cuenta_2'] ?? null
                ),
            ],
        ];

        $hasDefault = DB::table('company_bank_accounts')
            ->where('tenant_id', $company->tenant_id)
            ->where('company_id', $company->id)
            ->where('is_default', true)
            ->exists();

        foreach ($accounts as $account) {
            // Sin número de cuenta no generamos una cuenta bancaria.
            if (empty($account['account_number'])) {
                continue;
            }

            // Evita duplicados si por alguna razón ya fue migrada.
            $exists = DB::table('company_bank_accounts')
                ->where('tenant_id', $company->tenant_id)
                ->where('company_id', $company->id)
                ->where(
                    'account_number',
                    $account['account_number']
                )
                ->exists();

            if ($exists) {
                continue;
            }

            $isDefault = !$hasDefault;

            DB::table('company_bank_accounts')->insert([
                'tenant_id' => $company->tenant_id,
                'company_id' => $company->id,

                // No existen de forma fiable en DataGeneral.
                'bank_id' => null,
                'currency' => null,

                'title' => $account['title']
                    ?: 'Cuenta bancaria ' . $account['position'],

                'account_number' => $account['account_number'],
                'cci' => $account['cci'],
                'account_holder' => $account['account_holder'],
                'image' => $account['image'],

                'position' => $account['position'],
                'is_default' => $isDefault,
                'is_active' => true,

                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($isDefault) {
                $hasDefault = true;
            }
        }
    }

    public function down()
    {
        // El backfill de datos legacy no se revierte automáticamente
        // para evitar eliminar cuentas que hayan sido modificadas
        // o utilizadas después de la migración.
    }

    private function clean($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }
}
