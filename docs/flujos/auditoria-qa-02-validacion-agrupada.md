# QA-02 — Evidencia agrupada y pendientes de cierre

20/11/2026. Avatar/parámetros verificado: hashes idénticos de ConfigUserWebController, UserAvatarFiles y documento instalados. Prueba de avatar adaptada para cargar clases instaladas, no candidatas: rollback conserva referencia/bytes, retira solo nuevo archivo, éxito limpia anterior sin referencias y conserva compartido. Se repiten altas/edición como fixture privada.

Nuevos casos agrupados aprobados: edición del propietario actualiza datos permitidos y no aplica tenant/privilegios/alcance operativo enviados; guardado de parámetros con dependencias, sin duplicar, versión obsoleta, rollback y membresía; 53 comprobaciones de subcategorías con servicio/Request/controlador instalados, permisos simulados, dependencias y rollback de árboles. No se requirió otro cambio de aplicación.

Las pruebas son privadas, SQLite :memory:, con rutas/respuestas/identidad simuladas donde se indica. Avatar escribe exclusivamente runtime del paquete. No prueba HTTP/UI, concurrencia MySQL, todas las escaladas posibles ni todos los CRUD de catálogos. No hay secretos ni modificaciones de datos reales.

## Lista única de validación real de QA-02

| Grupo | Prueba integrada pendiente |
|---|---|
| Plazos | Crear/editar, utilizar en documento, comprobar restricciones y desactivación con historia conservada |
| Categorías y subcategorías | Borrado permitido y rechazado por dependencia, selección múltiple y usuario sin permiso |
| Categorías de facturas | CRUD autorizado, restricción de referencia histórica y cambio de contexto |
| Impuestos | Validación y cambio de predeterminado, sin certificar concurrencia por pruebas SQLite |
| Cuentas de empresa | Crear/editar y alternar estado/default; comprobar empresa y relaciones con cajas según uso real |
| Settings y archivos | Texto+imagen, archivos inválidos, cambio de empresa, vista previa y reversión |
| Usuarios | Alta/edición operativa/propietario, estado/cupo/reset/avatar y restricciones por URL |
| Materiales | Guardado de parámetros, formulario obsoleto, selectores y CRUD de los catálogos efectivamente utilizados |

Registrar versión, entorno, fecha, rol, contexto y resultado. Solo usar copia de pruebas para mutaciones de demostración. No repetir cambios productivos destructivos ni enviar credenciales para certificar la lista. Quedan también concurrencia MySQL (cupos/defaults/borrados) y cobertura CRUD no ejercitada; se mantienen explícitos, no se infieren a partir de lecturas.

## Estado y avance

QA-02 permanece parcialmente atendido; corrección de avatar instalada y evidencias automatizadas agrupadas aprobadas en los casos descritos. No se declara cierre completo. No se proponen más microentregas sin defecto reproducido: se conserva esta lista para integración y se continúa con QA-03, ciclo de traslados/disponibilidad/concurrencia. QA-01 y DATA-01 mantienen sus pendientes separados. Este documento no certifica el resto del backlog.
