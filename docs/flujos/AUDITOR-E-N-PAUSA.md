# Texto para el auditor — solo este paquete

**E — Cotizaciones industriales antiguas:** Por decisión funcional se pausa el módulo, conservando código y datos históricos. Se ocultan sus enlaces y se bloquean sus acciones en el servidor, incluidos edición, planos, reportes y descargas por controlador. El GET de edición ya no ejecuta su lógica de escritura porque no alcanza la acción. No se declara corregido el ownership ni el flujo interno para una futura reactivación.

Se conserva la consulta compartida de contactos utilizada por Cotizaciones de venta, con sus permisos de crear/editar y controles de cliente, tenant y contexto. Tener únicamente permisos industriales no habilita esa consulta. Cotizaciones de venta y sus consumidores modernos no se pausan.

**N — Solicitudes antiguas de salida:** Por decisión funcional se pausa su flujo operativo y sus reportes propios mediante ocultación y bloqueo en el servidor. Se conservan código y registros. Se mantienen separadas las funciones simples/por área y los traslados modernos entre almacenes. Transfer legacy conserva sus rutas comentadas; no se reactiva ni se modifica el módulo moderno.

**Validación:** Pasaron las pruebas privadas de bloqueo GET/POST, cobertura de rutas inventariadas, ausencia de SQL antes del bloqueo en el router aislado, conservación de métodos y regresión de consultas de Cotizaciones de venta. Pasaron sintaxis y compilación de layouts.

**Estado:** preparado y probado de forma aislada, pendiente de instalación y validación HTTP/UI con roles reales. Los defectos internos quedan contenidos por la pausa, no corregidos para reactivación. La protección de archivos históricos servidos directamente por rutas públicas sigue siendo parte independiente del bloque documental P.
