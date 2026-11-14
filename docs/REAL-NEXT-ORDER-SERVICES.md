# REAL NEXT — Órdenes de Servicio

Fecha: 2026-11-13. Auditoría focalizada. **Ninguna superficie apta para integración en esta fase**. Backend/frontend/datos sin cambios; sin commit.

## Surface Matrix

Prefijo de todas las rutas: `/dashboard`. Read indica propósito; se anotan GET con escritura Audit.

| Surface | Route | Permission | Scope | Read/Mutation | Decision |
|---|---|---|---|---|---|
| Main list | ordenes/servicio; listar/ordenes/servicio; all/order/services | list_orderService | OrderService global | Read | BACKEND SYNC REQUIRED |
| Detail | ver/orden/servicio/{id} | list_orderService | find global + usuarios globales | GET con Audit::create | BACKEND SYNC REQUIRED |
| Create | ordenes/servicio/crear | create_orderService | User::all global; numeración max global | Read | BACKEND SYNC REQUIRED |
| Edit | editar/orden/service/{id} | update_orderService | find global + usuarios globales | GET con Audit::create | BACKEND SYNC REQUIRED |
| Servicios | servicios; all/order/services/regularize | list_service | OrderService global regularize=r | Read | BACKEND SYNC REQUIRED |
| Regularizadas list | ordenes/servicio/regularizadas | list_orderPurchaseNormal | AJAX global | Read | BACKEND SYNC REQUIRED |
| Regularizadas data | all/order/service/regularize | Sin permiso específico | Global regularize=r | Read | BACKEND SYNC REQUIRED |
| Deleted list | ordenes/servicio/anuladas | list_orderPurchaseNormal | AJAX global | Read | BACKEND SYNC REQUIRED |
| Deleted data | all/order/service/deleted | Sin permiso específico | onlyTrashed global | Read | BACKEND SYNC REQUIRED |
| Lost list | ordenes/servicio/perdidas | list_orderPurchaseNormal | AJAX global | Read | BACKEND SYNC REQUIRED |
| Lost data | all/order/service/lost | Sin permiso específico | Códigos globales withTrashed | GET con Audit::create | BACKEND SYNC REQUIRED |
| Regularize form | ingresar/orden/servicio/{id} | regularize_orderService | find global / User::all | Read | BACKEND SYNC REQUIRED |
| Regularize auto form | regularizar/automaticamente/entrada/servicio/{entry_id} | create_orderService | Dependiente Entry; User::all y max global | Read | OUT OF SCOPE |
| Store / update | order/service/store; order/service/update | create_orderService / update_orderService | No certificado | Mutation | OUT OF SCOPE |
| Delete / detail mutations | destroy/order/service/{id}; update/detail/order/service/{idDetail}; destroy/detail/order/service/{idDetail} | delete_orderService / update_orderService / delete_orderService | No certificado | Mutation | OUT OF SCOPE |
| Regularize POST | order/service/regularize; store/regularize/order/service | regularize_orderService / create_orderService | No certificado | Mutation | OUT OF SCOPE |
| Print | imprimir/orden/servicio/{id} | list_orderService | find global | Generación documento | OUT OF SCOPE |

## Routes

Controller real `OrderServiceController`; modelo `OrderService`, detalles `OrderServiceDetail`. Blade: indexOrderService/listOrderService/createOrderService/editOrderService/showOrderService/indexService/indexRegularize/indexDeleted/indexLost/regularizeOrderService/regularizeAutoEntryService.

Nombres: order.service.index, list.order.service.index, order.service.create, service.index, order.service.store/update, show.order.service (es formulario de regularización), order.service.regularize, order.service.list.regularize/deleted/lost, order.service.regularize.store. AJAX, detalle simple y edición no tienen nombres declarados en el bloque auditado. No rutas nuevas ni eliminación de rutas.

## Permissions

Middleware compartido auth/check.user.enabled/password.changed/tenant.context. No equivale a permiso por operación ni ownership. Los tres AJAX singulares regularize/deleted/lost no declaran permission, mientras sus vistas usan list_orderPurchaseNormal. Conservar esa evidencia, no asumir que el permiso de vista protege acceso directo al JSON. No cambios de roles ni Owner bypass asumido. QA HTTP negativo no ejecutado: evidencia estática suficiente para excluir.

## Scope

OrderService usa SoftDeletes, no TenantScope ni company scope. DB actual carece de tenant_id/company_id/branch_id. Main getAllOrderService devuelve all activo orden created_at DESC; Supplier scoped no limita parent OrderService. Detail/edit/regularize consultan find(id), sin ownership; relación approved_user tampoco contextual. OrderServiceDetail no tiene scope propio: where parentID sólo es seguro si parent fue autorizado, lo que no ocurre aquí.

Create no tiene binding, pero consulta User::all y renderiza usuarios en approved_by; además calcula código con max(id) global including trashed. Por eso tampoco se integra. No filtrar opciones desde JS ni restaurar contratos viejos. No se reauditaron compras/finanzas completos.

## Dataset

Relectura SQL/modelo en transacción **START TRANSACTION READ ONLY**, terminada con ROLLBACK:

- order_services: 0 físicos, 0 activos, 0 eliminados; IDs [].
- order_service_details: 0.
- Contextos T1/C1 EDESCE, T1/C2 EDESCE y T3/C3 Gamarra: IDs [] en modelo actual.
- Tabla sí incluye supplier_id, currency_order/compra/venta, total/igv, date_order/delivery/invoice, regularize, state_paid, deleted_at; no ownership T/C/B.
- No hay valores positivos de moneda/total/fecha/proveedor/estado que reportar. No se heredaron conteos previos.
- User global contiene8 filas: tenant NULL1, tenant1=4, tenant2=1, tenant3=1, tenant4=1. No se copiaron nombres ni correos. Confirma que User::all no es catálogo contextual.

Propio/ajeno/otra company: PENDING DATASET, no isolation PASS. Inexistente: find devuelve null y siguiente acceso $order->id no implementa404 explícito; no se navega GET inseguro para provocar error. Sin sesiones UI nuevas ni descarga de datos de órdenes ajenas.

## Fields

Create real: service_order readonly, date_order, date_delivery, observation, quote_supplier, supplier_id, approved_by, payment_deadline_id, currency_order y regularize_order (switches), filas de servicio/unidad/cantidad/precio. JS FormData añade items JSON, subtotal_send/taxes_send/total_send/state/regularize. Edit añade identidad de orden y acciones de detalle. Ningún campo/hook/payload fue modificado.

StoreOrderServiceRequest (también update): service_order required; service_condition/observation/quote_supplier nullable|string. No se adoptan reglas financieras nuevas ni se certifica validación de todos los datos enviados.

## Filters

DataTables cliente: búsqueda/orden/length/paginación; AJAX de listas según matriz. Main no filtra regularize (where comentado). No filtros tenant/company. Columnas reales incluyen código, fecha orden/entrega, observación, proveedor, aprobado por, moneda, total y acciones; Lost sólo códigos faltantes. No se agregaron columnas/filtros/badges.

MULTIPAGE QA — PENDING DATASET; además listado excluido por scope. No evidencia nueva de GENERAL-01: aquí DataTables devuelve colección, no se ejecutó cálculo paginado tipo General V2.

## States

regularize usa r/nr. Servicios/regularizadas filtran r. Deleted usa onlyTrashed: no homogeneizar soft delete con estado comercial de compras. Lost calcula huecos de secuencia OS- a partir de códigos withTrashed, no es un flag “perdido” ni una acción marcar lost. No ruta restore encontrada en esta familia. state_paid existe en schema; sin valores actuales para certificar semántica adicional.

## Supplier / Services

Supplier::all hereda scope tenant actual; no agregar company frontend. PaymentDeadline tiene contrato contextual propio y se consulta type=purchases; no se modifica ni se reabre PAY-01. UnitMeasure aporta unidades.

No catálogo Service separado identificado en este flujo: indexServices lista OrderService regularize=r; detalle contiene service como texto y unit. No confundir menú Servicios con un CRUD independiente de Service.

## Money

Currency switch preservado conceptualmente: JS envía state y backend asigna PEN si string true; USD si no. Store consulta obtenerTipoCambio y usa precioCompra/precioVenta. NO se ejecutó ni llamó proveedor externo. JS actual calcula detalles/totales y usa1.18; se identifica, no se reescribe ni se certifica. Backend recibe taxes_send/total_send. POSTING / RECEIPT OUT OF SCOPE: no regularización, Entry, SupplierCredit, pagos ni cambios de inventario.

## Included Views

**Ninguna**: todas las candidatas tienen bloqueo demostrado de lectura/lookup. No modificar fachada sin contrato seguro. Allowlist SIN CAMBIOS; no se agregó carpeta, view ni AJAX. No simulación de scope en frontend.

## Excluded Mutations

Todas: store/update/destroy/detalles/regularize/Entry y documentos. Show/edit/Lost también escriben Audit en GET: no invocados. DOCUMENT SECURITY — BACKEND/INFRA OPEN; no abrir PDFs, facturas, imágenes ni reauditar DOC-01.

## Responsive

NO APLICA: ninguna vista integrada o modificada. No afirmar PASS de matriz no ejecutada. CSS nuevo0; JS/Blade/backend intactos. git diff --check PASS para documentación. No compilación Blade necesaria sin cambios de vistas.

## Backend Sync Notes

[OPEN] SERVICE-01 (trazabilidad de PUR-01): OrderServiceController list/find/withTrashed sin ownership y modelo sin scope. Impacto: main/detail/edit/services/deleted/lost no aprobables. DEV debe definir frontera T/C/B e historia y autorizar parent antes de detalles. Frontend can continue: NO. Se amplía evidencia PUR-01, no se duplica inventario.

[OPEN] SERVICE-02 (trazabilidad de USER-01): create/show/edit/regularize consultan User::all; create expone opciones approved_by de distintos tenants. DEV debe proveer aprobadores autorizados por contexto; no filtro JS. Frontend can continue: NO. Se amplía causa de lookup global ya registrada, no duplica inventario.

[OPEN] SERVICE-03: AJAX regularize/deleted/lost sin permiso de acción, vistas con list_orderPurchaseNormal. DEV debe alinear gate correcto por superficie y validar acceso directo. Backend unchanged; no prueba destructiva/HTTP requerida para constatar declaración de rutas.

## Estado final

Main / Detail / Create / Edit / Services / Deleted / Lost: **BACKEND SYNC REQUIRED**.
Regularization / Posting: **OUT OF SCOPE** (GET regularize además bloqueado).
Documents: **BACKEND/INFRA OPEN**.
Backend: **UNCHANGED**. Frontend: **UNCHANGED**. Sin commit.
