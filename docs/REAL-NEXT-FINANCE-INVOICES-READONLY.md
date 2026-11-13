# REAL NEXT — Finance Invoices — Read-only Integration

Fecha: 2026-11-12. Fase de lectura comercial. No commit. Backend sin cambios.

## Post-Migration Audit

Inspeccionados InvoiceController, FinanceInvoiceContext, FinanceInvoiceAmounts, Entry, DetailEntry, CategoryInvoice, rutas, cinco Blades, scripts legacy asociados y patrones Venti Next. Migración `2026_11_10_000002_add_finance_tax_snapshot_to_detail_entries`: Yes, batch 166. Schema SQL confirma finance_tax_kind y finance_tax_amount.

El controller no es homogéneo. Se integran cinco vistas que consumen financeEntries; no se certifica el controller completo. El detalle anterior cargaba presentación de edición y controles/plantillas innecesarios: ahora es solo lectura sin formulario ni JS de mutaciones. Los scripts legacy se conservan intactos para otros consumidores, pero ninguna de las cinco vistas los carga.

## Endpoint Matrix

Prefijo de todas las rutas: `/dashboard`. T/C = TenantContext tenant/company. Los GET de colecciones registran Audit::create; son lectura comercial con escritura técnica de auditoría existente, no SQL puramente read-only. No se deshabilitó auditoría.

| Route | Action | financeEntries | Tenant | Company | Permission | Read/Mutation | Included |
|---|---|---|---|---|---|---|---|
| factura/compra | indexInvoices | vía AJAX | T | C | list_invoice | Vista | Sí |
| factura/compra/anuladas | indexInvoicesAnnulled | vía AJAX | T | C | list_invoice | Vista | Sí |
| get/json/invoices/purchase | getJsonInvoices | Sí | T | C | list_invoice | Lectura + Audit | Sí |
| get/json/invoices/purchase/annulled | getJsonInvoicesAnnulled | Sí | T | C | list_invoice | Lectura + Audit | Sí |
| factura/compra/ver/{entry} | verInvoice | Sí, findOrFail | T | C | list_invoice | Lectura | Sí |
| reporte/faturas/finanzas | reportInvoiceFinance | vía AJAX | T | C | list_invoice | Vista | Sí |
| get/json/invoices/finance | getJsonInvoicesFinance | Sí | T | C | list_invoice | Lectura + Audit | Sí |
| reporte/faturas/finanzas/sin/orden | reportInvoiceFinanceSinOrden | vía AJAX | T | C | list_invoice | Vista | Sí |
| get/json/invoices/finance/sin/orden | getJsonInvoicesFinanceSinOrden | Sí | T | C | list_invoice | Lectura + Audit | Sí |
| get/invoices/purchase | getInvoices | Sí | T | C | list_invoice | Lectura + Audit | No necesario; no consumido |
| get/invoice/by/id/{id} | getInvoiceById | No, Entry directo | T | C | Sin permiso específico en ruta | Lectura | NO: falta finance=true y permiso equivalente |
| get/service/by/id/{id} | getServiceById | No, OrderService | Scope separado | Sin filtro C explícito aquí | Sin permiso específico en ruta | Lectura | NO: contrato distinto |
| exportar/reporte/factura | exportInvoices | Sí | T | C | list_invoice | Export + Audit | Auditoría de código únicamente; no ejecutado |
| crear/factura/compra | createInvoice | Contrato de creación | No certificado en esta fase | No certificado | create_invoice | Vista de mutación | NO |
| invoice/store | storeInvoice | Escritura | No certificado en esta fase | No certificado | create_invoice | POST | NO |
| factura/compra/editar/{entry} | editInvoice | Sí para cabecera | T | C | update_invoice | Vista de mutación | NO |
| invoice/update | updateInvoice | Sí | T | C | update_invoice | POST | NO |
| destroy/detail/invoice/{idDetail} | destroyDetailInvoice | No certificado | No certificado | No certificado | update_invoice | POST | NO |
| destroy/total/invoice/{id} | destroyInvoice | Sí | T | C | destroy_invoice | POST | NO |

Pagos, reversión y cajas: OUT OF SCOPE, no se invocaron. No existe un GET específico de DetailEntry en este grupo; sus detalles solo se muestran después de validar la cabecera. No se probó el endpoint de eliminación como sustituto de una lectura.

## Routes

Allowlist añadida exclusivamente: invoice.index, invoice.index.annulled, invoice.show, report.invoice.finance, report.invoice.finance.sin.orden. ALLOWLIST MATCH: YES. Navegador confirmó venti.min.css y venti-next.css. No se añadió create/edit ni endpoints AJAX a allowlist.

## Permissions

Rutas incluidas: list_invoice dentro del grupo autenticado, usuario habilitado, password.changed y tenant.context. Ambas cuentas Owner probadas tienen list_invoice. La única acción de fila nueva es Ver, hacia invoice.show. Sidebar global conserva navegación preexistente; no se modifica autorización global ni se abren flujos de escritura.

## financeEntries

Entry::query()->where(FinanceInvoiceContext::ids())->where('finance', true). IDs desde TenantContext, nunca desde filtros frontend. El detalle vuelve a verificar ID contextual después de route binding. No se fusionan OrderService y Entry mediante heurística code como hacía JS legacy.

## Tenant / Company Scope

Scope de lectura T+C, SIN filtro branch. La branch visible en shell no implica aislamiento del reporte por local. Contextos sin company no se rescatan. El servicio tiene otros métodos branch-scoped para cajas/sesiones de pago, pero no son el contrato de lectura de facturas. Relaciones Entry->details conservan su backend actual.

## Dataset

Lecturas con transacción SET TRANSACTION READ ONLY y rollback:

| Métrica | Resultado |
|---|---:|
| TOTAL ENTRIES | 43 |
| FINANCE ENTRIES | 4 |
| ACTIVE | 3 |
| ANNULLED | 1 |
| DETAILS de las 4 financieras | 5 |
| WITH TAX SNAPSHOT completo | 0 |
| WITHOUT TAX SNAPSHOT | 5 |
| LEGACY UNSCOPED financiero | 0 |
| Detail tenant/company distinto o NULL respecto a cabecera | 0 |
| Referencia a categoría con contexto distinto | 0 |

Cabeceras 29,30,36,38: T1/C1, PEN, Por compra, type_order purchase, sin purchase_order. Anulada ID30. Categoría1 en 29/30/36; ID38 sin categoría. Dataset no permite QA de USD ni de snapshot completo; no se fabricaron registros.

## Tax Snapshot

Únicamente finance_tax_kind (string 12 nullable) y finance_tax_amount (decimal 12,2 nullable). No persiste ID TypeTax, porcentaje/tasa ni base separada. Kind validado gravada/exonerada/inafecta. FinanceInvoiceAmounts calcula importes al escribir: total recibido en céntimos; gravada usa división 1.18 con redondeo; otras condiciones impuesto cero. Esta regla solo se auditó, nunca se ejecutó ni se duplicó en JS.

Frontend muestra kind/amount guardados; NULL => Sin snapshot / No registrado. No etiqueta históricos como gravada o IGV18 por deducción. Entry/DetailEntry aún tienen fallback histórico 1.18/0.18 cuando falta snapshot: se presentan los importes backend y se advierte ausencia de snapshot. Aprobación de fiscalidad histórica NO certificada. Tax Snapshot — FRONTEND ALIGNED / BACKEND AUTHORITATIVE; QA de filas con snapshot PENDING por dataset.

## CategoryInvoice

forCurrentCompany filtra catálogo T+C, validación de categoría de escritura exige contexto y no deleted_at. Relación Entry::category_invoice usa withTrashed para histórico, pero NO impone por sí sola T+C. Dataset financiero actual no tiene referencias cruzadas; no se filtraron categorías incorrectas en JS. Protección ante futuras referencias corruptas permanece BACKEND SYNC OPEN. No se afirma garantía universal a partir de cuatro registros. Sin edición de categorías ni backfill.

## Active / Annulled

Activas: financeEntries + entry_type Por compra + state_annulled 0. Anuladas: mismo contrato con 1. JSON separados, vistas separadas, badges Activa/Anulada. Reportes incluyen ambos estados conforme a consultas actuales; no se cambió ese significado. No hay anulación desde UI integrada.

## List Mapping

REUSE WITH CONTRACT ADAPTATION: next-page-heading, next-page-toolbar, next-toolbar-actions, next-form-section, next-operational-list, next-list-toolbar, next-list-summary, next-data-table y next-list-pagination. Baseline revisado: venti-next invoice/index_invoice y guía de componentes; composición operativa ya usada por Invoice Categories/User Profit. CSS nuevo 0; CSS global y vendor intactos.

Cuatro tablas usan el mismo adaptador finance-readonly.js; endpoint explícito data-read-url por vista. Columnas: fecha, documento, referencia purchase_order, tipo, proveedor, diferido, categoría, moneda, subtotal, impuestos, total, estado y Ver. Fechas/documento/referencia/estado/acciones centrados, textos izquierda e importes derecha. Valores escapados como texto.

Conservados #dynamic-table, #permissions, #start/#end con names start/end y .date-range-filter. Fecha usa date_entry o date_invoice; filtro inclusivo DD/MM/YYYY solo cuando ambas fechas existen, igual contrato previo; búsqueda/longitud/paginación DataTables local, AJAX dataSrc=data. Sin selector T/C/B, sin nuevos parámetros backend. Datepicker existente anclado a .wrapper, orientation bottom; medición popup 4px bajo input. Totales cruzados de footer legacy eliminados: no sumar monedas ni recalcular fiscalidad en JS. Export no se ofrece en estas superficies hasta QA separado.

## Detail Mapping

Cabecera, proveedor, categoría opcional/histórica, fecha, documento, referencia, tipo, diferido, moneda, observación y estado. Líneas con material_description, entered_quantity, unit, unit_price, sub_total, finance_tax_kind, finance_tax_amount, taxes y total del modelo. Totales de Entry. Sin form, submit, edit_invoice.js, add/delete hooks ni solicitud getInvoiceById/getServiceById. No se cargan imágenes/PDF automáticamente. No hay nuevos cálculos Blade/JS.

## Amount Contract

FinanceInvoiceAmounts es autoridad de nuevas líneas; lectura usa accessors Entry/DetailEntry. Snapshots existentes usados por accessors; históricos conservan fallback backend documentado. JS no resta total-igv ni calcula subtotal/impuesto. Total de ID38 observado 100.00, subtotal84.75, impuestos15.25, snapshot ausente; no interpretado como certificación fiscal. No se modificó inconsistencia histórica.

## Currency

currency_invoice explícita por fila y en detalle/totales. Dataset PEN. Sin conversión ni total agregado multimoneda. USD rendering contractual, QA real pendiente.

## Reports

| Superficie | Contrato | Estado |
|---|---|---|
| report.invoice.finance | financeEntries sin restricción de anulación | REPORT FRONTEND INTEGRATED; browser 4 registros |
| report.invoice.finance.sin.orden | financeEntries, purchase_order NULL, type_order purchase/service | REPORT FRONTEND INTEGRATED; browser4 registros |
| exportar/reporte/factura | financeEntries, start/end opcionales, accessors y InvoicesFinanceExport | READ EXPORT — CONTRACT AUDITED / EXPORT QA PENDING |

El export general no comparte necesariamente filtro sin-orden; no se añadió botón que prometa ese alcance. No ejecutado ni cambiado. Los cálculos de footer y navegación a servicios de JS antiguo no forman parte del nuevo contrato Entry de lectura.

## Documents

Legacy usaba /images/entries/ y /images/orderServices/ públicos. DOCUMENT SECURITY — BACKEND/INFRA OPEN. Se informa existencia de adjunto sin enlazar/cargar archivos no certificados. No se abrió ningún adjunto ni se modificó almacenamiento. Vista detalle medida: 0 documentAssets y 0 forms.

## Responsive QA

Navegador local, Owner EDESCE T1/C1/B1. Las cinco vistas comprobadas en 320,375,390,576,768,992,1200,1440: documentElement.scrollWidth <= innerWidth; diferencia de15px donde existe scrollbar vertical. GLOBAL OVERFLOW: NONE; scroll horizontal dentro de tabla. Se observaron desktop de listado/detalle y móvil de reporte. Consola sin errores observados. Tabla con 3/1/4/4 registros; detalle ID38 con una línea.

Búsqueda inexistente produjo empty; limpiar recuperó registros. Rango01/05/2026–31/05/2026 en reporte sin orden produjo1de4. Calendar gap4px, Escape probado. Página única con controles Anterior/Siguiente; navegación multipágina real pendiente porque dataset <10. Sintaxis JS, view:cache PHP7.3 y git diff --check aprobados.

## Cross-context QA

Harness temporal fuera del repo, sesión array efímera, Auth::setUser y InitializeTenantContext. SET TRANSACTION READ ONLY/rollback. Se invocó financeEntries real mediante Reflection para no disparar Audit::create en el harness; detalle verInvoice comprobado directamente. No confundir con certificación completa HTTP middleware: permisos consultados y rutas auditadas por separado. No POST/login persistente en estas pruebas.

| Cuenta | T/C/B | IDs visibles | Detalle29/30/36/38 | ID99999999 |
|---|---|---|---|---|
| Owner EDESCE | 1/1/1 | 29,30,36,38 | Permitido | ModelNotFound |
| Owner EDESCE | 1/2/2 | vacío | ModelNotFound | ModelNotFound |
| Owner EDESCE | 1/5/6 | vacío | ModelNotFound | ModelNotFound |
| Owner Gamarra | 3/3/4 | vacío | ModelNotFound | ModelNotFound |

ModelNotFound corresponde a rechazo404 del contrato; son pruebas de controller, no mediciones HTTP de esas sesiones. Browser real EDESCE confirmó rutas/AJAX/detalle. Gamarra: empty y cruces comprobados en harness, no login UI. DetailEntry ajeno: sin GET específico incluido; acceso solo por cabecera validada. No se ejecutó mutación para probarlo. Tenant / Company Isolation — QA PASSED para matriz observada, con límite de relaciones indicado abajo.

## Legacy Data

0 cabeceras financieras sin company/tenant;5líneas sin snapshot, conservadas sin backfill. No categorías ambiguas/cruzadas observadas en dataset financiero. No se rescata legacy con JS. No se suma otra moneda ni se adopta tenant desde URL.

## Mutation Exclusions

Create/Edit/Store/Update/Delete/Annulment/Payment/Reversal/Cash — OUT OF SCOPE. No se ejecutó POST/PUT/DELETE ni SQL comercial de escritura. GETs de tabla mantienen Audit::create preexistente: no afirmar cero escrituras técnicas. Sin cron/recalculo de Daily Profit. Backend, modelos, scopes, snapshots, categorías, archivos y scripts legacy intactos.

## Backend Sync Notes

[OPEN]
Point: relación de categoría histórica no impone company.
File / endpoint: Entry::category_invoice; JSON financeEntries.
Impact: referencia corrupta futura podría resolver categoría ajena aunque cabecera esté contextualizada.
What backend needs to define or correct: garantía de integridad contextual en relación/persistencia preservando withTrashed.
Frontend can continue: Yes, dataset auditado sin cruces; no filtro correctivo JS.

[OPEN]
Point: fiscalidad histórica sin snapshot.
File / endpoint: Entry/DetailEntry accessors,5líneas actuales.
Impact: fallback18% no constituye snapshot ni certificación histórica.
What backend needs to define or correct: tratamiento/backfill autorizado de históricos.
Frontend can continue: Yes, ausencia visible e importes backend sin recalcular.

[OPEN]
Point: documentos públicos.
File / endpoint: images/entries, images/orderServices.
Impact: acceso documental no certificado.
What backend needs to define or correct: entrega autenticada/autorizada o infraestructura equivalente.
Frontend can continue: Yes, documentos no enlazados.

[OPEN]
Point: endpoints auxiliares heterogéneos.
File / endpoint: getInvoiceById/getServiceById.
Impact: no equivalencia de finance/permiso/company.
What backend needs to define or correct: alcance y permisos para futura integración.
Frontend can continue: Yes, excluidos y sin consumidores en vistas integradas.

## Revisit Triggers

Backend cambia scopes/relaciones/accessors; aparecen snapshots completos, USD, referencias históricas soft-deleted o dataset multipágina; se certifican exports/documentos; se autoriza fase de mutaciones. Repetir QA de esos casos sin extrapolar evidencia actual.

## Files Changed

- config/venti-next.php: cinco rutas de lectura.
- resources/views/invoice/index_invoice.blade.php
- resources/views/invoice/index_invoice_annulled.blade.php
- resources/views/invoice/show_invoice.blade.php
- resources/views/invoice/report_invoice.blade.php
- resources/views/invoice/report_invoice_sin_orden.blade.php
- public/js/invoice/finance-readonly.js (nuevo adaptador, sin mutaciones).
- docs/REAL-NEXT-FINANCE-INVOICES-READONLY.md

List / Annulled / Detail / ambos Reports — FRONTEND INTEGRATED — READ PATH.
Tax Snapshot — FRONTEND ALIGNED / BACKEND AUTHORITATIVE.
Backend — UNCHANGED. CSS nuevo0. No commit.

## Reporting Exports — QA Closure 2026-11-13

HEAD9262d65. Export auditado: InvoiceController::exportInvoices -> InvoicesFinanceExport -> exports.excelInvoiceFinance, XLSX. GET `/dashboard/exportar/reporte/factura`, permiso `list_invoice` además de web/auth/enabled/password/tenant.context. No endpoint auxiliar getInvoiceById/getServiceById incluido.

### Scope / Permissions / Dataset

financeEntries usa FinanceInvoiceContext::ids: **tenant/company, no branch**. Branch8 ve los mismos IDs de company1 que branch1: comportamiento contractual, no fuga. EDESCE1/1/1 y1/1/8:IDs29,30,36,38; otras companies2 y5 y Gamarra3/3 vacíos. Son4 facturas,3activas,1anulada(ID30),5líneas,0/5finance_tax_amount snapshot; todas PEN. No inventar snapshot. CategoryInvoice vía relación actual; no cruzada en dataset, hardening de referencia corrupta sigueOPEN.

Gate de ruta real en harness: OwnerEDESCE1/1/1 yOwnerGamarra3/3/4 pasan200; CajeroMaster2sinpermiso403; Gamarra con1/1/1 yEDESCE concompany3/branch4:403. Middleware/contexto real con callback, no descarga HTTP de sesiones; export controller generado aparte en contexto autorizado.

### Screen / Export / Filters

Report general consulta financeEntries sin excluir anuladas; export sin fechas entrega las mismas4 cabeceras. No confundirlo con listado de activas ni con informe sin orden: export no tiene filtro de estado, purchase_order/type_order, categoría ni búsqueda DataTables. Las vistas integradas report_invoice/report_invoice_sin_orden y finance-readonly.js no enlazan el export actualmente.

- Fechas reales `start/end` en d/m/Y, usa date_entry inclusive. 28/04/2026 entrega2filas(ID29/30);01/01/1900 entrega0.
- Export sin filtros entrega4filas, no página actual. Dataset financiero no alcanza multipágina, ausencia de paginate/take confirmada en código.
- Prueba start/end vacíos +state_annulled0+searchNO-MATCH: sigue4filas. No trasladar filtros cliente a esta ruta sin contrato.
- **Filters: PARTIAL**: fechasPASS; paridad con búsqueda/estado/categoría o informe sinorden NO IMPLEMENTED. TECH DEBT / contrato de futura integración, no afirmar que el botón actual pierda filtros (no existe en fachada integrada).
- `$_GET['start/end']` directo sin validación. Fechasbad provocan Carbon InvalidFormatException, no422: **BUG de validación**, Expected manejo controlado de input. No corregido: necesita Request validation y política explícita de fechas incompletas/invertidas. Una fecha vacía actualmente exporta todo. Backend Sync OPEN.

### Totals / Currency / Historic Tax

Por ID (subtotal/impuesto/total):29=76.27/13.73/90.00;30=76.27/13.73/90.00;36=93.22/16.78/110.00;38=84.75/15.25/100.00. Suma de cabeceras exportadas330.51/59.49/390.00; activas excluyendo30=254.24/45.76/300.00. Export no añade total global ni convierte monedas; lleva currency_invoice por fila. Mixed currency real no disponible: no certificar dataset multimoneda por tener soloPEN.

Coincide con accessors de pantalla Entry.sub_total/taxes/total. Sin snapshot, subtotal divide total_detail por1.18 (o quantity*unit_price si total_detail null), impuesto=base*0.18. Con finance_tax_amount usaría snapshot. Es **fallback fiscal actual**, no histórico: BACKEND SYNC OPEN permanece. No agregar etiqueta falsa de snapshot.

### File / Format / Test Boundaries

Generado `%TEMP%/reporting-exports-qa/finance.xlsx`;6filas (título+headers+4datos),10columnas A:J:fecha,OC/OS,factura,tipo,proveedor,categoría,moneda,subtotal,impuestos,total. Lectura con PhpSpreadsheet: G=PEN,H/I/J numéricos; fechas texto dd-mm-yyyy. No CSV/PDF en contrato. No guardar artefactos/datos sensibles en repo.

Harness READ ONLY+rollback. El export y GET de reporte tienen Audit::create técnico; para ejecutar export sin ninguna escritura DB se canceló solo Audit::saving en memoria del proceso CLI. No se modificó archivo/backend ni se deshabilitó logging del servidor. Por ello esta evidencia no certifica persistencia de auditoría ni flujo HTTP completo. No abrir documentos públicos.

Export Scope / Permissions / Totals / Cross-context: QA PASSED para dataset/matriz indicada. Filters: PARTIAL. Fiscal snapshot: OPEN. Documents: BACKEND/INFRA OPEN. Backend/frontend UNCHANGED; CSS0; sin mutaciones comerciales ni commit.
