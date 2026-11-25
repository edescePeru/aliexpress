# Texto para el auditor — únicamente este paquete

**L — CASH-02 / CASH-03 / CASH-04:** Se implementaron las tres reglas de negocio aprobadas. La moneda de una caja queda bloqueada cuando tiene sesiones, movimientos o saldo; se protege también el cambio desde su cuenta bancaria vinculada. La modalidad inmediato/diferido no puede cambiar cuando el subtipo ya tiene cobros registrados. La desactivación se rechaza mientras existan pagos pendientes de regularizar en cualquier local de la empresa, aunque su sesión esté cerrada.

Se mantienen el aislamiento tenant/empresa y los permisos existentes. Las validaciones y la actualización se ejecutan transaccionalmente con bloqueos; una moneda bancaria nueva solo se sincroniza a cajas vinculadas sin uso. Omitir flags en una edición conserva sus valores y los valores explícitos inválidos se rechazan antes de normalizar. Se conserva el historial, sin modificar saldos ni convertir automáticamente monedas desconocidas. La lectura de subtipo de pagos parciales se alinea con el bloqueo del POS y la regularización.

**Validación:** Pasaron pruebas privadas con controladores y modelos reales, router con permisos efectivos, contextos negativos, historia abierta/cerrada, pendientes/regularizados/cancelados y fallo inducido con reversión completa. Pasaron sintaxis PHP y JavaScript. No se utilizaron bases del negocio ni proveedores externos.

**Estado:** preparado y probado de forma aislada; pendiente de instalación, validación HTTP/UI real y certificación de concurrencia MySQL. No se declara cerrado el ciclo financiero completo ni se reparan atribuciones históricas incompletas mediante este paquete.
