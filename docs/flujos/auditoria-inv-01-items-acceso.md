# INV-01 — Acceso de ItemController

19/11/2026. Paquete Kardex aplicado: controlador, servicio y documentación idénticos; catorce comprobaciones aisladas aprobadas.

Nuevo servicio LegacyItemReadAccess impone contexto operativo y permisos existentes en seis acciones de lectura antiguas. getJsonItemsEntry fija tenant/empresa y el permiso de su tipo real antes del eager loading. No se elimina ninguna ruta, tabla ni dato.

Los consumidores JS existen en retacería, solicitudes/salidas, traslado antiguo y listados de entrada. No confundir falta de uso en el flujo moderno con ausencia de exposición.

Pendiente siguiente: scopes de empresa/almacén/ubicación en consultas Item y relaciones hijas; estados históricos; StockItemController/selectores; integridad de inventory_movements_view; QA HTTP/MySQL con roles. Esta entrega no certifica esa pertenencia. INV-01 parcial.

Verificación ejecutada: sintaxis del controlador y servicio; pruebas aisladas SQLite con contexto real de MaterialInventoryScope/MaterialParameters y actor simulado. Se comprobaron permisos por acción, rechazo desde cada método del controlador antes de leer, tipos de entrada, tenant/empresa ajenos, entrada borrada, membresía desactivada, plataforma y falta de contexto. También pasaron las catorce comprobaciones previas del Kardex. No se ejecutó navegación HTTP ni la lectura autorizada completa de relaciones hijas.

