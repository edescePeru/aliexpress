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



@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Proveedores · créditos</span><h1 class="page-title">Facturas y saldos de créditos</h1><p class="next-page-description">Consulta y administración de créditos de la empresa activa según sus permisos.</p></div>
@endsection

@section('page-title')
    <div class="d-flex align-items-center justify-content-between flex-wrap"><h5 class="card-title">Listado operativo</h5><div class="next-toolbar-actions flex-wrap">
    <button id="btn-summary" data-url="{{ route('get.summary.deuda.pending') }}" class="btn btn-outline-secondary btn-sm " > <i class="fas fa-hand-holding-usd"></i> Ver resumen de deuda </button>
    <button id="btn-expire" data-url="{{ route('get.invoices.for.expire') }}" class="btn btn-outline-secondary btn-sm " > <i class="fas fa-exclamation-triangle"></i> Ver facturas por vencer </button>
    <button id="btn-amount" data-url="{{ route('get.amount.invoice.current.month') }}" class="btn btn-outline-secondary btn-sm " > <i class="fas fa-dollar-sign"></i> Ver monto facturas </button>

    </div></div>
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
    <div class="next-list-toolbar">
        <div class="row align-items-end">
            <div class="col-md-7 col-xl-5" id="sandbox-container2">
                <label for="start2">Fechas de emisión</label>
                <div class="input-daterange input-group" id="datepicker2">
                    <input type="text" class="form-control form-control-sm date-range-filter2" id="start2" name="start" aria-label="Desde">
                    <div class="input-group-append input-group-prepend"><span class="input-group-text">al</span></div>
                    <input type="text" class="form-control form-control-sm date-range-filter2" id="end2" name="end" aria-label="Hasta">
                </div>
            </div>
            <div class="col-md-5 col-xl-7 mt-2 mt-md-0 next-toolbar-actions justify-content-md-end flex-wrap">
                <button type="button" id="btn-export" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-excel mr-1"></i> Exportar</button>
                <div class="dropdown next-column-visibility">
                    <button type="button" id="credit-columns-trigger" class="btn btn-outline-secondary btn-sm dropdown-toggle next-column-visibility-trigger" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-columns mr-1"></i> Columnas</button>
                    <div class="dropdown-menu dropdown-menu-right next-column-visibility-menu" aria-labelledby="credit-columns-trigger">
                        <div class="next-column-visibility-header"><strong>Columnas visibles</strong><span>Personaliza esta tabla</span></div>
                        <div class="next-column-visibility-grid"><div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="0" class="custom-control-input" id="customSwitch1">
                <label class="custom-control-label" for="customSwitch1">Orden</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="1" class="custom-control-input" id="customSwitch2">
                <label class="custom-control-label" for="customSwitch2">Código</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="2" class="custom-control-input" id="customSwitch3">
                <label class="custom-control-label" for="customSwitch3">Proveedor</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="3" class="custom-control-input" id="customSwitch4">
                <label class="custom-control-label" for="customSwitch4">Moneda</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="4" class="custom-control-input" id="customSwitch5">
                <label class="custom-control-label" for="customSwitch5">Condición</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="5" class="custom-control-input" id="customSwitch6">
                <label class="custom-control-label" for="customSwitch6">Monto Dólares</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="6" class="custom-control-input" id="customSwitch7">
                <label class="custom-control-label" for="customSwitch7">Monto Soles</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="7" class="custom-control-input" id="customSwitch8">
                <label class="custom-control-label" for="customSwitch8">Deuda Actual Dólares</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="8" class="custom-control-input" id="customSwitch9">
                <label class="custom-control-label" for="customSwitch9">Deuda Actual Soles</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="9" class="custom-control-input" id="customSwitch10">
                <label class="custom-control-label" for="customSwitch10">Adelanto</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="10" class="custom-control-input" id="customSwitch11">
                <label class="custom-control-label" for="customSwitch11">Deuda Actual</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="11" class="custom-control-input" id="customSwitch12">
                <label class="custom-control-label" for="customSwitch12">Factura</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="12" class="custom-control-input" id="customSwitch13">
                <label class="custom-control-label" for="customSwitch13">Fecha Emisión</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="13" class="custom-control-input" id="customSwitch14">
                <label class="custom-control-label" for="customSwitch14">Fecha Vencimiento</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="14" class="custom-control-input" id="customSwitch15">
                <label class="custom-control-label" for="customSwitch15">Estado</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" checked data-column="15" class="custom-control-input" id="customSwitch16">
                <label class="custom-control-label" for="customSwitch16">Estado Pago</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="16" class="custom-control-input" id="customSwitch17">
                <label class="custom-control-label" for="customSwitch17">Fecha Pago</label>
            </div>
<div class="next-column-visibility-option custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                <input type="checkbox" data-column="17" class="custom-control-input" id="customSwitch18">
                <label class="custom-control-label" for="customSwitch18">Observación</label>
            </div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="credit-list-feedback" class="next-form-section d-none" role="status"></div>
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

    <div id="modalPays" class="modal fade next-aux-modal" tabindex="-1" role="dialog" aria-labelledby="credit-payment-title">
        <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h4 id="credit-payment-title" class="modal-title">Abonos del crédito</h4><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
            <div class="modal-body p-0">
                <div class="next-form-section"><p class="mb-1 font-weight-bold" id="credit-payment-context"></p><p class="text-muted mb-0" id="credit-payment-balance"></p></div>
                <input type="hidden" id="credito_id" value="">
                <input type="hidden" id="payment-request-key" value="">
                @can('pay_credit')
                <div class="next-form-section">
                    <h5>Registrar abono</h5>
                    <div id="credit-payment-error" class="alert alert-danger d-none" role="alert"></div>
                    <div class="row">
                        <div class="col-sm-6 form-group"><label for="montoPago">Importe</label><input type="number" min="0" step="0.01" id="montoPago" name="montoPago" class="form-control" aria-describedby="montoPago-error"><div id="montoPago-error" class="invalid-feedback"></div></div>
                        <div class="col-sm-6 form-group"><label for="fechaPago">Fecha del abono</label><input type="text" id="fechaPago" name="fechaPago" class="form-control" data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask aria-describedby="fechaPago-error"><div id="fechaPago-error" class="invalid-feedback"></div><small class="form-text text-muted">dd/mm/aaaa · opcional</small></div>
                        <div class="col-12 form-group"><label for="comprobantePago">Comprobante (opcional)</label><input type="file" id="comprobantePago" name="comprobantePago" class="form-control-file w-100" aria-describedby="comprobantePago-error"><div id="comprobantePago-error" class="invalid-feedback"></div></div>
                    </div>
                    <div class="next-toolbar-actions flex-wrap justify-content-end"><button type="button" id="btn-new-payment-draft" class="btn btn-outline-secondary btn-sm">Iniciar otro abono</button><button type="button" id="btn-save-pay" class="btn btn-primary btn-sm">Registrar abono</button></div>
                </div>
                @else
                <div class="next-form-section text-muted">Consulta de historial. No tiene permiso para registrar abonos.</div>
                @endcan
                <div class="next-form-section"><h5 class="mb-0">Historial de abonos</h5><div id="credit-history-status" class="text-muted mt-2" role="status"></div></div>
                <div id="body-items-load" class="table-responsive"><table class="table table-hover table-sm mb-0"><thead><tr><th class="text-center">#</th><th class="text-right">Importe</th><th class="text-center">Fecha</th><th class="text-center">Acciones</th></tr></thead><tbody id="body-pays"></tbody></table></div>
                <template id="template-pay"><tr><td data-id class="text-center"></td><td data-monto class="text-right"></td><td data-fecha class="text-center"></td><td class="text-center"><div class="dropdown next-row-actions"><button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones del abono"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu"><button type="button" class="dropdown-item" data-comprobante data-image_comprobante=""><i class="far fa-image next-row-action-item-icon"></i><span>Ver comprobante</span></button><button type="button" class="dropdown-item next-row-action-danger" data-delete=""><i class="fas fa-trash-alt next-row-action-item-icon"></i><span>Eliminar abono</span></button></div></div></td></tr></template>
                <template id="template-pay2"><tr><td data-id class="text-center"></td><td data-monto class="text-right"></td><td data-fecha class="text-center"> </td><td class="text-center"><div class="dropdown next-row-actions"><button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle next-row-actions-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" aria-label="Acciones del abono"><i class="fas fa-ellipsis-h"></i></button><div class="dropdown-menu dropdown-menu-right next-row-actions-menu"><a target="_blank" rel="noopener" href="" class="dropdown-item" data-comprobante><i class="fas fa-file-pdf next-row-action-item-icon"></i><span>Ver comprobante</span></a><button type="button" class="dropdown-item next-row-action-danger" data-delete=""><i class="fas fa-trash-alt next-row-action-item-icon"></i><span>Eliminar abono</span></button></div></div></td></tr></template>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button></div>
        </div></div>
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
