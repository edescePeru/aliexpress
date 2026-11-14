# REAL NEXT — Entries V2 + Órdenes para almacén — READ-ONLY LISTS

Fecha: 2026-11-12. Roadmap C, fase 4. Sólo los dos listados certificados. Backend sin cambios; sin commit.

## Contract Audit

Auditados EntryController::listEntryPurchaseV2/getAllEntriesV2/listOrderPurchaseV2/getAllOrdersV2/showEntryPurchase/updateStateOrderPurchase, Entry, OrderPurchase, BelongsToTenant/TenantScope, ScopedPurchaseCompany y NormalPurchaseReceiptSummary, rutas, vistas y sus dos scripts.

Entries usa Entry con frontera tenant y condición company explícita. Su total es el accessor backend sobre detalles (total_detail o cantidad * precio); el frontend sólo lo presenta. Supplier es tenant-scoped. Entry conserva relaciones details, supplier, company, sourceOrder y la relación legacy por código de orden. No se modifica su granularidad.

OrderPurchase aplica ScopedPurchaseCompany con tenant y company, fail-closed sin contexto válido. El listado usa órdenes tipo n. created_user ya incluye el fallback backend «No registrado / usuario no disponible». No se deduce autor desde Worker. Material/StockItem intervienen en la validación del resumen de recepción, no son columnas del listado. No se reconstruyen variantes.

## Endpoint Matrix

Prefijo común /dashboard. Middleware común auth, check.user.enabled, password.changed y tenant.context.

| Endpoint GET | Action | Permission | Incluido |
|---|---|---|---|
| listado/entradas/compra/v2 | listEntryPurchaseV2 | list_entryPurchase | Sí, vista |
| get/all/entries/v2/{numberPage} | getAllEntriesV2 | list_entryPurchase | Sí, lectura AJAX |
| entradas/compra/ordenes/v2 | listOrderPurchaseV2 | listOrder_entryPurchase | Sí, vista |
| get/all/orders/entrie/v2/{numberPage} | getAllOrdersV2 | listOrder_entryPurchase | Sí, lectura AJAX |

## Routes

Allowlist ampliado exclusivamente con entry.purchase.indexV2 y order.purchase.list.indexV2. ALLOWLIST MATCH: YES. Navegador confirma venti.min.css y venti-next.css. No se añaden rutas de detalle, edición, documentos, reportes, legacy o recepción.

## Permissions

Se mantienen los middleware list_entryPurchase y listOrder_entryPurchase en vistas y datos. Se conserva hidden #permissions recibido del backend. Entries obtiene getPermissionsViaRoles; Orders getAllPermissions. Al no incluir acciones de filas, no se reinterpretan capacidades desde JS. QA con Owner EDESCE y Owner Gamarra autorizados; no se certifica matriz exhaustiva de roles sin permiso.

## Entries V2 Scope

TenantScope + company_id = TenantContext::companyId(), entry_type = Por compra, finance = false. Orden date_entry descendente. No equivale a created_at. Años de filtro proceden de las entradas de la company según el contrato existente, sin restringir esa lista auxiliar a las mismas condiciones del listado.

## Orders V2 Scope

ScopedPurchaseCompany + type = n. Fecha date_order descendente. Estado de recepción calculado por NormalPurchaseReceiptSummary; el endpoint entrega state, stateText, can_receive y receipt_errors. No se habilitan acciones a partir de can_receive.

## Tenant / Company

Contexto autenticado existente; no selectors de tenant/company agregados a las pantallas. Supplier se muestra tal como llega del backend, sin filtro company adicional.

## Branch Non-Scope

BRANCH — NOT APPLICABLE / COMPANY LEVEL. C1 B1 y C1 B8 devuelven exactamente los mismos IDs en ambos listados. No es fuga: no existe contrato branch. No se agrega selector o filtro branch.

## Dataset

Lectura real, sin fixtures ni escrituras comerciales. Comparados todos los IDs, no sólo conteos.

| Cuenta/rol | Tenant | Company | Branch QA | Entries | Orders |
|---|---:|---:|---|---|---|
| Owner EDESCE | 1 | 1 | 1 y 8 | 31 | 4, 3, 2 |
| Owner EDESCE | 1 | 2 | 2 | 52, 49 | vacío |
| Owner Gamarra | 3 | 3 | 4 | 45 | vacío |

IDs C1: 53, 54, 44, 43, 42, 41, 40, 39, 37, 35, 25, 24, 22, 21, 20, 19, 18, 17, 16, 15, 14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4.

Orders C1: 4 = COMPLETA (1); 3 y 2 = POR INGRESAR (2). No hay registros INCOMPLETA o REQUIERE REVISIÓN en el dataset. No se fabricaron para QA.

## Filters

Entries: year, supplier, invoice, guide, order, startDate, endDate. Invoice/guide/order conservan búsqueda LIKE. Rango d/m/Y sobre date_entry sólo cuando ambos extremos existen. Supplier usa la relación backend actual.

Orders: year, supplier, code, quote, state, deliveryDate, startDate, endDate. quote filtra observation; deliveryDate aplica date_arrival; rango aplica date_order. Se conserva el parámetro legacy type del script (sin control activo; backend fija n), sin inventar un filtro de tipo.

Paginación actual de 10 registros: currentPage, totalPages, startRecord, endRecord, totalRecords y totalFilteredRecords. Buscar vuelve a página 1. Se descartan respuestas AJAX obsoletas con contador de solicitud. Respuesta vacía muestra 0 a 0; error HTTP se muestra separado y limpia resultados previos. No se cambia el endpoint ni su semántica.

Limitación backend existente: Entries no normaliza numberPage como Orders (que usa max(1, int)). No se certifican páginas manipuladas fuera de rango ni formatos de fecha inválidos; UI emite páginas válidas. No se corrigió backend.

## Entry List Mapping

| Columna | Campo real |
|---|---|
| ID | id |
| Guía de remisión | guide |
| Orden de compra | order |
| Factura | invoice |
| Tipo de entrada | type |
| Proveedor | supplier |
| Fecha de entrada | date_entry |
| Diferido | diferidoText (badge backend) |
| Moneda | currency |
| Total | total |

No se inventan almacén, usuario o código Entry. file/image se reciben según contrato pero no se renderizan ni solicitan. Campos de texto se insertan como textContent; sólo badges generados por backend conservan HTML.

## Orders List Mapping

| Columna | Campo real |
|---|---|
| ID | id |
| Código | code |
| Fecha orden | date_order |
| Fecha llegada | date_arrival |
| Observación | observation |
| Proveedor | supplier |
| Creado por | created_user |
| Moneda | currency |
| Total | total |
| Recepción | stateText; receipt_errors como title |

No se agregan cantidades ni estado comercial de orden: no están expuestos como columnas de este contrato. Empty state «Sin órdenes para los filtros seleccionados» es válido tanto para pendientes como completas.

## NormalPurchaseReceiptSummary

Servicio de lectura. Valida orden normal del tenant/company; consulta detalles y entradas relacionadas con tenant/company y soft-delete, por FK y compatibilidad legacy de código. Comprueba identidad de línea, material y stock_item, cantidades positivas y consistencia histórica. Entradas anuladas/relaciones contradictorias producen errores. Trabaja cantidades en centésimas; no se replica cálculo en frontend.

| Valor | Filtro backend | Presentación |
|---|---|---|
| 1 | COMPLETAS | COMPLETA |
| 0 | INCOMPLETAS | INCOMPLETA |
| 2 | POR INGRESAR | POR INGRESAR |
| null / filtro review | REQUIERE REVISIÓN | badge backend de revisión |

El servicio expone ordered/received/pending por línea; no se añaden al listado porque el endpoint no entrega las líneas. No se usan colores para inferir estados.

## Action Matrix

Todas las rutas indicadas mantienen su implementación; sólo se excluyen sus accesos desde estas dos pantallas.

| Action | Route / endpoint | Permission | Read/Mutation | Safe para esta fase | Included |
|---|---|---|---|---|---|
| Consultar / filtrar / paginar Entries | entry.purchase.indexV2 + get/all/entries/v2 | list_entryPurchase | Read | Sí | Sí |
| Consultar / filtrar / paginar Orders | order.purchase.list.indexV2 + get/all/orders/entrie/v2 | listOrder_entryPurchase | Read | Sí | Sí |
| Ver Entry | entry.purchase.show | list_entryPurchase | Read | No, company pendiente | No |
| Crear Entry | entry.purchase.create / entry.purchase.store | create_entryPurchase | Form/Mutation | Fuera de alcance | No |
| Editar Entry | entry.purchase.edit / entry.purchase.update | update_entryPurchase | Form/Mutation | Fuera de alcance | No |
| Eliminar Entry | entry.purchase.destroy | destroy_entryPurchase | Mutation POST | Fuera de alcance | No |
| Recibir orden | order.purchase.create / entry.purchase.order.store | create_entryPurchase | Form/Mutation | Fuera de alcance | No |
| Actualizar estado legacy | update/state/orders/with/status | listOrder_entryPurchase | GET legacy, hoy 405 | No reactivar | No |
| Documentos extras / imágenes | agregar/documentos/extras/entrada; modificar/eliminar/guardar images | update_entryPurchase | Read/Mutation | Deuda autorización/public-path | No |
| Imagen/descarga pública | images/entries | Sin frontera suficiente certificada | Read | No | No |
| Imprimir orden | imprimir/orden/compra/{id} | list_orderPurchaseNormal | Generación documental | Fuera de alcance | No |
| Regularizar / cambiar warehouse / posting | Flujos de recepción/regularización existentes | No consumidos en esta fase | Mutation | Fuera de alcance | No |

No hay columna Acciones ni enlaces de detalle, recepción o documentos. Se retiraron de estos scripts handlers y funciones de mutación/detalle/documentos; no se alteraron endpoints ni otras pantallas.

## Entry Detail Security Finding

ENTRY DETAIL — BACKEND SYNC OPEN / EXCLUDED. showEntryPurchase(Entry $entry) no valida company; Entry tiene TenantScope pero no una frontera company suficiente para binding dentro del tenant. No se visitó el detalle ni se certificó como seguro. No se modernizó su Blade, no se añadió allowlist ni guard frontend. Backend debe resolver y probar autorización por company antes de reconsiderarlo.

## Documents Boundary

ENTRY DOCUMENTS — BACKEND/INFRA OPEN / EXCLUDED. Sin imágenes de entries, documentos extras, adjuntos, edición de imágenes, descargas o impresión. El contrato JSON legacy todavía contiene campos documentales: esta fase no los elimina del backend ni certifica autorización de sus paths.

## Receipt Boundary

RECEIPT MUTATION — OUT OF SCOPE. Sin createEntryOrder/storeEntryPurchaseOrder, selección warehouse, lotes, costos, posting o inventory movements. updateStateOrderPurchase actualmente aborta 405; no se llamó ni se reactiva el GET legacy.

## Responsive QA

Patrón reutilizado: Venti Next Advanced Operational List, next-page-toolbar, next-list-toolbar, busqueda-avanzada, next-list-summary, next-data-table y next-list-pagination. Surface Spacing Standard existente. 0 CSS nuevo. Select2 width 100%; bootstrap-datepicker usa container .wrapper y orientación bottom auto, como patrón ya aplicado al reporte Ganancias, evitando el desplazamiento del body. Separación medida entre input y calendario: 4 px en desktop y a 320 px; calendario móvil dentro del viewport (x42–264). Escape cierra.

| Ancho | Entries | Orders | Overflow global |
|---:|---|---|---|
| 320 | PASS | PASS | NONE |
| 375 | PASS | PASS | NONE |
| 390 | PASS | PASS | NONE |
| 576 | PASS | PASS | NONE |
| 768 | PASS | PASS | NONE |
| 992 | PASS | PASS | NONE |
| 1200 | PASS | PASS | NONE |
| 1440 | PASS | PASS | NONE |

Tablas permiten scroll horizontal LOCAL. Filtros abiertos durante la matriz; lectura de dimensiones DOM y capturas desktop/móvil. Entries: página 2, búsqueda sin resultados y vuelta a página 1 comprobadas en navegador. Orders: filtro COMPLETAS devuelve sólo ID4. Consola consultada sin errores. Sesiones de navegador C1/C2/Gamarra confirman datasets y vacíos; contexto final Gamarra.

Evidencia externa al repo: C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/entries-lists/ (entries-desktop.png, orders-desktop.png, entries-mobile.png, orders-mobile-calendar.png).

## Cross-context QA

Prueba PHP temporal fuera del repo: Laravel autenticado con Owners autorizados, InitializeTenantContext, transacción READ ONLY con rollback; invocación de las acciones GET seguras del controlador. El navegador complementa con HTTP autenticado de ambas vistas/listados.

- Todos los IDs de C1/C2/Gamarra son disjuntos entre companies.
- C1 B1 y B8: misma lista completa, company-level esperado.
- Parámetros tenant_id/company_id/branch_id = 999 no sustituyen contexto autenticado: sólo dataset propio.
- Supplier de tenant ajeno: vacío en ambos listados.
- Invoice/code inexistentes: vacío; rango 01/01/2026–31/12/2026 conserva dataset esperado.
- Filtro state: 1 devuelve [4], 2 [3,2], 0 y review vacíos en C1; los cuatro vacíos en C2/Gamarra.
- Contextos no autorizados/inconsistentes (T1/C3/B4, T3/C3/B4 con Owner EDESCE, T1/C999999/B999999): middleware rechaza 403.
- No se ejerció detalle inseguro ni ningún endpoint de escritura comercial.

## Backend Sync Notes

- ENTRY DETAIL — BACKEND SYNC OPEN: falta company guard en show/binding.
- ENTRY DOCUMENTS — BACKEND/INFRA OPEN: paths públicos/autorización no certificados.
- Paginación manipulada y fechas inválidas de Entries: hardening backend pendiente de fase apropiada, no modificado.
- INCOMPLETA / REQUIERE REVISIÓN: contrato auditado, QA positivo pendiente de dataset real, sin generar registros.
- Recepción, documentos y mutaciones permanecen fuera de alcance. No se cierra deuda por ocultar acciones.

## Revisit Triggers

Reauditar cuando backend cierre company en detalle; proteja documentos; cambie payload, receipt summary, fechas o permisos; introduzca branch scope; o exista dataset real de recepción parcial/revisión. Abrir recepción sólo como fase separada con aprobación y QA propios.

## Files and validation

Modificados: config/venti-next.php; resources/views/entry/index_entry_purchasev2.blade.php; resources/views/entry/listOrderPurchaseV2.blade.php; public/js/entry/index_entry_purchaseV2.js; public/js/entry/listOrdersV2.js; este documento.

Validación: node --check de ambos scripts; php artisan view:cache; git diff --check. Sin cambios en controllers/modelos/services/rutas/CSS ni consumidores ajenos. Sin commit.

## Final status

- Entries V2 List — FRONTEND INTEGRATED — READ PATH
- Orders for Warehouse V2 List — FRONTEND INTEGRATED — READ PATH
- Tenant / Company Isolation — QA PASSED (alcance de listados y casos descritos)
- Branch — NOT APPLICABLE / COMPANY LEVEL
- Entry Detail — BACKEND SYNC REQUIRED / EXCLUDED
- Entry Documents — BACKEND/INFRA OPEN / EXCLUDED
- Receipt / Mutations — OUT OF SCOPE
- Backend — UNCHANGED
