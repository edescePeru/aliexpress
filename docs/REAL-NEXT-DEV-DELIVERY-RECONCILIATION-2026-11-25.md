# REAL NEXT — DEV Delivery Reconciliation — 2026-11-25

Estado actual R7.5; historia anterior se conserva. DEV principal 41ed99f; Platform0129133; merge remoto bf6e834. No confundir presencia de código, prueba aislada, cierre por negocio e instalación completa.

| Finding | Estado anterior | Cambio DEV / evidencia actual | Commit | Estado backend | Estado frontend | QA | Siguiente acción |
|---|---|---|---|---|---|---|---|
| PROFILE-01 | Upload inseguro/rollback | ProfileAvatarService real image limits2MB/4096/8Mpx; GD reencode; UUID; cleanup después commit | 41ed99f (J: evidencia documental) | FIX CONFIRMED | ALREADY AVAILABLE | Prueba aislada carga/fallo PASS; upload real no ejecutado | Conservar UI, smoke de despliegue autorizado aparte |
| MAT-01 | Decisión pendiente | MaterialEditValues + decisión nueva: permite historia/stock/sharedmaster, preserva omitidos | 41ed99f (J: evidencia documental) | FIX CONFIRMED | QA REQUIRED | SQLite PASS | No reintroducir bloqueos descartados; verificar guard Save R7.6 |
| MAT-03 | Retacería/maestro compartido | MaterialRetaceriaPolicy ya NO prohíbe globalmente shared master; MAT-01 posterior lo define; Items no cambian | 41ed99f (J: evidencia documental) | FIX CONFIRMED | QA REQUIRED | SQLite PASS | Respetar alcance tenant; no migrar snapshots |
| MAT-04 | Flags | UpdateMaterialRequest valida antes de normalizar; omitted preservado | 41ed99f (J: evidencia documental) | FIX CONFIRMED | QA REQUIRED | SQLite PASS | Verificar UI actual contra contrato |
| MAT-05 | Posiciones | MaterialInventoryScope valida ubicación real y unicidad de posición, no sólo warehouse | 41ed99f (J: evidencia documental) | FIX CONFIRMED | QA REQUIRED | SQLite PASS | No inventar ubicación999 |
| CASH-02 | Moneda histórica mutable | CashConfigurationGuard bloquea con saldos/movimientos/sesiones; bank sync sólo sin uso | 41ed99f (J: evidencia documental) | FIX CONFIRMED | ALREADY AVAILABLE | SQLite PASS | UI muestra errores backend; no cambiar caja real |
| CASH-03 | Modalidad histórica mutable | Subtype immediate/deferred inmutable tras cobros | 41ed99f (J: evidencia documental) | FIX CONFIRMED | ALREADY AVAILABLE | SQLite PASS | No duplicar regla JS |
| CASH-04 | Desactivar con pendientes | Pending payments incluso sesión cerrada bloquean; no reescribe saldos | 41ed99f (J: evidencia documental) | FIX CONFIRMED | ALREADY AVAILABLE | SQLite PASS | QA real mutador pendiente |
| PUR-R3-01 | Faltantes Normal/General | NormalPurchaseReadAccess missingCodes ordena/deduplica y limita10000; controller mantiene correlativo compartido | 41ed99f (J: evidencia documental) | FIX PRESENT — INTEGRATION QA PENDING | ALREADY AVAILABLE para Normal; General excluido | Inspección, sin query de negocio mutadora | No restaurar General |
| PUR-R3-02 | Print soft-deleted no encontraba | printOrderPurchaseDelete usa query(true)->onlyTrashed + list permiso + T/C | 41ed99f (J: evidencia documental) | FIX PRESENT — INTEGRATION QA PENDING | FRONTEND RECOVERY REQUIRED FR-036 | Sin PDF/export | Adaptar sólo enlace autorizado en R7.6 |
| C / Q — preloads | Create/Edit Entry y Normal bloqueados | consumer explícito + StockItems/variants contextuales; Normal permiso update correcto | 41ed99f (J: evidencia documental) | PARTIALLY FIXED | FR017/018/046 recuperables; FR047 no | Inspección + caracterización negativa ENTRY-R75-01 | Recuperar Create/Normal; DEV alinear permiso EditEntry |
| ENTRY-R75-01 | NUEVO, no existía en R7 | PurchaseFormReadAccess::PERMISSIONS entry_edit=edit_entryPurchase; ruta y DB sólo update_entryPurchase | 41ed99f (J: evidencia documental) | STILL OPEN | BACKEND STILL BLOCKED FR047 | Test aislado demuestra rechazo; DB read-only confirma catálogo | DEV usar misma capacidad efectiva existente en route/service; no seedear bypass |
| ENTRY-R4-01 | Reversa financiera por endpoint compra | EntryStockReversalService valida entry_type Por compra, sin finance=false; no modificado | 41ed99f (J: evidencia documental) | STILL OPEN | FR048 bloqueado | Contraste diff/servicio, no anular | DEV frontera de tipo financiero antes de revertir |
| INV-01 | Ajuste / authority legacy | Fixes de catálogo/posiciones presentes; no corrigen ajustes stock ni reportes R7 globales | 41ed99f (J: evidencia documental) | PARTIALLY FIXED | FR059/063/064/103 siguen bloqueados | R6 test aislado; ajuste NO | No recuperar ajuste de stock |
| METRIC-01 | Dashboard aggregates pendientes | Home cambia Owner, no corrige ReportController/RotationMaterial/PersonalPayment | 41ed99f (J: evidencia documental) | STILL OPEN (residual Dashboard) | FR063/064/102/103/105/106/107 bloqueados | Sin exports/GET escritor | Respetar cierre previo Metas/FinancialSummary, no cerrar raíz |
| DASH-R7-01 | GET escribe negocio | RotationMaterialController y PersonalPaymentController no corregidos | 41ed99f (J: evidencia documental) | STILL OPEN | FR106/107 bloqueados | Inspección; no ejecutar GET | DEV separar consultas de escrituras y autorizar |
| DOC-01 / P | Rutas públicas/lectores | SaleDocumentFile; EntryDocumentAccess; QuoteDocumentFiles; credit readers; .htaccess | 41ed99f (J: evidencia documental) | CLOSED BY BUSINESS DECISION | FRONTEND RECOVERY REQUIRED en documentos Entry/Invoice | Código presente; Apache/despliegue no certificado aquí | Cierre negocio con limitaciones, no afirmar todo public certificado |
| DOC-02 | Cleanup quotes | QuoteDocumentFiles elimina limpieza global; escrituras por archivo | 41ed99f (J: evidencia documental) | FIX PRESENT — INTEGRATION QA PENDING | ALREADY AVAILABLE según contrato QuoteSale | No PDF real; cubierto por cierre P de negocio | No reabrir paquete P excluido |
| GUIDE-01 / GUIDE-02 / J | Evidencia entrega previa | Documentos J MySQL/HTTP aislado conservados; no cambios nuevos GRE en este diff | 41ed99f (J: evidencia documental) | CLOSED BY BUSINESS DECISION | ALREADY AVAILABLE | No nueva emisión ni QA GRE | Conservar TECHNICAL LIMITATIONS DOCUMENTED |
| K / Supplier Credits | Certificación financiera pendiente | Readers preservados; decisión no uso temporal | 41ed99f (J: evidencia documental) | CLOSED BY BUSINESS DECISION | PRODUCT SCOPE EXCLUDED | No pagos/pruebas nuevas | No purgar datos ni reactivar módulo |
| FIN-03 | Auxiliar factura sin permiso/finance | InvoiceController@getInvoiceById y route sin nuevo permiso siguen iguales | 41ed99f (J: evidencia documental) | STILL OPEN | Auxiliar excluido; details DTO vigente | Inspección HEAD | DEV permiso list_invoice + finance authority; no volver a usar auxiliar |
| QA-02 | Mantenedores/concurrencia | Cash/Material tests focalizados; no entrega tests ejecutables globales QA02 en diff | 41ed99f (J: evidencia documental) | FIX PRESENT — INTEGRATION QA PENDING | R5 ya recuperado | R5/Cash/Material PASS aislado; no QA global | No cerrar todos los CRUD por suite parcial |
| Cotizaciones venta D | Create/Edit permisos/contactos | QuoteFormReadAccess authorize create/edit, QuoteSale contacts y comprobante | 41ed99f (J: evidencia documental) | FIX PRESENT — INTEGRATION QA PENDING | QA REQUIRED / FRONTEND RECOVERY REQUIRED según control | Inspección; sin cotizar/emitir | R7.6 inventariar gates heredados de formulario sin confundir industrial |
| USER-01 / legacy access | CRUD legacy inseguro | OperationalModuleGate legacy-access423; UsuariosWeb moderno intacto | 41ed99f (J: evidencia documental) | CONTAINED BY SERVER BLOCK | PRODUCT SCOPE EXCLUDED legacy | Definición/gate, no reabrir password obligatorio | No equiparar retirada con certificación del CRUD |
| PAY-01 / SERVICE-01 / FINANCIAL PURCHASE | Módulos legacy/finanzas | Finanzas25acciones410; Service/Proforma fuera de alcance, no fix SQL probado | 41ed99f (J: evidencia documental) | DEPRECATED BY BUSINESS DECISION para Finanzas; STILL OPEN técnico fuera de scope | PRODUCT SCOPE EXCLUDED | 25 acciones410 ceroSQL PASS | No cerrar bugs Service/Proforma como corregidos |
| CupOS / schema | Nuevos límites | Migration plans nueva; servicio transactional failclosed; down restringido | 41ed99f / 0129133 | FIX PRESENT — INTEGRATION QA PENDING | INSTALLATION REQUIRED | SQLite cuota PASS; DB real sin columnas | Aplicar migración/configurar límites mediante autorización posterior |

## Nuevo blocker de integración: ENTRY-R75-01

PurchaseFormReadAccess.php:14 exige edit_entryPurchase, pero routes/web.php:1150 y los consumidores usan update_entryPurchase. DB real devolvió sólo update_entryPurchase. authorize() se ejecuta antes de cargar Entry y catálogos; el editor legítimo queda403. No es ENTRY-R4-01 (reversa financiera) ni el antiguo preload; identidad nueva justificada. Test DevDeliveryIntegrationTest caracteriza el rechazo; no arregla ni concede permisos. Fix mínimo DEV: alinear el consumer con el permiso efectivo ya usado por ruta/roles, conservar membresía/T/C/physical references y negativos cross-context. No crear un permiso alternativo para sortear el fallo.

## Intención, restricciones heredadas y scope

- Retiro demostrado por documentos/gates: Finanzas410, DatosGenerales410, accesslegacy423; pausa industrial/Express/FreeSale/Outputlegacy423. No todos los módulos fuera de scope tienen bloqueo servidor: General/Service/Proforma/Follow/Promotions/Credits no deben etiquetarse automáticamente410.
- Las restricciones read-only de nuestros adapters de Compras normales, Entradas y adjuntos fueron frontend; DEV no declaró su retiro. Fix presente no equivale a botón recuperado: se programa R7.6.
- MAT-01 posterior resuelve expresamente la diferencia con la prohibición sharedmaster de documentación previa MAT-03. No decisión pendiente ni endurecimiento inventado.
- J/P y K: CLOSED BY BUSINESS DECISION — TECHNICAL LIMITATIONS DOCUMENTED. Limitaciones HTTP/Apache/archivos reales y K DECIMAL/concurrencia continúan registradas; no exigimos reabrirlas para este merge.
- Cambio Owner es intencional. FR104 pérdida potencial por nuevo template resuelta con partial existente. Elegir local cerraba dropdown por bubbling; corregido sólo frontend. ENTRY-R75-01 sigue DEV, no se oculta como vacío ni se asignan permisos reales.

## R0–R7

R1 13 originales + diagnósticos y R1.1 iconos conservados (9 renderer tests). R2 apertura SELF/regularización/documento/sesión preservados estáticamente; no ejecutados. R3 Normal vigente, General/Express/Finanzas exclusiones respetadas. R4 21 capacidades recuperadas conservadas; ninguna nueva activada. R5 once Eliminar selección + acumulación marcas/modelos conservados y tests PASS. R6 cuatro campos/flags conservados, StockItem81 revalidado, ajuste bloqueado. R7 export entradas preservado en ambos dashboards autorizados; demás exports/metrics no restaurados.

## Reconciliación de IDs (no duplicar exclusiones ni contar backend como UI)

107 FR únicos; 23 fuera de alcance; 84 activos =62 recuperados +12 candidatos R7.6 +10 aún bloqueados. Las12 son capacidades, NO12 pantallas. No nuevo FR por compartir FR104 con Owner. 0 decisión producto pendiente para esta integración; límites comerciales aún por configurar.

| Grupo | IDs únicos | Total |
|---|---|---:|
| Recuperados | FR-001, FR-002, FR-003, FR-004, FR-005, FR-006, FR-007, FR-008, FR-009, FR-010, FR-011, FR-012, FR-013, FR-014, FR-015, FR-016, FR-019, FR-020, FR-021, FR-035, FR-050, FR-051, FR-052, FR-053, FR-055, FR-056, FR-057, FR-058, FR-060, FR-061, FR-062, FR-065, FR-066, FR-067, FR-068, FR-069, FR-070, FR-071, FR-072, FR-073, FR-074, FR-075, FR-076, FR-077, FR-078, FR-079, FR-081, FR-082, FR-084, FR-085, FR-086, FR-087, FR-089, FR-090, FR-091, FR-092, FR-094, FR-095, FR-099, FR-100, FR-101, FR-104 | 62 |
| Candidatos R7.6 | FR-017, FR-018, FR-036, FR-046, FR-049, FR-054, FR-083, FR-088, FR-093, FR-096, FR-097, FR-098 | 12 |
| Bloqueados | FR-047, FR-048, FR-059, FR-063, FR-064, FR-102, FR-103, FR-105, FR-106, FR-107 | 10 |
| Excluidos producto | FR-022, FR-023, FR-024, FR-025, FR-026, FR-027, FR-028, FR-029, FR-030, FR-031, FR-032, FR-033, FR-034, FR-037, FR-038, FR-039, FR-040, FR-041, FR-042, FR-043, FR-044, FR-045, FR-080 | 23 |

Universo original197 superficies: ledger R7 mantiene40 revisadas/resueltas por producto y157 pendientes semánticas. R7.5 no es R8 ni barrido nuevo; no se descuentan duplicados/newdashboards. QA de negocio separado.

## Inventario canónico residual

Matriz superior prevalece para hallazgos afectados. Para el resto, el cruce con el paquete no aporta evidencia suficiente para cambiar su estado: no se cierran ni se reaudita todo el ERP. Estado histórico base, no nueva certificación.

| ID | Estado base del registro | Tratamiento R7.5 |
|---|---|---|
| CASH-01 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| CASH-02 | NEEDS BUSINESS DECISION | Ver matriz afectada arriba |
| CASH-03 | NEEDS BUSINESS DECISION | Ver matriz afectada arriba |
| CASH-04 | NEEDS BUSINESS DECISION | Ver matriz afectada arriba |
| CASH-05 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| CASH-06 | QA PENDING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| CASH-07 | QA PENDING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PRICE-01 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PRICE-02 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PRICE-03 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PRICE-04 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PRICE-05 | NEEDS BACKEND DESIGN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PRICE-06 | NEEDS BACKEND DESIGN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PRICE-07 | NEEDS BUSINESS DECISION | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PRICE-08 | BLOCKED BY DATASET | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PRICE-09 | QA PENDING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| ENTRY-01 | FIXED / QA PASSED / FRONTEND INTEGRATED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| ENTRY-02 | QA PENDING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| DOC-01 | NEEDS BACKEND DESIGN | Ver matriz afectada arriba |
| DOC-02 | PARTIALLY FIXED | Ver matriz afectada arriba |
| DAILY-01 | ACCEPTED TECH DEBT | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| DAILY-02 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| FIN-01 | BLOCKED BY DATASET | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| FIN-02 | NEEDS BACKEND DESIGN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| FIN-03 | NEEDS BACKEND DESIGN | Ver matriz afectada arriba |
| PAY-01 | PARTIALLY FIXED | Ver matriz afectada arriba |
| BANK-01 | BLOCKED BY DATASET | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| BANK-02 | NEEDS BUSINESS DECISION | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| BANK-03 | NEEDS BUSINESS DECISION | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PUBLIC-01 | BLOCKED BY DATASET | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PUBLIC-02 | QA PENDING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PUBLIC-03 | NEEDS BUSINESS DECISION | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PUBLIC-04 | NEEDS BUSINESS DECISION | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PUBLIC-05 | NEEDS BUSINESS DECISION | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| QUOTE-01 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| QUOTE-02 | NEEDS BUSINESS DECISION | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| QUOTE-03 | NEEDS BACKEND DESIGN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| QUOTE-04 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| QUOTE-05 | QA PENDING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| MAT-01 | PARTIAL QA PASSED / NEEDS BACKEND DESIGN | Ver matriz afectada arriba |
| MAT-02 | NEEDS BACKEND DESIGN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| METRIC-01 | PARTIALLY FIXED | Ver matriz afectada arriba |
| USER-01 | PARTIALLY FIXED | Ver matriz afectada arriba |
| GUIDE-01 | PARTIALLY CLOSED — MODERN READ SCOPE PASS | Ver matriz afectada arriba |
| GUIDE-02 | REPRODUCED — ISOLATED QA | Ver matriz afectada arriba |
| INV-01 | PARTIAL — MODERN KARDEX SCOPE QA PASSED; HTTP QA PENDING | Ver matriz afectada arriba |
| KARDEX-01 | OPEN — LOCAL PROVISIONING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| FOLLOW-01 | NEEDS BACKEND DESIGN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PROMO-01 | NEEDS BACKEND DESIGN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PUR-01 | PARTIALLY FIXED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| POS-01 | QA PENDING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| CUSTOMER-01 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| CUSTOMER-02 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| CUSTOMER-03 | NEEDS BUSINESS DECISION | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| SUPPLIER-01 | NEEDS BUSINESS DECISION | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| AUTH-01 | QA PENDING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| DATA-01 | BLOCKED BY DATASET | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| QA-01 | QA PENDING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| QA-02 | QA PENDING | Ver matriz afectada arriba |
| QA-03 | PARTIALLY CLOSED — TEST DB PASS | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| QA-04 | BLOCKED BY DATASET | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| EXT-01 | QA PENDING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| SCHEMA-01 | FIXED — QA PASSED | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| SALES-NEW-01 | OPEN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| SALES-NEW-02 | OPEN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| TRANSFER-01 | OPEN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| MAT-03 | OPEN | Ver matriz afectada arriba |
| MAT-04 | OPEN | Ver matriz afectada arriba |
| MAT-05 | OPEN | Ver matriz afectada arriba |
| QUALITY-01 | DATA RECONCILIATION PENDING | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PERCENT-01 | OPEN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| EXPENSE-01 | OPEN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| SERVICE-03 | OPEN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| OUTPUT-01 | OPEN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| OUTPUT-02 | OPEN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| WAREHOUSE-01 | OPEN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| WAREHOUSE-02 | OPEN | EVIDENCE REQUIRED para cualquier nuevo cierre; conservar evidencia canónica |
| PROFILE-01 | cierre mutaciones perfil BACKEND READY; upload positivo pendiente | Ver matriz afectada arriba |
| PUR-R3-02 | Ver registro canónico | Ver matriz afectada arriba |
