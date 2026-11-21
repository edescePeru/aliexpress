# EXT-01 — Configuración por empresa y contrato local de GRE

20/11/2026. QA-04 instalado completo: documento idéntico, restauraciones de clientes y proveedor/multipágina aprobadas otra vez. Se continúa con EXT-01 en un bloque de cuatro suites, sin cambios nuevos de aplicación para los casos probados.

## Configuración

Suite nueva con NubefactCompanyConfigService/Connection, CompanyNubefactConfig y controlador de plataforma instalados, usando SQLite privado y Encrypter con clave efímera. Token cifrado y oculto en serialización; series factura/boleta/nota y serie/número inicial GRE; rechazo de URL no HTTPS, host ajeno, query/credenciales incrustadas, RUC inconsistente, conexión deshabilitada, serie inválida y token no descifrable. Rechaza contexto distinto; permite resolución técnica sin sesión con IDs explícitos del documento. Controlador declara auth/platform.admin; esto no demuestra middleware HTTP completo.

## Guías

Tres suites previas adaptadas a modelos/servicios/controlador instalados, con proveedor simulado. Consulta no muta ante fallo externo simulado; respuesta parcial conserva documentos; snapshot obsoleto y contexto ajeno rechazados; aplicación de respuesta con booleanos/códigos válidos. Operación persistente y replay conservan guía existente, no consumen otra clave tras rollback y mantienen históricos sin clave. Migración aplicada solo a esquema SQLite privado. Se compila fuente Blade instalada en runtime, sin render de layout real.

No se envía red ni se usan credenciales reales. Las suites sustituyen proveedor y en algunos casos servicio de conexión. Algunas llamadas directas no pasan por los validadores/middleware de creación HTTP. La configuración no prueba guardado POST administrativo ni versión obsoleta de esa pantalla; la protección de snapshot de guía es un control distinto y no se confunde con ella.

## Estado y validación externa agrupada

EXT-01 parcialmente atendido: contrato local ampliado y probado. Pendientes en copia/sandbox: guardar configuración por plataforma, rechazar formulario administrativo obsoleto, creación GRE completa con series por empresa, aceptación/rechazo/consulta/documentos reales, timeout y reintentos, concurrencia MySQL y roles/contexto por HTTP. No realizar emisión productiva para obtener evidencia. Un replay de clave existente no demuestra deduplicación entre formularios con claves distintas ni evita por sí mismo duplicado remoto tras timeout.

Sin sandbox configurado y evidencia de proveedor, mantener certificación externa pendiente. QA-01/02/03/04 mantienen sus respectivas listas de integración/concurrencia, DATA-01 su reconciliación. No se declara cierre global de auditoría por pasar fixtures.
