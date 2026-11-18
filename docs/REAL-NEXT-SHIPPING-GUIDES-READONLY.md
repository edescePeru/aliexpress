# REAL NEXT — Guías de Remisión — Read-only Frontend Integration

Fecha: 2026-11-14. Roadmap E. Alcance: consulta, sin emisión, creación, edición, envío, consulta externa, exportación, anulación, reintentos ni documentos. Backend sin cambios.

## Domain

Dos familias independientes:

- **ShippingGuide moderno / GRE remitente**: ShippingGuideController, ShippingGuide, ShippingGuideItem, ShippingGuideVehicle, ShippingGuideDriver; vistas shipping_guides/index, show, create; JS shipping_guides/index.js y create.js. El listado y detalle sólo admiten tipo_de_comprobante=7. No existe un mantenedor separado de guía transportista integrado en esta fase; create rechaza type distinto de remitente. No equiparar la información del transportista con otro tipo de guía.
- **ReferralGuide legacy**: ReferralGuideController, ReferralGuideDetailController, ReferralGuide/ReferralGuideDetail, vistas referralGuide/*. Index/getDataGuides usan consultas globales; show usa find por ID sin frontera contextual. Incluye clientes/proveedores/responsables y datos operativos: BACKEND SYNC REQUIRED, no integrado ni navegado. Su estado enabled_status (activa/anulada) no se mezcla con status GRE.

NubefactGreService construye/envía/consulta al proveedor; NubefactCompanyConfigService resuelve configuración por empresa para operaciones externas. No se ejecutaron esos servicios externos. Los controllers de items/vehicles/drivers no constituyen superficies independientes de consulta seleccionadas para este batch.

## Surface Matrix

Todas las URLs indicadas abajo usan prefijo `/dashboard`.

| Surface | Route | Action | Permission | Scope | Read/Mutation | Decision |
|---|---|---|---|---|---|---|
| Listado GRE | shipping_guides.view, GET /shipping-guides/listado | viewIndex | Grupo autenticado, sin permiso granular propio | Shell; datos contextuales por AJAX | Read | READ-ONLY INTEGRATE |
| Datos GRE | shipping_guides.list, GET /shipping-guides/list | index | Igual | tenant/company/branch + tipo7 | Read | READ-ONLY INTEGRATE |
| Detalle GRE | shipping_guides.show, GET /shipping-guides/detalles/{guide} | show | Igual; no deducido de permiso list | tenant/company/branch + tipo7 | Read | READ-ONLY INTEGRATE |
| Crear GRE | shipping_guides.create, GET /shipping-guides/create | create | Sin permiso granular propio en ruta | Contextual | Preparación | OUT OF SCOPE |
| Guardar/enviar GRE | shipping_guides.store, POST /shipping-guides | store | Sin permiso granular propio en ruta | Contextual | Mutation + external | OUT OF SCOPE |
| Consultar GRE | shipping_guides.consult, POST /shipping-guides/{guide}/consult | consultNubefact | Sin permiso granular propio en ruta | forCurrentLocal + tipo7 | External + mutation | OUT OF SCOPE |
| Exportar GRE | shipping_guides.export, POST /shipping-guides/export | exportExcel | Sin permiso granular propio en ruta | Company del contexto | Download | OUT OF SCOPE |
| Legacy listado/datos | referral.guide.index / getDataGuides | index / getDataGuides | list_referralGuide en vista; datos sin granular | Global operativo | Read inseguro | BACKEND SYNC REQUIRED |
| Legacy detalle | referral.guide.show | show | list_referralGuide | Global operativo | Read inseguro | BACKEND SYNC REQUIRED |
| Legacy restantes | create/store/edit/update/destroy/print/export | ReferralGuideController | Ver matriz | Legacy | Mutations/documents | OUT OF SCOPE |

## Routes / Permissions

GRE mantiene auth/check.user.enabled/password.changed/tenant.context del grupo. No tiene gates de list/show/create/send/consult/export específicos en estas rutas. No se afirma que Owner tenga bypass ni que list autorice show. Es deuda de autorización granular conocida en discovery; no corregida desde Blade.

| Action | Route | Permission actual | Read/Mutation | Included |
|---|---|---|---|---|
| GRE list | shipping_guides.view/list | Grupo autenticado/contexto | Read | Sí |
| GRE detail | shipping_guides.show | Grupo autenticado/contexto | Read | Sí |
| Emitir → create | shipping_guides.create | Botón @can create_referralGuide; ruta sin gate granular | Preparación | No; botón previo preservado |
| Guardar/enviar | shipping_guides.store | Grupo; no gate granular propio | Mutation/external | No |
| Consultar / refresh | shipping_guides.consult | Grupo; botones sin @can específico | Mutation/external | No; hooks previos preservados |
| Export Excel | shipping_guides.export | Botón @can download_referralGuide; ruta sin gate granular | Download | No |
| PDF/XML/CDR | Links entregados por backend | Sin guard certificado aquí | Document | No |
| GRE edit/cancel/retry dedicado | No localizado en grupo actual | N/A | N/A | No inventado |
| Legacy create/store | referral.guide.create/store | create_referralGuide | Preparación/mutation | No |
| Legacy edit/update | referral.guide.edit/update | edit_referralGuide | Preparación/mutation | No |
| Legacy destroy | referral.guide.destroy | destroy_referralGuide | Mutation | No |
| Legacy print/export | imprimir/guia/remision/{guide_id}, exportar/guias/remision/v2 | print_referralGuide / download_referralGuide | Document | No |

No se abrieron modales de emisión/export ni se pulsaron botones de consulta. Las acciones preexistentes se mantienen con condiciones, IDs, URLs y permisos previos; su presencia NO certifica su seguridad ni extiende el QA. No se añadieron acciones nuevas.

## Scope

ShippingGuide::forCurrentLocal aplica igualdad explícita a tenant_id, company_id y branch_id obtenidos de TenantContext. Esos getters requieren sesión contextual y lanzan RuntimeException si falta. No toma contexto de query/body. El modelo no tiene un global scope equivalente: la garantía revisada corresponde a estos métodos concretos, no a todas las consultas posibles del modelo.

index añade tipo_de_comprobante=7. show recibe route binding, pero vuelve a consultar por forCurrentLocal + tipo7 + ID antes de cargar items/vehicles/drivers. Los hijos se cargan mediante shipping_guide_id del padre ya autorizado. Motivo se lee del catálogo global transfer_reasons por código.

| Dimension | Estado | Evidencia / límite |
|---|---|---|
| Tenant | PASS — código + test aislado | Lista y detalle rechazan otro tenant; no QA positivo MySQL |
| Company | PASS — código + test aislado | Otra company del mismo tenant excluida |
| Branch | PASS — código + test aislado | Otro local de la misma company excluido; NO es company-wide |
| Role scope | OPEN | Rutas modernas sin permiso granular; no se certifica matriz por roles |
| Contexto ausente | Falla cerrada | RuntimeException; no se transforma en vacío. UX de error no certificada |

## Dataset

SELECT actuales en transacción MySQL READ ONLY y rollback. Sin fixtures en MySQL.

| Familia | Total | IDs | tenant/company/branch, estado, serie/número, fecha, documento, tipo |
|---|---|---|---|
| shipping_guides | 0 | [] | Sin registros para informar |
| referral_guides | 0 | [] | Sin registros para informar |

**REAL DATASET — EMPTY**. Lectura moderna vacía corroborada en T1/C1/B1 (EDESCE principal), T1/C1/B8 (OUTLET DE EDESCE), T1/C2/B2 y T3/C3/B4 (Gamarra). Branches obtienen tenant a través de companies, no tienen columna tenant_id propia.

Sesiones reales: Owner EDESCE / EDESCE CONSULTORA E.I.R.L. / Local Principal y Owner Gamarra / Gamarra Moda / Local Principal: ambas renderizan 0 guías, página1 de1, sin errores de carga. Al finalizar se restauró Owner EDESCE. No hay guía positiva real para navegar.

Prueba pequeña `tests/Feature/ShippingGuideReadContractTest.php`: conexión SQLite explícita :memory:, tablas mínimas y cinco fixtures exclusivamente en memoria; sin migrar ni escribir en la DB configurada. 2 tests / 35 assertions PASS. Cubre lista por las cuatro combinaciones, query tenant/company/branch manipulada ignorada, detalle propio, ajeno por cada dimensión, tipo8 excluido, inexistente y filtros fecha/documento. No prueba permisos HTTP ni mutaciones. El GET real de detalle999999 en Gamarra devuelve404.

## States

Backend actual store usa DRAFT y SENT; resolución de respuesta usa ACCEPTED, REJECTED y PENDING_SUNAT. consultNubefact actualiza esos últimos estados. JS existente también contempla ERROR y valores desconocidos; no se afirma que todas las ramas backend produzcan ERROR.

Listado conserva etiquetas: ACCEPTED→ACEPTADA, SENT→POR CONFIRMAR, PENDING_SUNAT→PENDIENTE, REJECTED→RECHAZADA, ERROR→ERROR; desconocido/DRAFT se muestra literal en badge neutro. Detalle muestra status literal con badge semántico, sin cambiar significado. No se simularon respuestas externas ni se certificaron transiciones reales.

Columnas conservadas: Tipo, Serie-Número, Fecha Emisión, Motivo, Partida→Llegada, Estado SUNAT, Acciones. Tipo/identificador/fecha/estado/acciones centrados; motivo y trayecto descriptivo a izquierda. No se añadieron columnas inventadas.

## Filters

- fDesde → date_from; fHasta → date_to: whereDate >= / <= fecha_emision.
- fBuscarDoc → buscar_doc: sólo aplica si contiene guion; split serie/numero con igualdad exacta. No es búsqueda libre por destinatario ni substring. Entrada sin guion queda sin filtro según backend; comportamiento preservado.
- fTipoDoc contiene sólo opción7. JS no envía ese campo: backend impone tipo7; no fingir selector multiformato.
- No hay filtros de estado/destinatario/tipo adicionales. No se crearon lookups nuevos.
- Buscar reinicia page=1. Paginación Laravel de20 y metadata original, IDs prevPage/nextPage/paginationInfo intactos.
- UI con fechas y QA-999999 confirmó vacío sin error; tests aislados verifican inclusión/exclusión de fecha/documento.
- Vacío normal: página1/1, total0, anterior/siguiente deshabilitados. **MULTIPAGE QA — PENDING DATASET**. No se declara GENERAL-01 por paginación vacía normal.
- Ante fallo AJAX se muestra error de carga, se limpian metadata y se deshabilita paginación; no queda «Cargando» indefinidamente ni se representa un error como vacío. No se forzó error de red durante QA.

## Detail

show es lectura pura: reconsulta padre, carga relaciones y motivo, retorna vista; no refresh ni generación documental. Integrado con secciones, spacing compartido, tablas con scroll local, textos largos y toolbar adaptable. Se preservan campos de encabezado, destinatario, traslado, origen/destino, transportista, vehículos, conductores, items y payload/respuesta registrados.

QA propio/ajeno/inexistente: PASS en controller real con SQLite. Navegación real positiva y populated-state con datos de negocio: PENDING DATASET. Para visual se renderizó el contenido Blade con un modelo no persistido y relaciones en memoria, servido temporalmente en localhost8099; sin scripts funcionales, sin documentos ni llamadas externas. Marcado EVIDENCIA VISUAL AISLADA, servidor cerrado al terminar. No equivale a probar el shell completo del detalle real.

## Related Document

store puede asociar Sale del contexto mediante source_sale_id y snapshot source_sale_ref (serie/número o SALE#id); items_mode distingue selección. El listado selecciona shipping_guides.*, pero las columnas existentes no presentan esa referencia y show no la usaba: no se inventó una columna ni una navegación nueva. No se atribuye asociación Entry/Invoice/Order sin contrato. No se probaron creación ni composición de items.

Origen/destino: listado usa partida_ubigeo/llegada_ubigeo; detalle añade partida_direccion/llegada_direccion del snapshot. No reconstrucción frontend desde almacenes/branches.

## External Actions

**EXTERNAL INTEGRATION — OUT OF SCOPE**. Consultar es POST que llama Nubefact y actualiza last_nubefact_response, enlaces, aceptación, descripciones/códigos y status. No es read-only pese a su etiqueta. Store envía al proveedor. Ninguna operación ejecutada.

GET view/index/show modernos revisados no tienen esos efectos. GET legacy print genera PDF y queda fuera. Create, export y lookups operativos no se navegaron. No se califican como read-only seguro por ser GET.

## Documents

**DOCUMENT SECURITY — BACKEND/INFRA OPEN (DOC-01)**. No abrir, descargar, regenerar o certificar PDF/XML/CDR/QR/constancias. Links previos preservados sin activación. La rama legacy de PDF zip en show referencia shipping_guides.download, ruta no registrada en este grupo; no se ejercitó ni se reparó como parte de documentos. No se cierra DOC-01 ni se inicia reauditoría documental.

## Responsive

Patrón reutilizado: Advanced Operational List / Operational List del baseline y componentes compartidos next-page-heading, next-page-toolbar, next-toolbar-actions, next-form-section, next-list-summary, next-list-toolbar, table-responsive. Se retiró CSS legacy del listado (colores de columnas y top:100px del datepicker no utilizado por inputs date nativos). Detalle retiró kv inline; el pre de diagnóstico conserva su overflow local existente. **0 CSS nuevo**.

| Width | Listado real vacío | Detalle contenido aislado | Global overflow |
|---|---|---|---|
| 320 | PASS | PASS | NONE |
| 375 | PASS | PASS | NONE |
| 390 | PASS | PASS | NONE |
| 576 | PASS | PASS | NONE |
| 768 | PASS | PASS | NONE |
| 992 | PASS | PASS | NONE |
| 1200 | PASS | PASS | NONE |
| 1440 | PASS | PASS | NONE |

Medición documentElement.scrollWidth <= innerWidth; scrollbar vertical del navegador descuenta15px cuando corresponde. Tabla ancha usa scroll local. Capturas desktop/mobile revisadas; consola del listado sin errores antes del 404 deliberado. No cambios a responsive global.

Allowlist: sólo shipping_guides.view y shipping_guides.show. **ALLOWLIST MATCH: YES**. venti.min.css y venti-next.css observados en listado real. create y endpoints externos NO añadidos.

Evidencia fuera del repo: C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/guides-list-desktop.png, guides-list-mobile.png, guides-detail-fixture-desktop.png, guides-detail-fixture-mobile.png.

## Backend Sync Notes

[OPEN] GUIDE-01 existente — actualización de evidencia, no ID duplicado.
- Point: separar Shipping moderno contextual de Referral legacy global. La descripción histórica del backlog no basta para afirmar que el read moderno sigue global.
- File/endpoints: ShippingGuide::forCurrentLocal, ShippingGuideController@index/show; ReferralGuideController@index/getDataGuides/show.
- Impact: moderno limita tenant/company/branch; legacy aún expone ámbito operativo global. Permisos granulares modernos sin definir.
- Backend needs: cerrar contrato legacy y autorización por acción; no extender garantía a documentos/emisión.
- Frontend can continue: Sí, listado/detalle modernos con deuda; No, Referral legacy.

[OPEN] DOC-01 y external integration: sin cambios, no certificados. No duplicar como GUIDE nuevo.

[OPEN] Dataset: QA positivo real y multipágina pendientes. No crear guías para cubrirlos.

No confundir autorización granular ausente con AUTH-01 (permisos directos versus roles); esta fase no reprodujo ese root cause. No se cerró GUIDE-01 globalmente ni se modificó backlog canónico de DEV.

## Validation / Delivery

- Guides List: FRONTEND INTEGRATED — READ PATH WITH TECH DEBT.
- Detail: FRONTEND INTEGRATED — READ PATH; QA POSITIVE REAL DATASET PENDING.
- Tenant / Company / Branch: PASS por código y test aislado, límites arriba.
- Filters: PASS (UI vacía + pruebas aisladas); multipágina PENDING DATASET.
- Legacy Referral: BACKEND SYNC REQUIRED.
- External integration / mutations: OUT OF SCOPE.
- Documents: BACKEND/INFRA OPEN.
- Backend: UNCHANGED. Sin commit.
- PHP7.3 artisan view:cache PASS; node --check public/js/shipping_guides/index.js PASS; PHPUnit 2 tests/35 assertions PASS; git diff --check PASS.

Archivos modificados: config/venti-next.php; resources/views/shipping_guides/index.blade.php y show.blade.php; public/js/shipping_guides/index.js. Archivos creados: tests/Feature/ShippingGuideReadContractTest.php y este documento. No cambios a controllers, modelos, services, rutas, CSS, documentos, consumidores ni create.js.

## Create Remitente Audit — 2026-11-17

HEAD auditado: `eb5ebbc` (`front: integrar guias de remision read only`). Esta sección amplía el documento canónico; las matrices de lectura anteriores son históricas del 14/11. No se reaudita ReferralGuide ni se cambia su clasificación previa.

**Respuesta: todavía no habilitar creación.** El contrato de datos del GET y sus lookups permite avanzar técnicamente, pero el gate de permisos exigido para esta fase falla. No se integró la vista ni se añadió a allowlist. Store se probó sólo en SQLite en memoria con dependencias externas falsas; eso no significa readiness de autorización o emisión.

### Readiness Matrix

| Gate | Result | Evidencia / límite |
|---|---|---|
| Create GET | PASS | create retorna formulario, catálogos locales y customers del tenant; sin envío externo |
| Permission | FAIL | Create/store/lookups sin permission/can, policy ni autorización funcional en controller |
| Tenant | PASS | Contexto de sesión; customers y productos tenant-scoped; snapshots gre_* deben coincidir |
| Company | PASS | Ventas y habilitación de productos por company; emisión/config/serie por company |
| Branch | PASS | Ventas limitadas al local; guía persiste local de sesión; producto habilitado company-wide, no filtro de stock local inventado |
| Sale lookup | PASS | Servicio real probado contra propia, otra branch/company/tenant, anulada/reversed |
| Sale items | PASS | Ambos endpoints reconsultan venta contextual antes de relaciones; inexistente/ajenas404 |
| Customer | PASS | exists tenant + not deleted; lookup reconsulta tenant; nombre viene del modelo |
| Origin/Destination | PASS | Ubigeo existe; direcciones/códigos validados. No certificación fiscal externa |
| Store validation | PASS — casos probados | Contexto, customer, sale, manual quantity/ownership, serie; no validación fiscal exhaustiva |
| Nubefact boundary | OPEN | Orden y fallos probados con fake; proveedor real fuera de alcance |
| Idempotency | OPEN — TECH DEBT | Doble POST reproduce dos guías; GUIDE-02 |
| Documents | OPEN | DOC-01, sin abrir ni certificar |

Decisiones: **CREATE REMITENTE — BACKEND SYNC REQUIRED (permission gate)**. **STORE — BACKEND SYNC REQUIRED**. No confundir aislamiento con autorización funcional. Una futura corrección de permisos puede liberar integración visual aunque idempotencia/emisión sigan pendientes; no se implementa un guard sólo en JS.

### Permissions

Grupo actual: auth + check.user.enabled + password.changed + tenant.context. InitializeTenantContext resuelve/valida tenant activo y company/branch autorizadas mediante memberships activos; Platform Admin no recibe contexto operativo. No es un permiso para emitir guías. ShippingGuideController no añade autorización funcional propia.

| Endpoint | Method | Permission | Context | Result |
|---|---|---|---|---|
| shipping_guides.create | GET | Sin granular | Tenant + company para numeración; Blade requiere T/C/B | FAIL permission |
| shipping_guides.store | POST | Sin granular | T/C/B + gre_* coincidentes | FAIL permission |
| sales.select2 | GET | Sin granular | T/C/B, no anulada/reversed | Datos PASS, permission FAIL |
| sales.items | GET | Sin granular | Reconsulta sale por T/C/B | Datos PASS, permission FAIL |
| shipping_guides.sale_items | GET | Sin granular | Reconsulta por serie/número y T/C/B | Datos PASS, permission FAIL |
| shipping_guides.customer_payload | GET | Sin granular | Tenant | Datos PASS, permission FAIL |
| shipping_guides.products | GET | Sin granular de guías | MaterialInventoryScope exige membresía activa; catálogo T/C | Datos PASS, permission FAIL |
| ubigeo.departments/provinces/districts.select2 | GET | Grupo, sin granular | Catálogo geográfico global | No datos empresariales |
| customer.store (modal auxiliar) | POST | Contrato Customers separado | No ejecutado | OUT OF SCOPE |

El test de permisos caracteriza la ausencia de middleware granular, NO afirma que el test verde sea autorización segura. El botón Emitir del listado usa create_referralGuide, pero no protege estas rutas directas. DEV debe definir permiso y aplicarlo coherentemente a vista, store y lookups; no se inventó nomenclatura frontend.

### Type / Context

GET lee `type` con default literal `remitente`. Sólo acepta ese literal: transportista, REMITENTE y valor inválido →404 (tests). POST no selecciona tipo desde query ni body: fija guide_type=REMITENTE y tipo_de_comprobante=7. Un payload con type=transportista sigue generando únicamente remitente en el test. No se habilita guía transportista.

Inputs hidden gre_tenant/gre_company/gre_branch son snapshots de TenantContext; store compara cada uno con sesión y rechaza discrepancias con422 antes de persistir. tenant_id/company_id/branch_id arbitrarios no son usados para ownership. La prueba de store confirma que esos valores manipulados no alteran [1,1,1].

### Lookups / Sale / Items

JS usa sales.select2 y sales.items (`/dashboard/sales/:id/items`). shipping_guides.sale_items por sale_ref permanece declarado; se probó también para no dejar un endpoint alternativo sin revisión. sales() filtra tenant/company/branch, state_annulled=false e internal_reversal_status distinto de reversed o NULL. `q` se agrupa dentro de ese scope. No usa Worker ni scope de Sales List por analogía.

Fixtures independientes: propia1; otra branch2; otra company3; otro tenant4; anulada5; reversed6; inexistente999. Select2 sólo devuelve1. Ambos endpoints de items y store rechazan las otras seis. SALE usa todos los detalles backend; quantity o packs si hay presentación, exige cantidad>0 y venta no vacía; copia stock_item_id/material_id y descripción backend. Unidad fijada NIU, no campo libre. source_sale_id/source_sale_ref se guardan como vínculo/snapshot. No requiere que destinatario seleccionado coincida con customer de la venta: contrato no lo impone, no se añadió regla.

MANUAL no significa texto libre sin catálogo: items[].product_id es StockItem y material_id debe coincidir con el material del catálogo habilitado para company. Cantidad min0.001; descripción requerida max255; código nullable max60; detalle adicional max255. Unidad NIU fija. Duplicados no tienen regla distinct ni consolidación: se preservan filas; no se declara bug sin requisito comercial. No escribe InventoryLevel, stock, kardex ni Items físicos.

Prueba usa PosCatalogReadService real, no filtro falso: devuelve producto propio; rechaza stock item de otra company/tenant y par stock/material incongruente. Guarda manual1.125 con NIU en memoria. No certifica cantidades/precios de una venta real.

### Customer

Customers es tenant-level. GET carga customers()->orderBy business_name. Payload cliente reconsulta tenant y delega a CustomerController::payload: documento RUC, nombre, dirección y email de primer contacto si existe. JS propone tipo documento por longitud (8→1,11→6, fallback6) y rellena campos; el usuario puede modificar snapshot permitido. Store valida customer_id por tenant/not deleted y reconsulta; customer_name usa business_name backend, no texto JS. Doc type permitido6/1/4/7 activo; número requerido max20; dirección opcional255; emails validados120. La propiedad no depende del documento/nombre enviado. No se ejercitó creación de cliente ni servicios de consulta DNI/RUC.

### Transfer Data

Catálogos GET: transportTypes activos01/02, transferReasons activos, weightUnits activos KGM/TNE, indicators activos, identityDocTypes activos. No requests separados: reglas inline en store. Motivo requerido string2 y exists activo. Labels provienen de DB; seeder actual enumera01 VENTA,02 COMPRA,04 TRASLADO ENTRE ESTABLECIMIENTOS DE LA MISMA EMPRESA,14 VENTA SUJETA A CONFIRMACIÓN DEL COMPRADOR,18 TRASLADO EMISOR ITINERANTE CP,08 IMPORTACIÓN,09 EXPORTACIÓN,05 CONSIGNACION,17 TRANSFORMACION,03 VENTA CON ENTREGA A TERCEROS,06 DEVOLUCION,07 RECOJO DE BIENES TRANSFORMADOS,13 OTROS. Esto describe seed/código, no un relevamiento actualizado de catálogos MySQL. No se encontraron condiciones frontend adicionales por motivo.

Origen/destino: Select2 departamento/provincia/distrito global, filtros de parent; hidden ubigeo toma district.id. Ubigeo requerido6 caracteres y exists districts; dirección requerida255. No default de warehouse/branch ni reconstrucción automática desde compañía. Código establecimiento opcional4 caracteres, backend fallback0000; la vista no expone un selector nuevo. Fechas requeridas date, sin imponer relación temporal adicional desde frontend.

Transporte01 público: RUC/tipo6 activo, documento y nombre transportista, placa principal. Transporte02 privado: placa principal, documento/tipo activo, nombres/apellidos/licencia conductor; hasta2 vehículos y2 conductores secundarios. JS alterna secciones. Son datos del transporte de una GRE remitente, no habilitación de GRE transportista.

Peso requerido numeric min0 (cero aceptado por contrato), step UI0.001 y cast decimal3; KGM/TNE activo. Bultos integer min1. Defaults visuales peso1/bultos1. No cálculo automático. Indicador opcional activo; buildPayload sólo implementa los tres códigos REMITTER_INDICATORS (04/05/06 de proveedor). Catálogo futuro fuera de esa lista provocaría excepción antes del commit; no se inventan códigos.

### Series / Number

Serie proviene de NubefactCompanyConfigService por tenant/company, no branch. greRemitterNumbering valida patrón T +3 alfanuméricos mayúsculos, inicial1..99999999. Formulario readonly muestra serie/número referencial. Store exige serie exactamente igual a configuración y recalcula número; ignora numero enviado. Dentro de transacción bloquea fila company con lockForUpdate, calcula max(numero)+1 en T/C/tipo7/serie y respeta inicial. Índice de migración gre_company_document_unique cubre T/C/tipo/serie/numero.

Tests: enviar numero999 obtiene1, repetición obtiene2; serie distinta422 sin persistencia. SQLite NO demuestra lock/concurrencia MySQL ni confirma índice aplicado en DB local. Concurrencia productiva queda pendiente, no requisito para inventar numeración UI.

### Store Boundary

1. Compara contexto gre_*.
2. Valida campos y transporte condicional.
3. Reconsulta customer, sale o catálogo manual; valida ownership.
4. BEGIN; lock company, resuelve conexión/config y numeración.
5. Crea ShippingGuide DRAFT, vehículo/conductores y líneas; guarda vínculo venta si aplica.
6. buildPayload local; guarda payload y SENT.
7. COMMIT; persisted=true.
8. send Nubefact real (reemplazado por mock en tests).
9. Actualiza respuesta, estado y links fuera de la transacción inicial.

Guardar y enviar es una sola petición/acción de usuario, pero la llamada externa ocurre después del commit. No existe opción guardar borrador separada en este contrato. No se añadió ninguna.

### Nubefact Boundary

Antes del commit, error produce rollback y422 genérico (probado con excepción fake de buildPayload; cero guía/líneas). Después del commit, excepción send devuelve502 con status SENT y guía persistida; no ERROR ni rollback total. UI reconoce SENT+id y vuelve al listado. En aceptación guarda ACCEPTED; respuesta sin aceptación/errores →PENDING_SUNAT; SOAP error/responsecode no vacío →REJECTED (revisión código; aceptación y pending probados con fake). No hay evaluación fiscal real.

Tests mockean config con objeto de conexión ficticio y NubefactGreService; Http::fake lanza ante cualquier llamada accidental y assertNothingSent verifica cero tráfico. No se leen credenciales reales, no se usa DB compartida, no se descarga documento. Payload proveedor real se audita por código; el mock de buildPayload no certifica compatibilidad externa.

### Idempotency

**TECH DEBT — GUIDE-02.** Deshabilitar botón mientras AJAX está activo no es idempotencia servidor. Dos solicitudes idénticas guardan/emiten dos guías con números1 y2. Lock/índice evitan repetir correlativo, no repetir intención. No hay request key, deduplicación ni recuperación de resultado por clave. Si respuesta se pierde tras commit/envío, reintentar la misma operación puede duplicar documento. Reproducción aislada determinista; no se ejecutó concurrencia real.

DEV debe definir clave/resultado idempotente y semántica de reintento, sin asumir que todas las guías de una misma venta son duplicados. EXT-01 sigue cubriendo certificación proveedor; GUIDE-02 describe este root cause reproducido específico.

### Create Frontend

**NO INTEGRADO en esta fase:** permission gate FAIL. Blade/JS funcional/allowlist/CSS/backend intactos. No se reemplazó autorización con ocultamiento visual. Responsive320..1440: NO EJECUTADO para Create porque la decisión de integración está bloqueada; QA histórico de listado/detalle no se extrapola. CSS nuevo0. No submit en navegador ni petición de escritura MySQL.

### Remaining Backend Sync

- GUIDE-01: lectura moderna contextual demostrada; Create/lookups con scope correcto pero autorización funcional pendiente. Legacy conserva estado previo, sin nueva investigación.
- GUIDE-02: doble submit no idempotente demostrado. Requiere decisión backend antes de habilitación operacional.
- EXT-01: proveedor, concurrencia MySQL y recuperación externa no certificados.
- DOC-01: DOCUMENT SECURITY — BACKEND/INFRA OPEN.
- CREATE: BACKEND SYNC REQUIRED por permisos; puede integrarse cuando DEV defina/aplique el gate, separado del cierre de store.
- STORE: BACKEND SYNC REQUIRED; apto para continuar pruebas aisladas, no calificado READY por tener tests verdes.

### Isolated Tests / Delivery 2026-11-17

`ShippingGuideCreateContractTest.php`: 8 tests /101 assertions PASS. Incluye type/default, catálogos tenant, permisos caracterizados, sale/items/store ajenos, contexto manipulado, customer ajeno, validaciones manuales, catálogo manual real, fake aceptación/pending, fallo antes/después de commit y duplicidad. Esquema mínimo SQLite, no replica constraints/tipos completos MySQL ni prueba política de roles HTTP.

Con `ShippingGuideReadContractTest.php`: 10 tests /136 assertions PASS, PHP7.3. Sin frontend/backend modificado. Archivos de esta fase: este documento, backlog DEV y test nuevo. git diff --check PASS; sin commit.
