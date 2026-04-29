@extends('layouts.appAdmin2')

@section('openInvoice')
    menu-open
@endsection

@section('activeInvoice')
    active
@endsection

@section('activeListInvoiceAnnulled')
    active
@endsection

@section('title')
    Facturas anuladas por compras
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
    <h1 class="page-title">Facturas anuladas por compras</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de facturas anuladas</h5>
    @can('create_invoice')
    <a href="{{ route('invoice.create') }}" class="btn btn-outline-success btn-sm float-right" > <i class="fa fa-plus font-20"></i> Nuevo ingreso </a>
    @endcan
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
        <table class="table table-bordered table-hover" id="dynamic-table">
            <thead>
            <tr>
                <th>Fecha de factura</th>
                <th>Orden de compra</th>
                <th>Factura</th>
                <th>Tipo de entrada</th>
                <th>Proveedor</th>
                <th>Diferido</th>
                <th>Categoría</th>
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
    <script src="{{ asset('js/invoice/index_invoice_annulled.js') }}"></script>
@endsection
