# REAL NEXT — UNKNOWN SCREENS CLASSIFICATION

> Estado vigente: **UNKNOWN REMAINING: 0**. Ver **Final 13 Resolution** al final.
> Las tablas iniciales son el snapshot histórico del discovery, no la cola actual.

2026-11-18. Discovery exclusivamente sobre las86 vistas UNKNOWN del readiness anterior. No reauditoría de las135 WAIT, ni frontend/backend, navegador, tests, exports, cálculos o mutaciones. Platform Shipping Catalogs y Company Product Catalog ya cerrados, fuera del universo.

## Family Classification

Conteos de **vistas**, no candidatos. Safe suma READY FOR FRONTEND, READY FOR READ-ONLY FRONTEND y FRONTEND WITH TECH DEBT. UNKNOWN es explícito: no se fuerza una decisión por encontrar una ruta.

| Family | Unknown Views | New Useful Surfaces / evidencia corta | Safe | Blocked | Legacy/Skip | Unknown restante |
|---|---:|---|---:|---:|---:|---:|
| Entry |10|Reporte de entradas por compra; forms de entrada distintos de EntriesV2. Retacería/documentos/regularización no son read path seguro|1|5|2|2|
| OrderPurchase |11|Forms normales y auxiliares; no confundir scope implícito con globalidad. Express usa User::all; reporte data sin permiso específico|0|3|0|8|
| Invoice |2|Crear/editar factura de compra financiera, distintos del listado integrado|2|0|0|0|
| Quote |19|Cotización industrial, no QuoteSale. Company ausente en queries/lookup User global; edit guarda equipos durante GET|0|19|0|0|
| OrderExecution |3|Órdenes ejecución derivadas de Quote: queries raise_status/state sin company, binding por quote|0|3|0|0|
| Report |1|report.reportQuote: quoteSummaryReport consulta Quote confirmado sin company|0|1|0|0|
| Timeline |7|Cronograma de trabajos/cotizaciones, actividades y personal; Timeline global, find y workers sin company; supervisores hardcodeados|0|7|0|0|
| Worker |4|Directorio/alta/edición contextual; habilitar devuelve have_contract aleatorio|3|1|0|0|
| Assistance |8|Asistencia y totales laborales. Worker tenant-only sin WorkerAccess/company; Assistance find global|0|8|0|0|
| Gratification |2|Periodos/gratificaciones personales; GratiPeriod find y workers sin company|0|2|0|0|
| FifthCategory |2|Retención laboral: Worker find y FifthCategory por worker sin guard company|0|2|0|0|
| Alimony |2|Pensiones alimenticias personales: binding Worker y Alimony sin ownership company|0|2|0|0|
| Boleta |4|Boletas de pago, no comprobantes de venta: PaySlip find/Worker find sin WorkerAccess|0|4|0|0|
| PersonalPayment |1|Nómina mensual; workers sin company y cálculo incluye SueldoMensual::create. No se ejecutó|0|1|0|0|
| FinanceWork |1|Financiación de trabajos: FinanceWork::all operativo sin scope contextual|0|1|0|0|
| PuntoVenta |1|Bandeja ventas con error SUNAT, read model moderno protegido|1|0|0|0|
| Combo |2|Combos de materiales, con precio/descuento; Combo sin scope y consultas globales. Menú vigente|0|2|0|0|
| CashRegister |1|Pantalla por tipo de caja legacy; camino moderno de sesión propia ya integrado|0|0|1|0|
| Face |2|Carga/verificación de imágenes de rostros. Sin consumer de menú/flujo activo encontrado; Face::all global|0|0|2|0|
| QuoteSale |3|Create/edit/registrarComprobante, distintas de la façade de listados; contrato auxiliar pendiente de corte corto|0|0|0|3|
| **TOTAL** |**86**| |**7**|**61**|**5**|**13**|

UNKNOWN reducido de86 a13 (73 clasificadas). No significa que los61 bloqueos sean61 root causes nuevos. SKIP no autoriza borrar archivos.

## Newly Ready

### 1. Worker — batch de tres vistas — READY FOR FRONTEND

`worker.index`, `worker.create`, `worker.edit`; rutas /dashboard/colaboradores, /dashboard/registrar/colaborador, /dashboard/editar/colaborador/{id}. Navegación appAdmin2: listado/alta; editar desde listado. Permisos list_worker/create_worker/edit_worker; getWorkers protegido también desde constructor.

WorkerController aplica WorkerCompanyContext a todas sus acciones. WorkerAccess verifica usuario habilitado, tenant, company activa, memberships company/branch activos; scope workers tenant+company. Branch autoriza acceso al contexto, no filtra los trabajadores por sucursal. GETs index/create/edit no cambian negocio. getWorkers también usa WorkerAccess; contratos se buscan sólo para workers ya scoped.

**Binding:** edit usa find internamente, pero WorkerCompanyContext ejecuta worker(id) con404 antes del controller. No clasificar inseguro sólo por leer ese find aislado. Lookups laborales son los catálogos básicos ya aprobados; WorkerUserLink limita usuarios a tenant/company, enabled y membership, excluye vínculos ya usados. No confundir Worker con Workforce ni ConfigUserWeb.

Dataset READ ONLY: T1/C1 activos3/inactivo1; T1/C2 activo1; Gamarra sin filas. Total5 workers no eliminados. Dataset vacío no bloquea. Integrar estos tres forms/listado sin ejecutar store/update/destroy, fin de contrato ni exports. No incluir worker.indexEnable hasta aclarar campo aleatorio.

### 2. Ventas con error — READY FOR READ-ONLY FRONTEND

`puntoVenta.listError`, /dashboard/listado/ventas/error; menú appAdmin2 vigente. GET data /dashboard/get/data/sales/error/{page}. SaleListGuard:errors y SaleListAccess requieren list_puntoVenta + listErrors_puntoVenta, WorkerAccess/contexto, snapshot de data, tenant/company/branch y actor según viewBranch_puntoVenta. Options data(true) reutiliza lookups contextuales. getSalesErrorAdmin usa visible(...,true), state_annulled0, type_document01/03, sunat_status Error, discarded_at null; sin llamada externa ni escritura en lectura.

Dataset rápido:6 cabeceras con esos predicados en T1/C1/B1 **antes del filtro de actor/permiso de sesión**; no afirmar que cada usuario verá6. Es una bandeja nueva, no repetir Sales List. JS legacy tiene emisión/descarte/recuperación: integración sólo read path y presentación de acciones según contratos existentes; cero ejecución financiera, documentos o proveedor. DOC-01 continúa separado.

### 3. Finance Invoice Create/Edit — READY FOR FRONTEND

`invoice.create_invoice`, `invoice.edit_invoice`; rutas invoice.create (/dashboard/crear/factura/compra) e invoice.edit (/dashboard/factura/compra/editar/{entry}), permisos create_invoice/update_invoice. Alta en menú; edición enlazada desde flujo de facturas. No reabrir sus índices/reportes.

GET create usa Supplier tenant-scoped, UnitMeasure global no sensible, CategoryInvoice::forCurrentCompany, FinanceInvoiceContext para cajas/subtipos/sesión propia. Edit revalida binding Entry con financeEntries()->findOrFail: tenant+company+finance=true. GETs no escriben. Las llamadas legacy /get/materials y /get/locations del arranque de invoice.js están **comentadas**, no contarlas como requests activos. Edit usa líneas existentes. Dataset rápido finance=true:5 cabeceras T1/C1 (sin certificar elegibilidad de cada una para edición). Integración visual con contrato actual, sin guardar, pagos/anulación ni tipo de cambio externo.

### 4. Reporte entradas por compra — FRONTEND WITH TECH DEBT

`entry.index_entry_purchase_report`, route entry.purchase.report.index, menú existente. Vista y getJsonEntriesPurchaseReport exigen list_entryPurchase y tenant/company presentes; query filtra ambos, entry_type Por compra, finance=false y rango validado d/m/Y. Sin binding de entidad en consulta principal. Dataset base sin rango: C1=35, C2=2, C3=1.

**Deuda explícita:** el fetch de datos es POST de consulta y escribe Audit::create técnico; no modifica entradas/stock. No se ejecutó en discovery, ni se certifica como GET puro. Integración futura limitada al reporte de lectura; excluir borrar, documentos y exports del alcance de QA. No se propone el reporte legacy por material. Si la siguiente fase exige cero escrituras incluso en Audit, usar sólo render/respuestas controladas o dejar ese smoke pendiente; no quitar auditoría desde frontend.

## Best Batch

**Worker index + create + edit**, tres superficies del mismo dominio, WorkerCompanyContext/WorkerAccess y permisos coherentes. No añadir habilitar para inflar el batch. No agrupar Invoice/Entry por similitud visual.

## Newly Wait for Dev

- Quote19: QUOTE-01 del review post-merge cerró opciones creator de QuoteSale index/indexFacturadas. No corrigió QuoteController industrial: User::select global en indexV2/index2V2/raiseV2, queries Quote sólo tenant y edit GET que guarda equipos/consumibles/cotización. No extrapolar cierre.
- Timeline7, OrderExecution3 y Report1: datos de proyectos/cotizaciones sin company/guard equivalente. Son útiles pero no seguros; no decir legacy sólo por antigüedad.
- RRHH personal: Assistance8, Gratification2, FifthCategory2, Alimony2, Boleta4, PersonalPayment1. Worker ahora tiene tenant scope, **eso no equivale a company scope**: esos controllers no usan WorkerAccess. No calcular nómina ni llamar endpoints.
- FinanceWork1: catálogo operativo global de trabajos financieros. Combo2: combos/precios reales globales, con menú; no catálogo inocuo ni SKU moderno.
- Entry5: entry.create_entry_scrap, entry.index_entry_scrap, entry.editImages, orderPurchase.regularizeEntryPurchase, entry.reportMaterialEntries. Retacería/parents, documentos o User::all y detalle por material sin comprobación company suficiente; no rescatar esos caminos.
- OrderPurchase3: createExpress/editExpress conservan User::all y Quote sin company. report_orders_by_material_v2 tiene scope purchase_company en el modelo, pero el GET de datos getReportOrderPurchaseByMaterial carece del permiso report_orderPurchaseExpress que sí exige la vista; sólo auth/context general no reemplaza capacidad. No certificar el reporte por proteger su menú. No se investigó export.
- Worker1: getWorkersEnable devuelve `have_contract => rand(0,1)` (WorkerController alrededor de línea630). Root de respuesta no determinista; no inventar estado en JS. worker.indexEnable queda WAIT, aunque su scope sí es correcto. No cambio backend.

## Legacy / Replaced

**5 SKIP / LEGACY:** entry.listOrderPurchase (OrdersV2 ya integrado); cashRegister.index por type (sesión moderna propia ya integrada); faces.index/verify (registro/verificación de imágenes facial, sin consumer ERP vigente encontrado; Face::all además sería inseguro si se reactiva); entry.create_entry_purchase_order **SKIP por límite de esta fase**, recepción/posting con stock, no afirmación de obsolescencia. No tocar ni eliminar. Esta última se distingue de legacy técnicamente.

## Remaining Unknown

**13**, con pregunta concreta antes de promover:

- Entry2: create_entry_purchaseV2/edit_entry_purchaseV2. Cabecera contextual/warehouse resolver moderno; falta corte corto de lookups y requests activos del editor de líneas, sin entrar a posting/stock. No declarar WAIT por Supplier::all: Supplier tiene tenant scope.
- OrderPurchase8: createNormalV2/editNormalV2, deleteGeneral/showDelete, indexLost/indexRegularize, showExpress/showOperator. El modelo usa ScopedPurchaseCompany **fail-closed**; no clasificarlos globales por Model::all/find. Falta confirmar permiso de cada data consumer y distinguir recuperación/regularización/documento del read path. Normal forms muestran señales favorables (NormalPurchaseGuard::users, Quote company y PaymentDeadline selectable), pero no se completó el enlace de todos sus lookups activos. No son los índices V2 ya cerrados.
- QuoteSale3: create/edit/registrarComprobante. Company binding, Customer tenant y cajas contextuales ya visibles; falta confirmar lookup de productos/presentaciones y permisos de entrada de esos forms sin reauditar POS ni su reserva/facturación. QUOTE-01 de listados no basta. No ejecutar ni sugerir facturación como read-only.

## Recommended Frontend Order

| # | Candidate | Views | State | Límite |
|---|---|---:|---|---|
|1|Worker: directorio + alta + edición|3|READY FOR FRONTEND|Sin habilitar, fin de contrato ni mutaciones|
|2|Ventas con error SUNAT|1|READY FOR READ-ONLY FRONTEND|Sin documentos, emisión, recuperación, descarte o anulación|
|3|Finance Invoice formularios|2|READY FOR FRONTEND|Sin guardar ni operaciones financieras|
|4|Reporte entradas por compra|1|FRONTEND WITH TECH DEBT|Audit técnico en fetch; no export/delete/documentos|

READY agregado7 =5 READY FOR FRONTEND +1 READY FOR READ-ONLY FRONTEND +1 FRONTEND WITH TECH DEBT. WAIT61 + SKIP5 + UNKNOWN13 =86. Cuatro candidatos, no siete fases. No QA funcional ni prueba de seguridad realizada: conclusiones de discovery estático, no PASS runtime.

Reproducción: conjunto exacto de vistas del snapshot remaining-views del readiness anterior, filtrado sólo a las20 familias indicadas; exclusión de los cuatro índices legacy ya contabilizados previamente. Cruce con rutas/controller/JS y menú appAdmin2. Conteos candidatos vía SELECT agregados DB en transacción READ ONLY + ROLLBACK, sin controllers HTTP; ningún dato personal en este documento. No se regeneró inventario completo ni se revisaron las135 WAIT.

## Final 13 Resolution

2026-11-18. Clasificación final estática de las 13 vistas pendientes, no integración.
Sólo se modificó este documento en esta fase. Se preservaron los cambios previos
del reporte de entradas. No controllers HTTP, navegador, screenshots, responsive,
exports, documentos, llamadas externas ni mutaciones. Dataset: SELECT agregado
con conexión en transacción READ ONLY + ROLLBACK, sólo para candidatos viables.
READY significa candidato seguro de presentación, no certificación de mutaciones.

### Decisión individual

| Family | View | State | Blocker / Evidence | Frontend action |
|---|---|---|---|---|
| Entry | create_entry_purchaseV2 | WAIT FOR DEV | GET cabecera seguro, EntryWarehouseResolver contextual; JS carga activamente get/materials/stock/items/entry, tenant-only y stock agregado sin company; AJAX sin capacidad específica | Esperar cierre del lookup compartido y permisos; no retirar silenciosamente el request para promover |
| Entry | edit_entry_purchaseV2 | WAIT FOR DEV | Mismo lookup activo. editEntryPurchase limita tenant/company pero no finance=false ni entry_type; destino actual no se valida contra resolver en este GET | Separar de Create; DEV debe definir binding de entrada editable además del lookup |
| OrderPurchase | createNormalV2 | WAIT FOR DEV | Guard users/Quote/deadline correctos; precarga activa del mismo lookup legacy; búsquedas AJAX sin permiso de operación | No basta con el GET seguro. Esperar corrección de dependencias activas |
| OrderPurchase | editNormalV2 | WAIT FOR DEV | Parent type=n y ScopedPurchaseCompany correctos; líneas contextualizadas. Precarga legacy activa y permiso AJAX pendiente | No certificar por el binding del parent; Save/recepciones fuera |
| OrderPurchase | deleteGeneral | READY FOR READ-ONLY FRONTEND | Papelera normal: onlyTrashed + type=n + ScopedPurchaseCompany; vista y fetch destroy_orderPurchaseNormal; fetch sólo Audit técnico | Integrar listado de eliminadas, separando restore/delete/estado/documentos |
| OrderPurchase | showDelete | READY FOR READ-ONLY FRONTEND | onlyTrashed + type=n + findOrFail; detalles withTrashed vinculados al parent y scoped; mismo permiso; GET puro | Integrar detalle histórico sin acciones de escritura/documentos |
| OrderPurchase | indexLost | WAIT FOR DEV | GET vista puro; datos contextuales withTrashed pero /all/order/purchase/lost sin capacidad específica. Códigos faltantes inferidos de secuencia, sin ORDER BY explícito | DEV debe cerrar permiso del fetch; no ocultar botones para certificar |
| OrderPurchase | indexRegularize | WAIT FOR DEV | GET no regulariza; lee regularize=r contextual y Audit. /all/order/purchase/regularize carece de permiso específico | Esperar autorización del fetch; regularización/estado/delete siguen fuera |
| OrderPurchase | showExpress | WAIT FOR DEV | showOrderPurchaseExpress ejecuta User::all; parent scoped pero find sin fail y sin type=e explícito | No extrapolar Express V2 list; corregir contrato GET/lookup antes de integrar |
| OrderPurchase | showOperator | WAIT FOR DEV | User::all y binding por code con first; ruta sin capacidad específica, sin guard adicional en controller | Mantener WAIT; no declarar legacy/skip sólo por antigüedad |
| QuoteSale | create | WAIT FOR DEV | Productos/presentaciones contextuales, pero cambio de cliente llama /get/contact/{customer} sin capacidad específica; sólo auth/context y tenant scope | DEV debe proteger lookup activo con capacidad compatible con consumidores |
| QuoteSale | edit | WAIT FOR DEV | Binding company+branch correcto; contacto activo sin permiso; productos/totales exigen create_quoteSale aunque formulario exige edit_quoteSale | Alinear capacidades de edición y contactos; reservas/Save no certificados |
| QuoteSale | registrarComprobante | WAIT FOR DEV | GET principal sin negocio mutable, cajas contextuales; buscar/getDataIndividual activos no exigen edit_quoteSale ni otra capacidad de negocio; validación contextual no reemplaza permiso | No integrar flujo de conversión para completar familia; dejar venta/Nubefact/documentos fuera |

### Evidencia del bloqueo compartido Entry / Normal Purchase

- `public/js/entry/entry_purchaseV2.js:19`, `edit_entry_purchaseV2.js:19`,
  `orderPurchase/createNormalV2.js:28`, `editNormalV2.js:31`: precarga GET en ready,
  NO comentada, hacia `/dashboard/get/materials/stock/items/entry`.
- `MaterialController::getJsonMaterialStockItemsEntry` (4803): StockItem activo
  con `inventoryLevels`, sin enabledForCompany ni predicado company. StockItem e
  InventoryLevel tienen BelongsToTenant, no company global scope. `stock_current`
  suma niveles cargados. No se describen como globales entre tenants: el hueco es
  company dentro del tenant. La ruta (web.php:1098) no tiene permiso específico;
  MaterialController no añade middleware de capacidades en constructor.
- Los buscadores nuevos son distintos: `materials.entry.search`
  (getJsonMaterialsForEntry, 4864) y `materials.order.purchase.search`
  (getJsonMaterialsForOrderPurchase, 5099) usan company/pivot activo/niveles
  company. Sus rutas (3708–3712) tampoco añaden capacidad. Mejorar el buscador no
  vuelve segura la precarga que sigue corriendo. No se llamó ningún endpoint.
- `/get/materials/entry` de Normal está comentado; `/get/locations` de Entry
  también está comentado. No se usan como findings ni se cuentan como activos.
- `cargarLocations()` usa JSON `entry-warehouse-catalog` generado por
  EntryWarehouseResolver: tenant/company, warehouse general o branch actual,
  location de la misma company/warehouse y errores de configuración explícitos.
  No crea almacenes ni ubicaciones. No existe lookup global activo de ubicación.
- Units/tipo de retacería llegan en producto; no se encontró un lookup independiente
  de impuestos en esos editores. No se añaden endpoints por asumir que deberían existir.
- `EntryController::editEntryPurchase` (2338) usa Entry tenant-scoped y company,
  firstOrFail; no restringe finance ni tipo. Lectura cabecera sin escrituras; no
  certifica que un ID financiero corresponda a este editor de entradas físicas.
- StoreEntryPurchaseRequest y UpdateEntryPurchaseRequest autorizan true y validan
  factura/fecha/entry_type, proveedor exists:suppliers,id; Store exige items. Los
  requests no certifican por sí mismos ownership. Se revisaron estáticamente,
  sin store/update ni intentar auditar todo el posting.
- Editor Entry agrega líneas/variantes en memoria y tiene handlers persistentes
  add/destroy/update para interacción explícita; no se ejecutaron. Resolver
  moderno no implica certificación de stock, costeo, lotes, attachments o recepción.

### Normal Purchase y lectura histórica

`OrderPurchase` y `OrderPurchaseDetail` usan ScopedPurchaseCompany, fail-closed si
falta contexto: tenant+company. No se etiquetan globales por usar all/find.
CreateNormal consulta Supplier tenant, NormalPurchaseGuard::users (tenant+membership
company activo), Quote company y PaymentDeadline purchases selectable. El GET escribe
únicamente Audit técnico. EditNormal exige type=n, parent scoped y findOrFail;
líneas referencian ese parent. Deadline permite conservar el actual inactivo.
StoreOrderPurchaseRequest es básico; validaciones contextuales adicionales están
en NormalPurchaseGuard. editable() rechaza recepciones existentes al mutar; GET
puede renderizar y eso no certifica Update. No se ejecutó editable ni guardado.

Papelera: `getOrderDeleteGeneral` (1929) consulta sólo normales soft-deleted,
con supplier/approved_user, y escribe Audit técnico. No restaura ni cambia estado.
`showOrderPurchaseDelete` (2157) usa parent onlyTrashed type=n y líneas withTrashed.
JS showNormal arranca con fillItems local DOM; no lookup AJAX de arranque.
Acciones de edición/restore/documentos presentes en código compartido se separarán
al integrar la lectura, no se certifican ni ejecutan. No se borra el flujo original.
Estos dos candidatos no requieren resolver una mutación para leer la papelera.

`indexRegularize` muestra órdenes YA regularizadas (regularize=r). No ejecuta
regularización en GET. `indexLost` infiere huecos de códigos de órdenes scoped,
no representa una papelera ni prueba pérdida de registros. Ambos fetches sólo
escriben Audit; el bloqueo comprobado es autorización, no un efecto de negocio
inventado. El algoritmo de huecos sin orden explícito queda señalado a DEV.
ShowExpress/Operator: User no tiene TenantScope; User::all real en el GET. Aunque
el Blade no necesariamente exponga toda esa colección, la query global sigue en
el contrato inspeccionado. No afirmar una fuga visual demostrada sin ejecutar QA.

### QuoteSale: qué está bien y qué falta

- QuoteSaleController añade QuoteSaleBranchContext. Create/Edit validan memberships
  tenant/company/branch mediante QuoteBranchCatalog. Edit exige assertQuote: ID
  contextual, misma branch y rechaza histórico sin branch para edición; además
  controller where company + firstOrFail. No confundir con QuoteController industrial.
- Customer/ContactName son tenant-level por contrato. El problema del contacto NO
  es ausencia de company en un catálogo legítimamente tenant-level, sino ausencia
  de capacidad en el endpoint activo. `QuoteController::getContactsByCustomer`
  (3608), web.php:1416: sólo filtra customer_id bajo TenantScope, sin can/middleware
  específico. Create lo llama al cambiar cliente; Edit lo llama desde getContacts()
  en ready y al cambiar cliente (edit.js:2455+, 2744).
- `getMaterials` (228) y `getMaterialTotals` (348) usan QuoteBranchCatalog,
  disponibilidad del local y niveles company; presentaciones usan
  MaterialPresentationController::catalog con OR explícito de permisos de catálogo,
  QuoteSale create/edit y POS, más BranchPresentationService. No se etiqueta ese
  catálogo como abierto por no tener middleware de ruta: el controller SÍ autoriza.
- Edit JS (2467/2479) usa los endpoints modernos; ambos requieren create_quoteSale
  en rutas, distinto de edit_quoteSale del formulario. No asumir Owner bypass.
  El Select2 de consumibles inline legacy es reinicializado por edit.js: no se
  toma el endpoint industrial reemplazado como blocker activo adicional.
- Servicios/unitMeasures, PaymentDeadline selectable, TypeTaxResolver y SettingService
  existentes preservados. Create/Edit GET generan Audit técnico, no reservas ni
  cambios de negocio en los caminos revisados. Edit carga stockItem/presentation y
  quoteStockLots de líneas vinculadas al quote; no ejecuta reserva ni reconciliación.
  No se certifica correspondencia histórica de Items físicos ni moneda al guardar.
- `showRegistrarComprobante` (7921) valida tipo Boleta/Factura/Ticket; cajas/subtipos
  tenant/company y caja general/local; GET sin emisión. `buscar` (8074) es local,
  confirmed y sin venta activa; `getDataIndividual` (8137) tiene binding company y
  middleware assertQuote. Pero rutas web.php:3429–3430 no exigen capacidad, y esos
  guards validan contexto, no edit_quoteSale. Son lecturas activas de su buscador y
  selección. Éste es un blocker concreto, no una clasificación sólo por cercanía a
  facturación. POST storeFromQuote, Decolecta, documentos y Nubefact NO ejecutados.

### View / Permission / Active lookup permissions

Todos heredan auth/context del grupo dashboard; eso no reemplaza capacidad.

| View | Permission de vista | Active lookup permissions |
|---|---|---|
| Entry create V2 | create_entryPurchase | precarga y materials.entry.search: sin capacidad específica |
| Entry edit V2 | update_entryPurchase | igual; warehouse embebido protegido por GET |
| createNormalV2 | create_orderPurchaseNormal | precarga y materials.order.purchase.search: sin capacidad específica |
| editNormalV2 | update_orderPurchaseNormal | igual |
| deleteGeneral | destroy_orderPurchaseNormal | /all/order/delete: mismo permiso |
| showDelete | destroy_orderPurchaseNormal | lecturas servidor bajo binding; sin AJAX read de arranque |
| indexLost | list_orderPurchaseNormal | /all/order/purchase/lost: sin capacidad específica |
| indexRegularize | list_orderPurchaseNormal | /all/order/purchase/regularize: sin capacidad específica |
| showExpress | list_orderPurchaseExpress | User::all en GET, sin scope; no resolver con permiso de vista |
| showOperator | sin capacidad específica | sin guard adicional; User::all en GET |
| QuoteSale create | create_quoteSale | productos/totales create_quoteSale; presentaciones OR autorizado; contactos sin capacidad |
| QuoteSale edit | edit_quoteSale | productos/totales create_quoteSale (mismatch); presentaciones admite edit; contactos sin capacidad |
| registrarComprobante | edit_quoteSale | buscar/getDataIndividual sin capacidad específica; scope contextual no suple permiso |

### Dataset y límite de evidencia

Sólo candidatos viables: order_purchases type=n, deleted_at NOT NULL.
SELECT agrupado: T1/C1=1; T1/C2=0; T3/C3=0. La misma fila permite papelera y detalle.
Dataset vacío en otros contextos no bloquea integración. No fixtures ni cambios DB.
No se renderizaron GET con Audit ni se ejecutaron requests de consulta. Las
conclusiones son de código/rutas/modelos/JS, NO equivalen a PASS de QA runtime.
Evidencia temporal de conteo: %TEMP%/unknown13-count.php, conexión READ ONLY.

### Medición actualizada / siguiente implementación

De las 13: **2 READY FOR READ-ONLY FRONTEND, 0 READY FOR FRONTEND,
11 WAIT FOR DEV, 0 SKIP / LEGACY, 0 UNKNOWN**.
Universo histórico de 86: 9 clasificadas útiles (7 previas ya integradas + 2 nuevas),
72 WAIT, 5 SKIP, 0 UNKNOWN. No significa nueve vistas aún pendientes de frontend.
No se reauditaron las demás familias ni se reabrió Finance Invoice.

1. Próxima: OrderPurchase deleteGeneral, papelera normal read-only.
2. Segunda: OrderPurchase showDelete, detalle de orden normal eliminada.
3. Tercera: NONE en este conjunto hasta cambios DEV.
**BEST BATCH: Normal Purchase Trash — deleteGeneral + showDelete**.
Entry Forms y Normal Purchase Forms NO se fuerzan como batch; comparten el primer
bloqueo de catálogo pero Entry Edit tiene además contrato de binding pendiente.
QuoteSale Forms sigue WAIT por contactos/capacidades; registrarComprobante separado.

Cierre: sólo este documento modificado en esta fase; frontend/backend intactos,
cero pruebas extensas, cero mutaciones, sin commit. `git diff --check`: PASS.
