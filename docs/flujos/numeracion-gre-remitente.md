# Numeración Nubefact de guías remitente

Platform Admin administra la serie y el correlativo inicial por empresa y RUC en la conexión Nubefact existente. El cambio se registra en la auditoría de configuración. Para GRE remitente, Nubefact requiere una serie de cuatro caracteres que empiece con T y un correlativo numérico explícito de hasta ocho dígitos (manual GRE, página 6).

El número inicial se interpreta como el primer número libre verificado en el proveedor. Al emitir, Venti360 toma el mayor entre ese número y el último correlativo guardado para la misma empresa y serie más uno. Todas las sucursales de la empresa comparten la secuencia. El bloqueo de la empresa y el índice único del bloque de contexto protegen la asignación. El número del formulario es una vista previa. La consulta de una guía anterior utiliza la identidad guardada en la propia guía.

La migración deja la configuración GRE vacía hasta que Platform Admin confirme la serie y el primer número libre. Las guías históricas sin contexto y las emitidas en el portal del proveedor se deben considerar al verificar ese número. Si otros sistemas emiten simultáneamente para el mismo RUC, deben usar una serie propia para evitar colisiones.
