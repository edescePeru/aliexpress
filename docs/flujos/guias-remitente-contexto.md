# Guías de remisión remitente

Cada guía nueva registra el tenant, la empresa y la sucursal de la sesión. El listado, el detalle, la consulta a Nubefact y la exportación Excel usan ese mismo contexto. Los clientes pertenecen al tenant. Las ventas de origen se buscan en la sucursal activa y los productos manuales se validan contra los StockItems habilitados de la empresa. Los catálogos de documento, motivo, peso e indicadores se comparten. La unidad de peso se restringe a KGM y TNE. El ubigeo se valida contra los distritos registrados.

La migración conserva las guías históricas con contexto pendiente. Quedan fuera de las vistas operativas hasta verificar a qué emisor y local pertenecen. El máximo histórico de TTT1 se usa como piso conservador del correlativo. El correlativo se genera en servidor con un bloqueo por empresa y un índice único por tenant, empresa, tipo, serie y número. La serie TTT1 sigue fija en este bloque.

La emisión y la consulta usan la conexión Nubefact de la empresa emisora. La guía y su identidad quedan guardadas antes de enviar la solicitud. Si el envío queda incierto se conserva el estado SENT («Por confirmar») y se puede consultar desde el listado. Esta etapa conserva el flujo existente de respuesta del proveedor.

Quedan pendientes la serie configurable de guías, la conciliación completa de resultados inciertos y los mantenedores globales en Platform Admin. La primera emisión debe probarse con la conexión y serie del emisor.
