# REAL NEXT — Financial Purchase — Create + Edit

2026-11-22. HEAD de referencia:996a31a. Sin commit.

Create y Edit: **FRONTEND INTEGRATED WITH TECH DEBT — MUTATION QA PENDING**.
Backend UNCHANGED. CSS nuevo0. Store/Update y mutaciones de detalles NOT EXECUTED.

## Routes / Permissions

| GET | Route name / view | Permission |
|---|---|---|
| /dashboard/crear/orden/compra/finanzas | order.purchase.finance.create / orderPurchaseFinance.createOrderFinance | create_orderPurchaseFinance |
| /dashboard/editar/orden/compra/finanza/{id} | Sin nombre / orderPurchaseFinance.editOrderPurchaseFinance | update_orderPurchaseFinance |

Controller OrderPurchaseFinanceController. Se preservan auth, check.user.enabled, password.changed, tenant.context, permission y middleware contextual del controller. CSRF, enctype, formCreate, data-url, nombres, IDs, templates y endpoints no cambiados.

Allowlist: sólo nombre Create y URI exacto Edit. Sin Store/Update/JSON ni endpoints de detalles.

## Scope

TENANT + COMPANY. ScopedFinancePurchaseCompany en órdenes/detalles. Company debe pertenecer al tenant; sin contexto falla cerrado. Branch no se añade como ownership.

## Audit Technical Debt

TECH DEBT — GET WITH TECHNICAL AUDIT WRITE / TECHNICAL WRITE ACCEPTED.

Ambos GET escriben user_id, action, duración time y timestamps Eloquent. Create mantiene etiqueta legacy Crear Orden compra Normal VISTA; Edit usa Editar Orden de compra finance VISTA. Sin payload de formulario ni modificación de orden/stock/saldos. No se eliminó Audit ni se modificó backend.

QA: controller Create real con conexión MySQL READ ONLY y evento creating de Audit interceptado exclusivamente en memoria del proceso de prueba. Tres intentos interceptados por corrida; ninguna auditoría ni escritura comercial persistida. Es una técnica QA, no el comportamiento productivo: el GET normal conserva Audit.

## Create Lookups

- Supplier: tenant.
- Aprobador: tenant + company_user.is_active en company actual.
- Quote: tenant/company + raise_status1 + state_active=open.
- PaymentDeadline: tenant/company + purchases + selectable().
- UnitMeasure: tenant.
- No nuevos lookups, inventario ni Entry.
- OCF permanece calculado por backend desde max ID contextual incluyendo eliminadas. El frontend no reserva ni certifica numeración.

Lectura real actual y render controller:

| Contexto | Proveedores IDs | Aprobadores IDs | Cotizaciones | Plazos compras | Unidades IDs | Órdenes |
|---|---|---|---|---|---|---|
| EDESCE T1/C1/B1, Owner4 |1|2,3,4,6|[]|[]|1,2|0|
| EDESCE T1/C2/B2, Owner4 |1|3,4|[]|[]|1,2|0|
| Gamarra T3/C3/B4, Owner7 |2|7|[]|[]|3|0|

Create/update permissions presentes en los actores leídos. No se crean órdenes ni referencias.

## Edit Binding

findOrFail bajo scope, sin withTrashed: propia activa puede resolver; otra company/tenant/eliminada/inexistente no. ID99999999 devuelve ModelNotFoundException (contrato404) en los tres contextos. No se presenta como HTTP E2E.

POSITIVE EDIT QA — PENDING DATASET. Tampoco hay órdenes ajenas/eliminadas para certificar esos casos HTTP. Render estructural con modelo y línea NO persistidos, fechas/importes controlados, sin invocar Update ni crear fixtures DB. No equivale a validar binding positivo.

## Historical PaymentDeadline

selectable(currentId) preservado: plazo inactivo actual permanece seleccionable dentro del scope/tipo purchases. Ningún reemplazo frontend. Dataset real sin plazos: cobertura poblada/histórica pendiente.

## Historical Approver Debt

TECH DEBT — HISTORICAL SELECTOR OPTION NOT REHYDRATED.

Si approved_by ya viene en order pero no está en users, se muestra su ID histórico como contexto read-only y se conserva el mismo id/name approved_by mediante input hidden. No opción inventada, no query extra ni ampliación del catálogo. Select2 sólo inicializa selects reales. Cuando hay opción disponible, se conserva el selector existente. La elección alternativa en el caso histórico ausente queda pendiente de un contrato GET completo; no se sustituye ni borra el valor recibido.

## Historical Quote Debt

Mismo tratamiento para quote_id recibido pero ausente de quotesRaised (por ejemplo cerrada). No se carga relación quote ni se inventa descripción. Modelo de presentación con approved_by999998 y quote_id999997: FormData conserva ambos después de inicialización en los tres contextos. IDs exclusivamente sintéticos en memoria, no registros reales.

Esta preservación de payload no soluciona la rehidratación backend; deuda documentada.

## Credit-Bound Edit

GET no consulta crédito ni aplica assertMutable: no se deshabilita todo el formulario ni se inventa banner de bloqueo. Guard financiero backend conserva su autoridad sobre cambios financieros y detalles. Metadata no implica necesariamente rechazo total. No consulta SupplierCredit adicional; QA de orden vinculada pendiente de dataset y de mutaciones.

## Sections / Visual Parity

REUSE WITH CONTRACT ADAPTATION: material.create e invoice.create_invoice. Tres bloques iguales en Create/Edit: Datos generales y referencias, Líneas de compra, Importes. Observación y moneda permanecen en sus grupos existentes. Superficies con next-form-section, headers numerados, toolbar/actions y botones aprobados. Se eliminan controles decorativos de colapso y CSS inline de Select2. Sin nuevos estilos globales/page-specific.

VALID REAL CONTRACT DIFFERENCE: Edit conserva campos históricos y handlers de líneas persistidas; no se sustituye por un formulario nuevo. No wizard ni cierre inicial de secciones. No required nuevo; badges de booleanos quitados para no aparentar validación requerida inexistente.

## Lines

Se conserva editor, template service-selected, body-services, data-*, controles y orden de inputs. Los handlers actuales recorren parents/siblings; no se reordena ese contrato. Etiquetas móviles se agregan después del input en DOM y se presentan antes con order-first; no desplazan el primer elemento leído por .val(). Encabezados desktop ocultos en móvil para evitar una columna de títulos desconectada de campos.

Empty state mediante observación DOM de children; no agrega, edita ni elimina líneas. Add/update/delete no ejecutados. Botones siguen operativos; QA no hace click.

## Money / Tax

Cálculos, precisión, impuestos, moneda y payload legacy permanecen. No se introducen reglas ni autoridad financiera frontend. Totales adaptados a col-12/col-lg-6 para móvil. No se cambian subtotal_send/taxes_send/total_send, state/currency ni regularize. Reconciliación/validación de escritura fuera de alcance.

## Select2 / Empty States

Mismas opciones y una instancia por select. En Create5 selects/5 instancias; Edit estructural con dos referencias históricas ocultas3 selects/3 instancias. Abrir/cerrar proveedor funciona. Se informa cuando una colección está vacía sin fabricar opciones. En real hay proveedores/aprobadores/unidades; cotizaciones/plazos vacíos comprobados. Sin proveedores/aprobadores: rama de presentación disponible, escenario real no encontrado.

## Validation

Request y FinancePurchaseInput sin cambios. Laravel422 conserva mensajes/toastr existentes. Única modificación de JS externo: fallback responseJSON cuando un error HTTP no devuelve JSON, para evitar TypeError y mostrar HTTP status. No nuevos endpoints, payloads, reglas o handlers mutadores. 403/404/422/500 no se transforman en lookup vacío.

Errores de Store/Update/detalles no se provocaron: MUTATION QA PENDING. No se certifica validación POST por lectura de código. Los fallos GET conservan respuesta Laravel; no se interceptan como formulario vacío.

## Mutation Boundary

Store, Update, agregar/editar/eliminar detalle, regularización, restore, documentos y exports NOT EXECUTED. No botón financiero pulsado. Navegador bloquea requests no-GET; cero intentos de escritura observados. Inputs hidden históricos verificados únicamente mediante FormData sin enviar.

## Responsive

Chrome headless aislado, sin escritorio del usuario. HTML Laravel real de Create con lookups reales; Edit render controlado explícito. Assets desde servidor local8080; solicitudes de shell ajenas al alcance neutralizadas en QA. No equivale a login HTTP end-to-end.

320/375/390/576/768/992/1200/1440 en tres contextos y dos vistas:48 mediciones, sin overflow global. Menú móvil colapsado mediante PushMenu existente para inspección de contenido. Select2, grillas apiladas, etiquetas móviles, importes y acciones revisados. Cero errores JS, cero POST intentados. Ningún botón de guardar quedó permanentemente disabled.

## Automated Checks / Evidence

- PHP7.3.33 artisan view:cache PASS y renders PHP ejecutados (el render detectó y permitió corregir una directiva Blade adyacente; resuelto antes del cierre).
- node --check createOrderFinance.js y editOrderFinance.js PASS.
- FinancePurchaseListReadPathTest:2 tests/10 assertions PASS, suite existente; no certifica mutaciones/forms.
- git diff --check PASS.
- Evidencia: C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/finance-forms/ (JSON de lectura/browser y capturas).

## Files

- resources/views/orderPurchaseFinance/createOrderFinance.blade.php
- resources/views/orderPurchaseFinance/editOrderPurchaseFinance.blade.php
- public/js/orderPurchaseFinance/createOrderFinance.js: fallback de errores sin JSON.
- public/js/orderPurchaseFinance/editOrderFinance.js: mismo fallback en tres callbacks.
- config/venti-next.php: sólo dos entradas de vista adicionales.
- docs/REAL-NEXT-FINANCIAL-PURCHASE-FORMS.md

Cambios anteriores del workspace preservados. Backend modificado NO. CSS nuevo0. Sin commit.

## Backend Sync Notes

[OPEN] TECH DEBT — HISTORICAL SELECTOR OPTION NOT REHYDRATED. GET no incluye aprobador inactivo/cotización cerrada seleccionados. Frontend conserva IDs recibidos sin ampliar opciones; backend debe definir rehidratación contextual si se requiere cambiar esas referencias. Frontend puede continuar:Sí.

[ACCEPTED TECH DEBT] GET WITH TECHNICAL AUDIT WRITE. No implica escritura comercial; preservado.

[OPEN] POSITIVE EDIT QA — PENDING DATASET. Store/Update/detalles/credit lock mutador no certificados. No se fabrican órdenes para cerrar estos puntos.

## Visual Review Limitation

PENDING MANUAL REVIEW — DESKTOP SHELL OVERLAP: las capturas desktop del replay muestran sidebar sobre el borde izquierdo del contenido incluso tras esperar la transición. Las mediciones de scrollWidth no detectan esta superposición: no equivalen a paridad visual completa. No se atribuye a backend ni se modifica shell/CSS global para ocultarla. Mobile permite inspección completa; layout desktop requiere validación final en sesión HTTP normal antes de cerrar VISUAL PARITY global.
