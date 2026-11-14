# REAL NEXT — Branch Pricing & Presentations

> Actualización 2026-11-12: la sección **Closure Preflight — Roadmap C / 8A**, al final, sustituye los estados históricos BP-01/02/03 y la antigua declaración READY AS DEPENDENCY. Estado vigente: **Pricing Dependency for POS — NOT READY**. BP-01/02 y whitelist BP-03 corregidos; faltan configuración monetaria consistente, precios en Gamarra y validación concurrente MySQL aislada. La auditoría original que sigue se conserva como evidencia histórica; sus afirmaciones de backend sin cambios y ausencia de tests corresponden a aquella fase.

Fecha: 2026-11-11. Backend auditado: HEAD `087f931`. QA local PHP 7.3.33 / Laravel real en 8080. Sin cambios en backend comercial, stock, pagos, checkout o emisión. Sin commit.

## Status

| Área | Resultado |
| --- | --- |
| Branch Price List UI | FRONTEND INTEGRATED |
| Branch Presentation Offerings | FRONTEND INTEGRATED |
| Price Resolution Contract | FRONTEND ALIGNED |
| Mutation QA | PENDING |
| POS integration | READY AS DEPENDENCY — checkout no implementado en esta fase |
| Cobertura con datos reales | Parcial: lista explícita, fallback único, ausencia, presentación activa y sin oferta; faltan estados adversos en dataset seguro |

## Contract Audit

Leídos Branch, TenantBranchController, Store/UpdateTenantBranchRequest, BranchPriceListService, PriceResolverService, PriceListController, PriceList, MaterialPresentation, MaterialPresentationBranch, MaterialPresentationController, BranchPresentationService, MaterialInventoryScope, MaterialCompanyCatalog, modelos/scopes, rutas, forms y JS actuales. POS contrastado contra PosCatalogReadService, PosSalePriceReference, PosLineCalculator y PosSaleService. No se toma la documentación anterior como contrato.

Migraciones comprobadas con migrate:status (Yes): definición de presentaciones 2026_01_05, campos de presentación sale_details 2026_01_06, price_lists/items 2026_03_24, tenant/company/material prices y unique stock-item 2026_09_24, branch price_list 2026_11_02, branch offerings 2026_11_03. No se ejecutaron migraciones.

Branch carece de tenant_id: pertenencia a tenant por company. price_list_id nullable con FK restrict. Ofertas tienen unique tenant/company/branch/presentation, FKs restrict y precio decimal(14,2). La migración de ofertas no copia el precio legacy a cada sucursal. Backfill de tenant de definiciones se deriva de Material y rechaza huérfanos/incompatibilidades. Los snapshots de paquetes ya existentes se conservan.

## Routes

Todas bajo `/dashboard`; middleware común auth, check.user.enabled, password.changed, tenant.context. No se modificaron rutas.

| Método / ruta relativa | Nombre | Uso / permiso |
| --- | --- | --- |
| GET configuracion/empresas/{companyId}/sucursales | tenantBranch.index | list_branch + tenant.owner |
| GET configuracion/empresas/{companyId}/sucursales/crear | tenantBranch.create | create_branch + tenant.owner |
| POST configuracion/sucursales | tenantBranch.store | create_branch + tenant.owner |
| GET configuracion/sucursales/{id}/editar | tenantBranch.edit | edit_branch + tenant.owner |
| POST configuracion/sucursales/{id}/actualizar | tenantBranch.update | edit_branch + tenant.owner |
| POST configuracion/sucursales/{id}/toggle-status | tenantBranch.toggleStatus | enable_branch + tenant.owner; preservado |
| GET listado/materiales/v2 | material.indexV2 | list_material + MaterialInventoryGuard:form |
| GET materials-presentations/material/{material}/presentations/configuration | material-presentations.configuration | managePresentations_material + snapshot de contexto |
| GET materials-presentations/material/{material}/presentations | material-presentations.index | lectura contextual; autorización en controller |
| POST materials-presentations/material/{material}/presentations | material-presentations.store | managePresentations_material |
| PUT materials-presentations/presentation/{presentation} | material-presentations.update | managePresentations_material |
| PATCH materials-presentations/presentation/{presentation}/toggle | material-presentations.toggle | managePresentations_material |
| DELETE materials-presentations/presentation/{presentation} | material-presentations.destroy | contrato rechaza con 422; usar desactivación |
| GET prices / prices/lists | priceList.index / priceList.lists | show_priceList |
| POST prices/lists; PUT prices/lists/{priceList} | priceList.store / priceList.update | manage_priceListMaterial |

Las rutas de edición de precios de material/variante bajo prices/lists también requieren manage_priceListMaterial; se auditaron como dependencia, sin reconstruir ese módulo.

### Route Allowlist

| Pantalla | Antes | Después |
| --- | --- | --- |
| tenantBranch.index/create/edit | ALLOWLIST MATCH: NO | ALLOWLIST MATCH: YES |
| material.indexV2, anfitrión del modal | ALLOWLIST MATCH: YES | ALLOWLIST MATCH: YES |
| material-presentations.* | JSON, sin tema | JSON, no requiere allowlist visual |
| priceList.index (mantenimiento general de listas) | ALLOWLIST MATCH: NO | NO; auditado como dependencia, no modernizado en esta fase |

appAdmin2 activa venti.min.css automáticamente al activar Venti Next. Verificados ambos links en formulario real; el modal hereda los del listado Materiales. CSS nuevo de esta fase: 0. Los cambios existentes de Cotizaciones en CSS/Blades/docs se preservaron y no forman parte de esta fase.

## Permissions

Tenant Branch exige Tenant Owner (Platform Admin excluido) y permiso de operación. Puede administrar empresas del mismo tenant aunque no sean la company activa; el selector usa la empresa de la sucursal editada, no una company enviada por JS arbitrariamente. Request valida pertenencia, controller impide cambiar company de branch y servicio vuelve a validar al asignar.

Presentaciones: listado anfitrión list_material; mantenimiento managePresentations_material. Lectura comercial permite alguno de managePresentations_material/create_quoteSale/edit_quoteSale/create_puntoVenta/createV2_puntoVenta. MaterialInventoryScope exige company/local activos y asignación branch_user activa. Headers X-Material-Inventory-Tenant/Company/Branch detectan pantalla obsoleta; no reemplazan contexto autenticado. Se preservan los headers y payloads.

## Branch Price List

Asignación por price_list_id opcional. Update omitiendo la clave conserva el valor; clave vacía lo borra explícitamente (null). La UI conserva name/ID/POST/serialize y muestra lista inválida seleccionada sin sustituirla silenciosamente.

availableForCompany retorna exclusivamente listas activas del tenant y empresa objetivo. assignment valida activo, tenant, company y compañía activa. current exige branch activa de company/tenant activos. Con asignación explícita válida se usa esa lista; con asignación inválida se rechaza, nunca fallback. Sin asignación: busca como máximo dos defaults activos de company; uno se usa, cero retorna null, dos producen ValidationException.

Default company significa is_default + is_active dentro de esa empresa; no default de tenant ni de usuario. El mantenedor intenta desmarcar los otros defaults; no hay constraint único para el default y el resolver sigue defendiendo la ambigüedad.

| Caso | Resultado backend | Frontend esperado |
| --- | --- | --- |
| Lista explícita activa válida | current retorna asignada | Mostrar nombre/moneda y mantener selección |
| Sin lista + default activo único | Retorna default company | Opción sin asignación, explicación condicional; no fallback calculado en JS |
| Sin lista + ningún default | null | No inventar lista/precio; advertir ausencia de listas si colección vacía |
| Sin lista + múltiples defaults activos | 422 al resolver | Advertir que requiere corregir configuración; no elegir primero |
| Lista explícita inactiva | assignment/current rechazan | Mostrar asignada no disponible; no sustitución automática |
| Lista de otra company | Rechazo de asignación | No ofrecerla; conservar error del servidor |
| Lista de otro tenant | Rechazo; acceso a branch ajena 404 | No exponer nombre/moneda de lista ajena |
| Moneda distinta | Asignación admite lista activa; POS store rechaza desacuerdo con finance.base_currency | Mostrar moneda real y condición POS, sin convertir ni bloquear asignación por una regla nueva |

## Price Resolution

resolveWithSource/resolveForStockItem por sí solos utilizan el default company, no la asignación branch. El camino POS llama BranchPriceListService::current y luego resolveUsingPriceList. No son métodos intercambiables.

Prioridad: PriceListItem por stock_item_id + price_list_id; si no existe, PriceListMaterial del material padre; si tampoco existe, price=null y source=none. Un importe existente cero es válido; dato no numérico, negativo o no finito lanza excepción, no se convierte a cero ni sigue buscando fallback. No hay precio de presentación dentro de este resolver.

StockItem pertenece al tenant; la relación material enlaza su padre. resolveUsingPriceList verifica tenant/activo y empresa de la lista dentro del tenant; los callers POS aportan la restricción company/branch. El método genérico no exige por sí mismo que la company de la lista sea la company activa. La respuesta incluye price, source, price_list_id/name, material_id y stock_item_id; moneda se obtiene de PriceList en el DTO POS, no del array del resolver.

## Presentations

MaterialPresentation define tenant/material, label, quantity, active y precio legacy (no fuente comercial local). Cantidad entera 1..999999999 e inmutable al editar; el nombre es compartido. Si label legacy es vacío, DTO muestra Paquete de N. No hay unidad independiente en el DTO: cantidad representa unidades por paquete del material, sin inventar conversiones o unidad nueva.

create reutiliza definición de igual cantidad o crea una nueva, rechaza duplicados legacy ambiguos y nombre distinto de definición existente; habilita oferta local. update exige quantity sin cambios, label y label_original para conflicto de edición; cambia nombre compartido y precio local; conserva estado previo o deja inactiva oferta nueva. toggle exige oferta guardada en moneda vigente. No se expone borrado destructivo.

## Branch Offerings

MaterialPresentationBranch almacena tenant, company, branch, definition, price, currency, is_active. configurations siempre filtra las tres dimensiones autenticadas. El material debe tener al menos un StockItem habilitado en company_stock_items para esa empresa. No hay sustitución por una oferta de otro local.

DTO: id/material_id/quantity/label/price/active/definition_active/configured/currency. configured requiere oferta cuya currency coincida con finance.base_currency; de lo contrario price=null y configured=false, aunque exista fila de moneda anterior. active requiere definición válida, configured y oferta activa. La UI muestra Sin precio, activa/inactiva local o definición inactiva/inválida, preserva los botones deshabilitados y la cantidad fija.

UI reutiliza next-aux-modal, next-form-section, next-section-header, formularios compactos, badges y alert vacío. Mantiene filas de formulario editables existentes, sin imponer una tabla nueva. Precio y moneda locales se etiquetan aparte del nombre compartido. Editar/cancelar preserva valores y no guarda. AJAX POST quantity/price/label; PUT agrega label_original; PATCH toggle sin precio inventado. Generación de requests, validaciones, errores y rutas existentes intactos; JS solo añade binding de moneda y texto vacío.

## Tenant / Company / Branch Matrix

| Entidad | Tenant | Company | Branch | Global/local | Permiso |
| --- | --- | --- | --- | --- | --- |
| PriceList | BelongsToTenant | Propietaria obligatoria | Reutilizable por sucursales company | Company | show_priceList / manage_priceListMaterial |
| BranchPriceList (FK, no tabla separada) | Vía company | Debe coincidir con branch | Asignación por branch | Local, fallback company | Tenant Owner + create/edit_branch |
| MaterialPresentation | Tenant y material padre | Visible si material habilitado en company | Definición no pertenece a branch | Compartida tenant; acceso catálogo company | managePresentations_material |
| MaterialPresentationBranch | Explícito | Explícita | Explícita, contexto autenticado | Oferta local | managePresentations_material + snapshot |
| Precio base/variante | Lista tenant | Lista company | Lista efectiva de branch | Company/local según fuente | manage_priceListMaterial; lectura POS autorizada |
| Precio presentación | Oferta tenant | Oferta company | Oferta branch | Local | managePresentations_material |
| Moneda | Lista/oferta del tenant | finance.base_currency para venta | Oferta del local debe coincidir | Company, sin conversión | Hereda operación; sin selector de moneda local inventado |

## Currency Contract

Oferta acepta precio no negativo con hasta 2 decimales (12 enteros), incluye cero explícito. La moneda de oferta se toma de SettingService finance.base_currency y solo admite PEN/USD. No se envía moneda desde el modal. PriceListController valida string max:3 y uppercases; no limita ahí a PEN/USD ni a la moneda financiera. Asignación de branch tampoco valida esa coincidencia. PosSaleService sí exige moneda financiera PEN/USD igual a la lista y pos_currency enviado. Cambiar moneda de empresa vuelve no configuradas las ofertas de otra moneda hasta reconfigurarlas explícitamente.

## POS Contract Impact

POS CONTRACT IMPACT: dependencia preparada, sin cambios en POS.

V1/V2 leen catálogo StockItem habilitado en empresa/local; V1 además exige disponibilidad positiva válida. Ambos usan lista actual branch y price/has_price/price_source/currency. No se debe habilitar venta tomando null como cero. Presentaciones disponibles son únicamente ofertas activas del contexto.

PosSalePriceReference valida pos_price_list_id contra lista actual bajo lock y exige precio base resuelto. PosLineCalculator calcula primero ese snapshot incluso si después se elige presentación: tener precio de paquete no elimina la exigencia de precio base actual. Para paquete verifica material/tenant/oferta local activa, calcula packs enteros y units_per_pack, obtiene precio y nombre del servidor y rechaza precio cliente discordante.

PosSaleService persiste sale.currency; SaleDetail guarda price_list_id/name, catalog_unit_price/source, stock_item/material, material_presentation_id, presentation_label_snapshot, packs, units_per_pack y precio efectivo. No existe snapshot currency independiente en la fila de oferta/detalle por este flujo; moneda histórica está en Sale. Checkout deberá mantener esas semánticas y los guards de pantalla obsoleta, sin reimplementar precios.

## Store Web Non-Equivalence

SW-03/SW-05 siguen contratos públicos diferentes. Pricing branch y offering autenticado no resuelven el contexto público ni autorizan exposición Store Web. Potencial reutilización futura solo con contrato backend público explícito. No se cerró SW-01…SW-07.

## Responsive QA

| Ancho | Crear sucursal | Editar sucursal | Modal vacío | Modal con filas |
| --- | --- | --- | --- | --- |
| 320 | PASS | PASS | PASS | PASS |
| 375 | PASS | PASS | PASS | PASS |
| 390 | PASS | PASS | PASS | PASS |
| 576 | PASS | PASS | PASS | PASS |
| 768 | PASS | PASS | PASS | PASS |
| 992 | PASS | PASS | PASS | PASS |
| 1200 | PASS | PASS | PASS | PASS |
| 1440 | PASS | PASS | PASS | PASS |

Mediciones DOM: ningún overflow global ni overflow horizontal del modal; select dentro de viewport. QA visual desktop y móvil mediante capturas. Scroll vertical del modal y horizontal interno de tablas conservados. Vista de lista sucursales usa alineación semántica compartida, permisos y acciones previas.

## Functional QA

Owners autorizados, contraseñas no almacenadas. Contextos reales identificados:

| Cuenta / rol | Tenant | Company | Branch | Evidencia |
| --- | --- | --- | --- | --- |
| owner@venti360.com / Owner EDESCE | 1 | 1 EDESCE | 1 Local Principal | Lista 1; opciones 1/6/5, todas PEN. Material 1: ofertas 1/2 sin precio, oferta 5 Caja de 4 activa PEN 250.00 |
| Mismo Owner EDESCE | 1 | 1 EDESCE | 8 OUTLET DE EDESCE | Lista 6; mismo material/definiciones, todas sin precio en ese local, sin heredar 250 |
| Owner EDESCE administra empresa 2 dentro de tenant | 1 | Pantalla destino 2; sesión seguía company 1 | Form crear de company 2 | Opciones 4/3/2, no 1/6/5. Es administración owner, no cambio implícito de contexto |
| owner.gamarra@test.com / Owner Gamarra | 3 | 3 Gamarra Moda | 4 Local Principal | Sin listas; material 19 sin definiciones/ofertas, moneda PEN; catálogo propio de dos materiales |

Cambios de sesión a Outlet y login Gamarra realizados vía UI. Gamarra → GET branch 1 edit de EDESCE devuelve 404; GET catálogo presentaciones material 1 de EDESCE devuelve 404. No errores de consola antes de esas pruebas negativas deliberadas.

Prueba adicional de servicios con contexto explícito en CLI y transacción DB READ ONLY (no sustituye QA de auth): current [1,1,1]→1; [1,1,8]→6; [1,2,2]→2 por fallback; [3,3,4]→null. assignment(company1,list2) y assignment(company3,list1) rechazadas. SELECTs corroboran una sola oferta persistida (tenant/company/branch 1/1/1, presentación 5), sin escrituras. Editar precio en navegador y Cancelar recupera 250.00, cantidad continúa disabled. Se verificaron estados reales vacíos y null, sin transformarlos a cero.

Capturas en `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`: branch-pricing-edesce-desktop.jpg, branch-pricing-create-mobile.jpg, branch-pricing-gamarra.jpg, branch-presentations-edesce-desktop.jpg, branch-presentations-edesce-mobile.jpg, branch-presentations-outlet.jpg, branch-presentations-gamarra.jpg. Scripts readonly locales en ese directorio, sin credenciales.

## Mutation QA

MUTATION QA PENDING. No suite transaccional de mutaciones de este módulo encontrada en tests. No POST de guardar, PUT, PATCH, DELETE de negocio ni cambios de configuración persistidos. Solo login/logout/cambio de contexto autenticado y lectura. READ ONLY + rollback valida lecturas, no permite afirmar mutaciones PASSED. No se crearon datos adversos para simular defaults múltiples, moneda distinta o ofertas inactivas.

## Backend Sync Notes

[OPEN] BP-01 — PriceListController::store permite primera lista inactiva sin default solicitado y después fuerza is_default=true. current ignora defaults inactivos; no asumir unicidad/disponibilidad garantizada por el mantenedor. Backend debe revisar coherencia y concurrencia de defaults. Frontend can continue: Yes; advertencia condicional y errores existentes.

[OPEN] BP-02 — PriceListController::update retorna respuestas 422 desde el closure transaccional, pero la respuesta externa es siempre éxito; además puede desmarcar otras listas antes de ese retorno. Verificado estáticamente, no ejecutado por ser mutación real. Backend debe corregir propagación/atomicidad. Frontend can continue: Yes para esta fase; no se tocó el mantenedor general ni se certifican sus mutaciones.

[OPEN] BP-03 — Moneda de lista acepta cualquier string max:3, asignación admite moneda discordante y venta rechaza fuera de PEN/USD o distinta de finance.base_currency. Backend debe decidir si adelanta validación o conserva restricción al vender. Frontend can continue: Yes; mostrar moneda y condición sin inventar conversión.

[OPEN] BP-04 — Oferta con moneda anterior aparece configured=false/price=null sin exponer moneda o precio anterior en DTO. Frontend no puede distinguir ausencia de oferta de moneda obsoleta; muestra Sin precio en este local. Backend puede añadir motivo si requiere remediación más precisa. Frontend can continue: Yes.

[OPEN] BP-05 — resolveUsingPriceList es un servicio genérico tenant-wide: no valida company activa por sí mismo; mantener validación de callers. La dependencia POS sí obtiene lista del branch y catálogo company. Reauditar cualquier nuevo consumidor; no reutilizarlo como autorización pública. Frontend can continue: Yes.

[OPEN] QA — Faltan mutaciones y datos adversos seguros (listas inactivas/múltiples defaults/moneda distinta/precio cero/definición inválida). No se marca aislamiento completo exhaustivo por pruebas de solo dos tenants.

## Revisit Triggers

- Cambios en DTO/payload/permisos/snapshot de contexto, validación monetaria o fallback.
- Cambios en cantidad/nombre compartido, manejo de duplicados legacy, ofertas inactivas y moneda obsoleta.
- Cierre backend de BP-01/BP-02 y disponibilidad de dataset aislado para mutaciones.
- Inicio POS: probar snapshots, precio base obligatorio aun con paquete, revalidación y moneda; sin tocar checkout ahora.
- Contrato público Store Web explícito: nueva auditoría antes de reutilizar servicios.

## Files / validation

Modificados por esta fase: config/venti-next.php; resources/views/tenantBranch/{index,create,edit}.blade.php; nuevo _pricing.blade.php; resources/views/material/indexv2.blade.php; public/js/material/presentations-branch.js; este documento. Eliminada anidación de cards en formularios, corregida referencia a branch inexistente en breadcrumb Create. Conservado include de almacenes y sus scripts. No controllers/models/requests/services/migrations/rutas modificados.

Validaciones: PHP 7.3.33 artisan view:cache, node --check presentations-branch.js, git diff --check. Cero CSS nuevo. No commit; cambios anteriores de Cotizaciones permanecen intactos.

## Closure Preflight — Roadmap C / 8A

Fecha: 2026-11-12. Auditoría focalizada y corrección backend autorizada. Branch Pricing Frontend: **PRESERVED**. POS V1/V2: **VISUAL BASELINE FROZEN / UNCHANGED**. Sin mutaciones comerciales en la base compartida, sin migraciones ejecutadas y sin commit. Daily Profit no reabierto.

Se revisaron PriceListController, PriceList, PriceListItemController (sin implementación alternativa), validaciones inline, rutas/permisos, BranchPriceListService, PriceResolverService, BranchPresentationService, configuración financiera y consumidores POS/Store Web. No existe FormRequest independiente para el mantenedor PriceList.

### BP-01

**CONFIRMED → RESOLVED.** Store validaba el default solicitado antes de calcular el default efectivo: la primera lista se convertía automáticamente en default aunque `is_active=0`. Ahora calcula ese estado dentro de la transacción y rechaza con ValidationException/422 el default efectivo inactivo antes de crear o desmarcar listas. Primera lista activa sigue siendo default automático; listas posteriores inactivas no predeterminadas siguen permitidas. No se cambia el contrato desde JavaScript.

### BP-02

**CONFIRMED → RESOLVED.** Update desmarcaba defaults antes de validar y devolvía una respuesta 422 desde el closure; el método ignoraba ese retorno y devolvía éxito. Ahora recarga la lista bajo lock, valida antes de desmarcar y lanza ValidationException: el error llega al handler Laravel con estado 422 y la transacción revierte. Un fallo de persistencia posterior a desmarcar defaults también revierte todo, comprobado con trigger de fallo en SQLite aislado. Se mantienen las claves de payload y las respuestas exitosas.

### BP-03

**CONFIRMED → RESOLVED para la whitelist contractual PEN/USD.** Store/update normalizan strings a mayúsculas y validan `Rule::in(['PEN', 'USD'])`; valores no string siguen inválidos. Respaldo: selectores existentes PriceList, enum `finance.base_currency`, BranchPresentationService y PosSaleService. No se inventaron monedas ni conversión.

La igualdad entre moneda de lista y moneda financiera sigue validándose en el límite de venta POS, como antes. Se pueden mantener listas PEN y USD; asignar una lista no convierte sus precios. No se añadió una prohibición nueva al asignador de branch. La discordancia real de company 2 queda como pendiente operativo explícito, no como un dato corregido por esta fase. Store Web mantiene su contrato público separado; ningún SW-01…SW-07 se cierra.

### ID Scope

**PRICE LIST ID SCOPE — COMPANY / RESOLVED.** El listado, UI y las operaciones de variantes ya requieren company actual. La autorización tenant-only de los demás IDs era inconsistente con ese contrato. El guard común ahora exige tenant y company actuales para update, getMaterials, saveMaterialPrices, getMaterialVariants y saveMaterialVariants; devuelve 404 antes de leer/modificar los precios ajenos.

Rutas `/dashboard/prices` y `/dashboard/prices/lists` conservadas, incluidas las operaciones por lista/material/variante. Middleware auth, check enabled, password.changed y tenant.context, junto con `show_priceList` / `manage_priceListMaterial`, sin cambios. No se alteró la administración Owner de sucursales de otras empresas del mismo tenant: ese flujo usa el servicio de asignación y valida explícitamente la empresa destino. Tampoco se convirtió el resolver genérico en un servicio de autorización.

### Default Invariants

- Store/update bloquean la fila Company del contexto dentro de la transacción; las escrituras de defaults por estos endpoints quedan serializadas, incluso cuando todavía no hay listas. Update vuelve a leer la lista bajo lock para no validar un estado obsoleto del route binding.
- Default efectivo debe estar activo. No se permite desactivar directamente el default actual; primero debe designarse otra lista. Cambiar default desmarca las otras listas únicamente de esa company y conserva los defaults de otras empresas.
- El contrato permite desmarcar default sin elegir reemplazo: no se introduce la obligación nueva de tener exactamente uno. Sin default activo, fallback devuelve null.
- Múltiples defaults activos legacy son rechazados por los resolvers; no se escoge arbitrariamente el primero. No hubo reparación automática de registros existentes ni nueva restricción SQL.
- La garantía del lock corresponde a estos endpoints, no a escritores externos o SQL manual. Prueba concurrente multiproceso en MySQL aislado **PENDING**; SQLite verifica atomicidad, no semántica real de FOR UPDATE.

### Price Resolution

**QA PASSED en casos focalizados.** Lista explícita válida tiene prioridad; lista explícita inválida/inactiva se rechaza sin fallback silencioso. Sin asignación: único default activo, ausencia → null, múltiples → rechazo. Precio StockItem override antes de precio Material; ausencia → null/source none; cero explícito se conserva como cero legítimo. No se inventaron precios ni se cambiaron servicios de resolución.

El resolver genérico sigue siendo tenant-wide; sus callers deben autorizar company/branch. POS obtiene lista efectiva y catálogo company antes de resolver. **BP-05 OPEN** para reutilizaciones nuevas, sin evidencia de regresión en el camino auditado.

### Branch Offerings

**QA PASSED en casos focalizados.** Definición de presentación compartida por tenant/material; configuración/precio/activación/moneda locales por tenant/company/branch. Tests prueban la misma definición con precios 20 y 30 en dos branches, ausencia sin heredar precio legacy y moneda discordante → configured=false, active=false, price=null.

**BP-04 OPEN:** el DTO no distingue oferta ausente de oferta con moneda anterior. La UI conserva el estado sin precio; no se añadió un motivo ni se modificó el frontend.

### Dataset read-only

Lecturas en transacción MySQL `READ ONLY`, con rollback final, sesión array y usuarios Owner existentes en memoria: Owner EDESCE (ID 4, tenant 1), Owner Gamarra (ID 7, tenant 3). Contextos explícitos mediante el mecanismo actual de sesión; no login navegador ni prueba HTTP end-to-end en esta fase. Estas lecturas prueban servicios/controladores bajo contexto, no reemplazan QA de middleware/autenticación.

| Tenant / company / branch | Lista explícita → efectiva | Moneda financiera / lista | Evidencia |
| --- | --- | --- | --- |
| 1 / 1 / 1 | 1 → 1 | PEN / PEN | Stock 71: 70, override StockItem; presentación 5: PEN 250, activa |
| 1 / 1 / 8 | 6 → 6 | PEN / PEN | Stock 71: 50, precio Material; presentación 5 sin oferta/precio local |
| 1 / 2 / 2 | null → 2, fallback | USD / PEN | Precio resuelto stock 95: 25; moneda incompatible con venta actual |
| 1 / 2 / 3 | null → 2, fallback | USD / PEN | Mismo fallback company, discordancia monetaria conservada |
| 3 / 3 / 4 | null → null | PEN / sin lista | Stocks 105/106 sin precio resuelto; no se convierten a cero |

Inventario de listas: company 1 tiene 1 PRECIO GENERAL (default), 5 PRECIO PREMIUM y 6 PRECIO OUTLET; company 2 tiene 2 REGULAR (default), 3 PREMIUM y 4 OUTLET; todas activas/PEN. Company 3 no tiene listas. No hay defaults múltiples en esas companies. Los StockItems activos habilitados consultados de companies 1/2 tienen precio; esto no certifica moneda compatible ni disponibilidad de venta por local.

Única oferta persistida encontrada: tenant/company/branch 1/1/1, presentación 5 (Caja de 4), activa PEN 250.00. No se hereda en branch 8. Definiciones 1/2 del material 1 comparten cantidad legacy 3, sin oferta activa; no se limpiaron datos. Company 2 y Gamarra no presentan ofertas locales en el catálogo consultado.

Guard real getMaterials: company 1 rechaza IDs 2/3/4; company 2 rechaza 1/5/6; Gamarra rechaza los seis IDs, todos con 404. Se invocó controlador de lectura, no endpoints de escritura. Los cinco métodos por ID se cubren además con fixtures sintéticos de otra company y otro tenant.

### Tests

Nuevo `tests/Feature/PriceListClosureTest.php`: conexión dedicada `pricing_memory`, SQLite `:memory:`, configurada antes de schema/fixtures, con aserción de base en memoria. No RefreshDatabase sobre conexión compartida, no migraciones reales, no truncados reales.

- Reproducción antes del fix: 8 tests, 24 assertions, 5 failures (BP-01, promoción inválida, desactivación default, moneda, scope company).
- Después del fix y ampliación de cobertura: **10 tests, 62 assertions — PASS**.
- Cobertura: primer default, inactive, cambio default company-local, whitelist y normalización, los cinco guards por ID, rollback tras fallo de persistencia, default ambiguo/ausente/asignado, null/0/override, aislamiento y moneda de ofertas.
- Runtime: PHP 7.3.33, PHPUnit 9.6.13. Comando: `php vendor/phpunit/phpunit/phpunit tests/Feature/PriceListClosureTest.php` usando ese binario PHP. Warning preexistente de schema XML PHPUnit deprecado; configuración no modificada.
- Sintaxis PHP de controlador y test: PASS. `git diff --check`: PASS.
- Tests invocan controladores/servicios directamente: no se afirma cobertura HTTP, middleware, concurrencia MySQL ni checkout.

### Remaining Blockers

1. Company 2: moneda financiera USD y lista efectiva PEN en branches 2/3. Debe configurarse coherentemente mediante una decisión autorizada; no convertir ni reasignar automáticamente. La venta actual rechaza la discordancia.
2. Gamarra/company 3: no hay lista efectiva ni precios para stocks 105/106. Falta configuración autorizada antes de certificar ese dataset para POS. El null actual es comportamiento seguro, no una regresión.
3. Falta validar concurrencia del primer default/cambio de default en MySQL aislado. No se presenta la suite SQLite como prueba de exclusión mutua entre procesos.

Mutaciones HTTP reales y checkout siguen excluidos por alcance. BP-04 (diagnóstico de moneda de oferta) y BP-05 (autorización de callers futuros) permanecen OPEN como límites conocidos; no se amplió alcance para resolverlos. No se requiere rediseño frontend para los fixes entregados.

### POS Dependency Decision

**NO — Pricing Dependency for POS: NOT READY como dependencia certificada integral para los contextos solicitados.** Los defectos BP-01/BP-02, la whitelist BP-03 y el scope por ID quedan corregidos y probados aisladamente. La configuración monetaria de company 2, los precios ausentes de Gamarra y el QA concurrente pendiente impiden declarar cierre completo.

Siguiente paso recomendado: acordar/remediar configuración en una fase expresamente autorizada y probar concurrencia en MySQL de testing separado; después reejecutar lecturas y decidir certificación. No entrar todavía al core POS ni editar su baseline visual.

Archivos de esta fase: `app/Http/Controllers/PriceListController.php`, `tests/Feature/PriceListClosureTest.php`, este documento. **Frontend files modified: 0.** Servicios, modelos, rutas, permisos, migraciones, POS V1/V2 y CSS sin cambios. Sin commit.
