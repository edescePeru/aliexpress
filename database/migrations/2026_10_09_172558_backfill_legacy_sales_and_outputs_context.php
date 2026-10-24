<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class BackfillLegacySalesAndOutputsContext extends Migration
{
    public function up()
    {
        // IDs autorizados por el usuario para TODO el histórico actual.
        $tenantId = 1;
        $companyId = 1;
        $branchId = 1;

        DB::transaction(function () use ($tenantId, $companyId, $branchId) {
            $tenant = DB::table('tenants')->where('id', $tenantId)->first();
            $company = DB::table('companies')->where('id', $companyId)
                ->where('tenant_id', $tenantId)->first();
            $branch = DB::table('branches')->where('id', $branchId)
                ->where('company_id', $companyId)->first();

            if (!$tenant || !$company || !$branch) {
                throw new \RuntimeException('El contexto histórico 1/1/1 no es consistente.');
            }

            $tables = [
                'sales' => true,
                'sale_details' => false,
                'sale_partial_payments' => true,
                'outputs' => true,
                'output_details' => false,
            ];

            // No sobreescribir un contexto diferente encontrado al aplicar.
            foreach ($tables as $name => $hasBranch) {
                $values = ['tenant_id' => $tenantId, 'company_id' => $companyId];
                if ($hasBranch) {
                    $values['branch_id'] = $branchId;
                }
                $conflicts = DB::table($name)->where(function ($query) use ($values) {
                    foreach ($values as $column => $value) {
                        $query->orWhere(function ($q) use ($column, $value) {
                            $q->whereNotNull($column)->where($column, '<>', $value);
                        });
                    }
                })->exists();
                if ($conflicts) {
                    throw new \RuntimeException('Contexto histórico contradictorio en ' . $name);
                }
            }

            foreach ($tables as $name => $hasBranch) {
                $values = ['tenant_id' => $tenantId, 'company_id' => $companyId];
                if ($hasBranch) {
                    $values['branch_id'] = $branchId;
                }
                DB::table($name)->update($values);

                $missing = DB::table($name)->where(function ($query) use ($values) {
                    foreach ($values as $column => $value) {
                        $query->orWhereNull($column);
                    }
                })->exists();
                if ($missing) {
                    throw new \RuntimeException('Quedaron registros sin contexto en ' . $name);
                }
            }
        });
    }

    public function down()
    {
        // No borrar contexto: puede haber registros nuevos después del backfill.
        // El down de la migración estructural elimina las columnas si se revierte.
    }
}
