# REAL NEXT 5.9 — Material Configuration CRUD: Marcas

## Current Contract

Marcas es un CRUD simple con relación padre-hijo hacia Modelos (`Exampler`) y referencia directa desde Materiales.

| Operación | Método y ruta | Nombre | Permiso | Request / respuesta |
| --- | --- | --- | --- | --- |
| Index | `GET /dashboard/Marcas` | `brand.index` | `list_brand` | Blade `brand.index` |
| Datos | `GET /dashboard/all/brands` | Sin nombre | `list_brand` | DataTables JSON, campos `id`, `name`, `comment` |
| Create | `GET /dashboard/crear/marca` | `brand.create` | `create_brand` | Blade `brand.create` |
| Store | `POST /dashboard/brand/store` | `brand.store` | `create_brand` | `name`, `comment`; JSON con `data.id`, `data.name`, `data.comment` |
| Edit | `GET /dashboard/editar/marca/{id}` | `brand.edit` | `update_brand` | `Brand::findOrFail()` bajo `TenantScope` |
| Update | `POST /dashboard/brand/update` | `brand.update` | `update_brand` | `brand_id`, `name`, `comment` |
| Delete | `POST /dashboard/brand/destroy` | `brand.destroy` | `destroy_brand` | `brand_id` validado dentro del tenant |
| Modelos por marca | `GET /dashboard/get/exampler/{brand_id}` | Sin nombre | Sin permiso específico | Valida primero la Marca bajo `TenantScope` y devuelve sus Modelos |
| Bulk delete | `POST /dashboard/brand/delete-multiple` | Sin nombre | **Sin permiso específico** | `ids[]` mediante `Request` genérico |

Los formularios conservan los IDs funcionales `formCreate` y `formEdit`, los nombres `name`, `comment`, `brand_id`, CSRF, `FormData` y los endpoints existentes. `StoreBrandRequest` y `UpdateBrandRequest` exigen un nombre único entre marcas activas del tenant, con máximo 255 caracteres; el comentario es opcional y admite 255 caracteres.

El listado sigue usando DataTables 1.10.x con carga AJAX client-side. La modernización cambia el DOM visual y el adapter de presentación, no la tecnología ni la forma de obtener datos.

## Differences vs Unit / Gender Pilot

- Comparte el mismo Page Header, toolbar, Result Summary, `next-data-table`, búsqueda, selector de cantidad, paginación, empty state, formularios y Auxiliary Modal.
- Nombre y comentario son texto alineado a la izquierda; selección y acciones se alinean al centro, con header y body coincidentes.
- Marca tiene dependencias reales con Modelos y Materiales. Por eso el mensaje del modal individual describe el comportamiento destructivo actual y el bulk permanece deshabilitado.
- La acción individual usa el mismo Row Actions Dropdown portalizado a `body`; el comportamiento compartido de `admin2.js` conserva Popper `flip`, límite de viewport y scroll horizontal local.
- No se añadió CSS: se reutilizaron literalmente los estilos Venti Next aprobados.

## Permissions

Los permisos de las rutas CRUD son:

- `list_brand`: index y endpoint DataTables.
- `create_brand`: create y store.
- `update_brand`: edit y update.
- `destroy_brand`: delete individual.

Blade y el adapter DataTables ocultan columnas y acciones que el usuario no puede ejecutar. El menú lateral también respeta `list_brand` y `create_brand`.

El endpoint bulk y el endpoint auxiliar de Modelos no declaran middleware de permiso específico. El primero es un bloqueo de autorización; el segundo queda limitado por autenticación, contexto tenant y validación de la Marca, pero su permiso de lectura sigue siendo una decisión de backend.

## Multitenancy

`Brand` y `Exampler` usan `BelongsToTenant`; `Material` también está actualmente bajo `BelongsToTenant`. Create asigna `tenant_id` automáticamente desde `TenantContext`; index, DataTables, edit, update y delete resuelven registros con `TenantScope`. Los Requests de update/delete, además, exigen que el ID exista, esté activo y pertenezca al tenant actual.

La revisión read-only de fixtures encontró:

| Tenant | Marcas activas | Modelos asociados | Materiales con marca |
| --- | ---: | ---: | ---: |
| Grupo Empresarial EDESCE (`tenant_id=1`) | 9 | 9 | 14 |
| Gamarra Test Editado (`tenant_id=3`) | 2 | 3 | 3 |

No se encontraron referencias cruzadas de tenant entre Marca–Modelo, Material–Marca o Material–Modelo. La QA de ambos owners confirmó que el endpoint DataTables devuelve únicamente los registros del tenant de la sesión.

## Brand → Model Relationship

`Brand::examplers()` es una relación `hasMany`; `Exampler::brand()` es `belongsTo`. La selección de Marca en Material Create/Edit carga los Modelos mediante `/dashboard/get/exampler/{brand_id}`. El controlador valida primero la Marca con `Brand::findOrFail()` bajo `TenantScope` y solo después consulta la relación.

`Exampler` no usa `SoftDeletes`. En consecuencia, los Modelos eliminados durante el borrado de una Marca son eliminados físicamente.

## Material Dependency

`Brand::materials()` es `hasMany` y Material conserva `brand_id` y `exampler_id`. El flujo de borrado actual recorre los Modelos de la Marca, establece `materials.exampler_id = null` para cada uno, elimina físicamente el Modelo y finalmente hace soft delete de la Marca.

El flujo **no establece `materials.brand_id = null`**. Por tanto, los Materiales existentes conservan la clave hacia una Marca archivada, pero la relación Eloquent normal deja de resolverla porque `Brand` usa `SoftDeletes`. La base de datos no queda con una FK rota, aunque sí con una referencia funcionalmente oculta.

## Delete Behavior

Delete individual:

- está protegido por `destroy_brand`;
- valida `brand_id` dentro del tenant y excluye marcas ya archivadas;
- elimina físicamente todos los Modelos asociados;
- desvincula esos Modelos de Materiales;
- archiva la Marca mediante soft delete;
- no bloquea cuando existen Modelos o Materiales;
- no limpia `materials.brand_id`.

Las FK declaradas no resuelven esta política: `examplers.brand_id` tiene cascade de base de datos, pero el padre solo se archiva; `materials.brand_id` no tiene cascade. El `$cascadeDeletes = ['examplers']` de `Brand` tampoco convierte a `Exampler` en soft-deletable.

El frontend muestra la confirmación individual sin ejecutar acciones durante QA. La política de si Modelos o Materiales deben bloquear la eliminación requiere decisión de negocio.

## Bulk Delete

El endpoint bulk replica la misma cascada destructiva por cada Marca encontrada dentro del TenantScope, pero presenta dos diferencias críticas:

- no tiene middleware `permission:destroy_brand`;
- usa `Illuminate\\Http\\Request` y solo comprueba que `ids` sea un array; no aplica un FormRequest equivalente a delete individual.

La acción se mantiene visible únicamente para usuarios con `destroy_brand`, pero deshabilitada e inerte mediante `data-backend-sync-open="true"`. No se envía ninguna petición bulk.

## Backend Sync Notes

`BACKEND SYNC OPEN`

1. Definir si una Marca con Modelos o Materiales debe bloquearse, requerir desvinculación explícita o mantener la cascada destructiva actual.
2. Si se conserva la eliminación, decidir si `materials.brand_id` debe quedar en `null`, si la relación debe usar `withTrashed()`, o si debe preservarse un snapshot histórico.
3. Proteger `/dashboard/brand/delete-multiple` con `permission:destroy_brand` y un FormRequest tenant-aware que valide cada ID.
4. Confirmar si `/dashboard/get/exampler/{brand_id}` debe exigir `list_exampler`, `list_brand` o un permiso de Materiales.
5. Actualizar el comentario obsoleto del controlador que afirma que Material aún no usa `TenantScope`.

## Revisit Triggers

- Al definir la política de dependencias Marca–Modelo–Material.
- Al añadir autorización y validación al bulk delete.
- Si Modelos adopta soft delete o cambia su FK hacia Marca.
- Si Material debe mostrar Marcas archivadas en históricos.
- Si el endpoint de Modelos recibe un permiso explícito.

## Implementation Result

- `brand.index`, `brand.create` y `brand.edit` fueron añadidas a la allowlist Venti Next.
- Index, create y edit reutilizan el patrón Material Configuration CRUD aprobado.
- El bulk inseguro permanece deshabilitado.
- No se cambió backend, esquema, persistencia ni contrato AJAX.
- No se añadió CSS.

Estado: `APPROVED WITH BACKEND SYNC OPEN`.
