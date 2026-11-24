# REAL NEXT — Workers

2026-11-18. Worker List FRONTEND INTEGRATED. Create/Edit FRONTEND INTEGRATED — MUTATION QA PENDING. Backend UNCHANGED; CSS0; sin commit.

## Worker vs User vs Workforce

Worker es colaborador laboral; User es cuenta de acceso; Workforce es servicio de cotización. Esta fase no modifica los otros dos dominios, ConfigUserWeb ni sus mantenedores.

## Routes

| Surface | Route | Permission | Scope | Read/Mutation |
|---|---|---|---|---|
| worker.index | GET /dashboard/colaboradores | list_worker | tenant/company | Read |
| getWorkers | GET /dashboard/get/workers/ | list_worker, constructor | tenant/company | Read |
| worker.create | GET /dashboard/registrar/colaborador | create_worker | tenant/company | Read form |
| worker.edit | GET /dashboard/editar/colaborador/{id} | edit_worker | tenant/company + ownership ID | Read form |
| worker.store | POST /dashboard/worker/store | create_worker | contexto validado | Mutation excluida |
| worker.update | POST /dashboard/worker/update/{id} | edit_worker | contexto + ownership | Mutation excluida |

## Permissions

web/auth/check.user.enabled/password.changed/tenant.context y permisos anteriores. WorkerCompanyContext registrado en constructor para las tres vistas. Gate consultado en proceso para Owner EDESCE4 y Gamarra7: permisos de vistas true. No bypass Owner asumido. Allowlist añade exactamente worker.index/create/edit; no indexEnable, AJAX, exports ni módulos laborales.

## WorkerCompanyContext

WorkerAccess::context exige usuario habilitado y no Platform Admin, tenant coincidente, company activa del tenant, company_user activo, branch activa de company y branch_user activo. Para edit valida worker(id) antes del find del controller. Store/update comparan hidden form_tenant_id/form_company_id/form_branch_id con contexto, sin cambios.

## WorkerAccess

scope añade workers.tenant_id y company_id. worker(id) además excluye deleted_at y aborta404 si no existe. Middleware real invocado en QA de lectura, no se elude para validar binding:

| Contexto | IDs propios permitidos | IDs ajenos | Inexistente999999 |
|---|---|---|---|
| T1/C1 |1,2,3,4|5 ->404|404|
| T1/C2 |5|1,2,3,4 ->404|404|
| T3/C3 |ninguno|1,2,3,4,5 ->404|404|

Invocación directa del middleware/controller con autenticación y sesión array en proceso; no afirmar login HTTP completo ni E2E. Ningún POST.

## Company-wide Semantics

Scope TENANT + COMPANY. Branch AUTHORIZATION CONTEXT ONLY; no filtro de trabajadores por branch añadido. Se conserva información de empresa/local del formulario y base laboral del trabajador. No nuevos selectores de contexto.

## Dataset

SELECT read-only revalidado antes de capturas: T1/C1 activos IDs1,3,4; inactivo ID2. T1/C2 activo ID5. Gamarra0. Total5 workers no eliminados; listado getWorkers muestra sólo enable1 (3/1/0). Edit directo de inactivo sigue el contrato actual, pero habilitación fuera de fase. Datos personales no copiados a documentación ni fixtures del repo.

## User Link

worker._systemUser intacto: user_id opcional, no crea User ni permisos. WorkerUserLink::options valida contexto, tenant/company membership, cuenta enabled/no Platform Admin y excluye vínculo ya utilizado en misma company (excepto worker actual en edit). Mantiene opción de vínculo actual no disponible. IDs de lookup comprobados: C1 create/edit4,6; C2 create/edit4,3; Gamarra create7. Sin User::all y sin cambiar UserController.

## Labor Catalogs

Cargo/WorkFunction, estado civil, PensionSystem, Relationship, AreaWorker, PercentageWorker existentes. Se consumen sin alterar su scope ni deuda. WorkingDay aparece como bloque comentado en los forms; no se inventa un selector activo nuevo. Usuario asociado se distingue de datos laborales y parentescos.

## List

Operational/Configuration List: heading/toolbar, acción crear según permiso, Columnas visibles desplegable que conserva switches data-column/data-key e IDs. DataTables y query existentes intactos: búsqueda y paginación cliente (10 por defecto), selectors y callbacks originales. Tabla con scroll local y contenedor de espaciado compartido. Configuración visual de columnDefs alinea identificadores/fechas/cantidades y acciones al centro, importes a derecha.

Columnas existentes conservadas: código/id, DNI, nombres/apellidos, dirección, teléfono, email, cargo, género, nacimiento/edad, instrucción, hijos, ingreso/cese, salario diario/mensual, pensión, ESSALUD, asignación, quinta categoría, contrato, estado civil, sistema/porcentaje pensión, observación, área, profesión, motivo cese y acciones. No se añaden campos sensibles. Visibilidad inicial y permisos de render salarial preservados; no se afirma redacción backend del JSON por ocultar columnas.

Acciones legacy/export y modales de fin de contrato se conservan sin reconstruir ni abrir; no forman parte del QA ni se certifican. Estado enable, borrado, contrato y User no se fusionan ni se inventa badge único. getWorkers sólo activos; have_contract procede de contrato existente en este endpoint.

## Create

FormCreate, data-url worker.store, CSRF, enctype, botón btn-submit, relaciones/names/IDs y JS create.js conservados. Se aplica wrapper next-form-section, cards neutras con gutters compartidos y toolbar. Se mantiene colapso AdminLTE, contactos y acciones de formulario sin guardar.

Campos: first_name, last_name, dni, gender, work_function, area_worker, admission_date, termination_date, reason_for_termination, personal_address, email, phone, birthplace, level_school, profession, civil_status, num_children, daily_salary, monthly_salary, pension_system, percentage_pension_system, pension, observation; contacts[]/relations[]/phones[]; user_id y snapshots hidden del partial. Campos auxiliares/read-only/cálculos del JS no modificados. No endurecimiento de validation ni nuevos endpoints. old()/values existentes preservados; no se certifica roundtrip de errores por POST.

## Edit

Mismo patrón y campo/relaciones; values del worker y emergency_contacts intactos. find interno no es bypass: middleware rechaza ID ajeno antes. worker.edit render real de IDs4(C1)/5(C2). No fixture positivo Gamarra; no existe worker propio y sus IDs ajenos devuelven404. Actualización no ejecutada.

## Plugins / Accessibility

Select2, Inputmask fechas dd/mm/yyyy, Bootstrap Switch e iCheck y card collapse preservados. En HEAD las fechas usan Inputmask, no datepicker popup; no se introdujo otro plugin. Select2 abre/cierra en QA; inputmask está presente. Labels e IDs conservados, textarea dirección/email ahora tienen ID correspondiente, campos repetidos de contactos reciben aria-label. Tab order sigue DOM, details/summary nativo accesible. No modales externos al alcance abiertos.

## IndexEnable Boundary

worker.indexEnable WAIT FOR DEV. getWorkersEnable devuelve have_contract => rand(0,1), no se corrige ni oculta. No enable/disable/delete/fin de contrato. Su allowlist no cambia.

## Personal Data

HTML/JSON de QA sólo temporales locales; documentación contiene IDs y conteos, no DNI, salarios, teléfonos ni nombres personales. Capturas temporales no se incorporan al repo. No export, archivo remoto ni API externa. READ ONLY MySQL terminado con rollback, session/cache array en proceso.

## Responsive

Matriz320/375/390/576/768/992/1200/1440: index/create/edit para C1/C2, index/create para Gamarra (edit inexistente no fabricado). Chromium headless con HTML del controller/Blade real y JSON getWorkers real; assets del servidor local8080, requests de shell ajenas controladas y no-GET bloqueado. No equivale a sesión HTTP autenticada completa.

64 comprobaciones de viewport: cero overflow global. Forms con secciones desplegadas, selector y campos fechas; tabla con scroll local. Capturas desktop1440/mobile375 revisadas. Búsqueda sin coincidencias muestra mensaje DataTables. Gamarra listado vacío sin error y acción crear según permiso. Cero errores JS y cero POST intentados. MULTIPAGE QA — PENDING DATASET:3/1/0 filas no alcanzan10. No creación para simularlo.

Evidencia temporal: worker-contract.json, worker-qa.json, worker-{company}-{index/create/edit}.html y capturas worker-{view}-{375/1440}.png en TEMP. Se mantienen fuera del repositorio por datos personales.

## Mutation Boundary

MUTATION QA PENDING para store/update/destroy/enable/disable/finish contract. No submit ni cálculos de nómina/export. Sólo render, búsqueda, expansión visual, Select2 y acceso por ID. No nuevos findings backend aparte del indexEnable conocido.

## Backend Sync Notes

[OPEN] indexEnable aleatorio, endpoint getWorkersEnable; requiere dato contractual determinista. Frontend autorizado puede continuar: Sí, otras tres vistas.

[OPEN] Assistance/Gratification/FifthCategory/Alimony/Boleta/PersonalPayment/Licenses/Permits/MedicalRest/Suspensions personales mantienen WAIT FOR DEV; no heredan WorkerAccess por integrar Worker. Sin reauditoría.

## Files / Validation

resources/views/worker/index.blade.php, create.blade.php, edit.blade.php; public/js/worker/index.js (alineación visual únicamente); config/venti-next.php; este documento. CSS0, backend unchanged. Scripts create/edit y partial _systemUser unchanged. Allowlist MATCH YES en las tres rutas.

Validación final: node --check index.js PASS; PHP7.3.33 artisan view:cache PASS; git diff --check PASS. Comparación estática de names/data-url create/edit contra HEAD: PRESERVED. Sin commit.


## Worker index — Visual parity correction (2026-11-18)

Previous classification: **UNJUSTIFIED VISUAL DEVIATION**. Individual colored row
buttons and a separate disclosure for columns were not functional requirements.
Current classification: **VISUAL PARITY** with `material.indexV2`.

- Reused Material V2 `next-row-actions` / trigger / menu / item icon / danger classes.
  One ellipsis trigger; shell `initializeNextRowActions` portals the menu outside
  the table scroll container. No trigger when no permitted action exists.
- Preserved edit, disable, register accounts, create/renew contract, finish contract,
  edit termination and delete termination, with exactly the existing permissions
  and contract-state conditions, URLs, data attributes and handlers. Edit is neutral;
  disable/termination/delete use the approved destructive class.
- Reused `next-column-visibility` header, grid, options and toolbar trigger.
  All 29 original input elements (IDs, checked, readonly, data-key, data-column)
  are unchanged. Native DataTables show/hide remains the same; delegation moved
  to the panel so its click propagation guard does not swallow switch events.
- Existing Bootstrap `pre-scrollable` bounds the long 29-option panel, including
  access to the last option on mobile. No new style or alternate component.
  Grid uses the baseline breakpoint: one column below 390px, two when space permits.
- Toolbar retains Exportar excel and permission-gated Nuevo colaborador (primary).
  Native DataTables search, page size, paging and AJAX remain unchanged.
- Escape from a checkbox closes Columnas and returns focus; portaled row menu
  Escape restores the owning trigger. Outside click closes Columnas.

### Correction QA evidence

Rendered current Blade/controller under the existing read-only QA harness; browser
uses those rendered pages and replayed real JSON. This is visual/DOM QA, not a new
certification of full authenticated HTTP or backend functionality.

320 / 375 / 390 / 576 / 768 / 992 / 1200 / 1440: PASS, document scrollWidth equals
viewport width, both floating menus within horizontal viewport bounds. Toggle first
hidden and last configurable column and restore: PASS. Escape / outside click:
PASS. Portaled row Escape: PASS. Browser page errors: zero. No mutation/export.

96 isolated permission/contract-state render combinations compared before/after:
identical visible action href/data/title attributes. All 29 column inputs compared
verbatim: PASS. JavaScript syntax, Laravel 7 / PHP 7.3 Blade compilation and
`git diff --check`: PASS.

Local evidence: `%TEMP%/worker-parity-qa.json`, `worker-parity-qa.cjs`,
`worker-actions-compare.cjs`, `worker-parity-{375,1440}.png`,
`worker-columns-{320,375,390,576,768,992,1200,1440}.png`.

Correction files: `resources/views/worker/index.blade.php`,
`public/js/worker/index.js`, this document. Earlier create/edit/allowlist integration
changes remain in the working tree; this correction did not modify them.
Backend/routes/permissions/dataset: unchanged. CSS added: 0. No commit.


## Worker Index Enable

2026-11-22 — **FRONTEND INTEGRATED**. Backend unchanged; 0 CSS nuevo. Esta fase no modifica worker.index/create/edit.

### Route / permission / purpose

- GET `/dashboard/habilitar/colaborador`: route name **worker.enable**, controller WorkerController@indexEnable, Blade worker.indexEnable. No existe una route llamada worker.indexEnable; la allowlist usa el nombre real worker.enable, sólo esta vista.
- GET `/dashboard/get/workers/enable/`: getWorkersEnable, endpoint DataTables sin route name. `restore_worker` requerido por middleware del controller; la vista lo requiere en routes/web.php.
- POST `/dashboard/enable/worker/{id}`: enable, restore_worker; preservado NO ejecutado.
- Diferencia frente al directorio worker.index: esta superficie lista `enable=0` para habilitación. No es listado de contratos vigentes ni de workers actualmente habilitados. No se agregaron edición/alta/renovación de contratos, export ni acciones del directorio general.

### Scope

WorkerCompanyContext y WorkerAccess intactos: ownership de filas **tenant + company**. WorkerAccess valida además usuario habilitado, membresías company/branch y contexto activo; branch es requisito de acceso, no filtro de ownership Worker. No selector ni filtro branch nuevo.

### have_contract semantics

**DETERMINISTIC HISTORICAL EXISTENCE**. Backend selectSub consulta existencia de cualquier contracts.worker_id=workers.id; no usa vigencia, fechas ni enable. Frontend sólo interpreta el booleano have_contract, muestra `Con contrato` / `Sin contrato`, sin inferir desde contract_id/código o fechas.
Se reutiliza columna21 Contrato, ahora visible inicialmente para mostrar el estado; conserva código de contrato si backend lo entrega y el guard contract_worker previo para su contenido. No se agrega columna ni switch. El código es información adicional, no autoridad del badge. Aviso explícito indica que existencia histórica no confirma vigencia.

### Dataset / HTTP read QA

Lectura actual MySQL READ ONLY, sin fixtures DB; dos respuestas consecutivas completas comparadas idénticas por contexto:

| Cuenta QA | T/C/B | IDs deshabilitados | have_contract | HTTP vista/datos |
| --- | --- | --- | --- | --- |
| Owner EDESCE | 1/1/1 | [2] | false | 200/200 |
| Owner Gamarra | 3/3/4 | [] | N/A | 200/200 |

HTTP autenticado separado confirma la misma igualdad y carga venti-next.css. No escrituras de negocio. No hay mezcla real con/sin contrato en este dataset; mezcla validada sólo como variación controlada del renderer en memoria del navegador, claramente separada de datos reales. No certifica mutación ni crea contratos.

### Columns / actions / column visibility

29 columnas configurables originales, índices e IDs preservados: Código, DNI, Nombres, Apellidos, Dirección, Teléfono, Email, Cargo, Género, Fecha Nac., Edad, Nivel Estudios, N° Hijos, Fecha Ingreso, Fecha Cese, Salario Diario, Salario Mensual, Pensión Alimentos, ESSALUD, Asignación Familiar, Quinta Categoría, Contrato, Estado Civil, Sistema Pensión, Porcentaje Sistema Pensión, Observación, Área, Profesión, Motivo de Cese. Más columna Acciones no configurable.
Se conserva DataTables, búsqueda y paginación cliente, sin reescribir su contrato. Guards contract_worker de información laboral/salarial preservados. Columnas ocultas predeterminadas intactas salvo21 Contrato, ahora marcada visible coherentemente en switch/DataTables.
Columnas se presenta en toolbar con next-column-visibility-menu/grid/options, idéntico patrón worker.index, dos columnas/una móvil y Escape/click fuera. No disclosure ni switches inventados.
Acción única Habilitar dentro de trigger `…`, reutilizando next-row-actions/menu y portal de shell. Se conservan data-enable, data-nombre, data-worker_id, handler destroyWorker, confirmación, endpoint POST y CSRF. La autorización restore_worker se mantiene en rutas, sin reemplazarla por una inferencia JS sobre roles. No se pulsó Habilitar ni se abrió la confirmación.
Nuevo colaborador conserva URL worker.create y presentación/visibilidad @can(create_worker) del baseline. No se navegó a create/edit durante esta fase. No se añadió Exportar porque esta superficie no lo tenía.

### Empty / error

Datos vacíos: `No hay colaboradores deshabilitados en esta empresa`. Búsqueda sin coincidencias conserva mensaje DataTables diferenciado. Badge false no es un estado de usuario ni un error.
Fallo AJAX muestra alert visible (403 distingue permiso), oculta tabla fallida en lugar de presentar un falso empty state. Simulación403 aislada PASS; no se afirma que sea un nuevo QA de autenticación sin permiso.

### Visual parity / responsive

Reutilización directa de worker.index aprobado: next-page-heading, toolbar/actions, tabla next-data-table, wrapper acolchado next-form-section, scroll local, menús de columnas/acciones, badges y alineación. IDs/fechas/cantidades/estado/acciones centrados; importes/porcentajes derecha. Sin CSS nuevo, sin segundo componente.
Chrome/Chromium headless vía Playwright, perfil propio del proceso, sin controlar escritorio ni ventanas del usuario. Render real Laravel de cada Owner y JSON real reproducido en navegador; requests ajenos a assets/read fixture bloqueados y POST abortados. Prueba visual/DOM separada del smoke HTTP autenticado.

| Viewport | document.scrollWidth | Columnas / acciones dentro de viewport | Toggle / Escape |
| --- | --- | --- | --- |
| 320 | 320 | PASS | PASS |
| 375 | 375 | PASS | PASS |
| 390 | 390 | PASS | PASS |
| 576 | 576 | PASS | PASS |
| 768 | 768 | PASS | PASS |
| 992 | 992 | PASS | PASS |
| 1200 | 1200 | PASS | PASS |
| 1440 | 1440 | PASS | PASS |

Capturas reales320/1440 inspeccionadas. Tabla ancha con scroll local (en móvil desplazar para ver las demás columnas); cero overflow global. Cero pageerrors. Estados PASS: sin contrato real, búsqueda vacía, Gamarra vacío, mezcla controlada, error403 controlado diferenciado. No afirmar mezcla real ni prueba de vigencia.
Evidencia local: `%TEMP%/worker-enable-evidence/` (results.json,320.png,1440.png). Harness y render temporales retirados del workspace tras QA.

### Checks / boundary

Blade compile PHP7.3.33 PASS; node --check indexEnable.js PASS; PHP lint config PASS; git diff --check PASS.
JS modificado sólo en presentación de badges/acciones, accesibilidad de menús/alineación y estado de error de lectura. getWorkersEnable/WorkerAccess/controller/model/routes sin cambios. No alta/edición/baja/asignación de contrato, habilitación de Worker ni otra mutación. MUTATION QA PENDING.

Archivos: resources/views/worker/indexEnable.blade.php; public/js/worker/indexEnable.js; config/venti-next.php (worker.enable); docs/REAL-NEXT-WORKERS.md. Sin commit.
