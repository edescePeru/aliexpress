# R8.0 / R8.1 — Global Semantic Functional Parity Audit

Fecha: 2026-11-25. Shared baseline **6df57e7**. Audited HEAD **6df57e7d73a2546bc283527b166041584bfc708f**. Rama feature/venti-next-frontend. PHP 7.3.33; entorno127.0.0.1:8080 intacto.

## Estado vigente — continuación optimizada R8 (2026-11-25)

Esta sección sustituye la interpretación de alcance del checkpoint anterior, sin borrar su evidencia. **379 es el inventario provisional, no 379 auditorías obligatorias.** Las 311 filas antiguamente sin cierre no son una cola de homologación ni de QA integral.

| Universo | Superficies | Tratamiento |
|---|---:|---|
| A — NEXT FUNCTIONAL PARITY | 194 | 192 allowlisted activas + Entry Edit (navegación FR047) + editImages (lector compartido modificado por Next) |
| B — ACTIVE HOMOLOGATION PENDING | 2 | Recepción de orden y Registrar comprobante; vigencia/acceso/contrato identificados, sin pérdida Next nueva |
| C — HISTORICAL / SCOPE UNCONFIRMED | 125 | Evidencia de vigencia pendiente agrupada por familias; no auditar exhaustivamente backend |
| EXCLUDED / PAUSED / RETIRED | 58 | Decisiones existentes; independientes de las 23 capacidades FR excluidas |

**Universo A: 80 auditorías semánticas cerradas; 114 pendientes.** Cierre de auditoría significa diferencias explicadas, NO que todas las funciones estén recuperadas: Entry List conserva FR047/048 y StockItems conserva FR059 bloqueados. 39 cierres reutilizan evidencia previa vigente; 41 usan comparación nueva (incluidos 8 del checkpoint anterior). Incremento de esta continuación: **70 cierres A**; no 70 funciones recuperadas. A08 queda revisado fuera de A y no infla esos contadores.

No se asignan FR nuevos: **74 recuperadas +10 bloqueadas +23 excluidas =107**. Pérdidas nuevas confirmadas/parciales, gates obsoletos activos nuevos y findings DEV nuevos:0 en lo revisado. No extrapolar a los114 residuales. Implementación R8, mutaciones comerciales e infraestructura:0. No navegador nuevo ni repetición de QA responsive.

La pertenencia A no se deriva solamente de allowlist: se cruzó historial `front:` de vistas para localizar superficies externas intervenidas (editImages) y navegación compartida retirada (Entry Edit). C continúa provisional; una evidencia nueva de intervención o navegación Next la mueve a A, no se descarta por falta de allowlist.

## Historia — estado del checkpoint inicial

**R8.0 PARTIAL / R8.1 PARTIAL. No es cierre global.** Se reconciliaron candidatos obligatorios y un subconjunto adicional; el censo provisional y la cola completa están en REAL-NEXT-R8-SEMANTIC-SURFACE-INVENTORY.md. No se recuperaron funciones ni se modificó implementación. No se declara PARITY CONFIRMED por una búsqueda sin coincidencias.

Working tree inicial: cambios R7.7E en resources/views/cashBox/index.blade.php, resources/views/cashBoxSubtype/index.blade.php, plan post-DEV y nuevo informe Cash. Se preservan; no son cambios R8. HEAD no cambió. No fetch/pull/merge/rebase. No se tocó multitenancy.

### Conciliación de unidades

- 197 era el número de rutas GET allowlisted del inventario original, no un censo de todas las vistas activas. Ese informe registró39 revisadas/158 pendientes y el ledger posterior40/157, incluyendo resoluciones por producto; no reutilizar157 como la cola actual.
- HEAD registra896 rutas GET. Configuración Next:198 nombres y5 URI. Se extrajeron métodos sin despachar requests y se reconstruyeron vistas, alias y dispatchers.
- Censo provisional:379 superficies/variantes, incluidas páginas históricas fuera de allowlist y retiros.58 clasificadas LEGACY/PAUSED/RETIRED;321 activas o por determinar. No afirmar que todas321 son vigentes:119 requieren evidencia adicional.
- 32 superficies reutilizan evidencia R1–R7.7E sobre capacidades concretas;30 se mantienen PARTIAL conservadoramente,2 Cash tienen inventario completo de la fase anterior. La homologación por sí sola no cierra una fila.
- 16 superficies tienen revisión focalizada R8;8 con cierre estático del alcance de superficie, otras8 sólo residual. Sumadas las2 Cash previas:10 COMPLETE. **311 filas no tienen cierre semántico integral**, incluyendo8 bloqueadas,2 pendientes de homologación,36 parciales,146 requeridas y119 de evidencia insuficiente.
- Cobertura integral conservadora:10/321 (3,12%) del universo provisional no excluido. No equivale a cobertura de operaciones ya recuperadas: los74 FR permanecen recuperados.
- 20 referencias auth/público/POS/Store Web fuera del denominador y sin certificación. Modales y endpoints auxiliares no se contabilizan como páginas. Las variantes Owner/operador y Admin/resumen se separan porque su contrato es distinto.

R8.0 sigue parcial por vigencia pendiente de superficies externas, despachos dinámicos/delegados y reconciliación de retiros no allowlisted. El número379 no es definitivo. Los IDs R8-S se conservan al continuar; no renumerarlos.

## Matriz de diferencias y decisiones focalizadas

No hay nuevos FR asignados: **0 pérdidas adicionales confirmadas en el alcance revisado**. Esto no significa que las311 filas pendientes estén libres de pérdidas. R8-A/B/C/E son referencias de auditoría, no capacidades nuevas ni findings DEV.

| FR ID / referencia | Superficie | Capacidad | Evidencia histórica / origen Git | Estado actual / vigencia | Contrato DEV | Clasificación | Prioridad / acción |
|---|---|---|---|---|---|---|---|
| Sin nuevo FR; R8-A01 | QuoteSale Edit79 | Guardar datos generales | a614191 añade botón;83dbe6c lo comenta y añade sus campos al POST unificado; bf14723 conserva ese estado | Guardar cambios en la cotización sigue conectado y envía datos generales | updateEquipmentOfQuote actual actualiza encabezado y equipo | INTENTIONAL DEV CHANGE / PARITY CONFIRMED para guardado general | Sin recuperación; no duplicar botón |
| FR-050 existente; R8-A02 | Recepción de orden2 | Recibir productos | df5c53f sustituye formulario por recepción contextual; R4 restauró navegación FR050 | Trigger de list-operations + formulario y Guardar visibles; fuera de allowlist | Summary scoped, warehouse context, receipt_key y POST actual | PARITY CONFIRMED en acceso; ACTIVE — VISUAL HOMOLOGATION PENDING | P3: homologar sin nueva recuperación FR |
| Sin nuevo FR; R8-A03 | Reporte órdenes por material | Exportar | gate hasanyrole existe en d9c21ec; bf14723 lo cambia a can(report_orderPurchaseExpress) | Ya corregido; URL/handler disponibles | Reporte/JSON/export comparten permiso y contexto actual | OBSOLETE FRONTEND GATE histórico YA CORREGIDO, no pérdida causada por homologación | No modificar107; registrar reconciliación |
| FR063/064/102/103/105/106/107; R8-A04 | Dashboard operativo | Siete pendientes R7 | dbca24e1 y revisión R7 | Continúan bloqueados por causas conocidas; no GET escritores | INV-01/METRIC-01/DASH-R7-01 | BACKEND BLOCKER conocido | Prioridades canónicas preservadas |
| FR104 existente; R8-A04 | Dashboard Owner | Export entradas / contexto / navegación |41ed99f/0129133 cambian despacho Owner intencionalmente; R7.7D preserva partial/export | Owner no debe recibir dashboard operacional aunque seleccione company | HomeController::dashboard/OwnerWorkspace | INTENTIONAL DEV CHANGE; no nuevo FR | Conservar; cupos no se cambian |
| Sin nuevo FR; R8-A05 | QuoteSale Show | Edición aparente por templates |2d9e365^ cargaba sólo tooltips; edit.js comentado | Detalle semántico conserva consulta; no flujo editor previo activo | show y read scope histórico/local | HISTORICALLY UNREACHABLE en baseline de homologación | No reactivar templates; revisión residual no recertifica show entero |
| FR097 existente; R8-A06 | Entry Show | Agregar/editar líneas | R4-H01 y483a785^: botones en templates/modales sin trigger externo | Detalle + adjunto autorizado R7.6.1 | list_entryPurchase; edición separada | HISTORICALLY UNREACHABLE para esos mutadores | No duplicar FR097 ni agregar editor al detalle |
| FR098 existente; R8-A07 | Invoice Show | Guardar/editar líneas |fa8f7ed^ tenía btn-submit y edit_invoice.js comentados; R4-H02 | Detalle + adjunto autorizado | list_invoice; edición separada | HISTORICALLY UNREACHABLE en baseline | No duplicar FR098 |
| Sin FR; R8-A08 | Registrar comprobante | Seleccionar cotización / guardar | Vista no homologada; último cambio d5cf431, no eliminación Next demostrada | btn-submit inicia disabled y se habilita al cargar cotización | QuoteFormReadAccess::conversion, edit_quoteSale, contexto local | Restricción legítima de disponibilidad; homologación pendiente | No contar disabled inicial como pérdida; comparar resto del formulario |
| Sin FR; R8-B01 | Category/Subcategory/Subtype Create/Edit (6) | Guardar/Reset/padres/filas |8faf1ef^→8faf1ef→HEAD, diffs Blade+JS | Formulario conserva control externo asociado mediante form=; subcategorías usa click explícito | Rutas POST/CSRF/names/requests actuales preservados | PARITY CONFIRMED estática | Sin recuperación ni pruebas mutadoras |
| Sin FR; R8-C01 | Financial Summary Movements | Operaciones de caja retiradas del resumen |summaryIndex establece readOnly=true; contrato separado de Admin | Template summary_movements es consulta por caja/sesiones; Admin usa admin_readonly con operaciones R2 | showFinancialSummary_cashBox; no reutilizar permiso administrativo | INTENTIONAL DEV CHANGE / read-only contractual | No confundir con gate R2 ya recuperado |
| Sin FR; R8-E01 | Relationship deleted | Papelera | Ruta/controller desde d9c21ec; no archivo relationship/indexDeleted.blade.php en historia Git localizada | Vista referenciada ausente; navegación/uso histórico operativo no demostrados | indexDeleted retorna vista ausente | EVIDENCE REQUIRED, no pérdida de homologación demostrada | Investigar antes de abrir finding DEV o proponer UI |

## R8-A01 — Guardado de cotización: sustitución demostrada

URL: http://127.0.0.1:8080/dashboard/editar/cotizacion/venta/79 ; quoteSale.edit ; edit_quoteSale ; QuoteSaleController / quoteSale.edit / js/quoteSale/edit.js.

83dbe6cd2a8bbb53bff227a653050f91ea3c07b2, autor Git arcadio1894,2026-01-23, «Cambiar los botones»: comenta btn-guardar_datos_generales, cambia texto de btn-saveProducts a Guardar cambios en la cotización y añade quote_id, descriptionQuote, codeQuote, fechas, way_to_pay, delivery_time, customer/contact, payment_deadline y observations al POST de equipo. Es anterior a la homologación bf14723 del25/11.

HEAD: edit.blade.php:94 conserva comentario; botón unificado:792. edit.js:2523 conecta click a saveEquipmentEdit;2925 POST /dashboard/update/equipment/{equipment}/quote/sale/{quote}, con contexto T/C/B y CSRF. QuoteSaleController::updateEquipmentOfQuote:6386 actualiza explícitamente los datos generales. La ruta update-general y su handler permanecen, pero no son el único camino de guardado. No ejecutar ni reactivar ese handler huérfano.

Conclusión: **guardar datos generales no está perdido**. El botón separado tuvo existencia operativa antigua, pero fue reemplazado intencionalmente antes de Next; no clasificarlo como «nunca existió». El residual se resuelve por comparación semántica, no por contar botones.

## R8-A02 — Recepción de orden

URL válida revalidada: **http://127.0.0.1:8080/dashboard/crear/entrada/compra/orden/2**. Route order.purchase.create; EntryController::createEntryOrder:3945; entry.create_entry_purchase_order; public/js/entry/normalPurchaseReceipt.js. Middleware create_entryPurchase más auth/contexto del grupo.

Alcanzable desde orden para almacén por list-operations.js:28 cuando can_receive=true y create_entryPurchase. No requiere crear una orden nueva. GET obtiene OrderPurchase normal mediante ScopedPurchaseCompany, summary scoped y almacenes del contexto; genera UUID en memoria. No escribe orden/detalles/stock/estado. Summary recalcula disponibilidad de recepción; las prohibiciones por orden completa, inconsistencias históricas o almacén no seleccionable son DEV, no gates de QA.

Formulario: purchase_order_id, receipt_key, entry_context_tenant/company/branch, date_invoice d/m/Y, invoice, referral_guide, image/imageOb, observation, deferred_invoice; por línea entered, order_purchase_detail_id, warehouse_id, id_location, lote y vencimiento. JS lee cantidades positivas y destinos, construye FormData+items JSON, POST entry.purchase.order.store, mantiene misma clave durante reintento y reabre botón tras fallo. Backend sigue autoridad de idempotencia y stock; no recertificados aquí.

Lectura actual autorizada Owner EDESCE T1/C1/B1, con permiso confirmado y transacción MySQL READ ONLY: orden2 OC-00002 y3 OC-00003 pueden recibirse; orden4 completa. Almacenes seleccionables8 local y1 general; default8; sin errores de configuración. Render de2 tiene2 líneas, receipt_key y Guardar visibles. Chrome headless aislado1440:0 pageerrors,0 POST; no carga venti-next.css porque no está allowlisted. Se inspeccionó captura r8-receipt-1440.png: cards/encabezados legacy; **homologación pendiente, no pérdida funcional nueva**. Assets externos/auxiliares interceptados: no certificar por esta captura fuentes remotas ni shell completo.

## R8-A03 — Reconciliación de Exportar por Material

http://127.0.0.1:8080/dashboard/reporte/de/ordenes/compra/por/materiales ; report.orders.by.materials ; OrderPurchaseController::reportOrderPurchaseByMaterial/getReportOrderPurchaseByMaterial/exportReportOrdersByMaterial. Controller:52 aplica permission:report_orderPurchaseExpress a los tres. Backend conserva contexto tenant/company; no se reabre Express.

El gate @hasanyrole(admin|principal|cordinator_operations) estaba en el primer commit d9c21ec. El export era alcanzable por esos roles; el problema fue una restricción heredada frente a permisos efectivos, no eliminación introducida por nuestra homologación. bf14723 lo reemplaza por @can. JS btn-export y URL exportar/reporte/ordenes/by/material/v2 permanecen. R7.7C ya interceptó parámetros start/end/material/year; no se ejecutó export aquí.

No coincide con FR028 (export del listado General excluido) ni FR057/058 (exports de Invoice) ni FR063/064/103 (inventario valorizado). Es un gate histórico corregido fuera del universo de pérdidas causadas por Next. **No asignar FR108 ni aumentar recuperadas sin redefinir expresamente el universo.**

## R8-B01 — Seis formularios de catálogos

Create/Edit Category, Subcategory y Subtype revisados contra su baseline individual8faf1ef^, homologación8faf1ef y HEAD. No se generaliza el resultado a todos los catálogos.

| Familia | Contrato conservado | Alcanzabilidad actual |
|---|---|---|
| Category | name/description; category_id en Edit; category.store/update; FormData+CSRF | Submit externo formCreate/formEdit dispara handler del formulario; reset y breadcrumb al listado disponibles; JS propio sin diff funcional |
| Subcategory | category_id; subcategories[n][name/description] en Create; subcategory_id/name/description en Edit | btn-submit conserva click storeSubCategory; add-subcategory/remove-subcategory conservan filas dinámicas; Edit submit; Select2 mantiene padre y reset sincronizado |
| Subtype | material_type_id, name, description; subtype_id en Edit | Submit conectado a storeSubtype/updateSubType; renombre de función acompaña registro de evento; POST no cambia |

Los names, hidden IDs, data-url y CSRF no se sustituyen por texto estático. Botones movidos a toolbar conservan form= válido. Cambios JS de Select2, reset, labels y handler(event) no eliminan capacidad. Required/maxlength cotejados con requests; no declaración de QA mutadora ni cierre global QA-02. Artefacto r8-catalog-forms.json es apoyo estructural; la conclusión también usa handlers, payload, rutas y diff, no igualdad de conteos.

## Búsqueda transversal y falsos positivos

Inventario y búsqueda abarcan Blade y JS de aplicación; app.js compilado/minificados excluidos de conteos de patrones. Resultados detallados por archivo en r8-scan.json. Patrones sólo abren candidatos. Se inspeccionaron especialmente read-only, readonly, disabled, QA PENDING, BACKEND SYNC, comentarios de acciones y gates por rol.

- Cash summary readOnly no es Admin: el dispatcher separa ambas superficies. No eliminar ese guard por analogía con R2.
- registrarComprobante disabled inicial exige seleccionar cotización; al cargarla habilita Guardar. Campos monetarios readonly/descuentos reflejan cotización cargada; no inferir pérdida por atributo.
- Entry/Invoice/Quote Show contenían residuos de templates/JS sin flujo editor previo. Se reutiliza R4-H01/H02 y se contrasta Git; no abrir FR duplicados.
- Scripts list-readonly/finance-readonly siguen coexistiendo con adaptadores operativos recuperados. El nombre del archivo no prueba retiro de acciones.
- Comentario inicial de index_entry_purchase_report.js sobre documentos fuera de alcance no refleja por sí solo el runtime recuperado: lectores y adaptadores R4/R7.6.1 son autoridad de alcanzabilidad.

## Evidencia, seguridad y límites

Fuentes consultadas: AGENTS/patrones; auditoría global original; backlog FR y DEV; clasificación UNKNOWN; plan DEV paralelo; entrega/reconciliación25/11; plan/R76; informes R4/R7/R7.7A/C/D/E; docs/flujos de cotizaciones/pausas, Q, CASH, dashboards y cierre J/P/K. No se volvió a certificar backend ni se ejecutaron pruebas financieras.

Artefactos locales: C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/ — r8-routes.json, r8-ledger.json, r8-scan.json, r8-read.php/json, r8-receipt.html/png, r8-receipt-browser.cjs, r8-catalog-forms.json. Contienen lectura real y extracción estática; ningún fixture persistido. Se usó PHP7.3.33 y Chrome aislado, no escritorio. No documentos, exportaciones, proveedores, migraciones, permisos ni registros modificados.

Backlog:74 recuperadas +10 bloqueadas +23 excluidas =107, sin IDs nuevos. Los10 FR bloqueados conservan identidad; ENTRY-R75-01, ENTRY-R4-01, INV-01/METRIC-01 residual, DASH-R7-01 y FIN-03 no se duplican. QA-02 y DOC-01 conservan límites; J/P/K no reabiertos. No nuevos findings backend confirmados.

Cambios R8: sólo documentación. Backend/Blade/JS/CSS/rutas/allowlist/infraestructura:0. Mutaciones comerciales:0. No commit/push/merge. Git diff check: PASS al checkpoint.

## Continuación exacta

Reanudar por R8-A08 Registrar comprobante: inventario completo de tres variantes, baseline d5cf431 y trazabilidad de handlers/permisos; no emitir. Continuar residuales del bloque A no cubiertos por R1/R4/R7.7. Después B restantes (B01 sólo seis formularios), C, D y E. Registrar por fila lo cerrado; conservar PARTIAL ante incertidumbre. El censo379 y los conteos de pendientes deben recalcularse al confirmar aliases/vigencia sin renumerar FR.

R8.2 **NO-GO para recuperación global todavía**: no hay pérdida nueva demostrada lista para recuperar y la auditoría no está completa. Homologación de recepción es candidata independiente P3, no autorización de implementación. Esperar revisión del propietario; no iniciar recuperaciones.

## Continuación optimizada — evidencia por bloque

### A08 / S281 — Registrar comprobante: Universo B

Comparación d5cf431→HEAD: Blade sin diferencias. JS sólo cambia en df5c53f: subtipos filtrados por caja/general, limpieza y disabled del subtipo no aplicable; success muestra message/warnings y URL de impresión contractual. Son adaptaciones DEV, no retiro Next. Las tres variantes siguen enlazadas desde quoteSale.general: Boleta bajo generarBoleta_quoteSale, Factura bajo generarFactura_quoteSale, Ticket conserva enlace; route show.register.comprobante y guard edit_quoteSale/conversion vigentes.

| Variante | Campos / condición conservada | Serialización vigente |
|---|---|---|
| Ticket | Sin DNI/RUC fiscal obligatorio; parcial sólo si negocio lo permite | type_document null; pago parcial s/n; caja/subtipo null sólo en parcial |
| Boleta | DNI8, nombre/email; switch parcial deshabilitado por tipo como baseline | type_document03, tipo_documento_cliente1 |
| Factura | RUC11, razón/dirección/email; switch parcial deshabilitado por tipo | type_document01, tipo_documento_cliente6 |

Búsqueda por código/nombre→quotes/buscar; selección→get/data/quotes/sale/{id}; carga equipos, consumibles/mano de obra, descuentos y totales; habilita btn-submit al cargar. Consultar cliente sigue su handler. Confirmación→POST store/sale/from/quote con quote_id, fecha, cliente, caja/subtipo, parciales, detalles; CSRF meta-header. Form data-url antiguo no gobierna este POST: manda el handler btn-submit, no inventar cambio de endpoint. Ninguna emisión/consulta de proveedor ejecutada. Las restricciones iniciales readonly/disabled son anteriores y contractuales, no gates QA. Contratos backend no recertificados.

URLs: http://127.0.0.1:8080/dashboard/registrar/comprobante/venta/Ticket ; http://127.0.0.1:8080/dashboard/registrar/comprobante/venta/Boleta ; http://127.0.0.1:8080/dashboard/registrar/comprobante/venta/Factura . Una superficie, tres variantes. Homologación pendiente, sin pérdida nueva.

### A — Reutilización R7.7A/B/C y R4

Se revisaron informes, matrices de campos/acciones/handlers/payloads y diffs posteriores, no sólo conteos de controles. 66f4c60→HEAD: Normal Create/Edit, Material Edit y JS sin cambios; Entry sólo grilla/densidad y Perfil sólo tabs, ya cubiertos por owner-final-results y comparación de atributos. bf14723→HEAD: Quote Create/Edit sin diff; A01 resuelve el único residual de guardado general. Evidencia previa suficiente y vigente: no repetir renders/intercepciones.

R4 inventarió63 capacidades, incluyendo23 grupos ya disponibles,32 pérdidas y exclusiones/residuos históricos. R7.6/R7.6.1 resuelven navegación Create y documentos. Se contrastó a0ca809→HEAD: adapter añade lectores autorizados, enlace Create Entry, partial documental en detalles; scripts legacy sólo retiran regularización financiera explícitamente retirada por negocio. No otras pérdidas observadas. Invoice Create/Edit permanecen iguales al alcance auditado. Cerrar auditoría de esas11 superficies NO cierra FR047/048 ni la protección HTTP de despliegue.

Ventas S251/252: evidencia R1 sigue vigente (sin diff Blade/adapter desde3560b17), pero R1-I01/I02 no se transforman en cierre total. Cotizaciones listados/históricos y GRE conservan residual histórico específico pendiente; no se repite su QA funcional ahora.

### A09 / S096 — documentos extras, alcance compartido A

Historial Blade: d9c21ec y3a79b99; JS sin cambio Next. El único diff de la página sustituye seis asset públicos por entry.document.extra con filename, en tres grupos de documentos. Agregar imagen Factura/Guía/Observación, templates, editar código anterior, eliminar anterior, quitar fila local, preview y Guardar multipart conservan triggers y handlers de editImages.js. Navegación FR049 recuperada; no se duplica. Auditoría de paridad cerrada; aspecto legacy restante no fue homologado. No upload/delete ni apertura documental; no certificación de mutaciones legacy por esta comparación.

### B — Catálogos e inventario

R5 sigue vigente: comparación a0ca809→HEAD sin diff en los11 índices/JS (r8-reused-evidence-current-files.json). Matriz R5 explica bulk, selección acumulada Brand/Exampler, CRUD individual, filtros/paginación y permisos; cierre de paridad de índices reutilizado, no QA destructivo.

R6 sigue vigente sin diff en StockItems/Items: min/max, flags, código físico y lectura preservados. FR059 se mantiene BLOCKED; cierre de auditoría no equivale a recuperación del ajuste. No ejecutar sync, stock ni export.

Se amplía B01 a16 formularios restantes de ocho familias, comparando8faf1ef^→8faf1ef→HEAD. Artefacto r8-catalog-residual-diffs.json. Botones movidos a toolbar usan form= del formulario; Submit conserva FormData/CSRF/ID y padre. Brand/Exampler/Genero/TypeScrap renombraron funciones junto a su registro submit y mejoraron feedback; no cambio de URL/payload. MaterialType agrega reset Select2 y corrige description que antes mostraba name; Category/Subcategory/Subtype ya auditados. Color conserva picker/HEX/preview, Talla short_name y serialización; Unidades JS sin cambio. Nuevos required/maxlength cotejados con requests, sin campo legítimo retirado. Variantes de material, transferencias, availability y catálogos físicos restantes siguen pendientes concretos, no se infiere su cierre.

### D — Admin / estructura, residual histórico resuelto

R7.7D y8d4d25e→HEAD sin cambios mantienen Owner/Platform/Planes cerrables por evidencia previa: contexto/cupos/9 accesos/export FR104, modales y payloads. Para nueve páginas Company/Branch/ConfigUserWeb se completó comparación histórica a9679aa4/399f7d9c/b22eb015→HEAD. PriceList no se perdió: se extrajo a tenantBranch._pricing manteniendo branch_price_list_id/price_list_id y opción histórica no disponible. Slugs DEV, old(), CSRF, company_id y submits permanecen; defaults PRINCIPAL pasan a old(default). Sin JS funcional nuevo en Company/Branch; handlers submit/status mantienen data-url y confirmaciones.

ConfigUserWeb: diff concentra botones en dropdown; editar/reset/status conservan rutas/handlers. Gates data-can-* coinciden con middleware editUser/resetPasswordUser/changeStatusUser_configUserWeb (routes3786–3817), no QA gates. Exclusiones self/Owner de reset/status ya estaban antes. Búsqueda, estado, perPage, carga/paginación y plan permanecen. Create/Edit no cambian JS. Cambios DEV posteriores de cupos y garantías siguen autoridad; R7.7D cubre su render. No se ejecutó ninguna mutación.

Observación visual estática separada: tenantBranch.index aún muestra Editar/Activar como botones individuales; no es pérdida funcional ni autorización para rediseñar. Mantener como revisión visual posterior, junto a receipt legacy; no afirmar nuevo QA visual responsive.

### C y E — próximo residual concreto

Resumen financiero read-only conserva su decisión intencional R8-C01. Admin Cash/My movements/Session requieren aprovechar R2 y sus contratos previos antes de nuevas pruebas. Reportes y auxiliares allowlisted requieren comparar filtros/export y gates previos por familia. Universo C histórico se agrupa por módulo, no consume la cola principal como125 auditorías completas.

## Estado vigente y siguiente checkpoint

R8.0: PARTIAL — alcance A/B/C definido, vigencia C provisional. R8.1: PARTIAL —80/194 de A con auditoría cerrada,114 pendientes. R8.2: NO INICIAR; revisión del propietario requerida. Las70 nuevas filas cerradas no son70 recuperaciones. 39 cierres A reutilizados,41 por nueva comparación acumulada. A08 completado fuera de A. Sin pérdidas nuevas confirmadas, sin findings DEV duplicados;107 FR intactos.

El segundo avance siguiente resuelve Sales, QuoteSale y los índices Normal. Continuar por **S201/203 Normal detail y S305–307 GRE**, reutilizando sus informes. Después C Cash y restantes A por familias. No volver a los80 cierres sin cambio posterior. El ledger tiene alcance y residual por fila. No ejecutar Chrome salvo duda de alcanzabilidad irresoluble por evidencia estática.

## Segundo avance optimizado — A10/A11 y C02

### A10 — Sales residual R1-I01/I02

git grep sobre ffe0974^ encuentra data-facturador únicamente en handlers y list-access, nunca en markup generado. modalFacturador sólo se abre desde ese trigger ausente. log -S rastrea handlers a a1f2b96/458b72c/640fb46/ec5f6dd, sin trigger operativo en baseline. No reactivar update-invoice-data por tener endpoint.

Reintentar comprobante aparece en640fb46 como botón data-reintentar_comprobante y renderer can_retry_invoice; no registra evento, href ni invocación. b52c3cd lo retira. Diferenciar botón visible inerte de capacidad funcional ejecutable: pérdida operacional nueva no demostrada, no conectar a generate-invoice ni a recuperar archivos por analogía. R1-I01/I02 se resuelven para paridad del baseline como código históricamente no conectado; si Producto quiere activarlos sería definición de capacidad, no recuperación confirmada. No se declara inexistencia universal en todas las ramas remotas no consultadas.

R1/R1.1 cubren operaciones disponibles, iconos, payloads, permisos/condiciones y lectura; 3560b17→HEAD sin diff en Blade/adapter activos. S251/252 audit CLOSED; documentos/proveedor/mutaciones mantienen sus límites previos.

### A11 — Cotizaciones general/facturadas/históricos/show

Evidencia REAL-NEXT-QUOTES enumera filtros, paginación, estados, actions/print, decimales, recotizar, conversion links y show. Comparación posterior2d9e365→HEAD: JS general/facturadas únicamente adapta cancelQuote en merge20cea30; mantiene POST destroy/quote/sale/{id}, agrega quoteSaleSnapshot y manejo ok/error/dobleenvío. No elimina operación. Históricos sólo cambian wrappers de espaciado/paginación; comparación con2d9e365^ conserva formulario GET, q, links de detalle y retorno. Show sin cambio posterior y A05 ya demuestra templates editores no alcanzables. Cinco cierres de paridad estática, no nueva certificación de anulación ni impresión.

### A12 — Normal listado/papelera

R3.1 documenta antes/después de acciones, búsqueda/fechas/estado/paginación/DataTables y modal contextual. Cambios posteriores exactos del adapter: añade print eliminada, Edit sólo Normal activo con recoverNormalEdit=true y Create según permiso. R7.6 aporta wiring actual y R7.7A cubre destinos. No cierre nuevo de Express/General. S188/198 audit CLOSED; S201/203 siguen comparación residual de datos del detalle. No ejecutar PDFs/restore/state.

### C02 — Cash Admin

R2 matriz + contratos + cuatro POST simulados previos explican apertura, sesión, regularización y documentos junto a filtros/paginación. Archivos runtime sin diff desdea0ca809. S033 audit CLOSED reutilizando evidencia, sin repetir transacciones ni certificar finanzas. C01 resumen read-only sigue separado. My/Session/Summary continúan pendientes, no se hereda este cierre.

**Estado actualizado: A80/194 cerrado;114 pendientes;39 por evidencia previa,41 por nueva comparación acumulada.** B2/C125/excluidas58. Sin nuevas pérdidas/FR/findings. Checkpoint: S201/203 y S305–307; después S035/039/040 y familias restantes A. R8.0/R8.1 PARTIAL; R8.2 no iniciado.

### Checkpoint de continuidad — comparación iniciada, no contabilizada como cierre

- S201/203: separar los campos históricos derivados (precio sin IGV/18%) del contrato de snapshots actual. Los handlers Express sin trigger no prueban una capacidad perdida en el detalle Normal. Falta concluir la representación de datos con la evidencia canónica; mantener PENDING.
- S305–307: Create no presenta diff desde9c26f05a; no cerrar por esa sola igualdad. List/Show contienen cambios DEV de identidad conflictiva, lectores y diagnóstico. Antes del cierre, interpretar `frmGreRecovery` sin formulario actual frente a la decisión DEV de no instalar el paquete anterior de recuperación. No reabrir J/P ni llamar al proveedor.
- S035/039/040: My/Session incorporan `deferred_cancelled` y origen de apertura; comparar esas diferencias con el contrato DEV y la matriz previa. Summary conserva C01 read-only. No heredar el cierre de Cash Admin.

Estas observaciones de trabajo no son nuevos findings ni pérdidas confirmadas. No alteran80 cierres /114 pendientes. Continuación exacta: terminar S201/203, luego S305–307, S035/039/040 y restantes familias A; después resolver vigencia C por familias.
