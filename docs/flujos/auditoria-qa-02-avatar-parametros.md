# QA-02 — Fallo reproducido de avatar y persistencia de parámetros

20/11/2026. Paquete usuarios/materiales instalado completo: documentación idéntica y tres pruebas aprobadas nuevamente.

## Avatar

Se reprodujo en controlador instalado y archivos privados: update reemplazaba el archivo de nombre ID.png antes de confirmar User; un evento de persistencia fallido devolvía 422 y revertía la base, pero no conservaba los bytes originales. La prueba se realizó con public_path redirigido al runtime y SQLite privado, sin afectar ERP.

Se entrega ConfigUserWebController completo y UserAvatarFiles. Solo se altera manejo de imagen en update: nombre aleatorio nuevo, extensión MIME, conservar anterior hasta commit, descartar nuevo sin referencias ante rollback y retirar anterior compatible sin referencias después del commit. Se preservan validación de Request, operaciones de usuario y sincronización de permisos/membresías. Limpieza usa patrón de archivo propio, comprobación de ruta y consulta sin scopes de referencias; no elimina default, archivos ajenos ni rutas arbitrarias. Limpieza fallida se reporta, sin convertir commit válido en rollback ficticio.

Prueba propuesta aprobada con modelos/controlador/servicio y archivos privados reales: conserva referencia y bytes tras fallo, no deja archivo nuevo tras rollback, genera nombre aleatorio tras éxito, retira legacy propio después del commit y conserva archivo compartido. La fixture también repite altas/edición sin imagen. No prueba avatar del propietario por HTTP ni carreras concurrentes; una caída abrupta puede dejar archivo huérfano. No se afirma atomicidad de base+filesystem frente a fallos del proceso.

## Parámetros de materiales

Código instalado probado sin cambios: crear con dependencias, actualizar fila sin duplicar, rechazar versión anterior sin guardar, revertir fallo de persistencia y rechazar empresa sin membresía. SQLite privado y respuesta de redirección simulada, sin HTTP/UI/concurrencia.

## Estado

QA-02 parcialmente atendido: corrección de avatar preparada y aprobada en fixture, instalación y validación real pendientes. No se certifican todos los CRUD de catálogos ni toda la concurrencia. Agrupar comprobación integrada final de mantenedores y documentar los casos sin evidencia antes de pasar al siguiente hallazgo; no presentar los pendientes de QA-01/DATA-01 como resueltos.
