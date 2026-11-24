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
    Control Créditos
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

@endsection



@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Proveedores · créditos</span><h1 class="page-title">Créditos registrados</h1><p class="next-page-description">Consulta y administración de créditos de la empresa activa según sus permisos.</p></div>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de créditos de proveedores</h5>
    {{--@can('create_invoice')
    <a href="{{ route('invoice.create') }}" class="btn btn-outline-success btn-sm float-right" > <i class="fa fa-plus font-20"></i> Nuevo ingreso </a>
    @endcan--}}
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
    <div class="next-list-toolbar"><div class="row"><div class="col-md-7 col-xl-5" id="sandbox-container2">
        <label for="start2">Fechas de emisión</label><div class="input-daterange input-group" id="datepicker2">
            <input type="text" class="form-control form-control-sm date-range-filter2" id="start2" name="start" aria-label="Desde">
            <div class="input-group-append input-group-prepend"><span class="input-group-text">al</span></div>
            <input type="text" class="form-control form-control-sm date-range-filter2" id="end2" name="end" aria-label="Hasta">
        </div>
    </div></div></div>
    <div id="registered-list-feedback" class="next-form-section d-none" role="status"></div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm" id="dynamic-table2">
            <thead>
            <tr>
                <th>Proveedor</th>
                <th>Factura</th>
                <th># O.C.</th>
                <th>Importe S/.</th>
                <th>Importe $</th>
                <th>Fecha Emisión</th>
                <th>Fecha Vencimiento</th>
                <th>Plazo de pago</th>
                <th>Días para vencer</th>
                <th>Observación</th>
                <th>Estado de crédito</th>
                <th>Observación 2</th>
                <th>Imagen Factura</th>
                <th>Fecha Pago</th>
                <th>Imagen Pago</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>

    <div id="modalPay" class="modal fade next-aux-modal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Marcar crédito como pagado</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form class="d-flex flex-column overflow-hidden" id="formPay" data-url="{{ route('credit.control.paid') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body next-form-section"><p class="text-muted">Marca manual de pago. No registra un abono ni elimina los existentes.</p>
                        <div class="alert alert-danger d-none" data-credit-error role="alert"></div>
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
                                <input type="file" id="image_paid" name="image_paid" class="form-control-file w-100">
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
                        <button type="button" id="btn-pay" class="btn btn-primary">Marcar pagado</button>
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

    <div id="modalEdit" class="modal fade next-aux-modal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Editar fechas y observaciones</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form class="d-flex flex-column overflow-hidden" id="formEdit" data-url="{{ route('credit.control.update') }}">
                    @csrf
                    <div class="modal-body next-form-section">
                        <div class="alert alert-danger d-none" data-credit-error role="alert"></div>
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
                        <button type="button" id="btn-submit" class="btn btn-primary">Guardar cambios</button>
                        @endcan
                    </div>
                </form>
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

    <div id="modalItems" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Listado de detalles</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body table-responsive">
                    <div class="card-body table-responsive p-0" >
                        <table class="table table-head-fixed text-nowrap">
                            <thead>
                            <tr>
                                <th>Material</th>
                                <th>Cantidad</th>
                                <th>Und</th>
                                <th>Precio Unit.</th>
                                <th>Total sin Imp.</th>
                                <th>Total Imp.</th>
                                <th>Importe</th>
                            </tr>
                            </thead>
                            <tbody id="body-materials">


                            </tbody>

                        </table>
                        <template id="template-item">
                            <tr>
                                <td data-description></td>
                                <td data-quantity></td>
                                <td data-unit></td>
                                <td data-price></td>
                                <td data-subtotal></td>
                                <td data-taxes></td>
                                <td data-total></td>
                            </tr>
                        </template>
                    </div>
                    <!-- /.card-body -->
                    <div class="row">
                        <!-- accepted payments column -->
                        <div class="col-6">

                        </div>
                        <!-- /.col -->
                        <div class="col-6">
                            <p class="lead">Resumen de factura</p>

                            <div class="table-responsive" id="body-summary">

                            </div>
                            <template id="template-summary">
                                <table class="table">
                                    <tr>
                                        <th style="width:50%">Subtotal: </th>
                                        <td data-subtotal="subtotal"></td>
                                    </tr>
                                    <tr>
                                        <th>Igv: </th>
                                        <td data-taxes="taxes"></td>
                                    </tr>
                                    <tr>
                                        <th>Total: </th>
                                        <td data-total="total"></td>
                                    </tr>
                                </table>
                            </template>
                        </div>
                        <!-- /.col -->
                    </div>
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
@endsection

@section('scripts')
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
    <script src="{{ asset('js/credit/permissions.js') }}?v=credit-ui-1"></script>
    <script src="{{ asset('js/credit/index_creditSupplier.js') }}?v=credit-attachment-1"></script>
    <script>
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
@endsection
