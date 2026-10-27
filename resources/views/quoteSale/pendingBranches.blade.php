@extends('layouts.appAdmin2')

@section('openQuote') menu-open @endsection
@section('activeQuote') active @endsection
@section('activeGeneralQuote') active @endsection
@section('title') Cotizaciones pendientes de asignación @endsection
@section('page-header')
    <h1 class="page-title">Cotizaciones pendientes de asignación</h1>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Histórico — sin sucursal</strong><span>Consulta de registros de la empresa activa.</span></div><div class="next-toolbar-actions"><a href="{{ route('quoteSale.index') }}" class="btn btn-outline-secondary btn-sm">Volver al listado del local</a></div></div>
@endsection
@section('content')
    <div class="alert alert-warning" role="status">
        Estas cotizaciones no tienen sucursal asignada. Se muestran para consulta dentro de la empresa actual.
        La asignación requiere revisar cada caso y sus reservas; no se realiza desde esta pantalla.
    </div>
    <form method="GET" action="{{ route('quoteSale.pendingBranches') }}" class="next-list-toolbar mb-3">
        <label for="pending-search">Código o descripción</label>
        <div class="input-group next-list-search-control">
            <input id="pending-search" type="text" name="q" maxlength="150" class="form-control" value="{{ $search }}" placeholder="Buscar cotización histórica">
            <div class="input-group-append"><button type="submit" class="btn btn-primary">Buscar</button></div>
        </div>
    </form>
    <p>{{ $quotes->total() }} cotizaciones pendientes de asignación.</p>
    <div class="table-responsive" tabindex="0" role="region" aria-label="Cotizaciones históricas">
        <table class="table table-bordered table-hover table-sm next-data-table">
            <thead><tr><th scope="col" class="text-center">ID</th><th scope="col" class="text-center">Código</th><th scope="col" class="text-left">Descripción</th><th scope="col" class="text-center">Fecha</th><th scope="col" class="text-center">Estado</th><th scope="col" class="text-center">Sucursal</th><th scope="col" class="text-center">Acciones</th></tr></thead>
            <tbody>
            @forelse ($quotes as $quote)
                <tr>
                    <td class="text-center">{{ $quote->id }}</td><td class="text-center">{{ $quote->code }}</td><td class="text-left">{{ $quote->description_quote }}</td>
                    <td class="text-center text-nowrap">{{ $quote->date_quote ? \Carbon\Carbon::parse($quote->date_quote)->format('d/m/Y') : '—' }}</td>
                    <td class="text-center">{{ $quote->state }}</td><td class="text-center"><span class="badge badge-warning">Histórico — sin sucursal</span></td>
                    <td class="text-center text-nowrap">
                        <a href="{{ route('quoteSale.pendingBranches.show', ['id' => $quote->id]) }}" class="btn btn-outline-info btn-sm">Ver</a>
                        @can('printCustomer_quote')
                            <a href="{{ url('dashboard/imprimir/cotizacion/cliente/'.$quote->id) }}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">Imprimir</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No se encontraron cotizaciones pendientes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $quotes->links() }}
@endsection
