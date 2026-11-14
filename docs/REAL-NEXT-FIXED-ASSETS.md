# REAL NEXT — FIXED ASSETS

2026-11-14 · Auditoría corta READ ONLY · **BACKEND SYNC REQUIRED**. Sin cambios de frontend/backend/inventario. Fallback ejecutado: REAL-NEXT-WARRANTS.md.

## Contract / modern vs legacy

Activo fijo aquí no es un ledger contable: MaterialController::getAllMaterialsActivosFijos combina materiales habilitados de **category_id=8** con materiales de Items `type=true` fuera de esa categoría, agrupando su `percentage`. Para categoría8 entrega `quantity_items` (cuenta Items usage != finished); precio `price_final` (list_price o unit_price). No usa el acceso moderno de MaterialCompanyCatalog, MaterialInventoryScope ni StockItemScreenAccess. Material e Item tienen TenantScope, no filtro company/branch/warehouse en este camino. No se extrapolan las garantías de Material V2.

## Surfaces

Todas bajo dashboard/auth/check.user.enabled/password.changed/tenant.context.

| Surface | GET route / name | Permission | Scope | Decision |
|---|---|---|---|---|
| Listado operativo | listar/materiales/activos/fijos / material.actives.index | listActive_material | Query legacy tenant-only | BACKEND SYNC REQUIRED |
| Listado financiero | materiales/activos/fijos / invoice.materials.fijos | list_invoice | Mismo JSON legacy | BACKEND SYNC REQUIRED |
| JSON común | all/materials/activos | list_invoice | Tenant-only; stock sin company | BACKEND SYNC REQUIRED |
| Detalle Items | view/material/activo/items/{id} / material.getItems.active | Sin permission específica en esta ruta | Material::find tenant-only | BACKEND SYNC REQUIRED |
| JSON detalle | view/material/activo/all/items/{id} / material.getItemsMaterial.activos | Sin permission específica en esta ruta | Items por material_id, tenant-only | BACKEND SYNC REQUIRED |
| Create/edit Material general | Rutas Material existentes | Permisos Material | Contrato separado | OUT OF SCOPE |

Controllers/actions: listarActivosFijos → material.listarActivosIndex → js/material/indexActivesFijos.js; indexMaterialsActivos → material.indexActivosFijos → indexActivosFijos.js; getAllMaterialsActivosFijos; getItemsMaterialActivo → material.itemsActivos → itemsActivos.js; getItemsMaterialAllActivos.

## Columns / filters / detail

Listado: código, descripción, medida, unidad, stock máximo/mínimo/actual, prioridad, imagen/precio según render, categoría y atributos subcategoría/tipo/subtipo/marca/modelo/cédula/calidad/retacería, acciones. DataTables mantiene columnas ocultas, búsqueda/orden/paginación cliente. No hay filtros contextuales de company/branch/warehouse. El JSON arma valores escalares para relaciones mientras hay renderers legacy que todavía esperan objetos: no se certifica la compatibilidad visual de este flujo bloqueado.

Detalle: descripción material, código Item, largo, ancho, price, percentage, estado derivado de usage/state_item y acción visual Cambiar Estado. `getItemsMaterialAllActivos` usa todos los Items si category8, o type1 para otra categoría; carga location/jerarquía/material/typescrap/detailEntry. No restringe company ni warehouse; Material inexistente produce null y se accede category_id, sin 404 explícito. No abrir documentos ni probar cambios de estado.

## Current Dataset / isolation

MySQL START TRANSACTION READ ONLY / ROLLBACK. Consultas equivalentes al acceso del controller, sin ejecutar mutaciones ni recalcular stock.

| Context | Material habilitado category8 | Items type1 | Items detalle material15/16/17 |
|---|---|---|---|
| EDESCE T1/C1 | [15,16,17] | [] | [1..81] completos y consecutivos |
| Otra company T1/C2 | [15,16,17] | [] | Mismos [1..81] |
| Gamarra T3/C3 | [] | [] | [] |

Material15 CELULAR 256GB APPLE IPHONE 17; 16 CELULAR APPLE IPHONE 17 PRO MAX; 17 PRODUCTO TEST NUEVO APPLE IPHONE 17 HOMBRE. enable_status=1. La categoría8 actual se llama **CELULARES**. Estos son candidatos seleccionados por la query legacy, no activos contables certificados.

81 Items, todos T1/C1, warehouse1 general, branch=NULL. Material15: 10 entered, 2 reserved, 5 exited; material16: 48 entered,16 exited; material17:0. Todos usage=new. `price` almacenado entre2000–4000; no se interpreta como depreciación/valor contable. No hay Items type1 en los contextos auditados. Material/Item no heredan company desde el contexto: C2 puede leer IDs físicos de C1 en esta query. TenantScope excluye T1 bajo T3; no significa company isolation PASS. Material999999 inexistente comprobado con find; no se certifica HTTP404.

## Action Matrix

| Action | Permission | Read/Mutation | Included |
|---|---|---|---|
| Listar / JSON | listActive_material vs list_invoice | Read | No: scope y permisos no alineados |
| Ver Items | Sin guard granular de ruta | Read | No: binding tenant-only |
| Editar material | update_material | GET formulario + mutación separada | OUT OF SCOPE |
| Cambiar Estado | Botón data-usage; este JS no implementa handler de escritura | Potencial mutación | No ejecutado ni habilitado |
| Imagen / documentos relacionados | Contrato público legacy | Read | BACKEND/INFRA OPEN; sin reauditar DOC-01 |
| Transferir, asignar, depreciar | No contrato dedicado encontrado en estas vistas | Mutation | OUT OF SCOPE |

## Backend Sync Notes

[OPEN] **INV-01**: lectura de inventario/Items legacy sin frontera company/branch/warehouse y sin ScreenAccess. No duplicar root cause con ASSET. DEV debe homologar listado/detalle y permisos antes de integrar; no filtrar sólo en frontend.

[OPEN] **ASSET-01 — Clasificación por ID fijo category8**: `getAllMaterialsActivosFijos` y `getItemsMaterialAllActivos` codifican8 como activo, pero DB actual8=CELULARES. Root semántico distinto del aislamiento; DEV/Producto debe definir autoridad explícita de activo fijo sin IDs mágicos. No cambiar categorías/datos ni deducir depreciación. Frontend puede continuar: No en Activos, Sí fallback Cédulas.

Allowlist: sin adiciones para Activos. Responsive no ejecutado: ninguna vista integrada. CSS0. Backend UNCHANGED. Mutaciones OUT OF SCOPE. No commit.
