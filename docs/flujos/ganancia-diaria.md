# Ganancia Diaria multitenant

Estado de entrega. Probado con SQLite en memoria. Pendiente de aplicar la migración y validar la pantalla y el cron en el entorno del proyecto. El código vigente prevalece sobre este documento.

La fórmula comparte `UserProfitReport` con Ganancia por Usuario. Usa el importe vendido menos descuento registrado y costo histórico. No recalcula IGV. Si falta el costo, la utilidad de ese grupo y del resumen se informa como «Sin costo registrado». El listado usa PEN, igual que el reporte por usuario. Las notas de crédito parciales y los descuentos globales de cotización mantienen las limitaciones del cálculo compartido.

El comando `calculate:ganancia` usa ayer en Lima y acepta `--date=YYYY-MM-DD`. Descubre contextos válidos a partir de ventas o resúmenes de la fecha. Guarda una cabecera por tenant, empresa, local, moneda y día. Cada recálculo reemplaza el detalle dentro de la misma transacción. El índice único y el bloqueo de empresa protegen ejecuciones repetidas. Los productos se agrupan por StockItem y, cuando falta, por material. Las líneas sin producto quedan agrupadas como servicios y venta libre.

La pantalla exige `show_gananciaDiaria` y valida el contexto también cuando abre una cabecera por ID. El total del detalle representa el día completo. Las ventas anuladas o revertidas se excluyen según la consulta compartida. Para ajustar un día tras una anulación posterior, ejecutar el comando con la fecha original de venta.

Código. [Comando](../../app/Console/Commands/CalculateResumenDiarioGanancia.php) · [Resumen](../../app/Services/Sales/DailyProfitReport.php) · [Cálculo compartido](../../app/Services/Sales/UserProfitReport.php) · [Controlador](../../app/Http/Controllers/GananciaDiariaController.php)
