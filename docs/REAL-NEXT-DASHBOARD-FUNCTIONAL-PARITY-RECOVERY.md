# REAL NEXT — R7 — DASHBOARD FUNCTIONAL PARITY RECOVERY

Fecha: 2026-11-25. HEAD `3914020`, rama `feature/venti-next-frontend`, working tree R0–R6 preservado. Una superficie revisada/modificada: `dashboard.principal` / `dashboard.dashboard`. No R8, commit, backend ni CSS.

## Resultado

Ocho pérdidas activas confirmadas: las dos originales FR-063/064 y seis adicionales FR-102…107. Una recuperada (FR-104); siete pendientes backend. **FUNCTIONAL PARITY: PARTIAL — BACKEND BLOCKERS**. No se cierran como éxito los controles inseguros. Ninguna exclusión legacy nueva ni decisión de producto pendiente para las capacidades recuperadas.

URL: http://127.0.0.1:8080/dashboard/principal. Al final de la página: **Reportes de almacén → Exportar entradas**. El botón requiere `showBoxAlmacen_dashboard` y al menos `list_entryPurchase` o `list_entryInventory`; las opciones respetan los permisos del endpoint. No se introduce Owner bypass.

## Evidencia histórica y vigencia

Se contrastaron `dbca24e1^`, `dbca24e1`, HEAD y working tree. El padre cargaba `report/reportAmount.js` y `dashboard/ordersChart.js`; la homologación retiró reportAmount y reescribió ordersChart para los dos gráficos actuales. `report/charts.js` y `report/viewReport.js` estaban comentados en el padre: no eran dependencias runtime. El comparador local elimina comentarios Blade antes de contabilizar controles (`r7-inventory.py`, capturas de fuente y `r7-original-inventory.json`).

- Conservados: siete cards de operación/finanzas/relaciones con permisos, navegación, ranking de metas contextual moderno y dos gráficos Ventas / Ingresos vs egresos con diario/semanal/mensual/rango y sus exports existentes. No se sustituyen por las versiones antiguas ni se alteran fórmulas.
- Perdidos y activos: valorización/refresh de almacén, exports de importes/materiales/materiales por almacén, export de entradas, historial de rotación, nuevo corte de rotación, reporte mensual de personal. Un modal/filtro/handler no se suma por separado a su operación.
- `btnViewReportDollarsQuote`, `btnViewReportSolesQuote`, los tres `btnViewReportIncomeExpense*` y los tres `btnViewReportUtilities*`, con sus modales: **HISTORICAL CODE — NOT REACHABLE**. Estaban dentro de comentarios antes de la homologación. El reporte de cotizaciones usa el dominio industrial `Quote`; no se declara retirado por asociación con QuoteSale. No se reactiva código huérfano.
- No se recupera ningún reporte exclusivo de Compras General/Express/Finance, OrderService, Proforma, ReferralGuide legacy, FollowMaterial o Promotions. No son el dominio de FR-063/064 (inventario).
- El gráfico de flujo ya disponible tiene contrato mixto que incluye OrderService/Entry en backend. Se preserva íntegro, sin certificar su vigencia global ni cambiar la fórmula; METRIC-01 permanece abierto. Recuperar o reformular reportes mixtos adicionales exigiría decisión del propietario. Ninguno se recupera en R7.
- DEV posterior: `82e92aa` y `2f87545` endurecieron Entry y exportEntriesAlmacen. Estos cambios permiten FR-104; no arreglaron automáticamente ReportController, RotationMaterialController ni PersonalPaymentController.

## Matriz funcional antes/después

En filas bloqueadas, el permiso visual nunca equivale a autorización del endpoint. Grupo común de rutas: auth, check.user.enabled, password.changed, tenant.context; no garantiza permiso funcional ni company por sí solo.

| ID | Capacidad histórica | Vigencia | Evidencia Git | Permiso | Endpoint | Scope | Estado anterior | Blocker | Recuperación | QA |
|---|---|---|---|---|---|---|---|---|---|---|
| FR-063 | Exportar importes | ACTIVE | dbca24e1^ → dbca24e1 | UI showBoxAlmacen_dashboard; endpoint sin granular | GET report/excel/amount/stock | TenantScope parcial, sin company/branch explícitos | Retirada | INV-01 / METRIC-01 | ACTIVE — BACKEND BLOCKER | Contrato estático; export no ejecutado |
| FR-064 | Exportar materiales | ACTIVE | dbca24e1^ → dbca24e1 | UI showBoxAlmacen_dashboard; endpoint sin granular | GET report/excel/bd/materials | Material/Item/Location legacy, sin company explícita | Retirada | INV-01 / METRIC-01 | ACTIVE — BACKEND BLOCKER | Contrato estático |
| FR-102 | Importes/cantidad de almacén + refrescar | ACTIVE | reportAmount.js + btn-refresh en padre | Sólo gate visual | GET report/amount/items | Legacy stock/items | Retirada | INV-01 / METRIC-01 | ACTIVE — BACKEND BLOCKER | No invocado |
| FR-103 | Exportar materiales por almacén | ACTIVE | btn-download/modalLocations/btn-submitDownload | Sólo gate visual | GET report/excel/bd/materials/warehouse/{id} | Warehouse::find + Location sin ownership company | Retirada | INV-01 | ACTIVE — BACKEND BLOCKER | No invocado |
| FR-104 | Exportar entradas compra/inventario | ACTIVE | btn-downloadEntries/modalEntries/btn-submitExport | list_entryPurchase/list_entryInventory según tipo | GET exportar/entradas/almacen/v2/ | Tenant + company; finance=false; no branch | Retirada | Ninguno demostrado para acceso | ACTIVE — RESTORED | SQLite + navegador interceptado PASS; Excel real pendiente |
| FR-105 | Historial de cortes de rotación + paginación | ACTIVE | reportAmount.js getDataRotations(1), body-table | Sin permiso funcional | GET get/data/rotations/v2/{page} | RotationMaterial global + user | Retirada | METRIC-01 | ACTIVE — BACKEND BLOCKER | No invocado |
| FR-106 | Nuevo corte de rotación | ACTIVE | btn-newRotation + confirm en reportAmount | Sin permiso funcional | GET store/rotation/material/ | Material/Items/RotationMaterial legacy | Retirada | DASH-R7-01 + INV-01/METRIC-01 | ACTIVE — BACKEND BLOCKER | GET escribe negocio; no invocado |
| FR-107 | Personal por año/mes: tabla y proyección | ACTIVE | btn-pays/year/month + ordersChart histórico | UI showBoxGraficosRRHH_dashboard; sin guard equivalente | GET personal/payments?year&month | Worker/PaySlip/Projection/SueldoMensual sin company | Retirada | DASH-R7-01 / METRIC-01 | ACTIVE — BACKEND BLOCKER | GET escribe negocio; no invocado |
| R7-A01 | Cards, enlaces y ranking de metas | ACTIVE | Padre/integración/HEAD | showboxes_dashboard + permisos de dominio; showBoxMetas_dashboard | dashboard.principal / metas.ranking | Conteos legacy; metas company-wide | Disponible | METRIC-01 general no cerrado | ACTIVE — ALREADY AVAILABLE | Render real read-only; sin modificar |
| R7-A02 | Ventas + flujo: gráficos/filtros/exports de rango | ACTIVE | btn-export-range-sales/cashflow-range presentes en HEAD | showBoxGraficosVentas_dashboard visual | chart-data-sale/utilidad; sales.export.range/cashflow.export.range | Scope y fórmula existentes | Disponible | METRIC-01; no certificación financiera nueva | ACTIVE — ALREADY AVAILABLE | Gráficos simulados, filtros intactos; exports existentes no ejecutados |
| R7-H01 | Ocho reportes de cotizaciones/ingresos/utilidades | No alcanzable | Comentarios Blade ya en dbca24e1^ | N/A runtime | Handlers huérfanos viewReport/charts | No reactivado | Oculto previamente | No pérdida demostrada | HISTORICAL CODE — NOT REACHABLE | Extracción sin comentarios |

## Contrato FR-063 — importes

`report.excel.amount`: GET `/dashboard/report/excel/amount/stock`, `ReportController@excelAmountStock` (línea 88), `AmountReport`, `exports.excelAmount`, archivo `reporte_Stock_Monto_En_Almacen.xlsx`. Sin parámetros, fechas, IDs ni selector de moneda. Exporta inventario valorizado, no ventas/cotizaciones: Material stock_current>0, enable_status=1, descripción no EDESCE, categorías excluidas 8/6; Items no exited, DetailEntry/Entry para currency_invoice; sumas de precio/porcentaje separadas USD y resto tratado como soles por backend. No modificar ni certificar esa regla monetaria.

No hay permiso funcional de export en ruta/controller ni company/branch explícitos. TenantScope por sí solo no cubre la autoridad InventoryLevel/StockItems. Exponer el enlace haría accesible un export no demostrado aislado. `amountInWarehouse` (línea27) alimentaba indicadores con deuda equivalente. DEV debe definir permiso efectivo y scope, aplicar lectura contextual autoritativa y probar conjuntos completos entre companies/tenants; frontend no puede filtrar el archivo.

## Contrato FR-064/103 — materiales

`report.excel.materials`: GET `/dashboard/report/excel/bd/materials`, `ReportController@excelBDMaterials` (151), `DatabaseMaterialsExport`, `exports.excelDataBase`, `reporte_base_materiales.xlsx`. Material habilitado, exclusiones EDESCE/categoría8; incluye códigos/descripción/unidad/clasificaciones, stock_min/max/current, unit_price, prioridad derivada y ubicaciones obtenidas de Items no exited/Location/shelf/level. Por eso NO equivale a exportar únicamente el maestro Material.

FR-103 usa `report.excel.materials.warehouse`, `excelBDMaterialsByWarehouse` (296). El control histórico `location` enviaba ID de **Warehouse**, no Location; `modalLocations` se alimentaba de `almacenes`. Warehouse::find y Location::where(warehouse_id) sin frontera company/local; el selector de Home usa Warehouse::all. Existe export por Location (230), pero no se contabiliza como otra pérdida si el dashboard no lo invocaba.

No hay fechas/tipos de entrada/moneda configurables en estos dos exports. Los tipos de entrada pertenecen a FR-104. Sin autorización granular/ownership, no recuperados. Los exporters son FromView/download, no hay un almacenamiento comercial permanente explícito en esos métodos; acceso/temporales generales de Excel no se certifican porque no se ejecutan exports reales.

## FR-104 — contrato recuperado

Ruta sin nombre, `routes/web.php:3158`; permission middleware `list_entryPurchase|list_entryInventory`. `EntryController@exportEntriesAlmacen:4760` exige TenantContext tenant+company y valida tipo 1/2/3. Tipo1 requiere **ambos** permisos;2 compra;3 inventario. Sin Owner bypass. Método GET existente, sin payload CSRF necesario; conserva sesión same-origin y query `typeEntry`, `start`, `end`.

Fechas `d/m/Y`, ambas opcionales juntas y end>=start. Sin fechas conserva confirmación de todo el periodo. No se endurece backend. Company-wide: no se inventa filtro branch ni selector de almacén/actor/moneda. Entry tenant_id/company_id explícitos, finance=false, tipos Por compra/Inventario, fechas y soft-delete de modelo. Export incluye tipo, orden de compra, factura, proveedor, fecha, observación, moneda de cada entrada, código/nombre de material, cantidad/precio/total. No convierte monedas ni modifica cálculos.

`EntriesReportExcelDownload` → `exports.excelExportEntries`, descarga `reporteEntradasCompraInventario.xlsx`. En QA se sustituye Exporter ANTES de construir workbook; no archivo comercial ni proveedor externo. En UI, loading evita doble submit, errores JSON 403/422/500 se muestran, HTML de sesión no se descarga como Excel. Mismo contrato, nuevo adaptador pequeño; no vuelve reportAmount con sus consultas/mutaciones ajenas. Fonts/iconos existentes, modal next-aux-modal, botón Font Awesome y datepicker existentes. Contenedor `.wrapper` como Entries R4 para anclaje correcto, sin CSS.

## Seguridad adicional — un root cause nuevo

**DASH-R7-01 — GET de reportes ejecuta escrituras de negocio**, distinto del scope de METRIC-01 y de QA-01. `RotationMaterialController@storeRotationMaterial:246` actualiza Material.rotation/rotation_value (450–451), crea RotationMaterial (462) y commit (467). `PersonalPaymentController@getPersonalPaymentByMonth:66` actualiza/crea SueldoMensual en314–331 y consulta tipo de cambio. No es Audit técnico ni cache inocua demostrada. No ejecutados.

DEV: separar lectura de escrituras; definir comandos autorizados con método mutador/CSRF/contexto y manejo de repetición, o exponer reporte puramente read-only. Preservar fórmulas, no eliminarlas unilateralmente. Aceptación: GET no altera Material/RotationMaterial/SueldoMensual; usuario sin permiso403; referencias/consultas cross-company/tenant fail closed; pruebas aisladas sin proveedor. El historial de rotación requiere además scope/permiso, pero no tiene por sí mismo escritura. Un finding nuevo compartido por dos handlers con el mismo fallo HTTP; no duplicar INV-01/METRIC-01.

## Cobertura previa

FR-063/064 eran PENDING EXACT/semántica en la auditoría inicial; ahora BACKEND BLOCKER concreto, no QA pendiente genérico. Las seis FR-102…107 no estaban en el registro de pérdidas: fueron descubiertas al comparar runtime histórico, no seis pantallas nuevas. FR-104 demuestra una exclusión read-only obsoleta; las otras cinco tienen blockers actuales. No se asigna a DEV la omisión frontend de FR-104.

## QA y datos

- MySQL `START TRANSACTION READ ONLY`, conexiones de lectura/escritura en el mismo PDO, cache/session array, ROLLBACK final. Render Home real Owner EDESCE user4 T1/C1/B1 y Owner Gamarra user7 T3/C3/B4. No contraseñas ni tokens reales guardados.
- Entry elegibles EDESCE C1:32 (31 Por compra +1 Inventario), IDs4–22,24,25,34,35,37,39–44,53,54. Gamarra C3:1 Por compra, ID45. No export generado con estos datos. Los conteos de cards existentes no se reinterpretan ni se certifica METRIC-01.
- `DashboardEntryExportContractTest`: **4 tests /31 assertions PASS**, SQLite :memory:, export interceptado. Prueba tenant/company, exclusión finance/Retacería, PEN/USD por fila sin conversión, tipos, rangos/vacío, permisos parciales/ambos, fechas inválidas y contexto ausente. No sustituye HTTP con sesión real ni QA Excel/MySQL de negocio.
- Chrome headless aislado, Blade renderizado real y assets locales; gráficos SIMULADOS, todas las solicitudes interceptadas. Ocho anchos320/375/390/576/768/992/1200/1440: sin overflow; modal abre/cierra sin export; datepicker alineado al input. No escritorio/navegador del usuario.
- Roles simulados sobre mismo render: sin permisos no botón; compra sólo opción2; inventario sólo opción3; Owners tipos disponibles. No roles persistentes ni fixtures DB real.
- Cinco GET export interceptados: confirmar sin fechas +403/422/500/200 con rango. Dos descargas de bytes explícitamente SIMULADOS, sin guardar archivo comercial. Fechas inválidas y cancelar no envían; doble submit produce una sola solicitud. Nombre archivo correcto, icono existente, error visible y desbloqueo del botón. Cero pageerrors. Filtro semanal del gráfico emite una petición; no carga reportAmount, payroll ni rotación.
- Recursos externos de fuente/ionicons y `/api/sunat` del shell fueron interceptados; no conexión a SUNAT. La revisión visual es focalizada, no certificación global del shell ni de fuentes remotas. Los exports ya disponibles no se ejecutaron.
- JS syntax, Blade compile y `git diff --check`: PASS. **REAL EXPORT QA PENDING**; no mutación comercial, no cambio backend, 0 CSS.

## Evidencia local

Directorio `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`:

- `r7-read.php`, `r7-read-results.json`, `r7-edesce.html`, `r7-gamarra.html`: lectura y render de contexto.
- `r7-browser.cjs`, `r7-browser-results.json`: interceptaciones, viewport, permisos, errores y payloads.
- `r7-before-dashboard.png`: reconstrucción visual controlada del estado previo (mismo render sin sección recuperada), no captura histórica de datos comerciales.
- `r7-dashboard-375.png`, `r7-dashboard-1440.png`, `r7-modal-375.png`, `r7-modal-1440.png`: después, capturas de viewport del control y modal; charts etiquetados SIMULADO.
- `r7-dbca24e1-parent.blade.php`, `r7-dbca24e1.blade.php`, `r7-HEAD.blade.php`, variantes active.txt: evidencia histórica de markup alcanzable.

## Archivos intervenidos por R7

1. resources/views/dashboard/dashboard.blade.php
2. resources/views/dashboard/partials/entry-export.blade.php (nuevo)
3. public/js/dashboard/entry-export.js (nuevo)
4. tests/Feature/DashboardEntryExportContractTest.php (nuevo)
5. Este documento (nuevo)
6. docs/REAL-NEXT-FUNCTIONAL-PARITY-RECOVERY-BACKLOG.md
7. docs/REAL-NEXT-DEV-TEAM-FINDINGS-BACKLOG.md
8. docs/REAL-NEXT-DEV-PARALLEL-WORK-2026-11-22.md

ordersChart.js, shell, CSS, controllers, routes, modelos, export classes, allowlist y R0–R6: sin cambios de R7.

## Reconciliación canónica R0–R7, preparación R8 (sin comenzar R8)

Unidad: una superficie del inventario original, identificada por GET URI único y nombre de ruta cuando existe. No sumar endpoints, modales, capabilities ni destinos auxiliares no homologados. Índice S-001…197 fijado por orden del inventario JSON de la auditoría inicial. Las9 superficies con paridad **estática** no se promocionan automáticamente a auditoría semántica.

| Métrica | Total |
|---|---:|
| Superficies originalmente homologadas |197|
| Revisadas semánticamente o resueltas por decisión de producto, IDs únicos |40|
| De las40, activas revisadas semánticamente |32|
| De las40, excluidas por decisión legacy |8|
| Pendientes de revisión semántica |157|
| Con paridad funcional recuperada |16|
| Ya disponibles y paridad preservada sin pérdida |4|
| Con recuperación parcial/blockers backend |12|
| Capacidades individuales FR-001…107 |107|
| Excluidas por decisión de producto |23|
| Capacidades vigentes |84|
| Capacidades vigentes recuperadas |62|
| Capacidades vigentes pendientes (todas con blocker identificado) |22|
| Pendientes de decisión de producto |0|

Comprobación:197=157+16+4+12+8;107=62+22+23. Las40 son R1:2,R2:1,R3.1:6,R3.2:6,R4:11,R5:11,R6:2,R7:1. Las3 superficies destino adicionales examinadas en R4 no estaban homologadas y no se descuentan de197. Navegación SELF recuperada en R2 no equivale a reauditar integralmente My Cash/Own Session. R3.2 suma seis decisiones legacy, no seis recuperaciones.

Las158 UNKNOWN originales se reconcilian con10 superficies adicionales revisadas y9 estáticas aún pendientes:158-10+9=157. No usar158 como remanente actual. Los12 blockers de superficie pueden contener varias capacidades y los23 excluidos son capacidades, no superficies. QA operacional sigue separado: pruebas simuladas/aisladas no cierran mutaciones comerciales, documentos/infra, exports reales ni proveedor externo de R1–R7; no declarar las62 certificadas en producción. Sin iniciar auditoría R8.

### Ledger completo, sin nuevas auditorías

| Surface ID | Route o GET URI original | Fase | Estado |
|---|---|---|---|
| S-001 | `password.required.edit` | — | PENDING SEMANTIC AUDIT |
| S-002 | `tenantCompany.index` | — | PENDING SEMANTIC AUDIT |
| S-003 | `tenantCompany.create` | — | PENDING SEMANTIC AUDIT |
| S-004 | `tenantCompany.edit` | — | PENDING SEMANTIC AUDIT |
| S-005 | `tenantBranch.index` | — | PENDING SEMANTIC AUDIT |
| S-006 | `tenantBranch.create` | — | PENDING SEMANTIC AUDIT |
| S-007 | `tenantBranch.edit` | — | PENDING SEMANTIC AUDIT |
| S-008 | `companyBankAccount.index` | — | PENDING SEMANTIC AUDIT |
| S-009 | `companyBankAccount.create` | — | PENDING SEMANTIC AUDIT |
| S-010 | `companyBankAccount.edit` | — | PENDING SEMANTIC AUDIT |
| S-011 | `companySetting.index` | — | PENDING SEMANTIC AUDIT |
| S-012 | `workforce.index` | — | PENDING SEMANTIC AUDIT |
| S-013 | `dashboard.principal` | R7 | PARTIAL — BACKEND BLOCKER |
| S-014 | `priceList.index` | — | PENDING SEMANTIC AUDIT |
| S-015 | `customer.index` | — | PENDING SEMANTIC AUDIT |
| S-016 | `customer.create` | — | PENDING SEMANTIC AUDIT |
| S-017 | `customer.edit` | — | PENDING SEMANTIC AUDIT |
| S-018 | `customer.indexrestore` | — | PENDING SEMANTIC AUDIT |
| S-019 | `supplier.index` | — | PENDING SEMANTIC AUDIT |
| S-020 | `supplier.create` | — | PENDING SEMANTIC AUDIT |
| S-021 | `supplier.edit` | — | PENDING SEMANTIC AUDIT |
| S-022 | `supplier.indexrestore` | — | PENDING SEMANTIC AUDIT |
| S-023 | `contactName.index` | — | PENDING SEMANTIC AUDIT |
| S-024 | `contactName.create` | — | PENDING SEMANTIC AUDIT |
| S-025 | `contactName.edit` | — | PENDING SEMANTIC AUDIT |
| S-026 | `contactName.indexrestore` | — | PENDING SEMANTIC AUDIT |
| S-027 | `materialtype.index` | R5 | PARITY RESTORED — QA SEPARATE |
| S-028 | `materialtype.create` | — | PENDING SEMANTIC AUDIT |
| S-029 | `materialtype.edit` | — | PENDING SEMANTIC AUDIT |
| S-030 | `subtype.index` | R5 | PARITY RESTORED — QA SEPARATE |
| S-031 | `subtype.create` | — | PENDING SEMANTIC AUDIT |
| S-032 | `subtype.edit` | — | PENDING SEMANTIC AUDIT |
| S-033 | `category.index` | R5 | PARITY RESTORED — QA SEPARATE |
| S-034 | `category.create` | — | PENDING SEMANTIC AUDIT |
| S-035 | `category.edit` | — | PENDING SEMANTIC AUDIT |
| S-036 | `subcategory.index` | R5 | PARITY RESTORED — QA SEPARATE |
| S-037 | `subcategory.create` | — | PENDING SEMANTIC AUDIT |
| S-038 | `subcategory.edit` | — | PENDING SEMANTIC AUDIT |
| S-039 | `categoryInvoice.index` | — | PENDING SEMANTIC AUDIT |
| S-040 | `categoryInvoice.create` | — | PENDING SEMANTIC AUDIT |
| S-041 | `categoryInvoice.edit` | — | PENDING SEMANTIC AUDIT |
| S-042 | `exampler.index` | R5 | PARITY RESTORED — QA SEPARATE |
| S-043 | `exampler.create` | — | PENDING SEMANTIC AUDIT |
| S-044 | `exampler.edit` | — | PENDING SEMANTIC AUDIT |
| S-045 | `brand.index` | R5 | PARITY RESTORED — QA SEPARATE |
| S-046 | `brand.create` | — | PENDING SEMANTIC AUDIT |
| S-047 | `brand.edit` | — | PENDING SEMANTIC AUDIT |
| S-048 | `warrant.index` | — | PENDING SEMANTIC AUDIT |
| S-049 | `warrant.create` | — | PENDING SEMANTIC AUDIT |
| S-050 | `warrant.edit` | — | PENDING SEMANTIC AUDIT |
| S-051 | `quality.index` | — | PENDING SEMANTIC AUDIT |
| S-052 | `quality.create` | — | PENDING SEMANTIC AUDIT |
| S-053 | `quality.edit` | — | PENDING SEMANTIC AUDIT |
| S-054 | `typescrap.index` | R5 | PARITY RESTORED — QA SEPARATE |
| S-055 | `typescrap.create` | — | PENDING SEMANTIC AUDIT |
| S-056 | `typescrap.edit` | — | PENDING SEMANTIC AUDIT |
| S-057 | `unitmeasure.index` | R5 | PARITY RESTORED — QA SEPARATE |
| S-058 | `unitmeasure.create` | — | PENDING SEMANTIC AUDIT |
| S-059 | `unitmeasure.edit` | — | PENDING SEMANTIC AUDIT |
| S-060 | `genero.index` | R5 | PARITY RESTORED — QA SEPARATE |
| S-061 | `genero.create` | — | PENDING SEMANTIC AUDIT |
| S-062 | `genero.edit` | — | PENDING SEMANTIC AUDIT |
| S-063 | `talla.index` | R5 | PARITY RESTORED — QA SEPARATE |
| S-064 | `talla.create` | — | PENDING SEMANTIC AUDIT |
| S-065 | `talla.edit` | — | PENDING SEMANTIC AUDIT |
| S-066 | `material.create` | — | PENDING SEMANTIC AUDIT |
| S-067 | `material.edit` | — | PENDING SEMANTIC AUDIT |
| S-068 | `material.indexV2` | — | PENDING SEMANTIC AUDIT |
| S-069 | `material.stock.getItems` | R6 | PARITY RESTORED — QA SEPARATE |
| S-070 | `warehouseTransfer.index` | — | PENDING SEMANTIC AUDIT |
| S-071 | `warehouseTransfer.create` | — | PENDING SEMANTIC AUDIT |
| S-072 | `warehouseTransfer.show` | — | PENDING SEMANTIC AUDIT |
| S-073 | `warehouseAvailability.index` | — | PENDING SEMANTIC AUDIT |
| S-074 | `entry.purchase.indexV2` | R4 | PARTIAL — BACKEND BLOCKER |
| S-075 | `entry.purchase.show` | R4 | PARTIAL — BACKEND BLOCKER |
| S-076 | `order.purchase.list.indexV2` | R4 | PARITY RESTORED — QA SEPARATE |
| S-077 | `entry.purchase.report.index` | R4 | PARTIAL — BACKEND BLOCKER |
| S-078 | `GET dashboard/ver/orden/compra/normal/{id}` | R3.1 | ALREADY AVAILABLE — PARITY PRESERVED |
| S-079 | `order.purchase.delete` | R3.1 | PARTIAL — BACKEND BLOCKER |
| S-080 | `GET dashboard/ver/orden/compra/eliminada/{id}` | R3.1 | ALREADY AVAILABLE — PARITY PRESERVED |
| S-081 | `order.purchase.general.indexV2` | R3.1 | LEGACY — OWNER CONFIRMED |
| S-082 | `order.purchase.express.indexV2` | R3.1 | LEGACY — OWNER CONFIRMED |
| S-083 | `order.purchase.normal.indexV2` | R3.1 | PARTIAL — BACKEND BLOCKER |
| S-084 | `user.profile` | — | PENDING SEMANTIC AUDIT |
| S-085 | `invoice.index` | R4 | PARTIAL — BACKEND BLOCKER |
| S-086 | `invoice.index.annulled` | R4 | PARTIAL — BACKEND BLOCKER |
| S-087 | `invoice.create` | R4 | ALREADY AVAILABLE — PARITY PRESERVED |
| S-088 | `invoice.edit` | R4 | ALREADY AVAILABLE — PARITY PRESERVED |
| S-089 | `invoice.show` | R4 | PARTIAL — BACKEND BLOCKER |
| S-090 | `index.credit.supplier` | — | PENDING SEMANTIC AUDIT |
| S-091 | `index.invoices.pending` | — | PENDING SEMANTIC AUDIT |
| S-092 | `paymentDeadline.index` | — | PENDING SEMANTIC AUDIT |
| S-093 | `paymentDeadline.create` | — | PENDING SEMANTIC AUDIT |
| S-094 | `paymentDeadline.edit` | — | PENDING SEMANTIC AUDIT |
| S-095 | `porcentageQuote.index` | — | PENDING SEMANTIC AUDIT |
| S-096 | `porcentageQuote.create` | — | PENDING SEMANTIC AUDIT |
| S-097 | `porcentageQuote.edit` | — | PENDING SEMANTIC AUDIT |
| S-098 | `report.invoice.finance` | R4 | PARTIAL — BACKEND BLOCKER |
| S-099 | `report.invoice.finance.sin.orden` | R4 | PARTIAL — BACKEND BLOCKER |
| S-100 | `worker.index` | — | PENDING SEMANTIC AUDIT |
| S-101 | `worker.create` | — | PENDING SEMANTIC AUDIT |
| S-102 | `worker.edit` | — | PENDING SEMANTIC AUDIT |
| S-103 | `worker.enable` | — | PENDING SEMANTIC AUDIT |
| S-104 | `civilStatuses.index` | — | PENDING SEMANTIC AUDIT |
| S-105 | `civilStatuses.create` | — | PENDING SEMANTIC AUDIT |
| S-106 | `civilStatuses.edit` | — | PENDING SEMANTIC AUDIT |
| S-107 | `civilStatuses.deleted` | — | PENDING SEMANTIC AUDIT |
| S-108 | `relationship.index` | — | PENDING SEMANTIC AUDIT |
| S-109 | `relationship.create` | — | PENDING SEMANTIC AUDIT |
| S-110 | `relationship.edit` | — | PENDING SEMANTIC AUDIT |
| S-111 | `workFunctions.index` | — | PENDING SEMANTIC AUDIT |
| S-112 | `workFunctions.create` | — | PENDING SEMANTIC AUDIT |
| S-113 | `workFunctions.edit` | — | PENDING SEMANTIC AUDIT |
| S-114 | `workFunctions.deleted` | — | PENDING SEMANTIC AUDIT |
| S-115 | `pensionSystems.index` | — | PENDING SEMANTIC AUDIT |
| S-116 | `pensionSystems.create` | — | PENDING SEMANTIC AUDIT |
| S-117 | `pensionSystems.edit` | — | PENDING SEMANTIC AUDIT |
| S-118 | `pensionSystems.deleted` | — | PENDING SEMANTIC AUDIT |
| S-119 | `holiday.index` | — | PENDING SEMANTIC AUDIT |
| S-120 | `holiday.create` | — | PENDING SEMANTIC AUDIT |
| S-121 | `holiday.edit` | — | PENDING SEMANTIC AUDIT |
| S-122 | `reasonSuspension.index` | — | PENDING SEMANTIC AUDIT |
| S-123 | `reasonSuspension.create` | — | PENDING SEMANTIC AUDIT |
| S-124 | `reasonSuspension.edit` | — | PENDING SEMANTIC AUDIT |
| S-125 | `areaWorker.index` | — | PENDING SEMANTIC AUDIT |
| S-126 | `areaWorker.create` | — | PENDING SEMANTIC AUDIT |
| S-127 | `areaWorker.edit` | — | PENDING SEMANTIC AUDIT |
| S-128 | `order.purchase.finance.index` | R3.2 | LEGACY — OWNER CONFIRMED |
| S-129 | `order.purchase.finance.create` | R3.2 | LEGACY — OWNER CONFIRMED |
| S-130 | `GET dashboard/editar/orden/compra/finanza/{id}` | R3.2 | LEGACY — OWNER CONFIRMED |
| S-131 | `order.purchase.finance.delete` | R3.2 | LEGACY — OWNER CONFIRMED |
| S-132 | `GET dashboard/ver/orden/compra/finanzas/eliminada/{id}` | R3.2 | LEGACY — OWNER CONFIRMED |
| S-133 | `order.purchase.finance.list.regularize` | R3.2 | LEGACY — OWNER CONFIRMED |
| S-134 | `bill.index` | — | PENDING SEMANTIC AUDIT |
| S-135 | `bill.create` | — | PENDING SEMANTIC AUDIT |
| S-136 | `bill.edit` | — | PENDING SEMANTIC AUDIT |
| S-137 | `expense.create` | — | PENDING SEMANTIC AUDIT |
| S-138 | `supplier.accounts.index` | — | PENDING SEMANTIC AUDIT |
| S-139 | `categoryEquipment.index` | — | PENDING SEMANTIC AUDIT |
| S-140 | `categoryEquipment.eliminated` | — | PENDING SEMANTIC AUDIT |
| S-141 | `stocks.files.index` | — | PENDING SEMANTIC AUDIT |
| S-142 | `puntoVenta.index` | — | PENDING SEMANTIC AUDIT |
| S-143 | `puntoVenta.indexV2` | — | PENDING SEMANTIC AUDIT |
| S-144 | `puntoVenta.list` | R1 | PARITY RESTORED — QA SEPARATE |
| S-145 | `puntoVenta.listError` | R1 | PARITY RESTORED — QA SEPARATE |
| S-146 | `ganancia.index` | — | PENDING SEMANTIC AUDIT |
| S-147 | `ganancia.detail.index` | — | PENDING SEMANTIC AUDIT |
| S-148 | `ganancia.detail.usuario` | — | PENDING SEMANTIC AUDIT |
| S-149 | `ganancia.index.trabajador` | — | PENDING SEMANTIC AUDIT |
| S-150 | `metas.index` | — | PENDING SEMANTIC AUDIT |
| S-151 | `metas.create` | — | PENDING SEMANTIC AUDIT |
| S-152 | `metas.edit` | — | PENDING SEMANTIC AUDIT |
| S-153 | `metas.ranking` | — | PENDING SEMANTIC AUDIT |
| S-154 | `quoteSale.index` | — | PENDING SEMANTIC AUDIT |
| S-155 | `quoteSale.index.facturadas` | — | PENDING SEMANTIC AUDIT |
| S-156 | `quoteSale.show` | — | PENDING SEMANTIC AUDIT |
| S-157 | `quoteSale.pendingBranches` | — | PENDING SEMANTIC AUDIT |
| S-158 | `quoteSale.pendingBranches.show` | — | PENDING SEMANTIC AUDIT |
| S-159 | `kardex.index` | — | PENDING SEMANTIC AUDIT |
| S-160 | `settings.material-details.index` | — | PENDING SEMANTIC AUDIT |
| S-161 | `cashBox.index` | — | PENDING SEMANTIC AUDIT |
| S-162 | `cashBoxSubtype.index` | — | PENDING SEMANTIC AUDIT |
| S-163 | `cashMovement.my.index` | — | PENDING SEMANTIC AUDIT |
| S-164 | `cashMovement.admin.index` | R2 | PARITY RESTORED — QA SEPARATE |
| S-165 | `cashRegister.session` | — | PENDING SEMANTIC AUDIT |
| S-166 | `financial.summary` | — | PENDING SEMANTIC AUDIT |
| S-167 | `financial.summary.movements` | — | PENDING SEMANTIC AUDIT |
| S-168 | `shipping_guides.view` | — | PENDING SEMANTIC AUDIT |
| S-169 | `shipping_guides.create` | — | PENDING SEMANTIC AUDIT |
| S-170 | `shipping_guides.show` | — | PENDING SEMANTIC AUDIT |
| S-171 | `stockitems.index` | R6 | PARTIAL — BACKEND BLOCKER |
| S-172 | `stockitems.view.variants` | — | PENDING SEMANTIC AUDIT |
| S-173 | `color.index` | R5 | PARITY RESTORED — QA SEPARATE |
| S-174 | `color.create` | — | PENDING SEMANTIC AUDIT |
| S-175 | `color.edit` | — | PENDING SEMANTIC AUDIT |
| S-176 | `configUserWeb.index` | — | PENDING SEMANTIC AUDIT |
| S-177 | `configUserWeb.create` | — | PENDING SEMANTIC AUDIT |
| S-178 | `configUserWeb.edit` | — | PENDING SEMANTIC AUDIT |
| S-179 | `companyStockItem.index` | — | PENDING SEMANTIC AUDIT |
| S-180 | `platform.dashboard` | — | PENDING SEMANTIC AUDIT |
| S-181 | `platformActivity.index` | — | PENDING SEMANTIC AUDIT |
| S-182 | `platformTenant.index` | — | PENDING SEMANTIC AUDIT |
| S-183 | `platformTenant.create` | — | PENDING SEMANTIC AUDIT |
| S-184 | `platformTenant.show` | — | PENDING SEMANTIC AUDIT |
| S-185 | `plan.index` | — | PENDING SEMANTIC AUDIT |
| S-186 | `roleTemplate.index` | — | PENDING SEMANTIC AUDIT |
| S-187 | `roleTemplate.create` | — | PENDING SEMANTIC AUDIT |
| S-188 | `roleTemplate.edit` | — | PENDING SEMANTIC AUDIT |
| S-189 | `tenantRole.index` | — | PENDING SEMANTIC AUDIT |
| S-190 | `tenantRole.create` | — | PENDING SEMANTIC AUDIT |
| S-191 | `tenantRole.edit` | — | PENDING SEMANTIC AUDIT |
| S-192 | `platformPercentageWorker.index` | — | PENDING SEMANTIC AUDIT |
| S-193 | `platformPercentageWorker.edit` | — | PENDING SEMANTIC AUDIT |
| S-194 | `platformTypeTax.index` | — | PENDING SEMANTIC AUDIT |
| S-195 | `platformNubefact.index` | — | PENDING SEMANTIC AUDIT |
| S-196 | `platformNubefact.edit` | — | PENDING SEMANTIC AUDIT |
| S-197 | `platformShippingCatalogs.index` | — | PENDING SEMANTIC AUDIT |
