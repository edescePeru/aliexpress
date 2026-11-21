# INV-01 — Lecturas físicas e historia de entradas

19/11/2026. Paquete items-acceso verificado aplicado: controlador, servicio y documentación coinciden y sus pruebas aisladas pasan.

LegacyItemReadQuery reúne pertenencia de ítems, almacenes/ubicaciones y sus referencias sin usar relaciones globales. ItemController conserva respuestas y estados, incluyendo exited para retacería y todos los estados históricos en consulta de entrada. Las uniones de jerarquía física validan sus padres y tenant. El detalle comercial se limita a tenant/empresa/entrada; las piezas físicas, además, al local/compartidos.

Historia admite material borrado y vínculo desactivado; operación exige material vigente y vínculo activo cuando hay stock_item. Sin stock_item no se inventa asociación; se comprueba material del tenant y atribución física de la empresa. Referencias inconsistentes se excluyen, sin reparar datos. Un detalle financiero sin material se muestra desde su snapshot y no carga identidades ajenas.

No se cambia fuente de precio ni se altera stock, costos, movimientos o datos históricos. Pendientes StockItemController/selectores, vista SQL real, política de precios legacy y QA HTTP/MySQL. INV-01 parcial.

Verificación ejecutada: sintaxis PHP del controlador y servicio; regresión de permisos/Kardex y pruebas SQLite de las seis acciones autorizadas, sus estados y formatos. Incluye IDs ajenos, ubicación incompatible, origen contradictorio, jerarquía ajena, servicio sin material e historia desactivada/borrada. Se validaron también las consultas en MySQL local: EXPLAIN para operación e historia, y ejecución de consulta de detalles con ID 0 sin registros. Estas comprobaciones SQL sustituyen la autorización solo en el arnés para revisar columnas; no equivalen a roles HTTP reales, conciliación del dataset ni prueba funcional MySQL con datos positivos. Sin modificaciones en la base del ERP.

