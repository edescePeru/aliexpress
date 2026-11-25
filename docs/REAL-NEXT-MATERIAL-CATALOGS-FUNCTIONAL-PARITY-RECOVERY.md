# REAL NEXT — MATERIAL CATALOGS FUNCTIONAL PARITY RECOVERY

Fecha: 2026-11-25 (America/Lima). HEAD `3914020`, rama `feature/venti-next-frontend`. R0–R4 y demás cambios del working tree preservados. R5 no hace commit, push ni merge; R6 no iniciado.

## Resultado y unidad de conteo

11 catálogos revisados y modificados. Las 11 pérdidas originales FR-066…076 siguen siendo capacidades activas: se recuperan las 11 eliminaciones múltiples. Se descubren y recuperan otras 2 pérdidas: selección acumulada entre páginas de Modelos (FR-100) y Marcas (FR-101). Total 13 pérdidas activas, 13 recuperadas; 0 capacidades pendientes de recuperación dentro de este alcance. Ninguna exclusión legacy ni decisión de producto pendiente en estos once catálogos.

**FUNCTIONAL PARITY: RESTORED — FOCUSED QA. MUTATION QA: ISOLATED CONTRACT PASS / REAL SHARED-DB MUTATIONS NOT EXECUTED.** No se certifica concurrencia MySQL ni todo el CRUD por estas pruebas. QA-02 global permanece abierto. Backend/CSS: 0 cambios. Las operaciones de Retacerías del maestro Material y MAT-01/03/04/05 no se reabren.

## Evidencia Git y bloqueo original

- `8faf1ef^`: controles y handlers bulk alcanzables desde los once índices. Marcas/Modelos mantienen `selectedIds = new Set()`, eventos individuales y select-all sobre nodos filtrados; payload `Array.from(selectedIds)`. En Unidades aparece un Set residual sin uso: no se convierte en capacidad.
- `8faf1ef`: homologación introduce `data-backend-sync-open`, `aria-disabled`, tooltip y `disabled` en los once. Se conservan seis handlers detrás del gate y se retiran cuerpos bulk de Modelos, Marcas, Retacerías, Tallas y Colores. Se pierde también el Set funcional de los dos primeros.
- `a8582b9`: protección destroy explícita en rutas bulk antes desprotegidas. Tallas/Colores ya tenían permisos pero carecían del request dedicado. Los contratos históricos individuales `REAL-NEXT-MATERIAL-CONFIG-*-CRUD-CONTRACT.md` documentan autorización insuficiente, validación genérica y cascadas sin política/referencias.
- `ec5f6dd`: once `DeleteMultiple*Request` y once servicios `App\Services\Materials\Delete*`, validación tenant, transacciones, locks, selección completa y referencias. HEAD conserva estos contratos. R5 no vuelve a implementar seguridad en JS.
- `REAL-NEXT-MATERIAL-POST-MERGE-QA.md`, sección «Catálogos: seguridad sin deletes», ya auditó requests y dejó **BACKEND IMPLEMENTED — DESTRUCTIVE QA PENDING**. Esa cobertura pendiente no justificaba mantener los once gates después del cambio backend.
- Comparación `8faf1ef^ → 8faf1ef → entregas DEV → HEAD → working tree`: crear/editar, eliminar individual, búsqueda, paginación, modales y navegación permanecen. No hay export alcanzable en estos índices; las traducciones CSV/Excel/PDF de DataTables no constituyen botones ni handlers export. Category legacy apuntaba erróneamente al bulk de Subcategory: se conserva el endpoint Category corregido; no se restaura el error. Género conserva permisos `*_genero`, no los históricos `*_warrant` incorrectos.

## Contrato común actual

Ámbito legítimo **TENANT**, compartido entre companies del mismo tenant; no añadir company ni branch artificialmente. Modelos usan `BelongsToTenant`; request y servicio verifican tenant explícito. Grupo de rutas: auth, check.user.enabled, password.changed, tenant.context; middleware destroy por endpoint. Sin Owner bypass. El FormRequest `authorize=true` delega autorización al middleware, no elimina el gate.

POST `ids[]` + `_token`; no tenant/company en payload. `ids`: required,array,min:1,max:500; cada ID required,integer,min:1,distinct,exists restringido al tenant y deleted_at null en modelos soft. Color/Exampler son hard delete. Inexistentes, ajenos, repetidos, vacíos y selección mixta se rechazan íntegramente. Servicio vuelve a contar la selección bajo `DB::transaction`, `orderBy(id)` y `lockForUpdate`, y consulta referencias incluyendo historia antes de borrar. Excepción revierte la transacción; controller devuelve 422 con mensaje/errores; éxito 200 con message/deleted_count. No se salta silenciosamente IDs inválidos.

Referencias globales inspeccionadas por el servicio sólo para impedir borrar objetivos con dependencias: no exponen registros ajenos ni permiten modificarlos. Jerarquías corruptas con hijos de otro tenant se rechazan. Brand y MaterialType comprueban que la cascada no quedó incompleta; Category/Subcategory eliminan niveles de abajo arriba verificando resultados.

## Rutas, casing y contratos individuales

GET index tiene route name `<clave>.index`. Bulk sin nombre salvo `talla.deleteMultiple` y `color.deleteMultiple`. Todos POST `/dashboard/<clave>/delete-multiple`; controller `<Modelo>Controller::deleteMultiple`, request `DeleteMultiple<Plural>Request`, servicio `Delete<Plural>` (detallados abajo).

| Catálogo | URL local / route name | Blade real / JS | Request / servicio | Destroy permission | Referencias / semántica |
|---|---|---|---|---|---|
| Tipos de material | [materialtype.index](http://127.0.0.1:8080/dashboard/TiposMateriales) | `materialtype.index` / `public/js/materialtype/index.js` | `DeleteMultipleMaterialTypesRequest` / `DeleteMaterialTypes` | `destroy_materialType` | Material + subtipos históricos; cascada soft comprobada |
| Subtipos | [subtype.index](http://127.0.0.1:8080/dashboard/Subtipos) | `subtype.index` / `public/js/subtype/index.js` | `DeleteMultipleSubtypesRequest` / `DeleteSubtypes` | `destroy_subType` | Material histórico |
| Categorías | [category.index](http://127.0.0.1:8080/dashboard/Categorias) | `category.index` / `public/js/Category/index.js` | `DeleteMultipleCategoriesRequest` / `DeleteCategories` | `destroy_category` | Material en cualquier nivel + seasonal_promotions; jerarquía soft |
| Subcategorías | [subcategory.index](http://127.0.0.1:8080/dashboard/Subcategorias) | `subcategory.index` / `public/js/subcategory/index.js` | `DeleteMultipleSubcategoriesRequest` / `DeleteSubcategories` | `destroy_subcategory` | Material en cualquier nivel; jerarquía soft |
| Modelos | [exampler.index](http://127.0.0.1:8080/dashboard/Modelos) | `exampler.index` / `public/js/exampler/index.js` | `DeleteMultipleExamplersRequest` / `DeleteExamplers` | `destroy_exampler` | Material histórico; hard delete |
| Marcas | [brand.index](http://127.0.0.1:8080/dashboard/Marcas) | `brand.index` / `public/js/brand/index.js` | `DeleteMultipleBrandsRequest` / `DeleteBrands` | `destroy_brand` | Material + modelos; marca soft/modelos hard |
| Retacerías | [typescrap.index](http://127.0.0.1:8080/dashboard/Retacerías) | `typeScrap.index` / `public/js/typescrap/index.js` | `DeleteMultipleTypeScrapsRequest` / `DeleteTypeScraps` | `destroy_typeScrap` | Material + Items históricos |
| Unidades | [unitmeasure.index](http://127.0.0.1:8080/dashboard/Unidades) | `unitMeasure.index` / `public/js/unitmeasure/index.js` | `DeleteMultipleUnitMeasuresRequest` / `DeleteUnitMeasures` | `destroy_unitMeasure` | Material + StockItems + Workforce históricos |
| Géneros | [genero.index](http://127.0.0.1:8080/dashboard/generos) | `genero.index` / `public/js/genero/index.js` | `DeleteMultipleGenerosRequest` / `DeleteGeneros` | `destroy_genero` | Material histórico |
| Tallas | [talla.index](http://127.0.0.1:8080/dashboard/tallas) | `talla.index` / `public/js/talla/index.js` | `DeleteMultipleTallasRequest` / `DeleteTallas` | `destroy_talla` | Material + Variant, incluso inactivo/histórico |
| Colores | [color.index](http://127.0.0.1:8080/dashboard/colors) | `color.index` / `public/js/color/index.js` | `DeleteMultipleColorsRequest` / `DeleteColors` | `destroy_color` | Variant, incluso inactivo; hard delete |

Los controllers son MaterialTypeController, SubtypeController, CategoryController, SubcategoryController, ExamplerController, BrandController, TypescrapController, UnitMeasureController, GeneroController, TallaController y ColorController respectivamente. Case Git importante: `public/js/Category/index.js`, `resources/views/typeScrap/index.blade.php`, `resources/views/unitMeasure/index.blade.php`; las claves route siguen minúsculas.

## Matriz individual de capacidades

QA F = flujo frontend interceptado; QA I = request/servicio SQLite aislado; QA R = lectura actual readonly EDESCE/Gamarra. La recuperación no cierra otros findings del módulo.

| Catálogo | Capacidad | Vigencia | Bloqueo original | Contrato HEAD | Permiso | Scope | Referencias | Blocker actual | Recuperación | QA |
|---|---|---|---|---|---|---|---|---|---|---|
| Tipos de material | FR-066 Eliminar selección | ACTIVE | Autorización bulk insuficiente; validación/integridad | Request + DeleteMaterialTypes | destroy_materialType | Tenant | Material + subtipos históricos; cascada soft comprobada | Ninguno demostrado para recuperación | ACTIVE — FRONTEND GATE OBSOLETE → ACTIVE — RESTORED | F/I/R; mutación real pendiente |
| Tipos de material | Crear/editar, eliminar individual con modal, buscar/paginar, navegar | ACTIVE | No pérdida adicional demostrada | Contratos y enlaces actuales preservados | create/update/destroy/list_materialType según acción | Tenant | Sin cambios | No auditado fuera del alcance R5 | ACTIVE — ALREADY AVAILABLE | Inspección; sin store/update/delete real |
| Subtipos | FR-067 Eliminar selección | ACTIVE | Autorización bulk insuficiente; validación/integridad | Request + DeleteSubtypes | destroy_subType | Tenant | Material histórico | Ninguno demostrado para recuperación | ACTIVE — FRONTEND GATE OBSOLETE → ACTIVE — RESTORED | F/I/R; mutación real pendiente |
| Subtipos | Crear/editar, eliminar individual con modal, buscar/paginar, navegar | ACTIVE | No pérdida adicional demostrada | Contratos y enlaces actuales preservados | create/update/destroy/list_subType según acción | Tenant | Sin cambios | No auditado fuera del alcance R5 | ACTIVE — ALREADY AVAILABLE | Inspección; sin store/update/delete real |
| Categorías | FR-068 Eliminar selección | ACTIVE | Autorización bulk insuficiente; validación/integridad | Request + DeleteCategories | destroy_category | Tenant | Material en cualquier nivel + seasonal_promotions; jerarquía soft | Ninguno demostrado para recuperación | ACTIVE — FRONTEND GATE OBSOLETE → ACTIVE — RESTORED | F/I/R; mutación real pendiente |
| Categorías | Crear/editar, eliminar individual con modal, buscar/paginar, navegar | ACTIVE | No pérdida adicional demostrada | Contratos y enlaces actuales preservados | create/update/destroy/list_category según acción | Tenant | Sin cambios | No auditado fuera del alcance R5 | ACTIVE — ALREADY AVAILABLE | Inspección; sin store/update/delete real |
| Subcategorías | FR-069 Eliminar selección | ACTIVE | Autorización bulk insuficiente; validación/integridad | Request + DeleteSubcategories | destroy_subcategory | Tenant | Material en cualquier nivel; jerarquía soft | Ninguno demostrado para recuperación | ACTIVE — FRONTEND GATE OBSOLETE → ACTIVE — RESTORED | F/I/R; mutación real pendiente |
| Subcategorías | Crear/editar, eliminar individual con modal, buscar/paginar, navegar | ACTIVE | No pérdida adicional demostrada | Contratos y enlaces actuales preservados | create/update/destroy/list_subcategory según acción | Tenant | Sin cambios | No auditado fuera del alcance R5 | ACTIVE — ALREADY AVAILABLE | Inspección; sin store/update/delete real |
| Modelos | FR-070 Eliminar selección | ACTIVE | Autorización bulk insuficiente; validación/integridad | Request + DeleteExamplers | destroy_exampler | Tenant | Material histórico; hard delete | Ninguno demostrado para recuperación | ACTIVE — FRONTEND GATE OBSOLETE → ACTIVE — RESTORED | F/I/R; mutación real pendiente |
| Modelos | Crear/editar, eliminar individual con modal, buscar/paginar, navegar | ACTIVE | No pérdida adicional demostrada | Contratos y enlaces actuales preservados | create/update/destroy/list_exampler según acción | Tenant | Sin cambios | No auditado fuera del alcance R5 | ACTIVE — ALREADY AVAILABLE | Inspección; sin store/update/delete real |
| Marcas | FR-071 Eliminar selección | ACTIVE | Autorización bulk insuficiente; validación/integridad | Request + DeleteBrands | destroy_brand | Tenant | Material + modelos; marca soft/modelos hard | Ninguno demostrado para recuperación | ACTIVE — FRONTEND GATE OBSOLETE → ACTIVE — RESTORED | F/I/R; mutación real pendiente |
| Marcas | Crear/editar, eliminar individual con modal, buscar/paginar, navegar | ACTIVE | No pérdida adicional demostrada | Contratos y enlaces actuales preservados | create/update/destroy/list_brand según acción | Tenant | Sin cambios | No auditado fuera del alcance R5 | ACTIVE — ALREADY AVAILABLE | Inspección; sin store/update/delete real |
| Retacerías | FR-072 Eliminar selección | ACTIVE | Autorización bulk insuficiente; validación/integridad | Request + DeleteTypeScraps | destroy_typeScrap | Tenant | Material + Items históricos | Ninguno demostrado para recuperación | ACTIVE — FRONTEND GATE OBSOLETE → ACTIVE — RESTORED | F/I/R; mutación real pendiente |
| Retacerías | Crear/editar, eliminar individual con modal, buscar/paginar, navegar | ACTIVE | No pérdida adicional demostrada | Contratos y enlaces actuales preservados | create/update/destroy/list_typeScrap según acción | Tenant | Sin cambios | No auditado fuera del alcance R5 | ACTIVE — ALREADY AVAILABLE | Inspección; sin store/update/delete real |
| Unidades | FR-073 Eliminar selección | ACTIVE | Autorización bulk insuficiente; validación/integridad | Request + DeleteUnitMeasures | destroy_unitMeasure | Tenant | Material + StockItems + Workforce históricos | Ninguno demostrado para recuperación | ACTIVE — FRONTEND GATE OBSOLETE → ACTIVE — RESTORED | F/I/R; mutación real pendiente |
| Unidades | Crear/editar, eliminar individual con modal, buscar/paginar, navegar | ACTIVE | No pérdida adicional demostrada | Contratos y enlaces actuales preservados | create/update/destroy/list_unitMeasure según acción | Tenant | Sin cambios | No auditado fuera del alcance R5 | ACTIVE — ALREADY AVAILABLE | Inspección; sin store/update/delete real |
| Géneros | FR-074 Eliminar selección | ACTIVE | Autorización bulk insuficiente; validación/integridad | Request + DeleteGeneros | destroy_genero | Tenant | Material histórico | Ninguno demostrado para recuperación | ACTIVE — FRONTEND GATE OBSOLETE → ACTIVE — RESTORED | F/I/R; mutación real pendiente |
| Géneros | Crear/editar, eliminar individual con modal, buscar/paginar, navegar | ACTIVE | No pérdida adicional demostrada | Contratos y enlaces actuales preservados | create/update/destroy/list_genero según acción | Tenant | Sin cambios | No auditado fuera del alcance R5 | ACTIVE — ALREADY AVAILABLE | Inspección; sin store/update/delete real |
| Tallas | FR-075 Eliminar selección | ACTIVE | Request genérico; validación dedicada pendiente | Request + DeleteTallas | destroy_talla | Tenant | Material + Variant, incluso inactivo/histórico | Ninguno demostrado para recuperación | ACTIVE — FRONTEND GATE OBSOLETE → ACTIVE — RESTORED | F/I/R; mutación real pendiente |
| Tallas | Crear/editar, eliminar individual con modal, buscar/paginar, navegar | ACTIVE | No pérdida adicional demostrada | Contratos y enlaces actuales preservados | create/update/destroy/list_talla según acción | Tenant | Sin cambios | No auditado fuera del alcance R5 | ACTIVE — ALREADY AVAILABLE | Inspección; sin store/update/delete real |
| Colores | FR-076 Eliminar selección | ACTIVE | Request genérico; validación dedicada pendiente | Request + DeleteColors | destroy_color | Tenant | Variant, incluso inactivo; hard delete | Ninguno demostrado para recuperación | ACTIVE — FRONTEND GATE OBSOLETE → ACTIVE — RESTORED | F/I/R; mutación real pendiente |
| Colores | Crear/editar, eliminar individual con modal, buscar/paginar, navegar | ACTIVE | No pérdida adicional demostrada | Contratos y enlaces actuales preservados | create/update/destroy/list_color según acción | Tenant | Sin cambios | No auditado fuera del alcance R5 | ACTIVE — ALREADY AVAILABLE | Inspección; sin store/update/delete real |
| Modelos | FR-100 Selección acumulada entre páginas | ACTIVE | Set y eventos retirados en 8faf1ef | IDs seleccionados, no nueva query | destroy_exampler | Tenant | Mismo servicio | Ninguno | ACTIVE — RESTORED | F: payload acumulado, filtro/página |
| Marcas | FR-101 Selección acumulada entre páginas | ACTIVE | Set y eventos retirados en 8faf1ef | IDs seleccionados, no nueva query | destroy_brand | Tenant | Mismo servicio | Ninguno | ACTIVE — RESTORED | F: payload acumulado, filtro/página |

## Selección y frontend

- MaterialType, Subtype, Category, Subcategory, TypeScrap, UnitMeasure, Genero: preservan select-all sobre nodos DataTables filtrados y envío de `.row-checkbox:checked` presentes en DOM. Con fixture 12, página 1 envía 10 y página 2 envía 2. No se amplía a todos los resultados al construir payload.
- Brand/Exampler: restauran Set histórico. Select-all incorpora todos los nodos del filtro actual, como antes; selección individual puede acumular páginas. Con fixture 12 envía los 12 seleccionados, también desde página 2. Se reconstruye el check visual al dibujar y se limpia el Set después de éxito. No existe selección implícita de registros nunca marcados.
- Talla/Color: paginación backend; sólo página cargada; reload/page change limpia selección. Botón disabled cuando no hay selección sigue siendo válido: se quita únicamente el bloqueo Backend Sync obsoleto. POST/CSRF y confirmación original se restauran; recarga al aceptar éxito.
- Selección vacía en DataTables mantiene alerta; Talla/Color mantiene botón deshabilitado. Cancelar no envía POST; éxito recarga tabla, 403/422 no se convierten en éxito ni en empty state. Rechazo por referencias conserva texto contractual.
- Cinco Blades DataTables (MaterialType, Subtype, Category, Subcategory, UnitMeasure) ahora serializan `auth()->user()->getAllPermissions()` para coincidir con `@can`/middleware. El array role-only del controller omitía permisos directos; se usa la API existente sin alterar permisos ni controllers. Era deuda previa a la homologación, no una nueva pérdida atribuida a 8faf1ef ni un nuevo finding AUTH-01. Brand/Exampler/TypeScrap/Genero/Talla/Color ya usan `can()` y no requieren este ajuste.
- Row dropdown `…` queda intacto, con `fa-pen` / `fa-trash` + `next-row-action-item-icon`, estilo destructivo existente y focus. Bulk permanece en toolbar; no nuevo menú, CSS, librería ni allowlist.

## Dataset actual, sólo lectura

Captura PHP del controller/Blade y endpoints GET en transacción MySQL `START TRANSACTION READ ONLY`, revertida al finalizar; Audit desactivado únicamente en ese proceso QA. No cambios persistentes de cuentas/permisos. IDs completos del modelo activo, no sólo la primera página:

| Catálogo | EDESCE tenant 1 / C1 / B1 IDs | Gamarra tenant 3 / C3 / B4 IDs |
|---|---|---|
| Tipos de material | [1, 2, 3, 4] | [5, 6] |
| Subtipos | [1, 2, 3, 4] | [5, 6] |
| Categorías | [2, 3, 4, 5, 6, 7, 8] | [9, 10] |
| Subcategorías | [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18] | [19, 20] |
| Modelos | [1, 2, 3, 4, 5, 6, 7, 8, 10] | [9, 11, 12] |
| Marcas | [1, 6, 3, 4, 5, 9, 2, 7, 11] | [12, 10] |
| Retacerías | [1, 2] | [] |
| Unidades | [1, 2] | [3] |
| Géneros | [1, 5] | [4, 6] |
| Tallas | [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13] | [14] |
| Colores | [1, 3, 4, 5, 6, 7, 9, 10, 12, 13, 14] | [] |

Requests reales: selección vacía/inexistente/duplicada/mixta rechazada en ambos contextos; propios válidos donde existen. IDs ajenos comprobados cuando hay dataset: falta contraparte Gamarra para Retacerías/Colores, cubiertos por fixtures SQLite explícitos, no se inventa dataset. Cuenta Owner no representa bypass: el middleware real se ejecutó con relaciones de permisos en memoria vacías (403) y sólo permisos directos (200) en los once. Son pruebas de middleware en proceso, no POST HTTP real.

## QA y límites

- `tests/Feature/MaterialCatalogDeletionContractTest.php`: **26 tests / 325 assertions PASS**, PHP 7.3.33, conexión SQLite `:memory:` comprobada. Once requests/servicios: IDs, selección mixta, referencias históricas/ajenas, éxito soft/hard, rollback al fallar segunda eliminación. Cuatro jerarquías: hijo extranjero, referencia a descendiente histórico y cascada incompleta. No prueba de locks concurrentes MySQL ni copia completa del esquema productivo.
- Chrome headless aislado; todas las peticiones resueltas con route interception, sin `continue` a red real. HTML/DTO reales leídos y fixtures marcados SIMULADO para operaciones. Ningún contrato real creado/editado/borrado.
- QA navegador **PASS**: 88 combinaciones listado/ancho (11 × 320/375/390/576/768/992/1200/1440),22 confirmaciones (11 × 320/1440),11 capturas antes +22 dropdowns después +22 confirmaciones. Cero overflow global y cero errores JS. 55 POST interceptados del runner principal +4 de selección acumulada/filtro;0 peticiones mutadoras reales. Selección vacía/individual/múltiple, cancelar, CSRF, URL/payload exactos,200/403/422, rechazo por referencias y recarga simulada comprobados. Perfiles EDESCE/Gamarra y relaciones de permisos direct-only/none en memoria; sin modificar usuarios reales. Caso Retacerías: error de helper ausente detectado durante recuperación, corregido y runner final PASS.
- Blade compile **PASS**, sintaxis de los11 JS **PASS**, `git diff --check` **PASS**. Revisión visual directa de capturas desktop/mobile y confirmaciones, sin nuevo diseño.
- Backend modificado NO; CSS nuevo/modificado 0; frontend modifica únicamente once índices Blade/JS. Ninguna mutación compartida, export, documento ni proveedor.

## Evidencia verificable

Artifacts locales (no fixtures productivos): `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`.

- `r5-spec.json`, snapshots Git `r5-*-8faf1efparent-*`, `r5-*-8faf1ef-*`, `r5-*-HEAD-*`.
- `r5-read.php`, `r5-data.json`, `r5-{edesce,gamarra,direct,none}-*.html`: lecturas y renders identificados.
- `r5-browser.cjs`, `r5-browser-results.json`: requests interceptados, permisos, tamaños, errores.
- `r5-<catálogo>-375.png` y `r5-<catálogo>-1440.png`: dropdowns reales con tabla de scroll local. No controla escritorio ni navegador personal.

## Cobertura anterior y Backend Sync Notes

Los once fueron auditados previamente (contratos Material Config + Material Post-Merge QA). DEV sí corrigió autorización/validación/integridad bulk mediante a8582b9/ec5f6dd. Gates obsoletos retirados individualmente; cinco handlers y dos selecciones acumuladas recuperados. No hay nuevo riesgo backend reproducible en este alcance ni nuevo ticket DEV. AUTH-01 y QA-02 se referencian sin duplicarlos ni cerrarlos globalmente.

[RESOLVED FOR FRONTEND RECOVERY] FR-066…076, FR-100/101. [OPEN QA] mutación HTTP real no ejecutada; concurrencia MySQL pendiente de suite backend. No volver a bloquear producto únicamente por estas exclusiones de QA.

## Archivos modificados por R5

- `resources/views/materialtype/index.blade.php`
- `public/js/materialtype/index.js`
- `resources/views/subtype/index.blade.php`
- `public/js/subtype/index.js`
- `resources/views/category/index.blade.php`
- `public/js/Category/index.js`
- `resources/views/subcategory/index.blade.php`
- `public/js/subcategory/index.js`
- `resources/views/exampler/index.blade.php`
- `public/js/exampler/index.js`
- `resources/views/brand/index.blade.php`
- `public/js/brand/index.js`
- `resources/views/typeScrap/index.blade.php`
- `public/js/typescrap/index.js`
- `resources/views/unitMeasure/index.blade.php`
- `public/js/unitmeasure/index.js`
- `resources/views/genero/index.blade.php`
- `public/js/genero/index.js`
- `resources/views/talla/index.blade.php`
- `public/js/talla/index.js`
- `resources/views/color/index.blade.php`
- `public/js/color/index.js`
- `tests/Feature/MaterialCatalogDeletionContractTest.php`
- `docs/REAL-NEXT-MATERIAL-CATALOGS-FUNCTIONAL-PARITY-RECOVERY.md`
- `docs/REAL-NEXT-FUNCTIONAL-PARITY-RECOVERY-BACKLOG.md`
- `docs/REAL-NEXT-DEV-TEAM-FINDINGS-BACKLOG.md` (addendum, sin cierre global)
- `docs/REAL-NEXT-DEV-PARALLEL-WORK-2026-11-22.md` (addendum)

## Capturas antes/después y confirmación

- [MaterialType antes,1440](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r5-before-materialtype-1440.png)
- [MaterialType después,1440](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r5-materialtype-1440.png)
- [Dropdown después,375](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r5-materialtype-375.png)
- [Marcas confirmación,320](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r5-confirm-brand-320.png)
- [Colores confirmación,320](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r5-confirm-color-320.png)
- [Resultados principales](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r5-browser-results.json)
- [Resultados confirmaciones/selección](C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/r5-extra-results.json)

Antes usa HTML y JS HEAD capturados; después usa working tree. Ambos usan los mismos assets compartidos congelados. Los datos de las pruebas operativas llevan etiqueta SIMULADO; no son nuevos registros DB. Las capturas de dropdowns usan DTO de lectura real. Scroll horizontal sólo dentro de tablas cuando se necesita.
