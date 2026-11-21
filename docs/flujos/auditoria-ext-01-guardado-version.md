# EXT-01 — Configuración obsoleta y guardado agrupado

20/11/2026. Paquete configuración/guías anterior instalado completo: documentación idéntica y cuatro suites aprobadas. Se amplió el controlador administrativo con prueba directa, sin tráfico externo.

## Fallo reproducido

updated_at se usaba como versión del formulario y se comparaba bajo bloqueo. Congelando tiempo al mismo segundo, dos cambios de serie con la versión inicial fueron aceptados: la marca temporal no distinguía los contenidos. Reproducción con SQLite privado, Controller/Model instalados y redirects/auditoría privados. No se usó conexión ni token real.

## Corrección

CompanyNubefactConfig::configurationVersion devuelve SHA-256 de atributos originales ordenados, incluido ciphertext. No se serializa el contenido ni el token. La vista usa huella, controlador valida hasta 64 caracteres y compara la huella del registro bloqueado. Se conservan reglas de RUC/URL/series, bloqueos y auditoría existente. La huella es control de consistencia, no un permiso o sustituto de autenticación. Formularios previos deben recargarse.

## Pruebas aprobadas

Con modelo/controlador propuestos: guardar sin token conserva ciphertext; segundo formulario con versión anterior en el mismo segundo se rechaza; ruta nueva sin token rechazada; ruta+token+versión vigentes se guardan y cambian huella; input de redirección y propiedades de auditoría recibidas no incluyen token/ciphertext; fallo simulado de auditoría revierte configuración; transacciones cerradas. También se repite resolución de configuración por empresa del paquete previo.

Auditoría se sustituye por un servicio privado que captura propiedades y puede fallar: no se certifica el almacenamiento real de ActivityLog. Redirect privado no certifica flash/cookies ni experiencia del navegador. No se ejecutó router, middleware, POST HTTP real, emisión ni consultas a proveedor. Clave/token efímeros. La prueba secuencial con tiempo congelado no certifica lockForUpdate/concurrencia MySQL.

Estado EXT-01 parcialmente atendido: corrección preparada pendiente de instalación y validación HTTP; sandbox, creación GRE completa y certificación externa siguen pendientes. Sin migraciones, cambios de datos históricos ni emisión productiva. Los demás hallazgos conservan sus listas de integración/reconciliación.
