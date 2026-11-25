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
    <div class="next-toolbar-context"><strong>{{ $stockMaterial->sku }} · {{ $stockMaterial->display_name }}</strong><span>Unidades físicas del local activo y almacenes generales permitidos.</span></div>
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
@can('editarCodeItems_material')
<div class="modal fade next-aux-modal" id="modalEditarCodigoItem" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="editItemCodeTitle">
    <div class="modal-dialog" role="document"><form class="modal-content" id="formEditarCodigoItem">
        @csrf
        <div class="modal-header"><h2 class="modal-title h5" id="editItemCodeTitle">Editar código del Item</h2><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
        <div class="modal-body next-form-section">
            <div id="item-code-error" class="alert alert-danger d-none" role="alert"></div>
            <input type="hidden" id="edit_item_id">
            <div class="form-group mb-0"><label for="edit_item_code">Código o serie</label><input type="text" class="form-control" id="edit_item_code" name="code" required maxlength="255" aria-describedby="item-code-help"><small id="item-code-help" class="form-text text-muted">Código de la unidad física; no modifica el SKU ni el código de barras del producto.</small></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" id="btnGuardarCodigoItem"><i class="fas fa-save mr-1" aria-hidden="true"></i>Guardar</button></div>
    </form></div>
</div>
@endcan
@endsection
@section('plugins')
<script src="{{ asset('admin/plugins/datatables/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.js') }}"></script>
@endsection
@section('scripts')
<script src="{{ asset('js/material/inventory-context.js') }}"></script>
<script>
window.stockItemItemsDataUrl = @json(route('material.stock.getItemsMaterial', $stockMaterial->id));
window.stockItemCodeUrl = @json(route('items.update.code', ':id'));
window.canEditItemCode = @json(auth()->user()->can('editarCodeItems_material'));
</script>
<script src="{{ asset('js/material/stockItems_items.js') }}?v={{ filemtime(public_path('js/material/stockItems_items.js')) }}"></script>
@endsection
