# PUR-01 — Lecturas de órdenes financieras

Revisión local del 17/11/2026: ambos modelos, trait, migración y documentación del paquete anterior coinciden con la entrega. artisan migrate:status indica ejecutada la migración de contexto, lote 175.

Nueva entrega: controlador completo con contexto obligatorio; usuarios por tenant/membresía activa de empresa; cotizaciones por tenant/empresa; maestros por tenant; entrada financiera a regularizar por tenant/empresa; consultas por ID que fallan si no existe registro autorizado. Conserva permisos y cálculos previos.

No declarar cerrado PUR-01: siguiente etapa validar referencias del formulario y detalles, y revisar transacciones y regularización repetida. Las respuestas 422 de capturas legacy se mantienen y no equivalen a un contrato HTTP homogéneo. QA funcional del controlador pendiente.
