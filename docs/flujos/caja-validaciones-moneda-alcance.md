# Validaciones de caja y subtipo

CASH-01 conserva la moneda opcional en efectivo y valida PEN/USD cuando se informa. La cuenta bancaria vinculada determina la moneda de una caja bancaria, validada dentro de PEN/USD. La mutabilidad de moneda en cajas utilizadas se trata en CASH-02.

CASH-05 normaliza cash_box_id omitido a null y conserva global, vacío y null como alcance global. Un ID específico debe tener formato numérico y corresponder a una caja activa de la empresa que admita subtipos. Las reglas de cambios históricos de diferidos siguen en CASH-03/04.
