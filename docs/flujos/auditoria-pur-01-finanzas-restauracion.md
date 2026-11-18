# PUR-01 — Restauración selectiva

Revisión local del 17/11/2026: controlador y documentación del paquete operaciones coinciden con lo instalado; PHP lint correcto.

Se incorpora deletion_batch nullable en cabecera y detalle. Al eliminar una orden se identifican únicamente los detalles activos afectados por esa operación. Restaurar usa esa relación y no recupera detalles retirados previamente. La restauración legacy sin identificador se bloquea para revisión; no se realiza backfill por fechas porque no prueba la causa del borrado.

Mantener PUR-01 parcialmente atendido hasta QA HTTP/MySQL y revisión de consumidores restantes y ciclo de créditos de proveedor. El respaldo de la relación mientras la orden permanece eliminada depende de conservar estos marcadores; la migración impide revertirlos si contienen datos.

Pruebas SQLite en memoria aprobadas: separación de retiro individual y eliminación de orden, repetición de ciclos, limpieza de marcadores, bloqueo de restauración legacy sin datos inferidos y rollback ante fallo de auditoría. Archivos PHP validados sintácticamente. No certifica concurrencia MySQL.
