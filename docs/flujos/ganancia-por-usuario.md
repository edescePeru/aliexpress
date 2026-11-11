# Ganancia por Usuario

Bloque preparado para validación en la aplicación. La fórmula comercial conserva el criterio anterior de importe de línea menos descuento registrado y costo histórico. Ejemplo con venta de S/ 130 y costo de S/ 100. Ganancia de S/ 30. Los importes se usan tal como están registrados, sin normalizar IGV.

`UserProfitReport` centraliza consulta y cálculo. El alcance es tenant, empresa y local actuales. Este bloque muestra ventas en PEN para que los totales correspondan a la moneda de la pantalla. La fecha es `date_sale`. Las ventas anuladas o revertidas se excluyen. El autor se filtra por `created_by` y se muestra su nombre guardado. Worker es opcional.

Se prioriza `sale_details.total_cost`. Si falta, se usa `unit_cost × quantity` cuando ambos datos permiten reconstruirlo. Un costo cero guardado es válido. Si falta costo, la ganancia de la línea y sus acumulados se muestran como sin costo registrado.

Pendientes de bloques posteriores. Ganancia Diaria y cron multitenant, cobertura de otras monedas y tratamiento de notas de crédito parciales y descuentos globales de cotización. El código vigente prevalece sobre este documento.
