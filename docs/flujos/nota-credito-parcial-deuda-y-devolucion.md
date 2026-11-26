# Nota de crédito parcial: productos, deuda y devolución

Fecha: 25/11/2026. Entrada: `/dashboard/listado/ventas` → acciones → «Generar nota de crédito parcial».

## Reglas acordadas

- La parcial corresponde a devolución de productos, motivo 07. No incorpora descuentos sin devolución.
- Las cantidades del modal son unidades físicas de inventario. Si la venta fue por presentación, el importe se calcula proporcionalmente sobre las unidades vendidas guardadas; no se toma el precio de una caja completa por cada unidad.
- Se reduce primero la deuda y solo se devuelve el excedente cobrado. Los cobros se consideran por su bruto: las comisiones no reducen lo pagado por el cliente. Un recibo posterior de regularización no es otro cobro del cliente; el vuelto tampoco se considera dinero retenido.
- Se elige un solo canal de devolución, incluso distinto a los cobros originales. Debe pertenecer a la empresa, ser compartido o del local actual, estar activo, tener la moneda de la venta y una única sesión abierta del usuario que ejecuta la operación en ese local. Si usa subtipos bancarios, se exige uno válido.
- Un subtipo diferido elegido para devolver dinero identifica el canal; el egreso es inmediato y afecta saldo. No crea otra cuenta pendiente por abonar.
- Cuando todo el importe reduce deuda, no se exige canal ni sesión y no se registra egreso.
- **Decisión confirmada del negocio: esperar aceptación SUNAT para aplicar stock, deuda y devolución.** La respuesta pendiente solo reserva la solicitud y sus cantidades.

## Cálculo

Se conserva `sales.importe_total`, que representa el comprobante original.

- Neto comercial = importe original − notas ya aplicadas internamente.
- Cobrado retenido = cobros originales activos − devoluciones de dinero ya aplicadas.
- Deuda actual = máximo(neto comercial − cobrado retenido, 0).
- Reducción de deuda = mínimo(importe de la nueva nota, deuda actual).
- Dinero a devolver = importe de la nota − reducción de deuda.

Los cálculos de decisión monetaria usan centavos. Las devoluciones sucesivas distribuyen los centavos del importe original de cada línea, evitando que tres devoluciones de una línea de S/ 10 dejen S/ 0.01 pendiente. Se respetan los datos tributarios y la moneda guardados; no se consulta el precio actual del catálogo para valorar la devolución.

## Secuencia

1. El permiso de anulación y el contexto siguen protegidos por `SaleListGuard` y `SaleListAccess`.
2. Al abrir, se obtiene una vista del saldo, los productos disponibles y las cajas con sesión propia abierta.
3. Al confirmar, se bloquea la venta; se verifica que el saldo no haya cambiado, que las cantidades/ítems estén disponibles, la procedencia del stock, la configuración fiscal y el destino del egreso.
4. Se guarda una nota con su clave de solicitud única, referencia estable, reducción de deuda e importe a devolver. La preparación de stock se verifica con un punto de restauración y no deja cambios físicos.
5. Se confirma esa transacción **antes** de contactar al proveedor. Un fallo de comunicación no borra la solicitud y repetirla no emite un documento nuevo.
6. Se guarda la respuesta fiscal. Si sigue pendiente, no se mueve stock ni dinero. Si se rechaza, se conserva la nota sin aplicar movimientos.
7. Cuando se acepta, una transacción bloquea venta y nota, reingresa los productos/ítems a sus lotes y ubicaciones originales, sincroniza niveles y registra el egreso en la sesión propia del canal elegido. Al final marca la aplicación interna como realizada.
8. Otra consulta no vuelve a aplicar la misma nota. Si cerró la sesión mientras se esperaba aceptación, se conserva la aceptación fiscal y la operación queda pendiente: abrir una sesión propia en el mismo canal y local y volver a consultar. No se altera una sesión antigua cerrada ni su arqueo.

## Abonos y reportes

El historial de pagos, el importe efectivo del listado y sus filtros de cumplimiento usan el neto después de devoluciones y los cobros retenidos. Los abonos nuevos no pueden superar esa deuda y se bloquean mientras una nota esté pendiente de resolución.

Los reportes que usan `UserProfitReport` restan cantidades e importe de devoluciones aplicadas. Reducen proporcionalmente el costo histórico guardado de la línea, conservando su precisión; no reemplazan ese costo por el costo actual del producto.

Una venta con devoluciones parciales pendientes o aplicadas no se puede anular por el camino completo anterior: este podría devolver dos veces stock o dinero. Se deben devolver solo las cantidades restantes mediante notas parciales. La interfaz oculta esa anulación y el servidor la bloquea antes de llamar al proveedor. Tampoco se permite eliminar un abono histórico de una venta con notas pendientes o aplicadas: requiere conciliación.

## Históricos y recuperación

Los campos nuevos son nulos para notas anteriores. No se recalculan devoluciones antiguas ni se corrigen saldos automáticamente. Si faltan vínculos de cobros, egresos o distribución histórica de lotes, se pide conciliación en lugar de adivinar importes o ubicaciones.

Una nota sin número confirmado tras un fallo de comunicación conserva su referencia de generación. No se reenvía automáticamente. Debe contrastarse ese envío con Nubefact antes de cualquier recuperación; «documento no confirmado» no equivale a autorización para emitir otra nota.

No se borra ningún cobro original, movimiento arqueado, comisión ni regularización anterior para efectuar el nuevo egreso.

## Alcance de inventario

Este paquete aplica el reingreso físico sobre `stock_lots`, `inventory_levels` e ítems y conserva los detalles de la nota. **No modifica `inventory_movements_view`, `KardexMovementQuery` ni `InventoryCostService`.** La representación de estas devoluciones como movimientos de entrada en el Kardex y su uso por ese cálculo de costo requieren un bloque adicional; no se debe interpretar este paquete como cierre de esa conciliación histórica.

## Archivos del paquete

| Archivo | Cambio |
| --- | --- |
| `app/CreditNote.php` | Campos del plan y de la solicitud. |
| `app/Services/Sales/PartialCreditSettlement.php` | Saldos, plan de devolución y egreso en sesión propia. |
| `app/Http/Controllers/PuntoVentaController.php` | Modal, preparación durable, consulta y aplicación única; validación de lotes/ítems. |
| `app/Http/Controllers/SalePartialPaymentController.php` | Deuda neta, límites y protección de abonos. |
| `app/Http/Controllers/Traits/NubefactTrait.php` | Referencia estable, moneda y datos fiscales de la nota parcial. |
| `app/Services/Sales/UserProfitReport.php` | Devoluciones aplicadas en los reportes que utilizan este servicio. |
| `public/js/puntoVenta/list-operations.js` | Vista previa, canal, subtipo y estado de procesamiento. |
| `resources/views/puntoVenta/_operations.blade.php` | Nuevos campos dentro del modal actual. |
| `database/migrations/2026_11_25_180000_add_partial_credit_settlement.php` | Columnas nuevas e índice único por venta/solicitud. |

## Evidencia y pendientes de validación

82 comprobaciones aisladas con modelos y controlador reales sobre SQLite en memoria y proveedor HTTP sustituido; PHP 7.3 y JavaScript sin errores de sintaxis; modal Blade compilado y renderizado con y sin permiso de gestionar abonos. Se verificaron deuda primero, varias notas, centavos, sesiones ajenas/cerradas, otra moneda/empresa/local, serializados, reintentos, timeout, aceptación posterior, rollback conjunto y abonos posteriores.

Falta la comprobación visual y de emisión/consulta con sesión real en el ERP de pruebas y la ejecución simultánea sobre MySQL. No se usó la base del ERP ni una conexión Nubefact real para estas pruebas privadas. Las notas de crédito totales y los cierres anteriores del auditor no se declaran revalidados por este paquete.
