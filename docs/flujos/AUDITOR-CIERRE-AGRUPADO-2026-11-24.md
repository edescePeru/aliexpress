# Auditoría — Estado consolidado y avance a dashboards

Fecha: 24/11/2026. Documento de referencia: `REAL-NEXT-DEV-PARALLEL-WORK-CURRENT-2026-11-22(1).md` (A–S).

Este documento reúne implementación, decisiones del negocio y evidencia. No cambia todos los puntos a «cerrados por auditor»: ese cierre requiere su revalidación. Tampoco presenta las pruebas de despliegue pendientes como nuevos defectos sin reproducción.

## Resultado del lote

- El código GRE coincide con los hashes de la evidencia MySQL anterior; las cinco suites GRE volvieron a pasar.
- Se reunió una suite de 22 pruebas sobre las clases instaladas, sin usar las copias de aplicación de paquetes anteriores. Pasaron avatar/retiros, compras normales, cotizaciones y pausas legacy, reglas de cajas, política de edición de productos, ciclo de créditos, documentos por familia, extras de Entradas y GRE.
- Se actualizaron dos fixtures de pruebas antiguas, sin modificar la aplicación: el permiso industrial pausado ya no concede el endpoint compartido de contactos; la fixture financiera ahora contiene la membresía y espera 403 para lectura sin membresía. Se mantuvieron las comprobaciones de rechazo.
- La evidencia MySQL GRE se incorpora al mismo paquete: dos procesos/conexiones reales, misma operación sin duplicación, conflicto por contenido y números distintos para operaciones distintas. La instancia privada quedó detenida.
- Este lote no incluye archivos de aplicación para reemplazar, SQL ni migraciones. Se entregan pruebas, evidencia y los documentos faltantes de `docs/flujos/`.

## Matriz A–S

| ID | Tema | Tratamiento y estado |
|---|---|---|
| A | Avatar | Implementado; prueba agrupada de carga/rollback aprobada. Revisión visual del despliegue aparte. |
| B | Materiales: tipos, posiciones y guardado | Tratado en paquetes anteriores; se revalidó la política actual y conservación de campos omitidos. No se reintroducen bloqueos rechazados por negocio. |
| C | Entradas Create/Edit y catálogos | Tratado en el paquete de contexto de materiales/Entradas. Este lote revalida documentos y extras, no ejecuta toda la compra/entrada por HTTP. |
| D | Cotizaciones de venta | Correcciones de capacidades/contexto aplicadas; pruebas agrupadas de catálogo/contactos/conversión aprobadas. Se conserva el módulo vigente. |
| E | Cotización industrial antigua | Pausada por decisión del negocio; acceso bloqueado y datos conservados. Pruebas de pausa y consumidores conservados aprobadas. |
| F | Órdenes de compra financieras: parser | Módulo financiero retirado del acceso operativo por decisión del negocio; datos conservados. No reactivar para probar un flujo descartado. |
| G | Regularización financiera antigua | Mismo retiro del módulo financiero; las compras normales y su reporte usado se conservan. |
| H | Accesos/usuarios legacy | Accesos legacy pausados; Usuarios Web y Platform conservados. Sidebar/contexto por tipo de usuario se termina en los dashboards. |
| I | Mutaciones de compra financiera | Flujo retirado; no se exige certificar altas/ediciones de un módulo que no se usará. Se conserva la protección de créditos vinculados y el histórico. |
| J | GRE operacional | Store/estados/retry/recuperación, router en memoria con autenticación/permisos/CSRF y MySQL concurrente aprobados en sus alcances. Pendiente login/cookies/pila completa del despliegue y cotejo real de documentos de pruebas. |
| K | Créditos de proveedor | Ciclo aislado y lector documental revalidados. Pendiente certificación financiera DECIMAL/concurrencia MySQL y HTTP del despliegue; no se sustituyen por la concurrencia GRE. |
| L | Reglas CASH-02/03/04 | Decisiones aprobadas implementadas: moneda de caja usada y condición diferida tras uso protegidas; desactivación con pendientes controlada. Suite de reglas aprobada. |
| M | Política MAT-01 | Decisión del negocio implementada: permitir cambios con historial, desactivación con existencias y cambios de maestro compartido; conservar valores omitidos. Suite aprobada. Estos permisos de negocio no se registran como bugs pendientes de bloqueo. |
| N | Solicitudes/traslados legacy | Solicitudes antiguas pausadas; transferencia legacy no activa. Datos conservados y traslados modernos preservados. |
| O | Selectores históricos de compra financiera | Queda dentro del módulo retirado; no se prepara otra interfaz para ese flujo. Audit técnico aceptado se conserva. |
| P | Seguridad documental | Ventas, Entradas, cotizaciones, extras y comprobantes de créditos tratados y revalidados; marcadores Apache comprobados por familia en lotes previos. Pendiente revisión HTTP/visual por roles y tipos utilizados, correspondencia GRE y revisión de cualquier escritor/familia residual que se reproduzca. No se declara certificado todo `public`. |
| Q | Compras normales/Express/reportes | Compras normales y reporte por material conservados/corregidos; Compras Express pausadas. Suite de compras aprobada. |
| R | Venta Libre | Pausada sin borrar el código ni los datos, por decisión del negocio; no activar antes de retomarla explícitamente. |
| S | Datos Generales antiguos | Acceso retirado; se usa Configuraciones vigente. No se restablece el inicializador antiguo en GET. |

## Pendientes finitos de certificación

1. **J/P:** probar el flujo en el despliegue con login/cookies/roles; abrir y cotejar PDF/XML/CDR reales de una guía de pruebas contra emisor, serie/número y destinatario locales. No requiere otra emisión si la aceptada existente sirve como evidencia.
2. **K:** ejecutar los casos financieros en MySQL descartable con DECIMAL y concurrencia, además de HTTP autorizado/denegado. Nunca fabricar pagos en la base compartida.
3. **P:** completar la revisión visual/HTTP de las familias documentales utilizadas y conservar los históricos ambiguos para revisión. La limpieza global no está autorizada ni es requisito de este lote.
4. **Dashboards:** resolver la visibilidad de enlaces por tipo de usuario y contexto, conservando las autorizaciones del servidor. Ese es el siguiente trabajo funcional; no queda declarado resuelto aquí.

Las pruebas de cada lote y sus límites están en `RESULTADOS-DETALLE.json`, `EVIDENCIA-GRE-MYSQL.json` y `CODIGO_VERIFICADO.json`. No hubo cambios de aplicación ni nuevas operaciones productivas en este lote.

## Continuación acordada

Podemos avanzar con Dashboard Owner y luego Platform Admin, manteniendo visibles los pendientes de certificación anteriores. No son un motivo para inventar nuevos parches de módulos retirados ni para repetir paquetes ya instalados. Cualquier defecto nuevo reproducido se registra por separado y se corrige con su evidencia.

La migración de Menaje debe tener su propia validación de datos y saldos antes del cambio productivo; esta revisión no la certifica.
