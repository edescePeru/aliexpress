@extends('layouts.appAdmin2')

@section('openEntryPurchase')
    menu-open
@endsection

@section('activeEntryPurchase')
    active
@endsection

@section('activeListOrdersInEntries')
    active
@endsection

@section('title')
    Ordenes de compra
@endsection

@section('styles-plugins')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">

@endsection

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Inventario · Compras</span><h1 class="page-title">Órdenes para almacén</h1><p class="next-page-description">Consulta de la empresa activa.</p></div>
@endsection

@section('page-title')<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Listado operativo</strong><span>Consulta de solo lectura.</span></div></div>@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        {{--<li class="breadcrumb-item">
            <a href="{{route('order.purchase.express.index')}}"><i class="fa fa-archive"></i> Ordenes de compra</a>
        </li>--}}
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
<section class="next-operational-list">
<input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
<div class="next-list-toolbar"><form id="list-filters"><div class="next-list-search-row"><div class="next-list-search-control"><div class="input-group"><input type="text" id="code" aria-label="Código de orden" class="form-control" placeholder="Código de orden…" autocomplete="off"><div class="input-group-append"><button class="btn btn-primary" type="button" id="btn-search">Buscar</button></div></div></div><button type="button" id="btnBusquedaAvanzada" class="btn btn-outline-secondary" data-toggle="collapse" data-target="#advanced-filters" aria-expanded="false" aria-controls="advanced-filters">Filtros avanzados</button></div>
<div id="advanced-filters" class="collapse"><div class="busqueda-avanzada d-block"><div class="row"><div class="form-group col-md-3"><label for="year">Año de la orden</label><select id="year" class="form-control form-control-sm select2"><option value="">TODOS</option>@foreach($arrayYears as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach</select></div>
<div class="form-group col-md-3"><label for="supplier">Proveedor</label><select id="supplier" name="supplier" class="form-control form-control-sm select2"><option value="">TODOS</option>@foreach($arraySuppliers as $supplier)<option value="{{ $supplier['id'] }}">{{ $supplier['business_name'] }}</option>@endforeach</select></div><div class="form-group col-md-3"><label for="quote">Cotización / observación</label><input id="quote" class="form-control form-control-sm" autocomplete="off"></div><div class="form-group col-md-3"><label for="state">Estado de recepción</label><select id="state" name="state" class="form-control form-control-sm select2"><option value="">TODOS</option>@foreach($arrayStates as $state)<option value="{{ $state['value'] }}">{{ $state['display'] }}</option>@endforeach</select></div><div class="form-group col-md-3"><label for="deliveryDate">Fecha de llegada</label><input id="deliveryDate" class="form-control form-control-sm" autocomplete="off"></div><div class="form-group col-md-6"><label for="start">Fechas de orden</label><div id="sandbox-container"><div class="input-daterange input-group" id="datepicker"><input type="text" id="start" name="start" aria-label="Fecha inicial" class="form-control form-control-sm date-range-filter" autocomplete="off"><div class="input-group-append input-group-prepend"><span class="input-group-text">al</span></div><input type="text" id="end" name="end" aria-label="Fecha final" class="form-control form-control-sm date-range-filter" autocomplete="off"></div></div></div></div></div></div></form></div>
<div id="list-error" class="alert alert-danger d-none" role="alert"></div>
<div class="next-list-summary"><span><strong id="numberItems"></strong> órdenes de compra</span><span>Fecha de orden · más recientes primero</span></div>
<div class="next-list-content"><div class="table-responsive"><table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-center">ID</th><th class="text-center">Código</th><th class="text-center">Fecha orden</th><th class="text-center">Fecha llegada</th><th class="text-left">Observación</th><th class="text-left">Proveedor</th><th class="text-left">Creado por</th><th class="text-center">Moneda</th><th class="text-right">Total</th><th class="text-center">Recepción</th></tr></thead><tbody id="body-table"></tbody></table></div></div>
<div class="next-list-pagination"><div id="textPagination"></div><ul class="pagination" id="pagination"></ul></div></section>
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


<template id="item-table"><tr><td class="text-center" data-id></td><td class="text-center" data-code></td><td class="text-center" data-date_order></td><td class="text-center" data-date_arrival></td><td class="text-left" data-observation></td><td class="text-left" data-supplier></td><td class="text-left" data-created_user></td><td class="text-center" data-currency></td><td class="text-right" data-total></td><td class="text-center" data-state></td></tr></template>
<template id="item-table-empty"><tr><td colspan="10" class="text-left text-muted">Sin órdenes para los filtros seleccionados.</td></tr></template>
@endsection

@section('plugins')
    <!-- Datatables -->
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
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
                allowClear: true, width: '100%'
            });

            $('#supplier').select2({
                placeholder: "Selecione Proveedor",
                allowClear: true, width: '100%'
            });

            $('#type').select2({
                placeholder: "Selecione Tipo",
                allowClear: true, width: '100%'
            });

            $('#state').select2({
                placeholder: "Seleccione Estado",
                allowClear: true, width: '100%'
            });

            $('#stateOrder').select2({
                placeholder: "Seleccione"
            });

        })
    </script>
    <script src="{{ asset('js/entry/listOrdersV2.js') }}"></script>

@endsection