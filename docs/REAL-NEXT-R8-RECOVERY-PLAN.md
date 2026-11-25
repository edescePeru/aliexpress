# R8 — Recovery Plan (checkpoint R8.0/R8.1)

## R8.2B — COMPLETE (2026-11-25)

Recepción de compra normal S091 homologada y allowlisted exclusivamente por `order.purchase.create`. [Evidencia y validación](REAL-NEXT-R8-PURCHASE-RECEIPT-HOMOLOGATION.md): orden2 autorizada en DB READ ONLY;25 controles preservados;10 resoluciones; dos reintentos interceptados con clave/contexto/payload iguales; JS intacto; cero mutaciones.

FR050 ya recuperado, esta homologación suma0 FR: **76 recuperadas +11 bloqueadas +23 excluidas =110**. B histórico2: recepción completada, Registrar comprobante pendiente1. Conservar observaciones Branch/GRE, FR110/EXPENSE-01, contrato monetario y decisiones C; no iniciar R8.2C. Esperar revisión del propietario. Historia inferior preservada; ya no interpretar Recepción como homologación pendiente.

## R8.2A — estado vigente (2026-11-25)

Autorización del propietario: FR-108/109/110 quedan formalizados. **FR-108 RESTORED; FR-109 RESTORED; FR-110 BLOCKED / EXPENSE-01**. Conciliación: **76 recuperadas +11 bloqueadas +23 excluidas =110**; pendientes frontend confirmados0. Los107 anteriores y propuestas inferiores se conservan como historia, no son el total vigente. S201/S203 no se suman sin contrato DEV.

A194/194 auditadas: **187 con paridad /5 con pérdidas /2 dependencias monetarias** después de recuperar3 superficies mediante2 capacidades. B2 y C125 sin cambios. Nuevos findings DEV0. R8.2A COMPLETE — FRONTEND RECOVERY; R8 global sigue parcial por alcance/contratos; R8.2B no iniciada.

Evidencia: [informe R8.2A](REAL-NEXT-R8-FUNCTIONAL-PARITY-RECOVERY.md), test `tests/Frontend/r82a-recovery.cjs` PASS (8 casos de paginación +2 navegaciones numéricas; menú375/1440; cero pageerrors/overflow). Chrome headless aislado y requests interceptados, no certificación HTTP autenticada ni mutaciones. Ruta destino verificada con PHP7.3.33 y contrato actual. Backend0 / Blade0 / CSS0 / infraestructura0 / mutaciones0; runtime sólo2 JS. `git diff --check` PASS.

Plan restante: FR110 espera EXPENSE-01; mantener los10 bloqueos anteriores, aclaración monetaria, B y decisiones C. No recuperar gasto ni iniciar nuevas fases. Esperar revisión del propietario.

## Historia preservada anterior a R8.2A

## Estado vigente — continuación final R8 (2026-11-25)

HEAD `079aea9b25fa5fe9591fd5607a1801640e5108e4`; baseline compartido `6df57e7`. Se preservaron los tres documentos R8 ya modificados al inicio; no se incorporaron commits ni cambios DEV. Esta sección prevalece sobre los checkpoints históricos inferiores.

**Inventario379: A194 / B2 / C125 / excluidas58. Auditoría A194/194: anteriores139 + adicionales55; pendientes0. Evidencia acumulada PREVIOUS42 / NEW152 (esta continuación2 /53).** Son superficies/variantes renderizadas, no194 URLs independientes. S172 es fallback409 de Create/Edit, sin ruta propia nueva ni duplicación de capacidad.

**Paridad semántica confirmada184; auditadas con pérdidas8** (4 superficies con pérdidas anteriores bloqueadas y4 con nuevas pérdidas); **2 con dependencia contractual monetaria**, sin paridad certificada. Auditoría cerrada NO equivale a implementación completa, seguridad integral o QA mutadora PASS.

Nuevas capacidades perdidas acumuladas en R8: **2 completas de navegación +1 parcial de paginación**; de ellas, en esta continuación se añade sólo R8-F01 (retorno desde Crear gasto, bloqueado por EXPENSE-01 ya existente). Nuevos gates obsoletos0; nuevas raíces/blockers DEV0. Dos recuperaciones frontend directas propuestas y una dependiente del fix DEV existente. S201/S203 no inflan el conteo.

Baseline FR intacto: **74 recuperadas +10 bloqueadas +23 excluidas =107**. Propuestas deduplicadas: FR-108/R8-B02, FR-109/R8-E02 y FR-110/R8-F01; no incorporadas como restauradas ni renumeradas. Conciliación prospectiva, si se aprueban: **110 =74 recuperadas +2 pendientes frontend +11 bloqueadas DEV +23 excluidas**. Candidato monetario1 compartido por2 vistas permanece fuera de los110.

**R8.1: AUDIT COMPLETE FOR UNIVERSE A — FINDINGS OPEN. R8.0/global: PARTIAL — PRODUCT SCOPE DECISIONS OPEN (C125). R8.2: PLAN PREPARED / NOT STARTED / OWNER AUTHORIZATION REQUIRED.** B2 pendientes (Recepción y Registrar comprobante); observaciones visuales receipt/Branch/GRE preservadas. `frmGreRecovery` existe mediante partial incluido: no formulario huérfano.

Backend/Blade/JS/CSS/rutas/allowlist/infraestructura y mutaciones comerciales: **0 cambios/ejecuciones**. Ningún HTTP/GET escritor, export, documento ni proveedor invocado; sin navegador/escritorio ni QA responsive repetida. Verificación nueva estática Git/contratos; simulación previa de paginación identificada como tal, no dato real. Sin commit/push/fetch/pull/merge/rebase.

### Plan R8.2 consolidado propuesto — no implementar sin autorización

| Orden | Capacidad / alcance | Dependencia | Aceptación focal |
|---|---|---|---|
| P1 /1 | FR-108 propuesto, Availability → Solicitar traslado | Ningún blocker nuevo del contrato request_url demostrado | Render según URL/permiso DEV; click interceptado confirma origen/producto/snapshots; absent URL sin alternativa; no POST de traslado |
| P2 /2 | FR-109 propuesto, numeración DailyProfit S120/S121 | Frontend aislado | Loop usa índice; simulación10p en1/4/8; Prev/Next/atajo final preservados; no ejecutar GET writer ni exports |
| P2 /3 | FR-110 propuesto, retorno Crear gasto | EXPENSE-01 existente | DEV resuelve ownership de lista/JSON; después link según permiso. No recuperar una navegación insegura ni declarar retiro por QA |
| DEV /4 | FR047/048/059 y063/064/102/103/105/106/107 | ENTRY-R75-01/ENTRY-R4-01, INV-01, METRIC-01/DASH-R7-01 | Guard/permisos/contexto/GET read y contratos actuales; no repetir certificación completa DEV ni reactivar excluidos |
| Contrato /5 | S201/S203 monetarios | Respuesta DEV exacta en informe, no nuevo FR confirmado | Definir importes netos/impuesto/históricos antes de recuperación; nada de 1.18 supuesto |
| Producto /6 | C125 en37 submódulos | Decisiones agrupadas del propietario | Confirmar continuidad/sustitución/pausa sin retirar dependencias compartidas |
| Visual /7 | B2 + receipt/Branch/GRE | Fase autorizada independiente | Recepción /dashboard/crear/entrada/compra/orden/2; Registrar /dashboard/registrar/comprobante/venta/Ticket, Boleta, Factura. Conservar contratos; no emisión/recepción real |

No existe autorización implícita de R8.2 por terminar las194 auditorías. Auditoría global no cerrada mientras C y el contrato monetario sigan inciertos. Checkpoint siguiente: revisión del propietario de FR108–110 y decisiones C; respuesta DEV de R8-A13. No queda cola semántica A pendiente; no repetir Metas/GRE ni matrices responsive ya válidas. Corrección GRE preservada: frmGreRecovery está incluido, no requiere recreación.

## Evidencia histórica preservada — checkpoints anteriores

## Estado vigente - continuacion sobre HEAD 079aea9 (2026-11-25)

Baseline compartido 6df57e7; HEAD observado 079aea9b25fa5fe9591fd5607a1801640e5108e4. El delta contiene el checkpoint R8 y R7.7E previamente aprobados; no se incorporaron cambios remotos. Working tree limpio al inicio. Los estados siguientes prevalecen sobre los checkpoints historicos conservados.

Inventario 379: A194 / B2 / C125 / excluidas58. Cierres anteriores80; adicionales59; acumulado139/194 (71.65%), pendientes55. Evidencia acumulada: PREVIOUS40 / NEW99; esta ejecucion1 reutilizado y58 nuevas comparaciones. Cierre de auditoria NO significa recuperacion: S357 queda auditada con perdida confirmada R8-B02.

Nuevas perdidas confirmadas1 (navegacion contextual Disponibilidad -> Solicitar traslado); parciales confirmadas1 (R8-E02 paginacion numerica Daily Profit); blockers DEV nuevos0. R8-A13 mantiene dos detalles pendientes por representacion monetaria retirada, sin atribuir una decision fiscal a DEV. FR canonicos74 recuperados +10 bloqueados +23 excluidos=107; R8-B02 se registra separado como hallazgo nuevo pendiente de plan R8.2, sin reutilizar/renumerar IDs FR.

R8.0 PARTIAL; R8.1 PARTIAL; R8.2 NO INICIADO, requiere revision del propietario. Implementacion/backend/Blade/JS/CSS/rutas/allowlist/infraestructura/mutaciones0. No QA runtime nuevo ni llamadas a proveedores.


2026-11-25. Baseline compartido6df57e7; HEAD6df57e7d73a2546bc283527b166041584bfc708f. **No iniciar R8.2.** Este plan separa hallazgos de auditoría, homologación y capacidades ya contabilizadas.

## Estado vigente — continuación optimizada R8 (2026-11-25)

Esta sección sustituye la interpretación de alcance del checkpoint anterior, sin borrar su evidencia. **379 es el inventario provisional, no 379 auditorías obligatorias.** Las 311 filas antiguamente sin cierre no son una cola de homologación ni de QA integral.

| Universo | Superficies | Tratamiento |
|---|---:|---|
| A — NEXT FUNCTIONAL PARITY | 194 | 192 allowlisted activas + Entry Edit (navegación FR047) + editImages (lector compartido modificado por Next) |
| B — ACTIVE HOMOLOGATION PENDING | 2 | Recepción de orden y Registrar comprobante; vigencia/acceso/contrato identificados, sin pérdida Next nueva |
| C — HISTORICAL / SCOPE UNCONFIRMED | 125 | Evidencia de vigencia pendiente agrupada por familias; no auditar exhaustivamente backend |
| EXCLUDED / PAUSED / RETIRED | 58 | Decisiones existentes; independientes de las 23 capacidades FR excluidas |

**Universo A: 80 auditorías semánticas cerradas; 114 pendientes.** Cierre de auditoría significa diferencias explicadas, NO que todas las funciones estén recuperadas: Entry List conserva FR047/048 y StockItems conserva FR059 bloqueados. 39 cierres reutilizan evidencia previa vigente; 41 usan comparación nueva (incluidos 8 del checkpoint anterior). Incremento de esta continuación: **70 cierres A**; no 70 funciones recuperadas. A08 queda revisado fuera de A y no infla esos contadores.

No se asignan FR nuevos: **74 recuperadas +10 bloqueadas +23 excluidas =107**. Pérdidas nuevas confirmadas/parciales, gates obsoletos activos nuevos y findings DEV nuevos:0 en lo revisado. No extrapolar a los114 residuales. Implementación R8, mutaciones comerciales e infraestructura:0. No navegador nuevo ni repetición de QA responsive.

La pertenencia A no se deriva solamente de allowlist: se cruzó historial `front:` de vistas para localizar superficies externas intervenidas (editImages) y navegación compartida retirada (Entry Edit). C continúa provisional; una evidencia nueva de intervención o navegación Next la mueve a A, no se descarta por falta de allowlist.

## Historia — plan del checkpoint inicial

## 1. Recuperaciones frontend directas

**Ninguna pérdida nueva confirmada en el subconjunto revisado.** No reactivar Guardar datos generales separado de QuoteSale: DEV consolidó su payload en Guardar cambios en la cotización en83dbe6c. No crear FR108 para Exportar por Material: era un gate de roles anterior a Next, corregido enbf14723; no coincide con exports FR028/057/058/063/064/103.

Esto no cierra el universo:379 filas provisionales,58 excluidas y311 sin cierre semántico integral. Siguiente trabajo es terminar la auditoría, no generar controles basándose en coincidencias de texto.

## 2. Recuperaciones dependientes de DEV

| FR existentes | Dependencia vigente | Criterio posterior |
|---|---|---|
| FR047 | ENTRY-R75-01: permiso entry_edit distinto del permiso de ruta/roles | DEV alinea contrato; preservar ownership/contexto; entonces reconsiderar recuperación |
| FR048 | ENTRY-R4-01: frontera financiera de reversa | No exponer reversa insegura; fix DEV antes de recuperar |
| FR059 | INV-01 ajuste StockItem | Permiso/scope/authority/lotes y comando seguro; no solución frontend |
| FR063/064/102/103/105/106/107 | INV-01/METRIC-01 residual/DASH-R7-01 | Preservar siete pendientes R7; no ejecutar GET escritores/export para demostrarlos |

FIN-03 sigue como deuda auxiliar: no reintroducir lector inseguro. QA-02 pendiente no oculta mantenedores válidos. DOC-01/despliegue queda separado de los controles documentales recuperados. J/P/K y23 exclusiones de negocio intactos. No crear un finding nuevo para cada FR dependiente de la misma raíz.

## 3. Decisiones de producto

Ninguna decisión nueva exigida por los candidatos obligatorios resueltos. No se necesita autorización para «rescatar» el botón QuoteSale: la capacidad no está perdida. Las119 filas EVIDENCE REQUIRED necesitan primero evidencia de vigencia/historia, no una decisión automática de retiro. Relationship deleted requiere investigar por qué la ruta apunta a un Blade inexistente; no presentar su implementación como recuperación confirmada.

## 4. Homologaciones pendientes (no pérdidas nuevas)

| Orden | Superficie / URL exacta | Estado | Límites |
|---|---|---|---|
| 1 | Recepción OC-00002: http://127.0.0.1:8080/dashboard/crear/entrada/compra/orden/2 | ACTIVE — VISUAL HOMOLOGATION PENDING; render/Guardar presentes | Preservar receipt_key, headers contextuales del formulario, normalPurchaseReceipt.js, cantidades/destinos y contrato DEV. No recibir stock en QA. FR050 ya recuperado |
| 2 | Registrar comprobante: http://127.0.0.1:8080/dashboard/registrar/comprobante/venta/Ticket | ACTIVE — VISUAL HOMOLOGATION PENDING; auditoría residual incompleta | Comparar variantes Boleta/Factura/Ticket, guards y payload completos antes de proponer integración. No emitir, no proveedores |

Las variantes Boleta y Factura comparten superficie; no son dos pantallas nuevas. Su acceso tiene condiciones propias. Receipt es una página distinta de Create Entry directa y no debe fusionarse inventando un selector de orden.

## 5. Correcciones visuales

R8-A02/P3: receipt aún usa cards/encabezados legacy y no carga venti-next.css. Evidencia aislada1440 con dos líneas reales y sin POST. Homologar sólo en fase autorizada, reutilizando baseline existente; no modificar backend ni rehacer contrato. RegistrarComprobante no tuvo inspección visual runtime aquí: no declarar defectos de responsive ni certificación visual.

No hay CSS ni Blade modificados en R8. No duplicar el ajuste de modales Cash R7.7E que ya estaba en el working tree.

## 6. Evidencia insuficiente y cola

- 379 filas canónicas provisionales, IDs R8-S estables.10 COMPLETE,36 PARTIAL,146 REQUIRED,119 EVIDENCE REQUIRED,8 BACKEND BLOCKED,2 homologaciones pendientes,58 excluidas.
- 32 superficies tienen evidencia previa reutilizable de capacidades; esto no certifica por inferencia sus capacidades residuales.
- 16 superficies revisadas focalizadamente en R8; seis formularios Category/Subcategory/Subtype cerrados estáticamente; resto de B pendiente.
- Cerrar R8.0: confirmar vigencia externa, aliases y métodos de retorno dinámico/delegado; separar páginas retiradas de sus endpoints. No confundir el censo379 con379 pantallas activas.
- Continuar R8.1: **R8-A08 Registrar comprobante**, luego residuales A; B restantes; C/D/E. Usar matriz y metadatos r8-ledger.json. Búsquedas globales ya capturadas en r8-scan.json; cada coincidencia requiere historia y alcanzabilidad antes de convertirla en finding.
- Persistir cada bloque en los tres documentos R8. No renumerar FR ni contar nuevas funciones DEV como pérdidas históricas recuperadas.

## 7. Condiciones de cierre

R8.0 PARTIAL. R8.1 PARTIAL. R8.2 READINESS: **NO-GO global hasta completar la auditoría o aprobar expresamente un alcance separado**. No implementar desde este plan.

Contabilidad:74 recuperadas +10 bloqueadas +23 excluidas =107. Nuevas pérdidas confirmadas0; pérdida parcial nueva0; gate obsoleto activo nuevo0; gate histórico ya corregido1. Hallazgos nuevos P0/P1/P2=0; observación visual P3=1 (recepción no homologada). No nuevas raíces DEV. Mutaciones/exports/documentos/proveedores0.

Validación documental: git diff --check. Cambios R8 exclusivamente en estos tres documentos y adenda de conciliación del backlog FR; implementación previa R7.7E preservada. Sin commit/push/merge.

## Plan vigente — auditoría optimizada, no implementación

1. Cola prioritaria:114 residuales de Universo A, no311 ni379. Siguiente checkpoint S201/203 y S305–307; luego S035/039/040 y familias A descritas en informe. No repetir evidencia vigente de80 superficies cerradas.
2. B:2 páginas activas no homologadas (receipt y Registrar comprobante); A08 ya revisado, actualizar su estado residual anterior: no pérdida Next. Mantener las URLs exactas anteriores, incluidas variantes Boleta/Factura. No iniciar homologación.
3. C:125 registros de vigencia por confirmar agrupados en familias; decisiones de alcance pendientes, no125 solicitudes individuales de producto. Si se acredita intervención/navegación Next, trasladar a A.
4. Las10 capacidades FR bloqueadas y23 excluidas conservan IDs, causas y evidencia. Auditoría cerrada con blocker conocido NO significa función recuperada.
5. Visual: receipt legacy observado; branch index con acciones individuales detectado estáticamente. editImages sigue legacy pero únicamente su modificación documental compartida pertenece al cierre A. No ampliar unilateralmente el alcance.

Contadores vigentes:194 A /2 B /125 C /58 excluidas =379. A cerradas80 (39 evidencia previa,41 nueva), pendientes114. Nuevas pérdidas/gates activos/findings DEV0 en lo revisado; FR74+10+23=107. R8.0/R8.1 PARTIAL; R8.2 pendiente de revisión, sin implementación. Backend/Blade/JS/CSS/allowlist/infraestructura/mutaciones0.

### Checkpoint vigente tras segundo avance

A10 resuelve los dos residuos Sales como históricos sin conexión ejecutable en baseline. A11 cierra cinco páginas QuoteSale por evidencia/diff, A12 dos índices Normal y C02 Admin Cash. Ya no repetir esos residuales. **Siguiente: S201/203, S305–307, S035/039/040**, luego resto A por familias. Conteo vigente80/194 cerrado;114 pendientes;39 PREVIOUS/41 NEW. Nuevas recuperaciones listas0; no iniciar R8.2. El conteo histórico inicial311 no es el alcance actual.

## Plan vigente de hallazgos - continuacion079aea9

1. R8-B02: restauracion frontend candidata de request_url en Disponibilidad, solo despues de revision. Backend ya proporciona autorizacion/contexto/prefill. No iniciar R8.2 ahora.
2. R8-A13: decision de representacion monetaria en detalle Normal/Eliminado. No interpretar la falta de campos derivados como autorizacion de retiro, ni restaurar18% fijo. Dos filas pendientes, no cierre artificial.
3. Cierres de esta ejecucion36: GRE3, Cash3, Inventario6 (incluye1con finding), RRHH24. Continuar los78 restantes por familias. No volver a auditar B receipt/Registrar.
4. Observaciones visuales separadas: receipt legacy, acciones individuales Branch y GRE. Sin QA visual nuevo ni autorizacion de redisenio.
5. FR74+10+23=107 conservados. R8-B02 es hallazgo nuevo pendiente de plan, no FR recuperado. No duplicar INV-01/HR-01/HR-02 ni bloqueos anteriores.

## Universo C - decisiones de vigencia agrupadas (sin retiro automatico)

| Familia | Filas C | Decision pendiente / evidencia requerida |
|---|---:|---|
| RRHH personal/nomina/contratos | 59 | Confirmar alcance operativo de transacciones personales frente a maestros HR integrados; HR-01/HR-02 no prueban retiro. No auditar nomina ni reactivar Parentescos deleted roto. |
| Inventario/material/estructura legacy | 27 | Identificar sustitucion efectiva por InventoryLevel/StockItems/ManagedWarehouse; una ruta legacy o ausencia de allowlist no confirma vigencia ni retiro. |
| Entradas/salidas legacy | 14 | Separar entradas/salidas operativas sustituidas de vistas historicas aun utilizadas; menus y llamadas internas antes de homologar. |
| Ordenes/ejecucion/cronogramas | 15 | Confirmar familias de ejecucion/cronograma vigentes y versiones historicas; mantener pausas/exclusiones explicitas, no trasladar automaticamente a A. |
| Caja/gastos/devoluciones/auxiliares | 10 | Definir vigencia del registro admin/cuentas y auxiliares historicos; no inferir alcance por ruta registrada ni ausencia de datos. |

Son cinco decisiones por familia con125 filas de respaldo, no125 nuevas tareas de backend. No cambio de universo por evidencia insuficiente. Recepcion y Registrar siguen B2. R8-A13 es una decision separada dentro de A sobre lectura monetaria, no vigencia de C.

## Checkpoint final vigente - 139/194

59 cierres adicionales: GRE3 + Cash3 + Inventario6 + HR24 + catalogos15 + Daily/User Profit4 + Metas4. Esta ejecucion1 PREVIOUS/58 NEW; acumulado40/99. Quedan55. Dos hallazgos frontend nuevos independientes: R8-B02 (navegacion traslado desde disponibilidad) y R8-E02 (paginacion numerica Daily Profit). S357/S120/S121 estan auditadas con finding, no recuperadas ni certificadas funcionalmente. R8-A13 sigue pendiente en S201/203. No otros blockers DEV nuevos.

Candidatos R8.2: usar request_url autorizado en S357; corregir argumento del bucle numerico Daily Profit sin romper atajo ultima pagina. No implementar sin revision. URLs: http://127.0.0.1:8080/dashboard/disponibilidad/almacenes y http://127.0.0.1:8080/dashboard/listado/ganancias/diarias. FR107 intactos (74/10/23) +2 hallazgos R8 no incorporados como recuperaciones.

Siguiente bloque exacto S157/S163/S171/S172/S118/S062; despues S058-061/S319, S081/S095/S109 y familias pendientes del ledger. Cinco familias C125 requieren vigencia; no se reclasifican por ausencia de allowlist. B2 permanece sin nueva homologacion. Observaciones visuales estaticas: receipt legacy, Branch acciones individuales y GRE acciones individuales. Sin QA visual nuevo. R8.0/R8.1 PARTIAL; R8.2 NO INICIADO.

Verificacion final de esta continuacion: git diff --check PASS;379 IDs/orden/universos preservados; tabla canonica379 filas y11 columnas consistente con ledger;80 cierres anteriores preservados. Solo estos3 documentos cambian en Git; artefactos de auditoria externos actualizados. Backend/Blade/JS/CSS/rutas/allowlist/infraestructura y mutaciones comerciales0. Sin commit, push, fetch, pull ni merge. Evidencia reproducible: r8-continuation-079aea9-verification.json y r8-daily-pagination-isolated.json.
