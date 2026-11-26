# Boleta y factura desde una venta registrada

Fecha: 2026-11-25. Superficie: `/dashboard/listado/ventas` y su modal «Generar comprobante».
Estado de implementación: IMPLEMENTED en este paquete; pendiente de copiar al ERP y validación visual/HTTP con el usuario. No equivale a emisión real validada.

## Diagnóstico y resultado

El POS moderno ya utilizaba SaleEmissionService, pero el botón del listado utilizaba el envío antiguo del trait. Este último recalculaba IGV al 18%, no conservaba correctamente otras afectaciones/monedas y convertía cualquier excepción (también transporte o descarga) en un supuesto rechazo, permitiendo un nuevo intento.

El listado usa ahora el mismo servicio de emisión persistida, con preparación del cliente bajo el bloqueo de la venta. La información fiscal se toma de los detalles y totales guardados, sin consultar precios/impuestos actuales del catálogo. La emisión corresponde al total ORIGINAL de la venta, incluso cuando tiene pagos pendientes. No registra abonos, egresos, stock, utilidad ni otra venta. El cliente del comprobante se conserva en los campos de la venta; no se cambia el maestro de clientes.

La serie se toma de la configuración Nubefact de la empresa; el número se deja vacío para que lo asigne el proveedor, como en el POS moderno. La serie se toma de la configuración Nubefact de la empresa; el número se deja vacío para que lo asigne el proveedor, como en el POS moderno. Fecha de emisión: hoy, como hacía este botón antes del cambio. `date_sale` se conserva. Monedas: PEN/USD. Las presentaciones mantienen cantidad de paquetes, unidades por paquete y descripción guardadas. Exoneración/inafectación se conservan; no se convierten a gravadas. Se comprueba concordancia de líneas, bases e IGV con la cabecera antes del HTTP. No se infiere el impuesto de ventas antiguas sin snapshot fiscal ni se modifica su precio. Los descuentos del POS actual ya están deshabilitados en PosSaleService; los históricos con descuento requieren revisar su distribución fiscal y no se reconstruyen arbitrariamente.

## Flujo

1. Operador con permiso abre «Generar comprobante» en una venta habilitada sin identidad fiscal ni intentos inciertos.
2. El modal muestra código, importe ORIGINAL y moneda; conserva pestañas Boleta/Factura por permiso y consulta DNI/RUC con ingreso manual cuando la consulta falla.
3. Boleta mantiene DNI de 8 dígitos y nombre; factura exige RUC de 11 dígitos, razón social y dirección. El correo sigue opcional. Estos controles no certifican la existencia del documento en SUNAT.
4. Confirmación: botón bloqueado y texto «Generando…». Se revalida contexto, acceso, permisos, venta activa, anulación/notas existentes y estado bajo bloqueo. Se guarda una referencia estable antes del envío.
5. Preparación local inválida: HTTP 422, sin contacto con Nubefact. Se puede corregir la causa y volver a intentar con la misma referencia. No se ofrece una nota de crédito por el solo hecho de haber elegido un tipo de comprobante en un intento fallido.
6. Respuesta con identidad válida: se guarda serie/número y se informa el estado SUNAT obtenido. «Registrado en Nubefact» no significa necesariamente «Aceptado por SUNAT». Faltantes/fallos de PDF/XML/CDR producen aviso y se recuperan por la acción existente, sin generar otro documento.
7. Timeout, respuesta dudosa o error remoto: se conserva `unknown`/`processing`, no se etiqueta falsamente como rechazo y no hay reenvío automático. «Revisar emisión de comprobante» muestra estado y referencia locales. Si existe una respuesta íntegra `persistence_pending`, el operador puede recuperar sus datos sin HTTP. Si no existe esa evidencia, debe contrastar el resultado con Nubefact antes de cualquier conciliación; esta pantalla no inventa identidad ni envía otra vez.
8. Repetir el POST o enviar dos solicitudes simultáneas no crea otro intento/comprobante una vez reclamada la emisión. Se conserva el historial de intentos.

## Inventario funcional antes/después

| Capacidad | Antes | Después | Estado / QA |
|---|---|---|---|
| Generar Boleta/Factura | POST antiguo desde modal | Mismo botón, modal y POST; servicio compartido con bloqueo y referencia persistida | ACTIVE — PRESERVE; integración privada PASS; QA real pendiente |
| Elegir tipo y cliente | Pestañas por permiso, DNI/RUC, nombre/dirección, correo opcional | Mismos campos y consulta; validación estricta de longitud/formato, consultas obsoletas invalidadas | ACTIVE — PRESERVE |
| Importe, moneda, presentación, impuesto | Recalculaba 18%/PEN desde datos antiguos | Snapshot guardado, total original y comprobación de concordancia | IMPLEMENTED; pruebas privadas PASS |
| Progreso y mensaje | Deshabilitaba sin explicar avance | «Generando…», bloqueo y resultado real; mensaje de revisión si incierto | IMPLEMENTED; menú/Blade PASS, interacción visual pendiente |
| Revisar intento incierto | Sin acción específica en este listado | GET local y recuperación POST exclusivamente con evidencia íntegra | ACTIVE — FRONTEND RECOVERY REQUIRED resuelto en paquete; HTTP real pendiente |
| Archivos y ticket | Lectores guardados / recuperar archivos | Se conservan los lectores y acción existente; nunca nueva emisión por archivo fallido | ACTIVE — PRESERVE |
| Pagos, despacho, anulación, NC | Acciones activas según permiso | Se mantienen. Corrección adicional descrita debajo para despacho | ACTIVE — PRESERVE; regresión NC/Kardex PASS |
| Filtros, paginación y exportaciones | Lectores del listado | Sin cambios | ACTIVE — PRESERVE |
| Ventas libres y módulos retirados | Pausados según decisión del owner | No se reactivan | LEGACY — INTENTIONALLY HIDDEN |

Corrección adicional localizada: updateDispatchStatus contenía comprobaciones de un pago parcial (`amount`) insertadas en un paquete anterior. Se retiran de ese método y se coloca su bloqueo dentro de transacción. Cambiar despacho vuelve a requerir únicamente venta/estado y sus permisos/contexto; no exige registrar un pago. No se altera el cálculo de notas ni el Kardex.

## Contrato, rutas y permisos

- POST `/dashboard/sale/generate-invoice`: mismo sale_id/tipo_comprobante y campos de cliente. Retorna message, sale_id, emission_status y sunat_status cuando corresponde; HTTP 200 registrado, 202 incierto/en proceso, 422 validación/preparación fallida. El adaptador activo no abre URLs devueltas por mutaciones; recarga enlaces guardados del DTO.
- GET `/dashboard/sale/{sale}/emission-status`: NUEVO, solo estado local, referencia y metadatos mínimos; nunca credenciales/payload completo. No hace HTTP a Nubefact ni mutaciones.
- POST `/dashboard/sale/{sale}/recover-emission`: NUEVO, attempt_id. Comprueba evidencia/contexto/identidad; no emite ni consulta Nubefact.
- DTO de listados normal y errores: agrega invoice_info (habilitación, revisión, identidad, estado, importe original y moneda). Se conservan los campos anteriores.
- Se mantiene list_puntoVenta, manageDocuments_puntoVenta, generarBoleta_puntoVenta / generarFactura_puntoVenta. Revisión/recuperación exige manageDocuments_puntoVenta más acceso al listado/venta; no otorga permiso de emisión.
- Se preservan tenant/empresa/local, autor o viewBranch_puntoVenta, CSRF y cabeceras X-Sales-Tenant/Company/Branch. El permiso de recuperación no salta estos controles. Otra empresa/local/tenant no puede emitir ni recuperar esa venta.
- SaleEmissionService conserva la firma existente de dos argumentos; tercer callback opcional para preparar el cliente de este flujo bajo el mismo bloqueo. SaleEmissionRecoveryService conserva por defecto la restricción a cotizaciones; la extensión para ventas existentes exige local actual y excluye venta libre.
- Scripts activos comprobados: list.blade.php y listError.blade.php cargan list-operations.js. Los antiguos list.js/listError.js no se reactivan.

## Interfaz y patrón visual

REUSE WITH CONTRACT ADAPTATION: se reutiliza el modal operativo `.next-aux-modal` / `.next-aux-modal-form`, Bootstrap y tipografía existentes. No se añade CSS, no se modifica el shell/sidebar ni otras pantallas. La ruta del laboratorio visual indicada en AGENTS.md no estaba disponible; se conserva el patrón ya integrado en esta vista. Se conservan selectores, foco y cierres existentes; se agregan gc_summary_sale/gc_summary_amount y la acción revisar_emision. Los valores se muestran como texto, no HTML del proveedor.

## Validación realizada

- PHP 7.3.33: sintaxis de controladores, middleware, servicios y rutas.
- Integración privada con SQLite y proveedor simulado: emisión, boleta/factura, total original con pagos parciales, packs, PEN/USD, gravada/exonerada/inafecta/mixta, errores de datos/cliente, permisos, aislamiento, doble POST, incertidumbre, archivos fallidos y recuperación local.
- MySQL 8 privado, separado de la base ERP: misma integración y dos procesos simultáneos; una llamada simulada, un intento, un comprobante, sin caja ni stock.
- Render real del Blade del paquete con permiso de factura permitido/denegado; controles únicos de comprobante y NC conservados.
- Menú JavaScript ejecutado aisladamente: estados habilitado, preparación fallida, desconocido, confirmado, errores y permisos.
- Regresión privada previa de NC, deuda/devolución y Kardex ejecutada con los controladores del paquete: 128 comprobaciones PASS.
- No se hizo una emisión real, no se escribió la base ERP, no se ejecutó una migración real y no se modificó Apache/PHP/hosts/puertos/configuración.

Pendiente tras instalación: validación visual del modal y progreso, flujo HTTP con sesión real y una boleta/factura de pruebas autorizada en la conexión de pruebas. Los resultados privados no sustituyen esa validación. No se reabren cierres J/P ni K del auditor.

## Backend Sync Notes

[RESOLVED]
Point: emisión desde listado distinta del POS moderno y reintentos inciertos.
File / endpoint: SalePartialPaymentController@generateInvoiceFromSale; SaleEmissionService; ExistingSaleInvoice; POST sale/generate-invoice.
Final contract: preparación y reclamo atómicos, snapshot fiscal guardado, referencia estable, un envío y recuperación local solo con evidencia. Sin pagos ni inventario.
Date / commit: 2026-11-25; paquete sin commit.

[RESOLVED]
Point: interfaz activa de emisión y revisión.
File / endpoint: list-operations.js; _operations.blade.php; lectores normal/errores; dos endpoints locales nuevos.
Final contract: mismos permisos, contexto y CSRF; botón con progreso y mensajes diferenciados; nuevo DTO y revisión local. No CSS nuevo.
Date / commit: 2026-11-25; paquete sin commit.

[OPEN]
Point: aceptación visual/HTTP y emisión real de pruebas después de instalar.
File / endpoint: /dashboard/listado/ventas y modal Generar comprobante.
Impact: todavía no validado contra Nubefact real por este paquete.
What backend needs to define or correct: no definición de negocio adicional pendiente; el owner debe instalar y ejecutar la prueba en su entorno de pruebas. Históricos sin evidencia fiscal o envíos inciertos sin respuesta requieren conciliación individual; no se corrigen por SQL genérico.
Frontend can continue: Yes.
