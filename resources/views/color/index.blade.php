@extends('layouts.appAdmin2')

@section('openConfig') menu-open @endsection
@section('activeConfig') active @endsection
@section('openColor') menu-open @endsection
@section('activeColor') active @endsection
@section('activeListColor') active @endsection

@section('title') Colores @endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Colores</h1>
        <p class="next-page-description">Administra los colores usados para identificar y diferenciar variantes.</p>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Catálogo de colores</strong>
            <span>Busca y administra los registros del tenant actual.</span>
        </div>
        <div class="next-toolbar-actions">
            @can('destroy_color')
                <button type="button" id="btnDeleteSelectedColors" class="btn btn-outline-danger btn-sm"
                    data-backend-sync-open="true" aria-disabled="true"
                    title="Pendiente de validación backend dedicada para eliminación múltiple" disabled>
                    Eliminar seleccionados
                </button>
            @endcan
            @can('create_color')
                <a href="{{ route('color.create') }}" class="btn btn-primary btn-sm">Nuevo color</a>
            @endcan
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Materiales</li>
        <li class="breadcrumb-item active" aria-current="page">Colores</li>
    </ol>
@endsection

@section('content')
    <section id="color-app" class="next-operational-list" aria-label="Listado de colores"
        data-url-data="{{ route('color.data') }}"
        data-url-create="{{ route('color.create') }}"
        data-url-edit="{{ route('color.edit', ['id' => ':id']) }}"
        data-url-delete="{{ route('color.destroy') }}"
        data-url-delete-multiple="{{ route('color.deleteMultiple') }}"
        data-can-create="{{ auth()->user()->can('create_color') ? 1 : 0 }}"
        data-can-update="{{ auth()->user()->can('update_color') ? 1 : 0 }}"
        data-can-delete="{{ auth()->user()->can('destroy_color') ? 1 : 0 }}">

        <div class="next-list-toolbar">
            <div class="next-list-search-row">
                <div class="next-list-search-control">
                    <label class="sr-only" for="colorSearch">Buscar colores</label>
                    <input type="search" id="colorSearch" class="form-control"
                        placeholder="Buscar por nombre, código o nombre corto" autocomplete="off">
                </div>
                <div class="next-list-length">
                    <label for="colorPerPage" class="d-flex align-items-center mb-0">
                        Mostrar
                        <select id="colorPerPage" class="form-control form-control-sm mx-2">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        registros
                    </label>
                </div>
            </div>
        </div>

        <div id="colorResultSummary" class="next-list-summary" aria-live="polite"></div>

        <div class="next-list-content">
            <div class="table-responsive" tabindex="0">
                <table class="table table-bordered table-hover table-sm next-data-table">
                    <thead>
                    <tr>
                        @can('destroy_color')
                            <th class="text-center" data-selection>
                                <div class="custom-control custom-checkbox d-inline-block">
                                    <input type="checkbox" class="custom-control-input" id="checkAllColors" aria-label="Seleccionar todos los colores visibles">
                                    <label class="custom-control-label" for="checkAllColors"><span class="sr-only">Seleccionar todos los colores visibles</span></label>
                                </div>
                            </th>
                        @endcan
                        <th class="text-center">Muestra</th>
                        <th class="text-left">Nombre</th>
                        <th class="text-center">Código</th>
                        <th class="text-center">Nombre corto</th>
                        @canany(['update_color', 'destroy_color'])
                            <th class="text-center" data-buttons>Acciones</th>
                        @endcanany
                    </tr>
                    </thead>
                    <tbody id="colorTableBody"></tbody>
                </table>
            </div>
            <div id="colorEmpty" class="next-table-empty d-none" aria-live="polite"></div>
        </div>

        <div class="next-list-pagination">
            <div id="colorPaginationInfo" class="next-list-page-context" aria-live="polite"></div>
            <nav id="colorPagination" aria-label="Paginación de colores"></nav>
        </div>
    </section>

    @can('destroy_color')
        <div id="modalDelete" class="modal fade next-aux-modal" tabindex="-1" role="dialog" aria-labelledby="modalDeleteLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form id="formDelete" class="next-aux-modal-form" data-url="{{ route('color.destroy') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDeleteLabel">Eliminar color</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="color_id" name="color_id">
                            <p class="mb-2">Esta acción eliminará el color seleccionado si no está asociado a variantes.</p>
                            <p class="mb-0"><strong id="colorDeleteName"></strong></p>
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

@section('scripts')
    <script src="{{ asset('js/color/index.js') }}?v={{ time() }}"></script>
@endsection
