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

@section('title', 'Precios')

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
    <h1 class="page-title">Lista de precios</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Administrar lista de precios</h5>
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
            id="priceListApp"

            data-url-lists="{{ route('priceList.lists') }}"
            data-url-store="{{ route('priceList.store') }}"
            data-url-update-template="{{ route('priceList.update', ['priceList' => '__LIST__']) }}"
            data-url-materials-template="{{ route('priceList.materials', ['priceList' => '__LIST__']) }}"
            data-url-save-materials-template="{{ route('priceList.materials.save', ['priceList' => '__LIST__']) }}"
            data-url-variants-template="{{ route('priceList.materialVariants', ['priceList' => '__LIST__', 'material' => '__MATERIAL__']) }}"
            data-url-save-variants-template="{{ route('priceList.materialVariants.save', [ 'priceList' => '__LIST__', 'material' => '__MATERIAL__' ]) }}"
    >

        <div class="row">

            <div class="col-md-5">

                <div class="form-group">
                    <label>Empresa actual</label>

                    <div class="form-control bg-light">
                        {{ $company->business_name }}
                        -
                        {{ $company->ruc }}
                    </div>
                </div>

            </div>

            <div class="col-md-5">
                <div class="form-group">
                    <label>
                        Lista de precios
                    </label>

                    <div class="d-flex align-items-center">

                        <div class="flex-grow-1 mr-2">
                            <select
                                    id="priceList"
                                    class="form-control select2"
                                    style="width: 100%;"
                            >
                            </select>
                        </div>

                        @can('manage_priceListMaterial')
                            <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    id="btnEditPriceList"
                                    title="Editar lista"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                        @endcan

                    </div>
                </div>
            </div>

            @can('manage_priceListMaterial')
                <div class="col-md-2 d-flex align-items-end">

                    <div class="form-group w-100">

                        <button
                                type="button"
                                class="btn btn-primary btn-block"
                                id="btnNewPriceList"
                        >
                            <i class="fas fa-plus"></i>
                            Nueva lista
                        </button>

                    </div>

                </div>
            @endcan

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

    </div>

@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>

@endsection

@section('scripts')

    <script src="{{ asset('js/priceList/index.js') }}"></script>

@endsection