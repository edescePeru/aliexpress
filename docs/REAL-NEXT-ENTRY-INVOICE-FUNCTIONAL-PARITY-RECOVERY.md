# REAL NEXT — ENTRY / INVOICE FUNCTIONAL PARITY RECOVERY

Fecha: 2026-11-25 (America/Lima). Rama feature/venti-next-frontend, HEAD 3914020. R3.1/R3.2 previos preservados; R4 sin commit. **PARTIAL — SAFE CAPABILITIES RESTORED; BACKEND/DOCUMENT BLOCKERS OPEN.** No avanzar R5 hasta revisión del propietario.

## R3 scope closure

General, Express y OrderPurchaseFinance: **LEGACY — OWNER CONFIRMED — NO FURTHER RECOVERY**. Sidebar permanece oculto. LEGACY conserva código y no equivale a RETIRED. No se borraron rutas, permisos, controllers, datos, servicios ni implementaciones anteriores. FR-022…034,037…045,080 son 23 exclusiones de recuperación activa, no 23 capacidades reparadas. R3.2 CLOSED BY PRODUCT SCOPE DECISION.

Normal/Papelera, Entry, Invoice, SupplierCredit, Inventory y reportes vigentes continúan activos. R3.1 no añadió accesos activos hacia destinos General/Express/Finance: Normal opera Normal y papelera opera Normal eliminado; el endpoint de impresión compartido recibe el ID Normal. No rollback unilateral.

DEV: 5 líneas exclusivas diferidas (parser OCF/F; QA financiero I; referencias históricas O; estado Express/Q; paginación GENERAL-01). Permanecen riesgos de acceso directo/seguridad en Express lookups, PUR-R3-01 y regularización financiera G que llama endpoints Normal. Purchase Material Report conserva autorización pendiente por ser reporte vigente. INV-01 compartido, PUR-R3-02, DOC-01, FIN-03, Invoice y SupplierCredit NO se cierran. Ver addenda de ambos backlogs DEV.

## Universo y método

14 superficies revisadas: 11 homologadas (6 originales + Entry show, Invoice anuladas/show/create/edit) y 3 destinos activos revisados sólo por contrato (Entry create/edit y recepción Normal). Las 7 superficies modificadas son las 6 originales + Invoice anuladas. No se amplía allowlist ni se modernizan formularios bloqueados.

El “listado de entradas V2” afectado es entry.purchase.indexV2; no se inventa un segundo listado Inventory allowlisted. Catálogos de categorías y SupplierCredit no se reauditan; se verifica el consumidor/modal existente del formulario Invoice. No se reabre inventario ni se toca stock.

Unidad de inventario: capacidad por superficie; un grupo ya disponible describe explícitamente sus elementos. No se cuentan botones duplicados por registro como capacidades adicionales. 63 filas funcionales abajo: **55 activas (23 disponibles +32 pérdidas), 6 legacy, 2 código no alcanzable**. Las 13 pérdidas originales se confirman; aparecen 19 pérdidas activas adicionales. De las 32, **21 recuperadas y 11 pendientes backend/documentos**. No hay decisiones de vigencia pendientes.

## Rutas, Blades, JS y commits

Prefijo local: http://127.0.0.1:8080/dashboard/. Todas mantienen auth/context y permisos actuales, sin Owner bypass.

| Alias | Ruta / URL | Blade / controller | Permiso | Evidencia Git |
|---|---|---|---|---|
| EP | [Entradas V2](http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2) entry.purchase.indexV2 | entry.index_entry_purchasev2 / EntryController::listEntryPurchaseV2 | list_entryPurchase | 2ec61f3^ → 2ec61f3 → HEAD → R4 |
| OR | [Órdenes para almacén](http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2) order.purchase.list.indexV2 | entry.listOrderPurchaseV2 / listOrderPurchaseV2 | listOrder_entryPurchase; cada acción su permiso | 2ec61f3^ → 2ec61f3 → HEAD → R4 |
| ER | [Reporte entradas](http://127.0.0.1:8080/dashboard/entradas/compra/reporte) entry.purchase.report.index | entry.index_entry_purchase_report / indexEntryPurchaseReport | list_entryPurchase; UI searchReportEntries_entryPurchase | 6097da5^ → 6097da5 → HEAD → R4 |
| IL | [Facturas](http://127.0.0.1:8080/dashboard/factura/compra) invoice.index | invoice.index_invoice / InvoiceController::indexInvoices | list_invoice | fa8f7ed^ → fa8f7ed → HEAD → R4 |
| IR | [Reporte facturas](http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas) report.invoice.finance | invoice.report_invoice / reportInvoiceFinance | list_invoice | fa8f7ed^ → fa8f7ed → HEAD → R4 |
| IS | [Reporte sin orden](http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas/sin/orden) report.invoice.finance.sin.orden | invoice.report_invoice_sin_orden / reportInvoiceFinanceSinOrden | list_invoice | fa8f7ed^ → fa8f7ed → HEAD → R4 |
| IA | [Anuladas](http://127.0.0.1:8080/dashboard/factura/compra/anuladas) invoice.index.annulled | invoice.index_invoice_annulled / indexInvoicesAnnulled | list_invoice | fa8f7ed^ → fa8f7ed → HEAD → R4 |
| ES | [Detalle Entry 54](http://127.0.0.1:8080/dashboard/entrada/compra/visualizar/54) entry.purchase.show | entry.show_entry_purchase / showEntryPurchase | list_entryPurchase | 483a785^ → 483a785 → HEAD; no edición R4 |
| ID | [Detalle factura 29](http://127.0.0.1:8080/dashboard/factura/compra/ver/29) invoice.show | invoice.show_invoice / verInvoice | list_invoice | fa8f7ed^ → fa8f7ed → HEAD; no edición R4 |
| IC | [Crear factura](http://127.0.0.1:8080/dashboard/crear/factura/compra) invoice.create | invoice.create_invoice / createInvoice | create_invoice | 6abf452^ → 6abf452 → HEAD; no edición R4 |
| IE | [Editar factura 29](http://127.0.0.1:8080/dashboard/factura/compra/editar/29) invoice.edit | invoice.edit_invoice / editInvoice | update_invoice | 6abf452^ → 6abf452 → HEAD; no edición R4 |
| EC | /dashboard/crear/entrada/compra | entry.create_entry_purchaseV2 / createEntryPurchase | create_entryPurchase | Destino activo bloqueado; no navegar consumer inseguro |
| EE | /dashboard/entrada/compra/editar/{entry} | entry.edit_entry_purchaseV2 / editEntryPurchase | update_entryPurchase | Destino activo bloqueado; no restaurar ejecución |
| RC | [Recibir orden 3](http://127.0.0.1:8080/dashboard/crear/entrada/compra/orden/3) order.purchase.create | entry.create_entry_purchase_order / createEntryOrder | create_entryPurchase | HEAD ya usa NormalPurchaseReceiptSummary y EntryWarehouseResolver; GET render read-only comprobado |

JS actuales: EP index_entry_purchaseV2.js; OR listOrdersV2.js; ER index_entry_purchase_report.js; IL/IR/IS/IA finance-readonly.js. Adaptador nuevo común entry/list-operations.js; los handlers legacy completos no se cargan para evitar OrderService, documentos públicos y mutadores ajenos. Se preservan hooks data-detail/data-details/data-code/data-delete/data-ver_orden/data-imprimir/data-crear_entrada y btn-export donde corresponden.

## Matriz de pérdidas y recuperación

Todas las filas siguientes son ACTIVE. “Anterior” = retirado durante homologación por alcance read-only; no fue retiro de producto. QA está separado de disponibilidad. Permisos nuevos de presentación se resuelven con getAllPermissions (directos + roles), mismos nombres, sin bypass.

| ID | Pantalla | Capacidad histórica | Vigencia | Permiso | Condición | Endpoint | Estado anterior | Blocker | Recuperación | QA |
|---|---|---|---|---|---|---|---|---|---|---|
| FR-046 | entry.purchase.indexV2 | Crear entrada | ACTIVE | create_entryPurchase | Permiso efectivo; contexto actual | GET /dashboard/crear/entrada/compra | WITHDRAWN | INV-01: preload StockItems/inventoryLevels sin company/permiso | ACTIVE — BACKEND BLOCKER | STATIC — NOT EXECUTED |
| FR-047 | entry.purchase.indexV2 | Editar entrada | ACTIVE | update_entryPurchase | Permiso efectivo; contexto actual | GET /dashboard/entrada/compra/editar/{id} | WITHDRAWN | INV-01: preload; edit no restringe finance/entry_type | ACTIVE — BACKEND BLOCKER | STATIC — NOT EXECUTED |
| FR-048 | entry.purchase.indexV2 | Anular entrada | ACTIVE | destroy_entryPurchase | Permiso efectivo; contexto actual | POST /dashboard/entry_purchase/destroy/{entry} | WITHDRAWN | ENTRY-R4-01: falta exclusión explícita finance=true | ACTIVE — BACKEND BLOCKER | STATIC — NOT EXECUTED |
| FR-049 | entry.purchase.indexV2 | Adjuntos y documentos extra | ACTIVE | update_entryPurchase / list_entryPurchase | Permiso efectivo; contexto actual | /dashboard/agregar/documentos/extras/entrada/{entry}; /images/entries/* | WITHDRAWN | DOC-01 | ACTIVE — BACKEND BLOCKER | STATIC — NOT EXECUTED |
| FR-050 | order.purchase.list.indexV2 | Recibir productos | ACTIVE | create_entryPurchase | Permiso efectivo; contexto actual; can_receive === true | GET /dashboard/crear/entrada/compra/orden/{id} | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-051 | order.purchase.list.indexV2 | Ver orden normal | ACTIVE | list_orderPurchaseNormal | Permiso efectivo; contexto actual | GET /dashboard/ver/orden/compra/normal/{id} | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-052 | order.purchase.list.indexV2 | Imprimir orden normal | ACTIVE | list_orderPurchaseNormal | Permiso efectivo; contexto actual | GET /dashboard/imprimir/orden/compra/{id} | WITHDRAWN | — | ACTIVE — RESTORED | WIRING / intercepted only; real QA PENDING |
| FR-053 | entry.purchase.report.index | Detalle de ítems | ACTIVE | list_entryPurchase | Permiso efectivo; contexto actual | GET /dashboard/get/json/items/{entry_id} | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-054 | entry.purchase.report.index | Adjunto | ACTIVE | list_entryPurchase | Permiso efectivo; contexto actual | /images/entries/* | WITHDRAWN | DOC-01 | ACTIVE — BACKEND BLOCKER | STATIC — NOT EXECUTED |
| FR-055 | invoice.index | Crear factura | ACTIVE | create_invoice | Permiso efectivo; contexto actual | GET /dashboard/crear/factura/compra | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-056 | invoice.index | Anular factura | ACTIVE | destroy_invoice | Permiso efectivo; contexto actual; no anulada | POST /dashboard/destroy/total/invoice/{id} | WITHDRAWN | — | ACTIVE — RESTORED | WIRING / intercepted only; real QA PENDING |
| FR-057 | report.invoice.finance | Exportar facturas | ACTIVE | list_invoice | Permiso efectivo; contexto actual | GET /dashboard/exportar/reporte/factura?start&end | WITHDRAWN | — | ACTIVE — RESTORED | WIRING / intercepted only; real QA PENDING |
| FR-058 | report.invoice.finance.sin.orden | Exportar facturas | ACTIVE | list_invoice | Permiso efectivo; contexto actual | GET /dashboard/exportar/reporte/factura?start&end | WITHDRAWN | — | ACTIVE — RESTORED | WIRING / intercepted only; real QA PENDING |
| FR-081 | entry.purchase.indexV2 | Detalle de ítems | ACTIVE | list_entryPurchase | Permiso efectivo; contexto actual | GET /dashboard/get/json/items/{entry_id} | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-082 | invoice.index | Detalle rápido | ACTIVE | list_invoice | Permiso efectivo; contexto actual | DTO details del listado autorizado | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-083 | invoice.index | Adjunto | ACTIVE | list_invoice | Permiso efectivo; contexto actual | /images/entries/* | WITHDRAWN | DOC-01 | ACTIVE — BACKEND BLOCKER | STATIC — NOT EXECUTED |
| FR-084 | report.invoice.finance | Crear factura | ACTIVE | create_invoice | Permiso efectivo; contexto actual | GET /dashboard/crear/factura/compra | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-085 | report.invoice.finance | Editar factura | ACTIVE | update_invoice | Permiso efectivo; contexto actual; no anulada (Update la rechaza) | GET /dashboard/factura/compra/editar/{entry} | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-086 | report.invoice.finance | Anular factura | ACTIVE | destroy_invoice | Permiso efectivo; contexto actual; no anulada; purchase_order NULL o primer carácter 0 (condición histórica) | POST /dashboard/destroy/total/invoice/{id} | WITHDRAWN | — | ACTIVE — RESTORED | WIRING / intercepted only; real QA PENDING |
| FR-087 | report.invoice.finance | Detalle rápido | ACTIVE | list_invoice | Permiso efectivo; contexto actual | DTO details del listado autorizado | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-088 | report.invoice.finance | Adjunto | ACTIVE | list_invoice | Permiso efectivo; contexto actual | /images/entries/* | WITHDRAWN | DOC-01 | ACTIVE — BACKEND BLOCKER | STATIC — NOT EXECUTED |
| FR-089 | report.invoice.finance.sin.orden | Crear factura | ACTIVE | create_invoice | Permiso efectivo; contexto actual | GET /dashboard/crear/factura/compra | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-090 | report.invoice.finance.sin.orden | Editar factura | ACTIVE | update_invoice | Permiso efectivo; contexto actual; no anulada (Update la rechaza) | GET /dashboard/factura/compra/editar/{entry} | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-091 | report.invoice.finance.sin.orden | Anular factura | ACTIVE | destroy_invoice | Permiso efectivo; contexto actual; no anulada; purchase_order NULL o primer carácter 0 (condición histórica) | POST /dashboard/destroy/total/invoice/{id} | WITHDRAWN | — | ACTIVE — RESTORED | WIRING / intercepted only; real QA PENDING |
| FR-092 | report.invoice.finance.sin.orden | Detalle rápido | ACTIVE | list_invoice | Permiso efectivo; contexto actual | DTO details del listado autorizado | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-093 | report.invoice.finance.sin.orden | Adjunto | ACTIVE | list_invoice | Permiso efectivo; contexto actual | /images/entries/* | WITHDRAWN | DOC-01 | ACTIVE — BACKEND BLOCKER | STATIC — NOT EXECUTED |
| FR-094 | invoice.index.annulled | Crear factura | ACTIVE | create_invoice | Permiso efectivo; contexto actual | GET /dashboard/crear/factura/compra | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-095 | invoice.index.annulled | Detalle rápido | ACTIVE | list_invoice | Permiso efectivo; contexto actual | DTO details del listado autorizado | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |
| FR-096 | invoice.index.annulled | Adjunto | ACTIVE | list_invoice | Permiso efectivo; contexto actual | /images/entries/* | WITHDRAWN | DOC-01 | ACTIVE — BACKEND BLOCKER | STATIC — NOT EXECUTED |
| FR-097 | entry.purchase.show | Adjunto | ACTIVE | list_entryPurchase | Permiso efectivo; contexto actual | /images/entries/* | WITHDRAWN | DOC-01 | ACTIVE — BACKEND BLOCKER | STATIC — NOT EXECUTED |
| FR-098 | invoice.show | Adjunto | ACTIVE | list_invoice | Permiso efectivo; contexto actual | /images/entries/* | WITHDRAWN | DOC-01 | ACTIVE — BACKEND BLOCKER | STATIC — NOT EXECUTED |
| FR-099 | report.invoice.finance | Totales PEN, USD y equivalente PEN | ACTIVE | list_invoice | Permiso efectivo; contexto actual; búsqueda aplicada; currency_venta snapshot | DTO total/currency_invoice/currency_venta; búsqueda aplicada | WITHDRAWN | — | ACTIVE — RESTORED | READ / ISOLATED UI PASS |

### Capacidades ya disponibles: 23 grupos, sin cambios funcionales

Estas filas no son pérdidas ni aumentan las restauraciones. QA R4 sólo se amplía sobre lo intervenido; las demás son contraste estático del contrato existente, no certificación mutadora.

| ID | Pantalla | Capacidad histórica | Vigencia | Permiso | Condición | Endpoint | Estado anterior | Blocker | Recuperación | QA |
|---|---|---|---|---|---|---|---|---|---|---|
| A01 | EP | Filtros año/proveedor/guía/orden/fechas/búsqueda | ACTIVE | list_entryPurchase | Contrato actual | get/all/entries/v2/{page} | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A02 | EP | Paginación y empty/error | ACTIVE | list_entryPurchase | Contrato actual | mismo DTO pagination | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A03 | OR | Filtros y estado de recepción | ACTIVE | listOrder_entryPurchase | Contrato actual | get/all/orders/entrie/v2/{page} | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A04 | OR | Paginación y empty/error | ACTIVE | listOrder_entryPurchase | Contrato actual | mismo DTO pagination | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A05 | ER | Fechas d/m/Y + POST consulta + Audit técnico | ACTIVE | list_entryPurchase + searchReportEntries_entryPurchase UI | Contrato actual | get/json/entries/purchase/report | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A06 | ER | Búsqueda/orden/paginación DataTables y errores | ACTIVE | list_entryPurchase | Contrato actual | respuesta del reporte | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A07 | IL | Fechas/búsqueda/paginación | ACTIVE | list_invoice | Contrato actual | get/json/invoices/purchase | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A08 | IL | Ver factura | ACTIVE | list_invoice | Contrato actual | factura/compra/ver/{entry} | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A09 | IR | Fechas/búsqueda/paginación | ACTIVE | list_invoice | Contrato actual | get/json/invoices/finance | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A10 | IR | Ver factura | ACTIVE | list_invoice | Contrato actual | factura/compra/ver/{entry} | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A11 | IS | Fechas/búsqueda/paginación | ACTIVE | list_invoice | Contrato actual | get/json/invoices/finance/sin/orden | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A12 | IS | Ver factura | ACTIVE | list_invoice | Contrato actual | factura/compra/ver/{entry} | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A13 | IA | Fechas/búsqueda/paginación | ACTIVE | list_invoice | Contrato actual | get/json/invoices/purchase/annulled | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A14 | IA | Ver factura anulada | ACTIVE | list_invoice | Contrato actual | factura/compra/ver/{entry} | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A15 | ES | Proveedor/documento/líneas/importes/observación | ACTIVE | list_entryPurchase | Contrato actual | entrada/compra/visualizar/{entry} | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A16 | ID | Datos/líneas/impuestos snapshot/totales | ACTIVE | list_invoice | Contrato actual | factura/compra/ver/{entry} | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A17 | IC | Campos, moneda, impuesto, cajas/subtipos y sesiones propias | ACTIVE | create_invoice | Contrato actual | FinanceInvoiceContext; GET create | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A18 | IC | Modal crear categoría | ACTIVE | create_categoryInvoice | Contrato actual | POST category/invoice/store | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A19 | IC | Añadir/retirar líneas locales del formulario | ACTIVE | create_invoice | Contrato actual | invoice.js; sin persistir hasta submit | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A20 | IC | Guardar factura y pago | ACTIVE | create_invoice | Contrato actual | POST invoice/store | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A21 | IE | Campos y valores históricos del editor | ACTIVE | update_invoice | Contrato actual | GET factura/compra/editar/{entry} | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A22 | IE | Líneas existentes y handlers originales | ACTIVE | update_invoice | Contrato actual | edit_invoice.js; destroy/detail/invoice retorna rechazo contractual | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |
| A23 | IE | Guardar cambios | ACTIVE | update_invoice | Contrato actual | POST invoice/update | AVAILABLE | QA mutadora separada | ACTIVE — ALREADY AVAILABLE | Estática; lectura focalizada; sin submit |

### Exclusiones de R4: 6 legacy y 2 no alcanzables

| ID | Pantalla | Capacidad histórica | Vigencia | Permiso | Condición | Endpoint | Estado anterior | Blocker | Recuperación | QA |
|---|---|---|---|---|---|---|---|---|---|---|
| L01 | IR | Regularizar compra financiera | LEGACY — OWNER CONFIRMED | regularizeOrder_entryPurchase | finance/type purchase | regularizar/automaticamente/entrada/compra/finanzas/{id} | WITHDRAWN | G residual integrity | No reactivar OrderPurchaseFinance | Static |
| L02 | IS | Regularizar compra financiera | LEGACY — OWNER CONFIRMED | igual L01 | igual L01 | igual L01 | WITHDRAWN | G residual integrity | No reactivar | Static |
| L03 | IR | Regularizar servicio | LEGACY — SUPERSEDED | regularizeOrder_entryPurchase | type service | regularizar/automaticamente/entrada/servicio/{id} | WITHDRAWN | OrderService fuera de alcance | No reactivar | Static |
| L04 | IS | Regularizar servicio | LEGACY — SUPERSEDED | igual L03 | igual L03 | igual L03 | WITHDRAWN | OrderService fuera de alcance | No reactivar | Static |
| L05 | IR | Rama OrderService: editar/anular/detalle/documento | LEGACY — SUPERSEDED | permisos orderService históricos | item.code != null | endpoints OrderService/getServiceById | WITHDRAWN | DTO actual sólo financeEntries | No reactivar mezcla de dominios | Static |
| L06 | IS | Rama OrderService: editar/anular/detalle/documento | LEGACY — SUPERSEDED | igual L05 | igual L05 | igual L05 | WITHDRAWN | DTO actual sólo financeEntries | No reactivar | Static |
| H01 | ES | Templates de líneas/mutadores sin trigger operativo | HISTORICAL CODE — NOT REACHABLE | No nuevo permiso inferido | template/modal sin botón externo; sin submit visible | JS edit_entry_purchase residual | No alcanzable | No es pérdida confirmada | No activar edición desde show | Git markup/handlers |
| H02 | ID | Guardar/editar/líneas residuales | HISTORICAL CODE — NOT REACHABLE | No nuevo permiso inferido | btn-submit y script edit_invoice comentados en Blade | No handler cargado | No alcanzable | No es pérdida confirmada | No activar desde show | Git comentarios Blade |

No se contabiliza como nueva pérdida un export de Entry Report: no había trigger export alcanzable. El handler cancelEntry y modalDelete residuales tampoco tenían botón de acción en sus filas. No se añade Column Visibility: estas superficies no tenían columnas configurables. No se añaden regularizaciones ni callbacks de OrderService.

## Contratos, permisos y blockers revalidados

- Entry list/report: T/C, entry_type Por compra, finance=false. Fechas del reporte d/m/Y sobre created_at; tabla muestra date_entry. Se preservan POST y Audit técnico. Invoice GET/fetch también registra Audit; para QA se cancela sólo el evento creating de Audit en el proceso aislado y se usa transacción READ ONLY, no se altera producción.
- Entry create/edit: StockItem::with(inventoryLevels) preload sin company y sin permission en ruta; no se confunde resolver de almacenes ya contextual con selector de productos resuelto. Edit filtra company y TenantScope pero no finance/tipo. Warehouse resolver limita empresa/branch/general, location y default; no se inventa warehouse1. Permanecen INV-01/paqueteC compartidos con Normal.
- Recepción: OrderPurchase ScopedPurchaseCompany + type=n, NormalPurchaseReceiptSummary + EntryWarehouseResolver. GET crea UUID en memoria, no reserva stock ni escribe negocio. can_receive del DTO gobierna acceso visible. El Store mantiene locks, clave/fingerprint y contexto; no se ejecutó.
- Entry ítems: ItemController → LegacyItemReadAccess::entry valida permisos por tipo, T/C y existencia; LegacyItemReadQuery contextualiza detalles, StockItem/company association, ítems/warehouse/location. 403/404 no se convierten en vacío. El modal preserva detalles e ítems, incluye largos/ancho/peso/precio/estado; no muestra imágenes públicas.
- Invoice: financeEntries T/C + finance=true; supplier y UnitMeasure tenant-scoped, categorías forCurrentCompany, FinanceInvoiceContext conserva sesiones propias/cajas elegibles/moneda. No hay nuevos pagos, SupplierCredit ni reglas fiscales. Edit GET anulada puede renderizar, Update la rechaza; no se ofrece editar/anular anuladas desde el reporte. Create/Edit existentes intactos.
- Invoice anulaciones: mismo POST vacío con CSRF que legacy. Controller bloquea Entry, exige movimiento único contextual/no revertido y payment/sesión elegible, revierte caja transaccionalmente. No se deduce elegibilidad financiera completa desde JS. Errores backend visibles, modal cancelar no envía POST.
- **ENTRY-R4-01:** cadena de eliminación Entry sin finance=false explícito; no restaurada FR-048. No se ejecutó explotación ni eliminación. Evidencia y aceptación en backlog DEV. No confundir estado de QA con este defecto estático concreto.
- **FIN-03 existente:** getInvoiceById sin permiso/finance filter; no reintroducido. Detalle rápido Invoice usa el DTO scoped ya recibido. Esto preserva la capacidad sin consumir un auxiliar inseguro ni abrir servicios. No se afirma cerrar el endpoint.
- Export Invoice usa FinanceInvoiceExportRead (T/C, finance, fechas validadas). Mismo contrato start/end; **no filtra sin-orden ni búsqueda DataTables**: se explica en UI sin inventar parámetros. No ejecutado; navegación interceptada. Audit de export pendiente de QA real.
- Totales IR recuperados: PEN y USD por separado, equivalente PEN por total × currency_venta snapshot, filtro DataTables aplicado, como footer original. No tipo de cambio actual ni nuevos datos. Snapshot/moneda inválida muestra equivalente no disponible; no inventa tasa. Totales conservan anuladas según dataset original del reporte, no se cambia su semántica.
- DOC-01 sigue OPEN: /images/entries es ruta pública; no restaurar PDF/imágenes/extra-docs sin autorización documental por artefacto. Sin exports/archivos/proveedores reales. Prints Normal recuperados sólo como navegación protegida; QA documental independiente.

## Dataset real y límites de QA

PDO conexión dedicada con START TRANSACTION READ ONLY + ROLLBACK, sin datos creados, sin mutaciones ni Audit persistido. No es una certificación HTTP con login: se ejecutan middleware/contexto, actions y render reales en proceso aislado; Chrome usa esas respuestas capturadas y mocks de comandos. QA previo de otros módulos no se repite.

| Contexto | Entry Por compra no financiera (todos, reporte) | Invoice finance | Normal para recibir |
|---|---|---|---|
| EDESCE T1/C1/B1 | 54,53,44,43,42,41,40,39,37,35,25,24,22,21,20,19,18,17,16,15,14,13,12,11,10,9,8,7,6,5,4 (31) | 55,38,36,30,29 (5);30 anulada; activas55,38,36,29 | 4,3,2;4 can_receive=false;3/2 true |
| EDESCE T1/C2/B2 | 52,49 (2) | [] | [] |
| Gamarra T3/C3/B4 | 45 (1) | [] | [] |

Ítems: C1 resuelve54/53, C2 resuelve49, Gamarra45. Cruzados e inexistente999999 dan404. Entry1 ya no resuelve como activa: no se reutilizó ese ID histórico. Edit Invoice29/30 renderizan en C1; otros contextos/financefalse45/49/inexistente404. Recibir2/3/4 renderiza C1; mismos IDs C2/Gamarra404. No se envió Store/Update ni se abrieron documentos. Los permisos por fila se prueban además con fixture simulado sin permisos; no Owner bypass.

## QA visual / wiring

Chrome headless aislado, sin controlar navegador/escritorio del usuario. 7 listas ×8 anchos (320/375/390/576/768/992/1200/1440) =56 checks sin overflow global. Dos patrones de detalle ×8 anchos =16; tablas con scroll local. Menú único, iconos Font Awesome + next-row-action-item-icon, alineación y destructive text-danger reutilizados. DataTables/paginación existentes intactos.

2 POST de anulación **simulados** (200/422), URL/CSRF correctos; errores visibles; ninguna petición real enviada al backend. 403 simulado de ítems visible, no empty state falso. 2 exports interceptados y cancelar sin navegar. Receipt de orden3, show e impresión URL exacta verificados, sin ejecutar impresión. Permisos vacíos sin acciones, can_receive=false sin recepción, anulada sin edit/delete. Totales reales490PEN y fixture explícitamente simulado USD10 ×3.75 +PEN100 =137.50; búsqueda simulada conserva37.50. No representa persistencia ni dataset real USD.

Sin errores JS en replay. Blade cache PASS y node --check en los5 JS intervenidos. Las pruebas de navegador son evidencia de interfaz/cableado, no certificación de las mutaciones backend. No se agregaron dependencias. No se ejecutaron suites ajenas que escriban en DB compartida.

## Evidencia antes/después

Artefactos fuera del repo: C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/.

- r4-read.php + r4-data.json: dataset actual, contextos, DTOs y render; Audit cancelado en proceso, READ ONLY.
- r4-before-*.blade.php/html/js: baseline git show HEAD con datos actuales, sin modificar temporalmente fuentes del repo.
- r4-before-browser.cjs:7 capturas baseline; r4-before-{entry,orders,report,invoice,annulled,finance,noorder}-1440.png.
- r4-browser.cjs + r4-browser-results.json:56 checks,16 modales, mocks200/422/403, payloads, permisos y exports interceptados.
- r4-extra.cjs: totales/filtrado simulado, enlaces Normal3, confirmación anulación Entry ausente, capturas finales de modal sin toast transitorio.
- r4-{surface}-{375,1440}.png:14 capturas posteriores; r4-{report,invoice}-modal-{375,1440}.png:4 modales finales.
- r4-capability-rows.json / r4-canonical-counts.json: IDs/recuento reproducible; no credenciales.

## Cobertura previa y nuevas pérdidas

FR046…058 estaban identificadas pero excluidas por QA read-only. Se reauditaron las13. FR081…099 no estaban contabilizadas en el registro individual: vistas read auditadas, capacidades operativas sólo estáticas/incompletas o nunca inventariadas individualmente (especialmente menús de reportes, anuladas y totales). No se afirma que se hubieran probado mutaciones antes. Sólo ENTRY-R4-01 es finding DEV nuevo; FIN-03/DOC-01/INV-01 se reutilizan. Los faltantes frontend no se envían como bugs backend.

## Archivos y estado final

- Frontend: public/js/entry/{index_entry_purchaseV2.js,listOrdersV2.js,index_entry_purchase_report.js,list-operations.js}; public/js/invoice/finance-readonly.js.
- Blade: resources/views/entry/{index_entry_purchasev2.blade.php,listOrderPurchaseV2.blade.php,index_entry_purchase_report.blade.php,partials/list-operations.blade.php}; resources/views/invoice/{index_invoice.blade.php,index_invoice_annulled.blade.php,report_invoice.blade.php,report_invoice_sin_orden.blade.php}.
- Documentación: este informe, AGENTS.md, backlog canónico, R3.1,R3.2, backlog DEV e informe DEV paralelo.
- Backend/controller/routes/DB/CSS/allowlist: **UNCHANGED**. No commit, push ni merge. Trabajo previo preservado.
- ACTION ICON PARITY: PASS. FUNCTIONAL PARITY: PARTIAL (21 recuperadas /11 bloqueadas de32 pérdidas activas R4).
- MUTATION QA: PENDING; no recepción, anulaciones, creación, restauración, regularización, pagos ni cambios de stock reales. No documentos/proveedores.
- PRODUCT DECISIONS REQUIRED: ninguna ambigüedad de vigencia impide continuar lo seguro; revisar R4 antes de R5.
- GIT DIFF CHECK: PASS al cierre.
