# REAL NEXT — Payment Deadlines

Fecha: 2026-11-12. Backend sin cambios; no commit. QA sin mutaciones.

## Contract Audit

Revisados PaymentDeadlineController, PaymentDeadline, PaymentDeadlineContextRequest y Store/Update/DeletePaymentDeadlineRequest, PaymentDeadlineReferences, tres Blades y tres JS, rutas y consumidores. Fuente visual: Configuration CRUD ya integrado en Invoice Categories y guía VENTI-NEXT-COMPONENT-PATTERNS. No se reabrió Finance Invoices.

## Routes

Prefijo /dashboard. Grupo auth/check.user.enabled/password.changed/tenant.context.

| Operación | Ruta | Permission | Tenant | Company | Branch | Read/Write |
|---|---|---|---|---|---|---|
| Listado | GET plazos/pagos (paymentDeadline.index) | list_paymentDeadline | contexto | contexto | No | Read |
| JSON | GET all/paymentDeadlines | list_paymentDeadline | contexto | contexto | No | Read |
| Create | GET crear/plazo/pago (paymentDeadline.create) | create_paymentDeadline | contexto | contexto | No | Read form |
| Store | POST paymentDeadline/store | create_paymentDeadline | contexto | contexto | No | Write, no ejecutado |
| Edit | GET editar/plazo/pago/{id} (paymentDeadline.edit) | update_paymentDeadline | contexto | contexto | No | Read form |
| Update | POST paymentDeadline/update | update_paymentDeadline | contexto | contexto | No | Write, no ejecutado |
| Desactivar | POST paymentDeadline/destroy | destroy_paymentDeadline | contexto | contexto | No | Write, no ejecutado |

## Permissions

Nombres exactos confirmados. Controller index usa getAllPermissions; crear condicionado por @can, editar/desactivar por permisos entregados en JSON hidden. Desactivar aparece solo para activos. Backend sigue validando permisos en rutas. No se eliminaron los controles de mutación del mantenedor: se conservaron, sin ejecutarlos en QA.

## Tenant / Company Scope

Global scope payment_company: tenantIdOrNull + companyIdOrNull; si falta uno, WHERE1=0. No branch. Prueba sin contexto devuelve0. findOrFail de edit respeta scope. Saving impide mover T/C y asigna IDs del contexto; hidden form_tenant_id/form_company_id se validan contra contexto actual, no son autoridad para elegir empresa. identifierRules comprueba ID T/C. No selector de sucursal agregado.

## Fields

| Campo | Contrato |
|---|---|
| description | requerido string max255, descripción/nombre del plazo |
| days | requerido entero 0..2147483647 |
| type | purchases o quotes |
| credit | boolean, valores0/1 |
| is_active | boolean, valores0/1 |

No código, orden ni otra descripción independiente. Conservados names, #type/#credit/#is_active, #formCreate/#formEdit, #btn-submit, #formDelete/#modalDelete, paymentDeadline_id, data-url, enctype y CSRF. Formularios siguen interceptados por JS mediante FormData POST; no se cambió method/action implícito ni URL contractual. Añadidos IDs descriptivos description/days para labels; old() y @error conservan validación de retorno. Boolean false se muestra como opción0 correctamente. JS create/edit intactos, incluidos mensajes backend, protección de doble envío y redirects.

## Status Contract

Activo/Inactivo son estados reales. Listado JSON incluye is_active pero no used/historical: no se inventa badge En uso. Inactivo no se presenta como eliminado. Selectable(currentId) permite activos + selección histórica actual dentro del mismo scope.

## Historical Preservation

PaymentDeadlineReferences::used recorre tablas reales que tengan payment_deadline_id, incluyendo históricos y soft-deleted, sin scopes de otros modelos. Si usado, saving bloquea cambios days/type/credit y permite description/is_active. Mensaje backend se muestra por handler existente; no se duplica regla en JS ni se decide en frontend si está usado. Edit mantiene aviso informativo.

## Delete / Disable Semantics

DELETE CONTRACT: deleting del modelo rechaza borrado físico siempre, utilizado o no. No SoftDeletes.
DISABLE CONTRACT: destroy hace lockForUpdate, is_active=false y save; nunca delete. También un plazo no utilizado se desactiva. Un inactivo conserva registro/historial; UI no ofrece nuevamente Desactivar. Backend repetiría asignaciónfalse si se invoca y pasa validación. Update puede modificar estado conforme al contrato actual. Ninguna de estas operaciones se ejecutó.
HISTORICAL PRESERVATION — CONTRACT PRESERVED por inspección de código; MUTATION QA PENDING.

## Consumers

Inventario SQL de payment_deadline_id: order_purchase_finances, order_purchases, order_services, proformas, quotes, supplier_credits. Consumidores de lectura/selección encontrados: OrderPurchaseController, OrderPurchaseFinanceController, OrderServiceController, QuoteController, QuoteSaleController, ProformaController, EntryController/EntryInventoryController, SupplierCredit/ExpenseSupplier. InvoiceController contiene import PaymentDeadline, sin uso directo encontrado; no asumir que factura financiera usa selector. Proveedores se relacionan mediante crédito/órdenes, no se inventa campo Supplier. Ventas/cotizaciones: QuoteSale participa; no se afirma uso directo de Sale donde no existe referencia.

Hay selectores modernos con selectable() y selectable(currentId), por ejemplo OrderPurchaseController1962/2207. Otros antiguos usan where(type)->get sin selectable (Quote, Proforma, OrderService, OrderPurchaseFinance y caminos legacy de OrderPurchase/QuoteSale): scope T/C sigue aplicado, pero selección de inactivos necesita revisión por backend/consumidor. Ninguno modificado.

## Visual Mapping

Configuration CRUD — REUSE WITH CONTRACT ADAPTATION. next-page-heading/toolbar, next-toolbar-context/actions, next-operational-list, next-list-summary, next-list-pagination, next-data-table y next-form-section. Formularios comparten gutters y ejes; selects Select2 width100% por configuración del plugin, sin CSS nuevo. Tabla descripción izquierda, días/crédito/estado/acciones centro. Dropdown de acciones mantiene data-delete/data-name y permiso. Modal next-aux-modal mantiene cancelación y confirmación explícita. Se corrigió breadcrumb edit que apuntaba a quality.index.

DataTables mantiene búsqueda, paginación, orden original, endpoint y dataSrc. Resumen text-wrap directamente en dataTables_info evita nowrap a320; comprobado también con carga inicial vacía. No se reemplazó lógica de negocio. CSS nuevo0; vendor/global intactos.

ALLOWLIST MATCH: YES para paymentDeadline.index/create/edit exclusivamente. DOM confirma venti.min.css y venti-next.css.

## Responsive QA

Listado/create/edit:320,375,390,576,768,992,1200,1440; scrollWidth <= innerWidth tras corregir resumen listado320. Tabla permite scroll local; formularios sin overflow. Capturas desktop edit y móvil listado. Select2 probado con valores reales quotes/credit0/activo1; ancho corregido al100% para resize. Modal abierto y cancelado sin confirmar. Consola sin errores observados. node --check index.js, artisan view:cache PHP7.3 y git diff --check aprobados. Estados inactivos y paginación con >10registros pendientes por dataset; no se fabricaron.

## Cross-context QA

Dataset real2plazos: ID1 T1/C1 activo; ID2 T1/C2 activo. Ambos EN EFECTIVO, days0, typequotes, creditfalse. Sin registros Gamarra ni inactivos.

Harness PHP temporal fuera del repo, SET TRANSACTION READ ONLY/rollback, Auth::setUser, sesión array efímera e InitializeTenantContext. Controllers getPaymentDeadlines/create/edit reales. No POST/login persistido.

| Cuenta | T/C/B | JSON | Create | Edit1 | Edit2 | Inexistente99999999 |
|---|---|---|---|---|---|---|
| Owner EDESCE | 1/1/1 | ID1 | Vista | Permite | ModelNotFound | ModelNotFound |
| Owner EDESCE | 1/2/2 | ID2 | Vista | ModelNotFound | Permite | ModelNotFound |
| Owner EDESCE | 1/5/6 | vacío | Vista | ModelNotFound | ModelNotFound | ModelNotFound |
| Owner Gamarra | 3/3/4 | vacío | Vista | ModelNotFound | ModelNotFound | ModelNotFound |

Ambas cuentas tienen los4permisos. ModelNotFound es el contrato de404; no confundir prueba directa con medición HTTP. Navegador real EDESCE C1 verificó listado/create/editID1 y modal. Gamarra validado en harness, no loginUI. Sin contexto count0. Tenant / Company Isolation — QA PASSED para matriz disponible.

## Mutation Exclusions

No crear/guardar/editar/eliminar/desactivar. No submit ni confirmación de modal. Datos comerciales intactos. MUTATION QA PENDING; validaciones históricas auditadas por código, no mediante escrituras.

## Backend Sync Notes

[OPEN]
Point: selectores legacy pueden ofrecer inactivos.
File / endpoint: consumidores listados en Consumers con where(type)->get sin selectable.
Impact: desactivado puede seguir apareciendo como opción nueva en caminos antiguos; no altera aislamientoT/C.
What backend needs to define or correct: aplicar política activos + histórico seleccionado consistentemente en cada consumidor.
Frontend can continue: Yes, mantenedor correcto; consumidores fuera de alcance.

No otros defectos bloqueantes encontrados en el contrato del mantenedor. Mutaciones siguen QA PENDING, no certificadas.

## Revisit Triggers

Cambios de scope o permisos; nuevos consumidores payment_deadline_id; cambios de política historical/disable; dataset inactivo/Gamarra/multipágina; autorización explícita para QA de mutaciones. Revalidar errores reales de plazo usado sin duplicar reglas en frontend.

## Files Changed

- config/venti-next.php
- resources/views/paymentDeadline/index.blade.php
- resources/views/paymentDeadline/create.blade.php
- resources/views/paymentDeadline/edit.blade.php
- public/js/paymentDeadline/index.js
- docs/REAL-NEXT-PAYMENT-DEADLINES.md

List / Create / Edit — FRONTEND INTEGRATED.
Tenant / Company Isolation — QA PASSED.
Historical Preservation — CONTRACT PRESERVED.
Mutations — QA PENDING.
Backend — UNCHANGED. No commit.
