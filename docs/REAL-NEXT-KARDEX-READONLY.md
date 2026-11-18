# REAL NEXT — Kardex moderno — Read-only frontend integration

Fecha: 2026-11-18. Backend base `50a0d5c`, read path introducido en `4567897`.

**KARDEX MODERNO — FRONTEND INTEGRATED — READ PATH**

**IMPLEMENTED WITH BACKEND DEPENDENCY — AUTHORIZED HTTP QA PENDING (KARDEX-01).**

La integración visual y las consultas contextualizadas pasan las comprobaciones descritas. No equivale a acceso operativo habilitado: el permiso `list_kardex` no existe en el catálogo local. No se creó, asignó ni evitó mediante cambios de aplicación. HTTP real EDESCE/Gamarra responde 403. No se certifica acceso positivo ni se cierra INV-01 completo.

## Modern vs Legacy Contract

Se trabaja únicamente `InventoryMovementController::index/kardex`, `KardexMovementQuery`, `MaterialInventoryScope`, selectores de MaterialController y `inventory.kardex`/`js/inventory/kardex.js`. No se usa `kardexO` ni se añaden fuentes legacy.

Reutilización: **REUSE WITH CONTRACT ADAPTATION** de Advanced Operational List, heading/summary/table de `venti-next/resources/views/puntoVenta/list.blade.php` y sección de filtros de Warehouse Availability. `.next-operational-list`, `.next-form-section`, `.next-list-summary`, `.next-data-table`; Select2 permanece. Diez columnas, sin nuevas acciones, filtros ni paginación. CSS nuevo: **0**.

## Routes

Todas bajo web/auth/check.user.enabled/password.changed/tenant.context.

| Route (GET/HEAD) | Name | Action | Permission | Read/Mutation |
|---|---|---|---|---|
| `/dashboard/kardex` | `kardex.index` | InventoryMovementController@index | list_kardex | Read |
| `/dashboard/kardex/{materialId}` | `kardex.material` | InventoryMovementController@kardex | list_kardex | Read |
| `/dashboard/stock-items/select` | `stockitems.select` | MaterialController@selectStockItemsAjax | list_kardex | Read |
| `/dashboard/warehouses/select` | `warehouses.select` | MaterialController@selectWarehousesAjax | list_kardex | Read |

Deuda nominal: `{materialId}` recibe **StockItem ID**. No se renombró ni se envía Material ID.

Allowlist: sólo se añadió `kardex.index` (la configuración usa nombres de ruta, no nombres Blade); corresponde a `inventory.kardex`. **ALLOWLIST MATCH: YES**; HTML renderizado carga `venti.min.css` y `venti-next.css`. Ningún endpoint JSON añadido.

## Permission

`permission:list_kardex` protege las cuatro rutas. El catálogo local contiene **0** permisos con ese nombre; búsqueda en `database/` sin provisión de esa clave. No asumir defecto de todos los despliegues: evidencia local actual.

QA HTTP con login autorizado de Owner EDESCE y Owner Gamarra: vista, Kardex 8/105 y ambos selectores → **403**, ocho comprobaciones. Middleware Spatie con usuarios reales en proceso aislado también rechaza. No hay bypass Owner. Autorización positiva pendiente de provisión DEV; no modificar roles reales durante esta fase.

## Scope

MaterialParameters exige usuario habilitado no Platform Admin, tenant coincidente, company activa y vínculo company_user activo. MaterialInventoryScope exige branch activo de company y branch_user activo. Warehouses: tenant/company exactos, branch actual **o general (`branch_id IS NULL`)**. Se comprueba nuevamente el warehouse recibido; no basta el selector.

StockItem: tenant y habilitación activa en company_stock_items. El maestro puede estar habilitado en varias companies; eso no autoriza movimientos de otra company. Query ordinaria y uniones de transfers aplican tenant/company/almacenes autorizados. No se reconstruye scope en JS.

## StockItem Selector

Select2 conserva `q`, mínimo un carácter, búsqueda por sku/barcode/display_name/material code/full_name. Backend ordena por display_name y limita a 20; sin contrato de páginas. Sólo `is_active=1` y habilitación company activa. Las búsquedas dirigidas comprueban elementos fuera del primer bloque de 20.

| Contexto | q=ACE | q=TOM | q=CAM | q=MATERIAL PRUEBA COMPANY 2 |
|---|---|---|---|---|
| C1/B1 y C1/B8 | 8,75,76 | vacío | 95,98,101,96,99,102,97,100,103,104 | 107,109,108,110 |
| C2/B2 | vacío | vacío | 95,98,101,96,99,102,97,100,103,104 | 107,109,108,110 |
| Gamarra C3/B4 | vacío | 105 | vacío | vacío |

`zzzz-no-match`: vacío en los cuatro contextos. La descripción “COMPANY 2” no es una regla de autorización: 107–110 están habilitados también en C1. No se ocultaron desde JS.

## Warehouse Selector

Select2 conserva `q`, límite 20, nombre y sufijo General/Local actual del backend. C1/B1: IDs **1,8**; C1/B8: **1,9**; C2/B2: **2**; Gamarra C3/B4: **4**. Sin warehouse, se consulta el conjunto general/local permitido; no todos los almacenes del tenant.

## Dataset

Revalidado con SELECT + llamadas a controllers/services sobre PDO MySQL en `START TRANSACTION READ ONLY`, finalizado con ROLLBACK; Auth/context sólo en memoria. Esto comprueba servicios con datos reales, **no el acceso HTTP autorizado**. No se guardaron fixtures, permisos ni registros. Totales de referencia: 49 StockItems, 106 filas ordinarias; los resultados incluyen uniones de transferencias y se superponen en almacenes generales.

| Usuario/contexto en memoria | StockItems habilitados (IDs completos por intervalos inclusivos) | Warehouses | Movimientos resultantes |
|---|---|---|---:|
| Owner EDESCE 4, T1/C1/B1 | 8; 45–52; 71–104; 107–110 (47) | 1,8 | 108 |
| Owner EDESCE 4, T1/C1/B8 | mismos 47 | 1,9 | 95 |
| Owner EDESCE 4, T1/C2/B2 | 95–104; 107–110 (14) | 2 | 2 |
| Owner Gamarra 7, T3/C3/B4 | 105,106 (2) | 4 | 1 |

Se consultó cada StockItem habilitado, no sólo los 20 del lookup inicial. Comparación por identificadores de movimiento:

- W1 compartido por C1/B1 y C1/B8: 93 movimientos idénticos; 37 entry, 54 output, `TRANSFER_OUT_2`, `TRANSFER_OUT_3`.
- W8 exclusivo B1: `IN_106_60`, `IN_107_61`, `IN_108_62`, `IN_109_63`, `IN_110_64`; `OUT_125`–`OUT_130`; `TRANSFER_IN_2`, `TRANSFER_IN_3`, `TRANSFER_IN_4`, `TRANSFER_OUT_1` (15).
- W9 exclusivo B8: `OUT_124`, `TRANSFER_IN_1` (2).
- W2/C2: `IN_105_59` (StockItem 95, entry 52), `IN_104_58` (104, entry 49).
- W4/Gamarra: `IN_103_57` (105, entry 45).

No intersección de movimientos C1/C2/Gamarra. La intersección B1/B8 es exactamente W1 y está permitida. No sumar ambos totales como ventas/movimientos únicos de company.

## Movement Sources

`inventory_movements_view` aporta `entry`/`output`. KardexMovementQuery añade `transfer_dispatch` desde allocations dispatched y `transfer_receipt` desde receipt lines, con joins tenant/company y almacén origen/destino respectivamente. Conserva orden movement_date, movement_type, movement_id. No se ejecutó lifecycle de transferencias.

Tipos del response: **IN / OUT**, mostrados literalmente con badge neutral. No se inventan ajustes ni categorías. Fuente/referencia permanece texto; transfers conservan formato `TR-00000n · Recepción #n` cuando existe receipt_id. No enlaces a documentos.

## Filters

Sólo StockItem y warehouse; botón Buscar. Sin filtro de fechas/tipo ni búsqueda adicional del listado. Búsqueda textual pertenece a los selectores. Cambio de filtro invalida respuesta pendiente y limpia resultados previos; errores no se presentan como vacíos. Enter evita submit nativo de la página y dispara la misma consulta GET.

**Paginación: NOT APPLICABLE — no existe contrato**, devuelve rows completos. No corresponde PENDING DATASET ni inventar página 2. **EXPORT — OUT OF SCOPE**, no se agregó export.

## Columns

Conservadas: Fecha (`date`), Tipo (`type`), Origen (source_type/source_id/receipt_id), Cant. Entrada (`qty_in`), Cant. Salida (`qty_out`), Costo Unit. In (`unit_cost_in`), Costo Unit. Out (`unit_cost_out`), Saldo Cant. (`saldo_qty`), Costo Promedio (`saldo_cost`), Saldo Total (`saldo_total`). Producto/almacén del response en summary, no columnas nuevas.

Fecha/tipo/cantidades centrados, origen izquierda, importes derecha; encabezados alineados con celdas. Origen/valores insertados como texto. Null permanece vacío; cero se muestra explícitamente, sin inventar saldo cero para un null. El summary cuenta rows, no calcula cantidades.

## Quantity / Balance Semantics

IN/OUT no determina un signo nuevo en JS. Se muestran qty_in/qty_out y saldo_qty tal como llegan; backend conserva acumulación, signos y orden. Fecha es `date` del response, generado desde movement_date; el cliente no sustituye created_at. Ninguna fórmula de saldo en frontend.

## Cost Boundary

Importes usan `simbolo_moneda` y formato existente a dos decimales; backend redondea campos a cuatro decimales. No cambia cálculo, moneda, fuente ni reglas; ahora los ceros reales son visibles. **VALUATION HISTORICAL QA — OPEN**: no certifica costos legacy ni exactitud contable. La discrepancia de moneda en transfers continúa siendo 422 backend; UI conserva el error de validación, no muestra empty state.

## Cross-context QA

Pruebas directas de controller/service con Auth/context real en memoria, independientes de middleware de permiso; los códigos representan sus excepciones/respuestas, no una sesión HTTP con autorización concedida.

| Caso | Resultado |
|---|---|
| C1 StockItem 8 | 200, 9 movimientos W1 |
| C2 intenta StockItem 8 (no habilitado) | 404 |
| C1 consulta 107, compartido con C2 | 200 vacío, habilitación real válida; no es fuga |
| C1/C2 intentan StockItem Gamarra 105 | 404 |
| Gamarra intenta 8/107 | 404 |
| StockItem 999999 | 404 en cuatro contextos |
| C1 StockItem 8 + warehouse C2/2 o Gamarra/4 | 404 |
| C1/B1 + warehouse B8/9; C1/B8 + B1/8 | 404 |
| Warehouse 999999 | 404 en cuatro contextos |
| Warehouse 0 o bad | 422 en cuatro contextos |
| Usuario 4 con company/tenant ajenos o contexto vacío | 403, tres casos |
| Producto 8 + W8 en B1 / W9 en B8 | 200 vacío: ambos componentes autorizados, sin movimientos; no implica incoherencia de seguridad |

No hay StockItem exclusivo de C2 en este dataset: los 14 también están habilitados C1. Se valida el rechazo company en sentido inverso (8 desde C2). No se creó fixture para falsear cobertura.

Vacíos reales: 95 en C1 sin movimientos; 8 en almacenes locales 8/9 sin movimientos; 106 en Gamarra; texto de lookup sin coincidencia. Ninguna de las cuatro companies/locales consultados está totalmente vacía considerando todo su inventario: **company totalmente vacía — PENDING DATASET**. Sin producto seleccionado: estado inicial de UI.

## Responsive

QA en Edge headless: Blade real renderizado por Laravel, assets permanentes locales y replay de JSON capturado por consultas readonly. Es un arnés temporal fuera del repo; no habilita preview productivo ni cambia guards. No confundirlo con HTTP autorizado. Interacción Select2 (búsqueda/selección), warehouse y Buscar PASS; empty/error 403/404/422/500 y cancelación de respuesta tardía PASS. Sin excepciones JS de Kardex.

| Viewport | Document scrollWidth | Table scrollWidth / contenedor | Resultado |
|---:|---:|---|---|
| 320 | 320 | 1008 / 286 | PASS |
| 375 | 375 | 1008 / 341 | PASS |
| 390 | 390 | 1008 / 356 | PASS |
| 576 | 576 | 1008 / 526 | PASS |
| 768 | 768 | 1008 / 702 | PASS |
| 992 | 992 | 1008 / 852 | PASS |
| 1200 | 1200 | 1008 / 884 | PASS |
| 1440 | 1440 | 1124 / 1124 | PASS |

Capturas revisadas desktop/móvil; filtros apilados en móvil, una fila cuando hay espacio. Tabla con foco y scroll LOCAL. GLOBAL OVERFLOW: NONE. CSS nuevo 0. El arnés no sirvió Google Fonts/ionicons externos ni peticiones ajenas del shell `/api/sunat` y notificaciones; no se declara red global libre de errores. Integración HTTP/visual con permiso real: pendiente KARDEX-01.

## INV-01 Update

**INV-01 / MODERN KARDEX SCOPE — FIXED / DIRECT READ QA PASSED.**

**INV-01 / MODERN KARDEX AUTHORIZED HTTP READ PATH — QA PENDING (KARDEX-01).**

No marcar sin matiz `MODERN KARDEX READ PATH — FIXED / QA PASSED` mientras falta acceso autorizado. Mantener OPEN Inventory legacy, Items legacy, Fixed Assets legacy, warehouse structure legacy y valorización histórica. Backlog actualizado únicamente en ese alcance.

## Backend Sync Notes

[OPEN] **KARDEX-01 — provisión local de list_kardex**. Archivo/endpoints: las cuatro rutas protegidas y catálogo permissions. Impacto: usuarios autorizados de QA reciben 403; falta permiso y su asignación explícita. DEV debe entregar provisión/asignación idempotente y validar rol permitido/denegado. Frontend puede continuar: **Sí**, fachada terminada; no relajar guards ni otorgar permisos desde esta fase.

[OPEN] **INV-01 / histórico**. No se audita nuevamente inventario legacy ni costos; el nuevo scope no certifica valorización. Frontend puede continuar: **Sí**, mostrando contrato actual.

Archivos: `config/venti-next.php`, `resources/views/inventory/kardex.blade.php`, `public/js/inventory/kardex.js`, este documento y backlog DEV. Backend comercial/routes/services/models: **UNCHANGED**. Sin escrituras de negocio, stock, costos, movimientos ni configuración. Sin commit. Validación: JS syntax PASS, Laravel PHP 7.3.33 view:cache PASS, git diff --check PASS.
