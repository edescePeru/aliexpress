# Compras de Finanzas

Las vistas de facturas activas y anuladas usan entradas finance=true de la empresa activa. Las órdenes de servicio sueltas quedan fuera del listado principal. Las entradas sin empresa quedan pendientes de conciliación histórica.

Las categorías pertenecen a la empresa. Proveedores y unidades se consultan en su catálogo de tenant. Las cajas pertenecen a la empresa y al local actual o son compartidas. El pago usa la moneda del comprobante, una sesión propia abierta en el local y un subtipo inmediato compatible cuando corresponde.

Cada línea acepta importe final y afectación gravada al 18 % incluido, exonerada o inafecta. Se conserva la afectación y el IGV calculado en servidor. El egreso usa el total bruto validado. Los detalles históricos sin desglose conservan el cálculo anterior. La edición conserva el importe y moneda contabilizados. Para cambiarlos se anula y se vuelve a registrar la factura.

Al anular, se registra un ingreso ligado al egreso en una sesión propia abierta de la misma caja y local. La factura y su detalle se conservan. Egresos históricos sin contexto o con vínculos ambiguos requieren conciliación.
