@extends('layouts.appAdmin2')
@section('openOrderPurchaseGeneral', 'menu-open')
@section('activeOrderPurchaseGeneral', 'active')
@section('activeListOrderPurchaseFinanceRegularize', 'active')
@section('title', 'Órdenes financieras regularizadas')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Compras · Finanzas</span><h1 class="page-title">Órdenes financieras regularizadas</h1><p class="next-page-description">Histórico de la empresa activa · Solo lectura.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Listado histórico</strong><span>Órdenes financieras regularizadas</span></div><div class="next-toolbar-actions flex-wrap">
@can('list_orderPurchaseFinance')<a class="btn btn-outline-secondary btn-sm" href="{{ route('order.purchase.finance.index') }}">Órdenes financieras</a>@endcan
</div></div>
@endsection
@section('content')
<input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
<section class="next-operational-list" id="finance-history" data-mode="regularized" data-url="{{ url('dashboard/all/order/purchase/finance/regularize') }}" data-detail-url="{{ url('dashboard/ver/orden/compra/finanzas/eliminada') }}" aria-label="Órdenes financieras regularizadas">
<div class="next-list-summary"><div id="history-status" class="next-list-summary-copy" role="status">Consultando órdenes…</div></div>
<div class="next-form-section d-none" id="history-error" role="alert"></div>
<div id="history-results">
<table class="table table-bordered table-hover table-sm next-data-table" id="dynamic-table"><thead><tr><th class="text-center">ID</th><th class="text-center">Código</th><th class="text-center">Fecha orden</th><th class="text-center">Fecha entrega</th><th>Observación</th><th>Proveedor</th><th>Aprobado por</th><th class="text-center">Moneda</th><th class="text-right">Total</th><th class="text-center">Estado</th></tr></thead><tbody></tbody></table>
</div></section>
@endsection
@section('styles-plugins')
<link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
@endsection
@section('scripts')
<script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('js/orderPurchaseFinance/history-readonly.js') }}"></script>
@endsection
