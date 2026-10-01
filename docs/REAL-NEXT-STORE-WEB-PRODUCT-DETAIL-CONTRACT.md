# REAL NEXT 9.5 — Store Web Product Detail Integration

**STORE WEB PRODUCT DETAIL FRONTEND INTEGRATED — BACKEND CONTRACT TEMPORARY**

**LOCAL AUTHENTICATED BRIDGE — NON PRODUCTION. SW-01…SW-07 BACKEND SYNC OPEN.**

## Current Temporary Backend Contract

`GET /store-web/product/{material}` → `shop.product.show` → `StoreWebController::showProduct(Material $material)`. Route, parameter, implicit binding, controller, scopes and business queries are unchanged. `/store-web/producto/{material}` remains blocked.

`StoreWebLocalPreview` checks local environment + explicit `STORE_WEB_PREVIEW=true` before sessions. Detail now passes to `StoreWebAuthenticatedDataBridge` after StartSession and before SubstituteBindings. Real IDs require an authenticated enabled non-platform user, an active tenant matching that user, and an active company assigned through active membership in that tenant. Both context IDs come only from the existing authenticated session. No default context, query/body override or frontend tenant resolver is added.

Anonymous local detail requests still return the isolated fixture without model lookup, as authorized in 9.2. The reserved `frontend-fixture` value always returns that fixture, including authenticated Home navigation; it never invokes business binding. Authenticated requests for real IDs with missing/invalid context return 404. OFF/non-local returns 404 for every Store Web path. Existing active-session access is required for real QA on the same origin; fixtures never impersonate tenant data.

Real responses are private/no-store, noindex/nofollow and marked `local-authenticated-data-bridge-non-production`; page metadata/title mark QA. Bridge flags are restored after each request. TenantScope applies during implicit binding only after the verified session is available. This does not establish public company publication policy or resolve SW-01.

The action eager-loads category, subcategory, brand, active presentations ordered by quantity, active variants with talla/color, and active stockItems with variant attributes, inventoryLevels and priceListItems for the first active default price list. Its price/stock rules remain the temporary functional truth. No product-wide serialization or lazy-loading accessors are used by the presentation adapter.

## Real Bindings

| Approved slot | Existing value | Integration |
| --- | --- | --- |
| Title / CTA name | material.full_name | Escaped Blade text / safe JSON |
| Marca | Eager-loaded brand.name | Omitted if absent; no lazy query |
| Código | code, then codigo | Omitted if absent |
| Price | priceText + catalog.show_prices | Current string, no frontend calculation |
| Availability | stockAvailable | Positive / nonpositive; unknown stays neutral |
| Gallery | images[{image,thumb,label}] | Same stage/thumb/counter markup |
| Attribute groups | sizes / colors | Disabled, unselected informational options |
| Logo/footer/socials | Existing company settings | Escaped text and validated HTTP(S) links |
| WhatsApp | Existing sanitized company number | Product name + canonical route URL only |
| Back link | store-web.catalog | Approved single back link |

The name of the business is not a separate supplied public field: the existing neutral label remains. No category description, internal material description, unit accessor, promotion, previous price, discount or generic attribute is inferred.

## Gallery Contract

`catalog-main.js` remains unchanged and owns active thumbnail, `aria-pressed`, stage src/alt and counter. Real gallery uses precisely the approved classes and hierarchy, with count based on supplied images. Zero images becomes one permanent fallback; one and many retain the same interaction. No Owl/Magnific or variant resolver is introduced.

`product-detail.js` handles failed uploads without requests for business data. It also handles failures completed before the deferred script loads. Failed main-image URLs are replaced in thumbnail navigation to avoid repeated selection of a known missing image. A missing thumbnail alone does not invalidate a possibly valid full image.

The only product placeholder is `public/store-web/img/no_image.png`. Legacy `no-image.png` and `no_image.png` placeholder paths from the controller are normalized before rendering. No request is made to the hyphenated fallback. CSS/fonts/JS/fixture imagery remain under `public/store-web`; real uploads retain their existing backend URLs. Runtime does not depend on `project-catalog`.

Data quality: the five missing EDESCE catalog assets recorded in 9.4 remain open; no upload or database record was changed. Detail additionally exposes missing variant images: 22 distinct real missing paths across the full detail sample (including the previously identified assets). The complete path inventory and disk-existence checks are in `store-web/detail-authenticated-live-qa.json`; browser failures and successful fallback coverage are in `store-web/detail-render-qa.json` and `store-web/detail-render-page2-qa.json`. None of the non-placeholder product image paths supplied for the 12 QA products exists locally. The image-present scenario is therefore verified with existing permanent images in an explicitly synthetic, non-persisted rendering case; it is not claimed as real product coverage.

## Price Contract

Render `priceText` only when the real show_prices flag is enabled. Never render a previous price, sale badge or discount. A missing/empty price string uses “Precio por consultar”; the browser does not derive a price from variants or presentations.

Current backend inconsistency retained: positive price-list prices are collected across active stockItems; absent prices fall back to zero, so an actual controller string `S/. 0.00` remains visible. `Desde` depends on active variants and count of prices, even if all prices are equal. Default price-list/company policy and tax/currency/public-price rules require backend alignment; frontend does not correct them.

## Availability Contract

`stockAvailable` is the controller sum of max(0, on-hand minus reserved) per active stockItem. The frontend displays “Disponible” only for a positive value, otherwise “No disponible”; missing nonnumeric data uses “Disponibilidad por confirmar”. No threshold, urgency label, variant availability or warehouse/company restriction is invented. Approved availability markup/colors are retained; no new CSS for unavailable state is introduced. CTA is an inquiry, not a stock reservation.

## Attribute Options

**VISUAL ATTRIBUTE OPTIONS — VARIANT RESOLUTION PENDING**.

Real talla/color names replace the sample attribute labels within the approved fieldsets. Inputs are disabled and unselected, with an explanatory note and `aria-describedby`. They cannot assert a valid combination or mutate gallery, price or stock. Empty groups are omitted. No Cartesian product or default valid variant is inferred.

## Variant Resolution Pending

SW-05 remains open: backend must define valid combinations, concrete variant identity, availability/pricing per variant, image policy and presentation relationship before selection becomes functional.

Presentations are already eager-loaded, active and ordered by quantity; `catalog.show_presentations` is delivered. The presentation adapter reserves that loaded collection without querying or serializing it. No selector/table is rendered: public label/quantity/unit/price and relationship to variants are not sufficiently defined. The previous legacy table was commented out; it is not restored.

## Description and Characteristics

Legacy detail displayed literal lorem ipsum in its description tab; it did not establish material.description as approved catalog copy. Intro/description therefore remain prepared in the fixture only; no internal description is published. **BACKEND CONTRACT PENDING / SW-06**.

The real characteristics section reuses the approved definition-list markup for only supplied brand, code and availability. Mock “Unidad”, generic features and other sample text are absent. Backend must define the public characteristics schema before additional rows appear.

## WhatsApp Contract

Both desktop inline and fixed mobile buttons use the existing sanitized digits. Message: product name + URL generated by `shop.product.show` for the bound material, without context query parameters, variant, price or stock assertion. `window.open` uses `noopener,noreferrer`. No jQuery or automatic outbound request.

Absent number disables both buttons and shows the existing note slot with “Contacto por WhatsApp no disponible.” The approved green CTA styling is unchanged; disabled semantics are real. Neither QA company currently supplies a number. Real empty-number handling is tested; enabled desktop/mobile links are tested using a clearly synthetic rendering payload, with `window.open` captured, **zero messages sent** and no external navigation.

## Related Products Pending

**BACKEND CONTRACT PENDING.** Four approved related cards remain in fixture mode only. Real mode renders no related mocks, recommendations query or fabricated relationship.

## Branding/Footer

Existing logo setting maps to its current `images/logo/` location (or an absolute HTTP(S) URL). Absent/failed logos use the permanent approved logo asset. Header, mobile menu and detail footer share this value. Footer uses the delivered description as escaped text. Instagram/TikTok/Facebook/YouTube retain the approved icon structure; missing/invalid URLs become noninteractive spans with the same classes, never functional `href="#"`. Twitter/Pinterest settings are delivered but have no approved icon slots and are not added. Company setting/public branding fallback remains SW-07.

## Visual and Mobile Contract

Reference: frozen `project-catalog/product-details.html`. No CSS, Sass, spacing, type, color, breakpoint, gallery layout, card, CTA frame or visual JS changes. Fixture pixel comparison covers 320/375/390/576/767/768/991/992/1200/1440: zero differences above a one-channel raster tolerance (0–4 raw pixel differences).

`style.css` SHA-256: `eb43009f04a0298356f56781b9c128c5969fdb4ef9333f7fde05a508b57e1431`. All 54 original migrated assets match their recorded final hashes. The adapter JS was a separate reserved file outside that frozen asset set.

Real data differences are expressly authorized contract adaptations: variable image/attribute counts and content, no mock promotions/description/related cards, disabled unresolved options and unavailable contact. Existing safe-area inset CSS remains intact; emulated viewports have zero physical notch inset. Browser QA checks mobile fixed CTA below 768, inline CTA from 768, sticky summary from 992, gallery selection/counter, loaded images/fonts and zero horizontal overflow. Browser replay is explicitly separated from authenticated HTTP-kernel testing; no browser login is fabricated.

## Files Changed

Production-facing integration (all limited to Store Web):

- `app/Http/Middleware/StoreWebLocalPreview.php`, `StoreWebAuthenticatedDataBridge.php`: detail allowlist + isolated guest/reserved fixture dispatch before binding.
- `resources/store-web/detail.php`: presentation-only adapter, no SQL.
- `resources/views/shop/detailCatalog.blade.php`: real/fixture dispatch, title, CTA/scripts.
- `resources/views/shop/partials/store-web/detail-real.blade.php`, `detail-cta.blade.php`: real slots inside approved structures.
- `resources/views/shop/partials/store-web/gallery.blade.php`: real images plus unchanged fixture.
- `resources/views/shop/partials/store-web/header.blade.php`, `mobile-menu.blade.php`, `footer.blade.php`: detail branding only; other page appearance preserved.
- `public/store-web/js/product-detail.js`: image recovery and WhatsApp only.

Validation/docs: `tests/Feature/StoreWebLocalPreviewTest.php`, `StoreWebProductDetailTest.php`, `StoreWebProductDetailLiveQaTest.php`; `tests/StoreWebPreview/detail-visual-qa.cjs`, `detail-render-qa.cjs`, README; this contract, dated notes in Shop/Local Preview contracts; `docs/store-web/detail-*.json` evidence. No StoreWebController, business model/scope, productive route, CSS/Sass or catalog-main.js change.

## QA Evidence

- PHP 7.3.33: **10 tests / 512 assertions PASS** across Store Web guard, presentation, authenticated Shop and Detail suites, database-enforced READ ONLY transaction for live tests; no migrations, seeders or business writes.
- Gamarra: product 19 (simple, placeholder). EDESCE: all 11 current catalog products, including 18/16/13 with 10/12/8 variants and real talla/color arrays; images missing locally are documented above. Cross-tenant model binding denied; client context spoof ignored; alias blocked.
- `store-web/detail-authenticated-live-qa.json`: real action values, eager loading, relation tenant checks, gallery source inventory.
- `store-web/detail-visual-qa.json`: reference parity and fonts at ten widths, zero business XHR/fetch in fixture.
- `store-web/detail-render-qa.json` + `store-web/detail-render-page2-qa.json`: **120 real product/viewport renderings + 8 synthetic edge-case renderings PASS**. Captured authenticated HTML plus synthetic 0/1/many-image, missing-upload, price-hidden, unavailable-stock, disabled/active-contact cases; six WhatsApp openings intercepted, zero sent. Image 404s are documented data quality/test faults; no JS exceptions or business AJAX.
- Three-page anonymous regression: **30 viewports PASS**, 568 successful asset responses, all 54 original hashes unchanged, no console/page errors or business requests; menu, drawer, gallery and navigation pass.

## Backend Sync Notes

[OPEN]
Point: SW-01 public isolation/context.
File / endpoint: StoreWebController::showProduct, TenantScope, authenticated bridge.
Impact: local QA only; not a public company catalog authorization contract.
What backend needs to define or correct: trusted public tenant/company resolution and per-product publication/image access policy.
Frontend can continue: Yes, under the local authenticated bridge only.

[OPEN]
Point: SW-02…SW-04 existing price, stock and catalog/filter contracts (see initial audit).
File / endpoint: current Store Web product/data actions.
Impact: default-price selection, aggregation and catalog/detail semantics remain temporary.
What backend needs to define or correct: final scoped price/stock/filter rules; reconcile endpoint outputs without frontend inference.
Frontend can continue: Yes, displaying supplied values unchanged.

[OPEN]
Point: SW-05 variants/presentations.
File / endpoint: showProduct loaded variants, sizes, colors, presentations.
Impact: options are informational only.
What backend needs to define or correct: valid combinations and variant-specific price/stock/image/presentation contract.
Frontend can continue: Yes, disabled options.

[OPEN]
Point: SW-06 public content/characteristics/related products.
File / endpoint: detail view and showProduct response.
Impact: no approved public intro/description/generic schema/related collection.
What backend needs to define or correct: explicit public fields and related policy/response.
Frontend can continue: Yes, omitted real mocks with prepared fixture slots.

[OPEN]
Point: SW-07 branding/contact/settings.
File / endpoint: SettingService company settings used by showProduct.
Impact: no number in current QA contexts; public branding policy still pending.
What backend needs to define or correct: final company-scoped public settings/fallback contract.
Frontend can continue: Yes, supplied local settings and disabled missing contacts.

## Revisit Triggers

Reaudit this screen when backend closes any SW point; changes implicit-binding/public access policy, default price-list/company scope, price/stock aggregation, variant/presentation response, public description/characteristics, related collection or company settings. Restored upload files require rechecking their real gallery paths. Production/public activation needs the final backend contract; this local bridge is not that activation. Do not delete `project-catalog` in this phase.
