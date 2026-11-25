# R8.0 / R8.1 — Global Semantic Functional Parity Audit

## R8.2A — estado vigente (2026-11-25)

Autorización del propietario: FR-108/109/110 quedan formalizados. **FR-108 RESTORED; FR-109 RESTORED; FR-110 BLOCKED / EXPENSE-01**. Conciliación: **76 recuperadas +11 bloqueadas +23 excluidas =110**; pendientes frontend confirmados0. Los107 anteriores y propuestas inferiores se conservan como historia, no son el total vigente. S201/S203 no se suman sin contrato DEV.

A194/194 auditadas: **187 con paridad /5 con pérdidas /2 dependencias monetarias** después de recuperar3 superficies mediante2 capacidades. B2 y C125 sin cambios. Nuevos findings DEV0. R8.2A COMPLETE — FRONTEND RECOVERY; R8 global sigue parcial por alcance/contratos; R8.2B no iniciada.

Evidencia: [informe R8.2A](REAL-NEXT-R8-FUNCTIONAL-PARITY-RECOVERY.md), test `tests/Frontend/r82a-recovery.cjs` PASS (8 casos de paginación +2 navegaciones numéricas; menú375/1440; cero pageerrors/overflow). Chrome headless aislado y requests interceptados, no certificación HTTP autenticada ni mutaciones. Ruta destino verificada con PHP7.3.33 y contrato actual. Backend0 / Blade0 / CSS0 / infraestructura0 / mutaciones0; runtime sólo2 JS. `git diff --check` PASS.

Plan restante: FR110 espera EXPENSE-01; mantener los10 bloqueos anteriores, aclaración monetaria, B y decisiones C. No recuperar gasto ni iniciar nuevas fases. Esperar revisión del propietario.

## Historia preservada anterior a R8.2A

## Estado vigente — continuación final R8 (2026-11-25)

HEAD `079aea9b25fa5fe9591fd5607a1801640e5108e4`; baseline compartido `6df57e7`. Se preservaron los tres documentos R8 ya modificados al inicio; no se incorporaron commits ni cambios DEV. Esta sección prevalece sobre los checkpoints históricos inferiores.

**Inventario379: A194 / B2 / C125 / excluidas58. Auditoría A194/194: anteriores139 + adicionales55; pendientes0. Evidencia acumulada PREVIOUS42 / NEW152 (esta continuación2 /53).** Son superficies/variantes renderizadas, no194 URLs independientes. S172 es fallback409 de Create/Edit, sin ruta propia nueva ni duplicación de capacidad.

**Paridad semántica confirmada184; auditadas con pérdidas8** (4 superficies con pérdidas anteriores bloqueadas y4 con nuevas pérdidas); **2 con dependencia contractual monetaria**, sin paridad certificada. Auditoría cerrada NO equivale a implementación completa, seguridad integral o QA mutadora PASS.

Nuevas capacidades perdidas acumuladas en R8: **2 completas de navegación +1 parcial de paginación**; de ellas, en esta continuación se añade sólo R8-F01 (retorno desde Crear gasto, bloqueado por EXPENSE-01 ya existente). Nuevos gates obsoletos0; nuevas raíces/blockers DEV0. Dos recuperaciones frontend directas propuestas y una dependiente del fix DEV existente. S201/S203 no inflan el conteo.

Baseline FR intacto: **74 recuperadas +10 bloqueadas +23 excluidas =107**. Propuestas deduplicadas: FR-108/R8-B02, FR-109/R8-E02 y FR-110/R8-F01; no incorporadas como restauradas ni renumeradas. Conciliación prospectiva, si se aprueban: **110 =74 recuperadas +2 pendientes frontend +11 bloqueadas DEV +23 excluidas**. Candidato monetario1 compartido por2 vistas permanece fuera de los110.

**R8.1: AUDIT COMPLETE FOR UNIVERSE A — FINDINGS OPEN. R8.0/global: PARTIAL — PRODUCT SCOPE DECISIONS OPEN (C125). R8.2: PLAN PREPARED / NOT STARTED / OWNER AUTHORIZATION REQUIRED.** B2 pendientes (Recepción y Registrar comprobante); observaciones visuales receipt/Branch/GRE preservadas. `frmGreRecovery` existe mediante partial incluido: no formulario huérfano.

Backend/Blade/JS/CSS/rutas/allowlist/infraestructura y mutaciones comerciales: **0 cambios/ejecuciones**. Ningún HTTP/GET escritor, export, documento ni proveedor invocado; sin navegador/escritorio ni QA responsive repetida. Verificación nueva estática Git/contratos; simulación previa de paginación identificada como tal, no dato real. Sin commit/push/fetch/pull/merge/rebase.

### Cierre individual agrupado de las55 superficies

Método: comparación padre de homologación → homologación → HEAD, lectura de handlers/contratos y reutilización de los informes citados. El inventario automático de names/routes/hooks sólo orienta; no se usa por sí solo como PASS. No se atribuye a esta ejecución el QA runtime de los informes anteriores. Los diffs íntegros de los paths de estas55 vistas y scripts quedan en `r8-final-historical-evidence.json` junto al ledger externo.

| IDs | Familia | Evidencia | Interpretación semántica |
|---|---|---|---|
| S157 | Material Create | aa2ded06; Material Create Contract + Post-Merge QA + diff hasta HEAD | Guardar FormData, reset, imagen, variantes, clasificación dependiente y altas auxiliares conservados. Contexto/version, unidad obligatoria, tipo fiscal, permisos de talla/color/género y Option segura son adaptaciones DEV; no restaurar defaults/IDs antiguos. |
| S163 | Material List V2 | 8faf1ef3^/8faf1ef3/HEAD; Material List Contract; presentations-branch.js | Búsqueda, 15 columnas, paginación, ocho acciones de fila, imagen/inventario, stock y resumen preservados. Cédula/calidad eran parámetros no-op del endpoint getDataMaterials, no confundir con getDataMaterialsPack/Store. Presentaciones migradas a adapter por local, no eliminadas; cantidad fija y precio local siguen contrato DEV. |
| S171, S172 | Material parameters / required state | 8faf1ef3^/HEAD; MaterialController:90,819; Material Parameters Contract | Guardar enabled_sections[] + CSRF, reset, grupos y extraKeys preservados; _context/version y unidad obligatoria alineados a DEV. S172 es vista completa fallback 409 de Create/Edit, NO partial ni URL nueva: unidad del inventario es variante renderizada, no página independiente. |
| S118 | Stock file import | 8faf1ef3^/HEAD; Material Stock Import Contract; files/stockFiles.js | Seleccionar archivo, FormData, plantilla, submit/reset y errores preservados; submit sin archivo deshabilitado no retira una capacidad válida. No importación ni descarga ejecutada. |
| S062 | Company Stock Items | 4b0ac964^/HEAD; Company Stock Items report; index.js | URI dashboard/dashboard/company-stock-items confirmada. Selectores company/brand/category/status/search, paginación y toggle company_stock_items preservados; abort/version evita respuestas del contexto anterior. No se altera stock físico. |
| S058, S059, S060 | Company bank accounts | 1f972a7c^/HEAD; REAL-NEXT-COMPANY-BANK-ACCOUNTS | Crear/editar/toggle conservan permisos, métodos, partial de campos, default/moneda, CSRF y handlers intactos. Cambio a dropdown sólo presentación. No búsqueda/paginación inventada; no certificar mutaciones ni deuda default por auditoría estática. |
| S061 | Company Settings | 58a7841a^/HEAD; REAL-NEXT-COMPANY-SETTINGS-BRANDING | Definiciones dinámicas, tipos, hidden boolean0, enum vacío, imágenes/previews, snapshots y Guardar FormData conservados; cambio JS limita etiqueta del switch sin tocar label principal. No secreto/override real leído ni upload. |
| S319 | Supplier accounts | 4b546d4c^/HEAD; REAL-NEXT-SUPPLIER-ACCOUNTS | Agregar, guardar inline, eliminar y volver a proveedores conservados. closest(data-account-row) sustituye traversals; value corrige selección, permisos y CSRF intactos. No confundir con SupplierCredit; scope del parent y permiso list_supplierAccount actuales son autoridad. |
| S066, S067, S068, S069 | Contacts | 3883302a^/HEAD; REAL-NEXT-CONTACTS | Create/edit names, FormData, reset y validaciones preservados; DataTables list/restore conservan endpoint, búsqueda, página, edición, delete/restore y parent. Escapado y guard null no cambian IDs; nuevas rutas de retorno no eliminan acciones. |
| S077, S078, S079, S080 | Customers | 5242c73f^/HEAD; REAL-NEXT-CUSTOMERS | Forms create/edit y handlers intactos; index/restore cambian sólo composición, badge y escapado. Mantienen consulta documental existente, modal delete/restore, payload e IDs. Restore batch DEV no se reemplaza por reglas JS; sin llamada externa ni GET search-or-create. |
| S315, S316, S317, S318 | Suppliers | cffac49e^/HEAD; REAL-NEXT-SUPPLIERS | Create/edit y FormData intactos; list/restore conservan búsqueda/paginación, edit/delete/restore, acceso y visualización de cuentas con list_supplierAccount. No confundir controles de cuentas con créditos. |
| S364, S365, S366, S367 | Workers | 5a79bc83^/HEAD y 996a31a4^/HEAD; REAL-NEXT-WORKERS | Create/edit sin delta JS. Index conserva export, 29 columnas, editar/deshabilitar/cuentas/crear-renovar-terminar contrato y edición/eliminación del término con condiciones originales; dropdown no filtra capacidades adicionales. IndexEnable conserva habilitar y have_contract histórico determinista, no vigencia; columna21 inicialmente visible, sin añadir columna. |
| S228, S229, S238, S243, S244, S245, S300, S301, S302, S332, S333, S334 | Platform administration | 673cb064^/HEAD; REAL-NEXT-PLATFORM-ADMIN-CONTRACT | Parámetros laborales edit/update; ActivityLog filtros/página/detalle; Tenants create/show/update/toggle/resetOwnerPassword; RoleTemplate y TenantRole create/edit/toggle + catálogo/selección permisos preservados. JS mutador/form sin delta; renderers sólo dropdown/alineación. Breadcrumb platform.dashboard corrige ámbito; cupos añadidos por partial R77D, no sustituyen formularios. |
| S240, S241 | Platform Nubefact | d2592207^/HEAD; REAL-NEXT-PLATFORM-NUBEFACT | Listado/filtros/Configurar y formulario POST CSRF/version, credenciales, enabled y series preservados. configurationVersion reemplaza updated_at por contrato DEV; serie GRE/correlativo añadidos. No prueba de conexión ni proveedor. |
| S242 | Platform shipping catalogs | b951afdc^/HEAD; REAL-NEXT-PLATFORM-SHIPPING-CATALOGS | Pestañas y alta con availableCodes/supportedCodes preservadas. Edición inline trasladada a modal por registro; mismo PUT, record_id, old(), validación, nombre/orden/estado/código readonly; modal de error se reabre. No ejecutar. |
| S345 | Platform tax types | 4c2b1989^/HEAD; REAL-NEXT-PLATFORM-TAX-TYPES | Crear/editar y flags estado/default mantienen handlers y validación backend; JS sólo cambia wrapper/IDs/escape. Código readonly en edit preservado; no duplicación de reglas fiscales. |
| S044, S045, S369 | Quotation auxiliary catalogs | 3636c1f1^/HEAD; categoryEquipment y workforce diffs | Categorías: búsqueda/sugerencias/paginación, equipos hijos, crear/editar/delete/restore e imagen conservados; URLs relativas sustituidas por routes exactas en data-url. Workforce: búsqueda/tabla/modal/guardar/delete, precio/unidad/facturable y manage_workforce intactos; JS sin delta. Sin reactivar cotización industrial. |
| S361, S362, S363 | Warrants | b898890a^/HEAD; REAL-NEXT-WARRANTS | Listado y CRUD legítimo Warrant conservados/corregidos. Original consultaba y mutaba Género; bulk enviaba a genero/delete-multiple, NO capacidad Warrant válida. No recuperar operación del dominio equivocado; WARRANT-01 no es nueva pérdida. |
| S249 | Price List | b898890a^/HEAD; REAL-NEXT-PRICE-LISTS-GENERAL | Maestro crear/editar/estado/default y tabla nueva; editor de productos/variantes permanece en details con selector, filtros y guardar. manage_priceListMaterial coincide con endpoints; NO eliminar editor por alcance QA. Cache/error/diálogos conservan payload y moneda. |
| S081 | Operational dashboard | R7 audit dbca24e1^/HEAD + entry-export.js; R76 evidence | Reutiliza matriz R7 de ocho pérdidas: FR104 recuperada; siete bloqueadas FR063/064/102/103/105/106/107. Cards/ranking/gráficos actuales conservados; no reactivar handlers históricamente comentados ni GET escritores. |
| S095 | Purchase Entry Edit | R4/R75/R76; PurchaseFormReadAccess:13,32; routes/web.php:1150-1170 | Superficie fuera de allowlist incluida por navegación FR047 retirada. Formulario DEV permanece; permiso entry_edit=edit_entryPurchase distinto de update_entryPurchase de ruta. Auditoría cerrada con blocker conocido, no paridad ni nueva homologación. |
| S201, S203 | Normal purchase monetary detail | abc9daa0^ y c32352c5^/HEAD; OrderPurchaseController:2151-2179,2238-2240,2345-2356; NormalPurchaseGuard:103 | DEPENDENCIA CONTRACTUAL DEV: contrato GET entrega price/igv/total_detail y order.total/igv/currency, no bases ni tax_type snapshot. Retiro de precio sin IGV y subtotal visible confirmado, legitimidad del cálculo histórico 1.18 no certificada. No contar como pérdida recuperable hasta respuesta DEV. |
| S109 | Expense Create | d43ca2f7^/HEAD; REAL-NEXT-EXPENSES; ExpenseController y Expense model | Formulario/guardar/reset intactos; navegación expense.index eliminada de toolbar y breadcrumb. Destino activo en código/menu pero bloqueado por EXPENSE-01 (sin ownership), no decisión de retiro. Pérdida de navegación con dependencia DEV conocida, no restaurar inseguramente. |

### R8-A13 — resolución de S201/S203: DEPENDENCIA CONTRACTUAL DEV

El padre de abc9daa0 (showNormal) y c32352c5 (showDelete) mostraba precio sin IGV calculado `round(price/1.18,2)`, subtotal de cabecera `total-igv` y fallback de total de línea `quantity*price`. HEAD muestra price, igv, total_detail almacenados y moneda/IGV/total de cabecera; null se presenta como No registrado. `fillItems` no sustituía automáticamente el subtotal inicial: updateSummaryInvoice estaba comentado. No se halló decisión DEV explícita que ordenara retirar esa información; la motivación del informe frontend no equivale a aprobación fiscal.

GET showOrderPurchaseNormal/showOrderPurchaseDelete carga modelos/details con scopes y relaciones; no entrega net_unit_price, taxable/exempt/unaffected bases ni tasa snapshot por línea. OrderPurchaseDetail fillable: quantity, price, igv, total_detail (más IDs). No aparece distinción de bases gravada/exonerada/inafecta en este contrato. La presencia de TypeTax en Material no garantiza el impuesto histórico de la compra.

El código actual de creación/edición de líneas trata total_detail como importe inclusivo para derivar IGV mediante total-total/1.18 (Controller2238-2240 y2345-2356); guarda price desde payload, y NormalPurchaseGuard::recalculate suma total_detail/igv. Esto evidencia la convención del writer actual, NO una garantía universal sobre históricos/migraciones, precios unitarios nulos, descuentos o líneas a otra tasa. La resta total-igv evita una tasa fija pero sigue necesitando definición de qué representa el subtotal contractual. Por tanto no se inventa una fórmula fiscal ni se declara sustitución equivalente/cambio intencional.

**Pregunta DEV exacta:** ¿Para todas las órdenes normales vigentes y eliminadas, incluidos históricos, `price` y `total_detail` incluyen IGV y `igv` es el impuesto final de esa línea? ¿Es `order.total-order.igv` el subtotal neto autorizado aun con descuentos, exoneradas/inafectas y datos null? Si no es universal, entregar campos autoritativos net_unit_price/subtotal y snapshot fiscal (o una regla explícita de indisponibilidad); definir también el fallback de total_detail null. No recuperar división1.18 a partir del Material actual. Es una aclaración contractual, no nuevo bug backend demostrado; candidato fuera del conteo FR.

### R8-B02 / FR-108 propuesto — solicitar traslado contextual (P1)

En a90d322a se retiró el enlace renderizado cuando `warehouse.request_url` existía. WarehouseAvailabilityService:67-75 sigue entregándolo sólo con disponible positivo/no null, producto trazable, list_transfer+create_transfer y almacén destino de branch actual distinto del origen. Usa warehouseTransfer.create con source_warehouse_id, stock_item_id y snapshots form_tenant_id/company_id/branch_id. WarehouseTransferController::create valida snapshots, ownership del origen, disponibilidad y prefill. Recuperación propuesta: consumir exclusivamente request_url entregado; no reconstruir URLs ni reactivar transfer legacy. Una capacidad contextual, no pérdida de todo Transfers.

### R8-E02 / FR-109 propuesto — paginación Ganancia Diaria (P2)

bd413c65 cambió el argumento del bucle de `i` a `pagination.totalPages` en public/js/ganancias/index.js:144; el atajo final de153 sí debe usar totalPages. Afecta S120/S121 que comparten JS; Prev/Next subsisten. Simulación aislada previa `r8-daily-pagination-isolated.json`: con10 páginas, página1 devuelve [10,10,10,10] frente a [1,2,3,4]; página4 devuelve [1,10,10,10,10,10,10] frente a [1,2,3,4,5,6,7]. No se ejecutó reporte real.

Barrido focal `renderItemPage(totalPages/lastPage)` en public/js:8 coincidencias/7 archivos. Ganancias/index.js:144 es el único bucle defectuoso de esas coincidencias. indexTrabajador:382 usa i y391 atajo final; quoteSale/general:555 y facturadas:554 usan i,564/563 atajos; orderPurchase/normalV2:168, generalV2:93 y expressV2:93 usan i,177/102/102 atajos. Estos seis no reproducen la raíz. Es búsqueda focal, NO certificación de todos los paginadores del ERP ni reauditoría de módulos excluidos.

### R8-F01 / FR-110 propuesto — retorno Crear gasto → Rendición (P2, bloqueado)

d43ca2f7 retira route(expense.index) de toolbar y breadcrumb en expense.create. Antes era navegación ejecutable; Guardar/reset/CSRF/campos siguen presentes. REAL-NEXT-EXPENSES reconoce retirada por lista fuera del rollout. HEAD Expense continúa sin BelongsToTenant/ownership; getAllExpenses filtra fecha, no tenant/company. Root cause **EXPENSE-01** ya documentado (backlog DEV), no defecto nuevo. No hay aprobación de retiro funcional; ocultar enlace no elimina la capacidad ni arregla endpoint. Clasificación: pérdida completa de ese acceso contextual, dependiente de DEV, no recuperar inseguramente. Criterio: primero lista/JSON contextual y permisos; luego enlace de retorno según permiso, sin cambiar el formulario ni alcance de Expense por inferencia.

### Conciliación y límites

184 conformes al contrato funcional actual;8 auditadas con pérdidas: S081/095/100/308 conocidas, S109/120/121/357 nuevas. S201/203: incertidumbre contractual, no conforme ni pérdida FR nueva confirmada. Se conserva separación de implementación y QA: los controles de escritura existentes no fueron ejecutados y no se ocultan por eso.

No duplicados FR:108 es navegación desde Availability, no ajuste StockItems FR059;109 es paginación DailyProfit, no historial rotación/export de dashboard FR063/064/105;110 es retorno desde ExpenseCreate, no Bill CRUD ni Cash ni FR de Entry. Los IDs son propuestas para aprobación; baseline107 no cambia silenciosamente.

## Evidencia histórica preservada — checkpoints anteriores

## Estado vigente - continuacion sobre HEAD 079aea9 (2026-11-25)

Baseline compartido 6df57e7; HEAD observado 079aea9b25fa5fe9591fd5607a1801640e5108e4. El delta contiene el checkpoint R8 y R7.7E previamente aprobados; no se incorporaron cambios remotos. Working tree limpio al inicio. Los estados siguientes prevalecen sobre los checkpoints historicos conservados.

Inventario 379: A194 / B2 / C125 / excluidas58. Cierres anteriores80; adicionales59; acumulado139/194 (71.65%), pendientes55. Evidencia acumulada: PREVIOUS40 / NEW99; esta ejecucion1 reutilizado y58 nuevas comparaciones. Cierre de auditoria NO significa recuperacion: S357 queda auditada con perdida confirmada R8-B02.

Nuevas perdidas confirmadas1 (navegacion contextual Disponibilidad -> Solicitar traslado); parciales confirmadas1 (R8-E02 paginacion numerica Daily Profit); blockers DEV nuevos0. R8-A13 mantiene dos detalles pendientes por representacion monetaria retirada, sin atribuir una decision fiscal a DEV. FR canonicos74 recuperados +10 bloqueados +23 excluidos=107; R8-B02 se registra separado como hallazgo nuevo pendiente de plan R8.2, sin reutilizar/renumerar IDs FR.

R8.0 PARTIAL; R8.1 PARTIAL; R8.2 NO INICIADO, requiere revision del propietario. Implementacion/backend/Blade/JS/CSS/rutas/allowlist/infraestructura/mutaciones0. No QA runtime nuevo ni llamadas a proveedores.


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

## Continuacion 079aea9 - bloques comercial, inventario, caja y RRHH

### R8-A13 - Normal detalle S201/S203: representacion monetaria pendiente

abc9daa^ y c32352c5^ muestran Precio S/IGV como price/1.18 y Subtotal como order.total-order.igv. abc9daa/c32352c5 los retiraron. showNormal.js fillItems NO recalcula el subtotal al cargar: updateSummaryInvoice esta comentado alli. Por tanto, no justificar el retiro del subtotal diciendo que siempre era sobrescrito por JS. Los templates mutadores Express carecian de trigger alcanzable desde estas filas readonly; R3.1 demuestra ausencia de restore/print propios del detalle. Navegacion de retorno y campos persistidos preservados.

La documentacion de homologacion explica el retiro como rechazo de derivados no entregados por backend, pero eso no demuestra decision de producto/DEV de retirar la lectura monetaria. Separar: (1) subtotal derivado de snapshots; (2) precio neto basado en18% fijo; (3) fallback quantity*price cuando total_detail es null. No restaurar formulas fiscales por analogia. Mantener ambas superficies PENDING hasta resolver representacion autorizada; no clasificar como backend roto ni declarar paridad plena. URLs historicas de evidencia R3.1: http://127.0.0.1:8080/dashboard/ver/orden/compra/normal/4 y http://127.0.0.1:8080/dashboard/ver/orden/compra/eliminada/1. IDs no revalidados en DB en esta ejecucion.

### GRE S305/306/307 - cierre de paridad

Create reutiliza informe completo y correccion UX con12 checks aislados; 9c26f05a..HEAD sin cambios runtime. No repetir QA emision/idempotencia. List/Show se contrastaron eb5ebbc3^/eb5ebbc3/HEAD: filtro/paginacion/export/consulta/documentos/navegacion permanecen. Cambios DEV ede0552/d9efd28 reemplazan payload/respuesta crudos por diagnostico seguro, fases de emision y guarda identity_conflict; lectores internos reemplazan links externos. Gates consult edit_referralGuide, PDF print/download y documentos download corresponden a rutas actuales, no QA gates.

Correccion explicita del checkpoint anterior: frmGreRecovery SI existe en shipping_guides._recovery, incluido por show linea46. No es handler huerfano. Conserva preview backend, permisos list+create+edit, estados REGISTERED/UNKNOWN/API_ERROR sin conflicto, version/CSRF, confirm_account y confirm_send opcional. Handler serialize->POST shipping_guides.recover con doble-envio protegido y recarga; no se ejecuto. La frase documental sobre un paquete anterior no instalado no prueba ausencia del contrato actual. No reabrir J/P. Observacion visual estatica: listado GRE aun usa botones individuales por fila; no perdida funcional.

### Caja S035/S039/S040 - cierre de paridad

My/Session: matriz anterior de apertura/sesion/ingreso/egreso/arqueo/cierre/regularizacion/documentos + diff80b240ae..HEAD. Origen consolidated/external, procedencia/observacion de apertura son campos DEV entregados y serializados; errores validation se muestran. deferred_cancelled impide regularizar solo movimientos cancelados; coincide con CashRegularizationService y respuesta controller. Sin otro cambio de permisos/endpoints/payloads operativos. Summary: diff483a7858^/483a7858 es presentacion; filtros GET branch/period/fechas, tabs/saldos/actividad/drilldown preservados. No hay mutadores historicos que restaurar en esa superficie (C01).

### R8-B02 - perdida confirmada: Solicitar traslado desde Disponibilidad

Superficie S357: http://127.0.0.1:8080/dashboard/disponibilidad/almacenes. a90d322a elimina del renderer el anchor construido con warehouse.request_url; informe original dice deliberadamente no renderizarlo por TRANSFER MUTATION OUT OF SCOPE. Es navegacion GET, no ejecucion de traslado. No hay evidencia de retiro de producto. HEAD mantiene request_url y el formulario destino activo.

Autoridad actual: app/Services/Inventory/WarehouseAvailabilityService.php:65-75. URL solo si available>0, traceable, permisos list_transfer+create_transfer y destino del local distinto del origen. Incluye source_warehouse_id, stock_item_id, form_tenant_id/company_id/branch_id. No replicar condiciones en JS; no fabricar URL cuando backend devuelve null. Create consume prefill y valida snapshots. No se pierde el CRUD de traslados completo: se pierde el acceso contextual con producto/origen preseleccionados.

Clasificacion: ACTIVE - FRONTEND RECOVERY REQUIRED; causa Next/read-only QA mal interpretado; backend blocker nuevo NO. Una capacidad confirmada, no dos por enlace/prefill. S357 audit COMPLETE WITH FINDING, no functional parity PASS. Recuperacion solo tras revision del propietario en R8.2. No ejecutar solicitud/reserva/preparacion/despacho/recepcion. Los FR107 existentes permanecen sin renumerar; este candidato R8-B02 aun no se incorpora como FR recuperado.

### Inventario S135/S170/S358-360

Kardex y Variants: informes especificos + diff historico; diez columnas/query StockItem+warehouse y seis columnas/imagen/modal/search/paginacion respectivamente preservadas. Escapado, errores y anulacion de respuestas antiguas no retiran capacidades; cero monetario ahora visible, sin recalculo. No nuevo export/operacion historica demostrada. Runtime sin cambios posteriores. No reabrir provision/certificacion backend Kardex.

Transfers: diff087f9311^/HEAD y matriz estado/acciones. Create mantiene request_key, contexto, productos, cantidades, notes y prefill; disabled sin destinos ya existia antes de Next. Index q/status/paginacion y Ver. Show preserva prepare/dispatch/receive/cancel, predicates actions+estado, receiptKey, limites de recepcion, notes/reason e historial; agrega trazabilidad. JS solo adapta Escape/portal de menu. No cambios posteriores. No homologar ni ejecutar operaciones.

### RRHH - 24 cierres por familia

S008-010,049-052,129-131,224-227,283-285,290-292,370-373. Comparacion6d8652a9^/6d8652a9/HEAD y HR BASIC MAINTAINERS. Artefacto r8-hr-semantic-diff.txt preserva diff completo; extraccion de atributos es apoyo, no criterio de cierre aislado. Se interpretaron campos, defaults/old(), CSRF/hidden/form/data-url, registros de eventos y FormData. Sin cambios posteriores.

Forms conservan descripcion/nombre, fechas d/m/Y+year, percentage, reason/days; old conserva default original. Datatables cambia renderer/escape/menu, no rutas/payloads/busqueda/paginacion. Habilitar usa data-delete existente; semantica enable distinta de SoftDeletes preservada. Generar feriados permanece POST, sin ejecutarlo. Nuevos @can corresponden a permisos reales de rutas. Link Crear en Pension inhabilitados antes apuntaba erroneamente a WorkFunction; ahora apunta a Pension, no se cuenta como perdida de Cargos. Relationship deleted sigue C/HR-02; no se hereda cierre del CRUD. Global legacy y QA mutadora pendiente no se confunden con aislamiento ni perdida Next.

### Catalogos de finanzas/compras - 15 cierres adicionales

Bill, CategoryInvoice, PaymentDeadline, PorcentagesQuote y Quality: respectivos commits9670a98b/651667fa/b3a670b6/06533c55/d287d4ca, padre/homologacion/HEAD, informes individuales y artefacto r8-finance-catalog-diffs.json. Sin cambios runtime posteriores. No se cerraron por numero de controles: diferencias de permisos, campos y semantica interpretadas.

Bill conserva description, cancel reset, hidden bill_id, FormData y hard delete protegido; CategoryInvoice name/description/category_id y modal individual. PaymentDeadline conserva description/days/type/credit/is_active, seleccion false0 correcta y update que permite activar; desactivar solo activos sigue contrato previo. Texto de plazo ya utilizado informa restriccion backend, sin disabled adicional. Porcentajes mantiene name/value y min/step, reserva de nombres utility/rent/letter/igv exactamente como antes. No convertirlo en regla fiscal nueva.

Quality exige distinguir correccion de cross-domain de perdida legitima: baseline SI tenia checkboxes/seleccionar todos y boton bulk, pero enviaba IDs Quality a talla/delete-multiple. No era un bulk Quality autorizado. La homologacion corrige create/edit/back que apuntaban a Talla y conserva eliminacion individual quality_id. No reactivar endpoint ajeno ni inventar bulk Quality. Si Producto requiere bulk propio, sera contrato nuevo, no recuperacion segura de ese cableado. Formularios no agregan reglas fiscales ni ocultan guardado por QA pendiente. Gate crear/modal Bill corresponde a permiso existente; acciones index preservan permisos entregados. Deudas global legacy, invalid-ID y mutaciones no se certifican de nuevo.

Estado tras este bloque:131/194 auditadas,63 pendientes;51 cierres adicionales (1 PREVIOUS/50 NEW). R8-B02 sigue unica perdida funcional nueva confirmada. R8-A13 queda pendiente de decision sobre representacion, no se suma como perdida legitimada ni como cierre.

### R8-E02 - perdida parcial confirmada: paginacion Daily Profit

S120 ganancia.index y S121 ganancia.indexDetail comparten public/js/ganancias/index.js. bd413c65 intento corregir el acceso a ultima pagina, pero tambien sustituyo el argumento i DENTRO del bucle de paginas por pagination.totalPages (linea144 actual). HEAD lo conserva. Con10 paginas, currentPage4: baseline emitia [1,2,3,4,5,6,7] (ultimo atajo historico tambien tenia bug); HEAD emite [1,10,10,10,10,10,10]. No declarar baseline perfecto: se perdieron los enlaces locales validos mientras se corregia el atajo final. Anterior/Siguiente siguen intactos; perdida parcial, una raiz compartida, no dos findings ni backend blocker.

Reproduccion aislada: se evalua el JS real baseline/HEAD con Node vm; renderItemPage captura argumentos, jQuery/DOM minimo simulado, sin ejecutar ready ni requests. Casos pagina1/4/8 y totalPages10. Resultado en r8-daily-pagination-isolated.json, identificado SIMULATED ISOLATED RENDER; NO HTTP/DB. No se fabricaron summaries ni se recalculo nada. Afecta listado y detalle cuando exista mas de una pagina; se valida algoritmo, no disponibilidad del dataset actual.

URL principal http://127.0.0.1:8080/dashboard/listado/ganancias/diarias. Detalle historico documentado http://127.0.0.1:8080/dashboard/listado/ganancia/detalles/2 (ID no revalidado ahora). Listado conserva datos/paginacion API y navegacion detalle; detalle fue creado durante homologacion porque Blade faltaba. Cierre de auditoria con finding, NO paridad funcional PASS. Recuperacion candidata exclusivamente frontend en R8.2, no corregida ahora.

### User Profit y Metas - seis cierres sin perdida

S122/123: informe USER-PROFIT y diff8349ba11^/HEAD. creator/startDate/endDate, Excel normal/detallado y sus gates/handlers, print_url/detail_url, null cost y actor separado de Worker permanecen. Cambio a menu no elimina acciones. Totales vacios ahora muestran contrato backend; formateo no recalcula utilidad. Detalle agrega cantidad/worker y conserva report->line/costo/ganancia, no formulario mutador historico. No repetir certificacion documental/export.

S179-182: informe GOALS y4a8e796a^/HEAD. Nombres/importe/frecuencia/periodo/users[], agregar todos/quitar, snapshots y CSRF, PUT y navegacion/ranking/progreso preservados; IDs worker-* siguen transportando User por contrato. Cambios de labels/layout no alteran JS inline. Runtime sin diferencias posteriores. No reejecutar las6 pruebas/62 assertions ni QA Metas: evidencia previa reutilizada como soporte y nueva comparacion estatica solamente.

## Checkpoint final de esta ejecucion

A139/194 (71.65%), adicionales59;55 pendientes. Acumulado40 PREVIOUS/99 NEW. Findings nuevos: R8-B02 perdida de navegacion completa1; R8-E02 perdida parcial de paginacion1; ambos frontend, blockers DEV nuevos0. No sumarlos como FR recuperados; FR107 mantiene74/10/23 y hay2 candidatos R8 separados. R8-A13 requiere decision sobre representacion, aun pendiente en S201/203.

Continuar por S157/S163/S171/S172/S118/S062 (Material/catalogo/configuracion y habilitacion company), luego S058-061/S319 (cuentas/settings), S081/S095/S109 y restantes administracion/plataforma/usuarios segun ledger. No repetir las59 filas cerradas ni ejecutar sus mutaciones. Para S201/203 obtener decision/contrato autorizado de subtotal/precio neto antes de cerrar; no bloquear otras familias. R8.0/R8.1 PARTIAL; R8.2 requiere revision del propietario, NO INICIADO.

Verificacion final de esta continuacion: git diff --check PASS;379 IDs/orden/universos preservados; tabla canonica379 filas y11 columnas consistente con ledger;80 cierres anteriores preservados. Solo estos3 documentos cambian en Git; artefactos de auditoria externos actualizados. Backend/Blade/JS/CSS/rutas/allowlist/infraestructura y mutaciones comerciales0. Sin commit, push, fetch, pull ni merge. Evidencia reproducible: r8-continuation-079aea9-verification.json y r8-daily-pagination-isolated.json.
