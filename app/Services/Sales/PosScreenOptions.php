<?php
namespace App\Services\Sales;
use App\{Category,TipoPago,Worker};
use App\Services\SettingService;
use App\Support\TenantContext as Context;
class PosScreenOptions
{
    public function data(string $version): array
    {
        app(PosCatalogReadService::class)->authorize($version);
        $cash=app(PosCashService::class);
        $settings=app(SettingService::class);
        $cashBoxes=$cash->selectableBoxes()->where('currency',(string)$settings->get('finance.base_currency'))->orderBy('position')->orderBy('name')->get();
        $subtypes=$cash->subtypes($cashBoxes->pluck('id')->all())->orderBy('position')->orderBy('name')->get();
        // The setting can display the optional selector; never requires a user-worker link.
        $askWorker=(bool)$settings->get('pos.require_worker');
        $pagos_parciales=$settings->get('sales.allow_partial_payments')?'s':'n';
        $workers=Worker::query()->where('tenant_id',Context::tenantId())->where('company_id',Context::companyId())
            ->where('enable',true)->where(function($q){$q->whereNull('branch_id')->orWhere('branch_id',Context::branchId());})
            ->orderBy('first_name')->orderBy('last_name')->get();
        $categories=Category::query()->get();$tipoPagos=TipoPago::query()->get();
        return compact('cashBoxes','subtypes','askWorker','pagos_parciales','workers','categories','tipoPagos');
    }
}
