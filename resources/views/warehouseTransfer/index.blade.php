@extends('layouts.appAdmin2')
@section('title', 'Traslados entre almacenes')
@section('activeWarehouseTransfer', 'active')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Inventario</span><h1 class="page-title">Traslados entre almacenes</h1><p class="next-page-description">Solicitudes, despachos y recepciones de mercancía.</p></div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li><li class="breadcrumb-item active">Traslados entre almacenes</li></ol>
@endsection
@section('page-title')
<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Traslados visibles</strong><span>Consulta solicitudes, preparación y recepciones de la empresa activa.</span></div><div class="next-toolbar-actions">
@can('create_transfer')<a class="btn btn-primary btn-sm" href="{{ route('warehouseTransfer.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Solicitar traslado</a>@endcan
</div></div>
@endsection
@section('content')
<section class="next-operational-list" aria-label="Listado de traslados">
<form method="GET" action="{{ route('warehouseTransfer.index') }}" class="next-list-toolbar">
    <div class="form-row align-items-end">
        <div class="form-group col-md-4"><label for="q">Código</label><input class="form-control" name="q" id="q" maxlength="60" placeholder="TR-000001" value="{{ $filters['q'] ?? '' }}"></div>
        <div class="form-group col-md-4"><label for="status">Estado</label><select name="status" id="status" class="form-control"><option value="">Todos</option>@foreach($labels as $key => $label)<option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="form-group col-md-4"><button class="btn btn-primary" type="submit">Buscar</button> <a class="btn btn-outline-secondary" href="{{ route('warehouseTransfer.index') }}">Limpiar</a></div>
    </div>
</form>
<div class="next-list-summary"><div class="next-list-summary-copy"><strong class="next-list-count">{{ $transfers->total() }}</strong><span>Traslados encontrados</span></div><span class="next-list-sort-context">Más recientes primero</span></div>
<div class="next-list-content"><div class="table-responsive" tabindex="0" role="region" aria-label="Traslados; desplazamiento horizontal local"><table class="table table-bordered table-hover table-sm next-data-table">
    <thead><tr><th scope="col" class="text-center">Código</th><th scope="col" class="text-left">Origen</th><th scope="col" class="text-left">Destino</th><th scope="col" class="text-center">Solicitado</th><th scope="col" class="text-center">Estado</th><th scope="col" class="text-center">Acciones</th></tr></thead>
    <tbody>@forelse($transfers as $transfer)<tr>
        <td class="text-center">{{ $transfer->code }}</td><td class="text-left">{{ optional($transfer->sourceWarehouse)->name ?: 'Almacén no disponible' }}</td><td class="text-left">{{ optional($transfer->destinationWarehouse)->name ?: 'Almacén no disponible' }}</td>
        <td class="text-center">{{ $transfer->requested_at->format('d/m/Y H:i') }}</td><td class="text-center">@include('warehouseTransfer._status', ['status'=>$transfer->status])</td>
        <td class="text-center"><div class="dropdown next-row-actions">
            <button type="button" id="wt-actions-{{ $transfer->id }}" class="btn btn-sm next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones de {{ $transfer->code }}"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button>
            <div class="dropdown-menu dropdown-menu-right next-row-actions-menu" role="menu" aria-labelledby="wt-actions-{{ $transfer->id }}"><a class="dropdown-item" role="menuitem" href="{{ route('warehouseTransfer.show', $transfer->id) }}"><i class="fas fa-eye next-row-action-item-icon" aria-hidden="true"></i>Ver traslado</a></div>
        </div></td>
    </tr>@empty<tr><td colspan="6" class="text-center">No hay traslados para los filtros y el local actuales.</td></tr>@endforelse</tbody>
</table></div></div>
<div class="next-list-pagination"><span>Mostrando {{ $transfers->firstItem() ?: 0 }} a {{ $transfers->lastItem() ?: 0 }} de {{ $transfers->total() }}</span>{{ $transfers->links() }}</div>
</section>
@endsection

@section('scripts')<script src="{{ asset('js/warehouseTransfer/operations.js') }}?v={{ filemtime(public_path('js/warehouseTransfer/operations.js')) }}"></script>@endsection
