# REAL NEXT 7 — Platform Admin Contract

## Boundary and Activation

- Prefix: `/dashboard/plataforma`.
- Shared middleware: `web`, `auth`, `check.user.enabled`, `password.changed`,
  `platform.admin`.
- Guard: `EnsurePlatformAdmin` requires `User::is_platform_admin`; it does not
  reject historical platform users that still have a `tenant_id`.
- Venti Next allowlist: all seven primary screens plus their rendered
  create/edit/detail pages are enabled.
- Asset contract: `appAdmin2.blade.php` loads `venti.min.css` and then
  `venti-next.css` when the current named route is allowlisted.

`ALLOWLIST MATCH: YES`

`VENTI NEXT CSS LOADED: YES`

## Screen Inventory

| Screen | Type | Scope | CRUD | Filters | Permissions | Risk | Visual Pattern |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Platform Overview | Platform Overview | Global | Read/navigation | None | `platform.admin` | No metrics exist; never invent them | Page Header + directory surface |
| Plans | Admin CRUD List | Global | Create, update, toggle status; no delete/bulk | Search, page size | `platform.admin` | Toggle blocked only when disabling a plan with active tenants | Operational List + Auxiliary Modal |
| Role Templates | Admin CRUD List | Global | Create, update, toggle status; no delete/bulk | Search, page size | `platform.admin` | Permissions endpoint exposes the global `web` permission catalog | Operational List + separate forms |
| Tenant Roles | Admin Filtered Operational List | Explicit `tenant_id` | Create, update, toggle status; no delete/bulk | Tenant, search, page size | `platform.admin` | Any active tenant is selectable by design | Filtered Operational List + separate forms |
| Activity Log | Audit Log | Global platform log | Read/detail only | Search, action, tenant, administrator, dates, page size | `platform.admin` | Tenant identity is historical JSON metadata | Filtered Operational List + detail surface |
| Tenants | Tenant Directory | Global directory | Create, update, toggle status, owner password reset; no delete/bulk | Search, plan, status, page size | `platform.admin` | Mutations are operationally sensitive | Operational List + create/detail flows |
| Labor Parameters | Global Settings Table | Global | Update the three allowlisted keys; no delete/bulk on platform routes | None | `platform.admin`; sidebar also checks `list_percentageWorker` | UI permission and route guard are not identical | Compact settings table + edit page |

## Exact Routes and Adapters

### Platform Overview

- `GET /dashboard/plataforma` — `platform.dashboard` —
  `Platform\PlatformDashboardController@index` —
  `platform.dashboard.index`.
- Dataset: authenticated platform user only.

### Plans

- `plan.index`, `plan.data`, `plan.store`, `plan.update`,
  `plan.toggleStatus`.
- Controller: `PlanController`; view: `plan.index`; adapter:
  `public/js/plan/index.js`.
- Fields: `code`, `name`, `max_active_users`, `description`; new records start
  active. Status is the deletion substitute and cannot be disabled while an
  active tenant uses the plan.
- Pagination is Laravel JSON pagination, not DataTables.

### Role Templates

- `roleTemplate.index`, `data`, `permissions`, `create`, `edit`, `show`,
  `store`, `update`, `toggleStatus`.
- Controller: `RoleTemplateController`; views: `roleTemplate/*`; adapters:
  `roleTemplate/index.js` and `roleTemplate/form.js`.
- Contract: code, name, description, owner-assignable flag, active flag and a
  set of `web` permission IDs.
- Pagination is Laravel JSON pagination, not DataTables.

### Tenant Roles

- `tenantRole.index`, `data`, `create`, `edit`, `show`, `store`, `update`,
  `toggleStatus`.
- Controller: `TenantRoleController`; views: `tenantRole/*`; adapters:
  `tenantRole/index.js` and `tenantRole/form.js`.
- Every role lookup used by list/show/edit/update/toggle includes the selected
  route or request `tenant_id`. Permissions reuse
  `roleTemplate.permissions`.
- Pagination is Laravel JSON pagination, not DataTables.

### Activity Log

- `platformActivity.index`, `data`, `show`.
- Controller: `Platform\ActivityLogController`; view:
  `platform.activityLog.index`; adapter: `platform/activityLog/index.js`.
- Query is restricted to `log_name = platform`; tenant filtering uses
  `properties.tenant_id`. Detail is read-only.

### Tenants

- `platformTenant.index`, `data`, `create`, `store`, `show`, `editData`,
  `update`, `toggleStatus`, `resetOwnerPassword`.
- Controller: `Platform\TenantController`; views: `platform.tenants/*`;
  adapters: `platform/tenants/index.js`, `create.js`, `show.js`.
- The index exposes plan, active user capacity, companies, owner and status.
  Creation provisions related records transactionally; detail owns subsequent
  updates, state changes and password reset.

### Labor Parameters

- `platformPercentageWorker.index`, `edit`, `update`.
- Controller: `PercentageWorkerController`; views: `percentageWorker/index`
  and `edit`.
- Platform pages restrict records to `assign_family`, `essalud`, `rmv`; the
  update request and controller preserve the existing value contract.
- `public/js/percentageWorker/index.js` targets a separate legacy listing and
  is not loaded by the current platform index.

## Scope Model

- `Plan`, `RoleTemplate`, `Tenant`, `PercentageWorker` and the platform audit
  log are global platform data and intentionally do not use `TenantScope`.
- `TenantRoleController` uses explicit `tenant_id` constraints; this is the
  only tenant-bound list in this phase.
- Platform administrators do not use the normal active tenant/company context.
- No frontend scope, company filter or branch filter was invented.

## Mutating Actions and Deletion

- None of the seven platform route groups defines an individual DELETE or a
  bulk-delete endpoint.
- Plans, templates, tenant roles and tenants use status toggles.
- Tenant detail additionally supports update and owner password reset.
- Audit is read-only.
- Labor settings expose update only in the platform group.
- QA must not submit create/update/toggle/reset operations.

## Shared Visual Contract

All primary screens reuse the approved Page Header, toolbar, Operational List,
Table Alignment Standard, Row Actions Dropdown, local table scrolling,
pagination and empty-state treatments. Plans reuse Auxiliary Modal; other
existing create/edit page flows remain separate. No Platform-only CSS family
was introduced.

## Sidebar

The `VENTI360` section is visible only to `isPlatformAdmin()`. Its real children
are Superadministración, Planes, Plantillas de perfiles, Roles por tenant,
Auditoría, Tenants and Parámetros laborales. Active-state hooks are present on
the primary entries and the spelling of Parámetros laborales is normalized.

## Backend Sync Open

[OPEN]
Point: Labor-parameter authorization parity.
Impact: the sidebar checks `list_percentageWorker`, while the platform routes
only require `platform.admin`. A platform admin can navigate directly without
that granular permission.
Required backend decision: either declare `platform.admin` sufficient and
remove the extra menu capability, or enforce the capability on index/edit/update.
Frontend can continue: Yes; no route or permission behavior was changed.

[OPEN]
Point: Platform-admin tenant integrity.
Impact: `EnsurePlatformAdmin` intentionally permits a platform admin carrying
historical `tenant_id` data.
Required backend decision: add an administrative integrity report or migration
once ownership rules are approved; do not infer a frontend tenant context.
Frontend can continue: Yes.

[OPEN]
Point: Granular platform capabilities.
Impact: plans, templates, tenant roles, audit and tenants rely exclusively on
the broad `platform.admin` middleware. There are no per-action permissions.
Required backend decision: confirm broad platform authority or introduce an
explicit capability matrix before delegating platform access.
Frontend can continue: Yes; the current guard remains authoritative.

## Revisit Triggers

- Platform access is delegated beyond trusted platform administrators.
- A destructive delete or bulk endpoint is introduced.
- Tenant ownership is moved from explicit controller filters to a global scope.
- Labor settings receive new keys or company-specific overrides.
- Audit retention or immutable export becomes a product requirement.
