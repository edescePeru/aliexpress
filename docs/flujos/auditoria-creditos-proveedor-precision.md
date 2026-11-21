# Créditos — Precisión monetaria

18/11/2026: cinco archivos de saldo coinciden y pruebas aisladas aprobadas. La base local mantiene credit_pays vacío.

Nueva entrega: migración de FLOAT a DECIMAL(12,2) exclusivamente para tabla vacía, validación de importe original, cast decimal y aritmética interna en centavos. El JSON de reportes conserva números para no cambiar el contrato de la interfaz. No migrar historia ni redondear importes automáticamente.

La regla de fuente única sigue vigente: solo abonos. Adelantos antiguos, marcas, sobrepagos/duplicados y privacidad documental siguen separados. No declarar cerrado PUR-01.

Validación: PHP lint y prueba SQLite en memoria aprobados para decimales exactos, límites, rechazo de precisión adicional, cast y saldo en centavos. Doctrine generó en modo pretend el ALTER NUMERIC(12,2) sobre la conexión MySQL local, sin aplicarlo. No se certifica todavía almacenamiento real, concurrencia ni descarga Excel tras despliegue.
