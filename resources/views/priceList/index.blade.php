@extends('layouts.appAdmin2')

@section('openPriceList')
    menu-open
@endsection

@section('activePriceList')
    active
@endsection

@section('activePriceListIndex')
    active
@endsection

@section('title', 'Listas de precios')

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('styles')
    <style>
        .price-list-actions {
            position: sticky;
            bottom: 0;
            z-index: 20;
            background: #fff;
            border-top: 1px solid #dee2e6;
            box-shadow: 0 -3px 8px rgba(0, 0, 0, 0.06);
        }
    </style>
@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Configuración de empresa</span><h1 class="page-title">Listas de precios</h1><p class="next-page-description">Mantenimiento general de la empresa activa.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Listas de precios</strong><span>Nombre, moneda y estado de cada lista.</span></div><div class="next-toolbar-actions flex-wrap">
    @can('manage_priceListMaterial')
        <button type="button" class="btn btn-primary btn-sm" id="btnNewPriceList"><i class="fas fa-plus mr-1" aria-hidden="true"></i>Nueva lista</button>
    @endcan
    </div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('priceList.index') }}"><i class="fa fa-archive"></i> Lista de Precios</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Administrar</li>
    </ol>
@endsection

@section('content')

    <div
            id="priceListApp" class="next-operational-list"
            data-can-manage="{{ auth()->user()->can('manage_priceListMaterial') ? 1 : 0 }}"

            data-url-lists="{{ route('priceList.lists') }}"
            data-url-store="{{ route('priceList.store') }}"
            data-url-update-template="{{ route('priceList.update', ['priceList' => '__LIST__']) }}"
            data-url-materials-template="{{ route('priceList.materials', ['priceList' => '__LIST__']) }}"
            data-url-save-materials-template="{{ route('priceList.materials.save', ['priceList' => '__LIST__']) }}"
            data-url-variants-template="{{ route('priceList.materialVariants', ['priceList' => '__LIST__', 'material' => '__MATERIAL__']) }}"
            data-url-save-variants-template="{{ route('priceList.materialVariants.save', [ 'priceList' => '__LIST__', 'material' => '__MATERIAL__' ]) }}"
    >

<div class="next-list-toolbar"><p class="mb-0 text-break"><strong>Empresa actual:</strong> {{ $company->business_name }} · {{ $company->ruc }}</p></div>
        <div class="next-list-summary" role="status" id="priceListSummary">Cargando listas…</div>
        <div class="table-responsive" tabindex="0" aria-label="Listas de precios; desplazamiento horizontal">
            <table class="table table-bordered table-hover table-sm">
                <thead><tr><th>Nombre</th><th class="text-center">Moneda</th><th class="text-center">Estado</th><th class="text-center">Default</th>@can('manage_priceListMaterial')<th class="text-center">Acciones</th>@endcan</tr></thead>
                <tbody id="priceListBody"></tbody>
            </table>
        </div>
        {{-- Existing product-price editor: separate contract, not modernized in this phase. --}}
        @can('manage_priceListMaterial')
        <details class="next-form-section overflow-auto">
            <summary>Precios de productos</summary>
            <p class="text-muted small mt-2">Edición de precios base y variantes por lista.</p>
            <div class="form-group">
                <label for="priceList">Lista de precios</label>
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1 mr-2"><select id="priceList" class="form-control select2 w-100"></select></div>
                    <button type="button" class="btn btn-outline-secondary" id="btnEditPriceList" title="Editar lista" aria-label="Editar lista seleccionada"><i class="fas fa-edit" aria-hidden="true"></i></button>
                </div>
            </div>
        <hr>

        <div class="row mb-3">

            <div class="col-md-6">

                <input
                        type="text"
                        id="searchMaterialPrice"
                        class="form-control"
                        placeholder="Buscar producto..."
                >

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover">

                <thead>

                <tr>
                    <th>
                        Producto
                    </th>

                    <th width="150">
                        Variantes
                    </th>

                    <th width="220">
                        Precio base
                    </th>
                </tr>

                </thead>

                <tbody id="materialPriceBody">
                </tbody>

            </table>

        </div>

        <div id="materialPricePagination">
        </div>

        <div class="price-list-actions py-3 mt-3">
            <div class="d-flex justify-content-end">
                <button
                        type="button"
                        id="btnSaveMaterialPrices"
                        class="btn btn-success"
                >
                    <i class="fas fa-save"></i>
                    Guardar precios
                </button>
            </div>
        </div>

        </details>
        @endcan
    </div>

@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>

@endsection

@section('scripts')

    <script src="{{ asset('js/priceList/index.js') }}"></script>

@endsection
