@extends('layouts.appAdmin2')

@section('openContactName')
    menu-open
@endsection

@section('activeContactName')
    active
@endsection

@section('activeCreateContactName')
    active
@endsection

@section('title')
    Contactos
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
<div class="next-page-heading"><span class="next-page-eyebrow">Configuración · Contactos</span><h1 class="page-title">Nuevo contacto</h1><p class="next-page-description">Personas de contacto de los clientes del grupo empresarial.</p></div>
@endsection

@section('page-title')
<div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Nuevo contacto</strong><span>Los campos con * son obligatorios.</span></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('contactName.index') }}"><i class="fa fa-users"></i> Contactos</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nuevo</li>
    </ol>
@endsection

@section('content')
<form id="formCreate" class="form-horizontal" data-url="{{ route('contactName.store') }}" enctype="multipart/form-data">
@csrf

<section class="next-form-section"><div class="next-section-header"><div><h2>Datos del contacto</h2><p>Contacto asociado a un cliente del grupo empresarial.</p></div></div><div class="row">
<div class="form-group col-md-6"><label for="customer_id">Cliente *</label><select id="customer_id" class="form-control select2 @error('customer_id') is-invalid @enderror" name="customer_id" data-states style="width:100%;"><option></option>
@foreach($customers as $customer)<option value="{{ $customer->id }}" {{ (string) old('customer_id', '') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->business_name }}</option>@endforeach
</select>@error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="form-group col-md-6"><label for="contact-name">Nombre de contacto *</label><input id="contact-name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" onkeyup="mayus(this);">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="form-group col-md-4"><label for="contact-area">Área</label><input id="contact-area" type="text" class="form-control @error('area') is-invalid @enderror" name="area" value="{{ old('area') }}" onkeyup="mayus(this);">@error('area')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="form-group col-md-4"><label for="contact-phone">Teléfono</label><input id="contact-phone" type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="form-group col-md-4"><label for="contact-email">Email</label><input id="contact-email" type="text" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
</div></section><div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap justify-content-end"><button type="reset" class="btn btn-outline-secondary">Cancelar</button><button type="button" id="btn-submit" class="btn btn-primary">Guardar</button></div></div>
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
    <script src="{{ asset('js/contactName/create.js') }}"></script>
@endsection
