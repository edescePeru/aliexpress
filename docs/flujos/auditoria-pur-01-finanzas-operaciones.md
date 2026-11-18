# PUR-01 — Operaciones y concurrencia

17/11/2026: controlador, FinancePurchaseInput y documentación anterior coinciden con la entrega; PHP lint correcto y pruebas aisladas de validación repetidas con éxito.

Nuevo paquete: validación de edición individual, transacciones y bloqueo de cabecera para detalles y eliminación/restauración, revalidación de cabecera dentro del bloqueo y bloqueo de entrada en regularización con rechazo de asociación existente. Código definitivo basado en ID insertado.

Conservar como parcial hasta QA MySQL/HTTP y revisión de restauración de detalles eliminados individualmente. El guardado actual conserva esa política legacy; no interpretar la transacción como una corrección de la política. No se ejecutaron migraciones ni cambios de datos del ERP durante la preparación.

Validación local de la entrega: PHP lint correcto. Prueba SQLite en memoria aprobada para actualización de total/IGV, eliminación de detalle y orden, restauración y rollback ante fallo simulado de auditoría. No certifica bloqueos concurrentes MySQL ni rutas HTTP.
