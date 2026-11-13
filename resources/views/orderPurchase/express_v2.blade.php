@extends('layouts.appAdmin2')

@section('openOrderPurchaseGeneral')
    menu-open
@endsection

@section('activeOrderPurchaseGeneral')
    active
@endsection

@section('activeListOrderPurchaseExpress')
    active
@endsection

@section('title')
    Ordenes de compra
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">

@endsection

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Compras</span><h1 class="page-title">Órdenes de compra express</h1><p class="next-page-description">Consulta de órdenes de la empresa activa.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Listado operativo</strong><span>Órdenes express · solo lectura.</span></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{route('order.purchase.express.indexV2')}}"><i class="fa fa-archive"></i> Ordenes de compra</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
    <section class="next-operational-list" aria-label="Órdenes express"><div class="next-form-section">
        <div class="row">
            <div class="col-md-12">
                <div class="row align-items-end">
                    <div class="col-md form-group"><label class="sr-only" for="code">Código de orden</label><input type="text" id="code" class="form-control form-control-sm" placeholder="Código de orden..." autocomplete="off"></div>
                    <div class="col-md-auto form-group"><div class="next-toolbar-actions flex-wrap"><button class="btn btn-primary btn-sm" type="button" id="btn-search">Buscar</button><button type="button" id="btnBusquedaAvanzada" class="btn btn-outline-secondary btn-sm" aria-expanded="false" aria-controls="express-filters">Filtros avanzados</button></div></div>
                </div>
                <div class="next-list-filters mt-3 busqueda-avanzada" id="express-filters" hidden>
                    <div class="row">

                        <div class="col-sm-6 col-lg-3 form-group">
                            <label for="year">Año de la orden:</label>
                            <select id="year" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayYears); $i++)
                                    <option value="{{ $arrayYears[$i] }}">{{ $arrayYears[$i] }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-sm-6 col-lg-3 form-group">
                            <label for="supplier">Proveedor:</label>
                            <select id="supplier" name="supplier" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arraySuppliers); $i++)
                                    <option value="{{ $arraySuppliers[$i]['id'] }}">{{ $arraySuppliers[$i]['business_name'] }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-sm-6 col-lg-3 form-group">
                            <label for="quote">Cotización:</label>
                            <input type="text" id="quote" class="form-control form-control-sm" placeholder="791-" autocomplete="off">

                        </div>

                        <div class="col-sm-6 col-lg-3 form-group">
                            <label for="state">Estado de la orden:</label>
                            <select id="state" name="state" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @foreach ($arrayStates as $state)
                                    <option value="{{ $state['value'] }}">{{ $state['display'] }}</option>
                                @endforeach
                            </select>
                        </div>


                    </div>



                    <div class="row">
                        <div class="col-sm-6 col-lg-3 form-group">
                            <label for="deliveryDate">Fecha de Llegada:</label>
                            <div id="sandbox-container1">
                                <div class="input-daterange input-group input-group-sm" id="datepicker1">
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="deliveryDate" name="deliveryDate" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-6 form-group">
                            <label for="start">Fechas de Orden:</label>
                            <div id="sandbox-container">
                                <div class="input-daterange input-group input-group-sm" id="datepicker">
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start" autocomplete="off">
                                    <div class="input-group-append input-group-prepend"><span class="input-group-text">al</span></div>
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end" aria-label="Fecha final" autocomplete="off">
                                </div>
                            </div>
                        </div>

                    </div>




                </div>
            </div>
        </div>


    </div>

    <div class="next-list-summary"><div class="next-list-summary-copy"><strong><span id="numberItems">—</span> órdenes</strong><span>Fecha de orden · más recientes primero</span></div></div>
    <div class="next-list-content">
        <div class="table-responsive" tabindex="0" aria-label="Órdenes express; desplazamiento horizontal">
            <table class="table table-bordered table-hover table-sm next-data-table">
                <thead>
                <tr>
                    <th class="text-center">ID</th>
                    <th class="text-center">Código</th>
                    <th class="text-center">Fecha Orden</th>
                    <th class="text-center">Fecha Llegada</th>
                    <th>Observación</th>
                    <th>Proveedor</th>
                    <th>Aprobado por</th>
                    <th class="text-center">Moneda</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="body-table" aria-live="polite">

                </tbody>
            </table>
        </div>
        <div class="next-list-toolbar">
            <div class="fs-6 fw-bold text-gray-700" id="textPagination"></div>
            <ul class="pagination mb-0" id="pagination">

            </ul>
        </div>
    </div>

    </section>
    <template id="previous-page">
        <li class="page-item previous">
            <a href="#" class="page-link" data-item>
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
            <td class="text-center" data-id></td>
            <td class="text-center" data-code></td>
            <td class="text-center" data-date_order></td>
            <td class="text-center" data-date_arrival></td>
            <td data-observation></td>
            <td data-supplier></td>
            <td data-approved_user></td>
            <td class="text-center" data-currency></td>
            <td class="text-right" data-total></td>
            <td class="text-center" data-type></td>
            <td class="text-center" data-state></td>
            <td class="text-center" data-buttons></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="12" class="next-table-empty text-left">Sin órdenes express.</td>
        </tr>
    </template>


@endsection

@section('plugins')
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>

    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>

    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/inputmask/min/jquery.inputmask.bundle.min.js') }}"></script>
@endsection

@section('scripts')
    <script>
        $(function () {
            //Initialize Select2 Elements
            $('#year').select2({
                placeholder: "Selecione año",
                allowClear: true, width: "100%"
            });

            $('#supplier').select2({
                placeholder: "Selecione Proveedor",
                allowClear: true, width: "100%"
            });

            $('#state').select2({
                placeholder: "Seleccione Estado",
                allowClear: true, width: "100%"
            });



        })
    </script>
    <script src="{{ asset('js/orderPurchase/expressV2.js') }}"></script>

@endsection
