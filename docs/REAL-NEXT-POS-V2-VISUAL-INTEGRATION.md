# REAL NEXT — POS V2 Visual Integration

## V1 Shared Baseline
Audit completed before runtime edits, 2026-11-11. V1 iterations 1–3 are the approved baseline. Store Web is product-language reference only; experimental is historical. No divergent POS design.

| Elemento | V1 | V2 actual | Diferencia funcional necesaria | Diferencia visual necesaria |
|---|---|---|---|---|
| Layout / panel izquierdo | Shared POS grid | Legacy Bootstrap columns | None | Reuse V1 |
| Carrito / stepper | Compact, natural growth | Large +/- and separators | Separate V2 item state and physical-item flow | Same template hooks; reuse V1 |
| Resumen / Total | Compact, dominant total | Legacy blue total | Same monetary IDs | Reuse V1 |
| Cobro / Caja / parcial | Shared section, mini switch | Legacy spacing | V2 checkbox has name pagos_parciales_venta; preserve | Reuse with name retained |
| Comprobante / Cobrar | Aligned accordion and CTA | Legacy header image | Same permissions and fields | Reuse V1 |
| Toolbar / búsqueda / categoría / Vista | One row, wraps by container | Legacy rows | V2 GET endpoint, c/f values unchanged | Reuse V1 |
| Grid / images / cards | Container-based 2–5 columns | Legacy variable cards | Material DTO vs StockItem DTO | Same shell and fallback |
| Precio | Exact option | Backend minimum reference when multiple | Preserve price_is_reference and shared formatter | Desde only if server indicates |
| Disponibilidad | Local option stock | Aggregate Material stock | Never imply aggregate is selected option stock | Label aggregate explicitly |
| Acciones | Special/add/stock | One Material resolver action | Stock/presentation actions belong to resolved option | Compact icon on card; existing option actions in modal |
| Variantes | Direct StockItem | GET modal table | Required resolution; no Cartesian combinations | Shared auxiliary modal, responsive table |
| Presentaciones | Direct modal | Per-option nested modal | Keep V2 branch-offering flow | Shared auxiliary modal |

## V2 Functional Differences
Route puntoVenta.indexV2 -> PuntoVentaController@indexV2 -> PosScreenOptions::data(v2). PosCatalogGuard:v2,page; permission createV2_puntoVenta. Snapshot and MaterialInventoryScope retain tenant/company/branch. Catalog GET /dashboard/get/data/products/v2/{page}, category_id/product_search; 12 per page. Store action and payload unchanged.

## Material → Variant → StockItem
PosCatalogReadService::v2 includes enabled tenant materials with active stock items enabled for company and active same-material variants. Unlike V1 it may include options without local stock. variants() returns only real scoped StockItems. Material stock is sum of available option stock; stock_items_count is actual option count. No inferred talla×color combinations.

## Product Card
Reuse V1 frame, image, name, metadata, price and icon row. Show option count and explicitly aggregate local stock. The SKU supplied by Material DTO belongs to its first option; do not present it as an unambiguous material SKU for multi-option materials. Do not fabricate additional card actions.

## Variant Resolver
Existing addProductCartSpecial always opens modalStockItemsVenta and GET /dashboard/get/material/{material}/stock-items, even for one option. Preserve this deliberate current contract; no auto-add optimization. Existing table lists variant_text (attribute summary or talla/color), SKU, barcode, available, price, quantity and presentation action. IDs/data-state/selection queue retained.

## Pricing
BranchPriceListService and PriceResolverService are authority. Shared posCatalogPrepareProduct owns reference formatting and Sin precio. Per-option price returned by backend, no new pricing arithmetic. Existing renderer disables quantity/presentation when no price or available<=0.

## Stock
Local balance comes from valid scoped inventory levels; reserved stock excluded. Option lookup uses stock_item_id; a Material id must never be passed as stock id. Existing stock icon remains in the option row and global lookup remains in toolbar.

## Presentations
Keep fetchPresentations and /dashboard/pos/materials/{id}/presentations plus modalPresentaciones. Shared definition and branch offering remain backend concerns. V2 records presentation selections on the resolved row; no parallel selector.

## Cart
Keep itemKey, resolved stockItemId/materialId, presentationId, quantity and physical-item queues. Existing productName already includes option identity. Shared V1 visual cart template has identical active functional hooks.

## Shared Components
Audit of pre-integration V1 vs V2 form showed only one functional field difference: V2 partial-payment checkbox name. Controlled duplicated form markup preserves it; large partial extraction deferred to avoid coupling separate legacy scripts. Same cart template and CSS patterns, separate JS and V2 modals. No broad refactor.

## JS Contracts
indexV2.js stays separate. Existing callbacks, AJAX, selected physical-item queues, data attributes and POST payload preserved. Only render/presentation changes planned. V2 has exactly one real card action; additional icons would falsely imply direct add or stock lookup without a resolved option.

## CSS Shared
Use the same venti-next-pos.css. No new V2 stylesheet. CSS SHARED WITH V1: 97.7% (85 shared style-rule blocks / 87 total, excluding @media/@container wrapper blocks; 2 resolver-only rules). All core layout/card/cart/payment rules are shared without duplication.

## V2-specific CSS
Two rules only: `.next-pos-variant-table { min-width:850px }` and `.next-pos-variant-table .input-group { min-width:120px }`. Both constrain the existing locally scrolling resolver table, preventing stock digits from clipping. No separate V2 stylesheet.

## V1/V2 Visual Comparison
At verified 1440px, all 63 measured properties across nine shared elements match V1: panel width450, toolbar645, card152.25, image150.25, name13px, price15px, section12px/20px, radius10px, CTA width408 and its shared tokens. See pos-v1-v2-parity.json. This is measured component parity, not a fabricated whole-screen pixel similarity score. Different dataset, action count and resolver are intentional functional differences. V1 and V2 screenshots captured at the same 1440x900 viewport.

## Responsive QA
Verified actual innerWidth with the resolver open (initial browser-wide viewport attempts did not apply to the second tab and were discarded; used tab emulation). All widths below: zero global overflow, no main collapse controls, cart overflow visible. The 850px resolver table scrolls only inside its responsive wrapper.

| Width | Columns | Panel width | Modal width |
|---|---:|---:|---:|
|320|2|273|289|
|375|2|328|344|
|390|2|343|359|
|576|3|513|561|
|768|2|310|736|
|992|3|368|736|
|1200|2|410|736|
|1366|3|450|736|
|1440|4|450|736|
|1920|5|450|736|

Mobile resolver uses existing Next auxiliary modal behavior; no global table overflow. Some width differences account for scrollbar reservation while modal is open.

## Non-persistent QA
Owner EDESCE, tenant1/company1/Local Principal branch1. Only local cart and GET lookups used.

- Catalog14 materials; page2 shows records13–14. Search by variant SKU POL-ADI-SPO-HOM-41-BLA returns its material. CELULARES category returns3 materials.
- Material POLO DEPORTIVO has4 returned options: 41/BLANCO stock13, 42/BLANCO20, 41/NEGRO20, 42/NEGRO0. Last option quantity/presentation disabled. No invented combinations.
- Selected StockItem77 at40; cart name includes41/BLANCO, quantity1 =>40, increment2 =>80, removal works. No sale.
- Single-option POLO NEGRO still opens the existing resolver. Presentation Caja de4 at250 from branch offering selected and added locally: cart displays1 package (4 units), total250. Original flow preserved.
- Stock lookup from StockItem77 shows local13 and Outlet physical3/reserved1/available2, then restores the variant resolver.
- Filas displays existing contracts with icon action. Search without matches shows contextual empty state. Fixed visual legacy empty renderer which cleared nonexistent #table-body instead of #body-card; no endpoint or query change.
- Partial toggle ON/OFF preserves normal-payment visibility/reset behavior. Solicitar/Factura reveal existing fields. No customer lookup or form submission.
- Console error collection empty. Final carts reset to empty. Price-less samples and serialized physical-item selection were not available for live coverage; hooks, disabled rules and queue logic preserved statically. Gamarra not re-certified in this visual phase.

## Mutation Exclusions
No sale, payment, stock update, emission or cash opening/closing. Do not invoke final Cobrar.

## Backend Sync Notes
[OPEN] Existing resolver errors remain generic and do not expose a detailed configuration/PriceList diagnosis. Presentation and variant load errors remain distinct; filtered catalog empty uses filter context, unfiltered empty says no enabled materials in context. Do not infer why an individual backend configuration failed. Frontend can continue: Yes. File: indexV2.js / existing GET endpoints. No backend fix claimed.
[OPEN] No-price, zero-stock and physical-item paths require representative local data for live coverage. Static preservation is not end-to-end certification.

## Revisit Triggers
Backend resolver/direct-option policy, price reference semantics, presentation offerings, stock guard, physical-item rules, payload or permissions change. Shared POS CSS changes must be tested on V1 and V2.


## Implementation files / contract verification

- resources/views/puntoVenta/indexV2.blade.php: same V1 form composition with the V2 checkbox name retained; identical compact cart template; adapted Material card with one real resolver action; existing V2 modals and initialization remain.
- public/js/puntoVenta/indexV2.js: render-only image fallback, metadata/option count, icons/labels/alignment, accurate empty text and stale-empty-container correction. Business totals, variant resolution, presentation requests, selection queues and sale payload unchanged.
- public/admin/dist/css/venti-next-pos.css: two narrowly scoped resolver rules only. Existing V1 rules unchanged in this phase.
- config/venti-next.php: add puntoVenta.indexV2 to visual allowlist.
- AGENTS.md: POS V1/V2 Shared Visual Baseline precedent.
- docs/REAL-NEXT-POS-V2-VISUAL-INTEGRATION.md: this audit and evidence.

Active IDs, names and permission directives compared against pre-change V2: none removed or added. Intentionally removed main-panel collapse widgets, retained all functional data/JS contracts. No backend/routes/services changed. Existing uncommitted V1 integration files remain; this phase did not overwrite unrelated work. Controlled markup duplication is documented rather than imposing a risky JS/partial refactor.

## Evidence and validation

Artifact directory: C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/

- pos-v2-legacy-1440.jpg
- pos-v1-shared-baseline-1440.jpg
- pos-v2-integrated-1440.jpg
- pos-v2-variant-resolver-1440.jpg
- pos-v2-responsive.json
- pos-v1-v2-parity.json

PHP7.3 Laravel view:cache PASS; node --check indexV2.js PASS; git diff --check PASS. No commit, sale, stock mutation, cash operation or emission.

POS V2 VISUAL BASELINE INTEGRATED
V1/V2 SHARED VISUAL SYSTEM PRESERVED
FUNCTIONAL CONTRACT PRESERVED
SALE MUTATION OUT OF SCOPE


## Variant modal desktop-fit correction — 2026-11-11

Supersedes the initial compact-modal/local-desktop-scroll decision. User approved wide desktop operational resolver.

Cause: shared next-aux-modal limited dialog to 736px while the resolver table required at least850px plus wrapper gutters. Overflow was real; hiding overflow would hide action columns.

Changes:
- Only modalStockItemsVenta receives semantic next-pos-wide. At >=992px, `.next-aux-modal.next-pos-wide .modal-dialog` uses width/max-width94vw; no fixed desktop width, global override or !important.
- Existing table now uses fixed layout, width100%, mobile minimum850px. Column proportions: variant22%, SKU16%, barcode20%, available16%, price10%, quantity9%, action7%.
- SKU/barcode retain full text and gain title; visual ellipsis prevents long identifiers expanding the table. No values/payload modified.
- Compact input groups are capped at9rem; readonly price7rem, quantity5rem. Existing button/input events and min/max unchanged.
- Header/footer/backdrop/radius and all seven columns preserved. Footer action stays reachable outside the local scroll wrapper.

| Viewport | Dialog width approx | Horizontal table scroll | Document overflow |
|---|---:|---|---|
|992|932.47px|NONE|NONE|
|1200|1128px|NONE|NONE|
|1366|1284.03px|NONE|NONE|
|1440|1353.59px|NONE|NONE|
|1920|1804.80px|NONE|NONE|
|768|736px|LOCAL|NONE|
|576|561px|LOCAL|NONE|
|390|359px|LOCAL|NONE|
|375|344px|LOCAL|NONE|
|320|289px|LOCAL|NONE|

Seven columns present throughout; no clipped numeric inputs in the matrix; footer button right edge within dialog in every width. Final compact caps were visually rechecked in desktop/mobile captures. Mobile retains one responsive table, not duplicate cards.

V1 regression: at1440, measured panel450, toolbar645, card152.25, image150.25, font13/15, section12px20px and CTA408 match prior baseline. No V1 markup/script edits in this correction; new selectors require the V2 resolver class. Console errors empty.

Modified in this correction: indexV2.blade.php (modal/column classes), venti-next-pos.css (scoped wide/table/controls), indexV2.js (rendered identifier classes/title only), this document. No backend/event/payload/variant/presentation/stock/pricing changes. No sale and no commit.

Evidence in existing artifacts directory: pos-v2-wide-1440.png, pos-v2-wide-390.png, pos-v2-wide-matrix.json. Prior 97.7% CSS count belongs to initial integration; this correction adds resolver-only presentation rules and supersedes that count.

POS V2 VARIANT MODAL DESKTOP FIT: PASS
DESKTOP TABLE SCROLL REMOVED: PASS
MOBILE FALLBACK RESPONSIVE: PASS
ALL REQUIRED COLUMNS VISIBLE: PASS
ADD TO CART ALWAYS ACCESSIBLE: PASS
FUNCTIONAL CONTRACT UNCHANGED

Validation: Blade compile, JS syntax and git diff --check PASS.

## Balanced desktop modal width — 2026-11-11

Supersedes the 94vw desktop sizing above. The resolver now uses `width: clamp(900px, 76vw, 1180px)` and `max-width: 1180px` only at >=992px. The 900px lower bound fits the existing seven-column table without making smaller desktops scroll; the upper bound preserves clear outer margins at 1920. Existing compact controls, column allocation and identifier ellipsis/title remain unchanged. No additional cell compression was necessary.

Read-only visual QA with Owner EDESCE, using the four-option POLO DEPORTIVO resolver:

| Viewport | Dialog width | Table scroll | Global overflow |
|---|---:|---|---|
|1920|1180px|NONE|NONE|
|1440|1094.39px|NONE|NONE|
|1366|1038.16px|NONE|NONE|
|1200|912px|NONE|NONE|
|992|900px|NONE|NONE|
|390|359px|LOCAL, unchanged|NONE|

All five desktop widths inspected visually: seven required columns visible, numeric inputs unclipped, footer complete and outer margins present. At 390, existing local table scrolling and complete footer remain intact. No changes below the desktop breakpoint. No business writes performed.

MODAL DESKTOP WIDTH: BALANCED
DESKTOP HORIZONTAL SCROLL: NONE
CLEAR OUTER MARGINS: PASS
ALL REQUIRED COLUMNS VISIBLE: PASS
MOBILE RESPONSIVE FALLBACK: PASS

This follow-up modifies only venti-next-pos.css and this document. No Blade, JS, backend, payload, event, stock or price changes; no commit. Evidence: pos-v2-balanced-1920.png, pos-v2-balanced-1440.png and pos-v2-balanced-matrix.json in the existing local QA artifacts directory.
