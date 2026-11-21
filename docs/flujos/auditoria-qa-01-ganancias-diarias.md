# QA-01 — Resumen y detalle diarios

19/11/2026. Redondeos del detallado verificados: métodos instalados conservan precisión y formato K:O con decimales opcionales; documento instalado idéntico; prueba de corrección aprobada. Esta evidencia no incluye apertura de XLSX real.

Inventario de consumidores: en rutas y vistas diarias revisadas no se encontró un export independiente. Se prueban DailyProfitReport y el detalle de GananciaDiariaController existentes, sin agregar una exportación por interpretación del backlog.

Prueba aislada con SQLite privado, Container/Validator/Eloquent reales y modelos instalados: once ventas válidas del día, cantidad 22, neto 990 y utilidad 770; once grupos; recálculo conserva cabecera y reemplaza detalle sin duplicados; paginación de diez más uno con el mismo total diario en ambas páginas; resumen ajeno excluido y acceso directo por ID rechazado; costo faltante deja utilidad nula; costo cero conserva utilidad; fallo simulado durante inserción del detalle revierte cabecera y detalles; descubrimiento conserva contexto previo y recálculo tras anular ventas deja total cero y elimina detalle.

Las fixtures base incluyen fechas fuera del período, USD, ventas anuladas/revertidas y otros contextos. Se ejecutan antes los casos de ganancias por usuario. La prueba llama directamente al controlador con validación real y Request::validate registrado mediante macro privada; no ejecuta router, middleware ni roles. No prueba concurrencia MySQL, bloqueos efectivos, programación real del comando, todos los atributos de productos, volúmenes grandes ni archivos XLSX. La reversión simulada afecta solo SQLite en memoria.

No fue necesario modificar lógica de aplicación para los casos probados. Estado QA-01: parcialmente atendido, evidencia automatizada diaria ampliada; pendientes HTTP/roles, despliegue y operación real, descargas existentes y Finanzas. La documentación y fixtures no son una certificación completa de producción ni de DATA-01.
