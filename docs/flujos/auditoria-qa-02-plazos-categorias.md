# QA-02 — Plazos y eliminación de categorías

20/11/2026. Verificado auditoria-qa-01-validacion-final.md instalado idéntico. Ese paquete no requería cambios de aplicación. QA-01 sigue pendiente de validación integrada real según su lista única; no se vuelve a fragmentar sin defecto reproducido.

Inicio QA-02 con fixtures privadas adaptadas para usar código instalado, no solo archivos candidatos de paquetes anteriores. No se modificó código de aplicación en este paso.

## Plazos de pago

Suite previa adaptada: modelos PaymentDeadline, servicios de referencias/guard de compras, Requests y PaymentDeadlineController instalados. Esquema y ejercicios de migración/backfill ocurren únicamente en SQLite privado; no demuestran que deba ejecutarse backfill sobre el servidor. Se excluyó la sección de cotizaciones del paquete antiguo para delimitar alcance.

Casos aprobados: filtrado por contexto, protección ante escritura fuera de empresa y traslado de contexto; plazos inactivos no asignables a documento nuevo y conservación en documento existente; reactivación; validación de campos/contexto/ID; controlador crea desde contexto servidor, actualiza plazo sin uso, rechaza cambios financieros de plazo utilizado sin guardar parcialmente, desactiva sin borrar ni perder referencia y rechaza ID ajeno.

Los Requests se validan mediante Validator y se inyectan al controlador; no se recorre FormRequest/HTTP completo. Actor privado y permisos de ruta no quedan certificados por esta prueba de controlador. SQLite no certifica concurrencia/bloqueos MySQL ni comportamiento visual.

## Categorías

Se ejecutó suite instalada de eliminación segura con 62 comprobaciones. Incluye selección individual/múltiple, contexto, raíces eliminadas/ausentes, referencias/dependencias, middleware de permiso de eliminación y rollback de árboles ante fallo simulado en un subtipo. La identidad del middleware usa actor privado; no es login real. No incluye toda creación/edición, categorías financieras ni todos los catálogos.

## Estado

Pruebas aisladas aprobadas sin escritura en ERP. QA-02 parcialmente atendido; pendientes integración HTTP/UI y MySQL para estos grupos y ampliación a categorías de facturas, impuestos/bancos/maestros y settings/uploads/ConfigUserWeb/catálogos de material. No declarar completado QA-02 ni convertir ausencia de fallo en certificación de todas las rutas.
