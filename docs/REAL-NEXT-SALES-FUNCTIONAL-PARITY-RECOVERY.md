# REAL NEXT — SALES FUNCTIONAL PARITY RECOVERY

## R1.1 — Sales Action Menu Visual Parity (2026-11-24)

**ROOT CAUSE: ausencia de markup, no fallo de recursos, sanitización ni CSS.** En `ffe0974^` los botones legacy tenían iconos Font Awesome (print, list-ol, trash-alt, sync-alt, file-invoice-dollar, file-invoice, file-pdf/code/archive; pagos plus y eliminación trash). La homologación `ffe0974` mantuvo `fa-eye next-row-action-item-icon` únicamente en Ver detalle, pero creó los enlaces documentales sin `<i>`. `b52c3cd` y `744d28b` conservan esa omisión. El adaptador R1 recuperó acciones operativas con etiquetas escapadas, pero también sin markup de iconos. No era un efecto de escapeHtml: el icono simplemente no se construía.

**Patrón reutilizado: REUSE DIRECTLY.** `material.indexV2` usa `<i class="… next-row-action-item-icon" aria-hidden="true"></i><span>Etiqueta</span>`. Cotizaciones (`quoteSale/general.js`, renderer local `add`) usa el mismo slot; Clientes (`customer/index.js`) también conserva Font Awesome dentro de cada dropdown-item, aunque sin el slot especializado. No se encontró un helper JS global compartido entre estas tres superficies: el contrato reutilizable es markup + CSS. `venti-next.css` ya define flex/align-items:center, gap compartido, slot fijo1rem, font-size0.8125rem, hover/focus y wrapping. R1.1 reutiliza esas reglas y la librería existente; no modifica CSS ni incorpora componentes nuevos.

### Matriz acción / icono

Todos los iconos decorativos tienen aria-hidden=true y mantienen etiqueta textual. Las acciones destructivas mantienen text-danger, también en su icono. No se cambió disponibilidad, permiso ni hook.

| Acción / variante | Font Awesome existente |
|---|---|
| Ver detalle | fa-eye (conservado) |
| Ver Ticket / comprobante imprimible | fa-print |
| Ver PDF original, anulación, NC total, NC parcial | fa-file-pdf |
| Ver XML anulación / NC total | fa-file-code |
| Ver CDR anulación / NC total | fa-file-archive |
| Generar comprobante | fa-file-invoice |
| Anular venta | fa-trash-alt |
| Pagos parciales / historial o historial sin permiso mutador | fa-money-bill-wave |
| Agregar pago dentro del modal | fa-plus (ya existente, conservado) |
| Eliminar pago dentro del modal | fa-trash-alt; espaciado Bootstrap mr-1 |
| Marcar despacho pendiente / despachado | fa-truck |
| Generar NC total | fa-file-invoice-dollar |
| Generar NC parcial | fa-file-invoice |
| Consultar anulación / NC | fa-search |
| Recuperar archivos Nubefact | fa-sync-alt |
| Descartar error de comprobante | fa-ban |
| Diagnóstico SUNAT / rechazo NC | fa-exclamation-circle |
| Solución del error SUNAT | fa-info-circle |

### QA y evidencia antes/después

Chrome headless aislado, sin controlar navegador/escritorio del usuario. Render Blade real de R1 y snapshots de lectura de las ventas122/119/118; no nueva consulta de negocio necesaria para esta corrección de markup. Estados restantes y documentos se identifican como simulados. Toda red interceptada; ningún documento abierto, ninguna mutación ni llamada Nubefact/SUNAT. No se certifica una nueva sesión HTTP autenticada mediante este harness.

- Antes/después: comparación exacta de etiquetas y atributos de todas las acciones de las tres ventas PASS; no se perdió ninguna capacidad de R1.
- 13 tipos de acción del adaptador + 8 variantes documentales + detalle = 22 tipos de menú con icono; 0 sin icono. Alta/eliminación dentro del modal también tienen icono, sin moverlas al menú ni cambiar su flujo.
- 320/375/390/576/768/992/1200/1440: menús de122/119/118 PASS, slot16px, eje de texto uniforme, glyph cargado, Escape cierra, sin overflow global. Hover/focus reutilizan reglas existentes intactas. Diagnósticos/NC pendientes/descarte/documentos adicionales: render simulado PASS.
- Capturas inspeccionadas: [Antes122](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r11-before-122.png), [Después122](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r11-after-122.png), [Después119 móvil](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r11-after-119-mobile.png), [Error SUNAT simulado](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r11-after-errors-simulated.png).
- Harness/resultados: `r11-qa.cjs` y `r11-results.json` en la misma carpeta de evidencia. Incluye snapshots JS antes de R1.1 para comparación reproducible.

Archivos intervenidos exclusivamente en R1.1: `public/js/puntoVenta/list-readonly.js`, `public/js/puntoVenta/list-operations.js` y este documento. Markup del botón eliminar pago incorporado en su renderer, sin modificar handler. Permisos, guards, condiciones, payloads, endpoints, backend y CSS: UNCHANGED. No modificaciones de POS/Store Web/Caja Admin. Sin commit. Los cambios R0/R1 previos permanecen en working tree.

Checks: sintaxis de ambos JS PASS; suite de regresión R1 9/9 PASS; `git diff --check` PASS (incluido diff --no-index de archivos nuevos). FUNCTIONAL REGRESSIONS: 0 detectadas. La certificación de mutaciones reales sigue PENDING como en R1.

Fecha: 2026-11-24. HEAD de partida `744d28b`, rama `feature/venti-next-frontend`. Cambios locales sin commit.

## Resultado y límites

R0 actualizado en AGENTS.md. R1 recupera las **13/13 capacidades solicitadas**: 11 General y 2 Error SUNAT. Recupera además dos diagnósticos históricos alcanzables, FR-077/078. Conserva detalle, Ticket y enlaces documentales protegidos. No se ejecutaron mutaciones financieras reales ni llamadas a Nubefact/SUNAT.

**REQUESTED RECOVERY IMPLEMENTED — FULL FUNCTIONAL PARITY NOT CERTIFIED.** Reintentar comprobante y Facturador/update-invoice-data siguen como investigaciones de alcanzabilidad; no se inventa su contrato. QA de operaciones reales pendiente. No avanzar a Caja Admin hasta revisión del propietario.

Baseline original General: 17 capacidades de la auditoría inicial; 6 permanecían y 11 se recuperan. Se identificaron dos capacidades adicionales (diagnóstico NC rechazada y solución SUNAT), por lo que el mínimo histórico General conocido pasa a 19. Se conservan también los tres enlaces protegidos NC total PDF/XML/CDR añadidos posteriormente. No equivale a 22 botones por venta: permisos, estado y disponibilidad documental gobiernan cada fila. Error SUNAT recupera sus dos capacidades separadamente; no duplicarlas en el conteo General.

## Evidencia histórica y arquitectura

- `ffe0974^`: baseline operativo, templates, modales y handlers. `ffe0974da2c7a080d3b0dd6c52e2384a9ba38412`: pérdida General. `b52c3cd`: sustitución Error SUNAT. `ec5f6dd`: autorización/contexto DEV preservados.
- `list-readonly.js` conserva renderer, filtros, paginación, detalle y URL validator. Su nombre histórico no limita el producto.
- `list-operations.js` contiene elegibilidad visual, handlers, confirmaciones y contratos operativos; `_operations.blade.php` contiene los modales Next. No se carga `list.js` completo ni un segundo paginador/prefilter legacy.
- SaleListAccess, SaleListOptions, SaleListGuard, WorkerAccess y backend permanecen intactos. La capacidad efectiva proviene del DTO, nunca de un bypass Owner.
- Cada request operacional lleva CSRF, Accept JSON y X-Sales-Tenant/Company/Branch del contexto renderizado. Métodos POST/DELETE actuales preservados. FreeSale conserva sus endpoints especializados.
- Errores 401/403/404/419/422/backend se muestran como error, no como éxito ni empty state. Peticiones duplicadas de mutación se bloquean mientras están pendientes; lecturas de modales usan secuencia/abort para evitar respuestas obsoletas. Se refresca la página actual tras resultado, sin disparar operaciones automáticamente.

## Matriz individual de recuperación

“Sí” significa accesible con permiso/estado, no ejecución real certificada. Evidencia común: diff de los commits citados, código actual, suite Node y harness de Chrome aislado descritos abajo. Endpoints relativos comienzan en /dashboard. QA documental no abre archivos.

| Funcionalidad | Antes | Después | Permiso / condición de negocio | Endpoint / handler actual | Implementación | QA | Blocker |
|---|---|---|---|---|---|---|---|
| FR-001 Emitir | Sí, condicional | Sí, condicional | `manageDocuments_puntoVenta + generarBoleta_puntoVenta/generarFactura_puntoVenta`. Condición: Venta activa sin documento fiscal válido; tipo permitido por usuario. | `POST /dashboard/sale/generate-invoice; FreeSale: /dashboard/ventas-libres/generate-invoice`. Handler: `data-generar_comprobante → openInvoice / prepareInvoice`. | IMPLEMENTED R1 | SIMULATED WIRING PASS; REAL MUTATION PENDING | Sin blocker demostrado para el control; límites abajo |
| FR-002 Anular | Sí, condicional | Sí, condicional | `annul_puntoVenta`. Condición: can_annul y no pending/waiting/accepted; SaleListGuard/DeferredSaleRefund. | `POST /dashboard/anular/order/{id}; FreeSale: /dashboard/ventas-libres/{id}/anular`. Handler: `data-anular → act → mutate`. | IMPLEMENTED R1 | SIMULATED WIRING PASS; REAL MUTATION PENDING | Sin blocker demostrado para el control; límites abajo |
| FR-003 Registrar abono | Sí, condicional | Sí, condicional | `managePayments_puntoVenta`. Condición: pagos_parciales_venta=s; caja/subtipo contextual; saldo/guard backend. | `POST /dashboard/sale/partial-payment/store`. Handler: `btnGuardarPagoParcial`. | IMPLEMENTED R1 | SIMULATED WIRING PASS; REAL MUTATION PENDING | Sin blocker demostrado para el control; límites abajo |
| FR-004 Eliminar abono | Sí, condicional | Sí, condicional | `managePayments_puntoVenta`. Condición: Pago contextual y movimiento regularizable; guard payment. | `DELETE /dashboard/sale/partial-payment/{id}`. Handler: `data-eliminar_pago_parcial`. | IMPLEMENTED R1 | SIMULATED WIRING PASS; REAL MUTATION PENDING | Sin blocker demostrado para el control; límites abajo |
| FR-005 Despacho | Sí, condicional | Sí, condicional | `updateDispatch_puntoVenta`. Condición: Venta activa; estado pendiente/despachado. | `POST /dashboard/sale/update-dispatch-status`. Handler: `data-cambiar_despacho → act (reemplaza switch visual, mismo payload)`. | IMPLEMENTED R1 | REAL READ + SIMULATED UI PASS | Sin blocker demostrado para el control; límites abajo |
| FR-006 NC total | Sí, condicional | Sí, condicional | `annul_puntoVenta`. Condición: can_generate_credit_note; sin NC pendiente/rechazada. | `POST /dashboard/generar/nota-credito/total/{id}`. Handler: `data-generar_nota_credito → act`. | IMPLEMENTED R1 | SIMULATED WIRING PASS; REAL MUTATION PENDING | Sin blocker demostrado para el control; límites abajo |
| FR-007 NC parcial | Sí, condicional | Sí, condicional | `annul_puntoVenta`. Condición: No FreeSale, doc01/03 válido, activa, sin NC pendiente/rechazada; Items elegibles del backend. | `GET /dashboard/nota-credito/parcial/data/{id}; POST /dashboard/generar/nota-credito/parcial/{id}`. Handler: `data-generar_nota_credito_parcial → openPartial → btn-submit-nc-partial / btn-confirm-nc-partial-items`. | IMPLEMENTED R1 | SIMULATED WIRING PASS; REAL MUTATION PENDING | Sin blocker demostrado para el control; límites abajo |
| FR-008 Historial abonos | Sí, condicional | Sí, condicional | `list_puntoVenta + SaleListGuard read`. Condición: pagos_parciales_venta=s; accesible con saldo0, sin permiso mutador. | `GET /dashboard/sale/partial-payment/{sale_id}`. Handler: `data-pagos_parciales → showPayments / cargarPagosParciales`. | IMPLEMENTED R1 | SIMULATED WIRING PASS; REAL MUTATION PENDING | Sin blocker demostrado para el control; límites abajo |
| FR-009 Consultar anulación | Sí, condicional | Sí, condicional | `annul_puntoVenta`. Condición: pending/waiting_sunat_process. | `POST /dashboard/consultar/anulacion/{id}; FreeSale: /dashboard/ventas-libres/{id}/consultar-anulacion`. Handler: `data-consultar_anulacion → act`. | IMPLEMENTED R1 | SIMULATED WIRING PASS; REAL MUTATION PENDING | Sin blocker demostrado para el control; límites abajo |
| FR-010 Consultar NC | Sí, condicional | Sí, condicional | `annul_puntoVenta`. Condición: has_pending_credit_note. | `POST /dashboard/consultar/nota-credito/{id}`. Handler: `data-consultar_nota_credito → act`. | IMPLEMENTED R1 | SIMULATED WIRING PASS; REAL MUTATION PENDING | Sin blocker demostrado para el control; límites abajo |
| FR-011 Recuperar Nubefact | Sí, condicional | Sí, condicional | `manageDocuments_puntoVenta`. Condición: can_retry_nubefact_files. | `POST /dashboard/ventas/{sale}/recuperar-archivos-nubefact`. Handler: `data-recuperar_archivos_nubefact → act`. | IMPLEMENTED R1 | SIMULATED WIRING PASS; REAL MUTATION PENDING | Sin blocker demostrado para el control; límites abajo |
| FR-012 Descartar error | Sí, condicional | Sí, condicional | `annul_puntoVenta + acceso listErrors_puntoVenta`. Condición: can_discard_error; guard de conciliación backend. | `POST /dashboard/sales/errors/{id}/discard`. Handler: `data-descartar_error_comprobante → discard`. | IMPLEMENTED R1 | SIMULATED WIRING PASS; REAL MUTATION PENDING | Sin blocker demostrado para el control; límites abajo |
| FR-013 Imprimir documento | Sí, condicional | Sí, condicional | `print_puntoVenta`. Condición: print_url del backend validada como ruta protegida del mismo origen. | `GET /dashboard/imprimir/documento/venta/{id} o /dashboard/sales/{id}/documents/{kind}`. Handler: `actions → safeDocument → enlace autorizado`. | IMPLEMENTED R1 | SAFE URL/PERMISSION PASS; DOCUMENT NOT OPENED | Sin blocker demostrado para el control; límites abajo |
| FR-077 Diagnóstico de NC rechazada | Sí, condicional | Sí, condicional | list_puntoVenta + objeto visible. Condición: `has_rejected_credit_note`. | ninguno adicional; diagnóstico del DTO `GET /dashboard/get/data/sales/{page}`. Handler: `data-ver_error_nota_credito → act → notice`. | IMPLEMENTED R1 | STATIC/UNIT PASS; DTO diagnostics | Sin blocker demostrado para el control; límites abajo |
| FR-078 Solución de error SUNAT | Sí, condicional | Sí, condicional | list_puntoVenta + objeto visible. Condición: `sunat_error_is_discarded`. | ninguno adicional; diagnóstico del DTO `GET /dashboard/get/data/sales/{page}`. Handler: `data-ver_solucion_sunat_error → act → notice`. | IMPLEMENTED R1 | STATIC/UNIT PASS; DTO diagnostics | Sin blocker demostrado para el control; límites abajo |

## Capacidades conservadas y adicionales actuales

Todas utilizan `list-readonly.js::actions/safeDocument`; sólo se generan enlaces del mismo origen y de la venta correspondiente. Print requiere `print_puntoVenta`. URL ausente no se reemplaza por un archivo público.

| Capacidad | Antes | Después | Endpoint/handler | Condición / implementación | QA |
|---|---|---|---|---|---|
| Detalle | Sí | Sí | GET /dashboard/sales/{id}/details; data-ver_detalles → detail | list_puntoVenta, padre visible; preservado | Render/código preservados; endpoint no reaudita otra fase |
| Ticket/comprobante original | Sí | Sí | print_url DTO → safeDocument; /dashboard/imprimir/documento/venta/{id} o /dashboard/sales/{id}/documents/{kind} | print + archivo disponible; preservado | URL/permiso inspeccionados, NO abrir |
| PDF anulación | Sí | Sí | annulment_pdf_url_local → safeDocument | print + URL disponible; preservado | NO abrir |
| XML anulación | Sí | Sí | annulment_xml_url_local → safeDocument | print + URL disponible; preservado | NO abrir |
| CDR anulación | Sí | Sí | annulment_cdr_url_local → safeDocument | print + URL disponible; preservado | NO abrir |
| PDF NC parcial | Sí | Sí | partial_credit_note_pdf_url_local → safeDocument | print + URL disponible; preservado | NO abrir |
| PDF NC total | No en conteo original17; sí HEAD | Sí | credit_note_pdf_url_local → safeDocument | print + URL disponible; preservado | NO abrir |
| XML NC total | No en conteo original17; sí HEAD | Sí | credit_note_xml_url_local → safeDocument | print + URL disponible; preservado | NO abrir |
| CDR NC total | No en conteo original17; sí HEAD | Sí | credit_note_cdr_url_local → safeDocument | print + URL disponible; preservado | NO abrir |
| Diagnóstico SUNAT Error | Mensaje DTO/detalle | Sí + acceso directo | get/data/sales/error/{page}; ver_error_sunat → notice | acceso a listado Error; texto escapado | Simulación, sin proveedor |

Toolbar, búsqueda, filtros actor/fechas/documento/estado, tabla, badges, paginación y modal detalle permanecen. Los modales operativos utilizan Bootstrap/Next existentes, select2 con dropdownParent modal y tablas con scroll local. **0 CSS nuevo**.

## Payloads y decisiones preservadas

- Emisión: sale_id, tipo_comprobante, dni/name/email_boleta o ruc/razon_social/direccion_fiscal/email_factura. Tipos según generarBoleta_puntoVenta/generarFactura_puntoVenta. Consulta de identidad manual conserva endpoint customer/decolecta/{document}; no se llamó al proveedor en QA.
- Pagos: sale_id, payment_date, amount, cash_box_id, cash_box_subtype_id. Saldo y elegibilidad definitivos backend. Historial disponible con saldo0 y sin permiso mutador; alta/eliminación requieren managePayments_puntoVenta.
- Despacho: sale_id, dispatch_status; la transición visual conserva pendiente/despachado, backend valida.
- NC parcial: reason_code=07, reason_description=Devolución parcial, items[{sale_detail_id,quantity,selected_item_ids}]. Se conserva cantidad disponible y selección/escaneo de Items; no se modificó inventario real.
- Descarte Error: reason seleccionado + comentario. No modifica por frontend el estado SUNAT.
- Confirmaciones explícitas antes de enviar operaciones. No se abre url_print/pdf_url legacy de una respuesta mutadora: se refresca el DTO y se mantiene navegación documental protegida.

## Dataset real y validación manual

Lectura renovada 2026-11-24: PHP7.3, Laravel real, PDO en START TRANSACTION READ ONLY y rollback final. Auth usuario4 Owner EDESCE sólo en memoria, tenant1/company1/branch1. Permisos efectivos print/dispatch/payments/documents/annul/emit=true. No cambios de cuentas/permisos/sesiones persistidas. Esto prueba lectura del controller/contrato, no un login HTTP ni guard mutador ejecutado.

| Registro real | Evidencia | Control esperado |
|---|---|---|
| VENTA-122 | Sin comprobante, pago completo, no anulada | Emisión/anulación/despacho según capacidades; no historial parcial inventado |
| VENTA-119 | Pagos parciales21=100 y22=40; total140, saldo0 | Historial visible y completo pese a saldo cubierto; backend decide alta/eliminación |
| VENTA-118 | Factura01 aceptada con observaciones, can_generate_credit_note=false | No nueva emisión; no NC total indiscriminada; NC parcial según contrato; recuperación según can_retry_nubefact_files |

Errores SUNAT del contexto consultado: 0 registros. Descarte/diagnósticos/print se verificaron con DTO simulado, nunca creando ventas reales. Estados sintéticos901–903 y999 sólo viven en el interceptor de pruebas.

URLs para revisión del usuario:

- [Ventas](http://127.0.0.1:8080/dashboard/listado/ventas): buscar122,119,118 en contexto EDESCE principal y abrir menú … / modales. No es necesario confirmar ninguna operación para revisar la recuperación.
- [Errores SUNAT](http://127.0.0.1:8080/dashboard/listado/ventas/error): empty state real si sigue sin errores. Las pruebas simuladas no se incorporan al runtime.

## QA aislado y responsive

9 tests Node verifican permisos, elegibilidad, ausencia de doble emisión, NC total/parcial, FreeSale, anuladas, consultas pendientes, historial con saldo0 y salida escapada.

Chrome headless en perfil temporal propio, sin controlar escritorio ni navegador del usuario. El harness renderiza secciones Blade reales con contexto/DTO real copiado, intercepta toda petición y sirve assets locales. No es una sesión HTTP autenticada de producción. Las operaciones son respuestas simuladas explícitas; ninguna request mutadora llega a Laravel ni al proveedor.

12 envíos simulados: alta y eliminación de pago, emisión, anulación, despacho, recuperación, NC parcial con Items, consulta de anulación FreeSale, NC total, consulta NC y dos descartes (uno422). CSRF y tres headers PASS; payload de Items PASS; error422 PASS. 0 errores de página; empty state PASS. Sin permisos mutadores queda historial, sin acciones financieras.

| Ancho | Overflow global | Modal pagos | Emisión / NC |
|---|---|---|---|
| 320 | NONE | Fits | Fits |
| 375 | NONE | Fits | Fits |
| 390 | NONE | Fits | Fits |
| 576 | NONE | Fits | Fits |
| 768 | NONE | Fits | Fits |
| 992 | NONE | Fits | Fits |
| 1200 | NONE | Fits | Fits |
| 1440 | NONE | Fits | Fits |

Evidencia local fuera del repositorio, bajo `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`: r1-real-read.json, r1-read-render.php, r1-browser.cjs, r1-browser-results.json, r1-payments-375.png, r1-payments-1440.png, r1-nc-375.png, r1-nc-1440.png. Capturas inspeccionadas; sin cambios CSS. El shell completo autenticado y operaciones reales no quedan certificados por este harness.

## Investigaciones y decisiones pendientes

| Caso | Evidencia | Impacto / alternativa / responsable |
|---|---|---|
| R1-I01 Reintentar comprobante | 640fb46 añade botón; b52c3cd lo retira. No handler alcanzable encontrado en baseline/HEAD; can_retry_invoice no define payload | No inventar enlace a emisión o recuperación. DEV debe identificar contrato/trigger; Producto decide nueva capacidad si no existió una ejecutable |
| R1-I02 Facturador / update-invoice-data | Modal/handlers y rutas existen, pero sin trigger generado en templates del baseline ffe0974^ | Sin pérdida operativa demostrada. Evidencia de alcanzabilidad o definición DEV antes de conectarlo; no ejecutar reconciliación fiscal por suposición |
| DOC-01 | Ruta protegida no demuestra protección física de public/comprobantes | Backend/Infra mantiene certificación documental pendiente. UI sólo usa URL protegida, nunca fallback público |
| EXT-01 / mutaciones | Proveedor, fiscalidad, caja y stock no ejecutados | QA real autorizada/sandbox pendiente; no ocultar las capacidades por ese alcance de QA |

No blocker backend nuevo demostrado que impida los13 controles requeridos. No se da por cerrado DOC-01/EXT-01 ni se declara paridad global completa. No se aprobó retirar ninguna funcionalidad.

## Backlog y archivos

Registro canónico: REAL-NEXT-FUNCTIONAL-PARITY-RECOVERY-BACKLOG.md. Conserva76 IDs originales/30 superficies,9 con paridad estática parcial y158 pendientes semánticos.13/76 recuperadas;63 pendientes fuera deR1. FR-077/078 amplían el mínimo conocido a78. No se borra la deuda ni se duplican findings DEV.

Archivos de esta fase: AGENTS.md; public/js/puntoVenta/list-readonly.js; public/js/puntoVenta/list-operations.js; resources/views/puntoVenta/list.blade.php; resources/views/puntoVenta/listError.blade.php; resources/views/puntoVenta/_operations.blade.php; tests/Frontend/sales-functional-recovery.test.cjs; este informe; backlog canónico. Los dos informes previos de auditoría/regresión se conservan sin modificaciones.

Backend, rutas, permisos, CSS, POS, Store Web: UNCHANGED. Sin commit/push/merge. Sin cambios reales de negocio.

## Checks finales

- `node --test tests/Frontend/sales-functional-recovery.test.cjs`: 9/9 PASS.
- `node --check public/js/puntoVenta/list-operations.js` y `list-readonly.js`: PASS.
- PHP7.3 `artisan view:cache`: PASS; vistas compiladas correctamente.
- `git diff --check`: PASS. Archivos nuevos verificados adicionalmente con diff --no-index --check contra NUL.
- QA headless: 8 anchos, 12 envíos simulados, 0 errores de página, 0 mutaciones reales.
