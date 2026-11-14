# REAL NEXT — Cash Boxes & Subtypes

> Estado vigente 2026-11-12: ver **Mutation QA — Roadmap C / 8B** al final. Suite aislada: 11 tests / 132 assertions PASS; dependencia POS de mantenedores **NOT READY**, por moneda, protección histórica y payload incompleto. Los estados PENDING e inventarios siguientes pertenecen a la auditoría original.

Fecha: 2026-11-05. Base auditada: `ffe0974` (front sales list read only).

- Cash Boxes: **FRONTEND INTEGRATED**.
- Cash Box Subtypes: **FRONTEND INTEGRATED**.
- Mutation QA: **PENDING**.
- POS Operational Selection: fuera de implementación; auditoría documental solamente.
- CSS nuevo: **0**. Backend comercial, permisos y rutas: sin cambios. Sin commit.

## Contract Audit

Fuentes actuales: `CashBoxController`, `CashBoxSubtypeController`, modelos `CashBox`, `CashBoxSubtype`, `CashRegister`, `CashMovement`, `CompanyBankAccount`, `TenantContext`, `TenantScope`, `BelongsToTenant`, `InitializeTenantContext`, servicios `PosCashService`/`PosScreenOptions`, `routes/web.php`, ambos Blade/JS y configuración Venti Next. No se dedujo el contrato desde documentos anteriores.

No hay FormRequest específico: validación privada en los controllers. Create/Edit son modales en cada index, no páginas GET independientes. Los controladores no delegan estos CRUD a un servicio de mantenimiento.

## Routes

Prefijo real: `/dashboard`. Se conservan verbos, nombres y acciones.

| Módulo | GET vista | GET datos | POST crear | POST editar | POST estado |
|---|---|---|---|---|---|
| Cajas | `/cash-boxes` / `cashBox.index` | `/cash-boxes/list` / `cashBox.list` | `/cash-boxes/store` / `cashBox.store` | `/cash-boxes/update` / `cashBox.update` | `/cash-boxes/toggle` / `cashBox.toggle` |
| Subtipos | `/cash-box-subtypes` / `cashBoxSubtype.index` | `/cash-box-subtypes/list` / `cashBoxSubtype.list` | `/cash-box-subtypes/store` / `cashBoxSubtype.store` | `/cash-box-subtypes/update` / `cashBoxSubtype.update` | `/cash-box-subtypes/toggle` / `cashBoxSubtype.toggle` |

Acciones respectivas: `index`, `listar`, `store`, `update`, `toggle`. No hay delete ni soft-delete expuesto por estos CRUD.

**ALLOWLIST MATCH: YES** para `cashBox.index` y `cashBoxSubtype.index`, añadidas a `config/venti-next.php`. Las dos vistas cargan `venti.min.css`, `venti-next.css` y JS compartido del shell. Los ocho endpoints JSON no renderizan layouts y no necesitan allowlist CSS.

## Permissions

Guard común G: grupo web/dashboard con autenticación, usuario habilitado, contraseña vigente y `tenant.context`; controller con `auth` y middleware `permission`. POST conserva CSRF. TenantContext exige company activa; no se toma tenant/company del payload. El frontend muestra capacidades, pero la autorización sigue en servidor.

| Operación | Ruta relativa a /dashboard | Permiso | Tenant | Company | Branch | Guard | Estado |
|---|---|---|---|---|---|---|---|
| Listar cajas | GET cash-boxes y cash-boxes/list | list_cashBox | contexto/Scope | activa | company-wide | G | GET 200 Owners |
| Crear caja | POST cash-boxes/store | create_cashBox | contexto | activa | null o misma company | G + validación | auditado; persistencia pendiente |
| Editar caja | POST cash-boxes/update | edit_cashBox | Scope | ID en company | misma company; guard sesión | G + validación | rechazo IDs probado; éxito pendiente |
| Activar/desactivar caja | POST cash-boxes/toggle | disable_cashBox | Scope | ID en company | company-wide | G + sesión abierta al desactivar | rechazo IDs probado; éxito pendiente |
| Listar subtipos | GET cash-box-subtypes y cash-box-subtypes/list | list_cashBoxSubtype | contexto/Scope | activa | company-wide | G | GET 200 Owners |
| Crear subtipo | POST cash-box-subtypes/store | create_cashBoxSubtype | contexto | activa | no campo propio | G + validación/conflictos | auditado; persistencia pendiente |
| Editar subtipo | POST cash-box-subtypes/update | edit_cashBoxSubtype | Scope | ID en company | no campo propio | G + validación/conflictos | rechazo IDs probado; éxito pendiente |
| Activar/desactivar subtipo | POST cash-box-subtypes/toggle | disable_cashBoxSubtype | Scope | ID en company | no campo propio | G | rechazo IDs probado; éxito pendiente |
| Delete | No existe | delete_cashBox / delete_cashBoxSubtype están en catálogo | N/A | N/A | N/A | sin endpoint | no añadir acción |

Ambos Owners tienen create/edit/toggle en los dos módulos. No se modificaron roles ni se verificó una cuenta real sin permisos; esa matriz negativa no se presenta como ejecutada. No se asumió bypass de Owner ni Platform Admin.

## Tenant / Company / Branch Scope

Mantenimiento explícitamente company-wide: no filtra por branch activa. Caja general = `branch_id=null`; caja local = branch perteneciente a company. La lista de branches del modal no exige estado activo. Esto se conserva, sin imponer reglas del selector POS.

| Cuenta / medio | Tenant | Company | Branch | Opciones branch | Cajas/subtipos visibles | Cuentas bancarias |
|---|---:|---:|---:|---|---|---|
| Owner EDESCE / navegador + harness read-only | 1 | 1 | 1 | 1 | 0 / 0 | solo company 1 |
| Owner EDESCE / harness read-only | 1 | 5 | 6 | 6, 7 | 0 / 0 | ninguna |
| Owner Gamarra / navegador + harness read-only | 3 | 3 | 4 | 4 | 0 / 0 | ninguna |

El navegador cambió de Owner EDESCE / EDESCE CONSULTORA / Local Principal a Owner Gamarra / Gamarra Moda / Local Principal. IDs corroborados en contexto del harness autenticado. La company 5 se verificó solamente por harness, no se afirma una navegación de cambio de company.

Dataset: 3 cajas y 4 subtipos en total, **todos sin company_id**, excluidos por los contratos actuales. No se reasignaron ni migraron. No existen cajas/subtipos company-scoped para una comparación positiva cruzada EDESCE/Gamarra.

Pruebas negativas sin escrituras: branch de otra empresa rechazada por validateCashBox; cuenta ajena rechazada en contextos con cuenta ajena disponible (en company 1 se usó ID inexistente); caja legacy rechazada por validateSubtype. Update/toggle de caja/subtipo legacy y de ID inexistente producen ModelNotFoundException antes de guardar en los tres contextos. Son invocaciones de métodos bajo transacción READ ONLY, no pruebas POST HTTP completas. La separación positiva con registros company-scoped permanece pendiente.

## Cash Box Model

Campos de mantenimiento: name, type, branch_id, uses_subtypes, position, company_bank_account_id, currency. DTO también incluye id, is_active, scope_label, branch_name, datos bancarios, campos legacy y current_balance. No se añade edición de saldo.

- `name`: obligatorio, máximo 120; `type`: cash/bank; position entero >=0.
- branch nullable, validada en company. Cambiar branch con CashRegister status=1 de la caja/company se rechaza.
- bank: exige cuenta activa de la company; currency procede de esa cuenta. No se toma la moneda enviada como autoridad.
- cash: borra cuenta vinculada y fuerza uses_subtypes=false; moneda opcional de longitud 3, normalizada a mayúsculas.
- Campos legacy bank_name/account_label/account_number_mask se limpian en validación.
- Create establece tenant/company desde contexto, is_active=true y current_balance=0.
- Update no permite alterar tenant/company/current_balance.
- Relaciones: company, branch, subtypes, registers, settlements, bankAccount; movimientos contienen referencias operativas a caja.

## Subtype Contract

- Company-scoped, con `cash_box_id=null` (global de esa company) o una caja específica.
- Opciones: cajas de company activas con uses_subtypes=true. No hay condición explícita type=bank en ese query; el contrato normal de cajas fuerza uses_subtypes=false para cash.
- code obligatorio <=50, minúsculas, espacios a `_`, eliminación de caracteres fuera de `[a-z0-9_-]`; vacío tras normalizar se rechaza. Name obligatorio <=120; position entero >=0.
- is_deferred boolean; requires_commission boolean, forzado false si no es diferido. El frontend mantiene este comportamiento y la etiqueta de comisión expresa su función en regularización.
- Crear/editar rechaza conflictos de código global/específico y duplicados en mismo alcance dentro de company, incluyendo registros inactivos; edición excluye su propio ID.
- Create activo; toggle cambia is_active. No existe guard específico de sesiones/movimientos en toggle de subtipo.
- Relaciones: company, cashBox, cashMovements. `availableForCashBox` incluye globales o la caja indicada; debe combinarse con el contexto y estado del consumidor.

## Dependencies

| Situación | Contrato observado | Tratamiento frontend |
|---|---|---|
| Caja con sesión abierta | Bloquea desactivar y cambiar branch | Mostrar error backend; no inventar otro bloqueo |
| Caja con movimientos/historial | No hay veto explícito de mantenimiento basado en movimientos | Mantener contrato; revisión backend pendiente |
| Cambiar tipo/cuenta/moneda/uses_subtypes | No tiene el mismo guard de sesiones que branch | No añadir restricciones en JS |
| Cuenta inactiva/ausente del catálogo al editar | No aparece entre opciones nuevas; validación exige cuenta activa | Conservar valor persistido identificado como no disponible, sin convertirlo silenciosamente a vacío |
| Subtipo vinculado a caja inactiva/no elegible | No aparece en catálogo; validador rechaza ese vínculo | Conservar vínculo mostrado; servidor decide al guardar |
| Desactivar subtipo con referencias | Toggle no consulta sesiones/movimientos | Sin simulación de bloqueo frontend |
| Concurrencia de apertura y cambio de caja | No se observó transacción/lock que abarque check y update de estos guards CRUD | Política/QA pendiente |

## CRUD Mapping

Patrón: **Material Configuration CRUD / Operational Configuration — REUSE WITH CONTRACT ADAPTATION**. Reutiliza shell, next-page-heading, toolbar, next-operational-list, next-data-table, acciones por fila con portal compartido, badges, paginación y next-aux-modal/next-aux-modal-form. No se modifica CSS ni JS compartido.

| Superficie | Integración | Contrato conservado |
|---|---|---|
| Listado | Toolbar, resumen, tabla compacta, scroll local, empty/error diferenciados | GET q/page; subtipo añade cash_box_id |
| Cajas | ID/nombre/tipo/alcance/subtipos/cuenta/moneda/estado/acciones | DTO actual; sin columnas inventadas |
| Subtipos | ID/alcance/código/nombre/diferido/comisión/orden/estado/acciones | DTO actual |
| Formularios | Modal aprobado, labels, cierre/foco, `wrap_currency` faltante corregido | IDs/names/rutas/payloads/confirmaciones existentes |
| Acciones | Editar y activar/desactivar según capacidades | mismos POST, mismos IDs, sin delete |
| Paginación | Diseño compartido; secuencia/abort de GET para evitar respuestas antiguas | current_page/last_page/total/from/to |

Alineación: nombres/alcance/cuenta a izquierda, ID/código/orden/flags/estado/acciones centrados. Moneda es código PEN/USD y se centra conforme al requerimiento de esta fase; no representa importe monetario.

Cajas busca q en nombre, datos bancarios legacy, moneda y branch.name. Subtipos en código/nombre/caja; cash_box_id vacío=todos, global=null, ID=caja específica. Orden real position/id; etiqueta corregida. per_page default10, admite1..100; frontend mantiene el tamaño existente. Respuesta `{data,meta:{current_page,last_page,total,from,to,per_page}}`.

Payload caja sin cambios: id,name,type,branch_id,uses_subtypes,company_bank_account_id,currency,position,_token. Subtipo: id,cash_box_id,code,name,position,is_deferred,requires_commission,_token. Toggle: id,_token. No tenant/company añadidos.

## POS Relationship

Auditoría documental, no operación POS: PosCashService aplica tenant/company, activo y branch actual o general. Selección operativa requiere una sesión abierta adecuada al usuario/company/branch; se valida moneda/compatibilidad, y los subtipos deben ser activos y globales o correspondientes a caja. PosScreenOptions utiliza la moneda de configuración financiera. Preparación de pago usa locks y reglas de diferido/regularización. No se trasladan esas reglas al CRUD ni se abren/cerran sesiones.

## Responsive QA

| Ancho | Lista cajas | Lista subtipos | Modal caja bancaria | Modal subtipo diferido | Overflow global |
|---:|---|---|---|---|---|
| 320 | PASS | PASS | PASS | PASS | 0 |
| 375 | PASS | PASS | PASS | PASS | 0 |
| 390 | PASS | PASS | PASS | PASS | 0 |
| 576 | PASS | PASS | PASS | PASS | 0 |
| 768 | PASS | PASS | PASS | PASS | 0 |
| 992 | PASS | PASS | PASS | PASS | 0 |
| 1200 | PASS | PASS | PASS | PASS | 0 |
| 1440 | PASS | PASS | PASS | PASS | 0 |

Medición DOM en navegador: document.scrollWidth=innerWidth, modales y selects dentro del viewport, tablas anchas con scroll local. Matriz real con Owner EDESCE y datos vacíos; matriz adicional de tablas pobladas en harness de presentación sintético sin transporte de negocio. Inspección visual de modales desktop/móvil. Gamarra: comprobación funcional de ambas vistas y modales, sin repetir cada ancho por cuenta. No confundir presentación sintética con datos de negocio o validación de persistencia.

## Functional QA

- GET index/list reales 200 en los tres contextos del harness; q sin resultados, page2 y per_page1 preservan meta backend.
- Navegador Owners EDESCE/Gamarra: opciones branch/account aisladas, estados vacíos, apertura/cierre Create, cash/bank, búsqueda y alcance global. Diferido off limpia comisión.
- Harness de presentación externo al repo: filas/flags activo-inactivo, acciones Editar/Activar/Desactivar, Edit conserva cuenta/branch/caja ausentes del catálogo, página2 y estado de error de consulta. Escape del menú restaura foco al trigger y aria-expanded=false.
- Sin errores JS capturados en las vistas reales. PHP 7.3.33: render real index 200; view:cache. Node --check para ambos JS y git diff --check.
- La primera pasada de render detectó una comilla faltante en @can de ambos nuevos toolbars; corregida y repetida la pasada con 200. view:cache por sí solo no detectaba ese error de PHP compilado.
- Artefactos locales externos: cash-config-readonly.php, cash-presentation-render.php, sub-presentation-render.php, cash-presentation-shim.js y cash-boxes-final.png en la carpeta de evidencia de esta sesión. No instalados ni referenciados desde runtime productivo.

## Mutation QA

**MUTATION QA PENDING**. No se crearon, editaron, activaron/desactivaron ni eliminaron cajas/subtipos reales. No se tocaron saldos, sesiones ni movimientos. El harness DB usa START TRANSACTION READ ONLY / ROLLBACK, auth/session/cache en memoria; invoca validadores y rutas GET. Los métodos update/toggle se probaron solo con IDs que fallan antes de escribir. No se presentan estos rechazos como validación de mutaciones exitosas.

Pendiente: persistencia correcta, unicidad/conflictos con datos company-scoped, guards con sesiones abiertas reales y concurrencia; permisos negativos con cuenta apropiada. No se encontraron tests específicos existentes de estos CRUD para ejecutar la matriz completa sin inventar datos compartidos.

## Backend Sync Notes

[OPEN] CB-01 — Política de cambios con dependencias.
File / endpoint: CashBoxController update/toggle/validateCashBox; CashBoxSubtypeController update/toggle.
Impact: branch/desactivación de caja tienen guard de sesión; tipo/cuenta/moneda/subtipos e historial no tienen restricciones equivalentes.
What backend needs to define or correct: confirmar política para cambios con sesiones/movimientos y protección concurrente. No se afirma que todo cambio deba bloquearse.
Frontend can continue: Yes. Compatible con contrato actual; errores backend conservados.

[OPEN] CB-02 — Dataset local legacy y cobertura de mutaciones.
File / endpoint: cash_boxes/cash_box_subtypes y endpoints CRUD.
Impact: todos los registros locales carecen de company; no se puede probar éxito ni aislamiento positivo entre cajas company-scoped de ambos tenants.
What backend needs to define or correct: disponer de dataset QA autorizado o suite aislada; cualquier migración legacy requiere decisión backend. No se reasignan registros desde frontend.
Frontend can continue: Yes. Empty state correcto; MUTATION QA PENDING.

## Revisit Triggers

- Nuevos contratos de caja/subtipo, permisos o respuesta paginada.
- Política backend de tipo/moneda/cuenta/diferido con sesiones o historial; guards concurrentes.
- Dataset company-scoped autorizado o tests aislados disponibles.
- Cambios de contexto entre pestañas: esta fase conserva resolución del contexto de sesión existente, sin añadir un contrato nuevo de snapshot al payload.
- Cualquier futura fase POS debe auditar su selección operativa separadamente.

## Files Changed

1. config/venti-next.php — allowlist de las dos vistas.
2. resources/views/cashBox/index.blade.php — patrón visual y modal.
3. resources/views/cashBoxSubtype/index.blade.php — patrón visual y modal.
4. public/js/cashBox/index.js — integración DOM/acciones/GET/foco.
5. public/js/cashBoxSubtype/index.js — integración DOM/acciones/GET/foco.
6. docs/REAL-NEXT-CASH-BOXES-SUBTYPES.md — contrato, QA y límites.

Sin cambios en controllers, modelos, servicios, middleware, scopes, rutas, CSS ni configuración comercial persistida.

## Mutation QA — Roadmap C / 8B (2026-11-12)

Esta actualización sustituye el estado histórico MUTATION QA PENDING y el inventario vacío de la auditoría original, sin borrar aquella evidencia. Resultado: **QA de contratos ejecutado en DB aislada; cierre de seguridad NOT READY**. Un test de caracterización que reproduce una vulnerabilidad funcional no significa que la regla sea segura.

### Dataset Strategy

**TEST DB**, anunciado antes de ejecutar. `tests/Feature/CashMaintainersClosureTest.php` configura conexión exclusiva `cash_qa_memory`, SQLite `:memory:`, antes de schema/fixtures. Aserción explícita de database=:memory:, sesión/cache array. Fixtures sintéticos con nombres QA CASH BOX / QA CASH SUBTYPE y contextos 1/1/1, 1/1/8, 1/2/2 y 3/3/4. Los IDs representan la matriz solicitada, no copias ni modificaciones de datos reales.

Referencias CashRegister/CashMovement son filas sintéticas mínimas en memoria para probar guards del mantenedor. No se llamó open/close/income/expense/post/regularize/prepare ni se realizó ninguna venta/pago. Schema mínimo focalizado: no certifica FKs, concurrencia ni comportamiento MySQL de producción. No se ejecutaron migraciones.

### Scope — Field / Authority / Validation / Scope

| Field | Authority | Validation | Scope |
| --- | --- | --- | --- |
| CashBox tenant_id/company_id | TenantContext servidor | Ignora valores enviados; update busca ID en company con TenantScope | Company actual dentro de tenant |
| branch_id | Cliente elige dentro de company | nullable integer; existencia en company; cambiar con sesión abierta rechaza 422 | null=company compartida; ID=local |
| name/type/position | Payload permitido | required string max120; cash/bank; entero >=0 | Mantenedor company |
| currency efectivo | Payload | nullable string size3, trim/uppercase; NO whitelist | Caja; no conversión |
| company_bank_account_id | Cliente, validado servidor | bank exige cuenta activa de company y tenant; cash la elimina | Company |
| currency bancaria | CompanyBankAccount | Sobrescribe currency cliente con moneda de cuenta | Caja/cuenta |
| uses_subtypes | Payload boolean normalizado | cash fuerza false | Caja bancaria operacional |
| is_active/current_balance | Servidor al crear | true / 0; update no acepta cambios de estos campos | Toggle independiente para estado |
| Subtype tenant/company | TenantContext | No se acepta autoridad cliente; IDs filtrados | Company |
| cash_box_id subtype | Payload | null/global=company; específica exige caja activa de company con uses_subtypes | Global company o caja específica |
| code/name/position subtype | Payload | code max50 normalizado a minúscula, espacios a _, caracteres permitidos; name max120; position >=0 | Duplicados/conflictos por company |
| is_deferred/requires_commission | Payload normalizado boolean | No diferido fuerza comisión=false; sin veto por referencias | Semántica funcional del canal |
| moneda/default subtype | No existen campos propios | No inventados | Moneda de caja/operación; no default obligatorio |

No FormRequests dedicados: validaciones privadas de ambos controladores. Modelos BelongsToTenant; CashBoxSubtype::availableForCashBox solo añade global/caja, requiere filtros company/activo del consumidor.

### Create

CashBox: 201, tenant/company desde servidor pese a payload ajeno; saldo inicial 0, activa true, cash sin subtipos. Bank usa moneda de cuenta propia aunque cliente mande USD. Branch de company 2, tenant 3 o inexistente: 422; bank sin cuenta, cuenta ajena/inexistente: 422; tipo inválido, nombre vacío y moneda con longitud inválida: 422. No se crean filas en rechazos. Company/tenant manipulados se ignoran y se conserva contexto, no necesariamente provocan 422.

Subtype: inmediato y deferred/comisión persisten y se leen correctamente (201). Code normalizado; comisión anulada para inmediato. Duplicado mismo alcance y conflicto global/específico: 422, incluye ambas direcciones. Código vacío tras normalizar, nombre vacío, caja inexistente/ajena/inactiva/sin subtipos: 422. Mismo código en otra company permitido. Crear queda activo independientemente de cliente.

### Update

CashBox: nombre permitido (200), repetir el mismo update conserva valores; tenant/company/saldo no cambian por payload. Se administra una caja de branch 1 desde contexto branch 8 de la MISMA company: permitido por contrato del mantenedor. Cambio a compartida con sesión abierta: 422; con sesión cerrada: 200. No es autorización operativa para usar esa caja en POS.

Subtype: nombre, deferred y comisión persisten (200). Cambiar deferred con referencias también es aceptado: se documenta como fallo de protección histórica, no PASS de seguridad. No se implementó workaround frontend.

### Toggle

CashBox con sesión status=1: 422, permanece activa. Con sesión cerrada y movimiento histórico sintético: 200, inactiva; otra llamada la reactiva. Subtipo sin referencias y con referencias: toggle 200 sin veto de uso. No hay default/required guard en subtipo.

Toggle es inversión, **no idempotente**: repetir cambia nuevamente el estado. No se añadió semántica set-active ni token de idempotencia. El guard de sesión es check-then-save sin lock/transacción que lo coordine con aperturas concurrentes; carrera pendiente de revisión backend.

### Shared/Local

Queries reales de PosCashService::boxes y FinanceInvoiceContext::boxes probadas sin ejecutar pagos: en branch 8 excluyen caja local branch 1 y admiten shared company y local branch 8. Company 2 y tenant 3 no reciben cajas company 1. Shared nunca equivale a tenant-global. El mantenedor sigue company-wide y no requiere que branch destino sea la activa.

### Currency

**NOT CLOSED.** Cash efectivo admite XYZ (201) por size:3; también permite cambiar currency a USD (200) con sesión abierta y referencia histórica. El frontend solo ofrece PEN/USD, pero no protege API. No se modificó backend: se requiere definir/validar catálogo y política de edición con referencias. Sin conversión o remediación automática.

POS exige caja.currency igual a venta; FinanceInvoiceContext filtra moneda de factura; CashRegularizationService compara moneda de venta contra caja para fuentes de venta y usa moneda de caja en su resultado. Subtipo no tiene moneda. CashRegister no aporta aquí una moneda inmutable que legitime cambiar la caja durante la sesión. La asignación bancaria sigue fuente CompanyBankAccount; estos mantenedores no certifican sincronización posterior al editar la cuenta bancaria.

### Deferred

Persistencia y lectura flags **PASS**. PosCashService::subtypes excluye inactivos, incluye globales/específicos del contexto; FinanceInvoiceContext::subtypes además excluye diferidos. Probado sobre fixtures aislados. CashRegularizationService exige subtipo activo y diferido correspondiente a caja/contexto. DeferredSaleRefund consulta is_deferred actual además de indicadores del movimiento; SalePartialPaymentController usa is_deferred al generar regularize y vuelve a consultarlo al cancelar. Solo inspección de esos flujos transaccionales, no ejecución.

### Historical Semantics

**BACKEND SYNC OPEN — HISTORICAL SEMANTICS.** Update de subtype permite diferido→inmediato aunque CashMovement lo referencie; toggle lo desactiva sin consultar uso. Eso puede volver no elegibles cobros pendientes en regularización o cambiar decisiones de consumidores históricos. No se afirma que todos los cambios con historial deban bloquearse: backend debe definir guard/snapshot y tratamiento de pendientes. Similar pendiente para tipo/cuenta/moneda/uses_subtypes de caja con sesiones/referencias; el guard actual solo cubre cambio branch y desactivación con sesión abierta.

### Cross-context

| Caso aislado | Resultado |
| --- | --- |
| company 2 → update/toggle caja/subtipo de company 1 | 404 |
| Gamarra tenant 3 → update/toggle caja/subtipo EDESCE tenant 1 | 404 |
| branch 1 → destino branch 8 misma company | Creación permitida; mantenimiento company-wide |
| branch de otra company/tenant o inexistente en create | 422 |
| IDs de objeto inexistentes update/toggle | 404 |
| IDs ausentes update/toggle | 422 |
| Payload tenant/company ajeno en create | Ignorado; servidor persiste contexto propio |
| Subtype específico de caja otra company | 422 |
| Selección operativa de caja de otro local | Excluida en queries POS/Finance |

Pruebas invocan controllers/services directamente, salvo middleware aislado y render del handler. No constituyen una suite HTTP completa de InitializeTenantContext/CSRF ni matriz real de login Gamarra en esta fase.

### Permissions

Sin cambios: auth y permission middleware. list/create/edit/disable_cashBox y list/create/edit/disable_cashBoxSubtype mapeados a index/listar, store, update, toggle. El middleware Spatie REAL se ejecutó con usuario sintético sin capacidades: **403 para los ocho permisos**, callback no alcanzado. No se supone bypass Owner. Navegador real Owner EDESCE conserva opciones Crear/Editar/Desactivar; no se alteraron roles. El stub de capacidades negativo no certifica la asignación de roles en DB ni todos los middleware HTTP.

### Tests

`tests/Feature/CashMaintainersClosureTest.php`: **11 tests, 132 assertions PASS**, PHP 7.3.33 / PHPUnit 9.6.13. Comando focalizado: `php vendor/phpunit/phpunit/phpunit tests/Feature/CashMaintainersClosureTest.php` con el binario Wamp 7.3.33. Warning preexistente de XML PHPUnit deprecado, sin cambios de configuración.

Cobertura create/update/toggle, aislamiento, local/shared, moneda bancaria, flags diferidos/comisión, duplicados, cajas no elegibles, sesión abierta/cerrada, referencias históricas sintéticas, idempotencia de update/inversión toggle, permiso negativo y errores JSON 422 via Handler Laravel. Test de gaps caracteriza XYZ, cambio moneda en uso y cambio deferred histórico. Otro reproduce **Undefined index: cash_box_id** cuando se omite ese campo nullable del subtipo: no es respuesta 422 limpia. No se marca como contrato correcto por pasar la aserción de caracterización.

### Frontend Regression

Navegador real, sesión existente Owner EDESCE / EDESCE CONSULTORA / Local Principal: ambas rutas cargan tabla, badges, acciones y modales Create/Edit. Se abrieron Nueva Caja, Editar Efectivo Local Principal, Nuevo Subtipo y Editar POS; cambios locales cancelados. Deferred muestra comisión y Edit recupera ambos flags. Sin errores JS capturados en la revisión. Inspección visual desktop 1280x720: modal legible y tabla con scroll local; sin rediseño ni CSS nuevo.

Inventario visible actualizado: company EDESCE muestra 4 cajas (local principal, BCP shared, Interbank shared, outlet) y 5 subtipos. Por tanto el inventario vacío/legacy de noviembre 5 ya no describe esa pantalla.

Feedback AJAX revisado en ambos JS: success cierra modal, muestra mensaje y recarga; error toma message/errors y muestra el primer error. JSON 422 comprobado con handler en DB aislada. **Feedback visual de create/update exitoso y error 422 end-to-end PENDING**: no se enviaron formularios a DB compartida; no se fingió un success en navegador. No se repitió matriz responsive completa por no cambiar frontend; la matriz histórica no se presenta como nueva ejecución.

### Cleanup

Registros QA creados/eliminados en DB compartida: **0 / 0**. Fixtures solo en memoria, descartados al finalizar procesos; no quedaron cajas/subtipos QA por limpiar. Sin borrados reales, sin desactivaciones reales, sin apertura/cierre/ingresos/egresos, arqueo, ventas, pagos, posting o regularización.

### Remaining Backend Sync

[OPEN] CB-01 — Política de cambios con dependencias, ahora reproducida.
File / endpoint: CashBoxController update/validateCashBox/toggle; CashBoxSubtypeController update/toggle.
Impact: cambios de moneda con sesión abierta y deferred con referencias aceptados; desactivar canal puede bloquear pendientes; check/save no serializado con apertura.
What backend needs to define or correct: reglas de inmutabilidad/snapshots, protección de pendientes y concurrencia.
Frontend can continue: Yes; no certificación integral del mantenedor como dependencia POS.

[RESOLVED PARTIAL] CB-02 — Cobertura aislada ahora disponible (11 tests); EDESCE ya tiene dataset company-scoped visible. No se certifica migración legacy global, HTTP end-to-end ni concurrencia MySQL.

[OPEN] CB-03 — Currency del efectivo sin whitelist.
File / endpoint: CashBoxController::validateCashBox.
Impact: XYZ aceptado aunque consumidores operativos requieren moneda compatible.
What backend needs to define or correct: catálogo soportado y nullable explícitos; edición con referencias.
Frontend can continue: Yes; no filtrar payload incorrecto solo en JS.

[OPEN] CB-04 — Payload incompleto subtype.
File / endpoint: CashBoxSubtypeController::validateSubtype.
Impact: cash_box_id nullable omitido causa Undefined index antes de respuesta de validación normal.
What backend needs to define or correct: normalizar ausencia a null o exigir campo explícitamente con error 422.
Frontend can continue: Yes; formulario actual envía alcance, pero el API sigue pendiente.

### POS Dependency Decision

**Cash Boxes/Subtypes dependency for POS — NOT READY (maintainers only).** CRUD básico, scope y selección local/shared comprobados; persistencia flags comprobada. No declarar Currency/Historical Semantics QA PASSED por reproducir sus gaps. Bloqueos: CB-01 histórico/concurrencia, CB-03 moneda y CB-04 payload incompleto; feedback HTTP/browser de mutaciones pendiente.

Siguiente gate: corrección backend focalizada de esos guards/validaciones y repetición de suite contra contratos corregidos; completar transporte UI en entorno de testing independiente. Después evaluar cierre de mantenedores. Cash Register Posting: **NOT TESTED / NEXT GATE**. **REGULARIZATION TRANSACTION QA — PENDING / NOT TESTED**. Esto no habilita caja, pagos ni POS.

Archivos de fase 8B: test nuevo y este documento. Backend runtime modificado: **0**. Frontend modificado: **0**. CSS nuevo: **0**. Pricing/POS/Daily Profit: sin cambios. Sin commit. Sintaxis PHP y git diff --check verificados al cierre.
