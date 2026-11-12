# REAL NEXT — Tenant Company / Empresas

Fecha: 2026-11-12. Fase 1 del Roadmap C de `REAL-NEXT-FRONTEND-PRIORITY-REAUDIT-2.md`. Integración exclusivamente visual, sin guardados comerciales ni commit.

## Contract Audit

Auditados TenantCompanyController, Company, Branch, StoreTenantCompanyRequest, UpdateTenantCompanyRequest, PublicCatalogSlug, CompanyInventoryStructureService, EnsureTenantOwner, rutas, las tres vistas y `public/js/tenantCompany/{index,create,edit}.js`. También el include/JS existente de managedWarehouses en edit y los patrones Company Bank Accounts/TenantBranch, Surface Spacing Standard y dirección visual.

Company no usa BelongsToTenant ni TenantScope. El controller aplica tenant_id explícito en index/edit/update/toggle; store lo obtiene de TenantContext. Index carga branches ordenadas principal primero y branches_count, ordena empresas activas primero/razón social. No hay búsqueda, DataTables ni paginación en este contrato: no se inventaron.

Los forms conservan IDs, data-url, CSRF, names y submit vía AJAX POST/form.serialize(). No había method/action HTML explícitos y se mantiene ese mecanismo. Create/edit JS muestra el primer error de validación con toastr, deshabilita submit durante petición y redirige con response.url; intacto. Index conserva confirmación, CSRF header y todos los data-* del toggle. No se usa Select2 en estos formularios.

Edit conserva `inventory.managedWarehouses`, companyId de la empresa editada y branchId null, además de su JS. La superficie recibe `warehouseSurfaceClass=next-form-section`, igual que Branch ya integrado. No se cambió ese partial compartido ni se probaron sus mutaciones.

## Routes

Base de rutas abreviadas: `/dashboard/configuracion`. Confirmadas con PHP 7.3.33 `artisan route:list --name=tenantCompany --compact`.

|Operación|Ruta / nombre|Permission|Tenant|Company|Branch|Read/Write|
|---|---|---|---|---|---|---|
|Listado|GET empresas · tenantCompany.index|list_company|Explícito actual|Todas del tenant|Conteo/relación|Read|
|Formulario create|GET empresas/crear · tenantCompany.create|create_company|Middleware|No existente|Formulario inicial|Read|
|Crear|POST empresas · tenantCompany.store|create_company|Del servidor|Nueva|Crea principal|Write — NO ejecutado|
|Formulario edit|GET empresas/{id}/editar · tenantCompany.edit|edit_company|Filtro explícito|ID dentro del tenant|No modifica|Read|
|Actualizar|POST empresas/{id}/actualizar · tenantCompany.update|edit_company|Filtro explícito|ID dentro del tenant|No modifica|Write — NO ejecutado|
|Estado|POST empresas/{id}/toggle-status · tenantCompany.toggleStatus|enable_company|Filtro explícito|Protecciones actuales|No cascada|Write — NO ejecutado|

Enlaces de sucursales conservan `tenantBranch.index($company->id)` y `list_branch`. No se modificaron rutas ni vistas Branch. El panel de almacenes conserva su ruta `managedWarehouse.company` y contratos actuales; es una operación independiente del formulario de identidad.

## Permissions

Grupo `auth`, `check.user.enabled`, `password.changed`, `tenant.context`, seguido de `tenant.owner` y permiso específico. EnsureTenantOwner rechaza ausencia de usuario, Platform Admin, usuario sin tenant y no Owner. Store añade comprobación de Owner/no Platform Admin y pertenencia al tenant del contexto.

Botón Nueva empresa conserva create_company; acciones Ver sucursales/Editar/Activar-Desactivar conservan list_branch/edit_company/enable_company. Dropdown no se muestra si no existe ninguna capacidad. No se añadieron permisos ni selectors tenant/company.

## Tenant Scope

Listado tenant-wide, no company activa. Editar otra empresa propia es válido sin cambiar la company/branch operativa. No se invocó el selector de contexto ni se persistieron cambios de contexto; se alternaron únicamente las cuentas QA autorizadas y sus contextos existentes/defaults de login.

Dataset actual de lectura SQL: **5 empresas activas / 8 sucursales activas**. Diferencia respecto al baseline: company 1 ya tiene `catalog_slug=edesce`; las otras cuatro companies y las ocho branches mantienen NULL. Este valor fue observado al iniciar QA, no creado por esta fase. No se reescribe la evidencia histórica del informe anterior.

## Company Identity

|Campo|Regla backend|Presentación|
|---|---|---|
|ruc|Obligatorio, 11 dígitos, único por tenant; update ignora propio ID|Texto, maxlength 11|
|business_name|Obligatorio string max 200|Razón social|
|trade_name|Nullable string max 150|Nombre comercial|
|address|Nullable string max 250|Dirección|
|phone|Nullable string max 30|Teléfono|
|email|Nullable email max 150|Correo|
|catalog_slug|Nullable, reglas PublicCatalogSlug|Enlace público opcional|
|is_active|Store fija true; sólo toggle cambia estado|Badge Activa/Inactiva; no input nuevo|

Se añadieron labels vinculados con IDs nuevos donde antes faltaban, `old()` para conservar valores y `@error`/is-invalid para renderizado Laravel; IDs existentes de formularios y slugs intactos. Maxlength refleja requests sin añadir lógica de negocio JS. No campos de logo/branding de Settings.

## Company Slug

Opcional, max 80, patrón backend `^[a-z0-9]+(?:[-_][a-z0-9]+)*$` con anclaje final estricto. Letras minúsculas/números con separadores simples guion/underscore entre segmentos. No acepta mayúsculas, espacios, separadores repetidos o iniciales/finales. No slugify ni lowercase automático: TrimStrings recorta extremos y ConvertEmptyStringsToNull trata vacío; no se transforma una frase en slug.

Unicidad **global** de companies.catalog_slug, no limitada al tenant. Update ignora ID de la ruta. Índice unique en DB de migración aplicada. La ayuda indica que cambiarlo modifica la dirección pública; no se genera enlace navegable desde el listado ni se publica nada durante QA.

## Initial Branch Slug

Create conserva branch_catalog_slug opcional. Branch_name y branch_code son obligatorios, default visual PRINCIPAL, máximos 150/30. Código permite A-Z/a-z/0-9/guion/underscore y el controller lo trim/uppercase. Branch_address max250 y branch_phone max30 opcionales, heredan de company cuando faltan.

PublicCatalogSlug::branchRules() en create de empresa no recibe companyId porque aún no existe; valida formato/max/reservado. No se inventó un unique contra company activa. En operaciones con companyId, branchRules limita unicidad a esa company y permite ignorar propio ID; DB garantiza unique(company_id,catalog_slug). Una empresa nueva crea una sola principal. Edit Company NO incorpora edición del slug branch: pertenece al mantenimiento Branch existente.

## PublicCatalogSlug Contract

Company reservados base: api, storage, images, shop, store-web, admin, dashboard, más primeros segmentos literales de las rutas registradas compatibles con el patrón (incluye login/logout/password, etc.). Branch reserva `producto`. Backend es autoridad; no duplicar lista en JS.

Validación no persistente nueva con Validator + reglas reales, DB en transacción READ ONLY:

- UPPER, `a b`, `a--b`, `-a`: rechazados por Regex.
- dashboard/api: rechazados por NotIn.
- Vacío y `qa-name_12`: aceptados por validación; NO guardados.
- `edesce`: rechazado por Unique global; permitido al ignorar company 1 en su edición. Prueba ejecutada también sin depender de Company TenantScope, que no existe.
- Branch `producto`: rechazado. Unicidad company-scoped confirmada en código/índice; no hay slugs branch reales para ejercer duplicado positivo sin fabricar registros. **SLUG MUTATION QA PENDING** (persistencia, colisiones concurrentes y cambio efectivo de URL).

## Provisioning Chain

CREATE MUTATION QA — PENDING / HIGH IMPACT.

Store ejecuta una transacción que:

1. Crea Company con tenant del servidor, identidad, slug opcional e is_active=true.
2. Consulta si Owner ya tiene una membresía company con pivot is_active; nueva membresía default sólo si no existe. `syncWithoutDetaching` preserva asignaciones anteriores.
3. Crea Branch principal is_main/is_active=true, código normalizado, nombre, slug opcional y dirección/teléfono propios o heredados.
4. Asigna Owner a branch por syncWithoutDetaching, default sólo si no tiene una membresía branch activa.
5. Invoca CompanyInventoryStructureService::provision y devuelve JSON message/company/branch/url.

No cambia sesión/defaults operativos del usuario desde frontend; no se ejecutó store. Existencia de un botón habilitado no autoriza guardar en este QA.

## Inventory Structure

Service usa consultas sin scopes globales pero fija tenant/company explícitos donde corresponde. Reutiliza único warehouse default y su área; rechaza varios defaults. Si ya tiene location default, valida contexto y jerarquía shelf/level/container/position antes de reutilizar. Si falta estructura, crea/reutiliza GENERAL: Area → Warehouse (company, branch_id NULL, default) → Shelf → Level → Container → Position → Location default. No equivale a crear stock/niveles con cantidades ni almacén local del branch nuevo.

Cadena física y membresías son efectos de alta relevancia. No se invocó provision, no se crearon almacenes/ubicaciones. El panel de almacenes de edit cargó por GET el GEN existente en EDESCE (1 ubicación), sin enviar formularios.

## Membership Effects

Store conserva anteriores membresías y añade nuevas activas; default de las nuevas depende de existencia de pivot activo previo. Edit identidad no sincroniza membresías, no edita branch y no provisiona inventario. No se cambiaron defaults ni Worker/User. Las pruebas de contexto PHP usan sesión array en memoria y rollback; no son escrituras de membresía ni reemplazan el login HTTP comprobado.

## Status Contract

Toggle bloquea fila company dentro del tenant. Activar marca true. Para desactivar rechaza company usada actualmente en TenantContext y la última activa del tenant; consulta otras activas con lockForUpdate. No desactiva en cascada branches, company_user, branch_user, almacenes ni locations; deja company fuera de operación. Las reglas siguen únicamente en backend, no replicadas en JS.

STATUS MUTATION QA — PENDING. No se pulsó confirmar toggle ni se simuló desactivación real. Dataset actual no tiene companies inactivas; badge/acción Inactiva/Activar se verifican estáticamente, no como caso de datos real. Concurrencia de última activa no certificada por esta fase.

## Public Catalog Relationship

Company slug define `/{companySlug}`; branch slug permite `/{companySlug}/{branchSlug}` y rutas de producto actuales. Activa **no equivale** a catálogo publicado ni garantiza ofertas. Slug de EDESCE existente no autoriza abrir catálogo en esta fase. No se visitaron rutas públicas, asignaron slugs ni crearon datos publicables.

**Public Catalog — NOT YET. SW-01…SW-07 permanecen OPEN según su alcance previo.** Branding continúa en Company Settings, que no se reabre.

## Visual Mapping

|Antes|Patrón reutilizado|Cambio|
|---|---|---|
|Título/acciones float|next-page-toolbar, next-toolbar-context/actions|Toolbar compacta y acciones con wrap|
|Tabla/botones múltiples|next-operational-list, next-list-summary, next-data-table, next-row-actions|Dropdown con permisos; columna slug real; badge; empty colspan 7|
|Cards anidadas create/edit|next-form-section + next-section-header|Identidad / enlace público / sucursal inicial, mismos ejes y gutters|
|Acciones text-right|next-list-toolbar + next-toolbar-actions justify-content-end|Fila alineada, wrap móvil|
|Header de sólo contexto en forms|next-page-toolbar-context-only|Conserva texto en móvil; evita header vacío|
|Panel de almacenes edit|warehouseSurfaceClass=next-form-section|Mismo wrapper aprobado de Branch, partial intacto|

RUC/conteo/estado/acciones centrados, texto izquierda. Tabla con scroll local accesible, sin nuevo buscador/paginación ni agregados backend inventados. Conteo viene de colección/branches_count existentes. **CSS nuevo: 0. JS modificado: 0.**

ALLOWLIST MATCH: YES, exclusivamente tenantCompany.index/create/edit añadidos en config/venti-next.php. Carga observada de `admin/dist/css/venti.min.css` y `venti-next.css` en navegador real.

## Responsive QA

Matriz de medición DOM en Laravel real, Owner EDESCE. `scrollWidth == clientWidth` del documento; inputs de create/edit dentro del viewport. Tabla de empresas conserva scroll local en anchos menores; a 1440 cabe completa.

|Ancho|Listado|Create|Edit|
|---|---|---|---|
|320|PASS, local scroll|PASS|PASS|
|375|PASS, local scroll|PASS|PASS|
|390|PASS, local scroll|PASS|PASS|
|576|PASS, local scroll|PASS|PASS|
|768|PASS, local scroll|PASS|PASS|
|992|PASS, local scroll|PASS|PASS|
|1200|PASS, local scroll|PASS|PASS|
|1440|PASS|PASS|PASS|

Inspección visual desktop y móvil, campos/ayudas completos, acciones de formulario sin corte, dropdown abre/cierra con Escape; no ejecutado toggle. Edit conserva carga de almacenes sin overflow. Consola revisada sin errores/warnings durante navegación normal; los 404 de QA son rechazos esperados. No pruebas de submit ni de errores AJAX con escritura.

Evidencias locales fuera del repo: `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/tenant-companies/`, incluyendo responsive.json, index-desktop-final.png, create-mobile-final.png, edit-mobile-final.png y Gamarra. Las capturas iniciales durante transición de viewport no sustituyen las finales estables. Viewport temporal restaurado al terminar.

## Cross-context QA

|Cuenta / rol|Contexto operativo|Listado esperado/obtenido|Create / edit propio|Edit cruzado / inexistente|
|---|---|---|---|---|
|Owner EDESCE / Tenant Owner|T1/C1/B1 Local Principal|1,2,5|Form create y edit C1 abiertos en navegador|C3 y 99999999: 404 HTTP|
|Owner Gamarra / Tenant Owner|T3/C3/B4 Local Principal|3|Form create y edit C3 abiertos en navegador|C1 y 99999999: 404 HTTP|

Prueba PHP adicional con controllers reales, EnsureTenantOwner e InitializeTenantContext, transacción READ ONLY/rollback: EDESCE edit C1/C2/C5 válidos, C3/C4/inexistente 404; Gamarra sólo C3 válido, C1/C2/C4/C5/inexistente 404. Permisos list/create/edit/enable_company y list_branch presentes en ambos Owners. Esto confirma que company activa no recorta el mantenimiento tenant-wide. No se persistió un cambio de contexto mediante selector. Al terminar se restauró login EDESCE; no se guardaron credenciales en código/docs/scripts.

**Tenant Isolation — QA PASSED**, limitado a lectura/listado/edit y casos anteriores. No representa QA de persistencia, concurrencia ni todos los roles posibles.

## Mutation Exclusions

NO store/update/toggle de empresas; NO asignación/cambio de slugs; NO cambios de company/branch/memberships/defaults; NO provision; NO cambios de almacenes; NO publicación ni navegación Public Catalog. No migraciones, exports ni commit. Sólo login/logout autorizados para QA y GET de navegación/carga.

## Backend Sync Notes

No se detectó un blocker backend nuevo para esta fachada. Backend autoritativo y sin cambios. Pendientes explícitos, no falsos cierres:

- CREATE MUTATION QA — PENDING / HIGH IMPACT: transacción completa, infraestructura, membresías y defaults.
- Edit/update, colisión concurrente de slugs y STATUS MUTATION QA — PENDING.
- Duplicado positivo branch sin dataset disponible; comprobación estática/índice, no escritura de fixture.
- Estado inactivo/listado vacío se mantiene por Blade y reglas; dataset positivo actual no cubre esos estados.
- Public Catalog/SW OPEN, fuera de fase; no inferir publicación por active/slug.

## Revisit Triggers

Cambio backend de fields/scopes/slugs/provision/defaults/permisos; autorización de mutation QA aislado; dataset con branch slugs/inactivas; regresión visual o funcional real. No reconstruir Branch/Settings ni incorporar Public Catalog por esta integración.

## Files / Validation / Status

Modificados: config/venti-next.php y resources/views/tenantCompany/index.blade.php, create.blade.php, edit.blade.php. Creado este informe. El informe de reauditoría anterior ya estaba untracked antes de empezar y no se tocó.

Comparación con HEAD: ningún name/id original eliminado, CSRF 1/1 en cada form; IDs añadidos sólo para labels/sections. JS y hooks preservados. PHP 7.3.33 `artisan view:cache`: PASS. `git diff --check`: PASS. No CSS/backend/routes ni vistas Branch modificados. No se requirió sintaxis JS porque no se editó JS.

- Tenant Company List — FRONTEND INTEGRATED
- Create / Edit — FRONTEND INTEGRATED
- Tenant Isolation — QA PASSED (lectura)
- Slug Contract — FRONTEND ALIGNED / BACKEND AUTHORITATIVE
- Create Provisioning — MUTATION QA PENDING
- Status Mutation — QA PENDING
- Public Catalog — NOT YET
- Backend — UNCHANGED
