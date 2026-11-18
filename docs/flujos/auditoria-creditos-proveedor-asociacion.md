# Créditos — Asociación única por entrada

Revisión local: los siete archivos del paquete de interfaz coinciden; PHP lint y matriz Node aprobados. Se repitieron las pruebas anteriores de fechas y marcas.

Nueva entrega: bloqueo de entrada al asociar, respuesta idempotente con crédito existente y restricción única tenant/empresa/entry_id. Migración con diagnóstico de duplicados previo, sin backfill ni eliminación de historial. Protección de entradas anuladas y validación de proveedor/moneda. Bloqueo conservador ante crédito de orden normal aún sin entrada, sin adivinar su asociación.

Estado preparado para instalación; QA HTTP/MySQL pendiente. No cerrar PUR-01: créditos por orden sin entry_id, política de sobrepagos, precisión monetaria, adjuntos y conciliación siguen separados.

Verificación: PHP lint aprobado. SQLite en memoria confirma reutilización del crédito al reintentar, una fila por entrada, rechazo de duplicado por índice, origen ajeno/anulado, moneda inválida y crédito de orden aún sin entrada. Rechazos sin transacciones abiertas. Concurrencia MySQL/HTTP y escritores de otros flujos pendientes.
