# REAL NEXT — R2 — CASH MOVEMENTS ADMIN FUNCTIONAL PARITY RECOVERY

2026-11-24. Implementación frontend; backend y CSS sin cambios. Sin commit ni operaciones financieras reales. Este documento sustituye las exclusiones funcionales de la fase read-only; no cambia sus límites de certificación operacional.

## Inventario y evidencia Git

URL: [Movimientos administrativos](http://127.0.0.1:8080/dashboard/cash-movements/admin).

`cashMovement.admin.index` → `CashBoxController@adminIndex` → `cashBox.admin_index`. `744d28b^` despacha a `_admin_legacy`; `744d28b` cambia esa rama a `admin_readonly`, añade su renderer y elimina en runtime apertura/sesión, regularización y documentos. `_admin_legacy` y `admin_index.js` aún conservan el flujo operativo. El dispatcher de Financial Summary continúa separado e intacto.

Unidad de conteo: 8 capacidades originales: consulta, búsqueda, filtros, paginación, apertura, navegación a sesión propia, regularización y acceso documental (venta/factura son variantes de esta última). Antes de R2: 4/8 originales más el enlace Next Mis movimientos = 5 disponibles. R2 recupera4, conserva las5 y deja9 disponibles. No se cuentan iconos, campos ni botones de confirmar como capacidades independientes.

La auditoría global registró3 pérdidas; la cuarta es **navegar a sesión propia** por caja abierta (`data-cashbox-btn`, rama isOpen). FR-079 amplía el inventario; no se inventa acceso nuevo. No había accesos directos a mantenimiento de Cajas/Subtipos en esta pantalla: no se agregan. Tampoco existían aquí ingreso/egreso/arquéo/cierre; pertenecen a la sesión, no son botones retirados de Admin.

## Matriz antes/después

| Funcionalidad | Legacy original | Next antes R2 | Permiso / condición | Endpoint | Handler actual | Blocker actual | Recuperación | QA |
|---|---|---|---|---|---|---|---|---|
| Consulta tabla | Sí | Sí | listCashMovementAdmin_cashBox; T/C/B | GET cash-movements/admin/list | admin-readonly load/render | Ninguno nuevo | ALREADY AVAILABLE | Real read PASS |
| Búsqueda | Sí | Sí | Mismo scope; q | Mismo GET | admin-filters submit | Ninguno nuevo | ALREADY AVAILABLE | Read/replay |
| Filtros | Sí | Sí | user_id, cash_box_id, type, subtype_id, date_from/date_to | Mismo GET | change → load1 | Ninguno nuevo | ALREADY AVAILABLE | Read contextual PASS |
| Paginación | Sí | Sí | Mismos filtros, page/per_page | Mismo GET | pageButton/load | Ninguno nuevo | ALREADY AVAILABLE | 10/10/1 real |
| Abrir caja propia | Sí | No | Auth/contexto activo; caja activa company/general o branch actual; actor Auth::id | POST cash-registers/open | data-cashbox-btn → confirmación | Sin blocker nuevo demostrado; CASH-02 no cerrado | FRONTEND RECOVERY REQUIRED → IMPLEMENTED | MUTATION QA PENDING; mock200/422 PASS |
| Ir a mi sesión abierta | Sí | No | SELF + caja contextual; openCashBoxIds del actor | GET cash-registers/session/{cashBox} | enlace contextual | Ninguno nuevo | FRONTEND RECOVERY REQUIRED → IMPLEMENTED | URL/render PASS; destino interceptado |
| Regularizar | Sí | No | regularize_caja; no cancelado, regularize0, sale/income; servicio valida resto | POST cash-registers/regularize | data-regularizar → formRegularize | CASH-02/03/04 condicionan casos históricos, no toda la capacidad | FRONTEND RECOVERY REQUIRED → IMPLEMENTED | MUTATION QA PENDING; mock200/422 PASS |
| Comprobante venta / factura compra | Sí | No | print_puntoVenta / list_invoice; referencia presente; guard del destino | GET imprimir/documento/venta/{sale_id} o factura/compra/ver/{entry_id} | data-print_nota → enlace | DOC-01 físico no certificado; sin fallback público | FRONTEND RECOVERY REQUIRED → IMPLEMENTED por ruta autorizada | URL/permiso PASS; documentos NO abiertos |
| Mis movimientos | No en baseline toolbar | Sí | listCashMovementMy_cashBox | GET cash-movements/my | enlace @can existente | Ninguno nuevo | ALREADY AVAILABLE, conservado | Inspección URL |

Todos los endpoints anteriores tienen prefijo `/dashboard/`. No APPROVED RETIREMENT ni capacidad eliminada. No se asume que permiso de listado admin conceda regularización/impresión. Se usa `Auth::user()->can` efectivo para controles, sin modificar permisos ni apoyarse en nombres de rol. El array legacy getPermissionsViaRoles no reemplaza autorización efectiva.

## Apertura SELF

HEAD no define permiso granular en `cashRegister.open`/`session`; ambos conservan el contrato autenticado de tenant.context y contexto activo. No inventamos `open_caja` ni asumimos que listCashMovementAdmin equivale a permiso mutador universal. El POST fija user_id=Auth::id, tenant/company/branch desde contexto, nunca desde target user del navegador.

AdminIndex entrega cajas activas de company con branch NULL o branch actual. Sus botones representan únicamente sesiones del usuario actual. Si hay sesión abierta, enlace directo; si no, confirmación con nombre escapado y campos originales. No se abre automáticamente al cargar ni al navegar.

Payload: `_token`, cash_box_id, opening_balance, opening_origin, opening_provenance, opening_observation. Efectivo admite saldo; bank/other envía0. Backend valida decimal hasta7 enteros/2 decimales, origen consolidated/external, procedencia150 y observación500. Bloquea caja ajena/inactiva; lock de caja y búsqueda SELF evitan duplicar sesión existente (respuesta200 reutiliza sesión). La transacción llama CashOpeningFund: consolidated descuenta saldo rendido suficiente; external requiere procedencia; registra cash_opening_funds. Cero saldo no necesita fondo. No se omite ese servicio basándose en comentarios legacy anteriores.

Moneda no viaja como elección frontend. No se añade un símbolo inferido: no cambia semántica financiera. CASH-02 (cambio de moneda de caja ya usada) permanece abierto. El POST de apertura no tiene snapshot de contexto comparable a regularización; resuelve contexto al enviar, y rechaza caja ajena. No se inventa un contrato snapshot inexistente en esta recuperación.

## Regularización

Reutilización directa de `cashRegister._regularization` y `cashRegister/regularization.js` aprobados, sin editarlos ni duplicar listeners. Sólo se cargan una vez; `cash:regularized` refresca página actual conservando filtros. No se carga el antiguo admin_index.js ni un segundo paginador.

Payload: CSRF, cash_movement_id, amount_regularize, observation y regularization_context_tenant/company/branch renderizados. El formulario conserva decimal positivo y observación1000. Confirmación explícita mediante botón Regularizar del modal; no envío al abrir.

`CashRegularizationService` exige usuario enabled no PlatformAdmin, permiso, memberships activas tenant/company/branch y snapshot exacto. Valida movimiento contextual con actor/caja/sesión/subtipo identificables; sale/income pendiente, no cancelado, no regularizado, sin vínculos incompatibles. Caja bank activa con subtipos, subtipo activo diferido aplicable. No se replica esta lógica completa en JavaScript: se preserva condición visual legacy y se muestran errores del backend.

Con sesión original abierta: única sesión del usuario original. Con sesión cerrada: única sesión abierta SELF de la misma caja/local; crea abono en ésta y conserva referencia original, sin alterar cierre histórico. Neto<=bruto; sin comisión neto=bruto; usa DECIMAL/bcmath. Venta vinculada debe coincidir en contexto/moneda. El resultado incluye message, balance_total, sesión/IDs, neto/comisión/currency; Admin refresca listado, no inventa agregado de saldos.

Dependencias por caso: CASH-02 moneda histórica; CASH-03 subtipo cambiado de diferido; CASH-04 canal desactivado con pendiente. Servicio rechaza incompatibles con422; la recuperación no cambia guard ni resuelve política histórica. No hay defecto nuevo reproducido que justifique retirar toda la operación. QA financiero real continúa pendiente.

## Documentos / ownership / storage

- Si sale_id existe, se conserva prioridad original del comprobante de venta, bajo print_puntoVenta. Ruta `puntoVenta.print` tiene SaleListGuard print, resolución SaleListAccess contextual/alcance y PDF generado por printDocumentSale. No sustituir por public/comprobantes ni url de proveedor. No se abrió ni generó PDF durante QA.
- Si no hay venta y existe entry_id, enlace `invoice.show`, permiso list_invoice y `financeEntries()->findOrFail` con FinanceInvoiceContext. Es vista de factura, no descarga arbitraria del archivo. No se modifican sus consumidores. Referencia que no sea financiera/visible falla en destino; no se consulta globalmente para simular autorización.
- Permiso visual adicional corresponde al guard vigente, no a la exclusión read-only anterior. IDs recibidos se codifican en ruta; no se toman URLs libres de la respuesta.
- DOC-01 permanece OPEN para infraestructura/almacenamiento público transversal. R2 no certifica acceso físico a archivos ni restaura enlaces públicos. No blocker individual nuevo identificado para los enlaces autorizados recuperados.

## Dataset actual (lectura 2026-11-24)

PDO START TRANSACTION READ ONLY, finally ROLLBACK; Laravel y middleware de contexto/permiso reales. Auth QA sólo en memoria; cache/session array; ninguna cuenta modificada. No login POST, ni prueba de ejecución financiera por HTTP.

| Contexto | Total | IDs completos | Cajas disponibles / sesiones propias abiertas |
|---|---:|---|---|
| EDESCE T1/C1/B1 | 21 | 271,270,269,268,267,266,265,264,263,262,261,260,259,258,257,256,255,253,252,251,250 | 1 efectivo,2 bancaria,5 bancaria; abiertas1/2/5 |
| EDESCE T1/C1/B8 | 2 | 272,254 | 2,5 bancarias y6 efectivo; ninguna abierta |
| EDESCE T1/C2/B2 | 0 | [] | [] |
| Gamarra T3/C3/B4 | 0 | [] | [] |

243 movimientos sin company o branch siguen excluidos. No remediación ni asignaciones frontend. Usuarios de filtro: [4] en B1/B8 y [] en C2/Gamarra. Query con tenant/company/branch99 conserva IDs autorizados. Página999 y búsqueda imposible vacías; primera/segunda/tercera página B1=10/10/1. Cajas actuales PEN; DTO de movimientos sigue sin moneda, igual a legacy. No conversiones, símbolos ni totales nuevos.

## Cobertura anterior / estado independiente de QA

| Capacidad | Cobertura anterior | R2 | Pendiente |
|---|---|---|---|
| Listado/filtros/paginación | Real read + replay en informe Admin | Dataset renovado; guards preservados | Sin cierre mutador implícito |
| Apertura | Legacy inspeccionable; excluida en homologación Admin | Contrato HEAD/CashOpeningFund auditado estáticamente; payload200/422 simulado | Ejecución real/doble POST/concurrencia financiera |
| Sesión propia | Excluida y no contada como pérdida independiente | Alcanzabilidad histórica y SELF documentados, link recuperado | No repetir QA operaciones de Own Cash Session |
| Regularización | Servicio existente auditado en Cash; Admin la excluyó | Lectura estática guard/servicio y wiring contextual200/422 | Mutación operacional Admin y escenarios históricos |
| Documentos | DOC-01 y límites previos; sin ejecución | Rutas/guards actuales y permisos inspeccionados | Documento real e infraestructura |

No afirmar que el informe DEV certifica todas las operaciones recuperadas. Nuevas capacidades antes no contabilizadas: sesión propia. Nuevos findings backend: ninguno reproducido en R2; no cerrar CASH-02/03/04 ni DOC-01.

## Visual y QA focalizado

Next mantiene toolbar, filtros, tabla, paginación. Acciones en un menú … con Font Awesome y next-row-action-item-icon: comprobante fa-print, factura fa-file-invoice, regularización fa-check-double. Apertura fa-cash-register; sesión fa-door-open. Sin Column Visibility nuevo. Tabla pasa de11 a12 columnas por recuperación de acciones; empty colspan12. El wrapper de cajas usa flex-wrap/mw-100 existentes para no desbordar768px. 0 CSS nuevo.

Chrome headless aislado, HTML Laravel real y JSON read-only reproducidos mediante interceptor. No controla escritorio/navegador personal. 320/375/390/576/768/992/1200/1440: tabla con scroll local, cero overflow global, iconos y menús visibles. Capturas inspeccionadas. Matriz de permisos vacíos/permitidos, cancelado/regularizado/egreso y referencias documentales PASS. Cuatro POST estrictamente simulados: regularización200/422 y apertura422/200; contextos, CSRF y payload verificados; éxito redirige a sesión interceptada. Sin solicitudes duplicadas; sin JS errors. No documentos abiertos ni descargas, cero mutaciones reales.

Evidencia local en `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`: `r2-read.php`, `cash-admin-data.json`, HTML cash-admin-b1/b8/c2/gamarra, `r2-browser.cjs`, `r2-browser-results.json`.

- [Menú desktop](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r2-menu-1440.png)
- [Menú móvil](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r2-menu-375.png)
- [Apertura simulada](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r2-open-simulated.png)

Revisión adicional focalizada: formularios de apertura (caja6 del contexto B8, sin enviar) y regularización caben en los ocho anchos. [Apertura móvil](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r2-opening-375.png) y [regularización móvil](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r2-regularize-375.png). Sin POST adicional; escenarios sólo de presentación.

## Archivos y estado final

- resources/views/cashBox/admin_readonly.blade.php (nombre histórico, producto operativo).
- public/js/cashBox/admin-readonly.js (renderer conservado).
- public/js/cashBox/admin-operations.js (nuevo adaptador; no backend).
- Este documento y backlog canónico.

Sintaxis JS y Blade compile PASS; git diff --check PASS. Backend/routes/permissions/servicios/CSS/otros módulos: UNCHANGED. No commit. R3 no iniciado.

**FUNCTIONAL PARITY: HISTORICAL CAPABILITIES RESTORED — OPERATIONAL QA PENDING.** 8 originales;5 disponibles antes R2 (4 originales+Mis movimientos),4 recuperadas,0 originales pendientes de implementación;1 pérdida adicional descubierta. No retiro aprobado. Apertura/regularización: recuperadas con MUTATION QA PENDING. Documentos: navegación protegida recuperada, ejecución/infra pendiente. Las decisiones CASH históricas no se resuelven ni bloquean universalmente la interfaz.
