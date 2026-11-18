@extends('layouts.appAdmin2')
@section('openOrderPurchaseGeneral', 'menu-open')
@section('activeOrderPurchaseGeneral', 'active')
@section('activeListOrderPurchaseFinance', 'active')
@section('title', 'Órdenes de compra financieras')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Compras · Finanzas</span><h1 class="page-title">Órdenes de compra financieras</h1><p class="next-page-description">Consulta de la empresa activa.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Listado operativo</strong><span>Solo lectura.</span></div></div>
@endsection
@section('content')
<input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
<div class="next-form-section" id="finance-orders" data-url="{{ url('dashboard/all/order/finance') }}">
    <p id="finance-error" class="d-none" role="alert"></p>
    <p id="finance-empty" class="text-muted d-none" aria-live="polite"></p>
    <div class="table-responsive" tabindex="0" aria-label="Órdenes financieras; desplazamiento horizontal">
        <table class="table table-bordered table-hover table-sm next-data-table" id="dynamic-table">
            <thead><tr><th class="text-center">ID</th><th class="text-center">Código</th><th class="text-center">Fecha orden</th><th class="text-center">Fecha entrega</th><th>Observación</th><th>Proveedor</th><th>Aprobado por</th><th class="text-center">Moneda</th><th class="text-right">Total</th><th class="text-center">Regularización</th></tr></thead><tbody></tbody>
        </table>
    </div>
</div>
@endsection
@section('styles-plugins')
<link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
@endsection
@section('scripts')
<script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('js/orderPurchaseFinance/list-readonly.js') }}"></script>
@endsection
