# R7.7C — QuoteSale / Purchase Material Report

Fecha: 2026-11-25. Rama: feature/venti-next-frontend. PHP 7.3.33; artisan serve en http://127.0.0.1:8080. Sin cambios de infraestructura, backend ni CSS. Sin commit.

## Superficies / clasificación

| Superficie | URL | Estado inicial | Resultado |
|---|---|---|---|
| Crear cotización | http://127.0.0.1:8080/dashboard/crear/cotizacion/venta | VISUAL HOMOLOGATION REQUIRED; fuera de allowlist | FRONTEND INTEGRATED; persistencia QA pendiente |
| Editar cotización | http://127.0.0.1:8080/dashboard/editar/cotizacion/venta/79 | VISUAL HOMOLOGATION REQUIRED; fuera de allowlist | FRONTEND INTEGRATED; precarga y guardado por equipo conservados |
| Órdenes por material | http://127.0.0.1:8080/dashboard/reporte/de/ordenes/compra/por/materiales | VISUAL HOMOLOGATION + FUNCTIONAL RECONCILIATION REQUIRED | FRONTEND INTEGRATED; Exportar visible según permiso vigente |

Registrar comprobante /dashboard/registrar/comprobante/venta/Boleta: revisión de contrato compartido únicamente; no homologado en esta fase. Ninguna de las tres superficies principales estaba ya homologada.

## Fuentes / contratos DEV

Contraste de HEAD con AGENTS.md, plan post-DEV, reconciliación 2026-11-25, backlog funcional y DEV; docs/flujos/AUDITOR-COTIZACIONES-PAUSAS.md (D) y AUDITOR-Q-COMPRAS-EXPRESS.md (Q). Antecedente QuoteSale: REAL-NEXT-QUOTES.md. No se presenta la evidencia aislada DEV como una certificación HTTP nueva propia.

### QuoteSale

- Vistas quoteSale/create.blade.php y edit.blade.php; scripts public/js/quoteSale/create.js y edit.js, sin modificaciones.
- Rutas quoteSale.create / quoteSale.edit; permisos create_quoteSale / edit_quoteSale. Store/update permanecen intactos.
- QuoteFormReadAccess::catalog admite create_quoteSale **o** edit_quoteSale; los GET de materiales y totales conservan esa alternativa. No se agregó Owner bypass ni requisito Create para Edit.
- QuoteBranchCatalog valida tenant/company/branch activos y acceso; QuoteBranchContext protege binding. No se amplió ownership desde frontend.
- Cliente/contactos: contrato contextual existente de QuoteFormReadAccess; /dashboard/get/contact/{customer}. En QA se leyó cliente de la cotización79 y sus dos contactos; selector incluye placeholder.
- Create/Edit registran Audit técnico de pantalla/usuario/tiempo. La extracción QA utilizó transacción READ ONLY y supresión del evento Audit sólo dentro del proceso temporal de prueba, no en la aplicación. No se afirma haber probado ese write.
- Registro de comprobante usa QuoteFormReadAccess::conversion (Edit), tipos Boleta/Factura/Ticket; cajas/subtipos tenant/company y rama elegible. Buscar utiliza QuoteBranchReadScope local, company, cotizaciones confirmadas sin venta no anulada; getDataIndividual valida binding. No se ejecutó conversión, emisión ni proveedor.

### Inventario preservado

Datos comerciales, código, descripción, fechas, plazo, entrega, cliente/contacto, observaciones Summernote; productos, presentación, cantidades, selección Items, precios/impuestos/descuentos/totales, mano de obra, filas dinámicas, confirmaciones, retorno, CSRF/contexto y guardado. No se agregaron campos de moneda/archivos que no renderiza el formulario actual. El escritor conserva las entradas files existentes en FormData; no se subió ningún archivo.

Comparación automática de atributos funcionales (incluye shell, templates y formularios): Create **336 → 336**, Edit **355 → 355**, sin diferencias en IDs/names/data/values/methods/hooks; Report **255 → 256**, único agregado: control Exportar permitido al contexto QA. Son conteos de nodos/contratos, no capacidades FR.

Se conservan las clases card-success/card-gray-dark utilizadas por JS para señalar modificaciones; no se neutralizan estados funcionales por estética. Plugins Select2, Datepicker, Summernote, confirmaciones y handlers permanecen.

**Observación preexistente, no regresión R7.7C:** el botón btn-guardar_datos_generales está comentado en Edit antes y después; su handler sigue en edit.js. La prueba no pudo activar ese control. No se afirma guardado general validado ni se reactivó unilateralmente: confirmar intención funcional por separado. El guardado por equipo sí emite la solicitud existente. No constituye defecto backend demostrado.

### Documentos

No se añadieron enlaces documentales ni rutas públicas. Estas vistas no ofrecen lectores PDF/planos independientes que recuperar. Escritores actuales sin cambios; adjuntos/upload no ejecutados. Lectores de listado/detalle fuera de alcance. J/P permanece cerrado por decisión de negocio; protección HTTP directa pendiente en despliegue, sin instalar Apache ni bloquear controles por artisan serve.

## Reporte — contrato Q

- Ruta report.orders.by.materials; controlador OrderPurchaseController, reportOrderPurchaseByMaterial / getReportOrderPurchaseByMaterial / exportReportOrdersByMaterial.
- Permiso report_orderPurchaseExpress en vista, fetch y export. Compras Express sigue pausado; reporte no depende de reactivarlo.
- Scope tenant + company mediante contexto y ScopedPurchaseCompany; no se añadió filtro branch.
- Fetch GET /dashboard/get/data/order/purchase/by/material/{page}: year, material, startDate, endDate; fechas d/m/Y. Material obligatorio según JS actual. Sin escrituras de negocio detectadas en estos métodos de consulta.
- Export /dashboard/exportar/reporte/ordenes/by/material/v2/: start, end, material, year. Sólo navegación interceptada; no se generó ni descargó Excel.
- Columnas reales: código, año, observación, fecha de orden, llegada, proveedor, usuario aprobador, tipo/estado, moneda, cantidad, precio, total. Importes/cálculos/monedas sin cambios. No acciones por fila ni columnas configurables que inventar.
- Paginación por10, currentPage/totalPages/totalFilteredRecords/startRecord/endRecord. Sin sustitución por DataTables.
- Gate previo Exportar: hasanyrole(admin|principal|cordinator_operations). Sustituido por can(report_orderPurchaseExpress), conforme al contrato DEV efectivo; no se cambió permiso backend.
- JS sólo agrega loading y manejo de error no JSON. Antes responseJSON.message lanzaba excepción si backend devolvía HTML; ahora error visible, limpia resumen/paginación obsoletos, conserva mensajes JSON. Error nunca se presenta como cero resultados.

## Visual parity / allowlist

Reutilizados next-page-heading, next-form-section, next-aux-modal, next-list-summary, next-data-table, next-list-pagination y Font Awesome + next-row-action-item-icon. Densidad operativa y gutters existentes. Reporte elimina CSS local obsoleto (tipografía/colores/posición forzada del calendario), sin crear CSS.

Allowlist agrega exclusivamente quoteSale.create, quoteSale.edit, report.orders.by.materials. Sin JSON/export/documentos. No modificaciones a otras superficies.

## QA — evidencia y límites

Lectura real contextual: Owner EDESCE, tenant1/company1/branch1, cotización79 con una línea precargada. Órdenes actuales C1: IDs2,3,4; materiales con detalles12,13,14. Consulta material1: vacío; material13: órdenes3 y2, dos filas. No se fabricaron datos en DB.

Chrome headless aislado, assets permanentes reales y HTML renderizado por Laravel desde lectura contextual. Respuestas de productos/totales/contactos/reporte capturadas del controlador en sólo lectura. No se usó el navegador del propietario. **No equivale a smoke HTTP autenticado integral ni a recertificación de middleware DEV.**

| Prueba | Resultado / naturaleza |
|---|---|
| 3 pantallas ×320/375/390/576/768/992/1200/1440 | 24 PASS, sin overflow global ni pageerrors |
| Modal cliente/cantidad/Items, Create y Edit, 375/1440 | 12 PASS, sin overflow; apertura UI, no alta |
| Create producto real1/StockItem71, cantidad2 | Fila y total140 generados en memoria por JS existente |
| Create confirmar/Guardar | POST store/quote/sale interceptado; payload/contexto preservados; respuesta422 SIMULADA |
| Edit cotización79 | Una línea precargada; POST update/equipment/79/quote/sale/79 interceptado |
| Reporte vacío/poblado | DTOs reales de material1/13 |
| Reporte páginas1/2 | Fixture identificado SIMULADO de11 resultados, material conservado al paginar; dataset real de este filtro no alcanza segunda página |
| Fechas / export | URL interceptada con 01/01/2026–25/11/2026, material13; consulta conserva formato d/m/Y |
| Error HTML500 del reporte | SIMULADO; mensaje de error, sin excepción JS |
| Blade compile / JS syntax | PASS PHP7.3.33 view:cache y node --check Create/Edit/Report |

Artefactos locales: C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/ con prefijo r77c-. Resultados: r77c-results.json, r77c-operations-results.json, r77c-hooks.json; capturas r77c-create/edit/report-375/1440.png y modales. Render previo conservado en r77c-before-*.html / *.blade.php; no afirmar captura visual previa donde sólo existe HTML.

Mutaciones reales, uploads, exports, documentos, Nubefact/SUNAT: **0**. QA de persistencia sigue pendiente. No cambios de roles, permisos ni dataset. Sin nueva certificación de casos cross-context ni usuario sólo Edit; su contrato se comprobó en código y evidencia D de DEV.

## Archivos / cierre

Cambios R7.7C: config/venti-next.php; resources/views/quoteSale/create.blade.php; resources/views/quoteSale/edit.blade.php; resources/views/orderPurchase/report_orders_by_material_v2.blade.php; public/js/orderPurchase/report_orders_by_material_v2.js; este informe; plan post-DEV. Cambios previos ajenos preservados.

3/3 homologadas. Paridad respecto al inventario activo anterior: PASS, cero controles perdidos. Un gate frontend obsoleto corregido (Exportar), sin asignar FR duplicado. Backend0 / CSS0 / infraestructura0 / JS1 archivo de presentación de estados. Observación del guardado general de Edit queda explícita arriba, sin defecto backend nuevo demostrado. Backlogs no se alteran por una homologación sin nuevo FR.

Contabilidad global inalterada: **74 recuperadas +10 bloqueadas +23 excluidas =107**. Git diff --check PASS. No commit/push/merge. No R7.7D ni R8. Pendiente revisión del propietario.
