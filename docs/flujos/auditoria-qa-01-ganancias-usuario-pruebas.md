# QA-01 — Ganancias por usuario: consulta y hojas

19/11/2026. Verificado GananciaVentasDetalladoExport::columnFormats instalado en K:O y sintaxis PHP correcta. El documento auditoria-qa-01-exportaciones.md instalado coincide con el entregado. La prueba de columnas del paquete anterior pasa; además se revisó directamente el método instalado.

Nueva suite sobre clases instaladas: UserProfitReport, GananciasReporteSheet y GananciasResumenSheet, con Container/Validator/Eloquent reales y SQLite privado en memoria, sin bootstrap del ERP. Las relaciones materiales y productos disponen de tablas vacías en la fixture; no se prueban aquí sus nombres ni asociaciones históricas.

Resultados aprobados: doce ventas filtradas frente a una primera página de diez; mismos IDs y orden en la hoja REPORTE; RESUMEN con cantidad 24, venta neta 1080 y utilidad 840; exclusión de tenant/empresa/local diferentes, USD, anulaciones, reversión y fechas fuera de rango; límites inicio del día y fin del último día incluidos; usuario sin ventas; costo nulo, costo cero y uso de costo unitario histórico cuando falta el total; rangos inválidos/incompletos/invertidos rechazados; falta de local no amplía la consulta.

No fue necesario cambiar lógica de aplicación para estos casos. El contraste usa la consulta y los cálculos compartidos por el listado; no ejecuta el controlador HTTP del listado ni prueba las demás columnas de sus respuestas. Las hojas se verifican antes de serializar XLSX. No equivale a validar Excel abierto, permisos por HTTP, membresías de contexto, concurrencia, ni todas las relaciones de los datos históricos.

Estado QA-01: parcialmente atendido. Próximos controles: autorización efectiva y descarga XLSX por ruta; exportación detallada completa; familias Daily Profit y Finance, con filtros, importes, monedas y costos nulos. Ninguna evidencia aquí cierra DATA-01.
