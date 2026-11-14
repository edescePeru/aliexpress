# REAL NEXT — Almacenes / Warehouses

2026-11-13. Integración parcial: **Managed Warehouses — FRONTEND INTEGRATED WITH BACKEND DEPENDENCY**. Maestro legacy por área: **BACKEND SYNC REQUIRED**. Sin mutaciones, stock, commit ni cambios backend.

## Routes

Prefijo `/dashboard`. Middleware común: auth, check.user.enabled, password.changed, tenant.context. Administrados además tenant.owner (rechaza Platform Admin y no Owner).

| Route / name | Permission | Tenant | Company | Branch | Read/Mutation |
|---|---|---|---|---|---|
| GET configuracion/empresas/{id}/editar — tenantCompany.edit | edit_company | explícito | empresa elegida del tenant | N/A | Read, incluye maestro general |
| GET configuracion/sucursales/{id}/editar — tenantBranch.edit | edit_branch | vía company | company de sucursal | ID validado | Read, incluye maestro local |
| GET configuracion/empresas/{companyId}/almacenes — managedWarehouse.company | edit_company | explícito | URL validada | NULL | Read JSON |
| POST misma ruta (sin nombre) | edit_company | explícito | URL validada | NULL | save/default; no ejecutado |
| GET configuracion/empresas/{companyId}/sucursales/{branchId}/almacenes — managedWarehouse.branch | edit_branch | explícito | URL validada | pertenece a company | Read JSON |
| POST misma ruta (sin nombre) | edit_branch | explícito | URL validada | pertenece a company | save/preference; no ejecutado |
| GET ver/almacenes/{area} — warehouse.index | list_warehouse | TenantScope | NO | NO | Read Blade legacy |
| GET all/warehouses/{id_area} | sin capability específica | TenantScope | NO | NO | Read DataTables legacy |
| POST warehouse/store — warehouse.store | create_warehouse | autoasignado | no asignada | no asignada | Mutation legacy |
| POST warehouse/update — warehouse.update | update_warehouse | TenantScope find | NO | NO | Mutation legacy |
| POST warehouse/destroy — warehouse.destroy | destroy_warehouse | TenantScope find | NO | NO | Physical delete legacy |
| POST warehouse/generate/visual — warehouse.create.visual | update_warehouse | scope modelos | sin validar parent company | NO | Genera estructura; fuera de alcance |
| GET ver/estructura/almacen/{warehouse_id} | sin capability específica | scope Shelf | NO | NO | Lectura estructura; fuera de alcance |
| POST positions/toggle-status — positions.toggleStatus | sin capability específica | scope Position | no guard parent explícito | NO | Cambia Position, NO Warehouse; no ejecutado |

## Permissions

ManagedWarehouseController + ManagedWarehouseService: permisos edit_company/edit_branch y Owner, no se reemplazan por permisos legacy. WarehouseController: list/create/update/destroy_warehouse. Legacy create/edit no tienen vistas GET separadas: modales en `inventory.warehouses`, abastecidos por la fila; métodos create/edit vacíos. Requests Store/Update/DeleteWarehouseRequest authorize=true; validan exists global, no ownership company. Se preservaron todos los permisos; no se añadieron rutas ni menú.

## Scope

Warehouse usa BelongsToTenant; no tiene CompanyScope. Legacy filtra area_id, pero no valida pertenencia del área a company activa. `Area::find` tampoco es findOrFail: parent inexistente puede romper el Blade, no certificamos 404 legacy. Un cambio de company dentro del mismo tenant conserva los mismos IDs legacy.

El servicio administrado es distinto: Owner administra empresas de su tenant mediante company explícita en URL, incluso cuando la empresa activa es otra. Esto es el contrato de TenantCompany, NO una fuga del catálogo de company activa. Branch se valida dentro de esa company; warehouses/location se filtran por tenant + company y branch exacta/NULL. No se agregó selector company/branch. Sin tenant, TenantContext::tenantId exige contexto; no se usó Platform Admin.

## General vs Local

General = branch_id NULL. Local = branch_id de sucursal. is_default es predeterminado general, no indicador de activo. El backend exige un único default general y una ubicación operativa para default/preference. Branch.default_entry_warehouse_id representa destino habitual; NULL conserva fallback backend al general. No se convirtió general en local. Edición managed cambia sólo name/comment; no cambia filiación.

## Dataset

Consulta actual PDO/MySQL en START TRANSACTION READ ONLY + ROLLBACK; no conteos históricos reutilizados. 7 warehouses, sin deleted_at/active flag.

| Tenant/company | IDs: nombre | Branch | General / default |
|---|---|---|---|
| EDESCE 1/1 | 1 GEN | NULL | general, default |
| EDESCE 1/1 | 8 Almacen Local Principal | 1 | local, no default |
| EDESCE 1/1 | 9 Almacen Outlet | 8 | local, no default |
| EDESCE 1/2 | 2 GENERAL | NULL | general, default |
| EDESCE 1/5 | 7 GENERAL | NULL | general, default |
| Gamarra 3/3 | 4 GENERAL | NULL | general, default |
| Otro tenant 4/4 | 6 GENERAL | NULL | general, default |

Managed general IDs C1=[1], C2=[2], C5=[7], C3=[4]. Todos tienen locations_count=1 y configuration_error=null. C1/B1=[8], preferencia=8; C1/B8=[9], preferencia=NULL. Prueba servicio C2/B1 y C1/B999999: ModelNotFoundException. Bajo T1, C3/999999 rechazadas; bajo T3, C1/C2/C5/999999 rechazadas. Son pruebas del servicio/modelo, no sustituyen QA HTTP de permisos.

Legacy Warehouse IDs bajo T1/C1, C2 y C5: [1,2,7,8,9]; bajo T3/C3: [4]. Area IDs T1=[1,2,7,8], T3=[4]. Missing Warehouse999999=null. Estos resultados demuestran ausencia de company scope, sin declarar que los modelos sean globales.

## Fields

Managed: name required|string|max:255; comment nullable|string|max:255; warehouse_id hidden nullable integer/min1 (required para action=default); action save/default/preference; CSRF. IDs managed-warehouse-name/comment/preference y hooks js-warehouse-* intactos. POST shape, callbacks, resets, errores backend y estados disabled sin cambios.

Legacy: name/comment, area_id hidden al crear y warehouse_id hidden al editar/eliminar. No capacity/address/inventory_mode ni clasificación inventada. Esquema actual name/comment VARCHAR(191), validación 255: WAREHOUSE-02 abierto; no se alteró maxlength unilateralmente.

## Branch Relationship

Warehouse belongsTo Company/Branch/Area. Branch no tiene tenant_id propio: pertenencia tenant se valida por Company. No lookup global de branches en formulario managed; el parent es la sucursal editada. Preference usa sólo warehouses locales devueltos por servicio. Las companies secundarias son administrables por Owner, no requieren cambio de sesión activa.

## Inventory Relationships

hasMany shelves, locations, inventoryLevels. Managed alta crea una estructura GENERAL y una ubicación operativa; no se ejecutó. Legacy genera Shelf → Level → Container → Position → Location; no se integró esta acción. No se tocó Items, costos, min/max, reservas ni InventoryLevels.

## SoftDelete / Disable

Warehouse NO usa SoftDeletes, no flag active, no rutas deleted/restore. Managed no expone eliminar/desactivar. Legacy destroy llama delete físico sin revisar stock/historia en controller/request. Existen restricciones/cascadas FK, pero no equivalen a una política funcional completa: **BUSINESS/BACKEND DESIGN**, dentro de INV-01. No se probó eliminación ni se inventó bloqueo visual. Restore: NOT APPLICABLE.

## Consumers

TenantCompany.edit y TenantBranch.edit incluyen inventory.managedWarehouses y js/inventory/managedWarehouses.js. EntryWarehouseResolver/EntryDestinationValidator, DefaultInventoryLocationResolver, PosInventoryService, Warehouse Transfers, Availability y estructura física consumen Warehouse. Sólo se documentan; ninguno modificado. Menú actual: Empresas conduce al managed; maestro legacy se encadena desde Áreas, no se añadió acceso nuevo. Transfers y Availability mantienen sus rutas y read models.

## Visual Mapping

REUSE WITH CONTRACT ADAPTATION: Configuration CRUD, next-page-toolbar/context, next-form-section, next-list-toolbar, next-toolbar-actions flex-wrap, Bootstrap row/col-md-6. Card-body p-0; secciones poseen gutters. Tabla de cuatro columnas reales: nombre/observación izquierda, configuración/acciones centro. Se conserva acción inline existente; no se inventó CRUD separado.

Tabla usa table-bordered/table-hover/table-sm y table-responsive: next-data-table fuerza min-width:63rem, innecesario para cuatro columnas dentro de una ficha. Reutilizamos tabla Bootstrap tematizada sin ese mínimo, 0 CSS nuevo. Formulario de alta/edición compartido, botones y preference permanecen en su propio form. No forms anidados.

ALLOWLIST MATCH: YES para tenantCompany.edit y tenantBranch.edit ya existentes; no cambio de allowlist ni endpoints JSON añadidos. Carga comprobada venti.min.css + venti-next.css. Legacy warehouse.index NO añadido.

## Responsive

Empresa y sucursal, modo edición: 320/375/390/576/768/992/1200/1440 PASS sin overflow global (scrollWidth <= innerWidth). Tabla con scroll local en móvil; campos y acciones reorganizados; desktop dos campos inline. Capturas warehouses-company-desktop.png y warehouses-branch-mobile.png en carpeta de evidencias de la sesión. Sin errores de consola durante QA EDESCE.

HTTP real Owner EDESCE (T1/C1/B1): company1/2/5 render y JSON, abrir Editar y Cancelar edición, sucursal1 y preference=8. HTTP Owner Gamarra (T3/C3/B4): general propio y abrir Editar; empresa1 ajena e ID999999 muestran404. El cambio de cuenta cambió contexto correctamente. No se ejecutó POST de negocio, default ni preference. Permisos negativos de rol: auditoría estática, no simulación HTTP completa. No se certifica visualmente legacy bloqueado.

## Backend Sync Notes

[OPEN] INV-01: company/parent ownership del maestro legacy y Áreas; no duplicar causa de inventario. Frontend legacy: No. Managed: Sí, contrato explícito validado.

[OPEN] WAREHOUSE-01: WarehouseController::store omite company_id exigido NOT NULL sin default; trait sólo asigna tenant. Código/esquema verificados, no POST ejecutado. DEV debe alinear alta legacy o retirar ese camino. No afecta alta managed que asigna company explícita.

[OPEN] WAREHOUSE-02: validación/HTML max255 frente a VARCHAR191 real en name/comment; afecta managed y legacy. Definir límite coherente servidor/esquema; no truncar cliente. Frontend continúa preservando contrato, mutaciones no certificadas.

[OPEN] Eliminación física y coherencia stock/historia: BUSINESS/BACKEND DESIGN, referencia INV-01. JSON legacy sin capability propia, además de scope insuficiente; no se habilitó.

Siguiente mantenedor estructural revisado brevemente: AreaController/getAreas devuelve áreas de todas las companies del tenant y store omite company_id; **AREAS — BACKEND SYNC REQUIRED**. No seguir QA profundo ni integrar sus modales. Shelves/Levels/Containers/Positions no se amplían a esta fase.

## Final Status / Validation

| Superficie | Estado |
|---|---|
| Managed List | FRONTEND INTEGRATED — READ QA PASSED |
| Managed Create/Edit | FRONTEND INTEGRATED WITH BACKEND DEPENDENCY — MUTATION QA PENDING |
| Legacy List/Create/Edit/Delete | BACKEND SYNC REQUIRED |
| Deleted/Restore | NOT APPLICABLE |
| Inventory mutations | OUT OF SCOPE |
| Backend | UNCHANGED |
| CSS | 0 NEW |

node --check managedWarehouses.js, PHP7.3 artisan view:cache y git diff --check: PASS. Ningún commit/staging. Cambios: partial managedWarehouses Blade, JS sólo clases/wrapper de presentación, este documento y addendum backlog.
