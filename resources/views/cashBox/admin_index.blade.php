@extends('layouts.appAdmin2')

@section('activeAdminCashMovements')
    active
@endsection

@section('title') Movimientos de Caja (Admin) @endsection

@section('page-header')
    <h1 class="page-title">Movimientos de Caja (Admin)</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado global de movimientos</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-cash-register"></i> Caja</li>
        <li class="breadcrumb-item"><i class="fa fa-list"></i> Admin</li>
    </ol>
@endsection

@section('styles')
    <style>
        .letraTabla { font-family: "Calibri", Arial, sans-serif; font-size: 15px; }
        .normal-title { background-color: #203764; color:#fff; text-align:center; }
    </style>
@endsection

@section('content')

    <form onsubmit="return false;">
        <div class="row">
            <div class="col-md-3">
                <div class="input-group">
                    <input type="text" id="q" class="form-control" placeholder="Buscar descripción..." autocomplete="off">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" id="btn-search">Buscar</button>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <select id="user_id" class="form-control">
                    <option value="">Todos los usuarios</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <select id="cash_box_id" class="form-control">
                    <option value="">Todas las cajas</option>
                    @foreach($cashBoxes as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <select id="type" class="form-control">
                    <option value="">Todos los tipos</option>
                    <option value="sale">Venta</option>
                    <option value="income">Ingreso</option>
                    <option value="expense">Egreso</option>
                </select>
            </div>

            <div class="col-md-2">
                <select id="subtype_id" class="form-control">
                    <option value="">Todos los subtipos</option>
                    <option value="none">(Sin subtipo)</option>
                    @foreach($subtypes as $st)
                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-1">
                <input type="date" id="date_from" class="form-control" title="Desde">
            </div>
            <div class="col-md-1">
                <input type="date" id="date_to" class="form-control" title="Hasta">
            </div>
        </div>
    </form>

    <hr>

    <div class="table-responsive">
        <table class="table table-bordered letraTabla table-hover table-sm mb-2">
            <thead>
            <tr class="normal-title">
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Caja</th>
                <th>Tipo</th>
                <th>Subtipo</th>
                <th>Descripción</th>
                <th>Monto</th>
            </tr>
            </thead>
            <tbody id="body-table"></tbody>
        </table>
    </div>

    <div class="d-flex flex-stack flex-wrap pt-1">
        <div class="fs-6 fw-bold text-gray-700" id="textPagination"></div>
        <ul class="pagination" style="margin-left: auto;" id="pagination"></ul>
    </div>

    <template id="item-table">
        <tr>
            <td data-date></td>
            <td data-user></td>
            <td data-cashbox></td>
            <td data-type></td>
            <td data-subtype></td>
            <td data-desc></td>
            <td data-amount class="text-right"></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="7" align="center">No se encontraron movimientos</td>
        </tr>
    </template>

@endsection

@section('scripts')
    <script>
        window.CASH_MOVEMENT_LIST_URL = "{{ route('cashMovement.admin.list') }}";
    </script>
    <script src="{{ asset('js/cashBox/admin_index.js') }}"></script>
@endsection
