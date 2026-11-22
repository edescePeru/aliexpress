# REAL NEXT — RRHH BASIC MAINTAINERS — BATCH FRONTEND INTEGRATION

Fecha: 2026-11-14. Backend y base de datos sin modificaciones. No commit.

**7 catálogos / 24 vistas: FRONTEND INTEGRATED WITH TECH DEBT.** Todos los maestros integrados son `GLOBAL LEGACY — TECH DEBT`. No se afirma tenant/company isolation PASS. Mutaciones: `MUTATION QA PENDING`.

## Domain Matrix

| Domain | Controller / Model | Views | Permission | Scope | Dataset | Decision |
|---|---|---|---|---|---|---|
| Cargos — maestro | WorkFunctionController / WorkFunction | `workFunction`: index/create/edit/indexDeleted | `function_worker` | Global legacy | 0 | INTEGRATE WITH TECH DEBT |
| Estado civil — maestro | CivilStatusController / CivilStatus | `civilStatus`: index/create/edit/indexDeleted | `statusCivil_worker` | Global legacy | 0 | INTEGRATE WITH TECH DEBT |
| Parentescos — maestro | RelationshipController / Relationship | `relationship`: index/create/edit | `relationship_worker` | Global legacy | 0 | INTEGRATE WITH TECH DEBT |
| Áreas laborales — maestro | AreaWorkerController / AreaWorker | `areaWorker`: index/create/edit | `list_areaWorker` + create/update/destroy_areaWorker | Global legacy | 1 | INTEGRATE WITH TECH DEBT |
| Feriados — maestro | HolidayController / Holiday | `holiday`: index/create/edit | `holiday_worker` | Global legacy | 0 | INTEGRATE WITH TECH DEBT |
| Sistemas de pensión — maestro | PensionSystemController / PensionSystem | `pensionSystem`: index/create/edit/indexDeleted | `systemPension_worker` | Global legacy | 0 | INTEGRATE WITH TECH DEBT |
| Motivos de suspensión — maestro | ReasonSuspensionController / ReasonSuspension | `reasonSuspension`: index/create/edit | `enable_suspension` | Global legacy | 0 | INTEGRATE WITH TECH DEBT |
| Licencias — registro por trabajador | LicenseController / License | `license`: index/create/edit | list/create/edit/delete_license (ver rutas abajo) | Registro global; Worker tenant-only | 0 | BACKEND SYNC REQUIRED; asistencia fuera de alcance |
| Licencias sin goce — registro por trabajador | UnpaidLicenseController / UnpaidLicense | `unpaidLicense`: index/create/edit | list/create/edit/delete_unpaidLicense (ver rutas abajo) | Registro global; Worker tenant-only | 0 | BACKEND SYNC REQUIRED; asistencia fuera de alcance |
| Permisos — registro por trabajador | PermitController / Permit | `permit`: index/create/edit | list/create/edit/delete_permit (ver rutas abajo) | Registro global; Worker tenant-only | 0 | BACKEND SYNC REQUIRED; asistencia fuera de alcance |
| Permisos por horas — registro por trabajador | PermitHourController / PermitHour | `permithour`: index/create/edit | list/create/edit/destroy_permitHour (ver rutas abajo) | Registro global; Worker tenant-only | 0 | BACKEND SYNC REQUIRED; asistencia fuera de alcance |
| Descansos médicos — registro por trabajador | MedicalRestController / MedicalRest | `medicalRest`: index/create/edit | list/create/edit/delete_medicalRest (ver rutas abajo) | Registro global; Worker tenant-only | 0 | BACKEND SYNC REQUIRED; asistencia fuera de alcance |
| Suspensiones — registro por trabajador | SuspensionController / Suspension | `suspension`: index/create/edit | list/create/edit/delete_suspension (ver rutas abajo) | Registro global; Worker tenant-only | 0 | BACKEND SYNC REQUIRED; asistencia fuera de alcance |
| Tipos de licencia / permiso independientes | No encontrados como CRUD separado en esta familia | — | — | — | N/A | NOT FOUND |
| Jornadas / Regímenes | WorkingDayController / RegimeController / RegimeDetailController | workingDay/regime | Contrato de asistencia | Legacy | No relevado: fuera de alcance | COMPLEX / OUT OF SCOPE |
| Contratos individuales | ContractController / FinishContractController / WorkerController | contract + adjuntos | contract_worker y rutas reales específicas | Datos personales | No relevado: fuera de alcance | COMPLEX / OUT OF SCOPE |
| Planillas, boletas, beneficios, vacaciones, créditos, pagos, gastos y parámetros de cálculo | Familias especializadas existentes | Sin cambios | Sin cambios | No certificado aquí | No relevado | COMPLEX / OUT OF SCOPE |

Sistemas de pensión se integra únicamente como catálogo de descripción/porcentaje existente: este controller no calcula pagos. No se edita ninguna tasa ni se auditan fórmulas/boletas/planillas. Motivos de suspensión tampoco aplica suspensiones ni calcula días: conserva los campos `reason`/`days`. Feriados conserva su acción de generación, que **no se ejecutó**.

## Scope

Los siete modelos integrados no usan TenantScope/CompanyScope ni columnas tenant/company/branch. Sus index/JSON/edit consultan el catálogo global. El middleware común `tenant.context` no contextualiza automáticamente un modelo global. No se agregan selectores ni filtros de contexto, tampoco aislamiento en JS.

QA READ ONLY de Eloquent bajo T1/C1, T1/C2 y T3/C3: mismos IDs; AreaWorker `[1]` y los otros maestros vacíos. Sesiones reales Owner EDESCE (EDESCE / Local Principal) y Owner Gamarra (Gamarra Moda / Local Principal): AreaWorker ID 1 visible en listado y Edit; Cargos vacío en ambos. Esto confirma globalidad, **no isolation PASS**. Branch N/A para estos catálogos.

`find(999999)` devuelve null en los siete maestros. Los Edit usan `find`, no `findOrFail`: no hay garantía de HTTP 404 controlado; no se cambió el backend. Edit real verificado sólo para AreaWorker ID 1. Los otros seis no tienen registros para certificar binding con un ID existente.

Los registros por trabajador se bloquean temprano: License/UnpaidLicense/Permit/PermitHour/MedicalRest/Suspension no tienen scope ni columnas de contexto; sus getAll/binding son globales. `with('worker')` no filtra el registro padre. El lookup `Worker::where('id','<>',1)->where('enable',1)` hereda TenantScope de Worker, pero no exige company/membresía activa equivalente a WorkerCompanyContext. Dataset vacío no constituye evidencia de aislamiento. No se abrieron esas superficies personales ni se probaron cálculos.

## Dataset

Lectura MySQL con `START TRANSACTION READ ONLY` y `ROLLBACK`, sin datos personales, fixtures ni inserts. IDs completos del dataset actual (incluye filas físicas y contraste con IDs del modelo):

| Domain / table | Count | IDs | Context |
|---|---:|---|---|
| WorkFunction / `work_functions` | 0 | `[]` | Global; sin tenant/company/branch en tabla |
| CivilStatus / `civil_statuses` | 0 | `[]` | Global; sin tenant/company/branch en tabla |
| Relationship / `relationships` | 0 | `[]` | Global; sin tenant/company/branch en tabla |
| AreaWorker / `area_workers` | 1 | `['1']` | Global; sin tenant/company/branch en tabla |
| Holiday / `holidays` | 0 | `[]` | Global; sin tenant/company/branch en tabla |
| PensionSystem / `pension_systems` | 0 | `[]` | Global; sin tenant/company/branch en tabla |
| ReasonSuspension / `reason_suspensions` | 0 | `[]` | Global; sin tenant/company/branch en tabla |
| License / `licenses` | 0 | `[]` | Global; sin tenant/company/branch en tabla |
| UnpaidLicense / `unpaid_licenses` | 0 | `[]` | Global; sin tenant/company/branch en tabla |
| Permit / `permits` | 0 | `[]` | Global; sin tenant/company/branch en tabla |
| PermitHour / `permits_hours` | 0 | `[]` | Global; sin tenant/company/branch en tabla |
| MedicalRest / `medical_rests` | 0 | `[]` | Global; sin tenant/company/branch en tabla |
| Suspension / `suspensions` | 0 | `[]` | Global; sin tenant/company/branch en tabla |

No se reutilizaron conteos históricos. Sin fixtures en DB. Para los Edit vacíos se renderizó el Blade real con objetos Eloquent **no guardados** y layout de QA temporal, acciones deshabilitadas, fuera de public/repo. Esto valida campos/renderizado visual, no binding, permisos ni mutaciones reales.

## Routes / Permissions

Todas las rutas siguientes mantienen `web`, `auth`, `check.user.enabled`, `password.changed`, `tenant.context` y su middleware de permiso existente. Rutas y backend sin cambios.

| Domain | List | Create | Edit | Delete | Restore |
|---|---|---|---|---|---|
| Cargos | `function_worker` | `function_worker` | `function_worker` | `function_worker` | `function_worker` |
| Estado civil | `statusCivil_worker` | `statusCivil_worker` | `statusCivil_worker` | `statusCivil_worker` | `statusCivil_worker` |
| Parentescos | `relationship_worker` | `relationship_worker` | `relationship_worker` | `relationship_worker` | `relationship_worker (ruta rota)` |
| Áreas laborales | `list_areaWorker` | `create_areaWorker` | `update_areaWorker` | `destroy_areaWorker` | `—` |
| Feriados | `holiday_worker` | `holiday_worker` | `holiday_worker` | `holiday_worker` | `—` |
| Sistemas de pensión | `systemPension_worker` | `systemPension_worker` | `systemPension_worker` | `systemPension_worker` | `systemPension_worker` |
| Motivos de suspensión | `enable_suspension` | `enable_suspension` | `enable_suspension` | `enable_suspension` | `—` |
| Licencias | list_license | create_license | edit_license | delete_license | — |
| Sin goce | list_unpaidLicense | create_unpaidLicense | edit_unpaidLicense | delete_unpaidLicense | — |
| Permisos | list_permit | create_permit | edit_permit | delete_permit | — |
| Por horas | list_permitHour | create_permitHour | edit_permitHour | destroy_permitHour | — |
| Descansos médicos | list_medicalRest | create_medicalRest | edit_medicalRest | delete_medicalRest | — |
| Suspensiones | list_suspension | create_suspension | edit_suspension | delete_suspension | — |

### Vistas integradas y allowlist explícita

| Route name | GET URI | View |
|---|---|---|
| `workFunctions.index` | `/dashboard/cargos` | `workFunction.index` |
| `workFunctions.create` | `/dashboard/crear/cargo` | `workFunction.create` |
| `workFunctions.edit` | `/dashboard/editar/cargo/{id}` | `workFunction.edit` |
| `workFunctions.deleted` | `/dashboard/cargos/eliminados` | `workFunction.indexDeleted` |
| `civilStatuses.index` | `/dashboard/estado/civil` | `civilStatus.index` |
| `civilStatuses.create` | `/dashboard/crear/estado/civil` | `civilStatus.create` |
| `civilStatuses.edit` | `/dashboard/editar/estado/civil/{id}` | `civilStatus.edit` |
| `civilStatuses.deleted` | `/dashboard/estado/civil/eliminados` | `civilStatus.indexDeleted` |
| `relationship.index` | `/dashboard/parentescos` | `relationship.index` |
| `relationship.create` | `/dashboard/crear/parentesco` | `relationship.create` |
| `relationship.edit` | `/dashboard/editar/parentesco/{id}` | `relationship.edit` |
| `areaWorker.index` | `/dashboard/areas/empresa` | `areaWorker.index` |
| `areaWorker.create` | `/dashboard/crear/area/empresa` | `areaWorker.create` |
| `areaWorker.edit` | `/dashboard/editar/area/empresa/{id}` | `areaWorker.edit` |
| `holiday.index` | `/dashboard/feriados` | `holiday.index` |
| `holiday.create` | `/dashboard/crear/feriado` | `holiday.create` |
| `holiday.edit` | `/dashboard/editar/feriado/{id}` | `holiday.edit` |
| `pensionSystems.index` | `/dashboard/sistemas/pension` | `pensionSystem.index` |
| `pensionSystems.create` | `/dashboard/crear/sistema/pension` | `pensionSystem.create` |
| `pensionSystems.edit` | `/dashboard/editar/sistema/pension/{id}` | `pensionSystem.edit` |
| `pensionSystems.deleted` | `/dashboard/sistemas/pension/eliminados` | `pensionSystem.indexDeleted` |
| `reasonSuspension.index` | `/dashboard/razones/suspension` | `reasonSuspension.index` |
| `reasonSuspension.create` | `/dashboard/crear/razon/suspension` | `reasonSuspension.create` |
| `reasonSuspension.edit` | `/dashboard/editar/razon/suspension/{id}` | `reasonSuspension.edit` |

`config/venti-next.php`: exactamente esas 24 rutas de vista; sin wildcards ni JSON. `ALLOWLIST MATCH: YES` para las vistas navegadas; CSS `venti.min.css` y `venti-next.css` cargados. Edit sin dataset: route/allowlist inspeccionados + render aislado; no se afirma navegación con datos inexistentes.

POST preservados: workFunctions/civilStatuses/pensionSystems `store/update/destroy/restore`; relationship/areaWorker/holiday/reasonSuspension `store/update/destroy`. `holiday.generate` sigue POST `/dashboard/generate/holidays`. El body continúa FormData del formulario original. Todos los create/edit usan `formCreate`, `btn-submit`, `data-url`, CSRF y hidden ID existentes. Los modales mantienen `modalDelete`, `formDelete`, `data-delete` y `btn-submit`; las pantallas de habilitación usan los mismos hooks legacy.

## Worker/User Dependencies

No hay lookup de Worker/User/Company/Branch en los formularios de los siete maestros. `Auth::user()->getPermissionsViaRoles()` obtiene permisos de UI, no es un selector de personas. No se introduce `User::all()` ni se sustituye Worker por User. `USER-01` no es el root cause de estos registros personales: el problema es su propio binding global y la ausencia de frontera company en el lookup Worker.

Consumers intactos: Worker y WorkerController usan work_function/civil_status/pension_system/area_worker; EmergencyContact usa relationship; AssistanceController y BoletaController consumen Holiday; BoletaController consume PensionSystem; SuspensionController consume ReasonSuspension. Ningún consumer fue modificado.

AUTH-01 sigue abierto: arrays de permisos vía roles pueden diferir de permisos efectivos/directos. Los handlers/listados mantienen las condiciones existentes. AreaWorker tiene middleware por acción pero su renderer legacy no oculta Edit/Delete según permiso individual; no se certifica QA de roles limitados. El guard del servidor permanece intacto. Owner QA no implica bypass.

## Integrated Views

24 Blades: 7 index, 7 create, 7 edit, 3 indexDeleted. Patrón `Venti Next Configuration CRUD`: encabezado, toolbar compacta, acciones, sección de formulario con gutter, DataTables con búsqueda/length/summary/paginación y menú de acciones, modal compartido. Reutiliza Calidades/Contactos y la composición congelada de material.create; **0 CSS nuevo**.

Se conservan columnas reales y alineación (ID/año/fecha/días/acciones centrados, porcentaje a derecha, descripción a izquierda). Las tablas cortas reutilizan `.table.table-sm` con estilo global Venti; no usan el min-width de 63rem de `.next-data-table` destinado a tablas operativas anchas. La tabla tiene contenedor local si el contenido lo exige.

Formularios conservan IDs/names/hidden/CSRF/plugin attrs y valores; se agrega `old()` sin cambiar defaults. El formulario Feriados distribuye descripción/fecha/año con grid Bootstrap para que los inputs sigan legibles en tablet. No se cambia fecha d/m/Y ni año. No se añaden reglas de negocio JS. Se conservan errores AJAX/Toastr. Escapado de texto/atributos al construir filas y `.text()` para las etiquetas del modal, sin cambiar payloads.

## Blocked Views

- Licencias, licencias sin goce, permisos, permisos por horas, descansos médicos y suspensiones: todas sus vistas fuera de allowlist de esta fase; `BACKEND SYNC REQUIRED` (HR-01). También modifican asistencia, fuera de alcance.
- Parentescos eliminados/restauración: `relationship.deleted` devuelve una vista que no existe; el JSON apunta a `getCivilStatusesDeleted` inexistente y `restore` tampoco existe en RelationshipController. No se integra ni se añade enlace/allowlist a ese flujo (HR-02). Su CRUD principal sí se integra.
- Tipos de licencia/permiso separados: `NOT FOUND`. No se inventan catálogos.
- Inventario estructural y Metas: sin cambios ni nuevo QA.

## Soft Deletes

| Domain | Mecanismo efectivo | Eliminados / Restore |
|---|---|---|
| Cargos, Estado civil, Sistemas de pensión | Modelo usa SoftDeletes, pero destroy del controller cambia `enable=false`; restore `enable=true` | indexDeleted consulta enable=false; no withTrashed; integración visual completa |
| Parentescos, Áreas laborales, Feriados | Eliminación física (sin SoftDeletes) | No restore funcional; rutas huérfanas Parentescos bloqueadas |
| Motivos de suspensión | SoftDeletes real vía delete | Sin vista/endpoint restore |
| Registros personales bloqueados | SoftDeletes en modelos | No restore integrado; sin pruebas de mutaciones |

No se ejecutó create/update/delete/restore/generate/aprobación/pago. **MUTATION QA PENDING** para toda la fase. No se investigaron dependencias históricas de borrado.

## Responsive

QA Owner EDESCE real: los 10 listados/inhabilitados cargan el JSON hasta estado vacío o fila real. Siete Create navegan; AreaWorker Edit ID 1 carga VENTAS. Búsqueda sin resultados, dropdown y modal de eliminación abierto/cerrado con Cancelar, sin ejecutar acción. Feriado inputmask dd/mm/yyyy comprobado sin guardar. Consola de las rutas navegadas sin errores capturados.

| Patrón | 320 | 375 | 390 | 576 | 768 | 992 | 1200 | 1440 |
|---|---|---|---|---|---|---|---|---|
| Listado Cargos: vacío/toolbar | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| Create Cargos: campo simple | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| Create Feriados: fecha/año | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| Create Motivos: textarea + número | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| Edit Feriados: modelo en memoria, sin shell real | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |

Smoke restante: listados/create a 320; siete Edit renderizados aisladamente con valores largos, más Edit real AreaWorker a 320. Modal real AreaWorker a 320 usable y contenido dentro del viewport. Se verificó `documentElement.scrollWidth <= innerWidth`. **GLOBAL OVERFLOW: NONE** en las muestras. Sin nueva matriz idéntica por cada catálogo. Habilitación con fila real y paginación multipágina: PENDING DATASET; todos los listados excepto AreaWorker están vacíos.

## Backend Sync Notes

[OPEN] **HR-01 — Registros personales globales y lookup Worker sin company.**
File/endpoint: License/UnpaidLicense/Permit/PermitHour/MedicalRest/SuspensionController; modelos homónimos; getAll/edit/create.
Impact: list/binding pueden mezclar registros personales sin tenant/company; relación Worker tenant-scoped no restringe el padre. Sin evidencia de exposición real con dataset vacío; evidencia estática positiva del contrato inseguro.
DEV: definir ownership contextual, aplicar list/binding/validation y lookup Worker contextual; no resolver en JS. Frontend puede continuar: No en esas vistas, Sí en maestros. Root distinto de USER-01/AUTH-01/DATA-01.

[OPEN] **HR-02 — Rutas huérfanas de eliminados/restauración de Parentescos.**
File: routes/web.php y RelationshipController; view relationship.indexDeleted ausente.
DEV: retirar rutas legacy o implementar lifecycle acordado. Frontend puede continuar: Sí, index/create/edit. No crear restore ficticio ni cambiar physical delete.

[OPEN] **GLOBAL LEGACY — TECH DEBT.**
Los siete maestros son compartidos y sus permisos pueden autorizar cambios globales desde un tenant. Integración permitida explícitamente por el usuario para maestros sin datos personales. DEV debe decidir si se mantienen globales o se contextualizan; no se añade un scope frontend. No se duplica como un bug nuevo de datos.

[OPEN] **Hardening del contrato legacy (sin certificado de mutación).**
Edit con ID inexistente devuelve modelo null; falta 404 controlado. Excepto AreaWorker, los controllers integrados usan Request sin validación formal de campos; SQL/errores transaccionales no sustituyen validación. AreaWorker tiene FormRequests name required/string/max255 y store unique; update no replica unique. No se ejecutaron requests de escritura para probar estas condiciones. Revisar antes de cerrar mutaciones. AUTH-01 y QA-02 mantienen su estado; DATA-01 no se usa para justificar registros personales inseguros.

## Files / Verification

- 24 Blades en workFunction, civilStatus, relationship, areaWorker, holiday, pensionSystem, reasonSuspension.
- 10 JS de listados: index en los siete dominios; indexDeleted en workFunction/civilStatus/pensionSystem. Create/Edit JS sin cambios.
- config/venti-next.php: 24 entradas explícitas.
- Este único documento de la fase. El documento de Inventario estructural ya estaba sin seguimiento al iniciar y no se modificó.
- Verificación automática frente a HEAD: mismos IDs, names, data-url, CSRF y atributos Inputmask en los 24 Blades.
- PHP 7.3 `artisan view:cache`: PASS. Sintaxis de los 10 JS modificados: PASS. `git diff --check`: PASS.
- Backend/controllers/models/requests/routes/migrations: **UNCHANGED**. CSS: **0**. Sin commit.

## Revisit Triggers

Fix contextual DEV en registros personales; decisión de scope global; restauración Parentescos implementada/retirada; dataset real para seis Edit, inhabilitados/paginación; roles limitados/permisos directos; autorización explícita futura para QA de mutaciones. Hasta entonces, no certificar payroll, cálculo de días, beneficios, asistencia ni aislamiento inexistente.
