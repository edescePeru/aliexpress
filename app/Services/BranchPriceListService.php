<?php
namespace App\Services;

use App\Branch;
use App\PriceList;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchPriceListService
{
    public function availableForCompany(int $companyId)
    {
        abort_unless(DB::table('companies')->where('id', $companyId)
            ->where('tenant_id', TenantContext::tenantId())->exists(), 404);
        return PriceList::query()->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
    }

    public function assignment(int $companyId, $id, bool $lock = false): ?int
    {
        if ($id === null || $id === '') return null;
        if (!is_scalar($id) || !preg_match('/^[1-9][0-9]*$/D', (string) $id)) $this->invalid();
        abort_unless(DB::table('companies')->where('id', $companyId)
            ->where('tenant_id', TenantContext::tenantId())->where('is_active', true)->exists(), 404);
        $query = PriceList::query()->where('id', $id)->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', $companyId)->where('is_active', true);
        if ($lock) $query->lockForUpdate();
        $list = $query->first();
        if (!$list) $this->invalid();
        return (int) $list->id;
    }

    private function invalid(): void
    {
        throw ValidationException::withMessages(['price_list_id' =>
            'Seleccione una lista activa de la misma empresa. La lista asignada no se sustituirá automáticamente por otra.']);
    }

    public function current(bool $lock = false): ?PriceList
    {
        $query = Branch::query()->where('id', TenantContext::branchId())
            ->where('company_id', TenantContext::companyId())->where('is_active', true)
            ->whereHas('company', function ($q) { $q->where('tenant_id', TenantContext::tenantId())->where('is_active', true); });
        if ($lock) $query->lockForUpdate();
        $branch = $query->firstOrFail();
        if ($branch->price_list_id !== null) {
            $id = $this->assignment((int) $branch->company_id, $branch->price_list_id, $lock);
            return PriceList::query()->where('tenant_id', TenantContext::tenantId())
                ->where('company_id', $branch->company_id)->findOrFail($id);
        }
        $query = PriceList::query()->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', $branch->company_id)->where('is_active', true)->where('is_default', true)->orderBy('id')->limit(2);
        if ($lock) $query->lockForUpdate();
        $lists = $query->get();
        if ($lists->count() > 1) throw ValidationException::withMessages(['price_list_id' => 'La empresa tiene más de una lista predeterminada activa.']);
        return $lists->first();
    }
}
