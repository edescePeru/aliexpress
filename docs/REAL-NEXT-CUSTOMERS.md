# REAL NEXT — Clientes

Roadmap C fase3 · 2026-11-12. Fachada integrada; backend intacto; no commit.

## Contract Audit

Auditados CustomerController, Customer, Store/Update/Delete/RestoreCustomerRequest, ContactName, DecolectaService, rutas y cuatro Blades/JS customer. Listado DataTables client-side con `data` de GET all/customers; eliminados con all/customers/destroy. Búsqueda/orden/tamaño/paginación permanecen locales. No se modificó lógica AJAX mutadora: create/edit siguen usando click btn-submit, FormData, append special como string true/false, toastr y navegación/reload existente.

Formulario real sin consultas automáticas ni botón Decolecta. No hay selectors DNI/RUC/CE, nombres/apellidos separados, teléfono/email, select de ubigeo ni contacto editable aquí. No se copiaron esos campos de otro mantenedor.

## Routes

Prefijo `/dashboard/`, salvo test-* indicados después. Grupo común: auth, check.user.enabled, password.changed, tenant.context. No se alteró ninguna ruta.

| Operation | Route / name | Permission | Tenant | Company | Branch | Read/Write |
|---|---|---|---|---|---|---|
| List | GET clientes / customer.index | list_customer | Sí | No | No | Read |
| List JSON | GET all/customers | list_customer | Sí | No | No | Read |
| Create view | GET crear/cliente / customer.create | create_customer | Sí | No | No | Read |
| Store | POST customer/store / customer.store | create_customer | Sí | No | No | Write |
| Edit view | GET editar/cliente/{id} / customer.edit | update_customer | Sí | No | No | Read |
| Update | POST customer/update / customer.update | update_customer | Sí | No | No | Write |
| Delete | POST customer/destroy / customer.destroy | destroy_customer | Sí | No | No | Write |
| Deleted view | GET clientes/restore / customer.indexrestore | destroy_customer | Sí | No | No | Read |
| Deleted JSON | GET all/customers/destroy | destroy_customer | Sí | No | No | Read |
| Restore | POST customer/restore / customer.restore | destroy_customer | Sí | No | No | Write |
| Payload | GET customers/{customer}/payload / customers.payload | Sin permission adicional en declaración | Sí, binding | No | No | Read |
| Lookup | GET customer/decolecta/{documento} / customer.consultarDocumentoDecolecta | Sin permission adicional en declaración | Contexto de grupo | No | No | External |
| Search/create | GET customer/search-or-create/{documento} / customer.searchOrCreateByDocument | Sin permission adicional en declaración | Sí, búsqueda/create | No | No | Write + posible External |

Fuera de ese grupo: GET /test-ruc/{ruc} y /test-dni/{dni} apuntan a consultas externas; no se invocaron. Ver Backend Sync.

## Permissions

list_customer, create_customer, update_customer, destroy_customer. Restore no tiene permiso separado: utiliza destroy_customer en vista, datos y POST. @can y array getPermissionsViaRoles conservados. Ambos Owners auditados poseen los cuatro permisos. No se probó una matriz de usuarios con permisos limitados.

## Tenant Scope

Customer usa BelongsToTenant/TenantScope. edit findOrFail; requests update/delete requieren ID del tenant no eliminado; restore requiere ID tenant eliminado y vuelve a resolver onlyTrashed. RUC único dentro del tenant, incluyendo eliminados; update ignora ID propio. Crear asigna tenant por trait. Payload recibe Customer por binding Eloquent tenant-scoped.

TenantScope no filtra si no hay contexto por diseño compartido; los resultados de esta auditoría corresponden a Owners con contexto activo y no certifican usos fuera de ese contexto.

## Company / Branch Non-Scope

NOT APPLICABLE. Catálogo compartido entre empresas/sucursales del tenant. No selectors, hidden company_id/branch_id ni filtros frontend añadidos. Scopes de documentos consumidores no se trasladan al cliente.

## Fields

| Campo | Contrato |
|---|---|
| ruc → RUC | Required string; unique tenant; controller limita longitud a11 si special no es string true |
| business_name | Required string max255; mayus(this) preservado |
| special | Checkbox btn-grouped + Bootstrap Switch SI/NO; extranjero; FormData agrega string true/false |
| address | Nullable string max255; mayus(this) |
| location | Nullable string max255; texto libre; mayus(this), no catálogo de ubigeo |
| customer_id | Hidden edit/delete/restore; IDs conservados donde existían |
| code | Backend genera C- + ID padded5; no editable |
| deleted_at | Soft delete; badges derivados de listado activo/onlyTrashed |

Names, formCreate/formEdit/formDelete/formRestore, btn-submit, btn-grouped, customer_id, permissions, data-url, data-delete/data-restore/data-code/data-company, enctype y CSRF preservados. Añadidos IDs para labels donde antes no existían, old() y @error. Cancelar sigue siendo reset. Scripts create/edit intactos.

## Customer Types

Mantenedor no posee tipo persona natural/jurídica ni ramas condicionales por documento. Usa un único business_name, RUC y special extranjero. No se añadió lógica fiscal ni restricciones nuevas. Label documento comunica el uso DNI/RUC sin transformar el valor enviado; backend sigue siendo autoridad para documentos extranjeros.

## Documents

El CRUD no exige exactamente8/11 dígitos ni separa CE; su validación real es la descrita en Fields. La integración externa sí normaliza a dígitos y acepta DNI8/RUC11. No se extrapoló esa regla externa al formulario manual.

## Contacts

Customer::contactNames hasMany customer_id. ContactName es tenant-scoped con SoftDeletes; no forma parte de los campos de este CRUD. Customer también tiene quotes y guides. Payload read-only obtiene email del primer contacto si existe. No se modificó ContactName ni se modernizó su mantenedor.

## Soft Delete / Restore

Customer usa SoftDeletes y CascadeSoftDeletes con contactNames. Delete ejecuta delete(), no forceDelete. Restore restaura Customer y luego TODOS los contactNames onlyTrashed relacionados; no distingue si fueron eliminados antes que el cliente. Contrato actual preservado, pendiente confirmación backend si se requiere distinción histórica.

Frontend diferencia Activo/Eliminado según origen del listado; no inventa estado comercial. Modal de eliminación abierto y cancelado; no submit. Restore UI integrada, empty state y navegación probados. Ambos tenants tienen0 eliminados: RESTORE POSITIVE QA — PENDING DATASET. No se creó dataset. RestoreCustomerRequest evaluado sin mutación rechaza IDs activos y ajenos/inexistentes; esto no sustituye QA HTTP POST positivo.

## Decolecta

Auditoría estática solamente. Config `services.decolecta.token` / `services.decolecta.url`, variables DECOLECTA_TOKEN/DECOLECTA_URL; default URL del servicio en config. No se leyeron ni documentaron credenciales reales. Service constructor exige configuración; requests usan bearer y GET reniec/dni o sunat/ruc. Errores se traducen a422.

consultarDocumentoDecolecta devuelve success, created=false, source=api, customer con id=null/business_name/RUC/address/location. Consultas DNI/RUC de prueba usan consultarDni/consultarRuc. El lookup de DNI mapea nombre completo/nombres/apellidos; RUC razón social/dirección/provincia/departamento. Sin guardado en consultarDocumentoEnDecolecta.

DECOLECTA EXTERNAL QA — OUT OF SCOPE / PENDING. No se ejecutaron llamadas, no se certifican disponibilidad, cuotas, credenciales ni respuesta real. No se añadió botón de lookup inexistente en estas vistas.

## searchOrCreate Mutation Risk

GET customer/search-or-create/{documento} llama buscarOCrearClientePorDocumento: primero consulta Customer del tenant; si no existe llama API, crea Customer y guarda code. Responde success/created/source/customer. Es MUTATION — EXCLUDED, incluso si un documento existente pudiera devolver solo lectura. No se llamó ni se utilizó para QA.

## POS Consumers

public/js/puntoVenta/index.js e indexV2.js y list/listError usan customer/decolecta en el flujo documental. PuntoVentaController consume customer_id y relación/snapshot de cliente en ventas/listados. Se conserva Customer como catálogo tenant mientras las ventas mantienen sus propios scopes. POS core continúa congelado, sin cambios.

## Quote Consumers

QuoteSaleController usa Customer en filtros/listas, resolución tenant y formularios de cotización. También existen relaciones Quote y ReferralGuide. Ningún consumidor de Cotizaciones/POS/facturación fue modificado ni certificado por esta integración.

## Visual Mapping

REUSE WITH CONTRACT ADAPTATION: Venti Next Configuration CRUD de Proveedores/Empresas. next-page-heading/toolbar, next-toolbar-actions flex-wrap, next-form-section, next-section-header, next-operational-list, next-list-* y next-data-table. Dropdown con next-row-actions-trigger/menu y boundary viewport; modales Bootstrap existentes.

Columnas: código/documento/estado/acciones centrados; razón social/dirección/ubicación izquierda. No se agregaron teléfono/email/contactos inexistentes. DataTables info usa utilidad text-wrap; empty state text-left/text-wrap visible en móvil. Escapado de atributos de acciones; comilla sobrante del botón restore corregida.

0 CSS nuevo. Se retiró regla inline Select2 de vistas que no tienen selects; cargas de plugins conservadas. ALLOWLIST MATCH: YES, solo customer.index/create/edit/indexrestore. Confirmada carga DOM de venti.min.css y venti-next.css. No JSON/Decolecta ni ContactName en allowlist.

## Action Matrix

| Action | Route | Permission | Read/Mutation/External | Included in QA |
|---|---|---|---|---|
| List/search/pagination | customer.index + all/customers | list_customer | Read | Sí, búsqueda positiva/vacía y limpieza |
| Create form | customer.create | create_customer | Read | Sí, ambos Owners |
| Edit form | customer.edit | update_customer | Read | Sí, propio/cruzado |
| Deleted list | customer.indexrestore + all/customers/destroy | destroy_customer | Read | Sí, vacío ambos tenants |
| Payload cruzado | customers.payload | Grupo auth/context | Read | Sí,404 ambos sentidos |
| Store/update | customer.store/update | create/update_customer | Mutation | No |
| Destroy/restore | customer.destroy/restore | destroy_customer | Mutation | No; confirmación delete cancelada |
| searchOrCreate | customer.searchOrCreateByDocument | Grupo auth/context | Mutation + External | EXCLUDED |
| Lookup | customer.consultarDocumentoDecolecta | Grupo auth/context | External | EXCLUDED |
| Test DNI/RUC | /test-dni y /test-ruc | Sin guard explícito en rutas | External | EXCLUDED |

## Responsive QA

| Width | List | Create | Edit | Deleted |
|---|---|---|---|---|
| 320 | PASS | PASS | PASS | PASS |
| 375 | PASS | PASS | PASS | PASS |
| 390 | PASS | PASS | PASS | PASS |
| 576 | PASS | PASS | PASS | PASS |
| 768 | PASS | PASS | PASS | PASS |
| 992 | PASS | PASS | PASS | PASS |
| 1200 | PASS | PASS | PASS | PASS |
| 1440 | PASS | PASS | PASS | PASS |

Matriz DOM de32 combinaciones: scrollWidth <= innerWidth. Revisión visual desktop/móvil, tabla con scroll LOCAL, formularios apilados con gutters compartidos y botones completos; estado vacío legible. GLOBAL OVERFLOW: NONE. Consola final sin warnings/errors observados.

Paginación visible; dataset5/1 con tamaño mínimo10 produce una sola página: transición entre páginas PENDING DATASET, no se fabricaron filas ni se cambió contrato para probarla.

Evidencia fuera del repo: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/customers/`, capturas desktop/mobile y responsive-matrix.json.

## Cross-tenant QA

| Cuenta/rol | Contexto | Activos | Eliminados | Edit propio | Edit ajeno | Payload ajeno |
|---|---|---|---|---|---|---|
| Owner EDESCE / owner | T1 C1 B1 Local Principal | IDs1,2,3,4,5 | 0 | ID1 carga browser; IDs1–5 controller200 | ID6:404 | ID6:404 |
| Owner Gamarra / owner | T3 C3 B4 Local Principal | ID6 | 0 | ID6 carga | ID1:404 | ID1:404 |

ID99999999:404 en controlador ambos contextos y browser EDESCE. List/create/edit/deleted en ambos Owners. EDESCE búsqueda C-00002 devuelve solo esa fila; texto sin coincidencia devuelve empty state; limpieza recupera5. Ningún ID ajeno en listado.

Auditoría PHP adicional bajo transacción READ ONLY, rollback final, sesión array y Auth::setUser: confirma dataset, scopes, findOrFail, permisos y route binding payload. No instanció Decolecta ni llamó endpoints mutadores. Sesión final browser Owner EDESCE. No contraseñas persistidas.

Tenant Isolation — QA PASSED para contextos probados, no una certificación de todas las combinaciones de permisos/contextos.

## Mutation Exclusions

No crear, editar, eliminar, restaurar, consumir GET mutador ni llamar Decolecta/test-DNI/test-RUC. No se guardaron datos de formulario. No modificaciones de consumidores/contactos/backend. Mutations QA PENDING. Restore Positive QA PENDING DATASET. No commit.

## Backend Sync Notes

[OPEN]
Point: searchOrCreate muta mediante GET y no declara permission:create_customer adicional.
File / endpoint: routes/web.php customer.searchOrCreateByDocument; DecolectaService.
Impact: semántica GET no segura y autorización específica de creación por definir.
What backend needs to define or correct: contrato/método y autorización de creación; frontend no lo usa para QA ni lo modifica.
Frontend can continue: Yes.

[OPEN]
Point: rutas test de consulta externa sin guard explícito.
File / endpoint: routes/web.php /test-ruc/{ruc}, /test-dni/{dni}, fuera de grupos auth mostrados.
Impact: potencial uso externo/consumo de cuota; evidencia estática, endpoints NO ejecutados.
What backend needs to define or correct: restringir o retirar rutas de prueba según política de despliegue.
Frontend can continue: Yes.

[OPEN]
Point: restore de todos los contactos eliminados relacionados.
File / endpoint: CustomerController@restore.
Impact: también puede restaurar contactos borrados antes del cliente; sin dataset para QA positivo.
What backend needs to define or correct: confirmar que esta semántica es intencional o preservar origen temporal de eliminación.
Frontend can continue: Yes; contrato intacto.

[OPEN]
Point: permisos directos frente a roles.
File / endpoint: CustomerController@index getPermissionsViaRoles vs rutas/@can.
Impact: posible autorización directa no representada en acciones JS; no reproducido con Owners.
What backend needs to define or correct: confirmar soporte de permisos directos/alinear array si aplica.
Frontend can continue: Yes.

## Revisit Triggers

Cambios backend en scopes, validaciones/documentos, lookup/creación por documento, permisos, restore/contactos; nuevo dataset eliminado o multipágina; autorización para QA mutador/externo; regresión visual. Proveedores permanece cerrado salvo regresión directa.

## Files / Checks / Status

Modificados: config/venti-next.php; resources/views/customer/{index,create,edit,restore}.blade.php; public/js/customer/{index,restore}.js; este documento. JS create/edit y todos los archivos backend intactos.

PHP7.3 artisan view:cache PASS; node --check index/restore PASS; git diff --check PASS.

- Customer List — FRONTEND INTEGRATED
- Create / Edit — FRONTEND INTEGRATED
- Deleted / Restore UI — FRONTEND INTEGRATED
- Tenant Isolation — QA PASSED
- Company / Branch — NOT APPLICABLE
- Mutations — QA PENDING
- Restore Positive QA — PENDING DATASET
- Decolecta — EXTERNAL QA PENDING / OUT OF SCOPE
- searchOrCreateByDocument — MUTATION — EXCLUDED
- Backend — UNCHANGED
