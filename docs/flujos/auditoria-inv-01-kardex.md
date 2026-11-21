# INV-01 — Kardex reutilizable y método legacy

19/11/2026. GUIDE-01 operación aplicado completo y migración verificada. Continúa pendiente QA externo documentado.

Evidencia actual: rutas Kardex protegidas con list_kardex; controlador activo aplica MaterialInventoryScope y stockItem de empresa; KardexMovementQuery une movimientos de vista SQL con despachos y recepciones de transferencias. El diagnóstico original no se extrapola al código actual.

Cambio: el servicio vuelve a comprobar contexto autorizado, producto habilitado y almacenes permitidos, incluso si el query del llamador es global. El controlador añade permiso propio y el método sin ruta kardexO rechaza 410. No se recalculan ni migran movimientos, precios o stocks; no se cambia la política de almacenes compartidos existente.

Pendientes INV-01: ItemController legacy (seis rutas), StockItemController/selectores, integridad de fuentes/relaciones de inventory_movements_view y QA HTTP con roles reales/MySQL. Pruebas aisladas no prueban conciliación histórica ni concurrencia de traslados. INV-01 parcial.

Verificación ejecutada: sintaxis de ambos PHP y catorce comprobaciones aisladas en SQLite usando los validadores reales de MaterialInventoryScope/MaterialParameters con actor y tablas de prueba. Incluye almacenes locales/compartidos, exclusión de empresa/tenant/local ajenos, vínculo inactivo, contexto ausente, plataforma, traslados parciales y método legacy 410. La fuente ordinaria es una tabla fixture, no la vista SQL de producción; no se certifica conciliación histórica ni autorización HTTP real.

