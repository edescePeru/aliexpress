# Créditos — Comprobantes autorizados

18/11/2026: faltante savePaysCredit corregido; controlador coincide con la entrega de reintentos. Protección de lectura de comprobantes por IDs con contexto y permisos, nuevos uploads privados y bloqueo Apache del árbol público legacy. Sin mover, borrar ni reasignar historia.

La privacidad legacy solo se certifica tras comprobar respuesta denegada de URLs antiguas en el servidor real. La aplicación puede servir el fichero local antiguo a usuarios autorizados, pero un .htaccess ignorado mantendría exposición directa. La revisión general DOC-01 no se declara cerrada por este ajuste limitado a comprobantes de créditos.

QA HTTP y servidor pendiente; no confundir controles de PHP con protección de estáticos. PUR-01 parcial.

Verificación: pruebas de reintentos contra el controlador instalado aprobadas. Pruebas SQLite/archivos temporales del lector aprobadas para fichero privado/legacy, contexto ajeno, rutas inválidas, tipos y cabeceras privadas. PHP/JS y ambas vistas compiladas correctos. No se probaron aún el bloqueo Apache de estáticos, las rutas HTTP completas ni las cargas reales a almacenamiento privado.
