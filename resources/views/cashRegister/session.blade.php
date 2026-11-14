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



@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Caja · Sesión propia</span><h1 class="page-title">{{ $active }}</h1><p class="next-page-description">Sesión de la empresa y local activos.</p></div>
@endsection

@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Sesión #{{ $cashRegister->id }}</strong><span>Movimientos y saldo de caja.</span></div><div class="next-toolbar-actions flex-wrap">
<button type="button" id="btn-incomeCashRegister" class="btn btn-outline-success">Ingreso</button>
<button type="button" id="btn-expenseCashRegister" class="btn btn-outline-danger">Egreso</button>
<button type="button" id="btn-arqueoCashRegister" class="btn btn-outline-secondary">Arqueo de caja</button>
</div></div>
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
    <section class="next-operational-list">
    <div class="next-form-section"><div class="row">
        <div class="col-sm-6 col-lg-3"><span class="text-muted">Usuario</span><p class="font-weight-bold">{{ auth()->user()->name }}</p></div>
        <div class="col-sm-6 col-lg-3"><span class="text-muted">Apertura</span><p>{{ $cashRegister->opening_time ? $cashRegister->opening_time->format('d/m/Y H:i') : 'No registrada' }}</p></div>
        <div class="col-sm-6 col-lg-3"><span class="text-muted">Estado</span><p><span class="badge {{ $cashRegister->status ? 'badge-success' : 'badge-secondary' }}">{{ $cashRegister->status ? 'Abierta' : 'Cerrada' }}</span></p></div>
        <div class="col-sm-6 col-lg-3" id="label_balance"><span class="text-muted">Saldo de sesión · {{ $cashBox->currency ?: 'Moneda no informada' }}</span><p class="h4 text-primary font-weight-bold" id="valueBalanceTotal" data-currency="{{ $cashBox->currency }}">{{ $cashBox->currency }} {{ $balance_total }}</p></div>
    </div></div>
    <div id="list-error" class="alert alert-danger d-none" role="alert"></div>
    <div class="next-list-summary"><span><strong id="numberItems"></strong> movimientos de caja</span><span>Fecha de registro · más recientes primero · {{ $cashBox->currency ?: 'Moneda no informada' }}</span></div>
    <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm next-data-table">
                <thead>
                <tr >
                    <th class="text-center">N°</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-left">Subtipo</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Fecha</th>
                    <th class="text-right">Monto</th>
                    <th class="text-right">Abonado</th>
                    <th class="text-right">Comisión</th>
                    <th class="text-left w-25">Descripción</th>
                    <th class="text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="body-table">

                </tbody>
            </table>
        </div>
    <div class="next-list-pagination"><div id="textPagination"></div><ul class="pagination" id="pagination"></ul></div>
    </section>

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

    @include('cashRegister._regularization')

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
                            <strong>Importante:</strong> El arqueo cerrará tu sesión y consolidará el monto confirmado en esta caja. Los cobros pendientes quedarán registrados para su regularización.
                        </div>

                        <p><b>Caja:</b> {{ $active }}</p>
                        <p><b>Saldo teórico:</b> {{ $cashBox->currency }} <span id="arqueo_teorico">{{ $balance_total }}</span></p>

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
            <td data-id class="text-center"></td>
            <td data-type class="text-center"></td>
            <td data-subtype class="text-left"></td>
            <td data-status class="text-center"></td>
            <td data-date class="text-center text-nowrap"></td>
            <td data-amount class="text-right"></td>
            <td data-amount_regularize class="text-right"></td>
            <td data-commission class="text-right"></td>
            <td data-description class="text-left"></td>
            <td data-buttons class="text-center"></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="10" align="center">No se ha encontrado ningún dato</td>
        </tr>
    </template>

    <template id="template-button">
        <a href="" target="_blank" rel="noopener" data-print_nota data-id=""
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
    <script src="{{ asset('js/cashRegister/regularization.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/cashRegister/session.js') }}?v={{ time() }}"></script>
@endsection
