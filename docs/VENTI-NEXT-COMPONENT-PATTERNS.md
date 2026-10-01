# Venti Next — Approved Component Patterns

This document records the reusable frontend patterns validated by
`material.create`. The examples are conceptual: real screens must preserve
their backend and JavaScript contracts, permissions, field names, IDs, routes,
and plugin hooks.

## Scope taxonomy

| Pattern | Classification | Approved boundary |
| --- | --- | --- |
| Tokens, canvas, typography, focus, density | GLOBAL NEXT | System-wide visual foundation. |
| Primary/secondary action hierarchy | GLOBAL NEXT | One clear solid primary action; quieter secondary actions. |
| Page header and page toolbar | SHARED COMPONENT | Reusable page context and action composition. |
| Numbered form section header | SHARED COMPONENT | Reusable for long, ordered administrative forms. |
| Form controls and Select2 treatment | SHARED COMPONENT | Reusable when loaded inside a Next screen scope. |
| Auxiliary field action | SHARED COMPONENT | Input/select plus a related create or generate action. |
| Operational panel and upload panel | SHARED COMPONENT | Reusable grouped work surfaces; plugin integration stays local. |
| Operational table and row actions | SHARED COMPONENT | Dense desktop records with restrained semantic actions. |
| Operational list composition | SHARED COMPONENT | Search, optional filters, result context, dense table, and pagination. |
| Row Actions Dropdown | SHARED COMPONENT | One compact trigger; permitted actions keep their original hooks inside a floating menu. |
| Column Visibility Menu | SHARED COMPONENT | Existing visibility controls grouped in one floating panel without replacing their state API. |
| Advanced Operational List | SHARED COMPONENT | Wide, configurable ERP datasets with local scrolling and directly reachable row actions. |
| Toast feedback | SHARED COMPONENT | Existing Toastr API with soft semantic surface, strong semantic edge and floating-layer elevation. |
| Responsive mobile record block | SHARED COMPONENT | Same record DOM reflows below the desktop table breakpoint. |
| Auxiliary modal | SHARED COMPONENT | Neutral header, scrollable body, real footer, focus/reset behavior. |
| Variant mode, generator, table fields and serialization | MATERIAL-SPECIFIC | Material workflow and `variantes_json` contract only. |
| Material stock/package controls | MATERIAL-SPECIFIC | Current material payload and business vocabulary. |
| Material Dropzone callbacks and file keys | MATERIAL-SPECIFIC | Current upload API and `variant_image_N` mapping. |

### Consolidated list-system boundary

| Classification | Selectors / behavior | Boundary |
| --- | --- | --- |
| GLOBAL NEXT | `--next-*` color, spacing, radius, focus and floating-surface tokens | Shared visual vocabulary; no module logic. |
| SHARED LIST COMPONENT | `.next-operational-list`, `.next-list-toolbar`, `.next-list-filters-*`, `.next-list-summary`, `.next-data-table`, `.next-row-actions*`, `.next-column-visibility*`, `.next-list-pagination` | Presentation and responsive composition validated by Sales and Materials V2. |
| SALES-SPECIFIC | payment-state cells, dispatch switches, electronic-document states and `.pp-*` workflow overlays | Sales vocabulary, permissions, templates and callbacks remain owned by `puntoVenta.list`. |
| MATERIAL-LIST-SPECIFIC | `.next-materials-table` width/sticky action cell, stock/image cell actions, rotation state and visibility checkbox state | Advanced dataset behavior and material business hooks remain owned by `material.indexV2`. |

The shared selectors define appearance only. Screen-specific endpoints, AJAX
parameters, templates, permissions, status text, `data-*` hooks and callbacks
must never be inferred from the shared CSS.

## Page header

Use the heading for durable page context and the toolbar for the current record
state and actions. The solid primary action is last in reading order.

```html
<div class="next-page-heading">
  <span class="next-page-eyebrow">Módulo</span>
  <h1 class="page-title">Crear registro</h1>
  <p class="next-page-description">Descripción breve y operativa.</p>
</div>

<div class="next-page-toolbar">
  <div class="next-toolbar-context">
    <strong>Ficha del registro</strong>
    <span>Completa los datos requeridos.</span>
  </div>
  <div class="next-toolbar-actions">
    <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
    <button type="button" class="btn btn-primary">Guardar</button>
  </div>
</div>
```

Do not add decorative actions to the header or use multiple solid primary
buttons. Page actions must remain reachable and must not alter the form submit
contract.

## Primary and secondary actions

- Primary: solid brand treatment for the single commit action.
- Secondary: neutral/outline treatment for cancel, back, or non-committing
  operations.
- Auxiliary: compact outline treatment adjacent to the field it supports.
- Destructive row action: restrained danger outline, never the dominant page
  action.
- Every focus state remains visible; icon-only actions require an accessible
  name and tooltip/title where useful.

## Form section

Long ERP forms use clear sections rather than disconnected floating cards.
The section number is navigational hierarchy, not decoration.

```html
<section class="next-form-section" aria-labelledby="section-identity-title">
  <div class="next-section-header">
    <div>
      <span class="next-section-kicker">01</span>
      <h2 id="section-identity-title">Identidad</h2>
      <p>Describe the purpose of this group.</p>
    </div>
  </div>
  <div class="row">
    <!-- Existing Bootstrap form markup and contractual fields. -->
  </div>
</section>
```

Use compact labels, consistent vertical rhythm, and the existing Bootstrap
grid. Required markers are concise text. Do not increase spacing merely to make
the screen feel more decorative.

## Auxiliary field action

An action that creates or generates a value belongs visually to its control.
The control keeps the flexible width; the action keeps a stable compact width.

```html
<div class="input-group next-field-action">
  <select class="form-control select2" id="contractual-id" name="contractual_name"></select>
  <div class="input-group-append">
    <button type="button" class="btn btn-outline-primary next-field-action-button"
            aria-label="Crear opción">+</button>
  </div>
</div>
```

Preserve the input/select ID, name, Select2 initialization, modal target, and
dependent-select callbacks. This pattern does not create a new data API.

## Operational table

Desktop operational data favors scanning density, neutral headers, one-line
controls where practical, and locally contained horizontal overflow.

```html
<div class="table-responsive">
  <table class="table table-sm next-variants-table">
    <thead>...</thead>
    <tbody>...</tbody>
  </table>
</div>
```

`next-variants-table` is the material implementation; future screens should
reuse the visual principles through their approved shared table pattern rather
than copying material field selectors. Global document overflow is not
acceptable. Row actions remain compact and subordinate to page actions.

## Table Alignment Standard

Operational tables use semantic alignment consistently in both `th` and `td`:

| Data type | Alignment | Preferred Bootstrap utility |
| --- | --- | --- |
| Identifiers and short codes | Center | `text-center` |
| Descriptive text | Left | `text-left` |
| Units | Center | `text-center` |
| Quantities and stock | Center | `text-center` |
| Currency and monetary values | Right | `text-right` |
| Percentages | Right | `text-right` |
| Dates | Center | `text-center` |
| Statuses and badges | Center | `text-center` |
| Images, icons and cell actions | Center | `text-center` |
| Row actions | Center | `text-center` |

The header and body of a column must use the same alignment. Prefer Bootstrap
utilities or an existing shared Venti Next class; do not create screen- or
column-specific CSS when those utilities solve the requirement. Any exception
must apply to the whole semantic column and document why the data scans better
with a different alignment.

## Row Actions Dropdown

The row-action dropdown is approved after validation in `puntoVenta.list` and
`material.indexV2`. It replaces a horizontal cluster of icon buttons with one
neutral trigger while preserving the action elements themselves.

```html
<td data-buttons>
  <div class="dropdown next-row-actions">
    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
            aria-label="Abrir acciones del registro">
      <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-right next-row-actions-menu">
      <a class="dropdown-item" data-contractual-action href="...">
        <i class="fas fa-pen next-row-action-item-icon" aria-hidden="true"></i>
        <span>Editar</span>
      </a>
      <button type="button" class="dropdown-item next-row-action-danger" data-contractual-delete>
        <i class="fas fa-trash next-row-action-item-icon" aria-hidden="true"></i>
        <span>Eliminar</span>
      </button>
    </div>
  </div>
</td>
```

- Render only actions allowed by the record state and current permissions.
- Keep contractual IDs, `data-*` hooks, URLs, and delegated callbacks on the
  actionable item, not on the dropdown wrapper.
- Place a destructive action last and separate it with the shared danger
  treatment.
- Disabled actions keep their native `disabled`/`.disabled` state, remain
  visibly unavailable, and do not acquire hover emphasis.
- Common actions stay neutral. A genuinely highlighted functional action may
  use a contained brand accent, but a warning/gold fill is not the default
  action language.
- Bootstrap retains outside-click, Escape, focus, and `aria-expanded`
  behavior. The trigger requires a record-specific accessible name.
- Use the shared floating-surface tokens; do not reproduce menu shadows or
  borders per screen.

The trigger is a fixed 2rem square with the ellipsis icon and a visible focus
outline. Menu items use icon + label, compact vertical rhythm and wrapping for
long labels. The menu has a bounded height and scrolls internally rather than
escaping the viewport.

## Floating surface

Dropdowns, the Column Visibility Menu and future popovers use one elevated
surface contract:

- `--next-floating-surface` for the near-white surface;
- `--next-floating-border` for separation from white tables/cards;
- `--next-floating-shadow` for a stronger elevation than a normal work card.

These tokens are intentionally reserved for temporary floating layers. Normal
cards and list surfaces continue to rely on borders and restrained shadows.
Screens must not reproduce floating colors or shadows with local values.

## Toast feedback

Toastr remains the notification engine. Venti Next changes only its visual
surface; calls, options, callbacks, timing, position and generated markup stay
owned by the plugin and the invoking screen.

- `success`, `warning`, `error` and `info` use their existing `--next-*-soft`
  surface and solid semantic token on the leading border and progress bar.
- Title and message remain dark for reliable contrast on the soft surface.
- The shared floating shadow gives more presence than a normal card without
  approaching modal or SweetAlert weight.
- The close control remains visible, keyboard focusable and visually quiet.
- Existing width, padding, radius, position and responsive behavior are not
  changed.
- Multiple simultaneous notifications must remain readable and fit inside the
  mobile viewport without document overflow.

The bundled Toastr icons are white bitmap backgrounds. Legacy intentionally
positions them outside the visible surface; restoring them on a pale semantic
background would require brittle image replacement or `!important` overrides.
Venti Next therefore relies on surface, border and progress color rather than
introducing an icon hack.

## Column Visibility Menu

When a wide operational table already owns a show/hide column API, group those
existing controls in one `Columnas` dropdown. This pattern is not permission
management and does not create a second visibility state.

```html
<div class="dropdown next-column-visibility">
  <button type="button" class="btn btn-outline-secondary dropdown-toggle next-column-visibility-trigger"
          data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    Columnas
  </button>
  <div class="dropdown-menu dropdown-menu-right next-column-visibility-menu">
    <div class="next-column-visibility-grid">
      <div class="custom-control custom-switch next-column-visibility-option">
        <input type="checkbox" checked class="custom-control-input"
               id="contractual-switch" data-column="contractual-column">
        <label class="custom-control-label" for="contractual-switch">Columna</label>
      </div>
    </div>
  </div>
</div>
```

- Preserve every original checkbox ID, `data-column` value, default state,
  change handler, and rendering selector.
- Keep the panel open while switches are changed so several columns can be
  configured in one pass.
- The trigger is keyboard operable, exposes `aria-expanded`, and the menu
  closes through normal Bootstrap outside-click/Escape behavior.
- Switch focus remains visible. On mobile the menu fits inside the viewport
  and the option grid reduces to one column at the narrowest breakpoint.
- The panel uses `--next-floating-surface`, `--next-floating-border`, and
  `--next-floating-shadow` and remains within the mobile viewport.
- Do not add column hiding to a screen that did not already support it.

## Operational list

The approved list composition is a continuous working surface rather than a
stack of decorative cards. It separates query controls, result context, data,
and navigation while preserving each screen's existing endpoint and rendering
hooks.

```html
<section class="next-operational-list">
  <div class="next-list-toolbar">
    <div class="next-list-search">
      <!-- Existing contractual search input and submit action. -->
    </div>
    <button type="button" class="btn btn-outline-secondary next-list-advanced-toggle"
            aria-controls="advanced-filters" aria-expanded="false">
      Filtros avanzados
    </button>
  </div>

  <div class="next-list-filters" id="advanced-filters">
    <!-- Existing Bootstrap form controls and plugin hooks. -->
  </div>

  <div class="next-list-summary">
    <div><strong class="next-list-count">0</strong> resultados</div>
    <span>Orden actual</span>
  </div>

  <div class="next-list-content table-responsive" tabindex="0">
    <table class="table table-sm next-data-table">...</table>
  </div>

  <nav class="next-list-pagination" aria-label="Paginacion de resultados">...</nav>
</section>
```

- The simple search remains the quickest path; advanced filters are optional
  and disclose inside the same surface.
- The toggle reflects its actual state with `aria-expanded` and references the
  panel with `aria-controls`.
- Filter controls use the Bootstrap grid and stack naturally at narrower
  breakpoints; they must not create page-level horizontal overflow.
- The summary states both the result count and the active ordering context.
- Wide datasets scroll only inside `.next-list-content`; columns are not hidden
  unless the existing workflow already owns that behavior.
- Pagination remains part of the list surface and preserves the screen's
  existing page-loading mechanism.
- Dynamic row templates, status vocabulary, action hooks, plugin data
  attributes, and AJAX response formats are functional contracts, not visual
  implementation details.

`puntoVenta.list` and `material.indexV2` validate this shared composition on
two different operational workflows. Their module-specific status decisions,
permissions, modals, endpoint callbacks, and payloads must not be copied as a
generic component API.

### Approved operational flow and density

The stable sequence is:

1. **Page Header** — durable route and module context.
2. **Primary Toolbar** — quick search first; the search action is the solid
   primary control. Advanced filters and configuration controls remain quiet.
3. **Advanced Filters** — optional, grouped in the same work surface and
   disclosed without displacing the dataset into a separate card.
4. **Result Summary** — compact count and ordering context.
5. **Operational Table** — dense header, compact rows, tabular numeric values,
   muted semantic statuses and a single row-action trigger.
6. **Pagination** — visually attached to the dataset and wired to the existing
   pagination mechanism.

Toolbar and filter padding use the shared spacing scale. Table headers use
compact uppercase labels; body cells keep a single-line operational rhythm
when content permits and are allowed to grow for legitimate multiline states.
Pagination wraps rather than causing page overflow.

### Status and badge language

- `success`: completed, accepted, available or healthy.
- `warning`: pending, attention required or intermediate processing.
- `danger`: rejected, destructive, unavailable or failed.
- `info`: informative processing state that must not compete with the primary
  page action.
- `neutral`: inactive, historical, annulled or context-only states where a
  semantic alert would overstate urgency.

All status badges use muted semantic surfaces and compact text. Modules own
their wording and state mapping; the shared layer owns only the visual
treatment. Full-cell semantic surfaces are retained only where the existing
workflow needs the entire cell to communicate a payment condition.

## Advanced Operational List

Use the advanced variant when a list has many columns, multiple optional
filters, existing column visibility, and several permitted row operations.
It extends the Operational List rather than replacing it.

- Keep a single continuous surface: page actions, query toolbar, result
  context, dataset, and pagination.
- Wide tables receive a deliberate minimum width and scroll only inside the
  table region. Never introduce document-level horizontal overflow.
- A sticky actions cell may keep the approved row-action trigger reachable;
  it must use neutral surfaces and must not hide or duplicate record data.
- Numeric stock or monetary cells use tabular figures and compact alignment.
- Image, inventory, and similar cell actions remain compact and neutral when
  they are distinct from the row action menu.
- Semantic statuses use muted badges, not saturated cell backgrounds.
- The empty state spans the current visible-column count plus the actions
  column and retains the normal header context.

`material.indexV2` is the reference implementation for this advanced list
variant. Its inventory vocabulary and auxiliary modal contracts remain
material-specific.

The advanced variant adds only: column visibility when an API already exists,
more columns and deliberate local horizontal scrolling, an optional sticky
actions cell, compact dataset configuration controls and additional contextual
actions. It does not define a separate toolbar, status, dropdown or pagination
language.

## Material Configuration CRUD

Use this shared pattern for compact material-maintenance catalogs. It combines
the existing Page Header, page toolbar, Operational List, table alignment, Row
Actions Dropdown, Form Section and Auxiliary Modal contracts; it does not
introduce a second table, dropdown, form or modal family.

- Preserve the catalog's real routes, request fields, AJAX behavior,
  DataTables version, permissions and tenant/company scope.
- Keep create/edit in their existing flow. Separate pages use the standard
  header, toolbar and form sections; existing modal flows use
  `.next-aux-modal` and `.next-aux-modal-form`.
- Let DataTables own length, quick search, ordering, result info and
  pagination. Place its generated controls inside the Operational List layout
  instead of recreating them.
- Build columns from effective permissions so selection and actions columns do
  not render empty. Header and body follow the Table Alignment Standard.
- Render one ellipsis trigger per actionable row. Put non-destructive actions
  first, an optional separator next, and destructive actions last. Responsive
  tables use the shared body-portal behavior so menus are not clipped.
- Use one empty state for an empty catalog and a distinct zero-results state
  for an active search. A permitted primary create link may appear in the
  empty catalog state.
- Confirm individual destruction through the approved Auxiliary Modal. Focus
  Cancelar when the modal finishes opening and restore focus to the row trigger
  when it closes.
- Expose bulk selection only when the endpoint has explicit authorization,
  tenant-safe validation and a confirmed business dependency policy. A
  tenant scope alone is not destructive-action authorization.
- Keep the page dense and allow only local table scrolling when a relational
  catalog adds enough columns to exceed the viewport. Never add global
  overflow or catalog-specific breakpoints.
- Prefer Bootstrap 4/AdminLTE/Venti Next classes and shared JS. Catalog-specific
  CSS is not part of this pattern.

`unitMeasure` is the pilot implementation. Its field vocabulary and endpoint
names are module-specific; the composition and interaction rules above are the
reusable contract for categories, subcategories, material types/subtypes,
gender, sizes, colors, brands, models and scrap types.

## Responsive list contract

- **1600×900:** full toolbar and dense table; use the available width without
  inflating row height.
- **1280×720:** preserve the same hierarchy and keep wide datasets inside the
  local horizontal scroller.
- **1024×768:** allow toolbar controls to wrap predictably; configuration and
  row actions remain reachable.
- **390×844:** stack the quick-search row and summary/pagination blocks, keep
  controls touch-usable, constrain floating menus to the viewport, and retain
  all columns through local scrolling instead of silently hiding data.

Sticky action cells are permitted only for advanced wide datasets and must
track header, row and hover surfaces so the fixed edge never looks detached.

## Mobile record block

At the validated responsive breakpoint, the same table row becomes a labelled
record block. Do not duplicate records into a second mobile-only DOM tree.

```html
<tr class="item-record">
  <td data-label="Identity">...</td>
  <td data-label="SKU">...</td>
  <td data-label="Status">...</td>
  <td data-label="Action">...</td>
</tr>
```

The record block uses the cell's `data-label` as its mobile label, preserves
source order and form controls, and allows long file names or values to wrap or
truncate without widening the viewport. Collapsible record cards remain a
future option only after real high-volume usage proves the need.

## Auxiliary modal

Use `.next-aux-modal` for small create/edit tasks launched from a parent form.
The form owns header, body, and footer so button behavior remains explicit.

```html
<div class="modal fade next-aux-modal" id="contractualModal" tabindex="-1"
     role="dialog" aria-labelledby="contractualModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <form class="next-aux-modal-form" id="contractualForm" data-url="...">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="contractualModalLabel">Nuevo registro</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">...</div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary"
                  data-modal-cancel data-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </form>
  </div>
</div>
```

The modal uses a neutral header, bordered surfaces, a scrollable body, and a
real footer. Opening focus moves to the designated field; Cancelar resets only
the local form and closes without a request; closing returns focus to the
trigger when possible. Bootstrap Escape and focus containment remain active.

## Material-specific implementation

The following must not be promoted by copying their selectors into unrelated
modules:

- `.next-material-page` as the current screen scope;
- `.next-variant-mode`, `.next-variant-options`, and the simple/variant toggle;
- `.next-stock-grid`, `.next-package-control`, and material inventory switches;
- `.next-variants-toolbar`, `.next-variants-generate`, `.next-variants-results`,
  `.next-variants-table`, and `.next-variant-*` field selectors;
- `#image-dropzone`, material image callbacks, variant file inputs, and
  `variant_image_N`/`image_key` coupling;
- the exact talla/color generation workflow and `variantes_json` payload.

Their visual lessons may inform a future shared component, but their backend
and JavaScript contracts remain owned by Material Create.

## Responsive and accessibility baseline

- Validate at 1600×900, 1280×720, 1024×768, and 390×844.
- Desktop remains information-dense; tablet and mobile reflow without global
  horizontal overflow.
- Inputs and actions remain usable at touch widths without becoming oversized.
- Labels, modal title references, unique IDs, keyboard focus, Escape, and focus
  return are part of the component contract.
- A plugin may be visually wrapped, but its API and initialization hooks remain
  unchanged unless a later contract explicitly authorizes a migration.

## Promotion rule

A material-specific pattern becomes shared only after it is validated on a
second real workflow without screen-specific selectors or payload assumptions.
Global promotion requires evidence across multiple modules. Documentation does
not by itself authorize changes to production contracts.

## Executive Dashboard Pattern

Use this composition for a real dashboard that already owns trustworthy
datasets. It adds hierarchy to existing information; it does not authorize new
metrics, inferred statuses or backend aggregation.

1. **Executive Hero** — a restrained brand surface containing page purpose,
   current context and current date. It may greet the authenticated user, but
   must not present decorative or invented figures.
2. **KPI families** — group existing indicators by business meaning. A primary
   KPI supports a recurring operational or financial workflow; secondary KPIs
   remain useful navigation and reference counts. The distinction is visual
   only and must not change permissions, links or values.
3. **Goal surface** — rank information remains compact and scannable. The
   leading positions may receive restrained emphasis; progress color stays
   semantic and never implies data not returned by the backend.
4. **Analytical surfaces** — charts receive more canvas height and stronger
   headers than ordinary cards, while filters remain quiet and keep their
   current endpoints and payloads.
5. **Section rhythm** — alternate calm neutral surfaces, spacing and border
   strength instead of adding decorative widgets. Color is reserved for brand
   hierarchy and semantic meaning.

The pattern reuses `--next-*` tokens, standard focus behavior, Auxiliary Modal
and existing chart/filter contracts. It must collapse to one column without
global overflow. The hero is not a marketing banner: avoid gradients,
oversized typography, decorative statistics and large empty areas.

## Platform Admin Console Pattern

Use the existing Venti Next language for global administration; platform scope
does not create a second design system.

- Identify the area with the restrained eyebrow `Venti360 · Plataforma` and
  retain the standard Page Header, toolbar, surface, form and focus contracts.
- The overview is a directory of real administrative destinations. It must not
  infer totals, health states or tenant metrics that the controller does not
  provide.
- A toolbar with context but no action uses
  `.next-page-toolbar-context-only`; this keeps its meaningful context visible
  on mobile instead of leaving an empty header surface.
- Global catalogs use the Operational List composition. Tenant-bound tools
  place the tenant selector first and treat it as the explicit data boundary.
- Audit views may expose more filters, but those filters remain in one toolbar
  surface and the event dataset keeps local horizontal scrolling.
- State changes such as enable/disable, password reset and save remain
  secondary or destructive actions with their existing confirmations. A
  platform route never implies permission to bypass its backend guard.
- Global settings use the compact table and row-action dropdown even when the
  dataset is server-rendered.

The visual layer must not add a tenant scope to global models, remove an
explicit tenant filter, invent granular permissions or expose a mutating
endpoint that the backend does not already own.
