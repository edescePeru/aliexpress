@extends('layouts.appAdmin2')

@section('openEntryPurchaseReport')
    menu-open
@endsection

@section('activeEntryPurchaseReport')
    active
@endsection

@section('activeListEntryPurchaseReport')
    active
@endsection

@section('title')
    Entradas por compras
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
    <div class="next-page-heading"><span class="next-page-eyebrow">Almacén · Reportes</span><h1 class="page-title">Entradas por compra</h1><p class="next-page-description">Consulta de entradas de la empresa activa.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Reporte de entradas por compra</strong><span>Consulta por fecha de registro · Solo lectura</span></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-archive"></i> Reporte de Entradas por compras </li>
    </ol>
@endsection

@section('content')
@include('entry.partials.list-operations')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
    <section class="next-operational-list" aria-label="Reporte de entradas por compra">
        @can('searchReportEntries_entryPurchase')
        <form id="myForm" class="next-list-toolbar">
            @csrf
            <div class="row align-items-end">
                <div class="col-12 col-md-8 col-lg-6" id="sandbox-container">
                    <label for="start">Fechas de registro</label>
                    <div class="input-daterange input-group" id="datepicker">
                        <input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start" aria-label="Fecha inicial" autocomplete="off" placeholder="dd/mm/aaaa">
                        <div class="input-group-prepend input-group-append"><span class="input-group-text">al</span></div>
                        <input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end" aria-label="Fecha final" autocomplete="off" placeholder="dd/mm/aaaa">
                    </div>
                </div>
                <div class="col-12 col-md-auto mt-2 mt-md-0 next-toolbar-actions">
                    <button type="submit" id="submitButton" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1" aria-hidden="true"></i>Buscar</button>
                </div>
            </div>
            <small class="form-text text-muted">El rango filtra la fecha de registro; la tabla muestra la fecha de entrada.</small>
        </form>
        @else
        <div class="next-form-section"><p class="mb-0">No tienes permiso para realizar búsquedas en este reporte.</p></div>
        @endcan
        <div class="next-list-summary"><div class="next-list-summary-copy" id="report-status" role="status">Selecciona un rango de fechas para consultar.</div></div>
        <div id="report-error" class="alert alert-danger m-3 d-none" role="alert"></div>
        <div id="element_loader">
            <table class="table table-bordered table-hover table-sm next-data-table" id="dynamic-table">
                <thead><tr>
                    <th class="text-center">Guía de remisión</th><th class="text-center">Orden de compra</th><th class="text-center">Factura</th><th>Tipo de entrada</th><th>Proveedor</th><th class="text-center">Fecha</th><th class="text-center">Diferido</th><th class="text-right">Total</th><th class="text-center">Moneda</th><th class="text-center">Acciones</th>
                </tr></thead><tbody></tbody>
            </table>
        </div>
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
<script src="{{ asset('js/entry/list-operations.js') }}"></script>
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
    <script src="{{ asset('js/entry/index_entry_purchase_report.js') }}"></script>

    <script src="{{asset('admin/plugins/jquery_loading/loadingoverlay.min.js')}}"></script>
@endsection
