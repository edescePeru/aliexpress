# Permiso y traslados en Kardex

El módulo operativo usa list_kardex para la pantalla, los selectores y la consulta de movimientos. Los filtros de tenant, empresa y almacén siguen en los controladores.

## Diseño de integración de traslados

| Evento | Origen | Destino |
| --- | --- | --- |
| Preparar y reservar | Sin movimiento físico | Sin movimiento físico |
| Despachar | Salida por asignación y lote, en dispatched_at | Mercancía en tránsito |
| Recibir | La salida original permanece | Entrada por cada detalle de recepción en la fecha de su cabecera |
| Cancelar antes del despacho | Liberación de reserva | Sin movimiento físico |

Usar warehouse_transfer_allocations para el despacho y warehouse_transfer_receipt_lines con su cabecera para cada recepción. La trazabilidad se basa en las cantidades registradas en cada operación y en base_unit_cost de la asignación. Validar que el tenant y la empresa coincidan en los joins, conservar los almacenes y ubicaciones de origen y destino, y ordenar por fecha e identidad estable. Verificar moneda base antes de mezclar el costo histórico de traslados con otros movimientos.

Caso de aceptación: con 10 unidades iniciales en origen, despachar 6 y recibir 4. El origen queda en 4, el destino gana 4 y quedan 2 en tránsito. La segunda recepción suma 2 al destino. Reintentar cualquiera de las recepciones conserva el mismo saldo. Consultar ambos almacenes durante el tránsito refleja la mercancía físicamente disponible.

inventory_movements_view también alimenta InventoryCostService. Una salida y una entrada de traslado cambian el saldo operativo durante el tránsito y pueden afectar el costo promedio de empresa si se incorporan a la vista compartida. Preparar primero una consulta de movimientos exclusiva del Kardex, reutilizando la vista para compras y salidas y agregando los eventos de traslado. Reconciliar casos reales antes de cambiar la fuente del costo promedio.
