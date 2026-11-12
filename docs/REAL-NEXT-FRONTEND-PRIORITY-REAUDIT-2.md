# REAL NEXT — FRONTEND PRIORITY REAUDIT AFTER ROADMAP A PROGRESS

Fecha: 2026-11-12. Rama `feature/venti-next-frontend`, HEAD `a90d322`. Baseline: `REAL-NEXT-FRONTEND-PRIORITY-REAUDIT.md`, HEAD `3bffe60`, 2026-11-11. Auditoría diferencial; no sustituye el inventario anterior ni vuelve a enumerar sus 1390 rutas.

## Decisión ejecutiva

**Siguiente fase recomendada: Tenant Company / Empresas.** Su contrato de identidad y slugs ya tiene esquema disponible, hay empresas reales en ambos tenants y reutiliza el patrón de configuración aprobado. Integrar listado/create/edit, sin ejecutar guardados: crear empresa provisiona sucursal, membresías e inventario, no es una mutación inocua.

Después: Proveedores y Clientes, seguidos de Entries V2 limitado a listados. Mis movimientos/sesión propia también tienen una frontera de lectura útil. ConfigUserWeb es candidato independiente del UserController legacy.

- **PUBLIC CATALOG NEXT? NOT YET.** Esquema resuelto, dataset público no: 5/5 companies y 8/8 branches tienen slug NULL. No hay URL real positiva para QA anónimo. Persisten publicación, branding, paridad del detalle y SW parciales.
- **Daily Profit — recálculo controlado recomendado: YES**, como fase separada de cierre QA, con autorización de escritura y respaldo/clon. Propuesta concreta: `2026-11-04`, actualmente sólo contexto 1/1/1. No ejecutado.
- **POS V1/V2 — VISUAL BASELINE FROZEN.** No volver al core dentro de estas ocho fases. Evaluar al terminar el roadmap y sólo tras cumplir las puertas funcionales indicadas abajo.

## Método y límites de la evidencia

Lectura de AGENTS, baseline, documentos de las fases cerradas y código focalizado: controllers/modelos/requests, scopes, servicios, rutas y vistas públicas. Comparación Git `3bffe60..HEAD`. Consultas SQL agregadas y llamadas de lectura a servicios/actions mediante PHP 7.3.33, transacción `SET TRANSACTION READ ONLY`, `ROLLBACK` final; sesión array y Owners autorizados en memoria, contextos derivados de sus membresías activas.

No se hizo login HTTP, QA visual/responsive nuevo, prueba de mutaciones, emisión, consulta Nubefact/Decolecta, export, migración ni recálculo. Los conteos SQL incluyen históricos salvo indicación; no equivalen a filas visibles. Los permisos de rutas se inspeccionaron en código, no se certificó hoy toda la matriz de roles por navegador. Un GET puede escribir: no se recorrieron masivamente endpoints.

Los scripts auxiliares se ejecutaron fuera del repositorio, en TEMP. Único entregable del repositorio: este documento. No se cambiaron código, allowlist ni datos comerciales.

## Delta desde el baseline

`git diff --stat 3bffe60..HEAD -- app routes database` sin salida: **cero delta backend/rutas/migraciones de código**. No atribuir las mejoras de readiness a un paquete backend posterior inexistente. Hay 13 commits de frontend:

|Commit|Avance|
|---|---|
|64d6f89|Baseline visual POS V2|
|6de4406|StockItems/Items read path|
|58a7841|Company Settings/Branding|
|4c2b198|Platform Tax Types|
|d259220|Platform Nubefact|
|8349ba1|User Profit lectura|
|abc9daa|Normal Purchases lectura|
|1f972a7|Company Bank Accounts|
|651667f|Invoice Categories|
|bd413c6|Daily Profit lectura|
|fa8f7ed|Finance Invoices lectura/reportes|
|b3a670b|Payment Deadlines|
|a90d322|Warehouse Availability lectura|

Se conservan cerradas/congeladas las fachadas Material Create/Edit/List V2/Parameters, POS V1/V2, Cotizaciones, Sales List, Cajas/Subtipos, Transfers, Branch Pricing/Presentations y Platform Admin (Overview, Plans, Role Templates, Tenant Roles, Activity Log, Tenants, parámetros laborales, Tax Types, Nubefact). Los avances de la tabla salen del backlog de nuevas pantallas. «Integrado» no convierte mutation/export/document QA pendiente en aprobado.

### Blockers de esquema eliminados

Verificación nueva en tabla `migrations`: las cuatro entradas están en batch **166**; lectura efectiva de columnas contextuales/slugs sin error.

|Migración aplicada|Readiness que cambia|Lo que NO resuelve|
|---|---|---|
|2026_11_09_000001_add_context_to_daily_profit_summaries|Daily Profit puede leer/escribir el nuevo resumen contextual; fachada ya integrada|Sigue 1 legacy, 0 contextual; falta recálculo/QA real|
|2026_11_09_000002_add_public_catalog_slugs|Empresas/Branches pueden presentar el campo; resolver público ya tiene esquema|No rellena automáticamente slugs ni publica dataset|
|2026_11_10_000001_scope_invoice_categories_to_company|Categorías integradas; documento de cierre acredita 3/3 scoped, 0 ambiguas|Mutation QA y relación histórica de facturas no quedan certificadas|
|2026_11_10_000002_add_finance_tax_snapshot_to_detail_entries|Snapshot disponible en esquema; Finance lectura integrada|Los 5 detalles históricos siguen sin snapshot según cierre Finance; no backfill fiscal inventado|

## Dataset actual y alcance de QA

Conteos nuevos de sólo lectura, no transacciones de prueba:

|Familia|Resultado|Utilidad/límite|
|---|---|---|
|Companies / branches|5 empresas activas, 8 sucursales activas; todos los slugs NULL|Identidad/listados sí; publicación anónima positiva no|
|Customers|Tenant 1: 5; tenant 3: 1; eliminados: 0|Contraste intertenant positivo; restore sólo vacío|
|Suppliers|Tenant 1: 1; tenant 3: 1; eliminados: 0|Contraste intertenant positivo; dataset pequeño|
|Users|T1:4, T2:1, T3:1, T4:1; sin tenant:1|No confundir número total con usuarios editables/no owner|
|Price lists|3 T1/C1 y 3 T1/C2; ninguna T3/C3|Contraste company y vacío; no demuestra coherencia default|
|Entries, SQL bruto|40 T1/C1, 2 T1/C2, 1 T3/C3; 4 soft-deleted|El filtro real V2 reduce el conjunto|
|Órdenes|4 T1/C1, una eliminada|3 normales activas, estados de recepción 1,2,2|
|Cash movements|T1: 2 sin C/B, 241 C1 sin B, 15 C1/B1, 1 C1/B8|Mucho histórico; no rescatarlo relajando contexto|
|Cash registers|23 C1 sin B, 5 C1/B1, 1 C1/B8|Sesiones propias abiertas: Owner EDESCE 3 en B1 y 1 en B8, no necesariamente misma caja|
|Guides|Shipping 0; Referral 0|Sin QA positivo; ausencia de datos no valida aislamiento|
|Promotions / follow|Limit 0, Order 0, Seasonal 0, follow 0|Dataset positivo inexistente|
|Ganancia diaria|1 con T/C/B NULL; 0 contextual|No desapareció la necesidad de recálculo|

Prueba nueva de contexto vía middleware `InitializeTenantContext` y lectura real de `EntryController::getAllEntriesV2/getAllOrdersV2` (no HTTP):

|Owner / T-C-B|Customers IDs|Suppliers IDs|Entries V2 visibles|Órdenes normales / estado|
|---|---|---|---:|---|
|EDESCE 1-1-1|1–5|1|31|3 / 1,2,2|
|EDESCE 1-1-8|1–5|1|31|3 / 1,2,2|
|EDESCE 1-2-2|1–5|1|2|0|
|EDESCE 1-5-6 y 1-5-7|1–5|1|0|0|
|Gamarra 3-3-4|6|2|1|0|

Clientes/proveedores son compartidos por tenant deliberadamente; ver los mismos IDs en C1/C2/C5 no es fuga company bajo este contrato. Órdenes/entradas sí son company. No forzar branch sobre sus listados.

## Reauditoría focalizada A–L

### A. Public Catalog — esquema listo, publicación y QA pendientes

Fuentes: `app/Services/PublicCatalog.php`, `PublicCatalogSlug.php`, `PublicCatalogController.php`, últimas rutas de `routes/web.php`, `resources/views/shop/publicCatalog/{index,detail}.blade.php`.

Rutas públicas distintas del preview histórico: `/{companySlug}`, `/{companySlug}/{branchSlug}`, y sus variantes `/producto/{material}`; throttle 120/min, sin necesidad de sesión. Company exige tenant/empresa activos; branches activas y con slug. La migración elimina el error de columna, pero **no existe ningún slug real utilizable hoy**. No inventar URL EDESCE/Gamarra ni publicar valores durante auditoría.

|SW|Contrato presente|Estado actual / cierre necesario|
|---|---|---|
|SW-01|Resolver explícito T/C/B, material y stock tenant, company_stock_items activo|OPEN de verificación pública. Esquema RESUELTO, falta QA anónimo positivo/negativo entre tenants/companies/branches|
|SW-02|Sólo ofertas con SUM(on_hand-reserved)>0 y cantidades coherentes; no almacén general sin branch|OPEN. Disponibilidad no equivale a política editorial de publicación; faltan agotados, trazabilidad/location y casos inconsistentes|
|SW-03|Lista activa company del branch o único default activo; item precede material; rangos PEN/USD positivos; show_prices|OPEN. QA de default ambiguo/inactivo, precio ausente, moneda y HTML oculto; no hay contrato público completo de presentaciones|
|SW-04|buscar/local/category_id/subcategory_id/talla_id/color_id/min_price/max_price; paginate(24)|OPEN de QA. Precio filtra PEN; no arrays legacy size_ids/color_ids, ni marca/promoción/multicategoría|
|SW-05|Detalle agrupa tiendas, WhatsApp de `company.profile.whatsapp` normalizado; botón disabled sin número|OPEN. Sin selección comercial variante/presentación pública ni mensaje con opción precisa|
|SW-06|Inicio remite al catálogo company|OPEN. No contrato editorial de Home/destacados/novedades; no publicar fixtures|
|SW-07|Imagen real por basename/urlencode + fallback permanente; nombre de empresa|OPEN. Branding completo no se hereda automáticamente de Settings; comprobar logo/contactos/URLs e imágenes públicas|

Detalle nuevo sigue requiriendo contraste de paridad con la referencia aprobada; no confundir compartir CSS con conservar galería/selector/CTA. No se reabre preview/bridge ni se relajan guards. **PUBLIC CATALOG NEXT? NOT YET**: antes, asignación autorizada de slugs y dataset publicable aislado, decisiones SW de exposición y QA anónimo. No es necesario resolver toda la home editorial para probar un catálogo básico, pero sí esas puertas de acceso/datos. Estado: FRONTEND CAN CONTINUE WITH TECHNICAL DEBT, no READY FOR PUBLIC QA positivo.

### B. Tenant Company / Empresas — READY FOR FRONTEND

`TenantCompanyController`: `/dashboard/configuracion/empresas`, `tenantCompany.index/create/edit`, auth/check.enabled/password/tenant.context + tenant.owner y permisos company. Company no tiene TenantScope: controller filtra tenant explícitamente. Owner puede gestionar sus empresas, no sólo la company activa.

Identidad: RUC, razón social, nombre comercial, dirección, teléfono, email, activo; slug company y slug de sucursal inicial en create. `PublicCatalogSlug` exige formato, unicidad global de company, unicidad branch dentro de company y segmentos reservados. No generar slugs desde JS.

Store transaccional crea company, branch principal, membresías owner/defaults y `CompanyInventoryStructureService::provision`; no cambia contexto actual. Edit actualiza identidad/slug; toggle protege empresa actual y última activa. Slug cambiado puede cambiar enlaces públicos. Integrar UI sin guardar, no rehacer Branch ya integrado. Mutaciones/provisión/concurrencia requieren QA aislado posterior.

### C. PriceList general — FRONTEND CAN CONTINUE WITH TECHNICAL DEBT

`PriceListController`, documento `REAL-NEXT-BRANCH-PRICING-PRESENTATIONS.md`: **BP-01, BP-02, BP-03 siguen OPEN** sin delta corrector.

- BP-01: primera lista fuerza default después de validar el default solicitado; puede resultar default inactivo.
- BP-02: returns 422 dentro del closure de update no se propagan; respuesta externa de éxito y posibles defaults desmarcados previamente. No certificar atomicidad.
- BP-03: moneda string max:3, no whitelist coherente con POS/base_currency. No convertir monedas en frontend.
- Límite adicional: `getPriceLists` filtra company actual; `validatePriceListTenant` de operaciones por ID sólo verifica tenant. Debe definirse/confirmarse si edición entre companies del mismo tenant es intencional y comprobar autorización; no asumir el mismo perímetro que el selector de branch. BP-04/05 tampoco se cierran.

Lectura puede presentarse, pero no merece una fase CRUD prioritaria antes de corregir defaults/errores y aclarar el alcance por ID. Branch Pricing integrado no lo resuelve.

### D. Cash — separar tres superficies y posting

`CashBoxController::myIndex/myList/adminIndex/adminList/buildListResponse`; `CashRegisterController::session/getSessionMovements`.

- `/dashboard/cash-movements/my`: **READY FOR READ-ONLY FRONTEND**. Company/branch explícitos, usuario propio para myList; TenantScope en modelos. Históricos sin branch excluidos.
- `/dashboard/cash-registers/session/{cashBox}` y `/{cashRegister}/movements/{pageNumber?}`: **READY FOR READ-ONLY FRONTEND**, caja activa de company/local o compartida y sesión propia/contextual. Sin sesión abierta redirige; no abrir una para fabricar QA. Hay sesiones EDESCE; Gamarra permite negativo/vacío.
- `/dashboard/cash-movements/admin`: **BACKEND SYNC REQUIRED de autorización**. Query mantiene C/B pero rutas no tienen permiso admin específico; actions inspeccionadas no exigen owner/rol administrativo. Menú oculto no es autorización. No certificar acceso administrativo sólo con Owner.

Cash regularization ya existe (partial/JS/servicio), no crear duplicado. Apertura/cierre/arqueo/ingreso/egreso/regularización son otra fase: identidad de posting, comisiones, reintentos, saldo y locks. No se ejecutan ni se certifican desde la fachada de lectura.

### E. Entries V2 / recepción — READY FOR READ-ONLY FRONTEND, sólo listados

`/dashboard/listado/entradas/compra/v2` → listEntryPurchaseV2/getAllEntriesV2: TenantScope + company explícita, Por compra y finance=false, fecha date_entry, filtros reales y paginación. Hay datos positivos en C1/C2/Gamarra.

`/dashboard/entradas/compra/ordenes/v2` → listOrderPurchaseV2/getAllOrdersV2: OrderPurchase con `ScopedPurchaseCompany`, type n; `NormalPurchaseReceiptSummary` devuelve completas/incompletas/por ingresar/requiere revisión. Actualmente 1 completa y 2 por ingresar en C1. El listado no equivale a recepción.

**Nuevo límite documentado:** `showEntryPurchase(Entry $entry)` devuelve vista sin comprobar company; Entry sólo usa BelongsToTenant/SoftDeletes. No hay resolver company en ese modelo/RouteServiceProvider inspeccionados. En T1/C1, consulta equivalente al binding ve 2 entries activas de otra company; en T1/C2 ve 36. Es evidencia de modelo/código, no explotación HTTP. **Detalle company-safe NO certificado; BACKEND SYNC OPEN.** No enlazarlo como detalle seguro nuevo ni filtrar su acceso sólo con JS.

Adjuntos `images/entries` y documentos extra conservan deuda public-path. No incluir documentos, exports/reporte legacy, recepción, eliminación, regularización ni posteo. El GET antiguo de actualización de estados responde 405; no reactivarlo. Conviene después de Proveedores/Normal Purchases únicamente con ese alcance explícito.

### F. Clientes — READY FOR FRONTEND, contrato tenant

`CustomerController`, modelo Customer con BelongsToTenant/SoftDeletes; Store/Update/Delete/Restore requests verifican tenant para IDs/unicidad RUC. Rutas `customer.index/create/edit/indexrestore`, permisos list/create/update/destroy_customer. Edit findOrFail y onlyTrashed mantienen TenantScope; restore también restaura contactos.

No company_id en contrato del catálogo: compartido por empresas del tenant. Dataset positivo en ambos tenants y negativo por IDs; no hay eliminados para restore positivo. POS/cotizaciones consumen este catálogo; no modificar sus consumidores ni convertir customer en actor/worker.

Decolecta es dependencia separada. `searchOrCreateByDocument` es mutador aunque sea GET: EXCLUIDO. Consultas DNI/RUC y documento externo no se ejecutaron; no certificadas credenciales, cuotas ni disponibilidad. Integrar listado/forms/restore UI; persistencia, contactos y proveedores externos quedan QA PENDING.

### G. Proveedores — READY FOR FRONTEND, contrato tenant

Supplier/Controller/requests aplican BelongsToTenant y validación tenant, soft-delete/restore contextual. `supplier.index/create/edit/indexrestore` y permisos list/create/update/destroy_supplier. `getSuppliers` incluye accounts; empresa no es el scope del maestro. Compras normales/facturas referencian proveedores del tenant mientras sus documentos son company. No inferir que un proveedor sólo pertenece a C1 por su uso histórico.

Un proveedor por tenant EDESCE/Gamarra, ninguno eliminado. Reutilizar Configuration CRUD, preservar cuentas/contacto y validaciones; no exportar ni crear/restaurar. Menor complejidad que Usuarios y continuidad directa de compras/finanzas: segunda prioridad.

### H. Usuarios: tres ámbitos diferentes

1. Empresas = TenantCompany, apartado B; no es un mantenedor de usuarios.
2. **ConfigUserWeb — READY FOR FRONTEND**: `/dashboard/configuracion/usuarios/web`, `tenantUsersQuery` restringe tenant del actor, excluye Platform Admin; ensureTenantOwner. Create/edit manejan roles tenant, companies/branches autorizadas y defaults compatibles. `TenantPlanService` comprueba cupos de usuarios activos; TemporaryPasswordService forma parte del contrato. Inhabilitar conserva Worker, roles/membresías/historial y elimina remember_token. No crear ni modificar Worker ni inferir autor de ventas. QA de reset, habilitación, límites/concurrencia y asignaciones pendiente; no mostrar secretos en evidencia.
3. **UserController legacy — BACKEND SYNC REQUIRED**: index/getUsers/getUsers2/getRoles usan User/Role sin filtro tenant/company explícito; User no tiene TenantScope. Mutaciones y users/to/workers no son el contrato seguro de ConfigUserWeb. No modernizar/reutilizar ese camino como sustituto. WorkerCompanyContext/WorkerAccess/WorkerUserLink no agregan filtros a User::all por existir en otra familia.

### I. Guías — BACKEND SYNC REQUIRED incluso para lectura

ShippingGuide y ReferralGuide no tienen scope contextual. `ShippingGuideController::index` filtra tipo 7, fechas/documento, no T/C; show por binding. `ReferralGuideController` lista/find/detalle igualmente sin frontera T/C. Cero registros en ambas tablas no prueba seguridad.

Shipping depende de NubefactGreService/SUNAT, documentos/respuestas y estados SENT/ACCEPTED/REJECTED según respuesta; consultar Nubefact actualiza estado, no es QA read-only. Referral tiene enabled_status y documentos/impresión legacy. La nueva configuración Nubefact no arregla consultas de guías. **No recomendar lectura antes de un read model contextual y documentos autorizados**, aun excluyendo emisión.

### J. Inventario físico / Kardex — BACKEND SYNC REQUIRED

`InventoryController::getDataInventory/getLocationsGeneralMaterial` parten de Material/Item legacy y stock_current; no equivalen al read model company/local de WarehouseAvailability. Guardado de inventario/export tiene efectos y queda excluido.

`InventoryMovementController::kardex`: obtiene StockItem tenant, luego InventoryMovement por stock_item_id y warehouse_id opcional del request. Modelo apunta a inventory_movements_view, tiene casts T/C pero **ningún scope**; query no restringe company/branch ni valida warehouse del contexto. StockItems compartidos entre companies hacen especialmente insuficiente comprobar sólo tenant del producto. No hay frontera read-only segura demostrada para recomendar esta pantalla. Conservar las lecturas modernas ya integradas.

### K. Seguimiento de stock — BACKEND SYNC REQUIRED

FollowMaterialController limita lista por user_id, pero deriva existencias de `Material.stock_current` y detalle/seguimiento no fija el perímetro company/branch. Las órdenes relacionadas ahora usan ScopedPurchaseCompany; eso no convierte el stock agregado legacy en disponibilidad contextual. `unFollowMaterialUser` busca por follow_id sin filtro owner explícito. Alertas/reportes/email usan categoría fija 5; email/export son efectos externos y no se ejecutan. Dataset follow vacío. Separar alertas lectura, follow/unfollow y envío; no reemplazar este cálculo por WarehouseAvailability desde JS.

### L. Promotions — BACKEND SYNC REQUIRED

PromotionLimit, PromotionOrder y SeasonalPromotion sin scope tenant/company; listados/edición/orden global no delimitan contrato. `PromotionOrder::checkPromotions` enlaza orden de aplicación, temporada, descuentos de cantidad y límites/uso: no es sólo configuración estética. Tres tablas vacías. Backend debe definir scope de configuración, elegibilidad, precedencia, precio/moneda y relación con POS; no inventar descuentos ni priorizar este motor para una fase visual.

## Scoring de nuevas superficies

Escala 1–5: BR readiness backend (5 mejor), BV valor (5 mayor), FE esfuerzo (5 más alto), RR riesgo (5 mayor), QF factibilidad QA (5 mejor), DQ calidad dataset (5 mejor). Se puntúa el alcance descrito, no producción completa. DQ baja si sólo hay vacíos, falta restore/roles/casos adversos. Ranking por criterio, sin fórmula automática.

|Candidato|BR|BV|FE|RR|QF|DQ|Recommended Next?|
|---|---:|---:|---:|---:|---:|---:|---|
|Empresas identidad/slugs, sin guardar|4|4|2|2|4|4|YES|
|Proveedores UI tenant|4|4|2|2|4|3|YES|
|Clientes UI tenant|4|5|2|2|4|3|YES|
|Entries V2 + órdenes, sólo listados|4|5|3|3|4|4|YES|
|Mis movimientos + sesión propia lectura|4|5|3|3|3|3|YES|
|ConfigUserWeb Owner|4|4|3|3|3|3|YES|
|PriceList general, lectura acotada|3|5|3|4|3|3|LATER|
|Public Catalog|3|5|4|4|1|1|LATER|
|Movimientos admin|2|4|2|4|2|3|NO|
|Entry detalle/documentos|2|4|2|4|2|4|NO|
|UserController legacy|1|3|3|5|2|3|NO|
|Shipping / Referral lectura|1|4|3|5|1|1|NO|
|Inventory físico / Kardex|1|5|3|5|2|3|NO|
|FollowMaterial / alertas|2|3|3|4|1|1|NO|
|Promotions config / motor|1|4|4|5|1|1|NO|

## NEW FRONTEND PHASES

Empresas; Proveedores; Clientes; Entries V2 y órdenes para almacén (sólo listas); Mis movimientos/sesión propia (sólo lectura); ConfigUserWeb. No promover automáticamente detalle Entry ni admin cash por compartir controller con un candidato listo. PriceList/Public Catalog quedan posteriores y condicionados.

### QUICK WINS — máximo 5

1. Empresas: patrón de configuración existente, dataset y schema listos.
2. Proveedores: CRUD pequeño, relación inmediata con compras/facturas.
3. Clientes: mismo patrón, dataset en ambos tenants; excluir Decolecta mutador.
4. Entries V2 listado: reutiliza Operational List y no necesita recepción para aportar valor.
5. QA de Invoice Categories ya integrado: pequeño alcance, **QA CLOSURE**, sólo cuando se autorice dataset de mutación aislado.

### HIGH VALUE NEXT — máximo 5

1. Entries V2 + órdenes pendientes: continuidad operativa de Normal Purchases.
2. Mis movimientos y sesión propia: lectura financiera útil sin tocar posting.
3. Clientes: maestro compartido por ventas/cotizaciones.
4. ConfigUserWeb: roles y asignaciones reales, distinto del legacy inseguro.
5. Daily Profit contextual: **QA CLOSURE**, aprovecha la fachada terminada y schema aplicado.

### WAIT FOR BACKEND — máximo 10 grupos

1. Material Edit Save / Separate Pack: siguen STILL BLOCKED.
2. OrderPurchaseFinance: sin cierre contextual; no hereda FinanceInvoices.
3. Dashboard metrics / Financial Summary / Meta-Ranking: consultas/contexto y semántica pendientes.
4. Core POS / FreeSale: posting, reversión, pricing/tax y concurrencia sin cierre integral.
5. Legacy Item endpoints / Inventory físico / Kardex / FollowMaterial: autorización y stock contextual incompletos.
6. Documentos public-path + detalle Entry company + categoría histórica Finance: lectura de lista no certifica documentos/relaciones.
7. PriceList BP-01/02/03 y autorización por ID: no cerrado por Branch Pricing.
8. UserController legacy / Movimientos admin: límites de acceso no equivalentes a sus alternativas seguras.
9. ShippingGuide / ReferralGuide: read model contextual y documentos, además de emisión.
10. Promotions: scope y motor comercial.

Public Catalog espera además dataset/autorización de publicación y QA, no sólo backend. PaymentDeadline consumidores legacy/inactivos y bancos/default/link CashBox conservan sus Backend Sync Notes; no se pierden por quedar fuera de este top 10.

## QA CLOSURE PHASES — no son nuevas fachadas

|Superficie integrada|Cierre necesario|Puerta / lo que no se hizo|
|---|---|---|
|Sales List|Roles, actor/worker/contextos, acciones y documentos|Lectura adicional posible; acciones/documentos separados; no emitir/anular|
|Cash Boxes/Subtypes|Mutaciones, límites company/local, moneda, estados|Dataset aislado y autorización; no confundir con regularización|
|Warehouse Transfers|Ciclo real, recepción/rechazo/cancelación, trazabilidad|3 transferencias previas no certifican ciclo; stock mutable|
|Branch Pricing|Asignación, fallback, moneda, BP-01…05|Backend fix donde corresponde + mutation QA; selector integrado no basta|
|User Profit exports/docs|Igualdad filtros/contextos con listado y acceso documental|No exports ejecutados; public-path OPEN|
|Daily Profit|Recálculo por fecha y contraste resumen/detalles|Propuesta detallada abajo; 0 contextual hoy|
|Finance Invoices exports/docs|Reportes/exports, relación categoría, documentos|0/5 snapshots históricos según cierre; no inventar fiscalidad|
|Company Settings|Guardar/reset/validación/upload y preservación imagen|QA aislado autorizado; no reabrir diseño|
|Company Bank Accounts|Mutaciones/default activo/currency y link CashBox|Históricos bank/currency, default inactivo y link checks OPEN|
|Payment Deadlines|Guardar/desactivar/usados, consumidores activos|Legacy puede ofrecer inactivos; no modificar consumidor en frontend|
|Invoice Categories|Crear/editar/desactivar o delete según contrato/referencias|Backfill cerrado, mutation QA pendiente|
|Warehouse Availability|Tránsito positivo/inconsistente y trazabilidad|QA real pendiente; no crear traslado durante auditoría|

Priorizar cierres pequeños de configuración cuando exista un dataset aislado; no consumir nuevas fases en repetir responsive de una fachada congelada sin regresión. Los cierres monetarios requieren su propia autorización y evidencia backend.

## Daily Profit: propuesta exacta, NO ejecutada

**¿Conviene recalcular ahora de forma controlada? YES.** Ya no falta esquema; permite cerrar un reporte integrado con un día acotado. Recomendación para siguiente fase autorizada, no permiso implícito para escribir en esta auditoría.

Desde la raíz de aliexpress, PowerShell:

```powershell
& 'C:\wamp64\bin\php\php7.3.33\php.exe' artisan calculate:ganancia --date=2026-11-04
```

`DailyProfitReport::contexts('2026-11-04')` ejecutado sólo en lectura devuelve **tenant 1, company 1, branch 1**. La consulta une ventas PEN del día y resúmenes PEN existentes, validando empresas/sucursales/tenant; **el comando NO acepta filtro tenant/company/branch**. Volver a comprobar contexts inmediatamente antes: si aparecen otros contextos, el comando los incluiría también. No afirmar que el flag date restringe a EDESCE por diseño.

Escrituras: crea/actualiza cabecera `ganancia_diarias` por T/C/B/PEN/fecha, guarda cantidad/venta/utilidad, elimina y reconstruye sus detalles agrupados por stock_item o material. No debería modificar ventas, inventario, precios ni movimientos; el legacy de 2026-01-20 queda fuera. Usa transacción por contexto, lock company, índice único y hasta 3 intentos; no hay transacción global para todo el día. Reintento vuelve a generar IDs de detalle.

Riesgos: DB de desarrollo compartida, bloqueo company, lecturas concurrentes, redondeo y costos históricos ausentes; no todo fallo global revierte contextos ya procesados. Fijar fecha, respaldar/clonar y evitar operaciones concurrentes. No ejecutar sin autorización de esa fase.

Validar después: un resumen contextual de esa fecha/moneda, sin duplicados; comparar cantidades/importe/utilidad con `UserProfitReport` para las mismas ventas PEN no anuladas/no reversed; preservar null si falta costo. Contrastar detalle agrupado y filtros de fechas; verificar EDESCE B8/C2/C5 y Gamarra excluyen esa cabecera; legacy sigue excluido; segunda corrida autorizada debe conservar totales sin duplicar. Sin snapshots/costo suficiente no inventar margen para aprobar QA.

## Roadmap C — próximas 8 fases

Seis fases nuevas y dos cierres QA explícitos. Son prioridades propuestas, no autorización para persistir. Si una puerta de QA no está lista, no sustituirla silenciosamente por core POS.

|#|Module|Why now|Backend readiness|QA dataset|Risk|Dependency|
|---|---|---|---|---|---|---|
|1|NEW — Tenant Company / Empresas|Identidad/slugs tras schema, patrón pequeño y útil|READY FOR FRONTEND|5 empresas/8 branches; slugs NULL|Medio al guardar|Owner; provisión/mutación fuera de QA visual|
|2|NEW — Proveedores|Completa maestros de Compras/Finance|READY FOR FRONTEND tenant|1 por T1/T3; restore vacío|Bajo visual/medio mutación|Preservar catálogo tenant y cuentas|
|3|NEW — Clientes|Alto uso en ventas/cotizaciones, mismo patrón|READY FOR FRONTEND tenant|5 T1/1 T3; restore vacío|Medio|Excluir search-or-create y Decolecta del QA de lectura|
|4|NEW — Entries V2 + órdenes para almacén, listados|Cerrar continuidad de compras sin recibir stock|READY FOR READ-ONLY FRONTEND|31 C1/2 C2/1 C3; 3 órdenes C1|Medio|Detalle Entry/documentos excluidos hasta backend|
|5|NEW — Mis movimientos + sesión propia|Lectura financiera cotidiana sin posting|READY FOR READ-ONLY FRONTEND|Movimientos/sesiones EDESCE; Gamarra vacío|Medio|No admin, abrir/cerrar, arqueo ni regularizar|
|6|NEW — ConfigUserWeb|Administración contextual pendiente|READY FOR FRONTEND Owner|T1 4 usuarios/T3 1; variedad limitada|Medio/alto permisos|Plan, roles, membresías; no UserController legacy|
|7|QA CLOSURE — Daily Profit contextual|Desbloquear dataset del reporte ya integrado|Schema/servicio disponibles|2026-11-04, contexto 1/1/1|Medio escritura|Autorización, respaldo/clon y preflight de contexts|
|8|QA CLOSURE — Pricing/Branch + Cajas/Subtipos|Cerrar dependencias del futuro POS, no nueva UI|Condicionado BP y QA financiero|Listas C1/C2; cajas EDESCE; completar casos aislados|Alto|Fix BP, dataset aislado, autorización de mutaciones; no posting POS|

## Trigger para volver al core POS

Revisión al final de C8, **no inicio automático**. Condiciones simultáneas: BP-01/02/03 corregidos y QA de precio/lista/moneda/fallback; asignaciones branch y ofertas verificadas; cajas/subtipos/defaults/monedas coherentes; sesiones y posting/regularización/reversión probados en entorno aislado; tax/documentos con contrato y autorización confirmados; escenarios de stock/presentación/variante, concurrencia/idempotencia y reversión; dataset aislado EDESCE/Gamarra y autorización explícita para ventas de prueba.

C8 no certifica por sí sola posting ni SUNAT. Si alguno sigue OPEN, mantener core pospuesto y conservar ambos baselines visuales. No reabrir POS por falta de nuevas pantallas fáciles.

## Backend Sync Notes y revisita

Hallazgos focalizados que esta auditoría hace explícitos, no regresiones atribuidas a commits nuevos: detalle Entry sin guard company; admin cash sin gate administrativo específico; PriceList por ID sólo valida tenant; UserController legacy no equivalente a ConfigUserWeb; guías/Kardex/promociones sin frontera contextual suficiente. Stock-follow depende de stock legacy y mutaciones sin ownership homogéneo. Ninguno se corrigió desde frontend.

Persisten los blockers conocidos: Material Edit Save, Separate Pack, OrderPurchaseFinance, Dashboard/Financial Summary/Meta-Ranking, POS core, FreeSale, legacy Item endpoints y public-path documents. Cotizaciones mantiene creator/permisos/PDF OPEN según cierre aprobado. PaymentDeadlines, bancos y Finance conservan sus notas; ningún SW/BP se cierra por migración o por estar la fachada integrada.

Revisitar sólo por delta backend verificable, dataset publicado autorizado, mutaciones aisladas aprobadas o regresión real. No repetir el inventario completo para cada avance. Referencias de cierre: `REAL-NEXT-BRANCH-PRICING-PRESENTATIONS.md`, `REAL-NEXT-COMPANY-BANK-ACCOUNTS.md`, `REAL-NEXT-INVOICE-CATEGORIES.md`, `REAL-NEXT-DAILY-PROFIT-READONLY.md`, `REAL-NEXT-FINANCE-INVOICES-READONLY.md`, `REAL-NEXT-PAYMENT-DEADLINES.md`, `REAL-NEXT-WAREHOUSE-AVAILABILITY-READONLY.md`, más el baseline principal.

## Validación del entregable

Únicamente este informe añadido, confirmado con `git status --short`. Código/backend/allowlist sin cambios; sin migraciones, recálculos, exports, escrituras comerciales ni commit. `git diff --check`: PASS; `git diff --no-index --check -- /dev/null docs/REAL-NEXT-FRONTEND-PRIORITY-REAUDIT-2.md`: PASS, sin errores de whitespace (sólo aviso de normalización LF/CRLF). Auditoría de código y lectura contextual, no certificación productiva ni nueva QA visual.
