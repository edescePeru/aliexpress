# REAL NEXT — Shipping Guide Create Remitente

Fecha: 2026-11-22. Estado: **FRONTEND IMPLEMENTED — MANUAL VISUAL QA PENDING**.
Implementación sobre el contrato moderno vigente. Backend, rutas, datos comerciales y CSS sin cambios. Sin commit.

## Verified

- PHP 7.3.33: `artisan view:cache` PASS; lint de config PASS.
- `node --check public/js/shipping_guides/create.js` PASS.
- `ShippingGuideReadContractTest`: 2 tests / 35 assertions PASS, SQLite aislado. Cubre lectura list/detail entre tenant/company/branch, filtros y contexto ausente; no es certificación HTTP de Create/Store.
- `ShippingGuideCreateContractTest --filter test_create_type_and_tenant_customer_catalog`: 1 test / 8 assertions PASS, SQLite aislado. No se ejecutaron los métodos históricos que invocan Store.
- Comparación estática contra HEAD: los 40 controles nombrados del Blade (incluido modal auxiliar) mantienen name, tipo, valor, readonly, required, min/max, step y maxlength. IDs funcionales preservados; sin duplicados. Se retiraron sólo wrappers de acordeón sin consumidor funcional.
- HTTP autenticado real con Owner EDESCE y Owner Gamarra; Create 200 y ambos assets `venti.min.css` / `venti-next.css` 200.
- `git diff --check` PASS.
- Smoke visual inicial en Chrome, Owner EDESCE, sin Store. Se observaron encabezado, origen, destinatario, traslado y partida; se corrigió el wrap del botón auxiliar de cliente usando clases existentes. Esto no sustituye la matriz responsive completa.

## Route / Permission

| Método | Ruta / nombre | Contrato |
| --- | --- | --- |
| GET | `/dashboard/shipping-guides/create?type=remitente` / `shipping_guides.create` | Vista objetivo |
| GET | `/dashboard/listado/sales/select2/` / `sales.select2` | Búsqueda remota paginada q/page |
| GET | `/dashboard/sales/{sale}/items` / `sales.items` | Líneas por venta |
| GET | `/dashboard/shipping-guides/sale-items` / `shipping_guides.sale_items` | Líneas por sale_ref; contrato conservado |
| GET | `/dashboard/shipping-guides/customer/{id}/payload` / `shipping_guides.customer_payload` | Datos de cliente |
| GET | `/dashboard/shipping-guides/products/select2` / `shipping_guides.products` | Catálogo manual autorizado |
| POST | `/dashboard/shipping-guides` / `shipping_guides.store` | Preservado; NO ejecutado |

Create y endpoints GRE anteriores protegidos por `create_referralGuide`, rutas y constructor de ShippingGuideController. El middleware valida contexto existente y pertenencia company/branch. No se reconstruye autorización en JS. QA HTTP de usuario sin permiso no repetido en esta fase; autoridad inspeccionada, no afirmar un nuevo PASS 403 de esa sesión.
Sin type, backend usa remitente (GET 200). `type=transportista` devuelve 404, verificado. No se agregó selector de tipos.

## Scope

**TENANT + COMPANY + BRANCH** para guía y ventas. Se conservan gre_tenant/gre_company/gre_branch hidden; Store contrasta con sesión, sin aceptar esos campos como autoridad.
Excepciones reales: clientes pertenecen al **tenant**, no se promete aislamiento company de este catálogo. Productos usan PosCatalogReadService y elegibilidad company; ubigeo/catálogos normativos son compartidos según backend. No se inventó scope en frontend.

## Source Modes

`SALE` y `MANUAL` intactos. Origen visible arriba; detalle de líneas en sección Productos.
Venta muestra selector y botón de carga explícita, no descarga todas las ventas en Blade. Manual muestra tabla y Agregar item. Cambiar modo oculta el bloque alterno y limpia la vista previa de venta, como antes.
Los valores de sale_id y las líneas manuales se conservan al alternar, igual que el contrato anterior; no se duplican líneas automáticamente ni se copian clientes entre modos. Backend decide qué origen consumir. No se introdujo una limpieza arbitraria de payload. Cambiar venta ahora limpia la vista previa anterior; una respuesta tardía no se dibuja si ya cambió sale_id o modo. No altera el ID enviado.

## Sales Lookup

Select2, parámetros q/page, respuesta results/pagination y URL reales conservados. GET EDESCE (T1/C1/B1): primera página IDs 122,120,119,118,117,116,115,110,109,108. Gamarra (T3/C3/B4): lista vacía válida. Búsqueda sin coincidencias: 200/results vacío, distinguido de error.

## Sale Items / Snapshots

EDESCE venta122: 200/1 línea. Venta114 de sucursal8:404 desde sucursal1. ID99999999:404. Gamarra recibe404 para122 y114. Endpoint por referencia para venta118: EDESCE200, Gamarra404.
Backend ShippingGuideSaleLine prioriza description/presentation_label_snapshot, packs y units_per_pack; mantiene sus fallbacks backend existentes. JS presenta descripcion/cantidad/unidad_medida recibidas; no consulta Material para recomponer la venta. Escape de texto en preview evita interpretar descripciones como HTML; no cambia el snapshot.

## Customer Payload

Selector backend tenant-scoped; endpoint devuelve id/business_name/doc_number/address/email. Se mantiene fillCustomerFields existente, incluida inferencia legacy de tipo documental por longitud: no se añadió catálogo ni regla nueva. Documento, email y dirección opcionales conservan contratos. No hay autoselección de cliente al elegir venta, tampoco se añadió.
HTTP cliente1: EDESCE200/Gamarra404; cliente6: EDESCE404/Gamarra200; ID inexistente404 ambos. Modal auxiliar y customer.store siguen conectados como antes, pero no se ejecutó ninguna creación ni consulta externa.

## Product Lookup

`shipping_guides.products`, q/page, PosCatalogReadService. EDESCE primera página: 8,95,98,101,96,99,102,97,100,103; Gamarra:106,105. `page=0`:422 ambos. product_id sigue siendo StockItem ID, material_id separado; no sustituido por Material global. Codigo/descripcion copiados sólo desde resultado manual existente. Unidad manual NIU permanece; no se agregó soporte de presentaciones no disponible en esta UI.

## Shipping Catalogs

TransportType activo 01/02; TransferReason activo; WeightUnit KGM/TNE activo; SunatShippingIndicator activo; IdentityDocumentType activo, subcolecciones destinatario6/1/4/7 y transportista6. Todos iterados desde Blade/backend. Sin catálogos JS nuevos. Se mantienen Select2 para ventas, cliente, producto y ubigeo; inicialización única.

## Series / Numbering

Serie y número readonly. EDESCE obtuvo TTT1 / referencial1; Gamarra sin configuración obtuvo valores vacíos, advertencia y botón deshabilitado por la condición previa nextNum===null. No se modificó configuración ni se generó numeración JS. GreRemitterNumbering/nextGreNumber/Store continúan siendo autoridad. El aviso indica expresamente que el número visible es referencial.

## Idempotency Key

UUID generado por Blade al renderizar, hidden dentro de frmGuide. Se conserva entre cambios de campos/modo y reintentos en la misma página; serialize() incluye emission_key y CSRF. Una nueva carga del formulario genera otro UUID, tal como HEAD. Ningún handler visual regenera la key.
Se preserva guideSubmissionLocked, confirmación, POST, replay, persisted, 409, redirecciones y estados inciertos. Una respuesta exitosa/redirección sale del formulario; nueva operación requiere formulario nuevo. No se alteró fingerprint/backend.

## Addresses

Partida y llegada en secciones separadas, cada una con departamento/provincia/distrito y dirección. IDs y hidden ubigeo intactos. Dependencias Select2 existentes limpian provincia/distrito y hidden al cambiar padre; no geocodificación ni API externa.

## Transfer / Transport

Fecha emisión en origen; inicio traslado, modalidad, motivo, peso, unidad, bultos e indicador en Traslado. Placa principal en Transporte (requerida por backend para ambas modalidades).
`01` muestra cardPublicTransport (documento/RUC/nombre); `02` muestra cardPrivateDriver (tipo/documento/nombres/apellidos/licencia). El collapse exterior de sección es independiente: los padres condicionales siguen gobernados por el mismo toggle de modalidad. Valores ocultos se conservan como antes; no se borran ni cambian validaciones condicionales. No hay campos MTC/vehículos secundarios nuevos.

## Products

Tabla de vista previa de venta y editor manual en tabla responsive con scroll local. Columnas reales venta: línea, descripción, cantidad, UM. Manual: línea, producto, cantidad, UM, detalle adicional y quitar. Cantidades/UM/acciones centradas; descripción izquierda. Sin precios inventados ni cards nuevas. Eliminar línea es únicamente edición DOM local, reindexado existente conservado; no es delete persistente.

## Validation / Empty States

Indicadores next-required del patrón Finance; opcionales no endurecidos. Serie readonly sin required decorativo. Laravel422 sigue autoritativo; mismo toastr, ahora también resumen enfocable y mensajes junto a campos para keys escalares/anidadas. Keys sin input visible quedan en resumen. No se ejecutó Store para provocar422.
Lookups: 403 permiso;401/419 sesión;404 no disponible;422 filtros; otros fallo de consulta, sin presentarlos como vacío. Select2 mantiene failure callback y mensaje propio de error; results vacío tiene mensaje separado.
Avisos para cliente inexistente en catálogo, falta numeración, venta sin líneas y tabla manual sin líneas. Son estados, no opciones ficticias. El preview se identifica como local, nunca como aceptación SUNAT.

## Visual Parity

Comparación estructural contra material.create (incluido baseline venti-next), invoice.create_invoice, shipping_guides.index/show:
- REUSE DIRECTLY: next-page-heading, eyebrow/description, next-page-toolbar, next-toolbar-actions, next-form-section, next-section-header/kicker, next-required, next-field-action/button, next-aux-modal, tabla Bootstrap y table-responsive.
- VALID REAL CONTRACT DIFFERENCE: dos modos de origen, campos condicionales de traslado y numeración de autoridad backend; tabla manual y preview separados por contrato.
- Se retiraron acordeón, card anidadas redundantes y cinco reglas locales de estilo. **0 CSS nuevo**, sin archivos CSS cambiados.
- Smoke Chrome confirma presentación de secciones iniciales y alineación cliente/botón tras corrección. VISUAL PARITY estructural; no se certifica paridad visual completa sin matriz restante.

Secciones finales: 01 Comprobante y origen;02 Destinatario;03 Datos del traslado;04 Punto de partida;05 Punto de llegada;06 Transporte;07 Productos;08 Observaciones. Headers ahora colapsables por la corrección UX solicitada: navegación libre, sin wizard ni exclusión entre secciones.

## Allowlist

Única incorporación: route `shipping_guides.create`, vista `shipping_guides.create`. No se agregan Store, JSON, documentos, consulta ni recuperación. Venti Next y Legacy cargan200 en ambos contextos.

## Cross-context

La lectura MySQL en transacción READ ONLY localizó ventas elegibles:92 en T1/C1/B1 y1 en T1/C1/B8. No hay ventas elegibles C2/Gamarra para probar carga propia allí; no se fabricaron.
- HTTP branch ajena114:404; tenant ajeno122/118 desde Gamarra:404.
- Clientes cruzados1/6:404; inexistentes:404.
- Matriz company y contexto ausente cubierta por suite de lectura aislada, no atribuirla a HTTP Create en C2.
- No se cambió contexto mediante escrituras ni se probaron documentos.

## Responsive / Pending manual visual QA

**RESPONSIVE VISUAL QA — PENDING MANUAL BROWSER REVIEW**.
320/375/390/576/768/992/1200/1440: grid auditado estáticamente; campos se apilan antes de md, filas dentro de wrappers acolchados, acciones con wrap, Select2 ancho fluido, tablas con scroll local. No se certifica overflow global mediante revisión estática.
Chrome sí disponible: smoke desktop inicial con Owner EDESCE y ajuste de campo auxiliar verificados visualmente; Firefox no controlado. Pendiente matriz exacta, parte inferior completa, transporte público/privado, modo manual, dropdowns, teclado, consola y responsive de modal auxiliar. No se crearon screenshots sintéticos.
**VISUAL QA — PENDING MANUAL REVIEW** para cierre completo.

## Store Boundary / Pending mutation QA

**STORE NOT EXECUTED — HTTP/AUTH/CSRF QA PENDING**. Botón real y confirmación conservados, sin deshabilitación permanente artificial. No Store, alta de cliente, cambios de series/clientes/productos o stock. Sólo POST de autenticación de QA; GET de negocio.

## Documents Boundary / Pending operational QA

Nubefact real NOT EXECUTED. PDF/XML/CDR NOT EXECUTED; sin acciones de documentos añadidas a Create. No consulta/recovery/export. GUIDE-02 FIXED / MYSQL CONCURRENCY QA PENDING: no se resuelve desde frontend. Failure contract conserva evidencia aislada previa; no se reejecuta emisión.

## Backend Sync Notes

[RESOLVED] GUIDE-01: autoridad create_referralGuide/contexto entregada por DEV y preservada; auditoría focalizada previa vigente.
[OPEN] GUIDE-02: prueba reproducible MySQL de concurrencia pendiente. Frontend puede continuar: Sí.
[OPEN] Store HTTP/auth/CSRF y mutaciones auxiliares: QA pendiente; no implica defecto demostrado.
[OPEN] Operación Nubefact/documentos: pendiente, fuera de esta fase.
No se reabre ReferralGuide legacy ni se modifican Shipping List/Show.

## Files modified

- resources/views/shipping_guides/create.blade.php — composición, accesibilidad, required y estados; sin alteración de campos.
- public/js/shipping_guides/create.js — quitar collapse; mensajes de lookup/empty; errores422 inline; render seguro/alineado; prevenir preview de venta anterior; hints de líneas. No cambios a rutas/payload/reglas de negocio/emission lifecycle.
- config/venti-next.php — única ruta de vista autorizada visualmente.
- docs/REAL-NEXT-SHIPPING-GUIDE-CREATE-REMITENTE.md — esta evidencia y límites.

**BACKEND MODIFIED = NO**. **CSS NUEVO = 0**. **FRONTEND IMPLEMENTED — MANUAL VISUAL QA PENDING**.


## UX correction — Progressive disclosure (2026-11-22)

Esta corrección sustituye el requisito inicial de mostrar todas las secciones abiertas. Se conserva el estilo aprobado y las ocho secciones; únicamente cambia su visibilidad progresiva.

- Estado inicial: 01 OPEN;02–08 COLLAPSED, incluidas Observaciones. Bootstrap4 Collapse (`collapse show` sólo en01), sin data-parent: cualquier combinación de secciones puede permanecer abierta.
- Header entero mediante button type=button, aria-controls/aria-expanded y teclado Enter/Space nativo. Número y título conservados; chevrons Font Awesome existentes actualizados por show/hide. Clases Bootstrap/Venti Next existentes, 0 CSS nuevo. Sin wizard, tabs, resúmenes inventados o localStorage.
- Cuerpo persistente en DOM; no disabled, no destrucción de líneas, no cambios de names/IDs funcionales/serialización. Botón final de emisión permanece fuera de los cuerpos colapsables y conectado, no ejecutado.
- Venta/Manual no expanden secciones; toggles y limpieza de preview previos intactos. Select2 no se destruye ni recrea al colapsar; no se requiere refresh adicional en las pruebas. Transporte conserva padres condicionales internos, independientes del collapse exterior.
- renderGuideValidation abre paneles de los inputs asociados, incluidos hidden (por ejemplo partida_ubigeo). Errores agregados items / items.* y driver_primary / driver_primary.* abren Productos/Transporte aunque no haya input exacto. Resumen de errores continúa visible fuera de paneles; errores visibles conservan feedback junto al campo. Errores de lookup no cierran paneles.
- Captura de evento nativo invalid abre la sección del campo sin agregar reglas. Secciones con varios errores se abren conjuntamente. Ningún cambio a endpoint, payload, Store, fingerprint, snapshots ni reglas backend.

### Isolated browser behavior verification

Chrome headless con perfil temporal independiente, sin ventanas, mouse, teclado ni control del navegador local. HTML renderizado por Laravel en transacción MySQL READ ONLY, Owner EDESCE T1/C1/B1; jQuery, Bootstrap4, Select2 y create.js reales. Harness temporal expone únicamente renderGuideValidation para simular feedback de errores SIN POST. AJAX bloqueado deliberadamente. Esta prueba de DOM/interacciones no equivale a QA HTTP de Store ni a captura visual certificada.

12 checks PASS:
1. 01 abierta y02–08 cerradas.
2. Ocho controles independientes y sin data-parent.
3. Abrir02 mantiene01 abierta y aria-expanded correcto.
4. Payload serializado idéntico antes/después de collapse.
5. Línea manual/cantidad y misma instancia Select2 conservadas, sin contenedores duplicados.
6. Alternar Venta/Manual no abre todos los paneles ni duplica líneas.
7. Errores de destinatario, ubigeo hidden, conductor y cantidad abren02/04/06/07.
8. Feedback junto a campos y resumen visibles.
9. Transporte privado/público conserva visibilidad condicional independiente.
10. Error agregado items abre07.
11. emission_key sin cambios.
12. Cero llamadas de negocio; Store/Nubefact/documentos no ejecutados.

Blade compile PHP7.3.33, node --check y git diff --check PASS.
Responsive 320/375/390/576/768/992/1200/1440: grid original conservado; no evidencia visual nueva de la matriz completa. **RESPONSIVE VISUAL QA — PENDING MANUAL BROWSER REVIEW**. No se declara PASS visual a partir del harness DOM.

Delta de esta corrección: resources/views/shipping_guides/create.blade.php; public/js/shipping_guides/create.js; este documento. Allowlist previamente integrada sin nuevos cambios. Backend unchanged; 0 CSS; no commit.
