# REAL NEXT — R7.5 — Full DEV Delivery Integration

Fecha: 2026-11-25. Rama destino: feature/venti-next-frontend. Código integrado; instalación local incompleta. No push, despliegue, R7.6 masivo ni R8.

## Git y procedencia

- Rama encontrada al iniciar: multitenancy, limpia, HEAD `a0ca809b07dbe0911ab7449a3db862a68a0ceb87`; R0–R7 ya estaban comprometidos (3560b17,3914020,a0ca809). No se fabricó otro checkpoint.
- Puntero frontend al inicio: `744d28b81aee93167cf1f5b5bcd72477d005bd0b`. Se hizo fast-forward a `a0ca809b07dbe0911ab7449a3db862a68a0ceb87` para conservar las recuperaciones antes del merge DEV.
- SHA frontend consolidado / respaldo: `a0ca809b07dbe0911ab7449a3db862a68a0ceb87`; rama `backup/venti-next-pre-dev-20261125`.
- DEV: origin/multitenancy `bf6e834badf7b9d186f1465861d7bdcff591fd4b`. Fetch verificado contra origin https://github.com/edescePeru/aliexpress.git.
- Merge base: `744d28b81aee93167cf1f5b5bcd72477d005bd0b`.
- Tres commits entrantes: 41ed99f (paquetes/cupos/Owner),0129133 (Platform),bf6e834 (merge remoto).
- Merge controlado --no-commit --no-ff: 123 archivos automáticos; cero conflictos de contenido/modify-delete/rename. No ours/theirs global.
- SHA final de merge: PENDING_LOCAL_MERGE_SHA (se registra después del commit mediante actualización documental; no se puede almacenar el hash de un commit dentro de sí mismo).
- multitenancy local se conserva en `a0ca809b07dbe0911ab7449a3db862a68a0ceb87`. Sin push.

## Reconciliación semántica, aunque no hubo conflictos textuales

1. DEV redirige Owner a dashboard.owner incluso con contexto. Se conserva ese contrato. Para no perder FR-104, se extrajo el bloque/modal de Exportar entradas de R7 a dashboard/partials/entry-reports, usado por ambos dashboards con los mismos permisos. En Owner sólo aparece con company Y branch válidas. No ejecución del export ni alteración de su endpoint.
2. Elegir local abría el dropdown y Bootstrap lo cerraba al recibir el mismo click propagado. Reproducido con Chrome aislado; se añadió preventDefault/stopPropagation exclusivamente al trigger data-owner-company. No cambió selección, payload, guard ni handler de guardado.
3. Se actualizaron tests antiguos que caracterizaban errores corregidos de avatar/flags/moneda. Los rechazos nuevos son el contrato vigente, no una regresión a eludir. Tests siempre en SQLite :memory:.
4. Rutas/Controllers/Services/migración de DEV permanecen idénticos al parent DEV. No resolución backend propia. Allowlist y CSS Venti Next conservados. DEV trae CSS inline de Owner/Platform; preservado, no CSS nuevo de integración.
5. Adaptadores R1–R6 no pierden acciones, iconos, guards, payloads o handlers. Ventas ya usa lector autorizado por sale ID sin fallback público. Documentos de Entradas todavía retirados de UI son recuperación futura, no retiro intencional.

## Paquetes confirmados y origen

Todos los temas A–S del adjunto tienen correspondencia en código o documentos. No existen commits individuales por paquete: el grueso está agregado en 41ed99f. J/K incluyen evidencia documental; no adjudicarles un cambio de aplicación inexistente en este diff. No se entregaron nuevos tests ejecutables en el diff tests/: las 22 suites privadas declaradas por DEV no se presentan como ejecutadas aquí.

| Paquete | Evidencia en HEAD integrado | Resultado |
|---|---|---|
| Cupos / Owner | TenantPlanService, OwnerWorkspace, nueva migración, TenantCompany/Branch, TenantController, PlanController | Código presente; configuración e instalación pendientes |
| A PROFILE-01 | ProfileAvatarService, UserController, profile.js | Imagen real/GD/UUID/transaction/cleanup; tests aislados PASS |
| B MAT-03/04/05 | MaterialEditValues, MaterialInventoryScope, UpdateMaterialRequest | Flags/posiciones/contexto/omitidos preservados |
| M MAT-01 | MaterialRetaceriaPolicy + AUDITOR-MAT-01-DECISION | No bloquear historial, stock ni maestro compartido por regla general; Items históricos no se reescriben |
| C Entradas | PurchaseFormReadAccess, EntryController, JS V1/V2 con consumer | Create candidato; Edit bloqueado por mismatch de permiso ENTRY-R75-01 |
| Q Compras normales | NormalPurchaseReadAccess, OrderPurchaseController, create/editNormalV2 | Company-wide, catálogos con scope de stock, onlyTrashed, códigos faltantes finitos |
| D Cotización venta | QuoteFormReadAccess, QuoteSaleController | Capacidades create/edit, contactos contextuales y comprobante edit; sin emitir |
| L CASH-02/03/04 | CashConfigurationGuard, CashBox/Subtype/CompanyBankAccount | Moneda/modalidad históricas protegidas, pendientes impiden desactivación |
| P Ventas | SaleDocumentFile, SaleListDocumentController, PuntoVentaController | Lectores y escritores protegidos; R1 sin URL pública alternativa |
| P Entradas | EntryDocumentAccess/File/ExtraDocuments, EntryDocumentController, routes, public/.htaccess | Main/observation/extras por contexto; path público pasa a autorización |
| P Cotizaciones | QuoteDocumentFiles, QuoteSale/Quote, plantillas export, .htaccess planos/pdfs | No borrar archivos ajenos; lectores/escritores por contrato |
| P Créditos | SupplierCreditDocumentAccess/Attachments y AttachmentController | Preservado; módulo fuera de recuperación activa |
| J GRE / K Créditos | AUDITOR-J-MYSQL-AISLADO, JP/K validación y J-P-K-CIERRE-NEGOCIO | Cierre negocio con limitaciones, sin reabrir suites excluidas |
| E/N/R | OperationalModules + gates Quote/Output/FreeSale/Express | Pausas reales antes del negocio; shared endpoints conservados |
| F/G/I/O | OrderPurchaseFinanceController + retired Blade | 25 acciones 410 antes de SQL; histórico y código conservados |
| H/S | gates legacy-access / legacy-settings y sidebar | Administración antigua bloqueada; Usuarios Web/Configuraciones vigentes |
| Platform | PlatformDashboardSummary + dashboard, commit0129133 | Global, no TenantContext; schema local pendiente |
| Tienda enlaces | public-store-links + docs/flujos/TIENDA-WEB-ENLACES-LOCALES | Enlaces contextuales DEV preservados; ningún cambio propio Store Web |

## Flujos, contexto y navegación

- Login Owner limpia intended y company/branch, conserva tenant. dashboard.principal es la ruta de entrada compartida; no existe otro URL Owner inventado. Owner permanece en su dashboard administrativo tras seleccionar contexto.
- Sin contexto: administración tenant; operación denegada. Con contexto: menú operativo según permisos efectivos/membresías, sin Owner bypass. Operador conserva dashboard.dashboard. Platform exclusivo/global, limpia contexto.
- Selección de empresa carga sólo locales autorizados; NO elige primero automáticamente. Cambio de contexto POST también escribe is_default en company_user/branch_user: no se ejecutó contra DB compartida. La prueba del POST fue interceptada (422 simulado).
- Cupos cuentan activos, incluyendo locales activos de empresas inactivas; reducción no elimina existentes; NULL no es ilimitado. Configurar valores es decisión comercial, no fixture QA.
- Reporte Órdenes por Material continúa vigente con permiso report_orderPurchaseExpress. Invoice no es Financial Purchase; QuoteSale no es Quote industrial.

| Pantalla/flujo | URL local exacta | Estado |
|---|---|---|
| Owner / operador | http://127.0.0.1:8080/dashboard/principal | Render según actor; Owner homologación completa pendiente |
| Platform | http://127.0.0.1:8080/dashboard/plataforma | Requiere nueva migración; homologación nueva pendiente |
| Empresas Owner | http://127.0.0.1:8080/dashboard/empresas | Cupos nuevos; no crear/reactivar en QA |
| Ventas | http://127.0.0.1:8080/dashboard/listado/ventas | R1 preservado |
| Error SUNAT | http://127.0.0.1:8080/dashboard/listado/ventas/error | R1.1 iconos/acciones preservados |
| Caja Admin | http://127.0.0.1:8080/dashboard/cash-movements/admin | R2 preservado |
| Compras normales | http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2 | R3 vigente |
| Entradas | http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2 | R4 preservado; edit no desbloqueado |
| Facturas | http://127.0.0.1:8080/dashboard/factura/compra | Vigente; no confundir con retiro Finanzas |
| StockItems | http://127.0.0.1:8080/dashboard/stock-items | R6 preservado |
| Items81 | http://127.0.0.1:8080/dashboard/view/stock/material/items/81 | ID revalidado: tenant1 material15 |

## Instalación pendiente (NO ejecutada)

Una migración entrante, migrate:status=No: 2026_11_21_000001_add_structure_limits_to_plans. Añade plans.max_active_companies y max_active_branches unsigned nullable; no índices/FK nuevos ni seeder/permiso/env/dependencia Composer nuevos. down() rechaza rollback destructivo deliberadamente.

PlatformDashboardSummary actualmente produce SQLSTATE42S22 por columnas faltantes en DB local. No es instalación completa ni prueba HTTP Platform PASS. Owner muestra Sin configurar; no se asignaron límites ficticios.

Procedimiento autorizado posteriormente, con respaldo verificado de DB y plan de recuperación (down no disponible):

```powershell
Set-Location C:\wamp64\www\venti-adminlte-ui\aliexpress
& 'C:\wamp64\bin\php\php7.3.33\php.exe' artisan migrate:status
& 'C:\wamp64\bin\php\php7.3.33\php.exe' artisan migrate --path=database/migrations/2026_11_21_000001_add_structure_limits_to_plans.php
& 'C:\wamp64\bin\php\php7.3.33\php.exe' artisan migrate:status
```

Después: Platform define máximos aprobados de cada plan (no decisión tomada aquí), revisar Owner/Platform/cupos. No seeders ni cambio de roles. No ejecutar config:cache sin procedimiento del entorno. Composer/package/config no cambiaron.

Apache: conservar public/.htaccess (rewrite images/entries antes de !-f) y denegación de public/images/credits, public/images/planos, public/pdfs/quotes. Verificar AllowOverride/mod_rewrite y DocumentRoot public en despliegue autorizado. El servidor PHP :8080 no certifica Apache. No cambiamos Apache global ni reabrimos la certificación J/P excluida por negocio. No declarar todo public seguro.

## QA focalizado / evidencia

- PHPUnit PHP7.3: 71 tests / 832 assertions PASS en 7 suites, SQLite :memory: explícito. Profile16/Material incluidos, Cash reglas nuevas, R5 bulk, R6 campos, R7 export, Owner/Platform/cupos/retiros. No certifica concurrencia MySQL.
- Node sales-functional-recovery:9 tests PASS, fixtures renderer aislados (no emisión).
- Blade view:cache PASS. PHP/JS syntax y diff check se registran al cierre.
- Lectura real DB en transacción READ ONLY y rollback: Owner4 T1/C1/B1 y sin contexto; Owner7 T3/C3/B4 y sin contexto; operador2 T1/C1/B1. Todos render200. Captura real Platform detectó schema pendiente.
- HTTP real Chrome headless separado: login EDESCE/Gamarra, GET dashboard y GET branches PASS. Ningún POST negocio; export/proveedor/otras escrituras interceptados/bloqueados.
- Render aislado del HTML real + assets locales:5 escenarios x8 anchos (320/375/390/576/768/992/1200/1440)=40 checks; overflow0, pageerrors0. Modal export accesible sólo con contexto/permiso. POST contexto simulado422 con CSRF y recuperación del botón. No confundir con persistencia operacional certificada.
- Screenshots desktop/mobile inspeccionados. Sin controlar escritorio ni navegador del usuario. Nuevos dashboards conservan diseño entregado DEV; homologación final es fase distinta.
- Inicialmente tests históricos esperaban bugs antiguos; se actualizó contrato, sin ocultar rechazos. ENTRY-R75-01 es test de caracterización de un blocker, no afirmación de fix.
- Artefactos locales no versionados: r75-read-results.json, r75-live-results.json, r75-browser-results.json y r75-*.png en directorio Codex 2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347. No credenciales en docs/tests.
- Mutaciones reales de negocio0; uploads0; documentos/exports reales0; provider0. Mutaciones sólo SQLite descartable. QA operacional real continúa pendiente.

## Archivos propios de integración

AGENTS.md; dashboard/dashboard.blade.php; dashboard/owner.blade.php; dashboard/partials/entry-reports.blade.php; public/js/layout/tenant-context.js; ProfileMutationClosureTest, MaterialEditClosureTest, CashMaintainersClosureTest, DevDeliveryIntegrationTest; estos tres documentos R7.5 y tres backlogs canónicos. Otros123 archivos proceden de DEV; inventario abajo. Backend propio0, CSS propio0, POS0, StoreWeb propio0.

## Comprobación final de código

Sintaxis: 63 archivos PHP y13 JS PASS. Blade view:cache PASS. git diff --check y --cached --check PASS; sin archivos unmerged ni marcadores residuales. Backend app/routes/database idéntico al parent DEV. CSS Venti Next, allowlist y renderer Sales idénticos al parent R0–R7. No secretos, env, locks, logs ni artefactos QA añadidos al índice. La diferencia frente al parent DEV incluye deliberadamente las recuperaciones R0–R7 ya comprometidas, no sólo los ajustes R7.5.

CODE MERGE listo para commit local; INSTALLATION PENDING; R7.5 COMPLETE WITH CONDITIONS; R7.6 GO WITH CONDITIONS; R8 NOT STARTED. No confundir el test de caracterización ENTRY-R75-01 con corrección del defecto.

## Inventario de archivos DEV automáticos

- `app/Exceptions/TenantCapacityException.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/CashBoxController.php`
- `app/Http/Controllers/CashBoxSubtypeController.php`
- `app/Http/Controllers/CompanyBankAccountController.php`
- `app/Http/Controllers/ConfigUserWebController.php`
- `app/Http/Controllers/DataGeneralController.php`
- `app/Http/Controllers/EntryController.php`
- `app/Http/Controllers/EntryDocumentController.php`
- `app/Http/Controllers/FreeSaleController.php`
- `app/Http/Controllers/GananciaDiariaController.php`
- `app/Http/Controllers/HomeController.php`
- `app/Http/Controllers/MaterialController.php`
- `app/Http/Controllers/OrderPurchaseController.php`
- `app/Http/Controllers/OrderPurchaseFinanceController.php`
- `app/Http/Controllers/OutputController.php`
- `app/Http/Controllers/PermissionController.php`
- `app/Http/Controllers/PlanController.php`
- `app/Http/Controllers/Platform/PlatformDashboardController.php`
- `app/Http/Controllers/Platform/TenantController.php`
- `app/Http/Controllers/PuntoVentaController.php`
- `app/Http/Controllers/QuoteController.php`
- `app/Http/Controllers/QuoteSaleController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/RoleController.php`
- `app/Http/Controllers/SaleListDocumentController.php`
- `app/Http/Controllers/SalePartialPaymentController.php`
- `app/Http/Controllers/SupplierCreditAttachmentController.php`
- `app/Http/Controllers/SupplierCreditController.php`
- `app/Http/Controllers/TenantBranchController.php`
- `app/Http/Controllers/TenantCompanyController.php`
- `app/Http/Controllers/TenantContextController.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Middleware/InitializeTenantContext.php`
- `app/Http/Middleware/OperationalModuleGate.php`
- `app/Http/Requests/UpdateMaterialRequest.php`
- `app/Plan.php`
- `app/Services/CashConfigurationGuard.php`
- `app/Services/Inventory/PurchaseFormReadAccess.php`
- `app/Services/Materials/MaterialCompanyCatalog.php`
- `app/Services/Materials/MaterialEditValues.php`
- `app/Services/Materials/MaterialInventoryScope.php`
- `app/Services/Materials/MaterialRetaceriaPolicy.php`
- `app/Services/OwnerWorkspace.php`
- `app/Services/PlatformDashboardSummary.php`
- `app/Services/ProfileAvatarService.php`
- `app/Services/Purchases/EntryDocumentAccess.php`
- `app/Services/Purchases/EntryDocumentFile.php`
- `app/Services/Purchases/EntryExtraDocuments.php`
- `app/Services/Purchases/NormalPurchaseReadAccess.php`
- `app/Services/Purchases/SupplierCreditAttachments.php`
- `app/Services/Purchases/SupplierCreditDocumentAccess.php`
- `app/Services/Quotes/QuoteDocumentFiles.php`
- `app/Services/Quotes/QuoteFormReadAccess.php`
- `app/Services/Sales/SaleDocumentFile.php`
- `app/Services/TenantPlanService.php`
- `app/Support/OperationalModules.php`
- `database/migrations/2026_11_21_000001_add_structure_limits_to_plans.php`
- `docs/flujos/AUDITOR-CASH-02-03-04.md`
- `docs/flujos/AUDITOR-CIERRE-AGRUPADO-2026-11-24.md`
- `docs/flujos/AUDITOR-COTIZACIONES-PAUSAS.md`
- `docs/flujos/AUDITOR-E-N-PAUSA.md`
- `docs/flujos/AUDITOR-J-MYSQL-AISLADO.md`
- `docs/flujos/AUDITOR-J-P-K-CIERRE-NEGOCIO.md`
- `docs/flujos/AUDITOR-JP-VALIDACION.md`
- `docs/flujos/AUDITOR-K-VALIDACION.md`
- `docs/flujos/AUDITOR-MAT-01-DECISION.md`
- `docs/flujos/AUDITOR-P-COTIZACIONES.md`
- `docs/flujos/AUDITOR-P-CREDITOS-DOCUMENTOS.md`
- `docs/flujos/AUDITOR-P-ENTRADAS.md`
- `docs/flujos/AUDITOR-P-EXTRAS-ENTRADAS.md`
- `docs/flujos/AUDITOR-P-VENTAS.md`
- `docs/flujos/AUDITOR-Q-COMPRAS-EXPRESS.md`
- `docs/flujos/DASHBOARD-OWNER-AJUSTE-VISUAL.md`
- `docs/flujos/DASHBOARD-OWNER-USUARIOS-MENU.md`
- `docs/flujos/DASHBOARD-OWNER.md`
- `docs/flujos/DASHBOARD-PLATFORM-ADMIN.md`
- `docs/flujos/DASHBOARDS-OWNER-PLATFORM-ALCANCE.md`
- `docs/flujos/TIENDA-WEB-ENLACES-LOCALES.md`
- `docs/flujos/auditoria-materiales-entradas-contexto.md`
- `docs/flujos/auditoria-perfil-retiro-finanzas-reporte.md`
- `docs/flujos/planes-cupos-tenant.md`
- `public/.htaccess`
- `public/images/credits/venti360-credit-protection-probe.txt`
- `public/images/planos/.htaccess`
- `public/images/planos/venti360-quote-protection-probe.txt`
- `public/js/cashBoxSubtype/index.js`
- `public/js/entry/edit_entry_purchase.js`
- `public/js/entry/edit_entry_purchaseV2.js`
- `public/js/entry/entry_purchase.js`
- `public/js/entry/entry_purchaseV2.js`
- `public/js/invoice/report_invoice.js`
- `public/js/invoice/report_invoice_sin_orden.js`
- `public/js/layout/admin2.js`
- `public/js/layout/tenant-context.js`
- `public/js/orderPurchase/createNormalV2.js`
- `public/js/orderPurchase/editNormalV2.js`
- `public/js/plan/index.js`
- `public/js/user/profile.js`
- `public/pdfs/quotes/.htaccess`
- `public/pdfs/quotes/venti360-quote-protection-probe.txt`
- `resources/views/dashboard/owner.blade.php`
- `resources/views/exports/quoteInternal.blade.php`
- `resources/views/exports/quoteSaleCustomerV2.blade.php`
- `resources/views/layouts/appAdmin2.blade.php`
- `resources/views/layouts/appAdmin3.blade.php`
- `resources/views/layouts/appAdmin4.blade.php`
- `resources/views/layouts/partials/owner-administration-menu.blade.php`
- `resources/views/layouts/partials/public-store-links.blade.php`
- `resources/views/layouts/partials/tenant-context-selector.blade.php`
- `resources/views/operationalModules/unavailable.blade.php`
- `resources/views/orderPurchaseFinance/retired.blade.php`
- `resources/views/plan/index.blade.php`
- `resources/views/plan/partials/form.blade.php`
- `resources/views/platform/dashboard/index.blade.php`
- `resources/views/platform/tenants/show.blade.php`
- `resources/views/tenantBranch/create.blade.php`
- `resources/views/tenantBranch/index.blade.php`
- `resources/views/tenantCompany/_plan_capacity.blade.php`
- `resources/views/tenantCompany/create.blade.php`
- `resources/views/tenantCompany/index.blade.php`
- `resources/views/user/profile.blade.php`
- `routes/web.php`
