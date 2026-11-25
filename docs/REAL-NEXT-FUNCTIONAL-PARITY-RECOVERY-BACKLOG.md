# REAL NEXT — FUNCTIONAL PARITY RECOVERY BACKLOG

Fecha: 2026-11-24. Baseline HEAD 744d28b; R0/R1 sin commit. Registro canónico de recuperación frontend, no duplicado del backlog DEV.

## Inventario y contabilidad

- Baseline: 76 pérdidas confirmadas en 30 superficies. IDs FR-001…FR-076 estables: no renumerar al cerrar ni borrar filas.
- R1: 13 capacidades implementadas (11 General, 2 Error SUNAT). R2: FR-014/015/016 recuperadas. Total16/76 originales implementadas;60 pendientes en otras familias. R2 fue autorizado después de revisar Ventas; no avanzar a R3 sin revisión.
- Nuevas pruebas de alcanzabilidad: FR-077/078 (rechazo NC y solución SUNAT) recuperadas. R2 añade FR-079 (sesión propia). Universo conocido ahora79; no es definitivo.
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

- Módulo/pantalla: `order.purchase.normal.indexV2` / `orderPurchase.normal_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear. Commit de pérdida: `abc9daa`.
- Permiso: `create_orderPurchaseNormal`. Condición: Acceso autorizado; lookups/GET requieren su contrato actual.
- Endpoint: `GET /dashboard/crear/orden/compra/normal`. Handler: `Enlace toolbar; route order.purchase.normal.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-018 — Editar

- Módulo/pantalla: `order.purchase.normal.indexV2` / `orderPurchase.normal_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar. Commit de pérdida: `abc9daa`.
- Permiso: `update_orderPurchaseNormal`. Condición: Orden propia editable; credit lock si Finance.
- Endpoint: `GET /dashboard/editar/orden/compra/normal/{id}`. Handler: `data-editar / enlace edición`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-019 — Anular

- Módulo/pantalla: `order.purchase.normal.indexV2` / `orderPurchase.normal_v2`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Anular. Commit de pérdida: `abc9daa`.
- Permiso: `destroy_orderPurchaseNormal`. Condición: Orden propia; estado admitido por backend.
- Endpoint: `POST /dashboard/destroy/order/purchase/normal/{id}`. Handler: `data-delete / data-anular → cancelOrden`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-020 — Cambiar estado

- Módulo/pantalla: `order.purchase.normal.indexV2` / `orderPurchase.normal_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Cambiar estado. Commit de pérdida: `abc9daa`.
- Permiso: `update_orderPurchaseNormal (ruta compartida; revisar permiso Express)`. Condición: Estado actual admite transición.
- Endpoint: `POST /dashboard/order_purchase/change/status/{order_id}/{status}`. Handler: `data-state → changeStatusOrder`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-021 — Imprimir

- Módulo/pantalla: `order.purchase.normal.indexV2` / `orderPurchase.normal_v2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/normal/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `abc9daa`.
- Permiso: `list_orderPurchaseNormal`. Condición: Documento propio; DOC-01 pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/{id}`. Handler: `data-imprimir / enlace documental`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01; sólo rutas protegidas, nunca fallback público; apertura QA pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-022 — Crear normal

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear normal. Commit de pérdida: `b1fa9d5`.
- Permiso: `create_orderPurchaseNormal`. Condición: Acceso autorizado; lookups/GET requieren su contrato actual.
- Endpoint: `GET /dashboard/crear/orden/compra/normal`. Handler: `Enlace toolbar; route order.purchase.normal.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-023 — Crear express

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear express. Commit de pérdida: `b1fa9d5`.
- Permiso: `create_orderPurchaseExpress`. Condición: Acceso autorizado; lookups/GET requieren su contrato actual.
- Endpoint: `GET /dashboard/crear/orden/compra/express`. Handler: `Enlace toolbar; route order.purchase.express.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-024 — Editar

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar. Commit de pérdida: `b1fa9d5`.
- Permiso: `update_orderPurchaseNormal`. Condición: Orden propia editable; credit lock si Finance.
- Endpoint: `GET /dashboard/editar/orden/compra/normal/{id}`. Handler: `data-editar / enlace edición`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-025 — Anular

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Anular. Commit de pérdida: `b1fa9d5`.
- Permiso: `destroy_orderPurchaseNormal`. Condición: Orden propia; estado admitido por backend.
- Endpoint: `POST /dashboard/destroy/order/purchase/normal/{id}`. Handler: `data-delete / data-anular → cancelOrden`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-026 — Cambiar estado

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Cambiar estado. Commit de pérdida: `b1fa9d5`.
- Permiso: `update_orderPurchaseNormal (ruta compartida; revisar permiso Express)`. Condición: Estado actual admite transición.
- Endpoint: `POST /dashboard/order_purchase/change/status/{order_id}/{status}`. Handler: `data-state → changeStatusOrder`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-027 — Imprimir

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `b1fa9d5`.
- Permiso: `list_orderPurchaseNormal`. Condición: Documento propio; DOC-01 pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/{id}`. Handler: `data-imprimir / enlace documental`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01; sólo rutas protegidas, nunca fallback público; apertura QA pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-028 — Exportar

- Módulo/pantalla: `order.purchase.general.indexV2` / `orderPurchase.general_v2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2](http://127.0.0.1:8080/dashboard/listado/general/ordenes/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Exportar. Commit de pérdida: `b1fa9d5`.
- Permiso: `list_orderPurchaseExpress en ruta compartida HEAD; revisar correspondencia con listado General antes de recuperar`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/exportar/reporte/ordenes/compra/v2/`. Handler: `btn-export → exportExcel (baseline 2ec61f3^)`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-029 — Crear

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear. Commit de pérdida: `3ed0b46`.
- Permiso: `create_orderPurchaseExpress`. Condición: Acceso autorizado; lookups/GET requieren su contrato actual.
- Endpoint: `GET /dashboard/crear/orden/compra/express`. Handler: `Enlace toolbar; route order.purchase.express.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-030 — Editar

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar. Commit de pérdida: `3ed0b46`.
- Permiso: `update_orderPurchaseExpress`. Condición: Orden propia editable; credit lock si Finance.
- Endpoint: `GET /dashboard/editar/orden/compra/express/{id}`. Handler: `data-editar / enlace edición`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-031 — Anular

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Anular. Commit de pérdida: `3ed0b46`.
- Permiso: `destroy_orderPurchaseExpress`. Condición: Orden propia; estado admitido por backend.
- Endpoint: `POST /dashboard/destroy/order/purchase/express/{id}`. Handler: `data-delete / data-anular → cancelOrden`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-032 — Cambiar estado

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Cambiar estado. Commit de pérdida: `3ed0b46`.
- Permiso: `update_orderPurchaseNormal (ruta compartida; revisar permiso Express)`. Condición: Estado actual admite transición.
- Endpoint: `POST /dashboard/order_purchase/change/status/{order_id}/{status}`. Handler: `data-state → changeStatusOrder`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-033 — Imprimir

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `3ed0b46`.
- Permiso: `list_orderPurchaseExpress`. Condición: Documento propio; DOC-01 pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/{id}`. Handler: `data-imprimir / enlace documental`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01; sólo rutas protegidas, nunca fallback público; apertura QA pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-034 — Detalle

- Módulo/pantalla: `order.purchase.express.indexV2` / `orderPurchase.express_v2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2](http://127.0.0.1:8080/dashboard/listado/ordenes/compra/express/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Detalle. Commit de pérdida: `3ed0b46`.
- Permiso: `list_orderPurchaseExpress`. Condición: Orden visible en contexto.
- Endpoint: `GET /dashboard/ver/orden/compra/express/{id}`. Handler: `data-ver_orden / enlace detalle`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-035 — Restaurar

- Módulo/pantalla: `order.purchase.delete` / `orderPurchase.deleteGeneral`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas](http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Restaurar. Commit de pérdida: `c32352c`.
- Permiso: `destroy_orderPurchaseNormal`. Condición: Orden eliminada propia; referencias y restauración backend.
- Endpoint: `POST /dashboard/restore/order/purchase/delete/{id}`. Handler: `data-restore → restoreOrden`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-036 — Imprimir

- Módulo/pantalla: `order.purchase.delete` / `orderPurchase.deleteGeneral`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas](http://127.0.0.1:8080/dashboard/ordenes/compra/eliminadas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `c32352c`.
- Permiso: `list_orderPurchaseNormal`. Condición: Documento propio; DOC-01 pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/eliminada/{id}`. Handler: `data-imprimir / enlace documental`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01; sólo rutas protegidas, nunca fallback público; apertura QA pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-037 — Crear

- Módulo/pantalla: `order.purchase.finance.index` / `orderPurchaseFinance.indexOrderFinance`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear. Commit de pérdida: `483a785`.
- Permiso: `create_orderPurchaseFinance`. Condición: Acceso autorizado; lookups/GET requieren su contrato actual.
- Endpoint: `GET /dashboard/crear/orden/compra/finanzas`. Handler: `Enlace toolbar; route order.purchase.finance.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-038 — Detalle

- Módulo/pantalla: `order.purchase.finance.index` / `orderPurchaseFinance.indexOrderFinance`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Detalle. Commit de pérdida: `483a785`.
- Permiso: `list_orderPurchaseFinance`. Condición: Orden visible en contexto.
- Endpoint: `GET /dashboard/ver/orden/compra/finanzas/{id}`. Handler: `data-ver_orden / enlace detalle`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-039 — Editar

- Módulo/pantalla: `order.purchase.finance.index` / `orderPurchaseFinance.indexOrderFinance`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar. Commit de pérdida: `483a785`.
- Permiso: `update_orderPurchaseFinance`. Condición: Orden propia editable; credit lock si Finance.
- Endpoint: `GET /dashboard/editar/orden/compra/finanza/{id}`. Handler: `data-editar / enlace edición`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-040 — Eliminar

- Módulo/pantalla: `order.purchase.finance.index` / `orderPurchaseFinance.indexOrderFinance`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar. Commit de pérdida: `483a785`.
- Permiso: `destroy_orderPurchaseFinance`. Condición: Orden propia; estado admitido por backend.
- Endpoint: `POST /dashboard/destroy/order/purchase/finance/{id}`. Handler: `data-delete / data-anular → cancelOrden`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
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
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-042 — Restaurar

- Módulo/pantalla: `order.purchase.finance.delete` / `orderPurchaseFinance.indexFinanceDeleted`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/eliminadas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/eliminadas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Restaurar. Commit de pérdida: `744d28b`.
- Permiso: `destroy_orderPurchaseFinance`. Condición: Orden eliminada propia; referencias y restauración backend.
- Endpoint: `POST /dashboard/restore/order/purchase/finance/delete/{id}`. Handler: `data-restore → restoreOrden`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
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
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-044 — Detalle

- Módulo/pantalla: `order.purchase.finance.list.regularize` / `orderPurchaseFinance.indexRegularizeFinance`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/regularizadas](http://127.0.0.1:8080/dashboard/ordenes/compra/finanzas/regularizadas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Detalle. Commit de pérdida: `744d28b`.
- Permiso: `list_orderPurchaseFinance`. Condición: Orden visible en contexto.
- Endpoint: `GET /dashboard/ver/orden/compra/finanzas/{id}`. Handler: `data-ver_orden / enlace detalle`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
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
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-046 — Crear

- Módulo/pantalla: `entry.purchase.indexV2` / `entry.index_entry_purchasev2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2](http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear. Commit de pérdida: `2ec61f3`.
- Permiso: `create_entryPurchase`. Condición: Entry propia; guard/backend y estado de recepción.
- Endpoint: `GET /dashboard/crear/entrada/compra`. Handler: `Enlace toolbar`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-047 — Editar

- Módulo/pantalla: `entry.purchase.indexV2` / `entry.index_entry_purchasev2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2](http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar. Commit de pérdida: `2ec61f3`.
- Permiso: `update_entryPurchase`. Condición: Entry propia; guard/backend y estado de recepción.
- Endpoint: `GET /dashboard/entrada/compra/editar/{entry}`. Handler: `data-edit / enlace edición`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-048 — Anular

- Módulo/pantalla: `entry.purchase.indexV2` / `entry.index_entry_purchasev2`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2](http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Anular. Commit de pérdida: `2ec61f3`.
- Permiso: `destroy_entryPurchase`. Condición: Entry propia; guard/backend y estado de recepción.
- Endpoint: `POST /dashboard/entry_purchase/destroy/{entry}`. Handler: `data-delete / handler de anulación`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-049 — Documentos

- Módulo/pantalla: `entry.purchase.indexV2` / `entry.index_entry_purchasev2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2](http://127.0.0.1:8080/dashboard/listado/entradas/compra/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Documentos. Commit de pérdida: `2ec61f3`.
- Permiso: `update_entryPurchase para documentos extras; adjuntos públicos pendientes DOC-01`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/agregar/documentos/extras/entrada/{id}; legacy /images/entries/{image}`. Handler: `data-documentos; data-pdf; data-image → showImage`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01; sólo rutas protegidas, nunca fallback público; apertura QA pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-050 — Recibir entrada

- Módulo/pantalla: `order.purchase.list.indexV2` / `entry.listOrderPurchaseV2`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2](http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Recibir entrada. Commit de pérdida: `2ec61f3`.
- Permiso: `create_entryPurchase; can_receive=true`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/crear/entrada/compra/orden/{id}`. Handler: `data-crear_entrada → enlace`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-051 — Ver orden

- Módulo/pantalla: `order.purchase.list.indexV2` / `entry.listOrderPurchaseV2`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2](http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Ver orden. Commit de pérdida: `2ec61f3`.
- Permiso: `list_orderPurchaseNormal`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/ver/orden/compra/normal/{id}`. Handler: `data-ver_orden → enlace`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-052 — Imprimir

- Módulo/pantalla: `order.purchase.list.indexV2` / `entry.listOrderPurchaseV2`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2](http://127.0.0.1:8080/dashboard/entradas/compra/ordenes/v2). Parámetros requieren registro contextual existente.
- Funcionalidad original: Imprimir. Commit de pérdida: `2ec61f3`.
- Permiso: `list_orderPurchaseNormal`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/imprimir/orden/compra/{id}`. Handler: `data-imprimir → enlace`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01; sólo rutas protegidas, nunca fallback público; apertura QA pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-053 — Detalle de ítems

- Módulo/pantalla: `entry.purchase.report.index` / `entry.index_entry_purchase_report`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/entradas/compra/reporte](http://127.0.0.1:8080/dashboard/entradas/compra/reporte). Parámetros requieren registro contextual existente.
- Funcionalidad original: Detalle de ítems. Commit de pérdida: `6097da5`.
- Permiso: `Sin permission middleware específico en get/json/items; revisar guard ItemController antes de recuperar (INV-01)`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/get/json/items/{entry_id}`. Handler: `showItems; confirmar trigger efectivo al recuperar reporte`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-054 — Adjunto

- Módulo/pantalla: `entry.purchase.report.index` / `entry.index_entry_purchase_report`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/entradas/compra/reporte](http://127.0.0.1:8080/dashboard/entradas/compra/reporte). Parámetros requieren registro contextual existente.
- Funcionalidad original: Adjunto. Commit de pérdida: `6097da5`.
- Permiso: `Sin autorización de archivo demostrada; DOC-01`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /images/entries/{image} (legacy público; NO recuperar como fallback)`. Handler: `data-image → showImage; enlace adjunto`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: DOC-01; sólo rutas protegidas, nunca fallback público; apertura QA pendiente.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-055 — Crear

- Módulo/pantalla: `invoice.index` / `invoice.index_invoice`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/factura/compra](http://127.0.0.1:8080/dashboard/factura/compra). Parámetros requieren registro contextual existente.
- Funcionalidad original: Crear. Commit de pérdida: `fa8f7ed`.
- Permiso: `create_invoice`. Condición: FinanceInvoiceContext y validación backend.
- Endpoint: `GET /dashboard/crear/factura/compra`. Handler: `Enlace invoice.create`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-056 — Anular

- Módulo/pantalla: `invoice.index` / `invoice.index_invoice`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/factura/compra](http://127.0.0.1:8080/dashboard/factura/compra). Parámetros requieren registro contextual existente.
- Funcionalidad original: Anular. Commit de pérdida: `fa8f7ed`.
- Permiso: `destroy_invoice`. Condición: FinanceInvoiceContext y validación backend.
- Endpoint: `POST /dashboard/destroy/total/invoice/{id}`. Handler: `data-delete → deleteItem`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-057 — Exportar

- Módulo/pantalla: `report.invoice.finance` / `invoice.report_invoice`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas](http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Exportar. Commit de pérdida: `fa8f7ed`.
- Permiso: `list_invoice`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/exportar/reporte/factura`. Handler: `btn-export → exportExcel`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-058 — Exportar

- Módulo/pantalla: `report.invoice.finance.sin.orden` / `invoice.report_invoice_sin_orden`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas/sin/orden](http://127.0.0.1:8080/dashboard/reporte/faturas/finanzas/sin/orden). Parámetros requieren registro contextual existente.
- Funcionalidad original: Exportar. Commit de pérdida: `fa8f7ed`.
- Permiso: `list_invoice`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `GET /dashboard/exportar/reporte/factura`. Handler: `btn-export → exportExcel`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-059 — Ajustar stock

- Módulo/pantalla: `stockitems.index` / `stockItem.list`. Prioridad: P0.
- URL local: [http://127.0.0.1:8080/dashboard/stock-items](http://127.0.0.1:8080/dashboard/stock-items). Parámetros requieren registro contextual existente.
- Funcionalidad original: Ajustar stock. Commit de pérdida: `6de4406`.
- Permiso: `MISSING EXPLICIT PERMISSION (INV-01 familia; evidencia en auditoría global)`. Condición: NO reactivar sin ownership y autorización demostrados.
- Endpoint: `POST /dashboard/fix/stock-item/output-adjustment`. Handler: `modalAdjustStock / handler ajuste legacy`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: INV-01 / evidencia ajuste en auditoría global: permiso/ownership no demostrados; requiere DEV/Producto.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-060 — Mínimo/máximo

- Módulo/pantalla: `stockitems.index` / `stockItem.list`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/stock-items](http://127.0.0.1:8080/dashboard/stock-items). Parámetros requieren registro contextual existente.
- Funcionalidad original: Mínimo/máximo. Commit de pérdida: `6de4406`.
- Permiso: `update_material + MaterialInventoryGuard`. Condición: Permiso efectivo y estado del registro; cierre semántico individual pendiente.
- Endpoint: `POST /dashboard/stock-items/{stockItem}/inventory-levels`. Handler: `btn-save-inventory-levels / inventory-context.js`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-061 — Inventariable

- Módulo/pantalla: `stockitems.index` / `stockItem.list`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/stock-items](http://127.0.0.1:8080/dashboard/stock-items). Parámetros requieren registro contextual existente.
- Funcionalidad original: Inventariable. Commit de pérdida: `6de4406`.
- Permiso: `configInventariable_material + StockItemScreenGuard`. Condición: MaterialInventoryGuard / contexto actual.
- Endpoint: `POST /dashboard/stock-items/{id}/toggle-inventory`. Handler: `updateInventoryToggle`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-062 — Activo

- Módulo/pantalla: `stockitems.index` / `stockItem.list`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/stock-items](http://127.0.0.1:8080/dashboard/stock-items). Parámetros requieren registro contextual existente.
- Funcionalidad original: Activo. Commit de pérdida: `6de4406`.
- Permiso: `configActive_material + StockItemScreenGuard`. Condición: MaterialInventoryGuard / contexto actual.
- Endpoint: `POST /dashboard/stock-items/{id}/toggle-active`. Handler: `updateActiveToggle`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-063 — Exportar importes

- Módulo/pantalla: `dashboard.principal` / `dashboard.dashboard`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/principal](http://127.0.0.1:8080/dashboard/principal). Parámetros requieren registro contextual existente.
- Funcionalidad original: Exportar importes. Commit de pérdida: `dbca24e1`.
- Permiso: `PENDING EFFECTIVE EXPORT PERMISSION`. Condición: Filtros/contexto del reporte; QA-01/DOC-01.
- Endpoint: `GET /dashboard/report/excel/amount/stock`. Handler: `Enlace report.excel.amount`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-064 — Exportar materiales

- Módulo/pantalla: `dashboard.principal` / `dashboard.dashboard`. Prioridad: P2.
- URL local: [http://127.0.0.1:8080/dashboard/principal](http://127.0.0.1:8080/dashboard/principal). Parámetros requieren registro contextual existente.
- Funcionalidad original: Exportar materiales. Commit de pérdida: `dbca24e1`.
- Permiso: `PENDING EFFECTIVE EXPORT PERMISSION`. Condición: Filtros/contexto del reporte; QA-01/DOC-01.
- Endpoint: `GET /dashboard/report/excel/bd/materials`. Handler: `Enlace report.excel.materials`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-065 — Editar código ítem

- Módulo/pantalla: `material.stock.getItems` / `material.stockItems_items`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/view/stock/material/items/{id}](http://127.0.0.1:8080/dashboard/view/stock/material/items/{id}). Parámetros requieren registro contextual existente.
- Funcionalidad original: Editar código ítem. Commit de pérdida: `6de4406`.
- Permiso: `editarCodeItems_material`. Condición: StockItemScreenGuard; Item propio.
- Endpoint: `PUT /dashboard/stock/material/item/{item}/code`. Handler: `Editor código legacy en vista material.items; reconectar con items.update.code`.
- Motivo de exclusión: INTENTIONAL READ-ONLY EXCLUSION; no retiro permanente aprobado.
- Blocker actual: Contrato de operación HEAD pendiente de cerrar antes de recuperar; no afirmar seguridad por existencia de ruta.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-066 — Eliminar selección

- Módulo/pantalla: `materialtype.index` / `materialtype.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/TiposMateriales](http://127.0.0.1:8080/dashboard/TiposMateriales). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_materialtype`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/materialtype/delete-multiple`. Handler: `data-backend-sync-open; handler delete-multiple en public/js/materialtype/index.js (confirmar alias antes de implementar)`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Revisar gate obsoleto individualmente; QA-02. No reactivar en R1.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-067 — Eliminar selección

- Módulo/pantalla: `subtype.index` / `subtype.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Subtipos](http://127.0.0.1:8080/dashboard/Subtipos). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_subtype`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/subtype/delete-multiple`. Handler: `data-backend-sync-open; handler delete-multiple en public/js/subtype/index.js (confirmar alias antes de implementar)`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Revisar gate obsoleto individualmente; QA-02. No reactivar en R1.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-068 — Eliminar selección

- Módulo/pantalla: `category.index` / `category.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Categorias](http://127.0.0.1:8080/dashboard/Categorias). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_category`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/category/delete-multiple`. Handler: `data-backend-sync-open; handler delete-multiple en public/js/category/index.js (confirmar alias antes de implementar)`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Revisar gate obsoleto individualmente; QA-02. No reactivar en R1.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-069 — Eliminar selección

- Módulo/pantalla: `subcategory.index` / `subcategory.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Subcategorias](http://127.0.0.1:8080/dashboard/Subcategorias). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_subcategory`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/subcategory/delete-multiple`. Handler: `data-backend-sync-open; handler delete-multiple en public/js/subcategory/index.js (confirmar alias antes de implementar)`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Revisar gate obsoleto individualmente; QA-02. No reactivar en R1.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-070 — Eliminar selección

- Módulo/pantalla: `exampler.index` / `exampler.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Modelos](http://127.0.0.1:8080/dashboard/Modelos). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_exampler`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/exampler/delete-multiple`. Handler: `data-backend-sync-open; handler delete-multiple en public/js/exampler/index.js (confirmar alias antes de implementar)`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Revisar gate obsoleto individualmente; QA-02. No reactivar en R1.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-071 — Eliminar selección

- Módulo/pantalla: `brand.index` / `brand.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Marcas](http://127.0.0.1:8080/dashboard/Marcas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_brand`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/brand/delete-multiple`. Handler: `data-backend-sync-open; handler delete-multiple en public/js/brand/index.js (confirmar alias antes de implementar)`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Revisar gate obsoleto individualmente; QA-02. No reactivar en R1.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-072 — Eliminar selección

- Módulo/pantalla: `typescrap.index` / `typeScrap.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Retacerías](http://127.0.0.1:8080/dashboard/Retacerías). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_typescrap`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/typescrap/delete-multiple`. Handler: `data-backend-sync-open; handler delete-multiple en public/js/typescrap/index.js (confirmar alias antes de implementar)`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Revisar gate obsoleto individualmente; QA-02. No reactivar en R1.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-073 — Eliminar selección

- Módulo/pantalla: `unitmeasure.index` / `unitMeasure.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/Unidades](http://127.0.0.1:8080/dashboard/Unidades). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_unitmeasure`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/unitmeasure/delete-multiple`. Handler: `data-backend-sync-open; handler delete-multiple en public/js/unitmeasure/index.js (confirmar alias antes de implementar)`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Revisar gate obsoleto individualmente; QA-02. No reactivar en R1.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-074 — Eliminar selección

- Módulo/pantalla: `genero.index` / `genero.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/generos](http://127.0.0.1:8080/dashboard/generos). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_genero`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/genero/delete-multiple`. Handler: `data-backend-sync-open; handler delete-multiple en public/js/genero/index.js (confirmar alias antes de implementar)`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Revisar gate obsoleto individualmente; QA-02. No reactivar en R1.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-075 — Eliminar selección

- Módulo/pantalla: `talla.index` / `talla.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/tallas](http://127.0.0.1:8080/dashboard/tallas). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_talla`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/talla/delete-multiple`. Handler: `data-backend-sync-open; handler delete-multiple en public/js/talla/index.js (confirmar alias antes de implementar)`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Revisar gate obsoleto individualmente; QA-02. No reactivar en R1.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-076 — Eliminar selección

- Módulo/pantalla: `color.index` / `color.index`. Prioridad: P1.
- URL local: [http://127.0.0.1:8080/dashboard/colors](http://127.0.0.1:8080/dashboard/colors). Parámetros requieren registro contextual existente.
- Funcionalidad original: Eliminar selección. Commit de pérdida: `8faf1ef`.
- Permiso: `destroy_color`. Condición: Selección no vacía; ownership/referencias del servicio; no levantar gate sin revisión individual.
- Endpoint: `POST /dashboard/color/delete-multiple`. Handler: `data-backend-sync-open; handler delete-multiple en public/js/color/index.js (confirmar alias antes de implementar)`.
- Motivo de exclusión: BACKEND SYNC gate frontend; no retiro permanente aprobado.
- Blocker actual: Revisar gate obsoleto individualmente; QA-02. No reactivar en R1.
- Responsable: Frontend recuperación; Backend por guards/contratos o finding vinculado; Producto por retiro o tratamiento de riesgo explícito.
- Recuperación: PENDING RECOVERY — fuera de R1.
- QA independiente: PENDING — no operación ejecutada.
- Condición de cierre: trigger/DOM/handler vigente, permisos y estados probados; diferencias resueltas; certificación mutadora/documental separada sin ocultar la capacidad.

### FR-077 — Diagnóstico de NC rechazada

- Módulo/pantalla: puntoVenta.list. URL: [Ventas](http://127.0.0.1:8080/dashboard/listado/ventas).
- Original: trigger generado por renderDataTable y handler `ver_error_nota_credito` en `ffe0974^:public/js/puntoVenta/list.js`; pérdida ffe0974.
- Permiso: list_puntoVenta + objeto visible. Condición: `has_rejected_credit_note`.
- Endpoint: ninguno adicional; diagnóstico del DTO `GET /dashboard/get/data/sales/{page}`. Handler: `data-ver_error_nota_credito → act → notice`.
- Motivo: exclusión read-only del renderer. Blocker: ninguno demostrado.
- Responsable: Frontend. Recuperación: IMPLEMENTED R1. QA: STATIC/UNIT PASS; mensaje escapado; no proveedor.
- Cierre: acceso condicional y texto seguro preservados; no ejecutar operaciones externas para mostrar diagnóstico.

### FR-078 — Solución de error SUNAT

- Módulo/pantalla: puntoVenta.list. URL: [Ventas](http://127.0.0.1:8080/dashboard/listado/ventas).
- Original: trigger generado por renderDataTable y handler `ver_solucion_sunat_error` en `ffe0974^:public/js/puntoVenta/list.js`; pérdida ffe0974.
- Permiso: list_puntoVenta + objeto visible. Condición: `sunat_error_is_discarded`.
- Endpoint: ninguno adicional; diagnóstico del DTO `GET /dashboard/get/data/sales/{page}`. Handler: `data-ver_solucion_sunat_error → act → notice`.
- Motivo: exclusión read-only del renderer. Blocker: ninguno demostrado.
- Responsable: Frontend. Recuperación: IMPLEMENTED R1. QA: STATIC/UNIT PASS; mensaje escapado; no proveedor.
- Cierre: acceso condicional y texto seguro preservados; no ejecutar operaciones externas para mostrar diagnóstico.

### FR-079 — Navegar a sesión propia desde Admin

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
