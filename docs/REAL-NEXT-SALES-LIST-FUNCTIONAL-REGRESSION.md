# REAL NEXT — SALES LIST FUNCTIONAL REGRESSION

Fecha: 2026-11-24. HEAD `744d28b`. Working tree inicialmente limpio. Ruta: [Ventas](http://127.0.0.1:8080/dashboard/listado/ventas). [Errores SUNAT](http://127.0.0.1:8080/dashboard/listado/ventas/error).

## Dictamen

**SALES REGRESSION CONFIRMED: YES. FIRST COMMIT INTRODUCING LOSS: ffe0974da2c7a080d3b0dd6c52e2384a9ba38412 (2026-11-05).**

La integración sustituyó un listado operativo por una fachada de consulta. `resources/views/puntoVenta/list.blade.php` pasó de 1164 a 174 líneas, retiró modales/templates y dejó de cargar `list.js` y `list-access.js`. `public/js/puntoVenta/list-readonly.js:68–82` sólo construye detalle y enlaces documentales. Tener los handlers originales en disco no conserva su accesibilidad. El texto “Solo lectura” describe una reducción real, no sólo QA.

Causa: **INTENTIONAL READ-ONLY EXCLUSION**, con resultado **FRONTEND REGRESSION** en paridad de producto. Responsable técnico: integración frontend. No prueba una retirada permanente aprobada por Producto. Las instrucciones históricas sí limitaron el QA y algunas fases explícitamente pidieron excluir acciones; eso explica la implementación, pero no acredita una decisión permanente de retirar capacidades. El criterio actual del propietario exige recuperarlas de forma segura tras aprobar el plan.

## A–E: trazabilidad Git

| Punto | Evidencia | Conclusión |
|---|---|---|
| A Legacy inmediatamente anterior | `ffe0974^:resources/views/puntoVenta/list.blade.php`, `public/js/puntoVenta/list.js`, `list-access.js` | Plantillas de acciones + modales + handlers financieros activos |
| DEV `ec5f6dd` | Añade SaleListAccess/Options/Guard y list-access.js; cambios mínimos en list/listError | Refuerza autorización/contexto; NO elimina las operaciones de la vista |
| B primera integración | `ffe0974`, git show/log -S y blame del constructor actions | Primera pérdida demostrada; no es un merge |
| C SUNAT errors | `b52c3cd`, añade errorsMode y sustituye listError por adaptador compartido | Replica read-only en errores; `!errorsMode && permissions.print` suprime documentos sólo en errores. No origina la pérdida previa del listado general |
| D merges | `f367675`, `5fb180d`, `9816321` existen en historial | No hay evidencia de que originen la pérdida de Ventas: la atribución positiva está en ffe0974. 9816321 preserva operaciones de créditos/GRE; no repara list-readonly |
| E HEAD | `744d28b`; archivos runtime y endpoints vigentes | Persiste la exclusión. No cambios locales previos que la expliquen |

Comandos usados: git log por paths, git show, git diff entre parent/integración/HEAD, git log -S list-readonly.js, git log -G readOnly/read-only y git blame. No reset/revert/checkout.

## Runtime actual

- `PuntoVentaController::listar:1514` → `puntoVenta.list` + SaleListOptions.
- Blade `:15` texto Solo lectura; `:21` contexto/capacidades; `:159` único modal detalle; `:173` list-readonly.js.
- Errores usa el mismo adaptador con `data-list-mode=errors`.
- JSON `getSalesAdmin:1536`; contexto y visibilidad `SaleListAccess`; filtro actor/branch conservado.
- `list.js` y `list-access.js` existen, pero NO se cargan en estas dos fachadas. No basta reinsertarlos: les faltan DOM/templates/modales y el render es otro.
- El problema no depende sólo de activar allowlist: el Blade fue reemplazado. Desactivar CSS/allowlist no restaura las acciones.

## Permisos y dataset real (lectura 2026-11-24)

PHP7.3, bootstrap Laravel real, cache/session array sólo proceso; PDO MySQL con START TRANSACTION READ ONLY. Usuario existente4, Owner EDESCE, tenant1/company1/branch1. Auth::setUser en memoria, sin login ni password. No cambios de permisos/caché persistida. Invocación directa del controller de lectura, no certificación HTTP/cookies ni ejecución de guards mutadores.

Permisos directos relevantes: ninguno. Rol: owner. Permisos efectivos: list_puntoVenta, viewBranch_puntoVenta, print_puntoVenta, updateDispatch_puntoVenta, managePayments_puntoVenta, manageDocuments_puntoVenta, annul_puntoVenta, generarBoleta_puntoVenta, generarFactura_puntoVenta, listErrors_puntoVenta, además de capacidades POS/FreeSale. `SaleListAccess::permissions` devuelve **print/dispatch/payments/documents/annul/emit=true**. El frontend recibe capacidades, pero sólo usa print en actions. No Owner bypass inferido; WorkerAccess valida memberships company/branch.

| Venta | T/C/B; actor/worker | Documento / pago / despacho | DTO actual y acciones que corresponden |
|---|---|---|---|
| 122 | 1/1/1; actor4; workerNULL | Sin comprobante; completo; despachado; no anulada | can_annul=true; detalle + Ticket; emisión y anulación deberían ser accesibles con guard. Switch despacho existía. No modal de pagos parciales (flag n) |
| 120 | Igual | Sin comprobante; completo; despachado | Igual a122 |
| 119 | Igual | Sin comprobante; parcial; pendiente | estado_pago_parcial=verde; abonos21=100 y22=40, ambos activos, total140. Historial debe poder verse aunque saldo0. Registro/eliminación sujetos al backend/caja, no autorizar sobrepago desde UI. Emisión/anulación/despacho tienen capacidades presentes |
| 118 | Igual | Factura01; “La Factura Electrónica FFF1-9 ha sido ACEPTADA CON OBSERVACIONES”; completo; despachado | can_annul=true; can_generate_credit_note=false; ninguna NC pendiente/rechazada. No emitir otra factura. NC total no corresponde todavía; NC parcial cumple condiciones legacy, su elegibilidad definitiva requiere endpoint/guard actual. print_url=NULL, print_label=PDF no disponible: no inventar documento |
| 117,116,115 | Igual | Sin comprobante; completo; despachado | can_annul=true, sin NC; equivalentes a122 |

Todos pertenecen al contexto consultado. created_by y worker NO se intercambian. No se hicieron backfills. El JSON generado por CLI usa host APP_URL aliexpress.site:8080; no interpretar esa diferencia con127.0.0.1 como fallo HTTP observado. En navegador las URLs se resuelven con su request real.

## Matriz de paridad funcional Ventas

Unidad de conteo: capacidad funcional, no cantidad de botones de una fila. Baseline:17 capacidades verificadas (14 tipos de acciones de templates + despacho + historial y eliminación dentro de pagos). HEAD ofrece9 capacidades potenciales (detalle +8 enlaces), de las que6 ya estaban en ese baseline;3 son enlaces adicionales NC total PDF/XML/CDR. Por tanto **11 capacidades originales faltantes**. No afirmar que cada venta deba mostrar17 o9 botones.

`F` = ffe0974. Todas las ausencias F son INTENTIONAL READ-ONLY EXCLUSION / FRONTEND REGRESSION; no retiro aprobado. “Sí” indica disponible con permiso/estado, no ejecución QA aprobada.

| Acción | Legacy | Primera Next | HEAD | Permiso actual | Condición | Commit | Motivo / responsable | Recuperación / prioridad |
|---|---|---|---|---|---|---|---|---|
| Ver detalle | Sí | Sí | Sí | list_puntoVenta | Padre visible | — | FUNCTIONAL PARITY | Mantener |
| Ver Ticket/comprobante original | Sí | Sí | Sí condicional | print_puntoVenta | print_url segura y disponible | — | VALID BUSINESS STATE si sin archivo | Mantener ruta protegida |
| Recuperar archivos Nubefact | Sí | No | No | manageDocuments_puntoVenta | can_retry_nubefact_files | F | Exclusión frontend | Restaurar acción/confirmación P1; proveedor no se ejecuta en QA local |
| Anular Orden/venta | Sí | No | No | annul_puntoVenta | can_annul y guard; no pending/waiting/accepted | F | Exclusión frontend | Restaurar confirmación P0; caja/reversal deben validar |
| Consultar anulación | Sí | No | No | annul_puntoVenta | pending/waiting_sunat_process | F | Exclusión frontend | P1; POST externo, no tratar como GET inocuo |
| Generar comprobante | Sí | No | No | manageDocuments + generarBoleta/Factura | No comprobante válido; emisión/reconciliación backend | F | Exclusión frontend | Modal/handlers P0 |
| Registrar pago parcial | Sí | No | No | managePayments_puntoVenta | pagos_parciales_venta=s; caja elegible | F | Exclusión frontend | Modal P0; saldo/importe backend |
| Historial de pagos parciales | Sí dentro modal | No | No | list_puntoVenta + padre visible | pagos_parciales_venta=s, incluso saldo0 | F | Exclusión frontend | Lectura independiente P1 |
| Eliminar pago parcial | Sí dentro modal | No | No | managePayments_puntoVenta | pago propio/contexto; guard caja | F | Exclusión frontend | Confirmación P0; no ignorar guard de caja |
| Despacho editable | Sí switch | No | No | updateDispatch_puntoVenta | Venta autorizada; estado permitido | F | Sustituido por badge | Control operacional P0 |
| Generar NC total | Sí | No | No | annul_puntoVenta | requires_credit_note y sin NC existente | F | Exclusión frontend | P0; no mostrar para118 sólo por ser factura |
| Consultar NC | Sí | No | No | annul_puntoVenta | NC pendiente | F | Exclusión frontend | P1 |
| Generar NC parcial / selección Items | Sí | No | No | annul_puntoVenta | No FreeSale; doc01/03 válido; no anulada/NC pendiente/rechazada | F | Modal principal y selector Items retirados | P0; endpoint verifica cantidades/Items |
| Ver PDF NC parcial | Sí | Sí | Sí si URL | print_puntoVenta | Archivo existente autorizado | — | FUNCTIONAL PARITY | No link público fallback |
| PDF anulación | Sí | Sí | Sí si URL | print_puntoVenta | Archivo autorizado | — | FUNCTIONAL PARITY | Mantener |
| XML anulación | Sí | Sí | Sí si URL | print_puntoVenta | Archivo autorizado | — | FUNCTIONAL PARITY | Mantener |
| CDR anulación | Sí | Sí | Sí si URL | print_puntoVenta | Archivo autorizado | — | FUNCTIONAL PARITY | Mantener |

Otras superficies auxiliares: `modalFacturador`, btnGuardarDatos, btnGenerarComprobante y handlers data-facturador/data-imprimir_comprobante existían en archivo histórico; no se encontró trigger en el template de filas de esa versión. **UNKNOWN — EVIDENCE REQUIRED**, no sumar como función accesible perdida sin probar alcanzabilidad. mapModal/ruta generada tiene igual límite. Los diagnósticos de rechazo NC/solución SUNAT eran affordances auxiliares; comparación de contenido completo pendiente, no incluidos en las17 ni en el mínimo11. No se inventó “regularizar venta” como acción histórica de este listado.

## Contratos a recuperar (prefijo /dashboard)

| Función | Método y ruta vigente | Handler / modal legacy | Dependencia |
|---|---|---|---|
| Detalle | GET /sales/{orderId}/details | showDetails / orderDetailsModal | SaleListGuard read |
| Ticket/documentos | GET /imprimir/documento/venta/{id}; /sales/{id}/documents/{kind} | enlaces | print; storage/infra |
| Historial | GET /sale/partial-payment/{sale_id} | data-pagos_parciales / modalPagosParciales | read + padre |
| Registrar abono | POST /sale/partial-payment/store | btnGuardarPagoParcial / mismo modal | payments; caja/sesión |
| Eliminar abono | DELETE /sale/partial-payment/{id} | data-eliminar_pago_parcial | payments,payment,id |
| Emisión desde venta | POST /sale/generate-invoice | btnConfirmarGenerarComprobante / modalGenerarComprobante | emit,input,sale_id; tipo fiscal |
| Despacho | POST /sale/update-dispatch-status | switch-dispatch-status | dispatch,input,sale_id |
| Anulación | POST /anular/order/{order} | anularOrder / confirmación | annul; DeferredSaleRefund/CashRegularizationGuard |
| Consulta anulación | POST /consultar/anulacion/{id} | data-consultar_anulacion | annul; proveedor |
| NC total | POST /generar/nota-credito/total/{id} | data-generar_nota_credito | annul; proveedor |
| Consultar NC | POST /consultar/nota-credito/{id} | data-consultar_nota_credito | annul; proveedor |
| NC parcial opciones | GET /nota-credito/parcial/data/{id} | modalNotaCreditoParcial + modalNcPartialItems | annul; items/stock |
| NC parcial envío | POST /generar/nota-credito/parcial/{id} | btn-submit-nc-partial | annul; transacción/proveedor |
| Recuperación | POST /ventas/{sale}/recuperar-archivos-nubefact | data-recuperar_archivos_nubefact | documents; proveedor |
| Datos fiscales auxiliares | POST /sales/update-invoice-data; /facturador/generar | modalFacturador | documents/emit; alcanzabilidad legacy pendiente |

No copiar rutas antiguas /print/order ni llamadas públicas. FreeSale conserva su ruta especializada; no sustituir por venta común. Encabezados X-Sales-Tenant/Company/Branch y CSRF necesarios para mutaciones; list-readonly no aporta el prefilter legacy para esas rutas. Restaurar eventos sin esos headers produciría422. No aflojar SaleListGuard.

## Blockers y decisiones

1. **Documentos públicos/infra**: SaleListDocumentController y almacenamiento public/comprobantes. Guard de ruta no prueba inaccesibilidad estática. Riesgo exposición; permiso print. Backend/Infra debe proteger rutas físicas; alternativa sólo enlaces protegidos, sin fallback público. Impacto de mantener apagado: no consulta documental. Propietario decide alcance documental tras evidencia; no desbloquear archivos inseguros.
2. **Elegibilidad fiscal/caja**: SaleListGuard, DeferredSaleRefund, CashRegularizationGuard y requiresEmissionReview. No es prueba de que toda acción esté rota; son restricciones válidas por estado. Restaurar presentación de capacidad no equivale a aprobar ejecución. Alternativa: mensajes de backend fieles y QA aislado autorizado; no bypass.
3. **Código legacy no drop-in**: DOM retirado y nuevo adaptador. Frontend debe integrar acciones/modales con patrón Next y contratos vigentes. Reinsertar list.js entero duplicaría render/paginación/eventos; no hacerlo.
4. **Mutaciones y proveedor no ejecutados**: QA PENDING, no justificación automática para ocultar. No se encontró prueba nueva que obligue a eliminar universalmente pagos/historial/despacho. Backend fixes concretos sólo ante defecto reproducible, no pedir rehacer guards existentes.

## Plan para aprobación, sin implementación

1. Recuperar historial de pagos y navegación legítima, sin escrituras. Mostrar saldo0 correctamente.
2. Añadir adaptador operacional separado del render compartido, capabilities actuales, único menú …, modales Next con IDs/payloads actuales y snapshot/CSRF. Despacho/pagos/anulación/emisión permanecen con sus guards.
3. Integrar NC total/parcial, selección Items y reconciliación/recuperación; no ejecutar proveedor sin fase autorizada aislada.
4. Restaurar SUNAT Error de forma independiente: no compartir flags que amputen General; probar matrices normal/error/activa/anulada/pendiente/rechazada.
5. Tests de render/acciones con todas las combinaciones de permisos/estado. Pruebas mutadoras separadas con autorización y sandbox. No certificar mutaciones por conservar botones.

**Backend/frontend/permissions modificados: NO.** Sólo informes. No se abrieron documentos, no se ejecutaron operaciones, no se llamó Nubefact, no commit.
