# REAL NEXT — Entry Purchase Report

Estado: **FRONTEND INTEGRATED WITH TECH DEBT**. Fecha: 2026-11-18.
Backend unchanged. CSS nuevo: 0. Sin commit.

## Routes / permission

| Superficie | Método / URI | Nombre / permiso |
|---|---|---|
| Vista | GET /dashboard/entradas/compra/reporte | entry.purchase.report.index / list_entryPurchase |
| Consulta | POST /dashboard/get/json/entries/purchase/report | sin nombre / list_entryPurchase |

EntryController@indexEntryPurchaseReport y getJsonEntriesPurchaseReport.
Ambas comprueban list_entryPurchase y contexto tenant/company. Middleware web,
auth, check.user.enabled, password.changed y tenant.context en el grupo dashboard.
Se conserva el gate adicional existente `searchReportEntries_entryPurchase` del
formulario. El endpoint no exige ese segundo permiso: diferencia contractual
existente, sin ampliar visibilidad ni alterar backend.
Allowlist: únicamente `entry.purchase.report.index`, nombre real de la ruta que
renderiza `entry.index_entry_purchase_report`; no se agregó el nombre Blade como
si fuera una ruta. No se habilitó POST/export/documentos.

## Scope

TENANT + COMPANY. Entry usa BelongsToTenant y SoftDeletes; consulta explícita
por tenant_id, company_id, entry_type='Por compra', finance=false y created_at
entre inicio/fin del día, orden created_at desc. No añade branch al filtro.
Company-wide: Principal y Outlet de C1 devuelven exactamente los mismos IDs.
Parámetros tenant_id/company_id enviados por cliente no gobiernan la consulta.
InitializeTenantContext valida pertenencia/activación del contexto en cada request.

## Dataset

Revalidación actual, excluyendo soft-deleted, rango 01/01/2000–31/12/2100:

| Contexto | Count | IDs completos |
|---|---:|---|
| T1/C1, branches 1 y 8 | 31 | 4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,24,25,35,37,39,40,41,42,43,44,53,54 |
| T1/C2/B2 | 2 | 49,52 |
| T3/C3/B4 (Gamarra) | 1 | 45 |

No se reutilizó el conteo aproximado 35 del discovery: el reporte vigente aplica
SoftDeletes. No se crearon Entries. El dataset inspeccionado usa PEN; se conserva
currency_invoice por fila, sin conversión ni suma de monedas.

## Date contract / Filters

start/end obligatorios en d/m/Y; end >= start. Backend responde 422 por formato,
campos ausentes o rango invertido. Datepicker español conserva rango, clear y
selección, con format dd/mm/yyyy explícito y orientación inferior.
IMPORTANTE: filtro por created_at; columna Fecha por date_entry. Ayuda visible
explica esta diferencia, sin cambiar queries ni sustituir fechas.
Búsqueda textual y orden local DataTables sobre resultados; no consultas extra.
Sin lookups nuevos ni filtros de branch/company inventados.

## POST + Audit side effect

**TECH DEBT — READ QUERY WITH TECHNICAL AUDIT WRITE**.
POST devuelve datatables($entries)->toJson() y ejecuta Audit::create con actor,
acción y duración. No se convirtió a GET ni se eliminó esa escritura.
Pruebas aisladas: conexión READ ONLY, evento creating de Audit interceptado sólo
en memoria del proceso de QA (5 intentos), 0 escrituras DB. Se ejecutaron
controlador real, validación y middleware contextual; no se sustituyó la query.
Navegador real: 3 consultas válidas registraron auditorías técnicas 1092–1094
(user 4), sin tocar datos de negocio. Una comprobación de rango inverso fue
normalizada por el Datepicker y llegó como consulta válida; no se atribuye a
esa interacción una prueba de 422. La cobertura 422 es del harness aislado.
No borramos las auditorías producidas por el contrato real.

## Columns / Actions / References

Columnas: Guía de remisión, Orden de compra, Factura, Tipo de entrada, Proveedor,
Fecha, Diferido, Total, Moneda. Códigos/fechas/estado/moneda centrados; proveedor y
tipo izquierda; importe derecha. Textos escapados, sin insertar HTML de negocio.
Valores derivados total y regla legacy off→NO/otro→SI se preservan.
Orden/proveedor son texto, sin links a otras superficies.

Sin acciones por fila en este read path, por lo tanto no menú vacío ni botones
individuales. Retirados documentos y modal legacy de eliminación. El antiguo
Ver detalles consultaba ItemController@getJsonItemsEntry, fuera del reporte
aprobado y sin filtro company explícito en esa lectura; no se incorpora ni se
reaudita Entry Detail/Entries V2. Se conserva su implementación en sus módulos.
No quedan handlers de cancelación, visualización documental ni código de alta
heredado en el script dedicado (sólo este Blade lo consume).
Column Visibility: NO EXISTÍA; no se inventó componente/configuración.

## Documents/export boundary

DOCUMENT SECURITY — BACKEND/INFRA OPEN. Documentos: OUT OF SCOPE.
EXPORT — OUT OF SCOPE. No Excel, PDF, imágenes de documentos, delete,
regularización, recepción, posting ni movimientos de stock ejecutados.

## Empty / Error / Pagination

Estado inicial pide consultar; cero filas válidas muestra ausencia de resultados.
Búsqueda local sin coincidencias tiene mensaje propio. Fechas ausentes muestran
error antes del POST. 403/422/419/fallo backend tienen mensajes diferenciados;
una respuesta fallida conserva la tabla anterior con aviso explícito, nunca se
presenta como una consulta vacía exitosa. Respuesta JSON inesperada también falla
explícitamente. 500/419 y respuesta malformada: ramas revisadas, no forzadas en
servidor real durante QA. No afirmar certificación de esos fallos.
DataTables client-side preservado: 10/25/50/100 filas, búsqueda, orden y páginas.
Navegador: 1–10 → 11–20 de 31 PASS; búsqueda sin coincidencias PASS; limpiar
búsqueda restaura resultados sin POST. Consulta bloquea doble envío mientras carga.

## Cross-context

Harness temporal: controlador real + InitializeTenantContext, sesiones array y
READ ONLY. Usuarios reales 4 (EDESCE) y 7 (Gamarra), sin cambiar asignaciones.
Comparación de IDs completos con query independiente: PASS en los cuatro contextos.
Manipulación de tenant_id/company_id en body ignorada: PASS.
C1 no recibe 49/52; C2 no recibe IDs C1; Gamarra sólo 45.
Contexto completo inexistente: middleware 403 antes de consultar. Usuario sin
permiso: stub aislado de can=false produce 403; no se modificó ningún rol real.
Fechas ausentes/ISO/invertidas: 422. Rango 01–02/01/1900: data=[].
No se ensayó impersonación mediante cambios de datos persistidos.

## Responsive / Visual parity

Referencia inspeccionada directamente: material.indexV2 y venti-next.css.
Reutiliza next-page-toolbar, next-list-toolbar, next-list-summary,
next-data-table, next-list-pagination y Surface Spacing Standard.
**VISUAL PARITY** en composición, jerarquía, tabla y superficies.
**VALID REAL CONTRACT DIFFERENCE**: DataTables existente, sólo rango de registro,
sin acciones/columnas configurables, sin nuevos filtros del catálogo Material.

| Ancho | Overflow global | Tabla |
|---:|---:|---|
| 320 | 0 px | scroll local |
| 375 | 0 px | scroll local |
| 390 | 0 px | scroll local |
| 576 | 0 px | scroll local |
| 768 | 0 px | scroll local |
| 992 | 0 px | scroll local |
| 1200 | 0 px | visible completa |
| 1440 | 0 px | visible completa |

Mediciones DOM con dataset C1 real después del ajuste de wrapping del buscador
DataTables. Se reutilizaron mw-100, d-flex, flex-wrap y ml-0; no CSS local/global.
Screenshots desktop/móvil revisadas. Datepicker visible y Escape operativo.
Consola sin errores observados. Viewport de QA restaurado.

## Files / Verification

- config/venti-next.php: una ruta de vista.
- resources/views/entry/index_entry_purchase_report.blade.php.
- public/js/entry/index_entry_purchase_report.js: presentación read-only y estados.
- docs/REAL-NEXT-ENTRY-PURCHASE-REPORT.md.

PHP7.3 artisan view:cache PASS; node --check del JS PASS; git diff --check PASS.
Evidencia local temporal: %TEMP%/entry-report-qa.php y entry-report-qa.json
(harness y resultados por ID), entry-report-dataset.php (consulta de inventario).
No credenciales en archivos/documentación. Backend/rutas/modelos: UNCHANGED.

## Backend Sync Notes

[OPEN] POST read query + Audit write: contrato actual conservado, deuda técnica.
[OPEN] Document security backend/infra: no certificada por esta integración.
No resolver deuda de Entry Detail/Inventory ni reabrir Finance Invoice.
Revisitar sólo si DEV cambia permisos, response, fechas, scope o ruta de consulta.
