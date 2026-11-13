# REAL NEXT — Porcentajes — Configuration CRUD

Fecha: 2026-11-13. **FRONTEND INTEGRATED**. Integración acotada; no mutaciones ni commit.

## Meaning / Domain

Controller `PorcentageQuoteController`, modelo `App\PorcentageQuote`, tabla `porcentage_quotes`; vistas `porcentagesQuote/{index,create,edit}`, JS homónimos. Son parámetros de cotización por nombre técnico: utility/rent/letter/igv. No es un catálogo genérico de descuentos o comisiones. Sus consumidores también incluyen equipos y cálculos legacy de ventas.

## Routes

Prefijo `/dashboard`. Middleware común auth/check.user.enabled/password.changed/tenant.context; permisos específicos abajo. Tener contexto de sesión no aplica un scope al modelo.

## Permissions

| Action | Route / name | Permission | Read/Mutation |
|---|---|---|---|
| List | GET porcentajes/cotizaciones — porcentageQuote.index | list_porcentageQuote | Read |
| Data | GET all/porcentages/quotes — sin nombre | list_porcentageQuote | Read |
| Create view | GET crear/porcentaje — porcentageQuote.create | create_porcentageQuote | Read |
| Store | POST porcentage/store — porcentageQuote.store | create_porcentageQuote | Mutation NO ejecutada |
| Edit view | GET editar/porcentaje/cotizacion/{id} — porcentageQuote.edit | update_porcentageQuote | Read |
| Update | POST porcentages/update — porcentageQuote.update | update_porcentageQuote | Mutation NO ejecutada |
| Destroy | POST porcentages/destroy — porcentageQuote.destroy | destroy_porcentageQuote | Mutation NO ejecutada |
| Deleted / Restore | No existen | No aplica | No implementado |

Se preservan @can, array getPermissionsViaRoles y gates JS existentes; no se certificaron roles restringidos ni permisos directos (AUTH-01 existente). Requests authorize() true, autorización por middleware.

## Scope

**GLOBAL LEGACY — TECH DEBT**. Modelo sin TenantScope/company scope, tabla sin tenant_id/company_id. Lectura global all/select/find; no evidencia de decisión de producto que lo convierta en catálogo global intencional.

Owner Gamarra y Owner EDESCE, cada uno con su empresa/Local Principal: navegación GET real devuelve los mismos cuatro nombres/valores. Comparación READ ONLY del modelo bajo contextos tenant/company [1,1] y [3,3]: IDs completos [1,2,3,4]. No se afirma isolation PASS ni se agrega filtro frontend.

## Dataset

Revalidación de DB local en transacción READ ONLY + ROLLBACK:

| ID | name | value |
|---|---|---|
|1|utility|20.00|
|2|rent|2.00|
|3|letter|3.70|
|4|igv|18.00|

4 físicos / 4 visibles. No existe active/is_active/deleted_at; no llamar activos a un estado inexistente. Eliminados no inventariables porque el borrado es físico. created_at/updated_at presentes.

## Fields

name: required|string|max:255; value: required|numeric|min:0. Update añade porcentage_id required|exists:porcentage_quotes,id; Destroy sólo ese ID. Sin unique, max porcentual ni validación de escala decimal en Requests. No se reinterpretan como valores necesariamente <=100.

Forms mantienen formCreate/formEdit, data-url, multipart/FormData, CSRF, hidden porcentage_id; no method spoofing existente. Numeric HTML conserva min=0, step=0.01, sin max/required adicionales. Se agregan IDs accesibles name/value, old() y @error. Mensajes backend y manejo AJAX originales intactos.

Update protege el nombre exacto utility/rent/letter/igv, pero permite cambiar value. El nombre sigue editable como antes: el backend es la autoridad y omite su cambio. No se añade otra regla fiscal/negocio en JS.

## Value Semantics

Migración: DECIMAL(9,2). El dataset contiene 20.00, 2.00, 3.70, 18.00, no fracciones 0.20/0.18. El mantenedor envía y muestra value sin multiplicar/dividir ni toFixed nuevo. QuoteSaleController, por ejemplo, calcula factor 1 + igv/100; quote/adjust.js divide letter/rent por100. Conversión pertenece a consumers existentes, no a este CRUD.

## Consumers

| Consumer | Usage |
|---|---|
| QuoteController | Busca utility/rent/letter por nombre para creación/edición/ajuste de cotizaciones legacy. |
| QuoteSaleController | Consume igv en cálculos y documentos/conversión, y utility/rent/letter en equipos; conserva first/firstOrFail/value actuales. |
| DefaultEquipmentController | Parámetros utility/rent/letter en create/edit de equipos. |
| PuntoVentaController | Lee igv para factor de cálculo en camino legacy de descuentos por cantidad. No certifica que todos los caminos POS dependan del catálogo. |
| Material / Purchases | Sin referencias directas a PorcentageQuote/porcentage_quotes encontradas en sus controllers/modelos; no se ampliaron sus auditorías. |

Consumers PRESERVED: ningún archivo de Quotes, Sales, Material, compras o equipos modificado.

## SoftDeletes

No usa SoftDeletes ni tiene deleted_at. Destroy llama delete() físico. No hay rutas de eliminados/restore. La ocultación de Eliminar para nombres reservados se conserva exactamente (búsqueda por palabra en JS); no se ejecutó borrado.

## AJAX Contracts

GET all/porcentages/quotes devuelve DataTables JSON con data[id,name,value], sin orderBy explícito. No se cambian shape, keys, filtros ni orden. Búsqueda/paginación siguen cliente. POST create/update/destroy conservan FormData; éxito message, update además url; validación422 existente. JS create/edit intactos. Sólo index cambia composición de acciones y wrappers DataTables; preserva permiso y condición found. Textos/atributos escapados; modal usa text para nombre.

## Visual Mapping

REUSE DIRECTLY: Configuration CRUD de Calidades/Contactos, next-page-heading, next-page-toolbar/context-only, next-operational-list, next-form-section, next-list-toolbar, next-row-actions y next-aux-modal. Tabla Nombre izquierda / Valor derecha / Acciones centro. Sin columnas de estado inventadas.

**ALLOWLIST MATCH: YES**: porcentageQuote.index/create/edit exclusivamente. Navegador confirma venti.min.css y venti-next.css. **CSS nuevo: 0**; eliminados overrides Select2 innecesarios. Surface Spacing por wrappers y grid; sin cambiar CSS compartido.

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

Medición DOM de tres vistas; inspección visual listado1440 y edit375. QA GET: listado en ambas cuentas, crear, editar ID1, enlace de dropdown Editar, búsqueda sin resultados. Los cuatro nombres reservados no ofrecen eliminación según condición preservada. Edit muestra utility/20.00 y min0/step0.01. Sin errores de consola observados. No envío de formularios ni simulación de escrituras422. Paginación tiene una página (4 filas); no se fabricó dataset.

Evidencias: percentages-desktop.png / percentages-edit-mobile.png en `C:/Users/Remso/.codex/visualizations/2026/09/29/01a0eb59-3ffa-7bd2-9885-1a304f512347/`.

## Backend Sync Notes

[OPEN] Scope global legacy. Archivo: PorcentageQuote/model/controller. Impacto: parámetro compartido entre tenants. DEV/Producto debe definir ownership; reutiliza deuda transversal de REAL-NEXT-ROADMAP-D-SCREEN-DISCOVERY, sin duplicarla. Frontend can continue: YES.

[OPEN] PERCENT-01: protección de nombres reservados sólo en UI para destroy. DeleteRequest sólo exists; controller elimina sin condición. Consumers buscan estas claves, algunos desreferencian first()->value. Backend debe hacer cumplir la protección; defecto estático, sin ejecutar POST ni demostrar fallo destructivo. Frontend can continue: YES. Backlog actualizado.

[OPEN] Validación no limita escala decimal/unique/nombres duplicados; contrato sigue igual. Política y robustez pendientes, no inventar rango ni normalizar. Edit find no garantiza404 para inexistente; no se amplió QA negativa. AUTH-01/QA-02 siguen pendientes.

**Percentages FRONTEND INTEGRATED · Scope GLOBAL LEGACY — TECH DEBT · Consumers PRESERVED · Mutations QA PENDING · Backend UNCHANGED**.

Checks: PHP7.3 view:cache PASS; node --check index.js PASS; git diff --check PASS. Sin commit.
