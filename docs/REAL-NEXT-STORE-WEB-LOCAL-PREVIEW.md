# REAL NEXT 9.2 — Store Web Local Preview Bridge

REAL NEXT 9.5 (2026-09-29): real product IDs now pass through the authenticated local bridge before implicit binding. Anonymous/reserved frontend-fixture requests retain the isolated visual fixture; OFF/non-local remain blocked. See [Product Detail Contract](REAL-NEXT-STORE-WEB-PRODUCT-DETAIL-CONTRACT.md).

Historical 9.2 policy below. REAL NEXT 9.4 adds the explicitly authorized LOCAL AUTHENTICATED BRIDGE — NON PRODUCTION for catalog and four data endpoints after session/context validation. Anonymous preview and non-local/OFF rejection remain. See [Shop Contract](REAL-NEXT-STORE-WEB-SHOP-CONTRACT.md).

## Purpose

VISUAL QA / FRONTEND PREVIEW only. SW-01 remains BACKEND SYNC OPEN. No product binding, tenant inference, SQL fixtures or commercial functionality was introduced.

## Exact 404 cause

Before this phase, `resources/views/layouts/storeWeb.blade.php:5` contained:

```php
abort_unless(app()->environment(['local', 'testing']) && ($storeWebFixture ?? false) === true, 404);
```

The ordinary controller did not supply `storeWebFixture=true`. The 9.1 isolated router created an Illuminate application with environment `testing` and explicitly passed that boolean. It bypassed the real HTTP kernel. The view guard did not protect against queries performed by a controller before rendering.

## Explicit local switch and early protection

`config/store-web.php` reads `STORE_WEB_PREVIEW`, default false. `.env.example` declares false. This workstation's ignored `.env` has `APP_ENV=local` and `STORE_WEB_PREVIEW=true`. No configuration cache existed when enabling it.

`StoreWebLocalPreview` is first in the `web` middleware group, before cookies, session, CSRF and model binding. For Store Web requests it requires BOTH environment `local` and strict boolean config true. It directly renders only these named GET/HEAD routes:

| URL | Existing route name | Fixture view |
| --- | --- | --- |
| `/store-web/inicio` | `store-web.home` | `shop.home` |
| `/store-web/catalogo` | `store-web.catalog` | `shop.catalog` |
| `/store-web/product/{material}` | `shop.product.show` | `shop.detailCatalog` |

The material segment is ignored, including numeric IDs: no Material binding or controller execution occurs. Fixture content remains in the existing staged Blades and is not a productive backend contract. Responses have no-store/private, noindex/nofollow and `X-Store-Web-Preview: visual-qa-frontend-fixture`. The layout retains a second guard at line 5 requiring local + config true + internal boolean fixture flag.

OFF, missing config, or any non-local environment fails closed. Production cannot enable this bridge merely by setting the variable true. Query parameters and session state cannot enable it. All other Store Web routes, including data APIs and legacy `/tienda` and `/producto/{material}` aliases, return 404 in this bridge rather than falling through to unsafe controllers. Route and controller source files were not changed. Non-Store-Web paths pass through normally. This blocking policy must be revisited explicitly when SW-01 is resolved.

## Normal Laravel server

```powershell
# Local .env: APP_ENV=local and STORE_WEB_PREVIEW=true
# If using cached configuration, clear it after changing the switch:
& 'C:\wamp64\bin\php\php7.3.33\php.exe' artisan config:clear
& 'C:\wamp64\bin\php\php7.3.33\php.exe' artisan serve --host=127.0.0.1 --port=8080
```

Open `http://127.0.0.1:8080/store-web/inicio`, `/store-web/catalogo` and `/store-web/product/frontend-fixture`. Set the switch false to close preview. Never use `project-catalog` as the application's document root or asset dependency.

## Validation

`tests/Feature/StoreWebLocalPreviewTest.php` exercises the actual Laravel HTTP kernel. Both read and write PDO resolvers on the default application connection are replaced with a throwing function before requests: any attempted SQL connection in rendering, binding, middleware or controllers fails the test. ON fixtures pass for string and numeric material values; OFF and production/staging/testing return 404, including attempted query-string bypasses and data APIs. No alternate business connection or business query is used by the bridge.

`tests/StoreWebPreview/local-bridge-qa.cjs` runs installed Edge against the normal server on 8080: three pages at 320, 375, 390, 576, 767, 768, 991, 992, 1200 and 1440px; asset status, image decoding, Montserrat, overflow, fixture metadata, menu focus/Escape, mobile/desktop drawer, search clear, gallery, bottom nav and mobile CTA. It rejects console/page/resource errors, external requests and all XHR/fetch. Results: `docs/store-web/local-preview-qa.json`.

All asset hashes in the 9.1 migration manifest are checked unchanged. No CSS, JS, fonts, images, page markup, card, drawer, gallery or responsive rules were edited in 9.2. Only the layout's nonvisual guard/comment changed. Runtime requests use permanent `/store-web/` assets and never `project-catalog`. Pixel comparisons remain the historical 9.1 evidence; this phase validates unchanged assets and actual-kernel behavior.

## Files changed in 9.2

- `.env.example`, local ignored `.env`, `config/store-web.php`.
- `app/Http/Middleware/StoreWebLocalPreview.php`, `app/Http/Kernel.php`.
- `resources/views/layouts/storeWeb.blade.php` (guard/comment only).
- `tests/Feature/StoreWebLocalPreviewTest.php`, `tests/StoreWebPreview/local-bridge-qa.cjs`.
- `tests/StoreWebPreview/router.php` and `guard-check.php`: isolated compatibility with the stricter local/config guard; README updated.
- This document and `docs/store-web/local-preview-qa.json`.

No real backend integration is authorized by this bridge. Final status: **STORE WEB LOCAL PREVIEW READY**.
