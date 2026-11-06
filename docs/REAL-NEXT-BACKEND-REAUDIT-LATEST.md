# REAL NEXT — Backend Differential Reaudit

Fecha: **2026-11-05** (America/Lima). Repositorio: `C:\wamp64\www\venti-adminlte-ui\aliexpress`. Rama: `feature/venti-next-frontend`. HEAD auditado: **`f367675`**. Working tree limpio al iniciar.

## Alcance y calidad de evidencia

**Actualización posterior a migraciones locales (2026-11-05): Sales List — READY FOR FRONTEND para integración de lectura.** El error SQL por `sales.created_by` está RESOLVED; se debía al esquema local desactualizado. `migrate:status` confirma las cinco migraciones relevantes en batch 142 y ambas columnas de actor existen. El detalle de la repetición de QA aparece en «Sales List — QA posterior a migraciones», que prevalece sobre las observaciones históricas de esquema ausente conservadas en este documento. No equivale a cierre productivo ni a aprobación de acciones financieras.

Reauditoría de código, contratos y diferencias; no implementación. Se leyeron AGENTS, la reclasificación anterior, contratos de Material/configuraciones, Cotizaciones, Dashboard, Platform Admin, Store Web y las rutas/servicios actuales de ventas, compras, cajas y traslados. Las conclusiones anteriores quedan sustituidas **únicamente para los subhallazgos identificados aquí**; se preservan los documentos históricos.

Base de código de la última auditoría: `20cea30` (2026-10-26). Su documentación quedó en `305abf2`. El delta funcional es **`305abf2..f367675`**, que incorpora `ec5f6dd` (2026-11-04, «CAmbios en backend ventas V1 y V2»); `f367675` es el merge del 2026-11-05. No atribuir fixes anteriores a este último commit. En `20cea30..HEAD` hay 133 archivos, incluidos seis documentos de la reclasificación; el delta funcional contra `305abf2` comprende 127 archivos.

La revisión estática demuestra conexiones entre rutas, middleware, requests y servicios; **no demuestra por sí sola que las migraciones estén aplicadas, que los permisos estén asignados o que las operaciones funcionen con datos reales**. Los cambios transaccionales pasan a IMPLEMENTED — QA PENDING cuando corresponde, no a RESOLVED por existir un servicio.

QA local: inicialmente MySQL rechazó conexión y Windows rechazó iniciar el servicio. Después de que el usuario inició Wamp, se retomó el harness externo bajo `START TRANSACTION READ ONLY` y `ROLLBACK` final. Se ejecutaron servicios/controllers con las identidades QA Owner EDESCE, Owner Gamarra y Platform Admin, usando Auth en memoria y contextos de membresías existentes; **no fue login HTTP ni QA de navegador**. Se confirmó un bloqueo de esquema local en Sales List y se obtuvieron lecturas reales descritas abajo. No se reutiliza el QA de octubre como evidencia de este HEAD. No se crearon ni modificaron registros de negocio, ni se ejecutaron migraciones, emisión electrónica, impresión con efectos sobre archivos o guardados.

Verificación disponible: **87 archivos PHP cambiados** pasan `php7.3.33 -l`; **16 JS cambiados** pasan `node --check`. Esto verifica sintaxis, no comportamiento ni paridad visual. Se modificó exclusivamente este informe dentro del repositorio.

## BACKEND CHANGES SINCE LAST AUDIT

Prefijos: controllers en `app/Http/Controllers/`; requests en `app/Http/Requests/`; servicios en `app/Services/`; modelos en `app/`. Tabla agrupada por cambios coherentes; el inventario exacto de archivos se reproduce al final.

| Archivo | Cambio | Dominio | Impacto potencial frontend |
| --- | --- | --- | --- |
| `MaterialController.php` | Update deja de asignar unit_price/list_price; aplica parámetros; scopes y snapshots de inventario; listado y resumen usan read model | Material/Edit/List | Retira blockers específicos; cambia indicadores, estados y mensajes |
| `StoreMaterialRequest.php`, `UpdateMaterialRequest.php` | Reglas según configuración; jerarquía efectiva; validación de contexto y niveles | Material/variantes | Formularios antiguos pueden recibir 422; preservar snapshots |
| `Materials/MaterialParameters.php`, `MaterialDetailSettingController.php` | Unidad obligatoria, dependencias, configuración inicial explícita, versionado, preservación de campos deshabilitados | Parámetros | Nueva pantalla 409 y formularios versionados |
| `Materials/MaterialInventoryScope.php`, `MaterialInventoryGuard.php` | Company + branch activa + general; coherencia warehouse/location/StockItem; min/max bajo locks | Inventory | No reconstruir filtros desde JS; QA de cambio de contexto |
| `Materials/MaterialCompanyCatalog.php`, `MaterialStockReadModel.php` | Habilitación company; fuentes comunes; stock local separado de general; min/max no sumados | List V2 | Nuevos DTO y semántica, no equivalencia ciega con columnas legacy |
| `StockItemController.php`, `Materials/StockItemScreenAccess.php`, `StockItemScreenGuard.php` | Acceso contextual a StockItems/Items; permisos y snapshots | StockItem | Reabrir QA de pantallas y acciones de Items |
| Controllers de once catálogos, `DeleteMultiple*Request.php`, `Materials/Delete*.php` | Borrado individual/bulk común; IDs completos, tenant, locks, dependencias históricas, rollback | Configuraciones | Botones bulk frontend siguen deshabilitados; ya hay backend para QA y activación posterior |
| `Material.php` | Relación talla apunta a Talla mediante talla_id | Material/variantes | Resuelve relación legacy a Quality; no elimina campo histórico |
| `PuntoVentaController.php`, `Sales/PosSaleService.php` | Store delega a transacción contextual/idempotente; emisión posterior al commit | POS | Nuevo contrato operativo y errores; no reutilizar store legacy |
| `Sales/PosCatalogReadService.php`, `PosAvailabilityController.php`, `PosSaleLookupController.php`, `PosCatalogGuard.php` | Catálogo de StockItems habilitados, inventario local, búsqueda y disponibilidad en otros locales | POS lectura | V1 StockItem / V2 Material + opciones; snapshots obligatorios |
| `Sales/PosLineCalculator.php`, `PosSalePriceReference.php`, `PosInventoryService.php`, `PosCashService.php` | Precio/tax en servidor, lotes/reservas, caja abierta propia, moneda/contexto | Ventas, inventario, caja | Payload ya no decide importes; QA transaccional imprescindible |
| `Sales/PosScreenOptions.php`, `PosSaleNotice.php`, `PosEmissionPayload.php` | Trabajador opcional, notificación y payload de emisión separados | POS | Actor y trabajador no son equivalentes; no volver a exigir worker por UI |
| `Sales/SaleListAccess.php`, `SaleListOptions.php`, `SaleListGuard.php` | Lista/años/creadores/detalle por tenant/company/branch y actor; permisos por acción | Sales List | Subconjunto de lectura ya candidato frontend |
| `SaleListDocumentController.php`, `SalePartialPaymentController.php` | Descarga autorizada; contexto en pagos; guards en rutas financieras | Ventas/documentos | No elimina exposición de archivos estáticos públicos; separar cierre de lectura/finanzas |
| `Branch.php`, `TenantBranchController.php`, requests de sucursal, `BranchPriceListService.php`, `PriceResolverService.php` | Lista activa por sucursal; fallback default company; rechazo de default ambiguo; precio efectivo | Pricing | Nueva configuración previa al POS; no inventar precio ausente |
| `MaterialPresentation.php`, `MaterialPresentationBranch.php`, controller y `Sales/BranchPresentationService.php` | Definición tenant y oferta con precio/moneda/estado por branch | Presentaciones | Flujo nuevo de mantenimiento, sin publicar automáticamente en Store Web |
| `CompanySettingController.php`, `CompanySettingImageService.php` | Upload validado, PNG, rutas tenant/company, preservación/limpieza de archivos | Branding | Nuevo input de imagen; mantiene base `images/logo/` |
| `EnsurePlatformAdmin.php`, `layouts/appAdmin2.blade.php` | Limpia TenantContext y retira permiso granular del menú laboral | Platform Admin | Decisión global implementada; soporte contextual sigue fuera de alcance |
| `routes/web.php`, `config/permissions_catalog.php` | Guards Material/POS/ventas y seis capacidades de Sales List | Permisos/middleware | Verificar sincronización/asignación en DB; config no equivale a concesión |
| `config/settings_catalog.php` | Worker opcional; tipos de input imagen | Settings | Definiciones persistidas requieren comprobar sincronización |
| Cuatro migraciones `2026_11_02_*` / `2026_11_03_*` | Lista branch; snapshot precio; idempotencia/tax; ofertas branch | Schema | No ejecutadas aquí; no hacen backfill de autor/company/branch de todas las ventas legacy |
| `public/js/material/*`, `resources/views/material/*`, `materialDetailSetting/*` | Snapshots, indicadores, presentaciones/contexto | Frontend ya alterado por backend | Reauditar compatibilidad con baseline congelado antes de cerrar pantallas |
| `public/js/puntoVenta/*`, vistas POS/list/error, `exports/salePdf2` | Catálogo, checkout contextual, acciones por permiso, branding | POS/Sales List | Contrato temporal ya actualizado; Venti Next no está allowlisted para estas rutas |
| `public/js/quoteSale/{create,edit,edit2}.js` | Ajustes en consumidores de catálogos/presentaciones | Cotizaciones | No cambia arrayUsers de general/facturadas |
| Workers/User, Transfers, Compras, Home/Graphs, StoreWebController | Sin delta desde la base auditada; revisados igualmente | Dominios sin cambio | No retirar sus findings por el título del commit de ventas |

## Tabla maestra

«Bloquea producción» se refiere al contrato/flujo afectado, no a todo el ERP. «No frontend» permite integrar el contrato actual sin aprobar su persistencia. Estados actuales usan las categorías solicitadas; OPEN de documentos antiguos se traduce a una de ellas.

| Módulo | Finding previo | Estado previo | Cambio encontrado | Estado actual | Impacto frontend | Bloquea frontend | Bloquea producción | QA requerido | Recomendación |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Material Edit | unit_price/list_price → 0 | Deuda, bloqueo Save | Retiradas ambas asignaciones en update | RESOLVED | No añadir precio | No | No por este defecto | Regresión de Save en entorno aislado | Cerrar defecto específico |
| Material Edit | Campos `$enabled` ausentes/deshabilitados | OPEN | MaterialParameters aplica sólo campos habilitados presentes; preserva resto | IMPLEMENTED — QA PENDING | Snapshots/version obligatorios | No | Hasta validar guardado | Ocultar/enviar/omitir campo; jerarquía efectiva | Reabrir QA |
| Material Edit | Descuentos borrados al omitir | OPEN histórico | Update actual no borra/reconstruye MaterialDiscountQuantity; store sí tiene contrato separado | RESOLVED | No añadir descuentos para compensar | No | No por ese supuesto | Regresión del update | Retirar blocker histórico incorrecto |
| Material Edit | type_tax_id ausente → null | OPEN histórico | Request requiere impuesto; Blade lo envía; nuevo inactivo rechazado, histórico propio aceptado | IMPLEMENTED — QA PENDING | Selector real preservado | No | Hasta QA de impuesto | Ausente, ajeno/inexistente, activo, histórico inactivo | No mantener afirmación de null silencioso por ruta validada |
| Material Edit | Tenant master vs inventory | Decisión definida, pendiente | Maestro sigue tenant; inventario ahora scoped en read/request/write | IMPLEMENTED — QA PENDING | Company + branch + general, sin excepción owner | No | Hasta QA de aislamiento | Principal/Outlet/general; IDs manipulados | Mantener decisión; validar implementación |
| Material Edit | Talla/color persistidos | IMPLEMENTED — QA PENDING | Guards request/controller conservados; request ahora tiene prerrequisitos adicionales | IMPLEMENTED — QA PENDING | Identidad readonly, nueva combinación nueva variante | No | Para cierre específico | Payload válido alterando sólo talla/color; no persistir aquí | Repetir QA del HEAD actual |
| Material Edit | Omitir variante | IMPLEMENTED — QA PENDING | Itera enviadas; no elimina omitidas | IMPLEMENTED — QA PENDING | Sin borrado implícito | No | Para cierre específico | Subset y alta nueva en DB aislada | No agrupar con SKU/estado |
| Material Edit | SKU histórico | Deuda | Unicidad/pertenencia siguen; sin política histórica nueva | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND | Errores servidor | No | Sí, cierre con historial | Movimientos existentes | Mantener deuda |
| Material Edit | Barcode histórico | Deuda | Sin política histórica nueva | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND | Mantener contrato | No | Sí, cierre con historial | Barcode en StockItems con movimientos | Mantener separado |
| Material Edit | Active/inactive histórico | Deuda | Persistencia actual continúa | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND | Sin locks UI inventados | No | Sí, cierre con historial | Transiciones y consumidores | Mantener separado |
| Material Edit | Inventariable histórico | Deuda | Sin política histórica nueva | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND | Mantener contrato | No | Sí, cierre con historial | Cambios con lotes/reservas | Mantener separado |
| Retacería | Propagación entered/exited | Intencional, revisión pendiente | Continúa por material/estado; no adopta filtro company/branch de niveles | INTENTIONAL BEHAVIOR — REVIEW PENDING | No impedir ni inventar warning | No | Sí, historia/alcance | Dimensiones originales y Items de otras companies | Mantener revisión pendiente |
| Material Create | Impuestos activos/default | OPEN histórico | TypeTax activo/global, default ordenado y seleccionado; request existente | IMPLEMENTED — QA PENDING | Catálogo plataforma, no tenant inventado | No | Hasta QA de configuración | Default, sin default, inactivo | Cerrar sólo después de QA |
| Material Create/Edit | Catálogos auxiliares/contexto | Parcial | Lookups con permisos Material OR catálogo; padres scoped; configuración versionada | IMPLEMENTED — QA PENDING | Lectura no exige permiso de mantenimiento | No | Hasta QA limitado/tenant | Usuario create-only/update-only y padre ajeno | Aún no RESOLVED agregado |
| Material List V2 | Fuentes stock/min/max/prioridad/resumen | Decisión definida, backend pendiente | Read model común, local/general separados, thresholds no sumados | IMPLEMENTED — QA PENDING | Nuevos campos y estados reales | No | Hasta QA de indicadores | Variantes/simple/sin niveles/múltiples ubicaciones | Prioridad de cierre |
| Material List V2 | Separar paquete | Legacy | Guardado sigue escribiendo materials.stock_current; relaciones unpack sin scope | STILL BLOCKED FOR FRONTEND | No ampliar ese subflujo | Sí, ese subflujo | Sí | Contrato inventario y autorización completo | Separarlo del cierre de lectura V2 |
| Configuración | Bulk/IDs ajenos/inexistentes | OPEN, UI deshabilitada | Once requests + servicios de borrado común; validación completa y locks | IMPLEMENTED — QA PENDING | Backend permite preparar reapertura de controles | No | Hasta QA de borrado | Rechazos; atomicidad/concurrencia en DB aislada | No activar durante auditoría |
| Configuración | Referencias históricas/cascadas | OPEN | Raw checks incluyen históricos; dependencias directas/transitivas; rollback | IMPLEMENTED — QA PENDING | Errores 422 reales | No | Hasta pruebas | Histórico soft-deleted, hijo referenciado, cascade failure | Reabrir QA de once catálogos |
| Tallas | Material::talla → Quality | OPEN | belongsTo Talla, talla_id | RESOLVED | Relación correcta | No | No por este defecto | Regresión de consumidores | Cerrar relación, no borrar columna |
| Parámetros | Autorización/menu/ruta | OPEN | config_materialSetting en GET/POST y menú; rechazo Platform sin contexto | IMPLEMENTED — QA PENDING | No granular extra de catálogos | No | Hasta QA de rol | Permitido/denegado, company ajena | Reabrir QA |
| Parámetros | Inicialización/array/disabled | Decisión pendiente | 409 sin config válida; unidad obligatoria; dependencias y versionado | IMPLEMENTED — QA PENDING | Nuevo estado requerido y rechazo stale | No | Hasta QA | Empresa nueva, array inválido, cambio paralelo | Decisión ya implementada, no pregunta abierta |
| Cotizaciones | Q-01 creadores globales | OPEN | index e indexFacturadas siguen User::select()->get() | BLOCKING FOR PRODUCTION | Fachada existente puede continuar | No para fachada; sí cierre | Sí | Catálogo disjunto entre contextos tras fix | No cerrar REAL NEXT 10 |
| Cotizaciones | Q-02 PDF global | OPEN | glob/unlink de public/pdfs continúa | BLOCKING FOR PRODUCTION | No ejecutar GET de impresión en QA readonly | Sí para ampliar impresión | Sí | Almacenamiento/descarga/limpieza aislados | Backend primero |
| Cotizaciones | Q-03/Q-04 relaciones/permisos | OPEN | Sin fix en controller/requests/rutas afectadas | BLOCKING FOR PRODUCTION | Lecturas existentes conservables | Sí para nuevos editores/conversión | Sí | Roles y IDs relacionados | No extrapolar SaleListGuard a quotes |
| Cotizaciones | Q-05 historia monetaria; Q-06 listas | OPEN | Sin delta; totales sigue apuntando a indexGeneral inexistente | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND | Listas existentes, no enlace totales | No para listas | Sí para contratos afectados | Moneda/tax históricos, fechas, vacío | Mantener deuda específica |
| Cotizaciones | Q-07 dataset/roles | QA incompleto | Sin QA nuevo disponible | IMPLEMENTED — QA PENDING | No certificar transiciones | No para fachada | Hasta QA | Datos branch-bound y roles | Conservar límite de evidencia |
| Sales List | Scope/lista/años/actor/detalle | STILL BLOCKED | SaleListAccess/Options/Guard conectados | IMPLEMENTED — QA PENDING | Lectura contextual lista para integrar | No, subconjunto lectura | Hasta QA | Dos tenants, companies, branches, otro actor, legacy nulos | READY FOR FRONTEND lectura |
| POS | Venta sin identidad/contexto fiables | STILL BLOCKED | PosSaleService asigna tenant/company/branch/created_by; worker opcional | IMPLEMENTED — QA PENDING | Checkout actual ya tiene contrato | No para integración por etapas | Sí hasta pruebas transaccionales | Idempotencia, concurrencia, rollback, actor sin worker | PARTIALLY READY módulo |
| POS | Precios/tax/stock/caja | OPEN | Servicios separados y snapshots, locks, importes servidor | IMPLEMENTED — QA PENDING | Nuevos errores y estados | No para catálogo/UX del contrato | Sí hasta QA integral | Moneda, reservas, caja propia, tax, pago parcial | No declarar cierre financiero |
| Venta libre | Flujo legacy fuera del checkout nuevo | Sin cierre contextual | FreeSaleController mantiene worker obligatorio y Sale::create sin company/branch/created_by explícitos | STILL BLOCKED FOR FRONTEND | No extender garantías de PosSaleService | Sí para ampliar este flujo | Sí | Contexto, actor, caja, impuestos y visibilidad posterior | Alinear backend antes de modernizar escritura |
| Ventas | Descargas públicas | Riesgo histórico | Nueva ruta protegida pero archivos/URLs aún en public | NEW FINDING | Descarga protegida no basta | No para lista; sí cierre documental | Sí | Acceso directo sin sesión y configuración servidor | Retirar publicación directa desde backend/infra |
| Dashboard | Tenant/company/branch agregados | OPEN | Sin delta de Home/Graphs; algunos modelos tenant-scoped, Meta/OrderService no | BLOCKING FOR PRODUCTION | Visual existente preservable | Sí nuevas métricas fiables | Sí | Todas las fuentes y exports | No llamarlo globalmente SAFE |
| Platform Admin | Menú laboral vs ruta | Defecto técnico | @can adicional retirado; platform.admin común | RESOLVED | Menú coherente | No | No por este defecto | Smoke de rol aún pendiente | Cerrar inconsistencia de código |
| Platform Admin | tenant histórico/capacidades | Decisión pendiente histórica | EnsurePlatformAdmin limpia contexto; laboral global no escribe tenant | RESOLVED | No exigir contexto ni permisos granulares | No | No por estos supuestos | Sesión con tenant residual | Decisión global definida |
| Store Web | SW-01…07 publicación | BLOCKED | Sin cambio de controller/bridge; mejoras compartidas no son contrato público | STILL BLOCKED FOR FRONTEND | Sólo preview/fachada existente | Sí binding público | Sí | Matriz SW inferior | STORE WEB PUBLICATION BLOCKED |
| Traslados | Madurez de ciclo/scope | Candidato | Access + state machine siguen presentes, sin delta nuevo | IMPLEMENTED — QA PENDING | Listado/detalle/flujo con contratos reales | No | Hasta QA transaccional | Origen/destino, partial receive, retries | Candidato prioritario |
| Cajas/subtipos | Multicompany | Implementado anteriormente | Mantenimiento company y relaciones contextualizadas; POS usa subconjunto local | IMPLEMENTED — QA PENDING | CRUD independiente de cierre POS | No | Hasta QA de mantenimiento | Roles, company ajena, sesiones abiertas | READY FOR FRONTEND |
| Compras normales | ScopedPurchaseCompany | Parcial | Trait/NormalPurchaseGuard continúan; sin nuevo delta | IMPLEMENTED — QA PENDING | Lecturas company candidatas | No para lectura company | Hasta QA; no prometer branch | Históricos, relaciones, IDs ajenos | Candidato acotado |
| Compras ampliadas | Solicitudes/financieras/branch | No certificado | RequestPurchase/OrderPurchaseFinance no comparten ese trait | STILL BLOCKED FOR FRONTEND | No heredar garantía de normal | Sí ampliación sin auditoría contractual | Sí | Ownership de cada familia | Esperar alineación |
| Presentaciones/precios branch | No contemplado | Nuevo | Ofertas por sucursal, moneda y snapshot de venta | IMPLEMENTED — QA PENDING | Nueva fase de mantenimiento | No | Hasta QA/migración | Branch cruzada, cambio precio, oferta inactiva | Incluir roadmap |

## Material Create y catálogos auxiliares

`MaterialController::create` carga TypeTax activos, ordenados por default/nombre; Blade selecciona `defaultTypeTax` mediante `old()`. TypeTax es catálogo global de plataforma, no una fuga tenant por carecer de TenantScope. Store valida impuesto existente/activo; la ausencia de un default no autoriza inventar uno en JS.

`/dashboard/get/types/{subcategory_id}`, `/get/subtypes/{type_id}`, `/get/subcategories/{category_id}`, `/get/exampler/{brand_id}` admiten create_material/update_material/list_material o el permiso propio de lectura. Controllers resuelven el padre con modelos scoped antes de consultar hijos. Los catálogos iniciales condicionados por `$enabled` se leen desde el permiso de Material; crear un auxiliar mantiene su permiso de alta. No se añaden permisos de mantenimiento a la lectura compuesta.

**Catálogos auxiliares y contexto: IMPLEMENTED — QA PENDING**, no RESOLVED global. Hay implementación suficiente, pero falta verificar un usuario real de permisos limitados, rechazo de padre de otro tenant, configuración válida/ausente y default fiscal. No se confunde lectura tenant de catálogo con habilitación company de productos.

## Material Edit — cierre de Save

**MATERIAL EDIT SAVE STILL BLOCKED** para aprobación productiva completa. Se retiran los motivos históricos que ya no aplican:

1. `update()` ya no asigna `unit_price` ni `list_price`; las apariciones de `input('unit_price',0)` restantes pertenecen a otros métodos, entre ellos store. El comentario de pricing legacy no es una escritura. El comparador del precio permanece, pero ya no cambia ese precio en update.
2. No hay reconstrucción de descuentos en el update vigente. Las lecturas de descuentos para render o el store no prueban borrado durante Edit.
3. Nombre/description son requeridos. Campos configurables sólo se aplican si están habilitados y presentes. `tipo_venta` se asigna sólo cuando existe en el request. Fields disabled se retiran por middleware antes del FormRequest y otra vez antes de escribir; se comprueba la jerarquía efectiva con valores conservados.
4. `type_tax_id` sigue asignándose en controller, pero es requerido por UMR: ausencia no llega al update por la ruta normal. Permite conservar el impuesto histórico inactivo; cambiarlo exige activo.
5. Niveles pasan por MaterialInventoryScope tanto al cargar como al validar y sincronizar; valida tenant/company/branch/general, pertenencia de stock y ubicación. Sólo escribe min/max, no cantidades/costos ni almacén. La granularidad real persistida sigue siendo InventoryLevel/ubicación: probar múltiples niveles por almacén antes de afirmar equivalencia completa con umbral por almacén.
6. Persisted variant size/color immutability sigue en UMR y dentro de transacción en controller. Omisión conserva variantes. Crear combinación distinta usa camino de variante nueva. El nuevo early return del validador exige que el payload manipulado cumpla antes configuración, campos obligatorios y snapshot. El harness de octubre no es evidencia del nuevo request.

Motivos vigentes del bloqueo productivo completo: política histórica de SKU/barcode/estado/inventariable; revisión histórica y company/branch de retacería; QA transaccional de preservación, variantes y niveles no ejecutado. Esto **no bloquea continuar frontend** ni autoriza agregar bloqueos UI. Cambiar retacería sigue intencional: consulta Items por material y entered/exited, no por MaterialInventoryScope. El fix de niveles no se propaga mágicamente a esa mutación.

## Material List V2 — semántica actual

`getDataMaterials` usa MaterialCompanyCatalog para productos habilitados en company y MaterialStockReadModel para indicadores. `indexV2` usa el mismo read model en alertas. La consulta de niveles permite branch actual y general, pero la presentación **los separa**:

| Indicador | Fuente/semántica actual | Límite de cierre |
| --- | --- | --- |
| stock_current y stock_actual | Mismo físico local; sólo si hay un único StockItem simple | No confundirlo con legacy Material.stock_current |
| stock_general | Físico de almacenes generales separado | No sumar desde frontend al stock local |
| stock_reserved / stock_available | Reserva local / físico menos reservado local | Probar saldos anómalos y precisión |
| stock_min / stock_max | Sólo cuando existe un único nivel local y único StockItem simple; en otros casos null | No sumar umbrales ni reemplazar null por cero ficticio |
| prioridad | Local: sin inventario, sin disponibilidad, por agotarse, sobre máximo, aceptable | Estados nuevos respecto de legacy |
| resumen/alertas | Niveles de branch activa; generales quedan en detalle | Gobernados por settings de alerts y popup |
| variantes/múltiples StockItems | Totales de fila null, detalle por StockItem | No inferir un agregado vendible |
| inventario detallado | MaterialInventoryScope + permisos/snapshot | QA de IDs y ubicaciones incongruentes |

Los accessors legacy de `Material` y otros endpoints aún existen, pero **ya no son la fuente de las columnas principales de V2**. No mantener ese blocker histórico en V2; tampoco declarar todo inventario normalizado.

**Separar paquete no queda corregido:** indexV2 alimenta `data-quantity` con el nuevo `stock_actual`, mientras `storeSeparatePack` sigue incrementando/decrementando `materials.stock_current`. Además `/material-unpack/*` usa un modelo sin scope, `exists:materials,id` sin tenant y rutas sin permiso específico. Es un riesgo concreto independiente de la lectura V2; no ejecutar ni ampliar este subflujo.

## Configuraciones de Material — findings abiertos revalidados

Los once servicios de eliminación se invocan desde los controllers; los requests bulk validan array no vacío, máximo 500, IDs positivos/distintos/existentes del tenant y no borrados. Los servicios vuelven a comprobar cardinalidad dentro de transacción y evitan omitir silenciosamente IDs. Las rutas bulk ya tienen permisos destroy correspondientes. UI Venti Next aún contiene `data-backend-sync-open`/disabled: la siguiente fase debe retirar controles obsoletos **después de QA**, no durante esta auditoría.

| Configuración | Evidencia actual de dependencias | Estado / pendiente específico |
| --- | --- | --- |
| Unidad | DeleteUnitMeasures consulta materials, stock_items, workforces incluyendo historia | IMPLEMENTED — QA PENDING; atomicidad y rechazo |
| Categoría | Descendientes subcategory/type/subtype, referencias materiales y seasonal_promotions; borrado bottom-up | IMPLEMENTED — QA PENDING; árbol inconsistente y cascade failure |
| Subcategoría | Descendientes e históricos, eliminación bottom-up | IMPLEMENTED — QA PENDING; store individual aún tiene contrato múltiple, no inferir cambio |
| Tipo Material | Subtipos/materiales históricos; una estrategia de cascada con comprobación final | IMPLEMENTED — QA PENDING; no mantener doble borrado como hecho vigente |
| Subtipo | Materiales históricos | IMPLEMENTED — QA PENDING |
| Género | Materiales históricos | IMPLEMENTED — QA PENDING |
| Talla | Materiales y variantes históricos; relación Material::talla corregida | Bulk IMPLEMENTED — QA PENDING; relación RESOLVED |
| Color | Referencias de variantes, incluidas históricas | IMPLEMENTED — QA PENDING |
| Marca | Modelos y materiales históricos, relaciones cross-tenant corruptas rechazadas; cascada verificada | IMPLEMENTED — QA PENDING |
| Modelo | No desvincula materiales al borrar: bloquea referencias históricas | IMPLEMENTED — QA PENDING; padre archivado por otra vía requiere QA |
| Retacería | Materials e Items directos/históricos bloquean eliminación | IMPLEMENTED — QA PENDING; unidad dimensional y propagación histórica no cerradas |
| Parámetros | config_materialSetting, company/membership, array/keys, normalize, snapshot/version | IMPLEMENTED — QA PENDING; configuración inicial 409 explícita, no default global inventado |

Catálogos permanecen tenant; configuración permanece company. Unidad de medida obligatoria y dependencias jerárquicas/pareja talla-color están ahora implementadas. Platform Admin recibe rechazo explícito en esta superficie operacional; no se abre selección de soporte. El requerimiento de un FormRequest dedicado queda superado funcionalmente por validación equivalente explícita en controller; no mantener un blocker sólo por la clase elegida.

## Cotizaciones / Workers / Users

**Creator filter Backend Sync: OPEN — BLOCKING FOR PRODUCTION.** `QuoteSaleController::index` (:144) e `indexFacturadas` (:205) continúan `User::select('id','name')->get()`. User no tiene TenantScope que convierta esa consulta en contextual. No hay delta en estos métodos ni en WorkerAccess/WorkerUserLink/WorkerCompanyContext en este merge.

WorkerAccess comprueba usuario, tenant, membresía company y branch. Su scope de trabajadores es company; WorkerCompanyContext protege acciones/IDs y snapshots de formularios. WorkerUserLink filtra usuarios del tenant autorizados en company, evita duplicados y resuelve actor de venta dentro de transacción. **Estos servicios no son usados para construir arrayUsers en las dos listas de Cotizaciones.** Tampoco el nuevo SaleListOptions cambia ese catálogo: pertenece a ventas.

Las cotizaciones de listas/años usan QuoteBranchReadScope (tenant/company/branch); históricos sin branch tienen `pending()` y lectura separada. QuoteBranchContext bloquea mutaciones/conversión de históricos pendientes y rechaza otras branches. No hay base para declarar corregido el filtro de creador ni para reabrir la asignación histórica desde UI.

Q-02 continúa: `printQuoteToCustomer` contiene `glob(public_path().'/pdfs/*')` y `unlink`; no se ejecutó esa ruta GET. Q-03/Q-04 no reciben fix de permisos/relaciones por el nuevo guard de ventas. Q-05 no obtiene snapshots históricos por las nuevas columnas de SaleDetail. Q-06 conserva ruta a `indexGeneral` inexistente y contrato histórico de paginación/fechas, sin modificación de esos métodos. Q-07 exige dataset branch-bound y roles limitados.

Para cerrar REAL NEXT 10: backend primero debe corregir catálogo de creadores; luego QA general/facturadas/detalle/históricos con EDESCE/Gamarra, otras companies/branches, rol limitado, fechas y paginación, visibilidad de precios, y endpoints documentales/acciones según alcance de cierre. **No está READY FOR FINAL QA integral** mientras siga la consulta global.

## POS / Sales List

Estado del módulo: **PARTIALLY READY**. El subconjunto de **listado/años/filtros/detalle de ventas autorizadas está READY FOR FRONTEND**, condicionado a smoke de datos cuando el entorno esté disponible. El checkout completo tiene implementación sustancial, pero requiere QA transaccional antes de cierre productivo.

| Superficie | Contrato actual verificado | Estado y límite |
| --- | --- | --- |
| Creación Sale | `PuntoVentaController::store` delega a PosSaleService; tenant/company/branch del contexto, created_by/name de WorkerUserLink; worker opcional validado | IMPLEMENTED — QA PENDING; no usar storeO/storeO2 como contrato de la ruta actual |
| Actor/worker | Usuario registrador siempre; trabajador opcional company y branch/general; setting `pos.require_worker` sólo muestra selector | Decisión implementada; QA sin worker y vínculo duplicado |
| Lista/años/creadores | SaleListAccess::visible y SaleListOptions; tenant/company/branch, autor no nulo y del tenant; sin permiso viewBranch sólo autor propio | No bypass automático admin/owner; viewBranch significa todas las ventas **del local**, no todas las branches |
| Históricos Sale | Registros sin contexto/autor válido quedan fuera del listado | No confundir ausencia con borrado; migraciones nuevas no reparan toda historia |
| Detalle/impresión | Rutas usan SaleListGuard; lookup del padre autorizado antes de controller; impresión usa company/settings del contexto | Lectura integrable; QA ID ajeno y branding; archivos estáticos aparte |
| V1/V2 catálogo | V1 sólo StockItems con disponible local válido; V2 Material con opciones habilitadas; DTO de precio/stock; snapshots | READY FOR FRONTEND catálogo/consulta; no rating ficticio nuevo desde frontend |
| Precio/moneda | BranchPriceListService: asignada válida o default único company; PriceResolver por StockItem→Material; ausencia es null | No vender con fallback cero inventado; requiere configuración |
| Impuesto | PosLineCalculator exige tax activo/coherente; cálculo servidor; snapshot tax y precio en detalle | QA gravado/exonerado/inafecto, tasas y redondeo |
| Stock/despacho inicial | PosInventoryService usa lotes/ubicaciones de branch activa, reservas, locks y cantidades; Items físicos cuando tipo lo exige | Consume local; general/otras branches se consultan aparte, no se consumen como local |
| Disponibilidad otros locales | Endpoint explícito de consulta company-wide, excluye local del grupo remoto; sin costos ni acciones de traslado | Comportamiento de consulta deliberadamente distinto del consumo; no es excepción owner |
| Caja/cash register | PosCashService exige box company/branch o general, moneda, subtipo compatible, exactamente una sesión propia/local abierta; locks ordenados | QA pago/vuelto/diferido y concurrencia |
| Pago parcial posterior | SaleListGuard + CashRegister tenant/company/branch/user; callbacks existentes permanecen | No equiparar su lógica con PosCashService; véase riesgo de concurrencia inferior |
| Despacho posterior | Ruta protegida por updateDispatch_puntoVenta + padre/snapshot | Guard de acceso implementado; transición de estado aún necesita QA funcional |
| Documentos/emisión | manageDocuments y permiso boleta/factura; SaleEmissionService después de commit, replays no reenvían; conciliación bloquea acciones incompatibles | IMPLEMENTED — QA PENDING; no emisión externa durante auditoría |
| Anulación/notas crédito | SaleListGuard aplica annul_puntoVenta a rutas actuales, padre/contexto; revisión de emisión impide mutaciones incompatibles | No valida por sí solo contabilidad, reposición ni límites de crédito; QA transaccional pendiente |
| Descargas | SaleListDocumentController verifica padre y filename/realpath; nuevo endpoint protegido | Ubicación prevista public/comprobantes y store devuelve asset directo: cierre documental bloqueado |

El riesgo de exposición surge del código: `.htaccess` remite a Laravel sólo rutas que no son archivos/directorios. El controlador de descarga no puede proteger por sí solo el acceso HTTP directo a un archivo público existente; no se encontró en el contrato revisado una regla que lo impida. **public/comprobantes no existe en este working tree**: no se acredita una fuga observada ni la configuración de otro servidor. Se requiere verificar infraestructura y retirar/denegar el acceso directo antes de certificar confidencialidad. No se descargaron comprobantes ajenos para demostrarlo.

**Venta libre es una excepción separada:** FreeSaleController no cambió y no delega a PosSaleService. Su store exige Worker por user_id, calcula el contrato gravado fijo y crea Sale sin company_id, branch_id ni created_by explícitos. BelongsToTenant aporta alcance tenant, pero no sustituye esos campos. Las rutas de creación/guardado no adoptaron el nuevo checkout; guards en anulación/emisión no reparan la creación. Por ello no se extiende a Venta libre el estado de readiness del catálogo/checkout principal. Queda STILL BLOCKED FOR FRONTEND para ampliar ese flujo y pendiente de backend antes de aprobación productiva.

**Pagos parciales:** `SalePartialPaymentController::store` usa `latest()->first()` para sesión abierta y actualiza saldos sobre modelos leídos sin `lockForUpdate`, a diferencia del nuevo PosCashService. El nuevo guard corrige alcance, pero no demuestra serialización/idempotencia de pagos posteriores. **NEW FINDING** de revisión de concurrencia, no una pérdida de dinero observada. No bloquea modernizar lectura; sí requiere backend/QA antes de cierre financiero.

Las seis capacidades nuevas de Sales List están en `config/permissions_catalog.php`. Debe comprobarse su sincronización y asignación real antes de interpretar un 403 de owner como bug o dar permisos desde frontend. Las cuatro migraciones nuevas agregan lista de branch, referencias de precio, idempotencia/tax y ofertas; **la identidad contextual de la venta se asigna en PosSaleService, no por un backfill general de estas migraciones**.

## Dashboard — garantías separadas

HomeController/GraphsController no cambiaron en este delta. Se releyeron sus queries y los modelos, sin asumir vigente la afirmación histórica de que Sale/CashMovement no tenían scope.

| Fuente/superficie | TENANT SAFE | COMPANY SAFE | BRANCH SAFE | Evidencia |
| --- | --- | --- | --- | --- |
| Sale utilizado por charts/ranking | Sí por TenantScope cuando existe contexto | No | No | Home suma por worker/fecha; Graphs consulta Sale sin scope company/branch |
| Output KPI | Sí por TenantScope contextual | No | No | Home::dashboard usa Output::count() |
| CashMovement | Sí por TenantScope contextual | No | No | getCashData filtra fecha/tipo; no caja/company/branch explícitos |
| Meta/ranking | No certificado | No | No | Meta global sin scope; relación Worker scoped no prueba ownership de la meta |
| OrderService/cash-flow | No | No | No | Modelo sin tenant scope; query por fecha/proveedor |
| Dashboard completo | **NO** | **NO** | **NO** | Mezcla fuentes con garantías distintas |

No se elimina el progreso de Sale/Output/CashMovement tenant-scoped, pero no basta para una etiqueta global. Rutas `/sales/chart-data-sale`, `/sales/chart-data-utilidad`, exports de ventas y cashflow continúan sin permiso granular equivalente a la visibilidad Blade. Los datasets de ubicaciones/almacenes/años/meses de widgets retirados siguen consultándose: **TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND** por trabajo innecesario; aislamiento y autorización de agregados son **BLOCKING FOR PRODUCTION**.

No se debe ampliar reporting con datos company/branch mientras no exista ese contrato. No corregir cifras con JS ni reutilizar SaleListAccess parcialmente y afirmar que todos los agregados quedaron protegidos.

## Platform Admin

Decisión vigente: equipo Venti360 global sin contexto tenant/company/branch; soporte contextual futuro. `EnsurePlatformAdmin` exige `is_platform_admin` y ahora ejecuta `TenantContext::clear()`. El menú laboral retiró el `@can('list_percentageWorker')`; las rutas bajo `/dashboard/plataforma` conservan `platform.admin`. `PercentageWorkerController` lee/escribe sólo las tres claves globales permitidas (`assign_family`, `essalud`, `rmv`) en las rutas platform; no atribuye esas escrituras a `user->tenant_id`.

**RESOLVED**: inconsistencia menú/ruta laboral y supuesto de que un tenant_id histórico obliga a contexto o permisos granulares. El dato histórico por sí solo no demuestra contaminación. No se requiere migration de ese dato para continuar estas pantallas. Queda smoke de sesión con contexto previo y acceso global, que no reabre una decisión funcional. El resto de superficies platform utiliza tenant explícito cuando corresponde (p. ej. roles de tenant), no tenant inferido del usuario.

## Store Web — SW-01…SW-07

**STORE WEB PUBLICATION BLOCKED**. No hay cambios en StoreWebController ni los dos middlewares de preview en este delta. `StoreWebLocalPreview` exige local + preview antes de controllers/bindings; `StoreWebAuthenticatedDataBridge` permite datos sólo en QA local autenticado con tenant/company válidos. Home sigue fixture. Esto no define identidad pública ni habilitación comercial por company.

La copia developers `C:\wamp64\www\aliexpress` **no existe** en este equipo. No es posible certificar equivalencia entre dos working trees. Se compara el backend incorporado por el merge `ec5f6dd` con la integración en HEAD, que sí está disponible; no se supone que una copia ausente contenga fixes adicionales.

| SW | Estado SW solicitado | Estado de finding | Evidencia actual / qué falta |
| --- | --- | --- | --- |
| SW-01 contexto público | FUNCTIONAL CONTRACT PENDING | STILL BLOCKED FOR FRONTEND | Bridge local no es resolver público; sin tenant/company público antes de binding; scope opcional no garantiza acceso anónimo |
| SW-02 publicación/disponibilidad | FUNCTIONAL CONTRACT PENDING | BLOCKING FOR PRODUCTION | StoreWeb agrega niveles por StockItem sin adoptar MaterialInventoryScope/habilitación pública company; faltan reglas públicas de publicación |
| SW-03 precios/moneda | PARTIAL | IMPLEMENTED — QA PENDING | PriceResolver compartido mejoró default único y validación tenant/lista; StoreWebController no adoptó contrato de branch/POS y todavía construye precio/price_text con su lógica. Falta moneda/flags/ausencia de precio públicos |
| SW-04 filtros | PARTIAL | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND | Search/category/subcategory/sizes/colors/precio siguen contrato temporal; no nuevas promociones/marca/condición. No resuelve coherencia por variante disponible ni alcance público |
| SW-05 variantes/presentaciones | FUNCTIONAL CONTRACT PENDING | STILL BLOCKED FOR FRONTEND | Ofertas branch nuevas son privadas de POS/mantenimiento; no binding en detalle StoreWeb ni combinación pública/WhatsApp definida |
| SW-06 Home/editorial/relacionados | FUNCTIONAL CONTRACT PENDING | STILL BLOCKED FOR FRONTEND | Home fixture, sin contrato editorial nuevo; no publicar descripciones internas por inferencia |
| SW-07 branding/assets | PARTIAL | IMPLEMENTED — QA PENDING | CompanySettingImageService guarda PNG bajo images/logo/tenant-N/company-N, compatible con base legacy; no establece identidad/fallback público. Assets permanentes ya integrados; revisar logos nuevos con contexto público cuando exista |

No marcar SW-03/07 RESOLVED por mejoras de servicios compartidos. Su QA técnico puede avanzar en bridge, pero cierre público depende de SW-01 y política comercial. SW-04 no autoriza convertir controles visuales pendientes en filtros falsos. No se modificaron ni borraron assets/reference folders durante esta fase.

## Traslados entre almacenes

**Candidato prioritario; READY FOR FRONTEND por etapas.** Sin delta nuevo, pero mantiene contrato suficiente:

- Rutas `/dashboard/traslados/almacenes` con list_transfer y capacidades de solicitud/preparación/despacho/recepción/cancelación.
- WarehouseTransferAccess valida tenant/company/membresías y branch; listado visible por origen o destino salvo `manageAll_transfer` explícito. No amplía owner automáticamente.
- Solicitud normal se inicia para destino de branch activa; almacenes origen/destino pertenecen a company, con validación de branches activas. Snapshots de contexto y request keys.
- Preparar sólo requested, reserva lotes/Items, locks y validación de saldos. Dispatch sólo prepared, descuenta reservas/físico sin duplicar un reintento. Receive sólo dispatched/partially_received, cantidades pendientes y trazabilidad; termina received. Cancelación limitada a estados permitidos libera reservas, no simula reversión de mercancía ya recibida.
- Recepción parcial/idempotencia y selección de origen/destino son contratos backend existentes. No inventar una transferencia instantánea en frontend.
- Servicios exigen condiciones como una ubicación operativa por almacén y costos válidos; representarlas mediante errores del servidor. No cambiar inventario para preparar QA.

List/detail/solicitud pueden mapearse a patrones aprobados. Cierre productivo del ciclo requiere dataset autorizado y pruebas de reserva, recepción parcial, permisos de extremos, empresa ajena, cancelación y reintentos. `summary()` es lectura; no usar métodos operativos para obtener datos del detalle.

## Cajas y subtipos

**READY FOR FRONTEND**, sin esperar cierre total de POS. CashBoxController/CashBoxSubtypeController ya separan permisos list/create/edit/disable; mantenimiento y relaciones se restringen a company. Caja puede ser general (`branch_id=null`) o asociada a branch de la misma company; valida cuenta bancaria y restricciones sobre cambios con sesiones abiertas. Subtipos son de company y compatibles con caja bancaria/uses_subtypes.

El listado de mantenimiento company-wide no debe confundirse con selección operativa de caja en POS (branch/general + sesión propia abierta). Movimientos my/admin añaden su alcance operativo. No concluir que «owner puede ver todo tenant». El frontend actual de cajas no figura en la allowlist Venti Next; es una integración visual nueva sobre contrato ya existente. QA de permisos, relaciones, toggles y sesiones debe distinguir lectura de mutación; aquí no se guardaron configuraciones.

## Compras

No hay nuevos cambios en compras desde `20cea30`. Se mantiene progreso real previo: OrderPurchase/OrderPurchaseDetail/MaterialOrder usan ScopedPurchaseCompany, que falla cerrado sin tenant/company; asigna contexto al guardar, verifica ownership original y prohíbe reubicar detalles. NormalPurchaseGuard valida snapshot tenant/company, proveedor/cotización/plazo/StockItem habilitado, y bloquea editar órdenes con recepciones sin conciliación.

**Candidato nuevo acotado: listado y detalle de órdenes normales a nivel company.** Hay rutas con permisos list_orderPurchaseNormal y controller scoped. No se acredita un contrato branch: no agregar filtro branch inventado ni presentar compras como BRANCH SAFE. La política para operar a nivel branch requerirá alineación si se exige esa superficie.

Create/update normal tiene implementación, pero falta QA de cantidades/importes, recepción y notificaciones; no se prueba mediante escrituras en esta auditoría. Solicitudes (`RequestPurchase`) y órdenes financieras (`OrderPurchaseFinance`) no comparten el trait; no heredan la certificación de órdenes normales. No se propone modernizar toda la familia Compras bajo una única garantía. Frontend de compras no está allowlisted en Venti Next.

## NEW FINDINGS

| ID | Evidencia | Clasificación | Consecuencia / siguiente paso |
| --- | --- | --- | --- |
| N-01 | SaleListDocumentController sirve ficheros de public; store devuelve asset directo; .htaccess excluye archivos existentes | NEW FINDING | Nueva ruta protegida no cierra descarga pública. Verificar infra y almacenamiento privado antes de cierre documental |
| N-02 | V2 usa stock_actual local nuevo, storeSeparatePack escribe Material.stock_current legacy; MaterialUnpack carece de scope | NEW FINDING | No ampliar Separar paquete; backend debe alinear inventario/ownership/permisos |
| N-03 | SalePartialPaymentController::store selecciona latest first y guarda saldo sin locks equivalentes al checkout | NEW FINDING | Revisar concurrencia/idempotencia en pagos posteriores; no extender garantía de PosCashService |
| N-04 | Once bulk aún disabled con mensajes de backend pendiente, pese a nuevos requests/services | NEW FINDING | Deuda de sincronización frontend; reabrir QA y luego activar controles, conservando diseño |
| N-05 | Repetición posterior a migraciones: batch 142 aplicado, created_by existe y consultas funcionan | RESOLVED | Retirado bloqueo de esquema local. Persisten límites de dataset y permisos para cierre del QA; no se ejecutaron migraciones durante la auditoría |
| N-06 | Nuevas vistas/partials/JS Material/POS incorporadas desde backend | NEW FINDING | Revalidar baseline y responsive; no afirmar paridad sólo porque CSS no cambió |
| N-07 | FreeSaleController mantiene creación legacy fuera de PosSaleService, sin asignación explícita de company/branch/actor | NEW FINDING | Separar Venta libre del avance POS; requiere alineación contextual y QA antes de ampliar el flujo |

## NEW IMPLEMENTATIONS

«Nuevo» significa nuevo en el delta auditado; funcionalidades previas ahora candidatas (Transfers/Cajas/Compras) se listan en sus propias secciones.

| Módulo | Qué hace | Backend maturity | Frontend existente | Riesgo | Recomendación |
| --- | --- | --- | --- | --- | --- |
| Motor checkout POS contextual | Venta/idempotencia/tax/stock/caja y emisión separada | Implementado, QA transaccional pendiente | V1/V2 legacy adaptados | Alto al guardar, medio al leer | Integrar lectura antes de cierre financiero |
| Sales List Access | Lista/detalle/años/actor y permisos de operaciones | Implementado, QA roles/datos pendiente | List/error con adapters nuevos | Medio | Próxima superficie nueva segura de lectura |
| Disponibilidad en otros locales | Consulta stock company remoto/general sin consumirlo | Implementado, QA pendiente | _stockLookup + catalog-stock.js | Medio | Reusar contrato, no recalcular |
| Lista de precios por branch | Asignación activa o default company único | Implementado, requiere migración/configuración | TenantBranch create/edit | Medio | Integrar con mantenimiento de sucursales/precios |
| Ofertas de presentaciones por branch | Precio/moneda/active por sucursal, definición tenant y snapshot | Implementado, QA y migración pendientes | presentations-branch.js + controller | Medio | Fase propia antes del POS completo |
| Material Parameters versionado | Estado inicial, unidad/dependencias, rechazo stale, disabled | Implementado, QA pendiente | Vistas existentes + required/_context | Bajo/medio | Cerrar trabajo existente |
| Inventario/Items contextual | Scopes, métricas comunes y permisos sobre Items | Implementado, QA pendiente | Material/StockItem con nuevos adapters | Medio | Cierre de List V2 y detalle |
| Borrado seguro de catálogos | IDs completos, historia/cascadas, transacciones | Implementado, QA pendiente | Controles bulk todavía inertes | Medio destructivo | QA aislado y activación posterior |
| Imágenes de empresa | Validación, conversión PNG, paths tenant/company, limpieza referencial | Implementado, QA de upload pendiente | CompanySetting image inputs | Bajo/medio | Quick win visual con selección sin guardar |

## RESOLVED SIN MÁS ACCIÓN

Sin más **fix backend para el defecto concreto**: unit_price/list_price en Material Edit; supuesto borrado de descuentos por update; Material::talla a Quality; permiso antiguo de lookups ya corregido; inconsistencia menú/ruta laboral; necesidad ficticia de tenant operativo o granularidad adicional para Platform Admin. Regresión de la pantalla completa sigue siendo necesaria y no se oculta dentro de esta etiqueta.

## IMPLEMENTED — QA PENDING

Preservación de fields disabled/ausentes, parámetros/versionado, inventario scoped, indicadores V2, tax Material, inmutabilidad/omisión de variantes, once borrados seguros, servicios POS, Sales List, listas/presentaciones branch y logos. QA debe ejecutar contratos reales; un botón disabled o una clase nueva no prueba seguridad.

## READY FOR FINAL QA

Material List V2 **lectura/detalle/indicadores**, Parámetros de Material, catálogos CRUD (en DB aislada para borrado) y Platform Admin laboral/global. Material Create puede reabrir QA de catálogos/impuestos/contexto. Material Edit permite QA selectivo de fixes, **no cierre de Save completo**. Cotizaciones no está lista para cierre integral por Q-01/Q-02 y demás pendientes.

## READY FOR FRONTEND

Sales List **lectura** (lista/años/filtros/detalle), Cajas/Subtipos de mantenimiento, Traslados por etapas, presentaciones/precios de branch y órdenes de compra normales **lectura company**. La autorización para diseñar/integrar el contrato no equivale a autorización para modificar registros QA en esta fase.

## FRONTEND CAN CONTINUE WITH TECHNICAL DEBT

Material Edit con historia pendiente; Material Create/List con QA pendiente; fachada de Cotizaciones existente sin inventar filtro; pantallas existentes de Dashboard sin ampliar sus métricas; preview local Store Web sin publicar. SKU/barcode/estado/inventariable y conservación histórica de retacería siguen documentados.

## STILL BLOCKED FOR FRONTEND

Binding/publicación Store Web; nueva funcionalidad de Separar paquete; ampliación de Venta libre legacy; nuevos editores/conversión/impresión de Cotizaciones sobre contratos inseguros; reporting nuevo que pretenda garantías company/branch no implementadas; ampliación indiscriminada de Compras financieras/solicitudes. No se etiqueta todo POS como bloqueado: lectura y catálogo tienen contrato independiente.

## BLOCKING FOR PRODUCTION

Q-01 usuarios globales y Q-02 limpieza PDF, permisos/relaciones Q-03/Q-04, agregados Dashboard, publicación SW, separación legacy y relaciones unpack, Venta libre sin contrato contextual completo, historia de Material Edit, riesgo de descarga estática de documentos, validación financiera/concurrente pendiente de POS/pagos/traslados. Migraciones y capacidades instaladas deben verificarse antes de QA funcional; no se dan por aplicadas.

## FUNCTIONAL DECISION DEFINED — IMPLEMENTATION PENDING

No reabrir decisiones: maestro Material/StockItem tenant; inventario operativo company + branch + general; owner global separado; precio en listas; retacería intencional; Platform Admin global sin contexto. El inventario operacional ya pasó de decisión pendiente de implementación a **IMPLEMENTED — QA PENDING** en los caminos conectados al nuevo servicio. Quedan fuera la separación de paquetes y la revisión histórica de retacería. La vista global independiente de owner y el soporte contextual de plataforma siguen siendo superficies futuras, no exigencias para terminar la vista operacional actual.

## ROADMAP FRONTEND NUEVO

Ranking derivado de este HEAD. Esfuerzo relativo (bajo/medio/alto), no estimación de calendario. Cada fase termina con QA acotado; el ranking favorece seguridad y cierre de trabajo ya integrado antes de aumentar alcance.

| Prioridad | Fase | Motivo | Dependencias | Backend readiness | Frontend effort | Riesgo |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | REAL NEXT — Material post-merge QA & contract alignment | Mayor avance backend, pantallas ya integradas; cerrar V2/Parámetros/Create y probar fixes Edit sin cerrar historia | MySQL disponible, migraciones/roles verificadas, baseline; QA de borrado en entorno aislado | Alto para lectura/configuración; Save Edit parcial | Bajo/medio | Bajo en lectura; medio en activación bulk |
| 2 | REAL NEXT — Sales List Read-only Integration | Retira blocker grande mediante access/options/guard; alto valor y patrón Operational List existente | QA EDESCE/Gamarra y rol propio/local, registros atribuibles | Alto para lista/años/filtros/detalle; documental parcial | Medio | Medio; separar finanzas y descarga estática |
| 3 | REAL NEXT — Cash Boxes & Subtypes | Mantenimiento autónomo y reutilización CRUD; prepara POS | Roles company y relaciones, dataset de cajas | Alto por contrato; QA pendiente | Bajo/medio | Medio en guardados, bajo en lectura |
| 4 | REAL NEXT — Warehouse Transfers | State machine, acceso y contratos suficientemente definidos; valor de inventario | QA origen/destino, disponibilidad/costos, ambiente aislado para ciclo | Alto de implementación, QA operacional pendiente | Alto | Medio/alto por operaciones de stock |
| 5 | REAL NEXT — Branch Pricing & Presentations | Nuevos contratos necesarios para POS, reduce retrabajo | Migraciones, listas y moneda; validación de oferta por branch | Implementado, QA pendiente | Medio | Medio |
| 6 | REAL NEXT — POS Catalog & Checkout Contract Integration | Ya existe motor contextual; modernizar sobre precios/caja preparados | Fases 2/3/5, roles, datasets y pruebas aisladas de transacción/emisión | Catálogo alto; checkout pendiente de QA integral | Alto | Alto al persistir; no publicación sin cierre |
| 7 | REAL NEXT — Normal Purchase Read-only Integration | Nuevo candidato útil por scoped company; reduce alcance frente a toda la familia | QA ownership/list/detail/roles y contrato explícito company | Medio/alto para lectura normal | Medio | Medio; no incorporar finanzas/recepción automáticamente |

### QUICK WINS

Smoke y cierre Platform Admin laboral; Material::talla/selector fiscal; alineación de mensajes y estados de Parámetros/V2; Cajas/Subtipos; UI de logos dentro de settings. Activación bulk sólo después de pruebas específicas. No mezclar esos cambios con rediseño.

### CLOSE EXISTING WORK

Material List V2, Parámetros, once configuraciones y Material Create; QA selectivo Edit. Revalidar responsive tras markup/backend incorporado. Cotizaciones necesita primero fix de creador y revisión de sus otros contratos; no basta repetir screenshots.

### WAIT FOR BACKEND

Store Web público (SW-01 y políticas comerciales); historia completa de StockItems/retacería para Save productivo; Separar paquete; archivos públicos; agregados Dashboard; correcciones Q-01…Q-06 según superficie; contrato de solicitudes/financieras de Compras. Pagos posteriores requieren revisión de concurrencia antes de cierre financiero.

**La siguiente fase frontend debe ser Material post-merge QA & contract alignment porque el backend ya corrigió varios blockers concretos de pantallas construidas, y validar esos contratos ofrece el mayor cierre verificable con el menor riesgo de retrabajo. La siguiente pantalla nueva debe ser Sales List de lectura.**

## QA requerido para convertir readiness en cierre

| Prueba | Evidencia en esta fase | Condición de cierre |
| --- | --- | --- |
| PHP 7.3.33 / JS syntax | 87 PHP y 16 JS cambiados: sin errores | No sustituye ejecución |
| Estado DB/migraciones | Conexión recuperada; ledger sin entradas 2026_11; Sales List falla por columna created_by ausente | Alinear esquema mediante fase autorizada; verificar tablas/columnas/índices y ledger sin ejecutar cambios aquí |
| EDESCE/Gamarra | Servicios/controllers con Auth en memoria; contexto y resultados en sección siguiente | Pendientes login HTTP, permisos de rutas, IDs manipulados y regresión visual |
| Usuarios limitados | No ejecutado | create-only/update-only/config/list/self/local; rechazos server-side |
| Variantes manipuladas | Prueba llegó al validador, pero material elegido carece de type_tax_id; casos base y alterados fallan antes del guard | Pendiente payload válido con otra talla/color del tenant y mismo variant_id; no contar rechazo fiscal como éxito del guard |
| Inventario/indicadores | Call graph y fórmulas revisadas | Principal excluye Outlet; general separado; reservas/umbrales/múltiples niveles/variantes |
| Platform Admin | Middleware real limpia contexto; controller devuelve las tres claves globales | Pendientes navegación HTTP, permisos efectivos y rechazo a operación tenant |
| Store Web | Guards y controller sin delta; copia secundaria ausente | OFF/no local bloqueado, anónimo sin datos reales, QA local no equivale a público |
| Sales/Transfers/Cash/Purchase mutations | No ejecutadas | Entorno aislado autorizado para atomicidad, concurrencia, estados y rollback |
| Responsive/paridad | No ejecutado | Revisar markup nuevo contra baseline, no certificar por lint |

No se emitió aprobación productiva basada en ausencia de datos. No hay commits, cambios de contratos ni fixes incluidos en esta auditoría.

## QA readonly retomado tras iniciar Wamp

Pruebas directas del backend en este HEAD, con conexión MySQL dentro de transacción READ ONLY y rollback final. Auth se estableció en memoria para las cuentas autorizadas; session/cache en array. No se afirma haber probado login, middleware completo de cada ruta ni responsive. Los contextos se seleccionaron exclusivamente de membresías activas del usuario, company y branch existentes.

| Cuenta QA / rol | Tenant | Company | Branch | Resultado observado |
| --- | --- | --- | --- | --- |
| owner@venti360.com / owner | 1 | 1 | 1 — Local Principal | Warehouse permitido 1 general; catálogo POS V2 14 registros; compras normales 2 |
| owner@venti360.com / owner | 1 | 2 | 2 — Tienda Prueba 1 | Warehouse permitido 2 general; catálogo POS V2 2 registros; compras normales 0 |
| owner@venti360.com / owner | 1 | 5 | 6 — PRINCIPAL | Warehouse permitido 7 general; métricas/catálogo vacíos; configuración Material ausente: create devuelve 409 |
| owner@venti360.com / owner | 1 | 5 | 7 — SUCURSAL TEST | Mismo warehouse general 7; catálogo vacío; create devuelve 409 por configuración |
| owner.gamarra@test.com / owner | 3 | 3 | 4 — Local Principal | Warehouse permitido 4 general; catálogo POS V2 2 registros; compras normales 0 |
| admin@venti360.com / Platform Admin | null tras middleware | null | null | EnsurePlatformAdmin limpia contexto previo; parámetros laborales: assign_family, essalud, rmv |

Hallazgos y límites de estas lecturas:

- **Cotizaciones Q-01 confirmado con datos:** index devuelve ocho creadores en ambos tenants. Para Owner EDESCE, tres son de otros tenants; para Owner Gamarra, seis. No se reproducen nombres ni datos personales de esos usuarios. El backend sigue OPEN; WorkerUserLink no corrige esta consulta.
- **Sales List bloqueado por esquema local:** visible() y options fallan con SQLSTATE 42S22 por `sales.created_by` ausente. El ledger no contiene migraciones con prefijo `2026_11_`. La columna de actor corresponde a `2026_10_24_000001_add_sale_actor_and_worker_user_uniqueness`, por lo que no basta asumir que faltan sólo las cuatro migraciones nuevas. No se ejecutaron ni repararon migraciones.
- **Material Create:** en companies 1, 2 y 3 devuelve impuestos activos `[1,2,4,3]`, default 1. En company 5 sin configuración devuelve 409. Corrobora contratos positivos y estado inicial, pero no cierra permisos limitados ni todos los lookups.
- **Inventario:** los warehouses devueltos cambiaron con company y tenant; en estos contextos eran generales. Las muestras del read model fueron distintas entre EDESCE y Gamarra. No existe aquí evidencia suficiente de combinaciones locales/remotas/reservas para cerrar toda la matriz de aislamiento.
- **Variantes:** el material de la variante 43 tenía impuesto ausente; baseline y cambios de talla/color fallaron por type_tax_id requerido. Prueba **inconclusa**, no evidencia de rechazo del guard de identidad. En Gamarra no se encontró variante persistida para este test. Sigue QA pendiente.
- **Traslados:** cero registros visibles en todos los contextos examinados. Confirma ejecución del scope, no aislamiento entre traslados existentes ni ciclo operativo.
- **Platform Admin:** comprobados middleware y lectura global directamente; no se realizaron escrituras globales ni QA visual.

La recomendación se mantiene: Material post-merge QA primero. Sales List conserva readiness de contrato para integración, **pero su QA funcional local no puede cerrarse hasta alinear el esquema**. Esta evidencia sustituye las menciones a indisponibilidad de DB de la primera ejecución; no convierte el resto del QA pendiente en aprobado.

## Sales List — QA posterior a migraciones

### Esquema confirmado

Se ejecutó `php7.3.33 artisan migrate:status` (sólo consulta): ninguna migración figura como No. Las siguientes figuran **Yes, batch 142**:

- `2026_10_24_000001_add_sale_actor_and_worker_user_uniqueness`
- `2026_11_02_000001_add_price_list_to_branches`
- `2026_11_02_000002_add_price_reference_to_sale_details`
- `2026_11_02_000003_add_pos_request_and_tax_snapshots`
- `2026_11_03_000001_add_branch_presentation_offerings`

Schema::hasColumn confirma `sales.created_by` y `sales.created_by_name`. Las mismas lecturas visible()/options() que antes fallaban ahora terminan sin error SQL. **El fallo concreto SQLSTATE 42S22 era exclusivamente esquema local desactualizado**, no requiere un fix de controller/frontend. Esto no prueba que todo POS esté cerrado.

### Datos, actor y contexto

Consulta agregada readonly: **104 ventas**, todas tenant 1 / company 1 / branch 1; 104 con worker_id, **0 con created_by**. La migración de actor deja deliberadamente los históricos con created_by NULL: el vínculo actual Worker.user_id no demuestra autoría histórica. No se hizo backfill ni se infirió el autor desde worker.

Se repitieron lecturas con Owner EDESCE (tenant 1, company/branch 1/1, 2/2, 5/6 y 5/7) y Owner Gamarra (tenant 3, company/branch 3/4), exclusivamente contextos de sus membresías activas. Ambos owners tienen list_puntoVenta y listErrors_puntoVenta, pero **no viewBranch_puntoVenta**. Alcance efectivo: **Mis ventas del local**; ser owner no produce bypass. Ninguna de estas cuentas tiene worker vinculado en las companies examinadas; esto no impide la lectura ni debe usarse para reconstruir actor.

Los seis flags print/dispatch/payments/documents/annul/emit resultan false para ambas cuentas. No se asignaron permisos. La migración de esquema no concede por sí sola las capacidades nuevas. Para QA de ventas de otros actores del mismo local será necesario un rol autorizado con viewBranch; la configuración actual no constituye por sí misma un bug.

### Pruebas ejecutadas

Harness externo con Auth en memoria, session/cache array, `START TRANSACTION READ ONLY` y rollback final. Se invocaron métodos reales de services/controllers; **no se simula que esto sea una sesión HTTP, render completo de Blade o QA visual**. Se corrigió únicamente el harness para aceptar el retorno array real de los controllers, sin alterar el contrato del proyecto.

| Prueba | Resultado actual | Límite |
| --- | --- | --- |
| visible() y options() | Sin error; ventas, años y creadores vacíos en los cinco contextos | Coherente con históricos sin actor; no acredita aislamiento positivo entre ventas atribuibles |
| listar() | Devuelve vista puntoVenta.list | No se renderizó navegador |
| getSalesAdmin página 1 y 2 | data vacío; currentPage respeta petición, totalPages/totalRecords/totalFilteredRecords = 0 | No hay dataset para paginación con resultados |
| Filtros code/year/fechas/annulled/payment_status | Retorno válido y vacío, sin error SQL | No demuestra selección positiva sobre filas |
| getSalesErrorAdmin | Retorno válido vacío | No prueba filas con error de emisión |
| created_by_id ajeno sin viewBranch | ValidationException | Rechazo del servicio; no se atribuye un HTTP status no observado |
| Página 0 | HttpException de la validación de página | Página inválida rechazada |
| Snapshot tenant/company/branch correcto | Aceptado | Se probó método snapshot directamente |
| Snapshot con branch incorrecta | ValidationException | Contexto stale rechazado |
| sale(id) sobre histórico sin autor | NotFoundHttpException en todos los contextos | Protección vigente; no hay detalle positivo atribuible disponible |

Se revalidó en código que visible() exige tenant/company/branch actual, actor no nulo del tenant y branch perteneciente a company, y que sin viewBranch añade actor autenticado. PosSaleService/WorkerUserLink asignan actor y contexto para nuevas ventas por el flujo actual; **no se ejecutó store, saleIdentity con locks ni una venta nueva**. La persistencia positiva de actor/worker continúa pendiente de QA transaccional autorizado. Los históricos no se hacen visibles cambiando frontend o relajando guards.

### Conclusión y blockers restantes

**SALES LIST: READY FOR FRONTEND**, acotado a listado/años/filtros/estado vacío/detalle autorizado según contrato actual. No se encontró un blocker que exija cambiar ese contrato para comenzar su integración. **POS completo mantiene PARTIALLY READY**.

Bloqueos para cierre final de QA/producción, no para empezar esa fachada:

1. No hay ventas con autor para probar filas, detalle positivo, paginación real, filtros efectivos y separación entre actores/companies/branches. No crear datos ni atribuir históricos durante esta fase.
2. Las cuentas actuales carecen de viewBranch y permisos de acciones: falta probar un rol autorizado con capacidades nuevas y contrastarlo con el alcance propio. No conceder permisos automáticamente a owner.
3. Falta QA HTTP/navegador y render de registros, incluido responsive. Las pruebas directas no validan toda la cadena de middleware.
4. Permanece el riesgo documental de archivos públicos y el QA financiero/de concurrencia de operaciones; no incluir esas acciones en un cierre de lectura ni certificar todo POS.

No se detectó un nuevo fallo de Sales List tras corregirse el esquema. La ausencia de resultados es una consecuencia verificable de los datos y la política de autoría, no otro error de columna ni un motivo para usar worker como creador.

**Recomendación de siguiente fase: no cambia el orden.** Material post-merge QA & contract alignment sigue primero porque puede cerrar trabajo existente con datos útiles. Sales List Read-only Integration sigue siendo la siguiente pantalla nueva, ahora sin bloqueo de esquema; puede iniciarse si se prioriza negocio, dejando explícitamente pendiente el QA de filas y roles. No necesita esperar el cierre financiero de todo POS.

Sólo se actualizó este informe dentro del repositorio. No se modificaron backend/frontend, permisos, registros, stock ni migraciones.

## Inventario exacto del delta funcional

127 archivos en `305abf2..f367675`. Este inventario corresponde al merge auditado, no a cambios realizados durante esta fase.

```text
app/Branch.php
app/Http/Controllers/BrandController.php
app/Http/Controllers/CategoryController.php
app/Http/Controllers/ColorController.php
app/Http/Controllers/CompanySettingController.php
app/Http/Controllers/ExamplerController.php
app/Http/Controllers/GeneroController.php
app/Http/Controllers/MaterialController.php
app/Http/Controllers/MaterialDetailSettingController.php
app/Http/Controllers/MaterialPresentationController.php
app/Http/Controllers/MaterialTypeController.php
app/Http/Controllers/PosAvailabilityController.php
app/Http/Controllers/PosSaleLookupController.php
app/Http/Controllers/PuntoVentaController.php
app/Http/Controllers/SaleListDocumentController.php
app/Http/Controllers/SalePartialPaymentController.php
app/Http/Controllers/StockItemController.php
app/Http/Controllers/SubcategoryController.php
app/Http/Controllers/SubtypeController.php
app/Http/Controllers/TallaController.php
app/Http/Controllers/TenantBranchController.php
app/Http/Controllers/TypescrapController.php
app/Http/Controllers/UnitMeasureController.php
app/Http/Middleware/EnsurePlatformAdmin.php
app/Http/Middleware/MaterialInventoryGuard.php
app/Http/Middleware/MaterialParametersGuard.php
app/Http/Middleware/PosCatalogGuard.php
app/Http/Middleware/SaleListGuard.php
app/Http/Middleware/StockItemScreenGuard.php
app/Http/Requests/DeleteMultipleBrandsRequest.php
app/Http/Requests/DeleteMultipleCategoriesRequest.php
app/Http/Requests/DeleteMultipleColorsRequest.php
app/Http/Requests/DeleteMultipleExamplersRequest.php
app/Http/Requests/DeleteMultipleGenerosRequest.php
app/Http/Requests/DeleteMultipleMaterialTypesRequest.php
app/Http/Requests/DeleteMultipleSubcategoriesRequest.php
app/Http/Requests/DeleteMultipleSubtypesRequest.php
app/Http/Requests/DeleteMultipleTallasRequest.php
app/Http/Requests/DeleteMultipleTypeScrapsRequest.php
app/Http/Requests/DeleteMultipleUnitMeasuresRequest.php
app/Http/Requests/StoreMaterialRequest.php
app/Http/Requests/StoreTenantBranchRequest.php
app/Http/Requests/UpdateMaterialRequest.php
app/Http/Requests/UpdateTenantBranchRequest.php
app/Material.php
app/MaterialPresentation.php
app/MaterialPresentationBranch.php
app/Sale.php
app/SaleDetail.php
app/Services/BranchPriceListService.php
app/Services/CompanySettingImageService.php
app/Services/Materials/DeleteBrands.php
app/Services/Materials/DeleteCategories.php
app/Services/Materials/DeleteColors.php
app/Services/Materials/DeleteExamplers.php
app/Services/Materials/DeleteGeneros.php
app/Services/Materials/DeleteMaterialTypes.php
app/Services/Materials/DeleteSubcategories.php
app/Services/Materials/DeleteSubtypes.php
app/Services/Materials/DeleteTallas.php
app/Services/Materials/DeleteTypeScraps.php
app/Services/Materials/DeleteUnitMeasures.php
app/Services/Materials/MaterialCompanyCatalog.php
app/Services/Materials/MaterialInventoryScope.php
app/Services/Materials/MaterialParameters.php
app/Services/Materials/MaterialStockReadModel.php
app/Services/Materials/StockItemScreenAccess.php
app/Services/PriceResolverService.php
app/Services/Sales/BranchPresentationService.php
app/Services/Sales/PosCashService.php
app/Services/Sales/PosCatalogReadService.php
app/Services/Sales/PosEmissionPayload.php
app/Services/Sales/PosInventoryService.php
app/Services/Sales/PosLineCalculator.php
app/Services/Sales/PosSaleNotice.php
app/Services/Sales/PosSalePriceReference.php
app/Services/Sales/PosSaleService.php
app/Services/Sales/PosScreenOptions.php
app/Services/Sales/SaleListAccess.php
app/Services/Sales/SaleListOptions.php
config/permissions_catalog.php
config/settings_catalog.php
database/migrations/2026_11_02_000001_add_price_list_to_branches.php
database/migrations/2026_11_02_000002_add_price_reference_to_sale_details.php
database/migrations/2026_11_02_000003_add_pos_request_and_tax_snapshots.php
database/migrations/2026_11_03_000001_add_branch_presentation_offerings.php
public/images/logo/tenant-1/company-1/setting-19-979c059fcbed7e7601b738fd0b88fe2c.png
public/images/logo/tenant-1/company-1/setting-20-3e1d3999fbf0c2108add37b6b688b75f.png
public/js/companySetting/index.js
public/js/material/edit.js
public/js/material/indexV2.js
public/js/material/inventory-context.js
public/js/material/presentations-branch.js
public/js/puntoVenta/catalog-stock.js
public/js/puntoVenta/checkout-context.js
public/js/puntoVenta/index.js
public/js/puntoVenta/indexV2.js
public/js/puntoVenta/list-access.js
public/js/puntoVenta/list.js
public/js/puntoVenta/listError.js
public/js/quoteSale/create.js
public/js/quoteSale/edit.js
public/js/quoteSale/edit2.js
public/js/stockItem/list.js
resources/views/companySetting/index.blade.php
resources/views/exports/salePdf2.blade.php
resources/views/layouts/appAdmin2.blade.php
resources/views/material/_inventoryContext.blade.php
resources/views/material/_resumen_popup.blade.php
resources/views/material/create.blade.php
resources/views/material/edit.blade.php
resources/views/material/indexv2.blade.php
resources/views/material/stockItems_items.blade.php
resources/views/materialDetailSetting/_context.blade.php
resources/views/materialDetailSetting/index.blade.php
resources/views/materialDetailSetting/required.blade.php
resources/views/puntoVenta/_catalogContext.blade.php
resources/views/puntoVenta/_listScope.blade.php
resources/views/puntoVenta/_stockLookup.blade.php
resources/views/puntoVenta/index.blade.php
resources/views/puntoVenta/indexV2.blade.php
resources/views/puntoVenta/list.blade.php
resources/views/puntoVenta/listError.blade.php
resources/views/stockItem/list.blade.php
resources/views/tenantBranch/create.blade.php
resources/views/tenantBranch/edit.blade.php
routes/web.php
```

