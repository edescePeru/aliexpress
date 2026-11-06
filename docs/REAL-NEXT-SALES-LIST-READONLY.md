# REAL NEXT — Sales List Read-only Integration

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
