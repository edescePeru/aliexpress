<?php
namespace App\Services;

use App\{CashMovement,Sale};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashRegularizationGuard
{
    public function movement(CashMovement $movement): void
    {
        if($movement->balance_effect===false||$movement->regularization_source_id)
            throw ValidationException::withMessages([
                'cash_movement'=>'El cobro fue regularizado en otra sesión. Su reversión requiere conciliación.',
            ]);
    }

    public function sale(Sale $sale): void
    {
        $late=CashMovement::query()->where('tenant_id',$sale->tenant_id)->where('company_id',$sale->company_id)
            ->where('balance_effect',false)->where(function($q)use($sale){
                $q->where('sale_id',$sale->id)->orWhereIn('id',DB::table('sale_partial_payments')
                    ->select('cash_movement_id')->where('sale_id',$sale->id)
                    ->where('tenant_id',$sale->tenant_id)->where('company_id',$sale->company_id));
            })->exists();
        if($late)throw ValidationException::withMessages([
            'sale_id'=>'La venta tiene un cobro regularizado en otra sesión. Su reversión o anulación requiere conciliación.',
        ]);
    }
}
