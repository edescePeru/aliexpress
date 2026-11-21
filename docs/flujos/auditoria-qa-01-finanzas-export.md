# QA-01 — Fechas y lectura de exportación financiera

19/11/2026. Ganancias diarias revisado: documento instalado idéntico y suite aprobada nuevamente. No había cambios de aplicación que instalar en ese paquete.

Hallazgo en exportInvoices: acceso directo a $_GET start/end; si faltaba un extremo se exportaban todos los registros; no había validación común del rango y se repetía la construcción de filas. Proveedor con referencia no resoluble podía causar error al acceder a business_name.

Se prepara FinanceInvoiceExportRead y reemplazo de un método. Validación nullable|required_with|date_format, rango invertido rechazado, normalización de blancos. Ventana por date_entry inclusive al inicio/exclusiva al siguiente día del final. Sin rango mantiene TOTALES. Misma base de lectura que financeEntries: tenant/company actuales, finance=true y borrado lógico del modelo. No restringe por branch ni cambia tratamiento de anuladas; no interpreta TOTALES como exclusión de anulaciones. Importes siguen atributos Entry y monedas originales.

Proveedor debe corresponder al tenant; categoría al tenant y empresa. Ausencia/incompatibilidad produce No tiene, sin reparación histórica automática. No se cambia la validación de todas las relaciones de details ni el listado completo. Se conserva la auditoría de descarga y la ruta con list_invoice, sin inventar permiso nuevo.

Prueba servicio propuesto sobre Eloquent/modelos reales y SQLite privado: doce filas, extremos horarios, contexto ajeno, finance=false, borrado lógico y fecha fuera excluidos; monedas originales, orden estable, subtotal/impuesto/total de Entry; ausencias; sin rango; rangos parciales, invertidos, inválidos y parámetro array; referencias ajenas y contexto de empresa ausente. Fixture no ejecuta HTTP, Audit, vistas Blade ni archivo XLSX. No hay escrituras en ERP.

Estado QA-01: parcialmente atendido; cambio preparado pendiente de instalación. Pendientes descarga real con roles/contextos, comportamiento visual de validación y comparación completa listado/Excel; rendimiento/relaciones históricas y pruebas de integración de las familias previas. No se declara certificado el reporte completo ni reconciliados los datos históricos.
