# Material List V2 — Frontend/Backend Contract

## Scope

This contract covers the authenticated operational list rendered by
`resources/views/material/indexv2.blade.php` at
`GET /dashboard/listado/materiales/v2`. It records the functional surface that
the Venti Next reconstruction must preserve. Visual markup may change; the
routes, permissions, selectors, payloads, and business callbacks below may not.

## Entry point and data source

- Page route: `GET /dashboard/listado/materiales/v2`.
- Route name: `material.indexV2`.
- Controller action: `MaterialController@indexV2`.
- Required permission: `list_material`.
- AJAX list route: `GET /dashboard/get/data/material/v2/{numberPage}`.
- AJAX controller action: `MaterialController@getDataMaterials`.
- Page size: 10 records.
- Response contract:
  - `data`: array of material rows;
  - `pagination.currentPage`;
  - `pagination.totalPages`;
  - `pagination.startRecord`;
  - `pagination.endRecord`;
  - `pagination.totalRecords`;
  - `pagination.totalFilteredRecords`.
- Row properties consumed by the renderer include `id`, `codigo`,
  `descripcion`, `medida`, `unidad_medida`, `stock_actual`, `stock_current`,
  `stock_min`, `stock_max`, `categoria`, `sub_categoria`, `marca`, `modelo`,
  `image`, `rotation`, `update_price`, `isPack`, and `has_variants`.

## Server-provided view data

`indexV2` must continue receiving:

- `$permissions`;
- `$arrayCategories`;
- `$arrayCedulas`;
- `$arrayCalidades`;
- `$arrayMarcas`;
- `$arrayRetacerias`;
- `$arrayRotations`;
- `$arrayMaterials`;
- `$rows`;
- `$hayAlertas`.

The hidden fields `#permissions` and `#hay-alertas` remain the JavaScript bridge
for permissions and the stock-summary alert state.

## Search and filters

The quick search and advanced-filter IDs are contractual because
`public/js/material/indexV2.js` reads them directly:

- `#description` → `description`;
- `#code` → `code`;
- `#category` → `category`;
- `#subcategory` → `subcategory`;
- `#rotation` → `rotation`;
- `#marca` → `marca`;
- `#retaceria` → `retaceria`;
- `#isPack` → `isPack`.

The script also safely reads the currently commented fields `#material_type`,
`#sub_type`, `#cedula`, and `#calidad`; their query keys must remain available
to the endpoint even while the controls are absent.

Interaction hooks that must remain:

- `#btn-search` loads page 1 with the current filters;
- `#btnBusquedaAvanzada` discloses `.busqueda-avanzada`;
- `#category` refreshes `#subcategory` through
  `GET /dashboard/get/subcategories/{category}`;
- the legacy dependent endpoints
  `GET /dashboard/get/types/{subcategory}` and
  `GET /dashboard/get/subtypes/{type}` remain untouched;
- the active Select2 initializations for category, subcategory, rotation,
  brand, scrap type, material, and pack status remain compatible.

## Column visibility

The existing show/hide API is the set of checked checkbox elements carrying
`data-column`. The following IDs, values, and default checked state are
contractual:

| ID | `data-column` |
| --- | --- |
| `customSwitch1` | `codigo` |
| `customSwitch2` | `descripcion` |
| `customSwitch3` | `unidad_medida` |
| `customSwitch4` | `stock_actual` |
| `customSwitch5` | `stock_min` |
| `customSwitch6` | `stock_max` |
| `customSwitch7` | `categoria` |
| `customSwitch8` | `sub_categoria` |
| `customSwitch9` | `marca` |
| `customSwitch10` | `modelo` |
| `customSwitch11` | `imagen` |
| `customSwitch12` | `rotation` |

`getActiveColumns()`, the checkbox `change` callback, and the matching
`data-column` cells/headers must keep the same behavior. Their visual container
may become a floating Columnas menu, but changing a switch must still reload
page 1 using the active filters.

## Dynamic table and pagination hooks

The following IDs/templates are renderer contracts:

- `#header-table`, `#body-table`;
- `#item-header`, `#item-table`, `#item-table-empty`;
- `#numberItems`, `#textPagination`, `#pagination`;
- `#previous-page`, `#item-page`, `#next-page`, `#disabled-page`;
- pagination link attribute `data-item`.

The AJAX response remains authoritative. No client-side DataTables instance is
used even though DataTables assets are loaded by the view. Horizontal overflow
must remain local to the table region.

## Row actions and permissions

The row-action dropdown may replace the visible button cluster, but the action
elements must retain the following hooks, data, order, and permission rules:

1. Edit: `data-editar_material`; URL
   `/dashboard/editar/material/{id}`; permission `update_material`.
2. View variants: `data-ver_variants`; URL
   `/dashboard/view/material/variants/{id}`; requires
   `verVariantes_material` and `has_variants === 1`.
3. Manage prices: `data-precioDirecto`; attributes `data-material` and
   `data-description`; permission `gestionarPrecios_material`.
4. View expiry dates: `data-show_vencimiento`; attributes `data-material` and
   `data-description`; permission `verVencimientos_material`.
5. Manage presentations: `data-manage_presentations`; attributes
   `data-material` and `data-description`; permission
   `managePresentations_material`.
6. Assign children: `data-assign_child`; attributes `data-material` and
   `data-description`; requires `isPack === 1` and permission
   `assignChild_material`.
7. Separate pack: `data-separate`; attributes `data-material`,
   `data-description`, `data-measure`, and `data-quantity`; requires
   `isPack === 1` and permission `separatePacks_material`.
8. Disable material: `data-deshabilitar` and `data-delete`; attributes
   `data-description` and `data-measure`; permission `enable_material`.
   This destructive action remains last and visually separated.

The image action remains outside the action menu as the compact cell action
`data-ver_imagen` / `data-image` / `data-src`. Stock fallback actions retain
`data-ver-inventario` and `data-material-id`.

## Auxiliary overlays and mutation endpoints

The reconstruction does not redesign or change the following existing modal
contracts:

- `#modalPrecioDirecto`, `#formPrecioDirecto`, route
  `material.manage.price`, `#material_id`, `#price_mode`,
  `#btn-submit_priceList`;
- `#modalAssignChild`, `#formAssignChild`, `#btn-submitAssignChild`;
- `#modalSeparate`, `#formSeparate`, route `save.separate.pack`,
  `#btn-submitSeparate`;
- `#modalPresentaciones` and its `data-mp-*` hooks;
- `#modalVencimientos`;
- `#modalDelete`, `#formDelete`, route `material.disable`;
- `#modalImage`, `#image-document`;
- `#modalInventoryLevels`, `#tbody-modal-inventory-levels`, and
  `window.materialInventoryLevelsUrl`.

Associated requests remain unchanged, including material unpack, expiry-date,
presentation, price, disable, and inventory-level endpoints. No QA action may
submit these requests or persist data.

## Plugin and asset dependencies

- Bootstrap 4 / AdminLTE modal, dropdown, tooltip, and custom-switch behavior.
- jQuery and delegated event handling.
- Select2 with the Bootstrap 4 theme.
- Bootstrap Datepicker, Moment, and Inputmask remain loaded for legacy modal
  compatibility.
- Toastr and jQuery Confirm are supplied by the parent layout.
- DataTables scripts are currently loaded but the primary material table uses
  custom AJAX rendering and custom pagination, not the DataTables API.

## Visual-only freedom

The following may change without altering the contract:

- page/header hierarchy and wording accents;
- toolbar, advanced-filter, summary, table, and pagination composition;
- location of the unchanged column switches inside the page;
- neutral/semantic styling for image, stock, rotation, and row actions;
- the row action cluster becoming the approved Venti Next dropdown pattern;
- responsive reflow and local table scrolling.

## Explicit non-goals and risk controls

- No controller, route, permission, AJAX URL, response shape, form payload, or
  business callback changes.
- No create, edit, disable, stock, unpack, price, expiry, or presentation
  mutation during QA.
- No removal of action hooks or permission gates.
- No global vendor edits and no new `!important` declarations.
- The legacy modal set remains outside this screen-focused visual pass.
