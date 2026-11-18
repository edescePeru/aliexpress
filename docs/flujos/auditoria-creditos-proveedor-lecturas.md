# Créditos — Lecturas sin mutación

Revisión: controlador y documentación de permisos coinciden con el paquete instalado; bloque de rutas verificado y PHP lint correcto.

Entrega: eliminar las escrituras de los dos listados GET, proyectar vencimientos en memoria y compartir la regla con alerta por vencer y comando. Días calendario con signo, zona America/Lima, umbral de empresa. Se conservan estados pagados y no se fabrican vencimientos para fechas ausentes.

El comando persiste dentro de bloqueo/transacción por crédito y notifica cambios de estado. La entrega de notificaciones no tiene garantías de outbox/reintento. Pendientes escritores POST legacy, QA HTTP/MySQL, interfaz, pagos y adjuntos. No declarar PUR-01 cerrado.

Verificación: PHP lint aprobado. Pruebas aisladas de días -1/0/1 y límites del umbral, pagados, sin fecha y consultas de ambos listados aprobadas. Se capturaron las consultas SQL y se comprobó ausencia de escrituras y conservación completa de la fila, incluido updated_at. QA HTTP/MySQL y notificaciones reales pendientes.
