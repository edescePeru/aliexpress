# REAL NEXT — Configuración auxiliar de cotización

Fecha: 2026-11-14. Batch Roadmap E. Backend y consumidores sin cambios; ninguna mutación de negocio, carga de imagen ni commit.

## Domain Matrix

| Domain | Controller / Model | Views | Permission | Scope | Decision |
|---|---|---|---|---|---|
| Servicios predeterminados | WorkforceController / Workforce | workforce.index + partials.table; create/edit modal | manage_workforce + tenant.owner | TENANT, no company/branch | INTEGRATE WITH TECH DEBT |
| Categorías de equipos | CategoryEquipmentController / CategoryEquipment | categoryEquipment.index/eliminated; modales | *Category_defaultEquipment, detalle abajo | GLOBAL LEGACY — TECH DEBT | INTEGRATE WITH TECH DEBT |

## Functional Meaning

Workforce es un concepto reutilizable de servicio/mano de obra, con descripción, unidad, precio unitario y condición facturable. No es Worker ni un registro de RRHH. Sus consumidores copian información a EquipmentWorkforce al construir equipos/cotizaciones.

CategoryEquipment clasifica DefaultEquipment; contiene descripción e imagen. No equivale a Material Category. El listado presenta el contador existente de equipos relacionados, sin nuevas consultas decorativas ni navegación QA hacia sus consumidores operativos.

## Routes / Permissions

Todas las rutas pertenecen al dashboard autenticado con contexto/middleware existentes. No se cambiaron rutas ni permisos backend.

| Domain | List | Create | Edit | Delete | Restore |
|---|---|---|---|---|---|
| Workforce | manage_workforce | manage_workforce | manage_workforce | manage_workforce | No existe |
| CategoryEquipment | listCategory_defaultEquipment | createCategory_defaultEquipment | editCategory_defaultEquipment | destroyCategory_defaultEquipment | restoreCategory_defaultEquipment |

Categorías eliminadas requiere eliminatedCategory_defaultEquipment. Workforce agrega tenant.owner a todo su grupo.

| Method / URL | Name / propósito |
|---|---|
| GET /dashboard/configuracion/workforces-default | workforce.index |
| GET /dashboard/configuracion/workforces-default/data | workforce.data |
| POST /dashboard/configuracion/workforces-default | workforce.store |
| GET /dashboard/configuracion/workforces-default/{id}/edit | workforce.edit, JSON contextual |
| PUT /dashboard/configuracion/workforces-default/{id} | workforce.update |
| DELETE /dashboard/configuracion/workforces-default/{id} | workforce.destroy |
| GET /dashboard/categorias/equipos | categoryEquipment.index |
| GET /dashboard/categorias/equiposeliminados | categoryEquipment.eliminated |
| GET /dashboard/get/data/category/equipments/{numberPage} | datos activos |
| GET /dashboard/get/data/category/equipmentseliminated/{numberPage} | datos eliminados |
| GET /dashboard/get/category/equipment/typeahead | sugerencias |
| POST /dashboard/category/equipment/store | categoryEquipment.store |
| POST /dashboard/categorias/actualizar/{categoryEquipment} | categoryEquipment.update |
| DELETE /dashboard/categorias/equiposxeliminar/{id} | categoryEquipment.destroy |
| POST /dashboard/categorias/equiposxrestaurar/{id} | categoryEquipment.restore |
| GET /dashboard/categorias/editar/{categoryEquipment} | categoryEquipment.edit: stub vacío, NO usado por modal |

Los tres endpoints GET de datos/sugerencias de categorías no tienen permiso granular propio; conservar deuda de autorización existente. La UI usa @can para sus acciones, sin presentarlo como seguridad del endpoint.

## Scope

Workforce y UnitMeasure usan BelongsToTenant. El listado y edit usan query/findOrFail con ese scope. Company y branch no forman parte del catálogo; ver el mismo ID en dos companies del mismo tenant es el contrato actual.

CategoryEquipment no tiene tenant/company scope. Ambos Owners ven el mismo catálogo global. No se declara PASS isolation ni GLOBAL INTENTIONAL: su autoridad global legacy sigue siendo deuda. No hay lookup de parent en sus formularios.

## Dataset

Relevamiento actual mediante SELECT en transacción READ ONLY con rollback; contraste UI autenticado Owner EDESCE y Owner Gamarra. No fixtures DB.

| Domain | Count / IDs | Context |
|---|---|---|
| Workforce | 1 / [1] | EDESCE T1, C1 y C2 (tenant-wide) |
| Workforce | 0 / [] | Gamarra T3, C3 |
| UnitMeasure lookup | [1,2] | T1 |
| UnitMeasure lookup | [3] | T3; confirmado en DOM del formulario Gamarra |
| CategoryEquipment activos | 0 / [] | Global; mismo resultado EDESCE y Gamarra |
| CategoryEquipment eliminados | 0 / [] | Global; mismo resultado EDESCE y Gamarra |

Servicio 1: DESPACHO A AGENCIA; unidad 1; precio 0.00; billable=false. Sesiones UI: Owner EDESCE / EDESCE CONSULTORA E.I.R.L. / Local Principal y Owner Gamarra / Gamarra Moda / Local Principal. No se modificó contexto empresarial; al finalizar se restauró Owner EDESCE.

Binding Workforce probado invocando el controller real bajo contextos aislados de lectura: T1/C1 y T1/C2 edit(1)=200; T3/C3 edit(1)=404; edit(999999)=404 en los tres. Esto verifica binding, no sustituye una matriz HTTP por roles. UI edit(1) EDESCE cargó los valores reales y se canceló sin guardar.

## Fields

| Domain | Campos y validación existente |
|---|---|
| Workforce | description required/string/max255; unit_measure_id required/integer/exists; unit_price required/numeric/min0; billable required/boolean. Hidden ID y hooks originales, Select2 con dropdownParent modal, CSRF y errores Toastr preservados. |
| CategoryEquipment store | description required/unique global/string/max190; image nullable/file/jpg,png,jpeg/max10240 KB |
| CategoryEquipment update | description required/string; editImage image/jpg,jpeg,png,gif/max2048 KB. Diferencias respecto a create preservadas. |

Requests de categorías: StoreCategoryEquipmentsRequest y UpdateCategoryEquipmentRequest. Workforce valida inline. No se inventaron active/default/parent/currency, ni se cambió required, names, IDs, old(), hidden o payloads de formularios.

## Price Semantics

unit_price es importe unitario, no porcentaje. DB decimal(9,2), modelo float, control min=0/step=0.01 y presentación con dos decimales. La validación backend numeric no impone escala explícita. El maestro no define moneda ni inclusión/exclusión de IGV; no se añade símbolo PEN, conversión, fórmula ni PriceList.

QuoteSaleController guarda billable en EquipmentWorkforce y suma el total de esos conceptos independientemente del flag. Se reemplazó el helper engañoso que condicionaba la suma al flag por «Condición facturable del servicio». No se recalcularon importes ni se modificó consumidor alguno.

## Consumers

| Domain | Consumer | Usage |
|---|---|---|
| Workforce | QuoteController | lookup de servicios/unidades y composición de cotización |
| Workforce | QuoteSaleController | lookup y snapshots EquipmentWorkforce con precio/cantidad/total/billable |
| Workforce | DefaultEquipmentController | composición de equipos predeterminados |
| Workforce | ProformaController | lookup y creación de EquipmentWorkforce |
| CategoryEquipment | DefaultEquipmentController | clasificación/selección de parent del equipo |
| CategoryEquipment | ProformaController | catálogo de categorías para selección de equipos |

No se atribuye un contrato POS directo a este maestro. Quote, equipos, proformas, reservas, inventario y PDF quedaron fuera del QA.

## Soft Delete / Active

- Workforce: borrado físico, sin SoftDeletes, sin active ni restore.
- CategoryEquipment: SoftDeletes, listado eliminado y restore; sin active.
- No existe acción masiva en estas superficies; no se añadió ninguna.
- Store/update/delete/restore/upload: MUTATION QA PENDING. Ninguna acción ejecutada.

## Integrated Views

Workforce index/partial/modales create-edit y CategoryEquipment index/modales create-edit-delete + eliminated/restore. Venti Next Configuration CRUD: next-page-heading, next-page-toolbar, next-list-toolbar, next-list-summary, next-form-section y next-aux-modal-form. Tabla compacta, alineaciones semánticas, desplazamiento local, estados vacíos y errores diferenciados.

Allowlist explícita: workforce.index, categoryEquipment.index, categoryEquipment.eliminated. No wildcard ni endpoints AJAX. ALLOWLIST MATCH: YES; carga observada de admin/dist/css/venti.min.css y venti-next.css.

AJAX Workforce permanece intacto: html/current_page/last_page/total. Categorías conserva response y paginación currentPage/totalPages/totalFilteredRecords, búsqueda activa LIKE y eliminados exacta, sin normalizar contratos. Render adaptado de cards a filas manteniendo templates/data hooks. Descripción renderizada como texto; acciones opcionales toleran @can. Vacío no se confunde con error de carga.

Referencias corregidas: URLs relativas update/delete/restore ahora vienen de route() en data-url del modal; se preservan método e ID. Label de archivo IMG/PDF cambiado a Imagen porque backend sólo acepta imágenes. Typeahead conserva plugin y búsqueda; form-row/col evita que su wrapper rompa la fila del botón Buscar.

## Blocked Views

No se bloqueó un mantenedor completo. No se añadió ni allowlisteó una vista categoryEquipment.edit inexistente: el GET es stub, el formulario real usa datos de la fila. Edit poblado, restore poblado y paginación múltiple de categorías: PENDING DATASET (cero registros). Paginación múltiple Workforce: PENDING DATASET (un registro). No se certifica binding global por un GET vacío ni se ejecuta update para probarlo.

## Responsive

| Width | Workforce list/create | CategoryEquipment list/create | Global overflow |
|---|---|---|---|
| 320 | PASS | PASS | NONE |
| 375 | PASS | PASS | NONE |
| 390 | PASS | PASS | NONE |
| 576 | PASS | PASS | NONE |
| 768 | PASS | PASS | NONE |
| 992 | PASS | PASS | NONE |
| 1200 | PASS | PASS | NONE |
| 1440 | PASS | PASS | NONE |

Mediciones tras carga de datos y modal visible: documentElement.scrollWidth=innerWidth en todos los anchos. Tablas pueden desplazar localmente en móvil. Smoke adicional: edit real Workforce móvil, cancelación, eliminadas desktop/móvil y sesiones EDESCE/Gamarra. Búsqueda Workforce sin coincidencias devuelve estado vacío. Consola inspeccionada sin errores. No se forzaron respuestas de error ni validaciones de escritura.

Evidencia visual fuera del repo, en C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/: quote-aux-services-desktop.png, quote-aux-service-mobile.png, quote-aux-category-desktop.png. CSS nuevo: 0.

## Backend Sync Notes

Se mantienen las deudas ya descritas en REAL-NEXT-ROADMAP-E-SCREEN-DISCOVERY.md; no duplicar IDs:

1. Workforce exists:unit_measures,id no restringe tenant en escrituras. Lookup contextual no reemplaza validación backend. No certificar mutations hasta alineación.
2. CategoryEquipment GLOBAL catalog tech debt: sin aislamiento; no cambiarlo en JS.
3. Categorías AJAX sin permiso granular: familia AUTH-01. @can visual no cierra el finding.
4. GET categoryEquipment.edit stub; documentado, no inventar vista ni contrato nuevo.
5. Categorías vacías impiden QA poblado de edit/restore; no crear registros para suplirlo.

Revisitar cuando DEV cambie scope/permisos/validaciones, aparezca dataset de categorías o se autoricen mutaciones. No se cerraron findings de Quote ni se amplió alcance hacia Equipment.

## Validation / Final Status

- Blade compile: PASS (artisan view:cache, PHP 7.3).
- JS syntax: PASS en categoryEquipment/*.js y workforce/index.js.
- git diff --check: PASS.
- Backend, rutas, requests, modelos y consumers: UNCHANGED.
- Default Services: FRONTEND INTEGRATED WITH TECH DEBT.
- Equipment Categories: FRONTEND INTEGRATED WITH TECH DEBT.
- Mutations / uploads: QA PENDING. Sin commit.

Archivos: config/venti-next.php; resources/views/workforce/index.blade.php y partials/table.blade.php; resources/views/categoryEquipment/index.blade.php y eliminated.blade.php; public/js/categoryEquipment/index.js, indexEliminated.js, edit.js, delete.js, restore.js; este documento.
