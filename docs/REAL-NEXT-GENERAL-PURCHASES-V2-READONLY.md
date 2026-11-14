# REAL NEXT — Compras General V2 — Read-only Integration

2026-11-13. **FRONTEND INTEGRATED — READ PATH**. Backend UNCHANGED; 0 CSS nuevo. Reutilización con adaptación contractual de Normal Purchases, Entries V2, Finance Invoices y Advanced Operational List aprobado. No se integraron nuevas superficies Express/Finance/recepción.

## Routes

Prefijo `/dashboard/`; controlador `OrderPurchaseController`. Todas pasan por auth, check.user.enabled, password.changed y tenant.context.

| Route | Action | Permission | Tenant | Company | Read/Mutation |
|---|---|---|---|---|---|
| GET `listado/general/ordenes/compra/v2`; nombre `order.purchase.general.indexV2` | indexV2 | list_orderPurchaseExpress | Sí | Sí | Read |
| GET `get/data/orders/general/v2/{numberPage}`; sin nombre | getDataOrderGeneral | list_orderPurchaseExpress | Sí | Sí | Read |
| GET `ver/orden/compra/normal/{id}`; sin nombre | showOrderPurchaseNormal | list_orderPurchaseNormal | Sí | Sí | Read |

Lookups año/proveedores/tipo/estado vienen del action de vista, sin endpoint auxiliar nuevo. Allowlist: solo `order.purchase.general.indexV2` añadida. **ALLOWLIST MATCH: YES**; navegador carga `venti.min.css` y `venti-next.css`. La allowlist previa del detalle Normal permanece intacta.

## Permissions

General conserva `list_orderPurchaseExpress`, aunque su nombre mencione Express. No se inventó un permiso General. Detalle Normal conserva su permiso independiente; sin él no se dibuja acción. No se agregó bypass Owner. QA navegador: Owner EDESCE y Owner Gamarra, ambos con acceso real al listado; no se modificaron roles/permisos. La denegación para cuentas sin permiso se revisó en middleware, no se certificó con una tercera cuenta.

## Scope

`OrderPurchase::ScopedPurchaseCompany` aplica tenant_id + company_id; contexto ausente impone `1=0`. SoftDeletes excluye eliminadas. Supplier es maestro tenant-scoped, no se filtra por company desde JS. createdUser/approved_user restringen tenant. **BRANCH — NOT APPLICABLE / COMPANY LEVEL**.

InitializeTenantContext rechaza contexto completo inválido con 403 y resuelve contexto autorizado cuando falta. La prueba directa del action sin contexto devuelve vacío; no equivale a afirmar que HTTP siempre devuelve vacío ante una sesión inválida.

## Included Types

General NO impone `whereIn(type,[n,e])`, ni filtro inicial por estado, ni condición Finance. Lee todas las órdenes no eliminadas de la company; los filtros opcionales acotan type/status_order. No consulta OrderPurchaseFinance como catálogo adicional, pero tampoco existe exclusión explícita de órdenes por estado financiero. No se declara “General = Normal + Express”.

El serializador solo reconoce `n` (Orden Normal) y `e` (Orden Express); otros tipos quedan con type/typeText vacíos. Se conserva esta limitación sin inferir por código. Dataset actual únicamente `n`.

## Dataset

Revalidado mediante SELECT en transacción MySQL READ ONLY, invocación del action actual con sesión en memoria y ROLLBACK. Sin insertar datos. Complementado con sesiones HTTP reales.

| Contexto | IDs completos por date_order DESC | Evidencia |
|---|---|---|
| EDESCE T1/C1 | `[4,3,2]` | Action + navegador Owner EDESCE, Local Principal B1 |
| EDESCE T1/C2 | `[]` | Action + navegador, NEGOCIO DE PRUEBA / Tienda Prueba 1 |
| EDESCE T1/C5 | `[]` | Action con contexto en memoria |
| Gamarra T3/C3 | `[]` | Action + navegador Owner Gamarra / Local Principal |
| Contexto incompatible T1/C3 | `[]` | Action; no concesión de acceso a company ajena |
| Sin tenant/company | `[]` | Action, fail-closed del scope |

Cuatro órdenes físicas T1/C1: ID1 soft-deleted; visibles ID2 stand_by, ID3/4 pick_up. ID4 actor Owner EDESCE; ID2/3 presentan el fallback backend «No registrado / usuario no disponible». Totales entregados: ID4 PEN 2400.00; ID3 PEN 1130.00; ID2 PEN 800.00. No se recalculan ni convierten.

Manipulación `tenant_id=3&company_id=3` en request con contexto T1/C1 sigue devolviendo `[4,3,2]`; el action ignora esas claves. Guard del detalle ID4: T1/C1 devuelve 4, T1/C2 y T3/C3 producen ModelNotFound/404. Prueba de guard en proceso, sin abrir documentos ni ejecutar Express.

## Filters

| UI / query real | Semántica | Resultado probado |
|---|---|---|
| year | año date_order | 2026 `[4,3,2]`; 1990 `[]` |
| code | LIKE código | OC-00004 `[4]`; NO-MATCH `[]` |
| quote | LIKE observation | NO-MATCH `[]` |
| supplier | relación proveedor contextual | ID inexistente `[]`; lookup tenant en vista |
| type | igualdad type | n `[4,3,2]`; e `[]` |
| state | igualdad status_order | stand_by `[2]`; pick_up `[4,3]`; send `[]` |
| deliveryDate | date_arrival, d/m/Y | 24/05/2026 `[2]` |
| startDate/endDate | date_order inclusivo si ambos están presentes | 16/10/2026–16/10/2026 `[4]` |

IDs `start/end` y names originales preservados; AJAX usa startDate/endDate. Sin pareja de fechas el backend omite ese rango. No se cambió por created_at. Búsqueda inicia página 1; paginación conserva los controles. Peticiones anteriores se abortan y se descartan respuestas obsoletas. Fallos HTTP muestran error, no empty state. Navegador confirmó filtro Express y búsqueda sin resultados.

## Columns

ID, Código, Fecha Orden, Fecha Llegada, Observación, Proveedor, Creado por, Moneda, Total, Tipo, Estado, Acciones: las 12 originales. Texto de datos se inserta con textContent; typeText/stateText conservan HTML fijo del backend. No se inventaron almacén, cantidades, recepción o situación financiera. Identificadores/fechas/estado/tipo/acciones centrados; descripciones izquierda; total derecha.

## States

`stand_by` → Pendiente; `send` → Enviado; `pick_up` → Recogido, exactamente según stateText. Otros valores no tienen etiqueta en este response; frontend no los convierte a “completo”. Se conserva el orden date_order DESC. Summary corrige la descripción legacy que decía fecha de creación.

## Actions

| Action | Route | Permission | Read/Mutation | Included |
|---|---|---|---|---|
| Ver Normal | GET ver/orden/compra/normal/{id} | list_orderPurchaseNormal | Read contextual | Sí, solo type n |
| Ver Express | GET ver/orden/compra/express/{id} | list_orderPurchaseExpress | GET con Audit::create y lookups legacy | No |
| Crear Normal/Express | crear/orden/compra/normal, crear/orden/compra/express | create_orderPurchaseNormal/Express | Entrada a flujo de escritura | No |
| Editar | editar/orden/compra/normal o express/{id} | update_orderPurchaseNormal/Express | Entrada a mutación | No |
| Anular | POST destroy/order/purchase/normal/{id}; express/{id} | destroy_orderPurchaseNormal; update_orderPurchaseExpress | Mutation | No |
| Estado | GET get/state/order/purchase/{id}; POST change/state/order/purchase | update_orderPurchaseNormal | Read auxiliar + Mutation | No |
| Imprimir | GET imprimir/orden/compra/{id} | contrato legacy de documentos; no certificado aquí | Documento | No |
| Exportar | GET exportar/reporte/ordenes/compra/v2/ | list_orderPurchaseExpress | Exportación | No |
| Restore/receive/regularize/Entry creation | Flujos ajenos al list read path | No auditados en esta fase | Mutation | No |

Se eliminaron de esta vista templates/modales/botones de escritura y del JS los handlers de mutación/export, además del bloque DataTables comentado ajeno al listado. Se conservaron endpoint, payload, response y render/paginación General; no se sustituyó su contrato por Normal.

## Detail Boundaries

Normal usa NormalPurchaseGuard::order, exige type n y scope company; usuarios auxiliares requieren pertenencia company activa. Link al detalle ya integrado, sin modificarlo. QA de límite por ID en guard, no una reauditoría del módulo.

**DETAIL EXPRESS — EXCLUDED / BACKEND SYNC OPEN**: showOrderPurchaseExpress carga User::all(), usa find seguido de acceso a id sin 404 explícito y crea Audit al consultar. No se abrió ni enlazó. No se certifica como read-only seguro solo por compartir modelo scoped.

## Responsive

320/375/390/576/768/992/1200/1440: documento sin overflow horizontal; scroll local de tabla cuando hace falta (ancho intrínseco observado ~1012px). Filtros abiertos, Select2 al 100%, input-group de fechas y acciones móviles con wrap. Desktop/móvil inspeccionados visualmente.

Se reutiliza `next-page-toolbar-context-only` de Entries para mantener el título operativo en móvil; `next-form-section`, `next-list-filters`, `next-list-summary`, `next-data-table` y dropdown de acciones. **0 CSS nuevo**.

Calendario bootstrap-datepicker con container `.wrapper` y orientation bottom como Normal; popup observado 4px debajo del input, Escape cierra. No se conserva el antiguo `top:100px!important`. Consola sin errores en el recorrido. Assets Venti cargados.

## Pagination

10 filas por página, currentPage/totalPages/startRecord/endRecord/totalFilteredRecords conservados. Página 1 y empty states comprobados. **MULTIPAGE QA — PENDING DATASET** (3 filas reales). Página 99 devuelve data vacía pero startRecord=981/endRecord=3; UI indica fuera de rango y ofrece página 1, sin inventar resultados. Se ajustó el enlace final legacy para usar totalPages, no la variable del bucle. No se crearon órdenes sintéticas.

## Backend Sync Notes

[OPEN] GENERAL-01 — metadatos fuera de rango, getDataOrderGeneral: backend debe validar/clamp o devolver rango coherente. Frontend can continue: Yes; navegación de recuperación sin modificar datos. Registrado en backlog DEV.

[OPEN] Detail Express: frontera descrita arriba. Frontend can continue: Yes, excluido. No cerrar por ausencia de dataset.

[OPEN] DOCUMENT SECURITY — BACKEND/INFRA OPEN; DOC-01 intacto. No abrir PDF ni exportar.

Backend UNCHANGED. Mutations OUT OF SCOPE. Tenant/company QA PASSED dentro de la matriz descrita; branch company-level. Filters QA PASSED para casos enumerados; sin certificación de validación de fechas malformadas. Sin suite extensa ni pruebas concurrentes.

Validación técnica: PHP 7.3 artisan view:cache, node --check generalV2.js y git diff --check. Archivos de aplicación: config/venti-next.php, general_v2.blade.php y generalV2.js; documentación canónica y addendum backlog. Sin commit.
