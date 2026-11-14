# REAL NEXT — Salidas — Operational Frontend Integration

Fecha: 2026-11-13. Auditoría focalizada; **ninguna superficie integrada** por lookups/guards pendientes. Sin stock, documentos ni commit.

## Real Domain Meaning

OutputController / Output / OutputDetail: solicitudes y salidas de materiales para órdenes de ejecución/cotizaciones y solicitudes de área (ors). Usuarios solicitante/responsable, atención/confirmación, selección de Items y devoluciones. No es WarehouseTransfer ni una única salida POS. No extrapolar contratos de Entries.

Vistas vigentes: index_output_request_v2, index_output_request, index_output, index_output_confirmed, index_output_simple, index_my_output_simple; create_output_request, create_output_request_order/extra, create_output_simple/activos, create_item_custom; reportes materiales/área/cotización. JS homónimos en public/js/output. Menú separa Solicitudes, Salidas y Solicitud de área.

## Surface Matrix

Prefijo `/dashboard`; READ no certifica ausencia de Audit cuando se señala.

| Surface | Route | Controller action | Permission | Scope | Read/Mutation | Decision |
|---|---|---|---|---|---|---|
| Solicitudes V2 | listado/solicitudes/salida/v2 + get/all/outputs/requests/v2/{numberPage} | indexOutputRequestV2/getAllOutputsRequestsV2 | list_request | Output tenant; lookup User global | Read | BACKEND SYNC REQUIRED |
| Solicitudes legacy | solicitudes/salida + get/json/output/request | indexOutputRequest/getOutputRequest | vista list_request; JSON sin permiso específico | Output tenant | GET JSON escribe Audit | BACKEND SYNC REQUIRED |
| Main Salidas | salidas + mismo JSON | indexOutputs/getOutputRequest | vista list_output; JSON sin permiso específico | Tenant; sin company/branch | GET JSON escribe Audit | BACKEND SYNC REQUIRED |
| Confirmadas | salidas/confirmadas + get/json/output/confirmed y get/json/outputs/filters/confirmed | indexOutputsConfirmed/getOutputConfirmed/getOutputsFilterConfirmed | vista list_output; JSON sin permiso específico | Tenant + selector User global | Read | BACKEND SYNC REQUIRED |
| Create normal | crear/solicitud | createOutputRequest | create_request | User::all global | Read | BACKEND SYNC REQUIRED |
| Create por orden/extra | crear/solicitud/orden/{id_quote}; crear/solicitud/extra/{output} | createOutputRequestOrder/Extra | create_request | Dependencias quote/items; User global | GET con Audit | BACKEND SYNC REQUIRED |
| Área / Mis solicitudes | solicitudes/area; mis/solicitudes/area + get/json/output/simple; get/json/my/output/simple | indexOutputSimple/indexMyOutputSimple/getOutputSimple/getMyOutputSimple | vistas list_requestSimple / myRequest_requestSimple; JSON sin gate específico | Tenant; Mis añade requesting_user=Auth.id | GET JSON escribe Audit | BACKEND SYNC REQUIRED |
| Create área/activos | crear/solicitud/area; crear/solicitud/area/activos | createOutputSimple/Activo | create_requestSimple | User enable global | Read | BACKEND SYNC REQUIRED |
| Detail filas | get/json/items/output/{output_id}; equivalentes simple/devolver/areas | getJsonItemsOutputRequest/Simple y variantes | Sin permiso específico en rutas | OutputDetail tenant; parent/action sin guard suficiente | GET con Audit en actions revisados | BACKEND SYNC REQUIRED |
| Item personalizado | crear/item/personalizado/{id_detail} | createItemCustom | Sin permiso específico | OutputDetail tenant; lookups adicionales | Read | BACKEND SYNC REQUIRED |
| Edit convencional | No existe vista edit Output | edición ejecución/descripción por POST | Ver matriz inferior | No certificado | Mutation | OUT OF SCOPE |
| Deleted/Cancelled | No listado dedicado/SoftDeletes en Output | — | — | — | — | OUT OF SCOPE |
| Atención/confirmación/devolución/borrado | POST familia output | actions correspondientes | Según acción | No certificado | Mutation | OUT OF SCOPE |
| Reportes material/área/quote/export | reporte/material/salidas y variantes | reportMaterialOutputs/Simple/ByArea; reportOutputsByQuote | permisos de reporte reales, no habilitados en esta fase | Dependencias legacy; varios GET Audit | Read/export | OUT OF SCOPE |
| Mantenimiento | modificando/takens | modificandoMaterialesTomados | Sin permiso específico | MaterialTaken según consulta existente | GET guarda cambios | OUT OF SCOPE |

## Routes

Nombres principales: output.request.indexV2/index/create/order.create/extra.create; output.confirm (vista salidas); output.index.confirmed; output.simple.index/my.index/create/create.activos; create.item.custom. No confundir nombre output.confirm con POST output.confirmed. No routes/allowlist modificadas.

## Permissions

Middleware común auth/check.user.enabled/password.changed/tenant.context. Rutas JSON legacy no heredan automáticamente los permisos de las vistas.

| Action | Permission |
|---|---|
| Solicitudes/lista V2 | list_request |
| Salidas/confirmadas vistas | list_output |
| Crear solicitud | create_request |
| Listar área / Mis área | list_requestSimple / myRequest_requestSimple |
| Crear área | create_requestSimple |
| Atender solicitud | attend_request OR attend_requestSimple |
| Confirmar solicitud | confirm_output OR confirm_requestSimple |
| Borrado total/parcial solicitud; confirmar todas | confirm_output |
| Atender/confirmar área | attend_requestSimple / confirm_requestSimple |
| Borrar área | delete_requestSimple |
| JSON legacy y detalle, edición execution/description, varias devoluciones/asignaciones | Sin permission específico declarado en las rutas revisadas; no equivalen a públicos (sí middleware común) |

No se cambian roles ni se presupone bypass Owner. No se ejecutan POST ni pruebas HTTP negativas sobre actions mutadores.

## Scope

Output y OutputDetail usan BelongsToTenant: filtro tenant con contexto presente. Output tiene company_id/branch_id, OutputDetail company_id/warehouse_id; presencia de columnas NO implica filtro. Listas revisadas no where company/branch/warehouse. requestingUser/responsibleUser relacionan User sin scope propio. Mis área añade usuario solicitante, sin convertirlo en Worker.

TenantScope sin tenant no restringe: no certificar Platform Admin/contexto ausente. No se afirma que company-wide/tenant-wide sea una decisión de producto aprobada; requiere definición backend (INV-01). No hacer filtro JS.

## Dataset

SQL/modelo dentro de START TRANSACTION READ ONLY, terminado con ROLLBACK; ningún action que escriba Audit fue llamado.

- 119 Output físicos, todos tenant1/company1/state=confirmed/indicator=or.
- IDs exactos: enteros1–125 **excepto11,30,41,45,66,112**.
- Branch1:118 filas; fechas request_date entre2026-01-12 y2026-11-04. Branch8: ID119, fecha2026-10-24 18:35:48.
- 110 OutputDetail: warehouse1=54, warehouse8=6, warehouse9=1, warehouseNULL=49; todos T1/C1. No se reconstruye warehouse histórico faltante.
- Solicitante/responsable por ID: 1/1=104 filas; 2/2=6; 3/3=2; 4/4=7. No se sustituyen por trabajadores.

| Contexto de modelo | IDs visibles | find1/find119 | find999999 |
|---|---|---|---|
| EDESCE T1/C1 | Los119 IDs anteriores | ambos presentes | null |
| EDESCE T1/C2 | Los mismos119 | ambos presentes | null |
| Gamarra T3/C3 | [] | ambos null | null |

Nivel de evidencia: modelo con sesión contextual, no certificación HTTP/roles. Tenant excluye IDs EDESCE de Gamarra en estas lecturas; company no los restringe. Branch8 existe y se incluye junto a branch1, sin scope branch en query. No llamadas de detalle UI para generar Audit.

V2 action de lectura pura, página1 bajo los tres contextos: data=[], totalPages0,startRecord1,endRecord0; excluye confirmed, de modo que dataset visible0 es coherente. Metadata invertida amplía GENERAL-01, no nuevo finding. Confirmadas tienen119 filas, pero multipágina UI NO ejecutada por bloqueo. **MULTIPAGE QA pendiente de backend para confirmadas; PENDING DATASET para solicitudes no confirmadas**.

## Warehouse

No aplicar reglas de WarehouseTransfer. Selección legacy a través de Item.location; endpoints complete/scraped filtran material_id y state_item, heredan tenant pero no fijan local/company en esas consultas. No garantía de “origen local”/“general” contractual. Lookup get/locations sí restringe company y relación warehouse; eso no corrige los otros endpoints ni parent Output.

## Materials / StockItems / Items

Material = maestro, tenant-scoped; get/materials filtra enable_status y excluye category8; get/materials/entry usa habilitados. Exponen precio/stock_current legacy; no se reinterpretó como disponibilidad moderna.

Item = unidad física/retazo, tenant-scoped; complete usa entered, scraped usa scraped; relación location aporta área/almacén/anaquel/nivel/contenedor. Create/item_custom consume también ubicaciones/entradas/materiales. OutputDetail posee item_id/material_id y campos modernos stock_item_id/stock_lot_id/warehouse_id; esto NO convierte la UI legacy en selector StockItem. No se reutilizan endpoints POS ni se ensayan reservas/kardex/lotes.

## Worker/User/Area

Create normal User::all; área User where enable1; JS consume get/users (USER-01). Confirmadas/V2 enumeran id/name global. Worker se usa en área para derivar AreaWorker desde responsible_user; Worker tiene tenant, AreaWorker es maestro global legacy. No intercambiar User/Worker ni atribuir company por inferencia.

## Filters

V2: year,code,execution_order,quote,code_quote,description_quote,type,state,requesting_user,responsible_user,startDate,endDate. Confirmadas: start/end/id/order_execution/requesting_user. Legacy DataTables búsqueda/orden/paginación cliente. No controles nuevos.

Columnas reales: Solicitud/ID, orden ejecución o descripción, fecha, solicitante, responsable, estado, indicador de items personalizados, cotización; área en solicitudes simples; acciones. No agregados inventados.

## States

created/attended/confirmed: creada/atendida/confirmada según controller. indicator or/orn/ore/ors (regulares/normales/extras/área). No estado anulado inventado; Output no usa SoftDeletes. “custom” deriva de detalles con item_id NULL. No se homogeneizó con transferencias.

Requests StoreRequestOutputRequest exige execution_order string max255, request_date d/m/Y, requesting_user/responsible_user; StoreSimple permite execution_order nullable. Create usa estos names y details/items legacy; no se modificó payload ni se guardó.

## Included Views

Ninguna. Main legacy depende de JSON sin capability y con Audit; V2/confirmadas/create además usuarios globales; detalle/custom sin gate de acción suficiente. No se intentó salvarlas con ocultación JS. El alcance tenant existente se reconoce, no se etiqueta Output como modelo global.

## Excluded Mutations

Todo inventario: guardar/atender/confirmar/borrar/asignar/devolver, edición ejecución/descripción, items personalizados, batch confirm y MaterialTaken. GET modificando/takens guarda equipment_id: nunca ejecutado. GET que sólo escriben Audit se documentan como escritura auxiliar, no automáticamente exploit, y no se certifican read-only. DOCUMENT SECURITY — BACKEND/INFRA OPEN; no impresión/export/vale ni documentos abiertos.

## Responsive

NO APLICA: cero vistas integradas; no matriz ni screenshots falsos. Allowlist sin cambios. CSS nuevo0; frontend/backend intactos. Sólo documentación; git diff --check PASS, sin necesidad de compilar vistas.

## Backend Sync Notes

[OPEN] USER-01: nueva evidencia Output create/V2/confirmadas y get/users; mismo root cause, sin duplicarlo.

[OPEN] INV-01: frontera company/branch/warehouse no homologada en lecturas legacy. Dataset muestra mismos IDs al cambiar C1→C2; no inventar política ni corregir backend. Confirmar contrato antes del rollout.

[OPEN] OUTPUT-01: JSON/detail/custom sin capability explícita. DEV debe definir y aplicar permiso/ownership por acción; middleware de vista no protege JSON. Frontend can continue: NO para superficies dependientes.

[OPEN] OUTPUT-02: GET modificando/takens cambia MaterialTaken. Retirar o convertir en operación autorizada explícita; no ejecutado. Evidencia de código, no prueba de explotación.

[OPEN] GET auxiliares escriben Audit (getOutputRequest, getOutputSimple/getMyOutputSimple, detalles revisados y create por orden/extra); distinguir auditoría de movimiento de negocio. No read-only certificado; sin nuevo bug por el mero logging.

[OPEN] GENERAL-01: V2 devuelve rango1–0 con cero pendientes. Mismo cálculo de rango, evidencia actual READ ONLY; no duplicar.

## Estado final

Main List / Confirmed / Detail / Create / lecturas auxiliares: **BACKEND SYNC REQUIRED**.
Edit convencional y Deleted/Cancelled: no existen como vistas dedicadas; edición por POST excluida.
Inventory mutations **OUT OF SCOPE**. Documents **BACKEND/INFRA OPEN**.
Backend / Frontend **UNCHANGED**. No commit. No otra familia seleccionada arbitrariamente.
