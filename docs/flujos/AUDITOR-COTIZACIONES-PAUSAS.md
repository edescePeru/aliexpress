# Estado del paquete: cotizaciones y módulos antiguos

## Texto para el auditor — solo este paquete

**D — Cotizaciones de venta:** Se preparó la adaptación de las consultas del formulario a los permisos efectivos de crear o editar, incluidos permisos directos. Las consultas para registrar comprobante mantienen el requisito de editar y verifican la cotización en su ámbito autorizado. Los contactos se consultan por cliente y tenant, excluyendo contactos eliminados o incompatibles; se conserva el consumidor compartido de cotizaciones industriales. Las lecturas comprueban contexto y acceso al local. Pasaron las pruebas privadas con permisos directos/de rol, contexto ausente, local no asignado y referencias ajenas. Estado: preparado y probado de forma aislada; pendiente de instalación y validación HTTP/UI real. No se certifican las mutaciones completas ni la emisión externa con estas pruebas.

**S — Datos Generales antiguo:** Por decisión funcional se retira su acceso, conservando código y datos históricos. El menú lo oculta y una barrera del servidor devuelve 410 antes de ejecutar sus acciones, evitando que una consulta cree configuraciones. Se mantiene Configuraciones por empresa. Estado: preparado; pendiente de instalación y revisión HTTP real.

**R — Venta Libre:** Por decisión funcional se pausa, sin eliminar código ni datos. Queda oculta del menú y bloqueada en el servidor con 423, tanto para consultas como para acciones. Las pruebas privadas verificaron el bloqueo antes de validación y SQL. Estado: contenido mediante pausa; los controles de cajas, subtipos y sesiones siguen pendientes antes de reactivarla. No se declara corregido el flujo de venta libre.

**H — Accesos antiguo:** Por decisión funcional se ocultan y bloquean con 423 los mantenedores antiguos de roles, permisos y usuarios. Se conservan Usuarios Web, perfil y selector operativo de usuarios, así como las rutas actuales de Platform Admin. Estado: preparado; pendiente de instalación y revisión con perfiles reales. No se modifican las asignaciones existentes de permisos ni se declara resuelto todo el bloque H por esta pausa.

## Evidencia y límites

- `prueba_pausas.php`: router privado; acciones protegidas antes de ejecución y FormRequest, sin SQL. Autenticación/CSRF reales pendientes.
- `prueba_cotizaciones.php`: modelos/controladores reales con SQLite privado, permisos directos y de rol, catálogos y contactos contextualizados, rechazo de cotizaciones ajenas y lecturas sin escrituras. Precio e impuesto simulados: no certifica cálculos comerciales.
- `verificar_sidebar.php`: compilación de los tres layouts y vista de indisponibilidad; cuatro bloques activos deshabilitados renderizan vacíos. No certifica diseño visual.
- No se ejecutaron llamadas a Nubefact ni cambios en bases del negocio. No hay migración ni reparación automática de históricos.

Este documento informa únicamente este paquete; no sustituye el estado de los demás hallazgos ni declara cerrada la auditoría completa.
