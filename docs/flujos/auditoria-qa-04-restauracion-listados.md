# QA-04 — Fixtures positivas y multipágina real

20/11/2026. QA-03 revisado: documento instalado idéntico; solicitudes/preparación/despacho-recepción/disponibilidad aprobados otra vez. Concurrencia MySQL no ejecutada; se conserva pendiente sin confundirla con idempotencia secuencial.

Se agrupan suites de clientes/proveedores adaptadas a código instalado y se agrega evidencia positiva de restauración y paginación. No se detectó una corrección nueva de aplicación para estos casos.

## Clientes y contactos

Prueba existente CUSTOMER-03 cargando modelos, servicio y controladores instalados. Crea datos privados activos/eliminados y lotes de borrado; verifica restauración selectiva, contactos previamente borrados conservados, legacy sin lote, contexto/identidad, errores y rollback, controladores directos y cierre de transacciones. Parte de los Requests usa subclasses privadas que devuelven datos validados: no se afirma validación HTTP completa de esos Requests. La migración construye únicamente SQLite :memory:.

## Proveedores y cuentas

Suite de contrato de lectura sobre clases instaladas: permiso separado de cuentas, ausencia de consulta/serialización bancaria sin permiso, referencias incompatibles y rutas internas. Su tramo inicial usa DataTables/Excel simulados; no se afirma archivo XLSX real.

Ampliación privada: proveedores activos y cinco eliminados, proveedor eliminado ajeno; restauración positiva usando reglas/Validator reales de RestoreSupplierRequest; rechazo de ajeno y de proveedor ya activo; fallo en evento restoring conserva borrado. A continuación reemplaza el adaptador de DataTables por Yajra real, manteniendo conexión SQLite privada y respuesta JSON simulada. Cuatro páginas de tamaño diez cubren todos los proveedores activos del tenant sin IDs duplicados ni omitidos; el total coincide con SupplierAccountRead y las cuentas siguen vacías sin permiso.

Las pruebas de permisos usan actor privado, no login real. No se recorre router/middleware completo. La prueba de restauración de proveedor valida reglas pero no ejecuta automáticamente authorize desde el ciclo HTTP. DataTables real no certifica la configuración frontend ni todas las búsquedas/ordenamientos de todos los listados.

## Estado

QA-04 parcialmente atendido: evidencia positiva y multipágina ampliada para estas familias. Pendientes roles/contextos por HTTP/UI, listados/reportes restantes mencionados en backlog (incluidos Finanzas/cotizaciones), concurrencia y dataset de servidor. No se declara cierre global. QA-01, QA-02, QA-03 y DATA-01 mantienen sus pendientes separados; EXT-01 sigue como integración externa no certificada. No enviar documentos a proveedor ni usar credenciales reales para estas fixtures.
