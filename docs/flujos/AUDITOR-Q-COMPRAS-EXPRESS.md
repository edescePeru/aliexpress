# Texto para el auditor — únicamente este paquete

**Q — Compras normales y Compras Express:** Por decisión funcional, Compras Express queda pausada, conservando código y datos históricos. Se ocultaron sus enlaces operativos activos y se bloquearon en el servidor sus 13 acciones específicas, antes de consultas, validaciones o mutaciones. Se mantiene Órdenes por Material y su permiso actual, aunque su nombre contenga Express; los reportes históricos compartidos no se eliminan.

En Compras normales se protegieron las consultas de órdenes perdidas y regularizadas con el permiso de listado. Se corrigió la secuencia para ordenar códigos válidos, excluir duplicados y conservar números históricos ya ocupados, sin renumerar ni modificar órdenes. Regularizadas se restringe al tipo normal y al tenant/empresa actuales.

Se reforzó el contexto autorizado en las lecturas cubiertas, se incorporaron permisos directos en sus listados y se alineó el permiso del selector de edición con `update_orderPurchaseNormal`, ya exigido por el formulario. El enlace por código desde solicitudes requiere listar compras normales y utiliza el detalle normal de solo lectura. La consulta de impresión rechaza padres Express, ajenos o inexistentes y distingue órdenes activas de eliminadas.

**Validación:** Pasaron las pruebas privadas con modelos, permisos y router reales, usando SQLite descartable: bloqueo de las acciones Express sin SQL, permisos directos/de rol, empresa no asignada, casos negativos de tenant/empresa/tipo, secuencia estable y lecturas sin escritura de negocio. Se conserva Audit técnico donde ya existía. Pasaron sintaxis y compilación de layouts.

**Estado:** preparado y probado de forma aislada; pendiente de instalación, revisión HTTP/UI con roles reales y PDF visual. No se certifican mutaciones completas ni se declara cerrada toda la familia Q o la auditoría documental P. Los defectos internos de Express quedan contenidos por la pausa, no se declara corregido su flujo para reactivarlo.
