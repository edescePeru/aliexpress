# PUR-01 — Exportación de entradas (17/11/2026)

El paquete anterior del reporte JSON fue encontrado aplicado: métodos, ruta con list_entryPurchase y documentación. EntryController.php y routes/web.php pasan PHP lint. Esto no certifica aún QA HTTP entre empresas.

La exportación exportEntriesAlmacen conservaba seis consultas sin company_id y su ruta no declaraba permiso específico. Esta entrega agrega tenant y empresa explícitos en las seis ramas, rechaza contexto ausente y exige permisos por tipo. Valida tipo y rango; ambas fechas vacías siguen permitiendo todo el historial. No cambia los cálculos ni el diseño del archivo.

Alcance: empresa activa, sin filtro nuevo por sucursal. Conserva date_entry en Excel y created_at en el reporte JSON. Los rangos no son equivalentes cuando las fechas difieren.

PUR-01 permanece parcial: compras financieras y demás consumidores legacy pendientes. Paquete manual sin patch. Antes de declarar cierre, ejecutar la matriz de aislamiento, permisos y exportación indicada en INSTRUCCIONES.md.
