# Flujo de nota de crédito total

## Alcance y decisión del negocio

Desde `/dashboard/listado/ventas`, emitir o consultar una NC total de una venta con factura o boleta válida. La reversión de dinero y stock es inmediata por decisión del negocio, aunque la respuesta electrónica quede pendiente, incierta o rechazada. Esa condición permanece visible y requiere seguimiento. La NC parcial mantiene su aplicación al ser aceptada.

Antes, la emisión total usaba el flujo antiguo dentro de una transacción y algunos cálculos no conservaban las presentaciones guardadas. Ahora se guarda una operación e intento antes de comunicarse con el proveedor, reutilizando la reversión segura ya implementada para Anular.

## Secuencia

1. Validar permiso efectivo `annul_puntoVenta`, acceso al listado, autor/permiso de sucursal y contexto tenant/empresa/local. Se conservan CSRF y las autorizaciones del servidor.
2. Preparar el comprobante a partir de la venta guardada: importe fiscal completo, moneda, impuestos y presentación. La serie sale de la configuración de NC correspondiente a factura o boleta; el número lo asigna el proveedor. No se usa el catálogo actual para reconstruir importes.
3. Mostrar comprobante original, importe total de la NC, deuda que se cancela, dinero que se devuelve y canal. Se recupera la acción para comprobantes válidos que el backend ya admitía, sin depender únicamente del indicador antiguo de fuera de plazo.
4. Bloquear la venta y guardar la solicitud, NC, detalles, reversión local e intento en una transacción. Se valida el canal autorizado, sesión propia abierta, empresa/local y moneda. Si falla la preparación, no hay efectos parciales ni envío.
5. La deuda se cancela y solo se devuelve lo efectivamente cobrado. Ejemplo: venta140, abono40, deuda100: NC140, egreso40 y deuda cancelada100. Un diferido pendiente se conserva cancelado sin egreso. Un diferido regularizado devuelve su bruto una sola vez; el abono neto no cuenta como otro pago y la comisión mantiene su efecto.
6. Conservar las salidas originales y registrar el reingreso físico y su evidencia de Kardex con el costo original. Si Anular ya aplicó la reversión, no mover nuevamente dinero ni stock y conservar el origen original de esa entrada.
7. Enviar fuera de la transacción con una referencia estable. Guardar la respuesta del intento antes de actualizar el estado final. Validar identidad, serie/número y campos de contraste que devuelva el proveedor. Una respuesta guardada puede recuperarse sin emitir otra vez.
8. Consultar la misma NC. No repetir generación ante doble clic, concurrencia o incertidumbre. Sin número confirmado se requiere conciliación manual con el proveedor; no hay consulta por número supuesto. Cambiar de cuenta emisora bloquea el seguimiento; rotar su token conservando URL y RUC no cambia la identidad.
9. Descargar archivos después de conservar el resultado fiscal. Un PDF fallido no convierte una aceptación en un fallo de emisión ni provoca otra NC. Los archivos se aceptan desde HTTPS Nubefact autorizado, sin redirecciones y comprobando su contenido.

## Historial e interfaz

Los abonos revertidos siguen visibles, sin acciones para borrarlos. El resumen financiero de la venta y sus expresiones SQL reflejan deuda y saldo operativo cero cuando existe evidencia de reversión completa. No se considera conciliado un histórico solo por tener una marca antigua.

El Kardex usa el lector compartido y diferencia `credit_note_total_return` de la devolución por Anular. Se valida que la NC enlazada pertenezca a la misma venta y contexto. Se impiden pagos, despachos o aplicación posterior de una NC parcial sobre una venta completamente revertida.

El modal reutiliza el componente de Anular ya instalado, con importe de la NC, resumen y botón bloqueado mientras procesa. El listado muestra el estado de la NC total y permite su consulta sin confundirla con una baja. No se modifican CSS, estructura global, sidebar ni dashboards.

## Persistencia

La migración `2026_11_26_190000_create_total_credit_note_operations.php` crea `sale_total_credit_operations` y `sale_total_credit_attempts`, con claves únicas por venta, NC, solicitud y referencia. Añade `credit_note_id` a `sale_annulment_operations`. Se reutiliza `sale_annulment_stock_returns` y no se borran las salidas originales.

Las operaciones guardan contexto, solicitante, referencia, huella de cuenta emisora, solicitud fiscal, evidencia financiera, estado y diagnóstico. Los intentos conservan solicitudes y respuestas. No se migran ni reasignan documentos históricos automáticamente. Los registros antiguos sin evidencia suficiente se detienen para conciliación.

## Backend Sync Notes

Archivos completos del controlador de ventas, controlador de abonos, guard del listado, servicio de liquidación, lector de inventario y rutas; nuevo servicio `TotalCreditNoteService`; JavaScript de operaciones, lectura y Kardex. Conservar dependencias instaladas de Anular, `ExistingSaleInvoice` y devoluciones parciales. La consulta parcial mantiene su contrato existente. No se retiraron helpers legacy fuera de este flujo.

Instalar antes de probar; no ejecutar una migración global ni SQL de cambio de estados. Los archivos base instalados se contrastaron por SHA256 al preparar la entrega. Si cambian después, integrar las diferencias antes de reemplazarlos.

## Estado de validación

Implementación y pruebas privadas completadas: PHP7.3, JavaScript, flujos simulados, regresión de parcial/Anular/Kardex y concurrencia en MySQL privado. Dos solicitudes simultáneas producen una sola NC, envío, egreso y entrada de stock. Se verificaron cantidades decimales y rollback antes del envío ante precisión insuficiente.

Pendiente: instalación, navegación con sesión real, aceptación de una NC total en la conexión de pruebas y comparación PDF/XML/CDR por el usuario. La prueba anterior de venta123 valida Anular y su Kardex, no sustituye esta prueba fiscal. Después de esa comprobación se puede iniciar el análisis de migración de Menaje.
