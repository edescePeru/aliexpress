@extends('layouts.appAdmin2')
@section('openMaterial', 'menu-open')
@section('activeMaterial', 'active')
@section('activeListStockItems', 'active')
@section('title', 'StockItems')
@section('page-header')
<h1 class="page-title">StockItems</h1>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Catálogo habilitado por empresa</strong><span>Consulta del local activo y almacenes generales permitidos.</span></div><span class="badge badge-info">Solo lectura</span></div>
@endsection
@section('content')
@include('material._inventoryContext')
<input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
<section class="next-operational-list next-advanced-operational-list" aria-label="Listado de StockItems">
    <form class="next-list-toolbar" id="stock-items-search-form" role="search">
        <div class="next-list-search-row"><div class="input-group next-list-search-control">
            <input id="search-stock-item" class="form-control" maxlength="250" placeholder="Buscar por SKU, código de barras o descripción" aria-label="Buscar StockItem" autocomplete="off">
            <div class="input-group-append"><button id="btn-search-stock-item" class="btn btn-primary" type="submit"><i class="fas fa-search mr-1" aria-hidden="true"></i>Buscar</button></div>
        </div></div>
    </form>
    <div class="next-list-summary"><div class="next-list-summary-copy"><strong id="stock-items-count" aria-live="polite">Cargando…</strong><span>Stock local y general por separado; no implican vendibilidad.</span></div></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="StockItems; desplazamiento horizontal de tabla">
        <table class="table table-bordered table-hover table-sm next-data-table"><thead><tr>
            <th class="text-center">SKU</th><th class="text-center">Código barras</th><th>Nombre visible</th><th>Producto padre</th><th>Variante</th><th class="text-center">UM</th><th class="text-center">Inventariable</th><th class="text-center">Estado</th><th class="text-center">Stock</th><th class="text-center">Reservado</th><th class="text-center">Disponible</th><th class="text-center">Acciones</th>
        </tr></thead><tbody id="tbody-stock-items"></tbody></table>
    </div></div>
    <nav class="next-list-pagination" aria-label="Paginación de StockItems"><div id="stock-items-info" aria-live="polite"></div><div id="stock-items-pagination"></div></nav>
</section>
<div class="modal fade next-aux-modal" id="modalInventoryLevels" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="modalInventoryLevelsLabel">
    <div class="modal-dialog modal-xl" role="document"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title h5" id="modalInventoryLevelsLabel">Inventario por ubicación</h2><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
        <div class="modal-body">
            <p id="modal_display_name" class="font-weight-bold"></p><p class="text-muted">SKU: <span id="modal_sku"></span> · Código barras: <span id="modal_barcode"></span></p>
            <div class="table-responsive" tabindex="0" aria-label="Niveles de inventario; desplazamiento horizontal de tabla">
                <table class="table table-sm table-bordered next-data-table"><thead><tr><th>Almacén / ámbito</th><th>Ubicación</th><th class="text-center">Stock actual</th><th class="text-center">Reservado</th><th class="text-center">Disponible</th><th class="text-center">Mínimo</th><th class="text-center">Máximo</th><th class="text-right">Costo promedio</th><th class="text-right">Último costo</th></tr></thead><tbody id="tbody-modal-inventory-levels"></tbody></table>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button></div>
    </div></div>
</div>
@endsection
@section('scripts')
<script src="{{ asset('js/material/inventory-context.js') }}"></script>
<script>
window.stockItemInventoryLevelsUrl = @json(route('stockitems.inventory-levels', ':id'));
window.stockItemItemsUrl = @json(route('material.stock.getItems', ':id'));
window.APP = {URLS: {STOCK_ITEMS: @json(route('stockitems.list'))}};
</script>
<script src="{{ asset('js/stockItem/list.js') }}?v={{ filemtime(public_path('js/stockItem/list.js')) }}"></script>
@endsection
