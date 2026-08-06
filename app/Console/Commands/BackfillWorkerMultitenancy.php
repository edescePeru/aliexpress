<?php

namespace App\Console\Commands;

use App\Branch;
use App\Company;
use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillWorkerMultitenancy extends Command
{
    protected $signature = 'multitenancy:backfill-workers
        {tenant_id : ID del tenant}
        {company_id : ID de la empresa}
        {branch_id : ID del local}';

    protected $description =
        'Asigna tenant, empresa y local a trabajadores históricos sin contexto.';

    public function handle()
    {
        $tenantId = (int) $this->argument('tenant_id');
        $companyId = (int) $this->argument('company_id');
        $branchId = (int) $this->argument('branch_id');

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $this->error('El tenant indicado no existe.');
            return 1;
        }

        $company = Company::where('id', $companyId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$company) {
            $this->error(
                'La empresa indicada no pertenece al tenant.'
            );

            return 1;
        }

        $branch = Branch::where('id', $branchId)
            ->where('company_id', $companyId)
            ->first();

        if (!$branch) {
            $this->error(
                'El local indicado no pertenece a la empresa.'
            );

            return 1;
        }

        $count = DB::table('workers')
            ->whereNull('tenant_id')
            ->count();

        if ($count === 0) {
            $this->info(
                'No existen trabajadores pendientes de migrar.'
            );

            return 0;
        }

        $this->info(
            "Se actualizarán {$count} trabajadores."
        );

        if (!$this->confirm('¿Desea continuar?')) {
            $this->warn('Operación cancelada.');
            return 0;
        }

        DB::transaction(function () use (
            $tenantId,
            $companyId,
            $branchId
        ) {
            DB::table('workers')
                ->whereNull('tenant_id')
                ->update([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'updated_at' => now(),
                ]);
        });

        $this->info(
            'Trabajadores migrados correctamente.'
        );

        return 0;
    }
}
