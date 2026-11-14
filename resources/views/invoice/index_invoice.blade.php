@extends('layouts.appAdmin2')
@section('openInvoice', 'menu-open')
@section('activeInvoice', 'active')
@section('activeListInvoice', 'active')
@section('title', 'Facturas financieras')
@section('styles-plugins')
<link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
@endsection
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Finanzas</span><h1 class="page-title">Facturas financieras</h1><p class="next-page-description">Consulta de facturas de la empresa activa.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Listado financiero</strong><span>Consulta de solo lectura.</span></div><div class="next-toolbar-actions flex-wrap"><a class="btn btn-outline-secondary btn-sm" href="{{ route('invoice.index') }}">Activas</a><a class="btn btn-outline-secondary btn-sm" href="{{ route('invoice.index.annulled') }}">Anuladas</a></div></div>
@endsection
@section('content')
<input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
<section class="next-operational-list" aria-label="Facturas financieras">
<div class="next-form-section"><div class="row"><div class="col-lg-6" id="sandbox-container"><label for="start">Fechas de factura</label><div class="input-daterange input-group input-group-sm" id="datepicker"><input class="form-control form-control-sm date-range-filter" id="start" name="start" aria-label="Fecha inicial" autocomplete="off"><div class="input-group-append input-group-prepend"><span class="input-group-text">al</span></div><input class="form-control form-control-sm date-range-filter" id="end" name="end" aria-label="Fecha final" autocomplete="off"></div></div></div></div>
<div id="invoice-read-error" class="alert alert-danger m-3 d-none" role="alert"></div>
<table id="dynamic-table" data-read-url="/dashboard/get/json/invoices/purchase" class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th>Fecha</th><th>Documento</th><th>Referencia</th><th>Tipo</th><th>Proveedor</th><th>Diferido</th><th>Categoría</th><th>Moneda</th><th>Subtotal</th><th>Impuestos</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead><tbody></tbody></table>
</section>
@endsection
@section('plugins')
<script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
@endsection
@section('scripts')
<script src="{{ asset('js/invoice/finance-readonly.js') }}"></script>
@endsection
