# R8 — Recovery Plan (checkpoint R8.0/R8.1)

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
