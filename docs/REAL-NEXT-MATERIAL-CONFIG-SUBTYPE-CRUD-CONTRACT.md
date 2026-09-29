# REAL NEXT 5.5 — Material Configuration CRUD: Subtipo

## Current Contract

Subtipo conserva páginas separadas, formularios AJAX y DataTables 1.10.20 en cliente.

| Operación | Método y endpoint | Route name | Permiso | Payload |
| --- | --- | --- | --- | --- |
| Listado | `GET /dashboard/Subtipos` | `subtype.index` | `list_subType` | — |
| Datos DataTables | `GET /dashboard/all/subtypes` | — | `list_subType` | — |
| Crear | `GET /dashboard/crear/subtipo` | `subtype.create` | `create_subType` | — |
| Guardar | `POST /dashboard/subtype/store` | `subtype.store` | `create_subType` | `material_type_id`, `name`, `description`, CSRF |
| Editar | `GET /dashboard/editar/subtipo/{id}` | `subtype.edit` | `update_subType` | `id` en URL |
| Actualizar | `POST /dashboard/subtype/update` | `subtype.update` | `update_subType` | `subtype_id`, `material_type_id`, `name`, `description`, CSRF |
| Eliminar | `POST /dashboard/subtype/destroy` | `subtype.destroy` | `destroy_subType` | `subtype_id`, CSRF |
| Subtipos por Tipo | `GET /dashboard/get/subtypes/{type_id}` | — | **Sin permiso explícito** | `type_id` en URL |
| Bulk delete | `POST /dashboard/subtype/delete-multiple` | — | **Sin permiso explícito** | `ids[]`, CSRF |

Store/Update exigen `material_type_id` tenant-aware, `name` requerido de máximo 255 y único por `tenant_id + material_type_id`, y `description` opcional de máximo 255. Delete valida `subtype_id` dentro del tenant actual.

## Differences vs Material Type

- El padre obligatorio es `MaterialType`, no `Subcategory`.
- Create y Edit conservan un payload plano; no existe creación múltiple.
- El listado añade el Tipo de material como relación descriptiva.
- Subtipo no declara cascadas hacia otro catálogo hijo.
- El endpoint dependiente devuelve `{ id, subtype }`, mientras `/get/types` devuelve `{ id, type }`.
- La jerarquía visible es `Categoría → Subcategoría → Tipo Material → Subtipo`; Categoría y Subcategoría se derivan del padre y no se duplican en el payload.

## Parent Relation

`MaterialType` es el padre obligatorio. Create/Edit consultan `MaterialType::query()->orderBy('name')->get()` y TenantScope limita las opciones al tenant vigente. Store/Update validan `material_type_id` con `tenant_id` y `deleted_at IS NULL`; el controller vuelve a resolverlo con `MaterialType::findOrFail` como segunda barrera.

Edit selecciona el valor persistido. Los formularios son AJAX y no recargan la vista ante validación 422, por lo que conservan el estado actual del DOM y no dependen de `old()`. Si el padre está eliminado o deja de estar disponible, deja de aparecer en el select y la validación tenant-aware impide enviar esa referencia.

## Permissions

- `list_subType`: index y datos DataTables.
- `create_subType`: create/store y CTA principal.
- `update_subType`: edit/update y acción `Editar`.
- `destroy_subType`: destroy individual, selección y acción `Eliminar`.

El endpoint dependiente y el bulk no tienen middleware de permiso propio. Permanecen dentro del grupo autenticado con `tenant.context`, pero eso no reemplaza autorización de catálogo.

## Multitenancy

`Subtype` y `MaterialType` usan `BelongsToTenant`. Controller, relaciones y Form Requests operan bajo TenantScope; `tenant_id` no forma parte del payload del navegador. El join del listado comprueba además igualdad entre `subtypes.tenant_id` y `material_types.tenant_id`.

QA seguro confirmado sin persistencia:

- **Gamarra:** 2 Subtipos (`INOXIDABLE`, `SIMPLE`) y 2 Tipos padre (`ACERO`, `PLASTICO`).
- **EDESCE:** 4 Subtipos (`BRILLANTE`, `OPACO`, `ALGODON`, `SUBTIPO TEST`) y 4 Tipos padre (`LIQUIDO`, `METALICO`, `TELA`, `TIPO TEST`).
- Los conjuntos son distintos y Create/Edit mostraron únicamente padres del tenant autenticado.
- No hubo registros cruzados ni campos `tenant_id` expuestos en formularios.

## Dependent Endpoints

Estado: `BACKEND SYNC OPEN` por autorización; aislamiento tenant `RESOLVED`.

`GET /dashboard/get/subtypes/{type_id}`:

1. resuelve primero `MaterialType::findOrFail($id)` bajo TenantScope;
2. consulta `Subtype` por `material_type_id` bajo su TenantScope;
3. ordena por nombre;
4. devuelve `{ id, subtype }`;
5. es consumido por Material Create/Edit al cambiar `#material_type`.

QA de integración:

- Gamarra: `TOMATODOS → METALICO → ACERO` devolvió únicamente `INOXIDABLE`.
- EDESCE: `CELULARES → SMARTPHONE → METALICO` devolvió únicamente `BRILLANTE` y `OPACO`.

La URI no declara `permission:list_subType`; backend debe definir si corresponde ese permiso o una autorización compatible con el formulario consumidor.

## Delete Behavior

`Subtype` usa `SoftDeletes`. Delete individual resuelve el registro bajo TenantScope y llama `delete()` dentro de una transacción. No comprueba `materials`, no modifica `StockItem` ni `Variant` directamente y no define una política para los Materiales que conservan `subtype_id`.

La FK de `materials.subtype_id` es nullable pero no declara `ON DELETE`; el soft delete no rompe la FK, aunque la relación normal de Material deja de resolver el padre eliminado porque no usa `withTrashed()`.

Estado: `BACKEND SYNC OPEN`.

## Bulk Delete

`POST /dashboard/subtype/delete-multiple` recibe `ids[]`, sólo comprueba que sea un array, consulta bajo TenantScope y hace soft delete dentro de una transacción.

Riesgos abiertos:

- sin middleware `destroy_subType`;
- `Request` genérico sin validación dedicada de `ids[]`;
- sin política ni comprobación de Materiales relacionados;
- no existe una respuesta diferenciada por dependencia referencial.

El frontend conserva el endpoint correcto y la selección, pero mantiene el botón `disabled`, `aria-disabled="true"` y `data-backend-sync-open="true"`. No se envía petición.

## Backend Sync Notes

### [OPEN] Permiso del endpoint dependiente

- **Endpoint:** `GET /dashboard/get/subtypes/{type_id}`.
- **Impacto:** está autenticado y tenant-scoped, pero no declara permiso de catálogo.
- **Backend requerido:** definir y aplicar `permission:list_subType` o una autorización explícita compatible con Material Create/Edit.
- **Frontend puede continuar:** Sí; la respuesta y el aislamiento actuales son compatibles.

### [OPEN] Autorización y validación bulk

- **Endpoint:** `POST /dashboard/subtype/delete-multiple`.
- **Backend requerido:** middleware `permission:destroy_subType` y request dedicado tenant-aware para `ids[]`.
- **Frontend puede continuar:** Sí; bulk inerte.

### [OPEN] Política referencial

- **Archivos:** `SubtypeController`, `Subtype`, `Material` y FK `materials.subtype_id`.
- **Impacto:** un soft delete puede dejar Materiales apuntando a un Subtipo que la relación normal ya no muestra.
- **Backend requerido:** definir bloqueo, preservación histórica con `withTrashed()` o desvinculación deliberada.
- **Frontend puede continuar:** Sí para lectura y confirmación; no para QA destructivo.

### [RESOLVED] Tenant isolation del endpoint dependiente

- **Contrato actual:** el Tipo padre se resuelve bajo TenantScope y los Subtipos se consultan bajo su propio TenantScope.
- **Frontend puede continuar:** Sí.

## Revisit Triggers

1. `/get/subtypes` obtiene un permiso explícito.
2. Bulk obtiene permiso y validación dedicada.
3. Se define la política de Materiales relacionados.
4. El padre pasa a company scope.
5. Cambia la respuesta `{ id, subtype }`.
6. Select2/DataTables cambian de contrato.
7. Se modifica el patrón Material Configuration CRUD.
