# QA-02 — Categorías financieras, impuestos, cuentas y guards de usuarios

20/11/2026. Documento de plazos/categorías instalado idéntico. A petición del usuario se agrupan varios mantenedores en una entrega. Sin cambios nuevos de aplicación para los casos probados.

| Familia | Evidencia aprobada | Límites principales |
|---|---|---|
| Categorías de facturas | Crear con contexto servidor, actualizar, rechazar ID ajeno por Request, impedir borrar categoría con factura histórica incluso borrada lógicamente, rollback de actualización fallida, soft delete permitido sin referencias | No UI, router/roles completos ni concurrencia entre asignación y eliminación |
| Tipos de impuesto | Crear, normalizar código, rechazar duplicado, proteger predeterminado, cambiar predeterminado, preservar clave técnica, desactivar no predeterminado y rollback al fallar creación tras cambiar default | No validación fiscal externa, concurrencia de defaults ni certificación de todas las entradas; alcance global existente |
| Cuentas bancarias de empresa | Primera cuenta usa contexto y default; cambio de default; desactivar elige otra activa; reactivar no desplaza default; ID ajeno de otra empresa rechazado; rollback de creación restaura default y evita fila parcial | No concurrencia MySQL, modificación completa, integración con cajas ni números bancarios reales |
| ConfigUserWeb | Guard propietario permite caso propio, consulta interna excluye otro tenant y plataforma; rechazo invitado/no propietario/plataforma | Guard de identidad privado, reflexión de métodos internos; no CRUD, contraseñas, cuotas, roles/membresías ni HTTP real |

Implementación de prueba: Container/Eloquent/Validator instalados, SQLite :memory: privado, Requests validados directamente, respuesta JSON y rutas simuladas. El contenedor privado implementa abort mediante HttpException; no simula el stack de middleware. Los fallos de persistencia se inducen solo mediante eventos privados para comprobar rollback. Las pruebas no conectan a la base del ERP ni envían mensajes ni archivos externos.

BankController revisado contiene métodos vacíos; no se certifica un CRUD de bancos inexistente ni se elimina por ello. CompanyBankAccountController es el mantenedor de cuentas efectivamente probado. Los bancos se usan como catálogo global existente. No se modifican políticas de tenantización ni claves técnicas.

Estado QA-02: parcialmente atendido, cuatro grupos adicionales con evidencia aislada. Para avanzar por bloques, siguientes: settings/uploads y ConfigUserWeb completo, más catálogos de material según consumidores. Pendientes integración HTTP/UI, permisos reales y MySQL. QA-01 conserva su lista final de descargas reales; esta entrega no declara cerrados QA-01 ni la auditoría global.
