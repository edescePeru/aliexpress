# REAL NEXT — Material Variants — Read-only

2026-11-22. **FRONTEND INTEGRATED — READ PATH**. Backend unchanged. CSS nuevo0. Sin commit ni mutaciones.

## Route / Permission

- GET /dashboard/view/material/variants/{id}: stockitems.view.variants → StockItemController::viewMaterialVariants → material.variants.
- GET /dashboard/view/material/all/variants/{id}: stockitems.getItemsMaterial → StockItemController::getItemsMaterial → DataTables JSON.
- Ambas actions llaman VariantScreenRead, que exige verVariantes_material mediante StockItemScreenAccess::permission. Protección backend, no sólo menú; el middleware de grupo autenticado se conserva. No route model binding implícito: servicio valida ID entero positivo, material tenant y habilitación.
- Allowlist indexada por ROUTE NAME: añadido exclusivamente stockitems.view.variants. No material.variants (nombre de Blade no reconocido por este mecanismo), JSON ni rutas mutadoras.

## Scope / VariantScreenRead

TENANT + COMPANY: material tenant, no eliminado; debe existir StockItem con company_stock_items activo para company/tenant. Variantes limitadas a material/tenant y a IDs de variante de esos StockItems. Talla/color con tenant explícito y selección acotada. No consultas frontend a catálogos globales.

Precondición existente: StockItemScreenAccess llama MaterialInventoryScope::context; requiere branch activo de company y branch_user activo. **Branch es condición de acceso, no filtro de ownership de variantes**. No se añadió branch ni se cambió contexto.

## Dataset

Revalidación MySQL con START TRANSACTION READ ONLY, ROLLBACK. Controller y servicio reales, Auth/contexto explícito en harness, no login HTTP completo. No fixtures de DB.

| Contexto | Materiales habilitados (ID: variantes) |
|---|---|
| EDESCE T1/C1/B1, usuario4 | 1:0,2:0,3:0,4:0,6:0,7:0,12:0,13:8,14:4,15:0,16:12,17:0,18:10,21:4 |
| EDESCE T1/C2/B2, usuario4 | 18:10,21:4 |
| Gamarra T3/C3/B4, usuario7 | 19:0,20:0 |

Material16: tallas/capacidades256GB,512GB,1TB,2TB; colores COS/DPB/SLV,12 combinaciones. Material18: L/M/XL/XXXXL y AZU/BLA/NEG,10 combinaciones según respuesta real (no producto cartesiano inventado). Material13:41/42/43/L y BLA/NEG,8. Gamarra19 autorizado sin variantes, empty real.

## Columns / Talla / Color

Seis columnas existentes: material.full_name, talla.short_name, color.short_name, attribute_summary, imagen, is_active. Encabezados/alineación Venti Next, texto escapado, badges Activo/Inactivo. Labels backend preservados, no IDs raw. No SKU/unidad/costo/stock inexistentes. Imagen ausente muestra Sin imagen; nombre escapado al construir URL. Modal existente de imagen conserva handler, alt e img-fluid.

## StockItem boundary / Inventory boundary

StockItem únicamente participa en autorización backend. La respuesta no entrega StockItem ID para mostrar/enlazar. No InventoryLevel, Kardex, lotes, ubicación, inventario o costos. Material Edit/Save y MAT-01/03/04/05 no tocados ni reabiertos: STILL BLOCKED / SEPARATE.

## Actions / Filters / Empty

Sólo Regresar a Material V2 y Ver imagen cuando existe. No edición/eliminación/stock, ni menú vacío o switches nuevos. DataTables client-side, búsqueda, orden y paginación originales; toolbar compartida. C1 material16 permite segunda página real12 registros; C2 diez; Gamarra vacío. Sin filtros extra.

Empty visible fuera del scroll de tabla: material sin variantes habilitadas. Búsqueda sin coincidencias conserva mensaje propio. Error HTTP403/404/500 no convertido en vacío: feedback con código. UI403 probado con respuesta interceptada; binding negativo en servicio real separado.

## Cross-context

IDs completos comparados desde stock_items existentes, sin consultar contenido ajeno en frontend:
- T1/C1:16 autorizado12;19/20 (T3)404.
- T1/C2:18 autorizado10;16 (mismo tenant sin habilitación C2)404. También1,2,3,4,6,7,12,13,14,15,17 denegados404;19/20 cross-tenant404.
- T3/C3:19/20 autorizados vacíos;1,2,3,4,6,7,12,13,14,15,16,17,18,21 denegados404.
- ID99999999:404 en los tres.
- Usuario4 con relaciones roles/permisos vaciadas SÓLO en memoria, sin escribir DB: VariantScreenRead::rows(16)403. Misma autoridad usada por página/datos; no se certifica denegación por sesión HTTP completa.

## Responsive / Visual parity

Chrome headless aislado con HTML Laravel actual, datos reales leídos reproducidos en GET interceptado y assets locales. No escritorio/browser del usuario. Tres contextos ×320/375/390/576/768/992/1200/1440:24 mediciones, scrollWidth=viewport. Tabla con scroll local, búsqueda sin coincidencias PASS, paginación real12→página2 PASS, error403 controlado diferenciado. Pageerrors0, mutaciones0; venti-next.css cargado y allowlist efectiva. Capturas320/1440 revisadas.

VISUAL PARITY con Material V2 y listados readonly: heading, toolbar, tabla compacta, badges y modal compartidos. Diferencia contractual válida: no acciones operativas ni selector de columnas. Cero CSS nuevo; reglas inline legacy sin consumidores retiradas. No nuevo test espejo para cambio de presentación. Checks: PHP7.3 artisan view:cache PASS; node --check PASS; git diff --check PASS. Evidencias locales %TEMP%/variants-read-results.json, variants-qa.json, variants-320.png, variants-1440.png.

## Files / Status

- resources/views/material/variants.blade.php
- public/js/material/variants.js
- config/venti-next.php: una ruta de vista
- docs/REAL-NEXT-MATERIAL-VARIANTS.md

FRONTEND INTEGRATED — READ PATH. Permission verVariantes_material; scope TENANT + COMPANY con precondición branch de sesión ya existente. Backend UNCHANGED. Sin operaciones de inventario ni escrituras. Cambios previos de Credits/Profile preservados; sin commit.
