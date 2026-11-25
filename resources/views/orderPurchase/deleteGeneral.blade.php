@extends('layouts.appAdmin2')

@section('openOrderPurchaseGeneral')
    menu-open
@endsection

@section('activeOrderPurchaseGeneral')
    active
@endsection

@section('activeListOrderPurchaseDelete')
    active
@endsection

@section('title')
    Ordenes de compra eliminadas
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Compras · Histórico</span><h1 class="page-title">Órdenes normales eliminadas</h1><p class="next-page-description">Papelera de la empresa activa.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Papelera de órdenes normales</strong><span>Consulta de registros eliminados</span></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{route('order.purchase.normal.indexV2')}}"><i class="fa fa-archive"></i> Ordenes de compra general</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
<input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
<section class="next-operational-list" aria-label="Órdenes normales eliminadas">
<div class="next-list-summary"><div id="trash-status" class="next-list-summary-copy" role="status">Consultando papelera…</div></div>
<div id="trash-error" class="alert alert-danger m-3 d-none" role="alert"></div>
<table id="dynamic-table" class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th class="text-center">ID</th><th class="text-center">Código</th><th class="text-center">Fecha orden</th><th class="text-center">Fecha llegada</th><th class="text-left">Observación</th><th class="text-left">Proveedor</th><th class="text-left">Aprobado por</th><th class="text-center">Moneda</th><th class="text-right">Total</th><th class="text-center">Tipo</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr></thead><tbody></tbody></table>
</section>
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
    @include('orderPurchase.partials.list-operations-scripts')
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('js/orderPurchase/deleteGeneral.js') }}"></script>
@endsection