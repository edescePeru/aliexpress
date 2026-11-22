@extends('layouts.appAdmin2')
@section('activeAdminFinancialSummary', 'active')
@section('title', 'Movimientos del resumen financiero')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Caja · consulta</span><h1 class="page-title">Movimientos del resumen</h1><p class="next-page-description">{{ $box->name }} · {{ $box->currency ?: 'Sin moneda' }}</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Listado operativo</strong><span>{{ $branchId === null ? 'Todas las sucursales de la empresa' : 'Sucursal #'.$branchId }} · Solo lectura</span></div><div class="next-toolbar-actions flex-wrap"><a class="btn btn-outline-secondary btn-sm" href="{{ $summaryBackUrl }}">Volver al resumen</a></div></div>
@endsection
@section('content')
<div id="summary-movements" data-url="{{ $summaryListUrl }}">
    <form id="summary-filters" class="next-list-toolbar">
        <div class="row align-items-end">
            <div class="col-sm-6 col-lg-4 form-group"><label for="q">Descripción</label><input class="form-control" id="q" name="q" type="search" placeholder="Buscar descripción…"></div>
            <div class="col-sm-6 col-lg-4 form-group"><label for="session_id">Sesión</label><select id="session_id" name="session_id" class="form-control"><option value="">Todas las sesiones</option>@foreach($sessionOptions as $session)<option value="{{ $session->id }}" {{ ($data['session_id'] ?? null) == $session->id ? 'selected' : '' }}>#{{ $session->id }} · {{ optional($session->user)->name }} · {{ optional($session->branch)->name }} · {{ $session->status ? 'Abierta' : 'Cerrada' }} · {{ $session->opening_time }}</option>@endforeach</select></div>
            <div class="col-sm-6 col-lg-4 form-group"><label for="type">Tipo</label><select id="type" name="type" class="form-control"><option value="">Todos los tipos</option><option value="sale">Venta</option><option value="income">Ingreso</option><option value="expense">Egreso</option></select></div>
            <div class="col-sm-6 col-lg-4 form-group"><label for="subtype_id">Subtipo</label><select id="subtype_id" name="subtype_id" class="form-control"><option value="">Todos los subtipos</option><option value="none">Sin subtipo</option>@foreach($subtypes as $st)<option value="{{ $st->id }}">{{ $st->name }}</option>@endforeach</select></div>
            <div class="col-sm-6 col-lg-3 form-group"><label for="date_from">Desde</label><input id="date_from" name="date_from" type="date" class="form-control" value="{{ $data['date_from'] ?? '' }}"></div>
            <div class="col-sm-6 col-lg-3 form-group"><label for="date_to">Hasta</label><input id="date_to" name="date_to" type="date" class="form-control" value="{{ $data['date_to'] ?? '' }}"></div>
            <div class="col-sm-6 col-lg-2 form-group"><button class="btn btn-primary" id="btn-search" type="submit">Buscar</button></div>
        </div>
    </form>
    <div class="next-form-section d-none" id="summary-error" role="alert"></div>
    <div class="next-list-toolbar d-flex flex-wrap justify-content-between"><span id="textPagination" aria-live="polite">Cargando movimientos…</span><span class="text-muted">Importes en {{ $box->currency ?: 'moneda no registrada' }}</span></div>
    <div class="table-responsive" tabindex="0" aria-label="Movimientos; desplazamiento horizontal">
        <table class="table table-bordered table-hover table-sm next-data-table"><thead><tr>
            <th class="text-center">ID</th><th class="text-center">Fecha</th><th>Usuario / sesión</th><th>Caja</th><th class="text-center">Tipo</th><th>Subtipo</th><th class="text-center">Estado</th><th class="text-center">Afecta saldo</th><th class="text-right">Monto</th><th class="text-right">Abonado</th><th class="text-right">Comisión</th><th>Descripción</th>
        </tr></thead><tbody id="body-table"></tbody></table>
    </div>
    <div class="next-list-toolbar"><nav aria-label="Paginación de movimientos"><ul class="pagination flex-wrap mb-0" id="pagination"></ul></nav></div>
</div>
@endsection
@section('scripts')
<script src="{{ asset('js/cashBox/summary-readonly.js') }}"></script>
@endsection