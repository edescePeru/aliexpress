# REAL NEXT 5.3 — Material Configuration CRUD: Subcategorías

## Current Contract

Subcategorías conserva páginas separadas, formularios AJAX y DataTables 1.10.20 en cliente. El listado obtiene toda la colección visible del tenant y carga la relación `category`.

| Operación | Método y endpoint | Route name | Permiso | Payload |
| --- | --- | --- | --- | --- |
| Listado | `GET /dashboard/Subcategorias` | `subcategory.index` | `list_subcategory` | — |
| Datos DataTables | `GET /dashboard/all/subcategories` | — | `list_subcategory` | — |
| Crear | `GET /dashboard/crear/subcategoria` | `subcategory.create` | `create_subcategory` | — |
| Guardar colección | `POST /dashboard/subcategory/store` | `subcategory.store` | `create_subcategory` | `category_id`, `subcategories[][name]`, `subcategories[][description]`, CSRF |
| Guardar individual | `POST /dashboard/subcategory/store/individual` | `subcategory.store.individual` | `create_subcategory` | Reutiliza el mismo request y payload de colección |
| Editar | `GET /dashboard/editar/subcategoria/{id}` | `subcategory.edit` | `update_subcategory` | `id` en URL |
| Actualizar | `POST /dashboard/subcategory/update` | `subcategory.update` | `update_subcategory` | `subcategory_id`, `category_id`, `name`, `description`, CSRF |
| Eliminar | `POST /dashboard/subcategory/destroy` | `subcategory.destroy` | `destroy_subcategory` | `subcategory_id`, CSRF |
| Bulk delete | `POST /dashboard/subcategory/delete-multiple` | — | **Sin permiso explícito** | `ids[]`, CSRF |
| Tipos por subcategoría | `GET /dashboard/get/types/{subcategory_id}` | — | `list_materialType` | `subcategory_id` en URL |

Create exige al menos un elemento en `subcategories[]`. El nombre es requerido, máximo 255, distinto dentro del lote y único por `tenant_id + category_id` entre registros no eliminados. La descripción es opcional y máximo 255. Update usa un payload plano y aplica la misma unicidad, ignorando `subcategory_id`.

## Differences vs Category

- Subcategoría exige una Categoría padre; Categoría no tiene padre funcional.
- Create permite crear varias subcategorías en una sola transacción. Esta colección repetible no se reemplazó por un formulario simple.
- Edit actualiza un solo registro y usa `subcategory_id`, `category_id`, `name` y `description`.
- El listado añade la columna Categoría, alineada a la izquierda como texto descriptivo.
- Select2 sigue siendo parte del contrato de Create/Edit y usa el tema Bootstrap 4; no se sustituyó el plugin.
- La eliminación en cascada empieza en MaterialType y continúa hacia Subtype.

## Parent Relation

`Category` es el padre obligatorio. `SubcategoryController::create` y `edit` consultan `Category::query()->orderBy('name')->get()`, por lo que el selector recibe únicamente categorías no eliminadas del tenant vigente mediante `BelongsToTenant`.

`StoreSubcategoryRequest` y `UpdateSubcategoryRequest` validan `category_id` con `tenant_id` actual y `deleted_at IS NULL`. El controller vuelve a resolver el padre con `Category::findOrFail`, proporcionando una segunda barrera tenant. Edit marca el valor persistido como seleccionado.

No existe recuperación `old()` porque los formularios envían AJAX y la vista no se recarga ante validación; los valores permanecen en el DOM. Si la categoría padre deja de estar disponible, la validación rechaza el envío. La cascada de Category elimina lógicamente sus Subcategorías, de modo que un registro hijo normal no debería permanecer editable sin padre.

## Permissions

- `list_subcategory`: index y endpoint DataTables.
- `create_subcategory`: create, store y store individual.
- `update_subcategory`: edit/update y acción `Editar`.
- `destroy_subcategory`: delete individual, selección y acción `Eliminar`.

Las columnas de selección/acciones se generan según permisos efectivos. El endpoint bulk no se considera autorizado aunque el usuario tenga `destroy_subcategory`, porque la ruta carece de middleware explícito.

## Multitenancy

`Subcategory`, `Category`, `MaterialType` y `Subtype` usan `BelongsToTenant`. El listado valida además que `subcategories.tenant_id = categories.tenant_id`. Requests, resolución de padre, edit, update, delete y bulk operan bajo el tenant vigente.

El QA confirmó conjuntos separados: EDESCE expuso 17 subcategorías y 7 categorías padre; Gamarra expuso 2 subcategorías y 2 categorías padre. Los nombres observados no se cruzaron entre tenants. No se enviaron formularios ni operaciones destructivas y la sesión quedó restaurada en Gamarra.

## Delete Behavior

`Subcategory` usa `SoftDeletes` y `CascadeSoftDeletes` sobre `materialTypes`; `MaterialType` continúa la cascada sobre `subtypes`. `destroy` y `deleteMultiple` llaman `delete()` dentro de una transacción.

No hay bloqueo ni mensaje de dominio para Subcategorías relacionadas con Materiales. Tanto Subcategory como MaterialType y Subtype poseen relaciones directas con Material, por lo que el efecto histórico de ocultar la jerarquía mientras Material conserva sus claves no está definido.

Estado: `BACKEND SYNC OPEN`.

## Bulk Delete

El endpoint correcto es `/dashboard/subcategory/delete-multiple`, recibe `ids[]` y consulta con TenantScope. Sin embargo:

- no declara `permission:destroy_subcategory`;
- usa `Request` sin Form Request tenant-aware;
- ejecuta las mismas cascadas sin política confirmada para Materiales.

El frontend conserva selección y endpoint correcto, pero el botón permanece `disabled`, `aria-disabled="true"` y `data-backend-sync-open="true"`. El handler aborta antes de confirmar o enviar red. No se ejecuta bulk delete.

## Backend Sync Notes

### [OPEN] Autorización bulk

- **Endpoint:** `POST /dashboard/subcategory/delete-multiple`.
- **Impacto:** TenantScope filtra datos, pero no sustituye autorización destructiva.
- **Backend requerido:** añadir `permission:destroy_subcategory` y validar `ids[]` con un request dedicado.
- **Frontend puede continuar:** Sí; acción inerte.

### [OPEN] Política referencial

- **Archivos:** `SubcategoryController`, `Subcategory`, `MaterialType`, `Subtype`.
- **Impacto:** la cascada lógica no comprueba Materiales asociados.
- **Backend requerido:** definir bloqueo, preservación histórica o desvinculación y devolver mensajes estables.
- **Frontend puede continuar:** Sí para navegación y confirmación; no para QA destructivo.

### [OPEN] Store individual

- **Archivos:** `StoreSubcategoryIndividualRequest` y `subcategory.store.individual`.
- **Impacto:** el request individual tiene `authorize() = false`, pero el endpoint real no lo usa y delega al request de colección.
- **Backend requerido:** decidir si se elimina la clase muerta o se formaliza un contrato individual sin romper Material Create/Edit.
- **Frontend puede continuar:** Sí; se conserva el endpoint y payload actuales.

## Revisit Triggers

1. El bulk obtiene middleware y request validador.
2. Se define la política de Materiales relacionados.
3. El store individual adopta un payload realmente individual.
4. Create deja de aceptar múltiples subcategorías.
5. La relación padre pasa de tenant a company scope.
6. Select2 o DataTables cambian de versión o contrato.
7. Se modifica el patrón compartido Material Configuration CRUD.
