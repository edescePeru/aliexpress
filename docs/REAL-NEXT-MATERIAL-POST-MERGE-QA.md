# REAL NEXT — Material Post-Merge QA & Contract Alignment

Fecha: 2026-11-05. Base: HEAD `f367675`, rama `feature/venti-next-frontend`. Precondición verificada en fase anterior: 545 migraciones aplicadas, cero pendientes. Esta fase no inició Sales List ni modificó backend.

## Resultado y alcance de aprobación

| Superficie | Estado | Alcance / límite |
| --- | --- | --- |
| Material Create | MATERIAL CREATE — FINAL QA PASSED | Lectura/render, catálogos, defaults, configuración y validación no persistente. No se creó Material. Ausencia de default y old fiscal se probaron mediante render Blade, no cambiando configuración persistente del navegador |
| Material Edit frontend | POST-MERGE FRONTEND ALIGNED | Contratos vigentes y corrección mínima del inventario expandido responsive |
| Material Edit Save | MATERIAL EDIT SAVE STILL BLOCKED FOR PRODUCTION | Historia SKU/barcode/estado/inventariable, retacería, granularidad multinivel y QA transaccional ampliado pendientes |
| Variantes persistidas | PERSISTED VARIANT SIZE/COLOR IMMUTABILITY — QA PASSED | Base válida; talla/color alterados rechazados específicamente por el request; update real con una variante conserva las omitidas bajo rollback |
| Material List V2 | FINAL QA PASSED — READ PATH | Dataset actual EDESCE/Gamarra; no aprobación universal de escenarios de locales/reservas ausentes del dataset |
| Separar paquete | SEPARAR PAQUETE — STILL BLOCKED FOR FRONTEND | No ejecutado; mantiene deuda de fuente stock/ownership legacy |
| Material Parameters | MATERIAL PARAMETERS — FINAL QA PASSED | Lectura, permisos, empresa sin configuración, dependencias, snapshots y store con rollback; no configuración persistida |
| Once catálogos | FRONTEND READY / BACKEND IMPLEMENTED — DESTRUCTIVE QA PENDING | Validadores y contratos revisados. Bulk permanece deshabilitado; no deletes, cascadas ni atomicidad destructiva ejecutados |

No interpretar FINAL QA PASSED de lectura como aprobación de creación/guardado productivo ni del conjunto completo de reglas históricas. No se creó un dataset artificial para afirmar aislamiento entre locales inexistentes.

## Contextos y método

| Cuenta/rol | Tenant | Company | Branch | Ejecución |
| --- | --- | --- | --- | --- |
| Owner EDESCE | 1 | 1 EDESCE CONSULTORA | 1 Local Principal | Login real; Create, Edit material 13, List V2, Parámetros; servicios/requests y rollback |
| Owner EDESCE | 1 | 5 COMPANY OWNER TEST | 6 PRINCIPAL | Cambio de contexto real; Create bloqueado sin configuración; Parámetros muestra inicialización con unidad obligatoria |
| Owner Gamarra | 3 | 3 Gamarra Moda | 4 Local Principal | Login real; Create, Edit material 19, List V2, inventario detallado, Parámetros; Edit ajeno 13 devuelve 404 |
| Usuario existente 2 / cajero | 1 | 1 | 1 | Auth en memoria, router Laravel con middleware: list_material permitido, sin list_materialType ni config_materialSetting. No login por contraseña ni cambio de permisos |
| Platform Admin autorizado | Sin contexto operativo | — | — | Invocación no persistente de política/controller: rechazo 403 de superficie operativa |

Navegador: Laravel real `http://127.0.0.1:8080`, computer-use. Se recuperó una pestaña tras un timeout de automatización; no se atribuye ese timeout a backend. Una medición inmediata durante resize puede mostrar posiciones transitorias; el menú móvil se comprobó nuevamente una vez estable.

Pruebas backend externas al repositorio: PHP 7.3.33, sesión/cache array, requests/servicios reales. Lecturas bajo transacción READ ONLY. Regresiones de update, impuesto/default, parámetros y min/max bajo transacciones con rollback en finally. No archivos de imagen ni eventos externos enviados; no se invocó store Material, deletes, Separar paquete, ventas ni movimientos de stock.

Las pruebas directas no equivalen a HTTP donde se indique FormRequest/service. El primer intento de router en CLI requirió inicializar el Http Kernel para registrar `web`; tras corregir el harness los endpoints devolvieron los estados documentados. No se hizo un fix ficticio en la aplicación por ese error del harness.

## Matriz de hallazgos

| Finding | Estado previo | Fix backend | QA realizado | Estado final | Bloquea frontend | Bloquea producción | Revisit Trigger |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Create lookups/permisos | Parcial | OR permiso Material/catálogo | Cadena navegador en ambos tenants; usuario 2 con list_material: cuatro rutas 200 sin list_materialType | RESOLVED para permiso incorrecto histórico | No | No por ese defecto | Cambios de middleware/roles |
| Create parent ajeno | QA pendiente | findOrFail sobre modelos scoped | Padres de tenant 3 desde tenant 1 en categoría/subcategoría/tipo/marca: ModelNotFound | QA PASSED | No | No por este rechazo | Cambios de scopes/padres |
| Tax activo/default | QA pendiente | Colección activa/global y default explícito | Navegador: 1/2/4/3, default 1; sin default en transacción: null; old fiscal 2 renderizado | QA PASSED | No | QA store fuera de fase | Política fiscal/default |
| Configuración inexistente | Implementado | 409 + pantalla required | Company 5 en navegador y prueba controller previa; parámetros iniciales con unidad | QA PASSED | No | Requiere configurar empresa antes de operar | Empresa nueva/config inválida |
| unit_price/list_price update | Fix estático | Eliminadas asignaciones | update real 200 bajo transacción externa: precios idénticos; rollback restaura material | RESOLVED | No | No por este defecto | Cambios en update/pricing |
| Descuentos omitidos | Fix estático | Update no borra/reconstruye descuentos | Revisión del update + colección intacta en regresión; material probado tiene 0 descuentos | RESOLVED código; regresión con descuentos poblados pendiente | No | No restaurar blocker de borrado inexistente | Cambios de update; dataset con descuentos |
| Ausentes/deshabilitados | QA pendiente | apply sólo presentes habilitados; stripDisabled | Modelo en memoria conserva atributos; payload disabled retirado; update con campos omitidos no los solicita | QA PASSED política; escenario histórico amplio pendiente | No | Según histórico | Config/jerarquía/campos nuevos |
| Jerarquía efectiva | QA pendiente | Valida hijo conservado cuando cambia padre | Modelo de prueba en memoria con subcategoría conservada incompatible: rechazo específico | QA PASSED | No | No por este defecto | Cambios de jerarquía |
| Tax Edit | QA pendiente | Requerido; inactivo sólo histórico propio | Base activa válida; ausente/inexistente rechazados; cambiar a inactivo rechazado; conservarlo pasa con rollback | QA PASSED | No | QA consumidores fiscales | Cambios de impuestos |
| Talla/color persistidos | QA pendiente | Guarda identidad en request/controller | Material 13/variante 43: baseline sin errores; talla y color cambiados por separado rechazados | QA PASSED | No | QA de guardado integral aún pendiente | Identidad/reglas variantes |
| Variante omitida | QA pendiente | No delete implícito | update real de 1/8 variantes: siguen 8; otras 7 idénticas; rollback | QA PASSED | No | No por omisión | Cambiar semántica de colección |
| Combinación nueva | QA pendiente | Nuevo variant_id ausente | Talla 1/color 3 no existentes para material 13: request válido; combinación duplicada rechazada | QA PASSED validación; alta no ejecutada | No | QA persistencia nueva | Creación real autorizada |
| Scope inventario | Implementado parcial | MaterialInventoryScope | Warehouses/contextos existentes; ID inválido rechazado; Gamarra no accede a Edit 13 | IMPLEMENTED — QA PENDING para matriz branch ampliada | No lectura actual | Sí para certificación integral | Datos de Principal/Outlet y niveles de otras ubicaciones |
| Min/max vs stock/cost | QA pendiente | sync sólo guarda umbrales | Transacción: sólo min_alert/max_alert/updated_at cambian; qty/reservas/costos/location extra no cambian | QA PASSED acotado | No | Casos concurrentes/multinivel pendientes | Inventario con varias locations |
| Varias locations mismo warehouse | Riesgo de granularidad | Service por id de InventoryLevel, request aún deduplica warehouse | Código request líneas ~1558: rechaza warehouse repetido; dataset no tiene pares múltiples | BACKEND SYNC REQUIRED — granularidad | No para lectura; sí ese guardado | Sí para ese escenario | Unificar validación con granularidad persistida |
| V2 fuentes/indicadores | QA pendiente | CompanyCatalog + StockReadModel | Listas 14/2; local 0 y general separado; variantes/null → Ver Inv.; fórmulas en memoria | FINAL QA PASSED — READ PATH acotado | No | Falta matriz con inventario local real | Datos locales/reservas/umbrales múltiples |
| Parámetros autorización | QA pendiente | config_materialSetting | Owner render; usuario 2 recibe 403; Platform sin contexto rechazado | QA PASSED | No | No por este defecto | Nuevos roles/soporte contextual |
| Parámetros dependencias/version | QA pendiente | normalize/snapshot/store | Unidad ausente rechazada; stale rechazado; store 302 con dependencias/version nueva y rollback | QA PASSED | No | Concurrencia no probada | Edición paralela real |
| Bulk once catálogos | Implementado | Requests + services comunes | Vacío/inexistente/mixto/duplicado rechazados en 11; ajenos en 9 con datos aptos | BACKEND IMPLEMENTED — DESTRUCTIVE QA PENDING | No | Sí cierre destructivo | DB aislada para deletes/cascadas/rollback |
| SKU/barcode/estado/inventariable | Deuda histórica | Sin política histórica nueva | No se cambió contrato ni se añadieron locks UI | TECHNICAL DEBT — NON-BLOCKING FOR FRONTEND | No | Sí cierre de Save | Política histórica + pruebas |
| Retacería | Intencional | Sin cambio | No se ejecutó propagación entered/exited | INTENTIONAL BEHAVIOR — REVIEW PENDING | No | Sí historia/alcance | Conservación histórica y company/branch |
| Separar paquete | Bloqueado | Sin alineación | Sólo revisión; no ejecución | STILL BLOCKED FOR FRONTEND | Sí ese flujo | Sí | Inventario/ownership/permisos backend |
| Inventario expandido a 320 | Regresión observada | No backend | Antes scrollWidth 357/clientWidth 305; después 305/305; headers vuelven a mostrarse | RESOLVED frontend | No | No | Cambio de tabla responsive compartida |

## Create: catálogos y restauración

EDESCE: APPLE → IPHONE 17; CELULARES → SMARTPHONE → METALICO → subtipos. Network observó 200 en `/dashboard/get/subcategories/8`, `/get/exampler/6`, `/get/types/18`, `/get/subtypes/3`. Gamarra: MARCA NUEVA → MODELO NUEVO; TOMATODOS → METALICO → ACERO → INOXIDABLE. Los catálogos de Género, Unidad, Talla, Color y Retacería se entregan con la vista; Gamarra carece de colores y tipos de retacería, no se rellenó con datos de EDESCE.

Los impuestos son catálogo global de plataforma: «impuesto ajeno al tenant» no es una categoría aplicable; se probaron inexistente/inactivo y no se inventó TenantScope fiscal. Sin default se comprobó default null del controller y old('type_tax_id')=2 mediante render Blade dentro de rollback. Esto no certifica restauración general de cada select dependiente tras un POST: el flujo actual de Material usa AJAX y conserva controles al fallar; no se envió Material Create. Ese ensayo completo de error/old de todas las entradas queda como límite, no como fix backend demostrado.

No hay cuenta no-owner con create_material/update_material apta en el dataset. El usuario limitado 2 tiene list_material, sin permiso propio de tipos: las cuatro rutas auxiliares devuelven 200 mediante router real. El mismo usuario recibe 403 en Parámetros. No se crearon usuarios ni se ampliaron permisos para conseguir un PASS.

## Inventario y semántica List V2

- Material/StockItem siguen tenant-wide; CompanyCatalog determina habilitación para company. No se cambió scope ni se amplió owner.
- Los cinco warehouses de la BD tienen branch_id null. No existe un warehouse Outlet/local apto para demostrar aislamiento positivo Principal vs Outlet; company 5 sí tiene dos branches, pero comparten únicamente su almacén general.
- No hay múltiples InventoryLevels por stock_item_id + warehouse_id. Request aún mantiene prohibición por warehouse repetido; service guarda por ID. El frontend ahora muestra ubicación explícita y conserva ID por fila. No se homologa «umbral por warehouse» con «umbral por location».
- Un warehouse inexistente/manipulado en variantes devuelve error de pertenencia. location_id no es campo editable del contrato: un campo extra no selecciona otra ubicación; sync conserva la ubicación del nivel validado server-side. No afirmar un 422 por ese extra si realmente se ignora.
- stock_current/stock_actual son físico local del simple; general se presenta por separado, no se suma. Null de variantes/múltiples StockItems/umbrales se conserva visualmente como Ver Inv., nunca cero inventado.
- Gamarra material 19: listado local 0/general 10; modal general muestra físico 10/reservado 0/disponible 10/min 5/max 100. Stock/costos son readonly; Edit permite min/max según permiso update_material.
- metrics() probado con colecciones en memoria, sin fingir que son registros reales: Sin inventario registrado, Sin disponibilidad, Por agotarse, Sobre máximo y Aceptable. Ejemplo: físico 3/reserva 1 → disponible 2. Para variante devuelve null; no se fabrica agregado.
- List V2 no añade columnas de reservado/disponible o prioridad ajenas al diseño aprobado. Respeta DTO y detalle; los badges de rotación siguen siendo rotación, no se reinterpretan como prioridad de stock.

## Catálogos: seguridad sin deletes

Unidad, Categoría, Subcategoría, Tipo Material, Subtipo, Género, Tallas, Colores, Marcas, Modelos y Tipo Retacería conservan bulk deshabilitado. Se validaron requests, no se llamaron services delete. Vacío, inexistente, colección mixta y duplicado fallaron en los once. IDs ajenos reales fallaron en nueve; no había datos de otro tenant aptos en Colores/Tipo Retacería. No se cuentan estas dos ausencias como prueba de aislamiento superada.

Dependencias históricas, cascadas, locks y rollback se revisaron en código contra la reauditoría vigente, sin cerrar atomicidad por mera inspección. **BACKEND IMPLEMENTED — DESTRUCTIVE QA PENDING**: requiere DB aislada; no se hicieron deletes sobre BD compartida.

## Regresión visual y corrección

Edit expandido a 320 px desbordaba 52 px. La tabla externa pasa a bloques por el patrón responsive, pero su fila de inventario permanecía table-row. Además los selectores descendientes ocultaban el thead y alteraban tbody de la tabla interna.

Corrección de compatibilidad, sin rediseño:

1. Toggle añade/quita `d-block d-xl-table-row` únicamente cuando el inventario está abierto; conserva d-none y aria-expanded.
2. Celda wrapper usa `d-block d-xl-table-cell`; mantiene colspan, IDs y payload.
3. Tres selectores existentes de venti-next.css pasan a hijos directos (`> tbody`, `> colgroup`, `> thead`), para que el patrón de tarjetas sólo afecte a su tabla principal.

**CSS nuevo: 0 reglas, 0 declaraciones, 0 tokens, 0 breakpoints.** Sí se modificó el alcance de tres selectores compartidos existentes en dos reglas. No se afirma «CSS intacto». Create mantiene la misma estructura principal y se comprobó después del ajuste; no se modificó el proyecto baseline congelado.

| Ancho | Create | Edit variantes expandido tras fix | Edit simple Gamarra | List V2 Gamarra | Parámetros EDESCE |
| --- | --- | --- | --- | --- | --- |
| 320 | Sin overflow | Sin overflow (305/305) | Sin overflow | Sin overflow | Sin overflow |
| 375 | Sin overflow | Sin overflow | Sin overflow | Sin overflow | Sin overflow |
| 390 | Sin overflow | Sin overflow | Sin overflow | Sin overflow | Sin overflow |
| 576 | Sin overflow | Sin overflow | Sin overflow | Sin overflow | Sin overflow |
| 768 | Sin overflow | Sin overflow | Sin overflow | Sin overflow | Sin overflow |
| 992 | Sin overflow | Sin overflow | Sin overflow | Sin overflow | Sin overflow |
| 1200 | Sin overflow | Sin overflow | Sin overflow | Sin overflow | Sin overflow |
| 1440 | Sin overflow | Sin overflow | Sin overflow | Sin overflow | Sin overflow |

Create se midió en ambos tenants; Edit variante en EDESCE y simple en Gamarra. La matriz son mediciones DOM de ancho; no equivale a 40 comparaciones screenshot pixel-perfect. Inspección visual adicional: Create desktop, modal Marca móvil (abre/Cancelar/cierra), Edit móvil, listado/filtros/menú de columnas. Menú de columnas estable a 375: left 12.5/right 331.5 dentro del viewport; Escape cierra. No errores/warnings de consola en las sesiones inspeccionadas. No se completó nuevamente una comparación screenshot con servidor del baseline; se conservaron sus patrones y no se introdujo lenguaje visual nuevo.

## Validaciones y archivos

- `node --check public/js/material/edit.js`: correcto.
- PHP 7.3.33 `artisan view:cache`: correcto.
- `git diff --check`: correcto.
- Rollbacks comprobados en Material/Parámetros; min/max se ejecutó en finally rollback. No commit.
- Archivos funcionales: `public/js/material/edit.js`, `resources/views/material/edit.blade.php`, `public/admin/dist/css/venti-next.css`.
- Documentación: este informe; notas vigentes en contratos Create/Edit/List/Parámetros, auditoría central de configuraciones y once contratos de catálogos. Se preservan secciones históricas.
- `REAL-NEXT-BACKEND-REAUDIT-LATEST.md` ya era un archivo no versionado al comenzar esta fase; no se atribuye su creación a este trabajo.

## Próxima recomendación

La siguiente pantalla nueva puede ser **Sales List Read-only Integration**, manteniendo sus propios límites de datos/roles. No se inició en esta fase. Para cerrar Material Save productivamente, backend debe alinear la validación multinivel y completar política histórica; QA requiere datos de warehouse local/otras branches y ambiente aislado para borrados/concurrencia. No reabrir como blockers la asignación de precio eliminada ni la supuesta eliminación de descuentos que ya no existe.
