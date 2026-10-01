# REAL NEXT 5.12 — Material Configuration: Parámetros

## Current Contract

Parámetros de materiales no es un CRUD tabular. Es una única pantalla de configuración que controla la visibilidad y disponibilidad de campos de Material para la empresa activa.

| Operación | Método y ruta | Nombre | Permiso de ruta | Contrato |
| --- | --- | --- | --- | --- |
| Ver configuración | `GET /dashboard/settings/material-details` | `settings.material-details.index` | Ninguno específico | Busca el registro del tenant y la company activa |
| Guardar configuración | `POST /dashboard/settings/material-details` | `settings.material-details.store` | Ninguno específico | POST síncrono con CSRF y `enabled_sections[]` |

No existen index de registros, create, edit, delete, bulk delete, DataTables, endpoint AJAX, JavaScript propio ni Form Request. El POST filtra las claves recibidas contra `config/material_details.php`, aplica dependencias y ejecuta `updateOrCreate` por `tenant_id + company_id`.

Si no existe un registro para la company activa, la pantalla devuelve todas las opciones desmarcadas. No hay defaults globales, fallback al tenant ni herencia entre empresas.

## Scope

`SCOPE: BOTH (TENANT + COMPANY)`

`MaterialDetailSetting` usa `BelongsToTenant`, por lo que el global scope limita consultas al tenant activo. El controller añade `forCompany(TenantContext::companyId())`. La tabla exige `tenant_id` y `company_id`, tiene FK hacia ambos contextos y un índice único sobre `company_id`.

El catálogo de claves es global en código (`config/material_details.php`), pero sus valores habilitados son específicos por compañía.

## Company-aware Behavior

- La empresa activa se obtiene de `TenantContext::companyId()`, inicializado y validado por `InitializeTenantContext`.
- Una misma empresa sólo puede tener un registro por el índice único de `company_id`.
- Dos empresas del mismo tenant pueden conservar arreglos distintos.
- El store toma siempre tenant y company del contexto; el request no puede elegirlos mediante payload.
- Si falta configuración, `$enabled` es `[]`; no se crea nada al abrir la pantalla.
- No existe fallback tenant → company ni company → global.
- Los fixtures permiten comprobar dos compañías de EDESCE: company 1 y company 2. Ambas tienen hoy las doce claves habilitadas. La company 5 del mismo tenant no posee registro y representa el estado sin configuración.
- Gamarra usa tenant 3 / company 3 y tiene su propio registro con las doce claves.
- Platform Admin no tiene comportamiento soportado: `tenant.context` limpia su contexto y el controller llama a `TenantContext::companyId()`, por lo que la ruta termina en una excepción si se accede como platform admin.

## Parameter Types

Clasificación real del módulo:

- `BOOLEAN SETTINGS`: cada clave está habilitada o deshabilitada por presencia en el arreglo.
- `SECTION VISIBILITY`: controla campos, filtros, columnas y carga de catálogos de Material.
- `COMPANY OVERRIDE`: cada company guarda su propio conjunto.
- No es `KEY_VALUE` general, `NUMERIC CONFIG`, `CATALOG CONFIG` ni `TENANT DEFAULT`.

Claves válidas:

| Parámetro | Tipo configurado | Dependencia aplicada al guardar |
| --- | --- | --- |
| `unit_measure` | relation | Ninguna |
| `brand` | relation | Ninguna |
| `exampler` | relation | Habilita `brand` |
| `genero` | relation | Ninguna |
| `talla` | relation | Habilita también `color` |
| `color` | relation | Habilita también `talla` |
| `perecible` | field | Ninguna |
| `category` | relation | Ninguna |
| `subcategory` | relation | Habilita `category` |
| `material_type` | relation | Habilita `category` + `subcategory` |
| `subtype` | relation | Habilita `category` + `subcategory` + `material_type` |
| `typescrap` | relation | Ninguna |

## Permissions

Existen `enable_materialSetting` y `config_materialSetting` en el catálogo de permisos. Sin embargo:

- GET y POST no tienen middleware de permiso específico;
- `enable_materialSetting` sólo controla el nodo padre del menú;
- el enlace hijo usa incorrectamente `list_exampler`;
- la vista no tenía ni tiene una frontera contractual de autorización propia.

La modernización preserva el contrato actual y no inventa autorización frontend. La corrección debe realizarse en rutas y menú de forma coordinada.

## Integration with Material

`MaterialController::create`, `edit` e `indexV2` leen la configuración mediante TenantScope + `forCompany(companyId)` y usan `[]` cuando no existe registro.

| Parámetro | Material Create/Edit | Material List |
| --- | --- | --- |
| `unit_measure` | Muestra selector y carga catálogo | Controla columna Unidad de medida |
| `brand` | Muestra Marca y carga catálogo | Controla columna y filtro Marca |
| `exampler` | Muestra Modelo; carga dependiente de Marca | Controla columna Modelo |
| `genero` | Muestra Género y carga catálogo | No se observó columna/filtro condicionado en List V2 |
| `talla` + `color` | Habilitan el flujo de variantes y sus catálogos | No se observó columna/filtro condicionado en List V2 |
| `perecible` | Muestra el campo Perecible | No se observó columna/filtro condicionado en List V2 |
| `category` | Muestra Categoría y carga catálogo | Controla columna y filtro Categoría |
| `subcategory` | Muestra Subcategoría y carga dependiente | Controla columna y filtro Subcategoría |
| `material_type` | Muestra Tipo y carga dependiente | Controla columna y filtro Tipo material |
| `subtype` | Muestra Subtipo y carga dependiente | Controla columna y filtro Subtipo |
| `typescrap` | Muestra Tipo de retacería y carga catálogo | Controla columna y filtro Retacería |

No se observaron efectos directos sobre persistencia de stock, costos, alertas, permisos o inventario. El impacto en esos módulos es indirecto a través de los datos que Material permite capturar.

## Cross-module Impact

### CURRENT CONTRACT

- Material Create/Edit: oculta campos y evita cargar catálogos deshabilitados.
- Material List V2: oculta columnas y filtros configurables.
- Variantes: sólo se ofrecen cuando Talla y Color están habilitados; el store fuerza que ambas claves permanezcan juntas.
- Las dependencias de clasificación se normalizan únicamente al guardar Parámetros.

### OPEN BUSINESS DECISION

- Definir si deshabilitar una clave debe sólo ocultar UI o también restringir payloads/backend.
- Definir cómo deben mostrarse datos históricos cuando una clave queda deshabilitada.
- Definir si una company nueva debe empezar sin campos o heredar un default del tenant.
- Definir el comportamiento soportado para Platform Admin.

### REVISIT TRIGGER

- Introducción de defaults tenant/globales.
- Validación backend de payload Material según `$enabled`.
- Nuevos consumidores de `enabled_sections`.
- Nuevas claves o dependencias.
- Cambio del alcance de catálogos tenant-scoped a company-scoped.

## Delete/Reset Semantics

No existe delete, archive, reset ni restore-defaults. Desmarcar todas las opciones y guardar persiste `enabled_sections = []`; no elimina el registro. El botón Cancelar añadido en frontend es `type="reset"`: sólo restaura los valores renderizados y no envía ninguna petición.

La inexistencia de un default hace que “resetear” sea semánticamente ambiguo. No se añadió esa acción.

## Backend Sync Notes

[OPEN]
Point: autorización de lectura y escritura.
File / endpoint: `routes/web.php`, GET/POST `dashboard/settings/material-details`, menú en `layouts/appAdmin2.blade.php`.
Impact: cualquier usuario autenticado con contexto válido puede acceder directamente y guardar; el menú mezcla `enable_materialSetting` con `list_exampler` y no usa `config_materialSetting`.
What backend needs to define or correct: aplicar el permiso final a GET/POST y al menú.
Frontend can continue: Yes.

[OPEN]
Point: ausencia de Form Request.
File / endpoint: `MaterialDetailSettingController::store`.
Impact: el controller filtra claves desconocidas, pero no valida que `enabled_sections` sea array antes de iterarlo; un payload escalar puede producir comportamiento inválido.
What backend needs to define or correct: request dedicado con `enabled_sections` nullable/array y validación de cada clave.
Frontend can continue: Yes.

[OPEN]
Point: política cuando no existe configuración.
File / endpoint: `MaterialDetailSettingController::index`, `MaterialController::create/edit/indexV2`.
Impact: una company nueva obtiene cero campos configurables sin fallback ni aviso contractual.
What backend needs to define or correct: confirmar empty-by-default o introducir un default explícito por tenant/global.
Frontend can continue: Yes.

[OPEN]
Point: Platform Admin.
File / endpoint: `InitializeTenantContext`, `MaterialDetailSettingController`.
Impact: el middleware limpia contexto y el controller exige company, produciendo una excepción.
What backend needs to define or correct: impedir la ruta al platform admin o diseñar selección explícita de tenant/company.
Frontend can continue: Yes para usuarios tenant; No para Platform Admin.

[OPEN]
Point: alcance funcional de deshabilitar campos.
File / endpoint: Material Create/Edit/List y requests de Material.
Impact: `$enabled` gobierna principalmente render/carga; falta confirmar si backend debe rechazar datos de campos deshabilitados o preservar históricos.
What backend needs to define or correct: política de escritura e históricos por campo.
Frontend can continue: Yes.

## Revisit Triggers

- Se añade middleware `config_materialSetting` o se redefine el permiso.
- El enlace del menú deja de depender de `list_exampler`.
- Se incorpora un Form Request.
- Se define fallback/default para companies sin registro.
- Se soporta Platform Admin.
- Se añaden claves, tipos distintos de booleano o categorías dinámicas.
- Material valida payload según la configuración activa.
- Se implementa reset o eliminación de configuración.

## Implementation Result

- La ruta GET se incorporó a la allowlist Venti Next.
- La pantalla usa Page Header, toolbar, form sections y switches Bootstrap existentes.
- Se agruparon las claves en Identidad comercial, Clasificación y Variantes; nuevas claves desconocidas siguen apareciendo en Otros parámetros.
- Se preservaron POST síncrono, CSRF, `enabled_sections[]`, claves, checked state y dependencias backend.
- Cancelar sólo hace reset local.
- No se añadió CSS ni JavaScript.

Estado: `APPROVED WITH BACKEND SYNC OPEN`.
