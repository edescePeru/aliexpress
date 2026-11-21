# INV-01 — Variantes de material y permisos efectivos

19/11/2026. Selectores aplicados: servicio y docs idénticos y los dos métodos delegan correctamente; pruebas aisladas pasan.

VariantScreenRead aplica verVariantes_material, contexto autorizado, material tenant vigente y catálogo de empresa. Filtra variantes por StockItem habilitado y material/tenant coherentes; DTO explícito mantiene las columnas de DataTables sin accessors globales de material. Las referencias de talla/color se limitan al tenant.

StockItemController delega página/JSON a este servicio y usa getAllPermissions en index. No se modifican escrituras, stock, inventario ni rutas. Variantes desactivadas pueden consultarse si conservan vínculo habilitado; no implica vendibilidad.

Pendientes INV-01: otras relaciones/listados/niveles de StockItemController, vista SQL y fuentes, QA real. La privacidad/validación de adjuntos por URL no se certifica con este DTO. INV-01 parcial.

Verificación ejecutada: sintaxis del controlador/servicio y regresiones aisladas anteriores; casos positivos/negativos de variantes con empresa/material/atributos incompatibles, variante inactiva, material borrado, falta de contexto/permiso y permisos efectivos del index. Consulta MySQL y respuesta DataTables válidas con autorización simulada; la muestra local devolvió cero variantes, por lo que los casos positivos se comprobaron en SQLite. Sin escrituras en ERP. Pendientes navegación HTTP con roles, datos positivos MySQL y QA visual de tabla/imagen.

