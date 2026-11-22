@extends('layouts.appAdmin2')

@section('openConfigRH')
    menu-open
@endsection

@section('activeConfigRH')
    active
@endsection

@section('openPensionSystem')
    menu-open
@endsection

@section('activeListPensionSystem')
    active
@endsection

@section('title')
    Sistema de pensión
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
    <div class="next-page-heading"><span class="next-page-eyebrow">Configuración · RRHH</span><h1 class="page-title">Sistemas de pensión</h1><p class="next-page-description">Catálogo laboral compartido.</p></div>
@endsection

@section('page-title')
    <div class="next-page-toolbar next-page-toolbar-context-only"><div class="next-toolbar-context"><strong>Editar registro</strong><span>Datos del catálogo laboral.</span></div><div class="next-toolbar-actions flex-wrap"><a href="{{ route('pensionSystems.index') }}" class="btn btn-outline-secondary btn-sm">Volver al listado</a></div></div>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('pensionSystems.index') }}"><i class="fa fa-archive"></i> Sistemas de pensión</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal" data-url="{{ route('pensionSystems.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="pensionSystem_id" value="{{ $pensionSystem->id }}">
        <section class="next-form-section"><div class="row">
            <div class="col-md-6 form-group">
                <label for="description">Descripción <span class="next-required">(*)</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="far fa-keyboard"></i></span>
                    </div>
                    <input id="description" type="text" class="form-control" value="{{ old('description', $pensionSystem->description) }}" name="description" >
                </div>
            </div>

            <div class="col-md-6 form-group">
                <label for="percentage">Porcentaje <span class="next-required">(*)</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-sort-numeric-up"></i></span>
                    </div>
                    <input id="percentage" type="number" min="0" step="0.01" value="{{ old('percentage', $pensionSystem->percentage) }}" class="form-control" name="percentage" >
                    <div class="input-group-append">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>
        </div>

        </section>
        <div class="next-list-toolbar"><div class="next-toolbar-actions flex-wrap">
            <button type="button" id="btn-submit" class="btn btn-primary">Guardar</button>
            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
        </div>
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
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/inputmask/min/jquery.inputmask.bundle.min.js') }}"></script>

@endsection

@section('scripts')
    <script src="{{ asset('js/pensionSystem/edit.js') }}"></script>
@endsection
