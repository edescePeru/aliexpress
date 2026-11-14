# REAL NEXT — DEV TEAM FINDINGS BACKLOG

Fecha: **2026-11-13 (America/Lima)**. Código contrastado: HEAD `9262d65` y documentación canónica acumulada. Preparado para backend/dev y producto; **no enviado a terceros**.

## Resumen para triage

**71 registros:** 21 BUG / FALLA (17 OPEN y 4 FIXED — QA PASSED), 38 DEUDA TÉCNICA y 12 DECISIÓN DE NEGOCIO / PRODUCTO. Incluye los addenda Sales/Reporting Exports, Warehouse Transfers y Material Edit. Los registros cerrados preservan trazabilidad; no son trabajo nuevo. DEV ACTION LIST original contiene nueve bugs abiertos: cinco P1 y cuatro P2; añadir SALES-NEW-01 y TRANSFER-01 como P2, MAT-03 como P1 y MAT-04/05 como P2. Sin P0 asignado.

Prioridad inmediata: límites de acceso Entry/creadores/usuarios legacy, limpieza PDF y validación monetaria Cash. En paralelo, Producto debe definir mutabilidad histórica de cajas/subtipos y consistencia de moneda. La infraestructura documental se consolida en DOC-01. Pricing/Cash todavía no son dependencias POS certificadas; no se autoriza iniciar core POS por este informe.

## Alcance y criterios

Sólo auditoría y documentación. No se ejecutaron fixes, tests, consultas DB nuevas, migraciones, exports, recálculos ni mutaciones en esta fase. Las pruebas citadas son evidencia previa: Pricing 8A **10 tests / 62 assertions**; Cash 8B **11 tests / 132 assertions**. Caracterizar un defecto mediante un test verde no certifica su seguridad.

El adjunto termina en `# 27.` sin contenido posterior. Este entregable cubre todos los requisitos completos recibidos (1–26). No sustituye los contratos canónicos ni reabre fachadas aprobadas.

- BUG: Expected respaldado por contrato. DECISIÓN: política no inequívoca. DEUDA: robustez, diseño, QA o dataset sin fallo funcional demostrado.
- Los blockers YES/NO/PARTIAL se refieren a la certificación del subflujo afectado, no a detener todo el ERP. Production YES significa no aprobar ese camino multitenant hasta cierre; no afirma conocer el despliegue productivo.
- FIXED — QA PASSED significa únicamente la cobertura descrita, no deployed. Los conteos históricos no se presentan como inventario vigente del 13/11.
- No se asignan CRITICAL/P0 sin evidencia de gravedad suficiente. Hallazgo estático no equivale a explotación HTTP probada.
- NEEDS AUDIT se recoge bajo QA PENDING/NEEDS BACKEND DESIGN, con su límite explícito. No se convierten sospechas en bugs.

## Reconciliación de estados

- BP-01/02/03 y PriceList ID scope están corregidos y probados aisladamente. La decisión COMPANY ya está definida; no reabrirla.
- Daily Profit contextual: recálculo, idempotencia y aislamiento QA PASSED; resumen legacy correctamente excluido. Documentos y exports separados.
- Schema slugs y snapshot fiscal local resuelto; publicación y backfill no resueltos por migrar.
- Admin cash: la reauditoría2 señalaba ausencia de permiso. Código actual `CashBoxController::__construct` sí registra `permission:listCashMovementAdmin_cashBox` para adminIndex/adminList, también reconocido por ConfigUserWeb. **No se arrastra como bug abierto**; matriz negativa real pendiente.
- Cash dataset antiguo “sin company” ya superado al menos en EDESCE: 8B vio 4 cajas/5 subtipos. BANK-01 conserva evidencia histórica, requiere nuevo inventario antes de reconciliar.
- Frontend aprobado, POS visual y Settings no se reabren. Permanecen sus gates de mutación explícitos.

## Tabla maestra

| ID | Módulo | Finding | Clasificación | Severidad | Estado | Tags | Expected | Actual | Evidencia | Frontend blocker | Production blocker | POS blocker | Recomendación | Owner sugerido |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| CASH-01 | CashBox | Currency arbitraria | BUG / FALLA | HIGH | OPEN | FINANCIAL; DATA INTEGRITY | Monedas compatibles con PEN/USD del contrato POS/base_currency | Cash store acepta XYZ; UI no protege API | CashMaintainersClosureTest::test_known_gaps_currency_and_historical_semantics_remain_open; CashBoxController::validateCashBox; PosSaleService; settings_catalog | NO | PARTIAL | YES | Backend Fix: whitelist respaldada; preservar nullable explícito y autoridad bancaria | Backend Cash |
| CASH-02 | CashBox | Cambiar moneda con sesión abierta | DECISIÓN DE NEGOCIO / PRODUCTO | HIGH | NEEDS BUSINESS DECISION | FINANCIAL; DATA INTEGRITY; PRODUCT CONTRACT | Definir inmutabilidad de caja/sesión | Update USD con sesión status1 y referencia devuelve200/persiste | Test gaps 8B; validateCashBox sólo protege cambio branch | NO | PARTIAL | YES | Business Decision Required CASH-02; luego guard/snapshot | Producto financiero + Backend |
| CASH-03 | Subtype | Cambiar is_deferred referenciado | DECISIÓN DE NEGOCIO / PRODUCTO | HIGH | NEEDS BUSINESS DECISION | FINANCIAL; DATA INTEGRITY | Definir versión histórica y cambios permitidos | Diferido→inmediato persiste; consumidores leen flag actual; daño transaccional no ejecutado | Test gaps 8B; CashRegularizationService::subtype; DeferredSaleRefund::plan; SalePartialPaymentController | NO | PARTIAL | YES | Business Decision Required CASH-03; separar hecho y riesgo | Producto financiero + Backend |
| CASH-04 | Subtype | Desactivar canal referenciado | DECISIÓN DE NEGOCIO / PRODUCTO | HIGH | NEEDS BUSINESS DECISION | FINANCIAL; PRODUCT CONTRACT | Diferenciar histórico cerrado, cobros y regularización pendientes | Toggle acepta referencias; regularización requiere activo. Referencia no demuestra pendiente | Test gaps 8B; CashBoxSubtypeController::toggle; CashRegularizationService::subtype | NO | PARTIAL | YES | Business Decision Required CASH-04, sin prohibición genérica inventada | Producto financiero |
| CASH-05 | Subtype | Nullable omitido causa Undefined index | BUG / FALLA | MEDIUM | OPEN | DATA INTEGRITY; UX / FRONTEND | Ausencia nullable normalizada o validación422 explícita | Omitir cash_box_id provoca ErrorException | CashMaintainersClosureTest::test_known_gap_omitted_nullable_subtype_scope_is_not_handled; validateSubtype | NO | PARTIAL | PARTIAL | Backend Fix: null por defecto o presencia requerida validada | Backend Cash |
| CASH-06 | Cash | Concurrencia MySQL | DEUDA TÉCNICA | MEDIUM | QA PENDING | CONCURRENCY; TEST COVERAGE | Verificar guards concurrentes apertura/edición y duplicados | Check-then-save sin coordinación observada; no race reproducida | CashBoxController::toggle/validateCashBox; CashBoxSubtypeController::store; 8B Tests | NO | PARTIAL | YES | Technical Hardening: diseño y pruebas multiproceso aisladas | Backend + QA |
| CASH-07 | Cash UI/transporte | Feedback mutación end-to-end | DEUDA TÉCNICA | LOW | QA PENDING | TEST COVERAGE; UX / FRONTEND | Success/422 y refresco con HTTP aislado | Controllers/handler probados; browser sólo abrir/editar/cancelar | 8B Frontend Regression, 11 tests/132 assertions | NO | PARTIAL | PARTIAL | Technical Hardening: entorno HTTP de testing separado | QA |
| PRICE-01 | BP-01 | Primera lista default inactiva | BUG / FALLA | HIGH | FIXED — QA PASSED | FINANCIAL; DATA INTEGRITY | Default efectivo activo | Ahora422 antes de insertar | PriceListController::store; PriceListClosureTest; cierre8A BP-01 | NO | NO | NO | Fix ya realizado; conservar regresión | Backend Pricing |
| PRICE-02 | BP-02 | 422 ignorado/defaults parciales | BUG / FALLA | HIGH | FIXED — QA PASSED | FINANCIAL; DATA INTEGRITY | Error propagado y rollback completo | ValidationException y rollback probado incluso tras fallo persistencia | PriceListController::update; PriceListClosureTest::test_failure_after_unsetting_defaults_rolls_back_all_changes | NO | NO | NO | Fix ya realizado; conservar tests | Backend Pricing |
| PRICE-03 | BP-03 | Whitelist de moneda | BUG / FALLA | HIGH | FIXED — QA PASSED | FINANCIAL | PEN/USD explícitos | Rule::in y normalización string probadas | PriceListController::store/update/normalizeCurrency; PriceListClosureTest | NO | NO | NO | Fix ya realizado; no confundir con PRICE-07 | Backend Pricing |
| PRICE-04 | PriceList ID | Scope company inconsistente | BUG / FALLA | HIGH | FIXED — QA PASSED | SECURITY / AUTHORIZATION; MULTITENANCY | Cinco operaciones ID respetan company como listado | Guard tenant+company404, QA aislado y lectura8A | validatePriceListContext; PriceListClosureTest::test_id_scope_rejects_other_company_and_tenant_before_read_or_write | NO | NO | NO | Fix ya realizado; COMPANY es decisión cerrada | Backend Pricing |
| PRICE-05 | BP-04 | Oferta ausente vs moneda obsoleta | DEUDA TÉCNICA | MEDIUM | NEEDS BACKEND DESIGN | FINANCIAL; UX / FRONTEND | Diagnóstico explícito si se requiere remediación | Ambos configured=false/price=null | BranchPresentationService::dto; cierre8A Branch Offerings | NO | PARTIAL | PARTIAL | Technical Hardening: reason sin precio vendible falso | Backend Pricing |
| PRICE-06 | BP-05 | Resolver genérico tenant-wide | DEUDA TÉCNICA | MEDIUM | NEEDS BACKEND DESIGN | SECURITY / AUTHORIZATION; MULTITENANCY | Callers autorizan company/branch | resolveUsingPriceList no lo hace; caller POS sí añade contexto. No fuga POS demostrada | PriceResolverService; BranchPriceListService; cierre8A | NO | PARTIAL | PARTIAL | Technical Hardening: precondición y tests de nuevos callers | Backend Pricing |
| PRICE-07 | Company2 | Base USD/lista PEN | DECISIÓN DE NEGOCIO / PRODUCTO | HIGH | NEEDS BUSINESS DECISION | DATA QUALITY; FINANCIAL | Elegir configuración monetaria coherente | 8A branches2/3 baseUSD fallback lista2PEN; POS rechaza correctamente | Cierre8A Dataset read-only, 2026-11-12 | NO | PARTIAL | YES | Data Reconciliation tras decisión PRICE-07; no conversión automática | Owner company + Producto |
| PRICE-08 | Gamarra company3 | Sin lista/precios efectivos | DEUDA TÉCNICA | MEDIUM | BLOCKED BY DATASET | DATA QUALITY; FINANCIAL | Dataset configurado antes de vender | Branch4 sin lista; stocks105/106 null, fail-closed correcto | Cierre8A Dataset read-only | NO | PARTIAL | YES | Data Reconciliation autorizada; no fake0 | Owner company + QA |
| PRICE-09 | PriceList | Defaults concurrentes MySQL | DEUDA TÉCNICA | MEDIUM | QA PENDING | CONCURRENCY; TEST COVERAGE | Invariantes bajo solicitudes simultáneas | Lock company existe; 10/62 SQLite no prueba exclusión multiproceso | lockCurrentCompany; cierre8A Default Invariants | NO | PARTIAL | YES | Technical Hardening: MySQL aislado, primer insert/cambio default | Backend QA |
| ENTRY-01 | Entries detalle | Falta guard company | BUG / FALLA | HIGH | OPEN | SECURITY / AUTHORIZATION; MULTITENANCY | Detalle respeta frontera company del listadoV2 | Binding Entry tenant-only; show entrega vista sin company | EntryController:2374 showEntryPurchase; Entry; routes/web.php:1141; reauditoría2 E | PARTIAL | YES | NO | Backend Fix: resolver company fail-closed; 404 cross-company/tenant | Backend Compras |
| ENTRY-02 | Entries listas | Límites/estados sin QA | DEUDA TÉCNICA | MEDIUM | QA PENDING | TEST COVERAGE; DATA QUALITY | Fechas/paginación y estados adversos definidos | INCOMPLETA/REQUIERE REVISIÓN y fechas manipuladas pendientes | Entries V2 Lists Readonly, Backend Sync Notes | NO | PARTIAL | NO | Technical Hardening: validación y dataset aislado, recepción aparte | Backend Compras + QA |
| DOC-01 | Documentos transversal | Public storage/access model | DEUDA TÉCNICA | HIGH | NEEDS BACKEND DESIGN | SECURITY / AUTHORIZATION; DOCUMENTS; MULTITENANCY | Autorizar descarga por documento/contexto aunque URL sea conocida | Paths públicos; acceso HTTP directo no certificado, no explotación anónima afirmada | comprobantes/pdfs; images/entries; images/orderServices; public/pdfs; cierres Sales/UserProfit/Finance/Quotes/Entry | PARTIAL | YES | PARTIAL | Technical Hardening: entrega protegida/almacenamiento privado y retención | Backend transversal + Infra |
| DOC-02 | Quotes PDF | Limpieza global de archivos | BUG / FALLA | HIGH | OPEN | DOCUMENTS; DATA INTEGRITY; MULTITENANCY | Imprimir documento no elimina archivos ajenos | glob(public/pdfs/*)+unlink sin selección contextual; no ejecutado | QuoteSaleController::printQuoteToCustomer:10911–10914; Q-02 | PARTIAL | YES | NO | Backend Fix: generación/limpieza por artefacto y contexto | Backend Quotes + Infra |
| DAILY-01 | Daily Profit legacy | Resumen sin contexto excluido | DEUDA TÉCNICA | LOW | ACCEPTED TECH DEBT | LEGACY DATA; DATA QUALITY | No atribuir historia sin respaldo | Summary1 2026-01-20 T/C/B/currency null; correctamente excluido | Daily Profit Contextual Recalculation QA | NO | NO | NO | Data Reconciliation futura; exclusión aceptada hasta conciliación | Data owner |
| DAILY-02 | Daily Profit contextual | Cierre ya realizado | DEUDA TÉCNICA | LOW | FIXED — QA PASSED | TEST COVERAGE; FINANCIAL | Recálculo/idempotencia/aislamiento correctos | Dos corridas 2026-11-04: summary2 1/1/1 PEN;23 unidades/1460 venta/400.62 utilidad | Daily Profit QA 2026-11-12; comparación de tablas documentada | NO | NO | NO | Sin fix nuevo; exports QA-01, documentos DOC-01 | QA |
| FIN-01 | Finance historia | Cinco líneas sin snapshot fiscal | DEUDA TÉCNICA | MEDIUM | BLOCKED BY DATASET | LEGACY DATA; TAX; DATA QUALITY | Evidencia fiscal histórica, no inventarla | 5 DetailEntry sin snapshot según cierre; fallback18% no lo reemplaza | Finance Invoices Legacy Data; migration2026_11_10_000002 | NO | PARTIAL | NO | Data Reconciliation autorizada; conservar ausencia explícita | Contabilidad + Data |
| FIN-02 | Finance categoría | Relación sin frontera company | DEUDA TÉCNICA | HIGH | NEEDS BACKEND DESIGN | DATA INTEGRITY; MULTITENANCY | Integridad contextual también en relaciones | belongsTo withTrashed sin company; dataset auditado sin cruces | Entry.php:106 category_invoice; Finance Backend Sync | NO | PARTIAL | NO | Technical Hardening: garantía lectura/escritura, fixture corrupto, preservar históricos | Backend Finance |
| FIN-03 | Finance auxiliares | Endpoints heterogéneos | DEUDA TÉCNICA | HIGH | NEEDS BACKEND DESIGN | SECURITY / AUTHORIZATION; MULTITENANCY | Definir finance/permisos/contexto antes de consumir | getInvoiceById/getServiceById no equivalen a financeEntries; excluidos | InvoiceController; Finance Backend Sync | PARTIAL | PARTIAL | NO | Technical Hardening: auditoría y autorización por endpoint | Backend Finance |
| PAY-01 | Plazos | Selectores ofrecen inactivos | BUG / FALLA | MEDIUM | OPEN | DATA INTEGRITY; UX / FRONTEND | Nuevas opciones activas; sólo conservar histórico seleccionado | where(type)->get no filtra activo; scope modelo sólo T/C | PaymentDeadline::scopeSelectable; OrderServiceController:63; OrderPurchaseFinanceController:59 | NO | PARTIAL | PARTIAL | Backend Fix: selectable/currentId y prueba activo/inactivo/histórico | Backend Compras/Ventas |
| BANK-01 | Cuentas bancarias | Históricos sin bank/currency | DEUDA TÉCNICA | MEDIUM | BLOCKED BY DATASET | DATA QUALITY; LEGACY DATA | Completar con evidencia de origen | Cierre reportó IDs1/2 incompletos; no reconfirmado hoy, dataset posterior cambió | Company Bank Accounts Backend Sync; 8B muestra cajas bancarias PEN | NO | PARTIAL | PARTIAL | Data Reconciliation: refrescar inventario antes de corregir | Data owner |
| BANK-02 | Cuentas bancarias | Default inactivo permitido | DECISIÓN DE NEGOCIO / PRODUCTO | MEDIUM | NEEDS BUSINESS DECISION | FINANCIAL; PRODUCT CONTRACT | Definir default implica activo o preferencia histórica | Update lo permite; toggle desactivar limpia/reasigna default | CompanyBankAccountController::store/update/toggleStatus | NO | PARTIAL | PARTIAL | Business Decision Required BANK-02; unicidad concurrente después | Producto financiero |
| BANK-03 | Bank→CashBox | Desactivar cuenta vinculada | DECISIÓN DE NEGOCIO / PRODUCTO | HIGH | NEEDS BUSINESS DECISION | FINANCIAL; DATA INTEGRITY | Definir efecto sobre cajas/pagos existentes | toggleStatus sin guard CashBox; configurar caja exige cuenta activa | CompanyBankAccountController::toggleStatus; CashBoxController::validateCashBox | NO | PARTIAL | PARTIAL | Business Decision Required BANK-03, incluir sincronización moneda | Producto + Backend Cash |
| PUBLIC-01 | Public Catalog | Schema resuelto; slugs/dataset pendientes | DEUDA TÉCNICA | MEDIUM | BLOCKED BY DATASET | DATA QUALITY; TEST COVERAGE | Slugs autorizados para QA público | Migración batch166 aplicada; reauditoría2 reportó5/5 companies y8/8 branches NULL, conteo histórico | REAUDIT-2 esquema/A; migration2026_11_09_000002; PublicCatalogSlug | PARTIAL | YES | NO | Data Reconciliation: inventario actual y asignación autorizada | Producto catálogo + QA |
| PUBLIC-02 | SW-01/04 | Aislamiento/filtros públicos sin cierre | DEUDA TÉCNICA | HIGH | QA PENDING | SECURITY / AUTHORIZATION; MULTITENANCY; TEST COVERAGE | Resolver/filtros dentro de empresa/local publicables | Resolver T/C/B existe; falta matriz anónima. Bridge local no equivale a público | PublicCatalog/PublicCatalogController; REAUDIT-2 matriz SW | PARTIAL | YES | NO | Technical Hardening: positivos/negativos slugs/material/local/filtros | Backend Public Catalog + QA |
| PUBLIC-03 | SW-02 | Política editorial/disponibilidad | DECISIÓN DE NEGOCIO / PRODUCTO | HIGH | NEEDS BUSINESS DECISION | PRODUCT CONTRACT; INVENTORY | Definir publicable/agotados/locales | Stock positivo coherente no equivale a autorización editorial | REAUDIT-2 SW-02; PublicCatalog | PARTIAL | YES | NO | Business Decision Required PUBLIC-03 | Producto catálogo |
| PUBLIC-04 | SW-03/05 | Moneda y opción comercial pública | DECISIÓN DE NEGOCIO / PRODUCTO | HIGH | NEEDS BUSINESS DECISION | PRODUCT CONTRACT; FINANCIAL | Definir precios/variantes/presentaciones y contacto | Listas/rangos presentes; filtro precio PEN; sin selección pública completa de presentación | REAUDIT-2 SW-03/05; no equivalencia POS | PARTIAL | YES | NO | Business Decision Required PUBLIC-04; QA null/default/show_prices | Producto catálogo + Backend |
| PUBLIC-05 | SW-06/07 | Home/branding/contacto | DECISIÓN DE NEGOCIO / PRODUCTO | MEDIUM | NEEDS BUSINESS DECISION | PRODUCT CONTRACT; UX / FRONTEND | Contenido editorial e identidad pública autorizados | Home sin contrato editorial; branding parcial; assets permanentes no son problema | REAUDIT-2 SW-06/07; Store Web Home Contract | PARTIAL | PARTIAL | NO | Business Decision Required PUBLIC-05; no publicar fixtures | Producto catálogo |
| QUOTE-01 | Q-01 creadores | Lookup global de identidades | BUG / FALLA | HIGH | OPEN | SECURITY / AUTHORIZATION; MULTITENANCY | No entregar usuarios de otros tenants | User::select(id,name)->get en general/facturadas; QA previo vio catálogo cruzado | QuoteSaleController:144/205; User sin TenantScope; Q-01 | NO | YES | NO | Backend Fix: lookup contextual; política histórica explícita, no filtro JS | Backend Quotes |
| QUOTE-02 | Q-03/04 | Permisos/relaciones heterogéneos | DECISIÓN DE NEGOCIO / PRODUCTO | HIGH | NEEDS BUSINESS DECISION | SECURITY / AUTHORIZATION; PRODUCT CONTRACT | Matriz única precios/conversión/edición e IDs hijos autorizados | UI comprobantes vs edit_quoteSale; update-general sin permiso granular; pertenencia hijos no certificada | Quotes Q-03/04; routes/web.php:3420; showRegistrarComprobante/storeFromQuote | PARTIAL | PARTIAL | PARTIAL | Business Decision Required QUOTE-02; luego validators y QA roles | Producto permisos + Backend |
| QUOTE-03 | Q-05 historia | Moneda/tasa actuales sin snapshot garantizado | DEUDA TÉCNICA | HIGH | NEEDS BACKEND DESIGN | TAX; LEGACY DATA; FINANCIAL | Historia respaldada sin reinterpretación por configuración posterior | show usa configuración actual; no snapshot histórico garantizado | Quotes Q-05; QuoteSaleController::show | NO | PARTIAL | PARTIAL | Technical Hardening + Data Reconciliation, sin inventar snapshots | Backend Quotes + Contabilidad |
| QUOTE-04 | Q-06 totales | Action inexistente | BUG / FALLA | MEDIUM | OPEN | UX / FRONTEND | Ruta registrada resuelve método válido | cotizaciones/venta/totales→indexGeneral no existe en controller | routes/web.php:3376; búsqueda actual function indexGeneral sin coincidencia | PARTIAL | PARTIAL | NO | Backend Fix: implementar contrato o retirar ruta, sin nuevo enlace | Backend Quotes |
| QUOTE-05 | Q-06/07 | Metadata/fechas y cobertura | DEUDA TÉCNICA | MEDIUM | QA PENDING | TEST COVERAGE; UX / FRONTEND | Metadata vacía y fechas parciales uniformes | Doc registra start1/end0 y fechas diferentes; transiciones/roles pendientes. No asumir dataset actual vacío | Quotes Q-06/07 y QA histórico | NO | PARTIAL | PARTIAL | Technical Hardening: revalidar metadata, matriz fechas/roles y dataset aislado | QA + Backend |
| MAT-01 | Material Edit Save | Política histórica y gates restantes | DEUDA TÉCNICA | HIGH | PARTIAL QA PASSED / NEEDS BACKEND DESIGN | DATA INTEGRITY; INVENTORY; TEST COVERAGE | Definir cambios flags con historia, omisión, identidad presentada y escritura de maestro tenant-wide | SQLite11/164: regresiones y rollback PASS; SKU modifica representación histórica de compra; flags admiten stock/reservas; catálogo company no limita update maestro | Material Edit Save Closure2026-11-13; MaterialEditClosureTest | NO (fachada congelada) | PARTIAL | PARTIAL | Cerrar decisiones explícitas y QA MySQL/upload; bugs concretos separados MAT-03/04/05, sin duplicar TRANSFER-01 | Backend Inventario + Producto + QA |
| MAT-02 | Separate Pack | Stock/ownership legacy | DEUDA TÉCNICA | HIGH | NEEDS BACKEND DESIGN | INVENTORY; MULTITENANCY | Alinear separación con inventario contextual | Reauditorías señalan materials.stock_current y relaciones unpack sin scope; no mutación probada | Material Post-Merge QA; Backend Reaudit Latest Separate Pack | YES | YES | PARTIAL | Technical Hardening: contrato inventario/trazabilidad antes de habilitar | Backend Inventario |
| METRIC-01 | Dashboard/Financial Summary/Meta | Queries y actores sin contexto homogéneo | DEUDA TÉCNICA | HIGH | NEEDS BACKEND DESIGN | FINANCIAL; MULTITENANCY; PRODUCT CONTRACT | Definir T/C/B/moneda por indicador | Dashboard/Graphs parcial; summary primer owner global; Meta/Workers legacy. Sin explotación HTTP nueva | CashRegisterController::financialSummary:2979/2982; reauditoría baseline filas37–39 | YES | YES | NO | Technical Hardening tras decisión METRIC-01 | Backend Reporting + Producto |
| USER-01 | UserController legacy | Listados globales | BUG / FALLA | HIGH | OPEN | SECURITY / AUTHORIZATION; MULTITENANCY | Usuario operativo no enumera identidades ajenas | getUsers/getUsers2 filtran enable/id, no tenant; rutas dashboard vigentes | UserController:129/136; User sin TenantScope; routes all/users,get/users | PARTIAL | YES | NO | Backend Fix: contextualizar/retirar rutas; mantener ConfigUserWeb safe path | Backend Identity |
| GUIDE-01 | Shipping/Referral | Read model sin cierre contextual | DEUDA TÉCNICA | HIGH | NEEDS BACKEND DESIGN | MULTITENANCY; DOCUMENTS; EXTERNAL INTEGRATION | Contrato T/C y documentos autorizados antes de lectura/emisión | Modelos/list/binding sin frontera suficiente según auditoría; dataset entonces0/0 | REAUDIT-2 I; ShippingGuideController/ReferralGuideController | YES | YES | NO | Technical Hardening: acceso contextual; DOC-01; consulta proveedor puede escribir | Backend Logística |
| INV-01 | Kardex/Inventory/Items legacy | Fuentes/scopes no homologados | DEUDA TÉCNICA | HIGH | NEEDS BACKEND DESIGN | INVENTORY; MULTITENANCY | Company/branch/warehouse coherentes | Kardex stock_item/warehouse sin company/branch; inventory_movements_view sin scope; legacy Item sin ScreenAccess | REAUDIT-2 J; InventoryMovementController::kardex; StockItems closure | YES | YES | PARTIAL | Technical Hardening: read model y validación warehouse; no extrapolar lectura moderna | Backend Inventario |
| FOLLOW-01 | Stock Follow | Existencias/ownership legacy | DEUDA TÉCNICA | MEDIUM | NEEDS BACKEND DESIGN | INVENTORY; MULTITENANCY | Definir seguimiento local/company vs maestro tenant | Lista user_id; stock_current/detalle no fija perímetro homogéneo; dataset positivo pendiente | REAUDIT-2 K; FollowMaterialController | YES | PARTIAL | NO | Technical Hardening con contrato explícito, no explotación afirmada | Backend Inventario |
| PROMO-01 | Promotions | Scope/motor sin cierre | DEUDA TÉCNICA | HIGH | NEEDS BACKEND DESIGN | MULTITENANCY; FINANCIAL; PRODUCT CONTRACT | Definir promoción contextual y aplicación comercial | Reauditoría mantiene BACKEND SYNC; dataset Limit/Order/Seasonal vacío | REAUDIT-2 WAIT FOR BACKEND; controllers PromotionLimit/Order/Seasonal | YES | PARTIAL | PARTIAL | Technical Hardening tras contrato; no inventar descuentos | Producto comercial + Backend |
| PUR-01 | Compras financieras/Entry reporte legacy | No heredan scope normal | DEUDA TÉCNICA | HIGH | NEEDS BACKEND DESIGN | MULTITENANCY; FINANCIAL | Frontera company por familia | OrderPurchaseFinance sin scope; reporte fecha legacy sin company; normal sí ScopedPurchaseCompany | Reauditoría baseline Compras; EntryController::getJsonEntriesPurchaseReport | YES | YES | NO | Technical Hardening por endpoint; no mezclar cierre Normal Purchases | Backend Compras |
| POS-01 | Core POS/FreeSale | Cierre transaccional no certificado | DEUDA TÉCNICA | HIGH | QA PENDING | FINANCIAL; INVENTORY; TAX; TEST COVERAGE | Caja/reservas/reversión/idempotencia verificadas | Servicios nuevos no certifican transacción; FreeSale no delega actor/T/C/B moderno | Backend Reaudit Latest Venta libre; REAUDIT-2 Trigger core POS | NO | YES | YES | Technical Hardening posterior a Pricing/Cash; FreeSale diseño separado | Backend POS + QA |
| CUSTOMER-01 | Clientes lookup | GET searchOrCreate mutador | BUG / FALLA | MEDIUM | OPEN | SECURITY / AUTHORIZATION; DATA INTEGRITY | Lectura GET sin crear; mutación con método/permiso adecuados | GET crea y no declara create_customer adicional | Customers Backend Sync; route customer.searchOrCreateByDocument; DecolectaService | NO | PARTIAL | PARTIAL | Backend Fix: separar lookup/creación y autorización | Backend Clientes |
| CUSTOMER-02 | Consulta externa | Rutas test sin guard explícito | DEUDA TÉCNICA | HIGH | NEEDS BACKEND DESIGN | EXTERNAL INTEGRATION; SECURITY / AUTHORIZATION | Exposición/cuotas de despliegue definidas | test-ruc/test-dni fuera de auth según cierre; no llamadas ejecutadas | Customers Backend Sync; routes/web.php | NO | PARTIAL | NO | Technical Hardening: retirar/restringir rutas de prueba y verificar despliegue | Backend + Infra |
| CUSTOMER-03 | Restore | Todos contactos borrados restaurados | DECISIÓN DE NEGOCIO / PRODUCTO | MEDIUM | NEEDS BUSINESS DECISION | LEGACY DATA; PRODUCT CONTRACT | Definir contactos borrados antes de cliente | Restore abarca todos relacionados; sin QA positivo | CustomerController::restore; Customers Backend Sync | NO | PARTIAL | NO | Business Decision Required CUSTOMER-03 | Producto maestros |
| SUPPLIER-01 | Accounts | Payload más amplio que permiso UI | DECISIÓN DE NEGOCIO / PRODUCTO | HIGH | NEEDS BUSINESS DECISION | SECURITY / AUTHORIZATION; PRODUCT CONTRACT | Definir list_supplier vs list_supplierAccount | JSON serializa accounts; UI requiere permiso separado | SupplierController::getSuppliers; Suppliers Backend Sync | NO | PARTIAL | NO | Business Decision Required SUPPLIER-01; si separado, limitar backend | Producto permisos + Backend |
| AUTH-01 | Clientes/Proveedores | Permisos directos vs roles | DEUDA TÉCNICA | LOW | QA PENDING | SECURITY / AUTHORIZATION; UX / FRONTEND | UI alineada a can efectivo si grants directos admitidos | Array getPermissionsViaRoles vs can/permission; no reproducido con Owners | Customers/Suppliers Backend Sync | NO | PARTIAL | NO | Technical Hardening: test grants directos antes de declarar bug | QA Identity |
| DATA-01 | Históricos varios | Contexto/cuentas/imágenes incompletos | DEUDA TÉCNICA | MEDIUM | BLOCKED BY DATASET | LEGACY DATA; DATA QUALITY | Reconciliar sólo con evidencia de origen | Sales/cash históricos sin contexto; cuenta proveedor5 sin número; imágenes históricas pendientes según cierres | REAUDIT-2 Dataset; Suppliers Backend Sync; cierres Sales/StockItems | NO | PARTIAL | PARTIAL | Data Reconciliation por familia y conteos actuales | Data owners |
| QA-01 | Exports | QA consolidado pendiente | DEUDA TÉCNICA | MEDIUM | QA PENDING | TEST COVERAGE; FINANCIAL | Filtros/contexto/totales/permisos iguales a listado | Exports User Profit/Daily Profit/Finance no certificados | Cierres canónicos de las tres familias | NO | PARTIAL | NO | Technical Hardening: multipágina, roles, null/costo/moneda | QA Reporting |
| QA-02 | CRUD configuraciones | Mutaciones no cerradas por UI | DEUDA TÉCNICA | MEDIUM | QA PENDING | TEST COVERAGE; DATA INTEGRITY | Persistencia/rollback/guards por familia | Settings/upload, Tax Types, categorías, plazos, bancos, maestros, ConfigUserWeb y catálogos Material pendientes | Cierres canónicos; Material Post-Merge QA deletes/cascadas | NO | PARTIAL | PARTIAL | Technical Hardening: suites por módulo, sin reabrir diseño | QA Backend |
| QA-03 | Transfers/Availability | Concurrencia MySQL e integración restante | DEUDA TÉCNICA | HIGH | PARTIALLY CLOSED — TEST DB PASS | INVENTORY; CONCURRENCY; TEST COVERAGE | Locks, kardex y escenarios entre módulos verificados en entorno aislado MySQL | Ciclo/parciales/reservas/rollback/idempotencia: SQLite PASS10/236; 3 recibidos reales readonly; MySQL pendiente | Warehouse Transfers Functional Closure2026-11-13 | NO | PARTIAL | PARTIAL | Mantener QA MySQL aislado; no duplicar deuda genérica de ciclo | Inventario + QA |
| QA-04 | Restore/roles/paginación | Dataset positivo insuficiente | DEUDA TÉCNICA | LOW | BLOCKED BY DATASET | TEST COVERAGE | Eliminados, roles limitados y multipágina verificables | Clientes/Proveedores sin eliminados en cierres; varios reportes pequeños | Customers/Suppliers Functional QA; Finance Revisit; Quotes Q-07 | NO | PARTIAL | NO | Technical Hardening: fixtures deterministas sin confundir visual/persistencia | QA |
| EXT-01 | Nubefact/guías | Config/emisión sin certificación | DEUDA TÉCNICA | HIGH | QA PENDING | EXTERNAL INTEGRATION; TAX; TEST COVERAGE | Versionado/config/proveedor validados | No POST stale/conexión/emisión; guías fuente configuración separada | Platform Nubefact:76/117/125; NubefactGreService | NO | PARTIAL | PARTIAL | Technical Hardening: sandbox proveedor sin secretos en evidencia | Integraciones + QA |
| SCHEMA-01 | Migraciones | Blockers locales superados | DEUDA TÉCNICA | LOW | FIXED — QA PASSED | DATA QUALITY; TEST COVERAGE | Verificar esquema antes de atribuir defecto | sales.created_by revalidado; cuatro migraciones batch166 aplicadas; no equivale a backfill/despliegue | Backend Reaudit Latest re-QA Sales; REAUDIT-2 esquema | NO | NO | NO | Sin fix nuevo; validar despliegue objetivo aparte | DevOps + QA |

| SALES-NEW-01 | Sales List | Rango invertido fuera de página | BUG / FALLA | MEDIUM | OPEN | PAGINATION; API CONTRACT | Rango coherente para página vacía | page2,total6: start11/end6; page999:start9981/end6 | getSalesAdmin: start calculado antes de comprobar filas y end=min(total,page*10); cierre Sales 2026-11-13 | NO | PARTIAL | NO | P2 Backend: definir rechazo/clamp/rango vacío, test regresión; no corregir contrato unilateralmente | Backend Sales |
| SALES-NEW-02 | Sales List | Validación document_type compartida con errores | DEUDA TÉCNICA | LOW | OPEN | CONTRACT CLARITY | Distinguir filtros admitidos por superficie | Normal acepta e ignora; getSalesErrorAdmin sí aplica where type_document | SaleListAccess::validateFilters; PuntoVentaController líneas5142/5184; UI normal no expone filtro | NO | NO | NO | Separar/documentar contratos normales/errores; no inventar filtro ni eliminar su uso real | Backend Sales |
## Evidencia documental raíz DOC-01

| Consumer | Evidencia/ubicación | Alcance y separación |
|---|---|---|
| Sales | REAL-NEXT-SALES-LIST-READONLY; comprobantes/pdfs; rutas protegidas del camino nuevo | No afirmar que todo Sales sea inseguro: route guard no prueba acceso directo al archivo público. |
| User Profit | REAL-NEXT-USER-PROFIT-READONLY; GananciaDiariaController::getDataGananciasTrabajador | Construcción asset(comprobantes/pdfs/...) legacy; no hereda protección de otro consumer. |
| Daily Profit | REAL-NEXT-DAILY-PROFIT-READONLY cierre contextual | Cálculo certificado no certifica entrega documental; comparte raíz, no nuevo bug duplicado. |
| Finance | REAL-NEXT-FINANCE-INVOICES-READONLY | images/entries y images/orderServices, links excluidos; relación categoría FIN-02 independiente. |
| Entry | REAL-NEXT-ENTRIES-V2-LISTS-READONLY | images/entries/adjuntos; ENTRY-01 es autorización del objeto, DOC-01 autorización del archivo. |
| Quotes | REAL-NEXT-QUOTES Q-02 | public/pdfs y descarga contextual; borrado global es bug separado DOC-02. |
| Compras/guías | REAL-NEXT-NORMAL-PURCHASES-READONLY y REAUDIT-2 I | Auditar rutas/documentos al abrir su fase; no asumir mismo almacenamiento sin inventario. |

Criterio raíz: documento de tenant/company ajenos inaccesible por URL directa, autenticado o anónimo; descarga propia permitida con permiso; nombres/limpieza sin colisiones; estrategia de migración de enlaces legacy. Elegir entrega autenticada o mecanismo equivalente explícitamente autorizado. No se verificó infraestructura productiva en esta fase.

# DEV ACTION LIST

Sólo bugs abiertos con fix respaldado. P0: **ninguno asignado**. Los fixes PRICE-01…04 quedan en registro de cierre, no en cola. Cada línea es copiable a un ticket.

- **P1 — ENTRY-01 — Company guard en detalle Entry.** Fix: resolver cabecera por tenant/company antes de vista. Evidencia: showEntryPurchase y listado V2 contextual. Aceptación: company propia200; otra company mismo tenant404; otro tenant404; inexistente404; mismos resultados con manipulación de ID; no romper listado company-wide entre branches.
- **P1 — QUOTE-01 — Lookup contextual de creadores.** Fix: dejar de enumerar User global; resolver opciones del contexto autorizado. Evidencia: arrayUsers144/205 y QA cruzado documentado. Aceptación: EDESCE/Gamarra no reciben identidades ajenas en HTML/JSON; general/facturadas coherentes; política de creadores históricos explícita; sin filtrado de seguridad JS.
- **P1 — USER-01 — Cerrar listados legacy globales.** Fix: retirar rutas o usar acceso contextual equivalente al camino seguro, sin trasladar permisos arbitrariamente. Evidencia: UserController getUsers/getUsers2 y User sin scope. Aceptación: caller operativo no lista otro tenant; probar usuarios limitados, Platform Admin, IDs ajenos y rutas alternativas; ausencia de exposición en JSON, no sólo ocultar menú.
- **P1 — DOC-02 — Eliminar limpieza global de PDF.** Fix: generación y limpieza limitadas al artefacto/contexto. Evidencia: glob + unlink global. Aceptación: dos tenants con PDFs; generar/repetir uno no borra ni sobrescribe otro; fallo de generación conserva artefactos ajenos; no recorrer GET real hasta disponer de entorno aislado.
- **P1 — CASH-01 — Validar moneda contractual.** Fix: whitelist backend PEN/USD para efectivo; conservar autoridad CompanyBankAccount para bancaria. Evidencia: XYZ aceptado en test8B. Aceptación: XYZ422 sin fila; PEN/USD válidos; definir/null según contrato opcional; bank ignora moneda cliente y toma cuenta; no convertir precios/saldos. Cambios de moneda en uso se resuelven aparte con CASH-02.
- **P2 — CASH-05 — Normalizar alcance nullable.** Fix: ausencia cash_box_id normalizada a null o validación presence explícita documentada. Evidencia: test Undefined index. Aceptación: omisión/null/global/vacío/caja válida reciben respuesta contractual; inválida/ajena422; ningún500; no warning PHP; update y store cubiertos.
- **P2 — PAY-01 — Excluir plazos inactivos de nuevas selecciones.** Fix: aplicar selectable() o selectable(currentId) según create/edit. Evidencia: OrderService63, OrderPurchaseFinance59 frente a scopeSelectable. Aceptación: inactivo no aparece en create; edit conserva sólo su histórico seleccionado; otro inactivo excluido; company/tenant intactos; revisar Quote/Proforma y caminos legacy de OrderPurchase/QuoteSale enumerados en Consumers.
- **P2 — QUOTE-04 — Resolver ruta a action inexistente.** Fix: implementar el contrato aprobado o retirar/deprecar ruta/enlaces existentes. Evidencia: route venta/totales apunta a indexGeneral ausente. Aceptación: ruta no produce error de método; permisos y respuesta definidos; test de ruta; no inventar métricas.
- **P2 — CUSTOMER-01 — Separar lectura y creación por documento.** Fix: GET sin efectos de creación; mutación con método/CSRF/permiso de crear apropiados. Evidencia: searchOrCreateByDocument. Aceptación: GET no inserta; usuario sin permiso no crea; POST autorizado y repetido mantiene regla de duplicados; proveedor simulado en testing; sin llamada externa real para QA.

P3: no bug adicional suficientemente acreditado para asignar fix automático. Metadata QUOTE-05, textos legacy de servicios y permisos directos requieren revalidación focalizada; no convertirlos en tickets de lógica comercial por apariencia.

# BUSINESS DECISIONS REQUIRED

Las opciones siguientes no son decisiones adoptadas. Backend debe traducir la respuesta aprobada a reglas verificables; QA no debe fabricar históricos para decidir fiscalidad o moneda.

### CASH-02 — ¿Cuándo puede cambiar la moneda de una caja que ya se utilizó?

Hecho: cambio aceptado con sesión abierta y referencia sintética. Riesgo: servicios que comparan la moneda actual pueden rechazar operaciones del contexto original. No se ejecutó posting para probar corrupción.

A. Nunca desde la primera sesión/movimiento: máxima estabilidad, requiere nueva caja para otra moneda. B. Sólo sin sesión abierta ni cobros pendientes: exige definir comprobación de pendientes y conservar historia. C. Permitir con versionado/snapshot explícito: mayor complejidad y migración. D. Otra regla escrita. Recomendación técnica: evitar cambio sin reconciliación durante sesión abierta; decisión final del equipo financiero. Aceptación posterior: tests de sesión abierta/cerrada, historial y pendientes según opción.

### CASH-03 — ¿Puede cambiar is_deferred después del primer uso?

Hecho reproducido: update referenciado persiste. Riesgo demostrado por código: regularización requiere flag actual=true; devolución/pagos parciales consultan el flag actual junto a otras señales. No se afirmó que todo movimiento se reinterpretó en una operación real.

A. Inmutable desde primer uso, crear otro canal: sencillo y preserva semántica. B. Cambiable sólo sin pendientes, snapshots históricos: requiere reconciliación y garantía de lectura histórica. C. Cambiable siempre, nueva versión efectiva sólo para futuras operaciones: exige versionado completo. D. Otra regla. Recomendación técnica: no reinterpretar cobros existentes por flag mutable; escoger A/B/C es decisión funcional.

### CASH-04 — ¿Qué referencias impiden desactivar un subtipo?

Responder por cada escenario: **histórico cerrado**, **cobros pendientes**, **regularización pendiente**.

A. Bloquear ante cualquier uso: conservador, impide retirar canales antiguos. B. Permitir histórico cerrado pero bloquear pendientes: requiere definición/query de pendiente y coordinación concurrente. C. Permitir desactivar para nuevas operaciones, manteniendo uso histórico/regularización autorizada: separar elegibilidad nueva de resolución histórica. D. Otra regla. Recomendación técnica: B o C con invariantes explícitas; no inferir que una referencia sola sea pendiente. Prueba de aceptación debe cubrir los tres estados, no sólo existencia de CashMovement.

### PRICE-07 — ¿Qué configuración debe prevalecer en company 2?

Última evidencia8A: base USD/lista efectiva PEN en branches2/3; rechazo POS actual es correcto.

A. Mantener base USD y configurar lista/precios USD autorizados: exige carga comercial validada. B. Mantener lista PEN y cambiar base a PEN después de analizar historia/servicios: impacto transversal. C. Mantener ambas para usos separados y no vender con esa combinación: bloqueo operativo intencional. D. Otra configuración explícita. Recomendación técnica: no conversión automática ni edición masiva inferida; pedir decisión del owner financiero. Reconsultar dataset antes de aplicar.

### BANK-02 — ¿Default implica que la cuenta esté activa?

A. Sí siempre: rechazar default inactivo y serializar unicidad. B. Default es preferencia histórica y puede estar inactivo: consumidores nuevos deben excluirlo y definir fallback. C. Otra regla. Evidencia actual: update permite inactivo mientras toggle desactivar limpia/reasigna default. Recomendación técnica: unificar ambos caminos tras decisión, no declarar bug sólo por esa asimetría.

### BANK-03 — ¿Qué efecto tiene desactivar o cambiar moneda de una cuenta vinculada a CashBox?

A. Bloquear si hay caja activa/sesión/pendiente dependiente. B. Permitir, pero invalidar nuevas selecciones y conservar operaciones históricas con snapshot. C. Cambiar sólo la cuenta maestra sin afectar cajas existentes, declarando formalmente independencia/versionado. D. Otra regla. A protege coherencia inmediatamente; B/C requieren distinguir estado/moneda copiada de autoridad vigente. Recomendación técnica: cubrir sincronización de currency y permisos, no desactivar cajas en cascada por inferencia.

### PUBLIC-03 — ¿Qué productos/locales son publicables y se muestran agotados?

A. Opt-in editorial por company/local, ocultar agotados. B. Opt-in editorial, mostrar agotados como no disponibles. C. Todo catálogo habilitado company con stock válido y política explícita de exposición. D. Otra regla. Impacto: cambia dataset público, filtros y significado de disponibilidad; no sólo copy. Recomendación técnica: identidad pública resuelta y dataset autorizado antes de QA anónimo; ninguna opción autoriza publicar fixtures.

### PUBLIC-04 — ¿Qué precio y opción comercial debe comunicar el catálogo público?

A. Una moneda/lista efectiva del local y selección de variante, sin paquetes. B. Moneda/lista local con presentaciones públicas autorizadas y mensaje de contacto con opción precisa. C. Catálogo consultivo sin precios ni selección de compra, contacto genérico claramente indicado. D. Otro contrato. Impacto: API de rangos/precio ausente, filtros, show_prices y WhatsApp. Recomendación técnica: definir moneda antes de extender filtro PEN; no reutilizar contrato privado POS sin autorización pública.

### PUBLIC-05 — ¿Home y branding se publican con qué fuente?

A. Catálogo básico con identidad/contacto explícitos, sin home editorial. B. Home editorial con bloques administrables y contrato propio. C. Home sólo después de cerrar editorial, manteniendo publicación bloqueada. D. Otra regla. Impacto: A permite alcance menor; B requiere datos y permisos editoriales; C pospone lanzamiento. Recomendación técnica: no usar novedades/promociones fixture como datos reales ni asumir que Settings completo sea público.

### QUOTE-02 — ¿Qué matriz de permisos gobierna precios, conversión y edición?

A. Capacidades separadas para ver precios, editar cotización y generar cada comprobante. B. Rol comercial compuesto autorizado explícitamente para esas capacidades. C. Mantener regla actual documentando excepciones concretas por endpoint. D. Otra matriz. Impacto: frontend y API deben coincidir; los IDs padre/hijo/contacto se validan siempre, cualquiera sea la opción. Recomendación técnica: una fuente de autorización y tests usuario limitado; no decidir desde visibilidad de botones.

### METRIC-01 — ¿Las métricas son de local, empresa o tenant?

Responder por Dashboard/Graphs, Financial Summary y Meta/Ranking.

A. Local activo por defecto, empresa sólo con permiso agregado. B. Empresa activa, sin desglose local implícito. C. Vista tenant/owner separada explícita, con desglose por empresa/moneda. D. Otra regla por indicador. Impacto: no basta añadir where(branch_id) a toda consulta; cambia semántica de totales y actores. Recomendación técnica: no sumar monedas distintas ni seleccionar un owner global para una vista contextual.

### CUSTOMER-03 — ¿Restore debe recuperar contactos eliminados antes del cliente?

A. Sólo contactos eliminados como parte de la misma operación del cliente: exige trazabilidad de eliminación. B. Todos los contactos borrados relacionados: comportamiento actual, debe quedar explícito y probado. C. Restauración selectiva autorizada: más UX/contrato. D. Otra regla. Recomendación técnica: no cambiar restore masivo sin decidir origen histórico.

### SUPPLIER-01 — ¿list_supplier autoriza leer cuentas bancarias?

A. No; list_supplierAccount es obligatorio: limitar JSON backend y probar usuario sin permiso. B. Sí; permiso separado sólo gobierna mantenimiento/presentación: documentar acceso concedido y simplificar contradicción. C. Otro permiso de lectura. Impacto: exposición del payload, no sólo celda oculta. Recomendación técnica: resolver antes de certificar autorización de cuentas.

### PROMO-01 / FOLLOW-01 — ¿Cuál es el ámbito funcional de promociones y seguimiento?

A. Company/local con elegibilidad explícita. B. Definición tenant compartida con habilitación operacional por company/local. C. Otro ámbito escrito. Impacto: cambia ownership, disponibilidad y aplicación comercial. Recomendación técnica: mantener fuera de nuevos consumidores hasta definirlo; las consultas legacy no son una decisión de producto por sí mismas.

## Cobertura y asignación a equipos

- **Backend fixes claros:** DEV ACTION LIST. Ningún fix aplicado aquí.
- **Diseño transversal:** DOC-01; históricos CASH/BANK/QUOTE/FIN; scopes de métricas, guías, inventario, promociones y compras ampliadas. No cerrar por agregar un filtro frontend.
- **Data owners:** PRICE-07/08, BANK-01, FIN-01, DAILY-01, DATA-01 y PUBLIC-01. Refresh de inventario antes de remediar; conteos locales no son prueba productiva.
- **QA:** CASH-06/07, PRICE-09, QA-01…04, EXT-01, MAT-01 y POS-01. No iniciar ventas, emisión ni posting por recibir este backlog.
- **Gates:** Pricing y Cash maintainers siguen NOT READY como dependencias certificadas; CashRegister/CashMovement/Regularization son una puerta posterior independiente. Daily Profit contextual permanece cerrado, no recalcular otra vez por esta entrega.

## Índice de fuentes y trazabilidad

Los nombres abreviados de la tabla corresponden a estos documentos del mismo directorio. Se prioriza la sección de cierre más reciente sobre el estado inicial de cada archivo.

- [Cash Boxes/Subtypes: cierre 8B](REAL-NEXT-CASH-BOXES-SUBTYPES.md)
- [Pricing/Branch: cierre 8A](REAL-NEXT-BRANCH-PRICING-PRESENTATIONS.md)
- [Reauditoría backend y schema Sales](REAL-NEXT-BACKEND-REAUDIT-LATEST.md)
- [Inventario global y contratos legacy](REAL-NEXT-FRONTEND-PRIORITY-REAUDIT.md)
- [Reauditoría diferencial / Roadmap C](REAL-NEXT-FRONTEND-PRIORITY-REAUDIT-2.md)
- [Entries V2](REAL-NEXT-ENTRIES-V2-LISTS-READONLY.md)
- [Quotes Q-01…07](REAL-NEXT-QUOTES.md)
- [Daily Profit y recálculo contextual](REAL-NEXT-DAILY-PROFIT-READONLY.md)
- [Finance Invoices](REAL-NEXT-FINANCE-INVOICES-READONLY.md)
- [Payment Deadlines](REAL-NEXT-PAYMENT-DEADLINES.md)
- [Company Bank Accounts](REAL-NEXT-COMPANY-BANK-ACCOUNTS.md)
- [Material Post-Merge QA](REAL-NEXT-MATERIAL-POST-MERGE-QA.md)
- [ConfigUserWeb safe path](REAL-NEXT-CONFIG-USER-WEB.md)
- [Customers](REAL-NEXT-CUSTOMERS.md)
- [Suppliers](REAL-NEXT-SUPPLIERS.md)
- [Sales List](REAL-NEXT-SALES-LIST-READONLY.md)
- [User Profit](REAL-NEXT-USER-PROFIT-READONLY.md)
- [Normal Purchases](REAL-NEXT-NORMAL-PURCHASES-READONLY.md)
- [Warehouse Transfers](REAL-NEXT-WAREHOUSE-TRANSFERS.md)
- [Warehouse Availability](REAL-NEXT-WAREHOUSE-AVAILABILITY-READONLY.md)
- [StockItems/Items](REAL-NEXT-STOCKITEMS-ITEMS-READONLY.md)
- [Platform Nubefact](REAL-NEXT-PLATFORM-NUBEFACT.md)
- [Company Settings/Branding](REAL-NEXT-COMPANY-SETTINGS-BRANDING.md)
- [Platform Tax Types](REAL-NEXT-PLATFORM-TAX-TYPES.md)
- [Tests Pricing](../tests/Feature/PriceListClosureTest.php)
- [Tests Cash](../tests/Feature/CashMaintainersClosureTest.php)

Evidencia reproducida CASH proviene de fixtures aislados, no de daño a registros reales. ENTRY-01 tiene inspección actual y lectura equivalente al binding en reauditoría, no explotación HTTP afirmada. DOC-01 requiere verificar despliegue web/infra antes de afirmar acceso anónimo. USER-01 tiene evidencia de query/ruta vigente, no explotación realizada aquí. PAY-01 tiene evidencia estática positiva de ausencia de filtro, no simple sospecha; falta prueba de consumo end-to-end.

## Entrega / control de cambios

Único archivo creado por esta fase: `docs/REAL-NEXT-DEV-TEAM-FINDINGS-BACKLOG.md`. Los cambios preexistentes del cierre8B (documento Cash y test Cash) se preservan. Backend/frontend/CSS/configuración/datos sin cambios; sin commit ni envío externo. Revisión de estructura de tabla, IDs únicos, enlaces locales y `git diff --check` al cierre. Este backlog organiza evidencia para priorizar; no sustituye una autorización de remediación o mutación.


## Addendum — Reporting Exports / Sales QA

2026-11-13: se agregan únicamente SALES-NEW-01 (BUG,P2) y SALES-NEW-02 (TECH DEBT,uso real en listado de errores). Los conteos iniciales se actualizaron; la sección original de alcance sin DB/tests describe la preparación original, no este addendum. Evidencia nueva: cierre Sales read-only y clasificación Reporting Exports. No duplicar DOC-01 ni QA-04. Hallazgos de formato/validación/paridad de exports quedan en canónicos User Profit/Finance para triage, sin ampliar esta tabla más allá de los dos findings Sales solicitados.

## Addendum — Warehouse Transfers Functional Closure

2026-11-13. Suite aislada `WarehouseTransferClosureTest.php`: **10 tests / 236 assertions**. Se reduce QA-03 sin duplicarlo; WT-03 permanece deuda textual canónica. Lectura compartida actual:3 transfers received,4 recepciones; Owners ahora tienen permisos operativos/manageAll. No se ejecutó ninguna mutación compartida. Backend/frontend intactos; test de caracterización verde no cierra el defecto siguiente.

| ID | Módulo | Finding | Clasificación | Severidad | Estado | Tags | Expected | Actual | Evidencia | Frontend blocker | Production blocker | POS blocker | Recomendación | Owner sugerido |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| TRANSFER-01 | Warehouse Transfers | Escritura admite StockItem sin tracks_inventory | BUG / FALLA | MEDIUM / P2 | OPEN | INVENTORY; VALIDATION | Elegibilidad de request/prepare debe respetar exclusión backend del catálogo/disponibilidad | tracks_inventory=false + lote/nivel existente: Availability rechaza422 pero request crea y prepare reserva4 | WarehouseTransferClosureTest::test_non_inventory_stock_item_is_currently_accepted_by_write_services; WarehouseTransferLine::initializeNewRecord; PreparationService::prepare; Controller::products | NO | PARTIAL (camino afectado) | NO directo | Alinear validación de escritura; decidir tratamiento de stock residual sin ampliar catálogo desde UI | Inventario Backend |

Reproducción solo TEST DB: producto activo de company, lote/nivel10, compra verificable; cambiar flag a false, Availability::product rechaza, RequestService crea4 y PreparationService reserva4. No explotación HTTP ni stock real alterado. La FormRequest no valida el flag y el controller delega al servicio; alcance demostrado es servicio/modelo. No se corrigió automáticamente porque afecta elegibilidad y tratamiento de inventario residual. Retest debe incluir cambio de flag entre solicitud y preparación y mantener alcance multitenant. No hay hallazgo nuevo de decisión comercial independiente ni duplicado DOC-01.
## Addendum — Material Edit Save Isolated Closure

2026-11-13: **11 tests / 164 assertions PASS** en SQLite in-memory, guards/request/controller reales, sin modificaciones de materiales compartidos. MAT-01 refinado; no se reabren precios legacy, descuentos omitidos, parámetros, talla/color o variantes omitidas. Characterization verde no significa bug resuelto. Backend/frontend/POS intactos; no fixes ni commit.

| ID | Módulo | Finding | Clasificación | Severidad | Estado | Tags | Expected | Actual | Evidencia | Frontend blocker | Production blocker | POS blocker | Recomendación | Owner sugerido |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| MAT-03 | Material Edit / Retacería | Propaga geometría física a otra company | BUG / FALLA | HIGH / P1 | OPEN | MULTITENANCY; DATA INTEGRITY | Edición contextual no debe modificar Items físicos de otra empresa sin contrato/autorización explícita | Cambiar typescrap desde company1 modifica length/width/typescrap de Item company2/exited | MaterialEditClosureTest::test_retaceria_currently_propagates_to_other_company_items; MaterialController::update Item::where(material_id)->whereIn(entered,exited) | NO | YES (retacería) | PARTIAL | Definir propagación histórica y limitar autorización/scope conjuntamente; no fix parcial silencioso | Backend Inventario + Producto |
| MAT-04 | Material Edit / Inventario | Request rechaza dos locations del mismo almacén | BUG / FALLA | MEDIUM / P2 | OPEN | INVENTORY; VALIDATION | Umbrales por ID InventoryLevel, sin deduplicar warehouse válido entre locations distintas | Request rechaza almacenes repetidos; sync acepta IDs1/5 con locations1/5 | MaterialEditClosureTest::test_inventory_thresholds_scope_and_known_multiple_location_rejection; UpdateMaterialRequest warehouseIds; MaterialInventoryScope::sync | NO | PARTIAL (multinivel) | NO | Alinear granularidad request/servicio manteniendo scope y anti-duplicado por nivel | Backend Inventario |
| MAT-05 | Material Edit / Flags | Texto inválido aceptado como false | BUG / FALLA | MEDIUM / P2 | OPEN | VALIDATION; INVENTORY | Rechazar fuera de dominio0/1 antes del cast | is_active/afecto_inventario='invalid' pasan y persisten0; número2 sí falla | MaterialEditClosureTest::test_current_flags_allow_disabling_with_stock_and_coerce_invalid_strings; UpdateMaterialRequest casts int previos a in_array | NO | PARTIAL (payload manipulado) | PARTIAL | Validación estricta previa a coerción con regresión; no resolver política de stock por este fix | Backend Material |

Decisiones pendientes permanecen dentro de MAT-01, no filas duplicadas: (a) flags con10 unidades/2 reservadas e historia, incluyendo omisión que reactiva a1; (b) SKU cambia la representación dinámica de compras aunque IDs y filas históricas se preserven; (c) Material tenant-master habilitado solo en company2 puede editarse desde company1 fuera de CompanyCatalog. La última es evidencia de diferencia de scopes, no se etiqueta automáticamente fuga de un maestro compartido. TRANSFER-01 sigue separado: valida elegibilidad en escritura de Transfers, no la política de edición del flag.

Retacería: no hay lectura de stock_current en ese fragmento; MAT-02 Separate Pack permanece fuera del alcance. La política de alterar geometría de Items exited propios requiere Producto además del bug físico cross-company. No se trasladan datos ni se corrigen materiales reales. Gates técnicos pendientes: MySQL locks/collation, alta de nueva variante y atomicidad filesystem de upload; no se certificaron mediante SQLite.

## Addendum — Compras General V2 (2026-11-13)

GENERAL-01 — BUG / FALLA — LOW / P3 — OPEN. Endpoint GET `/dashboard/get/data/orders/general/v2/{numberPage}`, `OrderPurchaseController::getDataOrderGeneral`.

Con 3 filas visibles, página 99 devuelve `data=[]`, `currentPage=99`, `totalPages=1`, `startRecord=981`, `endRecord=3`. Expected: rechazo/página normalizada o rango vacío coherente, definido por backend. Evidencia: invocación del action actual con dataset local, conexión READ ONLY y rollback; sin órdenes sintéticas. No afecta aislamiento. Frontend can continue: YES, UI muestra fuera de rango y enlace a página 1. Backend sin modificar. Owner: Backend Compras. Véase `REAL-NEXT-GENERAL-PURCHASES-V2-READONLY.md`.


### GENERAL-01 — ampliación Express V2 (misma causa, sin nuevo finding)

`OrderPurchaseController::getDataOrderExpress`, GET `/dashboard/get/data/orders/express/v2/{numberPage}`, repite la fórmula `(pageNumber - 1) * 10 + 1` sin normalizar vacío/fuera de rango, igual que General. Dataset Express físico/activo = 0: página 1 devuelve `totalPages=0,startRecord=1,endRecord=0`; página 99 `currentPage=99,startRecord=981,endRecord=0`. Revalidado en transacción READ ONLY con action real, sin fixtures. UI Express muestra 0 a 0 de 0 y no genera links de páginas inexistentes; backend permanece OPEN/UNCHANGED. Mantener un único GENERAL-01 para ambos actions; no duplicar SALES-NEW-01 ni incrementar inventario. Positivo/multipágina pendiente de dataset. Véase `REAL-NEXT-EXPRESS-PURCHASES-V2-READONLY.md`.


## Addendum — Calidades (2026-11-13)

Scope global legacy ya identificado por REAL-NEXT-ROADMAP-D-SCREEN-DISCOVERY.md: se aporta QA EDESCE/Gamarra con los mismos 12 IDs, sin duplicar la deuda general de ownership. El siguiente registro es una anomalía de datos distinta. Mutaciones siguen bajo QA-02; gates directos/roles bajo AUTH-01.

| ID | Módulo | Hallazgo | Clasificación | Prioridad | Estado | Evidencia / Acción |
|---|---|---|---|---|---|---|
| QUALITY-01 | Calidades | deleted_at con fecha cero excluye una fila del catálogo | DEUDA TÉCNICA | LOW | DATA RECONCILIATION PENDING | Lectura READ ONLY: 13 filas físicas; ID3 TALLA40 tiene 0000-00-00 00:00:00, por eso SoftDeletes devuelve12. No inferir que deba restaurarse: confirmar intención histórica y reconciliar con autorización. REAL-NEXT-QUALITIES Dataset. Backend y datos intactos. |


## Addendum — Porcentajes (2026-11-13)

Auditoría acotada, sin mutaciones. Scope global documentado junto a deuda transversal del discovery; no se duplica. Se agrega un defecto concreto diferente:

| ID | Módulo | Hallazgo | Clasificación | Prioridad | Estado | Evidencia / Acción |
|---|---|---|---|---|---|---|
| PERCENT-01 | PorcentageQuote | Eliminación de claves reservadas protegida sólo en JS | BUG / FALLA | MEDIUM / P2 | OPEN | index.js oculta delete para utility/rent/letter/igv; DeletePorcentageQuoteRequest sólo required/exists y destroy llama delete físico sin guard. Consumers buscan esas claves y algunos usan first()->value/firstOrFail. Agregar protección backend equivalente y QA aislado; no ejecutado POST ni eliminación real. Backend unchanged; frontend conserva condición existente. Ver REAL-NEXT-PERCENTAGES. |


## Addendum — Gastos (2026-11-13)

| ID | Módulo | Hallazgo | Clasificación | Prioridad | Estado | Evidencia / Acción |
|---|---|---|---|---|---|---|
| EXPENSE-01 | Rendición de gastos | Operaciones sin frontera de ownership | BUG / FALLA | HIGH / P1 | OPEN | Expense sin scope/tenant/company/branch; getAllExpenses sólo fecha; edit/update/destroy find global; reporte global. Eager Worker tenant no restringe Expense. Dataset actual0, no explotación cross-ID ni POST ejecutado. Definir ownership/historia y aplicar lectura/binding contextual; mantener lista/edit fuera del rollout. Véase REAL-NEXT-EXPENSES. |

PUR-01 — evidencia adicional sin duplicar finding: ExpenseSupplierController indexV2/getDataExpenseSuppliers mezclan compras scoped con OrderService global; lookup Supplier tenant no garantiza frontera del conjunto. GET de facturación rama servicio también find global. Superficie alternativa excluida; no export/mutación ejecutada. Backend unchanged.
