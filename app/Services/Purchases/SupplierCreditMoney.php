<?php

namespace App\Services\Purchases;

use Illuminate\Validation\ValidationException;

class SupplierCreditMoney
{
    public static function cents($value)
    {
        if (PHP_INT_SIZE < 8) { self::invalid(); }
        if (!is_string($value) && !is_int($value) && !is_float($value)) { self::invalid(); }
        $value = (string) $value;
        if (!preg_match('/^(-?)([0-9]+)(?:\.([0-9]{1,2}))?$/D', $value, $parts)) { self::invalid(); }
        $integer = ltrim($parts[2], '0');
        if (strlen($integer) > 16) { self::invalid(); }
        $cents = (int) ($integer === '' ? '0' : $integer) * 100 + (int) str_pad($parts[3] ?? '', 2, '0');
        return $parts[1] === '-' ? -$cents : $cents;
    }

    public static function decimal($cents)
    {
        if ($cents === PHP_INT_MIN) { self::invalid(); }
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);
        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function add($left, $right)
    {
        if (($right > 0 && $left > PHP_INT_MAX - $right) || ($right < 0 && $left < PHP_INT_MIN - $right)) { self::invalid(); }
        return $left + $right;
    }

    private static function invalid()
    {
        throw ValidationException::withMessages(['amount' => 'El importe requiere revisión: usa hasta dos decimales dentro del rango admitido.']);
    }
}
