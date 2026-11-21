# EXT-01 — Servicio real con HTTP interceptado

20/11/2026. Guardado/versionado anterior verificado completo por hash de modelo/controlador/vista/documento. Suite administrativa aprobada otra vez. No se cambió aplicación en este paso.

Nueva prueba agrupada con NubefactGreService instalado y Factory/PendingRequest reales de Laravel. Callback fake global devuelve una promesa PSR-7 privada para cualquier URL o lanza fallo controlado; nunca cae al handler de red. No bootstrap, base ERP ni credenciales reales. Modelo de guía y relaciones precargados en memoria.

Casos aprobados: tipo/serie/número persistidos; fechas de emisión y traslado independientes; peso KGM/cantidad sin conversión; transporte público y privado con principal/secundario/conductor; indicador implementado traducido y desconocido rechazado antes de HTTP; POST con operación/cabecera correctas; consulta con identidad correcta y booleano de rechazo conservado; HTTP 500 en ambos métodos produce error; timeout simulado se propaga sin reintento automático; no JSON no se trata como aceptación; guía local sin modificaciones por servicio.

Límites: se prueba frontera de transporte, no creación completa mediante FormRequest/router ni resultado SUNAT. La respuesta no JSON provoca TypeError por contrato de retorno array; no se declara una nueva gestión amigable ni prueba del mensaje final al usuario. No se evalúa el cuerpo real/documentos del proveedor, latencia real, costos de llamada, duplicidad remota tras timeout ni concurrencia MySQL.

Observación de entorno de pruebas: Factory::response del Laravel instalado llama GuzzleHttp\Promise\promise_for, función ausente en la versión de promises presente. La fixture usa FulfilledPromise y respuesta PSR-7 directamente, ambas instaladas, sin modificar vendor ni introducir polyfill. Esto es un ajuste de la fixture; no se ha probado todo el árbol Composer ni se declara solucionado un conflicto general de dependencias.

Estado EXT-01 parcialmente atendido. Control administrativo y contrato local ampliados con evidencia aislada; quedan validación HTTP/UI de flujo completo, sandbox proveedor (creación/consulta/PDF/XML/CDR), fallos y concurrencia entre conexiones. Mantener agrupada esa validación externa. Ningún test interceptado certifica emisión ni aceptación real por SUNAT.
