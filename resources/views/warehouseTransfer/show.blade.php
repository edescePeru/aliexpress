@extends('layouts.appAdmin2')
@section('title', 'Detalle del traslado')
@section('activeWarehouseTransfer', 'active')
@section('page-header')<div class="next-page-heading"><h1 class="page-title">Traslado {{ $transfer->code }}</h1><p class="next-page-description">Seguimiento de las existencias desde el origen hasta su recepción.</p></div>@endsection
@section('page-breadcrumb')<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('warehouseTransfer.index') }}">Traslados entre almacenes</a></li><li class="breadcrumb-item active">{{ $transfer->code }}</li></ol>@endsection
@section('page-title')<h5 class="card-title">Detalle del traslado</h5><a class="btn btn-outline-secondary btn-sm float-right" href="{{ route('warehouseTransfer.index') }}">Volver al listado</a>@endsection
@section('content')
<div id="wt-errors" class="alert alert-danger d-none" role="alert" aria-live="polite"></div>
<dl class="row">
    <dt class="col-md-2">Origen</dt><dd class="col-md-4">{{ optional($transfer->sourceWarehouse)->name }}</dd>
    <dt class="col-md-2">Destino</dt><dd class="col-md-4">{{ optional($transfer->destinationWarehouse)->name }}</dd>
    <dt class="col-md-2">Estado</dt><dd class="col-md-4">@include('warehouseTransfer._status', ['status'=>$transfer->status])</dd>
    <dt class="col-md-2">Solicitado</dt><dd class="col-md-4">{{ $transfer->requested_at->format('d/m/Y H:i') }}</dd>
</dl>
@if($transfer->notes)<p><strong>Observación:</strong> {{ $transfer->notes }}</p>@endif
@if($transfer->cancellation_reason)<div class="alert alert-secondary"><strong>Motivo de cancelación:</strong> {{ $transfer->cancellation_reason }}</div>@endif
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Producto / variante</th><th class="text-center">Solicitado</th><th class="text-center">Reservado</th><th class="text-center">Despachado</th><th class="text-center">Recibido</th><th class="text-center">En tránsito</th><th class="text-center">Pendiente de recibir</th></tr></thead><tbody>
@foreach($summary['lines'] as $line)<tr><td>{{ $line['description'] }}<br><small class="text-muted">{{ $line['sku'] }}</small></td>@foreach(['requested','reserved','dispatched','received','in_transit','pending_receipt'] as $field)<td class="text-center">{{ $line[$field] }}</td>@endforeach</tr>@endforeach
</tbody></table></div>
<div class="mb-3">
@if($actions['prepare'] && $transfer->status === 'requested')
<form method="POST" action="{{ route('warehouseTransfer.prepare', $transfer->id) }}" class="wt-operation d-inline-block" data-url="{{ route('warehouseTransfer.prepare', $transfer->id) }}" data-confirm="¿Preparar este traslado y reservar todas las existencias solicitadas?">@include('warehouseTransfer._context')<button type="submit" class="btn btn-primary">Preparar traslado</button></form>
@endif
@if($actions['dispatch'] && $transfer->status === 'prepared')
<form method="POST" action="{{ route('warehouseTransfer.dispatch', $transfer->id) }}" class="wt-operation d-inline-block" data-url="{{ route('warehouseTransfer.dispatch', $transfer->id) }}" data-confirm="¿Confirmar la salida de la mercancía del almacén de origen?">@include('warehouseTransfer._context')<button type="submit" class="btn btn-primary">Despachar traslado</button></form>
@endif
</div>
@if($actions['receive'] && in_array($transfer->status, ['dispatched','partially_received'], true))
<h2 class="h5">Registrar recepción</h2>
<p>Indica cuánto llegó ahora. Deja cero en los productos que siguen pendientes.</p>
<form method="POST" action="{{ route('warehouseTransfer.receive', $transfer->id) }}" id="wt-receive" data-url="{{ route('warehouseTransfer.receive', $transfer->id) }}" data-confirm="¿Confirmar las cantidades recibidas en el destino?">
    @include('warehouseTransfer._context')<input type="hidden" name="request_key" value="{{ $receiptKey }}">
    <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Producto / variante</th><th class="text-center">En tránsito</th><th class="text-center">Recibe ahora</th></tr></thead><tbody>
    @foreach($summary['lines'] as $line)
    @php($unitLine = $transfer->lines->firstWhere('id', $line['transfer_line_id']))
    <tr><td>{{ $line['description'] }}</td><td class="text-center">{{ $line['in_transit'] }}</td><td class="text-center"><label class="sr-only" for="receive-{{ $line['transfer_line_id'] }}">Cantidad recibida de {{ $line['description'] }}</label><input type="number" id="receive-{{ $line['transfer_line_id'] }}" class="form-control text-center wt-receive-quantity" data-line-id="{{ $line['transfer_line_id'] }}" min="0" max="{{ $line['in_transit'] }}" step="{{ $unitLine->tracks_items ? '1' : '0.01' }}" value="0" {{ bccomp($line['in_transit'], '0', 2) === 0 ? 'disabled' : '' }}></td></tr>
    @endforeach
    </tbody></table></div>
    <div class="form-group"><label for="receipt-notes">Observación de recepción</label><textarea class="form-control" id="receipt-notes" name="notes" rows="2" maxlength="2000"></textarea></div>
    <button class="btn btn-primary" type="submit">Guardar recepción</button>
</form>
@endif
@if($actions['cancel'] && in_array($transfer->status, ['requested','prepared'], true))
<hr><form method="POST" action="{{ route('warehouseTransfer.cancel', $transfer->id) }}" class="wt-operation" data-url="{{ route('warehouseTransfer.cancel', $transfer->id) }}" data-confirm="¿Cancelar este traslado? Se liberarán únicamente sus reservas.">
@include('warehouseTransfer._context')<div class="form-group"><label for="reason">Motivo de cancelación</label><textarea id="reason" class="form-control" name="reason" maxlength="2000" rows="2" required></textarea></div><button class="btn btn-outline-danger" type="submit">Cancelar traslado</button>
</form>
@endif
<hr><h2 class="h5">Historial de recepciones</h2>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th class="text-center">Recepción</th><th class="text-center">Fecha</th><th>Productos / cantidades</th><th>Observación</th></tr></thead><tbody>
@forelse($transfer->receipts as $receipt)<tr><td class="text-center">{{ $receipt->id }}</td><td class="text-center">{{ $receipt->received_at->format('d/m/Y H:i') }}</td><td>@foreach($receipt->lines as $receivedLine){{ optional($receivedLine->allocation->line)->description_snapshot }}: {{ $receivedLine->quantity }}<br>@endforeach</td><td>{{ $receipt->notes ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="text-center">Todavía no hay recepciones.</td></tr>@endforelse
</tbody></table></div>
@endsection
@section('scripts')<script src="{{ asset('js/warehouseTransfer/operations.js') }}"></script>@endsection
