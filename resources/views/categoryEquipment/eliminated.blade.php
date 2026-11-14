@extends('layouts.appAdmin2')

@section('openDefaultEquipment')
    menu-open
@endsection

@section('activeDefaultEquipment')
    active
@endsection

@section('activeCategoryEquipmentDelete')
    active
@endsection

@section('title', 'Categorías de equipos eliminadas')

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection


@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración auxiliar de cotización</span><h1 class="page-title">Categorías de equipos eliminadas</h1><p class="next-page-description">Clasificación del catálogo de equipos.</p></div>
@endsection

@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Catálogo de categorías</strong><span>Descripción e imagen.</span></div><div class="next-toolbar-actions flex-wrap">@can('listCategory_defaultEquipment')<a class="btn btn-outline-secondary btn-sm" href="{{ route('categoryEquipment.index') }}">Volver al listado</a>@endcan</div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('categoryEquipment.index') }}"><i class="fa fa-archive"></i> Categorías de Equipos</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado de eliminados</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
    <div class="next-operational-list">
        <div class="next-list-toolbar"><div class="w-100">
            <div class="input-group"><input type="text" aria-label="Buscar categoría" placeholder="Nombre de la categoría del equipo" id="inputNameCategoryEquipment" name="search" class="form-control "><div class="input-group-append"><button type="button" id="btn-search" class="btn btn-primary">Buscar</button></div></div>

        </div></div>
        <div class="next-list-summary"><strong><span id="numberItems">0</span> categorías</strong><span>Eliminadas</span></div>
        <div class="table-responsive" tabindex="0" aria-label="Categorías de equipos; desplazamiento horizontal"><table class="table table-bordered table-hover table-sm"><thead><tr><th>Descripción</th><th class="text-center">Imagen</th><th class="text-center">Equipos</th><th class="text-center">Acciones</th></tr></thead><tbody id="body-card"><tr><td colspan="4" class="text-center py-4">Cargando categorías…</td></tr></tbody></table></div>
        <div class="next-list-toolbar d-flex flex-wrap justify-content-between align-items-center"><div id="textPagination" class="text-muted small"></div><ul class="pagination pagination-sm mb-0 flex-wrap" id="pagination"></ul></div>
    </div>
    <template id="item-card"><tr data-id><td data-description></td><td class="text-center"><img data-image src="" alt="Imagen de categoría" width="48" class="img-fluid"></td><td data-number class="text-center"></td><td class="text-center"><div class="next-toolbar-actions flex-wrap justify-content-center">@can('restoreCategory_defaultEquipment')<button type="button" data-restore="" data-description="" data-image="" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#restoreModal">Restaurar</button>@endcan</div></td></tr></template>

    <template id="previous-page">
        <li class="page-item previous">
            <a href="#" class="page-link" data-item><
                <i class="previous"></i>
            </a>
        </li>
    </template>

    <template id="item-page">
        <li class="page-item" data-active>
            <a href="#" class="page-link" data-item="">5</a>
        </li>
    </template>

    <template id="next-page">
        <li class="page-item next">
            <a href="#" class="page-link" data-item>>
                <i class="next"></i>
            </a>
        </li>
    </template>

    <template id="disabled-page">
        <li class="page-item disabled">
            <span class="page-link">...</span>
        </li>
    </template>
    @can('restoreCategory_defaultEquipment')
    <div class="modal fade next-aux-modal next-aux-modal-form" id="restoreModal" data-url="{{ route('categoryEquipment.restore', ['id' => '__ID__']) }}" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restoreModalLabel">Restaurar Categoría de Equipo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body next-form-section">
                    <p>¿Estás seguro de que quieres restaurar esta categoría?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-primary" id="btn-restore-confirm">Restaurar</button>
                </div>
            </div>
        </div>
    </div>
    @endcan

@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/categoryEquipment/indexEliminated.js') }}"></script>
    <script src="{{ asset('js/categoryEquipment/restore.js') }}"></script>
@endsection
