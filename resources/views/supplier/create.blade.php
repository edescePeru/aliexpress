@extends('layouts.appAdmin2')

@section('openSupplier')
    menu-open
@endsection

@section('activeSupplier')
    active
@endsection

@section('activeCreateSupplier')
    active
@endsection

@section('title')
    Proveedores
@endsection

@section('styles-plugins')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">

@endsection

@section('page-header')
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración · Proveedores</span><h1 class="page-title">Nuevo proveedor</h1><p class="next-page-description">Catálogo compartido por las empresas del grupo empresarial.</p></div>
@endsection

@section('page-title')
<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Nuevo proveedor</strong><span>Datos del proveedor. Los campos con * son obligatorios.</span></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('supplier.index') }}"><i class="fa fa-archive"></i> Proveedores</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nuevo</li>
    </ol>
@endsection

@section('content')
<form id="formCreate" class="form-horizontal" data-url="{{ route('supplier.store') }}" enctype="multipart/form-data">
@csrf

<section class="next-form-section"><div class="next-section-header"><div><h2>Identificación y contacto</h2><p>Proveedor del grupo empresarial, sin asignación exclusiva de empresa o sucursal.</p></div></div><div class="row">
<div class="form-group col-md-4"><label for="supplier-ruc">RUC *</label><input id="supplier-ruc" type="text" name="ruc" class="form-control @error('ruc') is-invalid @enderror" value="{{ old('ruc') }}">@error('ruc')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="form-group col-md-8"><label for="supplier-business_name">Razón social *</label><input id="supplier-business_name" type="text" name="business_name" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name') }}" onkeyup="mayus(this);">@error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="form-group col-md-12"><label for="btn-grouped" class="d-block">Extranjero</label><input id="btn-grouped" type="checkbox" name="special" data-bootstrap-switch data-off-color="danger" data-on-text="SI" data-off-text="NO" data-on-color="success" {{ filter_var(old('special', false), FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}></div>
<div class="form-group col-md-12"><label for="supplier-address">Dirección</label><input id="supplier-address" type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" onkeyup="mayus(this);">@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="form-group col-md-6"><label for="supplier-phone">Teléfono</label><input id="supplier-phone" type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="form-group col-md-6"><label for="supplier-email">Email</label><input id="supplier-email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
</div></section>
<div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap justify-content-end"><button type="reset" class="btn btn-outline-secondary">Cancelar</button><button type="button" id="btn-submit" class="btn btn-primary">Guardar</button></div></div>
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
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>

@endsection

@section('scripts')
    <script src="{{ asset('js/supplier/create.js') }}"></script>
    <script>
        $("input[data-bootstrap-switch]").each(function(){
            $(this).bootstrapSwitch();
        });
    </script>
@endsection
