<?php

namespace App\Services\Purchases;

use Carbon\Carbon;

class SupplierCreditExpiration
{
    public static function evaluate($credit, $warningDays, Carbon $today = null)
    {
        $today = $today ? $today->copy()->setTimezone('America/Lima')->startOfDay() : Carbon::now('America/Lima')->startOfDay();
        $paid = $credit->state_credit === 'paid_out' || $credit->state_pay === 'canceled';
        if ($paid || !$credit->date_expiration) {
            return ['days' => $credit->days_to_expiration, 'state' => $credit->state_credit, 'eligible' => false];
        }
        // El campo es DATE: conservar su día calendario, sin desplazarlo por zona del cast.
        $date = $credit->date_expiration instanceof \DateTimeInterface
            ? $credit->date_expiration->format('Y-m-d') : $credit->date_expiration;
        $expiration = Carbon::parse($date, 'America/Lima')->startOfDay();
        $days = (int) $today->diffInDays($expiration, false);
        $state = $days < 0 ? 'expired' : ($days < max(0, (int) $warningDays) ? 'by_expire' : 'outstanding');
        return ['days' => $days, 'state' => $state, 'eligible' => true];
    }

    public static function project($credit, $warningDays)
    {
        $value = self::evaluate($credit, $warningDays);
        if ($value['eligible']) {
            $credit->days_to_expiration = $value['days'];
            $credit->state_credit = $value['state'];
        }
        return $credit;
    }
}
