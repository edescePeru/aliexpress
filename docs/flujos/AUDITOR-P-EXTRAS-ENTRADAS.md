# Registro del lote — P-EXTRAS-ENTRADAS

**P — Adjuntos extras de Entradas: carga, edición y eliminación.** Se preparó la validación de permisos efectivos y pertenencia a una Entrada normal del tenant/empresa autorizados. Se rechazan IDs contradictorios entre dirección y formulario. La carga valida formato y correspondencia de archivos, usa nombres únicos y revierte registros/archivos nuevos ante fallos verificables. La eliminación física se intenta después del commit y conserva archivos compartidos o cuya limpieza sea incierta.

Pasaron sintaxis PHP 7.3 y pruebas aisladas del servicio y controlador, incluyendo aislamiento, permisos, archivos falsos, fallos de persistencia y conservación de bytes anteriores. Pendiente de instalación y pruebas HTTP/CSRF, escritura del servidor y concurrencia MySQL. P permanece parcialmente atendido por los escritores y verificaciones integradas restantes; no se ejecuta limpieza global de históricos.


> Registro del texto entregado en ese lote. Para el estado actualizado de instalación y pruebas, consultar `AUDITOR-CIERRE-AGRUPADO-2026-11-24.md`; no acumular las frases de estado histórico como pendientes adicionales.
