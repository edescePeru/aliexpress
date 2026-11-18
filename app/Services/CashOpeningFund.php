<?php

namespace App\Services;

use App\CashBox;
use App\CashRegister;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashOpeningFund
{
    // The caller holds the box lock and owns the opening transaction.
    public function record(CashBox $box, CashRegister $register, array $data): void
    {
        $amount = (string) $register->opening_balance;
        if (bccomp($amount, '0', 2) === 0) {
            return;
        }

        $origin = $data['opening_origin'] ?? null;
        if ($origin === 'consolidated') {
            $balance = (string) ($box->current_balance ?? '0');
            if (bccomp($balance, $amount, 2) < 0) {
                throw ValidationException::withMessages(['opening_balance' => 'El saldo rendido de esta caja es insuficiente.']);
            }
            $box->current_balance = bcsub($balance, $amount, 2);
            $box->save();
        } elseif ($origin !== 'external' || trim($data['opening_provenance'] ?? '') === '') {
            throw ValidationException::withMessages([
                'opening_origin' => 'Indique el origen del fondo. Para fondos externos, indique su procedencia.',
            ]);
        }

        DB::table('cash_opening_funds')->insert([
            'cash_register_id' => $register->id,
            'tenant_id' => $register->tenant_id,
            'company_id' => $register->company_id,
            'branch_id' => $register->branch_id,
            'cash_box_id' => $box->id,
            'user_id' => $register->user_id,
            'origin' => $origin,
            'amount' => $amount,
            'provenance' => $origin === 'external' ? $data['opening_provenance'] : null,
            'observation' => $data['opening_observation'] ?? null,
            'created_at' => $register->opening_time,
        ]);
    }
}
