# Créditos — Referencia de operación

18/11/2026: los siete archivos de precisión coinciden, pruebas aisladas aprobadas y migración ejecutada lote 179.

Nueva entrega: referencia UUID obligatoria en registro de abonos, huella de crédito/importe/fecha/archivo, persistencia atómica de pago y referencia y replay sin duplicar. La referencia sobrevive a la eliminación del abono para impedir que un reintento lo recree. Interfaz con borrador por pestaña y bloqueo de envío.

Pendientes QA HTTP/MySQL y archivos reales. No equivale a conciliación bancaria, detección de dos operaciones distintas por igual importe ni política de sobrepagos. No borrar registros de operaciones para simular limpieza. PUR-01 parcial.

Verificación: PHP lint de controlador/migración y Blade compilado, sintaxis JavaScript y SQLite en memoria aprobados. Pruebas: replay con ID original, importe/crédito distintos, no recrear retirado, rollback de pago y referencia, reintento tras fallo y contexto. Falta concurrencia MySQL, navegador/sessionStorage y adjuntos reales.
