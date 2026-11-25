# Dashboard Owner — ajuste visual

- Se retira la indicación repetida al pie del sidebar, que se cortaba por el ancho del menú. La ayuda «Elige dónde trabajar» permanece en el contenido principal.
- Se completan las secciones del layout compartido: título de página «Dashboard del propietario» y cabecera de tarjeta «Administración del negocio», evitando el encabezado vacío.
- El bloque de bienvenida utiliza azul oscuro y texto claro, acorde al sidebar. El estilo está limitado a esta pantalla y este bloque; no cambia colores de otras tarjetas o módulos.

Solo cambian dos vistas Blade. No se modifican contexto, cupos, permisos, rutas ni controladores. Después de copiar las vistas, ejecutar `php artisan view:clear` y actualizar la página.
