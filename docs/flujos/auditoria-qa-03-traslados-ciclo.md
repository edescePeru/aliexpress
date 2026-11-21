# QA-03 — Solicitud, reserva, tránsito, recepción y consulta

20/11/2026. Documento QA-02 validación agrupada instalado idéntico. Se inicia QA-03 mediante un bloque completo de cuatro suites previas adaptadas para cargar código instalado, no clases candidatas de entregas antiguas.

| Suite | Resultado | Alcance |
|---|---|---|
| Solicitudes | 58 comprobaciones aprobadas | Contexto, productos, cantidades, datos enviados no confiables, reintento/actor, sin modificar stock ni reservas al solicitar |
| Preparación | 39 aprobadas | Reservas, lotes/unidades, stock origen, coherencia nivel/lote, costo y moneda de asignación |
| Despacho/recepción | 49 aprobadas | Estados, reintento sin doble descuento, recepción parcial/completa, límites y cantidades inválidas, items unitarios/granel, fallo de costo y rollback; incluye comprobación privada de driver de migración |
| Disponibilidad | 30 aprobadas | Grupos local/general, otros contextos, on_hand/reserved/available, inconsistencias, trazabilidad, producto habilitado, tránsito y recepción parcial, contexto de formulario y respuesta de consulta |

La consulta refleja tránsito saliente/entrante por separado; lo despachado no suma disponibilidad al destino. La recepción parcial incorpora únicamente lo recibido y deja saldo en tránsito. Los casos de fallo de recepción no dejan stock/recibos/unidades parcialmente movidos en las fixtures.

Todas usan Eloquent/servicios instalados con SQLite :memory:, tablas/actores privados y contexto de sesión privado. Se construye esquema de traslados con clases instaladas en la conexión privada; no es una nueva migración del ERP. Servicios de costo/configuración se sustituyen en partes de las fixtures con valores deterministas y con fallos controlados: no certifican valorización histórica real ni todos los costos. Varias tablas de soporte tienen esquema reducido de prueba.

Disponibilidad usa servicios/controlador/Request instalados y monta un router privado con un fragmento de rutas extraído del archivo instalado durante preparación. Se excluyó la parte antigua de render con layout simulado y la comparación obsoleta de catálogo de permisos contra un paquete anterior; no se certifica UI ni caché de rutas. El actor de permisos no sustituye login/Gate completo del servidor.

Estado QA-03: parcialmente atendido, cuatro suites aprobadas sin cambio de aplicación indicado en estos casos. Pendientes ciclo positivo por HTTP con roles/contextos reales y comprobación MySQL de simultaneidad entre conexiones: reservas concurrentes, doble despacho, recepción/reintento simultáneos y conciliación lote/nivel/unidades/Kardex tras el ciclo. Los reintentos secuenciales probados no certifican concurrencia. No modificar stock productivo para obtener evidencia; usar copia de pruebas.

Continuación: comprobar concurrencia en entorno aislado adecuado o documentar expresamente su falta de evidencia antes de declarar cierre. QA-04 y EXT-01 permanecen aparte; DATA-01 no se resuelve por pasar estas fixtures.
