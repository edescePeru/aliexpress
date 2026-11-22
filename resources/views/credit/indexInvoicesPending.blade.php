@extends('layouts.appAdmin2')

@section('openCredit')
    menu-open
@endsection

@section('activeCreditSupplier')
    active
@endsection

@section('activeListCreditSupplier')
    active
@endsection

@section('title')
    Facturas Proveedores Pendientes
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/vdialog/css/vdialog.css') }}">

@endsection

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }
        td.details-control {
            background: url('/admin/plugins/datatables/resources/details_open.png') no-repeat center center;
            cursor: pointer;
        }
        tr.details td.details-control {
            background: url('/admin/plugins/datatables/resources/details_close.png') no-repeat center center;
        }
    </style>
@endsection

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Proveedores · créditos</span><h1 class="page-title">Facturas y saldos de créditos</h1><p class="next-page-description">Consulta y administración de créditos de la empresa activa según sus permisos.</p></div>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de créditos de proveedores</h5>
    <button id="btn-summary" data-url="{{ route('get.summary.deuda.pending') }}" class="btn btn-outline-success btn-sm float-right" > <i class="fas fa-hand-holding-usd"></i> Ver resumen de deuda </button>
    <button id="btn-expire" data-url="{{ route('get.invoices.for.expire') }}" class="btn btn-outline-danger btn-sm float-right" > <i class="fas fa-exclamation-triangle"></i> Ver facturas por vencer </button>
    <button id="btn-amount" data-url="{{ route('get.amount.invoice.current.month') }}" class="btn btn-outline-primary btn-sm float-right" > <i class="fas fa-dollar-sign"></i> Ver monto facturas </button>

@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-archive"></i> Facturas por compras </li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
    {{--<h3>Listado de facturas</h3>
    <div class="row">
        <div class="col-md-3">
            <strong> Seleccione un rango de fechas: </strong>
        </div>
        <div class="col-md-6" id="sandbox-container">
            <div class="input-daterange input-group" id="datepicker">
                <input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start">
                <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                <input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end">
            </div>
        </div>

        <br><br>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm" id="dynamic-table">
            <thead>
            <tr>
                <th>Fecha de factura</th>
                <th>Orden de compra</th>
                <th>Factura</th>
                <th>Proveedor</th>
                <th>Subtotal</th>
                <th>Impuestos</th>
                <th>Total</th>
                <th>Imagen</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
    <br>
    <hr>--}}
    <h3>Listado de créditos</h3>
    <div class="row">
        <div class="col-md-3">
            <strong> Seleccione un rango de fechas: </strong>
        </div>
        <div class="col-md-6" id="sandbox-container2">
            <div class="input-daterange input-group" id="datepicker2">
                <input type="text" class="form-control form-control-sm date-range-filter2" id="start2" name="start">
                <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                <input type="text" class="form-control form-control-sm date-range-filter2" id="end2" name="end">
            </div>
        </div>
        <div class="col-md-3">
            <button type="button" id="btn-export" class="btn btn-block btn-sm btn-outline-success"> <i class="fas fa-file-excel"></i> Exportar</button>
        </div>
        <br><br>
    </div>
    <div>
        <div class="row">
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="0" class="custom-control-input" id="customSwitch1">
                <label class="custom-control-label" for="customSwitch1">Orden</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="1" class="custom-control-input" id="customSwitch2">
                <label class="custom-control-label" for="customSwitch2">Código</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="2" class="custom-control-input" id="customSwitch3">
                <label class="custom-control-label" for="customSwitch3">Proveedor</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="3" class="custom-control-input" id="customSwitch4">
                <label class="custom-control-label" for="customSwitch4">Moneda</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="4" class="custom-control-input" id="customSwitch5">
                <label class="custom-control-label" for="customSwitch5">Condición</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="5" class="custom-control-input" id="customSwitch6">
                <label class="custom-control-label" for="customSwitch6">Monto Dólares</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="6" class="custom-control-input" id="customSwitch7">
                <label class="custom-control-label" for="customSwitch7">Monto Soles</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="7" class="custom-control-input" id="customSwitch8">
                <label class="custom-control-label" for="customSwitch8">Deuda Actual Dólares</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="8" class="custom-control-input" id="customSwitch9">
                <label class="custom-control-label" for="customSwitch9">Deuda Actual Soles</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="9" class="custom-control-input" id="customSwitch10">
                <label class="custom-control-label" for="customSwitch10">Adelanto</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="10" class="custom-control-input" id="customSwitch11">
                <label class="custom-control-label" for="customSwitch11">Deuda Actual</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="11" class="custom-control-input" id="customSwitch12">
                <label class="custom-control-label" for="customSwitch12">Factura</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="12" class="custom-control-input" id="customSwitch13">
                <label class="custom-control-label" for="customSwitch13">Fecha Emisión</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="13" class="custom-control-input" id="customSwitch14">
                <label class="custom-control-label" for="customSwitch14">Fecha Vencimiento</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="14" class="custom-control-input" id="customSwitch15">
                <label class="custom-control-label" for="customSwitch15">Estado</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="15" class="custom-control-input" id="customSwitch16">
                <label class="custom-control-label" for="customSwitch16">Estado Pago</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="16" class="custom-control-input" id="customSwitch17">
                <label class="custom-control-label" for="customSwitch17">Fecha Pago</label>
            </div>
            <div class="col-md-2 custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="17" class="custom-control-input" id="customSwitch18">
                <label class="custom-control-label" for="customSwitch18">Observación</label>
            </div>
        </div>

    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm" id="dynamic-table2">
            <thead>
            <tr>
                <th>Orden</th>
                <th>Codigo</th>
                <th>Proveedor</th>
                <th>Moneda</th>
                <th>Condición</th>
                <th>Monto Dólares</th>
                <th>Monto Soles</th>
                <th>Deuda Actual Dólares</th>
                <th>Deuda Actual Soles</th>
                <th>Pago</th>
                <th>Deuda Actual</th>
                <th>Factura</th>
                <th>Fecha Emisión</th>
                <th>Fecha Vencimiento</th>
                <th>Vence en</th>
                <th>Estado Pago</th>
                <th>Fecha Pago</th>
                <th>Observación</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>

    <div id="modalPay" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Información del crédito</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formPay" data-url="{{ route('credit.control.paid') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="credit_id" name="credit_id">
                        <input type="hidden" id="days_deadline" name="days_deadline">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="supplier"> Proveedor <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="supplier" name="supplier" class="form-control form-control-sm" readonly />
                            </div>
                            <div class="col-md-4">
                                <label for="invoice"> Factura <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="invoice" name="invoice" class="form-control form-control-sm" readonly />
                            </div>
                            <div class="col-md-4">
                                <label for="code_order"> # O.C / O.S <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="code_order" name="code_order" class="form-control form-control-sm" readonly />
                            </div>

                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="total_soles"> Importe S/.  <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="total_soles" name="total_soles" class="form-control form-control-sm" readonly />
                            </div>
                            <div class="col-md-4">
                                <label for="total_dollars"> Importe $ <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="total_dollars" name="total_dollars" class="form-control form-control-sm" readonly />
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="date_issue">Fecha Emisión <span class="right badge badge-danger">(*)</span></label>

                                    <input type="text" class="form-control form-control-sm" id="date_issue" name="date_issue" readonly>

                                </div>
                            </div>

                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="payment_deadline"> Plazo Pago <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="payment_deadline" name="payment_deadline" class="form-control form-control-sm" readonly />
                            </div>
                            <div class="col-md-4">
                                <label for="date_expiration_2">Fecha Vence <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control form-control-sm" id="date_expiration_2" name="date_expiration" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="state_credit"> Estado del credito <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="state_credit" name="state_credit" class="form-control form-control-sm" readonly />
                            </div>

                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="days_to_expiration"> Días Vence <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="days_to_expiration" name="days_to_expiration" class="form-control form-control-sm" readonly />
                            </div>
                            <div class="col-md-8">
                                <label for="observation"> Observación <span class="right badge badge-danger">(*)</span></label>
                                <textarea name="observation" class="form-control form-control-sm" id="observation" rows="2" readonly></textarea>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="date_paid">Fecha Pago <span class="right badge badge-danger">(*)</span></label>
                                    <div class="input-date" id="date_picker_paid">
                                        <input type="text" class="form-control date_picker_paid" id="date_paid" name="date_paid">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="image">Imagen/PDF Comprobante </label>
                                <input type="file" id="image_paid" name="image_paid" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="observation2"> Observación de pago <span class="right badge badge-danger">(*)</span></label>
                                <textarea name="observation2" class="form-control" id="observation2" rows="2"></textarea>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        @can('pay_credit')
                        <button type="button" id="btn-pay" class="btn btn-success">Guardar pago</button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalDelete" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirmar eliminación</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formDelete" data-url="{{ route('material.destroy') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="material_id" name="material_id">
                        <p>¿Está seguro de eliminar este material?</p>
                        <p id="descriptionDelete"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalEdit" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Información del crédito</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formEdit" data-url="{{ route('credit.control.update') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="credit_id" name="credit_id">
                        <input type="hidden" id="days_deadline" name="days_deadline">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="supplier"> Proveedor <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="supplier" name="supplier" class="form-control" readonly />
                            </div>
                            <div class="col-md-4">
                                <label for="invoice"> Factura <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="invoice" name="invoice" class="form-control" readonly />
                            </div>
                            <div class="col-md-4">
                                <label for="code_order"> # O.C / O.S <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="code_order" name="code_order" class="form-control" readonly />
                            </div>

                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="total_soles"> Importe S/.  <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="total_soles" name="total_soles" class="form-control" readonly />
                            </div>
                            <div class="col-md-4">
                                <label for="total_dollars"> Importe $ <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="total_dollars" name="total_dollars" class="form-control" readonly />
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="date_issue">Fecha Emisión <span class="right badge badge-danger">(*)</span></label>
                                    <div class="input-date" id="date_picker_issue">
                                        <input type="text" class="form-control date_picker_issue" id="date_issue" name="date_issue">
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="payment_deadline"> Plazo Pago <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="payment_deadline" name="payment_deadline" class="form-control" readonly />
                            </div>
                            <div class="col-md-4">
                                <div class="form-group" id="date_expiration">
                                    <label for="date_expiration_2">Fecha Vence <span class="right badge badge-danger">(*)</span></label>
                                    <div class="input-date" id="date_picker_expiration">
                                        <input type="text" class="form-control date_picker_expiration" id="date_expiration_2" name="date_expiration">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="state_credit"> Estado del credito <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="state_credit" name="state_credit" class="form-control" readonly />
                            </div>

                        </div>

                        <div class="form-group row">
                            <div class="col-md-3">
                                <label for="days_to_expiration"> Días Vence <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="days_to_expiration" name="days_to_expiration" class="form-control" readonly />
                            </div>
                            <div class="col-md-6">
                                <label for="observation"> Observación <span class="right badge badge-danger">(*)</span></label>
                                <textarea name="observation" class="form-control" id="observation" rows="2"></textarea>
                            </div>

                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        @can('edit_credit')
                        <button type="button" id="btn-submit" class="btn btn-success">Guardar cambios</button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalSummary" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Deuda Actual</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="col-md-12 col-sm-12 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-gradient-success">S/.</span>

                            <div class="info-box-content">
                                <span class="info-box-text">SOLES</span>
                                <span class="info-box-number" id="deudaSoles">1,410</span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                        <!-- /.info-box -->
                    </div>
                    <div class="col-md-12 col-sm-12 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-gradient-info">$</span>

                            <div class="info-box-content">
                                <span class="info-box-text">DÓLARES</span>
                                <span class="info-box-number" id="deudaDolares">1,410</span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                        <!-- /.info-box -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <div id="modalExpire" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Facturas por vencer</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="body-expire-load" class="table-responsive p-0" style="height: 300px;">
                        <table class="card-body table table-head-fixed">
                            <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Proveedor</th>
                                <th>Factura</th>
                                <th>Fecha Vence</th>
                                <th>Vence en</th>
                            </tr>
                            </thead>
                            <tbody id="body-expires">

                            </tbody>
                            <template id="template-expire">
                                <tr>
                                    <td data-orden></td>
                                    <td data-proveedor></td>
                                    <td data-factura></td>
                                    <td data-fecha></td>
                                    <td data-vence></td>
                                </tr>
                            </template>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <div id="modalAmount" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Monto de facturas</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4" id="cboYears">
                            <label for="year">Año <span class="right badge badge-danger">(*)</span></label>

                            <select id="year" name="year" class="form-control select2" style="width: 100%;">
                                <option></option>
                                @foreach( $years as $year )
                                    <option value="{{ $year->year }}">{{ $year->year}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4" id="cboMonths">
                            <label for="month">Mes <span class="right badge badge-danger">(*)</span></label>

                            <select id="month" name="month" class="form-control select2" style="width: 100%;">
                                <option></option>

                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="btn-get_amount">&nbsp;</label><br>
                            <button type="button" id="btn-get_amount" data-url="{{ route('get.amount.invoice.general') }}" class="btn btn-outline-success btn-block"> <i class="fas fa-arrow-circle-right"></i> Obtener</button>
                        </div>
                    </div>
                    <hr>
                    <div id="body-amount-load" class="table-responsive p-0" style="height: 300px;">
                        <div class="col-md-12 col-sm-12 col-12">
                            <div class="info-box">
                                <span class="info-box-icon bg-gradient-success">S/.</span>

                                <div class="info-box-content">
                                    <span class="info-box-text">SOLES</span>
                                    <span class="info-box-number" id="amountSoles">1,410</span>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                        </div>
                        <div class="col-md-12 col-sm-12 col-12">
                            <div class="info-box">
                                <span class="info-box-icon bg-gradient-info">$</span>

                                <div class="info-box-content">
                                    <span class="info-box-text">DÓLARES</span>
                                    <span class="info-box-number" id="amountDolares">1,410</span>
                                </div>
                                <!-- /.info-box-content -->
                            </div>
                            <!-- /.info-box -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <div id="modalPays" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Información de pagos</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    @can('pay_credit')
                    <div class="row">
                        <input type="hidden" id="credito_id" value="">
                        <div class="col-md-3">
                            <label class="col-sm-12 control-label" for="montoPago"> Monto <span class="right badge badge-danger">(*)</span></label>

                            <div class="col-sm-12">
                                <input type="number" min="0" step="0.01" id="montoPago" name="montoPago" class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="col-sm-12 control-label" for="fechaPago"> Fecha <span class="right badge badge-danger">(*)</span></label>

                            <div class="col-sm-12">
                                <input type="text" id="fechaPago" name="fechaPago" class="form-control" data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="col-sm-12 control-label" for="comprobantePago"> Comprobante </label>

                            <div class="col-sm-12">
                                <input type="file" id="comprobantePago" name="comprobantePago" class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="col-sm-12 control-label" for="material_selected_quantity"> &nbsp;&nbsp;&nbsp; </label>

                            <div class="col-sm-12">
                                <button type="button" id="btn-save-pay" class="btn btn-outline-primary">Guardar <i class="fas fa-arrow-circle-right"></i></button>
                                <button type="button" id="btn-new-payment-draft" class="btn btn-link btn-sm">Iniciar otro abono</button>
                            </div>
                        </div>
                    </div>
                    @endcan
                    <hr>
                    <div class="row">
                        <div class="col-sm-12">
                            <label class="col-sm-12 control-label"> Pagos registrados </label>
                        </div>
                    </div>

                    <div id="body-items-load" class="table-responsive p-0" style="height: 300px;">
                        <table class="card-body table table-head-fixed">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                                <th>Comprobante</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody id="body-pays">

                            </tbody>
                            <template id="template-pay">
                                <tr>
                                    <td data-id></td>
                                    <td data-monto></td>
                                    <td data-fecha></td>
                                    <td>

                                        <button type="button" class="btn btn-outline-primary btn-sm" data-comprobante data-image_comprobante=""><i class="far fa-image"></i></button>

                                    </td>
                                    <td>

                                        <button type="button" class="btn btn-outline-danger btn-sm" data-delete=""><i class="fas fa-trash-alt"></i></button>

                                    </td>
                                </tr>
                            </template>
                            <template id="template-pay2">
                                <tr>
                                    <td data-id></td>
                                    <td data-monto></td>
                                    <td data-fecha></td>
                                    <td>
                                        <a target="_blank" href="" class="btn btn-outline-primary btn-sm" data-comprobante><i class="fas fa-file-pdf"></i></a>
                                    </td>
                                    <td>

                                        <button type="button" class="btn btn-outline-danger btn-sm" data-delete=""><i class="fas fa-trash-alt"></i></button>

                                    </td>
                                </tr>
                            </template>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <div id="modalImageComprobante" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ver imagen</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" >
                    <div id="zoom">
                        <img id="imagePreview" src="" class="img-fluid" alt="">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalImage" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Visualización del documento</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <img id="image-document" src="" alt="" width="100%">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('plugins')
    <!-- Datatables -->
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- Vdialog -->
    <script src="{{ asset('admin/plugins/vdialog/js/lib/vdialog.js') }}"></script>
    <script src="{{ asset('admin/plugins/zoom/jquery.zoom.js')}}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/inputmask/min/jquery.inputmask.bundle.min.js') }}"></script>
    <script src="{{asset('admin/plugins/jquery_loading/loadingoverlay.min.js')}}"></script>

    <script>
        $('#year').select2({
            placeholder: "Año",
        });
        $('#month').select2({
            placeholder: "Mes",
        });

        $('#fechaPago').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' });

        $('#date_picker_issue .date_picker_issue').datepicker({
            todayBtn: "linked",
            clearBtn: true,
            language: "es",
            multidate: false,
            autoclose: true,
            todayHighlight: true,
            defaultViewDate: moment().format('L')
        });
        $('#date_expiration .date_picker_expiration').datepicker({
            todayBtn: "linked",
            clearBtn: true,
            language: "es",
            multidate: false,
            autoclose: true,
            todayHighlight: true,
            defaultViewDate: moment().format('L')
        });
        $('#date_picker_paid .date_picker_paid').datepicker({
            todayBtn: "linked",
            clearBtn: true,
            language: "es",
            multidate: false,
            autoclose: true,
            todayHighlight: true,
            defaultViewDate: moment().format('L')
        });
    </script>
    <script src="{{ asset('js/credit/permissions.js') }}?v=credit-ui-1"></script>
    <script src="{{ asset('js/credit/index_invoicesPending.js') }}?v=credit-attachment-1"></script>
@endsection
