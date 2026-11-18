# REAL NEXT — Cuentas de proveedores

2026-11-18. **FRONTEND INTEGRATED WITH TECH DEBT**.

Tenant / Supplier parent: **QA PASSED** para sesiones tenant EDESCE y Gamarra. Company/Branch: **NOT APPLICABLE**. Mutaciones: **QA PENDING**. Supplier Credits: **OUT OF SCOPE**. SUPPLIER-01: **OPEN**. Backend: **UNCHANGED**.

## Parent Supplier Contract

`SupplierAccountController::index($worker_id)` recibe el supplier_id de la ruta. Resuelve `Supplier::findOrFail`, luego `SupplierAccount::where('supplier_id', $supplier->id)->with('bank')->get()`. Ambos modelos usan BelongsToTenant/TenantScope. Supplier además usa SoftDeletes. El nombre worker_id del argumento/hidden es legado; se conserva y no representa Worker.

El mantenedor sigue siendo anidado: **Proveedor → Cuentas**, no un catálogo global de cuentas. El encabezado identifica al proveedor y permite regresar al listado ya integrado.

## Routes / Permissions

Prefijo `/dashboard`; middleware común web/auth/check.user.enabled/password.changed/tenant.context. No se modificaron rutas, métodos ni permisos.

| Action | Route | Name | Permission | Read/Mutation |
|---|---|---|---|---|
| Listado + edición inline | GET `registrar/cuentas/proveedores/{supplier_id}` | supplier.accounts.index | list_supplierAccount | Read |
| Agregar cuenta | POST `supplier/account/store/{worker_id}` | supplier.account.store | create_supplierAccount | Mutation |
| Guardar cambios | POST `supplier/account/update/{account_id}` | supplier.account.update | edit_supplierAccount | Mutation |
| Eliminar cuenta | POST `supplier/account/destroy/{account_id}` | supplier.account.destroy | destroy_supplierAccount | Mutation |
| Auxiliar legacy, NO utilizado | GET `get/supplier/accounts/{worker_id}` | Sin nombre | list_supplierAccount | Read declarado; action inexistente |

`list_supplier` no sustituye list_supplierAccount. Ambos Owners utilizados tienen los cuatro permisos de cuentas. Create conserva @can; se añadieron los @can correspondientes a las acciones de edición/eliminación existentes y al template dinámico. El backend sigue siendo el guard real. No se otorgaron ni cambiaron permisos; QA de rol limitado HTTP no realizado en esta fase.

## Tenant Scope

Scope contractual **tenant**, compartido entre companies/branches de ese tenant. No hay company_id/branch_id en el contrato de SupplierAccount; no se agregaron filtros frontend.

Update/destroy resuelven `SupplierAccount::findOrFail($account_id)` con TenantScope, no un ID global. Update valida campos antes del binding: una petición inválida puede devolver 422 antes de llegar al 404 de cuenta ajena. No se afirma que todo payload ajeno devuelva siempre 404.

Límite existente: TenantScope no aplica filtro si no existe tenant; InitializeTenantContext permite el caso Platform Admin sin tenant. Esta fase valida usuarios tenant con contexto, **no certifica acceso Platform Admin/sin contexto** ni modifica esa política compartida.

## Dataset

Revalidación SQL en transacción MySQL READ ONLY + ROLLBACK; llamadas GET reales posteriores con las cuentas de QA autorizadas. No números bancarios ni contraseñas en esta evidencia.

| Tenant | Supplier ID | Account IDs | Bank | Currency |
|---|---:|---|---|---|
| EDESCE T1 | 1 | Ninguna | N/A | N/A |
| Gamarra T3 | 2 | 5 | 1 (BCP) | PEN |

Totales: 2 proveedores activos, 1 cuenta. Owner EDESCE usuario 4 en T1/C1/B1; Owner Gamarra usuario 7 en T3/C3/B4. Company/branch son contexto de sesión, no filtros de este mantenedor.

| Sesión | Supplier propio | Supplier extranjero | Supplier inexistente 999999 |
|---|---|---|---|
| Owner EDESCE | ID1: HTTP 200, vacío | ID2: HTTP 404 | HTTP 404 |
| Owner Gamarra | ID2: HTTP 200, cuenta5 | ID1: HTTP 404 | HTTP 404 |

Binding de cuentas en lectura, usando **la misma operación `SupplierAccount::findOrFail` que emplean update/destroy**, sin invocar esas acciones: ID5 en T1 → 404; ID5 en T3 → encontrado; ID999999 → 404 en ambos. Esto más la inspección de código confirma el binding tenant; **no constituye QA HTTP de POST edit/delete**. No se intentaron peticiones mutadoras con IDs manipulados, ni siquiera para provocar errores.

## Fields

Sólo número de cuenta, banco y moneda. No existen tipo, CCI ni titular en este contrato; no se añadieron.

| Campo enviado | Contrato backend | Hooks conservados |
|---|---|---|
| number_account | nullable string, max191 en store/update | data-number_account; template name numberAccounts[] |
| bank_id | nullable, exists:banks,id | data-bank; banks[]; .bank |
| currency | store nullable PEN/USD; update required PEN/USD | data-currency; currency[]; .currency |
| account_id | URL de update/destroy; JS de update también conserva account_id en payload | data-updateAccount / data-deleteAccount |
| supplier_id | URL de store y parent GET | hidden worker_id; #newAccount data-url |

Los names existentes, IDs, data hooks y header X-CSRF-TOKEN del meta permanecen. No existe formulario con submit/action propio; son botones AJAX. Los Request se validan inline en controller, no mediante FormRequest separado.

Adaptación del número: input ahora `type=text`, maxlength191, coherente con string backend y conservando ceros iniciales; no conversión numérica ni cambios de valor. No había maxlength en el markup anterior. Se añadieron nombres accesibles a los controles.

## Bank Catalog

**GLOBAL REFERENCE CATALOG**. `Bank::all()`; modelo Bank sin TenantScope. IDs actuales 1 BCP, 2 BBVA CONTINENTAL, 3 SCOTIABANK, 4 INTERBANK. La UI conserva short_name e imagen de banco en Select2; no modifica el catálogo ni lo transforma en tenant-scoped.

## Currency

Valores exactos PEN/USD, labels Soles/Dólares. Store sin currency usa default backend PEN; explícitamente null se conserva según el request. No hay conversión de moneda. Update exige uno de los dos valores.

Se corrigió exclusivamente la representación del callback de alta: los select/input usan `.value`, no atributo value/innerHTML. Bank null ahora aparece vacío, en vez de seleccionar ficticiamente banco1; currency refleja la respuesta real. No se ejecutó store para probarlo: renderTemplateAccount se verificó con objeto temporal sólo en DOM, seguido de recarga, sin registros DB.

## Legacy Unused Route

**LEGACY UNUSED ROUTE — TECH DEBT**: `get/supplier/accounts/{worker_id}` apunta a getWorkerAccounts inexistente. El listado carga `$accounts` server-side. No se llama, corrige ni incorpora esa ruta en la integración.

## SUPPLIER-01 Boundary

**SUPPLIER-01 — OPEN**. La serialización de cuentas en otro endpoint de Supplier pese al permiso separado de UI sigue fuera de esta fase. No se tocó SupplierController, su listado ni Supplier Credits. Proteger este mantenedor no certifica todos los consumidores de cuentas.

## Visual Integration

**REUSE WITH CONTRACT ADAPTATION**: Configuration CRUD / Nested Maintainer, toolbar y empty state de Company Bank Accounts, Surface Spacing Standard. `.next-page-heading`, `.next-page-toolbar`, `.next-toolbar-actions`, `.next-operational-list`, `.next-list-summary`, `.next-form-section`.

Se mantiene listado de filas editables inline, sin convertirlo en tabla de sólo texto ni introducir modales de edición nuevos. Filas compactas en desktop, dos columnas intermedias y una en móvil. Se eliminó la card anidada/colapsable y el CSS local heredado; no se añaden reglas CSS. Summary/empty se actualizan en los callbacks existentes de alta/delete.

Guardar cambios expresa la acción real del botón antes llamado Editar cuenta. Confirmación jQuery Confirm existente se conserva; cerrar con X reabilita el botón cuando no se confirmó, sin permitir reenvío mientras hay una petición confirmada pendiente. Selectores JS por `[data-account-row]` reemplazan recorridos parent/prev frágiles, conservando URLs, payloads, CSRF y validación/toasts. El template respeta permisos y tolera acciones ausentes.

Allowlist: **sólo `supplier.accounts.index`**, correspondiente a supplierAccount.index. **ALLOWLIST MATCH: YES** comprobado en HTML real; carga venti.min.css y venti-next.css. No hay vistas nuevas ni endpoints JSON añadidos.

## Responsive

QA con Edge headless, Laravel real en 127.0.0.1:8080 y sesiones autorizadas; sin excepciones JS observadas. Se bloquearon preventivamente requests a rutas mutadoras del mantenedor en el navegador de QA y se verificó **0 intentos**.

| Ancho | scrollWidth documento | Cuenta Gamarra | Estado |
|---:|---:|---:|---|
| 320 | 320 | 1 | PASS |
| 375 | 375 | 1 | PASS |
| 390 | 390 | 1 | PASS |
| 576 | 576 | 1 | PASS |
| 768 | 768 | 1 | PASS |
| 992 | 992 | 1 | PASS |
| 1200 | 1200 | 1 | PASS |
| 1440 | 1440 | 1 | PASS |

EDESCE vacío: 320/1440 PASS, mensaje visible y Agregar cuenta según permiso; no se pulsó. Confirmación de guardar abierta y cerrada sin confirmar, también en móvil. Capturas desktop/móvil revisadas. **GLOBAL OVERFLOW: NONE; CSS nuevo: 0**. Alta real y respuesta persistida no validadas.

## Mutation Boundary

**MUTATION QA PENDING**. Agregar cuenta crea inmediatamente mediante POST sin payload de campos; no abre modal previo. Posteriormente la fila permite completar/guardar datos. Se preserva ese contrato; no se pulsó Agregar cuenta en QA real.

Update/delete mantienen confirmación. Delete es **hard delete**: SupplierAccount no usa SoftDeletes y destroy llama delete(). No se borró ni desactivó nada. No hay restore. No se ejecutaron store/update/destroy, ni pruebas transaccionales de escritura en la DB compartida.

## Backend Sync Notes

[OPEN] SUPPLIER-01: frontera de permisos de la serialización externa. Frontend puede continuar: Sí, mantenedor explícito ya integrado; no cerrar la decisión de negocio.

[OPEN] Ruta auxiliar sin action: mantener como deuda legacy no consumida; DEV debe implementarla con contrato apropiado o retirarla. Frontend puede continuar: Sí.

[OPEN] Mutaciones y acceso sin contexto: no certificados; no inferir seguridad de Platform Admin a partir de TenantScope. No se cambió backend para esta fase.

Archivos de esta fase: `config/venti-next.php`, `resources/views/supplierAccount/index.blade.php`, `public/js/supplierAccount/create.js` y este documento. Cambios previos de Kardex/readiness se conservaron. Backend/routes/models/requests/CSS: sin cambios. Sintaxis JS, compilación Blade PHP7.3.33 y git diff --check: PASS. Sin commit.
