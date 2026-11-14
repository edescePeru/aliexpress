# REAL NEXT — Normal Purchases — Read-only Integration

Fecha: 2026-11-12. Fuente funcional: aliexpress. Baseline: Advanced Operational List / Material List V2, Sales, User Profit y Surface Spacing Standard. Ganancias por usuario no se modifica.

## Contract Audit

OrderPurchaseController::indexNormalV2 carga permissions (getPermissionsViaRoles), años de órdenes normales, proveedores del tenant y estados. getDataOrderNormal filtra type=n y pagina 10 registros, ordenados por date_order DESC. showOrderPurchaseNormal resuelve NormalPurchaseGuard::order y detalles por order_purchase_id, con material.unitMeasure y stockItem. No se modifica controller, modelos, middleware, rutas, consultas, importes ni documentos.

## Routes

Prefijo /dashboard. Todas las rutas incluidas son GET|HEAD con web, auth, check.user.enabled, password.changed, tenant.context y permission:list_orderPurchaseNormal.

| Ruta | Tenant | Company | Branch | Permission | Guard | Read/Write |
| --- | --- | --- | --- | --- | --- | --- |
| listado/ordenes/compra/normal/v2 (order.purchase.normal.indexV2) | explícito | explícita | sin filtro | list_orderPurchaseNormal | ScopedPurchaseCompany | Read |
| get/data/orders/normal/v2/{numberPage} (sin nombre) | explícito | explícita | sin filtro | list_orderPurchaseNormal | ScopedPurchaseCompany, type=n | Read |
| ver/orden/compra/normal/{id} (sin nombre) | explícito | explícita | sin filtro | list_orderPurchaseNormal | NormalPurchaseGuard::order, ScopedPurchaseCompany | Read |

Excluidas: Express, General, Solicitudes, OrderPurchaseFinance/compras financieras, servicios, recepción/Entry, regularización, pérdidas, eliminadas, entradas, reportes y exportaciones generales. No se amplía su allowlist ni readiness.

## Permissions

Las tres rutas requieren list_orderPurchaseNormal. La fila conserva el contrato permissions entregado por indexNormalV2 para mostrar Ver. La fuente actual es getPermissionsViaRoles (no incluye permisos directos fuera de roles); backend sigue siendo autoridad. Los enlaces de navegación global del ERP no se modifican.

## ScopedPurchaseCompany

OrderPurchase y OrderPurchaseDetail usan el scope purchase_company: tenant_id y company_id explícitos. Contexto ausente => whereRaw(1 = 0). SoftDeletes excluye eliminadas. Las reglas saving/deleting y atribución de actores se mantienen sin cambios.

## NormalPurchaseGuard

order(id) aplica type=n y findOrFail sobre el modelo scoped. users() restringe tenant y membresía activa company. header/context/products/editable protegen mutaciones, fuera de esta fase: cabeceras X-Purchase-Tenant/Company, proveedor tenant, quote company, payment deadline, StockItem habilitado, correspondencia material y bloqueo por recepciones. No se ejecutan ni alteran.

## Tenant / Company Scope

El contrato de órdenes/detalles es explícito tenant/company. Proveedores son catálogo tenant (Supplier::BelongsToTenant), no exclusivamente company. Los creadores/modificadores/aprobador son relaciones limitadas al tenant. No se confunde aprobador histórico con created_by. Relaciones históricas inválidas/no disponibles se muestran sin inventar valores.

## Branch Non-Contract

NOT APPLICABLE / COMPANY LEVEL. TenantContext exige contexto operativo válido, incluida sucursal, pero la consulta de órdenes no filtra branch. Ver las mismas órdenes en Local Principal y OUTLET de company 1 es esperado. No afirmar BRANCH SAFE ni añadir branch/warehouse a filtros.

## Filters

Se conservan exactamente year, supplier, code (LIKE code), quote (LIKE observation, aunque su etiqueta histórica sea Cotización), state (status_order), deliveryDate (date_arrival), startDate/endDate (date_order, d/m/Y). Si falta un extremo, backend no aplica rango. Buscar vuelve a página 1. No filtros nuevos. Datepicker usa .wrapper/orientation bottom para anclaje correcto y Select2 ancho 100%. Se conserva bootstrap-datepicker y los IDs.

## List Mapping

REUSE WITH CONTRACT ADAPTATION: next-page-heading/toolbar, next-form-section, filtros plegables, next-list-summary, next-data-table, next-row-actions y paginación. Columnas: ID, código, fecha orden/llegada, observación, proveedor, creado por, moneda, total, tipo, estado, acciones. Códigos/fechas/moneda/estado/acciones centrados, textos izquierda, total derecha. Datos textuales se insertan con textContent; badges recibidos del backend conservan etiquetas y usan clases semánticas existentes.

Se preserva data/pagination: currentPage, totalPages, startRecord, endRecord, totalRecords, totalFilteredRecords. Vacío: 0 a 0, sin inventar registros. Se corrige enlace final heredado (totalPages en vez del contador del bucle) y preventDefault de paginación. No reemplazo de estrategia.

## Detail Mapping

Datos generales: proveedor, fechas, estado, creador/modificador, aprobador previo si existe, forma de pago, cotización proveedor, cotización vinculada y observación. Tabla de lectura con cantidades, precio C/IGV, IGV y total almacenados; resumen moneda/IGV/total. Sin formularios de escritura ni JS de mutación.

Se elimina de esta vista la dependencia showNormal.js: contenía callbacks de edición/eliminación Express y recálculos 18%, ajenos a una lectura normal. El archivo permanece intacto para showDelete y otros consumidores. Tampoco se carga companyContext.js, que solo añade headers a mutaciones. Se eliminan controles/modal de cambio de estado y templates mutadores de esta superficie.

No se reconstruye precio sin IGV dividiendo por 1.18, subtotal por resta ni total faltante por cantidad*precio. Son cálculos heredados de Blade/JS, no campos almacenados entregados. Se muestran valores persistidos; null = No registrado. Esto se documenta como dependencia backend si se necesitan importes derivados.

## Statuses

status_order: stand_by => Pendiente; send => Enviado; pick_up => Recogido. Filtro backend: PENDIENTES/ENVIADAS/RECOGIDAS. No se inventan aprobada, parcial o anulada. Estos estados de orden no certifican recepción.

## Materials / StockItems

Se priorizan stockItem.id/sku/display_name cuando existe relación; fallback histórico material_id/material.code/full_description. Unidad desde material.unitMeasure, ya cargada por controller. No se reconstruyen variantes ni presentaciones. Se identifica StockItem frente a Material histórico. Cantidad/precio/IGV/total son los de la línea, sin consulta de stock actual ni pricing.

## Receipt Boundary

PURCHASE RECEIPT MUTATION — OUT OF SCOPE. No recibir, crear Entry, regularizar ni ajustar cantidades. El listado no entrega un estado de recepción separado. El accessor OrderPurchase::status calcula 0/1/2 desde Entry por código y detalles; no se incorpora ni se interpreta como status_order. ReceiptEntries/receiptDetails no se consumen.

## Action Matrix

| Acción | Permission | Backend Guard | Read/Mutation | Estado en esta fase |
| --- | --- | --- | --- | --- |
| Ver/listar | list_orderPurchaseNormal | type=n + tenant/company | Read | Integrado |
| Volver al listado | list_orderPurchaseNormal | scope | Read | Integrado |
| Imprimir | list_orderPurchaseNormal | scope modelo, endpoint compartido | GET documental | DOCUMENT QA PENDING, no expuesto |
| Crear | create_orderPurchaseNormal | NormalPurchaseGuard/header/items | Mutation | Fuera |
| Editar/cambiar estado | update_orderPurchaseNormal | guard/context/order/editable según acción | Mutation | Fuera |
| Eliminar/restaurar | destroy_orderPurchaseNormal | guard/context según acción | Mutation | Fuera |
| Recepción/regularizar | no auditado en esta fase | familia separada | Mutation | Fuera |

## Document Risk

printOrderPurchase consulta OrderPurchase scoped, pero usa first() en lugar de findOrFail y luego desreferencia supplier_id; no restringe type=n. Lee SupplierAccount, settings/branding y renderiza exports.entryPurchaseV2 con PDF::stream. No aparece guardado de PDF en ese método, pero no se certifica toda la cadena documental ni su respuesta cross-context. DOCUMENT QA PENDING. No se navega, genera ni descarga PDF.

## Allowlist / Visual Mapping

ALLOWLIST MATCH: YES en ambas vistas, comprobado en DOM: venti.min.css y venti-next.css. Lista por nombre order.purchase.normal.indexV2. Detalle sin nombre: nueva allowlist route_uris con coincidencia exacta del URI registrado dashboard/ver/orden/compra/normal/{id}, solo GET, evaluada por layout. No wildcard de familia ni cambios routes/web.php. 0 CSS nuevo; se retiran estilos inline legacy (colores, tipografía y top forzado de calendario).

## Responsive QA

| Ancho | Lista con filtros abiertos | Detalle |
| --- | --- | --- |
| 320 | sin overflow global, tabla local | sin overflow global |
| 375 | sin overflow global, tabla local | sin overflow global |
| 390 | sin overflow global, tabla local | sin overflow global |
| 576 | sin overflow global, tabla local | sin overflow global |
| 768 | sin overflow global | sin overflow global |
| 992 | sin overflow global | sin overflow global |
| 1200 | sin overflow global | sin overflow global |
| 1440 | sin overflow global | sin overflow global |

A 320 se detectó buscador demasiado comprimido por acciones; se adaptó con grid Bootstrap y acciones flex-wrap compartidas. Calendario 4 px debajo, dentro de viewport (right 255 en 320). Tabla permite desplazamiento local. Detalle desktop inspeccionado visualmente; controles de escritura ausentes. Sin CSS nuevo.

## Cross-context QA

Sin POST y sin escrituras comerciales. Navegador: sesión existente Owner EDESCE, tenant 1/company 1/branch 1. Listado 3 órdenes (4,3,2), búsqueda OC-00004 => 1; estado stand_by => orden 2; rango 16/10/2026 => orden 4; código sin coincidencia => vacío 0 a 0. Detalle 4 => 3 StockItems, total PEN 2400, IGV 366.09. No errores JS observados.

Para conservar prohibición POST, matriz adicional mediante proceso Laravel PHP 7.3 con cuentas reales, Auth::setUser, sesión efímera array, InitializeTenantContext, comprobación can(list_orderPurchaseNormal), controller/guard reales y transacción SQL READ ONLY + rollback. No es login HTTP ni QA visual de Gamarra; prueba de integración del read path con sus roles/contextos autorizados. No se modifica sesión del navegador.

| Cuenta / rol | Tenant | Company | Branch | Lista | Detalle 4/3/2 | Inexistente 999999999 |
| --- | --- | --- | --- | --- | --- | --- |
| Owner EDESCE / owner | 1 | 1 | 1 | 4,3,2 | accesibles, 3/3/2 líneas | rechazo findOrFail |
| Owner EDESCE / owner | 1 | 1 | 8 | 4,3,2 | accesibles | rechazo findOrFail |
| Owner EDESCE / owner | 1 | 2 | 2 | vacía | rechazo findOrFail | rechazo findOrFail |
| Owner Gamarra / owner | 3 | 3 | 4 | vacía | rechazo findOrFail | rechazo findOrFail |

También company 5/branches 6 y 7 vacías y rechazan IDs company 1. Sin contexto: OrderPurchase::count()=0. ModelNotFoundException equivale a rechazo 404 en HTTP; matriz no se presenta como captura de HTTP. No se asume branch-safe. Dataset de 3 registros solo permite una página real; navegación multipágina no validada con DB, no se crean órdenes para ampliarlo.

## Backend Sync Notes

[OPEN]
Point: importes derivados del detalle.
File / endpoint: showOrderPurchaseNormal / OrderPurchaseDetail.
Impact: no existe campo entregado para precio sin IGV/subtotal; legacy asumía 18% y reconstruía total null.
What backend needs to define or correct: suministrar cálculo autoritativo si esos importes son requeridos.
Frontend can continue: Yes, muestra únicamente importes almacenados.

[OPEN]
Point: impresión y documentos.
File / endpoint: printOrderPurchase / exports.entryPurchaseV2.
Impact: no certificado read-only/end-to-end; ID ajeno/inexistente usa first() y desreferencia posterior.
What backend needs to define or correct: garantizar rechazo consistente y auditar cadena documental antes de habilitar acción.
Frontend can continue: Yes, impresión excluida.

[OPEN]
Point: validación de filtros/página y asociación histórica.
File / endpoint: getDataOrderNormal; OrderPurchase::quote/supplier/createdUser.
Impact: fechas se parsean sin Request dedicado; quote filtra observation; relaciones históricas tenant-scoped no prueban por sí solas pertenencia company. No evidencia de fuga en órdenes probadas.
What backend needs to define or correct: formalizar validación y política histórica de relaciones si cambia el contrato; mantener coherencia company al escribir.
Frontend can continue: Yes, no añade scopes ni inventa datos.

## Revisit Triggers

Cambios en scopes, permisos, estados, branch contract, filtros/response, Material/StockItem/presentation, importes autoritativos, recepción o documentos. Auditar cada familia aparte. Multipágina real pendiente de dataset suficiente; QA visual de Gamarra con login HTTP queda pendiente por restricción no POST.

## Files / Validation / Final Status

Modificados: config/venti-next.php; resources/views/layouts/appAdmin2.blade.php (allowlist visual exacta); resources/views/orderPurchase/normal_v2.blade.php; resources/views/orderPurchase/showNormal.blade.php; public/js/orderPurchase/normalV2.js; este documento. Sin CSS, backend ni otros consumidores modificados. Sin commit.

Node --check, PHP 7.3 artisan view:cache y git diff --check PASS.

- Normal Purchase List — FRONTEND INTEGRATED — READ PATH
- Normal Purchase Detail — FRONTEND INTEGRATED — READ PATH
- Tenant / Company Isolation — QA PASSED (integración read-only; alcance anterior)
- Branch Scope — NOT APPLICABLE / COMPANY LEVEL
- Receipt / Mutations — OUT OF SCOPE
- Other Purchase Families — NOT INCLUDED
- Documents — DOCUMENT QA PENDING
- Backend — UNCHANGED

QA adicional: render de paginación con respuestas controladas en Node (100 registros, página 1 y 5) PASS; enlace final 10 correcto. Esta prueba no sustituye multipágina con datos reales.
