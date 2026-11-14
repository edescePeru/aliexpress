# REAL NEXT — Admin Cash Movements — Read-only Authorization Audit

Fecha: 2026-11-13. HEAD auditado: `9262d65`.

**Decisión: BACKEND SYNC REQUIRED. No integrar frontend en esta fase.**

El finding anterior «falta gate administrativo» queda RESOLVED: ambas acciones tienen `permission:listCashMovementAdmin_cashBox`. Sin embargo, Platform Admin posee ese permiso y alcanza acciones que requieren un contexto que su middleware acaba de eliminar: ambas lanzan `RuntimeException: No existe una empresa activa.` No hay contrato de rechazo controlado para este caso. No se observó exposición de movimientos en esa ejecución.

## Authorization Audit

- `CashBoxController::__construct()` aplica `auth` y el permiso a `adminIndex/adminList` (líneas 65–68).
- Spatie `PermissionMiddleware` consulta `Auth::user()->can($permission)`; no deduce autorización del menú, nombre de rol ni flag Owner.
- `AuthServiceProvider` no define un `Gate::before` de bypass.
- `InitializeTenantContext` valida tenant del usuario, membresía activa de company y branch, company activa, branch activa y pertenencia de branch a company.
- Platform Admin es `User::isPlatformAdmin()` / `is_platform_admin`. El middleware borra tenant/company/branch y continúa. La lectura posterior de `TenantContext::companyId()` lanza la excepción indicada.
- ConfigUserWeb asigna roles mediante `syncRoles`; para usuarios no Owner busca rol por tenant, activo y asignable. No confiere implícitamente este permiso a todos los usuarios. TenantRole administra roles por tenant y sincroniza permisos; no se modificó esa configuración.

### Método y alcance de la evidencia

PHP 7.3.33, bootstrap real Laravel, base local actual. Harness temporal fuera del repositorio, sesión y cache `array`, autenticación en memoria mediante usuarios existentes, `SET TRANSACTION READ ONLY`, transacción y rollback en `finally`. Sin contraseñas, cambios de usuarios, ni llamadas a servicios financieros.

Se ejecutaron los middleware reales `InitializeTenantContext` y `PermissionMiddleware`, seguidos de las acciones reales. Los 200 de listas son `JsonResponse`; los 200 de index indican retorno correcto del objeto View, **no renderizado completo de navegador**. Los 403 son excepciones HTTP del middleware. El 500 es la clasificación de una RuntimeException no HTTP; no se presenta como captura de red. La cadena completa de rutas fue inspeccionada con `gatherMiddleware()`. No se ejecutó QA visual al fallar el criterio previo.

## Role Matrix

Permiso: `listCashMovementAdmin_cashBox`.

| Usuario / rol | Permiso efectivo | Tenant | Company / branch probado | Resultado |
|---|---|---|---|---|
| 4 Owner EDESCE / owner | Sí, vía rol 2 | 1 | 1/1; 1/8 | 200 lectura contextual |
| 7 Owner Gamarra / owner | Sí, vía rol 15 | 3 | 3/4 | 200 vacío |
| 2 Cajero Master / cajero | No | 1 | 1/1 | 403 permiso |
| 6 Usuario Prueba / cashier | No | 1 | 1/1 | 403 permiso |
| 3 Pepe Castillo / almacen | No | 1 | 1/1 | 403 en gate aislado; password change requerido antes en ruta completa |
| 5 Usuario Prueba Tenant 2 / cashier | No | 2 | No ejecutado | No autorizado por permiso; contexto no probado |
| 8 Owner Gloria / owner | Sí, vía rol 20 | 4 | No ejecutado | Permiso comprobado; contexto no probado |
| 1 Administrador / admin | Sí, vía rol 1 | null | Contexto eliminado | Excepción 500 en ambas acciones |

Roles que actualmente tienen el permiso: admin id1 tenant1; owner id2 tenant1; owner id12 tenant2; owner id15 tenant3; owner id20 tenant4. Tener el permiso no sustituye membresías ni contexto. Los ocho usuarios actuales no tienen permisos directos; sí se consultó esa relación. Los usuarios 1,2,4,6,7 están habilitados y no requieren cambio de contraseña.

## Route Matrix

| Método / ruta | Nombre | Acción |
|---|---|---|
| GET `/dashboard/cash-movements/admin` | `cashMovement.admin.index` | CashBoxController::adminIndex |
| GET `/dashboard/cash-movements/admin/list` | `cashMovement.admin.list` | CashBoxController::adminList |

Ambas: `web`, `auth`, `check.user.enabled`, `password.changed`, `tenant.context`, `permission:listCashMovementAdmin_cashBox`.

Blade actual: `resources/views/cashBox/admin_index.blade.php`. JS: `public/js/cashBox/admin_index.js`.

## Tenant/Company/Branch Scope

`adminList` llama `buildListResponse($request, null, true)`. CashMovement tiene TenantScope; la consulta añade `cash_movements.company_id` y `cash_movements.branch_id` del contexto validado. No toma el contexto desde query/body.

`myList` limita además por usuario propio. Admin no añade ese predicado salvo filtro `user_id`: es una superficie administrativa distinta. Que los datos actuales pertenezcan a un único usuario no convierte ambos contratos en equivalentes.

| Criterio | Resultado y límite |
|---|---|
| Authorization | **FAIL para certificación integral**: gate tenant correcto, Platform Admin sin rechazo controlado |
| Tenant isolation | PASS en contextos y lecturas probados |
| Company isolation | PASS en contexto ajeno rechazado y parámetros ignorados; no se probó segunda company accesible del mismo tenant |
| Branch isolation | PASS: EDESCE 1/8 separadas; branch ajena rechazada |
| User lookup | SAFE para dataset actual; dependiente de integridad de referencias, ver abajo |

Los 243 movimientos con al menos un tenant/company/branch nulo no aparecen en los conjuntos contextualizados. TenantScope no filtra si no hay tenant, pero aquí Platform Admin falla al solicitar company antes de ejecutar el listado; no se certifica TenantScope como protección independiente.

## Dataset

Conjuntos completos obtenidos con `per_page=100`, contrastados con IDs/user_id de CashMovement en el mismo contexto:

| Tenant/company/branch | IDs completos en orden del endpoint | Actor |
|---|---|---|
| EDESCE 1/1/1 | 265,264,263,262,261,260,259,258,257,256,255,253,252,251,250 | Todos user_id 4, Owner EDESCE |
| EDESCE 1/1/8 | 254 | user_id 4, Owner EDESCE |
| Gamarra 3/3/4 | vacío | ninguno |

Branch1: IDs250,253,255,256,257,258,259,260,262,263 usan caja1; 251,252,261,264,265 caja2. Branch8: ID254 caja6.

Paginación real con tamaño10: página1 `[265,264,263,262,261,260,259,258,257,256]`; página2 `[255,253,252,251,250]`; total15, last_page2. Sin duplicados ni pérdida entre páginas.

## User Lookup

`adminIndex` obtiene IDs distintos de actores en CashMovement del tenant/company/branch actual y aplica `User::whereIn('id', $movementUserIds)`. No invoca UserController legacy ni lista todos los usuarios.

- EDESCE branch1 y8: únicamente `{id:4, name:Owner EDESCE, tenant:1}`.
- Gamarra: vacío.
- Filtro `user_id=7` desde EDESCE: vacío.
- Consulta de integridad sobre movimientos con contexto completo: cero actores inexistentes, sin tenant o con tenant distinto del movimiento.

Límite: el User externo y los joins de users/cash_boxes/subtypes/branches usan ID sin predicados adicionales de tenant/company. Un vínculo inconsistente podría introducir etiquetas ajenas. No se insertaron referencias corruptas en la base compartida y no se certificó ese escenario adversarial. Esto no es evidencia de un lookup global actual; queda como hardening a revisar por backend.

## Movement Semantics

Columnas existentes: N°, Fecha, Usuario, Caja, Tipo, Subtipo, Estado, Monto, Abonado, Comisión, Descripción, Acciones. No se añadió ninguna.

DTO: id, type_raw/type, regularize, balance_effect, regularization_source_id, amount, amount_regularize, commission, description, observation, sale_id, entry_id, cash_register_id, cash_settlement_id, origin_id, regularize_id, created_at, cash_box_id/name/type/label, branch_id/name, subtype_name/code/is_deferred, user_name.

- Mantener importes del backend: no recalcular, netear, invertir signos ni deducir moneda.
- El DTO actual **no expone currency**. No añadir columna o filtro de moneda basado en supuestos.
- Ejemplo real: 264 balance_effect=false, regularize_id265; 265 balance_effect=true, regularization_source_id264 y origin_id264. Ambos amount70.00; no deben sumarse ingenuamente como dos abonos.
- El JS actual distingue pendiente, confirmado y regularizado en otra sesión; muestra importes entregados, sin reconstruir balance.

## Filters

Payload actual: `page`, `q`, `user_id`, `cash_box_id`, `type`, `subtype_id`, `date_from`, `date_to`. Backend acepta `per_page` (1–100; inválido vuelve a10). Respuesta `data` + `meta.current_page/last_page/total/from/to/per_page`.

Tipo: sale/income/expense. Subtipo `none` aplica whereNull. Fechas inclusivas con whereDate. Búsqueda incluye descripción, observación, caja, usuario, subtipo y branch. No existe filtro de moneda.

Pruebas branch1:

| Filtro | IDs |
|---|---|
| user_id7 | vacío |
| cash_box_id6 (branch8) | vacío |
| tenant_id3/company_id3/branch_id4 inyectados | dataset íntegro de branch1, contexto no alterado |
| type expense | 260,258,256,253 |
| date_from/date_to 2026-11-05 | 265 |
| subtype_id none | 263,262,260,259,258,257,256,255,253,250 |
| q Regularización | 265 |

## Regularization Boundary

**REGULARIZATION — OUT OF SCOPE**. Se conserva sin ejecutar ni modificar `data-regularizar`, parcial `cashRegister._regularization`, JS regularization y evento `cash:regularized`.

Los botones superiores de cajas corresponden a sesiones **del usuario actual**, no sesiones globales administrativas. Su JS puede abrir caja: no se pulsaron. El hidden `permissions` usa permisos por roles; no refleja posibles permisos directos para acciones auxiliares. No se alteró ese contrato ni se certificaron mutaciones.

## Documents

**DOCUMENT SECURITY — BACKEND/INFRA OPEN**. JS enlaza `/dashboard/imprimir/documento/venta/{sale_id}` o `/dashboard/factura/compra/ver/{entry_id}`. No se abrieron documentos ni se certificó su autorización.

## Responsive QA

320/375/390/576/768/992/1200/1440: **NOT RUN — integración detenida en autorización**. No declarar ausencia de overflow o integración visual sin esa prueba.

Allowlist: `cashMovement.admin.index` no está en `config/venti-next.php`; no se añadió. **ALLOWLIST MATCH: NO (intencional)**. CSS nuevo:0. Blade/JS/backend sin cambios. No se compiló Blade al no modificar vistas.

## Negative Authorization QA

Ambas acciones probadas por el harness de middleware/controller:

| Actor / contexto | Resultado |
|---|---|
| Owner EDESCE 1/1/1 | Index retorna View; list200 |
| Cajero Master 1/1/1 sin permiso | 403 ambas |
| Usuario Prueba id6 1/1/1 sin permiso | 403 ambas |
| Owner Gamarra intentando EDESCE 1/1/1 | 403 ambas por contexto |
| Owner EDESCE intentando Gamarra 3/3/4 | 403 ambas por contexto |
| Owner EDESCE tenant1/company3/branch4 | 403 ambas por company |
| Owner EDESCE tenant1/company1/branch4 | 403 ambas por branch |
| Platform Admin con sesión inicial 1/1/1 | Sesión contextual eliminada; RuntimeException en ambas, equivalente500 |

No se modificaron roles ni usuarios para producir negativos. El caso Pepe Castillo solo demuestra el gate: su ruta completa requiere primero cambio de contraseña, por lo que no se atribuye un HTTP403 extremo a extremo a esa cuenta.

## Backend Sync Notes

### [RESOLVED] Gate administrativo presuntamente ausente

Contrato final: middleware `permission:listCashMovementAdmin_cashBox` en ambas acciones; roles y negativos comprobados. HEAD9262d65, 2026-11-13. No se afirma que la pantalla carezca de permiso.

### [OPEN] ADMIN-CASH-01 — Platform Admin alcanza acción operativa sin contexto

- Archivos: InitializeTenantContext, TenantContext, CashBoxController::adminIndex/adminList.
- Reproducción: usuario1 tiene permiso; middleware borra contexto; companyId lanza RuntimeException antes de leer movimientos.
- Impacto: error500 en vez de contrato explícito de acceso/rechazo. No prueba fuga de datos.
- Backend debe definir y aplicar rechazo controlado (o un mecanismo operativo explícito autorizado, si realmente corresponde). Frontend no debe inferir tenant ni reparar la sesión.
- **Frontend can continue: No**, siguiendo el requisito de certificar autorización antes de integrar.

### [OPEN] Integridad de relaciones de lectura

Base contextual correcta y dataset actual consistente. Revisar defensa ante user/caja/subtipo/branch referenciados fuera del contexto; joins no añaden esa defensa. No afirmar explotación ni fuga actual; no ejecutar escrituras adversariales sobre datos compartidos.

### [OPEN] Documentos

Seguridad documental continúa BACKEND/INFRA OPEN, independiente de autorización del listado.

No se implementaron decisiones CASH-02/03/04, ni se tocaron Cash Maintainers o Pricing. Mutaciones financieras OUT OF SCOPE. Backend UNCHANGED. Sin commit.

### Reanudación

Tras definir/corregir el contrato Platform Admin: repetir la matriz negativa con middleware completo, mantener separación My/Admin, comprobar hardening según contrato backend y realizar integración visual/QA responsive. No dar por aprobado el read path de toda la superficie solo por existencia del permiso.
