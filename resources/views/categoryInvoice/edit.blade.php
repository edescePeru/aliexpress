@extends('layouts.appAdmin2')

@section('openCategoryInvoice')
    menu-open
@endsection

@section('activeCategoryInvoice')
    active
@endsection

@section('activeCreateCategoryInvoice')
    active
@endsection

@section('title')
    Categorias de Facturas
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
<div class="next-page-heading"><span class="next-page-eyebrow">Finanzas · Configuración</span><h1 class="page-title">Editar categoría de factura</h1><p class="next-page-description">Categorías de la empresa activa.</p></div>
@endsection
@section('page-title')
<div class="next-page-toolbar"><div class="next-toolbar-context"><strong>Datos de la categoría</strong><span>Nombre y descripción.</span></div><div class="next-toolbar-actions flex-wrap">
<a href="{{ route('categoryInvoice.index') }}" class="btn btn-outline-secondary btn-sm">Volver al listado</a>
</div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('categoryInvoice.index') }}"><i class="fa fa-archive"></i> Categorías de facturas</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="{{ route('categoryInvoice.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" class="form-control" name="category_id" value="{{$category->id}}">

        <section class="next-form-section"><div class="row">
<div class="col-md-6 form-group"><label for="category-name">Nombre <span class="next-required">(*)</span></label><input type="text" id="category-name" class="form-control @error('name') is-invalid @enderror" onkeyup="mayus(this);" name="name" value="{{ old('name', $category->name) }}" placeholder="Nombre">@error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
<div class="col-md-6 form-group"><label for="category-description">Descripción</label><input type="text" id="category-description" class="form-control @error('description') is-invalid @enderror" onkeyup="mayus(this);" name="description" value="{{ old('description', $category->description) }}" placeholder="Descripción">@error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
</div></section><div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap"><button type="reset" class="btn btn-outline-secondary">Cancelar</button><button type="submit" class="btn btn-primary">Guardar cambios</button></div></div>
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
    <script src="{{ asset('js/categoryInvoice/edit.js') }}"></script>
@endsection
