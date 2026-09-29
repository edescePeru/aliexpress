# REAL NEXT 6 — Dashboard Contract

## Scope

- Screen: `/dashboard/principal`
- Route name: `dashboard.principal`
- Controller: `App\Http\Controllers\HomeController@dashboard`
- View: `resources/views/dashboard/dashboard.blade.php`
- Layout: `resources/views/layouts/appAdmin2.blade.php`
- Chart adapter: `public/js/dashboard/ordersChart.js`
- Middleware: `web`, `auth`, `check.user.enabled`, `password.changed`, `tenant.context`
- Venti Next allowlist: enabled for `dashboard.principal`.
- Platform administrators are redirected to `platform.dashboard`; this view is for a tenant context.

## Current Contract

### KPI datasets

The controller provides cumulative record counts. Rendering remains permission-based.

| KPI | Dataset | Permission | Link | Scope evidence |
| --- | --- | --- | --- | --- |
| Clientes | `Customer::count()` | `list_customer` | `customer.index` | `Customer` uses `BelongsToTenant` |
| Contactos | `ContactName::count()` | `list_contactName` | `contactName.index` | `ContactName` uses `BelongsToTenant` |
| Proveedores | `Supplier::count()` | `list_supplier` | `supplier.index` | `Supplier` uses `BelongsToTenant` |
| Materiales | `Material::count()` | `list_material` | `material.index` | `Material` uses `BelongsToTenant` |
| Entradas a almacén | `Entry::where('finance', false)->count()` | `list_entryPurchase` | `entry.purchase.index` | `Entry` uses `BelongsToTenant` |
| Facturas | `Entry::where('finance', true)->count()` | `list_invoice` | `invoice.index` | `Entry` uses `BelongsToTenant` |
| Salidas de almacén | `Output::count()` | `list_request` | `output.request.index` | `Output` has no direct tenant/company scope |

The complete KPI block additionally requires `showboxes_dashboard`.

### Ranking de cumplimiento de metas

- Permission: `showBoxMetas_dashboard`.
- Frequency setting: `sales.goals.frequency` through `SettingService`.
- Valid frequencies: `semanal`, `quincenal`, `mensual`.
- Period range: `MetaCalendarHelper::getRangeForPeriod()`.
- Source records: `Meta::with('workers')` for the exact active date range.
- Progress: non-annulled `Sale` totals by `worker_id` and `date_sale`.
- Output: top 10 ordered by percentage, with an empty state when no period data exists.
- Detail route: `metas.ranking`.

### Sales chart

- Permission to render and load its adapter: `showBoxGraficosVentas_dashboard`.
- GET endpoint: `/dashboard/sales/chart-data-sale`.
- Filters and payload are preserved: `daily`, `weekly`, `monthly`, `date_range`, `start_date`, `end_date`.
- Response contract: `labels`, `sales`, `total`, `total_percentage`.
- Canvas ID: `sale-chart`.
- Range export: route `sales.export.range` with `start_date` and `end_date`.

### Income vs expense chart

- Permission to render and load its adapter: `showBoxGraficosVentas_dashboard`.
- GET endpoint: `/dashboard/sales/chart-data-utilidad`.
- Filters and payload are preserved: `daily`, `weekly`, `monthly`, `date_range`, `start_date`, `end_date`.
- Response contract: `labels`, `incomes`, `expenses`, `total_income`, `total_expense`, `profit`.
- Canvas ID: `utilidad-chart`.
- Range export: route `cashflow.export.range` with `start_date` and `end_date`.

## Visual Composition

The screen uses the approved Venti Next shell, Page Header, toolbar hierarchy, border-first surfaces, shared spacing/tokens, restrained semantic color, focus visibility and responsive grid.

The final order is:

1. operational KPI summary;
2. goal ranking;
3. sales and cash-flow charts.

The former saturated `small-box` cards, gradient chart cards, inline spreadsheet styles and global card dragging are not part of the new composition. Chart canvases retain their contractual IDs and Chart.js 2-compatible configuration. Missing or failed chart data renders a neutral in-panel state instead of breaking layout.

## REAL NEXT 6.1 — Visual Evolution

| Current | Inspiration essence | Proposed Venti evolution |
| --- | --- | --- |
| Standard Page Header followed by a uniform white surface | Executive opening with more presence | A restrained navy `Executive Hero` inside the Venti shell, using the authenticated user and current date only |
| Seven equally weighted KPI cards | Stronger hierarchy and varied emphasis | Existing KPIs grouped into `Operación`, `Finanzas` and `Relaciones`; operational/financial indicators use primary cards and relationship counts use a quieter secondary strip |
| Ranking uses the same neutral panel weight as every other block | Clearer focus on performance | Dedicated goal surface with a trophy marker, stronger leading-position treatment and the existing semantic progress colors |
| Charts use standard panel headers and 17rem canvases | Charts as the analytical focus | Analytical surfaces receive stronger headers, taller canvases and restrained brand/semantic accents while retaining the same controls and datasets |
| Uniform section separators | More deliberate visual rhythm | Hero, KPI families, goals and charts alternate emphasis through existing canvas/surface/border tokens and spacing |

### New reusable pattern

`Executive Dashboard Pattern` is documented in `VENTI-NEXT-COMPONENT-PATTERNS.md`. It consists of:

- an informational hero with no invented metric;
- semantic KPI groups with `primary` and `secondary` emphasis;
- an emphasized but still border-first ranking surface;
- analytical chart surfaces with shared structure;
- responsive one-column collapse and no global overflow.

This evolution changes presentation only. All permissions, values, URLs, IDs, AJAX payloads, chart response keys and backend sync notes below remain authoritative.

## REAL NEXT 6.2 — Compact Hierarchy

- The Executive Hero is the sole visible page heading; the former Page Header, breadcrumb and `Vista general` surface are not rendered.
- Company and branch metadata reuse `TenantContext::company()` and `TenantContext::branch()`, matching the navbar source of truth.
- The Hero metadata is a compact inline sequence: company, branch and current date. It introduces no metric and no inferred state.
- KPI families start immediately after the Hero. The visible triple introduction (`Indicadores`, `Indicadores clave`, helper) was removed; an `sr-only` heading preserves the section's accessible name.
- Ranking retains its meaningful eyebrow and title.
- Financial activity uses one visible section title before the existing charts.
- The shared layout accepts an optional `body-class` section so a screen can intentionally flatten redundant shell headers without route-specific layout logic. Only this dashboard opts into `next-dashboard-shell`.

## Removed from the Main Composition

| Legacy block | UI result | Former browser dependency | Backend status |
| --- | --- | --- | --- |
| Existencias en almacén | Removed | `reportAmount.js`, amount GET and loading overlay | Routes/controllers remain unchanged |
| Base de datos materiales | Removed | Direct report link | Export route remains unchanged |
| Base de datos por almacén | Removed | warehouse modal, Select2 and download handler | Controller still prepares warehouse/location data |
| Descargar ingresos | Removed | datepicker modal, Moment and export handler | Export route remains unchanged |
| 5 Últimas Rotaciones | Removed | rotation GET, pagination templates and creation handler | Rotation endpoints remain unchanged |
| Gráfico de RRHH | Removed | Select2, year/month controls, `/dashboard/personal/payments`, dynamic table and `lineChart` | Controller still prepares year/month datasets |

The removed scripts are no longer loaded or initialized by this view. No route, controller or persistence behavior was deleted.

## KPI Relevance Review

No KPI was removed automatically. The following are candidates for a later product decision:

- `Contactos` overlaps strongly with `Clientes` and is a cumulative directory count rather than an operational signal.
- `Proveedores` and `Materiales` are inventory/master-data totals; useful as navigation shortcuts, but weak as time-sensitive KPIs.
- `Entradas`, `Facturas` and `Salidas` are all-time counts without period, status or monetary context. A future contract could replace them with period-aware measures such as pending entries, overdue invoices or open dispatches.

Until a business contract defines those alternatives, the current real counts and links remain intact.

## Multitenancy and Authorization

- The page itself requires authenticated tenant context.
- KPI links and cards preserve their existing per-resource permissions.
- Ranking and chart sections preserve their existing dashboard permissions.
- Models using `BelongsToTenant` inherit the active tenant filter.
- The frontend does not add or infer company/branch filters.

## Backend Sync Notes

[OPEN]
Point: `Output` KPI isolation.
File / endpoint: `HomeController@dashboard`, model `App\Output`.
Impact: `Output::count()` has no visible tenant/company global scope, so the value may cross tenant boundaries.
What backend needs to define or correct: authoritative ownership and tenant/company/branch scope for outputs.
Frontend can continue: Yes; the existing count and permission are preserved.

[OPEN]
Point: Sales chart and ranking isolation.
File / endpoint: `HomeController@dashboard`, `GraphsController@getChartDataSale`, model `App\Sale`.
Impact: `Sale` has no direct `TenantScope`; current queries do not constrain ownership through cash register, branch or company.
What backend needs to define or correct: canonical sale ownership scope for dashboard aggregates.
Frontend can continue: Yes; no frontend filter is invented.

[OPEN]
Point: Goals isolation.
File / endpoint: `HomeController@dashboard`, model `App\Meta` and its worker relation.
Impact: `Meta` has no direct tenant/company scope in the model inspected.
What backend needs to define or correct: tenant/company ownership for goals and workers used by the ranking.
Frontend can continue: Yes; existing calculation is preserved.

[OPEN]
Point: Cash-flow isolation.
File / endpoint: `GraphsController@getChartDataCashFlow` and `getCashData`.
Impact: `CashMovement` and `OrderService` have no direct tenant scope; the query does not join through the active cash register/company. `Entry` is tenant-scoped, but the aggregate mixes these sources.
What backend needs to define or correct: canonical company/branch ownership for cash movement and service-order aggregates.
Frontend can continue: Yes; existing endpoint and response are preserved.

[OPEN]
Point: Endpoint authorization.
File / endpoint: `/dashboard/sales/chart-data-sale`, `/dashboard/sales/chart-data-utilidad`, and the two range exports.
Impact: routes inherit the surrounding authenticated dashboard group but do not show a dedicated permission middleware equivalent to `showBoxGraficosVentas_dashboard`.
What backend needs to define or correct: enforce the same capability server-side, independently of Blade visibility.
Frontend can continue: Yes; the adapter loads only when Blade grants the permission.

[OPEN]
Point: eliminated-widget controller work.
File / endpoint: `HomeController@dashboard`.
Impact: locations, warehouses, years and months are still queried even though their former widgets are no longer rendered.
What backend needs to define or correct: remove these unused datasets after confirming no external view composer or downstream contract relies on them.
Frontend can continue: Yes; retaining them avoids an unnecessary backend change in this phase.

## Contracts That Must Not Break

- route and middleware for `dashboard.principal`;
- platform-admin redirect;
- permission names and Blade authorization boundaries;
- all KPI route targets and count values;
- ranking calculation, period text and top-10 ordering;
- chart endpoint URLs, query keys and JSON response keys;
- chart canvas IDs and range field IDs;
- export route URLs and query keys;
- tenant/company/branch context owned by the backend;
- compact navbar exchange-rate trigger and its existing data source.

## Revisit Triggers

- Backend defines sale, cash-flow, output or goal ownership scope.
- Product approves replacing cumulative counts with period-aware operational KPIs.
- Removed report widgets receive a dedicated reports hub.
- Chart endpoints add explicit permissions or a versioned response contract.
