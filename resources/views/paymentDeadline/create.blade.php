@extends('layouts.appAdmin2')

@section('openPaymentDeadline')
    menu-open
@endsection

@section('activePaymentDeadline')
    active
@endsection

@section('activeCreatePaymentDeadline')
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
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Nuevo plazo de pago</strong><span>Configuración del plazo y su uso.</span></div><div class="next-toolbar-actions"><a href="{{ route('paymentDeadline.index') }}" class="btn btn-outline-secondary btn-sm">Volver al listado</a></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('paymentDeadline.index') }}"><i class="fa fa-archive"></i> Plazos de pago</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nuevo</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal" data-url="{{ route('paymentDeadline.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="form_tenant_id" value="{{ \App\Support\TenantContext::tenantId() }}">
        <input type="hidden" name="form_company_id" value="{{ \App\Support\TenantContext::companyId() }}">
<section class="next-form-section"><div class="row"><div class="col-md-6 form-group"><label for="description">Plazo de pago <span class="next-required">(*)</span></label><input id="description" type="text" class="form-control @error('description') is-invalid @enderror" onkeyup="mayus(this);" name="description" value="{{ old('description') }}">@error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
<div class="col-md-6 form-group"><label for="days">Cantidad de días <span class="next-required">(*)</span></label><input id="days" type="number" step="1" min="0" class="form-control @error('days') is-invalid @enderror" onkeyup="mayus(this);" name="days" value="{{ old('days') }}">@error('days')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
<div class="col-md-6 form-group"><label for="type">Usado en</label><select id="type" name="type" class="form-control select2 w-100 @error('type') is-invalid @enderror"><option></option><option value="purchases" {{ (string) old('type') === 'purchases' ? 'selected' : '' }} >COMPRAS / SERVICIOS</option><option value="quotes" {{ (string) old('type') === 'quotes' ? 'selected' : '' }} >COTIZACIONES</option></select>@error('type')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
<div class="col-md-6 form-group"><label for="credit">Crédito</label><select id="credit" name="credit" class="form-control select2 w-100 @error('credit') is-invalid @enderror"><option></option><option value="1" {{ (string) old('credit') === '1' ? 'selected' : '' }} >SI</option><option value="0" {{ (string) old('credit') === '0' ? 'selected' : '' }} >NO</option></select>@error('credit')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
<div class="col-md-6 form-group"><label for="is_active">Estado</label><select id="is_active" name="is_active" class="form-control @error('is_active') is-invalid @enderror"><option value="1" {{ (string) old('is_active', '1') === '1' ? 'selected' : '' }} >Activo</option><option value="0" {{ (string) old('is_active', '1') === '0' ? 'selected' : '' }} >Inactivo</option></select>@error('is_active')<span class="invalid-feedback">{{ $message }}</span>@enderror</div></div></section><div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap"><button type="reset" class="btn btn-outline-secondary">Cancelar</button><button type="submit" id="btn-submit" class="btn btn-primary">Guardar</button></div></div>
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
                width: '100%',
                placeholder: "Selecione una opción",
            });

            $('#credit').select2({
                width: '100%',
                placeholder: "Seleccione una opción",
            });
        })
    </script>

    <script src="{{ asset('js/paymentDeadline/create.js') }}"></script>
@endsection
