# PUR-01 — Órdenes financieras: permisos de rutas

Revisión: 17/11/2026.

La exportación de entradas instalada coincide con el método entregado. Se verificaron middleware y documentación y PHP lint de EntryController.php y routes/web.php. Queda QA funcional de aislamiento.

Se detectaron cuatro rutas de datos de OrderPurchaseFinanceController sin permiso específico: general, eliminadas, regularizadas y perdidas. La impresión activa usaba list_orderPurchaseNormal. Se prepara una corrección de cinco declaraciones para alinearlas con sus pantallas y con la familia financiera.

El aislamiento no se considera resuelto: OrderPurchaseFinance y OrderPurchaseFinanceDetail carecen de scope contextual en la copia revisada. También deben revisarse referencias de proveedor, aprobador, cotización, plazo, entradas y créditos; lecturas por ID, detalles, PDF, restauraciones, cambios de estado y escrituras. La atribución histórica requiere evidencia de pertenencia y confirmar la estructura real. El SQL de este paquete solo obtiene columnas y cantidades.

Estado: permisos preparados, pendientes de instalación y QA. PUR-01 parcial; pendiente el aislamiento integral de órdenes financieras y restantes consumidores legacy. Esta revisión no certifica la seguridad de los endpoints financieros completos.
