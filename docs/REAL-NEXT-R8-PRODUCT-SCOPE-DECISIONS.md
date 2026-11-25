# REAL NEXT — R8.4 — Product Scope Decisions

Fecha: **2026-11-25 (America/Lima)**. Documento ejecutivo para reunión funcional.

**125 superficies / 37 submódulos / cinco familias. PRODUCT DECISIONS: DEFERRED TO FUNCTIONAL TEAM.**

Se reutiliza la clasificación del inventario R8. No se repitió auditoría, QA ni investigación de código. Las descripciones resumen los nombres y contratos ya documentados; no certifican disponibilidad, seguridad ni uso actual. Los IDs C-01…C-37 son referencias de reunión, no findings ni tareas nuevas.

## Resumen para la reunión

| Familia | Submódulos | Superficies |
|---|---:|---:|
| Recursos humanos | 21 | 59 |
| Inventario legacy | 5 | 27 |
| Entradas y salidas | 2 | 14 |
| Ejecución y cronogramas | 4 | 15 |
| Auxiliares | 5 | 10 |
| **Total** | **37** | **125** |

Todas las decisiones nuevas quedan **PENDIENTE DE REUNIÓN FUNCIONAL**. El equipo podrá elegir **Mantener**, **Homologar posteriormente**, **Postergar**, **Retirar operativamente** o **Solicitar aclaración técnica**, indicando responsable, consumidores afectados y alcance. Ninguna opción está preseleccionada. No se generan 125 tareas de homologación.

Antes de decidir, confirmar uso operativo, necesidad de históricos, dependencia de módulos vigentes y equivalencia real de los reemplazos. Una ruta registrada o un enlace visible no prueba seguridad; un enlace oculto no acredita retiro. La decisión funcional tampoco sustituye la resolución técnica de un blocker.

## Dependencias y sustituciones documentadas

- **Worker vigente:** ofrece navegación a contratos/renovaciones/términos y cuentas del trabajador; `have_contract` indica existencia histórica, no vigencia.
- **CategoryEquipment vigente:** conserva navegación a equipos hijos. La pausa de cotización industrial no demuestra retiro de ese catálogo compartido.
- **Compras normales:** el listado Normal V2/trash es moderno; las otras vistas de perdidas/regularizadas/regularización requieren decisión propia. Normal permanece activo.
- **Inventario moderno:** Material V2, Variants, StockItems, ManagedWarehouse, Location e InventoryLevel son referencias existentes, pero no prueban equivalencia total de activos, paquetes, combos, retacería o las once vistas estructurales.
- **Entries V2 y Transfers:** no demuestran sustitución completa de entradas de inventario/retacería, reportes ni salidas simples/activos.
- **CashMovement/CashSession:** modernos; equivalencia de Caja por tipo por determinar. SupplierCredit no sustituye automáticamente Egresos de proveedores; Bill sólo es catálogo y no reemplaza Rendición; Finance Invoice no equivale a Trabajos y finanzas.
- **Relationship:** catálogo moderno, pero la superficie de eliminados tiene una vista faltante documentada. Para el resto sin reemplazo demostrado: **Por determinar**.

## Precauciones y blockers existentes

Las URLs siguientes son localizadores documentales, **no instrucciones para navegar o ejecutar**. No se abrió ninguna en R8.4. Los placeholders requieren un registro/contexto autorizado; no se inventaron IDs. Formularios, botones y GET no están certificados por figurar aquí.

| Área | Evidencia existente / advertencia | Responsable o siguiente decisión |
|---|---|---|
| Rendición | EXPENSE-01 OPEN: ownership/contexto del listado y JSON; retorno FR-110 bloqueado. | DEV, handoff B6; equipo funcional decide continuidad sin omitir el blocker. |
| Inventario/estructura | INV-01 y WAREHOUSE-01/02: contexto legacy y límites de schema. ManagedWarehouse no hereda automáticamente esos defectos. | DEV, handoff B3/B4/B10/B11; equivalencia funcional por determinar. |
| Pagos al personal | DASH-R7-01 / METRIC-01: GET `/dashboard/personal/payments` crea/actualiza SueldoMensual y consulta tipo de cambio. No tratarlo como lectura inocua del listado S236. | DEV, handoff B5: separar lectura y comando. |
| Dependencia de métricas | GET `/dashboard/store/rotation/material/` escribe Material/RotationMaterial. Advertencia transversal del handoff, no superficie adicional de C. | DEV B5; no ejecutar ni ampliar el universo. |
| Parentescos eliminados | HR-02: Blade `indexDeleted` faltante. No asumir una página utilizable ni fabricar un sustituto. | Aclaración técnica y necesidad funcional. |
| Egresos de proveedores | PUR-01 / OrderService legacy y fuentes mixtas documentados; no certificar fuentes por existir el reporte. | Usar estado conciliado R8.3; aclarar alcance compartido. |
| Salidas / ejecución | Output legacy e industrial tienen pausas documentadas. No extenderlas automáticamente a todos los submódulos relacionados ni reactivar por este documento. | Confirmar alcance funcional antes de invertir. |

Los once FR bloqueados conservan sus seis raíces canónicas: ENTRY-R75-01, ENTRY-R4-01, INV-01, METRIC-01, DASH-R7-01 y EXPENSE-01. El handoff R8.3 contiene aceptación y responsables; no se crean findings nuevos ni se certifican correcciones en esta fase. S201/S203 siguen como consulta contractual monetaria independiente, fuera de C y sin aumentar FR.

## Recursos humanos

| ID | Submódulo | Superficies | Descripción funcional | URL principal | Dependencia o sustitución conocida | Decisión funcional |
|---|---|---:|---|---|---|---|
| C-17 | Asistencia y reportes de horas/pagos | 8 | Calendario y registro de asistencias, con consultas de horas y totales de pagos. | [http://127.0.0.1:8080/dashboard/calendario/asistencia](http://127.0.0.1:8080/dashboard/calendario/asistencia) | Modelo assistance / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-18 | Boletas de personal | 4 | Creación y consulta de boletas de pago por trabajador. | [http://127.0.0.1:8080/dashboard/boletas/pago/trabajadores](http://127.0.0.1:8080/dashboard/boletas/pago/trabajadores) | Modelo boleta / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-19 | Bonos especiales | 4 | Registro, edición, consulta y reporte de bonos especiales. | [http://127.0.0.1:8080/dashboard/bonos/especiales](http://127.0.0.1:8080/dashboard/bonos/especiales) | Modelo bonus / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-20 | Contratos y renovaciones | 5 | Creación, edición, consulta, historial de eliminados y renovación de contratos. | [http://127.0.0.1:8080/dashboard/contratos](http://127.0.0.1:8080/dashboard/contratos) | Worker.index activo enlaza crear/renovar y término | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-21 | Cuentas del trabajador | 1 | Registro y consulta de cuentas de un trabajador. | `http://127.0.0.1:8080/dashboard/registrar/cuentas/trabajador/{worker_id}` (template) | Worker.index activo ofrece navegación a cuentas | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-22 | Descansos médicos | 3 | Registro, edición y consulta de descansos médicos. | [http://127.0.0.1:8080/dashboard/descansos/medicos](http://127.0.0.1:8080/dashboard/descansos/medicos) | Modelo medicalRest / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-23 | Descuentos | 3 | Registro, edición y consulta de descuentos del personal. | [http://127.0.0.1:8080/dashboard/descuentos](http://127.0.0.1:8080/dashboard/descuentos) | Modelo discount / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-24 | Gratificaciones | 2 | Creación y consulta de gratificaciones. | [http://127.0.0.1:8080/dashboard/gratificaciones](http://127.0.0.1:8080/dashboard/gratificaciones) | Modelo gratification / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-25 | Jornada de trabajo | 1 | Registro de jornadas de trabajo. | [http://127.0.0.1:8080/dashboard/registrar/jornadas/trabajo](http://127.0.0.1:8080/dashboard/registrar/jornadas/trabajo) | Modelo workingDay / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-26 | Licencia sin goce | 3 | Registro, edición y consulta de licencias sin goce. | [http://127.0.0.1:8080/dashboard/licencias/sin/gozo](http://127.0.0.1:8080/dashboard/licencias/sin/gozo) | Modelo unpaidLicense / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-27 | Licencias | 3 | Registro, edición y consulta de licencias. | [http://127.0.0.1:8080/dashboard/licencias](http://127.0.0.1:8080/dashboard/licencias) | Modelo license / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-28 | Pagos al personal | 1 | Consulta de pagos al personal; su flujo asociado incluye cálculo con escrituras conocidas. | [http://127.0.0.1:8080/dashboard/pagos/al/personal](http://127.0.0.1:8080/dashboard/pagos/al/personal) | No sustituido por Cash/Metas | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-29 | Parentescos eliminados | 1 | Historial de parentescos eliminados; la vista faltante impide asumir disponibilidad. | [http://127.0.0.1:8080/dashboard/parentescos/eliminados](http://127.0.0.1:8080/dashboard/parentescos/eliminados) | Catálogo Relationship moderno; sólo eliminados pendiente | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-30 | Pensiones alimenticias | 2 | Consulta de pensiones alimenticias y su formulario por trabajador. | [http://127.0.0.1:8080/dashboard/pension/alimentos](http://127.0.0.1:8080/dashboard/pension/alimentos) | Modelo alimony / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-31 | Permisos | 3 | Registro, edición y consulta de permisos de trabajadores. | [http://127.0.0.1:8080/dashboard/permisos/trabajadores](http://127.0.0.1:8080/dashboard/permisos/trabajadores) | Modelo permit / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-32 | Permisos por hora | 3 | Registro, edición y consulta de permisos por hora. | [http://127.0.0.1:8080/dashboard/permisosxhora/trabajadores](http://127.0.0.1:8080/dashboard/permisosxhora/trabajadores) | Modelo permithour / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-33 | Préstamos al personal | 3 | Registro, edición y consulta de préstamos al personal. | [http://127.0.0.1:8080/dashboard/prestamos](http://127.0.0.1:8080/dashboard/prestamos) | Modelo loan / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-34 | Renta de quinta categoría | 2 | Creación y consulta de renta de quinta categoría. | [http://127.0.0.1:8080/dashboard/renta/quinta/categoria](http://127.0.0.1:8080/dashboard/renta/quinta/categoria) | Modelo fifthCategory / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-35 | Régimen de trabajo | 1 | Registro de regímenes de trabajo. | [http://127.0.0.1:8080/dashboard/registrar/regimenes/trabajo](http://127.0.0.1:8080/dashboard/registrar/regimenes/trabajo) | Modelo regime / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-36 | Suspensiones | 3 | Registro, edición y consulta de suspensiones. | [http://127.0.0.1:8080/dashboard/suspensiones](http://127.0.0.1:8080/dashboard/suspensiones) | Modelo suspension / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-37 | Vacaciones | 3 | Registro, edición y consulta de vacaciones. | [http://127.0.0.1:8080/dashboard/vacaciones](http://127.0.0.1:8080/dashboard/vacaciones) | Modelo vacation / flujo de personal; no reemplazo moderno equivalente demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |

## Inventario legacy

| ID | Submódulo | Superficies | Descripción funcional | URL principal | Dependencia o sustitución conocida | Decisión funcional |
|---|---|---:|---|---|---|---|
| C-12 | Combos | 2 | Consulta y generación de combos de materiales. | [http://127.0.0.1:8080/dashboard/listado/combos/materiales](http://127.0.0.1:8080/dashboard/listado/combos/materiales) | Presentaciones/ofertas no equivalencia demostrada de combos; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-13 | Equipos predeterminados por categoría | 3 | Creación, edición y listado de equipos predeterminados de una categoría. | `http://127.0.0.1:8080/dashboard/equipos/categoria/{category_id}` (template) | CategoryEquipment A sigue navegando a equipos hijos | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-14 | Materiales / activos / tienda / paquetes legacy | 9 | Vistas anteriores de materiales, activos, tienda, ítems y separación de paquetes. | [http://127.0.0.1:8080/dashboard/habilitar/materiales](http://127.0.0.1:8080/dashboard/habilitar/materiales) | MaterialV2/Variants/StockItems modernos; activos/paquetes no equivalentes automáticamente; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-15 | Navegador de estructura física legacy | 11 | Navegación de almacenes y estructura física, incluidos materiales e ítems asociados. | [http://127.0.0.1:8080/dashboard/areas](http://127.0.0.1:8080/dashboard/areas) | ManagedWarehouse/Location/InventoryLevel son referencia moderna; equivalencia de11 vistas no demostrada; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-16 | Retacería e Items | 2 | Consulta de materiales de retacería y sus ítems. | [http://127.0.0.1:8080/dashboard/crear/retazos/materiales](http://127.0.0.1:8080/dashboard/crear/retazos/materiales) | StockItems no demuestra equivalencia del ciclo retacería | PENDIENTE DE REUNIÓN FUNCIONAL |

## Entradas y salidas

| ID | Submódulo | Superficies | Descripción funcional | URL principal | Dependencia o sustitución conocida | Decisión funcional |
|---|---|---:|---|---|---|---|
| C-10 | Entradas inventario/retacería y reportes legacy | 8 | Entradas de inventario y retacería, listado anterior de compras y reportes asociados. | [http://127.0.0.1:8080/dashboard/entradas/compra](http://127.0.0.1:8080/dashboard/entradas/compra) | EntriesV2 moderno; inventario/retacería/reportes no equivalentes por nombre; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-11 | Solicitudes y salidas simples / áreas / activos | 6 | Solicitudes y salidas simples, incluidas variantes de activos y reportes por área. | [http://127.0.0.1:8080/dashboard/mis/solicitudes/area](http://127.0.0.1:8080/dashboard/mis/solicitudes/area) | Transfers moderno NO sustituye toda salida simple/activos | PENDIENTE DE REUNIÓN FUNCIONAL |

## Ejecución y cronogramas

| ID | Submódulo | Superficies | Descripción funcional | URL principal | Dependencia o sustitución conocida | Decisión funcional |
|---|---|---:|---|---|---|---|
| C-06 | Compras normales: vistas legacy alternativas | 4 | Listado anterior de compras normales y vistas de órdenes perdidas, regularizadas y regularización. | [http://127.0.0.1:8080/dashboard/ordenes/compra/normal](http://127.0.0.1:8080/dashboard/ordenes/compra/normal) | NormalV2/trash moderno para listado; otras vistas requieren mapeo | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-07 | Cronogramas y avances | 7 | Creación y consulta de cronogramas, gestión, revisión y registro de avances. | [http://127.0.0.1:8080/dashboard/cronogramas](http://127.0.0.1:8080/dashboard/cronogramas) | Sin reemplazo moderno demostrado; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-08 | Trabajos y finanzas | 1 | Consulta de trabajos y finanzas en su listado propio. | [http://127.0.0.1:8080/dashboard/trabajos/finanzas/v2](http://127.0.0.1:8080/dashboard/trabajos/finanzas/v2) | No equivale a Finance Invoice; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-09 | Órdenes de ejecución | 3 | Consulta de órdenes de ejecución y su vista de almacén, con superficie de finalización. | [http://127.0.0.1:8080/dashboard/ordenes/ejecución](http://127.0.0.1:8080/dashboard/ordenes/ejecución) | Sin sustitución moderna equivalente demostrada; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |

## Auxiliares

| ID | Submódulo | Superficies | Descripción funcional | URL principal | Dependencia o sustitución conocida | Decisión funcional |
|---|---|---:|---|---|---|---|
| C-01 | Caja legacy por tipo | 1 | Consulta del registro de caja legacy por tipo. | `http://127.0.0.1:8080/dashboard/ver/caja/{type}` (template) | CashMovement/CashSession modernos; equivalencia del registro legacy no probada; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-02 | Egresos de proveedores | 1 | Consulta de egresos de proveedores en un reporte de fuentes mixtas. | [http://127.0.0.1:8080/dashboard/egresos/proveedores/v2](http://127.0.0.1:8080/dashboard/egresos/proveedores/v2) | SupplierCredit no prueba equivalencia del reporte mixto; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-03 | Reembolsos | 3 | Registro, edición y consulta de reembolsos. | [http://127.0.0.1:8080/dashboard/reembolsos](http://127.0.0.1:8080/dashboard/reembolsos) | Flujo Reembolso separado de Cash; equivalencia no demostrada; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-04 | Registro/verificación facial | 2 | Registro y verificación facial; el uso operativo de biometría está por confirmar. | [http://127.0.0.1:8080/dashboard/faces](http://127.0.0.1:8080/dashboard/faces) | Sin sustitución moderna localizada; equivalencia/sustitución: Por determinar | PENDIENTE DE REUNIÓN FUNCIONAL |
| C-05 | Rendición: lista/editar/reporte | 3 | Listado, edición y reporte de rendiciones de gastos. | [http://127.0.0.1:8080/dashboard/rendicion/gastos/general](http://127.0.0.1:8080/dashboard/rendicion/gastos/general) | Bill es sólo catálogo; NO reemplaza Rendición | PENDIENTE DE REUNIÓN FUNCIONAL |

## Anexo compacto — trazabilidad completa

Se conservan las 125 filas C del inventario, agrupadas sin altas ni bajas. Incluye URL y route name originales; las rutas con parámetros son templates, no enlaces de QA. La advertencia por grupo reproduce evidencia existente, no una decisión nueva.

### C-01 — Auxiliares / Caja legacy por tipo

**Advertencia existente:** Cash antiguo no se retira sólo por existir nuevo.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S038 | http://127.0.0.1:8080/dashboard/ver/caja/{type} — `index.cashRegister` | `cashRegister.index` |

### C-02 — Auxiliares / Egresos de proveedores

**Advertencia existente:** PUR-01 + OrderService legacy; capacidades compartidas por resolver.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S113 | http://127.0.0.1:8080/dashboard/egresos/proveedores/v2 — `expenses.supplier.index` | `expenseSupplier.index_v2` |

### C-03 — Auxiliares / Reembolsos

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S286 | http://127.0.0.1:8080/dashboard/crear/reembolso — `refund.create` | `refund.create` |
| R8-S287 | http://127.0.0.1:8080/dashboard/editar/reembolso/{id} — `refund.edit` | `refund.edit` |
| R8-S288 | http://127.0.0.1:8080/dashboard/reembolsos — `refund.index` | `refund.index` |

### C-04 — Auxiliares / Registro/verificación facial

**Advertencia existente:** Sin evidencia específica de vigencia/retiro.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S114 | http://127.0.0.1:8080/dashboard/faces — `faces.index` | `faces.index` |
| R8-S115 | http://127.0.0.1:8080/dashboard/faces/verify — `faces.verify` | `faces.verify` |

### C-05 — Auxiliares / Rendición: lista/editar/reporte

**Advertencia existente:** EXPENSE-01 OPEN; link retirado S109/R8-F01.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S110 | http://127.0.0.1:8080/dashboard/editar/gasto/{id} — `expense.edit` | `expense.edit` |
| R8-S111 | http://127.0.0.1:8080/dashboard/rendicion/gastos/general — `expense.index` | `expense.index` |
| R8-S112 | http://127.0.0.1:8080/dashboard/reporte/rendicion/gastos — `expense.report` | `expense.report` |

### C-06 — Ejecución/cronogramas / Compras normales: vistas legacy alternativas

**Advertencia existente:** Normal sigue ACTIVE; exclusión General/Express/Finance no aplica.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S195 | http://127.0.0.1:8080/dashboard/ordenes/compra/perdidas — `order.purchase.list.lost` | `orderPurchase.indexLost` |
| R8-S196 | http://127.0.0.1:8080/dashboard/ordenes/compra/normal — `order.purchase.normal.index` | `orderPurchase.indexNormal` |
| R8-S197 | http://127.0.0.1:8080/dashboard/ordenes/compra/regularizadas — `order.purchase.list.regularize` | `orderPurchase.indexRegularize` |
| R8-S199 | http://127.0.0.1:8080/dashboard/regularizar/automaticamente/entrada/compra/{entry_id} — `(sin nombre)` | `orderPurchase.regularizeEntryPurchase` |

### C-07 — Ejecución/cronogramas / Cronogramas y avances

**Advertencia existente:** Link index.timelines comentado en appAdmin2; ocultación no demuestra retiro.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S335 | http://127.0.0.1:8080/dashboard/crear/cronograma/{timeline} — `create.timeline` | `timeline.create` |
| R8-S336 | http://127.0.0.1:8080/dashboard/cronogramas — `index.timelines` | `timeline.index` |
| R8-S337 | http://127.0.0.1:8080/dashboard/gestionar/cronograma/{timeline} — `manage.timeline` | `timeline.manage` |
| R8-S338 | http://127.0.0.1:8080/dashboard/registrar/avances/cronograma/{timeline} — `register.progress` | `timeline.progress` |
| R8-S339 | http://127.0.0.1:8080/dashboard/revisar/cronograma/{timeline} — `review.timeline` | `timeline.review` |
| R8-S340 | http://127.0.0.1:8080/dashboard/revisar/avances/cronograma/{timeline} — `save.progress` | `timeline.saveProgress` |
| R8-S341 | http://127.0.0.1:8080/dashboard/ver/cronograma/{timeline} — `show.timeline` | `timeline.show` |

### C-08 — Ejecución/cronogramas / Trabajos y finanzas

**Advertencia existente:** Dependencias mixtas, retiro específico no demostrado.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S119 | http://127.0.0.1:8080/dashboard/trabajos/finanzas/v2 — `finance.works.index` | `financeWork.index_v2` |

### C-09 — Ejecución/cronogramas / Órdenes de ejecución

**Advertencia existente:** Industrial/ejecución relacionado; pausa específica no probada para todo el grupo.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S183 | http://127.0.0.1:8080/dashboard/ordenes/ejecución/finalizadas — `order.execution.finish` | `orderExecution.finish` |
| R8-S184 | http://127.0.0.1:8080/dashboard/ordenes/ejecución — `order.execution.index` | `orderExecution.index` |
| R8-S185 | http://127.0.0.1:8080/dashboard/materiales/ordenes/ejecución — `order.execution.almacen` | `orderExecution.indexAlmacen` |

### C-10 — Entradas/salidas / Entradas inventario/retacería y reportes legacy

**Advertencia existente:** INV-01 conocido; no retirar Entradas por asociación legacy.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S090 | http://127.0.0.1:8080/dashboard/crear/entrada/inventario — `entry.inventory.create` | `entry.create_entry_inventory` |
| R8-S093 | http://127.0.0.1:8080/dashboard/crear/entrada/retacería — `entry.scrap.create` | `entry.create_entry_scrap` |
| R8-S094 | http://127.0.0.1:8080/dashboard/entrada/inventario/editar/{entry} — `entry.inventory.edit` | `entry.edit_entry_inventory` |
| R8-S097 | http://127.0.0.1:8080/dashboard/entradas/inventario — `entry.inventory.index` | `entry.index_entry_inventory` |
| R8-S098 | http://127.0.0.1:8080/dashboard/entradas/compra — `entry.purchase.index` | `entry.index_entry_purchase` |
| R8-S101 | http://127.0.0.1:8080/dashboard/entradas/retaceria — `entry.scrap.index` | `entry.index_entry_scrap` |
| R8-S102 | http://127.0.0.1:8080/dashboard/entradas/compra/ordenes — `order.purchase.list` | `entry.listOrderPurchase` |
| R8-S104 | http://127.0.0.1:8080/dashboard/reporte/material/ingresos — `report.materials.entries` | `entry.reportMaterialEntries` |

### C-11 — Entradas/salidas / Solicitudes y salidas simples / áreas / activos

**Advertencia existente:** Pausa output legacy documentada; revisar alcance exacto, no extender a otros dominios.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S208 | http://127.0.0.1:8080/dashboard/crear/solicitud/area — `output.simple.create` | `output.create_output_simple` |
| R8-S209 | http://127.0.0.1:8080/dashboard/crear/solicitud/area/activos — `output.simple.create.activos` | `output.create_output_simple_activos` |
| R8-S210 | http://127.0.0.1:8080/dashboard/mis/solicitudes/area — `output.simple.my.index` | `output.index_my_output_simple` |
| R8-S216 | http://127.0.0.1:8080/dashboard/solicitudes/area — `output.simple.index` | `output.index_output_simple` |
| R8-S219 | http://127.0.0.1:8080/dashboard/reporte/material/salidas/xarea — `output.simple.reportByArea` | `output.reportMaterialOutputsAreaSimple` |
| R8-S220 | http://127.0.0.1:8080/dashboard/reporte/material/salidas/area — `output.simple.report` | `output.reportMaterialOutputsSimple` |

### C-12 — Inventario legacy / Combos

**Advertencia existente:** Sin decisión específica localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S056 | http://127.0.0.1:8080/dashboard/generar/combos/materiales — `material.generate.combo` | `combo.generateCombo` |
| R8-S057 | http://127.0.0.1:8080/dashboard/listado/combos/materiales — `index.combos` | `combo.index_combo` |

### C-13 — Inventario legacy / Equipos predeterminados por categoría

**Advertencia existente:** Cotización industrial pausada; no implica retiro de catálogo compartido.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S084 | http://127.0.0.1:8080/dashboard/equipos/categoria/{category_id}/crear — `defaultEquipment.create` | `defaultEquipment.create` |
| R8-S085 | http://127.0.0.1:8080/dashboard/editar/equipo/categoria/{equipment_id} — `defaultEquipment.edit` | `defaultEquipment.edit` |
| R8-S086 | http://127.0.0.1:8080/dashboard/equipos/categoria/{category_id} — `defaultEquipment.index` | `defaultEquipment.index` |

### C-14 — Inventario legacy / Materiales / activos / tienda / paquetes legacy

**Advertencia existente:** Hay rutas/materiales legacy ocultos; no evidencia de retiro uniforme de9 vistas.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S159 | http://127.0.0.1:8080/dashboard/habilitar/materiales — `material.index.enable` | `material.enable` |
| R8-S160 | http://127.0.0.1:8080/dashboard/materiales — `material.index` | `material.index` |
| R8-S161 | http://127.0.0.1:8080/dashboard/materiales/activos/fijos — `invoice.materials.fijos` | `material.indexActivosFijos` |
| R8-S162 | http://127.0.0.1:8080/dashboard/listado/materiales/tienda — `material.index.store` | `material.indexStore` |
| R8-S164 | http://127.0.0.1:8080/dashboard/view/material/items/{id} — `material.getItems`<br>http://127.0.0.1:8080/dashboard/view/material/activo/items/{id} — `material.getItems.active` | `material.items` |
| R8-S165 | http://127.0.0.1:8080/dashboard/view/material/activo/items/{id} — `material.getItems.active` | `material.itemsActivos` |
| R8-S166 | http://127.0.0.1:8080/dashboard/listar/materiales/activos/fijos — `material.actives.index` | `material.listarActivosIndex` |
| R8-S167 | http://127.0.0.1:8080/dashboard/enviar/material/a/tienda/{material_id} — `(sin nombre)` | `material.sendToStore` |
| R8-S168 | http://127.0.0.1:8080/dashboard/separar/paquetes/materiales — `material.separate.pack` | `material.separatePack` |

### C-15 — Inventario legacy / Navegador de estructura física legacy

**Advertencia existente:** INV-01/WAREHOUSE deuda conocida; no retiro completo probado.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S132 | http://127.0.0.1:8080/dashboard/areas — `area.index` | `inventory.areas` |
| R8-S133 | http://127.0.0.1:8080/dashboard/ver/contenedores/nivel/{niveles}/anaquel/{anaqueles}/almacen/{almacen}/area/{area} — `container.index` | `inventory.containers` |
| R8-S134 | http://127.0.0.1:8080/dashboard/ver/items/material/{material_id}/ubicacion/{location_id} — `location.getItemsMaterial` | `inventory.items` |
| R8-S136 | http://127.0.0.1:8080/dashboard/ver/niveles/anaquel/{anaquel}/almacen/{almacen}/area/{area} — `level.index` | `inventory.levels` |
| R8-S137 | http://127.0.0.1:8080/dashboard/listado/de/inventario/fisico — `inventory.index` | `inventory.list` |
| R8-S138 | http://127.0.0.1:8080/dashboard/ubicaciones — `location.index` | `inventory.locations` |
| R8-S139 | http://127.0.0.1:8080/dashboard/ver/materiales/ubicacion/{location_id} — `location.getMaterialsLocation` | `inventory.materials` |
| R8-S140 | http://127.0.0.1:8080/dashboard/ver/posiciones/contenedor/{contenedor}/nivel/{niveles}/anaquel/{anaqueles}/almacen/{almacen}/area/{area} — `position.index` | `inventory.positions` |
| R8-S141 | http://127.0.0.1:8080/dashboard/ver/anaqueles/almacen/{almacen}/area/{area} — `shelf.index` | `inventory.shelves` |
| R8-S142 | http://127.0.0.1:8080/dashboard/ver/estructura/almacen/{warehouse_id} — `(sin nombre)` | `inventory.warehouse_visual` |
| R8-S143 | http://127.0.0.1:8080/dashboard/ver/almacenes/{area} — `warehouse.index` | `inventory.warehouses` |

### C-16 — Inventario legacy / Retacería e Items

**Advertencia existente:** Sin decisión específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S303 | http://127.0.0.1:8080/dashboard/ver/items/material/{material_id} — `(sin nombre)` | `scraps.index_items_material` |
| R8-S304 | http://127.0.0.1:8080/dashboard/crear/retazos/materiales — `entry.create.scrap` | `scraps.index_materials_scrap` |

### C-17 — RRHH / Asistencia y reportes de horas/pagos

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S011 | http://127.0.0.1:8080/dashboard/ver/horas/diarias — `assistance.show.hour.diary` | `assistance.hourDiary` |
| R8-S012 | http://127.0.0.1:8080/dashboard/calendario/asistencia — `assistance.index` | `assistance.index` |
| R8-S013 | http://127.0.0.1:8080/dashboard/registrar/asistencia/{assistance} — `assistance.register` | `assistance.register` |
| R8-S014 | http://127.0.0.1:8080/dashboard/ver/asistencias — `assistance.show` | `assistance.show` |
| R8-S015 | http://127.0.0.1:8080/dashboard/ver/total/bruto — `assistance.show.total.bruto` | `assistance.totalBruto` |
| R8-S016 | http://127.0.0.1:8080/dashboard/ver/total/horas — `assistance.show.total.hours` | `assistance.totalHours` |
| R8-S017 | http://127.0.0.1:8080/dashboard/ver/total/pagar — `assistance.show.total.pays` | `assistance.totalPays` |
| R8-S018 | http://127.0.0.1:8080/dashboard/ver/total/pagar/finanzas — `assistance.show.total.pays.accounts` | `assistance.totalPaysAccounts` |

### C-18 — RRHH / Boletas de personal

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S022 | http://127.0.0.1:8080/dashboard/generar/boleta/trabajadores — `paySlip.create` | `boleta.createByWorker` |
| R8-S023 | http://127.0.0.1:8080/dashboard/boletas/pago/trabajadores — `paySlip.index` | `boleta.index` |
| R8-S024 | http://127.0.0.1:8080/dashboard/ver/boletas/semanales/{worker_id} — `paySlip.boletas.semanales` | `boleta.indexBoletaWorker` |
| R8-S025 | http://127.0.0.1:8080/dashboard/ver/boleta/semanal/{boleta_id} — `paySlip.show.semanal` | `boleta.showBoleta` |

### C-19 — RRHH / Bonos especiales

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S026 | http://127.0.0.1:8080/dashboard/crear/bono/especial — `bonusRisk.create` | `bonus.create` |
| R8-S027 | http://127.0.0.1:8080/dashboard/editar/bono/especial/{id} — `bonusRisk.edit` | `bonus.edit` |
| R8-S028 | http://127.0.0.1:8080/dashboard/bonos/especiales — `bonusRisk.index` | `bonus.index` |
| R8-S029 | http://127.0.0.1:8080/dashboard/reporte/bonos/especiales — `bonusRisk.report` | `bonus.report` |

### C-20 — RRHH / Contratos y renovaciones

**Advertencia existente:** No retiro específico; contrato histórico have_contract consumido.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S070 | http://127.0.0.1:8080/dashboard/crear/contrato/{worker_id} — `contract.create` | `contract.create` |
| R8-S071 | http://127.0.0.1:8080/dashboard/editar/contrato/{id} — `contract.edit` | `contract.edit` |
| R8-S072 | http://127.0.0.1:8080/dashboard/contratos — `contract.index` | `contract.index` |
| R8-S073 | http://127.0.0.1:8080/dashboard/contratos/eliminados — `contract.deleted` | `contract.indexDeleted` |
| R8-S074 | http://127.0.0.1:8080/dashboard/renovar/contrato/{worker_id} — `contract.renew` | `contract.renew` |

### C-21 — RRHH / Cuentas del trabajador

**Advertencia existente:** No retiro específico; navegación heredada activa.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S368 | http://127.0.0.1:8080/dashboard/registrar/cuentas/trabajador/{worker_id} — `worker.accounts.index` | `workerAccount.index` |

### C-22 — RRHH / Descansos médicos

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S176 | http://127.0.0.1:8080/dashboard/crear/descanso/medico — `medicalRest.create` | `medicalRest.create` |
| R8-S177 | http://127.0.0.1:8080/dashboard/editar/descanso/medico/{id} — `medicalRest.edit` | `medicalRest.edit` |
| R8-S178 | http://127.0.0.1:8080/dashboard/descansos/medicos — `medicalRest.index` | `medicalRest.index` |

### C-23 — RRHH / Descuentos

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S087 | http://127.0.0.1:8080/dashboard/crear/descuento — `discount.create` | `discount.create` |
| R8-S088 | http://127.0.0.1:8080/dashboard/editar/descuento/{id} — `discount.edit` | `discount.edit` |
| R8-S089 | http://127.0.0.1:8080/dashboard/descuentos — `discount.index` | `discount.index` |

### C-24 — RRHH / Gratificaciones

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S127 | http://127.0.0.1:8080/dashboard/crear/gratificacion/{period} — `gratification.create` | `gratification.create` |
| R8-S128 | http://127.0.0.1:8080/dashboard/gratificaciones — `gratification.index` | `gratification.index` |

### C-25 — RRHH / Jornada de trabajo

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S374 | http://127.0.0.1:8080/dashboard/registrar/jornadas/trabajo — `workingDay.create` | `workingDay.create` |

### C-26 — RRHH / Licencia sin goce

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S349 | http://127.0.0.1:8080/dashboard/crear/licencia/sin/gozo — `unpaidLicense.create` | `unpaidLicense.create` |
| R8-S350 | http://127.0.0.1:8080/dashboard/editar/licencia/sin/gozo/{id} — `unpaidLicense.edit` | `unpaidLicense.edit` |
| R8-S351 | http://127.0.0.1:8080/dashboard/licencias/sin/gozo — `unpaidLicense.index` | `unpaidLicense.index` |

### C-27 — RRHH / Licencias

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S151 | http://127.0.0.1:8080/dashboard/crear/licencia — `license.create` | `license.create` |
| R8-S152 | http://127.0.0.1:8080/dashboard/editar/licencia/{id} — `license.edit` | `license.edit` |
| R8-S153 | http://127.0.0.1:8080/dashboard/licencias — `license.index` | `license.index` |

### C-28 — RRHH / Pagos al personal

**Advertencia existente:** DASH-R7-01/METRIC-01 relacionados; no ejecutar GET escritor.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S236 | http://127.0.0.1:8080/dashboard/pagos/al/personal — `personal.payments.index` | `personalPayment.list` |

### C-29 — RRHH / Parentescos eliminados

**Advertencia existente:** HR-02: Blade indexDeleted faltante; no nueva pérdida Next.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S375 | http://127.0.0.1:8080/dashboard/parentescos/eliminados — `relationship.deleted` | `relationship.indexDeleted` |

### C-30 — RRHH / Pensiones alimenticias

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S006 | http://127.0.0.1:8080/dashboard/ver/pension/alimentos/{worker} — `alimony.create` | `alimony.create` |
| R8-S007 | http://127.0.0.1:8080/dashboard/pension/alimentos — `alimony.index` | `alimony.index` |

### C-31 — RRHH / Permisos

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S230 | http://127.0.0.1:8080/dashboard/crear/permiso — `permit.create` | `permit.create` |
| R8-S231 | http://127.0.0.1:8080/dashboard/editar/permiso/{id} — `permit.edit` | `permit.edit` |
| R8-S232 | http://127.0.0.1:8080/dashboard/permisos/trabajadores — `permit.index` | `permit.index` |

### C-32 — RRHH / Permisos por hora

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S233 | http://127.0.0.1:8080/dashboard/crear/permisoxhora — `permit_hour.create` | `permithour.create` |
| R8-S234 | http://127.0.0.1:8080/dashboard/editar/permisoxhora/{id} — `permit_hour.edit` | `permithour.edit` |
| R8-S235 | http://127.0.0.1:8080/dashboard/permisosxhora/trabajadores — `permit_hour.index` | `permithour.index` |

### C-33 — RRHH / Préstamos al personal

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S154 | http://127.0.0.1:8080/dashboard/crear/prestamo — `loan.create` | `loan.create` |
| R8-S155 | http://127.0.0.1:8080/dashboard/editar/prestamo/{id} — `loan.edit` | `loan.edit` |
| R8-S156 | http://127.0.0.1:8080/dashboard/prestamos — `loan.index` | `loan.index` |

### C-34 — RRHH / Renta de quinta categoría

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S116 | http://127.0.0.1:8080/dashboard/crear/renta/quinta/categoria/{worker} — `fifthCategory.create` | `fifthCategory.create` |
| R8-S117 | http://127.0.0.1:8080/dashboard/renta/quinta/categoria — `fifthCategory.index` | `fifthCategory.index` |

### C-35 — RRHH / Régimen de trabajo

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S289 | http://127.0.0.1:8080/dashboard/registrar/regimenes/trabajo — `regime.create` | `regime.create` |

### C-36 — RRHH / Suspensiones

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S320 | http://127.0.0.1:8080/dashboard/crear/suspension — `suspension.create` | `suspension.create` |
| R8-S321 | http://127.0.0.1:8080/dashboard/editar/suspension/{id} — `suspension.edit` | `suspension.edit` |
| R8-S322 | http://127.0.0.1:8080/dashboard/suspensiones — `suspension.index` | `suspension.index` |

### C-37 — RRHH / Vacaciones

**Advertencia existente:** Sin decisión DEV específica de retiro localizada.

| ID R8 | URL / route name | Vista |
|---|---|---|
| R8-S354 | http://127.0.0.1:8080/dashboard/crear/vacaciones — `vacation.create` | `vacation.create` |
| R8-S355 | http://127.0.0.1:8080/dashboard/editar/vacaciones/{id} — `vacation.edit` | `vacation.edit` |
| R8-S356 | http://127.0.0.1:8080/dashboard/vacaciones — `vacation.index` | `vacation.index` |

## Cierre documental y fuentes

- Universo A: auditoría completada (194/194); no equivale a certificación de todas las mutaciones.
- Universo B: homologaciones completadas (2/2); observaciones Branch/GRE resueltas en R8.2D.
- Universo C: documentado (125/125), transferido a futura definición funcional; no certificado, retirado ni homologado por R8.4.
- Recuperaciones frontend directas: completadas. Handoff DEV: completado en R8.3; bloqueos pendientes de sus responsables.
- **76 recuperadas + 11 bloqueadas + 23 excluidas = 110.** Sin nuevas recuperaciones ni reclasificación funcional.
- **R8.4: DOCUMENTATION COMPLETE. R8.5: PENDING OWNER REVIEW / AUTHORIZATION.** No se inicia cierre final ni fase siguiente.
- Implementación, infraestructura, datos y mutaciones comerciales: **0 cambios**. Sin QA, navegación, commit, push o merge.

Fuentes: [Inventario y matriz de 37 submódulos](REAL-NEXT-R8-SEMANTIC-SURFACE-INVENTORY.md), [Auditoría](REAL-NEXT-R8-FUNCTIONAL-PARITY-AUDIT.md), [Plan vigente](REAL-NEXT-R8-RECOVERY-PLAN.md), [Handoff DEV R8.3](REAL-NEXT-DEV-HANDOFF-R8-CONSOLIDATED.md) y `AGENTS.md`. Prevalecen los addenda vigentes sobre los checkpoints históricos; se preserva su evidencia.
