# REAL NEXT — Supplier Credits — Read-only Integration

2026-11-18. **SUPPLIER CREDITS — FRONTEND INTEGRATED — READ PATH**.
Backend unchanged; CSS nuevo: 0; ninguna mutación financiera ni commit.

## DEV Contract

Autoridad: SupplierCreditController, SupplierCredit, CreditPay y servicios Purchases/SupplierCreditBalance y SupplierCreditExpiration. Se reutilizan Operational List, toolbar, form sections, badges, tabla responsive y modal Bootstrap de Venti Next. No se cargan los scripts legacy de mutación en estas dos vistas.

Los GET consumidos no guardan créditos ni ejecutan Audit::create. La proyección de vencimiento modifica atributos en memoria, no la BD. Los enlaces documentales que pueda devolver backend no se renderizan ni se abren.

## Routes

Todas las URI siguientes tienen prefijo `/dashboard/` y autenticación del grupo existente.

| Surface / Action | Route | Permission | Scope | Read/Mutation | Decision |
|---|---|---|---|---|---|
| Créditos registrados | control/creditos (index.credit.supplier) | control_credit | tenant/company | Read | INTEGRATED |
| Datos créditos | get/only/credits/supplier | control_credit | tenant/company | Read | INCLUDED |
| Facturas y saldos | faturas/proveedores/pendientes (index.invoices.pending) | control_credit | tenant/company | Read | INTEGRATED |
| Datos facturas/saldos | get/invoices/pending | control_credit | tenant/company | Read | INCLUDED |
| Detalle | get/credit/by/id/{creditId} | control_credit OR edit_credit OR pay_credit OR nopay_credit | tenant/company | Read | INCLUDED |
| Historial pagos | get/pays/credit/{credit_id} | control_credit OR pay_credit OR nopay_credit | crédito contextual | Read | INCLUDED |
| Entries elegibles | get/only/invoices/purchase | control_credit | tenant/company | Read | Auditado; asociación excluida |
| Asociar | add/invoice/credit/{idEntry} | edit_credit | backend actual | Mutation | EXCLUDED |
| Editar | credit/control/update | edit_credit | backend actual | Mutation | EXCLUDED |
| Ampliar días | add/days/credit/{credit_id} | edit_credit | backend actual | Mutation | EXCLUDED |
| Pagar | credit/control/paid; save/pay/credit/{credit_id} | pay_credit | backend actual | Mutation | EXCLUDED |
| Revertir/eliminar pago | cancel/pay/credit/{idCredit}; delete/pay/credit/{credit_pay_id} | nopay_credit | backend actual | Mutation | EXCLUDED |
| Cambiar estado | change/status/credit/{credit_id}/{status} | pay_credit OR nopay_credit | backend actual | Mutation | EXCLUDED |

Se conserva el spelling real `faturas`. Allowlist: sólo `index.credit.supplier` e `index.invoices.pending`; sin wildcard, JSON ni rutas de pago. HTML renderizado carga venti.min.css y venti-next.css.

## Permissions

El permiso de entrada es control_credit. Los OR de detalle/historial son contratos backend, no permisos nuevos del frontend. Los Owners usados (EDESCE user4, Gamarra user7) tienen control_credit según registrar temporal cache-array. No se asume bypass Owner. Matriz anterior auditada en middleware; esta fase no certifica un smoke HTTP de denegación para cada rol.

## Tenant / Company

SupplierCredit tiene scope tenant/company fail-closed. Controller exige ambos contextos y company del tenant. CreditPay hereda aislamiento por el crédito. Branch: **N/A**, sin restricción añadida.

Pruebas SQLite con IDs completos: T1/C1 ve crédito/pago1; T1/C2 sólo2; T3/C3 sólo3. Sin contexto: ninguno. El servicio de saldo rechaza un crédito obtenido en otra company. El detalle usa findOrFail contextual: ID inexistente devuelve404. Historial no valida existencia del padre con findOrFail: ID ajeno/inexistente produce lista vacía contextual, no404. No se altera ese contrato.

## Dataset

Revalidación real mediante conexión MySQL en transacción READ ONLY, final ROLLBACK; sin fixtures compartidos. Totales globales: supplier_credits=0, credit_pays=0 (**REAL DATASET — EMPTY**).

| Usuario | Tenant | Company | Branch de sesión (no scope) | Créditos / pagos | Supplier IDs | Entry IDs elegibles |
|---|---|---|---|---|---|---|
| Owner EDESCE (4) | 1 | 1 | 1 | 0 / 0 | 1 | 38,36,29 |
| Owner EDESCE (4) | 1 | 2 | 2 | 0 / 0 | 1 | ninguno |
| Owner Gamarra (7) | 3 | 3 | 4 | 0 / 0 | 2 | ninguno |

Controllers y vistas reales ejecutados con Auth/contexto explícitos en harness, no login HTTP end-to-end. Detalle999999:404 en los tres contextos; historial999999: pays[]. Casos positivos reales de crédito/pagos: pendientes de dataset; no se inventa cierre con datos reales.

## Supplier Contract

Supplier es tenant-scoped; Supplier1 puede ser compartido por C1/C2 del mismo tenant. Crédito y Entry deben pertenecer a company autorizada. No se añade selector ni filtro JS para simular aislamiento.

## Pending Entries

**indexInvoicesPending lista créditos ya registrados**, no Entries disponibles para generar crédito, y no restringe todos sus resultados a estado pendiente. Se titula “Facturas y saldos de créditos” y se explica esta distinción.

getOnlyInvoicesPurchase filtra Entries del tenant/company, compra, no anuladas y sin crédito asociado. Se audita su respuesta real; la antigua sección de asociación estaba comentada y no se reactiva. La migración 2026_11_17_000004_unique_supplier_credit_entry y el índice real supplier_credit_entry_context_unique cubren tenant_id/company_id/entry_id (3 columnas verificadas). No se ejecutó asociación ni migración. Test único usa esquema privado equivalente, no ejecuta la migración productiva.

## Columns / Filters

Principal: proveedor, factura, orden, importes PEN/USD, emisión, vencimiento, plazo, días, observaciones, estado, fecha pago y consulta. Facturas/saldos: orden, correlativo, proveedor, moneda, condición, montos y saldos por moneda, pagado, saldo actual, factura, emisión, vencimiento, días/estado pago, fecha pago, observaciones, estado crédito y consulta.

Se mantienen DataTables client-side (10 por página), búsqueda, ordenación, rango de emisión que requiere ambos límites y visibilidad de columnas del segundo listado. No se inventan selectors proveedor/estado/vencimiento. Sin dataset real multipágina; no se certifica ese caso. Alineación semántica compartida: importes derecha, fechas/IDs/estados/acciones centro, descripción izquierda.

## Balance

getInvoicesPending utiliza SupplierCreditBalance; la UI muestra adelanto/deudaActual y campos por moneda recibidos. No resta total-pagos en JS. Principal no entrega saldo: no se inventa ni se agrega cálculo. Servicio exige una única moneda definida, suma CreditPay contextual y señala legacy_review para anticipos históricos. PEN/USD se preservan; sin conversión ni suma entre monedas. Test aislado: total100, pagado20, saldo80 PEN.

## Expiration

SupplierCreditExpiration usa date_expiration y fecha actual America/Lima; días negativos expired; dentro de ventana by_expire; resto outstanding. paid_out, state_pay=canceled o fecha ausente no se reproyectan. Sin persistencia. Frontend sólo presenta resultado; detalle de la vista de saldos conserva proyección entregada por su listado.

Payment deadline: relación histórica deadline.description, sólo lectura. No hay selector ni edición: no requiere inventar selectable/currentId. No se toca la regresión PAY-01 de OrderService/Proforma ni se recalcula vencimiento desde plazo.

## CreditPay

Historial contextual en modal seguro, ID/fecha/importe, sin comprobantes ni links. Control de respuestas tardías al cerrar/reabrir. Dataset real vacío y empty state comprobados; aislamiento positivo del modelo en SQLite. Historial positivo real pendiente de dataset.

## States

Traducciones de state_credit real: outstanding=Pendiente, by_expire=Por vencer, expired=Vencido, paid_out=Pagado. Otros valores se muestran como recibidos. statePay/estadoPago permanece dato backend; no se deriva “pago parcial” del saldo.

## Actions Excluded

Asociación, editar crédito, pagar, revertir, marcar pagado/no pagado, cambiar días/estado, eliminar pago: **MUTATION QA PENDING / OUT OF SCOPE**. Sin controles ejecutables para estas acciones en las nuevas vistas. Reportes agregados/exportaciones y KPI legacy no se integran en este cierre. Documentos: **DOCUMENT SECURITY — BACKEND/INFRA OPEN**; ninguno abierto.

## Empty State

Mensaje visible fuera del scroll local, resumen0 créditos, filtros disponibles; no importes financieros sintéticos. Filtro sin coincidencias tiene mensaje distinto. Fallo HTTP se muestra como error explícito, no como ausencia de registros.

## Responsive

Chrome headless con HTML real renderizado y assets locales; shell externo/notifications interceptados. Dos vistas × dataset vacío/controlado × 320/375/390/576/768/992/1200/1440: scrollWidth=viewport, sin overflow global ni errores de consola. Tabla ancha conserva scroll local. Modal comprobado en320/375/768/1440; filtro de emisión sin resultados PASS. Capturas desktop/mobile revisadas; mensaje vacío mobile corregido. Respuestas positivas simuladas sólo en navegador, sin insertar créditos. Requests de la integración: GET únicamente.

## Tests / Validation

- SupplierCreditReadPathTest: 4 tests / 28 assertions PASS, SQLite :memory: separado de MySQL. Scopes crédito/pago, saldo, vencimiento, unicidad privada. No ejecuta pagos.
- PHP7.3: Blade compile PASS; node --check PASS; git diff --check PASS.
- Alcance combinado: auditoría de middleware, consultas reales read-only, pruebas aisladas y render visual. No equivale a sesión HTTP completa con créditos reales; queda pendiente ese smoke cuando haya dataset autorizado.

## PUR-01 Update

**PUR-01 / SUPPLIER CREDIT READ PATH — FIXED / QA PASSED / FRONTEND INTEGRATED** con límites de evidencia anteriores. PUR-01 global permanece PARTIALLY FIXED/OPEN para compras/servicios legacy y mutaciones.

## Backend Sync Notes

[RESOLVED] Read path de SupplierCredit: scope T/C, CreditPay contextual, saldo backend y vencimiento en memoria disponibles tras merge DEV. Integración de lectura completada 2026-11-18.

[OPEN] Document security; archivos/infra backend fuera de esta fase. Frontend puede continuar: Sí, sin links documentales.

[OPEN] PUR-01 general y PAY-01 OrderService/Proforma: contratos ajenos a este read path. No se reabren ni se corrigen aquí. QA financiero/mutaciones y positivo real: pendiente, no defecto nuevo atribuido al read path.

## Files

Modificados: config/venti-next.php (sólo dos allowlist de esta fase), resources/views/credit/indexSupplierCredit.blade.php, resources/views/credit/indexInvoicesPending.blade.php, docs/REAL-NEXT-DEV-TEAM-FINDINGS-BACKLOG.md (subfinding).

Nuevos: resources/views/credit/_readonly.blade.php, public/js/credit/supplier-readonly.js, tests/Feature/SupplierCreditReadPathTest.php y este documento. Cambios previos de Entry/Financial Summary preservados. Backend/rutas/modelos/servicios y CSS sin cambios.
