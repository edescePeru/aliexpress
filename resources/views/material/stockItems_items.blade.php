@extends('layouts.appAdmin2')
@section('openMaterial', 'menu-open')
@section('activeMaterial', 'active')
@section('activeListStockItems', 'active')
@section('title', 'Items del StockItem')
@section('styles-plugins')
<link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.css') }}">
@endsection
@section('page-header')
<h1 class="page-title">Items del StockItem</h1>
@endsection
@section('page-title')
<div class="next-page-toolbar">
    <div class="next-toolbar-context"><strong>{{ $stockMaterial->sku }} · {{ $stockMaterial->display_name }}</strong><span>Unidades físicas del local activo y almacenes generales permitidos. Solo lectura.</span></div>
    <div class="next-toolbar-actions"><a href="{{ route('stockitems.index') }}" class="btn btn-outline-secondary btn-sm">Volver a StockItems</a></div>
</div>
@endsection
@section('content')
@include('material._inventoryContext')
<input type="hidden" id="id-material" value="{{ $stockMaterial->id }}">
<section class="next-operational-list next-advanced-operational-list" aria-label="Items físicos">
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong>Items físicos</strong><span>Abre el detalle para consultar estado y ubicación.</span></div></div>
    <div id="items-read-error" class="alert alert-danger m-3 d-none" role="alert"></div>
    <div class="next-list-content">
        <table id="dynamic-table" class="table table-bordered table-hover table-sm next-data-table w-100">
            <thead><tr><th class="text-center">Detalle</th><th>Material</th><th class="text-center">Código</th><th class="text-right">Precio</th><th class="text-right">Porcentaje</th><th class="text-center">Estado</th></tr></thead><tbody></tbody>
        </table>
    </div>
</section>
@endsection
@section('plugins')
<script src="{{ asset('admin/plugins/datatables/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.js') }}"></script>
@endsection
@section('scripts')
<script src="{{ asset('js/material/inventory-context.js') }}"></script>
<script>window.stockItemItemsDataUrl = @json(route('material.stock.getItemsMaterial', $stockMaterial->id));</script>
<script src="{{ asset('js/material/stockItems_items.js') }}?v={{ filemtime(public_path('js/material/stockItems_items.js')) }}"></script>
@endsection
