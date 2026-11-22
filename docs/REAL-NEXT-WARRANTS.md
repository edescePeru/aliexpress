# REAL NEXT — WARRANTS / CÉDULAS

2026-11-14 · Fallback tras bloqueo de Activos Fijos.
**FRONTEND INTEGRATED WITH TECH DEBT — GLOBAL LEGACY.** Backend UNCHANGED. CSS0. MUTATION QA PENDING.

## Meaning / scope / dataset

Warrant es el maestro de **cédulas de material**, tabla warrants, campos name/description, relación hasMany Material vía warrant_id. No es garantía comercial, documento de identidad ni operación financiera. El código lo usa como atributo/filtro legacy de Material; no define aquí una semántica técnica más estrecha. Dataset actual: ID1 HOMBRE / HOMBRE; ID2 1L / UN LITRO, ambos deleted_at=NULL. No se reinterpretaron ni corrigieron esos valores.

Modelo Warrant con SoftDeletes, sin tenant/company/branch. Query index Warrant::all y getWarrants select id/name/description; edit Warrant::find(id). Sin lookups de personas, empresas ni inventario. Lectura READ ONLY bajo T1/C1, T1/C2 y T3/C3 devuelve [1,2]. Catálogo global permitido por la instrucción del usuario; **no isolation PASS**. find999999=null; edit no tiene findOrFail y la vista desreferencia el modelo: deuda de 404 controlado, sin backend modificado.

## Routes / permissions / actions

Prefijo /dashboard. Middleware común web/auth/check.user.enabled/password.changed/tenant.context; permission por ruta:

| Surface/action | URI | Name | Permission | Read/Mutation | Included |
|---|---|---|---|---|---|
| Listado GET | Cédulas (C mayúscula y acento) | warrant.index | list_warrant | Read | Sí |
| JSON GET | all/warrants | Sin nombre | list_warrant | Read | Sí |
| Create GET | crear/cedula | warrant.create | create_warrant | Read formulario | Sí |
| Edit GET | editar/cedula/{id} | warrant.edit | update_warrant | Read formulario | Sí |
| Store POST | warrant/store | warrant.store | create_warrant | Mutation | Contrato conectado; no ejecutado |
| Update POST | warrant/update | warrant.update | update_warrant | Mutation | Contrato conectado; no ejecutado |
| Destroy POST | warrant/destroy | warrant.destroy | destroy_warrant | Soft delete | Modal conectado; no ejecutado |
| Restore / borrado masivo | No existen en Warrant | — | — | Mutation | No inventado |

WarrantController index/create/edit/getWarrants/store/update/destroy; views warrant.index/create/edit; js/warrant index/create/edit. Store/UpdateWarrantRequest: name required|string|max255, description nullable|string|max255. Update/Delete requieren warrant_id exists:warrants,id. authorize=true: middleware es la autorización. No se han probado escrituras/validaciones server mediante POST. Names/formCreate/formEdit/formDelete/hidden warrant_id/CSRF/uppercase mayus()/handlers FormData se conservan; old() y feedback Blade añadidos. Create/edit JS intactos.

## Integration / real contract repair

Patrón aprobado Configuration CRUD de Calidades, tablas Bootstrap compactas, next-page-toolbar, next-form-section, next-list-toolbar, next-aux-modal, DataTables búsqueda/resumen/paginación y dropdown de acciones. Columnas reales ID/name/description/acciones; ID/acciones centrados, texto izquierda. No CSS nuevo.

**WARRANT-01 — RESOLVED FRONTEND; mutation QA pending:** el frontend original estaba copiado de Géneros. index leía all/generos, enlaces editaban Género, formularios enviaban genero.store/update/destroy. Se reemplazaron exclusivamente por los contratos Warrant YA EXISTENTES (sin cambiar backend). El borrado masivo enviaba IDs a genero/delete-multiple; se retiró ese control, checkbox de selección y handler ajenos porque no existe endpoint masivo Warrant. No se trasladó esa funcionalidad incorrecta ni se ejecutó. Se corrigieron títulos/breadcrumbs/labels que decían Géneros. Escapado de textos/atributos y .text en modal.

Allowlist exclusiva: warrant.index, warrant.create, warrant.edit. Sin wildcard, sin JSON. **ALLOWLIST MATCH: YES**. venti.min.css y venti-next.css cargados. No cambios en Material, consumidores, inventario o RRHH.

## Read-only QA / responsive

Owner EDESCE, empresa EDESCE / Local Principal. Listado real muestra IDs1 y2 desde Warrant; Edit1 precarga HOMBRE, formulario Create disponible. Búsqueda sin coincidencias muestra empty state; dropdown y modal de eliminar abiertos/cerrados con Cancelar, sin enviar. Endpoints/payloads de escritura verificados estáticamente, **no certificados mediante mutación**. Sin errores de consola capturados. Comparación global EDESCE/Gamarra por queries de modelos bajo contexto, no login Gamarra adicional en esta fase.

| Surface | 320 | 375 | 390 | 576 | 768 | 992 | 1200 | 1440 |
|---|---|---|---|---|---|---|---|---|
| Listado | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| Create | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |
| Edit1 | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS |

GLOBAL OVERFLOW: NONE (scrollWidth<=innerWidth). Se mantuvo búsqueda/paginación DataTables; dataset2 no permite multipágina. No guardar/actualizar/borrar/restaurar, ni modificar inventario. Edit inexistente: find-null confirmado, HTTP404 no certificado.

## Backend Sync Notes

[OPEN] Global legacy: decisiones de ownership/permisos globales pendientes; catálogos simples pueden integrarse con deuda según autorización. AUTH-01 permanece: UI usa getPermissionsViaRoles y gates can; roles limitados/permisos directos no certificados. QA-02/mutaciones pendientes. DATA-01: valores actuales no prueban significado técnico; no se limpian registros por inferencia. No hay documentos en este CRUD.

[RESOLVED FRONTEND] WARRANT-01 referencias cruzadas Género/Warrant corregidas. No se creó ASSET para inventario legacy ya cubierto por INV-01. Ver REAL-NEXT-FIXED-ASSETS.md para ASSET-01 clasificación fija.

## Files / verification

resources/views/warrant/{index,create,edit}.blade.php; public/js/warrant/index.js; config/venti-next.php; este documento y REAL-NEXT-FIXED-ASSETS.md. Backend/routes/models/requests/database/CSS sin cambios. PHP7.3 artisan view:cache PASS; node --check index.js PASS; git diff --check PASS. Sin commit.

Revisitar al cambiar ownership, clasificación Activos, permisos directos, binding inexistente o autorización para QA de mutaciones. No ampliar esta fase a Material general.
