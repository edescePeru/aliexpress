@extends('layouts.appAdmin2')
@section('openQuote') menu-open @endsection
@section('activeQuote') active @endsection
@section('activeGeneralQuote') active @endsection
@section('title') Cotización pendiente de asignación @endsection
@section('page-header')
    <h1 class="page-title">Cotización {{ $quote->code }}</h1>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Histórico — sin sucursal</strong><span>Consulta de registros de la empresa activa.</span></div><div class="next-toolbar-actions"><a href="{{ route('quoteSale.pendingBranches') }}" class="btn btn-outline-secondary btn-sm">Volver a históricos</a></div></div>
@endsection
@section('content')
    <div class="alert alert-warning" role="status">Pendiente de asignación de sucursal. Revise su origen y sus reservas antes de habilitar operaciones.</div>
    <div class="next-form-section">
    <dl class="row mb-0">
        <dt class="col-sm-3">Código</dt><dd class="col-sm-9">{{ $quote->code }}</dd>
        <dt class="col-sm-3">Descripción</dt><dd class="col-sm-9">{{ $quote->description_quote }}</dd>
        <dt class="col-sm-3">Fecha</dt><dd class="col-sm-9">{{ $quote->date_quote ? \Carbon\Carbon::parse($quote->date_quote)->format('d/m/Y') : '—' }}</dd>
        <dt class="col-sm-3">Estado</dt><dd class="col-sm-9">{{ $quote->state }}</dd>
    </dl>
    </div>
    <div class="next-list-toolbar"><div class="next-section-header mb-0"><h2>Productos registrados</h2></div></div>
    <div class="table-responsive" tabindex="0" role="region" aria-label="Cotizaciones históricas">
        <table class="table table-bordered table-hover table-sm next-data-table">
            <thead><tr><th scope="col" class="text-center">Detalle</th><th scope="col" class="text-center">StockItem</th><th scope="col" class="text-left">Producto / variante</th><th scope="col" class="text-center">Cantidad cotizada</th></tr></thead>
            <tbody>
            @forelse ($lines as $line)
                <tr><td class="text-center">{{ $line->id }}</td><td class="text-center">{{ $line->stock_item_id }}</td><td class="text-left">{{ $line->display_name ?: 'Producto histórico no disponible' }}</td><td class="text-center">{{ $line->quantity }}</td></tr>
            @empty
                <tr><td colspan="4">No hay productos registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="next-form-section mb-0">Las cantidades cotizadas no certifican stock disponible ni reservas vigentes. Use el diagnóstico de reservas para revisar la trazabilidad.</p>
@endsection
