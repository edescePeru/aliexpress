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
