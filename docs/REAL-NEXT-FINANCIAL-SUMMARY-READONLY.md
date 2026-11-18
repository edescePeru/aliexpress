# REAL NEXT — FINANCIAL SUMMARY + DRILLDOWN — READ-ONLY

2026-11-18. FRONTEND INTEGRATED — READ PATH. Fuente: POST-MERGE-DEV-FIX-REVIEW, merge5fb180d.

## DEV Contract

CashRegisterController::financialSummary + FinancialSummary son autoridad. CashBoxController::summaryIndex/summaryList/summaryFilters proporcionan consulta read-only por caja; no se modificaron. No se integra cashMovement.admin ni mutaciones.

## Routes

Todas bajo /dashboard y middleware de autenticación/contexto existente.

| Route | Action | Permission | Read/Mutation |
|---|---|---|---|
| GET /financial/summary — financial.summary | CashRegisterController::financialSummary | showFinancialSummary_cashBox | Read |
| GET /financial/summary/cash-boxes/{cashBox}/movements — financial.summary.movements | CashBoxController::summaryIndex | showFinancialSummary_cashBox | Read |
| GET /financial/summary/cash-boxes/{cashBox}/movements/list — financial.summary.movements.list | CashBoxController::summaryList | showFinancialSummary_cashBox | Read JSON |

Allowlist añade sólo financial.summary y financial.summary.movements. No JSON, Admin, sesión ni regularización. ALLOWLIST MATCH: YES; ambos assets venti.min.css/venti-next.css presentes.

## Permissions

Owners EDESCE4/Gamarra7: can(showFinancialSummary_cashBox)=true contra permisos actuales. Sin bypass supuesto. Request de usuario sin permisos: summary403. Drilldown tiene middleware del mismo permiso; no se certifica nueva matriz HTTP de roles. Proceso QA usa cache-array para no tocar caché compartida.

## Tenant / Company / Branch

Tenant/company obligatorios por MaterialParameters::context y filtros explícitos. Summary branch_id opcional; selector branches de company vigente, branch ajena/inexistente404. Sin branch significa consolidado de empresa, no inferir local de sesión. Drilldown fija cashBox por ID T/C y branch cuando se suministra; sustituye cash_box_id del query con caja resuelta. Session filtra dentro del conjunto ya scoped. Branch null de movimientos se excluye del drilldown.

## General Cash Boxes

Cajas con branch_id null se ofrecen en sucursales de la misma company. Al filtrar branch, consolidated=null en caja compartida y la UI enlaza al saldo de empresa; no lo representa como cero. Servicio lista cada caja una vez por consulta y filtra movimientos/sesiones/fondos por branch; frontend no suma respuestas de distintas branches ni columnas entre sí. Totales por columna son backend, no prueba de conciliación. En consolidado company cajas[1,2,5,6] una vez cada una.

## Dataset

Lectura MySQL aliexpress_multitenant_3 con PDO fijado y START TRANSACTION READ ONLY/ROLLBACK. Sin fixtures ni registros modificados. Datos del servicio, period=all:

| Contexto | Cajas | Movimientos por caja (IDs completos) |
|---|---|---|
| EDESCE T1/C1/B1 | 1,2,5 | 1:[263,262,260,259,258,257,256,255,253,250]; 2:[271,270,269,268,267,266,265,264,261,252,251]; 5:[] |
| EDESCE T1/C1/B8 | 2,5,6 | 2:[272];5:[];6:[254] |
| EDESCE T1/C2/B2 | [] | Sin cajas; consulta de cajas C1 rechazada |
| Gamarra T3/C3/B4 | [] | Sin cajas; consulta de cajas EDESCE rechazada |

Ejemplos de lectura, no conciliación: B1 caja1 consolidated1370/open100/receipts1560/expenses300; caja2 consolidated=null/open68/receipts596/expenses170; caja5 consolidated=null/open0. B8 caja2 receipts100; caja6 consolidated680/receipts80. Fondos externos actuales0 en estos grupos.

## Currency Groups

Dataset positivo PEN. Render del resumen mantiene grupos independientes por currency y totales por grupo. Sin suma PEN/USD, conversión ni KPI nuevo. Drilldown muestra box.currency en cabecera/summary, ya que el JSON no incluye moneda por movimiento. USD positivo pendiente de dataset; ramas de render agrupado conservadas.

## Summary Metrics

Saldos: consolidated (saldo rendido), open (turnos abiertos), sessions y pending (por abonar). Actividad: receipts netos, expenses, commissions, external_funds, opening_transfers. Todos vienen del servicio; frontend sólo number_format. No introducir saldo inicial calculado ni sumar saldo rendido+turnos como saldo nuevo.

Pendientes excluyen deferred_cancelled_at, regularizaciones/hijos y ventas anuladas/reversed según servicio. Fondos proceden de cash_opening_funds con origin external/consolidated; no equivalen a abrir/editar sesión.

Fechas: tab balances/activity; period all o range. Controller defaults range desde primer día del mes hasta hoy America/Lima. Summary convierte desde al inicio del día y hasta a límite exclusivo del día siguiente. Actividad/fondos por created_at; saldos/sesiones/pendientes son estado actual, no saldo histórico reconstruido. UI conserva esos defaults y deshabilita fechas en all, sin inventarlos.

## Drilldown Contract

Ver movimientos abre conjunto real por caja, no filtro exacto de una métrica. Balances transmite caja+branch, sin fechas; Activity transmite caja+branch+date_from/date_to o period=all. El JSON filtra whereDate inclusivo; no rehacer timezone desde JS. No hay filtro backend específico de pending/funds/balance_effect para transformar cada KPI en una conciliación. Por eso los números no son enlaces falsos de KPI: se conserva Ver movimientos contextual.

Filtros: descripción q, session_id, type(sale/income/expense), subtype_id incluido none, date_from/date_to. Caja fija del endpoint. Users/subtypes del controller son colecciones vacías; no se inventan lookups globales. Se omite selector de usuario sin opciones; actor sí se muestra. Sesiones provienen de T/C/caja y branch, excluyendo nullbranch. BackURL lo entrega backend con branch/period/fechas.

JS summary-readonly.js sólo GET; query y paginación actuales. Filtro reinicia página1, aborta petición anterior y descarta respuestas antiguas. HTTP error visible (403 incluido), no convertido en empty state. No resolve tenant/company/branch en JS.

## Movements

Columnas: ID, created_at formateado backend, user_name con branch_name/cash_register_id, cash_box_name, tipo, subtype_name, estado, balance_effect, amount, amount_regularize, commission, description/observation. Actor es User entregado, no Worker ni UserController legacy. Textos insertados con textContent. Estado respeta deferred_cancelled, regularize, regularization_source_id y balance_effect de DEV. Balance_effect se muestra explícito Sí/No; importes no cambian signo desde frontend. No enlaces a sale_id/entry_id/documentos.

## Empty States

C2/Gamarra: «No hay cajas para el contexto seleccionado», no tarjetas con ceros fabricados. Caja5 o búsqueda sin coincidencias: «No se encontraron movimientos». ErrorHTTP usa bloque de error, no vacío.

## Cross-context

Smoke de controllers reales en lectura:

- C1 branches1/8 admitidas; branches2/4/999999404. Caja6 en branch1 y caja1 en branch8:404.
- C2/Gamarra cajas1/2/5/6/999999404; branches ajenas404.
- Rango summary from=bad422; usuario sin permiso summary403.
- Query cash_box_id=6 sobre ruta caja2 conserva IDs caja2.
- user_id=7 o session_id=999999 en caja2/B1 devuelve vacío por intersección; no fuga.
- Paginación caja2/B1: página1[271,270,269,268,267,266,265,264,261,252], página2[251], total11.
- type=income →[270,252,251]; noviembre2026 →[271,270,269,268,267,266,265,264,261]; búsqueda inexistente[].

No nueva suite extensa, no transacciones financieras, no login HTTP nuevo. Scope positivo/negativo es controller/servicio + permisos/rutas inspeccionados; presentación Chrome usa render real y respuestas JSON capturadas del controller.

## Reconciliation Boundary

FINANCIAL RECONCILIATION — OUT OF SCOPE. EXPORT — OUT OF SCOPE. DOCUMENT SECURITY — BACKEND/INFRA OPEN.

| Action | Route | Permission | Read/Mutation | Included |
|---|---|---|---|---|
| Summary/filtros | financial.summary | showFinancialSummary_cashBox | Read | Sí |
| Drilldown/paginación | financial.summary.movements/list | showFinancialSummary_cashBox | Read | Sí |
| Admin genérico | cashMovement.admin.* | Contrato Admin previo, sin cambio | Separado | No |
| Crear/regularizar/refund/cobro/posting | Endpoints financieros existentes | No reinterpretados ni utilizados | Mutation | No |
| Abrir/cerrar sesión/editar caja | Endpoints existentes | No reinterpretados ni utilizados | Mutation | No |
| Imprimir/documentos/exports | Rutas existentes | No certificados | Documento/export | No |

## Responsive

Reutilizados next-page-heading, page-toolbar, next-form-section, list-toolbar, next-data-table, badges, grid BS4 y pagination. CSS0.

Chrome headless con HTML completo generado por Laravel y datos reales, assets locales; navegación y JSON del drilldown interceptados con respuestas capturadas readonly. Shell notificaciones y servicios externos simulados sin llamadas. No equivale a QA HTTP de sesión completa.

| Ancho | Saldos | Actividad | Drilldown | Vacíos C2/Gamarra | Global overflow |
|---|---|---|---|---|---|
| 320 | PASS | PASS | PASS | PASS | NONE |
| 375 | PASS | PASS | PASS | PASS | NONE |
| 390 | PASS | PASS | PASS | PASS | NONE |
| 576 | PASS | PASS | PASS | PASS | NONE |
| 768 | PASS | PASS | PASS | PASS | NONE |
| 992 | PASS | PASS | PASS | PASS | NONE |
| 1200 | PASS | PASS | PASS | PASS | NONE |
| 1440 | PASS | PASS | PASS | PASS | NONE |

Tablas scroll local. Console sin errores en harness. UI paginación llega a251; búsqueda vacía; error403 distinto de vacío. Requests conservan branch1/página y reinician1 al buscar. No botones mutadores ni links documentales.

## METRIC-01 Update

METRIC-01 / FINANCIAL SUMMARY — FIXED / QA PASSED / FRONTEND INTEGRATED para scope y representación read-only. METRIC-01 global permanece PARTIALLY FIXED/OPEN para Dashboard general, aggregates/reports no cubiertos. No conciliación, exactitud histórica ni mutaciones certificadas.

## Files / Validation

- resources/views/cashRegister/summary.blade.php: presentación contextual existente.
- resources/views/cashBox/admin_index.blade.php: dispatcher según readOnly suministrado por DEV.
- resources/views/cashBox/_admin_legacy.blade.php: contenido anterior preservado sin cambios para Admin genérico.
- resources/views/cashBox/summary_movements.blade.php: superficie exclusivamente read-only.
- public/js/cashBox/summary-readonly.js: renderer/filtros/paginación GET; sin acciones financieras.
- config/venti-next.php: dos vistas exactas.
- Este documento y nota METRIC-01 del backlog.

PHP7.3.33 view:cache PASS; node --check PASS; git diff --check PASS. Backend unchanged, sin commit. Cambios previos de Entry Detail y reauditoría preservados.
