# Venti Next — Provisional Visual Direction

This document records the visual baseline validated through the first Venti
Next pilots: `material.create`, `puntoVenta.list`, and `material.indexV2`.

The direction is provisional and evolutionary. It is a working standard for
real-screen validation, not a final brand lock. Decisions may be refined after
additional operational screens are reconstructed and tested.

## Product character

Venti Next is a modern ERP interface: professional, operationally dense,
legible, restrained, and efficient for repetitive work. It should have more
contrast and personality than the Legacy compatibility layer without becoming
decorative, oversized, or landing-page-like.

## Validated foundation

- Navy sidebar with controlled selected and hover states.
- White navbar with a subtle divider.
- Light gray-blue application canvas.
- Predominantly white working surfaces.
- `surface-soft` for secondary grouping and contained hover states.
- Visible but restrained neutral borders.
- Dark primary text and clearly subordinate secondary text.
- Intentional blue brand color for focus, selection, and primary actions.
- ERP density: compact controls, tables, and vertical rhythm.
- Border first, shadow second. Shadows are minimal and never carry hierarchy
  alone.

## Forms

- Compact fields with clear labels and visible focus.
- Logical grouping through section headings, borders, and surface changes.
- Primary solid buttons are reserved for the main page action.
- Secondary and auxiliary actions use quieter outline or neutral treatments.
- Related inputs and adjacent actions should read as one composed control.

## Auxiliary modals

- Use the reusable `.next-aux-modal` architecture: neutral header, scrollable
  body, and a real footer contained by the same form.
- Titles and close controls remain dark and restrained; saturated header fills
  are not part of the Venti Next modal pattern.
- Required markers are concise semantic text rather than badges.
- Footer actions follow a consistent order: secondary Cancelar, then primary
  Guardar.
- On mobile, fields stack, both actions remain usable, and modal content stays
  inside the viewport without horizontal overflow.

## Tables and operational lists

- Dense rows designed for fast scanning.
- Neutral headers with strong typography instead of saturated fills.
- Local horizontal overflow is acceptable for genuinely wide datasets; global
  document overflow is not.
- Table actions are predominantly neutral, with semantic accents reserved for
  primary and destructive intent.
- Semantic status colors support scanning but should not dominate the table.

## Surfaces and hierarchy

- Use white as the principal work surface and `surface-soft` for grouped,
  subordinate areas.
- Prefer borders and spacing to heavy shadows.
- Keep radii moderate and consistent with an administrative product.
- Use typography weight and controlled contrast to separate titles, section
  headings, labels, help text, and operational data.

## Evolution rule

New decisions should be tested on a real workflow, across desktop, tablet, and
mobile, before they become shared standards. Existing tokens remain unchanged
until evidence from multiple screens justifies a system-wide adjustment.

For variant-heavy workflows, a possible future mobile evolution is compact
collapsible variant cards when the number of variants is high. This interaction
is intentionally deferred until typical real-world variant counts have been
observed; the current responsive blocks remain the validated baseline.

## Material Create reference baseline

`material.create` is the first complete Venti Next reference screen. It
validates one continuous workflow composed of page context, a primary action
toolbar, sectioned forms, auxiliary field actions, Select2 controls, inventory
configuration, image upload, responsive variant records, and auxiliary modals.

The screen establishes three explicit scopes:

- **GLOBAL NEXT:** tokens, application canvas, shell appearance, typography,
  focus language, action hierarchy, density, and responsive principles.
- **SHARED COMPONENT:** page heading and toolbar, form section heading,
  composed field action, operational panel, upload panel, operational table,
  responsive record block, row action, and auxiliary modal.
- **MATERIAL-SPECIFIC:** variant mode and generation controls, material stock
  fields, material image/Dropzone integration, variant serialization hooks,
  and the exact variant table/card field set.

Shared patterns are documented with conceptual markup and adoption boundaries
in [`VENTI-NEXT-COMPONENT-PATTERNS.md`](VENTI-NEXT-COMPONENT-PATTERNS.md).
Promoting a pattern does not authorize renaming IDs, input names, `data-*`
hooks, routes, or plugin callbacks in an existing workflow.

## Sales List reference baseline

`puntoVenta.list` is the second complete Venti Next reference screen and the
first approved operational-list implementation. It validates a compact simple
search, optional advanced filters, explicit result and ordering context, a
dense wide table, contained horizontal scrolling, restrained semantic status
signals, row actions, custom pagination, and auxiliary workflow modals.

The reusable boundary is the list composition documented in
[`VENTI-NEXT-COMPONENT-PATTERNS.md`](VENTI-NEXT-COMPONENT-PATTERNS.md). Sales
status vocabulary, dispatch controls, action permissions, modal IDs, dynamic
templates, and AJAX callbacks remain screen-specific contracts. A shared
visual pattern never authorizes changing those hooks or their response format.

## Advanced Material List reference baseline

`material.indexV2` validates the advanced operational-list variant: quick and
advanced filters, existing user-controlled column visibility, a dense wide
dataset, compact inventory/image actions, muted rotation states, custom AJAX
pagination, and the shared Row Actions Dropdown.

The dataset scrolls locally while the actions cell remains reachable. Column
visibility is grouped in a floating `Columnas` menu without replacing the
existing checkbox IDs, `data-column` state, or renderer. Material permissions,
inventory vocabulary, modal IDs, endpoints, and delegated callbacks remain
screen-specific contracts documented in
[`NEXT-MATERIAL-LIST-CONTRACT.md`](NEXT-MATERIAL-LIST-CONTRACT.md).

## Consolidated list system

Sales and Materials V2 jointly validate one reusable list sequence: Page
Header, Primary Toolbar, optional Advanced Filters, Result Summary,
Operational Table and Pagination. Materials V2 extends that base only for a
wide configurable dataset through Column Visibility, greater local horizontal
scroll and a sticky actions cell.

List actions follow the approved hierarchy: a solid primary action belongs to
the main toolbar; row operations live behind one neutral ellipsis trigger;
common menu items remain neutral; a functional emphasis may use contained
brand blue; destructive actions are separated and use restrained danger.
Warning/gold is not a generic action color.

Temporary list layers use `--next-floating-surface`,
`--next-floating-border`, and `--next-floating-shadow`. These tokens are
shared by row-action dropdowns, column visibility and future popovers, while
normal cards and tables remain border-led.

## Toast feedback baseline

System feedback uses the soft semantic treatment: a very light semantic
surface, a clear semantic leading border, dark title/message text and the
shared floating-layer shadow. This establishes the intended hierarchy between
ordinary cards and modal feedback without returning to saturated Bootstrap
alert surfaces. Toastr API, timing, position, progress and close behavior are
unchanged.
