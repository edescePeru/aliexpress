# INV-01 — Consultas de InventoryCostService

19/11/2026. Vista anterior aplicada: archivos SQL/migración/docs coinciden y SHOW CREATE VIEW contiene nuevas igualdades de pertenencia. Pruebas SQL aisladas pasan.

InventoryCostService exige contexto de empresa autorizado, identidad de materiales/productos y frontera tenant/empresa/almacén/ubicación. Conserva promedio de empresa (todos sus locales), cálculo anterior y corte de fechas. No agrega permiso UI al servicio interno ni exige catálogo activo durante escrituras de entradas.

ID mixto ajeno/inválido rechaza toda la consulta; no se coacciona a otro ID. Material/producto histórico del tenant puede consultarse sin habilitación comercial, leyendo solo filas de la empresa actual. Sin movimientos devuelve 0; no es prueba de costo histórico reconciliado.

Pendientes: homologación de traslados en costos vs Kardex, cantidades de detalles multilote, snapshots de moneda/valorización, flujos automáticos sin actor/contexto y QA HTTP/transaccional. INV-01 parcial.

Verificación ejecutada: sintaxis PHP, regresión Kardex y pruebas SQLite de tenant/empresa/ubicación, distintos locales propios, fechas, IDs inválidos/mixtos, contexto/membresía y fórmulas existentes. Se comprobó explícitamente la diferencia previa: material usa promedio para salidas, producto usa unit_cost registrado cuando existe; no se homologa aquí. En MySQL local ambas consultas se ejecutaron y devolvieron valores finitos con autorización simulada, sin escrituras. Se confirmó registrada la migración previa de la vista. Pendientes QA transaccional/HTTP con roles y política monetaria.

