# REAL NEXT — R7.7A — Normal Purchase & Entry Purchase Forms Homologation

2026-11-25. Rama feature/venti-next-frontend. PHP7.3.33 obligatorio; aplicación http://127.0.0.1:8080. Sin infraestructura, backend, CSS ni cambios de JS funcional. Tres superficies homologadas. QA de persistencia real pendiente; Guardar permanece operativo. No R7.7B/R8, commit/push/merge.

## Fuentes y alcance

AGENTS; informe R76 y plan post-DEV; reconciliación2026-11-25; informes R3.1 Normal/Express y R4 Entry/Invoice; backlog canónico y DEV; docs/flujos/auditoria-materiales-entradas-contexto.md y entrada-compra-acceso.md. HEAD actual prevalece: PurchaseFormReadAccess usa purchase_edit→update_orderPurchaseNormal, aunque el texto histórico DEV dice edit_orderPurchaseNormal. No crear permiso alternativo ni exigir Create para Edit.

Historial de los formularios: df5c53f,82e92aa,8cf9093,bc87290. La evolución DEV conserva actor autenticado/snapshot, catálogo StockItems y variantes, destinos contextuales y payload operativo. No restaurar aprobador editable ni cotización industrial comentada por mera existencia histórica. R7.6 ya recuperó navegación FR017/018/046; esta fase no suma nuevos FR. Los listados de órdenes, entradas y órdenes para almacén permanecen intactos.

## Superficies y contratos

| Pantalla | URL | Blade | JS operativo | Ruta / permiso |
|---|---|---|---|---|
| Crear Normal | http://127.0.0.1:8080/dashboard/crear/orden/compra/normal | orderPurchase.createNormalV2 | js/orderPurchase/createNormalV2.js + companyContext.js | order.purchase.normal.create / create_orderPurchaseNormal |
| Editar Normal4 | http://127.0.0.1:8080/dashboard/editar/orden/compra/normal/4 | orderPurchase.editNormalV2 | js/orderPurchase/editNormalV2.js + companyContext.js | GET sin nombre / update_orderPurchaseNormal |
| Crear Entry | http://127.0.0.1:8080/dashboard/crear/entrada/compra | entry.create_entry_purchaseV2 | js/entry/entry_purchaseV2.js | entry.purchase.create / create_entryPurchase |

OrderPurchaseController::createOrderPurchaseNormal/editOrderPurchaseNormal; EntryController::createEntryPurchase. Middleware de aplicación/autenticación/contexto existente y permission preservados; no rutas nuevas. NormalPurchaseReadAccess/NormalPurchaseGuard y ScopedPurchaseCompany mantienen tenant/company; cabecera Normal company-wide, no inventar branch ownership. MaterialInventoryScope/PurchaseFormReadAccess exigen consumidor/capacidad/contexto y limitan catálogo habilitado, StockItems/variantes/stock coherente por empresa y almacén general/local. EntryWarehouseResolver entrega almacenes/destinos/contexto; EntryDestinationValidator valida payload al guardar. Supplier::all() conserva scopes del modelo; no se reemplaza por catálogos globales desde JS.

GET Create Normal escribe Audit técnico existente (usuario, acción de apertura y duración); Edit/Entry render no efectúa compras/stock. En captura QA Audit se cancela sólo en el proceso aislado y DB permanece READ ONLY. No se certifica Store/Update ni se invoca SUNAT/tipo de cambio.

### Lookups / dependencias

- Precarga GET /dashboard/get/materials/stock/items/entry con consumer purchase_create / purchase_edit / entry_create según formulario.
- Select2 búsqueda GET /dashboard/materials/order/purchase/search o /dashboard/materials/entry/search con search + consumer.
- Variantes GET /dashboard/materials/{material}/stock-items-entry con el mismo consumer; se preservan material_id/stock_item_id y su distinción.
- Proveedor y PaymentDeadline vienen de controller; selectable() en create y selectable(id histórico) en Edit. Creado por / última modificación / aprobador histórico siguen sólo lectura. QuotesRaised se carga en backend, pero selector quote_id sigue comentado: no se reactivó.
- Entry no contiene un selector vivo de orden. purchase_order es referencia textual; recepción de una orden usa la ruta separada /dashboard/crear/entrada/compra/orden/{id}, createEntryOrder y create_entry_purchase_order con receiptKey/contexto. Se conserva navegación desde órdenes para almacén, sin sustituir ese flujo ni homologarlo en esta fase.

## Inventario anterior / posterior

Comparación automatizada del render antes/después con mismo dataset y JS actual. Atributos id/name/type/required/readonly/disabled/min/max/step/data-* idénticos; también templates e IDs de modales. Valores seleccionados y precarga se contrastaron mediante payload.

| Pantalla | Controles form antes→después | Modales antes→después | Capacidades |
|---|---|---|---|
| Normal Create | 23→23 | 1→1 | Conservadas |
| Normal Edit4 | 52→52 (incluye3 líneas existentes) | 1→1 | Conservadas |
| Entry Create | 29→29 | 5→5 | Conservadas |

Controles no equivale a nuevos FR. Total103 controles preservados, siete instancias de modal preservadas. Inputs en templates se compararon adicionalmente. Capacidad perdida:0. CSS nuevo:0. JS modificado:0.

### Normal Create — campos y acciones

CSRF; purchase_order readonly, date_order/date_arrival d/m/Y, observation, quote_supplier, supplier_id, payment_deadline_id, currency_order, regularize_order, material_id, stock_item_id. Actor readonly. Material search, cantidad, Agregar; plantilla materials-selected con ID/SKU/descripción/cantidad/precio C-IGV/precio S-IGV/total y Eliminar. Totales editables subtotal/taxes/total con data-* y serialización *_send. Moneda y regularización mantienen estado/readonly del plugin original. Unidades vienen de catálogo, no hay selector nuevo de unidad, descuentos ni impuestos nuevos.

Guardar orden de compra sigue type=button→storeOrderPurchase; POST order.purchase.normal.store, multipart FormData + items JSON/subtotal_send/taxes_send/total_send. Regresar conserva route order.purchase.normal.indexV2; collapse original conservado. modalStockItems con variantes, cantidad/precio y selección mantiene IDs/handlers.

### Normal Edit — diferencias preservadas

Todos los campos de Create + order_id; valores de orden4, fechas históricas, moneda/regularize, proveedor/plazo, createdUser/updatedUser y aprobador histórico condicional. fillItems mantiene detail_id, stock_item_id, SKU, precio/precio sin IGV, cantidad y total. Tres detalles de4 conservados (StockItems77/79/78). Guardar cambios y totales envía POST order.purchase.normal.update + state/regularize además de items/totales.

Guardar línea data-edit→POST /dashboard/update/detail/order/purchase/normal/{detail}; Eliminar línea data-delete + data-stock-item/data-material→POST /dashboard/destroy/detail/order/purchase/normal/{detail}/stock-item/{stockItem}. Confirmaciones, CSRF, X-Purchase-Tenant/Company y validaciones existentes intactas. Filas nuevas se eliminan sólo del estado local hasta guardar. No se convierten operaciones independientes de detalle en un guardado masivo distinto.

### Entry Create — campos y acciones

CSRF; entry_context_company_id/branch_id hidden; date_invoice d/m/Y; referral_guide, purchase_order, supplier_id, invoice, deferred_invoice, entry_type readonly, image, currency_invoice, observation, imageOb, material_id, stock_item_id, lot, date_vence, my-checkbox (agrupado), almacen. Inputs de material/cantidad/precio conservados, incluidos step/pattern/onblur originales. Catálogo entry-warehouse-catalog con errores de configuración y default backend, sin IDs hardcodeados nuevos.

Selectores y estados condicionales: perecible habilita lote/vencimiento; variantes desactivan cantidad/precio principal y usan modal; itemeable sin agrupar solicita series manuales, rechaza faltantes/duplicadas; destino válido se aplica a filas nuevas. Items mantienen entry_group_id, id_material, stock_item_id, warehouse_id/id_location, lote/vencimiento, state/is_grouped y códigos. Totales/cálculo1.18 sin modificaciones.

Guardar compra type=submit→storeOrderPurchase(event), POST entry.purchase.store con FormData/items JSON y archivos; guard disabled/recuperación en error intactos. Cancelar sigue type=reset (no se convirtió en navegación); breadcrumb vuelve a entry.purchase.indexV2. Eliminar fila local se mantiene.

Modales preservados: modalAddItems, modalAddGroupItems, modalStockItems, modalCodigosMaterialSimple, modalCodigosVariante. Los dos primeros conservan templates/hooks históricos; la apertura programática QA no se presenta como un nuevo trigger operativo. Series simples/variantes conservan sus handlers y mensajes.

## Validaciones preservadas

StoreOrderPurchaseRequest: purchase_order requerido, purchase_condition/observation/quote_supplier nullable|string; NormalPurchaseGuard conserva validación contextual/referencias y restricciones de edición. StoreEntryPurchaseRequest conserva invoice/date_invoice/items/entry_type, referencias de5…255 caracteres, supplier exists y opciones nullable; servicio valida destinos. No endurecer ni replicar reglas en HTML. Misma lógica de cantidad/precio, no duplicación, series, cálculo/redondeo y rechazo backend visible. Moneda/tipo cambio real no ejecutados.

## Adaptación visual / paridad

Referencia directa: venti-next material.create, VISUAL-DIRECTION y patrón operativo real invoice.create_invoice. Clasificación REUSE WITH CONTRACT ADAPTATION. Shell/breadcrumbs existentes; next-page-heading/eyebrow; cards neutrales, next-form-section en wrapper y cuerpos; next-toolbar-actions flex-wrap para acciones, Guardar primario, requeridos next-required; modales next-aux-modal. Conservados collapse y todos los botones.

Normal: las filas antiguas div.row/col se presentan como tr/td dentro de table-responsive + next-data-table. Se conservan .material-row y cada atributo/hook; eliminar de Create sigue button→celda→fila, handlers Edit usan closest('.material-row'), cálculo usa find(). No adaptación JS. Cambiar sólo la presentación evita apilar siete campos por producto en móvil. Columnas código/cantidad centradas, descripción izquierda e importes derecha, headers alineados.

Entry: grid existente adaptado a col-12/col-6/col-lg-*; tabla compacta con scroll local y alineación compartida; resumen full-width móvil. Se retira altura fija300px y CSS local de select2-search__field, cubiertos por patrones compartidos; sin reglas CSS nuevas ni cambios globales. La tabla de variantes también reutiliza next-data-table para evitar SKU partidos en muchas líneas; el scroll queda dentro del modal. Plugins y dependencias conservados.

## QA funcional y responsive

PHP7.3.33: render de las tres vistas200 en EDESCE C1; Edit4 devuelve404 en C2 y Gamarra. Create Normal/Entry200 en los tres contextos. Precarga por consumidor: C1=47, C2=14, Gamarra=2 StockItems; búsqueda devuelve14/2/2 materiales padre respectivamente. Lectura dedicada START TRANSACTION READ ONLY/ROLLBACK, array session/cache, Audit cancelado sólo en proceso. No usuarios temporales ni datos persistidos. No representa certificación HTTP completa de permisos backend, ya validada por DEV.

Chrome headless aislado (sin escritorio/navegador del propietario): HTML/DTOs reales capturados; red interceptada, assets locales. Select2 AJAX mantiene consumer; error de material vacío, filas simples/variantes, recalcular precio y totales; baseline y versión nueva producen payload equivalente. Se normalizan sólo IDs/códigos aleatorios de Entry para comparar. Seis POST de formulario interceptados con422 SIMULADO; mensajes visibles y botón recuperado. CSRF y cabeceras contextuales preservados. Ningún POST alcanzó Laravel.

QA adicional: agregar/eliminar variante local en3 formularios; Edit guardar/eliminar detalle→dos POST interceptados422 y filas permanecen; series manuales simples→error requerido y dos códigos SIMULATED válidos sólo en memoria; cinco modales Entry abiertos/cerrados sin guardar negocio. Todos los casos ejecutados sin pageerrors.

Matriz:320/375/390/576/768/992/1200/1440 ×3=24 comprobaciones con filas cargadas y selector de variantes abierto; sin overflow global. Scroll horizontal LOCAL permitido para las tablas. Capturas finales desktop/móvil esperan fin de animaciones de Bootstrap; las capturas transitorias no se usan como evidencia final. No certificación de uploads reales, compras, recepción, stock, redondeo fiscal ni concurrencia.

Artefactos: r77-read.php/data.json; r77-before-*.blade.php/html; r77-browser.cjs/results.json (inventario + payloads); r77-final-responsive-results.json (24 checks después del ajuste de tabla de variantes); r77-operations.cjs/results.json; r77-final-*.png bajo C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/. Sin credenciales. Fixtures SIMULATED de series/422 no persisten en DB.

## Allowlist / archivos

Sólo rutas de vista: order.purchase.normal.create, entry.purchase.create y URI exacta dashboard/editar/orden/compra/normal/{id} (sin route name). No AJAX/store/update. venti.min.css + venti-next.css reutilizados.

Archivos intervenidos: config/venti-next.php; resources/views/orderPurchase/createNormalV2.blade.php; resources/views/orderPurchase/editNormalV2.blade.php; resources/views/entry/create_entry_purchaseV2.blade.php; este informe; plan post-DEV y backlog canónico (seguimiento). Backend/JS/CSS/infra sin cambios.

## Backend Sync Notes / límites

[OPEN] ENTRY-R75-01 y ENTRY-R4-01 permanecen fuera de esta fase; no bypass ni modificación. [PENDING] Persistencia real Store/Update/detalles/uploads y recepción: QA comercial no ejecutado; no bloquea Guardar. [UNCHANGED] Contratos DEV y limitación documental del entorno8080 permanecen. No nuevos findings backend demostrados durante la integración. No rehabilitar módulos legacy ni introducir selección de orden en Entry directa.

Estado:3/3 FRONTEND HOMOLOGATED; FUNCTIONAL PARITY PASS para inventario y escenarios ejecutados; FORM SUBMISSION/PAYLOAD PASS mediante interceptación; MUTATION QA PENDING. Capacidades globales74 recuperadas +10 bloqueadas +23 excluidas=107, sin doble conteo. PHP Blade compile, tres JS syntax checks y git diff --check PASS. Esperar revisión del propietario.

## R7.7A/B — Owner visual corrections — 2026-11-25

Corrección focalizada posterior a la aprobación funcional. Sin cambios backend, JavaScript operativo, CSS, contratos ni infraestructura. Sin POST ni mutaciones comerciales. Sin commit/push/merge ni inicio R7.7C/R8. Los cambios documentales de R7.7B preexistentes se conservan.

La regla permanente **Desktop-first density for operational field groups** se incorporó a `AGENTS.md` y `docs/VENTI-NEXT-COMPONENT-PATTERNS.md`: proporción, densidad, alineación y aprovechamiento horizontal forman parte de la aceptación visual; ausencia de overflow por sí sola no basta. Comprobación futura R8, sin barrido global actual.

QA Chrome headless aislado con HTML renderizado por Laravel y assets actuales; lecturas bajo transacción READ ONLY, sin controlar navegador/escritorio del propietario. Perfil probado en ambas pestañas a 320/375/768/992/1200/1440/1920/2560. Entrada en esas mismas resoluciones: una fila completa a 1440/1920/2560; redistribución en anchuras menores cuando el área de contenido no alcanza. Cero overflow global y pageerrors. Los 8 controles conservan su orden. Todas las capturas son de viewport real; no certificación de mutaciones.

Comparación de atributos funcionales del HTML completo antes/después (incluye shell): Perfil267 y Entrada303 elementos idénticos en IDs, names, values, methods/actions, CSRF, data-*, href, required/disabled/readonly y handlers inline. Sólo clases visuales, wrapper de grilla y roles/ARIA de pestañas cambiaron. Capacidades perdidas:0. Conteos FR sin cambios:74+10+23=107.

Blade compile PHP7.3.33: PASS. `git diff --check`: PASS. JS sin cambios; no se repite QA funcional de R7.7A/B.

### Entrada: Materiales en una fila de escritorio

Causa: grilla original sumaba18 columnas (4+7x2), forzando segunda fila. Se reutiliza `form-row` con buscador col-xl-3, lote/cantidad/precio col-xl-1, fecha/almacén col-xl-2 y switch/Agregar col-xl-auto. `col-md-*` y `col-*` mantienen redistribución natural. Fecha conserva espacio para d/m/Y; Agregar pierde únicamente btn-block, mantiene icono, aria-label, id y handler. Label d-block alinea su eje. La ayuda del almacén permanece debajo dentro de su columna sin desplazar la fila.

A1440 los controles están alineados con diferencia máxima de2px (geometría de plugins/botón), también a1920/2560. No formulario con scroll horizontal. La tabla conserva su scroll local.

Archivo de implementación: `resources/views/entry/create_entry_purchaseV2.blade.php`.

### Evidencia antes/después

- Materiales1440: [antes](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-before-entry-1440.png) / [después](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-after-entry-1440.png).
- Materiales1920: [antes](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-before-entry-1920.png) / [después](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-after-entry-1920.png).
- Materiales móvil: [antes](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-before-entry-375.png) / [después](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-after-entry-375.png).

Matriz y scripts: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/owner-final-results.json`, `owner-final.cjs`, `owner-tab-states.json`.
