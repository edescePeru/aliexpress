# REAL NEXT — CASH MOVEMENTS ADMIN — READ-ONLY FRONTEND

2026-11-22. **FRONTEND INTEGRATED — READ PATH**. Backend unchanged; 0 CSS nuevo; sin commit ni mutaciones.

## Route / Permission

[Abrir pantalla](http://127.0.0.1:8080/dashboard/cash-movements/admin).

- GET `cashMovement.admin.index` → `CashBoxController@adminIndex` → `cashBox.admin_index` → nuevo `cashBox.admin_readonly`.
- GET `/dashboard/cash-movements/admin/list`, `cashMovement.admin.list` → `adminList` → `buildListResponse(request, null, true)`.
- Ambas acciones conservan `listCashMovementAdmin_cashBox`, auth, check.user.enabled, password.changed y tenant.context. No rol nominal Owner ni bypass nuevo.
- Allowlist: únicamente `cashMovement.admin.index`. Render real carga `venti.min.css` y `venti-next.css`. No endpoints de datos/mutación/documentos añadidos.
- Se mantiene la rama `readOnly` del dispatcher para `cashBox.summary_movements` (Financial Summary). No cambiar esa superficie ni `_admin_legacy`/su JS; la nueva fachada no carga handlers mutadores.

## Scope

**TENANT + COMPANY + BRANCH**. Inicialización real valida memberships/contexto. Query usa valores de sesión, no parámetros tenant/company/branch recibidos. Admin no fuerza user_id propio; permite consultar otros usuarios dentro del contexto activo.

## Admin vs My Movements

Título: **Movimientos administrativos**. Descripción: movimientos de usuarios de empresa/local activos; toolbar de consulta administrativa. Enlace **Mis movimientos** sólo con `listCashMovementMy_cashBox`, navegación a superficie integrada. No sustituye Admin por My ni presenta listado global de todos los tenants.

## Dataset

Lectura MySQL real dentro de `START TRANSACTION READ ONLY`, finally ROLLBACK, session/cache en memoria y usuarios QA existentes. Sin login POST ni cambios de DB. HTML completo renderizado por Laravel con contexto y route reales; respuestas guardadas fuera del repo para replay aislado.

| Contexto | Total contextual | IDs completos | Páginas de10 |
|---|---:|---|---|
| Owner EDESCE T1/C1/B1 | 21 | 271,270,269,268,267,266,265,264,263,262,261,260,259,258,257,256,255,253,252,251,250 | 10 / 10 / 1 |
| Owner EDESCE T1/C1/B8 | 2 | 272,254 | 2 |
| Owner EDESCE T1/C2/B2 | 0 | [] | 0 |
| Owner Gamarra T3/C3/B4 | 0 | [] | 0 |

No fixtures en DB. Consultados también page999, búsqueda imposible, tipo income y usuario4/page2. Página fuera de rango devuelve colección vacía con metadata backend; no se cambió esa semántica.

## Columns

11 columnas: ID, fecha, usuario, caja, tipo, subtipo, estado, monto, abonado, comisión, descripción. Descripción/observación se combinan con ` | ` como contrato previo, usando textContent para no interpretar HTML.

ID/fecha/tipo/estado centrados, importes derecha, usuario/caja/subtipo/descripción izquierda. Texto largo conserva scroll local, con utilidad Bootstrap text-nowrap en fecha/descripción para no inflar cada fila en móvil. Importes se muestran tal como llegan, sin cálculos.

No columna acciones porque no queda acción por fila certificada en este alcance. No menú vacío. No Column Visibility Menu: legacy no tenía columnas configurables.

## Filters

Descripción `q`, usuario `user_id`, caja `cash_box_id`, tipo `type` (sale/income/expense), subtipo `subtype_id` (incluye none), `date_from`, `date_to`. Fechas nativas con formato transportado existente; sin datepicker nuevo ni filtro de moneda/sesión/cross-context inventado.

Buscar/Enter/cambio select o fecha consulta página1. Paginación conserva FormData actual. Se preserva orden backend por ID descendente, page/per_page/metadata. No DataTables en esta pantalla: AJAX propio, no draw lifecycle ni ordenación cliente que sustituir.

JS de lectura dedicado: `admin-readonly.js`, adaptado del patrón Cash `summary-readonly.js`. Sólo GET. Aborta petición anterior y usa secuencia para descartar respuestas fuera de orden. Durante carga limpia datos/paginación previos y marca aria-busy; no presenta un total anterior como actual.

## User Scope

Opciones entregadas por adminIndex: usuarios con movimientos del company/branch actual. QA B1/B8 `[4]`; C2/Gamarra `[]`. Sin User::all, filtro frontend de seguridad ni consultas nuevas. La capacidad administrativa es autoridad; los permisos de acciones mutadoras no se reutilizan para autorizar consulta.

## Actions Boundary

| Acción | Clasificación | Resultado |
|---|---|---|
| Buscar/filtrar/paginar | READ PATH | Integradas |
| Mis movimientos | SAFE NAVIGATION con @can | Enlace a superficie certificada |
| Abrir caja / iniciar sesión | MUTATION / UNCERTIFIED aquí | Excluida de toolbar y scripts |
| Ir directamente a sesión de caja | Otra superficie | No necesario en esta fachada; navegación principal disponible en módulo propio |
| Regularizar | MUTATION / UNCERTIFIED aquí | Sin botón, modal ni regularization.js |
| Imprimir venta/ver factura/documentos | UNCERTIFIED aquí | Sin enlaces ni handlers |
| Editar/eliminar/anular/cerrar sesión | OUT OF SCOPE | No incorporados |

No se alteró ningún endpoint ni permiso. El nuevo parcial no reutiliza indiscriminadamente los botones de `_admin_legacy`. Summary conserva su rama anterior. No tracking nuevo.

## Currency

**adminList no entrega moneda en el DTO por movimiento.** No se inventa columna, símbolo, conversión, moneda de sesión ni snapshot histórico. No inferir moneda a partir de caja actual: CASH-02 sigue siendo decisión independiente. No total agregado PEN+USD. Si se requiere moneda histórica explícita, DEV debe exponer contrato autoritativo; no bloquea la fachada compatible con datos actuales.

## Movement Types

Se usa `type_raw || type`: sale→Venta, income→Ingreso, expense→Egreso. Si existe `regularization_source_id`, etiqueta **Abono por regularización**; nunca se deduce por signo. Tipos desconocidos conservan texto backend y badge neutral. No inventar tipo apertura si backend lo codifica como ingreso.

Badges semánticos existentes: Venta primary, Ingreso success, Egreso danger, abono info. Estados: Cancelado (`deferred_cancelled`), Pendiente (`regularize=0` no cancelado), Regularizado en otra sesión (`balance_effect=false`) y Confirmado. No convertir «Pendiente» en botón Regularizar.

## Historical No-Context Data

**EXCLUDED / DATA REMEDIATION — HISTORICAL MOVEMENTS WITHOUT CONTEXT**.

Reassessment previo de esta misma fecha:241 T1/C1 sin branch y2 T1 sin company/branch (248/249):243 registros fuera del scope. Se preserva exclusión fail-closed. No opción «Mostrar históricos», OR branch NULL, reasignación ni fallback. No es blocker de integración. Fuente detallada: addendum Cash Movements Admin en `REAL-NEXT-DEV-PARALLEL-WORK-2026-11-22.md`.

## Empty States

Vacío real y búsqueda sin coincidencias muestran «No se encontraron movimientos» con metadata backend. Error HTTP NO se transforma en vacío:

- 403: no tienes permiso para consultar estos movimientos.
- 422: filtros no válidos, revisar datos.
- Otros/network: consulta no disponible, reintentar; muestra status cuando existe.

Errores limpian filas y paginación, conservan formulario y no muestran cuenta0 falsa. Usuario puede volver a Buscar. El replay prueba403/422/500 controlados: verifica presentación, no afirma que backend devolviera esos errores para los filtros normales.

## Cross-context

QA actual: contexto inicializado por middleware real, permiso real ejecutado antes de leer; outputs de cuatro contextos arriba. Parámetros tenant99/company99/branch99 mantienen exactamente los IDs del scope; usuario4/page2 queda limitado por contexto; búsqueda imposible retorna0. La prueba previa de middleware real con usuario2/T1 sin permiso confirmó403 para ambas rutas, no se cambiaron permisos para repetirla.

GET página y datos sin Audit ni negocio mutable; transacción READ ONLY protege la ejecución QA. No se certifican mutaciones por este resultado.

## Responsive

Chromium headless **aislado**, sin navegador personal/escritorio, usando el ejecutable ya instalado. Sin instalación de dependencias. Replay del HTML Laravel completo y JSON real; assets permanentes locales servidos por interceptación. Todas las peticiones no GET se bloquean; ninguna se intentó. No es una sesión HTTP autenticada end-to-end por socket8080.

| Width | Overflow global | Tabla | Controles |
|---|---|---|---|
| 320 | NONE | scroll local | wrap, labels y búsqueda disponibles |
| 375 | NONE | scroll local | PASS |
| 390 | NONE | scroll local | PASS |
| 576 | NONE | scroll local | PASS |
| 768 | NONE | scroll local | PASS |
| 992 | NONE | scroll local | PASS |
| 1200 | NONE | scroll local | PASS |
| 1440 | NONE | scroll local | PASS |

10 filas/11 celdas en página1, segunda10 y tercera1. Navegación Siguiente mostró11–20 y21–21 de21. Vacíos B8/C2/Gamarra revalidados en replay. Errores403/422/500 controlados: mensajes distintos, sin filas falsas. Cero excepciones JS en corrida final. Notificaciones del shell se interceptan con `{notifications:[]}`; tipo de cambio/proveedores no se contactan ni certifican. No formularios financieros enviados.

La primera captura full-page del replay mostró discrepancia de composición del shell durante captura. Se verificaron rectángulos reales (desktop content x250, sección x283) y se repitió con captura viewport y animaciones desactivadas: sidebar y contenido alineados. No se cambió CSS global para reparar el harness.

## Visual Parity

**VISUAL PARITY**: page heading, toolbar contextual, filtros con gutters, summary, next-data-table y paginación reutilizan Material V2/My Movements/Cash Boxes/Subtypes. Se inspeccionaron directamente los cuatro Blades y el contrato de Summary.

**VALID REAL CONTRACT DIFFERENCE:** selector usuario administrativo, sin botones de mutación/documentos, sin acciones por fila, sin moneda no entregada, sin Column Visibility ni DataTables nuevos. No diseño alternativo ni estilos page-specific.

Comparación visual viewport1440 y375 inspeccionada; matriz automatizada en los8 anchos. Capturas y evidencia local de QA (no contienen contraseñas):

- [Desktop](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/cash-admin-1440.png)
- [Mobile](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/cash-admin-375.png)
- [Resultados browser](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/cash-admin-browser.json)
- [Capturas de datos reales](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/cash-admin-data.json)

## Files / Checks / Final State

Cambios de esta fase:

1. `config/venti-next.php`: una route adicional.
2. `resources/views/cashBox/admin_index.blade.php`: dispatcher Admin al parcial readonly; Summary intacto.
3. `resources/views/cashBox/admin_readonly.blade.php`: fachada administrativa.
4. `public/js/cashBox/admin-readonly.js`: sólo contrato de lectura y feedback.
5. Este documento.

Checks: `node --check public/js/cashBox/admin-readonly.js`, Laravel7/PHP7.3.33 `artisan view:cache`, render real de4 contextos, consulta paginada/filters/scope en transacción readonly, replay Chromium8 anchos/errores/paginación, `git diff --check`. Sin cambios CSS/backend ni commit; cambios previos del workspace preservados.

**FRONTEND INTEGRATED — READ PATH**. Permission `listCashMovementAdmin_cashBox`; scope TENANT + COMPANY + BRANCH; admin data CONTEXTUAL. Historical missing-context EXCLUDED / DATA REMEDIATION. Open Cash / Regularization / Documents OUT OF SCOPE. Backend UNCHANGED.
