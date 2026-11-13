@extends('layouts.appAdmin2')

@section('openQuote')
    menu-open
@endsection

@section('activeQuote')
    active
@endsection

@section('activePorcentagesQuote')
    active
@endsection

@section('title')
    Porcentajes de cotizaciones
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
    <div class="next-page-heading"><span class="next-page-eyebrow">Configuración · Cotizaciones</span><h1 class="page-title">Porcentajes de cotización</h1><p class="next-page-description">Parámetros compartidos utilizados por las cotizaciones.</p></div>
@endsection


@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Editar porcentaje</strong><span>Nombre y valor del parámetro.</span></div><div class="next-toolbar-actions flex-wrap"><a href="{{ route('porcentageQuote.index') }}" class="btn btn-outline-secondary btn-sm">Volver al listado</a></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('porcentageQuote.index') }}"><i class="fa fa-archive"></i> Porcentajes</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formEdit" class="form-horizontal" data-url="{{ route('porcentageQuote.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" class="form-control" name="porcentage_id" value="{{$porcentageQuote->id}}">

        <section class="next-form-section"><div class="row">
                <div class="col-md-6 form-group">
                    <label for="name">Nombre <span class="next-required">(*)</span></label>
                    <input id="name" type="text" placeholder="Ejm: Porcentaje" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $porcentageQuote->name) }}">
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label for="value">Valor <span class="next-required">(*)</span></label>
                    <input id="value" type="number" min="0" step="0.01" class="form-control @error('value') is-invalid @enderror" name="value" value="{{ old('value', $porcentageQuote->value) }}">
                    @error('value')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
        </div></section>
        <div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap">
            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div></div>
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
    <script src="{{ asset('js/porcentagesQuote/edit.js') }}"></script>
@endsection
