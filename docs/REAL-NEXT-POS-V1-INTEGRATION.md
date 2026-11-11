# REAL NEXT — POS V1 Integration

## Scope and source hierarchy

2026-11-11. Phases A Audit, B Shell/Layout, C Product Cards, D Compact Actions,
E Cart/Payment Presentation, F Responsive QA. No sales, backend changes or commit.

1. Real `/dashboard/crear/venta/V1`: absolute functional source.
2. Real `/store-web/catalogo`: product visual reference only; frozen.
3. `C:/wamp64/www/venti-admin-ui/pages/venti360/sales/pos.html`: composition/UX only.
No framework import, Store Web stylesheet dependency, public filters or mock data.

## Phase A — audit before implementation

Route `puntoVenta.index` calls `PuntoVentaController@index` and
`PosScreenOptions::data('v1')`; view `puntoVenta.index` extends `appAdmin2`.
`PosCatalogGuard:v1,page` and `PosCatalogReadService::authorize('v1')` require
existing tenant/company/branch context plus `create_puntoVenta` permission.
No new context or frontend tenant filtering is proposed.

Catalog GET `/dashboard/get/data/products/{page}` uses `category_id` and
`product_search`, existing context snapshot headers, enabled company stock items,
active material/variant and valid positive availability in local branch warehouses.
Page size is 12. Preserve this and Celdas/Filas. Price is resolved by
`BranchPriceListService::current` and `PriceResolverService::resolveUsingPriceList`.
The DTO provides `has_price`, nullable price, currency, price list/source, SKU,
barcode, variant_text, stock_available/current/reserved, requires_review, unit,
tax_kind/tax, type, material_id, stock_item_id, source and image_url.
`rating:4` exists in DTO but will not be presented as a customer rating.

Additional GET contracts: `/dashboard/pos/materials/{materialId}/presentations`,
`pos.items.available` for individual physical items, `pos.catalog.search` and
`pos.catalog.availability`. Preserve stock query modal, special selection,
presentation equivalence, selected item IDs and existing quantity validation.
No new stock threshold or availability rule.

`PosScreenOptions` supplies current selectable cash boxes/subtypes and optional
workers scoped to tenant/company/branch. Existing worker selection is a checkout
popup, not the experimental permanent select. Keep that flow. Preserve partial
payments, currency, change box/subtype, invoice type, customer lookup, tax and
checkout calculations. Submission remains `puntoVenta.store`, POST
`/dashboard/store/venta/`. No submission during QA.

JS dependencies: jQuery, Bootstrap4/AdminLTE, Select2, Bootstrap Switch,
Moment/datepicker, Typeahead, jQuery Confirm, Toastr; `catalog-stock.js`,
`checkout-context.js`, `index.js`. `posCatalogPrepareProduct` owns existing price
formatting and disables unpriced stock-item actions. Keep it unchanged.
`index.js` cart traversal depends on `.flex-grow-1`, `.d-flex`, `data-cart-row`,
quantity buttons and data attributes. Preserve these ancestor hooks.

### Contract inventory

IDs (including templates/modals):

`accordionInvoice`, `body-card`, `body-cart`, `btn-add_product`, `btn-cancel-itemeable-items`, `btn-confirm-itemeable-items`, `btn-newSale`, `btn-notAddProduct`, `btn-notSave`, `btn-pay`, `btn-printDocument`, `btn-save`, `btn_search`, `category_id`, `collapseOneInvoice`, `datos_boleta`, `datos_factura`, `direccion_fiscal`, `disabled-page`, `dni`, `formCreate`, `headingOneInvoice`, `importe_total`, `item-card`, `item-card-empty`, `item-cart`, `item-page`, `item-table`, `itemeable-item-search`, `itemeable-items-empty`, `itemeable-items-error`, `itemeable-items-loading`, `itemeable-items-table-body`, `itemeable-items-table-container`, `itemeable-product-name`, `itemeable-required-count`, `itemeable-selected-count`, `itemeable-selected-required-count`, `modalQuantity`, `modalSelectItemeableItems`, `modalVuelto`, `monto_total`, `name`, `negocio_acepta_pagos_parciales`, `next-page`, `numberItems`, `op_exonerada`, `op_gravada`, `op_inafecta`, `pagination`, `pagos_parciales_venta`, `presentationsArea`, `previous-page`, `product_search`, `pv_cash_box_id`, `pv_cash_box_subtype_id`, `pv_subtype_hint`, `pv_vuelto_cash_box_id`, `pv_vuelto_subtype_id`, `quantity_materialId`, `quantity_productId`, `quantity_productName`, `quantity_productPrice`, `quantity_productStock`, `quantity_productTax`, `quantity_productType`, `quantity_productUnit`, `quantity_stock_show`, `quantity_total`, `radio_boleta`, `radio_factura`, `radio_none`, `razon_social`, `ruc`, `table-body`, `textPagination`, `total_descuentos`, `total_igv`, `total_importe`, `type_id`, `vuelto`, `wrap_comprobante`, `wrap_pago_normal`, `wrap_pv_subtype`, `wrap_vuelto_subtype`, `{{ $st->cash_box_id }}`

Input names:

`category_id`, `direccion_fiscal`, `dni`, `email_invoice_boleta`, `email_invoice_factura`, `invoice_type`, `name`, `negocio_acepta_pagos_parciales`, `quantity`, `razon_social`, `ruc`, `type_id`

DOM data hooks:

`data-active`, `data-add_cart`, `data-add_cart_special`, `data-bootstrap-switch`, `data-card-widget`, `data-cart-row`, `data-cash-box-id`, `data-delete`, `data-discount`, `data-image1`, `data-input`, `data-is_deferred`, `data-item`, `data-item_key_minus`, `data-item_key_plus`, `data-mdb-input-init`, `data-name`, `data-off-color`, `data-off-text`, `data-on-color`, `data-on-text`, `data-parent`, `data-placement`, `data-pos-stock`, `data-pos-stock-search`, `data-presentation_label`, `data-price`, `data-priceTotal`, `data-product_id`, `data-product_name`, `data-product_price`, `data-product_stock`, `data-product_tax`, `data-product_type`, `data-product_unit`, `data-quantity`, `data-row`, `data-selected_items`, `data-states`, `data-stock`, `data-target`, `data-toggle`, `data-type`, `data-url`, `data-uses_subtypes`

Explicit FormData keys:

`cash_box_id`, `cash_box_subtype_id`, `items`, `material_inventory_`, `pagos_parciales_venta`, `pos_currency`, `pos_price_list_id`, `pos_request_key`, `tipo_pago`, `total_descuentos`, `total_exonerada`, `total_gravada`, `total_igv`, `total_importe`, `total_vuelto`, `type_vuelto`, `vuelto_cash_box_id`, `vuelto_cash_box_subtype_id`, `worker_id`

Additionally retain form serialization/CSRF, `items` JSON contents, optional
worker and dynamically appended `material_inventory_tenant/company/branch`.
`APP_POS.URLS.AVAILABLE_ITEMS`, `PV_ASK_WORKER`, `PV_WORKERS` and checkout globals
remain owned by existing scripts. Presentation edits must not rewrite them.

### Before measurements (1440 × 900)

- Legacy POS: 4 columns, cards179px wide; heights vary286–410px with missing image/name;
  five real products available in EDESCE Local Principal. Three large text actions.
- Store Web: five261px columns/gap16, card about340px high; image aspect1/0.94 desktop,
 1/1.04 mobile, contain+5px image inset. Body11px desktop/9px mobile. Name13px500,
 price15px700; radius12. At375px two163px cards with gap10 and name12/price14.
- Experimental: left450px/right671px/gap16, four158.75px cards/gap12;
 actual rendered cards about404px high with demo imagery. Its overflow at1280 and
 truncated small-card content are not approved requirements to reproduce.

## Comparative matrix and final decisions

| Elemento | POS actual | Store Web actual | POS experimental | Decisión final |
| --- | --- | --- | --- | --- |
| Card ancho/alto |179px; variable286–410|261×340 desktop,163×260 mobile|159×404|Stable media and two-line name; width derived from remaining catalog area|
| Imagen |Natural image size; broken URL visible|contain, neutral background, near-square|cover, demo photos|Store contain/near-square within Next tokens; no experimental image data|
| Fallback |StockItem returns images/material/no_image.png; no browser error handler|Permanent Store fallback with onerror|Demo only|Reuse real backoffice fallback and one-shot image error handling; no Store asset dependency|
| Nombre |Centered bold, variable height|Two lines500|Two lines bold|Two-line hierarchy and full title; preserve exact real name|
| SKU |Passed to action but not visible|Not shown|Shown|Show real DTO SKU; no fabricated placeholder code|
| Precio |posCatalogPrepareProduct, Sin precio|price_text can include Desde/zero|Mock amounts|Preserve POS formatter exactly; visually emphasize with700 weight|
| Disponibilidad |Only eligible local stock listed|Broad public Disponible|Mock low/out of stock|Show exact local stock_available; no invented low-stock threshold|
| Presentación |Special dialog|Public variant flow|Presentation/serial badges|Show DTO variant_text; keep existing special dialog and type3 physical selection|
| Acciones |Large text links/buttons|Whole card opens detail|One large action|Three inline icon buttons: special/add/stock, existing Font Awesome, labels/titles/disabled|
| Columnas |Bootstrap col-md-3|2–5 by viewport|2–4|Compare4/5 using catalog width, not full viewport; preserve12/page|
| Gaps/densidad |Mixed margins/variable height|10/14/16|12|Next space3(12), dense operational metadata, predictable card heights|
| Responsive |Cart first; narrow columns at768|Mobile first public catalog|Catalog first below768|Maintain operational desktop left/right; catalog first on small screens|
| Search |Category + mode + small search|Full-width search plus drawer|Search + category|Search and category main row; preserve real mode and stock search in secondary compact row|
| Empty/loading |Template plus loading overlay|Dedicated empty states|Demo states|Preserve current renderer/loading mechanism; style empty wrapper, no false availability|
| Carrito/resumen |Nested cards, long totals|Not applicable|Sticky compact left panel|Experimental composition; original monetary IDs and quantity hooks|
| Caja/comprobante |Real scoped options and conditional fields|Not applicable|Mock inline controls|Style real controls; preserve field visibility and real confirmation sequence|

## Density decision

At1440, ~671px catalog supports4×159px after3×12px gaps. Five would be~125px,
below the Store mobile163px reference and too tight for SKU and3 actions.
Five becomes suitable around~800px catalog width (5×150+4×12=798px).
Use available-container behavior, cap at5; do not request more products per page.
At desktop1920 the catalog may show5 columns, preserving the12-product contract.

## Intentional deviations

- Inline special/add/stock icon actions instead of experimental large text CTA.
- Store-like contain images rather than cropped experimental apparel photos.
- Next font/color/spacing tokens and ERP shell; no Montserrat/framework import.
- Keep grid/list mode and stock lookup; no public Store filters/categories logic.
- Worker remains the real optional checkout popup, not a new permanent selector.
- No fabricated promotions, ratings, old prices, low-stock badge or Desde semantic.

The >=95% composition target is an approximate visual goal, not a claimed pixel
score across different data/fonts/shells. Record actual geometry and comparative
screenshots; do not invent a measured similarity percentage.

## Phase status

A–F: implemented and validated within the read-only / local-cart scope below. No financial transaction was submitted.

## Backend Sync Notes

No backend uncertainty is resolved by visual work. Existing no-price disabled
behavior, contextual guards and server validation remain authoritative. This phase
will not submit checkout or claim end-to-end financial verification.

## Implementation and QA evidence — 2026-11-11

- B: cart/summary left, catalog right, gap 16px. At 1440px the left panel is 450px, matching the experimental; catalog 645px vs experimental 671px (3.9% narrower because the real shell differs). Phase B was captured before cards were adapted.
- C: contained near-square media, real fallback, two-line names, SKU, presentation, real price and local stock. No ratings, mock discounts, experimental images or public Store contracts.
- D: special/add/stock inline native buttons, existing Font Awesome icons, accessible names/title and visible focus. Existing `posCatalogPrepareProduct` still owns no-price disabling; no replacement business rule.
- E: compact cart rows, existing +/- quantity handlers and remove action; monetary breakdown, box/subtype, conditional invoice fields and Cobrar. Comprobante retains its existing accordion behavior. Worker selection remains the optional original checkout flow.
- F: tested actual viewport dimensions through tab CDP emulation after the browser viewport control failed to apply them. The matrix was recorded only after checking `innerWidth`.

### Responsive matrix

| Width | Grid columns | Card width approx. | Global overflow | Action overflow |
|---|---:|---:|---|---|
| 320 | 2 | 130.5px | NONE | NONE |
| 375 | 2 | 158px | NONE | NONE |
| 390 | 2 | 165.5px | NONE | NONE |
| 576 | 3 | 163px | NONE | NONE |
| 768 | 2 | 175.5px | NONE | NONE |
| 992 | 3 | 143.8px | NONE | NONE |
| 1200 | 2 | 216.5px | NONE | NONE |
| 1440 | 4 | 152.25px | NONE | NONE |
| 1920 | 5 | 218.4px | NONE | NONE |

The real sidebar reappears on larger desktop widths; column count follows catalog container width, not a monotonically increasing viewport-only rule. Below 768px catalog precedes cart. A populated cart was also checked at 320px with no global overflow. The list table scrolls within its own wrapper (499px content at 390px viewport), not the page.

### Functional smoke checks (no sale submitted)

Session: Owner EDESCE, tenant 1/company 1, Local Principal branch 1. Five actual catalog records available.

- Normal add: one unit at 70.00; increment to 2 produces 140.00; decrement restores 70.00; remove restores zero.
- Special action opens real quantity/presentation modal: unit stock 25, Caja de 4 at 250.00. Cancelled without adding or saving a sale.
- Stock action opens real contextual GET lookup: local available 25, general warehouse GEN physical 32/reserved 3/available 29. Lookup remains informational.
- Search P-00001 returns one product. Nonmatching search and CELULARES category return existing empty state. Restored unfiltered view.
- Filas mode renders the real table and three accessible icon actions; switched back to Celdas.
- Boleta reveals DNI/name; Factura reveals RUC/business name/address; Ticket restores original state. No customer lookup or persistence.
- Bancario BCP reveals subtype selector and existing help text; cash selection hides it again.
- Console error collection at the end of these checks: empty. Missing product image uses the permanent POS fallback.
- All active Blade IDs, input names, permission directives and existing data hooks match HEAD inventories; four presentation-only data hooks added.
- JS changes restricted to card/table rendering and image fallback helper. Checkout, quantities, pricing, taxes, request context and payload functions unchanged.
- Blade compilation, JS syntax and git diff whitespace checks run before delivery.

### Limits

No sale, cash movement, customer, stock or configuration was created/updated. Checkout confirmation intentionally not executed. Gamarra was not revalidated in this visual phase; this is not a new multitenancy certification. No unpriced or serialized sample was available in this five-record EDESCE catalog, so their shared guards/hooks were preserved and inspected statically rather than claiming live coverage. Pagination renderer and page size remain unchanged; multiple pages were not available in this context. No numeric whole-screen 95% similarity score is claimed: geometry, screenshots and intentional deviations are the evidence.

### Files changed

- config/venti-next.php: enable only puntoVenta.index in the existing Next allowlist.
- resources/views/puntoVenta/index.blade.php: POS composition and templates; existing functional contracts retained.
- public/admin/dist/css/venti-next-pos.css: scoped component styles using Next tokens, replacing legacy inline POS CSS.
- public/js/puntoVenta/index.js: presentation-only card/table render adaptation and fallback.
- docs/REAL-NEXT-POS-V1-INTEGRATION.md: audit, decisions and evidence.

Store Web, backend, routes, shared POS contract scripts and experimental reference remain unchanged. No commit.

### Screenshots

Artifacts directory: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`.

- pos-v1-legacy-1440.jpg
- pos-reference-store-web-1440.jpg
- pos-reference-experimental-1440.jpg
- pos-v1-phase-b-shell-1440.jpg
- pos-v1-phase-cd-1440.jpg
- pos-v1-final-desktop-1440.png
- pos-v1-final-mobile-390.png
- pos-v1-cart-mobile-320.png
- pos-v1-responsive-matrix.json

POS V1 combines the experimental operational composition with the approved Store Web product language while retaining the real backend contract. Visual implementation ready for user review; financial checkout intentionally unexecuted.

## Iteration 2 — authorized density corrections (2026-11-11)

Supersedes iteration 1 sticky/scroll cart and secondary Vista row. Changes only the POS Blade, its scoped CSS and this document. Previous uncommitted config/JS work remains unchanged by this iteration.

- Removed exactly two card-tools wrappers/buttons carrying data-card-widget="collapse", tooltip title Collapse and minus icons, from Venta actual and Productos. Shared AdminLTE and the Comprobante accordion remain intact.
- Removed cart body max-height calc(100vh - 210px) and overflow-y:auto; removed cart list clamp(80px, calc(100vh - 650px), 240px), overflow-y:auto, overscroll containment and mobile max-height:280px. Removed sticky positioning on sale and sale column. Natural document flow now owns vertical growth.
- Retained compact 12px vertical cart-row padding and original quantity/name/presentation/remove hooks. Five distinct products expanded the cart list to 621–708px and the panel to 1164–1252px, with max-height:none and overflow-y:visible throughout. No inner vertical scrollbar.
- Toolbar order: search + its two existing action buttons, Vista, category. Existing type_id values c/f, category_id and JS unchanged. Vista has an accessible label/title and 120px width to avoid truncating Celdas. Existing token gap 12px; flexible search gets most space, category the next share.
- Container below 400px: full-width search followed by Vista/category. No horizontal toolbar scrolling or forced tiny search input. Catalog grid rules/cards untouched.

| Viewport | Toolbar rows | Grid columns | Global overflow |
|---|---:|---:|---|
| 1440 | 1 | 4 | NONE |
| 1366 | 1 | 3 | NONE |
| 1200 | 1 | 2 | NONE |
| 992 | 1 | 3 | NONE |
| 768 | 2 | 2 | NONE |
| 576 | 1 | 3 | NONE |
| 390 | 2 | 2 | NONE |
| 375 | 2 | 2 | NONE |
| 320 | 2 | 2 | NONE |
| 1920 | 1 | 5 | NONE |

All requested widths were measured with five cart lines; the final Vista widening was additionally rechecked at 320, 1200, 1440 and 1920. At 1200 search remains 201px; at 320 it spans the 273px available catalog width. No collapse controls remained. Existing View Filas/Celdas still switches renderers; final cart cleared by page reload, no sale submitted. Console errors empty.

### Updated visual comparison

Compared with the existing experimental and Store Web captures from phase A: left/right POS composition, product media hierarchy, compact metadata and inline icons remain. Four explicitly authorized differences are not treated as parity defects: always-expanded panels, icon actions, single-row desktop toolbar and naturally growing cart. Store Web remains a visual reference only and unchanged. No invented numerical similarity score.

### Validation / result

Blade compilation and git diff --check PASS. No JS edits in this iteration; endpoints, payload, prices, taxes, stock, worker, cash and checkout untouched. Backend Sync status unchanged.

MAIN PANEL COLLAPSE REMOVED: PASS
PRODUCT TOOLBAR SINGLE-LINE DESKTOP: PASS
CART INTERNAL SCROLL REMOVED: PASS
DOCUMENT SCROLL ONLY: PASS (POS cart/panels; existing sidebar/table mechanisms unchanged)
PRODUCT GRID DENSITY PRESERVED: PASS
GLOBAL OVERFLOW: NONE

Evidence in the same artifacts directory:
- pos-v1-iteration2-desktop-1440.jpg
- pos-v1-iteration2-desktop-1920.jpg
- pos-v1-iteration2-mobile-320.jpg
- pos-v1-iteration2-responsive.json

No commit. No business writes.

## Iteration 3 — compact left-panel controls (2026-11-11)

Only the POS Blade, scoped POS CSS and this document changed in this iteration. No JS/backend/vendor/global Next changes.

- Quantity stepper: 90x26px overall (was 113x32px), 24x24px +/- targets, 40px centered input, 10px icons. Uses existing space tokens. Native number spinners hidden to avoid duplicate arrows consuming the compact input; type, min, step, events and payload unchanged.
- Partial payment: existing Bootstrap Switch supports data-size="mini" natively (verified vendor implementation). Actual rendered size about 63x26px. Original checkbox and switchChange event unchanged. Flex wrapper aligns label/control with token gap 8px; label associated to existing checkbox.
- Section gutters: existing next-pos-sale-section now uses 20px horizontally on desktop, 16px below 768px, aligning summary, payment, voucher and primary action. Vertical density remains 12px. No individual field margin offsets.
- Voucher row: Bootstrap align-items-center on row AND inline-flex/align-items-center on Solicitar so the text is centered within its existing clickable area. Text-center delta measured 0.25px, no absolute positioning.
- Total and Cobrar retain their hierarchy. Cards/catalog and approved layout unchanged.

QA Owner EDESCE / Local Principal: 320,375,390,576,768,992,1200,1440,1920. All showed 90x26px stepper, mini switch, no global overflow, consistent section gutters and aligned voucher boxes. Final text alignment and numeric-input presentation additionally inspected on desktop after the visual correction. Increment/decrement produced 140/70 from a unit price 70. Partial toggle ON hides normal payment/voucher as before; OFF restores them. Bank selection reveals real subtype; Solicitar opens Ticket/Boleta/Factura. No checkout submitted. Cart cleared on completion; console errors empty.

QUANTITY STEPPER COMPACT: PASS
PARTIAL PAYMENT CONTROL COMPACT: PASS
PAYMENT SECTION SPACING: PASS
VOUCHER REQUEST ALIGNMENT: PASS
GLOBAL OVERFLOW: NONE

Blade compile / git diff --check: PASS. No commit. Backend Sync status unchanged.
Evidence in the existing artifacts directory: pos-v1-iteration3-desktop-1920.jpg and pos-v1-iteration3-responsive.json.
