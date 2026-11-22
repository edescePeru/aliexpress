# REAL NEXT — Company Stock Items

2026-11-18 · Company Product Catalog FRONTEND INTEGRATED · TENANT + AUTHORIZED COMPANY · Branch NOT APPLICABLE.

## Domain Meaning

Habilita StockItems/SKUs para una empresa. No representa stock físico/disponible, precio, publicación web ni venta automática. Se sustituyó el título legacy «Vende» por «Habilitado para empresa» conforme al contrato real enabled.

## Routes

Prefijo real preservado: `/dashboard/dashboard/company-stock-items`. Duplicación nominal, sin cambio de rutas.

| Method | Suffix | Name | Boundary |
|---|---|---|---|
| GET | raíz | companyStockItem.index | Vista |
| GET | /data | companyStockItem.data | Lectura paginada |
| POST | /status | companyStockItem.status | Mutación NO ejecutada |

## Permission

Las tres rutas exigen permission:manage_companyStockItem además del contexto/auth vigente. Owners EDESCE4 y Gamarra7: Gate real true en proceso con permission cache array. No se asume bypass Owner. Allowlist añade sólo companyStockItem.index; sin data/status ni wildcard. Navegación conserva manageCompanyStockItem.

## Tenant / Company Scope

Tenant obligatorio. StockItem y CompanyStockItem usan BelongsToTenant. resolveAllowedCompany filtra empresas activas del tenant para Owner; otros usuarios requieren company activa + pivot is_active del usuario y tenant coincidente. Branch N/A. Brand/Category scope tenant, sin scope company artificial.

## Company Selector

Opciones proceden del backend. La company elegida se vuelve a autorizar en getData y updateStatus, no se confía en HTML. Si no hay empresas, UI muestra «Sin empresas autorizadas» y no consulta data con ID vacío. Contextos de QA establecidos sólo en sesión array del proceso, no cambio de sesión compartida.

## Dataset

MySQL READ ONLY con ROLLBACK; ningún fixture real. StockItems49 y CompanyStockItems63. Comparación por IDs completos, no sólo conteos:

| Contexto | Todos | Enabled | Disabled | Páginas todos | Companies selector |
|---|---:|---:|---:|---:|---|
| T1/C1 | 47 | 47 | 0 | 3 | [1, 2, 5] |
| T1/C2 | 47 | 14 | 33 | 3 | [1, 2, 5] |
| T3/C3 | 2 | 2 | 0 | 1 | [3] |

C1 IDs todos: `[8, 95, 98, 101, 96, 99, 102, 97, 100, 103, 104, 81, 84, 88, 92, 85, 89, 93, 82, 86, 90, 83, 87, 91, 74, 75, 76, 107, 109, 108, 110, 73, 77, 79, 78, 80, 71, 94, 72, 45, 48, 46, 49, 47, 50, 51, 52]`.

C1 IDs habilitados: `[8, 95, 98, 101, 96, 99, 102, 97, 100, 103, 104, 81, 84, 88, 92, 85, 89, 93, 82, 86, 90, 83, 87, 91, 74, 75, 76, 107, 109, 108, 110, 73, 77, 79, 78, 80, 71, 94, 72, 45, 48, 46, 49, 47, 50, 51, 52]`.

C2 IDs todos: `[8, 95, 98, 101, 96, 99, 102, 97, 100, 103, 104, 81, 84, 88, 92, 85, 89, 93, 82, 86, 90, 83, 87, 91, 74, 75, 76, 107, 109, 108, 110, 73, 77, 79, 78, 80, 71, 94, 72, 45, 48, 46, 49, 47, 50, 51, 52]`.

C2 IDs habilitados: `[95, 98, 101, 96, 99, 102, 97, 100, 103, 104, 107, 109, 108, 110]`.

C3 IDs todos: `[106, 105]`.

C3 IDs habilitados: `[106, 105]`.

C1/C2 comparten universo tenant intencionalmente; el estado es por company. Gamarra IDs105/106, intersección vacía con EDESCE. Owner EDESCE puede consultar C1/C2; C3 e inexistente999999 ->404. Gamarra C1/C2/inexistente ->404. Evidencia por invocación directa del controller con contexto autenticado, no login/pipeline HTTP completo. Rama de usuarios no Owner revisada estáticamente, no certificada con sesión real en esta fase.

## StockItem Inclusion

Sólo stock_items.is_active=true del tenant, incluidos los aún no habilitados. No se limita a CompanyStockItems existentes. Sin carga total en cliente; paginate20 backend intacto. Columnas preservadas: producto/material, marca, categoría, variante, SKU, barcode, enabled. Sin precios ni stock añadidos.

## Filters

Payload GET intacto: company_id, brand_id, category_id, status(all/enabled/disabled), search, page. Búsqueda SKU/barcode/display_name/material full_name/description. Resets página1 al cambiar filtros, debounce400ms conservado. Marca/categoría reales probadas por controller; búsquedas sin coincidencias y estados vacíos reales comprobados.

Paginación: C1/C2 tres páginas20/20/7, C2 disabled dos20/13. IDs recolectados completos. Página999 devuelve colección vacía según backend, no se cambia ese contrato. Filtro disabled conservado al paginar en navegador controlado. C3 dos productos, una página.

## Status Semantics

Checkbox original company-stock-toggle/data-stock-item-id conservado; texto Habilitado/No habilitado y nombre accesible SKU. No representa StockItem.active ni Material.active. POST conserva company_id/stock_item_id/enabled, CSRF, bloqueo mientras guarda, rollback visual si error y recarga con filtro de estado.

Adaptación JS: render/summary/loading/error y paginación compacta; abort/versionado de GET evita aplicar respuestas anteriores al cambiar company/filtros. Se quitan filas anteriores durante carga/error para no dejar toggles de otro contexto visibles. Error no se convierte en empty; mensaje y alerta originales preservados. Sin modificación de lógica backend.

## Mutation Boundary

NO POST ni cambios de habilitación. Status Mutation QA PENDING. STORE CONTRACT — READY FOR ISOLATED MUTATION QA (revisión estática únicamente): validación IDs integer/enabled boolean; resolveAllowedCompany; StockItem findOrFail tenant-scoped; tenant coincide explícitamente, sino422; firstOrNew por company+stockItem y save. No certificado mediante mutación real.

## Store Web Boundary

StoreWebController, Public Catalog, slugs/publicación y assets/vistas Store Web UNCHANGED.

## Material Boundary

Material List, StockItems, Items, Availability e Inventory UNCHANGED.

## Branch Pricing Boundary

Precios/listas/presentaciones/moneda/fallback UNCHANGED. Company enabled independiente del precio efectivo.

## Responsive

Venti Next Advanced Operational/Configuration List: heading/toolbar, next-form-section y grid existente, summary, next-data-table, table-responsive y next-list-pagination. Cero CSS nuevo. Campos conservan IDs; etiquetas for y checkbox accesible. Corrección de overflow encontrada en QA: posicionar label con utilidad Bootstrap position-relative para contener sr-only dentro de tabla con scroll local; sin CSS nuevo.

QA headless Chromium con HTML Laravel y JSON reales guardados del controller READ ONLY. Assets locales8080; respuestas JSON reproducidas en navegador, requests de shell ajenas controladas y POST bloqueado. No equivale a HTTP autenticado end-to-end. Anchos320/375/390/576/768/992/1200/1440; dataset C1/C2/C3, página siguiente, filtro disabled paginado, empty y error403 controlado. Evidencias temporales company-contract.json, company-qa.json, company-375.png, company-1440.png en TEMP. Resultado final abajo.

## Empty / Error States

Filtro search sin coincidencias:0 en tres contextos. Estado disabled:0 en C1/C3. C2 enabled14/disabled33. No existe company autorizada totalmente sin SKUs activos en dataset actual: caso real pendiente dataset, no se fabricó. Rama genérica de cero resultados cubierta. Respuesta403 controlada muestra error y cero toggles residuales; no mensaje de «sin productos».

## Backend Sync Notes

Sin nuevo blocker de este mantenedor. Prefijo duplicado se documenta como deuda nominal, no se corrige. Mutaciones y membresía no Owner end-to-end pendientes. No se reabren superficies cerradas ni se afirma aislamiento por branch.

## Files

resources/views/companyStockItem/index.blade.php; public/js/companyStockItem/index.js; config/venti-next.php; este documento. Backend/rutas/modelos unchanged. Sin commit.

## QA Closure

PASS: 24 comprobaciones de viewport (3contextos ×8anchos), scrollWidth=viewport en todas; capturas desktop1440/mobile375 revisadas. Tabla con scroll local, formularios dentro del viewport. Cero errores JS y cero POST durante QA. Next CSS cargado por allowlist.

PASS: página2 C1/C2 (21–40), disabled C2 página2 (21–33), filtros conservados y búsqueda restablece página1; empty diferenciado de403 controlado; cero toggles obsoletos tras error. Company vacía total: PENDING DATASET; no se inventó fixture comercial. Toggle sólo renderizado con estados reales, no accionado.

PASS: node --check public/js/companyStockItem/index.js; PHP7.3.33 artisan view:cache; php -l config/venti-next.php; git diff --check. No cambios backend ni commit.

**COMPANY PRODUCT CATALOG — FRONTEND INTEGRATED**. Lectura validada con controller y render real; HTTP autenticado end-to-end y Status Mutation QA PENDING. Store Web/Material/StockItems/Branch Pricing UNCHANGED; CSS0.
