# Créditos — Reportes y vínculos

18/11/2026: controlador, migración de asociación y documentación coinciden; migración ejecutada lote 178; prueba de asociación repetida con éxito.

Nueva entrega: enlaces basados en relaciones estructurales y permiso/contexto; validación de filtros mensuales y fechas de exportación; tolerancia a relaciones ausentes; vencimientos proyectados en Excel. Se conserva el cálculo monetario actual y se registra la diferencia de fuentes entre advance y CreditPay como pendiente de conciliación.

No declarar PUR-01 cerrado. Se requiere QA HTTP/Excel y decisiones de conciliación y documentos privados.

Verificación: PHP lint y prueba SQLite en memoria aprobados para factura repetida con ID explícito, permiso insuficiente, origen ajeno, referencias normal/financiera, filtros inválidos mensuales/Excel y suma mensual válida. No se probó la descarga real de Excel ni HTTP completo.
