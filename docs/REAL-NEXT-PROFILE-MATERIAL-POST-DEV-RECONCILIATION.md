# REAL NEXT — R7.7B — Profile & Material Edit reconciliation

Fecha: 2026-11-25. HEAD auditado: `66f4c60`, rama `feature/venti-next-frontend`. Runtime PHP 7.3.33; aplicación `http://127.0.0.1:8080`. Infraestructura intacta.

## Resultado y estado anterior

**2/2 superficies revisadas; FUNCTIONAL PARITY — FRONTEND PASS.** Avatar y Guardar material ya estaban disponibles en HEAD. No se encontró un gate frontend obsoleto que retirar. No se cambia código para simular una recuperación inexistente. Capacidades nuevas recuperadas: 0; capacidades perdidas: 0. Se preservan 74 recuperadas + 10 bloqueadas + 23 excluidas = 107 FR únicos.

La nota histórica Material Edit de noviembre 5 no constituye un bloqueo UI vigente: las decisiones posteriores DEV permiten editar con historia y desactivar con stock/reservas. La evidencia anterior se conserva; esta sección y el informe R7.5 explican su sustitución contractual. No se cierra QA de persistencia por pruebas interceptadas.

## Perfil: contratos actuales DEV

| Capacidad | Contrato | Estado frontend |
|---|---|---|
| Lectura | GET `/dashboard/perfil`, `user.profile`, usuario autenticado | Disponible, pestañas y valores conservados |
| Avatar | POST `/dashboard/change/image/user/{user}`, `user.change.image` | Cambiar/Guardar/Cancelar disponibles; multipart, image y CSRF |
| Configuración | POST `/dashboard/change/settings/user/{user}`, `user.change.settings` | Nombre editable, email readonly; backend sólo persiste nombre |
| Password | POST `/dashboard/change/password/user/{user}`, `user.change.password` | Actual/nueva/confirmación; errores backend visibles |

`UserController` registra `EnsureOwnProfile` para las tres mutaciones; cada acción reafirma `SelfProfileAccess::assertSelf`. El servicio de avatar también lo exige. No se deriva ownership de settings/password del fix de imagen. Se mantienen middleware de autenticación/contexto y binding existente, sin ampliar permisos. Se inspeccionó código; no se repitieron pruebas HTTP negativas de DEV.

`ProfileAvatarService`: archivo real PNG/JPEG, máximo 2048 KB, 4096 por lado, 8388608 píxeles; decode/reencode GD; nombre `avatar-{user}-{32 hex}.png/jpg`, creación exclusiva, transacción, cleanup del nuevo si falla sin referencia DB, retiro del anterior propio y sin referencias sólo después de commit. Destino `public/images/users`. Frontend acepta png/jpg/jpeg, muestra preview, impide doble envío y permite reintentar tras error. No se agregan límites ni formatos. Upload y rollback reales no ejecutados en esta fase.

## Material: contratos actuales DEV

GET `material.edit`: `/dashboard/editar/material/{id}`; POST `material.update`: `/dashboard/material/update`. Permiso `update_material`, `MaterialParametersGuard:material`, `MaterialInventoryGuard:form`. Se conserva tenant maestro y catálogo contextual, inventario company + branch/general. No se convierte el maestro en propiedad exclusiva de branch.

| Finding canónico | Reconciliación |
|---|---|
| MAT-01 | Guardar disponible. No gate UI por historia/stock/reservas/shared-master. `MaterialEditValues` conserva campos omitidos; explícitos pasan validación. No imponer reglas descartadas |
| MAT-03 | `MaterialRetaceriaPolicy` actual permite shared-master conforme decisión MAT-01 posterior; exige tenant/catálogo/transacción y tipo propio. No modifica Items históricos. No restaurar prohibición general anterior ni propagación física |
| MAT-04 — niveles | Payload conserva ID InventoryLevel y warehouse. Location informativa, no editable: backend resuelve ubicación por ID y preserva identidad. Request/service deduplican posición, no warehouse solo. Fixture frontend con dos IDs/ubicaciones en mismo warehouse conserva ambos |
| MAT-05 — flags | Switches envían 0/1 explícitos; no coerción de texto arbitrario en frontend. Backend valida antes de convertir. Omisiones resueltas por DEV; inputs disabled no serializados por FormData. `tipo_variantes` se agrega explícitamente desde modo persistido |

**Corrección de documentación:** la tabla de reconciliación R7.5 intercambió los títulos MAT-04 y MAT-05. Se usan los IDs canónicos del backlog y del encargo actual: MAT-04 multinivel, MAT-05 flags. No son nuevos findings.

Restricciones legítimas preservadas: modo simple/variantes persistido readonly; identidad talla/color de variantes existentes; stock/reservas/costos/warehouse/location informativos; no eliminación inventada de variantes; validación SKU y combinaciones; campos según MaterialDetailSetting, snapshots y errores DEV. Pack conserva input deshabilitado cuando corresponde y payload contractual actual. No se agregan campos fiscales ni valores de impuesto ausentes; el backend sigue validando.

## Dataset y alcance de evidencia

Lectura con transacción MySQL READ ONLY y ROLLBACK; eventos Audit cancelados sólo en proceso de QA. Owner EDESCE user4, tenant1/company1/branch1. Render vía controlador y Blade reales con contexto, permiso update_material y guards de formulario; no login HTTP end-to-end nuevo.

- Material 1: POLO NEGRO, StockItem71; niveles64 (warehouse1/location1, 32 disponibles físicos/3 reservados en DTO) y112 (warehouse8/location8,24/0). Save habilitado pese a existencias/reservas.
- Material13: Zapatillas; ocho variantes y niveles38–45. Alternativa existente para revisar modo variantes.
- Perfil: usuario propio4; formularios apuntan a /4. No cambio de contraseña/imagen/nombre real.

## Evidencia frontend

Chrome headless aislado; HTML generado por Laravel y assets actuales, todos los POST interceptados antes de red. No se controló escritorio ni navegador del propietario. Los datos reales se usan sólo para render; respuestas de éxito/error son **SIMULADAS**, no certificación de mutaciones.

- 24 comprobaciones responsive: Perfil + Material1 + Material13 a 320/375/390/576/768/992/1200/1440. Cero overflow global; tablas con scroll local.
- Render de Guardar y valores iniciales; pestañas Perfil, preview PNG sintético, validación sin archivo, error422 y reactivación de Guardar imagen.
- Diez POST interceptados en las dos corridas principales: avatar/settings/password/material; CSRF y FormData conservados. Mensajes422 y200 visibles; no datos enviados al backend.
- Payload real material1 conserva niveles64/112; material13 conserva ocho variantes/niveles38–45; flags numéricos0/1.
- Fixture identificado SIMULATED: niveles900001/900002, mismo warehouse y ubicaciones distintas; ambos preservados, flags false=0. Sólo DOM/payload interceptado; no registros DB.
- Once modales Material abiertos/cerrados en 375px y nivel de inventario expandido: sin overflow global; cero POST en esa corrida.
- Cero pageerrors en las tres corridas. Assets externos Dropzone existentes se mantuvieron; no dependencia nueva.

Artefactos fuera del repositorio: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`:
`r77b-read.php`, `r77b-data.json`, `r77b-browser.cjs`, `r77b-results.json`, `r77b-extra.cjs`, `r77b-extra-results.json`, `r77b-modals.cjs`, `r77b-modal-results.json`; capturas `r77b-profile-375.png`, `r77b-profile-1440.png`, `r77b-material1-375.png`, `r77b-material13-1440.png`, `r77b-avatar-simulated.png`. Capturas revisadas sin rediseño.

## QA pendiente y Backend Sync Notes

QA real mutadora pendiente por alcance explícito: upload/rollback filesystem, cambios de password/sesión, settings persistidos, Save material/flags/minmax/retacería. DEV ya aportó validación aislada registrada en reconciliación R7.5; esta fase no la repite ni afirma haberla ejecutado. Ningún nuevo defecto backend demostrado. No reabrir USER-01, J/P, K ni INV-01 residual; no cerrar los diez FR bloqueados ajenos.

Backend/Blade/JS/CSS/rutas/allowlist/infraestructura: 0 cambios. Sólo documentación. Cero mutaciones reales. No commit, push ni merge; R7.7C/R8 no iniciadas.

## Revisión manual

- http://127.0.0.1:8080/dashboard/perfil
- http://127.0.0.1:8080/dashboard/editar/material/1
- http://127.0.0.1:8080/dashboard/editar/material/13

Usar contexto EDESCE company1/local principal para reproducir el dataset indicado.

## Verificación final

`node --check public/js/user/profile.js`: PASS. `node --check public/js/material/edit.js`: PASS. Blade reales compilados/renderizados durante captura READ ONLY. `git diff --check`: PASS. Sólo seis archivos de documentación modificados/creados; sin cambios de implementación.

## R7.7A/B — Owner visual corrections — 2026-11-25

Corrección focalizada posterior a la aprobación funcional. Sin cambios backend, JavaScript operativo, CSS, contratos ni infraestructura. Sin POST ni mutaciones comerciales. Sin commit/push/merge ni inicio R7.7C/R8. Los cambios documentales de R7.7B preexistentes se conservan.

La regla permanente **Desktop-first density for operational field groups** se incorporó a `AGENTS.md` y `docs/VENTI-NEXT-COMPONENT-PATTERNS.md`: proporción, densidad, alineación y aprovechamiento horizontal forman parte de la aceptación visual; ausencia de overflow por sí sola no basta. Comprobación futura R8, sin barrido global actual.

QA Chrome headless aislado con HTML renderizado por Laravel y assets actuales; lecturas bajo transacción READ ONLY, sin controlar navegador/escritorio del propietario. Perfil probado en ambas pestañas a 320/375/768/992/1200/1440/1920/2560. Entrada en esas mismas resoluciones: una fila completa a 1440/1920/2560; redistribución en anchuras menores cuando el área de contenido no alcanza. Cero overflow global y pageerrors. Los 8 controles conservan su orden. Todas las capturas son de viewport real; no certificación de mutaciones.

Comparación de atributos funcionales del HTML completo antes/después (incluye shell): Perfil267 y Entrada303 elementos idénticos en IDs, names, values, methods/actions, CSRF, data-*, href, required/disabled/readonly y handlers inline. Sólo clases visuales, wrapper de grilla y roles/ARIA de pestañas cambiaron. Capacidades perdidas:0. Conteos FR sin cambios:74+10+23=107.

Blade compile PHP7.3.33: PASS. `git diff --check`: PASS. JS sin cambios; no se repite QA funcional de R7.7A/B.

### Perfil: patrón de pestañas

Causa: `nav-pills` aplicaba fondo sólido activo legacy. Reutilizado `nav nav-tabs` ya usado en `cashRegister/summary.blade.php`, dentro de `next-list-toolbar`; borde/superficie neutra y estados de navegación existentes. No se creó un componente alternativo ni regla CSS. Se conservan enlaces #settings/#passwordReset y data-toggle=tab; roles/aria-controls/aria-selected explícitos. Bootstrap sigue gestionando el cambio de panel. Estado activo, hover/foco y cambio alternado PASS (fondo activo blanco en ambos). No se modifican formularios ni el resto de Perfil.

Archivo de implementación: `resources/views/user/profile.blade.php`.

### Evidencia antes/después

- Información: [antes](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-before-profile-settings-1440.png) / [después](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-after-profile-settings-1440.png).
- Contraseña: [antes](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-before-profile-passwordReset-1440.png) / [después](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-after-profile-passwordReset-1440.png).
- Perfil móvil: [antes](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-before-profile-settings-375.png) / [después](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-after-profile-settings-375.png).

Matriz y scripts: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-final-results.json`, `owner-final.cjs`, `owner-tab-states.json`.
