# QA-01 — Ganancias detalladas: prueba de filas e importes

19/11/2026. Verificado documento auditoria-qa-01-ganancias-usuario-pruebas.md instalado idéntico; suite anterior aprobada de nuevo. No había cambios de aplicación que instalar en ese paquete.

Se ejecutó GananciaVentasDetalladoExport real sobre la misma fixture aislada de UserProfitReport, ampliada con segunda línea para una venta. Resultado: 13 líneas de 12 ventas, mismas ventas y orden que GananciasReporteSheet; cabecera solo en primera línea; quince columnas por fila; descripción en J y formatos monetarios instalados en K:O.

Los totales de las líneas coinciden con el conjunto completo del reporte: 25 unidades, venta neta 1100, costo 245 y utilidad 855. Los escenarios previos incluyen otros tenants, empresas, locales, USD, anuladas, revertidas y fechas fuera de período. Se comprobó autor y fecha históricos, N/D sin movimientos de cobro, costo faltante explícito, costo cero distinto de faltante y usuario sin ventas.

No fue necesario modificar código de aplicación para estos casos. La fixture usa Container/Eloquent/Validator reales y SQLite :memory:, sin bootstrap ni escritura en el ERP. Los cálculos se verifican antes de serializar XLSX; no se probó descarga HTTP, políticas de roles, middleware de membresía, apertura de archivo, pagos mixtos, presentaciones ni asociaciones históricas de productos. Los importes de prueba son enteros: no certifican todas las reglas de redondeo con decimales ni discrepancias de cabecera frente a detalles.

Estado QA-01: parcialmente atendido. Siguientes controles: autorización y descarga real por ruta; revisar pagos/presentaciones y redondeos del detallado; continuar Daily Profit y Finance de forma independiente. No crear exportación diaria nueva solo porque el backlog mencione esa familia: verificar primero los consumidores existentes.
