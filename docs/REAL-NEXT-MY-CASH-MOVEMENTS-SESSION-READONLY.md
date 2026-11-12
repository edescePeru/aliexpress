# REAL NEXT — Mis movimientos + Sesión propia — READ-ONLY INTEGRATION

Roadmap C, fase 5. Fecha: 2026-11-12. Backend sin cambios. Sin commit ni escrituras comerciales.

## Contract Audit

Auditados CashBoxController (constructor, myIndex, myList, buildListResponse, adminIndex/adminList), CashRegisterController (session/getSessionMovements), CashRegularizationService, CashBox/CashRegister/CashMovement/CashBoxSubtype, rutas, los dos Blade, ambos scripts y partial/script de regularización. Se reutiliza Venti Next Financial/Operational List y Surface Spacing Standard; las referencias cash del baseline todavía son legacy, por lo que se aplica el patrón operacional aprobado en lugar de copiar su CSS antiguo.

Dos superficies distintas: Mis movimientos consulta movimientos propios del contexto; sesión muestra sólo la sesión abierta propia de una caja permitida. No se fusionan. No se modificó Entries ni ninguna fase cerrada.

## Route Matrix

Prefijo /dashboard. Todas pasan web/auth/check.user.enabled/password.changed/tenant.context.

| Route / GET | Tenant | Company | Branch | User | Permission | Read/Write |
|---|---|---|---|---|---|---|
| cashMovement.my.index / cash-movements/my | TenantScope | actual | actual/shared para cajas | sesiones propias | listCashMovementMy_cashBox | Read |
| cashMovement.my.list / cash-movements/my/list | TenantScope | movimiento actual | movimiento actual | Auth::id obligatorio | listCashMovementMy_cashBox | Read |
| cashRegister.session / cash-registers/session/{cashBox} | TenantScope/binding | actual | caja actual/shared; sesión actual | propia | sin permission adicional; ownership/contexto | Read |
| cashRegister.movements / cash-registers/{cashRegister}/movements/{pageNumber?} | TenantScope/binding | actual | actual | sesión propia | sin permission adicional; ownership/contexto | Read |
| cashMovement.admin.index/list | TenantScope | actual | actual | admite otros usuarios | listCashMovementAdmin_cashBox | Read, NO INTEGRADO |
| cashMovement.regularize | explícito + TenantScope | actual | actual | reglas del servicio | regularize_caja | POST, NO EJECUTADO |

Allowlist exclusivamente cashMovement.my.index y cashRegister.session. ALLOWLIST MATCH: YES. Se cargan venti.min.css y venti-next.css mediante layout existente. No añadido admin, financial.summary, register admin/index ni regularización independiente.

## Permissions

Diferencia importante respecto a la reauditoría: CashBoxController::__construct sí contiene actualmente permission:listCashMovementMy_cashBox para myIndex/myList y permission:listCashMovementAdmin_cashBox para adminIndex/adminList. Comprobado también con gatherMiddleware de rutas. Los permisos de mantenimiento list_cashBox/create_cashBox/etc. sólo aplican a sus acciones específicas; no se reutilizan artificialmente.

Las rutas de sesión/movimientos no exigen un permiso nominal extra; aplican contexto y ownership en controller. Regularización mantiene @can en partial, affordance según regularize_caja y pending sale/income, middleware y validación del servicio. No se inventan restricciones frontend ni se certifican permisos de mutación.

## My Movements Scope

CashMovement con TenantScope, company_id actual, branch_id actual y user_id = Auth::id. myList no acepta sustituir usuario por query; buildListResponse sólo procesa user_id en modo admin. Orden created_at DESC, id DESC. Join obligatorio CashBox y left joins CashRegister/User/Subtype/Branch. No se rescatan históricos sin company/branch ni se amplía la query para llenar tabla.

myIndex entrega cajas activas de company con branch actual o null, subtipos activos de company y IDs de cajas con sesión propia abierta en branch actual. El listado puede contener movimientos de cajas actualmente inactivas, aunque el selector sólo contiene activas; se conserva contrato.

## Own Session Scope

session vuelve a consultar caja por ID, company, is_active y branch actual/null. Busca la última sesión status=1 por company/branch/caja/usuario (TenantScope activo). Sin sesión devuelve redirect a Mis movimientos con warning; sin caja permitida, 404.

getSessionMovements revalida company/branch/user de CashRegister: 403 si no coincide, 422 si cerrada. Query de movimientos por tenant/company/branch/cash_register_id, sin filtro adicional movement.user_id: ownership se establece por sesión. No se cambia esa semántica. Página mínima 1, tamaño 10. No abre ni recupera sesiones históricas.

## Dataset

Revalidado con Laravel/PHP 7.3 en transacción READ ONLY y rollback, sin fixtures. Navegador con Owners autorizados complementa las lecturas.

| Contexto | Mis movimientos IDs | Sesiones propias abiertas |
|---|---|---|
| Owner EDESCE U4 / T1 C1 B1 | 265,264,263,262,261,260,259,258,257,256,255,253,252,251,250 | caja1 sesión31; caja2 sesión32; caja5 sesión29 |
| Owner EDESCE U4 / T1 C1 B8 | 254 | caja6 sesión30 |
| Owner EDESCE U4 / T1 C2 B2 | vacío | ninguna caja/sesión disponible |
| Owner Gamarra U7 / T3 C3 B4 | vacío | ninguna caja/sesión disponible |

Sesión31: IDs263,262,260,259,258,257,256,255; saldo backend PEN1290.00. Sesión32: ID265, PEN70.00. Sesión29 vacía, PEN0.00. Sesión30: ID254, PEN80.00. No se recalculan estos saldos.

## Legacy Unscoped

Inventario actual: cash_movements T1 sin company/branch=2; C1 sin branch=241; C1/B1=15; C1/B8=1. cash_registers C1 sin branch=23; B1=5; B8=1. LEGACY UNSCOPED — EXCLUDED para movimientos/sesiones, sin fallback por usuario. Las cajas con branch null tienen un contrato shared explícito distinto, no deben confundirse con sesiones históricas sin branch.

## Movement Types

Contrato de filtro: sale, income, expense únicamente. Se mantiene presentación Venta/Ingreso/Egreso; pending sale/income se presenta Regularizar como en contrato actual. regularization_source_id distingue Abono por regularización. No se inventan categorías regularize/commission: comisión es campo monetario y el abono conserva tipo original. Valores desconocidos en My conservan raw; Session mantiene su etiqueta backend actual.

## Balance Effect

regularize=0 indica pendiente. balance_effect=false representa original informativo regularizado en otra sesión; badge textual específico. null conserva semántica histórica backend (respuesta lo normaliza a true). CashMovement::affectingBalance exige regularize y balance_effect null/true; impactAmount devuelve cero para pendiente o false, luego amount_regularize o amount. No se suman importes ni se infieren signos por tipo desde frontend. Regularización tras cierre conserva original/arqueo y crea abono con source identity en sesión receptora según servicio. Sin cambios en posting/reintento/comisión.

## Currency

CashBox tiene currency; CashRegister/CashMovement no tienen moneda propia expuesta por estos contratos. My JSON tampoco entrega currency. Se muestra «Moneda de caja» desde las cajas ya enviadas en Blade, por cash_box_id; si no está disponible se muestra «No informada». No se consulta backend adicional ni se inventa PEN. Esto describe moneda actual de caja, no certifica un snapshot histórico de moneda.

Sesión muestra cashBox.currency junto al saldo y resumen; updateBalanceUI conserva esa moneda tras callbacks existentes en lugar del prefijo PEN fijo. Sólo presentación, sin cambiar payload o cálculos. No sumas multimoneda ni conversiones. Dataset positivo sólo PEN; USD requiere dataset futuro.

## Filters

My: q (description/observation/box/user/subtype/branch), cash_box_id, type, subtype_id (incluye none), date_from/date_to (whereDate created_at, ISO nativo), page. Backend admite per_page 1–100 y default10; UI mantiene10. No filtro moneda nuevo. Cambio de filtro/búsqueda vuelve a1. Metadatos paginator existentes. Contador de solicitudes evita renderizar respuestas obsoletas; error HTTP se distingue de vacío y limpia resultados previos.

Session: sólo pageNumber; no se agregan filtros. Metadatos currentPage/totalPages/startRecord/endRecord/totalRecords/totalFilteredRecords. Se preservan hooks/paginación y se evita salto de ancla. Error backend visible, incluido cierre concurrente.

## List Mapping

N°, fecha/hora, caja, tipo, subtipo, estado, moneda de caja, monto, abonado, comisión, descripción/observación y acciones existentes. Campos monetarios right, fecha/ID/estado/tipo center, texto left. Sin ingresos/egresos calculados. Mismo data-id/date/cashbox/type/subtype/status/amount/amount_regularize/commission/desc/buttons; se añade data-currency de presentación.

## Session Mapping

Caja y sesión real; usuario autenticado (ownership validado), apertura opening_time, estado status y current_balance/currency. Movimientos con tipo/subtipo/estado/fecha/importes/descripción y acciones originales. Se reemplaza HTML visual legacy $state por presentación del status y el mismo hook btn-arqueoCashRegister, conservando operación/modal. Todos los hidden y forms/CSRF/payloads permanecen. No se representa arqueo histórico inexistente.

## Shared CashBox

CashBox.branch_id=null permite usar la caja desde branches de la misma company. NO implica compartir sesiones/movimientos: éstos siguen con branch actual y ownership. BCP2 e Interbank5 disponibles en B1/B8; en B8 no hay sesión propia y session devuelve302. No se pulsaron botones que inician sesión para probarlo; lectura directa del controller.

## Action Matrix

| Action | Contrato | Permission/context | Estado QA |
|---|---|---|---|
| Buscar/filtrar/paginar My | cashMovement.my.list GET | listCashMovementMy_cashBox + contexto/user | probado |
| Ver caja con sesión abierta | cashRegister.session GET | caja activa + sesión propia | probado |
| Movimientos sesión | cashRegister.movements GET | contexto/ownership/status | probado |
| Abrir caja | cashRegister.open POST | validaciones existentes | conservado, OUT OF SCOPE |
| Ingreso/Egreso | cashRegister.income/expense POST | validaciones existentes | conservado, OUT OF SCOPE |
| Arqueo/cierre | cashRegister.arqueo POST | validaciones existentes | conservado, OUT OF SCOPE |
| Regularizar | cashMovement.regularize POST | regularize_caja + servicio | conservado, MUTATION QA OUT OF SCOPE |
| Imprimir venta / ver factura | URLs existentes por sale_id/entry_id | guards del destino | conservado, no abierto/certificado |
| Admin/summary | rutas existentes | ver hallazgo admin | sin allowlist/integración |

No se ocultaron botones operativos inventando permisos; preservados por instrucción explícita. El alcance READ PATH describe lo integrado/verificado, no convierte el producto en un modo técnicamente incapaz de escribir.

## Regularization Boundary

Partial cashRegister._regularization y regularization.js INTACTOS. Servicio INTACTO. Revalida usuario habilitado/no Platform Admin, permission, memberships/company/branch activos, snapshot de contexto, movimiento/caja/subtipo, sesión original y receptor único. Diferencia por comisión sólo si canal permite. Transacción/reintentos/identity/posting no alterados. No se abrió ni envió regularización.

## Admin Authorization Finding

La premisa «no existe gate específico» ya no coincide con HEAD: constructor contiene permission:listCashMovementAdmin_cashBox sólo para adminIndex/adminList; gatherMiddleware lo confirma. Ausencia del gate: RESOLVED STATICALLY en código actual. ADMIN sigue NOT INTEGRATED por alcance. No se visitó ni probó dataset administrativo ni usuarios con/sin capacidad. ADMIN AUTHORIZATION — BACKEND SYNC OPEN para cerrar reauditoría efectiva/matriz de concesión; no se afirma que Owner por sí mismo autorice.

## Documents

URLs existentes: /dashboard/imprimir/documento/venta/{sale_id} => puntoVenta.print con SaleListGuard:print,route,id; /dashboard/factura/compra/ver/{entry_id} => invoice.show con list_invoice y financeEntries()->findOrFail. Se preservan contratos, sin abrir documentos ni descargas. Autorización final/public paths/documentos no certificados en esta fase: DOCUMENT SECURITY — BACKEND/INFRA OPEN para su reauditoría específica.

## Responsive QA

0 CSS nuevo; sin vendor/global overrides. Eliminados estilos inline legacy de colores por fila y tipografía; badges textuales preservados. Wrappers next-form-section/next-list-toolbar/next-toolbar-actions/next-list-summary/next-list-pagination y next-data-table existentes. Fechas nativas preservadas; tablas con scroll local.

| Width | My | Session | Global overflow |
|---:|---|---|---|
|320|PASS|PASS|NONE|
|375|PASS|PASS|NONE|
|390|PASS|PASS|NONE|
|576|PASS|PASS|NONE|
|768|PASS|PASS|NONE|
|992|PASS|PASS|NONE|
|1200|PASS|PASS|NONE|
|1440|PASS|PASS|NONE|

DOM medido en los ocho tamaños; inspección/capturas desktop y móvil. Conservados botones de operaciones sin ejecutarlos. No se certifican formularios/modales de mutación. Consola final sin errores. Capturas fuera del repo: C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/cash-read/ (my-desktop.png, session-desktop.png, session-mobile.png).

## Cross-context QA

PHP temporal fuera del repo, Laravel autenticado/InitializeTenantContext, DB READ ONLY y rollback; acciones de lectura y resolución de binding. Navegador confirma Gamarra vacío, C2 vacío, C1/B1 positivo, paginación segunda página [255,253,252,251,250], filtro income [252,251,250], búsqueda inexistente vacía, sesión31 y B8 listado/sesión30 ID254.

- tenant/company/branch/user query spoof999 no cambia dataset propio.
- caja inexistente en filtro devuelve vacío; q inexistente vacío; filtros sale/income/expense/noneSubtype respetan IDs propios.
- Caja6/B8 solicitada desde B1:404. C2 solicita cajas1/2/5/6:404. Gamarra solicita esas cajas T1:404. ID999999:404.
- Registro30/B8 desde B1:403. Registros30/31 desde C2:403. Gamarra binding T1:404. Registro inexistente:404.
- Registros históricos de otros usuarios/contextos:403. Propios cerrados27/28 desde B1:422. Sin dataset de otro usuario con sesión en MISMA company/branch; ownership está auditado, caso positivo de negación aislada sólo por user queda pendiente de dataset.
- Caja compartida2/5 desde B8 sin sesión propia:302 a My; no se abre sesión. Gamarra carece de caja propia, por tanto valida vacío y caja ajena404, no se fabrica un302 con datos nuevos.
- My no entrega históricos null; C1B1 y B8 IDs disjuntos. Sesiones propias abiertas dan200.

## Backend Sync Notes

[OPEN]
Point: moneda no expuesta por fila del JSON My ni snapshot histórico.
File / endpoint: CashBoxController::buildListResponse.
Impact: caja inactiva/no disponible muestra No informada; no se certifica moneda histórica.
What backend needs to define or correct: contrato explícito currency de movimiento/caja y su estabilidad histórica.
Frontend can continue: Yes, sin fallback PEN ni cálculos.

[RESOLVED — STATIC]
Point: ausencia de gate administrativo en reauditoría anterior.
Final contract: permission:listCashMovementAdmin_cashBox presente en constructor y middleware efectivo.
Date: 2026-11-12. Sigue pendiente QA efectivo de matriz administrativa; admin fuera del alcance actual.

[OPEN]
Point: documentos y mutaciones financieras no certificados.
File / endpoint: destinos documentales, open/income/expense/arqueo/regularize.
Impact: affordances conservados no equivalen a QA de operaciones.
What backend needs to define or correct: cerrar auditorías específicas y datasets negativos requeridos antes de certificación.
Frontend can continue: Yes, sólo lectura.

## Revisit Triggers

Cambios de scope/ownership, gate admin, currency por movimiento, cajas shared, posting/regularization identity, nuevos tipos, permisos o dataset de sesión de otro usuario en misma branch. Cualquier mutación o documentos requiere fase propia; no reabrir fases cerradas sin regresión.

## Files / Validation

Modificados config/venti-next.php; resources/views/cashBox/my_index.blade.php; resources/views/cashRegister/session.blade.php; public/js/cashBox/my_index.js; public/js/cashRegister/session.js; este documento. Sin cambios en controllers/models/services/routes/CSS/regularization.js/partial. node --check ambos JS, php7.3 artisan view:cache y git diff --check ejecutados para cierre. Sin commit.

## Final Status

- My Cash Movements — FRONTEND INTEGRATED — READ PATH
- Own Cash Session — FRONTEND INTEGRATED — READ PATH
- Context Isolation — QA PASSED para casos documentados; caso aislado otro usuario misma branch pendiente dataset
- Legacy Unscoped — EXCLUDED
- Admin Movements — NOT INTEGRATED / AUTHORIZATION QA PENDING (gate actual confirmado)
- Open/Close/Income/Expense/Arqueo — OUT OF SCOPE
- Regularization — MUTATION QA OUT OF SCOPE
- Backend — UNCHANGED

### Polish desktop posterior

Cajas disponibles trasladadas a la derecha del título Listado operativo mediante next-page-toolbar/next-toolbar-actions, sin franja adicional. Filtros reunidos en form-row Bootstrap con controles compactos: una fila comprobada a 1200, 1440 y 1920; adaptación apilada bajo xl. Sin overflow global en 320/375/390/576/768/992/1200/1440/1920. Sólo Blade, sin CSS nuevo ni cambios JS/backend/hooks. Compilación Blade y diff check PASS. Evidencia: cash-read/my-inline-desktop.png, junto a las capturas anteriores.
