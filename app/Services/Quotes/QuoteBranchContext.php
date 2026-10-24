<?php
namespace App\Services\Quotes;

use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteBranchContext
{
    public function validateForm($request): void
    {
        app(QuoteBranchCatalog::class)->validateContext();
        foreach (['tenant' => TenantContext::tenantId(), 'company' => TenantContext::companyId(), 'branch' => TenantContext::branchId()] as $key => $expected) {
            $value = $request->input('form_'.$key.'_id');
            if (!is_scalar($value) || !preg_match('/^[1-9][0-9]*$/D', (string) $value) || (string) $value !== (string) $expected) {
                throw ValidationException::withMessages(['context' => 'La empresa o sucursal cambió, o el formulario está desactualizado. Recargue la página antes de guardar.']);
            }
        }
    }

    public function assertQuote($id, bool $allowHistoricalRead = false): void
    {
        app(QuoteBranchCatalog::class)->validateContext();
        if (!is_scalar($id) || !preg_match('/^[1-9][0-9]*$/D', (string) $id)) {
            abort(404);
        }
        $quote = DB::table('quotes')->where('id', $id)->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())->first(['branch_id']);
        if (!$quote) { abort(404); }
        if ($quote->branch_id === null) {
            if ($allowHistoricalRead) { return; }
            throw ValidationException::withMessages(['quote_id' => 'La cotización está pendiente de asignación de sucursal. Revise su contexto y sus reservas antes de modificarla o convertirla en venta.']);
        }
        if ((int) $quote->branch_id !== TenantContext::branchId()) { abort(404); }
    }
}
