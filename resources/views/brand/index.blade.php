@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openBrand')
    menu-open
@endsection

@section('activeBrand')
    active
@endsection

@section('activeListBrand')
    active
@endsection

@section('title')
    Marcas
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading">
        <span class="next-page-eyebrow">Materiales · Configuraciones</span>
        <h1 class="page-title">Marcas</h1>
        <p class="next-page-description">Administra las marcas disponibles para clasificar materiales y sus modelos.</p>
    </div>
@endsection

@section('page-title')
    <div class="next-page-toolbar">
        <div class="next-toolbar-context">
            <strong>Catálogo de marcas</strong>
            <span>Busca, ordena y administra los registros del tenant actual.</span>
        </div>
        <div class="next-toolbar-actions">
            @can('destroy_brand')
                <button
                    type="button"
                    id="delete-selected"
                    class="btn btn-outline-danger btn-sm"
                    data-url-bulk="{{ url('/dashboard/brand/delete-multiple') }}"
                    data-backend-sync-open="true"
                    aria-disabled="true"
                    title="Pendiente de permiso explícito y política de dependencias backend"
                    disabled>
                    Eliminar seleccionados
                </button>
            @endcan
            @can('create_brand')
                <a href="{{ route('brand.create') }}" class="btn btn-primary btn-sm">Nueva marca</a>
            @endcan
        </div>
    </div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.principal') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Materiales</li>
        <li class="breadcrumb-item active" aria-current="page">Marcas</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <section class="next-operational-list" aria-label="Listado de marcas">
        <table
            class="table table-bordered table-hover table-sm next-data-table"
            id="dynamic-table"
            data-url-data="{{ url('/dashboard/all/brands') }}"
            data-url-edit="{{ url('/dashboard/editar/marca') }}"
            data-url-create="{{ route('brand.create') }}"
            data-can-create="{{ auth()->user()->can('create_brand') ? 'true' : 'false' }}"
            data-can-update="{{ auth()->user()->can('update_brand') ? 'true' : 'false' }}"
            data-can-destroy="{{ auth()->user()->can('destroy_brand') ? 'true' : 'false' }}">
            <thead>
            <tr>
                @can('destroy_brand')
                    <th class="text-center" data-selection>
                        <div class="custom-control custom-checkbox d-inline-block">
                            <input type="checkbox" class="custom-control-input" id="select-all"
                                aria-label="Seleccionar todas las marcas visibles">
                            <label class="custom-control-label" for="select-all">
                                <span class="sr-only">Seleccionar todas las marcas visibles</span>
                            </label>
                        </div>
                    </th>
                @endcan
                <th class="text-left">Nombre</th>
                <th class="text-left">Comentario</th>
                @canany(['update_brand', 'destroy_brand'])
                    <th class="text-center" data-buttons>Acciones</th>
                @endcanany
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>

    @can('destroy_brand')
        <div id="modalDelete" class="modal fade next-aux-modal" tabindex="-1" role="dialog"
            aria-labelledby="modalDeleteLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form id="formDelete" class="next-aux-modal-form" data-url="{{ route('brand.destroy') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDeleteLabel">Eliminar marca</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="brand_id" name="brand_id">
                            <p class="mb-2">El backend actual archiva la marca, elimina sus modelos y desvincula esos modelos de los materiales.</p>
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
    <script src="{{ asset('js/brand/index.js') }}?v={{ time() }}"></script>
@endsection
