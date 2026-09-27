@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openCategory')
    menu-open
@endsection

@section('activeCategory')
    active
@endsection

@section('activeListCategory')
    active
@endsection

@section('title')
    Categorías
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Categorías</h1>
        <p class="next-page-description">Administra las categorías disponibles para organizar y clasificar materiales.</p>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Catálogo de categorías</strong>
            <span>Busca, ordena y administra los registros del tenant actual.</span>
        </div>
        <div class="next-toolbar-actions">
            @can('destroy_category')
                <button
                    type="button"
                    id="delete-selected"
                    class="btn btn-outline-danger btn-sm"
                    data-url-bulk="{{ url('/dashboard/category/delete-multiple') }}"
                    data-backend-sync-open="true"
                    aria-disabled="true"
                    title="Pendiente de autorización backend"
                    disabled>
                    Eliminar seleccionadas
                </button>
            @endcan
            @can('create_category')
                <a href="{{ route('category.create') }}" class="btn btn-primary btn-sm">
                    Nueva categoría
                </a>
            @endcan
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}">Dashboard</a>
        </li>
        <li class="breadcrumb-item">Materiales</li>
        <li class="breadcrumb-item active" aria-current="page">Categorías</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <section class="next-operational-list" aria-label="Listado de categorías">
        <table
            class="table table-bordered table-hover table-sm next-data-table"
            id="dynamic-table"
            data-url-data="{{ url('/dashboard/all/categories') }}"
            data-url-edit="{{ url('/dashboard/editar/categoria') }}">
            <thead>
            <tr>
                @can('destroy_category')
                    <th class="text-center" data-selection>
                        <div class="custom-control custom-checkbox d-inline-block">
                            <input
                                type="checkbox"
                                class="custom-control-input"
                                id="select-all"
                                aria-label="Seleccionar todas las categorías visibles">
                            <label class="custom-control-label" for="select-all">
                                <span class="sr-only">Seleccionar todas las categorías visibles</span>
                            </label>
                        </div>
                    </th>
                @endcan
                <th class="text-left">Nombre</th>
                <th class="text-left">Descripción</th>
                @canany(['update_category', 'destroy_category'])
                    <th class="text-center" data-buttons>Acciones</th>
                @endcanany
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>

    @can('destroy_category')
        <div
            id="modalDelete"
            class="modal fade next-aux-modal"
            tabindex="-1"
            role="dialog"
            aria-labelledby="modalDeleteLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form id="formDelete" class="next-aux-modal-form" data-url="{{ route('category.destroy') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDeleteLabel">Eliminar categoría</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="category_id" name="category_id">
                            <p class="mb-2">Esta acción eliminará la categoría seleccionada.</p>
                            <p class="mb-0"><strong id="name"></strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-delete-cancel data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@section('plugins')
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/Category/index.js') }}?v={{ time() }}"></script>
@endsection
