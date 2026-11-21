# INV-01 — DTO de niveles por producto/material

19/11/2026. Variantes aplicado completo: controlador, servicio y documentación idénticos; pruebas aisladas pasan.

StockItemInventoryRead relee el producto autorizado y limita sus relaciones a material/tenant coherentes. Reutiliza StockItemScreenAccess y MaterialInventoryScope::levels, añade permiso en el propio servicio y mantiene contrato y cálculos de los dos endpoints de niveles. No confía en relaciones precargadas del parámetro de binding.

Por producto: puede consultar estado inactivo si sigue habilitado en empresa. Por material: productos activos habilitados; material sin ningún producto habilitado rechaza 404. Los costos se muestran tal como están registrados y no se reinterpretan.

Sin cambios de stock, escrituras, rutas, políticas de reserva ni movimientos. Pendientes listado general/relaciones de StockItemController, fuentes inventory_movements_view y QA real. INV-01 parcial.

Verificación ejecutada: sintaxis de ambos archivos PHP y regresiones aisladas anteriores. Pruebas SQLite positivas/negativas de los dos contratos: local/compartido, ubicaciones incompatibles, metadatos ajenos, costos, reservados, disponible negativo, productos inactivos, permisos alternativos y relaciones precargadas. Lecturas MySQL locales por producto y material devolvieron un nivel cada una con autorización simulada; sin escrituras. No equivale a QA HTTP con roles reales, conciliación histórica ni prueba de cambios concurrentes.

