# REAL NEXT 5.11 — Material Configuration CRUD: Tipo de retacería

## Current Contract

Tipo de retacería es un catálogo especial tenant-scoped con dimensiones base. El modelo real es `Typescrap` y usa `SoftDeletes` junto con `BelongsToTenant`.

| Operación | Método y ruta | Nombre | Permiso | Contrato |
| --- | --- | --- | --- | --- |
| Index | `GET /dashboard/Retacerías` | `typescrap.index` | `list_typeScrap` | Blade `typeScrap.index` |
| Datos | `GET /dashboard/all/typescraps` | Sin nombre | `list_typeScrap` | DataTables JSON: `id`, `name`, `length`, `width` |
| Create | `GET /dashboard/crear/retaceria` | `typescrap.create` | `create_typeScrap` | Página separada |
| Store | `POST /dashboard/typescrap/store` | `typescrap.store` | `create_typeScrap` | `name`, `length`, `width`; respuesta `data` para Material |
| Edit | `GET /dashboard/editar/retaceria/{id}` | `typescrap.edit` | `update_typeScrap` | Resolución bajo TenantScope |
| Update | `POST /dashboard/typescrap/update` | `typescrap.update` | `update_typeScrap` | `typeScrap_id`, `name`, `length`, `width` |
| Delete | `POST /dashboard/typescrap/destroy` | `typescrap.destroy` | `destroy_typeScrap` | Soft delete bloqueado si existen Materiales |
| Bulk delete | `POST /dashboard/typescrap/delete-multiple` | Sin nombre | **Sin permiso específico** | `ids[]` mediante `Request` genérico |

`name` es requerido, máximo 191 y único entre registros activos del tenant. `length` y `width` son requeridos, numéricos y admiten el rango `0..99999.99`. Update y delete validan `typeScrap_id` dentro del tenant vigente.

El listado conserva DataTables 1.10.x con colección tenant completa; búsqueda, orden, selector de cantidad y paginación ocurren en el navegador.

## Differences vs Previous CRUDs

- Es un CRUD tipo C porque `length` y `width` tienen semántica operativa; no son metadatos decorativos.
- No posee relación padre ni Select2 en sus páginas administrativas.
- Material e Item conservan referencias propias a `typescraps`.
- Item persiste snapshots de `length`, `width` y `typescrap_id` utilizados por flujos de entrada, salida, retazos y cálculos porcentuales.
- Delete individual ya implementa un bloqueo explícito por Material, a diferencia de varios catálogos anteriores.
- Visualmente no introduce una familia nueva: reutiliza literalmente Material Configuration CRUD y sólo añade dos controles numéricos en el grid aprobado.

## Functional Impact on Materials/Items

`Material` guarda `typescrap_id`. `Item` guarda `material_id`, `typescrap_id`, `length`, `width`, porcentaje, estado y relaciones con entradas/salidas.

Actualizar nombre o dimensiones desde `TypescrapController::update` modifica únicamente el catálogo; no existe cascada hacia Materiales o Items existentes. Por tanto, un Item ya persistido conserva su snapshot dimensional.

Material Edit contiene una regla distinta: si al guardar cambia `material.typescrap_id` a un valor no nulo, actualiza `typescrap_id`, `length` y `width` de los Items del Material cuyo `state_item` sea `entered` o `exited`. No actualiza explícitamente Items `scraped` o `reserved`. La selección del control, por sí sola, no ejecuta red ni modifica Items; el efecto aparece únicamente al guardar Material Edit.

Los flujos de entradas crean Items copiando las dimensiones actuales del Tipo del Material. Otros flujos de retacería usan tanto el snapshot del Item como dimensiones actuales del catálogo para porcentajes. Esta convivencia de valores históricos y actuales necesita una política explícita antes de evolucionar backend.

Estado: `BACKEND SYNC OPEN` por semántica histórica y por la actualización selectiva de Items al guardar Material Edit.

## Permissions

- `list_typeScrap`: index y endpoint DataTables.
- `create_typeScrap`: create/store y botón auxiliar en Material Create/Edit.
- `update_typeScrap`: edit/update y acción Editar.
- `destroy_typeScrap`: delete individual, selección y acción Eliminar.

El bulk no posee middleware `destroy_typeScrap`; por ello la UI sólo lo muestra bajo ese permiso, pero lo mantiene deshabilitado e inerte.

## Multitenancy

`Typescrap`, `Material` e `Item` usan `BelongsToTenant`. Requests, listados y lookups principales operan bajo TenantScope. El catálogo no es company-scoped; Items sí conservan además `company_id` por su contrato de inventario.

Datos read-only auditados:

- EDESCE, tenant 1: dos tipos activos (`TUBOS`, `PLANCHA`), ambos usados por un Material.
- Gamarra, tenant 3: catálogo vacío.
- No existen referencias cruzadas Material–Typescrap ni Item–Typescrap entre tenants.
- Actualmente no existen Items con `typescrap_id` en los fixtures compartidos.

La ausencia de registros en Gamarra es un estado vacío válido y no se reemplaza con datos globales o de EDESCE.

## Integration with Material Create/Edit

El catálogo sólo se carga cuando `typescrap` está presente en `$enabled`, configuración company-scoped. Create/Edit conservan:

- selector `#typescrap`, name `typescrap` y Select2;
- opciones obtenidas con `Typescrap::orderBy('name')` bajo TenantScope;
- botón auxiliar `+` protegido por `create_typeScrap`;
- modal `#modalTypescrap` y formulario `#formCreateTypescrap`;
- campos `name`, `width`, `length` y CSRF;
- POST a `typescrap.store`;
- respuesta `data.id`, `data.name`, `data.width`, `data.length`;
- inserción y selección de la nueva opción sin recargar.

Material Create/Edit puede leer el catálogo con permisos de Material sin exigir `list_typeScrap`; backend debe confirmar si ésa es la autorización compuesta deseada. Seleccionar otro tipo en Edit no tiene efectos colaterales sin Guardar.

## Delete Behavior

Delete individual:

1. valida `typeScrap_id` contra el tenant actual;
2. resuelve el registro bajo TenantScope;
3. bloquea con 422 si `materials()->exists()`;
4. aplica soft delete dentro de una transacción cuando no existen Materiales.

El controller no comprueba directamente Items. Normalmente un Item pertenece a un Material, pero la base permite conservar `items.typescrap_id` y la relación normal no usa `withTrashed()`. Si existiera un Item histórico apuntando al Tipo sin un Material activo asociado, el catálogo podría archivarse y esa relación dejaría de resolverlo. Esto queda abierto como política histórica.

## Bulk Delete

El endpoint carga sólo registros visibles por TenantScope, precomprueba Materiales para todo el conjunto y evita eliminación parcial. Sin embargo:

- no tiene middleware `permission:destroy_typeScrap`;
- usa `Request` genérico;
- sólo valida que `ids` exista y sea array;
- no valida cada `ids.*` ni detecta IDs omitidos por tenant;
- tampoco comprueba Items directamente.

El control permanece `disabled`, `aria-disabled="true"` y `data-backend-sync-open="true"`; no se envía petición.

## Backend Sync Notes

`BACKEND SYNC OPEN`

1. Proteger `/dashboard/typescrap/delete-multiple` con `permission:destroy_typeScrap` y un FormRequest tenant-aware para `ids[]`.
2. Definir si editar dimensiones debe preservar snapshots de Items o propagar cambios, y por qué Material Edit actualiza sólo estados `entered` y `exited`.
3. Confirmar el tratamiento de Items históricos/directos antes de archivar un Tipo sin Materiales activos.
4. Definir si Material Create/Edit deben exigir `list_typeScrap` además del permiso de la pantalla Material.
5. Documentar una unidad de medida contractual para `length` y `width`; el código actual conserva valores numéricos sin unidad explícita en el catálogo.

## Revisit Triggers

- Bulk obtiene permiso y request dedicado.
- Se aprueba la política histórica de dimensiones e Items.
- Material Edit cambia su propagación a Items.
- Se incorpora unidad de medida dimensional explícita.
- El catálogo pasa a company scope.
- Cambian el payload o la respuesta compartida con Material Create/Edit.
- DataTables o Material Configuration CRUD cambian de contrato.

## Implementation Result

- `typescrap.index`, `typescrap.create` y `typescrap.edit` se incorporaron a la allowlist Venti Next.
- Index reutiliza Operational List, DataTables, Result Summary, alineación transversal, Row Actions Dropdown, paginación y empty state.
- Create/Edit reutilizan form surface con el grid aprobado de tres campos.
- Bulk inseguro permanece deshabilitado.
- No se modificó backend, persistencia ni CSS.

Estado: `APPROVED WITH BACKEND SYNC OPEN`.
