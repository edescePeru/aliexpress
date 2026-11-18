# Detalle de entradas de compra

La ruta entry.purchase.show utiliza el permiso list_entryPurchase. El controlador resuelve el ID por tenant y empresa activos antes de cargar la vista. La consulta conserva la exclusión de entradas eliminadas y el alcance de empresa entre sucursales. La descarga directa de adjuntos se revisa en DOC-01.
