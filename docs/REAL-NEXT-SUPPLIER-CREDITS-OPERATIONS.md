# REAL NEXT — Supplier Credits — Facturas y saldos / Abonos

2026-11-22. **FRONTEND INTEGRATED — MUTATION QA PENDING**. Ruta `/dashboard/faturas/proveedores/pendientes`, `index.invoices.pending`, vista `credit.indexInvoicesPending`. Backend unchanged; CSS nuevo0; sin commit.

## Routes / Permissions

Prefijo `/dashboard`, middleware autenticado existente; permisos también defendidos por controller. No se asume bypass Owner.

| Action | Route | Method | Permission |
|---|---|---|---|
| Vista | faturas/proveedores/pendientes (`index.invoices.pending`) | GET | control_credit |
| Listado | get/invoices/pending | GET | control_credit |
| Historial | get/pays/credit/{credit_id} (`get.pays.credit`) | GET | control_credit OR pay_credit OR nopay_credit |
| Registrar abono | save/pay/credit/{credit_id} | POST | pay_credit |
| Eliminar abono | delete/pay/credit/{credit_pay_id} | POST | nopay_credit |
| Comprobante abono | creditos/abonos/{payment}/comprobante (`supplier.credit.payment.attachment`) | GET | control_credit OR edit_credit OR pay_credit OR nopay_credit |
| Ampliación existente | add/days/credit/{credit_id} | POST | edit_credit |
| Estado manual existente | change/status/credit/{credit_id}/{status} | POST | pay_credit OR nopay_credit + autorización de transición |

Resumen, vencimientos, montos y export existentes conservados, fuera de QA de esta fase. No nueva página create/edit, asociación Entry comentada no reactivada. Allowlist ya contiene index.invoices.pending; sin duplicados ni endpoints POST añadidos.

## Scope

Tenant + company. Controller exige contexto y company perteneciente al tenant. SupplierCredit scoped; CreditPay mediante crédito contextual. No ownership branch añadido. GET historial ajeno/inexistente puede responder pays[] por filtro contextual, no garantiza404; detalle del crédito usa findOrFail. No se abre el endpoint documental para probar autorización.

## Dataset

Lectura MySQL con START TRANSACTION READ ONLY y ROLLBACK, controller/render reales con contexto explícito, no login HTTP E2E.

| Usuario QA | Tenant/company/branch de sesión | Créditos visibles | Historial positivo |
|---|---|---|---|
| Owner EDESCE | 1/1/1 | 0 | PENDING DATASET |
| Owner EDESCE | 1/2/2 | 0 | PENDING DATASET |
| Owner Gamarra | 3/3/4 | 0 | PENDING DATASET |

Los tres tienen control_credit, edit_credit, pay_credit, nopay_credit efectivos. Ninguna fila real para botón de abono; habilitación visual validada con respuesta controlada, no registros DB. ID inexistente99999999:404 de getCreditById en los tres contextos. No IDs reales ajenos disponibles para prueba positiva/negativa HTTP. SupplierCreditReadPathTest, sólo casos scopes/saldo: 2tests/19assertions PASS SQLite privado; IDs completos1/2/3 excluidos entre T1/C1,T1/C2,T3/C3, sin contexto vacío; saldo rechaza objeto ajeno. No se ejecutaron métodos financieros ni tests de mutación.

## Main List

DataTables client-side intacto: búsqueda, orden/paginación y rango de emisión con ambos límites.19 columnas reales: orden, correlativo, proveedor, moneda, condición, monto USD/PEN, deuda USD/PEN, pago, deuda actual, factura, emisión, vencimiento, vence en/estado crédito, estado pago, fecha pago, observación, acciones.18 switches originales conservan IDs/data-column/visibilidad inicial, ahora en Column Visibility Menu de Material V2. Búsqueda y controles DataTables en toolbar compartida, tabla con scroll local.

Importes derecha; texto izquierda; fechas/estado/acciones centro. Estado crédito en badge semántico con texto, reemplazando color de celda dependiente de índice visible. Selector de estado manual DEV permanece intacto; no se reemplaza su contrato por badge inerte. Menú único de fila: Abonos e historial según OR existente; Agregar7días según edit_credit. No acciones nuevas ni botones individuales.

## Payment Modal

Modal existente modalPays, patrón next-aux-modal + next-form-section. Contexto proveedor/documento/saldo/moneda viene de la fila ya cargada, sin consulta adicional. Registrar e historial permanecen juntos; formulario sólo con pay_credit. Sin permiso: explicación de consulta. Campos reales: montoPago, fechaPago, comprobantePago; credito_id oculto. Sin referencia/usuario/observaciones inventados. Acciones compactas y footer Cerrar, scroll interno móvil.

## request_key lifecycle

DEV usa UUIDv4 crypto.getRandomValues, borrador por creditId en sessionStorage con fallback memoria, fingerprint de creditId/importe/fecha/metadatos archivo. Se crea al primer intento, no al abrir modal. request_key sigue en FormData; espejo hidden payment-request-key no editable. CSRF X-CSRF-TOKEN desde meta; multipart contentType:false/processData:false.

**Desviación previa detectada:** el JS borraba el borrador en422, contrario al encargo. Corrección mínima:422 conserva key y marca validación rechazada; permite corregir fingerprint con la misma key, pues validación backend precede persistencia.409/error incierto conservan key y bloqueo de cambio de payload: requieren revisar historial y acción explícita Iniciar otro abono. No se cambia key automáticamente para ocultar conflicto.

Cerrar/reabrir mismo crédito conserva inputs/archivo en DOM y key. Cambiar de crédito conserva importe/fecha en memoria, limpia file input por seguridad; si había intento ambiguo con archivo distinto/faltante, fingerprint bloquea envío distinto. Tras reload la key persiste pero no se restauran bytes del archivo. Esto es límite del contrato existente, no permiso para reiniciar automáticamente.

Éxito/replay limpia borrador, vacía inputs y vuelve a consultar historial. No inserta filas locales ni cierra modal; conserva comportamiento anterior. Saldo contextual proviene del listado y no se recalcula: refresco del saldo de fila tras pago no se añade en esta fase. Nuevo intento tras éxito obtiene nueva key. Iniciar otro abono mantiene confirmación explícita y limpia key/errores visibles.

## Monetary Precision

Valor del input se envía como string, sin nuevo parseFloat ni cálculo financiero. Backend conserva regex/DECIMAL/centavos, locks/transacciones, replay409. No se añade regla contra sobrepago ni se presenta saldo cliente como autoridad. PEN/USD sin conversión; historial usa monto recibido y contexto monetario del crédito. Fecha d/m/Y; fechaPago nullable según backend, label opcional corregido. Inputmask existente preservado.

## Payment History

Mismo GET y payload pays: id, type, monto, fecha, comprobante. Sólo número de fila, importe, fecha y acciones; sin actor/referencia inventados. Carga/error/vacío separados. Request anterior abortado al cambiar de crédito y respuesta tardía descartada. DOM usa textContent para importes/fechas. Sin historial construido desde CashMovement.

## Delete Payment

Menú destructivo Eliminar abono sólo nopay_credit; handler/endpoints/confirmación existentes intactos. No se pulsó confirmación ni ejecutó delete. No equivale a cancelar crédito ni revertir marca manual. Comprobante y eliminación comparten menú único; sin acciones autorizadas muestra raya, no trigger vacío.

## Errors 409/422

409 muestra mensaje backend, conserva modal/input/key.422 muestra resumen y feedback junto a montoPago/fechaPago/comprobantePago con aria-invalid, conserva datos/modal/key. No se convierten403/500 en empty. Historial fallido muestra código HTTP; listado fallido muestra error explícito. Listado vacío real y búsqueda sin coincidencias tienen mensajes separados.

## Documents Boundary

Botones/enlaces existentes a comprobantes protegidos preservados (ruta contextual de SupplierCreditAttachmentController); missing image no renderiza enlace. Ninguna apertura/descarga/upload documental. **DOCUMENT QA PENDING**; no certificación de infraestructura.

## Mutation Boundary

**FINANCIAL MUTATION QA PENDING**. Cero POST de abono/delete/marcas/estados/plazos; cero export/doc requests. QA navegador bloqueó toda mutación/documentos y no disparó ninguna. Para422/409/replay se sustituyó $.ajax en memoria antes de llamar al handler: callbacks controlados, sin solicitud HTTP POST. Esto valida UX/lifecycle, no idempotencia financiera end-to-end ni concurrencia.

## Responsive / Visual Parity

Chrome headless propio, sin escritorio/navegador del usuario. HTML actual Laravel + assets permanentes; listado e historial positivos controlados en navegador (no DB).320/375/390/576/768/992/1200/1440: document.scrollWidth=viewport; modal sin overflow horizontal; menú Columnas dentro de viewport. Capturas320/1440 revisadas. Sin pageerrors. Referencias inspeccionadas directamente: material.indexV2 (menús), Supplier Credits read-only (badges/estados), invoice.create_invoice (secciones/modales).

**VISUAL PARITY** para toolbar, column visibility, row actions, spacing y modal; **VALID REAL CONTRACT DIFFERENCE** para selector de estado DEV y modal combinado registro/historial. Sin nuevos estilos/page-specific ni rediseño de otras vistas Credit.

Evidencias locales: %TEMP%/credit-operations-qa.json, credit-operations-320.png, credit-operations-1440.png, credit-operations-conflict.png, credit-operations-list.png. Fixtures visuales no se incluyen en runtime. Entorno real vacío impide certificar historial/detalle positivos y acciones por estado con datos reales.

## Files / Checks / Status

- resources/views/credit/indexInvoicesPending.blade.php
- public/js/credit/index_invoicesPending.js
- docs/REAL-NEXT-SUPPLIER-CREDITS-OPERATIONS.md

Blade compile PHP7.3 PASS; node --check PASS; read scope tests2/19 PASS; git diff --check PASS. Backend/routes/models/config/permissions/CSS intactos. Cambios previos de My Profile preservados y ajenos a esta fase. Sin commit.

Facturas y saldos FRONTEND INTEGRATED; modal FRONTEND INTEGRATED — MUTATION QA PENDING; historial FRONTEND INTEGRATED; eliminación UI INTEGRATED — MUTATION QA PENDING. Backend UNCHANGED. No cerrar PUR-01 global ni certificar concurrencia/documentos/pagos por QA visual.

## Registered Credits — 2026-11-22

`/dashboard/control/creditos` · route `index.credit.supplier` · view `credit.indexSupplierCredit`.
**FRONTEND INTEGRATED — MUTATION QA PENDING**. No páginas independientes, sin asociación Entry reactivada. Esta sección no reabre QA de Facturas y saldos.

### Permissions Matrix

| Action | Route (prefijo dashboard) | Method | Permission |
|---|---|---|---|
| Vista/listado | control/creditos; get/only/credits/supplier | GET | control_credit |
| Cargar crédito | get/credit/by/id/{creditId} | GET | control_credit OR edit_credit OR pay_credit OR nopay_credit |
| Editar fechas/observaciones | credit/control/update (`credit.control.update`) | POST | edit_credit |
| Ampliar +7 | add/days/credit/{credit_id} | POST | edit_credit |
| Marca pagado | credit/control/paid (`credit.control.paid`) | POST | pay_credit |
| Revertir marca | cancel/pay/credit/{idCredit} | POST | nopay_credit |
| Estado manual | change/status/credit/{credit_id}/{status} | POST | pay_credit OR nopay_credit + permiso según transición |
| Comprobante marca | creditos/{credit}/comprobante (`supplier.credit.paid.attachment`) | GET | control_credit OR edit_credit OR pay_credit OR nopay_credit |

getAllPermissions alimenta visibilidad; conservar gates anteriores de edición/marca cuando no paid_out y reversión cuando paid_out. Acciones nuevas en esta vista sólo conectan endpoints DEV existentes requeridos por el encargo: +7 y estados manuales. Antes no había controles activos aquí para esas dos operaciones. No nuevas reglas/endpoints backend. Scope tenant+company; branch no añadido.

### List / Columns

16 columnas existentes: proveedor, factura, orden, importe PEN/USD, emisión, vencimiento, plazo, días, observación, estado crédito, observación pago, documento factura, fecha pago, documento pago, acciones. No saldo añadido: este endpoint no entrega el saldo de Facturas y saldos. Importes/monedas conservados, sin FX ni cálculo financiero nuevo. DataTables client-side, ordering vacío inicial, búsqueda, paginación y rango de emisión existentes. No column visibility real en Blade; no se inventaron switches por existir un listener legacy sin controles.

Toolbar y badges compartidos con familia Credits; acciones en menú único. Estado del crédito separado del estado manual (título del grupo del menú), sin inferir desde importes. Listado vacío y error HTTP separados; búsqueda sin coincidencias conserva mensaje DataTables. Sin permiso de operaciones: Sin acciones.

### Edit Modal

Mismo modalEdit/formEdit, names/IDs/hidden/CSRF/data-url. next-aux-modal y next-form-section, botón primario, scroll interno. Fechas/observación editables; proveedor/importes/plazo/estado son contexto readonly. Relaciones deadline/supplier nulas ya no provocan excepción; tabla soporta purchase/service ausentes con code_order o Sin orden. Es corrección de render, sin consulta adicional.

### Dates

Formato d/m/Y. Backend exige date_issue/date_expiration y after_or_equal:date_issue; observation nullable|string. Se conserva datepicker. Se desconectó el listener legacy modifyDateIsuue que autocalculaba vencimiento a partir de deadline.days, sobrescribía entrada manual y producía Invalid date con plazo nulo. No se implementó otra regla JS; backend decide. Fechas ausentes se presentan vacías, sin fecha sintética.

### Extend +7 Days

Menú Ampliar vencimiento +7 días, permiso edit_credit, confirmación jQuery Confirm existente. No input de cantidad de días. Endpoint decide elegibilidad (vencimiento presente, no paid_out/canceled), lock y transacción. UI comunica incremento fijo y posibles restricciones backend; no POST ejecutado.

### Paid / Not Paid Semantics

modalPay/formPay conserva fecha date_paid, image_paid opcional, observation2, contexto y hidden. Multipart/CSRF existentes. Texto explícito: marca manual; no registra abono ni elimina existentes. Revertir marca de pago mantiene endpoint cancel/pay/credit y permiso nopay_credit: no borrar crédito, abonos ni comprobantes. Confirmación existente reetiquetada con significado preciso. No se agrega acción Eliminar crédito; modal legacy materialDelete sin trigger no se convierte en operación Credit.

### Manual States / State Transitions

Valores HEAD: pending, pending50, canceled. Se reutiliza SupplierCreditUi.transition de DEV; no replica cálculo financiero. Salir de canceled o pasar a pending requiere nopay_credit; pasar a pending50/canceled requiere pay_credit; algunas transiciones requieren ambos. Estado actual no se ofrece como operación. Grupo muestra etiqueta actual y sólo destinos permitidos. Cancelado es estado manual, no hard delete. Confirmación jQuery Confirm, no window.confirm; callback refresca DataTable sin duplicar filas. Backend mantiene autoridad y defensa adicional por transición.

### Documents

Comprobante de marca conserva ruta protegida. **DOCUMENT QA PENDING**. Documentos de factura legacy aún apuntan a images/entries en el renderer existente; no se certifican como protegidos por el fix de comprobantes de pago. No se abrieron/descargaron ni cambiaron contratos documentales. Límite documentado, no cierre global de seguridad documental.

### Errors / Success

422 inline en formulario específico, modal abierto, valores conservados y botón rehabilitado; summary muestra message HTTP o fallback con código403/404/409/500. GET fallido no habilita submit de datos anteriores. Callbacks edit/pay conservan mensaje, cierre y reload tras éxito; reversión conserva reload. Nuevos controles +7/estado usan confirmación y refresh tabla sólo tras success. Sin ejecución real de ningún callback financiero de éxito.

### Dataset / Cross-context

Nueva lectura MySQL READ ONLY/ROLLBACK: Owner EDESCE T1/C1/B1=0, T1/C2/B2=0; Owner Gamarra T3/C3/B4=0. Owners con permisos efectivos control/edit/pay/nopay_credit. ID99999999 devuelve404 en getCreditById para los tres. No crédito propio/ajeno real para certificar casos positivos HTTP. Suite read-only de scope/saldo en SQLite privado2tests/19assertions PASS, IDs completos aislados1/2/3; no mutaciones de negocio. Esta evidencia no equivale a sesión HTTP completa ni QA financiero.

### Mutation Boundary

No POST real de editar, +7, marca/reversión ni estados; ninguna apertura documental/export. Respuestas de fila/detalle positivas controladas sólo en Chrome; no fixtures DB. Para comprobar422 se intercepta $.ajax en memoria antes del handler, sin request POST. Backend/models/routes/permissions/storage unchanged. MUTATION QA PENDING para todas las operaciones.

### Responsive / Visual Parity

Chrome headless propio, sin escritorio del usuario. Referencia directa: indexInvoicesPending ya integrada y patrón Material V2. Mismos toolbar/menú/tabla/badges/secciones, CSS0. Diferencia contractual válida: no switch de columnas, campos y marca manual distintos de abonos. VISUAL PARITY; no rediseño de familia.

Evidencias temporales: registered-qa.json, registered-edit-{320,1440}.png, registered-pay-{320,1440}.png, registered-validation.png, registered-confirm.png y registered-list.png. QA final registra matriz320/375/390/576/768/992/1200/1440 con scroll global y modal; dataset real vacío se distingue de fixture visual. Confirmaciones abiertas/canceladas, nunca aceptadas.

### Registered Files / Status

resources/views/credit/indexSupplierCredit.blade.php; public/js/credit/index_creditSupplier.js; este documento extendido. Allowlist index.credit.supplier ya existente, sin cambios. Backend unchanged, CSS0, sin commit. Cambios previos de Facturas y saldos/Profile preservados.

Registered FRONTEND INTEGRATED; Edit FRONTEND INTEGRATED — MUTATION QA PENDING; +7/Paid/Not Paid/Manual Status UI INTEGRATED — MUTATION QA PENDING. No se declara certificación de mutaciones/documentos. Blade PHP7.3 compile y node --check PASS; git diff --check PASS.

Registered QA final: 8 anchos × edit/pay =16 mediciones, document.scrollWidth=viewport y modal scrollWidth=clientWidth. Confirmaciones en los mismos8 anchos sin overflow; todas canceladas. Error422 simulado conserva fecha01/01/2020 y modal abierto, feedback visible. Permiso control_credit sin permisos mutadores no muestra operaciones. Búsqueda sin coincidencias PASS; pageerrors0; POST/documentos0. Evidencia adicional registered-extra.json. Forms usan utilidades flex/overflow existentes para mantener footer accesible y scroll interno sin CSS nuevo.
