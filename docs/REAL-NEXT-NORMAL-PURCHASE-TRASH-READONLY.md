# REAL NEXT — NORMAL PURCHASE TRASH — READ-ONLY BATCH

Fecha: 2026-11-18. Normal Purchase Trash y Deleted Detail: **FRONTEND INTEGRATED — READ PATH**.
Backend unchanged. CSS nuevo: 0. Sin commit.

## Routes / Permissions

Todas las rutas siguientes están bajo `/dashboard`.

| Surface | Route | Permission | Scope | Read/Mutation |
| --- | --- | --- | --- | --- |
| Papelera | GET `/ordenes/compra/eliminadas`, `order.purchase.delete` | `destroy_orderPurchaseNormal` | tenant + company | Read |
| Data | GET `/all/order/delete`, sin nombre | `destroy_orderPurchaseNormal` | tenant + company | Read + escritura técnica Audit existente |
| Detalle | GET `/ver/orden/compra/eliminada/{id}`, sin nombre | `destroy_orderPurchaseNormal` | tenant + company | Read |

Actions: `indexOrderPurchaseDelete`, `getOrderDeleteGeneral`, `showOrderPurchaseDelete` en OrderPurchaseController. Middleware común: web, auth, check.user.enabled, password.changed, tenant.context. No se cambiaron rutas/permisos.
La visibilidad de detalle conserva `list_orderPurchaseNormal` y comprueba además el permiso de la ruta `destroy_orderPurchaseNormal`. Total conserva `showPrices_quote` en listado.

## Tenant / Company Scope

OrderPurchase y OrderPurchaseDetail usan ScopedPurchaseCompany. Contexto faltante falla cerrado. Branch NOT APPLICABLE: no se añadió filtro. Supplier conserva su scope tenant. No se filtran registros incorrectos desde JavaScript.

## Soft Delete Contract

SoftDeletes en órdenes y líneas. Listado y binding: `onlyTrashed`, `type = n`. Líneas: `withTrashed` por order_purchase_id, con relaciones material.unitMeasure y stockItem. Una orden activa no resuelve en detalle eliminado.

## Dataset

Revalidado en DB local, sin crear/eliminar órdenes:

| Contexto | IDs completos eliminados normales | Detalles |
| --- | --- | --- |
| EDESCE T1/C1/B1 | [1] | 3 líneas |
| EDESCE T1/C1/B8 | [1] | 3 líneas |
| EDESCE T1/C2/B2 | [] | N/A |
| Gamarra T3/C3/B4 | [] | N/A |

ID 1, OC-00001, PEN; deleted_at 2026-05-25 10:33:13. No se fabricó dataset.

## List

Se conserva DataTables: búsqueda textual local, ordenación, selector de longitud y paginación cliente. Endpoint y payload intactos. Columnas: ID, código, fecha orden, fecha llegada, observación, proveedor, aprobador, moneda, total, tipo, estado, acciones.
Estado inequívoco ELIMINADA; no selector que cambie estado. Única acción: Ver detalle histórico. Un enlace compacto es suficiente al existir una sola acción. No column visibility: el contrato legacy no la soportaba.
Toolbar, summary, tabla compacta, alineaciones, paginación y empty state reutilizan clases Venti Next del baseline Material V2. Búsqueda sin coincidencias validada en navegador. Companies vacías verificadas por endpoint bajo contexto; no se confundieron con errores.
Error AJAX muestra alerta y estado Consulta fallida, separado del resultado vacío. No se provocaron errores de red artificiales en QA vivo.
Primera página de 1 registro validada; MULTIPAGE QA — PENDING DATASET.

## Historical Detail

Patrón label/value de Normal Purchase Detail, sin formulario disabled. Datos generales, estado operativo persistido separado de ELIMINADA, fecha de eliminación, líneas y totales. Se retiró la carga del JS legacy operativo; sólo retorno a papelera. No formularios ni botones de mutación.

## Supplier

Se muestra la relación histórica entregada por backend, sin imponer company al maestro. En ID 1 la relación no está disponible: mensaje explícito. No se consulta un proveedor global para completar el dato ni se inventa snapshot.

## Lines

Tres líneas existentes: StockItem IDs 8, 45, 48. Cantidades, precios, IGV y total_detail persistidos. SKU/descripción/unidad proceden de las relaciones existentes; no se certifican como snapshots históricos inmutables. Fallback descriptivo si falta relación. No consultas de disponibilidad, stock, precios actuales ni PriceList desde frontend.

## Currency / Totals

PEN. Total persistido 1120.00, IGV persistido 170.85. Líneas: 150.00, 500.00, 470.00. Sólo formato numérico, sin recalcular impuestos, sumas ni convertir moneda. Nulos se muestran como No registrado. No recepción expuesta ni reconciliada.

## Restore Boundary

RESTORE QA — PENDING. Restore/destroy/cambios de estado excluidos de esta fachada. No llamados durante QA. Endpoints backend intactos.

## Print / Documents Boundary

Print OUT OF SCOPE. No impresión, PDFs, imágenes de órdenes, adjuntos ni comprobantes abiertos. DOCUMENT SECURITY — BACKEND/INFRA OPEN. Sin allowlist para print/documentos.

## Cross-context

Prueba de controllers con contexto autenticado real, PDO en transacción READ ONLY y rollback. Audit::creating se canceló únicamente en el proceso temporal de QA; código productivo sin cambios. La consulta GET normal en navegador conserva su Audit técnico existente: no equivale a cero escrituras técnicas.

| Caso | Resultado |
| --- | --- |
| ID eliminado 1 en T1/C1/B1 y B8 | 200; 3 líneas; Venti Next cargado |
| ID 1 desde T1/C2 | 404 |
| ID 1 desde T3/C3 | 404 |
| ID activo 2 desde T1/C1 | 404 |
| ID 999999 en los cuatro contextos | 404 |

Listados comparados por IDs completos, no sólo conteos. Harness aislado de controller/binding: no sustituye una prueba HTTP completa de todos los middleware. Sesión de navegador: Owner EDESCE, T1/C1/B8 (OUTLET DE EDESCE). Sin nuevas sesiones/roles ni cambios de datos de negocio.

## Responsive

| Ancho | Listado overflow global | Detalle overflow global |
| --- | --- | --- |
| 320 | 0 | 0 |
| 375 | 0 | 0 |
| 390 | 0 | 0 |
| 576 | 0 | 0 |
| 768 | 0 | 0 |
| 992 | 0 | 0 |
| 1200 | 0 | 0 |
| 1440 | 0 | 0 |

Medición DOM en navegador Laravel y revisión visual desktop/móvil. Tablas con wrapper table-responsive y desplazamiento local. Sin errores de consola capturados. Búsqueda sin resultados y navegación al detalle verificadas.

## Visual Parity

VISUAL PARITY: heading, toolbar, summary, tabla, badges, espacios y detalle por secciones. VALID REAL CONTRACT DIFFERENCE: DataTables cliente, sin filtros adicionales ni Column Visibility; única acción read-only; relaciones históricas faltantes indicadas. No CSS local ni nuevo componente.

## Allowlist / Files

Sólo vistas: route name order.purchase.delete y URI exacta dashboard/ver/orden/compra/eliminada/{id}, porque detalle no tiene route name. Sin wildcard ni endpoints JSON.

- config/venti-next.php
- resources/views/orderPurchase/deleteGeneral.blade.php
- resources/views/orderPurchase/showDelete.blade.php
- public/js/orderPurchase/deleteGeneral.js (exclusivo de papelera)
- docs/REAL-NEXT-NORMAL-PURCHASE-TRASH-READONLY.md

## Validation / Remaining limits

Blade cache compile PASS; node --check PASS; git diff --check PASS. Backend UNCHANGED. No commit.
Restore QA PENDING; print OUT OF SCOPE; documents BACKEND/INFRA OPEN; multipage PENDING DATASET.
El accessor legacy OrderPurchase.status contiene una rama de recepciones que busca una orden activa por código: no se cubrió con una orden eliminada con recepción. No se certifica ese dataset ausente; no se modificó backend ni se inició una reauditoría de recepciones.
