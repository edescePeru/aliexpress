# REAL NEXT — Roadmap E — Next Unintegrated Screens

Fecha: 2026-11-14. Discovery focalizado, no certificación QA. Roadmap D cerrado.

## Método y límites

Se contrastaron controllers/views candidatos con `config/venti-next.php`, queries principales, bindings, lookups y rutas concretas. No se recorrieron las 1390 rutas ni se ejecutaron endpoints operativos, tests, responsive o mutaciones. Conteos SQL actuales dentro de transacción READ ONLY, finalizada con ROLLBACK; incluyen registros físicos (no son conteos de resultados paginados ni certificación de permisos).

Todas las exclusiones solicitadas permanecen fuera del plan, incluidas las superficies integradas y WAIT FOR DEV de roadmaps anteriores. **PriceList general es una pantalla distinta del selector Branch Pricing**: `REAL-NEXT-BRANCH-PRICING-PRESENTATIONS.md` ya la identifica como dependencia auditada, no modernizada. Su vista y rutas siguen fuera de allowlist. No se propone reabrir sucursales/presentaciones.

## Ready Now

- **Listas de Precios — mantenimiento general**: `priceList.index`, formularios/modales existentes y lectura de materiales/variantes. Query tenant + company; bindings revalidados. Integración visual con QA sin guardar precios ni defaults.
- **Servicios predeterminados / Workforce**: `workforce.index`, partial de tabla y modales. Catálogo tenant-wide, no ficha de trabajador. GET y binding contextual; deuda de validación de unidad en escrituras, indicada abajo.
- **Cambio obligatorio de contraseña**: pantalla self-only independiente de UserController global. Mantener condición `must_change_password`; no convertirla en perfil general ni modificar credenciales para QA.
- **Guías de Remisión — listado/detalle read-only**: avance con deuda explícita de autorización granular; no emisión, consulta Nubefact, archivos o exportaciones. El scope de datos está implementado, no se declara QA PASSED.

### Ranking (13 candidatos/grupos; escala 1–5)

Readiness/valor: 5 mejor. Esfuerzo/riesgo: 5 mayor. Dataset es conteo actual; N/R significa no relevado, no vacío.

| Candidate | Backend readiness | Business value | Visual effort | Risk | Dataset | Decision |
|---|---:|---:|---:|---:|---|---|
| Listas de Precios general | 5 | 5 | 3 | 2 | 6: T1/C1=3, T1/C2=3; otros=0 | READY FOR FRONTEND |
| Servicios predeterminados (Workforce) | 4 | 4 | 2 | 2 | 1 en T1; otros=0 | FRONTEND WITH TECH DEBT |
| GRE listado/detalle | 4 | 5 | 3 | 3 | shipping_guides=0 | FRONTEND WITH TECH DEBT — sólo lectura |
| Cambio obligatorio de contraseña | 5 | 3 | 2 | 2 | 2 usuarios con flag activo | READY FOR FRONTEND |
| Categorías de equipos | 3 | 2 | 2 | 3 | 0 | FRONTEND WITH TECH DEBT — GLOBAL LEGACY |
| Jornadas + Regímenes | 2 | 3 | 3 | 4 | 0 + 0 | NEEDS SHORT AUDIT |
| Vacaciones + Contratos | 1 | 4 | 4 | 5 | 0 + 0 | WAIT FOR DEV |
| Promociones por límite + estacionales | 1 | 4 | 3 | 5 | 0 + 0 | WAIT FOR DEV |
| Equipos por defecto (composición) | 1 | 3 | 5 | 5 | 0 | WAIT FOR DEV |
| Datos generales | 2 | 2 | 2 | 4 | 60 globales | WAIT FOR DEV |
| Entradas por inventario auxiliares | 1 | 3 | 3 | 4 | entries totales=43; subconjunto no relevado | WAIT FOR DEV |
| Caja resumen / reportes legacy | 1 | 4 | 4 | 5 | N/R: descartados por query | WAIT FOR DEV |
| Bancos / catálogos GRE / notificaciones propias | 1 | 1 | 1 | 2 | N/R: sin CRUD de pantalla candidato | SKIP |

## Evidencia contractual mínima

| Superficie / ruta de vista | Query, binding y lookup | Acceso y límite |
|---|---|---|
| `/dashboard/prices` — `priceList.index` | `PriceListController@index/getPriceLists`: Company validada por tenant/id; PriceList usa BelongsToTenant + company. `validatePriceListContext()` exige tenant/company en update, materials y variants. Materiales/StockItems con `enabledForCompany`; variants rechaza material sin StockItem habilitado. | `show_priceList`; lectura/gestión de materiales requiere `manage_priceListMaterial`. Company-wide, no se inventa scope branch. Default/precios siguen backend. |
| `/dashboard/configuracion/workforces-default` — `workforce.index` | `WorkforceController@getData/edit`: Eloquent con BelongsToTenant; edit `findOrFail`. Unidad lookup `UnitMeasure::query()` también tenant-scoped. No parent company. | `tenant.owner` + `manage_workforce`, incluso data/edit. Escrituras usan `exists:unit_measures,id` sin condición tenant: deuda concreta, no certificar mutaciones. UI no debe filtrar para suplir backend. |
| `/dashboard/shipping-guides/listado`, `/detalles/{guide}` — `shipping_guides.view/show` | `ShippingGuideController@index`: `ShippingGuide::forCurrentLocal()` (tenant/company/branch) + tipo 7; fecha/documento y paginate(20). Show reconsulta por mismo scope antes de cargar items/vehicles/drivers; motivo de traslado global por code. | Grupo auth/check.user.enabled/password.changed/tenant.context; sin permiso granular propio en estas rutas. Preservar acceso actual, no afirmar separación por rol. No incluir create/store/consult/export ni abrir links documentales. |
| `/password/cambio-obligatorio` — `password.required.edit` | `RequiredPasswordChangeController@edit/update`: sólo `Auth::user()`, sin ID externo/lookups. Si flag false redirige al dashboard. Vista `auth.required-password-change` standalone AdminLTE, fuera del shell Next. | auth + check.user.enabled; no TenantContext necesario. Adaptar patrón de acceso existente sin forzar layout operativo. No persistir cambio ni activar flag para probar. |
| `/dashboard/categorias/equipos`, `/categorias/equiposeliminados` — `categoryEquipment.index/eliminated` | CategoryEquipment global SoftDeletes; data por descripción, contador `default_equipments`; eliminados `onlyTrashed`. Create/edit son modales en index. GET `edit()` es stub: NO proponer vista edit inexistente. Update busca por ID y devuelve 404 si falta. Sin parent lookup. | list/create/edit/destroy/restore/eliminatedCategory_defaultEquipment en rutas respectivas; AJAX data/typeahead sin permiso granular. No afirmar aislamiento ni incluir composición de equipos. Reutilizar patrón global legacy con deuda explícita. |

No basta `tenant.context`: `TenantScope` no filtra sin tenant y el middleware deja pasar Platform Admin sin contexto. Workforce además tiene tenant.owner; los métodos PriceList/GRE requieren contexto mediante TenantContext. Falta QA por rol para la futura integración, no se ejecutó aquí.

## Batch Opportunities

**BEST BATCH: Servicios predeterminados + Categorías de equipos — configuración auxiliar de cotización.** Un listado con modales y partial para Workforce; index/modales + eliminados para categorías. Comparten patrón Configuration CRUD, no scope: Workforce tenant-wide; categorías global legacy. No fusionar contratos ni agregar selectors. Sin tocar quotes, equipos compuestos, imágenes ni precios reales durante QA. Si la política global de categorías no se acepta en la fase, integrar Workforce y dejar categorías fuera, sin bloquearlo.

Jornadas + Regímenes son un batch potencial, **no comprometido**: `WorkingDay::all`, `Regime::all`, lookup de jornadas global y `Regime::with(details)->find`; rutas sin permiso granular, asociación con asistencia y edición inline. No equivalen a los siete maestros HR Basic ya cerrados. Requieren definición corta de autoridad/permisos antes de priorizarlos.

## High Value

1. Listas de Precios: superficie comercial nueva con seis registros y scope explícito; mayor valor inmediato sin reabrir POS/Branch Pricing.
2. GRE lectura: incorpora una familia operativa aún legacy. Dataset vacío permite integrar empty state; detalle poblado quedará QA PENDING DATASET, sin inventar fixtures en MySQL.
3. Workforce: mantenimiento real de servicios para cotizaciones, no datos laborales personales; un registro disponible.

## Wait for Dev

- **Vacaciones/Contratos**: modelos sin TenantScope; listados y `find` globales. Relación Worker no restringe la query padre; Worker lookup no garantiza company. Mismo tipo de deuda RRHH sensible ya identificado, no nuevo ticket duplicado.
- **Promociones**: PromotionLimit/SeasonalPromotion sin scope; `with(material)`/categoría no aíslan la promoción. No avanzar sobre precios/descuentos globales.
- **DefaultEquipment**: parent CategoryEquipment global, composición mezcla materiales/workforce; GET create además ejecuta `Audit::create`. No es un GET estrictamente read-only.
- **DataGeneral**: configuración global consumida por moneda/warehouse legacy. `getDataGeneral` contiene creación de defaults en GET (lista actual vacía); no homologar a SettingService ni reabrir Company Settings.
- **EntryInventory**: `getJsonEntriesInventory` filtra tipo/finance/fecha, no company; Entry tiene TenantScope solamente. GET escribe Audit. Los 43 entries generales son T1/C1=40, T1/C2=2, T3/C3=1, no evidencia de seguridad del auxiliar.
- **Caja/reportes**: `financialSummary` toma primer owner y consulta cash_registers directamente; `ReportController@quoteSummaryReport` filtra estado de Quote, sin condición contextual explícita. No promoverlos basándose en middleware, ni reabrir Admin Cash/Dashboard.
- Bancos y catálogos GRE explorados tienen controllers vacíos/stub, sin mantenedor real que integrar. Notificaciones propias ofrecen JSON/widget, no nueva pantalla: SKIP. Perfil general sigue UserController, excluido.
- Se mantienen sin cambios los WAIT FOR DEV previos: INV-01/WAREHOUSE-01/02, ASSET-01, RRHH sensible, Expenses List/Edit, OrderService, Outputs, Entry detail, Public Catalog, core POS, compras financieras y document security.

## Roadmap E

Cuatro fases recomendadas. No llenar ocho con pantallas bloqueadas ni dividir CRUD artificialmente; el máximo solicitado no obliga a ocho.

| # | Module | Surfaces | Scope | Dataset | Why now | Risk |
|---|---|---|---|---|---|---|
| 1 | Listas de Precios general | index, modales, tablas de materiales/variantes existentes | tenant/company | 6 listas | Operativa nueva, guards/lookup explícitos; no selector de sucursal | Medio-bajo; cero guardados/default/precios en QA |
| 2 | Batch configuración auxiliar de cotización | Workforce index/partial/modales + categorías index/modales/eliminados | tenant / global legacy separados | 1 / 0 | Batch pequeño, contratos existentes | Medio; exists unidad y permisos AJAX; mutaciones pendientes |
| 3 | Guías de Remisión — read-only | listado + detalle HTML | tenant/company/branch | 0 | Familia operativa nueva contextual | Medio; sin permiso granular, detalle pendiente dataset; documentos excluidos |
| 4 | Cuenta propia — cambio obligatorio | auth.required-password-change | self-only | 2 flags activos | Aislado de UserController global; flujo real pendiente de lenguaje Next | Bajo-medio; no cambiar credenciales, no forzar flags |

## Entrega y comprobación

Único archivo creado en esta fase: este documento. Backend/frontend/allowlist/CSS intactos. Se conservaron los cambios previos de Warrant y sus dos documentos. Sin commit, mutaciones, navegación operativa, QA profundo ni responsive. `git diff --check`: PASS.

**NEXT: Listas de Precios — mantenimiento general**

**SECOND: Batch Servicios predeterminados + Categorías de equipos**

**THIRD: Guías de Remisión — listado/detalle read-only**

**BEST BATCH: Configuración auxiliar de cotización (Workforce + CategoryEquipment)**
