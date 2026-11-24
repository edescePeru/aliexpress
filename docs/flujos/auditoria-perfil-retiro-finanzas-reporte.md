# Auditoría — Perfil, retiro de compras financieras y permiso del reporte

## Alcance aprobado

Se conserva el reporte **Órdenes por Material** (`report.orders.by.materials`) y las órdenes de compra normales. Se retira operativamente **Órdenes de Compra Finanzas**, conservando tablas, referencias de créditos, importes e historia. No se retira el módulo general de Finanzas, facturas, gastos o créditos. No se cambia el dashboard, contexto, planes ni GRE.

## A / PROFILE-01 — Avatar propio

`UserController::changeImage` conserva SELF y delega a `ProfileAvatarService`. Se valida archivo real PNG/JPEG, máximo 2048 KB, máximo 4096 por lado y 8 megapíxeles. Se decodifica y recodifica mediante GD; nunca se publica el archivo original ni se usa su extensión. El nombre aleatorio pertenece al usuario y se crea de forma exclusiva.

La operación bloquea el registro del usuario dentro de su propia transacción. Un fallo elimina el archivo nuevo si la base no lo referencia y mantiene la imagen anterior. Si el resultado del commit es incierto, no se borra un archivo potencialmente referenciado. Después del commit solo se retira un nombre propio reconocido, sin referencias de otros usuarios y que no sea un enlace simbólico. Los nombres de imágenes por defecto, compartidas o de origen incierto se conservan. No se hace limpieza masiva de huérfanos históricos.

El formulario informa formatos/tamaño y el envío bloquea doble clic, usa el evento recibido y muestra los errores de validación del servidor. Un fallo de permisos del filesystem o una interrupción abrupta puede requerir revisión de archivos residuales: no se promete atomicidad entre filesystem y DB ante caída del proceso. Las restricciones de escritura de PHP sobre `public/images/users` deben estar disponibles, como en la carga actual.

## F/G/I/O — Retiro, en vez de reparación

Las 25 acciones/auxiliares de `OrderPurchaseFinanceController` se convierten en respuesta 410 para HTML/JSON. Se conservan nombres de rutas y sus permisos existentes; no hay FormRequest, consultas de negocio, escrituras, PDFs ni llamadas de proveedor desde esas acciones. Conservar las rutas evita romper consumidores que las registran, pero ya no da acceso al flujo retirado.

Se retiraron los dos enlaces financieros de regularización en reportes de facturas y el enlace de documento financiero en SupplierCreditController. Los vínculos históricos de los créditos no se borran ni reinterpretan. Las pantallas internas del módulo retirado y sus JS quedan como código histórico sin acceso desde sus acciones; no se eliminan tablas ni archivos históricos.

No se encontraron enlaces financieros activos en los sidebars revisados (excluyendo comentarios Blade). El enlace **Reportes → Órdenes por Material** se conserva. No se modificaron layouts innecesariamente.

El retiro no demuestra privacidad de URLs directas a PDFs/imágenes públicos anteriores: ese alcance sigue en P, seguridad documental. Los hallazgos F/G/I/O deben registrarse como retiro operativo verificado tras instalar, no como fórmulas o mutaciones reparadas.

## Q — Reporte por materiales conservado

Se exige `report_orderPurchaseExpress` en backend para las tres acciones: pantalla, fetch y exportación. Se exige además tenant/empresa del usuario autenticado; Platform Admin sin contexto operativo no accede. Se mantienen las rutas, filtros, consulta, formato, cálculo y permisos propios de las órdenes normales. No se reasigna create/list a quien solo tiene acceso a reporte.

Este paquete atiende el permiso residual del reporte; no cierra los otros puntos Q de compras normales/Express ni certifica todas las fórmulas, referencias históricas o el XLSX real.

## Pruebas previas a instalación

- Avatar: PNG/JPEG reales, TXT disfrazado, tamaño, dimensiones, SELF, fallo de persistencia con conservación exacta de bytes, limpieza de archivo nuevo, recodificación y preservación de default/compartido. SQLite y filesystem privados; GD real.
- Retiro: 25 acciones × HTML/JSON con 410 y cero SQL desde las acciones; conservación de fila histórica, correspondencia de métodos con rutas, retiro de enlaces y conservación del reporte del sidebar.
- Reporte: User/Spatie/Gate y middleware reales; permiso directo y por rol, list-only/sin permiso rechazados; contexto ausente/incompatible rechazado. La consulta instalada sobre fixtures devuelve solo la empresa activa.
- Sintaxis PHP/JS y compilación de las vistas afectadas.

Pendiente: instalar y probar HTTP/UI con autenticación/CSRF, descarga real de reporte y condiciones del filesystem del servidor. Sin nuevas emisiones a Nubefact ni cambios en la base/archivos del ERP durante las pruebas privadas.
