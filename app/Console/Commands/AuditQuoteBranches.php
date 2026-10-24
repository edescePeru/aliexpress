<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Schema};

class AuditQuoteBranches extends Command
{
    protected $signature = 'quotes:branch-audit {--company= : Limitar a una empresa}';
    protected $description = 'Diagnóstico de sucursales de cotizaciones; no asigna contexto ni modifica reservas.';

    public function handle()
    {
        $company = $this->option('company');
        if ($company !== null && !preg_match('/^[1-9][0-9]{0,17}$/D', (string) $company)) {
            $this->error('El identificador de empresa no es válido.'); return 1;
        }
        $hasBranch = Schema::hasColumn('quotes', 'branch_id');
        if (!$hasBranch) { $this->warn('quotes aún no tiene branch_id; el diagnóstico puede ejecutarse antes de migrar.'); }
        $query = DB::table('quotes')->select('tenant_id', 'company_id')->groupBy('tenant_id', 'company_id')->orderBy('tenant_id')->orderBy('company_id');
        if ($company !== null) { $query->where('company_id', (int) $company); }
        $rows = [];
        foreach ($query->get() as $context) {
            $quotes = DB::table('quotes')->where('tenant_id', $context->tenant_id)->where('company_id', $context->company_id);
            $validCompany = DB::table('companies')->where('id', $context->company_id)->where('tenant_id', $context->tenant_id)->exists();
            $main = DB::table('branches')->where('company_id', $context->company_id)->where('is_active', true)->where('is_main', true)->orderBy('id')->pluck('id')->all();
            $unassigned = $hasBranch ? (clone $quotes)->whereNull('branch_id')->count() : $quotes->count();
            $invalid = 0;
            if ($hasBranch) {
                $invalid = (clone $quotes)->whereNotNull('branch_id')->whereNotExists(function ($q) {
                    $q->selectRaw('1')->from('branches')->whereColumn('branches.id', 'quotes.branch_id')->whereColumn('branches.company_id', 'quotes.company_id');
                })->count();
            }
            $generalReservations = DB::table('quote_stock_lots as r')->join('quotes as q', 'q.id', '=', 'r.quote_id')
                ->join('warehouses as w', 'w.id', '=', 'r.warehouse_id')->where('q.tenant_id', $context->tenant_id)->where('q.company_id', $context->company_id)
                ->where('w.tenant_id', $context->tenant_id)->where('w.company_id', $context->company_id)->whereNull('w.branch_id')->where('r.quantity', '>', 0)->count();
            $rows[] = [$context->tenant_id, $context->company_id, $quotes->count(), $unassigned, $invalid,
                $validCompany && count($main) === 1 ? $main[0] : 'Revisar', $generalReservations];
        }
        $this->table(['Tenant', 'Empresa', 'Cotizaciones', 'Sin local', 'Local incompatible', 'Principal único (ID)', 'Reservas en general'], $rows);
        $this->line('No se asignó ninguna sucursal ni se modificaron cotizaciones, ventas o reservas.');
        $this->comment('El local de un lote reservado no demuestra dónde se creó la cotización.');
        return 0;
    }
}
