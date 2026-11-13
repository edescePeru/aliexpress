# REAL NEXT — Calidades — Configuration CRUD

Fecha: 2026-11-13. **FRONTEND INTEGRATED**. Backend y consumers sin cambios. Sin commit.

## Routes

Prefijo `/dashboard`; middleware común `auth`, `check.user.enabled`, `password.changed`, `tenant.context`. El contexto de acceso no agrega un scope al modelo Quality.

## Permissions

| Action | Route | Permission | Read/Mutation |
|---|---|---|---|
| List | GET `Calidades` — `quality.index` | `list_quality` | Read |
| Data | GET `all/qualities` — sin nombre | `list_quality` | Read |
| Create view | GET `crear/calidad` — `quality.create` | `create_quality` | Read |
| Store | POST `quality/store` — `quality.store` | `create_quality` | Mutation: NO ejecutada |
| Edit view | GET `editar/calidad/{id}` — `quality.edit` | `update_quality` | Read |
| Update | POST `quality/update` — `quality.update` | `update_quality` | Mutation: NO ejecutada |
| Destroy | POST `quality/destroy` — `quality.destroy` | `destroy_quality` | Mutation: NO ejecutada |
| Deleted / Restore | No existen rutas/actions | No hay contrato | No implementado |

La UI conserva @can y el array `getPermissionsViaRoles()` de index para acciones. Roles limitados/permisos directos: no certificados por este QA (deuda AUTH-01 existente); no se cambian gates.

## Scope

**GLOBAL LEGACY CATALOG — FRONTEND CAN CONTINUE WITH TECH DEBT**.

Quality no tiene TenantScope, tenant_id ni company_id. index/getQualities/edit consultan globalmente. No hay evidencia de una decisión de producto que lo declare global intencional. No se agregaron filtros de seguridad frontend.

Owner EDESCE (tenant1/company1/Local Principal) y Owner Gamarra (tenant3/company3/Local Principal): sesiones reales GET muestran el mismo catálogo de 12 IDs. Comparación completa: `[1,2,4,5,6,7,8,9,10,11,12,13]`. Además se contrastó el modelo en contextos [1,1] y [3,3], dentro de una transacción READ ONLY finalizada con ROLLBACK. Esto prueba el contrato global actual, NO aislamiento.

## Dataset

Relectura local: **13 filas físicas; 12 no eliminadas; 1 excluida por SoftDeletes**. “Activo” aquí significa deleted_at NULL, no is_active (no existe).

| ID | Nombre | Descripción | short_name | SoftDeletes |
|---|---|---|---|---|
|1|TALLA L|TALLA L|L|Visible|
|2|TALLA M|TALLA M|M|Visible|
|3|TALLA 40|TALLA 40|40|Excluido: deleted_at = 0000-00-00 00:00:00|
|4|TALLA 41|TALLA 41|41|Visible|
|5|TALLA 42|TALLA 42|42|Visible|
|6|TALLA 43|TALLA 41|43|Visible; descripción histórica preservada|
|7|S|S|NULL|Visible|
|8|XL|XL|NULL|Visible|
|9|XXXXL|XXXXL|NULL|Visible|
|10|256 GB|256 GB|NULL|Visible|
|11|512 GB|512 GB|NULL|Visible|
|12|1 TB|1 TB|NULL|Visible|
|13|2 TB|2 TB|NULL|Visible|

No se normalizaron nombres, descripción ni fechas.

## Fields

Requests Store/Update: `name` required|string|max:255; `description` nullable|string|max:255. Update/Destroy: `quality_id` required|exists:qualities,id. authorize() true; permisos aplicados por rutas. No uniqueness ni estado activo.

Forms conservan name/description, mayus(this), formCreate/formEdit, enctype, CSRF y hidden quality_id. El HTML original no tenía required/maxlength: no se agregaron restricciones nuevas. Se incorporaron labels/IDs accesibles, old() y @error. `short_name` existe en DB/fillable, pero no es campo del contrato de estos forms ni del response; no se inventó edición.

## Consumers

- Material::quality belongsTo Quality; Quality::materials hasMany Material.
- MaterialController conserva serialización histórica quality/calidad, eager loads y filtro quality_id; selectores Quality(id,name) en index/list legacy (3787/4273 al auditar).
- Variant conserva quality_id temporal legacy; migración original lo relaciona con qualities y mantiene color_id independiente. No se convierte Quality en Talla ni se modifica resolución moderna de variantes.
- Talla/Color no declaran relación directa con Quality en sus modelos. Los nombres del dataset no cambian ese contrato.
- Ningún consumer, FK, ID, Material Edit, POS ni selector externo fue modificado.

## SoftDeletes

Trait y deleted_at presentes. getQualities excluye fechas no NULL. Destroy es soft delete; no toggle. No hay index de eliminados ni restore en controller/routes: no se fabricaron endpoints o pantallas.

## AJAX Contracts

GET all/qualities: DataTables JSON, `data` con id/name/description, orden backend name ASC; búsqueda y paginación cliente preservadas. POST create/edit/delete siguen FormData y sus respuestas message / message+url; errores Request 422 existentes. Create/edit JS sin cambios.

Corrección de integración necesaria: Blade/JS copiados de Tallas apuntaban a talla.store/update/create/index y editar/talla. Ahora usan las rutas Quality reales. El checkbox/botón de borrado múltiple llamaba `talla/delete-multiple` con IDs Quality: retirado ese camino ajeno, sin inventar bulk Quality. Se conserva eliminación individual con quality_id y modalDelete/formDelete. No se ejecutó ninguna acción de escritura.

## Visual Mapping

Configuration CRUD existente: next-page-heading, toolbar/context-only, next-operational-list, next-form-section, next-list-toolbar, next-row-actions y next-aux-modal. Tabla ID/Nombre/Descripción/Acciones; ID/acciones centrados. Textos y atributos escapados. Búsqueda/length, summary con wrap, paginación y empty state compartidos.

Allowlist exacta: quality.index/create/edit, sin endpoints AJAX. **ALLOWLIST MATCH: YES**; navegador confirma venti.min.css + venti-next.css. **CSS nuevo: 0**; retirado override Select2 innecesario en las tres vistas.

## Responsive

| Ancho | Index | Create | Edit | Overflow global |
|---|---|---|---|---|
|320|PASS|PASS|PASS|NONE|
|375|PASS|PASS|PASS|NONE|
|390|PASS|PASS|PASS|NONE|
|576|PASS|PASS|PASS|NONE|
|768|PASS|PASS|PASS|NONE|
|992|PASS|PASS|PASS|NONE|
|1200|PASS|PASS|PASS|NONE|
|1440|PASS|PASS|PASS|NONE|

Medición DOM en navegador de las tres vistas; inspección visual desktop1440/listado320/form375. Tabla con scroll horizontal LOCAL permitido en móvil. GET edit1 y enlace real a edit8; create y edit muestran contrato/valores correctos. Búsqueda sin resultados correcta. Segunda página devuelve IDs8/9 (11–12 de12). Gamarra length25 muestra todos los IDs. Sin errores de consola observados. No se enviaron formularios ni se probaron mutaciones/validaciones 422 por escritura.

Evidencias locales: qualities-desktop.png, qualities-mobile.png y qualities-edit-mobile.png en `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`.

## Backend Sync Notes

- Scope: **GLOBAL LEGACY — TECH DEBT**. Reutiliza deuda de ownership de catálogos globales en REAL-NEXT-ROADMAP-D-SCREEN-DISCOVERY.md, sin duplicar un finding genérico. Producto/DEV deben definir ownership antes de certificar aislamiento.
- QUALITY-01: fecha cero excluye ID3; nueva evidencia de calidad de datos en backlog. No restaurado ni normalizado.
- AUTH-01 y QA-02 siguen abiertos: permisos directos vs roles y mutaciones fuera del QA.
- edit usa find sin fail-fast; los Requests exists no excluyen deleted_at. Robustez de IDs inexistentes/eliminados no certificada; no backend fix en esta fase.
- **Consumers PRESERVED · Mutations QA PENDING · Backend UNCHANGED**.
- Validación: Blade view:cache PASS; node --check index.js PASS; git diff --check PASS. No commit, migraciones ni escrituras de negocio.
