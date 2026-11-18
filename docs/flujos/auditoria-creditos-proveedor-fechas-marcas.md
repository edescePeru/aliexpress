# Créditos — Fechas y marcas

Verificación del paquete previo: controlador y documentación coinciden, sintaxis correcta. Nueva entrega completa del controlador con validación de fechas, bloqueo/transacción en edición, ampliación y marcas de pago; cálculo de vencimiento compartido; comprobantes nuevos validados y sin sobrescribir nombres fijos; anulación sin borrar archivos ni abonos.

state_credit=paid_out y state_pay=canceled conservan sus flujos separados. Se impide anular solo paid_out si también hay canceled para no aparentar una reversión completa. La conciliación monetaria no está resuelta por estas marcas. No declarar cerrado PUR-01.

Pruebas aisladas SQLite aprobadas: fechas imposibles/invertidas, días calculados por servidor, ampliación de siete días, marca de pago, rechazo de repetición y doble marca, conservación de comprobante/referencia y abonos, rechazo sin factura y nivel de transacción cero tras los errores. También pasaron las pruebas previas de contexto, permisos y abonos. PHP lint correcto. Archivos reales y concurrencia MySQL pendientes.
