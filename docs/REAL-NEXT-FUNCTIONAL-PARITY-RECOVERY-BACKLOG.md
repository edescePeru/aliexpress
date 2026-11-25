# REAL NEXT — FUNCTIONAL PARITY RECOVERY BACKLOG

## R7.7B — reconciliación Perfil / Material Edit — 2026-11-25

Ver [informe focalizado](REAL-NEXT-PROFILE-MATERIAL-POST-DEV-RECONCILIATION.md). Avatar y Guardar material ya disponibles: FRONTEND PARITY PASS, sin gates obsoletos encontrados ni cambios runtime. Chrome aislado, payloads/errores/éxitos interceptados; 24 checks responsive sin overflow y cero pageerrors. Persistencia real no ejecutada, QA mutadora pendiente. MAT-01 permite historia/stock/shared-master; MAT-03 preserva Items y ownership sin prohibición general shared-master. IDs canónicos: MAT-04 multinivel, MAT-05 flags (la tabla R7.5 invirtió los títulos). PROFILE-01 fix DEV preservado, no nuevo cierre USER-01. No se repite certificación backend. Sin nuevos findings ni FR: **74 recuperadas +10 bloqueadas +23 excluidas =107**. R7.7C/R8 no iniciadas. Historia anterior conservada.


## Seguimiento R7.7A — 2026-11-25

Normal Create/Edit y Entry Purchase Create homologados3/3. Ver REAL-NEXT-NORMAL-PURCHASE-ENTRY-FORMS-HOMOLOGATION.md. Campos, modales, acciones y payloads preservados;24 checks responsive PASS; mutaciones interceptadas, ninguna compra/entrada real. Backend/JS/CSS0. Allowlist limitada a3 vistas. FR017/018/046 ya recuperadas en R7.6: **no sumar otra vez**. Contabilidad74 recuperadas+10 bloqueadas+23 excluidas=107. ENTRY-R75-01/ENTRY-R4-01 intactos. R7.7B/R8 no iniciadas; sin commit/push/merge. Evidencia anterior conservada.


## Estado vigente R7.6.1 — Document Functional Recovery — 2026-11-25

Decisión explícita del propietario: virtual host descartado; mantener http://127.0.0.1:8080 y PHP7.3.33. La limitación de artisan serve no bloquea por sí sola los controles que usan el contrato autorizado DEV. Esta sección sustituye la clasificación de instalación pendiente de R7.6; la evidencia anterior se conserva como historia, no como estado vigente.

**DOCUMENT CAPABILITIES RESTORED: 8/8.** FR-049/054/083/088/093/096/097/098 recuperadas mediante entry.document.main / observation / extra y navegación contextual existente. No asset() para adjuntos ni fallback público. Los paths de los lectores coinciden intencionalmente con /images/entries/... por contrato DEV; usar route() no convierte artisan serve en un servidor que aplique .htaccess.

**107 FR = 74 recuperados + 10 bloqueados backend + 23 excluidos.** R7.6 acumula 12/12 recuperadas (4 anteriores +8 documentales). Permanecen FR047/048/059/063/064/102/103/105/106/107; no nuevos blockers del lector demostrados. DOC-01 conserva observación de entorno y verificación HTTP directa pendiente en despliegue, fuera del conteo de pérdidas frontend. No equivale a certificación integral de seguridad.

Validación propia: 8 tests Node PASS (4 documentales +4 regresión R7.6), Blade compile y sintaxis JS PASS; 16 comprobaciones Chrome headless aislado (8 superficies ×375/1440), 16 navegaciones interceptadas; cero pageerrors y overflow global. Metadatos documentales SIMULADOS sobre render/DTO real leído sin escrituras; ninguna descarga comercial. Permisos negativos simulados en renderer. No se repite certificación backend DEV ni J/P/K. Backend/CSS/infraestructura/mutaciones comerciales:0. Sin commit/push/merge. R7.7/R8 no iniciadas.

Propuesta Apache descartada: retirado docs/environment/aliexpress-apache-local.conf.example, generado exclusivamente para esa propuesta y nunca instalado. URL vigente: http://127.0.0.1:8080. Ver informe R76 para matriz y evidencia.

## Evidencia anterior preservada

## Historia — Propuesta de entorno descartada por el propietario

Apache Wamp activo en80, PHP global8.4.24 y sin vhost aliexpress. Preparado vhost127.0.0.2 con DocumentRoot public y FastCGI PHP7.3.33 exclusivo; httpd -t/-S PASS usando include de prueba, sin activar ni modificar Apache/.env. Ver sección R7.6.1 en REAL-NEXT-POST-DEV-FUNCTIONAL-RECOVERY-R76.md y docs/environment/aliexpress-apache-local.conf.example. La recarga/inclusión requiere consulta explícita del usuario. FR049/054/083/088/093/096/097/098 siguen pendientes;66 recuperadas+8 instalación+10 backend+23 excluidas=107. URL propuesta http://127.0.0.2/dashboard/principal, todavía no activa para esta aplicación. No backend/CSS/mutaciones/commit/push/merge.

## Historia R7.6 — 2026-11-25

Ver REAL-NEXT-POST-DEV-FUNCTIONAL-RECOVERY-R76.md. **4/12 restauradas**: FR017/018/036/046 (navegación crear/editar Normal, print eliminadas y Create Entry). Formularios DEV intactos, sin homologación nueva. **8/12 pendientes de instalación documental**, no de mera QA: FR049/054/083/088/093/096/097/098. PHP builtin :8080 entrega archivos existentes antes de Laravel; .htaccess DEV no aplica. HEAD anónimo placeholder200 frente a inexistente302/login demuestra dispatch; no documentos comerciales descargados. Cierre J/P por negocio preservado; DOC01 limitación local documentada, no nuevo finding.

Migración cupos Yes batch180 y ambas columnas existentes; no planes ni migraciones ejecutados aquí. Platform prerrequisito schema resuelto; UI/configuración fuera de alcance.

Contabilidad107 IDs: **66 recuperados +8 instalación pendiente +10 bloqueados backend +23 excluidos**. Pendientes activos18. No contar backend como UI. R7.7/R8 no iniciadas; no commit/push/merge. Cuatro tests renderer PASS,24 checks responsive PASS, Blade/JS/diffcheck PASS; mutaciones reales0.

## Historia anterior preservada

## Estado vigente R7.5 — 2026-11-25

Esta adenda prevalece sobre estados históricos; no borra evidencia anterior. Ver REAL-NEXT-DEV-DELIVERY-INTEGRATION-2026-11-25.md, REAL-NEXT-DEV-DELIVERY-RECONCILIATION-2026-11-25.md y REAL-NEXT-POST-DEV-FUNCTIONAL-RECOVERY-PLAN.md.

DEV41ed99f/0129133 (origin bf6e834) integrado. PROFILE01/MAT01/03/04/05/CASH02/03/04 con fixes comprobados en tests aislados; QA real mutador separado. C/Q parcial: NormalCreate/Edit y EntryCreate desbloqueados; nuevo ENTRY-R75-01: entry_edit exige edit_entryPurchase mientras ruta/DB usan update_entryPurchase. No seed ni bypass; DEV debe alinear. ENTRY-R4-01, INV01 ajuste/reportes, METRIC01 residual, DASH-R7-01 y FIN03 siguen abiertos. PUR-R3-01/02 fix presente, QA específico pendiente; impresión aún no recuperada. QA02 no cerrado globalmente.

Finanzas/legacysettings/access retirados; Express/FreeSale/industrial/outputlegacy pausados; SupplierCredits y restantes legacy fuera de recuperación. Invoice y QuoteSale vigentes. J/P/K CLOSED BY BUSINESS DECISION — TECHNICAL LIMITATIONS DOCUMENTED; no equivalen a certificación técnica.

107 FR únicos =62 recuperados +12 candidatos R7.6 +10 bloqueados +23 excluidos. Candidatos:017/018/036/046/049/054/083/088/093/096/097/098. Bloqueados:047/048/059/063/064/102/103/105/106/107. No nuevas recuperaciones contabilizadas por un fix backend. Universo197/40/157 permanece; R8 no iniciado.

Owner cambia deliberadamente flujo: login tenant-only, dashboard.owner aun con contexto. Se conserva FR104 Exportar entradas con permisos/contexto, sin ejecutar. Platform instalación PENDING: migración2026_11_21_000001 No, faltan columnas max_active_companies/branches. No migraciones/cupos/roles compartidos modificados. 71tests/832assertions SQLite PASS;9tests renderer PASS;40renders responsive sin overflow/pageerror. ContextPOST interceptado: el real escribe defaults y NO se ejecutó.

R7.6 GO WITH CONDITIONS; ningún reabrir automático GRE/Profile uploads/paquetes cerrados. Nuevo defecto ENTRY-R75-01 separado de hallazgos históricos. Backend propio0; CSS propio0; mutaciones negocio0; sin push.

## Evidencia histórica preservada

Fecha: 2026-11-24. Baseline HEAD 744d28b; R0/R1 sin commit. Registro canónico de recuperación frontend, no duplicado del backlog DEV.

## Estado vigente R7 (2026-11-25)

R7 autorizado tras R6. R0–R6 preservados; R8 no iniciado. Documento de evidencia: REAL-NEXT-DASHBOARD-FUNCTIONAL-PARITY-RECOVERY.md.

| Métrica canónica | Total |
|---|---:|
| Diferencias históricas individuales FR-001…107 |107|
| Excluidas por decisión legacy FR-022…034,037…045,080 |23|
| Capacidades activas en el registro de pérdidas |84|
| Recuperadas activas |62|
| Pendientes DEV con evidencia actual |22|
| Pendientes de auditoría semántica de operación dentro del registro |0|
| Pendientes de decisión de producto |0|
| Pendientes activas totales |22|

R7:8 pérdidas (2 originales +6 nuevas),1 recuperada FR-104 y7 bloqueadas. FR-063/064 ya auditadas: no retirar ni declarar resueltas. INV-01/METRIC-01 + nuevo DASH-R7-01. Las23 exclusiones no son éxitos. R4 conserva21 recuperadas/11 bloqueadas; R3.2 cerrado por scope legacy.

Reconciliación por197 IDs únicos originales (ledger completo en documento R7):40 revisadas/resueltas por producto =32 activas +8 legacy;157 pendientes semánticas. De32 activas:16 con paridad recuperada +4 ya disponibles +12 con blockers. Original158 UNKNOWN -10 revisadas adicionales +9 paridad sólo estática =157. No sumar destinos auxiliares ni capacidades como pantallas. QA operacional/export/documentos/proveedor separado;62 recuperadas no implica62 certificadas.

## Inventario y contabilidad histórica (no usar como estado actual)


- Baseline: 76 pérdidas confirmadas en 30 superficies. IDs FR-001…FR-076 estables: no renumerar al cerrar ni borrar filas.
- R1: 13 capacidades implementadas (11 General, 2 Error SUNAT). R2: FR-014/015/016 recuperadas. Al cierre R2:16/76 originales implementadas. R3.1 añade7 completas y2 parciales:23/76 completas,2/76 parciales,51/76 pendientes completas. No avanzar a R3.2 sin revisión.
- Nuevas pruebas de alcanzabilidad: FR-077/078 (rechazo NC y solución SUNAT) recuperadas. R2 añade FR-079 (sesión propia). R3.1 añade FR-080 (detalle Express desde General, pendiente DEV). Universo conocido ahora80; no es definitivo.
- 9 superficies con paridad estática parcial; 158 pendientes de contraste semántico. Ninguna se promociona por un token scan.
- Implementación y QA son estados distintos. IMPLEMENTED no significa mutación certificada. Documentos/proveedores y mutaciones reales no se ejecutaron.
- Los campos PENDING EXACT / PENDING HEAD OPERATION AUDIT son huecos de evidencia explícitos del inventario previo; no contratos inferidos ni capacidades cerradas. Se conservan para completar en la fase correspondiente, sin reabrir ahora otros módulos.

## Referencias y condiciones comunes

Origen: REAL-NEXT-GLOBAL-FUNCTIONAL-PARITY-AUDIT.md y REAL-NEXT-SALES-LIST-FUNCTIONAL-REGRESSION.md. R1: REAL-NEXT-SALES-FUNCTIONAL-PARITY-RECOVERY.md.

Motivo histórico general: INTENTIONAL READ-ONLY EXCLUSION; bulk: BACKEND SYNC gate. No equivalen a retiro permanente aprobado. Cada cierre exige recuperar trigger, handler, modal/DOM, permisos/estado y contrato vigente; probar presentación/cableado; registrar QA mutadora por separado y resolver cualquier diferencia pendiente con Producto.

DOC-01 (documentos/infra), EXT-01 (proveedor), QA-01 (exports), QA-02 (mantenedores), INV-01 (inventario), PUR-01 (compras), CASH-02/03/04 (políticas históricas) mantienen su identidad en REAL-NEXT-DEV-TEAM-FINDINGS-BACKLOG.md; no abrir clones. No afirmar que un finding histórico siga bloqueando cada endpoint sin contraste HEAD.

## Capacidades individuales

### FR-001 — Emitir

- Módulo/pantalla: `puntoVenta.list` / `puntoVenta.list`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Emitir. Commit de pérdida: `ffe0974`.
- Permiso: `manageDocuments_puntoVenta + generarBoleta_puntoVenta/generarFactura_puntoVenta`. Condición: Venta activa sin documento fiscal válido; tipo permitido por usuario.
- Endpoint: `POST /dashboard/sale/generate-invoice; FreeSale: /dashboard/ventas-libres/generate-invoice`. Handler: `data-generar_comprobante → openInvoice / prepareInvoice`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: SIMULATED WIRING PASS; REAL MUTATION QA PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-002 — Anular

- Módulo/pantalla: `puntoVenta.list` / `puntoVenta.list`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Anular. Commit de pérdida: `ffe0974`.
- Permiso: `annul_puntoVenta`. Condición: can_annul y no pending/waiting/accepted; SaleListGuard/DeferredSaleRefund.
- Endpoint: `POST /dashboard/anular/order/{id}; FreeSale: /dashboard/ventas-libres/{id}/anular`. Handler: `data-anular → act → mutate`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: SIMULATED WIRING PASS; REAL MUTATION QA PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-003 — Registrar abono

- Módulo/pantalla: `puntoVenta.list` / `puntoVenta.list`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Registrar abono. Commit de pérdida: `ffe0974`.
- Permiso: `managePayments_puntoVenta`. Condición: pagos_parciales_venta=s; caja/subtipo contextual; saldo/guard backend.
- Endpoint: `POST /dashboard/sale/partial-payment/store`. Handler: `btnGuardarPagoParcial`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: SIMULATED WIRING PASS; REAL MUTATION QA PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-004 — Eliminar abono

- Módulo/pantalla: `puntoVenta.list` / `puntoVenta.list`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar abono. Commit de pérdida: `ffe0974`.
- Permiso: `managePayments_puntoVenta`. Condición: Pago contextual y movimiento regularizable; guard payment.
- Endpoint: `DELETE /dashboard/sale/partial-payment/{id}`. Handler: `data-eliminar_pago_parcial`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: SIMULATED WIRING PASS; REAL MUTATION QA PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-005 — Despacho

- Módulo/pantalla: `puntoVenta.list` / `puntoVenta.list`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Despacho. Commit de pérdida: `ffe0974`.
- Permiso: `updateDispatch_puntoVenta`. Condición: Venta activa; estado pendiente/despachado.
- Endpoint: `POST /dashboard/sale/update-dispatch-status`. Handler: `data-cambiar_despacho → act (reemplaza switch visual, mismo payload)`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: SIMULATED WIRING PASS; REAL MUTATION QA PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-006 — NC total

- Módulo/pantalla: `puntoVenta.list` / `puntoVenta.list`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas). Parámetros requieren registro contextual existente.
- Funcionalidad original: NC total. Commit de pérdida: `ffe0974`.
- Permiso: `annul_puntoVenta`. Condición: can_generate_credit_note; sin NC pendiente/rechazada.
- Endpoint: `POST /dashboard/generar/nota-credito/total/{id}`. Handler: `data-generar_nota_credito → act`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: SIMULATED WIRING PASS; REAL MUTATION QA PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-007 — NC parcial

- Módulo/pantalla: `puntoVenta.list` / `puntoVenta.list`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas). Parámetros requieren registro contextual existente.
- Funcionalidad original: NC parcial. Commit de pérdida: `ffe0974`.
- Permiso: `annul_puntoVenta`. Condición: No FreeSale, doc01/03 válido, activa, sin NC pendiente/rechazada; Items elegibles del backend.
- Endpoint: `GET /dashboard/nota-credito/parcial/data/{id}; POST /dashboard/generar/nota-credito/parcial/{id}`. Handler: `data-generar_nota_credito_parcial → openPartial → btn-submit-nc-partial / btn-confirm-nc-partial-items`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: SIMULATED WIRING PASS; REAL MUTATION QA PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-008 — Historial abonos

- Módulo/pantalla: `puntoVenta.list` / `puntoVenta.list`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Historial abonos. Commit de pérdida: `ffe0974`.
- Permiso: `list_puntoVenta + SaleListGuard read`. Condición: pagos_parciales_venta=s; accesible con saldo0, sin permiso mutador.
- Endpoint: `GET /dashboard/sale/partial-payment/{sale_id}`. Handler: `data-pagos_parciales → showPayments / cargarPagosParciales`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: REAL DTO READ PASS + ISOLATED UI PASS (119, saldo0).
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-009 — Consultar anulación

- Módulo/pantalla: `puntoVenta.list` / `puntoVenta.list`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Consultar anulación. Commit de pérdida: `ffe0974`.
- Permiso: `annul_puntoVenta`. Condición: pending/waiting_sunat_process.
- Endpoint: `POST /dashboard/consultar/anulacion/{id}; FreeSale: /dashboard/ventas-libres/{id}/consultar-anulacion`. Handler: `data-consultar_anulacion → act`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: SIMULATED WIRING PASS; REAL MUTATION QA PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-010 — Consultar NC

- Módulo/pantalla: `puntoVenta.list` / `puntoVenta.list`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Consultar NC. Commit de pérdida: `ffe0974`.
- Permiso: `annul_puntoVenta`. Condición: has_pending_credit_note.
- Endpoint: `POST /dashboard/consultar/nota-credito/{id}`. Handler: `data-consultar_nota_credito → act`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: SIMULATED WIRING PASS; REAL MUTATION QA PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-011 — Recuperar Nubefact

- Módulo/pantalla: `puntoVenta.list` / `puntoVenta.list`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas](http://127.0.0.1:8080/dashboard/listado/ventas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Recuperar Nubefact. Commit de pérdida: `ffe0974`.
- Permiso: `manageDocuments_puntoVenta`. Condición: can_retry_nubefact_files.
- Endpoint: `POST /dashboard/ventas/{sale}/recuperar-archivos-nubefact`. Handler: `data-recuperar_archivos_nubefact → act`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: SIMULATED WIRING PASS; REAL MUTATION QA PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-012 — Descartar error

- Módulo/pantalla: `puntoVenta.listError` / `puntoVenta.listError`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas/error](http://127.0.0.1:8080/dashboard/listado/ventas/error). Parámetros requieren registro contextual existente.
- Funcionalidad original: Descartar error. Commit de pérdida: `b52c3cd`.
- Permiso: `annul_puntoVenta + acceso listErrors_puntoVenta`. Condición: can_discard_error; guard de conciliación backend.
- Endpoint: `POST /dashboard/sales/errors/{id}/discard`. Handler: `data-descartar_error_comprobante → discard`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado que impida recuperar este control; QA real separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: SIMULATED WIRING PASS; REAL MUTATION QA PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-013 — Imprimir documento

- Módulo/pantalla: `puntoVenta.listError` / `puntoVenta.listError`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ventas/error](http://127.0.0.1:8080/dashboard/listado/ventas/error). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir documento. Commit de pérdida: `b52c3cd`.
- Permiso: `print_puntoVenta`. Condición: print_url del backend validada como ruta protegida del mismo origen.
- Endpoint: `GET /dashboard/imprimir/documento/venta/{id} o /dashboard/sales/{id}/documents/{kind}`. Handler: `actions → safeDocument → enlace autorizado`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01; sólo rutas protegidas, nunca fallback público; apertura QA pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R1.
- QA independiente: PROTECTED LINK RENDER PASS (fixture); DOCUMENT OPEN NOT EXECUTED / DOC-01.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-014 — Abrir caja

- Módulo/pantalla: `cashMovement.admin.index` / `cashBox.admin_index`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/cash-movements/admin](http://127.0.0.1:8080/dashboard/cash-movements/admin). Parámetros requieren registro contextual existente.
- Funcionalidad original: Abrir caja. Commit de pérdida: `744d28b`.
- Permiso: Auth/contexto activo SELF; no permission granular en cashRegister.open. Admin exige listCashMovementAdmin_cashBox, no abre para otro actor. Condición: caja activa contextual y sesión propia.
- Endpoint: `POST /dashboard/cash-registers/open`. Handler: `data-cashbox-btn → CASH_REGISTER_OPEN_URL`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker nuevo demostrado para recuperar el control. CASH-02/03/04 y DOC-01 permanecen abiertos según capacidad; ver informe R2.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R2.
- QA independiente: STATIC CONTRACT + ISOLATED UI PASS; REAL MUTATION / DOCUMENT QA PENDING. Ninguna operación real ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-015 — Regularizar

- Módulo/pantalla: `cashMovement.admin.index` / `cashBox.admin_index`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/cash-movements/admin](http://127.0.0.1:8080/dashboard/cash-movements/admin). Parámetros requieren registro contextual existente.
- Funcionalidad original: Regularizar. Commit de pérdida: `744d28b`.
- Permiso: `regularize_caja`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `POST /dashboard/cash-registers/regularize`. Handler: `data-regularizar → formRegularize → regularization.js`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker nuevo demostrado para recuperar el control. CASH-02/03/04 y DOC-01 permanecen abiertos según capacidad; ver informe R2.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R2.
- QA independiente: STATIC CONTRACT + ISOLATED UI PASS; REAL MUTATION / DOCUMENT QA PENDING. Ninguna operación real ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-016 — Documento

- Módulo/pantalla: `cashMovement.admin.index` / `cashBox.admin_index`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/cash-movements/admin](http://127.0.0.1:8080/dashboard/cash-movements/admin). Parámetros requieren registro contextual existente.
- Funcionalidad original: Documento. Commit de pérdida: `744d28b`.
- Permiso: print_puntoVenta para venta, list_invoice para factura. Guard SaleListGuard / FinanceInvoiceContext en destino. Condición: sale_id o, en su ausencia, entry_id contextual.
- Endpoint: `GET /dashboard/imprimir/documento/venta/{sale_id}; legacy Entry /dashboard/factura/compra/ver/{entry_id}`. Handler: `data-print_nota → enlace por sale_id/entry_id`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker nuevo demostrado para recuperar el control. CASH-02/03/04 y DOC-01 permanecen abiertos según capacidad; ver informe R2.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R2.
- QA independiente: STATIC CONTRACT + ISOLATED UI PASS; REAL MUTATION / DOCUMENT QA PENDING. Ninguna operación real ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-017 — Crear

- **R7.6 vigente:** ACTIVE — RESTORED; navegación y permisos PASS, ejecución mutadora/documental real pendiente. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Módulo/pantalla: `order.purchase.normal.indexV2` / `orderPurchase.normal_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear. Commit de pérdida: `abc9daa`.
- Permiso: `create_orderPurchaseNormal`. Condición: Acceso autorizado; lookups/GET requieren su contrato actual.
- Endpoint: `GET /dashboard/crear/orden/compra/normal`. Handler: `Enlace toolbar; route order.purchase.normal.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: BACKEND BLOCKER — R3.1; usuario eligió documentar pendiente DEV, no retiro.
- QA independiente: Auditoría estática HEAD; destino inseguro no ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-018 — Editar

- **R7.6 vigente:** ACTIVE — RESTORED; navegación y permisos PASS, ejecución mutadora/documental real pendiente. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Módulo/pantalla: `order.purchase.normal.indexV2` / `orderPurchase.normal_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar. Commit de pérdida: `abc9daa`.
- Permiso: `update_orderPurchaseNormal`. Condición: Orden propia editable; credit lock si Finance.
- Endpoint: `GET /dashboard/editar/orden/compra/normal/{id}`. Handler: `data-editar / enlace edición`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: BACKEND BLOCKER — R3.1; usuario eligió documentar pendiente DEV, no retiro.
- QA independiente: Auditoría estática HEAD; destino inseguro no ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-019 — Anular

- Módulo/pantalla: `order.purchase.normal.indexV2` / `orderPurchase.normal_v2`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Anular. Commit de pérdida: `abc9daa`.
- Permiso: `destroy_orderPurchaseNormal`. Condición: Orden propia; estado admitido por backend.
- Endpoint: `POST /dashboard/destroy/order/purchase/normal/{id}`. Handler: `data-delete / data-anular → cancelOrden`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; ejecución real mutadora/documental pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: RESTORED R3.1.
- QA independiente: Renderer/cableado aislado PASS; mutaciones/documentos reales PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-020 — Cambiar estado

- Módulo/pantalla: `order.purchase.normal.indexV2` / `orderPurchase.normal_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Cambiar estado. Commit de pérdida: `abc9daa`.
- Permiso: `update_orderPurchaseNormal (ruta compartida; revisar permiso Express)`. Condición: Estado actual admite transición.
- Endpoint: `GET /dashboard/get/state/order/purchase/{id}` + `POST /dashboard/change/state/order/purchase`; payload `orderPurchase_id,stateOrder`. Handler: `data-estado → modalState → btn-changeState`. HEAD sólo admite Normal.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; ejecución real mutadora/documental pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: RESTORED R3.1.
- QA independiente: Renderer/cableado aislado PASS; mutaciones/documentos reales PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-021 — Imprimir

- Módulo/pantalla: `order.purchase.normal.indexV2` / `orderPurchase.normal_v2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `abc9daa`.
- Permiso: `list_orderPurchaseNormal`. Condición: Documento propio; DOC-01 pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/{id}`. Handler: `data-imprimir / enlace documental`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; ejecución real mutadora/documental pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: RESTORED R3.1.
- QA independiente: Renderer/cableado aislado PASS; mutaciones/documentos reales PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-022 — Crear normal

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear normal. Commit de pérdida: `b1fa9d5`.
- Permiso: `create_orderPurchaseNormal`. Condición: Acceso autorizado; lookups/GET requieren su contrato actual.
- Endpoint: `GET /dashboard/crear/orden/compra/normal`. Handler: `Enlace toolbar; route order.purchase.normal.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Auditoría estática HEAD; destino inseguro no ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-023 — Crear express

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear express. Commit de pérdida: `b1fa9d5`.
- Permiso: `create_orderPurchaseExpress`. Condición: Acceso autorizado; lookups/GET requieren su contrato actual.
- Endpoint: `GET /dashboard/crear/orden/compra/express`. Handler: `Enlace toolbar; route order.purchase.express.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Auditoría estática HEAD; destino inseguro no ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-024 — Editar

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar. Commit de pérdida: `b1fa9d5`.
- Permiso: `update_orderPurchaseNormal`. Condición: Orden propia editable; credit lock si Finance.
- Endpoint: `GET /dashboard/editar/orden/compra/normal/{id}`. Handler: `data-editar / enlace edición`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Auditoría estática HEAD; destino inseguro no ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-025 — Anular

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Anular. Commit de pérdida: `b1fa9d5`.
- Permiso: `destroy_orderPurchaseNormal`. Condición: Orden propia; estado admitido por backend.
- Endpoint: `POST /dashboard/destroy/order/purchase/normal/{id}`. Handler: `data-delete / data-anular → cancelOrden`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Renderer/cableado aislado PASS; mutaciones/documentos reales PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-026 — Cambiar estado

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Cambiar estado. Commit de pérdida: `b1fa9d5`.
- Permiso: `update_orderPurchaseNormal (ruta compartida; revisar permiso Express)`. Condición: Estado actual admite transición.
- Endpoint: `GET /dashboard/get/state/order/purchase/{id}` + `POST /dashboard/change/state/order/purchase`; payload `orderPurchase_id,stateOrder`. Handler: `data-estado → modalState → btn-changeState`. HEAD sólo admite Normal.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Renderer/cableado aislado PASS; mutaciones/documentos reales PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-027 — Imprimir

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `b1fa9d5`.
- Permiso: `list_orderPurchaseNormal`. Condición: Documento propio; DOC-01 pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/{id}`. Handler: `data-imprimir / enlace documental`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; ejecución real mutadora/documental pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Renderer/cableado aislado PASS; mutaciones/documentos reales PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-028 — Exportar

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Exportar. Commit de pérdida: `b1fa9d5`.
- Permiso: `list_orderPurchaseExpress en ruta compartida HEAD; revisar correspondencia con listado General antes de recuperar`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/exportar/reporte/ordenes/compra/v2/`. Handler: `btn-export → exportExcel (baseline 2ec61f3^)`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; ejecución real mutadora/documental pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Renderer/cableado aislado PASS; mutaciones/documentos reales PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-029 — Crear

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear. Commit de pérdida: `3ed0b46`.
- Permiso: `create_orderPurchaseExpress`. Condición: Acceso autorizado; lookups/GET requieren su contrato actual.
- Endpoint: `GET /dashboard/crear/orden/compra/express`. Handler: `Enlace toolbar; route order.purchase.express.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Auditoría estática HEAD; destino inseguro no ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-030 — Editar

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar. Commit de pérdida: `3ed0b46`.
- Permiso: `update_orderPurchaseExpress`. Condición: Orden propia editable; credit lock si Finance.
- Endpoint: `GET /dashboard/editar/orden/compra/express/{id}`. Handler: `data-editar / enlace edición`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Auditoría estática HEAD; destino inseguro no ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-031 — Anular

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Anular. Commit de pérdida: `3ed0b46`.
- Permiso: `destroy_orderPurchaseExpress`. Condición: Orden propia; estado admitido por backend.
- Endpoint vigente Express: `POST /dashboard/destroy/order/purchase/express/{id}`, permiso ruta `update_orderPurchaseExpress`; legacy UI usaba `destroy_orderPurchaseExpress` y enviaba erróneamente a Normal. No reconectado por PUR-R3-01.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Auditoría estática HEAD; destino inseguro no ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-032 — Cambiar estado

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Cambiar estado. Commit de pérdida: `3ed0b46`.
- Permiso: `update_orderPurchaseNormal (ruta compartida; revisar permiso Express)`. Condición: Estado actual admite transición.
- Endpoint: `GET /dashboard/get/state/order/purchase/{id}` + `POST /dashboard/change/state/order/purchase`; payload `orderPurchase_id,stateOrder`. Handler: `data-estado → modalState → btn-changeState`. HEAD sólo admite Normal.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Auditoría estática HEAD; destino inseguro no ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-033 — Imprimir

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `3ed0b46`.
- Permiso: `list_orderPurchaseExpress`. Condición: Documento propio; DOC-01 pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/{id}`. Handler: `data-imprimir / enlace documental`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; ejecución real mutadora/documental pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Renderer/cableado aislado PASS; mutaciones/documentos reales PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-034 — Detalle

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Detalle. Commit de pérdida: `3ed0b46`.
- Permiso: `list_orderPurchaseExpress`. Condición: Orden visible en contexto.
- Endpoint: `GET /dashboard/ver/orden/compra/express/{id}`. Handler: `data-ver_orden / enlace detalle`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: Auditoría estática HEAD; destino inseguro no ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-035 — Restaurar

- Módulo/pantalla: `order.purchase.delete` / `orderPurchase.deleteGeneral`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas](http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Restaurar. Commit de pérdida: `c32352c`.
- Permiso: `destroy_orderPurchaseNormal`. Condición: Orden eliminada propia; referencias y restauración backend.
- Endpoint: `POST /dashboard/restore/order/purchase/delete/{id}`. Handler: `data-restore → restoreOrden`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; ejecución real mutadora/documental pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: RESTORED R3.1.
- QA independiente: Renderer/cableado aislado PASS; mutaciones/documentos reales PENDING.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-036 — Imprimir

- **R7.6 vigente:** ACTIVE — RESTORED; navegación y permisos PASS, ejecución mutadora/documental real pendiente. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Módulo/pantalla: `order.purchase.delete` / `orderPurchase.deleteGeneral`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas](http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `c32352c`.
- Permiso: `list_orderPurchaseNormal`. Condición: Documento propio; DOC-01 pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/eliminada/{id}`. Handler: `data-imprimir / enlace documental`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ver matriz R3.1 y paquete Q; FR-025/031: PUR-R3-01; FR-036: PUR-R3-02; Express estado sin contrato propio vigente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: BACKEND BLOCKER — R3.1; usuario eligió documentar pendiente DEV, no retiro.
- QA independiente: Auditoría estática HEAD; destino inseguro no ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-037 — Crear

- Módulo/pantalla: `order.purchase.finance.index` / `orderPurchaseFinance.indexOrderFinance`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear. Commit de pérdida: `483a785`.
- Permiso: `create_orderPurchaseFinance`. Condición: Acceso autorizado; lookups/GET requieren su contrato actual.
- Endpoint: `GET /dashboard/crear/orden/compra/finanzas`. Handler: `Enlace toolbar; route order.purchase.finance.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: No blocker backend nuevo demostrado para el control; vigencia de producto pendiente por ocultación histórica del sidebar. Ver informe R3.2, guard financiero/contexto y límites documentales.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-038 — Detalle

- Módulo/pantalla: `order.purchase.finance.index` / `orderPurchaseFinance.indexOrderFinance`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Detalle. Commit de pérdida: `483a785`.
- Permiso: `list_orderPurchaseFinance`. Condición: Orden visible en contexto.
- Endpoint: `GET /dashboard/ver/orden/compra/finanzas/{id}`. Handler: `data-ver_orden / enlace detalle`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: No blocker backend nuevo demostrado para el control; vigencia de producto pendiente por ocultación histórica del sidebar. Ver informe R3.2, guard financiero/contexto y límites documentales.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-039 — Editar

- Módulo/pantalla: `order.purchase.finance.index` / `orderPurchaseFinance.indexOrderFinance`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar. Commit de pérdida: `483a785`.
- Permiso: `update_orderPurchaseFinance`. Condición: Orden propia editable; credit lock si Finance.
- Endpoint: `GET /dashboard/editar/orden/compra/finanza/{id}`. Handler: `data-editar / enlace edición`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: No blocker backend nuevo demostrado para el control; vigencia de producto pendiente por ocultación histórica del sidebar. Ver informe R3.2, guard financiero/contexto y límites documentales.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-040 — Eliminar

- Módulo/pantalla: `order.purchase.finance.index` / `orderPurchaseFinance.indexOrderFinance`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar. Commit de pérdida: `483a785`.
- Permiso: `destroy_orderPurchaseFinance`. Condición: Orden propia; estado admitido por backend.
- Endpoint: `POST /dashboard/destroy/order/purchase/finance/{id}`. Handler: `data-delete / data-anular → cancelOrden`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: No blocker backend nuevo demostrado para el control; vigencia de producto pendiente por ocultación histórica del sidebar. Ver informe R3.2, guard financiero/contexto y límites documentales.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-041 — Imprimir

- Módulo/pantalla: `order.purchase.finance.index` / `orderPurchaseFinance.indexOrderFinance`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `483a785`.
- Permiso: `list_orderPurchaseFinance`. Condición: Documento propio; DOC-01 pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/finanza/{id}`. Handler: `data-imprimir / enlace documental`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01; sólo rutas protegidas, nunca fallback público; apertura QA pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-042 — Restaurar

- Módulo/pantalla: `order.purchase.finance.delete` / `orderPurchaseFinance.indexFinanceDeleted`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/eliminadas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/eliminadas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Restaurar. Commit de pérdida: `744d28b`.
- Permiso: `destroy_orderPurchaseFinance`. Condición: Orden eliminada propia; referencias y restauración backend.
- Endpoint: `POST /dashboard/restore/order/purchase/finance/delete/{id}`. Handler: `data-restore → restoreOrden`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: No blocker backend nuevo demostrado para el control; vigencia de producto pendiente por ocultación histórica del sidebar. Ver informe R3.2, guard financiero/contexto y límites documentales.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-043 — Imprimir

- Módulo/pantalla: `order.purchase.finance.delete` / `orderPurchaseFinance.indexFinanceDeleted`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/eliminadas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/eliminadas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `744d28b`.
- Permiso: `list_orderPurchaseFinance`. Condición: Documento propio; DOC-01 pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/finanza/eliminada/{id}`. Handler: `data-imprimir / enlace documental`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01; sólo rutas protegidas, nunca fallback público; apertura QA pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-044 — Detalle

- Módulo/pantalla: `order.purchase.finance.list.regularize` / `orderPurchaseFinance.indexRegularizeFinance`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/regularizadas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/regularizadas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Detalle. Commit de pérdida: `744d28b`.
- Permiso: `list_orderPurchaseFinance`. Condición: Orden visible en contexto.
- Endpoint: `GET /dashboard/ver/orden/compra/finanzas/{id}`. Handler: `data-ver_orden / enlace detalle`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: No blocker backend nuevo demostrado para el control; vigencia de producto pendiente por ocultación histórica del sidebar. Ver informe R3.2, guard financiero/contexto y límites documentales.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-045 — Imprimir

- Módulo/pantalla: `order.purchase.finance.list.regularize` / `orderPurchaseFinance.indexRegularizeFinance`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/regularizadas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/regularizadas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `744d28b`.
- Permiso: `list_orderPurchaseFinance`. Condición: Documento propio; DOC-01 pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/finanza/{id}`. Handler: `data-imprimir / enlace documental`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01; sólo rutas protegidas, nunca fallback público; apertura QA pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED. Evidencia/implementación histórica preservada; no cuenta como recuperación activa.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-046 — Crear

- **R7.6 vigente:** ACTIVE — RESTORED; navegación y permisos PASS, ejecución mutadora/documental real pendiente. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Módulo/pantalla: `entry.purchase.indexV2` / `entry.index_entry_purchasev2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2](http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear. Commit de pérdida: `2ec61f3`.
- Permiso: `create_entryPurchase`. Condición: Entry propia; guard/backend y estado de recepción.
- Endpoint: `GET /dashboard/crear/entrada/compra`. Handler: `Enlace toolbar`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: INV-01: preload StockItems/inventoryLevels sin company/permiso
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — BACKEND BLOCKER R4.
- QA independiente: Static contract evidence; unsafe operation not executed.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-047 — Editar

- Módulo/pantalla: `entry.purchase.indexV2` / `entry.index_entry_purchasev2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2](http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar. Commit de pérdida: `2ec61f3`.
- Permiso: `update_entryPurchase`. Condición: Entry propia; guard/backend y estado de recepción.
- Endpoint: `GET /dashboard/entrada/compra/editar/{entry}`. Handler: `data-edit / enlace edición`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: INV-01: preload; edit no restringe finance/entry_type
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — BACKEND BLOCKER R4.
- QA independiente: Static contract evidence; unsafe operation not executed.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-048 — Anular

- Módulo/pantalla: `entry.purchase.indexV2` / `entry.index_entry_purchasev2`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2](http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Anular. Commit de pérdida: `2ec61f3`.
- Permiso: `destroy_entryPurchase`. Condición: Entry propia; guard/backend y estado de recepción.
- Endpoint: `POST /dashboard/entry_purchase/destroy/{entry}`. Handler: `data-delete / handler de anulación`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: ENTRY-R4-01: falta exclusión explícita finance=true
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — BACKEND BLOCKER R4.
- QA independiente: Static contract evidence; unsafe operation not executed.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-049 — Documentos

- **R7.6.1 vigente: FRONTEND RECOVERED.** Lector autorizado DEV, permiso de familia preservado; QA frontend interceptado PASS. Protección HTTP directa de despliegue PENDING, independiente de recuperación. Detalles anteriores conservados como historia.


- **Historia R7.6:** ACTIVE — INSTALLATION REQUIRED; lector DEV presente pero servidor :8080 omite autorización de archivos físicos. DOC-01; no restaurado. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Módulo/pantalla: `entry.purchase.indexV2` / `entry.index_entry_purchasev2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2](http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Documentos. Commit de pérdida: `2ec61f3`.
- Permiso: `update_entryPurchase para documentos extras; adjuntos públicos pendientes DOC-01`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/agregar/documentos/extras/entrada/{id}; legacy /images/entries/{image}`. Handler: `data-documentos; data-pdf; data-image → showImage`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — BACKEND BLOCKER R4.
- QA independiente: Static contract evidence; unsafe operation not executed.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-050 — Recibir entrada

- Módulo/pantalla: `order.purchase.list.indexV2` / `entry.listOrderPurchaseV2`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2](http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Recibir entrada. Commit de pérdida: `2ec61f3`.
- Permiso: `create_entryPurchase; can_receive=true`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/crear/entrada/compra/orden/{id}`. Handler: `data-crear_entrada → enlace`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; QA mutadora/documental separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R4.
- QA independiente: HEAD contract + isolated UI wiring; no real mutation/document/export.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-051 — Ver orden

- Módulo/pantalla: `order.purchase.list.indexV2` / `entry.listOrderPurchaseV2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2](http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Ver orden. Commit de pérdida: `2ec61f3`.
- Permiso: `list_orderPurchaseNormal`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/ver/orden/compra/normal/{id}`. Handler: `data-ver_orden → enlace`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; QA mutadora/documental separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R4.
- QA independiente: HEAD contract + isolated UI wiring; no real mutation/document/export.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-052 — Imprimir

- Módulo/pantalla: `order.purchase.list.indexV2` / `entry.listOrderPurchaseV2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2](http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `2ec61f3`.
- Permiso: `list_orderPurchaseNormal`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/{id}`. Handler: `data-imprimir → enlace`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; QA mutadora/documental separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R4.
- QA independiente: HEAD contract + isolated UI wiring; no real mutation/document/export.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-053 — Detalle de ítems

- Módulo/pantalla: `entry.purchase.report.index` / `entry.index_entry_purchase_report`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/entradas/compra/reporte](http://127.0.0.1:8080/dashboard/entradas/compra/reporte). Parámetros requieren registro contextual existente.
- Funcionalidad original: Detalle de ítems. Commit de pérdida: `6097da5`.
- Permiso: `Sin permission middleware específico en get/json/items; revisar guard ItemController antes de recuperar (INV-01)`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/get/json/items/{entry_id}`. Handler: `showItems; confirmar trigger efectivo al recuperar reporte`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; QA mutadora/documental separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R4.
- QA independiente: HEAD contract + isolated UI wiring; no real mutation/document/export.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-054 — Adjunto

- **R7.6.1 vigente: FRONTEND RECOVERED.** Lector autorizado DEV, permiso de familia preservado; QA frontend interceptado PASS. Protección HTTP directa de despliegue PENDING, independiente de recuperación. Detalles anteriores conservados como historia.


- **Historia R7.6:** ACTIVE — INSTALLATION REQUIRED; lector DEV presente pero servidor :8080 omite autorización de archivos físicos. DOC-01; no restaurado. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Módulo/pantalla: `entry.purchase.report.index` / `entry.index_entry_purchase_report`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/entradas/compra/reporte](http://127.0.0.1:8080/dashboard/entradas/compra/reporte). Parámetros requieren registro contextual existente.
- Funcionalidad original: Adjunto. Commit de pérdida: `6097da5`.
- Permiso: `Sin autorización de archivo demostrada; DOC-01`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /images/entries/{image} (legacy público; NO recuperar como fallback)`. Handler: `data-image → showImage; enlace adjunto`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — BACKEND BLOCKER R4.
- QA independiente: Static contract evidence; unsafe operation not executed.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-055 — Crear

- Módulo/pantalla: `invoice.index` / `invoice.index_invoice`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/factura/compra](http://127.0.0.1:8080/dashboard/factura/compra). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear. Commit de pérdida: `fa8f7ed`.
- Permiso: `create_invoice`. Condición: FinanceInvoiceContext y validación backend.
- Endpoint: `GET /dashboard/crear/factura/compra`. Handler: `Enlace invoice.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; QA mutadora/documental separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R4.
- QA independiente: HEAD contract + isolated UI wiring; no real mutation/document/export.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-056 — Anular

- Módulo/pantalla: `invoice.index` / `invoice.index_invoice`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/factura/compra](http://127.0.0.1:8080/dashboard/factura/compra). Parámetros requieren registro contextual existente.
- Funcionalidad original: Anular. Commit de pérdida: `fa8f7ed`.
- Permiso: `destroy_invoice`. Condición: FinanceInvoiceContext y validación backend.
- Endpoint: `POST /dashboard/destroy/total/invoice/{id}`. Handler: `data-delete → deleteItem`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; QA mutadora/documental separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R4.
- QA independiente: HEAD contract + isolated UI wiring; no real mutation/document/export.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-057 — Exportar

- Módulo/pantalla: `report.invoice.finance` / `invoice.report_invoice`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas](http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Exportar. Commit de pérdida: `fa8f7ed`.
- Permiso: `list_invoice`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/exportar/reporte/factura`. Handler: `btn-export → exportExcel`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; QA mutadora/documental separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R4.
- QA independiente: HEAD contract + isolated UI wiring; no real mutation/document/export.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-058 — Exportar

- Módulo/pantalla: `report.invoice.finance.sin.orden` / `invoice.report_invoice_sin_orden`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas/sin/orden](http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas/sin/orden). Parámetros requieren registro contextual existente.
- Funcionalidad original: Exportar. Commit de pérdida: `fa8f7ed`.
- Permiso: `list_invoice`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/exportar/reporte/factura`. Handler: `btn-export → exportExcel`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Sin blocker para el control recuperado; QA mutadora/documental separada.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: IMPLEMENTED R4.
- QA independiente: HEAD contract + isolated UI wiring; no real mutation/document/export.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-059 — Ajustar stock

- Módulo/pantalla: `stockitems.index` / `stockItem.list`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/stock-items](http://127.0.0.1:8080/dashboard/stock-items). Parámetros requieren registro contextual existente.
- Funcionalidad original: Ajustar stock. Commit de pérdida: `6de4406`.
- Permiso: `MISSING EXPLICIT PERMISSION (INV-01 familia; evidencia en auditoría global)`. Condición: NO reactivar sin ownership y autorización demostrados.
- Endpoint: `POST /dashboard/fix/stock-item/output-adjustment`. Handler: `modalAdjustStock / handler ajuste legacy`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: INV-01 confirmado R6: falta permiso explícito y selección company/local de StockItem/lotes/Items; ajuste no idempotente certificado.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: BACKEND BLOCKER — R6; no restaurar ajuste inseguro.
- QA independiente: Auditoría estática HEAD; no POST ejecutado.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-060 — Mínimo/máximo

- Módulo/pantalla: `stockitems.index` / `stockItem.list`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/stock-items](http://127.0.0.1:8080/dashboard/stock-items). Parámetros requieren registro contextual existente.
- Funcionalidad original: Mínimo/máximo. Commit de pérdida: `6de4406`.
- Permiso: `update_material + MaterialInventoryGuard`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `POST /dashboard/stock-items/{stockItem}/inventory-levels`. Handler: `btn-save-inventory-levels / inventory-context.js`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno para reconectar contrato HEAD. Mutación real pendiente; MAT-01 global no se cierra por flags.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: RESTORED R6 — FRONTEND GATE OBSOLETE removido; contrato actual preservado.
- QA independiente: R6 SQLite + Chrome headless interceptado; cero mutaciones DB real. Ver REAL-NEXT-STOCKITEMS-ITEMS-FUNCTIONAL-PARITY-RECOVERY.md.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-061 — Inventariable

- Módulo/pantalla: `stockitems.index` / `stockItem.list`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/stock-items](http://127.0.0.1:8080/dashboard/stock-items). Parámetros requieren registro contextual existente.
- Funcionalidad original: Inventariable. Commit de pérdida: `6de4406`.
- Permiso: `configInventariable_material + StockItemScreenGuard`. Condición: MaterialInventoryGuard / contexto actual.
- Endpoint: `POST /dashboard/stock-items/{id}/toggle-inventory`. Handler: `updateInventoryToggle`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno para reconectar contrato HEAD. Mutación real pendiente; MAT-01 global no se cierra por flags.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: RESTORED R6 — FRONTEND GATE OBSOLETE removido; contrato actual preservado.
- QA independiente: R6 SQLite + Chrome headless interceptado; cero mutaciones DB real. Ver REAL-NEXT-STOCKITEMS-ITEMS-FUNCTIONAL-PARITY-RECOVERY.md.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-062 — Activo

- Módulo/pantalla: `stockitems.index` / `stockItem.list`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/stock-items](http://127.0.0.1:8080/dashboard/stock-items). Parámetros requieren registro contextual existente.
- Funcionalidad original: Activo. Commit de pérdida: `6de4406`.
- Permiso: `configActive_material + StockItemScreenGuard`. Condición: MaterialInventoryGuard / contexto actual.
- Endpoint: `POST /dashboard/stock-items/{id}/toggle-active`. Handler: `updateActiveToggle`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno para reconectar contrato HEAD. Mutación real pendiente; MAT-01 global no se cierra por flags.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: RESTORED R6 — FRONTEND GATE OBSOLETE removido; contrato actual preservado.
- QA independiente: R6 SQLite + Chrome headless interceptado; cero mutaciones DB real. Ver REAL-NEXT-STOCKITEMS-ITEMS-FUNCTIONAL-PARITY-RECOVERY.md.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-063 — Exportar importes

- Módulo/pantalla: `dashboard.principal` / `dashboard.dashboard`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/principal](http://127.0.0.1:8080/dashboard/principal). Parámetros requieren registro contextual existente.
- Funcionalidad original: Exportar importes. Commit de pérdida: `dbca24e1`.
- Permiso: showBoxAlmacen_dashboard sólo visual; endpoint sin capacidad funcional explícita. No Owner bypass.
- Endpoint: `GET /dashboard/report/excel/amount/stock`. Handler: `Enlace report.excel.amount`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: INV-01 / METRIC-01; ReportController conserva inventario legacy sin company explícita y sin guard granular. Contrato completo auditado en R7.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — BACKEND BLOCKER (R7).
- QA independiente: STATIC CONTRACT REVIEW PASS; export real no ejecutado; no disponible hasta fix.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-064 — Exportar materiales

- Módulo/pantalla: `dashboard.principal` / `dashboard.dashboard`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/principal](http://127.0.0.1:8080/dashboard/principal). Parámetros requieren registro contextual existente.
- Funcionalidad original: Exportar materiales. Commit de pérdida: `dbca24e1`.
- Permiso: showBoxAlmacen_dashboard sólo visual; endpoint sin capacidad funcional explícita. No Owner bypass.
- Endpoint: `GET /dashboard/report/excel/bd/materials`. Handler: `Enlace report.excel.materials`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: INV-01 / METRIC-01; ReportController conserva inventario legacy sin company explícita y sin guard granular. Contrato completo auditado en R7.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — BACKEND BLOCKER (R7).
- QA independiente: STATIC CONTRACT REVIEW PASS; export real no ejecutado; no disponible hasta fix.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-065 — Editar código ítem

- Módulo/pantalla: `material.stock.getItems` / `material.stockItems_items`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/view/stock/material/items/81](http://127.0.0.1:8080/dashboard/view/stock/material/items/81). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar código ítem. Commit de pérdida: `6de4406`.
- Permiso: `editarCodeItems_material`. Condición: StockItemScreenGuard; Item propio.
- Endpoint: `PUT /dashboard/stock/material/item/{item}/code`. Handler: `btn-edit-item-code / modalEditarCodigoItem / btnGuardarCodigoItem; items.update.code`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Ninguno para reconectar contrato HEAD. Mutación real pendiente; MAT-01 global no se cierra por flags.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: RESTORED R6 — FRONTEND GATE OBSOLETE removido; contrato actual preservado.
- QA independiente: R6 SQLite + Chrome headless interceptado; cero mutaciones DB real. Ver REAL-NEXT-STOCKITEMS-ITEMS-FUNCTIONAL-PARITY-RECOVERY.md.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-066 — Eliminar selección

- Módulo/pantalla: `materialtype.index` / `materialtype.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/TiposMateriales](http://127.0.0.1:8080/dashboard/TiposMateriales). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_materialtype`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/materialtype/delete-multiple`. Handler: `gate retirado R5; handler delete-multiple en public/js/materialtype/index.js`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado para bulk en HEAD. a8582b9/ec5f6dd implementan autorización, request y servicio; QA-02 no bloquea visibilidad.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — RESTORED (R5); gate obsoleto retirado. Ver matriz individual R5.
- QA independiente: SQLite aislado + Chrome interceptado; sin deletes DB compartida. Mutación real pendiente.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-067 — Eliminar selección

- Módulo/pantalla: `subtype.index` / `subtype.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Subtipos](http://127.0.0.1:8080/dashboard/Subtipos). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_subtype`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/subtype/delete-multiple`. Handler: `gate retirado R5; handler delete-multiple en public/js/subtype/index.js`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado para bulk en HEAD. a8582b9/ec5f6dd implementan autorización, request y servicio; QA-02 no bloquea visibilidad.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — RESTORED (R5); gate obsoleto retirado. Ver matriz individual R5.
- QA independiente: SQLite aislado + Chrome interceptado; sin deletes DB compartida. Mutación real pendiente.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-068 — Eliminar selección

- Módulo/pantalla: `category.index` / `category.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Categorias](http://127.0.0.1:8080/dashboard/Categorias). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_category`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/category/delete-multiple`. Handler: `gate retirado R5; handler delete-multiple en public/js/Category/index.js`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado para bulk en HEAD. a8582b9/ec5f6dd implementan autorización, request y servicio; QA-02 no bloquea visibilidad.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — RESTORED (R5); gate obsoleto retirado. Ver matriz individual R5.
- QA independiente: SQLite aislado + Chrome interceptado; sin deletes DB compartida. Mutación real pendiente.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-069 — Eliminar selección

- Módulo/pantalla: `subcategory.index` / `subcategory.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Subcategorias](http://127.0.0.1:8080/dashboard/Subcategorias). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_subcategory`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/subcategory/delete-multiple`. Handler: `gate retirado R5; handler delete-multiple en public/js/subcategory/index.js`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado para bulk en HEAD. a8582b9/ec5f6dd implementan autorización, request y servicio; QA-02 no bloquea visibilidad.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — RESTORED (R5); gate obsoleto retirado. Ver matriz individual R5.
- QA independiente: SQLite aislado + Chrome interceptado; sin deletes DB compartida. Mutación real pendiente.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-070 — Eliminar selección

- Módulo/pantalla: `exampler.index` / `exampler.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Modelos](http://127.0.0.1:8080/dashboard/Modelos). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_exampler`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/exampler/delete-multiple`. Handler: `gate retirado R5; handler delete-multiple en public/js/exampler/index.js`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado para bulk en HEAD. a8582b9/ec5f6dd implementan autorización, request y servicio; QA-02 no bloquea visibilidad.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — RESTORED (R5); gate obsoleto retirado. Ver matriz individual R5.
- QA independiente: SQLite aislado + Chrome interceptado; sin deletes DB compartida. Mutación real pendiente.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-071 — Eliminar selección

- Módulo/pantalla: `brand.index` / `brand.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Marcas](http://127.0.0.1:8080/dashboard/Marcas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_brand`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/brand/delete-multiple`. Handler: `gate retirado R5; handler delete-multiple en public/js/brand/index.js`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado para bulk en HEAD. a8582b9/ec5f6dd implementan autorización, request y servicio; QA-02 no bloquea visibilidad.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — RESTORED (R5); gate obsoleto retirado. Ver matriz individual R5.
- QA independiente: SQLite aislado + Chrome interceptado; sin deletes DB compartida. Mutación real pendiente.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-072 — Eliminar selección

- Módulo/pantalla: `typescrap.index` / `typeScrap.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Retacerías](http://127.0.0.1:8080/dashboard/Retacerías). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_typescrap`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/typescrap/delete-multiple`. Handler: `gate retirado R5; handler delete-multiple en public/js/typescrap/index.js`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado para bulk en HEAD. a8582b9/ec5f6dd implementan autorización, request y servicio; QA-02 no bloquea visibilidad.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — RESTORED (R5); gate obsoleto retirado. Ver matriz individual R5.
- QA independiente: SQLite aislado + Chrome interceptado; sin deletes DB compartida. Mutación real pendiente.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-073 — Eliminar selección

- Módulo/pantalla: `unitmeasure.index` / `unitMeasure.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Unidades](http://127.0.0.1:8080/dashboard/Unidades). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_unitmeasure`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/unitmeasure/delete-multiple`. Handler: `gate retirado R5; handler delete-multiple en public/js/unitmeasure/index.js`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado para bulk en HEAD. a8582b9/ec5f6dd implementan autorización, request y servicio; QA-02 no bloquea visibilidad.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — RESTORED (R5); gate obsoleto retirado. Ver matriz individual R5.
- QA independiente: SQLite aislado + Chrome interceptado; sin deletes DB compartida. Mutación real pendiente.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-074 — Eliminar selección

- Módulo/pantalla: `genero.index` / `genero.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/generos](http://127.0.0.1:8080/dashboard/generos). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_genero`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/genero/delete-multiple`. Handler: `gate retirado R5; handler delete-multiple en public/js/genero/index.js`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado para bulk en HEAD. a8582b9/ec5f6dd implementan autorización, request y servicio; QA-02 no bloquea visibilidad.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — RESTORED (R5); gate obsoleto retirado. Ver matriz individual R5.
- QA independiente: SQLite aislado + Chrome interceptado; sin deletes DB compartida. Mutación real pendiente.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-075 — Eliminar selección

- Módulo/pantalla: `talla.index` / `talla.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/tallas](http://127.0.0.1:8080/dashboard/tallas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_talla`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/talla/delete-multiple`. Handler: `gate retirado R5; handler delete-multiple en public/js/talla/index.js`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado para bulk en HEAD. a8582b9/ec5f6dd implementan autorización, request y servicio; QA-02 no bloquea visibilidad.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — RESTORED (R5); gate obsoleto retirado. Ver matriz individual R5.
- QA independiente: SQLite aislado + Chrome interceptado; sin deletes DB compartida. Mutación real pendiente.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-076 — Eliminar selección

- Módulo/pantalla: `color.index` / `color.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/colors](http://127.0.0.1:8080/dashboard/colors). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_color`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/color/delete-multiple`. Handler: `gate retirado R5; handler delete-multiple en public/js/color/index.js`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Ninguno demostrado para bulk en HEAD. a8582b9/ec5f6dd implementan autorización, request y servicio; QA-02 no bloquea visibilidad.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: ACTIVE — RESTORED (R5); gate obsoleto retirado. Ver matriz individual R5.
- QA independiente: SQLite aislado + Chrome interceptado; sin deletes DB compartida. Mutación real pendiente.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-077 — Diagnóstico de NC rechazada

- Recuperación: IMPLEMENTED R1.
- Módulo/pantalla: puntoVenta.list. URL: [Ventas](http://127.0.0.1:8080/dashboard/listado/ventas).
- Original: trigger generado por renderDataTable y handler `ver_error_nota_credito` en `ffe0974^:public/js/puntoVenta/list.js`; pérdida ffe0974.
- Permiso: list_puntoVenta + objeto visible. Condición: `has_rejected_credit_note`.
- Endpoint: ninguno adicional; diagnóstico del DTO `GET /dashboard/get/data/sales/{page}`. Handler: `data-ver_error_nota_credito → act → notice`.
- Motivo: exclusión read-only del renderer. Blocker: ninguno demostrado.
- Responsable: Frontend. Recuperación: IMPLEMENTED R1. QA: STATIC/UNIT PASS; mensaje escapado; no proveedor.
- Cierre: acceso condicional y texto seguro preservados; no ejecutar operaciones externas para mostrar diagnóstico.

### FR-078 — Solución de error SUNAT

- Recuperación: IMPLEMENTED R1.
- Módulo/pantalla: puntoVenta.list. URL: [Ventas](http://127.0.0.1:8080/dashboard/listado/ventas).
- Original: trigger generado por renderDataTable y handler `ver_solucion_sunat_error` en `ffe0974^:public/js/puntoVenta/list.js`; pérdida ffe0974.
- Permiso: list_puntoVenta + objeto visible. Condición: `sunat_error_is_discarded`.
- Endpoint: ninguno adicional; diagnóstico del DTO `GET /dashboard/get/data/sales/{page}`. Handler: `data-ver_solucion_sunat_error → act → notice`.
- Motivo: exclusión read-only del renderer. Blocker: ninguno demostrado.
- Responsable: Frontend. Recuperación: IMPLEMENTED R1. QA: STATIC/UNIT PASS; mensaje escapado; no proveedor.
- Cierre: acceso condicional y texto seguro preservados; no ejecutar operaciones externas para mostrar diagnóstico.

### FR-079 — Navegar a sesión propia desde Admin

- Recuperación: IMPLEMENTED R2.
- Módulo/pantalla: cashMovement.admin.index / cashBox.admin_index.
- URL local: http://127.0.0.1:8080/dashboard/cash-movements/admin.
- Funcionalidad original: caja abierta del actor permite ir a su sesión. Evidencia: 744d28b^ dispatcher → _admin_legacy; admin_index.js rama isOpen de data-cashbox-btn. Pérdida: 744d28b.
- Permiso: Auth/contexto SELF; Admin exige listCashMovementAdmin_cashBox. Condición: openCashBoxIds del actor/tenant/company/branch.
- Endpoint: GET /dashboard/cash-registers/session/{cashBox}. Handler: enlace de caja abierta, mismo destino histórico.
- Motivo: exclusión read-only; no retiro aprobado. Blocker actual: ninguno nuevo para navegación.
- Responsable: Frontend. Recuperación: IMPLEMENTED R2.
- QA independiente: STATIC SELF CONTRACT + LINK/ISOLATED NAVIGATION PASS; operaciones de sesión no ejecutadas.
- Condición de cierre: mantener acceso contextual propio y no abrir sesión por GET; permisos y business QA separados.
- Evidencia: REAL-NEXT-CASH-MOVEMENTS-ADMIN-FUNCTIONAL-PARITY-RECOVERY.md. No duplica CASH-02/03/04.

## Investigaciones R1 sin pérdida operativa demostrada

- R1-I01 Reintentar comprobante: botón en 640fb46, retirado b52c3cd. No handler alcanzable encontrado en baseline/HEAD; DTO can_retry_invoice=true no crea un contrato de retry. No conectar por suposición a emisión ni a recuperación de archivos. EVIDENCE REQUIRED — DEV debe identificar endpoint/payload seguro; Producto decide si crear nueva capacidad. No cuenta como pérdida confirmada.
- R1-I02 Facturador / update-invoice-data: handlers y modal históricos, sin trigger alcanzable en templates del baseline ffe0974^; no restaurar sólo por código huérfano. EVIDENCE REQUIRED — demostrar acceso histórico/contrato de reconciliación antes de proponerlo. No cuenta como pérdida confirmada.
- Diagnóstico SUNAT Error: mensaje ya presente en DTO/detalle; ahora también acceso directo seguro. No sumar de nuevo como pérdida.

## Superficies con paridad estática parcial (9)

- `cashMovement.my.index` — STATIC ACTION PARITY; mutaciones no certificadas por ese estado.
- `cashRegister.session` — STATIC ACTION PARITY; mutaciones no certificadas por ese estado.
- `shipping_guides.view` — STATIC ACTION PARITY; mutaciones no certificadas por ese estado.
- `shipping_guides.show` — STATIC ACTION PARITY; mutaciones no certificadas por ese estado.
- `index.credit.supplier` — STATIC ACTION PARITY; mutaciones no certificadas por ese estado.
- `index.invoices.pending` — STATIC ACTION PARITY; mutaciones no certificadas por ese estado.
- `quoteSale.index` — STATIC ACTION PARITY; mutaciones no certificadas por ese estado.
- `quoteSale.index.facturadas` — STATIC ACTION PARITY; mutaciones no certificadas por ese estado.
- `worker.index` — STATIC ACTION PARITY; mutaciones no certificadas por ese estado.

## Pendientes de contraste semántico (158)

| Superficie | URL | Estado |
|---|---|---|
| password.required.edit | [http://127.0.0.1:8080/password/cambio-obligatorio](http://127.0.0.1:8080/password/cambio-obligatorio) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| tenantCompany.index | [http://127.0.0.1:8080/dashboard/configuracion/empresas](http://127.0.0.1:8080/dashboard/configuracion/empresas) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| tenantCompany.create | [http://127.0.0.1:8080/dashboard/configuracion/empresas/crear](http://127.0.0.1:8080/dashboard/configuracion/empresas/crear) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| tenantCompany.edit | [http://127.0.0.1:8080/dashboard/configuracion/empresas/{id}/editar](http://127.0.0.1:8080/dashboard/configuracion/empresas/{id}/editar) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| tenantBranch.index | [http://127.0.0.1:8080/dashboard/configuracion/empresas/{companyId}/sucursales](http://127.0.0.1:8080/dashboard/configuracion/empresas/{companyId}/sucursales) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| tenantBranch.create | [http://127.0.0.1:8080/dashboard/configuracion/empresas/{companyId}/sucursales/crear](http://127.0.0.1:8080/dashboard/configuracion/empresas/{companyId}/sucursales/crear) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| tenantBranch.edit | [http://127.0.0.1:8080/dashboard/configuracion/sucursales/{id}/editar](http://127.0.0.1:8080/dashboard/configuracion/sucursales/{id}/editar) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| companyBankAccount.index | [http://127.0.0.1:8080/dashboard/configuracion/cuentas-bancarias](http://127.0.0.1:8080/dashboard/configuracion/cuentas-bancarias) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| companyBankAccount.create | [http://127.0.0.1:8080/dashboard/configuracion/cuentas-bancarias/crear](http://127.0.0.1:8080/dashboard/configuracion/cuentas-bancarias/crear) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| companyBankAccount.edit | [http://127.0.0.1:8080/dashboard/configuracion/cuentas-bancarias/{id}/editar](http://127.0.0.1:8080/dashboard/configuracion/cuentas-bancarias/{id}/editar) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| companySetting.index | [http://127.0.0.1:8080/dashboard/configuracion/configuraciones/empresa](http://127.0.0.1:8080/dashboard/configuracion/configuraciones/empresa) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| workforce.index | [http://127.0.0.1:8080/dashboard/configuracion/workforces-default](http://127.0.0.1:8080/dashboard/configuracion/workforces-default) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| priceList.index | [http://127.0.0.1:8080/dashboard/prices](http://127.0.0.1:8080/dashboard/prices) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| customer.index | [http://127.0.0.1:8080/dashboard/clientes](http://127.0.0.1:8080/dashboard/clientes) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| customer.create | [http://127.0.0.1:8080/dashboard/crear/cliente](http://127.0.0.1:8080/dashboard/crear/cliente) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| customer.edit | [http://127.0.0.1:8080/dashboard/editar/cliente/{id}](http://127.0.0.1:8080/dashboard/editar/cliente/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| customer.indexrestore | [http://127.0.0.1:8080/dashboard/clientes/restore](http://127.0.0.1:8080/dashboard/clientes/restore) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| supplier.index | [http://127.0.0.1:8080/dashboard/proveedores](http://127.0.0.1:8080/dashboard/proveedores) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| supplier.create | [http://127.0.0.1:8080/dashboard/crear/proveedor](http://127.0.0.1:8080/dashboard/crear/proveedor) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| supplier.edit | [http://127.0.0.1:8080/dashboard/editar/proveedor/{id}](http://127.0.0.1:8080/dashboard/editar/proveedor/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| supplier.indexrestore | [http://127.0.0.1:8080/dashboard/proveedores/restore](http://127.0.0.1:8080/dashboard/proveedores/restore) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| contactName.index | [http://127.0.0.1:8080/dashboard/contactos](http://127.0.0.1:8080/dashboard/contactos) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| contactName.create | [http://127.0.0.1:8080/dashboard/crear/contacto](http://127.0.0.1:8080/dashboard/crear/contacto) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| contactName.edit | [http://127.0.0.1:8080/dashboard/editar/contacto/{id}](http://127.0.0.1:8080/dashboard/editar/contacto/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| contactName.indexrestore | [http://127.0.0.1:8080/dashboard/contactos/restore](http://127.0.0.1:8080/dashboard/contactos/restore) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| materialtype.create | [http://127.0.0.1:8080/dashboard/crear/tipomaterial](http://127.0.0.1:8080/dashboard/crear/tipomaterial) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| materialtype.edit | [http://127.0.0.1:8080/dashboard/editar/tipomaterial/{id}](http://127.0.0.1:8080/dashboard/editar/tipomaterial/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| subtype.create | [http://127.0.0.1:8080/dashboard/crear/subtipo](http://127.0.0.1:8080/dashboard/crear/subtipo) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| subtype.edit | [http://127.0.0.1:8080/dashboard/editar/subtipo/{id}](http://127.0.0.1:8080/dashboard/editar/subtipo/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| category.create | [http://127.0.0.1:8080/dashboard/crear/categoria](http://127.0.0.1:8080/dashboard/crear/categoria) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| category.edit | [http://127.0.0.1:8080/dashboard/editar/categoria/{id}](http://127.0.0.1:8080/dashboard/editar/categoria/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| subcategory.create | [http://127.0.0.1:8080/dashboard/crear/subcategoria](http://127.0.0.1:8080/dashboard/crear/subcategoria) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| subcategory.edit | [http://127.0.0.1:8080/dashboard/editar/subcategoria/{id}](http://127.0.0.1:8080/dashboard/editar/subcategoria/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| categoryInvoice.index | [http://127.0.0.1:8080/dashboard/categorias/facturas](http://127.0.0.1:8080/dashboard/categorias/facturas) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| categoryInvoice.create | [http://127.0.0.1:8080/dashboard/crear/categoria/facturas](http://127.0.0.1:8080/dashboard/crear/categoria/facturas) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| categoryInvoice.edit | [http://127.0.0.1:8080/dashboard/editar/categoria/factura/{id}](http://127.0.0.1:8080/dashboard/editar/categoria/factura/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| exampler.create | [http://127.0.0.1:8080/dashboard/crear/modelo](http://127.0.0.1:8080/dashboard/crear/modelo) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| exampler.edit | [http://127.0.0.1:8080/dashboard/editar/modelo/{id}](http://127.0.0.1:8080/dashboard/editar/modelo/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| brand.create | [http://127.0.0.1:8080/dashboard/crear/marca](http://127.0.0.1:8080/dashboard/crear/marca) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| brand.edit | [http://127.0.0.1:8080/dashboard/editar/marca/{id}](http://127.0.0.1:8080/dashboard/editar/marca/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| warrant.index | [http://127.0.0.1:8080/dashboard/Cédulas](http://127.0.0.1:8080/dashboard/Cédulas) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| warrant.create | [http://127.0.0.1:8080/dashboard/crear/cedula](http://127.0.0.1:8080/dashboard/crear/cedula) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| warrant.edit | [http://127.0.0.1:8080/dashboard/editar/cedula/{id}](http://127.0.0.1:8080/dashboard/editar/cedula/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| quality.index | [http://127.0.0.1:8080/dashboard/Calidades](http://127.0.0.1:8080/dashboard/Calidades) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| quality.create | [http://127.0.0.1:8080/dashboard/crear/calidad](http://127.0.0.1:8080/dashboard/crear/calidad) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| quality.edit | [http://127.0.0.1:8080/dashboard/editar/calidad/{id}](http://127.0.0.1:8080/dashboard/editar/calidad/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| typescrap.create | [http://127.0.0.1:8080/dashboard/crear/retaceria](http://127.0.0.1:8080/dashboard/crear/retaceria) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| typescrap.edit | [http://127.0.0.1:8080/dashboard/editar/retaceria/{id}](http://127.0.0.1:8080/dashboard/editar/retaceria/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| unitmeasure.create | [http://127.0.0.1:8080/dashboard/crear/unidad](http://127.0.0.1:8080/dashboard/crear/unidad) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| unitmeasure.edit | [http://127.0.0.1:8080/dashboard/editar/unidad/{id}](http://127.0.0.1:8080/dashboard/editar/unidad/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| genero.create | [http://127.0.0.1:8080/dashboard/crear/genero](http://127.0.0.1:8080/dashboard/crear/genero) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| genero.edit | [http://127.0.0.1:8080/dashboard/editar/genero/{id}](http://127.0.0.1:8080/dashboard/editar/genero/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| talla.create | [http://127.0.0.1:8080/dashboard/crear/talla](http://127.0.0.1:8080/dashboard/crear/talla) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| talla.edit | [http://127.0.0.1:8080/dashboard/editar/talla/{id}](http://127.0.0.1:8080/dashboard/editar/talla/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| material.create | [http://127.0.0.1:8080/dashboard/crear/material](http://127.0.0.1:8080/dashboard/crear/material) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| material.edit | [http://127.0.0.1:8080/dashboard/editar/material/{id}](http://127.0.0.1:8080/dashboard/editar/material/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| material.indexV2 | [http://127.0.0.1:8080/dashboard/listado/materiales/v2](http://127.0.0.1:8080/dashboard/listado/materiales/v2) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| warehouseTransfer.index | [http://127.0.0.1:8080/dashboard/traslados/almacenes](http://127.0.0.1:8080/dashboard/traslados/almacenes) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| warehouseTransfer.create | [http://127.0.0.1:8080/dashboard/traslados/almacenes/crear](http://127.0.0.1:8080/dashboard/traslados/almacenes/crear) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| warehouseTransfer.show | [http://127.0.0.1:8080/dashboard/traslados/almacenes/{id}](http://127.0.0.1:8080/dashboard/traslados/almacenes/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| warehouseAvailability.index | [http://127.0.0.1:8080/dashboard/disponibilidad/almacenes](http://127.0.0.1:8080/dashboard/disponibilidad/almacenes) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| entry.purchase.show | [http://127.0.0.1:8080/dashboard/entrada/compra/visualizar/{entry}](http://127.0.0.1:8080/dashboard/entrada/compra/visualizar/{entry}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| dashboard/ver/orden/compra/normal/{id} | [http://127.0.0.1:8080/dashboard/ver/orden/compra/normal/{id}](http://127.0.0.1:8080/dashboard/ver/orden/compra/normal/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| dashboard/ver/orden/compra/eliminada/{id} | [http://127.0.0.1:8080/dashboard/ver/orden/compra/eliminada/{id}](http://127.0.0.1:8080/dashboard/ver/orden/compra/eliminada/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| user.profile | [http://127.0.0.1:8080/dashboard/perfil](http://127.0.0.1:8080/dashboard/perfil) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| invoice.index.annulled | [http://127.0.0.1:8080/dashboard/factura/compra/anuladas](http://127.0.0.1:8080/dashboard/factura/compra/anuladas) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| invoice.create | [http://127.0.0.1:8080/dashboard/crear/factura/compra](http://127.0.0.1:8080/dashboard/crear/factura/compra) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| invoice.edit | [http://127.0.0.1:8080/dashboard/factura/compra/editar/{entry}](http://127.0.0.1:8080/dashboard/factura/compra/editar/{entry}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| invoice.show | [http://127.0.0.1:8080/dashboard/factura/compra/ver/{entry}](http://127.0.0.1:8080/dashboard/factura/compra/ver/{entry}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| paymentDeadline.index | [http://127.0.0.1:8080/dashboard/plazos/pagos](http://127.0.0.1:8080/dashboard/plazos/pagos) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| paymentDeadline.create | [http://127.0.0.1:8080/dashboard/crear/plazo/pago](http://127.0.0.1:8080/dashboard/crear/plazo/pago) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| paymentDeadline.edit | [http://127.0.0.1:8080/dashboard/editar/plazo/pago/{id}](http://127.0.0.1:8080/dashboard/editar/plazo/pago/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| porcentageQuote.index | [http://127.0.0.1:8080/dashboard/porcentajes/cotizaciones](http://127.0.0.1:8080/dashboard/porcentajes/cotizaciones) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| porcentageQuote.create | [http://127.0.0.1:8080/dashboard/crear/porcentaje](http://127.0.0.1:8080/dashboard/crear/porcentaje) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| porcentageQuote.edit | [http://127.0.0.1:8080/dashboard/editar/porcentaje/cotizacion/{id}](http://127.0.0.1:8080/dashboard/editar/porcentaje/cotizacion/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| worker.create | [http://127.0.0.1:8080/dashboard/registrar/colaborador](http://127.0.0.1:8080/dashboard/registrar/colaborador) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| worker.edit | [http://127.0.0.1:8080/dashboard/editar/colaborador/{id}](http://127.0.0.1:8080/dashboard/editar/colaborador/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| worker.enable | [http://127.0.0.1:8080/dashboard/habilitar/colaborador](http://127.0.0.1:8080/dashboard/habilitar/colaborador) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| civilStatuses.index | [http://127.0.0.1:8080/dashboard/estado/civil](http://127.0.0.1:8080/dashboard/estado/civil) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| civilStatuses.create | [http://127.0.0.1:8080/dashboard/crear/estado/civil](http://127.0.0.1:8080/dashboard/crear/estado/civil) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| civilStatuses.edit | [http://127.0.0.1:8080/dashboard/editar/estado/civil/{id}](http://127.0.0.1:8080/dashboard/editar/estado/civil/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| civilStatuses.deleted | [http://127.0.0.1:8080/dashboard/estado/civil/eliminados](http://127.0.0.1:8080/dashboard/estado/civil/eliminados) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| relationship.index | [http://127.0.0.1:8080/dashboard/parentescos](http://127.0.0.1:8080/dashboard/parentescos) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| relationship.create | [http://127.0.0.1:8080/dashboard/crear/parentesco](http://127.0.0.1:8080/dashboard/crear/parentesco) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| relationship.edit | [http://127.0.0.1:8080/dashboard/editar/parentesco/{id}](http://127.0.0.1:8080/dashboard/editar/parentesco/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| workFunctions.index | [http://127.0.0.1:8080/dashboard/cargos](http://127.0.0.1:8080/dashboard/cargos) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| workFunctions.create | [http://127.0.0.1:8080/dashboard/crear/cargo](http://127.0.0.1:8080/dashboard/crear/cargo) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| workFunctions.edit | [http://127.0.0.1:8080/dashboard/editar/cargo/{id}](http://127.0.0.1:8080/dashboard/editar/cargo/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| workFunctions.deleted | [http://127.0.0.1:8080/dashboard/cargos/eliminados](http://127.0.0.1:8080/dashboard/cargos/eliminados) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| pensionSystems.index | [http://127.0.0.1:8080/dashboard/sistemas/pension](http://127.0.0.1:8080/dashboard/sistemas/pension) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| pensionSystems.create | [http://127.0.0.1:8080/dashboard/crear/sistema/pension](http://127.0.0.1:8080/dashboard/crear/sistema/pension) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| pensionSystems.edit | [http://127.0.0.1:8080/dashboard/editar/sistema/pension/{id}](http://127.0.0.1:8080/dashboard/editar/sistema/pension/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| pensionSystems.deleted | [http://127.0.0.1:8080/dashboard/sistemas/pension/eliminados](http://127.0.0.1:8080/dashboard/sistemas/pension/eliminados) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| holiday.index | [http://127.0.0.1:8080/dashboard/feriados](http://127.0.0.1:8080/dashboard/feriados) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| holiday.create | [http://127.0.0.1:8080/dashboard/crear/feriado](http://127.0.0.1:8080/dashboard/crear/feriado) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| holiday.edit | [http://127.0.0.1:8080/dashboard/editar/feriado/{id}](http://127.0.0.1:8080/dashboard/editar/feriado/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| reasonSuspension.index | [http://127.0.0.1:8080/dashboard/razones/suspension](http://127.0.0.1:8080/dashboard/razones/suspension) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| reasonSuspension.create | [http://127.0.0.1:8080/dashboard/crear/razon/suspension](http://127.0.0.1:8080/dashboard/crear/razon/suspension) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| reasonSuspension.edit | [http://127.0.0.1:8080/dashboard/editar/razon/suspension/{id}](http://127.0.0.1:8080/dashboard/editar/razon/suspension/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| areaWorker.index | [http://127.0.0.1:8080/dashboard/areas/empresa](http://127.0.0.1:8080/dashboard/areas/empresa) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| areaWorker.create | [http://127.0.0.1:8080/dashboard/crear/area/empresa](http://127.0.0.1:8080/dashboard/crear/area/empresa) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| areaWorker.edit | [http://127.0.0.1:8080/dashboard/editar/area/empresa/{id}](http://127.0.0.1:8080/dashboard/editar/area/empresa/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| order.purchase.finance.create | [http://127.0.0.1:8080/dashboard/crear/orden/compra/finanzas](http://127.0.0.1:8080/dashboard/crear/orden/compra/finanzas) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| dashboard/editar/orden/compra/finanza/{id} | [http://127.0.0.1:8080/dashboard/editar/orden/compra/finanza/{id}](http://127.0.0.1:8080/dashboard/editar/orden/compra/finanza/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| dashboard/ver/orden/compra/finanzas/eliminada/{id} | [http://127.0.0.1:8080/dashboard/ver/orden/compra/finanzas/eliminada/{id}](http://127.0.0.1:8080/dashboard/ver/orden/compra/finanzas/eliminada/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| bill.index | [http://127.0.0.1:8080/dashboard/tipos/gastos](http://127.0.0.1:8080/dashboard/tipos/gastos) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| bill.create | [http://127.0.0.1:8080/dashboard/crear/tipo/gasto](http://127.0.0.1:8080/dashboard/crear/tipo/gasto) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| bill.edit | [http://127.0.0.1:8080/dashboard/editar/tipo/gasto/{id}](http://127.0.0.1:8080/dashboard/editar/tipo/gasto/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| expense.create | [http://127.0.0.1:8080/dashboard/crear/gasto](http://127.0.0.1:8080/dashboard/crear/gasto) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| supplier.accounts.index | [http://127.0.0.1:8080/dashboard/registrar/cuentas/proveedores/{supplier_id}](http://127.0.0.1:8080/dashboard/registrar/cuentas/proveedores/{supplier_id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| categoryEquipment.index | [http://127.0.0.1:8080/dashboard/categorias/equipos](http://127.0.0.1:8080/dashboard/categorias/equipos) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| categoryEquipment.eliminated | [http://127.0.0.1:8080/dashboard/categorias/equiposeliminados](http://127.0.0.1:8080/dashboard/categorias/equiposeliminados) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| stocks.files.index | [http://127.0.0.1:8080/dashboard/importar/archivos/stocks/materiales](http://127.0.0.1:8080/dashboard/importar/archivos/stocks/materiales) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| puntoVenta.index | [http://127.0.0.1:8080/dashboard/crear/venta/V1](http://127.0.0.1:8080/dashboard/crear/venta/V1) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| puntoVenta.indexV2 | [http://127.0.0.1:8080/dashboard/crear/venta/V2](http://127.0.0.1:8080/dashboard/crear/venta/V2) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| ganancia.index | [http://127.0.0.1:8080/dashboard/listado/ganancias/diarias](http://127.0.0.1:8080/dashboard/listado/ganancias/diarias) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| ganancia.detail.index | [http://127.0.0.1:8080/dashboard/listado/ganancia/detalles/{id}](http://127.0.0.1:8080/dashboard/listado/ganancia/detalles/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| ganancia.detail.usuario | [http://127.0.0.1:8080/dashboard/listado/ganancia/detalles/trabajador/{sale_id}](http://127.0.0.1:8080/dashboard/listado/ganancia/detalles/trabajador/{sale_id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| ganancia.index.trabajador | [http://127.0.0.1:8080/dashboard/listado/ganancias/diarias/trabajador](http://127.0.0.1:8080/dashboard/listado/ganancias/diarias/trabajador) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| metas.index | [http://127.0.0.1:8080/dashboard/metas](http://127.0.0.1:8080/dashboard/metas) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| metas.create | [http://127.0.0.1:8080/dashboard/metas/create](http://127.0.0.1:8080/dashboard/metas/create) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| metas.edit | [http://127.0.0.1:8080/dashboard/metas/{meta}/edit](http://127.0.0.1:8080/dashboard/metas/{meta}/edit) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| metas.ranking | [http://127.0.0.1:8080/dashboard/metas/ranking](http://127.0.0.1:8080/dashboard/metas/ranking) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| quoteSale.show | [http://127.0.0.1:8080/dashboard/ver/cotizacion/venta/{quote}](http://127.0.0.1:8080/dashboard/ver/cotizacion/venta/{quote}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| quoteSale.pendingBranches | [http://127.0.0.1:8080/dashboard/cotizaciones/venta/pendientes-sucursal](http://127.0.0.1:8080/dashboard/cotizaciones/venta/pendientes-sucursal) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| quoteSale.pendingBranches.show | [http://127.0.0.1:8080/dashboard/cotizaciones/venta/pendientes-sucursal/{id}](http://127.0.0.1:8080/dashboard/cotizaciones/venta/pendientes-sucursal/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| kardex.index | [http://127.0.0.1:8080/dashboard/kardex](http://127.0.0.1:8080/dashboard/kardex) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| settings.material-details.index | [http://127.0.0.1:8080/dashboard/settings/material-details](http://127.0.0.1:8080/dashboard/settings/material-details) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| cashBox.index | [http://127.0.0.1:8080/dashboard/cash-boxes](http://127.0.0.1:8080/dashboard/cash-boxes) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| cashBoxSubtype.index | [http://127.0.0.1:8080/dashboard/cash-box-subtypes](http://127.0.0.1:8080/dashboard/cash-box-subtypes) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| financial.summary | [http://127.0.0.1:8080/dashboard/financial/summary](http://127.0.0.1:8080/dashboard/financial/summary) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| financial.summary.movements | [http://127.0.0.1:8080/dashboard/financial/summary/cash-boxes/{cashBox}/movements](http://127.0.0.1:8080/dashboard/financial/summary/cash-boxes/{cashBox}/movements) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| shipping_guides.create | [http://127.0.0.1:8080/dashboard/shipping-guides/create](http://127.0.0.1:8080/dashboard/shipping-guides/create) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| stockitems.view.variants | [http://127.0.0.1:8080/dashboard/view/material/variants/{id}](http://127.0.0.1:8080/dashboard/view/material/variants/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| color.create | [http://127.0.0.1:8080/dashboard/crear/color](http://127.0.0.1:8080/dashboard/crear/color) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| color.edit | [http://127.0.0.1:8080/dashboard/editar/color/{id}](http://127.0.0.1:8080/dashboard/editar/color/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| configUserWeb.index | [http://127.0.0.1:8080/dashboard/configuracion/usuarios/web](http://127.0.0.1:8080/dashboard/configuracion/usuarios/web) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| configUserWeb.create | [http://127.0.0.1:8080/dashboard/configuracion/usuarios/web/nuevo](http://127.0.0.1:8080/dashboard/configuracion/usuarios/web/nuevo) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| configUserWeb.edit | [http://127.0.0.1:8080/dashboard/configuracion/usuarios/web/{id}/editar](http://127.0.0.1:8080/dashboard/configuracion/usuarios/web/{id}/editar) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| companyStockItem.index | [http://127.0.0.1:8080/dashboard/dashboard/company-stock-items](http://127.0.0.1:8080/dashboard/dashboard/company-stock-items) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| platform.dashboard | [http://127.0.0.1:8080/dashboard/plataforma](http://127.0.0.1:8080/dashboard/plataforma) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| platformActivity.index | [http://127.0.0.1:8080/dashboard/plataforma/activity-log](http://127.0.0.1:8080/dashboard/plataforma/activity-log) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| platformTenant.index | [http://127.0.0.1:8080/dashboard/plataforma/tenants](http://127.0.0.1:8080/dashboard/plataforma/tenants) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| platformTenant.create | [http://127.0.0.1:8080/dashboard/plataforma/tenants/create](http://127.0.0.1:8080/dashboard/plataforma/tenants/create) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| platformTenant.show | [http://127.0.0.1:8080/dashboard/plataforma/tenants/{id}](http://127.0.0.1:8080/dashboard/plataforma/tenants/{id}) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| plan.index | [http://127.0.0.1:8080/dashboard/plataforma/plans](http://127.0.0.1:8080/dashboard/plataforma/plans) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| roleTemplate.index | [http://127.0.0.1:8080/dashboard/plataforma/role-templates](http://127.0.0.1:8080/dashboard/plataforma/role-templates) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| roleTemplate.create | [http://127.0.0.1:8080/dashboard/plataforma/role-templates/create](http://127.0.0.1:8080/dashboard/plataforma/role-templates/create) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| roleTemplate.edit | [http://127.0.0.1:8080/dashboard/plataforma/role-templates/{id}/edit](http://127.0.0.1:8080/dashboard/plataforma/role-templates/{id}/edit) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| tenantRole.index | [http://127.0.0.1:8080/dashboard/plataforma/tenant-roles](http://127.0.0.1:8080/dashboard/plataforma/tenant-roles) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| tenantRole.create | [http://127.0.0.1:8080/dashboard/plataforma/tenants/{tenantId}/roles/create](http://127.0.0.1:8080/dashboard/plataforma/tenants/{tenantId}/roles/create) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| tenantRole.edit | [http://127.0.0.1:8080/dashboard/plataforma/tenants/{tenantId}/roles/{roleId}/edit](http://127.0.0.1:8080/dashboard/plataforma/tenants/{tenantId}/roles/{roleId}/edit) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| platformPercentageWorker.index | [http://127.0.0.1:8080/dashboard/plataforma/parametros-laborales](http://127.0.0.1:8080/dashboard/plataforma/parametros-laborales) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| platformPercentageWorker.edit | [http://127.0.0.1:8080/dashboard/plataforma/parametros-laborales/{id}/editar](http://127.0.0.1:8080/dashboard/plataforma/parametros-laborales/{id}/editar) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| platformTypeTax.index | [http://127.0.0.1:8080/dashboard/plataforma/tipos-impuesto](http://127.0.0.1:8080/dashboard/plataforma/tipos-impuesto) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| platformNubefact.index | [http://127.0.0.1:8080/dashboard/plataforma/nubefact](http://127.0.0.1:8080/dashboard/plataforma/nubefact) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| platformNubefact.edit | [http://127.0.0.1:8080/dashboard/plataforma/tenants/{tenantId}/companies/{companyId}/nubefact](http://127.0.0.1:8080/dashboard/plataforma/tenants/{tenantId}/companies/{companyId}/nubefact) | EVIDENCE REQUIRED — no PASS por comparación estructural |
| platformShippingCatalogs.index | [http://127.0.0.1:8080/dashboard/plataforma/catalogos-guias/{catalog?}](http://127.0.0.1:8080/dashboard/plataforma/catalogos-guias/{catalog?}) | EVIDENCE REQUIRED — no PASS por comparación estructural |


### FR-080 — Detalle Express desde General V2

- Recuperación: LEGACY — OWNER CONFIRMED — RECOVERY NOT REQUIRED.
- Pantalla: `order.purchase.general.indexV2` / orderPurchase.general_v2.
- Original: b1fa9d5^ renderDataTable rama type=e, data-ver_orden; perdido en b1fa9d5. No estaba contabilizado entre FR017–036.
- Endpoint: GET /dashboard/ver/orden/compra/express/{id}; ruta list_orderPurchaseExpress (UI legacy list_orderPurchaseNormal).
- Estado histórico previo a la decisión: BACKEND BLOCKER. Mismo destino de FR034: User::all y binding sin tipo; paquete Q existente, no duplicar finding.
- Decisión del usuario R3.1: documentar pendiente DEV, sin control informativo, sin retiro aprobado.
- QA: estática; sin ejecutar destino. Recuperar después del fix, sin confundir pendiente de homologación con inseguridad.

## R3.1 — cierre parcial histórico (sustituido por reconciliación de alcance)

Ver `REAL-NEXT-NORMAL-EXPRESS-PURCHASE-FUNCTIONAL-PARITY-RECOVERY.md`:6 superficies revisadas/4 modificadas;34 capacidades agrupadas=13 disponibles+21 pérdidas.7 completas restauradas,2 parciales Normal/Express,12 sin recuperar;14 no cerradas incluyendo parciales. Mutaciones reales/documentos/export pendientes. Backend0/CSS0. Dataset activo2,3,4 y eliminado1 en T1/C1. Nuevos findings PUR-R3-01/02; no duplicar INV-01/paqueteQ. Usuario aprobó documentar pendientes de DEV. No declarar paridad total ni avanzar R3.2.


## R3.2 — revisión histórica anterior a la decisión explícita

2026-11-24: ver REAL-NEXT-FINANCIAL-PURCHASE-FUNCTIONAL-PARITY-RECOVERY.md. FR037–045 son9 pérdidas históricas internas, todavía NO declaradas pérdidas vigentes ni retiradas: PRODUCT DECISION REQUIRED — LEGACY STATUS UNCERTAIN. Los enlaces sidebar ya estaban comentados antes de df5c53f; se consultó si siguen vigentes las acciones al acceder por URL. No se recuperaron controles financieros mientras falta esa decisión. Dataset financiero0, tests read-only2/10PASS; backend/CSS0. No se reactivan OrderService ni handlers residuales; no nuevas pérdidas activas confirmadas. La autorización general de R3.2 no se interpreta como autorización para ignorar la aclaración de vigencia del propietario.

### FR-081 — Detalle de ítems / entry.purchase.indexV2

- Pantalla: `entry.purchase.indexV2`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_entryPurchase`.
- Endpoint/contrato: `GET /dashboard/get/json/items/{entry_id}`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-082 — Detalle rápido / invoice.index

- Pantalla: `invoice.index`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_invoice`.
- Endpoint/contrato: `DTO details del listado autorizado`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-083 — Adjunto / invoice.index

- **R7.6.1 vigente: FRONTEND RECOVERED.** Lector autorizado DEV, permiso de familia preservado; QA frontend interceptado PASS. Protección HTTP directa de despliegue PENDING, independiente de recuperación. Detalles anteriores conservados como historia.


- **Historia R7.6:** ACTIVE — INSTALLATION REQUIRED; lector DEV presente pero servidor :8080 omite autorización de archivos físicos. DOC-01; no restaurado. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Pantalla: `invoice.index`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_invoice`.
- Endpoint/contrato: `/images/entries/*`.
- Blocker actual: DOC-01
- Recuperación: ACTIVE — BACKEND BLOCKER R4.
- QA independiente: No ejecutar documentos públicos.

### FR-084 — Crear factura / report.invoice.finance

- Pantalla: `report.invoice.finance`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `create_invoice`.
- Endpoint/contrato: `GET /dashboard/crear/factura/compra`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-085 — Editar factura / report.invoice.finance

- Pantalla: `report.invoice.finance`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `update_invoice`.
- Endpoint/contrato: `GET /dashboard/factura/compra/editar/{entry}`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-086 — Anular factura / report.invoice.finance

- Pantalla: `report.invoice.finance`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `destroy_invoice`.
- Endpoint/contrato: `POST /dashboard/destroy/total/invoice/{id}`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-087 — Detalle rápido / report.invoice.finance

- Pantalla: `report.invoice.finance`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_invoice`.
- Endpoint/contrato: `DTO details del listado autorizado`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-088 — Adjunto / report.invoice.finance

- **R7.6.1 vigente: FRONTEND RECOVERED.** Lector autorizado DEV, permiso de familia preservado; QA frontend interceptado PASS. Protección HTTP directa de despliegue PENDING, independiente de recuperación. Detalles anteriores conservados como historia.


- **Historia R7.6:** ACTIVE — INSTALLATION REQUIRED; lector DEV presente pero servidor :8080 omite autorización de archivos físicos. DOC-01; no restaurado. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Pantalla: `report.invoice.finance`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_invoice`.
- Endpoint/contrato: `/images/entries/*`.
- Blocker actual: DOC-01
- Recuperación: ACTIVE — BACKEND BLOCKER R4.
- QA independiente: No ejecutar documentos públicos.

### FR-089 — Crear factura / report.invoice.finance.sin.orden

- Pantalla: `report.invoice.finance.sin.orden`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `create_invoice`.
- Endpoint/contrato: `GET /dashboard/crear/factura/compra`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-090 — Editar factura / report.invoice.finance.sin.orden

- Pantalla: `report.invoice.finance.sin.orden`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `update_invoice`.
- Endpoint/contrato: `GET /dashboard/factura/compra/editar/{entry}`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-091 — Anular factura / report.invoice.finance.sin.orden

- Pantalla: `report.invoice.finance.sin.orden`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `destroy_invoice`.
- Endpoint/contrato: `POST /dashboard/destroy/total/invoice/{id}`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-092 — Detalle rápido / report.invoice.finance.sin.orden

- Pantalla: `report.invoice.finance.sin.orden`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_invoice`.
- Endpoint/contrato: `DTO details del listado autorizado`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-093 — Adjunto / report.invoice.finance.sin.orden

- **R7.6.1 vigente: FRONTEND RECOVERED.** Lector autorizado DEV, permiso de familia preservado; QA frontend interceptado PASS. Protección HTTP directa de despliegue PENDING, independiente de recuperación. Detalles anteriores conservados como historia.


- **Historia R7.6:** ACTIVE — INSTALLATION REQUIRED; lector DEV presente pero servidor :8080 omite autorización de archivos físicos. DOC-01; no restaurado. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Pantalla: `report.invoice.finance.sin.orden`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_invoice`.
- Endpoint/contrato: `/images/entries/*`.
- Blocker actual: DOC-01
- Recuperación: ACTIVE — BACKEND BLOCKER R4.
- QA independiente: No ejecutar documentos públicos.

### FR-094 — Crear factura / invoice.index.annulled

- Pantalla: `invoice.index.annulled`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `create_invoice`.
- Endpoint/contrato: `GET /dashboard/crear/factura/compra`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-095 — Detalle rápido / invoice.index.annulled

- Pantalla: `invoice.index.annulled`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_invoice`.
- Endpoint/contrato: `DTO details del listado autorizado`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

### FR-096 — Adjunto / invoice.index.annulled

- **R7.6.1 vigente: FRONTEND RECOVERED.** Lector autorizado DEV, permiso de familia preservado; QA frontend interceptado PASS. Protección HTTP directa de despliegue PENDING, independiente de recuperación. Detalles anteriores conservados como historia.


- **Historia R7.6:** ACTIVE — INSTALLATION REQUIRED; lector DEV presente pero servidor :8080 omite autorización de archivos físicos. DOC-01; no restaurado. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Pantalla: `invoice.index.annulled`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_invoice`.
- Endpoint/contrato: `/images/entries/*`.
- Blocker actual: DOC-01
- Recuperación: ACTIVE — BACKEND BLOCKER R4.
- QA independiente: No ejecutar documentos públicos.

### FR-097 — Adjunto / entry.purchase.show

- **R7.6.1 vigente: FRONTEND RECOVERED.** Lector autorizado DEV, permiso de familia preservado; QA frontend interceptado PASS. Protección HTTP directa de despliegue PENDING, independiente de recuperación. Detalles anteriores conservados como historia.


- **Historia R7.6:** ACTIVE — INSTALLATION REQUIRED; lector DEV presente pero servidor :8080 omite autorización de archivos físicos. DOC-01; no restaurado. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Pantalla: `entry.purchase.show`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_entryPurchase`.
- Endpoint/contrato: `/images/entries/*`.
- Blocker actual: DOC-01
- Recuperación: ACTIVE — BACKEND BLOCKER R4.
- QA independiente: No ejecutar documentos públicos.

### FR-098 — Adjunto / invoice.show

- **R7.6.1 vigente: FRONTEND RECOVERED.** Lector autorizado DEV, permiso de familia preservado; QA frontend interceptado PASS. Protección HTTP directa de despliegue PENDING, independiente de recuperación. Detalles anteriores conservados como historia.


- **Historia R7.6:** ACTIVE — INSTALLATION REQUIRED; lector DEV presente pero servidor :8080 omite autorización de archivos físicos. DOC-01; no restaurado. Ver informe R76; los estados R4/R7.5 siguientes son evidencia histórica.

- Pantalla: `invoice.show`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_invoice`.
- Endpoint/contrato: `/images/entries/*`.
- Blocker actual: DOC-01
- Recuperación: ACTIVE — BACKEND BLOCKER R4.
- QA independiente: No ejecutar documentos públicos.

### FR-099 — Totales PEN, USD y equivalente PEN / report.invoice.finance

- Pantalla: `report.invoice.finance`.
- Evidencia: R4, comparación 2ec61f3^/fa8f7ed^/483a785^ con integración y HEAD; matriz completa en REAL-NEXT-ENTRY-INVOICE-FUNCTIONAL-PARITY-RECOVERY.md.
- Permiso: `list_invoice`.
- Endpoint/contrato: `DTO total/currency_invoice/currency_venta; búsqueda aplicada`.
- Blocker actual: Ninguno para esta recuperación; mutaciones reales separadas.
- Recuperación: IMPLEMENTED R4.
- QA independiente: ISOLATED UI / READ CONTRACT; MUTATION/DOCUMENT/EXPORT QA PENDING cuando corresponde.

## R5 — cierre focalizado

11 pérdidas originales +2 nuevas (selección acumulada),13 recuperadas. 11 catálogos modificados;0 backend/CSS; QA-02 global abierto. Ver REAL-NEXT-MATERIAL-CATALOGS-FUNCTIONAL-PARITY-RECOVERY.md.

### FR-100 — Selección acumulada entre páginas

- Módulo/pantalla: `exampler.index`. Prioridad P1.
- Evidencia: `8faf1ef^` usa selectedIds Set y Array.from; `8faf1ef` retira eventos/Set junto al handler bulk.
- Vigencia: ACTIVE; no retiro autorizado.
- Endpoint: `POST /dashboard/exampler/delete-multiple`, ids[] + CSRF; destroy_exampler, tenant.
- Recuperación: ACTIVE — RESTORED R5. Mantiene alcance histórico de nodos filtrados; no selección implícita.
- QA independiente: fixtures 12 registros, navegación/payload interceptados; rollback servicio aislado. Sin mutación compartida.
- Blocker actual: ninguno demostrado en esta capacidad. QA mutadora real separada.

### FR-101 — Selección acumulada entre páginas

- Módulo/pantalla: `brand.index`. Prioridad P1.
- Evidencia: `8faf1ef^` usa selectedIds Set y Array.from; `8faf1ef` retira eventos/Set junto al handler bulk.
- Vigencia: ACTIVE; no retiro autorizado.
- Endpoint: `POST /dashboard/brand/delete-multiple`, ids[] + CSRF; destroy_brand, tenant.
- Recuperación: ACTIVE — RESTORED R5. Mantiene alcance histórico de nodos filtrados; no selección implícita.
- QA independiente: fixtures 12 registros, navegación/payload interceptados; rollback servicio aislado. Sin mutación compartida.
- Blocker actual: ninguno demostrado en esta capacidad. QA mutadora real separada.


## R6 — StockItems / Items (2026-11-25)

Dos superficies revisadas/modificadas. Cinco pérdidas activas originales, ninguna nueva; cuatro recuperadas (FR-060/061/062/065), una bloqueada (FR-059 → INV-01). Cuatro gates read-only obsoletos; no exclusión legacy nueva. `btn-resumen-stock`/Nuevo material eran comentarios históricos; colvis sin controles y exports Dashboard no se cuentan como pérdidas nuevas R6. FR-063/064 siguen pendientes fuera de alcance.

QA: 7 tests /46 assertions SQLite; 16 combinaciones responsive +6 cargas de contexto en Chrome headless, 16 POST/PUT interceptados,0 reales; backend0/CSS0. Contratos de lectura EDESCE C1/B1,C1/B8,C2/B2,Gamarra revalidados. Paridad parcial, mutación real PENDING, no R7. Documento canónico: `REAL-NEXT-STOCKITEMS-ITEMS-FUNCTIONAL-PARITY-RECOVERY.md`.

### FR-102 — Importes/cantidad de almacén + refrescar

- Superficie: dashboard.principal / dashboard.dashboard; http://127.0.0.1:8080/dashboard/principal.
- Evidencia: control/handler alcanzable en dbca24e1^, retirado por dbca24e1; contrato HEAD contrastado R7.
- Vigencia: ACTIVE. No existía en el registro FR previo. No duplicar modal/filtros como pérdidas.
- Endpoint: `GET report/amount/items` (prefijo /dashboard/).
- Blocker: INV-01 / METRIC-01.
- Recuperación: ACTIVE — BACKEND BLOCKER.
- QA: Contrato estático; endpoint no ejecutado.
- Permisos/scope/payload y aceptación DEV: matriz/contratos de REAL-NEXT-DASHBOARD-FUNCTIONAL-PARITY-RECOVERY.md.

### FR-103 — Exportar materiales por almacén

- Superficie: dashboard.principal / dashboard.dashboard; http://127.0.0.1:8080/dashboard/principal.
- Evidencia: control/handler alcanzable en dbca24e1^, retirado por dbca24e1; contrato HEAD contrastado R7.
- Vigencia: ACTIVE. No existía en el registro FR previo. No duplicar modal/filtros como pérdidas.
- Endpoint: `GET report/excel/bd/materials/warehouse/{id}` (prefijo /dashboard/).
- Blocker: INV-01.
- Recuperación: ACTIVE — BACKEND BLOCKER.
- QA: Contrato estático; endpoint no ejecutado.
- Permisos/scope/payload y aceptación DEV: matriz/contratos de REAL-NEXT-DASHBOARD-FUNCTIONAL-PARITY-RECOVERY.md.

### FR-104 — Exportar entradas por compra/inventario

- Superficie: dashboard.principal / dashboard.dashboard; http://127.0.0.1:8080/dashboard/principal.
- Evidencia: control/handler alcanzable en dbca24e1^, retirado por dbca24e1; contrato HEAD contrastado R7.
- Vigencia: ACTIVE. No existía en el registro FR previo. No duplicar modal/filtros como pérdidas.
- Endpoint: `GET exportar/entradas/almacen/v2/` (prefijo /dashboard/).
- Blocker: Ninguno demostrado para restaurar; QA real de export pendiente.
- Recuperación: ACTIVE — RESTORED.
- QA: 4 tests/31 assertions SQLite, Chrome interceptado8 anchos PASS; export real PENDING.
- Permisos/scope/payload y aceptación DEV: matriz/contratos de REAL-NEXT-DASHBOARD-FUNCTIONAL-PARITY-RECOVERY.md.

### FR-105 — Historial de cortes de rotación

- Superficie: dashboard.principal / dashboard.dashboard; http://127.0.0.1:8080/dashboard/principal.
- Evidencia: control/handler alcanzable en dbca24e1^, retirado por dbca24e1; contrato HEAD contrastado R7.
- Vigencia: ACTIVE. No existía en el registro FR previo. No duplicar modal/filtros como pérdidas.
- Endpoint: `GET get/data/rotations/v2/{page}` (prefijo /dashboard/).
- Blocker: METRIC-01.
- Recuperación: ACTIVE — BACKEND BLOCKER.
- QA: Contrato estático; endpoint no ejecutado.
- Permisos/scope/payload y aceptación DEV: matriz/contratos de REAL-NEXT-DASHBOARD-FUNCTIONAL-PARITY-RECOVERY.md.

### FR-106 — Nuevo corte de rotación

- Superficie: dashboard.principal / dashboard.dashboard; http://127.0.0.1:8080/dashboard/principal.
- Evidencia: control/handler alcanzable en dbca24e1^, retirado por dbca24e1; contrato HEAD contrastado R7.
- Vigencia: ACTIVE. No existía en el registro FR previo. No duplicar modal/filtros como pérdidas.
- Endpoint: `GET store/rotation/material/` (prefijo /dashboard/).
- Blocker: DASH-R7-01 / INV-01 / METRIC-01.
- Recuperación: ACTIVE — BACKEND BLOCKER.
- QA: Contrato estático; endpoint no ejecutado.
- Permisos/scope/payload y aceptación DEV: matriz/contratos de REAL-NEXT-DASHBOARD-FUNCTIONAL-PARITY-RECOVERY.md.

### FR-107 — Personal mensual: tabla y proyección

- Superficie: dashboard.principal / dashboard.dashboard; http://127.0.0.1:8080/dashboard/principal.
- Evidencia: control/handler alcanzable en dbca24e1^, retirado por dbca24e1; contrato HEAD contrastado R7.
- Vigencia: ACTIVE. No existía en el registro FR previo. No duplicar modal/filtros como pérdidas.
- Endpoint: `GET personal/payments?year&month` (prefijo /dashboard/).
- Blocker: DASH-R7-01 / METRIC-01.
- Recuperación: ACTIVE — BACKEND BLOCKER.
- QA: Contrato estático; endpoint no ejecutado.
- Permisos/scope/payload y aceptación DEV: matriz/contratos de REAL-NEXT-DASHBOARD-FUNCTIONAL-PARITY-RECOVERY.md.

## R7 — cierre de revisión, no cierre de todos los blockers

Una superficie modificada;1 capacidad recuperada y7 pendientes. Backend0, CSS0, sin writes comerciales ni exports reales. R8 requiere revisión del propietario; quedan157 superficies por contraste semántico, ledger único en documento R7.


## Candidato R8 registrado durante R7.7D — 2026-11-25

QuoteSale Edit (/dashboard/editar/cotizacion/venta/79; familia /{id}): Guardar datos generales sigue comentado, observación R7.7C. No asumir retiro intencional. En R8 investigar Git/origen, intención, alcanzabilidad histórica, contrato backend y posible gate heredado; cotejar IDs existentes antes de crear otro finding. Sin intervención funcional ni FR nuevo en R7.7D. Ver informe OWNER-PLATFORM-STRUCTURE-LIMITS-HOMOLOGATION. Contabilidad vigente74 recuperadas+10 bloqueadas+23 excluidas=107. R8 no iniciada.
