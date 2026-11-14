# REAL NEXT — Warehouse Location Structure — Batch audit

2026-11-14. **BACKEND SYNC REQUIRED para los seis mantenedores legacy**. No se detuvo la exploración en Area: se revisó Location primero y luego Shelf, Level, Container y Position, además de los caminos modernos. No apareció una superficie independiente segura pendiente de integrar. ManagedWarehouse ya integrado permanece intacto. No cambios backend/frontend, stock, migraciones, mutaciones ni commit.

## Current hierarchy

Jerarquía de relaciones real:

```text
Company → Area → Warehouse → Shelf → Level → Container → Position
                   └ branch_id nullable → Branch (general si NULL)
Location → company_id + area_id + warehouse_id + shelf_id + level_id
         + container_id + position_id
InventoryLevel → warehouse_id + location_id
```

Location no es simplemente un hijo de Level: registra referencias explícitas a toda la cadena; Position::locations es hasMany, no se impone 1:1 universal. Modelo actual managed usa una ubicación operativa por almacén, verificada por servicio. No confundir InventoryLevel (saldo de inventario) con Level (nivel físico). AreaWorker es RRHH, no parte de esta jerarquía. No se encontraron entidades alternativas Storage/Estante con otro CRUD contextual: Estante/Anaquel corresponde a Shelf.

## Active vs legacy paths

- **ManagedWarehouseController / ManagedWarehouseService**: camino moderno ya integrado en Empresa/Sucursal. Listado/alta/edición Warehouse, no CRUD separado de cada componente. Crea Area GENERAL y cadena Shelf/Level/Container/Position/Location con contexto explícito al crear un almacén. No se ejecutó ni se reabrió su UI.
- **CompanyInventoryStructureService::provision**: aprovisionamiento de infraestructura al crear Company (TenantCompanyController y Platform/TenantController). Usa company/tenant explícitos incluso con withoutGlobalScopes; crea/reutiliza estructura GENERAL. NO mantenedor GET ni vista para editar niveles. No se ejecutó.
- **DefaultInventoryLocationResolver**: resuelve default por tenant/company/warehouse; lectura para Material, no CRUD.
- **EntryWarehouseResolver**, **MaterialInventoryScope**, **WarehouseAvailabilityService** y servicios Transfers validan sus propias lecturas/referencias contextuales. Su seguridad NO se hereda automáticamente por controllers legacy.
- **LocationController::getJsonLocations**, GET `/dashboard/get/locations`: lookup con company actual + whereHas warehouse misma company y TenantScope. Devuelve id/location/warehouse_id/warehouse/is_default. Es seguro en separación company verificada, pero no una vista ni un CRUD. Sin permission específica en esa ruta: no se certifica autorización granular. No se usa para reemplazar silenciosamente `/all/locations`.
- Controllers Area/Shelf/Level/Container/Position y Location@index/getLocations: caminos legacy con TenantScope y sin frontera company completa. No autoridad contextual de mantenimiento moderno.

## Domain matrix

Views bajo resources/views/inventory; JS public/js/{domain}/index.js. Todos los modelos listados existen.

| Domain | Controller | Model | Views | Permission listado | Scope efectivo legacy | Dataset | Decision |
|---|---|---|---|---|---|---|---|
| Location | LocationController | Location | locations | list_location | tenant, NO company | 7 | BACKEND SYNC REQUIRED |
| Area | AreaController | Area | areas | list_area | tenant, NO company | 6 | BACKEND SYNC REQUIRED |
| Shelf / Anaquel | ShelfController | Shelf | shelves | list_shelf | tenant + warehouse_id recibido, sin ownership company | 7 | BACKEND SYNC REQUIRED |
| Level / Nivel físico | LevelController | Level | levels | list_level | tenant + shelf_id recibido, sin company/cadena validada | 7 | BACKEND SYNC REQUIRED |
| Container | ContainerController | Container | containers | list_container | tenant + level_id recibido, sin company/cadena validada | 7 | BACKEND SYNC REQUIRED |
| Position | PositionController | Position | positions; warehouse_visual auxiliar | list_position | tenant + container_id recibido, sin company/cadena validada | 7 | BACKEND SYNC REQUIRED |
| ManagedWarehouse | ManagedWarehouseController | Warehouse + componentes | managedWarehouses partial | edit_company/edit_branch | tenant + company explícita + branch validada | ya documentado | LEGACY / NOT AUTHORITY: N/A; camino moderno YA INTEGRADO |
| CRUD moderno independiente de componentes | no encontrado | modelos existentes | ninguna | N/A | N/A | N/A | NOT FOUND |

## Routes/permissions

Prefijo `/dashboard`; middleware común auth/check.user.enabled/password.changed/tenant.context. Permisos de mutaciones no implican pertenencia company. No Owner bypass supuesto.

| Domain | List | Create | Edit | Delete | Restore |
|---|---|---|---|---|---|
| Area | list_area | create_area | update_area | destroy_area | inexistente |
| Shelf | list_shelf | create_shelf | update_shelf | destroy_shelf | inexistente |
| Level | list_level | create_level | update_level | destroy_level | inexistente |
| Container | list_container | create_container | update_container | destroy_container | inexistente |
| Position | list_position | create_position | update_position | destroy_position | inexistente |
| Location | list_location | sin ruta real | sin ruta real | sin ruta real | inexistente |

| Domain | GET view (route name) | GET JSON consumido |
|---|---|---|
| Area | areas (area.index) | all/areas |
| Shelf | ver/anaqueles/almacen/{almacen}/area/{area} (shelf.index) | all/shelves/{id_warehouse} |
| Level | ver/niveles/anaquel/{anaquel}/almacen/{almacen}/area/{area} (level.index) | all/levels/{id_shelf} |
| Container | ver/contenedores/nivel/{niveles}/anaquel/{anaqueles}/almacen/{almacen}/area/{area} (container.index) | all/containers/{id_level} |
| Position | ver/posiciones/contenedor/{contenedor}/nivel/{niveles}/anaquel/{anaqueles}/almacen/{almacen}/area/{area} (position.index) | all/positions/{id_container} |
| Location | ubicaciones (location.index) | all/locations |

Para Area/Shelf/Level/Container/Position: POST `{domain}/store`, `/update`, `/destroy`, nombres `{domain}.store/update/destroy`. Create/edit son modales dentro del index, no pantallas GET separadas; métodos create/edit vacíos. Names name/comment y parent area/warehouse/shelf/level/container según entidad; hidden ID del registro en update/delete; CSRF y JS data-edit/data-delete. Requests Store/Update/Delete respectivos usan authorize=true y exists sin company; no cambio de contratos.

Los JSON all/* no declaran permisos equivalentes a list_* en routes. Lookup get/locations tampoco. Location tiene páginas auxiliares inventory.materials/items (materiales/ítems por ubicación), que no son mantenedores de estructura y quedan fuera de alcance por stock y mismos problemas de parent binding.

POST warehouse/generate/visual usa update_warehouse y genera componentes; GET ver/estructura/almacen/{warehouse_id} no permission específica; POST positions/toggle-status tampoco y alterna Position.status. No integrar ni ejecutar estas acciones auxiliares. Sin restore/SoftDeletes en ninguno de los seis modelos; destroy es eliminación física. Position sí tiene status active/inactive, distinto a eliminación; no se investigaron cascadas históricas profundamente. **MUTATION QA PENDING**.

## Scope

Los seis usan BelongsToTenant/TenantScope, NO son globales entre tenants cuando contexto existe. Area y Location tienen company_id; Shelf/Level/Container/Position no tienen company_id directo, se obtiene siguiendo padres hasta Warehouse. Ningún controller legacy añade esa validación en sus listados/find/update. Branch deriva de Warehouse, no de un flag inventado en Location. General vs local no se transforma.

Index de hijos hace find independientes para cada parámetro de breadcrumb; no comprueba que todos pertenezcan a una misma cadena ni a company. JSON filtra parent_id recibido, no parent autorizado. Requests exists comprueba existencia de tabla, no ownership ni coherencia de cadena. Edit reutiliza datos de fila y update encuentra por ID con TenantScope solamente. No es seguro integrar create separado de un index que trae parents ajenos.

QA mínimo READ ONLY de la misma consulta/find usados por controllers, no HTTP ni writes:
- ID1 propio en T1/C1 encontrado en todos los modelos.
- ID1 de C1 sigue encontrado bajo T1/C2 y T1/C5: **FAIL company** en todos.
- T3/C3 excluye ID1: PASS tenant para este dato.
- ID999999 devuelve null: no se certifica404 HTTP; index legacy usa find y puede dereferenciar null.
- Warehouse8 y9 pertenecen C1 pero B1/B8; cadena está almacenada correctamente en DB actual. Código no valida parent-chain recibida; no se ejecutó actualización ajena. No se declara branch-scoped un CRUD que no lo es.

## Dataset

Relevado2026-11-14 en PDO/MySQL START TRANSACTION READ ONLY + ROLLBACK, sin fixtures ni stock. IDs físicos completos:

| Domain | IDs / count | Parent / contexto |
|---|---|---|
| Area | [1,2,4,6,7,8] /6 | C1=[1,8], C2=[2], C3=[4], C4=[6], C5=[7] |
| Shelf | [1,2,4,6,7,8,9] /7 | warehouse_id igual al ID para este dataset |
| Level | [1,2,4,6,7,8,9] /7 | shelf_id igual al ID |
| Container | [1,2,4,6,7,8,9] /7 | level_id igual al ID |
| Position | [1,2,4,6,7,8,9] /7 | container_id igual al ID; todos active |
| Location | [1,2,4,6,7,8,9] /7 | todos IDs de cadena iguales al ID, excepto area_id de location9=8 |

La coincidencia de IDs es dato observado, NO regla para implementar.

| Cadena ID / Warehouse / Location | Tenant | Company | Branch | Area | General/local |
|---|---|---|---|---|---|
| 1 | 1 | 1 | NULL | 1 | general GEN |
| 2 | 1 | 2 | NULL | 2 | general GENERAL |
| 4 | 3 | 3 | NULL | 4 | general GENERAL |
| 6 | 4 | 4 | NULL | 6 | general GENERAL |
| 7 | 1 | 5 | NULL | 7 | general GENERAL |
| 8 | 1 | 1 | 1 | 8 | Local Principal |
| 9 | 1 | 1 | 8 | 8 | Outlet |

Consultas legacy bajo T1/C1,C2,C5: Shelf/Level/Container/Position/Location=[1,2,7,8,9]; Area=[1,2,7,8]. Bajo Gamarra T3/C3=[4] en todos. Lookup real getJsonLocations ejecutado: C1=[1,8,9], C2=[2], C5=[7], Gamarra=[4], con forma JSON conservada. No confundir buen lookup con seguridad del listado all/locations.

## Consumers

| Domain | Consumers reales relevantes |
|---|---|
| Area | Warehouse.area; Location.area; aprovisionamiento Company/ManagedWarehouse; breadcrumb/estructura legacy |
| Shelf | Warehouse.shelves; Level.shelf; Location.shelf; MaterialController navegación de ubicaciones a través Location; exportMaterialsAnaquel legacy (no ejecutado) |
| Level | Shelf.levels; Container.level; Location.level; estructura/provisioning |
| Container | Level.containers; Position.container; Location.container; estructura/provisioning |
| Position | Container.positions; Location.position; MaterialController StoreMaterialLocation/posición; status visual legacy |
| Location | InventoryLevel.location; MaterialInventoryScope valida pareja location/warehouse y contexto; DefaultInventoryLocationResolver/Material; Entries vía EntryWarehouseResolver; Availability consulta locations por contexto/warehouse; Transfers usa source_location_id/locations contextuales; OutputDetail.location legacy |

Los consumidores modernos operan fundamentalmente sobre Warehouse/Location; no hay CRUD nuevo de Level por existir InventoryLevel. No se modificó ningún consumidor, stock, reservas, costos, min/max, lotes, Items ni Kardex.

## Integrated views

**Ninguna nueva**: no se encontró un contrato CRUD independiente seguro para integrar. ManagedWarehouse permanece integrado sin retoques. El lookup contextual no habilita por sí solo una vista y no se inventó una. Allowlist unchanged: cero nuevas rutas, sin wildcard/JSON.

## Blocked views

inventory.locations, inventory.areas, inventory.shelves, inventory.levels, inventory.containers, inventory.positions; create/edit modales incluidos quedan bloqueados. No sólo Area: cada dominio se revisó con su controller/model/query/parent. No se rescataron datos incorrectos mediante JS.

## Responsive

N/A: no hay modificación visual ni nueva superficie integrada. No se repite matriz320/375/390/576/768/992/1200/1440 para certificar un flujo bloqueado. Sin cambios CSS: **0 NEW**. No se abrió UI que pueda sugerir QA funcional aprobado. No tests de escritura ni compilación Blade innecesaria; git diff --check PASS.

## Backend Sync Notes

- **INV-01**: ampliar evidencia de frontera company/warehouse/parent a los seis mantenedores; misma causa conocida, sin nuevos IDs duplicados. Lookups/JSON legacy carecen de permission granular equivalente y parent-chain autorizada; DEV debe alinear el mantenedor con la autoridad moderna antes de su integración.
- **WAREHOUSE-01**: PositionController::store también crea Location sin company_id explícita; misma deuda de altas estructurales legacy. No se ejecutó ni se corrigió. WAREHOUSE-02 permanece intacto; no se revisaron nuevamente límites de todos los campos.
- CompanyInventoryStructureService y ManagedWarehouseService no sustituyen automáticamente las rutas legacy: se requiere contrato explícito de mantenimiento de componentes o decisión de retirarlas como autoridad. No inventar ese contrato en frontend.
- Sin nuevo root cause independiente demostrado: no se crea LOCATION-xx/AREA-xx ni se modifica backlog. Se crea sólo este documento, conforme alcance.
- Estado global: **WAREHOUSE LOCATION STRUCTURE — BACKEND SYNC REQUIRED**. Backend unchanged; mutation QA pending; ninguna configuración persistida. Metas cerrada, no reabierta.
