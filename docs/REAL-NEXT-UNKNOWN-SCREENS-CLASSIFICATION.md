# REAL NEXT — UNKNOWN SCREENS CLASSIFICATION

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
