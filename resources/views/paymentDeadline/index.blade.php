@extends('layouts.appAdmin2')

@section('openPaymentDeadline')
    menu-open
@endsection

@section('activePaymentDeadline')
    active
@endsection

@section('activeListPaymentDeadline')
    active
@endsection

@section('title')
    Plazos de pago
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
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración de empresa</span><h1 class="page-title">Plazos de pago</h1><p class="next-page-description">Condiciones de pago de la empresa activa.</p></div>
@endsection
@section('page-title')
    <div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Plazos de pago</strong><span>Catálogo de condiciones e históricos.</span></div><div class="next-toolbar-actions">
    @can('create_paymentDeadline')
    <a href="{{ route('paymentDeadline.create') }}" class="btn btn-primary btn-sm" > <i class="fa fa-plus font-20"></i> Nuevo plazo de pago </a>
    @endcan
</div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('paymentDeadline.index') }}"><i class="fa fa-archive"></i> Plazos de pago</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <section class="next-operational-list">
        <table class="table table-bordered table-hover table-sm next-data-table" id="dynamic-table">
            <thead>
            <tr>
                <th>Descripción</th>
                <th>Cantidad de días</th>
                <th>Usado en</th>
                <th>Indica crédito</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </section>
    @can('destroy_paymentDeadline')
    <div id="modalDelete" class="modal fade next-aux-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirmar desactivación</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formDelete" data-url="{{ route('paymentDeadline.destroy') }}">
                    @csrf
        <input type="hidden" name="form_tenant_id" value="{{ \App\Support\TenantContext::tenantId() }}">
        <input type="hidden" name="form_company_id" value="{{ \App\Support\TenantContext::companyId() }}">
                    <div class="modal-body">
                        <input type="hidden" id="paymentDeadline_id" name="paymentDeadline_id">
                        <strong>¿Está seguro de desactivar este plazo de pago?</strong>
                        <p id="name"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-submit" class="btn btn-danger">Desactivar</button>
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
    <script src="{{ asset('js/paymentDeadline/index.js') }}"></script>
@endsection