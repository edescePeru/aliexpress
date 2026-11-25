# REAL NEXT — R3.1 — Normal & Express Purchase Functional Parity Recovery

## R3.1 — reconciliación de alcance

General y Express: LEGACY — OWNER CONFIRMED — NO FURTHER RECOVERY. FR-022…034 y FR-080 (14) excluidos de recuperación activa, incluidas las implementaciones completas/parciales históricas que permanecen en código. Normal y papelera siguen vigentes: FR-019/020/021/035 recuperadas; FR-017/018 (preload compartido) y FR-036 (PUR-R3-02) pendientes DEV. No se encontraron enlaces nuevos desde las superficies activas modificadas en R3.1 hacia General/Express/Finance: Normal usa show/print/estado/anulación normales; papelera show histórico/restore normales. La impresión compartida se invoca con ID normal. No se revierte código ni se toca sidebar.

El resto conserva la evidencia anterior al cierre; sus estados provisionales no son la decisión vigente.


Fecha: 2026-11-24. HEAD auditado: `3914020`, rama `feature/venti-next-frontend`.

## Resultado y límites

**PARTIAL FUNCTIONAL PARITY — SAFE OPERATIONS RESTORED / DEV PENDING**.
6 superficies revisadas, 4 intervenidas. Inventario operacional agrupado: 34 capacidades (13 ya disponibles + 21 pérdidas). Las 20 pérdidas originales siguen con IDs estables; se añade FR-080, detalle Express desde General. Recuperadas completamente 7; parcialmente 2 (General, sólo filas normales). Pendientes completas 12, más las variantes Express de esas 2 capacidades parciales: 14 capacidades no cerradas. No sumar una capacidad parcial como cierre completo.

Decisión explícita del usuario en R3.1: **Documentar pendientes de DEV**. No colocar controles informativos ni ejecutar destinos inseguros. No hay retiros aprobados. No se modernizaron formularios destino ni se amplió a Finance/Entries.

## Evidencia Git y superficies

| Superficie | Ruta / URL local | Controller | Blade / JS | Homologación / baseline |
|---|---|---|---|---|
| Normal V2 | `order.purchase.normal.indexV2` — http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2 | indexNormalV2 / getDataOrderNormal | normal_v2 / normalV2.js | abc9daa / abc9daa^ |
| General V2 | `order.purchase.general.indexV2` — http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2 | indexV2 / getDataOrderGeneral | general_v2 / generalV2.js | b1fa9d5 / b1fa9d5^ |
| Express V2 | `order.purchase.express.indexV2` — http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2 | indexExpressV2 / getDataOrderExpress | express_v2 / expressV2.js | 3ed0b46 / 3ed0b46^ |
| Papelera Normal | `order.purchase.delete` — http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas | indexOrderPurchaseDelete / getOrderDeleteGeneral | deleteGeneral / deleteGeneral.js | c32352c / c32352c^ |
| Detalle Normal, sin cambios | sin nombre — http://127.0.0.1:8080/dashboard/ver/orden/compra/normal/4 | showOrderPurchaseNormal | showNormal / sin adapter operativo | abc9daa / abc9daa^ |
| Detalle eliminado, sin cambios | sin nombre — http://127.0.0.1:8080/dashboard/ver/orden/compra/eliminada/1 | showOrderPurchaseDelete | showDelete / sin adapter operativo | c32352c / c32352c^ |

Todos los controllers pertenecen a `OrderPurchaseController`. Blades en `resources/views/orderPurchase/`, JS en `public/js/orderPurchase/`. Contraste: padres operativos, commits de integración, HEAD, legacy indexNormal/indexGeneral/indexExpress y controller/model/routes actuales. Los detalles no contenían una acción ejecutable de restauración/impresión propia: no inventarla allí. `order.purchase.list.indexV2` pertenece al flujo de Entradas/R4, no se intervino.

## Inventario ya disponible

La unidad contable es capacidad por pantalla, agrupando campos de una misma consulta; no se cuentan iconos, cada columna o un callback como capacidades independientes.

| ID local | Pantalla | Capacidad | Estado |
|---|---|---|---|
| A01 | Normal | Consulta y filtros: código, año, proveedor, cotización, estado, llegada, inicio/fin | ALREADY AVAILABLE |
| A02 | Normal | Paginación server-side | ALREADY AVAILABLE |
| A03 | Normal | Ver orden normal | ALREADY AVAILABLE |
| A04 | General | Consulta y filtros anteriores + tipo | ALREADY AVAILABLE |
| A05 | General | Paginación server-side | ALREADY AVAILABLE |
| A06 | General | Ver orden normal | ALREADY AVAILABLE |
| A07 | Express | Consulta y filtros existentes | ALREADY AVAILABLE |
| A08 | Express | Paginación server-side | ALREADY AVAILABLE |
| A09 | Papelera | Consulta, búsqueda y longitud DataTables | ALREADY AVAILABLE |
| A10 | Papelera | Paginación DataTables | ALREADY AVAILABLE |
| A11 | Papelera | Ver detalle eliminado | ALREADY AVAILABLE |
| A12 | Detalle normal | Consulta de cabecera y líneas | ALREADY AVAILABLE |
| A13 | Detalle eliminado | Consulta histórica de cabecera y líneas | ALREADY AVAILABLE |

No se encontraron controles alcanzables de Column Visibility, acciones masivas, aprobación, recepción, regularización ni navegación a Entries en los cuatro baselines V2/papelera. No se inventaron. `exportExcel` existe en Normal/Express legacy JS pero no hay `btn-export` en sus Blades: **HISTORICAL REACHABILITY PENDING**, no pérdida confirmada. El selector de estado de papelera tenía su listener/función comentados: no operación ejecutable que recuperar. Funciones de edición de facturación copiadas en JS sin trigger/form alcanzable tampoco se cuentan como capacidades retiradas. Lost/Regularized/reportes siguen con sus findings previos, sin rehabilitarlos ni auditar módulos completos.

## Matriz de recuperación

Antes de R3.1 todas las filas FR siguientes estaban ausentes de la UI. `R` = RESTORED, `B` = BACKEND BLOCKER. `R/B` = restaurada sólo la variante Normal. Permisos abreviados N/E = sufijo `orderPurchaseNormal` / `orderPurchaseExpress`. Las rutas se prefijan con `/dashboard/`. No hay condiciones inventadas por estado: backend mantiene editable/recepciones/contexto como autoridad.

| ID | Pantalla | Original | Antes | Permiso | Condición | Endpoint | Handler | Blocker | Recuperación | QA |
| FR-017 | Normal | Crear | FRONTEND RECOVERY REQUIRED | create_N | Permiso y orden contextual del tipo correspondiente | GET crear/orden/compra/normal | toolbar | preload StockItems global | B | Auditoría estática; ejecución no realizada |
| FR-018 | Normal | Editar | FRONTEND RECOVERY REQUIRED | update_N | Permiso y orden contextual del tipo correspondiente | GET editar/orden/compra/normal/{id} | data-editar | preload StockItems global | B | Auditoría estática; ejecución no realizada |
| FR-019 | Normal | Anular | FRONTEND RECOVERY REQUIRED | destroy_N | Permiso y orden contextual del tipo correspondiente | POST destroy/order/purchase/normal/{id} | data-delete | — | R | Cableado/renderer aislado PASS; ejecución real PENDING |
| FR-020 | Normal | Estado | FRONTEND RECOVERY REQUIRED | update_N | Permiso y orden contextual del tipo correspondiente | GET get/state/order/purchase/{id}; POST change/state/order/purchase | data-estado / btn-changeState | — | R | Cableado/renderer aislado PASS; ejecución real PENDING |
| FR-021 | Normal | Imprimir | FRONTEND RECOVERY REQUIRED | list_N | Permiso y orden contextual del tipo correspondiente | GET imprimir/orden/compra/{id} | data-imprimir | QA documental pendiente | R | Cableado/renderer aislado PASS; ejecución real PENDING |
| FR-022 | General | Crear Normal | FRONTEND RECOVERY REQUIRED | create_N | Permiso y orden contextual del tipo correspondiente | GET crear/orden/compra/normal | toolbar | preload StockItems global | B | Auditoría estática; ejecución no realizada |
| FR-023 | General | Crear Express | FRONTEND RECOVERY REQUIRED | create_E | Permiso y orden contextual del tipo correspondiente | GET crear/orden/compra/express | toolbar | User::all; Quote sin company | B | Auditoría estática; ejecución no realizada |
| FR-024 | General | Editar N/E | FRONTEND RECOVERY REQUIRED | update_N / update_E | Permiso y orden contextual del tipo correspondiente | GET editar/orden/compra/normal|express/{id} | data-editar | lookups destino | B | Auditoría estática; ejecución no realizada |
| FR-025 | General | Anular N/E | FRONTEND RECOVERY REQUIRED | destroy_N; Express legacy destroy_E / ruta update_E | Permiso y orden contextual del tipo correspondiente | POST destroy/order/purchase/normal/{id}; Express pendiente | data-delete | Express: tipo/atomicidad; histórico enviaba a Normal | R/B | Cableado/renderer aislado PASS; ejecución real PENDING |
| FR-026 | General | Estado N/E | FRONTEND RECOVERY REQUIRED | update_N; Express legacy update_E | Permiso y orden contextual del tipo correspondiente | GET get/state/order/purchase/{id}; POST change/state/order/purchase | data-estado / btn-changeState | endpoint actual sólo Normal | R/B | Cableado/renderer aislado PASS; ejecución real PENDING |
| FR-027 | General | Imprimir N/E | FRONTEND RECOVERY REQUIRED | list_N (ruta actual) | Permiso y orden contextual del tipo correspondiente | GET imprimir/orden/compra/{id} | data-imprimir | QA documental pendiente | R | Cableado/renderer aislado PASS; ejecución real PENDING |
| FR-028 | General | Exportar Excel | FRONTEND RECOVERY REQUIRED | list_E | Permiso y orden contextual del tipo correspondiente | GET exportar/reporte/ordenes/compra/v2/?start=&end= | btn-export | QA export pendiente | R | Cableado/renderer aislado PASS; ejecución real PENDING |
| FR-029 | Express | Crear | FRONTEND RECOVERY REQUIRED | create_E | Permiso y orden contextual del tipo correspondiente | GET crear/orden/compra/express | toolbar | lookups globales | B | Auditoría estática; ejecución no realizada |
| FR-030 | Express | Editar | FRONTEND RECOVERY REQUIRED | update_E | Permiso y orden contextual del tipo correspondiente | GET editar/orden/compra/express/{id} | data-editar | lookups globales | B | Auditoría estática; ejecución no realizada |
| FR-031 | Express | Anular | FRONTEND RECOVERY REQUIRED | legacy destroy_E; ruta actual update_E | Permiso y orden contextual del tipo correspondiente | POST destroy/order/purchase/express/{id} | data-delete (no reconectado) | tipo no validado / sin atomicidad | B | Auditoría estática; ejecución no realizada |
| FR-032 | Express | Estado | FRONTEND RECOVERY REQUIRED | legacy update_E; actual update_N | Permiso y orden contextual del tipo correspondiente | GET get/state/order/purchase/{id}; POST change/state/order/purchase | data-estado (no reconectado) | guard order restringe type=n | B | Auditoría estática; ejecución no realizada |
| FR-033 | Express | Imprimir | FRONTEND RECOVERY REQUIRED | list_N (ruta actual) | Permiso y orden contextual del tipo correspondiente | GET imprimir/orden/compra/{id} | data-imprimir | QA documental pendiente | R | Cableado/renderer aislado PASS; ejecución real PENDING |
| FR-034 | Express | Detalle | FRONTEND RECOVERY REQUIRED | ruta list_E; legacy UI list_N | Permiso y orden contextual del tipo correspondiente | GET ver/orden/compra/express/{id} | data-ver_orden (no reconectado) | User::all; binding sin type / find | B | Auditoría estática; ejecución no realizada |
| FR-035 | Papelera | Restaurar | FRONTEND RECOVERY REQUIRED | destroy_N | Permiso y orden contextual del tipo correspondiente | POST restore/order/purchase/delete/{id} | data-restore | — | R | Cableado/renderer aislado PASS; ejecución real PENDING |
| FR-036 | Papelera | Imprimir eliminado | FRONTEND RECOVERY REQUIRED | list_N | Permiso y orden contextual del tipo correspondiente | GET imprimir/orden/compra/eliminada/{id} | data-imprimir (no reconectado) | query excluye soft-deleted | B | Auditoría estática; ejecución no realizada |
| FR-080 | General | Detalle Express | FRONTEND RECOVERY REQUIRED | ruta list_E; legacy UI list_N | Permiso y orden contextual del tipo correspondiente | GET ver/orden/compra/express/{id} | data-ver_orden (no reconectado) | mismo destino inseguro que FR-034 | B | Auditoría estática; ejecución no realizada |

## Contratos preservados y adaptaciones

- Lists: tenant + company mediante `ScopedPurchaseCompany`; branch no es ownership de estas órdenes. Rutas y backend sin cambios.
- Fuente de permisos de filas: `$permissions` de controller (`getPermissionsViaRoles`), preservada. Toolbar Excel usa `@can(list_orderPurchaseExpress)`, coherente con la ruta. No Owner bypass. No certificar aquí la asignación directa que el array legacy no incluye.
- Normal delete, restore y state: `NormalPurchaseGuard::context` exige `X-Purchase-Tenant` y `X-Purchase-Company`. Se reutiliza **companyContext.js**, ya existente, y contexto generado por TenantContext, no por filtros. CSRF en todos los POST.
- Modal conserva `modalState`, `formStates`, `orderPurchase_id`, `stateOrder`, `btn-changeState`, valores `stand_by/send/pick_up`. GET carga estado actual; POST FormData al endpoint V2 original; error backend visible. Cancelar no envía. Prevención de doble confirmación/envío y refresh a página1, como legacy.
- Restore: endpoint vigente sólo normales eliminadas; guard, transacción y conciliación permanecen backend. Se recarga DataTables sin duplicar filas. No restore real.
- Print activo: URL original, permiso real list_orderPurchaseNormal, `_blank` con noopener; parent usa scope tenant/company y SupplierAccount tenant. No convertir el QA pendiente de documento en retiro de su enlace. No se generó PDF ni se certificó seguridad documental/infra.
- Excel: únicamente General tenía botón alcanzable; conserva `start/end` d/m/Y y confirmación si faltan fechas. Export backend consulta órdenes de la empresa; no se añade filtro/tipo que su contrato no recibe. Cancelación probada; descarga no ejecutada.
- Se conserva tipo Normal/Express; NO reutilizar endpoint Normal para anular/cambiar estado Express. Los controles pendientes no se vuelven a enlazar.
- DataTables de papelera, paginación manual de V2, filtros y precios sin reescritura. Sin nuevos endpoints, dependencias, CSS o cambios a allowlist.

## Blockers y decisión de producto

1. Normal Create/Edit: `createNormalV2.js:28` y equivalente Edit invocan `/get/materials/stock/items/entry`; route1098 no tiene permiso propio; `MaterialController::getJsonMaterialStockItemsEntry:4736` carga StockItem + inventoryLevels sin enabledForCompany/company. Finding existente INV-01 / paquete Q Other purchase dependencies: no duplicarlo.
2. Express Create/Edit/Show: `OrderPurchaseController:900,1358,1491` mantiene User::all y Quote sin company en forms; Show usa find sin type. Mantener paquete Q; destino legacy NO equivale por sí solo a blocker: aquí sí hay evidencia de lookups/binding.
3. Express state: controller740/746 delega al guard Normal, que exige type=n. No existe contrato Express equivalente que pueda inventar frontend. Gap funcional explícito a resolver por DEV; no restaurar el cruce original de dominios.
4. **PUR-R3-01**: destroyOrderPurchaseExpress1859 usa find sin type y elimina detalles secuencialmente sin transacción; puede aceptar orden Normal contextual por ID, sin NormalPurchaseGuard::editable/context, y puede borrar líneas previas antes de detectar otra recibida. Demostración por código, no se ejecutó el daño. Debe validar type=e, autorización canónica/ownership, prevalidar y hacer rollback atómico.
5. **PUR-R3-02**: printOrderPurchaseDelete2445 usa query activa, no onlyTrashed; ID1 existe eliminado pero la query no lo encuentra. La siguiente lectura supplier_id falla. DEV debe usar binding normal eliminado contextual y fail closed. No se abrió PDF.

Usuario eligió **documentar pendientes de DEV**, no añadir placeholders; no retiro permanente. No quedan decisiones de presentación pendientes; la definición del contrato de estado/anulación Express corresponde a DEV/producto. Lost, Regularized y Material Report no aparecen como acciones alcanzables de estos baselines; sus deudas conocidas no se resuelven ni duplican.

## Dataset real y lectura aislada

Transacción MySQL READ ONLY, PDO dedicado, rollback final; usuarios existentes Owner EDESCE y Owner Gamarra, sin contraseñas ni nuevas cuentas. Audit::creating cancelado únicamente dentro del proceso QA para impedir escrituras técnicas: NO runtime ni backend alterado. Renderer real de Laravel y permission middleware explícito, no una sesión HTTP end-to-end certificada.

| Contexto | Normal activo / General | Express | Papelera |
|---|---|---|---|
| T1/C1/B1 EDESCE | 4,3,2 | vacío | 1 |
| T1/C2/B2 EDESCE | vacío | vacío | vacío |
| T3/C3/B4 Gamarra | vacío | vacío | vacío |

Estados: 2 stand_by; 3/4 pick_up. ID1 eliminado. GET estado 2/3/4 propio devuelve estado; 1 eliminado y999999 → ModelNotFound; 1/2/3/4/999999 fuera de C1 → ModelNotFound (404 contractual). No inventar dato Express: el renderer Express positivo se validó con fixture JS SIMULADO999 fuera de DB. No multipágina real con 3 registros.

## Cobertura anterior y ampliación

FR017–036 estaban inventariadas pero excluidas de recuperación por paradigma read-only; docs anteriores cubrían principalmente list/empty/detail y auditoría estática de destinos, no mutaciones. R3.1 separa ejecución QA de existencia del control. Anular/estado/restore ahora tienen pruebas de payload, CSRF/context y respuestas interceptadas, no certificación transaccional real. Print/export conservan auditoría estática + enlace/confirmación, ejecución pendiente. FR080 no había sido contabilizada individualmente. El tipo/atomicidad de delete Express y el query de print eliminado no tenían findings específicos demostrados en el backlog anterior: se añaden PUR-R3-01/02, sin duplicar INV-01 ni el paquete Q.

## Visual parity / QA

Patrón reutilizado: Material V2, R1.1 y R2: `.next-row-actions`, `.next-row-actions-trigger`, `.next-row-actions-menu`, `.next-row-action-item-icon`, Bootstrap dropdown/modal y Surface Spacing. Iconos: ver `fa-eye`, imprimir `fa-print`, estado `fa-exchange-alt`, anular `fa-trash` con text-danger, restaurar `fa-trash-restore`, Excel `fa-file-excel`. Cero CSS nuevo. No estilos por pantalla.

Chrome headless aislado, HTML renderizado por Laravel con dataset real y assets locales permanentes; toda red interceptada (no se controló navegador local). Cuatro listados x320/375/390/576/768/992/1200/1440: 32 PASS, overflow global0, iconos completos. Modal de estado:8 anchos/Escape; fixture Express y cancelación Excel. Cuatro POST simulados: estado, anular, restaurar422 y restaurar200; un envío por confirmación, contexto/CSRF presente, error422 visible, refresh correcto. Sin errores JS en suite principal. Validación sintáctica JS y Blade compile PASS. Mutaciones/documentos/Excel reales **PENDING**, no ejecutados.

Artefactos fuera del repositorio en `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`:
- r3-read.php / r3-data.json: dataset y render/controller en READ ONLY.
- r3-browser.cjs / r3-browser-results.json: responsive, permisos, iconos, payloads simulados.
- r3-extra.cjs / r3-extra-results.json: modal y evidencia adicional.
- r3-normal-375.png, r3-normal-1440.png, r3-general-375.png, r3-general-1440.png, r3-trash-375.png, r3-trash-1440.png: después.
- r3-state-375.png / r3-state-1440.png: modal estabilizado; r3-restore-error.png: error simulado.
- r3-express-simulated.png: fixture identificado; no dataset real Express.
- r3-before-normal/general/express/trash.png: renderers HEAD previos reproducidos contra shell Blade renderizado; evidencia del área de acciones, no una sesión histórica ni screenshot completo anterior auténtico.

## Archivos intervenidos

- public/js/orderPurchase/list-operations.js (nuevo adapter compartido).
- public/js/orderPurchase/normalV2.js, generalV2.js, expressV2.js, deleteGeneral.js.
- resources/views/orderPurchase/normal_v2.blade.php, general_v2.blade.php, express_v2.blade.php, deleteGeneral.blade.php.
- resources/views/orderPurchase/partials/list-operations.blade.php y list-operations-scripts.blade.php (nuevos).
- Este informe, backlog canónico y backlog DEV.

Backend changes:0. CSS changes:0. Allowlist changes:0. Sin commit/push/merge. `git diff --check`: PASS. R3.2 no iniciada.


## R3.2 — verificación breve de vigencia y corrección de URL

2026-11-24. La entrega en chat de R3.1 enlazó erróneamente Compras generales a `/dashboard/listado/general/cotizaciones/v2` (QuoteController@indexV2). La URL correcta es **http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2**, route order.purchase.general.indexV2 / OrderPurchaseController@indexV2. Este documento y los endpoints implementados ya usaban Compras; no se modificaron rutas.

Normal y papelera mantienen navegación activa en appAdmin2 (2090–2116); sus acciones restauradas se prueban alcanzables en el baseline inmediato previo y mantienen contratos actuales. No se detectó sustituto ni ruta retirada para anulación/estado/impresión Normal o restore.

General/Express: df5c53f (2026-10-23) elimina enlaces de sidebar que ya estaban comentados en su padre. Ese cambio es anterior a las homologaciones. La ocultación del menú es un hecho; su motivo y la vigencia de las acciones internas no pueden inferirse sólo de ese hecho. Se consultó explícitamente al propietario bajo PRODUCT DECISION REQUIRED — LEGACY STATUS UNCERTAIN. No se revierten controles de R3.1 ni se reactivan enlaces de sidebar unilateralmente. No se repitió QA R3.1.
