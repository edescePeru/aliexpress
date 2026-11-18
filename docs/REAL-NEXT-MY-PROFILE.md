# REAL NEXT — My Profile

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
