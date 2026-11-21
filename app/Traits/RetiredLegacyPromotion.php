<?php
namespace App\Traits;
use Illuminate\Validation\ValidationException;
trait RetiredLegacyPromotion
{
    public static function bootRetiredLegacyPromotion()
    {
        // Evita que consumidores legacy sigan seleccionando o consumiendo promociones.
        static::addGlobalScope('retired_legacy_promotion', function ($q) { $q->whereRaw('1 = 0'); });
        $reject = function ($model) { throw ValidationException::withMessages(['promotion'=>'Las promociones legacy fueron retiradas. Sus registros históricos no se modifican.']); };
        static::saving($reject);
        static::deleting($reject);
    }
}
