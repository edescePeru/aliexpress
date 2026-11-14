# REAL NEXT — Backend Sync Reclassification

Fecha: 2026-10-26. Código contrastado: `20cea30`, rama `feature/venti-next-frontend`, después del merge de `multitenancy`.

Fuente funcional: aclaración oficial del equipo backend/funcional entregada por el usuario. Esta revisión modifica exclusivamente documentación. Conserva las auditorías anteriores como evidencia fechada; las clasificaciones siguientes prevalecen para los subhallazgos enumerados. No cierra otros Backend Sync Open ni autoriza guardados productivos.

## Finding matrix

Las referencias son relativas al repositorio y las líneas corresponden al commit auditado. MC = `app/Http/Controllers/MaterialController.php`; UMR = `app/Http/Requests/UpdateMaterialRequest.php`; SIC = `app/Http/Controllers/StockItemController.php`.

| Finding | Estado anterior | Aclaración backend | Evidencia código actual | Nueva clasificación | Frontend puede continuar | Bloquea producción | Revisit Trigger |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Material Edit: precio ausente → cero | BACKEND SYNC OPEN; Save no aprobado | Edit modifica producto; precios pertenecen a listas de precios | MC:1541 asigna `input('unit_price', 0)`; :1612 también `list_price`; :1754 compara precio y marca actualización. Blade Edit no contiene el campo; JS:1797 serializa FormData y :1818 agrega variantes, no precio | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND; BLOCKING FOR PRODUCTION del guardado afectado | Sí, sin añadir precio ni simular payload | Sí, hasta separar/preservar precio legacy al editar producto | Backend retira estas escrituras del update o preserva ausentes; pruebas demuestran que editar producto no altera precios ni sus indicadores |
| Maestro Material / StockItem compartido por tenant | BUSINESS DECISION PENDING | Maestro compartido dentro del tenant, decisión definitiva | `Material.php:12`, `StockItem.php` y `BelongsToTenant.php` aplican TenantScope; el update usa Material y StockItems del maestro | RESOLVED, exclusivamente decisión y alcance del maestro | Sí | No por compartir maestro; inventario sigue pendiente | Cambio explícito del contrato del maestro; no reabrir por uso entre companies |
| Inventario operativo y min/max por almacén | Alcance/ownership pendiente | Company activa + almacenes generales (`branch_id = null`) + sucursal activa; stock readonly; min/max con contexto y permisos | MC carga niveles company-only y `syncInventoryLevels` (:2449); UMR:1590/:1637 valida company; SIC `getInventoryLevelsByMaterial` y `updateInventoryLevels` filtran company, sin branch. `InventoryLevel`/`Warehouse` tienen TenantScope, sin filtro global de branch | FUNCTIONAL DECISION DEFINED — IMPLEMENTATION PENDING | Sí, respetando contrato temporal; sin ampliar datos al owner | Sí para aislamiento operativo y escritura min/max | Backend aplica el mismo alcance en lecturas, validación, escrituras y agregados; QA Principal/Outlet y almacenes generales |
| SKU con historial | BUSINESS DECISION PENDING / OPEN | Mantener pertenencia y unicidad actuales; no inventar bloqueo frontend | UMR resuelve StockItem actual y valida SKU; MC permite editarlo, sin política histórica completa acreditada | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND; BLOCKING FOR FUNCTIONAL PRODUCTION CLOSURE | Sí, mostrar errores del servidor | Sí para cierre funcional con historial | Política backend de cambio de SKU con movimientos + pruebas |
| Barcode con historial | BUSINESS DECISION PENDING / OPEN | Conservar contrato actual y validaciones | UMR conserva validación de barcode y exclusión del StockItem propio; MC permite cambios | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND; BLOCKING FOR FUNCTIONAL PRODUCTION CLOSURE | Sí | Sí para cierre funcional con historial | Política y pruebas específicas de barcode histórico |
| Active/inactive con historial | BUSINESS DECISION PENDING / OPEN | No añadir restricciones frontend | UMR recibe `is_active`; MC persiste estado; no evidencia de política histórica completa | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND; BLOCKING FOR FUNCTIONAL PRODUCTION CLOSURE | Sí | Sí para cierre funcional con historial | Backend define y prueba transiciones con historial |
| Inventariable con historial | OPEN, agrupado con variantes | Mantenerlo separado de talla/color; no inferir resolución | UMR recibe `afecto_inventario`; MC conserva contrato de persistencia | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND; BLOCKING FOR FUNCTIONAL PRODUCTION CLOSURE | Sí, sin reglas nuevas | Sí para cierre del contrato histórico | Política backend de transición inventariable y prueba sobre movimientos existentes |
| Propagación de retacería | OPEN; se preguntaba si debía permitirse | Propagación a Items `entered`/`exited` intencional | MC:1703 selecciona por material y estados, copia largo/ancho/tipo cuando cambia a tipo no nulo; no filtra explícitamente company/branch | INTENTIONAL BEHAVIOR — HISTORICAL REVIEW PENDING; NON-BLOCKING FOR FRONTEND | Sí, sin bloqueo ni warning inventado | Sí para aprobar completamente Save | Verificar conservación de dimensiones originales en movimientos y alcance company/branch; no reconsiderar la intención ya definida |
| Persistent variant size/color immutability | Se preguntaba si requería regla backend | Mismo `variant_id` no admite otra talla/color; combinación nueva requiere nueva variante | UMR:885 rechaza cambio; MC:1966–1984 resuelve variante del Material con lock y vuelve a rechazar dentro de transacción | IMPLEMENTED — QA PENDING | Sí | Sí para cierre específico hasta completar QA funcional | Petición HTTP manipulada con combinaciones válidas y variante existente; rechazo sin mutaciones; probar guard transaccional y creación nueva |
| Variantes omitidas | Contrato sin eliminación, pendiente QA | Omitir no elimina | MC:1785 conserva existentes y procesa variantes enviadas; no elimina las omitidas en ese flujo | IMPLEMENTED — QA PENDING | Sí, sin acción de borrar | Sí para cierre específico de persistencia | Prueba controlada de subset que compruebe conservación de IDs y datos omitidos |
| Material List V2: stock/min/max/resumen/prioridad | BUSINESS DECISION PENDING sobre alcance y fuentes | Alcance operativo definido; backend normaliza fuentes/cálculos; owner global será otra superficie | `Material.php:62/141/233` consulta niveles por company, sin branch y con fallback legacy; :427 resumen de columnas legacy. MC:2937 prioridad y :3095 `stock_actual` mantienen otra fuente; :3250 usa resumen legacy | FUNCTIONAL DECISION DEFINED — BACKEND IMPLEMENTATION/VALIDATION PENDING; NON-BLOCKING FOR VISUAL INTEGRATION | Sí; no recalcular ni igualar significados en JS | Sí para consistencia funcional de indicadores e aislamiento | Backend entrega fuentes/fórmulas consistentes y alcance operativo; QA por almacén, company y branch |
| Permiso de lectura de tipos desde Material | Permiso incorrecto o limitado a `list_materialType`, según fecha del informe | Conservar contratos backend actuales | `routes/web.php:574` permite `create_material`, `update_material`, `list_material` o `list_materialType` | RESOLVED para el middleware incorrecto de esa ruta | Sí | No por este defecto concreto | Cambio de permiso/ruta; no implica cierre de otros permisos de configuración |
| Doble prefijo de rutas inventory-levels | OPEN: `/dashboard/dashboard/...` | Usar rutas backend actuales | `routes/web.php:3585–3595` define rutas con un único prefijo dashboard y permisos GET/POST explícitos | RESOLVED para definición de ruta y middleware | Sí | No por el prefijo; alcance branch sigue abierto | Cambio de rutas; validar consumidores al retomar QA de inventario |

## RESOLVED

- Maestro tenant compartido: decisión explícita y modelos coherentes. No cierra aislamiento operativo de inventario.
- Definición del permiso de `/dashboard/get/types/{subcategory_id}` corregida: OR de permisos Material/catálogo.
- Definición de rutas inventory-levels sin doble dashboard; GET exige `list_material|verInventario_material|update_material`, POST exige `update_material`. Cierre limitado al defecto de definición, no certificación funcional completa de inventario.

## IMPLEMENTED — QA PENDING

**Persistent variant size/color immutability: IMPLEMENTED — QA PENDING.** Existen dos defensas independientes del disabled visual: FormRequest y comprobación transaccional. Las variantes omitidas se conservan por la estructura del update; las nuevas combinaciones se crean por el camino sin `variant_id`.

QA aislado realizado el 2026-10-26 con PHP 7.3.33 y el UMR real:

- Transacción SQL `START TRANSACTION READ ONLY`, terminada mediante `ROLLBACK`; sesión efímera en memoria. Sin controlador, rutas HTTP, archivos de imagen, login ni escrituras de negocio.
- Tenant 1, company 5, variante persistida 43: identidad original no produce el error de inmutabilidad; manipular talla y manipular color manteniendo ID sí lo producen, por separado.
- Para manipulación se usó ID `-1`. Confirma ejecución del guard específico, pero no sustituye probar otra combinación válida del mismo tenant.
- Tenant 3: no se encontró variante persistida visible con Material asociado; no se crearon fixtures. QA correspondiente pendiente.
- Se evaluaron reglas y callback del request; no se afirma que el payload mínimo pase el resto de validaciones. No es QA de sesión Owner ni de middleware/autorización.
- El primer intento de conexión encontró error PDO 2014 antes de iniciar la prueba. El harness externo usó una conexión PDO con consultas bufferizadas, con la misma configuración local y la misma transacción readonly; no se cambió configuración del proyecto.

Pendientes: HTTP 422 autenticado, combinación alternativa válida, defensa del controlador con rollback y efectos sobre archivos, alta de nueva combinación y conservación de variantes omitidas. No se ejecutó `MaterialController::update()` sobre datos compartidos: además de escrituras SQL tiene efectos sobre imágenes que una transacción no revierte. SKU/barcode/estado/inventariable no quedan resueltos por esta prueba.

## FUNCTIONAL DECISION DEFINED — IMPLEMENTATION PENDING

El alcance operativo obligatorio es tenant contextual, company activa y almacenes de branch activa o generales de esa company. El filtro company, algunas validaciones de pertenencia y permisos ya existen. Falta completar branch/general en lectura, validación y escritura de niveles, además de los indicadores de List V2. TenantScope no aplica automáticamente company ni branch.

Stock permanece readonly en esta superficie. Min/max pertenecen al almacén; su persistencia actual por InventoryLevel/ubicación debe validarse contra esa granularidad funcional, sin reinterpretación en JavaScript. El owner no recibe una excepción de alcance operativo; su vista global será independiente.

Para List V2 se mantiene la clasificación específica **FUNCTIONAL DECISION DEFINED — BACKEND IMPLEMENTATION/VALIDATION PENDING**. No están cerrados `stock`, `stock_actual`, disponible, mínimos/máximos, resumen ni prioridad. El frontend consume valores del servidor y no los normaliza.

## INTENTIONAL BEHAVIOR — REVIEW PENDING

Retacería: **INTENTIONAL BEHAVIOR — HISTORICAL REVIEW PENDING**. Cambiar el tipo desde Material Edit propaga dimensiones y tipo a Items entered/exited. No debe impedirse desde frontend.

Esto es distinto de editar el catálogo en `TypescrapController::update`, que no ejecuta esa cascada. Que Item almacene dimensiones no demuestra que las dimensiones originales de movimientos sobrevivan a su posterior sobrescritura. Se mantienen revisión histórica y alcance company/branch; no se aprueba Save productivo completo.

## TECHNICAL DEBT — NON-BLOCKING

**Precio legacy:** Material Edit no renderiza `unit_price` ni `list_price`; `updateMaterial()` envía FormData del formulario y agrega imagen/variantes, no precio. MC todavía escribe ambos desde `input('unit_price', 0)`. Por tanto un submit ordinario puede ponerlos en cero y modificar indicadores de actualización de precio.

El propio MC:1608 identifica pricing legacy temporal. El campo aún tiene consumidores: `Material::getPriceFinalAttribute` (:297), payload de listado MC:3112, lecturas `priceBase/price_base` (:4071/:4107), flujo legacy de precios (:4317/:4351) y exportación (:4674). No es código muerto ni se demuestra inocuidad por existir listas de precios. La limpieza debe separar responsabilidades y verificar consumidores; no añadir un campo precio ni reenviar valores desde frontend para ocultar el problema.

SKU, barcode, active/inactive e inventariable conservan pertenencia/unicidad y contrato actual. Frontend muestra errores del servidor, sin bloqueos históricos adicionales. Su política completa sigue pendiente antes del cierre funcional productivo.

## PRODUCTION BLOCKERS

- Guardado de Material Edit que sobrescribe precio legacy ausente.
- Alcance operativo company/branch/general incompleto, incluida edición min/max.
- Política histórica incompleta de SKU, barcode, estado e inventariable.
- Conservación histórica y alcance de propagación de retacería pendientes.
- Inconsistencia pendiente de fuentes/cálculos de List V2.
- QA funcional pendiente de talla/color persistidos y variantes omitidas; revisión de código y prueba aislada no bastan para cierre.
- Hallazgos previos no afectados por la aclaración permanecen abiertos: `type_tax_id` ausente, reconstrucción de descuentos, campos ocultos por `$enabled`, contrato de `TipoVenta` y otros permisos/políticas de configuración. No se declaran resueltos por esta revisión.

Continuar frontend, hacer QA controlado, demostrar corrección backend y aprobar producción son cuatro decisiones distintas. Esta fase habilita continuidad visual dentro del contrato vigente; no añade bloqueos UI ni cambia el comportamiento de Guardar.

## NO LONGER A BUSINESS DECISION

- Producto se edita en Material Edit; precios se administran en listas de precios.
- Material y StockItem son maestro compartido del tenant.
- Inventario operativo: company activa, branch activa y almacenes generales de esa company; Principal no incluye Outlet.
- Existencias readonly; min/max por almacén, con contexto y permisos.
- Owner no amplía el alcance de esta superficie: tendrá vista global separada.
- Propagación entered/exited al cambiar retacería es intencional; revisar historia no equivale a cuestionar la intención.
- Identidad talla/color persistida es inmutable; otra combinación es nueva variante; omisión no elimina.
- El frontend no recalcula ni unifica indicadores de inventario de List V2.

Sí siguen pendientes la política completa de StockItems con historial, conservación histórica de dimensiones y detalles de normalización backend. Tampoco se deduce de estas decisiones una política nueva para `$enabled`, descuentos, TipoVenta o Material Create.

## Documentation scope and verification

Actualizadas las notas de Material Create, Material Edit, Material List V2, Tipo de retacería y Parámetros de Material. Los textos originales se conservan debajo de avisos fechados; no representan decisiones abiertas vigentes cuando esta matriz las sustituye expresamente. No se modificaron PHP, JS, Blade, CSS, rutas ni contratos. No hubo escrituras de negocio, commit ni cambios en Store Web/Cotizaciones.
