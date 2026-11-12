# REAL NEXT — Ganancias por usuario — Read-only Integration

Fecha: 2026-11-12. Listado y detalle integrados; backend, cálculos, exports y registros comerciales sin cambios. Sin commit. No se ejecutaron ventas, descargas documentales ni exports. Cambios de contexto realizados por el selector autorizado para QA.

## Contract Audit

Auditados GananciaDiariaController (acciones Trabajador y exports), UserProfitReport, ambos Blade, public/js/ganancias/indexTrabajador.js, exports/sheets y rutas. El listado es por venta, no agrupación por trabajador ni dashboard. AJAX devuelve data, pagination y totals. Totales corresponden a todos los registros filtrados; filas paginadas de diez. Orden date_sale DESC, id DESC.

## Routes

Prefijo `/dashboard`. Todos estos endpoints son GET:

| Ruta relativa | Nombre / acción |
| --- | --- |
| `/listado/ganancias/diarias/trabajador` | ganancia.index.trabajador / indexTrabajador |
| `/get/data/ganancias/trabajador/V2/{numberPage}` | sin nombre / getDataGananciasTrabajador |
| `/listado/ganancia/detalles/trabajador/{sale_id}` | ganancia.detail.usuario / indexDetailTrabajador |
| `/export/ganancias/trabajador` | ganancia.export.trabajador / exportGananciasTrabajador |
| `/export/ganancia/ventas-detallado` | ganancia.export.ventas_detallado / exportGananciaVentasDetallado |

Solo se agregaron a allowlist las dos rutas de vista. ALLOWLIST MATCH: YES. Carga de venti.min.css y venti-next.css comprobada; shell NEXT en listado y detalle.

## Permissions

Middleware auth, check.user.enabled, password.changed y tenant.context (InitializeTenantContext). Vistas, detalle y AJAX exigen showGananciaWorker_gananciaDiaria. Export resumen exige además export_gananciaDiaria; detallado exige además exportDetail_gananciaDiaria. Se preservan @can de botones y hidden permissions. No se alteró autorización de comprobantes.

## UserProfitReport

query valida actor y rango; usa tenantId/companyId/branchId de TenantContext. salesInContext retira TenantScope y aplica explícitamente los tres IDs. authors obtiene actores presentes en ventas elegibles del contexto, no usuarios globales. Detalle usa la misma query y findOrFail; no agrega accesos a otras branches.

## Tenant / Company / Branch Scope

| Ruta | Tenant | Company | Branch | Actor | Permission | Guard/Service |
| --- | --- | --- | --- | --- | --- | --- |
| Listado/AJAX | Activo | Activa | Activa | created_by opcional en filtro | showGananciaWorker_gananciaDiaria | InitializeTenantContext + UserProfitReport |
| Detalle | Activo | Activa | Activa | snapshot del Sale | mismo | query()->findOrFail |
| Export resumen | Activo | Activa | Activa | mismo filtro | show + export_gananciaDiaria | sheets usan UserProfitReport |
| Export detallado | Activo | Activa | Activa | mismo filtro | show + exportDetail_gananciaDiaria | UserProfitReport |

InitializeTenantContext valida pertenencia activa y acceso a empresa/sucursal en cada petición. No se introdujo contexto por querystring ni se modificaron scopes. No aparecen históricos sin sucursal por este contrato.

## Actor vs Worker

Actor = created_by; etiqueta = created_by_name, si falta `Usuario #ID`, o `No registrado` sin ID. Nunca Worker como fallback. Filtro mantiene ID/name creator pero cambia ayuda visual a usuario. Detalle muestra worker_id separado como `Trabajador #ID`, sin consultar/reconstruir identidad histórica. Venta #1: actor No registrado y trabajador #1, preservados simultáneamente. Venta #120: Owner EDESCE y trabajador No registrado.

## Profit Formula Contract

Backend line(detail): quantity = quantity; vendido = total - discount; costo = total_cost si no null, en otro caso unit_cost × quantity si unit_cost no null y quantity > 0; de lo contrario null. Ganancia = vendido - costo solo si costo existe. No fallback al precio actual del material/stock en la ruta activa. Helpers legacy presentes en controller/exports no sustituyen UserProfitReport en estas acciones.

totals suma líneas, redondea importes a dos decimales, cuenta missing_cost_count; si cualquier costo falta, total_cost_sum y total_utility_sum son null. No hay porcentaje de margen entregado ni se calcula desde JS. Frontend solo formatea valores backend y llama report->line en Blade como antes.

## Missing Cost

Null se representa `Sin costo registrado`; cero se conserva numérico. Totales vacíos ahora usan data.totals, sin inventar cantidades monetarias. Prueba aislada PHP sin DB: [quantity=2,sold=20,cost=null,profit=null]; costo explícito 0 produce profit 20; unit_cost 3 produce costo 6 y profit 14. QA real: venta #1, dos líneas sin costo; venta #120 vendido 70.00, costo 46.06, ganancia 23.94. Total sin filtro mantiene estado desconocido por históricos incompletos.

## Currency

Filtro obligatorio currency=PEN; UI etiqueta PEN/S/. No USD, conversiones ni mezcla de monedas. No se añaden filtros monetarios.

## Filters

creator nullable integer >=1; startDate/endDate se pasan a query como start/end, ambos requeridos juntos, d/m/Y y final >= inicial. Filtran date_sale desde inicio del primer día hasta antes del día siguiente al final. No filtros de año, mes, cliente ni venta nuevos. Buscar resetea página a 1. Select2 y Bootstrap Datepicker conservados. Exports conservan exactamente creator/startDate/endDate.

QA: en EDESCE principal, filtro Owner EDESCE entrega seis ventas; rango 04/11/2026–04/11/2026 más actor entrega cinco, cantidad 23, vendido S/ 1,460.00, ganancia S/ 400.62. Paginación sin filtro: 1–10 y 11–20 de 91. Se corrigió enlace final que usaba variable de iteración en vez de totalPages (ahora página 10). Se evita navegación # al buscar/paginar. No se cambió estrategia ni endpoint.

## List Mapping

Patrón Advanced Operational List reutilizado de Material List V2 y superficies aprobadas. Toolbar, next-form-section, summary, next-data-table, next-row-actions, paginación y empty state. Sin nuevo CSS; se retiran estilos legacy locales y overlay absoluto a favor de composición modal Bootstrap existente (hook export-overlay intacto).

Columnas existentes: Venta/fecha centradas, usuario izquierda, cantidad centro, vendido/ganancia derecha, acciones centro. Dropdown conserva print_url y detail_url. No se inventan costo por fila, cliente, worker ni margen en AJAX; solo existen en detalle cuando disponibles. Los tres totales reutilizan IDs originales, con jerarquía compacta. Mensaje vacío corregido para no mostrar “1 a 0”. Errores no JSON ya no provocan dereferencia de responseJSON inexistente. Export functions intactas.

## Detail Mapping

Secciones read-only: identidad (venta, fecha, actor, worker ID opcional, cliente), tabla de líneas con cantidad/vendido/costo/ganancia y acción Volver. Mismos datos Sale/report y cálculo backend. No se inventaron estado documental, porcentaje ni nuevos enlaces de emisión.

## Documents

DOCUMENT SECURITY — BACKEND/INFRA OPEN. Controller construye asset('comprobantes/pdfs/' + pdf_path) cuando existe; en otro caso route puntoVenta.print. Se conserva contrato, sin abrir ni descargar documentos. rel=noopener para nueva pestaña. El aislamiento del reporte NO demuestra protección de archivos públicos ni certifica permisos del documento.

## Exports

GananciasTrabajadorExport contiene GananciasResumenSheet/GananciasReporteSheet; consultas activas y totales usan UserProfitReport. GananciaVentasDetalladoExport también usa query/line y conserva texto de costo faltante. Excel::download genera archivo: no ejecutado durante QA. Rutas, permisos, filtros y funciones JS preservados. EXPORT RUNTIME QA PENDING; no se certifican contenido final, estilos o formato Excel por inspección HTML.

## Responsive QA

| Ancho | Listado | Detalle | Overflow global |
| --- | --- | --- | --- |
| 320 | PASS | PASS | NONE |
| 375 | PASS | PASS | NONE |
| 390 | PASS | PASS | NONE |
| 576 | PASS | PASS | NONE |
| 768 | PASS | PASS | NONE |
| 992 | PASS | PASS | NONE |
| 1200 | PASS | PASS | NONE |
| 1440 | PASS | PASS | NONE |

Mediciones DOM scrollWidth <= viewport en ambos; tabla con desplazamiento local. Inspección visual desktop listado filtrado y móvil 375; calendario abierto móvil sin overflow. Dropdown abre Ver ganancia y Comprobante; solo se navegó al detalle. Consola final sin errores capturados. Export overlay no abierto por exclusión de exports.

Evidencia fuera del repo en `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`: user-profit-list-desktop.png, user-profit-list-mobile.png, user-profit-detail-desktop.png, user-profit-gamarra-empty.png. Captura full-page móvil falló; se conservó captura viewport móvil válida.

## Cross-context QA

| Cuenta/contexto | Prueba | Resultado |
| --- | --- | --- |
| Owner EDESCE, T1/C1/B1 Local Principal | Listado | 91 ventas elegibles |
| mismo | Detalles #120 y #1 | Abren; datos/costo/actor correctos |
| mismo | Detalle #114 de branch8 | 404 |
| Owner EDESCE, T1/C1/B8 OUTLET | Listado y #114 | Una venta, detalle abre: vendido 80/costo80/ganancia0 |
| mismo | Detalle #120 de branch1 | 404 |
| Owner EDESCE, empresa NEGOCIO DE PRUEBA (C2), Tienda Prueba 1 | Detalle #120 de C1 | 404; listado vacío |
| Owner Gamarra, T3/C3, Local Principal | Listado | Vacío, solo TODOS en actores |
| mismo | Detalle #120 de EDESCE | 404 |

Una consulta SQL dentro de transacción READ ONLY corroboró 91 ventas elegibles T1/C1/B1 y una T1/C1/B8, sin otras ventas elegibles en el dataset. No se crearon datos para QA. Contexto final restaurado a Owner EDESCE / C1 / Local Principal. Las cuentas utilizadas no se incluyen con contraseñas.

## Backend Sync Notes

[OPEN]
Point: DOCUMENT SECURITY — BACKEND/INFRA OPEN.
File / endpoint: GananciaDiariaController::getDataGananciasTrabajador, print_url / comprobantes/pdfs.
Impact: URL pública puede no aplicar aislamiento documental.
What backend needs to define or correct: entrega protegida o política de infraestructura documental.
Frontend can continue: Yes; no descargas ni ampliación de capacidades.

[OPEN]
Point: tratamiento comercial de notas de crédito y reversión parcial.
File / endpoint: UserProfitReport::salesInContext.
Impact: excluye state_annulled=true y internal_reversal_status=reversed; no descuenta explícitamente notas de crédito ni filtra otros estados de emisión en esta query.
What backend needs to define or correct: confirmar alcance comercial o extender reporte si debe netear notas parciales; frontend no ajusta históricos.
Frontend can continue: Yes.

Export runtime queda pendiente de QA autorizado, no bloquea read path. No se reabrió Nubefact ni se cerraron pendientes de otras fases.

## Revisit Triggers

Cambios de created_by/snapshots, scope, costos, moneda, notas/reversión, permisos, formato de respuesta/paginación, export o entrega documental. Reauditar ante datos sin fecha, cambios de worker/actor o políticas de acceso a otras sucursales. No reconstruir costos/autor desde frontend.

## Final Status

- User Profit List — FRONTEND INTEGRATED — READ PATH
- User Profit Detail — FRONTEND INTEGRATED — READ PATH
- Context Isolation — QA PASSED (matriz anterior)
- Profit Calculation — BACKEND AUTHORITATIVE / FRONTEND ALIGNED
- Missing Cost Semantics — PRESERVED
- Document Security — BACKEND/INFRA OPEN
- Mutations — OUT OF SCOPE
- Backend — UNCHANGED

Checks: node --check public/js/ganancias/indexTrabajador.js, PHP 7.3 artisan view:cache y git diff --check PASS. Archivos: ambos Blade, JS del listado, config/venti-next.php y este documento. CSS nuevo 0; no backend/exports modificados.

## Polish de filtros y calendario — 2026-11-12

La columna fija de acciones provocaba wrapping y el grupo de fechas tenía una altura distinta. Se reutilizan columnas Bootstrap flexibles (`col-lg-3`, `col-lg`, `col-lg-auto`), acciones sin wrapping desde desktop, `input-group-sm` y Select2 al 100% del ancho. No se agrega CSS.

El datepicker descontaba el desplazamiento de 57 px del body al calcular su posición, aunque el popup utilizaba otro origen. Se configura `.wrapper` como contenedor y orientación inferior; el calendario queda a 4 px debajo del input, sin modificar filtros ni payloads.

QA visual: 992, 1200, 1440 y 1920 px con usuario, fechas, Buscar y Exportar alineados en una fila; 320, 375, 390, 576 y 768 px con reorganización responsive. Calendario dentro del viewport y sin overflow global. Verificado el extremo final del rango a 320 px. Sintaxis JS, compilación Blade y git diff --check PASS. Sin escrituras comerciales, exportaciones ni cambios backend; sin commit.
