# QA-01 — Precisión de líneas y total

19/11/2026. Pagos parciales revisado: método instalado, documento idéntico, sintaxis y suites aprobadas. Se continúa con redondeos.

Se reprodujo en fixture privada una diferencia: tres costos 0.005, redondeados por línea en el export anterior a 0.01, sumaban 0.03; UserProfitReport suma 0.015 y redondea el agregado a 0.02. El usuario eligió conservar precisión guardada y mostrar decimales adicionales, redondeando al final.

Se entregan dos métodos completos en TXT. collection elimina el round por celda de los importes; columnFormats aplica #,##0.00######## a K:O. Mantiene texto de costo faltante, métodos de pagos/presentación, cálculos y filtros. No cambia la política del servicio de reporte ni incorpora una fila total nueva al Excel. Se compara la suma de líneas redondeada al final, no la suma de importes previamente redondeados.

Prueba con la clase completa propuesta sobre fuente instalada y SQLite privado: conserva 0.005 por línea; venta/costo/utilidad agregados coinciden con reporte al redondear al final; formato admite decimales opcionales; costo faltante permanece como texto. También se repite suite de conjunto completo y costos. Sin bootstrap ni escritura en ERP, sin generar XLSX.

Estado: preparado y aprobado en prueba aislada; pendiente instalación, archivo real y control visual. QA-01 continúa parcialmente atendido. Pendientes autorización/descarga por HTTP, rendimiento y casos reales de pagos/presentaciones, Daily Profit y Finance. No se certifica aritmética decimal arbitraria con float ni igualdad contra cabeceras/documentos externos.
