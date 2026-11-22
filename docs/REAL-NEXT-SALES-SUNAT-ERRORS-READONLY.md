# REAL NEXT — SALES SUNAT ERRORS — READ-ONLY FRONTEND

2026-11-18. **SALES SUNAT ERRORS — FRONTEND INTEGRATED — READ PATH**.
Fuente: REAL-NEXT-UNKNOWN-SCREENS-CLASSIFICATION.md. Sin cambios backend, CSS,
datos, rutas o permisos. Sin commit. Worker y Sales List general no se reauditaron.

## Contract

PuntoVentaController::listarErrors usa SaleListOptions::data(true).
getSalesErrorAdmin conserva la consulta, serializer y paginación actuales.
SaleListGuard:errors y SaleListAccess protegen ambos GET. WorkerAccess verifica
usuario habilitado, no Platform Admin, tenant coincidente, company/branch activos
y memberships company_user/branch_user activos. No Owner bypass en frontend.

## Routes / Permissions

| Acción | Ruta / guard | Permiso | QA |
|---|---|---|---|
| Vista | GET /dashboard/listado/ventas/error/ — puntoVenta.listError; errors | list_puntoVenta + listErrors_puntoVenta | Render real PASS |
| Datos | GET /dashboard/get/data/sales/error/{page}; errors | ambos anteriores | Guard/controller READ ONLY PASS |
| Detalle | GET /dashboard/sales/{orderId}/details; read,route,orderId | list_puntoVenta + ownership moderno | Negativos PASS; positivo de bandeja PENDING DATASET |
| Imprimir/documentos | rutas print/document del backend existente | print_puntoVenta | Excluido, no abierto |
| Emitir/reintentar | POST /dashboard/facturador/generar; emit,input,order_id | manageDocuments_puntoVenta + generarBoleta_puntoVenta o generarFactura_puntoVenta | OUT OF SCOPE |
| Descartar error | POST /dashboard/sales/errors/{id}/discard; annul,route,id | annul_puntoVenta | OUT OF SCOPE |

Middleware de vista: web, auth, check.user.enabled, password.changed,
tenant.context, SaleListGuard:errors. No endpoints nuevos.
Sólo `puntoVenta.listError` añadido a config/venti-next.php. ALLOWLIST MATCH: YES.
Assets Venti existentes mediante layouts.appAdmin2.

## Scope

sales.tenant_id + company_id + branch_id; created_by no nulo y autor del tenant;
branch perteneciente a company. Sin viewBranch_puntoVenta se añade created_by=Auth::id().
viewBranch amplía actores del mismo local, no companies ni branches.
Snapshot X-Sales-Tenant/Company/Branch requerido por data y enviado también a detalle.

QA ejecutado con Auth/contexto reales en memoria y middleware/controller dentro de
transacción MySQL START TRANSACTION READ ONLY / ROLLBACK. No se presenta como QA
HTTP autenticado completo. Usuarios: Owner EDESCE (4), Owner Gamarra (7).

| Contexto usuario/T/C/B | IDs visibles actuales | Resultado |
|---|---|---|
| 4/1/1/1 | [] | 200 |
| 4/1/1/8 | [] | 200 |
| 4/1/2/2 | [] | 200 |
| 7/3/3/4 | [] | 200 |
| 4/1/1/4 branch ajena | — | 403 |
| 4/1/3/4 company ajena | — | 403 |
| 4/3/3/4 tenant ajeno | — | 403 |
| 7/1/1/1 tenant ajeno | — | 403 |

Snapshot 99/99/99:422. Pruebas aisladas del usuario en memoria (sin alterar roles DB):
retirar list_puntoVenta o listErrors_puntoVenta produce403; retirar viewBranch y pedir
created_by_id=7 como usuario4 produce422. SQL de alcance propio contiene created_by=?.
Esto no certifica un rol real adicional sin viewBranch con ventas positivas.

## Dataset

Revalidado: seis candidatos antes de scope, IDs completos **24,26,27,32,35,97**,
todos T1/C1/B1 y **created_by=NULL**. Por ello ningún candidato es visible.
No sustituir actor por Worker, no corregir registros, no relajar guard.
Detalle de esos seis IDs y 999999 bajo C1/B1:404 antes del controller.
Detalle positivo y comparación positiva entre actores/branches: PENDING DATASET.
No se crearon fixtures ni ventas en DB.

## Error Predicate

state_annulled=0; type_document IN (01,03); sunat_status='Error';
sunat_error_discarded_at IS NULL; orderBy created_at DESC. Todo pertenece a backend.
No filtrado de seguridad ni reconstrucción del predicado en JS.

## Filters

Conservados los controles legacy y sus parámetros: code, year, startDate, endDate,
customer_id, cash_box_id, cash_box_subtype_id. No se agregaron filtros nuevos.
Code busca ID mediante LIKE; las fechas y año se aplican a created_at (no date_sale).
Clientes conserva venta_directa y cotizacion_sin_cliente. Caja conserva pago_parcial.
Subtipo se muestra para cajas uses_subtypes. Lookups proceden de SaleListOptions.

Backend también admite created_by_id, document_type, dispatch_status y payment_status;
no se añadieron controles que la vista no tenía. No filtros de estado comercial nuevos.
Datos vacíos, búsqueda sin coincidencia, factura/boleta y cliente directo:200 vacío;
fecha inválida:422. IDs inexistentes string de caja/cliente:200 vacío sin ampliar scope.
El harness conserva evidencia detallada de lookups por contexto.

## Columns

Código, usuario registrador (snapshot; sin fallback Worker), fecha venta, cliente,
comprobante (tipo/serie/número), moneda, total, método pago, estado SUNAT,
mensaje SUNAT y acciones. Sólo campos del response. Estado SUNAT usa badge-danger;
no se convierte en estado comercial. Despacho se conserva en detalle cuando viene
en response. No se inventa is_annulled: ese campo no viene en este serializer.
Mensaje corto backend en tabla y completo en detalle, escapados con text().
Sin traducciones fiscales/códigos o peticiones al proveedor.

## Row Actions

Un trigger ellipsis por fila; clases next-row-actions, next-row-actions-trigger,
next-row-actions-menu y next-row-action-item-icon del baseline Material V2.
El shell porta el menú fuera de la tabla. Única opción de esta fachada: Ver detalle,
con data-ver_detalles/data-id y el mismo endpoint seguro. No se duplicó el detalle:
se reutiliza list-readonly.js y el modal de Sales List. La autorización no se amplía.
El mensaje completo vive en ese detalle; no se mantiene el popup legacy paralelo.

## Column Visibility

NOT APPLICABLE. El listado legacy no tenía columnas configurables. No se inventa
DataTables, switches, panel ni configuración. No hay disclosure independiente.

## SUNAT Actions Boundary

Legacy listError.js conserva sus handlers, pero deja de cargarse en esta vista.
No se copian sus mutaciones a list-readonly.js. Ningún otro Blade consumía ese script
al buscar referencias. No se elimina el archivo ni se modifican endpoints.
Fuera de esta fachada: reintentar, descartar, emitir, recuperar, consultar proveedor,
anular, notas de crédito, pagos, despacho y actualizar invoice. También se eliminan
los modales mutadores huérfanos de la vista. can_retry_invoice/can_discard_error=true
del response no sustituye permisos ni autoriza acciones en la fachada read-only.

## Documents Boundary

**DOCUMENT SECURITY — BACKEND/INFRA OPEN**. DOC-01 permanece abierto.
No links PDF/XML/CDR/print en modo errors, aunque el usuario tenga print=true.
No documentos abiertos, recuperados, certificados o expuestos como acciones de QA.
/api/sunat del shell (tipo de cambio) se interceptó en navegador; no se ejecutó
la solicitud real ni se cambió el shell. No se llamó SUNAT/Nubefact.

## Pagination / Empty / Errors

Servidor, 10 por página; currentPage,totalPages,startRecord,endRecord,
totalFilteredRecords intactos. Filtros/búsqueda reinician página1. Request abort/token
compartido evita respuestas AJAX fuera de orden. Página2 con dataset vacío:200 vacío.
**MULTIPAGE QA — PENDING DATASET**. No DataTables client-side.
Cuatro contextos reales muestran vacío; 403 controlado muestra error y no empty state.
422 backend preserva mensaje. El mensaje de error nunca se convierte en cero resultados.

## Responsive

320/375/390/576/768/992/1200/1440:PASS, scrollWidth=viewport. Tabla con scroll local.
Filtros, Select2, toolbar, acciones y modal read-only reutilizan patrones existentes.
53 comprobaciones de navegador; cero page errors y cero overflow observado.

Evidencia real: cuatro vistas renderizadas y JSON vacío actuales. Evidencia visual
con fila: respuesta aislada en navegador marcada QA VISUAL ONLY, id999999 y detalle
vacío controlado, nunca enviada a DB/backend. Sirve sólo para layout, mensaje escapado,
menú y modal, NO para afirmar venta real accesible. Capturas inspeccionadas375/1440.
Regresión del adaptador compartido: Sales List general mantiene endpoint
/dashboard/get/data/sales/1,11 columnas y vacío, sin page errors.

## Visual Parity

| Elemento | Comparación Material V2 / Sales List | Clasificación |
|---|---|---|
| Toolbar/búsqueda/filtros/summary/tabla/paginación | Clases next-list-* existentes | VISUAL PARITY |
| Row actions | Trigger único y menú portado compartido | VISUAL PARITY |
| Colores/spacing/badges/modal | Sistema aprobado;0 CSS | VISUAL PARITY |
| Campos y filtros de errores | Response/inputs propios del contrato | VALID REAL CONTRACT DIFFERENCE |
| Sin Columnas | No existe funcionalidad contractual | VALID REAL CONTRACT DIFFERENCE |
| Sin acciones financieras/documentos | Alcance read-only explícito | VALID REAL CONTRACT DIFFERENCE |

Sin UNJUSTIFIED VISUAL DEVIATION conocida tras revisión visual.

## Backend Sync Notes / Remaining QA

[OPEN] DOC-01: seguridad documentos backend/infra fuera de esta fase. Frontend continúa:Sí.
[PENDING DATASET] Positivo de bandeja, detalle positivo y multipágina: ningún candidato
con actor permitido. No es autorización para fabricar datos ni reparar históricos.
No nuevo finding fiscal ni cierre de deuda anterior. Backend sin modificaciones.

## Files / Verification

- config/venti-next.php: sólo nombre de vista listError.
- resources/views/puntoVenta/listError.blade.php: fachada read-only y patrones aprobados.
- public/js/puntoVenta/list-readonly.js: modo errors explícito; detalle compartido;
  modo general conserva filtros/render/documentos anteriores.
- docs/REAL-NEXT-SALES-SUNAT-ERRORS-READONLY.md.

node --check:PASS. PHP7.3 artisan view:cache:PASS. git diff --check:PASS.
No commit. No CSS nuevo. Ningún backend modificado.
Evidencia temporal local: %TEMP%/sunat-contract.json, sunat-render.php,
sunat-browser-qa.json, sunat-browser-qa.cjs, sunat-visual-375.png y sunat-visual-1440.png.
Los artefactos de QA no se versionaron ni contienen contraseñas.
