# GUIDE-01 — Actualización de consulta

Filtros instalados verificados. Se detectó que consultar sustituía enlaces por null y last_nubefact_response perdía ZIP ante respuestas parciales. Se prepara conservación de campos documentales, validación básica de respuesta y actualización transaccional con comparación de huella del registro.

El cliente remoto se invoca fuera del bloqueo; los datos se aplican solo al contexto/registro vigente. Se mantienen permisos y redacción de documentos. Sin migraciones ni emisiones durante la preparación. Pendientes QA HTTP/MySQL y semántica completa de estados/errores del proveedor. GUIDE-01 parcial.

Validación: PHP lint y SQLite en memoria con proveedor simulado aprobados. Se comprobó conservación de enlaces/ZIP ante respuesta parcial, reemplazo de documentos nuevos, respuesta inválida sin cambios, rechazo de huella desactualizada, conservación de cambio concurrente, fallo de proveedor sin mutación y contexto ajeno rechazado. No se hizo ninguna llamada de red. QA MySQL/HTTP pendiente.
