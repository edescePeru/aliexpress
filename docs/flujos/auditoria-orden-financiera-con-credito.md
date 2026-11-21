# Orden financiera con crédito — Regla confirmada

18/11/2026: el usuario confirma bloquear importes, detalles y eliminación al existir crédito, incluso sin abonos. Se mantiene deuda y pagos intactos. Se bloquea también restauración con crédito por requerir revisar la composición que se recupera.

Guardia en modelos financieros, comparación monetaria en centavos y transacciones/bloqueo de orden compartido con la creación de crédito. Edición de metadatos sin cambios financieros permanece permitida. No se habilita excepción por rol ni una cancelación implícita de deuda.

Antecedente: comprobantes instalados, lector validado y URLs estáticas de muestra denegadas 403 en Apache local; QA multiusuario y cargas reales permanece pendiente.

No cerrar PUR-01 hasta pruebas HTTP/MySQL y restantes referencias/consumidores. No declarar protegido SQL directo o actualizaciones masivas que no disparen eventos.

Validación: PHP lint y SQLite en memoria aprobados para campos financieros, alta/edición/retiro de detalles, eliminación completa con rollback, crédito pagado, restauración, metadatos sin cambio monetario, igualdad 100/100.00 y conservación de crédito/abono. Flujo sin crédito mantiene modificación transaccional. QA HTTP/MySQL de concurrencia pendiente.
