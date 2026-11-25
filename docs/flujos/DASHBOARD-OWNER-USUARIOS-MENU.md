# Dashboard Owner — ubicación de Usuarios Web

En el menú con contexto activo, Usuarios Web pasa a ser un enlace directo al final de Configuración Empresa, después de Plazo de pago. Se retiran del sidebar el encabezado Administrador y el desplegable Datos de Configuración. Accesos y Datos Generales antiguos siguen bloqueados; no se borran sus controladores, vistas o datos.

Se conservan el permiso `listUser_configUserWeb`, la restricción de owner y la sección activa existente. Sin contexto, Usuarios Web continúa disponible en Administración del negocio. El bloque exclusivo de Platform no se modifica.

Solo cambia `layouts/appAdmin2.blade.php`; no hay cambios de rutas, cupos o controladores. Copiar la vista y ejecutar `php artisan view:clear`.
