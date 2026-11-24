<?php
namespace App\Services\Purchases;

use App\Support\TenantContext;
use Illuminate\Support\Facades\{Auth, DB};

class EntryDocumentAccess
{
    public function authorize(string $kind, string $filename): void
    {
        $user = Auth::user();
        $tenant = TenantContext::tenantIdOrNull(); $company = TenantContext::companyIdOrNull();
        abort_unless($user && $tenant && $company && !$user->isPlatformAdmin() && $user->enable
            && (int) $user->tenant_id === (int) $tenant
            && DB::table('companies')->where('id', $company)->where('tenant_id', $tenant)->where('is_active', true)->exists()
            && DB::table('company_user')->where('company_id', $company)->where('user_id', $user->id)->where('is_active', true)->exists(), 403);
        abort_unless(in_array($kind, ['main', 'observation', 'extra'], true), 404);
        $normal = $this->any(['list_entryPurchase', 'update_entryPurchase']);
        $finance = $this->any(['list_invoice', 'update_invoice']);
        $credit = $this->any(['control_credit', 'edit_credit', 'pay_credit', 'nopay_credit']);
        // Shared fixed placeholders contain no business document or inferred ownership.
        if ($kind !== 'extra' && in_array($filename, ['no_image.png', 'no_image.jpg'], true)) {
            abort_unless($normal || $finance || $credit, 403);
            return;
        }
        if ($kind === 'extra') {
            $rows = DB::table('entry_images as image')->leftJoin('entries as entry', 'entry.id', '=', 'image.entry_id')
                ->where('image.image', $filename)->select('entry.tenant_id', 'entry.company_id', 'entry.finance', 'entry.deleted_at')->get();
        } else {
            $rows = DB::table('entries')->where($kind === 'main' ? 'image' : 'imageOb', $filename)
                ->select('tenant_id', 'company_id', 'finance', 'deleted_at')->get();
        }
        $allowed = false;
        foreach ($rows as $row) {
            // A physical filename referenced across companies is ambiguous: fail closed.
            abort_unless((int) $row->tenant_id === (int) $tenant && (int) $row->company_id === (int) $company, 404);
            if ($row->deleted_at === null) {
                $allowed = $allowed || ($kind === 'extra'
                    ? (!(bool) $row->finance && $this->any(['update_entryPurchase']))
                    : ((bool) $row->finance ? $finance : $normal));
            }
        }
        if ($kind === 'main') {
            $credits = DB::table('supplier_credits')->where('image_invoice', $filename)
                ->select('tenant_id', 'company_id', 'entry_id', 'supplier_id')->get();
            foreach ($credits as $row) {
                abort_unless((int) $row->tenant_id === (int) $tenant && (int) $row->company_id === (int) $company, 404);
                if ($row->entry_id !== null) {
                    // Historical entries may be soft deleted, but must belong to this credit.
                    abort_unless(DB::table('entries')->where('id', $row->entry_id)->where('tenant_id', $tenant)
                        ->where('company_id', $company)->where('supplier_id', $row->supplier_id)->exists(), 404);
                }
                $allowed = $allowed || $credit;
            }
        }
        abort_unless($allowed, 404);
    }

    private function any(array $permissions): bool
    {
        foreach ($permissions as $permission) { if (Auth::user()->can($permission)) { return true; } }
        return false;
    }
}
