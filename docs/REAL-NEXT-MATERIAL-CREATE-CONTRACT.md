# REAL NEXT — Material Create Contract

Fecha de validación: 2026-09-24  
Pantalla: `material.create` (`GET /dashboard/crear/material`)  
Estado: `BASELINE REUSE APPROVED`

## Alcance

Esta fase reconstruye únicamente `resources/views/material/create.blade.php` sobre el frontend activo `aliexpress`. `venti-next` se usó como fuente de verdad visual y `aliexpress` como fuente de verdad funcional. La hoja `public/admin/dist/css/venti-next.css` activa se sustituyó, con autorización expresa de esta fase, por la hoja aprobada del baseline; no se copiaron rutas, controladores, contratos backend ni otros archivos entre proyectos. No se habilitaron `material.indexV2`, ventas ni otras rutas.

La carga visual queda controlada así:

1. AdminLTE 3.
2. `admin/dist/css/venti.min.css` como base Venti Legacy.
3. `admin/dist/css/venti-next.css` como capa REAL NEXT.

La tercera capa se carga exclusivamente cuando la ruta nombrada está incluida en `config/venti-next.php`. La allowlist actual contiene solo `material.create`.

## Reutilización limpia del baseline

- Las hojas `venti-next/public/admin/dist/css/venti-next.css` y `aliexpress/public/admin/dist/css/venti-next.css` son idénticas byte a byte (`SHA-256 8D138C549DF113602C914D546D3C081AFC4A89ACF3D7C0899139022D4B73CC68`).
- Se eliminó la capa paralela agregada en `aliexpress`: aliases de tokens, hardcodes, selectores por ID, proporciones recreadas, `nth-child`/`nth-last-child`, compensaciones de orden y breakpoints duplicados.
- El Blade usa directamente las cuatro secciones hermanas, el grid Bootstrap aprobado, `.next-variant-mode`, `.next-operational-layout`, `.next-image-panel`, la tabla semántica con `colgroup` y `.next-aux-modal`/`.next-aux-modal-form`.
- No quedan anchos inline en la vista. Los únicos `style` inline restantes controlan visibilidad contractual (`display: none`) y coinciden con la interacción existente.
- No se añadió ninguna regla CSS exclusiva de `material.create`: las diferencias reales se expresan con el mismo markup y las mismas clases del baseline.

## Cierre de paridad visual

La pantalla renderizada de `venti-next` se comparó lado a lado con la pantalla real de `aliexpress`. Se recuperaron del baseline aprobado los tokens de color y superficie, el shell navy/blanco, la jerarquía tipográfica, los bordes y radios, las sombras contenidas, la superficie continua del formulario, los encabezados numerados, el panel operativo 8/4, la sección independiente de variantes, la tabla semántica y el tratamiento de modales auxiliares.

La adaptación conserva el Blade real y sus campos adicionales `material_type`, `subtype` y `typescrap`. Estos tres campos se insertan en el mismo `col-md-6 col-xl-3` de clasificación y explican la única altura adicional de esa sección. En la tabla se mantuvieron todos los selectores y datos de talla/color, pero se agruparon visualmente en la columna semántica **Variante** para respetar la densidad aprobada de nueve columnas.

Las diferencias visibles restantes responden al contrato multitenant real: identidad de empresa/local/usuario, tres campos comerciales adicionales y catálogos propios del tenant.

## Contrato backend activo

### Acceso y multitenancy

`material.create` conserva los middleware:

- `auth`
- `check.user.enabled`
- `password.changed`
- `tenant.context`
- `permission:create_material`

`MaterialController@create` resuelve el `company_id` mediante `TenantContext`, consulta `MaterialDetailSetting::forCompany(...)` y entrega `$enabled`. Las colecciones auxiliares se cargan condicionalmente según las secciones habilitadas de la empresa activa.

`MaterialController@store` continúa siendo la única autoridad de persistencia. Resuelve tenant, empresa, almacén y ubicación en servidor; la vista no introduce IDs de tenant o empresa controlables por el cliente.

### Secciones gobernadas por `$enabled`

La vista conserva las condiciones activas para:

- `brand`
- `exampler`
- `category`
- `subcategory`
- `material_type`
- `subtype`
- `typescrap`
- `genero`
- `unit_measure`
- `perecible`
- `talla`
- `color`

Las variantes solo pueden elegirse cuando `talla` y `color` están habilitados simultáneamente.

### Cadena de clasificación preservada

La cadena real sigue siendo:

`category` → `subcategory` → `material_type` → `subtype`

Se conservaron los IDs y names usados por `public/js/material/create.js`:

- `#category` / `category`
- `#subcategory` / `subcategory`
- `#material_type` / `material_type`
- `#subtype` / `subtype`
- `#typescrap` / `typescrap`

No se reintrodujo el selector histórico `#type`.

## Permisos de acciones auxiliares

Los disparadores visuales de alta auxiliar se muestran con `@can` usando los permisos reales de sus rutas:

| Acción | Permiso |
| --- | --- |
| Marca | `create_brand` |
| Modelo | `create_exampler` |
| Categoría | `create_category` |
| Subcategoría | `create_subcategory` |
| Tipo de material | `create_materialType` |
| Subtipo | `create_subType` |
| Tipo de retacería | `create_typeScrap` |
| Género | `create_genero` |
| Unidad de medida | `create_unitMeasure` |
| Talla | `create_talla` |
| Color | `create_color` |

Las rutas backend conservan su middleware de permiso y siguen siendo la barrera definitiva. Los modales permanecen en el DOM para no alterar el contrato JS existente, pero sin disparador visible cuando el usuario no posee el permiso correspondiente.

## Backend Sync Notes

[OPEN]
Punto: La lectura de tipos de material depende de un permiso de eliminación.
Archivo / endpoint: `routes/web.php:475` — `GET /dashboard/get/types/{subcategory_id}` con `permission:destroy_materialType`.
Impacto: Los dos owners de QA cargaron tipos porque ambos poseen efectivamente `destroy_materialType`; un usuario futuro con `create_material` pero sin permiso de eliminar tipos recibiría `403` y no podría completar la cadena de clasificación.
Qué debe definir/corregir backend: Alinear el middleware con un permiso de lectura/creación de material o documentar explícitamente que `destroy_materialType` forma parte del contrato de `material.create`.
Frontend puede seguir avanzando: Sí

[RESOLVED]
Punto: Contexto multitenant y configuración de campos de `material.create`.
Contrato confirmado: `TenantContext` resuelve empresa/local activos y `MaterialDetailSetting::forCompany(...)` gobierna `$enabled`; el frontend no fuerza campos deshabilitados ni envía IDs de tenant/empresa.

### Evidencia de `/get/types`

La ruta activa:

`GET /dashboard/get/types/{subcategory_id}`

continúa protegida por:

`permission:destroy_materialType`

Esto no es coherente con la operación de lectura ni con `permission:create_material`. Un usuario que puede crear materiales pero no eliminar tipos puede:

1. abrir `material.create`;
2. seleccionar una categoría;
3. seleccionar una subcategoría;
4. recibir `403` al intentar cargar los tipos de material;
5. quedar impedido de completar `material_type` y `subtype` cuando esas secciones son obligatorias/habilitadas.

En las dos sesiones de QA el endpoint respondió funcionalmente y pobló tipos porque ambos roles owner incluyen `destroy_materialType`. No se observó `403` con estos fixtures. La dependencia semántica incorrecta permanece clasificada como **BACKEND SYNC OPEN**; no se modificó la ruta, el controlador, el middleware ni los permisos.

## Contrato de envío preservado

El botón `#btn-submit` continúa invocando `storeMaterial` y enviando `FormData` a `route('material.store')` con CSRF. No se cambió la serialización ni las reglas de `StoreMaterialRequest`.

Se preservan:

- `variantes_json`
- claves de archivo `variant_image_N`
- `image_key` dentro del JSON de variantes
- la imagen principal gestionada por Dropzone
- Select2
- Bootstrap Switch
- iCheck
- altas auxiliares por modal/AJAX
- SKU y código de barras
- stock mínimo y máximo
- estado e inventariabilidad por variante

El cambio JS se limita a integración DOM: restauración de foco en modales, estado vacío de variantes y nombre visible del archivo seleccionado. No altera endpoints ni payloads.

### Selectores y eventos JS modificados

| Selector / función | Evento o cambio | Motivo |
| --- | --- | --- |
| `.next-material-page ~ .modal` | `show.bs.modal` | recordar el disparador que abrió el modal |
| `.next-material-page ~ .modal` | `shown.bs.modal` | enfocar el primer control visible |
| `.next-material-page ~ .modal` | `hidden.bs.modal` | devolver el foco al disparador |
| `[data-delete]` | `click` | conservar la eliminación y restaurar el estado vacío cuando ya no quedan variantes |
| `[data-image_variante]` | `change` | mostrar el nombre local del archivo sin cambiar `variant_image_N` |
| `appendVariantRow(...)` | integración DOM | retirar `[data-variants-empty]` antes de agregar el nuevo registro |
| `limpiarSeccionConVariantes()` | integración DOM | volver a renderizar el estado vacío |
| `renderVariantsEmptyState()` | función nueva | crear una fila semántica vacía sin participar en el payload |

Los selectores contractuales `#formCreate`, `#btn-submit`, `#body-variantes`, `.item-variante` y todos los atributos `data-*` usados para construir `variantes_json` permanecen vigentes.

## Validación y no persistencia

Durante la fase no se invocó `material.store`, no se enviaron formularios auxiliares y no se crearon materiales, variantes, catálogos ni fixtures.

Validaciones ejecutadas:

- compilación completa de vistas Blade con PHP 7.3.33;
- sintaxis de `config/venti-next.php`;
- sintaxis de `public/js/material/create.js`;
- inspección de rutas y middleware con `artisan route:list`;
- comprobación de whitespace con `git diff --check`.

## QA real multitenant

### Sesión Owner EDESCE

- Tenant activo: `Grupo Empresarial EDESCE` (`tenant_id=1`).
- Empresa activa: `EDESCE CONSULTORA E.I.R.L.` (`company_id=1`).
- Local activo: `Local Principal` (`branch_id=1`).
- `$enabled`: `unit_measure`, `brand`, `exampler`, `genero`, `talla`, `color`, `perecible`, `category`, `subcategory`, `material_type`, `subtype`, `typescrap`.
- Permisos auxiliares: los once disparadores de alta están disponibles; incluye `create_material` y `destroy_materialType`.
- Cadena comprobada: `CELULARES → SMARTPHONE → METALICO → BRILLANTE/OPACO`.
- Cambio de categoría: limpia subcategoría, tipo y subtipo antes de cargar el catálogo del nuevo padre.
- Catálogos observados: siete categorías; marcas, géneros, tallas, colores y modelos con datos propios del tenant.
- Nombre completo comprobado: descripción + marca + modelo + género; SKU sugerido actualizado por la lógica existente.

### Sesión Owner Gamarra

- Tenant activo: `Gamarra Test Editado` (`tenant_id=3`).
- Empresa activa: `Gamarra Moda SAC` (`company_id=3`).
- Local activo: `Local Principal` (`branch_id=4`).
- `$enabled`: idéntico al de la primera empresa.
- Permisos auxiliares: los once disparadores de alta están disponibles; incluye `create_material` y `destroy_materialType`.
- Cadena comprobada: `TOMATODOS → METALICO → ACERO/PLASTICO`; `ACERO → INOXIDABLE` y `PLASTICO → SIMPLE`.
- Cambio de tipo: limpia el subtipo seleccionado antes de cargar el catálogo del nuevo padre.
- Catálogos observados: dos categorías, dos marcas, dos géneros, una talla y ningún color; son datos distintos y aislados del primer tenant.
- Nombre completo comprobado: `TERMO DEMO QA MARCA NUEVA MODELO NUEVO HOMBRE`; SKU sugerido `TER-MAR-MOD-HOM`.

No hubo diferencias de campos visibles porque ambas empresas habilitan exactamente las mismas doce secciones. Sí hubo diferencias reales en el contenido de los catálogos. Retacería, tipo/subtipo y el bloque de variantes respetaron la configuración común sin mezclar datos entre tenants.

## QA de interacción

- Layout, sidebar, navbar, canvas, Page Header, toolbar y las cuatro secciones cargan correctamente.
- Select2, Bootstrap Switch y Dropzone inicializan sin IDs duplicados.
- Los once modales disponibles por permisos se abren con backdrop, exponen Guardar/Cancelar, reciben foco inicial y restauran el foco al disparador; Escape y Cancelar cierran sin persistir.
- Se corrigió el cierre real de Cancelar en los once modales, el foco determinista, la sincronización del `brand_id` del modal Modelo y el hidden correcto de Categoría para Subcategoría.
- La variante talla/color se genera en tabla semántica; Estado e Inventario alternan, el archivo local muestra su nombre y eliminar la última variante restaura el estado vacío.
- Inspección de `buildMultipleVariantsPayload(...)`: conserva `variantes_json`, adjunta cada archivo como `variant_image_N` y guarda la clave correlativa en `image_key`.
- No se pulsó Guardar material ni ningún Guardar de modal; `material.store` no fue invocado.

## QA responsive y capturas

- Comparación baseline/real 1600×900: geometría idéntica en Inventario e imagen, alineación SKU/código/paquete, radios, bordes, spacing y columnas; la clasificación real suma exclusivamente los tres campos multitenant.
- Comparación baseline/real 1280×720: mismo grid, mismas proporciones operativas y sin overflow horizontal global.
- Comparación baseline/real 1024×768: mismo apilado de Inventario e Imagen a ancho completo, sin overflow horizontal global.
- Comparación baseline/real 390×844: `innerWidth=390`, documento útil de 375 px, sin overflow horizontal global; toolbar, campos operativos, Dropzone y variantes reproducen el responsive del baseline.
- Con la sección 04 visible, la separación 03/04, altura, ancho, `colgroup` y las nueve proporciones de columna resultaron idénticas al baseline a 1600 px. En móvil la tabla semántica no provoca overflow global.
- La hoja contiene reglas específicas a 1199.98, 767.98 y 420 px para formulario, toolbar, paneles operativos, modal y tarjetas de variantes.

## Bugs frontend corregidos durante QA

1. `Cancelar` solo reseteaba el formulario y dejaba abierta la modal: ahora descarta y cierra.
2. El foco inicial/restaurado podía perderse por el orden de eventos Bootstrap: ahora usa el elemento nativo tras `shown/hidden`.
3. Modelo podía abrir con `brand_id_hidden` vacío: ahora se sincroniza en `show.bs.modal`.
4. Subcategoría escribía en `#category_id_hidden`, selector inexistente: ahora usa `#categoria_id_hidden`.
5. Botones de Tipo, Subtipo y Retacería carecían de nombre accesible completo: se añadieron `title` y `aria-label`.

## Decisión

La pantalla queda implementada bajo el contrato multitenant activo y aislada a `material.create`. El QA funcional real de ambos tenants continúa aprobado, el único punto backend abierto no impide a estos owners continuar y la comparación renderizada confirma que referencia y pantalla real pertenecen al mismo sistema visual.

Estado: **BASELINE REUSE APPROVED**.
