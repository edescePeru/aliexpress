# INV-01 — Lotes y extremos de traslados en lectura

19/11/2026. Costos aplicado: servicio y documentación coinciden, pruebas aisladas pasan.

KardexMovementQuery comprueba la cadena asignación/lote de origen/producto/extremo de salida y los dos almacenes/ubicaciones de la empresa. Cada recepción valida lote de destino y fecha. El extremo consultado conserva el filtro de local; el extremo contraparte solo exige pertenencia a la empresa, permitiendo traslados entre locales.

No usa existencia actual del lote ni detail_entry_id para fabricar cantidades o excluir lotes de traslados anteriores. Si origen inválido se excluye asignación; si solo recepción inválida se conserva despacho válido. No se cambian escritores, costos, reservas ni estados.

Pendientes: homologación de fórmula y traslados en costos de empresa, multilote/moneda histórica y QA HTTP/transaccional. La mutabilidad de ubicación de un lote por procesos externos debe revisarse si existe; no se inventan snapshots. INV-01 parcial.

Verificación ejecutada: sintaxis PHP y pruebas SQLite de origen/destino incompatibles, extremos, estados, fecha, lote sin compra original y recepciones parciales. La muestra MySQL comparativa devolvió tres movimientos antes y después con campos idénticos; incluía un despacho y ninguna recepción visible. Los ingresos parciales positivos se probaron en SQLite, no se presentan como muestra positiva MySQL. Sin escrituras. Pendientes HTTP/roles, concurrencia y conciliación del resto de datos.

