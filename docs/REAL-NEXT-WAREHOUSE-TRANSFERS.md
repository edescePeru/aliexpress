# REAL NEXT — Warehouse Transfers

Fecha: 2026-11-05. HEAD auditado: `9ad0106` (`front cash boxes and subtypes`). Árbol limpio al comenzar. No commit.

| Superficie | Estado |
|---|---|
| Transfer List | FRONTEND INTEGRATED |
| Transfer Detail | FRONTEND INTEGRATED |
| Request Form | FRONTEND INTEGRATED — configuración QA de destinos pendiente |
| Prepare / Dispatch / Receive / Cancel | FRONTEND CONTRACT INTEGRATED |
| Mutaciones de inventario | MUTATION QA PENDING |
| Transferencias persistidas | REAL DATA QA COVERAGE PENDING |

No se modificaron backend, state machine, scopes, permisos, stock ni CSS. No se crearon transferencias. La aprobación frontend no equivale a aprobación productiva del ciclo de inventario.

## Contract Audit

Se revisaron las fuentes actuales: WarehouseTransferController, WarehouseTransferOperationRequest, WarehouseTransferAccess, WarehouseTransferRequestService, WarehouseTransferPreparationService, WarehouseTransferMovementService, WarehouseTransferCostResolver, TransferRecord y los siete modelos WarehouseTransfer*, Warehouse/Location/StockItem/StockLot y referencias de Item, contexto/middleware, rutas, tres Blades, parciales y operations.js. Se consultó el contrato auxiliar de disponibilidad y el patrón aprobado de formularios/listados.

El módulo activo es `warehouseTransfer`, distinto del legacy `transfer` cuyas rutas antiguas están comentadas. No se portaron sus endpoints ni JS. No hay delete, edición general de solicitud, impresión, documento ni endpoint HTTP summary en el grupo activo. `summary()` es una lectura interna de MovementService consumida por show, no una acción mutadora.

## Routes

Prefijo P: `/dashboard/traslados/almacenes`. Nombres bajo `warehouseTransfer.`.

| Método | Ruta | Nombre | Controller action | Tipo |
|---|---|---|---|---|
| GET | P | index | index | READ |
| GET | P/crear | create | create | READ |
| GET | P/productos | products | products | READ |
| POST | P | store | store | MUTATION |
| GET | P/{id} | show | show | READ |
| POST | P/{id}/prepare | prepare | prepare | MUTATION |
| POST | P/{id}/dispatch | dispatch | dispatchTransfer | MUTATION |
| POST | P/{id}/receive | receive | receive | MUTATION |
| POST | P/{id}/cancel | cancel | cancel | MUTATION |

IDs de ruta numéricos. Grupo dashboard: auth, check.user.enabled, password.changed, tenant.context y middleware web/CSRF. Todo el grupo exige list_transfer; cada acción adicional exige su permiso propio.

**ALLOWLIST MATCH: YES**, tras añadir exclusivamente warehouseTransfer.index/create/show. Cargan venti.min.css, venti-next.css y JS compartido del shell. Endpoints JSON no requieren theme. Create conserva Select2/bootstrap4; operaciones conservan jQuery, jquery-confirm y AJAX. No se añadieron librerías.

## State Machine

Transiciones derivadas de servicios, no de colores. `allows()` resuelve permisos/contexto; los servicios validan estado bajo locks. Blade combina ambos criterios.

| Estado actual | Acción permitida | Estado siguiente | Permiso adicional | Restricciones | Efecto inventario |
|---|---|---|---|---|---|
| Sin solicitud | store | requested | create_transfer | origen distinto de destino; contexto, ubicaciones, productos/cantidades válidos | Ninguno; snapshots y líneas |
| requested | prepare | prepared | prepare_transfer | actor de origen o manageAll; disponibilidad conciliada, costos, lotes/Items válidos | Reserva lotes/nivel e Items |
| prepared | prepare (reintento de servicio) | prepared | prepare_transfer | autorización vigente | No duplica reserva |
| prepared | dispatch | dispatched | dispatch_transfer | origen o manageAll; asignaciones completas/reservadas | Reduce on_hand y reserved en origen; Items in_transit |
| dispatched / partially_received / received | dispatch (reintento) | mismo | dispatch_transfer | autorización vigente | No vuelve a descontar |
| dispatched / partially_received | receive parcial | partially_received | receive_transfer | destino o manageAll; cantidades positivas <= pendiente, moneda/Items válidos | Ingresa solo lo recibido al destino |
| dispatched / partially_received | receive restante completo | received | receive_transfer | todas las asignaciones recibidas, sin excesos | Completa ingreso y completed_at |
| Estado compatible con identidad de recepción previa | receive, misma UUID/payload/actor | sin nuevo ingreso | receive_transfer | hash e identidad deben coincidir; contexto sigue autorizado | Reutiliza recepción |
| requested | cancel | cancelled | cancel_transfer | origen o manageAll; también solicitante en destino mientras requested; motivo | Ningún movimiento; no admite asignaciones inesperadas |
| prepared | cancel | cancelled | cancel_transfer | origen o manageAll; motivo/reservas verificables | Libera solo reservas de ese traslado e Items |
| cancelled | cancel (reintento de servicio) | cancelled | cancel_transfer | autorización sigue aplicando; no toda cuenta conserva acceso al reintento | No vuelve a liberar |
| dispatched / partially_received / received | cancel | Rechazado | cancel_transfer no basta | Servicio exige devolución; no existe reversión en esta UI | Ninguno |

La UI ofrece prepare solamente en requested, dispatch en prepared, receive en dispatched/partially_received y cancel en requested/prepared, siempre con actions autorizadas. No ofrece botones de reintento de estados finales. Received/cancelled no muestran acciones operativas. «En tránsito» es una cantidad del summary, no un séptimo estado inventado.

## Permissions

A = WarehouseTransferAccess.context: usuario autenticado, no Platform Admin, tenant coincidente, company activa del tenant y pivot company_user activo; branch activa de company y pivot branch_user activo. No hay bypass por nombre de rol Owner.

| Operación | Ruta | Permiso | Access/Guard | Origen | Destino | Mutación |
|---|---|---|---|---|---|---|
| Listado | GET P | list_transfer | A + scopeVisible | visible por branch snapshot | visible por branch snapshot | READ |
| Detalle | GET P/{id} | list_transfer | A + scopeVisible + authorize(view) | visible | visible | READ |
| Formulario | GET P/crear | list_transfer + create_transfer | A; catálogo contextual | company actual | branch activa salvo manageAll | READ |
| Productos | GET P/productos | list_transfer + create_transfer | A + warehouse + FormRequest snapshot | warehouse company | no recibe destino | READ |
| Solicitar | POST P | list_transfer + create_transfer | A + authorizeCreate + FormRequest + servicio/modelos | company actual | branch activa salvo manageAll | MUTATION |
| Preparar | POST P/{id}/prepare | list_transfer + prepare_transfer | A + authorize + estado bloqueado | branch origen o manageAll | warehouse válido | MUTATION |
| Despachar | POST P/{id}/dispatch | list_transfer + dispatch_transfer | A + authorize + servicio | branch origen o manageAll | warehouse válido | MUTATION |
| Recibir | POST P/{id}/receive | list_transfer + receive_transfer | A + authorize + UUID/hash + servicio | warehouse válido | branch destino o manageAll | MUTATION |
| Cancelar | POST P/{id}/cancel | list_transfer + cancel_transfer | A + authorize + servicio | origen o manageAll | solo requested y requested_by propio sin manageAll | MUTATION |
| Summary | Interno desde show | hereda list_transfer + acceso show | TransferRecord scope tenant/company | hereda visibilidad | hereda visibilidad | READ |
| Impresión/documento/delete | No existe | N/A | no se inventa | N/A | N/A | N/A |

manageAll_transfer amplía alcance de branch dentro de la company; no reemplaza permisos de acción ni A ni condiciones de estado. La autorización se ejecuta otra vez sobre el registro bloqueado en operate().

## Tenant / Company / Branch Scope

TransferRecord usa global scope tenant/company que retorna cero filas sin ambos valores. Prohíbe cambiar identidad/contexto y borrar registros; recepciones son append-only. WarehouseTransfer guarda snapshots inmutables de origen/destino, branch/location, actor, fecha y UUID/hash. Las relaciones de almacén también restringen company.

Sin manageAll, scopeVisible = source_branch_id o destination_branch_id igual a branch activa. Un almacén general tiene branch null y por sí solo no concede visibilidad. Owner no implica manageAll.

| Cuenta / QA | Tenant | Company | Branch | Orígenes | Destinos elegibles | Traslados |
|---|---:|---:|---:|---|---|---:|
| Owner EDESCE, navegador + harness | 1 | 1 | 1 | almacén 1, general | ninguno | 0 |
| Owner EDESCE, solo harness | 1 | 5 | 6 | almacén 7, general | ninguno | 0 |
| Owner Gamarra, navegador + harness | 3 | 3 | 4 | almacén 4, general | ninguno | 0 |

Ambos Owners: list/create=true; prepare/dispatch/receive/cancel/manageAll/viewStock=false. Roles no modificados. Navegador verificó cambio EDESCE CONSULTORA/Local Principal y Gamarra Moda/Local Principal. Company5 se probó únicamente por harness; no afirmar cambio de company en navegador.

| Control de aislamiento | Evidencia actual | Límite |
|---|---|---|
| Almacenes ajenos | warehouse() rechaza ID de otra company con AuthorizationException | no es prueba de traslado persistido ajeno |
| Contexto de formulario cambiado | GET products con form_company_id incorrecta devuelve 422 | no se enviaron mutaciones reales |
| Detalle inexistente | GET P/999999 devuelve 404 | no hay registros ajenos para comparar |
| Productos | GET 200: EDESCE company1 devuelve catálogo propio; Gamarra IDs105/106; company5 vacío | disponibilidad es informativa; no se validó consumo de stock |
| Transferencias | tabla warehouse_transfers total=0 | REAL DATA QA COVERAGE PENDING |

## Warehouse Scope

Warehouses actuales se consultan por tenant/company, admiten branch null o branch activa de esa company. No existe un filtro explícito warehouse.is_active en estos métodos ni un atributo equivalente en el contrato revisado: no se inventa. Cada almacén debe tener exactamente una location operativa contextual; no hay selector libre de location en frontend. Origen y destino deben ser diferentes.

Destino de un usuario sin manageAll debe pertenecer a su branch activa; origen puede ser otro almacén válido de la company. El catálogo actual solo contiene generales en los contextos QA, por eso Create muestra el aviso backend y deshabilita Guardar. No se habilitó un destino artificial ni se otorgaron permisos.

## Operational List

REUSE WITH CONTRACT ADAPTATION del Operational List: toolbar/contexto, filtros GET, resumen, next-data-table, badges existentes, acciones por fila con portal compartido, paginación Laravel y empty state. Columnas reales: código, origen, destino, solicitado, estado, acciones. No se agregaron conteos de Items, nombres de creadores ni consultas inexistentes.

Filtros conservados: q (máximo60, ID exacto con prefijo TR- opcional) y status (seis valores reales); orden ID descendente; paginate20 y appends(filters). No se convirtió a AJAX ni a otra estrategia de paginación. Buscar mantiene parámetros y Limpiar vuelve al index. El enlace Ver traslado sigue siendo la única acción por fila.

## Detail

Secciones aprobadas de identidad/recorrido, cantidades, recepción cuando corresponde, cancelación cuando corresponde, historial y trazabilidad. Usa exclusivamente transfer, summary, labels, actions y receiptKey entregados por show. Se muestran IDs registrados de sucursal/location/StockItem/actor cuando no hay nombre en contrato; no se hacen búsquedas nuevas en Blade. Hitos usan requested/prepared/dispatched/completed/cancelled_at y actores existentes; completed remite a las recepciones.

Cantidades summary sin recalcular: requested/reserved/dispatched/received/in_transit/pending_receipt. pending_receipt es solicitado menos recibido antes de cancelar, incluso antes de despacho; el input de recepción usa in_transit, no ese total pendiente. En cancelado pending_receipt=0 según servicio.

No se añadieron listas de lotes/costos ni IDs de Items físicos al detalle: no vienen como colección explícita del contrato de presentación. Su asignación/trazabilidad se auditó en servicios/modelos. Ampliar esa presentación requeriría definir un contrato de lectura, no cargar relaciones ad hoc.

## Request

Conservados IDs/names, método POST, CSRF, request_key, notes, source_warehouse_id, destination_warehouse_id y lines[n][stock_item_id/requested_quantity]. Hidden form_tenant_id/company_id/branch_id son snapshots que FormRequest compara con contexto vigente; no seleccionan contexto.

Select2 mantiene GET products con q/page/source_warehouse_id y los tres snapshots. Backend filtra StockItems activos, tracks_inventory, company_stock_items activos y material vigente del tenant. Página25 con límite26 para pagination.more. Disponible se obtiene de lotes del origen (on_hand-reserved); no se recalcula en JS ni limita la solicitud a esa lectura provisional.

Conservados prefill autorizado, eliminación de filas al cambiar origen con confirmación, prevención de duplicados, step entero para tracks_items y decimal0.01 para cantidades. UUID permanece al reintentar. RequestService normaliza/sortea líneas, exige positivas/únicas, valida locations y crea snapshots en modelo. Create no reserva stock.

## Prepare

 requested → prepared; prepared retorna existente. Transacciones, company/transfer/lines/lots/levels con locks; orden de lotes por vencimiento no-null primero y luego ID. Valida empresa/producto vigente, tipo de control de unidades, consistencia de niveles/lotes y disponibilidad. Asigna cantidades/costos, reserva lotes y nivel y selecciona Items entered, comprobando que no estén reserved/in_transit en otra asignación. No hay selección FEFO alternativa en frontend.

CostResolver exige trazabilidad verificable a compra o recepción previa, moneda PEN/USD/base configurada, costo no negativo y tipo de cambio válido cuando corresponde. Conserva costo original y costo base en allocation. No se cambian precios ni costos desde la UI.

## Dispatch

 prepared → dispatched con reservas completas. Descuenta on_hand y reserved del origen sin tocar reservas ajenas; Items reserved pasan a in_transit y allocation a dispatched. Reintentos dispatched/partially_received/received no vuelven a descontar. Autorización actual y validación de identidad/almacenes siguen aplicando antes del retorno. No se ejecutó despacho real.

## Receive

Acepta dispatched/partially_received. Cada línea positiva recibida se envía con transfer_line_id y quantity; líneas cero se omiten. UUID/hash conserva identidad de recepción y previene duplicación; la misma UUID con otra operación se rechaza. No se reemplazó recepción parcial por total.

Backend reparte cantidades sobre allocations dispatched, valida moneda base, crea lotes independientes de destino y receipt lines inmutables; conserva Items físicos/ID y los mueve de ubicación/lote a entered. Aumenta inventory_levels del destino con costo promedio verificado. Exceso o Items inconsistentes causan rollback. Solo al recibir todo establece received/completed_at.

## Cancel

Motivo obligatorio <=2000, requested/prepared únicamente. Cancelación prepared libera contribución exacta a lotes/nivel y devuelve Items reserved a entered, marcando vínculos/asignaciones cancelled. requested con asignaciones inesperadas se rechaza. No revierte despachos ni recepciones. Frontend conserva confirmación y mensajes backend, sin controles en estados no cancelables.

## Inventory Effects

Solicitud: registros/snapshots, sin reserva. Preparación: reserva, sin salida física. Despacho: salida física del origen, sin ingreso anticipado al destino. Recepción: ingreso parcial o completo, trazabilidad a allocations/lotes/Items. Cancelación previa: libera reservas. Costos y cantidades se calculan exclusivamente en backend con BC Math y transacciones; UI no implementa una segunda disponibilidad.

## Responsive QA

| Ancho | Listado real vacío | Solicitud real | Listado sintético poblado | Solicitud sintética poblada | Detalle en seis estados |
|---:|---|---|---|---|---|
| 320 | PASS | PASS | PASS | PASS | PASS |
| 375 | PASS | PASS | PASS | PASS | PASS |
| 390 | PASS | PASS | PASS | PASS | PASS |
| 576 | PASS | PASS | PASS | PASS | PASS |
| 768 | PASS | PASS | PASS | PASS | PASS |
| 992 | PASS | PASS | PASS | PASS | PASS |
| 1200 | PASS | PASS | PASS | PASS | PASS |
| 1440 | PASS | PASS | PASS | PASS | PASS |

Medición DOM: scrollWidth no supera innerWidth (puede ser15px menor por scrollbar vertical). Tablas con scroll horizontal local; selects/forms y origen/destino se adaptan. Inspección visual desktop real y móvil sintético. La matriz real se ejecutó en Gamarra; EDESCE se validó funcionalmente sin repetir todos los anchos. Matriz sintética completa por estado no equivale a aislamiento backend.

Se detectó y corrigió overflow de labels sr-only absolutos dentro de tablas desplazables usando **position-relative**, utilidad Bootstrap existente, en sus contenedores. No CSS nuevo. Menú por fila conserva Escape/foco/aria-expanded con el portal compartido.

## Functional QA

- Harness PHP7.3.33 externo: conexión START TRANSACTION READ ONLY / ROLLBACK, sesiones/cache en memoria, usuarios autorizados y contexto explícito. No invoca prepare/dispatch/receive/cancel/store.
- Index/Create reales 200 en los tres contextos. Estado, código sin coincidencias y página2:200. Detalle inexistente:404. Products:200 y paginación sin coincidencias:vacío. Snapshot de company incorrecto:422. Almacén ajeno:AuthorizationException.
- Navegador Owner Gamarra: lista, filtro de estado, solicitud sin destinos y catálogo de productos Gamarra. Owner EDESCE: búsqueda GET preserva q=TR-999999&status=prepared; solicitud mantiene origen1, destinos vacíos y Guardar deshabilitado. Ambos contexts visibles en shell. Cero errores JS capturados en las páginas reales revisadas.
- Presentación sintética externa: ocho páginas renderizadas desde los Blades reales, seis estados + index/create; modelos no persistidos y transporte AJAX interceptado. Buttons por estado coinciden con matriz. Menú Ver traslado abre y Escape devuelve foco. Paginación poblada renderizada con paginator Laravel; navegación con datos reales paginados sigue pendiente.
- Payloads sintéticos comprobados para solicitud (cantidad3), prepare, dispatch, cancel con reason y receive parcial (2 de6 en tránsito). Todos los AJAX simulados devolvieron422 controlado: error visible, controles recuperados, cantidades conservadas. No se envió solicitud de negocio a Laravel desde esas pruebas.
- El diff de operations.js solo añade soporte de teclado del menú; serialización, UUID, CSRF, solicitudes Select2 y manejo de operaciones permanecen idénticos a HEAD.
- Node --check operations.js, Laravel view:cache y git diff --check. Render real valida index/create; render sintético valida todos los caminos condicionales principales de show.

## Mutation QA

**MUTATION QA PENDING**. No se ejecutaron operaciones reales de solicitud/preparación/despacho/recepción/cancelación; no se modificaron stock, reservas, costos, roles o almacenes. No hay suite específica de transferencias encontrada bajo tests para validar el ciclo transaccional aislado. No se ejecutaron tests genéricos que pudieran migrar/resetear la base compartida.

**SYNTHETIC PRESENTATION QA != BACKEND ISOLATION QA**. Los errores simulados prueban la UI, no locks, costos, rollback de inventario o idempotencia real.

## Backend Sync Notes

[OPEN] WT-01 — Configuración y permisos de QA operacional.
File / endpoint: WarehouseTransferAccess, create/warehouses, permisos *_transfer.
Impact: Owners tienen list/create pero no acciones ni manageAll; solo almacenes generales, destinos elegibles vacíos.
What backend needs to define or correct: preparar contexto QA autorizado con destinos/ubicaciones y permisos apropiados si se quiere validar operaciones. No se afirma que Owner deba recibir esos permisos ni que generales deban habilitarse como destino.
Frontend can continue: Yes. Aviso y bloqueo actuales preservados.

[OPEN] WT-02 — Cobertura de datos, inventario e idempotencia.
File / endpoint: servicios Transfers y warehouse_transfers (0 filas).
Impact: detalle persistido, visibilidad cruzada origen/destino/manageAll, parciales reales, concurrencia y reservas no se comprobaron con transferencias reales.
What backend needs to define or correct: dataset/suite aislada autorizada para probar ciclo y rechazos transaccionales. No crear transferencias compartidas solo para QA.
Frontend can continue: Yes. Lecturas actuales y presentación integradas; REAL DATA QA COVERAGE PENDING / MUTATION QA PENDING.

[OPEN] WT-03 — Texto de algunos errores del servicio.
File / endpoint: WarehouseTransferPreparationService / WarehouseTransferCostResolver.
Impact: algunas cadenas actuales contienen caracteres mal codificados; frontend conserva el error recibido sin reemplazar reglas ni mensajes con heurísticas.
What backend needs to define or correct: normalizar esas cadenas cuando corresponda en backend.
Frontend can continue: Yes.

No se identificó una necesidad de cambiar reglas comerciales para esta integración. Access no es por sí solo una máquina de estados; servicios más condiciones Blade constituyen el contrato actual. No se confundió esa separación con una autorización implícita de todos los estados.

## QA Coverage Gaps

- Cero transferencias: aislamiento positivo entre documentos origen/destino/company/tenant no demostrado empíricamente.
- Sin permisos operativos ni destino elegible de Owners: flujo productivo no aprobado.
- Locks/reintentos, conciliación de lotes/niveles, Items físicos, costos, parciales y liberaciones auditados en código, no ejecutados.
- Prefill desde disponibilidad auditado y probado sintéticamente; navegación integrada desde esa otra superficie no ampliada en esta fase.
- Names de actores/branches y listado de lotes/Items individuales no añadidos al contrato; se muestran los identificadores ya entregados.

## Revisit Triggers

Cambios backend de autorización/state machine/snapshots, endpoints o payloads; dataset aislado autorizado; nuevos permisos o almacenes operativos; pruebas transaccionales disponibles; contrato ampliado de trazabilidad/impresión; correcciones de costos/conciliación o mensajes backend.

## Files Changed

1. config/venti-next.php — allowlist index/create/show.
2. resources/views/warehouseTransfer/index.blade.php — Operational List, resumen, acciones/paginación.
3. resources/views/warehouseTransfer/create.blade.php — secciones de formulario y scroll accesible.
4. resources/views/warehouseTransfer/show.blade.php — detalle, operaciones existentes, historial/trazabilidad.
5. public/js/warehouseTransfer/operations.js — teclado/foco de menú por fila; contratos intactos.
6. docs/REAL-NEXT-WAREHOUSE-TRANSFERS.md — auditoría, matrices y evidencia.

Artefactos de QA fuera del repo, en la carpeta local de evidencia de esta sesión: warehouse-transfer-readonly.php, wt-presentation-render.php/router.php/shim.js, ocho HTML sintéticos y warehouse-transfers-final.png. Ninguna dependencia productiva hacia esos archivos. Servidor sintético detenido al terminar.
