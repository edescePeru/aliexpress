# REAL NEXT — Compras Express V2 — Read-only Integration

2026-11-13. **FRONTEND INTEGRATED — READ PATH**. Tenant/Company **FRONTEND ALIGNED**; Branch **NOT APPLICABLE / COMPANY LEVEL**. Empty State **QA PASSED**. Positive Dataset QA y multipágina **PENDING DATASET**. Detail **EXCLUDED / NEEDS AUDIT**. Mutations **OUT OF SCOPE**. Backend **UNCHANGED**.

## Routes

Prefijo `/dashboard/`; controlador `OrderPurchaseController`.

| Route | Action / name | Permission | Tenant | Company | Read/Mutation |
|---|---|---|---|---|---|
| GET listado/ordenes/compra/express/v2 | indexExpressV2 / order.purchase.express.indexV2 | list_orderPurchaseExpress | Sí | Sí | Read |
| GET get/data/orders/express/v2/{numberPage} | getDataOrderExpress / sin nombre | list_orderPurchaseExpress | Sí | Sí | Read JSON |

Lookups año/proveedor/estado vienen de indexExpressV2, sin endpoints adicionales. Año se obtiene de todas las órdenes contextuales, no solo Express; se conserva, por eso EDESCE ofrece 2026 aunque Express esté vacío.

## Permission

Rutas verificadas en HEAD y route:list: web, auth, check.user.enabled, password.changed, tenant.context y `permission:list_orderPurchaseExpress`. Sin bypass Owner ni cambios de roles. Allowlist añade **solo order.purchase.express.indexV2**. **ALLOWLIST MATCH: YES**; venti.min.css y venti-next.css observados en navegador.

## Scope

OrderPurchase usa ScopedPurchaseCompany + SoftDeletes. Tenant/company proceden de sesión, no request; sin contexto el scope impone `1=0`. Express aplica siempre `where(type,e)`; no depende del filtro de General ni acepta type para sustituirlo. No hay filtro branch ni condición Finance adicional. No incorpora OrderPurchaseFinance como origen. Órdenes eliminadas excluidas; estados no restringidos salvo filtro solicitado.

Supplier es maestro tenant-scoped; no se añadió filtro company en JS. approved_user restringe users.tenant_id. No se presume equivalencia actor/aprobador ni se copia created_user de General/Normal.

## Dataset

SELECT actual: **0 órdenes type=e**, tanto físicas como activas. Sin fixtures ni registros sintéticos. Verificación con transacción MySQL READ ONLY + ROLLBACK, action real y contexto en memoria:

| Contexto | IDs Express | Evidencia |
|---|---|---|
| EDESCE T1/C1 | `[]` | Action y navegador Owner EDESCE / Local Principal |
| EDESCE T1/C2 | `[]` | Action |
| EDESCE T1/C5 | `[]` | Action |
| Gamarra T3/C3 | `[]` | Action y navegador Owner Gamarra / Local Principal |
| Incompatible T1/C3 | `[]` | Action scoped |
| Tenant/company ausentes | `[]` | Action fail-closed |

InitializeTenantContext ejecutado aisladamente con usuario EDESCE real y contexto completo incompatible: **403**, sin cambiar sesión del navegador. Request manipulado `type=n,tenant_id=3,company_id=3` sigue vacío en contexto T1/C1; código confirma type fijo y scope de sesión. No interpretar vacío como demostración de aislamiento con datos positivos: **POSITIVE CROSS-CONTEXT DATA QA — PENDING DATASET**. No se certifican permisos de una cuenta sin acceso ni variantes de errores no probadas.

## Empty State

Mensaje final «Sin órdenes express.», alineado a la izquierda para ser visible en móvil sin desplazar la tabla. Summary 0 órdenes; paginación «Mostrando 0 a 0 de 0 órdenes de compra», cero links. Toolbar, Buscar y filtros permanecen disponibles. Carga tiene texto propio; error AJAX muestra fallo HTTP, no se convierte en vacío. No se provocó una caída backend para certificar errores HTTP.

Backend devuelve página 1 con totalPages=0/startRecord=1/endRecord=0; página 99 con startRecord=981/endRecord=0. Misma fórmula duplicada de General; ampliación **GENERAL-01**, sin nuevo finding. UI normaliza la presentación vacía a 0/0; no modifica response ni inventa filas. Para futuro resultado positivo fuera de rango conserva aviso y retorno a página 1. Multipágina y render de filas reales pendientes de dataset.

## Filters

Ocho parámetros originales: `year`, `supplier`, `code`, `quote`, `state`, `deliveryDate`, `startDate`, `endDate`. No se agregó filtro type, branch, recepción ni finance.

- year y rango: date_order; rango inclusivo d/m/Y solo si ambos extremos están presentes.
- deliveryDate: igualdad date_arrival, d/m/Y.
- code: LIKE code; quote: LIKE observation.
- supplier: relación contextual por supplier_id; state: igualdad status_order.

IDs/names start/end, deliveryDate, supplier/state y hooks existentes conservados. Buscar reinicia página 1; paginación reenvía filtros. Peticiones anteriores abortadas y respuestas obsoletas descartadas. Navegador probó código sin coincidencias y combinación proveedor/estado; action con ocho filtros devolvió vacío sin error. Semántica positiva pendiente de registros. Bootstrap-datepicker conserva contrato, con anclaje bottom/container wrapper del patrón aprobado.

## Columns

Doce columnas existentes: ID, Código, Fecha Orden, Fecha Llegada, Observación, Proveedor, **Aprobado por**, Moneda, Total, Tipo, Estado, Acciones. Binding approved_user preservado; no sustituido por created_user. Campos textuales con textContent; typeText/stateText conservan el HTML fijo generado por backend. Total/currency se muestran sin recálculo ni conversión. Sin columnas de recepción/almacén/cantidades inventadas. Alineación compartida: códigos/fechas/badges/acciones centro, descripción izquierda, total derecha.

## States

Extraídos de getDataOrderExpress: stand_by → Pendiente; send → Enviado; pick_up → Recogido. Otros status_order no tienen etiqueta en este response; no se normalizan en JS. type=e → Orden Express. Backend ordena date_order DESC; no created_at. Badges y filas positivas no certificados con el dataset vacío.

## Actions

| Action | Route | Permission | Read/Mutation | Included |
|---|---|---|---|---|
| List/filtros/páginas | listado y data Express anteriores | list_orderPurchaseExpress | Read | Sí |
| Detail Express | GET ver/orden/compra/express/{id} | list_orderPurchaseExpress | GET con escritura Audit y lookup legacy | No |
| Create | GET crear/orden/compra/express | create_orderPurchaseExpress | Entrada a flujo de escritura | No |
| Edit | GET editar/orden/compra/express/{id} | update_orderPurchaseExpress | Entrada a flujo de escritura | No |
| Delete | POST destroy/order/purchase/express/{id} | update_orderPurchaseExpress | Mutation | No |
| Change state | POST change/state/order/purchase | update_orderPurchaseNormal | Mutation legacy | No |
| Print | GET imprimir/orden/compra/{id} | No recertificado | Documento | No |
| Export | export de órdenes legacy | No recertificado | Export | No |
| Restore/regularize/recepción | Flujos fuera de esta vista | No auditados | Mutation | No |

Se retiraron del listado botones, templates y modal de mutación; JS conserva endpoint/payload/render de Express, reutiliza orquestación de lectura aprobada y elimina handlers de escritura/documentos/export. Columna acciones queda sin enlaces. No cambian las rutas backend.

## Detail Boundary

showOrderPurchaseExpress mantiene Supplier::all(), **User::all()**, búsqueda find con posterior acceso a id y **Audit::create** al abrir. Aunque OrderPurchase tenga scope, esa combinación no permite certificar el detalle como lectura segura. **EXPRESS DETAIL — EXCLUDED / NEEDS AUDIT**. Sin navegación, allowlist ni integración de detalle. Documentos continúan BACKEND/INFRA OPEN; no PDF/print/export.

## Responsive

320/375/390/576/768/992/1200/1440: filtros abiertos, toolbar, summary y tabla vacía sin overflow global; scroll horizontal local en tabla. Desktop y móvil inspeccionados visualmente. Composición de General/Normal/Entries: next-page-toolbar-context-only, next-form-section, next-list-filters, next-list-summary, next-data-table y paginación. **0 CSS nuevo**. Assets Venti presentes; consola final sin errores.

## Backend Sync Notes

[OPEN] GENERAL-01 ampliado a getDataOrderExpress: cálculo de rangos vacío/fuera de rango compartido por duplicación. Frontend can continue: Yes, presentación coherente sin modificar backend. No incrementar inventario ni duplicar SALES-NEW-01.

[OPEN] Dataset positivo/multipágina/cross-context positivo pendientes. Frontend can continue: Yes. No cerrar por respuestas vacías.

[OPEN] Detail Express y documentos excluidos. No se modificó backend, reglas de compra, consumers ni fachadas General/Normal/Contactos.

Archivos: express_v2.blade.php, expressV2.js, allowlist, este documento y ampliación backlog. PHP7.3 view:cache, node --check y git diff --check correctos. Sin commit, mutaciones comerciales, exports ni fixtures. Navegador final Owner EDESCE / Express V2.
