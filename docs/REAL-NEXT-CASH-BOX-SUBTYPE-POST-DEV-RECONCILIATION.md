# R7.7E — Cash Box & Subtype Post-DEV Reconciliation

Fecha: 2026-11-25. Rama `feature/venti-next-frontend`. HEAD inicial `6df57e7`, working tree limpio. **Checkpoint compartido y baseline para R8: `6df57e7`**. No fetch/pull/merge; R8 no iniciada.

## Alcance y estado inicial

- Cajas: http://127.0.0.1:8080/dashboard/cash-boxes
- Subtipos: http://127.0.0.1:8080/dashboard/cash-box-subtypes

Ambas vistas ya homologadas y allowlisted (`cashBox.index`, `cashBoxSubtype.index`). Crear, editar y activar/desactivar estaban disponibles. No se encontraron gates frontend obsoletos ni una edición completa deshabilitada por CASH-02/03/04. No se agregan capacidades a las 107 existentes.

Fuentes: AGENTS.md, VENTI-NEXT-COMPONENT-PATTERNS.md, informes de integración y reconciliación DEV 2026-11-25, plan post-DEV, backlogs canónicos, REAL-NEXT-CASH-BOXES-SUBTYPES.md, `docs/flujos/AUDITOR-CASH-02-03-04.md`, `tests/Feature/CashMaintainersClosureTest.php` y HEAD. Historial de los adaptadores: `9ad0106` homologación y `41ed99f` correctivos consolidados. Se preserva la evidencia histórica; no se reinterpretan sus pruebas antiguas como ejecutadas nuevamente.

## Contratos y permisos actuales

Los controladores CashBoxController y CashBoxSubtypeController usan auth y permisos granulares. Mantenedores tenant + company; el local de una caja es configuración, no un filtro de ownership del listado. Las mutaciones tienen contexto activo autorizado, scope y bloqueos transaccionales de CashConfigurationGuard. Nada de esto se modifica.

| Capacidad | Cajas | Subtipos | Método / permiso |
|---|---|---|---|
| Vista | cashBox.index | cashBoxSubtype.index | GET / list_cashBox o list_cashBoxSubtype |
| Consulta | cashBox.list, /cash-boxes/list | cashBoxSubtype.list, /cash-box-subtypes/list | GET / mismo permiso de listado |
| Crear | cashBox.store, /cash-boxes/store | cashBoxSubtype.store, /cash-box-subtypes/store | POST / create_cashBox o create_cashBoxSubtype |
| Editar | cashBox.update, /cash-boxes/update | cashBoxSubtype.update, /cash-box-subtypes/update | POST / edit_cashBox o edit_cashBoxSubtype |
| Activar/desactivar | cashBox.toggle, /cash-boxes/toggle | cashBoxSubtype.toggle, /cash-box-subtypes/toggle | POST / disable_cashBox o disable_cashBoxSubtype |

Paths relativos anteriores bajo `/dashboard`. No existe eliminación en el contrato de estas dos pantallas; no se inventó. Permisos Blade, JSON de permisos, CSRF, IDs, names, data attributes, handlers y confirmaciones se conservan.

## CASH-02 — Moneda

CashConfigurationGuard::assertCurrency compara la moneda propuesta con la autoridad actual y rechaza cambio con sesiones, movimientos o saldo distinto de cero. Para bancaria, autoridad = cuenta bancaria vinculada; validateCashBox descarta la moneda enviada por el cliente y obtiene la de esa cuenta. No se convierte saldo ni se asigna una moneda nueva a historia desconocida.

Frontend: crear efectivo inicia PEN, selector permite sin definir/PEN/USD. Edit precarga el valor del DTO; banco muestra la cuenta y oculta el selector independiente. La moneda sigue editable en efectivo: la decisión sobre historia pertenece al backend. Los demás campos no se bloquean. Update envía id, name, type, branch_id, uses_subtypes, company_bank_account_id, currency, position y token. Rechazo 422 muestra error y conserva modal/datos intentados; cerrar y reabrir precarga nuevamente el DTO no modificado.

## CASH-03 — Modalidad

assertSubtype impide cambiar is_deferred si existen cobros sale/income relacionados. Update completa flags omitidos con los valores guardados antes de validar. Los flags explícitos inválidos se rechazan; requires_commission se normaliza a false si no es diferido. Esta semántica fue inspeccionada, no recertificada con escrituras.

Frontend envía siempre is_deferred y requires_commission como 0/1; no depende de omisión de checkbox HTML. Precarga inmediata y diferida con comisión confirmada. La comisión sólo se presenta para diferido. El rechazo deja el modal editable y no altera el listado; al reabrir se recupera la modalidad persistida. No hay cálculo frontend de historia ni bloqueo total del subtipo.

## CASH-04 — Obligaciones pendientes

assertCanDeactivate consulta cobros positivos no regularizados y no cancelados en toda la empresa, independientemente de sesión abierta/cerrada. Se conserva acción Desactivar y confirmación; POST lleva id y token. No se calcula deuda en JS. Ante rechazo no cambia optimistamente el estado de la fila. Ante éxito, el handler existente vuelve a consultar el listado (inspección contractual; persistencia/success backend no ejecutados aquí).

## Inventario y paridad

| Capacidad | Resultado |
|---|---|
| Crear / editar / activar / desactivar | Preservadas con sus permisos; no ejecutadas contra DB |
| Buscar | q y reinicio de página existentes, sin cambios |
| Filtrar subtipos por caja / global | cash_box_id existente, sin cambios |
| Paginar | current_page/last_page/total/from/to; controles sólo cuando corresponden |
| Modales / confirmaciones / errores | Apertura, cierre, precarga, reintento y rechazo interceptado PASS |
| Acciones de fila | Un dropdown; fa-pen y fa-power-off + next-row-action-item-icon |
| Estado | Activo real; Activar para inactivo SIMULATED |
| Campos | Cajas: nombre, tipo, alcance/local, subtipos, cuenta, moneda, posición. Subtipos: alcance/caja, código, nombre, diferido, comisión, posición |

No se añade filtro, columna configurable, export, borrado ni comportamiento nuevo. Gates obsoletos eliminados: 0. Capacidades restauradas: 0 nuevas; perdidas: 0.

## Cambio visual puntual

La inspección inicial confirmó que los modales disponían de ancho compartido suficiente, pero todos sus campos estaban apilados, incluidos tipo, moneda y posición. Se reutilizó `.form-row` y `.col-12/.col-md-*` de Bootstrap, sin CSS nuevo:

- Cajas: Nombre/Tipo en proporción 8/4; Moneda/Posición en 6/6; alcance y cuenta bancaria mantienen espacio para textos largos.
- Subtipos: Código/Nombre en 6/6; Diferido/Comisión en una fila cuando aplica; posición compacta. En móvil se apilan naturalmente.
- Se mantienen next-aux-modal, gutters, footer, botones, foco y handlers existentes.

Sólo dos Blade cambiaron en implementación. La allowlist ya era correcta. Backend/JS/CSS/infraestructura: 0 cambios.

## Evidencia frontend y dataset

Runtime PHP 7.3.33. Se renderizaron los Blade y se obtuvieron DTO reales de listar con Owner EDESCE, tenant1/company1/branch1, mediante harness de lectura y transacción MySQL `START TRANSACTION READ ONLY`, finalizada con ROLLBACK. No se ejecutaron POST contra Laravel ni se modificó el contexto persistente del usuario.

- Cajas actuales: 4, IDs **1,2,5,6**; efectivo1/6 y bancarias2/5; moneda PEN, todas activas; cajas generales2/5 y locales1/8 para1/6.
- Subtipos: 5, IDs **1,2,3,4,6**; POS3 diferido con comisión; restantes inmediatos; subtipo6 asociado a caja2, restantes generales; todos activos.
- Dataset de una página; no se fabricó multipaginación ni se recertificó aislamiento backend. Estado inactivo sólo fixture **SIMULATED** en memoria del navegador.

Chrome headless aislado, sin escritorio ni navegador local. Browser sirvió HTML generado por Laravel, assets permanentes y DTO capturados; todas las solicitudes externas/auxiliares fuera del alcance fueron interceptadas. Por tanto esto es QA frontend, no una prueba HTTP integral de autorización/CSRF.

**34 comprobaciones registradas PASS**: 24 combinaciones listado/create/edit en375/1440/1920/2560, seis comprobaciones de payload/rechazo y cuatro adicionales de precarga bancaria/diferida y activación simulada. Ocho POST interceptados con422 SIMULATED, **cero POST enviados al backend**. Errores JS no capturados:0. Overflow global:0. Inspección visual de modales efectivo/bancario e inmediato/diferido realizada. Botones continúan habilitados tras rechazo y la reapertura restaura el DTO original.

Artefactos locales (no versionados): `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`:

- r77e-read.php, r77e-data.json, r77e-box.html, r77e-subtype.html: extracción de lectura/render.
- r77e-browser.cjs, r77e-results.json:30 checks y6 POST interceptados.
- r77e-extra.cjs, r77e-extra-results.json:4 checks y2 POST interceptados.
- r77e-{box,subtype}-{create,edit}-{375,1440,1920,2560}.png: capturas finales.
- r77e-bank-edit.png, r77e-deferred-edit.png, r77e-{box,subtype}-422.png: variantes y rechazo.

`artisan view:cache`: PASS. `git diff --check`: PASS. JS no modificado. Suite CashMaintainersClosureTest inspeccionada, no ejecutada nuevamente por exclusión explícita de recertificación backend.

## Evidencia DEV y pendientes

AUDITOR-CASH-02-03-04 reporta pruebas privadas con controladores/modelos reales, permisos efectivos, contextos negativos, historia abierta/cerrada, pendientes/regularizados/cancelados y fallo inducido con rollback. Se acepta esa evidencia como DEV; no se declara ejecutada por esta fase. Su nota mantiene pendiente validación HTTP/UI mutadora instalada y concurrencia MySQL; no certifica el ciclo financiero completo.

QA mutadora real: **PENDING / NOT EXECUTED**. Pruebas frontend interceptadas no certifican persistencia, locks, concurrencia ni reglas financieras. Ningún defecto backend nuevo demostrado; no se duplica CASH-02/03/04 ni se actualizan backlogs sin evidencia nueva. Restricciones DEV preservadas. No se reabren módulos excluidos.

## Cierre

SURFACES REVIEWED:2/2. CASH BOX y CASH BOX SUBTYPE: FRONTEND RECONCILED. CASH-02/03/04: CONTRACT + FRONTEND PASS, evidencia backend atribuida a DEV. FUNCTIONAL PARITY: PRESERVED. VISUAL PARITY: PASS con ajuste de densidad. REAL BUSINESS MUTATIONS:0.

Contabilidad global intacta: **74 recuperadas +10 bloqueadas +23 excluidas =107**.

**R7.7: COMPLETE en alcance frontend; mutaciones reales pendientes. R8 READINESS: GO WITH CONDITIONS**: revisión del propietario, baseline6df57e7,10 blockers y23 exclusiones intactos, candidato QuoteSale Edit ya registrado en R7.7D y límites de evidencia indicados. No se inicia R8. Sin commit/push/merge.
