@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openSubType')
    menu-open
@endsection

@section('activeSubType')
    active
@endsection

@section('activeListSubType')
    active
@endsection

@section('title')
    Subtipos
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Subtipos</h1>
        <p class="next-page-description">Administra los subtipos asociados a los tipos de material.</p>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Catálogo de subtipos</strong>
            <span>Busca, ordena y administra los registros del tenant actual.</span>
        </div>
        <div class="next-toolbar-actions">
            @can('destroy_subType')
                <button type="button" id="delete-selected" class="btn btn-outline-danger btn-sm"
                    data-url-bulk="{{ url('/dashboard/subtype/delete-multiple') }}"
                    data-backend-sync-open="true" aria-disabled="true"
                    title="Pendiente de autorización backend" disabled>
                    Eliminar seleccionados
                </button>
            @endcan
            @can('create_subType')
                <a href="{{ route('subtype.create') }}" class="btn btn-primary btn-sm">Nuevo subtipo</a>
            @endcan
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Materiales</li>
        <li class="breadcrumb-item active" aria-current="page">Subtipos</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <section class="next-operational-list" aria-label="Listado de subtipos">
        <table class="table table-bordered table-hover table-sm next-data-table" id="dynamic-table"
            data-url-data="{{ url('/dashboard/all/subtypes') }}"
            data-url-edit="{{ url('/dashboard/editar/subtipo') }}">
            <thead>
            <tr>
                @can('destroy_subType')
                    <th class="text-center" data-selection>
                        <div class="custom-control custom-checkbox d-inline-block">
                            <input type="checkbox" class="custom-control-input" id="select-all"
                                aria-label="Seleccionar todos los subtipos visibles">
                            <label class="custom-control-label" for="select-all">
                                <span class="sr-only">Seleccionar todos los subtipos visibles</span>
                            </label>
                        </div>
                    </th>
                @endcan
                <th class="text-left">Subtipo</th>
                <th class="text-left">Tipo de material</th>
                <th class="text-left">Descripción</th>
                @canany(['update_subType', 'destroy_subType'])
                    <th class="text-center" data-buttons>Acciones</th>
                @endcanany
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>

    @can('destroy_subType')
        <div id="modalDelete" class="modal fade next-aux-modal" tabindex="-1" role="dialog"
            aria-labelledby="modalDeleteLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form id="formDelete" class="next-aux-modal-form" data-url="{{ route('subtype.destroy') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDeleteLabel">Eliminar subtipo</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="subtype_id" name="subtype_id">
                            <p class="mb-2">Esta acción eliminará el subtipo seleccionado.</p>
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
    <script src="{{ asset('js/subtype/index.js') }}?v={{ time() }}"></script>
@endsection
