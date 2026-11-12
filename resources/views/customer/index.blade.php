@extends('layouts.appAdmin2')

@section('openCustomer')
    menu-open
@endsection

@section('activeCustomer')
    active
@endsection

@section('activeListCustomer')
    active
@endsection

@section('title')
    Clientes
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
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración · Clientes</span><h1 class="page-title">Clientes</h1><p class="next-page-description">Catálogo compartido por las empresas del grupo empresarial.</p></div>
@endsection
@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-users"></i> Clientes</li>
    </ol>
@endsection

@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Clientes</strong><span>Activos · Catálogo de clientes.</span></div><div class="next-toolbar-actions flex-wrap">@can('destroy_customer')<a href="{{ route('customer.indexrestore') }}" class="btn btn-outline-secondary btn-sm">Eliminados</a>@endcan
@can('create_customer')<a href="{{ route('customer.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus" aria-hidden="true"></i> Nuevo cliente</a>@endcan</div></div>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
    <div class="next-operational-list">
        <table class="table table-bordered table-hover table-sm next-data-table" id="dynamic-table">
            <thead>
            <tr>
                <th>Código</th>
                <th>Razón social</th>
                <th>Documento</th>
                <th>Dirección</th>
                <th>Ubicación</th>
                <th>Estado</th><th>Acciones</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
    @can('destroy_customer')
    <div id="modalDelete" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirmar eliminación</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formDelete" data-url="{{ route('customer.destroy') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="customer_id" name="customer_id">
                        <strong>¿Está seguro de eliminar a esta empresa cliente?</strong>
                        <p id="company"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

@endsection

@section('plugins')
    <!-- Datatables -->
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('js/customer/index.js') }}"></script>
@endsection