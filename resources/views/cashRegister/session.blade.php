@extends('layouts.appAdmin2')

@section('activeMyCashMovements')
    active
@endsection

@section('title')
    Caja {{ $active }}
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/typehead/typeahead.css') }}">
@endsection

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }
        .income-row {
            background-color: #d4edda; /* Verde claro */
        }

        .expense-row {
            background-color: #f8d7da; /* Rojo claro */
        }

        .regularize-row {
            background-color: #f4c3a1; /* Verde claro */
        }
    </style>
@endsection

@section('page-header')
    <div class="row">
        <div class="col-md-4">
            <h1 class="page-title">Caja {{ $active }}</h1>
        </div>
        <div class="col-md-4 col-sm-6 col-12" id="label_balance">

            <div class="bg-success py-2 px-3 align-content-end">
                <h2 class="mb-0" id="valueBalanceTotal">
                    S/. {{ $balance_total }}
                </h2>
            </div>
        </div>
        {!! $state !!}

    </div>

@endsection

@section('page-title')
    <div class="btn-group float-right">
        <button type="button" id="btn-incomeCashRegister" class="btn btn-success mr-1">Ingreso</button>
        <button type="button" id="btn-expenseCashRegister" class="btn btn-danger mr-1">Egreso</button>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('cashMovement.my.index') }}"><i class="fa fa-archive"></i> Mis movimientos</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Caja {{ $active }}</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
    <input type="hidden" id="balance_total" value="{{ $balance_total }}">
    <input type="hidden" id="active" value="{{ $active }}">
    <input type="hidden" id="cash_register_id" value="{{ $cashRegister->id }}">
    <input type="hidden" id="cash_box_id" value="{{ $cashBox->id }}">
    <input type="hidden" id="cash_box_type" value="{{ $cashBox->type }}"> {{-- cash | bank --}}
    <input type="hidden" id="cash_box_uses_subtypes" value="{{ $cashBox->uses_subtypes ? 1 : 0 }}">
    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack pb-7">
        <!--begin::Title-->
        <div class="d-flex flex-wrap align-items-center my-1">
            <h3 class="fw-bolder me-5 my-1"><span id="numberItems"></span> Movimientos de caja
                <span class="text-gray-400 fs-6"> ordenandos por fecha de creación ↓ </span>
            </h3>
        </div>
        <!--end::Title-->
    </div>
    <!--end::Toolbar-->

    <!--begin::Tab Content-->
    <div class="tab-content">
        <!--begin::Tab pane-->
        <hr>
        <div class="table-responsive">
            <table class="table table-bordered letraTabla table-hover table-sm mb-5">
                <thead>
                <tr class="normal-title">
                    <th>N°</th>
                    <th>Tipo</th>
                    <th>Subtipo</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Abonado</th>
                    <th>Comisión</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody id="body-table">

                </tbody>
            </table>
        </div>
        <!--end::Tab pane-->
        <!--begin::Pagination-->
        <div class="d-flex flex-stack flex-wrap pt-1">
            <div class="fs-6 fw-bold text-gray-700" id="textPagination"></div>
            <!--begin::Pages-->
            <ul class="pagination" style="margin-left: auto;" id="pagination">

            </ul>
            <!--end::Pages-->
        </div>
        <!--end::Pagination-->
    </div>
    <!--end::Tab Content-->

    {{--<div id="modalOpen" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">¿Esta seguro de aperturar caja?</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formOpen" data-url="{{ route('open.cashRegister') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="active_open" name="active_open">
                        <div class="col-md-12">
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <strong>Importante!</strong> Se va a aperturar la caja con el siguiente monto.
                            </div>
                        </div>
                        <h5>Monto inicial</h5>
                        <input type="number" readonly class="form-control" name="balance_total_open" id="balance_total_open">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn_open" class="btn btn-success">Aperturar Caja</button>
                    </div>
                </form>
            </div>
        </div>
    </div>--}}

    {{--<div id="modalClose" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">¿Esta seguro de arquear caja?</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formClose" data-url="{{ route('close.cashRegister') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="active_close" name="active_close">
                        <div class="col-md-12">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Importante!</strong> Se va a cerrar la caja con el siguiente monto.
                            </div>
                        </div>
                        <h5>Monto inicial</h5>
                        <input type="number" class="form-control" name="balance_total_close" id="balance_total_close">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn_close" class="btn btn-danger">Arqueo de Caja</button>
                    </div>
                </form>
            </div>
        </div>
    </div>--}}

    <div id="modalIncome" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ingrese datos del ingreso</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formIncome" data-url="{{ route('cashRegister.income') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="active_income" name="active_income">
                        <input type="hidden" id="balance_total_income" name="balance_total_income">
                        <div class="col-md-12">
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Importante!</strong> Se va a guardar los datos del ingreso.
                            </div>
                        </div>
                        @if($cashBox->type === 'bank' && $cashBox->uses_subtypes)
                            <h5>Subtipo bancario</h5>
                            <select class="form-control" id="income_subtype_id">
                                <option value="">Seleccione...</option>
                                @foreach($subtypes as $st)
                                    <option value="{{ $st->id }}"
                                            data-is_deferred="{{ $st->is_deferred ? 1 : 0 }}"
                                            data-requires_commission="{{ $st->requires_commission ? 1 : 0 }}">
                                        {{ $st->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <h5>Monto ingreso</h5>
                        <input type="number" class="form-control" name="income_amount" id="income_amount">
                        <h5>Descripción ingreso</h5>
                        <textarea name="" class="form-control" id="income_description" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn_ingreso" class="btn btn-success">Guardar Ingreso</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalExpense" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ingrese datos del egreso</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formExpense" data-url="{{ route('cashRegister.expense') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="active_expense" name="active_expense">
                        <input type="hidden" id="balance_total_expense" name="balance_total_expense">
                        <div class="col-md-12">
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <strong>Importante!</strong> Se va a guardar los datos del egreso.
                            </div>
                        </div>
                        @if($cashBox->type === 'bank' && $cashBox->uses_subtypes)
                            <h5>Subtipo bancario</h5>
                            <select class="form-control" id="expense_subtype_id">
                                <option value="">Seleccione...</option>
                                @foreach($subtypes as $st)
                                    <option value="{{ $st->id }}"
                                            data-is_deferred="{{ $st->is_deferred ? 1 : 0 }}"
                                            data-requires_commission="{{ $st->requires_commission ? 1 : 0 }}">
                                        {{ $st->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <h5>Monto egreso</h5>
                        <input type="number" class="form-control" name="expense_amount" id="expense_amount">
                        <h5>Descripción egreso</h5>
                        <textarea name="" class="form-control" id="expense_description" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn_egreso" class="btn btn-danger">Guardar Egreso</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalRegularize" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Regularizar movimiento diferido</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="formRegularize" data-url="{{ route('cashMovement.regularize') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="cash_movement_id" name="cash_movement_id">

                        <div class="col-md-12">
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Importante!</strong> Ingrese el monto real abonado (neto). La comisión se calculará automáticamente.
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Monto abonado (neto)</label>
                            <input type="number" class="form-control" step="0.01" min="0" id="regularize_amount" name="amount_regularize">
                        </div>

                        <div class="form-group">
                            <label>Observación (opcional)</label>
                            <textarea class="form-control" id="regularize_observation" name="observation" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn_regularizar" class="btn btn-success">Regularizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalArqueo" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Arqueo y Cierre de Sesión</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="formArqueo" data-url="{{ route('cashRegister.arqueo', $cashRegister->id) }}">
                    @csrf
                    <div class="modal-body">

                        <div class="alert alert-warning">
                            <strong>Importante:</strong> Este arqueo cerrará tu sesión y enviará los montos al administrador (según reglas).
                        </div>

                        <p><b>Caja:</b> {{ $active }}</p>
                        <p><b>Saldo teórico:</b> S/. <span id="arqueo_teorico">{{ $balance_total }}</span></p>

                        {{-- Solo si deseas que el cajero ingrese contado en efectivo --}}
                        @if($cashBox->type === 'cash')
                            <div class="form-group">
                                <label>Monto contado (efectivo físico)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="arqueo_counted" name="counted" required>
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Observación (opcional)</label>
                            <textarea class="form-control" id="arqueo_observation" name="observation" rows="2"></textarea>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn_confirm_arqueo" class="btn btn-success">Confirmar Arqueo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <template id="previous-page">
        <li class="page-item previous">
            <a href="#" class="page-link" data-item>
                <!--<i class="previous"></i>-->
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    </template>

    <template id="item-page">
        <li class="page-item" data-active>
            <a href="#" class="page-link" data-item="">5</a>
        </li>
    </template>

    <template id="next-page">
        <li class="page-item next">
            <a href="#" class="page-link" data-item>
                <!--<i class="next"></i>-->
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    </template>

    <template id="disabled-page">
        <li class="page-item disabled">
            <span class="page-link">...</span>
        </li>
    </template>

    <template id="item-table">
        <tr>
            <td data-id></td>
            <td data-type></td>
            <td data-subtype></td>
            <td data-status></td>
            <td data-date></td>
            <td data-amount></td>
            <td data-amount_regularize></td>
            <td data-commission></td>
            <td data-description></td>
            <td data-buttons></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="10" align="center">No se ha encontrado ningún dato</td>
        </tr>
    </template>

    <template id="template-button">
        <a href="" target="_blank" data-print_nota data-id=""
           class="btn btn-outline-dark btn-sm" title="Imprimir boleta">
            <i class="fas fa-print"></i>
        </a>

        <button data-regularizar data-id=""
                class="btn btn-outline-success btn-sm"
                title="Regularizar Venta POS">
            <i class="fas fa-check-double"></i>
        </button>

    </template>
@endsection

@section('plugins')
    <!-- Datatables -->
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/inputmask/min/jquery.inputmask.bundle.min.js') }}"></script>
    <script src="{{asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/cashRegister/session.js') }}"></script>
@endsection
