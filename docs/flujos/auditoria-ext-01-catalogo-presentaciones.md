# EXT-01 — Catálogo real y presentación guardada en venta

20/11/2026. Creación local anterior verificada completa: documento instalado idéntico y suite aprobada. Se amplía en bloque con catálogo real y decisión de producto sobre presentación.

## Catálogo

Se reemplaza el catálogo simulado de la fixture anterior por PosCatalogReadService/MaterialInventoryScope/MaterialParameters instalados, con membresías y datos privados. Aprobado: MANUAL válido, producto deshabilitado en company rechazado sin guardar/enviar, variante de tenant ajeno excluida aunque apunte a material propio, local sin membresía bloqueado. Proveedor y conexión siguen simulados; no router ni permisos HTTP completos.

## Decisión y corrección

Usuario decidió representar 2 cajas de 6 como cantidad 2 y detallar unidades por caja con snapshot de venta. Código anterior elegía packs solo si conservaba material_presentation_id y describía desde catálogo actual. Venta moderna con packs y snapshot sin FK podía convertirse en cantidad 12 y perder presentación.

Se entrega ShippingGuideSaleLine para quantity/description y controlador completo que lo usa en store, saleItems e items. Packs no nulo tiene prioridad, incluso sin FK; cantidades inválidas se rechazan. Description de venta tiene prioridad, luego snapshots de presentación/unidades; catálogo solo fallback. Presentación legacy fallback valida tenant/material. Snapshot sin unidades no inventa equivalencia. Se conserva NIU y no se alteran guías ya persistidas.

Prueba propuesta aprobada: 12 unidades / 2 packs / 6 unidades por pack sin FK produce cantidad 2; descripción conserva producto y Caja original; preview y guía guardada coinciden; payload conserva 2; packs cero revierte guía y no llama proveedor. La prueba compuesta también repite MANUAL/SALE, transporte, replay, fallos y catálogo real del bloque previo.

Esquemas reducidos SQLite y actores privados, sin base ERP ni llamadas externas. No certifica todas las restricciones MySQL, documentos SUNAT, equivalencias de presentación legacy ausentes, frontend, todos los permisos ni concurrencia. Las otras rutas consumidoras de ShippingGuideSaleLine están cambiadas, pero la integración de items por HTTP no se ejecuta en esta suite.

Estado EXT-01 parcialmente atendido: corrección preparada pendiente instalación y pruebas integradas. Pendientes sandbox de proveedor, HTTP/UI y concurrencia. Los demás hallazgos mantienen sus propios estados, sin cierre global por fixtures.
