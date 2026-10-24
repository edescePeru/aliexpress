<?php
namespace App\Services\Quotes;

use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteSaleActions
{
    public function read(int $id, string $field): array
    {
        app(QuoteBranchCatalog::class)->validateContext();
        $quote = $this->quote($id, false);
        if ($field === 'decimals') { return ['decimals' => (int) (bool) $quote->state_decimals]; }
        throw new \LogicException('Consulta de opción no admitida.');
    }

    public function change($request, int $id, string $action, $value = null): void
    {
        app(QuoteBranchContext::class)->validateForm($request);
        DB::transaction(function () use ($id, $action, $value) {
            $quote = $this->quote($id, true);
            if (!in_array($quote->state, ['created', 'confirmed'], true)) { $this->invalid('El estado actual de la cotización no permite esta operación.'); }
            $changes = [];
            switch ($action) {
                case 'confirm':
                    $this->open($quote);
                    if (DB::table('sales')->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())
                        ->where('quote_id', $id)->where('state_annulled', 0)->exists()) { $this->invalid('La cotización ya tiene una venta activa.'); }
                    $changes = ['state' => 'confirmed'];
                    break;
                case 'send': $this->open($quote); $changes = ['send_state' => true]; break;
                case 'close': $changes = ['state_active' => 'close']; break;
                case 'reopen': $changes = ['state_active' => 'open']; break;
                case 'decimals':
                    if (!in_array($value, [0, 1, '0', '1'], true)) { $this->invalid('Seleccione mostrar u ocultar decimales.'); }
                    $changes = ['state_decimals' => (int) $value];
                    break;
                default: throw new \LogicException('Acción no admitida.');
            }
            DB::table('quotes')->where('id', $id)->where('tenant_id', TenantContext::tenantId())->where('company_id', TenantContext::companyId())
                ->where('branch_id', TenantContext::branchId())->update($changes + ['updated_at' => now()]);
        });
    }

    private function quote(int $id, bool $lock)
    {
        $query = DB::table('quotes')->where('id', $id)->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())->where('branch_id', TenantContext::branchId());
        if ($lock) { $query->lockForUpdate(); }
        $quote = $query->first();
        if (!$quote) { $this->invalid('La cotización no pertenece al local activo o está pendiente de asignación.'); }
        return $quote;
    }
    private function open($quote): void { if ($quote->state_active !== 'open') { $this->invalid('Reabra la cotización antes de esta operación.'); } }
    private function invalid(string $message): void { throw ValidationException::withMessages(['quote_id' => $message]); }
}
