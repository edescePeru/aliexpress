# Store Web — preview tooling

REAL NEXT 9.6: see [Final Independence](../../docs/REAL-NEXT-STORE-WEB-FINAL-INDEPENDENCE.md). Application/regression tests need no reference checkout. Historical pixel-comparison tools require an explicit existing external reference directory through `STORE_WEB_QA_REFERENCE_ROOT` plus `STORE_WEB_QA_REFERENCE=1`; with no valid root `/reference/*` is unavailable. This is optional developer tooling, never a production route or build dependency. `independence-static.cjs` verifies permanent hashes/legacy files; `independence-qa.cjs <shop-snapshot> <detail-snapshot>` requires the reference directory to be absent and replays freshly captured authenticated kernel responses.

REAL NEXT 9.5: [Product Detail Contract](../../docs/REAL-NEXT-STORE-WEB-PRODUCT-DETAIL-CONTRACT.md). Use PHP 7.3.33 with `vendor/bin/phpunit --filter StoreWebProductDetail`; live test requires `STORE_WEB_LIVE_QA=1` and runs in READ ONLY SQL. Optional `STORE_WEB_DETAIL_QA_OUTPUT` captures real authenticated HTML; `STORE_WEB_DETAIL_CASES_OUTPUT` captures synthetic edge cases. `detail-visual-qa.cjs <output-directory>` compares frozen fixtures. `detail-render-qa.cjs <live-json> <cases-json> <output-directory> [report-json]` tests explicit browser replay with WhatsApp intercepted (no sends). All ten widths are covered for real snapshots.

REAL NEXT 9.4: [Shop Contract](../../docs/REAL-NEXT-STORE-WEB-SHOP-CONTRACT.md) supersedes the data-access policy: local authenticated users with existing valid active tenant/company context can use the catalog/data endpoints. Anonymous local requests remain fixtures; OFF/non-local stays closed. Run `shop-contract-qa.cjs` for controlled response tests and `shop-visual-qa.cjs <output-directory>` for reference parity. The opt-in `StoreWebAuthenticatedLiveQaTest.php` uses READ ONLY SQL and authenticated in-memory sessions for Gamarra/EDESCE; it does not seed or modify records. `shop-live-render-qa.cjs <snapshot-json> <output-directory>` separately verifies browser rendering of captured authenticated responses.

REAL NEXT 9.3: see [Home Contract](../../docs/REAL-NEXT-STORE-WEB-HOME-CONTRACT.md). Current Home comparison: `node tests/StoreWebPreview/home-qa.cjs <output-directory>` with Laravel on 8080 and the reference-only harness on 8097 (`STORE_WEB_QA_REFERENCE=1`). `local-bridge-qa.cjs` remains the three-page regression check. The original `qa.cjs` below is historical 9.1 tooling: its exact attribute-DOM expectation predates the authorized Home search and disabled-link semantics; use home-qa.cjs for the current Home.

REAL NEXT 9.2 enables the normal Laravel server on port 8080 with APP_ENV=local and STORE_WEB_PREVIEW=true. See [Local Preview Bridge](../../docs/REAL-NEXT-STORE-WEB-LOCAL-PREVIEW.md). Run `node tests/StoreWebPreview/local-bridge-qa.cjs` for actual-server QA and `vendor/bin/phpunit tests/Feature/StoreWebLocalPreviewTest.php` with PHP 7.3 for fail-closed kernel tests.

The following isolated harness is retained for historical 9.1 visual comparisons.

REAL NEXT 9.1 uses frontend fixtures, not catalog records. Do not deploy these staged views as a functional public catalog. SW-01 through SW-07 remain open.

The preview uses the project's installed Laravel 7 Blade engine, view factory and named URL generator under PHP 7.3.33. It **does not boot the application's providers, HTTP kernel, controller, session, authentication, model binding or database service**. In-memory URL metadata mirrors three existing route names; it does not register new application routes or change `routes/web.php`.

Start from the `aliexpress` directory in PowerShell:

```powershell
$env:STORE_WEB_QA_REFERENCE = '1'
$env:STORE_WEB_QA_REFERENCE_ROOT = 'C:\path\to\archived-reference'
& 'C:\wamp64\bin\php\php7.3.33\php.exe' -S 127.0.0.1:8097 tests/StoreWebPreview/router.php
```

Local fixtures (these URLs are handled only by the isolated preview server):

- `http://127.0.0.1:8097/store-web/inicio`
- `http://127.0.0.1:8097/store-web/catalogo`
- `http://127.0.0.1:8097/store-web/product/frontend-fixture`

Reference pages are available under `/reference/index.html`, `/reference/shop.html` and `/reference/product-details.html` only when `STORE_WEB_QA_REFERENCE=1` and `STORE_WEB_QA_REFERENCE_ROOT` names an existing directory. With `0` or no variable, that entire prefix returns 404, and all three fixtures continue to render from permanent assets. The application data endpoints and all real product IDs always return 404 in this harness. No HTTP-kernel fallback is allowed.

Run the guard check:

```powershell
& 'C:\wamp64\bin\php\php7.3.33\php.exe' tests/StoreWebPreview/guard-check.php
```

Run the browser QA with existing Node packages `playwright` and `pngjs` available to Node (e.g. the desktop workspace dependency bundle via `NODE_PATH`):

```powershell
node tests/StoreWebPreview/qa.cjs C:/path/to/qa-output
```

Uses installed Edge headless by default; `STORE_WEB_QA_BROWSER=chrome` selects installed Chrome. It does not install browsers/packages. Screenshots and JSON go to the supplied output folder. The test compares all three pages at 320, 375, 390, 576, 767, 768, 991, 992, 1200 and 1440px. Only the reference's explicitly excluded tester DOM is removed for screenshot comparison; reference assets are never rewritten. The raster check requires zero pixels differing by more than one 8-bit color-channel level, after image decode and raster settling; raw differences remain in the report. This accounts for observed one-level image raster rounding (0–4 raw pixels per full-page capture). DOM structure is compared after URL normalization; overflow, images/fonts and interactions are checked separately. Pass `--verify-captures` after the output folder to re-evaluate saved PNGs and the associated report without rerunning the browser; this is only for unchanged implementation/captures.

The permanent layout now requires environment local, config store-web.preview strictly true, and boolean storeWebFixture=true. Both the controlled real-kernel bridge and this isolated harness supply these values explicitly. The harness remains database-free. The actual Laravel bridge rejects all other Store Web requests before session, binding or controllers while SW-01 remains open.

`frontend-fixture` is an isolated preview key, never a database ID. The document title, meta status, HTML comment and body attribute explicitly identify fixture content; no banner or layout styling is added to the approved visual.

Compiled QA views use the system temp directory `venti-store-web-qa-blade`; application view caches are not rebuilt. Stop the preview with Ctrl+C. Never expose this test router through Apache or a production document root.
