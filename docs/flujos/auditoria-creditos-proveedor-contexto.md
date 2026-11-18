# Auditoría — Contexto de créditos de proveedor

17/11/2026. Diagnóstico directo local: supplier_credits=0, credit_pays=0; créditos sin tenant_id/company_id. OrderService no tiene contexto; Entry, OrderPurchase y OrderPurchaseFinance sí.

Entrega: migración solo para tablas vacías; scope de empresa para créditos; pagos visibles por el crédito padre; eventos de pertenencia y proveedor; controlador contextual; comando de vencimientos por empresa y destinatarios contextuales. Los archivos se entregan completos y la base del ERP no se modifica al preparar el paquete.

No declarar cerrado PUR-01 ni el módulo de créditos. Pendientes permisos, escrituras en GET, pagos y adjuntos, concurrencia e interacción con anulación/eliminación de órdenes. Las referencias a servicios legacy quedan rechazadas al guardar hasta contextualizarlas. La familia de créditos afecta a compras normales y financieras y debe validarse con ambas.

Validación: PHP lint aprobado. Pruebas SQLite en memoria aprobadas para origen propio, rechazo de referencias mezcladas/ajenas, separación por empresa/tenant, pagos positivos, rechazo de pago ajeno/negativo y traslado de pago, ausencia de contexto y recuperación del contexto tras el comando. No se prueba aún el envío real de notificaciones ni concurrencia MySQL. Diagnóstico local de estructura y cantidades realizado sin modificar datos.
