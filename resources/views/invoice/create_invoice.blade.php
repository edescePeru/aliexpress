@extends('layouts.appAdmin2')

@section('openInvoice')
    menu-open
@endsection

@section('activeInvoice')
    active
@endsection

@section('activeCreateInvoice')
    active
@endsection

@section('title')
    Factura por Compras-Servicios
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/typehead/typeahead.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
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

        /*.modal-dialog {
            height: 100% !important;
        }*/

        /*.modal-content {
            height: auto;
            min-height: 100%;
        }*/
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Facturas por compra/servicio</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Crear nueva factura por compra/servicio</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('invoice.index') }}"><i class="fa fa-archive"></i> Facturas por compra/servicio</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nueva factura</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal" data-url="{{ route('invoice.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Datos generales</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group " id="sandbox-container">
                                    <label for="date_invoice">Fecha de Factura</label>
                                    <div class="input-daterange" id="datepicker">
                                        <input type="text" class="form-control date-range-filter" id="date_invoice" name="date_invoice">
                                    </div>
                                </div>
                                {{--<div class="form-group">
                                    <label for="purchase_order">Orden de Compra/servicio</label>
                                    <input type="text" id="purchase_order" name="purchase_order" class="form-control">
                                </div>--}}
                                <div class="form-group">
                                    <label for="supplier">Proveedor </label>
                                    <select id="supplier" name="supplier_id" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $suppliers as $supplier )
                                            <option value="{{ $supplier->id }}">{{ $supplier->business_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="observation">Observación </label>
                                    <textarea name="observation" cols="30" class="form-control" style="word-break: break-all;" placeholder="Ingrese observación ...."></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="category_invoice">Categoría </label>
                                    <div class="input-group">
                                        <select id="category_invoice" name="category_invoice_id" class="form-control select2" style="width: 90%;">
                                            <option></option>
                                            @foreach( $categories as $category )
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#modalCategoria">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group row">
                                    <div class="col-md-8">
                                        <label for="invoice">Factura <span class="right badge badge-danger">(*)</span></label>
                                        <input type="text" id="invoice" name="invoice" class="form-control">
                                    </div>
                                    {{--<div class="col-md-4">
                                        <label for="btn-grouped"> Diferido <span class="right badge badge-danger">(*)</span></label> <br>
                                        <input id="btn-grouped" type="checkbox" name="deferred_invoice" data-bootstrap-switch data-off-color="danger" data-on-text="SI" data-off-text="NO" data-on-color="success">
                                    </div>--}}
                                </div>

                                <div class="form-group">
                                    <label for="type_order">Tipo de Orden <span class="right badge badge-danger">(*)</span></label>
                                    <input type="hidden" id="entry_type" value="Por compra" name="entry_type" class="form-control" readonly>
                                    <select id="type_order" name="type_order" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        <option value="purchase">Por compra</option>
                                        <option value="service">Por servicio</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="image">Imagen/PDF Factura </label>
                                    <input type="file" id="image" name="image" class="form-control">
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-4">
                                        <label for="btn-currency"> Moneda <span class="right badge badge-danger">(*)</span></label> <br>
                                        <input id="btn-currency" type="checkbox" name="currency_invoice" data-bootstrap-switch data-off-color="primary" data-on-text="DOLARES" data-off-text="SOLES" data-on-color="success" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-4">
                                        <label for="pv_cash_box_id">
                                            Caja <span class="right badge badge-danger">(*)</span>
                                        </label>

                                        <select id="pv_cash_box_id"
                                                name="pv_cash_box_id"
                                                class="form-control select2"
                                                style="width: 100%;">
                                            <option value=""></option>

                                            @foreach($cashBoxes as $b)
                                                <option
                                                        value="{{ $b['id'] }}"
                                                        data-type="{{ $b['type'] }}"
                                                        data-uses_subtypes="{{ $b['uses_subtypes'] ? 1 : 0 }}"
                                                >
                                                    {{ $b['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4" id="pv_cash_box_subtype_wrap" style="display:none;">
                                        <label for="pv_cash_box_subtype_id">
                                            Canal / Subtipo <span class="right badge badge-danger">(*)</span>
                                        </label>

                                        <select id="pv_cash_box_subtype_id"
                                                name="pv_cash_box_subtype_id"
                                                class="form-control select2"
                                                style="width: 100%;">
                                            <option value=""></option>
                                        </select>

                                        <small class="text-muted">
                                            Solo aplica cuando la caja es bancaria y usa subtipos.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <div class="col-md-12">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Materiales/Servicios</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="material_search">Ingresar material/Servicio <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" id="material_search" onkeyup="mayus(this);" class="form-control">

                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="material_unit">Unidad <span class="right badge badge-danger">(*)</span></label>
                                    <select id="material_unit" name="material_unit" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $unitMeasures as $unitMeasure )
                                            <option value="{{ $unitMeasure->id }}">{{ $unitMeasure->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="quantity">Cantidad <span class="right badge badge-danger">(*)</span></label>
                                    <input type="number" id="quantity" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="price">Precio Total C/IGV <span class="right badge badge-danger">(*)</span></label>
                                    <input type="number" id="price" class="form-control" placeholder="0.00" min="0" value="" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                    ">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label for="btn-add"> &nbsp; </label>
                                <button type="button" id="btn-add" class="btn btn-block btn-outline-primary">Agregar <i class="fas fa-arrow-circle-right"></i></button>
                            </div>

                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Materiales/Servicios</h3>
                                    </div>
                                    <!-- /.card-header -->
                                    <div class="card-body table-responsive p-0" style="height: 300px;">
                                        <table class="table table-head-fixed text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th>Codigo</th>
                                                    <th>Material</th>
                                                    <th>Cantidad</th>
                                                    <th>Und</th>
                                                    <th>Precio Unit.</th>
                                                    <th>Total sin Imp.</th>
                                                    <th>Total Imp.</th>
                                                    <th>Importe</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="body-materials">


                                            </tbody>
                                            <template id="materials-selected">
                                                <tr>
                                                    <td data-id>183</td>
                                                    <td data-description>John Doe</td>
                                                    <td data-quantity>John Doe</td>
                                                    <td data-unit>11-7-2014</td>
                                                    <td data-price>11-7-2014</td>
                                                    <td data-subtotal>11-7-2014</td>
                                                    <td data-taxes>11-7-2014</td>
                                                    <td data-total>11-7-2014</td>
                                                    <td>
                                                        <button type="button" data-delete="" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </table>
                                    </div>
                                    <!-- /.card-body -->
                                </div>
                                <!-- /.card -->
                            </div>
                        </div>
                        <div class="row">
                            <!-- accepted payments column -->
                            <div class="col-6">

                            </div>
                            <!-- /.col -->
                            <div class="col-6">
                                <p class="lead">Resumen de factura</p>

                                <div class="table-responsive">
                                    <table class="table">
                                        <tr>
                                            <th style="width:50%">Subtotal: </th>
                                            <td ><span class="moneda">PEN</span> <span id="subtotal">0.00</span> </td>
                                        </tr>
                                        <tr>
                                            <th>Igv: </th>
                                            <td ><span class="moneda">PEN</span> <span id="taxes">0.00</span> </td>
                                        </tr>
                                        <tr>
                                            <th>Total: </th>
                                            <td ><span class="moneda">PEN</span> <span id="total">0.00</span> </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <!-- /.col -->
                        </div>
                        <!-- /.row -->
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                <button type="button" id="btn-submit" class="btn btn-outline-success float-right">Guardar factura compra/servicio</button>
            </div>
        </div>
        <!-- /.card-footer -->
    </form>

    <div id="modalAddItems" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Agregar items</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="col-sm-12 control-label" for="material_selected"> Material </label>

                            <div class="col-sm-12">
                                <input type="text" id="material_selected" name="material_selected" class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="col-sm-12 control-label" for="quantity_selected"> Cantidad </label>

                            <div class="col-sm-12">
                                <input type="text" id="quantity_selected" name="quantity_selected" class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="col-sm-12 control-label" for="price_selected"> Precio </label>

                            <div class="col-sm-12">
                                <input type="text" id="price_selected" name="price_selected" class="form-control" />
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-12">
                            <label class="col-sm-12 control-label"> Items y ubicaciones </label>
                        </div>
                    </div>

                    {{--<div class="p-1 row">
                        <div class="col-sm-5">
                            <div class="col-md-12">
                                <input type="text" name="series[]" class="form-control" />
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="col-md-12">
                                <input type="text" name="locations[]" class="form-control locations" />
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="col-md-12">
                                <button type="button" id="btnNew" class="btn btn-block btn-success"><i class="fa fa-plus"></i> Agregar</button>
                            </div>
                        </div>
                    </div>--}}

                    <div id="body-items"></div>
                    <template id="template-item">
                        <div class="row p-1">
                            <div class="col-sm-3">
                                <div class="col-md-12">
                                    <input type="text" name="series[]" data-series class="form-control" />
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="col-md-12">
                                    <input type="text" name="locations[]" data-locations class="form-control locations" />
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <select class="form-control select2" data-states style="width: 100%;">
                                    <option value="good" selected="selected">Buena estado</option>
                                    <option value="bad">Deficiente</option>
                                </select>

                            </div>
                        </div>

                    </template>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btn-saveItems" class="btn btn-outline-primary">Agregar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalAddGroupItems" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Agregar items</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="col-sm-12 control-label" for="material_GroupSelected"> Material </label>

                            <div class="col-sm-12">
                                <input type="text" id="material_GroupSelected" onkeyup="mayus(this);" name="material_GroupSelected" class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="col-sm-12 control-label" for="quantity_GroupSelected"> Cantidad </label>

                            <div class="col-sm-12">
                                <input type="text" id="quantity_GroupSelected" name="quantity_GroupSelected" class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="col-sm-12 control-label" for="price_GroupSelected"> Precio </label>

                            <div class="col-sm-12">
                                <input type="text" id="price_GroupSelected" name="price_GroupSelected" class="form-control" />
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-12">
                            <label class="col-sm-12 control-label"> Seleccione la ubicación </label>
                        </div>
                    </div>

                    <div class="row p-1">
                        <div class="col-sm-9">
                            <div class="col-md-12">
                                <input type="text" id="locationGroup" name="locationGroup" data-locationGroup class="form-control locationGroup" />
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <select class="form-control select2" id="stateGroup" data-stateGroup style="width: 100%;">
                                <option value="good" selected="selected">Buena estado</option>
                                <option value="bad">Deficiente</option>
                            </select>
                        </div>
                    </div>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btn-saveGroupItems" class="btn btn-outline-primary">Agregar</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Crear Categoria -->
    <div class="modal fade" id="modalCategoria" tabindex="-1" role="dialog" aria-labelledby="modalCategoriaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalCategoriaLabel">Crear Categoría</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreateCategoria" data-url="{{ route('categoryInvoice.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>Categoría <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" name="name" onkeyup="mayus(this);" placeholder="Ejm: ELECTRICIDAD">
                            </div>

                            <div class="col-md-6">
                                <label>Descripción</label>
                                <input type="text" class="form-control" name="description" onkeyup="mayus(this);" placeholder="Ejm: Servicios de Luz">
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" id="btnSaveCategoria" class="btn btn-outline-success">Guardar</button>
                            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>

@endsection

@section('scripts')
    <script src="{{asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
    <script>
        $('#date_invoice').attr("value", moment().format('DD/MM/YYYY'));

        $('#sandbox-container .input-daterange').datepicker({
            todayBtn: "linked",
            clearBtn: true,
            language: "es",
            multidate: false,
            autoclose: true,
            todayHighlight: true,
            defaultViewDate: moment().format('L')
        });

        $("input[data-bootstrap-switch]").each(function(){
            $(this).bootstrapSwitch();
        });
        $('#supplier').select2({
            placeholder: "Seleccione un proveedor",
        });
        $('#material_unit').select2({
            placeholder: "Seleccione unidad",
        });
        $('#type_order').select2({
            placeholder: "Seleccione un tipo",
        });
        $('#category_invoice').select2({
            placeholder: "Seleccione una categoría",
        });
    </script>

    <script>
        window.PV_CASHBOXES = @json($cashBoxes);
        window.PV_SUBTYPES  = @json($subtypes);
    </script>

    <script src="{{ asset('js/invoice/invoice.js') }}?v={{ time() }}"></script>

@endsection
