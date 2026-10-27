# REAL NEXT 10 — Cotizaciones / Operational List + Detail

Fecha: 2026-10-23. Rama: `feature/venti-next-frontend`; base de inspección: `df5c53f`.

**REAL NEXT 10 — FRONTEND READY / BACKEND SYNC REQUIRED**

Revalidación 2026-10-26 durante merge `feature/venti-next-frontend <- multitenancy`: **Creator filter Backend Sync: OPEN**. Los nuevos vínculos Worker/User no corrigen las consultas arrayUsers de Cotizaciones. Véase la sección de resolución de merge al final; las notas anteriores conservan su fecha de auditoría.

Fachada de listado general, facturadas, detalle e históricos integrada con Venti Next. No se modificaron controllers, rutas, middleware, scopes, requests, servicios, DB ni CSS. El filtro de creador entrega usuarios ajenos al contexto: este hallazgo impide declarar una integración funcional completamente cerrada. Las comprobaciones de aislamiento de cotizaciones no certifican aislamiento de todos sus catálogos auxiliares.

## Contract Audit

Fuente funcional: código actual de aliexpress. Fuente visual: Material List V2 / Advanced Operational List, Configuration CRUD y `venti-next/resources/views/puntoVenta/list.blade.php`. Se revisaron AGENTS, dirección visual y documentación REAL-NEXT de listas, responsive, permisos y Backend Sync antes de reconstruir vistas.

### Rutas y responsables

Las rutas siguientes llevan prefijo `/dashboard`. Pertenecen al grupo autenticado, con controles de usuario habilitado, contraseña y contexto tenant. `QuoteSaleController` incorpora `QuoteSaleBranchContext`; `QuoteBranchPendingController` utiliza explícitamente `QuoteBranchReadScope`.

| Método / ruta | Controller / action | Nombre / permiso |
|---|---|---|
| GET cotizaciones/venta | QuoteSaleController@index | quoteSale.index / list_quoteSale |
| GET get/data/quotes/sale/index/v2/{numberPage} | getDataQuotesIndex | sin nombre / list_quoteSale |
| GET cotizaciones/venta/facturadas | indexFacturadas | quoteSale.index.facturadas / list_quoteSale |
| GET get/data/quotes/sale/facturadas/index/v2/{numberPage} | getDataQuotesSalesIndex | sin nombre / list_quoteSale |
| GET ver/cotizacion/venta/{quote} | show | quoteSale.show / list_quoteSale |
| GET cotizaciones/venta/pendientes-sucursal | QuoteBranchPendingController@index | quoteSale.pendingBranches / list_quoteSale |
| GET cotizaciones/venta/pendientes-sucursal/{id} | QuoteBranchPendingController@show | quoteSale.pendingBranches.show / list_quoteSale |
| GET crear/cotizacion/venta | QuoteSaleController@create | quoteSale.create / create_quoteSale |
| POST store/quote/sale | store | quoteSale.store / create_quoteSale |
| GET editar/cotizacion/venta/{quote} | edit | quoteSale.edit / edit_quoteSale |
| POST update/quote/sale | update | quoteSale.update / edit_quoteSale |
| POST update/equipment/{id_equipment}/quote/sale/{id_quote} | updateEquipmentOfQuote | quoteSale.update.equipment / edit_quoteSale |
| POST destroy/quote/sale/{quote} | destroy | quoteSale.destroy / destroy_quoteSale |
| GET imprimir/cotizacion/cliente/{quote} | printQuoteToCustomer | sin nombre / printCustomer_quote |
| GET registrar/comprobante/venta/{type} | showRegistrarComprobante | show.register.comprobante / edit_quoteSale |
| GET quotes/buscar | buscar | quotes.buscar / sin permiso granular en ruta |
| GET get/data/quotes/sale/{id} | getDataIndividual | quotes.getDataIndividual / sin permiso granular en ruta |
| POST store/sale/from/quote | storeFromQuote | sin nombre / edit_quoteSale |
| POST sales/from-quote/{saleId}/recover-emission-persistence | recoverEmissionPersistence | quoteSale.recoverEmissionPersistence / edit_quoteSale |
| POST quotes/update-general | updateDatosGeneral | quotes.sales.update.general / sin permiso granular en ruta |
| POST raise/quote/sale/{quote} | raiseQuote | quote.raise.quote.sale / raise_quote |
| POST cotizaciones/venta/{quote}/recotizar | renewQuote | quoteSale.renew / renew_quoteSale |
| POST quote-sale/{quote}/confirm | confirm | quoteSale.confirm / confirm_quoteSale |
| POST quote-sale/{quote}/send | send | quoteSale.send / send_quoteSale |
| POST quote-sale/{quote}/close | closeQuote | quoteSale.close / finish_quoteSale |
| POST quote-sale/{quote}/reopen | activeQuote | quoteSale.reopen / finish_quoteSale |
| GET quote-sale/{quote}/decimals | getDecimalsQuote | quoteSale.decimals.get / edit_quoteSale |
| POST quote-sale/decimals | changeDecimalsQuote | quoteSale.decimals.change / edit_quoteSale |
| GET cotizaciones/venta/totales | indexGeneral: método no encontrado | quoteSale.list.general / list_quoteSale; BLOCKED |
| GET get/quote/sale/materials, get/quote/sale/materials/totals | getMaterials, getMaterialTotals | create_quoteSale; auxiliares de creación |
| GET quotes/stock-items/{stockItemId}/available-items | getAvailableItemsByStockItem | quotes.stock-items.available-items; auxiliar, sin permiso granular en ruta |

Referencia de rutas: `routes/web.php`, bloque 3318–3410. Crear/editar, emisión, recuperación de emisión y auxiliares se auditaron como dependencias; sus pantallas y operaciones de escritura no se modernizan ni se ejecutan en esta fase.

### Modelos, requests y servicios

`Quote` usa pertenencia tenant y campos company/branch. Las líneas se resuelven mediante `equipments`, `consumables`, `workforces`, materiales/stock items y sus unidades/presentaciones. Cliente, contacto y plazo de pago provienen de relaciones actuales. `User` no incorpora el mismo scope tenant: ver Q-01.

`StoreQuoteRequest` / `UpdateQuoteRequest` conservados. La validación de formulario compara los snapshots `form_tenant_id`, `form_company_id`, `form_branch_id` con el contexto activo. No se consideran esos inputs fuente de autoridad. La validación del padre no sustituye una revisión de todos los IDs auxiliares enviados en edición.

Servicios centrales: `QuoteBranchCatalog`, `QuoteBranchContext`, `QuoteBranchReadScope`, `QuoteSaleActions`, `QuoteStockReservationService`; impuestos/cálculo y emisión conservan los servicios actuales, incluyendo `SaleEmissionService` y `NubefactCompanyConfigService`. No se añadió cálculo comercial en JS.

### Contrato del listado

GET AJAX paginado, 10 registros por página. Respuesta: `data` y `pagination` (`currentPage`, `totalPages`, `startRecord`, `endRecord`, `totalRecords`, `totalFilteredRecords`). Orden actual: fecha de creación descendente, sin ordenamiento interactivo nuevo.

| Columna | Campo real / procedencia | Alineación |
|---|---|---|
| ID / código | id, code de Quote | centro |
| Descripción | description, desde description_quote | izquierda |
| Fecha | date_quote formateado por backend | centro |
| Forma de pago | deadline, relación | izquierda |
| Entrega | time_delivery, formato actual | centro |
| Cliente | customer, business_name relacionado | izquierda |
| Orden | order, code_customer | centro |
| Total SUNAT | total_sunat, total_importe registrado | derecha |
| Servicios adicionales | total_services, suma de servicios no facturables | derecha |
| Total cliente | total_cliente, total_importe + servicios no facturables | derecha |
| Moneda | currency, contrato de moneda actual | derecha |
| Estado | state / stateText calculados por backend | centro |
| Creador | creator, usuario relacionado | izquierda |
| Acciones | permisos y estado existentes | centro |

También llegan datos como fecha de validez, año, fecha de creación, decimales, send_state y has_active_sale. Creador no se renombra como vendedor. No se inventan columnas/filtros de vendedor o sucursal.

Se conservan `description_quote`, `year`, `code`, `order`, `customer`, `stateQuote`, `start`, `end`, `creator` como hooks DOM, y los nombres de query del JS original (las fechas se envían como `startDate` / `endDate`). Buscar reinicia página 1. Cancelación de petición anterior y contador de secuencia impiden que una respuesta antigua sustituya la búsqueda reciente.

General excluye cotizaciones vencidas y ventas activas; su selección por defecto usa los estados operativos existentes. Facturadas exige venta activa. La semántica de `created`, `send`, `confirm`, `raise`, `close`, `canceled`, `requote`, y la combinación de state/send_state/state_active/raise_status siguen en backend. No se unificó el comportamiento de fechas de ambos endpoints.

## Tenant / Company / Branch Scope

`QuoteBranchCatalog::validateContext` valida pertenencia y disponibilidad del contexto. `QuoteBranchReadScope::local` acota tenant + company + branch; `pending` acota tenant + company + branch NULL. `QuoteBranchContext::assertQuote` valida ID, tenant/company y branch; permite NULL únicamente para lecturas históricas autorizadas (show/print), rechaza su mutación. Otra sucursal o empresa no se reinterpretan desde frontend.

| Operación | tenant | company | branch | permiso | Estado de esta fase |
|---|---|---|---|---|---|
| Listado / facturadas AJAX | contexto | contexto | local activo | list_quoteSale | IMPLEMENTED WITH BACKEND DEPENDENCY: catálogo creador |
| Detalle completo | padre validado | padre validado | local o NULL histórico | list_quoteSale | IMPLEMENTED; QA de histórico real |
| Históricos listado/detalle | scope pending | scope pending | solamente NULL | list_quoteSale | IMPLEMENTED |
| Create / store | contexto validado | contexto validado | contexto, snapshot al guardar | create_quoteSale | enlace conservado; IMPLEMENTED WITH BACKEND DEPENDENCY, escritura no probada |
| Edit / update | padre validado | padre validado | local; NULL rechazado | edit_quoteSale | enlace conservado; IMPLEMENTED WITH BACKEND DEPENDENCY, IDs auxiliares por revisar |
| Update equipment | padre quote validado | padre quote validado | local + snapshot | edit_quoteSale | sin UI nueva; validar ownership de IDs hijos antes de ampliar |
| Anular (destroy) | padre validado | padre validado | local | destroy_quoteSale | IMPLEMENTED WITH BACKEND DEPENDENCY; callback conservado, no ejecutado |
| Print | padre validado | padre validado | local o NULL | printCustomer_quote | IMPLEMENTED WITH BACKEND DEPENDENCY: archivos PDF compartidos |
| Conversión a venta | quote acotada | quote acotada | local validado | edit_quoteSale en ruta | IMPLEMENTED WITH BACKEND DEPENDENCY; permisos UI divergentes |
| Buscar / datos individuales | local / padre | local / padre | local | auth/context; sin granular | contrato existente, no ampliar acceso |
| Confirm/send/close/reopen/decimals | QuoteSaleActions | QuoteSaleActions | local + contexto de formulario | permiso específico | callbacks conservados; no mutaciones QA |
| Recotizar | padre validado | padre validado | local | renew_quoteSale | callback conservado; requiere estado requote y validación backend |
| Raise | padre validado | padre validado | local | raise_quote | NOT PRESENT en acciones de esta lista |
| Update general | padre validado | padre validado | local + snapshot | no granular | BLOCKED para nuevas acciones frontend |
| Borrado físico / duplicado genérico | — | — | — | — | NOT PRESENT; anular y recotizar no equivalen a estas operaciones |

Matriz basada en inspección de código. No significa que las escrituras hayan sido probadas ni que todos los auxiliares estén certificados. Recuperación de emisión conserva su servicio actual; no se añadió acceso desde esta pantalla.

## Permissions

Se mantienen los permisos entregados por backend y los `@can` existentes. Ver usa list_quoteSale; editar/decimales edit_quoteSale; impresión printCustomer_quote; anular destroy_quoteSale; confirmación confirm_quoteSale; envío send_quoteSale; cierre/reapertura finish_quoteSale; recotización renew_quoteSale. El menú conserva las ramas por estado y has_active_sale. Canceladas no reciben nuevas mutaciones; requote mantiene recotizar si existe permiso; cerradas permiten reapertura según permiso. No se agregó raise.

`showPrices_quote` sigue controlando columnas monetarias y resumen del detalle. La lista ya entregaba importes independientemente de ese permiso; esto requiere decisión backend, no una nueva política JS. Los enlaces existentes Boleta/Factura/Ticket mantienen sus permisos Blade actuales; su ruta usa edit_quoteSale (Q-04).

## Historical Quotes Without Branch

Se conserva un acceso visible a `pendientes-sucursal`, con etiqueta **Histórico — sin sucursal**. No se les asignó branch_id, no se fabricó reparación ni se mezclaron con la consulta local. Listado histórico: búsqueda `q` por código/descripción, paginate(15), orden ID descendente. Detalle histórico: padre acotado y líneas de sus equipos; join de stock items restringido por tenant. Se mantienen únicamente Ver e Imprimir donde ya existían; no se ejecutó impresión durante QA.

## Operational List Mapping

| Contrato actual | Patrón aprobado | Clasificación |
|---|---|---|
| Listados general y facturadas | page heading + next-page-toolbar | REUSE DIRECTLY |
| Buscar / filtros existentes | next-list-toolbar, next-list-search-row, filtros avanzados | REUSE WITH CONTRACT ADAPTATION |
| Conteo de respuesta | next-list-summary | REUSE DIRECTLY; sin métricas ficticias |
| 15 columnas reales | next-data-table + table-responsive | REUSE WITH CONTRACT ADAPTATION |
| Acciones existentes | next-row-actions / next-row-actions-menu, portal compartido | REUSE WITH CONTRACT ADAPTATION |
| Estado backend | badge semántico Bootstrap/Venti | REUSE DIRECTLY |
| Paginación AJAX | paginación aprobada, hooks data-item | REUSE WITH CONTRACT ADAPTATION |
| Sin resultados | next-table-empty | REUSE DIRECTLY |
| Decimales | next-aux-modal + Select2 | REUSE WITH CONTRACT ADAPTATION |
| Históricos NULL | tabla compacta y aviso contextual | NEW REAL REQUIREMENT, resuelto con componentes existentes |

No se añadió menú de columnas ni filtros ficticios. La amplitud de la tabla se resuelve mediante scroll local y acceso por teclado. Las acciones dejaron de ser una hilera de iconos; conservan URLs, data attributes y callbacks.

## Detail Mapping

Identidad y datos comerciales, cliente/contacto, productos por equipo, servicios adicionales, descuento y resumen monetario se presentan con `next-form-section`, `next-section-header`, grid Bootstrap y tablas existentes. Se conserva la fórmula/formatos de cantidades, presentaciones, V/U, P/U, importes, servicios, descuento, gravada, IGV y total. El frontend no recalcula cotización ni stock.

Los controles readonly del detalle anterior se convierten en texto semántico; se retiran templates/modales de edición sin un flujo activo en esa vista. El JS anterior del detalle solo inicializaba tooltips. Se conservan datos completos de ítems seleccionados mediante details/summary. La leyenda y color ui_color mantienen la procedencia backend. No se añadió edición, conversión o impresión nueva al toolbar del detalle completo.

## Visual Reuse

**ALLOWLIST MATCH: YES** al finalizar, para quoteSale.index, quoteSale.index.facturadas, quoteSale.show, quoteSale.pendingBranches y quoteSale.pendingBranches.show. No estaban habilitadas al iniciar. Ajuste exclusivo de configuración de tema; no cambio de rutas de negocio.

Verificados HTTP 200 para venti.min.css, venti-next.css, js/layout/admin2.js y JS de lista. Reutiliza Bootstrap 4/AdminLTE, Select2, Datepicker, Toastr y modales existentes. No se modificó CSS ni el portal global. Adaptación de Escape limitada a los menús quote-actions: cierra el dropdown portaled y devuelve foco al trigger. Se cierra el portal antes de reemplazar filas.

Se corrigió el enlace de última página del renderer (usaba el índice posterior al bucle en vez de totalPages). No cambia la estrategia de paginación ni el contrato. Copy de confirmación ahora dice anular, coherente con destroy. Se eliminó el modal/callback ajeno de subcategorías sin consumidor en estas vistas. No se tocó la anulación de cotizaciones.

## Responsive QA

Navegador local http://127.0.0.1:8080. Medición de viewport y scrollWidth del documento; tablas anchas conservan desplazamiento local.

| Ancho | General + filtros expandidos | Facturadas | Detalle completo real | Detalle histórico |
|---|---|---|---|---|
| 320 | PASS | PASS | PASS | PASS |
| 375 | PASS | PASS | PASS | PASS |
| 390 | PASS | PASS | PASS | PASS |
| 576 | PASS | PASS | PASS | PASS |
| 768 | PASS | PASS | PASS | PASS |
| 992 | PASS | PASS | PASS | PASS |
| 1200 | PASS | PASS | PASS | PASS |
| 1440 | PASS | PASS | PASS | PASS |

PASS significa ausencia de overflow horizontal global en la muestra, contenido/tabla accesible en contenedor local. No significa todas las combinaciones de permisos/datos. Histórico listado también revisado en tamaños móviles/tablet/desktop. Toolbar y filtros usan el comportamiento compartido; resumen monetario permanece presente en móvil. Portal probado a 390: menú dentro del viewport y parent BODY. Escape cierra y restaura quote-actions-990000; fixture no persistido.

## Functional QA

### Sesiones autorizadas

| Cuenta / rol | tenant activo | company activa | branch activa | Resultado |
|---|---|---|---|---|
| Owner EDESCE, owner@venti360.com / Owner | 1 | 1, EDESCE CONSULTORA E.I.R.L. | 1, Local Principal | listado actual vacío; 65 históricos de esta empresa |
| Owner Gamarra, owner.gamarra@test.com / Owner | 3 | 3, Gamarra Moda | 4, Local Principal | general/facturadas/históricos vacíos; detalle EDESCE denegado |
| Owner EDESCE, segundo login | 1 | 1, EDESCE CONSULTORA E.I.R.L. | 1, Local Principal | vuelve al contexto EDESCE y permite consultar su histórico |

No se utilizó Platform Admin. Credenciales introducidas únicamente en login; no se guardan en este documento, código o fixtures.

- EDESCE: históricos 65, 15 por página, navegación a segunda página; búsqueda COT-00076 y apertura de detalle histórico 76. Detalle completo 76 responde y conserva descuento 8.47, gravada 49.15, IGV 8.85, total 58 del contrato actual.
- Gamarra: históricos 0; intento de detalle completo de cotización EDESCE 76 devuelve 404. No se interpreta un listado vacío como prueba exhaustiva de aislamiento.
- Conteo agregado readonly durante auditoría: tenant 1/company 1, 65 históricos NULL; tenant 1/company 2, 1 histórico NULL; no registros con branch asignado. Aislamiento company/branch inspeccionado en código; no hay muestra operativa entre sucursales para certificar todas las negativas reales.
- Listado: filtros se conservan y ejecutan GET; AJAX y assets responden. Se verifican respuesta vacía y representación de filas/estados/paginación mediante fixtures solo en memoria del navegador. No se incorporan fixtures al producto ni se insertan registros.
- Fixture de 10 páginas: enlaces 1,2,3,…,10 tras corrección. Menú portal y Escape comprobados. No se pulsaron acciones mutadoras del fixture. Se recargó la página después para retirar toda evidencia sintética del DOM.
- Consola sin errores/warnings en carga real final de facturadas. Errores deliberados de 404 durante prueba negativa y un error del harness al pasar un fixture con firma incorrecta no se clasifican como errores de aplicación; el harness fue corregido y retirado.
- No se crearon, editaron, anularon, cerraron, confirmaron, convirtieron o recotizaron documentos. El GET show ya registra un Audit de lectura en backend; por eso este QA es read-mostly, no una afirmación de cero escrituras técnicas. No se invocó PDF por sus efectos sobre archivos compartidos.
- Al primer intento se observó Unknown column quotes.branch_id. Una lectura posterior de schema y nuevas peticiones confirmaron la columna y funcionamiento. No se ejecutaron migraciones ni se atribuye su resolución a esta integración; registrar posible sincronización concurrente del entorno.

## Backend Sync Notes

### [OPEN] Q-01 — Catálogo de creadores fuera de contexto

Revalidado el 2026-10-26 con HEAD `2d9e365` y MERGE_HEAD `a8e01e1`: OPEN. General y facturadas entregan las mismas ocho opciones de usuario tanto a EDESCE como a Gamarra, incluyendo el owner del otro tenant. No se aplicó filtrado de seguridad en JavaScript.

Point: `User::select('id','name')->get()` entrega usuarios globales.
File / endpoint: QuoteSaleController@index / indexFacturadas (arrayUsers); vistas de ambos listados.
Impact: Gamarra recibe opciones de Owner EDESCE y otros contextos. Fuga de catálogo auxiliar aunque las cotizaciones estén acotadas.
What backend needs to define or correct: resolver creadores autorizados por tenant/company, incluida política para históricos; no aceptar filtrado de seguridad en JS.
Frontend can continue: Yes para fachada; No para declarar aislamiento completo o cierre funcional.

### [OPEN] Q-02 — Impresión y directorio PDF compartido

Point: printQuoteToCustomer borra archivos mediante glob(public/pdfs/*) + unlink antes de generar.
File / endpoint: QuoteSaleController@printQuoteToCustomer, GET imprimir/cotizacion/cliente/{quote}.
Impact: efectos globales sobre archivos fuera del alcance de lectura del padre.
What backend needs to define or correct: almacenamiento, limpieza, nombres y autorización de descarga por contexto.
Frontend can continue: Yes, conservar enlace existente sin ampliar consumidores; PDF no probado en esta fase.

### [OPEN] Q-03 — Actualización general y validación de relaciones

Point: updateDatosGeneral no declara permiso granular en ruta; la validación del padre no demuestra pertenencia de todos los customer/contact IDs. Revisar también IDs hijos de updateEquipment y requests de edición.
File / endpoint: POST quotes/update-general; update/equipment; StoreQuoteRequest/UpdateQuoteRequest.
Impact: no habilitar nuevos editores sobre la mera existencia de estos endpoints.
What backend needs to define or correct: permisos y validación de cada relación en el contexto autorizado.
Frontend can continue: Yes para lectura/listado; No para ampliar estas escrituras.

### [OPEN] Q-04 — Permisos de conversión y precios

Point: UI de comprobantes usa permisos de generación mientras la ruta exige edit_quoteSale; listado monetario no aplica la misma regla showPrices_quote que detalle.
File / endpoint: showRegistrarComprobante / storeFromQuote, listados y show.
Impact: diferencias de autorización entre pantallas y API.
What backend needs to define or correct: política única de generación, consulta de precios y endpoints auxiliares sin permiso granular.
Frontend can continue: Yes, preservando permisos actuales; operaciones no certificadas por este QA.

### [OPEN] Q-05 — Contrato histórico de moneda e impuesto

Point: show usa moneda de configuración y valor IGV del contexto actual para presentación/cálculo de V/U de servicios; no necesariamente snapshot histórico de la cotización. Hay consultas auxiliares legacy y supuestos de configuración en show.
File / endpoint: QuoteSaleController@show / SettingsService / show.blade.php.
Impact: cotizaciones históricas con otra configuración requieren reauditoría de representación.
What backend needs to define or correct: moneda y tasa históricas de origen, relaciones ausentes y eliminación de consultas auxiliares globales no consumidas.
Frontend can continue: Yes; se preservaron valores y fórmulas del contrato actual, sin inventar precios.

### [OPEN] Q-06 — Contratos de lista inconsistentes

Point: facturadas vacío devuelve startRecord=1/endRecord=0; validación de fechas de facturadas y general usa condiciones diferentes; ruta totales apunta a indexGeneral inexistente.
File / endpoint: getDataQuotesSalesIndex / getDataQuotesIndex / cotizaciones/venta/totales.
Impact: texto 1 a 0 para resultado vacío; filtros de fecha parcial requieren acuerdo; totales no utilizable.
What backend needs to define or correct: paginación vacía, fechas incompletas y destino válido de la ruta totales.
Frontend can continue: Yes para listas existentes; No para añadir enlace a totales. No se falsificó la metadata recibida.

### [OPEN] Q-07 — Cobertura pendiente de datos y roles

Point: no hay cotizaciones operativas branch-bound en los contextos QA; no se ejecutan escrituras por instrucción del usuario.
File / endpoint: conjunto de acciones y consultas locales.
Impact: no certificar transiciones, reservas, conversiones ni rechazos entre sucursales mediante fixtures visuales.
What backend needs to define or correct: proporcionar dataset QA autorizado y pruebas de servicio no destructivas; confirmar políticas de roles restringidos.
Frontend can continue: Yes; documentación explícita de límites.

Los pendientes de Material Edit, POS y Store Web SW-01…SW-07 no se modifican ni se cierran por esta fase.

## Revisit Triggers

Reauditar al corregir arrayUsers; cambiar permisos de consulta/conversión; incorporar cotizaciones branch-bound en ambas empresas; cerrar reglas de históricos/reservas; cambiar impuestos/moneda snapshot; aislar almacenamiento PDF; cambiar payloads/response de lista o semántica de estados. Ninguna de estas incertidumbres se convierte en un filtro o regla comercial de frontend.

## Files Changed

- config/venti-next.php: cinco rutas permitidas para tema.
- resources/views/quoteSale/general.blade.php y facturadas.blade.php: composición de página, hooks y permisos conservados.
- resources/views/quoteSale/_operationalList.blade.php: nuevo partial común con filtros, tabla, paginación y modal de decimales.
- resources/views/quoteSale/show.blade.php: detalle readonly estructurado.
- resources/views/quoteSale/pendingBranches.blade.php y pendingBranchDetail.blade.php: históricos con patrón compartido.
- public/js/quoteSale/general.js y facturadas.js: adaptación DOM, menú, Escape, respuestas en orden, texto seguro y paginación.
- docs/REAL-NEXT-QUOTES.md: auditoría, mapping, evidencia y pendientes.

CSS nuevo: **0**. Archivos backend comercial modificados: **0**. Sin commits ni cambios de rama durante esta entrega.

Validación técnica final: `node --check` en ambos JS; PHP 7.3.33 `-l` para config/venti-next.php; `artisan view:cache` completado; `git diff --check` sin errores (solo aviso de normalización CRLF). Diff de app/, routes/ y public/admin/dist/css/ vacío.

Evidencia visual local (fuera de archivos públicos/versionados): directorio `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/real-next-10/`. Capturas detail-desktop.jpg / detail-mobile.jpg corresponden al detalle real; list-fixture-* son únicamente evidencia sintética, no datos de negocio.

## Resolución semántica de merge — 2026-10-26

Merge en curso, sin abortar ni crear commit. Base: `df5c53f420932511dcbf4c3ca858e82205291f3c`; OURS/HEAD: `2d9e365`; THEIRS/MERGE_HEAD: `a8e01e1`. Se compararon los stages 1/2/3 de ambos JS y el delta backend desde la base. No se reemplazó ninguno de los archivos completos por una sola rama.

### Conflicto e integración

El único bloque textual conflictivo de cada JS estaba en la configuración del diálogo de `cancelQuote`: OURS había corregido el texto a «anular»; THEIRS cambió el flujo completo de anulación. La fusión automática ya incorporaba el resto del nuevo callback; conservar el bloque OURS habría perdido el bloqueo de cierre y la limpieza del flag de operación pendiente.

Se conserva de OURS toda la implementación fuera de `cancelQuote`: menús Venti Next con portal, Escape y restauración de foco; permisos/estados del renderer; texto seguro; filtros y hooks DOM; requests abortables y secuenciados; paginación corregida; separación de endpoints general/facturadas. Blade, allowlist y responsive de REAL NEXT 10 permanecen sin cambios en esta resolución.

Se conserva de THEIRS `cancelQuote` completo: validación del ID, descripción escapada, guard contra doble ejecución, botones deshabilitados durante petición, confirmación explícita, `quoteSaleSnapshot()` en POST, respuesta `ok/message`, errores mediante `quoteSaleError`, restauración del botón y recarga tras aceptar el resultado. La adaptación manual consiste en seleccionar ese bloque dentro del archivo integrado, no en recuperar el archivo legacy de THEIRS.

Verificación exacta, normalizando únicamente saltos de línea: para ambos archivos, resultado = versión stage 2 con su función cancelQuote sustituida por la de stage 3. No existen otras diferencias manuales en JS.

### Cambios backend recibidos y conservados

- `QuoteSaleBranchContext`: destroy ahora requiere validateForm y los snapshots de tenant/company/branch. Omitir el payload nuevo rompería el contrato.
- `QuoteSaleController::destroy`: consulta bloqueada por tenant/company/branch; venta activa acotada por tenant/company; respuesta ValidationException 422 con message/errors. Se retira destroyO obsoleto. Se conserva todo ello sin edición manual.
- `QuoteSaleController`: corrección de paymentDeadlines en edición; conversión a venta pasa a `WorkerUserLink::saleIdentity()` con created_by, created_by_name y worker opcional. No se ejecutó conversión.
- `WorkerCompanyContext` y `WorkerAccess`: contexto autorizado, snapshots para guardar, consultas company/tenant y ownership de trabajador/contrato. `WorkerController` aplica estas reglas y permisos adicionales.
- `WorkerUserLink`: opciones y validación de vínculo a usuarios activos autorizados para tenant/company, exclusión de vínculos duplicados y resolución transaccional de identidad de venta. `User::worker()` queda contextualizado; `Worker` incorpora BelongsToTenant. La migración recibida aporta identidad de venta/unicidad; no se ejecutaron migraciones en esta resolución.
- `routes/web.php`: se conservan los cambios de permisos/rutas de otros módulos y la retirada de la ruta legacy de destrucción de QuoteController. Las rutas QuoteSale consumidas por los dos JS conservan sus URLs, verbos y permisos. No se confundió `/destroy/quote/{quote}` legacy con `/destroy/quote/sale/{quote}` vigente.

**Creator filter Backend Sync: OPEN.** `QuoteSaleController@index` e `indexFacturadas` siguen ejecutando `User::select('id','name')->get()`. `User` no recibió un scope global de tenant y dichas consultas no usan WorkerUserLink. Las mejoras en selección de trabajadores no se trasladan automáticamente al catálogo de creadores.

### QA posterior al merge

Servidor local Laravel/PHP 7.3.33 en `http://127.0.0.1:8080`. Se inició para esta revisión porque el puerto no estaba escuchando. Cuentas autorizadas, sin registrar contraseñas:

| Cuenta / tipo | Tenant / company / branch activos | Resultado actual |
|---|---|---|
| Owner EDESCE / owner | 1 / 1 / 1, EDESCE CONSULTORA E.I.R.L., Local Principal | General 0, facturadas 0; ambos filtros incluyen Owner Gamarra (id 7) |
| Owner Gamarra / owner | 3 / 3 / 4, Gamarra Moda, Local Principal | General 0, facturadas 0, históricos 0; ambos filtros incluyen Owner EDESCE (id 4) |

Cambio de sesión/contexto confirmado en navbar y snapshot DOM. Se probaron filtros por creador (id 4 e id 7 respectivamente), búsqueda de descripción y respuesta vacía; no se alteraron las opciones recibidas. Facturadas conserva el pendiente «1 a 0 de 0» de metadata backend.

Rutas reales verificadas en código/route:list y navegador: `/dashboard/cotizaciones/venta`, `/dashboard/cotizaciones/venta/facturadas`, `/dashboard/cotizaciones/venta/pendientes-sucursal`, `/dashboard/cotizaciones/venta/pendientes-sucursal/76`, `/dashboard/ver/cotizacion/venta/76`. Los GET de datos siguen separados en `get/data/quotes/sale/index/v2/{numberPage}` y `get/data/quotes/sale/facturadas/index/v2/{numberPage}` con los mismos nueve parámetros de filtros.

EDESCE: 65 históricos, navegación real a página 2 (primera fila 60), búsqueda COT-00076 con un resultado, detalle histórico y detalle completo correctos; total registrado 58. Gamarra: intento de abrir el detalle completo 76 de EDESCE devuelve 404. Sin datos operativos locales para validar múltiples páginas AJAX reales; no se fabricaron registros para cubrir esa carencia.

Prueba de `cancelQuote` en VM de Node con jQuery/AJAX simulados, sin red: snapshot y POST correctos, guard de doble ejecución, escape de descripción, éxito con recarga tras aceptar, manejo 422 y restauración del botón pasan en ambos JS. Esta prueba no ejecuta ni certifica la transacción comercial de anulación.

Responsive del listado general con filtros expandidos revalidado a 320/375/390/576/768/992/1200/1440: sin overflow horizontal global. CSS y Blades no modificados por la resolución. Carga real EDESCE sin errores/warnings de consola; 404 deliberado en prueba negativa Gamarra. Se conserva QA read-mostly: ninguna cotización creada, editada, anulada o convertida; show puede registrar su auditoría de lectura existente.

Validación: sintaxis Node en ambos JS y `git diff --check`. Stage final limitado a los dos JS resueltos y este documento; los archivos ya fusionados/staged de backend se preservan. Merge pendiente de revisión y commit del usuario.

Evidencia adicional: `real-next-10/merge-gamarra-creator.jpg`, en el directorio local de capturas indicado arriba.
