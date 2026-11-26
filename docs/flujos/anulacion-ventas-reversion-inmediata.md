# Anulación de ventas: reversión inmediata y trazabilidad

Fecha: 2026-11-26. Estado de implementación: IMPLEMENTED en el paquete; instalación y prueba visual/HTTP real pendientes.

## Decisión del negocio vigente

Se mantiene la reversión operativa INMEDIATA del stock y el dinero. No se espera la aceptación electrónica. Una respuesta pendiente, incierta o rechazada no deshace automáticamente esa reversión. Esta política fue confirmada por el owner el 2026-11-26 y NO se considera un hallazgo pendiente.

La situación electrónica se muestra por separado: pending / waiting_sunat_process / processing / unknown / rejected / accepted. Solo accepted marca la anulación fiscal definitiva. Si es rechazada se conserva la reversión y se indica revisar el caso. No se genera otra baja automáticamente.

La reversión se confirma en una transacción local antes de la llamada HTTP. Esto permite conservar el reclamo y su referencia, aun si se corta la comunicación, y aplica la política inmediata también en resultados inciertos. Si falla una validación local, la configuración o el reingreso/devolución, la transacción revierte y no se contacta al proveedor.

## Alcance exacto

- Listado operativo `/dashboard/listado/ventas`, botones Anular venta y Consultar anulación.
- POST `/dashboard/anular/order/{order}` y POST `/dashboard/consultar/anulacion/{order}` mantienen URL, método, permiso y contexto.
- NUEVO GET `/dashboard/sale/{id}/annulment-preview`: prepara el resumen y canales autorizados, sin mutaciones.
- Se conserva el menú, filtros, paginación, detalle, ticket y documentos existentes.
- Se preservan los módulos legacy pausados y los cierres anteriores del auditor.
- Las notas parciales mantienen su criterio de aplicación al aceptar, sus servicios, campos y rutas; se probó su regresión.
- La acción separada Generar nota de crédito total conserva su implementación. La rama fuera de plazo sigue indicando que se requiere una NC y no realiza una nueva baja ni una devolución en este botón. No se sustituye el emisor de NC total en este paquete.

## Resumen previo y dinero

Al abrir Anular se muestra deuda que se cancela y dinero que se devuelve. Si hay devolución, el operador elige caja/canal y subtipo cuando corresponda. Debe existir una única sesión propia abierta en esa caja, en la misma empresa/local y moneda. No se exige usar el mismo canal de los cobros originales. No se modifica el turno original, aunque siga abierto o pertenezca a otro usuario.

Ejemplo: venta 140, abonos efectivos 40 → deuda cancelada 100, egreso 40. Varios cobros se consolidan en un solo egreso vinculado a la operación. El vuelto se descuenta una sola vez. Se conservan los movimientos originales y los abonos, que pasan a inactivos como antes, con evidencia del egreso y los movimientos de origen guardada en la operación.

Cobro diferido pendiente sin efecto en saldo: se marca cancelado, no se elimina, no produce egreso. Cobro regularizado: se valida el abono vinculado y se devuelve el bruto al cliente; el abono neto no se cuenta como otro pago. La comisión conserva su efecto económico. Los cobros sin caja/sesión/contexto o abonos que no coinciden requieren conciliación; no se completan sus datos automáticamente.

Cada egreso conserva tenant_id, company_id, branch_id, cash_box_id, cash_register_id, user_id, subtipo, sale_id, importe y descripción con operación. El registro de operación conserva refund_movement_id y financial_evidence (IDs de cobros, importes, pendientes), evitando vínculos ambiguos cuando hubo pagos mixtos. Se aumenta el total de egresos de la sesión propia; no se reescriben las ventas de turnos cerrados.

El resumen genera financial_token. Bajo el bloqueo de venta se recalcula y compara antes de aplicar. Cambios en abonos, cobros o vuelto obligan a abrir nuevamente la confirmación. Si solo se cancela deuda, no se exige sesión de devolución.

Después de una reversión no se permiten nuevos cobros, eliminación de abonos ni otra devolución parcial sobre esa misma venta. La consulta y la recuperación de documentación siguen disponibles; una NC total necesaria para regularizar el documento fiscal conserva su acceso existente.

## Inventario y Kardex

SaleAnnulmentReversal conserva cada OutputDetail y valida la procedencia con el mismo validador histórico utilizado por el diario de devoluciones parciales: venta/detalle/salida/lote/producto, cabecera de salida, empresa/local, almacén/ubicación, cantidad y costo guardado. No se crea una NC ficticia en la base: solo se reutiliza el validador puro con relaciones de contexto en memoria.

Se agrega una fila IN en sale_annulment_stock_returns por salida, identificada de forma única por output_detail_id. Guarda cantidad, costo unitario/total original, moneda histórica cuando existe, origen, usuario y fecha. Se devuelve el lote, se sincroniza InventoryLevel y se valida la precisión realmente almacenada. Un redondeo no representable provoca rollback de toda la operación. Los ítems físicos se validan y vuelven a entered. La cotización queda canceled/requote según corresponda y se conserva la limpieza de sus reservas legacy con ámbito validado.

La consulta compartida InventoryTimelineQuery agrega estas entradas junto con entradas, salidas originales, traslados y devoluciones NC. Kardex muestra «Anulación · Venta #…». Los lectores de costos usan la misma cronología y costo original; no se usa el precio de venta ni el costo actual del catálogo. La salida OUT ya no se borra.

No se reconstruyen masivamente salidas borradas por anulaciones antiguas. Si la venta ya tiene internal_reversal_status=reversed, consultar no devuelve dinero ni productos otra vez. Una solicitud histórica pendiente sin reversión registrada puede aplicar la operación local validada al confirmar su consulta; no genera otra solicitud de baja histórica.

## Envío, consulta y recuperación

sale_annulment_operations conserva una operación por venta, referencia estable de 20 caracteres, estado, actor, destino, evidencia financiera y reversión. sale_annulment_attempts conserva cada generación/consulta con petición, respuesta, estado HTTP y fechas. No se almacenan tokens en estas peticiones.

Se reclama y aplica la operación local bajo bloqueo de la venta. El HTTP se hace después del commit, con timeout y sin retry automático. Dos solicitudes simultáneas no generan dos operaciones, egresos, entradas ni solicitudes de baja.

Si se pierde comunicación, se conserva unknown y la referencia. Repetir Anular no genera otra baja. Consultar usa consultar_anulacion con la identidad del comprobante. La transición de la boleta en espera genera una sola vez; las posteriores acciones consultan. Una solicitud en processing no se consulta mientras sigue dentro del intervalo de seguridad de 90 segundos. Si quedó abandonada, después del intervalo se permite consultar, nunca regenerar.

Se guarda la respuesta en el intento antes de actualizar el estado final de la venta. Si falla esa actualización, la consulta recupera localmente la última respuesta íntegra guardada, sin otro HTTP. No se toma una respuesta antigua cuando existe un intento posterior todavía activo.

Los PDF/XML/CDR se recuperan después de guardar el resultado, con HTTPS/host Nubefact autorizado, sin redirecciones, validación de contenido y reemplazo mediante archivo temporal. Una descarga fallida solo informa que hay archivos pendientes. La consulta/revisión de una anulación aceptada permite recuperar sus archivos desde la respuesta guardada sin volver a generar la baja. No se pierde un archivo válido por una respuesta inválida.

## Inventario funcional antes y después

| Capacidad | Antes | Después | Implementación / QA |
|---|---|---|---|
| Anular venta sin comprobante | Reversión interna | Se mantiene inmediata; abonos/destino y stock con evidencia | IMPLEMENTED; privado PASS; HTTP real pendiente |
| Anular boleta/factura | Reversión inmediata, solicitud dentro de transacción | Misma política inmediata, reclamo persistido y HTTP fuera de transacción | IMPLEMENTED; privado PASS |
| Abonos inmediatos | Podían quedar sin devolver al desactivarlos | Devuelve lo cobrado, cancela deuda, conserva origen y egreso | IMPLEMENTED; privado PASS |
| Sesión de devolución | Podía debitar la original cerrada o de otro usuario | Canal elegido y sesión propia abierta, ámbito completo | IMPLEMENTED; privado PASS |
| Pagos mixtos / vuelto | Ramas separadas, riesgo de omisión | Un plan con fuentes y vuelto, un egreso | IMPLEMENTED; privado PASS |
| Diferidos | Pendientes/cobros regularizados diferenciados parcialmente | Conserva cancelación pendiente y prueba del abono; bruto sin duplicar neto | IMPLEMENTED; privado PASS |
| Reintento / consulta | UUID nuevo y rollback del pending ante timeout | Referencia estable, intentos, unknown y consulta sin regeneración | IMPLEMENTED; concurrencia MySQL PASS |
| Kardex/costos | Se eliminaba OutputDetail | OUT preservado + IN de anulación con costo original | IMPLEMENTED; lector compartido y regresión PASS |
| Modal/progreso | Confirmación genérica | Resumen, selector de canal, «Procesando…», bloqueo y recuperación de estados previos | IMPLEMENTED; render/JS PASS; interacción visual pendiente |
| Permisos/contexto | SaleListGuard | Se conservan; el servicio nuevo valida el destino bajo bloqueo | ACTIVE — PRESERVE |
| Boleta/factura desde venta / NC parcial | Flujos recién validados | Se conservan, mismos modales/selectores y funciones | ACTIVE — PRESERVE; regresión privada PASS |
| NC total separada / legacy | Fuera del cambio de botón | No se reescriben sus emisores | ACTIVE — PRESERVE / legacy según alcance previo |

No se agrega CSS ni se cambia shell/sidebar. Se reutilizan next-aux-modal / next-aux-modal-form y estilos existentes; el laboratorio visual indicado en AGENTS.md no estaba disponible en esta instalación.

## Contratos y seguridad

Se conserva annul_puntoVenta y list_puntoVenta; acceso por autor o viewBranch_puntoVenta, tenant/empresa/local, CSRF y cabeceras X-Sales-Tenant/Company/Branch. No hay acceso sin contexto desde Platform Admin.

POST añade financial_token, refund_cash_box_id y refund_subtype_id cuando corresponde. Un cliente antiguo sin resumen no puede devolver dinero arbitrariamente: recibe validación y debe abrir el modal nuevo. Consultas de una reversión ya aplicada no requieren elegir otro canal.

GET preview devuelve código, moneda, importes en centavos, token y canales propios autorizados. No devuelve credenciales ni payloads del proveedor. Si hace falta NC, la confirmación solo deja esa indicación sin aplicar nuevos efectos.

Los errores esperados muestran mensajes de negocio. Los inesperados devuelven referencia de diagnóstico y se registran con clase de excepción/IDs, sin exponer rutas de archivos, stack, tokens o respuesta HTTP cruda. Las transacciones locales se gestionan mediante DB::transaction y no quedan abiertas por returns anticipados.

## Migración e instalación

Una migración nueva crea tres tablas: sale_annulment_operations, sale_annulment_attempts y sale_annulment_stock_returns. No actualiza saldos, ventas, movimientos ni anulaciones históricos. Es indispensable instalarla antes de usar el flujo/Kardex del paquete. No borrar operaciones o intentos para habilitar reenvíos.

## Validación y límites

PHP 7.3.33, sintaxis de archivos y rutas; Node, sintaxis y reglas del menú; render real de Blade; integración privada de servicio/controlador, errores, permisos, aislamiento, cantidades, costo, vuelto, abonos, diferidos y recuperación. MySQL 8 privado: integración, precisión DECIMAL y dos procesos simultáneos. Regresión previa de NC/Kardex más comprobación de la entrada nueva en el lector real compartido.

No se escribió la base ERP ni se anuló una venta real. Pendiente: copiar archivos, ejecutar la migración y probar el modal/progreso, un caso interno y un caso electrónico autorizado en el entorno de pruebas. No se declara aceptación visual/HTTP real sin esa prueba.

## Backend Sync Notes

[RESOLVED]
Point: política de reversión inmediata.
Final contract: se mantiene por decisión explícita del owner, aunque el resultado electrónico esté pendiente/incierto/rechazado. No es un bug abierto.
Date / commit: 2026-11-26, paquete sin commit.

[RESOLVED]
Point: abonos, sesión propia y evidencia financiera.
File / endpoint: SaleAnnulmentReversal, SaleAnnulmentService, Anular/Consultar, modalAnularVenta.
Final contract: resumen/token, canal elegido, solo cobros efectivos, deuda cancelada, vuelto y diferidos separados, egreso con ámbito completo y sin modificar sesiones cerradas.
Date / commit: 2026-11-26, paquete sin commit.

[RESOLVED]
Point: reenvíos inciertos, stock y transacciones.
Final contract: operación/referencia/intentos persistidos, HTTP fuera de transacción, consulta y recuperación local; OUT preservado e IN de anulación con costo original; errores seguros. No se rehabilitan módulos legacy.
Date / commit: 2026-11-26, paquete sin commit.

[OPEN]
Point: instalación y aceptación visual/HTTP real del paquete.
File / endpoint: listado operativo, modal de anulación y consultas.
Impact: las pruebas privadas no sustituyen la prueba en la copia del ERP del usuario.
What backend needs to define or correct: no definición de negocio adicional pendiente para los cinco cambios autorizados; instalar y ejecutar QA de pruebas. Las salidas históricas eliminadas requieren evidencia individual si se desean reconstruir.
Frontend can continue: Yes.
