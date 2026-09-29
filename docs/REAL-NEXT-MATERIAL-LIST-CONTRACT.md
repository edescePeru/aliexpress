# REAL NEXT — Material List Contract

## Estado

**APPROVED WITH BACKEND SYNC OPEN**

Este documento describe el contrato vigente de `material.indexV2` en el proyecto real multitenant. No reemplaza ni corrige retroactivamente `NEXT-MATERIAL-LIST-CONTRACT.md`, que se conserva como contrato histórico del experimento visual.

## 1. Contrato real auditado

- Ruta de pantalla: `GET /dashboard/listado/materiales/v2`, nombre `material.indexV2`.
- Acción: `MaterialController@indexV2`.
- Middleware efectivo: `web`, `auth`, `check.user.enabled`, `password.changed`, `tenant.context`, `permission:list_material`.
- Blade: `resources/views/material/indexv2.blade.php`.
- JavaScript: `public/js/material/indexV2.js`.
- Endpoint de datos: `GET /dashboard/get/data/material/v2/{numberPage}` → `MaterialController@getDataMaterials`, con el mismo contexto y permiso de listado.
- Paginación contractual: 10 registros por página; respuesta `{ data, pagination }` con `currentPage`, `totalPages`, `startRecord`, `endRecord`, `totalRecords` y `totalFilteredRecords`.
- Activación visual: `config/venti-next.php` incluye solamente las rutas aprobadas, ahora también `material.indexV2`.
- `Material`, categorías, marcas, tipos, subtipos y retacerías conservan su aislamiento mediante el contexto tenant del backend real. La configuración visible se obtiene con `MaterialDetailSetting::forCompany(TenantContext::companyId())`.

## 2. Patrones baseline reutilizados

Se reutilizan sin crear una variante visual paralela:

- `Advanced Operational List` mediante `.next-operational-list`, `.next-advanced-operational-list` y `.next-material-list`.
- `List Toolbar`, búsqueda simple y trigger de filtros avanzados.
- `Advanced Filters` y su grid responsive.
- `Result Summary`.
- `Column Visibility Menu` como floating surface.
- `Operational Table` dentro de `.table-responsive`.
- `Row Actions Dropdown` con un único ellipsis por fila.
- lenguaje de badges semánticos suaves.
- paginación Next y empty state.

El archivo `public/admin/dist/css/venti-next.css` reutiliza el baseline aprobado. La única evolución compartida posterior desacopla los estados hover/focus de Row Actions Dropdown del ancestro tabla para permitir que el menú se porte temporalmente fuera del scroll container.

## 3. Diferencias multitenant válidas

- El listado real incorpora `tipo_material`, `subtipo` y `retaceria` además de las columnas del baseline histórico.
- `$enabled` de la empresa activa decide los defaults de unidad, categoría, subcategoría, marca, modelo, tipo, subtipo y retacería.
- Los catálogos se resuelven dentro del tenant activo.
- El stock físico detallado se consulta en el contexto de la company activa.
- Se mantienen las acciones, permisos, endpoints y payloads reales de `aliexpress`; el JS experimental no reemplaza al JS real.

No se restauraron `customSwitch1…12`, la lista fija de doce columnas, ni filtros activos de cédula o calidad.

## 4. Filtros

La búsqueda simple usa `#description`. Los filtros avanzados vigentes son:

- `#category` → `category`;
- `#subcategory` → `subcategory`;
- `#material_type` → `material_type`;
- `#sub_type` → `sub_type`;
- `#marca` → `marca`;
- `#retaceria` → `retaceria`;
- `#rotation` → `rotation`;
- `#code` → `code`;
- `#isPack` → `isPack`.

La dependencia real queda como Categoría → Subcategoría → Tipo de material → Subtipo. Cambiar un padre limpia los hijos. Se preservan los GET `/dashboard/get/subcategories/{category}`, `/dashboard/get/types/{subcategory_id}` y `/dashboard/get/subtypes/{type_id}`. Se retiraron únicamente inicializaciones y parámetros AJAX no-op de `cedula/calidad`.

## 5. Columnas

El contrato actual contiene quince columnas configurables y una columna fija de acciones:

1. `codigo`;
2. `descripcion`;
3. `unidad_medida`;
4. `stock_actual`;
5. `stock_min`;
6. `stock_max`;
7. `categoria`;
8. `sub_categoria`;
9. `marca`;
10. `modelo`;
11. `tipo_material`;
12. `subtipo`;
13. `retaceria`;
14. `imagen`;
15. `rotation`;
16. acciones, no configurable.

El empty state calcula su `colspan` como `activeColumns.length + 1`.

## 6. Column Visibility Menu

Se conservan `.column-toggle` y los IDs reales descriptivos: `columnCodigo`, `columnDescripcion`, `columnUnidadMedida`, `columnStockActual`, `columnStockMin`, `columnStockMax`, `columnCategoria`, `columnSubcategoria`, `columnMarca`, `columnModelo`, `columnTipoMaterial`, `columnSubtipo`, `columnRetaceria`, `columnImagen` y `columnRotation`.

El menú permanece abierto al cambiar switches, actualiza cabecera y filas por AJAX, y usa `$enabled` para los defaults configurables. El contrato vigente no implementa persistencia entre recargas; no se inventó almacenamiento client-side.

## 7. Tabla

La tabla conserva densidad ERP, encabezados claros, imagen, stock, rotación y acciones. El overflow horizontal queda encapsulado en `.table-responsive`; el documento no adquiere overflow horizontal global. No se creó una segunda familia de tabla ni un breakpoint nuevo.

## 8. Acciones

Cada fila usa un único `.next-row-actions-trigger`. El menú conserva los hooks y permisos reales:

- editar: `data-editar_material` / `update_material`;
- variantes: `data-ver_variants` / `verVariantes_material`;
- precios: `data-precioDirecto` / `gestionarPrecios_material`;
- vencimientos: `data-show_vencimiento` / `verVencimientos_material`;
- presentaciones: `data-manage_presentations` / `managePresentations_material`;
- asignar hijo: `data-assign_child` / `assignChild_material` y solo paquetes;
- separar paquete: `data-separate` / `separatePacks_material` y solo paquetes;
- deshabilitar: `data-deshabilitar` / `enable_material`.

La acción destructiva queda separada visualmente al final con `.next-row-action-danger`. Si el usuario no dispone de acciones visibles, la celda muestra un guion accesible.

Para evitar clipping por `.table-responsive`, el inicializador compartido de `public/js/layout/admin2.js` porta `.next-row-actions-menu` a `body` durante `show.bs.dropdown` y lo devuelve a su dropdown en `hidden.bs.dropdown`. Bootstrap 4 y Popper conservan el posicionamiento y el flip automático (`bottom-end`/`top-end`) con boundary de viewport. El menú mantiene `aria-labelledby`, Escape, restauración de foco y no deja nodos huérfanos.

## 9. Stock

El JSON vigente expone:

- `stock_current`: valor mostrado en la columna Stock actual; proviene de `Material::stock_current_total`.
- `stock_min`: `stock_min_total`.
- `stock_max`: `stock_max_total`.
- `stock_actual`: columna legacy `materials.stock_current`, actualmente utilizada como `data-quantity` de Separar paquete.

`stock_current_total` devuelve el stock físico de la company activa cuando existe exactamente un StockItem activo sin variante; devuelve `null` para múltiples StockItems o variantes, y la UI presenta `Ver Inv.`. Si no existen StockItems, mantiene el fallback legacy `materials.stock_current`.

## 10. Inventario detallado

- Endpoint nominal: route `material.inventory-levels`.
- URI efectiva actual: `GET /dashboard/dashboard/material/{material}/inventory-levels`.
- Acción: `StockItemController@getInventoryLevelsByMaterial`.
- Middleware: `web`, `auth`, `check.user.enabled`, `password.changed`, `tenant.context`.
- URL inyectada al JS mediante `window.materialInventoryLevelsUrl`.
- El controlador restringe StockItems habilitados para la company activa y filtra `inventoryLevels.company_id` con `TenantContext::companyId()`.
- Se preservan modal, estructura JSON y callbacks vigentes.

## 11. Estados y badges

El backend sigue entregando el HTML de rotación (`ALTA`, `MEDIA`, `BAJA`) con clases `bg-*`. El adaptador frontend traduce esas clases a `badge-success`, `badge-warning` o `badge-danger` y elimina `bg-*`/`text-md`, reutilizando el lenguaje semántico suave del baseline sin cambiar el payload.

## 12. Paginación

La paginación continúa siendo AJAX y server-side, diez filas por página. Se preservan `#pagination`, `#textPagination`, `[data-item]`, páginas anterior/siguiente y el resumen de rango. La navegación fue probada con 15 registros y dos páginas.

## 13. JavaScript adaptado

`public/js/material/indexV2.js` se mantuvo como fuente funcional. Las adaptaciones se limitan a:

- integración del menú aprobado de columnas;
- `aria-expanded` para filtros avanzados;
- submit del toolbar;
- cadena Categoría → Subcategoría → Tipo → Subtipo;
- render dinámico de header y empty-state;
- botones neutrales `Ver Inv.`;
- normalización visual de badges;
- labels accesibles del ellipsis;
- detección de menú de acciones vacío;
- retiro de parámetros legacy no activos `cedula/calidad`.

No se alteraron callbacks mutadores, rutas, permisos, modales ni payloads funcionales vigentes.

## 14. CSS nuevo

No se añadieron tokens, estilos visuales, radios, sombras, superficies ni breakpoints. Dos selectores existentes de hover/focus se hicieron context-independent (`.next-row-actions-menu …`) para que conserven el mismo aspecto cuando el menú está portaled a `body`.

## 15. Comparación de tenants

QA local read-only/read-mostly:

- EDESCE CONSULTORA E.I.R.L. / Local Principal: 15 materiales, catálogos propios, 15 columnas activas y seis accesos `Ver Inv.` en la primera página.
- Gamarra Moda / Local Principal: 2 materiales propios, categorías `TOMATODOS`/`TERMOS`, marcas propias y cadena completa hasta subtipo `INOXIDABLE`.
- Ambos owners mostraron los mismos defaults `$enabled` para esta configuración y el mismo conjunto visible de acciones permitido.
- Los IDs de materiales renderizados fueron distintos entre contextos observados: EDESCE mostró su catálogo paginado; Gamarra mostró solamente sus dos materiales.
- No se observaron errores de consola ni mezcla visible de catálogos entre tenants.
- Gamarra no tenía filas que requirieran `Ver Inv.`; el inventario detallado se validó con una fila variante de EDESCE y devolvió únicamente la estructura resuelta para su company activa.

## 16. Responsive

Se validaron 1600×900, 1280×720, 1024×768 y 390×844.

- Cero overflow horizontal global en los cuatro anchos.
- Scroll horizontal local de tabla cuando el ancho disponible es menor al ancho de las columnas.
- Toolbar, filtros y controles permanecen dentro del viewport.
- En 390 px los filtros ocupan una columna útil de 306 px y el menú de columnas queda dentro del viewport (334 px de ancho).
- El trigger de acciones permanece accesible en el borde derecho de la tabla.
- Los menús de filas finales usan `top-end` cuando no hay espacio inferior y permanecen completos dentro del viewport.
- El modal de confirmación probado en 390 px quedó dentro del viewport (374 px de ancho); se cerró sin confirmar ninguna mutación.
- Paginación AJAX utilizable y menú de columnas operativo.

## 17. Backend Sync Notes

1. `GET /dashboard/get/types/{subcategory_id}` exige `permission:list_materialType`, mientras la pantalla solo exige `permission:list_material`. Un usuario con permiso de materiales pero sin permiso de tipos puede ver la pantalla y recibir 403 al completar esa cadena de filtros.
2. La ruta nominal `material.inventory-levels` produce actualmente una URI con doble prefijo `/dashboard/dashboard/...`. Funciona mediante la URL generada por Laravel, pero conviene normalizarla coordinadamente si se modifica el grupo de rutas.
3. El resumen superior de alertas usa `Material::resumenPorMaterial()`, que agrega las columnas legacy de `materials`; no usa el inventario físico company-aware de `InventoryLevel`.
4. La prioridad calculada en `getDataMaterials` también compara columnas legacy, aunque actualmente no se muestra como columna.

## 18. Open Business Decisions

- Definir la fuente final del stock agregado company-aware para materiales simples, variantes y múltiples StockItems.
- Definir si `stock_actual` seguirá significando `materials.stock_current`, será un alias company-aware o desaparecerá del contrato.
- Definir qué cantidad debe limitar `Separar paquete`: stock legacy, stock físico company-aware, stock disponible descontando reservas u otra métrica.
- Definir si mínimo/máximo y resumen de alertas son atributos globales del material, de StockItem o de CompanyStockItem/InventoryLevel.

La UI no inventa respuestas para estas decisiones; conserva el contrato real actual.

## 19. Revisit Triggers

Revisar esta pantalla si ocurre cualquiera de estos cambios:

- cambia la semántica o nombre de `stock_current`, `stock_actual`, `stock_min` o `stock_max`;
- Separar paquete recibe una cantidad company-aware diferente;
- el resumen de stock migra a InventoryLevel/CompanyStockItem;
- se unifican permisos de listado con los endpoints dependientes;
- cambia la URI o el JSON de inventario detallado;
- `$enabled` incorpora nuevas secciones o persistencia de visibilidad;
- se añaden o eliminan columnas/filtros del contrato real.

## 20. Archivos modificados

- `resources/views/material/indexv2.blade.php`.
- `public/js/material/indexV2.js`.
- `public/js/layout/admin2.js`, inicializador compartido del portal de Row Actions Dropdown.
- `config/venti-next.php`.
- `public/admin/dist/css/venti-next.css`, selectores compartidos compatibles con el portal.
- `docs/REAL-NEXT-MATERIAL-LIST-CONTRACT.md`.

No se modificaron controllers, models, requests, middleware, rutas ni persistencia.

## 21. Validaciones técnicas

- `node --check public/js/material/indexV2.js`.
- `node --check public/js/layout/admin2.js`.
- PHP 7.3.33: `php -l config/venti-next.php`.
- PHP 7.3.33: `php artisan view:cache`.
- `git diff --check`.
- QA de navegador sobre baseline y proyecto real.
- QA de ambos tenants, filtros dependientes, búsqueda, show/hide de columnas, empty state, paginación, acciones, inventario y responsive.
- QA específico de Row Actions Dropdown con 2, 5 y 10 filas; primera, intermedia y última fila; 1280×720 y 390×844; `bottom-end` y `top-end`; Escape; reinserción del menú; scroll horizontal local y cero overflow global.
- No se ejecutaron creates, edits, deletes, separación de paquetes, cambios de stock ni confirmaciones mutadoras.

## 22. Estado final

**APPROVED WITH BACKEND SYNC OPEN**

El frontend cumple el patrón aprobado de Advanced Operational List y conserva el contrato multitenant actual. Los puntos abiertos están limitados a semántica y sincronización backend futura; no impiden usar el listado bajo el contrato vigente.
