@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openTalla')
    menu-open
@endsection

@section('activeTalla')
    active
@endsection

@section('activeListTalla')
    active
@endsection

@section('title')
    Tallas
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Tallas</h1>
        <p class="next-page-description">Administra tallas, capacidades y otras medidas usadas por las variantes.</p>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Catálogo de tallas</strong>
            <span>Busca y administra los registros del tenant actual.</span>
        </div>
        <div class="next-toolbar-actions">
            @can('destroy_talla')
                <button
                    type="button"
                    id="btnDeleteSelectedTallas"
                    class="btn btn-outline-danger btn-sm"
                    data-backend-sync-open="true"
                    aria-disabled="true"
                    title="Pendiente de validación backend dedicada para eliminación múltiple"
                    disabled>
                    Eliminar seleccionadas
                </button>
            @endcan
            @can('create_talla')
                <a href="{{ route('talla.create') }}" class="btn btn-primary btn-sm">Nueva talla</a>
            @endcan
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Materiales</li>
        <li class="breadcrumb-item active" aria-current="page">Tallas</li>
    </ol>
@endsection

@section('content')
    <section
        id="talla-app"
        class="next-operational-list"
        aria-label="Listado de tallas"
        data-url-data="{{ route('talla.data') }}"
        data-url-create="{{ route('talla.create') }}"
        data-url-edit="{{ route('talla.edit', ['id' => ':id']) }}"
        data-url-delete="{{ route('talla.destroy') }}"
        data-url-delete-multiple="{{ route('talla.deleteMultiple') }}"
        data-can-create="{{ auth()->user()->can('create_talla') ? 1 : 0 }}"
        data-can-update="{{ auth()->user()->can('update_talla') ? 1 : 0 }}"
        data-can-delete="{{ auth()->user()->can('destroy_talla') ? 1 : 0 }}">

        <div class="next-list-toolbar">
            <div class="next-list-search-row">
                <div class="next-list-search-control">
                    <label class="sr-only" for="tallaSearch">Buscar tallas</label>
                    <input
                        type="search"
                        id="tallaSearch"
                        class="form-control"
                        placeholder="Buscar por nombre, nombre corto o descripción"
                        autocomplete="off">
                </div>
                <div class="next-list-length">
                    <label for="tallaPerPage" class="d-flex align-items-center mb-0">
                        Mostrar
                        <select id="tallaPerPage" class="form-control form-control-sm mx-2">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        registros
                    </label>
                </div>
            </div>
        </div>

        <div id="tallaResultSummary" class="next-list-summary" aria-live="polite"></div>

        <div class="next-list-content">
            <div class="table-responsive" tabindex="0">
                <table class="table table-bordered table-hover table-sm next-data-table">
                    <thead>
                    <tr>
                        @can('destroy_talla')
                            <th class="text-center" data-selection>
                                <div class="custom-control custom-checkbox d-inline-block">
                                    <input type="checkbox" class="custom-control-input" id="checkAllTallas" aria-label="Seleccionar todas las tallas visibles">
                                    <label class="custom-control-label" for="checkAllTallas"><span class="sr-only">Seleccionar todas las tallas visibles</span></label>
                                </div>
                            </th>
                        @endcan
                        <th class="text-left">Nombre</th>
                        <th class="text-center">Nombre corto</th>
                        <th class="text-left">Descripción</th>
                        @canany(['update_talla', 'destroy_talla'])
                            <th class="text-center" data-buttons>Acciones</th>
                        @endcanany
                    </tr>
                    </thead>
                    <tbody id="tallaTableBody"></tbody>
                </table>
            </div>
            <div id="tallaEmpty" class="next-table-empty d-none" aria-live="polite"></div>
        </div>

        <div class="next-list-pagination">
            <div id="tallaPaginationInfo" class="next-list-page-context" aria-live="polite"></div>
            <nav id="tallaPagination" aria-label="Paginación de tallas"></nav>
        </div>
    </section>

    @can('destroy_talla')
        <div id="modalDelete" class="modal fade next-aux-modal" tabindex="-1" role="dialog" aria-labelledby="modalDeleteLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form id="formDelete" class="next-aux-modal-form" data-url="{{ route('talla.destroy') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDeleteLabel">Eliminar talla</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="talla_id" name="talla_id">
                            <p class="mb-2">Esta acción eliminará la talla seleccionada si no está asociada a variantes.</p>
                            <p class="mb-0"><strong id="tallaDeleteName"></strong></p>
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
    <script src="{{ asset('js/talla/index.js') }}?v={{ time() }}"></script>
@endsection
