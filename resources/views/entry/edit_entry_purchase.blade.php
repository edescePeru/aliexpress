@extends('layouts.appAdmin2')

@section('openEntryPurchase')
    menu-open
@endsection

@section('activeEntryPurchase')
    active
@endsection

@section('activeCreateEntryPurchase')
    active
@endsection

@section('title')
    Entrada por Compras
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
    <h1 class="page-title">Entrada por compra</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Modificar entrada por compra</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('entry.purchase.index') }}"><i class="fa fa-archive"></i> Entradas por compra</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar entrada</li>
    </ol>
@endsection

@section('content')

    <div class="row">
        <div class="col-md-12">
            <form id="formEdit" data-url="{{ route('entry.purchase.update') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="entry_id" value="{{ $entry->id }}">
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
                                        <input type="text" class="form-control date-range-filter" id="date_invoice" name="date_invoice" value="{{ $entry->date_entry->format('d/m/Y') }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="referral_guide">Guía de remisión</label>
                                    <input type="text" id="referral_guide" name="referral_guide" class="form-control" value="{{ $entry->referral_guide }}">
                                </div>
                                <div class="form-group">
                                    <label for="purchase_order">Orden de Compra</label>
                                    <input type="text" id="purchase_order" name="purchase_order" class="form-control" value="{{ $entry->purchase_order }}">
                                </div>
                                <div class="form-group">
                                    <label for="supplier">Proveedor </label>
                                    <select id="supplier" name="supplier_id" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $suppliers as $supplier )
                                            <option value="{{ $supplier->id }}" {{ ($supplier->id === $entry->supplier_id) ? 'selected':'' }}>{{ $supplier->business_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group row">
                                    <div class="col-md-8">
                                        <label for="invoice">Factura <span class="right badge badge-danger">(*)</span></label>
                                        <input type="text" id="invoice" name="invoice" class="form-control" value="{{ $entry->invoice }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="btn-grouped"> Diferido <span class="right badge badge-danger">(*)</span></label> <br>
                                        <input id="btn-grouped" type="checkbox" name="deferred_invoice" {{ ($entry->deferred_invoice==='on') ? 'checked':'' }} data-bootstrap-switch data-off-color="danger" data-on-text="SI" data-off-text="NO" data-on-color="success">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="entry_type">Tipo de Ingreso <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" id="entry_type" value="Por compra" name="entry_type" class="form-control" readonly>
                                </div>

                                <div class="form-group">
                                    <label for="image">Imagen/PDF Factura </label>
                                    <input type="file" id="image" name="image" class="form-control">
                                    @if ( substr($entry->image,-3) == 'pdf' )
                                        <a href="{{ asset('images/entries/'.$entry->image) }}" class="btn btn-outline-success float-right">Ver PDF</a>
                                    @else
                                        <img data-image src="{{ asset('images/entries/'.$entry->image) }}" alt="{{$entry->invoice}}" width="100px" height="100px">
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="observation">Observación </label>
                                    <textarea name="observation" cols="30" class="form-control" style="word-break: break-all;" placeholder="Ingrese observación ....">{{ $entry->observation }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="imageOb">Imagen Observación</label>
                                    <input type="file" id="imageOb" name="imageOb" class="form-control">
                                    {{--<img data-image src="{{ asset('images/entries/observations/'.$entry->imageOb) }}" alt="{{$entry->invoice}}" width="100px" height="100px">
                                    --}}@if ( substr($entry->imageOb,-3) == 'pdf' )
                                        <a href="{{ asset('images/entries/observations/'.$entry->imageOb) }}" class="btn btn-outline-success float-right">Ver PDF</a>
                                    @else
                                        <img data-image src="{{ asset('images/entries/observations/'.$entry->imageOb) }}" alt="{{$entry->invoice}}" width="100px" height="100px">
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <button type="submit" id="btn-submitForm" class="btn btn-outline-success float-right">Guardar datos generales</button>
                    </div>

                </div>
            <!-- /.card -->
            </form>
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="material_search">Buscar material <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" id="material_search" class="form-control rounded-0 typeahead">

                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group" id="sandbox-container2">
                                <label for="quantity">F. Vence </label>
                                <div class="input-daterange" id="datepicker2">
                                    <input type="text" class="form-control date-range-filter" id="date_vence" name="date_vence">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label for="quantity">Cantidad </label>
                                <input type="number" id="quantity" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label for="price">Precio IGV </label>
                                <input type="number" id="price" class="form-control" placeholder="0.00" min="0" value="" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                    this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                    ">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <label for="btn-grouped2"> Agrupado </label> <br>
                            <input id="btn-grouped2" class="col-md-12" type="checkbox" name="my-checkbox" data-bootstrap-switch data-off-color="danger" data-on-text="SI" data-off-text="NO" data-on-color="success">
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="quantity">Almacen </label>
                                <input type="text" id="almacen" name="almacen" data-locationGroup class="form-control locationGroup" />

                            </div>
                        </div>
                        <div class="col-md-1">
                            <label for="btn-add"> &nbsp; </label>
                            <button type="button" id="btn-add" class="btn btn-block btn-outline-primary"> <i class="fas fa-arrow-circle-right"></i></button>
                        </div>

                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Materiales</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body table-responsive p-0">
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
                                        @foreach( $entry->details as $detail )
                                            <tr>
                                                <td data-code>{{$detail->material_code}}</td>
                                                <td data-description>{{$detail->material_description}}</td>
                                                <td data-quantity>{{$detail->entered_quantity}}</td>
                                                <td data-unit>{{$detail->unit}}</td>
                                                <td data-price>{{ $detail->unit_price}}</td>
                                                <td data-subtotal>{{ $detail->sub_total }}</td>
                                                <td data-taxes>{{ $detail->taxes }}</td>
                                                <td data-total>{{ $detail->total }}</td>
                                                <td><button data-deleteOld="{{ $detail->id }}" data-entry="{{ $entry->id }}" type="button" class="btn btn-sm btn-danger" ><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                        @endforeach

                                        </tbody>
                                        <template id="materials-selected">
                                            <tr>
                                                <td data-code>183</td>
                                                <td data-description>John Doe</td>
                                                <td data-quantity>John Doe</td>
                                                <td data-unit>11-7-2014</td>
                                                <td data-price>11-7-2014</td>
                                                <td data-subtotal>11-7-2014</td>
                                                <td data-taxes>11-7-2014</td>
                                                <td data-total>11-7-2014</td>
                                                <td>
                                                    <button type="button" data-delete="" class="btn btn-sm btn-warning"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        </template>
                                    </table>
                                </div>
                                <hr>
                                <div class="card-footer">
                                    <button type="button" id="btn-submit" data-entry="{{ $entry->id }}" class="btn btn-outline-success float-right">Guardar nuevos materiales</button>
                                </div>
                            </div>
                            <!-- /.card -->

                        </div>
                    </div>
                    <!-- /.row -->

                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
            <!-- /.card-body -->
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
                                <td><span class="moneda">{{ $entry->currency_invoice }}</span> <span id="subtotal">{{ $entry->sub_total }}</span></td>
                            </tr>
                            <tr>
                                <th>Igv: </th>
                                <td><span class="moneda">{{ $entry->currency_invoice }}</span> <span id="taxes">{{ $entry->taxes }}</span></td>
                            </tr>
                            <tr>
                                <th>Total: </th>
                                <td><span class="moneda">{{ $entry->currency_invoice }}</span> <span id="total">{{ $entry->total }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.card-body -->
        </div>

    </div>

    <!-- /.card-footer -->

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

        $('#sandbox-container2 .input-daterange').datepicker({
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
            allowClear: true
        })
    </script>
    <script src="{{ asset('js/entry/edit_entry_purchase.js') }}?v={{ time() }}"></script>

@endsection
