# REAL NEXT — Daily Profit — Read-only Integration

Fecha: 2026-11-12. Backend, datos, cálculos y consumidores sin cambios. Sin commit.

## Post-Migration Audit

Se revisaron GananciaDiariaController, DailyProfitReport, UserProfitReport (dependencia del cálculo), GananciaDiaria, GananciaDiariaDetail, CalculateResumenDiarioGanancia, migración, rutas, exports y vistas/JS. Hallazgo: el controller pedía `ganancia.indexDetail`, pero ese Blade no existía. Se creó exclusivamente su presentación. La ruta de detalle real es `ganancia.detail.index`, NO `ganancia.indexDetail` (este último es el nombre de vista).

## Schema

`migrate:status`: Yes, migración 2026_11_09_000001_add_context_to_daily_profit_summaries, batch 166. Lectura SQL confirma tenant_id, company_id, branch_id, currency y unique `daily_profit_context_date_unique` sobre esos cuatro campos + date_resumen. Campos contextuales nullable para conservar históricos. SCHEMA CONTEXT — PRESENT / QA PASSED.

## Dataset

TOTAL 1 / CONTEXTUAL 0 / LEGACY-UNSCOPED 1 / DETAILS 1.
Única cabecera ID 1, fecha 2026-01-20; tenant/company/branch/currency NULL. No companies, branches ni currencies contextualizadas disponibles. No se infiere PEN para el registro legacy.
REAL DATA QA — PENDING RECALCULATION. No se ejecutó generación ni se fabricaron summaries.

## Routes

Todas bajo /dashboard; GET y permiso show_gananciaDiaria. Grupo autenticado, check.user.enabled, password.changed, tenant.context.

| Ruta | Nombre/acción | Tenant | Company | Branch | Currency | Permiso | Operación |
|---|---|---|---|---|---|---|---|
| /listado/ganancias/diarias | ganancia.index / index | contexto | contexto | contexto | PEN en datos | show_gananciaDiaria | Lectura |
| /get/data/ganancias/V2/{numberPage} | getDataGanancias | contexto | contexto | contexto | PEN | show_gananciaDiaria | Lectura |
| /listado/ganancia/detalles/{id} | ganancia.detail.index / indexDetail | contexto | contexto | contexto | PEN | show_gananciaDiaria | Lectura |
| /get/data/ganancia/details/V2/{numberPage} | getDataGananciaDetails | contexto | contexto | contexto | PEN | show_gananciaDiaria | Lectura |

ALLOWLIST MATCH: YES para las dos rutas de vista reales; AJAX no agregado. Navegador confirma venti.min.css y venti-next.css.

## Permissions

show_gananciaDiaria presente para ambas cuentas Owner probadas. Sin acciones de escritura. No se añade selector contextual ni autoridad por query. El único ID de detalle es ganancia_id; backend valida entero positivo y cabecera contextual antes de cargar filas.

## DailyProfitReport

summaries() filtra explícitamente T/C/B desde TenantContext y currency PEN. Los métodos de generación contexts()/recalculate() se leyeron, nunca se invocaron. Modelo de cabecera sin TenantScope automático: el aislamiento depende del servicio y del middleware contextual; no reutilizar consultas globales.

## Tenant / Company / Branch / Currency Scope

Vista y AJAX de detalle hacen findOrFail sobre summaries(). Los detalles proceden de la relación de esa cabecera, no de una búsqueda global por ID. Sin conversión de moneda. Moneda de listado indicada PEN conforme a filtro backend, detalle usa currency de la cabecera.

## Calculation Contract

Generación toma ventas del intervalo date_sale [fecha, fecha + 1 día), T/C/B y PEN; excluye state_annulled y internal_reversal_status=reversed. Cantidad desde detail.quantity; vendido total-discount; costo total_cost, con fallback unit_cost*quantity cuando cantidad positiva; utilidad vendido-costo. Costo ausente produce utilidad NULL y se muestra Sin costo registrado, nunca cero inventado. Agrupa por stock_item_id o material_id y persiste cantidades, importe y utilidad; redondeo backend a dos decimales. El comando calculate:ganancia valida fecha y por defecto usa ayer America/Lima. No ejecutado.

User Profit consulta actores created_by; Daily Profit consulta resúmenes persistidos por fecha/contexto. Se comparte lenguaje visual, no contrato. JS no suma ni recalcula costos, márgenes o totales.

## Filters

No existen filtros de fecha/rango/mes/año en el listado daily actual: no se agregaron. Conserva página de 10 registros y orden date_resumen DESC, id DESC. Detail AJAX envía solo ganancia_id y página por ruta. Totales/paginación son backend.

## List Mapping

next-page-heading, next-page-toolbar, next-operational-list, next-list-toolbar, next-list-summary, next-data-table y next-table-empty. N°/fecha/acciones centrados; cantidades e importes derecha. Columnas id, date_resumen, quantity_sale, total_sale, total_utility; no KPI inventado. Enlace Detalles conserva destino real. Empty muestra 0 a 0 en vez del 1 a 0 original. Clic de página previene navegación # y último enlace usa totalPages. Valores se insertan como texto.

## Detail Mapping

Nuevo resources/views/ganancia/indexDetail.blade.php, misma composición compartida. Fecha de cabecera, moneda, contexto visible en shell y enlace de regreso. Columnas producto/presentación, fecha, cantidad, venta, utilidad. Consume material_description/date_detail/quantity/price_sale/utility. Fila id=0 TOTAL DEL DÍA llega del backend y se destaca; no se suma desde filas. El contrato no entrega costo separado, porcentaje margen ni ventas individuales: no se inventan. La ausencia de dataset impide certificar render dinámico real de esta vista; Blade compila y se mantiene el rechazo de cabeceras inválidas.

## Legacy Summary

LEGACY UNSCOPED — EXCLUDED. ID1 no se reasigna ni se expone. Migración no backfillea esa cabecera.

## Exports

No hay export de summaries enlazado por estas vistas. Rutas relacionadas son User Profit, sin botones nuevos:

| Export | Auditoría | QA |
|---|---|---|
| ganancia.export.trabajador | READ EXPORT — CONTRACT AUDITED; GananciasTrabajadorExport y sus dos sheets usan UserProfitReport query, contexto y filtros creator/startDate/endDate; permisos showGananciaWorker_gananciaDiaria + export_gananciaDiaria | EXPORT QA PENDING, no ejecutado |
| ganancia.export.ventas_detallado | READ EXPORT — CONTRACT AUDITED; GananciaVentasDetalladoExport usa UserProfitReport query/line; permisos showGananciaWorker_gananciaDiaria + exportDetail_gananciaDiaria | EXPORT QA PENDING, no ejecutado |

No se presume que sean export del resumen diario.

## Documents

El contrato daily no entrega documentos/PDF y no se añadieron enlaces. DOCUMENT SECURITY — BACKEND/INFRA OPEN heredado de superficies comerciales; no se abrió ni certificó ningún documento.

## Responsive QA

Listado real vacío Owner EDESCE, navegador local 8080. Mediciones documentElement.scrollWidth = innerWidth en 320,375,390,576,768,992,1200,1440. Tabla con scroll local. Capturas desktop y móvil; consola sin errores observados. CSS nuevo: 0. Detalle y tablas pobladas: QA visual PENDING RECALCULATION, no afirmar pase sin dataset. Assets Next observados en DOM. Sintaxis node --check, artisan view:cache y git diff --check aprobados.

## Cross-context QA

Harness PHP temporal no versionado, transacción SET TRANSACTION READ ONLY y rollback, Auth::setUser con sesión array efímera, InitializeTenantContext; sin login POST ni sesión persistida. Controllers reales, no mocks.

| Cuenta | Tenant/company/branch | Listado | ID1 legacy y 99999999 en vista/AJAX |
|---|---|---|---|
| Owner EDESCE | 1/1/1 | 0 | ModelNotFound |
| Owner EDESCE | 1/1/8 | 0 | ModelNotFound |
| Owner EDESCE | 1/2/2 | 0 | ModelNotFound |
| Owner EDESCE | 1/5/6 y 1/5/7 | 0 | ModelNotFound |
| Owner Gamarra | 3/3/4 | 0 | ModelNotFound |

ModelNotFound corresponde al contrato 404; no confundir prueba directa de controller con medición HTTP. Browser real probado con sesión existente EDESCE 1/1/1. Gamarra probado en harness, no login navegador. Context isolation: QA PASSED para exclusión legacy/ID inexistente y empty contextual. Summary propio válido y cruces con summary de otra branch/company/tenant: PENDING RECALCULATION, no existen candidatos. No es evidencia suficiente para certificar toda la matriz positiva y cruzada.

## Backend Sync Notes

- REAL DATA QA — PENDING RECALCULATION: proceso posterior autorizado fuera de esta fase.
- DOCUMENT SECURITY — BACKEND/INFRA OPEN, sin cambios.
- Profit Calculation — BACKEND AUTHORITATIVE / FRONTEND ALIGNED.
- Summary Generation — OUT OF SCOPE.
- List y Detail — FRONTEND INTEGRATED — READ PATH; QA de detalle real pendiente.
- Backend — UNCHANGED. Sin ventas, costos, summaries, detalles ni exports modificados; sin POST, cron, jobs ni comandos de cálculo.

## Revisit Triggers

Cuando existan resúmenes válidos, verificar cabecera propia, filas/total null, paginación >10, múltiples monedas excluidas y 404 con IDs válidos ajenos. Repetir responsive de detalle en ocho anchos y Gamarra en navegador. Reauditar ante cambios de cálculo, reversión, moneda, contexto, relaciones o exports. No relajar scopes para obtener evidencia.

## Files

- config/venti-next.php
- resources/views/ganancia/index.blade.php
- resources/views/ganancia/indexDetail.blade.php (nuevo)
- public/js/ganancias/index.js (compartido listado/detalle)
- docs/REAL-NEXT-DAILY-PROFIT-READONLY.md

## Contextual Recalculation QA

Cierre Roadmap C #7 — 2026-11-12. Esta sección sustituye los estados históricos PENDING RECALCULATION / Summary Generation OUT OF SCOPE de la auditoría inicial anterior. La escritura autorizada fue exclusivamente el comando de Daily Profit con fecha 2026-11-04, ejecutado dos veces. Backend y frontend permanecen sin modificaciones; no se realizaron otras escrituras comerciales.

### Preflight

Lectura real bajo transacción MySQL READ ONLY, rollback al terminar. Se revisaron CalculateResumenDiarioGanancia, DailyProfitReport, UserProfitReport y los modelos antes de ejecutar. No hay hooks de escritura adicionales en los modelos inspeccionados; recalculate guarda la cabecera y elimina/recrea solo sus detalles dentro de una transacción. La lectura de company con lockForUpdate no actualiza company.

- CONTEXTS: exclusivamente tenant1/company1/branch1.
- SALES: 5 ventas, IDs116,117,118,119,120; todas PEN, date_sale dentro de [2026-11-04,2026-11-05), created_by=4.
- Anuladas/reversed excluidas por contrato salesInContext: state_annulled=false y reversal null/distinto de reversed. En el dataset de esta fecha las cinco tienen state_annulled=0 e internal_reversal_status=none: 0 exclusiones reales y ninguna moneda ajena. No se fabricó una venta anulada para probarlo.
- Actor disponible en las cinco ventas; cinco líneas con costo registrado, missing_cost_count=0.
- CURRENT SUMMARIES de la fecha: 0.
- CURRENT DETAILS de la fecha: 0.
- Legacy existente: summary1, 2026-01-20, T/C/B/currency NULL, cantidad12.00, venta460.00, utilidad-20.00; created_at/updated_at 2026-01-20 19:57:57.

### Contexts

`DailyProfitReport::contexts('2026-11-04')` devuelve solo 1/1/1. Se volvió a consultar tras cada ejecución y se mantuvo el mismo contexto. No apareció contexto adicional; se cumplió la condición de autorización.

### Command

Ejecutado desde raíz real con PHP7.3.33, dos veces y solo para la fecha autorizada:

```text
C:/wamp64/bin/php/php7.3.33/php.exe artisan calculate:ganancia --date=2026-11-04
```

Ambas ejecuciones: exit0, `Fecha 2026-11-04. Resúmenes procesados: 1. Fallos: 0.` No cron, otras fechas, jobs ni llamada directa a recalculate fuera del comando.

Antes y después de CADA ejecución se compararon CHECKSUM TABLE EXTENDED contra el preflight: 231 objetos, 230 tablas con checksum disponible. La única excepción fue la vista inventory_movements_view. Solo cambiaron ganancia_diarias y ganancia_diaria_details; 228 tablas verificables restantes sin diferencias, incluidas sales, sale_details, stock/inventario, caja, precios, documentos y users. Comparaciones realizadas inmediatamente alrededor del comando, antes del login/cambios de sesión del QA de navegador. Los checksums son evidencia de contenido, no un registro binlog de cada sentencia ni prueba contra modificaciones concurrentes transitorias. No se exportaron registros ni secretos de esas tablas.

### Summary Result

Exactamente una cabecera contextual: **ID2**, fecha2026-11-04, tenant1/company1/branch1, PEN. Primera creación 2026-11-12 20:38:28; segunda ejecución updated_at20:38:48. No duplicados.

| Métrica | Resultado |
|---|---:|
| Cantidad vendida | 23.00 |
| Venta PEN | 1460.00 |
| Utilidad PEN | 400.62 |
| Costo de líneas fuente (no columna del summary) | 1059.38 |

### Detail Result

Una agrupación por StockItem71 / Material1; cinco líneas fuente157–161, ventas116–120. Cantidades8+8+4+2+1=23; vendidos500+500+250+140+70=1460; costos368.48+368.48+184.24+92.12+46.06=1059.38; utilidad400.62.

Primera ejecución detailID2; segunda detailID3, FK ganancia_diaria_id=2. Ambas con fecha2026-11-04, quantity23.00, price_sale1460.00 y utility400.62. No details duplicados ni huérfanos del summary recalculado.

El esquema de detalle **no almacena costo separado**. Se contrastó con las líneas originales mediante UserProfitReport::line; no se añadió ni inventó un campo. Null cost: contrato preservado (costo ausente produce utilidad NULL, también a nivel de grupo/summary); dataset actual sin costos ausentes, por lo que ese caso sigue revisado estáticamente y no certificado con datos reales.

AJAX real devuelve producto `POLO NEGRO ADIDAS SPORT HOMBRE` y fila id0 `TOTAL DEL DÍA`, ambos con23.00/1460.00/400.62. El total no se duplica como producto persistido.

### UserProfit Comparison

Se ejecutó lectura real `UserProfitReport::query(null,'04/11/2026','04/11/2026')->with('details')` bajo TenantContext1/1/1 y la cuenta Owner correspondiente. IDs116–120, mismos que el cálculo. Totals: quantity23, sale1460, cost1059.38, utility400.62, missing_cost_count0. Coincidencia completa al redondeo backend de dos decimales. No diferencias inesperadas ni nuevo BACKEND SYNC OPEN de cálculo.

### Cross-context Results

Pruebas de navegador con cuentas autorizadas Owner Gamarra y Owner EDESCE; cambios mediante selector de contexto existente. No se modificaron defaults ni memberships.

| Contexto | Listado | Detail summary2 por URL | Evidencia adicional |
|---|---|---|---|
| EDESCE1/1/1 | 1 resumen, ID2 | Disponible | Filas reales y TOTAL DEL DÍA |
| EDESCE1/1/8 | 0 | 404 | Sesión Owner, OUTLET DE EDESCE |
| EDESCE1/2/2 | 0 | 404 | Sesión Owner, Tienda Prueba1 |
| EDESCE1/5/6 | 0 | 404 | Sesión Owner, PRINCIPAL |
| Gamarra3/3/4 | 0 | 404 | Sesión Owner, Local Principal |
| EDESCE1/5/7 | 0 | ModelNotFound | Harness READ ONLY, no recorrido navegador adicional |

Harness adicional: Auth::setUser, sesión array efímera, InitializeTenantContext, controladores reales; vista y AJAX detalle rechazan summary2 en TODOS los contextos ajenos anteriores y legacy1 en todos, incluido1/1/1. ModelNotFound en harness corresponde al contrato404; se distingue de HTTP404 observado en navegador. No mocks ni bypass de scopes.

QA visual con datos: listado fecha04/11/2026, cantidad23.00, venta1460.00, utilidad400.62, PEN; detalle legible con una agrupación y total backend. Orden date_resumen DESC/id DESC confirmado en código; solo una cabecera visible, no se certifica orden entre múltiples registros. 1440px desktop y390px móvil sin overflow global; scroll horizontal local en tabla móvil. Cantidades/importes right, fechas center, producto left, consistentes con baseline. No regresión que requiera CSS/Blade/JS nuevo. Consola final sin errores/warnings capturados. Baseline responsive anterior conservado, sin repetir matriz completa.

Detalle validado por URL directa y activación del enlace con Enter; Volver al listado funciona. Dos clics automatizados iniciales sobre Detalles solo mostraron tooltip sin transición observable; no se demostró un defecto de código y no se cambió navegación por esa observación. La validación de activación con ratón queda como observación a revalidar manualmente, separada del QA contextual/datos aprobado.

Capturas fuera del repo: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/daily-profit-closure/`: list-desktop.png, detail-desktop.png y detail-mobile.png. Viewport restaurado, sesión final EDESCE1/1/1, detalle2 abierto para revisión.

### Idempotency

Segunda ejecución del MISMO comando: una cabeceraID2, mismos totales y contexto, un detalle nuevoID3 reemplazandoID2. Cambian timestamps/ID de detalle según contrato; no hay duplicados ni cambios en tablas comerciales ajenas. Idempotencia funcional QA PASSED, no identidad byte-a-byte de tablas de resumen.

### Legacy Preservation

Summary1 sigue en2026-01-20 con T/C/B/currency NULL, mismos totales y timestamps. El comando busca solo el contexto completo + fecha y reconstruye exclusivamente la relación details() de summary2; no adopta legacy. Navegador en1/1/1 rechaza detalle1 con404. Harness lo rechaza en todos los contextos. LEGACY UNSCOPED — STILL EXCLUDED.

### Remaining Open Items

- Documents: BACKEND/INFRA OPEN. No comprobantes ni PDFs abiertos.
- Exports: QA PENDING. Ninguno ejecutado.
- Null cost, agrupación fallback Material sin StockItem, múltiples productos/páginas y ventas anuladas/reversed: contrato estático conservado; sin dataset adicional creado para cobertura.
- Observación de clic automatizado en Detalles: revalidación manual pendiente; acceso directo y teclado verificados.
- Código backend/frontend: UNCHANGED; CSS nuevo0. Único archivo modificado por esta fase: este documento. Sin commit.

Estado final contextual:

- Daily Profit List / Detail: FRONTEND INTEGRATED — READ PATH.
- Contextual Dataset: QA PASSED.
- Recalculation: QA PASSED.
- Idempotency: QA PASSED.
- Cross-context Isolation: QA PASSED.
- Legacy Summary: EXCLUDED.
- Documents: BACKEND/INFRA OPEN.
- Exports: QA PENDING.
- Backend: UNCHANGED (código; datos de summaries/details recalculados con autorización explícita).

## Reporting Exports — QA Closure 2026-11-13

**Daily Profit export: NOT IMPLEMENTED / N/A**, no clasificar QA PASSED de una ruta inexistente. Inspeccionados GananciaDiariaController, routes, views/ganancia/index e indexDetail y clases Exports. Las únicas rutas Excel del controller corresponden a User Profit, con sus propios permisos showGananciaWorker/export/exportDetail. Daily Profit tiene summary/detail con show_gananciaDiaria; no existe XLSX/CSV/PDF dedicado, filtro de export ni paginación export que certificar. No crear uno en este cierre.

Contraste real READ ONLY, sin recalculate/cron: tenant/company/branch1/1/1,PEN,04/11/2026,summaryID2: quantity23,total_sale1460.00,total_utility400.62. Suma de detalles contextualizados:23/1460.00/400.62. UserProfitReport y su export filtrado al mismo día/actor4: IDs116–120,23/1460.00/costo1059.38/utilidad400.62. Coincide sin hardcodear ni recalcular resumen persistido. Summary legacyID1 no aparece en summaries(); outlet1/1/8,companies1/2/2,1/5/6 yGamarra3/3/4 vacíos.

Scope/summary/detail contrast QA PASSED. Export Scope/Filters/Permissions/Totals/Cross-context: **N/A — no export propio**. Si producto desea export del resumen diario, requiere fase/contrato, no reutilizar User Profit fingiendo equivalencia. Archivo Daily XLSX generado:ninguno. DOCUMENT SECURITY — BACKEND/INFRA OPEN. Backend/frontend unchanged, CSS0, sin mutaciones ni commit.
