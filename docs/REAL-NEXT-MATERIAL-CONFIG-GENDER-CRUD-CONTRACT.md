# REAL NEXT 5.6 — Material Configuration CRUD: Género

## Current Contract

Género conserva páginas separadas, formularios AJAX y DataTables 1.10.20 en cliente.

| Operación | Método y endpoint | Route name | Permiso | Payload |
| --- | --- | --- | --- | --- |
| Listado | `GET /dashboard/generos` | `genero.index` | `list_genero` | — |
| Datos DataTables | `GET /dashboard/all/generos` | — | `list_genero` | — |
| Crear | `GET /dashboard/crear/genero` | `genero.create` | `create_genero` | — |
| Guardar | `POST /dashboard/genero/store` | `genero.store` | `create_genero` | `name`, `description`, CSRF |
| Editar | `GET /dashboard/editar/genero/{id}` | `genero.edit` | `update_genero` | `id` en URL |
| Actualizar | `POST /dashboard/genero/update` | `genero.update` | `update_genero` | `genero_id`, `name`, `description`, CSRF |
| Eliminar | `POST /dashboard/genero/destroy` | `genero.destroy` | `destroy_genero` | `genero_id`, CSRF |
| Bulk delete | `POST /dashboard/genero/delete-multiple` | — | `destroy_genero` | `ids[]`, CSRF |

`name` es obligatorio, string, máximo 191 y único por tenant entre registros no eliminados. `description` es opcional, string y máximo 255. Update y delete validan `genero_id` contra el tenant vigente y excluyen soft-deleted.

`GET /dashboard/all/generos` devuelve DataTables JSON con `id`, `name` y `description`, ordenado alfabéticamente.

## Differences vs Previous CRUDs

- Es un CRUD simple tipo A: no tiene relación padre ni select dependiente.
- A diferencia de Subtipo, no necesita Select2 en Create/Edit.
- El nombre admite 191 caracteres; la descripción mantiene 255.
- Bulk sí declara `permission:destroy_genero`, a diferencia de varios catálogos anteriores, pero sigue sin request dedicado ni política de dependencias.
- Género es consumido directamente por Material Create/Edit y participa en la generación de SKU de variantes.
- El módulo histórico Cédula/`Warrant` es independiente. Las referencias `update_warrant`, `destroy_warrant` y funciones `*Warrant` del JS de Género eran contaminación de copia, no parte del contrato.

## Permissions Audit

| Superficie | Contrato real | Estado |
| --- | --- | --- |
| Index y datos AJAX | `list_genero` en ambas rutas | **RESOLVED** |
| Create/store | `create_genero` en rutas, CTA y botón auxiliar de Material Create/Edit | **RESOLVED** |
| Edit/update | `update_genero` en rutas, Blade y acción JS | **RESOLVED** |
| Delete individual | `destroy_genero` en ruta, Blade, selección y acción JS | **RESOLVED** |
| Bulk delete | `destroy_genero` en ruta y Blade; validación y dependencias pendientes | **BACKEND SYNC OPEN** |
| JS legacy | comprobaba `update_warrant` y `destroy_warrant` | **WRONG PERMISSION → RESOLVED** |
| Guards Blade legacy | Create y modal Delete tenían `@can` comentados | **WRONG PERMISSION → RESOLVED** |
| Menú padre Configuraciones | no incluía `list_genero` en su `@canany` | **STILL OPEN → RESOLVED** |
| Permisos directos de usuario | el controller entrega sólo `getPermissionsViaRoles()` | **RESOLVED en frontend**: DataTables lee flags generados por Gate/`@can`, no infiere columnas del array de roles |

No queda uso de permisos `warrant` dentro de las vistas o JS de Género. `gender` sólo existe como campo independiente de Worker y no interviene en este catálogo.

## Multitenancy

`Genero` usa `BelongsToTenant` y `SoftDeletes`. TenantScope se aplica a index data, edit, update, delete y bulk; store asigna `tenant_id` automáticamente. Los Form Requests de store/update/delete validan unicidad o existencia con `tenant_id`.

QA seguro sin persistencia:

- **EDESCE (tenant 1):** `HOMBRE`, `MUJER`.
- **Gamarra (tenant 3):** `HOMBRE`, `UNISEX`.
- La lista renderizada en Gamarra mostró únicamente sus dos registros.
- Consultas mediante el mismo modelo y TenantScope devolvieron conjuntos distintos para EDESCE y Gamarra.
- Create/Edit no exponen ni envían `tenant_id`.

El catálogo es tenant-scoped, no company-scoped ni branch-scoped.

## Integration with Material Create/Edit

Material Create/Edit reciben `$generos` desde `MaterialController`, que consulta `Genero::orderBy('name')` bajo TenantScope cuando el campo `genero` está habilitado. El select conserva `id` como value y `name` como texto; el payload material usa el campo `genero`.

El modal auxiliar:

- publica en `genero.store`;
- conserva `name`, `description` y CSRF;
- espera `data.id` y `data.description` en la respuesta;
- añade la opción creada al select sin recargar;
- sólo expone el botón `+` con `create_genero` tanto en Create como en Edit;
- el endpoint vuelve a exigir `create_genero`.

La lectura del catálogo dentro de Material Create/Edit está autorizada por los permisos de la pantalla Material, no por `list_genero`. Estado: **BACKEND SYNC OPEN** para confirmar si este acceso compuesto es la política deseada; el aislamiento tenant sí está resuelto.

## Delete Behavior

`GeneroController::destroy` resuelve el registro bajo TenantScope y ejecuta soft delete dentro de una transacción. No comprueba materiales relacionados.

`Material` es la única dependencia directa mediante `materials.genero_id`. StockItems y Variantes no almacenan `genero_id`; su relación es indirecta a través del Material. La FK es nullable y no declara `ON DELETE`; el soft delete no rompe la FK, pero la relación normal de Material deja de resolver el Género eliminado porque no usa `withTrashed()`.

Estado: **BACKEND SYNC OPEN**. Backend debe definir bloqueo, preservación histórica o desvinculación deliberada. No se ejecutó delete durante QA.

## Bulk Delete

El endpoint recibe `ids[]`, comprueba únicamente que exista y sea array, consulta los registros bajo TenantScope y aplica soft delete dentro de una transacción.

Aspectos resueltos:

- middleware explícito `permission:destroy_genero`;
- endpoint y payload correctos;
- consulta tenant-scoped.

Riesgos abiertos:

- `Request` genérico sin validación dedicada de cada ID;
- IDs inexistentes se omiten silenciosamente;
- sin política ni comprobación de Materiales relacionados;
- no hay respuesta diferenciada por dependencia.

El frontend conserva selección y endpoint, pero el botón está `disabled`, `aria-disabled="true"` y `data-backend-sync-open="true"`. No se envía petición.

## Backend Sync Notes

### [OPEN] Política referencial de Materiales

Definir si un Género usado debe bloquearse, permanecer resoluble como histórico o desvincularse. Afecta delete individual y bulk.

### [OPEN] Validación dedicada de bulk

Crear un request tenant-aware para `ids[]` y definir el resultado esperado cuando parte del conjunto no existe o tiene dependencias.

### [OPEN] Permiso de lectura dentro de Material

Definir si Material Create/Edit pueden listar Géneros sólo con sus permisos de Material o si también requieren `list_genero`. El contrato actual permite la lectura compuesta y mantiene TenantScope.

### [RESOLVED] Nomenclatura de permisos

Routes, Blade, acciones DataTables, menú y botones auxiliares usan `list_genero`, `create_genero`, `update_genero` y `destroy_genero`. Las referencias heredadas a `warrant` fueron eliminadas del frontend de Género.

## Revisit Triggers

1. Se define la política para Materiales que conservan `genero_id`.
2. Bulk obtiene validación dedicada y semántica de dependencias.
3. Se decide exigir o no `list_genero` en Material Create/Edit.
4. El catálogo cambia de tenant scope a company scope.
5. Cambia la respuesta auxiliar `data.id/data.description`.
6. Se incorporan campos adicionales a Género.
7. DataTables o el patrón Material Configuration CRUD cambian de contrato.
