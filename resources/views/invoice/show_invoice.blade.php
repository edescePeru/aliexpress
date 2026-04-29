@extends('layouts.appAdmin2')

@section('openInvoice')
    menu-open
@endsection

@section('activeInvoice')
    active
@endsection

@section('activeListInvoice')
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

        .modal-dialog {
            height: 100% !important;
        }

        .modal-content {
            height: auto;
            min-height: 100%;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Facturas por compra/Servicios</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Ver factura por compra/Servicio</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('invoice.index') }}"><i class="fa fa-archive"></i> Facturas por compra/Servicios</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Ver factura</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="entry_id" value="{{ $entry->id }}">
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
                                <div class="form-group" id="sandbox-container">
                                    <label for="date_invoice">Fecha de Factura</label>
                                    <div class="input-daterange" id="datepicker">
                                        <input type="text" class="form-control date-range-filter" id="date_invoice" name="date_invoice" value="{{ $entry->date_entry->format('d/m/Y') }}" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="purchase_order">Orden de Compra/Servicio</label>
                                    <input type="text" id="purchase_order" name="purchase_order" value="{{ $entry->purchase_order }}" class="form-control" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="supplier">Proveedor </label>
                                    <input type="text" value="{{ ( $entry->supplier_id == null ) ? "Sin Proveedor":$entry->supplier->business_name }}" class="form-control" readonly>

                                </div>
                                <div class="form-group">
                                    <label for="observation">Observación </label>
                                    <textarea name="observation" cols="30" class="form-control" style="word-break: break-all;" placeholder="Ingrese observación ...." readonly>{{$entry->observation}}</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="category_invoice">Categoría </label>
                                    <input type="text" id="supplier" name="supplier" value="{{ ( $entry->category_invoice_id == null ) ? "Sin categoría":$entry->category_invoice->name }}" class="form-control" readonly>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group row">
                                    <div class="col-md-8">
                                        <label for="invoice">Factura <span class="right badge badge-danger">(*)</span></label>
                                        <input type="text" id="invoice" name="invoice" class="form-control" value="{{ $entry->invoice }}" readonly>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-md-8">
                                        <label for="type_order">Tipo de Orden <span class="right badge badge-danger">(*)</span></label>
                                        <input type="hidden" id="entry_type" value="Por compra" name="entry_type" class="form-control" readonly>
                                        <input type="text" class="form-control" value="{{ ($entry->type_order == 'purchase') ? 'Orden de Compra': 'Orden de Servicio' }}" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="image">Imagen/PDF Factura </label>
                                    {{--<input type="file" id="image" name="image" class="form-control">--}}
                                    @if ( strtoupper(substr($entry->image,-3)) == 'PDF' )
                                        <a target="_blank" href="{{ asset('images/entries/'.$entry->image) }}" class="btn btn-outline-success float-right">Ver PDF</a>
                                    @else
                                        <img data-image src="{{ asset('images/entries/'.$entry->image) }}" alt="{{$entry->invoice}}" width="100px" height="100px">
                                    @endif
                                    {{--<img data-image src="{{ asset('images/entries/'.$entry->image) }}" alt="{{$entry->invoice}}" width="100px" height="100px">--}}
                                </div>
                                <div class="col-md-4">
                                    <label for="btn-currency"> Moneda <span class="right badge badge-danger">(*)</span></label> <br>
                                    <input id="btn-currency" readonly type="checkbox" {{ ($entry->currency_invoice === 'USD') ? 'checked':''}} name="currency_invoice" data-bootstrap-switch data-off-color="primary" data-on-text="DOLARES" data-off-text="SOLES" data-on-color="success">
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
                        <h3 class="card-title">Materiales</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Materiales</h3>
                                    </div>
                                    <!-- /.card-header -->
                                    <div class="card-body table-responsive p-0">
                                        <table id="tablita" class="table table-head-fixed text-nowrap">
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
                                                @foreach( $entry->details as $key => $detail )
                                                    <tr>
                                                        <td data-description>{{$detail->material_description}}</td>
                                                        <td data-quantity>{{$detail->entered_quantity}}</td>
                                                        <td data-unit>{{$detail->unit}}</td>
                                                        <td data-price>{{$detail->unit_price}}</td>
                                                        <td data-subtotal>{{ $detail->sub_total }}</td>
                                                        <td data-taxes>{{ $detail->taxes }}</td>
                                                        <td data-total>{{ $detail->total }}</td>

                                                    </tr>
                                                @endforeach

                                            </tbody>
                                            <template id="materials-selected">
                                                <tr>
                                                    <td data-description>John Doe</td>
                                                    <td data-quantity>John Doe</td>
                                                    <td data-unit>11-7-2014</td>
                                                    <td data-price>11-7-2014</td>
                                                    <td data-subtotal>11-7-2014</td>
                                                    <td data-taxes>11-7-2014</td>
                                                    <td data-total>11-7-2014</td>
                                                    <td>
                                                        <button type="button" data-deleteNew="" class="btn btn-outline-warning btn-sm"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </table>
                                    </div>
                                    <!-- /.card-body -->
                                    <hr>
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
                                                        <td ><span class="moneda">{{ $entry->currency_invoice }}</span> <span id="subtotal">{{ $entry->sub_total }}</span> </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Igv: </th>
                                                        <td ><span class="moneda">{{ $entry->currency_invoice }}</span> <span id="taxes">{{ $entry->taxes }}</span> </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Total: </th>
                                                        <td ><span class="moneda">{{ $entry->currency_invoice }}</span> <span id="total">{{ $entry->total }}</span> </td>
                                                    </tr>

                                                </table>
                                            </div>
                                        </div>
                                        <!-- /.col -->
                                    </div>
                                </div>
                                <!-- /.card -->
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
        {{--<div class="row">
            <div class="col-12">
                <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                <button type="button" id="btn-submit" class="btn btn-outline-success float-right">Guardar cambios</button>
            </div>
        </div>--}}
        <!-- /.card-footer -->
    </form>

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
                                <input type="text" id="material_GroupSelected" name="material_GroupSelected" class="form-control" />
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
        if ( $('#date_invoice') === '' )
        {
            $('#date_invoice').attr("value", moment().format('DD/MM/YYYY'));
        }

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
        $('#type_order').select2({
            placeholder: "Seleccione un tipo",
        });
        $('#category_invoice').select2({
            placeholder: "Seleccione una categoría",
        });
        $('#material_unit').select2({
            placeholder: "Seleccione unidad",
        });
    </script>
    <script src="{{ asset('js/invoice/edit_invoice.js') }}"></script>

@endsection
