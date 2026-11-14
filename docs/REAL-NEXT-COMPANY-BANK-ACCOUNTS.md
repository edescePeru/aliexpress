# REAL NEXT — Company Bank Accounts

Fecha: 2026-11-12. Configuración de cuentas bancarias de empresa. Compras normales permanece cerrada; no se modifica. Fuente funcional: aliexpress; patrón visual Configuration CRUD, Material Configurations y Surface Spacing Standard.

## Contract Audit

Auditados CompanyBankAccountController, CompanyBankAccount, Bank, ambos FormRequest, index/create/edit/partials.form, form.js/index.js, rutas y middleware. Listado server-rendered: todas las cuentas de company activa ordenadas is_default DESC, position, id; sin búsqueda ni paginación. Crear/editar usan formulario AJAX serializado; index.js conserva confirmación y POST de estado. No se modifica JS ni backend.

## Routes

Prefijo /dashboard/configuracion. Middleware común: web, auth, check.user.enabled, password.changed, tenant.context, tenant.owner. Alias companyBankAccount.*.

| Operación | Ruta / método | Permission | Tenant | Company | Branch | Read/Write |
| --- | --- | --- | --- | --- | --- | --- |
| index | GET cuentas-bancarias | list_companyBankAccount | TenantScope | where company activa | no filtro | Read |
| create | GET cuentas-bancarias/crear | create_companyBankAccount | contexto | contexto | no filtro | Read |
| store | POST cuentas-bancarias | create_companyBankAccount | asignado por modelo | TenantContext | no campo | Write, no ejecutado |
| edit | GET cuentas-bancarias/{id}/editar | edit_companyBankAccount | TenantScope | findAccount | no filtro | Read |
| update | PUT cuentas-bancarias/{id} | edit_companyBankAccount | TenantScope | findAccount | no filtro | Write, no ejecutado |
| toggleStatus | POST cuentas-bancarias/{id}/estado | enable_companyBankAccount | TenantScope | findAccount | no filtro | Write, no ejecutado |

## Permissions

EnsureTenantOwner exige autenticación, usuario con tenant y isTenantOwner(); rechaza Platform Admin. Cada ruta agrega el permiso específico de la matriz. Botones conservan @can. No se concede acceso nuevo con la allowlist visual.

## Tenant / Company Scope

CompanyBankAccount usa BelongsToTenant. Controller aplica company_id de TenantContext en index/findAccount y mutaciones. IDs ajenos se resuelven con firstOrFail. No selectores tenant/company/branch en el formulario. Branch requerida por contexto operativo general, pero cuenta no es branch-scoped.

TenantScope sin tenant NO agrega filtro: no se certifica fail-closed del modelo aislado. La superficie depende de tenant.context + tenant.owner + companyId obligatorio. No alterar scopes ni reutilizar query sin contexto como si fuera segura.

## Bank Catalog

Bank es catálogo global, sin TenantScope. create/edit ordenan bancos por name; QA: cuatro opciones. Request exige bank_id integer exists:banks,id. Imágenes provienen de images/bank. No se crea ni cambia un banco; no se convierte el catálogo global en company-scoped.

## Fields

| Name / ID | Contrato |
| --- | --- |
| bank_id | requerido, integer, exists banks |
| title | requerido string max150 |
| account_number | requerido string max100 |
| cci | nullable string max100 |
| currency | requerido PEN/USD |
| account_holder | nullable string max200 |
| is_default | nullable boolean; checkbox value1 |

position lo asigna backend, is_active se administra por endpoint de estado; image existe en modelo pero no en este formulario. No se añaden campos. Mismo partial en create/edit, intacto: old(), @error, names/IDs, data-image, Select2 y valores. Se conserva CSRF, POST y _method PUT de edit. form.js muestra errores 422/response.message sin duplicar validación fiscal/bancaria. No se ejecutaron envíos inválidos para probar validación remota.

## Currency

PEN y USD exclusivamente. Create inicia vacío. Edición preserva valor existente. No conversión ni PEN por defecto. Históricos con currency null quedan sin selección, igual que el contrato previo.

## Default/Status Contract

- Crear: si no existen cuentas activas, nueva cuenta se vuelve default; también si checkbox marcado. Limpia otros defaults de company. Nueva cuenta activa, position=max+1.
- Editar: marcar default limpia otros defaults de company. Desmarcar una que ya era default no la quita automáticamente.
- Desactivar: is_active=false/is_default=false. Si era default, promueve primera activa por position/id. Si no queda ninguna activa, no queda default.
- Activar: default solo si no había otra activa.
- No regla de default por moneda. No borrar cuentas.
- Update no impide marcar como default una inactiva. Unicidad se intenta mantener transaccionalmente en controller; migración auditada tiene índices de scope/posición, sin constraint único default. No certificar concurrencia.

Badges Activa/Inactiva y Predeterminada conservan texto y semántica; no dependen solo del color. No se agrega validación JS.

## Dependencies

PuntoVentaController y QuoteSaleController leen cuentas activas de company para documentos, ordenadas default/position/id. CashBoxController consume cuenta de company activa. No se identifica en búsquedas de CompanyBankAccount una escritura de Nubefact desde este CRUD. No se certifica aquí toda la cadena de pagos/documentos; no se modifican consumidores.

## CashBox Relationship

CompanyBankAccount::cashBoxes hasMany CashBox(company_bank_account_id); CashBox tiene belongsTo. Al configurar una caja bancaria, controller exige cuenta de company y activa y usa su moneda. toggleStatus de cuenta no consulta cashBoxes ni movimientos: no hay bloqueo por uso en ese método. No se altera CashBox, CashRegister, pagos, movimientos, conciliación o transferencias.

## Visual Mapping

REUSE DIRECTLY / WITH CONTRACT ADAPTATION: next-page-heading, next-page-toolbar, next-toolbar-actions, next-operational-list, next-list-summary, next-data-table, next-row-actions, next-form-section, next-list-toolbar. Listado: banco/título/titular izquierda; cuenta/CCI/moneda/estado/acciones centro. Default continúa al lado del título. Dropdown conserva hooks data-toggle-bank-account/data-id/data-active y permisos; no números bancarios en data-* nuevos. Empty state existente conservado; no búsqueda inventada.

Create/edit utilizan mismo wrapper y partial, acciones con gap compartido. 0 CSS nuevo; JS intacto. Allowlist agrega únicamente companyBankAccount.index/create/edit. ALLOWLIST MATCH: YES; ambos CSS venti.min.css y venti-next.css comprobados en listado/edición; creación usa la misma allowlist y fue inspeccionada visualmente.

Números y CCI ya se mostraban completos; se preserva comportamiento. No se registran aquí ni en logs, fixtures, data-* adicionales o screenshots de QA. Única captura guardada: formulario create vacío.

## Responsive QA

| Ancho | Index | Create | Edit |
| --- | --- | --- | --- |
| 320 | sin overflow global | sin overflow global | sin overflow global |
| 375 | sin overflow global | sin overflow global | sin overflow global |
| 390 | sin overflow global | sin overflow global | sin overflow global |
| 576 | sin overflow global | sin overflow global | sin overflow global |
| 768 | sin overflow global | sin overflow global | sin overflow global |
| 992 | sin overflow global | sin overflow global | sin overflow global |
| 1200 | sin overflow global | sin overflow global | sin overflow global |
| 1440 | sin overflow global | sin overflow global | sin overflow global |

Tabla con scroll local permitido. Banco Select2 abre cuatro opciones. Create vacío capturado e inspeccionado; list/edit verificados mediante DOM/geometría sin capturar datos bancarios. No errores JS observados.

## Cross-context QA

Navegador sesión Owner EDESCE: tenant1/company1/branch1. Listado cuatro cuentas; create cuatro bancos/PEN/USD; edit propio accesible. Cuenta histórica 1: banco no resuelto y moneda null; cuenta 3: banco válido y PEN seleccionados correctamente. No guardar ni toggle.

Matriz adicional: proceso Laravel PHP7.3, Auth::setUser con cuentas reales, sesiones efímeras array, InitializeTenantContext + EnsureTenantOwner, permisos comprobados, controllers reales dentro de transacción SQL READ ONLY con rollback. No números de cuenta impresos. Esto prueba read path contextual, NO se presenta como login HTTP/QA visual de Gamarra.

| Cuenta/rol | Tenant | Company | Branch | Lista | Edit IDs1–4 | Bancos create |
| --- | --- | --- | --- | --- | --- | --- |
| Owner EDESCE/owner | 1 | 1 | 1 | cuatro cuentas | accesibles | cuatro |
| Owner EDESCE/owner | 1 | 2 | 2 | vacía | rechazo ModelNotFound/404 | cuatro |
| Owner EDESCE/owner | 1 | 5 | 6 | vacía | rechazo ModelNotFound/404 | cuatro |
| Owner Gamarra/owner | 3 | 3 | 4 | vacía | rechazo ModelNotFound/404 | cuatro |

ID inexistente99999999 rechazado en todos. Vacío Gamarra válido. No atribuir a branch el aislamiento. QA visual adicional de Gamarra y mutaciones permanece pendiente; integración contextual pasó.

## Mutation Exclusions

MUTATION QA PENDING. No crear, guardar edición, activar/desactivar, cambiar default/banco, pagos ni movimientos. Los botones legítimos siguen conectados a contratos existentes; no se hacen pruebas de persistencia. No POST bancarios ni escrituras comerciales.

## Backend Sync Notes

[OPEN]
Point: cuentas históricas incompletas.
File / endpoint: CompanyBankAccount / edit.
Impact: IDs1/2 del contexto EDESCE tienen banco no resuelto y currency null; uno es default. Selecciones vacías son evidencia del dato, no fallo visual.
What backend needs to define or correct: regularización autorizada de históricos; no rellenar automáticamente desde frontend.
Frontend can continue: Yes.

[OPEN]
Point: default inactivo/unicidad concurrente.
File / endpoint: CompanyBankAccountController::update/store/toggleStatus.
Impact: update permite elegir default inactivo; unicidad lógica transaccional sin constraint único identificado ni QA de concurrencia.
What backend needs to define or correct: política de default activo y garantía concurrente si requerida.
Frontend can continue: Yes, sin reglas JS duplicadas.

[OPEN]
Point: desactivar cuenta vinculada a CashBox.
File / endpoint: toggleStatus; CashBoxController.
Impact: no se comprueban dependencias al desactivar, aunque caja requiere cuenta activa al configurarse.
What backend needs to define or correct: confirmar política sobre cajas existentes/operaciones dependientes antes de certificar mutaciones.
Frontend can continue: Yes.

## Revisit Triggers

Cambios de moneda, default/estado, restricciones de uso, scopes/owner/permisos, validaciones, enmascaramiento, bancos globales, relaciones con cajas o consumidores documentales. No ampliar a finanzas sin fase explícita.

## Files / Checks / Final Status

Archivos modificados: config/venti-next.php; resources/views/companyBankAccount/index.blade.php, create.blade.php, edit.blade.php; este documento. Partial/form.js/index.js intactos. Backend, relaciones, permisos y CSS sin cambios. Sin commit.

PHP7.3 artisan view:cache y git diff --check PASS. JS no modificado.

- Company Bank Accounts List — FRONTEND INTEGRATED
- Create / Edit — FRONTEND INTEGRATED
- Tenant / Company Isolation — QA PASSED (integración contextual, alcance descrito)
- Banking Mutations — QA PENDING
- Financial Operations — OUT OF SCOPE
- Backend — UNCHANGED
