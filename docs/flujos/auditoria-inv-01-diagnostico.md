# INV-01 — Diagnóstico antes de conciliación monetaria

19/11/2026. traslados-lectura aplicado completo: KardexMovementQuery y docs coinciden; sintaxis y pruebas aisladas aprobadas.

Se ejecutaron nueve consultas de solo lectura: confirmación tenant/empresa y ocho comprobaciones de multilote, pertenencia, duplicación e inventario actual/origen. Local T1/C1: cero filas en las ocho comprobaciones. Se probaron casos positivos SQLite y ausencia de modificaciones de tablas fuente. Se entrega SQL para repetirlo en el servidor; no es una migración.

No se cierra INV-01 a partir de resultados locales. Falta resultado del servidor, QA HTTP/roles/transacciones y decisión de homologación monetaria. Se conserva promedio de empresa; no se concluye que falte sumar traslados internos: podrían ser neutros en empresa y distintos en Kardex por almacén. Definir tránsito y fuente de costos antes de implementarlo.

Los algoritmos previos de costo por material/producto difieren en valoración de salidas. Requieren decisión y pruebas de historia, no un cambio silencioso. Si aparecen detalles multilote, no reconstruir la distribución histórica a partir del stock actual. Preparar correcciones específicas con evidencia.
