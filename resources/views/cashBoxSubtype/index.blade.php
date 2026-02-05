@extends('layouts.appAdmin2')

@section('openDataGeneral') menu-open @endsection
@section('activeDataGeneral') active @endsection
@section('activeCashBoxSubtype') active @endsection

@section('title') Subtipos Bancarios @endsection

@section('styles')
    <style>
        .letraTabla { font-family: "Calibri", Arial, sans-serif; font-size: 15px; }
        .normal-title { background-color: #203764; color:#fff; text-align:center; }
        .badge-pill { padding: .35rem .6rem; }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Subtipos Bancarios</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de Subtipos</h5>
    @can('create_cashBoxSubtype')
        <button data-btn_create class="btn btn-outline-success btn-sm float-right">
            <i class="fa fa-plus font-20"></i> Nuevo Subtipo
        </button>
    @endcan
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('cashBoxSubtype.index') }}"><i class="fa fa-cogs"></i> Subtipos</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-list"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions ?? []) }}">

    <form action="#" onsubmit="return false;">
        <div class="row">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" id="search" class="form-control" placeholder="Buscar por código o nombre..." autocomplete="off">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" id="btn-search">Buscar</button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <select id="filter_cash_box" class="form-control">
                    <option value="">TODAS (Global + Caja)</option>
                    <option value="global">SOLO GLOBALES</option>
                    @foreach($cashBoxes as $b)
                        <option value="{{ $b->id }}">SOLO: {{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="d-flex flex-wrap flex-stack pb-2 mt-3">
        <div class="d-flex flex-wrap align-items-center my-1">
            <h3 class="fw-bolder me-5 my-1">
                <span id="numberItems"></span> Subtipos encontrados
            </h3>
        </div>
    </div>

    <hr>

    <div class="table-responsive">
        <table class="table table-bordered letraTabla table-hover table-sm mb-2">
            <thead>
            <tr class="normal-title">
                <th>ID</th>
                <th>Alcance</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Diferido</th>
                <th>Comisión</th>
                <th>Orden</th>
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

    <template id="item-table">
        <tr>
            <td data-id></td>
            <td data-scope></td>
            <td data-code></td>
            <td data-name></td>
            <td data-deferred></td>
            <td data-commission></td>
            <td data-position></td>
            <td data-status></td>
            <td data-buttons></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="9" align="center">No se ha encontrado ningún subtipo</td>
        </tr>
    </template>

    <template id="template-btn-edit">
        <button class="btn btn-outline-warning btn-sm" data-editar title="Editar">
            <i class="fa fa-pen"></i>
        </button>
    </template>

    <template id="template-btn-toggle">
        <button class="btn btn-outline-secondary btn-sm" data-toggle-active title="Activar/Desactivar">
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

    <div class="modal fade" id="modalSubtype" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formSubtype"
                      data-url_list="{{ route('cashBoxSubtype.list') }}"
                      data-url_create="{{ route('cashBoxSubtype.store') }}"
                      data-url_update="{{ route('cashBoxSubtype.update') }}"
                      data-url_toggle="{{ route('cashBoxSubtype.toggle') }}"
                >
                    <div class="modal-header">
                        <h5 class="modal-title" id="subtypeTitle">Nuevo Subtipo</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="id" id="st_id">

                        <div class="form-group">
                            <label for="st_cash_box_id">Alcance</label>
                            <select class="form-control" name="cash_box_id" id="st_cash_box_id">
                                <option value="global">GLOBAL (para todos)</option>
                                @foreach($cashBoxes as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="st_code">Código</label>
                            <input type="text" class="form-control" name="code" id="st_code" maxlength="50" required placeholder="yape / plin / pos / transfer">
                            <small class="text-muted">Se guardará en minúsculas, sin espacios (se reemplaza por guión bajo).</small>
                        </div>

                        <div class="form-group">
                            <label for="st_name">Nombre</label>
                            <input type="text" class="form-control" name="name" id="st_name" maxlength="120" required placeholder="Yape">
                        </div>

                        {{-- ✅ NUEVOS FLAGS --}}
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="st_is_deferred" name="is_deferred">
                                <label class="custom-control-label" for="st_is_deferred">
                                    Diferido (requiere regularización)
                                </label>
                            </div>
                            <small class="text-muted">Ej: POS/pasarela que deposita después.</small>
                        </div>

                        <div class="form-group" id="wrap_requires_commission" style="display:none;">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="st_requires_commission" name="requires_commission">
                                <label class="custom-control-label" for="st_requires_commission">
                                    Requiere comisión (se calcula amount - amount_regularize)
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="st_position">Orden (posición)</label>
                            <input type="number" class="form-control" name="position" id="st_position" min="0" value="0">
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="st_is_active" name="is_active" checked>
                                <label class="custom-control-label" for="st_is_active">Activo</label>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        @can('create_cashBoxSubtype')
                            <button type="button" class="btn btn-primary" id="btnSaveSubtype">Guardar</button>
                        @endcan
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/cashBoxSubtype/index.js') }}"></script>
@endsection
