# PUR-01 — Validación de referencias financieras

Revisión local del 17/11/2026: controlador y documentación de lecturas coinciden con el paquete; PHP lint correcto. La migración de contexto figura ejecutada, lote 175.

Nueva entrega: servicio FinancePurchaseInput invocado antes de crear, actualizar o regularizar. Controla referencias tenant/empresa y estructura de detalles; conserva únicamente referencias históricas ya asignadas cuando corresponde. Se incluye controlador completo para evitar omitir llamadas al servicio.

Estado: preparado para instalación y QA. No cierra PUR-01; quedan acciones independientes de detalle y garantías transaccionales, además de los consumidores legacy restantes. La base de datos del ERP no fue modificada durante la preparación.

Verificación aislada: pasaron referencias propias, rechazo de referencias de otro tenant/empresa, plazo inactivo en creación, conservación de plazo histórico, JSON inválido, lista vacía, detalle de otra orden y cantidad negativa. SQLite en memoria, sin cargar .env ni conectar al ERP. No sustituye QA HTTP/MySQL.
