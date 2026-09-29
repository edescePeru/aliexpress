# REAL NEXT 5.13 — Importación de stocks de materiales

Estado frontend: implementado sobre el contrato actual, con dependencias backend abiertas.  
Ruta de pantalla: `GET /dashboard/importar/archivos/stocks/materiales` (`stocks.files.index`).  
Clasificación: **BULK IMPORT / OPERATIONAL FORM**.

## Current Contract

| Operación | Método y ruta | Nombre | Handler | Middleware |
| --- | --- | --- | --- | --- |
| Ver formulario | `GET /dashboard/importar/archivos/stocks/materiales` | `stocks.files.index` | `UploadFilesController@showUploadFilesStocksMaterials` | `auth`, `check.user.enabled`, `password.changed`, `tenant.context`, `permission:stock_files` |
| Procesar archivo | `POST /dashboard/upload/files/stocks/min/max/materials` | `stocks.files.store` | `UploadFilesController@uploadFilesStocksMaterials` | los anteriores, incluido `permission:stock_files` |
| Descargar ejemplo | `GET /dashboard/download/example/stock/file` | sin nombre | `UploadFilesController@downloadExampleStockFile` | `auth`, `check.user.enabled`, `password.changed`, `tenant.context`; sin permiso `stock_files` |

La vista real es `resources/views/files/stockFiles.blade.php` y conserva el formulario AJAX gestionado por `public/js/files/stockFiles.js`. El formulario mantiene `#formStocksFile`, `data-url`, CSRF, `multipart/form-data`, `#stockFile`, `name="file"`, `#btn-submitStockFiles`, `FormData` y el endpoint POST existentes.

El botón Cancelar es `type="reset"`. Solo limpia la selección local, restaura el texto sin archivo y vuelve a deshabilitar Importar. No navega, no llama al servidor y no revierte una importación ya ejecutada.

## Import File Contract

`UploadStockFilesRequest` autoriza la petición porque la autorización efectiva se aplica en la ruta mediante `permission:stock_files`. Valida:

- campo requerido: `file`;
- MIME/extensiones aceptadas por Laravel: `xlsx`, `xls`;
- tamaño máximo real: `10240` KiB, es decir, 10 MB;
- mensajes JSON: archivo obligatorio, formato Excel inválido o exceso de 10 MB.

El comentario del Request todavía dice “Límite de 2MB”, pero la regla y el mensaje establecen 10 MB. La UI documenta el límite efectivo de 10 MB.

El procesador usa únicamente la primera hoja, descarta siempre la primera fila como cabecera y lee por posición:

1. columna A: código del Material;
2. columna B: stock mínimo;
3. columna C: stock máximo.

Una fila solo se procesa cuando el código no está vacío y ambos valores son numéricos. No existe validación del nombre de las cabeceras, cantidad de columnas, valores negativos, rangos, duplicados ni consistencia mínimo ≤ máximo. Los materiales inexistentes y las filas inválidas se omiten silenciosamente.

La búsqueda es `Material::where('code', $materialCode)->first()`. No identifica `StockItem`, variante, SKU, company, warehouse ni location.

## Template Download

La acción auxiliar conserva el endpoint actual y se presenta como `Descargar plantilla`. El controller intenta descargar:

`public/excels/excelOrigin/ejemploExcelMinimoMaximo.xlsx`

Ese archivo y el directorio `public/excels` no existen en el checkout actual. El GET terminará en error hasta que backend/despliegue reponga el artefacto o defina otra fuente. La ruta tampoco posee nombre ni middleware `permission:stock_files`.

## Scope / Multitenancy

`Material` usa `BelongsToTenant`; con `tenant.context` activo, el `TenantScope` agrega `materials.tenant_id = TenantContext::tenantIdOrNull()` a la búsqueda por código. Un código manipulado no puede resolver un Material de otro tenant mientras el middleware mantenga un contexto tenant válido.

El proceso no consulta ni filtra `company_id`, branch, warehouse o location. Actualiza campos legacy tenant-wide del Material. Por ello:

- el aislamiento entre tenants sí existe por el global scope;
- no existe aislamiento por company para esta operación;
- las companies del mismo tenant comparten el cambio en `materials.stock_min`/`stock_max`;
- no se modifican los umbrales company-aware de `inventory_levels.min_alert`/`max_alert`.

No se envían IDs de tenant o company desde la vista.

## Stock Fields Modified

El import modifica exclusivamente:

- `materials.stock_min`;
- `materials.stock_max`;
- `materials.updated_at`, como efecto de `save()`.

No modifica stock físico, reservado, `StockItem`, `Variant`, `CompanyStockItem`, `InventoryLevel`, almacenes ni ubicaciones. La interfaz evita el término genérico “stock físico” y describe la operación como actualización de mínimos y máximos.

## Validation / Error Contract

- Validación de Form Request: HTTP 422 con `message` y `errors.file`; el JS muestra los mensajes existentes mediante Toastr.
- Excepción durante lectura o persistencia: HTTP 422 con `message` devuelto por el controller.
- Éxito: HTTP 200 con `Archivo subido exitosamente.`; el JS muestra Toastr y recarga a los dos segundos.
- Filas inválidas o materiales no encontrados: no generan error, conteo ni detalle; se omiten y la respuesta sigue siendo exitosa.
- La transacción cubre los `save()` de Material: una excepción revierte los cambios de base de datos realizados dentro del ciclo.
- El movimiento del archivo ocurre antes de leerlo y fuera de una operación reversible de almacenamiento. Un rollback de base de datos no elimina el archivo.

## UX Pattern

La pantalla usa el lenguaje Venti Next existente sin CSS nuevo:

- Page Header con eyebrow, título operativo, descripción y breadcrumb;
- toolbar con contexto, Cancelar secundario e Importar como única acción primaria;
- `next-form-section` como superficie continua;
- warning soft semántico con icono y copy contractual preciso;
- `custom-file` Bootstrap, nombre local visible, extensiones y límite;
- Importar deshabilitado hasta que exista una selección local;
- Descargar plantilla como acción auxiliar outline separada del input;
- reset local sin petición;
- Toastr existente para la respuesta del backend.

La pantalla es un primer candidato a **Import / Bulk Operation Form**, pero no se promueve todavía a `VENTI-NEXT-COMPONENT-PATTERNS.md`: el catálogo actual contiene un solo flujo real de importación y la regla de promoción exige validación en un segundo workflow sin selectores ni payloads específicos.

## Permissions

El permiso real del formulario y del POST es `stock_files`. La vista no crea una autorización paralela. El endpoint de plantilla permanece dentro del grupo autenticado y tenant-aware, pero carece del permiso específico; se documenta como dependencia backend.

## Backend Sync Notes

### [OPEN] Plantilla ausente y endpoint sin autorización específica

- **Archivo / endpoint:** `UploadFilesController@downloadExampleStockFile`, `GET /dashboard/download/example/stock/file`.
- **Impacto:** el botón visible apunta al contrato real, pero el archivo objetivo no existe y el GET falla; además cualquier usuario autenticado con contexto puede intentar descargarlo sin `stock_files`.
- **Backend debe:** restaurar/generar la plantilla, definir su despliegue y aplicar el permiso final; idealmente asignar route name.
- **Frontend puede continuar:** Sí. La acción se conserva sin ejecutarla durante QA.

### [OPEN] Semántica legacy frente a inventario company-aware

- **Archivo / endpoint:** `UploadFilesController@uploadFilesStocksMaterials`.
- **Impacto:** el import escribe mínimos/máximos tenant-wide en `materials`, mientras el inventario multitenant moderno usa `InventoryLevel` por company, StockItem, warehouse y location. Puede no actualizar el dato mostrado por los flujos modernos y afecta a todas las companies del tenant en el campo legacy.
- **Backend debe:** decidir si el import seguirá siendo tenant-wide legacy o migrará a `InventoryLevel.min_alert/max_alert` con claves inequívocas de company/StockItem/ubicación.
- **Frontend puede continuar:** Sí, con copy limitado al comportamiento real actual.

### [OPEN] Política de filas y resultado parcial

- **Archivo / endpoint:** `UploadFilesController@uploadFilesStocksMaterials`.
- **Impacto:** filas inválidas, códigos inexistentes y datos incoherentes se omiten silenciosamente; una respuesta 200 no prueba que todas las filas hayan sido aplicadas.
- **Backend debe:** definir esquema/cabeceras, rangos, relación mínimo-máximo, duplicados, política all-or-nothing y un resumen estable de filas procesadas/rechazadas.
- **Frontend puede continuar:** Sí; no se inventan resultados ni estados parciales.

### [OPEN] Ciclo de vida y exposición del archivo

- **Archivo / endpoint:** `UploadFilesController@uploadFilesStocksMaterials`.
- **Impacto:** el archivo se mueve a `public/excels/{nombre original}`, no se elimina tras éxito o error y puede colisionar con un nombre previo. La alerta Legacy afirmaba incorrectamente que se eliminaba.
- **Backend debe:** usar almacenamiento temporal no público, nombre seguro/único y eliminación garantizada en `finally`, o documentar una política de retención deliberada.
- **Frontend puede continuar:** Sí; se retiró la promesa falsa de eliminación.

## Revisit Triggers

- Se define el destino company/StockItem/warehouse/location de los umbrales.
- La plantilla vuelve a estar disponible o cambia su endpoint/esquema.
- Backend devuelve conteos, errores por fila o resultado parcial.
- Se valida un segundo import real y puede promoverse `Import / Bulk Operation Form`.
- Cambian límite, extensiones, columnas o política de retención.
- El POST deja de usar AJAX/FormData o incorpora procesamiento asíncrono.

