@extends('layouts.appAdmin2')

@section('openConfig')
    menu-open
@endsection

@section('activeConfig')
    active
@endsection

@section('openWarrant')
    menu-open
@endsection

@section('activeWarrant')

@endsection

@section('activeListWarrant')
    active
@endsection

@section('title')
    Cédulas
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
    <div class="next-page-heading"><span class="next-page-eyebrow">Configuración · Materiales</span><h1 class="page-title">Cédulas</h1><p class="next-page-description">Catálogo compartido de cédulas de material.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Editar cédula</strong><span>Nombre y descripción de la cédula.</span></div><div class="next-toolbar-actions flex-wrap"><a href="{{ route('warrant.index') }}" class="btn btn-outline-secondary btn-sm">Volver al listado</a></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('warrant.index') }}"><i class="fa fa-archive"></i> Cédulas</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="{{ route('warrant.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" class="form-control" name="warrant_id" value="{{$warrant->id}}">

<section class="next-form-section"><div class="row">
            <div class="col-md-6 form-group"><label for="name">Cédula <span class="next-required">(*)</span></label>
                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" onkeyup="mayus(this);" name="name" value="{{ old('name', $warrant->name) }}">
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-6 form-group"><label for="description">Descripción</label>
                <input id="description" type="text" class="form-control @error('description') is-invalid @enderror" onkeyup="mayus(this);" name="description" value="{{ old('description', $warrant->description) }}">
                @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div></section>
        <div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap"><button type="submit" class="btn btn-primary">Guardar</button><button type="reset" class="btn btn-outline-secondary">Cancelar</button></div></div>
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
    <script src="{{ asset('js/warrant/edit.js') }}"></script>
@endsection
