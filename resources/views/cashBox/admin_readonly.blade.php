@extends('layouts.appAdmin2')
@section('activeAdminCashMovements', 'active')
@section('title', 'Movimientos administrativos')
@section('page-header')
<div class="next-page-heading">
    <span class="next-page-eyebrow">Caja · Administración</span>
    <h1 class="page-title">Movimientos administrativos</h1>
    <p class="next-page-description">Movimientos de los usuarios de la empresa y local activos.</p>
</div>
@endsection
@section('page-title')
<div class="next-page-toolbar">
    <div class="next-toolbar-context"><strong>Listado operativo</strong><span>Movimientos y operaciones del contexto activo</span></div>
    @can('listCashMovementMy_cashBox')
    <div class="next-toolbar-actions"><a class="btn btn-outline-secondary btn-sm" href="{{ route('cashMovement.my.index') }}">Mis movimientos</a></div>
    @endcan
</div>
@endsection
@section('page-breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Caja</li><li class="breadcrumb-item active">Movimientos administrativos</li>
</ol>
@endsection
@section('content')
<section id="admin-movements" class="next-operational-list" data-url="{{ route('cashMovement.admin.list') }}" data-regularize="{{ auth()->user()->can('regularize_caja') ? 1 : 0 }}" data-print="{{ auth()->user()->can('print_puntoVenta') ? 1 : 0 }}" data-invoice="{{ auth()->user()->can('list_invoice') ? 1 : 0 }}" data-open-url="{{ route('cashRegister.open') }}" data-session-url="{{ url('dashboard/cash-registers/session') }}" aria-label="Movimientos administrativos">
    <div class="next-list-toolbar d-flex align-items-center justify-content-between flex-wrap">
        <strong>Mis sesiones de caja</strong>
        <div class="next-toolbar-actions flex-wrap mw-100">
            @foreach($cashBoxes as $box)
                @php $isOpen = in_array($box->id, $openCashBoxIds); @endphp
                @if($isOpen)
                    <a class="btn btn-outline-success btn-sm" href="{{ route('cashRegister.session', $box->id) }}"><i class="fas fa-door-open mr-1" aria-hidden="true"></i>{{ $box->name }} · Ir a mi sesión</a>
                @else
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-cashbox-btn data-cash_box_id="{{ $box->id }}" data-cash_box_name="{{ $box->name }}" data-cash_box_type="{{ $box->type }}" data-is_open="0"><i class="fas fa-cash-register mr-1" aria-hidden="true"></i>{{ $box->name }} · Abrir</button>
                @endif
            @endforeach
        </div>
    </div>
    <form id="admin-filters" class="next-list-toolbar">
        <div class="form-row align-items-end">
            <div class="form-group col-12 col-xl-3 mb-xl-0">
                <label for="q">Descripción</label>
                <div class="input-group input-group-sm"><input id="q" name="q" type="search" class="form-control form-control-sm" placeholder="Buscar descripción…" autocomplete="off"><div class="input-group-append"><button id="btn-search" type="submit" class="btn btn-primary btn-sm">Buscar</button></div></div>
            </div>
            <div class="form-group col-sm-6 col-md-4 col-xl mb-xl-0"><label for="user_id">Usuario</label><select id="user_id" name="user_id" class="form-control form-control-sm"><option value="">Todos los usuarios</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
            <div class="form-group col-sm-6 col-md-4 col-xl mb-xl-0"><label for="cash_box_id">Caja</label><select id="cash_box_id" name="cash_box_id" class="form-control form-control-sm"><option value="">Todas las cajas</option>@foreach($cashBoxes as $box)<option value="{{ $box->id }}">{{ $box->name }}</option>@endforeach</select></div>
            <div class="form-group col-sm-6 col-md-4 col-xl mb-xl-0"><label for="type">Tipo</label><select id="type" name="type" class="form-control form-control-sm"><option value="">Todos los tipos</option><option value="sale">Venta</option><option value="income">Ingreso</option><option value="expense">Egreso</option></select></div>
            <div class="form-group col-sm-6 col-md-4 col-xl mb-xl-0"><label for="subtype_id">Subtipo</label><select id="subtype_id" name="subtype_id" class="form-control form-control-sm"><option value="">Todos los subtipos</option><option value="none">Sin subtipo</option>@foreach($subtypes as $subtype)<option value="{{ $subtype->id }}">{{ $subtype->name }}</option>@endforeach</select></div>
            <div class="form-group col-sm-6 col-md-4 col-xl mb-xl-0"><label for="date_from">Desde</label><input id="date_from" name="date_from" type="date" class="form-control form-control-sm"></div>
            <div class="form-group col-sm-6 col-md-4 col-xl mb-xl-0"><label for="date_to">Hasta</label><input id="date_to" name="date_to" type="date" class="form-control form-control-sm"></div>
        </div>
    </form>
    <div id="list-error" class="next-form-section d-none" role="alert"><div class="alert alert-danger mb-0" id="list-error-message"></div></div>
    <div class="next-list-summary" aria-live="polite"><div class="next-list-summary-copy"><strong id="numberItems" class="next-list-count">—</strong><span>Movimientos encontrados</span></div><span class="next-list-sort-context">ID · más recientes primero</span></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Movimientos; desplazamiento horizontal local">
        <table class="table table-bordered table-hover table-sm next-data-table"><thead><tr>
            <th scope="col" class="text-center">ID</th><th scope="col" class="text-center">Fecha</th><th scope="col" class="text-left">Usuario</th><th scope="col" class="text-left">Caja</th><th scope="col" class="text-center">Tipo</th><th scope="col" class="text-left">Subtipo</th><th scope="col" class="text-center">Estado</th><th scope="col" class="text-right">Monto</th><th scope="col" class="text-right">Abonado</th><th scope="col" class="text-right">Comisión</th><th scope="col" class="text-left">Descripción</th>
            <th scope="col" class="text-center">Acciones</th>
        </tr></thead><tbody id="body-table"></tbody></table>
    </div></div>
    <div class="next-list-pagination"><div id="textPagination" aria-live="polite">Cargando movimientos…</div><nav aria-label="Paginación de movimientos"><ul class="pagination flex-wrap mb-0" id="pagination"></ul></nav></div>
</section>
@include('cashRegister._regularization')
@endsection
@section('scripts')
<script src="{{ asset('js/cashRegister/regularization.js') }}?v={{ filemtime(public_path('js/cashRegister/regularization.js')) }}"></script>
<script src="{{ asset('js/cashBox/admin-operations.js') }}?v={{ filemtime(public_path('js/cashBox/admin-operations.js')) }}"></script>
<script src="{{ asset('js/cashBox/admin-readonly.js') }}?v={{ filemtime(public_path('js/cashBox/admin-readonly.js')) }}"></script>
@endsection
