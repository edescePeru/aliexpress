<?php
namespace App\Services\Purchases;

use App\OrderPurchase;
use App\Services\Materials\MaterialParameters;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class NormalPurchaseReadAccess
{
    public function authorize(?string $permission = null): void
    {
        $actor = Auth::user();
        abort_unless($actor, 401);
        abort_unless(!$permission || $actor->can($permission), 403);
        // Purchase parents are company-wide, not branch-owned. Inventory selectors retain their branch guard.
        app(MaterialParameters::class)->context();
    }

    public function query(bool $deleted = false)
    {
        $this->authorize();
        $query = OrderPurchase::query()->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())->where('type', 'n');
        return $deleted ? $query->onlyTrashed() : $query;
    }

    public function missingCodes(array $codes): array
    {
        $numbers = [];
        foreach ($codes as $code) {
            if (!is_string($code) || !preg_match('/^OC-([0-9]{1,15})$/D', $code, $match)) { continue; }
            $number = (int) $match[1];
            if ($number > 0) { $numbers[$number] = $number; }
        }
        sort($numbers, SORT_NUMERIC);
        $cursor = 1; $lost = [];
        foreach ($numbers as $number) {
            // Never allocate an unbounded list for a corrupt or extraordinarily distant historical number.
            if (count($lost) + $number - $cursor > 10000) {
                throw ValidationException::withMessages(['code' => 'La secuencia contiene más de 10000 huecos. Revise los códigos históricos antes de consultar el listado.']);
            }
            while ($cursor < $number) {
                $lost[] = ['code' => 'OC-'.str_pad((string) $cursor, 5, '0', STR_PAD_LEFT)];
                $cursor++;
            }
            $cursor = $number + 1;
        }
        return $lost;
    }
}
