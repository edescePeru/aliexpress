## READY NOW

Barrido focalizado — 2026-11-18, HEAD `50a0d5c`. Fuente: allowlist actual, Roadmaps C/D/E, backlog DEV y contratos HEAD. Sin cambios de aplicación, tests, navegación con efectos secundarios ni mutaciones. Readiness es autorización para iniciar integración, no certificación de QA funcional. Las cinco superficies propuestas siguen fuera de allowlist.

| Candidato | Estado | Contrato mínimo y límite |
|---|---|---|
| Cuentas de proveedores | FRONTEND WITH TECH DEBT | `GET /dashboard/registrar/cuentas/proveedores/{supplier_id}`, enlace desde Proveedores. `SupplierAccountController@index`: Supplier por `findOrFail`, cuentas por supplier; ambos usan TenantScope. Catálogo compartido dentro del tenant, no company/branch. Edición inline por cuenta tenant; bancos globales de referencia. Permisos `list/create/edit/destroy_supplierAccount`. Integrar esta superficie con contexto tenant, sin créditos. SUPPLIER-01 sigue abierto: otro endpoint de Proveedores serializa cuentas sin el permiso separado. No certificar autorización global. Ruta auxiliar `get/supplier/accounts/{worker_id}` apunta a método inexistente; la vista actual carga `$accounts` en servidor y no la consume: no incorporarla. |
| Tipos de gasto — Bill | FRONTEND WITH TECH DEBT | `/dashboard/tipos/gastos`, `/dashboard/crear/tipo/gasto`, `/dashboard/editar/tipo/gasto/{id}`; navegación Configuración. BillController, catálogo GLOBAL LEGACY no sensible: sólo id/description, sin parents/lookups. Permisos `list/create/update/destroy_bill`. `edit` usa `find`, no `findOrFail`: inexistente no tiene 404 limpio; validación de escritura limitada. Integrar presentación sin corregir backend ni incluir Expense. |
| Cambio obligatorio de contraseña | READY FOR FRONTEND | `/password/cambio-obligatorio`, acceso por flujo obligatorio, no menú. RequiredPasswordChangeController usa exclusivamente Auth::user; auth + check.user.enabled; redirect si no corresponde. Sin ID externo ni lookups. Vista `auth.required-password-change`, formulario propio y validación backend. QA sin cambiar credenciales. |

Conteos actuales mediante SELECT en transacción MySQL READ ONLY + ROLLBACK: `supplier_accounts=1` (tenant 3), `suppliers=2`, `bills=0`, `users=8`, `must_change_password=1: 2 usuarios`, `stock_items=49`, `inventory_movements_view=106`, `warehouse_transfer_allocations=3`, `working_days=0`, `regimes=0`. Son totales técnicos, no resultados visibles por sesión ni prueba de aislamiento. Dataset vacío no exige crear registros.

Escalas: Backend/Value altos favorecen; Effort/Risk altos penalizan. Riesgo residual reconocido, no resuelto por frontend.

| Candidate | Backend | Value | Effort | Risk | Dataset | Batch |
|---|---:|---:|---:|---:|---|---|
| Kardex moderno, lectura | 4 | 5 | 3 | 2 | 106 movimientos; 49 StockItems | No |
| Cuentas de proveedores, frontera tenant | 3 | 4 | 2 | 3 | 1 cuenta | No |
| Bill | 3 | 2 | 1 | 2 | 0 tipos | No |
| Cambio obligatorio SELF | 5 | 3 | 1 | 1 | 2 pendientes | No |
| Perfil SELF, sólo consulta | 4 | 2 | 2 | 3 | 8 usuarios; lectura del autenticado | No |

Roadmap inmediato (cinco fases de frontend, ninguna QA closure):

| # | Module | Surfaces | State | Why now |
|---|---|---|---|---|
| 1 | Kardex moderno | Consulta, filtros, tabla de movimientos | READY FOR READ-ONLY FRONTEND | DEV añadió scope y validación contextual; valor operativo alto |
| 2 | Cuentas de proveedores | Listado + formulario/edición inline del mantenedor | FRONTEND WITH TECH DEBT | Parent/cuenta tenant y permisos propios; deuda externa delimitada |
| 3 | Tipos de gasto | Index/create/edit | FRONTEND WITH TECH DEBT | CRUD pequeño, catálogo no sensible; independiente de Expense |
| 4 | Cambio obligatorio de contraseña | Formulario SELF | READY FOR FRONTEND | Contrato autocontenido y seguro por usuario autenticado |
| 5 | Perfil propio | Datos y roles propios, exclusivamente lectura | READY FOR READ-ONLY FRONTEND | GET usa Auth::id; separar las acciones legacy inseguras |

## READY READ-ONLY

| Candidato | Estado | Contrato mínimo y límite |
|---|---|---|
| Kardex moderno | READY FOR READ-ONLY FRONTEND | `/dashboard/kardex` y `/dashboard/kardex/{materialId}`, menú Inventario, permiso `list_kardex` también en ambos selectores. Aunque el parámetro se llama materialId, recibe StockItem. `MaterialInventoryScope` exige tenant/company y branch autorizado; binding verifica habilitación company; warehouse pertenece a company y branch actual/general. `KardexMovementQuery` filtra movimientos y traslados por tenant/company/almacenes. Selectores `/stock-items/select` y `/warehouses/select` son contextuales. Vista `inventory.kardex`; no abrir fuentes/documentos ni tocar stock/costos. |
| Perfil propio | READY FOR READ-ONLY FRONTEND | `/dashboard/perfil`, enlace Perfil del shell; auth/check.user.enabled/password.changed/tenant.context. `UserController::profile` obtiene sólo Auth::id con roles; sin lookup global ni ID externo. Integrar únicamente consulta. La vista mezcla upload/settings: excluir esas acciones del alcance y de la interacción de esta frontera; `changeImage(User $user)` no demuestra ownership SELF. No habilita el resto de UserController. |

Cambio DEV relevante: `4567897` añadió KardexMovementQuery y adaptó InventoryMovementController/MaterialController. El root cause antiguo de consulta Kardex global ya no describe este camino HEAD. Esto permite recomendar la fachada read-only; **no cierra INV-01 completo**, Item legacy, valorización histórica ni QA de traslados. Los otros commits recientes revisados (`28c413a`, `9262d65`, `9e6b3ce`, `ec5f6dd`) corresponden principalmente a superficies ya integradas; no se reciclan como fases nuevas.

## BEST BATCH

**NONE.** No hay 4–6 CRUD relacionados seguros pendientes. Compartir patrón Configuration CRUD no basta para agrupar cuentas bancarias, Bill y seguridad SELF. Jornadas/Regímenes todavía no son un batch aprobado.

## NEEDS 10-MINUTE CHECK

| Candidato | Estado | Único punto a resolver |
|---|---|---|
| Jornadas + Regímenes | NEEDS VERY SHORT AUDIT | Rutas activas `/dashboard/registrar/jornadas/trabajo/` y `/dashboard/registrar/regimenes/trabajo/`, vistas create con edición inline y navegación laboral. `WorkingDay::all`, `Regime::all`, parent jornadas global, binding global; sin permiso granular en rutas. Confirmar autoridad de maestro global y autorización antes de recomendarlos. Si deben ser contextuales, WAIT FOR DEV; no filtrar en JS. Dataset 0/0. |

## WAIT FOR DEV

Descartes tempranos, sin endpoints operativos ni conteos adicionales: no gastar QA en blockers evidentes. Familias agrupadas; 11 candidatos/familias evaluados en total.

| Candidato | Estado | Motivo inmediato |
|---|---|---|
| Promociones Seasonal/Limit/Order | WAIT FOR DEV | Modelos promocionales sin scope; binding/global operativo en límites/temporada. El equivalente real de OrderPromotion es PromotionOrder: GET index crea prioridades faltantes. No abrirlo. |
| SpecialBonus | WAIT FOR DEV | Asignación monetaria personal a Worker, no maestro laboral; modelo sin TenantScope y binding global. |
| Refund | WAIT FOR DEV | Reembolso personal a Worker, modelo sin TenantScope y binding global; no se encontró aquí un read path moderno. |
| Supplier Credits | WAIT FOR DEV | Flujo financiero de facturas/créditos con queries y binding globales; no equivale a SupplierAccount. |
| Roles legacy | WAIT FOR DEV | RoleController lista roles/permisos globales y busca por nombre. La frontera Platform Roles/Templates segura ya está integrada, no es candidata nueva. |

Se mantienen fuera del roadmap todos los excluidos por el encargo: ShippingGuide create (GUIDE-01/02 y fallo Nubefact), ReferralGuide, Expense, OrderService, Outputs, FixedAssets, estructura Warehouse legacy, ausencias personales, UserController legacy salvo consulta SELF delimitada, AdminCash, Entrydetail, OrderPurchaseFinance, PublicCatalog, core POS, document security, Material Edit Save y Separate Pack. **Prioridad DEV: ShippingGuide Create Remitente**. SUPPLIER-01 e INV-01 permanecen abiertos con los límites anteriores. Ningún finding se cierra mediante este barrido.
