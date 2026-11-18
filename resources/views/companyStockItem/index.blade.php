@extends('layouts.appAdmin2')
@section('title', 'Catálogo de productos por empresa')
@section('manageCompanyStockItem', 'active')
@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración · Empresas</span><h1 class="page-title">Catálogo de productos por empresa</h1><p class="next-page-description">Administra qué SKUs están habilitados para cada empresa.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Habilitación de productos</strong><span>El estado corresponde a la empresa seleccionada.</span></div></div>
@endsection
@section('content')
<section class="next-operational-list" id="company-stock-items-app" data-url-data="{{ route('companyStockItem.data') }}" data-url-status="{{ route('companyStockItem.status') }}" aria-label="Catálogo de productos por empresa">
    <div class="next-form-section">
        <div class="row">
            <div class="col-md-6 col-xl-4"><div class="form-group"><label for="company_id">Empresa</label><select id="company_id" class="form-control">
                @forelse($companies as $company)
                <option value="{{ $company->id }}" {{ $company->id == \App\Support\TenantContext::companyId() ? 'selected' : '' }}>{{ $company->business_name }}</option>
                @empty
                <option value="">Sin empresas autorizadas</option>
                @endforelse
            </select></div></div>
            <div class="col-md-6 col-xl-3"><div class="form-group"><label for="brand_id">Marca</label><select id="brand_id" class="form-control"><option value="">Todas</option>@foreach($brands as $brand)<option value="{{ $brand->id }}">{{ $brand->name }}</option>@endforeach</select></div></div>
            <div class="col-md-6 col-xl-3"><div class="form-group"><label for="category_id">Categoría</label><select id="category_id" class="form-control"><option value="">Todas</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div></div>
            <div class="col-md-6 col-xl-2"><div class="form-group"><label for="status">Estado en empresa</label><select id="status" class="form-control"><option value="all">Todos</option><option value="enabled">Habilitados</option><option value="disabled">No habilitados</option></select></div></div>
        </div>
        <div class="form-group mb-0"><label for="search">Buscar</label><input type="text" id="search" class="form-control" placeholder="Producto, SKU o código de barras"></div>
    </div>
    <div class="next-list-summary"><div class="next-list-summary-copy" id="catalog-summary" role="status" aria-live="polite">Cargando catálogo…</div></div>
    <div class="next-list-content"><div class="table-responsive" tabindex="0" aria-label="Productos; desplazamiento horizontal de tabla">
        <table class="table table-bordered table-hover table-sm next-data-table"><thead><tr><th>Producto</th><th>Marca</th><th>Categoría</th><th>Variante</th><th class="text-center">SKU</th><th class="text-center">Código de barras</th><th class="text-center">Habilitado para empresa</th></tr></thead><tbody id="stock-items-body"></tbody></table>
    </div></div>
    <nav id="pagination-container" class="next-list-pagination" aria-label="Páginas del catálogo"></nav>
</section>
@endsection
@section('scripts')
<script src="{{ asset('js/companyStockItem/index.js') }}?v={{ filemtime(public_path('js/companyStockItem/index.js')) }}"></script>
@endsection
