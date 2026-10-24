@extends('layouts.appAdmin2')

@section('openQuote') menu-open @endsection
@section('activeQuote') active @endsection
@section('activeGeneralQuote') active @endsection
@section('title') Cotizaciones pendientes de asignación @endsection
@section('page-header')
    <h1 class="page-title">Cotizaciones pendientes de asignación</h1>
@endsection
@section('page-title')
    <h5 class="card-title">Históricos de la empresa actual</h5>
    <a href="{{ route('quoteSale.index') }}" class="btn btn-outline-secondary btn-sm float-right">Volver al listado del local</a>
@endsection
@section('content')
    <div class="alert alert-warning" role="status">
        Estas cotizaciones no tienen sucursal asignada. Se muestran para consulta dentro de la empresa actual.
        La asignación requiere revisar cada caso y sus reservas; no se realiza desde esta pantalla.
    </div>
    <form method="GET" action="{{ route('quoteSale.pendingBranches') }}" class="mb-3">
        <label for="pending-search">Código o descripción</label>
        <div class="input-group">
            <input id="pending-search" type="text" name="q" maxlength="150" class="form-control" value="{{ $search }}" placeholder="Buscar cotización histórica">
            <div class="input-group-append"><button type="submit" class="btn btn-primary">Buscar</button></div>
        </div>
    </form>
    <p>{{ $quotes->total() }} cotizaciones pendientes de asignación.</p>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead><tr><th>ID</th><th>Código</th><th>Descripción</th><th>Fecha</th><th>Estado</th><th>Sucursal</th><th>Acciones</th></tr></thead>
            <tbody>
            @forelse ($quotes as $quote)
                <tr>
                    <td>{{ $quote->id }}</td><td>{{ $quote->code }}</td><td>{{ $quote->description_quote }}</td>
                    <td>{{ $quote->date_quote ? \Carbon\Carbon::parse($quote->date_quote)->format('d/m/Y') : '—' }}</td>
                    <td>{{ $quote->state }}</td><td><span class="badge badge-warning">Pendiente de asignación</span></td>
                    <td>
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
