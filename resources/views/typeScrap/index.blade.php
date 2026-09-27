@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openTypeScrap')
    menu-open
@endsection

@section('activeTypeScrap')
    active
@endsection

@section('activeListTypeScrap')
    active
@endsection

@section('title')
    Tipos de retacería
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Tipos de retacería</h1>
        <p class="next-page-description">Administra las dimensiones base utilizadas para clasificar la retacería.</p>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Catálogo de tipos de retacería</strong>
            <span>Busca, ordena y consulta sus dimensiones contractuales.</span>
        </div>
        <div class="next-toolbar-actions">
            @can('destroy_typeScrap')
                <button
                    type="button"
                    id="delete-selected"
                    class="btn btn-outline-danger btn-sm"
                    data-url-bulk="{{ url('/dashboard/typescrap/delete-multiple') }}"
                    data-backend-sync-open="true"
                    aria-disabled="true"
                    title="Pendiente de permiso explícito y validación backend"
                    disabled>
                    Eliminar seleccionados
                </button>
            @endcan
            @can('create_typeScrap')
                <a href="{{ route('typescrap.create') }}" class="btn btn-primary btn-sm">Nuevo tipo de retacería</a>
            @endcan
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Materiales</li>
        <li class="breadcrumb-item active" aria-current="page">Tipos de retacería</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <section class="next-operational-list" aria-label="Listado de tipos de retacería">
        <table
            class="table table-bordered table-hover table-sm next-data-table"
            id="dynamic-table"
            data-url-data="{{ url('/dashboard/all/typescraps') }}"
            data-url-edit="{{ url('/dashboard/editar/retaceria') }}"
            data-url-create="{{ route('typescrap.create') }}"
            data-can-create="{{ auth()->user()->can('create_typeScrap') ? 'true' : 'false' }}"
            data-can-update="{{ auth()->user()->can('update_typeScrap') ? 'true' : 'false' }}"
            data-can-destroy="{{ auth()->user()->can('destroy_typeScrap') ? 'true' : 'false' }}">
            <thead>
            <tr>
                @can('destroy_typeScrap')
                    <th class="text-center" data-selection>
                        <div class="custom-control custom-checkbox d-inline-block">
                            <input type="checkbox" class="custom-control-input" id="select-all"
                                aria-label="Seleccionar todos los tipos de retacería visibles">
                            <label class="custom-control-label" for="select-all">
                                <span class="sr-only">Seleccionar todos los tipos de retacería visibles</span>
                            </label>
                        </div>
                    </th>
                @endcan
                <th class="text-left">Nombre</th>
                <th class="text-center">Largo</th>
                <th class="text-center">Ancho</th>
                @canany(['update_typeScrap', 'destroy_typeScrap'])
                    <th class="text-center" data-buttons>Acciones</th>
                @endcanany
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>

    @can('destroy_typeScrap')
        <div id="modalDelete" class="modal fade next-aux-modal" tabindex="-1" role="dialog"
            aria-labelledby="modalDeleteLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form id="formDelete" class="next-aux-modal-form" data-url="{{ route('typescrap.destroy') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDeleteLabel">Eliminar tipo de retacería</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="typeScrap_id" name="typeScrap_id">
                            <p class="mb-2">No podrá eliminarse si está siendo utilizado por uno o más materiales.</p>
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
    <script src="{{ asset('js/typescrap/index.js') }}?v={{ time() }}"></script>
@endsection
