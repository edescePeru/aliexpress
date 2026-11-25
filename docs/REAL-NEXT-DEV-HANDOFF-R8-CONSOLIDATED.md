# REAL NEXT — R8.3 — Consolidated DEV Handoff

## A. Resumen ejecutivo

Fecha de corte: **2026-11-25 (America/Lima)**. Repositorio `aliexpress`, rama `feature/venti-next-frontend`. Checkpoint compartido **6df57e7**; HEAD local **af0c127032e9f143e16032f650dfb5beb06c5f50**. Working tree inicial limpio. No fetch/pull/merge/rebase ni inspección de la rama de trabajo paralela DEV. Las conclusiones describen exclusivamente el código integrado local y la evidencia entregada; no afirman que DEV aún no haya corregido algo después del checkpoint.

R8.1: 194/194 superficies Next auditadas. R8.2A–D: FR-108/109 recuperadas, Recepción homologada, Registrar comprobante homologado en tres variantes y acciones Branch/GRE homologadas. **76 recuperadas + 11 bloqueadas + 23 excluidas = 110; pérdidas confirmadas pendientes de recuperación directa frontend: 0.** Homologación visual no incrementa FR ni certifica mutaciones.

Esta entrega es documental: lectura de fuentes y contraste estático acotado de los residuales contra HEAD. No nuevos tests, consultas DB, HTTP, exports, documentos, comandos de negocio, cambios de infraestructura ni acceso al escritorio. Se conserva evidencia anterior sin presentar pruebas DEV como ejecutadas aquí. PHP contractual: `C:\wamp64\bin\php\php7.3.33\php.exe`; URL base: `http://127.0.0.1:8080`.

Las tablas históricas del backlog contienen estados sustituidos por addenda posteriores. La matriz de conciliación E es el corte vigente de este handoff. Las prioridades de FR antiguas no se suman a las prioridades de trabajo siguientes. No se asigna P0 nuevo sin evidencia suficiente de emergencia operativa; P1 incluye riesgos de autorización e integridad demostrados estáticamente/por pruebas previas, no una explotación productiva afirmada.

### Fuentes y precedencia

1. `AGENTS.md` y decisiones del propietario: conservación funcional, autoridad DEV, exclusiones, J/P/K y limitación local documental.
2. Código HEAD, `REAL-NEXT-DEV-DELIVERY-INTEGRATION-2026-11-25.md` y `REAL-NEXT-DEV-DELIVERY-RECONCILIATION-2026-11-25.md` (DEV 41ed99f/0129133; merge 98619bc).
3. `REAL-NEXT-DEV-TEAM-FINDINGS-BACKLOG.md`, `REAL-NEXT-FUNCTIONAL-PARITY-RECOVERY-BACKLOG.md`, `REAL-NEXT-DEV-PARALLEL-WORK-2026-11-22.md`: registros históricos, no órdenes de reimplementar correctivos ya integrados.
4. Inventario/auditoría/plan R8 y `REAL-NEXT-R8-FUNCTIONAL-PARITY-RECOVERY.md`; informes R4/R6/R7/R76 y homologaciones R7.7/R8.2B–D citados por capacidad.
5. `docs/flujos/AUDITOR-CIERRE-AGRUPADO-2026-11-24.md`, `AUDITOR-J-P-K-CIERRE-NEGOCIO.md`, `AUDITOR-MAT-01-DECISION.md`, `AUDITOR-CASH-02-03-04.md` y series `auditoria-qa-01…04`: pruebas DEV, alcance y límites declarados.

## B. Backlog técnico accionable

Los códigos B1…D5 son secciones de entrega, **no nuevos finding IDs**. Para cada aceptación, devolver SHA, test/resultado y evidencia negativa además del positivo. Las URLs son localizadores, no autorización de ejecución. Los IDs ejemplificados se documentaron antes; esta fase no revalidó su existencia en DB. Un parámetro `{id}` se conserva explícito cuando no existe un registro autorizado verificado para el caso.

### B1 — ENTRY-R75-01 — P1 — Editor de entrada de compra

- **URL/pantalla:** http://127.0.0.1:8080/dashboard/entrada/compra/editar/1 (ejemplo histórico contextual); origen http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2. FR-047.
- **HEAD:** `app/Services/Inventory/PurchaseFormReadAccess.php:14`, `PERMISSIONS['entry_edit']='edit_entryPurchase'`; `authorize()` exige ese permiso. `EntryController::editEntryPurchase` llama `entryForEdit`; `routes/web.php:1150` exige `update_entryPurchase`, igual que el catálogo constatado en R7.5.
- **Actual/raíz:** un editor legítimo con update pasa la ruta y falla en el servicio/preload. El antiguo defecto finance/tipo/parent NO es la causa actual: `entryForEdit` ya exige T/C, finance=false, Por compra y referencias físicas coherentes.
- **Esperado:** ruta, consumer, servicio y catálogo usan la misma capacidad efectiva; preferir el contrato existente update_entryPurchase, sin permiso paralelo ni bypass Owner/frontend. Conservar contexto y ownership.
- **Aceptación:** update-only accede a propio y lookups; sin permiso403; otro tenant/company, financiero, otro tipo o inexistente falla cerrado; GET sin negocio. Guardado queda bajo su permiso actual. Evidencia: `DevDeliveryIntegrationTest` de caracterización y reconciliación R7.5; devolverlo convertido a aceptación, contratos de consumer y rutas afectados.
- **Desbloqueo:** restaurar navegación/formulario Edit, no certificar persistencia por abrir GET.

### B2 — ENTRY-R4-01 — P1 — Frontera de reversión de compras

- **URL:** http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2. FR-048.
- **Endpoint/cadena:** POST `/dashboard/entry_purchase/destroy/{entry}` (`destroy_entryPurchase`) → `EntryController::destroyEntryPurchase:2632` → `app/Services/Inventory/EntryDeletionService::delete:14` → `EntryStockReversalService::reverse` (límites de tenant/company y entry_type en líneas 17–21). Incluir eliminación de líneas que comparte la autoridad.
- **Actual:** falta exigir finance=false. Entry financiera Por compra sin detalles puede alcanzar borrado del encabezado, sin la capacidad destroy_invoice ni la reversión financiera de Invoice. No se ejecutó ese caso contra datos compartidos.
- **Preservar:** locks/rollback, crédito outstanding sin pagos/adelantos, coherencia orden/proveedor/moneda, cantidad restante original, reservas, referencias de traslado/salida/cotización, trazabilidad Item/lote/nivel y resumen de recepción. No eliminar restricciones para conseguir un positivo.
- **Esperado/aceptación:** rechazar finance=true con/sin detalles antes de cambiar Entry/DetailEntry/CashMovement/crédito/stock; rechazar contexto/tipo ajeno e inexistente. Compra no financiera elegible mantiene reversión atómica; fallo inducido no deja cambios parciales. Devolver pruebas de ambas rutas (cabecera/línea), autorización y rollback. Riesgo: integridad financiera e inventario; ocultar el botón no protege endpoint.

### B3 — INV-01 residual: ajuste StockItem — P1

- **URL:** http://127.0.0.1:8080/dashboard/stock-items. FR-059.
- **HEAD:** `InventoryMigrationController::adjustStockItemStockOut:891`, `adjustStockItemStockOutLogic:917`; POST `/dashboard/fix/stock-item/output-adjustment`, `routes/web.php:3781`. No capability específica en esa ruta ni autoridad contextual equivalente a ScreenAccess/snapshot. Selección de lotes/Items/niveles no establece frontera completa company/local.
- **Actual:** salida de stock con transacción/locks, disponible menos reservado y creación Output/OutputDetail; TenantScope no prueba autorización company/warehouse/location. Idempotencia no certificada; comprobar retry y vocabulario scrapped/scraped sin inventar semántica.
- **Esperado:** capacidad efectiva ajustarStock_material (o contrato DEV explícito equivalente), binding contextual de StockItem habilitado y almacén/local/general autorizado, identidad de lote/Item/nivel coherente, preservación de reservas, trazabilidad y costo bajo autoridad moderna.
- **Aceptación:** sin permiso403; foreign tenant/company/branch/warehouse/location y snapshots alterados rechazados antes de escribir; cantidad/reservas inválidas sin cambios; fallo rollback; reintento no duplica salidas según contrato de idempotencia definido. Devolver pruebas aisladas y contrato de payload/errores. No recuperar el ajuste usando filtros JS.
- **Límite:** no reabrir MAT-01/03/04/05, lectores LegacyItemReadAccess, Kardex ni flags/min-max/código físico ya recuperados. Evidencia R6 y reconciliación R7.5; no nuevo INV duplicado.

### B4 — INV-01 + METRIC-01: lectura y exports de dashboard — P1

- **URL común:** http://127.0.0.1:8080/dashboard/principal. Es ruta compartida: Owner conserva dashboard.owner, operador dashboard.dashboard. No restaurar el antiguo dashboard al Owner para mostrar estos controles.
- **HEAD:** `ReportController::amountInWarehouse:32`, `excelAmountStock:93`, `excelBDMaterials:156`, `excelBDMaterialsByWarehouse:301`; `HomeController:74` usa Warehouse::all. `routes/web.php:1700–1704` no declara capacidad granular.
- **Endpoints:** GET `/dashboard/report/amount/items` (FR-102), `/dashboard/report/excel/amount/stock` (FR-063), `/dashboard/report/excel/bd/materials` (FR-064), `/dashboard/report/excel/bd/materials/warehouse/{id}` (FR-103).
- **Raíz:** métricas/exports todavía usan autoridad legacy sin scope company uniforme; Warehouse por ID/lookup global no representa autorización. No aplicar el fix sólo al dropdown de almacenes.
- **Esperado:** fuente StockItem/InventoryLevel/servicio de costo autorizada por DEV, scope T/C y definición explícita branch/general por reporte; permiso funcional tanto GET datos como export, no sólo showBoxAlmacen_dashboard visual.
- **Aceptación:** mismo filtro/lista de IDs y moneda entre consulta/export; no mezcla de contextos; permiso ausente403; almacén ajeno/inexistente y contexto ausente fallan cerrado; stock reservado/disponible y costos sin dato conservan significado. No inventar tasas/fórmulas. Devolver matriz de autoridad, permiso, DTO y ejemplos sanitizados; después frontend recupera cuatro capacidades con un correctivo compartido.

### B5 — METRIC-01 + DASH-R7-01: rotación y personal — P1

- **URL:** http://127.0.0.1:8080/dashboard/principal. FR-105/106/107.
- **HEAD/contratos:** `RotationMaterialController::getDataRotations:476`, GET `/dashboard/get/data/rotations/v2/{page}` (FR-105) lee historial/usuario sin permiso/scope homogéneo. `storeRotationMaterial:246`, GET `/dashboard/store/rotation/material/` (FR-106), actualiza Material.rotation/rotation_value en 450–451 y crea RotationMaterial antes del commit. `PersonalPaymentController::getPersonalPaymentByMonth:66`, GET `/dashboard/personal/payments?year=2026&month=11` (FR-107), crea/actualiza SueldoMensual en 314–331 y consulta tipo de cambio. Rutas 2954/3172/3174.
- **Raíz:** GET con escrituras de negocio, no Audit técnico; autorización/contexto METRIC-01 independientes del cambio de verbo. Esta entrega no ejecuta ninguno.
- **Esperado/aceptación:** separar lectura pura y comandos autorizados con método/CSRF apropiados; GET no cambia tablas; permiso ausente403; company/tenant/actor e historial coherentes; repetición/idempotencia del comando definida; rollback y proveedor simulado. Preservar reglas de cálculo o declarar cambio intencional a Producto. Devolver DTO de consulta, endpoints de comando y pruebas antes/después de tablas. No reabrir Metas ni Financial Summary contextual ya cerrados.

### B6 — EXPENSE-01 — P1 — Ownership de Rendición y retorno FR-110

- **Origen:** http://127.0.0.1:8080/dashboard/crear/gasto. **Destino histórico exacto:** http://127.0.0.1:8080/dashboard/rendicion/gastos/general (`expense.index`). Commit d43ca2f7 retiró ese enlace.
- **HEAD:** `app/Expense.php` no declara ownership tenant/company; `ExpenseController::index:19`, `getAllExpenses:113`, GET `/dashboard/all/expenses`, rutas 2896–2900 con list_expense. Lookup Worker contextual no filtra por sí solo el parent Expense. Create/edit/destroy/report comparten la deuda histórica.
- **Esperado:** autoridad backend explícita para lectura/listado y referencias; definir tratamiento de históricos sin ownership sin asignarlos arbitrariamente. Permisos no sustituyen contexto. No confundir Bill con Expense.
- **Aceptación de desbloqueo FR:** listado y JSON propios, IDs completos; ajenos no aparecen; contexto ausente/inválido y referencias manipuladas fallan cerrado; list_expense efectivo. DEV declara alcance histórico/branch si aplica. Después se restaura exclusivamente el enlace original bajo permiso, sin ruta alternativa. El resto de mutaciones se certifica aparte, sin suponer que QA de navegación las cubre.

### B7 — FIN-03 — P1 — Auxiliar de Invoice

- **URL/pantalla:** http://127.0.0.1:8080/dashboard/factura/compra. GET `/dashboard/get/invoice/by/id/{id}`, `routes/web.php:1688`, `InvoiceController::getInvoiceById:254`.
- **Actual:** filtro FinanceInvoiceContext T/C presente, pero sin finance=true y sin permiso list_invoice específico en ruta. No equivale a `financeEntries()` usado por listados. El frontend usa details del DTO autorizado y no debe volver al auxiliar inseguro.
- **Esperado/aceptación:** permiso list_invoice y autoridad financiera consistente; ID no financiero/ajeno/inexistente rechazado sin serializar datos; sin permiso403; propio financiero permitido. Devolver consumidores confirmados o retiro autorizado del auxiliar, no borrarlo unilateralmente.
- **Precisión documental:** este residual es autorización/selección del registro de Invoice; no prueba un defecto de los lectores de archivo DEV. Archivos Invoice/Entry se atienden por DOC-01/despliegue. `getServiceById` y OrderService permanecen fuera de recuperación; OrderPurchaseFinance retirado no cierra FIN-03. FR nuevos: 0.

### B8 — TRANSFER-01 — P1 — Elegibilidad en escritura

- **URL:** http://127.0.0.1:8080/dashboard/traslados/almacenes. `app/Transfers/WarehouseTransferLine::initializeNewRecord`, `app/Services/Transfers/WarehouseTransferRequestService` y `WarehouseTransferPreparationService::prepare`; operaciones POST de warehouseTransfer.
- **Actual:** comprobación activa/habilitada por company sin tracks_inventory equivalente a WarehouseAvailabilityService. La prueba histórica WarehouseTransferClosureTest demuestra solicitud y reserva de producto no inventariable; HEAD conserva ese contraste. Las suites QA-03 positivas no son evidencia de cierre de este caso negativo.
- **Esperado:** elegibilidad coherente al solicitar y preparar; definir explícitamente tratamiento de stock residual, sin ampliar catálogo en frontend ni revertir política MAT-01.
- **Aceptación:** flag falso inicial y cambio entre solicitud/preparación rechazados o tratados según contrato aprobado, sin reserva parcial; verdadero conserva flujo/contexto; rollback. No nuevo FR, no reejecución aquí. Riesgo inventario justifica P1 propuesto frente al P2 histórico.

### B9 — PERCENT-01 — P2 — Claves reservadas

- `PorcentageQuoteController::destroy:76`, `DeletePorcentageQuoteRequest`: sólo required/exists y delete físico; POST `/dashboard/porcentages/destroy`. URL de catálogo: http://127.0.0.1:8080/dashboard/porcentajes/cotizaciones (`porcentageQuote.index`; no se ejecuta aquí).
- La exclusión JS de utility/rent/letter/igv no protege POST. Agregar garantía backend equivalente; keys reservadas rechazo sin cambios, otras elegibles según permiso, inexistente/rollback probados. No asumir que pausa industrial retira automáticamente el catálogo compartido. No nuevo FR ni modificación de porcentajes.

### B10 — WAREHOUSE-01 — P2 — Alta legacy sin company

- URL localizadora http://127.0.0.1:8080/dashboard/ver/almacenes/{area}, requiere Area propia real. `WarehouseController::store:37`, POST `/dashboard/warehouse/store`, crea name/comment/area_id sin company; BelongsToTenant no completa company. Schema NOT NULL sin default documentado en auditoría Warehouse; no se reconsultó DB ahora.
- Resolver autoridad contextual o proponer retiro explícito del camino legacy y sus consumers. No reconstruir jerarquía Area como authority moderna. Aceptación: propio crea con company autorizada en copia aislada, parent ajeno rechazado, sin contexto falla cerrado; o evidencia del bloqueo/reemplazo aprobado. No inventar company por frontend.
- ManagedWarehouse ya integrado asigna contexto; no hereda este defecto. INV-01 de estructura/parent y WAREHOUSE-01 no son dos tickets por el mismo lookup. Sin FR nuevo.

### B11 — WAREHOUSE-02 — P2 — Longitud validada frente a schema

- URL moderna: http://127.0.0.1:8080/dashboard/configuracion/empresas/1/editar (sección almacenes). `ManagedWarehouseController::store:20–21`, `StoreWarehouseRequest`/`UpdateWarehouseRequest`: name/comment max255. POST configuración empresas/{companyId}/almacenes y legacy warehouse/store/update.
- Evidencia histórica schema real VARCHAR191; HEAD conserva max255. No afirmar una nueva lectura de schema ni proponer migración sin verificar destino.
- DEV alinea límites Request/UI/schema según contrato elegido, sin truncamiento silencioso. Devolver schema destino y casos 191/192/255/256, error de validación controlado/rollback, tanto managed como legacy alcanzable. El riesgo moderno sigue aunque no se modernice Area.

### B12 — SALES-NEW-01 — P2 — Metadata de página fuera de rango

- URL http://127.0.0.1:8080/dashboard/listado/ventas. `PuntoVentaController::getSalesAdmin`, líneas 1702–1703: startRecord depende del total global, endRecord=min(total,page×size). Con total positivo y página fuera de rango produce start>end; el fix para total=0 no corrige ese caso.
- Esperado: contrato explícito para página vacía/fuera de rango (normalización o rango0/0), sin fingir errores como vacío. Devolver pruebas de primera/última/fuera de rango, filtros y total0, sin cambiar estrategia de paginación. No nuevo FR: UI ya está integrada. SALES-NEW-02 es validación contextual del listado de errores, separada en EVIDENCE REQUIRED, no otro arreglo de rangos.

### Trazabilidad de las once capacidades bloqueadas

Todas siguen BLOCKED según el corte local; no sumarlas como once tareas DEV.

| FR | Capacidad / pantalla y URL local | Raíz / trabajo | Contrato para desbloquear / evidencia | Prioridad |
|---|---|---|---|---|
| FR-047 | Editar entrada; http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2 → /dashboard/entrada/compra/editar/1 | ENTRY-R75-01 / B1 | Alinear update_entryPurchase en ruta/consumer; R7.5 + HEAD | P1 |
| FR-048 | Anular entrada; http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2 | ENTRY-R4-01 / B2 | Rechazo financiero antes de reversión/cabecera; R4 + servicios HEAD | P1 |
| FR-059 | Ajustar StockItem; http://127.0.0.1:8080/dashboard/stock-items | INV-01 / B3 | Permiso, binding T/C/almacén/lote, reservas/rollback/retry; R6 + HEAD | P1 |
| FR-063 | Exportar importes; http://127.0.0.1:8080/dashboard/principal | INV-01 + METRIC-01 / B4 | Authority/permisos/scope export; R7 + ReportController | P1 |
| FR-064 | Exportar materiales; http://127.0.0.1:8080/dashboard/principal | INV-01 + METRIC-01 / B4 | Misma autoridad de inventario; R7 + ReportController | P1 |
| FR-102 | Importes/cantidad y refrescar; http://127.0.0.1:8080/dashboard/principal | INV-01 + METRIC-01 / B4 | GET contextual autorizado y DTO definido; R7 | P1 |
| FR-103 | Export por almacén; http://127.0.0.1:8080/dashboard/principal | INV-01 + METRIC-01 / B4 | Warehouse ID contextual, no Warehouse::all; R7/HEAD | P1 |
| FR-105 | Historial rotación; http://127.0.0.1:8080/dashboard/principal | METRIC-01 / B5 | Historial/actor/contexto/permiso, lectura pura; R7 | P1 |
| FR-106 | Nuevo corte rotación; http://127.0.0.1:8080/dashboard/principal | DASH-R7-01 + METRIC-01 + INV-01 / B5 | Separar comando autorizado y lectura; R7/HEAD | P1 |
| FR-107 | Personal mensual/proyección; http://127.0.0.1:8080/dashboard/principal | DASH-R7-01 + METRIC-01 / B5 | GET sin SueldoMensual writes, comando/contexto definidos; R7/HEAD | P1 |
| FR-110 | Retorno Crear gasto; http://127.0.0.1:8080/dashboard/crear/gasto | EXPENSE-01 / B6 | Destino expense.index/JSON contextual seguro; R8-F01/R8.2A | P1 |

Son **seis raíces canónicas** para once FR: ENTRY-R75-01, ENTRY-R4-01, INV-01, METRIC-01, DASH-R7-01 y EXPENSE-01. FR-104 Exportar entradas ya recuperado, no es un octavo pendiente Dashboard; quedan exactamente siete: 063/064/102/103/105/106/107.

## C. Consultas contractuales

### C1 — R8-A13 / S201–S203 — P1 — Contrato monetario independiente

Localizadores: http://127.0.0.1:8080/dashboard/ver/orden/compra/eliminada/{id} y http://127.0.0.1:8080/dashboard/ver/orden/compra/normal/{id}. Requieren orden autorizada del estado adecuado; no se inventa ID de detalle eliminado.

Evidencia: R8-A13 en auditoría R8; históricos abc9daa^/c32352c5^ mostraban price/1.18 y order.total-order.igv. `showNormal.js` no sobrescribe necesariamente subtotal al cargar. DTO actual expone price/igv/total_detail y total/igv/currency; no garantiza bases/tasa snapshot para todo histórico. La fórmula de writers actuales no acredita el significado de todas las filas almacenadas.

DEV debe devolver un contrato versionado que responda:

1. ¿price es precio bruto o neto y por unidad de qué presentación? ¿Puede variar históricamente?
2. ¿total_detail/subtotal incluyen IGV o descuentos? ¿Descuento de línea/global y redondeo se aplican antes/después de impuesto?
3. ¿Qué campos identifican gravada/exonerada/inafecta y cuál es la tasa propia del registro, no la tasa actual del Material/configuración?
4. ¿total-igv es subtotal neto autorizado en todos los casos? ¿Cómo tratar null, históricos, importaciones y cambios de moneda/tasa?
5. ¿Qué campos netos/snapshots deben presentar ambas vistas? Cuando no pueda reconstruirse, ¿qué estado de indisponibilidad debe mostrar UI?

Entregar ejemplos sanitizados con valores persistidos y resultado esperado: gravada, exonerada, inafecta, descuento, redondeo, PEN/USD e histórico sin snapshot. Aceptación: ambos detalles muestran importes conforme al contrato, sin división universal por1.18, sin recalcular desde impuestos actuales ni mezclar monedas. **No FR nuevo ni pérdida confirmada; no mutación ni cambio frontend autorizado por esta consulta.**

### C2 — P2 — Decisiones y contratos históricos restantes

Una respuesta por ID, no un nuevo ticket general: PRICE-05 diagnóstico de oferta ausente/moneda incompatible; PRICE-06 precondición company/branch de nuevos callers del resolver (POS protegido no se declara inseguro); PRICE-07 configuración monetaria autorizada; BANK-02 semántica default inactivo; QUOTE-03 snapshots fiscales históricos; PUBLIC-03/04/05 publicación/disponibilidad, precio/presentación y home/branding. Conservar evidencia histórica y pedir decisión/contrato actualizado, no afirmar que DEV carece de él en su rama.

Aceptación: ejemplos, invariantes, consumers y tratamiento de históricos/ausencia explícitos; ningún backfill ni cambio fiscal inventado. Store Web/Universe C no se reauditan ni se deciden en R8.3. MAT-01 y CASH-02/03/04 **no** pertenecen a esta cola: decisiones ya entregadas/implementadas.

| ID | Referencia contractual a responder | Evidencia requerida para cerrar la consulta |
|---|---|---|
| PRICE-05 | `BranchPresentationService::dto`, ausencia de oferta vs moneda incompatible | Confirmar si configured=false/price=null es suficiente o publicar motivo explícito sin precio ficticio |
| PRICE-06 | `PriceResolverService::resolveUsingPriceList` y `BranchPriceListService`, precondición de callers | Enumerar callers autorizados y quién garantiza company/branch; no cambiar un resolver genérico sin mapa |
| PRICE-07 | Configuración base_currency de Company y lista efectiva Branch | Decisión de configuración monetaria, no conversión automática; dataset histórico no es evidencia actual |
| BANK-02 | `CompanyBankAccountController::update/toggleStatus` | Definir default inactivo: histórico admisible o invariant activo; negativo y transición representativos |
| QUOTE-03 | `QuoteSaleController::show`, moneda/tasa histórica | Campos snapshot/autoritativos y ausencia explícita; no usar tasa actual como prueba del pasado |
| PUBLIC-03/04/05 | `PublicCatalog`, `PublicCatalogController` y contratos SW existentes | Publicabilidad/locales, precio/opción/moneda y editorial/branding; devolver definición vigente, sin promover fixtures |

Ubicaciones de interfaz para triage, sin navegar en esta fase: http://127.0.0.1:8080/dashboard/cotizaciones/venta (QUOTE), http://127.0.0.1:8080/dashboard/configuracion/empresas (Company/Branch) y http://127.0.0.1:8080/store-web/catalogo (PUBLIC; su bridge local no certifica publicación anónima).

## D. QA y despliegue: no confundir con defectos

### D1 — DOC-01 — P1 — OPEN — DEPLOYMENT VALIDATION

Lectores autorizados implementados y ocho controles FR049/054/083/088/093/096/097/098 recuperados en R7.6.1. URLs de revisión: http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2 y http://127.0.0.1:8080/dashboard/factura/compra; sin descargar documentos aquí.

`public/.htaccess` y reglas por familia forman parte del contrato DEV. `artisan serve` usa server.php y no interpreta .htaccess: ruta autorizada y URL física pueden compartir path sin que eso certifique protección. Apache requiere DocumentRoot public/AllowOverride/mod_rewrite correctos. **Nginx requiere reglas propias equivalentes**; .htaccess no se aplica allí. No modificar servidor local, hosts, APP_URL ni puertos.

Para despliegue autorizado: archivo sintético por familia, acceso HTTP directo anónimo/ajeno denegado, lector propio permitido y sin permiso/contexto ajeno denegado, sin fallback público; registrar configuración efectiva y resultados sanitizados, limpiar fixture. No usar archivos comerciales ni presentar esta tarea como reapertura de J/P/K o requisito para ocultar controles ya recuperados. FIN-03 queda separado. No cierre integral de todo public ni de escritores no examinados.

### D2 — QA-02 — P2 — OPEN — MUTATION QA

DEV ya reportó suites privadas SQLite sobre código instalado: plazos/categorías, categorías financieras/impuestos/cuentas, settings/archivos, ConfigUserWeb, parámetros/materiales/subcategorías y avatar. Fuentes: `docs/flujos/auditoria-qa-02-{plazos-categorias,mantenedores-agrupados,settings-usuarios,usuarios-materiales,avatar-parametros,validacion-agrupada}.md`. No solicitar reimplementar esos fixes ni afirmar haber ejecutado sus suites privadas.

Evidencia frontend previa: R5 26 tests/325 assertions aisladas; R7.5 71/832 aisladas; R7.7/R8 wiring con requests interceptados. Esto no certifica persistencia HTTP real ni concurrencia MySQL. Consolidar una sola matriz de huecos por familia: create/edit/status/default/delete/restore/reset/upload aplicables, autorizado/denegado, dependencia histórica, stale version, rollback, simultaneidad y contexto. Incluir CASH-06/07 y PRICE-09 sin tickets clonados.

Aceptación: copia descartable, versión/rol/contexto/método, resultados por caso y cleanup; defaults/cupos/borrados con conexiones MySQL independientes. Si falla un caso, asociar evidencia a su finding real; ausencia de test no es un bug. UI integrada permanece disponible. No mutations en base compartida ni fiscalidad real para cerrar QA.

### D3 — QA-03 — P2 — Ciclo Transfers pendiente

DEV reporta solicitudes58, preparación39, despacho/recepción49, disponibilidad30 comprobaciones SQLite en `auditoria-qa-03-traslados-ciclo.md`. Pendiente HTTP con permisos/contextos reales y MySQL entre conexiones: reserva concurrente, doble despacho/recepción/retry, conciliación lote/nivel/Items/Kardex. No confundir reintento secuencial con concurrencia. TRANSFER-01 B8 sigue negativo específico, no cerrado por esas suites.

### D4 — QA-01 / QA-04 / POS-01 / cobertura restante — P2

QA-01 DEV ya validó precisión, rangos/contextos y exports en fixtures; falta matriz integrada XLSX/roles/dataset de `auditoria-qa-01-validacion-final.md`. QA-04 ya amplió positivos restore/customer/supplier y paginación Yajra; falta HTTP/middleware/contextos y conjuntos no cubiertos. CUSTOMER-03/SUPPLIER-01 no vuelven a ser bugs de diseño por esa falta. POS-01: operación transaccional en copia de pruebas, separada de fachada. ENTRY-02/QUOTE-05 quedan como cobertura read-path limitada, no nuevas pérdidas.

Devolver evidencia del hueco concreto sin repetir lo validado ni reabrir J/P/K. EXT-01 no autoriza nuevas llamadas: GRE/proveedor excluidos por decisión de negocio; cualquier otro consumer requiere delimitar evidencia antes de pedir certificación. QA de archivos y datos debe usar muestras sintéticas/sanitizadas.

### D5 — P3 — EVIDENCE REQUIRED / reconciliación de datos

FIN-01, BANK-01, PRICE-08, PUBLIC-01, QUALITY-01 y DATA-01 conservan inventarios históricos, **no conteos actuales**. No corregir ni rellenar datos en función de esos números. Solicitar snapshot sanitizado reciente, origen, propuesta de tratamiento y autorización para backfill. FIN-02, MAT-02, BANK-03, AUTH-01 residual, QUOTE-02 residual, SALES-NEW-02 y PUBLIC-02 requieren delimitar consumers/evidencia no cubierta antes de proponer otro fix. Las familias tratadas no se reabren por falta de cierre global.

## E. Findings cerrados, conciliación completa y exclusiones

La matriz al final contiene todos los IDs canónicos extraídos de la tabla histórica y addenda narrativos. La unidad es finding, no FR, paquete DEV, vista o alias. Estado primario único; los límites de QA se conservan en la nota. RESOLVED BY DEV significa correctivo entregado dentro del alcance citado, no despliegue certificado. Ningún estado EVIDENCE REQUIRED afirma que el defecto persista en la rama paralela.

### Duplicaciones y precisiones resueltas

- BP-01/02/03 → PRICE-01/02/03; BP-04/05 → PRICE-05/06. No cinco tareas nuevas.
- SERVICE-01 → PUR-01; SERVICE-02 → USER-01; SERVICE-03 sí era finding propio. Fuera de recuperación legacy, sin afirmar reparación SQL.
- SW-01/04 → PUBLIC-02; SW-02 → PUBLIC-03; SW-03/05 → PUBLIC-04; SW-06/07 → PUBLIC-05. No añadir siete tickets.
- WT-03 deuda textual → QA-03; no es TRANSFER-01 de elegibilidad.
- R8-F01/FR-110 → EXPENSE-01. FR108/R8-B02 y FR109/R8-E02: RESOLVED BY FRONTEND, no solicitudes DEV.
- R8-A13/S201/S203: una consulta monetaria, cero FR nuevos.
- MAT-04 canónico = posiciones/multinivel; MAT-05 = flags. La tabla R7.5 invirtió títulos; conservar identidad/correctivos, no pedirlos otra vez.
- PUR-R3-01 tiene uso documental ambiguo: título original anulación Express vs fila R7.5 «faltantes Normal/General». **No declarar reparada la anulación Express por el fix missingCodes.** Express está pausado; caso original BUSINESS EXCLUDED. missingCodes Normal es evidencia de entrega Q, sin nuevo ID inventado. PUR-R3-02 print soft-deleted sí corregido y FR036 recuperado.
- FR047 aún citaba preload/finance legacy en su ficha individual: sustituir sólo su causa vigente por ENTRY-R75-01, preservando historia.
- DOC-02 limpieza global corregida; DOC-01 despliegue independiente. FIN-03 auxiliar Invoice independiente de ambos. QA-02 no se duplica por cada familia parcialmente cubierta.

### Exclusiones inalteradas

23 FR excluidos: 022–034, 037–045 y 080. No contar retiros como recuperaciones. Financial Purchase operativo retirado410; legacy access bloqueado423 y legacy settings retirado; Express/FreeSale/industrial/Output legacy pausados. General/OrderService/Proforma/Follow/Promotions fuera de recuperación; no asignarles410 por inferencia. SupplierCredit K fuera de uso temporal. Preservar código, datos y servicios compartidos.

J/P/K: CLOSED BY BUSINESS DECISION — TECHNICAL LIMITATIONS DOCUMENTED; no ejecutar ni pedir como condición de avance pruebas previamente excluidas. DOC-01 instalación se registra por mandato específico, no como reapertura de la certificación integral. No iniciar decisiones sobre C125.

## F. Orden de ejecución recomendado

| Orden / sección | Prioridad | Tipo | Desbloquea / aceptación resumida |
|---|---|---|---|
| B2 ENTRY-R4-01 | P1 | Backend | FR048; finance=true rechazado antes de reversión y rollback |
| B3 INV-01 ajuste | P1 | Backend | FR059; permiso/ownership/lotes/reservas/retry coherentes |
| B6 EXPENSE-01 | P1 | Backend | FR110; listado/JSON contextual permite volver al destino histórico |
| B7 FIN-03 | P1 | Backend | Auxiliar financiero seguro, sin nuevo FR; list_invoice + finance authority |
| B1 ENTRY-R75-01 | P1 | Backend aislado, paralelizable | FR047; misma capacidad efectiva en ruta/consumer/servicio |
| B4 INV-01 + METRIC-01 | P1 | Backend / contrato lectura | FR063/064/102/103; fuente y scope uniformes, negativos por almacén |
| B5 DASH-R7-01 + METRIC-01 | P1 | Backend / contrato comandos | FR105/106/107; GET puro y comandos autorizados |
| B8 TRANSFER-01 | P1 | Backend | Escritura coherente con elegibilidad; flag cambia entre fases |
| C1 R8-A13 | P1 | Consulta contractual, paralela | Dos detalles; ejemplos monetarios sin inferencias fiscales |
| D1 DOC-01 | P1 | Despliegue autorizado | Protección HTTP sintética equivalente, sin reabrir J/P |
| B9 PERCENT-01 | P2 | Backend | Protección de claves reservadas antes de borrar |
| B10 WAREHOUSE-01 | P2 | Backend / disposición legacy | Company autorizado o retiro explícito; no rescate UI Area |
| B11 WAREHOUSE-02 | P2 | Backend / schema contrato | Límites acordes a schema destino, sin truncamiento |
| B12 SALES-NEW-01 | P2 | Backend | Metadata válida fuera de rango |
| C2 contratos históricos | P2 | Consulta | Respuesta por ID con consumers/invariantes, no decisiones frontend |
| D2 QA-02 | P2 | Certificación pendiente | Persistencia/rollback/concurrencia sobre huecos de evidencia |
| D3 QA-03 | P2 | Certificación pendiente | Ciclo HTTP y simultaneidad MySQL; depende de B8 para ese negativo |
| D4 QA-01/04/POS y cobertura | P2 | Certificación pendiente | Matrices integradas acotadas, exclusiones preservadas |
| D5 evidencia/datos | P3 | Reconciliación | Datos/evidencia actuales antes de nuevos cambios o cierres |

**19 paquetes abiertos: P0=0 / P1=10 / P2=8 / P3=1.** Son paquetes heterogéneos (12 de backend, 2 de consulta, 5 de QA/despliegue/evidencia), no 19 bugs ni 19 FR. Doce raíces técnicas en B1–B12, considerando INV/METRIC compartidas; seis de ellas explican los once FR. No estimaciones de horas/fechas. La autorización contextual antecede a la recuperación frontend; QA depende del correctivo, pero las validaciones pendientes no justifican retirar otras capacidades legítimas.

## G. Contrato de devolución DEV → Frontend

Por entrega devolver:

1. SHA base y SHA final; indicar si parte de un checkpoint distinto a 6df57e7.
2. Finding IDs resueltos/parciales y FR desbloqueados; no cerrar familias por un único consumer.
3. Archivos/métodos modificados; contratos nuevos o preservados (rutas, verbo, payload, DTO, errores, idempotencia, ownership, autoridad monetaria/inventario).
4. Permisos afectados y compatibilidad con catálogo/roles/grants directos. Nada de seedear permisos de escape.
5. Pruebas ejecutadas: entorno/motor/versiones, casos positivos/negativos, assertions, resultados y artefactos sanitizados. Separar aislamiento de HTTP real, secuencial de concurrente, proveedor simulado de real.
6. Limitaciones no ejecutadas y defectos residuales. Si un hallazgo ya fue corregido en la rama paralela, enviar evidencia/commit; no repetir el correctivo por este corte local.
7. Migraciones/backfills/instalación requeridos, reversibilidad y tratamiento histórico; ninguna credencial/token/dato comercial sensible.
8. Decisiones intencionales de producto (retiro/pausa/sustitución) explícitas, no inferidas de botones ausentes o archivos borrados.

Frontend contrastará contratos y recuperará sólo las capacidades desbloqueadas tras revisión del propietario. No commit/push/merge en esta fase. **R8.3 COMPLETE — DOCUMENTAL; R8.4 no iniciada.**

Verificación de entrega: `git diff --check` PASS; únicamente este documento y las adendas de los dos backlogs canónicos. Implementación/backend/Blade/JS/CSS/allowlist/infraestructura: 0 cambios. Mutaciones comerciales, requests HTTP, proveedores y QA nueva: 0. Se verificó mecánicamente la unicidad de los84 IDs y la distribución de estados; no equivale a una suite funcional.

## H. Matriz individual de conciliación

**84 findings canónicos revisados** (sin aliases ni FR). Distribución primaria: BUSINESS EXCLUDED: 9; EVIDENCE REQUIRED: 10; OPEN — BACKEND BLOCKER: 10; OPEN — CONTRACT CLARIFICATION: 8; OPEN — DEPLOYMENT VALIDATION: 1; OPEN — MUTATION QA: 6; PARTIALLY RESOLVED: 12; RESOLVED BY DEV: 27; SUPERSEDED: 1. Los estados parciales conservan cierres por consumer; no se contabilizan como resueltos completos.

| Finding ID | Estado R8.3 | Evidencia / tratamiento / siguiente paso |
|---|---|---|
| AUTH-01 | PARTIALLY RESOLVED | Permisos efectivos en consumidores tratados / QuoteFormReadAccess corregidos. D5 exige delimitar residual antes de nuevo fix global; R8.2C conserva conversión y no certifica mutación. |
| BANK-01 | EVIDENCE REQUIRED | D5: evidencia de datos histórica; inventario actual/origen y autorización antes de backfill. No convertir cifras antiguas en dataset vigente ni afirmar corrección. |
| BANK-02 | OPEN — CONTRACT CLARIFICATION | C2: confirmar contrato vigente/ejemplos por ID. No decisión/frontend unilateral ni reauditoría Store Web; cifras y propuestas históricas no son datos actuales. |
| BANK-03 | PARTIALLY RESOLVED | CashConfigurationGuard protege/sincroniza moneda bancaria con caja usada en update; no acredita toda política de desactivación de cuenta vinculada. D5 pide evidencia/decisión residual, no reabrir CASH02. |
| CASH-01 | RESOLVED BY DEV | Fix acotado documentado en POST-MERGE-DEV-FIX-REVIEW 18/11; no reimplementar. Persistencia/cobertura restante separada en QA. |
| CASH-02 | RESOLVED BY DEV | CashConfigurationGuard + decisión AUDITOR-CASH-02-03-04; R7.5 y R7.7E. No conservar NEEDS BUSINESS DECISION histórico. QA real aparte D2. |
| CASH-03 | RESOLVED BY DEV | CashConfigurationGuard + decisión AUDITOR-CASH-02-03-04; R7.5 y R7.7E. No conservar NEEDS BUSINESS DECISION histórico. QA real aparte D2. |
| CASH-04 | RESOLVED BY DEV | CashConfigurationGuard + decisión AUDITOR-CASH-02-03-04; R7.5 y R7.7E. No conservar NEEDS BUSINESS DECISION histórico. QA real aparte D2. |
| CASH-05 | RESOLVED BY DEV | Fix acotado documentado en POST-MERGE-DEV-FIX-REVIEW 18/11; no reimplementar. Persistencia/cobertura restante separada en QA. |
| CASH-06 | OPEN — MUTATION QA | Pruebas aisladas/interceptadas previas no certifican persistencia HTTP ni simultaneidad MySQL. D2–D4 especifican huecos; no defecto nuevo por faltar QA. |
| CASH-07 | OPEN — MUTATION QA | Pruebas aisladas/interceptadas previas no certifican persistencia HTTP ni simultaneidad MySQL. D2–D4 especifican huecos; no defecto nuevo por faltar QA. |
| CUSTOMER-01 | RESOLVED BY DEV | Fix acotado documentado en POST-MERGE-DEV-FIX-REVIEW 18/11; no reimplementar. Persistencia/cobertura restante separada en QA. |
| CUSTOMER-02 | RESOLVED BY DEV | Fix acotado documentado en POST-MERGE-DEV-FIX-REVIEW 18/11; no reimplementar. Persistencia/cobertura restante separada en QA. |
| CUSTOMER-03 | RESOLVED BY DEV | Lifecycle selectivo y permiso separado list_supplierAccount en código; evidencia DEV auditoria-qa-04-restauracion-listados. Aceptación aislada reportada; cobertura HTTP global permanece D4. |
| DAILY-01 | BUSINESS EXCLUDED | Camino legacy/pausado fuera de recuperación según R7.5/AGENTS y decisiones vigentes. No significa código reparado ni autoriza borrarlo; PUR-R3-01 conserva ambigüedad nominal indicada en E. |
| DAILY-02 | RESOLVED BY DEV | Cierre contextual Daily Profit; recálculo/idempotencia/aislamiento previos. FR109 paginación es recuperación frontend distinta. |
| DASH-R7-01 | OPEN — BACKEND BLOCKER | Contraste HEAD y aceptación específica en B1/B2/B5/B6/B7; no ejecutar endpoints ni bypass frontend. |
| DATA-01 | EVIDENCE REQUIRED | D5: evidencia de datos histórica; inventario actual/origen y autorización antes de backfill. No convertir cifras antiguas en dataset vigente ni afirmar corrección. |
| DOC-01 | OPEN — DEPLOYMENT VALIDATION | Readers/controles recuperados. D1 mantiene instalación HTTP directa pendiente; artisan serve no aplica .htaccess, Nginx requiere reglas propias. No reapertura J/P. |
| DOC-02 | RESOLVED BY DEV | QuoteDocumentFiles elimina cleanup global; entrega P/R7.5. QA excluida J/P no se transforma en defecto abierto. |
| ENTRY-01 | RESOLVED BY DEV | Fix acotado documentado en POST-MERGE-DEV-FIX-REVIEW 18/11; no reimplementar. Persistencia/cobertura restante separada en QA. |
| ENTRY-02 | PARTIALLY RESOLVED | Cobertura previa/DEV aportada, no cierre integrado global. D4: read-path/exports/listados/restore con límites documentados; no volver a pedir fixes ya verificados. |
| ENTRY-R4-01 | OPEN — BACKEND BLOCKER | Contraste HEAD y aceptación específica en B1/B2/B5/B6/B7; no ejecutar endpoints ni bypass frontend. |
| ENTRY-R75-01 | OPEN — BACKEND BLOCKER | Contraste HEAD y aceptación específica en B1/B2/B5/B6/B7; no ejecutar endpoints ni bypass frontend. |
| EXPENSE-01 | OPEN — BACKEND BLOCKER | Contraste HEAD y aceptación específica en B1/B2/B5/B6/B7; no ejecutar endpoints ni bypass frontend. |
| EXT-01 | BUSINESS EXCLUDED | J/P y K cerrados por negocio con límites técnicos: no nuevas llamadas/certificación bloqueante. Otros consumers no certificados no se declaran resueltos ni se auditan en R8.3. |
| FIN-01 | EVIDENCE REQUIRED | D5: evidencia de datos histórica; inventario actual/origen y autorización antes de backfill. No convertir cifras antiguas en dataset vigente ni afirmar corrección. |
| FIN-02 | EVIDENCE REQUIRED | D5: residual histórico no cerrado por evidencia disponible; pedir consumer/contrato/prueba actual. No asegurar que DEV no lo haya corregido en rama paralela ni iniciar discovery. |
| FIN-03 | OPEN — BACKEND BLOCKER | Contraste HEAD y aceptación específica en B1/B2/B5/B6/B7; no ejecutar endpoints ni bypass frontend. |
| FOLLOW-01 | BUSINESS EXCLUDED | Camino legacy/pausado fuera de recuperación según R7.5/AGENTS y decisiones vigentes. No significa código reparado ni autoriza borrarlo; PUR-R3-01 conserva ambigüedad nominal indicada en E. |
| GENERAL-01 | BUSINESS EXCLUDED | Camino legacy/pausado fuera de recuperación según R7.5/AGENTS y decisiones vigentes. No significa código reparado ni autoriza borrarlo; PUR-R3-01 conserva ambigüedad nominal indicada en E. |
| GUIDE-01 | RESOLVED BY DEV | Autorización/idempotencia/failure contract implementados; DEV MySQL/HTTP aislado reportado. J cerrado por negocio con limitaciones; no solicitar otra certificación/emisión. |
| GUIDE-02 | RESOLVED BY DEV | Autorización/idempotencia/failure contract implementados; DEV MySQL/HTTP aislado reportado. J cerrado por negocio con limitaciones; no solicitar otra certificación/emisión. |
| INV-01 | PARTIALLY RESOLVED | Lectores/variantes/posiciones/preloads corregidos. Residual ajuste B3 y authority reportes B4; no reabrir MAT ni Kardex. ENTRY Edit ya no depende del viejo preload, sino ENTRY-R75-01. |
| KARDEX-01 | RESOLVED BY DEV | Catálogo/permiso y HTTP smoke Owners EDESCE/Gamarra PASS en revisión22/11. Caché pendiente del18/11 superada; no cambiar KardexMovementQuery. |
| MAT-01 | RESOLVED BY DEV | R7.5 + R7.7B y decisión MAT-01. Conservar historia/omitidos/maestro, posiciones y validación de flags. IDs MAT04/05 corregidos documentalmente, no intercambiar tickets. |
| MAT-02 | EVIDENCE REQUIRED | D5: residual histórico no cerrado por evidencia disponible; pedir consumer/contrato/prueba actual. No asegurar que DEV no lo haya corregido en rama paralela ni iniciar discovery. |
| MAT-03 | RESOLVED BY DEV | R7.5 + R7.7B y decisión MAT-01. Conservar historia/omitidos/maestro, posiciones y validación de flags. IDs MAT04/05 corregidos documentalmente, no intercambiar tickets. |
| MAT-04 | RESOLVED BY DEV | R7.5 + R7.7B y decisión MAT-01. Conservar historia/omitidos/maestro, posiciones y validación de flags. IDs MAT04/05 corregidos documentalmente, no intercambiar tickets. |
| MAT-05 | RESOLVED BY DEV | R7.5 + R7.7B y decisión MAT-01. Conservar historia/omitidos/maestro, posiciones y validación de flags. IDs MAT04/05 corregidos documentalmente, no intercambiar tickets. |
| METRIC-01 | PARTIALLY RESOLVED | Metas y Financial Summary contextual cerrados; residual Report/Rotation/PersonalPayment B4/B5, siete FR Dashboard. Cambio Owner no corrige estos endpoints. |
| OUTPUT-01 | BUSINESS EXCLUDED | Camino legacy/pausado fuera de recuperación según R7.5/AGENTS y decisiones vigentes. No significa código reparado ni autoriza borrarlo; PUR-R3-01 conserva ambigüedad nominal indicada en E. |
| OUTPUT-02 | BUSINESS EXCLUDED | Camino legacy/pausado fuera de recuperación según R7.5/AGENTS y decisiones vigentes. No significa código reparado ni autoriza borrarlo; PUR-R3-01 conserva ambigüedad nominal indicada en E. |
| PAY-01 | PARTIALLY RESOLVED | Selectable/currentId aplicado a consumers vigentes; regresión SQL de OrderService/Proforma no se declara reparada: fuera de scope por negocio. No reactivar para probar. |
| PERCENT-01 | OPEN — BACKEND BLOCKER | B9: Request required/exists y destroy delete físico; exclusión UI de cuatro claves no es guard backend. |
| POS-01 | OPEN — MUTATION QA | Pruebas aisladas/interceptadas previas no certifican persistencia HTTP ni simultaneidad MySQL. D2–D4 especifican huecos; no defecto nuevo por faltar QA. |
| PRICE-01 | RESOLVED BY DEV | Cierre8A/PriceListClosureTest: default activo, rollback, moneda y binding company. No reabrir BP-01/02/03. |
| PRICE-02 | RESOLVED BY DEV | Cierre8A/PriceListClosureTest: default activo, rollback, moneda y binding company. No reabrir BP-01/02/03. |
| PRICE-03 | RESOLVED BY DEV | Cierre8A/PriceListClosureTest: default activo, rollback, moneda y binding company. No reabrir BP-01/02/03. |
| PRICE-04 | RESOLVED BY DEV | Cierre8A/PriceListClosureTest: default activo, rollback, moneda y binding company. No reabrir BP-01/02/03. |
| PRICE-05 | OPEN — CONTRACT CLARIFICATION | C2: confirmar contrato vigente/ejemplos por ID. No decisión/frontend unilateral ni reauditoría Store Web; cifras y propuestas históricas no son datos actuales. |
| PRICE-06 | OPEN — CONTRACT CLARIFICATION | C2: confirmar contrato vigente/ejemplos por ID. No decisión/frontend unilateral ni reauditoría Store Web; cifras y propuestas históricas no son datos actuales. |
| PRICE-07 | OPEN — CONTRACT CLARIFICATION | C2: confirmar contrato vigente/ejemplos por ID. No decisión/frontend unilateral ni reauditoría Store Web; cifras y propuestas históricas no son datos actuales. |
| PRICE-08 | EVIDENCE REQUIRED | D5: evidencia de datos histórica; inventario actual/origen y autorización antes de backfill. No convertir cifras antiguas en dataset vigente ni afirmar corrección. |
| PRICE-09 | OPEN — MUTATION QA | Pruebas aisladas/interceptadas previas no certifican persistencia HTTP ni simultaneidad MySQL. D2–D4 especifican huecos; no defecto nuevo por faltar QA. |
| PROFILE-01 | RESOLVED BY DEV | SELF + ProfileAvatarService, UUID/reencode/validación/rollback/cleanup. Evidencia DEV agrupada y R7.5; UI reconciliada R7.7B. QA despliegue aparte, USER01 no se cierra por esto. |
| PROMO-01 | BUSINESS EXCLUDED | Camino legacy/pausado fuera de recuperación según R7.5/AGENTS y decisiones vigentes. No significa código reparado ni autoriza borrarlo; PUR-R3-01 conserva ambigüedad nominal indicada en E. |
| PUBLIC-01 | EVIDENCE REQUIRED | D5: evidencia de datos histórica; inventario actual/origen y autorización antes de backfill. No convertir cifras antiguas en dataset vigente ni afirmar corrección. |
| PUBLIC-02 | EVIDENCE REQUIRED | D5: residual histórico no cerrado por evidencia disponible; pedir consumer/contrato/prueba actual. No asegurar que DEV no lo haya corregido en rama paralela ni iniciar discovery. |
| PUBLIC-03 | OPEN — CONTRACT CLARIFICATION | C2: confirmar contrato vigente/ejemplos por ID. No decisión/frontend unilateral ni reauditoría Store Web; cifras y propuestas históricas no son datos actuales. |
| PUBLIC-04 | OPEN — CONTRACT CLARIFICATION | C2: confirmar contrato vigente/ejemplos por ID. No decisión/frontend unilateral ni reauditoría Store Web; cifras y propuestas históricas no son datos actuales. |
| PUBLIC-05 | OPEN — CONTRACT CLARIFICATION | C2: confirmar contrato vigente/ejemplos por ID. No decisión/frontend unilateral ni reauditoría Store Web; cifras y propuestas históricas no son datos actuales. |
| PUR-01 | PARTIALLY RESOLVED | Normal/Entry preloads y créditos tratados; Financial Purchase retirado y Service excluido. Residual Entry identificado separadamente B1/B2; no ticket amplio duplicado. |
| PUR-R3-01 | BUSINESS EXCLUDED | Camino legacy/pausado fuera de recuperación según R7.5/AGENTS y decisiones vigentes. No significa código reparado ni autoriza borrarlo; PUR-R3-01 conserva ambigüedad nominal indicada en E. |
| PUR-R3-02 | RESOLVED BY DEV | Print eliminado usa onlyTrashed + contexto/permiso; FR036 recuperado R7.6. No PDF real nuevo ni reabrir operación documental. |
| QA-01 | PARTIALLY RESOLVED | Cobertura previa/DEV aportada, no cierre integrado global. D4: read-path/exports/listados/restore con límites documentados; no volver a pedir fixes ya verificados. |
| QA-02 | OPEN — MUTATION QA | Pruebas aisladas/interceptadas previas no certifican persistencia HTTP ni simultaneidad MySQL. D2–D4 especifican huecos; no defecto nuevo por faltar QA. |
| QA-03 | OPEN — MUTATION QA | Pruebas aisladas/interceptadas previas no certifican persistencia HTTP ni simultaneidad MySQL. D2–D4 especifican huecos; no defecto nuevo por faltar QA. |
| QA-04 | PARTIALLY RESOLVED | Cobertura previa/DEV aportada, no cierre integrado global. D4: read-path/exports/listados/restore con límites documentados; no volver a pedir fixes ya verificados. |
| QUALITY-01 | EVIDENCE REQUIRED | D5: evidencia de datos histórica; inventario actual/origen y autorización antes de backfill. No convertir cifras antiguas en dataset vigente ni afirmar corrección. |
| QUOTE-01 | RESOLVED BY DEV | Fix acotado documentado en POST-MERGE-DEV-FIX-REVIEW 18/11; no reimplementar. Persistencia/cobertura restante separada en QA. |
| QUOTE-02 | PARTIALLY RESOLVED | Permisos efectivos en consumidores tratados / QuoteFormReadAccess corregidos. D5 exige delimitar residual antes de nuevo fix global; R8.2C conserva conversión y no certifica mutación. |
| QUOTE-03 | OPEN — CONTRACT CLARIFICATION | C2: confirmar contrato vigente/ejemplos por ID. No decisión/frontend unilateral ni reauditoría Store Web; cifras y propuestas históricas no son datos actuales. |
| QUOTE-04 | SUPERSEDED | Ruta/action de totales retirada por DEV, sin referencias runtime según revisión18/11. Retiro intencional, no reponer método ni contar nueva recuperación. |
| QUOTE-05 | PARTIALLY RESOLVED | Cobertura previa/DEV aportada, no cierre integrado global. D4: read-path/exports/listados/restore con límites documentados; no volver a pedir fixes ya verificados. |
| SALES-NEW-01 | OPEN — BACKEND BLOCKER | B12: fórmula startRecord/endRecord aún invertida para total positivo y página fuera de rango. Empty total0 sí mejoró. |
| SALES-NEW-02 | EVIDENCE REQUIRED | D5: residual histórico no cerrado por evidencia disponible; pedir consumer/contrato/prueba actual. No asegurar que DEV no lo haya corregido en rama paralela ni iniciar discovery. |
| SCHEMA-01 | RESOLVED BY DEV | Migraciones8/8 batch179 y cupos batch180 confirmados en evidencias posteriores; no instalación repetida en R8.3 ni certificación de todos los datos. |
| SERVICE-03 | BUSINESS EXCLUDED | Camino legacy/pausado fuera de recuperación según R7.5/AGENTS y decisiones vigentes. No significa código reparado ni autoriza borrarlo; PUR-R3-01 conserva ambigüedad nominal indicada en E. |
| SUPPLIER-01 | RESOLVED BY DEV | Lifecycle selectivo y permiso separado list_supplierAccount en código; evidencia DEV auditoria-qa-04-restauracion-listados. Aceptación aislada reportada; cobertura HTTP global permanece D4. |
| TRANSFER-01 | OPEN — BACKEND BLOCKER | B8: prueba negativa histórica + contraste HEAD request/prepare frente a Availability; QA03 positivo no cierra tracks_inventory=false. |
| USER-01 | PARTIALLY RESOLVED | Directorio/lecturas tratadas; administración legacy contenida por gate y fuera de scope. SELF y Required Password Change son contratos separados; no cierre global por perfil. |
| WAREHOUSE-01 | OPEN — BACKEND BLOCKER | B10/B11: causa actual en código y schema histórico documentado, sin nueva consulta DB. Separar legacy de ManagedWarehouse; no nuevo FR. |
| WAREHOUSE-02 | OPEN — BACKEND BLOCKER | B10/B11: causa actual en código y schema histórico documentado, sin nueva consulta DB. Separar legacy de ManagedWarehouse; no nuevo FR. |

FR108/R8-B02 y FR109/R8-E02: **RESOLVED BY FRONTEND**, fuera de los 84 IDs DEV; evidencia R8.2A. No sumar como defectos DEV ni alterar los110 FR.

