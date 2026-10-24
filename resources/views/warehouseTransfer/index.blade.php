@extends('layouts.appAdmin2')
@section('title', 'Traslados entre almacenes')
@section('activeWarehouseTransfer', 'active')
@section('page-header')
<div class="next-page-heading"><h1 class="page-title">Traslados entre almacenes</h1><p class="next-page-description">Solicitudes, despachos y recepciones de mercancía.</p></div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li><li class="breadcrumb-item active">Traslados entre almacenes</li></ol>
@endsection
@section('page-title')
<h5 class="card-title">Listado de traslados</h5>
@can('create_transfer')<a class="btn btn-primary btn-sm float-right" href="{{ route('warehouseTransfer.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Solicitar traslado</a>@endcan
@endsection
@section('content')
<form method="GET" action="{{ route('warehouseTransfer.index') }}" class="mb-3">
    <div class="form-row align-items-end">
        <div class="form-group col-md-4"><label for="q">Código</label><input class="form-control" name="q" id="q" maxlength="60" placeholder="TR-000001" value="{{ $filters['q'] ?? '' }}"></div>
        <div class="form-group col-md-4"><label for="status">Estado</label><select name="status" id="status" class="form-control"><option value="">Todos</option>@foreach($labels as $key => $label)<option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="form-group col-md-4"><button class="btn btn-primary" type="submit">Buscar</button> <a class="btn btn-outline-secondary" href="{{ route('warehouseTransfer.index') }}">Limpiar</a></div>
    </div>
</form>
<p>{{ $transfers->total() }} traslados encontrados.</p>
<div class="table-responsive"><table class="table table-bordered table-hover">
    <thead><tr><th class="text-center">Código</th><th>Origen</th><th>Destino</th><th class="text-center">Solicitado</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr></thead>
    <tbody>@forelse($transfers as $transfer)<tr>
        <td class="text-center">{{ $transfer->code }}</td><td>{{ optional($transfer->sourceWarehouse)->name ?: 'Almacén no disponible' }}</td><td>{{ optional($transfer->destinationWarehouse)->name ?: 'Almacén no disponible' }}</td>
        <td class="text-center">{{ $transfer->requested_at->format('d/m/Y H:i') }}</td><td class="text-center">@include('warehouseTransfer._status', ['status'=>$transfer->status])</td>
        <td class="text-center"><a class="btn btn-outline-primary btn-sm" href="{{ route('warehouseTransfer.show', $transfer->id) }}">Ver traslado</a></td>
    </tr>@empty<tr><td colspan="6" class="text-center">No hay traslados para los filtros y el local actuales.</td></tr>@endforelse</tbody>
</table></div>
{{ $transfers->links() }}
@endsection
