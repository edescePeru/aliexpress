# REAL NEXT Material Configuration CRUD — Unit Pilot Contract

Status: **APPROVED WITH BACKEND SYNC OPEN**  
Reference route: `/dashboard/Unidades`  
Validated: 2026-09-26

## Current Contract

Unidad de medida is a tenant-scoped CRUD backed by `UnitMeasure`,
`UnitMeasureController`, tenant-aware form requests, three Blade pages, and
AJAX forms. Create and edit remain separate pages; delete remains a Bootstrap
confirmation modal.

| Operation | Route | Handler | Request / payload | Response / flow |
| --- | --- | --- | --- | --- |
| Index | `GET /dashboard/Unidades` (`unitmeasure.index`) | `index` | none | Blade list |
| Data | `GET /dashboard/all/unitmeasure` | `getUnitMeasure` | none | DataTables JSON with all tenant-visible rows |
| Create | `GET /dashboard/crear/unidad` (`unitmeasure.create`) | `create` | none | separate Blade page |
| Store | `POST /dashboard/unitmeasure/store` (`unitmeasure.store`) | `store` | `name`, optional `description`, CSRF; AJAX `FormData` | JSON success/error |
| Edit | `GET /dashboard/editar/unidad/{id}` (`unitmeasure.edit`) | `edit` | tenant-visible route id | separate Blade page |
| Update | `POST /dashboard/unitmeasure/update` (`unitmeasure.update`) | `update` | `unitMeasure_id`, `name`, optional `description`, CSRF; AJAX `FormData` | JSON and index URL |
| Delete | `POST /dashboard/unitmeasure/destroy` (`unitmeasure.destroy`) | `destroy` | `unitMeasure_id`, CSRF; AJAX `FormData` | JSON success/error |
| Bulk delete | `POST /dashboard/unitmeasure/delete-multiple` | `deleteMultiple` | `ids[]`, CSRF | JSON; not approved for frontend execution |

DataTables 1.10.20 remains the list engine. It performs client-side search,
ordering, length, info and pagination after the existing AJAX endpoint returns
the complete tenant-visible collection. The implementation does not introduce
server-side DataTables or replace the plugin.

Validation remains authoritative in `StoreUnitMeasureRequest`,
`UpdateUnitMeasureRequest`, and `DeleteUnitMeasureRequest`: names are required,
strings, maximum 255 characters, and unique within the current tenant and
non-deleted records; description is optional and maximum 255 characters.
Update and individual delete also validate that the id exists in the current
tenant.

## Shared CRUD Pattern

The pilot promotes the following composition as **Material Configuration
CRUD**:

- contextual Page Header and breadcrumb;
- compact page toolbar with context at left and permitted actions at right;
- Venti Next Operational List wrapping DataTables' length, search, summary,
  table and pagination controls;
- the shared table-alignment standard (text left, compact identifiers/numbers
  center, money right, status/actions center, header matching body);
- one Row Actions Dropdown per row, rendered inside the responsive table and
  portalized to `body` by the shared `admin2.js` behavior;
- destructive action last in the row menu;
- reusable empty and zero-results states;
- separate create/edit pages using Page Header, toolbar and Form Section when
  that is the existing functional flow;
- `.next-aux-modal` plus `.next-aux-modal-form` for destructive confirmation,
  with Cancelar focused on open and trigger focus restored on close;
- Bootstrap/AdminLTE/Venti Next utilities only; no catalog-specific CSS.

Literal reuse candidates are index Blade structure, DataTables `dom` layout,
language and accessibility setup, conditional-column construction, row action
renderer, empty state, delete modal, focus lifecycle, and the two-field form
shell. Relational catalogs must add only their real parent control and request
contract.

## Unit-specific Differences

- Fields are only `name` and optional `description`.
- There is no status, code, parent catalog, filter set, or create option list.
- Create/edit use separate pages rather than create/edit modals.
- The list has no Unit-specific visual component; its only module-owned text is
  the unit vocabulary and endpoint mapping.

## Permissions

| Capability | Permission middleware | Frontend effect |
| --- | --- | --- |
| list / data | `list_unitMeasure` | index and AJAX data access |
| create / store | `create_unitMeasure` | primary action and create flow |
| edit / update | `update_unitMeasure` | Editar row action and edit flow |
| individual delete | `destroy_unitMeasure` | selection column, Eliminar row action and modal |
| bulk delete | **none on the route** | disabled pending backend authorization |

Blade and DataTables columns are permission-aware. Absence of update/destroy
does not leave an empty actions column.

## Multitenancy

`UnitMeasure` uses `BelongsToTenant` and `SoftDeletes`. Controller queries and
route-model lookups use the model scope. Store assigns `tenant_id` through the
trait; update/delete requests constrain ids and uniqueness to
`TenantContext::tenantId()`.

Read-only QA confirmed distinct results under the two supplied owner tenants:
the EDESCE context exposed two records and the Gamarra context one record,
with the active company/local reflected in the shell. Create was reachable in
both contexts, edit targeted only a visible row, and the permitted actions were
consistent. No form was submitted and no record was persisted or deleted.

## Bulk Actions

The table preserves tenant-scoped selection markup and the existing bulk
endpoint mapping, but the bulk action is rendered disabled and guarded in JS.
This is deliberate: the route has no `permission:destroy_unitMeasure`
middleware and the controller accepts a raw `Request` rather than the
tenant-aware delete request. TenantScope prevents cross-tenant selection, but
it does not replace capability authorization.

Bulk delete is therefore **not part of the approved pilot flow** and was not
executed during QA.

## Backend Sync Notes

### [OPEN] Bulk-delete authorization

- **File / endpoint:** `routes/web.php`,
  `POST /dashboard/unitmeasure/delete-multiple`.
- **Impact:** a user able to reach the authenticated dashboard route could call
  a destructive operation without the explicit Unit delete permission.
- **Backend action:** add the same destruction authorization used by the
  individual endpoint and preferably a tenant-aware validated request.
- **Frontend can continue:** Yes; the control remains disabled and guarded.

### [OPEN] Referential delete policy

- **File / endpoint:** `UnitMeasureController::destroy` and
  `UnitMeasureController::deleteMultiple`.
- **Impact:** the controller performs soft delete without an explicit business
  dependency check or a catalog-specific dependency message. Units are
  referenced by materials/stock and other modules; the intended policy is not
  stated by this CRUD contract.
- **Backend action:** define whether referenced units may be soft-deleted and
  return a stable domain message when deletion is denied.
- **Frontend can continue:** Yes for navigation and confirmation; destructive
  QA remains excluded.

## QA Evidence

- DataTables AJAX, search, ordering hooks, length menu, info, pagination and
  Spanish labels loaded correctly.
- Row dropdown was confirmed as a direct `body` child, inside the viewport on
  desktop and mobile, with Editar followed by Eliminar.
- The delete modal was opened but not confirmed; focus moved to Cancelar and
  closing restored the interaction safely.
- Create and edit pages loaded their current endpoints, CSRF and fields; no
  submit occurred.
- 1600×900, 1280×720, 1024×768 and 390×844 produced no document-level
  horizontal overflow. The table remains inside its local responsive wrapper.
- Sidebar hierarchy expands through Materiales > Configuraciones > Unidad de
  medida and the Unit parent/list/create active states are now explicit.

## Visual Parity Correction — REAL NEXT 5.1.1

The first pilot review had correct Venti Next markup but the Unit route names
were absent from `config/venti-next.php`. Consequently `appAdmin2.blade.php`
did not load either the Venti UI compatibility layer or `venti-next.css` for
the CRUD, leaving AdminLTE's `card-primary card-outline` and Bootstrap
DataTables presentation visible.

The allowlist now includes `unitmeasure.index`, `unitmeasure.create`, and
`unitmeasure.edit`. Runtime inspection confirms both `venti.min.css` and
`venti-next.css` on all three routes. The existing approved rules now replace
the blue 3 px outline with the neutral 1 px surface, approved radius/shadow,
Venti Next shell, operational table, controls and pagination.

DataTables retains its functional contract. Its generated DOM is adapted into
the same four-part structure as `material.indexV2`: `next-list-toolbar`,
`next-list-summary`, `next-list-content`, and `next-list-pagination`. Search is
the primary toolbar control, length is secondary, summary presents the result
count and ordering context, and the pagination footer contains both record
range and page controls. Selection uses existing Bootstrap/Venti checkbox
markup. No new CSS or tokens were introduced.

## Revisit Triggers

Revisit this contract when any of the following occurs:

1. bulk delete gains explicit permission middleware and validated input;
2. backend defines the deletion policy for referenced units;
3. DataTables changes from client-side full-collection loading to server-side;
4. create/edit move to modals or their payload changes;
5. Unit Measure gains status, code, company scope, parent relations or custom
   filters;
6. shared CRUD extraction is formalized into Blade/JS partials after adoption
   by additional catalogs.

