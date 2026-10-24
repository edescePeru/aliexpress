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

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Plazos de pago</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Editar plazo de pago {{$paymentDeadline->description}}</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('quality.index') }}"><i class="fa fa-archive"></i> Plazo de pago</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar</li>
    </ol>
@endsection

@section('content')
<div class="alert alert-info">Si este plazo ya se utilizó, sus días, tipo y condición de crédito se conservan. Para condiciones nuevas, cree otro plazo.</div>
    <form id="formEdit" class="form-horizontal" data-url="{{ route('paymentDeadline.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="form_tenant_id" value="{{ \App\Support\TenantContext::tenantId() }}">
        <input type="hidden" name="form_company_id" value="{{ \App\Support\TenantContext::companyId() }}">
        <input type="hidden" class="form-control" name="paymentDeadline_id" value="{{$paymentDeadline->id}}">

        <div class="form-group row">
            <div class="col-md-6">
                <label for="inputEmail3" class="col-12 col-form-label">Plazo de pago <span class="right badge badge-danger">(*)</span></label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" onkeyup="mayus(this);" name="description" value="{{$paymentDeadline->description}}">
                </div>
            </div>

            <div class="col-md-6">
                <label for="inputEmail3" class="col-12 col-form-label">Cantidad de días <span class="right badge badge-danger">(*)</span></label>
                <div class="col-sm-10">
                    <input type="number" class="form-control" onkeyup="mayus(this);" step="1" min="0" name="days" value="{{$paymentDeadline->days}}">
                </div>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label for="type" class="col-12 col-form-label">Usado en: <span class="right badge badge-danger">(*)</span></label>
                <div class="col-sm-10">
                    <select id="type" name="type" class="form-control form-control-sm select2" style="width: 100%;">
                        <option></option>
                        <option value="purchases" {{ ($paymentDeadline->type == 'purchases') ? 'selected': ''}}>COMPRAS / SERVICIOS</option>
                        <option value="quotes" {{ ($paymentDeadline->type == 'quotes') ? 'selected': ''}}>COTIZACIONES</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <label for="credit" class="col-12 col-form-label">Crédito: <span class="right badge badge-danger">(*)</span></label>
                <div class="col-sm-10">
                    <select id="credit" name="credit" class="form-control form-control-sm select2" style="width: 100%;">
                        <option></option>
                        <option value="1" {{ ($paymentDeadline->credit == '1') ? 'selected': ''}}>SI</option>
                        <option value="0" {{ ($paymentDeadline->credit == '0') ? 'selected': ''}}>NO</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group row"><div class="col-md-6"><label for="is_active" class="col-12 col-form-label">Estado</label><div class="col-sm-10"><select name="is_active" id="is_active" class="form-control"><option value="1" {{ $paymentDeadline->is_active ? 'selected' : '' }}>Activo</option><option value="0" {{ !$paymentDeadline->is_active ? 'selected' : '' }}>Inactivo</option></select></div></div></div>
        <div class="text-center">
            <button type="submit" id="btn-submit" class="btn btn-outline-success">Guardar Cambios</button>
            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
        </div>
        <!-- /.card-footer -->
    </form>
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
    <script>
        $(function () {
            $('#type').select2({
                placeholder: "Selecione una opción",
            });

            $('#credit').select2({
                placeholder: "Seleccione una opción",
            });
        })
    </script>
    <script src="{{ asset('js/paymentDeadline/edit.js') }}"></script>
@endsection
