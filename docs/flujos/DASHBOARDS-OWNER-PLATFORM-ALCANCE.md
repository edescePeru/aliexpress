# Siguiente bloque: Dashboard Owner y Platform Admin

## Owner

- Iniciar sesión sin empresa/local seleccionados; los selectores deben estar disponibles y mostrar que falta elegir contexto.
- Sin contexto: acceso a las funciones autorizadas de ámbito tenant, como Empresas y Usuarios Web. La clasificación final de los demás enlaces de configuración debe contrastarse con sus rutas/controladores: no se asume que todos requieren local ni que todos funcionan sin empresa.
- Con empresa y local válidos: mostrar módulos operativos según permisos efectivos. Cambiar contexto debe actualizar menú y contenido sin conservar un objeto de otra empresa/local.
- Mantener los límites del plan de usuarios, empresas y total de locales del tenant. El owner ocupa un cupo de usuario y el local principal consume un cupo de local. No se reimplementan estos límites solo desde la interfaz.
- Promociones, Accesos legacy, Datos Generales antiguos, Venta Libre y demás módulos pausados no deben reaparecer. Se conservan el código/datos según las decisiones previas.

## Platform Admin

- Mantener la plataforma administrativa separada de la operación de una empresa.
- Sin contexto operativo, mostrar solo sus módulos de plataforma autorizados; no enlaces que fallen por exigir empresa/local.
- Conservar mantenedores de planes, tenants, conexiones y catálogos de plataforma existentes. No retirarlos al reestructurar el sidebar del owner.
- Evitar que ocultar un enlace sea la única protección: las rutas conservan autenticación, permisos y comprobaciones de contexto del servidor.

## Aceptación agrupada

Probar owner sin contexto, owner con contexto válido, contexto cambiado/inactivo, usuario operativo con permisos limitados, permisos directos y Platform Admin sin contexto. Revisar inicio de sesión, navegación, selector, acceso por URL directa y ausencia de enlaces legacy. La implementación y pruebas de este bloque se entregan aparte; este documento define su alcance y no afirma que el dashboard ya esté construido.
