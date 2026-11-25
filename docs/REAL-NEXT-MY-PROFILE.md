# REAL NEXT — My Profile

## R7.7B — reconciliación Perfil / Material Edit — 2026-11-25

Ver [informe focalizado](REAL-NEXT-PROFILE-MATERIAL-POST-DEV-RECONCILIATION.md). Avatar y Guardar material ya disponibles: FRONTEND PARITY PASS, sin gates obsoletos encontrados ni cambios runtime. Chrome aislado, payloads/errores/éxitos interceptados; 24 checks responsive sin overflow y cero pageerrors. Persistencia real no ejecutada, QA mutadora pendiente. MAT-01 permite historia/stock/shared-master; MAT-03 preserva Items y ownership sin prohibición general shared-master. IDs canónicos: MAT-04 multinivel, MAT-05 flags (la tabla R7.5 invirtió los títulos). PROFILE-01 fix DEV preservado, no nuevo cierre USER-01. No se repite certificación backend. Sin nuevos findings ni FR: **74 recuperadas +10 bloqueadas +23 excluidas =107**. R7.7C/R8 no iniciadas. Historia anterior conservada.


2026-11-18 — **MY PROFILE — FRONTEND INTEGRATED WITH BACKEND DEPENDENCY**.
Visual/read path PASS. Mutations SELF enforcement: **BACKEND SYNC OPEN**; mutations/upload QA PENDING. Backend unchanged, CSS nuevo0, sin commit.

## Contract / Routes

| Route | Name | Controller | Contract |
|---|---|---|---|
| GET /dashboard/perfil | user.profile | UserController@profile | Usuario autenticado con roles |
| POST /dashboard/change/image/user/{user} | user.change.image | changeImage | Imagen existente, multipart |
| POST /dashboard/change/settings/user/{user} | user.change.settings | changeSettings | Nombre; email requerido pero no se actualiza |
| POST /dashboard/change/password/user/{user} | user.change.password | changePassword | Contraseña actual + nueva confirmada |

Middleware del grupo: web, auth, check.user.enabled, password.changed, tenant.context. Sin permission específico adicional en estas rutas. No se cambia middleware, CSRF, route model binding, métodos ni payloads. No endpoints nuevos.

## SELF Boundary

GET consulta User::with('roles')->find(Auth::user()->id), sin aceptar ID externo. QA real de lectura con usuario4 EDESCE/T1/C1/B1 y7 Gamarra/T3/C3/B4, query user_id=999999: devuelve respectivamente4/7 y sus roles. No se usa Worker como identidad. No añade selects de tenant/company/branch.

**BACKEND SYNC OPEN:** los tres POST reciben User $user desde {user}; changeImage/changeSettings no comprueban Auth::id()==$user->id. FormRequests settings/password authorize=true; no UserPolicy ni binding SELF encontrado. changePassword verifica la contraseña del usuario enlazado, pero tampoco exige identidad SELF. TenantContext valida contexto de sesión, no ownership de ese parámetro.

Por tanto, enlaces propios en Blade NO certifican aislamiento de mutaciones; no se arregla filtrando JS ni se afirma SELF PASS para POST. No se realizaron solicitudes a IDs ajenos ni mutaciones. DEV debe definir/enforzar ownership backend para cerrar esta parte. Integración visual preserva exactamente acciones existentes; no amplía rutas/permisos. No cerrar USER-01 legacy por este trabajo ni confundir con Required Password Change (controller independiente ya validado).

## Data / Fields / Validation

Se muestran únicamente name, email, image y roles.description obtenidos del backend. Empty roles: “Sin roles asignados”. Foto conserva images/users/{image}. No datos laborales, direcciones ni otros campos inventados.

- Información: formSettings; name editable, email readonly, ambos nombres/IDs existentes. UpdateUserSettingsRequest: name required|string|max:255; email required|string|email|max:255. JS previo mantiene minlength5 para nombre; discrepancia preexistente documentada, no se endurece ni reescribe aquí. old(name) añadido como presentación segura.
- Imagen: formImage, image, btnImage, btnCancel, changeImage; FileInput existente acepta jpg/png/jpeg y máximo1 archivo en frontend. Backend mueve archivo a images/users/{id}.{extension}; no se certifica seguridad de upload por esa restricción de plugin. No upload ejecutado.
- Contraseña: formResetPassword, current_password, new_password, new_password_confirmation. Request required|string|min:8 para actual/nueva y confirmed para nueva. Backend Hash::check actual y bcrypt nueva; no cambio de este flujo. autocomplete current-password/new-password añadido. Password values no repoblados ni registrados.

Se conservan data-url con ID propio, enctype, @csrf, submit real, callbacks FormData/POST, tabs settings/passwordReset, clases/selectores de showPassword y contenedor col-sm-8 usado por errorPlacement de jQuery Validation. JavaScript public/js/user/profile.js intacto. No cambio funcional intencionado.

## Visual Mapping

Patrón aprobado: formulario seccionado + ficha de identidad, shell Venti Next actual, heading/toolbar context-only, next-form-section, cards Bootstrap y nav-pills existentes. Panel identidad4/12 y formulario8/12 a partir de lg; apilado antes para evitar columnas estrechas. Table/CRUD no añadido.

Surface Spacing Standard aplicado a wrappers, no reglas individuales. Primary para guardar, secondary para imagen/cancelar. Labels de confirmación corregidos y botones de visibilidad con nombre accesible. Sin roles, estado vacío textual. CSS inline kv-avatar/kv-reqd sin consumidor eliminado; ningún CSS nuevo ni cambios globales.

Allowlist: sólo user.profile. Preserva anteriores password.required.edit y otras integraciones. Carga venti.min.css/venti-next.css por mecanismo appAdmin2. Ningún POST ni endpoint JSON añadido.

## QA Evidence

Lecturas reales con Auth/contexto explícitos en harness y transacción MySQL READ ONLY/ROLLBACK. Roles1 para cada Owner, IDs propios confirmados aun con query externo. No sesión HTTP end-to-end ni escritura a cuentas reales en esta fase.

Chrome headless con HTML real renderizado, assets locales, shell externo/notifications interceptados. EDESCE/Gamarra × pestaña información/contraseña/imagen abierta ×320/375/390/576/768/992/1200/1440: scrollWidth=viewport, **GLOBAL OVERFLOW NONE**. Espera de transición del sidebar antes de capturas. Capturas desktop1440 y mobile375 revisadas. Tabs, abrir/cancelar imagen PASS; readonly email PASS; sin pageerrors ni POST durante QA.

Conservados CSRF en3 formularios y JS/plugin hooks. No ejecución de guardar nombre, contraseña o imagen; **MUTATION / UPLOAD QA PENDING**. Preview con archivo elegido y respuesta persistente no certificados. No nueva suite para cambios de markup de bajo impacto; regresión aplicable: compile Blade, revisión DOM/hooks y matriz navegador. PHP7.3 artisan view:cache PASS; git diff --check PASS. JS no modificado.

## Files / Status

- resources/views/user/profile.blade.php
- config/venti-next.php (user.profile)
- docs/REAL-NEXT-MY-PROFILE.md

Cambios previos de Required Password Change/test/documentación preservados. Backend, esquema, JS funcional y módulos ajenos intactos. Sin commit.

Estado: frontend visual PASS; lectura SELF PASS; autorización SELF de mutaciones BACKEND SYNC OPEN; mutaciones/upload QA PENDING. No se declara el flujo completo seguro por una modernización visual.

## PROFILE-01 Closure — 2026-11-22

Esta sección supera el estado histórico de ausencia de SELF. **SELF ownership PASS; cierre integral de mutaciones NOT CLOSED** debido a imagen. Required Password Change y USER-01 global permanecen separados; no se reabrieron.

### SELF Guard

| Mutation | Route name / path | Method | SELF Guard | Permission |
|---|---|---|---|---|
| Imagen | user.change.image / dashboard/change/image/user/{user} | POST | EnsureOwnProfile + assertSelf en action | Sin adicional |
| Settings | user.change.settings / dashboard/change/settings/user/{user} | POST | Mismo guard doble | Sin adicional |
| Password | user.change.password / dashboard/change/password/user/{user} | POST | Mismo guard doble | Sin adicional |

artisan route:list confirma web, auth, check.user.enabled, password.changed, tenant.context, EnsureOwnProfile. Binding User $user. SelfProfileAccess compara estrictamente IDs convertidos a string; middleware corre antes de FormRequest y controller vuelve a defender. No sustituye SELF por tenant/company/branch.

### Settings Mutation

PASS aislado: propio actualiza name; email es requerido/validado pero conserva valor original por contrato. Vacío422 con errores name/email. Payload tenant_id, is_platform_admin, password y user no altera esos campos ni destino. Transacción termina en0.

### Password Mutation

PASS aislado: campos vacíos422, confirmación distinta422, actual incorrecta422 con hash intacto y transactionLevel=0; query posterior funciona. Correcta200: hash nuevo verifica y anterior deja de verificar; hash ajeno intacto. Usuario autenticado y remember_token se conservan. Controller no invalida otras sesiones/tokens ni cambia must_change_password; no se afirma revocación global. Se valida hash/estado auth, no login E2E por servidor web.

### Image Mutation

PNG mínimo propio200, image=1.png y archivo presente. Sin archivo422. **Aceptación integral FAIL:** texto inocuo qa.txt también200 e image=1.txt. No hay validación backend de MIME/extensión/tamaño; jpg/png/jpeg sólo es restricción del plugin. No existe máximo contractual de aplicación para certificar tamaño permitido/rechazado. Límites PHP/webserver no sustituyen validación de endpoint.

### Negative Ownership Tests

Las tres actions rechazan otro usuario mismo tenant403 y otro tenant403. Inexistente fail closed403/404 según orden guard/binding. Snapshot completo de users intacto. Dos uploads ajenos403 antes de move: fuente sigue existiendo y destino vacío. Manipulación user/tenant del payload no cambia destino ni privilegios.

Suite usa router Laravel con SubstituteBindings y middleware real del controller, usuarios SQLite temporales. No ejecuta toda la cadena auth/tenant de producción: se inspeccionó route:list; no presentar la suite como HTTP E2E de todos los middleware.

### Storage / Rollback

Destino public/images/users/{id}.{clientOriginalExtension}. Move ocurre antes de save, sin transacción filesystem. Mismo nombre sobrescribe; extensión distinta deja anterior. Reproducido en carpeta temporal: previous.jpg queda tras reemplazo; evento saving forzado a fallar produce500 y destruye contenido previo de 1.png aunque DB no cambie. No rollback/cleanup. No se expuso archivo QA en public real.

[OPEN] Validación y persistencia de imagen: UserController::changeImage. DEV debe definir tipos/tamaño, validar antes de mover, preservar avatar previo al fallar DB y limpiar archivos nuevos/anteriores según éxito/fallo. No se inventa política de tamaño ni se cambia arquitectura durante QA.

### HTTP/UI Evidence

Chrome headless aislado; HTML actual renderizado desde MySQL READ ONLY/ROLLBACK: EDESCE4 y Gamarra7 muestran SELF correcto. POST del navegador interceptados con respuestas controladas; ninguna mutación de Owners. Mutaciones reales del controller probadas por separado en suite SQLite.

Settings success/validation, password error, imagen success/error y preview PASS. Cinco POST conservan CSRF y FormData multipart, cero pageerrors. Callback conserva reload; avatar persistido tras reload no certificado end-to-end. Sin rediseño ni nueva matriz responsive completa. Evidencia local: %TEMP%/profile-closure-ui.json y profile-closure-*.png.

Defecto UI reproducido antes de corregir: error imagen sólo decía Error porque leía jqXHR.message. Corrección mínima en public/js/user/profile.js: responseJSON.message con fallback. Segunda ejecución confirma texto No se pudo guardar la imagen. Sin cambios de payload/estética.

### Tests / Cleanup / Final Status

Reproducción: PHP7.3 vendor/phpunit/phpunit/phpunit tests/Feature/ProfileMutationClosureTest.php --do-not-cache-result. **5 tests / 56 assertions PASS**; tests con observed/debt caracterizan defectos actuales, NO certifican seguridad del upload. SQLite :memory:, red de negocio no utilizada, almacenamiento temporal aleatorio eliminado por teardown. Sin cuentas persistentes ni contraseñas de Owner modificadas.

Read SELF PASS; Settings PASS; Password PASS; imagen happy path PASS / validación y rollback FAIL. **PROFILE-01 ownership corregido; PROFILE-01 MUTATION CLOSURE OPEN** hasta corregir imagen. USER-01 separado. Backend unchanged; frontend sólo mensaje error; CSS0; node --check PASS; git diff --check PASS. No commit.

Archivos: public/js/user/profile.js; tests/Feature/ProfileMutationClosureTest.php; docs/REAL-NEXT-MY-PROFILE.md. Blade intacto: @csrf en tres forms, image multipart, data-url con ID propio, AJAX POST preservado.
