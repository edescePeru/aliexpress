# REAL NEXT 9.4 — Store Web Shop Integration

REAL NEXT 9.5 (2026-09-29): real product detail now uses the same authenticated local bridge, with binding after context validation. The detail-fixture-only statements below describe the historical 9.4 boundary. See [Product Detail Contract](REAL-NEXT-STORE-WEB-PRODUCT-DETAIL-CONTRACT.md).

**STORE WEB SHOP FRONTEND INTEGRATED — BACKEND CONTRACT TEMPORARY**.

This is **LOCAL AUTHENTICATED BRIDGE — NON PRODUCTION**. **SW-01 BACKEND SYNC OPEN**. All SW-01…SW-07 remain open. This phase does not certify public multitenancy or change business rules.

## Visual Contract

`project-catalog/shop.html` remains the frozen visual source; permanent layout/assets/partials are reused. No CSS/Sass, breakpoint, grid, spacing, color, card geometry, menu, drawer frame/footer or bottom nav was changed. `style.css` SHA-256 remains `eb43009f04a0298356f56781b9c128c5969fdb4ef9333f7fde05a508b57e1431`.

Anonymous local preview retains literal reference cards for visual comparison only. Authenticated mode renders an initially empty grid and then binds the current response; it never renders those sample cards or fictitious badges. The approved filter-group/option markup accommodates subcategory, size and color inside the same drawer. Single category/subcategory selection uses the existing checkbox appearance with mutually exclusive values and accessible selection labels. No multicategory backend behavior is implied.

## LOCAL AUTHENTICATED BRIDGE — NON PRODUCTION

The user explicitly authorized this local exception after confirming no parallel access mechanism exists. It supersedes the 9.2 policy that rejected every data endpoint, including authenticated requests.

Two middleware stages enforce access:

1. `StoreWebLocalPreview`, first in `web`, rejects non-local environments and disabled preview before session/binding. Home and Detail retain their isolated fixture responses. Only catalog and the four data routes proceed toward authentication.
2. `StoreWebAuthenticatedDataBridge`, immediately after `StartSession` and before `SubstituteBindings`, requires an authenticated, enabled, non-platform user; existing positive session tenant/company IDs; tenant matching the user and active; company belonging to that tenant, active and assigned to the user through an active membership. These membership predicates match the existing `InitializeTenantContext` checks. No missing context is defaulted or initialized here.

Tenant/company come exclusively from `multitenancy.tenant_id` / `multitenancy.company_id` in the authenticated session. Query/body values are never read by the bridge. No new tenant resolver, scope or productive route is introduced. Only access-validation queries were added; business controller queries are unchanged. Branch is not invented or selected; existing business logic remains as-is.

| Case | Catalog | Four data endpoints |
| --- | --- | --- |
| Local + preview true + valid authenticated context | Current controller + integrated facade | Current GET/HEAD controller responses |
| Local + preview true + anonymous | Isolated visual fixture | 404 |
| Authenticated but missing/foreign/inactive/unauthorized context | 404 | 404 |
| Preview false, or environment other than local | 404 | 404 |

`/store-web/search-product`, `/tienda`, `/producto/{material}` remain blocked. The adapter uses full-catalog search, so search-product is not needed. `/store-web/product/{material}` still shows the previously authorized detail **fixture**, including when reached from a real card; real detail integration is outside 9.4. No product model lookup is added there.

The layout accepts only an internal fixture flag or the internal flag set after successful bridge validation, with the same local/config conditions. Authenticated responses carry `X-Store-Web-Preview: local-authenticated-data-bridge-non-production`, private/no-store and noindex/nofollow headers. Title/meta identify non-production QA. Flags are restored after the request to avoid leaking view state between requests.

For manual review, log into the existing application, select an authorized active company through its existing context flow, and visit `/store-web/catalogo` on the same origin/session. Keep `APP_ENV=local`, `STORE_WEB_PREVIEW=true`. An anonymous browser continues to show the visual fixture. `.env.example` remains false by default.

## CURRENT TEMPORARY BACKEND CONTRACT

`StoreWebController`, `TenantScope`, models, business queries and `routes/web.php` were not changed.

| Existing endpoint/name | Adapter use |
| --- | --- |
| `/store-web/catalogo`, `store-web.catalog` | Existing settings/search projection and new Blade shell |
| `/store-web/products/data/{pageNumber?}`, `shop.products.data` | Products and pagination |
| `/store-web/categories/data`, `shop.categories.data` | Categories with nested subcategories |
| `/store-web/sizes/data`, `shop.sizes.data` | Size choices |
| `/store-web/colors/data`, `shop.colors.data` | Color choices |

The controller currently requires session context for company settings. `TenantScope` uses session tenant when present and omits its tenant restriction when absent. The local bridge prevents anonymous access to these data endpoints; it does not repair their productive/public contract.

The product endpoint loads enabled materials and active stock items, aggregates available stock, excludes nonpositive aggregate stock, chooses the first active/default price list, computes current prices, filters and slices **nine per page in memory**. Search order and matching remain unchanged. The first price list, stock aggregation and publication/facet policies still have unresolved company/publication limitations. The frontend does not fix, recompute or reinterpret them.

## Request and Filter Mapping

- `category_id`: one scalar, reset subcategory when category changes.
- `subcategory_id`: one scalar from the selected category's response.
- `size_ids[]`, `color_ids[]`: multiple values, serialized as the existing array contract (the controller also accepts CSV).
- `min_price`, `max_price`: strings, with the same legacy `cleanPrice` normalization; no new pricing logic or range policy.
- `search`: current query / header input. The backend also accepts `product_search` with priority, but the preserved full-catalog flow sends `search`, as legacy catalog.js did. No call to search-product.
- Pagination uses route pageNumber, and returned `currentPage`, `totalPages`, `totalFilteredRecords`. The existing page-window algorithm is retained; no page-size/infinite-scroll/backend strategy change.

Category, subcategory, size and color changes reload page 1 as in the current UI. Price apply uses the approved “Ver resultados” action and closes the drawer. Clear resets real filters and reloads page 1; search remains a separate value. Search submit/clear reset page 1 and update the URL query. An AbortController plus request sequence prevents old product responses/errors from replacing the latest state. Every request sends same-origin credentials and JSON Accept; no company/tenant identifiers are appended.

## Cards and States

| Field | Binding |
| --- | --- |
| `full_name` | Escaped DOM text in h2 and image alt; existing unnamed fallback |
| `image_url` | HTTP(S) image URL; permanent `store-web/img/no_image.png` on missing/failed image |
| `detail_url` | HTTP(S) link from the response; missing/invalid links disabled |
| `price_text` | Supplied string, using current legacy missing-text fallback; controlled by existing show-prices setting |
| `stock` | Preserved in data-stock; supplied sign determines availability presentation, no inventory arithmetic |
| `category` | Preserved in data-category; no extra visible design slot invented |

No rating, fictional previous price, promotional percentage, offer or newness badge is bound. Backend cards with a supplied nonpositive stock can use the approved unavailable styling, but no unavailable product is manufactured and no “include exhausted” filter exists. Text is assigned through DOM textContent, not unsafe HTML concatenation. Invalid URL schemes are refused.

Loading, empty and failure messages use plain existing typography within the approved regions. Facet failure does not populate global or mock values. A product error clears stale cards/pagination. No persistence, purchase, variant or WhatsApp behavior is added.

## VISUAL PRESENT / FUNCTIONAL PENDING

Novedades, Destacados, Promociones, Disponibles chips, Marca, Condición, Oferta/promotions, Agotados inclusion and real multicategory remain pending. Chips are disabled links with no href; unsupported drawer inputs are disabled. Preview pagination is inert, while authenticated pagination comes only from the response. The anonymous sample badges are marked as fixture-only; the authenticated renderer never creates them.

## QA Evidence

- `StoreWebLocalPreviewTest.php`: **7 tests / 107 assertions**. SQL connections are denied in these unit/kernel scenarios; auth membership and controller are doubled only for the positive bridge case. Covers anonymous, OFF, production/staging/testing, query spoofing, missing/foreign/inactive context, disabled users and platform users. Authorization is not inferred from a query flag.
- `StoreWebAuthenticatedLiveQaTest.php`: **1 test / 53 assertions** after the final fixture-marker assertions. Explicit opt-in only (`STORE_WEB_LIVE_QA=1`), real database **READ ONLY transaction**, rolled back. Existing users/memberships are selected without changing them; Laravel `actingAs` and isolated in-memory sessions exercise the real middleware/controller. These are authenticated kernel sessions, not a claim of manual browser login. All returned pages and facet IDs checked belong to the session tenant. Requests cover actual available filters, search, price, pagination and ignored tenant/company query spoofing. No business writes.
- `shop-contract-qa.cjs`: browser HTTP doubles test outbound contracts, scalar/multiple selection, resetting page, exact text/price/link binding, escaping, price hiding, clear, empty/error/facet failure, and a deliberately delayed obsolete search response. No test response endpoint is installed in Laravel.
- `shop-visual-qa.cjs`: anonymous preview vs unchanged shop.html in **320/375/390/576/767/768/991/992/1200/1440**. Zero material pixel differences; 0–2 raw pixels differ by only one color-channel level. No overflow; frozen 2/3/4/5-column breakpoints and fonts preserved. The reference tester is excluded as in 9.1.
- `shop-live-render-qa.cjs`: browser replay of the **actual authenticated HTML/JSON** captured by kernel QA, across the same ten widths for each tenant (20 rows). Only the asset host is normalized to localhost. No overflow or undecoded images after fallback; real drawer content checked. This distinguishes live backend assertions from browser rendering; no browser authentication bypass is created.
- Existing 30-viewport bridge regression passes for Home/Shop/Detail; all 54 migration asset hashes still match. The changed catalog-data.js was an empty reserved adapter, outside that copied-asset manifest.

| Authenticated context | Company | Products total | Categories | Sizes | Colors |
| --- | ---: | ---: | ---: | ---: | ---: |
| Gamarra, tenant 3 | 3 | 1 | 1 | 0 | 0 |
| EDESCE, tenant 1 | 1 | 11 | 5 | 9 | 6 |

Observed product sets are disjoint. This evidence does **not** certify company isolation within a tenant, public access, pricing correctness, warehouse policies, or SW-01 resolution.

Reports: `docs/store-web/shop-authenticated-live-qa.json`, `shop-contract-qa.json`, `shop-visual-qa.json`, `shop-live-render-qa.json`. Raw catalog snapshots and screenshots stay in the local task artifact directory, not application public routes or repository fixtures.

### Existing asset gaps

EDESCE responses reference five files absent from this checkout: `images/material/12.png`, `15.webp`, `4.jpg`, `1.webp`, and `images/material/variants/variant_16_6a3b2259b8cc3.webp`. Their initial image requests return 404; the permanent fallback loads and preserves card geometry. Browser replay status is **PASS WITH EXISTING ASSET GAPS**. Do not claim zero network/console resource errors for these real images. No image records, filenames or uploads were modified. Static Store Web preview assets return 200.

## Backend Sync Notes

[OPEN]
Point: SW-01 public context and company isolation.
File / endpoint: Store Web routes/controller, TenantScope; local bridge middleware.
Impact: no public rollout; local authenticated QA only. Company-level stock/price rules remain whatever the current backend does.
What backend needs to define or correct: validated public resolver, company/warehouse restrictions, product URL/binding policy and missing context behavior.
Frontend can continue: Yes, using this explicitly authorized non-production bridge.

[OPEN]
Point: SW-02/SW-03 publication, availability and pricing.
File / endpoint: getDataProductsV2 and facet queries.
Impact: first default list selection, stock aggregation, minimum price/moneda and facet inconsistencies remain.
What backend needs to define or correct: canonical company/publication/price/stock policy; frontend will re-audit when delivered.
Frontend can continue: Yes, current fields unchanged.

[OPEN]
Point: SW-04 filters and SW-05 variants/detail.
File / endpoint: drawer, data endpoints and product detail route.
Impact: unsupported commercial filters remain inert; real detail/variant integration is not part of this phase.
What backend needs to define or correct: multicategory/commercial filter semantics and valid variant/detail/WhatsApp contracts.
Frontend can continue: Yes, only current supported filters.

[OPEN]
Point: SW-06 editorial/promotions and SW-07 branding/assets.
File / endpoint: cards, Home, current company settings and image URLs.
Impact: no manufactured promotions; approved neutral shell remains; five EDESCE source assets are absent locally.
What backend needs to define or correct: editorial/badge rules, public branding projection and valid image paths/assets.
Frontend can continue: Yes, approved shell and permanent fallback.

## Files and Revisit Triggers

Changed: `app/Http/Kernel.php`, `app/Http/Middleware/StoreWebLocalPreview.php`, new `StoreWebAuthenticatedDataBridge.php`, config comment, layout guard/non-production metadata, catalog Blade, shared header search attributes, filter drawer, `public/store-web/js/catalog-data.js`, QA tests/harness metadata and reports/documentation.

Unchanged: StoreWebController, TenantScope, productive route definitions, models/business queries, CSS/Sass/visual JS, Home's content/adapter and Detail's content/behavior. No database record was created or updated. No login credentials/cookies were recorded.

Re-audit on closure of each SW point, changes to session/context authorization, public deployment, company/warehouse price-stock policy, supported filters, product response fields or real Detail integration. The local bridge must not become a productive tenant resolver by changing APP_ENV checks.
