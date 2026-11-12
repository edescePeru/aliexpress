# REAL NEXT — StockItems & Items — Read-only Integration

Fecha: 2026-11-11. Alcance: lectura moderna. Sin cambios backend, CSS, stock, configuración ni commits. Priorización: REAL-NEXT-FRONTEND-PRIORITY-REAUDIT.md.

## Contract Audit

Auditados StockItemController, MaterialController (getItemsStockItems/getItemsStockItemsMaterial), StockItemScreenAccess, StockItemScreenGuard, MaterialInventoryScope, MaterialInventoryGuard, rutas, ambas vistas/scripts e inventory-context.js. Referencias visuales: venti-next/resources/views/material/indexV2.blade.php y patrones operacionales reales de Material List V2, Sales List y mantenedores.

La pantalla antigua cargaba cambios de flags, min/max, ajuste y edición de códigos. Esta integración retira esos controles y sus handlers de los dos scripts exclusivos de estas vistas. Los endpoints backend no se modifican ni se declaran cerrados.

## Routes

Todas bajo `/dashboard`, autenticación y contexto tenant existente. T/C/B = tenant/company/branch.

|Route name / URL|Permission|Guard|Tenant|Company|Branch|Read/Write|Included|
|---|---|---|---|---|---|---|---|
|stockitems.index / stock-items|listStockitem_material|StockItemScreenGuard:page + ScreenAccess|Actual|Habilitación comercial|Contexto válido|GET|Sí|
|stockitems.list / stock-items/list|listStockitem_material|StockItemScreenGuard:endpoint + ScreenAccess + snapshot|Actual|Habilitación comercial|Niveles local + general|GET|Sí|
|material.stock.getItems / view/stock/material/items/{id}|verItems_material|StockItemScreenGuard:page + ScreenAccess.stockItem|Actual|StockItem habilitado|Contexto válido|GET|Sí|
|material.stock.getItemsMaterial / view/stock/material/all/items/{id}|verItems_material|StockItemScreenGuard:endpoint + ScreenAccess.items + snapshot|Actual|Actual|Local + general permitido|GET|Sí|
|stockitems.inventory-levels / stock-items/{stockItem}/inventory-levels|list_material OR verInventario_material OR update_material|MaterialInventoryGuard:endpoint + MaterialInventoryScope|Actual|StockItem habilitado|Local + general permitido|GET|Sí, acción UI conserva verInventario_material|
|toggle-inventory / toggle-active|configInventariable_material / configActive_material|Guard existente intacto|Backend|Backend|Backend|POST|No|
|stockitems.inventory-levels.update|update_material|MaterialInventoryGuard|Backend|Backend|Backend|POST|No|
|items.update.code|editarCodeItems_material|StockItemScreenGuard|Backend|Backend|Backend|PUT|No|
|stock-item.output-adjustment|Contrato existente, no consumido|No certificado por este cierre|—|—|—|Mutación|No|
|ItemController legacy / get/items, get/items/output, get/items/transfer; variantes alternativas|Contratos legacy|No equivalente a ScreenAccess|No certificado|No certificado|No certificado|Lecturas/mutaciones legacy|No|

`stockItem.list` mencionado en el pedido no es el nombre real. La página es `stockitems.index`; los datos son `stockitems.list`. Allowlist añadida únicamente a `stockitems.index` y `material.stock.getItems`. **ALLOWLIST MATCH: YES**.

## Permissions

Se conserva el permiso de ruta y el gating previo de acciones. Ver Items requiere `verItems_material` y `material.tipo_venta_id == 3` en la lista, tal como antes. Ver inventario requiere `verInventario_material` en UI. El backend permanece autoridad definitiva. Los permisos proceden de getPermissionsViaRoles, sin ampliar este contrato. Owner no implica acceso a todo el tenant.

## StockItemScreenAccess

StockItems: tenant actual + company_stock_items activo del mismo T/C + material del tenant no eliminado. IDs enteros positivos y firstOrFail. Items: T/C, StockItem habilitado, warehouse autorizado, location del mismo T/C y warehouse, estados entered/scraped/reserved. No se utiliza ItemController legacy ni se filtra un conjunto inseguro desde JS.

## StockItemScreenGuard

Valida contexto en página y endpoint; exige snapshot en endpoints. Las páginas GET/HEAD capturan el contexto de render. MaterialInventoryGuard hace lo equivalente para niveles. Guards intactos.

## Tenant / Company / Branch Scope

MaterialParameters valida usuario habilitado, tenant, company activa y company_user. MaterialInventoryScope exige branch activa perteneciente a company y branch_user activo. Warehouses: mismo T/C y branch NULL o branch actual. Levels exigen ubicación compatible y StockItem del tenant. No se suman ni se sustituyen cantidades en frontend.

## Inventory Context

Se conserva material._inventoryContext y inventory-context.js. Envía X-Material-Inventory-Tenant/Company/Branch del render, nunca reconstruidos de respuestas nuevas. Confirmado GET HTTP 200 con headers 3/3/4 en Gamarra. Contexto stale produce 422 y mensaje para recargar. La tabla Items retira filas y paginación anteriores en error; StockItems vacía resultados al iniciar consulta. No se reintenta con otro contexto ni endpoint.

## StockItem List Mapping

|Contrato|Presentación|
|---|---|
|sku / barcode|Identificadores centrados; ausencia —|
|display_name / material.full_name|Nombre de unidad comercial / padre separados|
|variant.attribute_summary o talla/color entregados|Variante descriptiva, sin fabricar presentación|
|unit_measure.name|UM centrada|
|tracks_inventory / is_active|Badges informativos; sin toggle|
|stock_current / stock_general|Stock local / general separados|
|stock_reserved / stock_general_reserved|Reservado local / general separados|
|stock_available / stock_general_available|Disponible local / general separados|
|search / page; paginator Laravel de 10|Buscador, resumen y páginas existentes; búsqueda vuelve a 1|
|inventory_levels|Modal de consulta por ubicación; min/max/costos texto, no inputs editables|

Sin aritmética de stock, null→0 ni inferencia de vendibilidad. El backend sí convierte algunos valores a float y calcula agregados: se muestran sus resultados. Se abortan peticiones anteriores y se ignoran respuestas fuera de orden. Error/empty/loading no dejan resultados previos engañosos.

## Items Mapping

Se conserva DataTables serverSide, parámetros draw/start/length/search/order y nombres de columnas reales; 25 por página, opciones 10/25/50/100. Código sin editor, precio y porcentaje originales sin cambio de unidad/escala; no se renombra precio a costo ni se multiplica porcentaje. Material_description es el contrato backend existente. No se agregan lote, vencimiento o ingreso que el contrato de esta pantalla no proporciona.

Detalle expandible accesible por botón: state, state_item, typescrap, location.description y componentes reales area/warehouse/shelf/level/container/position. Textos escapados. Estados: entered → Ingresado, scraped → Retazo (etiqueta existente del ERP), reserved → Reservado; se conserva visible el código original y se muestra cualquier valor desconocido sin inventar categoría.

## Read Actions

Ver inventario y Ver Items en dropdown compartido `next-row-actions`, portal de `js/layout/admin2.js`; detalle de Item inline, búsqueda, paginación y navegación atrás. Modal con Escape/cierre y retorno de foco. Todos los AJAX propios de estas dos pantallas son GET. Login/logout y cambio de contexto QA son las únicas interacciones de sesión, sin escrituras de negocio.

## Mutation Exclusions

Sin toggles, min/max editables, guardar niveles, ajuste stock, editar código o separar paquete; no se cargan handlers ni URLs de mutación en estas vistas. No se eliminan rutas backend ni entradas del shell general ajenas al alcance. Stock mutations: **OUT OF SCOPE**. Separate Pack: **STILL BLOCKED**.

## Legacy Endpoints

`/dashboard/get/items/...`, `/get/items/output/...`, `/get/items/transfer/...`, rutas de variantes alternativas y separar paquetes no se usan como fallback. **BACKEND SYNC REQUIRED**. Este cierre no certifica su aislamiento ni moderniza esos caminos.

## Responsive QA

CSS nuevo: **0**. Reutilizados next-page-toolbar, next-operational-list, next-list-toolbar, next-list-summary, next-data-table, next-list-pagination, next-form-section, next-row-actions y next-aux-modal; utilidades Bootstrap 4 para alineación y wrap. Retirados CSS inline y plugins no usados. Carga observada de venti.min.css y venti-next.css; JS compartido admin2.js, Bootstrap modal/dropdown y DataTables existentes.

|Viewport|StockItems root/client|Items root/client|Modal root/client|Resultado|
|---:|---|---|---|---|
|320|305/305|305/305|320/320|PASS|
|375|360/360|360/360|375/375|PASS|
|390|375/375|375/375|390/390|PASS|
|576|561/561|561/561|576/576|PASS|
|768|753/753|753/753|768/768|PASS|
|992|977/977|977/977|992/992|PASS|
|1200|1185/1185|1185/1185|1200/1200|PASS|
|1440|1425/1425|1425/1425|1440/1440|PASS|

Mediciones DOM en navegador real con filas cargadas. Diferencia de 15px = scrollbar vertical; scroll horizontal solo dentro de tabla, no documento. Modal con niveles reales 12/2/10, mínimo5/máximo100, costos entregados. Revisados dropdown, búsqueda, página2, detalle entered/reserved, vacío de company2/Gamarra y error stale. Node --check ambos scripts; PHP7.3.33 artisan view:cache y git diff --check.

## Cross-context QA

Cuentas owner autorizadas; contraseñas no registradas. Servicios/controlador ejecutados en transacción SQL START TRANSACTION READ ONLY y ROLLBACK, sesiones/cache en memoria. Complementado con navegador autenticado (no confundir comprobaciones de servicio con HTTP).

|Usuario/rol|T/C/B|StockItems|Items|Evidencia|
|---|---|---:|---:|---|
|Owner EDESCE / owner / user4|1/1/1|47|60|Servicio + lista HTTP; StockItem81 muestra12 Items (10 entered,2 reserved)|
|Owner EDESCE / owner / user4|1/1/8|47|60|Servicio, mismos IDs en almacén general1; no atribuir a stock local|
|Owner EDESCE / owner / user4|1/2/2|14|0|Servicio + navegador lista14 y detalle110 vacío|
|Owner Gamarra / owner / user7|3/3/4|2|0|Servicio + navegador IDs105/106 y detalle105 vacío|

La comparación incluye IDs, warehouses y locations, no solo conteos. Los14 compartidos entre company1/2 son95–104 y107–110, habilitados explícitamente; no es fuga por sí mismo. EDESCE/Gamarra no comparten IDs. Todos los60 Items visibles EDESCE están en warehouse1 general, de ahí igualdad entre branches1/8. No hay scraped visibles en dataset: renderer soportado/auditado, QA real de ese estado pendiente por ausencia de datos; no se fabricaron registros.

|Prueba|Resultado|
|---|---|
|StockItem8 de company1 desde company2|404 por ScreenAccess|
|Item1 de EDESCE desde Gamarra|404 por ScreenAccess.item|
|Página Items StockItem81 desde sesión Gamarra|404 en navegador HTTP|
|Snapshot stale en cuatro contextos|422 por servicio; rechazo y mensaje en navegador tras cambio de sesión/contexto|
|Warehouse ajeno|0 Items y0 levels en intersección con IDs ajenos, cuatro contextos|
|Location ajena|0 Items en intersección con IDs ajenos, cuatro contextos|

No hay endpoint GET por Item ID individual en este alcance: la prueba Item1 es del servicio, sin invocar PUT de edición. Warehouse/location no son parámetros de estos GET: rechazo significa exclusión de IDs ajenos por query protegida, no un ficticio422 por filtro que no existe. No se ejecutó sync min/max para probarlo.

Evidencia completa local: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/stockitems-context-qa.json` (IDs, estados, ubicaciones, roles, comparaciones y rechazos).

## Backend Sync Notes

[OPEN]
Point: endpoints legacy ItemController y variantes alternativas no comparten este ScreenAccess.
File / endpoint: rutas legacy indicadas arriba.
Impact: no certificados ni integrados por esta fase.
What backend needs to define or correct: acceso T/C/B y snapshots equivalentes antes de ampliar UI.
Frontend can continue: Yes, solo read path moderno.

[OPEN]
Point: mutaciones/Separate Pack excluidas.
Impact: no se declara integración funcional de inventario completo.
What backend needs to define or correct: autorización, invariantes y readiness de cada camino en fase propia.
Frontend can continue: Yes, lectura. Separate Pack: STILL BLOCKED.

[OPEN]
Point: QA con registros scraped y datos físicos de almacén exclusivo de otra branch.
Impact: dataset actual no permite demostrar casos positivos/negativos poblados de esos dos tipos; código/scope auditados y exclusión por IDs comprobada sin crear datos.
What backend needs to define or correct: proporcionar dataset autorizado existente cuando esté disponible; no requiere relajar guards.
Frontend can continue: Yes.

## Revisit Triggers

Cambios en StockItemScreenAccess/Guard/MaterialInventoryScope, estados permitidos, contratos de paginación/DTO, permisos por rol, habilitación company, semántica local/general, nuevos endpoints o aprobación de mutaciones. Repetir QA con Items scraped y warehouses exclusivos de branch cuando existan.

## Final status

- StockItems List: **FRONTEND INTEGRATED — READ PATH**.
- Items Detail: **FRONTEND INTEGRATED — READ PATH**.
- Context Isolation: **QA PASSED para contextos/dataset actuales**, con límites explícitos anteriores.
- Stock mutations: **OUT OF SCOPE**.
- Legacy Item endpoints: **BACKEND SYNC REQUIRED**.
- Separate Pack: **STILL BLOCKED**.

Archivos: config/venti-next.php; resources/views/stockItem/list.blade.php; resources/views/material/stockItems_items.blade.php; public/js/stockItem/list.js; public/js/material/stockItems_items.js; este documento. Sin cambio de controllers, servicios, middleware, rutas, inventory-context.js o CSS. Sin commit.

## Evidencia visual final

Capturas locales verificadas (misma carpeta de evidencia QA):

- stockitems-desktop.png
- stockitems-mobile.png
- stockitems-items-desktop.png
- stockitems-items-mobile.png

StockItems utiliza text-nowrap de Bootstrap para identificadores/cantidades: evita fragmentar el SKU, conserva scroll local y se revalidaron las ocho anchuras tras el ajuste. Items: paginación de10 muestra1–10 y11–12; búsqueda IMEI devuelve1 de12. Modal: Escape cierra y devuelve foco a «Acciones de CEL-APP-IPH-17». Navegación Ver Items abre la ruta protegida81. Consola final de Items sin errores. Sesión restaurada a Owner EDESCE, T/C/B1/1/1; viewport temporal retirado. Ninguna venta ni operación de inventario ejecutada.
