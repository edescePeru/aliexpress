# Registro del lote — J-MYSQL-AISLADO

**J — Concurrencia GRE en MySQL aislado.** Pasó la prueba del Store instalado con dos procesos y conexiones independientes en MySQL 8.0.31/InnoDB: misma clave/contenido produjo una sola guía y un solo envío simulado; misma clave con contenido distinto devolvió 200/409 sin envío duplicado; claves distintas generaron guías y números diferentes. Se adjuntan respuestas de cada proceso, evidencia de sincronización y hashes del código.

Se utilizó una instancia temporal separada, sin conexión ni credenciales de la base del negocio, con proveedor simulado y sin red externa. La instancia quedó detenida. Este lote no modifica la aplicación. Queda cubierta esa prueba de concurrencia; J permanece parcialmente atendido por la validación del despliegue con login/cookies y el cotejo PDF/XML/CDR reales de pruebas. No se extiende esta certificación al ciclo financiero de Créditos.


> Registro del texto entregado en ese lote. Para el estado actualizado de instalación y pruebas, consultar `AUDITOR-CIERRE-AGRUPADO-2026-11-24.md`; no acumular las frases de estado histórico como pendientes adicionales.
