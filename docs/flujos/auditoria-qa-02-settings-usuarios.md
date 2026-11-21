# QA-02 — Persistencia de settings/archivos y estado/reset de usuarios

20/11/2026. Paquete anterior de mantenedores completo: documento instalado idéntico y cuatro suites aprobadas nuevamente. Este bloque agrupa dos suites adicionales sobre código instalado, sin nuevas modificaciones de aplicación.

## Configuraciones y logotipos

Suite de logotipos anterior adaptada para cargar CompanySettingImageService, CompanySettingController, settings_catalog y vista instalados. SQLite privado; public_path se redirige exclusivamente al runtime del paquete. Intervention/GD e imágenes de prueba reales, sin escritura en el ERP. 46 comprobaciones aprobadas, incluyendo texto+imagen, normalización PNG, dimensiones/transparencia, validación de archivos, permisos/contexto, referencias y limpieza tras rollback. Se compila la vista Blade instalada sin certificar renderizado de la aplicación completa.

La prueba no demuestra escritura del servidor productivo ni todas las combinaciones de settings/scope. Usa actor y servicios de URL/vista/respuesta privados; no router ni autenticación completa. Los archivos temporales generados permanecen solo en runtime del paquete y se excluyen del ZIP entregado. No usa enlaces a archivos externos ni modifica logotipos reales.

## Estado, cupo y contraseña de usuarios

Se ejercitan changeStatus y resetPassword instalados con User, TenantPlanService, TemporaryPasswordService y BcryptHasher reales sobre SQLite privado. Casos aprobados: no desactivar cuenta propia ni otro propietario; desactivar conserva usuario y limpia remember_token; reactivar dentro de cupo; rechazar cupo agotado sin modificar estado; rechazar tenant ajeno; impedir reset de propietarios; reset guarda hash verificable y obliga cambio; contraseña temporal cumple composición, sin imprimir el secreto.

El tenant/plan y actor están precargados de forma privada. No prueba alta/edición completa, relaciones de roles/company/branch, login con contraseña temporal, revocación de sesiones ya abiertas, concurrencia de cupos ni entrega real de credenciales. No se envían mensajes ni se utilizan usuarios reales.

## Estado y continuación

QA-02 parcialmente atendido. Pendientes altas/ediciones y relaciones completas de ConfigUserWeb, parámetros/catálogos restantes de material e integración HTTP/UI/MySQL. No hay corrección nueva indicada por estos casos. Mantener agrupación de siguientes pruebas; no reinstalar paquetes ni migraciones antiguas. QA-01 conserva su validación integrada de descargas reales pendiente.
