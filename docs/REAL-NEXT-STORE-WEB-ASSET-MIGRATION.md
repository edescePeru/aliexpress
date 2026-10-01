# REAL NEXT 9.1 — Store Web Asset Migration + Frozen Shell

Scope: frontend migration only. No business data integration, controller/scopes/routes changes, Sass compilation, dependency updates or temporary/legacy deletion.

## Assets and hashes

54 copies recorded in [asset-migration-manifest.json](store-web/asset-migration-manifest.json), with source path, destination path, source SHA-256, initial copied SHA-256 and final SHA-256 for every file.

- Four CSS → `public/store-web/css/`.
- One approved JS → `public/store-web/js/catalog-main.js` (then remove only tester block).
- Three font binaries plus Montserrat license → `public/store-web/fonts/`.
- 28 reference images → `public/store-web/img/`, preserving directories/names.
- 15 Sass sources → `resources/sass/store-web/`, including the historical tester source and unchanged entrypoint. No build consumes that entrypoint yet.
- Reference readme/notice → `docs/store-web/vendor/REFERENCE-NOTICE.txt`.
- Real fallback `public/images/material/no_image.png` → `public/store-web/img/no_image.png`.

Every initial copy matches the SHA-256 captured in REAL NEXT 9 (fallback separately checked against its real source). **53 final files remain byte-identical** to their source; the only adapted copied file is catalog-main.js. Two reserved adapter files are new and not loaded.

`public/store-web/css/style.css` remains exactly:

```text
eb43009f04a0298356f56781b9c128c5969fdb4ef9333f7fde05a508b57e1431
```

No Sass recompilation and no CSS rule changed. Vendor versions and relative font URLs remain intact. The compiled CSS contains inert tester selectors because byte identity was expressly required; **there is no tester DOM or executable tester JS** in the new shell. Sass/history is preserved as requested. A later build must explicitly exclude the historical tester module; this phase does not alter the source entrypoint or webpack.

## Definitive layout and partials

Created `resources/views/layouts/storeWeb.blade.php`. It preserves the four stylesheet order, Spanish document, per-page body class, shell structure and the single deferred visual JS.

Created `resources/views/shop/partials/store-web/`:

| Partial | Extraction |
|---|---|
| header.blade.php | Literal shared header/search; only active navigation state parameterized |
| mobile-menu.blade.php | Literal overlay/menu/ARIA; active home/products selected by page |
| footer.blade.php | The three literal footer variants, selected by page; their copy/classes preserved |
| bottom-nav.blade.php | Literal home/shop navigation with active state; not inserted into detail |
| filter-drawer.blade.php | Literal groups/options/price controls/footer; no query handlers added |
| product-card.blade.php | Literal anchor wrapper plus Blade slot for each approved fixture body; no invented product schema |
| gallery.blade.php | Literal four-image gallery, thumbnails and counter |

Replaced only the three target Blades: `resources/views/shop/home.blade.php`, `catalog.blade.php`, `detailCatalog.blade.php`. They retain the names returned by the existing controller. Static fixture content is extracted literally, not reconstructed from a new design or generated from mock APIs.

## Fixtures and pending bindings

| View | Phase state | Pending |
|---|---|---|
| Home / Index | FRONTEND READY / BACKEND CONTRACT PENDING | Company intro, banner/editorial, featured selection, promotions, commercial collections, public footer/branding |
| Catalog / Shop | FROZEN SHELL / FRONTEND FIXTURE | Real cards/count/pagination/facets/search; multi-category, brand, condition, availability and collections contracts |
| Product Detail | FROZEN SHELL / FRONTEND FIXTURE | Real images/summary/price/stock; selection of actual variants/presentations; WhatsApp payload; public description; related products |

The original nine Shop cards, four featured cards, two offers, four gallery images and four related cards are frontend fixtures. No new promotions, recommendations or combinations were invented. Existing labels/prices are retained only to compare the approved mockup. Technical Blade comments mark `BIND REAL DATA` and `BACKEND CONTRACT NEEDED` zones.

To avoid publishing fixtures as business information, the layout requires a strict server-side boolean opt-in in local/testing. It rejects production or missing/string flags with 404 before rendering fixture markup. The page title includes `[FIXTURE FRONTEND]`; meta status, robots noindex/nofollow, an HTML comment and `data-store-web-fixture` identify the preview without altering page composition. The flag is not read from query strings or sessions.

**Deployment limitation:** these staged views are deliberately not enabled in StoreWebController. Ordinary application requests still execute the unchanged controller and may fail on its existing context requirements or return 404 at the view guard. The guard does not prevent those earlier queries and does not solve SW-01. This phase is a local frozen-shell deliverable, not a deployable public catalog.

## JavaScript separation

- `catalog-main.js`: approved visual behavior, byte-for-byte except removal of the contiguous theme-tester block. Mobile menu, shared overlay controller, scroll lock, focus trap, Escape, focus restoration, search focus/clear, drawer and gallery preserved.
- `catalog-data.js`: reserved, comments only apart from strict mode; not loaded, no requests.
- `product-detail.js`: reserved, comments only apart from strict mode; not loaded, no variants/requests.
- Search submit still prevents default, filter apply still only closes, WhatsApp buttons still lack a commercial handler. This is intentional baseline behavior, not claimed functionality.
- No jQuery added. No backend code mixed into the visual JS. No existing legacy JS overwritten.

## URL and dependency changes

Physical CSS/JS/img/data-gallery-image paths now use `asset('store-web/...')`. Home and catalog links use existing `store-web.home`/`store-web.catalog` names. Fixture cards use `shop.product.show` with the explicit non-record key `frontend-fixture`; only the isolated test server resolves that fixture key without binding a Material.

No executable reference to the temporary directory in the new Blades, CSS, JS, Sass or webpack. Documentation/manifests and the optional QA reference-serving branch intentionally name the historical source. The test branch is off by default; it is not an application route or runtime asset dependency.

## Legacy preservation

172 protected files checked against pre-migration SHA-256 with no changes: the full temporary reference, full `public/shop/` tree, StoreWebController, routes/web.php, TenantScope, appShop, catalogNoPrice, catalogNoPrice.js, detailCatalogNotPrice and webpack.mix.js. `/store-web/tienda` and its route/controller stay intact. Real uploaded images and other business assets are not moved or modified.

Preexisting changes to AGENTS, login/home/welcome2 and public-access assets/documentation are outside this phase and preserved.

## QA architecture and independence

[Preview instructions](../tests/StoreWebPreview/README.md) document the reproducible isolated server and checks. It uses the installed Laravel Blade engine/URL generator and PHP 7.3.33, without booting application providers/controllers/middleware/model binding or registering a DB service. No real product ID is used and no business queries or persistence are performed by the final harness.

The comparison uses the requested URI shapes on a dedicated localhost port: `/store-web/inicio`, `/store-web/catalogo`, `/store-web/product/frontend-fixture`. These are **not HTTP-kernel/controller integration tests**. No new route was added to the application; the QA harness mirrors route metadata only for URL generation and directly renders the real Blades.

The first full-application bootstrap experiment was stopped by a deliberately missing database configuration when AppServiceProvider resolved Schema; it performed no successful DB query. The final harness excludes that bootstrap entirely.

Independence check: a second localhost process with reference serving disabled responds 404 for `/reference/index.html`; fixtures use only permanent `public/store-web/` assets. The temporary directory remains intact, as explicitly required. Guard check rejects production/no opt-in/string/null flags. Real IDs and data endpoints are refused by the fixture server.

## Responsive and visual QA

Matrix: Index, Shop, Detail × 320 / 375 / 390 / 576 / 767 / 768 / 991 / 992 / 1200 / 1440px. Browser: installed Microsoft Edge headless, device scale 1, viewport height 900, PHP 7.3.33.

Baseline screenshots exclude only the experimental tester DOM; the source files remain untouched. Comparison checks normalized DOM, full-page raster, document overflow, loaded images and local font, grid columns, bottom nav/sticky CTA, scroll-snap and safe-area CSS. Captures wait for font/image decode and raster settling; initial immediate captures exposed transient image-resampling differences, so no source changes were made in response.

Final results are recorded in the QA results section below. Synthetic desktop viewports verify CSS breakpoints; real iOS safe-area insets, physical-device gestures and cross-tenant behavior are not claimed as tested.

## Authorized differences

1. Asset URL helper replacement and existing named routes.
2. Literal markup extracted into Blade layout/partials; active page state parameterized.
3. Tester UI/JS removed; frozen CSS and historical Sass left intact.
4. Fixture guard and nonvisual fixture metadata/title/noindex.
5. Two empty reserved adapter files, not loaded.

No changes to layout, grid, font, spacing, color, banner, cards, menus, drawer, gallery or breakpoints. The unused adapters do not manufacture backend behavior. Any preexisting baseline limitation stays documented rather than silently redesigned.

## Backend Sync Notes

SW-01 through SW-07 remain **BACKEND SYNC OPEN**, with the same meaning as REAL NEXT 9. No resolver, scope, query, price/stock policy, variant or promotional contract has been introduced or closed. Public rollout still requires SW-01; a safe visual preview does not establish tenant isolation.

## Files changed / created

- Replaced: the three `resources/views/shop/{home,catalog,detailCatalog}.blade.php` files.
- Created: `resources/views/layouts/storeWeb.blade.php` and seven `resources/views/shop/partials/store-web/*.blade.php` files.
- Created: 40 files under `public/store-web/` (including migrated visual JS, two reserved adapters and real fallback).
- Created: 15 Sass files under `resources/sass/store-web/`.
- Created: vendor notice and hash manifest under `docs/store-web/`.
- Created: this migration report; updated the original audit with a dated phase addendum.
- Created: isolated router, guard check, browser QA script and README under `tests/StoreWebPreview/`.
- Deleted: nothing. No application route/controller/DB/build configuration changes.

## QA results and phase status

**STORE WEB FROZEN SHELL READY** — local fixture shell only; not a public data-integration release.

Evidence: [responsive-qa.json](store-web/responsive-qa.json), [asset-verification.json](store-web/asset-verification.json) and [asset-migration-manifest.json](store-web/asset-migration-manifest.json).

| Width (px) | Shop columns | Home/Shop bottom nav | Detail fixed CTA | All three pages |
|---:|---:|---|---|---|
| 320 | 2 | visible | visible | PASS |
| 375 | 2 | visible | visible | PASS |
| 390 | 2 | visible | visible | PASS |
| 576 | 2 | visible | visible | PASS |
| 767 | 2 | visible | visible | PASS |
| 768 | 3 | hidden | hidden; inline CTA | PASS |
| 991 | 3 | hidden | hidden; inline CTA | PASS |
| 992 | 4 | hidden | hidden; inline CTA | PASS |
| 1200 | 5 | hidden | hidden; inline CTA | PASS |
| 1440 | 5 | hidden | hidden; inline CTA | PASS |

- 30/30 comparisons: normalized body DOM identical; CSS/fonts/images byte-identical; no geometry redesign.
- 30/30 full-page comparisons: 0–4 raw pixel differences, each only one 8-bit channel level, in image raster rounding. Zero pixels exceed that one-level tolerance. The initial arbitrary raw-pixel threshold of 3 rejected a four-pixel/one-level rounding case; individual pixel/channel inspection justified a color-level tolerance instead of changing source/UI. Saved captures were re-evaluated with that criterion and passed. Not claimed as byte-identical screenshots.
- No page JS exceptions, failed asset requests, missing images or missing Montserrat; document scrollWidth equals viewport width in all 30 cases.
- Menu open/ARIA, Tab cycle, Escape, return focus and restored body scroll: PASS. Drawer full-width mobile and 400px desktop, apply-close/ARIA: PASS. Search clear/focus and gallery thumbnail/src/alt/counter/pressed state: PASS. Reduced-motion media: PASS. Existing scroll-snap and safe-area CSS confirmed.
- QA server refuses real product IDs and business endpoints. Second server with reference access disabled: all three views 200, respectively 15/18/21 permanent asset URLs checked successfully; reference URL 404. No temporary files renamed or removed.
- Guard check: 12 local/testing/production flag cases refused as intended; browser fixtures render only with explicit internal boolean opt-in. PHP 7.3.33 syntax checks and JS syntax checks passed. Scoped git diff whitespace check passed.
- Existing ten Store Web routes/names/actions/middleware rechecked with PHP 7.3.33; unchanged. 172 protected file hashes unchanged, all 54 manifest final hashes correct, zero temporary runtime references in migrated files/build entrypoint.
- Visually inspected full-page home mobile, Shop mobile and detail desktop captures. Screenshots are QA artifacts in the local task output directory, not runtime dependencies. The JSON reports are retained in the repository for review.

No physical mobile-device safe-area measurement, HTTP-kernel/controller integration, live business-data test or multitenant isolation certification is implied. No new user-approved visual deviations are required by this asset/shell extraction; future data differences still require the documented alignment.
