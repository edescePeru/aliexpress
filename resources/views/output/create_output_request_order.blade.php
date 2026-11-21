@extends('layouts.appAdmin2')

@section('openOrderExecutions')
    menu-open
@endsection

@section('activeOrderExecutions')
    active
@endsection

@section('activeListOrderExecutions')
    active
@endsection

@section('title')
    Solicitud de salida
@endsection

@section('styles-plugins')
    <!-- daterange picker -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">

    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/typehead/typeahead.css') }}">
@endsection

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }

        .modal-dialog {
            height: 90% !important;
        }

        .modal-content {
            height: auto;
            min-height: 100%;
        }
        #modal-dialog-custom {
            height: 60% !important;
        }
        #change-color {
            height: 60% !important;
        }
        .modal-header-success {
            color:#fff;
            padding:9px 15px;
            border-bottom:1px solid #eee;
            background-color: #5cb85c;
            -webkit-border-top-left-radius: 5px;
            -webkit-border-top-right-radius: 5px;
            -moz-border-radius-topleft: 5px;
            -moz-border-radius-topright: 5px;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
        .modal-header-warning {
            color:#fff;
            padding:9px 15px;
            border-bottom:1px solid #eee;
            background-color: #f0ad4e;
            -webkit-border-top-left-radius: 5px;
            -webkit-border-top-right-radius: 5px;
            -moz-border-radius-topleft: 5px;
            -moz-border-radius-topright: 5px;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
        .modal-header-danger {
            color:#fff;
            padding:9px 15px;
            border-bottom:1px solid #eee;
            background-color: #d9534f;
            -webkit-border-top-left-radius: 5px;
            -webkit-border-top-right-radius: 5px;
            -moz-border-radius-topleft: 5px;
            -moz-border-radius-topright: 5px;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Solicitud de salida de la orden {{ $quote->order_execution }}</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Crear nueva solicitud de salida</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('order.execution.index') }}"><i class="fa fa-archive"></i> Órdenes de ejecución</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nueva solicitud</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <input type="hidden" id="materials" value="{{ json_encode($materials) }}">

    <form id="formCreate" class="form-horizontal" data-url="{{ route('output.request.store') }}" enctype="multipart/form-data">
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
                                <div class="form-group">
                                    <input type="hidden" name="indicator" value="orn">
                                    <label for="execution_order">Orden de ejecución <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" id="execution_order" name="execution_order" value="{{ $quote->order_execution }}" class="form-control" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="request_date">Fecha de Solicitud <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" id="request_date" name="request_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="requesting_user">Usuario solicitante <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" id="requesting_user" name="requesting_user" value="{{ Auth::user()->name }}" class="form-control" readonly>
                                </div>

                                <div class="form-group">
                                    <label for="responsible_user">Usuario responsable <span class="right badge badge-danger">(*)</span></label>
                                    <select id="responsible_user" name="responsible_user" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $users as $user )
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            @foreach($quote->equipments as $equipment)
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">EQUIPO: {{ $equipment->description }}</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <!-- /.card-header -->
                                    <div class="card-body table-responsive p-0">
                                        <table class="table table-head-fixed">
                                            <thead>
                                            <tr>
                                                <th>Material</th>
                                                <th>Largo</th>
                                                <th>Ancho</th>
                                                <th>Cantidad Total</th>
                                                <th>Detalles</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ( $equipment->materials as $key => $material )
                                                <tr>
                                                    <td>{{ $material->material->full_description }}</td>
                                                    <td>{{ $material->length }}</td>
                                                    <td>{{ $material->width }}</td>
                                                    <td>{{ (float)$material->quantity*(float)$equipment->quantity }}</td>
                                                    <td>
                                                        <button type="button" data-toggle="tooltip" data-placement="top" title="Detalle de cantidades" class="btn btn-success" data-show data-name="{{ $material->material->full_description }}" data-quote="{{ $quote->id }}" data-material="{{ $material->material_id }}"><i class="fas fa-search-plus"></i></button>
                                                        

                                                    </td>
                                                    </tr>
                                            @endforeach
                                            @foreach ( $equipment->consumables as $key => $consumable )
                                                @if ($consumable->material->category_id == 2 && trim($consumable->material->subcategory_id) == 22)
                                                <tr>
                                                    <td>{{ $consumable->material->full_description }}</td>
                                                    <td>  --  </td>
                                                    <td>  --  </td>
                                                    <td>{{ (float)$consumable->quantity*(float)$equipment->quantity }}</td>
                                                    <td>
                                                        <button type="button" data-toggle="tooltip" data-placement="top" title="Detalle de cantidades" class="btn btn-success" data-show data-name="{{ $consumable->material->full_description }}" data-quote="{{ $quote->id }}" data-material="{{ $consumable->material_id }}"><i class="fas fa-search-plus"></i></button>
                                                        

                                                    </td>
                                                </tr>
                                                @endif
                                            @endforeach
                                            </tbody>
                                        </table>

                                    </div>
                                    <!-- /.card-body -->
                                </div>
                                <!-- /.card -->
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            @endforeach
            {{--<div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Materiales de la orden de ejecucion</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <!-- /.card-header -->
                                    <div class="card-body table-responsive p-0">
                                        <table class="table table-head-fixed">
                                            <thead>
                                            <tr>
                                                <th>N°</th>
                                                <th>Material</th>
                                                <th>Cantidad Total</th>
                                                <th>Detalles</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ( $materials as $key => $material )
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $material['material'] }}</td>
                                                    <td>{{ $material['quantity'] }}</td>
                                                    <td>
                                                        <button type="button" data-toggle="tooltip" data-placement="top" title="Detalle de cantidades" class="btn btn-success" data-show data-name="{{ $material['material'] }}" data-quote="{{ $quote->id }}" data-material="{{ $material['material_id'] }}"><i class="fas fa-search-plus"></i></button>
                                                        

                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>

                                    </div>
                                    <!-- /.card-body -->
                                </div>
                                <!-- /.card -->
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>--}}
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Consumibles de la orden de ejecución</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <!-- /.card-header -->
                                    <div class="card-body table-responsive p-0">
                                        <table class="table table-head-fixed text-nowrap">
                                            <thead>
                                            <tr>
                                                <th>N°</th>
                                                <th>Consumible</th>
                                                <th>Cantidad</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ( $consumables as $key => $consumable )
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $consumable['material'] }}</td>
                                                    <td>{{ $consumable['quantity'] }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- /.card-body -->
                                </div>
                                <!-- /.card -->
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Servicios Adicionales de la orden de ejecución</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <!-- /.card-header -->
                                    <div class="card-body table-responsive p-0">
                                        <table class="table table-head-fixed">
                                            <thead>
                                            <tr>
                                                <th>N°</th>
                                                <th>Material</th>
                                                <th>Cantidad Total</th>
                                                {{--<th>Detalles</th>--}}
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ( $turnstiles as $key => $turnstile )
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $turnstile['material'] }}</td>
                                                    <td>{{ $turnstile['quantity'] }}</td>
                                                    {{--<td>
                                                        <button type="button" data-toggle="tooltip" data-placement="top" title="Detalle de cantidades" class="btn btn-success" data-show data-name="{{ $turnstile['material'] }}" data-quote="{{ $quote->id }}" data-material="{{ $turnstile['material_id'] }}"><i class="fas fa-search-plus"></i></button>
                                                        

                                                    </td>--}}
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>

                                    </div>
                                    <!-- /.card-body -->
                                </div>
                                <!-- /.card -->
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
                    <div class="card-body" id="element_loader">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="equipments_order">Equipo del cual se solicitará el material <span class="right badge badge-danger">(*)</span></label>
                                    <select id="equipments_order" name="equipments_order" class="form-control select2" style="width: 100%;">
                                        <option></option>
                                        @foreach( $quote->equipments as $equipment )
                                            <option value="{{ $equipment->id }}">{{ $equipment->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="material_search">Buscar material <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" id="material_search" class="form-control rounded-0 typeahead">

                                </div>
                            </div>
                            {{--<div class="col-md-3">
                                <div class="form-group">
                                    <label for="quantity">Cantidad Ingresada <span class="right badge badge-danger">(*)</span></label>
                                    <input type="text" id="quantity" class="form-control">
                                </div>
                            </div>--}}
                            <div class="col-md-4">
                                <label for="btn-add"> Seleccione: </label><br>
                                <button type="button" id="btn-add" class="btn btn-outline-primary">Completo <i class="fas fa-arrow-circle-right"></i></button>
                                <button type="button" id="btn-add-scrap" class="btn btn-outline-primary">Retazo <i class="fas fa-arrow-circle-right"></i></button>
                                <button type="button" id="btn-add-custom" class="btn btn-outline-primary">Personaliz.<i class="fas fa-arrow-circle-right"></i></button>
                            </div>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Materiales</h3>
                                    </div>
                                    <!-- /.card-header -->
                                    <div class="card-body table-responsive p-0" style="height: 300px;">
                                        <table class="table table-head-fixed">
                                            <thead>
                                                <tr>
                                                    <th>Equipo</th>
                                                    <th>Material</th>
                                                    <th>Item</th>
                                                    <th>Precio</th>
                                                    <th>Estado</th>
                                                    <th>Largo</th>
                                                    <th>Ancho</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="body-materials">
                                                <template id="materials-selected">
                                                    <tr>
                                                        <td data-equipment></td>
                                                        <td data-description></td>
                                                        <td data-item></td>
                                                        <td data-price></td>
                                                        <td data-state></td>
                                                        <td data-length></td>
                                                        <td data-width></td>
                                                        <td>
                                                            <button type="button" data-delete="" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                                                        </td>
                                                    </tr>
                                                </template>

                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- /.card-body -->
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
        <div class="row">
            <div class="col-12">
                <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                <button type="button" id="btn-submit" class="btn btn-outline-success float-right">Guardar solicitud de salida</button>
            </div>
        </div>
        <!-- /.card-footer -->
    </form>

    <div id="modalAddItems" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Seleccionar items</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="col-sm-12 control-label" for="material_selected"> Material </label>
                            <input type="hidden" name="equipment" id="equipment">
                            <input type="hidden" name="equipment_name" id="equipment_name">

                            <div class="col-sm-12">
                                <input type="text" id="material_selected" name="material_selected" class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="col-sm-12 control-label" for="material_selected_quantity"> Cantidad </label>

                            <div class="col-sm-12">
                                <input type="text" id="material_selected_quantity" name="material_selected_quantity" class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-2" id="show_btn_request_quantity">
                            <label class="col-sm-12 control-label" for="material_selected_quantity"> &nbsp;&nbsp;&nbsp; </label>

                            <div class="col-sm-12">
                                <button type="button" id="btn-request-quantity" class="btn btn-outline-primary">Pedir <i class="fas fa-arrow-circle-right"></i></button>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row" id="show-btn-follow">
                        <div class="col-md-4 offset-4" id="show_btn_follow_material">
                            
                        </div>
                    </div>
                    <div class="row" id="show-btn-unfollow">
                        <div class="col-md-4 offset-4" id="show_btn_follow_material">
                            
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-12">
                            <label class="col-sm-12 control-label"> Items y ubicaciones </label>
                        </div>
                    </div>

                    <div id="body-items-load" class="table-responsive p-0" style="height: 300px;">
                        <table class="card-body table table-head-fixed text-nowrap">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Serie</th>
                                <th>Ubicación</th>
                                <th>Largo</th>
                                <th>Ancho</th>
                                <th>Peso</th>
                                <th>Precio</th>
                                <th>Selección</th>
                            </tr>
                            </thead>
                            <tbody id="body-items">


                            </tbody>
                            <template id="template-item">
                                <tr>
                                    <td data-id>John Doe</td>
                                    <td data-serie>John Doe</td>
                                    <td data-location>John Doe</td>
                                    <td data-length></td>
                                    <td data-width></td>
                                    <td data-weight></td>
                                    <td data-price></td>
                                    <td>
                                        <div class="icheck-success d-inline">
                                            <input type="checkbox" data-selected id="checkboxSuccess1">
                                            <label for="checkboxSuccess1" data-label></label>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </table>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-saveItems" class="btn btn-outline-primary">Agregar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalAddItemsCustom" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Personalizar item</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="col-sm-12 control-label" for="material_selected_custom"> Material </label>
                            <input type="hidden" name="equipment_custom" id="equipment_custom">
                            <input type="hidden" name="equipment_name_custom" id="equipment_name_custom">

                            <div class="col-sm-12">
                                <input type="text" id="material_selected_custom" name="material_selected_custom" class="form-control" readonly />
                            </div>
                        </div>

                    </div>
                    <br>
                    <div class="row" id="show-btn-follow2">
                        <div class="col-md-4 offset-4" id="show_btn_follow_material2">
                            
                        </div>
                    </div>
                    <div class="row" id="show-btn-unfollow2">
                        <div class="col-md-4 offset-4" id="show_btn_unfollow_material2">
                            
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="col-sm-12">
                                <label class="col-sm-12 control-label"> Medidas completas </label>
                            </div>
                            <div class="col-sm-12">
                                <div class="row">
                                    <div class="col-md-6" id="length_item_custom">
                                        <label class="col-sm-12 control-label" for="length_custom"> Largo (mm) </label>

                                        <div class="col-sm-12">
                                            <div class="input-group">
                                                <input type="text" id="length_custom" name="length_custom" class="form-control form-control-sm" readonly >
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="width_item_custom">
                                        <label class="col-sm-12 control-label" for="width_custom"> Ancho (mm) </label>

                                        <div class="col-sm-12">
                                            <div class="input-group">
                                                <input type="text" id="width_custom" name="width_custom" class="form-control form-control-sm" readonly >

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="col-sm-12">
                                <div class="col-sm-12">
                                    <label class="col-sm-12 control-label"> Medidas a pedir </label>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="row">
                                    <div class="col-md-6" id="length_new_item_custom">
                                        <label class="col-sm-12 control-label" for="length_new_custom"> Largo (mm) </label>

                                        <div class="col-sm-12">
                                            <input type="number" id="length_new_custom" min="0" name="length_new_custom" class="form-control form-control-sm" />
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="width_new_item_custom">
                                        <label class="col-sm-12 control-label" for="width_new_custom"> Ancho (mm) </label>

                                        <div class="col-sm-12">
                                            <input type="number" id="width_new_custom" min="0" name="width_new_custom" class="form-control form-control-sm" />
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-saveItemsCustom" class="btn btn-outline-primary">Agregar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalShowDetailsMaterial" class="modal fade" tabindex="-1">
        <div id="modal-dialog-custom" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Cantidades pedidas</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label class="col-sm-12 control-label" for="material_selected_custom"> Material </label>

                            <div class="col-sm-12">
                                <input type="text" id="material_selected_details" name="material_selected_details" class="form-control" readonly />
                            </div>
                        </div>

                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="row">
                                <div class="col-md-4" >
                                    <label class="col-sm-12 control-label" for="quantity_material"> Cantidad total </label>

                                    <div class="col-sm-12">
                                        <div class="input-group">
                                            <input type="text" id="quantity_material" name="quantity_material" class="form-control form-control-sm" readonly >
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4" >
                                    <label class="col-sm-12 control-label" for="request_material"> Cantidad Pedida </label>

                                    <div class="col-sm-12">
                                        <div class="input-group">
                                            <input type="text" id="request_material" name="request_material" class="form-control form-control-sm" readonly >

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4" >
                                    <label class="col-sm-12 control-label" for="missing_material"> Cantidad faltante </label>

                                    <div class="col-sm-12">
                                        <div class="input-group">
                                            <input type="text" id="missing_material" name="missing_material" class="form-control form-control-sm" readonly >

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cerrar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalFollowDetailsMaterial" class="modal fade" tabindex="-1">
        <div id="change-color" class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" id="color-custom">
                    <h4 class="modal-title">Estados del material</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <label class="col-sm-12 control-label" for="material_selected_follow"> Material </label>

                            <div class="col-sm-12">
                                <input type="text" id="material_selected_follow" name="material_selected_follow" class="form-control" readonly />
                            </div>
                        </div>

                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="row">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Stock</th>
                                        <th>Estado</th>
                                        <th>Órdenes de compra</th>
                                        <th>Fechas de llegada</th>
                                    </tr>
                                    </thead>
                                    <tbody id="body-follow">


                                    </tbody>
                                    <template id="template-follow">
                                        <tr>
                                            <td data-code></td>
                                            <td data-stock></td>
                                            <td data-state></td>
                                            <td data-orders></td>
                                            <td data-dates></td>
                                        </tr>
                                    </template>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cerrar</button>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/daterangepicker/daterangepicker.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{asset('admin/plugins/jquery_loading/loadingoverlay.min.js')}}"></script>
    <script src="{{asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
    <script>
        $(function () {
            $('body').tooltip({
                selector: '[data-toggle="tooltip"]'
            });
            $('#responsible_user').select2({
                placeholder: "Seleccione un usuario",
            });
            $('#equipments_order').select2({
                placeholder: "Seleccione un equipo",
            });
        })
    </script>
    <script src="{{ asset('js/output/output_request_order.js') }}"></script>
@endsection
