<?php
namespace App\Services\Purchases;

use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth, DB};

class SupplierCreditDocumentAccess
{
    public function paid($id)
    {
        $credit = $this->credit($id);
        $this->filenameOwner('paid', $credit->image_paid, $credit->id);
        return $credit;
    }

    public function payment($id)
    {
        $this->context(); $this->id($id);
        $payment = DB::table('credit_pays')->where('id', $id)->first();
        abort_unless($payment, 404);
        $credit = $this->credit($payment->supplier_credit_id);
        $this->filenameOwner('pays', $payment->image, $credit->id);
        return $payment;
    }

    private function credit($id)
    {
        $this->context(); $this->id($id);
        $tenant = TenantContext::tenantId(); $company = TenantContext::companyId();
        $credit = DB::table('supplier_credits')->where('tenant_id', $tenant)->where('company_id', $company)->where('id', $id)->first();
        abort_unless($credit && DB::table('suppliers')->where('id', $credit->supplier_id)->where('tenant_id', $tenant)->exists(), 404);
        // Include soft-deleted origins for historical reading; never rewrite a reference.
        foreach (['entry_id'=>'entries', 'order_purchase_id'=>'order_purchases', 'order_purchase_finance_id'=>'order_purchase_finances'] as $field=>$table) {
            if (!empty($credit->$field)) {
                abort_unless(DB::table($table)->where('id', $credit->$field)->where('tenant_id', $tenant)
                    ->where('company_id', $company)->where('supplier_id', $credit->supplier_id)->exists(), 404);
            }
        }
        return $credit;
    }

    private function filenameOwner(string $kind, $filename, $creditId): void
    {
        abort_unless(is_string($filename) && $filename !== '', 404);
        $query = $kind === 'paid'
            ? DB::table('supplier_credits')->where('image_paid', $filename)->where('id', '<>', $creditId)
            : DB::table('credit_pays')->where('image', $filename)->where(function ($query) use ($creditId) {
                $query->where('supplier_credit_id', '<>', $creditId)->orWhereNull('supplier_credit_id');
            });
        // Filename-only historical storage cannot establish an owner when another credit also references it.
        abort_unless(!$query->exists(), 404);
    }

    private function id($id): void { abort_unless(is_scalar($id) && preg_match('/^[1-9][0-9]*$/D', (string) $id), 404); }

    private function context(): void
    {
        $user = Auth::user(); $tenant = TenantContext::tenantIdOrNull(); $company = TenantContext::companyIdOrNull();
        $permission = false;
        if ($user) { foreach (['control_credit','edit_credit','pay_credit','nopay_credit'] as $name) { if ($user->can($name)) { $permission = true; break; } } }
        abort_unless($user && $user->enable && !$user->isPlatformAdmin() && $permission && $tenant && $company
            && (int) $user->tenant_id === (int) $tenant
            && DB::table('companies')->where('id', $company)->where('tenant_id', $tenant)->where('is_active', true)->exists()
            && DB::table('company_user')->where('company_id', $company)->where('user_id', $user->id)->where('is_active', true)->exists(), 403);
    }
}
