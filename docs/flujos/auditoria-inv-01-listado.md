# INV-01 — Listado general con campos explícitos

19/11/2026. Niveles aplicado completo: controlador, servicio y documentación coinciden y regresiones aisladas pasan.

StockItemListRead conserva permiso, snapshot de contexto, catálogo de empresa, búsqueda, paginación y cantidades. Resuelve únicamente referencias permitidas; DTO explícito evita serializar Material y stock accessors. Variante de otro material queda null; atributos de otro tenant no se incluyen. Los totales local/general se calculan sobre niveles autorizados y se mantienen separados.

No cambia existencias, reservas, costos, parámetros, estados ni escrituras. El contrato se limita a lo consumido por la pantalla y paginación; consumidores personalizados deben revisar la retirada de campos incidentales del modelo.

Pendientes: fuentes reales de inventory_movements_view, historia y precios legacy, QA HTTP/MySQL con roles/datasets completos. INV-01 parcial.

Verificación ejecutada: sintaxis de controlador/servicio, regresiones anteriores y pruebas SQLite de búsqueda, paginación/orden, permisos, snapshot, relaciones inválidas, totales local/compartido y disponible negativo. El controlador conserva el envoltorio de paginación. Lectura MySQL local con autorización simulada devolvió 10 filas de muestra y serializó correctamente el DTO; sin escrituras. Pendientes QA HTTP con roles reales, navegación y conciliación histórica.

