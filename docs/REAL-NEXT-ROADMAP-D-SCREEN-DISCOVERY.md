# REAL NEXT — Next Screens Discovery / Roadmap D

Fecha: 2026-11-13. Descubrimiento focalizado; no certificación funcional ni QA profundo. Solo lectura de código y DB local mediante transacción READ ONLY y ROLLBACK. Sin mutaciones, tests nuevos, cambios de aplicación ni commit.

Se contrastaron rutas, controladores, modelos y `config/venti-next.php`: las superficies seleccionadas siguen fuera de la integración. No se reabren módulos terminados ni findings que DEV ya revisa. Las rutas siguientes llevan prefijo `/dashboard/`; las vistas, `resources/views/`.

## Candidates

B = backend readiness, V = valor, E = esfuerzo, R = riesgo, D = dataset; escala 1–5. B/V/D altos son favorables; E/R altos implican mayor coste. Orden editorial, no suma mecánica. READY significa suficiente para iniciar integración acotada, no certificación productiva.

| # | Pantalla/familia nueva | Estado | B/V/E/R/D | NEXT? |
|---|---|---|---|---|
| 1 | Compras General V2 — listado | READY FOR READ-ONLY FRONTEND | 4/5/3/2/3 | YES |
| 2 | Contactos de clientes — mantenimiento independiente | READY FOR FRONTEND | 4/3/2/2/3 | LATER |
| 3 | Compras Express V2 — listado | READY FOR READ-ONLY FRONTEND | 4/4/2/2/1 | LATER |
| 4 | Calidades | FRONTEND CAN CONTINUE WITH TECH DEBT | 3/3/1/2/4 | LATER |
| 5 | Tipos de gasto / Bill | FRONTEND CAN CONTINUE WITH TECH DEBT | 3/3/1/2/1 | LATER |
| 6 | Estados civiles | FRONTEND CAN CONTINUE WITH TECH DEBT | 3/2/1/2/1 | LATER |
| 7 | Feriados | FRONTEND CAN CONTINUE WITH TECH DEBT | 3/2/2/2/1 | LATER |
| 8 | Porcentajes de cotización — catálogo independiente | FRONTEND CAN CONTINUE WITH TECH DEBT | 3/3/1/3/4 | LATER |
| 9 | Inventario estructural | NEEDS FOCUSED AUDIT | — | NO |
| 10 | Salidas / solicitudes simples y por área | WAIT FOR DEV | — | NO |
| 11 | Órdenes de servicio | WAIT FOR DEV | — | NO |
| 12 | Gastos / egresos de proveedores | WAIT FOR DEV | — | NO |

### Evidencia mínima y límites

| Candidato | Rutas / controlador / vistas | Scope y permisos | Dataset actual y frontera de integración |
|---|---|---|---|
| General V2 | `listado/general/ordenes/compra/v2`, `get/data/orders/general/v2/{numberPage}`; `OrderPurchaseController::indexV2/getDataOrderGeneral`; `orderPurchase/general_v2.blade.php` | `OrderPurchase` usa `ScopedPurchaseCompany`: tenant + company, sin contexto devuelve cero filas. `list_orderPurchaseExpress` también protege General; conservar este contrato aunque el nombre resulte extraño. Supplier es tenant-scoped; actor/aprobador restringidos al tenant. | 4 órdenes físicas T1/C1; 3 no eliminadas, todas tipo `n`. Integrar listado, filtros y paginación. Excluir cambiar estado, regularizar, documentos, impresión y exportaciones no auditadas. No prometer aislamiento por branch: el contrato es company. |
| Contactos | `contactos`, `all/contacts`, `crear/contacto`, `editar/contacto/{id}`, `contactos/restore`; `ContactNameController`; `contactName/index/create/edit/restore.blade.php` | ContactName y Customer usan TenantScope; edit busca contacto contextual y formularios consultan clientes del tenant. `list/create/update/destroy_contactName`. | 4 filas físicas: T1=3, T3=1. Maestro tenant, no company. Integrar listado/forms/restore visual; guardar, eliminar y restaurar quedan sin ejecución durante QA. No reabrir Customers. |
| Express V2 | `listado/ordenes/compra/express/v2`, `get/data/orders/express/v2/{numberPage}`; `OrderPurchaseController::indexExpressV2/getDataOrderExpress`; `orderPurchase/express_v2.blade.php` | Mismo scope company de General; filtro `type=e`; `list_orderPurchaseExpress`. Lookups de proveedores por tenant. | 0 órdenes Express activas. Se puede integrar con empty state; filas reales y multipágina pendientes de dataset. Excluir creación, recepción, regularización y estados. |
| Calidades | `Calidades`, `all/qualities`, `crear/calidad`, `editar/calidad/{id}`; `QualityController`; `quality/index/create/edit.blade.php` | Catálogo legacy global, sin tenant/company; `list/create/update/destroy_quality`. | 13 filas físicas. Integración de lectura y presentación de forms; no escribir ni certificar gobierno global. Sin tocar consumidores de Material. |
| Bill | `tipos/gastos`, `all/bills`, `crear/tipo/gasto`, `editar/tipo/gasto/{id}`; `BillController`; `bill/index/create/edit.blade.php` | Referencia global de descripciones de gasto; `list/create/update/destroy_bill`. | 0 filas. Empty state y formulario convencional; no gastos monetarios. POST store/update/destroy fuera del QA. Decisión de ownership global pendiente. |
| Estados civiles | `estado/civil`, `all/civilStatuses`, `crear/estado/civil`, `editar/estado/civil/{id}`, `estado/civil/eliminados`; `CivilStatusController`; `civilStatus/index/create/edit/indexDeleted.blade.php` | Referencia global, no expedientes de trabajadores. `statusCivil_worker` para lectura y mantenimiento. | 0 filas. Integrar listado/forms/estado vacío; altas, cambios, baja y restore no ejecutados. Permiso único no equivale a Platform Admin. |
| Feriados | `feriados`, `all/holidays`, `crear/feriado`, `editar/feriado/{id}`; `HolidayController`; `holiday/index/create/edit.blade.php` | Calendario global legacy; `holiday_worker`. | 0 filas. Presentación solamente; CRUD y `generate/holidays` fuera del QA. No extender a asistencia/planilla. |
| Porcentajes | `porcentajes/cotizaciones`, `all/porcentages/quotes`, `crear/porcentaje`, `editar/porcentaje/cotizacion/{id}`; `PorcentageQuoteController`; `porcentagesQuote/index/create/edit.blade.php` | Catálogo global; `list/create/update/destroy_porcentageQuote`. Nombres técnicos utility/rent/letter/igv tienen protección de renombrado; no reinterpretarlos. | 4 filas. Lectura/presentación de forms; cambiar valores tiene alcance comercial global, excluido. No reabrir listado ni lógica de Quotes. |
| Estructural | `areas`, `ver/almacenes/{area}`, rutas anidadas de anaqueles/niveles/contenedores/posiciones, `ubicaciones`; Area/Warehouse/Shelf/Level/Container/Position/LocationController; familias de vistas homónimas | Permisos `list/create/update/destroy_*`. Scope tenant en partes del flujo no demuestra aislamiento company ni ownership de toda la cadena de IDs. | Áreas 6, almacenes 7, ubicaciones 7; shelves/levels/containers/positions: 7 cada uno. Datos en T1/C1,C2,C5, T3/C3,T4/C4. Separar posible CRUD seguro tras auditoría puntual; no incluir Kardex. Generar estructura y alternar posiciones son mutaciones. |
| Salidas | `listado/solicitudes/salida/v2`, `solicitudes/salida`, `salidas`, `solicitudes/area/`, `mis/solicitudes/area/`; `OutputController`; `output/index_output_request_v2`, `index_output_simple`, `index_my_output_simple` | Permisos por acción, incluyendo `list_output` y confirmaciones. Output tenant-scoped, pero listado V2 no prueba restricción company/branch; lookup `User::all()` sin frontera contextual explícita. | 119 filas: T1/C1/B1=118, B8=1. No basta con quitar botones: lectura requiere revisión backend. Confirmar/despachar afecta stock. |
| Servicio | `ordenes/servicio`, `listar/ordenes/servicio`, `ver/orden/servicio/{id}`, `servicios/`; `OrderServiceController`; `orderService/indexOrderService/listOrderService/showOrderService/indexService` | `list/create/update/delete/regularize_orderService`; OrderService sin scope tenant/company y consultas globales. | 0 órdenes y detalles. Crear/regularizar/eliminar y rutas de regularización automática peligrosas; listado read-only no corrige exposición global. |
| Gastos/proveedores | `rendicion/gastos/general`; `ExpenseController`; `expense/index/create/edit/report`. `egresos/proveedores/v2`; `ExpenseSupplierController`; `expenseSupplier/index_v2` | `list_expense` / `list_expenseSupplier`. Expense sin scope; usuarios/trabajadores y gastos globales. ExpenseSupplier es agregación: no asumir tabla propia ni seguridad por nombre. | Expenses=0. Tabla `expense_suppliers` no disponible: no equivale a cero egresos. Rendiciones, pagos y cambios de facturación fuera; dependencia financiera necesita DEV. |

Conteos físicos incluyen eliminados cuando el modelo soporta soft delete, salvo compras activas expresamente indicadas. No equivalen automáticamente a filas visibles. No se extrajeron datos personales ni credenciales. Fuente: tablas locales actuales y código, no conteos de auditorías anteriores.

## Quick Wins

1. **Contactos:** contrato tenant claro, datos en dos tenants y CRUD convencional; mejor mantenedor independiente.
2. **Calidades:** 13 filas y patrón Configuration CRUD; limitar QA a lectura.
3. **Bill:** pequeño catálogo, separado del flujo monetario bloqueado.
4. **Estados civiles:** referencia simple; dataset vacío explícito.
5. **Porcentajes:** dataset disponible; fachada únicamente, sin tocar valores comerciales.

Para catálogos globales, la ausencia de scope se registra como deuda de ownership, no como prueba de aislamiento. Avanzar visualmente sin ampliar rutas/permisos; no convertirlos artificialmente en tenant-scoped ni permitir nuevas capacidades.

## High Value

1. **Compras General V2:** nueva superficie agregada, scope demostrado y datos; no es otro cierre de Compras Normales.
2. **Compras Express V2:** comparte contrato seguro y patrones; valor operativo con dataset de filas aún pendiente.
3. **Salidas/solicitudes:** 119 registros y valor alto, pero no elegible ahora por scope/lookup.
4. **Inventario estructural:** datos multiempresa; valor alto condicionado a comprobar cada relación padre/hijo, sin mezclarlo con Kardex.

## Wait for Dev

| Familia revisada | Estado / motivo para no priorizar |
|---|---|
| Expense / OrderService / Output | WAIT FOR DEV: las fronteras de lectura descritas arriba no permiten vender una integración read-only como segura. |
| ExpenseSupplier | WAIT FOR DEV: composición financiera legacy; no usar ausencia de tabla propia como prueba de dataset vacío. |
| Activos fijos / equipment | NEEDS FOCUSED AUDIT: `material.actives.index` / `MaterialController::listarActivosFijos` es superficie legacy distinta; no se demostró frontera company del flujo. Equipment tiene 72 registros sin scope; DefaultEquipment=0. No asumir que equipamiento y activos son el mismo flujo vigente. |
| FollowMaterial | WAIT FOR DEV: seguimiento por usuario no certifica scope del stock/detalle. Hay GET de follow/unfollow y envío de correo; no recorrerlos como lectura segura. |
| License, Permit, PermitHour, MedicalRest, Contract | WAIT FOR DEV: tablas vacías y modelos sin aislamiento tenant/company; contienen expedientes personales, no simples referencias globales. |
| Discount / DiscountQuantity | WAIT FOR DEV / NEEDS FOCUSED AUDIT respectivamente: Discount es descuento laboral con Worker, no catálogo comercial; 0 filas. DiscountQuantity presenta métodos stub; no proponer una fachada sin flujo vigente. |
| Permission / Role | WAIT FOR DEV: Permission global (554), roles 21 distribuidos entre T1=10,T2=5,T3=3,T4=3. RoleController legacy usa Spatie Role sin frontera tenant explícita suficiente; no reabrir UserController ni Platform Admin integrado. |
| Findings ya entregados a DEV | Permanecen fuera: Material Save/Separate Pack, Cash fixes/Admin Cash, Entry detail, documentos, Quotes, core POS/FreeSale, Finance, Dashboard/Meta y catálogo público sin dataset publicable. Este documento no cierra ni modifica findings. |

## Roadmap D — 8 nuevas fases

Todas son integración frontend nueva, no QA closure. Reutilizar Operational List y Configuration CRUD, Surface Spacing Standard y allowlist mínima. No exigir mutaciones para integrar el read path. Las fases 4–8 se acotan a presentación/lectura por deuda global; no prometen CRUD productivamente certificado.

| # | Module | Routes | Why now | Scope | Dataset | Frontend effort | Backend limitation |
|---|---|---|---|---|---|---|---|
| 1 | Compras General V2 — Read-only Integration | `listado/general/ordenes/compra/v2` | Operativo nuevo con frontera company explícita | Listado, filtros, paginación | 3 activas T1/C1 | Medio | Sin estados, exportación, documentos ni detalle no auditado |
| 2 | Contactos — Configuration CRUD | `contactos`, create/edit/restore | Alternar con mantenedor pequeño contextual | Tenant; listado y forms | 4 físicas / 2 tenants | Bajo | Mutaciones no ejecutadas; sin aislamiento company porque es maestro tenant |
| 3 | Compras Express V2 — Read-only Integration | `listado/ordenes/compra/express/v2` | Reutiliza patrón de fase 1 | Company; listado/empty state | 0 activas | Bajo–medio | QA con filas/multipágina pendiente; no inventar órdenes |
| 4 | Calidades — Legacy Catalog Integration | `Calidades`, crear/editar calidad | Catálogo con datos, sin tocar Material | Global; lectura/forms visuales | 13 físicas | Bajo | Gobierno global y escritura sin certificar |
| 5 | Tipos de gasto — Catalog Integration | `tipos/gastos`, crear/editar tipo | Desacoplar catálogo del flujo Expense | Global; listado/forms | 0 | Bajo | No rendiciones/pagos; escritura excluida |
| 6 | Estados civiles — Catalog Integration | `estado/civil`, crear/editar/eliminados | Referencia pequeña y patrón reutilizable | Global; listado/forms | 0 | Bajo | No expedientes RRHH; restore sin ejecutar |
| 7 | Feriados — Calendar Catalog Integration | `feriados`, crear/editar feriado | Nueva pantalla independiente de planilla | Global; calendario/listado/forms existentes | 0 | Bajo–medio | No generación masiva ni escritura |
| 8 | Porcentajes — Read-only Catalog Integration | `porcentajes/cotizaciones`, crear/editar porcentaje | Catálogo restante con datos | Global; presentación del contrato actual | 4 | Bajo | No cambiar porcentajes ni semántica fiscal/comercial |

La alternancia operativo/CRUD se sostiene en fases 1–4. No se fuerza un tercer operativo inseguro para rellenar el roadmap. Los últimos cuatro mantenedores son pequeños y acotados; pueden repriorizarse si DEV libera una frontera operativa segura.

**NEXT SCREEN RECOMMENDED: Compras General V2 — Read-only Integration**

**SECOND: Contactos — Configuration CRUD**

**THIRD: Compras Express V2 — Read-only Integration**
