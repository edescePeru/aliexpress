# REAL NEXT — Contactos — Configuration CRUD

2026-11-13. Listado, crear, editar y eliminados/restaurar: **FRONTEND INTEGRATED**. Tenant Isolation: **QA PASSED** en lectura. Company / Branch: **NOT APPLICABLE**. Mutations: **QA PENDING**. Backend: **UNCHANGED**. Sin commit ni mutaciones de contactos/clientes.

## Routes

Prefijo `/dashboard/`, `ContactNameController`; middleware común auth, check.user.enabled, password.changed, tenant.context. Scope de todas las operaciones: tenant **sí**, company/branch **no**.

| Route | Action / route name | Permission | Read/Mutation | QA |
|---|---|---|---|---|
| GET contactos | index / contactName.index | list_contactName | Read | EDESCE/Gamarra PASS |
| GET all/contacts | getContacts / sin nombre | list_contactName | Read JSON | IDs contextuales PASS |
| GET crear/contacto | create / contactName.create | create_contactName | Read form | Render y lookup PASS |
| POST contact/store | store / contactName.store | create_contactName | Mutation | No ejecutado |
| GET editar/contacto/{id} | edit / contactName.edit | update_contactName | Read form | Propio, ajeno e inexistente PASS |
| POST contact/update | update / contactName.update | update_contactName | Mutation | No ejecutado |
| POST contact/destroy | destroy / contactName.destroy | destroy_contactName | Soft-delete | No ejecutado |
| GET contactos/restore | indexrestore / contactName.indexrestore | destroy_contactName | Read form/list | Empty state PASS |
| GET all/contacts/destroy | getContactsDestroy / sin nombre | destroy_contactName | Read JSON | Vacío contextual PASS |
| POST contact/restore | restore / contactName.restore | destroy_contactName | Mutation | No ejecutado |

## Permissions

Nombres verificados en HEAD, conservados en rutas, directivas y acciones. No Owner bypass añadido. Listado mantiene permisos de edición/eliminación enviados por controller. Restore está protegido por destroy_contactName en GET y POST. No se alteraron roles ni capacidades; no se ensayó una cuenta adicional sin permisos.

## Tenant Scope

ContactName usa `BelongsToTenant` y `SoftDeletes`. getContacts y getContactsDestroy conservan scope; edit usa findOrFail contextual. Formularios consultan Customer con TenantScope. Los Requests Store/Update/Delete/Restore validan IDs por tenant, incluidos estados deleted_at, sin aceptar company/branch para reducir artificialmente el maestro.

Dataset revalidado en DB local mediante transacción READ ONLY, SELECT/action GET y ROLLBACK, sin fixtures:

| Contexto | IDs activos | Eliminados | Clientes del formulario |
|---|---|---|---|
| EDESCE T1/C1 | `[1,2,3]` | `[]` | `[1,4,3,2,5]` en orden business_name |
| EDESCE T1/C2 | `[1,2,3]` | `[]` | Mismos IDs |
| Gamarra T3/C3 | `[4]` | `[]` | `[6]` |

Relaciones actuales: contactos 1/2 → cliente 1; contacto 3 → cliente 2; contacto 4 → cliente 6. Cuatro contactos físicos, todos activos. Comparación por IDs completos, no solo conteos.

QA HTTP: Owner EDESCE (company EDESCE, Local Principal) vio `[1,2,3]`; Owner Gamarra (Gamarra Moda, Local Principal) vio `[4]`. Edit 4 desde EDESCE y edit 1 desde Gamarra: 404. ID999999 desde EDESCE: 404. Actions invocados en proceso también rechazaron IDs1/2/3 desde T3 y 4 desde T1; C2 conserva contactos de T1. Esta prueba de C2 fue en memoria, no cambio de sesión navegador. Navegador final queda en Owner Gamarra/listado.

## Fields

- `customer_id`: obligatorio, cliente activo del tenant. Label «Cliente» aclara la antigua «Empresa»: no es Company operativa.
- `name`: requerido/string/max255; mayus(this) preservado.
- `area`: nullable/string; mayus(this) preservado.
- `phone`: nullable/string/max12.
- `email`: nullable/email/string/max255; único por tenant, incluye eliminados; Update ignora contacto actual.
- `contactName_id`: hidden de edición y modales, validado por tenant. Código generado por backend (`CN-` + ID), no input nuevo.

Preservados formCreate/formEdit/formDelete/formRestore, data-url, enctype, CSRF, btn-submit, customer_id/data-states, names y FormData POST. No se añadió action/method HTML que cambie el contrato AJAX. Create/Edit JS funcionales intactos. Se añaden IDs accesibles solo donde antes no existían y old()/@error para los campos; no se duplican reglas backend en JS. No se enviaron formularios para provocar validaciones; manejo AJAX de errores existente conservado, validación con respuesta real pendiente de mutation QA.

## Relationships

ContactName pertenece a Customer mediante customer_id y relación withTrashed; tiene quotes por contact_id. Customer tiene contactNames. No hay relación directa Supplier ni OrderPurchase en los modelos inspeccionados; no se añade un selector de proveedor. Mostrar al cliente eliminado asociado no autoriza restaurar automáticamente ese cliente.

## Soft Delete

Activos excluyen deleted_at; destroy invoca delete(), no forceDelete. Deleted lista onlyTrashed bajo tenant. DeleteContactNameRequest exige registro activo del tenant. No se eliminó ningún registro ni se alteró el comportamiento de Customer.

## Restore

Existe vista contactName.restore, ruta de vista **contactName.indexrestore** y POST **contactName.restore**: no confundirlos en allowlist. RestoreRequest exige contacto eliminado del tenant; controller obtiene customer_id del contacto, ignora el valor del navegador y devuelve 422 si cliente ausente/eliminado. Se preserva hidden customer_id del modal por compatibilidad. **RESTORE POSITIVE QA — PENDING DATASET**; cero eliminados, sin fabricar ninguno ni ejecutar restore.

## Consumers

Inspección focalizada: Customer/CustomerController (relación, ciclo de soft-delete y lookup de contactos/email); Quote y QuoteController (contact_id, relación y lookup por customer_id); QuoteSaleController (resolución/validación de contacto); HomeController (conteo). Sin cambios en consumidores ni QA end-to-end de Quotes, POS, Clientes o Proveedores. No se declara ausencia universal de consumidores por no aparecer en esta revisión limitada.

## Visual Mapping

Configuration CRUD de Customers/Suppliers: page heading, toolbar, search/length de DataTables, summary, tabla compacta, paginación, dropdown y empty state. Create/Edit usan mismos next-form-section, row gutters y next-list-toolbar; toolbar-context-only mantiene contexto móvil. Modales reutilizan next-aux-modal.

Columnas reales conservadas: Código, Nombre de contacto, Cliente (antes Empresa), Área, Teléfono, Email, Acciones. Código/acciones centrados; descriptivos izquierda. Sin columnas inventadas de documento/estado. Datos textuales escapados; atributos data-delete/data-restore/data-contact/data-company/data-idcompany y handlers conservados. No se cambian endpoints JSON ni payloads de escritura.

**ALLOWLIST MATCH: YES**: añadidas únicamente contactName.index/create/edit/indexrestore. Assets venti.min.css y venti-next.css observados. **0 CSS nuevo**; eliminadas reglas inline de Select2 redundantes.

## Responsive

Listado, create, edit y restore: 320/375/390/576/768/992/1200/1440 medidos sin overflow global. Tabla con scroll local. Se detectó overflow de .dataTables_info a 320 y se resolvió con `text-wrap` en preDrawCallback, siguiendo el patrón compartido; sin CSS nuevo. Search sin coincidencias mostró «No se encontraron resultados»; restore vacío mostró «Ningún dato disponible». Dropdown de edición mostró destino propio correcto. Select2 preserva cliente seleccionado y lookup contextual. Desktop y formularios móviles inspeccionados visualmente. Consola final sin errores; 404 de QA esperados no se transforman en empty state.

Dataset insuficiente para segunda página real; controles/paginación inicial de DataTables renderizados. Ninguna mutación ni confirmación de delete/restore ejecutada.

## Backend Sync Notes

No se encontró un nuevo blocker de lectura que requiera cambiar backend o backlog. Permanecen abiertos los límites de esta fase: MUTATION QA PENDING, RESTORE POSITIVE QA PENDING DATASET y multipágina con dataset mayor. No cerrar estos puntos por haber integrado la fachada. Backend, Requests, relaciones y consumidores UNCHANGED.

Archivos: cuatro Blade contactName; public/js/contactName/index.js y restore.js (presentación); config/venti-next.php; este documento. Validaciones: PHP7.3 artisan view:cache, node --check de ambos JS y git diff --check. Revisit triggers: cambios de scope/Requests, relación Customer o permisos, dataset eliminado y autorización explícita de mutation QA.
