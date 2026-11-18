# REAL NEXT — Required Password Change

## Final Password Mutation QA — 2026-11-18

**USER-01 — PASS / READY TO CLOSE (alcance de esta solicitud: Required Password Change SELF).**
**PASSWORD MUTATION QA — PASS**. Esta actualización sustituye los estados QA PENDING y las restricciones de no POST de la evidencia inicial conservada abajo. El finding histórico USER-01 de directorios/listados legacy globales NO se cierra por este resultado.

Autorización explícita del usuario para POST reales y cuentas temporales locales. Cuenta temporal creada con must_change_password=1, habilitada y con asignaciones de company/branch existentes para comprobar acceso al dashboard; sin roles administrativos nuevos, sin tocar cuentas existentes. Credenciales aleatorias mantenidas sólo en memoria de QA, sin persistirlas en scripts/docs/logs.

| Prueba | Evidencia | Resultado |
|---|---|---|
| Login con contraseña temporal | Login real; dashboard redirige al formulario obligatorio | PASS |
| Campos vacíos | POST real422 | PASS |
| Contraseña demasiado corta | POST real422 | PASS |
| Confirmación distinta | POST real422 | PASS |
| Reutilizar contraseña temporal | POST real422 | PASS |
| Validaciones no mutan credenciales | Hash anterior todavía válido, nuevo no válido, flag1 | PASS |
| Cambio desde formulario real | Botón Actualizar contraseña, POST200, confirmación Continuar | PASS |
| Persistencia | Hash::check nuevo=true/anterior=false; flag=false; remember_token=null | PASS |
| Acceso posterior | Dashboard real200 y GET formulario redirige al dashboard | PASS |
| Contraseña anterior deja de autenticar | Login real en otro contexto de navegador:422 | PASS |
| Nueva contraseña autentica | Login real y dashboard200 en contexto nuevo | PASS |
| Limpieza | Usuario temporal y pivots eliminados;3 sesiones temporales identificadas por user ID eliminadas;0 usuarios QA restantes | PASS |

No existe campo “contraseña actual”: contraseña incorrecta se cubre con rechazo del login anterior y con la prohibición backend de reutilizar la temporal. No se añadió validación inexistente.

Regresión: no había suite específica de este flujo; se añadió RequiredPasswordChangeTest, SQLite :memory: explícito. **3 tests /29 assertions PASS**: required/min/confirmed, rechazo de temporal, credenciales intactas ante errores, cambio SELF ignorando user_id/email ajenos, hash/flag/remember_token, regeneración del ID de sesión, rechazo de segundo cambio, redirect posterior y protección de invitados GET/POST. La prueba SQLite no reemplaza el POST real con CSRF, ejecutado por separado.

No defectos funcionales encontrados; no cambios backend/frontend adicionales en este cierre. Se conserva la integración previa sin modificar diseño. Archivos de este cierre: tests/Feature/RequiredPasswordChangeTest.php y este documento. Sin dependencias ni commit. git diff --check PASS. PHPUnit sólo advierte esquema XML deprecado preexistente.

## Evidencia inicial de integración (anterior al QA de mutación)

2026-11-18. **Required Password Change — FRONTEND INTEGRATED**. Scope SELF; External IDs NONE; Backend UNCHANGED. **PASSWORD MUTATION QA — PENDING**.

## Routes

| Route | Method | Name | Middleware | Read/Mutation |
|---|---|---|---|---|
| /password/cambio-obligatorio | GET | password.required.edit | web, auth, check.user.enabled | Read |
| /password/cambio-obligatorio | POST | password.required.update | web, auth, check.user.enabled | Mutation — no ejecutada |

Vista auth.required-password-change, controller RequiredPasswordChangeController. Sin FormRequest específico, validación inline. Web incluye sesión, CSRF y bindings existentes.

## Middleware

La propia pantalla no usa password.changed ni tenant.context: evita bucle de redirección y no requiere contexto ERP. EnsurePasswordChanged redirige al formulario cuando must_change_password=true en los grupos protegidos del ERP. CheckUserEnabled invalida sesión y regenera CSRF al detectar enable=0, después redirige login con error; no se ejecutó contra cuenta real.

GET controller: usuario con flag1 recibe formulario; flag0 redirige dashboard.principal (/dashboard/principal); sin usuario redirige login. Auth middleware también dirige invitados HTML a login y responde401 para JSON según negociación. POST controller sin usuario tiene fallback401; flag0 devuelve422 “La contraseña ya fue actualizada”. Middleware puede interceptar antes. Sin cambios de middleware ni redirects.

Verificación no persistente con App\User sólo en memoria: flag1 render y redirección de EnsurePasswordChanged al formulario PASS; flag0 redirect dashboard PASS. Metadata GET/POST comprobada. Invitado/deshabilitado auditados por código; no se alteraron cuentas para forzar casos.

## SELF Contract

edit/update usan exclusivamente Auth::user(). No parámetro de usuario, email ajeno, user_id, tenant/company/branch ni hidden de identidad. CSRF es el único hidden del formulario de cambio. No UserController ni ConfigUserWeb auditados/modificados. No relación con reset/forgot password.

## Fields

password: nueva contraseña; password_confirmation: confirmación. No campo de contraseña actual. IDs/names/formRequiredPasswordChange/data-url/btnSavePassword/btn-show-password/data-target preservados. Ambos type=password y autocomplete=new-password; required existente. Sin values, old(password), almacenamiento ni logs de secretos. Gestor de contraseñas habilitado; sin autocomplete=off.

## Validation

Backend password: required, string, min:8, confirmed. Sin max, regex ni requisito de complejidad adicional. Hash::check impide reutilizar la contraseña temporal actual, con422 message. JS conserva controles previos mínimo8/coincidencia; no agrega reglas. Laravel errors por campo se presentan con texto escapado, is-invalid, aria-invalid y aria-describedby. Resumen role=alert recibe foco. Blade también soporta error bag Laravel sin repoblar contraseñas.

Error states probados sin servidor mutador: longitud inválida, confirmación distinta,422 con errors,401 y419. Se usa stub de $.ajax en navegador; no POST real. 401/419 muestran instrucciones de iniciar sesión/recargar, sin navegación automática nueva. Otros errores conservan message backend o fallback. Los avisos de error del plugin se sustituyen por mensajes inline accesibles; confirmación de éxito/redirect del plugin permanece.

## Success Flow

Inspección de código únicamente: Hash::make(password), must_change_password=false, remember_token=null, user->save(), session()->regenerate(); JSON message y redirect a dashboard.principal. JS conserva alert de éxito y navegación a response.redirect al confirmar Continuar. No se ejecutó success flow ni logout real.

## Security

Ninguna credencial real introducida, cambiada, registrada o guardada. Datos de prueba efímeros sólo en memoria del navegador y campos vaciados antes de capturas. Nada en localStorage/sessionStorage. No cambios de autenticación, autorización, validación backend, CSRF, sesión o remember-me. Mostrar/ocultar sigue siendo acción explícita de usuario, con nombre accesible y aria-pressed; por defecto oculto.

## Visual / Allowlist

Superficie de seguridad compacta centrada en su documento standalone original; no sidebar, tabla ni CRUD ERP. Reutiliza grid/utilidades Bootstrap4, card, next-form-section, next-list-toolbar y estilos Venti Next; elimina CSS inline específico. Texto “Debes actualizar tu contraseña antes de continuar.”; acción “Actualizar contraseña”. Logout original conservado.

El mecanismo allowlist usa **nombres de ruta**, no nombres Blade. Se añade exclusivamente password.required.edit (correspondiente a auth.required-password-change), no una entrada inoperante auth.required-password-change. Blade standalone aplica enabled+allowlist para cargar venti.min.css/venti-next.css; AdminLTE/plugins permanecen. No otras vistas/rutas User habilitadas. CSS nuevo0.

## Accessibility

main, encabezado h1 y región etiquetada; labels asociados a IDs. Orden natural: nueva, mostrar nueva, confirmación, mostrar confirmación, submit, logout. Botones de visibilidad con aria-controls/pressed/nombre actualizado. Ayuda y errores asociados; resumen enfoc​able con tabindex=-1/role=alert. Focus visible mediante estilos compartidos. Enter en confirmación dispara submit existente: comprobado con transporte AJAX sustituido, nunca POST real. Toggle mostrar/ocultar PASS; password manager no deshabilitado. No se afirma prueba manual con lector de pantalla.

## Responsive

Chrome headless con HTML real renderizado y assets locales: normal, inválida, mismatch,422,401,419 ×320/375/390/576/768/992/1200/1440. Sin overflow global ni errores JS. Capturas normal/error a375/1440 revisadas, formulario centrado y usable; campos vacíos en evidencia. No solicitudes POST durante QA. Render usa usuario sintético no persistido, no sesión HTTP real con flag forzado.

## USER-01 Boundary

**USER-01 — OPEN / NOT APPLICABLE TO THIS SURFACE**. Los directorios/listados legacy globales siguen fuera de alcance; este contrato SELF no los resuelve ni los reactiva.

## Mutation Boundary

**PASSWORD MUTATION QA — PENDING**. No POST real de cambio, no logout real, no update del flag, no alteración de contraseñas/remember_token/sesiones reales. Validación exitosa y persistencia pendientes de QA autorizado separado.

## Files / Checks

- resources/views/auth/required-password-change.blade.php
- public/js/auth/required-password-change.js
- config/venti-next.php (password.required.edit)
- docs/REAL-NEXT-REQUIRED-PASSWORD-CHANGE.md

node --check PASS; PHP7.3 artisan view:cache PASS; git diff --check PASS. Backend/CSS sin cambios, sin commit. No suite nueva para este ajuste de presentación; evidencia de flujo no persistente y errores simulados descrita arriba.
