# REAL NEXT 5.10 — Material Configuration CRUD: Modelos

## Current Contract

Modelos es un CRUD relacional tenant-scoped. El modelo Eloquent real es `Exampler`; la interfaz y los mensajes usan el término Modelo.

| Operación | Método y ruta | Nombre | Permiso | Contrato |
| --- | --- | --- | --- | --- |
| Index | `GET /dashboard/Modelos` | `exampler.index` | `list_exampler` | Blade `exampler.index` |
| Datos | `GET /dashboard/all/examplers` | Sin nombre | `list_exampler` | DataTables JSON con `id`, `brand`, `name`, `comment` |
| Create | `GET /dashboard/crear/modelo` | `exampler.create` | `create_exampler` | Marcas activas del tenant |
| Store | `POST /dashboard/exampler/store` | `exampler.store` | `create_exampler` | `brand_id`, `name`, `comment`; responde `id`, `exampler`, `message` |
| Edit | `GET /dashboard/editar/modelo/{id}` | `exampler.edit` | `update_exampler` | Modelo y Marcas resueltos con TenantScope |
| Update | `POST /dashboard/exampler/update` | `exampler.update` | `update_exampler` | `exampler_id`, `brand_id`, `name`, `comment` |
| Delete | `POST /dashboard/exampler/destroy` | `exampler.destroy` | `destroy_exampler` | `exampler_id` tenant-aware |
| Bulk delete | `POST /dashboard/exampler/delete-multiple` | Sin nombre | **Sin permiso específico** | `ids[]` mediante `Request` genérico |

Los formularios conservan `formCreate`, `formEdit`, CSRF, `FormData`, nombres, IDs contractuales y endpoints. `name` es obligatorio, string, máximo 255 y único por tenant + Marca. `comment` es opcional y admite 255 caracteres. No existe un campo `description`; la descripción funcional actual se almacena como `comment`.

El listado conserva DataTables 1.10.x con colección tenant completa y paginación, orden y búsqueda en cliente.

## Parent Relation

La relación obligatoria es `Marca → Modelo`:

- `Exampler::brand()` pertenece a `Brand` mediante `brand_id`.
- `Brand::examplers()` contiene sus Modelos.
- Create y Edit cargan únicamente Marcas activas visibles bajo `TenantScope`, ordenadas por nombre.
- El Select2 persiste `brand_id`; Edit selecciona el valor actual del Modelo.
- `StoreExamplerRequest` y `UpdateExamplerRequest` validan que la Marca exista, pertenezca al tenant actual y no esté archivada.
- El controller vuelve a resolver la Marca con `Brand::findOrFail()` antes de persistir, como segunda barrera tenant-aware.

Cuando una Marca se elimina por el flujo aprobado actual, `BrandController` elimina físicamente todos sus Modelos antes de archivar la Marca. Por ello no deberían sobrevivir Modelos con ese padre archivado. Si una Marca se archivara por una vía externa sin la cascada del controller, la relación normal podría devolver `null`, la Marca no aparecería en Select2 y update sería rechazado por validación.

## Dependent Endpoint

`GET /dashboard/get/exampler/{brand_id}` es atendido por `BrandController@getJsonBrands`.

- Valida primero la Marca mediante `Brand::findOrFail()` bajo `TenantScope`.
- Devuelve un array de objetos `{ id, exampler }` obtenido desde la relación `Brand::examplers()`.
- Un `brand_id` de otro tenant produce 404.
- No tiene middleware de permiso específico, aunque sí hereda autenticación, usuario habilitado, password vigente y `tenant.context` del grupo dashboard.
- Es consumido por Material Create/Edit cuando cambia `#brand`; repuebla `#exampler` y mantiene `#brand_id_hidden` para el Auxiliary Modal de creación.

La ausencia de permiso explícito permanece como `BACKEND SYNC OPEN`. No se modificó backend.

## Permissions

- `list_exampler`: index y endpoint DataTables.
- `create_exampler`: create y store; también controla el botón auxiliar para crear Modelo en Material Create/Edit.
- `update_exampler`: edit y update.
- `destroy_exampler`: delete individual.

El index construye selección y acciones según los permisos efectivos. El menú lateral respeta `list_exampler` y `create_exampler`. Bulk se muestra solo para quien tiene `destroy_exampler`, pero permanece deshabilitado porque su endpoint no declara ese permiso.

## Multitenancy

`Exampler`, `Brand` y `Material` usan `BelongsToTenant`. Las consultas de controller, relaciones y Form Requests quedan bajo `TenantScope`; no existe scope por company para este catálogo.

Fixtures read-only auditados:

| Tenant | Modelos | Marcas padre usadas | Materiales con Modelo |
| --- | ---: | ---: | ---: |
| Grupo Empresarial EDESCE (`tenant_id=1`) | 9 | 6 | 13 |
| Gamarra Test Editado (`tenant_id=3`) | 3 | 2 | 3 |

No se encontraron relaciones Marca–Modelo ni Material–Modelo cruzadas entre tenants. La QA con los owners de EDESCE y Gamarra verifica aislamiento del listado, de las opciones de Marca, de Edit y del endpoint dependiente.

## Integration with Material Create/Edit

Material Create/Edit conserva:

- selector de Marca `#brand`, name `brand`;
- selector dependiente de Modelo `#exampler`, name `exampler`;
- GET `/dashboard/get/exampler/{brand_id}`;
- botón `#btn-newExampler` protegido por `create_exampler`;
- modal `#modalExampler` y formulario `#formCreateExampler`;
- hidden `#brand_id_hidden`, name `brand_id`;
- store `exampler.store` con respuesta `{ id, exampler, message }`;
- append y selección del nuevo Modelo sin recargar el formulario padre.

Cambiar Marca limpia y vuelve a cargar Modelos. La respuesta queda aislada por la Marca tenant-scoped. No se modificaron estos archivos ni sus contratos durante REAL NEXT 5.10.

## Delete Behavior

`Exampler` no usa `SoftDeletes`: la eliminación es física.

Delete individual:

1. valida `exampler_id` dentro del tenant;
2. resuelve el Modelo con TenantScope;
3. establece `materials.exampler_id = null` para los Materiales asociados;
4. elimina físicamente el Modelo;
5. confirma la transacción.

No deja Materiales apuntando al Modelo eliminado porque el controller limpia la referencia antes del hard delete. Sí elimina la clasificación histórica Modelo de esos Materiales; esa política funcional continúa pendiente de aprobación de negocio. El comentario del controller que afirma que Material aún no usa TenantScope está obsoleto.

## Bulk Delete

El endpoint bulk replica la desvinculación de Materiales y el hard delete por cada Modelo encontrado bajo TenantScope, pero:

- no tiene middleware `permission:destroy_exampler`;
- usa `Illuminate\\Http\\Request` sin FormRequest tenant-aware por cada ID;
- solo valida que `ids` exista y sea un array;
- ejecuta una política destructiva cuya aprobación de negocio sigue abierta.

La UI presenta el control solo bajo `destroy_exampler`, deshabilitado e inerte con `data-backend-sync-open="true"`. No se envía ninguna petición.

## Backend Sync Notes

`BACKEND SYNC OPEN`

1. Proteger `/dashboard/exampler/delete-multiple` con `permission:destroy_exampler` y un FormRequest que valide todos los IDs dentro del tenant.
2. Confirmar si borrar un Modelo debe desvincular Materiales y perder la clasificación histórica, o bloquearse cuando existen dependencias.
3. Definir permiso explícito para `/dashboard/get/exampler/{brand_id}`: `list_exampler`, permiso de Materiales o una política compartida documentada.
4. Actualizar los comentarios obsoletos de `ExamplerController` sobre el TenantScope de Material.
5. Definir comportamiento defensivo para un Modelo cuyo padre haya sido archivado por una vía externa al flujo normal de Marca.

## Revisit Triggers

- Al autorizar y validar el bulk delete.
- Al aprobar la política histórica de Material–Modelo.
- Si Modelo adopta soft delete.
- Si el endpoint dependiente recibe middleware de permiso.
- Si Marca cambia su política de eliminación en cascada.
- Si los catálogos pasan de tenant-scoped a company-scoped.

## Implementation Result

- `exampler.index`, `exampler.create` y `exampler.edit` se incorporaron a la allowlist Venti Next.
- Index reutiliza Operational List, DataTables, Result Summary, alineación transversal, Row Actions Dropdown, paginación y empty state.
- Create/Edit reutilizan form surface y Select2, conservando el contrato Marca → Modelo.
- Bulk inseguro permanece deshabilitado.
- No se añadió CSS ni se modificó backend o persistencia.

Estado: `APPROVED WITH BACKEND SYNC OPEN`.
