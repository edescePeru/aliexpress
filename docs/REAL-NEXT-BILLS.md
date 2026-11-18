# REAL NEXT — Bills / Tipos de gasto

2026-11-18 — **BILLS / TIPOS DE GASTO — FRONTEND INTEGRATED WITH TECH DEBT**.

## Domain

BillController / App\Bill representan el catálogo de tipos o conceptos de gasto, no un comprobante financiero. Modelo simple con description fillable. Scope **GLOBAL LEGACY — TECH DEBT**. Backend sin cambios; no se trabaja Rendición de gastos.

## Routes

Todas bajo `/dashboard/`, grupo autenticado existente.

| Action | Route | Name | Permission | Read/Mutation |
|---|---|---|---|---|
| List | GET tipos/gastos | bill.index | list_bill | Read |
| Data | GET all/bills | sin nombre | list_bill | Read |
| Create form | GET crear/tipo/gasto | bill.create | create_bill | Read |
| Store | POST bill/store | bill.store | create_bill | Mutation, NO ejecutada |
| Edit form | GET editar/tipo/gasto/{id} | bill.edit | update_bill | Read |
| Update | POST bill/update | bill.update | update_bill | Mutation, NO ejecutada |
| Delete | POST bill/destroy | bill.destroy | destroy_bill | Mutation, NO ejecutada |

## Permissions

Permisos reales list_bill/create_bill/update_bill/destroy_bill. Botón crear con @can('create_bill'); editar/eliminar mantienen array getPermissionsViaRoles del contrato JS. Modal delete condicionado a destroy_bill. No se cambia autorización backend ni se asume bypass Owner.

Owners EDESCE4 y Gamarra7 tienen los cuatro permisos al consultar el registrar temporal cache-array. Evidencia por contexto/Auth en harness, no certificación HTTP completa de roles sin permiso. Gates de rutas auditados estáticamente.

## Global Legacy Scope

Bill no tiene TenantScope, company, branch ni SoftDeletes. getAllBills consulta id/description globalmente. EDESCE y Gamarra comparten el catálogo por contrato, **no isolation PASS**. No selector/filtro contextual añadido. Deuda conocida preservada, sin duplicar finding.

## Dataset

Revalidado en MySQL mediante START TRANSACTION READ ONLY y ROLLBACK: bills=0 global; respuesta data=[] / recordsTotal=0 / recordsFiltered=0 en lectura. EDESCE T1/C1/B1 y Gamarra T3/C3/B4: IDs[]. Sin inserts, fixtures ni modificaciones reales.

Edit positivo real: PENDING DATASET. Para QA visual se renderizó el Blade con un objeto Bill sólo en memoria, description “EVIDENCIA VISUAL NO PERSISTIDA”; no se invocó save ni se hizo pasar por registro real. No se simularon filas en el listado vacío.

## Fields

ID (técnico, sólo tabla y hidden bill_id en edit), description (único campo editable). Esquema: mediumText nullable y timestamps; sin código de negocio, estado ni valores adicionales. Tabla ID / Descripción / Acciones. No maxlength/required en Blade original ni límite funcional explícito añadido.

## Validation

Store/update reciben Illuminate\Http\Request, **no Store/Update FormRequest ni validate()**. No reglas requeridas/únicas/longitud definidas en esos métodos. No se inventan validaciones JS. La función mayus existente permanece.

Se preservan formCreate, btn-submit type=button, description name/id, onkeyup, enctype, CSRF, hidden bill_id y data-url. No action/method HTML explícitos en original: el contrato sigue siendo FormData + AJAX POST en create.js/edit.js, intactos. Cancelar conserva reset, no navegación. Se añade old() y presentación @error sin nuevas reglas; errores AJAX/toastr existentes preservados. Guardados no probados.

## Invalid ID Debt

**TECH DEBT — INVALID ID HANDLING**. edit usa Bill::find, no findOrFail. ID999999 en BD actual produce bill=null; render lanza ViewException “Trying to get property 'id' of non-object” por hidden bill_id. No es404 controlado. Se confirmó con render real en lectura, sin HTTP a ruta mutadora. No se agrega fallback/frontend404 ni se modifica controller. Update/destroy también usan find, fuera de QA de mutaciones.

## Delete Semantics

Hard delete: Bill no usa SoftDeletes ni estado disabled. Antes de borrar, controller consulta Expense::where(bill_id) y devuelve422 si hay referencias (“No se puede eliminar porque hay gastos con este tipo”). Se conserva semántica, permiso, formDelete/data-url, bill_id, code, modalDelete, btn-submit y AJAX. Sólo se abrió/cerró modal vacío en QA; nunca confirmar. La comprobación de dependencias no fue probada ejecutando destroy.

## Expense Boundary

Consumers constatados por búsqueda puntual: Expense::bill belongsTo Bill y ExpenseController obtiene Bill::all para formularios, almacena bill_id. No auditoría de sus flujos, no edición de archivos ni apertura de Expense create/list/edit/reports. **Expense — BACKEND SYNC REQUIRED** permanece intacto.

## Visual Integration

Reutiliza Configuration CRUD de Calidades: heading, toolbar context-only, Surface Spacing Standard, next-form-section, actions flex-wrap, DataTables dom compartido, tabla compacta, dropdown next-row-actions y modal next-aux-modal. Crear conserva botón principal; Edit conserva ID y descripción. Breadcrumb Edit corregido.

JS index sólo adapta presentación: columnas y alineación ID/acciones centro; descripción izquierda; dropdown por permisos; escape de datos y texto del modal (no HTML de descripción). Mutaciones existentes sin cambios. Empty state claro, búsqueda, resumen0 y paginación inactiva. No CSS añadido: se retiró override Select2 inline sin consumer en estas vistas.

Allowlist exacta bill.index, bill.create, bill.edit. **ALLOWLIST MATCH: YES**, tres HTML cargan venti.min.css y venti-next.css. Sin endpoints JSON ni mutaciones añadidos.

## Responsive

Chrome headless con Blade real renderizado y assets locales, shell externo/notifications interceptados. Index vacío/create/edit en memoria en320/375/390/576/768/992/1200/1440: scrollWidth=viewport, cero errores JS. Capturas1440/375 revisadas. Modal delete abierto/cerrado a320 sin overflow, sin confirmar.

Búsqueda del listado vacío PASS; botón crear visible con permiso; uppercase PRUEBA y reset en create/edit comprobados sin guardar. CSRF/data-url presentes. Requests mutadores durante QA:0. No es sesión HTTP end-to-end con un Bill válido persistido. QA positivo real de edición/multipágina pendiente de dataset. No suite de tests nueva para este cambio de presentación.

## Mutation Boundary

**MUTATION QA PENDING**: no store/update/destroy. No creación de registros, cambios de catálogo ni Expense. Botones y contratos disponibles para uso autorizado posterior, sin certificar mutaciones.

## Backend Sync Notes

[OPEN] GLOBAL LEGACY: catálogo compartido sin tenant/company/branch. Contrato aceptado para integración con deuda; no se contextualiza desde UI. Frontend puede continuar: Sí.

[OPEN] INVALID ID HANDLING: BillController@edit/find y Blade esperan registro. DEV podrá definir404 con findOrFail; conservado sin parche backend. Frontend puede continuar: Sí.

[OPEN] Validación store/update: Request genérico, reglas ausentes. Se documenta contrato, no se reemplaza por restricciones JS. No cierre de mutaciones ni finding duplicado.

## Files / Validation

- resources/views/bill/index.blade.php
- resources/views/bill/create.blade.php
- resources/views/bill/edit.blade.php
- public/js/bill/index.js
- config/venti-next.php (3 vistas)
- docs/REAL-NEXT-BILLS.md

PHP7.3 Blade compile PASS; node --check index.js PASS; git diff --check PASS. Backend/CSS/Expense sin cambios. No commit.
