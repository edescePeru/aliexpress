# Anulación total con cobros diferidos confirmados

Estado de entrega. Cambio preparado y probado de forma aislada. Validar su instalación y los flujos completos en la rama activa. El código de esa rama prevalece sobre este documento.

La devolución al cliente se registra por el bruto del cobro. Una venta de S/ 70 que abonó S/ 68 genera un egreso de S/ 70. Si la sesión tenía S/ 68, su saldo queda en −S/ 2. La comisión permanece en el registro de regularización.

La reversión busca el abono del cobro dentro del contexto activo. El egreso se vincula a ese abono. Si su sesión está abierta, se usa esa sesión. Si ya fue arqueada, se usa una única sesión propia abierta en la misma caja y local. El egreso incrementa el total de gastos y reduce el saldo. Los arqueos previos conservan sus cifras.

Puntos de entrada. `reverseSaleInternally` en [PuntoVentaController](../../app/Http/Controllers/PuntoVentaController.php), `reverseFreeSaleFinancially` en [FreeSaleFinancialReversalTrait](../../app/Http/Controllers/Traits/FreeSaleFinancialReversalTrait.php) y [SaleListGuard](../../app/Http/Middleware/SaleListGuard.php). La regla del bruto se concentra en [DeferredSaleRefund](../../app/Services/Sales/DeferredSaleRefund.php).

El alcance es la reversión total de diferidos confirmados. La nota de crédito parcial y el retiro individual de un pago se revisan por separado. Si ya hay una nota de crédito parcial registrada, el servicio pide conciliación antes de devolver el cobro completo. El egreso registra la devolución dentro de Venti360. El negocio gestiona la devolución al cliente por su canal de pago.

Para la prueba web, verificar ticket, venta de cotización con Items, pagos mixtos, abono en sesión arqueada y nota de crédito total. Comprobar el retorno de inventario cuando corresponda y que repetir la anulación no genere otro egreso.
