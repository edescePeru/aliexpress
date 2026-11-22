@extends('layouts.appAdmin2')

@section('openConfigRH')
    menu-open
@endsection

@section('activeConfigRH')
    active
@endsection

@section('openWorkFunction')
    menu-open
@endsection

@section('activeListWorkFunctionDeleted')
    active
@endsection

@section('title')
    Cargos
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('page-header')
    <div class="next-page-heading"><span class="next-page-eyebrow">Configuración · RRHH</span><h1 class="page-title">Cargos</h1><p class="next-page-description">Catálogo laboral compartido.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Registros inhabilitados</strong><span>Habilitación de registros del catálogo.</span></div><div class="next-toolbar-actions flex-wrap"><a href="{{ route('workFunctions.index') }}" class="btn btn-outline-secondary btn-sm">Habilitados</a>@can('function_worker')<a href="{{ route('workFunctions.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus" aria-hidden="true"></i> Nuevo registro</a>@endcan</div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('workFunctions.index') }}"><i class="fa fa-archive"></i> Cargos</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <div class="next-operational-list">
        <table class="table table-bordered table-hover table-sm" id="dynamic-table">
            <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>

    <div id="modalDelete" class="modal fade next-aux-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirmar habilitación</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formDelete" data-url="{{ route('workFunctions.restore') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="workFunction_id" name="workFunction_id">
                        <p> ¿Desea habilitar este cargo? </p>
                        <p id="description"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-submit" class="btn btn-success">Habilitar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('plugins')
    <!-- Datatables -->
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>

@endsection

@section('scripts')
    <script src="{{ asset('js/workFunction/indexDeleted.js') }}"></script>
@endsection