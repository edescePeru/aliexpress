# Resumen financiero de cajas

Cada caja aparece una vez por moneda. El saldo consolidado se lee de CashBox.current_balance y las sesiones abiertas se agrupan por caja, sin filtrar por cajero. Los cobros pendientes se consultan sobre movimientos originales de subtipos diferidos aunque la sesión se haya cerrado. En un filtro por sucursal, los saldos consolidados de cajas compartidas se reservan para la vista de empresa porque su saldo no está desglosado por local.

Los movimientos del período se filtran por created_at y solo incluyen los que afectan saldo. La regularización en la sesión original actualiza el movimiento original, así que en ese caso la actividad se atribuye a la fecha de registro del cobro. Si se creó un abono posterior al cierre, cuenta la fecha del nuevo movimiento. La fecha bancaria real y lotes de liquidación requieren un registro específico. La comisión se muestra como dato del cobro neto. El arqueo conserva sus movimientos originales.

El consolidado y el saldo abierto tienen totales separados. El origen del fondo de apertura todavía carece de trazabilidad. Los movimientos y las sesiones sin sucursal se omiten del desglose operativo. Las cajas inactivas siguen visibles para revisar sus saldos.
