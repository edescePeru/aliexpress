# REAL NEXT — R8.2A — Confirmed Functional Parity Recovery

2026-11-25. Rama `feature/venti-next-frontend`; baseline `6df57e7`. Recuperación autorizada de FR-108/109; FR-110 formalizada pero bloqueada. No R8.2B.

## Resultado y contabilidad

| ID | Resultado | Evidencia |
|---|---|---|
| FR-108 / R8-B02 | RESTORED — FRONTEND PARITY | Solicitar traslado reaparece sólo cuando warehouse.request_url existe; navegación interceptada exacta |
| FR-109 / R8-E02 | RESTORED — FRONTEND PARITY | Índice del loop corregido; prueba con JavaScript real y templates Blade de ambos consumidores |
| FR-110 / R8-F01 | BLOCKED / EXPENSE-01 | Retorno de Crear gasto no recuperado; no cambios al formulario ni destinos alternativos |

IDs 108–110 formalizados por autorización del propietario. Inicio: 74 recuperadas +2 pendientes frontend +11 bloqueadas +23 excluidas =110. Final: **76 recuperadas +11 bloqueadas +23 excluidas =110**. No nuevas pérdidas/gates/findings DEV. S201/S203 siguen fuera del conteo, con dependencia contractual monetaria.

Auditoría A194/194 preservada. Estado posterior de paridad: **187 confirmadas /5 con pérdidas /2 dependencias monetarias**. FR-109 comprende dos superficies, no dos capacidades. B2, C125 y observaciones Branch/GRE intactos. No cierre global ni certificación de persistencia.

## FR-108 — contrato y cambio

El commit de homologación `a90d322a` retiró el trigger histórico. `WarehouseAvailabilityService::snapshot` conserva request_url hacia `warehouseTransfer.create`. Su disponibilidad depende de existencias positivas, trazabilidad, permisos `list_transfer` + `create_transfer` y destino distinto dentro del local actual. La lectura de inventario conserva scope tenant/company.

El renderer utiliza literalmente esa URL mediante jQuery `.attr('href', warehouse.request_url)`. No concatena rutas, no recalcula permisos, no usa transferencias legacy ni fallback. Propaga así source_warehouse_id, stock_item_id, form_tenant_id/company_id/branch_id sin reinterpretación. Sin URL no hay trigger ni enlace. Se añadió columna Acciones y colspan8 al vacío.

Patrón reutilizado: dropdown `next-row-actions`, trigger compacto accesible por almacén, icono `fas fa-exchange-alt next-row-action-item-icon`, etiqueta Solicitar traslado. Se usa el portal compartido de `public/js/layout/admin2.js`, Bootstrap y `data-boundary="viewport"` para el menú dentro de tabla con scroll local. Sin CSS nuevo.

Destino: GET `dashboard/traslados/almacenes/crear`, ruta `warehouseTransfer.create`, `WarehouseTransferController::create`; middleware de rutas `list_transfer` y `create_transfer`. Controller valida snapshots contra TenantContext, origen mediante WarehouseTransferAccess y producto mediante availability; renderiza prefill sin guardar solicitud. Registro verificado con `artisan route:list --path=traslados/almacenes --compact`, PHP7.3.33. **No se ejecutó GET autenticado real del destino**: alcanzabilidad de frontend comprobada por click interceptado; autorización backend preservada por inspección del contrato actual, no recertificada por esta prueba.

## FR-109 — contrato y cambio

`bd413c65` sustituyó dentro del loop `renderItemPage(i, currentPage)` por `renderItemPage(totalPages, currentPage)`. Se corrige sólo ese argumento. El atajo final continúa usando totalPages correctamente.

Consumidores reales: `ganancia.index` y `ganancia.indexDetail`, ambos incluyen `js/ganancias/index.js`. Se mantienen renderers, currentPage/totalPages, estado activo, elipsis disabled, Anterior/Siguiente, handler data-item y AJAX. Esta implementación no tiene filtros de fecha/búsqueda en getDataGanancias: el contexto de detalle activo es `ganancia_id`; se comprobó su conservación en la solicitud de página5. No se inventaron filtros ni se alteraron los seis scripts previamente descartados como afectados.

## QA reproducible — datos SIMULADOS

Prueba versionada: `tests/Frontend/r82a-recovery.cjs`. Requiere Playwright existente y Chrome/Chromium; no instala dependencias. Variables opcionales `PLAYWRIGHT_MODULE`, `CHROME_PATH`, `QA_OUTPUT` permiten usar el runtime aislado disponible y guardar evidencia fuera del repo.

Ejecución local con módulo existente `C:/Users/Remso/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright` y Chrome `C:/Program Files/Google/Chrome/Application/chrome.exe`, **headless y perfil aislado**, sin escritorio/browser del propietario.

| Comprobación | Resultado |
|---|---|
| FR108 URL presente / null / propiedad ausente | PASS; exactamente un trigger, dos filas sin acción |
| FR108 URL y parámetros completos / navegación | PASS; GET interceptado exactamente igual al fixture, no destino comercial abierto |
| Tabla vacía | PASS; colspan8 |
| Icono, menú, Escape, responsive375/1440 | PASS; imágenes inspeccionadas, cero overflow global, scroll local de tabla |
| FR109 página1/10 | PASS: 1,2,3,10; activa1, sin Anterior, Siguiente2 |
| FR109 página4/10 | PASS: 1,2,3,4,5,6,10; activa4, Anterior3/Siguiente5 |
| FR109 página10/10 | PASS: 1,8,9,10; activa10, Anterior9, sin Siguiente |
| FR109 página única | PASS; sin enlaces superfluos, comportamiento existente |
| Ambos consumidores | PASS: 8 casos +2 clicks numéricos a página5; ganancia_id88 intacto en detalle |
| Errores de navegador | 0 |
| node --check | PASS en ambos JS y test |
| git diff --check | PASS |

Todos los requests del navegador se interceptan: assets locales reales, fixture HTML aislado, respuestas sintéticas. No test de seguridad de sesión ni de permisos efectivos con DB real. No HTTP comercial, export, proveedor, traslados o gastos ejecutados. No nueva matriz responsive global.

Evidencia externa: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r82a-evidence.json`, `r82a-availability-375.png`, `r82a-availability-1440.png`. Capturas son del renderer real en fixture SIMULATED, no de una sesión comercial.

## Archivos y límites

- Runtime: `public/js/warehouseAvailability/index.js`, `public/js/ganancias/index.js`.
- Regresión: `tests/Frontend/r82a-recovery.cjs`.
- Documentación: este informe, los tres documentos R8, backlog funcional y backlog DEV. Ledger externo actualizado conservando snapshot previo.
- Backend0 / Blade0 / CSS0 / infraestructura0 / mutaciones comerciales0. No compilación Blade necesaria: no cambió Blade.
- FR110 depende del finding existente EXPENSE-01, no se crea otro. S201/S203, B, C, POS, Store Web y otros módulos sin intervención.
- R7.7E y cambios documentales anteriores preservados. Sin commit/push/fetch/pull/merge.

## Revisión manual

- Disponibilidad: http://127.0.0.1:8080/dashboard/disponibilidad/almacenes
- Ganancia Diaria: http://127.0.0.1:8080/dashboard/listado/ganancias/diarias
- El detalle usa su navegación existente y comparte el JS; no se inventa un ID real.

**R8.2A COMPLETE — FRONTEND RECOVERY.** QA mutador y certificación backend no incluidos. Esperar revisión del propietario; R8.2B no iniciada.
