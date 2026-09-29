# REAL NEXT 5.7 — Material Configuration CRUD: Tallas

## Current Contract

Tallas es un CRUD simple con páginas separadas para Create/Edit y un listado AJAX propio. No usa DataTables. El adapter existente se conserva: el navegador envía `page`, `search` y `per_page`; Laravel devuelve el paginador estándar con `data`, `total`, `from`, `to`, `current_page` y `last_page`.

| Operación | Método y endpoint | Route name | Permiso | Payload |
| --- | --- | --- | --- | --- |
| Listado | `GET /dashboard/tallas` | `talla.index` | `list_talla` | — |
| Datos paginados | `GET /dashboard/all/tallas` | `talla.data` | `list_talla` | `page`, `search`, `per_page` |
| Crear | `GET /dashboard/crear/talla` | `talla.create` | `create_talla` | — |
| Guardar | `POST /dashboard/talla/store` | `talla.store` | `create_talla` | `name`, `short_name`, `description`, CSRF |
| Editar | `GET /dashboard/editar/talla/{id}` | `talla.edit` | `update_talla` | `id` en URL |
| Actualizar | `POST /dashboard/talla/update` | `talla.update` | `update_talla` | `talla_id`, `name`, `short_name`, `description`, CSRF |
| Eliminar | `POST /dashboard/talla/destroy` | `talla.destroy` | `destroy_talla` | `talla_id`, CSRF |
| Bulk delete | `POST /dashboard/talla/delete-multiple` | `talla.deleteMultiple` | `destroy_talla` | `ids[]`, CSRF |

`name` es obligatorio, string, máximo 191 y único por tenant entre registros no eliminados. `short_name` es opcional y máximo 191; `description` es opcional y máximo 255. Update y delete validan `talla_id` contra el tenant vigente y excluyen soft-deleted.

El endpoint de datos limita `per_page` a 10, 25 o 50, busca sobre nombre, nombre corto y descripción, y ordena por nombre ascendente.

## Differences vs Previous CRUDs

- Talla no usa DataTables 1.10.20. Su búsqueda, cantidad, carga, resumen, empty state y paginación se renderizan con el adapter AJAX propio.
- Incorpora `short_name`, usado como abreviatura visible y como insumo para la identidad/SKU de variantes.
- El listado mantiene exactamente la API paginada de Laravel; Venti Next sólo aporta markup y clases compartidas.
- El delete individual ya implementa una política de dependencia explícita sobre Variantes.
- El bulk precomprueba variantes y evita eliminación parcial, pero usa `Request` genérico.

## Permissions

| Superficie | Contrato |
| --- | --- |
| Index y datos AJAX | `list_talla` |
| Create/store | `create_talla` |
| Edit/update | `update_talla` |
| Delete individual | `destroy_talla` |
| Selección y bulk | `destroy_talla`; ejecución frontend inerte por Backend Sync Open |
| Botón auxiliar en Material Create/Edit | `create_talla` |

Las tres rutas visuales `talla.index`, `talla.create` y `talla.edit` están en la allowlist de Venti Next. El menú padre de Materiales incluye `list_talla` y las vistas activan `activeTalla` junto con el hijo correspondiente.

## Multitenancy

`Talla` usa `BelongsToTenant` y `SoftDeletes`. TenantScope cubre listado, edit, update, delete y bulk; store asigna `tenant_id` automáticamente. Los Form Requests de store/update/delete validan unicidad o existencia con `tenant_id`.

QA seguro de sólo lectura:

- **EDESCE / tenant 1:** 13 tallas activas; entre ellas `TALLA 40`, `TALLA L`, `256 GB` y `1 TB`.
- **Gamarra / tenant 3:** 1 talla activa, `TALLA L`.
- Variantes con talla: 38 en EDESCE y 2 en Gamarra.
- No existen materiales activos con el campo legacy `materials.talla_id` poblado.

Los conjuntos son distintos por tenant. El catálogo es tenant-scoped, no company-scoped ni branch-scoped; Create/Edit no exponen `tenant_id`.

## Integration with Material Create/Edit

Material Create/Edit consulta `Talla::orderBy('name')` bajo TenantScope cuando el campo `talla` está habilitado. El selector conserva:

- `id="talla"`;
- `name="talla[]"`;
- Select2 múltiple;
- `option.value = talla.id`;
- `data-short-name = talla.short_name`.

El botón auxiliar `+` sólo se renderiza con `create_talla`. El modal publica en `talla.store`, conserva CSRF y los campos `name`, `short_name` y `description`. Tras una creación exitosa, añade la opción seleccionada sin recargar y propaga `data-short-name`, por lo que la generación de variantes puede reutilizar inmediatamente la abreviatura.

Store/Update de Material validan los `talla_id` de variantes contra el tenant vigente y bloquean combinaciones duplicadas de talla/color. El aislamiento es correcto.

La lectura del catálogo dentro de Material Create/Edit depende del permiso de la pantalla Material y no exige `list_talla`. Esto queda como decisión de autorización compuesta en Backend Sync.

## Delete Behavior

`TallaController::destroy` recibe un `DeleteTallaRequest` tenant-aware. Antes del soft delete comprueba `variants()->exists()`; si la talla está en uso responde 422 y no elimina.

La FK `variants.talla_id -> tallas.id` declara `ON DELETE RESTRICT`. StockItems, niveles de inventario, precios e historial no guardan `talla_id` directamente: quedan relacionados a través de Variant/StockItem. La política actual preserva esa cadena al bloquear el borrado de cualquier talla usada por una variante.

Existe un campo legacy nullable `materials.talla_id`. La base actual no contiene valores activos en ese campo y el flujo vigente usa `variants.talla_id`, pero `Material::talla()` todavía apunta erróneamente a `Quality`. Es deuda backend y un trigger de revisión si reaparece información legacy.

## Bulk Delete

El endpoint:

- está protegido por `destroy_talla`;
- consulta `Talla` bajo TenantScope;
- precomprueba dependencias con Variant antes de borrar;
- evita eliminación parcial;
- aplica soft delete dentro de una transacción.

Riesgos abiertos:

- usa `Request` genérico, no un Form Request tenant-aware para cada `ids.*`;
- IDs inexistentes o pertenecientes a otro tenant se omiten silenciosamente;
- no devuelve un resultado diferenciado cuando el conjunto solicitado no coincide con el conjunto cargado.

El frontend conserva checkboxes y endpoint, pero el botón queda `disabled`, `aria-disabled="true"` y `data-backend-sync-open="true"`. No se emite ninguna petición bulk.

## Backend Sync Notes

### [OPEN] Validación dedicada de bulk

Crear un request que valide `ids` e `ids.*` contra el tenant actual y defina la respuesta para IDs inexistentes, duplicados o ajenos.

### [OPEN] Campo y relación legacy de Material

Confirmar la retirada de `materials.talla_id` o corregir `Material::talla()` si el campo vuelve a utilizarse. Hoy no hay registros activos que dependan de él.

### [OPEN] Permiso de lectura dentro de Material

Definir si Material Create/Edit pueden listar Tallas únicamente con permisos de Material o si también deben exigir `list_talla`. El contrato actual permite el acceso compuesto y conserva TenantScope.

### [RESOLVED] Dependencia de Variantes

Delete individual y bulk bloquean tallas usadas por variantes; la FK añade una segunda barrera referencial.

## Revisit Triggers

1. Bulk obtiene un Form Request tenant-aware.
2. Se reutiliza o elimina definitivamente `materials.talla_id`.
3. Cambia la política de permiso compuesto en Material Create/Edit.
4. `short_name` deja de participar en la identidad de variantes.
5. El catálogo cambia de tenant scope a company/branch scope.
6. Cambia la forma del paginador JSON o se incorpora DataTables.
7. Se modifica la política histórica de Variantes o StockItems.
