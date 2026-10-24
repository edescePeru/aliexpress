@extends('layouts.appAdmin2')
@section('openQuote') menu-open @endsection
@section('activeQuote') active @endsection
@section('activeGeneralQuote') active @endsection
@section('title') Cotización pendiente de asignación @endsection
@section('page-header')
    <h1 class="page-title">Cotización {{ $quote->code }}</h1>
@endsection
@section('page-title')
    <h5 class="card-title">Consulta de histórico</h5>
    <a href="{{ route('quoteSale.pendingBranches') }}" class="btn btn-outline-secondary btn-sm float-right">Volver a pendientes</a>
@endsection
@section('content')
    <div class="alert alert-warning" role="status">Pendiente de asignación de sucursal. Revise su origen y sus reservas antes de habilitar operaciones.</div>
    <dl class="row">
        <dt class="col-sm-3">Código</dt><dd class="col-sm-9">{{ $quote->code }}</dd>
        <dt class="col-sm-3">Descripción</dt><dd class="col-sm-9">{{ $quote->description_quote }}</dd>
        <dt class="col-sm-3">Fecha</dt><dd class="col-sm-9">{{ $quote->date_quote ? \Carbon\Carbon::parse($quote->date_quote)->format('d/m/Y') : '—' }}</dd>
        <dt class="col-sm-3">Estado</dt><dd class="col-sm-9">{{ $quote->state }}</dd>
    </dl>
    <h6>Productos registrados</h6>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead><tr><th>Detalle</th><th>StockItem</th><th>Producto / variante</th><th>Cantidad cotizada</th></tr></thead>
            <tbody>
            @forelse ($lines as $line)
                <tr><td>{{ $line->id }}</td><td>{{ $line->stock_item_id }}</td><td>{{ $line->display_name ?: 'Producto histórico no disponible' }}</td><td>{{ $line->quantity }}</td></tr>
            @empty
                <tr><td colspan="4">No hay productos registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p>Las cantidades cotizadas no certifican stock disponible ni reservas vigentes. Use el diagnóstico de reservas para revisar la trazabilidad.</p>
@endsection
