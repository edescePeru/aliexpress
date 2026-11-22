# REAL NEXT â€” DEV DELIVERY REVIEW â€” 2026-11-22

Baseline c32352c; HEAD 9816321. AuditorÃ­a focalizada, sin implementaciÃ³n frontend ni cambios backend. BitÃ¡cora `Enviar soluciones a Auditor.md` tratada como DEV DELIVERY CLAIMS, no como evidencia de ejecuciÃ³n local. Fechas America/Lima.

## DEV DELIVERY COMMITS

| SHA | Entrega |
| --- | --- |
| ede0552 | Consolidar auditoria y validacion GRE antes del siguiente bloque: acumulado backend, servicios, retiros, migraciones y documentaciÃ³n DEV |
| d9efd28 | Consolidar auditoria GRE, perfiles y retiro comercial legacy: Worker/SELF, retiro PAY, fingerprint/transmisiÃ³n GRE |
| 9816321 | Merge d9efd28 + c32352c: Integrar frontend preservando operaciones de creditos y contrato GRE |

No se atribuyen los commits frontend del segundo padre a DEV. Diff contra baseline: 186 archivos, incluidos cambios DEV en JS/Blades operativos, 8 migraciones, servicios, modelos y rutas. No hay tests nuevos/modificados en el delta respecto a c32352c. Las suites descritas por DEV no fueron entregadas como tests versionados en este repositorio. La prueba MySQL concurrente es un claim explÃ­cito de la bitÃ¡cora, sin script/log reproducible localizado en HEAD.

## Evidence / QA

PHP 7.3.33. Harness temporal `%TEMP%/DeliveryGreReviewTest.php`, derivado del test existente ShippingGuideCreateContractTest, actualizado al contrato emission_key y placa vÃ¡lida: **14 tests / 224 assertions PASS**. SQLite :memory:, no migraciones ni escrituras sobre DB ERP. Proveedor Nubefact fake; Http::fake bloquea red. Escenarios: create, ventas ajenas company/tenant/branch/anulada/reversed, cliente ajeno, catÃ¡logo manual contextual, rollback previo, replay idÃ©ntico, conflicto409, timeout+retry, estados pending/rejected/API_ERROR, fallo tardÃ­o no degrada ACCEPTED, siete rutas autorizadas, permisos directos/rol/list-only/ninguno, perfil propio/ajeno mediante router privado con binding y middleware reales, password invÃ¡lida sin transacciÃ³n abierta, 44 acciones retiradas410 sin SQL, Worker existencia histÃ³rica determinista y exclusiÃ³n de otras companies/tenants.

Los primeros fallos del harness fueron adaptaciÃ³n de fixture (placa antigua invÃ¡lida y description requerida en permissions), corregidos Ãºnicamente en archivo temporal. Los tests versionados antiguos caracterizan ausencia de permisos y doble envÃ­o: deben actualizarse por DEV; no se presentan como regresiÃ³n funcional del fix.

Perfil: settings propio y password propio mutan sÃ³lo SQLite; imagen propia sin archivo alcanza422; ajenos403 antes de FormRequest y acciÃ³n, sin cambios en filas. No se certificÃ³ upload positivo ni filesystem real. No se usaron contraseÃ±as reales en fixtures ni docs. Required Password Change no reabierto.

Lecturas MySQL con START TRANSACTION READ ONLY/ROLLBACK: Worker C1 devuelve ID2 have_contract=false dos veces idÃ©nticas; C2 y Gamarra vacÃ­os dos veces. Prueba positiva de contrato histÃ³rico soft-deleted en SQLite devuelve true, sin contrato false. SQL preserva WorkerAccess y enable=0.

HTTP real con login autorizado y CSRF de login, tras `artisan permission:cache-reset` (PASS):

| GET | Owner EDESCE | Owner Gamarra |
| --- | --- | --- |
| /dashboard/kardex | 200 | 200 |
| /dashboard/stock-items/select | 200 | 200 |
| /dashboard/warehouses/select | 200 | 200 |
| /dashboard/get/workers/enable | 200 | 200 |
| /dashboard/shipping-guides/create?type=remitente | 200 | 200 |
| /dashboard/listado/sales/select2/ | 200 | 200 |

No POST de negocio al servidor local. Roles limitados/denegados y perfil se probaron aisladamente, no con cambios de roles reales. Store HTTP con auth/CSRF real sigue pendiente. Kardex visual permanece integrado; no se tocÃ³ KardexMovementQuery. Smoke positivo de vista/selectores cerrado; migraciÃ³n de vista SQL nueva pendiente por separado.

## Findings matrix

| Finding | Previous state | DEV change | QA evidence | Final state | Frontend consequence |
| --- | --- | --- | --- | --- | --- |
| GUIDE-01 Create | OPEN | create_referralGuide formalizado en 7 rutas y controller; contexto T/C/B | siete rutas + middleware Spatie directo/rol permitido, list-only/ninguno403; scope con IDs; GET Owners200 | FIXED / QA PASS focalizado; Store HTTP/CSRF pendiente | Create Remitente desbloqueado para integraciÃ³n de formulario |
| GUIDE-02 | OPEN | emission_key, fingerprint, replay,409, lock company y unique | replay/conflicto/retry PASS fake; concurrencia MySQL reportada DEV sin artefacto entregado; migraciones locales pendientes | PARTIAL de cierre verificable/instalaciÃ³n; implementaciÃ³n secuencial QA PASS | no habilitar emisiÃ³n operativa local todavÃ­a |
| GRE failure | SENT ambiguo | submission_state + snapshots + failure conservador | timeout UNKNOWN; retry no send; late failure conserva ACCEPTED; pending/rejected/API_ERROR PASS | FIXED / QA PASS aislado; instalaciÃ³n/HTTP pendiente | contrato visual definido; no certifica proveedor |
| WORKER ENABLE | rand | subquery contracts.worker_id | histÃ³rico incluye deleted; repeticiÃ³n MySQL y SQLite; HTTP ambos200 | FIXED / QA PASS | worker.indexEnable READY |
| PROFILE-01 | sin SELF | EnsureOwnProfile + SelfProfileAccess en las tres acciones | propio settings/password200, imagen sin archivo422; ajenos403; hash ajeno intacto; transaction0 tras password invÃ¡lida | FIXED / HTTP QA PENDING | cierre mutaciones perfil BACKEND READY; upload positivo pendiente |
| PAY-01 | SQL1054 en dos ediciones | retiro intencional 27+17 acciones | 44 acciones410 sin SQL, sin acceso a documentos; scope PaymentDeadline intacto | CLOSED BY RETIREMENT para OrderService/Proforma | ambos SKIP / RETIRED; no READY |
| MAT-03 | OPEN | sin cambio en update fÃ­sico | diff sÃ³lo selectores Material; request intacto | OPEN | Save retacerÃ­a bloqueado |
| MAT-04 | OPEN | NOT TOUCHED | UpdateMaterialRequest sin delta | OPEN | multinivel sigue bloqueado |
| MAT-05 | OPEN | NOT TOUCHED | casts previos sin delta | OPEN | flags siguen bloqueados |
| MAT-01 | diseÃ±o pendiente | NOT TOUCHED | no decisiÃ³n entregada para historia/stock/reservas/omisiÃ³n/maestro | DESIGN DECISION | no cerrar Save global |
| CASH-02 | decisiÃ³n moneda histÃ³rica | NOT TOUCHED | sin delta CashBox | DECISION OPEN | independiente de GRE/Worker/Profile |
| CASH-03 | decisiÃ³n is_deferred | NOT TOUCHED | sin delta Subtype | DECISION OPEN | no cerrar ediciÃ³n histÃ³rica |
| CASH-04 | decisiÃ³n desactivaciÃ³n | NOT TOUCHED | sin delta Subtype | DECISION OPEN | no cerrar toggle referenciado |

## GRE exact contract

Rutas: shipping_guides.create, shipping_guides.store, sales.select2, sales.items, shipping_guides.sale_items, shipping_guides.customer_payload, shipping_guides.products: permission:create_referralGuide. AutorizaciÃ³n denegada detiene antes de Store/proveedor; no confundir con permisos de documentos. Customer tenant y ventas T/C/B conservados.

ShippingGuideEmission::existing exige misma clave/contexto/huella, mismo local y tipo7; histÃ³rico sin huella409. Store consulta antes de validar y tras lock de company. Unique de operaciÃ³n: tenant_id/company_id/emission_key; documento mantiene unique company/document. Huella excluye CSRF y clave, incluye contexto servidor/campos admitidos. No deduplica ventas distintas por sale_id ni formularios nuevos con claves diferentes.

Estados reales: REGISTERED, UNKNOWN, API_ERROR, PENDING_SUNAT, ACCEPTED, REJECTED. IDENTITY_CONFLICT domina representaciÃ³n. El enum status legado coexiste con submission_state; no aÃ±adir estados desde frontend. Antes de commit, fallo422 sin fila; despuÃ©s persistido true con ID/serie/numero. UNKNOWN se guarda antes de HTTP; HTTP4xx salvo cÃ³digo23 se clasifica API_ERROR; timeout/5xx/23 quedan UNKNOWN. Snapshot evita degradaciÃ³n por error tardÃ­o. Retry Store devuelve operaciÃ³n sin reenviar. RecuperaciÃ³n separada exige permisos, versiÃ³n, consulta previa y consentimiento de generaciÃ³n; una reserva persistente impide bucles/reenvÃ­os automÃ¡ticos. RecuperaciÃ³n inspeccionada, no ejecutada.

## Migrations installed

`migrate:status` ejecutado con PHP7.3.33. Las ocho nuevas son **PENDING**; ninguna aplicada por esta revisiÃ³n.

| Migration | Estado | Finalidad |
| --- | --- | --- |
| 2026_11_18_000001_decimal_supplier_credit_payments | PENDING | DECIMAL abonos; rechaza tabla con historial, requiere conciliaciÃ³n previa |
| 2026_11_18_000002_supplier_credit_payment_requests | PENDING | identidad de pago/retry |
| 2026_11_19_000001_shipping_guide_emission_key | PENDING | clave y unique de operaciÃ³n GRE |
| 2026_11_19_000002_harden_inventory_movement_references | PENDING | integridad de referencias de vista SQL |
| 2026_11_19_000003_track_customer_contact_deletion_batches | PENDING | trazabilidad de cascada cliente/contacto |
| 2026_11_20_000002_shipping_guide_identity_conflicts | PENDING | cuarentena de conflictos GRE |
| 2026_11_20_000003_gre_recovery_and_private_diagnostics | PENDING | intentos de recuperaciÃ³n y diagnÃ³sticos privados |
| 2026_11_20_000004_gre_request_fingerprint_and_submission_state | PENDING | huella y estado operativo de transmisiÃ³n |

No certificar constraints instaladas por existir su migraciÃ³n en Git. La ausencia local de schema impide declarar GRE OPERATION BACKEND READY=YES. No recomendar migrate indiscriminado: la conversiÃ³n de abonos tiene precondiciÃ³n explÃ­cita.

## DEV claims / DEMO / documents

DEV DOCUMENT CLAIMS MATCH HEAD: PARTIAL. Cambios de cÃ³digo prioritarios coinciden. No encontrados como artefactos en HEAD: suites nuevas DEV y prueba concurrente MySQL reproducible. La evidencia DEMO no reside necesariamente en Git: lectura local `shipping_guides where serie=TTT1` devuelve [] (no se consultaron URLs de proveedor ni documentos). TTT1-3 ACCEPTED no corroborado en esta DB; no significa falsedad de la bitÃ¡cora de otro entorno.

Document security separado: ShippingGuideDocumentController valida permiso print/download, tipo, contexto y binding forCurrentLocal; JSON oculta links/base64/payloads. Ruta shipping_guides.download presente. Resolver aplica control de conflictos y validaciÃ³n de URL/ZIP. No se descargÃ³ archivo ni se certificÃ³ correspondencia. DOCUMENT SECURITY PARTIAL / QA PENDING; no bloquea la integraciÃ³n visual Create.

## Other DEV changes / readiness consequences

- ReferralGuide legacy, FOLLOW-01 y PROMO-01: controllers/rutas a retiro410; datos/modelos histÃ³ricos conservados. SKIP / RETIRED, no nuevos candidatos. Los modelos de promociones ademÃ¡s bloquean escrituras legacy; no se probaron transacciones comerciales.
- CUSTOMER-03: servicio CustomerDeletionLifecycle y lote de borrado; implementaciÃ³n presente, migraciÃ³n pendiente, mutation QA no reejecutada. PARTIAL.
- SUPPLIER-01: SupplierAccountRead con permiso/contexto tenant y arrays de permisos efectivos; read path reforzado. HTTP/exports restantes pendientes; no cierre global.
- AUTH-01: getAllPermissions en Customer/Contact/Supplier/Accounts/StockItems y crÃ©ditos. Cierre parcial por consumidores, no permiso global corregido.
- INV-01: servicios de variantes, niveles, StockItems, Items, costos, SQL y Kardex. Variantes es candidato acotado de revalidaciÃ³n tras refuerzo (ya no asumir blocker antiguo intacto), no se certifica todo Inventory ni se hace discovery nuevo. No sustituye MAT-01/03/04/05. MigraciÃ³n de vista pendiente.
- PUR-01: precisiÃ³n/retry/adjuntos privados de crÃ©ditos y guard de orden financiera con crÃ©dito. Dos migraciones pendientes; no declarar mutaciones financieras READY. Merge preserva UI operativa de crÃ©ditos: no es implementaciÃ³n frontend realizada en esta auditorÃ­a.
- QA-01/02/03/04: documentaciÃ³n DEV de pruebas/reportes/maestros/traslados/restauraciÃ³n, servicios instalados donde hay diff. No entrega de suites nuevas versionadas; permanecen PARTIAL en el alcance integrado, sin reabrir todos sus mÃ³dulos.
- EXT-01: HTTP exception/diagnÃ³sticos/recuperaciÃ³n/versionado config/snapshots presentes. CertificaciÃ³n externa y archivos siguen pendientes; no emisiÃ³n real en esta revisiÃ³n.

## Priorities / decision

| Priority | Surface | Readiness | Business value | Next action |
| --- | --- | --- | --- | --- |
| 1 | Shipping Guide Create Remitente | FRONTEND UNLOCKED (formulario); operaciÃ³n instalada PARTIAL | Alto | integrar visualmente contrato actual; instalar schema y completar Store HTTP/concurrencia reproducible antes de habilitaciÃ³n operacional |
| 2 | worker.indexEnable | FRONTEND UNLOCKED | Medio | integrar read path; restore permanece fuera |
| 3 | My Profile mutation closure | BACKEND READY / HTTP QA PENDING | Medio | cierre SELF HTTP/upload local controlado; perfil visual ya integrado |
| â€” | OrderService / Proforma | SKIP / RETIRED | N/A | no integrar |
| â€” | Material Edit Save | WAIT FOR DEV | Alto | MAT separados pendientes |

NEWLY UNLOCKED FRONTEND SURFACES: 3 alcances (2 pantallas + 1 cierre de mutaciones). Highest value: GRE. Fastest nueva pantalla: Worker. Kardex smoke cerrado, no nueva pantalla. No se cuenta Variantes como ready certificado sin su aceptaciÃ³n focalizada.

REGRESSIONS FOUND: ninguna funcional nueva demostrada en los escenarios ejecutados. Hay incompatibilidad de despliegue schema/cÃ³digo (8 pendientes) y tests de caracterizaciÃ³n GRE obsoletos: no confundirlos con QA PASS instalada ni ocultarlos. Otros caminos no cubiertos no quedan certificados.

SÃ³lo documentaciÃ³n modificada. Sin Blade/JS/CSS/allowlist/backend edits, sin commit. git diff --check PASS.


## Post-migration delta — 2026-11-22 (supersedes installation conclusions above)

HEAD sigue 9816321. Revisión limitada al schema recién instalado, sin reabrir auditoría ni modificar aplicación. `migrate:status` PHP7.3.33: **8/8 Ran, batch179**: GRE4, Supplier Credits2, inventory view1, Customer/Contact1. Ninguna migración ejecutada por el auditor.

### Schema REAL vs migrations

SHOW CREATE TABLE / SHOW INDEX consultados en MySQL dentro de lectura segura; evidencia temporal `%TEMP%/postmigration-schema.json`.

- shipping_guides: emission_key char36 nullable; emission_fingerprint char64 nullable; submission_state varchar32; submission_http_status unsigned smallint; submission_provider_code varchar8; submission_diagnostic_reference char24; identity_conflict_at. UNIQUE shipping_guides_emission_unique(tenant_id,company_id,emission_key) y gre_company_document_unique(tenant_id,company_id,tipo_de_comprobante,serie,numero), índice gre_local_lookup presentes.
- Conflictos: tabla, evidencia cifrada/hash, columnas tenant/company/branch y UNIQUE shipping_guide_id presentes. Recuperación: PK id, UNIQUE shipping_guide_id, contexto/user, state/phase/evidencia presentes. Diagnósticos: PK reference, guía/contexto/código/estadoHTTP/evidencia presentes. Tablas InnoDB.
- Estas migraciones GRE añaden columnas de contexto e índices, NO foreign keys SQL hacia tenant/company/branch/guide. El schema instalado coincide; no afirmar FKs inexistentes. Las validaciones de relación siguen siendo backend.
- Créditos: credit_pays.amount DECIMAL(12,2), tabla supplier_credit_payment_requests con tenant/company/request_key/payload_hash/IDs, UNIQUE supplier_payment_request_unique y el índice de supplier_credit_id. Esa tabla nueva tampoco declara FKs; la FK existente credit_pays→supplier_credits sigue presente.
- Customer/contact: customer_deletion_batch CHAR(36) nullable en ambas tablas; contact_customer_deletion_batch_idx(tenant_id,customer_id,customer_deletion_batch) presente. FKs previas conservadas.
- inventory_movements_view: SHOW CREATE VIEW presente; se ejecutó sólo el SELECT del SQL up y down, nunca CREATE/REPLACE. 107 filas instaladas =107 del up =107 de definición previa sobre dataset actual; comparación de filas completas ordenadas idéntica. T1/C1=104,T1/C2=2,T3/C3=1. No se infiere una comparación temporal de datos que no fue capturada.

**CODE/SCHEMA MISMATCH: RESOLVED para las ocho migraciones auditadas.** No se extiende a todo el schema ERP.

### Focalized tests

- Harness GRE temporal anterior, seleccionando sólo GRE: **6 tests /42 assertions PASS**, SQLite privado/provider fake: misma key+payload misma guía; distinta huella409; retry no reenvía; clave nueva genera guía diferente/correlativos1 y2; rollback previo; UNKNOWN tras timeout; API_ERROR; PENDING_SUNAT; ACCEPTED; REJECTED; error tardío no degrada ACCEPTED. Se añadió únicamente el caso separado de nueva operación para cubrir el requisito actual; no se alteraron tests históricos ni assertions para ocultar fallos. Sin Nubefact real.
- SupplierCreditReadPathTest existente: **4 tests /28 assertions PASS** en SQLite: scope, saldo, vencimiento read-only y unicidad crédito/entrada. No equivale a certificar concurrencia/retry de abonos ni a mutaciones financieras integradas.
- No se encontró suite aislada CustomerDeletionLifecycle en tests/scripts entregados. No se fabricó suite ni se ejecutó delete/restore compartido. CUSTOMER-03 pierde el blocker de instalación; su QA funcional restante continúa pendiente.
- Búsqueda nueva de artefactos en tests/scripts/comandos/helpers/docs: HEAD sin cambios; no suite concurrente MySQL reproducible entregada. **MYSQL CONCURRENCY EVIDENCE — DEV CLAIM ONLY**. Schema supports concurrency contract: YES; concurrent execution reproduced locally: NO. No se construyó prueba concurrente nueva.

### Historical GRE tests (unchanged)

ShippingGuideCreateContractTest: 8 tests/33 assertions, 5 errors y1 failure. Clasificación STALE, no fixed by migration:

| Test | Fallo actual | Clasificación |
| --- | --- | --- |
| sales_lookup_and_both_item_endpoints_reject_foreign_or_ineligible_sales | payload Store sin emission_key requerido | STALE fixture |
| fake_send_after_commit_and_duplicate_submit_is_not_idempotent | falta emission_key; además espera duplicación ya corregida | STALE characterization |
| provider_failure_keeps_sent_guide_and_returns_502 | falta emission_key | STALE fixture |
| series_mismatch_and_payload_failure_do_not_leave_a_guide | falta emission_key antes del escenario específico | STALE fixture |
| documents_missing_granular_create_and_store_permissions | strpos recibe Closure middleware; además espera autorización ausente | STALE assertion |
| manual_catalog_and_item_ownership_use_real_catalog_service | espera error items, pero validación previa exige emission_key | STALE fixture |

Create/catalog y validaciones restantes pasan; esos tests usan SQLite propio, por lo que migrar MySQL no corrige sus fixtures. Sin cambios al archivo versionado. No fallos clasificados como regresión real ni unrelated en esta corrida.

### Inventory HTTP smoke after migration

Sesiones reales Owner EDESCE y Owner Gamarra: GET /dashboard/kardex, /dashboard/stock-items/select, /dashboard/warehouses/select =200 ambos. Producto elegido desde selector autorizado: StockItem8 EDESCE → Kardex200/9 movimientos; StockItem106 Gamarra → Kardex200/0 movimientos. Parámetro de ruta denominado materialId, pero contrato real usa StockItem; no se confundieron IDs. Sin writes/exports/stock. Visual integrado permanece cerrado, no responsive nuevo ni KardexMovementQuery editado.

### Updated conclusions

| Finding | Antes | Ahora |
| --- | --- | --- |
| GUIDE-01 | FIXED / QA PASS focalizado | Sin cambio |
| GUIDE-02 | PARTIAL por instalación + concurrencia no reproducible | **FIXED / MYSQL CONCURRENCY QA PENDING**; schema real correcto y pruebas secuenciales fake PASS |
| GUIDE failure | FIXED / QA PASS aislado | Sin cambio; schema instalado |
| GRE operation readiness | PARTIAL | PARTIAL: sólo concurrencia reproducible y HTTP Store/auth/CSRF pendiente; schema ya no bloquea |
| GRE Create frontend | UNLOCKED | YES; próximo frontend recomendado, documentos no bloquean |
| Supplier Credits | instalación pendiente | Schema OK; read surfaces integradas sin cambio; no certificación de pagos |
| Inventory view | instalación pendiente | OK; smoke y comparativa107 PASS |
| CUSTOMER-03 | instalación pendiente | Schema OK; QA de lifecycle restante pendiente |

TTT1-3: EXTERNAL DEMO EVIDENCE — NOT PRESENT LOCALLY. Su ausencia no es fallo de migración; no se buscó recrearla ni emitir.

NEW REGRESSIONS: NONE en el alcance probado. Sólo este documento y addendum del backlog actualizados en esta fase. El cambio previo de readiness se conserva, no se editó nuevamente. Sin frontend/backend/test versionado/migraciones modificados, sin documentos descargados, sin commit. git diff --check PASS.

## Post-migration delta — 2026-11-22 (estado vigente)

Esta sección reemplaza las conclusiones anteriores de instalación pendiente, conservadas como historial. HEAD sigue9816321. PHP7.3.33 migrate:status: **8/8 Ran, batch179**; GRE4, SupplierCredits2, inventory view1, customer/contact1. No migraciones ejecutadas por auditor.

### Schema real

SHOW CREATE TABLE y SHOW INDEX contrastados contra las ocho migrations; evidencia temporal postmigration-schema.json. shipping_guides: emission_key char36, emission_fingerprint char64, submission_state varchar32, submission_http_status unsigned smallint, submission_provider_code varchar8, submission_diagnostic_reference char24 e identity_conflict_at presentes, con nulabilidad prevista. UNIQUE shipping_guides_emission_unique(tenant_id,company_id,emission_key), gre_company_document_unique(tenant_id,company_id,tipo_de_comprobante,serie,numero) y gre_local_lookup presentes.

Conflictos: contexto tenant/company/branch, evidencia cifrada/hash y UNIQUE shipping_guide_id. Recuperación: PK id, UNIQUE shipping_guide_id, contexto/user/state/phase/evidencia. Diagnósticos: PK reference, guía/contexto/código/HTTP/evidencia. Tablas InnoDB. Estas migrations añaden columnas de contexto, NO FKs SQL hacia tenant/company/branch/guide: coincide con HEAD; no afirmar relaciones SQL que no existen.

Créditos: credit_pays.amount DECIMAL(12,2); supplier_credit_payment_requests con contexto, request_key, payload_hash, IDs, UNIQUE supplier_payment_request_unique e índice supplier_credit_id. Tabla nueva no declara FKs; credit_pays conserva su FK previa a supplier_credits.

Customer/contact: customer_deletion_batch CHAR36 nullable en ambas tablas, índice contact_customer_deletion_batch_idx(tenant_id,customer_id,customer_deletion_batch) presente; FKs previas conservadas.

Inventory: SHOW CREATE VIEW revisado; ejecución exclusivamente SELECT de definiciones up/down. Vista instalada107 filas, SELECT up107, definición down107 sobre dataset actual, filas completas idénticas tras ordenar. T1/C1=104,T1/C2=2,T3/C3=1. No es una captura temporal previa/posterior: compara definiciones sobre los mismos datos actuales. No CREATE VIEW ejecutado.

**CODE/SCHEMA MISMATCH: RESOLVED para las ocho migrations revisadas.**

### Tests focalizados

- Harness GRE temporal anterior: **6 tests/42 assertions PASS**, SQLite privado/provider fake. Replay idéntico misma guía, diferente huella409, timeoutUNKNOWN/retry sin resend, API_ERROR/PENDING_SUNAT/ACCEPTED/REJECTED, fallo tardío no degrada aceptación, rollback previo. Caso adicional independiente solicitado: nueva clave genera otra guía y correlativos1/2. No se alteraron tests históricos para ocultar fallos; sin Nubefact real.
- SupplierCreditReadPathTest existente: **4 tests/28 assertions PASS**. Scope, saldo, vencimiento read-only y unicidad crédito/entrada. No certifica concurrencia/retry de abonos ni operaciones financieras completas.
- No suite CustomerDeletionLifecycle aislada localizada en tests/scripts entregados. No se inventó suite ni se ejecutó delete/restore compartido. CUSTOMER-03 pierde blocker instalación; QA funcional restante pendiente.
- Nueva búsqueda tests/scripts/comandos/helpers/docs: ninguna suite MySQL concurrente reproducible entregada. **MYSQL CONCURRENCY EVIDENCE — DEV CLAIM ONLY**. Schema supports concurrency contract:YES; concurrent execution reproduced locally:NO. No se construyó nueva prueba concurrente.

### Historical GRE tests — STALE

ShippingGuideCreateContractTest sin modificar:8 tests/33 assertions,5 errors/1 failure. No fixed by migration: usa SQLite propia.

| Test | Fallo | Clasificación |
| --- | --- | --- |
| sales_lookup_and_both_item_endpoints_reject_foreign_or_ineligible_sales | Store fixture sin emission_key | STALE |
| fake_send_after_commit_and_duplicate_submit_is_not_idempotent | sin emission_key; además espera duplicación corregida | STALE |
| provider_failure_keeps_sent_guide_and_returns_502 | sin emission_key | STALE |
| series_mismatch_and_payload_failure_do_not_leave_a_guide | sin emission_key antes del escenario específico | STALE |
| documents_missing_granular_create_and_store_permissions | strpos recibe Closure; assertion espera ausencia de autorización | STALE |
| manual_catalog_and_item_ownership_use_real_catalog_service | espera items, pero validación previa exige emission_key | STALE |

Los dos casos restantes pasan. Ningún fallo clasificado como regresión real o unrelated. Archivo versionado intacto.

### Inventory HTTP smoke

Owner EDESCE y Owner Gamarra: GET /dashboard/kardex, /dashboard/stock-items/select, /dashboard/warehouses/select =200 ambos. Producto elegido desde selector autorizado: StockItem8 EDESCE→200/9 movimientos; StockItem106 Gamarra→200/0 movimientos. Ruta llama materialId al parámetro pero el contrato real es StockItem; IDs no confundidos. Sin cambios de stock/exports. Frontend visual permanece cerrado, sin reauditar INV-01.

### Updated conclusions

| Punto | Ahora |
| --- | --- |
| GUIDE-01 | FIXED/QA PASS focalizado; sin cambio |
| GUIDE-02 | FIXED / MYSQL CONCURRENCY QA PENDING (antes PARTIAL) |
| GUIDE failure | FIXED / QA PASS aislado; schema instalado |
| GRE operation readiness | PARTIAL: concurrencia reproducible y Store HTTP/auth/CSRF pendientes; schema ya no bloquea |
| GRE Create frontend | YES / UNLOCKED; próxima integración; documentos no bloquean |
| Supplier credits | schema OK; fachadas integradas sin cambio, pagos no certificados |
| Inventory view | OK; comparativa107 y smoke PASS |
| CUSTOMER-03 | instalación resuelta; lifecycle QA restante pendiente |

TTT1-3: **EXTERNAL DEMO EVIDENCE — NOT PRESENT LOCALLY**. Ausencia esperada, no fallo de migration. No se recreó, emitió ni descargó documento.

NEW REGRESSIONS:NONE en alcance probado. Sólo este documento y backlog actualizados; readiness conserva cambio previo, sin nueva edición. Sin aplicación/backend/frontend/tests versionados modificados, sin commit. git diff --check PASS.
