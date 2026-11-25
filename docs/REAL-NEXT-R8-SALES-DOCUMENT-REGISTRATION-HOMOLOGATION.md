# REAL NEXT — R8.2C — Sales Document Registration Homologation

2026-11-25. Rama `feature/venti-next-frontend`; baseline `6df57e7`; PHP7.3.33; infraestructura actual conservada. **Una superficie, tres variantes.** Reutilizada evidencia R8-A08/S281, sin repetir auditoría semántica ni certificación backend DEV.

## Alcance y contratos

- GET `show.register.comprobante`: `/dashboard/registrar/comprobante/venta/{type}`; `QuoteSaleController::showRegistrarComprobante`, vista `quoteSale.registrarComprobante`.
- `edit_quoteSale`, `QuoteFormReadAccess::conversion`, QuoteSaleBranchContext, RetiredPromotionInput y contexto autenticado existentes intactos. Allowlist añade exclusivamente `show.register.comprobante`.
- Controller admite Ticket/Boleta/Factura y entrega cajas/subtipos elegibles de tenant/company/local, moneda e IGV configurados y permiso de pagos parciales. No nuevos catálogos ni campos de contexto inventados.
- Búsqueda GET `/dashboard/quotes/buscar`; carga GET `/dashboard/get/data/quotes/sale/{id}`. `btn-submit` inicia disabled y sólo se habilita tras cargar una cotización; restricciones readonly/disabled conservadas.
- POST real del handler `/dashboard/store/sale/from/quote`; el `data-url` histórico del form no gobierna este envío y no se cambió. CSRF se conserva mediante meta-header; quote_id y demás identificadores permanecen intactos.
- `public/js/quoteSale/registrarComprobante.js` y los scripts inline de inicialización **sin cambios**. No cálculo tributario nuevo, no adaptación de fórmulas ni reglas de pago.

## Matriz por variante

| Variante | Controles preservados antes/después* | Diferencias funcionales intactas | Resultado focal |
|---|---:|---|---|
| Ticket | 53/53 | Sin identificación fiscal obligatoria; parcial sujeto a configuración; type_document null | PASS |
| Boleta | 57/57 | DNI8, nombre readonly tras consulta, email; parcial deshabilitado por tipo; type_document03 / tipo cliente1 | PASS |
| Factura | 58/58 | RUC11, razón social/dirección readonly tras consulta, email; parcial deshabilitado por tipo; type_document01 / tipo cliente6 | PASS |

*Comparación de inputs/selects/textareas/buttons del formulario y templates originales mediante DOMParser, excluyendo únicamente class/style visuales. IDs, names, tipos, hooks, atributos, readonly, límites y valores iguales. No son nuevas capacidades ni registros DB. Modales, confirmaciones, retorno por breadcrumb y colapsadores existentes preservados.

## Homologación visual

Referencias: QuoteSale Create/Edit aprobados y Component Patterns. Encabezado Next identifica la variante; tarjetas neutrales, Surface Spacing mediante `next-form-section`; primera fila operativa proporcional con moneda/parcial compactos y selección de cotización. Datos de cotización en seis columnas en escritorio y redistribución natural. Clientes y observaciones mantienen sus controles reales, incluido Summernote.

Productos/servicios conservan filas Bootstrap `.row`, porque el handler utiliza `closest('.row')` al serializar. No se sustituyen por un DataTable ni se altera el renderer. Columnas desktop desde xl y etiquetas por campo debajo de xl para una lectura móvil legible. Inputs y valores conservados. Modales usan `next-aux-modal`; resultados de búsqueda con scroll local. Resumen monetario en tabla compacta (sin ancho mínimo de tabla operativa), importes alineados a derecha y permiso showPrices_quote intacto. Guardar mantiene su comportamiento y jerarquía primaria.

CSS nuevo0; no archivo CSS modificado. Se retiró únicamente la regla inline local `.select2-search__field { width:100% !important; }`, redundante para este formulario, tras QA. Se preservan estilos funcionales de ocultación y plugins existentes. No modificaciones en otras vistas.

## Evidencia de lectura y simulación

Render PHP autorizado de las tres variantes con Owner EDESCE tenant1/company1/branch1, PermissionMiddleware edit_quoteSale y conversion activos;3 cajas disponibles. DB en `START TRANSACTION READ ONLY`, ROLLBACK final. No login HTTP nuevo ni escrituras comerciales. Arnés externo `r82c-read.php` reutiliza el bootstrap de lectura anterior; no modifica aplicación.

Navegador: Chrome headless con perfil aislado; todo request interceptado. HTML real renderizado + assets reales. Cotización990001 y producto/servicio990002/990003 son **fixtures SIMULATED**, nunca creados en DB. Consulta documental de cliente también simulada: no acceso externo a Decolecta, Nubefact ni SUNAT.

## QA focalizada

| Prueba | Resultado |
|---|---|
| Render inicial / allowlist / CSS Next | PASS en3 variantes |
| Guardar inicial disabled → carga mediante modal → enabled | PASS en3 variantes |
| Búsqueda/selección/precarga | PASS; código, fechas, cliente/contacto, observaciones, producto, servicio, descuentos y totales simulados |
| Importes | Precio118/cantidad1/importe118 y resumen100+18=118 proporcionados por fixture; sin nueva fórmula |
| Cliente Boleta/Factura | Consulta interceptada rellena campos readonly mediante handler original |
| Payload | quote_id, type_document, tipo cliente, fecha, caja/subtipo, pagos parciales, detalles y CSRF conservados |
| Rechazo422 simulado |3 POST interceptados; mensaje contractual, confirmación re-habilitada, cotización conservada |
| Responsive |30 combinaciones:3 variantes ×320/375/390/576/768/992/1200/1440/1920/2560; sin overflow global |
| Visual | Densidad/grupos desktop y labels mobile revisados; resumen sin scroll horizontal forzado; capturas de viewport para evitar transiciones de shell durante full-page capture |
| JS runtime |0 pageerrors; console.error del rechazo422 es el handler existente, no un fallo inesperado |
| Blade compile PHP7.3.33 | PASS |
| PHP lint allowlist | PASS |
| git diff --check | PASS |

No se prueba persistencia/emisión ni se presenta el POST interceptado como éxito de negocio. Contexto/ownership backend se preservan, no se recertifican. No exportaciones, ventas, cobros, movimientos de caja, documentos tributarios ni cambios de cotizaciones/clientes/inventario.

Evidencia externa en `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`: `r82c-browser.cjs`, `r82c-read.php`, `r82c-evidence.json`, renders before/after y capturas `r82c-{variante}-{ancho}.png` / `r82c-summary-{variante}-{ancho}.png`.

## Resultado y continuidad

**SURFACES HOMOLOGATED1/1; VARIANTS3/3; FUNCTIONAL / VISUAL PARITY PASS.** Recuperadas/perdidas nuevas0/0. **76 recuperadas +11 bloqueadas +23 excluidas =110**, sin cambios. B histórico2: Recepción y Registro de comprobantes homologados;0 homologaciones pendientes de esa cohorte. C, Branch/GRE, FR110 y S201/S203 no intervenidos.

Backend0 / Blade1 / JS0 / CSS nuevo0 / allowlist1 ruta / infraestructura0 / findings DEV nuevos0 / llamadas externas0 / mutaciones comerciales0. Documentación: este informe, inventario y plan; ledger externo actualizado sin renumeración. Sin commit/push/merge. **R8.2C COMPLETE; R8.2D no iniciada**, esperar revisión del propietario.

## URLs para revisión manual

- http://127.0.0.1:8080/dashboard/registrar/comprobante/venta/Ticket
- http://127.0.0.1:8080/dashboard/registrar/comprobante/venta/Boleta
- http://127.0.0.1:8080/dashboard/registrar/comprobante/venta/Factura
