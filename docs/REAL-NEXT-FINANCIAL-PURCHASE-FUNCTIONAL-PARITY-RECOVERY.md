# REAL NEXT — R3.2 — Financial Purchase Functional Parity Recovery

## R3.2 — CLOSED BY PRODUCT SCOPE DECISION

La decisión explícita del propietario del 2026-11-24 sustituye la duda provisional que se conserva abajo como evidencia histórica. FR-037…045: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED (9). No se implementan los controles; no son recuperaciones ni bugs corregidos. OrderPurchaseFinance completo queda fuera de recuperación. Invoice y SupplierCredit continúan vigentes.

El resto conserva la evidencia anterior al cierre; sus estados provisionales no son la decisión vigente.


Fecha: 2026-11-24. HEAD:3914020, feature/venti-next-frontend. Cambios R3.1 del working tree preservados.

## Estado de vigencia

**PRODUCT DECISION REQUIRED — LEGACY STATUS UNCERTAIN** respecto de los controles internos de Compras Financieras: las rutas siguen activas y las nueve capacidades eran alcanzables dentro de sus Blades previos a homologación, pero sus enlaces de sidebar estaban comentados antes del commit df5c53f (2026-10-23), que quitó ese markup. No se encontró una justificación de negocio que permita equiparar ocultación del sidebar con retiro de las acciones internas. Se consultó al propietario; no se reactivan capacidades ambiguas hasta su respuesta.

Esta duda no es un blocker backend ni una decisión de retirar funcionalidades. Los nueve contratos se auditaron y no se identificó un blocker de seguridad que por sí solo impida recuperar sus controles. No se marca QA pendiente como motivo de exclusión.

## AGENTS legacy rule

Actualizado AGENTS.md: vigencia antes de recuperación; comprobar navegación/permisos/flags/condiciones/sustitutos/historia/decisiones; no deducir vigencia por ruta ni retiro por menú oculto; clasificación explícita; no reactivar OrderService/Proforma/ReferralGuide/FollowMaterial/Promotions legacy. Sin cambios al shell.

## R3.1 recheck

Normal y papelera mantienen navegación activa y endpoints vigentes. General/Express comparten la duda de ocultación histórica del sidebar; no se revirtió nada unilateralmente. No se repitió QA.

Corrección de la entrega previa: **http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2** es Compras generales (`order.purchase.general.indexV2`, OrderPurchaseController@indexV2). `/dashboard/listado/general/cotizaciones/v2` es QuoteController@indexV2. El error estaba en el enlace del chat anterior, no en las rutas ni en la implementación R3.1.

## Superficies e historial

Controller común: OrderPurchaseFinanceController. Prefijo URL http://127.0.0.1:8080/dashboard/.

| Surface | Route name | URI | Blade / JS | Baseline |
|---|---|---|---|---|
| Listado | order.purchase.finance.index | ordenes/compra/finanzas | indexOrderFinance / list-readonly.js | 483a785^ → 483a785 |
| Eliminadas | order.purchase.finance.delete | ordenes/compra/finanzas/eliminadas | indexFinanceDeleted / history-readonly.js | 744d28b^ → 744d28b |
| Regularizadas | order.purchase.finance.list.regularize | ordenes/compra/finanzas/regularizadas | indexRegularizeFinance / history-readonly.js | 744d28b^ → 744d28b |
| Detalle eliminado | sin nombre | ver/orden/compra/finanzas/eliminada/{id} | showOrderPurchaseDeleteFinance | 744d28b^ → 744d28b |
| Crear | order.purchase.finance.create | crear/orden/compra/finanzas | createOrderFinance / createOrderFinance.js | 744d28b^ → 744d28b |
| Editar | sin nombre | editar/orden/compra/finanza/{id} | editOrderPurchaseFinance / editOrderFinance.js | 744d28b^ → 744d28b |

Seis superficies homologadas revisadas; detalle activo revisado sólo como destino, NO homologado. No se añaden páginas Lost/regularización automática ni se interviene overlap desktop de forms.

## Matriz de pérdidas originales

Todas estaban alcanzables en el baseline interno indicado y ausentes en la UI homologada. Vigencia actual de producto pendiente de confirmación por ocultación de navegación previa. Permisos son los actuales de ruta, no un bypass Owner. Condición común: contexto tenant/company válido y objeto perteneciente a él.

| ID | Pantalla | Capacidad histórica | Vigencia actual | Evidencia | Permiso | Condición | Endpoint /dashboard/ | Blocker | Recuperación | QA |
|---|---|---|---|---|---|---|---|---|---|---|
| FR037 | Listado | Crear | PRODUCT DECISION REQUIRED | 483a785^ toolbar | create_orderPurchaseFinance | Contexto / lookups | GET crear/orden/compra/finanzas | No blocker nuevo; Audit técnico | Pendiente decisión | Estático; Store no ejecutado |
| FR038 | Listado | Detalle | PRODUCT DECISION REQUIRED | indexOrderFinance.js legacy | list_orderPurchaseFinance | Parent activo contextual | GET ver/orden/compra/finanzas/{id} | Destino legacy no es retiro; Audit técnico | Pendiente decisión | No documento/GET poblado |
| FR039 | Listado | Editar | PRODUCT DECISION REQUIRED | indexOrderFinance.js legacy | update_orderPurchaseFinance | Parent activo contextual | GET editar/orden/compra/finanza/{id} | Referencias históricas, deuda conocida | Pendiente decisión | Update no ejecutado |
| FR040 | Listado | Anular | PRODUCT DECISION REQUIRED | data-delete/cancelOrden | destroy_orderPurchaseFinance | Credit guard backend | POST destroy/order/purchase/finance/{id} | No blocker nuevo | Pendiente decisión | MUTATION QA PENDING |
| FR041 | Listado | Imprimir | PRODUCT DECISION REQUIRED | data row print | list_orderPurchaseFinance | Parent contextual | GET imprimir/orden/compra/finanza/{id} | Document QA pendiente | Pendiente decisión | No PDF ejecutado |
| FR042 | Eliminadas | Restaurar | PRODUCT DECISION REQUIRED | indexDeleted.js/data-restore | destroy_orderPurchaseFinance | Sólo eliminada; deletion_batch/crédito | POST restore/order/purchase/finance/delete/{id} | Rechazo contractual si falta batch, no retirada | Pendiente decisión | MUTATION QA PENDING |
| FR043 | Eliminadas | Imprimir | PRODUCT DECISION REQUIRED | indexDeleted.js print | list_orderPurchaseFinance | withTrashed contextual | GET imprimir/orden/compra/finanza/eliminada/{id} | Document QA pendiente | Pendiente decisión | No PDF ejecutado |
| FR044 | Regularizadas | Detalle | PRODUCT DECISION REQUIRED | indexRegularize.js show | list_orderPurchaseFinance | Parent activo contextual | GET ver/orden/compra/finanzas/{id} | Destino legacy no es retiro | Pendiente decisión | Read poblado pendiente |
| FR045 | Regularizadas | Imprimir | PRODUCT DECISION REQUIRED | indexRegularize.js print | list_orderPurchaseFinance | Parent activo contextual | GET imprimir/orden/compra/finanza/{id} | Document QA pendiente | Pendiente decisión | No PDF ejecutado |

## Inventario adicional y exclusiones

11 capacidades agrupadas ya presentes: consulta/búsqueda y paginación de cada uno de tres listados (6), detalle desde papelera (1), consulta del detalle eliminado (1), formulario Create/Store (1), formulario Edit/Update (1), operaciones de líneas de Edit (1). Son agrupaciones de operación por superficie, no conteos de botones/campos. Se preservan; no se ejecutaron mutaciones.

Tres candidatos históricos no constituyen pérdidas vigentes demostradas:
- Crear ingreso con regularize_orderService: bloque /*...*/ en indexOrderFinance.js, presente también en baseline483a785^; HISTORICAL CODE — NOT REACHABLE. El propietario excluye reactivar OrderService. No conectarlo a Entries ni inventar un sustituto.
- Cambio de estado: ruta/controller existe, pero no hay handler/trigger en ninguno de los tres JS/Blades operativos contrastados; HISTORICAL CODE — NOT REACHABLE en estas superficies. No inferir capacidad por existencia de endpoint.
- Export/Column Visibility: strings del diccionario DataTables no son botones. No configuración Buttons/trigger export alcanzable; HISTORICAL CODE — NOT REACHABLE. No crear controles nuevos.

Inventario:23 capacidades/candidatos agrupados=11 preservados+9 pérdidas históricas sujetas a decisión+3 residuales sin alcanzabilidad. No afirmar que las nueve son retiradas ni confirmarlas vigentes sin decisión. Los módulos excluidos del propietario permanecen excluidos.

## Contratos backend

Rutas2802–2860 preservan auth, check.user.enabled, password.changed, tenant.context y permisos list/create/update/destroy_orderPurchaseFinance. Controller exige company perteneciente a tenant; ScopedFinancePurchaseCompany califica parent/details y aplica 1=0 sin contexto. Branch no es ownership.

- List/getAllOrderFinance: sólo lectura, no Audit. Eliminadas onlyTrashed, regularizadas regularize=r. DataTables mantiene búsqueda client-side y paginación.
- Create/Edit/Show activo: Audit::create(user_id,action,time,timestamps) técnico aceptado, no modificación comercial en GET. Lookups contextualizados; aprobador inactivo/cotización cerrada pueden faltar del catálogo de Edit. Forms ya preservan IDs históricos ocultos, validator admite conservarlos según contrato; no inventar opciones ni bloquear toda edición.
- Destroy: transacción, lock del parent, deletion_batch común y soft-delete de detalles/parent. ScopedFinancePurchaseCompany::deleting invoca FinancePurchaseCreditGuard::assertMutable. No confundir ausencia de llamada en controller con falta de protección.
- Restore: onlyTrashed, transacción/locks, requiere deletion_batch y restaura sólo detalles del mismo batch; guard restoring rechaza crédito. Falta batch →422 contractual; no certificación real de mutación.
- Crédito: bloquea cambios financieros (supplier/currency/deadline/total/igv), detalles, delete/restore. No prohíbe GET Edit indiscriminadamente ni todos los metadatos. No añadir flags frontend artificiales.
- Print activo/eliminado: queries contextuales, firstOrFail, eliminado withTrashed; relación details incluye withTrashed. Permiso list_orderPurchaseFinance. Documentos no ejecutados; storage/infra y contenido PDF no certificados. No extrapolar el bug de impresión Normal R3.1 a Finance.

## Blockers conocidos, fuera de recuperación

Lost mantiene parsing substr(code,3) sobre OCF- y secuencia sin orden; paqueteF existente. Regularización automática mantiene endpoints de detalle Normal en JS (paqueteG). No se tocan ni se ofrecen enlaces nuevos. References históricas: deuda conocida de rehidratación, no blocker para navegación Edit. No nuevos findings DEV demostrados.

## Dataset y QA ejecutado

MySQL en START TRANSACTION READ ONLY, PDO dedicado y rollback, sin interceptar Audit porque listas/fetch no escriben. Tabla order_purchase_finances:0 incluyendo eliminadas. EDESCE T1/C1/B1, T1/C2/B2 y Gamarra T3/C3/B4: arrays vacíos para main/deleted/regularized. No fixtures en DB. Renders reales y middleware de permiso invocados; no sesión HTTP end-to-end nueva.

FinancePurchaseListReadPathTest:2 tests/10 assertions PASS, SQLite :memory: privado; warning XML preexistente. No certifica Store/Update/restore ni permite afirmar aislamiento positivo con dataset real vacío.

Artefactos: C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r32-read.php, r32-data.json, r32-*.html.

## Cobertura anterior

FR037–045 estaban inventariadas y excluidas por read-only; recuperarlas depende ahora de vigencia, no del estado de QA. Listas/históricos ya tuvieron lectura/empty/responsive. Forms ya conservaron payload y operaciones de detalles, sin certificación mutadora. Crédito y restauración auditados estáticamente, mutación positiva sin dataset. Residuales OrderService/estado/diccionario no son nuevas pérdidas. No se reabre QA global.

## Resultado provisional

AGENTS actualizado, R3.1 URL corregida documentalmente; ninguna ruta/frontend/backend/CSS financiero modificado todavía. Sin commit/push/merge ni avanceR4. Se espera decisión de vigencia antes de recuperar controles. PRODUCT DECISION REQUIRED no es retirement ni backend blocker.
