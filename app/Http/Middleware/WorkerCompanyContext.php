<?php
namespace App\Http\Middleware;

use App\Services\Workers\WorkerAccess;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Closure;

class WorkerCompanyContext
{
    public function handle($request, Closure $next)
    {
        $access = app(WorkerAccess::class); $access->context();
        $action = $request->route()->getActionMethod();
        if (in_array($action, ['store', 'update'], true)) {
            foreach (['tenant' => TenantContext::tenantId(), 'company' => TenantContext::companyId(), 'branch' => TenantContext::branchId()] as $key => $id) {
                $value = $request->input('form_'.$key.'_id');
                if (!is_scalar($value) || (string) $value !== (string) $id) { throw ValidationException::withMessages(['context' => 'Cambió el contexto o el formulario está desactualizado. Recargue la página.']); }
            }
        }
        $worker = null;
        if (in_array($action, ['edit', 'update', 'destroy', 'enable'], true)) { $worker = $request->route('id'); }
        if ($action === 'show') { $worker = $request->route('worker'); if (is_object($worker)) { $worker = $worker->getKey(); } }
        if (in_array($action, ['getDataFinishContractWorker', 'getDataFinishContractWorkerEdit', 'getDataFinishContractWorkerDelete'], true)) { $worker = $request->route('worker_id'); }
        if (in_array($action, ['finishContract', 'finishContractDelete'], true)) { $worker = $request->input('worker_id'); }
        if ($worker !== null) { $access->worker($worker); }
        if (in_array($action, ['finishContract', 'finishContractDelete'], true)) {
            abort_unless(DB::table('contracts')->where('id', $request->input('contract_id'))->where('worker_id', $worker)->exists(), 404);
            $finish = $request->input('finish_contract_id');
            if ($action === 'finishContractDelete' || $request->input('type') === 'e') {
                abort_unless(DB::table('finish_contracts')->where('id', $finish)->where('worker_id', $worker)->where('contract_id', $request->input('contract_id'))->exists(), 404);
            }
        }
        return $next($request);
    }
}
