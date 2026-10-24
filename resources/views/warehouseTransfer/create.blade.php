@extends('layouts.appAdmin2')
@section('title', 'Solicitar traslado')
@section('activeWarehouseTransfer', 'active')
@section('styles-plugins')
<link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection
@section('page-header')<div class="next-page-heading"><h1 class="page-title">Solicitar traslado</h1><p class="next-page-description">Selecciona el origen, el destino y los productos que necesitas.</p></div>@endsection
@section('page-breadcrumb')<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('warehouseTransfer.index') }}">Traslados entre almacenes</a></li><li class="breadcrumb-item active">Nuevo</li></ol>@endsection
@section('page-title')<h5 class="card-title">Nueva solicitud</h5>@endsection
@section('content')
<div id="wt-errors" class="alert alert-danger d-none" role="alert" aria-live="polite"></div>
<form method="POST" action="{{ route('warehouseTransfer.store') }}" id="wt-create" data-prefill="{{ json_encode($prefill ?? null) }}" data-url="{{ route('warehouseTransfer.store') }}" data-products-url="{{ route('warehouseTransfer.products') }}">
    @include('warehouseTransfer._context')
    <input type="hidden" name="request_key" value="{{ $requestKey }}">
    <div class="form-row">
        <div class="form-group col-md-6"><label for="source_warehouse_id">Almacén de origen</label><select class="form-control" name="source_warehouse_id" id="source_warehouse_id" required><option value="">Seleccione el origen</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }} · {{ $warehouse->branch_name ?: 'General de la empresa' }}</option>@endforeach</select></div>
        <div class="form-group col-md-6"><label for="destination_warehouse_id">Almacén de destino</label><select class="form-control" name="destination_warehouse_id" id="destination_warehouse_id" required><option value="">Seleccione el destino</option>@foreach($destinations as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }} · {{ $warehouse->branch_name ?: 'General de la empresa' }}</option>@endforeach</select></div>
    </div>
    @if($destinations->isEmpty())<div class="alert alert-warning">El local no tiene un almacén de destino disponible. Solicite su configuración antes de continuar.</div>@endif
    <div class="form-group"><label for="wt-product">Buscar producto o variante</label><select id="wt-product" class="form-control" disabled aria-describedby="wt-stock-help"><option></option></select><small id="wt-stock-help" class="form-text text-muted">Elige primero el origen. La disponibilidad mostrada corresponde a sus lotes; se comprobará nuevamente al preparar.</small></div>
    <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Producto / variante</th><th class="text-center">Disponible en origen</th><th class="text-center">Solicitar</th><th class="text-center">Acciones</th></tr></thead><tbody id="wt-products"><tr id="wt-empty"><td colspan="4" class="text-center">Agrega los productos de la solicitud.</td></tr></tbody></table></div>
    <div class="form-group"><label for="notes">Observación</label><textarea name="notes" id="notes" maxlength="2000" class="form-control" rows="2"></textarea></div>
    <p class="text-muted">Guardar la solicitud todavía no reserva existencias. El almacén de origen debe prepararla.</p>
    <button type="submit" class="btn btn-primary" {{ $destinations->isEmpty() ? 'disabled' : '' }}>Guardar solicitud</button>
    <a class="btn btn-outline-secondary" href="{{ route('warehouseTransfer.index') }}">Volver</a>
</form>
@endsection
@section('plugins')<script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>@endsection
@section('scripts')<script src="{{ asset('js/warehouseTransfer/operations.js') }}"></script>@endsection
