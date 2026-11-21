# QA-01 — Middleware de permisos de exportaciones

20/11/2026. Exportación financiera revisada: servicio y documento instalados idénticos; método exportInvoices aplicado con aliases equivalentes; prueba del servicio aprobada. Sin cambios adicionales de aplicación en este paso.

Se cotejaron las declaraciones de tres rutas en routes/web.php con su contrato efectivo: ganancias usuario exige consulta y export_gananciaDiaria; detallado exige consulta y exportDetail_gananciaDiaria; Finanzas exige list_invoice. Este último permiso habilita ambas capacidades según el contrato existente; no se declara separación consulta/exportación ni se inventa un nuevo permiso.

Suite aislada con modelos User reales, tablas de permisos/roles privadas SQLite, Spatie PermissionRegistrar, Gate y PermissionMiddleware. Se probaron permisos directos completos, permisos completos por rol, solo consulta de ganancias, solo exportación de ganancias, usuario sin permisos e invitado. Se ejecutó la composición de middlewares de permiso declarados, con rechazo 403 en casos no autorizados. No se modificaron grants del ERP ni caché persistente.

La prueba usa un guard de identidad privado para seleccionar el usuario; no prueba autenticación real, cookies, router completo, middlewares externos de contexto/membresía, controlador, Audit ni descarga XLSX. No certifica respuesta final HTTP del stack completo para invitados. La comprobación de ruta lee su declaración fuente y falla si los permisos divergen; no certifica caché de rutas del despliegue.

Estado QA-01: evidencia ampliada, parcialmente atendido. Quedan pruebas de integración navegador/HTTP con roles y contextos reales, generación/apertura de XLSX y comparación final de exportación financiera con filtros del listado. Mantener pendiente el cierre completo hasta obtener esa evidencia; estas fixtures no sustituyen DATA-01 ni certifican producción.
