# REAL NEXT — R8.2B — Normal Purchase Receipt Homologation

2026-11-25. `feature/venti-next-frontend`, baseline `6df57e7`, PHP7.3.33. **1/1 superficie homologada; R8.2B COMPLETE.** FR-050 ya recuperado: ninguna capacidad nueva recuperada/perdida. **76 recuperadas +11 bloqueadas +23 excluidas =110**.

## Superficie y lectura autorizada

- URL: http://127.0.0.1:8080/dashboard/crear/entrada/compra/orden/2
- GET `order.purchase.create`, `EntryController::createEntryOrder`, permiso `create_entryPurchase`; middleware auth/contexto existentes, sin cambios.
- Blade `entry.create_entry_purchase_order`; allowlist añade exclusivamente `order.purchase.create`. No URI wildcard ni endpoints añadidos.
- POST existente `entry.purchase.order.store`, `/dashboard/entry/purchase/order/store`; JavaScript `normalPurchaseReceipt.js` intacto.
- Reutilizada evidencia R8-A02/S091 y referencias Crear Entrada V2 / Crear-Editar Normal. No repetición de auditoría semántica ni certificación DEV.

Lectura actual con Owner EDESCE, tenant1/company1/branch1, permiso comprobado mediante PermissionMiddleware y contexto mediante InitializeTenantContext. Orden2 OC-00002 admite recepción: estado2/POR INGRESAR, dos líneas, cada una pedido10/recibido0/pendiente10, sin errores. Almacenes seleccionables8 (Local Principal, ubicación8, default) y1 (general GEN, ubicación1). La orden4 está completa; no se modifica ni se usa para forzar recepción.

Render PHP autorizado mediante controller/vista y DB `START TRANSACTION READ ONLY`, finalizado con ROLLBACK. No se presenta como login HTTP nuevo. Se reutilizó el arnés externo r8-read.php; su supresión de Audit es sólo del proceso QA heredado, no cambio de aplicación. No mutaciones comerciales ni consultas a proveedores.

## Homologación visual

Encabezado Next y breadcrumb conservando destino Órdenes para almacén; título de orden visible también en móvil. Cards neutrales, `next-form-section`, grid Bootstrap proporcional y grupos relacionados en fila en escritorio. Información de orden/proveedor/moneda sólo lectura. Fecha/comprobante/guía/diferido juntos cuando hay ancho; adjuntos/observación en fila desde xl.

Tabla `next-data-table` compacta: descripciones izquierda, cantidades centradas y pendiente destacado; controles operativos existentes, scroll horizontal local accesible. Alertas originales conservadas. Footer usa toolbar/action group aprobado, Guardar primario con icono y Volver secundario; ambos reorganizan en móvil. No CSS nuevo, estilos inline ni controles alternativos. No se transforma la recepción en Crear Entrada directa.

## Contratos preservados

Comparación de HTML antes/después: **25 controles** con id/name/type, valores, min/max/step, required/disabled/readonly, hooks data-*, opciones y ubicación asociada idénticos. Sólo diferencias de presentación/accesibilidad y orden visual; receipt_key generado al abrir se normaliza en comparación entre renders separados, pero permanece idéntico en los reintentos del mismo formulario.

Se conservan action, POST, multipart/form-data, CSRF, purchase_order_id, receipt_key, entry_context_tenant_id/company_id/branch_id, date_invoice d/m/Y, invoice, referral_guide, image/imageOb, observation, deferred_invoice, IDs de línea, cantidad pendiente, destino, lote y vencimiento. Ubicación no es un selector nuevo: procede de `data-location` de la opción de almacén actual, como antes.

`$canReceive`, restricciones de configuración/orden completa y disabled por pendiente permanecen literalmente iguales. No cambios a reglas monetarias, idempotencia ni permisos. JS funcional idéntico a HEAD.

## QA focalizada

Chrome headless aislado, sin controlar escritorio ni navegador personal. HTML renderizado con datos reales de lectura; assets reales locales; toda red del navegador interceptada. Los POST de prueba se respondieron con422 simulado, sin llegar a Laravel/DB.

| Prueba | Resultado |
|---|---|
| 320 /375 /390 /576 /768 /992 /1200 /1440 /1920 /2560 | PASS; diez capturas, cero overflow global |
| Densidad escritorio | Fecha/comprobante/guía en una fila; anchos proporcionales; adjuntos/observación aprovechan ancho |
| Precarga / líneas / Guardar | PASS; dos líneas reales, default de almacén8, Guardar disponible legítimamente |
| Comparación funcional antes/después | PASS;25 controles y contrato de formulario |
| Cantidad0 | Rechazo cliente correcto, cero POST |
| Destino vacío con cantidad1 | Rechazo cliente correcto, cero POST |
| Cambio almacén8→1 | Payload usa warehouse_id1 / id_location1, no ubicación anterior |
| Adjuntos | PNGs mínimos sintéticos qa.png y qa-ob.png incluidos en FormData; nunca subidos |
| Dos intentos con422 simulado | PASS; mismo receipt_key, items y contexto; mensaje visible, campos conservados y Guardar habilitado nuevamente |
| items JSON | ID de línea original, entered1, warehouse_id1, id_location1, material_lote QA-LOT, date_vence2027-12-31 |
| Contexto / fecha / diferido / CSRF | PASS; purchase_order_id2, tenant1/company1/branch1, d/m/Y, deferred_invoice on, token presente |
| Consola | 0 pageerrors |
| PHP7.3.33 artisan view:cache | PASS |
| PHP7.3.33 -l config/venti-next.php | PASS |
| git diff --check | PASS |

No se certifica éxito de recepción/persistencia; QA mutador real no ejecutado por alcance. No tests backend de idempotencia repetidos. La inspección visual detectó que `next-toolbar-context` se ocultaba en móvil; se utilizó el título card aprobado, sin tocar CSS compartido.

## Evidencia y archivos

Arnés externo: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r82b-browser.cjs`. JSON `r82b-evidence.json`; capturas `r82b-{width}.png`, anterior `r82b-before-1440.png`; render de lectura `r8-receipt.html`. Rechazos/payloads simulados identificados en evidencia; token redactado.

Archivos de aplicación:1 Blade y1 allowlist. Documentación: este informe, inventario y plan R8. Ledger externo refleja homologación S091 sin renumeración ni reabrir auditoría. Cohorte B histórica sigue documentada como2:1 homologada y1 pendiente (Registrar comprobante).

**Backend0 / rutas0 / permisos0 / JS0 / CSS0 / infraestructura0 / mutaciones comerciales0 / findings DEV nuevos0.** Sin commit/push/merge. R8.2C no iniciada; esperar revisión del propietario.
