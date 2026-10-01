# REAL NEXT 5 — Material Configuration CRUD Audit

Fecha de auditoría: 2026-09-26

Estado: análisis de contrato y propuesta de patrón. No se ejecutaron operaciones de escritura, actualización ni eliminación sobre datos.

## Fuentes y criterio

Esta auditoría toma como fuentes de verdad:

- `AGENTS.md` y `docs/VENTI-NEXT-COMPONENT-PATTERNS.md` para reglas transversales;
- los contratos aprobados `REAL-NEXT-MATERIAL-CREATE-CONTRACT.md`, `REAL-NEXT-MATERIAL-EDIT-CONTRACT.md` y `REAL-NEXT-MATERIAL-LIST-CONTRACT.md`;
- rutas, controllers, Form Requests, modelos, Blade y JavaScript actuales de `aliexpress` para el contrato funcional real.

Todos los endpoints auditados heredan el grupo `/dashboard` con middleware `auth`, `check.user.enabled`, `password.changed` y `tenant.context`. Salvo Parámetros, cada operación principal añade además un middleware `permission:*` específico. El menú no es una frontera de autorización.

## Resultado ejecutivo

- Los once catálogos de datos son **tenant-scoped** mediante `BelongsToTenant` y `TenantScope`. No son company-scoped ni globales en el código actual.
- Parámetros (`MaterialDetailSetting`) es **tenant + company-scoped**: además del scope tenant, consulta y persiste por `company_id` del `TenantContext`.
- Nueve módulos usan el mismo esqueleto legacy: página index + AJAX que trae toda la colección tenant + DataTables 1.10.20 en cliente + páginas separadas de crear/editar + confirmación Bootstrap para delete individual.
- Tallas y Colores usan páginas separadas para crear/editar, pero su listado es AJAX paginado en servidor con JavaScript propio; no usan DataTables.
- Parámetros no es un CRUD convencional: es una única pantalla de checkboxes con POST síncrono y dependencias automáticas.
- Los endpoints `store` son consumidores compartidos por Material Create/Edit mediante Auxiliary Modals. Sus nombres, payloads, respuestas JSON y permisos no deben romperse al modernizar las pantallas de catálogo.
- El patrón visual puede ser único, pero la capa de datos debe conservar dos adaptadores: DataTables legacy y paginación AJAX de Tallas/Colores.
- No se recomienda iniciar la implementación destructiva hasta cerrar los `BACKEND SYNC OPEN` de permisos bulk, permisos de Género y política de cascadas.

## Matriz principal

| Módulo | Tipo | Scope | Index | Create | Edit | Delete | Permisos | Relaciones | Riesgo |
|---|---|---|---|---|---|---|---|---|---|
| Unidad de medida | A — simple | Tenant | `unitmeasure.index` / `GET Unidades`; DataTables AJAX `GET all/unitmeasure` | `unitmeasure.create` + `unitmeasure.store` | `unitmeasure.edit` + `unitmeasure.update` | Individual + bulk; soft delete | `list/create/update/destroy_unitMeasure` | Referenciada por Material | Medio: bulk sin middleware de permiso; delete no comprueba uso |
| Tipo de retacería | C — especial | Tenant | `typescrap.index` / `GET Retacerías`; DataTables AJAX `GET all/typescraps` | `typescrap.create` + `typescrap.store` | `typescrap.edit` + `typescrap.update` | Individual + bulk; soft delete; bloquea si hay materiales | `list/create/update/destroy_typeScrap` | Material; campos `length`, `width` | Alto: bulk sin middleware; semántica dimensional propia |
| Categorías | A — raíz de jerarquía | Tenant | `category.index` / `GET Categorias`; DataTables AJAX `GET all/categories` | `category.create` + `category.store` | `category.edit` + `category.update` | Individual + bulk; soft delete en cascada | `list/create/update/destroy_category` | Padre de Subcategoría; relación directa con Material | Crítico: bulk sin middleware y JS llama por error al bulk de Subcategoría; cascada sin bloqueo por materiales |
| Subcategorías | B — padre requerido | Tenant | `subcategory.index` / `GET Subcategorias`; DataTables AJAX `GET all/subcategories` | `subcategory.create` + `subcategory.store`; variante `subcategory.store.individual` | `subcategory.edit` + `subcategory.update` | Individual + bulk; soft delete en cascada | `list/create/update/destroy_subcategory` | Pertenece a Categoría; padre de Tipo Material; relación directa con Material | Alto: bulk sin middleware; create admite arreglo; cascada sin bloqueo por materiales |
| Tipo Materiales | B — padre requerido | Tenant | `materialtype.index` / `GET TiposMateriales`; DataTables AJAX `GET all/materialtypes` | `materialtype.create` + `materialtype.store` | `materialtype.edit` + `materialtype.update` | Individual + bulk; soft delete en cascada | `list/create/update/destroy_materialType` | Pertenece a Subcategoría; padre de Subtipo; relación directa con Material | Alto: bulk sin middleware; edit muestra `name` en el campo descripción; cascada sin bloqueo por materiales |
| SubTipos | B — padre requerido | Tenant | `subtype.index` / `GET Subtipos`; DataTables AJAX `GET all/subtypes` | `subtype.create` + `subtype.store` | `subtype.edit` + `subtype.update` | Individual + bulk; soft delete | `list/create/update/destroy_subType` | Pertenece a Tipo Material; relación directa con Material | Alto: bulk y endpoint hijo sin middleware; delete no comprueba uso |
| Género | A — simple | Tenant | `genero.index` / `GET generos`; DataTables AJAX `GET all/generos` | `genero.create` + `genero.store` | `genero.edit` + `genero.update` | Individual + bulk; soft delete | `list/create/update/destroy_genero` | Referenciado por Material | Crítico: JS consulta permisos `*_warrant`; guards Blade comentados; acciones quedan incoherentes |
| Tallas | C — especial | Tenant | `talla.index` / `GET tallas`; AJAX paginado `talla.data` | `talla.create` + `talla.store` | `talla.edit` + `talla.update` | Individual + bulk; soft delete; bloquea si hay variantes | `list/create/update/destroy_talla` | Variant; `short_name` | Medio: flujo distinto a DataTables; regla referencial debe conservarse |
| Colores | C — especial | Tenant | `color.index` / `GET colors`; AJAX paginado `color.data` | `color.create` + `color.store` | `color.edit` + `color.update` | Individual + bulk; hard delete; bloquea si hay variantes | `list/create/update/destroy_color` | Variant; `code` hexadecimal y `short_name` | Alto: hard delete; menú muestra Crear bajo permiso de listar; regla referencial propia |
| Marcas | A — raíz de jerarquía | Tenant | `brand.index` / `GET Marcas`; DataTables AJAX `GET all/brands` | `brand.create` + `brand.store` | `brand.edit` + `brand.update` | Individual + bulk; soft delete de marca, hard delete de modelos | `list/create/update/destroy_brand` | Padre de Modelo; Material puede referenciar Modelo | Crítico: bulk sin middleware; al borrar marca elimina modelos y pone `materials.exampler_id = null` |
| Modelos (`Exampler`) | B — padre requerido | Tenant | `exampler.index` / `GET Modelos`; DataTables AJAX `GET all/examplers` | `exampler.create` + `exampler.store` | `exampler.edit` + `exampler.update` | Individual + bulk; hard delete | `list/create/update/destroy_exampler` | Pertenece a Marca; referenciado por Material | Alto: bulk sin middleware; delete modifica materiales (`exampler_id = null`) |
| Parámetros de detalles | C — configuración | Tenant + Company | `settings.material-details.index` | No create separado; POST `settings.material-details.store` hace `updateOrCreate` | Misma pantalla | No existe delete ni bulk | No hay middleware de permiso en rutas; menú usa `enable_materialSetting` y un child guard incorrecto `list_exampler` | Activa secciones y fuerza dependencias | Crítico: autorización de rutas abierta a cualquier usuario del grupo dashboard; no es CRUD tabular |

## Inventario técnico por módulo

Las rutas de esta sección son relativas a `/dashboard`.

### Unidad de medida

- Controller: `UnitMeasureController`.
- Blade: `unitMeasure/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- JS: `public/js/unitmeasure/index.js`, `create.js`, `edit.js`.
- Requests: `StoreUnitMeasureRequest`, `UpdateUnitMeasureRequest`, `DeleteUnitMeasureRequest`.
- Campos: `name` requerido y único por tenant; `description` opcional; update/delete usan `unitMeasure_id`.
- Datos: `GET all/unitmeasure`, colección tenant completa serializada para DataTables.
- Delete: `POST unitmeasure/destroy`; bulk `POST unitmeasure/delete-multiple`.

### Tipo de retacería

- Controller: `TypescrapController`.
- Blade: `typeScrap/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- JS: `public/js/typescrap/index.js`, `create.js`, `edit.js`.
- Requests: `StoreTypeScrapRequest`, `UpdateTypeScrapRequest`, `DeleteTypeScrapRequest`.
- Campos: `name` requerido y único por tenant; `length` y `width` requeridos, numéricos, rango `0..99999.99`; update/delete usan `typeScrap_id`.
- Datos: `GET all/typescraps`, colección tenant completa para DataTables.
- Delete: individual y bulk son atómicos respecto de dependencias: si algún tipo está asociado a Material, responde 422 y no elimina el lote.

### Categorías

- Controller: `CategoryController`.
- Blade: `category/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- JS: `public/js/Category/index.js`, `create.js`, `edit.js`.
- Requests: `StoreCategoryRequest`, `UpdateCategoryRequest`, `DeleteCategoryRequest`.
- Campos: `name` requerido y único por tenant; `description` opcional; update/delete usan `category_id`.
- Datos: `GET all/categories`; hijo AJAX `GET get/subcategories/{category_id}`.
- Delete: soft-delete de Category y cascada hacia Subcategory → MaterialType → Subtype. No hay bloqueo por Material.
- Defecto confirmado: `public/js/Category/index.js` envía el bulk a `/dashboard/subcategory/delete-multiple`, no a `/dashboard/category/delete-multiple`.

### Subcategorías

- Controller: `SubcategoryController`.
- Blade: `subcategory/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- JS: `public/js/subcategory/index.js`, `create.js`, `edit.js`.
- Requests: `StoreSubcategoryRequest`, `UpdateSubcategoryRequest`, `DeleteSubcategoryRequest`. Existe `StoreSubcategoryIndividualRequest`, pero tiene `authorize() = false` y no se usa.
- Create real: `category_id` + `subcategories[]` (mínimo uno), con `subcategories.*.name` y `subcategories.*.description`. La unicidad es tenant + category + name.
- `subcategory.store.individual` reutiliza `StoreSubcategoryRequest` y delega en `store`; no tiene un contrato request independiente.
- Update real: `subcategory_id`, `category_id`, `name`, `description`.
- Delete: soft-delete y cascada a MaterialType → Subtype; no bloquea por Material.

### Tipo Materiales

- Controller: `MaterialTypeController`.
- Blade: `materialtype/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- JS: `public/js/materialtype/index.js`, `create.js`, `edit.js`.
- Requests: `StoreMaterialTypeRequest`, `UpdateMaterialTypeRequest`, `DeleteMaterialTypeRequest`.
- Campos: `subcategory_id`, `name` requerido y único por tenant + subcategory, `description`; update/delete usan `materialtype_id`.
- Datos: `GET all/materialtypes`; hijo AJAX `GET get/types/{subcategory_id}`.
- Delete: soft-delete y cascada a Subtype; no bloquea por Material.
- Defecto confirmado: el Blade edit carga `materialtype->name` como valor de `description`.

### SubTipos

- Controller: `SubtypeController`.
- Blade: `subtype/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- JS: `public/js/subtype/index.js`, `create.js`, `edit.js`.
- Requests: `StoreSubtypeRequest`, `UpdateSubtypeRequest`, `DeleteSubtypeRequest`.
- Campos: `material_type_id`, `name` requerido y único por tenant + material type, `description`; update/delete usan `subtype_id`.
- Datos: `GET all/subtypes`; hijo AJAX `GET get/subtypes/{type_id}`.
- Delete: soft-delete sin comprobación explícita de Materials.

### Género

- Controller: `GeneroController`.
- Blade: `genero/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- JS: `public/js/genero/index.js`, `create.js`, `edit.js`.
- Requests: `StoreGeneroRequest`, `UpdateGeneroRequest`, `DeleteGeneroRequest`.
- Campos: `name` requerido y único por tenant; `description` opcional; update/delete usan `genero_id`.
- Datos: `GET all/generos`, colección tenant completa para DataTables.
- Delete: soft delete individual y bulk. El bulk sí tiene middleware `destroy_genero`.
- Defecto confirmado: la columna acciones comprueba `update_warrant` y `destroy_warrant`, no permisos de Género; además los guards Blade de Crear y del modal delete están comentados.

### Tallas

- Controller: `TallaController`.
- Blade: `talla/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- JS: `public/js/talla/index.js` y `public/js/talla/form.js`.
- Requests: `StoreTallaRequest`, `UpdateTallaRequest`, `DeleteTallaRequest`.
- Campos: `name` requerido y único por tenant; `short_name` opcional; `description` opcional; update/delete usan `talla_id`.
- Datos: `GET all/tallas` (`talla.data`) acepta `page`, `search`, `per_page`; `per_page` permitido: 10, 25 o 50. Ordena y pagina en servidor.
- Delete: individual y bulk bloquean con 422 si existe alguna Variant; el bulk no hace eliminación parcial.

### Colores

- Controller: `ColorController`.
- Blade: `color/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- JS: `public/js/color/index.js` y `public/js/color/form.js`.
- Requests: `StoreColorRequest`, `UpdateColorRequest`, `DeleteColorRequest`.
- Campos: `name` requerido y único por tenant; `short_name` requerido y único por tenant; `code` opcional con formato `#RRGGBB`; update/delete usan `color_id`.
- Datos: `GET colors/data` (`color.data`) acepta `page`, `search`, `per_page`; `per_page` permitido: 10, 25 o 50. Ordena y pagina en servidor.
- Delete: hard delete individual y bulk; ambos bloquean con 422 si existe alguna Variant y el bulk no hace eliminación parcial.

### Marcas

- Controller: `BrandController`.
- Blade: `brand/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- JS: `public/js/brand/index.js`, `create.js`, `edit.js`.
- Requests: `StoreBrandRequest`, `UpdateBrandRequest`, `DeleteBrandRequest`.
- Campos: `name` requerido y único por tenant; `comment` opcional; update/delete usan `brand_id`.
- Datos: `GET all/brands`; hijo AJAX `GET get/exampler/{brand_id}`.
- Delete: por cada Exampler de la marca, pone `Material.exampler_id` en null, elimina físicamente el Exampler y luego hace soft-delete de Brand. Bulk replica el mismo comportamiento.

### Modelos (`Exampler`)

- Controller: `ExamplerController`.
- Blade: `exampler/index.blade.php`, `create.blade.php`, `edit.blade.php`.
- JS: `public/js/exampler/index.js`, `create.js`, `edit.js`.
- Requests: `StoreExamplerRequest`, `UpdateExamplerRequest`, `DeleteExamplerRequest`.
- Campos: `brand_id`, `name` requerido y único por tenant + brand, `comment`; update/delete usan `exampler_id`.
- Datos: `GET all/examplers`, colección tenant completa con relación `brand` para DataTables.
- Delete: pone `Material.exampler_id` en null y elimina físicamente el Exampler; bulk replica el comportamiento.

### Parámetros

- Controller/modelo: `MaterialDetailSettingController` / `MaterialDetailSetting`.
- Blade: `materialDetailSetting/index.blade.php`. No tiene JS propio ni Form Request.
- Flujo: una página con formulario POST síncrono `enabled_sections[]`; `updateOrCreate` por `tenant_id + company_id`.
- Claves válidas: `unit_measure`, `brand`, `exampler`, `genero`, `talla`, `color`, `perecible`, `category`, `subcategory`, `material_type`, `subtype`, `typescrap`.
- Dependencias automáticas: Subcategoría habilita Categoría; Modelo habilita Marca; Tipo Material habilita Categoría + Subcategoría; Subtipo habilita toda la cadena; Talla o Color habilita ambos.
- No hay delete, bulk delete, DataTables ni create/edit separados.

## Contrato común real

### Alcance multitenant

- `UnitMeasure`, `Typescrap`, `Category`, `Subcategory`, `MaterialType`, `Subtype`, `Genero`, `Talla`, `Color`, `Brand` y `Exampler` usan `BelongsToTenant`.
- El global scope añade `where tenant_id = TenantContext::tenantIdOrNull()` cuando existe contexto. Las consultas `::all()`, `findOrFail()`, relaciones y listados observados quedan filtrados por tenant.
- Los Form Requests validan IDs padres y unicidad dentro del tenant actual.
- Ninguno de esos once catálogos filtra por `company_id`: se comparten entre las compañías del tenant según el contrato actual.
- Parámetros añade `forCompany(TenantContext::companyId())`; es el único módulo company-scoped de esta auditoría.
- No se encontraron catálogos globales dentro del alcance auditado.

### Flujos de crear y editar

- Todos los catálogos usan **página separada**, no modal, para su CRUD administrativo.
- Los nueve módulos legacy envían `FormData` por AJAX desde `create.js`/`edit.js`; Tallas y Colores lo hacen desde un `form.js` compartido entre create/edit.
- Store/update devuelven JSON y URL de retorno; no se debe convertir el flujo a submit síncrono sin una decisión funcional.
- Material Create/Edit reutiliza los stores de Unidad, Marca, Modelo, Género, Talla, Categoría, Subcategoría individual, Color, Tipo Material, Subtipo y Tipo de retacería dentro de Auxiliary Modals. La modernización del CRUD administrativo debe conservar esos endpoints como API compartida.

### Endpoints AJAX padre/hijo

- Categoría → Subcategorías: `GET get/subcategories/{category_id}`.
- Subcategoría → Tipos: `GET get/types/{subcategory_id}`.
- Tipo Material → Subtipos: `GET get/subtypes/{type_id}`.
- Marca → Modelos: `GET get/exampler/{brand_id}`.
- Actualmente Subtipos, Categorías y Marcas tienen endpoints hijo sin middleware de permiso propio; permanecen dentro del grupo autenticado/tenant, pero requieren definición de permiso.

## DataTables y listados

### DataTables legacy

DataTables real: **1.10.20**, integración Bootstrap 4 y extensión Responsive cargada.

Módulos: Unidad de medida, Tipo de retacería, Categorías, Subcategorías, Tipo Materiales, SubTipos, Género, Marcas y Modelos.

- El controller ejecuta `->get()` y Yajra serializa la colección; en frontend no existe `serverSide: true`. Búsqueda, orden y paginación ocurren en el navegador sobre toda la colección tenant.
- El page length queda en el default de DataTables (10); no hay `lengthMenu` explícito, aunque la UI usa el texto “Mostrar _MENU_ registros”.
- Búsqueda, info, paginación y language strings están duplicados en cada archivo JS.
- `aaSorting: []` evita un orden inicial de DataTables, aunque los endpoints ya ordenan mayormente por nombre.
- La extensión Responsive se carga, pero no hay configuración `responsive` explícita; el Blade también envuelve la tabla en `.table-responsive`.
- Las acciones son botones amarillo/rojo inline generados en cada renderer.
- El checkbox de selección y bulk delete están replicados por módulo.

### Listados especiales

Tallas y Colores no usan DataTables. Sus endpoints hacen búsqueda y paginación en servidor, aceptan 10/25/50 registros y el JS construye tabla, estados vacíos, info y paginación.

El patrón visual compartido no debe sustituir DataTables ni forzar Tallas/Colores a DataTables durante una migración visual. Debe ofrecer el mismo DOM visual y dos adaptadores de datos compatibles con el contrato existente.

## Shared CRUD Pattern

Nombre: **Material Configuration CRUD**.

### 1. Page Header

- Eyebrow `MATERIALES`.
- Título plural del catálogo y breadcrumb consistente `Materiales / Configuraciones / [Catálogo]`.
- Sin controles duplicados en el sidebar y en la cabecera.
- CTA primario `Nuevo` sólo con permiso create. En mobile ocupa el ancho disponible si el patrón Venti Next ya lo indica.

### 2. Operational List surface

- Reutilizar el patrón aprobado de Material List: superficie única, toolbar, tabla compacta y footer de resultados.
- Toolbar: búsqueda a la izquierda; selector de cantidad/filtros sólo si el adaptador los soporta; `Nuevo` como acción primaria; acción bulk destructiva oculta/deshabilitada hasta que haya selección y sólo con permiso destroy.
- No duplicar la búsqueda nativa visible de DataTables y una búsqueda Venti. La toolbar Venti debe controlar la búsqueda DataTables mediante su API.
- Empty state dentro del cuerpo de tabla, con CTA sólo si el usuario puede crear.

### 3. Tabla y alineación

- Checkbox/ID corto/código/estado/acciones: center.
- Nombre, descripción, comentario y relaciones textuales: left.
- Largo/ancho y otros números: center.
- Moneda, si una excepción futura la incorpora: right.
- Header y body deben compartir alineación.
- Acciones en columna final, no sortable, centrada y con ancho estable.
- Reutilizar `.table-responsive` y la corrección aprobada del Row Actions Dropdown; scroll horizontal local, sin overflow global.

### 4. Row Actions Dropdown

- Un ellipsis por fila.
- Orden: `Editar`; separador; `Eliminar` al final con tratamiento destructivo.
- Renderizar únicamente acciones autorizadas. Si no hay acciones, no mostrar un menú vacío.
- Usar el patrón compartido con posicionamiento/dropup dinámico ya aprobado; no hacks por fila.
- Las únicas acciones adicionales observadas son las derivadas de bulk delete; no hay acciones de dominio por fila que requieran otro patrón.

### 5. Paginación y adaptadores

- `DataTablesAdapter`: conserva DataTables 1.10.20, `ajax`, sorting, search, info y pagination. Centraliza `language`, layout, `lengthMenu` y render de acciones sin cambiar el endpoint.
- `PagedAjaxAdapter`: conserva el contrato de Tallas/Colores (`page`, `search`, `per_page`) y presenta los mismos controles visuales.
- Ambos adaptadores deben producir la misma toolbar, empty state, tabla, footer y responsive.
- No activar server-side DataTables ni cambiar payloads en la fase visual; eso sería una migración funcional separada.

### 6. Create/Edit

- Mantener páginas separadas para los CRUD administrativos, porque ése es el flujo real de todos los catálogos.
- Reutilizar Complex Form ligero: Page Header, una superficie, labels arriba, controles existentes, validación inline, `Cancelar` y `Guardar`.
- Para relaciones padre, Select2 actual y opciones tenant-scoped; no inventar filtros company-scoped.
- Conservar IDs, names, `data-url`, FormData, respuestas JSON y redirects.
- Auxiliary Modal se reserva para Material Create/Edit, donde ya está aprobado; no convertir todo el CRUD a modal sólo por uniformidad visual.

### 7. Eliminación

- Confirmación destructiva única y reutilizable; nombre del registro en el mensaje; foco inicial en Cancelar; botón destructivo explícito.
- Individual y bulk deben mostrar literalmente el mensaje 422 del backend, especialmente para Retacería, Tallas y Colores.
- No hacer optimistic removal antes de respuesta exitosa.
- No habilitar bulk en un módulo hasta que su ruta tenga middleware destroy y su endpoint frontend sea correcto.
- No ejecutar deletes en QA; validar apertura, foco, copy, payload preparado y cancelación.

### 8. Responsive

- Desktop: toolbar en una fila cuando haya espacio; tabla compacta.
- Mobile: búsqueda full width, acciones principales apiladas, `.table-responsive` con scroll local y dropdown no recortado.
- Mantener columnas contractuales; priorizar scroll horizontal antes que ocultar relaciones necesarias.
- QA mínimo por implementación: 1280×720 y 390×844; añadir 1600×900/1024×768 para formularios según los contratos Material ya aprobados.

## Exceptions

1. **Subcategoría create** admite varias subcategorías en una sola petición. El patrón de formulario debe soportar filas repetibles; el Auxiliary Modal “individual” sigue enviando el mismo array contractual.
2. **Tipo de retacería** necesita layout numérico para Largo/Ancho y mensajes de restricción por Material.
3. **Tallas** usa `short_name`, lista AJAX paginada propia y restricción por Variant.
4. **Colores** requiere `short_name`, input hexadecimal, picker/preview y hard delete restringido por Variant.
5. **Marca/Modelo** tienen semántica destructiva especial: borrar Marca elimina Modelos y desvincula Materials; borrar Modelo lo desvincula de Materials.
6. **Parámetros** no usa Operational List. Debe reutilizar Page Header y una superficie de configuración con checkboxes, helper de dependencias, Guardar/Cancelar, pero conservar POST síncrono y `updateOrCreate` company-scoped.
7. **DataTables vs Paged AJAX** es una excepción técnica, no visual. El usuario debe percibir un solo patrón.

## Backend Sync Open

### Bloqueantes de autorización

1. **Bulk delete sin middleware destroy**: Unidad, Tipo de retacería, Categoría, Subcategoría, Tipo Material, SubTipo, Marca y Modelo. El controller filtra por tenant, pero eso no reemplaza autorización. Género, Talla y Color sí están protegidos.
2. **Parámetros sin middleware de permiso** en GET y POST. `enable_materialSetting` sólo afecta al menú; cualquier usuario que alcance la ruta dentro del grupo dashboard puede abrir/guardar según el routing actual.
3. **Género usa permisos de Cédula/Warrant en JS** (`update_warrant`, `destroy_warrant`) y tiene guards Blade comentados. Debe sincronizarse antes de reutilizar el renderer de acciones.
4. **Endpoints hijo sin permiso explícito**: `get/subcategories`, `get/subtypes`, `get/exampler`. Definir si deben usar permiso list del catálogo hijo o quedar autorizados por el permiso del formulario consumidor.
5. **Menú Parámetros**: el nodo usa `enable_materialSetting`, pero el enlace hijo está guardado por `list_exampler`; las rutas no aplican ninguno.

### Bloqueantes funcionales

6. **Categoría bulk apunta al endpoint incorrecto**: el JS llama a Subcategoría. No debe activarse visualmente hasta corregir contrato frontend.
7. **MaterialType edit rellena descripción con el nombre**. Debe corregirse antes del polish del formulario para no ocultar una pérdida/sobrescritura de datos.
8. **Política de cascada Categoría/Subcategoría/Tipo/Subtipo**: hoy permite soft-delete aunque existan Materials asociados. Confirmar si debe bloquear, desvincular o conservar catálogos eliminados como referencia histórica.
9. **Política Marca/Modelo**: hoy borrar desvincula Materials y hard-deletea Modelos. Confirmar que esta pérdida de clasificación es la decisión de negocio aprobada.
10. **Unidad y Género referenciados**: no hay bloqueo explícito al eliminar. Confirmar política referencial antes de exponer una experiencia bulk homogénea.
11. **Color y Modelo no usan SoftDeletes**, mientras los demás catálogos sí. Es contrato actual, pero debe aprobarse como política permanente antes de uniformar mensajes de “eliminar”.
12. **`StoreSubcategoryIndividualRequest` inválido/no usado**: existe con `authorize() = false`; el endpoint individual usa `StoreSubcategoryRequest`. Documentar si se elimina como deuda o se convierte en contrato real, sin cambiar ahora el payload aprobado de Material.

### No abierto por scope

El scope técnico no es ambiguo en el estado actual: los once catálogos son tenant-scoped y Parámetros es tenant + company-scoped. Cualquier cambio para hacer catálogos company-scoped sería una decisión nueva de backend, no una corrección visual.

## Menú lateral: `Materiales > Configuraciones`

- El árbol está condicionado por `configGeneral_materialSetting`, pero el header `@canany` no incluye Género, Talla, Color ni Parámetros; puede ocultar el conjunto aunque existan permisos hijos.
- La navegación añade un nivel redundante por catálogo (`Catálogo > Listar / Crear`) cuando el patrón objetivo ya ofrece `Nuevo` en el listado.
- Nombres inconsistentes: `Unidad de Medida`, `Tipo Materiales`, `SubTipos`, `Listar categorias`, `Listar Tipo retacería`.
- Iconos y colores de puntos no siguen una semántica única.
- Active states usan múltiples sections/yields no uniformes.
- Crear Color aparece dentro del bloque de `list_color` sin guard `create_color` propio.
- Parámetros tiene el cruce de permisos `enable_materialSetting` / `list_exampler` ya señalado.
- Recomendación futura: un enlace por catálogo al index, CTA `Nuevo` dentro de la pantalla, nombres normalizados y active state por route name. No se modifica en esta fase.

## Recommended Implementation Order

El orden propuesto prioriza un piloto simple, luego relaciones, después excepciones y deja las decisiones destructivas de mayor riesgo para cuando exista sincronización backend.

1. **Unidad de medida** — piloto del patrón visual; antes de habilitar bulk, añadir autorización y decidir dependencia con Material.
2. **Categorías** — raíz del árbol; corregir endpoint bulk y aprobar política de cascada antes de delete.
3. **Subcategorías** — valida relación padre y formulario repetible/endpoint individual compartido con Material.
4. **Tipo Materiales** — valida segundo nivel dependiente; corregir valor de descripción en edit.
5. **SubTipos** — cierra la cadena jerárquica y permite consolidar endpoints hijo.
6. **Género** — visualmente simple, pero después de corregir permisos Warrant/Genero.
7. **Tallas** — primer adaptador Paged AJAX y regla Variant.
8. **Colores** — reutiliza el adaptador de Tallas y añade picker/preview/hard delete.
9. **Marcas** — raíz de la segunda jerarquía, sólo tras aprobar la política de desvinculación.
10. **Modelos** — relación padre Marca y consumidor directo de Material.
11. **Tipo de retacería** — campos dimensionales y bloqueo por uso; buen cierre de excepciones tabulares.
12. **Parámetros** — pantalla especial company-scoped; implementar después de resolver autorización y usando los catálogos ya normalizados.

## Criterio de salida hacia implementación

El patrón visual **sí está suficientemente definido** para iniciar un piloto no destructivo con Unidad de medida. La implementación general de los doce módulos no está liberada todavía: antes de activar acciones delete/bulk compartidas deben cerrarse los puntos de autorización, endpoints incorrectos y políticas referenciales indicados como `BACKEND SYNC OPEN`.

Estado final: `MATERIAL CONFIG CRUD AUDIT COMPLETE`
