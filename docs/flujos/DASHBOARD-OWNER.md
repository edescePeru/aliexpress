# Dashboard Owner — administración y contexto explícito

## Resultado funcional

Al iniciar sesión, el owner conserva únicamente el tenant. Empresa y local quedan vacíos, aunque tenga predeterminados en sus asignaciones o una URL operativa pendiente. El inicio llega a Administración del negocio. Los usuarios operativos mantienen la selección automática que ya utilizaban; Platform mantiene su panel y no adquiere contexto de un cliente.

El inicio del owner muestra plan/cupos, Empresas y Usuarios Web según permiso, y empresas activas asignadas con sus locales activos autorizados. Los cupos de locales son el total del tenant y el owner cuenta como usuario activo. No se muestran métricas financieras ni se reutilizan las consultas operativas del dashboard antiguo para construir esta pantalla.

El selector está disponible sin contexto y en móvil. Al elegir empresa se cargan sus locales, siempre con una opción vacía inicial: no se elige silenciosamente el primero. Las respuestas atrasadas de otra empresa se descartan. El envío muestra Procesando y se bloquea; al terminar se vuelve al dashboard.

Con empresa/local elegidos, el menú operativo aparece según los permisos existentes. «Volver a administración» vacía empresa/local y conserva el tenant. No borra los predeterminados del usuario ni cierra sesiones de caja: cambia el contexto de navegación. Al volver a iniciar sesión el owner vuelve a elegir explícitamente.

## Autorización y rutas

No se limita a ocultar enlaces. El middleware mantiene al owner sin contexto y permite únicamente las rutas de administración de tenant, selección, dashboard y perfil. Una ruta operativa sin contexto devuelve 403 para JSON o redirige a Administración para navegación HTML. El guard de permisos de cada módulo sigue aplicándose cuando existe contexto.

Se revalida pertenencia activa de empresa/local en cada petición. Si el contexto dejó de ser válido, el owner vuelve al ámbito tenant y puede recuperar la selección. Los endpoints de selección existentes siguen comprobando empresa/local activos y asignados, incluidos los rechazos por tenant o empresa incorrectos.

La ruta POST nueva `tenant.context.clear` exige autenticación, CSRF del grupo web, contexto de tenant y owner. Los operadores y Platform no pueden vaciar su contexto mediante esta acción. El perfil propio sigue disponible sin empresa/local.

## Sidebar

- Sin contexto: Empresas y Usuarios Web, por sus permisos; selector disponible.
- Con contexto: módulos operativos por permisos; administración del owner conservada.
- Promociones: oculto en este sidebar por decisión del negocio; no se borra su código ni sus datos.
- Módulos ya pausados: conservan sus bloqueos actuales.
- Platform: conserva su bloque exclusivo exactamente; no se le presenta el menú operativo ni selector de empresa/local. La construcción de su nuevo dashboard sigue siendo el bloque posterior.
- Tipo de cambio y notificaciones operativas no se consultan desde el inicio sin contexto; se conserva su funcionamiento al trabajar en un local.

## Configuración Empresa: ámbito y navegación

| Apartado | Ámbito mínimo revisado | Comportamiento de este paquete |
|---|---|---|
| Empresas y sus locales | Tenant; la empresa se identifica en la ruta al administrar locales | Disponible sin contexto, con owner/permisos |
| Usuarios Web | Tenant, plan y asignaciones | Disponible sin contexto, con owner/permisos |
| Cuentas bancarias | Empresa | Visible al elegir empresa/local, conservando su permiso |
| Configuraciones | Empresa | Visible al elegir empresa/local; conserva su validación contextual |
| Servicios | Maestro de ámbito tenant en el código actual | Se mantiene en el menú de trabajo tras elegir contexto; no se cambia el ámbito de sus datos |
| Cajas | Empresa y cajas compartidas/asignadas a local | Visible tras elegir contexto, conservando sus reglas actuales |
| Subtipos bancarios | Empresa y referencias de caja | Visible tras elegir contexto y por permiso |
| Plazos de pago | Empresa | Visible tras elegir contexto y por permiso |

La interfaz de este lote activa el contexto operativo completo empresa+local. No agrega selección parcial de solo empresa ni modifica unilateralmente el ámbito de Servicios. Si una empresa aún no tiene un local asignado, se administra desde Empresas/Usuarios Web antes de trabajar.

## Cupos de usuarios

Se conservaron conteo, plan, owner incluido, perfiles y asignaciones. Se reforzaron creación/reactivación con el mismo bloqueo de tenant usado por los límites de estructura, seguido de la comprobación dentro de la transacción. Una solicitud no puede confiar solo en el precheck de pantalla. Una activación repetida no consume un cupo adicional; el owner no puede desactivarse desde ese módulo.

## Pruebas de este lote

Pasaron PHP 7.3 y JavaScript, pruebas con Eloquent/SQLite privados, login hook, middleware, selección/retiro de contexto, pivots incompatibles, local desasignado, continuidad del operador y Platform, cupos, creación real de usuario con perfil/asignaciones y revalidación tardía del cupo. También se renderizó Blade completo para owner sin/con contexto, operador y Platform con capacidades amplias para probar fronteras del menú.

Las pruebas de cupo usan intercalación simulada y bloqueo real del servicio en SQLite; no constituyen certificación de concurrencia MySQL. La revisión visual, login/cookies/CSRF y navegación del despliegue se comprueban después de instalar. Los HTML de QA son ficticios y no se publican.

## Auditoría anterior

Por decisión expresa del negocio, J/P y K se consideran cerrados con la evidencia disponible. No se afirma que sus verificaciones adicionales se hayan ejecutado. Este paquete no reabre esos puntos ni cambia guías, pagos, deudas o documentos históricos.
