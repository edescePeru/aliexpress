# REAL NEXT 5.4 — Material Configuration CRUD: Tipo Material

## Current Contract

Tipo Material conserva páginas separadas, formularios AJAX y DataTables 1.10.20 en cliente.

| Operación | Método y endpoint | Route name | Permiso | Payload |
| --- | --- | --- | --- | --- |
| Listado | `GET /dashboard/TiposMateriales` | `materialtype.index` | `list_materialType` | — |
| Datos DataTables | `GET /dashboard/all/materialtypes` | — | `list_materialType` | — |
| Crear | `GET /dashboard/crear/tipomaterial` | `materialtype.create` | `create_materialType` | — |
| Guardar | `POST /dashboard/materialtype/store` | `materialtype.store` | `create_materialType` | `subcategory_id`, `name`, `description`, CSRF |
| Editar | `GET /dashboard/editar/tipomaterial/{id}` | `materialtype.edit` | `update_materialType` | `id` en URL |
| Actualizar | `POST /dashboard/materialtype/update` | `materialtype.update` | `update_materialType` | `materialtype_id`, `subcategory_id`, `name`, `description`, CSRF |
| Eliminar | `POST /dashboard/materialtype/destroy` | `materialtype.destroy` | `destroy_materialType` | `materialtype_id`, CSRF |
| Tipos por Subcategoría | `GET /dashboard/get/types/{subcategory_id}` | — | `list_materialType` | `subcategory_id` en URL |
| Bulk delete | `POST /dashboard/materialtype/delete-multiple` | — | **Sin permiso explícito** | `ids[]`, CSRF |

Store/Update exigen `subcategory_id` tenant-aware, `name` requerido de máximo 255 y único por `tenant_id + subcategory_id`, y `description` opcional de máximo 255. Delete valida `materialtype_id` dentro del tenant actual.

## Differences vs Subcategory

- Create genera un solo Tipo Material; no acepta la colección repetible `subcategories[]`.
- El padre obligatorio es `Subcategory`, no `Category`.
- El payload es plano en Create y Edit.
- El listado añade la Subcategoría como relación descriptiva.
- La jerarquía se presenta como `Categoría → Subcategoría → Tipo Material`, pero Categoría se deriva de la Subcategoría y no se duplica en el payload.
- Se corrigió un defecto Legacy en Edit: Descripción mostraba `materialtype->name`; ahora usa `materialtype->description`.

## Parent Relation

`Subcategory` es el padre obligatorio. Create/Edit consultan `Subcategory::query()->orderBy('name')->get()` y TenantScope limita las opciones al tenant vigente. Store/Update validan `subcategory_id` con `tenant_id` y `deleted_at IS NULL`, y el controller vuelve a resolver el padre con `Subcategory::findOrFail`.

Edit selecciona el valor persistido. Los formularios son AJAX: ante validación fallida la vista no se recarga y los valores permanecen en el DOM, por lo que no existe recuperación `old()` en el Blade actual. Si el padre deja de estar disponible, la validación tenant-aware rechaza el envío; además, el borrado lógico de Subcategory cascadearía al Tipo Material.

## Permissions

- `list_materialType`: index, datos DataTables y `/get/types`.
- `create_materialType`: create/store y CTA principal.
- `update_materialType`: edit/update y acción `Editar`.
- `destroy_materialType`: destroy individual, selección y acción `Eliminar`.

El bulk no se considera autorizado porque su ruta no declara `permission:destroy_materialType`.

## Multitenancy

`MaterialType`, `Subcategory` y `Subtype` usan `BelongsToTenant`. Controller, relaciones y Form Requests operan bajo TenantScope. `tenant_id` no forma parte del payload del navegador.

QA seguro confirmado sin persistencia:

- **Gamarra:** 2 Tipos (`ACERO`, `PLASTICO`) y 2 opciones de Subcategoría (`METALICO`, `SIMPLE`).
- **EDESCE:** 4 Tipos (`LIQUIDO`, `METALICO`, `TELA`, `TIPO TEST`) y 17 opciones de Subcategoría.
- Los conjuntos son distintos y no hubo registros cruzados entre tenants.
- Create/Edit resolvieron sus opciones desde el contexto autenticado; `tenant_id` nunca apareció en el payload del navegador.

## Get Types Endpoint

Estado: `RESOLVED`.

`GET /dashboard/get/types/{subcategory_id}` usa actualmente `permission:list_materialType`, no `destroy_materialType`. El controller:

1. resuelve la Subcategoría mediante `Subcategory::findOrFail` bajo TenantScope;
2. consulta MaterialType por `subcategory_id` bajo su propio TenantScope;
3. ordena por nombre;
4. devuelve elementos `{ id, type }`.

Material Create/Edit consume este endpoint para poblar el Tipo Material dependiente de la Subcategoría. No se modificó su ruta, permiso ni respuesta.

QA de integración confirmado desde Material Create bajo Gamarra: Categoría `TOMATODOS` cargó Subcategoría `METALICO` y ésta pobló únicamente `ACERO` y `PLASTICO`. La navegación directa a la respuesta JSON fue bloqueada por el navegador de QA, por lo que la comprobación se hizo mediante el consumidor contractual real, sin guardar el formulario.

## Delete Behavior

`MaterialType` usa `SoftDeletes` y `CascadeSoftDeletes` sobre `subtypes`. Delete individual llama `delete()` dentro de una transacción. No bloquea Tipos relacionados con Materiales ni define cómo conservar la clasificación histórica de éstos.

Estado: `BACKEND SYNC OPEN`.

## Bulk Delete

El endpoint correcto es `/dashboard/materialtype/delete-multiple`. Recibe `ids[]`, consulta Tipos bajo TenantScope y elimina manualmente los Subtipos antes de eliminar cada Tipo; el modelo ya declara además cascada sobre `subtypes`.

Riesgos abiertos:

- sin middleware `destroy_materialType`;
- `Request` sin validador dedicado para `ids[]`;
- cascada manual potencialmente redundante con `CascadeSoftDeletes`;
- ausencia de política para Materiales relacionados.

El frontend conserva selección y mapping correcto, pero el botón queda `disabled`, `aria-disabled="true"` y protegido por `data-backend-sync-open="true"`. No se envía petición.

## Backend Sync Notes

### [OPEN] Autorización y validación bulk

- **Endpoint:** `POST /dashboard/materialtype/delete-multiple`.
- **Backend requerido:** middleware `permission:destroy_materialType` y request dedicado tenant-aware.
- **Frontend puede continuar:** Sí; bulk inerte.

### [OPEN] Política referencial

- **Archivos:** `MaterialTypeController`, `MaterialType`, `Subtype` y relaciones con Material.
- **Backend requerido:** definir bloqueo, preservación histórica o desvinculación para Materiales relacionados.
- **Frontend puede continuar:** Sí para lectura/confirmación; no para QA destructivo.

### [OPEN] Doble estrategia de cascada

- **Endpoint:** `deleteMultiple`.
- **Impacto:** elimina Subtipos manualmente y luego vuelve a aplicar la cascada declarada por el modelo.
- **Backend requerido:** confirmar una única estrategia deliberada.
- **Frontend puede continuar:** Sí.

### [RESOLVED] Permiso de `/get/types`

- **Contrato final:** `permission:list_materialType`, Subcategoría y Tipos tenant-scoped, respuesta `{ id, type }`.
- **Frontend puede continuar:** Sí.

## Revisit Triggers

1. Bulk obtiene permiso y validación dedicada.
2. Se define la política de Materiales relacionados.
3. Se unifica la estrategia de cascada.
4. `/get/types` cambia permiso o formato.
5. El padre pasa a company scope.
6. Select2/DataTables cambian de contrato.
7. Se modifica el patrón Material Configuration CRUD.
