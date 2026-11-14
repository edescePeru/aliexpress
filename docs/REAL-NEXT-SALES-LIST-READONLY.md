# REAL NEXT — Sales List Read-only Integration

> Estado vigente: ver **QA Closure — 2026-11-13** al final. Las secciones de 2026-11-05 conservan evidencia histórica: ya existen 7 ventas atribuibles y los Owners actuales sí tienen viewBranch. No interpretar los antiguos pendientes de datos/roles como el resultado vigente.

Fecha: 2026-11-05. Base funcional: `f367675`, `feature/venti-next-frontend`.

**REAL NEXT — SALES LIST READ-ONLY FRONTEND INTEGRATED**

| Área | Estado |
| --- | --- |
| READ PATH | Integrado: lista, opciones, filtros, vacío y detalle autorizado |
| FINANCIAL ACTIONS | Fuera de fase; no cerradas ni ejecutadas |
| DOCUMENT SECURITY | BACKEND / INFRASTRUCTURE SYNC OPEN |
| REAL POSITIVE DATA QA | REAL DATA QA COVERAGE PENDING |
| ROLE QA | Pendiente viewBranch y capacidades específicas |

## Contract Audit

Se revisaron AGENTS, la reauditoría vigente, Material List V2, Cotizaciones, Component Patterns, la dirección visual y el listado aprobado en `venti-next/resources/views/puntoVenta/list.blade.php`. El backend actual prevalece sobre sus versiones históricas. Referencias: [reauditoría](REAL-NEXT-BACKEND-REAUDIT-LATEST.md), [Cotizaciones](REAL-NEXT-QUOTES.md), [patrones](VENTI-NEXT-COMPONENT-PATTERNS.md).

Responsables: `PuntoVentaController::listar/getSalesAdmin/getOrderDetails/printDocumentSale`, `SaleListAccess`, `SaleListOptions`, `SaleListGuard`, `SaleListDocumentController`, `Sale`, `WorkerAccess`, `permissions_catalog.php`, rutas y contratos del JS real `puntoVenta/list.js` y `list-access.js`.

No se modificaron controllers, servicios, modelos, rutas, permisos, scopes, migraciones, datos ni Venta Libre. El JS financiero legacy permanece en disco, pero esta vista no lo carga. El nuevo adaptador de lectura conserva endpoints, filtros, snapshot y DTO; sustituye únicamente la integración de presentación de esta superficie. No se conecta lógica financiera mediante botones ocultos.

## Routes

Todas las rutas de la matriz tienen prefijo `/dashboard`, autenticación, usuario habilitado, contraseña/contexto y `SaleListGuard`. T/C/B significa tenant/company/branch activos y validados, sin IDs elegidos por filtros.

| Operación | Route | Permission | Tenant | Company | Branch | Actor | Guard | Read/Mutation | Estado frontend |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Listado + años/opciones | GET `/listado/ventas/`, `puntoVenta.list` | list_puntoVenta | Actual | Actual | Actual | Propio o local por permiso | read + Options/Access | Read | READ SAFE / integrado |
| Datos/paginación | GET `/get/data/sales/{page}` | list_puntoVenta | Actual | Actual | Actual | Propio o local por permiso | read + snapshot + visible | Read | READ SAFE / integrado |
| Detalle | GET `/sales/{orderId}/details` | list_puntoVenta | Actual | Actual | Actual | Padre autorizado | read,route,orderId | Read | READ SAFE / integrado; positivo real pendiente |
| Imprimir ticket | GET `/imprimir/documento/venta/{id}`, `puntoVenta.print` | print_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | print,route,id | Read/PDF stream | Condicional; NOT AVAILABLE BY PERMISSION en owners QA |
| Documento local | GET `/sales/{id}/documents/{kind}`, `sales.list.document` | print_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | print + access/file checks | Read | Condicional; seguridad estática OPEN |
| Pagos existentes | GET `/sale/partial-payment/{sale_id}` | list_puntoVenta | Actual | Actual | Actual | Padre autorizado | read,route,sale_id | Read | No integrado; modal financiero excluido |
| Lista de errores | GET `/listado/ventas/error`, `/get/data/sales/error/{page}` | list + listErrors_puntoVenta | Actual | Actual | Actual | Propio o local | errors | Read | Fuera de esta integración |
| Despacho | POST `/sale/update-dispatch-status` | updateDispatch_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | dispatch,input,sale_id + snapshot | Mutation | BLOCKED FOR THIS PHASE |
| Registrar pago | POST `/sale/partial-payment/store` | managePayments_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | payments,input,sale_id + snapshot/caja | Mutation | BLOCKED FOR THIS PHASE |
| Eliminar pago | DELETE `/sale/partial-payment/{id}` | managePayments_puntoVenta + list | Actual | Actual | Actual | Padre desde pago/movimiento | payments,payment,id + snapshot | Mutation | BLOCKED FOR THIS PHASE |
| Datos de comprobante | POST `/sales/update-invoice-data` | manageDocuments_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | documents,input,order_id | Mutation | BLOCKED FOR THIS PHASE |
| Emitir | POST `/facturador/generar`, `/sale/generate-invoice` | manageDocuments + generarBoleta/Factura_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | emit + snapshot/revisión emisión | Mutation/externa | BLOCKED FOR THIS PHASE |
| Recuperar archivos | POST `/ventas/{sale}/recuperar-archivos-nubefact` | manageDocuments_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | documents,route,sale | Mutation/externa | BLOCKED FOR THIS PHASE |
| Anular | POST `/anular/order/{order}` | annul_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | annul + snapshot | Mutation | BLOCKED FOR THIS PHASE |
| Consultar anulación/NC | POST `/consultar/anulacion/{id}`, `/consultar/nota-credito/{id}` | annul_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | annul + snapshot | Consulta con reconciliación/mutación | BLOCKED FOR THIS PHASE |
| Nota crédito | POST `/generar/nota-credito/total/{id}`, `/generar/nota-credito/parcial/{id}` | annul_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | annul + snapshot | Mutation/externa | BLOCKED FOR THIS PHASE |
| Datos NC parcial | GET `/nota-credito/parcial/data/{id}` | annul_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | annul,route,id | Read para flujo financiero | BLOCKED FOR THIS PHASE |
| Descartar error | POST `/sales/errors/{id}/discard` | annul_puntoVenta + list | Actual | Actual | Actual | Padre autorizado | annul + snapshot | Mutation | BLOCKED FOR THIS PHASE |

## Permissions

`list_puntoVenta` habilita consulta. `viewBranch_puntoVenta` amplía actores únicamente dentro del local activo. No existe bypass frontend admin/owner. Las capacidades nuevas son print/updateDispatch/managePayments/manageDocuments/annul y emitir requiere además el permiso específico boleta/factura. Configuración del catálogo de permisos no equivale a concesión al usuario.

## SaleListAccess

`context()` delega a WorkerAccess: usuario habilitado no Platform Admin, tenant coincidente, company/branch activas y membresías activas. `visible()` exige sales.tenant_id/company_id/branch_id actuales, created_by no nulo, usuario autor del tenant y branch de company. Sin viewBranch añade `created_by=Auth::id()`; sale(id) reusa esta consulta y devuelve 404 si no es visible. No se intenta corregir aislamiento filtrando filas en JS.

## SaleListOptions

Años y creadores derivan de ventas visibles. Clientes se limitan a ventas/cotizaciones visibles; cajas a company y branch/general activas; subtipos a company/cajas permitidas. No se consulta el catálogo global defectuoso de creadores de Cotizaciones. El selector de creador sólo aparece con más de un creador disponible. «Todos los autorizados» no amplía el scope backend ni revela históricos.

## SaleListGuard

Resuelve el padre antes del detalle/archivo; comprueba permiso por operación. Datos paginados requieren los headers `X-Sales-Tenant`, `X-Sales-Company`, `X-Sales-Branch`. El adaptador también los envía en detalle para detectar cambio de contexto. Son snapshots, no selectores de tenant/company. Los links documentales normales siguen autorización del contexto vigente en servidor. Mutaciones además tienen controles de snapshot, revisión de emisión y caja según operación; su existencia no certifica el flujo financiero.

## Actor vs Worker

`created_by` y `created_by_name` identifican actor/snapshot de autor. `worker_id` es asociación laboral separada. Aunque el modelo tiene worker/attendedByName, el DTO de esta lista y el detalle no entregan una columna de trabajador. No se agregó ni se usa Worker como fallback de actor. «No registrado» representa ausencia de texto, sin asignar identidad.

## Tenant / Company / Branch Scope

| Sesión/contexto | Tenant | Company | Branch | Alcance/acciones |
| --- | --- | --- | --- | --- |
| Owner Gamarra, sesión real previamente autorizada | 3 | 3 Gamarra Moda | 4 Local Principal | Mis ventas del local; seis flags false |
| Owner EDESCE, login real | 1 | 5 COMPANY OWNER TEST | 6 PRINCIPAL | Contexto inicialmente recordado; vacío correcto |
| Owner EDESCE, cambio por UI | 1 | 1 EDESCE CONSULTORA E.I.R.L. | 1 Local Principal | Mis ventas del local; seis flags false |

Ambos owners tienen list/listErrors y carecen de viewBranch. Se confirmó también mediante harness readonly la lectura en company/branch 2/2 y 5/7 del Owner EDESCE, sin atribuir esos casos a navegación de esta fase. Ninguno tiene Worker vinculado en esas companies; la lectura no lo requiere. No se otorgaron permisos ni se cambiaron roles.

## Historical Sales

Reconsulta actual: 104 ventas tenant/company/branch 1/1/1; todas tienen worker_id y ninguna created_by. Son excluidas deliberadamente. Años, creadores y lista vacíos son correctos. No se hizo backfill, no se crearon ventas y no se usaron fixtures en Laravel real.

## Operational List Mapping

| Elemento | Reutilización / adaptación |
| --- | --- |
| Theme | ALLOWLIST MATCH antes: NO; después: YES para puntoVenta.list. Layout carga venti.min.css + venti-next.css y admin2.js/portal compartido |
| Cabecera | next-page-heading y toolbar-context-only, visible también en móvil |
| Búsqueda/filtros | next-operational-list + next-advanced-operational-list, toolbar/filter grid del baseline Sales; Select2 y Datepicker existentes |
| Resumen | totalFilteredRecords; startRecord/endRecord/currentPage/totalPages recibidos. totalRecords se conserva en respuesta (backend actualmente lo iguala al filtrado) |
| Tabla | next-data-table, table-responsive con scroll local y foco; sin reglas CSS nuevas |
| Acciones | Dropdown/portal compartido, Escape y restitución de foco; cierre fija aria-expanded=false |
| Paginación | Página de servidor, 10 registros según backend; primera/última, vecinos y elipsis sin cambiar estrategia |
| Vacío/error | Vacío explícito, loading y alert separado; no transformar fallos de autorización/red en «sin ventas» |
| Column Visibility | No se añadió: no existe estado/contrato de visibilidad previo en Sales; se usa scroll local para estas 11 columnas |

Columnas: código center; fecha center; cliente left; usuario registrador left; moneda center; importe right; método de pago left; cumplimiento center; despacho/anulación center; comprobante/emisión center; acciones center. Headers/body alineados. `total` es texto backend: en pago parcial viene **abonado / total**, no se invierte ni se suma. Estado de pago usa estado_pago_parcial y la semántica pagos_parciales_venta; no calcula porcentaje. Despacho es badge informativo, nunca switch operativo.

Filtros conservados: `code`, `year`, `startDate/endDate` d/m/Y, `created_by_id`, `customer_id` (incluye venta_directa/cotizacion_sin_cliente), `payment_status` rojo/naranja/verde, `dispatch_status`, `cash_box_id` (incluye pago_parcial), `cash_box_subtype_id`, `invoice_status` con/sin_comprobante, `sale_status` active/annulled. Subtipo se muestra según uses_subtypes. `document_type` aparece validado en Access pero getSalesAdmin no lo aplica: no se inventó ese filtro.

Búsqueda/selecciones reinician página 1; paginación conserva filtros. Abort de petición previa más secuencia impide respuestas tardías. Limpiar restaura active y opciones vacías. Código no interpreta actor/company/branch. Los valores se insertan como texto escapado; los importes/líneas se muestran sin recálculo.

## Detail Mapping

Modal next-aux-modal con tabla de líneas. GET details entrega code/producto/quantity/price/total, protegido por SaleListGuard. Identidad, cliente, actor, fecha, moneda, importe, método de pago, comprobante y despacho/anulación proceden del DTO de la misma lista autorizada, mostrados tras éxito del detalle. No se consultan relaciones globales adicionales. No hay desglose fiscal, worker, trazabilidad completa ni historial de pagos en este endpoint: no se fabricaron secciones. No hay totales calculados desde líneas. Cerrar aborta petición pendiente y devuelve foco; errores 403/404/contexto no muestran contenido previo.

## Action Matrix

- **READ SAFE:** Ver detalle; ticket/documentos entregados por backend y protegidos, sólo con print_puntoVenta.
- **NOT AVAILABLE BY PERMISSION:** impresión/documentos para los dos owners actuales. No se descargó ningún archivo ajeno ni se simuló concesión de capacidades.
- **IMPLEMENTED — QA PENDING:** impresión/descargas positivas con usuario apto y documentos reales. Confidencialidad estática no certificada.
- **BLOCKED FOR THIS PHASE:** despacho editable, pagos, datos fiscales, emisión, recuperación Nubefact, anulación, reconciliaciones y notas de crédito. Modales/handlers financieros no se cargan en esta vista, aunque existan permisos. El JS legacy y backend permanecen disponibles para fases posteriores; listado de errores y Venta Libre no se modifican.

## Document Risk

Links de print_url y URLs locales de anulación/NC se aceptan sólo same-origin y dentro de la ruta protegida de esa venta o ticket. No se usan `asset(public/comprobantes/...)` ni URLs externas como fallback. SaleListDocumentController verifica padre, permiso, filename y realpath. Eso **no protege por sí mismo archivos públicos**: public/comprobantes/infraestructura sigue BACKEND / INFRASTRUCTURE SYNC OPEN. No se certifica confidencialidad documental por ocultar botones.

## Responsive QA

| Ancho | Real vacío + filtros, EDESCE/Gamarra | Tabla sintética | Detalle sintético |
| --- | --- | --- | --- |
| 320 | Sin overflow global | Scroll local, sin overflow global | Sin overflow global/modal |
| 375 | Sin overflow global | Scroll local, sin overflow global | Sin overflow global/modal |
| 390 | Sin overflow global | Scroll local, sin overflow global | Sin overflow global/modal |
| 576 | Sin overflow global | Scroll local, sin overflow global | Sin overflow global/modal |
| 768 | Sin overflow global | Scroll local, sin overflow global | Sin overflow global/modal |
| 992 | Sin overflow global | Scroll local, sin overflow global | Sin overflow global/modal |
| 1200 | Sin overflow global | Scroll local si necesario | Sin overflow global/modal |
| 1440 | Sin overflow global | Contenida | Sin overflow global/modal |

Matriz basada en mediciones DOM; inspección visual adicional de móvil/desktop/modal, toolbar, Select2, badges e importes. No equivale a comparación pixel-perfect de 24 screenshots ni QA positivo con ventas reales. Se corrigió cabecera móvil usando la clase compartida context-only. CSS nuevo: **0**; ningún archivo CSS se modificó en esta fase. El CSS de Material pendiente en git pertenecía a la fase anterior.

## Functional QA

Navegador real: Gamarra y EDESCE con contexto registrado, empty state, filtros, búsqueda y limpiar; requests GET 200 con headers correctos. Consola sin errores/warnings observados. Assets de esta pantalla sin errores HTTP observados. Allowlist/CSS/JS confirmados en DOM y red. Filtros anulada/cumplimiento/código mantienen parámetros y vuelven a página 1; limpieza vuelve a active.

Harness externo bajo START TRANSACTION READ ONLY y ROLLBACK final: schema/104 históricos/0 actores, options, controllers, cinco contextos, página 1/2/filtros vacíos. Router real con middleware y Auth en memoria: list/data 200; página 0, snapshot branch incorrecto y creador ajeno 422; histórico ID 1 detalle 404; impresión sin permiso 403 en ambos tenants. No confundir router en CLI con login HTTP. El primer harness reutilizaba request del container entre despachos y dio un resultado engañoso de creador; se corrigió el harness vinculando cada request, sin cambiar backend. Resultado documentado: 422.

**SYNTHETIC PRESENTATION QA:** servidor estático aislado temporal en 8098, fuera del repositorio, con secciones compiladas del Blade real y el mismo adaptador/CSS. Shim de transporte exclusivamente en ese artefacto; ninguna llamada de negocio sale del harness, ninguna fila sintética se introduce en la sesión Laravel real. Probados página 1, intermedia 7 y última 9; filas/importes/badges, detalle de líneas, portal, Escape/foco, error 422, vacío y respuesta lenta seguida de vacío. No acredita aislamiento, cálculos ni permisos positivos. Servidor detenido y pestaña sintética cerrada al terminar.

Validaciones: `node --check public/js/puntoVenta/list-readonly.js`, PHP 7.3.33 `artisan view:cache`, `git diff --check`. View compilation no sustituye QA de datos. No operaciones financieras, ventas nuevas, backfill ni asignación de permisos; no commit.

## Backend Sync Notes

[RESOLVED] Esquema sales.created_by: presente; no reapareció el error SQL. El problema anterior era schema local desactualizado, no un contrato que requiera workaround frontend.

[OPEN] Point: datos positivos y roles. Endpoint: SaleListAccess/Options + list/details. Impact: no certifica filtros/paginación/aislamiento sobre filas atribuibles ni viewBranch. Backend/QA necesita dataset autorizado aislado y rol apto sin concederlo automáticamente. Frontend can continue: Yes. Estado: REAL DATA QA COVERAGE PENDING / ROLE QA PENDING.

[OPEN] Point: documentos estáticos. Endpoint: SaleListDocumentController y almacenamiento public/comprobantes. Impact: posible acceso directo fuera del controller. Backend/infra debe retirar o denegar acceso estático y validar despliegue. Frontend can continue: Yes, enlaces protegidos; no certificar confidencialidad.

[OPEN] Point: pago parcial concurrente. File: SalePartialPaymentController. Impact: auditoría previa detecta sesión/saldos sin los locks del nuevo PosCashService. Backend necesita revisar serialización/idempotencia y QA transaccional. Frontend can continue: Yes, lectura; flujo financiero fuera de fase.

[OPEN] Point: Venta Libre. File: FreeSaleController. Impact: contrato independiente no alineado completamente con PosSaleService. Frontend can continue: Yes en Sales List; Venta Libre permanece sin cambios y fuera del cierre.

## QA Coverage Gaps

Sin ventas atribuibles: no hay detalle positivo real, resultados efectivos de filtros, página intermedia/última real, ni contraste entre dos actores autorizados del mismo local. No se añadió dataset para ocultar ese límite. Sin viewBranch ni capacidades específicas: QA de roles positivos pendiente. Sin documentos positivos: no se probaron descargas/branding PDF. Años/creadores reales vacíos; selectores con varias opciones requieren datos aptos. Detalle sólo cubierto positivamente en presentación sintética; histórico no autorizado sí rechazado por router real.

## Revisit Triggers

1. Ventas creadas por flujo autorizado con actor/contexto: repetir filtro, paginación, detalle y aislamiento positivo.
2. Cuenta QA con viewBranch/print u otras capacidades: contrastar alcance propio/local sin ampliar branch, y documentos autorizados.
3. Cambio de SaleListAccess/Options/Guard/DTO: reauditar filtros, actor, snapshots y render antes de adaptar.
4. Backend/infra cierra almacenamiento documental: ejecutar QA de confidencialidad separado.
5. Fase financiera autorizada y entorno aislado: integrar mutaciones con sus contratos/QA; no activar sólo por existencia del permiso.

## Files Changed

- `config/venti-next.php`: añade únicamente puntoVenta.list a allowlist.
- `resources/views/puntoVenta/list.blade.php`: estructura aprobada, filtros/tabla/empty/modal de lectura; retira CSS inline y superficies financieras de esta vista.
- `public/js/puntoVenta/list-readonly.js`: nuevo adaptador de lectura; preserva contrato vigente y separa handlers financieros.
- `docs/REAL-NEXT-SALES-LIST-READONLY.md`: auditoría, matrices, evidencia y límites.

Cambios pendientes de Material y documentos de reauditoría ya existían al iniciar; se conservaron. No se alteró el baseline congelado. La siguiente fase debe mantener separados los cierres de datos/roles/documentos y la integración financiera.

## QA Closure — 2026-11-13

HEAD auditado: `9262d65`. **Sales List: FRONTEND INTEGRATED — READ PATH**. Sin rediseño, cambios de Blade/JS/CSS/backend ni mutaciones financieras. Esta sección reemplaza los estados históricos de cobertura, no borra su evidencia.

| Área | Estado vigente |
|---|---|
| Actor / Worker | QA PASSED para dataset actual; separación y exclusión histórica comprobadas |
| Tenant / Company / Branch Isolation | QA PASSED en matriz detallada debajo |
| Role Scope | QA PASSED: roles reales + ampliación aislada en memoria; sin modificar roles persistidos |
| Filters | QA PASSED para filtros expuestos con límites de dataset; document_type no aplicado sigue OPEN |
| Pagination | Primera página PASS; MULTIPAGE QA — PENDING DATASET; metadata fuera de rango OPEN |
| Row Detail | QA PASSED: positivo real y 404 por padre no autorizado/inexistente |
| Documents | DOCUMENT SECURITY — BACKEND/INFRA OPEN |
| Financial Actions | OUT OF SCOPE |
| Backend | UNCHANGED |

Método: relectura de listar/getSalesAdmin/getOrderDetails, SaleListAccess, SaleListOptions, SaleListGuard, SaleListDocumentController, Blade y adaptador real. PHP7.3.33, harness temporal fuera del repo, `SET TRANSACTION READ ONLY`, rollback final, sesión/cache array. Routes reales resueltas y enlazadas a cada Request, container request actualizado; middleware InitializeTenantContext y SaleListGuard reales, seguido del controller. No equivale a ejecutar toda la cadena web: se omiten autenticación por cookie, enabled/password middleware en CLI. QA de navegador adicional con login autorizado Owner EDESCE, tenant1/company1/branch1. No contraseñas guardadas ni cambios de permisos.

### Current Dataset

Lectura actual completa: **111 sales**, todas PEN. Tipo documento: 35 `01`, 29 `03`, 47 null. No se reutilizó el conteo histórico como resultado actual.

| Tenant/company/branch | Total físico | Sin actor | IDs visibles completos (active) |
|---|---:|---:|---|
| EDESCE 1/1/1 | 110 | 104 | 120,119,118,117,116,115 |
| EDESCE 1/1/8 | 1 | 0 | 114 |
| EDESCE 1/2/2 | 0 | 0 | vacío |
| EDESCE 1/5/6 | 0 | 0 | vacío |
| EDESCE 1/5/7 | 0 | 0 | vacío |
| Gamarra 3/3/4 | 0 | 0 | vacío |

No existen otros grupos físicos tenant/company/branch en sales. Los contextos vacíos se comprobaron además contra Options/lista.

Las siete ventas atribuibles tienen created_by4, snapshot `Owner EDESCE`, worker_id null, state_annulled0 e internal_reversed_at null. ID114 e118 son factura01; las demás no tienen type_document. 114: sunat_status Enviado; 118: aceptada con observaciones; restantes null. Despacho:119 pendiente, resto despachado. Pago parcial:119; resto `n`.

104 históricos: created_by y snapshot nulos, todos con worker_id, 16 state_annulled1 y 12 internal_reversed_at no nulo (indicadores independientes). Ninguno se hace visible atribuyéndolo al trabajador. IDs completos:

```text
1,2,3,4,6,7,8,9,10,12,13,14,15,16,17,18,19,23,24,25,26,27,28,29,31,32,33,34,35,36,37,38,39,41,42,43,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,102,103,104,105,106,107,108,109,110,111,112,113
```

### Actor / Worker

`created_by` determina autorización, `created_by_name` es el texto snapshot del DTO. No se sustituye por User.name ni Worker cuando falta. Fallback textual actual: `No registrado`. Worker es relación independiente; se eager-load en controller pero no se expone como actor ni filtro en esta pantalla.

Cobertura positiva: siete ventas con actor y sin worker visibles; 104 con worker pero sin actor excluidas. Detalle histórico ID1:404. No hay venta actual visible con snapshot ausente ni caso de renombre del actor: esos casos quedan cubiertos por inspección del contrato, no por datos positivos nuevos. No se hizo backfill.

### Permission Matrix

| Cuenta / rol real | list_puntoVenta | viewBranch_puntoVenta | Resultado |
|---|---|---|---|
| Owner EDESCE id4 / owner | Sí | Sí | Ventas del local; seis principal, una outlet |
| Owner Gamarra id7 / owner | Sí | Sí | Ventas del local; vacío |
| Owner Gloria id8 / owner | Sí | Sí | Permisos consultados; lectura no probada |
| Cajero Master id2 / cajero | Sí | No | Mis ventas del local; vacío; venta120 ajena404; actor4 filtro422 |
| Usuario Prueba id6 / cashier | Sí | No | Mis ventas del local; vacío |
| Pepe Castillo id3 / almacen | No | No | SaleListGuard403; ruta web puede redirigir primero a cambio de contraseña |
| Usuario Prueba Tenant2 id5 / cashier | Sí | No | Permisos consultados; lectura no probada |
| Platform Admin id1 / admin | Sí | No | WorkerAccess403; no bypass operativo |

Los Owners ya poseen viewBranch: corrige la observación de noviembre5. La autorización depende de `can()`, no del nombre Owner.

Prueba aislada adicional: misma identidad/membresías del usuario2, con implementación de `can()` limitada a list+viewBranch únicamente en memoria del harness, sin sincronizar roles/permisos ni guardar User. Resultado: devuelve `[120,119,118,117,116,115]`, acepta filtro actor4 y detalle120=200; con usuario real sin ampliación devuelve vacío /422/404 respectivamente. Esto prueba el alcance ampliado sobre ventas de otro actor. No se presenta como una concesión real ni sesión productiva. El harness fijó tabla/users y foreign key/user_id de la subclase para preservar las relaciones; intentos preliminares con mock/subclase incorrectos se descartaron y no son findings de aplicación.

### Context Matrix

Los seis contextos de Current Dataset retornaron200 con exactamente los IDs listados. Other companies EDESCE2 y5 autorizadas y vacías. No se agregaron selectores de contexto a filtros.

| Manipulación / escenario | Resultado |
|---|---|
| Owner Gamarra con sesión1/1/1 | 403 contexto |
| Owner EDESCE con company3/branch4 | 403 contexto |
| Owner EDESCE tenant1/company1/branch4 | 403 contexto |
| Query tenant_id3/company_id3/branch_id4 desde1/1/1 | Ignorada como selección de contexto; conserva seis IDs locales |
| Snapshot X-Sales-Branch999 | 422, no vacío |
| Cajero id2 intenta created_by_id4 | 422, no vacío |
| Owner con viewBranch filtra creator7 ajeno | 200 vacío; no amplía scope |

### Filters

Options es contextual: principal creators `[4]`, years `[2026]`, customers vacío, boxes `[1,2,5]`, subtypes `[1,2,3,4,6]`; outlet creators `[4]`, years `[2026]`, customers `[1]`, boxes `[2,5,6]`, mismos subtypes. Otras companies y Gamarra: opciones vacías. Cajas generales de company permitidas por contrato. No lookup User global: tenant explícito + IDs derivados de ventas visibles.

Resultados branch1:

| Filtro real | IDs / resultado |
|---|---|
| created_by_id4; year2026 | 120,119,118,117,116,115 |
| year1900; code NO-QA-MATCH | vacío |
| 04/11/2026 a04/11/2026 | 120,119,118,117,116 |
| fecha final anterior a inicial | 422 |
| code120 | 120 (búsqueda por ID LIKE, no nombre de producto) |
| sale_status annulled | vacío: no anuladas atribuibles |
| invoice_status con_comprobante | 118 |
| invoice_status sin_comprobante | 120,119,117,116,115 |
| payment_status verde | seis IDs locales |
| payment_status naranja/rojo | vacío |
| cash_box_id pago_parcial | 119 |
| cash_box_id "1" | 117,116,115 |
| cash_box_id "2" + cash_box_subtype_id "1" | 118 |
| customer_id "1" en principal | vacío |
| customer_id "1" en outlet | 114 |
| dispatch_status pendiente | 119 |
| dispatch_status despachado | 120,118,117,116,115 |
| dispatch_status pending (no es el valor español del dataset) | vacío |
| document_type01 | NO filtra: conserva seis; validación admite01/03 |
| document_type bad | 422 |

IDs de caja/cliente se probaron como strings de query HTTP. Un primer harness pasó integers a reglas `string` y recibió422; se corrigió el harness, no backend. Worker no es filtro contractual. Document type no está expuesto por UI; el filtro disponible de comprobante es con/sin. No añadir un selector que finja filtrar01/03.

### Pagination

Página1: currentPage1,totalPages1,startRecord1,endRecord6,totalFilteredRecords6. Principal solo seis filas atribuibles; outlet una. **MULTIPAGE QA — PENDING DATASET** para segunda página con resultados, aunque haya111 registros físicos.

Página0:422. Página2 con year2026+created_by_id4 conserva total6 y filtros, data vacío; metadata startRecord11/endRecord6. Página999: startRecord9981/endRecord6. **Backend Sync OPEN: rango invertido fuera de página válida**. No se arregló vía JS ni se cambió backend durante este cierre. La UI normal solo ofrece página1 en este dataset; no es evidencia de un fallo de navegación normal. El renderer consume literalmente start/end; un borrado/cambio concurrente que reduzca páginas podría alcanzar el defecto.

El adaptador relee filtros al solicitar una página y mantiene headers snapshot; cambio de filtros reinicia1. Conservación en request fuera de rango comprobada en harness; navegación entre páginas con resultados queda pendiente de dataset.

### Row Detail

GET `/dashboard/sales/{orderId}/details`, SaleListGuard read/route/orderId autoriza padre antes de getOrderDetails.

| Actor/contexto/venta | Resultado |
|---|---|
| Owner EDESCE1/1/1,120 | 200; P-00001, cantidad1, precio70, total70.00 |
| Owner EDESCE1/1/8,114 | 200; SKU POL-ADI-SPO-HOM-41-BLA, cantidad2, precio40, total80.00 |
| Owner principal pide114 | 404 |
| Owner outlet pide120 | 404 |
| Gamarra pide120 | 404 |
| Owner EDESCE company2/branch2 o company5/branch6 pide120 | 404 |
| Cajero id2 pide120 | 404 |
| Owner principal pide histórico1 | 404 |
| Owner principal pide999999 | 404 |

Navegador: menú de venta120 abierto con teclado, Ver detalle carga resumen y línea real; cerrar deja modal cerrado y listado con seis filas. No se pulsó Ver Ticket. El controller de detalle por sí solo busca ID; no omitir SaleListGuard al probar autorización.

### Action Matrix

Todas requieren además list_puntoVenta y contexto autorizado; fuente estática routes/SaleListGuard/Access. No se ejecutaron rutas documentales o financieras.

| Action | Permission adicional | Guard | Read/Mutation | QA status |
|---|---|---|---|---|
| Listar / filtrar / paginar | Ninguno | read + snapshot para datos | Read | Probado; límites paginación arriba |
| Ver detalle | Ninguno | read + padre | Read | PASS |
| Ticket / documento | print_puntoVenta | print + padre + archivo para documents | Read/documento | NO EJECUTADO; seguridad OPEN |
| Modificar despacho | updateDispatch_puntoVenta | dispatch + padre/snapshot | Mutation | OUT OF SCOPE |
| Registrar/eliminar pagos | managePayments_puntoVenta | payments + padre/snapshot/contexto caja | Mutation | OUT OF SCOPE |
| Actualizar invoice | manageDocuments_puntoVenta | documents + padre/snapshot/revisión | Mutation | OUT OF SCOPE |
| Emitir | manageDocuments + generarBoleta/Factura_puntoVenta | emit + padre/snapshot/revisión | Mutation/externa | OUT OF SCOPE |
| Recuperar Nubefact | manageDocuments_puntoVenta | documents + padre/snapshot | Mutation/externa | OUT OF SCOPE |
| Anular / NC / consultar anulación-NC | annul_puntoVenta | annul + padre/snapshot; plan refund/guard según acción | Mutation o reconciliación | OUT OF SCOPE |
| Descartar error | annul_puntoVenta | annul + padre/snapshot/revisión | Mutation | OUT OF SCOPE |

Owner EDESCE tiene actualmente los seis flags de capacidades true en DOM (print/dispatch/payments/documents/annul/emit). La vista conserva solo adaptador read-only; no activa handlers financieros por ello. La matriz histórica de flags false no describe el estado actual.

### Documents

**DOCUMENT SECURITY — BACKEND/INFRA OPEN**. Guards/filename/realpath inspeccionados, sin navegación ni descarga documental. El listado ya existente puede leer internamente cinco bytes de pdf_path para validar `%PDF-` al construir su DTO: no se invocó acceso PDF ni se considera esa comprobación una certificación de seguridad. No se abrió ningún archivo ajeno, impresión, recuperación ni PDF público.

### Responsive QA — cierre incremental

Sin cambios visuales: se mantiene la matriz histórica. Comprobación nueva en navegador con seis filas reales, sesión Owner EDESCE1/1/1:

- Desktop1440: document scrollWidth1440; tabla con scroll local.
- Tablet768: document scrollWidth768; contenedor tabla client470/scroll1510.
- Mobile390: document scrollWidth375 (scrollbar vertical), sin overflow horizontal global; toolbar reorganizada y tabla local.
- Inspección visual desktop/tablet/mobile; snapshots del navegador y mediciones DOM. Sin nueva matriz completa ni afirmación pixel-perfect.
- Buscar NO-QA-MATCH muestra vacío real y `Mostrando 0 a 0 de 0 ventas`; Limpiar restaura seis. Detalle120 positivo y cierre comprobados. Consola capturada sin error/warn al final.
- Viewport temporal restaurado. No nueva CSS. No cambios de frontend necesarios.

### Remaining Open Items

1. **[OPEN] SALES-QA-PAGE-RANGE:** getSalesAdmin calcula startRecord con página solicitada aunque exceda totalPages. Reproducido p2/p999,total6. Backend debe definir clamp/rechazo/rango vacío coherente. Frontend can continue: Yes, lectura normal; no declarar paginación completa PASS.
2. **[OPEN] document_type:** validado pero no aplicado, ya conocido y ahora reproducido. Frontend can continue: Yes, UI no lo expone. No convertirlo en filtro fiscal nuevo.
3. **PENDING DATASET:** >10 ventas visibles por contexto, multi-actor real simultáneo, anuladas/reversed atribuibles, pagos naranja/rojo, boleta atribuible, actor con snapshot ausente/renombrado. No crear datos para cerrar artificialmente estos casos.
4. **DOCUMENT SECURITY — BACKEND/INFRA OPEN**, sin cierre por comprobar guards.
5. Pago parcial concurrente y Venta Libre del audit anterior permanecen fuera de esta fase; no revalidados ni resueltos aquí.

Cierra el pendiente anterior de detalle positivo real y resultados efectivos de filtros sobre actor/contexto. Cierra el desconocimiento del permiso viewBranch actual y prueba su efecto con identidad alternativa aislada. No cierra segunda página positiva por contar históricos invisibles.

Archivo modificado en esta fase: únicamente este documento canónico. Backend/Blade/JS/CSS intactos. Sin acciones financieras, cambios de invoice, datos fiscales o permisos; sin commit. `git diff --check` ejecutado al cierre.

## Reporting Exports — clasificación Sales 2026-11-13

- **SALES-NEW-01 — BUG:** getSalesAdmin calcula `startRecord=(page-1)*10+1` con total>0 antes de saber si la página tiene filas; `endRecord=min(total,page*10)`. Por eso page2,total6 produce11/6. Expected inequívoco: metadata coherente, no intervalo invertido. Mecanismo final (422, clamp o0/0) requiere fijar contrato con DEV. No corregido en esta fase documental; no afecta dataset normal de primera página.
- **SALES-NEW-02 — TECH DEBT / uso C confirmado:** document_type no es un filtro huérfano global. getSalesErrorAdmin lo lee (línea5142) y aplica where(type_document) (5184–5185). SaleListAccess::validateFilters se comparte con getSalesAdmin normal, que lo admite pero ignora. La UI normal no lo expone. No hay evidencia suficiente para exigir filtro01/03 en la lista normal; se clasifica claridad/separación del contrato, no BUG fiscal ni nuevo filtro. El OPEN anterior debe leerse con esta precisión.

Ambos registrados una sola vez en tabla maestra del backlog; no duplicar como findings nuevos en posteriores cierres. No cambios backend/JS/Blade.
