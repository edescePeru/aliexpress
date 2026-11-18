# Retiro de la ruta de totales de cotizaciones

QUOTE-04 retira cotizaciones/venta/totales y el nombre quoteSale.list.general. El destino indexGeneral no existe en QuoteSaleController. La búsqueda de consumidores en app, resources, public/js y routes del ERP local encontró únicamente la propia declaración de ruta. Los listados General y Facturadas mantienen sus rutas vigentes.

El acceso antiguo queda sujeto al enrutamiento restante del ERP, incluido el catálogo por slug. La prueba HTTP confirma el rechazo final y la ausencia de errores de método.
