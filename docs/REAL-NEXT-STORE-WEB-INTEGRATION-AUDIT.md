# REAL NEXT 9 — Catalog Integration Audit

Estado de integración funcional: **NEEDS BACKEND ALIGNMENT**.

Actualización REAL NEXT 9.1 (2026-09-28): **STORE WEB FROZEN SHELL READY**. Se migraron assets permanentes y se extrajo el shell congelado a los tres Blades objetivo, con fixtures restringidos al preview local; 30 comparaciones responsive verificadas. El informe histórico de REAL NEXT 9 y sus hashes se conservan abajo. Ver [Store Web Asset Migration](REAL-NEXT-STORE-WEB-ASSET-MIGRATION.md) para archivos, pruebas y estado frontend; SW-01…SW-07 permanecen abiertos. Esta actualización no convierte los fixtures en catálogo público ni habilita consultas reales.

Fecha: 2026-09-28 (America/Lima). Alcance: exclusivamente Store Web / catálogo público de `aliexpress`. Esta fase documenta; no implementa, migra ni elimina archivos de aplicación.

Se leyó completo `AGENTS.md`, incluida `Store Web reference rule`. Fuente funcional: `aliexpress`. Fuente visual y mobile-first congelada: `project-catalog`. Ninguna propuesta permite rediseñar, simplificar o normalizar al backoffice. Toda desviación visual necesaria por contrato debe justificarse y aprobarse antes de implementarse.

## Evidence and audit limits

- Inspección de las tres páginas HTML, su JS completo, los 15 archivos Sass, CSS compilado/dependencias, fuentes y árbol de imágenes; inventario reproducible al final.
- Inspección de rutas, controlador completo, Blades objetivo, layout compartido, JS de catálogo/búsqueda, inicialización legacy y modelos/scopes/settings relacionados.
- Confirmación efectiva con PHP **7.3.33**: `artisan route:list --path=store-web`, exit 0, diez rutas, todas con middleware `web`, sin dominio específico.
- Comprobación de 114 referencias locales en HTML/CSS/Sass (src, href, data-gallery-image y url): ninguna ruta faltante en la referencia. Inventario de 56 archivos con tamaño y SHA-256. Esto verifica presencia, no decodificación visual de imágenes/fuentes ni respuestas HTTP.
- Auditoría estática del responsive y de aislamiento. No se hicieron solicitudes HTTP con datos empresariales, consultas de negocio directas, escrituras en base de datos ni pruebas entre tenants. No se afirma paridad visual renderizada ni aislamiento probado en ejecución. La matriz de aceptación al final queda para integración.
- La búsqueda en `tests/` no encontró referencias a `StoreWeb`, `store-web`, `shop.products` o `shop.product`; no equivale a asegurar ausencia de toda cobertura indirecta.
- Se preservaron cambios preexistentes en `AGENTS.md`, login/home/welcome2, documentación de acceso público, layout y assets públicos. `project-catalog/` ya era un directorio no versionado. No se tocó ese trabajo.

## Current Backend Contract

### Routes, actions and middleware

Definición: `routes/web.php:3683–3707`. Controlador único: `app/Http/Controllers/StoreWebController.php`. Todos los métodos son GET/HEAD.

| URI | Route name | Action | Resultado |
|---|---|---|---|
| `/store-web/inicio` | `store-web.home` | `home` | `shop.home` |
| `/store-web/catalogo` | `store-web.catalog` | `catalog` | `shop.catalog` |
| `/store-web/tienda` | `store-web.tienda` | `tienda` | `shop.catalogNoPrice`, ruta lateral vigente |
| `/store-web/products/data/{pageNumber?}` | `shop.products.data` | `getDataProductsV2` | JSON `data`, `pagination` |
| `/store-web/categories/data` | `shop.categories.data` | `getCategoriesData` | JSON `data`, categorías con subcategorías |
| `/store-web/sizes/data` | `shop.sizes.data` | `getSizesData` | JSON `data`, tallas |
| `/store-web/colors/data` | `shop.colors.data` | `getColorsData` | JSON `data`, colores |
| `/store-web/product/{material}` | `shop.product.show` | `showProduct(Material $material)` | `shop.detailCatalog` |
| `/store-web/producto/{material}` | `shop.product.show.not.price` | `showProduct(Material $material)` | También `shop.detailCatalog` |
| `/store-web/search-product` | `shop.product.search` | `searchProduct` | JSON `success`, `product_id`, `url`, o error |

El parámetro real es `{material}`, con binding implícito por ID; no renombrarlo a `{id}`. El alias `producto` no selecciona un Blade sin precios: ambos apuntan al mismo método. `detailCatalogNotPrice.blade.php` existe, pero no se encontró un retorno a esa vista en las rutas/controladores examinados.

`RouteServiceProvider::mapWebRoutes` aplica `web`. `Kernel.php` incluye cookies cifradas, cookies en respuesta, sesión, errores compartidos, CSRF y `SubstituteBindings`. La pila global incluye TrustProxies, CORS, mantenimiento, límite de POST, trim y conversión de strings vacíos a null. No hay `auth`, `permission`, `tenant.context`, ni autorización propia en StoreWebController o el controlador base. No hay resolución pública por dominio/slug en este grupo. `tenant.context` está registrado, pero no aplicado aquí.

### Blades and current JavaScript

| Archivo | Responsabilidad actual |
|---|---|
| `resources/views/layouts/appShop.blade.php` | Shell Ashion, logos, menú, modal de búsqueda, footer, todos los plugins legacy |
| `resources/views/shop/home.blade.php` | Home legacy con contenido de plantilla; controller solo proporciona logo |
| `resources/views/shop/catalog.blade.php` | Sidebar categorías/precio/talla/color, contenedores AJAX, paginación, settings |
| `resources/views/shop/detailCatalog.blade.php` | Galería Owl, nombre/marca/precio agregado, talla/color, breadcrumb |
| `resources/views/shop/catalogNoPrice.blade.php` | Consumidor lateral que aún necesita shell/assets legacy |
| `resources/views/shop/detailCatalogNotPrice.blade.php` | Archivo legacy sin retorno localizado; no borrar por inferencia |
| `public/js/shop/catalog.js` | Estado de filtros, cuatro GET iniciales, render HTML, paginación, WhatsApp y Magnific Popup |
| `public/js/shop/search.js` | Submit `.search-model-form` → catálogo `?search=`; no usa el endpoint de primer resultado |
| `public/js/shop/catalogNoPrice.js` | Consumidor lateral con render/filtros propios |
| `public/shop/js/main.js` | Preloader, fondos, búsqueda modal, offcanvas, SlickNav, collapse, Owl, Magnific, NiceScroll, slider de precio, countdown demo, selección visual de talla |

`window.APP_SHOP` contiene `search` y `URLS.{PRODUCTS,DEFAULT_IMAGE,CATEGORIES,SIZES,COLORS,WHATSAPP,CAN_SHOW_PRICES,CAN_SHOW_PRESENTATIONS}`. PRODUCTS usa token `:page`. `window.APP_SHOP_SEARCH` contiene `URL` y `CATALOG_URL`. Preservar URLs generadas por Laravel y semántica del contrato; adaptar selectores/render a la referencia, sin copiar el DOM legacy.

Selectores actuales: `#products-container`, `#products-pagination`, `#categoriesAccordion`, `#sizes-container`, `#colors-container`, `#show-all-products`, `.category-filter`, `.subcategory-filter`, `.size-filter`, `.color-filter`, `#btn-filter-price`, `#minamount`, `#maxamount`, `#search-result-bar`, `#clear-catalog-search`, `.set-bg`, `.image-popup`. La integración necesitará un mapa explícito hacia `.catalog-*`, sin ejecutar simultáneamente ambos inicializadores sobre la nueva pantalla.

### Catalog view data and settings

`catalog(Request)` entrega exactamente: `logotipoEmpresa`, `maxPrice`, `whatsappEmpresa`, `showPricesCatalogEmpresa`, `showPresentationsEmpresa`, `descriptionFooterEmpresa`, `socialNetworksEmpresa`, `search`.

Settings: `company.branding.logo`, `company.profile.whatsapp`, `catalog.show_prices`, `catalog.show_presentations`, `catalog.footer_description` y `company.social.{facebook,twitter,youtube,instagram,pinterest,tiktok}`. Según `config/settings_catalog.php`, son de scope company; los dos show son booleanos. SettingService requiere tenant y company de sesión, busca definición activa y override exacto, o devuelve default. La definición efectiva está en DB: no se inspeccionaron sus registros.

WhatsApp se normaliza a dígitos. El Blade construye `https://wa.me/{numero}`. Logo se interpreta actualmente como nombre relativo a `images/logo/`, aunque la definición describe ruta o URL: falta cerrar el formato. Nombre comercial y color de tema no se entregan a estas vistas.

`maxPrice` usa primera PriceList activa/default, precio máximo de items con SUM(qty_on_hand - qty_reserved) > 0, redondeado hacia arriba. No necesariamente coincide con el máximo de productos visibles: esa consulta no aplica todos los criterios de publicación/actividad del listado.

### Products, filters and pagination

Entrada de `getDataProductsV2`:

| Campo | Contrato actual |
|---|---|
| `category_id`, `subcategory_id` | Un ID escalar por dimensión; string vacío omite filtro |
| `product_search`, `search` | Trim; primero no vacío tiene prioridad. Busca nombre, code, codigo y SKU/barcode/display_name de stock items activos |
| `size_ids`, `color_ids` | Array o cadena separada por comas; filtra vacíos y convierte a int |
| `min_price`, `max_price` | String vacío omite filtro; comparación float contra precio mínimo del material |
| `pageNumber` | Parámetro de ruta, mínimo 1; nueve materiales por página; no hay page size configurable |

No hay contrato para marca, multicategoría, condición, ofertas, novedades, destacados, orden elegido por usuario o incluir agotados. No hay FormRequest ni validación explícita de pertenencia de los IDs en este método.

La consulta carga materiales `enable_status = 1`, stockItems `is_active = 1`, variantes, inventoryLevels y priceListItems de primera lista activa/default. Sin lista: no carga precios. Filtra material con stock disponible agregado > 0. Obtiene todos los materiales, calcula y filtra en memoria, luego corta página: no usa `paginate()` SQL. Orden alfabético; con búsqueda prioriza coincidencias exactas y prefijo de nombre.

Cada producto devuelve: `source='material'`, `id`, `material_id`, `full_name`, `category` (descripción), `price`, `price_text`, `has_variants`, `stock`, `image`, `image_url`, `unit`, `tax`, `rating`, `type`, `sku`, `barcode`, `stock_items_count`, `detail_url`. `rating=4` está hardcodeado: no constituye reseñas reales ni debe portarse como valoración. `tax` tiene fallback 18. URL generada con `shop.product.show`.

Precios: mínimo positivo de todos los stock items activos de la lista, aunque el item más barato no tenga disponibilidad. Sin precio válido resulta 0. Moneda textual fija `S/.`, aunque PriceList posee `currency`. `Desde` depende de variantes y número de registros de precio, no del número de precios distintos. No hay precio anterior, descuento ni vigencia promocional.

Stock del listado: `max(0, suma on_hand - suma reserved)` del material. Filtros talla/color son dos `whereHas` independientes: pueden coincidir en variantes diferentes; no garantizan una combinación talla+color disponible. Tampoco limitan el precio/stock agregado al subconjunto seleccionado.

`pagination` devuelve `currentPage`, `totalPages`, `startRecord`, `endRecord`, `totalRecords`, `totalFilteredRecords`. Ambos totales son el total filtrado, no un total global sin filtros. Sin resultados: totalPages 1, start/end 0. Páginas mayores al máximo no se acotan y pueden producir rango incoherente. El frontend actual oculta paginación cuando hay una página.

### Facets, detail, images and contact

- Categorías: `{id,name,description,subcategories:[{id,name,description}]}`; nombres mayúsculas. Solo categorías/subcategorías relacionadas con stock positivo. Tallas: `{id,name,description}`, nombre corto preferido. Colores: `{id,name,code}`. Los facets no reciben filtros activos ni devuelven conteos y no replican todas las condiciones de actividad/publicación del listado.
- Detalle carga category, subcategory, brand; presentations activas ordenadas por quantity; variants activas con talla/color; stockItems activos con variante, inventario y precios. No verifica explícitamente `enable_status` del material enlazado.
- Entrega `material`, `stockAvailable`, `priceText`, `colors`, `sizes`, `images` y los mismos settings de branding/contacto/visibilidad/footer/sociales. No entrega `maxPrice`, `search` ni relacionados.
- Stock detalle: suma `max(0, on_hand - reserved)` **por item**. Difiere del clamp global del listado cuando hay items con saldo negativo.
- Galería: imágenes de variantes activas si existen; entonces no agrega la principal del material. Si no, imagen principal o `images/material/no-image.png`. Cada entrada es `{image,thumb,label}`. Imagen y thumb pueden ser la misma URL.
- `StockItem::display_image_url` prioriza variante, luego material, luego `images/material/no_image.png`. Material no tiene accessor `getImageUrlAttribute` en el archivo auditado: el listado intenta `material->image_url` y fallback del primer item; no asumir que toda imagen viene del padre.
- Verificación de archivos: existe `public/images/material/no_image.png`; no existen `public/images/material/no-image.png` ni `public/shop/img/no-image.png` (este último es DEFAULT_IMAGE del Blade). Riesgo real de fallback 404.
- Talla/color del detalle son uniones de variantes activas; radios legacy carecen de ID/value de variante y solo cambian presentación visual. Presentaciones están cargadas pero su tabla está comentada. No hay resolución cliente de combinación → SKU/precio/stock/imagen.
- El catálogo actual crea enlace WhatsApp con nombre del producto, sin URL ni variante. El detalle solo expone la base en APP_SHOP, sin CTA funcional equivalente a la referencia.
- Breadcrumb actual: catálogo Home→Shop; detalle Home→categoría con `href="#"`→nombre. Referencia usa navegación y “Volver a productos”; no reconstruir breadcrumb legacy por costumbre.
- `searchProduct`: búsqueda vacía 422, sin match 404, primer material habilitado coincidente 200 con URL; no exige stock. No se usa para el flujo global actual `?search=`.

## Reference Frontend Structure

La referencia contiene **56 archivos**: tres HTML, cuatro CSS, un JS, 15 Sass, tres binarios de fuente, dos TXT y 28 imágenes (27 JPG y un PNG). No hay package.json, pipeline propio, API, JSON de productos, módulos AJAX ni vendor JS. Los mocks viven en HTML.

| Página | Estructura aprobada |
|---|---|
| index | Menú móvil, header/logo/nombre, búsqueda inline; intro negocio; banner promocional con formas CSS; cuatro destacados; dos promociones; tira de categorías comerciales; footer/sociales; tester de tema; bottom nav |
| shop | Shell compartido; chips Todos/Novedades/Destacados/Promociones/Disponibles; resumen “9 productos”; botón filtros/contador; nueve cards (incluye oferta, nuevo, agotado); paginación ficticia; drawer; footer; tester; bottom nav |
| product-details | Shell; volver; galería de cuatro imágenes; oferta/precios/disponibilidad/intro; dos grupos de radios; CTA WhatsApp; características; descripción; cuatro relacionados; footer; tester; CTA fijo móvil |

Orden CSS en los tres HTML: bootstrap.min.css → font-awesome.min.css → elegant-icons.css → style.css. Bootstrap **4.4.1 CSS**, Font Awesome **4.7.0 CSS reducido** (solo seis iconos: heart/facebook/bars/youtube-play/instagram/whatsapp), ElegantIcons y Montserrat variable local. No jQuery, Bootstrap JS, Owl, SlickNav, Magnific ni slider JS en referencia. No confundir dependencia de CSS Bootstrap con uso de plugins Bootstrap.

Sass, orden exacto de entrada `style.scss`: variable → mixins → catalog-theme → base → header → catalog-home → catalog-shop → catalog-filters → catalog-product-detail → responsive → catalog-mobile-menu → catalog-search → catalog-coherence → catalog-theme-tester. Conservar precedencia, especialmente coherence después de responsive. `style.css` tiene 2934 líneas; no se recompiló ni se certificó equivalencia byte a byte con Sass durante esta auditoría.

Tema: Montserrat 100–900, fondo #f7f8fa, superficie blanca, borde #e5e7eb, radios 12/10px, acento #2f6fed y propiedades `--catalog-primary*` con fallbacks/color-mix. Conservar incluso gradiente del banner; no aplicar restricciones visuales del backoffice a esta referencia aprobada.

JS `catalog-main.js`:

- Control compartido de overlays: un overlay activo, bloqueo de scroll con compensación de scrollbar, Escape, Tab/Shift+Tab, foco inicial y retorno, aria-hidden/expanded.
- Menú móvil con `data-catalog-menu-open/close`; cierre al navegar.
- Buscador: enfoque/scroll, botón limpiar, reduced-motion. Submit siempre `preventDefault()`: no búsqueda real.
- Drawer: abre/cierra; “Ver resultados” solamente cierra. Limpiar, badge de conteo y filtros no tienen lógica de consulta.
- Galería: click en thumbnails cambia src/alt, aria-pressed y contador. No swipe/zoom plugin ni carga dinámica de variantes.
- Tester: presets/custom/reset modifican CSS variables, sin persistencia. El propio Sass dice que se retire para producción. Propuesta `DO NOT PORT`; registrar ese retiro técnico explícito, sin cambiar el tema base ni efectuarlo en esta fase.
- No handlers para WhatsApp, paginación, opciones de producto o datos sociales; href de redes es `#`. data-product-url está vacío.

No hay estados aprobados de catálogo vacío/error/carga, facetas vacías, imagen fallida o producto inexistente. “Agotado” sí existe como card, pero no equivale a estado vacío. Los estados de error/vacío reales existen como mensajes en catalog.js legacy: preservar significado y acordar encaje visual mínimo, sin inventar otro sistema.

## Asset Migration Map

Propuesta de namespace aislado: **public/store-web/**. Mantener el CSS vendor en `css/` facilita conservar exactamente sus URLs relativas `../fonts/`; no mezclar con `public/admin` ni sobrescribir `public/shop` mientras haya consumidores legacy.

| Origen | Destino definitivo propuesto | Estrategia / observación |
|---|---|---|
| `css/bootstrap.min.css` | `public/store-web/css/bootstrap.min.css` | COPY AS-IS; vendor 4.4.1 congelado |
| `css/font-awesome.min.css` | `public/store-web/css/font-awesome.min.css` | COPY AS-IS; no sustituir por versión distinta |
| `css/elegant-icons.css` | `public/store-web/css/elegant-icons.css` | COPY AS-IS |
| `css/style.css` | `public/store-web/css/style.css` | COPY AS-IS para baseline; retiro acotado del tester en paso separado |
| `js/catalog-main.js` | `public/store-web/js/catalog-main.js` | ADAPT JS CONTRACT; preservar overlays/galería, coordinar submit, retirar tester técnico |
| `sass/*.scss` | `resources/sass/store-web/*.scss` | COPY AS-IS del fuente visual; import de tester solo en archivo de evidencia no productivo |
| `fonts/Montserrat-Variable.ttf` | `public/store-web/fonts/Montserrat-Variable.ttf` | COPY AS-IS |
| `fonts/Montserrat-OFL.txt` | `public/store-web/fonts/Montserrat-OFL.txt` | COPY AS-IS, conservar licencia |
| `fonts/fontawesome-webfont.woff2` | `public/store-web/fonts/fontawesome-webfont.woff2` | COPY AS-IS |
| `fonts/ElegantIcons.woff` | `public/store-web/fonts/ElegantIcons.woff` | COPY AS-IS |
| `img/logo.png` | `public/store-web/img/logo.png` | COPY AS-IS como referencia/fallback pendiente; marca real se liga al setting |
| `img/product/product-1..6.jpg` | `public/store-web/img/product/` | COPY AS-IS para home visual pendiente, jamás datos comerciales reales |
| `img/shop/shop-1..9.jpg` | `public/store-web/img/shop/` | COPY AS-IS solo si quedan fixtures visuales; cards reales usan image_url |
| `img/product/details/{product,thumb}-1..4.jpg` | `public/store-web/img/product/details/` | COPY AS-IS para fixture de galería; detalle real usa images |
| `img/product/related/rp-1..4.jpg` | `public/store-web/img/product/related/` | COPY AS-IS para bloque visual pendiente de relacionados |
| `readme.txt` | `docs/store-web/vendor/REFERENCE-NOTICE.txt` | COPY AS-IS de aviso/procedencia; revisar evidencia de licencia antes de publicación |
| Datos/fotos de negocio existentes | `public/images/material/`, `variants/`, `public/images/logo/` | BIND REAL DATA; ya son permanentes, no mover uploads con esta migración |
| Fallback sin imagen | `public/store-web/img/no_image.png` desde fallback real existente | COPY AS-IS; cerrar URL única con backend |
| Plugins JS legacy de `public/shop` | Permanecen allí para consumidores legacy | DO NOT PORT al nuevo shell salvo comportamiento real que se decida conservar explícitamente |

Los destinos de fixtures son propuestos para preservar la referencia durante validación, no autorización para publicarlos como inventario real. Home debe quedar visualmente preparada sin inventar consultas; definir su exposición pública antes del corte. Tras retirar fixtures de producción se pueden conservar fuera de public en `docs/store-web/reference/`. Nunca dejar enlaces a la carpeta temporal.

Pipeline actual `webpack.mix.js` solo compila app.js/app.scss. No agregar Store Web a ese bundle compartido. Primero conservar CSS compilado aprobado; después agregar entrada separada o script dedicado al Sass permanente y verificar equivalencia visual. Resolver URLs de fonts respecto del CSS de salida y copiar licencias. No actualizar dependencias para esta auditoría.

Plan de migración: inventario/hash → copia permanente → rutas asset() y route() → adaptación mínima JS/Blade → búsqueda de referencias temporales en ejecutables/build → carga de las tres vistas desde destinos permanentes → pruebas con la carpeta temporal fuera del alcance del servidor/build → eliminación final autorizada en fase posterior. Comprobar HTML, CSS url(), src/data-gallery-image, JS, manifiestos y sourcemaps. La documentación histórica puede nombrar la referencia; ningún archivo ejecutable debe depender de ella.

## Index Mapping

**FRONTEND READY / BACKEND CONTRACT PENDING**. “Ready” aquí describe la referencia visual aprobada, no una landing integrada o funcional. Reutilizar `/store-web/inicio` y `store-web.home`; no inventar nueva ruta ni lógica de destacados.

| Elemento | project-catalog | aliexpress actual | estrategia |
|---|---|---|---|
| Shell home | `catalog-page--home`, header/footer/nav | `shop.home` + appShop | CONVERT TO BLADE |
| Layout, banner decorativo, cards, scroll | Sass aprobado | No equivalente aprobado en home legacy | COPY AS-IS |
| Logo | img/logo.png | home recibe logotipoEmpresa | BIND REAL DATA |
| Nombre/intro negocio | “Nombre del negocio”, texto fijo | home no los entrega | BACKEND CONTRACT NEEDED |
| Banner | Selección especial, copy, CTA | Sin contrato editorial/vigencia | BACKEND CONTRACT NEEDED |
| Destacados (4) | Productos, precios, badge Nuevo mock | Sin selección/orden/flag de destacado | BACKEND CONTRACT NEEDED |
| Promociones (2) | Precio previo, actual, descuento | Sin contrato de campaña/descuento/vigencia | BACKEND CONTRACT NEEDED |
| Tira comercial | Novedades/Destacados/Promociones/Disponibles | Categorías reales no son estas colecciones | BACKEND CONTRACT NEEDED |
| Ver todos / Inicio / Productos | HTML relativos | store-web.home / store-web.catalog | CONVERT TO BLADE |
| Búsqueda | Submit cancelado | catálogo `?search=` | ADAPT JS CONTRACT |
| Footer/social/contacto | Copys/redes mock | settings existen en catalog/detail, no en home | BACKEND CONTRACT NEEDED |
| Tema por empresa | CSS vars + tester | No setting de tema entregado | BACKEND CONTRACT NEEDED |
| Tester | Control temporal explícito | Sin contrato productivo | DO NOT PORT |

Contrato que falta por bloque: intro (nombre público, descripción, fallback logo); banner (contenido, enlace permitido, vigencia/visibilidad); destacados (criterio o selección editorial, orden, límite, contrato de cards); promociones (elegibilidad, precios/moneda/descuento, vigencia); colecciones (identificador público y filtro/URL destino); footer (proyección de settings en home); badges (significado y caducidad); tema (fuente y validación del color si se desea personalización). No se proponen queries ni se usa created_at como sustituto de “Nuevo”.

## Shop Mapping

Objetivo: `shop.html` → `/store-web/catalogo`.

| Elemento | project-catalog | aliexpress actual | estrategia |
|---|---|---|---|
| Shell/grid/cards | catalog-page--shop; grid CSS 2/3/4/5 | Blade/render legacy | CONVERT TO BLADE |
| CSS responsive y estructura de cards | Aspect ratio, status, tipografía | Clases product__item | COPY AS-IS |
| Nombre/imagen/link | Nueve mocks | full_name, image_url, detail_url | BIND REAL DATA |
| Precio normal | S/ mock | price_text y showPricesCatalogEmpresa | BIND REAL DATA |
| Oferta/precio tachado/Nuevo | Flags ficticios | No fields equivalentes | BACKEND CONTRACT NEEDED |
| Agotado | Card unavailable | API excluye stock <= 0 | BACKEND CONTRACT NEEDED |
| Total | “9 productos” | pagination.totalFilteredRecords | BIND REAL DATA |
| Paginación | Tres páginas y flecha fake | currentPage/totalPages y endpoint :page | ADAPT JS CONTRACT |
| Búsqueda inline | Sin name/action y submit cancelado | search/product_search | ADAPT JS CONTRACT |
| Drawer visual | Overlay/details/precio/footer | Sidebar legacy | COPY AS-IS |
| Categorías multi checkbox | category-1..3 | category_id único | BACKEND CONTRACT NEEDED |
| Subcategorías | No bloque expreso | category + subcategory_id | ADAPT JS CONTRACT |
| Talla/color | No bloques expresos | size_ids/color_ids y endpoints | ADAPT JS CONTRACT |
| Precio desde/hasta | Inputs sin name; 0/500 placeholders | min_price/max_price y maxPrice | ADAPT JS CONTRACT |
| Marca | brand-a..c | Sin filtro/API facet de marcas | BACKEND CONTRACT NEEDED |
| Disponibilidad/promoción/condición | Checkboxes | Sin filtros equivalentes | BACKEND CONTRACT NEEDED |
| Chips comerciales | Novedades/destacados/etc. | No colecciones equivalentes | BACKEND CONTRACT NEEDED |
| Aplicar/limpiar/contador | Aplicar solo cierra, resto sin handler | Estado JS actual cambia inmediatamente | ADAPT JS CONTRACT |
| Vacío/error/cargando | No patrón explícito | Mensajes de error/vacío AJAX | ADAPT JS CONTRACT |
| Redes/logo/footer | Placeholder | Settings company | BIND REAL DATA |
| Rating hardcodeado del API | No rating visual | rating=4 | DO NOT PORT |

No reducir silenciosamente multicategoría a la primera selección ni hacer filtrado local sobre una página de nueve resultados. Backend debe definir multicategoría o aprobar adaptación de selección única. Talla/color/subcategorías pueden usar el mismo lenguaje de grupos del drawer, pero el cambio de contenido/estructura debe quedar justificado y aprobado. No esconder o sustituir filtros comerciales sin resolver esa diferencia con el usuario.

Conservar nueve por página aunque desktop tenga cinco columnas: no modificar backend para llenar filas visuales. Mantener un estado único para filtros, reiniciar página a 1 al aplicar/limpiar, preservar búsqueda y evitar respuestas AJAX antiguas sobrescribiendo las nuevas. Estos son requisitos de integración, no cambios ejecutados.

El zoom/WhatsApp sobre cards legacy no existe en la card aprobada. No inyectar botones en la card sin aprobación: acordar conservación funcional en detalle/galería y documentar la diferencia. Contrato público de navegación debe transportar categoría/búsqueda cuando proceda; catalog actual solo inicializa search desde query, no category_id.

## Product Detail Mapping

Objetivo: `product-details.html` → `/store-web/product/{material}`.

| Elemento | project-catalog | aliexpress actual | estrategia |
|---|---|---|---|
| Shell y hero | catalog-page--detail | shop.detailCatalog | CONVERT TO BLADE |
| Layout/gallery/sticky CTA | Estructura Sass aprobada | Owl/col-lg legacy | COPY AS-IS |
| Volver / enlace producto | HTML relativo | store-web.catalog / shop.product.show | CONVERT TO BLADE |
| Nombre | Producto de ejemplo | material.full_name | BIND REAL DATA |
| Galería/thumbs/alt | Cuatro fotos mock | images[{image,thumb,label}] | BIND REAL DATA |
| Contador/thumbnail activa | index/n total | Colección variable de imágenes | ADAPT JS CONTRACT |
| Precio | S/129.90 | priceText + flag booleano | BIND REAL DATA |
| Precio anterior/Oferta | Mock | Sin contrato | BACKEND CONTRACT NEEDED |
| Disponible | Siempre disponible | stockAvailable | BIND REAL DATA |
| Marca/código | Ejemplo/PROD-001 | material.brand.name, code/codigo | BIND REAL DATA |
| Presentación/unidad | Unidad mock | unitMeasure y presentations; unidad no eager-loaded aquí | BACKEND CONTRACT NEEDED |
| Intro/descripción | Textos editoriales mock | material.description existe; no está proyectada como descripción pública | BACKEND CONTRACT NEEDED |
| Radios atributos | presentation/attribute genéricos | colors/sizes/variants/stockItems | ADAPT JS CONTRACT |
| Resolver selección | Radios sin lógica | Sin contrato UI SKU→precio/stock/imagen/combinación | BACKEND CONTRACT NEEDED |
| WhatsApp inline/móvil | Botones sin handler | Número saneado + datos de material | ADAPT JS CONTRACT |
| Consulta con variante/URL | data-product-url vacío | URL por route; sin payload de selección | BACKEND CONTRACT NEEDED |
| Características adicionales | dl estático | Sin esquema público genérico de atributos | BACKEND CONTRACT NEEDED |
| Relacionados (4) | Mocks | showProduct no carga relacionados | BACKEND CONTRACT NEEDED |
| Footer/social/logo | Mocks | Settings ya enviados | BIND REAL DATA |
| Breadcrumb legacy | Solo “Volver” en referencia | Home/categoría/producto | DO NOT PORT |

No fabricar combinaciones desde producto cartesiano de colors × sizes. Identificar Variant/StockItem real, política para agotados y presentaciones; backend debe definir qué precio e inventario corresponde a una selección. La consulta WhatsApp puede reutilizar nombre y URL resuelta, pero no prometer precio/disponibilidad de una combinación que el servidor no haya validado. Definir estado sin número de contacto, sin precio, sin imagen y material no publicable. No insertar texto interno como descripción pública sin acordar exposición y saneamiento.

## Mobile Contract

Contrato extraído de Sass y CSS compilado; pendiente de comparación renderizada sobre datos reales.

| Rango / elemento | Comportamiento que preservar |
|---|---|
| Base móvil | Grid shop dos columnas; margen lateral móvil 12px; cards con imagen contain y nombre a dos líneas |
| >=576px | Ajustes de gaps/padding; home muestra status e intro/banner más amplio |
| >=768px | Shop tres columnas; home destacados cuatro y ofertas dos; drawer 400px; detalle CTA inline y relacionados grid cuatro |
| >=992px | Shop cuatro columnas; detalle hero dos columnas 1.08fr/0.92fr y summary sticky; menú desktop, overlay móvil oculto |
| >=1200px | Shop cinco columnas; container máximo 1440px con padding 28px; ajustes de proporción de imagen |
| <768px home/shop | Bottom nav fija de cuatro destinos con safe-area; footer reserva espacio |
| <768px detalle | CTA WhatsApp fijo inferior; no bottom nav en este HTML |
| Drawer | Pantalla completa móvil, 100vh + 100dvh, cuerpo con scroll independiente y footer seguro |
| Menú | Panel izquierdo 300px/82vw, móvil min(82vw,290px); backdrop; un overlay a la vez |
| Carruseles | Scroll horizontal nativo y scroll-snap en destacados/ofertas/relacionados, sin plugin |
| Galería | Thumbnails horizontales 64px móvil/72px tablet, stage contain y contador |
| Accesibilidad | Labels, aria-current/pressed/expanded/hidden, dialog, Escape, restauración de foco, focus-visible, reduced-motion |

Riesgos pendientes: abrir menú móvil y redimensionar a >=992 puede ocultar overlay sin ejecutar unlockBody; el selector focusableElements no incluye `summary`, aunque los details son tabulables; foco puede salir mediante interacciones no contempladas por el trap. Son observaciones de código, no fallos reproducidos en navegador. No corregir estilos congelados por hipótesis: verificar durante integración.

Matriz posterior: 320/375/390/576/767/768/991/992/1200/1440px; tres páginas; teclado y orientación; nombre largo, cero/uno/muchas imágenes, precio oculto, filtros vacíos, error/red lenta, cero resultados, página fuera de rango. Revisar overflow real aunque `.catalog-page` use overflow-x:hidden, safe-area, solapamiento CTA/footer y fuente cargada.

## Multitenancy

**No se puede certificar aislamiento público con el código actual. BACKEND SYNC OPEN.** Hallazgos estáticos, sin afirmar explotación ni datos filtrados observados.

| Dominio | Evidencia | Evaluación |
|---|---|---|
| Resolución pública | Rutas solo web; sin dominio ni resolver público; TenantContext lee sesión | Sin sesión no se resuelve tenant/company/branch de tienda |
| Productos | Material usa BelongsToTenant; TenantScope::apply retorna sin WHERE si tenantIdOrNull vacío | Queries de datos/búsqueda quedan sin aislamiento tenant cuando falta contexto |
| Detalle por ID | Binding Material sucede en SubstituteBindings; sin public context previo | Scope depende de sesión y no verifica enable_status; URL numérica no acredita pertenencia/publicación |
| Company producto | StockItem posee enabledForCompany; controller no lo usa | No comprueba habilitación por empresa |
| Stock | InventoryLevel tiene tenant scope y company_id; sumas sin company/branch/warehouse | Con tenant presente todavía puede sumar varias empresas/almacenes |
| Precio | PriceList tenant-scoped y company_id; first activa/default sin company | Puede elegir otra empresa del mismo tenant; sin tenant aún más amplio |
| PriceListItem | No BelongsToTenant; restricción por price_list_id/relación | Depende de selección correcta de lista e integridad relacional |
| Variantes/talla/color/categorías | Modelos tenant-scoped; mismo scope opcional | Sin tenant no hay barrera; facets además no verifican contexto company ni toda publicación |
| Presentaciones | MaterialPresentation sin tenant trait; via material_id | Aislamiento heredado del padre; requiere padre correctamente resuelto |
| Branding/contacto | SettingService obliga tenant/company para estos settings | Puede fallar sin sesión; no es solución para aislar los endpoints JSON que no usan settings |
| Imágenes | URLs públicas `images/material`, `variants`, `images/logo` sin segmento tenant | No prueban propiedad; publicación pública puede ser válida, pero debe definirse acceso, unicidad de nombres y asociación segura |
| URLs | route('shop.product.show', material.id), dominio no condicionado por rutas | No transporta contexto de tienda; enlace compartido con visitante nuevo carece de resolución pública demostrada |

`InitializeTenantContext` existente trabaja con Auth::user y deja pasar anónimos; añadirlo sin más no resuelve Store Web. La solución pública debe definirse en backend, funcionar antes del binding y en cada endpoint GET, y rechazar contexto ausente/inválido. No inferir tenant/company desde un producto arbitrario ni usar un ID fijo. No aceptar company_id del navegador sin validación.

Validación posterior necesaria: visitante sin sesión; tienda A/B en contextos distintos; ID ajeno en detalle y filtros; cambio de empresa dentro del tenant; stock/precio/branding divergentes; URL compartida en sesión limpia; tienda inactiva; producto deshabilitado; imágenes pertenecientes a otro catálogo. Resolver también qué almacenes publican stock. No usar sesión del backoffice como evidencia de catálogo público correcto.

## Backend Contracts Pending

Los siguientes puntos usan el formato de sincronización del AGENTS. Todos están **BACKEND SYNC OPEN**; las propuestas no implementan política backend.

### Backend Sync Notes

**[OPEN] SW-01 — Contexto público y aislamiento**

- Point: definir identidad de tienda/tenant/company y, si procede, branch/almacenes públicos.
- File / endpoint: routes/web.php Store Web, Kernel, TenantContext, TenantScope, todos los endpoints de la tabla.
- Impact: mezcla potencial de datos o fallo de settings en visitantes sin sesión; binding sin contexto seguro.
- What backend needs to define or correct: resolver público validado antes de bindings; política de contexto inexistente/inactivo; URL compartible y reglas company de productos/stock/precios/facets/imágenes.
- Frontend can continue: **Yes**, extracción visual/assets/Blade preparado; **No** para dar por segura la publicación o integrar datos sin resolución aprobada.

**[OPEN] SW-02 — Publicación y disponibilidad**

- Point: listado, detalle, búsqueda y facets no usan exactamente las mismas reglas.
- File / endpoint: getDataProductsV2, showProduct, searchProduct, stockItemsDisponiblesQuery.
- Impact: acceso directo a deshabilitados; facets sin resultados; fórmulas de stock distintas; card agotada sin fuente.
- What backend needs to define or correct: material/item/variante publicable, stock agregado canónico, agotados, reservas, enabledForCompany y respuesta de producto fuera de catálogo.
- Frontend can continue: **Yes**, conservar estados visuales; no inventar agotados en API.

**[OPEN] SW-03 — Precio, moneda y flags**

- Point: primera lista default sin company; mínimo puede ser de item agotado; cero por ausencia; moneda fija; visibilidad inconsistente.
- File / endpoint: StoreWebController, PriceList/PriceListItem, catalog/detail Blades, SettingService.
- Impact: precio incorrecto o engañoso; precio oculto todavía presente en JSON/DOM; semántica de presentaciones no aplicada.
- What backend needs to define or correct: lista y moneda públicas, falta de precio, mínimo publicable, booleanos show_prices/show_presentations y si ocultar es solo UI o también confidencialidad de respuesta.
- Frontend can continue: **Yes**, usar flag booleano serializado con @json y estructura aprobada después de confirmar semántica; no crear descuentos.

**[OPEN] SW-04 — Filtros visuales y contrato real**

- Point: multicategoría, marca, condición y colecciones comerciales no soportadas; talla/color/subcategoría reales no están en referencia.
- File / endpoint: shop.html drawer/chips; products/categories/sizes/colors data.
- Impact: filtros aparentarían funcionar sin efecto o perderían semántica.
- What backend needs to define or correct: ampliar contratos o acordar adaptación mínima y aprobación visual; combinación talla/color sobre misma variante; deep links de filtros; paginación fuera de rango.
- Frontend can continue: **Yes**, shell y handlers de lo soportado; los controles sin contrato no pueden declararse funcionales.

**[OPEN] SW-05 — Variantes/presentaciones y WhatsApp**

- Point: datos relacionales existen, no hay contrato público de selección completa.
- File / endpoint: showProduct, Variant, StockItem, MaterialPresentation, product-details.html.
- Impact: selección imposible, precio/imagen/stock incorrectos, consulta sin variante.
- What backend needs to define or correct: IDs públicos, combinaciones válidas, display/stock/price por selección, presentaciones y alcance del flag; payload de consulta y fallback sin contacto.
- Frontend can continue: **Yes**, galería y datos agregados; **No** para prometer selección comercial resuelta.

**[OPEN] SW-06 — Landing/editorial/promociones/relacionados**

- Point: home solo recibe logo; listado/detalle no entregan campañas ni relacionados.
- File / endpoint: home/catalog/showProduct y las tres referencias HTML.
- Impact: bloques aprobados quedarían con mocks si se publican sin contrato.
- What backend needs to define or correct: contratos por bloque descritos en Index Mapping, relacionados, descripción pública y características, badges y política de exposición de landing pendiente.
- Frontend can continue: **Yes**, FRONTEND READY / BACKEND CONTRACT PENDING, conservar estructura en preview; no fabricar datos/queries.

**[OPEN] SW-07 — Branding y assets empresariales**

- Point: nombre/color sin proyección, logo descrito como ruta/URL pero concatenado como filename; rutas fallback discordantes.
- File / endpoint: config/settings_catalog.php, SettingService, StockItem, showProduct, APP_SHOP.
- Impact: marca equivocada/incompleta o imagen 404; contacto/redes vacíos.
- What backend needs to define or correct: URLs públicas normalizadas de logo/imágenes, fallback canónico, nombre público y tema si aplica, validación de enlaces/contacto.
- Frontend can continue: **Yes**, assets neutros y bindings existentes; no tomar marca de otro tenant ni fijar teléfono de ejemplo.

No hay puntos [RESOLVED] en esta fase; no se ha recibido contrato corregido ni se realizaron cambios backend.

## Files to Create

Propuesta ajustada al proyecto: preservar los nombres de vistas `shop.*` que devuelve el controller, para reducir cambios funcionales. No es obligatorio renombrarlas a store-web.

```text
resources/views/layouts/storeWeb.blade.php
resources/views/shop/partials/store-web/
  header.blade.php
  mobile-menu.blade.php
  footer.blade.php
  bottom-nav.blade.php
  filter-drawer.blade.php
  product-card.blade.php
  gallery.blade.php
resources/sass/store-web/                 # fuentes aprobadas permanentes
public/store-web/
  css/                                  # style + tres CSS vendor congelados
  js/
    catalog-main.js                      # interacciones de referencia
    catalog-data.js                      # adaptador de contratos del JS real
    product-detail.js                    # solo lógica acordada de consulta/selección
  fonts/                                # tres fuentes y licencia Montserrat
  img/                                  # neutros y fixtures visuales separados de datos reales
docs/store-web/vendor/REFERENCE-NOTICE.txt
```

Los parciales son extracción literal de markup, no nuevos componentes de diseño. La variante de footer/clase de body sigue cada página aprobada. Datos de JS deben proyectarse de forma explícita; no serializar `material->toArray()` indiscriminadamente (modelo tiene appends operacionales). El adaptador conserva semántica GET/response del catalog.js real y cambia su capa de render; no importa toda la lógica mock como sustituto del contrato real.

Si se requiere jQuery temporal para conservar transporte actual, usar copia identificada de `public/shop/js/jquery-3.3.1.min.js` a `public/store-web/vendor/jquery/` y documentar dependencia; no es requisito de la referencia. Preferencia: adaptador aislado que no active los plugins visuales legacy. No introducir un framework frontend nuevo.

## Files to Replace

Solo en fase posterior:

- `resources/views/shop/home.blade.php`: markup aprobado index; landing preparada, contratos pendientes explícitos fuera del flujo público.
- `resources/views/shop/catalog.blade.php`: markup aprobado shop + contrato real.
- `resources/views/shop/detailCatalog.blade.php`: markup aprobado product-details + datos reales.
- Cada una extiende nuevo layout Store Web; mantener return view y nombres de rutas existentes.
- `webpack.mix.js` solo si se acuerda compilación dedicada; ningún reemplazo global de app.scss/app.js.
- `StoreWebController.php`, rutas o middleware requerirían alineación backend separada para puntos OPEN. No cambiar esas políticas como parte silenciosa del port visual.
- Los nuevos adaptadores reemplazan la carga del JS legacy **en estas vistas**, sin sobrescribir catalogNoPrice.js ni sus contratos laterales.

## Files to Delete

**Ninguno en REAL NEXT 9.**

Al terminar integración: eliminar `project-catalog/` únicamente tras verificar assets/fuentes/Sass/avisos en ubicaciones permanentes, fixtures y referencias runtime/build sin dependencia temporal. El tester queda fuera del bundle y markup productivo por su propósito temporal explícito.

No eliminar `public/shop/`, `layouts/appShop.blade.php`, catalogNoPrice ni search.js solo por terminar estas tres pantallas: `/store-web/tienda` sigue utilizándolos. `detailCatalogNotPrice` requiere confirmar consumidores antes de retirarlo. Mantener uploads `public/images/*`. No borrar el fallback real ni documentación de referencia/licencias.

## Risks

1. **Crítico: aislamiento público no garantizado.** Contexto opcional en scope, sin resolver público y sin filtros company; SW-01 bloquea publicación segura.
2. **Alto: deriva de contrato visual.** Convertir checkboxes multicategoría en selección única, ocultar bloques o volver a sidebar legacy sería desviación; resolver explícitamente.
3. **Alto: datos demo publicados como reales.** Landing, badges, precios tachados, relacionados y disponibilidad hardcodeada no son backend.
4. **Alto: precios/stock/variantes.** Mínimo no necesariamente disponible, fórmulas distintas, moneda fija, pares talla/color no correlacionados y presentaciones sin integración.
5. **Medio: flags legacy.** Detail compara show_prices con string `"s"`, mientras SettingService declara boolean; catalog usa truthiness. Hay cierres `@endcan` tras `@if` (compilan a endif en Blade, pero semánticamente confusos); no asumir que existe permiso detrás. Flags JS son strings, no booleanos JSON.
6. **Medio: imágenes.** Dos fallbacks inexistentes y rutas públicas sin política tenant documentada. Verificar datos históricos y encoding de URLs, no solo archivos demo.
7. **Medio: render seguro.** catalog.js interpola full_name, URLs y nombres de facets directamente en HTML; el nuevo binding necesita escape contextual/DOM seguro. Redes y color deben validarse; no reutilizar concatenación sin revisar.
8. **Medio: performance/UX.** Paginación en memoria, cuatro AJAX iniciales, sin protección frente a respuestas fuera de orden, manejo limitado de errores; medir antes de cambiar arquitectura.
9. **Medio: plugins y consumidores legacy.** appShop compartido; reemplazo global rompería tienda lateral. Font Awesome de referencia es reducido y no incluye Twitter/Pinterest legacy: acordar representación si deben aparecer, sin importar toda otra iconografía silenciosamente.
10. **Pendiente documental: licencias.** readme local de Colorlib impone aviso/condición de licencia mientras footer aprobado muestra Venti360. Registrar evidencia de licencia/procedencia antes de publicar; esta auditoría no determina derechos ni cambia el footer.
11. **Pendiente de ejecución:** responsive, foco, redimensionado con menú abierto, errores HTTP y cross-tenant no probados. `route:list` confirma rutas, no funcionamiento de datos ni vistas.

## Recommended Integration Order

1. Cerrar SW-01 con backend: resolución pública antes de binding, company de precios/stock/productos y URLs compartibles. Diseñar pruebas de aislamiento sin escribir datos de negocio.
2. Acordar SW-02/03/04/05/07 y registrar las mínimas diferencias visuales aprobadas; decidir exposición de landing y bloques pendientes SW-06. No usar la espera de home para rediseñar shop.
3. Copiar assets y Sass a destinos permanentes; conservar hashes y avisos. Crear shell mediante extracción literal. No mezclar AdminLTE/Venti Next.
4. Preparar index completo visualmente en destino definitivo, con FRONTEND READY / BACKEND CONTRACT PENDING. No crear queries ni presentar mocks como contenido comercial.
5. Integrar shop con contrato products/facets/pagination/search y reglas de visibilidad acordadas; render seguro y estados de red.
6. Integrar detalle con galería real, precio/stock agregados, atributos y WhatsApp; solo habilitar selección resuelta cuando SW-05 esté definido.
7. Incorporar contratos editoriales/relacionados/promociones cuando backend los entregue, conservando los bloques aprobados.
8. Validar con PHP 7.3.33, rutas/nombres y respuestas GET; contrastar referencia/real en matriz mobile/desktop, teclado y todos los estados. Validar aislamiento con tenant/company A/B y sesión limpia, incluyendo URLs e imágenes.
9. Verificar cero dependencia runtime/build de la carpeta temporal, assets 200, fonts correctas, ausencia de llamadas mock y consumers legacy intactos. Probar desde ubicaciones permanentes antes de eliminar la referencia.
10. Retirar carpeta temporal y tester productivo en fase final, con evidencia de independencia y paridad. No declarar STORE WEB INTEGRATION READY antes de resolver los bloqueos funcionales y revisar visualmente.

## Phase delivery

La auditoría identifica rutas, actions, Blades, datos/JS reales, estructura de referencia, assets, mappings de las tres vistas, contratos reutilizables/faltantes, multitenancy, arquitectura y orden. El contrato existente permite avanzar en extracción visual y adaptación de datos soportados; no permite aún certificar catálogo público aislado ni todos los controles de la referencia.

Único archivo creado por esta fase: este documento. Cambios de markup, selectores JS, CSS, controladores, rutas o base de datos: **ninguno**.

Estado final de la fase: **NEEDS BACKEND ALIGNMENT**.

## Appendix — Reference file manifest

Inventario completo de archivos con tamaño y SHA-256, generado desde la carpeta temporal al auditar. Los hashes permiten comparar copias permanentes sin depender del directorio después de eliminarlo.

| Archivo relativo | Bytes | SHA-256 |
|---|---:|---|
| css/bootstrap.min.css | 159469 | a98de7f79af22bd534296f9a1779bc76876282d7e55b6e65975b9946b31f5f5b |
| css/elegant-icons.css | 25024 | 49a7ba596bb0c4f37e4a92a94ae6ebc433882efdd54d629bd9ea705490b1a2cf |
| css/font-awesome.min.css | 613 | 158d58072880824514a2464065f64f02db0bb5d1742bb015fa30efe6c5a1b1d3 |
| css/style.css | 60812 | eb43009f04a0298356f56781b9c128c5969fdb4ef9333f7fde05a508b57e1431 |
| fonts/ElegantIcons.woff | 63664 | be1825e52a0dc7df04df9322f62abe2a2f2a25d98aac186de0140dfc7f6bdcae |
| fonts/fontawesome-webfont.woff2 | 77160 | 2adefcbc041e7d18fcf2d417879dc5a09997aa64d675b7a3c4b6ce33da13f3fe |
| fonts/Montserrat-OFL.txt | 4400 | 8b7141c03fa4f8d44e6345d5d4931709290f0f67875e452e95ac1fd3a027802e |
| fonts/Montserrat-Variable.ttf | 744936 | 0f7b311b2f3279e4eef9b2f968bcdbab6e28f4daeb1f049f4f278a902bcd82f7 |
| img/logo.png | 3523 | 956aef9e418b1dfff283a0cc26cd36f9775b856c425aebc1a05656942ec6e038 |
| img/product/details/product-1.jpg | 38304 | 2d76533921f82e5c97e9ea6abfbc456aad201bb19c9d5d7dfdff25ce0c857784 |
| img/product/details/product-2.jpg | 21257 | 3b49b19916fa886f45009af72166f392c3ce34227f7830947db2d3d355343991 |
| img/product/details/product-3.jpg | 23839 | 319400ec0afce8ff205bb546c243768984f635b9e61bcf3a54d7722fc8a6e704 |
| img/product/details/product-4.jpg | 20073 | 3bd9514bf23950b12056956f51a4939b2a6c93160c289539e1d448f119d39f3b |
| img/product/details/thumb-1.jpg | 3000 | 5282789ce6f42aa89a909767c3c61ef3d0500c71b7b028d4360b20ed38544462 |
| img/product/details/thumb-2.jpg | 3709 | 0a2cbd492f74c14e2844b619f5870918cc8e3b0db8e6856fd91e4c12ae66dcd8 |
| img/product/details/thumb-3.jpg | 4601 | c0fe674848bb9880a0bfdbb275bc6f31dc906bd19a21cf86d659a34bd77d8107 |
| img/product/details/thumb-4.jpg | 3222 | 827dbf7a84165d72064c78c4a82f0445c7de791f432d782d1db1c2ff0864f4c2 |
| img/product/product-1.jpg | 18184 | 1118222f94dd4f0a65b7fe523415256d08f25346f0f53057c67aa5a4cb6dcd7c |
| img/product/product-2.jpg | 20295 | 0f53daa86cd9c478d58f2506633bdf583548ae98f3d1cbb7f0860105ed8e7931 |
| img/product/product-3.jpg | 18252 | c5ffd182437efa17c0bf7f0c3cc67422607422ea7f8c5cfc4ece5d647131bea1 |
| img/product/product-4.jpg | 21651 | 18cf39b72e29a614de0bb91ef7573bce5d104613243f8b65d54a8b70d5fe1336 |
| img/product/product-5.jpg | 24585 | b8421cab12c696ba2fdd56d7b6e7250fc917db32dfbca1262536897a1e9af52c |
| img/product/product-6.jpg | 20391 | b38b63605114aa516c7cb9e2f2ecbb85e1ebb4af212c574275a7a5dcd022ff48 |
| img/product/related/rp-1.jpg | 17110 | 2a5504f95088884e6ce44ea498a4c306d452e9d4ed11faf8faf239455bd32356 |
| img/product/related/rp-2.jpg | 16748 | ea8641d4ef7bc1cd30d4f076032789a25b7965a9fa9184587c1e89dead227a67 |
| img/product/related/rp-3.jpg | 31317 | acb554f7124e5257c4126f745c4c84de5dbb7974cea18599eea0a75d1431f025 |
| img/product/related/rp-4.jpg | 17864 | 657599c7357c3ba1dd7388acc8423944f63109339d351b993d7004e440d751c2 |
| img/shop/shop-1.jpg | 14106 | b7dfbaf325d5a2cb01404f900ccd2a6d4b6de9d8c44e8459c6a568fdb0786e35 |
| img/shop/shop-2.jpg | 11844 | c31ec15cbc70e5798a949fd0257caddb7e15fcc06690387a540b6f50a9677bd8 |
| img/shop/shop-3.jpg | 15605 | a13b2f9cc721932ee54aaf309453d33e0a9682aec3666dc6c0744b18cde0fbab |
| img/shop/shop-4.jpg | 11913 | 7c6ef6ae885aa3984b5fe017fff275d0c0e19a7b19140a28f419982fbd0968b3 |
| img/shop/shop-5.jpg | 16914 | 99ad21e3523ba5c58b8f3e5c09291d53c1b019193240841a73c107489d8c8e57 |
| img/shop/shop-6.jpg | 12953 | 27ae53086a691076f723b39c692f008e71717c0a0efbe33a82861bb201cd6951 |
| img/shop/shop-7.jpg | 10538 | 6edea7f624efdd510dca362de6fdc7f3fd76ecd46748cbc4267295276d0ac63a |
| img/shop/shop-8.jpg | 20260 | 1d488508621e35111e500d79ba07e2bb97d329ed59b304cb39b8ef3115c637a9 |
| img/shop/shop-9.jpg | 10771 | 47b688f57f77c82c52c465025f782e00d6f9beb8865af068fa0cf75cae87977d |
| index.html | 15940 | 76b61ce1d73293c30aa2ac0757c193d9a6e36e3b0a28a62268456e4f186cd4be |
| js/catalog-main.js | 11656 | 92433d0e88cdacf89f637b20c25c265bfe7fc6f3d137dd6f1a5e642a5f7e87e7 |
| product-details.html | 16744 | cb21f65a8f8926facc8825c5a5bce065b90d550a6bd1d7f5f1942c54e9811950 |
| readme.txt | 410 | 4b6ac20e8048f6aa28e840cad948ea04c8a7220c822dc6549109bcf8e90dc7c7 |
| sass/_base.scss | 1567 | 6a6495212f0665c1c710b71be4407b5e56ee8cb1e4b84b610c9f241979422a03 |
| sass/_catalog-coherence.scss | 7778 | cd7b41e964a58aa527893714696d338159673809a7330e06532a52100249e627 |
| sass/_catalog-filters.scss | 6173 | 0d9823e2f2a52a4e8f51307edc919a3efbe80a46c470d2b756e781b6bdd1bbb5 |
| sass/_catalog-home.scss | 11072 | 00a101a5baec78b1f78f5fbeb8d67311c27bcf753ded89215052a817b480d2f7 |
| sass/_catalog-mobile-menu.scss | 2339 | fb90679983050c13fc55079a368b152422d6c3a24f7dfa62752eba19d7dece61 |
| sass/_catalog-product-detail.scss | 9628 | 66bfe16a7b7d9a1a38453faacdc88142b16eed7ef5f600206a883fe00495c403 |
| sass/_catalog-search.scss | 1346 | 414dfccb80d556118aa9a79028df438ba73bbcc3ac34447c03112e003f6f143b |
| sass/_catalog-shop.scss | 6977 | 37c90c062e2abbdd5b097b9c771fa0d69dece3090cc24fccd69ca1cecca5531e |
| sass/_catalog-theme-tester.scss | 4047 | 93e6e36432376ab3286095c9a1e9ee61ca5b2b17ee0066cce53ad7a21f0211a7 |
| sass/_catalog-theme.scss | 777 | 9581b05e7202112f0cc247b9f62a3e3bcecd40a77a235c4a9db314156a7553bf |
| sass/_header.scss | 2669 | bbfa05a6ecec847a4e1f09fe1011e0e561416056c67f91741c6138a3c0db3cdf |
| sass/_mixins.scss | 180 | 0f31be6abd2a0463c69fb981ac282f9acf4ff2ecfd47e0cf08181273a386ebb9 |
| sass/_responsive.scss | 3029 | 7f26e1d87cd4a9348f5ccb9b5110e09ff913c46c99b7f6ad8700113b6c4114ba |
| sass/_variable.scss | 1374 | 89eff3c58cb386b100982add2924e987ae7075c0e531ca63a93aab9e1a8693ff |
| sass/style.scss | 347 | 4dccba30b33148f3ebf2a9c64f42aeca753c2e162fa93470f26af4121cbaad68 |
| shop.html | 21002 | ee8701390877ece6fe2368132a24371aece10b6e36854d1bdb395ae19235d74b |
