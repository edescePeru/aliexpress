# REAL NEXT — Global Surface Spacing Audit

Date: 2026-11-11. Scope: spacing and structural consistency only.

**VENTI NEXT GLOBAL SURFACE SPACING AUDIT — PASS**

## Coverage and limits

- 67 registered GET routes in `config/venti-next.php`; all 67 visited in Laravel.
- 14 routes with a spacing issue or affected conditional fallback; 53 without an identified spacing defect in the inspected state.
- 536 final viewport measurements: 67 routes × 320/375/390/576/768/992/1200/1440. Global overflow: **0** at every final measurement.
- Cajas create/edit and Subtipos create/edit: 32 additional viewport measurements; document and modal overflow 0. No Save actions.
- Role Templates Create and Edit: **PASS**, including loaded 108 permission-module cards. Desktop title/code/description/permissions/first module x=303px (20px inside body). Mobile 375px: code/description/first module x=33px; right edge327px (16px inset). Search includes its expected icon prefix.
- Platform Admin used for platform routes. Owner EDESCE used for operational routes: EDESCE company1, tenant1, active OUTLET DE EDESCE branch8. No context rules were changed. Branch edit inspected branch1 as an authorized company configuration resource.
- No business records created, updated or deleted. No permission forms submitted. No backend, routes, payloads, JS, colors or typography changed.
- This is a spacing audit of the rendered states, not exhaustive authorization/business validation. Material configuration-required fallback was corrected and compiled statically; the configured EDESCE account renders the normal material form. Validation-error states and every transfer status were not induced through writes.
- Two additional Platform routes were inspected to confirm scope: `platformTypeTax.index` (`/dashboard/plataforma/tipos-impuesto`) and `platformNubefact.index` (`/dashboard/plataforma/nubefact`) do **not** load `venti-next.css` and are absent from the allowlist. They remain Legacy, unmodified. Store Web is a separate frozen visual system, not this allowlist.

## Causes and shared fixes

1. `.content > .card > .card-body { padding: 0; }` is intentional for operational composition. Raw Platform forms omitted the section wrapper.
2. Permission footers still compensated for an old padded parent with -20px/-10px margins. Removed that compensation and private padding; retained sticky behavior and existing surface appearance.
3. A redundant enclosing permission card shifted the first module relative to the fields/search. Module cards remain; their enclosing section now owns the common gutter.
4. `.row.next-form-section` mixed Bootstrap negative margins and surface padding. Row now lives inside the section.
5. Existing `container-fluid` and `p-3 p-md-4` wrappers did not match approved section gutters/vertical spacing. Reused `next-form-section` at surface boundaries.
6. Subcategories' DataTables count/sort summary renders from `drawCallback` before `initComplete` adds wrapping utilities. Added **one** shared selector in `venti-next.css` (flex/wrap with existing 4px/8px gaps), safe both during loading and afterward. No table CSS changed.

No per-route CSS, IDs, nth-child, new tokens or individual input/button margins.
The CSS addition applies to the shared DataTables summary; legacy negative-margin rules were removed from the existing permission styles partial.

## Route matrix

Responsive PASS means the eight requested widths rendered with zero document overflow in the final check. Table scroll is intentionally local. Surface entries identify the current Blade; the route URL retains its actual parameter contract.

| Ruta | Surface | Problema encontrado | Fix aplicado | Shared pattern | Responsive PASS |
| --- | --- | --- | --- | --- | --- |
| `tenantBranch.index`<br>`/dashboard/configuracion/empresas/{companyId}/sucursales` | `tenantBranch.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `tenantBranch.create`<br>`/dashboard/configuracion/empresas/{companyId}/sucursales/crear` | `tenantBranch.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `tenantBranch.edit`<br>`/dashboard/configuracion/sucursales/{id}/editar` | `tenantBranch.edit` | F: nested warehouse card only had container gutter | Padded parent, optional presentation class; company legacy consumer unchanged | next-form-section | PASS — 8/8 |
| `dashboard.principal`<br>`/dashboard/principal` | `dashboard.dashboard` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `materialtype.index`<br>`/dashboard/TiposMateriales` | `materialtype.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `materialtype.create`<br>`/dashboard/crear/tipomaterial` | `materialtype.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `materialtype.edit`<br>`/dashboard/editar/tipomaterial/{id}` | `materialtype.edit` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `subtype.index`<br>`/dashboard/Subtipos` | `subtype.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `subtype.create`<br>`/dashboard/crear/subtipo` | `subtype.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `subtype.edit`<br>`/dashboard/editar/subtipo/{id}` | `subtype.edit` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `category.index`<br>`/dashboard/Categorias` | `category.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `category.create`<br>`/dashboard/crear/categoria` | `category.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `category.edit`<br>`/dashboard/editar/categoria/{id}` | `category.edit` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `subcategory.index`<br>`/dashboard/Subcategorias` | `subcategory.index` | Loading summary overflow 87/32/17px at 320/375/390 before initComplete | One shared CSS rule makes DataTables summary wrap from first render | next-list-summary .dataTables_info | PASS — 8/8 |
| `subcategory.create`<br>`/dashboard/crear/subcategoria` | `subcategory.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `subcategory.edit`<br>`/dashboard/editar/subcategoria/{id}` | `subcategory.edit` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `exampler.index`<br>`/dashboard/Modelos` | `exampler.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `exampler.create`<br>`/dashboard/crear/modelo` | `exampler.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `exampler.edit`<br>`/dashboard/editar/modelo/{id}` | `exampler.edit` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `brand.index`<br>`/dashboard/Marcas` | `brand.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `brand.create`<br>`/dashboard/crear/marca` | `brand.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `brand.edit`<br>`/dashboard/editar/marca/{id}` | `brand.edit` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `typescrap.index`<br>`/dashboard/Retacerías` | `typeScrap.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `typescrap.create`<br>`/dashboard/crear/retaceria` | `typeScrap.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `typescrap.edit`<br>`/dashboard/editar/retaceria/{id}` | `typeScrap.edit` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `unitmeasure.index`<br>`/dashboard/Unidades` | `unitMeasure.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `unitmeasure.create`<br>`/dashboard/crear/unidad` | `unitMeasure.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `unitmeasure.edit`<br>`/dashboard/editar/unidad/{id}` | `unitMeasure.edit` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `genero.index`<br>`/dashboard/generos` | `genero.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `genero.create`<br>`/dashboard/crear/genero` | `genero.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `genero.edit`<br>`/dashboard/editar/genero/{id}` | `genero.edit` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `talla.index`<br>`/dashboard/tallas` | `talla.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `talla.create`<br>`/dashboard/crear/talla` | `talla.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `talla.edit`<br>`/dashboard/editar/talla/{id}` | `talla.edit` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `material.create`<br>`/dashboard/crear/material` | `materialDetailSetting.required`, `material.create` | A/H: required-configuration fallback had a raw action | Wrap fallback content | next-form-section | PASS — 8/8 |
| `material.edit`<br>`/dashboard/editar/material/{id}` | `materialDetailSetting.required`, `material.edit` | A/H: same required-configuration fallback | Shared fallback wrapper | next-form-section | PASS — 8/8 |
| `material.indexV2`<br>`/dashboard/listado/materiales/v2` | `material.indexv2` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `warehouseTransfer.index`<br>`/dashboard/traslados/almacenes` | `warehouseTransfer.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `warehouseTransfer.create`<br>`/dashboard/traslados/almacenes/crear` | `warehouseTransfer.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `warehouseTransfer.show`<br>`/dashboard/traslados/almacenes/{id}` | `warehouseTransfer.show` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `stocks.files.index`<br>`/dashboard/importar/archivos/stocks/materiales` | `files.stockFiles` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `puntoVenta.list`<br>`/dashboard/listado/ventas` | `puntoVenta.list` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `quoteSale.index`<br>`/dashboard/cotizaciones/venta` | `quoteSale.general` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `quoteSale.index.facturadas`<br>`/dashboard/cotizaciones/venta/facturadas` | `quoteSale.facturadas` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `quoteSale.show`<br>`/dashboard/ver/cotizacion/venta/{quote}` | `quoteSale.show` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `quoteSale.pendingBranches`<br>`/dashboard/cotizaciones/venta/pendientes-sucursal` | `quoteSale.pendingBranches` | E: pagination directly against card boundary | Wrap pagination without changing links | next-list-pagination | PASS — 8/8 |
| `quoteSale.pendingBranches.show`<br>`/dashboard/cotizaciones/venta/pendientes-sucursal/{id}` | `quoteSale.pendingBranchDetail` | B/D/H: row and section on same element; bare heading/footer text | Separate row from section; padded heading and note; preserve edge-to-edge table | next-form-section + next-list-toolbar | PASS — 8/8 |
| `settings.material-details.index`<br>`/dashboard/settings/material-details` | `materialDetailSetting.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `cashBox.index`<br>`/dashboard/cash-boxes` | `cashBox.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `cashBoxSubtype.index`<br>`/dashboard/cash-box-subtypes` | `cashBoxSubtype.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `color.index`<br>`/dashboard/colors` | `color.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `color.create`<br>`/dashboard/crear/color` | `color.create` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `color.edit`<br>`/dashboard/editar/color/{id}` | `color.edit` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `platform.dashboard`<br>`/dashboard/plataforma` | `platform.dashboard.index` | Different 16/24px body gutter versus 20px header | Reuse section scale | next-form-section | PASS — 8/8 |
| `platformActivity.index`<br>`/dashboard/plataforma/activity-log` | `platform.activityLog.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `platformTenant.index`<br>`/dashboard/plataforma/tenants` | `platform.tenants.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `platformTenant.create`<br>`/dashboard/plataforma/tenants/create` | `platform.tenants.create` | F: no vertical separation before nested cards | Replace outer container wrapper | next-form-section | PASS — 8/8 |
| `platformTenant.show`<br>`/dashboard/plataforma/tenants/{id}` | `platform.tenants.show` | B/F: heading and nested surfaces lack vertical inset | Replace outer container wrapper | next-form-section | PASS — 8/8 |
| `plan.index`<br>`/dashboard/plataforma/plans` | `plan.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `roleTemplate.index`<br>`/dashboard/plataforma/role-templates` | `roleTemplate.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `roleTemplate.create`<br>`/dashboard/plataforma/role-templates/create` | `roleTemplate.create` | A/B/C/D/E/H: raw form, nested axis drift, negative footer margins | Shared identity/permissions sections; remove redundant enclosing permission card; retain module cards | next-form-section + next-list-toolbar + next-toolbar-actions | PASS — 8/8 |
| `roleTemplate.edit`<br>`/dashboard/plataforma/role-templates/{id}/edit` | `roleTemplate.edit` | A/B/C/D/E/H: same shared partial | Same composition as create | Same permission partial | PASS — 8/8 |
| `tenantRole.index`<br>`/dashboard/plataforma/tenant-roles` | `tenantRole.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `tenantRole.create`<br>`/dashboard/plataforma/tenants/{tenantId}/roles/create` | `tenantRole.create` | A/B/C/D/E/H: same raw permissions pattern | Apply the same section/action composition | next-form-section + next-list-toolbar + next-toolbar-actions | PASS — 8/8 |
| `tenantRole.edit`<br>`/dashboard/plataforma/tenants/{tenantId}/roles/{roleId}/edit` | `tenantRole.edit` | A/B/C/D/E/H: same shared partial | Same composition as create | Same tenant-role partial | PASS — 8/8 |
| `platformPercentageWorker.index`<br>`/dashboard/plataforma/parametros-laborales` | `percentageWorker.index` | No spacing defect identified | No change | Existing section/list/modal composition | PASS — 8/8 |
| `platformPercentageWorker.edit`<br>`/dashboard/plataforma/parametros-laborales/{id}/editar` | `percentageWorker.edit` | A/D/E/H: raw form and action group | Padded form wrapper and wrapping action gap | next-form-section + next-toolbar-actions | PASS — 8/8 |

## Evidence and reproducibility

- Source discovery: `config/venti-next.php`, route collection, reflection of real controller methods, `layouts/appAdmin2.blade.php`, shared CSS and current Blade partials. No controller was modified.
- Re-run route inventory with PHP 7.3: `php scripts/qa/venti-next-spacing-inventory.php`. This only reads route/source metadata.
- Use existing, non-deleted records for parameterized routes. QA used configuration IDs1 except Category2/Subcategory2, Material1, branch1/company1, template3, tenant1/role17, labor parameter from its list, transfer3, historical quote78, current quote82.
- Historical ID79 returned404 in the current branch context. The actual current list exposes quote82; that route rendered successfully. This is not a spacing blocker, and the guard was not relaxed.
- Final measurements and modal results are in the external artifact directory `spacing-results-final.json`; screenshots `spacing-role-create-desktop.jpg`, `spacing-role-create-mobile.jpg`, `spacing-role-edit-desktop.jpg`, `spacing-branch-mobile.jpg`. Artifact root: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`.
- A wide descendant table alone is not global overflow. Compare root `scrollWidth` and `clientWidth`; inspect scroll containers and all action wrappers. Measure before and after AJAX initialization.
- Main visual proof: Role Templates before/after desktop and mobile; permission actions wrap within the section, and sticky actions remain inside the surface.

## Validation

- PHP7.3 Blade compilation: PASS.
- Changed Blade contract attributes compared against HEAD: IDs, names, form targets, methods, data attributes and authorization/loop directives preserved.
- Functional JavaScript unchanged; its existing selectors/permission module classes retained.
- `git diff --check`: PASS.
- No commit made.
- `AGENTS.md` and `docs/VENTI-NEXT-COMPONENT-PATTERNS.md` now define Surface, Header, Body, Section, Toolbar, Action Bar, Nested Surface and the edge-to-edge table exception.

## Remaining routes

No allowlisted route remains unvisited. Legacy tax/Nubefact routes are explicitly outside the Next stylesheet scope and were not migrated in this phase. Conditional error/fallback/status states have the limits stated above; PASS does not claim every business state or tenant was exercised.
