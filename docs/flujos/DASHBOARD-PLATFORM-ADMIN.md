# Dashboard de Platform Admin

## Acceso y contexto

Se mantiene la ruta `platform.dashboard` y el grupo autenticado `platform.admin`. El servicio de resumen vuelve a exigir usuario activo y condición Platform Admin antes de consultar datos. Owner y operadores no reciben acceso aunque conozcan la URL o se invoque el controlador directamente.

La plataforma no selecciona un tenant/empresa/local operativo. Se limpia cualquier contexto residual de sesión y se muestra una vista global administrativa. No se introducen acceso impersonado, selección de negocio para operar ni consultas a ventas, cajas, deudas o guías.

## Indicadores

| Indicador | Significado |
|---|---|
| Tenants | Total registrado, mostrando activos e inactivos |
| Empresas activas | Empresas activas asociadas a tenants existentes |
| Locales activos | Locales activos de empresas/tenants existentes, incluyendo empresas inactivas, igual que el cupo del plan |
| Usuarios activos | Usuarios habilitados asociados a tenants existentes, incluidos owners y excluidos Platform Admin |

Empresas, locales y usuarios se cuentan por su propio estado, incluso si el tenant está inactivo. La vista lo indica; no son indicadores comerciales o de facturación. Las referencias huérfanas no se agregan a esos tres totales.

## Revisión de planes

Se señalan tenants activos sin plan, con plan inactivo, con máximos pendientes de configurar o con consumo mayor que el permitido. Igualar el máximo no se interpreta como excederlo. Un límite NULL se presenta como sin configurar, conservando la política existente; no se convierte en ilimitado.

El consumo usa usuarios activos no Platform, empresas activas y todos los locales activos del tenant, incluso cuando su empresa está desactivada. El listado muestra hasta ocho tenants ordenados por nombre, además del total de avisos y un enlace al directorio completo. Cada tenant tiene enlace a su ficha existente.

Los avisos no modifican datos, cambian planes, desactivan usuarios/empresas/locales ni corrigen históricos automáticamente.

## Navegación preservada

Se conservan Tenants, Planes, Plantillas de perfiles, Roles por tenant y Auditoría. Se incorporan a los accesos del dashboard los mantenedores ya existentes de Nubefact, Catálogos de guías, Tipos de impuesto y Parámetros laborales, que ya estaban en el sidebar exclusivo de Platform.

No se modifican esos mantenedores ni sus rutas. Se mantiene el sidebar que ya separa Platform de Owner/operación. El dashboard usa un título visible y bienvenida azul oscuro con estilos limitados a esta pantalla.

## Validación

Pasaron sintaxis PHP 7.3, pruebas con SQLite privado y renderizado completo de Blade. Se comprobaron usuario ausente/inactivo, owner y operador rechazados, retiro de contexto residual, conteos globales, referencias huérfanas, locales de empresas inactivas, igualdad/exceso de cupos, plan ausente/inactivo, límites NULL, listado limitado, estado vacío, nueve enlaces conservados y GET sin escrituras de negocio.

Después de instalar se verifica el aspecto y navegación en el despliegue. Este paquete no ejecuta mutaciones ni conexiones externas y no reabre los cierres de auditoría J/P/K por decisión del negocio.
