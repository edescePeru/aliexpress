# Tienda Web — enlaces por local en el sidebar

El enlace de Tienda Web pasa a ser un desplegable con Catálogo general y un enlace por local activo y publicado de la empresa actual. Cada enlace abre en otra pestaña y utiliza las rutas existentes `publicCatalog.company` y `publicCatalog.branch`, sin construir direcciones manualmente.

Los locales se obtienen con el mismo servicio de catálogo público y se omiten los slugs vacíos/inválidos. No se incluyen empresas distintas, locales inactivos ni locales sin slug publicado. Son enlaces al catálogo público, no accesos a la operación interna: no cambian el contexto de trabajo ni otorgan permisos para operar otros locales. Se identifica el local actualmente seleccionado.

Sin contexto, en Platform o sin catálogo publicado se mantiene el comportamiento anterior. Se conserva comentado el enlace de la tienda antigua tal como estaba en la versión revisada. No se modifican publicación, productos, stock, precios, permisos o rutas.

Solo cambian el layout y una nueva vista parcial. Copiar las vistas y ejecutar `php artisan view:clear`. Comprobar Catálogo general, un local publicado, el local actual y la exclusión de locales inactivos/sin slug.
