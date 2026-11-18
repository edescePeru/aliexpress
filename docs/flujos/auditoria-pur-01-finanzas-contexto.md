# PUR-01 — Contexto de órdenes financieras

Evidencia recibida: base aliexpress_multitenant; tablas order_purchase_finances y order_purchase_finance_details sin tenant_id/company_id, ambas con cero registros. No se debe inferir que están tenantizadas a partir de consultas de PaymentDeadline.

Se prepara migración exclusiva para tablas vacías y trait específico en ambos modelos. La orden y cada detalle guardan tenant/empresa, asignados desde el contexto. Se comprueba pertenencia del detalle a la orden y se mantiene el orden legacy de restauración: detalles primero, cabecera después.

Las cinco rutas del paquete de permisos fueron verificadas en el paso anterior. Este paquete no sustituye ni repite esos cambios. PUR-01 continúa parcial hasta revisar selectores, validación, manejo de IDs ajenos y regularización del controlador, además de otros consumidores legacy.
