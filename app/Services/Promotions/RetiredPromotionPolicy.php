<?php
namespace App\Services\Promotions;
use Illuminate\Validation\ValidationException;
class RetiredPromotionPolicy
{
    public static function neutral($value): bool
    {
        return $value === null || $value === false || $value === 0
            || (is_string($value) && in_array(strtolower(trim($value)),['','ninguno','none','null','undefined','0'],true));
    }
    public static function reject(): void
    {
        throw ValidationException::withMessages(['promotion'=>'Las promociones legacy fueron retiradas. No se puede aplicar ni copiar una promoción a nuevas líneas. Los documentos históricos no se recalculan automáticamente.']);
    }
    public static function payload(array $data, int $depth = 0): void
    {
        if ($depth > 64) { self::reject(); }
        foreach ($data as $key=>$value) {
            if ($key === 'type_promo' && !self::neutral($value)) { self::reject(); }
            if (is_array($value)) { self::payload($value,$depth+1); }
            elseif (is_string($value) && in_array($key,['equipments','equipment','consumables','equipment_consumables','items','data','details'],true)) {
                $decoded = json_decode($value,true);
                if (is_array($decoded)) { self::payload($decoded,$depth+1); }
            }
        }
    }
}
