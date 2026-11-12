# REAL NEXT — ConfigUserWeb

Fecha: 2026-11-12. Roadmap C, fase 6. Backend sin cambios. Sin commit.

## Contract Audit

Fuente funcional: `ConfigUserWebController`, `TenantPlanService`, `TemporaryPasswordService`, `User`, `Role`, relaciones `company_user` / `branch_user`, rutas y los tres JS originales. Integración limitada a index/create/edit y resumen de plan. No se sustituye por UserController.

`getUsers` devuelve paginador Laravel: id, name, email, image, enable, is_tenant_owner, updated_at, role, role_id, is_current_user. Orden Owner primero y nombre. Search por nombre/email; status active/inactive/all; per_page 10/25/50. No se inventa columna Worker. Render de estado desde enable. Mutaciones existentes conservadas, no ejecutadas.

## Route Matrix

Prefijo: `/dashboard/configuracion/usuarios/web`. Nombres `configUserWeb.*`.

| Operation | Route | Permission | Tenant | Company Assignment | Branch Assignment | Read/Write |
|---|---|---|---|---|---|---|
| index | GET base | listUser_configUserWeb | Owner actual | No filtra usuario maestro | No filtra usuario maestro | Read |
| getUsers | GET /listado | listUser_configUserWeb | tenantUsersQuery | No filtro company | No filtro branch | Read |
| planSummary | GET /resumen-plan | listUser_configUserWeb | Tenant del Owner | N/A | N/A | Read |
| create | GET /nuevo | createUser_configUserWeb | Owner actual | Activas del tenant | Activas de esas empresas | Read |
| store | POST /guardar | createUser_configUserWeb | Owner actual | Valida selección | Valida selección y defaults | Write |
| edit | GET /{id}/editar | editUser_configUserWeb | findTenantUserOrFail | Opciones tenant y pivots activos | Opciones y pivots activos | Read |
| update | POST /{id}/actualizar | editUser_configUserWeb | findTenantUserOrFail | sync no Owner | sync no Owner | Write |
| resetPassword | POST /{id}/resetear-password | resetPasswordUser_configUserWeb | findTenantUserOrFail | N/A | N/A | Sensitive write |
| changeStatus | POST /{id}/cambiar-estado | changeStatusUser_configUserWeb | findTenantUserOrFail | Conserva | Conserva | Write |

## Permissions

Middleware efectivo verificado por gatherMiddleware: web, auth, check.user.enabled, password.changed, tenant.context y permission específico de cada ruta.

**Corrección respecto de la hipótesis inicial:** estas rutas NO llevan `tenant.owner`. Cada acción llama al guard privado `ensureTenantOwner()` del controlador. El middleware `EnsureTenantOwner` existe para otras rutas; no se añade ni se cambia backend en esta fase. La protección observada es Owner obligatorio y exclusión de Platform Admin en el controlador.

Nuevo usuario usa @can. Las acciones AJAX reciben flags de permisos Blade y preservan las restricciones Owner/self del render original. La autorización real sigue en backend.

## Tenant Scope

`tenantUsersQuery`: tenant_id del usuario autenticado y is_platform_admin=false. El usuario maestro NO se limita a company/branch activos. `findTenantUserOrFail` usa esa misma consulta. Las empresas disponibles son todas las activas del tenant, no solo un subconjunto deducido en JS.

QA navegador: Owner EDESCE (user 4, tenant 1, company 1, branch 8 OUTLET DE EDESCE); Owner Gamarra (user 7, tenant 3, company 3, branch 4 Local Principal). Cambio de cuenta/contexto correcto. Consultas suplementarias en transacción MySQL READ ONLY, finalizada con rollback; solo se exportaron IDs, flags, conteos y opciones, sin emails ni secretos.

## Dataset

| Tenant | Usuarios visibles | Owner | Normales | Activos / inactivos | Worker linked / unlinked |
|---|---|---|---|---|---|
| EDESCE 1 | 4 (IDs 2,3,4,6) | 4 | 2,3,6 | 4 / 0 | 2,3 vinculados; 4,6 sin vínculo |
| Gamarra 3 | 1 (ID 7) | 7 | Ninguno | 1 / 0 | 7 sin vínculo |

Workers de users 2/3: tenant 1, company 1. Clasificación solo auditada, no incorporada como contrato de listado. Platform Admin no forma parte de esos conteos.

## Owner / Platform Admin

Owner puede editar nombre/email/imagen; perfil readonly y sin controles de asignación. Update no sincroniza roles/memberships de Owner. Reset y cambio de estado rechazan self y cualquier Owner (422 por contrato estático). No ejecutados.

Platform Admin excluido por consulta y guard; su ID 1 devuelve 404 al Owner. Guard de listado con actor Platform Admin probado directamente en lectura: 403. No se administró la cuenta Platform Admin ni se hicieron mutaciones.

## Tenant Roles

Modelo real `App\Role` (Spatie), no un modelo separado TenantRole. Opciones where tenant_id, is_active, is_owner_assignable. Selección **única** `role_id`, no multiselect. `syncRoles([$role])` reemplaza el perfil del usuario normal; no modifica plantillas globales. EDESCE: 9 opciones (1,6,13,8,7,3,5,17,4). Gamarra: 14 y 16. No Role::all() en esta integración.

## Company Assignments

`companies[]` requerido min1 para normal; IDs únicos y conteo validado contra empresas activas del tenant. UI conserva `.company-checkbox`, IDs y labels. EDESCE opciones 1,2,5; Gamarra 3. Update sync elimina pivots omitidos y guarda is_active/is_default. No se persistió ninguna asignación.

## Branch Assignments

`branches[]` requerido min1 para normal; branches activas de empresas seleccionadas del tenant. EDESCE: company1→1,8; company2→2,3; company5→6,7. Gamarra: company3→4. Desmarcar company desmarca/deshabilita sus branches mediante JS original. No empresa sin branches en dataset: empty branch path revisado estáticamente, QA real pendiente.

## Defaults

`default_company_id` pertenece a companies[]; `default_branch_id` pertenece a branches[] y a la default company. Backend valida ambas relaciones; JS existente restringe opciones y autoselecciona cuando hay una sola alternativa.

QA sin guardar: EDESCE seleccionó companies1/2, branches8/3; default company2 ofreció solo branch3; quitar company2 desmarcó/deshabilitó branches2/3 y restituyó company1/branch8. Edit user3 conservó companies1/2, branches1/2 y defaults2/2. Gamarra company3/branch4 autoseleccionados. No nuevas reglas frontend.

## Plan Limits

TenantPlanService cuenta usuarios habilitados no Platform Admin del tenant, incluye Owner. Máximo desde plan.max_active_users; available=max(0,max-active); activar requiere active<max. Store valida antes del flujo y de crear; reactivar también valida. No plan produce error. No se recalculan licencias desde frontend.

Resumen GET: EDESCE Full, 4 activos, límite6, disponibles2, inactivos0, 66.67%; UI redondeo existente67%. Gamarra Básico, 1 activo, límite2, disponible1, inactivos0,50%. Plan Summary: QA PASSED. No dataset agotado en estos tenants: limit_reached se conserva por lectura del flag; escenario available=0 no ejecutado ni fabricado.

## Temporary Password

Servicio genera temporal criptográficamente aleatoria (random_int), longitud por defecto10 con clases de caracteres y mezcla. Store guarda hash, must_change_password=true y devuelve temporal solo en respuesta de creación. JS existente muestra respuesta escapada y redirige. Auditoría estática exclusivamente: NO se llamó generate ni store.

## Reset Password

SENSITIVE MUTATION — OUT OF SCOPE. Permiso resetPasswordUser_configUserWeb; scoped lookup, rechazo self/Owner; genera temporal, hash, must_change_password=true, remember_token=null; temporal entregada en respuesta. Affordance y confirmación originales conservadas dentro del dropdown. No se abrió confirmación ni se ejecutó reset.

## Status

enable define Activo/Inactivo. POST status acepta0/1; protege self/Owner; mismo estado retorna sin cambios; activar revisa plan; deshabilitar limpia remember_token y guarda enable. Conserva Worker, roles, memberships e historial. No hay campo status en create/edit: acción de listado separada. STATUS MUTATION QA — PENDING.

## Worker Relationship

User != Worker. User::workers hasMany por user_id; compatibilidad worker hasOne restringida al tenant/company activos, sin contexto retorna relación vacía. ConfigUserWeb no crea ni vincula/desvincula Worker. No se infiere autoría de ventas.

## UserController Legacy Boundary

BACKEND SYNC REQUIRED / EXCLUDED. UserController contiene User::all(), Role::all() y rutas antiguas separadas. No se integraron access.users, getUsers/getUsers2/getRoles ni users/to/workers. No se añadieron a allowlist. No se ejecutaron sus endpoints. La rama actual sí tiene listCashMovementAdmin_cashBox; Admin Movements sigue fuera de esta fase.

## Fields

Create: name,email (required max255),role_id,companies[],branches[],default_company_id,default_branch_id,@csrf. Edit añade image opcional (validación jpg/jpeg/png/webp max2048); nombre/email y valores actuales conservados, Owner sin assignments editables. Email unique global `users.email`, en update ignora usuario actual. No se supone uniqueness por tenant.

Se preservaron IDs,names,data-url,data-company-id,defaults,CSRF y arrays mediante comparación estática contra HEAD. Formularios siguen usando serialize (create) y FormData (edit). Los botones originales btnSaveTenantUser se asocian por atributo form al formulario correspondiente en el header sticky compartido. No se cambió create.js ni edit.js. No había inicialización Select2: se mantienen selects nativos y hooks reales.

## Action Matrix

| Action | Route | Permission | Read/Mutation/Sensitive | Included in QA |
|---|---|---|---|---|
| List + AJAX | index/getUsers | listUser_configUserWeb | Read | Sí, ambos Owners |
| Plan | planSummary | listUser_configUserWeb | Read | Sí, ambos Owners |
| Create GET | create | createUser_configUserWeb | Read | Sí, opciones sin guardar |
| Edit GET | edit | editUser_configUserWeb | Read | Sí, normal EDESCE y Owners |
| Store | store | createUser_configUserWeb | Mutation | No |
| Update/image | update | editUser_configUserWeb | Mutation | No |
| Status | changeStatus | changeStatusUser_configUserWeb | Mutation | No |
| Reset | resetPassword | resetPasswordUser_configUserWeb | Sensitive | Solo inspección estática/affordance |

## Secret Handling

TEMP PASSWORD EXPOSED: NO
EXISTING PASSWORD EXPOSED: NO
RESET RESULT GENERATED: NO
REMEMBER TOKEN EXPOSED: NO

Credenciales de QA se usaron únicamente para login autorizado, sin copiarlas a código, docs, fixtures, capturas ni archivos de QA. No se consultaron hashes/tokens ni se registraron modelos completos. Evidencia visual de formularios vacíos sin emails.

## Visual Mapping

REUSE DIRECTLY: next-page-heading/toolbar, next-form-section/section-header, next-list-toolbar, next-list-filters-grid, next-row-actions, badges Bootstrap y tabla responsive. Referencia material.create de venti-next y Tenant Roles / Companies integrados.

REUSE WITH CONTRACT ADAPTATION: resumen neutral con métricas reales y progress existente; companies agrupadas con branches; rol único; estado separado del formulario. Actions con permisos del servidor. Se retiraron los estilos locales de las tres vistas y las small-box coloreadas. La acción sticky pasa al header compartido (form explícito), sin regla sticky nueva.

ALLOWLIST MATCH: YES: únicamente configUserWeb.index/create/edit. Carga venti.min.css + venti-next.css comprobada en navegador. CSS nuevo: 0. Backend, auth, servicios y rutas: UNCHANGED.

## Responsive QA

| Width | List | Create | Edit | Global overflow |
|---|---|---|---|---|
| 320 | PASS | PASS | PASS | NONE |
| 375 | PASS | PASS | PASS | NONE |
| 390 | PASS | PASS | PASS | NONE |
| 576 | PASS | PASS | PASS | NONE |
| 768 | PASS | PASS | PASS | NONE |
| 992 | PASS | PASS | PASS | NONE |
| 1200 | PASS | PASS | PASS | NONE |
| 1440 | PASS | PASS | PASS | NONE |

Matriz geométrica real en navegador EDESCE; controles dentro del viewport; tabla con scroll local permitido. Inspección visual desktop y móvil; creación Gamarra con defaults. Selector único y checkboxes no reemplazados. Menú de usuario normal muestra Editar/Resetear/Inhabilitar; Owner solo Editar. Búsqueda sin coincidencias, estado inactivo vacío y page-size25 probados; dataset4/1 no permite QA de segunda página real. No dataset sintético añadido.

Consola de recorrido final: sin errores/warnings capturados. Hallazgo preexistente: una imagen de usuario EDESCE no carga desde la URL enviada por backend; no se modificó ese registro/asset. No se declara que todos los uploads existentes estén sanos.

Evidencia fuera del repo: carpeta `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/config-user-web`, create-desktop.png y create-mobile.png. Capturas viewport; el export full-page del navegador no estuvo disponible. Viewport restaurado.

Validación: PHP7.3.33 artisan view:cache PASS; node --check index.js PASS; git diff --check PASS; comparación IDs/names/data hooks y CSRF create/edit PASS. Sin commit.

## Cross-tenant QA

| Actor | Target edit | Resultado |
|---|---|---|
| Owner EDESCE | Gamarra7 | HTTP404 |
| Owner EDESCE | Platform1 | HTTP404 |
| Owner EDESCE | inexistente99999999 | HTTP404 |
| Owner Gamarra | EDESCE2 y4 | HTTP404 |
| Owner Gamarra | Platform1 | HTTP404 |
| Owner Gamarra | inexistente99999999 | HTTP404 |

GETs reales en sesiones autenticadas. Auditoría adicional READ ONLY: Owner de otro tenant8 rechazado404 en ambos; guard de actor Platform403. Opciones companies/branches/roles separadas en ambos tenants. Tenant Isolation — QA PASSED para estos read paths; no sustituye QA de mutaciones.

## Backend Sync Notes

[OPEN]
Point: UserController legacy conserva contratos distintos y consultas globales.
File / endpoint: UserController y rutas legacy.
Impact: no modernizar ni usar como sustituto del autoservicio tenant.
What backend needs to define or correct: contrato y aislamiento de legacy.
Frontend can continue: Yes; ConfigUserWeb separado y legacy EXCLUDED.

[OBSERVED / NO BACKEND CHANGE]
Point: ConfigUserWeb protege Owner en controlador, no middleware tenant.owner.
File / endpoint: todas las acciones ConfigUserWeb / gatherMiddleware.
Impact: documentar mecanismo real; no afirmar middleware ausente como protección existente.
Frontend can continue: Yes, guard y scoped lookup comprobados.

[PENDING QA]
Mutaciones store/update/status/image y validaciones persistentes no ejecutadas. Reset sensible fuera de alcance. Disponible0, companies sin branches y segunda página sin dataset disponible no simulados. Imagen histórica ausente documentada para reparación de datos/assets fuera de esta fase.

## Revisit Triggers

Cambios en guard Owner, permisos, tenantUsersQuery, roles asignables, pivots/defaults, validación global email, licencias/contabilización, contrato de temporal, Worker linkage o habilitación de QA de mutaciones. No volver a Settings/Cash/Entries por esta fase salvo regresión demostrada.

## Final Status

- ConfigUserWeb List: FRONTEND INTEGRATED.
- Create / Edit: FRONTEND INTEGRATED.
- Tenant Isolation: QA PASSED (lectura).
- Role / Company / Branch Assignment: FRONTEND ALIGNED / BACKEND AUTHORITATIVE.
- Plan Summary: QA PASSED.
- Mutations: QA PENDING.
- Password Reset: SENSITIVE MUTATION — OUT OF SCOPE.
- Secret Handling: QA PASSED.
- UserController Legacy: BACKEND SYNC REQUIRED / EXCLUDED.
- Backend: UNCHANGED.
