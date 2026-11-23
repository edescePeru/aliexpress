# REAL NEXT — DEV PARALLEL WORK REPORT

Fecha de corte: **2026-11-22**. HEAD: **996a31a**, más integraciones frontend pendientes de commit del workspace. Autoridad: código actual, no tablas históricas aisladas.

## Resumen ejecutivo

**ACTIVE DEV WORK PACKAGES: 17**

**P0: 4**

**P1: 6**

**P2: 3**

**P3: 3**

**P4: 1**

**FRONTEND SURFACES CURRENTLY BLOCKED: 36**

**FRONTEND ALREADY INTEGRATED BUT OPERATIONAL QA PENDING: 9**

**RETIRED / NO ACTION: 5**

Unidad de prioridades: paquetes A–Q, no tickets individuales. Las 36 vistas contabilizadas son Quote industrial19 + QuoteSale3 + Entry2 + Financial Lost/Regularize2 + User legacy1 + compras auxiliares9. Los dos candidatos legacy sujetos a retiro no se cuentan como trabajo frontend comprometido. Las 9 superficies integradas con cierre operacional pendiente son Perfil, Material Edit, Financial Create/Edit, GRE Create, dos Supplier Credits, Cash Boxes y Cash Subtypes. **No son totales absolutos del ERP**: la cola residual al final conserva pendientes sin reauditoría suficiente para convertirlos en nuevas asignaciones.

Primero **A: Profile upload safety**, pequeño y aislado. En paralelo **B: Material Save** y **F: Financial Lost**. F es el desbloqueo frontend más pequeño; A cierra un flujo existente, no abre otra pantalla.

## Evidencia y límites

Fuentes: [backlog](REAL-NEXT-DEV-TEAM-FINDINGS-BACKLOG.md), [DEV delivery y addendum post-migration](REAL-NEXT-DEV-DELIVERY-REVIEW-2026-11-22.md), [UNKNOWN clasificación final](REAL-NEXT-UNKNOWN-SCREENS-CLASSIFICATION.md), contratos recientes de Material, Cash, My Profile, GRE, Supplier Credits, Financial Purchase, QuoteSale, Entry, Inventory y Worker.

Revisión estática y SELECT acotados; no QA extensa ni mutaciones, exports, documentos o proveedor. No se reejecutaron tests mutadores: se contrastaron sus assertions con HEAD. Un test de caracterización que pasa demostrando un defecto no significa aceptación funcional.

IDs verificados mediante PDO en transacción READ ONLY: materials1/2/3 T1; entries1/2/3 T1/C1, finance=0, Por compra; quotes2/5/6 T1/C1. OrderPurchaseFinance T1: sin filas en la consulta. No se obtuvo dataset verificable de QuoteSale en esta lectura: se usa template, nunca IDs industriales. Existencia no autoriza cualquier sesión: se requiere contexto/permiso correspondiente. **No abrir Edit industrial para smoke: su GET escribe negocio.**

`LINK TEMPLATE` = **REQUIRES REAL ID / DATASET REQUIRED**, o código real cuando corresponda. Los links son localizadores de pantalla, no instrucciones de ejecutar acciones. Líneas aproximadas ancladas a métodos. Los identificadores A–Q son paquetes de este informe, no findings canónicos nuevos.

Flags por paquete: **Visual problem / Backend problem / Business decision**. Backend problem NO en QA significa «ningún defecto nuevo demostrado», no operación certificada.

## Tabla principal

| Priority | Work package | Finding | Screen | Local URL | Current state | Backend blocker | DEV action | Acceptance | Unlocks |
|---|---|---|---|---|---|---|---|---|---|
| P0 | A Profile upload | PROFILE-01 IMAGE | Perfil | [Abrir](http://127.0.0.1:8080/dashboard/perfil) | Integrado; upload abierto | Tipo/tamaño, overwrite antes de save, cleanup | Validación y reemplazo recuperable | Archivo inválido422; fallo conserva bytes; ajeno403; sin huérfanos | Cierre imagen, no nueva UI |
| P0 | B Material Save | MAT-03/04/05 separados | Material Edit | [Material1](http://127.0.0.1:8080/dashboard/editar/material/1) | Integrado; Save parcial | Items company, locations, cast previo | Tres tickets de corrección | Matriz company/locations/flags y rollback | Material Edit Save |
| P1 | C Entry forms | INV-01 consumer residual | Create/Edit V2 | [Crear](http://127.0.0.1:8080/dashboard/crear/entrada/compra) | WAIT | Lookup activo inventoryLevels sin company y permiso; tipo parent | Scope/permission/binding | Propios sí, ajenos/no permiso no | Dos forms |
| P1 | D QuoteSale forms | QUOTE-02/auxiliares | Create/Edit/Registrar | [Crear](http://127.0.0.1:8080/dashboard/crear/cotizacion/venta) | WAIT | Contactos/roles auxiliares/conversión | Tres contratos de capacidades | Create-only/edit-only/list-only y foreign IDs | Tres forms |
| P0 | E Industrial ownership | Quote industrial | Familia19 | [Listado](http://127.0.0.1:8080/dashboard/listado/general/cotizaciones/v2) | WAIT | Company/usuarios; GET guarda negocio | Ownership y separar comandos | GET deja negocio idéntico; IDs ajenos denegados | Familia por etapas |
| P1 | F Finance Lost | Parsing OCF | Perdidas | [Abrir](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/perdidas) | WAIT | substr(code,3) negativo; sin orden | Parser estricto y secuencia ordenada | 00001/00003 → sólo2 | Lost read-only |
| P1 | G Finance regularization | Endpoints de otro dominio | Regularizar | [Abrir template](http://127.0.0.1:8080/dashboard/regularizar/automaticamente/entrada/compra/finanzas/{entry_id}) — REQUIRES REAL ID | WAIT | JS llama detalles de compra normal | Contrato financiero + adaptación coordinada | Nunca alcanza endpoint normal; parent/credit guard | Form regularización |
| P1 | H User legacy | USER-01 legacy | Usuarios | [Abrir](http://127.0.0.1:8080/dashboard/usuarios) | WAIT o reemplazo | Role::all/permissions via roles | Directorio/roles/permisos efectivos o retiro | Sin enumeración/asignación ajena | access.users si se conserva |
| P2 | I Finance operations | Mutations QA | Create/Edit integrados | [Crear](http://127.0.0.1:8080/dashboard/crear/orden/compra/finanzas) | MUTATION QA PENDING | Falta aceptación; guard existe | Suite HTTP/transacciones/details/crédito | Propios/ajenos/fallo/rollback | Operación, no frontend |
| P2 | J GRE operations | GUIDE-02 QA/EXT/docs | GRE Create | [Abrir](http://127.0.0.1:8080/dashboard/shipping-guides/create?type=remitente) | Integrado | HTTP/concurrencia/sandbox pendientes | Evidencia MySQL y proveedor aislado | Un envío/número; correspondencia documental | Operación, no frontend |
| P2 | K Supplier Credits | Abonos/estados/plazos | Dos fachadas | [Facturas](http://127.0.0.1:8080/dashboard/faturas/proveedores/pendientes) | Integradas | Cierre operacional pendiente | Suite ciclo/retry/DECIMAL/reconciliación | Saldo exacto, idempotencia, rollback | Operación, no frontend |
| P3 | L Cash decisions | CASH-02/03/04 | Cajas/Subtipos | [Cajas](http://127.0.0.1:8080/dashboard/cash-boxes) | Integradas | Regla histórica no decidida | Producto decide, DEV implementa después | Casos sin uso/usado/obligación pendiente | Cierre Cash |
| P3 | M Material policy | MAT-01 | Material Edit | [Material1](http://127.0.0.1:8080/dashboard/editar/material/1) | Integrado | Flags/historia/maestro compartido | Decisión firmada e invariantes | Stock/reservas/omisión/identidad/company | Save completo junto con B |
| P3 | N Legacy disposition | Area/Output users | Transfer/Output | [Transfer](http://127.0.0.1:8080/dashboard/crear/transferencia) | RETIRE CANDIDATE | Area sin company / User global | Conservar o retirar explícitamente | Scope si conserva; transición si retira | Dos candidatos condicionales |
| P4 | O Finance tech debt | Históricos/Audit | Create/Edit | [Edit template](http://127.0.0.1:8080/dashboard/editar/orden/compra/finanza/{id}) — REQUIRES REAL ID | Integrados | Etiqueta histórica incompleta; Audit aceptado | Hidratar selección actual, conservar Audit | Histórico autorizado preservado sin ofrecer ajenos | Lectura completa, no nueva UI |
| P0 | P Documents by family | DOC-01 residual/DOC-02 QA | Facturas/Quotes | [Cotizaciones](http://127.0.0.1:8080/dashboard/cotizaciones/venta) | Read paths integrados; docs abiertos | Public paths/históricos no certificados | Tickets separados por familia | URL conocida ajena denegada; artifact ownership | Cierre documental |
| P1 | Q Other purchase dependencies | Auxiliares aún WAIT | Normal/Express/reportes | [Normal Create](http://127.0.0.1:8080/dashboard/crear/orden/compra/normal) | 9 vistas WAIT | Lookup/permisos/binding/tipo | Corregir consumer, no listas integradas | GET y fetch contextual y autorizado | Forms/reportes auxiliares |

## Registro de rutas/views

Los paquetes citan estas claves para no inventar rutas ni duplicar nombres. `sin nombre` indica ruta sin name explícito en HEAD.

| Key | Local URL | Route name / action sin nombre | View |
|---|---|---|---|
| profile | [Perfil](http://127.0.0.1:8080/dashboard/perfil) | user.profile | user.profile |
| material | [Material1](http://127.0.0.1:8080/dashboard/editar/material/1) | material.edit | material.edit |
| cash | [Cajas](http://127.0.0.1:8080/dashboard/cash-boxes) | cashBox.index | cashBox.index |
| subtype | [Subtipos](http://127.0.0.1:8080/dashboard/cash-box-subtypes) | cashBoxSubtype.index | cashBoxSubtype.index |
| fc | [Finance Create](http://127.0.0.1:8080/dashboard/crear/orden/compra/finanzas) | order.purchase.finance.create | orderPurchaseFinance.createOrderFinance |
| fe | [Finance Edit](http://127.0.0.1:8080/dashboard/editar/orden/compra/finanza/{id}) — LINK TEMPLATE | sin nombre; editOrderPurchaseFinance | orderPurchaseFinance.editOrderPurchaseFinance |
| fl | [Finance Lost](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/perdidas) | order.purchase.finance.list.lost | orderPurchaseFinance.indexFinanceLost |
| fr | [Finance regularize](http://127.0.0.1:8080/dashboard/regularizar/automaticamente/entrada/compra/finanzas/{entry_id}) — LINK TEMPLATE | sin nombre; regularizeAutoOrderEntryPurchaseFinance | orderPurchaseFinance.regularizeAutoEntryPurchaseFinance |
| ec | [Entry Create](http://127.0.0.1:8080/dashboard/crear/entrada/compra) | entry.purchase.create | entry.create_entry_purchaseV2 |
| ee | [Entry Edit1](http://127.0.0.1:8080/dashboard/entrada/compra/editar/1) | entry.purchase.edit | entry.edit_entry_purchaseV2 |
| qc | [QuoteSale Create](http://127.0.0.1:8080/dashboard/crear/cotizacion/venta) | quoteSale.create | quoteSale.create |
| qe | [QuoteSale Edit](http://127.0.0.1:8080/dashboard/editar/cotizacion/venta/{quote}) — LINK TEMPLATE | quoteSale.edit | quoteSale.edit |
| qr | [Registrar Boleta](http://127.0.0.1:8080/dashboard/registrar/comprobante/venta/Boleta) | show.register.comprobante | quoteSale.registrarComprobante |
| qi | [Industrial List](http://127.0.0.1:8080/dashboard/listado/general/cotizaciones/v2) | quote.general.indexV2 | quote.general_v2 |
| qic | [Industrial Create](http://127.0.0.1:8080/dashboard/crear/cotizacion) | quote.create | quote.create |
| qie | [Industrial Edit2 — NO smoke GET](http://127.0.0.1:8080/dashboard/editar/cotizacion/2) | quote.edit | quote.edit |
| user | [Usuarios](http://127.0.0.1:8080/dashboard/usuarios) | user.index | access.users |
| transfer | [Transfer legacy](http://127.0.0.1:8080/dashboard/crear/transferencia) | transfer.create | transfer.create |
| output | [Output legacy](http://127.0.0.1:8080/dashboard/crear/solicitud) | output.request.create | output.create_output_request |
| gre | [GRE Create](http://127.0.0.1:8080/dashboard/shipping-guides/create?type=remitente) | shipping_guides.create | shipping_guides.create |
| sc1 | [Facturas y saldos](http://127.0.0.1:8080/dashboard/faturas/proveedores/pendientes) | index.invoices.pending | credit.indexInvoicesPending |
| sc2 | [Créditos registrados](http://127.0.0.1:8080/dashboard/control/creditos) | index.credit.supplier | credit.indexSupplierCredit |
| qslist | [Cotizaciones](http://127.0.0.1:8080/dashboard/cotizaciones/venta) | quoteSale.index | quoteSale.general |
| nc | [Normal Create](http://127.0.0.1:8080/dashboard/crear/orden/compra/normal) | order.purchase.normal.create | orderPurchase.createNormalV2 |
| ne | [Normal Edit](http://127.0.0.1:8080/dashboard/editar/orden/compra/normal/{id}) — LINK TEMPLATE | sin nombre; editOrderPurchaseNormal | orderPurchase.editNormalV2 |
| nl | [Normal Lost](http://127.0.0.1:8080/dashboard/ordenes/compra/perdidas) | order.purchase.list.lost | orderPurchase.indexLost |
| nr | [Normal Regularized](http://127.0.0.1:8080/dashboard/ordenes/compra/regularizadas) | order.purchase.list.regularize | orderPurchase.indexRegularize |
| xc | [Express Create](http://127.0.0.1:8080/dashboard/crear/orden/compra/express) | order.purchase.express.create | orderPurchase.createExpress |
| xe | [Express Edit](http://127.0.0.1:8080/dashboard/editar/orden/compra/express/{id}) — LINK TEMPLATE | sin nombre; editOrderPurchaseExpress | orderPurchase.editExpress |
| xs | [Express Show](http://127.0.0.1:8080/dashboard/ver/orden/compra/express/{id}) — LINK TEMPLATE | sin nombre; showOrderPurchaseExpress | orderPurchase.showExpress |
| os | [Operator Show](http://127.0.0.1:8080/dashboard/visualizar/orden/compra/{code}) — LINK TEMPLATE | sin nombre; showOrderOperator | orderPurchase.showOperator |
| pr | [Purchase material report](http://127.0.0.1:8080/dashboard/reporte/de/ordenes/compra/por/materiales) | report.orders.by.materials | orderPurchase.report_orders_by_material_v2 |

Todos los templates requieren ID/código real del tipo y contexto correctos; no usar el1 sólo porque existe en otra tabla.

## P0 — A: Profile upload safety

**PROFILE-01 IMAGE UPLOAD. Visual NO / Backend YES / Business decision NO.** Frontend integrado; bloquea cierre de imagen, no otra fachada.

`UserController.php:181–200` aplica SELF pero toma extensión del cliente, mueve a `public/images/users/{user.id}.{extension}` antes de save, sin validación de contenido/tamaño. Mismo nombre puede destruir avatar previo si falla DB; extensión distinta deja anterior sin cleanup. `tests/Feature/ProfileMutationClosureTest.php:57–78` caracteriza PNG exitoso, TXT aceptado y overwrite al fallar persistencia. No leer verde como cierre.

**DEV:** validar imagen real/tamaño antes de mover; staging no colisionante y reemplazo coordinado con persistencia; rollback elimina nuevo temporal y conserva referencia/bytes anteriores; tras éxito limpiar sólo avatar anterior propio, nunca default/compartido. Publicar límite de tamaño admitido. No cambiar SELF/settings/password/rutas/multipart/diseño ni reabrir Required Password Change.

**Aceptación:** PNG propio PASS; TXT/disfrazado/sobretamaño422 sin archivo; ajenos mismo/otro tenant403 antes de mover; fallo inducido en save conserva hash de bytes original; éxito no deja archivo previo propio ni temporal huérfano. Convertir assertions de caracterización en assertions de seguridad. **UNLOCKS: Profile Image closure.**

### ¿Dónde lo veo?

[Perfil](http://127.0.0.1:8080/dashboard/perfil) → sección imagen. No subir nada en esta entrega. Problema de validación/rollback, no de estética.

## P0 — B: Material Save correctness

**Visual NO / Backend YES / Business decision NO** para los tres bugs concretos; política general separada en M. Frontend integrado.

| Ticket | Evidencia HEAD | Fix requerido | Aceptación |
|---|---|---|---|
| MAT-03 | `MaterialController::update:1691–1717`, Item por material_id y entered/exited; modelo Item tenant-only | Acotar objetivos company/ownership y proteger históricos según contrato | Dos companies comparten material: sólo objetivos autorizados cambian; ajenos/históricos preservados y rollback completo |
| MAT-04 | `UpdateMaterialRequest:1469–1570`, unicidad warehouse rechaza ubicaciones distintas | Validar identidad location/InventoryLevel y pertenencia; no prohibir dos locations del mismo warehouse | Dos ubicaciones válidas aceptadas; duplicado real y location ajena rechazados |
| MAT-05 | `UpdateMaterialRequest:1359,1376,1393`, cast int antes de validar flags | Validar valores originales y luego normalizar | Texto/array/fuera de dominio422 sin escritura; valores legales conservan contrato |

**No cambiar:** maestro tenant-wide por decisión unilateral, precio/impuestos/stock/UI; no reabrir size/color persistidos ni sincronización precio/descuento corregidos. **UNLOCKS: Material Edit Save**, sujeto además a M.

### ¿Dónde lo veo?

[Material1](http://127.0.0.1:8080/dashboard/editar/material/1) → retacería, ubicaciones y flags en contexto autorizado T1. No Guardar. Tres tickets, no «Material tiene problemas».

## P1 — C: Entry Purchase V2

**Visual NO / Backend YES / Business decision NO.** Create/Edit WAIT.

`MaterialController::getJsonMaterialStockItemsEntry:4736` agrega inventoryLevels sin company; StockItem/InventoryLevel tenant-only. Precarga activa en `public/js/entry/entry_purchaseV2.js:19` y `edit_entry_purchaseV2.js:19`; ruta `routes/web.php:1098` sin capacidad específica. No extrapolar el fix LegacyItemReadAccess a este consumer. `EntryController::editEntryPurchase:2347` filtra company pero no exige explícitamente finance=false/entry_type=Por compra. Revisar referencias warehouse/location seleccionadas del parent.

**DEV:** lookup contextual real y gate compatible create/edit, parent del tipo admitido y ownership de referencias. **Aceptación:** C1 no recibe IDs/niveles C2/Gamarra, sin permiso403, contexto ausente falla cerrado; Edit financiero/tipo ajeno/otro contexto/inexistente no renderiza. GET sin cambios de negocio. **No cambiar:** listados/detalle integrado, stock/recepción ni scope por JS. **UNLOCKS:** dos forms READY FOR FRONTEND — MUTATION QA PENDING.

### ¿Dónde lo veo?

[Create](http://127.0.0.1:8080/dashboard/crear/entrada/compra) solicita catálogo al iniciar. [Edit1](http://127.0.0.1:8080/dashboard/entrada/compra/editar/1) requiere T1/C1. No agregar líneas/guardar/recibir.

## P1 — D: QuoteSale, tres contratos

**Visual NO / Backend YES / Business decision YES** donde matriz conversión/emisión no esté acordada. No es Quote industrial.

| Form | Blocker vigente | DEV / aceptación |
|---|---|---|
| Create | `/get/contact/{customer}` sin capacidad específica | Gate compatible con consumidores y contacto contextual; create-only permitido, list-only/sin permiso rechazado |
| Edit | Contactos igual; productos/totales requieren create_quoteSale mientras form exige edit_quoteSale | Capacidades compatibles con edición; edit-only no queda roto ni recibe privilegios de creación |
| Registrar comprobante | `/quotes/buscar` y `/get/data/quotes/sale/{id}` sin capacidad equivalente | Gate funcional y alcance de conversión acordado; lookup directo sin permiso rechazado |

Evidencia: `routes/web.php:1416,3407–3440`; `QuoteSaleController:964,5132,7922,8075,8138`; `QuoteController@getContactsByCustomer`. Productos/presentaciones ya contextuales; binding company+branch y cash context no son nuevos blockers. **Items físicos/reservas/conversión/cobro/emisión** siguen aceptación operacional separada: pasar lookups no las certifica. Preparar casos parent/hijos manipulados y rollback antes de habilitar mutaciones.

**No cambiar:** creator filter cerrado, permisos en JS, lógica fiscal/stock. **UNLOCKS:** tres forms, mutaciones QA PENDING.

### ¿Dónde lo veo?

[Create](http://127.0.0.1:8080/dashboard/crear/cotizacion/venta) → cliente/contactos; [Edit template](http://127.0.0.1:8080/dashboard/editar/cotizacion/venta/{quote}) → productos; [Registrar Boleta](http://127.0.0.1:8080/dashboard/registrar/comprobante/venta/Boleta) → buscar cotización. No guardar/reservar/emitir. Edit REQUIRES REAL ID / DATASET REQUIRED.

## P0 — E: QUOTE INDUSTRIAL — COMPANY/OWNERSHIP CONTRACT

**Visual NO / Backend YES / Business decision NO** para ownership y GET puro. 19 vistas WAIT del inventario, raíz vigente.

`Quote.php` tenant-only; `QuoteController:324,588` lookup User global; edit967–1084 guarda equipos/materiales/consumibles/electricidad/Quote (999/1010/1020/1030/1081/1084). Esto no es Audit técnico.

**DEV:** contrato company de padres/hijos/usuarios y consumers; mover persistencia GET a comando explícito autorizado/transaccional. **Aceptación:** GET repetido deja negocio idéntico, ajenos company/tenant rechazados, hijos manipulados y contexto inválido fallan cerrado; matriz de las19 vistas antes de liberar por etapas. Consumers OrderExecution/Report/Timeline necesitan corte propio, no quedan automáticamente certificados. **No cambiar:** QuoteSale ni cálculos/estados para aparentar lectura pura. **UNLOCKS:** familia industrial por etapas, no promesa de19 READY con un guard.

### ¿Dónde lo veo?

[Listado](http://127.0.0.1:8080/dashboard/listado/general/cotizaciones/v2), [Create](http://127.0.0.1:8080/dashboard/crear/cotizacion), [Edit2](http://127.0.0.1:8080/dashboard/editar/cotizacion/2). **No navegar Edit para demostrarlo: GET escribe.** Link de localización para revisión y prueba posterior al fix.

## P1 — F: Financial Purchase Lost

**Visual NO / Backend YES / Business decision NO.** `OrderPurchaseFinanceController:590–620`: `substr($code,3)` transforma OCF-00001 en -00001; pluck sin orden explícito. Scope contextual/withTrashed deben preservarse.

**DEV:** parser estricto prefijo OCF-, números únicos ordenados, tratamiento explícito de códigos inválidos; sin renumerar historia. **Aceptación:** 00001/00003 → sólo hueco2, orden aleatorio idéntico, vacío/duplicados/inválidos y otra company cubiertos, nunca negativos. Audit del fetch es técnico separado, no eliminarlo. **UNLOCKS:** indexFinanceLost read-only.

### ¿Dónde lo veo?

[Órdenes financieras perdidas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/perdidas) → consulta faltantes. No crear órdenes para rellenar huecos.

## P1 — G: Regularización financiera

**Visual NO / Backend YES / Business decision NO.** JS `public/js/orderPurchaseFinance/regularizeAutoEntryPurchaseFinance.js` conserva:

- Línea374: `/dashboard/update/detail/order/purchase/normal/{detail_id}`.
- Línea468: `/dashboard/destroy/detail/order/purchase/normal/{idDetail}/material/{materialId}`.

Riesgo de confundir dominios/IDs; no se afirma corrupción reproducida. GET financiero `OrderPurchaseFinanceController:627–661` no corrige ese contrato cliente.

**DEV:** definir/proteger endpoints financieros parent T/C/finance=true y crédito; luego ajuste JS coordinado al contrato confirmado. **Aceptación:** detail financiero nunca alcanza controller normal, foreign IDs no escriben, permisos/credit guard/rollback probados aisladamente. **No cambiar:** aceptar detalles financieros dentro de endpoints normales ni ocultar problema deshabilitando UI. **UNLOCKS:** regularizeAutoEntryPurchaseFinance; operación queda sujeta a QA.

### ¿Dónde lo veo?

[Regularizar — LINK TEMPLATE](http://127.0.0.1:8080/dashboard/regularizar/automaticamente/entrada/compra/finanzas/{entry_id}). REQUIRES REAL ID / DATASET REQUIRED; editor de líneas. No regularizar ni editar/borrar detalle.

## P1 — H: User legacy

**Visual NO / Backend YES / Business decision YES** sólo para conservar/reemplazar. `UserController:25–33`: Role::all/getPermissionsViaRoles; vista **access.users**, no user.index. TenantDirectory/getUsers2 corregidos no cubren este render.

**DEV:** confirmar conservación frente a ConfigUserWeb; si conserva, directorio/roles asignables contextuales y permisos efectivos; si reemplaza, transición/rutas explícitas. **Aceptación:** no enumera/asigna roles ajenos, permisos directos funcionan, sin permiso403, Platform Admin con contrato separado. **No cambiar:** Perfil SELF/Required Password Change. **UNLOCKS:** access.users si se conserva.

### ¿Dónde lo veo?

[Usuarios](http://127.0.0.1:8080/dashboard/usuarios) → listado/selector roles, sin crear ni editar.

## P2 — I: Financial Purchase operational closure

**Visual NO / Backend NO (QA pendiente) / Business decision NO.** Create/Edit y deleted/deleted detail/regularized ya integrados; no pedir frontend.

`OrderPurchaseFinanceController:51–85,207–235,239,348`; `FinancePurchaseCreditGuard.php:10` ya exige contexto transaccional/orden bloqueada y crédito produce422 en operaciones protegidas. **No es un guard faltante. GET Edit puede renderizar; no inventar prohibición GET ni disable frontend.**

**Entrega:** suite aislada HTTP Store/Update/details, rollback, referencias históricas y crédito. Propio permitido por contrato; ajeno company/tenant no cambia; fallo revierte cabecera/detalles; crédito no produce efectos parciales. Restauración/históricos se certifican aparte, no ejecutar por esta entrega. **No cambiar:** fachada, Audit ni Supplier Credits en paralelo. **UNLOCKS:** cierre operacional, BLOCKS FRONTEND NO.

### ¿Dónde lo veo?

[Create](http://127.0.0.1:8080/dashboard/crear/orden/compra/finanzas), [Edit template](http://127.0.0.1:8080/dashboard/editar/orden/compra/finanza/{id}). REQUIRES REAL ID / DATASET REQUIRED; líneas y Guardar ya tienen fachada. No enviar formularios.

## P2 — J: GRE operational certification

**Visual NO / Backend NO (QA pendiente) / Business decision NO.** GUIDE-01 cerrado; GUIDE-02/failure implementados;8 migraciones Ran/batch179. Falta HTTP/auth/CSRF Store, concurrencia MySQL reproducible, sandbox Nubefact y correspondencia PDF/XML/CDR.

**DEV/QA:** suite multiproceso misma emission_key, payload distinto409, retry incierto sin nuevo envío; HTTP autorizado/sin permiso/CSRF; sandbox separado. Un solo número/registro/envío por clave, UNKNOWN no reenvía ciegamente, fallo poscommit conserva estado contractual y archivos del objeto autorizado. Guard documental moderno ya existe en ShippingGuideDocumentController: no reimplementarlo. Fuente: contrato Create Remitente y addendum delivery.

**No cambiar:** frontend ni volver a pedir permisos/idempotencia/schema implementados. **BLOCKS FRONTEND? NO. BLOCKS OPERATIONAL CLOSURE? YES.**

### ¿Dónde lo veo?

[Create Remitente](http://127.0.0.1:8080/dashboard/shipping-guides/create?type=remitente). Form integrado; no Store ni proveedor/documentos durante este informe.

## P2 — K: Supplier Credits operational certification

**Visual NO / Backend NO (QA pendiente) / Business decision NO.** Dos fachadas integradas. Scope/permisos/lectura sin escritura/locks/request_key/DECIMAL reforzados; no nuevo defecto demostrado.

`SupplierCreditController:45,212,296–337`, contratos recientes de créditos/operaciones. **Entrega:** ciclo abono/eliminar, doble POST/retry/clave conflictiva, centavos, fechas/plazos/marcas/estados, Entry→Credit y conciliación; sólo suite descartable. Una aplicación por clave, saldo exacto, eliminación revierte importe, ajenos denegados y fallo revierte todo. Crédito vinculado bloquea Purchase según guard; coordinar dueño I.

**No cambiar:** guards entregados ni fabricar pagos reales. Recibos protegidos no certifican images/entries de facturas legacy. Documentos P/J separados. **UNLOCKS:** cierre operacional, no otra fachada.

### ¿Dónde lo veo?

[Facturas y saldos](http://127.0.0.1:8080/dashboard/faturas/proveedores/pendientes) → abonos; [Créditos registrados](http://127.0.0.1:8080/dashboard/control/creditos) → fechas/operaciones. No confirmar acciones.

## P3 — L: Cash decisions

**Visual NO / Backend NO (regla no decidida) / Business decision YES.** Frontend integrado. No pedir a DEV inventar política.

| Finding | Causa/archivo | Decisión requerida | Aceptación posterior |
|---|---|---|---|
| CASH-02 | CashBoxController:450/522, validación protege branch pero no moneda de caja usada | Moneda inmutable/versionada tras uso; sesiones abiertas/cerradas | Sin uso/usada/abierta/cerrada; historia no reinterpretada |
| CASH-03 | CashBoxSubtypeController:386; consumers leen is_deferred actual | Inmutabilidad/versionado y efecto sobre obligaciones existentes | Diferido/inmediato con historia y deuda pendiente; no romper cobros |
| CASH-04 | CashBoxSubtypeController:469 toggle; CashRegularizationService exige activo | Diferenciar referencia histórica cerrada de obligación pendiente | Desactivar sin uso/cerrado/pendiente según política, no prohibición universal |

Consumers: CashRegularizationService::subtype, DeferredSaleRefund::plan, SalePartialPaymentController. CASH-06 MySQL concurrencia y CASH-07 HTTP feedback después de política; no race demostrada por falta de prueba. **No cambiar:** CASH-01 whitelist/CASH-05 nullable ya corregidos. **UNLOCKS:** operación Cash, no bloquear otras fachadas.

### ¿Dónde lo veo?

[Cajas](http://127.0.0.1:8080/dashboard/cash-boxes) → moneda; [Subtipos](http://127.0.0.1:8080/dashboard/cash-box-subtypes) → diferido/activar. No guardar ni toggle.

## P3 — M: MAT-01 policy

**Visual NO / Backend NO (decisión) / Business decision YES.** Flags con stock/reservas/historia, omisión/default, SKU/barcode mostrado históricamente y maestro tenant-wide compartido entre companies no tienen cierre de política.

Evidencia: Material Edit Contract/Save Closure, MaterialController::update, UpdateMaterialRequest. **Producto:** quién puede cambiar maestro y efectos permitidos sobre historia/ofertas; DEV implementa invariantes y QA MySQL/upload después. Aceptación: omisión vs false, con/sin stock/reservas, identidad histórica y dos companies. No atribuir scope company al maestro unilateralmente ni inventar snapshots. **UNLOCKS:** Material Edit Save completo junto a B.

### ¿Dónde lo veo?

[Material1](http://127.0.0.1:8080/dashboard/editar/material/1) → flags/identificación. No guardar; apariencia aprobada.

## P3 — N: Legacy disposition

**Visual NO / Backend YES / Business decision YES.** TransferController:30 sigue Area::all (tenant-only, no company); OutputController:259–265 User::all. **LEGACY / RETIRE CANDIDATE**, no se afirma retirado.

Producto decide por cada ruta. Si conserva, DEV contextualiza parents/usuarios/permission/binding; si retira, transición y bloqueo explícito, no borrar silenciosamente. Aceptación conservar: ajenos rechazados/GET sin escritura; retirar: consumers modernos intactos y sin enlaces al flujo retirado. No rescatar Area legacy duplicando INV-01/WAREHOUSE-01/02 ni tocar ManagedWarehouse. **UNLOCKS:** dos candidatos condicionales fuera del total comprometido.

### ¿Dónde lo veo?

[Transferencia](http://127.0.0.1:8080/dashboard/crear/transferencia) → Area; [Solicitud](http://127.0.0.1:8080/dashboard/crear/solicitud) → usuario. No enviar.

## P4 — O: Financial historical references / Audit

**Visual NO / Backend YES (deuda no bloqueante) / Business decision NO.** `OrderPurchaseFinanceController:51–85,207–235,620` y FinancePurchaseInput:40–65: GET ofrece usuarios activos/cotizaciones elegibles; validator conserva referencia histórica actual. Frontend guarda ID histórico oculto sin inventar opción.

**DEV:** hidratar etiqueta de selección histórica actual autorizada, sin ofrecerla como nueva selección. Aceptación: actual propia preservada/visible, histórica ajena no elegible, ausencia no se sustituye por usuario ficticio. No cambiar formulario ni PaymentDeadline selectable/currentId.

**Audit técnico aceptado:** registra usuario/acción y tiempo de auditoría; no modifica orden/detalles/stock/saldos. No pedir eliminación. Compatible con excepción Entry Purchase Report; distinto de GET industrial E. Limitar consultas de QA repetidas, verificar en suite que sólo Audit cambia. **UNLOCKS:** lectura histórica completa, no requisito para rehacer forms ya integrados.

Payload exacto de Create: `user_id=Auth::user()->id`, `action='Crear Orden compra Normal VISTA'` (texto heredado aunque el formulario sea financiero), `time=microtime(true)-begin`. Edit: mismos campos con `action='Editar Orden de compra finance VISTA'`. `Audit` usa timestamps Eloquent; no adjunta request, archivos, detalle de la orden ni resultado de la consulta. El nombre heredado del evento es deuda de observabilidad, no efecto de negocio ni blocker.

### ¿Dónde lo veo?

[Edit template](http://127.0.0.1:8080/dashboard/editar/orden/compra/finanza/{id}) → aprobador/cotización histórica. REQUIRES REAL ID / DATASET REQUIRED; construir caso sólo en suite aislada.

## P0 — P: Documents by family

**Visual NO / Backend YES (frontera documental no cerrada) / Business decision NO** salvo retención histórica a aprobar. No se ejecutó acceso anónimo ni se afirma explotación HTTP.

| Ticket familiar | Estado real | DEV/Infra + aceptación | Pantalla |
|---|---|---|---|
| P-SC | Recibos protegidos; imágenes de factura legacy images/entries no certificadas por ese guard | Separar ambas rutas/storage; owner autorizado y otro tenant/company/URL conocida denegados; correspondencia. No rehacer guard de recibos | sc1/sc2 |
| P-Q | Quote industrial public/pdfs/quotes e images/planos; QuoteSale docs requieren su propia evidencia | Entrega autorizada por objeto, inventario public paths/retención. DOC-02 limpieza global ya retirada: sólo regresión multiarchivo/fallo | qi/qslist |
| P-H | Históricos Sales/Entries/Finance: read scope no equivale a control del archivo | Inventario paths/ownership y migración autorizada; sin owner → cuarentena, no atribución supuesta | UI LINK: N/A — backend/service only para inventario |
| J-GRE | Controller documental protegido; correspondencia proveedor pendiente | QA PDF/XML/CDR y auth en paquete J; no duplicar ticket aquí | gre |

Evidencia `QuoteController:2506,3080–3106,4756`, contrato credit/indexInvoicesPending, backlog DOC-01/DOC-02. No generar/borrar/mover archivos en esta fase; no borrar históricos sin retención aprobada. **UNLOCKS:** cierre documental, no bloquea read UI que excluye documentos.

### ¿Dónde lo veo?

[Facturas](http://127.0.0.1:8080/dashboard/faturas/proveedores/pendientes), [Cotizaciones](http://127.0.0.1:8080/dashboard/cotizaciones/venta), [Industrial](http://127.0.0.1:8080/dashboard/listado/general/cotizaciones/v2) → localizar acción documental, **no abrir archivo ni imprimir**.

## P1 — Q: Other purchase dependencies

**Visual NO / Backend YES / Business decision NO.** Nueve vistas aún WAIT; no incluye listas V2 ni Trash/Deleted Detail integrados.

| Superficies | Causa vigente | Fix / aceptación |
|---|---|---|
| createNormalV2/editNormalV2 | Precarga activa del lookup legacy compartido con C | Scope y permiso de consumer; parent/type ya protegido no basta. Create/Edit sólo leen al abrir |
| indexLost/indexRegularize | Fetch /all/order/purchase/lost y /regularize sin capacidad equivalente a vista; huecos sin orden explícito | Gates directos + secuencia ordenada, mismo resultado independientemente del orden DB |
| createExpress/editExpress | User::all y lookup Quote sin company | Elegibles por contexto; IDs ajenos no llegan al form |
| showExpress/showOperator | User::all; find/first y tipo/permiso incompletos | Parent contextual/tipo y fail closed; listado seguro no certifica show |
| report_orders_by_material_v2 | Fetch /get/data/order/purchase/by/material/{page} sin permiso del reporte | report_orderPurchaseExpress efectivo también en fetch; no exports |

Evidencia `OrderPurchaseController:47,907,1358–1396,1499,1971,2209,2562–2600`; `routes/web.php:1544–1619,1870,3138–3140`; final13 UNKNOWN. Un dueño OrderPurchase coordina C, no dos soluciones al mismo selector. No tocar FinancialPurchase para corregir Normal ni reabrir read paths integrados. **UNLOCKS:** nueve vistas por etapas, mutaciones pendientes.

### ¿Dónde lo veo?

[Normal Create](http://127.0.0.1:8080/dashboard/crear/orden/compra/normal) y las keys ne/nl/nr/xc/xe/xs/os/pr del registro con enlaces completos. Revisar formulario/consulta, no Store/Update/regularización/exports. IDs/códigos template requieren dataset del tipo correcto.

## BACKEND PENDING BUT FRONTEND ALREADY INTEGRATED

A Perfil, B/M Material, I/O Financial forms e históricos integrados, J GRE, K Supplier Credits, L Cash y P documentos. **No pedir otra fachada.** Falta fix o aceptación operacional según paquete; un QA pendiente no prueba bug. La revisión responsive frontend pendiente de un replay financiero no se convierte en ticket backend.

## FRONTEND CURRENTLY BLOCKED BY DEV

| Superficies con links en registro | Cantidad | Paquete/fix | Unlock |
|---|---:|---|---|
| ec/ee | 2 | C lookup/parent/permisos | Forms read/render |
| qc/qe/qr | 3 | D capacidades auxiliares | Forms con mutación QA pending |
| Industrial qi/qic/qie representativos | 19 | E ownership/GET puro | Familia por etapas |
| fl | 1 | F parsing | Lost read-only |
| fr | 1 | G endpoints correctos | Regularization form |
| user | 1 | H roles/directorio | access.users o retiro explícito |
| nc/ne/nl/nr/xc/xe/xs/os/pr | 9 | Q dependencies | Forms/reportes auxiliares |
| transfer/output | 0 comprometido | N decisión | 2 candidatos condicionales |

## DATA REMEDIATION

Datos históricos NO son fixes de código. Conteos antiguos no se presentan como actuales: inventariar mediante SELECT antes de autorizar cualquier corrección. No hubo backfill ni fixtures reales en esta entrega.

| Historia | Acción de datos | ¿Bloquea pantalla? |
|---|---|---|
| PRICE-07 C2 USD/lista PEN | Confirmar configuración y escoger moneda/lista; sin conversión automática | No fachada; puede bloquear venta |
| PRICE-08 Gamarra precios | Confirmar ofertas/listas actuales antes de configurar; no fake0 | No fachada; precio efectivo requerido para venta |
| FIN-01 snapshots fiscales | Conciliar con respaldo original; desconocido si no existe evidencia | No listado; historia fiscal parcial |
| DAILY-01 sin contexto | Conservar exclusión aceptada hasta evidencia T/C/B/moneda | No; Daily contextual cerrado |
| BANK-01 incompletos | Nuevo inventario; conteo anterior cambió | No catálogo; casos operacionales |
| PUBLIC-01 slugs | Inventario y publicación autorizada; migración no publica datos | Publicación, no assets/fachadas |
| Supplier Credits historia | Conciliar Entry/pagos/saldo sin atribuir company por ID coincidente | No fachadas; casos de K |
| Avatares/adjuntos legacy | Ownership/path, cuarentena/retención y migración autorizada | No read UI; cierre P |
| Actor/snapshots históricos | No Worker como fallback del registrador ni snapshots inventados | No listado; ausencia explícita |

## Cola residual canónica: no convertir historia en nuevos bugs

Estos pendientes permanecen visibles, pero **no forman parte de los17 paquetes ni de las36 vistas revalidadas**. No se declara cierre ni nueva prioridad sin corte focalizado HEAD. La revisión actual no certifica la totalidad de los consumers de esos findings; este límite impide presentar cifras globales ficticias.

- PRICE-05/06/09: diagnóstico de oferta/moneda, precondiciones del resolver y concurrencia MySQL de defaults. BP-01/02/03 y scope company cerrados. UI LINK: N/A — backend/service only para resolver/tests.
- BANK-02/03: default inactivo y desactivar cuenta con caja ligada requieren Producto; coordinar con L, no inventar obligación pendiente por mera referencia.
- PUBLIC-02..05: resolver público existe; falta cierre anónimo y política editorial/agotados/moneda/presentación/Home/branding. Bridge local no certifica publicación; no publicar fixtures.
- FIN-02/03: relaciones históricas de categoría y auxiliares no equivalen a FinanceInvoiceContext. No extrapolar cierre del form moderno.
- QUOTE-03/05: snapshots históricos y matriz fechas/metadata/roles; coordinar D sin reabrir creator ni ruta totales retirada.
- ENTRY-02, QA-01/02/03 y EXT-01: límites/estados adversos, exports, mantenedores, transferencias y proveedor. La entrega DEV parcial no cierra cada consumer; no contar endpoint como pantalla.
- MAT-02 Separate Pack, METRIC-01 Dashboard (no Metas), INV/WAREHOUSE legacy, RRHH operativo, Timeline/OrderExecution/FinanceWork/Combo: clasificación vigente del inventario, sin promesa de desbloqueo por fixes ajenos. Requieren evidencia específica antes de asignar nuevo fix.

## No volver a pedir lo entregado

GUIDE-01; código GUIDE-02/failure y8 migraciones Ran/batch179; Worker Enable histórico determinista; SELF/settings/password de perfil; Supplier Credits read/guards/idempotencia implementados; Customer delete-batch instalado; ENTRY-01 detalle; QUOTE-01 creator; PRICE-01/02/03/04; CASH-01/05; Daily contextual. INV-01 tiene fixes instalados: C es consumer residual concreto. DOC-02 limpieza global retirada: aceptación pendiente, no mismo fix otra vez. Required Password Change y Metas no se reabren.

## RETIRED / NO ACTION

1. OrderService — PAY-01 cerrado por retiro, no reparación SQL pendiente.
2. Proforma — mismo criterio.
3. ReferralGuide legacy — distinto de ShippingGuide moderno.
4. FollowMaterial.
5. Promotions legacy.

No enviarlos como bugs ni borrar archivos. Transfer/Output son candidatos N, no suman a estos cinco.

## Propuesta paralela

### DEV 1

**A Profile upload safety primero.** Después H, porque ambos tocan UserController: un dueño evita conflictos. Documentos P por familia con Infra tras inventario, no refactor universal.

### DEV 2

**B Material Save** con tickets MAT-03/04/05. Coordinar M con Producto. Después C porque comparte MaterialController; no dos devs editando ese archivo para problemas parecidos. Q espera contrato compartido de lookup C.

### DEV 3

**F Financial Lost primero**, siguiente pantalla pequeña desbloqueable. Luego G → O → I con dueño financiero único. Coordinar K por FinancePurchaseCreditGuard; no editar guard central simultáneamente. J puede certificarlo QA dedicado en entorno aislado.

### DEV 4 / Product

Decidir **M MAT-01**, **L CASH-02/03/04** y **N conservar/retirar**. Matriz conversión D sin privilegios inventados. Si hay cuarto desarrollador real, D/E secuenciales porque ambos alcanzan QuoteController/contactos. Tres desarrolladores + Producto tienen trabajo independiente inmediato; no hace falta fabricar paralelismo en controllers centrales.

## Verificación de entrega

Sólo se crea este documento; cambios previos del workspace preservados. Backend/frontend/data sin modificaciones, sin commit. No mutaciones, exports, proveedor ni documentos abiertos. **PASS:** `git diff --check -- docs/REAL-NEXT-DEV-PARALLEL-WORK-2026-11-22.md`; comprobación adicional del archivo nuevo con `git diff --no-index --check -- NUL docs/REAL-NEXT-DEV-PARALLEL-WORK-2026-11-22.md` y lectura de whitespace sin líneas con espacios finales. Aviso LF→CRLF de Git informativo, sin error de whitespace.

**TOTAL CLICKABLE SCREEN LINKS: 33** (URLs únicas; incluye templates marcados, no sólo rutas ejecutables con dataset).

## Addendum — CASH MOVEMENTS ADMIN readiness reassessment — 2026-11-22

**CASH MOVEMENTS ADMIN — FRONTEND UNLOCKED**

**Readiness: READY FOR READ-ONLY FRONTEND.** La autorización administrativa ausente en el diagnóstico histórico está implementada en HEAD. No añadir esta superficie a WAIT ni pedir nuevamente ese gate a DEV. No está homologada todavía: esta revisión no implementa UI.

### Ruta, render y contrato de datos

| Elemento | HEAD actual |
|---|---|
| Pantalla | [Cash Movements Admin](http://127.0.0.1:8080/dashboard/cash-movements/admin) |
| Route name | `cashMovement.admin.index` |
| Controller | `CashBoxController@adminIndex`, línea1117 |
| View | `cashBox.admin_index` → `cashBox._admin_legacy` para esta ruta |
| Endpoint GET | `/dashboard/cash-movements/admin/list` |
| Data route/action | `cashMovement.admin.list` → `CashBoxController@adminList`, línea1293 |
| Data implementation | `buildListResponse($request, null, true)`, línea1372 |
| JS | `public/js/cashBox/admin_index.js` |
| Response | `data` + `meta`: current_page/last_page/total/from/to/per_page |
| Filtros activos | q, user_id, cash_box_id, type, subtype_id, date_from, date_to, page; per_page backend limitado1–100, fallback10 |
| Columnas | ID, fecha, usuario, caja, tipo, subtipo, estado, monto, abonado, comisión, descripción, acciones |

`cashBox.admin_index` es dispatcher compartido: cuando `readOnly` viene del Financial Summary incluye `cashBox.summary_movements`; Admin no recibe esa bandera e incluye el legacy. Que Summary esté integrado **no** significa que Admin lo esté. `config/venti-next.php` incluye My Movements/CashBox/Subtype, no `cashMovement.admin.index`. No modificar esta allowlist durante la auditoría.

### Autorización actual y alcance

La colección de rutas Laravel, después de resolver middleware del controller, devuelve para **ambas rutas**:

`web → auth → check.user.enabled → password.changed → tenant.context → permission:listCashMovementAdmin_cashBox`.

`CashBoxController::__construct:64–69` asigna esa capacidad a adminIndex y adminList. No depende de ocultar el menú. La ruta no necesita repetir el middleware en routes/web.php para que sea efectivo.

**Owner/admin nominal:** no hay `tenant.owner`, `platform.admin`, prueba de nombre de rol ni bypass Owner en estas dos acciones. El contrato es una **capacidad administrativa explícita y delegable**, `listCashMovementAdmin_cashBox`, resuelta mediante `user->can()` en Spatie PermissionMiddleware. Un usuario no Owner con ese permiso puede consultar; un Owner sin capacidad no obtiene un bypass especial por este controller. AuthServiceProvider no registra Gate::before de Owner. No imponer una segunda regla de rol que HEAD no tiene.

**Scope:** TenantContext exige tenant, company y branch activos; InitializeTenantContext valida tenant activo, company/branch activos y memberships activos del usuario. Query de datos exige `cash_movements.tenant_id`, `company_id` y branch de sesión (líneas1508–1510). Admin no aplica el filtro de usuario propio: permite ver usuarios del local, restringibles con user_id. No es un listado global de todo el ERP aunque el título legacy diga «Listado global».

Cajas: company actual, activas, branch actual o branch NULL (caja compartida). Sesiones superiores: **del usuario autenticado**, company/branch actual, abiertas. Subtipos: company actual y activos. Lookup de usuarios: IDs presentes en movimientos del company/branch actual, no User::all. Modelos CashMovement/CashBox/Subtype/Register aplican tenant scope. Joins de nombres usan referencias almacenadas: no se certifica reparación de FK históricas corruptas por pasar esta prueba.

### Evidencia focalizada sin persistencia

PHP7.3.33, bootstrap Laravel real, PDO MySQL con **START TRANSACTION READ ONLY / ROLLBACK**, session/cache en memoria. Sin login POST, sin usuarios temporales, sin modificar permisos, sin navegador ni archivos de código/test. Prueba de middleware/acciones **en proceso**, no smoke HTTP por socket del servidor8080 ni certificación del stack de cookies/sesión completo.

- Usuario real ID2/T1, sin capacidad administrativa: middleware real Spatie lanza `UnauthorizedException` con **status403** para index y list; no alcanza acción. Las rutas reales contienen ese middleware, verificado con `gatherMiddleware()`. Por tanto el acceso directo autenticado sin permiso queda protegido; anónimo sigue su flujo auth/login, no se afirma que deba dar403.
- Owner EDESCE, capacidad efectiva presente, contexto inicializado por middleware real T1/C1/B1: list200,21 filas; `adminIndex()` retorna `cashBox.admin_index`, lookup users `[4]`.
- EDESCE T1/C1/B8: list200, IDs `[272,254]`, users `[4]`.
- EDESCE T1/C2/B2: list200,0 filas, users vacío; contexto validado por InitializeTenantContext.
- Owner Gamarra T3/C3/B4: list200,0 filas.
- En T1/C1/B1, query manipulada tenant_id3/company_id3/branch_id4 **no cambia** los21 IDs porque scope viene de sesión; user_id999999 produce0 sin ampliar contexto.

No se pretende certificar todas las combinaciones de roles ni permisos mutadores. El caso negativo prueba el gate real sin conceder/revocar nada en DB.

### Dataset actual

| Tenant | Company | Branch | Filas cash_movements | IDs / lectura efectiva |
|---|---|---|---:|---|
| 1 | 1 | 1 | 21 | 250,251,252,253,255,256,257,258,259,260,261,262,263,264,265,266,267,268,269,270,271; mismos21 devueltos descendentes |
| 1 | 1 | 8 | 2 | 254,272; ambos devueltos |
| 1 | 1 | NULL | 241 | Históricos excluidos por branch activo; no reasignados |
| 1 | NULL | NULL | 2 | 248,249; excluidos por company/branch |
| 1 | 2 | 2 | 0 | Endpoint vacío |
| 3 | 3 | 4 | 0 | Endpoint vacío |

Total físico actual: **266 movimientos**. Sólo23 tienen company y branch del dataset contextual observado. Los243 históricos sin branch/contexto no son un fallo de empty state ni autorización; posible DATA REMEDIATION separada. No restaurar su visibilidad con OR branch IS NULL.

### Acciones y límites de la integración siguiente

| Acción actual | Ruta / guard observado | Read/mutation | Decisión de alcance |
|---|---|---|---|
| Buscar/filtrar/paginar | adminList, listCashMovementAdmin_cashBox | GET lectura | Integrar |
| Ir a sesión propia abierta | GET cashRegister.session `/dashboard/cash-registers/session/{cashBox}` | Navegación de otra superficie | Preservar sólo navegación segura ya aprobada; no equivale a administrar sesión ajena |
| Abrir sesión propia | POST cashRegister.open `/dashboard/cash-registers/open`; contrato propio contextual, no gate administrativo de listado heredado automáticamente | Mutación | No ejecutar/certificar en fase read-only |
| Regularizar | POST cashMovement.regularize `/dashboard/cash-registers/regularize`; `permission:regularize_caja` y CashRegularizationService::authorize/contexto | Mutación | Fuera de QA read-only; no conceder por ser admin del listado |
| Imprimir venta | GET puntoVenta.print; `SaleListGuard:print,route,id` | Documento | Fuera de alcance; no abrir ni certificar |
| Ver factura de entrada | GET `/dashboard/factura/compra/ver/{entry}`, ruta propia de Invoice | Otra superficie/documento | Fuera de alcance; capacidad admin de caja no autoriza automáticamente |

Botones de caja pertenecen al usuario actual incluso en Admin. JS muestra regularización sólo con `regularize_caja`, movimiento sale/income pendiente y no cancelado; partial usa @can y servicio valida otra vez. `permissions` pasado a la vista usa **getPermissionsViaRoles**, mientras el gate real admite permiso efectivo: posible subpresentación de acciones con permiso directo. No es bypass backend ni blocker del read path; no copiar esa limitación como nuevo sistema de permisos al modernizar. El flag READ_ONLY del JS suprime botones por fila, pero no basta por sí solo para retirar apertura de caja: el alcance read-only debe revisar también toolbar/handlers, sin ejecutar esas operaciones.

### GET side effects y deuda visual

adminIndex y adminList/buildListResponse hacen SELECT/joins/paginación; no Audit::create, apertura de caja, cambios de saldos ni escrituras de negocio. InitializeTenantContext puede resolver **sesión en memoria**; no es mutación de caja. Click confirmado en Abrir/Regularizar sí hace POST, no forma parte del GET de consulta.

JS error callback actualmente reutiliza empty state: la próxima integración debe distinguir403/422/error backend de0 resultados; es adaptación frontend real, no motivo para devolver la pantalla a DEV. No sustituir Admin por My Movements ni habilitar su allowlist mediante wildcard. Reutilizar patrón operativo aprobado, sin CSS nuevo como objetivo, y sin certificar documentos/mutaciones por integrar la tabla.

### Impacto en el informe / siguiente paso

**Bloqueo histórico BACKEND SYNC REQUIRED — AUTHORIZATION: RESUELTO para vista y endpoint de datos.** No hace falta nuevo paquete DEV ni añadir un blocker a los36 ya contados. Los17 paquetes permanecen iguales. Se agrega **una superficie READY FOR READ-ONLY FRONTEND**, no una superficie integrada más. El siguiente frontend puede ser Admin Cash Movements sin esperar decisiones CASH-02/03/04, que pertenecen a operación/configuración y siguen separadas.

**Recomendación actualizada:** integrar esta pantalla antes de esperar el fix Financial Lost si se busca trabajo frontend inmediato. Financial Lost sigue siendo el desbloqueo DEV pequeño, pero ya no es necesario esperar un fix para encontrar una siguiente pantalla viable.

Código/allowlist/backend/CSS/JS: sin cambios. Sólo este informe actualizado; sin commit ni mutaciones. QA de render visual/responsive pendiente de la futura fase de integración.
