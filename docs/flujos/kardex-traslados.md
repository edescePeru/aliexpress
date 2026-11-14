# Movimientos de traslado en Kardex

La consulta combina entradas y salidas de la vista existente con un egreso por asignación despachada y un ingreso por detalle de recepción. Los tres orígenes se filtran por tenant, empresa, producto y almacenes autorizados. La valoración usa el costo base histórico de la asignación. Si la moneda histórica difiere de la base actual, el reporte pide revisión para evitar mezclar importes.

La vista compartida y el servicio de costo promedio de empresa conservan su fuente actual. El precio de venta sigue su lista de precios de sucursal. Durante el tránsito, las unidades ya salieron del origen y entran al destino conforme se reciben.
