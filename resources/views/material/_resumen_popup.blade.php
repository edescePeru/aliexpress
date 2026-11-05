<p class="text-muted">Alertas del local actual, por StockItem y almacén. El stock general y los excedentes de otro almacén no compensan faltantes.</p>
<div class="table-responsive">
<table class="table table-sm table-striped mb-0">
    <thead><tr>
        <th>Material / variante / SKU</th>
        <th>Almacén</th>
        <th class="text-right">Físico</th>
        <th class="text-right">Reservado</th>
        <th class="text-right">Disponible</th>
        <th class="text-right">Mínimo</th>
        <th>Estado</th>
    </tr></thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td>{{ $row->full_name }}<br><small>{{ $row->variant_text }} {{ $row->stock_item_sku }}</small></td>
            <td>{{ $row->warehouse_name }}</td>
            <td class="text-right">{{ number_format($row->qty_on_hand, 2) }}</td>
            <td class="text-right">{{ number_format($row->qty_reserved, 2) }}</td>
            <td class="text-right">{{ number_format($row->qty_available, 2) }}</td>
            <td class="text-right">{{ number_format($row->min_alert, 2) }}</td>
            <td>
                @if($row->estado === 'desabastecido')
                    <span class="badge badge-danger">Sin disponibilidad</span>
                @else
                    <span class="badge badge-warning">Por agotarse</span>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center">No hay alertas en los almacenes del local actual.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
