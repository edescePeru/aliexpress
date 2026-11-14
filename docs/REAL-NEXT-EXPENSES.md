# REAL NEXT — Gastos — Operational Frontend Integration

Fecha: 2026-11-13. Integración parcial deliberada por frontera de lectura. Sin commit ni operaciones financieras.

## Real Module

| Pantalla real | Controller | Model | Routes | Permission |
|---|---|---|---|---|
| Rendición de gastos (RRHH / Desc.-Contrib.) | ExpenseController | Expense, Worker, Bill | expense.index/create/edit/report | list/create/update/report_expense |
| Tipos de gastos | BillController | Bill | bill.* / tipos/gastos | permisos bill existentes, fuera de fase |
| Egresos de proveedores | ExpenseSupplierController | OrderPurchase + OrderService, Entry, Supplier | expenses.supplier.index | list_expenseSupplier |

No hay modelo ExpenseSupplier: es lectura combinada de compras/servicios. Expense no es CashMovement. Menú Rendición depende de enable_expense y capacidades de cada hijo; no se modificó.

## Routes

Prefijo `/dashboard`; auth/check.user.enabled/password.changed/tenant.context comunes. Los permisos no sustituyen filtros de datos.

## Permissions

| Route | Permission | Tenant | Company | Branch | Read/Mutation |
|---|---|---|---|---|---|
| GET rendicion/gastos/general + all/expenses | list_expense | Expense sin scope | No | No | Read, excluido |
| GET crear/gasto | create_expense | Worker sí con contexto; Bill global | No filtro Worker company | No | Read, integrado |
| POST expense/store | create_expense | No ownership en Expense/exists worker | No | No | Mutation, excluida |
| GET editar/gasto/{id} | update_expense | find Expense global | No | No | Read, excluido |
| POST expense/update | update_expense | find global | No | No | Mutation, excluida |
| POST expense/destroy | destroy_expense | find global | No | No | Mutation, excluida |
| GET reporte/rendicion/gastos | report_expense | Worker tenant; Expense posterior global | No | No | Read, excluido |
| GET generate/report/expense | report_expense | Expense global | No | No | Read, excluido |
| GET descargar/excel/rendicion/gastos | report_expense | Expense global | No | No | Export, excluido |
| GET egresos/proveedores/v2 + get/expenses/supplier/v2/{numberPage} | list_expenseSupplier | Mixto: compras scoped, OrderService global | Mixto | No garantía | Read, excluido |
| GET get/info/facturacion/expense/supplier/{invoice_id}/{type} | modify_expenseSupplier | Entry / OrderService según tipo; este último global | Mixto | No garantía | Read, excluido |
| POST expense/supplier/edit/facturacion | modify_expenseSupplier | No certificado | No certificado | No certificado | Mutation, excluida |
| GET exportar/reporte/egresos/proveedores | export_expenseSupplier | Mixto | Mixto | No garantía | Export, excluido |

## Scope

Expense no usa scopes ni contiene tenant_id/company_id/branch_id/user_id. getAllExpenses limita sólo fecha (últimos4meses); eager Worker tenant-scoped no restringe filas Expense y puede producir worker null. edit usa find global. **List / Edit / Report: BACKEND SYNC REQUIRED**.

Create es un GET convencional sin consultar gastos ni ejecutar transacciones. Worker usa BelongsToTenant/TenantScope; catálogo actual where id<>1, enable1. No se añade filtro company/branch: no existe en el contrato. Bill es catálogo legacy global de referencia, distinto de operaciones financieras. Sólo se integra render con sesión tenant válida; no se certifican Platform Admin ni contexto ausente (TenantScope no filtra cuando no hay tenant). No se declara aislamiento financiero completo por haber modernizado create.

Alternativa auditada: ExpenseSupplier combina OrderPurchase scoped con OrderService sin tenant/company scope. El selector Supplier tenant-scoped no limita las órdenes de servicio por defecto. No hay otra lista segura demostrada en esta familia; no se expandió a Cash/POS.

## Dataset

Lectura DB actual en transacción READ ONLY + ROLLBACK: expenses=0, IDs=[], bills=0. Columnas expenses: id,bill_id,worker_id,date_expense,week,total,created_at,updated_at. No moneda, estado, company, branch ni deleted_at: no inferirlos del trabajador.

| Contexto leído con modelo actual | Expense IDs | Trabajadores elegibles create |
|---|---|---|
| EDESCE T1/C1 | [] | [3,4,5] |
| EDESCE T1/C2 | [] | [3,4,5] |
| Gamarra T3/C3 | [] | [] |

Navegador Owner EDESCE / EDESCE CONSULTORA / Local Principal confirma workers4,3,5 y tipos vacíos. Gamarra/otra company contrastados por lectura de modelo, no por segunda sesión HTTP en esta fase. No prueba de aislamiento Expense con dataset vacío. Propio/ajeno: PENDING DATASET y camino excluido por evidencia estática. ID inexistente: find devuelve null; no se certifica404 ni se navega edit inseguro.

## Fields

Create: worker_id, bill_id, date_expense (d/m/Y), total (min0/step0.01), CSRF. Form id=formCreate, data-url=expense.store, multipart; botón type=button id=btn-submit dispara JS POST FormData. Preservado; no se pulsó Guardar.

Requests: bill_id required|exists:bills,id; worker_id en store required|exists:workers,id; date required|date_format:d/m/Y; total required|numeric|min:0. Update no valida expense_id en sus rules. Edit conserva expense_id hidden, fecha/total/bill; no worker editable. No se modificó edit ni backend.

## Filters

Listado: búsqueda, orden y paginación DataTables cliente, con ventana backend fija de4meses; worker asc/date desc. Columnas actuales colaborador/fecha/tipo/monto/acciones. Endpoint data contiene id,bill_id,worker_id,week,total,date_expense y relaciones. No nuevos filtros ni columnas.

Reporte legacy usa type/year/month/week/worker. Supplier V2 usa number_order/year/supplier/date_due/stateCredit/statePaid/month_order/startDate/endDate/credito; sólo identificado, sin modificar/ejecutar exports.

## Money Contract

total se devuelve sin recálculo; no campo moneda en Expense. Se conserva icono monetario legacy del input sin atribuir USD/PEN. No conversiones ni cambios de signo. Semana se calcula en controller desde fecha. No se cambió precisión.

## Cash Dependency

Create/Update de Expense escriben sólo Expense en transacción; no referencia directa a CashMovement, Entry, pagos ni saldos en esos actions ni observer Expense registrado localizado. No hay selector caja ni requisito de sesión de caja en este formulario. Esto no certifica ciclos financieros externos. CashRegister expense es otra familia, no integrada ni probada.

## Supplier / Categories

Expense no tiene supplier_id. Bill es tipo de gasto por description, catálogo separado sin scope; 0 filas actuales. BillController impide borrar un tipo si encuentra Expense asociados; no se integra su CRUD. Supplier pertenece a Egresos de proveedores, conserva scope tenant; no se cambia.

## Actions

| Action | Route | Permission | Read/Mutation | Included |
|---|---|---|---|---|
| Crear: abrir | expense.create | create_expense | Read | YES, sólo fachada GET |
| Guardar | expense.store | create_expense | Mutation | UI/hook preservado; QA OUT OF SCOPE |
| Listar | expense.index / all/expenses | list_expense | Read | NO, BACKEND SYNC REQUIRED |
| Editar: abrir / guardar | expense.edit / expense.update | update_expense | Read / Mutation | NO |
| Eliminar | expense.destroy | destroy_expense | Mutation | NO |
| Reporte / Excel | expense.report y endpoints anteriores | report_expense | Read / Export | NO |
| Detail / Show | No existe en Expense | — | — | N/A |
| Deleted / Restore / Annul | No existen en Expense | — | — | N/A |
| Facturación proveedor | endpoints anteriores | modify_expenseSupplier | Read / Mutation | NO |

Expense no usa SoftDeletes: destroy físico, distinto de anular. No documentos/adjuntos en sus campos; documentos proveedor no abiertos. **DOCUMENT SECURITY — BACKEND/INFRA OPEN**, sin reauditar DOC-01.

## Included Views

Únicamente expense.create: **FRONTEND INTEGRATED — MUTATION QA PENDING**. next-page-heading, toolbar/context-only, next-form-section con rows internas, next-list-toolbar/actions. Labels/IDs/Select2/Inputmask/CSRF/payload conservados; old y errores añadidos. Select2 width100% explícito para ocupar el grid sin CSS nuevo.

Se retiró en esta vista el enlace directo de vuelta al listado excluido; breadcrumb Rendición queda texto. No se alteraron rutas ni menú global ni se afirma que esta adaptación cierre el acceso legacy.

Allowlist exclusivamente expense.create; **ALLOWLIST MATCH YES**. Navegador confirma venti.min.css/venti-next.css. CSS nuevo0; JS funcional externo intacto. Create no autoriza ni certifica persistencia segura.

## Excluded Mutations

Store/update/delete/restore/annul/posting/caja/export/documentos no ejecutados. Sin ventas, gastos, movimientos ni cambios DB. Sin pruebas financieras extensas. Listado/edit/report/supplier requieren backend; no se solucionan filtrando JS. MULTIPAGE QA — PENDING DATASET, adicional al bloqueo contractual.

## Responsive

Create probado en320/375/390/576/768/992/1200/1440: scrollWidth<=innerWidth, **GLOBAL OVERFLOW NONE**. Capturas desktop1440/mobile375; Select2 vacío abre y Escape cierra; consola sin errores observados. List/Edit/Detail responsive NO APLICA a esta integración, no se declaran PASS.

Evidencias expenses-create-desktop.png / expenses-create-mobile.png en `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`.

## Backend Sync Notes

[OPEN] EXPENSE-01 — Expense carece de ownership/scopes en lista/find/reporte. Backend debe definir tenant/company/branch y tratar historia antes de integrar lectura. List/Edit frontend can continue: NO. Create GET puede continuar en contexto tenant; persistencia NO certificada.

[OPEN] Egresos proveedores: rama OrderService global, evidencia adicional a PUR-01 existente (sin nuevo finding duplicado). No hereda seguridad de compras scoped.

[OPEN] Dataset cero y tipos vacíos impiden QA positivo; no crear datos en esta fase. Workers compartidos entre companies del tenant corresponden al contrato actual, no company isolation. Sin tenant TenantScope no restringe: Platform Admin fuera del QA y pendiente de política.

Backend **UNCHANGED**. Checks view:cache PHP7.3 y git diff --check PASS; no JS funcional modificado. Sin commit.
