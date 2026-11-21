<?php
namespace App\Services;

class GreVehiclePlate
{
    public static function normalize($value)
    {
        if (!is_string($value)) { return $value; }
        $value = strtoupper(trim($value));
        // Solo separador habitual tras tres caracteres. Otros símbolos no se eliminan.
        return preg_replace('/^([A-Z0-9]{3})-([A-Z0-9]{3,5})$/D', '$1$2', $value);
    }
}
