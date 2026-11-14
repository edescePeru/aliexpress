# REAL NEXT — Warehouse Availability — Read-only Integration

Fecha: 2026-11-12. Backend y stock sin cambios. Sin commit.

## Contract Audit

Revisados WarehouseAvailabilityController, WarehouseAvailabilityRequest, WarehouseAvailabilityService, WarehouseTransferAccess, MaterialInventoryScope, StockItem, InventoryLevel, Blade y JS. Referencia visual: operational tables de warehouseTransfer/index y patrones Inventory/Material List del sistema Venti Next. No se fusiona con StockItems: allí unidades comerciales/físicas; aquí ubicación de disponibilidad por almacén/contexto.

## Routes

Prefijo /dashboard/disponibilidad/almacenes, GET exclusivamente:

| Sufijo | Nombre | Acción | Contrato |
|---|---|---|---|
| / | warehouseAvailability.index | index | Valida context y renderiza |
| /productos | warehouseAvailability.products | products | q/page + snapshot contextual, Select2 results/pagination.more |
| /datos | warehouseAvailability.data | data | stock_item_id + snapshot contextual; product/groups/checked_at |

Allowlist añadida solo warehouseAvailability.index. ALLOWLIST MATCH: YES, venti.min.css y venti-next.css observados. No se añadieron rutas de traslado.

## Permissions

viewStock_transfer en las3rutas, dentro del grupo autenticado/contextual. WarehouseTransferAccess rechaza Platform Admin, tenant discrepante, company/branch inactiva o no asignada activamente al usuario. No se usa manageAll_transfer para ampliar/restringir grupos de disponibilidad: ese método gobierna otros flujos de transfers.

## WarehouseTransferAccess

context comprueba usuario, company T/C activa, company_user, branch de company activa y branch_user. warehouse(id) exige T/C y branch activa de misma company si existe, sin exigir que esa branch sea actual. No asumir que scopeVisible(traslados) se usa en disponibilidad: snapshot tiene consulta propia. authorizeCreate/prepare/dispatch/receive no invocados.

## Tenant / Company / Branch Scope

Company y tenant desde TenantContext; form_tenant_id/form_company_id/form_branch_id se comparan con contexto actual mediante Request. No eligen contexto. Branch actual determina grupo current. Snapshot filtra warehouses T/C y luego branch NULL o branch activa de esa empresa; no requiere asignación del usuario a cada otra branch visible. Ese comportamiento explícito se conserva.

MaterialInventoryScope, en cambio, limita warehouses a general + local actual: fue auditado pero NO es el servicio de esta pantalla. No sustituir un contrato por otro. Snapshot usa DB::table con T/C explícitos sobre niveles/lotes/locations en lugar de accessors globales StockItem. No nuevo filtro branch/warehouse.

## Warehouse Types

Matriz de inventario observado respecto a EDESCE T1/C1/B1:

| Warehouse ID / nombre | Type | Tenant | Company | Branch | Visible | Reason |
|---|---|---|---|---|---|---|
| 1 GEN | General | 1 | 1 | NULL | Sí | Mismo T/C y sin branch |
| 8 Almacen Local Principal | Local actual | 1 | 1 | 1 | Sí | Branch actual activa |
| 9 Almacen Outlet | Otro local | 1 | 1 | 8 | Sí | Otra branch activa misma company |
| 2 GENERAL | General | 1 | 2 | NULL | No | Otra company |
| 4 GENERAL | General | 3 | 3 | NULL | No | Otro tenant/company |
| 6 GENERAL | General | 4 | 4 | NULL | No | Otro tenant/company |
| 7 GENERAL | General | 1 | 5 | NULL | No | Otra company |

Gamarra T3/C3/B4: visible únicamente warehouse4 general. EDESCE C2: warehouse2; C5: warehouse7 permitido, pero catálogo sin productos elegibles.

## Local / General / Other Branch

Grupos entregados current/general/branch-ID y título backend. Badges Local actual / Almacén general / Otro local se basan en esos grupos, no en heurística de nombre. Con B8, warehouse9 pasa a current, warehouse8 a branch-1, warehouse1 sigue general. No sumatoria entre grupos. C1/stock71: W8 on_hand25/reserved0/available25; W1 32/3/29; W9 0/0/0.

## Material / StockItem

Catálogo stock_items JOIN materials; ambos tenant correcto, StockItem activo, tracks_inventory true, Material no eliminado, company_stock_items activo T/C. No endpoint por Material ID. Selección StockItem ID; text = display_name + SKU, tracks_items = material.tipo_venta_id ==3. Búsqueda q en display_name,sku,barcode; API no devuelve barcode ni talla/color por separado, no se inventan columnas/variantes. Material ID no se entrega en snapshot. IDs de warehouse y StockItem conservados en data-warehouse-id/data-stock-item-id; group key preserva branch-ID cuando el contrato lo entrega. No IDs técnicos nuevos visibles.

## Availability Contract

Backend suma qty_on_hand/qty_reserved por almacén con bcmath y escala2; valida formato no negativo y reservado<=existencia. Si hay InventoryLevel utiliza sus balances; si no hay, usa lotes. Datos inválidos => null en cantidades, UI —, nunca0 inventado. available=on_hand-reserved solo backend.

Trazabilidad requiere una ubicación, balances válidos y consistencia entre nivel/lotes (o ausencia de ambos); verifica location al existir nivel único. tracks_items compara items entered contra available, con item/lot/warehouse/location y T/C consistentes. Otros estados como scraped no se cuentan aquí. UI refleja requires_review como Requiere revisión; false como Consistente según chequeo del servicio, no certificación independiente.

Tránsito: allocations dispatched menos recepciones por allocation; incoming/outgoing separados. Si recepción excede envío, ValidationException. No se suma tránsito al available. No cálculo, reparación ni compensación JS. Casos de inconsistencia/tránsito positivo/tracks_items no fabricados para QA.

## Locations

Snapshot usa locations/warehouse/stock_lots/inventory_levels para trazabilidad. NO entrega location_id ni area/shelf/level/container/position. Se documenta límite, sin consultar endpoints legacy para completar jerarquía. No acciones de editar location.

## Filters

Único selector producto/variante por q y paginación Select2 de25 (consulta26 para more). q max100, page1..100000, stock_item_id entero positivo. Se conserva mínimo1carácter, delay300, contexto hidden y GET. Actualizar vuelve a cargar snapshot. Se conserva abort/revision para respuestas de snapshot fuera de orden; endpoints/payloads intactos. No filtros backend ficticios ni paginación añadida a warehouses.

## List Mapping

REUSE WITH CONTRACT ADAPTATION: next-operational-list, next-form-section, next-page-heading/toolbar, next-list-summary, next-data-table, next-table-empty. Tablas por grupo con almacén, existencias, reservado, disponible, por recibir, enviado en tránsito y trazabilidad. Texto izquierda, cantidades/estado centro. Cantidad disponible destacada sin KPI agregado. CSS nuevo0; sin cambios globales/vendor.

Empty inicial pide seleccionar producto. Sin resultados Select2 no equivale a sin stock. Grupo sin warehouses => Sin almacenes visibles en este grupo (no afirmar autorización denegada vs inexistencia). Stock0 se muestra0. Errores backend se muestran en alerta wa-error, separados de stock; snapshot fallido limpia resultados previos. Sin metadato específico de causa no se inventa diagnóstico. Todos IDs wa-* y formularios/contexto intactos.

## Action Matrix

| Action | Route | Permission | Read/Mutation | Included |
|---|---|---|---|---|
| Abrir | warehouseAvailability.index | viewStock_transfer | Read | Sí |
| Buscar | warehouseAvailability.products | viewStock_transfer | GET | Sí |
| Consultar/Actualizar | warehouseAvailability.data | viewStock_transfer | GET | Sí |
| Solicitar traslado | warehouseTransfer.create vía request_url | list_transfer/create_transfer + acceso backend | Inicio de flujo de mutación | NO |
| Guardar/prepare/dispatch/receive/cancel | Warehouse Transfers | Permisos específicos del flujo | Mutation | NO |
| Reservar/ajustar/editar ubicación/mover stock | No endpoint integrado | No se habilita | Mutation | NO |

## Transfer Boundary

Backend aún puede entregar request_url. Adaptador no lo renderiza ni lo sigue; 0links en wa-groups verificado, incluso W1 con request_url disponible. TRANSFER MUTATION — OUT OF SCOPE. Módulo Warehouse Transfers y sidebar global intactos. No movimientos ni escrituras de inventario.

## Responsive QA

Navegador real Owner EDESCE T1/C1/B1, producto71 elegido por búsqueda SKU P-00001. Búsqueda y Actualizar devolvieron W8/W1/W9 con valores esperados. 320,375,390,576,768,992,1200,1440: documentElement.scrollWidth<=innerWidth;15px de diferencia corresponde scrollbar vertical. GLOBAL OVERFLOW: NONE; tablas con scroll local. Capturas desktop/móvil, consola sin errores observados. No request de traslado desde resultado. Sintaxis node --check, Blade compile PHP7.3 y git diff --check aprobados.

## Cross-context QA

Harness temporal sin versionar, SET TRANSACTION READ ONLY y rollback. Sesión array efímera, Auth::setUser e InitializeTenantContext. Servicios reales catalog/product/snapshot y access.warehouse. No mock ni registros creados. Excepciones de servicio indican contratos422/403; no confundir con mediciones HTTP de sesiones UI.

| Usuario / T/C/B | Producto consultado | Warehouses snapshot | Evidencia |
|---|---|---|---|
| Owner EDESCE1/1/1 | 71 | 8,1,9 |25/29/0 disponibles |
| Owner EDESCE1/1/8 | 71 | 9,1,8 |0/29/25, grupos cambian correctamente |
| Owner EDESCE1/2/2 | 95 | 2 |10disponibles; grupos branch2/3 sin almacén local |
| Owner EDESCE1/5/6 y1/5/7 | sin elegibles | Sin snapshot | catálogo vacío; warehouse7 accesible por contrato |
| Owner Gamarra3/3/4 |106 |4 |0disponibles; grupo current sin almacenes |

Productos105/106 Gamarra rechazados422 en C1; productos EDESCE (incluido71) rechazados422 en Gamarra. C2 rechaza71; comparte95..104/107..110 habilitados con C1, compartición legítima por company_stock_items, no fuga.99999999producto=>422. Warehouses ajenos y99999999=>403. EDESCE C1 no accede W2/W4/W6/W7. Gamarra soloW4. Prueba con Owner EDESCE y snapshotT1/C1/B4=>AuthorizationException403: branch ajena rechazada. Material directo no es parámetro aceptado, no se intercambió Material ID por StockItem. Request contextual mismatch auditado por reglas; no se alteró sesión real.

QA Gamarra y Outlet realizado por harness de servicios; navegador real solo EDESCE Principal. Comparación por IDs, no solo cantidades. Inventory Context y Cross-context Isolation — QA PASSED para matriz observada.

## Backend Sync Notes

No defecto bloqueante demostrado en el contrato auditado. No se certifican casos no presentes: balances corruptos, tránsito positivo, inventario por pieza o branch inactiva con datos. No se resuelven pendientes previos de otros módulos. Si backend incorpora locations o nuevas restricciones warehouse, reauditar sin inventarlas en UI. Solicitar traslado queda fuera aunque backend entregue URL.

## Revisit Triggers

Cambios en catálogo company_stock_items, permisos, visibilidad de otros locales, balances/trazabilidad, snapshot de contexto o jerarquía de locations; dataset de tránsito/piezas; futura autorización de fase de traslados. Revalidar errores/empty y matrices por IDs.

## Files Changed / Status

- config/venti-next.php
- resources/views/warehouseAvailability/index.blade.php
- public/js/warehouseAvailability/index.js
- docs/REAL-NEXT-WAREHOUSE-AVAILABILITY-READONLY.md

Warehouse Availability — FRONTEND INTEGRATED — READ PATH.
Inventory Context / Cross-context Isolation — QA PASSED (alcance arriba).
Local / General / Other Branch — PRESERVED.
Transfer Actions / Inventory Mutations — OUT OF SCOPE.
Backend — UNCHANGED. CSS nuevo0. No commit.
