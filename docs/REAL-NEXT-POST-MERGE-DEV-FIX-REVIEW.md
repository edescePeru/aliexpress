# REAL NEXT — POST-MERGE DEV FIX REVIEW

Fecha: 2026-11-18. AUDIT ONLY. PHP 7.3.33. Base activa: `aliexpress_multitenant_3`.
Sólo documentación; sin cambios de código, datos, permisos, migraciones, frontend ni commit.

## Merge Delta

Merge `5fb180d` en `feature/venti-next-frontend`: primer padre `4b546d4`, segundo padre `2f87545`.
Commits DEV nuevos: `7d5335d` (resumen financiero y trazabilidad de caja), `2f87545` (bugs de auditoría).
Se contrastaron `git log HEAD^1..HEAD^2`, `git diff HEAD^1..HEAD -- app routes database tests` y el backlog.
81 archivos totales; 33 archivos app, routes/web.php y cinco migraciones. Ningún test nuevo/modificado por DEV.
Los cambios frontend de CashBox/CashRegister/Credit presentes en este delta son también entregas DEV; la integración anterior de cuentas de proveedores del primer padre NO se atribuye a DEV.

Fronteras backend incorporadas:

- Caja: CashBox/Subtype validación; CashRegister, CashOpeningFund, FinancialSummary; CashMovement, CashRegularizationService, DeferredSaleRefund y SalePartialPayment reconocen cancelación diferida. No certifica cambios de moneda/subtipo históricos.
- Compras financieras: controller/modelos/details, ScopedFinancePurchaseCompany y FinancePurchaseInput; contexto, validación y restauración por deletion_batch.
- Créditos: SupplierCredit/CreditPay, controller, scopes company/payment, SupplierCreditBalance/Expiration y comando de vencimientos. Lectura proyecta vencimiento en memoria; pagos/asociación/reversión siguen fuera de QA.
- Entry: detalle company y reportes/export con contexto/permisos.
- Quote/QuoteSale/Pdfs: creadores, retirada de ruta inválida y limpieza PDF; no se generaron documentos.
- User: directorios contextualizados y permisos AJAX.
- Customer/Decolecta: consulta GET separada de creación POST; rutas públicas de prueba retiradas.
- PaymentDeadline consumers: Entry, OrderPurchase, OrderPurchaseFinance, OrderService, Proforma, Quote, QuoteSale, PuntoVenta.

## Migrations

`php artisan migrate:status`: las cinco nuevas están Ran; no se ejecutó migrate. No pendientes observadas.

| Migration | Batch / estado | Objetivo / habilita |
|---|---|---|
| 2026_11_16_000001_track_cash_opening_funds_and_deferred_cancellations | 174 / Ran | cash_opening_funds y deferred_cancelled_*; resumen/trazabilidad |
| 2026_11_17_000001_add_context_to_finance_purchase_tables | 175 / Ran | tenant/company en órdenes financieras y detalles |
| 2026_11_17_000002_add_finance_purchase_deletion_batch | 176 / Ran | restaurar sólo detalles del mismo lote de eliminación |
| 2026_11_17_000003_add_supplier_credit_context | 177 / Ran | contexto créditos; pagos heredan del padre |
| 2026_11_17_000004_unique_supplier_credit_entry | 178 / Ran | unique tenant/company/entry, asociación idempotente |

Las migraciones de contexto rechazan tablas pobladas sin clasificación segura; no inventan backfill. La unique rechaza duplicados. OrderService y Proforma NO reciben columnas tenant/company de este paquete.

## Permissions

No hay migration/seeder/assignment de permisos en el delta. Rutas usan permisos existentes: list_user, create_request/create_requestSimple, list_entryPurchase/list_entryInventory, list_orderPurchaseFinance, showFinancialSummary_cashBox, control_credit/edit_credit/pay_credit/nopay_credit y create_customer.
Catálogo actual confirma esos permisos principales (incluidos control_credit, showFinancialSummary_cashBox y list_kardex). No se identificó un nombre nuevo faltante del delta.

KARDEX-01: `list_kardex` ahora existe (id555), asignado a roles2/12/15/20; Owners4/7 tienen roles2/15. No procede afirmar ausencia de asignación. El primer Gate consultó caché antigua y denegó; al reinicializar exclusivamente el registrar del proceso temporal con cache array, hasPermissionTo/viaRoles/Gate=true para ambos Owners. No se limpió caché compartida ni se cambió DB. Origen de provisión NO atribuible al merge. **KARDEX HTTP READY: pendiente refresco de caché del entorno y smoke HTTP**, no repetir KardexMovementQuery.
ShippingGuide no incorpora autorización granular, asignaciones ni idempotencia nuevas. `create_referralGuide` existente no equivale a autorización de ShippingGuide moderno.

## Findings Updated

Los PASS siguientes tienen el alcance explícito de evidencia; no significan QA de mutaciones. DOC-02 se mantiene parcial porque la aceptación de archivos requiere pruebas aisladas que esta fase excluye.

| Finding | Before | DEV change | Evidence | Current status | Frontend impact |
|---|---|---|---|---|---|
| CASH-01 | OPEN: XYZ aceptada | whitelist PEN/USD, normalización, banco autoritativo | Invocación de validateCashBox sin store: PEN/USD/null aceptados; XYZ ValidationException currency | FIXED — QA PASSED | Retomar QA aislado de validación; no cierra caja financiera |
| CASH-05 | OPEN: Undefined index | nullable omitido normalizado | validateSubtype omitido/null/global/vacío devuelve null sin warning | FIXED — QA PASSED | Retomar QA aislado store/update; no persistido |
| CASH-02 | Decisión abierta | Sin guard nuevo de moneda con sesión | Diff de validateCashBox | STILL OPEN | No cerrar modificación de caja en uso |
| CASH-03 | Decisión abierta | Cancelación diferida no congela is_deferred | Diff servicios/controller | STILL OPEN | Política histórica pendiente |
| CASH-04 | Decisión abierta | Sin política nueva toggle referenciado | Diff Subtype | STILL OPEN | No certificar desactivación |
| ENTRY-01 | OPEN: detalle tenant-only | find T/C fail-closed | Controller real: C1 propio4; C2 propio49; Gamarra propio45; ajenos/inexistente404 | FIXED — QA PASSED | READY FOR READ-ONLY FRONTEND |
| QUOTE-01 | OPEN: usuarios globales | Opciones a partir de quotes del local/subconjunto + tenant usuario; creator filter restringido | index/indexFacturadas reales: C1/B1 IDs[4]/[]; C2 y Gamarra []/[] | FIXED — QA PASSED | Smoke frontend existente, no nueva integración |
| QUOTE-04 | OPEN: action inexistente | Retira ruta venta/totales | Ruta/nombre y referencias runtime buscadas: ausentes | FIXED — QA PASSED | No recrear enlace ni métricas |
| DOC-02 | OPEN: glob/unlink global | Retira limpieza global; Quote usa temporal propio/finally; elimina printQuote de prueba | Inspección diff, no PDFs abiertos/generados | PARTIALLY FIXED | Código corregido; QA aislado de artefactos pendiente |
| USER-01 | OPEN: directorios globales | tenantDirectory, getUsers2 membresías company/branch, gates | Código de list/getUsers/getUsersDelete/getUsers2; index todavía Role::all, otros caminos legacy sin cierre | PARTIALLY FIXED | No habilitar CRUD legacy completo |
| PAY-01 | OPEN: inactivos ofrecidos | selectable/currentId en consumidores | Regresión: OrderService:209 y Proforma:1017 consultan columnas inexistentes; SELECT readonly reproduce SQLSTATE42S22/1054 en ambas | PARTIALLY FIXED | Esperar fix de esas consultas; no es migración nueva pendiente |
| CUSTOMER-01 | OPEN: GET crea | GET consulta; POST separado create_customer | Proveedor simulado, GET200 created=false/id=null, customers6→6 bajo READ ONLY; código POST con guard | FIXED — QA PASSED | Consumidor debe tratar propuesta sin ID; creación/duplicados QA pending |
| CUSTOMER-02 | Rutas test públicas | Retira test-ruc/test-dni | Registro de rutas/código y búsqueda runtime | FIXED — QA PASSED | Cierre de esas dos rutas, no certificación general proveedor |
| PUR-01 | Scope financiero/reporte ausente | Modelos/traits T/C fail-closed, reporte contextual y permisos | Migraciones Ran, queries scoped y dataset actual; OrderService sigue sin contexto | PARTIALLY FIXED | Listado financiero/crediticio read-only habilitado; no familia entera |
| METRIC-01 | Summary primer owner global | FinancialSummary T/C, branch opcional, grupos de moneda | Report real readonly en cuatro contextos; ver abajo | PARTIALLY FIXED | Resumen y drilldown seguros para integración read-only; no cerrar Dashboard entero |
| KARDEX-01 | Permiso ausente | Sin cambio DEV; entorno nuevo sí provisionado | DB + Gate en caché array PASS; caché compartida desactualizada | PARTIALLY FIXED | Smoke HTTP pendiente de entorno; no contar como fix DEV |
| GUIDE-01/02 y failure contract | OPEN | Ninguno | Sin delta ShippingGuide | NOT TOUCHED | Create remitente NO; store aislado NO dentro de este cierre |
| MAT-01/03/04/05 | OPEN | Ninguno | Sin delta Material/update request | NOT TOUCHED | Save sigue bloqueado: retacería/Items, ubicaciones, flags; MAT-01 diseño |
| INV-01, WAREHOUSE-01/02, TRANSFER-01, ASSET-01 | Estado previo | Ninguno | Sin delta de esos caminos | NOT TOUCHED | No reabrir ni cerrar |
| EXPENSE-01, OUTPUT-01/02, SUPPLIER-01, PERCENT-01, HR-01/02 | Estado previo | Ninguno sobre root cause | Diff acotado | NOT TOUCHED | WAIT FOR DEV donde correspondía |
| SERVICE-01/02/03 | Bloqueados | Sólo selector de plazo | Scope/usuarios/contrato servicio no resueltos; regresión PAY-01 | NOT TOUCHED | No integrar servicio |

Totales DEV: 7 fixed, 5 parcialmente corregidos, 3 still open de CASH revisados. KARDEX es un sexto parcial de entorno, separado de los cinco parciales DEV. Los grupos NOT TOUCHED no inflan esos totales.

### Focused QA / DEV Tests

Ningún test DEV incorporado. `CashMaintainersClosureTest --do-not-cache-result` se ejecutó con SQLite privado: **11 tests/121 assertions, 9 pass, 1 error y 1 failure**. Dos expectativas antiguas exigen los bugs ahora corregidos (XYZ aceptado y Undefined index). NO se anuncia suite verde ni se editaron tests. Deben actualizarse después con autorización de implementación.

| Finding | DEV Test | Coverage actual |
|---|---|---|
| CASH-01/05 | NONE | Validadores reales sin store + suite aislada anterior; banco por inspección, no persistencia |
| ENTRY-01 | NONE | Controller real, IDs propios/ajenos/inexistente en MySQL READ ONLY; no render de documentos |
| QUOTE-01 | NONE | View data real general/facturadas en tres contextos; conjunto facturadas vacío limita cobertura positiva |
| CUSTOMER-01 | NONE | Servicio externo simulado en memoria; conteo invariable, sin red |
| QUOTE-04/CUSTOMER-02 | NONE | Registro/retirada de rutas y referencias estáticas |
| DOC-02 | NONE | Static only; generación, colisión y limpieza de archivos pendientes |
| PAY-01 | NONE | SQL1054 reproducido por lectura equivalente a expresión nueva |
| PUR-01/METRIC-01/USER-01 | NONE | Scopes/guards inspeccionados y lecturas acotadas; sin QA de escrituras |

Lecturas temporales usaron conexión PDO fijada a MySQL `START TRANSACTION READ ONLY`, terminada en ROLLBACK. No fixtures reales ni cambio de roles. No HTTP end-to-end nuevo certificado.

### Current Dataset

44 entries: T1/C1=41, T1/C2=2, T3/C3=1 (incluye históricos/borrados; Entry1 no visible, no usado como caso positivo). Detalle positivo4/49/45. Sin contexto devuelve404 por guard.
order_purchase_finances=0, supplier_credits=0, credit_pays=0; cash_opening_funds=1. Dataset vacío permite integración de lectura pero no prueba positiva de detalle/restore financiero.
FinancialSummary(branch,null,null), IDs completos: T1/C1/B1=[1,2,5]; T1/C1/B8=[2,5,6]; T1/C2/B2=[]; T3/C3/B4=[]. Cajas generales2/5 se comparten entre branches de C1 por contrato. Moneda encontrada PEN; multimoneda verificada en código, no dataset USD positivo. Actividad/fondos por periodo, pendientes y sesiones como estado actual; no confundirlos. Consolidado de caja general se omite al filtrar branch. Esto valida frontera de lectura, no conciliación contable.

## Frontend Unlocked

### FRONTEND UNLOCKED NOW

| Priority | Screen | Previous blocker | Current evidence | Next action |
|---|---|---|---|---|
| 1 | Entry Detail | ENTRY-01 | Controller T/C, propio y cross-context probados | READY FOR READ-ONLY FRONTEND; excluir edición/documentos |
| 2 | Resumen financiero + drilldown de movimientos | METRIC-01 en summary | FinancialSummary contextual y rutas summaryIndex/summaryList readOnly con permission | READY FOR READ-ONLY FRONTEND; no promover Admin Cash mutador genérico |
| 3 | Créditos de proveedores: listado/pendientes | Frontera financiera PUR-01 | Scope SupplierCredit, CreditPay por padre, gates y esquema aplicado | READY FOR READ-ONLY FRONTEND; dataset vacío, pagos/asociaciones excluidos |
| 4 | Órdenes de compra financieras: listado | PUR-01 | ScopedFinancePurchaseCompany y permiso list AJAX | READY FOR READ-ONLY FRONTEND del listado; create/edit/show/lost tienen Audit::create y quedan fuera del QA estrictamente sin escritura |

Son cuatro superficies/familias; no contar Quotes ya integrada ni Kardex pendiente de smoke como nueva pantalla desbloqueada. Financieras necesitan contract audit de los consumers específicos al empezar, sin certificar mutaciones por esta revisión.

## Ready for Mutation QA

Sólo en DB de pruebas aislada: validación CashBox/Subtype corregida; creación Customer por nuevo POST; asociación única crédito/Entry y reglas FinancePurchaseInput/deletion_batch. El código y las cinco migraciones permiten preparar esos tests. **No se certifican pagos, emisión, stock, restore, cancelación ni concurrencia**. CASH-02/03/04 siguen condicionando casos de edición histórica. No ejecutar pruebas con registros compartidos.
ShippingGuide: **CREATE REMITENTE READY? NO** / **STORE READY FOR ISOLATED QA? NO** como salida de este paquete: GUIDE-01/02 sin fix; failure contract sin delta, no llamada Nubefact.
Material: **MATERIAL EDIT SAVE STILL BLOCKED? YES**, MAT-03/04/05 y diseño MAT-01 donde aplique.
Entry: **ENTRY DETAIL READY FOR READ-ONLY FRONTEND? YES**.
Cash Boxes/Subtypes: mejora a fixes de validación comprobados / QA aislado posible; no READY financiero completo.

## Still Waiting

| Module | Remaining finding |
|---|---|
| OrderService / Proforma | PAY-01 nuevo SQL1054; no migración pendiente que lo arregle |
| Cash histórico | CASH-02/03/04; CASH-06/07 no cerrados |
| ShippingGuide create/store | GUIDE-01/02 + failure contract no revalidado |
| Material save | MAT-03/04/05, MAT-01 diseño |
| Users legacy | USER-01 rutas restantes/roles globales |
| PDF | DOC-02 prueba de artefactos pendiente; DOC-01 infraestructura intacto |
| Kardex | Caché compartida + HTTP smoke; permiso/roles sí están en BD actual |
| Service / inventario legacy / Expense / Outputs | Root causes anteriores, sin cierre por este delta |

## Recommended Order

1. Entry Detail: blocker concreto eliminado, dataset propio/cross-context disponible y alcance reducido.
2. Resumen financiero y drilldown exclusivamente lectura: backend nuevo contextual, dataset disponible; separar integración visual de conciliación financiera.
3. Créditos de proveedores read-only: flujo ahora contextual; sin pago, archivo ni asociación.
4. Listado de órdenes financieras: no extender a GETs que registran Audit ni a documentos sin autorización adicional.

Revisión final: sólo este documento y el backlog modificados; CSS0; frontend/backend unchanged. Sin commit. `git diff --check`: PASS (sólo aviso de normalización LF/CRLF).
