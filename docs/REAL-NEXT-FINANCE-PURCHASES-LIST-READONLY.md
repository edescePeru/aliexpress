# REAL NEXT — Finance Purchases List — Read-only

2026-11-18. **FINANCE PURCHASES LIST — FRONTEND INTEGRATED — READ PATH**.
Frontera: listado exclusivamente. Backend unchanged; CSS nuevo0; sin commit ni mutaciones financieras.

## DEV Contract

Autoridad: OrderPurchaseFinanceController::indexOrderPurchaseFinance/getAllOrderFinance, OrderPurchaseFinance y ScopedFinancePurchaseCompany. Index obtiene permisos por roles; AJAX consulta órdenes no eliminadas con supplier/approved_user, orden created_at descendente, y devuelve contrato DataTables. No Audit::create ni persistencia en ambos métodos. Se conservan endpoint, respuesta, búsqueda client-side y paginación DataTables; no se carga el JS legacy que incluye anulación.

Mapping: Operational List de Venti Next (Compras/Finance Invoices/Supplier Credits), page heading/toolbar, next-form-section, tabla y badges compartidos. next-page-toolbar-context-only mantiene el encabezado en móvil. Sin diseño ni CSS nuevo.

## Routes

Prefijo `/dashboard/`; grupo autenticado existente.

| Route | Action | Permission | Scope | Read/Mutation |
|---|---|---|---|---|
| ordenes/compra/finanzas | indexOrderPurchaseFinance | list_orderPurchaseFinance | tenant/company | Read |
| all/order/finance | getAllOrderFinance | list_orderPurchaseFinance | tenant/company | Read |

Allowlist exclusivamente `order.purchase.finance.index`. **ALLOWLIST MATCH: YES**: HTML real incluye venti.min.css y venti-next.css. Sin JSON, create/edit/show/lost ni wildcard añadidos.

## Permission

Ambas rutas tienen permission:list_orderPurchaseFinance, comprobado por test de rutas. Controller requiere contexto T/C y company perteneciente al tenant. No se asumen permisos de edición ni bypass Owner.

La presentación legacy del total requiere showPrices_quote dentro de getPermissionsViaRoles: se preserva exactamente. Prueba navegador sin ese permiso: celda vacía, incluso para sort/search. Es una condición de presentación preexistente, no seguridad de campos del JSON: el endpoint actual serializa total. No se certifica confidencialidad de precio frente a inspección de red ni se cambia backend.

## Tenant / Company

ScopedFinancePurchaseCompany limita tenant_id y company_id; ausencia de contexto produce cero filas. SoftDeletes excluye eliminadas. Branch: **NOT APPLICABLE**; no selector ni filtro nuevo.

Prueba positiva privada: ID1/T1/C1 visible únicamente en C1; ID2/T1/C2 únicamente C2; ID3/T3/C3 únicamente C3. ID4 eliminado de C1 excluido. Inexistente999999 no encontrado en cada contexto. Sin contexto:0. No se invocó show ni ningún binding con efectos de auditoría.

## Dataset

MySQL real consultado mediante transacción READ ONLY y ROLLBACK; count global order_purchase_finances=0. Sin inserts ni fixtures en BD real.

| Usuario | Tenant/company | Branch de sesión, no scope | Permiso listado | IDs / count |
|---|---|---|---|---|
| Owner EDESCE4 | 1/1 | 1 | true | [] / 0 |
| Owner EDESCE4 | 1/2 | 2 | true | [] / 0 |
| Owner Gamarra7 | 3/3 | 4 | true | [] / 0 |

Evidencia: controllers y Blade reales con Auth/contexto explícitos, cache-array para registrar de permisos; no smoke de login HTTP. Aislamiento positivo probado por modelo real en SQLite :memory:, no inferido de listas vacías. Casos positivos reales pendientes de dataset autorizado.

## Columns

ID, código, fecha orden, fecha entrega, observación, proveedor, aprobado por, moneda y total conservados. Regularización presenta el campo real regularize; sustituye el espacio de acciones excluidas. Sin botones de detalle/documentos. Fechas/IDs/moneda/regularización centrados, total derecha, descripciones izquierda. Ausencia se muestra como raya o fallback textual del contrato; no se inventan agregados financieros.

## Filters

El listado original sólo tenía búsqueda general, tamaño de página y sort DataTables. Se preservan; no existían filtros separados de fechas/proveedor/estado, por lo que no se añaden. Fecha conserva orden cronológico mediante dato ISO al ordenar y formato DD-MM-YYYY al mostrar. Orden inicial es el recibido del backend.

Paginación: client-side,10 por página. Empty state, resumen0, botones inactivos y búsqueda sin resultados comprobados. **MULTIPAGE QA — PENDING DATASET** real. Respuesta controlada de una fila prueba render, no certifica paginación real múltiple.

## States

Campo real regularize: r=Regularizada, nr=No regularizada; badges compartidos con texto. Valor desconocido no se convierte en otro estado. No se confunde con estado de aprobación o pago. La tabla real no contiene status_order; no se trae el enum stand_by/send/pick_up de OrderPurchase normal ni se ejecuta el endpoint legacy de cambio de estado. Sin certificación de ese flujo excluido.

## Supplier

Supplier mantiene BelongsToTenant; orden financiera tenant/company. Relación supplier eager-loaded por backend, sin filtro company inventado. No selector ni lookup nuevo. supplier nulo: Sin proveedor. approved_user nulo: Sin aprobar; no se inventa actor alternativo.

Payment deadline: relación disponible en modelo, no cargada ni mostrada por este listado; no necesita selector. No se consulta ni cambia OrderService/Proforma y no se cierra PAY-01.

## Currency

currency_order backend (PEN/USD); total sin conversión, suma ni cálculo. No agregado0.00 en vacío. Se conserva la condición de visibilidad del total documentada arriba.

## Excluded GETs

**create/edit/show/lost — EXCLUDED**. Create, edit y show contienen Audit::create; getAllOrderPurchaseFinanceLost también registra auditoría. No se abrieron ni se intentó suprimir Audit desde frontend.

| Action | Route (dashboard/) | Permission | Read/Mutation | Included |
|---|---|---|---|---|
| List | ordenes/compra/finanzas; all/order/finance | list_orderPurchaseFinance | Read | YES |
| Create/store | crear/orden/compra/finanzas; store/order/purchase/finance | create_orderPurchaseFinance | GET con Audit / Mutation | NO |
| Edit/update | editar/orden/compra/finanza/{id}; update/order/purchase/finance | update_orderPurchaseFinance | GET con Audit / Mutation | NO |
| Show | ver/orden/compra/finanzas/{id} | list_orderPurchaseFinance | GET con Audit | NO |
| Lost | ordenes/compra/finanzas/perdidas; all/order/purchase/finance/lost | list_orderPurchaseFinance | GET / GET con Audit | NO |
| Delete | destroy/order/purchase/finance/{id} | destroy_orderPurchaseFinance | Mutation | NO |
| Restore | restore/order/purchase/finance/delete/{id} | destroy_orderPurchaseFinance | Mutation | NO |
| Status | order_purchase/finance/change/status/{order_id}/{status} | update_orderPurchaseFinance | Mutation | NO |
| Print | imprimir/orden/compra/finanza/{id}; imprimir/orden/compra/finanza/eliminada/{id} | list_orderPurchaseFinance | Document | NO |
| Entry regularization | regularizar/automaticamente/entrada/compra/finanzas/{entry_id}; store/regularize/order/purchase/finance | create_orderPurchaseFinance | GET con Audit / Mutation | NO |
| Payments/posting/files | No control incluido en este listado | Fuera de frontera | Financial/document | NO |

Eliminadas, vistas regularizadas y restore no se integran. JS legacy queda intacto en disco, desacoplado de index. **DOCUMENT SECURITY — BACKEND/INFRA OPEN**: no PDFs, attachments ni comprobantes abiertos.

## Empty State

Mensaje fuera del scroll local: “No hay órdenes de compra financieras registradas en esta empresa”. Tabla vacía, búsqueda y resumen correctos. Error HTTP explícito separado de empty state; escenario controlado403 muestra error y oculta mensaje vacío. No se convierte denegación en ausencia de órdenes.

## Responsive

Chrome headless, HTML real renderizado y assets locales; shell externo/notifications interceptados. Matriz320/375/390/576/768/992/1200/1440 en vacío, fila controlada, error403 y sin permiso de precio: scrollWidth igual a viewport, consola sin errores. Tablas con scroll local. Capturas375/1440 revisadas. Requests de datos del listado: sólo GET. Controles restantes son búsqueda/paginación, sin acciones de negocio.

Se detectó toolbar vacía en móvil: corregida con variante compartida context-only y revalidada. Evidencia positiva simulada exclusivamente en navegador, sin registros reales. No se declara QA HTTP autenticado end-to-end ni mutaciones.

## Validation

FinancePurchaseListReadPathTest: **2 tests / 10 assertions PASS**. SQLite privado, scopes reales, soft delete, inexistente, fail-closed y metadata permission de ambas rutas. No ejecuta create/store/delete/restore ni Audit. No equivale a probar todos los middleware HTTP con roles sintéticos.

PHP7.3 Blade compile PASS; node --check PASS; git diff --check PASS. Warning de schema XML PHPUnit deprecado preexistente, sin cambios de infraestructura.

## PUR-01 Boundary

**PUR-01 / FINANCE PURCHASE LIST — FIXED / QA PASSED / FRONTEND INTEGRATED** dentro de la evidencia y frontera list-only descritas. PUR-01 general permanece OPEN/PARTIALLY FIXED: flujos legacy, OrderService, create/edit/show, posting y documentos.

## Backend Sync Notes

[RESOLVED] Listado financiero contextual y permiso list en vista/AJAX provistos por merge DEV; integrado frontend 2026-11-18.

[OPEN] Seguridad documental/infra; rutas mutadoras y GETs con Audit excluidos. Frontend de listado puede continuar: Sí. No nuevo fix backend ni cierre de flujos excluidos.

[OPEN] Precio: showPrices_quote sigue siendo visibilidad frontend legacy; JSON total sin redacción por campo. Si debe ser confidencial, DEV debe definir/proteger autorización de campo. No se relaja ni se modifica contrato en esta fase.

## Files

- resources/views/orderPurchaseFinance/indexOrderFinance.blade.php: Operational List sin enlaces financieros.
- public/js/orderPurchaseFinance/list-readonly.js: nuevo adaptador exclusivamente GET.
- config/venti-next.php: sólo order.purchase.finance.index añadido en esta fase.
- tests/Feature/FinancePurchaseListReadPathTest.php: pruebas aisladas.
- docs/REAL-NEXT-DEV-TEAM-FINDINGS-BACKLOG.md: cierre parcial PUR-01.
- Este documento.

Cambios previos de Entry, Summary y Supplier Credits preservados. Backend, rutas, modelos, servicios, vendor y CSS sin cambios. No commit.
