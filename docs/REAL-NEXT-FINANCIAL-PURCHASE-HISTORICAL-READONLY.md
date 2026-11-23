# REAL NEXT — Financial Purchase — Historical Read-only Batch

Fecha: 2026-11-22. HEAD auditado: 996a31a. Integración frontend sin commit.

## Status

- Financial Purchase Deleted: FRONTEND INTEGRATED — READ PATH.
- Financial Purchase Deleted Detail: FRONTEND INTEGRATED — READ PATH; binding con registros reales y revisión visual poblada PENDING DATASET.
- Financial Purchase Regularized: FRONTEND INTEGRATED — READ PATH.
- Scope: TENANT + COMPANY. Branch no forma parte del ownership.
- Backend: UNCHANGED. CSS nuevo: 0.
- Restore, regularización y documentos: OUT OF SCOPE.

## Routes

Todas bajo `/dashboard`, GET. Controller: OrderPurchaseFinanceController.

| Surface / fetch | Route / URI | Action | Permission |
|---|---|---|---|
| Eliminadas | order.purchase.finance.delete / ordenes/compra/finanzas/eliminadas | indexOrderPurchaseFinanceDelete | destroy_orderPurchaseFinance |
| Fetch eliminadas | all/order/finance/delete | getOrderDeleteFinance | destroy_orderPurchaseFinance |
| Detalle histórico, sin nombre de ruta | ver/orden/compra/finanzas/eliminada/{id} | showOrderPurchaseFinanceDelete | list_orderPurchaseFinance |
| Regularizadas | order.purchase.finance.list.regularize / ordenes/compra/finanzas/regularizadas | indexOrderPurchaseFinanceRegularize | list_orderPurchaseFinance |
| Fetch regularizadas | all/order/purchase/finance/regularize | getAllOrderRegularizeFinance | list_orderPurchaseFinance |

## Permissions

Se preservan web, auth, check.user.enabled, password.changed, tenant.context, permission y guard contextual del controller. No se reinterpretó destroy como list para la papelera. Los arrays enviados por estos listados usan getPermissionsViaRoles: se conserva su contrato para showPrices_quote y la acción de detalle; no se introdujo bypass Owner ni permisos nuevos. La navegación de cabecera usa @can.

## Scope

OrderPurchaseFinance y OrderPurchaseFinanceDetail usan ScopedFinancePurchaseCompany. Tenant/company califican las queries, incluso withTrashed/onlyTrashed; sin contexto el scope aplica 1=0. El middleware del controller exige una company perteneciente al tenant. No se agregan filtros branch ni IDs contextuales al AJAX.

## Deleted List

Vista indexFinanceDeleted, GET data actual con onlyTrashed y orden created_at descendente. Columnas: ID, código, fecha orden, fecha entrega, observación, proveedor, aprobado por, moneda, total, estado y acción. Total conserva visibilidad showPrices_quote. Estado Eliminada corresponde al contrato onlyTrashed.

Única acción: Ver detalle histórico, visible con list_orderPurchaseFinance según permissions enviado. Enlace al URI histórico existente. No dropdown vacío ni botones de restore/print/delete. Búsqueda global, selector de tamaño de página y paginación client-side DataTables conservados. No filtros adicionales ni column visibility inventados.

## Deleted Detail

Vista showOrderPurchaseDeleteFinance. Patrón label/value, secciones con next-form-section, tabla de líneas y totales; sin formulario disabled, plugins de edición ni JS mutador.

Datos: código, registro activo/eliminado, fecha de eliminación, fechas de orden/entrega, proveedor, aprobador, plazo, regularización, referencia de cotización de proveedor, quote_id persistido y observación. Relaciones proveedor/aprobador/plazo son las ya cargadas por controller, no snapshots inventados. Se muestra quote_id sin cargar la relación quote adicional desde Blade.

## withTrashed Behavior

VALID REAL CONTRACT DIFFERENCE: el controller utiliza withTrashed y no exige deleted_at. Se preserva exactamente, sin filtro artificial frontend. Las líneas también incluyen eliminadas y muestran su propio estado registrado.

## Active Record Through Deleted Detail

Una orden activa propia puede resolver por este contrato; la vista muestra Activa y No eliminada, nunca Eliminada por el nombre del URI. El código se verificó; no hay orden activa real para confirmar HTTP 200. PENDING DATASET. No se enlaza desde regularizadas a este URI como sustituto del detalle activo excluido.

## Regularized List

Vista indexRegularizeFinance. Fetch existente con regularize=r, exclusión normal de soft-deleted y orden created_at descendente. Mismas nueve columnas de datos que eliminadas y estado Regularizada; sin columna de acciones porque los destinos legacy eran print y detalle activo (GET con Audit), ambos excluidos. No se ejecuta regularización ni se inventa una acción alternativa.

## Dataset

Lectura nueva MySQL bajo START TRANSACTION READ ONLY y ROLLBACK, sin desactivar observers/Audit. Tabla order_purchase_finances global: 0 registros, incluidos eliminados. Sin órdenes ni fixtures creados.

| Contexto | Actor QA | Tenant / Company / Branch | IDs eliminadas | IDs regularizadas |
|---|---|---|---|---|
| EDESCE C1 | Owner EDESCE (user4) | 1 / 1 / 1 | [] | [] |
| EDESCE C2 | Owner EDESCE (user4) | 1 / 2 / 2 | [] | [] |
| Gamarra | Owner Gamarra (user7) | 3 / 3 / 4 | [] | [] |

Ambas capacidades list/destroy disponibles para los actores examinados. Controller/fetch/render ejecutados en Laravel con Auth y sesión array por contexto; no equivale a un nuevo login HTTP end-to-end.

## Lines

El backend persiste descripción en detail.material (store recibe items[].service y lo guarda en material); el Blade legacy leía detail.service. La presentación ahora muestra material, unit, quantity, price, igv y total_detail persistidos, ID y deleted_at. No consulta Material/StockItem/catálogo actual ni calcula costo/stock/recepción. Ausencias se muestran como No registrado.

## Currency / Totals

Currency literal del backend, sin convertir a PEN/USD por defecto. Importes persistidos se muestran sin suma, resta, multiplicación ni conversión. Se elimina la presentación legacy calculada quantity*price y subtotal=total-igv; no se inventa subtotal no persistido. No agregados entre monedas.

## Documents Boundary

DOCUMENT QA / SECURITY — OUT OF SCOPE. No PDF, imagen, adjunto, export, impresión ni request documental desde estas vistas.

## Restore Boundary

RESTORE — OUT OF SCOPE / MUTATION QA PENDING. El backend permanece intacto; no se carga indexDeleted.js legacy que contiene restore.

## Regularization Boundary

Sólo consulta de órdenes ya regularizadas. No create/edit, detalle activo, regularización automática, POST, updates/deletes de líneas, estados ni lost. Sus deudas previas Audit/parsing no se corrigen ni se aceptan implícitamente.

## Cross-context

- Fetch en C1/C2/Gamarra: [] en los seis casos. Se contrastaron IDs completos; dataset vacío limita evidencia de aislamiento positivo/negativo.
- ID inexistente99999999: ModelNotFoundException (contrato404) en los tres contextos, usando action real. No se presenta como respuesta HTTP capturada.
- Scope sin contexto: count0.
- Propia eliminada200 / propia activa200 / otra company404 / otro tenant404: PENDING DATASET real. No se certifican con colecciones vacías.
- Regresión existente FinancePurchaseListReadPathTest: 2 tests /10 assertions PASS en SQLite privado; caracteriza scope/soft-delete/fail-closed y permisos del listado principal. No se afirma que cubra todos los middleware HTTP históricos ni binding del detalle.

## Empty / Error / Pagination

Vacío real confirmado en ambas listas, los tres contextos. Búsqueda escrita en DataTables funciona sobre el dataset vacío; búsqueda sin coincidencias sobre dataset poblado queda pendiente. No hay filtros adicionales reales.

403/500 simulados en transporte del navegador: mensaje explícito con HTTP, resultados ocultos y summary Consulta fallida; no se convierten en empty state. Respuesta con data no-array también tiene rama de error. Simulación no certifica autorización backend.

MULTIPAGE QA — PENDING DATASET. No se fabricaron órdenes para llenar páginas.

## Responsive

Chrome headless aislado, sin controlar escritorio/navegador del usuario. Render Laravel real y JSON real capturados en lectura y reproducidos en el navegador; assets permanentes desde servidor local8080. AJAX ajeno al batch neutralizado, requests no-GET bloqueados en QA.

320 /375 /390 /576 /768 /992 /1200 /1440: listas en tres contextos =48 mediciones; sin overflow global. Tabla con scroll local, toolbar wrap, menú móvil colapsado mediante PushMenu existente. Consola JS:0 errores; assets CSS/JS/fonts fallidos:0; requests de escritura:0.

Detalle:8 medidas estructurales con modelo vacío NO persistido y relaciones vacías, únicamente para render/presentación. No representa orden real, binding200 ni QA de líneas pobladas. Revisión poblada y con textos largos: PENDING DATASET.

## Visual Parity

VISUAL PARITY: reuse de material.indexV2 y Normal Purchase Trash para toolbar/tabla/DataTables, y Normal Purchase deleted detail para secciones label/value. Sin CSS local ni reglas globales nuevas. Ambas listas comparten history-readonly.js y misma composición.

VALID REAL CONTRACT DIFFERENCE: withTrashed acepta activa; regularizadas carece de acción segura incluida; no hay column visibility; detalle financiero utiliza líneas de texto persistido, no Material/StockItem. Estas diferencias no son rediseño.

No desviación visual nueva identificada en estados vacíos inspeccionados. Paridad con datos poblados pendiente de dataset.

## Allowlist / Files

Sólo se añaden order.purchase.finance.delete, order.purchase.finance.list.regularize y URI exacto dashboard/ver/orden/compra/finanzas/eliminada/{id}. Sin comodines ni endpoints JSON. Venti Next cargado en los renders inspeccionados.

Archivos de este batch:
- config/venti-next.php
- resources/views/orderPurchaseFinance/indexFinanceDeleted.blade.php
- resources/views/orderPurchaseFinance/indexRegularizeFinance.blade.php
- resources/views/orderPurchaseFinance/showOrderPurchaseDeleteFinance.blade.php
- public/js/orderPurchaseFinance/history-readonly.js (nuevo, exclusivo de este batch)
- docs/REAL-NEXT-FINANCIAL-PURCHASE-HISTORICAL-READONLY.md

Los JS legacy no se borran ni modifican; dejan de cargarse en las dos vistas intervenidas. No cambios en app/, routes/, migraciones, backend ni otros módulos. Cambios preexistentes del workspace preservados.

## Checks / Evidence

- PHP7.3.33 artisan view:cache: PASS.
- node --check public/js/orderPurchaseFinance/history-readonly.js: PASS.
- FinancePurchaseListReadPathTest: 2 tests /10 assertions PASS (warning de schema XML PHPUnit preexistente).
- git diff --check: PASS; aviso LF/CRLF de documento Profile preexistente, sin error whitespace.
- Evidencia local: finance-history-read.json, finance-history-browser.json y capturas finance-history-*.png en `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/finance-history/`. Scripts QA temporales no contienen credenciales.

## Backend Sync Notes

[OPEN] Dataset financiero vacío. Impide certificar bindings con registros propios/ajenos, detalle poblado y multipágina. No requiere inventar datos; frontend puede continuar: Sí.

[OPEN] GETs create/edit/show activo/lost con Audit, parsing OCF- y workflow regularización/restore permanecen fuera de alcance. No se modifica su estado ni se ejecutan.

[IMPLEMENTED] Tres vistas históricas contra contratos existentes, sin escrituras financieras ni nuevos endpoints.
