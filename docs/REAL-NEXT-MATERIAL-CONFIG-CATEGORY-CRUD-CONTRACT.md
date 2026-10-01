# REAL NEXT 5.2 — Material Configuration CRUD: Categorías

## Current Contract

Categorías conserva su flujo real de páginas separadas y DataTables 1.10.20 con carga AJAX del listado.

| Operación | Método y endpoint | Route name | Middleware de permiso | Payload |
| --- | --- | --- | --- | --- |
| Listado | `GET /dashboard/Categorias` | `category.index` | `list_category` | — |
| Datos DataTables | `GET /dashboard/all/categories` | — | `list_category` | — |
| Crear | `GET /dashboard/crear/categoria` | `category.create` | `create_category` | — |
| Guardar | `POST /dashboard/category/store` | `category.store` | `create_category` | `name`, `description`, CSRF |
| Editar | `GET /dashboard/editar/categoria/{id}` | `category.edit` | `update_category` | `id` en URL |
| Actualizar | `POST /dashboard/category/update` | `category.update` | `update_category` | `category_id`, `name`, `description`, CSRF |
| Eliminar | `POST /dashboard/category/destroy` | `category.destroy` | `destroy_category` | `category_id`, CSRF |
| Bulk delete | `POST /dashboard/category/delete-multiple` | — | **Sin permiso explícito** | `ids[]`, CSRF |
| Subcategorías por categoría | `GET /dashboard/get/subcategories/{category_id}` | — | Sin permiso específico; permanece dentro del grupo autenticado/tenant | `category_id` en URL |

`StoreCategoryRequest` exige `name` (`string`, máximo 255 y único entre registros no eliminados del tenant) y acepta `description` (`nullable|string|max:255`). `UpdateCategoryRequest` añade `category_id`, valida que pertenezca al tenant y excluye el registro actual de la unicidad. `DeleteCategoryRequest` exige un `category_id` existente dentro del tenant.

## Differences vs Unit Pilot

- El recurso usa permisos `*_category`, rutas y payload propios; no comparte IDs ni endpoints con Unidad de medida.
- `Category` es padre de `Subcategory` y participa en una cadena de borrado en cascada; Unidad no tiene esta jerarquía.
- El endpoint bulk real existe para Categorías, pero carece de middleware de permiso explícito. La acción permanece deshabilitada hasta sincronizar backend.
- El JS Legacy apuntaba incorrectamente a `/dashboard/subcategory/delete-multiple`. El listado modernizado declara el endpoint real de Categorías en el markup, pero el guard de frontend impide ejecutarlo mientras `data-backend-sync-open="true"` esté activo.
- Visualmente no se introduce ninguna variante: Index, Create y Edit reutilizan literalmente el patrón aprobado de Unidad.

## Permissions

- `list_category`: abre el index y el endpoint de datos.
- `create_category`: abre create, permite store y muestra la acción primaria.
- `update_category`: abre edit, permite update y muestra `Editar` en el ellipsis.
- `destroy_category`: permite destroy individual, muestra selección y `Eliminar` en el ellipsis.

Las columnas y acciones del DataTable se construyen de acuerdo con el arreglo real de permisos entregado por el controller. El bulk delete no se considera autorizado aunque el usuario tenga `destroy_category`, porque su ruta no posee middleware explícito.

## Multitenancy

`Category` usa `BelongsToTenant`. Las consultas `Category::all()`, `findOrFail`, validaciones `exists/unique`, update, delete, bulk y la resolución de subcategorías se ejecutan bajo el scope tenant vigente. `tenant_id` se asigna mediante el comportamiento del modelo; no forma parte del payload del navegador.

Create y Edit mantienen los endpoints relativos del tenant autenticado y no incorporan identificadores de otra compañía. El QA visual confirmó conjuntos distintos: EDESCE renderizó 7 categorías y Gamarra 2 categorías, sin nombres cruzados entre ambos resultados observados. No se creó, actualizó ni eliminó información durante la comprobación.

## Delete Behavior

El borrado es lógico (`SoftDeletes`). `CategoryController::destroy` carga las subcategorías y las elimina antes de borrar la categoría. Además, el modelo declara cascade soft delete sobre `subcategories`; `Subcategory` continúa la cascada hacia tipos de material y éstos hacia subtipos.

No existe una validación previa que bloquee categorías con hijos. Tampoco hay una política explícita para materiales que ya referencian la jerarquía eliminada. Por ello, la confirmación individual se conserva pero no se ejecuta en QA.

## Bulk Delete

Estado: `BACKEND SYNC OPEN`.

El endpoint correcto es `/dashboard/category/delete-multiple` y espera `ids[]` más CSRF. El endpoint existe y filtra las categorías a través del scope tenant, pero la ruta no declara `permission:destroy_category`. En el frontend:

- se eliminó la referencia errónea al bulk de Subcategorías;
- el botón conserva el tratamiento secundario/destructivo del patrón compartido;
- permanece `disabled`, `aria-disabled="true"` y con `data-backend-sync-open="true"`;
- el handler aborta antes de mostrar confirmación o emitir red.

No se ejecutó bulk delete.

## Backend Sync Notes

1. Añadir autorización explícita equivalente a `permission:destroy_category` al endpoint bulk.
2. Definir y probar la política de eliminación cuando existan Subcategorías, tipos, subtipos o Materiales relacionados.
3. Confirmar si el borrado manual de hijos y `CascadeSoftDeletes` es deliberadamente redundante o debe existir una sola estrategia.
4. Revisar si `/dashboard/get/subcategories/{category_id}` requiere un permiso de catálogo adicional además de autenticación y scope tenant.

Estos puntos no bloquean la modernización frontend mientras el bulk permanezca inerte y no se ejecuten eliminaciones durante QA.

## Revisit Triggers

- Se añade middleware al bulk delete.
- Se define una regla de bloqueo o cascada para categorías con relaciones.
- Cambian los campos, requests o endpoints del catálogo.
- DataTables cambia de versión o de modalidad client/server.
- Se modifica el patrón compartido `Material Configuration CRUD` o el Row Actions Dropdown.
- Se habilita una acción adicional por fila.
