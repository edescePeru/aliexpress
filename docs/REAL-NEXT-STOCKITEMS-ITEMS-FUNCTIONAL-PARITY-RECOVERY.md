# REAL NEXT — R6 — STOCKITEMS & ITEMS FUNCTIONAL PARITY RECOVERY

Fecha: 2026-11-25. Rama `feature/venti-next-frontend`; HEAD `3914020`, working tree R0–R5 preservado. Dos superficies exclusivamente. Sin commit, backend, CSS ni cambios de shell. No avanzar a R7 sin revisión del propietario.

## Resultado

**FUNCTIONAL PARITY: PARTIAL — 4 RESTORED / 1 BACKEND BLOCKER**.

Cinco pérdidas activas confirmadas; cuatro gates de exclusión read-only obsoletos retirados. Ninguna pérdida nueva alcanzable encontrada. No se considera recuperación exitosa la exclusión del ajuste de stock. No hay retiro legacy aprobado en estas dos superficies. Mutaciones reales de inventario: **QA PENDING**; recuperación visual y cableado interceptado separados de la certificación mutadora.

## Evidencia Git y cobertura anterior

- `6de4406^`: handlers de flags, formulario min/max, ajuste y editor de código presentes. `6de4406` (integración read-only) elimina controles, modales/inputs y handlers de los dos JS; no es pérdida por recursos, sanitización ni permisos DEV.
- HEAD conserva esa exclusión frontend. `StockItemScreenAccess`, `MaterialInventoryScope`, guards y contratos presentes en entregas `5c6907a`, `ec5f6dd`, `ede0552` se contrastaron con HEAD, no con una promesa de cierre general INV-01.
- El documento `REAL-NEXT-STOCKITEMS-ITEMS-READONLY.md` documentó lectura/contexto/responsive, excluyó explícitamente cinco mutaciones y no ejecutó `sync`. La auditoría global y backlog registraron FR-059…062/065, pero dejaron la semántica operacional pendiente. R6 cierra esa revisión focalizada; no afirma que previamente hubiera QA mutadora.
- `btn-resumen-stock` y enlace Nuevo material estaban dentro de comentarios Blade antes de `6de4406`: **HISTORICAL CODE NOT REACHABLE**. No se reactivan.
- `[data-column]` existía como handler huérfano en JS Items; no había switches de columnas en el Blade. No se inventa Column Visibility.
- FR-063/064 son exports de `dashboard.dashboard`, pérdida `dbca24e1`: fuera de estas dos superficies, no se cierran ni se retiran. Sin export activo alcanzable en estos Blades históricos.

## Matriz funcional antes / después

| ID | Pantalla | Capacidad original | Vigencia | Permiso | Scope | Endpoint | Motivo de exclusión | Blocker HEAD | Recuperación | QA |
|---|---|---|---|---|---|---|---|---|---|---|
| FR-059 | StockItems | Ajustar stock | ACTIVE | UI histórica ajustarStock_material; endpoint sin permission específica | TenantScope parcial; falta company/local/lote contextual | POST /dashboard/fix/stock-item/output-adjustment | Read-only | INV-01 confirmado abajo | BACKEND BLOCKER | Estático; cero invocaciones |
| FR-060 | StockItems | Mínimo/máximo | ACTIVE | update_material; entrada modal verInventario_material como antes | T/C/local + general, nivel existente del StockItem | POST /dashboard/stock-items/{stockItem}/inventory-levels | FRONTEND GATE OBSOLETE | Ninguno para reconectar contrato vigente | RESTORED | SQLite + POST interceptado |
| FR-061 | StockItems | Inventariable | ACTIVE | configInventariable_material | StockItem tenant-master habilitado en company; contexto local autorizado | POST /dashboard/stock-items/{id}/toggle-inventory | FRONTEND GATE OBSOLETE | Política global MAT-01 no se cierra | RESTORED | SQLite + POST interceptado |
| FR-062 | StockItems | Activo | ACTIVE | configActive_material | StockItem y variante del mismo material/tenant; habilitación company exigida | POST /dashboard/stock-items/{id}/toggle-active | FRONTEND GATE OBSOLETE | Política global MAT-01 no se cierra | RESTORED | SQLite + POST interceptado |
| FR-065 | Items | Editar código físico | ACTIVE | editarCodeItems_material | Item T/C/warehouse permitido/location coherente/estado visible | PUT /dashboard/stock/material/item/{item}/code | FRONTEND GATE OBSOLETE | Ninguno para reconectar contrato vigente | RESTORED | SQLite + PUT interceptado |
| R6-A | StockItems | Listar/buscar/paginar | ACTIVE | listStockitem_material | Catálogo company + cantidades local/general | GET /dashboard/stock-items/list | No excluida | — | ACTIVE ALREADY AVAILABLE | Lectura real, repetición idéntica, búsqueda vacía |
| R6-B | StockItems | Ver inventario | ACTIVE | verInventario_material en UI; endpoint list_material OR verInventario_material OR update_material | T/C/local + general | GET /dashboard/stock-items/{id}/inventory-levels | No excluida | — | ACTIVE ALREADY AVAILABLE | Niveles reales / vacío |
| R6-C | StockItems | Ver Items | ACTIVE | verItems_material + tipo_venta_id=3 | ScreenAccess | GET /dashboard/view/stock/material/items/{id} | No excluida | — | ACTIVE ALREADY AVAILABLE | StockItem81, Item1 |
| R6-D | Items | Detalle inline, búsqueda, paginación, volver | ACTIVE | verItems_material | ScreenAccess | GET /dashboard/view/stock/material/all/items/{id} | No excluida | — | ACTIVE ALREADY AVAILABLE | DTO real, DataTables preservado |

## Contratos y permisos

Rutas de página `stockitems.index` y `material.stock.getItems`; datos `stockitems.list`, `material.stock.getItemsMaterial`; código `items.update.code`. Conservan grupo dashboard/auth y middleware permission; guards `StockItemScreenGuard` y `MaterialInventoryGuard` según ruta. No Owner bypass nuevo.

`inventory-context.js` intacto añade snapshot `X-Material-Inventory-Tenant/Company/Branch` a GET/POST/PUT de estos contratos. CSRF intacto. Company/branch se resuelven en sesión backend; no se eligen desde UI. Contexto inválido 403; snapshot distinto 422; registro ajeno/no existente 404.

StockItems usa permisos efectivos entregados por su controller. Para código Item se usa `@can` y `auth()->user()->can` en Blade: el array legacy del controller usa roles solamente, y no se lo usa para ocultar permisos directos legítimos. Backend permanece sin modificar; reutiliza autoridad efectiva existente, sin crear otra política. AUTH-01 no se declara cerrado globalmente.

### Mínimos / máximos

`inventory_levels` array 1–500; IDs positivos distintos; campos nullable/numeric/min0. `MaterialInventoryScope::sync` acepta sólo niveles existentes del producto, company y almacenes del local/general con location coherente, locks ordenados por ID y transacción del caller. Vacío/null normaliza a 0; mínimo no puede superar máximo. Omitidos preservan valor. Formulario usa `step=any`, conserva precisión recibida, no redondea ni inventa restricciones fiscales/stock. Envía sólo id/min_alert/max_alert y CSRF. Costos, cantidades, reservas, almacén y ubicación son lectura. Sin niveles no puede guardar. Error conserva modal y valores; éxito recarga la página actual del listado.

### Flags: no confundir entidades

Inventariable modifica `StockItem.tracks_inventory`. Activo modifica `StockItem.is_active` y, si existe, `Variant.is_active` después de comprobar material/tenant; ambos en una transacción. **No modifica `CompanyStockItem.is_active`, Material ni un estado por sucursal.** Company habilitada es requisito de acceso; el valor escrito es compartido del tenant-master. UI mantiene esa semántica, no lo llama habilitación de empresa ni reserva/stock.

`required|boolean` acepta 0/1, no strings arbitrarios; snapshot se verifica antes y dentro de transacción. El frontend conserva el POST `value`, no manda variant_id para decidir ownership (backend lo resuelve). No impone prohibición por stock o reservas que el endpoint no contiene. MAT-01 sigue siendo política histórica pendiente; MAT-03/04 son otros caminos de Material Save; MAT-05 coerción legacy no es este boolean validator. R6 no certifica ni modifica esos caminos.

### Código de Item

Código/serie físico, distinto de SKU/barcode. Trim backend; required string max255; unicidad comprobada en el tenant excluyendo Item actual. Objetivo releído con lock y `ScreenAccess::item`; estados entered/scraped/reserved. No se altera estado, precio, cantidad ni identidad del padre. Historial sigue referenciando Item ID; no se inventa snapshot histórico. El POST antiguo no se replica: método actual **PUT**, ruta `items.update.code`. Éxito recarga DataTables conservando página; errores visibles, sin fake empty state. Verificación concurrente de unicidad MySQL no certificada por SQLite.

## Ajuste de stock — INV-01 sigue OPEN

`routes/web.php:3771`, `InventoryMigrationController::adjustStockItemStockOut` (~891) y `adjustStockItemStockOutLogic` (~917): sin permission `ajustarStock_material` en ruta/controller y sin ScreenAccess/MaterialInventoryScope/snapshot. Validación `exists:stock_items,id` global; selección de lotes por stock_item_id, sin company/warehouse/local explícitos. TenantScope no sustituye esas defensas (y sin tenant no filtra).

Semántica es **salida** positiva >=0.01, no ajuste arbitrario positivo/negativo. Calcula disponibilidad restando reservado, ordena lotes por vencimiento, locks en StockItem/lotes/Items, transacción, Output/OutputDetail, decrementa lotes y sincroniza InventoryLevel; Items pasan a exited. Itemeable exige entero y consulta entered/**scrapped**, diferente de **scraped** del read path: DEV debe verificar dominio de estados junto al ajuste, sin convertirlo en un nuevo finding duplicado. No hay clave de idempotencia observada; reintento puede representar otra salida. No se demuestra asiento mediante InventoryMovement en este método; no se certifica coherencia Kardex. R6 no ejecutó el endpoint.

DEV debe aplicar capacidad explícita a ruta y controller, resolver StockItem habilitado y lotes/Items/warehouse/location de contexto autorizado, mantener invariantes stock/reserva y probar rollback/retries/doble envío/movimientos. No basta añadir un permiso al botón. Es evidencia ampliada de **INV-01 / FR-059**, no nuevo finding ni cierre de Inventory.

## Dataset real, 2026-11-25

Read harness Laravel + PDO `START TRANSACTION READ ONLY`, cache/session array, ROLLBACK. Usuarios existentes Owner EDESCE/Owner Gamarra; sin contraseñas, fixtures DB ni escrituras. Captura de controller/servicios/render no se presenta como sesión HTTP real. Respuestas repetidas idénticas en los cuatro contextos.

| Contexto T/C/B | StockItems IDs | Items visibles | URL Items real |
|---|---|---:|---|
| EDESCE 1/1/1 | 8,45–52,71–104,107–110 (47) | 60; StockItem81 tiene12 | http://127.0.0.1:8080/dashboard/view/stock/material/items/81 |
| EDESCE 1/1/8 | mismos47 | mismos60, almacén general1 | http://127.0.0.1:8080/dashboard/view/stock/material/items/81 |
| EDESCE 1/2/2 | 95–104,107–110 (14) | 0 | http://127.0.0.1:8080/dashboard/view/stock/material/items/95 |
| Gamarra 3/3/4 | 105,106 | 0 | http://127.0.0.1:8080/dashboard/view/stock/material/items/105 |

StockItem81: CEL-APP-IPH-17; Item1 código existente; InventoryLevel74, warehouse1/location1, general, stock12/reservado2/disponible10, min5/max100. QA modal poblado también StockItem104/InventoryLevel97. StockItems107–110 de C1 no tienen niveles; guardar correctamente indisponible. Los14 StockItems compartidos C1/C2 corresponden a habilitación company, no a supuesta fuga. Sin IDs compartidos EDESCE/Gamarra. No existe dataset físico de sucursal exclusiva ni scraped para certificar esos casos en DB real; scope probado también en SQLite.

Lectura negativa: StockItem8 desde C2 y106 desde EDESCE fallan cerrado; Item1 desde C2/Gamarra falla; inexistentes fallan. No-permission: cinco middleware de permiso rechazan403; stale snapshot rechaza422; branch inexistente403; búsqueda sin coincidencias retorna vacío real. IDs completos, niveles, permisos y resultados están en `r6-data.json`.

## QA y evidencia visual

- `StockItemRecoveryContractTest`: **7 tests / 46 assertions PASS**, SQLite `:memory:` configurado explícitamente. No usa migraciones/fixtures de la base compartida. Cubre ownership company/tenant/local, ausentes, vacíos/0/decimales minmax, min>max, negativos/IDs duplicados, lote mezclado y rollback tras primera fila, flags/variante sin alterar habilitación company/cantidades, boolean inválido, snapshot vencido, código trim/duplicado/vacío/largo/ajeno y nivel de transacción0.
- Chrome headless aislado, assets permanentes y HTML real renderizado; GET DTOs reales capturados, **todas las requests interceptadas**. POST/PUT devuelven respuestas **SIMULADO**200/403/422/500. Se verifican método, ruta, CSRF, snapshot, valores y reload sólo en éxito. No stock/flags/minmax/código/reservas/Kardex reales modificados. No browser/escritorio del usuario.
- Resultado final del harness: **22 checks**, **16 POST/PUT interceptados**, **0 pageerrors**, **0 escrituras reales**. Incluye16 combinaciones pantalla/ancho y6 cargas adicionales de contexto; cada combinación responsive comprueba listado/dropdown y modal, sin contar dos veces como otra pantalla.
- Dos pantallas, ocho anchos 320/375/390/576/768/992/1200/1440, dropdown y modal; tabla con scroll local, cero overflow global. Permisos sin controles, vacío, dataset C2/Gamarra y ambos locales; iconos `fas` + `next-row-action-item-icon`, trigger único. DataTables, paginación y búsqueda conservados; sin Column Visibility inventado.
- Antes/después: `r6-before-stock.png`, `r6-before-items.png` comparan JS HEAD contra recuperación; no son capturas históricas del commit. Después: `r6-stock-375.png`, `r6-stock-1440.png`, `r6-items-375.png`, `r6-items-1440.png` y equivalentes `-modal-375/-modal-1440`.
- Artefactos: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`; scripts `r6-read.php`, `r6-browser.cjs`, `r6-browser-results.json`, `r6-data.json`. Son evidencias locales, no datos productivos ni credenciales versionadas.
- JS syntax, Blade compile, git diff --check: PASS. Mutación real y concurrencia/locks MySQL: **PENDING**. No extrapolar respuestas simuladas a éxito HTTP real del backend.

## Archivos modificados por R6

- `resources/views/stockItem/list.blade.php`
- `resources/views/material/stockItems_items.blade.php`
- `public/js/stockItem/list.js`
- `public/js/material/stockItems_items.js`
- `tests/Feature/StockItemRecoveryContractTest.php`
- Este documento y addenda de los tres backlogs canónicos.

`inventory-context.js`, allowlist (ya incluía ambas páginas), backend, CSS y módulos ajenos: sin cambios. Ninguna capacidad R0–R5 retirada.

## Revisión manual sin guardar

| Capacidad recuperada | URL exacta | Registro/contexto |
|---|---|---|
| Mínimo/máximo | http://127.0.0.1:8080/dashboard/stock-items | EDESCE C1/B1, buscar CEL-APP-IPH-17, StockItem81/Level74; Ver inventario |
| Inventariable | http://127.0.0.1:8080/dashboard/stock-items | EDESCE C1/B1, StockItem81; menú de fila |
| Activo | http://127.0.0.1:8080/dashboard/stock-items | EDESCE C1/B1, StockItem81; menú de fila |
| Código Item | http://127.0.0.1:8080/dashboard/view/stock/material/items/81 | EDESCE C1/B1, Item1; menú junto al código |

Los toggles ejecutan el contrato al seleccionarlos, como antes. La revisión sin escritura consiste en abrir el menú; no pulsar esas opciones ni Guardar. No se han añadido confirmaciones o reglas de producto nuevas.
