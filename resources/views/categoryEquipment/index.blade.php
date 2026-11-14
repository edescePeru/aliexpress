@extends('layouts.appAdmin2')

@section('openDefaultEquipment')
    menu-open
@endsection

@section('activeDefaultEquipment')
    active
@endsection

@section('activeCategoryEquipment')
    active
@endsection

@section('title', 'Categorías de equipos')

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/typehead/typeahead.css') }}">
@endsection



@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración auxiliar de cotización</span><h1 class="page-title">Categorías de equipos</h1><p class="next-page-description">Clasificación del catálogo de equipos.</p></div>
@endsection

@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Catálogo de categorías</strong><span>Descripción e imagen.</span></div><div class="next-toolbar-actions flex-wrap">@can('eliminatedCategory_defaultEquipment')<a class="btn btn-outline-secondary btn-sm" href="{{ route('categoryEquipment.eliminated') }}">Eliminadas</a>@endcan
 @can('createCategory_defaultEquipment')<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createModal" id="btn-createModal"><i class="fas fa-plus mr-1" aria-hidden="true"></i>Nueva categoría</button>@endcan</div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="#"><i class="fa fa-archive"></i> Categorías de Equipos</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
    <div class="next-operational-list">
        <div class="next-list-toolbar"><div class="w-100">
            <div class="form-row"><div class="col"><input type="text" aria-label="Buscar categoría" placeholder="Nombre de la categoría del equipo" id="inputNameCategoryEquipment" name="search" class="form-control typeahead categoryTypeahead"></div><div class="col-auto"><button type="button" id="btn-search" class="btn btn-primary">Buscar</button></div></div>
            <div id="suggestions-container" class="list-group suggestions-container"></div>
        </div></div>
        <div class="next-list-summary"><strong><span id="numberItems">0</span> categorías</strong><span>Catálogo global legacy</span></div>
        <div class="table-responsive" tabindex="0" aria-label="Categorías de equipos; desplazamiento horizontal"><table class="table table-bordered table-hover table-sm"><thead><tr><th>Descripción</th><th class="text-center">Imagen</th><th class="text-center">Equipos</th><th class="text-center">Acciones</th></tr></thead><tbody id="body-card"><tr><td colspan="4" class="text-center py-4">Cargando categorías…</td></tr></tbody></table></div>
        <div class="next-list-toolbar d-flex flex-wrap justify-content-between align-items-center"><div id="textPagination" class="text-muted small"></div><ul class="pagination pagination-sm mb-0 flex-wrap" id="pagination"></ul></div>
    </div>
    <template id="item-card"><tr data-id><td data-description></td><td class="text-center"><img data-image src="" alt="Imagen de categoría" width="48" class="img-fluid"></td><td data-number class="text-center"></td><td class="text-center"><div class="next-toolbar-actions flex-wrap justify-content-center"><a data-equip href="#" class="btn btn-sm btn-outline-secondary">Equipos</a>
 @can('editCategory_defaultEquipment')<button type="button" data-edit="" data-description="" data-image="" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editModal">Editar</button>@endcan
 @can('destroyCategory_defaultEquipment')<button type="button" data-delete="" data-description="" data-image="" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteModal">Eliminar</button>@endcan</div></td></tr></template>

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
    @can('destroyCategory_defaultEquipment')
    <div class="modal fade next-aux-modal next-aux-modal-form" id="deleteModal" data-url="{{ route('categoryEquipment.destroy', ['id' => '__ID__']) }}" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Eliminar Categoría de Equipo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body next-form-section">
                    <p>¿Estás seguro de que quieres eliminar esta categoría?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-primary" id="btn-delete-confirm">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @can('editCategory_defaultEquipment')
    <div class="modal fade next-aux-modal next-aux-modal-form" id="editModal" data-url="{{ route('categoryEquipment.update', ['categoryEquipment' => '__ID__']) }}" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Categoría de Equipo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body next-form-section">
                    <form id="editCategoryForm"  enctype="multipart/form-data">
                            @csrf
                        <input type="hidden" name="categoryEquipment_id" id="categoryEquipment_id">
                        <div class="form-group">
                            <label for="editDescription">Descripción<span class="right badge badge-danger">(*)</span></label>
                            <input type="text" id="editDescription" class="form-control" placeholder="Nueva descripción">
                        </div>
                        <div class="form-group">
                            <label for="editImage">Imagen </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-file-archive"></i></span>
                                </div>
                                <input type="file" id="editImage" name="editImage" accept="image/*" class="form-control" >
                            </div>
                            <label id="selectedFileNameLabel"></label>
                        </div>
                        <img class="img-fluid" id="editImagePreview" src="" alt="Vista previa de la imagen">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="btn-edit-cancel" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-edit-confirm">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('createCategory_defaultEquipment')
    <div class="modal fade next-aux-modal next-aux-modal-form" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createModalLabel">Nueva Categoría de equipo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formCreate" class="form-horizontal" data-url="{{ route('categoryEquipment.store') }}" enctype="multipart/form-data">
                    @csrf
                <div class="modal-body next-form-section">

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="description" name="description" required>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Imagen</label>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-file-archive"></i></span>
                                </div>
                                <input type="file" id="image" name="image" accept="image/*" class="form-control">
                            </div>
                        </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-outline-primary" id="btn-save-submit">Guardar</button>
                </div>
                </form>
            </div>
        </div>
    </div>
    @endcan



@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/categoryEquipment/index.js') }}"></script>
    <script src="{{ asset('js/categoryEquipment/edit.js') }}"></script>
    <script src="{{ asset('js/categoryEquipment/delete.js') }}"></script>
    <script src="{{ asset('js/categoryEquipment/create.js') }}"></script>
@endsection
