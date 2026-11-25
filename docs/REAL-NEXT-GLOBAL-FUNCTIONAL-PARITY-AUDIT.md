# REAL NEXT — GLOBAL FUNCTIONAL PARITY AUDIT

Fecha: 2026-11-24. HEAD: 744d28b. Auditoría estática; no mutaciones, proveedores ni documentos.

## Inventario completo y límites

Unidad: ruta GET activa allowlisted, no botón ni endpoint. 197 rutas. La ausencia de diferencias de tokens NO prueba paridad funcional. Este inventario conserva UNKNOWN donde falta contraste semántico; no clasifica QA pendiente como retiro aprobado. Rutas parametrizadas: DATASET REQUIRED salvo evidencia específica. No se tocaron Store Web ni POS.

## Resultado ejecutivo y límite de cobertura

**DIAGNÓSTICO GLOBAL PARCIAL — NO CIERRE EXHAUSTIVO.** Se inventariaron y contrastaron estructuralmente las 197 rutas homologadas. Hay 30 superficies con pérdidas demostradas y 9 con paridad estática de las acciones revisadas. Las 158 restantes requieren cierre semántico: no son PASS por ausencia de tokens eliminados. Ninguna superficie queda certificada en todas sus combinaciones de permisos/estado por esta auditoría.

Mínimo confirmado: **76 pares superficie/capacidad perdidos**: P0 17, P1 41, P2 18. No son endpoints únicos; crear una orden desde dos listados cuenta dos accesos perdidos. P0 significa impacto en operación financiera/stock; P1 mantenimiento/navegación operacional; P2 consulta/export/documentación. No equivale a autorización para reactivar.

Ventas: primera pérdida ffe0974, integración frontend, no merge. Se sustituyó el listado operacional por list-readonly y se retiraron modales. Capabilities de Owner EDESCE permiten las operaciones, pero el renderer no las ofrece. Informe específico adjunto.

### Pérdidas demostradas y commit de cambio

| Superficie | Commit | Capacidades ausentes en Next / prioridad |
|---|---|---|
| puntoVenta.list | ffe0974 | Emitir:P0; Anular:P0; Registrar abono:P0; Eliminar abono:P0; Despacho:P0; NC total:P0; NC parcial:P0; Historial abonos:P1; Consultar anulación:P1; Consultar NC:P1; Recuperar Nubefact:P1 |
| puntoVenta.listError | b52c3cd | Descartar error:P1; Imprimir documento:P2 |
| cashMovement.admin.index | 744d28b | Abrir caja:P0; Regularizar:P0; Documento:P2 |
| order.purchase.normal.indexV2 | abc9daa | Crear:P1; Editar:P1; Anular:P0; Cambiar estado:P1; Imprimir:P2 |
| order.purchase.general.indexV2 | b1fa9d5 | Crear normal:P1; Crear express:P1; Editar:P1; Anular:P0; Cambiar estado:P1; Imprimir:P2; Exportar:P2 |
| order.purchase.express.indexV2 | 3ed0b46 | Crear:P1; Editar:P1; Anular:P0; Cambiar estado:P1; Imprimir:P2; Detalle:P1 |
| order.purchase.delete | c32352c | Restaurar:P1; Imprimir:P2 |
| order.purchase.finance.index | 483a785 | Crear:P1; Detalle:P1; Editar:P1; Eliminar:P0; Imprimir:P2 |
| order.purchase.finance.delete | 744d28b | Restaurar:P1; Imprimir:P2 |
| order.purchase.finance.list.regularize | 744d28b | Detalle:P1; Imprimir:P2 |
| entry.purchase.indexV2 | 2ec61f3 | Crear:P1; Editar:P1; Anular:P0; Documentos:P2 |
| order.purchase.list.indexV2 | 2ec61f3 | Recibir entrada:P0; Ver orden:P1; Imprimir:P2 |
| entry.purchase.report.index | 6097da5 | Detalle de ítems:P2; Adjunto:P2 |
| invoice.index | fa8f7ed | Crear:P1; Anular:P0 |
| report.invoice.finance | fa8f7ed | Exportar:P2 |
| report.invoice.finance.sin.orden | fa8f7ed | Exportar:P2 |
| stockitems.index | 6de4406 | Ajustar stock:P0; Mínimo/máximo:P1; Inventariable:P1; Activo:P1 |
| dashboard.principal | dbca24e1 | Exportar importes:P2; Exportar materiales:P2 |
| material.stock.getItems | 6de4406 | Editar código ítem:P1 |
| materialtype.index | 8faf1ef | Eliminar selección:P1 |
| subtype.index | 8faf1ef | Eliminar selección:P1 |
| category.index | 8faf1ef | Eliminar selección:P1 |
| subcategory.index | 8faf1ef | Eliminar selección:P1 |
| exampler.index | 8faf1ef | Eliminar selección:P1 |
| brand.index | 8faf1ef | Eliminar selección:P1 |
| typescrap.index | 8faf1ef | Eliminar selección:P1 |
| unitmeasure.index | 8faf1ef | Eliminar selección:P1 |
| genero.index | 8faf1ef | Eliminar selección:P1 |
| talla.index | 8faf1ef | Eliminar selección:P1 |
| color.index | 8faf1ef | Eliminar selección:P1 |

### Clasificación y responsables

Predomina INTENTIONAL READ-ONLY EXCLUSION con reducción funcional real; no PRODUCT APPROVED RETIREMENT. La implementación frontend es responsable de quitar los accesos. Las fases históricas limitaron QA y en ocasiones solicitaron expresamente excluir acciones; esto explica la decisión de fase, pero no acredita autorización permanente de retiro del producto. No atribuir globalmente a DEV o a merges sin traza por función.

Cash Admin selecciona admin_readonly desde admin_index; apertura, regularización y documento no llegan a la nueva fachada. My Cash y Sesión propia conservan sus operaciones: no generalizar el hallazgo a esas pantallas. En compras/entradas/finance permanecen rutas y JS legacy pero se retiraron triggers o se sustituyó el script. Que Create/Edit exista integrado no restituye la navegación perdida desde el listado. En los once catálogos el bloqueo explícito data-backend-sync-open/disabled persiste en Next: no basta comparar nombres de handlers.

Ventas SUNAT: el botón de reintentar desapareció, pero el JS legacy conservado sólo tiene una referencia de render a data-reintentar_comprobante, sin handler demostrado. **UNKNOWN**, excluido del conteo de funcionalidades operativas perdidas hasta trazar su ejecución. Descartar sí conserva handler legacy y desaparece del runtime Next. No contar handlers huérfanos como capacidad previa demostrada.

### Paridad estática revisada (no QA mutadora)

cashMovement.my.index, cashRegister.session, index.credit.supplier, index.invoices.pending, quoteSale.index, quoteSale.index.facturadas, shipping_guides.show, shipping_guides.view, worker.index.

En Cotizaciones los parciales conservan modales/handlers y el menú cambia presentación, no semántica. Créditos conservan las operaciones tras su última integración. GRE list/show conserva emisión/consulta/recuperación/documentos según guards. Worker conserva acciones en dropdown. No se ejecutó ninguna de estas operaciones.

### Blockers reales frente a QA pendiente

| Caso | Evidencia HEAD | Responsable / decisión |
|---|---|---|
| Ajuste StockItems | routes/web.php:3771; InventoryMigrationController:891/917; POST fix/stock-item/output-adjustment sin permission explícita y búsquedas StockItem/StockLot sin company en el método | BACKEND SECURITY BLOCKER. DEV debe demostrar ownership/autorización antes de reactivación; no copiar botón legacy |
| Documentos | Guard HTTP no prueba protección de archivos públicos; ver SaleListDocumentController y backlog documental | Backend/Infra; no abrir archivos ni restaurar fallback público |
| Catálogos bulk | Rutas delete-multiple ya tienen destroy permission; DeleteMultipleMaterialTypesRequest valida ids tenant y DeleteMaterialTypes usa transacción/locks/referencias | Frontend gate puede estar obsoleto; comprobar cada servicio antes de levantarlo. No extrapolar MaterialType a los once |
| Create/Edit/Detail no seguros de familias legacy | Que listado sea seguro no certifica sus destinos; consultar blockers canónicos específicos | Decisión de Producto + DEV donde exista fallo real; mantener diferencia entre acceso perdido y recuperación segura |
| Ventas con guards actuales | SaleListGuard, snapshot, caja/estado, proveedor | QA PENDING no obliga a eliminar menú. No duplicar ni relajar autorización |

No hay prueba de que todas las capacidades ausentes necesiten un fix backend. No solicitar un bypass general. Guards correctos y estados inválidos son VALID BUSINESS STATE, no regresión.

### Falsos positivos y pendientes

- CSRF local eliminado puede estar en layout global; appAdmin2 lo contiene. No contarlo automáticamente.
- Correcciones de rutas copiadas de Género/Talla en Cédulas/Calidades no son pérdida de producto.
- Financial Summary reutiliza Blade de Cash pero su rama contextual no equivale a Cash Admin.
- Quitar JS de edición de una vista que ya era sólo detalle no prueba que antes existiera un submit accesible.
- POS V1/V2 incluidos en el inventario; sin rediseño ni ejecución. Su paridad semántica completa sigue pendiente.
- Roles, filtros, inputs, callbacks, condiciones de estado y payloads requieren cierre en las superficies UNKNOWN; el JSON adjunto permite localizar las diferencias, no certificarlas.
- OrderService, Proforma, ReferralGuide, FollowMaterial y Promotions retirados explícitamente: PRODUCT APPROVED RETIREMENT, fuera del plan de recuperación.

### Propuesta AGENTS.md para aprobación (NO aplicada)

> La paridad funcional es obligatoria en toda homologación visual. No retirar acciones, formularios, modales, navegación, handlers o capacidades legítimas por no ejecutarlas en QA. READ PATH, OUT OF SCOPE y MUTATION QA PENDING describen el alcance de validación, no el alcance funcional del producto. Conservar permisos, estados y guards backend. Ante un blocker real, documentar evidencia, impacto y alternativas y solicitar decisión explícita del propietario antes de retirar o reactivar una capacidad insegura. Todo retiro permanente requiere aprobación explícita. Cada homologación debe incluir matriz antes/después con acción, permiso, condición, endpoint, handler y estado QA. No declarar paridad sólo por render correcto, cero CSS o tests de lectura.

AGENTS actual ya exige preservar comportamiento; faltaba distinguir inequívocamente alcance QA y disponibilidad del producto. La propuesta refuerza esa regla, no autoriza operaciones inseguras.

### Plan de recuperación para aprobación — sin implementación

1. Ventas: historial y accesos read-only seguros; después menú/modal operacional con capacidades y snapshot actuales. No reinsertar list.js completo: duplicaría render/eventos. Mantener normal y errores separados.
2. Pagos/despacho/emisión/anulación/NC: conservar guards, integrar presentación y separar pruebas mutadoras autorizadas en entorno aislado. No llamar proveedor en esta fase.
3. Cash Admin: recuperar presentación de apertura/regularización sólo contra contratos actuales de autorización, sesión y CashRegularization; documentación protegida se decide aparte.
4. Compras normales/express/financieras y entradas: navegación segura y detalle primero; restauración/anulación/recepción después de revisar cada guard. Mantener módulos retirados fuera.
5. Mantenedores bulk: reauditar gates obsoletos individualmente con DEV; decidir activación sin fabricar permisos. Inventario: el ajuste inseguro espera fix de ownership.
6. Reports/documentos: exportación y storage requieren aprobación de seguridad; no confundir ausencia de QA con retiro de UI.
7. Cerrar el contraste semántico de UNKNOWN antes de declarar paridad global. Pruebas de render por permisos/estados y payloads; mutaciones en suite aislada, separadas del inventario estático.

Producto debe aprobar prioridades y retiros/restricciones permanentes; esta auditoría no los decide. No se implementó recuperación ni se alteró AGENTS. Sólo se escribieron los dos informes.

## Matriz global — evidencia estructural por superficie

| Módulo / pantalla | URL local | Funciones originales (anclas) | Funciones actuales (runtime) | Diferencias a verificar | Causa / blocker / responsable | Prioridad |
|---|---|---|---|---|---|---|
| password.required.edit | [http://127.0.0.1:8080/password/cambio-obligatorio](http://127.0.0.1:8080/password/cambio-obligatorio) | js/auth/required-password-change.js | auth.required-password-change → js/auth/required-password-change.js | name="password, name="password_confirmation | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 80cb50d8 | Pendiente de clasificación semántica |
| tenantCompany.index | [http://127.0.0.1:8080/dashboard/configuracion/empresas](http://127.0.0.1:8080/dashboard/configuracion/empresas) | js/tenantCompany/index.js | tenantCompany.index → js/tenantCompany/index.js | route('tenantBranch.create | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist a9679aa4 | Pendiente de clasificación semántica |
| tenantCompany.create | [http://127.0.0.1:8080/dashboard/configuracion/empresas/crear](http://127.0.0.1:8080/dashboard/configuracion/empresas/crear) | js/tenantCompany/create.js | tenantCompany.create → js/tenantCompany/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist a9679aa4 | Pendiente de clasificación semántica |
| tenantCompany.edit | http://127.0.0.1:8080/dashboard/configuracion/empresas/{id}/editar — DATASET REQUIRED | js/inventory/managedWarehouses.js, js/tenantCompany/edit.js | tenantCompany.edit → js/inventory/managedWarehouses.js, js/tenantCompany/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist a9679aa4 | Pendiente de clasificación semántica |
| tenantBranch.index | http://127.0.0.1:8080/dashboard/configuracion/empresas/{companyId}/sucursales — DATASET REQUIRED | js/tenantBranch/index.js | tenantBranch.index → js/tenantBranch/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 399f7d9c | Pendiente de clasificación semántica |
| tenantBranch.create | http://127.0.0.1:8080/dashboard/configuracion/empresas/{companyId}/sucursales/crear — DATASET REQUIRED | js/tenantBranch/create.js | tenantBranch.create → js/tenantBranch/create.js | name="price_list_id | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 399f7d9c | Pendiente de clasificación semántica |
| tenantBranch.edit | http://127.0.0.1:8080/dashboard/configuracion/sucursales/{id}/editar — DATASET REQUIRED | js/inventory/managedWarehouses.js, js/tenantBranch/edit.js | tenantBranch.edit → js/inventory/managedWarehouses.js, js/tenantBranch/edit.js | name="price_list_id, route('tenantCompany.index | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 399f7d9c | Pendiente de clasificación semántica |
| companyBankAccount.index | [http://127.0.0.1:8080/dashboard/configuracion/cuentas-bancarias](http://127.0.0.1:8080/dashboard/configuracion/cuentas-bancarias) | js/companyBankAccount/index.js | companyBankAccount.index → js/companyBankAccount/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 1f972a7c | Pendiente de clasificación semántica |
| companyBankAccount.create | [http://127.0.0.1:8080/dashboard/configuracion/cuentas-bancarias/crear](http://127.0.0.1:8080/dashboard/configuracion/cuentas-bancarias/crear) | js/companyBankAccount/form.js | companyBankAccount.create → js/companyBankAccount/form.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 1f972a7c | Pendiente de clasificación semántica |
| companyBankAccount.edit | http://127.0.0.1:8080/dashboard/configuracion/cuentas-bancarias/{id}/editar — DATASET REQUIRED | js/companyBankAccount/form.js | companyBankAccount.edit → js/companyBankAccount/form.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 1f972a7c | Pendiente de clasificación semántica |
| companySetting.index | [http://127.0.0.1:8080/dashboard/configuracion/configuraciones/empresa](http://127.0.0.1:8080/dashboard/configuracion/configuraciones/empresa) | js/companySetting/index.js | companySetting.index → js/companySetting/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 58a7841a | Pendiente de clasificación semántica |
| workforce.index | [http://127.0.0.1:8080/dashboard/configuracion/workforces-default](http://127.0.0.1:8080/dashboard/configuracion/workforces-default) | js/workforce/index.js | workforce.index → js/workforce/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 3636c1f1 | Pendiente de clasificación semántica |
| dashboard.principal | [http://127.0.0.1:8080/dashboard/principal](http://127.0.0.1:8080/dashboard/principal) | js/dashboard/ordersChart.js, js/report/charts.js, js/report/reportAmount.js, js/report/viewReport.js | dashboard.dashboard → js/dashboard/ordersChart.js | data-active, data-card-widget, data-dismiss, data-fecha, data-fgColor, data-filter, data-height, data-id, data-item, data-placement, data-readonly, data-target, data-toggle, data-url, data-user, data-width, id="btn-download, id="btn-downloadEntries, id="btn-export-cashflow-range, id="btn-export-range-sales, id="btn-newRotation, id="btn-pays, id="btn-refresh, id="btn-submitDownload, id="btn-submitExport, id="btnViewReportDollarsQuote, id="btnViewReportIncomeExpenseDollars, id="btnViewReportIncomeExpenseMix, id="btnViewReportIncomeExpenseSoles, id="btnViewReportSolesQuote, id="btnViewReportUtilitiesDollars, id="btnViewReportUtilitiesMix, id="btnViewReportUtilitiesSoles, id="modalEntries, id="modalLocations, id="modalViewReportDollars, id="modalViewReportIncomeExpenseDollars, id="modalViewReportIncomeExpenseMix, id="modalViewReportIncomeExpenseSoles, id="modalViewReportSoles, id="modalViewReportUtilitiesDollars, id="modalViewReportUtilitiesMix, id="modalViewReportUtilitiesSoles, name="end, name="location, name="month, name="start, name="typeEntry, name="year, route('dashboard.principal, route('report.excel.amount, route('report.excel.materials | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist dbca24e1 | Pendiente de clasificación semántica |
| priceList.index | [http://127.0.0.1:8080/dashboard/prices](http://127.0.0.1:8080/dashboard/prices) | js/priceList/index.js | priceList.index → js/priceList/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist b898890a | Pendiente de clasificación semántica |
| customer.index | [http://127.0.0.1:8080/dashboard/clientes](http://127.0.0.1:8080/dashboard/clientes) | js/customer/index.js | customer.index → js/customer/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 5242c73f | Pendiente de clasificación semántica |
| customer.create | [http://127.0.0.1:8080/dashboard/crear/cliente](http://127.0.0.1:8080/dashboard/crear/cliente) | js/customer/create.js | customer.create → js/customer/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 5242c73f | Pendiente de clasificación semántica |
| customer.edit | http://127.0.0.1:8080/dashboard/editar/cliente/{id} — DATASET REQUIRED | js/customer/edit.js | customer.edit → js/customer/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 5242c73f | Pendiente de clasificación semántica |
| customer.indexrestore | [http://127.0.0.1:8080/dashboard/clientes/restore](http://127.0.0.1:8080/dashboard/clientes/restore) | js/customer/restore.js | customer.restore → js/customer/restore.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 5242c73f | Pendiente de clasificación semántica |
| supplier.index | [http://127.0.0.1:8080/dashboard/proveedores](http://127.0.0.1:8080/dashboard/proveedores) | js/supplier/index.js | supplier.index → js/supplier/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist cffac49e | Pendiente de clasificación semántica |
| supplier.create | [http://127.0.0.1:8080/dashboard/crear/proveedor](http://127.0.0.1:8080/dashboard/crear/proveedor) | js/supplier/create.js | supplier.create → js/supplier/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist cffac49e | Pendiente de clasificación semántica |
| supplier.edit | http://127.0.0.1:8080/dashboard/editar/proveedor/{id} — DATASET REQUIRED | js/supplier/edit.js | supplier.edit → js/supplier/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist cffac49e | Pendiente de clasificación semántica |
| supplier.indexrestore | [http://127.0.0.1:8080/dashboard/proveedores/restore](http://127.0.0.1:8080/dashboard/proveedores/restore) | js/supplier/restore.js | supplier.restore → js/supplier/restore.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist cffac49e | Pendiente de clasificación semántica |
| contactName.index | [http://127.0.0.1:8080/dashboard/contactos](http://127.0.0.1:8080/dashboard/contactos) | js/contactName/index.js | contactName.index → js/contactName/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 3883302a | Pendiente de clasificación semántica |
| contactName.create | [http://127.0.0.1:8080/dashboard/crear/contacto](http://127.0.0.1:8080/dashboard/crear/contacto) | js/contactName/create.js | contactName.create → js/contactName/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 3883302a | Pendiente de clasificación semántica |
| contactName.edit | http://127.0.0.1:8080/dashboard/editar/contacto/{id} — DATASET REQUIRED | js/contactName/edit.js | contactName.edit → js/contactName/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 3883302a | Pendiente de clasificación semántica |
| contactName.indexrestore | [http://127.0.0.1:8080/dashboard/contactos/restore](http://127.0.0.1:8080/dashboard/contactos/restore) | js/contactName/restore.js | contactName.restore → js/contactName/restore.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 3883302a | Pendiente de clasificación semántica |
| materialtype.index | [http://127.0.0.1:8080/dashboard/TiposMateriales](http://127.0.0.1:8080/dashboard/TiposMateriales) | js/materialtype/index.js | materialtype.index → js/materialtype/index.js | Sin tokens retirados detectados; NO equivale a PASS | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| materialtype.create | [http://127.0.0.1:8080/dashboard/crear/tipomaterial](http://127.0.0.1:8080/dashboard/crear/tipomaterial) | js/materialtype/create.js | materialtype.create → js/materialtype/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| materialtype.edit | http://127.0.0.1:8080/dashboard/editar/tipomaterial/{id} — DATASET REQUIRED | js/materialtype/edit.js | materialtype.edit → js/materialtype/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| subtype.index | [http://127.0.0.1:8080/dashboard/Subtipos](http://127.0.0.1:8080/dashboard/Subtipos) | js/subtype/index.js | subtype.index → js/subtype/index.js | route('subtype.index | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| subtype.create | [http://127.0.0.1:8080/dashboard/crear/subtipo](http://127.0.0.1:8080/dashboard/crear/subtipo) | js/subtype/create.js | subtype.create → js/subtype/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| subtype.edit | http://127.0.0.1:8080/dashboard/editar/subtipo/{id} — DATASET REQUIRED | js/subtype/edit.js | subtype.edit → js/subtype/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| category.index | [http://127.0.0.1:8080/dashboard/Categorias](http://127.0.0.1:8080/dashboard/Categorias) | js/Category/index.js | category.index → js/Category/index.js | route('category.index | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| category.create | [http://127.0.0.1:8080/dashboard/crear/categoria](http://127.0.0.1:8080/dashboard/crear/categoria) | js/Category/create.js | category.create → js/Category/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| category.edit | http://127.0.0.1:8080/dashboard/editar/categoria/{id} — DATASET REQUIRED | js/Category/edit.js | category.edit → js/Category/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| subcategory.index | [http://127.0.0.1:8080/dashboard/Subcategorias](http://127.0.0.1:8080/dashboard/Subcategorias) | js/subcategory/index.js | subcategory.index → js/subcategory/index.js | route('subcategory.index | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| subcategory.create | [http://127.0.0.1:8080/dashboard/crear/subcategoria](http://127.0.0.1:8080/dashboard/crear/subcategoria) | js/subcategory/create.js | subcategory.create → js/subcategory/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| subcategory.edit | http://127.0.0.1:8080/dashboard/editar/subcategoria/{id} — DATASET REQUIRED | js/subcategory/edit.js | subcategory.edit → js/subcategory/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| categoryInvoice.index | [http://127.0.0.1:8080/dashboard/categorias/facturas](http://127.0.0.1:8080/dashboard/categorias/facturas) | js/categoryInvoice/index.js | categoryInvoice.index → js/categoryInvoice/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 651667fa | Pendiente de clasificación semántica |
| categoryInvoice.create | [http://127.0.0.1:8080/dashboard/crear/categoria/facturas](http://127.0.0.1:8080/dashboard/crear/categoria/facturas) | js/categoryInvoice/create.js | categoryInvoice.create → js/categoryInvoice/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 651667fa | Pendiente de clasificación semántica |
| categoryInvoice.edit | http://127.0.0.1:8080/dashboard/editar/categoria/factura/{id} — DATASET REQUIRED | js/categoryInvoice/edit.js | categoryInvoice.edit → js/categoryInvoice/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 651667fa | Pendiente de clasificación semántica |
| exampler.index | [http://127.0.0.1:8080/dashboard/Modelos](http://127.0.0.1:8080/dashboard/Modelos) | js/exampler/index.js | exampler.index → js/exampler/index.js | route('exampler.index | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| exampler.create | [http://127.0.0.1:8080/dashboard/crear/modelo](http://127.0.0.1:8080/dashboard/crear/modelo) | js/exampler/create.js | exampler.create → js/exampler/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| exampler.edit | http://127.0.0.1:8080/dashboard/editar/modelo/{id} — DATASET REQUIRED | js/exampler/edit.js | exampler.edit → js/exampler/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| brand.index | [http://127.0.0.1:8080/dashboard/Marcas](http://127.0.0.1:8080/dashboard/Marcas) | js/brand/index.js | brand.index → js/brand/index.js | route('brand.index | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| brand.create | [http://127.0.0.1:8080/dashboard/crear/marca](http://127.0.0.1:8080/dashboard/crear/marca) | js/brand/create.js | brand.create → js/brand/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| brand.edit | http://127.0.0.1:8080/dashboard/editar/marca/{id} — DATASET REQUIRED | js/brand/edit.js | brand.edit → js/brand/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| warrant.index | [http://127.0.0.1:8080/dashboard/Cédulas](http://127.0.0.1:8080/dashboard/Cédulas) | js/warrant/index.js | warrant.index → js/warrant/index.js | route('genero.create, route('genero.destroy, route('genero.index | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist b898890a | Pendiente de clasificación semántica |
| warrant.create | [http://127.0.0.1:8080/dashboard/crear/cedula](http://127.0.0.1:8080/dashboard/crear/cedula) | js/warrant/create.js | warrant.create → js/warrant/create.js | route('genero.store | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist b898890a | Pendiente de clasificación semántica |
| warrant.edit | http://127.0.0.1:8080/dashboard/editar/cedula/{id} — DATASET REQUIRED | js/warrant/edit.js | warrant.edit → js/warrant/edit.js | route('genero.index, route('genero.update | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist b898890a | Pendiente de clasificación semántica |
| quality.index | [http://127.0.0.1:8080/dashboard/Calidades](http://127.0.0.1:8080/dashboard/Calidades) | js/quality/index.js | quality.index → js/quality/index.js | route('talla.create, route('talla.index | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist d287d4ca | Pendiente de clasificación semántica |
| quality.create | [http://127.0.0.1:8080/dashboard/crear/calidad](http://127.0.0.1:8080/dashboard/crear/calidad) | js/quality/create.js | quality.create → js/quality/create.js | route('talla.index, route('talla.store | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist d287d4ca | Pendiente de clasificación semántica |
| quality.edit | http://127.0.0.1:8080/dashboard/editar/calidad/{id} — DATASET REQUIRED | js/quality/edit.js | quality.edit → js/quality/edit.js | route('talla.index, route('talla.update | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist d287d4ca | Pendiente de clasificación semántica |
| typescrap.index | [http://127.0.0.1:8080/dashboard/Retacerías](http://127.0.0.1:8080/dashboard/Retacerías) | js/typescrap/index.js | typeScrap.index → js/typescrap/index.js | Sin tokens retirados detectados; NO equivale a PASS | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| typescrap.create | [http://127.0.0.1:8080/dashboard/crear/retaceria](http://127.0.0.1:8080/dashboard/crear/retaceria) | js/typescrap/create.js | typeScrap.create → js/typescrap/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| typescrap.edit | http://127.0.0.1:8080/dashboard/editar/retaceria/{id} — DATASET REQUIRED | js/typescrap/edit.js | typeScrap.edit → js/typescrap/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| unitmeasure.index | [http://127.0.0.1:8080/dashboard/Unidades](http://127.0.0.1:8080/dashboard/Unidades) | js/unitmeasure/index.js | unitMeasure.index → js/unitmeasure/index.js | route('unitmeasure.index | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| unitmeasure.create | [http://127.0.0.1:8080/dashboard/crear/unidad](http://127.0.0.1:8080/dashboard/crear/unidad) | js/unitmeasure/create.js | unitMeasure.create → js/unitmeasure/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| unitmeasure.edit | http://127.0.0.1:8080/dashboard/editar/unidad/{id} — DATASET REQUIRED | js/unitmeasure/edit.js | unitMeasure.edit → js/unitmeasure/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| genero.index | [http://127.0.0.1:8080/dashboard/generos](http://127.0.0.1:8080/dashboard/generos) | js/genero/index.js | genero.index → js/genero/index.js | route('genero.index | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| genero.create | [http://127.0.0.1:8080/dashboard/crear/genero](http://127.0.0.1:8080/dashboard/crear/genero) | js/genero/create.js | genero.create → js/genero/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| genero.edit | http://127.0.0.1:8080/dashboard/editar/genero/{id} — DATASET REQUIRED | js/genero/edit.js | genero.edit → js/genero/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| talla.index | [http://127.0.0.1:8080/dashboard/tallas](http://127.0.0.1:8080/dashboard/tallas) | js/talla/index.js | talla.index → js/talla/index.js | route('talla.index | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| talla.create | [http://127.0.0.1:8080/dashboard/crear/talla](http://127.0.0.1:8080/dashboard/crear/talla) | js/talla/form.js | talla.create → js/talla/form.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| talla.edit | http://127.0.0.1:8080/dashboard/editar/talla/{id} — DATASET REQUIRED | js/talla/form.js | talla.edit → js/talla/form.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| material.create | [http://127.0.0.1:8080/dashboard/crear/material](http://127.0.0.1:8080/dashboard/crear/material) | Baseline no localizado en parent; js/material/create.js | materialDetailSetting.required → Blade/partials ; material.create → js/material/create.js | ; data-card-widget, name="afecto_inventario | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist aa2ded06 | Pendiente de clasificación semántica |
| material.edit | http://127.0.0.1:8080/dashboard/editar/material/{id} — DATASET REQUIRED | Baseline no localizado en parent; js/material/edit.js | materialDetailSetting.required → Blade/partials ; material.edit → js/material/edit.js, js/material/inventory-context.js | ; data-card-widget, data-delete, data-image_label, data-stock_maximo, data-stock_minimo, name="afecto_inventario, name="precio_promedio, name="reservado, name="stock_actual, name="stock_max, name="stock_min, name="ultimo_costo, name="warehouse, name="warehouse_id, route('material.index | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| material.indexV2 | [http://127.0.0.1:8080/dashboard/listado/materiales/v2](http://127.0.0.1:8080/dashboard/listado/materiales/v2) | js/material/indexV2.js | material.indexv2 → js/material/indexV2.js, js/material/inventory-context.js, js/material/presentations-branch.js | data-ver_items, name="calidad, name="cedula | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| material.stock.getItems | http://127.0.0.1:8080/dashboard/view/stock/material/items/{id} — DATASET REQUIRED | js/material/inventory-context.js, js/material/stockItems_items.js | material.stockItems_items → js/material/inventory-context.js, js/material/stockItems_items.js | data-dismiss, id="btnGuardarCodigoItem, id="modalEditarCodigoItem | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 6de44064 | Pendiente de clasificación semántica |
| warehouseTransfer.index | [http://127.0.0.1:8080/dashboard/traslados/almacenes](http://127.0.0.1:8080/dashboard/traslados/almacenes) | Blade previo | warehouseTransfer.index → js/warehouseTransfer/operations.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 087f9311 | Pendiente de clasificación semántica |
| warehouseTransfer.create | [http://127.0.0.1:8080/dashboard/traslados/almacenes/crear](http://127.0.0.1:8080/dashboard/traslados/almacenes/crear) | js/warehouseTransfer/operations.js | warehouseTransfer.create → js/warehouseTransfer/operations.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 087f9311 | Pendiente de clasificación semántica |
| warehouseTransfer.show | http://127.0.0.1:8080/dashboard/traslados/almacenes/{id} — DATASET REQUIRED | js/warehouseTransfer/operations.js | warehouseTransfer.show → js/warehouseTransfer/operations.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 087f9311 | Pendiente de clasificación semántica |
| warehouseAvailability.index | [http://127.0.0.1:8080/dashboard/disponibilidad/almacenes](http://127.0.0.1:8080/dashboard/disponibilidad/almacenes) | js/warehouseAvailability/index.js | warehouseAvailability.index → js/warehouseAvailability/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist a90d322a | Pendiente de clasificación semántica |
| entry.purchase.indexV2 | [http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2](http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2) | js/entry/index_entry_purchaseV2.js | entry.index_entry_purchasev2 → js/entry/index_entry_purchaseV2.js | data-anular, data-buttons, data-code, data-delete, data-detail, data-detalles, data-dismiss, data-documentos, data-editar, data-file, data-i, data-image, data-imagen, data-length, data-location, data-material, data-pdf, data-placement, data-price, data-quantity, data-src, data-state, data-url, data-weight, data-width, id="modalDelete, id="modalImage, id="modalItems, name="material_id, route('entry.purchase.create, route('material.destroy | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 2ec61f31 | Pendiente de clasificación semántica |
| entry.purchase.show | http://127.0.0.1:8080/dashboard/entrada/compra/visualizar/{entry} — DATASET REQUIRED | js/entry/edit_entry_purchase.js | entry.show_entry_purchase → Blade/partials  | data-bootstrap-switch, data-card-widget, data-delete, data-dismiss, data-image, data-locationGroup, data-locations, data-off-color, data-off-text, data-on-color, data-on-text, data-series, data-stateGroup, data-states, data-toggle, data-url, id="btn-grouped, id="btn-saveGroupItems, id="btn-saveItems, id="btnNew, id="modalAddGroupItems, id="modalAddItems, id="modalImage, name="date_invoice, name="deferred_invoice, name="entry_id, name="entry_type, name="invoice, name="locationGroup, name="locations[], name="material_GroupSelected, name="material_selected, name="observation, name="price_GroupSelected, name="price_selected, name="purchase_order, name="quantity_GroupSelected, name="quantity_selected, name="referral_guide, name="series[], name="supplier_id, route('dashboard.principal, route('entry.purchase.update | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 483a7858 | Pendiente de clasificación semántica |
| order.purchase.list.indexV2 | [http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2](http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2) | js/entry/listOrdersV2.js | entry.listOrderPurchaseV2 → js/entry/listOrdersV2.js | data-buttons, data-crear_entrada, data-imprimir, data-placement, data-ver_orden, name="deliveryDate | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 2ec61f31 | Pendiente de clasificación semántica |
| entry.purchase.report.index | [http://127.0.0.1:8080/dashboard/entradas/compra/reporte](http://127.0.0.1:8080/dashboard/entradas/compra/reporte) | js/entry/index_entry_purchase_report.js | entry.index_entry_purchase_report → js/entry/index_entry_purchase_report.js | data-code, data-dismiss, data-i, data-length, data-location, data-material, data-price, data-quantity, data-state, data-url, data-weight, data-width, id="modalDelete, id="modalImage, id="modalItems, name="material_id, route('material.destroy | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 6097da5e | Pendiente de clasificación semántica |
| dashboard/ver/orden/compra/normal/{id} | http://127.0.0.1:8080/dashboard/ver/orden/compra/normal/{id} — DATASET REQUIRED | js/orderPurchase/showNormal.js | orderPurchase.showNormal → Blade/partials  | data-bootstrap-switch, data-card-widget, data-code, data-delete, data-description, data-id, data-material, data-price, data-price2, data-quantity, data-toggle, data-total, data-url, id="btn-currency, name="currency_order, name="date_arrival, name="date_order, name="observation, name="order_id, name="purchase_condition, name="purchase_order, name="quote_id, name="quote_supplier, route('dashboard.principal | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist abc9daa0 | Pendiente de clasificación semántica |
| order.purchase.delete | [http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas](http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas) | js/orderPurchase/companyContext.js, js/orderPurchase/deleteGeneral.js | orderPurchase.deleteGeneral → js/orderPurchase/deleteGeneral.js | data-dismiss, data-url, id="modalDelete, name="subcategory_id, route('subcategory.destroy | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist c32352c5 | Pendiente de clasificación semántica |
| dashboard/ver/orden/compra/eliminada/{id} | http://127.0.0.1:8080/dashboard/ver/orden/compra/eliminada/{id} — DATASET REQUIRED | js/orderPurchase/showNormal.js | orderPurchase.showDelete → Blade/partials  | data-bootstrap-switch, data-card-widget, data-code, data-delete, data-description, data-id, data-material, data-price, data-price2, data-quantity, data-toggle, data-total, data-url, id="btn-currency, name="currency_order, name="date_arrival, name="date_order, name="observation, name="order_id, name="purchase_condition, name="purchase_order, name="quote_id, name="quote_supplier, route('dashboard.principal | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist c32352c5 | Pendiente de clasificación semántica |
| order.purchase.general.indexV2 | [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2) | js/orderPurchase/generalV2.js | orderPurchase.general_v2 → js/orderPurchase/generalV2.js | data-anular, data-delete, data-dismiss, data-editar, data-estado, data-imprimir, data-name, data-placement, data-url, id="btn-changeState, id="btn-export, id="modalState, name="orderPurchase_id, name="stateOrder, route('order.purchase.express.create, route('order.purchase.normal.create, route('state.order.purchase.change | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist b1fa9d52 | Pendiente de clasificación semántica |
| order.purchase.express.indexV2 | [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2) | js/orderPurchase/expressV2.js | orderPurchase.express_v2 → js/orderPurchase/expressV2.js | data-anular, data-delete, data-dismiss, data-editar, data-estado, data-imprimir, data-name, data-placement, data-toggle, data-url, data-ver_orden, id="btn-changeState, id="modalState, name="orderPurchase_id, name="stateOrder, route('order.purchase.express.create, route('state.order.purchase.change | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 3ed0b46b | Pendiente de clasificación semántica |
| order.purchase.normal.indexV2 | [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2) | js/orderPurchase/companyContext.js, js/orderPurchase/normalV2.js | orderPurchase.normal_v2 → js/orderPurchase/normalV2.js | data-anular, data-delete, data-dismiss, data-editar, data-estado, data-imprimir, data-name, data-placement, data-url, id="btn-changeState, id="modalState, name="orderPurchase_id, name="stateOrder, route('order.purchase.normal.create, route('state.order.purchase.change | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist abc9daa0 | Pendiente de clasificación semántica |
| user.profile | [http://127.0.0.1:8080/dashboard/perfil](http://127.0.0.1:8080/dashboard/perfil) | js/user/profile.js | user.profile → js/user/profile.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 80cb50d8 | Pendiente de clasificación semántica |
| invoice.index | [http://127.0.0.1:8080/dashboard/factura/compra](http://127.0.0.1:8080/dashboard/factura/compra) | js/invoice/index_invoice.js | invoice.index_invoice → js/invoice/finance-readonly.js | data-description, data-dismiss, data-price, data-quantity, data-subtotal, data-taxes, data-total, data-unit, data-url, id="modalDelete, id="modalImage, id="modalItems, name="material_id, route('dashboard.principal, route('invoice.create, route('material.destroy | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist fa8f7ede | Pendiente de clasificación semántica |
| invoice.index.annulled | [http://127.0.0.1:8080/dashboard/factura/compra/anuladas](http://127.0.0.1:8080/dashboard/factura/compra/anuladas) | js/invoice/index_invoice_annulled.js | invoice.index_invoice_annulled → js/invoice/finance-readonly.js | data-description, data-dismiss, data-price, data-quantity, data-subtotal, data-taxes, data-total, data-unit, data-url, id="modalDelete, id="modalImage, id="modalItems, name="material_id, route('dashboard.principal, route('invoice.create, route('material.destroy | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist fa8f7ede | Pendiente de clasificación semántica |
| invoice.create | [http://127.0.0.1:8080/dashboard/crear/factura/compra](http://127.0.0.1:8080/dashboard/crear/factura/compra) | js/invoice/invoice.js | invoice.create_invoice → js/invoice/invoice.js | data-card-widget | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6abf4528 | Pendiente de clasificación semántica |
| invoice.edit | http://127.0.0.1:8080/dashboard/factura/compra/editar/{entry} — DATASET REQUIRED | js/invoice/edit_invoice.js | invoice.edit_invoice → js/invoice/edit_invoice.js | data-card-widget, data-toggle | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6abf4528 | Pendiente de clasificación semántica |
| invoice.show | http://127.0.0.1:8080/dashboard/factura/compra/ver/{entry} — DATASET REQUIRED | js/invoice/edit_invoice.js | invoice.show_invoice → Blade/partials  | data-bootstrap-switch, data-card-widget, data-deleteNew, data-description, data-dismiss, data-image, data-locationGroup, data-locations, data-off-color, data-off-text, data-on-color, data-on-text, data-price, data-quantity, data-series, data-stateGroup, data-states, data-subtotal, data-taxes, data-toggle, data-total, data-unit, data-url, id="btn-currency, id="btn-saveGroupItems, id="btn-saveItems, id="btn-submit, id="btnNew, id="modalAddGroupItems, id="modalAddItems, id="modalImage, name="currency_invoice, name="date_invoice, name="entry_id, name="entry_type, name="image, name="invoice, name="locationGroup, name="locations[], name="material_GroupSelected, name="material_selected, name="observation, name="price_GroupSelected, name="price_selected, name="purchase_order, name="quantity_GroupSelected, name="quantity_selected, name="series[], route('dashboard.principal, route('invoice.index | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist fa8f7ede | Pendiente de clasificación semántica |
| index.credit.supplier | [http://127.0.0.1:8080/dashboard/control/creditos](http://127.0.0.1:8080/dashboard/control/creditos) | js/credit/index_creditSupplier.js, js/credit/permissions.js | credit.indexSupplierCredit → js/credit/index_creditSupplier.js, js/credit/permissions.js | Sin tokens retirados detectados; NO equivale a PASS | PARIDAD ESTÁTICA DE ACCIONES; no certifica mutaciones; inclusión allowlist 483a7858 | Pendiente de clasificación semántica |
| index.invoices.pending | [http://127.0.0.1:8080/dashboard/faturas/proveedores/pendientes](http://127.0.0.1:8080/dashboard/faturas/proveedores/pendientes) | js/credit/index_invoicesPending.js, js/credit/permissions.js | credit.indexInvoicesPending → js/credit/index_invoicesPending.js, js/credit/permissions.js | Sin tokens retirados detectados; NO equivale a PASS | PARIDAD ESTÁTICA DE ACCIONES; no certifica mutaciones; inclusión allowlist 483a7858 | Pendiente de clasificación semántica |
| paymentDeadline.index | [http://127.0.0.1:8080/dashboard/plazos/pagos](http://127.0.0.1:8080/dashboard/plazos/pagos) | js/paymentDeadline/index.js | paymentDeadline.index → js/paymentDeadline/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist b3a670b6 | Pendiente de clasificación semántica |
| paymentDeadline.create | [http://127.0.0.1:8080/dashboard/crear/plazo/pago](http://127.0.0.1:8080/dashboard/crear/plazo/pago) | js/paymentDeadline/create.js | paymentDeadline.create → js/paymentDeadline/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist b3a670b6 | Pendiente de clasificación semántica |
| paymentDeadline.edit | http://127.0.0.1:8080/dashboard/editar/plazo/pago/{id} — DATASET REQUIRED | js/paymentDeadline/edit.js | paymentDeadline.edit → js/paymentDeadline/edit.js | route('quality.index | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist b3a670b6 | Pendiente de clasificación semántica |
| porcentageQuote.index | [http://127.0.0.1:8080/dashboard/porcentajes/cotizaciones](http://127.0.0.1:8080/dashboard/porcentajes/cotizaciones) | js/porcentagesQuote/index.js | porcentagesQuote.index → js/porcentagesQuote/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 06533c55 | Pendiente de clasificación semántica |
| porcentageQuote.create | [http://127.0.0.1:8080/dashboard/crear/porcentaje](http://127.0.0.1:8080/dashboard/crear/porcentaje) | js/porcentagesQuote/create.js | porcentagesQuote.create → js/porcentagesQuote/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 06533c55 | Pendiente de clasificación semántica |
| porcentageQuote.edit | http://127.0.0.1:8080/dashboard/editar/porcentaje/cotizacion/{id} — DATASET REQUIRED | js/porcentagesQuote/edit.js | porcentagesQuote.edit → js/porcentagesQuote/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 06533c55 | Pendiente de clasificación semántica |
| report.invoice.finance | [http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas](http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas) | js/invoice/report_invoice.js | invoice.report_invoice → js/invoice/finance-readonly.js | data-description, data-dismiss, data-price, data-quantity, data-subtotal, data-taxes, data-total, data-unit, data-url, id="btn-export, id="modalDelete, id="modalImage, id="modalItems, name="material_id, route('dashboard.principal, route('invoice.create, route('material.destroy | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist fa8f7ede | Pendiente de clasificación semántica |
| report.invoice.finance.sin.orden | [http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas/sin/orden](http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas/sin/orden) | js/invoice/report_invoice_sin_orden.js | invoice.report_invoice_sin_orden → js/invoice/finance-readonly.js | data-description, data-dismiss, data-price, data-quantity, data-subtotal, data-taxes, data-total, data-unit, data-url, id="btn-export, id="modalDelete, id="modalImage, id="modalItems, name="material_id, route('dashboard.principal, route('invoice.create, route('material.destroy | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist fa8f7ede | Pendiente de clasificación semántica |
| worker.index | [http://127.0.0.1:8080/dashboard/colaboradores](http://127.0.0.1:8080/dashboard/colaboradores) | js/worker/index.js | worker.index → js/worker/index.js | Sin tokens retirados detectados; NO equivale a PASS | PARIDAD ESTÁTICA DE ACCIONES; no certifica mutaciones; inclusión allowlist 5a79bc83 | Pendiente de clasificación semántica |
| worker.create | [http://127.0.0.1:8080/dashboard/registrar/colaborador](http://127.0.0.1:8080/dashboard/registrar/colaborador) | js/worker/create.js | worker.create → js/worker/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 5a79bc83 | Pendiente de clasificación semántica |
| worker.edit | http://127.0.0.1:8080/dashboard/editar/colaborador/{id} — DATASET REQUIRED | js/worker/edit.js | worker.edit → js/worker/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 5a79bc83 | Pendiente de clasificación semántica |
| worker.enable | [http://127.0.0.1:8080/dashboard/habilitar/colaborador](http://127.0.0.1:8080/dashboard/habilitar/colaborador) | js/worker/indexEnable.js | worker.indexEnable → js/worker/indexEnable.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 996a31a4 | Pendiente de clasificación semántica |
| civilStatuses.index | [http://127.0.0.1:8080/dashboard/estado/civil](http://127.0.0.1:8080/dashboard/estado/civil) | js/civilStatus/index.js | civilStatus.index → js/civilStatus/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| civilStatuses.create | [http://127.0.0.1:8080/dashboard/crear/estado/civil](http://127.0.0.1:8080/dashboard/crear/estado/civil) | js/civilStatus/create.js | civilStatus.create → js/civilStatus/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| civilStatuses.edit | http://127.0.0.1:8080/dashboard/editar/estado/civil/{id} — DATASET REQUIRED | js/civilStatus/create.js | civilStatus.edit → js/civilStatus/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| civilStatuses.deleted | [http://127.0.0.1:8080/dashboard/estado/civil/eliminados](http://127.0.0.1:8080/dashboard/estado/civil/eliminados) | js/civilStatus/indexDeleted.js | civilStatus.indexDeleted → js/civilStatus/indexDeleted.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| relationship.index | [http://127.0.0.1:8080/dashboard/parentescos](http://127.0.0.1:8080/dashboard/parentescos) | js/relationship/index.js | relationship.index → js/relationship/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| relationship.create | [http://127.0.0.1:8080/dashboard/crear/parentesco](http://127.0.0.1:8080/dashboard/crear/parentesco) | js/relationship/create.js | relationship.create → js/relationship/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| relationship.edit | http://127.0.0.1:8080/dashboard/editar/parentesco/{id} — DATASET REQUIRED | js/relationship/edit.js | relationship.edit → js/relationship/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| workFunctions.index | [http://127.0.0.1:8080/dashboard/cargos](http://127.0.0.1:8080/dashboard/cargos) | js/workFunction/index.js | workFunction.index → js/workFunction/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| workFunctions.create | [http://127.0.0.1:8080/dashboard/crear/cargo](http://127.0.0.1:8080/dashboard/crear/cargo) | js/workFunction/create.js | workFunction.create → js/workFunction/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| workFunctions.edit | http://127.0.0.1:8080/dashboard/editar/cargo/{id} — DATASET REQUIRED | js/workFunction/edit.js | workFunction.edit → js/workFunction/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| workFunctions.deleted | [http://127.0.0.1:8080/dashboard/cargos/eliminados](http://127.0.0.1:8080/dashboard/cargos/eliminados) | js/workFunction/indexDeleted.js | workFunction.indexDeleted → js/workFunction/indexDeleted.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| pensionSystems.index | [http://127.0.0.1:8080/dashboard/sistemas/pension](http://127.0.0.1:8080/dashboard/sistemas/pension) | js/pensionSystem/index.js | pensionSystem.index → js/pensionSystem/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| pensionSystems.create | [http://127.0.0.1:8080/dashboard/crear/sistema/pension](http://127.0.0.1:8080/dashboard/crear/sistema/pension) | js/pensionSystem/create.js | pensionSystem.create → js/pensionSystem/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| pensionSystems.edit | http://127.0.0.1:8080/dashboard/editar/sistema/pension/{id} — DATASET REQUIRED | js/pensionSystem/edit.js | pensionSystem.edit → js/pensionSystem/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| pensionSystems.deleted | [http://127.0.0.1:8080/dashboard/sistemas/pension/eliminados](http://127.0.0.1:8080/dashboard/sistemas/pension/eliminados) | js/pensionSystem/indexDeleted.js | pensionSystem.indexDeleted → js/pensionSystem/indexDeleted.js | route('workFunctions.create | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| holiday.index | [http://127.0.0.1:8080/dashboard/feriados](http://127.0.0.1:8080/dashboard/feriados) | js/holiday/index.js | holiday.index → js/holiday/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| holiday.create | [http://127.0.0.1:8080/dashboard/crear/feriado](http://127.0.0.1:8080/dashboard/crear/feriado) | js/holiday/create.js | holiday.create → js/holiday/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| holiday.edit | http://127.0.0.1:8080/dashboard/editar/feriado/{id} — DATASET REQUIRED | js/holiday/edit.js | holiday.edit → js/holiday/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| reasonSuspension.index | [http://127.0.0.1:8080/dashboard/razones/suspension](http://127.0.0.1:8080/dashboard/razones/suspension) | js/reasonSuspension/index.js | reasonSuspension.index → js/reasonSuspension/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| reasonSuspension.create | [http://127.0.0.1:8080/dashboard/crear/razon/suspension](http://127.0.0.1:8080/dashboard/crear/razon/suspension) | js/reasonSuspension/create.js | reasonSuspension.create → js/reasonSuspension/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| reasonSuspension.edit | http://127.0.0.1:8080/dashboard/editar/razon/suspension/{id} — DATASET REQUIRED | js/reasonSuspension/edit.js | reasonSuspension.edit → js/reasonSuspension/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| areaWorker.index | [http://127.0.0.1:8080/dashboard/areas/empresa](http://127.0.0.1:8080/dashboard/areas/empresa) | js/areaWorker/index.js | areaWorker.index → js/areaWorker/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| areaWorker.create | [http://127.0.0.1:8080/dashboard/crear/area/empresa](http://127.0.0.1:8080/dashboard/crear/area/empresa) | js/areaWorker/create.js | areaWorker.create → js/areaWorker/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| areaWorker.edit | http://127.0.0.1:8080/dashboard/editar/area/empresa/{id} — DATASET REQUIRED | js/areaWorker/create.js | areaWorker.edit → js/areaWorker/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 6d8652a9 | Pendiente de clasificación semántica |
| order.purchase.finance.index | [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas) | js/orderPurchaseFinance/indexOrderFinance.js | orderPurchaseFinance.indexOrderFinance → js/orderPurchaseFinance/list-readonly.js | route('dashboard.principal, route('order.purchase.finance.create, route('order.purchase.finance.index | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 483a7858 | Pendiente de clasificación semántica |
| order.purchase.finance.create | [http://127.0.0.1:8080/dashboard/crear/orden/compra/finanzas](http://127.0.0.1:8080/dashboard/crear/orden/compra/finanzas) | js/orderPurchaseFinance/createOrderFinance.js | orderPurchaseFinance.createOrderFinance → js/orderPurchaseFinance/createOrderFinance.js | data-card-widget, data-toggle | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 744d28b8 | Pendiente de clasificación semántica |
| dashboard/editar/orden/compra/finanza/{id} | http://127.0.0.1:8080/dashboard/editar/orden/compra/finanza/{id} — DATASET REQUIRED | js/orderPurchaseFinance/editOrderFinance.js | orderPurchaseFinance.editOrderPurchaseFinance → js/orderPurchaseFinance/editOrderFinance.js | data-card-widget, data-toggle | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 744d28b8 | Pendiente de clasificación semántica |
| order.purchase.finance.delete | [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/eliminadas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/eliminadas) | js/orderPurchaseFinance/indexDeleted.js | orderPurchaseFinance.indexFinanceDeleted → js/orderPurchaseFinance/history-readonly.js | route('dashboard.principal | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 744d28b8 | Pendiente de clasificación semántica |
| dashboard/ver/orden/compra/finanzas/eliminada/{id} | http://127.0.0.1:8080/dashboard/ver/orden/compra/finanzas/eliminada/{id} — DATASET REQUIRED | Blade previo | orderPurchaseFinance.showOrderPurchaseDeleteFinance → Blade/partials  | data-bootstrap-switch, data-card-widget, data-code, data-id, data-quantity, data-toggle, data-total, data-url, id="btn-currency, name="currency_order, name="date_arrival, name="date_order, name="observation, name="order_id, name="purchase_condition, name="purchase_order, name="quote_id, name="quote_supplier, route('dashboard.principal, route('order.purchase.finance.index | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 744d28b8 | Pendiente de clasificación semántica |
| order.purchase.finance.list.regularize | [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/regularizadas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/regularizadas) | js/orderPurchaseFinance/indexRegularize.js | orderPurchaseFinance.indexRegularizeFinance → js/orderPurchaseFinance/history-readonly.js | route('dashboard.principal | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 744d28b8 | Pendiente de clasificación semántica |
| bill.index | [http://127.0.0.1:8080/dashboard/tipos/gastos](http://127.0.0.1:8080/dashboard/tipos/gastos) | js/bill/index.js | bill.index → js/bill/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 9670a98b | Pendiente de clasificación semántica |
| bill.create | [http://127.0.0.1:8080/dashboard/crear/tipo/gasto](http://127.0.0.1:8080/dashboard/crear/tipo/gasto) | js/bill/create.js | bill.create → js/bill/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 9670a98b | Pendiente de clasificación semántica |
| bill.edit | http://127.0.0.1:8080/dashboard/editar/tipo/gasto/{id} — DATASET REQUIRED | js/bill/edit.js | bill.edit → js/bill/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 9670a98b | Pendiente de clasificación semántica |
| expense.create | [http://127.0.0.1:8080/dashboard/crear/gasto](http://127.0.0.1:8080/dashboard/crear/gasto) | js/expense/create.js | expense.create → js/expense/create.js | route('expense.index | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist d43ca2f7 | Pendiente de clasificación semántica |
| supplier.accounts.index | http://127.0.0.1:8080/dashboard/registrar/cuentas/proveedores/{supplier_id} — DATASET REQUIRED | js/supplierAccount/create.js | supplierAccount.index → js/supplierAccount/create.js | data-card-widget, data-toggle | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 4b546d4c | Pendiente de clasificación semántica |
| categoryEquipment.index | [http://127.0.0.1:8080/dashboard/categorias/equipos](http://127.0.0.1:8080/dashboard/categorias/equipos) | js/categoryEquipment/create.js, js/categoryEquipment/delete.js, js/categoryEquipment/edit.js, js/categoryEquipment/index.js | categoryEquipment.index → js/categoryEquipment/create.js, js/categoryEquipment/delete.js, js/categoryEquipment/edit.js, js/categoryEquipment/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 3636c1f1 | Pendiente de clasificación semántica |
| categoryEquipment.eliminated | [http://127.0.0.1:8080/dashboard/categorias/equiposeliminados](http://127.0.0.1:8080/dashboard/categorias/equiposeliminados) | js/categoryEquipment/indexEliminated.js, js/categoryEquipment/restore.js | categoryEquipment.eliminated → js/categoryEquipment/indexEliminated.js, js/categoryEquipment/restore.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 3636c1f1 | Pendiente de clasificación semántica |
| stocks.files.index | [http://127.0.0.1:8080/dashboard/importar/archivos/stocks/materiales](http://127.0.0.1:8080/dashboard/importar/archivos/stocks/materiales) | js/files/stockFiles.js | files.stockFiles → js/files/stockFiles.js | data-card-widget, data-dismiss, route('stocks.files.index | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| puntoVenta.index | [http://127.0.0.1:8080/dashboard/crear/venta/V1](http://127.0.0.1:8080/dashboard/crear/venta/V1) | js/puntoVenta/catalog-stock.js, js/puntoVenta/checkout-context.js, js/puntoVenta/index.js | puntoVenta.index → js/puntoVenta/catalog-stock.js, js/puntoVenta/checkout-context.js, js/puntoVenta/index.js | data-card-widget | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 64d6f892 | Pendiente de clasificación semántica |
| puntoVenta.indexV2 | [http://127.0.0.1:8080/dashboard/crear/venta/V2](http://127.0.0.1:8080/dashboard/crear/venta/V2) | js/puntoVenta/catalog-stock.js, js/puntoVenta/checkout-context.js, js/puntoVenta/indexV2.js | puntoVenta.indexV2 → js/puntoVenta/catalog-stock.js, js/puntoVenta/checkout-context.js, js/puntoVenta/indexV2.js | data-card-widget | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 64d6f892 | Pendiente de clasificación semántica |
| puntoVenta.list | [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas) | js/puntoVenta/list-access.js, js/puntoVenta/list.js | puntoVenta.list → js/puntoVenta/list-readonly.js | data-active, data-anular, data-buttons, data-code, data-consultar_anulacion, data-consultar_nota_credito, data-created_by_name, data-currency, data-customer, data-date, data-dispatch_status, data-estado_comprobante, data-generar_comprobante, data-generar_nota_credito, data-generar_nota_credito_parcial, data-id, data-is_deferred, data-item, data-itemable-group, data-pagos_parciales, data-placement, data-print_anulacion_cdr, data-print_anulacion_pdf, data-print_anulacion_xml, data-print_recibo, data-recuperar_archivos_nubefact, data-sale_id, data-tipo_pago, data-toggle, data-total, data-type_document, data-ver_detalles, data-ver_nc_parcial_pdf, id="btn-confirm-nc-partial-items, id="btn-download, id="btn-search-nc-partial-item, id="btn-submit-nc-partial, id="btnBuscarGcDni, id="btnBuscarGcRuc, id="btnConfirmarGenerarComprobante, id="btnDescargarPDF, id="btnGenerarComprobante, id="btnGuardarDatos, id="btnGuardarPagoParcial, id="modalFacturador, id="modalFacturadorLabel, id="modalGenerarComprobante, id="modalNcPartialItems, id="modalNotaCreditoParcial, id="modalPagosParciales, name="direccion_fiscal, name="dni, name="email_invoice_boleta, name="email_invoice_factura, name="invoice_type, name="name, name="order_id, name="razon_social, name="ruc, route('puntoVenta.list, route('referral.guide.create | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist ffe0974d | Pendiente de clasificación semántica |
| puntoVenta.listError | [http://127.0.0.1:8080/dashboard/listado/ventas/error](http://127.0.0.1:8080/dashboard/listado/ventas/error) | js/puntoVenta/list-access.js, js/puntoVenta/listError.js | puntoVenta.listError → js/puntoVenta/list-readonly.js | data-active, data-buttons, data-code, data-comprobante, data-created_by_name, data-currency, data-customer, data-date, data-descartar_error_comprobante, data-id, data-is_deferred, data-item, data-print_recibo, data-reintentar_comprobante, data-sale_id, data-sunat_error, data-tipo_pago, data-toggle, data-total, data-ver_detalles, id="btn-submit-nc-partial, id="btnConfirmarGenerarComprobante, id="btnDescargarPDF, id="btnGenerarComprobante, id="btnGuardarDatos, id="btnGuardarPagoParcial, id="modalFacturador, id="modalFacturadorLabel, id="modalGenerarComprobante, id="modalNotaCreditoParcial, id="modalPagosParciales, name="direccion_fiscal, name="dni, name="email_invoice_boleta, name="email_invoice_factura, name="invoice_type, name="name, name="order_id, name="razon_social, name="ruc, route('puntoVenta.list | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist b52c3cd8 | Pendiente de clasificación semántica |
| ganancia.index | [http://127.0.0.1:8080/dashboard/listado/ganancias/diarias](http://127.0.0.1:8080/dashboard/listado/ganancias/diarias) | js/ganancias/index.js | ganancia.index → js/ganancias/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist bd413c65 | Pendiente de clasificación semántica |
| ganancia.detail.index | http://127.0.0.1:8080/dashboard/listado/ganancia/detalles/{id} — DATASET REQUIRED | Baseline no localizado en parent | ganancia.indexDetail → js/ganancias/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist bd413c65 | Pendiente de clasificación semántica |
| ganancia.detail.usuario | http://127.0.0.1:8080/dashboard/listado/ganancia/detalles/trabajador/{sale_id} — DATASET REQUIRED | Blade previo | ganancia.indexDetailTrabajador → Blade/partials  | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8349ba11 | Pendiente de clasificación semántica |
| ganancia.index.trabajador | [http://127.0.0.1:8080/dashboard/listado/ganancias/diarias/trabajador](http://127.0.0.1:8080/dashboard/listado/ganancias/diarias/trabajador) | js/ganancias/indexTrabajador.js | ganancia.indexTrabajador → js/ganancias/indexTrabajador.js | data-placement | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8349ba11 | Pendiente de clasificación semántica |
| metas.index | [http://127.0.0.1:8080/dashboard/metas](http://127.0.0.1:8080/dashboard/metas) | Blade previo | metas.index → Blade/partials  | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 4a8e796a | Pendiente de clasificación semántica |
| metas.create | [http://127.0.0.1:8080/dashboard/metas/create](http://127.0.0.1:8080/dashboard/metas/create) | Blade previo | metas.create → Blade/partials  | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 4a8e796a | Pendiente de clasificación semántica |
| metas.edit | http://127.0.0.1:8080/dashboard/metas/{meta}/edit — DATASET REQUIRED | Blade previo | metas.edit → Blade/partials  | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 4a8e796a | Pendiente de clasificación semántica |
| metas.ranking | [http://127.0.0.1:8080/dashboard/metas/ranking](http://127.0.0.1:8080/dashboard/metas/ranking) | Blade previo; Blade previo | metas.ranking → Blade/partials ; metas.ranking → Blade/partials  | ;  | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 4a8e796a | Pendiente de clasificación semántica |
| quoteSale.index | [http://127.0.0.1:8080/dashboard/cotizaciones/venta](http://127.0.0.1:8080/dashboard/cotizaciones/venta) | js/quoteSale/general.js | quoteSale.general → js/quoteSale/general.js | data-active, data-branch, data-buttons, data-code, data-company, data-created_at, data-creator, data-currency, data-customer, data-date_quote, data-date_validate, data-deadline, data-decimals, data-description, data-dismiss, data-id, data-item, data-order, data-services_add, data-state, data-tenant, data-time_delivery, data-total, data-total_sunat, data-url, id="btn-changeDecimals, id="btn-download, id="btn-export, id="btn-search, id="btnBusquedaAvanzada, id="modalDecimals, id="modalDelete, name="creator, name="customer, name="decimals, name="end, name="quote_id, name="start, name="stateQuote, name="subcategory_id, route('quoteSale.decimals.change, route('quoteSale.index, route('quoteSale.pendingBranches, route('subcategory.destroy | PARIDAD ESTÁTICA DE ACCIONES; no certifica mutaciones; inclusión allowlist 2d9e3650 | Pendiente de clasificación semántica |
| quoteSale.index.facturadas | [http://127.0.0.1:8080/dashboard/cotizaciones/venta/facturadas](http://127.0.0.1:8080/dashboard/cotizaciones/venta/facturadas) | js/quoteSale/facturadas.js | quoteSale.facturadas → js/quoteSale/facturadas.js | data-active, data-branch, data-buttons, data-code, data-company, data-created_at, data-creator, data-currency, data-customer, data-date_quote, data-date_validate, data-deadline, data-decimals, data-description, data-dismiss, data-id, data-item, data-order, data-services_add, data-state, data-tenant, data-time_delivery, data-total, data-total_sunat, data-url, id="btn-changeDecimals, id="btn-download, id="btn-export, id="btn-search, id="btnBusquedaAvanzada, id="modalDecimals, id="modalDelete, name="creator, name="customer, name="decimals, name="end, name="quote_id, name="start, name="stateQuote, name="subcategory_id, route('quoteSale.decimals.change, route('quoteSale.pendingBranches, route('subcategory.destroy | PARIDAD ESTÁTICA DE ACCIONES; no certifica mutaciones; inclusión allowlist 2d9e3650 | Pendiente de clasificación semántica |
| quoteSale.show | http://127.0.0.1:8080/dashboard/ver/cotizacion/venta/{quote} — DATASET REQUIRED | js/quoteSale/edit.js | quoteSale.show → Blade/partials  | data-bodyConsumable, data-bodyService, data-bootstrap-switch, data-card-widget, data-consumableDescription, data-consumableId, data-consumableImporte, data-consumablePrice, data-consumablePrice2, data-consumableQuantity, data-consumableTotal, data-consumableUnit, data-consumableValor, data-deleteConsumable, data-descuento, data-discount_input_mode, data-discount_type, data-discount_value, data-dismiss, data-equip, data-letterequipment, data-off-color, data-off-text, data-on-color, data-on-text, data-placement, data-rentequipment, data-serviceBillable, data-serviceDescription, data-serviceId, data-serviceImporte, data-servicePU, data-serviceQuantity, data-serviceRow, data-serviceUnit, data-serviceVU, data-toggle, data-type_promotion, data-url, data-utilityequipment, id="btn-changePercentage, id="btn-grouped, id="btn-submit, id="btn-submit-customer, id="modalChangePercentages, id="modalCustomer, id="modalCustomerLabel, name="address, name="business_name, name="code_description, name="code_quote, name="date_quote, name="date_validate, name="delivery_time, name="discount_input_mode, name="discount_type, name="equipment_percentage, name="location, name="percentage_letter, name="percentage_rent, name="percentage_utility, name="quote_id, name="quote_percentage, name="ruc, name="special, name="way_to_pay, route('customer.store, route('dashboard.principal, route('quoteSale.update | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 2d9e3650 | Pendiente de clasificación semántica |
| quoteSale.pendingBranches | [http://127.0.0.1:8080/dashboard/cotizaciones/venta/pendientes-sucursal](http://127.0.0.1:8080/dashboard/cotizaciones/venta/pendientes-sucursal) | Blade previo | quoteSale.pendingBranches → Blade/partials  | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 2d9e3650 | Pendiente de clasificación semántica |
| quoteSale.pendingBranches.show | http://127.0.0.1:8080/dashboard/cotizaciones/venta/pendientes-sucursal/{id} — DATASET REQUIRED | Blade previo | quoteSale.pendingBranchDetail → Blade/partials  | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 2d9e3650 | Pendiente de clasificación semántica |
| kardex.index | [http://127.0.0.1:8080/dashboard/kardex](http://127.0.0.1:8080/dashboard/kardex) | js/inventory/kardex.js | inventory.kardex → js/inventory/kardex.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 4b546d4c | Pendiente de clasificación semántica |
| settings.material-details.index | [http://127.0.0.1:8080/dashboard/settings/material-details](http://127.0.0.1:8080/dashboard/settings/material-details) | Blade previo | materialDetailSetting.index → Blade/partials materialDetailSetting._context | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| cashBox.index | [http://127.0.0.1:8080/dashboard/cash-boxes](http://127.0.0.1:8080/dashboard/cash-boxes) | js/cashBox/index.js | cashBox.index → js/cashBox/index.js | data-placement | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 9ad0106d | Pendiente de clasificación semántica |
| cashBoxSubtype.index | [http://127.0.0.1:8080/dashboard/cash-box-subtypes](http://127.0.0.1:8080/dashboard/cash-box-subtypes) | js/cashBoxSubtype/index.js | cashBoxSubtype.index → js/cashBoxSubtype/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 9ad0106d | Pendiente de clasificación semántica |
| cashMovement.my.index | [http://127.0.0.1:8080/dashboard/cash-movements/my](http://127.0.0.1:8080/dashboard/cash-movements/my) | js/cashBox/my_index.js, js/cashRegister/regularization.js | cashBox.my_index → js/cashBox/my_index.js, js/cashRegister/regularization.js | Sin tokens retirados detectados; NO equivale a PASS | PARIDAD ESTÁTICA DE ACCIONES; no certifica mutaciones; inclusión allowlist 80b240ae | Pendiente de clasificación semántica |
| cashMovement.admin.index | [http://127.0.0.1:8080/dashboard/cash-movements/admin](http://127.0.0.1:8080/dashboard/cash-movements/admin) | Blade previo | cashBox.admin_index → Blade/partials cashBox.summary_movements,cashBox.admin_readonly | Sin tokens retirados detectados; NO equivale a PASS | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 744d28b8 | Pendiente de clasificación semántica |
| cashRegister.session | http://127.0.0.1:8080/dashboard/cash-registers/session/{cashBox} — DATASET REQUIRED | js/cashRegister/regularization.js, js/cashRegister/session.js | cashRegister.session → js/cashRegister/regularization.js, js/cashRegister/session.js | Sin tokens retirados detectados; NO equivale a PASS | PARIDAD ESTÁTICA DE ACCIONES; no certifica mutaciones; inclusión allowlist 80b240ae | Pendiente de clasificación semántica |
| financial.summary | [http://127.0.0.1:8080/dashboard/financial/summary](http://127.0.0.1:8080/dashboard/financial/summary) | Blade previo | cashRegister.summary → Blade/partials  | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 483a7858 | Pendiente de clasificación semántica |
| financial.summary.movements | http://127.0.0.1:8080/dashboard/financial/summary/cash-boxes/{cashBox}/movements — DATASET REQUIRED | js/cashBox/admin_index.js, js/cashRegister/regularization.js | cashBox.admin_index → Blade/partials cashBox.summary_movements,cashBox.admin_readonly | data-amount, data-amount_regularize, data-buttons, data-cash_box_id, data-cash_box_name, data-cash_box_type, data-cashbox, data-cashbox-btn, data-commission, data-date, data-description, data-id, data-is_open, data-placement, data-print_nota, data-regularizar, data-status, data-subtype, data-toggle, data-type, data-user, id="btn-search, route('cashMovement.admin.list, route('cashRegister.open, route('dashboard.principal | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 483a7858 | Pendiente de clasificación semántica |
| shipping_guides.view | [http://127.0.0.1:8080/dashboard/shipping-guides/listado](http://127.0.0.1:8080/dashboard/shipping-guides/listado) | js/shipping_guides/index.js | shipping_guides.index → js/shipping_guides/index.js | Sin tokens retirados detectados; NO equivale a PASS | PARIDAD ESTÁTICA DE ACCIONES; no certifica mutaciones; inclusión allowlist eb5ebbc3 | Pendiente de clasificación semántica |
| shipping_guides.create | [http://127.0.0.1:8080/dashboard/shipping-guides/create](http://127.0.0.1:8080/dashboard/shipping-guides/create) | js/shipping_guides/create.js | shipping_guides.create → js/shipping_guides/create.js | data-parent | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 9c26f05a | Pendiente de clasificación semántica |
| shipping_guides.show | http://127.0.0.1:8080/dashboard/shipping-guides/detalles/{guide} — DATASET REQUIRED | Blade previo | shipping_guides.show → Blade/partials shipping_guides._recovery | Sin tokens retirados detectados; NO equivale a PASS | PARIDAD ESTÁTICA DE ACCIONES; no certifica mutaciones; inclusión allowlist eb5ebbc3 | Pendiente de clasificación semántica |
| stockitems.index | [http://127.0.0.1:8080/dashboard/stock-items](http://127.0.0.1:8080/dashboard/stock-items) | js/material/inventory-context.js, js/stockItem/list.js | stockItem.list → js/material/inventory-context.js, js/stockItem/list.js | id="btn-resumen-stock, id="btn-save-inventory-levels, id="modalAdjustStock, id="modal_stock_item_id, route('dashboard.principal, route('material.create, route('stock | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 6de44064 | Pendiente de clasificación semántica |
| stockitems.view.variants | http://127.0.0.1:8080/dashboard/view/material/variants/{id} — DATASET REQUIRED | js/material/variants.js | material.variants → js/material/variants.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 744d28b8 | Pendiente de clasificación semántica |
| color.index | [http://127.0.0.1:8080/dashboard/colors](http://127.0.0.1:8080/dashboard/colors) | js/color/index.js | color.index → js/color/index.js | route('color.index | PÉRDIDA CONFIRMADA; ver matriz de capacidades; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| color.create | [http://127.0.0.1:8080/dashboard/crear/color](http://127.0.0.1:8080/dashboard/crear/color) | js/color/form.js | color.create → js/color/form.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| color.edit | http://127.0.0.1:8080/dashboard/editar/color/{id} — DATASET REQUIRED | js/color/form.js | color.edit → js/color/form.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 8faf1ef3 | Pendiente de clasificación semántica |
| configUserWeb.index | [http://127.0.0.1:8080/dashboard/configuracion/usuarios/web](http://127.0.0.1:8080/dashboard/configuracion/usuarios/web) | js/configUserWeb/index.js | configUserWeb.index → js/configUserWeb/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist b22eb015 | Pendiente de clasificación semántica |
| configUserWeb.create | [http://127.0.0.1:8080/dashboard/configuracion/usuarios/web/nuevo](http://127.0.0.1:8080/dashboard/configuracion/usuarios/web/nuevo) | js/configUserWeb/create.js | configUserWeb.create → js/configUserWeb/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist b22eb015 | Pendiente de clasificación semántica |
| configUserWeb.edit | http://127.0.0.1:8080/dashboard/configuracion/usuarios/web/{id}/editar — DATASET REQUIRED | js/configUserWeb/edit.js | configUserWeb.edit → js/configUserWeb/edit.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist b22eb015 | Pendiente de clasificación semántica |
| companyStockItem.index | [http://127.0.0.1:8080/dashboard/dashboard/company-stock-items](http://127.0.0.1:8080/dashboard/dashboard/company-stock-items) | js/companyStockItem/index.js | companyStockItem.index → js/companyStockItem/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 4b0ac964 | Pendiente de clasificación semántica |
| platform.dashboard | [http://127.0.0.1:8080/dashboard/plataforma](http://127.0.0.1:8080/dashboard/plataforma) | Blade previo | platform.dashboard.index → Blade/partials  | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| platformActivity.index | [http://127.0.0.1:8080/dashboard/plataforma/activity-log](http://127.0.0.1:8080/dashboard/plataforma/activity-log) | js/platform/activityLog/index.js | platform.activityLog.index → js/platform/activityLog/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| platformTenant.index | [http://127.0.0.1:8080/dashboard/plataforma/tenants](http://127.0.0.1:8080/dashboard/plataforma/tenants) | js/platform/tenants/index.js | platform.tenants.index → js/platform/tenants/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| platformTenant.create | [http://127.0.0.1:8080/dashboard/plataforma/tenants/create](http://127.0.0.1:8080/dashboard/plataforma/tenants/create) | js/platform/tenants/create.js | platform.tenants.create → js/platform/tenants/create.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| platformTenant.show | http://127.0.0.1:8080/dashboard/plataforma/tenants/{id} — DATASET REQUIRED | js/platform/tenants/show.js | platform.tenants.show → js/platform/tenants/show.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| plan.index | [http://127.0.0.1:8080/dashboard/plataforma/plans](http://127.0.0.1:8080/dashboard/plataforma/plans) | js/plan/index.js | plan.index → js/plan/index.js | id="modalCreatePlan, id="modalEditPlan | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| roleTemplate.index | [http://127.0.0.1:8080/dashboard/plataforma/role-templates](http://127.0.0.1:8080/dashboard/plataforma/role-templates) | js/roleTemplate/index.js | roleTemplate.index → js/roleTemplate/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| roleTemplate.create | [http://127.0.0.1:8080/dashboard/plataforma/role-templates/create](http://127.0.0.1:8080/dashboard/plataforma/role-templates/create) | js/roleTemplate/form.js | roleTemplate.create → js/roleTemplate/form.js | route('dashboard.principal | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| roleTemplate.edit | http://127.0.0.1:8080/dashboard/plataforma/role-templates/{id}/edit — DATASET REQUIRED | js/roleTemplate/form.js | roleTemplate.edit → js/roleTemplate/form.js | route('dashboard.principal | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| tenantRole.index | [http://127.0.0.1:8080/dashboard/plataforma/tenant-roles](http://127.0.0.1:8080/dashboard/plataforma/tenant-roles) | js/tenantRole/index.js | tenantRole.index → js/tenantRole/index.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| tenantRole.create | http://127.0.0.1:8080/dashboard/plataforma/tenants/{tenantId}/roles/create — DATASET REQUIRED | js/tenantRole/form.js | tenantRole.create → js/tenantRole/form.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| tenantRole.edit | http://127.0.0.1:8080/dashboard/plataforma/tenants/{tenantId}/roles/{roleId}/edit — DATASET REQUIRED | js/tenantRole/form.js | tenantRole.edit → js/tenantRole/form.js | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| platformPercentageWorker.index | [http://127.0.0.1:8080/dashboard/plataforma/parametros-laborales](http://127.0.0.1:8080/dashboard/plataforma/parametros-laborales) | Blade previo | percentageWorker.index → Blade/partials  | route('dashboard.principal | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| platformPercentageWorker.edit | http://127.0.0.1:8080/dashboard/plataforma/parametros-laborales/{id}/editar — DATASET REQUIRED | js/percentageWorker/edit.js | percentageWorker.edit → js/percentageWorker/edit.js | route('dashboard.principal | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 673cb064 | Pendiente de clasificación semántica |
| platformTypeTax.index | [http://127.0.0.1:8080/dashboard/plataforma/tipos-impuesto](http://127.0.0.1:8080/dashboard/plataforma/tipos-impuesto) | js/typeTax/index.js | typeTax.index → js/typeTax/index.js | route('dashboard.principal | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist 4c2b1989 | Pendiente de clasificación semántica |
| platformNubefact.index | [http://127.0.0.1:8080/dashboard/plataforma/nubefact](http://127.0.0.1:8080/dashboard/plataforma/nubefact) | Blade previo | platform.nubefact.index → Blade/partials  | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist d2592207 | Pendiente de clasificación semántica |
| platformNubefact.edit | http://127.0.0.1:8080/dashboard/plataforma/tenants/{tenantId}/companies/{companyId}/nubefact — DATASET REQUIRED | Blade previo | platform.nubefact.edit → Blade/partials  | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist d2592207 | Pendiente de clasificación semántica |
| platformShippingCatalogs.index | http://127.0.0.1:8080/dashboard/plataforma/catalogos-guias/{catalog?} — DATASET REQUIRED | Blade previo | platform.shippingCatalogs.index → Blade/partials  | Sin tokens retirados detectados; NO equivale a PASS | UNKNOWN — EVIDENCE REQUIRED; inclusión allowlist b951afdc | Pendiente de clasificación semántica |

## Inventario reproducible

JSON de evidencia por ruta, commit de introducción de la clave de allowlist (historial de diffs) y anclas extraídas. Las anclas retiradas pueden ser renombres o cambios de contenedor: no son automáticamente funciones perdidas.

```json
[
  {
    "name": "password.required.edit",
    "uri": "password/cambio-obligatorio",
    "action": "App\\Http\\Controllers\\RequiredPasswordChangeController@edit",
    "views": [
      "auth.required-password-change"
    ],
    "intro": "80cb50d8",
    "comparisons": [
      {
        "view": "auth.required-password-change",
        "file": "resources/views/auth/required-password-change.blade.php",
        "baseline_exists": true,
        "removed": [
          "name=\"password",
          "name=\"password_confirmation"
        ],
        "added": [
          "route('password.required.update"
        ],
        "old_js": [
          "js/auth/required-password-change.js"
        ],
        "new_js": [
          "js/auth/required-password-change.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [
        "password",
        "password_confirmation"
      ],
      "routes": [],
      "old_js": [
        "js/auth/required-password-change.js"
      ],
      "new_js": [
        "js/auth/required-password-change.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "tenantCompany.index",
    "uri": "dashboard/configuracion/empresas",
    "action": "App\\Http\\Controllers\\TenantCompanyController@index",
    "views": [
      "tenantCompany.index"
    ],
    "intro": "a9679aa4",
    "comparisons": [
      {
        "view": "tenantCompany.index",
        "file": "resources/views/tenantCompany/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('tenantBranch.create"
        ],
        "added": [
          "data-table",
          "data-toggle"
        ],
        "old_js": [
          "js/tenantCompany/index.js"
        ],
        "new_js": [
          "js/tenantCompany/index.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "tenantBranch.create"
      ],
      "old_js": [
        "js/tenantCompany/index.js"
      ],
      "new_js": [
        "js/tenantCompany/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "tenantCompany.create",
    "uri": "dashboard/configuracion/empresas/crear",
    "action": "App\\Http\\Controllers\\TenantCompanyController@create",
    "views": [
      "tenantCompany.create"
    ],
    "intro": "a9679aa4",
    "comparisons": [
      {
        "view": "tenantCompany.create",
        "file": "resources/views/tenantCompany/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/tenantCompany/create.js"
        ],
        "new_js": [
          "js/tenantCompany/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/tenantCompany/create.js"
      ],
      "new_js": [
        "js/tenantCompany/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "tenantCompany.edit",
    "uri": "dashboard/configuracion/empresas/{id}/editar",
    "action": "App\\Http\\Controllers\\TenantCompanyController@edit",
    "views": [
      "tenantCompany.edit"
    ],
    "intro": "a9679aa4",
    "comparisons": [
      {
        "view": "tenantCompany.edit",
        "file": "resources/views/tenantCompany/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/inventory/managedWarehouses.js",
          "js/tenantCompany/edit.js"
        ],
        "new_js": [
          "js/inventory/managedWarehouses.js",
          "js/tenantCompany/edit.js"
        ],
        "same_js": false,
        "includes": [
          "inventory.managedWarehouses"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/inventory/managedWarehouses.js",
        "js/tenantCompany/edit.js"
      ],
      "new_js": [
        "js/inventory/managedWarehouses.js",
        "js/tenantCompany/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "tenantBranch.index",
    "uri": "dashboard/configuracion/empresas/{companyId}/sucursales",
    "action": "App\\Http\\Controllers\\TenantBranchController@index",
    "views": [
      "tenantBranch.index"
    ],
    "intro": "399f7d9c",
    "comparisons": [
      {
        "view": "tenantBranch.index",
        "file": "resources/views/tenantBranch/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/tenantBranch/index.js"
        ],
        "new_js": [
          "js/tenantBranch/index.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/tenantBranch/index.js"
      ],
      "new_js": [
        "js/tenantBranch/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "tenantBranch.create",
    "uri": "dashboard/configuracion/empresas/{companyId}/sucursales/crear",
    "action": "App\\Http\\Controllers\\TenantBranchController@create",
    "views": [
      "tenantBranch.create"
    ],
    "intro": "399f7d9c",
    "comparisons": [
      {
        "view": "tenantBranch.create",
        "file": "resources/views/tenantBranch/create.blade.php",
        "baseline_exists": true,
        "removed": [
          "name=\"price_list_id"
        ],
        "added": [
          "name=\"catalog_slug"
        ],
        "old_js": [
          "js/tenantBranch/create.js"
        ],
        "new_js": [
          "js/tenantBranch/create.js"
        ],
        "same_js": true,
        "includes": [
          "tenantBranch._pricing"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/tenantBranch/create.js"
      ],
      "new_js": [
        "js/tenantBranch/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "tenantBranch.edit",
    "uri": "dashboard/configuracion/sucursales/{id}/editar",
    "action": "App\\Http\\Controllers\\TenantBranchController@edit",
    "views": [
      "tenantBranch.edit"
    ],
    "intro": "399f7d9c",
    "comparisons": [
      {
        "view": "tenantBranch.edit",
        "file": "resources/views/tenantBranch/edit.blade.php",
        "baseline_exists": true,
        "removed": [
          "name=\"price_list_id",
          "route('tenantCompany.index"
        ],
        "added": [
          "name=\"catalog_slug"
        ],
        "old_js": [
          "js/inventory/managedWarehouses.js",
          "js/tenantBranch/edit.js"
        ],
        "new_js": [
          "js/inventory/managedWarehouses.js",
          "js/tenantBranch/edit.js"
        ],
        "same_js": false,
        "includes": [
          "tenantBranch._pricing",
          "inventory.managedWarehouses"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "tenantCompany.index"
      ],
      "old_js": [
        "js/inventory/managedWarehouses.js",
        "js/tenantBranch/edit.js"
      ],
      "new_js": [
        "js/inventory/managedWarehouses.js",
        "js/tenantBranch/edit.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "companyBankAccount.index",
    "uri": "dashboard/configuracion/cuentas-bancarias",
    "action": "App\\Http\\Controllers\\CompanyBankAccountController@index",
    "views": [
      "companyBankAccount.index"
    ],
    "intro": "1f972a7c",
    "comparisons": [
      {
        "view": "companyBankAccount.index",
        "file": "resources/views/companyBankAccount/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table",
          "data-toggle"
        ],
        "old_js": [
          "js/companyBankAccount/index.js"
        ],
        "new_js": [
          "js/companyBankAccount/index.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/companyBankAccount/index.js"
      ],
      "new_js": [
        "js/companyBankAccount/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "companyBankAccount.create",
    "uri": "dashboard/configuracion/cuentas-bancarias/crear",
    "action": "App\\Http\\Controllers\\CompanyBankAccountController@create",
    "views": [
      "companyBankAccount.create"
    ],
    "intro": "1f972a7c",
    "comparisons": [
      {
        "view": "companyBankAccount.create",
        "file": "resources/views/companyBankAccount/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/companyBankAccount/form.js"
        ],
        "new_js": [
          "js/companyBankAccount/form.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/companyBankAccount/form.js"
      ],
      "new_js": [
        "js/companyBankAccount/form.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "companyBankAccount.edit",
    "uri": "dashboard/configuracion/cuentas-bancarias/{id}/editar",
    "action": "App\\Http\\Controllers\\CompanyBankAccountController@edit",
    "views": [
      "companyBankAccount.edit"
    ],
    "intro": "1f972a7c",
    "comparisons": [
      {
        "view": "companyBankAccount.edit",
        "file": "resources/views/companyBankAccount/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/companyBankAccount/form.js"
        ],
        "new_js": [
          "js/companyBankAccount/form.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/companyBankAccount/form.js"
      ],
      "new_js": [
        "js/companyBankAccount/form.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "companySetting.index",
    "uri": "dashboard/configuracion/configuraciones/empresa",
    "action": "App\\Http\\Controllers\\CompanySettingController@index",
    "views": [
      "companySetting.index"
    ],
    "intro": "58a7841a",
    "comparisons": [
      {
        "view": "companySetting.index",
        "file": "resources/views/companySetting/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/companySetting/index.js"
        ],
        "new_js": [
          "js/companySetting/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/companySetting/index.js"
      ],
      "new_js": [
        "js/companySetting/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "workforce.index",
    "uri": "dashboard/configuracion/workforces-default",
    "action": "App\\Http\\Controllers\\WorkforceController@index",
    "views": [
      "workforce.index"
    ],
    "intro": "3636c1f1",
    "comparisons": [
      {
        "view": "workforce.index",
        "file": "resources/views/workforce/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/workforce/index.js"
        ],
        "new_js": [
          "js/workforce/index.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/workforce/index.js"
      ],
      "new_js": [
        "js/workforce/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "dashboard.principal",
    "uri": "dashboard/principal",
    "action": "App\\Http\\Controllers\\HomeController@dashboard",
    "views": [
      "dashboard.dashboard"
    ],
    "intro": "dbca24e1",
    "comparisons": [
      {
        "view": "dashboard.dashboard",
        "file": "resources/views/dashboard/dashboard.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-active",
          "data-card-widget",
          "data-dismiss",
          "data-fecha",
          "data-fgColor",
          "data-filter",
          "data-height",
          "data-id",
          "data-item",
          "data-placement",
          "data-readonly",
          "data-target",
          "data-toggle",
          "data-url",
          "data-user",
          "data-width",
          "id=\"btn-download",
          "id=\"btn-downloadEntries",
          "id=\"btn-export-cashflow-range",
          "id=\"btn-export-range-sales",
          "id=\"btn-newRotation",
          "id=\"btn-pays",
          "id=\"btn-refresh",
          "id=\"btn-submitDownload",
          "id=\"btn-submitExport",
          "id=\"btnViewReportDollarsQuote",
          "id=\"btnViewReportIncomeExpenseDollars",
          "id=\"btnViewReportIncomeExpenseMix",
          "id=\"btnViewReportIncomeExpenseSoles",
          "id=\"btnViewReportSolesQuote",
          "id=\"btnViewReportUtilitiesDollars",
          "id=\"btnViewReportUtilitiesMix",
          "id=\"btnViewReportUtilitiesSoles",
          "id=\"modalEntries",
          "id=\"modalLocations",
          "id=\"modalViewReportDollars",
          "id=\"modalViewReportIncomeExpenseDollars",
          "id=\"modalViewReportIncomeExpenseMix",
          "id=\"modalViewReportIncomeExpenseSoles",
          "id=\"modalViewReportSoles",
          "id=\"modalViewReportUtilitiesDollars",
          "id=\"modalViewReportUtilitiesMix",
          "id=\"modalViewReportUtilitiesSoles",
          "name=\"end",
          "name=\"location",
          "name=\"month",
          "name=\"start",
          "name=\"typeEntry",
          "name=\"year",
          "route('dashboard.principal",
          "route('report.excel.amount",
          "route('report.excel.materials"
        ],
        "added": [],
        "old_js": [
          "js/dashboard/ordersChart.js",
          "js/report/charts.js",
          "js/report/reportAmount.js",
          "js/report/viewReport.js"
        ],
        "new_js": [
          "js/dashboard/ordersChart.js"
        ],
        "same_js": false,
        "includes": [
          "dashboard.partials.kpi",
          "dashboard.partials.kpi",
          "dashboard.partials.kpi",
          "dashboard.partials.kpi",
          "dashboard.partials.kpi",
          "dashboard.partials.kpi",
          "dashboard.partials.kpi",
          "dashboard.partials.chart",
          "dashboard.partials.chart",
          "dashboard.partials.range-modal",
          "dashboard.partials.range-modal"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [
        "active",
        "fecha",
        "fgColor",
        "height",
        "id",
        "item",
        "readonly",
        "user",
        "width"
      ],
      "names": [
        "csrf-token",
        "end",
        "location",
        "month",
        "start",
        "typeEntry",
        "year"
      ],
      "routes": [
        "dashboard.principal",
        "report.excel.amount",
        "report.excel.materials"
      ],
      "old_js": [
        "js/dashboard/ordersChart.js",
        "js/report/charts.js",
        "js/report/reportAmount.js",
        "js/report/viewReport.js"
      ],
      "new_js": [
        "js/dashboard/ordersChart.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/get/data/rotations/v2/",
      "/dashboard/personal/payments",
      "/dashboard/report/amount/items",
      "/dashboard/report/chart/expense/income",
      "/dashboard/report/chart/income/expense/view/",
      "/dashboard/report/chart/quote/raised",
      "/dashboard/report/chart/quote/view/",
      "/dashboard/report/chart/utilities",
      "/dashboard/report/chart/utilities/view/",
      "/dashboard/store/rotation/material/"
    ]
  },
  {
    "name": "priceList.index",
    "uri": "dashboard/prices",
    "action": "App\\Http\\Controllers\\PriceListController@index",
    "views": [
      "priceList.index"
    ],
    "intro": "b898890a",
    "comparisons": [
      {
        "view": "priceList.index",
        "file": "resources/views/priceList/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-can-manage"
        ],
        "old_js": [
          "js/priceList/index.js"
        ],
        "new_js": [
          "js/priceList/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/priceList/index.js"
      ],
      "new_js": [
        "js/priceList/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "customer.index",
    "uri": "dashboard/clientes",
    "action": "App\\Http\\Controllers\\CustomerController@index",
    "views": [
      "customer.index"
    ],
    "intro": "5242c73f",
    "comparisons": [
      {
        "view": "customer.index",
        "file": "resources/views/customer/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table",
          "route('customer.indexrestore"
        ],
        "old_js": [
          "js/customer/index.js"
        ],
        "new_js": [
          "js/customer/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/customer/index.js"
      ],
      "new_js": [
        "js/customer/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "customer.create",
    "uri": "dashboard/crear/cliente",
    "action": "App\\Http\\Controllers\\CustomerController@create",
    "views": [
      "customer.create"
    ],
    "intro": "5242c73f",
    "comparisons": [
      {
        "view": "customer.create",
        "file": "resources/views/customer/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/customer/create.js"
        ],
        "new_js": [
          "js/customer/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/customer/create.js"
      ],
      "new_js": [
        "js/customer/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "customer.edit",
    "uri": "dashboard/editar/cliente/{id}",
    "action": "App\\Http\\Controllers\\CustomerController@edit",
    "views": [
      "customer.edit"
    ],
    "intro": "5242c73f",
    "comparisons": [
      {
        "view": "customer.edit",
        "file": "resources/views/customer/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/customer/edit.js"
        ],
        "new_js": [
          "js/customer/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/customer/edit.js"
      ],
      "new_js": [
        "js/customer/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "customer.indexrestore",
    "uri": "dashboard/clientes/restore",
    "action": "App\\Http\\Controllers\\CustomerController@indexrestore",
    "views": [
      "customer.restore"
    ],
    "intro": "5242c73f",
    "comparisons": [
      {
        "view": "customer.restore",
        "file": "resources/views/customer/restore.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table",
          "route('customer.index"
        ],
        "old_js": [
          "js/customer/restore.js"
        ],
        "new_js": [
          "js/customer/restore.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/customer/restore.js"
      ],
      "new_js": [
        "js/customer/restore.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "supplier.index",
    "uri": "dashboard/proveedores",
    "action": "App\\Http\\Controllers\\SupplierController@index",
    "views": [
      "supplier.index"
    ],
    "intro": "cffac49e",
    "comparisons": [
      {
        "view": "supplier.index",
        "file": "resources/views/supplier/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table",
          "route('supplier.indexrestore"
        ],
        "old_js": [
          "js/supplier/index.js"
        ],
        "new_js": [
          "js/supplier/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/supplier/index.js"
      ],
      "new_js": [
        "js/supplier/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "supplier.create",
    "uri": "dashboard/crear/proveedor",
    "action": "App\\Http\\Controllers\\SupplierController@create",
    "views": [
      "supplier.create"
    ],
    "intro": "cffac49e",
    "comparisons": [
      {
        "view": "supplier.create",
        "file": "resources/views/supplier/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/supplier/create.js"
        ],
        "new_js": [
          "js/supplier/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/supplier/create.js"
      ],
      "new_js": [
        "js/supplier/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "supplier.edit",
    "uri": "dashboard/editar/proveedor/{id}",
    "action": "App\\Http\\Controllers\\SupplierController@edit",
    "views": [
      "supplier.edit"
    ],
    "intro": "cffac49e",
    "comparisons": [
      {
        "view": "supplier.edit",
        "file": "resources/views/supplier/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/supplier/edit.js"
        ],
        "new_js": [
          "js/supplier/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/supplier/edit.js"
      ],
      "new_js": [
        "js/supplier/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "supplier.indexrestore",
    "uri": "dashboard/proveedores/restore",
    "action": "App\\Http\\Controllers\\SupplierController@indexrestore",
    "views": [
      "supplier.restore"
    ],
    "intro": "cffac49e",
    "comparisons": [
      {
        "view": "supplier.restore",
        "file": "resources/views/supplier/restore.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table",
          "route('supplier.index"
        ],
        "old_js": [
          "js/supplier/restore.js"
        ],
        "new_js": [
          "js/supplier/restore.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/supplier/restore.js"
      ],
      "new_js": [
        "js/supplier/restore.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "contactName.index",
    "uri": "dashboard/contactos",
    "action": "App\\Http\\Controllers\\ContactNameController@index",
    "views": [
      "contactName.index"
    ],
    "intro": "3883302a",
    "comparisons": [
      {
        "view": "contactName.index",
        "file": "resources/views/contactName/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table",
          "route('contactName.indexrestore"
        ],
        "old_js": [
          "js/contactName/index.js"
        ],
        "new_js": [
          "js/contactName/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/contactName/index.js"
      ],
      "new_js": [
        "js/contactName/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "contactName.create",
    "uri": "dashboard/crear/contacto",
    "action": "App\\Http\\Controllers\\ContactNameController@create",
    "views": [
      "contactName.create"
    ],
    "intro": "3883302a",
    "comparisons": [
      {
        "view": "contactName.create",
        "file": "resources/views/contactName/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/contactName/create.js"
        ],
        "new_js": [
          "js/contactName/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/contactName/create.js"
      ],
      "new_js": [
        "js/contactName/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "contactName.edit",
    "uri": "dashboard/editar/contacto/{id}",
    "action": "App\\Http\\Controllers\\ContactNameController@edit",
    "views": [
      "contactName.edit"
    ],
    "intro": "3883302a",
    "comparisons": [
      {
        "view": "contactName.edit",
        "file": "resources/views/contactName/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/contactName/edit.js"
        ],
        "new_js": [
          "js/contactName/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/contactName/edit.js"
      ],
      "new_js": [
        "js/contactName/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "contactName.indexrestore",
    "uri": "dashboard/contactos/restore",
    "action": "App\\Http\\Controllers\\ContactNameController@indexrestore",
    "views": [
      "contactName.restore"
    ],
    "intro": "3883302a",
    "comparisons": [
      {
        "view": "contactName.restore",
        "file": "resources/views/contactName/restore.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table",
          "route('contactName.index"
        ],
        "old_js": [
          "js/contactName/restore.js"
        ],
        "new_js": [
          "js/contactName/restore.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/contactName/restore.js"
      ],
      "new_js": [
        "js/contactName/restore.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "materialtype.index",
    "uri": "dashboard/TiposMateriales",
    "action": "App\\Http\\Controllers\\MaterialTypeController@index",
    "views": [
      "materialtype.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "materialtype.index",
        "file": "resources/views/materialtype/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-backend-sync-open",
          "data-buttons",
          "data-delete-cancel",
          "data-selection",
          "data-table",
          "data-url-bulk",
          "data-url-data",
          "data-url-edit",
          "id=\"modalDeleteLabel",
          "route('dashboard.principal"
        ],
        "old_js": [
          "js/materialtype/index.js"
        ],
        "new_js": [
          "js/materialtype/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/materialtype/index.js"
      ],
      "new_js": [
        "js/materialtype/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": [
      "/dashboard/all/materialtypes",
      "/dashboard/materialtype/delete-multiple"
    ]
  },
  {
    "name": "materialtype.create",
    "uri": "dashboard/crear/tipomaterial",
    "action": "App\\Http\\Controllers\\MaterialTypeController@create",
    "views": [
      "materialtype.create"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "materialtype.create",
        "file": "resources/views/materialtype/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/materialtype/create.js"
        ],
        "new_js": [
          "js/materialtype/create.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/materialtype/create.js"
      ],
      "new_js": [
        "js/materialtype/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "materialtype.edit",
    "uri": "dashboard/editar/tipomaterial/{id}",
    "action": "App\\Http\\Controllers\\MaterialTypeController@edit",
    "views": [
      "materialtype.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "materialtype.edit",
        "file": "resources/views/materialtype/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/materialtype/edit.js"
        ],
        "new_js": [
          "js/materialtype/edit.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/materialtype/edit.js"
      ],
      "new_js": [
        "js/materialtype/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "subtype.index",
    "uri": "dashboard/Subtipos",
    "action": "App\\Http\\Controllers\\SubtypeController@index",
    "views": [
      "subtype.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "subtype.index",
        "file": "resources/views/subtype/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('subtype.index"
        ],
        "added": [
          "data-backend-sync-open",
          "data-buttons",
          "data-delete-cancel",
          "data-selection",
          "data-table",
          "data-url-bulk",
          "data-url-data",
          "data-url-edit",
          "id=\"modalDeleteLabel"
        ],
        "old_js": [
          "js/subtype/index.js"
        ],
        "new_js": [
          "js/subtype/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "subtype.index"
      ],
      "old_js": [
        "js/subtype/index.js"
      ],
      "new_js": [
        "js/subtype/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/subtypes",
      "/dashboard/subtype/delete-multiple"
    ]
  },
  {
    "name": "subtype.create",
    "uri": "dashboard/crear/subtipo",
    "action": "App\\Http\\Controllers\\SubtypeController@create",
    "views": [
      "subtype.create"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "subtype.create",
        "file": "resources/views/subtype/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/subtype/create.js"
        ],
        "new_js": [
          "js/subtype/create.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/subtype/create.js"
      ],
      "new_js": [
        "js/subtype/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "subtype.edit",
    "uri": "dashboard/editar/subtipo/{id}",
    "action": "App\\Http\\Controllers\\SubtypeController@edit",
    "views": [
      "subtype.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "subtype.edit",
        "file": "resources/views/subtype/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/subtype/edit.js"
        ],
        "new_js": [
          "js/subtype/edit.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/subtype/edit.js"
      ],
      "new_js": [
        "js/subtype/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "category.index",
    "uri": "dashboard/Categorias",
    "action": "App\\Http\\Controllers\\CategoryController@index",
    "views": [
      "category.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "category.index",
        "file": "resources/views/category/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('category.index"
        ],
        "added": [
          "data-backend-sync-open",
          "data-buttons",
          "data-delete-cancel",
          "data-selection",
          "data-table",
          "data-url-bulk",
          "data-url-data",
          "data-url-edit",
          "id=\"modalDeleteLabel"
        ],
        "old_js": [
          "js/Category/index.js"
        ],
        "new_js": [
          "js/Category/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "category.index"
      ],
      "old_js": [
        "js/Category/index.js"
      ],
      "new_js": [
        "js/Category/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/categories",
      "/dashboard/subcategory/delete-multiple"
    ]
  },
  {
    "name": "category.create",
    "uri": "dashboard/crear/categoria",
    "action": "App\\Http\\Controllers\\CategoryController@create",
    "views": [
      "category.create"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "category.create",
        "file": "resources/views/category/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/Category/create.js"
        ],
        "new_js": [
          "js/Category/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/Category/create.js"
      ],
      "new_js": [
        "js/Category/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "category.edit",
    "uri": "dashboard/editar/categoria/{id}",
    "action": "App\\Http\\Controllers\\CategoryController@edit",
    "views": [
      "category.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "category.edit",
        "file": "resources/views/category/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/Category/edit.js"
        ],
        "new_js": [
          "js/Category/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/Category/edit.js"
      ],
      "new_js": [
        "js/Category/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "subcategory.index",
    "uri": "dashboard/Subcategorias",
    "action": "App\\Http\\Controllers\\SubcategoryController@index",
    "views": [
      "subcategory.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "subcategory.index",
        "file": "resources/views/subcategory/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('subcategory.index"
        ],
        "added": [
          "data-backend-sync-open",
          "data-buttons",
          "data-delete-cancel",
          "data-selection",
          "data-table",
          "data-url-bulk",
          "data-url-data",
          "data-url-edit",
          "id=\"modalDeleteLabel"
        ],
        "old_js": [
          "js/subcategory/index.js"
        ],
        "new_js": [
          "js/subcategory/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "subcategory.index"
      ],
      "old_js": [
        "js/subcategory/index.js"
      ],
      "new_js": [
        "js/subcategory/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/subcategories",
      "/dashboard/subcategory/delete-multiple"
    ]
  },
  {
    "name": "subcategory.create",
    "uri": "dashboard/crear/subcategoria",
    "action": "App\\Http\\Controllers\\SubcategoryController@create",
    "views": [
      "subcategory.create"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "subcategory.create",
        "file": "resources/views/subcategory/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/subcategory/create.js"
        ],
        "new_js": [
          "js/subcategory/create.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/subcategory/create.js"
      ],
      "new_js": [
        "js/subcategory/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "subcategory.edit",
    "uri": "dashboard/editar/subcategoria/{id}",
    "action": "App\\Http\\Controllers\\SubcategoryController@edit",
    "views": [
      "subcategory.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "subcategory.edit",
        "file": "resources/views/subcategory/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/subcategory/edit.js"
        ],
        "new_js": [
          "js/subcategory/edit.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/subcategory/edit.js"
      ],
      "new_js": [
        "js/subcategory/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "categoryInvoice.index",
    "uri": "dashboard/categorias/facturas",
    "action": "App\\Http\\Controllers\\CategoryInvoiceController@index",
    "views": [
      "categoryInvoice.index"
    ],
    "intro": "651667fa",
    "comparisons": [
      {
        "view": "categoryInvoice.index",
        "file": "resources/views/categoryInvoice/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/categoryInvoice/index.js"
        ],
        "new_js": [
          "js/categoryInvoice/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/categoryInvoice/index.js"
      ],
      "new_js": [
        "js/categoryInvoice/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "categoryInvoice.create",
    "uri": "dashboard/crear/categoria/facturas",
    "action": "App\\Http\\Controllers\\CategoryInvoiceController@create",
    "views": [
      "categoryInvoice.create"
    ],
    "intro": "651667fa",
    "comparisons": [
      {
        "view": "categoryInvoice.create",
        "file": "resources/views/categoryInvoice/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/categoryInvoice/create.js"
        ],
        "new_js": [
          "js/categoryInvoice/create.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/categoryInvoice/create.js"
      ],
      "new_js": [
        "js/categoryInvoice/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "categoryInvoice.edit",
    "uri": "dashboard/editar/categoria/factura/{id}",
    "action": "App\\Http\\Controllers\\CategoryInvoiceController@edit",
    "views": [
      "categoryInvoice.edit"
    ],
    "intro": "651667fa",
    "comparisons": [
      {
        "view": "categoryInvoice.edit",
        "file": "resources/views/categoryInvoice/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/categoryInvoice/edit.js"
        ],
        "new_js": [
          "js/categoryInvoice/edit.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/categoryInvoice/edit.js"
      ],
      "new_js": [
        "js/categoryInvoice/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "exampler.index",
    "uri": "dashboard/Modelos",
    "action": "App\\Http\\Controllers\\ExamplerController@index",
    "views": [
      "exampler.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "exampler.index",
        "file": "resources/views/exampler/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('exampler.index"
        ],
        "added": [
          "data-backend-sync-open",
          "data-buttons",
          "data-can-create",
          "data-can-destroy",
          "data-can-update",
          "data-delete-cancel",
          "data-selection",
          "data-table",
          "data-url-bulk",
          "data-url-create",
          "data-url-data",
          "data-url-edit",
          "id=\"modalDeleteLabel"
        ],
        "old_js": [
          "js/exampler/index.js"
        ],
        "new_js": [
          "js/exampler/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [
        "csrf-token"
      ],
      "routes": [
        "exampler.index"
      ],
      "old_js": [
        "js/exampler/index.js"
      ],
      "new_js": [
        "js/exampler/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/examplers",
      "/dashboard/exampler/delete-multiple"
    ]
  },
  {
    "name": "exampler.create",
    "uri": "dashboard/crear/modelo",
    "action": "App\\Http\\Controllers\\ExamplerController@create",
    "views": [
      "exampler.create"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "exampler.create",
        "file": "resources/views/exampler/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/exampler/create.js"
        ],
        "new_js": [
          "js/exampler/create.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/exampler/create.js"
      ],
      "new_js": [
        "js/exampler/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "exampler.edit",
    "uri": "dashboard/editar/modelo/{id}",
    "action": "App\\Http\\Controllers\\ExamplerController@edit",
    "views": [
      "exampler.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "exampler.edit",
        "file": "resources/views/exampler/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/exampler/edit.js"
        ],
        "new_js": [
          "js/exampler/edit.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/exampler/edit.js"
      ],
      "new_js": [
        "js/exampler/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "brand.index",
    "uri": "dashboard/Marcas",
    "action": "App\\Http\\Controllers\\BrandController@index",
    "views": [
      "brand.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "brand.index",
        "file": "resources/views/brand/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('brand.index"
        ],
        "added": [
          "data-backend-sync-open",
          "data-buttons",
          "data-can-create",
          "data-can-destroy",
          "data-can-update",
          "data-delete-cancel",
          "data-selection",
          "data-table",
          "data-url-bulk",
          "data-url-create",
          "data-url-data",
          "data-url-edit",
          "id=\"modalDeleteLabel"
        ],
        "old_js": [
          "js/brand/index.js"
        ],
        "new_js": [
          "js/brand/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [
        "csrf-token"
      ],
      "routes": [
        "brand.index"
      ],
      "old_js": [
        "js/brand/index.js"
      ],
      "new_js": [
        "js/brand/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/brands",
      "/dashboard/brand/delete-multiple"
    ]
  },
  {
    "name": "brand.create",
    "uri": "dashboard/crear/marca",
    "action": "App\\Http\\Controllers\\BrandController@create",
    "views": [
      "brand.create"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "brand.create",
        "file": "resources/views/brand/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/brand/create.js"
        ],
        "new_js": [
          "js/brand/create.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/brand/create.js"
      ],
      "new_js": [
        "js/brand/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "brand.edit",
    "uri": "dashboard/editar/marca/{id}",
    "action": "App\\Http\\Controllers\\BrandController@edit",
    "views": [
      "brand.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "brand.edit",
        "file": "resources/views/brand/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/brand/edit.js"
        ],
        "new_js": [
          "js/brand/edit.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/brand/edit.js"
      ],
      "new_js": [
        "js/brand/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "warrant.index",
    "uri": "dashboard/Cédulas",
    "action": "App\\Http\\Controllers\\WarrantController@index",
    "views": [
      "warrant.index"
    ],
    "intro": "b898890a",
    "comparisons": [
      {
        "view": "warrant.index",
        "file": "resources/views/warrant/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('genero.create",
          "route('genero.destroy",
          "route('genero.index"
        ],
        "added": [
          "route('warrant.create",
          "route('warrant.destroy",
          "route('warrant.index"
        ],
        "old_js": [
          "js/warrant/index.js"
        ],
        "new_js": [
          "js/warrant/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [
        "csrf-token"
      ],
      "routes": [
        "genero.create",
        "genero.destroy",
        "genero.index"
      ],
      "old_js": [
        "js/warrant/index.js"
      ],
      "new_js": [
        "js/warrant/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/generos",
      "/dashboard/genero/delete-multiple"
    ]
  },
  {
    "name": "warrant.create",
    "uri": "dashboard/crear/cedula",
    "action": "App\\Http\\Controllers\\WarrantController@create",
    "views": [
      "warrant.create"
    ],
    "intro": "b898890a",
    "comparisons": [
      {
        "view": "warrant.create",
        "file": "resources/views/warrant/create.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('genero.store"
        ],
        "added": [
          "route('warrant.store"
        ],
        "old_js": [
          "js/warrant/create.js"
        ],
        "new_js": [
          "js/warrant/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "genero.store"
      ],
      "old_js": [
        "js/warrant/create.js"
      ],
      "new_js": [
        "js/warrant/create.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "warrant.edit",
    "uri": "dashboard/editar/cedula/{id}",
    "action": "App\\Http\\Controllers\\WarrantController@edit",
    "views": [
      "warrant.edit"
    ],
    "intro": "b898890a",
    "comparisons": [
      {
        "view": "warrant.edit",
        "file": "resources/views/warrant/edit.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('genero.index",
          "route('genero.update"
        ],
        "added": [
          "route('warrant.index",
          "route('warrant.update"
        ],
        "old_js": [
          "js/warrant/edit.js"
        ],
        "new_js": [
          "js/warrant/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "genero.index",
        "genero.update"
      ],
      "old_js": [
        "js/warrant/edit.js"
      ],
      "new_js": [
        "js/warrant/edit.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "quality.index",
    "uri": "dashboard/Calidades",
    "action": "App\\Http\\Controllers\\QualityController@index",
    "views": [
      "quality.index"
    ],
    "intro": "d287d4ca",
    "comparisons": [
      {
        "view": "quality.index",
        "file": "resources/views/quality/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('talla.create",
          "route('talla.index"
        ],
        "added": [
          "data-table",
          "route('quality.create",
          "route('quality.index"
        ],
        "old_js": [
          "js/quality/index.js"
        ],
        "new_js": [
          "js/quality/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [
        "csrf-token"
      ],
      "routes": [
        "talla.create",
        "talla.index"
      ],
      "old_js": [
        "js/quality/index.js"
      ],
      "new_js": [
        "js/quality/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/talla/delete-multiple"
    ]
  },
  {
    "name": "quality.create",
    "uri": "dashboard/crear/calidad",
    "action": "App\\Http\\Controllers\\QualityController@create",
    "views": [
      "quality.create"
    ],
    "intro": "d287d4ca",
    "comparisons": [
      {
        "view": "quality.create",
        "file": "resources/views/quality/create.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('talla.index",
          "route('talla.store"
        ],
        "added": [
          "route('quality.index",
          "route('quality.store"
        ],
        "old_js": [
          "js/quality/create.js"
        ],
        "new_js": [
          "js/quality/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "talla.index",
        "talla.store"
      ],
      "old_js": [
        "js/quality/create.js"
      ],
      "new_js": [
        "js/quality/create.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "quality.edit",
    "uri": "dashboard/editar/calidad/{id}",
    "action": "App\\Http\\Controllers\\QualityController@edit",
    "views": [
      "quality.edit"
    ],
    "intro": "d287d4ca",
    "comparisons": [
      {
        "view": "quality.edit",
        "file": "resources/views/quality/edit.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('talla.index",
          "route('talla.update"
        ],
        "added": [
          "route('quality.index",
          "route('quality.update"
        ],
        "old_js": [
          "js/quality/edit.js"
        ],
        "new_js": [
          "js/quality/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "talla.index",
        "talla.update"
      ],
      "old_js": [
        "js/quality/edit.js"
      ],
      "new_js": [
        "js/quality/edit.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "typescrap.index",
    "uri": "dashboard/Retacerías",
    "action": "App\\Http\\Controllers\\TypescrapController@index",
    "views": [
      "typeScrap.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "typeScrap.index",
        "file": "resources/views/typeScrap/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-backend-sync-open",
          "data-buttons",
          "data-can-create",
          "data-can-destroy",
          "data-can-update",
          "data-delete-cancel",
          "data-selection",
          "data-table",
          "data-url-bulk",
          "data-url-create",
          "data-url-data",
          "data-url-edit",
          "id=\"modalDeleteLabel"
        ],
        "old_js": [
          "js/typescrap/index.js"
        ],
        "new_js": [
          "js/typescrap/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [
        "csrf-token"
      ],
      "routes": [],
      "old_js": [
        "js/typescrap/index.js"
      ],
      "new_js": [
        "js/typescrap/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/typescraps",
      "/dashboard/typescrap/delete-multiple"
    ]
  },
  {
    "name": "typescrap.create",
    "uri": "dashboard/crear/retaceria",
    "action": "App\\Http\\Controllers\\TypescrapController@create",
    "views": [
      "typeScrap.create"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "typeScrap.create",
        "file": "resources/views/typeScrap/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/typescrap/create.js"
        ],
        "new_js": [
          "js/typescrap/create.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/typescrap/create.js"
      ],
      "new_js": [
        "js/typescrap/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "typescrap.edit",
    "uri": "dashboard/editar/retaceria/{id}",
    "action": "App\\Http\\Controllers\\TypescrapController@edit",
    "views": [
      "typeScrap.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "typeScrap.edit",
        "file": "resources/views/typeScrap/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/typescrap/edit.js"
        ],
        "new_js": [
          "js/typescrap/edit.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/typescrap/edit.js"
      ],
      "new_js": [
        "js/typescrap/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "unitmeasure.index",
    "uri": "dashboard/Unidades",
    "action": "App\\Http\\Controllers\\UnitMeasureController@index",
    "views": [
      "unitMeasure.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "unitMeasure.index",
        "file": "resources/views/unitMeasure/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('unitmeasure.index"
        ],
        "added": [
          "data-backend-sync-open",
          "data-buttons",
          "data-delete-cancel",
          "data-selection",
          "data-table",
          "data-url-bulk",
          "data-url-data",
          "data-url-edit",
          "id=\"modalDeleteLabel"
        ],
        "old_js": [
          "js/unitmeasure/index.js"
        ],
        "new_js": [
          "js/unitmeasure/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "unitmeasure.index"
      ],
      "old_js": [
        "js/unitmeasure/index.js"
      ],
      "new_js": [
        "js/unitmeasure/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/unitmeasure",
      "/dashboard/unitmeasure/delete-multiple"
    ]
  },
  {
    "name": "unitmeasure.create",
    "uri": "dashboard/crear/unidad",
    "action": "App\\Http\\Controllers\\UnitMeasureController@create",
    "views": [
      "unitMeasure.create"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "unitMeasure.create",
        "file": "resources/views/unitMeasure/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/unitmeasure/create.js"
        ],
        "new_js": [
          "js/unitmeasure/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/unitmeasure/create.js"
      ],
      "new_js": [
        "js/unitmeasure/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "unitmeasure.edit",
    "uri": "dashboard/editar/unidad/{id}",
    "action": "App\\Http\\Controllers\\UnitMeasureController@edit",
    "views": [
      "unitMeasure.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "unitMeasure.edit",
        "file": "resources/views/unitMeasure/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/unitmeasure/edit.js"
        ],
        "new_js": [
          "js/unitmeasure/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/unitmeasure/edit.js"
      ],
      "new_js": [
        "js/unitmeasure/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "genero.index",
    "uri": "dashboard/generos",
    "action": "App\\Http\\Controllers\\GeneroController@index",
    "views": [
      "genero.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "genero.index",
        "file": "resources/views/genero/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('genero.index"
        ],
        "added": [
          "data-backend-sync-open",
          "data-buttons",
          "data-can-create",
          "data-can-destroy",
          "data-can-update",
          "data-delete-cancel",
          "data-selection",
          "data-table",
          "data-url-bulk",
          "data-url-data",
          "data-url-edit",
          "id=\"modalDeleteLabel"
        ],
        "old_js": [
          "js/genero/index.js"
        ],
        "new_js": [
          "js/genero/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "genero.index"
      ],
      "old_js": [
        "js/genero/index.js"
      ],
      "new_js": [
        "js/genero/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/generos",
      "/dashboard/genero/delete-multiple"
    ]
  },
  {
    "name": "genero.create",
    "uri": "dashboard/crear/genero",
    "action": "App\\Http\\Controllers\\GeneroController@create",
    "views": [
      "genero.create"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "genero.create",
        "file": "resources/views/genero/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/genero/create.js"
        ],
        "new_js": [
          "js/genero/create.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/genero/create.js"
      ],
      "new_js": [
        "js/genero/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "genero.edit",
    "uri": "dashboard/editar/genero/{id}",
    "action": "App\\Http\\Controllers\\GeneroController@edit",
    "views": [
      "genero.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "genero.edit",
        "file": "resources/views/genero/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/genero/edit.js"
        ],
        "new_js": [
          "js/genero/edit.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/genero/edit.js"
      ],
      "new_js": [
        "js/genero/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "talla.index",
    "uri": "dashboard/tallas",
    "action": "App\\Http\\Controllers\\TallaController@index",
    "views": [
      "talla.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "talla.index",
        "file": "resources/views/talla/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('talla.index"
        ],
        "added": [
          "data-backend-sync-open",
          "data-buttons",
          "data-can-create",
          "data-delete-cancel",
          "data-dismiss",
          "data-selection",
          "data-table",
          "data-url",
          "data-url-create",
          "id=\"modalDelete",
          "id=\"modalDeleteLabel",
          "name=\"talla_id"
        ],
        "old_js": [
          "js/talla/index.js"
        ],
        "new_js": [
          "js/talla/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [
        "csrf-token"
      ],
      "routes": [
        "talla.index"
      ],
      "old_js": [
        "js/talla/index.js"
      ],
      "new_js": [
        "js/talla/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "talla.create",
    "uri": "dashboard/crear/talla",
    "action": "App\\Http\\Controllers\\TallaController@create",
    "views": [
      "talla.create"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "talla.create",
        "file": "resources/views/talla/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/talla/form.js"
        ],
        "new_js": [
          "js/talla/form.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/talla/form.js"
      ],
      "new_js": [
        "js/talla/form.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "talla.edit",
    "uri": "dashboard/editar/talla/{id}",
    "action": "App\\Http\\Controllers\\TallaController@edit",
    "views": [
      "talla.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "talla.edit",
        "file": "resources/views/talla/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/talla/form.js"
        ],
        "new_js": [
          "js/talla/form.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/talla/form.js"
      ],
      "new_js": [
        "js/talla/form.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "material.create",
    "uri": "dashboard/crear/material",
    "action": "App\\Http\\Controllers\\MaterialController@create",
    "views": [
      "materialDetailSetting.required",
      "material.create"
    ],
    "intro": "aa2ded06",
    "comparisons": [
      {
        "view": "materialDetailSetting.required",
        "file": "resources/views/materialDetailSetting/required.blade.php",
        "baseline_exists": false,
        "removed": [],
        "added": [
          "route('settings.material"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      },
      {
        "view": "material.create",
        "file": "resources/views/material/create.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-card-widget",
          "name=\"afecto_inventario"
        ],
        "added": [
          "data-label",
          "data-modal-autofocus",
          "data-modal-cancel",
          "data-variant-color-label",
          "data-variant-file-name",
          "data-variant-size-label",
          "id=\"modalBrandComment",
          "id=\"modalBrandName",
          "id=\"modalCategoriaDescription",
          "id=\"modalCategoriaName",
          "id=\"modalColorCode",
          "id=\"modalColorLabel",
          "id=\"modalColorName",
          "id=\"modalColorShortName",
          "id=\"modalExamplerComment",
          "id=\"modalExamplerLabel",
          "id=\"modalExamplerName",
          "id=\"modalGeneroDescription",
          "id=\"modalGeneroName",
          "id=\"modalSubCategoriaDescription",
          "id=\"modalSubCategoriaLabel",
          "id=\"modalSubCategoriaName",
          "id=\"modalTallaDescription",
          "id=\"modalTallaName",
          "id=\"modalTallaShortName",
          "id=\"modalUnitMeasureDescription",
          "id=\"modalUnitMeasureName",
          "name=\"type_tax_id"
        ],
        "old_js": [
          "js/material/create.js"
        ],
        "new_js": [
          "js/material/create.js"
        ],
        "same_js": false,
        "includes": [
          "materialDetailSetting._context"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [
        "${response.data.short_name}",
        "afecto_inventario"
      ],
      "routes": [],
      "old_js": [
        "js/material/create.js"
      ],
      "new_js": [
        "js/material/create.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "material.edit",
    "uri": "dashboard/editar/material/{id}",
    "action": "App\\Http\\Controllers\\MaterialController@edit",
    "views": [
      "materialDetailSetting.required",
      "material.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "materialDetailSetting.required",
        "file": "resources/views/materialDetailSetting/required.blade.php",
        "baseline_exists": false,
        "removed": [],
        "added": [
          "route('settings.material"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      },
      {
        "view": "material.edit",
        "file": "resources/views/material/edit.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-card-widget",
          "data-delete",
          "data-image_label",
          "data-stock_maximo",
          "data-stock_minimo",
          "name=\"afecto_inventario",
          "name=\"precio_promedio",
          "name=\"reservado",
          "name=\"stock_actual",
          "name=\"stock_max",
          "name=\"stock_min",
          "name=\"ultimo_costo",
          "name=\"warehouse",
          "name=\"warehouse_id",
          "route('material.index"
        ],
        "added": [
          "data-modal-autofocus",
          "data-modal-cancel",
          "data-multiple-variant-content",
          "data-stock_general",
          "data-variant-color-label",
          "data-variant-file-name",
          "data-variant-size-label",
          "id=\"modalColorLabel",
          "id=\"modalEditColorCode",
          "id=\"modalEditColorName",
          "id=\"modalEditColorShortName",
          "id=\"modalTallaDescription",
          "id=\"modalTallaName",
          "id=\"modalTallaShortName",
          "name=\"type_tax_id"
        ],
        "old_js": [
          "js/material/edit.js"
        ],
        "new_js": [
          "js/material/edit.js",
          "js/material/inventory-context.js"
        ],
        "same_js": false,
        "includes": [
          "materialDetailSetting._context",
          "material._inventoryContext"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [
        "delete",
        "image_label",
        "stock_maximo",
        "stock_minimo"
      ],
      "names": [
        "${response.data.short_name}",
        "afecto_inventario",
        "precio_promedio",
        "reservado",
        "stock_actual",
        "stock_max",
        "stock_min",
        "ultimo_costo",
        "warehouse",
        "warehouse_id"
      ],
      "routes": [
        "material.index"
      ],
      "old_js": [
        "js/material/edit.js"
      ],
      "new_js": [
        "js/material/edit.js",
        "js/material/inventory-context.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "material.indexV2",
    "uri": "dashboard/listado/materiales/v2",
    "action": "App\\Http\\Controllers\\MaterialController@indexV2",
    "views": [
      "material.indexv2"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "material.indexv2",
        "file": "resources/views/material/indexv2.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-ver_items",
          "name=\"calidad",
          "name=\"cedula"
        ],
        "added": [
          "data-boundary",
          "data-buttons",
          "data-mp-currency",
          "data-mp-label",
          "data-mp-new-label",
          "data-table"
        ],
        "old_js": [
          "js/material/indexV2.js"
        ],
        "new_js": [
          "js/material/indexV2.js",
          "js/material/inventory-context.js",
          "js/material/presentations-branch.js"
        ],
        "same_js": false,
        "includes": [
          "material._inventoryContext",
          "material._resumen_popup"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [
        "ver_items"
      ],
      "names": [
        "calidad",
        "cedula"
      ],
      "routes": [],
      "old_js": [
        "js/material/indexV2.js"
      ],
      "new_js": [
        "js/material/indexV2.js",
        "js/material/inventory-context.js",
        "js/material/presentations-branch.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/materials-presentations/material/${currentMaterialId}/presentations",
      "/dashboard/materials-presentations/material/${materialId}/presentations",
      "/dashboard/materials-presentations/presentation/${id}",
      "/dashboard/materials-presentations/presentation/${id}/toggle"
    ]
  },
  {
    "name": "material.stock.getItems",
    "uri": "dashboard/view/stock/material/items/{id}",
    "action": "App\\Http\\Controllers\\MaterialController@getItemsStockItems",
    "views": [
      "material.stockItems_items"
    ],
    "intro": "6de44064",
    "comparisons": [
      {
        "view": "material.stockItems_items",
        "file": "resources/views/material/stockItems_items.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-dismiss",
          "id=\"btnGuardarCodigoItem",
          "id=\"modalEditarCodigoItem"
        ],
        "added": [
          "data-table",
          "route('material.stock.getItemsMaterial"
        ],
        "old_js": [
          "js/material/inventory-context.js",
          "js/material/stockItems_items.js"
        ],
        "new_js": [
          "js/material/inventory-context.js",
          "js/material/stockItems_items.js"
        ],
        "same_js": false,
        "includes": [
          "material._inventoryContext"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [
        "column",
        "item-id"
      ],
      "names": [
        "csrf-token"
      ],
      "routes": [],
      "old_js": [
        "js/material/inventory-context.js",
        "js/material/stockItems_items.js"
      ],
      "new_js": [
        "js/material/inventory-context.js",
        "js/material/stockItems_items.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/stock/material/item/",
      "/dashboard/view/stock/material/all/items/"
    ]
  },
  {
    "name": "warehouseTransfer.index",
    "uri": "dashboard/traslados/almacenes",
    "action": "App\\Http\\Controllers\\WarehouseTransferController@index",
    "views": [
      "warehouseTransfer.index"
    ],
    "intro": "087f9311",
    "comparisons": [
      {
        "view": "warehouseTransfer.index",
        "file": "resources/views/warehouseTransfer/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-boundary",
          "data-table",
          "data-toggle"
        ],
        "old_js": [],
        "new_js": [
          "js/warehouseTransfer/operations.js"
        ],
        "same_js": false,
        "includes": [
          "warehouseTransfer._status"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": [
        "js/warehouseTransfer/operations.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "warehouseTransfer.create",
    "uri": "dashboard/traslados/almacenes/crear",
    "action": "App\\Http\\Controllers\\WarehouseTransferController@create",
    "views": [
      "warehouseTransfer.create"
    ],
    "intro": "087f9311",
    "comparisons": [
      {
        "view": "warehouseTransfer.create",
        "file": "resources/views/warehouseTransfer/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/warehouseTransfer/operations.js"
        ],
        "new_js": [
          "js/warehouseTransfer/operations.js"
        ],
        "same_js": false,
        "includes": [
          "warehouseTransfer._context"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/warehouseTransfer/operations.js"
      ],
      "new_js": [
        "js/warehouseTransfer/operations.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "warehouseTransfer.show",
    "uri": "dashboard/traslados/almacenes/{id}",
    "action": "App\\Http\\Controllers\\WarehouseTransferController@show",
    "views": [
      "warehouseTransfer.show"
    ],
    "intro": "087f9311",
    "comparisons": [
      {
        "view": "warehouseTransfer.show",
        "file": "resources/views/warehouseTransfer/show.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/warehouseTransfer/operations.js"
        ],
        "new_js": [
          "js/warehouseTransfer/operations.js"
        ],
        "same_js": false,
        "includes": [
          "warehouseTransfer._status",
          "warehouseTransfer._context",
          "warehouseTransfer._context",
          "warehouseTransfer._context",
          "warehouseTransfer._context"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/warehouseTransfer/operations.js"
      ],
      "new_js": [
        "js/warehouseTransfer/operations.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "warehouseAvailability.index",
    "uri": "dashboard/disponibilidad/almacenes",
    "action": "App\\Http\\Controllers\\WarehouseAvailabilityController@index",
    "views": [
      "warehouseAvailability.index"
    ],
    "intro": "a90d322a",
    "comparisons": [
      {
        "view": "warehouseAvailability.index",
        "file": "resources/views/warehouseAvailability/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/warehouseAvailability/index.js"
        ],
        "new_js": [
          "js/warehouseAvailability/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/warehouseAvailability/index.js"
      ],
      "new_js": [
        "js/warehouseAvailability/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "entry.purchase.indexV2",
    "uri": "dashboard/listado/entradas/compra/v2",
    "action": "App\\Http\\Controllers\\EntryController@listEntryPurchaseV2",
    "views": [
      "entry.index_entry_purchasev2"
    ],
    "intro": "2ec61f31",
    "comparisons": [
      {
        "view": "entry.index_entry_purchasev2",
        "file": "resources/views/entry/index_entry_purchasev2.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-anular",
          "data-buttons",
          "data-code",
          "data-delete",
          "data-detail",
          "data-detalles",
          "data-dismiss",
          "data-documentos",
          "data-editar",
          "data-file",
          "data-i",
          "data-image",
          "data-imagen",
          "data-length",
          "data-location",
          "data-material",
          "data-pdf",
          "data-placement",
          "data-price",
          "data-quantity",
          "data-src",
          "data-state",
          "data-url",
          "data-weight",
          "data-width",
          "id=\"modalDelete",
          "id=\"modalImage",
          "id=\"modalItems",
          "name=\"material_id",
          "route('entry.purchase.create",
          "route('material.destroy"
        ],
        "added": [
          "data-id",
          "data-table",
          "data-target"
        ],
        "old_js": [
          "js/entry/index_entry_purchaseV2.js"
        ],
        "new_js": [
          "js/entry/index_entry_purchaseV2.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "anular",
        "buttons",
        "code",
        "delete",
        "description",
        "detail",
        "detalles",
        "documentos",
        "editar",
        "file",
        "i",
        "image",
        "imagen",
        "length",
        "location",
        "material",
        "pdf",
        "price",
        "quantity",
        "series",
        "src",
        "state",
        "url",
        "weight",
        "width"
      ],
      "names": [
        "csrf-token",
        "material_id"
      ],
      "routes": [
        "entry.purchase.create",
        "material.destroy"
      ],
      "old_js": [
        "js/entry/index_entry_purchaseV2.js"
      ],
      "new_js": [
        "js/entry/index_entry_purchaseV2.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/entry_purchase/destroy/",
      "/dashboard/get/json/items/"
    ]
  },
  {
    "name": "entry.purchase.show",
    "uri": "dashboard/entrada/compra/visualizar/{entry}",
    "action": "App\\Http\\Controllers\\EntryController@showEntryPurchase",
    "views": [
      "entry.show_entry_purchase"
    ],
    "intro": "483a7858",
    "comparisons": [
      {
        "view": "entry.show_entry_purchase",
        "file": "resources/views/entry/show_entry_purchase.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-bootstrap-switch",
          "data-card-widget",
          "data-delete",
          "data-dismiss",
          "data-image",
          "data-locationGroup",
          "data-locations",
          "data-off-color",
          "data-off-text",
          "data-on-color",
          "data-on-text",
          "data-series",
          "data-stateGroup",
          "data-states",
          "data-toggle",
          "data-url",
          "id=\"btn-grouped",
          "id=\"btn-saveGroupItems",
          "id=\"btn-saveItems",
          "id=\"btnNew",
          "id=\"modalAddGroupItems",
          "id=\"modalAddItems",
          "id=\"modalImage",
          "name=\"date_invoice",
          "name=\"deferred_invoice",
          "name=\"entry_id",
          "name=\"entry_type",
          "name=\"invoice",
          "name=\"locationGroup",
          "name=\"locations[]",
          "name=\"material_GroupSelected",
          "name=\"material_selected",
          "name=\"observation",
          "name=\"price_GroupSelected",
          "name=\"price_selected",
          "name=\"purchase_order",
          "name=\"quantity_GroupSelected",
          "name=\"quantity_selected",
          "name=\"referral_guide",
          "name=\"series[]",
          "name=\"supplier_id",
          "route('dashboard.principal",
          "route('entry.purchase.update"
        ],
        "added": [
          "data-table",
          "route('entry.purchase.indexV2"
        ],
        "old_js": [
          "js/entry/edit_entry_purchase.js"
        ],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "delete",
        "deleteOld",
        "image",
        "locationGroup",
        "locations",
        "series",
        "stateGroup",
        "states",
        "url"
      ],
      "names": [
        "csrf-token",
        "date_invoice",
        "deferred_invoice",
        "entry_id",
        "entry_type",
        "invoice",
        "locationGroup",
        "locations[]",
        "material_GroupSelected",
        "material_selected",
        "my-checkbox",
        "observation",
        "price_GroupSelected",
        "price_selected",
        "purchase_order",
        "quantity_GroupSelected",
        "quantity_selected",
        "referral_guide",
        "series[]",
        "supplier_id"
      ],
      "routes": [
        "dashboard.principal",
        "entry.purchase.update"
      ],
      "old_js": [
        "js/entry/edit_entry_purchase.js"
      ],
      "new_js": []
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/add/materials/entry/",
      "/dashboard/destroy/detail/",
      "/dashboard/get/locations",
      "/dashboard/get/materials/stock/items/entry"
    ]
  },
  {
    "name": "order.purchase.list.indexV2",
    "uri": "dashboard/entradas/compra/ordenes/v2",
    "action": "App\\Http\\Controllers\\EntryController@listOrderPurchaseV2",
    "views": [
      "entry.listOrderPurchaseV2"
    ],
    "intro": "2ec61f31",
    "comparisons": [
      {
        "view": "entry.listOrderPurchaseV2",
        "file": "resources/views/entry/listOrderPurchaseV2.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-buttons",
          "data-crear_entrada",
          "data-imprimir",
          "data-placement",
          "data-ver_orden",
          "name=\"deliveryDate"
        ],
        "added": [
          "data-table",
          "data-target"
        ],
        "old_js": [
          "js/entry/listOrdersV2.js"
        ],
        "new_js": [
          "js/entry/listOrdersV2.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "buttons",
        "crear_entrada",
        "imprimir",
        "ver_orden"
      ],
      "names": [
        "csrf-token",
        "deliveryDate"
      ],
      "routes": [],
      "old_js": [
        "js/entry/listOrdersV2.js"
      ],
      "new_js": [
        "js/entry/listOrdersV2.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "entry.purchase.report.index",
    "uri": "dashboard/entradas/compra/reporte",
    "action": "App\\Http\\Controllers\\EntryController@indexEntryPurchaseReport",
    "views": [
      "entry.index_entry_purchase_report"
    ],
    "intro": "6097da5e",
    "comparisons": [
      {
        "view": "entry.index_entry_purchase_report",
        "file": "resources/views/entry/index_entry_purchase_report.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-code",
          "data-dismiss",
          "data-i",
          "data-length",
          "data-location",
          "data-material",
          "data-price",
          "data-quantity",
          "data-state",
          "data-url",
          "data-weight",
          "data-width",
          "id=\"modalDelete",
          "id=\"modalImage",
          "id=\"modalItems",
          "name=\"material_id",
          "route('material.destroy"
        ],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/entry/index_entry_purchase_report.js"
        ],
        "new_js": [
          "js/entry/index_entry_purchase_report.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "code",
        "delete",
        "description",
        "detail",
        "i",
        "id",
        "image",
        "item",
        "length",
        "location",
        "material",
        "price",
        "quantity",
        "series",
        "src",
        "state",
        "url",
        "weight",
        "width"
      ],
      "names": [
        "csrf-token",
        "material_id"
      ],
      "routes": [
        "material.destroy"
      ],
      "old_js": [
        "js/entry/index_entry_purchase_report.js"
      ],
      "new_js": [
        "js/entry/index_entry_purchase_report.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/entry_purchase/destroy/",
      "/dashboard/get/json/items/"
    ]
  },
  {
    "name": null,
    "uri": "dashboard/ver/orden/compra/normal/{id}",
    "action": "App\\Http\\Controllers\\OrderPurchaseController@showOrderPurchaseNormal",
    "views": [
      "orderPurchase.showNormal"
    ],
    "intro": "abc9daa0",
    "comparisons": [
      {
        "view": "orderPurchase.showNormal",
        "file": "resources/views/orderPurchase/showNormal.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-bootstrap-switch",
          "data-card-widget",
          "data-code",
          "data-delete",
          "data-description",
          "data-id",
          "data-material",
          "data-price",
          "data-price2",
          "data-quantity",
          "data-toggle",
          "data-total",
          "data-url",
          "id=\"btn-currency",
          "name=\"currency_order",
          "name=\"date_arrival",
          "name=\"date_order",
          "name=\"observation",
          "name=\"order_id",
          "name=\"purchase_condition",
          "name=\"purchase_order",
          "name=\"quote_id",
          "name=\"quote_supplier",
          "route('dashboard.principal"
        ],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/orderPurchase/showNormal.js"
        ],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "add",
        "code",
        "delete",
        "description",
        "edit",
        "id",
        "material",
        "price",
        "price2",
        "quantity",
        "total",
        "url"
      ],
      "names": [
        "csrf-token",
        "currency_order",
        "date_arrival",
        "date_order",
        "observation",
        "order_id",
        "purchase_condition",
        "purchase_order",
        "quote_id",
        "quote_supplier"
      ],
      "routes": [
        "dashboard.principal"
      ],
      "old_js": [
        "js/orderPurchase/showNormal.js"
      ],
      "new_js": []
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/destroy/detail/order/purchase/express/",
      "/dashboard/update/detail/order/purchase/express/"
    ]
  },
  {
    "name": "order.purchase.delete",
    "uri": "dashboard/ordenes/compra/eliminadas",
    "action": "App\\Http\\Controllers\\OrderPurchaseController@indexOrderPurchaseDelete",
    "views": [
      "orderPurchase.deleteGeneral"
    ],
    "intro": "c32352c5",
    "comparisons": [
      {
        "view": "orderPurchase.deleteGeneral",
        "file": "resources/views/orderPurchase/deleteGeneral.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-dismiss",
          "data-url",
          "id=\"modalDelete",
          "name=\"subcategory_id",
          "route('subcategory.destroy"
        ],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/orderPurchase/companyContext.js",
          "js/orderPurchase/deleteGeneral.js"
        ],
        "new_js": [
          "js/orderPurchase/deleteGeneral.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "id",
        "name",
        "restore",
        "url",
        "widget"
      ],
      "names": [
        "csrf-token",
        "state_order",
        "subcategory_id"
      ],
      "routes": [
        "subcategory.destroy"
      ],
      "old_js": [
        "js/orderPurchase/companyContext.js",
        "js/orderPurchase/deleteGeneral.js"
      ],
      "new_js": [
        "js/orderPurchase/deleteGeneral.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/restore/order/purchase/delete/"
    ]
  },
  {
    "name": null,
    "uri": "dashboard/ver/orden/compra/eliminada/{id}",
    "action": "App\\Http\\Controllers\\OrderPurchaseController@showOrderPurchaseDelete",
    "views": [
      "orderPurchase.showDelete"
    ],
    "intro": "c32352c5",
    "comparisons": [
      {
        "view": "orderPurchase.showDelete",
        "file": "resources/views/orderPurchase/showDelete.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-bootstrap-switch",
          "data-card-widget",
          "data-code",
          "data-delete",
          "data-description",
          "data-id",
          "data-material",
          "data-price",
          "data-price2",
          "data-quantity",
          "data-toggle",
          "data-total",
          "data-url",
          "id=\"btn-currency",
          "name=\"currency_order",
          "name=\"date_arrival",
          "name=\"date_order",
          "name=\"observation",
          "name=\"order_id",
          "name=\"purchase_condition",
          "name=\"purchase_order",
          "name=\"quote_id",
          "name=\"quote_supplier",
          "route('dashboard.principal"
        ],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/orderPurchase/showNormal.js"
        ],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "add",
        "code",
        "delete",
        "description",
        "edit",
        "id",
        "material",
        "price",
        "price2",
        "quantity",
        "total",
        "url"
      ],
      "names": [
        "csrf-token",
        "currency_order",
        "date_arrival",
        "date_order",
        "observation",
        "order_id",
        "purchase_condition",
        "purchase_order",
        "quote_id",
        "quote_supplier"
      ],
      "routes": [
        "dashboard.principal"
      ],
      "old_js": [
        "js/orderPurchase/showNormal.js"
      ],
      "new_js": []
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/destroy/detail/order/purchase/express/",
      "/dashboard/update/detail/order/purchase/express/"
    ]
  },
  {
    "name": "order.purchase.general.indexV2",
    "uri": "dashboard/listado/general/ordenes/compra/v2",
    "action": "App\\Http\\Controllers\\OrderPurchaseController@indexV2",
    "views": [
      "orderPurchase.general_v2"
    ],
    "intro": "b1fa9d52",
    "comparisons": [
      {
        "view": "orderPurchase.general_v2",
        "file": "resources/views/orderPurchase/general_v2.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-anular",
          "data-delete",
          "data-dismiss",
          "data-editar",
          "data-estado",
          "data-imprimir",
          "data-name",
          "data-placement",
          "data-url",
          "id=\"btn-changeState",
          "id=\"btn-export",
          "id=\"modalState",
          "name=\"orderPurchase_id",
          "name=\"stateOrder",
          "route('order.purchase.express.create",
          "route('order.purchase.normal.create",
          "route('state.order.purchase.change"
        ],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/orderPurchase/generalV2.js"
        ],
        "new_js": [
          "js/orderPurchase/generalV2.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "anular",
        "delete",
        "editar",
        "estado",
        "formEditFacturacion",
        "imprimir",
        "name",
        "url"
      ],
      "names": [
        "csrf-token",
        "orderPurchase_id",
        "stateOrder",
        "{{--"
      ],
      "routes": [
        "order.purchase.express.create",
        "order.purchase.normal.create",
        "state.order.purchase.change"
      ],
      "old_js": [
        "js/orderPurchase/generalV2.js"
      ],
      "new_js": [
        "js/orderPurchase/generalV2.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/change/state/order/purchase",
      "/dashboard/destroy/order/purchase/normal/",
      "/dashboard/get/info/facturacion/expense/supplier/",
      "/dashboard/get/state/order/purchase/"
    ]
  },
  {
    "name": "order.purchase.express.indexV2",
    "uri": "dashboard/listado/ordenes/compra/express/v2",
    "action": "App\\Http\\Controllers\\OrderPurchaseController@indexExpressV2",
    "views": [
      "orderPurchase.express_v2"
    ],
    "intro": "3ed0b46b",
    "comparisons": [
      {
        "view": "orderPurchase.express_v2",
        "file": "resources/views/orderPurchase/express_v2.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-anular",
          "data-delete",
          "data-dismiss",
          "data-editar",
          "data-estado",
          "data-imprimir",
          "data-name",
          "data-placement",
          "data-toggle",
          "data-url",
          "data-ver_orden",
          "id=\"btn-changeState",
          "id=\"modalState",
          "name=\"orderPurchase_id",
          "name=\"stateOrder",
          "route('order.purchase.express.create",
          "route('state.order.purchase.change"
        ],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/orderPurchase/expressV2.js"
        ],
        "new_js": [
          "js/orderPurchase/expressV2.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "anular",
        "delete",
        "editar",
        "estado",
        "formEditFacturacion",
        "imprimir",
        "name",
        "url",
        "ver_orden"
      ],
      "names": [
        "csrf-token",
        "orderPurchase_id",
        "stateOrder",
        "{{--"
      ],
      "routes": [
        "order.purchase.express.create",
        "state.order.purchase.change"
      ],
      "old_js": [
        "js/orderPurchase/expressV2.js"
      ],
      "new_js": [
        "js/orderPurchase/expressV2.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/change/state/order/purchase",
      "/dashboard/destroy/order/purchase/normal/",
      "/dashboard/get/info/facturacion/expense/supplier/",
      "/dashboard/get/state/order/purchase/"
    ]
  },
  {
    "name": "order.purchase.normal.indexV2",
    "uri": "dashboard/listado/ordenes/compra/normal/v2",
    "action": "App\\Http\\Controllers\\OrderPurchaseController@indexNormalV2",
    "views": [
      "orderPurchase.normal_v2"
    ],
    "intro": "abc9daa0",
    "comparisons": [
      {
        "view": "orderPurchase.normal_v2",
        "file": "resources/views/orderPurchase/normal_v2.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-anular",
          "data-delete",
          "data-dismiss",
          "data-editar",
          "data-estado",
          "data-imprimir",
          "data-name",
          "data-placement",
          "data-url",
          "id=\"btn-changeState",
          "id=\"modalState",
          "name=\"orderPurchase_id",
          "name=\"stateOrder",
          "route('order.purchase.normal.create",
          "route('state.order.purchase.change"
        ],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/orderPurchase/companyContext.js",
          "js/orderPurchase/normalV2.js"
        ],
        "new_js": [
          "js/orderPurchase/normalV2.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "anular",
        "delete",
        "editar",
        "estado",
        "formEditFacturacion",
        "imprimir",
        "name",
        "url"
      ],
      "names": [
        "orderPurchase_id",
        "stateOrder",
        "{{--"
      ],
      "routes": [
        "order.purchase.normal.create",
        "state.order.purchase.change"
      ],
      "old_js": [
        "js/orderPurchase/companyContext.js",
        "js/orderPurchase/normalV2.js"
      ],
      "new_js": [
        "js/orderPurchase/normalV2.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/change/state/order/purchase",
      "/dashboard/destroy/order/purchase/normal/",
      "/dashboard/get/info/facturacion/expense/supplier/",
      "/dashboard/get/state/order/purchase/"
    ]
  },
  {
    "name": "user.profile",
    "uri": "dashboard/perfil",
    "action": "App\\Http\\Controllers\\UserController@profile",
    "views": [
      "user.profile"
    ],
    "intro": "80cb50d8",
    "comparisons": [
      {
        "view": "user.profile",
        "file": "resources/views/user/profile.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/user/profile.js"
        ],
        "new_js": [
          "js/user/profile.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/user/profile.js"
      ],
      "new_js": [
        "js/user/profile.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "invoice.index",
    "uri": "dashboard/factura/compra",
    "action": "App\\Http\\Controllers\\InvoiceController@indexInvoices",
    "views": [
      "invoice.index_invoice"
    ],
    "intro": "fa8f7ede",
    "comparisons": [
      {
        "view": "invoice.index_invoice",
        "file": "resources/views/invoice/index_invoice.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-description",
          "data-dismiss",
          "data-price",
          "data-quantity",
          "data-subtotal",
          "data-taxes",
          "data-total",
          "data-unit",
          "data-url",
          "id=\"modalDelete",
          "id=\"modalImage",
          "id=\"modalItems",
          "name=\"material_id",
          "route('dashboard.principal",
          "route('invoice.create",
          "route('material.destroy"
        ],
        "added": [
          "data-read-url",
          "data-table",
          "route('invoice.index",
          "route('invoice.index.annulled"
        ],
        "old_js": [
          "js/invoice/index_invoice.js"
        ],
        "new_js": [
          "js/invoice/finance-readonly.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "code",
        "delete",
        "deleteOS",
        "description",
        "details",
        "id",
        "image",
        "item",
        "location",
        "price",
        "quantity",
        "series",
        "src",
        "state",
        "subtotal",
        "taxes",
        "total",
        "unit",
        "url"
      ],
      "names": [
        "csrf-token",
        "material_id"
      ],
      "routes": [
        "dashboard.principal",
        "invoice.create",
        "material.destroy"
      ],
      "old_js": [
        "js/invoice/index_invoice.js"
      ],
      "new_js": [
        "js/invoice/finance-readonly.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/destroy/order/service/",
      "/dashboard/destroy/total/invoice/",
      "/dashboard/get/invoice/by/id/",
      "/dashboard/get/json/invoices/purchase",
      "/dashboard/get/service/by/id/"
    ]
  },
  {
    "name": "invoice.index.annulled",
    "uri": "dashboard/factura/compra/anuladas",
    "action": "App\\Http\\Controllers\\InvoiceController@indexInvoicesAnnulled",
    "views": [
      "invoice.index_invoice_annulled"
    ],
    "intro": "fa8f7ede",
    "comparisons": [
      {
        "view": "invoice.index_invoice_annulled",
        "file": "resources/views/invoice/index_invoice_annulled.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-description",
          "data-dismiss",
          "data-price",
          "data-quantity",
          "data-subtotal",
          "data-taxes",
          "data-total",
          "data-unit",
          "data-url",
          "id=\"modalDelete",
          "id=\"modalImage",
          "id=\"modalItems",
          "name=\"material_id",
          "route('dashboard.principal",
          "route('invoice.create",
          "route('material.destroy"
        ],
        "added": [
          "data-read-url",
          "data-table",
          "route('invoice.index",
          "route('invoice.index.annulled"
        ],
        "old_js": [
          "js/invoice/index_invoice_annulled.js"
        ],
        "new_js": [
          "js/invoice/finance-readonly.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "code",
        "delete",
        "deleteOS",
        "description",
        "details",
        "id",
        "image",
        "item",
        "location",
        "price",
        "quantity",
        "series",
        "src",
        "state",
        "subtotal",
        "taxes",
        "total",
        "unit",
        "url"
      ],
      "names": [
        "csrf-token",
        "material_id"
      ],
      "routes": [
        "dashboard.principal",
        "invoice.create",
        "material.destroy"
      ],
      "old_js": [
        "js/invoice/index_invoice_annulled.js"
      ],
      "new_js": [
        "js/invoice/finance-readonly.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/destroy/order/service/",
      "/dashboard/destroy/total/invoice/",
      "/dashboard/get/invoice/by/id/",
      "/dashboard/get/json/invoices/purchase/annulled",
      "/dashboard/get/service/by/id/"
    ]
  },
  {
    "name": "invoice.create",
    "uri": "dashboard/crear/factura/compra",
    "action": "App\\Http\\Controllers\\InvoiceController@createInvoice",
    "views": [
      "invoice.create_invoice"
    ],
    "intro": "6abf4528",
    "comparisons": [
      {
        "view": "invoice.create_invoice",
        "file": "resources/views/invoice/create_invoice.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-card-widget"
        ],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/invoice/invoice.js"
        ],
        "new_js": [
          "js/invoice/invoice.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/invoice/invoice.js"
      ],
      "new_js": [
        "js/invoice/invoice.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "invoice.edit",
    "uri": "dashboard/factura/compra/editar/{entry}",
    "action": "App\\Http\\Controllers\\InvoiceController@editInvoice",
    "views": [
      "invoice.edit_invoice"
    ],
    "intro": "6abf4528",
    "comparisons": [
      {
        "view": "invoice.edit_invoice",
        "file": "resources/views/invoice/edit_invoice.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-card-widget",
          "data-toggle"
        ],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/invoice/edit_invoice.js"
        ],
        "new_js": [
          "js/invoice/edit_invoice.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/invoice/edit_invoice.js"
      ],
      "new_js": [
        "js/invoice/edit_invoice.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "invoice.show",
    "uri": "dashboard/factura/compra/ver/{entry}",
    "action": "App\\Http\\Controllers\\InvoiceController@verInvoice",
    "views": [
      "invoice.show_invoice"
    ],
    "intro": "fa8f7ede",
    "comparisons": [
      {
        "view": "invoice.show_invoice",
        "file": "resources/views/invoice/show_invoice.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-bootstrap-switch",
          "data-card-widget",
          "data-deleteNew",
          "data-description",
          "data-dismiss",
          "data-image",
          "data-locationGroup",
          "data-locations",
          "data-off-color",
          "data-off-text",
          "data-on-color",
          "data-on-text",
          "data-price",
          "data-quantity",
          "data-series",
          "data-stateGroup",
          "data-states",
          "data-subtotal",
          "data-taxes",
          "data-toggle",
          "data-total",
          "data-unit",
          "data-url",
          "id=\"btn-currency",
          "id=\"btn-saveGroupItems",
          "id=\"btn-saveItems",
          "id=\"btn-submit",
          "id=\"btnNew",
          "id=\"modalAddGroupItems",
          "id=\"modalAddItems",
          "id=\"modalImage",
          "name=\"currency_invoice",
          "name=\"date_invoice",
          "name=\"entry_id",
          "name=\"entry_type",
          "name=\"image",
          "name=\"invoice",
          "name=\"locationGroup",
          "name=\"locations[]",
          "name=\"material_GroupSelected",
          "name=\"material_selected",
          "name=\"observation",
          "name=\"price_GroupSelected",
          "name=\"price_selected",
          "name=\"purchase_order",
          "name=\"quantity_GroupSelected",
          "name=\"quantity_selected",
          "name=\"series[]",
          "route('dashboard.principal",
          "route('invoice.index"
        ],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/invoice/edit_invoice.js"
        ],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "delete",
        "deleteNew",
        "description",
        "image",
        "locationGroup",
        "locations",
        "price",
        "quantity",
        "series",
        "stateGroup",
        "states",
        "subtotal",
        "taxes",
        "total",
        "unit",
        "url"
      ],
      "names": [
        "csrf-token",
        "currency_invoice",
        "date_invoice",
        "entry_id",
        "entry_type",
        "image",
        "invoice",
        "locationGroup",
        "locations[]",
        "material_GroupSelected",
        "material_selected",
        "observation",
        "price_GroupSelected",
        "price_selected",
        "purchase_order",
        "quantity_GroupSelected",
        "quantity_selected",
        "series[]"
      ],
      "routes": [
        "dashboard.principal",
        "invoice.index"
      ],
      "old_js": [
        "js/invoice/edit_invoice.js"
      ],
      "new_js": []
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/destroy/detail/invoice/"
    ]
  },
  {
    "name": "index.credit.supplier",
    "uri": "dashboard/control/creditos",
    "action": "App\\Http\\Controllers\\SupplierCreditController@indexCredits",
    "views": [
      "credit.indexSupplierCredit"
    ],
    "intro": "483a7858",
    "comparisons": [
      {
        "view": "credit.indexSupplierCredit",
        "file": "resources/views/credit/indexSupplierCredit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-credit-error"
        ],
        "old_js": [
          "js/credit/index_creditSupplier.js",
          "js/credit/permissions.js"
        ],
        "new_js": [
          "js/credit/index_creditSupplier.js",
          "js/credit/permissions.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/credit/index_creditSupplier.js",
        "js/credit/permissions.js"
      ],
      "new_js": [
        "js/credit/index_creditSupplier.js",
        "js/credit/permissions.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "index.invoices.pending",
    "uri": "dashboard/faturas/proveedores/pendientes",
    "action": "App\\Http\\Controllers\\SupplierCreditController@indexInvoicesPending",
    "views": [
      "credit.indexInvoicesPending"
    ],
    "intro": "483a7858",
    "comparisons": [
      {
        "view": "credit.indexInvoicesPending",
        "file": "resources/views/credit/indexInvoicesPending.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-boundary",
          "data-toggle",
          "id=\"btn-new-payment-draft"
        ],
        "old_js": [
          "js/credit/index_invoicesPending.js",
          "js/credit/permissions.js"
        ],
        "new_js": [
          "js/credit/index_invoicesPending.js",
          "js/credit/permissions.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/credit/index_invoicesPending.js",
        "js/credit/permissions.js"
      ],
      "new_js": [
        "js/credit/index_invoicesPending.js",
        "js/credit/permissions.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "paymentDeadline.index",
    "uri": "dashboard/plazos/pagos",
    "action": "App\\Http\\Controllers\\PaymentDeadlineController@index",
    "views": [
      "paymentDeadline.index"
    ],
    "intro": "b3a670b6",
    "comparisons": [
      {
        "view": "paymentDeadline.index",
        "file": "resources/views/paymentDeadline/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/paymentDeadline/index.js"
        ],
        "new_js": [
          "js/paymentDeadline/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/paymentDeadline/index.js"
      ],
      "new_js": [
        "js/paymentDeadline/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "paymentDeadline.create",
    "uri": "dashboard/crear/plazo/pago",
    "action": "App\\Http\\Controllers\\PaymentDeadlineController@create",
    "views": [
      "paymentDeadline.create"
    ],
    "intro": "b3a670b6",
    "comparisons": [
      {
        "view": "paymentDeadline.create",
        "file": "resources/views/paymentDeadline/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/paymentDeadline/create.js"
        ],
        "new_js": [
          "js/paymentDeadline/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/paymentDeadline/create.js"
      ],
      "new_js": [
        "js/paymentDeadline/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "paymentDeadline.edit",
    "uri": "dashboard/editar/plazo/pago/{id}",
    "action": "App\\Http\\Controllers\\PaymentDeadlineController@edit",
    "views": [
      "paymentDeadline.edit"
    ],
    "intro": "b3a670b6",
    "comparisons": [
      {
        "view": "paymentDeadline.edit",
        "file": "resources/views/paymentDeadline/edit.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('quality.index"
        ],
        "added": [
          "route('paymentDeadline.index"
        ],
        "old_js": [
          "js/paymentDeadline/edit.js"
        ],
        "new_js": [
          "js/paymentDeadline/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "quality.index"
      ],
      "old_js": [
        "js/paymentDeadline/edit.js"
      ],
      "new_js": [
        "js/paymentDeadline/edit.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "porcentageQuote.index",
    "uri": "dashboard/porcentajes/cotizaciones",
    "action": "App\\Http\\Controllers\\PorcentageQuoteController@index",
    "views": [
      "porcentagesQuote.index"
    ],
    "intro": "06533c55",
    "comparisons": [
      {
        "view": "porcentagesQuote.index",
        "file": "resources/views/porcentagesQuote/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/porcentagesQuote/index.js"
        ],
        "new_js": [
          "js/porcentagesQuote/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/porcentagesQuote/index.js"
      ],
      "new_js": [
        "js/porcentagesQuote/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "porcentageQuote.create",
    "uri": "dashboard/crear/porcentaje",
    "action": "App\\Http\\Controllers\\PorcentageQuoteController@create",
    "views": [
      "porcentagesQuote.create"
    ],
    "intro": "06533c55",
    "comparisons": [
      {
        "view": "porcentagesQuote.create",
        "file": "resources/views/porcentagesQuote/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/porcentagesQuote/create.js"
        ],
        "new_js": [
          "js/porcentagesQuote/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/porcentagesQuote/create.js"
      ],
      "new_js": [
        "js/porcentagesQuote/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "porcentageQuote.edit",
    "uri": "dashboard/editar/porcentaje/cotizacion/{id}",
    "action": "App\\Http\\Controllers\\PorcentageQuoteController@edit",
    "views": [
      "porcentagesQuote.edit"
    ],
    "intro": "06533c55",
    "comparisons": [
      {
        "view": "porcentagesQuote.edit",
        "file": "resources/views/porcentagesQuote/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/porcentagesQuote/edit.js"
        ],
        "new_js": [
          "js/porcentagesQuote/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/porcentagesQuote/edit.js"
      ],
      "new_js": [
        "js/porcentagesQuote/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "report.invoice.finance",
    "uri": "dashboard/reporte/faturas/finanzas",
    "action": "App\\Http\\Controllers\\InvoiceController@reportInvoiceFinance",
    "views": [
      "invoice.report_invoice"
    ],
    "intro": "fa8f7ede",
    "comparisons": [
      {
        "view": "invoice.report_invoice",
        "file": "resources/views/invoice/report_invoice.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-description",
          "data-dismiss",
          "data-price",
          "data-quantity",
          "data-subtotal",
          "data-taxes",
          "data-total",
          "data-unit",
          "data-url",
          "id=\"btn-export",
          "id=\"modalDelete",
          "id=\"modalImage",
          "id=\"modalItems",
          "name=\"material_id",
          "route('dashboard.principal",
          "route('invoice.create",
          "route('material.destroy"
        ],
        "added": [
          "data-read-url",
          "data-table",
          "route('invoice.index",
          "route('invoice.index.annulled"
        ],
        "old_js": [
          "js/invoice/report_invoice.js"
        ],
        "new_js": [
          "js/invoice/finance-readonly.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "code",
        "column",
        "delete",
        "description",
        "details",
        "id",
        "image",
        "item",
        "location",
        "price",
        "quantity",
        "series",
        "src",
        "state",
        "subtotal",
        "taxes",
        "total",
        "unit",
        "url"
      ],
      "names": [
        "csrf-token",
        "material_id"
      ],
      "routes": [
        "dashboard.principal",
        "invoice.create",
        "material.destroy"
      ],
      "old_js": [
        "js/invoice/report_invoice.js"
      ],
      "new_js": [
        "js/invoice/finance-readonly.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/destroy/total/invoice/",
      "/dashboard/get/invoice/by/id/",
      "/dashboard/get/json/invoices/finance",
      "/dashboard/get/service/by/id/"
    ]
  },
  {
    "name": "report.invoice.finance.sin.orden",
    "uri": "dashboard/reporte/faturas/finanzas/sin/orden",
    "action": "App\\Http\\Controllers\\InvoiceController@reportInvoiceFinanceSinOrden",
    "views": [
      "invoice.report_invoice_sin_orden"
    ],
    "intro": "fa8f7ede",
    "comparisons": [
      {
        "view": "invoice.report_invoice_sin_orden",
        "file": "resources/views/invoice/report_invoice_sin_orden.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-description",
          "data-dismiss",
          "data-price",
          "data-quantity",
          "data-subtotal",
          "data-taxes",
          "data-total",
          "data-unit",
          "data-url",
          "id=\"btn-export",
          "id=\"modalDelete",
          "id=\"modalImage",
          "id=\"modalItems",
          "name=\"material_id",
          "route('dashboard.principal",
          "route('invoice.create",
          "route('material.destroy"
        ],
        "added": [
          "data-read-url",
          "data-table",
          "route('invoice.index",
          "route('invoice.index.annulled"
        ],
        "old_js": [
          "js/invoice/report_invoice_sin_orden.js"
        ],
        "new_js": [
          "js/invoice/finance-readonly.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "code",
        "column",
        "delete",
        "description",
        "details",
        "id",
        "image",
        "item",
        "location",
        "price",
        "quantity",
        "series",
        "src",
        "state",
        "subtotal",
        "taxes",
        "total",
        "unit",
        "url"
      ],
      "names": [
        "csrf-token",
        "material_id"
      ],
      "routes": [
        "dashboard.principal",
        "invoice.create",
        "material.destroy"
      ],
      "old_js": [
        "js/invoice/report_invoice_sin_orden.js"
      ],
      "new_js": [
        "js/invoice/finance-readonly.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/destroy/total/invoice/",
      "/dashboard/get/invoice/by/id/",
      "/dashboard/get/json/invoices/finance/sin/orden",
      "/dashboard/get/service/by/id/"
    ]
  },
  {
    "name": "worker.index",
    "uri": "dashboard/colaboradores",
    "action": "App\\Http\\Controllers\\WorkerController@index",
    "views": [
      "worker.index"
    ],
    "intro": "5a79bc83",
    "comparisons": [
      {
        "view": "worker.index",
        "file": "resources/views/worker/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table",
          "data-toggle"
        ],
        "old_js": [
          "js/worker/index.js"
        ],
        "new_js": [
          "js/worker/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/worker/index.js"
      ],
      "new_js": [
        "js/worker/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "worker.create",
    "uri": "dashboard/registrar/colaborador",
    "action": "App\\Http\\Controllers\\WorkerController@create",
    "views": [
      "worker.create"
    ],
    "intro": "5a79bc83",
    "comparisons": [
      {
        "view": "worker.create",
        "file": "resources/views/worker/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/worker/create.js"
        ],
        "new_js": [
          "js/worker/create.js"
        ],
        "same_js": true,
        "includes": [
          "worker._systemUser"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/worker/create.js"
      ],
      "new_js": [
        "js/worker/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "worker.edit",
    "uri": "dashboard/editar/colaborador/{id}",
    "action": "App\\Http\\Controllers\\WorkerController@edit",
    "views": [
      "worker.edit"
    ],
    "intro": "5a79bc83",
    "comparisons": [
      {
        "view": "worker.edit",
        "file": "resources/views/worker/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/worker/edit.js"
        ],
        "new_js": [
          "js/worker/edit.js"
        ],
        "same_js": true,
        "includes": [
          "worker._systemUser"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/worker/edit.js"
      ],
      "new_js": [
        "js/worker/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "worker.enable",
    "uri": "dashboard/habilitar/colaborador",
    "action": "App\\Http\\Controllers\\WorkerController@indexEnable",
    "views": [
      "worker.indexEnable"
    ],
    "intro": "996a31a4",
    "comparisons": [
      {
        "view": "worker.indexEnable",
        "file": "resources/views/worker/indexEnable.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table",
          "data-toggle"
        ],
        "old_js": [
          "js/worker/indexEnable.js"
        ],
        "new_js": [
          "js/worker/indexEnable.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/worker/indexEnable.js"
      ],
      "new_js": [
        "js/worker/indexEnable.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "civilStatuses.index",
    "uri": "dashboard/estado/civil",
    "action": "App\\Http\\Controllers\\CivilStatusController@index",
    "views": [
      "civilStatus.index"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "civilStatus.index",
        "file": "resources/views/civilStatus/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "route('civilStatuses.deleted"
        ],
        "old_js": [
          "js/civilStatus/index.js"
        ],
        "new_js": [
          "js/civilStatus/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/civilStatus/index.js"
      ],
      "new_js": [
        "js/civilStatus/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "civilStatuses.create",
    "uri": "dashboard/crear/estado/civil",
    "action": "App\\Http\\Controllers\\CivilStatusController@create",
    "views": [
      "civilStatus.create"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "civilStatus.create",
        "file": "resources/views/civilStatus/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/civilStatus/create.js"
        ],
        "new_js": [
          "js/civilStatus/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/civilStatus/create.js"
      ],
      "new_js": [
        "js/civilStatus/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "civilStatuses.edit",
    "uri": "dashboard/editar/estado/civil/{id}",
    "action": "App\\Http\\Controllers\\CivilStatusController@edit",
    "views": [
      "civilStatus.edit"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "civilStatus.edit",
        "file": "resources/views/civilStatus/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/civilStatus/create.js"
        ],
        "new_js": [
          "js/civilStatus/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/civilStatus/create.js"
      ],
      "new_js": [
        "js/civilStatus/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "civilStatuses.deleted",
    "uri": "dashboard/estado/civil/eliminados",
    "action": "App\\Http\\Controllers\\CivilStatusController@indexDeleted",
    "views": [
      "civilStatus.indexDeleted"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "civilStatus.indexDeleted",
        "file": "resources/views/civilStatus/indexDeleted.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/civilStatus/indexDeleted.js"
        ],
        "new_js": [
          "js/civilStatus/indexDeleted.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/civilStatus/indexDeleted.js"
      ],
      "new_js": [
        "js/civilStatus/indexDeleted.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "relationship.index",
    "uri": "dashboard/parentescos",
    "action": "App\\Http\\Controllers\\RelationshipController@index",
    "views": [
      "relationship.index"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "relationship.index",
        "file": "resources/views/relationship/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/relationship/index.js"
        ],
        "new_js": [
          "js/relationship/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/relationship/index.js"
      ],
      "new_js": [
        "js/relationship/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "relationship.create",
    "uri": "dashboard/crear/parentesco",
    "action": "App\\Http\\Controllers\\RelationshipController@create",
    "views": [
      "relationship.create"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "relationship.create",
        "file": "resources/views/relationship/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/relationship/create.js"
        ],
        "new_js": [
          "js/relationship/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/relationship/create.js"
      ],
      "new_js": [
        "js/relationship/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "relationship.edit",
    "uri": "dashboard/editar/parentesco/{id}",
    "action": "App\\Http\\Controllers\\RelationshipController@edit",
    "views": [
      "relationship.edit"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "relationship.edit",
        "file": "resources/views/relationship/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/relationship/edit.js"
        ],
        "new_js": [
          "js/relationship/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/relationship/edit.js"
      ],
      "new_js": [
        "js/relationship/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "workFunctions.index",
    "uri": "dashboard/cargos",
    "action": "App\\Http\\Controllers\\WorkFunctionController@index",
    "views": [
      "workFunction.index"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "workFunction.index",
        "file": "resources/views/workFunction/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "route('workFunctions.deleted"
        ],
        "old_js": [
          "js/workFunction/index.js"
        ],
        "new_js": [
          "js/workFunction/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/workFunction/index.js"
      ],
      "new_js": [
        "js/workFunction/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "workFunctions.create",
    "uri": "dashboard/crear/cargo",
    "action": "App\\Http\\Controllers\\WorkFunctionController@create",
    "views": [
      "workFunction.create"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "workFunction.create",
        "file": "resources/views/workFunction/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/workFunction/create.js"
        ],
        "new_js": [
          "js/workFunction/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/workFunction/create.js"
      ],
      "new_js": [
        "js/workFunction/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "workFunctions.edit",
    "uri": "dashboard/editar/cargo/{id}",
    "action": "App\\Http\\Controllers\\WorkFunctionController@edit",
    "views": [
      "workFunction.edit"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "workFunction.edit",
        "file": "resources/views/workFunction/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/workFunction/edit.js"
        ],
        "new_js": [
          "js/workFunction/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/workFunction/edit.js"
      ],
      "new_js": [
        "js/workFunction/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "workFunctions.deleted",
    "uri": "dashboard/cargos/eliminados",
    "action": "App\\Http\\Controllers\\WorkFunctionController@indexDeleted",
    "views": [
      "workFunction.indexDeleted"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "workFunction.indexDeleted",
        "file": "resources/views/workFunction/indexDeleted.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/workFunction/indexDeleted.js"
        ],
        "new_js": [
          "js/workFunction/indexDeleted.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/workFunction/indexDeleted.js"
      ],
      "new_js": [
        "js/workFunction/indexDeleted.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "pensionSystems.index",
    "uri": "dashboard/sistemas/pension",
    "action": "App\\Http\\Controllers\\PensionSystemController@index",
    "views": [
      "pensionSystem.index"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "pensionSystem.index",
        "file": "resources/views/pensionSystem/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "route('pensionSystems.deleted"
        ],
        "old_js": [
          "js/pensionSystem/index.js"
        ],
        "new_js": [
          "js/pensionSystem/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/pensionSystem/index.js"
      ],
      "new_js": [
        "js/pensionSystem/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "pensionSystems.create",
    "uri": "dashboard/crear/sistema/pension",
    "action": "App\\Http\\Controllers\\PensionSystemController@create",
    "views": [
      "pensionSystem.create"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "pensionSystem.create",
        "file": "resources/views/pensionSystem/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/pensionSystem/create.js"
        ],
        "new_js": [
          "js/pensionSystem/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/pensionSystem/create.js"
      ],
      "new_js": [
        "js/pensionSystem/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "pensionSystems.edit",
    "uri": "dashboard/editar/sistema/pension/{id}",
    "action": "App\\Http\\Controllers\\PensionSystemController@edit",
    "views": [
      "pensionSystem.edit"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "pensionSystem.edit",
        "file": "resources/views/pensionSystem/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/pensionSystem/edit.js"
        ],
        "new_js": [
          "js/pensionSystem/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/pensionSystem/edit.js"
      ],
      "new_js": [
        "js/pensionSystem/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "pensionSystems.deleted",
    "uri": "dashboard/sistemas/pension/eliminados",
    "action": "App\\Http\\Controllers\\PensionSystemController@indexDeleted",
    "views": [
      "pensionSystem.indexDeleted"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "pensionSystem.indexDeleted",
        "file": "resources/views/pensionSystem/indexDeleted.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('workFunctions.create"
        ],
        "added": [
          "route('pensionSystems.create"
        ],
        "old_js": [
          "js/pensionSystem/indexDeleted.js"
        ],
        "new_js": [
          "js/pensionSystem/indexDeleted.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "workFunctions.create"
      ],
      "old_js": [
        "js/pensionSystem/indexDeleted.js"
      ],
      "new_js": [
        "js/pensionSystem/indexDeleted.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "holiday.index",
    "uri": "dashboard/feriados",
    "action": "App\\Http\\Controllers\\HolidayController@index",
    "views": [
      "holiday.index"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "holiday.index",
        "file": "resources/views/holiday/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "route('holiday.create"
        ],
        "old_js": [
          "js/holiday/index.js"
        ],
        "new_js": [
          "js/holiday/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/holiday/index.js"
      ],
      "new_js": [
        "js/holiday/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "holiday.create",
    "uri": "dashboard/crear/feriado",
    "action": "App\\Http\\Controllers\\HolidayController@create",
    "views": [
      "holiday.create"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "holiday.create",
        "file": "resources/views/holiday/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/holiday/create.js"
        ],
        "new_js": [
          "js/holiday/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/holiday/create.js"
      ],
      "new_js": [
        "js/holiday/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "holiday.edit",
    "uri": "dashboard/editar/feriado/{id}",
    "action": "App\\Http\\Controllers\\HolidayController@edit",
    "views": [
      "holiday.edit"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "holiday.edit",
        "file": "resources/views/holiday/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/holiday/edit.js"
        ],
        "new_js": [
          "js/holiday/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/holiday/edit.js"
      ],
      "new_js": [
        "js/holiday/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "reasonSuspension.index",
    "uri": "dashboard/razones/suspension",
    "action": "App\\Http\\Controllers\\ReasonSuspensionController@index",
    "views": [
      "reasonSuspension.index"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "reasonSuspension.index",
        "file": "resources/views/reasonSuspension/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/reasonSuspension/index.js"
        ],
        "new_js": [
          "js/reasonSuspension/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/reasonSuspension/index.js"
      ],
      "new_js": [
        "js/reasonSuspension/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "reasonSuspension.create",
    "uri": "dashboard/crear/razon/suspension",
    "action": "App\\Http\\Controllers\\ReasonSuspensionController@create",
    "views": [
      "reasonSuspension.create"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "reasonSuspension.create",
        "file": "resources/views/reasonSuspension/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/reasonSuspension/create.js"
        ],
        "new_js": [
          "js/reasonSuspension/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/reasonSuspension/create.js"
      ],
      "new_js": [
        "js/reasonSuspension/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "reasonSuspension.edit",
    "uri": "dashboard/editar/razon/suspension/{id}",
    "action": "App\\Http\\Controllers\\ReasonSuspensionController@edit",
    "views": [
      "reasonSuspension.edit"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "reasonSuspension.edit",
        "file": "resources/views/reasonSuspension/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/reasonSuspension/edit.js"
        ],
        "new_js": [
          "js/reasonSuspension/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/reasonSuspension/edit.js"
      ],
      "new_js": [
        "js/reasonSuspension/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "areaWorker.index",
    "uri": "dashboard/areas/empresa",
    "action": "App\\Http\\Controllers\\AreaWorkerController@index",
    "views": [
      "areaWorker.index"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "areaWorker.index",
        "file": "resources/views/areaWorker/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/areaWorker/index.js"
        ],
        "new_js": [
          "js/areaWorker/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/areaWorker/index.js"
      ],
      "new_js": [
        "js/areaWorker/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "areaWorker.create",
    "uri": "dashboard/crear/area/empresa",
    "action": "App\\Http\\Controllers\\AreaWorkerController@create",
    "views": [
      "areaWorker.create"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "areaWorker.create",
        "file": "resources/views/areaWorker/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/areaWorker/create.js"
        ],
        "new_js": [
          "js/areaWorker/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/areaWorker/create.js"
      ],
      "new_js": [
        "js/areaWorker/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "areaWorker.edit",
    "uri": "dashboard/editar/area/empresa/{id}",
    "action": "App\\Http\\Controllers\\AreaWorkerController@edit",
    "views": [
      "areaWorker.edit"
    ],
    "intro": "6d8652a9",
    "comparisons": [
      {
        "view": "areaWorker.edit",
        "file": "resources/views/areaWorker/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/areaWorker/create.js"
        ],
        "new_js": [
          "js/areaWorker/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/areaWorker/create.js"
      ],
      "new_js": [
        "js/areaWorker/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "order.purchase.finance.index",
    "uri": "dashboard/ordenes/compra/finanzas",
    "action": "App\\Http\\Controllers\\OrderPurchaseFinanceController@indexOrderPurchaseFinance",
    "views": [
      "orderPurchaseFinance.indexOrderFinance"
    ],
    "intro": "483a7858",
    "comparisons": [
      {
        "view": "orderPurchaseFinance.indexOrderFinance",
        "file": "resources/views/orderPurchaseFinance/indexOrderFinance.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('dashboard.principal",
          "route('order.purchase.finance.create",
          "route('order.purchase.finance.index"
        ],
        "added": [
          "data-table",
          "data-url"
        ],
        "old_js": [
          "js/orderPurchaseFinance/indexOrderFinance.js"
        ],
        "new_js": [
          "js/orderPurchaseFinance/list-readonly.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "delete",
        "name"
      ],
      "names": [
        "csrf-token"
      ],
      "routes": [
        "dashboard.principal",
        "order.purchase.finance.create",
        "order.purchase.finance.index"
      ],
      "old_js": [
        "js/orderPurchaseFinance/indexOrderFinance.js"
      ],
      "new_js": [
        "js/orderPurchaseFinance/list-readonly.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/order/finance",
      "/dashboard/destroy/order/purchase/finance/"
    ]
  },
  {
    "name": "order.purchase.finance.create",
    "uri": "dashboard/crear/orden/compra/finanzas",
    "action": "App\\Http\\Controllers\\OrderPurchaseFinanceController@createOrderPurchaseFinance",
    "views": [
      "orderPurchaseFinance.createOrderFinance"
    ],
    "intro": "744d28b8",
    "comparisons": [
      {
        "view": "orderPurchaseFinance.createOrderFinance",
        "file": "resources/views/orderPurchaseFinance/createOrderFinance.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-card-widget",
          "data-toggle"
        ],
        "added": [],
        "old_js": [
          "js/orderPurchaseFinance/createOrderFinance.js"
        ],
        "new_js": [
          "js/orderPurchaseFinance/createOrderFinance.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/orderPurchaseFinance/createOrderFinance.js"
      ],
      "new_js": [
        "js/orderPurchaseFinance/createOrderFinance.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": null,
    "uri": "dashboard/editar/orden/compra/finanza/{id}",
    "action": "App\\Http\\Controllers\\OrderPurchaseFinanceController@editOrderPurchaseFinance",
    "views": [
      "orderPurchaseFinance.editOrderPurchaseFinance"
    ],
    "intro": "744d28b8",
    "comparisons": [
      {
        "view": "orderPurchaseFinance.editOrderPurchaseFinance",
        "file": "resources/views/orderPurchaseFinance/editOrderPurchaseFinance.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-card-widget",
          "data-toggle"
        ],
        "added": [],
        "old_js": [
          "js/orderPurchaseFinance/editOrderFinance.js"
        ],
        "new_js": [
          "js/orderPurchaseFinance/editOrderFinance.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/orderPurchaseFinance/editOrderFinance.js"
      ],
      "new_js": [
        "js/orderPurchaseFinance/editOrderFinance.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "order.purchase.finance.delete",
    "uri": "dashboard/ordenes/compra/finanzas/eliminadas",
    "action": "App\\Http\\Controllers\\OrderPurchaseFinanceController@indexOrderPurchaseFinanceDelete",
    "views": [
      "orderPurchaseFinance.indexFinanceDeleted"
    ],
    "intro": "744d28b8",
    "comparisons": [
      {
        "view": "orderPurchaseFinance.indexFinanceDeleted",
        "file": "resources/views/orderPurchaseFinance/indexFinanceDeleted.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('dashboard.principal"
        ],
        "added": [
          "data-detail-url",
          "data-mode",
          "data-table",
          "data-url",
          "route('order.purchase.finance.index"
        ],
        "old_js": [
          "js/orderPurchaseFinance/indexDeleted.js"
        ],
        "new_js": [
          "js/orderPurchaseFinance/history-readonly.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "name",
        "restore"
      ],
      "names": [
        "csrf-token"
      ],
      "routes": [
        "dashboard.principal"
      ],
      "old_js": [
        "js/orderPurchaseFinance/indexDeleted.js"
      ],
      "new_js": [
        "js/orderPurchaseFinance/history-readonly.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/order/finance/delete",
      "/dashboard/restore/order/purchase/finance/delete/"
    ]
  },
  {
    "name": null,
    "uri": "dashboard/ver/orden/compra/finanzas/eliminada/{id}",
    "action": "App\\Http\\Controllers\\OrderPurchaseFinanceController@showOrderPurchaseFinanceDelete",
    "views": [
      "orderPurchaseFinance.showOrderPurchaseDeleteFinance"
    ],
    "intro": "744d28b8",
    "comparisons": [
      {
        "view": "orderPurchaseFinance.showOrderPurchaseDeleteFinance",
        "file": "resources/views/orderPurchaseFinance/showOrderPurchaseDeleteFinance.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-bootstrap-switch",
          "data-card-widget",
          "data-code",
          "data-id",
          "data-quantity",
          "data-toggle",
          "data-total",
          "data-url",
          "id=\"btn-currency",
          "name=\"currency_order",
          "name=\"date_arrival",
          "name=\"date_order",
          "name=\"observation",
          "name=\"order_id",
          "name=\"purchase_condition",
          "name=\"purchase_order",
          "name=\"quote_id",
          "name=\"quote_supplier",
          "route('dashboard.principal",
          "route('order.purchase.finance.index"
        ],
        "added": [
          "data-table",
          "route('order.purchase.finance.delete",
          "route('order.purchase.finance.list.regularize"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "code",
        "id",
        "quantity",
        "total",
        "url"
      ],
      "names": [
        "currency_order",
        "date_arrival",
        "date_order",
        "observation",
        "order_id",
        "purchase_condition",
        "purchase_order",
        "quote_id",
        "quote_supplier"
      ],
      "routes": [
        "dashboard.principal",
        "order.purchase.finance.index"
      ],
      "old_js": [],
      "new_js": []
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "order.purchase.finance.list.regularize",
    "uri": "dashboard/ordenes/compra/finanzas/regularizadas",
    "action": "App\\Http\\Controllers\\OrderPurchaseFinanceController@indexOrderPurchaseFinanceRegularize",
    "views": [
      "orderPurchaseFinance.indexRegularizeFinance"
    ],
    "intro": "744d28b8",
    "comparisons": [
      {
        "view": "orderPurchaseFinance.indexRegularizeFinance",
        "file": "resources/views/orderPurchaseFinance/indexRegularizeFinance.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('dashboard.principal"
        ],
        "added": [
          "data-detail-url",
          "data-mode",
          "data-table",
          "data-url",
          "route('order.purchase.finance.index"
        ],
        "old_js": [
          "js/orderPurchaseFinance/indexRegularize.js"
        ],
        "new_js": [
          "js/orderPurchaseFinance/history-readonly.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "dashboard.principal"
      ],
      "old_js": [
        "js/orderPurchaseFinance/indexRegularize.js"
      ],
      "new_js": [
        "js/orderPurchaseFinance/history-readonly.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/all/order/purchase/finance/regularize"
    ]
  },
  {
    "name": "bill.index",
    "uri": "dashboard/tipos/gastos",
    "action": "App\\Http\\Controllers\\BillController@index",
    "views": [
      "bill.index"
    ],
    "intro": "9670a98b",
    "comparisons": [
      {
        "view": "bill.index",
        "file": "resources/views/bill/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/bill/index.js"
        ],
        "new_js": [
          "js/bill/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/bill/index.js"
      ],
      "new_js": [
        "js/bill/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "bill.create",
    "uri": "dashboard/crear/tipo/gasto",
    "action": "App\\Http\\Controllers\\BillController@create",
    "views": [
      "bill.create"
    ],
    "intro": "9670a98b",
    "comparisons": [
      {
        "view": "bill.create",
        "file": "resources/views/bill/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/bill/create.js"
        ],
        "new_js": [
          "js/bill/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/bill/create.js"
      ],
      "new_js": [
        "js/bill/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "bill.edit",
    "uri": "dashboard/editar/tipo/gasto/{id}",
    "action": "App\\Http\\Controllers\\BillController@edit",
    "views": [
      "bill.edit"
    ],
    "intro": "9670a98b",
    "comparisons": [
      {
        "view": "bill.edit",
        "file": "resources/views/bill/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/bill/edit.js"
        ],
        "new_js": [
          "js/bill/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/bill/edit.js"
      ],
      "new_js": [
        "js/bill/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "expense.create",
    "uri": "dashboard/crear/gasto",
    "action": "App\\Http\\Controllers\\ExpenseController@create",
    "views": [
      "expense.create"
    ],
    "intro": "d43ca2f7",
    "comparisons": [
      {
        "view": "expense.create",
        "file": "resources/views/expense/create.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('expense.index"
        ],
        "added": [],
        "old_js": [
          "js/expense/create.js"
        ],
        "new_js": [
          "js/expense/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "expense.index"
      ],
      "old_js": [
        "js/expense/create.js"
      ],
      "new_js": [
        "js/expense/create.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "supplier.accounts.index",
    "uri": "dashboard/registrar/cuentas/proveedores/{supplier_id}",
    "action": "App\\Http\\Controllers\\SupplierAccountController@index",
    "views": [
      "supplierAccount.index"
    ],
    "intro": "4b546d4c",
    "comparisons": [
      {
        "view": "supplierAccount.index",
        "file": "resources/views/supplierAccount/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-card-widget",
          "data-toggle"
        ],
        "added": [
          "data-account-row"
        ],
        "old_js": [
          "js/supplierAccount/create.js"
        ],
        "new_js": [
          "js/supplierAccount/create.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/supplierAccount/create.js"
      ],
      "new_js": [
        "js/supplierAccount/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "categoryEquipment.index",
    "uri": "dashboard/categorias/equipos",
    "action": "App\\Http\\Controllers\\CategoryEquipmentController@index",
    "views": [
      "categoryEquipment.index"
    ],
    "intro": "3636c1f1",
    "comparisons": [
      {
        "view": "categoryEquipment.index",
        "file": "resources/views/categoryEquipment/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "name=\"search",
          "route('categoryEquipment.destroy",
          "route('categoryEquipment.eliminated",
          "route('categoryEquipment.update"
        ],
        "old_js": [
          "js/categoryEquipment/create.js",
          "js/categoryEquipment/delete.js",
          "js/categoryEquipment/edit.js",
          "js/categoryEquipment/index.js"
        ],
        "new_js": [
          "js/categoryEquipment/create.js",
          "js/categoryEquipment/delete.js",
          "js/categoryEquipment/edit.js",
          "js/categoryEquipment/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/categoryEquipment/create.js",
        "js/categoryEquipment/delete.js",
        "js/categoryEquipment/edit.js",
        "js/categoryEquipment/index.js"
      ],
      "new_js": [
        "js/categoryEquipment/create.js",
        "js/categoryEquipment/delete.js",
        "js/categoryEquipment/edit.js",
        "js/categoryEquipment/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": [
      "actualizar/",
      "equiposxeliminar/"
    ]
  },
  {
    "name": "categoryEquipment.eliminated",
    "uri": "dashboard/categorias/equiposeliminados",
    "action": "App\\Http\\Controllers\\CategoryEquipmentController@eliminated",
    "views": [
      "categoryEquipment.eliminated"
    ],
    "intro": "3636c1f1",
    "comparisons": [
      {
        "view": "categoryEquipment.eliminated",
        "file": "resources/views/categoryEquipment/eliminated.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-url",
          "route('categoryEquipment.restore"
        ],
        "old_js": [
          "js/categoryEquipment/indexEliminated.js",
          "js/categoryEquipment/restore.js"
        ],
        "new_js": [
          "js/categoryEquipment/indexEliminated.js",
          "js/categoryEquipment/restore.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/categoryEquipment/indexEliminated.js",
        "js/categoryEquipment/restore.js"
      ],
      "new_js": [
        "js/categoryEquipment/indexEliminated.js",
        "js/categoryEquipment/restore.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": [
      "equiposxrestaurar/"
    ]
  },
  {
    "name": "stocks.files.index",
    "uri": "dashboard/importar/archivos/stocks/materiales",
    "action": "App\\Http\\Controllers\\UploadFilesController@showUploadFilesStocksMaterials",
    "views": [
      "files.stockFiles"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "files.stockFiles",
        "file": "resources/views/files/stockFiles.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-card-widget",
          "data-dismiss",
          "route('stocks.files.index"
        ],
        "added": [
          "data-browse",
          "data-stock-file-label"
        ],
        "old_js": [
          "js/files/stockFiles.js"
        ],
        "new_js": [
          "js/files/stockFiles.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "stocks.files.index"
      ],
      "old_js": [
        "js/files/stockFiles.js"
      ],
      "new_js": [
        "js/files/stockFiles.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "puntoVenta.index",
    "uri": "dashboard/crear/venta/V1",
    "action": "App\\Http\\Controllers\\PuntoVentaController@index",
    "views": [
      "puntoVenta.index"
    ],
    "intro": "64d6f892",
    "comparisons": [
      {
        "view": "puntoVenta.index",
        "file": "resources/views/puntoVenta/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-card-widget"
        ],
        "added": [
          "data-pos-image-fallback",
          "data-pos-local-stock",
          "data-pos-presentation",
          "data-pos-sku",
          "data-size"
        ],
        "old_js": [
          "js/puntoVenta/catalog-stock.js",
          "js/puntoVenta/checkout-context.js",
          "js/puntoVenta/index.js"
        ],
        "new_js": [
          "js/puntoVenta/catalog-stock.js",
          "js/puntoVenta/checkout-context.js",
          "js/puntoVenta/index.js"
        ],
        "same_js": false,
        "includes": [
          "puntoVenta._catalogContext",
          "puntoVenta._stockLookup"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/puntoVenta/catalog-stock.js",
        "js/puntoVenta/checkout-context.js",
        "js/puntoVenta/index.js"
      ],
      "new_js": [
        "js/puntoVenta/catalog-stock.js",
        "js/puntoVenta/checkout-context.js",
        "js/puntoVenta/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "puntoVenta.indexV2",
    "uri": "dashboard/crear/venta/V2",
    "action": "App\\Http\\Controllers\\PuntoVentaController@indexV2",
    "views": [
      "puntoVenta.indexV2"
    ],
    "intro": "64d6f892",
    "comparisons": [
      {
        "view": "puntoVenta.indexV2",
        "file": "resources/views/puntoVenta/indexV2.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-card-widget"
        ],
        "added": [
          "data-pos-image-fallback",
          "data-pos-local-stock",
          "data-pos-presentation",
          "data-pos-sku",
          "data-size"
        ],
        "old_js": [
          "js/puntoVenta/catalog-stock.js",
          "js/puntoVenta/checkout-context.js",
          "js/puntoVenta/indexV2.js"
        ],
        "new_js": [
          "js/puntoVenta/catalog-stock.js",
          "js/puntoVenta/checkout-context.js",
          "js/puntoVenta/indexV2.js"
        ],
        "same_js": false,
        "includes": [
          "puntoVenta._catalogContext",
          "puntoVenta._stockLookup"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/puntoVenta/catalog-stock.js",
        "js/puntoVenta/checkout-context.js",
        "js/puntoVenta/indexV2.js"
      ],
      "new_js": [
        "js/puntoVenta/catalog-stock.js",
        "js/puntoVenta/checkout-context.js",
        "js/puntoVenta/indexV2.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "puntoVenta.list",
    "uri": "dashboard/listado/ventas",
    "action": "App\\Http\\Controllers\\PuntoVentaController@listar",
    "views": [
      "puntoVenta.list"
    ],
    "intro": "ffe0974d",
    "comparisons": [
      {
        "view": "puntoVenta.list",
        "file": "resources/views/puntoVenta/list.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-active",
          "data-anular",
          "data-buttons",
          "data-code",
          "data-consultar_anulacion",
          "data-consultar_nota_credito",
          "data-created_by_name",
          "data-currency",
          "data-customer",
          "data-date",
          "data-dispatch_status",
          "data-estado_comprobante",
          "data-generar_comprobante",
          "data-generar_nota_credito",
          "data-generar_nota_credito_parcial",
          "data-id",
          "data-is_deferred",
          "data-item",
          "data-itemable-group",
          "data-pagos_parciales",
          "data-placement",
          "data-print_anulacion_cdr",
          "data-print_anulacion_pdf",
          "data-print_anulacion_xml",
          "data-print_recibo",
          "data-recuperar_archivos_nubefact",
          "data-sale_id",
          "data-tipo_pago",
          "data-toggle",
          "data-total",
          "data-type_document",
          "data-ver_detalles",
          "data-ver_nc_parcial_pdf",
          "id=\"btn-confirm-nc-partial-items",
          "id=\"btn-download",
          "id=\"btn-search-nc-partial-item",
          "id=\"btn-submit-nc-partial",
          "id=\"btnBuscarGcDni",
          "id=\"btnBuscarGcRuc",
          "id=\"btnConfirmarGenerarComprobante",
          "id=\"btnDescargarPDF",
          "id=\"btnGenerarComprobante",
          "id=\"btnGuardarDatos",
          "id=\"btnGuardarPagoParcial",
          "id=\"modalFacturador",
          "id=\"modalFacturadorLabel",
          "id=\"modalGenerarComprobante",
          "id=\"modalNcPartialItems",
          "id=\"modalNotaCreditoParcial",
          "id=\"modalPagosParciales",
          "name=\"direccion_fiscal",
          "name=\"dni",
          "name=\"email_invoice_boleta",
          "name=\"email_invoice_factura",
          "name=\"invoice_type",
          "name=\"name",
          "name=\"order_id",
          "name=\"razon_social",
          "name=\"ruc",
          "route('puntoVenta.list",
          "route('referral.guide.create"
        ],
        "added": [
          "data-branch",
          "data-company",
          "data-permissions",
          "data-table",
          "data-tenant"
        ],
        "old_js": [
          "js/puntoVenta/list-access.js",
          "js/puntoVenta/list.js"
        ],
        "new_js": [
          "js/puntoVenta/list-readonly.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "active",
        "anular",
        "code",
        "consultar_anulacion",
        "consultar_nota_credito",
        "created_by_name",
        "credit_note_id",
        "currency",
        "customer",
        "date",
        "descartar_error_comprobante",
        "dispatch_status",
        "eliminar_pago_parcial",
        "facturador",
        "free_sale",
        "generar_comprobante",
        "generar_nota_credito",
        "generar_nota_credito_parcial",
        "imprimir_comprobante",
        "is_deferred",
        "is_itemable",
        "item-code",
        "item-list",
        "item-row",
        "item_code",
        "item_id",
        "itemable-group",
        "max",
        "message",
        "nc-partial-row",
        "pagos_parciales",
        "price",
        "print_anulacion_cdr",
        "print_anulacion_pdf",
        "print_anulacion_xml",
        "print_recibo",
        "recuperar_archivos_nubefact",
        "required_qty",
        "response_code",
        "sale_detail_id",
        "sale_id",
        "selected-counter",
        "tipo_pago",
        "total",
        "type_document",
        "ver_error_nota_credito",
        "ver_nc_parcial_pdf",
        "ver_solucion_sunat_error"
      ],
      "names": [
        "csrf-token",
        "direccion_fiscal",
        "dni",
        "email_invoice_boleta",
        "email_invoice_factura",
        "invoice_type",
        "name",
        "order_id",
        "razon_social",
        "ruc"
      ],
      "routes": [
        "puntoVenta.list",
        "referral.guide.create"
      ],
      "old_js": [
        "js/puntoVenta/list-access.js",
        "js/puntoVenta/list.js"
      ],
      "new_js": [
        "js/puntoVenta/list-readonly.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/anular/order/",
      "/dashboard/consultar/nota-credito/",
      "/dashboard/customer/decolecta/${documento}",
      "/dashboard/facturador/generar",
      "/dashboard/generar/nota-credito/parcial/",
      "/dashboard/generar/nota-credito/total/",
      "/dashboard/nota-credito/parcial/data/",
      "/dashboard/sale/partial-payment/",
      "/dashboard/sale/partial-payment/store",
      "/dashboard/sale/update-dispatch-status",
      "/dashboard/sales/${orderId}/details",
      "/dashboard/sales/update-invoice-data",
      "/dashboard/ventas/",
      "/print/order/${orderId}"
    ]
  },
  {
    "name": "puntoVenta.listError",
    "uri": "dashboard/listado/ventas/error",
    "action": "App\\Http\\Controllers\\PuntoVentaController@listarErrors",
    "views": [
      "puntoVenta.listError"
    ],
    "intro": "b52c3cd8",
    "comparisons": [
      {
        "view": "puntoVenta.listError",
        "file": "resources/views/puntoVenta/listError.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-active",
          "data-buttons",
          "data-code",
          "data-comprobante",
          "data-created_by_name",
          "data-currency",
          "data-customer",
          "data-date",
          "data-descartar_error_comprobante",
          "data-id",
          "data-is_deferred",
          "data-item",
          "data-print_recibo",
          "data-reintentar_comprobante",
          "data-sale_id",
          "data-sunat_error",
          "data-tipo_pago",
          "data-toggle",
          "data-total",
          "data-ver_detalles",
          "id=\"btn-submit-nc-partial",
          "id=\"btnConfirmarGenerarComprobante",
          "id=\"btnDescargarPDF",
          "id=\"btnGenerarComprobante",
          "id=\"btnGuardarDatos",
          "id=\"btnGuardarPagoParcial",
          "id=\"modalFacturador",
          "id=\"modalFacturadorLabel",
          "id=\"modalGenerarComprobante",
          "id=\"modalNotaCreditoParcial",
          "id=\"modalPagosParciales",
          "name=\"direccion_fiscal",
          "name=\"dni",
          "name=\"email_invoice_boleta",
          "name=\"email_invoice_factura",
          "name=\"invoice_type",
          "name=\"name",
          "name=\"order_id",
          "name=\"razon_social",
          "name=\"ruc",
          "route('puntoVenta.list"
        ],
        "added": [
          "data-branch",
          "data-company",
          "data-list-mode",
          "data-permissions",
          "data-table",
          "data-tenant"
        ],
        "old_js": [
          "js/puntoVenta/list-access.js",
          "js/puntoVenta/listError.js"
        ],
        "new_js": [
          "js/puntoVenta/list-readonly.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "active",
        "anular",
        "code",
        "comprobante",
        "consultar_anulacion",
        "consultar_nota_credito",
        "created_by_name",
        "currency",
        "customer",
        "date",
        "descartar_error_comprobante",
        "eliminar_pago_parcial",
        "facturador",
        "generar_comprobante",
        "generar_nota_credito",
        "generar_nota_credito_parcial",
        "imprimir_comprobante",
        "is_deferred",
        "max",
        "message",
        "pagos_parciales",
        "price",
        "print_anulacion_cdr",
        "print_anulacion_pdf",
        "print_anulacion_xml",
        "print_recibo",
        "recuperar_archivos_nubefact",
        "reintentar_comprobante",
        "sale_detail_id",
        "sale_id",
        "sunat_error",
        "tipo_pago",
        "total",
        "ver_error_sunat",
        "ver_nc_parcial_pdf"
      ],
      "names": [
        "csrf-token",
        "direccion_fiscal",
        "dni",
        "email_invoice_boleta",
        "email_invoice_factura",
        "invoice_type",
        "name",
        "order_id",
        "razon_social",
        "ruc"
      ],
      "routes": [
        "puntoVenta.list"
      ],
      "old_js": [
        "js/puntoVenta/list-access.js",
        "js/puntoVenta/listError.js"
      ],
      "new_js": [
        "js/puntoVenta/list-readonly.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/anular/order/",
      "/dashboard/consultar/anulacion/",
      "/dashboard/consultar/nota-credito/",
      "/dashboard/customer/decolecta/${documento}",
      "/dashboard/facturador/generar",
      "/dashboard/generar/nota-credito/parcial/",
      "/dashboard/generar/nota-credito/total/",
      "/dashboard/get/data/sales/error/",
      "/dashboard/nota-credito/parcial/data/",
      "/dashboard/sale/generate-invoice",
      "/dashboard/sale/partial-payment/",
      "/dashboard/sale/partial-payment/store",
      "/dashboard/sale/update-dispatch-status",
      "/dashboard/sales/${orderId}/details",
      "/dashboard/sales/errors/",
      "/dashboard/sales/update-invoice-data",
      "/print/order/${orderId}"
    ]
  },
  {
    "name": "ganancia.index",
    "uri": "dashboard/listado/ganancias/diarias",
    "action": "App\\Http\\Controllers\\GananciaDiariaController@index",
    "views": [
      "ganancia.index"
    ],
    "intro": "bd413c65",
    "comparisons": [
      {
        "view": "ganancia.index",
        "file": "resources/views/ganancia/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/ganancias/index.js"
        ],
        "new_js": [
          "js/ganancias/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/ganancias/index.js"
      ],
      "new_js": [
        "js/ganancias/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": [
      "/dashboard/get/data/ganancias/V2/"
    ]
  },
  {
    "name": "ganancia.detail.index",
    "uri": "dashboard/listado/ganancia/detalles/{id}",
    "action": "App\\Http\\Controllers\\GananciaDiariaController@indexDetail",
    "views": [
      "ganancia.indexDetail"
    ],
    "intro": "bd413c65",
    "comparisons": [
      {
        "view": "ganancia.indexDetail",
        "file": "resources/views/ganancia/indexDetail.blade.php",
        "baseline_exists": false,
        "removed": [],
        "added": [
          "data-active",
          "data-date_resumen",
          "data-id",
          "data-item",
          "data-placement",
          "data-quantity_sale",
          "data-table",
          "data-toggle",
          "data-total_sale",
          "data-total_utility",
          "data-ver_detalles",
          "route('dashboard.principal",
          "route('ganancia.index"
        ],
        "old_js": [],
        "new_js": [
          "js/ganancias/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": [
        "js/ganancias/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "ganancia.detail.usuario",
    "uri": "dashboard/listado/ganancia/detalles/trabajador/{sale_id}",
    "action": "App\\Http\\Controllers\\GananciaDiariaController@indexDetailTrabajador",
    "views": [
      "ganancia.indexDetailTrabajador"
    ],
    "intro": "8349ba11",
    "comparisons": [
      {
        "view": "ganancia.indexDetailTrabajador",
        "file": "resources/views/ganancia/indexDetailTrabajador.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table",
          "route('ganancia.index.trabajador"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "ganancia.index.trabajador",
    "uri": "dashboard/listado/ganancias/diarias/trabajador",
    "action": "App\\Http\\Controllers\\GananciaDiariaController@indexTrabajador",
    "views": [
      "ganancia.indexTrabajador"
    ],
    "intro": "8349ba11",
    "comparisons": [
      {
        "view": "ganancia.indexTrabajador",
        "file": "resources/views/ganancia/indexTrabajador.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-placement"
        ],
        "added": [
          "data-boundary",
          "data-profit-detail",
          "data-table"
        ],
        "old_js": [
          "js/ganancias/indexTrabajador.js"
        ],
        "new_js": [
          "js/ganancias/indexTrabajador.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/ganancias/indexTrabajador.js"
      ],
      "new_js": [
        "js/ganancias/indexTrabajador.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "metas.index",
    "uri": "dashboard/metas",
    "action": "App\\Http\\Controllers\\MetaController@index",
    "views": [
      "metas.index"
    ],
    "intro": "4a8e796a",
    "comparisons": [
      {
        "view": "metas.index",
        "file": "resources/views/metas/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "metas.create",
    "uri": "dashboard/metas/create",
    "action": "App\\Http\\Controllers\\MetaController@create",
    "views": [
      "metas.create"
    ],
    "intro": "4a8e796a",
    "comparisons": [
      {
        "view": "metas.create",
        "file": "resources/views/metas/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "metas.edit",
    "uri": "dashboard/metas/{meta}/edit",
    "action": "App\\Http\\Controllers\\MetaController@edit",
    "views": [
      "metas.edit"
    ],
    "intro": "4a8e796a",
    "comparisons": [
      {
        "view": "metas.edit",
        "file": "resources/views/metas/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "metas.ranking",
    "uri": "dashboard/metas/ranking",
    "action": "App\\Http\\Controllers\\MetaController@ranking",
    "views": [
      "metas.ranking",
      "metas.ranking"
    ],
    "intro": "4a8e796a",
    "comparisons": [
      {
        "view": "metas.ranking",
        "file": "resources/views/metas/ranking.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      },
      {
        "view": "metas.ranking",
        "file": "resources/views/metas/ranking.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "quoteSale.index",
    "uri": "dashboard/cotizaciones/venta",
    "action": "App\\Http\\Controllers\\QuoteSaleController@index",
    "views": [
      "quoteSale.general"
    ],
    "intro": "2d9e3650",
    "comparisons": [
      {
        "view": "quoteSale.general",
        "file": "resources/views/quoteSale/general.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-active",
          "data-branch",
          "data-buttons",
          "data-code",
          "data-company",
          "data-created_at",
          "data-creator",
          "data-currency",
          "data-customer",
          "data-date_quote",
          "data-date_validate",
          "data-deadline",
          "data-decimals",
          "data-description",
          "data-dismiss",
          "data-id",
          "data-item",
          "data-order",
          "data-services_add",
          "data-state",
          "data-tenant",
          "data-time_delivery",
          "data-total",
          "data-total_sunat",
          "data-url",
          "id=\"btn-changeDecimals",
          "id=\"btn-download",
          "id=\"btn-export",
          "id=\"btn-search",
          "id=\"btnBusquedaAvanzada",
          "id=\"modalDecimals",
          "id=\"modalDelete",
          "name=\"creator",
          "name=\"customer",
          "name=\"decimals",
          "name=\"end",
          "name=\"quote_id",
          "name=\"start",
          "name=\"stateQuote",
          "name=\"subcategory_id",
          "route('quoteSale.decimals.change",
          "route('quoteSale.index",
          "route('quoteSale.pendingBranches",
          "route('subcategory.destroy"
        ],
        "added": [
          "route('quoteSale.index.facturadas"
        ],
        "old_js": [
          "js/quoteSale/general.js"
        ],
        "new_js": [
          "js/quoteSale/general.js"
        ],
        "same_js": false,
        "includes": [
          "quoteSale._operationalList"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [
        "subcategory_id"
      ],
      "routes": [
        "quoteSale.index",
        "subcategory.destroy"
      ],
      "old_js": [
        "js/quoteSale/general.js"
      ],
      "new_js": [
        "js/quoteSale/general.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "quoteSale.index.facturadas",
    "uri": "dashboard/cotizaciones/venta/facturadas",
    "action": "App\\Http\\Controllers\\QuoteSaleController@indexFacturadas",
    "views": [
      "quoteSale.facturadas"
    ],
    "intro": "2d9e3650",
    "comparisons": [
      {
        "view": "quoteSale.facturadas",
        "file": "resources/views/quoteSale/facturadas.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-active",
          "data-branch",
          "data-buttons",
          "data-code",
          "data-company",
          "data-created_at",
          "data-creator",
          "data-currency",
          "data-customer",
          "data-date_quote",
          "data-date_validate",
          "data-deadline",
          "data-decimals",
          "data-description",
          "data-dismiss",
          "data-id",
          "data-item",
          "data-order",
          "data-services_add",
          "data-state",
          "data-tenant",
          "data-time_delivery",
          "data-total",
          "data-total_sunat",
          "data-url",
          "id=\"btn-changeDecimals",
          "id=\"btn-download",
          "id=\"btn-export",
          "id=\"btn-search",
          "id=\"btnBusquedaAvanzada",
          "id=\"modalDecimals",
          "id=\"modalDelete",
          "name=\"creator",
          "name=\"customer",
          "name=\"decimals",
          "name=\"end",
          "name=\"quote_id",
          "name=\"start",
          "name=\"stateQuote",
          "name=\"subcategory_id",
          "route('quoteSale.decimals.change",
          "route('quoteSale.pendingBranches",
          "route('subcategory.destroy"
        ],
        "added": [],
        "old_js": [
          "js/quoteSale/facturadas.js"
        ],
        "new_js": [
          "js/quoteSale/facturadas.js"
        ],
        "same_js": false,
        "includes": [
          "quoteSale._operationalList"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [
        "subcategory_id"
      ],
      "routes": [
        "subcategory.destroy"
      ],
      "old_js": [
        "js/quoteSale/facturadas.js"
      ],
      "new_js": [
        "js/quoteSale/facturadas.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "quoteSale.show",
    "uri": "dashboard/ver/cotizacion/venta/{quote}",
    "action": "App\\Http\\Controllers\\QuoteSaleController@show",
    "views": [
      "quoteSale.show"
    ],
    "intro": "2d9e3650",
    "comparisons": [
      {
        "view": "quoteSale.show",
        "file": "resources/views/quoteSale/show.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-bodyConsumable",
          "data-bodyService",
          "data-bootstrap-switch",
          "data-card-widget",
          "data-consumableDescription",
          "data-consumableId",
          "data-consumableImporte",
          "data-consumablePrice",
          "data-consumablePrice2",
          "data-consumableQuantity",
          "data-consumableTotal",
          "data-consumableUnit",
          "data-consumableValor",
          "data-deleteConsumable",
          "data-descuento",
          "data-discount_input_mode",
          "data-discount_type",
          "data-discount_value",
          "data-dismiss",
          "data-equip",
          "data-letterequipment",
          "data-off-color",
          "data-off-text",
          "data-on-color",
          "data-on-text",
          "data-placement",
          "data-rentequipment",
          "data-serviceBillable",
          "data-serviceDescription",
          "data-serviceId",
          "data-serviceImporte",
          "data-servicePU",
          "data-serviceQuantity",
          "data-serviceRow",
          "data-serviceUnit",
          "data-serviceVU",
          "data-toggle",
          "data-type_promotion",
          "data-url",
          "data-utilityequipment",
          "id=\"btn-changePercentage",
          "id=\"btn-grouped",
          "id=\"btn-submit",
          "id=\"btn-submit-customer",
          "id=\"modalChangePercentages",
          "id=\"modalCustomer",
          "id=\"modalCustomerLabel",
          "name=\"address",
          "name=\"business_name",
          "name=\"code_description",
          "name=\"code_quote",
          "name=\"date_quote",
          "name=\"date_validate",
          "name=\"delivery_time",
          "name=\"discount_input_mode",
          "name=\"discount_type",
          "name=\"equipment_percentage",
          "name=\"location",
          "name=\"percentage_letter",
          "name=\"percentage_rent",
          "name=\"percentage_utility",
          "name=\"quote_id",
          "name=\"quote_percentage",
          "name=\"ruc",
          "name=\"special",
          "name=\"way_to_pay",
          "route('customer.store",
          "route('dashboard.principal",
          "route('quoteSale.update"
        ],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/quoteSale/edit.js"
        ],
        "new_js": [],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [
        "addConsumable",
        "addService",
        "billable-id",
        "billable-label",
        "bodyConsumable",
        "bodyConsumables",
        "bodyService",
        "cantidad",
        "consumable",
        "consumableDescription",
        "consumableId",
        "consumableImporte",
        "consumablePrice",
        "consumablePrice2",
        "consumableQuantity",
        "consumableRow",
        "consumableTotal",
        "consumableUnit",
        "consumableValor",
        "consumable_price_real",
        "consumable_valor_real",
        "consumableid",
        "consumablesContainer",
        "deleteConsumable",
        "deleteService",
        "descuento",
        "descuento_real",
        "detailequipment",
        "dirty",
        "discount_input_mode",
        "discount_type",
        "discount_value",
        "equip",
        "equipment_consumable_id",
        "exonerada_real",
        "gravada_real",
        "idEquipment",
        "igv_total_real",
        "inafecta_real",
        "is_itemeable",
        "item-code",
        "item-id",
        "item-row",
        "letterequipment",
        "packs",
        "pres-id",
        "pres-label",
        "pres-packs",
        "pres-price",
        "pres-qty",
        "pres-row",
        "presentation_id",
        "presentation_text",
        "quote",
        "rentequipment",
        "saveEquipment",
        "selected_item_ids",
        "selected_items_text",
        "serviceBillable",
        "serviceDescription",
        "serviceId",
        "serviceImporte",
        "servicePU",
        "serviceQuantity",
        "serviceRow",
        "serviceUnit",
        "serviceVU",
        "stock_item_id",
        "tax_code",
        "tax_rate",
        "total_importe_real",
        "type_promotion",
        "type_tax_id",
        "units_equivalent",
        "units_per_pack",
        "url",
        "utilityequipment"
      ],
      "names": [
        "address",
        "business_name",
        "code_description",
        "code_quote",
        "csrf-token",
        "date_quote",
        "date_validate",
        "delivery_time",
        "discount_input_mode",
        "discount_type",
        "equipment_percentage",
        "form_branch_id",
        "form_company_id",
        "form_tenant_id",
        "location",
        "percentage_letter",
        "percentage_rent",
        "percentage_utility",
        "quote_id",
        "quote_percentage",
        "ruc",
        "special",
        "way_to_pay"
      ],
      "routes": [
        "customer.store",
        "dashboard.principal",
        "quoteSale.update"
      ],
      "old_js": [
        "js/quoteSale/edit.js"
      ],
      "new_js": []
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/get/contact/",
      "/dashboard/get/quote/sale/materials",
      "/dashboard/get/quote/sale/materials/totals",
      "/dashboard/materials-presentations/material/${materialId}/presentations",
      "/dashboard/quotes/update-general",
      "/dashboard/update/equipment/"
    ]
  },
  {
    "name": "quoteSale.pendingBranches",
    "uri": "dashboard/cotizaciones/venta/pendientes-sucursal",
    "action": "App\\Http\\Controllers\\QuoteBranchPendingController@index",
    "views": [
      "quoteSale.pendingBranches"
    ],
    "intro": "2d9e3650",
    "comparisons": [
      {
        "view": "quoteSale.pendingBranches",
        "file": "resources/views/quoteSale/pendingBranches.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "quoteSale.pendingBranches.show",
    "uri": "dashboard/cotizaciones/venta/pendientes-sucursal/{id}",
    "action": "App\\Http\\Controllers\\QuoteBranchPendingController@show",
    "views": [
      "quoteSale.pendingBranchDetail"
    ],
    "intro": "2d9e3650",
    "comparisons": [
      {
        "view": "quoteSale.pendingBranchDetail",
        "file": "resources/views/quoteSale/pendingBranchDetail.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "kardex.index",
    "uri": "dashboard/kardex",
    "action": "App\\Http\\Controllers\\InventoryMovementController@index",
    "views": [
      "inventory.kardex"
    ],
    "intro": "4b546d4c",
    "comparisons": [
      {
        "view": "inventory.kardex",
        "file": "resources/views/inventory/kardex.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/inventory/kardex.js"
        ],
        "new_js": [
          "js/inventory/kardex.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/inventory/kardex.js"
      ],
      "new_js": [
        "js/inventory/kardex.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "settings.material-details.index",
    "uri": "dashboard/settings/material-details",
    "action": "App\\Http\\Controllers\\MaterialDetailSettingController@index",
    "views": [
      "materialDetailSetting.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "materialDetailSetting.index",
        "file": "resources/views/materialDetailSetting/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": [
          "materialDetailSetting._context"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "settings.material-details.index"
      ],
      "old_js": [],
      "new_js": []
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "cashBox.index",
    "uri": "dashboard/cash-boxes",
    "action": "App\\Http\\Controllers\\CashBoxController@index",
    "views": [
      "cashBox.index"
    ],
    "intro": "9ad0106d",
    "comparisons": [
      {
        "view": "cashBox.index",
        "file": "resources/views/cashBox/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-placement"
        ],
        "added": [
          "data-boundary",
          "data-table",
          "data-toggle-label"
        ],
        "old_js": [
          "js/cashBox/index.js"
        ],
        "new_js": [
          "js/cashBox/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/cashBox/index.js"
      ],
      "new_js": [
        "js/cashBox/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "cashBoxSubtype.index",
    "uri": "dashboard/cash-box-subtypes",
    "action": "App\\Http\\Controllers\\CashBoxSubtypeController@index",
    "views": [
      "cashBoxSubtype.index"
    ],
    "intro": "9ad0106d",
    "comparisons": [
      {
        "view": "cashBoxSubtype.index",
        "file": "resources/views/cashBoxSubtype/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-boundary",
          "data-table",
          "data-toggle",
          "data-toggle-label"
        ],
        "old_js": [
          "js/cashBoxSubtype/index.js"
        ],
        "new_js": [
          "js/cashBoxSubtype/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/cashBoxSubtype/index.js"
      ],
      "new_js": [
        "js/cashBoxSubtype/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "cashMovement.my.index",
    "uri": "dashboard/cash-movements/my",
    "action": "App\\Http\\Controllers\\CashBoxController@myIndex",
    "views": [
      "cashBox.my_index"
    ],
    "intro": "80b240ae",
    "comparisons": [
      {
        "view": "cashBox.my_index",
        "file": "resources/views/cashBox/my_index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-currency",
          "data-table"
        ],
        "old_js": [
          "js/cashBox/my_index.js",
          "js/cashRegister/regularization.js"
        ],
        "new_js": [
          "js/cashBox/my_index.js",
          "js/cashRegister/regularization.js"
        ],
        "same_js": false,
        "includes": [
          "cashRegister._regularization"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/cashBox/my_index.js",
        "js/cashRegister/regularization.js"
      ],
      "new_js": [
        "js/cashBox/my_index.js",
        "js/cashRegister/regularization.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "cashMovement.admin.index",
    "uri": "dashboard/cash-movements/admin",
    "action": "App\\Http\\Controllers\\CashBoxController@adminIndex",
    "views": [
      "cashBox.admin_index"
    ],
    "intro": "744d28b8",
    "comparisons": [
      {
        "view": "cashBox.admin_index",
        "file": "resources/views/cashBox/admin_index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": [
          "cashBox.summary_movements",
          "cashBox.admin_readonly"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [
        "amount",
        "amount_regularize",
        "branch",
        "buttons",
        "cash_box_id",
        "cash_box_name",
        "cash_box_type",
        "cashbox",
        "cashbox-btn",
        "commission",
        "company",
        "date",
        "description",
        "id",
        "is_open",
        "print_nota",
        "regularizar",
        "regularization-amount",
        "session",
        "status",
        "subtype",
        "tenant",
        "type",
        "user"
      ],
      "names": [
        "_token",
        "amount_regularize",
        "cash_movement_id",
        "csrf-token",
        "observation"
      ],
      "routes": [
        "cashMovement.regularize",
        "cashRegister.open"
      ],
      "old_js": [
        "js/cashBox/admin_index.js",
        "js/cashBox/summary-readonly.js",
        "js/cashRegister/regularization.js"
      ],
      "new_js": [
        "js/cashBox/admin-readonly.js",
        "js/cashBox/summary-readonly.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "cashRegister.session",
    "uri": "dashboard/cash-registers/session/{cashBox}",
    "action": "App\\Http\\Controllers\\CashRegisterController@session",
    "views": [
      "cashRegister.session"
    ],
    "intro": "80b240ae",
    "comparisons": [
      {
        "view": "cashRegister.session",
        "file": "resources/views/cashRegister/session.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-currency",
          "data-table",
          "id=\"btn-arqueoCashRegister"
        ],
        "old_js": [
          "js/cashRegister/regularization.js",
          "js/cashRegister/session.js"
        ],
        "new_js": [
          "js/cashRegister/regularization.js",
          "js/cashRegister/session.js"
        ],
        "same_js": false,
        "includes": [
          "cashRegister._regularization"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/cashRegister/regularization.js",
        "js/cashRegister/session.js"
      ],
      "new_js": [
        "js/cashRegister/regularization.js",
        "js/cashRegister/session.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "financial.summary",
    "uri": "dashboard/financial/summary",
    "action": "App\\Http\\Controllers\\CashRegisterController@financialSummary",
    "views": [
      "cashRegister.summary"
    ],
    "intro": "483a7858",
    "comparisons": [
      {
        "view": "cashRegister.summary",
        "file": "resources/views/cashRegister/summary.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "financial.summary.movements",
    "uri": "dashboard/financial/summary/cash-boxes/{cashBox}/movements",
    "action": "App\\Http\\Controllers\\CashBoxController@summaryIndex",
    "views": [
      "cashBox.admin_index"
    ],
    "intro": "483a7858",
    "comparisons": [
      {
        "view": "cashBox.admin_index",
        "file": "resources/views/cashBox/admin_index.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-amount",
          "data-amount_regularize",
          "data-buttons",
          "data-cash_box_id",
          "data-cash_box_name",
          "data-cash_box_type",
          "data-cashbox",
          "data-cashbox-btn",
          "data-commission",
          "data-date",
          "data-description",
          "data-id",
          "data-is_open",
          "data-placement",
          "data-print_nota",
          "data-regularizar",
          "data-status",
          "data-subtype",
          "data-toggle",
          "data-type",
          "data-user",
          "id=\"btn-search",
          "route('cashMovement.admin.list",
          "route('cashRegister.open",
          "route('dashboard.principal"
        ],
        "added": [],
        "old_js": [
          "js/cashBox/admin_index.js",
          "js/cashRegister/regularization.js"
        ],
        "new_js": [],
        "same_js": true,
        "includes": [
          "cashBox.summary_movements",
          "cashBox.admin_readonly"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [
        "amount",
        "amount_regularize",
        "branch",
        "buttons",
        "cash_box_id",
        "cash_box_name",
        "cash_box_type",
        "cashbox",
        "cashbox-btn",
        "commission",
        "company",
        "date",
        "description",
        "id",
        "is_open",
        "print_nota",
        "regularizar",
        "regularization-amount",
        "session",
        "status",
        "subtype",
        "tenant",
        "type",
        "user"
      ],
      "names": [
        "_token",
        "amount_regularize",
        "cash_movement_id",
        "csrf-token",
        "observation"
      ],
      "routes": [
        "cashMovement.regularize",
        "cashRegister.open"
      ],
      "old_js": [
        "js/cashBox/admin_index.js",
        "js/cashRegister/regularization.js"
      ],
      "new_js": [
        "js/cashBox/admin-readonly.js",
        "js/cashBox/summary-readonly.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "shipping_guides.view",
    "uri": "dashboard/shipping-guides/listado",
    "action": "App\\Http\\Controllers\\ShippingGuideController@viewIndex",
    "views": [
      "shipping_guides.index"
    ],
    "intro": "eb5ebbc3",
    "comparisons": [
      {
        "view": "shipping_guides.index",
        "file": "resources/views/shipping_guides/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "route('shipping_guides.download"
        ],
        "old_js": [
          "js/shipping_guides/index.js"
        ],
        "new_js": [
          "js/shipping_guides/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/shipping_guides/index.js"
      ],
      "new_js": [
        "js/shipping_guides/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "shipping_guides.create",
    "uri": "dashboard/shipping-guides/create",
    "action": "App\\Http\\Controllers\\ShippingGuideController@create",
    "views": [
      "shipping_guides.create"
    ],
    "intro": "9c26f05a",
    "comparisons": [
      {
        "view": "shipping_guides.create",
        "file": "resources/views/shipping_guides/create.blade.php",
        "baseline_exists": true,
        "removed": [
          "data-parent"
        ],
        "added": [
          "data-guide-chevron",
          "data-guide-section",
          "data-target"
        ],
        "old_js": [
          "js/shipping_guides/create.js"
        ],
        "new_js": [
          "js/shipping_guides/create.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/shipping_guides/create.js"
      ],
      "new_js": [
        "js/shipping_guides/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "shipping_guides.show",
    "uri": "dashboard/shipping-guides/detalles/{guide}",
    "action": "App\\Http\\Controllers\\ShippingGuideController@show",
    "views": [
      "shipping_guides.show"
    ],
    "intro": "eb5ebbc3",
    "comparisons": [
      {
        "view": "shipping_guides.show",
        "file": "resources/views/shipping_guides/show.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": [
          "shipping_guides._recovery"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "stockitems.index",
    "uri": "dashboard/stock-items",
    "action": "App\\Http\\Controllers\\StockItemController@index",
    "views": [
      "stockItem.list"
    ],
    "intro": "6de44064",
    "comparisons": [
      {
        "view": "stockItem.list",
        "file": "resources/views/stockItem/list.blade.php",
        "baseline_exists": true,
        "removed": [
          "id=\"btn-resumen-stock",
          "id=\"btn-save-inventory-levels",
          "id=\"modalAdjustStock",
          "id=\"modal_stock_item_id",
          "route('dashboard.principal",
          "route('material.create",
          "route('stock"
        ],
        "added": [
          "data-table",
          "route('material.stock.getItems"
        ],
        "old_js": [
          "js/material/inventory-context.js",
          "js/stockItem/list.js"
        ],
        "new_js": [
          "js/material/inventory-context.js",
          "js/stockItem/list.js"
        ],
        "same_js": false,
        "includes": [
          "material._inventoryContext"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [
        "ajustar_stock",
        "display-name",
        "stock-actual",
        "value",
        "variant"
      ],
      "names": [
        "${escapeHtml(item.display_name || ",
        "csrf-token",
        "inventory_levels[${index}][id]",
        "inventory_levels[${index}][max_alert]",
        "inventory_levels[${index}][min_alert]"
      ],
      "routes": [
        "dashboard.principal",
        "material.create",
        "stock-item.output-adjustment",
        "stockitems.inventory-levels.update"
      ],
      "old_js": [
        "js/material/inventory-context.js",
        "js/stockItem/list.js"
      ],
      "new_js": [
        "js/material/inventory-context.js",
        "js/stockItem/list.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": [
      "/dashboard/stock-items/${id}/toggle-active",
      "/dashboard/stock-items/${id}/toggle-inventory"
    ]
  },
  {
    "name": "stockitems.view.variants",
    "uri": "dashboard/view/material/variants/{id}",
    "action": "App\\Http\\Controllers\\StockItemController@viewMaterialVariants",
    "views": [
      "material.variants"
    ],
    "intro": "744d28b8",
    "comparisons": [
      {
        "view": "material.variants",
        "file": "resources/views/material/variants.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/material/variants.js"
        ],
        "new_js": [
          "js/material/variants.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/material/variants.js"
      ],
      "new_js": [
        "js/material/variants.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "color.index",
    "uri": "dashboard/colors",
    "action": "App\\Http\\Controllers\\ColorController@index",
    "views": [
      "color.index"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "color.index",
        "file": "resources/views/color/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('color.index"
        ],
        "added": [
          "data-backend-sync-open",
          "data-buttons",
          "data-can-create",
          "data-delete-cancel",
          "data-dismiss",
          "data-selection",
          "data-table",
          "data-url",
          "data-url-create",
          "id=\"modalDelete",
          "id=\"modalDeleteLabel",
          "name=\"color_id"
        ],
        "old_js": [
          "js/color/index.js"
        ],
        "new_js": [
          "js/color/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [
        "csrf-token"
      ],
      "routes": [
        "color.index"
      ],
      "old_js": [
        "js/color/index.js"
      ],
      "new_js": [
        "js/color/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "color.create",
    "uri": "dashboard/crear/color",
    "action": "App\\Http\\Controllers\\ColorController@create",
    "views": [
      "color.create"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "color.create",
        "file": "resources/views/color/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/color/form.js"
        ],
        "new_js": [
          "js/color/form.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/color/form.js"
      ],
      "new_js": [
        "js/color/form.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "color.edit",
    "uri": "dashboard/editar/color/{id}",
    "action": "App\\Http\\Controllers\\ColorController@edit",
    "views": [
      "color.edit"
    ],
    "intro": "8faf1ef3",
    "comparisons": [
      {
        "view": "color.edit",
        "file": "resources/views/color/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/color/form.js"
        ],
        "new_js": [
          "js/color/form.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/color/form.js"
      ],
      "new_js": [
        "js/color/form.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "configUserWeb.index",
    "uri": "dashboard/configuracion/usuarios/web",
    "action": "App\\Http\\Controllers\\ConfigUserWebController@listar",
    "views": [
      "configUserWeb.index"
    ],
    "intro": "b22eb015",
    "comparisons": [
      {
        "view": "configUserWeb.index",
        "file": "resources/views/configUserWeb/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-can-edit",
          "data-can-reset",
          "data-can-status"
        ],
        "old_js": [
          "js/configUserWeb/index.js"
        ],
        "new_js": [
          "js/configUserWeb/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/configUserWeb/index.js"
      ],
      "new_js": [
        "js/configUserWeb/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "configUserWeb.create",
    "uri": "dashboard/configuracion/usuarios/web/nuevo",
    "action": "App\\Http\\Controllers\\ConfigUserWebController@create",
    "views": [
      "configUserWeb.create"
    ],
    "intro": "b22eb015",
    "comparisons": [
      {
        "view": "configUserWeb.create",
        "file": "resources/views/configUserWeb/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/configUserWeb/create.js"
        ],
        "new_js": [
          "js/configUserWeb/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/configUserWeb/create.js"
      ],
      "new_js": [
        "js/configUserWeb/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "configUserWeb.edit",
    "uri": "dashboard/configuracion/usuarios/web/{id}/editar",
    "action": "App\\Http\\Controllers\\ConfigUserWebController@edit",
    "views": [
      "configUserWeb.edit"
    ],
    "intro": "b22eb015",
    "comparisons": [
      {
        "view": "configUserWeb.edit",
        "file": "resources/views/configUserWeb/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [],
        "old_js": [
          "js/configUserWeb/edit.js"
        ],
        "new_js": [
          "js/configUserWeb/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/configUserWeb/edit.js"
      ],
      "new_js": [
        "js/configUserWeb/edit.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "companyStockItem.index",
    "uri": "dashboard/dashboard/company-stock-items",
    "action": "App\\Http\\Controllers\\CompanyStockItemController@index",
    "views": [
      "companyStockItem.index"
    ],
    "intro": "4b0ac964",
    "comparisons": [
      {
        "view": "companyStockItem.index",
        "file": "resources/views/companyStockItem/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [
          "js/companyStockItem/index.js"
        ],
        "new_js": [
          "js/companyStockItem/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/companyStockItem/index.js"
      ],
      "new_js": [
        "js/companyStockItem/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "platform.dashboard",
    "uri": "dashboard/plataforma",
    "action": "App\\Http\\Controllers\\Platform\\PlatformDashboardController@index",
    "views": [
      "platform.dashboard.index"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "platform.dashboard.index",
        "file": "resources/views/platform/dashboard/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "route('plan.index",
          "route('platformActivity.index",
          "route('platformPercentageWorker.index",
          "route('platformTenant.index",
          "route('roleTemplate.index",
          "route('tenantRole.index"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "platformActivity.index",
    "uri": "dashboard/plataforma/activity-log",
    "action": "App\\Http\\Controllers\\Platform\\ActivityLogController@index",
    "views": [
      "platform.activityLog.index"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "platform.activityLog.index",
        "file": "resources/views/platform/activityLog/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-buttons",
          "data-table",
          "route('platform.dashboard"
        ],
        "old_js": [
          "js/platform/activityLog/index.js"
        ],
        "new_js": [
          "js/platform/activityLog/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/platform/activityLog/index.js"
      ],
      "new_js": [
        "js/platform/activityLog/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "platformTenant.index",
    "uri": "dashboard/plataforma/tenants",
    "action": "App\\Http\\Controllers\\Platform\\TenantController@index",
    "views": [
      "platform.tenants.index"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "platform.tenants.index",
        "file": "resources/views/platform/tenants/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-buttons",
          "data-table",
          "route('platform.dashboard"
        ],
        "old_js": [
          "js/platform/tenants/index.js"
        ],
        "new_js": [
          "js/platform/tenants/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/platform/tenants/index.js"
      ],
      "new_js": [
        "js/platform/tenants/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "platformTenant.create",
    "uri": "dashboard/plataforma/tenants/create",
    "action": "App\\Http\\Controllers\\Platform\\TenantController@create",
    "views": [
      "platform.tenants.create"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "platform.tenants.create",
        "file": "resources/views/platform/tenants/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "route('platform.dashboard"
        ],
        "old_js": [
          "js/platform/tenants/create.js"
        ],
        "new_js": [
          "js/platform/tenants/create.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/platform/tenants/create.js"
      ],
      "new_js": [
        "js/platform/tenants/create.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "platformTenant.show",
    "uri": "dashboard/plataforma/tenants/{id}",
    "action": "App\\Http\\Controllers\\Platform\\TenantController@show",
    "views": [
      "platform.tenants.show"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "platform.tenants.show",
        "file": "resources/views/platform/tenants/show.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "route('platform.dashboard",
          "route('platformTenant.index"
        ],
        "old_js": [
          "js/platform/tenants/show.js"
        ],
        "new_js": [
          "js/platform/tenants/show.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/platform/tenants/show.js"
      ],
      "new_js": [
        "js/platform/tenants/show.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "plan.index",
    "uri": "dashboard/plataforma/plans",
    "action": "App\\Http\\Controllers\\PlanController@index",
    "views": [
      "plan.index"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "plan.index",
        "file": "resources/views/plan/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "id=\"modalCreatePlan",
          "id=\"modalEditPlan"
        ],
        "added": [
          "data-buttons",
          "data-table",
          "route('plan.toggleStatus",
          "route('plan.update",
          "route('platform.dashboard"
        ],
        "old_js": [
          "js/plan/index.js"
        ],
        "new_js": [
          "js/plan/index.js"
        ],
        "same_js": false,
        "includes": [
          "plan.partials.form"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/plan/index.js"
      ],
      "new_js": [
        "js/plan/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "roleTemplate.index",
    "uri": "dashboard/plataforma/role-templates",
    "action": "App\\Http\\Controllers\\RoleTemplateController@index",
    "views": [
      "roleTemplate.index"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "roleTemplate.index",
        "file": "resources/views/roleTemplate/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-buttons",
          "data-table",
          "route('platform.dashboard",
          "route('roleTemplate.edit",
          "route('roleTemplate.toggleStatus"
        ],
        "old_js": [
          "js/roleTemplate/index.js"
        ],
        "new_js": [
          "js/roleTemplate/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/roleTemplate/index.js"
      ],
      "new_js": [
        "js/roleTemplate/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "roleTemplate.create",
    "uri": "dashboard/plataforma/role-templates/create",
    "action": "App\\Http\\Controllers\\RoleTemplateController@create",
    "views": [
      "roleTemplate.create"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "roleTemplate.create",
        "file": "resources/views/roleTemplate/create.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('dashboard.principal"
        ],
        "added": [
          "route('platform.dashboard"
        ],
        "old_js": [
          "js/roleTemplate/form.js"
        ],
        "new_js": [
          "js/roleTemplate/form.js"
        ],
        "same_js": true,
        "includes": [
          "roleTemplate.partials.styles"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "dashboard.principal"
      ],
      "old_js": [
        "js/roleTemplate/form.js"
      ],
      "new_js": [
        "js/roleTemplate/form.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "roleTemplate.edit",
    "uri": "dashboard/plataforma/role-templates/{id}/edit",
    "action": "App\\Http\\Controllers\\RoleTemplateController@edit",
    "views": [
      "roleTemplate.edit"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "roleTemplate.edit",
        "file": "resources/views/roleTemplate/edit.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('dashboard.principal"
        ],
        "added": [
          "route('platform.dashboard"
        ],
        "old_js": [
          "js/roleTemplate/form.js"
        ],
        "new_js": [
          "js/roleTemplate/form.js"
        ],
        "same_js": true,
        "includes": [
          "roleTemplate.partials.styles"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "dashboard.principal"
      ],
      "old_js": [
        "js/roleTemplate/form.js"
      ],
      "new_js": [
        "js/roleTemplate/form.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "tenantRole.index",
    "uri": "dashboard/plataforma/tenant-roles",
    "action": "App\\Http\\Controllers\\TenantRoleController@index",
    "views": [
      "tenantRole.index"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "tenantRole.index",
        "file": "resources/views/tenantRole/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-buttons",
          "data-table",
          "route('platform.dashboard",
          "route('tenantRole.create",
          "route('tenantRole.edit",
          "route('tenantRole.toggleStatus"
        ],
        "old_js": [
          "js/tenantRole/index.js"
        ],
        "new_js": [
          "js/tenantRole/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/tenantRole/index.js"
      ],
      "new_js": [
        "js/tenantRole/index.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "tenantRole.create",
    "uri": "dashboard/plataforma/tenants/{tenantId}/roles/create",
    "action": "App\\Http\\Controllers\\TenantRoleController@create",
    "views": [
      "tenantRole.create"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "tenantRole.create",
        "file": "resources/views/tenantRole/create.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "route('platform.dashboard"
        ],
        "old_js": [
          "js/tenantRole/form.js"
        ],
        "new_js": [
          "js/tenantRole/form.js"
        ],
        "same_js": true,
        "includes": [
          "roleTemplate.partials.styles"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/tenantRole/form.js"
      ],
      "new_js": [
        "js/tenantRole/form.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "tenantRole.edit",
    "uri": "dashboard/plataforma/tenants/{tenantId}/roles/{roleId}/edit",
    "action": "App\\Http\\Controllers\\TenantRoleController@edit",
    "views": [
      "tenantRole.edit"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "tenantRole.edit",
        "file": "resources/views/tenantRole/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "route('platform.dashboard"
        ],
        "old_js": [
          "js/tenantRole/form.js"
        ],
        "new_js": [
          "js/tenantRole/form.js"
        ],
        "same_js": true,
        "includes": [
          "roleTemplate.partials.styles"
        ]
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [
        "js/tenantRole/form.js"
      ],
      "new_js": [
        "js/tenantRole/form.js"
      ]
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "platformPercentageWorker.index",
    "uri": "dashboard/plataforma/parametros-laborales",
    "action": "App\\Http\\Controllers\\PercentageWorkerController@index",
    "views": [
      "percentageWorker.index"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "percentageWorker.index",
        "file": "resources/views/percentageWorker/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('dashboard.principal"
        ],
        "added": [
          "data-buttons",
          "data-table",
          "data-toggle",
          "data-total",
          "route('platform.dashboard",
          "route('platformPercentageWorker.edit"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "dashboard.principal"
      ],
      "old_js": [],
      "new_js": []
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "platformPercentageWorker.edit",
    "uri": "dashboard/plataforma/parametros-laborales/{id}/editar",
    "action": "App\\Http\\Controllers\\PercentageWorkerController@edit",
    "views": [
      "percentageWorker.edit"
    ],
    "intro": "673cb064",
    "comparisons": [
      {
        "view": "percentageWorker.edit",
        "file": "resources/views/percentageWorker/edit.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('dashboard.principal"
        ],
        "added": [
          "route('platform.dashboard"
        ],
        "old_js": [
          "js/percentageWorker/edit.js"
        ],
        "new_js": [
          "js/percentageWorker/edit.js"
        ],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "dashboard.principal"
      ],
      "old_js": [
        "js/percentageWorker/edit.js"
      ],
      "new_js": [
        "js/percentageWorker/edit.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "platformTypeTax.index",
    "uri": "dashboard/plataforma/tipos-impuesto",
    "action": "App\\Http\\Controllers\\TypeTaxController@index",
    "views": [
      "typeTax.index"
    ],
    "intro": "4c2b1989",
    "comparisons": [
      {
        "view": "typeTax.index",
        "file": "resources/views/typeTax/index.blade.php",
        "baseline_exists": true,
        "removed": [
          "route('dashboard.principal"
        ],
        "added": [
          "data-boundary",
          "data-table",
          "data-toggle",
          "route('platform.dashboard",
          "route('platformTypeTax.update"
        ],
        "old_js": [
          "js/typeTax/index.js"
        ],
        "new_js": [
          "js/typeTax/index.js"
        ],
        "same_js": false,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [
        "dashboard.principal"
      ],
      "old_js": [
        "js/typeTax/index.js"
      ],
      "new_js": [
        "js/typeTax/index.js"
      ]
    },
    "structural_match": false,
    "missing_js_urls": []
  },
  {
    "name": "platformNubefact.index",
    "uri": "dashboard/plataforma/nubefact",
    "action": "App\\Http\\Controllers\\Platform\\NubefactConfigController@index",
    "views": [
      "platform.nubefact.index"
    ],
    "intro": "d2592207",
    "comparisons": [
      {
        "view": "platform.nubefact.index",
        "file": "resources/views/platform/nubefact/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-table"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "platformNubefact.edit",
    "uri": "dashboard/plataforma/tenants/{tenantId}/companies/{companyId}/nubefact",
    "action": "App\\Http\\Controllers\\Platform\\NubefactConfigController@edit",
    "views": [
      "platform.nubefact.edit"
    ],
    "intro": "d2592207",
    "comparisons": [
      {
        "view": "platform.nubefact.edit",
        "file": "resources/views/platform/nubefact/edit.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "name=\"gre_remitente_numero_inicial",
          "name=\"serie_gre_remitente"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  },
  {
    "name": "platformShippingCatalogs.index",
    "uri": "dashboard/plataforma/catalogos-guias/{catalog?}",
    "action": "App\\Http\\Controllers\\Platform\\ShippingCatalogController@index",
    "views": [
      "platform.shippingCatalogs.index"
    ],
    "intro": "b951afdc",
    "comparisons": [
      {
        "view": "platform.shippingCatalogs.index",
        "file": "resources/views/platform/shippingCatalogs/index.blade.php",
        "baseline_exists": true,
        "removed": [],
        "added": [
          "data-dismiss",
          "data-table",
          "data-target",
          "data-toggle",
          "route('platform.dashboard"
        ],
        "old_js": [],
        "new_js": [],
        "same_js": true,
        "includes": []
      }
    ],
    "functional_diff": {
      "hooks": [],
      "names": [],
      "routes": [],
      "old_js": [],
      "new_js": []
    },
    "structural_match": true,
    "missing_js_urls": []
  }
]
```
