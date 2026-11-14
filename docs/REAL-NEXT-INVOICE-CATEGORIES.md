# REAL NEXT — Invoice Categories

Fecha: 2026-11-12. Alcance exclusivo CategoryInvoice; Company Bank Accounts cerrado y sin cambios. Backend fuente funcional, Configuration CRUD y Surface Spacing Standard fuente visual. Sin commit.

## Post-Migration Audit

PHP 7.3 artisan migrate:status confirma Yes, batch166 para:
- 2026_11_09_000001_add_context_to_daily_profit_summaries
- 2026_11_09_000002_add_public_catalog_slugs
- 2026_11_10_000001_scope_invoice_categories_to_company
- 2026_11_10_000002_add_finance_tax_snapshot_to_detail_entries

Auditados controller, modelo, Store/Update/Delete requests, migración, tres vistas y JS, Entry::category_invoice y consumidores Invoice. No se ejecutan migraciones, backfill ni correcciones.

## Backfill Results

Consulta SQL de solo lectura incluyendo registros soft-deleted:

| TOTAL | SCOPED | UNSCOPED | AMBIGUOUS |
| --- | --- | --- | --- |
| 3 | 3 | 0 | 0 |

| Categoría ID | Tenant | Company | Eliminada | Referencias Entry |
| --- | --- | --- | --- | --- |
| 1 | 1 | 1 | No | 3 |
| 2 | 1 | 1 | No | 0 |
| 3 | 1 | 1 | No | 0 |

Tres referencias válidas, cero referencias con tenant/company discrepantes y cero referencias huérfanas. AMBIGUOUS aquí significa categoría actualmente sin contexto con referencias a múltiples contextos. No se dispone de snapshot pre-migración para inferir cuántas copias creó históricamente.

Migración agrega T/C nullable e índice compuesto. Asigna según referencias Entry cuyo tenant/company coincide con companies; si una categoría tiene múltiples companies, conserva primer ID, copia categoría y reasigna referencias por contexto. Categorías sin referencias se asignan solo si hay un único contexto válido global; en otros casos pueden quedar sin contexto. No se relaja frontend para mostrarlas. Estado actual: Migration / Backfill — QA PASSED, sin deuda unscoped observada.

## Routes

Prefijo /dashboard; middleware común web/auth/check.user.enabled/password.changed/tenant.context. No tenant.owner específico en estas rutas.

| Nombre / acción | Método y ruta | Permission |
| --- | --- | --- |
| categoryInvoice.index | GET categorias/facturas | list_categoryInvoice |
| getCategories (sin nombre) | GET all/categories/invoices | list_categoryInvoice |
| categoryInvoice.create | GET crear/categoria/facturas | create_categoryInvoice |
| categoryInvoice.store | POST category/invoice/store | create_categoryInvoice |
| categoryInvoice.edit | GET editar/categoria/factura/{id} | update_categoryInvoice |
| categoryInvoice.update | POST category/invoice/update | update_categoryInvoice |
| categoryInvoice.destroy | POST category/invoice/destroy | destroy_categoryInvoice |

## Permissions

Names actuales confirmados. @can de crear/modal y permissions getPermissionsViaRoles para acciones conservados. No cambio de roles ni permisos. Allowlist visual solo index/create/edit; endpoint AJAX no necesita tema.

## Tenant / Company Scope

CategoryInvoice no tiene global TenantScope; requiere scope explícito forCurrentCompany() que usa currentContext(TenantContext::tenantId/companyId). Index, getCategories, edit, update y destroy lo aplican. Update/destroy usan lockForUpdate y findOrFail; sus requests currentCategoryRule validan ID del T/C y deleted_at null. Store fillable solo name/description y forceFill de contexto servidor; no acepta T/C del navegador. Branch no pertenece al contrato del catálogo. Modelos consultados fuera de este scope no deben asumirse seguros.

## Fields

name requerido string max255; description nullable string max255. Edit/delete agregan category_id requerido integer y currentCategoryRule. No estado, moneda, default ni selector company/branch. Nombres y IDs de formularios (formCreate/formEdit/formDelete), data-url, CSRF, hidden category_id, uppercase mayus y FormData POST preservados. Inputs antes sin ID reciben IDs accesibles category-name/category-description; labels corregidos. Se añade old()/@error a create/edit, sin cambiar validación backend. Select2 se cargaba como dependencia legacy pero no existe campo Select2 en estos formularios.

## References

CategoryInvoice::entries hasMany Entry. InvoiceController obtiene opciones forCurrentCompany y resuelve categoría bajo lock scoped. StoreInvoiceRequest/UpdateInvoiceRequest usan currentCategoryRule. No se cambia consumidor. Referencias actuales: tres Entries a categoría1, todas T1/C1.

## Soft Delete / Historical Semantics

Modelo usa SoftDeletes. Listado/opciones/edición excluyen eliminadas. Entry::category_invoice belongsTo con withTrashed conserva etiqueta histórica. No se sustituye categoría histórica. No hay categorías eliminadas en dataset para prueba visual; semántica confirmada por código.

REFERENCED CATEGORY DELETE — BACKEND PROTECTED. Destroy valida contexto, bloquea fila y consulta DB::table(entries)->where(category_invoice_id)->exists sin excluir soft-deleted: cualquier referencia bloquea con422 y mensaje «Esta categoría ya tiene facturas asociadas.». No se ejecuta delete. Confirmación modal abierta y cerrada sin enviar.

## Visual Mapping

REUSE DIRECTLY / WITH CONTRACT ADAPTATION: next-page-heading, next-page-toolbar/actions, next-form-section, next-list-toolbar, next-data-table, next-row-actions y next-aux-modal. DataTables preserva GET/data, búsqueda, ordenación, tamaño de página y paginación. Columnas nombre/descripción izquierda y acciones centro; no status inventado. Dropdown mantiene data-delete/data-name y href edit. Texto y atributos construidos de forma segura con render.text/jQuery; nombre de modal usa text() en lugar de html().

Create/edit comparten grid y gutters; Cancelar sigue reset (contrato previo). Nuevos botones y secciones usan patrones existentes. 0 CSS nuevo; estilos inline legacy Select2 retirados. ALLOWLIST MATCH: YES; ambas hojas venti.min.css y venti-next.css cargadas.

Adaptación JS necesaria: callbacks aceptan event explícito; errores backend con message sin errors ahora se muestran en Toastr (antes se omitían), manteniendo validaciones por campo. No se duplica regla de referencias en frontend. Prueba Node VM con AJAX sustituido por stub local: message422 y errors422 presentados en index/create/edit, sin requests HTTP.

## Responsive QA

Index/create/edit medidos a320/375/390/576/768/992/1200/1440: cero overflow global. Tabla admite scroll local. Create desktop inspeccionado visualmente; listado con búsqueda, empty state y dropdown; modal abrir/cerrar sin submit. Sin errores JS observados. Datos de tres categorías permiten una sola página real, no se inventa dataset para QA multipágina.

## Cross-context QA

Navegador existente Owner EDESCE T1/C1/B1: GET real de listado/AJAX y formularios create/edit; tres categorías, búsqueda sin coincidencia => vacío, búsqueda SERVICIOS BASICOS => categoría utilizada. No guardar.

Prueba de integración adicional PHP7.3 dentro de transacción SQL READ ONLY/rollback: cuentas autorizadas Auth::setUser y sesiones efímeras, InitializeTenantContext, permiso list comprobado, controller index/getCategories/edit reales. Esto no es login HTTP ni QA visual de Gamarra.

| Cuenta / rol | T | C | B | Index / AJAX | Edit IDs1/2/3 | ID99999999 |
| --- | --- | --- | --- | --- | --- | --- |
| Owner EDESCE / owner | 1 | 1 | 1 | 3 / 3 | permitido | rechazo findOrFail |
| Owner EDESCE / owner | 1 | 2 | 2 | 0 / 0 | rechazo findOrFail | rechazo findOrFail |
| Owner EDESCE / owner | 1 | 5 | 6 | 0 / 0 | rechazo findOrFail | rechazo findOrFail |
| Owner Gamarra / owner | 3 | 3 | 4 | 0 / 0 | rechazo findOrFail | rechazo findOrFail |

ModelNotFoundException corresponde a404 HTTP; prueba de controller no se presenta como captura HTTP. Tenant / Company Isolation — QA PASSED en este alcance. Vacío Gamarra válido. Sin contrato branch.

## Mutation Exclusions

MUTATION QA PENDING. No create/update/delete ejecutados, no cambios de categoría ni referencias. Requests y protección auditados estáticamente; callback UI de errores probado con respuesta controlada. No se interpreta esta evidencia como certificación runtime de mutaciones.

## Finance Invoice Dependency

Tax snapshot agrega finance_tax_kind nullable varchar12 y finance_tax_amount nullable decimal12,2 en detail_entries. No altera payload/campos CategoryInvoice ni su relación Entry. No incompatibilidad visible en catálogo. Invoice list/create/edit, pagos, anulaciones, tax snapshot UI y órdenes financieras siguen fuera de alcance y no se certifican por esta fase.

## Backend Sync Notes

[RESOLVED]
Point: schema company-scoped de categorías pendiente.
Final contract: migración aplicada batch166, tres categorías T1/C1, tres referencias coherentes, cero unscoped/huérfanas/discrepantes.
Date: 2026-11-12.

[OPEN]
Point: certificación de mutaciones e históricos eliminados.
File / endpoint: store/update/destroy; Entry::category_invoice.
Impact: no se ejecutan mutaciones; dataset sin categorías soft-deleted.
What backend needs to define or correct: no defecto nuevo demostrado; programar QA autorizado de persistencia/históricos si requerido.
Frontend can continue: Yes.

No se reabren pendientes de otras fases. No se garantiza aislamiento de consultas nuevas que omitan forCurrentCompany; conservar contrato explícito.

## Revisit Triggers

Cambios en backfill/contextos, nuevas categorías sin company, referencias históricas multiempresa, permisos, política soft delete, nuevos consumidores Invoice, payload o esquema. Reauditar mutaciones en fase autorizada; revisar histórico real eliminado cuando exista dataset.

## Files / Validation / Final Status

Modificados config/venti-next.php; resources/views/categoryInvoice/index/create/edit.blade.php; public/js/categoryInvoice/index/create/edit.js; este documento. Backend, migraciones y datos sin cambios; CSS nuevo0. Sin commit.

PHP7.3 artisan view:cache, node --check de los tres JS y git diff --check PASS.

- Invoice Categories List — FRONTEND INTEGRATED
- Create / Edit — FRONTEND INTEGRATED
- Tenant / Company Isolation — QA PASSED (alcance descrito)
- Migration / Backfill — QA PASSED
- Referenced Category Protection — BACKEND CONTRACT CONFIRMED
- Mutations — QA PENDING
- Backend — UNCHANGED

QA final del listado: se reutilizó el dom DataTables de unitmeasure/index.js para separar toolbar, summary, tabla con scroll local y paginación. La utilidad Bootstrap text-wrap evita que el resumen desborde a 320 px. Tras recuperar una interrupción temporal del navegador se repitieron los ocho anchos: sin overflow global; screenshot desktop y móvil final guardados. Sin CSS nuevo.
