# QA-01 — Etiqueta con pagos parciales

19/11/2026. Pagos mixtos aplicado correctamente: método coincide, documento instalado idéntico y prueba aislada aprobada. Sintaxis de export instalada correcta.

Ampliación preparada de resolveFormaPago: combina movimientos directos con pagos parciales activos de la venta; requiere contexto coincidente en vínculo y movimiento. Movimiento con sale_id NULL puede atribuirse mediante vínculo activo; otro sale_id explícito no se reasigna. Se excluyen parciales inactivos y se deduplica el mismo movimiento por ID antes de resolver canales. Continúan filtros de egresos, hijos de regularización, diferidos cancelados y referencias incompatibles.

Prueba específica del método propuesto con modelos reales y relaciones precargadas: parcial activo sin sale_id, vínculo directo duplicado, anulado aún vinculado directamente, estados contradictorios, empresa ajena, movimiento de otra venta/local y movimiento ausente; casos anteriores de pagos mixtos también aprobados. La prueba no ejecuta consultas de estas relaciones ni certifica HTTP/XLSX. Suite del detallado instalada incluida con tabla privada sale_partial_payments para poder repetirla tras el cambio.

Sin cambios en cobros, saldo o política monetaria. En duplicidad histórica contradictoria, la etiqueta omite el movimiento en lugar de elegir un vínculo activo. Los nombres de canales siguen siendo catálogo actual. No se reconstruyen fechas históricas ni se recalculan cuentas por cobrar.

Estado QA-01: parcialmente atendido, pendiente instalación y pruebas reales con abonos. Redondeos, permisos y descarga real, rendimiento de relaciones, Daily Profit y Finance siguen pendientes. No declarar certificado el Excel completo por estas pruebas.
