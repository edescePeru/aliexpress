@extends('layouts.appAdmin2')

@section('openDataGeneral')
    menu-open
@endsection

@section('activeDataGeneral')
    active
@endsection

@section('activeCashBox')
    active
@endsection

@section('title')
    Cajas
@endsection

@section('styles')
    <style>
        .letraTabla {
            font-family: "Calibri", Arial, sans-serif;
            font-size: 15px;
        }
        .normal-title {
            background-color: #203764;
            color: #fff;
            text-align: center;
        }
        .busqueda-avanzada {
            display: none;
        }
        .vertical-center {
            display: flex;
            align-items: center;
        }
        .badge-pill { padding: .35rem .6rem; }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Listado de Cajas</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de Cajas</h5>
    @can('create_cashBox')
        <button data-btn_create class="btn btn-outline-success btn-sm float-right">
            <i class="fa fa-plus font-20"></i> Nueva Caja
        </button>
    @endcan
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('cashBox.index') }}"><i class="fa fa-cogs"></i> Cajas</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-list"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions ?? []) }}">

    <!-- Filtros -->
    <form action="#" onsubmit="return false;">
        <div class="row">
            <div class="col-md-12">
                <div class="input-group">
                    <input type="text" id="search" class="form-control" placeholder="Buscar por nombre / banco / cuenta..." autocomplete="off">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" id="btn-search">Buscar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="d-flex flex-wrap flex-stack pb-2 mt-3">
        <div class="d-flex flex-wrap align-items-center my-1">
            <h3 class="fw-bolder me-5 my-1">
                <span id="numberItems"></span> Cajas encontradas
                <span class="text-gray-400 fs-6">por nombre ↓</span>
            </h3>
        </div>
    </div>

    <hr>

    <div class="table-responsive">
        <table class="table table-bordered letraTabla table-hover table-sm mb-2">
            <thead>
            <tr class="normal-title">
                <th>ID</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Subtipos</th>
                <th>Banco</th>
                <th>Cuenta</th>
                <th>Moneda</th>
                <th>Estado</th>
                <th style="min-width: 140px;">Acciones</th>
            </tr>
            </thead>
            <tbody id="body-table"></tbody>
        </table>
    </div>

    <div class="d-flex flex-stack flex-wrap pt-1">
        <div class="fs-6 fw-bold text-gray-700" id="textPagination"></div>
        <ul class="pagination" style="margin-left: auto;" id="pagination"></ul>
    </div>

    {{-- Templates --}}
    <template id="item-table">
        <tr>
            <td data-id></td>
            <td data-name></td>
            <td data-type></td>
            <td data-uses_subtypes></td>
            <td data-bank_name></td>
            <td data-account></td>
            <td data-currency></td>
            <td data-status></td>
            <td data-buttons></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="9" align="center">No se ha encontrado ninguna caja</td>
        </tr>
    </template>

    <template id="template-btn-edit">
        <button
                class="btn btn-outline-warning btn-sm"
                data-editar
                data-toggle="tooltip"
                data-placement="top"
                title="Editar">
            <i class="fa fa-pen"></i>
        </button>
    </template>

    <template id="template-btn-toggle">
        <button
                class="btn btn-outline-secondary btn-sm"
                data-toggle-active
                data-toggle="tooltip"
                data-placement="top"
                title="Activar/Desactivar">
            <i class="fa fa-power-off"></i>
        </button>
    </template>

    <template id="badge-yes">
        <span class="badge badge-success badge-pill">Sí</span>
    </template>
    <template id="badge-no">
        <span class="badge badge-secondary badge-pill">No</span>
    </template>
    <template id="badge-active">
        <span class="badge badge-success badge-pill">Activo</span>
    </template>
    <template id="badge-inactive">
        <span class="badge badge-danger badge-pill">Inactivo</span>
    </template>

    {{-- Modal --}}
    <div class="modal fade" id="modalCashBox" tabindex="-1" aria-labelledby="cashBoxLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formCashBox"
                      data-url_list="{{ route('cashBox.list') }}"
                      data-url_create="{{ route('cashBox.store') }}"
                      data-url_update="{{ route('cashBox.update') }}"
                      data-url_toggle="{{ route('cashBox.toggle') }}"
                >
                    <div class="modal-header">
                        <h5 class="modal-title" id="cashBoxTitle">Nueva Caja</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="id" id="cb_id">

                        <div class="form-group">
                            <label for="cb_name">Nombre</label>
                            <input type="text" class="form-control" name="name" id="cb_name" required maxlength="120">
                        </div>

                        <div class="form-group">
                            <label for="cb_type">Tipo</label>
                            <select class="form-control" name="type" id="cb_type" required>
                                <option value="cash">Efectivo</option>
                                <option value="bank">Bancario</option>
                            </select>
                        </div>

                        <div class="form-group" id="wrap_uses_subtypes">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="cb_uses_subtypes" name="uses_subtypes">
                                <label class="custom-control-label" for="cb_uses_subtypes">
                                    Permitir subtipos (Yape/Plin/POS/Transferencia)
                                </label>
                            </div>
                        </div>

                        <div id="wrap_bank_fields" style="display:none;">
                            <div class="form-group">
                                <label for="cb_bank_name">Banco (opcional)</label>
                                <input type="text" class="form-control" name="bank_name" id="cb_bank_name" maxlength="120" placeholder="BCP, Interbank, etc.">
                            </div>

                            <div class="form-group">
                                <label for="cb_account_label">Alias / Etiqueta (opcional)</label>
                                <input type="text" class="form-control" name="account_label" id="cb_account_label" maxlength="120" placeholder="Cuenta principal, Izipay, etc.">
                            </div>

                            <div class="form-group">
                                <label for="cb_account_number_mask">Cuenta (máscara) (opcional)</label>
                                <input type="text" class="form-control" name="account_number_mask" id="cb_account_number_mask" maxlength="50" placeholder="****1234">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="cb_currency">Moneda (opcional)</label>
                            <select class="form-control" name="currency" id="cb_currency">
                                <option value="">(Sin definir)</option>
                                <option value="PEN">PEN</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="cb_position">Orden (posición)</label>
                            <input type="number" class="form-control" name="position" id="cb_position" min="0" value="0">
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="cb_is_active" name="is_active" checked>
                                <label class="custom-control-label" for="cb_is_active">
                                    Activo
                                </label>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        @can('create_cashBox')
                            <button type="button" class="btn btn-primary" id="btnSaveCashBox">Guardar</button>
                        @endcan
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/cashBox/index.js') }}"></script>
@endsection